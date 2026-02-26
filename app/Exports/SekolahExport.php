<?php

namespace App\Exports;

use App\Models\Transaksi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Carbon\Carbon;

class SekolahExport implements FromCollection, WithHeadings, WithStyles, WithColumnFormatting, ShouldAutoSize
{
    protected $transactions;

    public function __construct($transactions)
    {
        $this->transactions = $transactions;
    }

    public function collection()
    {
        return $this->transactions->map(function ($item) {
            return [
                'Tanggal'       => Carbon::parse($item->tanggal)->format('d/m/Y'),
                'Kategori'      => $item->kategori,
                'Nama Siswa'    => $item->nama_siswa ?? '-',
                'Kelas'         => $item->kelas ?? '-',
                'Keterangan'    => $item->catatan ?? '-',
                'Jenis'         => $item->jenis,
                'Total (Rp)'    => (float) $item->total_bayar,
            ];
        });
    }

    public function headings(): array
    {
        return [
            ['LAPORAN KEUANGAN SEKOLAH'],
            ['Tanggal', 'Kategori', 'Nama Siswa', 'Kelas', 'Keterangan', 'Jenis', 'Total (Rp)']
        ];
    }

    public function columnFormats(): array
    {
        return [
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Styling Judul
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1:G1')->applyFromArray([
            'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => '1e293b']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Styling Header Tabel
        $sheet->getStyle('A2:G2')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'E2E8F0']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Styling Kolom Total (Rata Kanan)
        $rowCount = $sheet->getHighestRow();
        if ($rowCount > 2) {
            $sheet->getStyle('G3:G' . $rowCount)->applyFromArray([
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);
        }

        return [];
    }
}
