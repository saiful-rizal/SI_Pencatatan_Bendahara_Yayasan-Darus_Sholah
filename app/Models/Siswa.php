<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Siswa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nis',
        'nama',
        'jenis_kelamin',
        'kelas',
        'angkatan',
        'kategori',
        'status',
    ];

    public function tagihans()
    {
        return $this->hasMany(Tagihan::class);
    }
}
