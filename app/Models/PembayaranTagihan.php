<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PembayaranTagihan extends Model
{
    use HasFactory;

    protected $fillable = [
        'tagihan_id',
        'tanggal_bayar',
        'nominal_bayar',
        'metode_bayar',
        'catatan',
    ];

    protected $casts = [
        'tanggal_bayar' => 'date',
        'nominal_bayar' => 'decimal:2',
    ];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class);
    }
}
