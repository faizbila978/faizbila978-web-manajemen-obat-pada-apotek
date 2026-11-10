<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Medicine extends Model {
    use HasFactory;

    protected $table = 'obats';
    protected $primaryKey = 'id_obat';

    protected $fillable = [
        'nama_obat', 'jenis', 'kategori', 'alergi', 'stok', 'harga' // <-- Pastikan 'kategori' ada di fillable
    ];

    public function batches() {
        return $this->hasMany(MedicineBatch::class, 'id_obat', 'id_obat');
    }

    // Relasi ke kandungan obat
    public function kandungan() {
        return $this->hasMany(KandunganObat::class, 'id_obat', 'id_obat');
    }
}