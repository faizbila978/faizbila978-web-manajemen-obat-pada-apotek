<?php

namespace App\Http\Controllers;

use App\Models\Medicine;
use App\Models\MedicineBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MedicineController extends Controller {
    // Menampilkan daftar stok obat dan status batch/expired-nya
    public function index() {
        $medicines = Medicine::with(['batches' => function($query) {
            $query->orderBy('expired_date', 'asc');
        }])->get();

        return view('stok.index', compact('medicines'));
    }

    // Menyimpan obat baru
    public function store(Request $request) {
        $request->validate([
            'name' => 'required|string|max:255',
            'unit' => 'required|in:Botol,Tablet',
            'initial_stock' => 'required|integer|min:1',
            'expired_date' => 'required|date',
        ]);

        $batchNumber = 'BATCH-' . strtoupper(Str::random(6));

        DB::transaction(function () use ($request, $batchNumber) {
            $medicine = Medicine::create([
                'name' => $request->name,
                'active_ingredient' => $request->name, 
                'category' => 'Umum',
                'unit' => $request->unit,
                'selling_price' => 0,
                'total_stock' => $request->initial_stock,
            ]);

            MedicineBatch::create([
                'medicine_id' => $medicine->id_obat,
                'batch_number' => $batchNumber,
                'initial_stock' => $request->initial_stock,
                'current_stock' => $request->initial_stock,
                'expired_date' => $request->expired_date,
                'type' => 'in',
                'description' => 'Stok Awal',
            ]);
        });

        return redirect()->route('stok.index')->with('success', 'Obat baru berhasil ditambahkan!');
    }

    // Mengurangi stok (Barang Keluar / Penjualan / Rusak dengan metode FIFO)
    public function reduceStock(Request $request) {
        $request->validate([
            'medicine_id' => 'required|exists:obats,id_obat',
            'quantity' => 'required|integer|min:1',
        ]);

        $qtyToReduce = $request->quantity;
        $warningMessage = null;

        DB::transaction(function () use ($request, $qtyToReduce, &$warningMessage) {
            $medicine = Medicine::find($request->medicine_id);

            if ($medicine->total_stock < $qtyToReduce) {
                throw new \Exception('Stok tidak mencukupi!');
            }

            // Ambil batch berdasarkan sistem FIFO (Expired paling awal didahulukan)
            $batches = MedicineBatch::where('medicine_id', $request->medicine_id)
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

            // Kurangi total stok utama
            $medicine->total_stock -= $request->quantity;
            $medicine->save();
        });

        $successMsg = 'Barang keluar berhasil diproses!';
        if ($warningMessage) {
            return redirect()->route('stok.index')->with('success', $successMsg)->with('error', $warningMessage);
        }

        return redirect()->route('stok.index')->with('success', $successMsg);
    }
}