<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PenjualanDetail extends Model
{
    protected $table = 'penjualan_details';
    protected $primaryKey = 'id_detail';

    protected $fillable = ['id_penjualan', 'id_obat', 'nama_obat', 'harga', 'jumlah', 'subtotal', 'id_screening'];

    public function penjualan()
    {
        return $this->belongsTo(Penjualan::class, 'id_penjualan', 'id_penjualan');
    }
}
