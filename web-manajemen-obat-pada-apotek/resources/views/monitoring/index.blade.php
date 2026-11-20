@extends('layouts.app')

@section('content')
<div class="p-6 bg-gray-50 min-h-screen space-y-5">

    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <h1 class="text-2xl font-bold text-[#0d2e26]">Monitoring Suhu & Kelembapan</h1>
            <p class="text-sm text-gray-500 mt-1">Pantau kondisi ruang penyimpanan obat dari sensor IoT.</p>
        </div>
        <form action="{{ route('monitoring.simulate') }}" method="POST">
            @csrf
            <button type="submit" class="bg-[#0d2e26] text-white px-4 py-2.5 rounded-xl shadow hover:bg-[#133d33] transition font-medium text-sm whitespace-nowrap">
                🔄 Simulasi Data Sensor
            </button>
        </form>
    </div>

    @if(session('success'))
        <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 rounded-xl text-sm">
            <p class="font-semibold mb-1">Gagal menyimpan pengaturan batas:</p>
            <ul class="list-disc list-inside space-y-0.5 text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

@if(!$perangkat)
    <div class="p-12 text-center bg-white rounded-2xl border border-gray-100 text-gray-500 text-sm">
        Belum ada perangkat sensor. Klik <b>Simulasi Data Sensor</b> untuk membuat perangkat dan data contoh.
    </div>
@else
    @php
        [$sMin, $sMax] = $batas['suhu'];
        [$hMin, $hMax] = $batas['lembap'];
        $suhu   = $terkini?->suhu;
        $lembap = $terkini?->kelembapan;
        $suhuOk   = $suhu === null   || ($suhu >= $sMin && $suhu <= $sMax);
        $lembapOk = $lembap === null || ($lembap >= $hMin && $lembap <= $hMax);

        $selisih = function ($nilai, $min, $max, $sat) {
            if ($nilai > $max) return 'Melebihi batas maksimum ' . round($nilai - $max, 1) . $sat;
            if ($nilai < $min) return 'Di bawah batas minimum ' . round($min - $nilai, 1) . $sat;
            return 'Dalam batas aman';
        };

        $kartu = [
            ['judul' => 'Suhu Ruang Penyimpanan Obat', 'nilai' => $suhu, 'sat' => '°C', 'ok' => $suhuOk,
             'label' => 'Batas suhu', 'rentang' => "{$sMin}–{$sMax}°C", 'min' => $sMin, 'max' => $sMax],
            ['judul' => 'Kelembapan Ruang Penyimpanan Obat', 'nilai' => $lembap, 'sat' => '%', 'ok' => $lembapOk,
             'label' => 'Batas kelembapan', 'rentang' => "{$hMin}–{$hMax}%", 'min' => $hMin, 'max' => $hMax],
        ];
    @endphp

    <!-- Banner Peringatan Global Jika Sensor Di Luar Batas -->
    @if($terkini && (!$suhuOk || !$lembapOk))
        <div id="alertPeringatanSensor" role="alert" class="p-4 rounded-2xl bg-red-600 text-white shadow-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
            <div class="flex items-start gap-3">
                <span class="text-xl leading-none mt-0.5">⚠️</span>
                <div>
                    <p class="font-bold text-sm">Peringatan! Pembacaan Sensor di Luar Batas Aman</p>
                    <ul class="text-xs text-red-100 mt-1 space-y-0.5 list-disc list-inside">
                        @if(!$suhuOk)
                            <li>Suhu saat ini <b>{{ number_format($suhu, 1) }}°C</b> ({{ $selisih($suhu, $sMin, $sMax, '°C') }}, batas aman {{ $sMin }}–{{ $sMax }}°C).</li>
                        @endif
                        @if(!$lembapOk)
                            <li>Kelembapan saat ini <b>{{ number_format($lembap, 1) }}%</b> ({{ $selisih($lembap, $hMin, $hMax, '%') }}, batas aman {{ $hMin }}–{{ $hMax }}%).</li>
                        @endif
                    </ul>
                </div>
            </div>
            <button type="button" onclick="document.getElementById('alertPeringatanSensor').classList.add('hidden')"
                    class="text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg transition shrink-0">
                Tutup
            </button>
        </div>
    @endif

    <!-- Filter -->
    <form method="GET" action="{{ route('monitoring.index') }}" class="flex flex-wrap items-center gap-3 bg-white border border-gray-100 rounded-2xl px-4 py-3">
        <input type="hidden" name="rentang" value="{{ $rentang }}">
        @if($perangkats->count() > 1)
            <select name="lokasi" onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded-xl text-sm bg-white">
                @foreach($perangkats as $pf)
                    <option value="{{ $pf->id_perangkat }}" @selected($pf->id_perangkat == $perangkat->id_perangkat)>{{ $pf->lokasi }}</option>
                @endforeach
            </select>
        @else
            <span class="text-sm text-gray-700 font-medium">{{ $perangkat->lokasi }}</span>
        @endif
        <input type="date" name="tanggal" value="{{ $tanggal->toDateString() }}" max="{{ now()->toDateString() }}"
               onchange="this.form.submit()" class="px-3 py-2 border border-gray-200 rounded-xl text-sm bg-white">
        <span class="inline-flex items-center gap-2 px-3 py-1.5 bg-emerald-50 text-emerald-700 rounded-full text-xs font-semibold">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> {{ $jumlahSensor }} sensor terhubung
        </span>
        <span class="ml-auto text-xs text-gray-500">
            Pembaruan terakhir
            {{ $terkini ? \Carbon\Carbon::parse($terkini->waktu_catat)->format('d/m/Y H:i:s') : '-' }}
        </span>
    </form>

    <!-- Dua kartu: suhu & kelembapan -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        @foreach($kartu as $k)
            <div class="bg-white rounded-2xl border {{ $k['ok'] ? 'border-gray-100' : 'border-red-400 ring-2 ring-red-100' }} p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-gray-800">{{ $k['judul'] }}</h3>
                        <p class="text-xs text-gray-500">{{ $perangkat->lokasi }}</p>
                    </div>
                    @if($k['nilai'] !== null)
                        <span class="px-2.5 py-1 text-xs font-semibold rounded-lg {{ $k['ok'] ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700 animate-pulse' }}">
                            {{ $k['ok'] ? 'Normal' : 'Peringatan / Bahaya' }}
                        </span>
                    @endif
                </div>

                <div class="mt-3 text-4xl font-bold {{ $k['ok'] ? 'text-gray-900' : 'text-red-600' }}">
                    {{ $k['nilai'] !== null ? number_format($k['nilai'], 1) . $k['sat'] : '–' }}
                </div>
                @if($k['nilai'] !== null)
                    <p class="text-xs mt-1 font-medium {{ $k['ok'] ? 'text-gray-500' : 'text-red-600' }}">
                        {{ $selisih($k['nilai'], $k['min'], $k['max'], $k['sat']) }}
                    </p>
                @else
                    <p class="text-xs mt-1 text-gray-400">Belum ada data. Klik simulasi.</p>
                @endif

                <div class="mt-4 flex items-center justify-between px-3 py-2 rounded-lg text-sm {{ $k['ok'] ? 'bg-emerald-50 text-emerald-800' : 'bg-red-50 text-red-700' }}">
                    <span>{{ $k['label'] }}</span>
                    <span class="font-semibold">{{ $k['rentang'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Grafik + panel batas & form pengaturan -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
        <div class="xl:col-span-2 bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
            <div class="flex flex-wrap items-start justify-between gap-3 mb-4">
                <div>
                    <h3 id="judulGrafik" class="font-semibold text-gray-800">Tren suhu</h3>
                    <p class="text-xs text-gray-500">
                        {{ $perangkat->lokasi }} ·
                        {{ $rentang === 'hari' ? $tanggal->format('d/m/Y') : 'Rata-rata per hari sampai ' . $tanggal->format('d/m/Y') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <!-- pilih metrik -->
                    <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-xs font-medium">
                        <button type="button" data-metrik="suhu"   class="btnMetrik px-3 py-1.5">Suhu</button>
                        <button type="button" data-metrik="lembap" class="btnMetrik px-3 py-1.5">Kelembapan</button>
                    </div>
                    <!-- pilih rentang -->
                    <div class="inline-flex rounded-lg border border-gray-200 overflow-hidden text-xs font-medium">
                        @foreach(['hari' => 'Hari', 'minggu' => 'Minggu', 'bulan' => 'Bulan'] as $kode => $nama)
                            <a href="{{ request()->fullUrlWithQuery(['rentang' => $kode, 'page' => null]) }}"
                               class="px-3 py-1.5 {{ $rentang === $kode ? 'bg-[#0d2e26] text-white' : 'bg-white text-gray-600 hover:bg-gray-50' }}">{{ $nama }}</a>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="relative h-64">
                <canvas id="grafik"></canvas>
                <div id="grafikKosong" class="hidden absolute inset-0 flex items-center justify-center text-sm text-gray-400">
                    Belum ada data pada rentang ini.
                </div>
            </div>

            <div class="mt-4 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-500">
                <span>Terendah: <b id="stMin" class="text-gray-800">–</b></span>
                <span>Tertinggi: <b id="stMax" class="text-gray-800">–</b></span>
                <span>Terkini: <b id="stNow" class="text-gray-800">–</b></span>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5 space-y-5">
            <div>
                <h3 class="font-semibold text-gray-800">Batas & peringatan</h3>
                <p class="text-xs text-gray-500 mb-3">Rentang aman penyimpanan obat</p>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-gray-600">Suhu</span><b>{{ $sMin }}–{{ $sMax }}°C</b></div>
                    <div class="flex justify-between"><span class="text-gray-600">Kelembapan</span><b>{{ $hMin }}–{{ $hMax }}%</b></div>
                </div>

                <div class="mt-4 space-y-3">
                    @if($suhu !== null && !$suhuOk)
                        <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-800">
                            <p class="font-semibold">⚠️ Suhu di luar batas ({{ number_format($suhu, 1) }}°C)</p>
                            <p class="text-xs mt-1">{{ $selisih($suhu, $sMin, $sMax, '°C') }}. Periksa pendingin dan kondisi ruangan.</p>
                        </div>
                    @endif
                    @if($lembap !== null && !$lembapOk)
                        <div class="p-3 rounded-xl bg-red-50 border border-red-200 text-sm text-red-800">
                            <p class="font-semibold">⚠️ Kelembapan di luar batas ({{ number_format($lembap, 1) }}%)</p>
                            <p class="text-xs mt-1">{{ $selisih($lembap, $hMin, $hMax, '%') }}. Obat berisiko lembap atau rusak.</p>
                        </div>
                    @endif
                    @if($terkini && $suhuOk && $lembapOk)
                        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-sm text-emerald-800">
                            Semua pembacaan terakhir berada dalam batas aman.
                        </div>
                    @endif
                    @if(!$terkini)
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-200 text-sm text-gray-500">Belum ada pembacaan sensor.</div>
                    @endif
                </div>
            </div>

            <!-- Form Pengaturan Batas Minimum & Maksimum -->
            <div class="pt-4 border-t border-gray-100">
                <h4 class="font-semibold text-sm text-gray-800">Pengaturan Batas Minimum & Maksimum</h4>
                <p class="text-xs text-gray-500 mt-0.5 mb-3">Atur ambang batas untuk memicu peringatan otomatis.</p>

                <form action="{{ route('monitoring.batas.update') }}" method="POST" class="space-y-3 text-xs">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="id_perangkat" value="{{ $perangkat->id_perangkat }}">

                    <!-- Batas Suhu -->
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 space-y-2">
                        <span class="font-semibold text-gray-700 block">Suhu (°C)</span>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="suhu_min" class="text-gray-500 block mb-1">Minimum (°C)</label>
                                <input id="suhu_min" type="number" step="0.1" name="suhu_min"
                                       value="{{ old('suhu_min', $sMin) }}" required
                                       class="w-full px-2.5 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:border-[#0d2e26]">
                            </div>
                            <div>
                                <label for="suhu_max" class="text-gray-500 block mb-1">Maksimum (°C)</label>
                                <input id="suhu_max" type="number" step="0.1" name="suhu_max"
                                       value="{{ old('suhu_max', $sMax) }}" required
                                       class="w-full px-2.5 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:border-[#0d2e26]">
                            </div>
                        </div>
                    </div>

                    <!-- Batas Kelembapan -->
                    <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 space-y-2">
                        <span class="font-semibold text-gray-700 block">Kelembapan (%)</span>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label for="lembap_min" class="text-gray-500 block mb-1">Minimum (%)</label>
                                <input id="lembap_min" type="number" step="0.1" min="0" max="100" name="lembap_min"
                                       value="{{ old('lembap_min', $hMin) }}" required
                                       class="w-full px-2.5 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:border-[#0d2e26]">
                            </div>
                            <div>
                                <label for="lembap_max" class="text-gray-500 block mb-1">Maksimum (%)</label>
                                <input id="lembap_max" type="number" step="0.1" min="0" max="100" name="lembap_max"
                                       value="{{ old('lembap_max', $hMax) }}" required
                                       class="w-full px-2.5 py-1.5 bg-white border border-gray-200 rounded-lg text-sm text-gray-800 focus:outline-none focus:border-[#0d2e26]">
                            </div>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full bg-[#0d2e26] text-white py-2 rounded-xl font-medium text-sm hover:bg-[#133d33] transition">
                        Simpan Pengaturan Batas
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Riwayat -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-5">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <div>
                <h3 class="font-semibold text-gray-800">Riwayat pengukuran</h3>
                <p class="text-xs text-gray-500">Catatan suhu dan kelembapan dari sensor</p>
            </div>
            <a href="{{ route('monitoring.export', ['lokasi' => $perangkat->id_perangkat]) }}"
               class="bg-[#0d2e26] text-white px-4 py-2 rounded-xl text-sm font-medium hover:bg-[#133d33] transition">Ekspor riwayat</a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="text-xs text-gray-500 uppercase border-b border-gray-100">
                    <tr>
                        <th class="px-3 py-2 font-semibold">Waktu</th>
                        <th class="px-3 py-2 font-semibold">Suhu</th>
                        <th class="px-3 py-2 font-semibold">Kelembapan</th>
                        <th class="px-3 py-2 font-semibold whitespace-nowrap">Batas suhu</th>
                        <th class="px-3 py-2 font-semibold whitespace-nowrap">Batas kelembapan</th>
                        <th class="px-3 py-2 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($riwayat as $r)
                        @php
                            $sOk = $r->suhu >= $sMin && $r->suhu <= $sMax;
                            $hOk = $r->kelembapan >= $hMin && $r->kelembapan <= $hMax;
                        @endphp
                        <tr class="{{ ($sOk && $hOk) ? '' : 'bg-red-50/60' }}">
                            <td class="px-3 py-2.5 whitespace-nowrap text-gray-600">{{ \Carbon\Carbon::parse($r->waktu_catat)->format('d/m/Y H:i:s') }}</td>
                            <td class="px-3 py-2.5 whitespace-nowrap font-medium {{ $sOk ? 'text-gray-800' : 'text-red-600' }}">{{ number_format($r->suhu, 1) }}°C</td>
                            <td class="px-3 py-2.5 whitespace-nowrap font-medium {{ $hOk ? 'text-gray-800' : 'text-red-600' }}">{{ number_format($r->kelembapan, 1) }}%</td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-gray-500">{{ $sMin }}–{{ $sMax }}°C</td>
                            <td class="px-3 py-2.5 whitespace-nowrap text-gray-500">{{ $hMin }}–{{ $hMax }}%</td>
                            <td class="px-3 py-2.5">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ ($sOk && $hOk) ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    {{ ($sOk && $hOk) ? 'Normal' : 'Peringatan' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-8 text-center text-gray-400">Belum ada data pengukuran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
            <span>Menampilkan {{ $riwayat->count() }} dari {{ $riwayat->total() }} pengukuran</span>
            <div class="flex items-center gap-2">
                <span>Halaman {{ $riwayat->currentPage() }} dari {{ max($riwayat->lastPage(), 1) }}</span>
                @if($riwayat->onFirstPage())
                    <span class="px-3 py-1.5 border border-gray-200 rounded-lg text-gray-300">‹</span>
                @else
                    <a href="{{ $riwayat->previousPageUrl() }}" class="px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50">‹</a>
                @endif
                @if($riwayat->hasMorePages())
                    <a href="{{ $riwayat->nextPageUrl() }}" class="px-3 py-1.5 border border-gray-200 rounded-lg hover:bg-gray-50">›</a>
                @else
                    <span class="px-3 py-1.5 border border-gray-200 rounded-lg text-gray-300">›</span>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const D = @json($grafik);
        const META = {
            suhu:   { judul: 'Tren suhu',       sat: '°C', warna: '#0F766E', batas: D.batas.suhu,   data: D.suhu },
            lembap: { judul: 'Tren kelembapan', sat: '%',  warna: '#2563EB', batas: D.batas.lembap, data: D.lembap },
        };
        const n = D.labels.length;
        const garis = (nilai) => Array(n).fill(nilai);

        const chart = new Chart(document.getElementById('grafik'), {
            type: 'line',
            data: { labels: D.labels, datasets: [
                { label: 'Aktual', data: [], borderWidth: 2, tension: 0.3, fill: true, pointRadius: n > 40 ? 0 : 3 },
                { label: 'Batas maksimum', data: [], borderColor: '#D97706', borderDash: [6, 4], borderWidth: 1.5, pointRadius: 0, fill: false },
                { label: 'Batas minimum',  data: [], borderColor: '#D97706', borderDash: [6, 4], borderWidth: 1.5, pointRadius: 0, fill: false },
            ]},
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { ticks: { maxTicksLimit: 8 } } },
            },
        });

        function tampil(kunci) {
            const m = META[kunci];
            const [a, b, c] = chart.data.datasets;
            a.data = m.data; a.borderColor = m.warna; a.backgroundColor = m.warna + '22'; a.pointBackgroundColor = m.warna;
            b.data = garis(m.batas[1]); c.data = garis(m.batas[0]);
            chart.update();

            document.getElementById('judulGrafik').textContent = m.judul;
            document.getElementById('grafikKosong').classList.toggle('hidden', n > 0);

            const f = (v) => v.toFixed(1) + m.sat;
            const ada = m.data.length > 0;
            document.getElementById('stMin').textContent = ada ? f(Math.min(...m.data)) : '–';
            document.getElementById('stMax').textContent = ada ? f(Math.max(...m.data)) : '–';
            document.getElementById('stNow').textContent = ada ? f(m.data[m.data.length - 1]) : '–';

            document.querySelectorAll('.btnMetrik').forEach(t => {
                const aktif = t.dataset.metrik === kunci;
                t.className = 'btnMetrik px-3 py-1.5 ' + (aktif ? 'bg-[#0d2e26] text-white' : 'bg-white text-gray-600 hover:bg-gray-50');
            });
        }
        document.querySelectorAll('.btnMetrik').forEach(t => t.addEventListener('click', () => tampil(t.dataset.metrik)));
        tampil('suhu');
    </script>
@endif
</div>
@endsection