<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategoris', function (Blueprint $table) {
            $table->id('id_kategori');
            $table->string('nama')->unique();
            $table->timestamps();
        });

        Schema::create('jenis_obats', function (Blueprint $table) {
            $table->id('id_jenis');
            $table->string('nama')->unique();
            $table->timestamps();
        });

        // Isi awal: pilihan yang sebelumnya tertulis manual di form + semua nilai yang sudah dipakai obat
        $this->isi('kategoris', 'kategori', [
            'Obat Pencernaan', 'Obat Demam', 'Obat Batuk', 'Obat Flu', 'Obat Pereda Nyeri', 'Obat Alergi',
            'Obat Peradangan', 'Vitamin & Suplemen', 'Obat Luar', 'Obat Tetes', 'Obat Luka',
        ]);
        $this->isi('jenis_obats', 'jenis', [
            'Tablet', 'Sirup', 'Kapsul', 'Bubuk', 'Tetes', 'Salep', 'Larutan', 'Inhalasi',
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_obats');
        Schema::dropIfExists('kategoris');
    }

    private function isi(string $tabel, string $kolomObat, array $bawaan): void
    {
        $dipakai = Schema::hasTable('obats') && Schema::hasColumn('obats', $kolomObat)
            ? DB::table('obats')->whereNotNull($kolomObat)->where($kolomObat, '!=', '')->distinct()->pluck($kolomObat)->all()
            : [];

        $now = now();
        $sudah = [];
        foreach (array_merge($bawaan, $dipakai) as $nama) {
            $nama = trim((string) $nama);
            $kunci = mb_strtolower($nama);
            if ($nama === '' || isset($sudah[$kunci])) {
                continue;
            }
            $sudah[$kunci] = true;
            DB::table($tabel)->insert(['nama' => $nama, 'created_at' => $now, 'updated_at' => $now]);
        }
    }
};
