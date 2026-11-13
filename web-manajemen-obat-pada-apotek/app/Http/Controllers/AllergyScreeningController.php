<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Pelanggan;
use App\Models\AlergiPelanggan;
use App\Models\Obat;
use App\Models\ScreeningAlergi;
use Illuminate\Http\Request;

class AllergyScreeningController extends Controller
{
    public function index(Request $request)
    {
        $namaPelanggan = trim($request->nama_pelanggan ?? '');
        $namaAlergiInput = trim($request->nama_alergi ?? '');
        $keyword = trim($request->keyword ?? '');

        $allergyList = [];
        $screeningResults = [];
        $searched = $request->has('nama_pelanggan') &&
                    $request->has('keyword');

        if ($request->filled('nama_pelanggan') && $request->filled('keyword')) {

            // Cari atau buat pelanggan
            $pelanggan = Pelanggan::firstOrCreate([
                'nama_pelanggan' => $namaPelanggan
            ]);

            // Simpan alergi baru tanpa menghapus alergi lama
            if ($namaAlergiInput !== '') {
                $daftarAlergi = array_filter(
                    array_map('trim', explode(',', $namaAlergiInput))
                );

                foreach ($daftarAlergi as $alergi) {
                    AlergiPelanggan::firstOrCreate([
                        'id_pelanggan' => $pelanggan->id_pelanggan,
                        'nama_alergi' => $alergi
                    ]);
                }
            }

            $pelanggan->load('alergis');

            $allergyList = $pelanggan->alergis
                ->pluck('nama_alergi')
                ->toArray();

            // Cari obat berdasarkan nama yang dimasukkan
            $obats = Obat::where('nama_obat', 'LIKE', '%' . $keyword . '%')
                ->get();

            foreach ($obats as $obat) {

                // Periksa kecocokan langsung dengan kolom 'alergi' pada tabel obat
                $alergiPemicu = null;
                $alergiObatStr = mb_strtolower(trim($obat->alergi ?? ''));

                if ($alergiObatStr !== '' && $alergiObatStr !== '-') {
                    foreach ($pelanggan->alergis as $alergi) {
                        $namaAlergiPasien = mb_strtolower(trim($alergi->nama_alergi));

                        if (
                            $namaAlergiPasien !== '' &&
                            (
                                $alergiObatStr === $namaAlergiPasien ||
                                str_contains($alergiObatStr, $namaAlergiPasien) ||
                                str_contains($namaAlergiPasien, $alergiObatStr)
                            )
                        ) {
                            $alergiPemicu = $alergi;
                            break;
                        }
                    }
                }

                $isSafe = $alergiPemicu === null;

                $hasil = $isSafe
                    ? 'AMAN'
                    : 'TIDAK AMAN - Berpotensi memicu alergi';

                // Simpan setiap pemeriksaan sebagai riwayat baru dengan waktu saat ini
                ScreeningAlergi::create([
                    'id_pelanggan' => $pelanggan->id_pelanggan,
                    'id_obat' => $obat->id_obat,
                    'id_alergi' => $alergiPemicu?->id_alergi,
                    'tgl_screening' => now(),
                    'hasil' => $hasil
                ]);

                // Cari obat alternatif berdasarkan KATEGORI yang sama
                $alternatif = collect();

                if (!$isSafe) {
                    $alternatif = Obat::where('kategori', $obat->kategori)
                        ->where('id_obat', '!=', $obat->id_obat)
                        ->where('stok', '>', 0)
                        ->get()
                        ->filter(function ($obatAlternatif) use ($pelanggan) {
                            $altAlergiStr = mb_strtolower(trim($obatAlternatif->alergi ?? ''));

                            if ($altAlergiStr === '' || $altAlergiStr === '-') {
                                return true; 
                            }

                            foreach ($pelanggan->alergis as $alergi) {
                                $a = mb_strtolower(trim($alergi->nama_alergi));

                                if (
                                    $a !== '' &&
                                    (
                                        $altAlergiStr === $a ||
                                        str_contains($altAlergiStr, $a) ||
                                        str_contains($a, $altAlergiStr)
                                    )
                                ) {
                                    return false; 
                                }
                            }

                            return true;
                        })
                        ->values();
                }

                $screeningResults[] = [
                    'obat' => $obat,
                    'is_safe' => $isSafe,
                    'alergi_pemicu' => $alergiPemicu,
                    'alternatif' => $alternatif
                ];
            }
        }

        return view('skrining.index', compact(
            'namaPelanggan',
            'namaAlergiInput',
            'keyword',
            'allergyList',
            'screeningResults',
            'searched'
        ));
    }

    // Menampilkan dan mencari riwayat pelanggan
    public function history(Request $request)
    {
        $search = trim($request->search ?? '');

        $pelanggans = Pelanggan::with([
            'alergis',
            'screenings.obat',
            'screenings.alergi'
        ])
        ->when($search, function ($query) use ($search) {
            $query->where(
                'nama_pelanggan',
                'LIKE',
                '%' . $search . '%'
            );
        })
        ->latest()
        ->paginate(10)
        ->withQueryString();

        return view('skrining.history', compact(
            'pelanggans',
            'search'
        ));
    }
}