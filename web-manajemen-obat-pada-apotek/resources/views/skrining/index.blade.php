@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-[#0d2e26]">Skrining Alergi Pasien</h1>
        <p class="text-xs text-gray-500 mt-1">Cukup ketik nama dan riwayat alergi untuk langsung menyaring keamanan obat dari database.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Kolom Kiri: Form Input Instan (Nama & Alergi) -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-fit">
            <h3 class="text-base font-bold text-[#0d2e26] mb-4">Input Data Skrining</h3>
            
            <form action="{{ route('skrining.index') }}" method="GET">
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Nama Pelanggan / Pasien</label>
                        <input type="text" name="nama_pelanggan" value="{{ $namaPelanggan ?? '' }}" required placeholder="Contoh: Budi Santoso" class="w-full px-3 py-2 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-[#0d2e26]">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Riwayat Alergi (Pisahkan koma)</label>
                        <input type="text" name="nama_alergi" value="{{ $namaAlergiInput ?? '' }}" placeholder="Contoh: Paracetamol, Amoxicillin" class="w-full px-3 py-2 border rounded-xl text-sm outline-none focus:ring-2 focus:ring-[#0d2e26]">
                        <span class="text-[10px] text-gray-400 mt-1 block">Contoh: Paracetamol, Ibuprofen</span>
                    </div>

                    <button type="submit" class="w-full py-2.5 bg-[#0d2e26] text-white rounded-xl text-sm font-medium hover:bg-[#133d33] transition shadow">
                        🔍 Proses Skrining Alergi
                    </button>
                </div>
            </form>
        </div>

        <!-- Kolom Kanan: Hasil Pencocokan Keamanan Obat -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-bold text-[#0d2e26] mb-4">Hasil Pencocokan Keamanan Obat</h3>

            @if(isset($namaPelanggan) && !empty($namaPelanggan))
                <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                    <p class="text-sm font-semibold text-emerald-900">Pelanggan: {{ $namaPelanggan }}</p>
                    <p class="text-xs text-emerald-700 mt-1">
                        Alergi Terinput: 
                        <span class="font-bold text-red-600">
                            {{ !empty($allergyList) ? implode(', ', $allergyList) : 'Tidak ada riwayat alergi' }}
                        </span>
                    </p>
                </div>

                <div class="overflow-hidden border border-gray-100 rounded-xl">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-100 text-gray-700 text-xs uppercase tracking-wider">
                                <th class="p-3 font-semibold">Nama Obat</th>
                                <th class="p-3 font-semibold">Kandungan Zat Aktif</th>
                                <th class="p-3 font-semibold">Stok & Harga</th>
                                <th class="p-3 font-semibold">Status Skrining</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 text-sm">
                            @forelse($screeningResults as $res)
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-800">{{ $res['obat']->nama_obat }} <br><span class="text-xs text-gray-400 font-normal">({{ $res['obat']->jenis }})</span></td>
                                <td class="p-3 text-gray-600">
                                    {{ $res['obat']->kandungans->pluck('nama_kandungan')->implode(', ') ?: '-' }}
                                </td>
                                <td class="p-3 text-gray-700">
                                    Stok: <b>{{ $res['obat']->stok }}</b><br>
                                    <span class="text-xs text-gray-500">Rp {{ number_format($res['obat']->harga, 0, ',', '.') }}</span>
                                </td>
                                <td class="p-3">
                                    @if($res['is_safe'])
                                        <span class="px-2.5 py-1 text-xs font-bold bg-emerald-100 text-emerald-800 rounded-lg">
                                            ✓ AMAN
                                        </span>
                                    @else
                                        <span class="px-2.5 py-1 text-xs font-bold bg-red-100 text-red-800 rounded-lg">
                                            ✕ TIDAK AMAN (ALERGI)
                                        </span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="p-6 text-center text-gray-500 text-sm">Belum ada data obat di dalam sistem.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="flex flex-col items-center justify-center p-12 text-center border-2 border-dashed border-gray-200 rounded-2xl">
                    <p class="text-sm text-gray-400">Silakan ketik nama pelanggan dan riwayat alerginya di sebelah kiri, lalu klik <b>Proses Skrining Alergi</b>.</p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection