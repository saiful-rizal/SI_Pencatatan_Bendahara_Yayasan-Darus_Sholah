<?php

namespace App\Http\Controllers;

use App\Exports\RekapExport;
use App\Models\PembayaranTagihan;
use App\Models\Siswa;
use App\Models\Tagihan;
use App\Models\TagihanPotongan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class RekapController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'nis' => ['nullable', 'string', 'max:100'],
            'kelas' => ['nullable', 'string', 'max:50'],
            'angkatan' => ['nullable', 'string', 'max:20'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $tanggalMulai = $validated['tanggal_mulai'] ?? null;
        $tanggalSelesai = $validated['tanggal_selesai'] ?? null;
        $nis = $validated['nis'] ?? null;
        $kelas = $this->normalisasiKelas($validated['kelas'] ?? null);
        $angkatan = $validated['angkatan'] ?? null;
        $perPage = (int) ($validated['per_page'] ?? 25);

        $siswaQuery = Siswa::query()
            ->select(['id', 'nis', 'nama', 'kelas', 'angkatan'])
            ->with(['tagihans' => function ($query) use ($tanggalMulai, $tanggalSelesai) {
                $query->select(['id', 'siswa_id', 'item_pembayaran_id', 'periode_bulan', 'periode_tahun', 'nominal_awal'])
                    ->with('itemPembayaran:id,nama_item')
                    ->with(['potongans:id,tagihan_id,keterangan,nominal_potongan'])
                    ->withSum('potongans as total_potongan', 'nominal_potongan')
                    ->withSum('pembayarans as total_pembayaran', 'nominal_bayar')
                    ->when($tanggalMulai && $tanggalSelesai, function ($tagihanQuery) use ($tanggalMulai, $tanggalSelesai) {
                        $tagihanQuery->whereBetween('created_at', [$tanggalMulai, $tanggalSelesai]);
                    })
                    ->orderByDesc('id');
            }])
            ->whereHas('tagihans', function ($query) use ($tanggalMulai, $tanggalSelesai) {
                $query->when($tanggalMulai && $tanggalSelesai, function ($tagihanQuery) use ($tanggalMulai, $tanggalSelesai) {
                    $tagihanQuery->whereBetween('created_at', [$tanggalMulai, $tanggalSelesai]);
                });
            });

        if ($nis) {
            $siswaQuery->where(function ($query) use ($nis) {
                $query->where('nis', 'like', '%' . $nis . '%')
                    ->orWhere('nama', 'like', '%' . $nis . '%');
            });
        }

        $this->applyKelasFilter($siswaQuery, $kelas);

        if (!empty($angkatan)) {
            $siswaQuery->where('angkatan', $angkatan);
        }

        $rekapPaginator = $siswaQuery->orderBy('nama')->paginate($perPage)->appends($request->query());

        $rekap = collect($rekapPaginator->items())->map(function ($siswa) {
            $detail = $siswa->tagihans->map(function ($tagihan) {
                $potongan = (float) ($tagihan->total_potongan ?? 0);
                $bayar = (float) ($tagihan->total_pembayaran ?? 0);
                $totalAkhir = max(0, (float) $tagihan->nominal_awal - $potongan);
                $sisa = max(0, $totalAkhir - $bayar);
                $keteranganPotongan = $tagihan->potongans
                    ->pluck('keterangan')
                    ->filter()
                    ->unique()
                    ->implode(', ');

                return [
                    'item' => $tagihan->itemPembayaran->nama_item ?? '-',
                    'kelas' => $tagihan->kelas ?? '-',
                    'periode' => $tagihan->periode_label,
                    'nominal_awal' => (float) $tagihan->nominal_awal,
                    'potongan' => $potongan,
                    'potongan_keterangan' => $keteranganPotongan !== '' ? $keteranganPotongan : '-',
                    'total_akhir' => $totalAkhir,
                    'pembayaran' => $bayar,
                    'sisa' => $sisa,
                ];
            })->values();

            return [
                'id' => $siswa->id,
                'nis' => $siswa->nis ?? '-',
                'nama' => $siswa->nama ?? '-',
                'kelas' => $this->normalisasiKelas($siswa->kelas ?? '-') ?? '-',
                'angkatan' => $siswa->angkatan ?? '-',
                'nominal_awal' => $detail->sum('nominal_awal'),
                'potongan' => $detail->sum('potongan'),
                'total_akhir' => $detail->sum('total_akhir'),
                'pembayaran' => $detail->sum('pembayaran'),
                'sisa' => $detail->sum('sisa'),
                'detail' => $detail,
            ];
        });

        $ringkasan = $this->buildRingkasan($nis, $kelas, $angkatan, $tanggalMulai, $tanggalSelesai);

        $kelasOptions = Siswa::query()
            ->select('kelas')
            ->distinct()
            ->pluck('kelas')
            ->map(fn ($kelasItem) => $this->normalisasiKelas((string) $kelasItem))
            ->filter()
            ->unique()
            ->sort(function ($a, $b) {
                $order = ['10' => 1, '11' => 2, '12' => 3, '13' => 4, 'LULUS' => 99];

                $rankA = $this->kelasRank((string) $a, $order);
                $rankB = $this->kelasRank((string) $b, $order);

                if ($rankA === $rankB) {
                    return strcmp((string) $a, (string) $b);
                }

                return $rankA <=> $rankB;
            })
            ->values();
        $angkatanOptions = Siswa::query()->select('angkatan')->distinct()->orderBy('angkatan')->pluck('angkatan');

        return view('keuangan.rekap', compact('rekap', 'rekapPaginator', 'ringkasan', 'request', 'kelasOptions', 'angkatanOptions'));
    }

    private function buildRingkasan(?string $nis, ?string $kelas, ?string $angkatan, ?string $tanggalMulai, ?string $tanggalSelesai): array
    {
        $potonganSub = TagihanPotongan::query()
            ->selectRaw('tagihan_id, SUM(nominal_potongan) as total_potongan')
            ->groupBy('tagihan_id');

        $pembayaranSub = PembayaranTagihan::query()
            ->selectRaw('tagihan_id, SUM(nominal_bayar) as total_pembayaran')
            ->groupBy('tagihan_id');

        $query = Tagihan::query()
            ->join('siswas', 'siswas.id', '=', 'tagihans.siswa_id')
            ->leftJoinSub($potonganSub, 'potongan', function ($join) {
                $join->on('potongan.tagihan_id', '=', 'tagihans.id');
            })
            ->leftJoinSub($pembayaranSub, 'pembayaran', function ($join) {
                $join->on('pembayaran.tagihan_id', '=', 'tagihans.id');
            });

        if (!empty($nis)) {
            $query->where(function ($inner) use ($nis) {
                $inner->where('siswas.nis', 'like', '%' . $nis . '%')
                    ->orWhere('siswas.nama', 'like', '%' . $nis . '%');
            });
        }

        if (!empty($kelas)) {
            $legacy = $this->kelasLegacyNumerik($kelas);
            $opsi = array_values(array_unique(array_filter([$kelas, $legacy])));
            $query->whereIn('siswas.kelas', $opsi);
        }

        if (!empty($angkatan)) {
            $query->where('siswas.angkatan', $angkatan);
        }

        if (!empty($tanggalMulai) && !empty($tanggalSelesai)) {
            $query->whereBetween('tagihans.created_at', [$tanggalMulai, $tanggalSelesai]);
        }

        $aggregate = $query->selectRaw(
            'COALESCE(SUM(tagihans.nominal_awal), 0) as nominal_awal, ' .
            'COALESCE(SUM(COALESCE(potongan.total_potongan, 0)), 0) as potongan, ' .
            'COALESCE(SUM(GREATEST(tagihans.nominal_awal - COALESCE(potongan.total_potongan, 0), 0)), 0) as total_akhir, ' .
            'COALESCE(SUM(COALESCE(pembayaran.total_pembayaran, 0)), 0) as pembayaran, ' .
            'COALESCE(SUM(GREATEST(GREATEST(tagihans.nominal_awal - COALESCE(potongan.total_potongan, 0), 0) - COALESCE(pembayaran.total_pembayaran, 0), 0)), 0) as sisa'
        )->first();

        return [
            'nominal_awal' => (float) ($aggregate->nominal_awal ?? 0),
            'potongan' => (float) ($aggregate->potongan ?? 0),
            'total_akhir' => (float) ($aggregate->total_akhir ?? 0),
            'pembayaran' => (float) ($aggregate->pembayaran ?? 0),
            'sisa' => (float) ($aggregate->sisa ?? 0),
        ];
    }

    public function export(Request $request)
    {
        $validated = $request->validate([
            'nis' => ['nullable', 'string', 'max:100'],
            'kelas' => ['nullable', 'string', 'max:50'],
            'angkatan' => ['nullable', 'string', 'max:20'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $query = Tagihan::with('siswa')
            ->withSum('potongans as total_potongan', 'nominal_potongan')
            ->withSum('pembayarans as total_pembayaran', 'nominal_bayar');

        if (!empty($validated['nis'])) {
            $nis = $validated['nis'];
            $query->whereHas('siswa', function ($q) use ($nis) {
                $q->where('nis', 'like', '%' . $nis . '%')
                    ->orWhere('nama', 'like', '%' . $nis . '%');
            });
        }

        if (!empty($validated['tanggal_mulai']) && !empty($validated['tanggal_selesai'])) {
            $query->whereBetween('created_at', [$validated['tanggal_mulai'], $validated['tanggal_selesai']]);
        }

        $kelas = $this->normalisasiKelas($validated['kelas'] ?? null);
        if (!empty($kelas)) {
            $query->whereHas('siswa', function ($q) use ($kelas) {
                $this->applyKelasFilter($q, $kelas);
            });
        }

        if (!empty($validated['angkatan'])) {
            $angkatan = $validated['angkatan'];
            $query->whereHas('siswa', function ($q) use ($angkatan) {
                $q->where('angkatan', $angkatan);
            });
        }

        $data = $query->orderByDesc('id')->get();
        $fileName = 'Rekap_Keuangan_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new RekapExport($data), $fileName);
    }

    private function applyKelasFilter($query, ?string $kelas): void
    {
        if ($kelas === null || $kelas === '') {
            return;
        }

        $legacy = $this->kelasLegacyNumerik($kelas);
        $opsi = array_values(array_unique(array_filter([$kelas, $legacy])));
        $query->whereIn('kelas', $opsi);
    }

    private function normalisasiKelas(?string $kelas): ?string
    {
        if ($kelas === null) {
            return null;
        }

        $kelas = preg_replace('/\s+/', ' ', trim($kelas)) ?? '';

        if ($kelas === '') {
            return null;
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
            'XIII' => '13',
            'XII' => '12',
            'XI' => '11',
            'X' => '10',
            default => $prefix,
        };

        return $angka . ($suffix !== '' ? ' ' . ltrim($suffix, '-/ ') : '');
    }

    private function kelasLegacyNumerik(?string $kelas): ?string
    {
        if ($kelas === null || strcasecmp($kelas, 'Lulus') === 0) {
            return $kelas;
        }

        if (!preg_match('/^(XIII|XII|XI|X)(.*)$/i', $kelas, $match)) {
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

    private function kelasRank(string $kelas, array $order): int
    {
        $token = strtoupper(trim(explode(' ', $kelas)[0] ?? ''));
        return $order[$token] ?? 50;
    }
}
