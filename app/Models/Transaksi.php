<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends Model
{
    use SoftDeletes;

    protected $fillable = ['jenis', 'kategori', 'nama_siswa', 'kelas', 'total_bayar', 'tanggal', 'catatan'];

    protected $casts = [
        'tanggal' => 'date',
        'total_bayar' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    public function details()
    {
        return $this->hasMany(DetailTransaksi::class);
    }
}
