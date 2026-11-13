<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicine_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_obat')->constrained('obats', 'id_obat')->onDelete('cascade');
            $table->string('batch_number');
            $table->integer('initial_stock');
            $table->integer('current_stock');
            $table->date('expired_date');
            $table->string('type');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicine_batches');
    }
};
