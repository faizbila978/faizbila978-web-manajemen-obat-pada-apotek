<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_sensors', function (Blueprint $table) {
            $table->id('id_sensor');
            $table->foreignId('id_perangkat')->constrained('perangkats', 'id_perangkat')->onDelete('cascade');
            $table->float('suhu');
            $table->float('kelembapan');
            $table->dateTime('waktu_catat');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_sensors');
    }
};
