<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    use HasFactory;

    protected $table = 'pelanggans'; // Sesuaikan jika nama tabel database Anda berbeda
    protected $primaryKey = 'id_pelanggan'; // Sesuaikan jika menggunakan id standar
    
    protected $fillable = [
        'nama_pelanggan',
    ];

    public function alergis()
    {
        return $this->hasMany(AlergiPelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function screenings()
    {
        return $this->hasMany(ScreeningAlergi::class, 'id_pelanggan', 'id_pelanggan')->latest('tgl_screening');
    }
}