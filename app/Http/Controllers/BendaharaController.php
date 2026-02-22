<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use Illuminate\Http\Request;

class BendaharaController extends Controller
{
    public function index()
    {
        $totalMasuk = Transaksi::where('jenis', 'Masuk')->sum('total_bayar');
        $totalKeluar = Transaksi::where('jenis', 'Keluar')->sum('total_bayar');
        $saldo = $totalMasuk - $totalKeluar;

        $transaksis = Transaksi::latest()->paginate(10);
        return view('dashboard', compact('transaksis', 'totalMasuk', 'totalKeluar', 'saldo'));
    }

    // Input Data dengan Multi-Item
    public function store(Request $request)
    {
        // Validasi Header
        $request->validate([
            'jenis' => 'required|in:Masuk,Keluar',
            'kategori' => 'required',
            'tanggal' => 'required|date',
            'nama_item' => 'required|array', // Wajib ada minimal 1 item
            'harga' => 'required|array',
            'jumlah' => 'required|array',
        ]);

        // Hitung Total dari Input Frontend (atau hitung ulang di backend)
        $totalBayar = 0;
        foreach ($request->harga as $key => $harga) {
            $totalBayar += ($harga * $request->jumlah[$key]);
        }

        // Simpan Transaksi Utama
        $transaksi = Transaksi::create([
            'jenis' => $request->jenis,
            'kategori' => $request->kategori,
            'nama_siswa' => $request->nama_siswa,
            'kelas' => $request->kelas,
            'total_bayar' => $totalBayar,
            'tanggal' => $request->tanggal,
            'catatan' => $request->catatan,
        ]);

        // Simpan Detail Item (Opsi Harga)
        foreach ($request->nama_item as $key => $nama) {
            if(!empty($nama)) {
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

    // 1. Laporan Sekolah (Semua Data)
    public function laporanSekolah()
    {
        $data = Transaksi::with('details')->orderBy('tanggal', 'desc')->get();
        return view('laporan.sekolah', compact('data'));
    }

    // 2. Laporan Wali Murid (Filter Siswa)
    public function laporanWali(Request $request)
    {
        $query = Transaksi::with('details')->where('jenis', 'Masuk');

        if ($request->nama_siswa) {
            $query->where('nama_siswa', 'like', '%' . $request->nama_siswa . '%');
        }

        $data = $query->orderBy('tanggal', 'desc')->get();
        return view('laporan.wali', compact('data', 'request'));
    }

    // 3. Laporan Yayasan (Laba Rugi)
    public function laporanYayasan()
    {
        $masuk = Transaksi::where('jenis', 'Masuk')->get();
        $keluar = Transaksi::where('jenis', 'Keluar')->get();

        // Kelompokkan per kategori untuk Yayasan
        $reportMasuk = $masuk->groupBy('kategori')->map(function($item) {
            return $item->sum('total_bayar');
        });
        $reportKeluar = $keluar->groupBy('kategori')->map(function($item) {
            return $item->sum('total_bayar');
        });

        $totalMasuk = $masuk->sum('total_bayar');
        $totalKeluar = $keluar->sum('total_bayar');
        $saldo = $totalMasuk - $totalKeluar;

        return view('laporan.yayasan', compact('reportMasuk', 'reportKeluar', 'totalMasuk', 'totalKeluar', 'saldo'));
    }

    // 4. Cetak Nota (Detail Item)
    public function cetakNota($id)
    {
        $transaksi = Transaksi::with('details')->findOrFail($id);
        return view('laporan.nota', compact('transaksi'));
    }

    public function destroy($id)
    {
        Transaksi::destroy($id);
        return back()->with('success', 'Data dihapus.');
    }
}
