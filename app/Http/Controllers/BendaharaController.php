<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\Siswa;
use Illuminate\Http\Request;
use App\Exports\SekolahExport;
use App\Exports\WaliExport;
use App\Exports\YayasanExport; // Export Yayasan ditambahkan
use Maatwebsite\Excel\Facades\Excel;

class BendaharaController extends Controller
{
    public function index()
    {
        $totalMasuk = Transaksi::where('jenis', 'Masuk')->sum('total_bayar');
        $totalKeluar = Transaksi::where('jenis', 'Keluar')->sum('total_bayar');
        $saldo = $totalMasuk - $totalKeluar;

        $transaksis = Transaksi::latest()->paginate(10);

        $months = [];
        $dataMasukChart = [];
        $dataKeluarChart = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->translatedFormat('M y');

            $dataMasukChart[] = Transaksi::where('jenis', 'Masuk')
                ->whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year)
                ->sum('total_bayar');

            $dataKeluarChart[] = Transaksi::where('jenis', 'Keluar')
                ->whereMonth('tanggal', $date->month)
                ->whereYear('tanggal', $date->year)
                ->sum('total_bayar');
        }

        $transaksiHariIni = Transaksi::whereDate('tanggal', today())->count();
        $totalSiswa = Siswa::where('status', 'aktif')->count();

        $pencapaianBulanIni = Transaksi::where('jenis', 'Masuk')
            ->whereMonth('tanggal', now()->month)
            ->whereYear('tanggal', now()->year)
            ->sum('total_bayar');

        $recentTransactions = Transaksi::latest()->take(5)->get();

        return view('dashboard', compact(
            'transaksis',
            'totalMasuk',
            'totalKeluar',
            'saldo',
            'months',
            'dataMasukChart',
            'dataKeluarChart',
            'transaksiHariIni',
            'totalSiswa',
            'pencapaianBulanIni',
            'recentTransactions'
        ));
    }

    // Input Data
    public function store(Request $request)
    {
        $request->validate([
            'jenis' => 'required|in:Masuk,Keluar',
            'kategori' => 'required',
            'tanggal' => 'required|date',
            'nama_item' => 'required|array',
            'harga' => 'required|array',
            'jumlah' => 'required|array',
        ]);

        $totalBayar = 0;
        foreach ($request->harga as $key => $harga) {
            $totalBayar += ($harga * $request->jumlah[$key]);
        }

        $transaksi = Transaksi::create([
            'jenis' => $request->jenis,
            'kategori' => $request->kategori,
            'nama_siswa' => $request->nama_siswa,
            'kelas' => $request->kelas,
            'total_bayar' => $totalBayar,
            'tanggal' => $request->tanggal,
            'catatan' => $request->catatan,
        ]);

        foreach ($request->nama_item as $key => $nama) {
            if (!empty($nama)) {
                DetailTransaksi::create([
                    'transaksi_id' => $transaksi->id,
                    'nama_item' => $nama,
                    'harga' => $request->harga[$key],
                    'jumlah' => $request->jumlah[$key],
                    'subtotal' => $request->harga[$key] * $request->jumlah[$key],
                ]);
            }
        }

        return redirect()->route('home')->with('success', 'Transaksi berhasil disimpan!');
    }

    // --- LAPORAN ---

    // 1. Laporan Sekolah
    public function laporanSekolah(Request $request)
    {
        $query = Transaksi::with('details')->orderBy('tanggal', 'desc');

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', 'like', '%' . $request->kategori . '%');
        }

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        $data = $query->get();
        return view('laporan.sekolah', compact('data', 'request'));
    }

    // Export Excel Sekolah
    public function exportSekolah(Request $request)
    {
        // 1. Ambil query yang sama dengan laporanSekolah agar data sinkron
        $query = Transaksi::query()->orderBy('tanggal', 'desc');

        if ($request->filled('jenis')) {
            $query->where('jenis', $request->jenis);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', 'like', '%' . $request->kategori . '%');
        }

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        $data = $query->get();

        // 2. Download Excel
        $fileName = 'Laporan_Keuangan_Sekolah_' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new SekolahExport($data), $fileName);
    }

    // 2. Laporan Wali Murid
    public function laporanWali(Request $request)
    {
        $query = Transaksi::with('details')->where('jenis', 'Masuk');

        if ($request->filled('nama_siswa')) {
            $query->where('nama_siswa', 'like', '%' . $request->nama_siswa . '%');
        }

        if ($request->filled('kelas')) {
            $query->where('kelas', $request->kelas);
        }

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        $data = $query->orderBy('tanggal', 'desc')->get();
        return view('laporan.wali', compact('data', 'request'));
    }

    // 3. Laporan Yayasan (View)
    public function laporanYayasan(Request $request)
    {
        $queryMasuk = Transaksi::where('jenis', 'Masuk');
        $queryKeluar = Transaksi::where('jenis', 'Keluar');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $queryMasuk->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
            $queryKeluar->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        $masuk = $queryMasuk->get();
        $keluar = $queryKeluar->get();

        $reportMasuk = $masuk->groupBy('kategori')->map(function ($item) {
            return $item->sum('total_bayar');
        });
        $reportKeluar = $keluar->groupBy('kategori')->map(function ($item) {
            return $item->sum('total_bayar');
        });

        $totalMasuk = $masuk->sum('total_bayar');
        $totalKeluar = $keluar->sum('total_bayar');
        $saldo = $totalMasuk - $totalKeluar;

        return view('laporan.yayasan', compact('reportMasuk', 'reportKeluar', 'totalMasuk', 'totalKeluar', 'saldo', 'request'));
    }

    // Export Excel Yayasan
    public function exportYayasan(Request $request)
    {
        // 1. Lakukan logika perhitungan yang sama dengan laporanYayasan
        $queryMasuk = Transaksi::where('jenis', 'Masuk');
        $queryKeluar = Transaksi::where('jenis', 'Keluar');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $queryMasuk->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
            $queryKeluar->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        $masuk = $queryMasuk->get();
        $keluar = $queryKeluar->get();

        $reportMasuk = $masuk->groupBy('kategori')->map(function ($item) {
            return $item->sum('total_bayar');
        });
        $reportKeluar = $keluar->groupBy('kategori')->map(function ($item) {
            return $item->sum('total_bayar');
        });

        $totalMasuk = $masuk->sum('total_bayar');
        $totalKeluar = $keluar->sum('total_bayar');
        $saldo = $totalMasuk - $totalKeluar;

        // 2. Download Excel menggunakan YayasanExport
        $fileName = 'Laporan_Yayasan_' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new YayasanExport($reportMasuk, $reportKeluar, $totalMasuk, $totalKeluar, $saldo), $fileName);
    }

    // 4. Cetak Nota
    public function cetakNota($id)
    {
        $transaksi = Transaksi::with('details')->findOrFail($id);
        return view('laporan.nota', compact('transaksi'));
    }

    public function destroy($id)
    {
        Transaksi::findOrFail($id)->delete();

        return back()->with('success', 'Data dipindahkan ke menu Riwayat dan dapat dipulihkan.');
    }

    public function riwayat()
    {
        $transaksis = Transaksi::onlyTrashed()->latest('deleted_at')->paginate(10);

        return view('keuangan.riwayat', compact('transaksis'));
    }

    public function restore($id)
    {
        $transaksi = Transaksi::onlyTrashed()->findOrFail($id);
        $transaksi->restore();

        return back()->with('success', 'Data transaksi berhasil dipulihkan.');
    }
}
