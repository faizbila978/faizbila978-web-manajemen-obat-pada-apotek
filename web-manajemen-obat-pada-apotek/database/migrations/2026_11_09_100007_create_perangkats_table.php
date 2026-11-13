<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perangkats', function (Blueprint $table) {
            $table->id('id_perangkat');
            $table->string('nama_perangkat');
            $table->string('lokasi');
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perangkats');
    }
};
