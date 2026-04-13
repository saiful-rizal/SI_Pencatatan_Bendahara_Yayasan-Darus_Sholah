<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItemPembayaran extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode',
        'nama_item',
        'nominal',
        'jenis_item',
        'berlaku_untuk',
        'pengelola',
        'aktif',
    ];

    protected $casts = [
        'nominal' => 'decimal:2',
        'aktif' => 'boolean',
    ];

    public function tagihans()
    {
        return $this->hasMany(Tagihan::class);
    }

    public function detailTransaksis()
    {
        return $this->hasMany(DetailTransaksi::class);
    }
}
