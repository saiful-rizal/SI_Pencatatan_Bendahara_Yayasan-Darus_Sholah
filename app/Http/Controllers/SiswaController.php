<?php

namespace App\Http\Controllers;

use App\Imports\RowsImport;
use App\Models\DeletionHistory;
use App\Models\PembayaranTagihan;
use App\Models\Siswa;
use App\Models\Tagihan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SiswaExport;

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
                    ->with(['potongans:id,tagihan_id,keterangan,nominal_potongan'])
                    ->withSum('potongans as total_potongan', 'nominal_potongan')
                    ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
                    ->when(Schema::hasColumn('tagihans', 'kelas'), function ($tagihanQuery) {
                        $tagihanQuery->orderBy('kelas');
                    })
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

        $kelasOptions = Siswa::query()
            ->where('status', 'aktif')
            ->select('kelas')
            ->distinct()
            ->orderBy('kelas')
            ->pluck('kelas');

        $angkatanOptions = Siswa::query()
            ->where('status', 'aktif')
            ->select('angkatan')
            ->distinct()
            ->orderBy('angkatan')
            ->pluck('angkatan');

        return view('siswa.index', compact('siswas', 'q', 'jenjang', 'status', 'perPage', 'ringkasJenjang', 'kelasOptions', 'angkatanOptions'));
    }

    public function export(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $jenjang = (string) $request->get('jenjang', '');
        $status = (string) $request->get('status', '');

        $query = Siswa::query()
            ->with(['tagihans' => function ($query) {
                $query->withSum('potongans as total_potongan', 'nominal_potongan')
                    ->withSum('pembayarans as total_pembayaran', 'nominal_bayar');
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

        $rows = $query->orderBy('nama')->get();
        $fileName = 'Data_Siswa_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new SiswaExport($rows), $fileName);
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
            'status' => 'required|in:aktif,tidak_aktif,lulus',
        ]);

        $validated = $this->sinkronkanKelasStatus($validated);

        Siswa::create($validated);

        return back()->with('success', 'Data siswa berhasil ditambahkan.');
    }

    public function update(Request $request, Siswa $siswa)
    {
        $kelasSebelumnya = $siswa->kelas;

        $validated = $request->validate([
            'nis' => 'required|string|max:50|unique:siswas,nis,' . $siswa->id,
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'kelas' => 'required|string|max:50',
            'angkatan' => 'required|string|max:20',
            'kategori' => 'required|in:mondok,non_mondok',
            'status' => 'required|in:aktif,tidak_aktif,lulus',
        ]);

        $validated = $this->sinkronkanKelasStatus($validated);

        if (($validated['kelas'] ?? $kelasSebelumnya) !== $kelasSebelumnya) {
            $this->kunciKelasTagihanKosong($siswa, $kelasSebelumnya);
        }

        $siswa->update($validated);

        return back()->with('success', 'Data siswa berhasil diperbarui.');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file_excel' => 'required|file|max:5120|mimes:xlsx,xls,csv|mimetypes:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv,text/plain',
        ]);

        $import = new RowsImport();
        Excel::import($import, $request->file('file_excel'));
        $rows = $import->rows ?? collect();

        if ($rows->isEmpty()) {
            return back()->with('error', 'File Excel kosong atau tidak valid.');
        }

        $requiredHeaders = ['nis', 'nama', 'kelas', 'kategori'];
        $headerRowIndex = null;
        $index = [];

        foreach ($rows as $rowIndex => $row) {
            $headers = $row->map(fn ($value) => strtolower(trim((string) $value)))->toArray();
            $candidateIndex = array_flip($headers);

            $hasRequiredHeaders = true;
            foreach ($requiredHeaders as $header) {
                if (!array_key_exists($header, $candidateIndex)) {
                    $hasRequiredHeaders = false;
                    break;
                }
            }

            if ($hasRequiredHeaders) {
                $headerRowIndex = $rowIndex;
                $index = $candidateIndex;
                break;
            }
        }

        if ($headerRowIndex === null) {
            return back()->with(
                'error',
                'Format Excel tidak sesuai. Header wajib: nis, nama, kelas, kategori.'
            );
        }

        foreach ($rows->skip($headerRowIndex + 1) as $row) {
            $nis = trim((string) ($row[$index['nis'] ?? -1] ?? ''));
            $nama = trim((string) ($row[$index['nama'] ?? -1] ?? ''));

            if ($nis === '' || $nama === '') {
                continue;
            }

            $payload = [
                'nama' => $this->sanitizeImportText($nama),
                'jenis_kelamin' => strtoupper(trim((string) ($row[$index['jenis_kelamin'] ?? -1] ?? 'L'))) === 'P' ? 'P' : 'L',
                'kelas' => $this->sanitizeImportText(trim((string) ($row[$index['kelas'] ?? -1] ?? '-'))),
                'angkatan' => $this->sanitizeImportText(trim((string) ($row[$index['angkatan'] ?? -1] ?? now()->year))),
                'kategori' => strtolower(trim((string) ($row[$index['kategori'] ?? -1] ?? 'non_mondok'))) === 'mondok' ? 'mondok' : 'non_mondok',
                'status' => strtolower(trim((string) ($row[$index['status'] ?? -1] ?? 'aktif'))) === 'lulus' ? 'lulus' : 'aktif',
            ];

            Siswa::updateOrCreate(
                ['nis' => $nis],
                $this->sinkronkanKelasStatus($payload)
            );
        }

        return back()->with('success', 'Import data siswa berhasil.');
    }

    private function sanitizeImportText(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return $value;
        }

        if (preg_match('/^[=+\-@]/', $value) === 1) {
            $value = "'" . $value;
        }

        return preg_replace('/[^\pL\pN\s\-\/_\.]/u', '', $value) ?? $value;
    }

    public function cetak(Siswa $siswa)
    {
        $siswa->load(['tagihans' => function ($query) {
            $query->with('itemPembayaran')
                ->with(['potongans:id,tagihan_id,keterangan,nominal_potongan'])
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
                $query->with('itemPembayaran')
                    ->withSum('potongans as total_potongan', 'nominal_potongan')
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
            ->route('pengeluaran.index')
            ->with('success', 'Pembayaran seluruh tagihan siswa berhasil diproses.');
    }

    public function destroy(Siswa $siswa)
    {
        $nama = $siswa->nama;

        DeletionHistory::create([
            'menu' => 'Data Siswa',
            'entity_type' => 'Siswa',
            'entity_id' => $siswa->id,
            'label' => $siswa->nis . ' - ' . $nama,
            'deleted_by' => auth()->id(),
            'deleted_at' => now(),
        ]);

        $siswa->delete();

        return back()->with('success', 'Data siswa ' . $nama . ' berhasil dihapus.');
    }

    public function naikKelasMassal(Request $request)
    {
        $validated = $request->validate([
            'mode' => 'required|in:selected,all',
            'siswa_ids' => 'nullable|array',
            'siswa_ids.*' => 'integer|exists:siswas,id',
        ]);

        if ($validated['mode'] === 'selected' && empty($validated['siswa_ids'])) {
            return back()->with('error', 'Pilih minimal satu siswa untuk diproses.');
        }

        $siswasQuery = Siswa::query()->where('status', 'aktif');

        if ($validated['mode'] === 'selected') {
            $siswasQuery->whereIn('id', $validated['siswa_ids']);
        }

        $siswas = $siswasQuery->get();

        if ($siswas->isEmpty()) {
            return back()->with('error', 'Tidak ada siswa aktif yang dapat diproses.');
        }

        DB::transaction(function () use ($siswas) {
            foreach ($siswas as $siswa) {
                $kelasSebelumnya = $siswa->kelas;
                $kelasBaru = $this->getKelasBerikutnya($siswa->kelas);

                $statusBaru = strcasecmp($kelasBaru, 'Lulus') === 0 ? 'lulus' : 'aktif';

                if ($kelasBaru !== $kelasSebelumnya) {
                    $this->kunciKelasTagihanKosong($siswa, $kelasSebelumnya);
                }

                $siswa->update([
                    'kelas' => $kelasBaru,
                    'status' => $statusBaru,
                ]);
            }
        });

        return back()->with('success', 'Kenaikan kelas berhasil diproses (naik 1 tingkat).');
    }

    private function applyJenjangFilter($query, string $jenjang): void
    {
        $jenjang = strtoupper(trim($jenjang));
        $jenjang = match ($jenjang) {
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
            '13' => 'XIII',
            default => $jenjang,
        };

        if (!in_array($jenjang, ['X', 'XI', 'XII', 'XIII'], true)) {
            return;
        }

        $query->where(function ($kelasQuery) use ($jenjang) {
            if ($jenjang === 'X') {
                $kelasQuery->where('kelas', 'like', '10%')
                    ->orWhere('kelas', '=', 'X')
                    ->orWhere('kelas', 'like', 'X %');
            }

            if ($jenjang === 'XI') {
                $kelasQuery->where('kelas', 'like', '11%')
                    ->orWhere('kelas', '=', 'XI')
                    ->orWhere('kelas', 'like', 'XI %');
            }

            if ($jenjang === 'XII') {
                $kelasQuery->where('kelas', 'like', '12%')
                    ->orWhere('kelas', '=', 'XII')
                    ->orWhere('kelas', 'like', 'XII %');
            }

            if ($jenjang === 'XIII') {
                $kelasQuery->where('kelas', 'like', '13%')
                    ->orWhere('kelas', '=', 'XIII')
                    ->orWhere('kelas', 'like', 'XIII %');
            }
        });
    }

    private function sinkronkanKelasStatus(array $data): array
    {
        $kelas = trim((string) ($data['kelas'] ?? ''));
        $status = (string) ($data['status'] ?? 'aktif');

        if (strcasecmp($kelas, 'lulus') === 0 || $status === 'lulus') {
            $data['kelas'] = 'Lulus';
            $data['status'] = 'lulus';
            return $data;
        }

        $data['kelas'] = $this->normalisasiKelas($kelas);
        $data['status'] = 'aktif';

        return $data;
    }

    private function getKelasBerikutnya(string $kelas): string
    {
        $kelas = $this->normalisasiKelas(trim($kelas));

        if (preg_match('/^(12|XII)(.*)$/i', $kelas, $match)) {
            return 'Lulus';
        }

        if (preg_match('/^(11|XI)(.*)$/i', $kelas, $match)) {
            $suffix = trim((string) ($match[2] ?? ''));
            return '12' . ($suffix !== '' ? ' ' . ltrim($suffix, '-/ ') : '');
        }

        if (preg_match('/^(10|X)(.*)$/i', $kelas, $match)) {
            $suffix = trim((string) ($match[2] ?? ''));
            return '11' . ($suffix !== '' ? ' ' . ltrim($suffix, '-/ ') : '');
        }

        return $kelas;
    }

    private function normalisasiKelas(string $kelas): string
    {
        $kelas = preg_replace('/\s+/', ' ', trim($kelas)) ?? '';

        if ($kelas === '') {
            return $kelas;
        }

        if (strcasecmp($kelas, 'lulus') === 0) {
            return 'Lulus';
        }

        if (!preg_match('/^(13|12|11|10|XIII|XII|XI|X)(.*)$/i', $kelas, $match)) {
            return $kelas;
        }

        $prefix = strtoupper($match[1]);
        $suffix = trim((string) ($match[2] ?? ''));

        $angka = match ($prefix) {
            'X' => '10',
            'XI' => '11',
            'XII' => '12',
            'XIII' => '13',
            default => $prefix,
        };

        return $angka . ($suffix !== '' ? ' ' . ltrim($suffix, '-/ ') : '');
    }

    private function kunciKelasTagihanKosong(Siswa $siswa, ?string $kelasSebelumnya): void
    {
        if (!Schema::hasColumn('tagihans', 'kelas')) {
            return;
        }

        $kelasSebelumnya = trim((string) $kelasSebelumnya);
        if ($kelasSebelumnya === '') {
            return;
        }

        Tagihan::query()
            ->where('siswa_id', $siswa->id)
            ->whereNull('kelas')
            ->update(['kelas' => $kelasSebelumnya]);
    }
}
