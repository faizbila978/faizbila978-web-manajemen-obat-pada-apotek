@extends('layouts.app')

@section('content')
<div class="p-8 bg-gray-50 min-h-screen">
    <div class="bg-white p-8 rounded-2xl shadow-sm border border-gray-100 max-w-3xl">
        <h1 class="text-2xl font-bold text-[#0d2e26] mb-4">Selamat Datang di Sistem Apotek Aishwa</h1>
        <p class="text-gray-600 leading-relaxed mb-6">
            Panel utama untuk mengelola inventaris obat, memantau tanggal kadaluarsa (expired date), 
            melakukan skrining alergi pasien, serta memantau suhu ruangan gudang secara real-time.
        </p>
        <div class="flex gap-4">
            <a href="{{ route('stok.index') }}" class="inline-block bg-[#0d2e26] text-white px-6 py-3 rounded-xl font-medium hover:bg-[#133d33] transition">
                Kelola Stok & Mutasi Obat &rarr;
            </a>
        </div>
    </div>
</div>
@endsection