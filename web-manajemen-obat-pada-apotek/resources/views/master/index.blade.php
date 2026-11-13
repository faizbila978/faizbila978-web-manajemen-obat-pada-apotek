@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen space-y-5">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0d2e26]">Data Master</h1>
            <p class="text-sm text-gray-500 mt-1">Daftar kategori dan jenis obat yang dipakai di form Stok & Mutasi.</p>
        </div>
        <form action="{{ route('master.sinkron') }}" method="POST">
            @csrf
            <button type="submit" title="Tambahkan kategori/jenis yang sudah dipakai obat tapi belum ada di daftar"
                    class="px-4 py-2.5 border border-gray-300 bg-white rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition whitespace-nowrap">
                🔄 Sinkronkan dari data obat
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="p-4 bg-amber-50 border border-amber-300 text-amber-900 rounded-xl text-sm font-medium">⚠️ {{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm">{{ $errors->first() }}</div>
    @endif

    <!-- Tab -->
    <div class="inline-flex rounded-xl border border-gray-200 overflow-hidden bg-white text-sm font-medium">
        <a href="{{ route('master.index', ['tab' => 'kategori']) }}"
           class="px-5 py-2.5 {{ $tab === 'kategori' ? 'bg-[#0d2e26] text-white' : 'text-gray-600 hover:bg-gray-50' }}">
            Kategori <span class="ml-1 opacity-70">({{ $jumlah['kategori'] }})</span>
        </a>
        <a href="{{ route('master.index', ['tab' => 'jenis']) }}"
           class="px-5 py-2.5 {{ $tab === 'jenis' ? 'bg-[#0d2e26] text-white' : 'text-gray-600 hover:bg-gray-50' }}">
            Jenis <span class="ml-1 opacity-70">({{ $jumlah['jenis'] }})</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Form tambah -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100 h-fit">
            <h3 class="text-lg font-bold text-[#0d2e26] mb-1">Tambah {{ $cfg['judul'] }}</h3>
            <p class="text-xs text-gray-500 mb-4">{{ $cfg['bantuan'] }}</p>
            <form action="{{ route('master.store', $tab) }}" method="POST">
                @csrf
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama {{ $cfg['judul'] }}</label>
                <input type="text" name="nama" required maxlength="100" value="{{ old('nama') }}" placeholder="{{ $cfg['contoh'] }}"
                       class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none mb-4">
                <button type="submit" class="w-full bg-[#0d2e26] text-white py-2.5 rounded-xl font-semibold hover:bg-[#133d33] transition">
                    Simpan {{ $cfg['judul'] }}
                </button>
            </form>
        </div>

        <!-- Tabel -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-100 text-xs text-gray-600 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3 font-semibold w-16">No</th>
                        <th class="px-4 py-3 font-semibold">Nama {{ $cfg['judul'] }}</th>
                        <th class="px-4 py-3 font-semibold whitespace-nowrap">Dipakai</th>
                        <th class="px-4 py-3 font-semibold text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-800">
                    @forelse($items as $item)
                        @php $n = $pakai[mb_strtolower($item->nama)] ?? 0; @endphp
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-gray-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 font-semibold">{{ $item->nama }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($n > 0)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">{{ $n }} obat</span>
                                @else
                                    <span class="text-xs text-gray-400">Belum dipakai</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    <button type="button" data-id="{{ $item->getKey() }}" data-nama="{{ $item->nama }}" onclick="bukaEdit(this)"
                                            class="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700 transition">Edit</button>

                                    @if($n > 0)
                                        <button type="button" disabled title="Masih dipakai {{ $n }} obat, tidak bisa dihapus"
                                                class="px-3 py-1.5 bg-gray-200 text-gray-400 rounded-lg text-xs font-medium cursor-not-allowed">Hapus</button>
                                    @else
                                        <form action="{{ route('master.destroy', [$tab, $item->getKey()]) }}" method="POST"
                                              onsubmit="return confirm('Hapus {{ strtolower($cfg['judul']) }} ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs font-medium hover:bg-red-700 transition">Hapus</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">
                            Belum ada data. Tambahkan lewat form di kiri, atau klik "Sinkronkan dari data obat".
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Edit -->
<div id="modalEdit" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <h3 class="text-lg font-bold text-[#0d2e26] mb-1">Ubah {{ $cfg['judul'] }}</h3>
        <p class="text-xs text-gray-500 mb-4">Nama baru otomatis diterapkan ke semua obat yang memakainya.</p>
        <form id="formEdit" method="POST">
            @csrf
            @method('PUT')
            <input type="text" id="editNama" name="nama" required maxlength="100"
                   class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none mb-5">
            <div class="flex justify-end gap-2">
                <button type="button" onclick="tutupEdit()" class="px-4 py-2 border border-gray-300 rounded-xl text-sm text-gray-700 hover:bg-gray-50">Batal</button>
                <button type="submit" class="px-4 py-2 bg-[#0d2e26] text-white rounded-xl text-sm font-semibold hover:bg-[#133d33]">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
    const BASE_URL = @json(url('/data-master/' . $tab));
    function bukaEdit(tombol) {
        document.getElementById('formEdit').action = BASE_URL + '/' + tombol.dataset.id;
        document.getElementById('editNama').value = tombol.dataset.nama;
        const m = document.getElementById('modalEdit');
        m.classList.remove('hidden'); m.classList.add('flex');
        document.getElementById('editNama').focus();
    }
    function tutupEdit() {
        const m = document.getElementById('modalEdit');
        m.classList.add('hidden'); m.classList.remove('flex');
    }
    document.getElementById('modalEdit').addEventListener('click', e => { if (e.target.id === 'modalEdit') tutupEdit(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupEdit(); });
</script>
@endsection
