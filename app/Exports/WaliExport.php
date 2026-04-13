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

class WaliExport implements FromArray, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    private int $totalRows = 0;

    public function __construct(private Collection $rows, private string $periodeLabel = 'Semua Periode')
    {
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN PEMBAYARAN WALI MURID'],
            ['Periode Laporan', $this->periodeLabel],
            ['Tanggal Cetak', now()->format('d-m-Y H:i')],
            [],
        ];

        $groupedRows = [
            '10' => collect(),
            '11' => collect(),
            '12' => collect(),
        ];

        foreach ($this->rows as $row) {
            $tagihan = $row->tagihan;
            $kelasTagihanRaw = trim((string) ($tagihan?->kelas ?? ''));
            $kelasTagihanNorm = preg_replace('/\s+/', ' ', $kelasTagihanRaw) ?: '';
            $kelasAngka = null;

            if (preg_match('/^(XII|XI|X|12|11|10)\b/i', $kelasTagihanNorm, $kelasMatch)) {
                $prefix = strtoupper($kelasMatch[1]);
                $kelasAngka = match ($prefix) {
                    'X' => '10',
                    'XI' => '11',
                    'XII' => '12',
                    default => $prefix,
                };
            }

            if (isset($groupedRows[$kelasAngka])) {
                $groupedRows[$kelasAngka]->push($row);
            }
        }

        $grandTotal = 0;
        foreach (['10', '11', '12'] as $kelas) {
            $data[] = ['LIST PEMBAYARAN KELAS TAGIHAN ' . $kelas];
            $data[] = ['No', 'Tanggal', 'NIS', 'Nama Siswa', 'Kelas Tagihan', 'Item Tagihan', 'Periode', 'Metode', 'Nominal Bayar'];

            $no = 1;
            $subtotal = 0;
            foreach ($groupedRows[$kelas] as $row) {
                $tagihan = $row->tagihan;
                $siswa = $tagihan?->siswa;
                $item = $tagihan?->itemPembayaran;
                $kelasTagihanRaw = trim((string) ($tagihan?->kelas ?? ''));
                $kelasTagihanNorm = preg_replace('/\s+/', ' ', $kelasTagihanRaw) ?: '-';
                $nominal = (float) $row->nominal_bayar;
                $subtotal += $nominal;

                $data[] = [
                    $no++,
                    optional($row->tanggal_bayar)->format('d-m-Y'),
                    $siswa?->nis ?? '-',
                    $siswa?->nama ?? '-',
                    $kelasTagihanNorm,
                    $item?->nama_item ?? '-',
                    $tagihan?->periode_label ?? '-',
                    strtoupper((string) ($row->metode_bayar ?? '-')),
                    $nominal,
                ];
            }

            if ($groupedRows[$kelas]->isEmpty()) {
                $data[] = ['-', '-', '-', '-', '-', '-', '-', '-', 0];
            }

            $data[] = ['', '', '', '', '', '', '', 'TOTAL KELAS ' . $kelas, $subtotal];
            $data[] = [];
            $grandTotal += $subtotal;
        }

        $data[] = ['', '', '', '', '', '', '', 'GRAND TOTAL', $grandTotal];

        $this->totalRows = count($data);

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        for ($row = 1; $row <= $this->totalRows; $row++) {
            $cellA = (string) $sheet->getCell('A' . $row)->getValue();
            $cellH = (string) $sheet->getCell('H' . $row)->getValue();

            if ($cellA === 'No') {
                $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1F3B61']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EAF2FF'],
                    ],
                ]);
            }

            if (str_starts_with($cellA, 'LIST PEMBAYARAN KELAS TAGIHAN')) {
                $sheet->mergeCells('A' . $row . ':I' . $row);
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                ]);
            }

            if (str_starts_with($cellH, 'TOTAL KELAS') || $cellH === 'GRAND TOTAL') {
                $sheet->getStyle('H' . $row . ':I' . $row)->applyFromArray([
                    'font' => ['bold' => true],
                ]);
            }
        }

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'I' => '"Rp." #,##0',
        ];
    }
}
