<?php

namespace App\Http\Controllers;

use App\Models\JenisObat;
use App\Models\Kategori;
use App\Models\Medicine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DataMasterController extends Controller
{
    // Satu controller untuk dua jenis data master
    private const TIPE = [
        'kategori' => [
            'model'   => Kategori::class,
            'kolom'   => 'kategori', // kolom di tabel obats
            'judul'   => 'Kategori',
            'contoh'  => 'Contoh: Antibiotik',
            'bantuan' => 'Golongan atau kegunaan obat, misalnya antibiotik, pereda nyeri, atau vitamin.',
        ],
        'jenis' => [
            'model'   => JenisObat::class,
            'kolom'   => 'jenis',
            'judul'   => 'Jenis',
            'contoh'  => 'Contoh: Tablet',
            'bantuan' => 'Bentuk sediaan obat, misalnya tablet, sirup, atau kapsul.',
        ],
    ];

    private function konfig(string $tipe): array
    {
        abort_unless(isset(self::TIPE[$tipe]), 404);
        return self::TIPE[$tipe];
    }

    // Jumlah obat per nilai (tidak membedakan huruf besar/kecil)
    private function hitungPemakaian(string $kolom)
    {
        return Medicine::query()
            ->whereNotNull($kolom)
            ->selectRaw("{$kolom} as nilai, COUNT(*) as total")
            ->groupBy($kolom)
            ->get()
            ->groupBy(fn ($r) => mb_strtolower(trim($r->nilai)))
            ->map(fn ($g) => (int) $g->sum('total'));
    }

    private function validasiNama(Request $request, string $tipe, ?int $abaikanId = null): string
    {
        $cfg = $this->konfig($tipe);
        $model = $cfg['model'];
        $pk = (new $model)->getKeyName();

        $data = $request->validate(
            ['nama' => ['required', 'string', 'max:100']],
            ['nama.required' => 'Nama ' . strtolower($cfg['judul']) . ' wajib diisi.']
        );
        $nama = trim(preg_replace('/\s+/', ' ', $data['nama']));

        $sudahAda = $model::whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])
            ->when($abaikanId, fn ($q) => $q->where($pk, '!=', $abaikanId))
            ->exists();

        if ($sudahAda) {
            throw ValidationException::withMessages(['nama' => "{$cfg['judul']} \"{$nama}\" sudah ada."]);
        }
        return $nama;
    }

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'jenis' ? 'jenis' : 'kategori';
        $cfg = $this->konfig($tab);

        return view('master.index', [
            'tab'    => $tab,
            'cfg'    => $cfg,
            'items'  => $cfg['model']::orderBy('nama')->get(),
            'pakai'  => $this->hitungPemakaian($cfg['kolom']),
            'jumlah' => ['kategori' => Kategori::count(), 'jenis' => JenisObat::count()],
        ]);
    }

    public function store(Request $request, string $tipe)
    {
        $cfg = $this->konfig($tipe);
        $nama = $this->validasiNama($request, $tipe);
        $cfg['model']::create(['nama' => $nama]);

        return redirect()->route('master.index', ['tab' => $tipe])
            ->with('success', "{$cfg['judul']} \"{$nama}\" berhasil ditambahkan.");
    }

    public function update(Request $request, string $tipe, int $id)
    {
        $cfg = $this->konfig($tipe);
        $item = $cfg['model']::findOrFail($id);
        $nama = $this->validasiNama($request, $tipe, $id);
        $lama = $item->nama;

        DB::transaction(function () use ($item, $nama, $lama, $cfg) {
            $item->update(['nama' => $nama]);
            // samakan nama di semua obat yang memakainya
            Medicine::whereRaw('LOWER(' . $cfg['kolom'] . ') = ?', [mb_strtolower($lama)])
                ->update([$cfg['kolom'] => $nama]);
        });

        return redirect()->route('master.index', ['tab' => $tipe])
            ->with('success', "{$cfg['judul']} \"{$lama}\" diubah menjadi \"{$nama}\" (data obat ikut diperbarui).");
    }

    public function destroy(string $tipe, int $id)
    {
        $cfg = $this->konfig($tipe);
        $item = $cfg['model']::findOrFail($id);

        $dipakai = Medicine::whereRaw('LOWER(' . $cfg['kolom'] . ') = ?', [mb_strtolower($item->nama)])->count();
        if ($dipakai > 0) {
            return redirect()->route('master.index', ['tab' => $tipe])
                ->with('error', "{$cfg['judul']} \"{$item->nama}\" masih dipakai {$dipakai} obat. Pindahkan obat itu ke {$cfg['judul']} lain dulu.");
        }

        $nama = $item->nama;
        $item->delete();

        return redirect()->route('master.index', ['tab' => $tipe])
            ->with('success', "{$cfg['judul']} \"{$nama}\" dihapus.");
    }

    // Menambahkan ke master semua kategori/jenis yang sudah dipakai obat tetapi belum terdaftar
    // (berguna setelah mengimpor data obat dari CSV)
    public function sinkron()
    {
        $tambah = 0;
        foreach (self::TIPE as $cfg) {
            $model = $cfg['model'];
            $ada = array_flip(array_map('mb_strtolower', $model::pluck('nama')->all()));
            $nilai = Medicine::whereNotNull($cfg['kolom'])->where($cfg['kolom'], '!=', '')
                ->distinct()->pluck($cfg['kolom']);

            foreach ($nilai as $n) {
                $n = trim((string) $n);
                $kunci = mb_strtolower($n);
                if ($n === '' || isset($ada[$kunci])) {
                    continue;
                }
                $model::create(['nama' => $n]);
                $ada[$kunci] = true;
                $tambah++;
            }
        }

        return back()->with('success', $tambah > 0
            ? "{$tambah} kategori/jenis dari data obat ditambahkan ke master."
            : 'Semua kategori dan jenis pada data obat sudah terdaftar.');
    }
}
