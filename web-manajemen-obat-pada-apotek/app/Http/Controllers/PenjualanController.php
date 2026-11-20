<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use App\Models\Pelanggan;
use App\Models\Penjualan;
use App\Models\PenjualanDetail;
use App\Models\ScreeningAlergi;
use App\Services\SkriningAlergiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenjualanController extends Controller
{
    private const LABEL_HASIL = [
        'aman'       => 'AMAN',
        'peringatan' => 'TIDAK AMAN - Berpotensi memicu alergi',
    ];

    // Halaman utama penjualan dan riwayat transaksi
    public function index()
    {
        return view('penjualan.index', [
            'transaksi' => Penjualan::with('details')
                ->latest('tgl_transaksi')
                ->get(),
        ]);
    }

    // Pencarian obat berdasarkan nama
    public function cariObat(Request $request)
    {
        $q = trim((string)$request->query('q'));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Medicine::where('nama_obat', 'like', '%' . $q . '%')
                ->orderBy('nama_obat')
                ->limit(8)
                ->get([
                    'id_obat',
                    'nama_obat',
                    'jenis',
                    'stok',
                    'harga',
                ])
        );
    }

    // Tarik data alergi dari database saat mengetik riwayat alergi
    public function cariAlergi(Request $request)
    {
        $q = trim((string)$request->query('q'));

        if (mb_strlen($q) < 1) {
            return response()->json([]);
        }

        $rows = DB::table('alergi_pelanggans')
            ->select('nama_alergi')
            ->whereNotNull('nama_alergi')
            ->where('nama_alergi', '!=', '')
            ->where('nama_alergi', 'like', '%' . $q . '%')
            ->distinct()
            ->orderBy('nama_alergi')
            ->limit(8)
            ->pluck('nama_alergi');

        return response()->json($rows);
    }

    // Proses skrining alergi
    public function skrining(Request $request, SkriningAlergiService$svc)
    {
        $data =$request->validate([
            'id_obat' => [
                'required',
                'integer',
                'exists:obats,id_obat',
            ],
            'alergi' => [
                'nullable',
                'string',
                'max:500',
            ],
        ], [
            'id_obat.required' => 'Pilih obat dari daftar hasil pencarian.',
            'id_obat.exists'   => 'Obat tidak ditemukan. Pilih ulang dari daftar hasil pencarian.',
        ]);

        $obat = Medicine::with('kandungan')
            ->findOrFail($data['id_obat']);

        $diinput = $svc->daftarAlergi($data['alergi'] ?? '');

        $out = DB::transaction(function () use ($obat,$diinput, $svc) {$pelanggan = Pelanggan::firstOrCreate([
                'nama_pelanggan' => 'Pelanggan Umum',
            ]);

            $periksa =$svc->periksa($obat,$diinput);

            $status =$periksa['status'] === 'aman' ? 'aman' : 'peringatan';

            $screening = ScreeningAlergi::create([
                'id_pelanggan'  => $pelanggan->id_pelanggan,
                'id_obat'       => $obat->id_obat,
                'id_alergi'     => null,
                'id_pengelola'  => auth()->id(),
                'tgl_screening' => now(),
                'hasil'         => self::LABEL_HASIL[$status],
                'status'        => $status,
                'alergi_input'  => implode(', ', $diinput),
                'temuan'        => $periksa['temuan'],
            ]);

            return compact('screening', 'periksa', 'diinput', 'status');
        });

        $tersedia = $this->stokTersedia($obat);
        $lolosSkrining =$out['status'] === 'aman';

        $alternatif =$lolosSkrining
            ? collect()
            : $svc->alternatif($obat,$out['diinput']);

        return response()->json([
            'id_screening' => $out['screening']->id_screening,
            'status'       => $out['status'],
            'hasil'        => self::LABEL_HASIL[$out['status']],
            'temuan'       => $out['periksa']['temuan'],
            'alergi'       => $out['diinput'],

            'obat' => [
                'id_obat'   => $obat->id_obat,
                'nama_obat' => $obat->nama_obat,
                'jenis'     => $obat->jenis,
                'harga'     => (float) $obat->harga,
                'stok'      => $tersedia,
                'kandungan' => $out['periksa']['kandungan'],
            ],

            'bisa_dijual' => $lolosSkrining &&$tersedia > 0,

            'alternatif' => $alternatif->map(fn ($m) => [
                'id_obat'   => $m->id_obat,
                'nama_obat' => $m->nama_obat,
                'jenis'     => $m->jenis,
                'stok'      => (int) $m->stok,
                'harga'     => (float) $m->harga,
            ])->values(),
        ]);
    }

    // Proses pembayaran
    public function bayar(Request $request)
    {
        $data =$request->validate([
            'items' => [
                'required',
                'array',
                'min:1',
                'max:50',
            ],
            'items.*.id_screening' => [
                'required',
                'integer',
                'distinct',
            ],
            'items.*.jumlah' => [
                'required',
                'integer',
                'min:1',
                'max:10000',
            ],
            'metode_bayar' => [
                'required',
                'in:Tunai,QRIS,Transfer',
            ],
            'dibayar' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ], [
            'items.required' => 'Daftar penjualan masih kosong.',
            'items.min'      => 'Daftar penjualan masih kosong.',
        ]);

        $hasil = DB::transaction(function () use ($data) {
            $ids = collect($data['items'])
                ->pluck('id_screening')
                ->all();

            $screenings = ScreeningAlergi::whereIn('id_screening',$ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id_screening');

            if ($screenings->count() !== count($ids)) {$this->gagal('Ada data skrining yang tidak ditemukan. Ulangi skrining obat tersebut.');
            }

            if ($screenings->pluck('id_pelanggan')->unique()->count() !== 1) {$this->gagal('Data skrining tidak sesuai. Silakan ulangi skrining.');
            }

            $obatDipakai = [];
            $baris = [];$total = 0;

            foreach ($data['items'] as $item) {$s = $screenings[$item['id_screening']];

                $obat = Medicine::lockForUpdate()
                    ->find($s->id_obat);

                if (!$obat) {$this->gagal('Obat tidak ditemukan. Ulangi skrining.');
                }

                $nama =$obat->nama_obat;

                if ($s->id_penjualan) {
                    $this->gagal("Skrining {$nama} sudah dipakai di transaksi lain. Ulangi skrining.");
                }

                if ($s->tgl_screening->lt(now()->subHours(12))) {$this->gagal("Hasil skrining {$nama} sudah lebih dari 12 jam. Ulangi skrining.");
                }

                if ($s->status !== 'aman') {
                    $this->gagal("{$nama} tidak lolos skrining dan tidak dapat dijual.");
                }

                if (in_array($obat->id_obat,$obatDipakai, true)) {
                    $this->gagal("{$nama} muncul dua kali dalam daftar.");
                }

                $obatDipakai[] =$obat->id_obat;

                $jumlah = (int)$item['jumlah'];

                $this->kurangiStok($obat,$jumlah);

                $harga = (float)$obat->harga;
                $subtotal = $harga * $jumlah;
                $total +=$subtotal;

                $baris[] = compact('obat', 's', 'jumlah', 'harga', 'subtotal');
            }

            $dibayar =$data['metode_bayar'] === 'Tunai'
                ? (float) ($data['dibayar'] ?? 0) :$total;

            if ($dibayar < $total) {$this->gagal('Uang yang diterima kurang dari total belanja.');
            }

            $awal = 'INV-' . now()->format('Ymd') . '-';

            $urut = Penjualan::where('no_transaksi', 'like',$awal . '%')
                ->lockForUpdate()
                ->count() + 1;

            $penjualan = Penjualan::create([
                'no_transaksi'  => $awal . str_pad((string) $urut, 4, '0', STR_PAD_LEFT),
                'id_pelanggan'  => $screenings->first()->id_pelanggan,
                'id_pengelola'  => auth()->id(),
                'total'         => $total,
                'metode_bayar'  => $data['metode_bayar'],
                'dibayar'       => $dibayar,
                'kembalian'     => $dibayar -$total,
                'tgl_transaksi' => now(),
            ]);

            foreach ($baris as$b) {
                PenjualanDetail::create([
                    'id_penjualan' => $penjualan->id_penjualan,
                    'id_obat'      => $b['obat']->id_obat,
                    'nama_obat'    => $b['obat']->nama_obat,
                    'harga'        => $b['harga'],
                    'jumlah'       => $b['jumlah'],
                    'subtotal'     => $b['subtotal'],
                    'id_screening' => $b['s']->id_screening,
                ]);
            }

            ScreeningAlergi::whereIn('id_screening', $ids)->update([
                'id_penjualan' => $penjualan->id_penjualan,
            ]);

            return [
                'id_penjualan' => $penjualan->id_penjualan,
                'no_transaksi' => $penjualan->no_transaksi,
                'waktu'        => $penjualan->tgl_transaksi->format('d/m/Y H:i'),
                'metode_bayar' => $penjualan->metode_bayar,
                'total'        => (float) $total,
                'dibayar'      => (float) $dibayar,
                'kembalian'    => (float) ($dibayar -$total),
                'items'        => array_map(fn ($b) => [
                    'nama_obat' => $b['obat']->nama_obat,
                    'jumlah'    => $b['jumlah'],
                    'harga'     => $b['harga'],
                    'subtotal'  => $b['subtotal'],
                ], $baris),
            ];
        });

        return response()->json($hasil);
    }

    private function gagal(string $pesan): void
    {
        throw ValidationException::withMessages([
            'items' => [$pesan],
        ]);
    }

    private function stokTersedia(Medicine $obat): int
    {
        if (!MedicineBatch::where('id_obat', $obat->id_obat)->exists()) {
            return (int) $obat->stok;
        }

        return (int) MedicineBatch::where('id_obat', $obat->id_obat)
            ->where('current_stock', '>', 0)
            ->whereDate('expired_date', '>=', today())
            ->sum('current_stock');
    }

    private function kurangiStok(Medicine $obat, int$jumlah): void
    {
        if (!MedicineBatch::where('id_obat', $obat->id_obat)->exists()) {
            if ($obat->stok < $jumlah) {$this->gagal("Stok {$obat->nama_obat} tidak cukup (tersedia {$obat->stok}).");
            }
        } else {
            $batches = MedicineBatch::where('id_obat',$obat->id_obat)
                ->where('current_stock', '>', 0)
                ->whereDate('expired_date', '>=', today())
                ->orderBy('expired_date')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $tersedia = (int)$batches->sum('current_stock');

            if ($tersedia < $jumlah) {$this->gagal("Stok {$obat->nama_obat} yang belum kedaluwarsa hanya {$tersedia}.");
            }

            $sisa =$jumlah;

            foreach ($batches as$b) {
                if ($sisa <= 0) {
                    break;
                }

                $ambil = min($b->current_stock,$sisa);
                $b->current_stock -=$ambil;
                $b->save();
                $sisa -=$ambil;
            }
        }

        $obat->stok = max(0,$obat->stok - $jumlah);$obat->save();
    }
}