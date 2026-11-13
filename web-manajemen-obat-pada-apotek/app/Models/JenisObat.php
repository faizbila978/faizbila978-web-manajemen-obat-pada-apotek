<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisObat extends Model
{
    protected $table = 'jenis_obats';
    protected $primaryKey = 'id_jenis';
    protected $fillable = ['nama'];
}
