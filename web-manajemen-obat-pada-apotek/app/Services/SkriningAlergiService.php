<?php

namespace App\Services;

use App\Models\Medicine;
use Illuminate\Support\Collection;

/**
 * Logika skrining: membandingkan alergi pelanggan dengan obat yang dipilih.
 * Hasil: aman | peringatan (ada kecocokan) | perlu_verifikasi (butuh pemeriksaan apoteker).
 */
class SkriningAlergiService
{
    // Isian yang artinya "tidak punya alergi"
    public const TIDAK_ADA = ['tidak ada', 'tidak', 'tidak ada alergi', 'tdk ada', 'tidak punya', 'nihil', 'none', 'no', '-', '0'];

    // Golongan obat. 'lintas' = tingkat peringatan bila alergi menyebut salah satu anggota golongan:
    //   peringatan -> seluruh golongan dihindari | verifikasi -> perlu cek apoteker | tidak -> hanya zat yang sama
    private const GOLONGAN = [
        'penisilin' => [
            'kelas' => ['penisilin', 'penicillin'],
            'anggota' => ['amoxicillin', 'amoksisilin', 'amoxycillin', 'ampicillin', 'ampisilin', 'cloxacillin', 'dicloxacillin',
                'flucloxacillin', 'piperacillin', 'benzylpenicillin', 'phenoxymethylpenicillin', 'benzathine penicillin', 'penisilin', 'penicillin'],
            'lintas' => 'peringatan',
        ],
        'sefalosporin' => [
            'kelas' => ['sefalosporin', 'cephalosporin'],
            'anggota' => ['cefixime', 'cefadroxil', 'cefalexin', 'cephalexin', 'ceftriaxone', 'cefuroxime', 'cefpodoxime',
                'cefotaxime', 'ceftazidime', 'cefazolin', 'cefepime', 'cefaclor', 'cefdinir', 'cefoperazone', 'sefiksim', 'sefadroksil'],
            'lintas' => 'peringatan',
        ],
        'sulfa' => [
            'kelas' => ['sulfa', 'sulfonamid', 'sulfonamide'],
            'anggota' => ['sulfamethoxazole', 'cotrimoxazole', 'co-trimoxazole', 'sulfadiazine', 'sulfasalazine', 'sulfacetamide', 'kotrimoksazol'],
            'lintas' => 'peringatan',
        ],
        'nsaid' => [
            'kelas' => ['nsaid', 'aint'],
            'anggota' => ['aspirin', 'asam asetilsalisilat', 'acetylsalicylic acid', 'ibuprofen', 'diclofenac', 'diklofenak', 'naproxen',
                'mefenamic acid', 'asam mefenamat', 'ketoprofen', 'piroxicam', 'meloxicam', 'indomethacin', 'ketorolac', 'etoricoxib',
                'celecoxib', 'aceclofenac', 'nimesulide'],
            'lintas' => 'verifikasi',
        ],
        'parasetamol' => [
            'kelas' => ['parasetamol', 'paracetamol', 'acetaminophen'],
            'anggota' => ['parasetamol', 'paracetamol', 'acetaminophen'],
            'lintas' => 'peringatan',
        ],
        'makrolida' => [
            'kelas' => ['makrolida', 'macrolide'],
            'anggota' => ['azithromycin', 'azitromisin', 'erythromycin', 'eritromisin', 'clarithromycin', 'klaritromisin', 'roxithromycin'],
            'lintas' => 'peringatan',
        ],
        'kuinolon' => [
            'kelas' => ['kuinolon', 'quinolone', 'fluoroquinolone'],
            'anggota' => ['ciprofloxacin', 'siprofloksasin', 'levofloxacin', 'ofloxacin', 'moxifloxacin', 'norfloxacin'],
            'lintas' => 'peringatan',
        ],
        'tetrasiklin' => [
            'kelas' => ['tetrasiklin', 'tetracycline'],
            'anggota' => ['tetracycline', 'doxycycline', 'doksisiklin', 'minocycline'],
            'lintas' => 'peringatan',
        ],
    ];

    /** Memecah isian "A, B; C" menjadi daftar alergi huruf kecil (tanpa "tidak ada"). */
    public function daftarAlergi(?string $teks): array
    {
        $hasil = [];
        foreach (preg_split('/[,;\n]+/', (string) $teks) as $bagian) {
            $a = $this->bersih($bagian);
            if ($a === '' || in_array($a, self::TIDAK_ADA, true)) {
                continue;
            }
            $hasil[$a] = true;
        }
        return array_map('strval', array_keys($hasil));
    }

    /**
     * @param  string[] $alergi  daftar alergi (hasil daftarAlergi)
     * @return array{status:string, temuan:array, kandungan:array}
     */
    public function periksa(Medicine $obat, array $alergi): array
    {
        $obat->loadMissing('kandungan');
        $kandunganAsli = $obat->kandungan->pluck('nama_kandungan')->filter()->values()->all();
        $zat = array_values(array_filter(array_map(fn ($z) => $this->bersih($z), $kandunganAsli)));
        $namaObat = $this->bersih((string) $obat->nama_obat);
        $efekObat = array_values(array_filter(
            array_map(fn ($x) => $this->bersih($x), preg_split('/[,;\/]+/', (string) $obat->alergi)),
            fn ($x) => $x !== '' && $x !== '-'
        ));

        $temuan = [];
        $tambah = function (string $a, string $alasan, string $tingkat) use (&$temuan) {
            $temuan[$a . '|' . $alasan] = ['alergi' => $a, 'alasan' => $alasan, 'tingkat' => $tingkat];
        };

        foreach ($alergi as $a) {
            // 1) data alergi/reaksi yang dicatat pada obat
            foreach ($efekObat as $e) {
                if ($this->cocok($a, $e)) {
                    $tambah($a, "Data obat mencatat \"{$e}\" sebagai reaksi/alergi yang mungkin timbul.", 'peringatan');
                }
            }

            // 2) zat aktif
            foreach ($zat as $z) {
                if ($this->cocok($a, $z)) {
                    $tambah($a, "Obat mengandung {$z}.", 'peringatan');
                } elseif ($this->mirip($a, $z)) {
                    $tambah($a, "Alergi \"{$a}\" mirip dengan zat aktif {$z}. Pastikan ejaan dan zat yang dimaksud.", 'verifikasi');
                }
            }

            // 3) nama obat
            if (mb_strlen($a) >= 4 && str_contains($namaObat, $a)) {
                $tambah($a, 'Nama obat memuat kata yang sama dengan alergi pelanggan.', 'peringatan');
            }

            // 4) golongan obat
            foreach (self::GOLONGAN as $namaGol => $g) {
                $termKelas = $this->anggota($a, $g['kelas']);
                $termAnggota = $this->anggota($a, $g['anggota']);
                if (!$termKelas && !$termAnggota) {
                    continue;
                }
                $tingkat = $termKelas ? 'peringatan' : $g['lintas'];
                if ($tingkat === 'tidak') {
                    continue;
                }
                foreach ($zat as $z) {
                    if ($this->cocok($a, $z)) {
                        continue; // sudah tercatat pada langkah 2
                    }
                    if ($this->anggota($z, $g['anggota'])) {
                        $alasan = $termKelas
                            ? "Termasuk golongan {$namaGol} ({$z})."
                            : "Satu golongan {$namaGol} dengan alergi yang tercatat ({$z}).";
                        $tambah($a, $alasan, $tingkat);
                        break;
                    }
                }
            }
        }

        $tingkatAda = array_column($temuan, 'tingkat');
        if (in_array('peringatan', $tingkatAda, true)) {
            $status = 'peringatan';
        } elseif (in_array('verifikasi', $tingkatAda, true)) {
            $status = 'perlu_verifikasi';
        } else {
            $status = 'aman';
        }

        // Pelanggan punya alergi, tetapi obat tidak punya data zat aktif -> keamanan tidak bisa dipastikan
        if ($status === 'aman' && !empty($alergi) && empty($zat) && empty($efekObat)) {
            $status = 'perlu_verifikasi';
            $temuan['kosong'] = [
                'alergi' => 'semua alergi pelanggan',
                'alasan' => 'Data zat aktif obat ini belum tersedia, sehingga keamanannya tidak dapat dipastikan sistem.',
                'tingkat' => 'verifikasi',
            ];
        }

        return ['status' => $status, 'temuan' => array_values($temuan), 'kandungan' => $kandunganAsli];
    }

    /** Obat sekategori, stok ada, dan lolos skrining (maksimal $batas). */
    public function alternatif(Medicine $obat, array $alergi, int $batas = 5): Collection
    {
        if (empty($obat->kategori)) {
            return collect();
        }

        return Medicine::with('kandungan')
            ->where('kategori', $obat->kategori)
            ->where('id_obat', '!=', $obat->id_obat)
            ->where('stok', '>', 0)
            ->orderBy('nama_obat')
            ->limit(60)
            ->get()
            ->filter(fn ($m) => $this->periksa($m, $alergi)['status'] === 'aman')
            ->take($batas)
            ->values();
    }

    // ---------- helper pencocokan ----------

    private function bersih(string $s): string
    {
        return preg_replace('/\s+/', ' ', mb_strtolower(trim($s)));
    }

    // Buang dosis dan nama garam agar "Salbutamol Sulphate 2mg" dibandingkan sebagai "salbutamol"
    private function inti(string $s): string
    {
        $s = preg_replace('/\d+([.,]\d+)?\s*(mg|mcg|g|ml|iu|%)/u', '', $s);
        $s = preg_replace('/\b(sulfate|sulphate|sulfat|hydrochloride|hcl|maleate|tartrate|besylate|succinate|phosphate|acetate|citrate|trihydrate|dihydrate|monohydrate|anhydrous|sodium|potassium)\b/u', '', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    private function cocok(string $a, string $b): bool
    {
        $a = $this->inti($a);
        $b = $this->inti($b);
        if ($a === '' || $b === '') {
            return false;
        }
        if ($a === $b) {
            return true;
        }
        if (min(mb_strlen($a), mb_strlen($b)) < 4) {
            return false;
        }
        return str_contains($a, $b) || str_contains($b, $a);
    }

    // Salah ketik kecil (mis. "amoxcillin" vs "amoxicillin")
    private function mirip(string $a, string $b): bool
    {
        $a = $this->inti($a);
        $b = $this->inti($b);
        if (mb_strlen($a) < 6 || mb_strlen($b) < 6 || $a === $b) {
            return false;
        }
        if (abs(strlen($a) - strlen($b)) > 2 || strlen($a) > 250 || strlen($b) > 250) {
            return false;
        }
        $batas = mb_strlen($a) >= 9 ? 2 : 1;
        return levenshtein($a, $b) <= $batas;
    }

    private function anggota(string $teks, array $daftar): bool
    {
        $teks = $this->inti($teks);
        if ($teks === '') {
            return false;
        }
        foreach ($daftar as $m) {
            if ($teks === $m || (mb_strlen($m) >= 5 && str_contains($teks, $m))) {
                return true;
            }
        }
        return false;
    }
}
