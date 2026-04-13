<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BackupDatabaseExport implements WithMultipleSheets
{
    public function __construct(private array $backupData)
    {
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->backupData['tables'] as $table => $tableData) {
            $sheets[] = new BackupTableSheet(
                $this->sheetTitle($tableData['label'] ?? $table),
                $tableData['columns'] ?? [],
                $tableData['rows'] ?? []
            );
        }

        if (empty($sheets)) {
            $sheets[] = new BackupTableSheet(
                'Info Backup',
                ['keterangan'],
                [['keterangan' => 'Tidak ada tabel yang dapat dibackup.']]
            );
        }

        return $sheets;
    }

    private function sheetTitle(string $title): string
    {
        $title = str_replace(['\\', '/', '?', '*', '[', ']', ':'], ' ', $title);
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? $title);

        return mb_substr($title, 0, 31);
    }
}
