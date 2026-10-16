<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\AllergyScreeningController;
use App\Http\Controllers\TemperatureMonitoringController;
// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Stok & Mutasi (Otomatis mendaftarkan stok.index, stok.create, stok.store, dll)
Route::resource('stok', MedicineController::class);

// Rute tambahan untuk mengurangi stok (Barang Keluar)
// Rute tambahan untuk mengurangi stok (Barang Keluar)
Route::post('/stok/reduce', [MedicineController::class, 'reduceStock'])->name('stok.reduce');

// Skrining Alergi
Route::get('/skrining', [AllergyScreeningController::class, 'index'])->name('skrining.index');
Route::post('/skrining/pelanggan', [AllergyScreeningController::class, 'storePelanggan'])->name('skrining.pelanggan.store');

Route::get('/monitoring', [TemperatureMonitoringController::class, 'index'])->name('monitoring.index');
Route::post('/monitoring/simulate', [TemperatureMonitoringController::class, 'simulateSensor'])->name('monitoring.simulate');