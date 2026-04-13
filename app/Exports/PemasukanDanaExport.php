<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PemasukanDanaExport implements FromArray, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    public function __construct(private Collection $rows, private string $periodeLabel = 'Semua Periode')
    {
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN PEMASUKAN DANA'],
            ['Periode Laporan', $this->periodeLabel],
            ['Tanggal Cetak', now()->format('d-m-Y H:i')],
            [],
            ['No', 'Tanggal Penerimaan', 'Sumber Dana', 'Nama Pemberi Dana', 'Keterangan/Tujuan Dana', 'Jumlah Dana Diterima'],
        ];

        $no = 1;
        foreach ($this->rows as $row) {
            $data[] = [
                $no++,
                optional($row->tanggal)->format('d-m-Y'),
                $row->kategori,
                $row->nama_siswa ?: '-',
                $row->catatan_tujuan_dana,
                (float) $row->total_bayar,
            ];
        }

        $total = (float) $this->rows->sum('total_bayar');
        $data[] = ['', '', '', '', 'TOTAL PEMASUKAN', $total];

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = 5 + $this->rows->count();

        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A5:F5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1F3B61']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EAF2FF'],
            ],
        ]);

        $sheet->getStyle('E' . $lastRow . ':F' . $lastRow)->applyFromArray([
            'font' => ['bold' => true],
        ]);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '"Rp." #,##0',
        ];
    }
}
