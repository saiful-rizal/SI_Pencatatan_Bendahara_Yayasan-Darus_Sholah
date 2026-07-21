<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class YayasanExport implements FromCollection, WithHeadings, WithStyles, WithTitle, WithColumnFormatting, WithEvents
{
    protected $reportMasuk, $reportKeluar, $totalMasuk, $totalKeluar, $saldo, $groupBy, $nomorSurat, $filterItem, $detailMasuk, $detailKeluar, $periode, $dicetakOleh;

    /** @var array<int,string> baris-baris yang harus di-bold (header kategori & total) */
    protected $boldRows = [];
    /** @var array<int,int> baris bagian Pemasukan yang diberi warna hijau */
    protected $incomeRows = [];
    /** @var array<int,int> baris bagian Pengeluaran yang diberi warna merah */
    protected $expenseRows = [];
    /** @var array<int,int> baris Posisi Akhir/Saldo yang diberi warna biru */
    protected $saldoRows = [];
    /** @var array<int,int> baris judul seksi (RINGKASAN, I./II./III.) yang di-merge A:G */
    protected $sectionHeaderRows = [];
    /** @var array<int,int> baris kategori/item (berisi label subtotal) untuk merge A:E */
    protected $categoryRows = [];
    /** @var array<int,int> baris TOTAL/LABA-RUGI/RINGKASAN untuk merge A:E */
    protected $totalRows = [];
    /** @var array<int,int> baris ringkasan Total Pemasukan (hijau) */
    protected $ringkasanMasukRows = [];
    /** @var array<int,int> baris ringkasan Total Pengeluaran (merah) */
    protected $ringkasanKeluarRows = [];
    /** @var array<int,int> baris ringkasan Saldo Akhir (biru) */
    protected $ringkasanSaldoRows = [];
    /** @var array<int,int> baris transaksi per item (bukan subtotal/header), untuk zebra-striping */
    protected $detailRows = [];
    protected $infoRowEnd = 0;
    protected $ringkasanRowEnd = 0;
    protected $lastDataRow = 0;
    protected $incomeRangeStart = 0;
    protected $incomeRangeEnd = 0;
    protected $expenseRangeStart = 0;
    protected $expenseRangeEnd = 0;
    protected $saldoRangeStart = 0;
    protected $saldoRangeEnd = 0;

    public function __construct(
        $reportMasuk,
        $reportKeluar,
        $totalMasuk,
        $totalKeluar,
        $saldo,
        $groupLabel = 'Per Kategori',
        $nomorSurat = null,
        $filterItem = null,
        $detailMasuk = null,
        $detailKeluar = null,
        $periode = 'Semua Periode',
        $dicetakOleh = '-'
    ) {
        $this->reportMasuk = $reportMasuk;
        $this->reportKeluar = $reportKeluar;
        $this->totalMasuk = $totalMasuk;
        $this->totalKeluar = $totalKeluar;
        $this->saldo = $saldo;
        $this->groupBy = $groupLabel;
        $this->nomorSurat = $nomorSurat;
        $this->filterItem = $filterItem;
        $this->detailMasuk = $detailMasuk ?? collect();
        $this->detailKeluar = $detailKeluar ?? collect();
        $this->periode = $periode;
        $this->dicetakOleh = $dicetakOleh;
    }

    public function collection()
    {
        $data = [];
        $label = $this->groupBy;
        $row = 1;

        // ===== KOP / LETTERHEAD =====
        $data[] = ['SMA UNGGULAN BPPT DARUS SHOLAH']; $row++;
        $data[] = ['LAPORAN KEUANGAN YAYASAN']; $row++;

        // Catatan: label & nilai digabung jadi satu baris teks (bukan 2 kolom terpisah)
        // supaya baris ini benar-benar center relatif terhadap SELURUH lebar A:G,
        // sama seperti judul di atasnya. Sebelumnya nilai hanya di-merge C:G sehingga
        // center-nya bergeser ke kanan (tidak simetris terhadap lebar penuh A:G).
        $data[] = ['Nomor Dokumen : ' . ($this->nomorSurat ?? '001/B/SMA.U.BPPT.DS/' . now()->format('d/m/Y'))]; $row++;
        $data[] = ['Jenis Dokumen : ' . ($this->filterItem ? 'Laporan Keuangan Yayasan Per Item - ' . $this->filterItem->nama_item : 'Laporan Keuangan Yayasan ' . $label)]; $row++;
        $data[] = ['Periode Laporan : ' . $this->periode]; $row++;
        $data[] = ['Tanggal Cetak : ' . now()->format('d-m-Y H:i')]; $row++;
        $data[] = ['Dicetak Oleh : ' . $this->dicetakOleh]; $row++;
        $this->infoRowEnd = $row - 1;

        // ===== RINGKASAN (supaya bendahara langsung tahu intinya tanpa scroll) =====
        $data[] = ['RINGKASAN LAPORAN']; $this->boldRows[] = $row; $this->sectionHeaderRows[] = $row; $row++;

        // Catatan: label diletakkan di kolom A (bukan F) lalu di-merge A:F pada styles().
        // Ini supaya teks label memiliki ruang penuh dan TIDAK kepotong, karena PhpSpreadsheet/Excel
        // tidak mengizinkan teks meluber melewati batas merge-cell lain di sebelahnya.
        $data[] = ['Total Pemasukan', '', '', '', '', '', $this->totalMasuk];
        $this->boldRows[] = $row; $this->totalRows[] = $row; $this->ringkasanMasukRows[] = $row; $row++;

        $data[] = ['Total Pengeluaran', '', '', '', '', '', $this->totalKeluar];
        $this->boldRows[] = $row; $this->totalRows[] = $row; $this->ringkasanKeluarRows[] = $row; $row++;

        $data[] = ['Saldo Akhir', '', '', '', '', '', $this->saldo];
        $this->boldRows[] = $row; $this->totalRows[] = $row; $this->ringkasanSaldoRows[] = $row; $row++;
        $this->ringkasanRowEnd = $row - 1;

        // baris kosong pemisah sebelum rincian
        $data[] = ['']; $row++;

        // ===== PEMASUKAN =====
        $this->incomeRangeStart = $row;
        $data[] = ['I. RINCIAN PEMASUKAN']; $this->incomeRows[] = $row; $this->boldRows[] = $row; $this->sectionHeaderRows[] = $row; $row++;
        $data[] = ['No', 'Tanggal', 'Nama Siswa / Uraian', 'Kelas', 'Item / Keterangan', 'Metode Bayar', 'Jumlah (Rp)'];
        $this->boldRows[] = $row; $this->incomeRows[] = $row; $row++;

        $no = 1;
        foreach ($this->reportMasuk as $key => $jumlah) {
            $data[] = [$key, '', '', '', '', 'Subtotal', $jumlah];
            $this->boldRows[] = $row; $this->categoryRows[] = $row; $row++;
            foreach (($this->detailMasuk[$key] ?? []) as $r) {
                $data[] = [$no, $r['tanggal'], $r['nama_siswa'], $r['kelas'], $r['item'], $r['metode'] ?? '-', $r['nominal']];
                $this->detailRows[] = $row;
                $row++; $no++;
            }
        }
        $data[] = ['TOTAL PEMASUKAN', '', '', '', '', '', $this->totalMasuk];
        $this->boldRows[] = $row; $this->totalRows[] = $row; $row++;
        $this->incomeRangeEnd = $row - 1;

        // ===== PENGELUARAN =====
        $this->expenseRangeStart = $row;
        $data[] = ['II. RINCIAN PENGELUARAN']; $this->expenseRows[] = $row; $this->boldRows[] = $row; $this->sectionHeaderRows[] = $row; $row++;
        $data[] = ['No', 'Tanggal', 'Item', 'Jumlah x Harga', 'Keterangan', 'Dicatat Oleh', 'Jumlah (Rp)'];
        $this->boldRows[] = $row; $this->expenseRows[] = $row; $row++;

        $no = 1;
        foreach ($this->reportKeluar as $key => $jumlah) {
            $data[] = [$key, '', '', '', '', 'Subtotal', $jumlah];
            $this->boldRows[] = $row; $this->categoryRows[] = $row; $row++;
            foreach (($this->detailKeluar[$key] ?? []) as $r) {
                $data[] = [$no, $r['tanggal'], $r['item'], $r['jumlah_label'], $r['keterangan'], $r['dicatat_oleh'] ?? '-', $r['nominal']];
                $this->detailRows[] = $row;
                $row++; $no++;
            }
        }
        $data[] = ['TOTAL PENGELUARAN', '', '', '', '', '', $this->totalKeluar];
        $this->boldRows[] = $row; $this->totalRows[] = $row; $row++;
        $this->expenseRangeEnd = $row - 1;

        // ===== SALDO =====
        $this->saldoRangeStart = $row;
        $data[] = ['III. POSISI AKHIR']; $this->saldoRows[] = $row; $this->boldRows[] = $row; $this->sectionHeaderRows[] = $row; $row++;
        $data[] = ['LABA / RUGI BERSIH', '', '', '', '', '', $this->saldo];
        $this->boldRows[] = $row; $this->totalRows[] = $row; $row++;
        $this->saldoRangeEnd = $row - 1;

        $this->lastDataRow = $row - 1;

        return collect($data);
    }

    public function headings(): array
    {
        return [];
    }

    public function columnFormats(): array
    {
        return [
            'G' => '"Rp" #,##0',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Lebar kolom tetap (bukan auto-size) - auto-size tidak akurat untuk sel yang di-merge
        // (label kategori/uraian), lebar berikut sudah disesuaikan agar pas tanpa berlebihan.
        $sheet->getColumnDimension('A')->setWidth(8);
        $sheet->getColumnDimension('B')->setWidth(13);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(20);
        $sheet->getColumnDimension('E')->setWidth(34);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(20);

        // Font dasar yang lebih modern & mudah dibaca
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);

        $sheet->getStyle('A1:G' . 2000)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A3:G2000')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // ===== Kop surat (banner navy modern) =====
        $sheet->mergeCells('A1:G1');
        $sheet->mergeCells('A2:G2');
        $sheet->getRowDimension(1)->setRowHeight(26);
        $sheet->getRowDimension(2)->setRowHeight(22);
        $sheet->getStyle('A1:G2')->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0F172A']],
        ]);
        $sheet->getStyle('A1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 15, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle('A2')->applyFromArray([
            'font' => ['bold' => true, 'size' => 12, 'color' => ['rgb' => 'CBD5E1']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // Info dokumen (baris 3 s/d infoRowEnd) - satu baris teks "Label : Nilai" per baris,
        // di-merge penuh A:G dan rata kiri (sama seperti gaya teks "RINGKASAN LAPORAN"
        // di bawahnya), supaya konsisten satu gaya penulisan di seluruh dokumen.
        for ($r = 3; $r <= $this->infoRowEnd; $r++) {
            $sheet->mergeCells('A' . $r . ':G' . $r);
            $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            // Bold hanya pada bagian "Label :" (sebelum tanda titik dua pertama),
            // sisanya (nilainya) tetap normal - dibuat dengan rich text dalam 1 sel.
            $teksLengkap = (string) $sheet->getCell('A' . $r)->getValue();
            $posisiTitikDua = strpos($teksLengkap, ' : ');
            if ($posisiTitikDua !== false) {
                $labelBagian = substr($teksLengkap, 0, $posisiTitikDua + 3);
                $nilaiBagian = substr($teksLengkap, $posisiTitikDua + 3);

                $richText = new \PhpOffice\PhpSpreadsheet\RichText\RichText();
                $runLabel = $richText->createTextRun($labelBagian);
                $runLabel->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF334155'));
                $runNilai = $richText->createTextRun($nilaiBagian);
                $runNilai->getFont()->setBold(false)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF1E293B'));

                $sheet->getCell('A' . $r)->setValue($richText);
            }

            if (($r - 3) % 2 === 1) {
                $sheet->getStyle('A' . $r . ':G' . $r)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                ]);
            }
        }
        $sheet->getStyle('A' . $this->infoRowEnd . ':G' . $this->infoRowEnd)->applyFromArray([
            'borders' => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '94A3B8']]],
        ]);

        // Bold baris kategori/header/total/ringkasan
        foreach (array_unique($this->boldRows) as $r) {
            $sheet->getStyle('A' . $r . ':G' . $r)->applyFromArray(['font' => ['bold' => true]]);
        }

        // Kotak RINGKASAN LAPORAN diberi border tebal supaya menonjol sebagai info utama
        $sheet->getStyle('A' . ($this->infoRowEnd + 1) . ':G' . $this->ringkasanRowEnd)->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1E293B']]],
        ]);

        // ===== Ringkasan: baris label (A:F, kiri) + nilai (G, kanan) - layout kartu ringkas modern =====
        foreach (array_unique($this->ringkasanMasukRows) as $r) {
            $sheet->getRowDimension($r)->setRowHeight(22);
            $sheet->getStyle('A' . $r . ':G' . $r)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCFCE7']],
                'font' => ['color' => ['rgb' => '166534'], 'size' => 12],
            ]);
        }
        foreach (array_unique($this->ringkasanKeluarRows) as $r) {
            $sheet->getRowDimension($r)->setRowHeight(22);
            $sheet->getStyle('A' . $r . ':G' . $r)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FEE2E2']],
                'font' => ['color' => ['rgb' => '991B1B'], 'size' => 12],
            ]);
        }
        foreach (array_unique($this->ringkasanSaldoRows) as $r) {
            $sheet->getRowDimension($r)->setRowHeight(24);
            $sheet->getStyle('A' . $r . ':G' . $r)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DBEAFE']],
                'font' => ['color' => ['rgb' => '1E3A8A'], 'size' => 13, 'bold' => true],
            ]);
        }

        // ===== Banner judul seksi (I./II./III.) - solid navy, teks putih, lebih tegas & modern =====
        foreach (array_unique($this->incomeRows) as $r) {
            $sheet->getStyle('A' . $r . ':G' . $r)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '15803D']],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ]);
        }
        foreach (array_unique($this->expenseRows) as $r) {
            $sheet->getStyle('A' . $r . ':G' . $r)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'B91C1C']],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ]);
        }
        foreach (array_unique($this->saldoRows) as $r) {
            $sheet->getStyle('A' . $r . ':G' . $r)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1D4ED8']],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
            ]);
        }

        // Judul seksi (baris pertama tiap array di atas) tetap satu baris penuh (merge A:G),
        // rata kiri, dengan tinggi baris lebih besar supaya terlihat seperti "banner" section.
        foreach (array_unique($this->sectionHeaderRows) as $r) {
            $sheet->mergeCells('A' . $r . ':G' . $r);
            $sheet->getRowDimension($r)->setRowHeight(22);
            $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('A' . $r)->applyFromArray(['font' => ['size' => 12]]);
        }

        // Header kolom ("No, Tanggal, Nama Siswa/Uraian, ...") - hanya baris kedua (index 1)
        // di masing-masing array incomeRows/expenseRows; baris pertama (index 0) adalah
        // judul seksi yang sudah ditangani sebagai banner di atas.
        $headerKolomRows = array_filter([
            $this->incomeRows[1] ?? null,
            $this->expenseRows[1] ?? null,
        ]);
        foreach ($headerKolomRows as $r) {
            $sheet->getRowDimension($r)->setRowHeight(20);
            $sheet->getStyle('A' . $r . ':G' . $r)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
        }

        // Merge A:E pada baris kategori (labelnya memang ditulis di kolom A, bukan B),
        // supaya teks panjang seperti "Keamanan Sekolah (Februari 2026)" tampil penuh
        // dan tidak terpotong oleh border sel di sebelahnya. Diberi warna abu lembut
        // supaya jelas berbeda tingkatan dengan baris detail transaksi di bawahnya.
        foreach (array_unique($this->categoryRows) as $r) {
            $sheet->mergeCells('A' . $r . ':E' . $r);
            $sheet->getStyle('A' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $sheet->getStyle('A' . $r . ':G' . $r)->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EEF2F7']],
                'font' => ['italic' => true],
            ]);
        }

        // ===== PERBAIKAN UTAMA: baris TOTAL/LABA-RUGI/RINGKASAN =====
        // Sebelumnya label ("TOTAL PEMASUKAN" dst.) diletakkan di kolom F yang sempit,
        // sedangkan A:E di-merge kosong di sebelahnya - karena keduanya blok merge yang
        // berbeda, Excel TIDAK mengizinkan teks meluber lintas-merge sehingga huruf
        // pertama kepotong ("OTAL PEMASUKAN", "ABA / RUGI BERSIH"). Sekarang label
        // sudah dipindah ke kolom A (lihat collection()) dan di-merge A:F sepenuhnya,
        // sehingga punya ruang penuh dan tidak akan kepotong lagi. Nilai nominal
        // berdiri sendiri di kolom G.
        foreach (array_unique($this->totalRows) as $r) {
            $sheet->mergeCells('A' . $r . ':F' . $r);
            $sheet->getStyle('A' . $r . ':F' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('G' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Border seluruh tabel data (dari baris judul seksi pemasukan sampai baris terakhir)
        $tableStart = $this->incomeRangeStart;
        $sheet->getStyle('A' . $tableStart . ':G' . $this->lastDataRow)->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CBD5E1']]],
        ]);
        // Kotak tebal mengelilingi seluruh tabel supaya terlihat tegas & tertata
        $sheet->getStyle('A' . $tableStart . ':G' . $this->lastDataRow)->applyFromArray([
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1E293B']]],
        ]);

        // ===== Wrap text supaya teks panjang di kolom C & E turun ke baris berikutnya
        // di dalam sel yang sama, bukan hilang/kepotong. Tinggi baris menyesuaikan
        // otomatis saat file dibuka di Excel. =====
        $sheet->getStyle('C' . $tableStart . ':C' . $this->lastDataRow)
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        $sheet->getStyle('E' . $tableStart . ':E' . $this->lastDataRow)
            ->getAlignment()
            ->setWrapText(true)
            ->setVertical(Alignment::VERTICAL_TOP)
            ->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Kode wrap-text C & E di atas ikut menimpa kolom C/E pada baris HEADER kolom
        // (karena rentangnya dimulai dari $tableStart, termasuk baris header). Supaya
        // "Nama Siswa / Uraian" dan "Item / Keterangan" tetap sejajar center seperti
        // header kolom lain ("No", "Tanggal", "Kelas", dst.), pusatkan ulang di sini -
        // dijalankan SETELAH blok wrap-text supaya tidak tertimpa lagi.
        foreach ($headerKolomRows as $r) {
            $sheet->getStyle('A' . $r . ':G' . $r)->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setWrapText(true);
        }

        // ===== Zebra-striping pada baris detail transaksi (bukan kategori/header/total)
        // supaya baris yang banyak tetap mudah diikuti mata tanpa perlu penggaris. =====
        foreach (array_values(array_unique($this->detailRows)) as $i => $r) {
            if ($i % 2 === 1) {
                $sheet->getStyle('A' . $r . ':G' . $r)->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
                ]);
            }
            $sheet->getStyle('B' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('D' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('G' . $r)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        // Baris total & saldo diberi border tebal di atas + ukuran lebih besar
        foreach (array_unique($this->totalRows) as $r) {
            $sheet->getStyle('A' . $r . ':G' . $r)->applyFromArray([
                'font' => ['bold' => true, 'size' => 12],
                'borders' => ['top' => ['borderStyle' => Border::BORDER_MEDIUM]],
            ]);
        }

        // Catatan: fitur "freeze pane" sempat dicoba di sini supaya Kop+Ringkasan tetap
        // terlihat saat scroll, TAPI dihapus karena kombinasi freeze pane dengan banner
        // warna solid (biru/hijau/merah) di sekitar titik beku memicu bug render Excel
        // berupa gap/kedip hitam saat scroll & zoom out. Ini murni bug tampilan Excel,
        // bukan kerusakan data, tapi lebih aman dihapus daripada mengorbankan kenyamanan
        // membaca laporan.

        // Sembunyikan gridlines bawaan Excel - semua border sudah digambar eksplisit,
        // tampilan jadi lebih bersih dan modern (tidak ada garis kotak-kotak default).
        $sheet->setShowGridlines(false);

        // Page setup: landscape, fit ke lebar 1 halaman
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageSetup()->setPrintArea('A1:G' . ($this->lastDataRow + 8));
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.6)->setLeft(0.4)->setRight(0.4);

        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Blok tanda tangan di bawah tabel (hanya Bendahara)
                $startRow = $this->lastDataRow + 3;
                $tempat = 'Jember';
                $tanggal = now()->translatedFormat('d F Y');

                $sheet->setCellValue('E' . $startRow, $tempat . ', ' . $tanggal);
                $sheet->setCellValue('E' . ($startRow + 1), 'Dibuat oleh,');
                $sheet->setCellValue('E' . ($startRow + 2), 'Bendahara');
                $sheet->setCellValue('E' . ($startRow + 6), '(' . $this->dicetakOleh . ')');

                $sheet->getStyle('E' . ($startRow + 1) . ':E' . ($startRow + 2))->applyFromArray(['font' => ['bold' => true]]);
                $sheet->getStyle('E' . ($startRow + 6))->applyFromArray(['font' => ['underline' => true]]);
            },
        ];
    }

    public function title(): string
    {
        $label = $this->groupBy !== 'Per Kategori' ? $this->groupBy : 'Yayasan';
        return mb_substr('Laporan ' . $label, 0, 31);
    }
}
