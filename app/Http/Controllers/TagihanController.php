<?php

namespace App\Http\Controllers;

use App\Models\ItemPembayaran;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanPotongan;
use Illuminate\Http\Request;

class TagihanController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', $request->get('nis', '')));
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

        $siswas = Siswa::orderBy('nama')->get(['id', 'nis', 'nama', 'kategori']);
        $items = ItemPembayaran::where('aktif', true)->orderBy('nama_item')->get();

        return view('keuangan.tagihan', compact('tagihans', 'siswas', 'items', 'q', 'jenjang', 'perPage'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'item_pembayaran_id' => 'required|exists:item_pembayarans,id',
            'periode_bulan' => 'nullable|integer|min:1|max:12',
            'periode_tahun' => 'nullable|integer|min:2000|max:2100',
            'nominal_awal' => 'required|numeric|min:1',
            'jatuh_tempo' => 'nullable|date',
            'catatan' => 'nullable|string',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);
        $item = ItemPembayaran::findOrFail($validated['item_pembayaran_id']);

        if ($item->berlaku_untuk === 'mondok' && $siswa->kategori !== 'mondok') {
            return back()->with('error', 'Item ini hanya berlaku untuk siswa mondok.')->withInput();
        }

        if ($item->berlaku_untuk === 'non_mondok' && $siswa->kategori !== 'non_mondok') {
            return back()->with('error', 'Item ini hanya berlaku untuk siswa non mondok.')->withInput();
        }

        Tagihan::create(array_merge($validated, ['status' => 'belum_lunas']));

        return back()->with('success', 'Tagihan berhasil dibuat.');
    }

    public function tambahPotongan(Request $request, Tagihan $tagihan)
    {
        $validated = $request->validate([
            'tanggal_potongan' => 'required|date',
            'keterangan' => 'required|string|max:255',
            'nominal_potongan' => 'required|numeric|min:1',
        ]);

        $sisaSaatIni = $tagihan->sisaTagihan();
        if ($sisaSaatIni <= 0) {
            return back()->with('error', 'Tagihan sudah lunas, potongan tidak dapat ditambahkan.');
        }

        if ((float) $validated['nominal_potongan'] > $sisaSaatIni) {
            return back()->with('error', 'Nominal potongan melebihi sisa tagihan.');
        }

        TagihanPotongan::create([
            'tagihan_id' => $tagihan->id,
            'tanggal_potongan' => $validated['tanggal_potongan'],
            'keterangan' => $validated['keterangan'],
            'nominal_potongan' => $validated['nominal_potongan'],
        ]);

        $tagihan->fresh()->sinkronkanStatus();

        return back()->with('success', 'Potongan tagihan berhasil ditambahkan.');
    }
}
