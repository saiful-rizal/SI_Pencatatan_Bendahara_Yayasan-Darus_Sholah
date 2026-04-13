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
        $transaksi->total_bayar = (float) $this->nominal_bayar;
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
            'harga' => (float) $this->nominal_bayar,
            'jumlah' => 1,
            'subtotal' => (float) $this->nominal_bayar,
        ]);
    }

    private function markerPembayaran(): string
    {
        return '[AUTO-PEMBAYARAN-TAGIHAN#' . $this->id . ']';
    }
}
