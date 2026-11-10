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
                + Barang Masuk
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
    <!-- Tabel Daftar Obat & Batch -->
    <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-gray-100">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-700 text-xs uppercase tracking-wider">
                    <th class="p-4 font-bold text-gray-800">No. Batch</th>
                    <th class="p-4 font-bold text-gray-800">Nama Obat</th>
                    <th class="p-4 font-bold text-gray-800">Jenis</th>
                    <th class="p-4 font-bold text-gray-800">Kategori</th>
                    <th class="p-4 font-bold text-gray-800">Kandungan Obat</th>
                    <th class="p-4 font-bold text-gray-800">Alergi</th>
                    <th class="p-4 font-bold text-gray-800">Total Stok</th>
                    <th class="p-4 font-bold text-gray-800">Expired</th>
                    <th class="p-4 font-bold text-gray-800 text-center">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 text-base">
                @forelse($medicines as $med)
                <tr class="hover:bg-gray-50">
                      <!-- Kolom 1: No Batch-->
                   <td class="p-4 text-gray-800">
                    @foreach($med->batches as $batch)
                    <div class="mb-1">
                    <span class="inline-block bg-amber-50 text-amber-800 px-2 py-1 rounded text-xs font-semibold">
                    {{ $batch->batch_number }}
                    </span>
                    </div>
                    @endforeach
                    </td>

                    <!-- Kolom 2: Nama Obat-->
                    <td class="p-4 font-bold text-gray-800">
                        {{ $med->nama_obat }} <br>
                    </td>

                    <!-- Kolom 3: Jenis -->
                    <td class="p-4 font-bold text-gray-800">
                        {{ $med->jenis }} <br>
                    </td>

                    <!-- Kolom 4: Kategori -->
                    <td class="p-4 font-bold text-gray-800">
                        {{ $med->kategori }} <br>
                    </td>
                    
                    <!-- Kolom 5: Kandungan Obat -->
                    <td class="p-4 font-bold text-gray-800">
                    @foreach($med->kandungan as $item)
                    <span>{{ $item->nama_kandungan }}</span><br>
                    @endforeach
                    </td>

                    <!-- Kolom 6: Alergi -->
                    <td class="p-4 font-bold text-gray-800">
                        {{ $med->alergi }} <br>
                    </td>

                    <!-- Kolom 6: Total Stok -->
                    <td class="p-4 font-bold text-gray-800">
                        {{ $med->stok }} <br>
                    </td>
                    
                    <!-- Kolom 7: Expired -->
                   <td class="p-4 text-gray-800">
                    @foreach($med->batches as $batch)
                    <div class="mb-1 text-xs">
                    <span>Exp: {{ $batch->expired_date }}</span>
            
                    @php
                    $isExpired = \Carbon\Carbon::parse($batch->expired_date)->isPast();
                    $isNearExpired = \Carbon\Carbon::parse($batch->expired_date)->lte(now()->addDays(60));
                    @endphp

                    @if($isExpired)
                    <span class="text-red-600 font-bold ml-1">[KADALUARSA]</span>
                    @elseif($isNearExpired)
                    <span class="text-amber-600 font-bold ml-1">[Hampir Exp]</span>
                    @endif
                    </div>
                    @endforeach
                    </td>

                   <!-- Kolom Aksi -->
                    <td class="p-4 text-center flex items-center justify-center gap-2">
                        <!-- Tombol Edit -->
                        <button onclick="openModalEdit('{{ $med->id_obat }}', '{{ $med->nama_obat }}', '{{ $med->jenis }}', '{{ $med->kategori }}', '{{ addslashes($med->kandungan->pluck('nama_kandungan')->implode(', ')) }}', '{{ $med->alergi }}', '{{ $med->stok }}')" 
                            class="px-3 py-1.5 bg-blue-600 text-white rounded-xl text-xs font-medium hover:bg-blue-700 transition shadow">
                            Edit
                        </button>

                        <!-- Tombol Hapus Total -->
                        <form action="{{ route('stok.destroy', $med->id_obat) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus obat ini?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1.5 bg-red-600 text-white rounded-xl text-xs font-medium hover:bg-red-700 transition shadow">
                                Hapus Total
                            </button>
                        </form>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="p-6 text-center text-gray-500">Belum ada data obat atau obat tidak ditemukan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Form Tambah Obat Baru -->
<!-- Modal Form Tambah Obat Baru -->
<div id="modalTambahObat" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl relative">
        <h3 class="text-lg font-bold text-[#0d2e26] mb-4">Tambah Obat Baru</h3>
        
        <form action="{{ route('stok.store') }}" method="POST">
            @csrf
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Obat</label>
                    <input type="text" name="nama_obat" required placeholder="" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis</label>
                    <input type="text" name="jenis" required placeholder="Contoh: Tablet / Botol" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <input type="text" name="kategori" required placeholder="Contoh: Demam / Ruam" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kandungan Obat</label>
                    <input type="text" name="kandungan_obat" required placeholder="" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alergi</label>
                    <input type="text" name="alergi" required placeholder="" class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                    <span class="text-[10px] text-gray-400 mt-0.5 block">Digunakan untuk pencocokan skrining alergi pasien.</span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Stok</label>
                    <input type="number" id="edit_stok" name="stok" min="0" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
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

<!-- Modal Form Barang Keluar -->
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
                    <select name="id_obat" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-amber-600 outline-none bg-white">
                        <option value="">-- Pilih Obat --</option>
                        @foreach($medicines as $m)
                            <option value="{{ $m->id_obat }}">{{ $m->nama_obat }} (Total Stok: {{ $m->stok }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Status / Keterangan Barang Keluar (Baru) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Keterangan Keluar</label>
                    <select name="keluar_type" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-amber-600 outline-none bg-white">
                        <option value="Terjual">Terjual / Keluar Normal</option>
                        <option value="Expired">Kadaluarsa (Expired)</option>
                        <option value="Rusak">Terjual</option>
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

<!-- Modal Form Edit Obat -->
<!-- Modal Form Edit Obat -->
<div id="modalEditObat" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl relative">
        <h3 class="text-lg font-bold text-[#0d2e26] mb-4">Edit Data & Stok Obat</h3>
        
        <form id="formEditObat" method="POST">
            @csrf
            @method('PUT')
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nama Obat</label>
                    <input type="text" id="edit_nama_obat" name="nama_obat" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jenis</label>
                    <input type="text" id="edit_jenis" name="jenis" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kategori</label>
                    <input type="text" id="edit_kategori" name="kategori" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Kandungan Obat</label>
                    <input type="text" id="edit_kandungan" name="kandungan" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Alergi</label>
                    <input type="text" id="edit_alergi" name="alergi" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
                <!-- PASTIKAN BAGIAN INI ADA DI FILE BLADE ANDA -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Jumlah Stok</label>
                    <input type="number" id="edit_stok" name="stok" min="0" required class="w-full px-4 py-2 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="closeModalEdit()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-xl hover:bg-gray-300 transition">Batal</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition">Simpan Perubahan</button>
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
    // ---> EDIT DI SINI <---
    function openModalEdit(id, nama, jenis, kategori, kandungan, alergi, stok) {
    document.getElementById('formEditObat').action = '/stok/' + id;
    
    document.getElementById('edit_nama_obat').value = nama;
    document.getElementById('edit_jenis').value = jenis;
    document.getElementById('edit_kategori').value = kategori;
    document.getElementById('edit_kandungan').value = kandungan;
    document.getElementById('edit_alergi').value = alergi;
    document.getElementById('edit_stok').value = stok; // Masukkan nilai stok

    document.getElementById('modalEditObat').classList.remove('hidden');
    document.getElementById('modalEditObat').classList.add('flex');
    }

    function closeModalEdit() {
        document.getElementById('modalEditObat').classList.remove('flex');
        document.getElementById('modalEditObat').classList.add('hidden');
    }
    </script>
@endsection