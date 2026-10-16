<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineBatch extends Model {
    use HasFactory;

    protected $fillable = [
        'medicine_id', 
        'batch_number', 
        'initial_stock', 
        'current_stock', 
        'expired_date', 
        'type', 
        'description'
    ];

    public function medicine() {
        return $this->belongsTo(Medicine::class);
    }

    // Scope untuk mendeteksi obat yang mendekati expired (misal < 60 hari)
    public function scopeNearExpired($query) {
        return $query->where('expired_date', '<=', now()->addDays(60))
                     ->where('current_stock', '>', 0);
    }
}