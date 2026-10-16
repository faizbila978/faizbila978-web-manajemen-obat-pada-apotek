<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\AlergiPelanggan;
use App\Models\Obat;
use App\Models\ScreeningAlergi;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AllergyScreeningController extends Controller {
    // Menampilkan halaman skrining beserta hasil instan berdasarkan input teks
    public function index(Request $request) {
        $namaPelanggan = $request->input('nama_pelanggan');
        $namaAlergiInput = $request->input('nama_alergi');
        
        $screeningResults = [];
        $allergyList = [];

        if ($request->has('nama_pelanggan') && !empty($namaPelanggan)) {
            // Simpan otomatis pelanggan baru atau gunakan yang sudah ada
            $pelanggan = Pelanggan::firstOrCreate(['nama_pelanggan' => $namaPelanggan]);

            // Bersihkan dan simpan riwayat alergi
            if (!empty($namaAlergiInput)) {
                $alergis = array_map('trim', explode(',', $namaAlergiInput));
                foreach ($alergis as $alg) {
                    if (!empty($alg)) {
                        AlergiPelanggan::firstOrCreate([
                            'id_pelanggan' => $pelanggan->id_pelanggan,
                            'nama_alergi' => $alg
                        ]);
                    }
                }
            }

            $allergyList = $pelanggan->alergis()->pluck('nama_alergi')->toArray();
            $allObats = Obat::with('kandungans')->where('stok', '>', 0)->get();

            foreach ($allObats as $obat) {
                $isSafe = true;
                $matchedAlergiId = null;

                foreach ($obat->kandungans as $kandungan) {
                    foreach ($pelanggan->alergis as $alergi) {
                        if (stripos($kandungan->nama_kandungan, $alergi->nama_alergi) !== false) {
                            $isSafe = false;
                            $matchedAlergiId = $alergi->id_alergi;
                            break 2;
                        }
                    }
                }

                $hasilText = $isSafe ? 'Aman Digunakan' : 'Tidak Aman (Memicu Alergi)';

                // Catat ke tabel screening_alergis sesuai ERD
                ScreeningAlergi::updateOrCreate(
                    [
                        'id_pelanggan' => $pelanggan->id_pelanggan,
                        'id_obat' => $obat->id_obat,
                    ],
                    [
                        'id_alergi' => $matchedAlergiId,
                        'tgl_screening' => Carbon::now(),
                        'hasil' => $hasilText,
                    ]
                );

                $screeningResults[] = [
                    'obat' => $obat,
                    'is_safe' => $isSafe,
                    'hasil' => $hasilText
                ];
            }
        }

        return view('skrining.index', compact('namaPelanggan', 'namaAlergiInput', 'allergyList', 'screeningResults'));
    }
}