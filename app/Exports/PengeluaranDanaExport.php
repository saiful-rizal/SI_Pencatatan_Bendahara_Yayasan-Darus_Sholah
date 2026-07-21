<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Excel "Laporan Pengeluaran Dana".
 *
 * $rows adalah data yang SUDAH dipecah per-item (satu baris per item, bukan
 * per transaksi) - lihat BendaharaController::flattenPengeluaranDetail() -
 * supaya rincian nama item & jumlah x harga per item ikut ter-export, sama
 * persis seperti yang ditampilkan di hasil Cetak PDF.
 */
class PengeluaranDanaExport implements FromArray, WithStyles, WithColumnFormatting
{
    private const KOLOM_TERAKHIR = 'I';
    private const JUMLAH_KOLOM = 9;

    private int $barisHeaderTabel = 0;
    private int $barisAwalData = 0;
    private int $barisAkhirData = 0;
    private int $barisTotal = 0;
    private int $barisJudulRingkasan = 0;
    private int $barisHeaderRingkasan = 0;
    private int $barisAwalRingkasan = 0;
    private int $barisAkhirRingkasan = 0;

    /**
     * @param array $rows        Baris per-item hasil flattenPengeluaranDetail()
     * @param array $rekapJenis  ['Jenis Pengeluaran' => ['jumlah_transaksi' => int, 'total' => float]]
     */
    public function __construct(
        private array $rows,
        private array $rekapJenis,
        private float $totalKeseluruhan,
        private string $periodeLabel = 'Semua Periode'
    ) {
    }

    public function array(): array
    {
        $data = [];

        // ===== Kop laporan =====
        $data[] = ['SMA UNGGULAN BPPT DARUS SHOLAH'];
        $data[] = ['LAPORAN PENGELUARAN DANA'];
        $data[] = ['Periode: ' . $this->periodeLabel];
        $data[] = ['Dicetak: ' . now()->format('d-m-Y H:i')];
        $data[] = array_fill(0, self::JUMLAH_KOLOM, '');

        // ===== Header tabel detail (per item) =====
        $data[] = [
            'No',
            'No Transaksi',
            'Tanggal',
            'Jenis Pengeluaran',
            'Item',
            'Jumlah x Harga',
            'Keterangan Penggunaan Dana',
            'Penerima Dana / Pihak Dibayar',
            'Jumlah (Rp)',
        ];
        $this->barisHeaderTabel = count($data);

        // ===== Data detail per item =====
        foreach ($this->rows as $row) {
            $data[] = [
                $row['no'],
                $row['no_transaksi'],
                $row['tanggal'],
                $row['jenis'],
                $row['item'],
                $row['jumlah_label'],
                $row['keterangan'],
                $row['penerima'],
                (float) $row['nominal'],
            ];
        }

        $this->barisAwalData = $this->barisHeaderTabel + 1;
        $this->barisAkhirData = $this->barisHeaderTabel + count($this->rows);

        // ===== Baris total keseluruhan =====
        $data[] = ['', '', '', '', '', '', '', 'TOTAL PENGELUARAN', $this->totalKeseluruhan];
        $this->barisTotal = count($data);

        // ===== Ringkasan per jenis pengeluaran =====
        $rekapJenis = $this->rekapJenis;
        arsort($rekapJenis);

        $data[] = array_fill(0, self::JUMLAH_KOLOM, '');
        $data[] = ['RINGKASAN PER JENIS PENGELUARAN'];
        $this->barisJudulRingkasan = count($data);

        $data[] = ['No', 'Jenis Pengeluaran', '', '', '', 'Jumlah Transaksi', '', '', 'Total Nominal'];
        $this->barisHeaderRingkasan = count($data);

        $this->barisAwalRingkasan = $this->barisHeaderRingkasan + 1;
        $noRingkasan = 1;
        foreach ($rekapJenis as $jenis => $rekap) {
            $data[] = [
                $noRingkasan++,
                $jenis,
                '', '', '',
                $rekap['jumlah_transaksi'],
                '', '',
                $rekap['total'],
            ];
        }
        $this->barisAkhirRingkasan = $this->barisAwalRingkasan + count($rekapJenis) - 1;

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $kolomTerakhir = self::KOLOM_TERAKHIR;

        // Lebar kolom tetap (bukan auto-size) supaya nama item, keterangan, dan
        // nominal panjang tidak terpotong.
        $sheet->getColumnDimension('A')->setWidth(6);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(13);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(26);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(32);
        $sheet->getColumnDimension('H')->setWidth(22);
        $sheet->getColumnDimension('I')->setWidth(20);
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
        $sheet->setShowGridlines(false);

        // --- Kop ---
        $sheet->mergeCells('A1:' . $kolomTerakhir . '1');
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('A2:' . $kolomTerakhir . '2');
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1F3B61']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        foreach ([3, 4] as $barisInfo) {
            $sheet->mergeCells('A' . $barisInfo . ':' . $kolomTerakhir . $barisInfo);
            $sheet->getStyle('A' . $barisInfo)->applyFromArray([
                'font' => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '555555']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // --- Header tabel detail ---
        $sheet->getStyle('A' . $this->barisHeaderTabel . ':' . $kolomTerakhir . $this->barisHeaderTabel)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B91C1C']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B91C1C']]],
        ]);
        $sheet->getRowDimension($this->barisHeaderTabel)->setRowHeight(26);

        // --- Data detail ---
        if (count($this->rows) > 0) {
            $dataRange = 'A' . $this->barisAwalData . ':' . $kolomTerakhir . $this->barisAkhirData;
            $sheet->getStyle($dataRange)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
            ]);

            // Kolom teks panjang rata kiri + wrap supaya enak dibaca & tidak kepotong
            $sheet->getStyle('E' . $this->barisAwalData . ':E' . $this->barisAkhirData)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
            $sheet->getStyle('G' . $this->barisAwalData . ':G' . $this->barisAkhirData)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
            $sheet->getStyle('H' . $this->barisAwalData . ':H' . $this->barisAkhirData)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('I' . $this->barisAwalData . ':I' . $this->barisAkhirData)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            // Zebra baris
            for ($baris = $this->barisAwalData; $baris <= $this->barisAkhirData; $baris++) {
                if (($baris - $this->barisAwalData) % 2 === 1) {
                    $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF6F6']],
                    ]);
                }
            }
        }

        // --- Baris total keseluruhan ---
        $sheet->mergeCells('A' . $this->barisTotal . ':H' . $this->barisTotal);
        $sheet->getStyle('A' . $this->barisTotal . ':' . $kolomTerakhir . $this->barisTotal)->applyFromArray([
            'font' => ['bold' => true, 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDEDED']],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B91C1C']],
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'B91C1C']],
            ],
        ]);
        $sheet->getStyle('I' . $this->barisTotal)->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'font' => ['color' => ['rgb' => 'B91C1C']],
        ]);

        // --- Judul & header ringkasan per jenis pengeluaran ---
        $sheet->mergeCells('A' . $this->barisJudulRingkasan . ':' . $kolomTerakhir . $this->barisJudulRingkasan);
        $sheet->getRowDimension($this->barisJudulRingkasan)->setRowHeight(20);
        $sheet->getStyle('A' . $this->barisJudulRingkasan)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'B91C1C']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('B' . $this->barisHeaderRingkasan . ':E' . $this->barisHeaderRingkasan);
        $sheet->mergeCells('F' . $this->barisHeaderRingkasan . ':H' . $this->barisHeaderRingkasan);
        $sheet->getStyle('A' . $this->barisHeaderRingkasan . ':' . $kolomTerakhir . $this->barisHeaderRingkasan)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B91C1C']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B91C1C']]],
        ]);

        if ($this->barisAkhirRingkasan >= $this->barisAwalRingkasan) {
            for ($baris = $this->barisAwalRingkasan; $baris <= $this->barisAkhirRingkasan; $baris++) {
                $sheet->mergeCells('B' . $baris . ':E' . $baris);
                $sheet->mergeCells('F' . $baris . ':H' . $baris);
            }

            $sheet->getStyle('A' . $this->barisAwalRingkasan . ':' . $kolomTerakhir . $this->barisAkhirRingkasan)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
            ]);

            $sheet->getStyle('B' . $this->barisAwalRingkasan . ':B' . $this->barisAkhirRingkasan)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('I' . $this->barisAwalRingkasan . ':I' . $this->barisAkhirRingkasan)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            for ($baris = $this->barisAwalRingkasan; $baris <= $this->barisAkhirRingkasan; $baris++) {
                if (($baris - $this->barisAwalRingkasan) % 2 === 1) {
                    $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEF6F6']],
                    ]);
                }
            }
        }

        // Kotak tebal mengelilingi seluruh isi laporan
        $barisTerakhir = max($this->barisAkhirRingkasan, $this->barisTotal);
        $sheet->getStyle('A5:' . $kolomTerakhir . $barisTerakhir)->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1E293B']]],
        ]);

        // Page setup: landscape, fit ke lebar 1 halaman
        $sheet->getPageSetup()
            ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.6)->setLeft(0.4)->setRight(0.4);

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'I' . $this->barisAwalData . ':I' . $this->barisTotal => '"Rp" #,##0',
            'I' . $this->barisAwalRingkasan . ':I' . $this->barisAkhirRingkasan => '"Rp" #,##0',
        ];
    }
}
