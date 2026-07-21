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
 * Export Excel "Rekapitulasi Kas".
 *
 * Berbeda dari sebelumnya (hanya total per kategori), sekarang setiap kategori
 * di-breakdown sampai ke level transaksi (tanggal, item, nominal per item) -
 * sama seperti rincian yang sudah ada di Laporan Yayasan - supaya bendahara
 * bisa langsung menelusuri dari mana angka rekap tersebut berasal tanpa perlu
 * buka laporan lain.
 */
class RekapKasExport implements FromArray, WithStyles, WithColumnFormatting
{
    private const KOLOM_TERAKHIR = 'G';
    private const JUMLAH_KOLOM = 7;

    /** @var array<int> baris judul section (untuk pewarnaan, merge penuh A:G) */
    private array $barisJudulSection = [];

    /** @var array<int> baris header tabel rincian kategori (7 kolom) */
    private array $barisHeaderDetail = [];

    /** @var array<int> baris header tabel tren (4 kolom) */
    private array $barisHeaderTren = [];

    /** @var array<int> baris subtotal per kategori */
    private array $barisSubtotal = [];

    /** @var array<int> baris total section (TOTAL PEMASUKAN/PENGELUARAN) */
    private array $barisTotal = [];

    /** @var array<int> baris detail transaksi (untuk zebra-striping) */
    private array $barisDetail = [];

    private int $barisRingkasanAwal = 0;
    private int $barisRingkasanAkhir = 0;
    private int $barisTrenAwal = 0;
    private int $barisTrenAkhir = 0;

    /** @var array<int,array{0:string,1:int,2:int}> [kolom, baris_awal, baris_akhir] untuk format Rupiah */
    private array $rangeDataUang = [];

    public function __construct(private string $periodeLabel, private array $ringkasanKas)
    {
    }

    public function array(): array
    {
        $data = [];

        // ===== Kop laporan =====
        $data[] = ['SMA UNGGULAN BPPT DARUS SHOLAH'];
        $data[] = ['LAPORAN REKAPITULASI KEUANGAN / KAS'];
        $data[] = ['Periode: ' . $this->periodeLabel];
        $data[] = ['Dicetak: ' . now()->format('d-m-Y H:i')];
        $data[] = array_fill(0, self::JUMLAH_KOLOM, '');

        // ===== Ringkasan utama =====
        $data[] = ['RINGKASAN POSISI KAS'];
        $this->barisJudulSection[] = count($data);

        $this->barisRingkasanAwal = count($data) + 1;
        $data[] = $this->baris7('Saldo Awal Kas', (float) $this->ringkasanKas['saldoAwalKas']);
        $data[] = $this->baris7('Total Pemasukan Dana', (float) $this->ringkasanKas['totalPemasukan']);
        $data[] = $this->baris7('Total Pengeluaran Dana', (float) $this->ringkasanKas['totalPengeluaran']);
        $data[] = $this->baris7('Saldo Akhir Kas', (float) $this->ringkasanKas['saldoAkhirKas']);
        $this->barisRingkasanAkhir = count($data);
        $this->rangeDataUang[] = ['G', $this->barisRingkasanAwal, $this->barisRingkasanAkhir];

        // ===== Rincian lengkap PEMASUKAN (per kategori -> per transaksi) =====
        $rincianPemasukan = $this->ringkasanKas['rincianPemasukan'] ?? [];
        $detailPemasukan = $this->ringkasanKas['detailPemasukan'] ?? collect();

        $data[] = array_fill(0, self::JUMLAH_KOLOM, '');
        $data[] = ['I. RINCIAN PEMASUKAN PER KATEGORI'];
        $this->barisJudulSection[] = count($data);

        $data[] = ['No', 'Tanggal', 'Nama Siswa / Uraian', 'Kelas', 'Item / Keterangan', 'Metode Bayar', 'Jumlah (Rp)'];
        $this->barisHeaderDetail[] = count($data);

        $no = 1;
        foreach ($rincianPemasukan as $kategori) {
            $namaKategori = $kategori['kategori'];
            $data[] = [$namaKategori, '', '', '', '', 'Subtotal (' . $kategori['jumlah_transaksi'] . ' transaksi)', (float) $kategori['total']];
            $this->barisSubtotal[] = count($data);
            $this->rangeDataUang[] = ['G', count($data), count($data)];

            foreach (($detailPemasukan[$namaKategori] ?? []) as $r) {
                $data[] = [$no, $r['tanggal'], $r['nama_siswa'], $r['kelas'], $r['item'], $r['metode'] ?? '-', (float) $r['nominal']];
                $this->barisDetail[] = count($data);
                $this->rangeDataUang[] = ['G', count($data), count($data)];
                $no++;
            }
        }
        $data[] = ['TOTAL PEMASUKAN', '', '', '', '', '', (float) $this->ringkasanKas['totalPemasukan']];
        $this->barisTotal[] = count($data);
        $this->rangeDataUang[] = ['G', count($data), count($data)];

        // ===== Rincian lengkap PENGELUARAN (per kategori -> per transaksi) =====
        $rincianPengeluaran = $this->ringkasanKas['rincianPengeluaran'] ?? [];
        $detailPengeluaran = $this->ringkasanKas['detailPengeluaran'] ?? collect();

        $data[] = array_fill(0, self::JUMLAH_KOLOM, '');
        $data[] = ['II. RINCIAN PENGELUARAN PER KATEGORI'];
        $this->barisJudulSection[] = count($data);

        $data[] = ['No', 'Tanggal', 'Item', 'Jumlah x Harga', 'Keterangan', 'Dicatat Oleh', 'Jumlah (Rp)'];
        $this->barisHeaderDetail[] = count($data);

        $no = 1;
        foreach ($rincianPengeluaran as $kategori) {
            $namaKategori = $kategori['kategori'];
            $data[] = [$namaKategori, '', '', '', '', 'Subtotal (' . $kategori['jumlah_transaksi'] . ' transaksi)', (float) $kategori['total']];
            $this->barisSubtotal[] = count($data);
            $this->rangeDataUang[] = ['G', count($data), count($data)];

            foreach (($detailPengeluaran[$namaKategori] ?? []) as $r) {
                $data[] = [$no, $r['tanggal'], $r['item'], $r['jumlah_label'], $r['keterangan'], $r['dicatat_oleh'] ?? '-', (float) $r['nominal']];
                $this->barisDetail[] = count($data);
                $this->rangeDataUang[] = ['G', count($data), count($data)];
                $no++;
            }
        }
        $data[] = ['TOTAL PENGELUARAN', '', '', '', '', '', (float) $this->ringkasanKas['totalPengeluaran']];
        $this->barisTotal[] = count($data);
        $this->rangeDataUang[] = ['G', count($data), count($data)];

        // ===== Tren arus kas harian =====
        $tren = $this->ringkasanKas['tren'] ?? ['label' => [], 'masuk' => [], 'keluar' => [], 'saldo' => []];
        if (!empty($tren['label'])) {
            $data[] = array_fill(0, self::JUMLAH_KOLOM, '');
            $data[] = ['III. TREN ARUS KAS HARIAN'];
            $this->barisJudulSection[] = count($data);

            $data[] = ['Tanggal', 'Pemasukan', 'Pengeluaran', 'Saldo Berjalan'];
            $this->barisHeaderTren[] = count($data);

            $this->barisTrenAwal = count($data) + 1;
            foreach ($tren['label'] as $i => $label) {
                $data[] = [
                    $label,
                    (float) ($tren['masuk'][$i] ?? 0),
                    (float) ($tren['keluar'][$i] ?? 0),
                    (float) ($tren['saldo'][$i] ?? 0),
                ];
            }
            $this->barisTrenAkhir = count($data);
            $this->rangeDataUang[] = ['B', $this->barisTrenAwal, $this->barisTrenAkhir];
            $this->rangeDataUang[] = ['C', $this->barisTrenAwal, $this->barisTrenAkhir];
            $this->rangeDataUang[] = ['D', $this->barisTrenAwal, $this->barisTrenAkhir];
        }

        return $data;
    }

    /** Baris ringkasan: label di A (nanti di-merge A:F), nilai Rupiah di G */
    private function baris7(string $label, float $nilai): array
    {
        return [$label, '', '', '', '', '', $nilai];
    }

    public function styles(Worksheet $sheet): array
    {
        $kolomTerakhir = self::KOLOM_TERAKHIR;

        // Lebar kolom tetap (bukan auto-size) - auto-size tidak akurat untuk sel
        // yang di-merge (kop, judul section, label ringkasan, subtotal kategori),
        // sehingga sebelumnya kolom sempit dan teks/data bisa terpotong.
        $sheet->getColumnDimension('A')->setWidth(26);
        $sheet->getColumnDimension('B')->setWidth(13);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(34);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
        $sheet->setShowGridlines(false);

        // --- Kop ---
        $sheet->mergeCells('A1:' . $kolomTerakhir . '1');
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
        ]);

        $sheet->mergeCells('A2:' . $kolomTerakhir . '2');
        $sheet->getRowDimension(2)->setRowHeight(20);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'CBD5E1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
        ]);

        foreach ([3, 4] as $barisInfo) {
            $sheet->mergeCells('A' . $barisInfo . ':' . $kolomTerakhir . $barisInfo);
            $sheet->getStyle('A' . $barisInfo)->applyFromArray([
                'font' => ['italic' => true, 'size' => 10, 'color' => ['rgb' => '555555']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
        }

        // --- Judul tiap section (banner penuh A:G) ---
        foreach ($this->barisJudulSection as $baris) {
            $sheet->mergeCells('A' . $baris . ':' . $kolomTerakhir . $baris);
            $sheet->getRowDimension($baris)->setRowHeight(22);
            $sheet->getStyle('A' . $baris)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3B61']],
            ]);
        }

        // --- Baris ringkasan (label A:F, nilai di G) ---
        for ($baris = $this->barisRingkasanAwal; $baris <= $this->barisRingkasanAkhir; $baris++) {
            $sheet->mergeCells('A' . $baris . ':F' . $baris);
            $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                'font' => ['bold' => true],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
            ]);
            $sheet->getStyle('A' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            if (($baris - $this->barisRingkasanAwal) % 2 === 1) {
                $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EAF2FF']],
                ]);
            }
        }
        // Baris saldo akhir ditonjolkan
        $sheet->getStyle('A' . $this->barisRingkasanAkhir . ':' . $kolomTerakhir . $this->barisRingkasanAkhir)->applyFromArray([
            'font' => ['bold' => true, 'size' => 12],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCEBFF']],
        ]);

        // --- Header tabel rincian (7 kolom) ---
        foreach ($this->barisHeaderDetail as $baris) {
            $sheet->getRowDimension($baris)->setRowHeight(22);
            $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']]],
            ]);
        }

        // --- Header tabel tren (4 kolom) ---
        foreach ($this->barisHeaderTren as $baris) {
            $sheet->getRowDimension($baris)->setRowHeight(20);
            $sheet->getStyle('A' . $baris . ':D' . $baris)->applyFromArray([
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '334155']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '334155']]],
            ]);
        }

        // --- Baris subtotal kategori (merge A:F, label rata kanan, warna abu) ---
        foreach ($this->barisSubtotal as $baris) {
            $sheet->mergeCells('A' . $baris . ':E' . $baris);
            $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                'font' => ['bold' => true, 'italic' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F7']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
            ]);
            $sheet->getStyle('A' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('F' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // --- Baris total section (TOTAL PEMASUKAN/PENGELUARAN) ---
        foreach ($this->barisTotal as $baris) {
            $sheet->mergeCells('A' . $baris . ':F' . $baris);
            $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                'font' => ['bold' => true, 'size' => 11],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FDF3D9']],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'C9A227']],
                    'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'C9A227']],
                ],
            ]);
            $sheet->getStyle('G' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // --- Baris detail transaksi: alignment kolom + zebra-striping ---
        foreach (array_values($this->barisDetail) as $i => $baris) {
            $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E2E8F0']]],
            ]);
            $sheet->getStyle('A' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('C' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
            $sheet->getStyle('D' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('E' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);
            $sheet->getStyle('F' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            if ($i % 2 === 1) {
                $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                ]);
            }
        }

        // --- Tabel tren: border + alignment ---
        if ($this->barisTrenAwal > 0) {
            $sheet->getStyle('A' . $this->barisTrenAwal . ':D' . $this->barisTrenAkhir)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']]],
            ]);
            for ($baris = $this->barisTrenAwal; $baris <= $this->barisTrenAkhir; $baris++) {
                if (($baris - $this->barisTrenAwal) % 2 === 1) {
                    $sheet->getStyle('A' . $baris . ':D' . $baris)->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7F9FC']],
                    ]);
                }
            }
        }

        // Kotak tebal mengelilingi seluruh isi laporan
        $barisTerakhir = max(
            $this->barisTotal[count($this->barisTotal) - 1] ?? $this->barisRingkasanAkhir,
            $this->barisTrenAkhir
        );
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
        $formats = [];
        foreach ($this->rangeDataUang as [$kolom, $awal, $akhir]) {
            $formats[$kolom . $awal . ':' . $kolom . $akhir] = '"Rp" #,##0';
        }

        return $formats;
    }
}
