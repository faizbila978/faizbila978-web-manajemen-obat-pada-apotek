<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MedicineController extends Controller {
    // Menampilkan daftar stok obat dan status batch/expired-nya
   public function index(Request $request) {
    $search = $request->input('search');

    $medicines = Medicine::with(['batches' => function($query) {
        $query->orderBy('expired_date', 'asc');
    }])
    ->when($search, function ($query, $search) {
        // Filter berdasarkan nama obat (menggunakan LIKE agar pencarian lebih fleksibel)
        return $query->where('nama_obat', 'like', '%' . $search . '%');
    })
    ->get();

    return view('stok.index', compact('medicines'));
    }

// Menyimpan obat baru beserta kandungan obatnya
    public function store(Request $request) {
        $request->validate([
            'nama_obat'      => 'required|string|max:255',
            'jenis'          => 'required|string|max:255',
            'kategori'       => 'required|string|max:255',
            'kandungan_obat' => 'required|string|max:255', // Diubah dari 'kandungan' menjadi 'kandungan_obat'
            'alergi'         => 'required|string|max:255',
            'stok'           => 'required|integer|min:1',     // Diubah dari 'initial_stock' menjadi 'stok' sesuai form
            'expired_date'   => 'required|date',
        ]);

        $batchNumber = 'BATCH-' . strtoupper(Str::random(6));

        DB::transaction(function () use ($request, $batchNumber) {
            $medicine = Medicine::create([
                'nama_obat' => $request->nama_obat,
                'jenis'     => $request->jenis,
                'kategori'  => $request->kategori,
                'alergi'    => $request->alergi,
                'harga'     => 0,
                'stok'      => $request->stok, // Mengambil dari input 'stok'
            ]);

            // Gunakan $medicine->id (atau sesuaikan jika primary key tabel obat Anda memang id_obat)
            $medicineId = $medicine->id ?? $medicine->id_obat;

            // Simpan kandungan obat (Diperbaiki mengambil dari $request->kandungan_obat)
            \App\Models\KandunganObat::create([
                'id_obat'        => $medicineId,
                'nama_kandungan' => $request->kandungan_obat, 
            ]);

            MedicineBatch::create([
                'id_obat'       => $medicineId,
                'batch_number'  => $batchNumber,
                'initial_stock' => $request->stok, // Mengambil dari input 'stok'
                'current_stock' => $request->stok, // Mengambil dari input 'stok'
                'expired_date'  => $request->expired_date,
                'type'          => 'in',
                'description'   => 'Stok Awal',
            ]);
        });

        return redirect()->route('stok.index')->with('success', 'Obat baru beserta kandungan berhasil ditambahkan!');
    }

    // Mengurangi stok (Barang Keluar / Penjualan / Rusak dengan metode FIFO)
    // Mengurangi stok (Barang Keluar / Penjualan / Rusak dengan metode FIFO)
    public function reduceStock(Request $request) {
        $request->validate([
            'id_obat' => 'required|exists:obats,id_obat', // Sesuaikan name dari form modal (id_obat)
            'quantity' => 'required|integer|min:1',
        ]);

        $qtyToReduce = $request->quantity;
        $warningMessage = null;

        DB::transaction(function () use ($request, $qtyToReduce, &$warningMessage) {
            $medicine = Medicine::find($request->id_obat);

            // Cek ketersediaan stok menggunakan kolom 'stok'
            if ($medicine->stok < $qtyToReduce) {
                throw new \Exception('Stok tidak mencukupi!');
            }

            // Ambil batch berdasarkan sistem FIFO menggunakan foreign key 'id_obat'
            $batches = MedicineBatch::where('id_obat', $request->id_obat)
                                    ->where('current_stock', '>', 0)
                                    ->orderBy('expired_date', 'asc')
                                    ->get();

            foreach ($batches as $batch) {
                if ($qtyToReduce <= 0) break;

                // Cek apakah batch terdepan ini sudah expired atau hampir expired (< 60 hari)
                $isExpired = \Carbon\Carbon::parse($batch->expired_date)->isPast();
                $isNearExpired = \Carbon\Carbon::parse($batch->expired_date)->lte(now()->addDays(60));

                if ($isExpired) {
                    $warningMessage = "Perhatian: Obat yang dikeluarkan mencakup batch {$batch->batch_number} yang TELAH KADALUARSA!";
                } elseif ($isNearExpired && !$warningMessage) {
                    $warningMessage = "Perhatian: Obat yang dikeluarkan mencakup batch {$batch->batch_number} yang hampir kadaluarsa (Exp: {$batch->expired_date})!";
                }

                if ($batch->current_stock >= $qtyToReduce) {
                    $batch->current_stock -= $qtyToReduce;
                    $batch->save();
                    $qtyToReduce = 0;
                } else {
                    $qtyToReduce -= $batch->current_stock;
                    $batch->current_stock = 0;
                    $batch->save();
                }
            }

            // Kurangi total stok utama pada kolom 'stok'
            $medicine->stok -= $request->quantity;
            $medicine->save();
        });

        $successMsg = 'Barang keluar berhasil diproses!';
        if ($warningMessage) {
            return redirect()->route('stok.index')->with('success', $successMsg)->with('error', $warningMessage);
        }

        return redirect()->route('stok.index')->with('success', $successMsg);
    }

        // Memperbarui data obat
    public function update(Request $request, $id_obat)
    {
        $request->validate([
            'nama_obat' => 'required|string|max:255',
            'jenis' => 'required|string|max:255',
            'kategori' => 'required|string|max:255',
            'kandungan' => 'required|string|max:255',
            'alergi' => 'required|string|max:255',
            'stok' => 'required|integer|min:0'
        ]);

        DB::transaction(function () use ($request, $id_obat) {
            $medicine = Medicine::findOrFail($id_obat);
            
            // Update data utama obat
            $medicine->update([
                'nama_obat' => $request->nama_obat,
                'jenis' => $request->jenis,
                'kategori' => $request->kategori,
                'alergi' => $request->alergi,
                'stok' => $request->stok,
            ]);

            // Update kandungan obat (jika ada relasi)
            $medicine->kandungan()->update([
                'nama_kandungan' => $request->kandungan,
            ]);
            // Opsional: Jika ingin mengupdate stok pada batch terdepan/pertama
        $batch = $medicine->batches()->orderBy('expired_date', 'asc')->first();
        if ($batch) {
            $batch->update([
                'initial_stock' => $request->stok,
                'current_stock' => $request->stok,
            ]);
        }
    });

    return redirect()->route('stok.index')->with('success', 'Data obat dan stok berhasil diperbarui!');
}

    // Menghapus data obat secara total beserta batch dan kandungannya
    public function destroy($id_obat)
    {
        DB::transaction(function () use ($id_obat) {
            $medicine = Medicine::findOrFail($id_obat);
            
            // Hapus relasi terkait terlebih dahulu (jika ondelete cascade belum aktif di db)
            $medicine->batches()->delete();
            $medicine->kandungan()->delete();
            
            // Hapus data utama obat
            $medicine->delete();
        });

        return redirect()->route('stok.index')->with('success', 'Data obat berhasil dihapus secara total!');
    }
}