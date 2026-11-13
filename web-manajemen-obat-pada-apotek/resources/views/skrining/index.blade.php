@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen">

    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-[#0d2e26]">
                Skrining Alergi Pasien
            </h1>
            <p class="text-xs text-gray-500 mt-1">
                Pemeriksaan keamanan obat berdasarkan riwayat alergi pelanggan.
            </p>
        </div>

        <a href="{{ route('skrining.history') }}"
           class="px-4 py-2 bg-amber-600 text-white rounded-xl text-sm">
            Riwayat Pelanggan
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- FORM PENCARIAN -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border h-fit">

            <h3 class="font-bold text-[#0d2e26] mb-4">
                Input Data Skrining
            </h3>

            <form action="{{ route('skrining.index') }}" method="GET">

                <div class="mb-4">
                    <label class="block text-sm mb-1">
                        Nama Pelanggan
                    </label>

                    <input type="text"
                           name="nama_pelanggan"
                           value="{{ $namaPelanggan }}"
                           required
                           placeholder="Nama pelanggan"
                           class="w-full border rounded-xl p-2 text-sm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm mb-1">
                        Nama Obat
                    </label>

                    <input type="text"
                           name="keyword"
                           value="{{ $keyword }}"
                           required
                           placeholder="Contoh: Amoxicillin"
                           class="w-full border rounded-xl p-2 text-sm">
                </div>

                <div class="mb-4">
                    <label class="block text-sm mb-1">
                        Riwayat Alergi Pelanggan
                    </label>

                    <input type="text"
                           name="nama_alergi"
                           value="{{ $namaAlergiInput }}"
                           placeholder="Contoh: Amoxicillin"
                           class="w-full border rounded-xl p-2 text-sm">

                    <p class="text-xs text-gray-400 mt-1">
                        Jika lebih dari satu alergi, pisahkan dengan koma.
                    </p>
                </div>

                <button type="submit"
                        class="w-full bg-[#0d2e26] text-white py-2.5 rounded-xl">
                    Periksa Keamanan Obat
                </button>

            </form>
        </div>

        <!-- HASIL SKRINING -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl shadow-sm border">

            <h3 class="text-lg font-bold text-[#0d2e26] mb-4">
                Hasil Pengecekan Obat
            </h3>

            @if(count($screeningResults) > 0)

                <div class="p-4 bg-emerald-50 border rounded-xl mb-4">
                    <p class="font-bold text-sm">
                        Pelanggan: {{ $namaPelanggan }}
                    </p>

                    <p class="text-sm mt-1">
                        Riwayat alergi:
                        <span class="font-bold text-red-600">
                            {{ count($allergyList) > 0
                                ? implode(', ', $allergyList)
                                : 'Tidak ada alergi tercatat' }}
                        </span>
                    </p>
                </div>

                @foreach($screeningResults as $res)

                    <div class="border rounded-xl p-4 mb-4">

                        <div class="flex justify-between items-start gap-3">

                            <div>
                                <h4 class="font-bold text-gray-800">
                                    {{ $res['obat']->nama_obat }}
                                </h4>

                                <!-- Tambahan Kategori Obat Utama -->
                                <p class="text-xs text-gray-500 mt-1">
                                    Kategori: {{ $res['obat']->kategori }}
                                </p>

                                <p class="text-xs text-gray-500">
                                    Jenis: {{ $res['obat']->jenis }}
                                </p>

                                <p class="text-xs text-gray-500">
                                    Stok: {{ $res['obat']->stok }}
                                </p>

                                <p class="text-sm mt-2">
                                    Kandungan:
                                    {{ $res['obat']->kandungans
                                        ->pluck('nama_kandungan')
                                        ->implode(', ') ?: '-' }}
                                </p>
                            </div>

                            @if($res['is_safe'])
                                <span class="bg-emerald-100 text-emerald-800 px-3 py-1 rounded-lg text-xs font-bold">
                                    AMAN
                                </span>
                            @else
                                <span class="bg-red-100 text-red-800 px-3 py-1 rounded-lg text-xs font-bold">
                                    TIDAK AMAN
                                </span>
                            @endif

                        </div>

                        @if(!$res['is_safe'])

                            <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-lg">

                                <p class="text-sm text-red-700">
                                    Obat berpotensi memicu alergi:
                                    <strong>
                                        {{ $res['alergi_pemicu']->nama_alergi }}
                                    </strong>
                                </p>

                            </div>

                            <!-- REKOMENDASI ALTERNATIF -->
                            <div class="mt-4">

                                <h5 class="font-bold text-sm text-[#0d2e26] mb-2">
                                    Rekomendasi Obat Alternatif (Kategori yang Sama)
                                </h5>

                                @forelse($res['alternatif'] as $alternatif)

                                    <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl mb-2">

                                        <div class="flex justify-between">
                                            <span class="font-bold text-sm">
                                                {{ $alternatif->nama_obat }}
                                            </span>

                                            <span class="text-xs text-emerald-700 font-bold">
                                                Lolos pencocokan
                                            </span>
                                        </div>

                                        <!-- Tambahan Kategori Obat Alternatif -->
                                        <p class="text-xs text-gray-600 mt-1">
                                            Kategori: {{ $alternatif->kategori }}
                                        </p>

                                        <p class="text-xs text-gray-600">
                                            Jenis: {{ $alternatif->jenis }}
                                        </p>

                                        <p class="text-xs text-gray-600">
                                            Kandungan:
                                            {{ $alternatif->kandungans
                                                ->pluck('nama_kandungan')
                                                ->implode(', ') ?: '-' }}
                                        </p>

                                        <p class="text-xs text-gray-600">
                                            Stok: {{ $alternatif->stok }}
                                        </p>

                                    </div>

                                @empty

                                    <p class="text-xs text-gray-500">
                                        Belum ditemukan obat alternatif dengan kategori yang sama
                                        yang lolos pencocokan alergi dan memiliki stok.
                                    </p>

                                @endforelse

                            </div>

                        @endif

                    </div>

                @endforeach

            @elseif($searched)

                <div class="p-6 text-center bg-amber-50 border rounded-xl">
                    <p class="text-sm text-amber-800">
                        Obat tidak ditemukan. Silakan periksa kembali nama obat.
                    </p>
                </div>

            @else

                <div class="p-10 text-center border-2 border-dashed rounded-xl">
                    <p class="text-sm text-gray-400">
                        Masukkan nama pelanggan, nama obat, dan riwayat alergi
                        untuk memulai pemeriksaan.
                    </p>
                </div>

            @endif

            <p class="text-xs text-gray-400 mt-4">
                Hasil skrining berdasarkan data kandungan dan alergi yang
                tersimpan. Semua hasil dan alternatif harus diverifikasi
                oleh apoteker sebelum obat diberikan.
            </p>

        </div>

    </div>
</div>
@endsection