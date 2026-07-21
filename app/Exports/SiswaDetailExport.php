<?php

namespace App\Exports;

use App\Models\Siswa;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Catatan penting:
 * Export ini SENGAJA tidak memakai concern `FromArray` + baris pemisah kosong
 * (`[]`) seperti versi sebelumnya. Ternyata baris array kosong yang dipakai
 * sebagai pemisah antar section itu "hilang" total saat diproses oleh
 * Maatwebsite Excel (lihat Maatwebsite\Excel\Helpers\ArrayHelper::hasMultipleRows,
 * yang secara tidak sengaja menganggap array kosong sebagai "multi baris"
 * sehingga baris tsb tidak pernah ditulis) — akibatnya semua baris setelah
 * pemisah ikut geser dan sebagian data/header jadi terpotong di file Excel.
 *
 * Solusinya: tulis cell satu per satu secara eksplisit lewat event AfterSheet,
 * supaya posisi setiap baris 100% pasti dan tidak bergantung pada baris kosong.
 *
 * Catatan perbaikan (file corrupt saat dibuka Excel):
 * Sebelumnya dipanggil `$sheet->freezePane('A1')`. Freeze pane di 'A1' itu
 * secara definisi berarti "tidak ada baris/kolom yang dibekukan" (xSplit dan
 * ySplit = 0), tapi PhpSpreadsheet tetap menulis atribut `state="frozen"` dan
 * `activePane="topRight"` pada XML-nya. Kombinasi ini tidak valid menurut
 * skema OOXML, sehingga Excel menampilkan peringatan "We found a problem
 * with some content..." dan mencoba melakukan repair. Perbaikannya: freeze
 * pane harus menunjuk ke baris/kolom SETELAH area yang mau dibekukan, jadi
 * dipakai 'A2' agar baris 1 (header judul) yang benar-benar dibekukan.
 */
class SiswaDetailExport implements WithEvents, WithColumnWidths
{
    private const LAST_COL = 'G';
    private const CURRENCY_FORMAT = '"Rp" #,##0';

    private const COLOR_PRIMARY = '1E3A5F';
    private const COLOR_PRIMARY_LIGHT = 'EAF0F6';
    private const COLOR_BORDER = 'D7DEE6';
    private const COLOR_SUCCESS = '15803D';
    private const COLOR_DANGER = 'DC2626';
    private const COLOR_TEXT_MUTED = '6B7280';

    private array $belumLunas = [];
    private array $lunas = [];
    private float $totalSisa = 0;
    private float $totalNominalKeseluruhan = 0;
    private float $totalSudahDibayarKeseluruhan = 0;

    public function __construct(private Siswa $siswa)
    {
        $riwayat = $siswa->tagihans->map(function ($tagihan) {
            $potongan = (float) ($tagihan->total_potongan ?? 0);
            $bayar = (float) ($tagihan->total_pembayaran ?? 0);
            $sisa = round(max(0, (float) $tagihan->nominal_awal - $potongan - $bayar), 2);

            return [
                'tagihan' => $tagihan,
                'potongan' => $potongan,
                'bayar' => $bayar,
                'sisa' => $sisa,
            ];
        });

        $sortKey = fn ($row) => [
            (int) ($row['tagihan']->periode_tahun ?? 0),
            (int) ($row['tagihan']->periode_bulan ?? 0),
        ];

        $this->belumLunas = $riwayat->filter(fn ($row) => $row['sisa'] > 0)
            ->sortBy($sortKey)->values()->all();

        $this->lunas = $riwayat->filter(fn ($row) => $row['sisa'] <= 0)
            ->sortBy($sortKey)->values()->all();

        $this->totalSisa = (float) array_sum(array_column($this->belumLunas, 'sisa'));
        $this->totalNominalKeseluruhan = (float) $riwayat->sum(fn ($row) => (float) $row['tagihan']->nominal_awal);
        $this->totalSudahDibayarKeseluruhan = (float) $riwayat->sum(fn ($row) => (float) $row['bayar']);
    }

    public function columnWidths(): array
    {
        return [
            'A' => 30,
            'B' => 16,
            'C' => 16,
            'D' => 15,
            'E' => 13,
            'F' => 16,
            'G' => 15,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->build($event->sheet->getDelegate());
            },
        ];
    }

    private function build(Worksheet $sheet): void
    {
        $lastCol = self::LAST_COL;
        $sheet->getDefaultRowDimension()->setRowHeight(18);
        $sheet->setShowGridlines(false);

        $row = 1;

        // ===== Banner judul =====
        $sheet->mergeCells('A' . $row . ':' . $lastCol . ($row + 1));
        $sheet->setCellValue('A' . $row, 'DETAIL RIWAYAT PEMBAYARAN SISWA');
        $sheet->getStyle('A' . $row . ':' . $lastCol . ($row + 1))->applyFromArray([
            'font' => ['bold' => true, 'size' => 16, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $sheet->getRowDimension($row + 1)->setRowHeight(24);
        $row += 2;

        $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
        $sheet->setCellValue('A' . $row, 'Dicetak pada ' . now()->translatedFormat('d F Y, H:i'));
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => self::COLOR_TEXT_MUTED]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $row += 2;

        // ===== Info siswa (kartu) =====
        $statusTagihan = $this->siswa->status_tagihan ?? '-';
        $kategoriLabel = [
            'mondok' => 'Mondok',
            'non_mondok' => 'Non Mondok',
            'alumni' => 'Alumni',
            'non_alumni' => 'Non Alumni',
        ][$this->siswa->kategori] ?? ucfirst(str_replace('_', ' ', (string) $this->siswa->kategori));

        $infoPairs = [
            ['NIS', (string) $this->siswa->nis],
            ['Nama', (string) $this->siswa->nama],
            ['Kelas', (string) $this->siswa->kelas],
            ['Kategori', $kategoriLabel],
            ['Status Siswa', ucwords((string) $this->siswa->status)],
            ['Status Pembayaran', ucwords(str_replace('_', ' ', (string) $statusTagihan))],
        ];

        $infoFirstRow = $row;

        foreach ($infoPairs as $i => [$label, $value]) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $sheet->mergeCells('B' . $row . ':' . $lastCol . $row);

            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => self::COLOR_PRIMARY]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY_LIGHT]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle('B' . $row . ':' . $lastCol . $row)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);

            if ($i % 2 === 1) {
                $sheet->getStyle('B' . $row . ':' . $lastCol . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                ]);
            }

            $row++;
        }

        $sheet->getStyle('A' . $infoFirstRow . ':' . $lastCol . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
        ]);

        $row += 2; // pemisah sebelum ringkasan

        // ===== Ringkasan total =====
        $ringkasan = [
            ['Total Seluruh Tagihan', $this->totalNominalKeseluruhan, self::COLOR_PRIMARY],
            ['Total Sudah Dibayar', $this->totalSudahDibayarKeseluruhan, self::COLOR_SUCCESS],
            ['Total Belum Dibayar (' . count($this->belumLunas) . ' item)', $this->totalSisa, self::COLOR_DANGER],
        ];

        $ringkasanFirstRow = $row;

        foreach ($ringkasan as [$label, $nilai, $warna]) {
            $sheet->setCellValue('A' . $row, $label);
            $sheet->mergeCells('A' . $row . ':' . 'D' . $row);
            $sheet->setCellValue('E' . $row, $nilai);
            $sheet->mergeCells('E' . $row . ':' . $lastCol . $row);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
            $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(22);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle('E' . $row)->getFont()->getColor()->setRGB($warna);
            $sheet->getStyle('E' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            $row++;
        }

        $sheet->getStyle('A' . $ringkasanFirstRow . ':' . $lastCol . ($row - 1))->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
        ]);

        $row += 2; // pemisah sebelum section tabel

        $row = $this->writeSection(
            $sheet,
            $row,
            'Riwayat Belum Lunas',
            ['Item', 'Kelas Tagihan', 'Periode', 'Nominal', 'Potongan', 'Sudah Dibayar', 'Sisa'],
            $this->belumLunas,
            'Tidak ada riwayat belum lunas.',
            true
        );

        $row++; // pemisah antar section

        $row = $this->writeSection(
            $sheet,
            $row,
            'Riwayat Lunas',
            ['Item', 'Kelas Tagihan', 'Periode', 'Nominal', 'Potongan', 'Sudah Dibayar', 'Status'],
            $this->lunas,
            'Belum ada riwayat lunas.',
            false
        );

        // Freeze pane sengaja TIDAK dipakai di sini — walau posisinya sudah
        // benar secara teknis (freeze di baris 3, setelah banner judul yang
        // di-merge), Excel tetap menampilkan area beku terpisah dari area
        // scroll, sehingga judul/header terlihat seperti dobel selama belum
        // di-scroll. Supaya tidak membingungkan, freeze pane dihilangkan.

        // Pengaturan cetak agar rapi & profesional saat di-print / PDF.
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
        $sheet->getSheetView()->setZoomScale(100);
        $sheet->getTabColor()->setRGB(self::COLOR_PRIMARY);
    }

    private function writeSection(
        Worksheet $sheet,
        int $row,
        string $title,
        array $headers,
        array $items,
        string $emptyMessage,
        bool $showSisa
    ): int {
        $lastCol = self::LAST_COL;

        // Judul section
        $sheet->setCellValue('A' . $row, $title);
        $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
        $sheet->getStyle('A' . $row)->applyFromArray([
            'font' => ['bold' => true, 'size' => 13, 'color' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(24);
        $row++;

        // Header tabel
        foreach ($headers as $i => $label) {
            $col = chr(ord('A') + $i);
            $sheet->setCellValue($col . $row, $label);
        }
        $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::COLOR_PRIMARY]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(22);
        $row++;

        // Baris data / pesan kosong
        if (empty($items)) {
            $sheet->setCellValue('A' . $row, $emptyMessage);
            $sheet->mergeCells('A' . $row . ':' . $lastCol . $row);
            $sheet->getStyle('A' . $row)->applyFromArray([
                'font' => ['italic' => true, 'color' => ['rgb' => self::COLOR_TEXT_MUTED]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;

            return $row;
        }

        $firstDataRow = $row;

        foreach ($items as $i => $item) {
            $tagihan = $item['tagihan'];
            $values = [
                $tagihan->itemPembayaran->nama_item ?? '-',
                $tagihan->kelas ?? '-',
                $tagihan->periode_label,
                (float) $tagihan->nominal_awal,
                (float) $item['potongan'],
                (float) $item['bayar'],
                $showSisa ? (float) $item['sisa'] : 'Lunas',
            ];

            foreach ($values as $c => $val) {
                $col = chr(ord('A') + $c);
                $sheet->setCellValue($col . $row, $val);
            }

            if ($i % 2 === 1) {
                $sheet->getStyle('A' . $row . ':' . $lastCol . $row)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F9FAFB']],
                ]);
            }

            $sheet->getStyle('G' . $row)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => $showSisa ? self::COLOR_DANGER : self::COLOR_SUCCESS]],
            ]);

            $row++;
        }

        $lastDataRow = $row - 1;

        $sheet->getStyle('A' . $firstDataRow . ':' . $lastCol . $lastDataRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle('A' . $firstDataRow . ':A' . $lastDataRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        foreach (['D', 'E', 'F'] as $col) {
            $sheet->getStyle($col . $firstDataRow . ':' . $col . $lastDataRow)->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
        }
        if ($showSisa) {
            $sheet->getStyle('G' . $firstDataRow . ':G' . $lastDataRow)->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
        }

        // Baris total sisa (khusus tabel belum lunas)
        if ($showSisa) {
            $totalRow = $row;
            $sheet->setCellValue('A' . $totalRow, 'Total Sisa Tagihan');
            $sheet->mergeCells('A' . $totalRow . ':' . 'F' . $totalRow);
            $sheet->setCellValue('G' . $totalRow, array_sum(array_column($items, 'sisa')));
            $sheet->getStyle('A' . $totalRow . ':' . $lastCol . $totalRow)->applyFromArray([
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF2F2']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => self::COLOR_BORDER]]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle('G' . $totalRow)->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
            $sheet->getStyle('G' . $totalRow)->getFont()->getColor()->setRGB(self::COLOR_DANGER);
            $row++;
        }

        return $row;
    }
}
