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

class RekapExport implements FromArray, WithColumnFormatting, ShouldAutoSize, WithStyles
{
    public function __construct(private Collection $rows)
    {
    }

    public function array(): array
    {
        $data = [
            ['REKAP KEUANGAN'],
            ['Tanggal Cetak', now()->format('d-m-Y H:i')],
            [],
            [
            'NIS',
            'Nama',
            'Kelas',
            'Angkatan',
            'Nominal Awal',
            'Potongan',
            'Total Akhir',
            'Pembayaran',
            'Sisa',
            ],
        ];

        foreach ($this->rows as $tagihan) {
            $potongan = (float) ($tagihan->total_potongan ?? 0);
            $bayar = (float) ($tagihan->total_pembayaran ?? 0);
            $totalAkhir = max(0, (float) $tagihan->nominal_awal - $potongan);
            $sisa = max(0, $totalAkhir - $bayar);

            $data[] = [
                $tagihan->siswa->nis ?? '-',
                $tagihan->siswa->nama ?? '-',
                $this->kelasUntukExport((string) ($tagihan->siswa->kelas ?? '-')),
                $tagihan->siswa->angkatan ?? '-',
                (float) $tagihan->nominal_awal,
                $potongan,
                $totalAkhir,
                $bayar,
                $sisa,
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = 4 + $this->rows->count();

        $sheet->mergeCells('A1:I1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A4:I4')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1F3B61']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EAF2FF'],
            ],
        ]);

        $sheet->getStyle('E5:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'E' => '"Rp." #,##0',
            'F' => '"Rp." #,##0',
            'G' => '"Rp." #,##0',
            'H' => '"Rp." #,##0',
            'I' => '"Rp." #,##0',
        ];
    }

    private function kelasUntukExport(string $kelas): string
    {
        $kelas = trim($kelas);
        if ($kelas === '' || strcasecmp($kelas, 'lulus') === 0) {
            return $kelas;
        }

        if (!preg_match('/^(XIII|XII|XI|X)(.*)$/i', $kelas, $match)) {
            return $kelas;
        }

        $prefix = strtoupper($match[1]);
        $suffix = trim((string) ($match[2] ?? ''));
        $numeric = match ($prefix) {
            'X' => '10',
            'XI' => '11',
            'XII' => '12',
            'XIII' => '13',
            default => $prefix,
        };

        return $numeric . ($suffix !== '' ? ' ' . ltrim($suffix, '-/ ') : '');
    }
}
