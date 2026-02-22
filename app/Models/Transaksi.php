<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    protected $fillable = ['jenis', 'kategori', 'nama_siswa', 'kelas', 'total_bayar', 'tanggal', 'catatan'];

    protected $casts = [
        'tanggal' => 'date',
        'total_bayar' => 'decimal:2'
    ];

    public function details()
    {
        return $this->hasMany(DetailTransaksi::class);
    }
}
