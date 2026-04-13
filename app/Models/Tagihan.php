<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tagihan extends Model
{
	use HasFactory;

	protected static function booted(): void
	{
		static::creating(function (Tagihan $tagihan) {
			if (!empty($tagihan->kelas)) {
				return;
			}

			$siswa = $tagihan->relationLoaded('siswa')
				? $tagihan->siswa
				: Siswa::find($tagihan->siswa_id);

			if ($siswa !== null && !empty($siswa->kelas)) {
				$tagihan->kelas = $siswa->kelas;
			}
		});
	}

	private const MONTH_NAMES = [
		1 => 'Januari',
		2 => 'Februari',
		3 => 'Maret',
		4 => 'April',
		5 => 'Mei',
		6 => 'Juni',
		7 => 'Juli',
		8 => 'Agustus',
		9 => 'September',
		10 => 'Oktober',
		11 => 'November',
		12 => 'Desember',
	];

	protected $fillable = [
		'siswa_id',
		'item_pembayaran_id',
		'kelas',
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

	public function getPeriodeLabelAttribute(): string
	{
		$bulan = (int) ($this->periode_bulan ?? 0);
		$tahun = trim((string) ($this->periode_tahun ?? ''));

		if ($bulan < 1 || $bulan > 12 || $tahun === '') {
			return '-';
		}

		$namaBulan = self::MONTH_NAMES[$bulan] ?? null;
		if ($namaBulan === null) {
			return '-';
		}

		return $namaBulan . ' ' . $tahun;
	}

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
