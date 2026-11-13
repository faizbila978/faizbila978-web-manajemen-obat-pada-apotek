@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#0d2e26]">
                Riwayat Pelanggan
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Daftar pelanggan dan riwayat pemeriksaan alergi obat.
            </p>
        </div>

        <a href="{{ route('skrining.index') }}"
           class="px-4 py-2 bg-[#0d2e26] text-white rounded-xl text-sm">
            Kembali ke Skrining
        </a>
    </div>

    <!-- FORM PENCARIAN RIWAYAT -->
    <div class="bg-white p-4 rounded-2xl shadow-sm border mb-6">
        <form action="{{ route('skrining.history') }}" method="GET" class="flex gap-2">
            <input type="text"
                   name="search"
                   value="{{ $search ?? '' }}"
                   placeholder="Cari nama pelanggan..."
                   class="w-full border rounded-xl p-2 text-sm">

            <button type="submit" class="px-5 py-2 bg-[#0d2e26] text-white rounded-xl text-sm">
                Cari
            </button>

            <a href="{{ route('skrining.history') }}" class="px-5 py-2 bg-gray-200 text-gray-700 rounded-xl text-sm flex items-center">
                Reset
            </a>
        </form>
    </div>

    <!-- DAFTAR PELANGGAN & RIWAYAT -->
    <div class="space-y-6">
        @forelse($pelanggans as $pelanggan)
            <div class="bg-white p-6 rounded-2xl shadow-sm border">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800">
                            {{ $pelanggan->nama_pelanggan }}
                        </h3>
                        
                        <!-- Menampilkan Waktu Screening Terakhir -->
                        <p class="text-xs text-gray-500 mt-0.5">
                            Screening Terakhir: 
                            {{ optional($pelanggan->screenings->first())->tgl_screening 
                                ? \Carbon\Carbon::parse($pelanggan->screenings->first()->tgl_screening)->format('d M Y, H:i') 
                                : $pelanggan->created_at->format('d M Y, H:i') }}
                        </p>
                    </div>

                    <a href="{{ route('skrining.index', ['nama_pelanggan' => $pelanggan->nama_pelanggan, 'keyword' => '', 'nama_alergi' => $pelanggan->alergis->pluck('nama_alergi')->implode(', ')]) }}"
                       class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-bold">
                        Skrining Ulang
                    </a>
                </div>

                <!-- DAFTAR ALERGI PELANGGAN -->
                <div class="mb-4">
                    <p class="text-xs font-bold text-gray-600 mb-1">RIWAYAT ALERGI</p>
                    <div class="flex flex-wrap gap-1">
                        @forelse($pelanggan->alergis as $alergi)
                            <span class="px-2.5 py-1 bg-red-50 text-red-700 border border-red-200 rounded-lg text-xs">
                                {{ $alergi->nama_alergi }}
                            </span>
                        @empty
                            <span class="text-xs text-gray-400">Tidak ada riwayat alergi tercatat</span>
                        @endforelse
                    </div>
                </div>

                <!-- TABEL RIWAYAT PEMERIKSAAN OBAT -->
                <div>
                    <p class="text-xs font-bold text-gray-600 mb-2">RIWAYAT PEMERIKSAAN OBAT</p>
                    
                    <div class="overflow-x-auto border rounded-xl">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-gray-50 border-b text-xs text-gray-500">
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">Nama Obat</th>
                                    <th class="p-3">Alergi Pemicu</th>
                                    <th class="p-3">Hasil</th>
                                </tr>
                            </thead>
                            <tbody class="text-sm divide-y">
                                @forelse($pelanggan->screenings as $screening)
                                    <tr class="hover:bg-gray-50">
                                        <td class="p-3 text-xs text-gray-600">
                                            {{ \Carbon\Carbon::parse($screening->tgl_screening)->format('d M Y, H:i') }}
                                        </td>
                                        <td class="p-3 font-medium text-gray-800">
                                            {{ $screening->obat->nama_obat ?? '-' }}
                                        </td>
                                        <td class="p-3 text-gray-600">
                                            {{ $screening->alergi->nama_alergi ?? '-' }}
                                        </td>
                                        <td class="p-3">
                                            @if(str_contains(strtoupper($screening->hasil), 'AMAN') && !str_contains(strtoupper($screening->hasil), 'TIDAK'))
                                                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-lg text-xs font-bold">
                                                    AMAN
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 bg-red-100 text-red-800 rounded-lg text-xs font-bold">
                                                    TIDAK AMAN
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="p-4 text-center text-xs text-gray-400">
                                            Belum ada riwayat pemeriksaan obat untuk pelanggan ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        @empty
            <div class="bg-white p-10 text-center rounded-2xl border">
                <p class="text-sm text-gray-400">Belum ada data pelanggan atau riwayat skrining.</p>
            </div>
        @endforelse
    </div>

    <!-- PAGINATION -->
    <div class="mt-6">
        {{ $pelanggans->links() }}
    </div>

</div>
@endsection