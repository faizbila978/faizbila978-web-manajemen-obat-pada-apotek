@extends('layouts.app')

@section('content')
<style>
@media print {
    body * {
        visibility: hidden !important;
    }
    #areaCetakStruk, #areaCetakStruk * {
        visibility: visible !important;
    }
    #areaCetakStruk {
        position: fixed !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin: 0 !important;
        padding: 16px !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        background: #fff !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<div class="p-6 bg-gray-50 min-h-screen space-y-5">

    <div>
        <h1 class="text-2xl font-bold text-[#0d2e26]">Penjualan</h1>
        <p class="text-sm text-gray-500 mt-1">Skrining alergi, daftar penjualan, dan pembayaran.</p>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-5 gap-5 items-start">

        <!-- INPUT & HASIL SKRINING -->
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

        <!-- DAFTAR PENJUALAN & TOMBOL MODAL RIWAYAT TRANSAKSI -->
        <aside class="xl:col-span-2 xl:sticky xl:top-4">
            <section class="bg-white rounded-2xl border shadow-sm p-5">

                <div class="flex gap-2 border-b mb-4 pb-3">
                    <button type="button" id="tabDaftar"
                        class="px-3 py-2 rounded-lg text-sm font-semibold bg-[#0d2e26] text-white">
                        Daftar Penjualan
                    </button>

                    <button type="button" id="btnBukaRiwayat"
                        class="px-3 py-2 rounded-lg text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200">
                        Riwayat Transaksi ({{ $transaksi->count() }})
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
                        Belum ada obat. Selesaikan skrining obat yang aman, lalu tambahkan ke daftar.
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

            </section>
        </aside>
    </div>
</div>

<!-- MODAL RIWAYAT TRANSAKSI (UKURAN SEDANG / PROPORSIONAL) -->
<div id="modalRiwayat"
    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[82vh] flex flex-col overflow-hidden">

        <div class="px-6 py-4 border-b flex items-center justify-between bg-white shrink-0">
            <div class="flex items-center gap-3">
                <div>
                    <h2 class="text-lg font-bold text-[#0d2e26]">Riwayat Transaksi</h2>
                    <p class="text-xs text-gray-500">Daftar transaksi yang sudah dibayar</p>
                </div>
                <span class="text-xs bg-emerald-50 text-emerald-700 font-medium px-3 py-1 rounded-full">
                    {{ $transaksi->count() }} Transaksi
                </span>
            </div>

            <button type="button" id="btnTutupRiwayat"
                class="px-3.5 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-sm font-semibold text-gray-700">
                Tutup
            </button>
        </div>

        <div class="p-5 overflow-y-auto flex-1 space-y-3">
            @forelse($transaksi as $t)
                <div class="border border-gray-200 rounded-xl p-4 hover:border-emerald-300 hover:shadow-sm transition flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs text-gray-500">ID Transaksi: {{ $t->id_penjualan }}</p>
                        <p class="font-bold text-[#0d2e26] break-all">{{ $t->no_transaksi }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">{{ $t->tgl_transaksi->format('d/m/Y, H:i') }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-4">
                        <span class="text-xs px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 font-medium whitespace-nowrap">
                            {{ $t->metode_bayar }}
                        </span>

                        <div class="text-left sm:text-right">
                            <p class="text-xs text-gray-500">Total Pembayaran</p>
                            <p class="text-base font-bold text-[#0d2e26]">
                                Rp {{ number_format($t->total, 0, ',', '.') }}
                            </p>
                        </div>

                        <button type="button"
                            data-buka-detail="detail-{{ $t->id_penjualan }}"
                            class="px-3.5 py-2 bg-[#0d2e26] text-white text-xs font-semibold rounded-lg hover:bg-[#174638]">
                            Lihat Detail
                        </button>
                    </div>
                </div>

                <!-- MODAL DETAIL TRANSAKSI -->
                <div id="detail-{{ $t->id_penjualan }}"
                    class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[100] p-4">
                    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-y-auto">
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
                                <h4 class="font-semibold text-sm text-[#0d2e26] mb-3">Informasi Transaksi</h4>
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
                                <h4 class="font-semibold text-sm text-[#0d2e26] mb-3">Rincian Obat</h4>
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
                                <h4 class="font-semibold text-sm text-[#0d2e26]">Rincian Pembayaran</h4>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500">Total Belanja</span>
                                    <span class="font-semibold">Rp {{ number_format($t->total, 0, ',', '.') }}</span>
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
                placeholder="0"
                class="w-full px-4 py-2.5 border rounded-xl focus:ring-2 focus:ring-[#0d2e26] outline-none">
            <div class="flex justify-between text-sm mt-2">
                <span class="text-gray-500">Kembalian</span>
                <b id="kembalian">Rp 0</b>
            </div>
        </div>

        <div id="errBayar" class="hidden mb-4 p-3 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm"></div>

        <div class="flex gap-2 justify-end">
            <button type="button" id="btnBatalBayar"
                class="px-4 py-2.5 rounded-xl bg-gray-100 hover:bg-gray-200 text-sm font-semibold text-gray-700">
                Batal
            </button>
            <button type="button" id="btnKonfirmasiBayar"
                class="px-5 py-2.5 rounded-xl bg-[#15803D] hover:bg-[#166534] text-sm font-semibold text-white">
                Konfirmasi Bayar
            </button>
        </div>
    </div>
</div>

<!-- MODAL STRUK & CETAK STRUK -->
<div id="modalStruk" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-[60] p-4">
    <div id="areaCetakStruk" class="bg-white rounded-2xl w-full max-w-md p-6 shadow-xl">
        <div id="isiStruk"></div>
        <div class="no-print mt-5 flex gap-2">
            <button type="button" onclick="cetakStruk()"
                class="flex-1 py-2.5 bg-[#15803D] hover:bg-[#166534] text-white rounded-xl font-semibold text-sm">
                Cetak Struk
            </button>
            <button type="button" onclick="window.location.reload()"
                class="flex-1 py-2.5 bg-[#0d2e26] hover:bg-[#133d33] text-white rounded-xl font-semibold text-sm">
                Selesai
            </button>
        </div>
    </div>
</div>

<script>
const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}';
let obatDipilih = null;
let keranjang = [];
let timerCari = null;

const elNamaObat = document.getElementById('namaObat');
const elSaranObat = document.getElementById('saranObat');
const elObatTerpilih = document.getElementById('obatTerpilih');
const elInfoCari = document.getElementById('infoCari');
const elAlergi = document.getElementById('alergi');
const elErrSkrining = document.getElementById('errSkrining');
const elBtnSkrining = document.getElementById('btnSkrining');
const elHasilSkrining = document.getElementById('hasilSkrining');

const elDaftarKosong = document.getElementById('daftarKosong');
const elDaftarObat = document.getElementById('daftarObat');
const elTotalHarga = document.getElementById('totalHarga');
const elBtnBayar = document.getElementById('btnBayar');
const elBtnKosongkan = document.getElementById('btnKosongkan');

const elModalRiwayat = document.getElementById('modalRiwayat');
const elModalBayar = document.getElementById('modalBayar');
const elModalStruk = document.getElementById('modalStruk');

const rp = n => 'Rp ' + Number(n || 0).toLocaleString('id-ID');
const esc = s => String(s ?? '').replace(/[&<>"']/g, m => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
}[m]));

// Modal Riwayat Transaksi
document.getElementById('btnBukaRiwayat').addEventListener('click', () => {
    elModalRiwayat.classList.remove('hidden');
    elModalRiwayat.classList.add('flex');
});

document.getElementById('btnTutupRiwayat').addEventListener('click', () => {
    elModalRiwayat.classList.add('hidden');
    elModalRiwayat.classList.remove('flex');
});

elModalRiwayat.addEventListener('click', e => {
    if (e.target === elModalRiwayat) {
        elModalRiwayat.classList.add('hidden');
        elModalRiwayat.classList.remove('flex');
    }
});

// Modal Detail Transaksi
document.querySelectorAll('[data-buka-detail]').forEach(btn => {
    btn.addEventListener('click', () => {
        const modal = document.getElementById(btn.dataset.bukaDetail);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    });
});

document.querySelectorAll('[data-tutup-detail]').forEach(btn => {
    btn.addEventListener('click', () => {
        const modal = btn.closest('[id^="detail-"]');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    });
});

// Pencarian Obat
elNamaObat.addEventListener('input', () => {
    obatDipilih = null;
    elObatTerpilih.classList.add('hidden');
    clearTimeout(timerCari);

    const q = elNamaObat.value.trim();
    if (q.length < 2) {
        elSaranObat.classList.add('hidden');
        elInfoCari.classList.add('hidden');
        return;
    }

    timerCari = setTimeout(async () => {
        elInfoCari.textContent = 'Mencari obat...';
        elInfoCari.className = 'text-xs text-gray-500 mt-1';
        elInfoCari.classList.remove('hidden');

        const res = await fetch(`{{ route('penjualan.cari') }}?q=${encodeURIComponent(q)}`);
        const data = await res.json();

        elInfoCari.classList.add('hidden');
        if (!data.length) {
            elSaranObat.innerHTML = '<li class="px-4 py-2.5 text-sm text-gray-400">Obat tidak ditemukan</li>';
            elSaranObat.classList.remove('hidden');
            return;
        }

        elSaranObat.innerHTML = data.map(o => `
            <li data-id="${o.id_obat}" data-nama="${esc(o.nama_obat)}" data-jenis="${esc(o.jenis)}" data-harga="${o.harga}"
                class="px-4 py-2.5 hover:bg-emerald-50 cursor-pointer flex justify-between items-center text-sm">
                <div>
                    <span class="font-medium text-[#0d2e26]">${esc(o.nama_obat)}</span>
                    <span class="text-xs text-gray-400 ml-1">(${esc(o.jenis)})</span>
                </div>
                <span class="text-xs font-semibold text-gray-600">${rp(o.harga)}</span>
            </li>
        `).join('');
        elSaranObat.classList.remove('hidden');
    }, 200);
});

elSaranObat.addEventListener('click', e => {
    const li = e.target.closest('li[data-id]');
    if (!li) return;

    obatDipilih = {
        id_obat: Number(li.dataset.id),
        nama_obat: li.dataset.nama,
        jenis: li.dataset.jenis,
        harga: Number(li.dataset.harga),
    };
    elNamaObat.value = obatDipilih.nama_obat;
    elObatTerpilih.textContent = `Dipilih: ${obatDipilih.nama_obat} (${obatDipilih.jenis}) - ${rp(obatDipilih.harga)}`;
    elObatTerpilih.classList.remove('hidden');
    elSaranObat.classList.add('hidden');
});

document.addEventListener('click', e => {
    if (!e.target.closest('#namaObat') && !e.target.closest('#saranObat')) {
        elSaranObat.classList.add('hidden');
    }
});

// Proses Skrining
elBtnSkrining.addEventListener('click', async () => {
    elErrSkrining.classList.add('hidden');

    if (!obatDipilih) {
        elErrSkrining.textContent = 'Pilih obat dari daftar saran terlebih dahulu.';
        elErrSkrining.classList.remove('hidden');
        return;
    }

    elBtnSkrining.disabled = true;
    elBtnSkrining.textContent = 'Memproses...';

    try {
        const res = await fetch(`{{ route('penjualan.skrining') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                id_obat: obatDipilih.id_obat,
                alergi: elAlergi.value,
            }),
        });

        const data = await res.json();
        if (!res.ok) {
            elErrSkrining.textContent = data.message || 'Gagal melakukan skrining.';
            elErrSkrining.classList.remove('hidden');
            return;
        }

        renderHasilSkrining(data);
    } finally {
        elBtnSkrining.disabled = false;
        elBtnSkrining.textContent = 'Proses Skrining';
    }
});

function renderHasilSkrining(data) {
    const aman = data.status === 'aman';
    const temuanHtml = Array.isArray(data.temuan) && data.temuan.length
        ? `<ul class="list-disc pl-5 mt-2 space-y-1 text-sm">${data.temuan.map(t => `<li>${esc(t.alasan || t)}</li>`).join('')}</ul>`
        : '';

    const altHtml = Array.isArray(data.alternatif) && data.alternatif.length
        ? `<div class="mt-3 pt-3 border-t border-red-200">
                <p class="text-xs font-semibold text-red-800 mb-2">Alternatif obat lain yang dapat dicek:</p>
                <div class="flex flex-wrap gap-2">
                    ${data.alternatif.map(a => `<span class="text-xs px-2.5 py-1 rounded-lg bg-white border text-gray-700">${esc(a.nama_obat)} (${rp(a.harga)})</span>`).join('')}
                </div>
           </div>`
        : '';

    let aksiHtml = '';
    if (!aman) {
        aksiHtml = `<p class="text-xs font-semibold text-red-700 mt-3">Obat tidak lolos skrining dan tidak dapat dijual.</p>`;
    } else if (!data.bisa_dijual) {
        aksiHtml = `<p class="text-xs font-semibold text-amber-700 mt-3">Stok obat habis atau sudah kedaluwarsa, tidak dapat dijual.</p>`;
    } else {
        aksiHtml = `<button type="button" id="btnTambahKeranjang"
            class="mt-4 px-4 py-2 bg-[#0d2e26] text-white text-sm font-semibold rounded-xl hover:bg-[#133d33]">
            + Tambah ke Daftar Penjualan
        </button>`;
    }

    elHasilSkrining.innerHTML = `
        <div class="p-4 rounded-xl border ${aman ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-red-50 border-red-200 text-red-900'}">
            <div class="flex items-center justify-between gap-2">
                <h3 class="font-bold text-base">${esc(data.obat.nama_obat)}</h3>
                <span class="text-xs font-bold px-3 py-1 rounded-full ${aman ? 'bg-emerald-200 text-emerald-950' : 'bg-red-200 text-red-950'}">
                    ${esc(data.hasil)}
                </span>
            </div>
            <p class="text-xs mt-1 opacity-80">
                Jenis: ${esc(data.obat.jenis)} &bull; Harga: ${rp(data.obat.harga)} &bull; Stok tersedia: ${data.obat.stok}
            </p>
            ${temuanHtml}
            ${aksiHtml}
            ${!aman ? altHtml : ''}
        </div>
    `;
    elHasilSkrining.classList.remove('hidden');

    const btnTambah = document.getElementById('btnTambahKeranjang');
    if (btnTambah) {
        btnTambah.addEventListener('click', () => {
            if (keranjang.some(k => k.id_obat === data.obat.id_obat)) {
                alert('Obat ini sudah ada di daftar penjualan.');
                return;
            }
            keranjang.push({
                id_screening: data.id_screening,
                id_obat: data.obat.id_obat,
                nama_obat: data.obat.nama_obat,
                harga: Number(data.obat.harga),
                stok: Number(data.obat.stok),
                jumlah: 1,
            });
            renderKeranjang();
        });
    }
}

function renderKeranjang() {
    if (!keranjang.length) {
        elDaftarKosong.classList.remove('hidden');
        elDaftarObat.innerHTML = '';
        elBtnKosongkan.classList.add('hidden');
        elBtnBayar.disabled = true;
        elTotalHarga.textContent = rp(0);
        return;
    }

    elDaftarKosong.classList.add('hidden');
    elBtnKosongkan.classList.remove('hidden');
    elBtnBayar.disabled = false;

    let total = 0;
    elDaftarObat.innerHTML = keranjang.map((item, idx) => {
        const sub = item.harga * item.jumlah;
        total += sub;
        return `
            <li class="py-3 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-semibold text-sm text-[#0d2e26] truncate">${esc(item.nama_obat)}</p>
                    <p class="text-xs text-gray-500">${rp(item.harga)} &times; ${item.jumlah} = <b>${rp(sub)}</b></p>
                </div>
                <div class="flex items-center gap-1.5 shrink-0">
                    <button type="button" onclick="ubahJumlah(${idx}, -1)" class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 font-bold">-</button>
                    <span class="w-6 text-center text-sm font-semibold">${item.jumlah}</span>
                    <button type="button" onclick="ubahJumlah(${idx}, 1)" class="w-7 h-7 rounded-lg bg-gray-100 hover:bg-gray-200 font-bold">+</button>
                    <button type="button" onclick="hapusItem(${idx})" class="ml-1 text-xs text-red-600 hover:underline">Hapus</button>
                </div>
            </li>
        `;
    }).join('');

    elTotalHarga.textContent = rp(total);
}

window.ubahJumlah = (idx, delta) => {
    const item = keranjang[idx];
    if (!item) return;
    const next = item.jumlah + delta;
    if (next < 1) return;
    if (next > item.stok) {
        alert(`Stok tersedia hanya ${item.stok}.`);
        return;
    }
    item.jumlah = next;
    renderKeranjang();
};

window.hapusItem = idx => {
    keranjang.splice(idx, 1);
    renderKeranjang();
};

elBtnKosongkan.addEventListener('click', () => {
    keranjang = [];
    renderKeranjang();
});

// Pembayaran
const elBayarTotal = document.getElementById('bayarTotal');
const elMetode = document.getElementById('metode');
const elBoxTunai = document.getElementById('boxTunai');
const elUangDiterima = document.getElementById('uangDiterima');
const elKembalian = document.getElementById('kembalian');
const elErrBayar = document.getElementById('errBayar');

function totalKeranjang() {
    return keranjang.reduce((acc, i) => acc + i.harga * i.jumlah, 0);
}

function angkaBersih(v) {
    return Number(String(v || '0').replace(/\D/g, '')) || 0;
}

function hitungKembalian() {
    const tot = totalKeranjang();
    const bayar = elMetode.value === 'Tunai' ? angkaBersih(elUangDiterima.value) : tot;
    elKembalian.textContent = rp(Math.max(0, bayar - tot));
}

elBtnBayar.addEventListener('click', () => {
    elErrBayar.classList.add('hidden');
    elBayarTotal.textContent = rp(totalKeranjang());
    elUangDiterima.value = '';
    elMetode.value = 'Tunai';
    elBoxTunai.classList.remove('hidden');
    hitungKembalian();
    elModalBayar.classList.remove('hidden');
    elModalBayar.classList.add('flex');
});

elMetode.addEventListener('change', () => {
    elBoxTunai.classList.toggle('hidden', elMetode.value !== 'Tunai');
    hitungKembalian();
});

elUangDiterima.addEventListener('input', () => {
    const n = angkaBersih(elUangDiterima.value);
    elUangDiterima.value = n ? n.toLocaleString('id-ID') : '';
    hitungKembalian();
});

document.getElementById('btnBatalBayar').addEventListener('click', () => {
    elModalBayar.classList.add('hidden');
    elModalBayar.classList.remove('flex');
});

document.getElementById('btnKonfirmasiBayar').addEventListener('click', async () => {
    elErrBayar.classList.add('hidden');
    const btn = document.getElementById('btnKonfirmasiBayar');
    btn.disabled = true;

    try {
        const res = await fetch(`{{ route('penjualan.bayar') }}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                items: keranjang.map(i => ({
                    id_screening: i.id_screening,
                    jumlah: i.jumlah,
                })),
                metode_bayar: elMetode.value,
                dibayar: elMetode.value === 'Tunai' ? angkaBersih(elUangDiterima.value) : totalKeranjang(),
            }),
        });

        const data = await res.json();
        if (!res.ok) {
            const pesan = data.errors?.items?.[0] || data.message || 'Pembayaran gagal.';
            elErrBayar.textContent = pesan;
            elErrBayar.classList.remove('hidden');
            return;
        }

        elModalBayar.classList.add('hidden');
        elModalBayar.classList.remove('flex');
        keranjang = [];
        renderKeranjang();
        tampilkanStruk(data);
    } finally {
        btn.disabled = false;
    }
});

function tampilkanStruk(d) {
    document.getElementById('isiStruk').innerHTML = `
        <div class="text-center border-b pb-3 mb-3">
            <h3 class="text-lg font-bold text-[#0d2e26]">Apotek Farmasi 57</h3>
            <p class="text-xs text-gray-500">Struk Pembayaran</p>
            <p class="text-xs text-gray-500 mt-1">${esc(d.no_transaksi)} &bull; ${esc(d.waktu)}</p>
        </div>
        <div class="divide-y text-sm border-b pb-2 mb-3">
            ${d.items.map(i => `
                <div class="py-1.5 flex justify-between gap-2">
                    <div>
                        <div class="font-medium">${esc(i.nama_obat)}</div>
                        <div class="text-xs text-gray-500">${i.jumlah} x ${rp(i.harga)}</div>
                    </div>
                    <b>${rp(i.subtotal)}</b>
                </div>
            `).join('')}
        </div>
        <div class="space-y-1 text-sm">
            <div class="flex justify-between"><span>Metode</span><b>${esc(d.metode_bayar)}</b></div>
            <div class="flex justify-between"><span>Total</span><b>${rp(d.total)}</b></div>
            <div class="flex justify-between"><span>Dibayar</span><span>${rp(d.dibayar)}</span></div>
            <div class="flex justify-between"><span>Kembalian</span><b>${rp(d.kembalian)}</b></div>
        </div>
        <p class="text-center text-xs text-gray-400 mt-4">Terima kasih telah berbelanja.</p>
    `;
    elModalStruk.classList.remove('hidden');
    elModalStruk.classList.add('flex');
}

function cetakStruk() {
    window.print();
}
</script>
@endsection