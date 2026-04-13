<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RekapKasExport implements FromArray, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    public function __construct(private string $periodeLabel, private array $ringkasanKas)
    {
    }

    public function array(): array
    {
        return [
            ['LAPORAN REKAPITULASI KEUANGAN / KAS'],
            ['Periode Laporan', $this->periodeLabel],
            ['Tanggal Cetak', now()->format('d-m-Y H:i')],
            [],
            ['Saldo Awal Kas', (float) $this->ringkasanKas['saldoAwalKas']],
            ['Total Pemasukan Dana', (float) $this->ringkasanKas['totalPemasukan']],
            ['Total Pengeluaran Dana', (float) $this->ringkasanKas['totalPengeluaran']],
            ['Saldo Akhir Kas', (float) $this->ringkasanKas['saldoAkhirKas']],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:B1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A5:B5')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EAF2FF'],
            ],
        ]);

        $sheet->getStyle('A6:B8')->applyFromArray([
            'font' => ['bold' => true],
        ]);

        $sheet->getStyle('B5:B8')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'B' => '"Rp." #,##0',
        ];
    }
}
