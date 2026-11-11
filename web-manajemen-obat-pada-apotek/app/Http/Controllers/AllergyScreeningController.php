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
            $obats = Obat::with('kandungans')
                ->where('nama_obat', 'LIKE', '%' . $keyword . '%')
                ->get();

            foreach ($obats as $obat) {

                // Periksa kecocokan kandungan dengan alergi pelanggan
                $alergiPemicu = null;

                foreach ($obat->kandungans as $kandungan) {
                    foreach ($pelanggan->alergis as $alergi) {

                        $namaKandungan = mb_strtolower(
                            trim($kandungan->nama_kandungan)
                        );

                        $namaAlergi = mb_strtolower(
                            trim($alergi->nama_alergi)
                        );

                        if (
                            $namaKandungan !== '' &&
                            $namaAlergi !== '' &&
                            (
                                $namaKandungan === $namaAlergi ||
                                str_contains($namaKandungan, $namaAlergi) ||
                                str_contains($namaAlergi, $namaKandungan)
                            )
                        ) {
                            $alergiPemicu = $alergi;
                            break 2;
                        }
                    }
                }

                $isSafe = $alergiPemicu === null;

                $hasil = $isSafe
                    ? 'AMAN'
                    : 'TIDAK AMAN - Berpotensi memicu alergi';

                // Simpan setiap pemeriksaan sebagai riwayat baru
                ScreeningAlergi::create([
                    'id_pelanggan' => $pelanggan->id_pelanggan,
                    'id_obat' => $obat->id_obat,
                    'id_alergi' => $alergiPemicu?->id_alergi,
                    'tgl_screening' => now(),
                    'hasil' => $hasil
                ]);

                // Cari obat alternatif jika obat tidak aman
                $alternatif = collect();

                if (!$isSafe) {
                    $alternatif = Obat::with('kandungans')
                        ->where('jenis', $obat->jenis)
                        ->where('id_obat', '!=', $obat->id_obat)
                        ->where('stok', '>', 0)
                        ->get()
                        ->filter(function ($obatAlternatif) use ($pelanggan) {

                            foreach ($obatAlternatif->kandungans as $kandungan) {
                                foreach ($pelanggan->alergis as $alergi) {

                                    $k = mb_strtolower(trim($kandungan->nama_kandungan));
                                    $a = mb_strtolower(trim($alergi->nama_alergi));

                                    if (
                                        $k !== '' && $a !== '' &&
                                        (
                                            $k === $a ||
                                            str_contains($k, $a) ||
                                            str_contains($a, $k)
                                        )
                                    ) {
                                        return false;
                                    }
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