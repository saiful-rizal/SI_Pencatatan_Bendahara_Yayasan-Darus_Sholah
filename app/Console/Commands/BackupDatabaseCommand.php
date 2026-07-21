<?php

namespace App\Console\Commands;

use App\Exports\BackupDatabaseExport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'backup:database
        {--disk=local : Disk penyimpanan}
        {--keep=30 : Jumlah hari backup disimpan}';

    protected $description = 'Menyimpan backup database ke storage';

    public function handle(): int
    {
        $disk = $this->option('disk');
        $keepDays = (int) $this->option('keep');

        $this->info("Mengambil data backup...");
        $backupData = $this->buildBackupPayload();
        $timestamp = now()->format('Ymd_His');

        $filename = "backup/backup_{$timestamp}.xlsx";

        $directory = storage_path('app/' . dirname($filename));
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        Excel::store(new BackupDatabaseExport($backupData), $filename, $disk);

        $fullPath = storage_path("app/{$filename}");
        $this->info("Backup tersimpan: {$fullPath}");

        $this->bersihkanBackupLama($disk, $keepDays);

        return self::SUCCESS;
    }

    private function buildBackupPayload(): array
    {
        $backupTables = $this->backupTables();

        $payload = [
            'generated_at' => now()->toDateTimeString(),
            'app_version' => config('app.version', 'unknown'),
            'tables' => [],
        ];

        foreach ($backupTables as $table => $info) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);

            $payload['tables'][$table] = [
                'label' => $info['label'],
                'columns' => $columns,
                'rows' => $this->fetchTableRows($table, $columns),
            ];
        }

        return $payload;
    }

    private function fetchTableRows(string $table, array $columns): array
    {
        $query = DB::table($table)->select('*');

        if (in_array('id', $columns, true)) {
            $query->orderBy('id');
        } elseif (in_array('created_at', $columns, true)) {
            $query->orderBy('created_at');
        }

        return $query->get()->map(function ($row) {
            return json_decode(json_encode($row), true);
        })->all();
    }

    private function bersihkanBackupLama(string $disk, int $keepDays): void
    {
        $files = Storage::disk($disk)->files('backup');
        $batas = now()->subDays($keepDays);

        foreach ($files as $file) {
            $lastModified = Storage::disk($disk)->lastModified($file);
            if ($lastModified < $batas->timestamp) {
                Storage::disk($disk)->delete($file);
                $this->line("  Backup lama dihapus: {$file}");
            }
        }
    }

    private function backupTables(): array
    {
        return [
            'users' => ['label' => 'Data Pengguna'],
            'siswas' => ['label' => 'Data Siswa'],
            'item_pembayarans' => ['label' => 'Item Pembayaran'],
            'tagihans' => ['label' => 'Tagihan'],
            'tagihan_potongans' => ['label' => 'Potongan Tagihan'],
            'pembayaran_tagihans' => ['label' => 'Pembayaran Tagihan'],
            'transaksis' => ['label' => 'Transaksi Keuangan'],
            'detail_transaksis' => ['label' => 'Detail Transaksi'],
            'deletion_histories' => ['label' => 'Riwayat Hapus'],
            'password_reset_codes' => ['label' => 'Kode Reset Password'],
        ];
    }
}
