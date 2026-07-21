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
            default => strtoupper(trim($jenjang)),
        };

        if (!in_array($jenjang, ['X', 'XI', 'XII'], true)) {
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
        });
    }

    /**
     * Normalisasi tanggal_bayar SEKALI di awal request, lalu pakai nilai yang
     * sama persis untuk semua item dalam satu batch pembayaran. Ini penting
     * saat bayar beberapa item sekaligus (bayarCustomSiswa / bayarSemuaSiswa):
     * jika jam ditempelkan terpisah per item (lihat PembayaranTagihan::setTanggalBayarAttribute),
     * proses simpan yang melewati batas detik akan membuat tanggal antar item
     * sedikit berbeda, sehingga GROUP BY di dashboard gagal menggabungkannya
     * jadi 1 baris/nota meski dibayar bersamaan.
     */
    private function normalisasiTanggalBayar(string $tanggalBayar): string
    {
        if (str_contains($tanggalBayar, ':')) {
            return $tanggalBayar;
        }

        return \Carbon\Carbon::parse($tanggalBayar)->setTimeFrom(now())->format('Y-m-d H:i:s');
    }

    public function store(Request $request, Tagihan $tagihan)
    {
        $validated = $request->validate([
            'tanggal_bayar' => 'required|date',
            'nominal_bayar' => 'required|numeric|min:1',
            'metode_bayar' => ['required', Rule::in(['cash', 'transfer'])],
            'nama_bank' => ['nullable', 'string', 'max:100'],
            'catatan' => 'nullable|string',
        ]);

        // Hindari masalah presisi floating-point — round ke integer (sama dengan tampilan UI)
        $sisa = (float) round($tagihan->sisaTagihan());
        if ($sisa <= 0) {
            return back()->with('error', 'Tagihan sudah lunas.');
        }

        $nominalBayar = (float) round((float) $validated['nominal_bayar']);

        if ($nominalBayar > $sisa) {
            return back()->with('error', 'Nominal bayar melebihi sisa tagihan.');
        }

        // pastikan nominal yang disimpan juga sudah dibulatkan
        $validated['nominal_bayar'] = $nominalBayar;
        $tanggalBayar = $this->normalisasiTanggalBayar($validated['tanggal_bayar']);

        $pembayaran = null;

        DB::transaction(function () use (&$pembayaran, $tagihan, $validated, $tanggalBayar) {
            $pembayaran = PembayaranTagihan::create([
                'tagihan_id' => $tagihan->id,
                'tanggal_bayar' => $tanggalBayar,
                'nominal_bayar' => $validated['nominal_bayar'],
                'metode_bayar' => $validated['metode_bayar'] ?? null,
                'nama_bank' => $validated['nama_bank'] ?? null,
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
            'metode_bayar' => ['required', Rule::in(['cash', 'transfer'])],
            'nama_bank' => ['nullable', 'string', 'max:100'],
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
        $tanggalBayar = $this->normalisasiTanggalBayar($validated['tanggal_bayar']);

        DB::transaction(function () use ($tagihans, $validated, $tanggalBayar, &$pembayaranIds) {
            foreach ($tagihans as $tagihan) {
                $potongan = (float) ($tagihan->total_potongan ?? 0);
                $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
                $sisa = (float) round(max(0, (float) $tagihan->nominal_awal - $potongan - $pembayaran));

                if ($sisa <= 0) {
                    continue;
                }

                $created = PembayaranTagihan::create([
                    'tagihan_id' => $tagihan->id,
                    'tanggal_bayar' => $tanggalBayar,
                    'nominal_bayar' => $sisa,
                    'metode_bayar' => $validated['metode_bayar'] ?? null,
                    'nama_bank' => $validated['nama_bank'] ?? null,
                    'catatan' => $validated['catatan'] ?? 'Pelunasan seluruh item tagihan siswa.',
                ]);

                $pembayaranIds[] = $created->id;

                // Update status langsung tanpa fresh() + sinkronkanStatus() (N+1)
                $totalBayarBaru = round($pembayaran + $sisa, 2);
                $sisaBaru = (float) round(max(0, (float) $tagihan->nominal_awal - $potongan - $totalBayarBaru), 2);
                $statusBaru = $sisaBaru <= 0 ? 'lunas' : ($totalBayarBaru > 0 ? 'sebagian' : 'belum_lunas');

                if ($tagihan->status !== $statusBaru) {
                    $tagihan->update(['status' => $statusBaru]);
                }
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

    public function bayarCustomSiswa(Request $request)
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'tanggal_bayar' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.nominal_bayar' => 'nullable|numeric|min:0',
            'items.*.metode_bayar' => ['nullable', Rule::in(['cash', 'cicil', 'transfer'])],
            'items.*.nama_bank' => 'nullable|string|max:100',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);

        // Hanya proses baris yang benar-benar diisi nominal-nya
        $itemsDiisi = collect($validated['items'])
            ->filter(fn ($item) => (float) ($item['nominal_bayar'] ?? 0) > 0);

        if ($itemsDiisi->isEmpty()) {
            return back()->with('error', 'Belum ada nominal pembayaran yang diisi.');
        }

        $tagihanIds = $itemsDiisi->keys()->map(fn ($id) => (int) $id)->values();

        $tagihans = Tagihan::query()
            ->where('siswa_id', $siswa->id)
            ->whereIn('id', $tagihanIds)
            ->withSum('potongans as total_potongan', 'nominal_potongan')
            ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
            ->get()
            ->keyBy('id');

        // Validasi tiap baris: metode wajib diisi, nominal tidak boleh melebihi sisa
        foreach ($itemsDiisi as $tagihanId => $item) {
            $tagihan = $tagihans->get((int) $tagihanId);

            if (!$tagihan) {
                return back()->with('error', 'Item tagihan tidak ditemukan.')->withInput();
            }

            if (empty($item['metode_bayar'])) {
                $label = $tagihan->itemPembayaran->nama_item ?? ('Tagihan #' . $tagihan->id);
                return back()->with('error', 'Metode pembayaran untuk item "' . $label . '" belum dipilih.')->withInput();
            }

            $potongan = (float) ($tagihan->total_potongan ?? 0);
            $sudahBayar = (float) ($tagihan->total_pembayaran ?? 0);
            $sisa = (float) round(max(0, (float) $tagihan->nominal_awal - $potongan - $sudahBayar));
            $nominalBayar = (float) round((float) $item['nominal_bayar']);

            if ($sisa <= 0) {
                $label = $tagihan->itemPembayaran->nama_item ?? ('Tagihan #' . $tagihan->id);
                return back()->with('error', 'Item "' . $label . '" sudah lunas.')->withInput();
            }

            if ($nominalBayar > $sisa) {
                $label = $tagihan->itemPembayaran->nama_item ?? ('Tagihan #' . $tagihan->id);
                return back()->with('error', 'Nominal bayar untuk item "' . $label . '" melebihi sisa tagihan.')->withInput();
            }
        }

        $pembayaranIds = [];
        $tanggalBayar = $this->normalisasiTanggalBayar($validated['tanggal_bayar']);

        DB::transaction(function () use ($itemsDiisi, $tagihans, $validated, $tanggalBayar, &$pembayaranIds) {
            foreach ($itemsDiisi as $tagihanId => $item) {
                $tagihan = $tagihans->get((int) $tagihanId);
                $nominalBayar = (float) round((float) $item['nominal_bayar']);

                $created = PembayaranTagihan::create([
                    'tagihan_id' => $tagihan->id,
                    'tanggal_bayar' => $tanggalBayar,
                    'nominal_bayar' => $nominalBayar,
                    'metode_bayar' => $item['metode_bayar'],
                    'nama_bank' => $item['nama_bank'] ?? null,
                    'catatan' => 'Pembayaran beberapa item sekaligus.',
                ]);

                $pembayaranIds[] = $created->id;

                $tagihan->fresh()->sinkronkanStatus();
            }
        });

        if (empty($pembayaranIds)) {
            return back()->with('error', 'Pembayaran gagal diproses.');
        }

        if ($request->boolean('cetak_nota')) {
            $pembayarans = PembayaranTagihan::query()
                ->with(['tagihan.siswa', 'tagihan.itemPembayaran'])
                ->whereIn('id', $pembayaranIds)
                ->orderBy('tanggal_bayar')
                ->orderBy('id')
                ->get();

            return view('keuangan.nota-pembayaran', compact('pembayarans'));
        }

        return back()->with('success', 'Pembayaran ' . count($pembayaranIds) . ' item berhasil disimpan.');
    }
}
