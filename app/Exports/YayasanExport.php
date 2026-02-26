<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class YayasanExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnFormatting
{
    protected $reportMasuk, $reportKeluar, $totalMasuk, $totalKeluar, $saldo;

    public function __construct($reportMasuk, $reportKeluar, $totalMasuk, $totalKeluar, $saldo)
    {
        $this->reportMasuk = $reportMasuk;
        $this->reportKeluar = $reportKeluar;
        $this->totalMasuk = $totalMasuk;
        $this->totalKeluar = $totalKeluar;
        $this->saldo = $saldo;
    }

    public function collection()
    {
        $data = [];

        // 1. Bagian Pemasukan
        $data[] = ['REKAPITULASI PEMASUKAN'];
        $data[] = ['Kategori', 'Jumlah (Rp)'];
        foreach ($this->reportMasuk as $kategori => $jumlah) {
            $data[] = [$kategori, $jumlah];
        }
        $data[] = ['TOTAL PEMASUKAN', $this->totalMasuk];
        $data[] = []; // Baris Kosong

        // 2. Bagian Pengeluaran
        $data[] = ['REKAPITULASI PENGELUARAN'];
        $data[] = ['Kategori', 'Jumlah (Rp)'];
        foreach ($this->reportKeluar as $kategori => $jumlah) {
            $data[] = [$kategori, $jumlah];
        }
        $data[] = ['TOTAL PENGELUARAN', $this->totalKeluar];
        $data[] = []; // Baris Kosong

        // 3. Bagian Saldo
        $data[] = ['LABA / RUGI BERSIH', $this->saldo];

        return collect($data);
    }

    public function headings(): array
    {
        // Kita tidak menggunakan heading standar karena kita membuat custom di collection
        // Tapi interface ini wajib ada, kita return array kosong
        return [];
    }

    public function columnFormats(): array
    {
        return [
            'B' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // Format kolom B (Rp)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styling Umum
        $sheet->getStyle('A:B')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getColumnDimension('A')->setWidth(30);
        $sheet->getColumnDimension('B')->setWidth(25);

        // Mencari baris untuk styling (Hardcoded berdasarkan struktur collection)
        // Baris 1 (Pemasukan Header)
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D1FAE5']]
        ]);

        // Mencari baris Total Pemasukan (dinamis tergantung jumlah data)
        $rowMasukTotal = 3 + $this->reportMasuk->count();
        $sheet->getStyle('A' . $rowMasukTotal . ':B' . $rowMasukTotal)->applyFromArray([
            'font' => ['bold' => true],
            'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM]]
        ]);

        // Baris Pengeluaran Header
        $rowKeluarStart = $rowMasukTotal + 2;
        $sheet->getStyle('A' . $rowKeluarStart . ':B' . $rowKeluarStart)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']]
        ]);

        // Baris Total Pengeluaran
        $rowKeluarTotal = $rowKeluarStart + 2 + $this->reportKeluar->count();
        $sheet->getStyle('A' . $rowKeluarTotal . ':B' . $rowKeluarTotal)->applyFromArray([
            'font' => ['bold' => true],
            'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM]]
        ]);

        // Baris Saldo Akhir
        $rowSaldo = $rowKeluarTotal + 2;
        $sheet->getStyle('A' . $rowSaldo . ':B' . $rowSaldo)->applyFromArray([
            'font' => ['bold' => true, 'size' => 14],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E0E7FF']]
        ]);

        // Rata kanan kolom angka
        $sheet->getStyle('B2:B' . $rowSaldo)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        return [];
    }

    public function title(): string
    {
        return 'Laporan Yayasan';
    }
}
