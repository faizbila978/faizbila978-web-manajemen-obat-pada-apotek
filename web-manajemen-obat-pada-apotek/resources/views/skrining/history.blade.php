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

    <!-- PENCARIAN PELANGGAN -->
    <form action="{{ route('skrining.history') }}"
          method="GET"
          class="bg-white p-4 rounded-xl shadow-sm border mb-5 flex gap-2">

        <input type="text"
               name="search"
               value="{{ $search }}"
               placeholder="Cari nama pelanggan..."
               class="flex-1 border rounded-xl px-3 py-2 text-sm">

        <button type="submit"
                class="bg-[#0d2e26] text-white px-5 rounded-xl text-sm">
            Cari
        </button>

        <a href="{{ route('skrining.history') }}"
           class="border px-4 py-2 rounded-xl text-sm">
            Reset
        </a>

    </form>

    <!-- DAFTAR PELANGGAN -->
    @forelse($pelanggans as $p)

        <div class="bg-white rounded-2xl shadow-sm border p-5 mb-5">

            <div class="flex justify-between items-center">

                <div>
                    <h3 class="font-bold text-lg text-gray-800">
                        {{ $p->nama_pelanggan }}
                    </h3>

                    <p class="text-xs text-gray-500">
                        Terdaftar:
                        {{ $p->created_at->format('d M Y, H:i') }}
                    </p>
                </div>

                <a href="{{ route('skrining.index', [
                    'nama_pelanggan' => $p->nama_pelanggan
                ]) }}"
                   class="bg-emerald-600 text-white px-3 py-2 rounded-lg text-xs">
                    Skrining Ulang
                </a>

            </div>

            <!-- RIWAYAT ALERGI -->
            <div class="mt-5">

                <h4 class="text-xs font-bold text-gray-700 mb-2">
                    RIWAYAT ALERGI
                </h4>

                <div class="flex flex-wrap gap-2">

                    @forelse($p->alergis as $alg)

                        <span class="px-3 py-1 bg-red-50 text-red-700 border border-red-200 rounded-lg text-xs">
                            {{ $alg->nama_alergi }}
                        </span>

                    @empty

                        <span class="text-xs text-gray-400">
                            Tidak ada riwayat alergi.
                        </span>

                    @endforelse

                </div>

            </div>

            <!-- RIWAYAT SKRINING -->
            <div class="mt-5">

                <h4 class="text-xs font-bold text-gray-700 mb-3">
                    RIWAYAT PEMERIKSAAN OBAT
                </h4>

                @if($p->screenings->count() > 0)

                    <div class="overflow-x-auto">

                        <table class="w-full text-left border-collapse">

                            <thead>
                                <tr class="bg-gray-100 text-xs text-gray-700">
                                    <th class="p-3">Tanggal</th>
                                    <th class="p-3">Nama Obat</th>
                                    <th class="p-3">Alergi Pemicu</th>
                                    <th class="p-3">Hasil</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y text-xs">

                                @foreach($p->screenings as $s)

                                    <tr>

                                        <td class="p-3">
                                            {{ $s->tgl_screening
                                                ? \Carbon\Carbon::parse($s->tgl_screening)->format('d M Y H:i')
                                                : '-' }}
                                        </td>

                                        <td class="p-3 font-semibold">
                                            {{ $s->obat->nama_obat ?? 'Obat tidak tersedia' }}
                                        </td>

                                        <td class="p-3">
                                            {{ $s->alergi->nama_alergi ?? '-' }}
                                        </td>

                                        <td class="p-3">

                                            @if(stripos($s->hasil, 'TIDAK AMAN') !== false)

                                                <span class="px-2 py-1 bg-red-100 text-red-700 rounded-lg font-bold">
                                                    TIDAK AMAN
                                                </span>

                                            @else

                                                <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-lg font-bold">
                                                    AMAN
                                                </span>

                                            @endif

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @else

                    <p class="text-xs text-gray-400">
                        Belum ada riwayat pemeriksaan obat.
                    </p>

                @endif

            </div>

        </div>

    @empty

        <div class="bg-white p-8 rounded-xl text-center text-sm text-gray-500">
            @if($search)
                Pelanggan "{{ $search }}" tidak ditemukan.
            @else
                Belum ada riwayat pelanggan.
            @endif
        </div>

    @endforelse

    <div class="mt-5">
        {{ $pelanggans->links() }}
    </div>

</div>
@endsection