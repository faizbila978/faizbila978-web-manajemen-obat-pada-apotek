<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alergi_pelanggans', function (Blueprint $table) {
            $table->id('id_alergi');
            $table->foreignId('id_pelanggan')->constrained('pelanggans', 'id_pelanggan')->onDelete('cascade');
            $table->string('nama_alergi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alergi_pelanggans');
    }
};
