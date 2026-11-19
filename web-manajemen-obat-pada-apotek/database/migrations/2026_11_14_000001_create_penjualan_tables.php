<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1) Tambahan kolom pada riwayat skrining
        Schema::table('screening_alergis', function (Blueprint $table) {
            if (!Schema::hasColumn('screening_alergis', 'status')) {
                $table->string('status', 30)->default('aman');          // aman | peringatan | perlu_verifikasi
            }
            if (!Schema::hasColumn('screening_alergis', 'alergi_input')) {
                $table->text('alergi_input')->nullable();               // daftar alergi yang diperiksa
            }
            if (!Schema::hasColumn('screening_alergis', 'temuan')) {
                $table->text('temuan')->nullable();                     // JSON: detail kecocokan
            }
            if (!Schema::hasColumn('screening_alergis', 'disetujui_oleh')) {
                $table->string('disetujui_oleh')->nullable();           // nama apoteker yang menyetujui
            }
            if (!Schema::hasColumn('screening_alergis', 'catatan_apoteker')) {
                $table->text('catatan_apoteker')->nullable();
            }
            if (!Schema::hasColumn('screening_alergis', 'id_penjualan')) {
                $table->unsignedBigInteger('id_penjualan')->nullable()->index(); // terisi setelah dibayar
            }
        });

        // riwayat lama: samakan status dengan teks hasil yang sudah ada
        DB::table('screening_alergis')->where('hasil', 'like', 'TIDAK AMAN%')->update(['status' => 'peringatan']);

        // 2) Transaksi penjualan
        Schema::create('penjualans', function (Blueprint $table) {
            $table->id('id_penjualan');
            $table->string('no_transaksi')->unique();
            $table->foreignId('id_pelanggan')->constrained('pelanggans', 'id_pelanggan')->onDelete('cascade');
            $table->unsignedBigInteger('id_pengelola')->nullable();     // petugas yang login
            $table->decimal('total', 14, 2);
            $table->string('metode_bayar', 20);
            $table->decimal('dibayar', 14, 2);
            $table->decimal('kembalian', 14, 2)->default(0);
            $table->dateTime('tgl_transaksi');
            $table->timestamps();
        });

        Schema::create('penjualan_details', function (Blueprint $table) {
            $table->id('id_detail');
            $table->foreignId('id_penjualan')->constrained('penjualans', 'id_penjualan')->onDelete('cascade');
            $table->foreignId('id_obat')->nullable()->constrained('obats', 'id_obat')->nullOnDelete();
            $table->string('nama_obat');                                // salinan nama saat transaksi
            $table->decimal('harga', 12, 2);                            // harga saat transaksi
            $table->unsignedInteger('jumlah');
            $table->decimal('subtotal', 14, 2);
            $table->unsignedBigInteger('id_screening')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_details');
        Schema::dropIfExists('penjualans');

        Schema::table('screening_alergis', function (Blueprint $table) {
            foreach (['status', 'alergi_input', 'temuan', 'disetujui_oleh', 'catatan_apoteker', 'id_penjualan'] as $kolom) {
                if (Schema::hasColumn('screening_alergis', $kolom)) {
                    $table->dropColumn($kolom);
                }
            }
        });
    }
};
