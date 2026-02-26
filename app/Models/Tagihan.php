<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagihan extends Model
{
	use HasFactory;

	protected $fillable = [
		'siswa_id',
		'item_pembayaran_id',
		'periode_bulan',
		'periode_tahun',
		'nominal_awal',
		'jatuh_tempo',
		'status',
		'catatan',
	];

	protected $casts = [
		'nominal_awal' => 'decimal:2',
		'jatuh_tempo' => 'date',
	];

	public function siswa()
	{
		return $this->belongsTo(Siswa::class);
	}

	public function itemPembayaran()
	{
		return $this->belongsTo(ItemPembayaran::class);
	}

	public function potongans()
	{
		return $this->hasMany(TagihanPotongan::class);
	}

	public function pembayarans()
	{
		return $this->hasMany(PembayaranTagihan::class);
	}

	public function totalPotongan(): float
	{
		return (float) $this->potongans()->sum('nominal_potongan');
	}

	public function totalPembayaran(): float
	{
		return (float) $this->pembayarans()->sum('nominal_bayar');
	}

	public function totalAkhir(): float
	{
		return max(0, (float) $this->nominal_awal - $this->totalPotongan());
	}

	public function sisaTagihan(): float
	{
		return max(0, $this->totalAkhir() - $this->totalPembayaran());
	}

	public function sinkronkanStatus(): void
	{
		$sisa = $this->sisaTagihan();
		$dibayar = $this->totalPembayaran();

		if ($sisa <= 0) {
			$status = 'lunas';
		} elseif ($dibayar > 0) {
			$status = 'sebagian';
		} else {
			$status = 'belum_lunas';
		}

		if ($this->status !== $status) {
			$this->update(['status' => $status]);
		}
	}
}
