@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">
    <!-- Header & Tombol Aksi -->
    <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
        <h1 class="text-2xl font-bold text-[#0d2e26]">Manajemen Stok & Mutasi Obat</h1>
        
        <!-- Form Pencarian & Tombol Aksi -->
        <div class="flex items-center gap-3 w-full md:w-auto">
            <form action="{{ route('stok.index') }}" method="GET" class="flex-1 md:w-64">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama obat..." 
                       class="w-full px-4 py-2 bg-white border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#0d2e26]">
            </form>

            <!-- Tombol Barang Keluar -->
            <button onclick="openModalKeluar()" class="bg-amber-600 text-white px-4 py-2 rounded-xl shadow hover:bg-amber-700 transition font-medium text-sm whitespace-nowrap">
                - Barang Keluar
            </button>

            <!-- Tombol Tambah Obat Baru -->
            <button onclick="openModalTambah()" class="bg-[#0d2e26] text-white px-4 py-2 rounded-xl shadow hover:bg-[#133d33] transition font-medium text-sm whitespace-nowrap">
                + Tambah Obat Baru
            </button>
        </div>
    </div>

    <!-- Alert Notifikasi Sukses -->
    @if(session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm">
            {{ session('success') }}
        </div>
    @endif
    <!-- Alert Notifikasi Peringatan Expired / Error -->
    @if(session('error'))
        <div class="mb-4 p-4 bg-amber-50 border border-amber-300 text-amber-900 rounded-xl text-sm font-medium">
            ⚠️ {{ session('error') }}
        </div>
    @endif

    <!-- Tabel Daftar Obat & Batch -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700 text-xs uppercase tracking-wider">
                    <th class="p-4 font-semibold">Nama Obat</th>
                    <th class="p-4 font-semibold">Total Stok</th>
                    <th class="p-4 font-semibold">Detail Batch & Expired</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-base">
                @forelse($medicines as $med)
                <tr class="hover:bg-gray-50">
                    <!-- Kolom 1: Nama Obat & Satuan -->
                    <td class="p-4 font-bold text-gray-800">
                        {{ $med->name }} <br>
                        <span class="text-xs text-gray-500 font-normal">Bentuk: {{ $med->unit }}</span>
                    </td>
                    
                    <!-- Kolom 2: Total Stok -->
                    <td class="p-4 font-bold text-[#0d2e26]">
                        {{ $med->total_stock }} {{ $med->unit }}
                    </td>
                    
                    <!-- Kolom 3: Detail Batch & Expired -->
                    <td class="p-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach($med->batches->where('current_stock', '>', 0) as $batch)
                                @php
                                    $isExpired = \Carbon\Carbon::parse($batch->expired_date)->isPast();
                                    $isNearExpired = \Carbon\Carbon::parse($batch->expired_date)->lte(now()->addDays(60));
                                @endphp
                                <div class="inline-flex items-center gap-2 text-xs px-3 py-1.5 rounded-lg border shadow-sm
                                    {{ $isExpired ? 'border-red-400 bg-red-50 text-red-700' : ($isNearExpired ? 'border-yellow-400 bg-yellow-50 text-yellow-700' : 'border-gray-200 bg-gray-50 text-gray-700') }}">
                                    <span>Batch: <b class="font-semibold">{{ $batch->batch_number }}</b></span>
                                    <span class="text-gray-400">|</span>
                                    <span>Sisa: <b>{{ $batch->current_stock }}</b></span>
                                    <span class="text-gray-400">|</span>
                                    <span>Exp: <b>{{ $batch->expired_date }}</b></span>
                                    
                                    @if($isExpired) 
                                        <span class="font-bold text-red-600">[KADALUARSA]</span>
                                    @elseif($isNearExpired) 
                                        <span class="font-bold text-yellow-600">[Hampir Exp]</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="p-6 text-center text-gray-500">Belum ada data obat atau obat tidak ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Tambah Obat Baru -->
<div id="modalTambahObat" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl relative">
        <h3 class="text-lg font-bold text-[#0d2e26] mb-4">Tambah Obat Baru</h3>
        
        <form action="{{ route('stok.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Obat</label>
                    <input type="text" name="name" required placeholder="Contoh: Paracetamol" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Bentuk / Satuan</label>
                    <select name="unit" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none bg-white">
                        <option value="">-- Pilih Bentuk --</option>
                        <option value="Botol">Botol</option>
                        <option value="Tablet">Tablet</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Stok Awal</label>
                    <input type="number" name="initial_stock" min="1" required placeholder="Contoh: 50" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tanggal Kadaluarsa (Expired)</label>
                    <input type="date" name="expired_date" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModalTambah()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition">Batal</button>
                <button type="submit" class="px-4 py-2 bg-[#0d2e26] text-white rounded-xl hover:bg-[#133d33] transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Form Barang Keluar (Sederhana: Pilih Obat & Jumlah) -->
<div id="modalBarangKeluar" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl relative">
        <h3 class="text-lg font-bold text-amber-700 mb-2">Form Barang Keluar</h3>
        <p class="text-xs text-gray-500 mb-4">Sistem akan otomatis mengeluarkan stok menggunakan metode FIFO berdasarkan tanggal expired terdekat.</p>
        
        <form action="{{ route('stok.reduce') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <!-- Pilih Obat -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pilih Obat</label>
                    <select name="medicine_id" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-amber-600 outline-none bg-white">
                        <option value="">-- Pilih Obat --</option>
                        @foreach($medicines as $m)
                            <option value="{{ $m->id }}">{{ $m->name }} (Total Stok: {{ $m->total_stock }} {{ $m->unit }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Jumlah Keluar -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Keluar / Qty</label>
                    <input type="number" name="quantity" min="1" required placeholder="Contoh: 5" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-amber-600 outline-none">
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModalKeluar()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition">Batal</button>
                <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-xl hover:bg-amber-700 transition">Proses Pengurangan</button>
            </div>
        </form>
    </div>
</div>

<!-- Script Modal & Interaksi -->
<script>
    function openModalTambah() {
        document.getElementById('modalTambahObat').classList.remove('hidden');
        document.getElementById('modalTambahObat').classList.add('flex');
    }
    function closeModalTambah() {
        document.getElementById('modalTambahObat').classList.remove('flex');
        document.getElementById('modalTambahObat').classList.add('hidden');
    }

    function openModalKeluar() {
        document.getElementById('modalBarangKeluar').classList.remove('hidden');
        document.getElementById('modalBarangKeluar').classList.add('flex');
    }
    function closeModalKeluar() {
        document.getElementById('modalBarangKeluar').classList.remove('flex');
        document.getElementById('modalBarangKeluar').classList.add('hidden');
    }
</script>
@endsection