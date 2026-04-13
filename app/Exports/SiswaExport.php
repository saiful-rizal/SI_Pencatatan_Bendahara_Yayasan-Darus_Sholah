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

class SiswaExport implements FromArray, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    public function __construct(private Collection $rows)
    {
    }

    public function array(): array
    {
        $data = [
            ['DATA SISWA'],
            [
                'NIS',
                'Nama',
                'Kelas',
                'Kategori',
                'Status',
                'Sisa Tanggungan',
                'Aksi',
            ],
        ];

        foreach ($this->rows as $siswa) {
            $sisa = $siswa->tagihans->sum(function ($tagihan) {
                $potongan = (float) ($tagihan->total_potongan ?? 0);
                $bayar = (float) ($tagihan->total_pembayaran ?? 0);
                return max(0, (float) $tagihan->nominal_awal - $potongan - $bayar);
            });

            $data[] = [
                $siswa->nis,
                $siswa->nama,
                $this->kelasUntukExport((string) $siswa->kelas),
                $siswa->kategori,
                $siswa->status,
                $sisa,
                '',
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = 2 + $this->rows->count();

        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => '1F3B61']],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EAF2FF'],
            ],
        ]);

        if ($lastRow >= 3) {
            $sheet->getStyle('F3:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '"Rp." #,##0',
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
