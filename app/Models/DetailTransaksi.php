<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailTransaksi extends Model
{
    protected $fillable = ['transaksi_id', 'nama_item', 'harga', 'jumlah', 'subtotal'];

    protected $casts = [
        'harga' => 'decimal:2',
        'subtotal' => 'decimal:2'
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }
}
