<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MedicineController;
use App\Http\Controllers\AllergyScreeningController;
use App\Http\Controllers\TemperatureMonitoringController;
use App\Http\Controllers\AuthController;
// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Stok & Mutasi (Otomatis mendaftarkan stok.index, stok.create, stok.store, dll)
Route::resource('stok', MedicineController::class);
Route::post('/stok/reduce', [MedicineController::class, 'reduceStock'])->name('stok.reduce');
Route::delete('/stok/{id_obat}', [MedicineController::class, 'destroy'])->name('stok.destroy');

// Skrining Alergi
Route::get('/skrining', [AllergyScreeningController::class, 'index'])->name('skrining.index');
Route::get('/skrining/history', [AllergyScreeningController::class, 'history'])->name('skrining.history');

Route::get('/monitoring', [TemperatureMonitoringController::class, 'index'])->name('monitoring.index');
Route::post('/monitoring/simulate', [TemperatureMonitoringController::class, 'simulateSensor'])->name('monitoring.simulate');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});