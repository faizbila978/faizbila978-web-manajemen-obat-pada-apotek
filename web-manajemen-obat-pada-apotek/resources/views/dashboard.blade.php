@extends('layouts.app')

@section('content')
<div class="p-8 bg-[#f4f7f6] min-h-screen">
    <!-- Bento Style Large Card Wrapper (Dibuat Full Memenuhi Halaman) -->
    <div class="bg-white p-8 md:p-10 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-gray-100 w-full transition-all duration-300">
        
        <!-- Header Section -->
        <div class="flex items-center gap-3 mb-4">
            <div class="w-3 h-8 bg-[#0d2e26] rounded-full"></div>
            <h1 class="text-2xl md:text-3xl font-bold text-[#0d2e26] tracking-tight">Selamat Datang di Sistem Apotek Farmasi 57</h1>
        </div>

        <!-- Description -->
        <p class="text-gray-500 leading-relaxed mb-8 text-base md:text-lg max-w-4xl">
            Panel utama untuk mengelola inventaris obat, memantau tanggal kadaluarsa (<span class="font-medium text-gray-700">expired date</span>), 
            melakukan skrining alergi pasien, serta memantau suhu ruangan gudang secara real-time.
        </p>

        <!-- Divider Tipis -->
        <hr class="border-gray-100 mb-8">

        <!-- Grid Menu Cepat (Lebih Lebar dan Proporsional) -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            
            <!-- Menu 1: Stok & Mutasi -->
            <div class="p-6 rounded-2xl bg-emerald-50/50 border border-emerald-100/60 hover:bg-emerald-50 transition">
                <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl font-bold mb-4">
                    📦
                </div>
                <h3 class="text-lg font-bold text-[#0d2e26] mb-2">Stok & Mutasi Obat</h3>
                <p class="text-gray-500 text-sm mb-4 leading-relaxed">
                    Kelola data obat masuk, keluar (FIFO), nomor batch, kategori, kandungan, dan tanggal kadaluarsa.
                </p>
                <a href="{{ route('stok.index') }}" class="text-[#0d2e26] font-semibold text-sm hover:underline inline-flex items-center gap-1">
                    Buka Stok & Mutasi Obat &rarr;
                </a>
            </div>

            <!-- Menu 2: Skrining Alergi -->
            <div class="p-6 rounded-2xl bg-blue-50/50 border border-blue-100/60 hover:bg-blue-50 transition">
                <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-700 flex items-center justify-center text-xl font-bold mb-4">
                    🔍
                </div>
                <h3 class="text-lg font-bold text-[#0d2e26] mb-2">Skrining Alergi</h3>
                <p class="text-gray-500 text-sm mb-4 leading-relaxed">
                    Lakukan pengecekan dan pencocokan kandungan obat dengan riwayat alergi pasien untuk keamanan resep.
                </p>
                <a href="{{ route('skrining.index') }}" class="text-[#0d2e26] font-semibold text-sm hover:underline inline-flex items-center gap-1">
                    Buka Skrining Alergi &rarr;
                </a>
            </div>

            <!-- Menu 3: Monitoring Suhu -->
            <div class="p-6 rounded-2xl bg-amber-50/50 border border-amber-100/60 hover:bg-amber-50 transition">
                <div class="w-12 h-12 rounded-2xl bg-amber-100 text-amber-700 flex items-center justify-center text-xl font-bold mb-4">
                    🌡️
                </div>
                <h3 class="text-lg font-bold text-[#0d2e26] mb-2">Monitoring</h3>
                <p class="text-gray-500 text-sm mb-4 leading-relaxed">
                    Pantau kestabilan suhu ruangan penyimpanan obat secara real-time guna menjaga kualitas obat.
                </p>
                <a href="{{ route('monitoring.index') }}" class="text-[#0d2e26] font-semibold text-sm hover:underline inline-flex items-center gap-1">
                    Buka Monitoring &rarr;
                </a>
            </div>

        </div>

    </div>
</div>
@endsection