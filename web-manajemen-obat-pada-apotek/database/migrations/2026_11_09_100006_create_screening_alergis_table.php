<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screening_alergis', function (Blueprint $table) {
            $table->id('id_screening');
            $table->foreignId('id_pelanggan')->constrained('pelanggans', 'id_pelanggan')->onDelete('cascade');
            $table->foreignId('id_obat')->constrained('obats', 'id_obat')->onDelete('cascade');
            $table->foreignId('id_alergi')->nullable()->constrained('alergi_pelanggans', 'id_alergi')->onDelete('set null');
            $table->unsignedBigInteger('id_pengelola')->nullable();
            $table->dateTime('tgl_screening');
            $table->text('hasil');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screening_alergis');
    }
};
