<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BackupTableSheet implements FromCollection, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private string $title,
        private array $columns,
        private array $rows
    ) {
    }

    public function collection(): Collection
    {
        if (empty($this->columns)) {
            return collect($this->rows);
        }

        return collect($this->rows)->map(function (array $row) {
            return collect($this->columns)->map(function (string $column) use ($row) {
                return $row[$column] ?? null;
            })->all();
        });
    }

    public function headings(): array
    {
        if (empty($this->columns)) {
            return [[$this->title]];
        }

        return [
            [$this->title],
            $this->columns,
        ];
    }

    public function title(): string
    {
        return $this->title;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $this->lastColumnLetter();

        $sheet->mergeCells('A1:' . $lastColumn . '1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        if (!empty($this->columns)) {
            $sheet->getStyle('A2:' . $lastColumn . '2')->applyFromArray([
                'font' => ['bold' => true],
            ]);
        }

        return [];
    }

    private function lastColumnLetter(): string
    {
        $columnCount = max(1, count($this->columns));
        $index = $columnCount;
        $letter = '';

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod) . $letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }
}
