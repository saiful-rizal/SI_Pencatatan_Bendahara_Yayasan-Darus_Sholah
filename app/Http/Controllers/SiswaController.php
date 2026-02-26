<?php

namespace App\Http\Controllers;

use App\Imports\RowsImport;
use App\Models\PembayaranTagihan;
use App\Models\Siswa;
use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $jenjang = (string) $request->get('jenjang', '');
        $status = (string) $request->get('status', '');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = Siswa::query()
            ->with(['tagihans' => function ($query) {
                $query->with('itemPembayaran')
                    ->withSum('potongans as total_potongan', 'nominal_potongan')
                    ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
                    ->orderByDesc('id');
            }])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($subQuery) use ($q) {
                    $subQuery->where('nis', 'like', '%' . $q . '%')
                        ->orWhere('nama', 'like', '%' . $q . '%');
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            });

        $this->applyJenjangFilter($query, $jenjang);

        $siswas = $query->orderBy('nama')
            ->paginate($perPage)
            ->appends($request->query());

        $ringkasJenjang = [
            '10' => Siswa::query()->tap(fn ($q) => $this->applyJenjangFilter($q, '10'))->count(),
            '11' => Siswa::query()->tap(fn ($q) => $this->applyJenjangFilter($q, '11'))->count(),
            '12' => Siswa::query()->tap(fn ($q) => $this->applyJenjangFilter($q, '12'))->count(),
        ];

        return view('siswa.index', compact('siswas', 'q', 'jenjang', 'status', 'perPage', 'ringkasJenjang'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:50|unique:siswas,nis',
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'kelas' => 'required|string|max:50',
            'angkatan' => 'required|string|max:20',
            'kategori' => 'required|in:mondok,non_mondok',
            'status' => 'required|in:aktif,lulus',
        ]);

        Siswa::create($validated);

        return back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Siswa $siswa)
    {
        $validated = $request->validate([
            'nis' => 'required|string|max:50|unique:siswas,nis,' . $siswa->id,
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'kelas' => 'required|string|max:50',
            'angkatan' => 'required|string|max:20',
            'kategori' => 'required|in:mondok,non_mondok',
            'status' => 'required|in:aktif,lulus',
        ]);

        $siswa->update($validated);

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|mimes:xlsx,xls,csv',
        ]);

        $import = new RowsImport();
        Excel::import($import, $request->file('file_excel'));
        $rows = $import->rows ?? collect();

        if ($rows->count() < 2) {
            return back()->with('error', 'File Excel kosong atau tidak valid.');
        }

        $headers = $rows->first()->map(fn ($value) => strtolower(trim((string) $value)))->toArray();
        $index = array_flip($headers);

        foreach ($rows->skip(1) as $row) {
            $nis = trim((string) ($row[$index['nis'] ?? -1] ?? ''));
            $nama = trim((string) ($row[$index['nama'] ?? -1] ?? ''));

            if ($nis === '' || $nama === '') {
                continue;
            }

            Siswa::updateOrCreate(
                ['nis' => $nis],
                [
                    'nama' => $nama,
                    'jenis_kelamin' => strtoupper(trim((string) ($row[$index['jenis_kelamin'] ?? -1] ?? 'L'))) === 'P' ? 'P' : 'L',
                    'kelas' => trim((string) ($row[$index['kelas'] ?? -1] ?? '-')),
                    'angkatan' => trim((string) ($row[$index['angkatan'] ?? -1] ?? now()->year)),
                    'kategori' => strtolower(trim((string) ($row[$index['kategori'] ?? -1] ?? 'non_mondok'))) === 'mondok' ? 'mondok' : 'non_mondok',
                    'status' => strtolower(trim((string) ($row[$index['status'] ?? -1] ?? 'aktif'))) === 'lulus' ? 'lulus' : 'aktif',
                ]
            );
        }

        return back()->with('success', 'Import data siswa berhasil.');
    }

    public function cetak(Siswa $siswa)
    {
        $siswa->load(['tagihans' => function ($query) {
            $query->with('itemPembayaran')
                ->withSum('potongans as total_potongan', 'nominal_potongan')
                ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
                ->orderByDesc('id');
        }]);

        return view('siswa.cetak', compact('siswa'));
    }

    public function tanggungan(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $jenjang = (string) $request->get('jenjang', '');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = Siswa::query()
            ->with(['tagihans' => function ($query) {
                $query->withSum('potongans as total_potongan', 'nominal_potongan')
                    ->withSum('pembayarans as total_pembayaran', 'nominal_bayar');
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

        $this->applyJenjangFilter($query, $jenjang);

        $siswas = $query->orderBy('nama')
            ->paginate($perPage)
            ->appends($request->query());

        return view('tanggungan.index', compact('siswas', 'q', 'jenjang', 'perPage'));
    }

    public function bayarSemua(Request $request)
    {
        $validated = $request->validate([
            'siswa_id' => 'required|exists:siswas,id',
            'tanggal_bayar' => 'required|date',
            'metode_bayar' => 'nullable|string|max:100',
        ]);

        $siswa = Siswa::findOrFail($validated['siswa_id']);

        $tagihans = Tagihan::withSum('potongans as total_potongan', 'nominal_potongan')
            ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
            ->where('siswa_id', $siswa->id)
            ->get();

        DB::transaction(function () use ($tagihans, $validated) {
            foreach ($tagihans as $tagihan) {
                $potongan = (float) ($tagihan->total_potongan ?? 0);
                $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
                $sisa = max(0, (float) $tagihan->nominal_awal - $potongan - $pembayaran);

                if ($sisa <= 0) {
                    continue;
                }

                PembayaranTagihan::create([
                    'tagihan_id' => $tagihan->id,
                    'tanggal_bayar' => $validated['tanggal_bayar'],
                    'nominal_bayar' => $sisa,
                    'metode_bayar' => $validated['metode_bayar'] ?? null,
                    'catatan' => 'Pelunasan seluruh tanggungan siswa.',
                ]);

                $tagihan->fresh()->sinkronkanStatus();
            }
        });

        return redirect()
            ->route('tanggungan.index')
            ->with('success', 'Semua tanggungan siswa berhasil dibayar.');
    }

    private function applyJenjangFilter($query, string $jenjang): void
    {
        if (!in_array($jenjang, ['10', '11', '12'], true)) {
            return;
        }

        $query->where(function ($kelasQuery) use ($jenjang) {
            if ($jenjang === '10') {
                $kelasQuery->where('kelas', 'like', '10%')
                    ->orWhere('kelas', '=', 'X')
                    ->orWhere('kelas', 'like', 'X %');
            }

            if ($jenjang === '11') {
                $kelasQuery->where('kelas', 'like', '11%')
                    ->orWhere('kelas', '=', 'XI')
                    ->orWhere('kelas', 'like', 'XI %');
            }

            if ($jenjang === '12') {
                $kelasQuery->where('kelas', 'like', '12%')
                    ->orWhere('kelas', '=', 'XII')
                    ->orWhere('kelas', 'like', 'XII %');
            }
        });
    }
}
