<?php

namespace App\Http\Controllers;

use App\Exports\BackupDatabaseExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Facades\Excel;

class BackupController extends Controller
{
    public function index()
    {
        return view('backup.database', [
            'backupGroups' => $this->backupGroups(),
            'backupFormats' => [
                'excel' => 'Excel (.xlsx)',
                'json' => 'JSON (.json)',
            ],
            'backupTables' => $this->backupTables(),
        ]);
    }

    public function download(Request $request)
    {
        $validated = $request->validate([
            'format' => ['required', 'in:excel,json'],
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

        if ($validated['format'] === 'excel') {
            return Excel::download(new BackupDatabaseExport($backupData), 'backup_keuangan_' . $timestamp . '.xlsx');
        }

        return response()->streamDownload(function () use ($backupData) {
            echo json_encode($backupData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, 'backup_keuangan_' . $timestamp . '.json', ['Content-Type' => 'application/json']);
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
                'rows' => $this->fetchTableRows($table, $columns),
            ];
        }

        return $payload;
    }

    private function fetchTableRows(string $table, array $columns): array
    {
        $query = DB::table($table);

        if (in_array('id', $columns, true)) {
            $query->orderBy('id');
        } elseif (in_array('created_at', $columns, true)) {
            $query->orderBy('created_at');
        }

        return $query->get()->map(function ($row) {
            return (array) $row;
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
