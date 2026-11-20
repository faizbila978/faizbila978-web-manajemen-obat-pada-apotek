<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\AllergyScreeningController;
use App\Http\Controllers\TemperatureMonitoringController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DataMasterController;
use App\Http\Controllers\PenjualanController;

// Auth (hanya untuk tamu / belum login)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Semua halaman yang wajib login
Route::middleware('auth')->group(function () {
    // Dashboard (Bisa diakses via / atau /dashboard)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Stok & Mutasi
    Route::resource('stok', MedicineController::class)
        ->except(['show', 'create', 'edit'])
        ->parameters(['stok' => 'id_obat']);
    
    Route::post('/stok/reduce', [MedicineController::class, 'reduceStock'])->name('stok.reduce');
    Route::post('/stok/restock', [MedicineController::class, 'restock'])->name('stok.restock');
    Route::put('/stok/{id}', [MedicineController::class, 'update'])->name('stok.update');

    // Skrining Alergi
    Route::redirect('/skrining', '/penjualan')->name('skrining.index');
    Route::redirect('/skrining/history', '/penjualan')->name('skrining.history');

    //penjualan
    Route::get('/penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');
    Route::get('/penjualan/cari-obat', [PenjualanController::class, 'cariObat'])->name('penjualan.cari');
    Route::get('/penjualan/cari-alergi', [PenjualanController::class, 'cariAlergi'])->name('penjualan.cari-alergi');
    Route::post('/penjualan/skrining', [PenjualanController::class, 'skrining'])->name('penjualan.skrining');
    Route::post('/penjualan/bayar', [PenjualanController::class, 'bayar'])->name('penjualan.bayar');

    // Monitoring Suhu
    Route::get('/monitoring', [TemperatureMonitoringController::class, 'index'])->name('monitoring.index');
    Route::post('/monitoring/simulate', [TemperatureMonitoringController::class, 'simulateSensor'])->name('monitoring.simulate');
    Route::get('/monitoring/export', [TemperatureMonitoringController::class, 'export'])->name('monitoring.export');
    Route::put('/monitoring/batas', [TemperatureMonitoringController::class, 'updateBatas'])->name('monitoring.batas.update');

    // Data Master
    Route::get('/data-master', [DataMasterController::class, 'index'])->name('master.index');
    Route::post('/data-master/sinkron', [DataMasterController::class, 'sinkron'])->name('master.sinkron');
    Route::post('/data-master/{tipe}', [DataMasterController::class, 'store'])
        ->where('tipe', 'kategori|jenis')->name('master.store');
    Route::put('/data-master/{tipe}/{id}', [DataMasterController::class, 'update'])
        ->where(['tipe' => 'kategori|jenis', 'id' => '[0-9]+'])->name('master.update');
    Route::delete('/data-master/{tipe}/{id}', [DataMasterController::class, 'destroy'])
        ->where(['tipe' => 'kategori|jenis', 'id' => '[0-9]+'])->name('master.destroy');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});