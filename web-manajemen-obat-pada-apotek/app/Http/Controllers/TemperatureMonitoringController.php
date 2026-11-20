<?php

namespace App\Http\Controllers;

use App\Models\DataSensor;
use App\Models\PengaturanBatasan;
use App\Models\Perangkat;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TemperatureMonitoringController extends Controller
{
    // Pilih perangkat dari ?lokasi=, kalau tidak ada pakai perangkat pertama
    private function pilihPerangkat($perangkats, $id)
    {
        return $perangkats->firstWhere('id_perangkat', (int) $id) ?? $perangkats->first();
    }

    private function batas(Perangkat $p): array
    {
        $b = $p->batasan;
        return [
            'suhu'   => [(float) ($b->suhu_minimal ?? 15),   (float) ($b->suhu_maksimal ?? 30)],
            'lembap' => [(float) ($b->lembap_minimal ?? 40), (float) ($b->lembap_maksimal ?? 60)],
        ];
    }

    public function index(Request $request)
    {
        $perangkats = Perangkat::with('batasan')->orderBy('id_perangkat')->get();
        $perangkat  = $this->pilihPerangkat($perangkats, $request->input('lokasi'));

        if (!$perangkat) {
            return view('monitoring.index', ['perangkat' => null, 'perangkats' => $perangkats]);
        }

        $batas   = $this->batas($perangkat);
        $rentang = in_array($request->input('rentang'), ['hari', 'minggu', 'bulan'], true)
            ? $request->input('rentang') : 'hari';

        try {
            $tanggal = $request->filled('tanggal')
                ? Carbon::createFromFormat('Y-m-d', $request->input('tanggal'))->startOfDay()
                : Carbon::today();
        } catch (\Throwable $e) {
            $tanggal = Carbon::today();
        }

        $dasar = DataSensor::where('id_perangkat', $perangkat->id_perangkat);

        // ---- Data grafik ----
        if ($rentang === 'hari') {
            $rows = (clone $dasar)
                ->whereDate('waktu_catat', $tanggal->toDateString())
                ->orderBy('waktu_catat')->limit(1000)
                ->get(['suhu', 'kelembapan', 'waktu_catat']);

            $grafik = [
                'labels' => $rows->map(fn ($r) => Carbon::parse($r->waktu_catat)->format('H:i'))->values(),
                'suhu'   => $rows->pluck('suhu')->map(fn ($v) => round($v, 1))->values(),
                'lembap' => $rows->pluck('kelembapan')->map(fn ($v) => round($v, 1))->values(),
            ];
        } else {
            $jumlahHari = $rentang === 'minggu' ? 7 : 30;
            $dari = $tanggal->copy()->subDays($jumlahHari - 1)->toDateString();

            $rows = (clone $dasar)
                ->whereDate('waktu_catat', '>=', $dari)
                ->whereDate('waktu_catat', '<=', $tanggal->toDateString())
                ->selectRaw('DATE(waktu_catat) as tgl, AVG(suhu) as suhu, AVG(kelembapan) as kelembapan')
                ->groupByRaw('DATE(waktu_catat)')
                ->orderByRaw('DATE(waktu_catat)')
                ->get();

            $grafik = [
                'labels' => $rows->map(fn ($r) => Carbon::parse($r->tgl)->format('d/m'))->values(),
                'suhu'   => $rows->pluck('suhu')->map(fn ($v) => round($v, 1))->values(),
                'lembap' => $rows->pluck('kelembapan')->map(fn ($v) => round($v, 1))->values(),
            ];
        }
        $grafik['batas'] = $batas;

        // ---- Pembacaan terbaru (untuk kartu & peringatan) ----
        $terkini = (clone $dasar)->orderBy('waktu_catat', 'desc')->first();

        // ---- Riwayat (10 per halaman) ----
        $riwayat = (clone $dasar)->orderBy('waktu_catat', 'desc')->orderBy('id_sensor', 'desc')
            ->paginate(10)->withQueryString();

        return view('monitoring.index', [
            'perangkats'   => $perangkats,
            'perangkat'    => $perangkat,
            'batas'        => $batas,
            'rentang'      => $rentang,
            'tanggal'      => $tanggal,
            'grafik'       => $grafik,
            'terkini'      => $terkini,
            'riwayat'      => $riwayat,
            'jumlahSensor' => $perangkats->where('status', 'Aktif')->count(),
        ]);
    }

    // Simpan perubahan batas minimum & maksimum suhu dan kelembapan
    public function updateBatas(Request $request)
    {
        $validated = $request->validate([
            'id_perangkat' => ['required', 'integer', 'exists:perangkats,id_perangkat'],
            'suhu_min'     => ['required', 'numeric', 'lt:suhu_max'],
            'suhu_max'     => ['required', 'numeric', 'gt:suhu_min'],
            'lembap_min'   => ['required', 'numeric', 'min:0', 'max:100', 'lt:lembap_max'],
            'lembap_max'   => ['required', 'numeric', 'min:0', 'max:100', 'gt:lembap_min'],
        ], [
            'suhu_min.lt'   => 'Batas minimum suhu harus lebih kecil dari batas maksimum suhu.',
            'suhu_max.gt'   => 'Batas maksimum suhu harus lebih besar dari batas minimum suhu.',
            'lembap_min.lt' => 'Batas minimum kelembapan harus lebih kecil dari batas maksimum kelembapan.',
            'lembap_max.gt' => 'Batas maksimum kelembapan harus lebih besar dari batas minimum kelembapan.',
        ]);

        PengaturanBatasan::updateOrCreate(
            ['id_perangkat' => $validated['id_perangkat']],
            [
                'suhu_minimal'    => $validated['suhu_min'],
                'suhu_maksimal'   => $validated['suhu_max'],
                'lembap_minimal'  => $validated['lembap_min'],
                'lembap_maksimal' => $validated['lembap_max'],
            ]
        );

        return redirect()
            ->route('monitoring.index', ['lokasi' => $validated['id_perangkat']])
            ->with('success', 'Pengaturan batas minimum & maksimum berhasil diperbarui.');
    }

    // Unduh seluruh riwayat sebagai CSV (bisa dibuka di Excel)
    public function export(Request $request)
    {
        $perangkats = Perangkat::with('batasan')->orderBy('id_perangkat')->get();
        $perangkat  = $this->pilihPerangkat($perangkats, $request->input('lokasi'));
        abort_if(!$perangkat, 404, 'Belum ada perangkat.');

        $batas = $this->batas($perangkat);
        $nama  = 'riwayat-monitoring-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($perangkat, $batas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // agar Excel membaca UTF-8
            fputcsv($out, ['Waktu', 'Suhu (C)', 'Kelembapan (%)', 'Status'], ',', '"', '');

            DataSensor::where('id_perangkat', $perangkat->id_perangkat)
                ->orderBy('waktu_catat', 'desc')->orderBy('id_sensor', 'desc')
                ->chunk(1000, function ($rows) use ($out, $batas) {
                    foreach ($rows as $r) {
                        $aman = $r->suhu >= $batas['suhu'][0] && $r->suhu <= $batas['suhu'][1]
                             && $r->kelembapan >= $batas['lembap'][0] && $r->kelembapan <= $batas['lembap'][1];
                        fputcsv($out, [
                            Carbon::parse($r->waktu_catat)->format('Y-m-d H:i:s'),
                            $r->suhu, $r->kelembapan, $aman ? 'Normal' : 'Bahaya',
                        ], ',', '"', '');
                    }
                });
            fclose($out);
        }, $nama, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Simulasi data sensor
    public function simulateSensor(Request $request)
    {
        $perangkats = Perangkat::all();

        if ($perangkats->isEmpty()) {
            $perangkat = Perangkat::create([
                'nama_perangkat' => 'Sensor ESP32 - Gudang Utama',
                'lokasi' => 'Ruang Penyimpanan Obat A',
                'status' => 'Aktif',
            ]);

            PengaturanBatasan::create([
                'id_perangkat' => $perangkat->id_perangkat,
                'suhu_minimal' => 15.0,
                'suhu_maksimal' => 30.0,
                'lembap_minimal' => 40.0,
                'lembap_maksimal' => 60.0,
            ]);

            $perangkats = [$perangkat];
        }

        foreach ($perangkats as $p) {
            DataSensor::create([
                'id_perangkat' => $p->id_perangkat,
                'suhu' => rand(140, 320) / 10,
                'kelembapan' => rand(450, 650) / 10,
                'waktu_catat' => Carbon::now(),
            ]);
        }

        return redirect()->route('monitoring.index')->with('success', 'Simulasi data sensor IoT berhasil diperbarui!');
    }
}