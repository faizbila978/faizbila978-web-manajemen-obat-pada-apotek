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
        'aman'             => 'AMAN',
        'peringatan'       => 'TIDAK AMAN - Berpotensi memicu alergi',
        'perlu_verifikasi' => 'PERLU VERIFIKASI APOTEKER',
    ];

    // Halaman utama: skrining + daftar penjualan + pembayaran
    public function index()
    {
        return view('penjualan.index', [
            'riwayatSkrining' => ScreeningAlergi::with(['pelanggan', 'obat'])->latest('tgl_screening')->limit(10)->get(),
            'transaksi'       => Penjualan::with('pelanggan')->latest('tgl_transaksi')->limit(5)->get(),
        ]);
    }

    // Pencarian obat untuk kolom "Nama Obat"
    public function cariObat(Request $request)
    {
        $q = trim((string) $request->query('q'));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        return response()->json(
            Medicine::where('nama_obat', 'like', '%' . $q . '%')
                ->orderBy('nama_obat')->limit(8)
                ->get(['id_obat', 'nama_obat', 'jenis', 'stok', 'harga'])
        );
    }

    // Proses Skrining
    public function skrining(Request $request, SkriningAlergiService $svc)
    {
        $data = $request->validate([
            'nama_pelanggan' => ['required', 'string', 'max:100'],
            'id_obat'        => ['required', 'integer', 'exists:obats,id_obat'],
            'alergi'         => ['required', 'string', 'max:500'],
        ], [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'id_obat.required'        => 'Pilih obat dari daftar hasil pencarian.',
            'id_obat.exists'          => 'Obat tidak ditemukan. Pilih ulang dari daftar hasil pencarian.',
            'alergi.required'         => 'Riwayat alergi wajib diisi. Tulis "Tidak ada" jika pelanggan tidak punya alergi.',
        ]);

        $namaPelanggan = trim(preg_replace('/\s+/', ' ', $data['nama_pelanggan']));
        $obat = Medicine::with('kandungan')->findOrFail($data['id_obat']);
        $diinput = $svc->daftarAlergi($data['alergi']);

        $out = DB::transaction(function () use ($namaPelanggan, $obat, $diinput, $svc) {
            // Cari pelanggan (tidak membedakan huruf besar/kecil) atau buat baru
            $pelanggan = Pelanggan::whereRaw('LOWER(nama_pelanggan) = ?', [mb_strtolower($namaPelanggan)])->first()
                ?? Pelanggan::create(['nama_pelanggan' => $namaPelanggan]);

            // Alergi yang sudah tercatat tetap ikut diperiksa walaupun petugas tidak mengetiknya lagi
            $tersimpan = $svc->daftarAlergi(implode(',', $pelanggan->alergis()->pluck('nama_alergi')->all()));
            foreach ($diinput as $a) {
                if (!in_array($a, $tersimpan, true)) {
                    $pelanggan->alergis()->create(['nama_alergi' => mb_convert_case($a, MB_CASE_TITLE, 'UTF-8')]);
                }
            }
            $semua = array_values(array_unique(array_merge($tersimpan, $diinput)));

            $periksa = $svc->periksa($obat, $semua);
            $status = $periksa['status'];

            $idAlergi = null;
            if (!empty($periksa['temuan'])) {
                $idAlergi = $pelanggan->alergis()
                    ->whereRaw('LOWER(nama_alergi) = ?', [$periksa['temuan'][0]['alergi']])
                    ->value('id_alergi');
            }

            $screening = ScreeningAlergi::create([
                'id_pelanggan'  => $pelanggan->id_pelanggan,
                'id_obat'       => $obat->id_obat,
                'id_alergi'     => $idAlergi,
                'id_pengelola'  => auth()->id(),
                'tgl_screening' => now(),
                'hasil'         => self::LABEL_HASIL[$status],
                'status'        => $status,
                'alergi_input'  => implode(', ', $semua),
                'temuan'        => $periksa['temuan'],
            ]);

            return compact('pelanggan', 'screening', 'periksa', 'semua', 'status');
        });

        $tersedia = $this->stokTersedia($obat);
        $alternatif = $out['status'] === 'aman' ? collect() : $svc->alternatif($obat, $out['semua']);

        return response()->json([
            'id_screening'            => $out['screening']->id_screening,
            'status'                  => $out['status'],
            'hasil'                   => self::LABEL_HASIL[$out['status']],
            'temuan'                  => $out['periksa']['temuan'],
            'alergi'                  => $out['semua'],
            'alergi_tersimpan_tambahan' => array_values(array_diff($out['semua'], $diinput)),
            'pelanggan'               => $out['pelanggan']->nama_pelanggan,
            'obat' => [
                'id_obat'   => $obat->id_obat,
                'nama_obat' => $obat->nama_obat,
                'jenis'     => $obat->jenis,
                'harga'     => (float) $obat->harga,
                'stok'      => $tersedia,
                'kandungan' => $out['periksa']['kandungan'],
            ],
            'bisa_dijual'    => $tersedia > 0,
            'perlu_apoteker' => $out['status'] !== 'aman',
            'alternatif'     => $alternatif->map(fn ($m) => [
                'id_obat' => $m->id_obat, 'nama_obat' => $m->nama_obat,
                'jenis' => $m->jenis, 'stok' => (int) $m->stok, 'harga' => (float) $m->harga,
            ])->values(),
        ]);
    }

    // Persetujuan apoteker untuk hasil "peringatan" / "perlu verifikasi"
    public function setujui(Request $request, int $id)
    {
        $data = $request->validate([
            'nama_apoteker' => ['required', 'string', 'max:100'],
            'catatan'       => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'nama_apoteker.required' => 'Nama apoteker yang menyetujui wajib diisi.',
            'catatan.required'       => 'Alasan/catatan pemeriksaan apoteker wajib diisi.',
            'catatan.min'            => 'Catatan apoteker terlalu singkat (minimal 5 karakter).',
        ]);

        $s = ScreeningAlergi::findOrFail($id);
        abort_if($s->id_penjualan, 422, 'Skrining ini sudah dipakai dalam transaksi.');
        abort_if($s->status === 'aman', 422, 'Hasil skrining ini aman dan tidak memerlukan persetujuan.');

        $s->update([
            'disetujui_oleh'   => trim($data['nama_apoteker']),
            'catatan_apoteker' => trim($data['catatan']),
        ]);

        return response()->json(['ok' => true]);
    }

    // Bayar: simpan transaksi dan kurangi stok (FIFO, batch kedaluwarsa dilewati)
    public function bayar(Request $request)
    {
        $data = $request->validate([
            'items'                => ['required', 'array', 'min:1', 'max:50'],
            'items.*.id_screening' => ['required', 'integer', 'distinct'],
            'items.*.jumlah'       => ['required', 'integer', 'min:1', 'max:10000'],
            'metode_bayar'         => ['required', 'in:Tunai,QRIS,Transfer'],
            'dibayar'              => ['nullable', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Daftar penjualan masih kosong.',
            'items.min'      => 'Daftar penjualan masih kosong.',
        ]);

        $hasil = DB::transaction(function () use ($data) {
            $ids = collect($data['items'])->pluck('id_screening')->all();
            $screenings = ScreeningAlergi::whereIn('id_screening', $ids)->lockForUpdate()->get()->keyBy('id_screening');

            if ($screenings->count() !== count($ids)) {
                $this->gagal('Ada data skrining yang tidak ditemukan. Ulangi skrining obat tersebut.');
            }
            if ($screenings->pluck('id_pelanggan')->unique()->count() !== 1) {
                $this->gagal('Semua obat dalam satu transaksi harus untuk pelanggan yang sama.');
            }

            $obatDipakai = [];
            $baris = [];
            $total = 0;

            foreach ($data['items'] as $item) {
                $s = $screenings[$item['id_screening']];
                $obat = Medicine::lockForUpdate()->find($s->id_obat);
                $nama = $obat->nama_obat ?? 'obat';

                if ($s->id_penjualan) {
                    $this->gagal("Skrining {$nama} sudah dipakai di transaksi lain. Ulangi skrining.");
                }
                if ($s->tgl_screening->lt(now()->subHours(12))) {
                    $this->gagal("Hasil skrining {$nama} sudah lebih dari 12 jam. Ulangi skrining.");
                }
                if ($s->status !== 'aman' && empty($s->disetujui_oleh)) {
                    $this->gagal("{$nama} belum disetujui apoteker.");
                }
                if (!$obat) {
                    $this->gagal('Obat tidak ditemukan. Ulangi skrining.');
                }
                if (in_array($obat->id_obat, $obatDipakai, true)) {
                    $this->gagal("{$nama} muncul dua kali dalam daftar.");
                }
                $obatDipakai[] = $obat->id_obat;

                $jumlah = (int) $item['jumlah'];
                $this->kurangiStok($obat, $jumlah);

                $harga = (float) $obat->harga;
                $subtotal = $harga * $jumlah;
                $total += $subtotal;
                $baris[] = compact('obat', 's', 'jumlah', 'harga', 'subtotal');
            }

            $dibayar = $data['metode_bayar'] === 'Tunai' ? (float) ($data['dibayar'] ?? 0) : $total;
            if ($dibayar < $total) {
                $this->gagal('Uang yang diterima kurang dari total belanja.');
            }

            $awal = 'INV-' . now()->format('Ymd') . '-';
            $urut = Penjualan::where('no_transaksi', 'like', $awal . '%')->lockForUpdate()->count() + 1;

            $penjualan = Penjualan::create([
                'no_transaksi'  => $awal . str_pad((string) $urut, 4, '0', STR_PAD_LEFT),
                'id_pelanggan'  => $screenings->first()->id_pelanggan,
                'id_pengelola'  => auth()->id(),
                'total'         => $total,
                'metode_bayar'  => $data['metode_bayar'],
                'dibayar'       => $dibayar,
                'kembalian'     => $dibayar - $total,
                'tgl_transaksi' => now(),
            ]);

            foreach ($baris as $b) {
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

            ScreeningAlergi::whereIn('id_screening', $ids)->update(['id_penjualan' => $penjualan->id_penjualan]);

            return [
                'no_transaksi' => $penjualan->no_transaksi,
                'waktu'        => $penjualan->tgl_transaksi->format('d/m/Y H:i'),
                'pelanggan'    => Pelanggan::find($penjualan->id_pelanggan)->nama_pelanggan ?? '-',
                'metode_bayar' => $penjualan->metode_bayar,
                'total'        => (float) $total,
                'dibayar'      => (float) $dibayar,
                'kembalian'    => (float) ($dibayar - $total),
                'items'        => array_map(fn ($b) => [
                    'nama_obat' => $b['obat']->nama_obat, 'jumlah' => $b['jumlah'],
                    'harga' => $b['harga'], 'subtotal' => $b['subtotal'],
                ], $baris),
            ];
        });

        return response()->json($hasil);
    }

    // ---------- helper ----------

    private function gagal(string $pesan): void
    {
        throw ValidationException::withMessages(['items' => [$pesan]]);
    }

    // Stok yang boleh dijual = batch yang belum kedaluwarsa (atau kolom stok jika obat belum punya batch)
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

    // FIFO: batch yang paling cepat kedaluwarsa dipakai lebih dulu
    private function kurangiStok(Medicine $obat, int $jumlah): void
    {
        if (!MedicineBatch::where('id_obat', $obat->id_obat)->exists()) {
            if ($obat->stok < $jumlah) {
                $this->gagal("Stok {$obat->nama_obat} tidak cukup (tersedia {$obat->stok}).");
            }
        } else {
            $batches = MedicineBatch::where('id_obat', $obat->id_obat)
                ->where('current_stock', '>', 0)
                ->whereDate('expired_date', '>=', today())
                ->orderBy('expired_date')->orderBy('id')
                ->lockForUpdate()->get();

            $tersedia = (int) $batches->sum('current_stock');
            if ($tersedia < $jumlah) {
                $this->gagal("Stok {$obat->nama_obat} yang belum kedaluwarsa hanya {$tersedia}.");
            }

            $sisa = $jumlah;
            foreach ($batches as $b) {
                if ($sisa <= 0) {
                    break;
                }
                $ambil = min($b->current_stock, $sisa);
                $b->current_stock -= $ambil;
                $b->save();
                $sisa -= $ambil;
            }
        }

        $obat->stok = max(0, $obat->stok - $jumlah);
        $obat->save();
    }
}
