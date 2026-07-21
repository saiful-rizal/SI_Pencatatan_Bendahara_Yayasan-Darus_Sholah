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
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class WaliExport implements FromArray, WithStyles, WithColumnFormatting, ShouldAutoSize, WithEvents
{
    private int $totalRows = 0;

    /** @var array<int, array{header:int, start:int, end:int, total:int}> */
    private array $tableBlocks = [];

    /**
     * Menyimpan nilai kolom Nominal Bayar (L) per nomor baris.
     * Dipakai untuk menulis ulang nilai secara eksplisit di AfterSheet,
     * karena Worksheet::fromArray() bawaan PhpSpreadsheet/Laravel-Excel
     * memakai perbandingan longgar (0 == null bernilai true di PHP)
     * sehingga nilai 0 pada kolom numerik ikut terhapus/tidak tertulis.
     *
     * @var array<int, float>
     */
    private array $nominalCells = [];

    public function __construct(private Collection $rows, private string $periodeLabel = 'Semua Periode')
    {
    }

    /**
     * Baris pemisah/kosong yang AMAN dipakai di antara blok tabel.
     *
     * PENTING: jangan pakai array kosong `[]` sebagai baris pemisah.
     * Laravel-Excel (Maatwebsite\Excel\Sheet::appendRows) memproses data
     * lewat Collection::flatMap(ArrayHelper::ensureMultipleRows(...)).
     * Untuk array kosong, ArrayHelper::hasMultipleRows() salah mendeteksinya
     * sebagai "banyak baris kosong" (0 === 0), sehingga baris tersebut
     * DIHAPUS TOTAL dari hasil akhir alih-alih tetap jadi satu baris kosong.
     * Akibatnya seluruh baris di bawahnya bergeser naik dan style/border/
     * merge/nilai (yang posisinya dihitung berdasarkan urutan array data)
     * jadi salah tempat — inilah penyebab "Nominal Bayar", "TOTAL KELAS",
     * dan "GRAND TOTAL" terlihat hilang di file Excel.
     *
     * Solusinya: pakai baris berisi 12 string kosong (satu per kolom A-L)
     * supaya dikenali sebagai satu baris nyata dan tidak pernah dihapus.
     */
    private function blankRow(): array
    {
        return array_fill(0, 12, '');
    }

    public function array(): array
    {
        $data = [
            ['LAPORAN PEMBAYARAN WALI MURID'],
            ['Periode Laporan', $this->periodeLabel],
            ['Tanggal Cetak', now()->format('d-m-Y H:i')],
            $this->blankRow(),
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
            $headerRowIndex = count($data) + 1; // baris ke berapa (1-based) setelah header ini ditambahkan
            $data[] = ['No', 'Tanggal', 'No Kwitansi', 'NIS', 'Nama Siswa', 'Kelas Tagihan', 'Item Tagihan', 'Periode', 'Metode', 'Bank', 'Keterangan', 'Nominal Bayar'];
            $dataStartRowIndex = count($data) + 1;

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
                    'KW-' . str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                    $siswa?->nis ?? '-',
                    $siswa?->nama ?? '-',
                    $kelasTagihanNorm,
                    $item?->nama_item ?? '-',
                    $tagihan?->periode_label ?? '-',
                    strtoupper((string) ($row->metode_bayar ?? '-')),
                    $row->nama_bank ?: '-',
                    $row->catatan ?: '-',
                    $nominal,
                ];
                $this->nominalCells[count($data)] = $nominal;
            }

            if ($groupedRows[$kelas]->isEmpty()) {
                $data[] = ['-', '-', '-', '-', '-', '-', '-', '-', '-', '-', '-', 0];
                $this->nominalCells[count($data)] = 0;
            }

            $dataEndRowIndex = count($data);

            $data[] = ['', '', '', '', '', '', '', '', '', '', 'TOTAL KELAS ' . $kelas, $subtotal];
            $totalRowIndex = count($data);
            $this->nominalCells[$totalRowIndex] = $subtotal;

            $this->tableBlocks[] = [
                'header' => $headerRowIndex,
                'start' => $dataStartRowIndex,
                'end' => $dataEndRowIndex,
                'total' => $totalRowIndex,
            ];

            $data[] = $this->blankRow();
            $grandTotal += $subtotal;
        }

        $data[] = ['', '', '', '', '', '', '', '', '', '', 'GRAND TOTAL', $grandTotal];
        $this->nominalCells[count($data)] = $grandTotal;

        $this->totalRows = count($data);

        return $data;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->mergeCells('A1:L1');
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1F3657']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Baris info periode & tanggal cetak (baris 2-3)
        $sheet->getStyle('A2:A3')->applyFromArray([
            'font' => ['bold' => true],
        ]);
        $sheet->getStyle('A2:B3')->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Rapikan seluruh area data: tengahkan secara default, lalu override kolom tertentu.
        for ($row = 5; $row <= $this->totalRows; $row++) {
            $sheet->getStyle('A' . $row . ':L' . $row)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);
        }

        // Nama Siswa (E), Item Tagihan (G), Keterangan (K) rata kiri agar mudah dibaca
        foreach (['E', 'G', 'K'] as $col) {
            $sheet->getStyle($col . '5:' . $col . $this->totalRows)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
        }

        // Nominal Bayar (L) rata kanan
        $sheet->getStyle('L5:L' . $this->totalRows)->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        for ($row = 1; $row <= $this->totalRows; $row++) {
            $cellA = (string) $sheet->getCell('A' . $row)->getValue();
            $cellK = (string) $sheet->getCell('K' . $row)->getValue();

            if ($cellA === 'No') {
                $sheet->getStyle('A' . $row . ':L' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1F3B61']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'EAF2FF'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(20);
            }

            if (str_starts_with($cellA, 'LIST PEMBAYARAN KELAS TAGIHAN')) {
                $sheet->mergeCells('A' . $row . ':L' . $row);
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '1F3657'],
                    ],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension($row)->setRowHeight(22);
            }

            if (str_starts_with($cellK, 'TOTAL KELAS') || $cellK === 'GRAND TOTAL') {
                $isGrandTotal = $cellK === 'GRAND TOTAL';
                $labelText = $cellK; // simpan dulu, karena mergeCells() akan mengosongkan isi K

                $sheet->mergeCells('A' . $row . ':K' . $row);

                // PENTING: PhpSpreadsheet::mergeCells() mengosongkan SEMUA sel
                // dalam rentang gabungan kecuali sel kiri-atas (A). Karena teks
                // labelnya semula ada di kolom K (bukan A), teks itu ikut terhapus.
                // Tulis ulang secara eksplisit ke A supaya "TOTAL KELAS x" /
                // "GRAND TOTAL" tetap tampil setelah digabung.
                $sheet->setCellValue('A' . $row, $labelText);

                $sheet->getStyle('A' . $row . ':L' . $row)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => $isGrandTotal ? 'FFFFFF' : '1F3657']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => $isGrandTotal ? '1F8A4C' : 'F4F7FC'],
                    ],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getStyle('A' . $row)->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);
                $sheet->getStyle('L' . $row)->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);
            }
        }

        return [];
    }

    public function columnFormats(): array
    {
        return [
            'L' => '"Rp." #,##0',
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Border tipis rapi untuk seluruh blok tabel (header + data + total) tiap kelas
                foreach ($this->tableBlocks as $block) {
                    $range = 'A' . $block['header'] . ':L' . $block['total'];
                    $sheet->getStyle($range)->applyFromArray([
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'A9B7CC'],
                            ],
                        ],
                    ]);
                }

                // Border untuk baris grand total
                $sheet->getStyle('A' . $this->totalRows . ':L' . $this->totalRows)->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'A9B7CC'],
                        ],
                    ],
                ]);

                // PENTING: tulis ulang paksa nilai kolom Nominal Bayar (L).
                // Worksheet::fromArray() bawaan menghapus nilai 0 karena bug
                // perbandingan longgar (0 == null), sehingga baris kosong/subtotal
                // Rp 0 jadi kosong. Set ulang di sini menjamin nominal selalu tampil.
                foreach ($this->nominalCells as $rowNumber => $nominalValue) {
                    $sheet->setCellValue('L' . $rowNumber, $nominalValue);
                }

                // Pastikan kolom Nominal Bayar tidak pernah terlalu sempit / tersembunyi
                $sheet->getColumnDimension('L')->setAutoSize(false)->setWidth(18);

                $sheet->getPageSetup()
                    ->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE)
                    ->setFitToWidth(1)
                    ->setFitToHeight(0);
            },
        ];
    }
}
