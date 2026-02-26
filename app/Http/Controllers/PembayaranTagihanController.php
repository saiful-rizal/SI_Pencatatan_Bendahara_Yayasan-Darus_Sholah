<?php

namespace App\Http\Controllers;

use App\Models\PembayaranTagihan;
use App\Models\Tagihan;
use Illuminate\Http\Request;

class PembayaranTagihanController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $jenjang = (string) $request->get('jenjang', '');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $tagihans = Tagihan::with(['siswa', 'itemPembayaran'])
            ->withSum('potongans as total_potongan', 'nominal_potongan')
            ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
            ->when($q !== '', function ($query) use ($q) {
                $query->whereHas('siswa', function ($siswaQuery) use ($q) {
                    $siswaQuery->where('nis', 'like', '%' . $q . '%')
                        ->orWhere('nama', 'like', '%' . $q . '%');
                });
            })
            ->when(in_array($jenjang, ['10', '11', '12'], true), function ($query) use ($jenjang) {
                $query->whereHas('siswa', function ($siswaQuery) use ($jenjang) {
                    $siswaQuery->where(function ($kelasQuery) use ($jenjang) {
                        if ($jenjang === '10') {
                            $kelasQuery->where('kelas', 'like', '10%')->orWhere('kelas', '=', 'X')->orWhere('kelas', 'like', 'X %');
                        }
                        if ($jenjang === '11') {
                            $kelasQuery->where('kelas', 'like', '11%')->orWhere('kelas', '=', 'XI')->orWhere('kelas', 'like', 'XI %');
                        }
                        if ($jenjang === '12') {
                            $kelasQuery->where('kelas', 'like', '12%')->orWhere('kelas', '=', 'XII')->orWhere('kelas', 'like', 'XII %');
                        }
                    });
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->query());

        return view('keuangan.pembayaran', compact('tagihans', 'q', 'jenjang', 'perPage'));
    }

    public function store(Request $request, Tagihan $tagihan)
    {
        $validated = $request->validate([
            'tanggal_bayar' => 'required|date',
            'nominal_bayar' => 'required|numeric|min:1',
            'metode_bayar' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
        ]);

        $sisa = $tagihan->sisaTagihan();
        if ($sisa <= 0) {
            return back()->with('error', 'Tagihan sudah lunas.');
        }

        if ((float) $validated['nominal_bayar'] > $sisa) {
            return back()->with('error', 'Nominal bayar melebihi sisa tagihan.');
        }

        PembayaranTagihan::create([
            'tagihan_id' => $tagihan->id,
            'tanggal_bayar' => $validated['tanggal_bayar'],
            'nominal_bayar' => $validated['nominal_bayar'],
            'metode_bayar' => $validated['metode_bayar'] ?? null,
            'catatan' => $validated['catatan'] ?? null,
        ]);

        $tagihan->fresh()->sinkronkanStatus();

        return back()->with('success', 'Pembayaran berhasil disimpan.');
    }
}
