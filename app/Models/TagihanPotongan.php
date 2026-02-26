<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagihanPotongan extends Model
{
    use HasFactory;

    protected $fillable = [
        'tagihan_id',
        'tanggal_potongan',
        'keterangan',
        'nominal_potongan',
    ];

    protected $casts = [
        'tanggal_potongan' => 'date',
        'nominal_potongan' => 'decimal:2',
    ];

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class);
    }
}
