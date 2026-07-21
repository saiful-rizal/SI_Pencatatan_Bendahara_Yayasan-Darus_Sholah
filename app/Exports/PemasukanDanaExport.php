<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PemasukanDanaExport implements FromArray, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    private const KOLOM_TERAKHIR = 'J';
    private const JUMLAH_KOLOM = 10;

    private int $barisHeaderTabel = 0;
    private int $barisAwalData = 0;
    private int $barisAkhirData = 0;
    private int $barisTotal = 0;
    private int $barisJudulRingkasan = 0;
    private int $barisHeaderRingkasan = 0;
    private int $barisAwalRingkasan = 0;
    private int $barisAkhirRingkasan = 0;

    public function __construct(private Collection $rows, private string $periodeLabel = 'Semua Periode', private ?string $keywordFilter = null)
    {
    }

    public function array(): array
    {
        $data = [];

        // ===== Kop laporan =====
        $data[] = ['SMA UNGGULAN BPPT DARUS SHOLAH'];
        $data[] = ['LAPORAN PEMASUKAN DANA'];
        $data[] = ['Periode: ' . $this->periodeLabel . (!empty($this->keywordFilter) ? '   |   Filter Keterangan: ' . $this->keywordFilter : '')];
        $data[] = ['Dicetak: ' . now()->format('d-m-Y H:i')];
        $data[] = array_fill(0, self::JUMLAH_KOLOM, '');

        // ===== Header tabel detail =====
        $data[] = [
            'No',
            'No Transaksi',
            'Tanggal',
            'NIS',
            'Nama Pemberi Dana',
            'Kelas',
            'Item Pembayaran',
            'Periode',
            'Metode Bayar',
            'Jumlah Dana Diterima',
        ];
        $this->barisHeaderTabel = count($data);

        // ===== Data detail =====
        $no = 1;
        $rekapItem = [];

        foreach ($this->rows as $row) {
            $metode = $row->pembayaranTagihan->metode_bayar ?? null;
            $jumlah = (float) $row->total_bayar;

            $itemPembayaran = $row->details
                ->pluck('nama_item')
                ->filter()
                ->unique()
                ->implode(', ');
            if ($itemPembayaran === '') {
                $itemPembayaran = optional(optional($row->pembayaranTagihan)->tagihan)->itemPembayaran->nama_item ?? ($row->kategori ?: '-');
            }

            $periode = optional(optional($row->pembayaranTagihan)->tagihan)->periode_label ?? '-';

            $noTransaksi = 'TRX' . optional($row->tanggal)->format('ymd') . str_pad((string) $row->id, 4, '0', STR_PAD_LEFT);

            $data[] = [
                $no++,
                $noTransaksi,
                optional($row->tanggal)->format('d-m-Y'),
                $row->siswa->nis ?? '-',
                $row->nama_siswa ?: '-',
                $row->kelas ?: '-',
                $itemPembayaran,
                $periode,
                $metode ? strtoupper($metode) : '-',
                $jumlah,
            ];

            $keyRekap = $itemPembayaran !== '' ? $itemPembayaran : '-';
            if (!isset($rekapItem[$keyRekap])) {
                $rekapItem[$keyRekap] = ['jumlah_transaksi' => 0, 'total' => 0.0];
            }
            $rekapItem[$keyRekap]['jumlah_transaksi']++;
            $rekapItem[$keyRekap]['total'] += $jumlah;
        }

        // Urutkan ringkasan dari nominal terbesar supaya item paling signifikan langsung terlihat
        arsort($rekapItem);

        $this->barisAwalData = $this->barisHeaderTabel + 1;
        $this->barisAkhirData = $this->barisHeaderTabel + $this->rows->count();

        // ===== Baris total keseluruhan =====
        $totalKeseluruhan = (float) $this->rows->sum('total_bayar');
        $data[] = ['', '', '', '', '', '', '', '', 'TOTAL PEMASUKAN', $totalKeseluruhan];
        $this->barisTotal = count($data);

        // ===== Ringkasan per item pembayaran (biar cepat dipahami tanpa hitung manual) =====
        $data[] = array_fill(0, self::JUMLAH_KOLOM, '');
        $data[] = ['RINGKASAN PER ITEM PEMBAYARAN'];
        $this->barisJudulRingkasan = count($data);

        $data[] = ['No', 'Item Pembayaran', '', '', '', 'Jumlah Transaksi', '', '', '', 'Total Nominal'];
        $this->barisHeaderRingkasan = count($data);

        $this->barisAwalRingkasan = $this->barisHeaderRingkasan + 1;
        $noRingkasan = 1;
        foreach ($rekapItem as $item => $rekap) {
            $data[] = [
                $noRingkasan++,
                $item,
                '',
                '',
                '',
                $rekap['jumlah_transaksi'],
                '',
                '',
                '',
                $rekap['total'],
            ];
        }
        $this->barisAkhirRingkasan = $this->barisAwalRingkasan + count($rekapItem) - 1;

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $kolomTerakhir = self::KOLOM_TERAKHIR;

        // --- Kop ---
        $sheet->mergeCells('A1:' . $kolomTerakhir . '1');
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
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3B61']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1F3B61']]],
        ]);
        $sheet->getRowDimension($this->barisHeaderTabel)->setRowHeight(22);

        // --- Data detail ---
        if ($this->rows->count() > 0) {
            $dataRange = 'A' . $this->barisAwalData . ':' . $kolomTerakhir . $this->barisAkhirData;
            $sheet->getStyle($dataRange)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
            ]);

            // Kolom Nama Pemberi Dana & Item Pembayaran rata kiri agar teks panjang tetap enak dibaca
            foreach (['E', 'G'] as $kolomTeks) {
                $sheet->getStyle($kolomTeks . $this->barisAwalData . ':' . $kolomTeks . $this->barisAkhirData)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }

            // Zebra baris
            for ($baris = $this->barisAwalData; $baris <= $this->barisAkhirData; $baris++) {
                if (($baris - $this->barisAwalData) % 2 === 1) {
                    $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7FAFF']],
                    ]);
                }
            }
        }

        // --- Baris total keseluruhan ---
        $sheet->mergeCells('A' . $this->barisTotal . ':H' . $this->barisTotal);
        $sheet->getStyle('A' . $this->barisTotal . ':' . $kolomTerakhir . $this->barisTotal)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF2FF']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1F3B61']]],
        ]);
        $sheet->getStyle('J' . $this->barisTotal)->getFont()->getColor()->setRGB('1E8449');

        // --- Judul & header ringkasan per item pembayaran ---
        $sheet->mergeCells('A' . $this->barisJudulRingkasan . ':' . $kolomTerakhir . $this->barisJudulRingkasan);
        $sheet->getStyle('A' . $this->barisJudulRingkasan)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '1F3B61']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells('B' . $this->barisHeaderRingkasan . ':E' . $this->barisHeaderRingkasan);
        $sheet->mergeCells('F' . $this->barisHeaderRingkasan . ':I' . $this->barisHeaderRingkasan);
        $sheet->getStyle('A' . $this->barisHeaderRingkasan . ':' . $kolomTerakhir . $this->barisHeaderRingkasan)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3B61']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1F3B61']]],
        ]);

        if ($this->barisAkhirRingkasan >= $this->barisAwalRingkasan) {
            for ($baris = $this->barisAwalRingkasan; $baris <= $this->barisAkhirRingkasan; $baris++) {
                $sheet->mergeCells('B' . $baris . ':E' . $baris);
                $sheet->mergeCells('F' . $baris . ':I' . $baris);
            }

            $sheet->getStyle('A' . $this->barisAwalRingkasan . ':' . $kolomTerakhir . $this->barisAkhirRingkasan)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
            ]);

            // Item pembayaran pada ringkasan rata kiri agar nama panjang tetap terbaca
            $sheet->getStyle('B' . $this->barisAwalRingkasan . ':B' . $this->barisAkhirRingkasan)
                ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            for ($baris = $this->barisAwalRingkasan; $baris <= $this->barisAkhirRingkasan; $baris++) {
                if (($baris - $this->barisAwalRingkasan) % 2 === 1) {
                    $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7FAFF']],
                    ]);
                }
            }
        }

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'J' . $this->barisAwalData . ':J' . $this->barisTotal => '"Rp." #,##0',
            'J' . $this->barisAwalRingkasan . ':J' . $this->barisAkhirRingkasan => '"Rp." #,##0',
        ];
    }
}
