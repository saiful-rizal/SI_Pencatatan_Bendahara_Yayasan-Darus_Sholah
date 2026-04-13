<?php

namespace App\Http\Controllers;

use App\Models\PembayaranTagihan;
use App\Models\Siswa;
use App\Models\Tagihan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class PembayaranTagihanController extends Controller
{
    public function cetakNota(Request $request)
    {
        $validated = $request->validate([
            'ids' => ['required', 'string'],
        ]);

        $ids = collect(explode(',', $validated['ids']))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        abort_if($ids->isEmpty(), 404);

        $pembayarans = PembayaranTagihan::query()
            ->with(['tagihan.siswa', 'tagihan.itemPembayaran'])
            ->whereIn('id', $ids)
            ->orderBy('tanggal_bayar')
            ->orderBy('id')
            ->get();

        abort_if($pembayarans->isEmpty(), 404);

        return view('keuangan.nota-pembayaran', compact('pembayarans'));
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $jenjang = strtoupper(trim((string) $request->get('jenjang', '')));
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $siswas = Siswa::query()
            ->with(['tagihans' => function ($query) {
                $query->with('itemPembayaran')
                    ->with(['potongans:id,tagihan_id,keterangan,nominal_potongan'])
                    ->withSum('potongans as total_potongan', 'nominal_potongan')
                    ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
                    ->whereIn('status', ['belum_lunas', 'sebagian'])
                    ->when(Schema::hasColumn('tagihans', 'kelas'), function ($tagihanQuery) {
                        $tagihanQuery->orderBy('kelas');
                    })
                    ->orderByDesc('id');
            }])
            ->whereHas('tagihans', function ($query) {
                $query->whereIn('status', ['belum_lunas', 'sebagian']);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('nis', 'like', '%' . $q . '%')
                        ->orWhere('nama', 'like', '%' . $q . '%');
                });
            });

        $this->applyJenjangFilter($siswas, $jenjang);

        $siswas = $siswas
            ->orderBy('nama')
            ->paginate($perPage)
            ->appends($request->query());

        return view('keuangan.pembayaran', compact('siswas', 'q', 'jenjang', 'perPage'));
    }

    private function applyJenjangFilter($query, string $jenjang): void
    {
        $jenjang = match (strtoupper(trim($jenjang))) {
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
            '13' => 'XIII',
            default => strtoupper(trim($jenjang)),
        };

        if (!in_array($jenjang, ['X', 'XI', 'XII', 'XIII'], true)) {
            return;
        }

        $query->where(function ($kelasQuery) use ($jenjang) {
            if ($jenjang === 'X') {
                $kelasQuery->where('kelas', 'like', '10%')->orWhere('kelas', '=', 'X')->orWhere('kelas', 'like', 'X %');
            }
            if ($jenjang === 'XI') {
                $kelasQuery->where('kelas', 'like', '11%')->orWhere('kelas', '=', 'XI')->orWhere('kelas', 'like', 'XI %');
            }
            if ($jenjang === 'XII') {
                $kelasQuery->where('kelas', 'like', '12%')->orWhere('kelas', '=', 'XII')->orWhere('kelas', 'like', 'XII %');
            }
            if ($jenjang === 'XIII') {
                $kelasQuery->where('kelas', 'like', '13%')->orWhere('kelas', '=', 'XIII')->orWhere('kelas', 'like', 'XIII %');
            }
        });
    }

    public function store(Request $request, Tagihan $tagihan)
    {
        $validated = $request->validate([
            'tanggal_bayar' => 'required|date',
            'nominal_bayar' => 'required|numeric|min:1',
            'metode_bayar' => ['required', Rule::in(['cash', 'cicil', 'qris', 'pinjaman'])],
            'catatan' => 'nullable|string',
        ]);

        $sisa = $tagihan->sisaTagihan();
        if ($sisa <= 0) {
            return back()->with('error', 'Tagihan sudah lunas.');
        }

        if ((float) $validated['nominal_bayar'] > $sisa) {
            return back()->with('error', 'Nominal bayar melebihi sisa tagihan.');
        }

        $pembayaran = null;

        DB::transaction(function () use (&$pembayaran, $tagihan, $validated) {
            $pembayaran = PembayaranTagihan::create([
                'tagihan_id' => $tagihan->id,
                'tanggal_bayar' => $validated['tanggal_bayar'],
                'nominal_bayar' => $validated['nominal_bayar'],
                'metode_bayar' => $validated['metode_bayar'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
            ]);

            $tagihan->fresh()->sinkronkanStatus();
        });

        if (!$pembayaran) {
            return back()->with('error', 'Pembayaran gagal diproses.')->withInput();
        }

        if ($request->boolean('cetak_nota')) {
            $pembayarans = PembayaranTagihan::query()
                ->with(['tagihan.siswa', 'tagihan.itemPembayaran'])
                ->where('id', $pembayaran->id)
                ->orderBy('tanggal_bayar')
                ->orderBy('id')
                ->get();

            return view('keuangan.nota-pembayaran', compact('pembayarans'));
        }

        return back()->with('success', 'Pembayaran berhasil disimpan.');
    }

    public function bayarSemuaSiswa(Request $request)
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'tanggal_bayar' => 'required|date',
            'metode_bayar' => ['required', Rule::in(['cash', 'cicil', 'qris', 'pinjaman'])],
            'catatan' => 'nullable|string',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);

        $tagihans = Tagihan::query()
            ->where('siswa_id', $siswa->id)
            ->whereIn('status', ['belum_lunas', 'sebagian'])
            ->withSum('potongans as total_potongan', 'nominal_potongan')
            ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
            ->get();

        if ($tagihans->isEmpty()) {
            return back()->with('error', 'Tidak ada item tagihan yang bisa dibayar.');
        }

        $pembayaranIds = [];

        DB::transaction(function () use ($tagihans, $validated, &$pembayaranIds) {
            foreach ($tagihans as $tagihan) {
                $potongan = (float) ($tagihan->total_potongan ?? 0);
                $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
                $sisa = max(0, (float) $tagihan->nominal_awal - $potongan - $pembayaran);

                if ($sisa <= 0) {
                    continue;
                }

                $created = PembayaranTagihan::create([
                    'tagihan_id' => $tagihan->id,
                    'tanggal_bayar' => $validated['tanggal_bayar'],
                    'nominal_bayar' => $sisa,
                    'metode_bayar' => $validated['metode_bayar'] ?? null,
                    'catatan' => $validated['catatan'] ?? 'Pelunasan seluruh item tagihan siswa.',
                ]);

                $pembayaranIds[] = $created->id;

                $tagihan->fresh()->sinkronkanStatus();
            }
        });

        if ($request->boolean('cetak_nota') && !empty($pembayaranIds)) {
            $pembayarans = PembayaranTagihan::query()
                ->with(['tagihan.siswa', 'tagihan.itemPembayaran'])
                ->whereIn('id', $pembayaranIds)
                ->orderBy('tanggal_bayar')
                ->orderBy('id')
                ->get();

            return view('keuangan.nota-pembayaran', compact('pembayarans'));
        }

        return back()->with('success', 'Pembayaran seluruh item siswa berhasil diproses.');
    }
}
