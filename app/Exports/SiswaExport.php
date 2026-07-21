<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SiswaExport implements FromArray, WithStyles, WithColumnFormatting, WithEvents, ShouldAutoSize
{
    private const LAST_COL = 'F';
    private const HEADER_ROW = 2;

    private const COLOR_PRIMARY = '1E3A5F';
    private const COLOR_PRIMARY_LIGHT = 'EAF0F6';
    private const COLOR_BORDER = 'D7DEE6';

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
                ucfirst(str_replace('_', ' ', (string) $siswa->kategori)),
                ucfirst((string) $siswa->status),
                $sisa,
            ];
        }

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = self::LAST_COL;
        $lastRow = self::HEADER_ROW + $this->rows->count();

        // ===== Banner judul =====
        $sheet->mergeCells('A1:' . $lastCol . '1');
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getStyle('A1:' . $lastCol . '1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // ===== Header tabel =====
        $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(22);
        $sheet->getStyle('A' . self::HEADER_ROW . ':' . $lastCol . self::HEADER_ROW)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
        ]);

        $firstDataRow = self::HEADER_ROW + 1;

        if ($lastRow >= $firstDataRow) {
            // Semua isi tabel rata tengah supaya rapi.
            $sheet->getStyle('A' . $firstDataRow . ':' . $lastCol . $lastRow)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
            ]);

            // Kolom Nama tetap rata kiri agar nama panjang tetap mudah dibaca.
            $sheet->getStyle('B' . $firstDataRow . ':B' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'F' => '"Rp" #,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = self::LAST_COL;
                $lastRow = self::HEADER_ROW + $this->rows->count();
                $firstDataRow = self::HEADER_ROW + 1;

                $sheet->setShowGridlines(false);

                // Baris selang-seling agar tabel mudah dibaca.
                for ($row = $firstDataRow; $row <= $lastRow; $row++) {
                    if (($row - $firstDataRow) % 2 === 1) {
                        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                        ]);
                    }
                }

                $sheet->getPageSetup()
                    ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
                    ->setPaperSize(PageSetup::PAPERSIZE_A4)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
                $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
                $sheet->getTabColor()->setRGB(self::COLOR_PRIMARY);
            },
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
