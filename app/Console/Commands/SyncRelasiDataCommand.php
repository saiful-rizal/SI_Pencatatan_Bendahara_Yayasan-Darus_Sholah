<?php

namespace App\Console\Commands;

use App\Models\DetailTransaksi;
use App\Models\PembayaranTagihan;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanPotongan;
use App\Models\Transaksi;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class SyncRelasiDataCommand extends Command
{
    protected $signature = 'data:sync-relasi {--dry-run : Simulasi tanpa menyimpan perubahan}';

    protected $description = 'Sinkronisasi relasi dan konsistensi data utama antar tabel';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $stats = [
            'siswa_updated' => 0,
            'tagihan_status_updated' => 0,
            'pembayaran_transaksi_synced' => 0,
            'transaksi_updated' => 0,
            'orphan_detail_deleted' => 0,
            'orphan_potongan_deleted' => 0,
            'orphan_pembayaran_deleted' => 0,
        ];

        $modeLabel = $dryRun ? 'DRY RUN' : 'EKSEKUSI';
        $this->info("Memulai sinkronisasi data ({$modeLabel})...");

        $this->syncSiswaData($dryRun, $stats);
        $this->syncTagihanStatus($dryRun, $stats);
        $this->syncPembayaranTransaksi($dryRun, $stats);
        $this->syncTransaksiData($dryRun, $stats);
        $this->cleanupOrphanData($dryRun, $stats);

        $this->newLine();
        $this->info('Ringkasan hasil sinkronisasi:');
        $this->table(['Aksi', 'Jumlah'], [
            ['Siswa diperbarui', $stats['siswa_updated']],
            ['Status tagihan diperbarui', $stats['tagihan_status_updated']],
            ['Transaksi pembayaran disinkronkan', $stats['pembayaran_transaksi_synced']],
            ['Transaksi diperbarui', $stats['transaksi_updated']],
            ['Detail transaksi yatim dibersihkan', $stats['orphan_detail_deleted']],
            ['Potongan tagihan yatim dibersihkan', $stats['orphan_potongan_deleted']],
            ['Pembayaran tagihan yatim dibersihkan', $stats['orphan_pembayaran_deleted']],
        ]);

        if ($dryRun) {
            $this->comment('Dry run selesai. Tidak ada perubahan tersimpan.');
        } else {
            $this->info('Sinkronisasi selesai dan perubahan sudah disimpan.');
        }

        return self::SUCCESS;
    }

    private function syncSiswaData(bool $dryRun, array &$stats): void
    {
        $this->line('1/4 Sinkronisasi data siswa...');

        Siswa::query()
            ->select(['id', 'kelas', 'status'])
            ->orderBy('id')
            ->chunkById(200, function ($siswas) use ($dryRun, &$stats) {
                foreach ($siswas as $siswa) {
                    $newKelas = $this->normalisasiKelas($siswa->kelas);
                    $newStatus = $siswa->status;

                    if ($newKelas !== null && strcasecmp($newKelas, 'Lulus') === 0) {
                        $newStatus = 'lulus';
                    }

                    if ($newStatus === 'lulus' && strcasecmp((string) $newKelas, 'Lulus') !== 0) {
                        $newKelas = 'Lulus';
                    }

                    $payload = [];

                    if ((string) $siswa->kelas !== (string) $newKelas) {
                        $payload['kelas'] = $newKelas;
                    }

                    if ((string) $siswa->status !== (string) $newStatus) {
                        $payload['status'] = $newStatus;
                    }

                    if ($payload === []) {
                        continue;
                    }

                    if (!$dryRun) {
                        $siswa->update($payload);
                    }

                    $stats['siswa_updated']++;
                }
            });
    }

    private function syncTagihanStatus(bool $dryRun, array &$stats): void
    {
        $this->line('2/4 Sinkronisasi status tagihan...');

        Tagihan::query()
            ->withSum('potongans as total_potongan', 'nominal_potongan')
            ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
            ->select(['id', 'nominal_awal', 'status'])
            ->orderBy('id')
            ->chunkById(200, function ($tagihans) use ($dryRun, &$stats) {
                foreach ($tagihans as $tagihan) {
                    $potongan = (float) ($tagihan->total_potongan ?? 0);
                    $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
                    $totalAkhir = max(0, (float) $tagihan->nominal_awal - $potongan);
                    $sisa = max(0, $totalAkhir - $pembayaran);

                    $newStatus = 'belum_lunas';
                    if ($sisa <= 0) {
                        $newStatus = 'lunas';
                    } elseif ($pembayaran > 0) {
                        $newStatus = 'sebagian';
                    }

                    if ((string) $tagihan->status === $newStatus) {
                        continue;
                    }

                    if (!$dryRun) {
                        $tagihan->update(['status' => $newStatus]);
                    }

                    $stats['tagihan_status_updated']++;
                }
            });
    }

    private function syncPembayaranTransaksi(bool $dryRun, array &$stats): void
    {
        $this->line('3/5 Sinkronisasi pembayaran ke transaksi dashboard...');

        PembayaranTagihan::query()
            ->with(['tagihan.siswa', 'tagihan.itemPembayaran'])
            ->select(['id', 'tagihan_id', 'tanggal_bayar', 'nominal_bayar'])
            ->orderBy('id')
            ->chunkById(200, function ($pembayarans) use ($dryRun, &$stats) {
                foreach ($pembayarans as $pembayaran) {
                    if (!$pembayaran->tagihan) {
                        continue;
                    }

                    $marker = $this->markerPembayaran((int) $pembayaran->id);
                    $siswa = $pembayaran->tagihan->siswa;
                    $item = $pembayaran->tagihan->itemPembayaran;
                    $nominal = (float) $pembayaran->nominal_bayar;
                    $namaItem = $item?->nama_item ?? 'Pembayaran Tagihan';
                    $periode = $pembayaran->tagihan->periode_label ?? '-';
                    $labelItem = $periode !== '-' ? $namaItem . ' (' . $periode . ')' : $namaItem;

                    $transaksi = Transaksi::query()->firstOrNew([
                        'jenis' => 'Masuk',
                        'catatan' => $marker,
                    ]);

                    $changed = !$transaksi->exists
                        || (string) $transaksi->kategori !== 'Pembayaran Tagihan'
                        || (string) $transaksi->nama_siswa !== (string) ($siswa?->nama)
                        || (string) $transaksi->kelas !== (string) ($siswa?->kelas)
                        || round((float) $transaksi->total_bayar, 2) !== round($nominal, 2)
                        || (string) optional($transaksi->tanggal)->toDateString() !== (string) optional($pembayaran->tanggal_bayar)->toDateString();

                    if (!$dryRun) {
                        $transaksi->kategori = 'Pembayaran Tagihan';
                        $transaksi->siswa_id = $siswa?->id;
                        $transaksi->pembayaran_tagihan_id = $pembayaran->id;
                        $transaksi->nama_siswa = $siswa?->nama;
                        $transaksi->kelas = $siswa?->kelas;
                        $transaksi->total_bayar = $nominal;
                        $transaksi->tanggal = $pembayaran->tanggal_bayar;
                        $transaksi->save();

                        $firstDetail = DetailTransaksi::query()->where('transaksi_id', $transaksi->id)->orderBy('id')->first();
                        if ($firstDetail === null
                            || (string) $firstDetail->nama_item !== (string) $labelItem
                            || round((float) $firstDetail->harga, 2) !== round($nominal, 2)
                            || (int) $firstDetail->jumlah !== 1
                            || round((float) $firstDetail->subtotal, 2) !== round($nominal, 2)
                            || DetailTransaksi::query()->where('transaksi_id', $transaksi->id)->count() > 1
                        ) {
                            DetailTransaksi::query()->where('transaksi_id', $transaksi->id)->delete();
                            DetailTransaksi::create([
                                'transaksi_id' => $transaksi->id,
                                'item_pembayaran_id' => $item?->id,
                                'nama_item' => $labelItem,
                                'harga' => $nominal,
                                'jumlah' => 1,
                                'subtotal' => $nominal,
                            ]);
                            $changed = true;
                        }
                    }

                    if ($changed) {
                        $stats['pembayaran_transaksi_synced']++;
                    }
                }
            });
    }

    private function syncTransaksiData(bool $dryRun, array &$stats): void
    {
        $this->line('4/5 Sinkronisasi total transaksi...');
        $canSyncSiswaRelation = Schema::hasColumn('transaksis', 'siswa_id');

        Transaksi::query()
            ->withSum('details as total_detail', 'subtotal')
            ->select(['id', 'siswa_id', 'nama_siswa', 'kelas', 'total_bayar'])
            ->orderBy('id')
            ->chunkById(200, function ($transaksis) use ($dryRun, $canSyncSiswaRelation, &$stats) {
                foreach ($transaksis as $transaksi) {
                    $payload = [];

                    if ($canSyncSiswaRelation && (int) ($transaksi->siswa_id ?? 0) === 0 && !empty($transaksi->nama_siswa)) {
                        $siswaId = $this->resolveSiswaId((string) $transaksi->nama_siswa, $transaksi->kelas);
                        if ($siswaId !== null) {
                            $payload['siswa_id'] = $siswaId;
                        }
                    }

                    $kelasBaru = $this->normalisasiKelas($transaksi->kelas);
                    if ((string) $transaksi->kelas !== (string) $kelasBaru) {
                        $payload['kelas'] = $kelasBaru;
                    }

                    $totalDetail = (float) ($transaksi->total_detail ?? 0);
                    if ($totalDetail > 0) {
                        $currentTotal = round((float) $transaksi->total_bayar, 2);
                        $newTotal = round($totalDetail, 2);

                        if ($currentTotal !== $newTotal) {
                            $payload['total_bayar'] = $newTotal;
                        }
                    }

                    if ($payload === []) {
                        continue;
                    }

                    if (!$dryRun) {
                        $transaksi->update($payload);
                    }

                    $stats['transaksi_updated']++;
                }
            });
    }

    private function cleanupOrphanData(bool $dryRun, array &$stats): void
    {
        $this->line('5/5 Membersihkan data yatim...');

        $orphanDetails = DetailTransaksi::query()->doesntHave('transaksi');
        $stats['orphan_detail_deleted'] = $orphanDetails->count();
        if (!$dryRun && $stats['orphan_detail_deleted'] > 0) {
            $orphanDetails->delete();
        }

        $orphanPotongans = TagihanPotongan::query()->doesntHave('tagihan');
        $stats['orphan_potongan_deleted'] = $orphanPotongans->count();
        if (!$dryRun && $stats['orphan_potongan_deleted'] > 0) {
            $orphanPotongans->delete();
        }

        $orphanPembayarans = PembayaranTagihan::query()->doesntHave('tagihan');
        $stats['orphan_pembayaran_deleted'] = $orphanPembayarans->count();
        if (!$dryRun && $stats['orphan_pembayaran_deleted'] > 0) {
            $orphanPembayarans->delete();
        }
    }

    private function normalisasiKelas(?string $kelas): ?string
    {
        if ($kelas === null) {
            return null;
        }

        $kelas = preg_replace('/\s+/', ' ', trim($kelas)) ?? '';

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

        $roman = match ($prefix) {
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
            '13' => 'XIII',
            default => $prefix,
        };

        return $roman . ($suffix !== '' ? ' ' . ltrim($suffix, '-/ ') : '');
    }

    private function markerPembayaran(int $pembayaranId): string
    {
        return '[AUTO-PEMBAYARAN-TAGIHAN#' . $pembayaranId . ']';
    }

    private function resolveSiswaId(string $namaSiswa, ?string $kelas): ?int
    {
        $namaSiswa = trim($namaSiswa);
        if ($namaSiswa === '') {
            return null;
        }

        $query = Siswa::query()->where('nama', $namaSiswa);

        $kelas = $this->normalisasiKelas($kelas);
        if ($kelas !== null) {
            $kelasLegacy = $this->kelasLegacyNumerik($kelas);
            $query->whereIn('kelas', array_values(array_unique([$kelas, $kelasLegacy])));
        }

        return $query->orderBy('id')->value('id');
    }

    private function kelasLegacyNumerik(?string $kelas): ?string
    {
        if ($kelas === null || strcasecmp($kelas, 'Lulus') === 0) {
            return $kelas;
        }

        if (!preg_match('/^(XIII|XII|XI|X)(.*)$/i', $kelas, $match)) {
            return $kelas;
        }

        $prefix = strtoupper($match[1]);
        $suffix = trim((string) ($match[2] ?? ''));
        $angka = match ($prefix) {
            'X' => '10',
            'XI' => '11',
            'XII' => '12',
            'XIII' => '13',
            default => $prefix,
        };

        return $angka . ($suffix !== '' ? ' ' . ltrim($suffix, '-/ ') : '');
    }
}
