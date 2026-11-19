
@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen space-y-5">

    <div>
        <h1 class="text-2xl font-bold text-[#0d2e26]">Penjualan</h1>
        <p class="text-sm text-gray-500 mt-1">Skrining alergi, daftar penjualan, dan pembayaran.</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 items-start">

        <!-- INPUT SKRINING -->
        <div class="xl:col-span-3 space-y-5">
            <section class="bg-white rounded-2xl border shadow-sm p-5">
                <h2 class="font-semibold text-[#0d2e26] mb-4">Input Data Skrining</h2>

                <div class="space-y-4">
                    <div class="relative">
                        <label for="namaObat" class="block text-sm font-medium mb-1">Nama Obat</label>
                        <input id="namaObat" type="text" autocomplete="off"
                            placeholder="Ketik nama obat, lalu pilih dari daftar"
                            class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">

                        <ul id="saranObat" class="hidden absolute z-20 left-0 right-0 mt-1 bg-white border rounded-xl shadow-lg max-h-64 overflow-y-auto"></ul>
                        <p id="obatTerpilih" class="hidden text-xs text-emerald-700 mt-1"></p>
                        <p id="infoCari" class="hidden text-xs mt-1"></p>
                    </div>

                    <div>
                        <label for="alergi" class="block text-sm font-medium mb-1">
                            Riwayat Alergi Pelanggan
                        </label>
                        <input id="alergi" type="text" maxlength="500" autocomplete="off"
                            placeholder="Contoh: Penisilin, Aspirin"
                            class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
                    </div>

                    <div id="errSkrining" class="hidden p-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm"></div>

                    <button type="button" id="btnSkrining"
                        class="px-6 py-2.5 bg-[#0d2e26] text-white rounded-xl font-semibold hover:bg-[#133d33] disabled:opacity-60">
                        Proses Skrining
                    </button>
                </div>
            </section>

            <!-- HASIL SKRINING -->
            <section id="hasilSkrining" class="hidden bg-white rounded-2xl border shadow-sm p-5"></section>
        </div>

        <!-- DAFTAR PENJUALAN DAN RIWAYAT -->
        <aside class="xl:col-span-2 xl:sticky xl:top-4">
            <section class="bg-white rounded-2xl border shadow-sm p-5">

                <div class="flex gap-2 border-b mb-4 pb-3">
                    <button type="button" id="tabDaftar"
                        class="px-3 py-2 rounded-lg text-sm font-semibold bg-[#0d2e26] text-white">
                        Daftar Penjualan
                    </button>

                    <button type="button" id="tabRiwayat"
                        class="px-3 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-100">
                        Riwayat Transaksi
                    </button>
                </div>

                <!-- DAFTAR PENJUALAN -->
                <div id="panelDaftar">
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="font-semibold text-[#0d2e26]">Daftar Penjualan</h2>
                        <button type="button" id="btnKosongkan"
                            class="hidden text-xs text-red-600 hover:underline">
                            Kosongkan
                        </button>
                    </div>

                    <div id="daftarKosong" class="py-8 text-center text-sm text-gray-400">
                        Belum ada obat. Selesaikan skrining, lalu tambahkan obat.
                    </div>

                    <ul id="daftarObat" class="divide-y divide-gray-100"></ul>

                    <div class="mt-4 pt-4 border-t flex items-center justify-between">
                        <span class="text-sm text-gray-600">Total</span>
                        <span id="totalHarga" class="text-xl font-bold text-[#0d2e26]">Rp 0</span>
                    </div>

                    <button type="button" id="btnBayar" disabled
                        class="mt-4 w-full py-3 bg-[#15803D] text-white rounded-xl font-bold hover:bg-[#166534] disabled:bg-gray-300 disabled:cursor-not-allowed">
                        Bayar
                    </button>
                </div>

                <!-- RIWAYAT TRANSAKSI -->
                <div id="panelRiwayat" class="hidden">

                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h2 class="font-semibold text-[#0d2e26]">Riwayat Transaksi</h2>
                            <p class="text-xs text-gray-500 mt-1">Daftar transaksi yang sudah dilakukan</p>
                        </div>

                        <span class="text-xs bg-emerald-50 text-emerald-700 px-3 py-1.5 rounded-full">
                            {{ $transaksi->count() }} Transaksi
                        </span>
                    </div>

                    <div class="space-y-3">
                        @forelse($transaksi as $t)

                            <div class="border border-gray-200 rounded-xl p-4 hover:border-emerald-300 hover:shadow-sm transition">

                                <div class="flex justify-between items-start gap-3">
                                    <div class="min-w-0">
                                        <p class="text-xs text-gray-500">ID Transaksi: {{ $t->id_penjualan }}</p>
                                        <p class="font-bold text-[#0d2e26] break-all">{{ $t->no_transaksi }}</p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            {{ $t->tgl_transaksi->format('d/m/Y, H:i') }}
                                        </p>
                                    </div>

                                    <span class="text-xs px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 whitespace-nowrap">
                                        {{ $t->metode_bayar }}
                                    </span>
                                </div>

                                <div class="flex justify-between items-center mt-4 pt-3 border-t">
                                    <div>
                                        <p class="text-xs text-gray-500">Total Pembayaran</p>
                                        <p class="text-lg font-bold text-[#0d2e26]">
                                            Rp {{ number_format($t->total, 0, ',', '.') }}
                                        </p>
                                    </div>

                                    <button type="button"
                                        data-buka-detail="detail-{{ $t->id_penjualan }}"
                                        class="px-4 py-2 bg-[#0d2e26] text-white text-xs font-semibold rounded-lg hover:bg-[#174638]">
                                        Lihat Detail
                                    </button>
                                </div>
                            </div>

                            <!-- MODAL DETAIL TRANSAKSI -->
                            <div id="detail-{{ $t->id_penjualan }}"
                                class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100] p-4">

                                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

                                    <div class="sticky top-0 bg-white border-b px-6 py-4 flex justify-between items-center z-10">
                                        <div>
                                            <h3 class="text-lg font-bold text-[#0d2e26]">Detail Transaksi</h3>
                                            <p class="text-xs text-gray-500">Informasi lengkap pembayaran</p>
                                        </div>

                                        <button type="button" data-tutup-detail
                                            class="text-gray-500 hover:text-red-600 text-2xl leading-none">
                                            &times;
                                        </button>
                                    </div>

                                    <div class="p-6 space-y-5">

                                        <div class="bg-gray-50 rounded-xl p-4">
                                            <h4 class="font-semibold text-sm text-[#0d2e26] mb-3">
                                                Informasi Transaksi
                                            </h4>

                                            <div class="grid grid-cols-2 gap-4 text-sm">
                                                <div>
                                                    <p class="text-xs text-gray-500">ID Transaksi</p>
                                                    <p class="font-medium">{{ $t->id_penjualan }}</p>
                                                </div>

                                                <div>
                                                    <p class="text-xs text-gray-500">Nomor Transaksi</p>
                                                    <p class="font-medium">{{ $t->no_transaksi }}</p>
                                                </div>

                                                <div>
                                                    <p class="text-xs text-gray-500">Tanggal</p>
                                                    <p class="font-medium">{{ $t->tgl_transaksi->format('d/m/Y') }}</p>
                                                </div>

                                                <div>
                                                    <p class="text-xs text-gray-500">Waktu</p>
                                                    <p class="font-medium">{{ $t->tgl_transaksi->format('H:i') }}</p>
                                                </div>

                                                <div>
                                                    <p class="text-xs text-gray-500">Metode Pembayaran</p>
                                                    <p class="font-medium">{{ $t->metode_bayar }}</p>
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <h4 class="font-semibold text-sm text-[#0d2e26] mb-3">
                                                Rincian Obat
                                            </h4>

                                            <div class="overflow-x-auto border rounded-xl">
                                                <table class="w-full text-sm">
                                                    <thead class="bg-gray-50 text-gray-600">
                                                        <tr>
                                                            <th class="text-left px-4 py-3">Nama Obat</th>
                                                            <th class="text-center px-3 py-3">Jumlah</th>
                                                            <th class="text-right px-4 py-3">Harga</th>
                                                            <th class="text-right px-4 py-3">Subtotal</th>
                                                        </tr>
                                                    </thead>

                                                    <tbody class="divide-y">
                                                        @foreach($t->details as $d)
                                                            <tr>
                                                                <td class="px-4 py-3 font-medium">{{ $d->nama_obat }}</td>
                                                                <td class="px-3 py-3 text-center">{{ $d->jumlah }} pcs</td>
                                                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                                                    Rp {{ number_format($d->harga, 0, ',', '.') }}
                                                                </td>
                                                                <td class="px-4 py-3 text-right font-medium whitespace-nowrap">
                                                                    Rp {{ number_format($d->subtotal, 0, ',', '.') }}
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="border rounded-xl p-4 space-y-3">
                                            <h4 class="font-semibold text-sm text-[#0d2e26]">
                                                Rincian Pembayaran
                                            </h4>

                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500">Total Belanja</span>
                                                <span class="font-semibold">
                                                    Rp {{ number_format($t->total, 0, ',', '.') }}
                                                </span>
                                            </div>

                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500">Uang Dibayar</span>
                                                <span>Rp {{ number_format($t->dibayar, 0, ',', '.') }}</span>
                                            </div>

                                            <div class="flex justify-between text-sm">
                                                <span class="text-gray-500">Kembalian</span>
                                                <span>Rp {{ number_format($t->kembalian, 0, ',', '.') }}</span>
                                            </div>

                                            <div class="border-t pt-3 flex justify-between items-center">
                                                <span class="font-bold text-[#0d2e26]">Total Pembayaran</span>
                                                <span class="text-xl font-bold text-[#0d2e26]">
                                                    Rp {{ number_format($t->total, 0, ',', '.') }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="text-center text-xs text-gray-400">
                                            Terima kasih telah melakukan transaksi di Apotek Farmasi 57.
                                        </div>

                                    </div>
                                </div>
                            </div>

                        @empty
                            <div class="text-center py-12 border border-dashed rounded-xl">
                                <p class="font-medium text-gray-500">Belum ada transaksi</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    Transaksi yang sudah dibayar akan muncul di sini.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>

            </section>
        </aside>
    </div>
</div>

<!-- MODAL PEMBAYARAN -->
<div id="modalBayar" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <h3 class="text-lg font-bold text-[#0d2e26] mb-4">Pembayaran</h3>

        <div class="flex justify-between text-sm mb-4">
            <span class="text-gray-600">Total belanja</span>
            <b id="bayarTotal" class="text-lg">Rp 0</b>
        </div>

        <label for="metode" class="block text-sm font-medium text-gray-700 mb-1">Metode pembayaran</label>
        <select id="metode" class="w-full px-4 py-2.5 border rounded-xl bg-white mb-4">
            <option>Tunai</option>
            <option>QRIS</option>
            <option>Transfer</option>
        </select>

        <div id="boxTunai" class="mb-4">
            <label for="uangDiterima" class="block text-sm font-medium text-gray-700 mb-1">Uang diterima</label>

            <input id="uangDiterima" type="text" inputmode="numeric" autocomplete="off"
                placeholder="Masukkan uang diterima"
                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">

            <div class="flex justify-between text-sm mt-2">
                <span class="text-gray-600">Kembalian</span>
                <b id="kembalian">Rp 0</b>
            </div>
        </div>

        <div id="errBayar" class="hidden mb-3 p-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm"></div>

        <div class="flex justify-end gap-2">
            <button type="button" id="btnBatalBayar"
                class="px-4 py-2 border border-gray-300 rounded-xl text-sm text-gray-700 hover:bg-gray-50">
                Batal
            </button>

            <button type="button" id="btnKonfirmasiBayar"
                class="px-5 py-2 bg-[#15803D] text-white rounded-xl text-sm font-bold hover:bg-[#166534] disabled:opacity-60">
                Konfirmasi Pembayaran
            </button>
        </div>
    </div>
</div>

<!-- MODAL BERHASIL -->
<div id="modalSukses" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <div class="text-center mb-4">
            <div class="text-4xl mb-1">✅</div>
            <h3 class="text-lg font-bold text-[#0d2e26]">Pembayaran berhasil</h3>
            <p id="sukNo" class="text-sm text-gray-500"></p>
        </div>

        <div id="sukRincian" class="text-sm space-y-1 mb-4"></div>

        <div class="flex justify-end gap-2">
            <button type="button" id="btnCetak"
                class="px-4 py-2 border border-gray-300 rounded-xl text-sm text-gray-700 hover:bg-gray-50">
                Cetak struk
            </button>

            <button type="button" id="btnSelesai"
                class="px-5 py-2 bg-[#0d2e26] text-white rounded-xl text-sm font-semibold hover:bg-[#133d33]">
                Transaksi baru
            </button>
        </div>
    </div>
</div>

<script>
(() => {
    const URL_CARI = @json(route('penjualan.cari'));
    const URL_SKRINING = @json(route('penjualan.skrining'));
    const URL_SETUJUI = @json(url('/penjualan/skrining'));
    const URL_BAYAR = @json(route('penjualan.bayar'));
    const CSRF = @json(csrf_token());

    const $ = (id) => document.getElementById(id);

    const rp = (n) => 'Rp ' + Math.round(Number(n) || 0).toLocaleString('id-ID');

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;'
    }[c]));

    let obatDipilih = null;
    let hasil = null;
    let keranjang = [];
    let terakhirStruk = null;

    function angkaUang() {
        return Number($('uangDiterima').value.replace(/\D/g, '')) || 0;
    }

    // TAB DAFTAR DAN RIWAYAT
    function pilihTab(tab) {
        const riwayat = tab === 'riwayat';

        $('panelDaftar').classList.toggle('hidden', riwayat);
        $('panelRiwayat').classList.toggle('hidden', !riwayat);

        $('tabDaftar').className =
            'px-3 py-2 rounded-lg text-sm font-semibold ' +
            (!riwayat ? 'bg-[#0d2e26] text-white' : 'text-gray-600 hover:bg-gray-100');

        $('tabRiwayat').className =
            'px-3 py-2 rounded-lg text-sm font-semibold ' +
            (riwayat ? 'bg-[#0d2e26] text-white' : 'text-gray-600 hover:bg-gray-100');
    }

    $('tabDaftar').addEventListener('click', () => pilihTab('daftar'));
    $('tabRiwayat').addEventListener('click', () => pilihTab('riwayat'));

    // KIRIM DATA KE SERVER
    async function kirim(url, body) {
        const r = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF
            },
            body: JSON.stringify(body || {})
        });

        const data = await r.json().catch(() => ({}));

        if (!r.ok) {
            let pesan = data.message || 'Terjadi kesalahan. Coba lagi.';

            if (data.errors) {
                pesan = Object.values(data.errors).flat()[0];
            }

            if (r.status === 419) {
                pesan = 'Sesi habis. Muat ulang halaman lalu coba lagi.';
            }

            throw new Error(pesan);
        }

        return data;
    }

    function tampilError(el, pesan) {
        el.textContent = pesan;
        el.classList.toggle('hidden', !pesan);
    }

    // PENCARIAN OBAT
    async function cariObat(q) {
        const r = await fetch(URL_CARI + '?q=' + encodeURIComponent(q), {
            headers: {'Accept': 'application/json'}
        });

        if (!r.ok) {
            throw new Error('Pencarian obat gagal. Silakan muat ulang halaman.');
        }

        return await r.json();
    }

    function infoCari(pesan, galat = false) {
        const el = $('infoCari');

        el.textContent = pesan;
        el.className = 'text-xs mt-1 ' +
            (galat ? 'text-red-600' : 'text-gray-500') +
            (pesan ? '' : ' hidden');
    }

    function tampilSaran(daftar) {
        const ul = $('saranObat');

        if (!daftar.length) {
            ul.innerHTML = '<li class="px-4 py-3 text-sm text-gray-400">Obat tidak ditemukan</li>';
        } else {
            ul.innerHTML = daftar.map((o, i) => `
                <li>
                    <button type="button" data-i="${i}"
                        class="w-full text-left px-4 py-2.5 hover:bg-gray-50 border-b">
                        <div class="text-sm font-medium">${esc(o.nama_obat)}</div>
                        <div class="text-xs text-gray-500">
                            ${esc(o.jenis)} · stok ${esc(o.stok)} · ${rp(o.harga)}
                        </div>
                    </button>
                </li>
            `).join('');
        }

        ul._data = daftar;
        ul.classList.remove('hidden');
    }

    let timer = null;

    $('namaObat').addEventListener('input', () => {
        obatDipilih = null;
        $('obatTerpilih').classList.add('hidden');
        infoCari('');
        sembunyikanHasil();

        clearTimeout(timer);

        const q = $('namaObat').value.trim();

        if (q.length < 2) {
            $('saranObat').classList.add('hidden');
            return;
        }

        infoCari('Mencari obat…');

        timer = setTimeout(async () => {
            try {
                const daftar = await cariObat(q);

                if ($('namaObat').value.trim() !== q) return;

                infoCari('');
                tampilSaran(daftar);
            } catch (e) {
                infoCari(e.message, true);
                $('saranObat').classList.add('hidden');
            }
        }, 250);
    });

    $('saranObat').addEventListener('click', (e) => {
        const tombol = e.target.closest('button[data-i]');

        if (!tombol) return;

        pilihObat($('saranObat')._data[Number(tombol.dataset.i)]);
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('#saranObat') && e.target.id !== 'namaObat') {
            $('saranObat').classList.add('hidden');
        }
    });

    function pilihObat(o) {
        obatDipilih = o;
        $('namaObat').value = o.nama_obat;
        $('saranObat').classList.add('hidden');
        infoCari('');

        const info = $('obatTerpilih');
        info.textContent = `✔ ${o.nama_obat} · ${o.jenis} · ${rp(o.harga)}`;
        info.classList.remove('hidden');
    }

    // PROSES SKRINING
    $('btnSkrining').addEventListener('click', prosesSkrining);

    ['namaObat', 'alergi'].forEach((id) => {
        $(id).addEventListener('keydown', (e) => {
            if (e.key === 'Enter') prosesSkrining();
        });
    });

    async function prosesSkrining() {
        tampilError($('errSkrining'), '');

        // Alergi boleh dikosongkan.
        const alergi = $('alergi').value.trim();

        if (!obatDipilih) {
            const ketik = $('namaObat').value.trim();

            if (ketik.length < 2) {
                return tampilError($('errSkrining'), 'Isi nama obat, lalu pilih dari daftar.');
            }

            try {
                const daftar = await cariObat(ketik);

                const persis = daftar.filter((o) =>
                    o.nama_obat.toLowerCase() === ketik.toLowerCase()
                );

                if (persis.length === 1) {
                    pilihObat(persis[0]);
                } else if (daftar.length === 1) {
                    pilihObat(daftar[0]);
                } else if (daftar.length > 1) {
                    tampilSaran(daftar);
                    return tampilError($('errSkrining'), 'Pilih salah satu obat dari daftar.');
                } else {
                    return tampilError($('errSkrining'), 'Obat tidak ditemukan di data obat.');
                }
            } catch (e) {
                return tampilError($('errSkrining'), e.message);
            }
        }

        const tombol = $('btnSkrining');
        tombol.disabled = true;
        tombol.textContent = 'Memproses…';

        try {
            hasil = await kirim(URL_SKRINING, {
                id_obat: obatDipilih.id_obat,
                alergi: alergi
            });

            tampilHasil(hasil);
        } catch (e) {
            tampilError($('errSkrining'), e.message);
        } finally {
            tombol.disabled = false;
            tombol.textContent = 'Proses Skrining';
        }
    }

    function sembunyikanHasil() {
        hasil = null;
        $('hasilSkrining').classList.add('hidden');
    }

    // TAMPIL HASIL SKRINING
    function tampilHasil(h) {
        const gaya = {
            aman: {
                judul: 'Aman: tidak ditemukan kecocokan alergi',
                kelas: 'border-emerald-300',
                kotak: 'bg-emerald-50 text-emerald-900',
                ikon: '✅'
            },
            peringatan: {
                judul: 'Peringatan: ditemukan kecocokan dengan alergi pelanggan',
                kelas: 'border-red-300',
                kotak: 'bg-red-50 text-red-900',
                ikon: '⛔'
            },
            perlu_verifikasi: {
                judul: 'Perlu pemeriksaan lebih lanjut oleh apoteker',
                kelas: 'border-amber-300',
                kotak: 'bg-amber-50 text-amber-900',
                ikon: '⚠️'
            }
        }[h.status];

        const el = $('hasilSkrining');

        el.className = 'bg-white rounded-2xl border-2 shadow-sm p-5 ' + gaya.kelas;

        const chips = h.alergi && h.alergi.length
            ? h.alergi.map((a) =>
                `<span class="px-2 py-0.5 rounded-full bg-gray-100 text-xs">${esc(a)}</span>`
            ).join(' ')
            : '';

        const temuan = h.temuan && h.temuan.length
            ? `<ul class="mt-3 space-y-1 text-sm list-disc pl-5">${
                h.temuan.map((t) =>
                    `<li><b>${esc(t.alergi)}</b>: ${esc(t.alasan)}</li>`
                ).join('')
            }</ul>`
            : '';

        const alt = h.alternatif && h.alternatif.length
            ? `
                <div class="mt-4">
                    <p class="text-sm font-semibold text-gray-700 mb-2">Alternatif sekategori yang lolos skrining</p>
                    <div class="flex flex-wrap gap-2">
                        ${h.alternatif.map((a, i) => `
                            <button type="button" data-aksi="alternatif" data-i="${i}"
                                class="px-3 py-1.5 border rounded-lg text-xs hover:bg-gray-50 text-left">
                                <span class="font-medium">${esc(a.nama_obat)}</span><br>
                                <span class="text-gray-500">stok ${esc(a.stok)} · ${rp(a.harga)}</span>
                            </button>
                        `).join('')}
                    </div>
                </div>
            `
            : '';

        let aksi = '';

        // Pesan stok habis tidak ditampilkan.
        // Tombol tambah tetap tidak muncul jika stok tidak tersedia.
        if (h.bisa_dijual && h.status === 'aman') {
            aksi = `
                <button type="button" data-aksi="tambah"
                    class="mt-4 px-5 py-2.5 bg-[#0d2e26] text-white rounded-xl text-sm font-semibold">
                    Tambahkan ke Daftar Penjualan
                </button>
            `;
        } else if (h.bisa_dijual && h.status !== 'aman') {
            aksi = `
                <div class="mt-4 p-4 border rounded-xl bg-gray-50">
                    <p class="text-sm font-semibold text-gray-800 mb-1">Persetujuan apoteker</p>
                    <p class="text-xs text-gray-500 mb-3">
                        Obat hanya boleh dijual setelah apoteker memeriksa dan menyetujuinya.
                    </p>

                    <input id="apNama" type="text" maxlength="100"
                        placeholder="Nama apoteker"
                        class="w-full px-3 py-2 border rounded-lg text-sm mb-2">

                    <textarea id="apCatatan" rows="2" maxlength="500"
                        placeholder="Alasan / hasil pemeriksaan apoteker"
                        class="w-full px-3 py-2 border rounded-lg text-sm mb-2"></textarea>

                    <div id="errSetuju" class="hidden mb-2 text-sm text-red-700"></div>

                    <button type="button" data-aksi="setujui"
                        class="px-5 py-2.5 bg-[#0d2e26] text-white rounded-xl text-sm font-semibold">
                        Setujui apoteker &amp; tambahkan
                    </button>
                </div>
            `;
        }

        el.innerHTML = `
            <h2 class="font-semibold text-[#0d2e26] flex items-center gap-2 mb-3">
                <span class="w-6 h-6 rounded-full bg-[#0d2e26] text-white text-xs flex items-center justify-center">2</span>
                Hasil Skrining
            </h2>

            <div class="p-4 rounded-xl ${gaya.kotak}">
                <p class="font-semibold">${gaya.ikon} ${gaya.judul}</p>
                <p class="text-sm mt-1">Obat <b>${esc(h.obat.nama_obat)}</b></p>
                ${temuan}
            </div>

            ${chips ? `
                <div class="mt-3 text-sm">
                    <span class="text-gray-600">Alergi yang diperiksa:</span> ${chips}
                </div>
            ` : ''}

            <p class="text-xs text-gray-500 mt-2">
                Zat aktif: ${esc((h.obat.kandungan || []).join(', ') || 'belum tercatat')}
                · Stok tersedia: ${esc(h.obat.stok)}
            </p>

            ${alt}
            ${aksi}
        `;

        el.classList.remove('hidden');
        el.scrollIntoView({behavior: 'smooth', block: 'nearest'});
    }

    // AKSI HASIL SKRINING
    $('hasilSkrining').addEventListener('click', async (e) => {
        const tombol = e.target.closest('button[data-aksi]');

        if (!tombol || !hasil) return;

        if (tombol.dataset.aksi === 'tambah') {
            tambahKeDaftar(hasil, false);

        } else if (tombol.dataset.aksi === 'alternatif') {
            pilihObat(hasil.alternatif[Number(tombol.dataset.i)]);
            prosesSkrining();

        } else if (tombol.dataset.aksi === 'setujui') {
            const err = $('errSetuju');
            tombol.disabled = true;

            try {
                await kirim(URL_SETUJUI + '/' + hasil.id_screening + '/setujui', {
                    nama_apoteker: $('apNama').value.trim(),
                    catatan: $('apCatatan').value.trim()
                });

                tambahKeDaftar(hasil, true);

            } catch (ex) {
                err.textContent = ex.message;
                err.classList.remove('hidden');

            } finally {
                tombol.disabled = false;
            }
        }
    });

    // TAMBAH OBAT KE DAFTAR
    function tambahKeDaftar(h, disetujui) {
        const ada = keranjang.find((i) => i.id_obat === h.obat.id_obat);

        if (ada) {
            ada.id_screening = h.id_screening;
            ada.stok = h.obat.stok;
            ada.jumlah = Math.min(ada.jumlah + 1, ada.stok);
            ada.perluApoteker = ada.perluApoteker || disetujui;
        } else {
            keranjang.push({
                id_screening: h.id_screening,
                id_obat: h.obat.id_obat,
                nama: h.obat.nama_obat,
                harga: h.obat.harga,
                stok: h.obat.stok,
                jumlah: 1,
                perluApoteker: disetujui
            });
        }

        obatDipilih = null;
        $('namaObat').value = '';
        $('alergi').value = '';
        $('obatTerpilih').classList.add('hidden');

        sembunyikanHasil();
        gambarDaftar();
        $('namaObat').focus();
    }

    // TAMPIL DAFTAR PENJUALAN
    function gambarDaftar() {
        const ada = keranjang.length > 0;

        $('daftarKosong').classList.toggle('hidden', ada);
        $('btnKosongkan').classList.toggle('hidden', !ada);
        $('btnBayar').disabled = !ada;

        $('daftarObat').innerHTML = keranjang.map((it, i) => `
            <li class="py-3">
                <div class="flex justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold truncate">${esc(it.nama)}</p>
                        <p class="text-xs text-gray-500">
                            ${rp(it.harga)} / unit · stok ${esc(it.stok)}
                            ${it.perluApoteker
                                ? '<span class="ml-1 px-1.5 py-0.5 rounded bg-amber-100 text-amber-800 text-[10px] font-bold">Disetujui apoteker</span>'
                                : ''
                            }
                        </p>
                    </div>

                    <button type="button" data-hapus="${i}" class="text-xs text-red-600 hover:underline">
                        Hapus
                    </button>
                </div>

                <div class="flex items-center justify-between mt-2">
                    <div class="inline-flex items-center border rounded-lg overflow-hidden">
                        <button type="button" data-kurang="${i}" class="px-3 py-1 hover:bg-gray-50">−</button>

                        <input type="number" min="1" max="${it.stok}" value="${it.jumlah}"
                            data-qty="${i}" class="w-14 text-center text-sm py-1 border-x outline-none">

                        <button type="button" data-tambah="${i}" class="px-3 py-1 hover:bg-gray-50">+</button>
                    </div>

                    <span class="text-sm font-semibold">${rp(it.harga * it.jumlah)}</span>
                </div>
            </li>
        `).join('');

        $('totalHarga').textContent = rp(total());
    }

    const total = () => keranjang.reduce((s, i) => s + i.harga * i.jumlah, 0);

    const batasi = (it, n) => Math.max(1, Math.min(Number.isFinite(n) ? n : 1, it.stok));

    $('daftarObat').addEventListener('click', (e) => {
        const b = e.target.closest('button');
        if (!b) return;

        if (b.dataset.hapus !== undefined) {
            keranjang.splice(Number(b.dataset.hapus), 1);
        } else if (b.dataset.kurang !== undefined) {
            const it = keranjang[Number(b.dataset.kurang)];
            it.jumlah = batasi(it, it.jumlah - 1);
        } else if (b.dataset.tambah !== undefined) {
            const it = keranjang[Number(b.dataset.tambah)];
            it.jumlah = batasi(it, it.jumlah + 1);
        } else {
            return;
        }

        gambarDaftar();
    });

    $('daftarObat').addEventListener('change', (e) => {
        if (e.target.dataset.qty === undefined) return;

        const it = keranjang[Number(e.target.dataset.qty)];
        it.jumlah = batasi(it, parseInt(e.target.value, 10));

        gambarDaftar();
    });

    $('btnKosongkan').addEventListener('click', () => {
        if (confirm('Kosongkan daftar penjualan?')) {
            keranjang = [];
            gambarDaftar();
        }
    });

    // MODAL PEMBAYARAN
    function bukaModal(id) {
        const m = $(id);
        m.classList.remove('hidden');
        m.classList.add('flex');
    }

    function tutupModal(id) {
        const m = $(id);
        m.classList.add('hidden');
        m.classList.remove('flex');
    }

    $('btnBayar').addEventListener('click', () => {
        $('bayarTotal').textContent = rp(total());
        $('metode').value = 'Tunai';
        $('uangDiterima').value = '';

        hitungKembalian();
        tampilError($('errBayar'), '');

        $('boxTunai').classList.remove('hidden');

        bukaModal('modalBayar');
        $('uangDiterima').focus();
    });

    $('btnBatalBayar').addEventListener('click', () => tutupModal('modalBayar'));

    $('metode').addEventListener('change', () => {
        $('boxTunai').classList.toggle('hidden', $('metode').value !== 'Tunai');
    });

    // FORMAT INPUT UANG DITERIMA
    $('uangDiterima').addEventListener('input', () => {
        const angka = angkaUang();

        $('uangDiterima').value = angka
            ? angka.toLocaleString('id-ID')
            : '';

        hitungKembalian();
    });

    function hitungKembalian() {
        const sisa = angkaUang() - total();
        $('kembalian').textContent = rp(Math.max(0, sisa));
    }

    // KONFIRMASI PEMBAYARAN
    $('btnKonfirmasiBayar').addEventListener('click', async () => {
        const metode = $('metode').value;
        const dibayar = angkaUang();

        if (metode === 'Tunai' && dibayar < total()) {
            return tampilError($('errBayar'), 'Uang yang diterima kurang dari total belanja.');
        }

        const tombol = $('btnKonfirmasiBayar');
        tombol.disabled = true;
        tombol.textContent = 'Memproses…';

        tampilError($('errBayar'), '');

        try {
            const r = await kirim(URL_BAYAR, {
                items: keranjang.map((i) => ({
                    id_screening: i.id_screening,
                    jumlah: i.jumlah
                })),
                metode_bayar: metode,
                dibayar: metode === 'Tunai' ? dibayar : null
            });

            terakhirStruk = r;
            keranjang = [];

            tutupModal('modalBayar');
            tampilSukses(r);

        } catch (e) {
            tampilError($('errBayar'), e.message);
        } finally {
            tombol.disabled = false;
            tombol.textContent = 'Konfirmasi Pembayaran';
        }
    });

    // TAMPIL PEMBAYARAN BERHASIL
    function tampilSukses(r) {
        $('sukNo').textContent = r.no_transaksi + ' · ' + r.waktu;

        $('sukRincian').innerHTML =
            r.items.map((i) => `
                <div class="flex justify-between">
                    <span>${esc(i.nama_obat)} × ${esc(i.jumlah)}</span>
                    <span>${rp(i.subtotal)}</span>
                </div>
            `).join('') +

            `
                <div class="flex justify-between border-t pt-1 mt-1">
                    <span class="text-gray-600">Total</span>
                    <b>${rp(r.total)}</b>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">${esc(r.metode_bayar)}</span>
                    <span>${rp(r.dibayar)}</span>
                </div>

                <div class="flex justify-between">
                    <span class="text-gray-600">Kembalian</span>
                    <b>${rp(r.kembalian)}</b>
                </div>
            `;

        bukaModal('modalSukses');
    }

    $('btnSelesai').addEventListener('click', () => {
        window.location.reload();
    });

    // CETAK STRUK
    $('btnCetak').addEventListener('click', () => {
        const r = terakhirStruk;
        if (!r) return;

        const w = window.open('', '_blank', 'width=380,height=600');
        if (!w) return;

        w.document.write(`
            <html>
            <head>
                <title>Struk ${esc(r.no_transaksi)}</title>
                <style>
                    body {font:13px monospace;width:300px;margin:12px auto;}
                    hr {border:0;border-top:1px dashed #000;}
                    .b {display:flex;justify-content:space-between;}
                </style>
            </head>
            <body>
                <b>Apotek Farmasi 57</b><br>
                ${esc(r.no_transaksi)}<br>
                ${esc(r.waktu)}
                <hr>

                ${r.items.map((i) => `
                    ${esc(i.nama_obat)}
                    <div class="b">
                        <span>${esc(i.jumlah)} × ${rp(i.harga)}</span>
                        <span>${rp(i.subtotal)}</span>
                    </div>
                `).join('')}

                <hr>

                <div class="b"><b>Total</b><b>${rp(r.total)}</b></div>
                <div class="b"><span>${esc(r.metode_bayar)}</span><span>${rp(r.dibayar)}</span></div>
                <div class="b"><span>Kembalian</span><span>${rp(r.kembalian)}</span></div>

                <hr>
                Terima kasih
            </body>
            </html>
        `);

        w.document.close();
        w.focus();
        w.print();
    });

    // MODAL DETAIL RIWAYAT TRANSAKSI
    document.addEventListener('click', (e) => {
        const tombolBuka = e.target.closest('[data-buka-detail]');

        if (tombolBuka) {
            const id = tombolBuka.dataset.bukaDetail;
            const modal = document.getElementById(id);

            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
            }
        }

        const tombolTutup = e.target.closest('[data-tutup-detail]');

        if (tombolTutup) {
            const modal = tombolTutup.closest('[id^="detail-"]');

            if (modal) {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            }
        }

        if (e.target.matches('[id^="detail-"]')) {
            e.target.classList.add('hidden');
            e.target.classList.remove('flex');
        }
    });

    // TUTUP MODAL DENGAN TOMBOL ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            document.querySelectorAll('[id^="detail-"]').forEach((modal) => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });
        }
    });

    // PERINGATAN JIKA HALAMAN DITUTUP SAAT KERANJANG MASIH BERISI
    window.addEventListener('beforeunload', (e) => {
        if (keranjang.length) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

})();
</script>
@endsection
