<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obats', function (Blueprint $table) {
    $table->id('id_obat');
    $table->string('nama_obat');
    $table->string('jenis');
    $table->string('kategori')->nullable();
    $table->string('alergi')->nullable(); // <-- TAMBAHKAN BARIS INI
    $table->integer('stok')->default(0);
    $table->decimal('harga', 12, 2)->default(0);
    $table->timestamps();
});
    }

    public function down(): void
    {
        Schema::dropIfExists('obats');
    }
};
