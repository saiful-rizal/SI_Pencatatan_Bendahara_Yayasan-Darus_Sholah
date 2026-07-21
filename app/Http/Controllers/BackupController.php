<?php

namespace App\Http\Controllers;

use App\Exports\BackupDatabaseExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class BackupController extends Controller
{
    public function index()
    {
        return view('backup.database', [
            'backupGroups' => $this->backupGroups(),
            'backupTables' => $this->backupTables(),
            'storedBackups' => $this->getStoredBackups(),
        ]);
    }

    public function stored()
    {
        return view('backup.stored', [
            'backups' => $this->getStoredBackups(),
        ]);
    }

    public function downloadStored(string $filename)
    {
        $path = 'backup/' . $filename;

        if (!Storage::exists($path)) {
            return back()->with('error', 'File backup tidak ditemukan.');
        }

        return Storage::download($path);
    }

    public function deleteStored(string $filename)
    {
        $path = 'backup/' . $filename;

        if (!Storage::exists($path)) {
            return back()->with('error', 'File backup tidak ditemukan.');
        }

        Storage::delete($path);

        return back()->with('success', 'Backup ' . $filename . ' berhasil dihapus.');
    }

    private function getStoredBackups(): array
    {
        if (!Storage::exists('backup')) {
            return [];
        }

        $files = Storage::files('backup');

        $backups = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $backups[] = [
                'filename' => $filename,
                'size' => Storage::size($file),
                'size_formatted' => $this->formatBytes(Storage::size($file)),
                'last_modified' => Storage::lastModified($file),
                'last_modified_formatted' => date('Y-m-d H:i:s', Storage::lastModified($file)),
                'type' => 'Excel',
            ];
        }

        usort($backups, fn ($a, $b) => $b['last_modified'] - $a['last_modified']);

        return $backups;
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'tables' => ['nullable', 'array'],
            'tables.*' => ['string'],
        ]);

        $selectedTables = collect($validated['tables'] ?? [])
            ->filter()
            ->intersect(array_keys($this->backupTables()))
            ->values()
            ->all();

        if (empty($selectedTables)) {
            $selectedTables = array_keys($this->backupTables());
        }

        $backupData = $this->buildBackupPayload($selectedTables);
        $timestamp = now()->format('Ymd_His');

        return Excel::download(new BackupDatabaseExport($backupData), 'backup_keuangan_' . $timestamp . '.xlsx');
    }

    private function buildBackupPayload(array $selectedTables): array
    {
        $payload = [
            'generated_at' => now()->toDateTimeString(),
            'tables' => [],
        ];

        $backupTables = $this->backupTables();

        foreach ($selectedTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $columns = Schema::getColumnListing($table);

            $payload['tables'][$table] = [
                'label' => $backupTables[$table]['label'] ?? $table,
                'columns' => $columns,
                'rows' => $this->fetchTableRows($table),
            ];
        }

        return $payload;
    }

    private function fetchTableRows(string $table): array
    {
        $query = DB::table($table)->select('*');

        $columns = Schema::getColumnListing($table);

        if (in_array('id', $columns, true)) {
            $query->orderBy('id');
        } elseif (in_array('created_at', $columns, true)) {
            $query->orderBy('created_at');
        }

        return $query->get()->map(function ($row) {
            return json_decode(json_encode($row), true);
        })->all();
    }

    private function backupGroups(): array
    {
        return [
            [
                'label' => 'Data Master',
                'description' => 'Data utama aplikasi yang dipakai di hampir semua menu.',
                'tables' => ['users', 'siswas', 'item_pembayarans', 'tagihans', 'tagihan_potongans', 'pembayaran_tagihans'],
            ],
            [
                'label' => 'Transaksi Keuangan',
                'description' => 'Data transaksi masuk/keluar beserta detail itemnya.',
                'tables' => ['transaksis', 'detail_transaksis'],
            ],
            [
                'label' => 'Sistem & Riwayat',
                'description' => 'Riwayat penghapusan dan kode pemulihan akun.',
                'tables' => ['deletion_histories', 'password_reset_codes'],
            ],
        ];
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
