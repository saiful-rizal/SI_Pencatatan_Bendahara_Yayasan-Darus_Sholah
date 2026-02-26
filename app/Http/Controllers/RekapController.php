<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use Illuminate\Http\Request;

class RekapController extends Controller
{
    public function index(Request $request)
    {
        $tanggalMulai = $request->get('tanggal_mulai');
        $tanggalSelesai = $request->get('tanggal_selesai');
        $nis = $request->get('nis');

        $query = Tagihan::with('siswa')
            ->withSum('potongans as total_potongan', 'nominal_potongan')
            ->withSum('pembayarans as total_pembayaran', 'nominal_bayar');

        if ($nis) {
            $query->whereHas('siswa', function ($q) use ($nis) {
                $q->where('nis', 'like', '%' . $nis . '%')
                    ->orWhere('nama', 'like', '%' . $nis . '%');
            });
        }

        if ($tanggalMulai && $tanggalSelesai) {
            $query->whereBetween('created_at', [$tanggalMulai, $tanggalSelesai]);
        }

        $rekap = $query->orderByDesc('id')->get()->map(function ($tagihan) {
            $potongan = (float) ($tagihan->total_potongan ?? 0);
            $bayar = (float) ($tagihan->total_pembayaran ?? 0);
            $totalAkhir = max(0, (float) $tagihan->nominal_awal - $potongan);
            $sisa = max(0, $totalAkhir - $bayar);

            return [
                'nis' => $tagihan->siswa->nis,
                'nama' => $tagihan->siswa->nama,
                'nominal_awal' => (float) $tagihan->nominal_awal,
                'potongan' => $potongan,
                'total_akhir' => $totalAkhir,
                'pembayaran' => $bayar,
                'sisa' => $sisa,
            ];
        });

        $ringkasan = [
            'nominal_awal' => $rekap->sum('nominal_awal'),
            'potongan' => $rekap->sum('potongan'),
            'total_akhir' => $rekap->sum('total_akhir'),
            'pembayaran' => $rekap->sum('pembayaran'),
            'sisa' => $rekap->sum('sisa'),
        ];

        return view('keuangan.rekap', compact('rekap', 'ringkasan', 'request'));
    }
}
