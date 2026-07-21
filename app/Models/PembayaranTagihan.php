<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class PembayaranTagihan extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saved(function (PembayaranTagihan $pembayaranTagihan): void {
            $pembayaranTagihan->sinkronkanTransaksi();
        });
    }

    protected $fillable = [
        'tagihan_id',
        'tanggal_bayar',
        'nominal_bayar',
        'metode_bayar',
        'nama_bank',
        'catatan',
    ];

    protected $casts = [
        'tanggal_bayar' => 'datetime',
        'nominal_bayar' => 'decimal:2',
    ];

    /**
     * Jika hanya tanggal (tanpa jam) yang dikirim dari form (input type="date"),
     * tempelkan jam saat ini (real time WIB) supaya nota pembayaran dan
     * riwayat transaksi menampilkan waktu pembayaran yang sebenarnya,
     * bukan selalu 00:00:00.
     */
    public function setTanggalBayarAttribute($value): void
    {
        if ($value instanceof \Illuminate\Support\Carbon || $value instanceof \Carbon\Carbon) {
            $this->attributes['tanggal_bayar'] = $value;
            return;
        }

        if (is_string($value) && str_contains($value, ':')) {
            $this->attributes['tanggal_bayar'] = $value;
            return;
        }

        $this->attributes['tanggal_bayar'] = \Carbon\Carbon::parse($value)->setTimeFrom(now());
    }

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class);
    }

    public function transaksi()
    {
        return $this->hasOne(Transaksi::class);
    }

    public function sinkronkanTransaksi(): void
    {
        $this->loadMissing(['tagihan.siswa', 'tagihan.itemPembayaran']);

        $tagihan = $this->tagihan;
        if (!$tagihan) {
            return;
        }

        $siswa = $tagihan->siswa;
        $itemPembayaran = $tagihan->itemPembayaran;
        $marker = $this->markerPembayaran();

        $transaksi = Transaksi::withTrashed()
            ->where('pembayaran_tagihan_id', $this->id)
            ->orWhere(function ($query) use ($marker) {
                $query->where('jenis', 'Masuk')->where('catatan', $marker);
            })
            ->first();

        if ($transaksi === null) {
            $transaksi = new Transaksi();
            $transaksi->jenis = 'Masuk';
            $transaksi->catatan = $marker;
        } elseif (method_exists($transaksi, 'trashed') && $transaksi->trashed()) {
            $transaksi->restore();
        }

        $userId = Auth::id();

        $transaksi->kategori = 'Pembayaran Tagihan';
        $transaksi->catatan = $marker;
        $transaksi->siswa_id = $siswa?->id;
        $transaksi->pembayaran_tagihan_id = $this->id;
        $transaksi->nama_siswa = $siswa?->nama;
        $transaksi->kelas = $tagihan->kelas ?? $siswa?->kelas;
        $transaksi->total_bayar = round((float) $this->nominal_bayar, 2);
        $transaksi->tanggal = $this->tanggal_bayar;

        if (!$transaksi->exists) {
            $transaksi->created_by = $userId;
        }
        if ($userId !== null) {
            $transaksi->updated_by = $userId;
        }

        $transaksi->save();

        $namaItem = $itemPembayaran?->nama_item ?? 'Pembayaran Tagihan';
        $periode = $tagihan->periode_label ?? '-';
        $labelItem = $periode !== '-' ? $namaItem . ' (' . $periode . ')' : $namaItem;

        DetailTransaksi::query()->where('transaksi_id', $transaksi->id)->delete();
        DetailTransaksi::create([
            'transaksi_id' => $transaksi->id,
            'item_pembayaran_id' => $itemPembayaran?->id,
            'nama_item' => $labelItem,
            'harga' => round((float) $this->nominal_bayar, 2),
            'jumlah' => 1,
            'subtotal' => round((float) $this->nominal_bayar, 2),
        ]);
    }

    private function markerPembayaran(): string
    {
        return '[AUTO-PEMBAYARAN-TAGIHAN#' . $this->id . ']';
    }
}
