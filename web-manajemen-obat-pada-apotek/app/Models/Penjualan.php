<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    protected $table = 'penjualans';
    protected $primaryKey = 'id_penjualan';

    protected $fillable = [
        'no_transaksi', 'id_pelanggan', 'id_pengelola', 'total',
        'metode_bayar', 'dibayar', 'kembalian', 'tgl_transaksi',
    ];

    protected $casts = ['tgl_transaksi' => 'datetime'];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function details()
    {
        return $this->hasMany(PenjualanDetail::class, 'id_penjualan', 'id_penjualan');
    }
}
