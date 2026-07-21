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

class RekapExport implements FromArray, WithColumnFormatting, ShouldAutoSize, WithStyles
{
    private const KOLOM_TERAKHIR = 'N';
    private const JUMLAH_KOLOM = 14;

    private int $barisHeaderTabel = 0;
    private int $barisAwalData = 0;
    private int $barisAkhirData = 0;
    private int $barisTotal = 0;

    public function __construct(private Collection $rows, private array $filterInfo = [])
    {
    }

    public function array(): array
    {
        $data = [];

        // ===== Kop / Judul (hanya satu kali, tidak diulang di bawah) =====
        $data[] = ['SMA UNGGULAN BPPT DARUS SHOLAH'];
        $data[] = ['REKAP KEUANGAN SISWA'];
        $data[] = [$this->buildKeteranganFilter()];
        $data[] = ['Dicetak: ' . now()->format('d-m-Y H:i') . '   |   Oleh: ' . ($this->filterInfo['dicetak_oleh'] ?? '-')];
        $data[] = array_fill(0, self::JUMLAH_KOLOM, '');

        // ===== Header tabel =====
        $data[] = [
            'No',
            'NIS',
            'Nama Siswa',
            'Kelas',
            'Angkatan',
            'Item Pembayaran',
            'Periode',
            'Nominal Awal',
            'Potongan',
            'Total Akhir',
            'Pembayaran',
            'Sisa',
            'Status',
            'Keterangan Potongan',
        ];
        $this->barisHeaderTabel = count($data);

        // ===== Data detail per item pembayaran =====
        $no = 1;
        $totalNominalAwal = 0.0;
        $totalPotongan = 0.0;
        $totalAkhirSemua = 0.0;
        $totalPembayaran = 0.0;
        $totalSisa = 0.0;

        foreach ($this->rows as $tagihan) {
            $potongan = (float) ($tagihan->total_potongan ?? 0);
            $bayar = (float) ($tagihan->total_pembayaran ?? 0);
            $totalAkhir = max(0, (float) $tagihan->nominal_awal - $potongan);
            $sisa = max(0, $totalAkhir - $bayar);
            $keteranganPotongan = collect($tagihan->potongans ?? [])
                ->pluck('keterangan')
                ->filter()
                ->unique()
                ->implode(', ');

            $data[] = [
                $no++,
                $tagihan->siswa->nis ?? '-',
                $tagihan->siswa->nama ?? '-',
                $this->kelasUntukExport((string) ($tagihan->siswa->kelas ?? '-')),
                $tagihan->siswa->angkatan ?? '-',
                $tagihan->itemPembayaran->nama_item ?? '-',
                $tagihan->periode_label ?? '-',
                (float) $tagihan->nominal_awal,
                $potongan,
                $totalAkhir,
                $bayar,
                $sisa,
                $sisa <= 0 ? 'Lunas' : 'Belum Lunas',
                $keteranganPotongan !== '' ? $keteranganPotongan : '-',
            ];

            $totalNominalAwal += (float) $tagihan->nominal_awal;
            $totalPotongan += $potongan;
            $totalAkhirSemua += $totalAkhir;
            $totalPembayaran += $bayar;
            $totalSisa += $sisa;
        }

        $this->barisAwalData = $this->barisHeaderTabel + 1;
        $this->barisAkhirData = $this->barisHeaderTabel + $this->rows->count();

        // ===== Satu-satunya baris ringkasan/total (tidak ada blok ringkasan lain di atas) =====
        $data[] = [
            '', '', 'TOTAL KESELURUHAN', '', '', '', '',
            $totalNominalAwal,
            $totalPotongan,
            $totalAkhirSemua,
            $totalPembayaran,
            $totalSisa,
            '',
            '',
        ];
        $this->barisTotal = count($data);

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $kolomTerakhir = self::KOLOM_TERAKHIR;

        // --- Kop sekolah ---
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

        // --- Header tabel ---
        $headerRange = 'A' . $this->barisHeaderTabel . ':' . $kolomTerakhir . $this->barisHeaderTabel;
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1F3B61'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1F3B61']],
            ],
        ]);
        $sheet->getRowDimension($this->barisHeaderTabel)->setRowHeight(22);

        // --- Data tabel ---
        if ($this->rows->count() > 0) {
            $dataRange = 'A' . $this->barisAwalData . ':' . $kolomTerakhir . $this->barisAkhirData;
            $sheet->getStyle($dataRange)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9D9D9']],
                ],
            ]);

            // Kolom teks panjang rata kiri supaya lebih enak dibaca
            foreach (['C', 'F', 'N'] as $kolomTeks) {
                $sheet->getStyle($kolomTeks . $this->barisAwalData . ':' . $kolomTeks . $this->barisAkhirData)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }

            // Zebra baris supaya mudah dibaca per baris
            for ($baris = $this->barisAwalData; $baris <= $this->barisAkhirData; $baris++) {
                if (($baris - $this->barisAwalData) % 2 === 1) {
                    $sheet->getStyle('A' . $baris . ':' . $kolomTerakhir . $baris)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F7FAFF'],
                        ],
                    ]);
                }
            }

            // Status pembayaran per baris: hijau untuk Lunas, merah untuk Belum Lunas
            foreach ($this->rows as $index => $tagihan) {
                $baris = $this->barisAwalData + $index;
                $potongan = (float) ($tagihan->total_potongan ?? 0);
                $bayar = (float) ($tagihan->total_pembayaran ?? 0);
                $totalAkhir = max(0, (float) $tagihan->nominal_awal - $potongan);
                $sisa = max(0, $totalAkhir - $bayar);

                if ($sisa > 0) {
                    $sheet->getStyle('L' . $baris)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'C0392B']],
                    ]);
                    $sheet->getStyle('M' . $baris)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'C0392B']],
                    ]);
                } else {
                    $sheet->getStyle('M' . $baris)->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '1E8449']],
                    ]);
                }
            }
        }

        // --- Baris total (satu-satunya ringkasan angka di file ini) ---
        $sheet->mergeCells('C' . $this->barisTotal . ':G' . $this->barisTotal);
        $sheet->getStyle('A' . $this->barisTotal . ':' . $kolomTerakhir . $this->barisTotal)->applyFromArray([
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'EAF2FF'],
            ],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1F3B61']],
            ],
        ]);
        $sheet->getStyle('L' . $this->barisTotal)->getFont()->getColor()->setRGB('C0392B');

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'H' . $this->barisAwalData . ':L' . $this->barisTotal => '"Rp." #,##0',
        ];
    }

    private function buildKeteranganFilter(): string
    {
        $bagian = [];

        $bagian[] = 'NIS/Nama: ' . (!empty($this->filterInfo['nis']) ? $this->filterInfo['nis'] : 'Semua');
        $bagian[] = 'Kelas: ' . (!empty($this->filterInfo['kelas']) ? $this->filterInfo['kelas'] : 'Semua Kelas');
        $bagian[] = 'Angkatan: ' . (!empty($this->filterInfo['angkatan']) ? $this->filterInfo['angkatan'] : 'Semua Angkatan');

        if (!empty($this->filterInfo['tanggal_mulai']) && !empty($this->filterInfo['tanggal_selesai'])) {
            $bagian[] = 'Periode: ' . date('d-m-Y', strtotime($this->filterInfo['tanggal_mulai']))
                . ' s/d ' . date('d-m-Y', strtotime($this->filterInfo['tanggal_selesai']));
        } else {
            $bagian[] = 'Periode: Semua Periode';
        }

        return implode('   |   ', $bagian);
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
