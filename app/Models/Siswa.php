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

    public function getKelasAttribute($value)
    {
        return self::normalisasiKelasValue($value);
    }

    public function setKelasAttribute($value): void
    {
        $this->attributes['kelas'] = self::normalisasiKelasValue($value);
    }

    public function tagihans()
    {
        return $this->hasMany(Tagihan::class);
    }

    public function getStatusTagihanAttribute(): string
    {
        $tagihans = $this->relationLoaded('tagihans')
            ? $this->tagihans
            : $this->tagihans()->withSum('potongans as total_potongan', 'nominal_potongan')
                ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
                ->get();

        if ($tagihans->isEmpty()) {
            return 'belum ada tagihan';
        }

        $totalSisa = $tagihans->sum(function ($tagihan) {
            $potongan = isset($tagihan->total_potongan)
                ? (float) $tagihan->total_potongan
                : $tagihan->totalPotongan();
            $pembayaran = isset($tagihan->total_pembayaran)
                ? (float) $tagihan->total_pembayaran
                : $tagihan->totalPembayaran();

            return max(0, (float) $tagihan->nominal_awal - $potongan - $pembayaran);
        });

        return $totalSisa > 0 ? 'belum lunas' : 'lunas';
    }

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }

    private static function normalisasiKelasValue($kelas): ?string
    {
        if ($kelas === null) {
            return null;
        }

        $kelas = preg_replace('/\s+/', ' ', trim((string) $kelas)) ?? '';

        if ($kelas === '') {
            return null;
        }

        if (strcasecmp($kelas, 'lulus') === 0) {
            return 'Lulus';
        }

        if (!preg_match('/^(13|12|11|10|XIII|XII|XI|X)(.*)$/i', $kelas, $match)) {
            return $kelas;
        }

        $prefix = strtoupper($match[1]);
        $suffix = trim((string) ($match[2] ?? ''));
        $numeric = match ($prefix) {
            'X' => '10',
            'XI' => '11',
            'XII' => '12',
            'XIII' => '13',
            default => $prefix,
        };

        return $numeric . ($suffix !== '' ? ' ' . ltrim($suffix, '-/ ') : '');
    }
}
