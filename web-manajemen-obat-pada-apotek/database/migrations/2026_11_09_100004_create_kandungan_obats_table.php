<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kandungan_obats', function (Blueprint $table) {
            $table->id('id_kandungan');
            $table->foreignId('id_obat')->constrained('obats', 'id_obat')->onDelete('cascade');
            $table->string('nama_kandungan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kandungan_obats');
    }
};
