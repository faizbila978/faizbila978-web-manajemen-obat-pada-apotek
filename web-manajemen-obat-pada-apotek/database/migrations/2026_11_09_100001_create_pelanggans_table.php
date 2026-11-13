<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggans', function (Blueprint $table) {
            $table->id('id_pelanggan');
            $table->string('nama_pelanggan');
            $table->string('no_hp', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggans');
    }
};
