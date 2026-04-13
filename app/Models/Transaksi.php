<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaksi extends Model
{
    use SoftDeletes;

    private const AUTO_PEMBAYARAN_TAGIHAN_PATTERN = '/^\[AUTO-PEMBAYARAN-TAGIHAN#(\d+)\]$/';

    protected $fillable = [
        'jenis',
        'kategori',
        'siswa_id',
        'pembayaran_tagihan_id',
        'nama_siswa',
        'kelas',
        'total_bayar',
        'tanggal',
        'catatan',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'siswa_id' => 'integer',
        'pembayaran_tagihan_id' => 'integer',
        'tanggal' => 'date',
        'total_bayar' => 'decimal:2',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'deleted_at' => 'datetime',
    ];

    public function getKelasAttribute($value)
    {
        return self::normalisasiKelasValue($value);
    }

    public function setKelasAttribute($value): void
    {
        $this->attributes['kelas'] = self::normalisasiKelasValue($value);
    }

    public function details()
    {
        return $this->hasMany(DetailTransaksi::class);
    }

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function pembayaranTagihan()
    {
        return $this->belongsTo(PembayaranTagihan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getCatatanTujuanDanaAttribute(): string
    {
        $catatan = trim((string) ($this->catatan ?? ''));

        if ($catatan === '') {
            return '-';
        }

        $pembayaranId = self::extractAutoPembayaranId($catatan);
        if ($pembayaranId !== null) {
            $tujuanPembayaran = $this->buildTujuanPembayaranTagihan();
            if ($tujuanPembayaran !== null) {
                return $tujuanPembayaran;
            }

            return 'Pembayaran Tagihan Otomatis #' . $pembayaranId;
        }

        return $catatan;
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

    private static function extractAutoPembayaranId(string $catatan): ?int
    {
        if (!preg_match(self::AUTO_PEMBAYARAN_TAGIHAN_PATTERN, $catatan, $match)) {
            return null;
        }

        return (int) $match[1];
    }

    private function buildTujuanPembayaranTagihan(): ?string
    {
        $namaItem = null;

        if ($this->relationLoaded('details')) {
            $namaItem = $this->details
                ->pluck('nama_item')
                ->filter(fn ($item) => trim((string) $item) !== '')
                ->unique()
                ->implode(', ');
            $namaItem = trim($namaItem);
            $namaItem = $namaItem !== '' ? $namaItem : null;
        }

        if ($namaItem === null) {
            $pembayaranTagihan = $this->relationLoaded('pembayaranTagihan')
                ? $this->pembayaranTagihan
                : $this->pembayaranTagihan()->first();

            $tagihan = $pembayaranTagihan?->relationLoaded('tagihan')
                ? $pembayaranTagihan?->tagihan
                : $pembayaranTagihan?->tagihan()->first();

            if ($tagihan) {
                $itemPembayaran = $tagihan->relationLoaded('itemPembayaran')
                    ? $tagihan->itemPembayaran
                    : $tagihan->itemPembayaran()->first();

                $labelItem = trim((string) ($itemPembayaran?->nama_item ?? ''));
                $periode = trim((string) ($tagihan->periode_label ?? ''));

                if ($labelItem !== '') {
                    $namaItem = $periode !== '' && $periode !== '-'
                        ? $labelItem . ' (' . $periode . ')'
                        : $labelItem;
                }
            }
        }

        return $namaItem;
    }
}
