<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Menambah kolom kategori & alergi ke tabel obats (hanya jika belum ada).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obats', function (Blueprint $table) {
            if (!Schema::hasColumn('obats', 'kategori')) {
                $table->string('kategori')->nullable()->after('jenis');
            }
            if (!Schema::hasColumn('obats', 'alergi')) {
                $table->string('alergi', 500)->nullable()->after('kategori');
            }
        });
    }

    public function down(): void
    {
        Schema::table('obats', function (Blueprint $table) {
            $table->dropColumn(['kategori', 'alergi']);
        });
    }
};