<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_batasans', function (Blueprint $table) {
            $table->id('id_batasan');
            $table->foreignId('id_perangkat')->constrained('perangkats', 'id_perangkat')->onDelete('cascade');
            $table->float('suhu_minimal')->default(15.0);
            $table->float('suhu_maksimal')->default(25.0);
            $table->float('lembap_minimal')->default(40.0);
            $table->float('lembap_maksimal')->default(60.0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_batasans');
    }
};
