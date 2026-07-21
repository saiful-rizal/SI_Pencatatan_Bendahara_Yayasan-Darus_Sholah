<?php

namespace App\Http\Controllers;

use App\Models\Transaksi;
use App\Models\DetailTransaksi;
use App\Models\DeletionHistory;
use App\Models\ItemPembayaran;
use App\Models\PembayaranTagihan;
use App\Models\Siswa;
use Illuminate\Http\Request;
use App\Exports\SekolahExport;
use App\Exports\WaliExport;
use App\Exports\YayasanExport; // Export Yayasan ditambahkan
use App\Exports\PemasukanDanaExport;
use App\Exports\PengeluaranDanaExport;
use App\Exports\RekapKasExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;

class BendaharaController extends Controller
{
    public function index()
    {
        $totalMasuk = Transaksi::where('jenis', 'Masuk')->sum('total_bayar');
        $totalKeluar = Transaksi::where('jenis', 'Keluar')->sum('total_bayar');
        $saldo = $totalMasuk - $totalKeluar;

        // Riwayat Transaksi (tabel di Dashboard): item-item pembayaran tagihan yang
        // dibayar bersamaan (siswa & waktu bayar sama persis) disimpan sebagai
        // beberapa baris Transaksi terpisah (lihat PembayaranTagihan::sinkronkanTransaksi).
        // Supaya tampil sebagai 1 baris saja (selaras dengan nota yang sudah digabung),
        // baris-baris tersebut digabung di sini menggunakan GROUP BY.
        $groupKeyExpr = "CASE WHEN jenis = 'Masuk' AND pembayaran_tagihan_id IS NOT NULL AND siswa_id IS NOT NULL "
            . "THEN CONCAT('grp-', siswa_id, '-', tanggal) ELSE CONCAT('id-', id) END";

        $transaksis = Transaksi::query()
            ->selectRaw(
                'MIN(id) as id, MIN(jenis) as jenis, MIN(kategori) as kategori, MIN(siswa_id) as siswa_id, '
                . 'MIN(nama_siswa) as nama_siswa, MIN(kelas) as kelas, MIN(tanggal) as tanggal, '
                . 'SUM(total_bayar) as total_bayar, MAX(created_at) as created_at, COUNT(*) as item_count'
            )
            ->groupByRaw($groupKeyExpr)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(10);

        $months = [];
        $dataMasukChart = [];
        $dataKeluarChart = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->translatedFormat('F Y');

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

        // Rekap harian untuk widget "Transaksi Terkini": gabungkan item-item
        // pembayaran tagihan siswa yang dibayar bersamaan (siswa & waktu bayar sama
        // persis) jadi 1 baris — sama seperti tabel "Riwayat Transaksi" di bawah.
        // Transaksi Keluar (pengeluaran) TIDAK ikut digabung berdasarkan nama,
        // karena nama di sana adalah nama penerima/pihak dibayar, bukan siswa —
        // dua pengeluaran berbeda yang kebetulan penerimanya sama tetap harus
        // tampil sebagai 2 baris terpisah.
        $recentGroupKeyExpr = "CASE WHEN jenis = 'Masuk' AND pembayaran_tagihan_id IS NOT NULL AND siswa_id IS NOT NULL "
            . "THEN CONCAT('grp-', siswa_id, '-', tanggal) ELSE CONCAT('id-', id) END";

        $recentTransactions = Transaksi::query()
            ->whereDate('tanggal', today())
            ->selectRaw(
                'MIN(id) as id, MIN(jenis) as jenis, MIN(kategori) as kategori, '
                . 'MIN(nama_siswa) as nama_siswa, MIN(kelas) as kelas, MIN(tanggal) as tanggal, '
                . 'SUM(total_bayar) as total_bayar, COUNT(*) as item_count'
            )
            ->groupByRaw($recentGroupKeyExpr)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->take(5)
            ->get();

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

    public function pengeluaran(Request $request)
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:150'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $query = Transaksi::query()
            ->with('details')
            ->where('jenis', 'Keluar');

        if ($request->filled('q')) {
            $q = trim((string) $request->q);
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('nama_siswa', 'like', '%' . $q . '%')
                    ->orWhere('catatan', 'like', '%' . $q . '%');
            });
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', 'like', '%' . $request->kategori . '%');
        }

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        $pengeluarans = $query->latest('tanggal')->paginate(10)->appends($request->query());
        $totalPengeluaran = (clone $query)->sum('total_bayar');

        return view('keuangan.pengeluaran', compact('pengeluarans', 'totalPengeluaran', 'request'));
    }

    public function storePengeluaran(Request $request)
    {
        $validated = $request->validate([
            'kategori' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'nama_siswa' => ['nullable', 'string', 'max:150'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'nama_item' => ['required', 'array', 'min:1'],
            'nama_item.*' => ['nullable', 'string', 'max:150'],
            'harga' => ['required', 'array', 'size:' . count($request->input('nama_item', []))],
            'harga.*' => ['required', 'numeric', 'min:0'],
            'jumlah' => ['required', 'array', 'size:' . count($request->input('nama_item', []))],
            'jumlah.*' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated) {
            $totalBayar = 0;
            $userId = auth()->id();
            $siswa = $this->resolveSiswaByNama($validated['nama_siswa'] ?? null, null);

            foreach ($validated['harga'] as $key => $harga) {
                $totalBayar += round((float) $harga * (int) $validated['jumlah'][$key], 2);
            }
            $totalBayar = round($totalBayar, 2);

            $transaksi = Transaksi::create([
                'jenis' => 'Keluar',
                'kategori' => $validated['kategori'],
                'siswa_id' => $siswa?->id,
                'nama_siswa' => $validated['nama_siswa'] ?? null,
                'kelas' => null,
                'total_bayar' => $totalBayar,
                'tanggal' => $validated['tanggal'],
                'catatan' => $validated['catatan'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($validated['nama_item'] as $key => $nama) {
                if (!empty($nama)) {
                    $harga = round((float) $validated['harga'][$key], 2);
                    $jumlah = (int) $validated['jumlah'][$key];
                    DetailTransaksi::create([
                        'transaksi_id' => $transaksi->id,
                        'item_pembayaran_id' => $this->resolveItemPembayaranIdByNama($nama),
                        'nama_item' => $nama,
                        'harga' => $harga,
                        'jumlah' => $jumlah,
                        'subtotal' => round($harga * $jumlah, 2),
                    ]);
                }
            }
        });

        return redirect()->route('pengeluaran.index')->with('success', 'Data pengeluaran berhasil disimpan.');
    }

    // Input Data
    public function store(Request $request)
    {
        $validated = $request->validate([
            'jenis' => ['required', Rule::in(['Masuk', 'Keluar'])],
            'kategori' => ['required', 'string', 'max:100'],
            'tanggal' => ['required', 'date'],
            'nama_siswa' => ['nullable', 'string', 'max:150'],
            'kelas' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string', 'max:500'],
            'nama_item' => ['required', 'array', 'min:1'],
            'nama_item.*' => ['nullable', 'string', 'max:150'],
            'harga' => ['required', 'array', 'size:' . count($request->input('nama_item', []))],
            'harga.*' => ['required', 'numeric', 'min:0'],
            'jumlah' => ['required', 'array', 'size:' . count($request->input('nama_item', []))],
            'jumlah.*' => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($validated) {
            $totalBayar = 0;
            $userId = auth()->id();
            $kelasNormalized = $this->normalisasiKelas($validated['kelas'] ?? null);
            $siswa = $this->resolveSiswaByNama($validated['nama_siswa'] ?? null, $kelasNormalized);
            $kelasTransaksi = $kelasNormalized ?? $siswa?->kelas;

            foreach ($validated['harga'] as $key => $harga) {
                $totalBayar += round((float) $harga * (int) $validated['jumlah'][$key], 2);
            }
            $totalBayar = round($totalBayar, 2);

            $transaksi = Transaksi::create([
                'jenis' => $validated['jenis'],
                'kategori' => $validated['kategori'],
                'siswa_id' => $siswa?->id,
                'nama_siswa' => $validated['nama_siswa'] ?? null,
                'kelas' => $kelasTransaksi,
                'total_bayar' => $totalBayar,
                'tanggal' => $validated['tanggal'],
                'catatan' => $validated['catatan'] ?? null,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]);

            foreach ($validated['nama_item'] as $key => $nama) {
                if (!empty($nama)) {
                    $harga = round((float) $validated['harga'][$key], 2);
                    $jumlah = (int) $validated['jumlah'][$key];
                    DetailTransaksi::create([
                        'transaksi_id' => $transaksi->id,
                        'item_pembayaran_id' => $this->resolveItemPembayaranIdByNama($nama),
                        'nama_item' => $nama,
                        'harga' => $harga,
                        'jumlah' => $jumlah,
                        'subtotal' => round($harga * $jumlah, 2),
                    ]);
                }
            }
        });

        return redirect()->route('home')->with('success', 'Transaksi berhasil disimpan!');
    }

    // --- LAPORAN ---

    // 1. Laporan Sekolah
    public function laporanSekolah(Request $request)
    {
        $request->validate([
            'jenis' => ['nullable', Rule::in(['Masuk', 'Keluar'])],
            'kategori' => ['nullable', 'string', 'max:100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $perPage = (int) $request->input('per_page', 25);

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

        $data = $query->paginate($perPage)->appends($request->query());
        return view('laporan.sekolah', compact('data', 'request'));
    }

    // Export Excel Sekolah
    public function exportSekolah(Request $request)
    {
        $request->validate([
            'jenis' => ['nullable', Rule::in(['Masuk', 'Keluar'])],
            'kategori' => ['nullable', 'string', 'max:100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

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
        $request->validate([
            'nama_siswa' => ['nullable', 'string', 'max:150'],
            'kelas' => ['nullable', 'string', 'max:50'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $perPage = (int) $request->input('per_page', 25);
        $hasTagihanKelasColumn = Schema::hasColumn('tagihans', 'kelas');

        $query = PembayaranTagihan::query()
            ->with(['tagihan.itemPembayaran', 'tagihan.siswa'])
            ->whereHas('tagihan', function ($subQuery) {
                if (Schema::hasColumn('tagihans', 'kelas')) {
                    $subQuery->where(function ($kelasQuery) {
                        $kelasQuery
                            ->where('kelas', 'like', '10%')
                            ->orWhere('kelas', 'like', '11%')
                            ->orWhere('kelas', 'like', '12%')
                            ->orWhere('kelas', '=', 'X')
                            ->orWhere('kelas', 'like', 'X %')
                            ->orWhere('kelas', '=', 'XI')
                            ->orWhere('kelas', 'like', 'XI %')
                            ->orWhere('kelas', '=', 'XII')
                            ->orWhere('kelas', 'like', 'XII %');
                    });
                } else {
                    $subQuery->whereHas('siswa', function ($siswaQuery) {
                        $siswaQuery->where(function ($kelasQuery) {
                            $kelasQuery
                                ->where('kelas', 'like', '10%')
                                ->orWhere('kelas', 'like', '11%')
                                ->orWhere('kelas', 'like', '12%')
                                ->orWhere('kelas', '=', 'X')
                                ->orWhere('kelas', 'like', 'X %')
                                ->orWhere('kelas', '=', 'XI')
                                ->orWhere('kelas', 'like', 'XI %')
                                ->orWhere('kelas', '=', 'XII')
                                ->orWhere('kelas', 'like', 'XII %');
                        });
                    });
                }
            });

        if ($request->filled('nama_siswa')) {
            $nama = trim((string) $request->nama_siswa);
            $query->whereHas('tagihan.siswa', function ($subQuery) use ($nama) {
                $subQuery->where('nama', 'like', '%' . $nama . '%');
            });
        }

        if ($request->filled('kelas')) {
            $jenjangDipilih = $this->resolveJenjangKelas($request->kelas);

            if ($jenjangDipilih !== null) {
                if ($hasTagihanKelasColumn) {
                    $query->whereHas('tagihan', function ($subQuery) use ($jenjangDipilih) {
                        $subQuery->where(function ($kelasQuery) use ($jenjangDipilih) {
                            $this->applyJenjangFilterToKelasQuery($kelasQuery, $jenjangDipilih);
                        });
                    });
                } else {
                    $query->whereHas('tagihan.siswa', function ($subQuery) use ($jenjangDipilih) {
                        $subQuery->where(function ($kelasQuery) use ($jenjangDipilih) {
                            $this->applyJenjangFilterToKelasQuery($kelasQuery, $jenjangDipilih);
                        });
                    });
                }
            }
        }

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tanggal_bayar', [$request->tanggal_mulai, $request->tanggal_selesai]);
        } elseif ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_bayar', '>=', $request->tanggal_mulai);
        } elseif ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_bayar', '<=', $request->tanggal_selesai);
        }

        $totalPembayaran = (clone $query)->sum('nominal_bayar');
        $data = $query->orderBy('tanggal_bayar', 'desc')->paginate($perPage)->appends($request->query());
        return view('laporan.wali', compact('data', 'request', 'totalPembayaran'));
    }

    public function exportWali(Request $request)
    {
        $request->validate([
            'nama_siswa' => ['nullable', 'string', 'max:150'],
            'kelas' => ['nullable', 'string', 'max:50'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $hasTagihanKelasColumn = Schema::hasColumn('tagihans', 'kelas');

        $query = PembayaranTagihan::query()
            ->with(['tagihan.itemPembayaran', 'tagihan.siswa'])
            ->whereHas('tagihan', function ($subQuery) {
                if (Schema::hasColumn('tagihans', 'kelas')) {
                    $subQuery->where(function ($kelasQuery) {
                        $kelasQuery
                            ->where('kelas', 'like', '10%')
                            ->orWhere('kelas', 'like', '11%')
                            ->orWhere('kelas', 'like', '12%')
                            ->orWhere('kelas', '=', 'X')
                            ->orWhere('kelas', 'like', 'X %')
                            ->orWhere('kelas', '=', 'XI')
                            ->orWhere('kelas', 'like', 'XI %')
                            ->orWhere('kelas', '=', 'XII')
                            ->orWhere('kelas', 'like', 'XII %');
                    });
                } else {
                    $subQuery->whereHas('siswa', function ($siswaQuery) {
                        $siswaQuery->where(function ($kelasQuery) {
                            $kelasQuery
                                ->where('kelas', 'like', '10%')
                                ->orWhere('kelas', 'like', '11%')
                                ->orWhere('kelas', 'like', '12%')
                                ->orWhere('kelas', '=', 'X')
                                ->orWhere('kelas', 'like', 'X %')
                                ->orWhere('kelas', '=', 'XI')
                                ->orWhere('kelas', 'like', 'XI %')
                                ->orWhere('kelas', '=', 'XII')
                                ->orWhere('kelas', 'like', 'XII %');
                        });
                    });
                }
            });

        if ($request->filled('nama_siswa')) {
            $nama = trim((string) $request->nama_siswa);
            $query->whereHas('tagihan.siswa', function ($subQuery) use ($nama) {
                $subQuery->where('nama', 'like', '%' . $nama . '%');
            });
        }

        if ($request->filled('kelas')) {
            $jenjangDipilih = $this->resolveJenjangKelas($request->kelas);

            if ($jenjangDipilih !== null) {
                if ($hasTagihanKelasColumn) {
                    $query->whereHas('tagihan', function ($subQuery) use ($jenjangDipilih) {
                        $subQuery->where(function ($kelasQuery) use ($jenjangDipilih) {
                            $this->applyJenjangFilterToKelasQuery($kelasQuery, $jenjangDipilih);
                        });
                    });
                } else {
                    $query->whereHas('tagihan.siswa', function ($subQuery) use ($jenjangDipilih) {
                        $subQuery->where(function ($kelasQuery) use ($jenjangDipilih) {
                            $this->applyJenjangFilterToKelasQuery($kelasQuery, $jenjangDipilih);
                        });
                    });
                }
            }
        }

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $query->whereBetween('tanggal_bayar', [$request->tanggal_mulai, $request->tanggal_selesai]);
        } elseif ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_bayar', '>=', $request->tanggal_mulai);
        } elseif ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_bayar', '<=', $request->tanggal_selesai);
        }

        $data = $query->orderBy('tanggal_bayar', 'desc')->get();

        $periodeLabel = 'Semua Periode';
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $periodeLabel = date('d-m-Y', strtotime($request->tanggal_mulai)) . ' s/d ' . date('d-m-Y', strtotime($request->tanggal_selesai));
        }

        $fileName = 'Laporan_Wali_Murid_' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new WaliExport($data, $periodeLabel), $fileName);
    }

    // 2A. Laporan Pemasukan Dana
    public function laporanPemasukanDana(Request $request)
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
            'keterangan_tujuan' => ['nullable', 'string', 'max:200'],
        ]);

        $perPage = (int) $request->input('per_page', 10);
        $periode = $this->resolvePeriode($request);

        $query = Transaksi::query()
            ->with([
                'details:id,transaksi_id,nama_item',
                'siswa:id,nis',
                'pembayaranTagihan:id,tagihan_id,metode_bayar,nama_bank',
                'pembayaranTagihan.tagihan:id,item_pembayaran_id,periode_bulan,periode_tahun',
                'pembayaranTagihan.tagihan.itemPembayaran:id,nama_item',
            ])
            ->where('jenis', 'Masuk')
            ->whereBetween('tanggal', [$periode['start'], $periode['end']])
            ->when($request->filled('keterangan_tujuan'), function ($query) use ($request) {
                $keyword = trim((string) $request->keterangan_tujuan);
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('catatan', 'like', '%' . $keyword . '%')
                        ->orWhere('nama_siswa', 'like', '%' . $keyword . '%')
                        ->orWhere('kelas', 'like', '%' . $keyword . '%')
                        ->orWhereHas('details', function ($detailQuery) use ($keyword) {
                            $detailQuery->where('nama_item', 'like', '%' . $keyword . '%');
                        })
                        ->orWhereHas('pembayaranTagihan.tagihan.itemPembayaran', function ($itemQuery) use ($keyword) {
                            $itemQuery->where('nama_item', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc');

        $totalPemasukan = (clone $query)->sum('total_bayar');
        $data = $query->paginate($perPage)->appends($request->query());

        // Data lengkap (tidak dipaginasi) khusus untuk bagian cetak/print,
        // supaya hasil cetak selalu memuat SEMUA transaksi pada periode/filter
        // terkait, bukan cuma satu halaman yang sedang ditampilkan di layar.
        $dataCetak = (clone $query)->get();

        $rekapItemCetak = [];
        foreach ($dataCetak as $row) {
            $itemPembayaran = $row->details->pluck('nama_item')->filter()->unique()->implode(', ');
            if ($itemPembayaran === '') {
                $itemPembayaran = optional(optional($row->pembayaranTagihan)->tagihan)->itemPembayaran->nama_item ?? ($row->kategori ?: '-');
            }

            if (!isset($rekapItemCetak[$itemPembayaran])) {
                $rekapItemCetak[$itemPembayaran] = ['jumlah_transaksi' => 0, 'total' => 0.0];
            }
            $rekapItemCetak[$itemPembayaran]['jumlah_transaksi']++;
            $rekapItemCetak[$itemPembayaran]['total'] += (float) $row->total_bayar;
        }
        arsort($rekapItemCetak);

        return view('laporan.pemasukan-dana', [
            'data' => $data,
            'dataCetak' => $dataCetak,
            'rekapItemCetak' => $rekapItemCetak,
            'totalPemasukan' => $totalPemasukan,
            'periode' => $periode,
            'request' => $request,
        ]);
    }

    public function exportPemasukanDana(Request $request)
    {
        $periode = $this->resolvePeriode($request);

        $data = Transaksi::query()
            ->with([
                'details:id,transaksi_id,nama_item',
                'siswa:id,nis',
                'pembayaranTagihan:id,tagihan_id,metode_bayar,nama_bank',
                'pembayaranTagihan.tagihan:id,item_pembayaran_id,periode_bulan,periode_tahun',
                'pembayaranTagihan.tagihan.itemPembayaran:id,nama_item',
            ])
            ->where('jenis', 'Masuk')
            ->whereBetween('tanggal', [$periode['start'], $periode['end']])
            ->when($request->filled('keterangan_tujuan'), function ($query) use ($request) {
                $keyword = trim((string) $request->keterangan_tujuan);
                $query->where(function ($subQuery) use ($keyword) {
                    $subQuery->where('catatan', 'like', '%' . $keyword . '%')
                        ->orWhere('nama_siswa', 'like', '%' . $keyword . '%')
                        ->orWhere('kelas', 'like', '%' . $keyword . '%')
                        ->orWhereHas('details', function ($detailQuery) use ($keyword) {
                            $detailQuery->where('nama_item', 'like', '%' . $keyword . '%');
                        })
                        ->orWhereHas('pembayaranTagihan.tagihan.itemPembayaran', function ($itemQuery) use ($keyword) {
                            $itemQuery->where('nama_item', 'like', '%' . $keyword . '%');
                        });
                });
            })
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $fileName = 'Laporan_Pemasukan_Dana_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new PemasukanDanaExport($data, $periode['label'], $request->filled('keterangan_tujuan') ? trim((string) $request->keterangan_tujuan) : null), $fileName);
    }

    // 2B. Laporan Pengeluaran Dana
    public function laporanPengeluaranDana(Request $request)
    {
        $request->validate([
            'per_page' => ['nullable', 'integer', 'min:10', 'max:100'],
        ]);

        $perPage = (int) $request->input('per_page', 10);
        $periode = $this->resolvePeriode($request);

        $query = Transaksi::query()
            ->with('details')
            ->where('jenis', 'Keluar')
            ->whereBetween('tanggal', [$periode['start'], $periode['end']])
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc');

        $totalPengeluaran = (clone $query)->sum('total_bayar');
        $data = $query->paginate($perPage)->appends($request->query());

        // Data lengkap (tidak dipaginasi) khusus untuk bagian cetak/print,
        // supaya hasil cetak selalu memuat SEMUA transaksi pada periode yang
        // dipilih, bukan cuma satu halaman yang sedang ditampilkan di layar.
        $dataCetak = (clone $query)->get();

        // Pecah setiap transaksi menjadi satu baris per ITEM (nama item, jumlah x
        // harga, subtotal per item) - bukan cuma satu baris ringkas per transaksi -
        // supaya hasil cetak PDF sedetail file Export Excel.
        $dataCetakDetail = $this->flattenPengeluaranDetail($dataCetak);

        $rekapJenisCetak = [];
        foreach ($dataCetak as $row) {
            $jenis = $row->kategori ?: '-';
            if (!isset($rekapJenisCetak[$jenis])) {
                $rekapJenisCetak[$jenis] = ['jumlah_transaksi' => 0, 'total' => 0.0];
            }
            $rekapJenisCetak[$jenis]['jumlah_transaksi']++;
            $rekapJenisCetak[$jenis]['total'] += (float) $row->total_bayar;
        }
        arsort($rekapJenisCetak);

        return view('laporan.pengeluaran-dana', [
            'data' => $data,
            'dataCetak' => $dataCetak,
            'dataCetakDetail' => $dataCetakDetail,
            'rekapJenisCetak' => $rekapJenisCetak,
            'totalPengeluaran' => $totalPengeluaran,
            'periode' => $periode,
            'request' => $request,
        ]);
    }

    /**
     * Pecah koleksi Transaksi (jenis Keluar) menjadi baris per-item, sehingga
     * rincian item, jumlah x harga, dan subtotal per item ikut tampil - dipakai
     * bersama oleh tampilan cetak PDF dan Export Excel supaya datanya konsisten.
     */
    private function flattenPengeluaranDetail($transaksiCollection): array
    {
        $transaksiCollection->loadMissing('details');

        $rows = [];
        $no = 1;

        foreach ($transaksiCollection as $t) {
            $tanggal = optional($t->tanggal)->format('d-m-Y') ?: '-';
            $noTransaksi = 'TRX' . optional($t->tanggal)->format('ymd') . str_pad((string) $t->id, 4, '0', STR_PAD_LEFT);
            $jenis = $t->kategori ?: '-';
            $penerima = $t->nama_siswa ?: '-';
            $keterangan = $t->catatan ?: '-';

            if ($t->details->isNotEmpty()) {
                foreach ($t->details as $d) {
                    $jumlah = (int) $d->jumlah;
                    $harga = (float) $d->harga;
                    $rows[] = [
                        'no' => $no++,
                        'no_transaksi' => $noTransaksi,
                        'tanggal' => $tanggal,
                        'jenis' => $jenis,
                        'item' => $d->nama_item ?: '-',
                        'jumlah_label' => $jumlah > 0 ? ($jumlah . ' x Rp ' . number_format($harga, 0, ',', '.')) : '-',
                        'keterangan' => $keterangan,
                        'penerima' => $penerima,
                        'nominal' => (float) $d->subtotal,
                    ];
                }
            } else {
                // Data lama / pengeluaran tanpa rincian item: tetap tampil 1 baris
                // berdasarkan catatan & total transaksi, supaya tidak hilang.
                $rows[] = [
                    'no' => $no++,
                    'no_transaksi' => $noTransaksi,
                    'tanggal' => $tanggal,
                    'jenis' => $jenis,
                    'item' => '-',
                    'jumlah_label' => '-',
                    'keterangan' => $keterangan,
                    'penerima' => $penerima,
                    'nominal' => (float) $t->total_bayar,
                ];
            }
        }

        return $rows;
    }

    public function exportPengeluaranDana(Request $request)
    {
        $periode = $this->resolvePeriode($request);

        $data = Transaksi::query()
            ->with('details')
            ->where('jenis', 'Keluar')
            ->whereBetween('tanggal', [$periode['start'], $periode['end']])
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $dataDetail = $this->flattenPengeluaranDetail($data);

        $rekapJenis = [];
        foreach ($data as $row) {
            $jenis = $row->kategori ?: '-';
            if (!isset($rekapJenis[$jenis])) {
                $rekapJenis[$jenis] = ['jumlah_transaksi' => 0, 'total' => 0.0];
            }
            $rekapJenis[$jenis]['jumlah_transaksi']++;
            $rekapJenis[$jenis]['total'] += (float) $row->total_bayar;
        }
        arsort($rekapJenis);

        $fileName = 'Laporan_Pengeluaran_Dana_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new PengeluaranDanaExport(
            $dataDetail,
            $rekapJenis,
            (float) $data->sum('total_bayar'),
            $periode['label']
        ), $fileName);
    }

    // 2C. Laporan Rekapitulasi Keuangan / Kas
    public function laporanRekapKas(Request $request)
    {
        $periode = $this->resolvePeriode($request);
        $ringkasanKas = $this->hitungRingkasanKas($periode);

        $chartLabels = ['Pemasukan', 'Pengeluaran', 'Saldo Akhir'];
        $chartData = [
            (float) $ringkasanKas['totalPemasukan'],
            (float) $ringkasanKas['totalPengeluaran'],
            (float) $ringkasanKas['saldoAkhirKas'],
        ];

        return view('laporan.rekap-kas', [
            'periode' => $periode,
            'request' => $request,
            'saldoAwalKas' => $ringkasanKas['saldoAwalKas'],
            'totalPemasukan' => $ringkasanKas['totalPemasukan'],
            'totalPengeluaran' => $ringkasanKas['totalPengeluaran'],
            'saldoAkhirKas' => $ringkasanKas['saldoAkhirKas'],
            'rincianPemasukan' => $ringkasanKas['rincianPemasukan'],
            'rincianPengeluaran' => $ringkasanKas['rincianPengeluaran'],
            'tren' => $ringkasanKas['tren'],
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
        ]);
    }

    public function exportRekapKas(Request $request)
    {
        $periode = $this->resolvePeriode($request);
        $ringkasanKas = $this->hitungRingkasanKas($periode);

        $fileName = 'Laporan_Rekap_Kas_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new RekapKasExport($periode['label'], $ringkasanKas), $fileName);
    }

    // 3. Laporan Yayasan (View)
    public function laporanYayasan(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'group_by' => ['nullable', 'string'],
        ]);

        $groupBy = $request->input('group_by', 'kategori');
        $itemPembayaranList = ItemPembayaran::query()
            ->orderBy('nama_item')
            ->get(['id', 'kode', 'nama_item']);

        if ($groupBy === 'per_item') {
            $data = $this->buildLaporanYayasanPerItem($request);
        } elseif (is_numeric($groupBy)) {
            $itemPembayaran = ItemPembayaran::find((int) $groupBy);
            $data = $itemPembayaran
                ? $this->buildLaporanYayasanFilterItem($request, $itemPembayaran)
                : $this->buildLaporanYayasanPerKategori($request);
        } else {
            $data = $this->buildLaporanYayasanPerKategori($request);
        }

        $data['groupBy'] = $groupBy;
        $data['request'] = $request;
        $data['itemPembayaranList'] = $itemPembayaranList;

        return view('laporan.yayasan', $data);
    }

    private function buildDetailMasuk($masukCollection, string $groupBy = 'kategori')
    {
        $masukCollection->loadMissing(['details', 'pembayaranTagihan']);

        return $masukCollection
            ->groupBy(function ($t) use ($groupBy) {
                if ($groupBy === 'per_item') {
                    $namaItem = $t->details->pluck('nama_item')->filter()->unique()->implode(', ');
                    return $namaItem !== '' ? $namaItem : ($t->kategori ?? '-');
                }
                return $t->kategori ?? '-';
            })
            ->map(function ($items) {
                return $items->map(function ($t) {
                    $namaItem = $t->details->pluck('nama_item')->filter()->unique()->implode(', ');
                    $metode = $t->pembayaranTagihan->metode_bayar ?? null;
                    $namaBank = $t->pembayaranTagihan->nama_bank ?? null;
                    $metodeLabel = $metode ? strtoupper($metode) : '-';
                    if ($metode === 'transfer' && $namaBank) {
                        $metodeLabel .= ' (' . strtoupper($namaBank) . ')';
                    }
                    return [
                        'tanggal' => optional($t->tanggal)->format('d-m-Y') ?? '-',
                        'nama_siswa' => $t->nama_siswa ?? '-',
                        'kelas' => $t->kelas ?? '-',
                        'item' => $namaItem !== '' ? $namaItem : ($t->catatan ?: '-'),
                        'metode' => $metodeLabel,
                        'nominal' => (float) $t->total_bayar,
                    ];
                })->sortBy('tanggal')->values();
            });
    }

    private function buildDetailKeluar($keluarCollection)
    {
        $keluarCollection->loadMissing(['creator', 'details']);

        return $keluarCollection
            ->groupBy(fn ($t) => $t->kategori ?? '-')
            ->map(function ($items) {
                $rows = collect();

                foreach ($items as $t) {
                    $tanggal = optional($t->tanggal)->format('d-m-Y') ?? '-';
                    $dicatatOleh = $t->creator->name ?? '-';
                    $keterangan = $t->catatan ?: '-';

                    if ($t->details->isNotEmpty()) {
                        foreach ($t->details as $d) {
                            $jumlah = (int) $d->jumlah;
                            $harga = (float) $d->harga;
                            $rows->push([
                                'tanggal' => $tanggal,
                                'item' => $d->nama_item ?: '-',
                                'jumlah_label' => $jumlah > 0 ? ($jumlah . ' x Rp ' . number_format($harga, 0, ',', '.')) : '-',
                                'keterangan' => $keterangan,
                                'dicatat_oleh' => $dicatatOleh,
                                'nominal' => (float) $d->subtotal,
                            ]);
                        }
                    } else {
                        // Data lama / pengeluaran tanpa rincian item: tetap tampil 1 baris
                        // berdasarkan catatan & total transaksi, supaya tidak hilang dari laporan.
                        $rows->push([
                            'tanggal' => $tanggal,
                            'item' => '-',
                            'jumlah_label' => '-',
                            'keterangan' => $keterangan,
                            'dicatat_oleh' => $dicatatOleh,
                            'nominal' => (float) $t->total_bayar,
                        ]);
                    }
                }

                return $rows->sortBy('tanggal')->values();
            });
    }

    private function buildLaporanYayasanPerKategori(Request $request): array
    {
        $queryMasuk = Transaksi::where('jenis', 'Masuk');
        $queryKeluar = Transaksi::where('jenis', 'Keluar');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $queryMasuk->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
            $queryKeluar->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        $masuk = $queryMasuk->get();
        $keluar = $queryKeluar->get();

        return [
            'reportMasuk' => $masuk->groupBy('kategori')->map(fn ($item) => $item->sum('total_bayar')),
            'reportKeluar' => $keluar->groupBy('kategori')->map(fn ($item) => $item->sum('total_bayar')),
            'detailMasuk' => $this->buildDetailMasuk($masuk, 'kategori'),
            'detailKeluar' => $this->buildDetailKeluar($keluar),
            'totalMasuk' => $masuk->sum('total_bayar'),
            'totalKeluar' => $keluar->sum('total_bayar'),
            'saldo' => $masuk->sum('total_bayar') - $keluar->sum('total_bayar'),
        ];
    }

    private function buildLaporanYayasanPerItem(Request $request): array
    {
        $queryMasuk = Transaksi::where('jenis', 'Masuk');
        $queryKeluar = Transaksi::where('jenis', 'Keluar');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $queryMasuk->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
            $queryKeluar->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        $masuk = $queryMasuk->get();
        $keluar = $queryKeluar->get();

        // Kelompokkan pemasukan berdasarkan nama_item dari detail_transaksis
        $detailMasuk = DetailTransaksi::whereIn('transaksi_id', $masuk->pluck('id'))
            ->selectRaw('transaksi_id, nama_item, SUM(subtotal) as total')
            ->groupBy('transaksi_id', 'nama_item')
            ->get()
            ->groupBy('nama_item')
            ->map(fn ($items) => $items->sum('total'));

        return [
            'reportMasuk' => $detailMasuk,
            'reportKeluar' => $keluar->groupBy('kategori')->map(fn ($item) => $item->sum('total_bayar')),
            'detailMasuk' => $this->buildDetailMasuk($masuk, 'per_item'),
            'detailKeluar' => $this->buildDetailKeluar($keluar),
            'totalMasuk' => $masuk->sum('total_bayar'),
            'totalKeluar' => $keluar->sum('total_bayar'),
            'saldo' => $masuk->sum('total_bayar') - $keluar->sum('total_bayar'),
        ];
    }

    private function buildLaporanYayasanFilterItem(Request $request, ItemPembayaran $item): array
    {
        $tanggalMulai = $request->input('tanggal_mulai');
        $tanggalSelesai = $request->input('tanggal_selesai');
        $itemId = $item->id;

        // Ambil ID transaksi dari pembayaran tagihan untuk item ini
        $transaksiIdsViaTagihan = Transaksi::query()
            ->select('transaksis.id')
            ->join('pembayaran_tagihans', 'pembayaran_tagihans.id', '=', 'transaksis.pembayaran_tagihan_id')
            ->join('tagihans', 'tagihans.id', '=', 'pembayaran_tagihans.tagihan_id')
            ->where('tagihans.item_pembayaran_id', $itemId)
            ->where('transaksis.jenis', 'Masuk')
            ->when($tanggalMulai && $tanggalSelesai, fn ($q) => $q->whereBetween('transaksis.tanggal', [$tanggalMulai, $tanggalSelesai]))
            ->pluck('id');

        // Ambil ID transaksi dari detail_transaksis untuk item ini
        $transaksiIdsViaDetail = DetailTransaksi::query()
            ->where('item_pembayaran_id', $itemId)
            ->pluck('transaksi_id');

        $allIds = $transaksiIdsViaTagihan
            ->merge($transaksiIdsViaDetail)
            ->unique()
            ->values();

        if ($allIds->isEmpty()) {
            return [
                'reportMasuk' => collect(),
                'reportKeluar' => collect(),
                'detailMasuk' => collect(),
                'detailKeluar' => collect(),
                'totalMasuk' => 0,
                'totalKeluar' => 0,
                'saldo' => 0,
                'filterItem' => $item,
            ];
        }

        $queryMasuk = Transaksi::whereIn('id', $allIds)->where('jenis', 'Masuk');
        $queryKeluar = Transaksi::whereIn('id', $allIds)->where('jenis', 'Keluar');

        if ($tanggalMulai && $tanggalSelesai) {
            $queryMasuk->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);
            $queryKeluar->whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai]);
        }

        $masuk = $queryMasuk->get();
        $keluar = $queryKeluar->get();

        return [
            'reportMasuk' => $masuk->groupBy('kategori')->map(fn ($item) => $item->sum('total_bayar')),
            'reportKeluar' => $keluar->groupBy('kategori')->map(fn ($item) => $item->sum('total_bayar')),
            'detailMasuk' => $this->buildDetailMasuk($masuk, 'kategori'),
            'detailKeluar' => $this->buildDetailKeluar($keluar),
            'totalMasuk' => $masuk->sum('total_bayar'),
            'totalKeluar' => $keluar->sum('total_bayar'),
            'saldo' => $masuk->sum('total_bayar') - $keluar->sum('total_bayar'),
            'filterItem' => $item,
        ];
    }

    // Export Excel Yayasan
    public function exportYayasan(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
            'group_by' => ['nullable', 'string'],
        ]);

        $groupBy = $request->input('group_by', 'kategori');

        if ($groupBy === 'per_item') {
            $data = $this->buildLaporanYayasanPerItem($request);
            $groupLabel = 'Per Item';
        } elseif (is_numeric($groupBy)) {
            $itemPembayaran = ItemPembayaran::find((int) $groupBy);
            $data = $itemPembayaran
                ? $this->buildLaporanYayasanFilterItem($request, $itemPembayaran)
                : $this->buildLaporanYayasanPerKategori($request);
            $groupLabel = $itemPembayaran?->nama_item ?? 'Per Kategori';
        } else {
            $data = $this->buildLaporanYayasanPerKategori($request);
            $groupLabel = 'Per Kategori';
        }

        $nomorSurat = '001/B/SMA.U.BPPT.DS/' . now()->format('d/m/Y');
        $filterItem = isset($data['filterItem']) ? $data['filterItem'] : null;
        $periode = ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai'))
            ? date('d-m-Y', strtotime($request->tanggal_mulai)) . ' s/d ' . date('d-m-Y', strtotime($request->tanggal_selesai))
            : 'Semua Periode';
        $dicetakOleh = auth()->user()->name ?? '-';

        $fileName = 'Laporan_Yayasan_' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new YayasanExport(
            $data['reportMasuk'],
            $data['reportKeluar'],
            $data['totalMasuk'],
            $data['totalKeluar'],
            $data['saldo'],
            $groupLabel,
            $nomorSurat,
            $filterItem,
            $data['detailMasuk'] ?? collect(),
            $data['detailKeluar'] ?? collect(),
            $periode,
            $dicetakOleh
        ), $fileName);
    }

    // 4. Cetak Nota
    public function cetakNota($id)
    {
        $transaksi = Transaksi::with(['details.transaksi.pembayaranTagihan', 'pembayaranTagihan', 'siswa'])->findOrFail($id);

        // Untuk transaksi Masuk yang lahir dari pembayaran tagihan: setiap item tagihan
        // yang dibayar dalam satu kali submit form tersimpan sebagai Transaksi terpisah
        // (masing-masing 1 item, masing-masing bisa punya metode_bayar sendiri).
        // Supaya nota yang dicetak dari Dashboard tidak pecah jadi banyak nota,
        // gabungkan item-item yang dibayar bersamaan (siswa & waktu bayar sama persis)
        // menjadi satu nota, sama seperti nota Keluar — sambil tetap membawa metode
        // pembayaran masing-masing item lewat details.transaksi.pembayaranTagihan.
        if ($transaksi->jenis === 'Masuk' && $transaksi->pembayaran_tagihan_id && $transaksi->siswa_id) {
            $satuNota = Transaksi::with('details.transaksi.pembayaranTagihan')
                ->where('jenis', 'Masuk')
                ->where('siswa_id', $transaksi->siswa_id)
                ->where('tanggal', $transaksi->tanggal)
                ->whereNotNull('pembayaran_tagihan_id')
                ->orderBy('id')
                ->get();

            if ($satuNota->count() > 1) {
                $transaksi->setRelation('details', $satuNota->flatMap(function ($t) {
                    return $t->details->each(fn ($d) => $d->setRelation('transaksi', $t));
                })->values());
                $transaksi->total_bayar = $satuNota->sum('total_bayar');
            }
        }

        return view('laporan.nota', compact('transaksi'));
    }

    // Cetak beberapa nota sekaligus (satu file, tiap nota di halaman terpisah saat print).
    // Hanya untuk transaksi yang lahir dari alur Transaksi Pembayaran (punya pembayaran_tagihan_id),
    // supaya nota yang dicetak selalu punya data siswa/kategori/metode yang lengkap.
    public function cetakNotaSemua(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids', '')))
            ->map(fn ($id) => (int) trim($id))
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $query = Transaksi::with(['details', 'pembayaranTagihan', 'siswa'])
            ->whereNotNull('pembayaran_tagihan_id');

        if ($ids->isNotEmpty()) {
            $query->whereIn('id', $ids);
        }

        $transaksis = $query->orderBy('tanggal')->orderBy('id')->get();

        return view('laporan.nota-semua', compact('transaksis'));
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $transaksi = Transaksi::findOrFail($id);

            // Baris ini bisa mewakili beberapa item pembayaran tagihan yang digabung
            // jadi 1 baris di tampilan Dashboard (lihat index()). Hapus seluruh
            // anggota grupnya sekaligus supaya tidak ada baris "yatim" yang tertinggal.
            $grup = collect([$transaksi]);
            if ($transaksi->jenis === 'Masuk' && $transaksi->pembayaran_tagihan_id && $transaksi->siswa_id) {
                $grup = Transaksi::where('jenis', 'Masuk')
                    ->where('siswa_id', $transaksi->siswa_id)
                    ->where('tanggal', $transaksi->tanggal)
                    ->whereNotNull('pembayaran_tagihan_id')
                    ->get();
            }

            // Transaksi yang dihapus cukup tersimpan di "Daftar Backup Transaksi"
            // (soft delete pada tabel transaksis) dan tidak perlu dicatat lagi ke
            // "Log Penghapusan Semua Menu" supaya tidak dobel tampil di kedua tabel.
            foreach ($grup as $item) {
                $item->delete();
            }
        });

        return back()->with('success', 'Data dipindahkan ke menu Riwayat dan dapat dipulihkan.');
    }

    public function riwayat()
    {
        $groupKeyExpr = "CASE WHEN jenis = 'Masuk' AND pembayaran_tagihan_id IS NOT NULL AND siswa_id IS NOT NULL "
            . "THEN CONCAT('grp-', siswa_id, '-', tanggal) ELSE CONCAT('id-', id) END";

        $transaksis = Transaksi::onlyTrashed()
            ->selectRaw(
                'MIN(id) as id, MIN(jenis) as jenis, MIN(kategori) as kategori, MIN(siswa_id) as siswa_id, '
                . 'MIN(nama_siswa) as nama_siswa, MIN(kelas) as kelas, MIN(tanggal) as tanggal, '
                . 'SUM(total_bayar) as total_bayar, MAX(deleted_at) as deleted_at, COUNT(*) as item_count'
            )
            ->groupByRaw($groupKeyExpr)
            ->orderByDesc('deleted_at')
            ->paginate(10, ['*'], 'transaksi_page');

        // Transaksi sudah punya tampilannya sendiri di "Daftar Backup Transaksi",
        // jadi disembunyikan dari "Log Penghapusan Semua Menu" (termasuk data lama
        // yang sempat tercatat sebelum perubahan ini).
        $deletionHistories = DeletionHistory::with('user')
            ->where('entity_type', '!=', 'Transaksi')
            ->latest('deleted_at')
            ->paginate(10, ['*'], 'history_page');

        return view('keuangan.riwayat', compact('transaksis', 'deletionHistories'));
    }

    public function restore($id)
    {
        DB::transaction(function () use ($id) {
            $transaksi = Transaksi::onlyTrashed()->findOrFail($id);

            // Simetris dengan destroy(): kalau transaksi ini adalah bagian dari
            // grup item pembayaran tagihan yang dihapus bersamaan, pulihkan
            // seluruh anggota grupnya sekaligus supaya tidak ada yang tertinggal
            // di keranjang sampah.
            $grup = collect([$transaksi]);
            if ($transaksi->jenis === 'Masuk' && $transaksi->pembayaran_tagihan_id && $transaksi->siswa_id) {
                $grup = Transaksi::onlyTrashed()
                    ->where('jenis', 'Masuk')
                    ->where('siswa_id', $transaksi->siswa_id)
                    ->where('tanggal', $transaksi->tanggal)
                    ->whereNotNull('pembayaran_tagihan_id')
                    ->get();
            }

            foreach ($grup as $item) {
                $item->restore();
            }

            // Bersihkan juga baris log terkait di "Log Penghapusan Semua Menu"
            // supaya tidak ada catatan yang masih menyiratkan data ini terhapus.
            DeletionHistory::where('entity_type', 'Transaksi')
                ->whereIn('entity_id', $grup->pluck('id'))
                ->delete();
        });

        return back()->with('success', 'Data transaksi berhasil dipulihkan.');
    }

    // Pulihkan data dari baris "Log Penghapusan Semua Menu" (bisa dari menu
    // manapun: Data Siswa, Tagihan, Item Pembayaran, atau Transaksi), berdasarkan
    // entity_type & entity_id yang tersimpan di DeletionHistory saat data dihapus.
    public function restoreDeletionHistory($historyId)
    {
        $history = DeletionHistory::findOrFail($historyId);

        $modelMap = [
            'Siswa' => \App\Models\Siswa::class,
            'Tagihan' => \App\Models\Tagihan::class,
            'ItemPembayaran' => \App\Models\ItemPembayaran::class,
            'Transaksi' => \App\Models\Transaksi::class,
        ];

        $modelClass = $modelMap[$history->entity_type] ?? null;

        if (!$modelClass || !$history->entity_id) {
            return back()->with('error', 'Tipe data ini tidak dapat dipulihkan otomatis.');
        }

        $record = $modelClass::onlyTrashed()->find($history->entity_id);

        if (!$record) {
            // Data sumbernya sudah tidak ada lagi di keranjang sampah (mis. sudah
            // dipulihkan lewat baris grup lain, atau sudah dihapus permanen).
            // Baris log ini jadi basi dan tidak akan pernah bisa dipulihkan lagi,
            // jadi langsung bersihkan saja supaya tidak nyangkut selamanya.
            $history->delete();

            return back()->with('success', 'Data sudah dipulihkan sebelumnya, log penghapusan dibersihkan.');
        }

        DB::transaction(function () use ($record, $history) {
            // Samakan dengan restore() Transaksi: kalau ini bagian dari grup
            // pembayaran tagihan yang dihapus bersamaan, pulihkan sekelompoknya.
            if (
                $history->entity_type === 'Transaksi'
                && $record->jenis === 'Masuk'
                && $record->pembayaran_tagihan_id
                && $record->siswa_id
            ) {
                $grup = \App\Models\Transaksi::onlyTrashed()
                    ->where('jenis', 'Masuk')
                    ->where('siswa_id', $record->siswa_id)
                    ->where('tanggal', $record->tanggal)
                    ->whereNotNull('pembayaran_tagihan_id')
                    ->get();

                $grup->each->restore();

                // Setiap item dalam grup dicatat sebagai baris log terpisah saat
                // dihapus — hapus semuanya sekaligus supaya tidak ada log yang
                // tertinggal seolah-olah datanya masih terhapus.
                DeletionHistory::where('entity_type', 'Transaksi')
                    ->whereIn('entity_id', $grup->pluck('id'))
                    ->delete();

                return;
            }

            $record->restore();
            $history->delete();
        });

        return back()->with('success', 'Data berhasil dipulihkan.');
    }

    // Pulihkan SEMUA baris "Daftar Backup Transaksi" sekaligus (semua halaman),
    // supaya admin tidak perlu memulihkan satu per satu atau berpindah halaman.
    public function restoreAll()
    {
        $restored = 0;

        DB::transaction(function () use (&$restored) {
            $trashedIds = Transaksi::onlyTrashed()->pluck('id');

            foreach ($trashedIds as $id) {
                $transaksi = Transaksi::onlyTrashed()->find($id);

                // Mungkin sudah ikut terpulihkan di iterasi sebelumnya sebagai
                // bagian dari grup pembayaran tagihan yang sama.
                if (!$transaksi) {
                    continue;
                }

                $grup = collect([$transaksi]);
                if ($transaksi->jenis === 'Masuk' && $transaksi->pembayaran_tagihan_id && $transaksi->siswa_id) {
                    $grup = Transaksi::onlyTrashed()
                        ->where('jenis', 'Masuk')
                        ->where('siswa_id', $transaksi->siswa_id)
                        ->where('tanggal', $transaksi->tanggal)
                        ->whereNotNull('pembayaran_tagihan_id')
                        ->get();
                }

                foreach ($grup as $item) {
                    $item->restore();
                }

                DeletionHistory::where('entity_type', 'Transaksi')
                    ->whereIn('entity_id', $grup->pluck('id'))
                    ->delete();

                $restored++;
            }
        });

        if ($restored === 0) {
            return back()->with('error', 'Tidak ada data yang dipulihkan. Data mungkin sudah dipulihkan sebelumnya.');
        }

        return back()->with('success', $restored . ' data transaksi berhasil dipulihkan semua.');
    }

    // Pulihkan SELURUH baris "Log Penghapusan Semua Menu" sekaligus, tanpa perlu
    // mencentang satu per satu atau berpindah halaman.
    public function restoreDeletionHistoryAll()
    {
        $modelMap = [
            'Siswa' => \App\Models\Siswa::class,
            'Tagihan' => \App\Models\Tagihan::class,
            'ItemPembayaran' => \App\Models\ItemPembayaran::class,
            'Transaksi' => \App\Models\Transaksi::class,
        ];

        $restored = 0;

        DB::transaction(function () use ($modelMap, &$restored) {
            // Ambil semua log yang bertipe data valid (bisa dipulihkan otomatis).
            $histories = DeletionHistory::whereIn('entity_type', array_keys($modelMap))->get();

            foreach ($histories as $history) {
                // Baris ini mungkin sudah terhapus di iterasi sebelumnya (mis. ikut
                // terhapus sebagai bagian dari grup Transaksi), jadi cek ulang.
                if (!DeletionHistory::whereKey($history->id)->exists()) {
                    continue;
                }

                $modelClass = $modelMap[$history->entity_type] ?? null;

                if (!$modelClass || !$history->entity_id) {
                    continue;
                }

                $record = $modelClass::onlyTrashed()->find($history->entity_id);

                if (!$record) {
                    // Data sumbernya sudah tidak ada lagi di keranjang sampah,
                    // log ini basi dan dibersihkan otomatis.
                    $history->delete();
                    $restored++;
                    continue;
                }

                if (
                    $history->entity_type === 'Transaksi'
                    && $record->jenis === 'Masuk'
                    && $record->pembayaran_tagihan_id
                    && $record->siswa_id
                ) {
                    $grup = \App\Models\Transaksi::onlyTrashed()
                        ->where('jenis', 'Masuk')
                        ->where('siswa_id', $record->siswa_id)
                        ->where('tanggal', $record->tanggal)
                        ->whereNotNull('pembayaran_tagihan_id')
                        ->get();

                    $grup->each->restore();

                    DeletionHistory::where('entity_type', 'Transaksi')
                        ->whereIn('entity_id', $grup->pluck('id'))
                        ->delete();
                } else {
                    $record->restore();
                    $history->delete();
                }

                $restored++;
            }
        });

        if ($restored === 0) {
            return back()->with('error', 'Tidak ada data yang dapat dipulihkan.');
        }

        return back()->with('success', $restored . ' data berhasil dipulihkan semua.');
    }

    public function purgeRiwayat(Request $request)
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:transaksi,log,all'],
        ]);

        $scope = $validated['scope'];

        DB::transaction(function () use ($scope) {
            if (in_array($scope, ['transaksi', 'all'], true)) {
                Transaksi::onlyTrashed()->forceDelete();
            }

            if (in_array($scope, ['log', 'all'], true)) {
                DeletionHistory::query()->delete();
            }
        });

        return back()->with('success', 'Riwayat hapus berhasil dibersihkan.');
    }

    private function resolvePeriode(Request $request): array
    {
        $validated = $request->validate([
            'periode' => ['nullable', Rule::in(['harian', 'bulanan', 'tahunan', 'custom'])],
            'tanggal' => ['nullable', 'date'],
            'bulan' => ['nullable', 'date_format:Y-m'],
            'tahun' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        $jenisPeriode = $validated['periode'] ?? 'bulanan';
        $today = now();

        if ($jenisPeriode === 'harian') {
            $tanggal = isset($validated['tanggal']) ? Carbon::parse($validated['tanggal']) : $today;

            return [
                'jenis' => 'harian',
                'start' => $tanggal->copy()->startOfDay(),
                'end' => $tanggal->copy()->endOfDay(),
                'label' => 'Harian: ' . $tanggal->translatedFormat('d F Y'),
            ];
        }

        if ($jenisPeriode === 'tahunan') {
            $tahun = isset($validated['tahun']) ? (int) $validated['tahun'] : (int) $today->format('Y');
            $start = Carbon::create($tahun, 1, 1)->startOfDay();
            $end = Carbon::create($tahun, 12, 31)->endOfDay();

            return [
                'jenis' => 'tahunan',
                'start' => $start,
                'end' => $end,
                'label' => 'Tahunan: ' . $tahun,
            ];
        }

        if ($jenisPeriode === 'custom' && isset($validated['tanggal_mulai'], $validated['tanggal_selesai'])) {
            $start = Carbon::parse($validated['tanggal_mulai'])->startOfDay();
            $end = Carbon::parse($validated['tanggal_selesai'])->endOfDay();

            return [
                'jenis' => 'custom',
                'start' => $start,
                'end' => $end,
                'label' => 'Periode: ' . $start->translatedFormat('d M Y') . ' - ' . $end->translatedFormat('d M Y'),
            ];
        }

        $bulan = isset($validated['bulan']) ? Carbon::createFromFormat('Y-m', $validated['bulan']) : $today->copy();

        return [
            'jenis' => 'bulanan',
            'start' => $bulan->copy()->startOfMonth(),
            'end' => $bulan->copy()->endOfMonth(),
            'label' => 'Bulanan: ' . $bulan->translatedFormat('F Y'),
        ];
    }

    private function hitungRingkasanKas(array $periode): array
    {
        $totalPemasukan = Transaksi::query()
            ->where('jenis', 'Masuk')
            ->whereBetween('tanggal', [$periode['start'], $periode['end']])
            ->sum('total_bayar');

        $totalPengeluaran = Transaksi::query()
            ->where('jenis', 'Keluar')
            ->whereBetween('tanggal', [$periode['start'], $periode['end']])
            ->sum('total_bayar');

        $saldoAwalMasuk = Transaksi::query()
            ->where('jenis', 'Masuk')
            ->whereDate('tanggal', '<', $periode['start'])
            ->sum('total_bayar');

        $saldoAwalKeluar = Transaksi::query()
            ->where('jenis', 'Keluar')
            ->whereDate('tanggal', '<', $periode['start'])
            ->sum('total_bayar');

        $saldoAwalKas = $saldoAwalMasuk - $saldoAwalKeluar;
        $saldoAkhirKas = $saldoAwalKas + $totalPemasukan - $totalPengeluaran;

        // ===== Data transaksi penuh (untuk rincian per transaksi di export) =====
        $masuk = Transaksi::where('jenis', 'Masuk')
            ->whereBetween('tanggal', [$periode['start'], $periode['end']])
            ->get();
        $keluar = Transaksi::where('jenis', 'Keluar')
            ->whereBetween('tanggal', [$periode['start'], $periode['end']])
            ->get();

        // ===== Rincian per kategori (agar laporan lebih mudah dipahami & detail) =====
        $rincianPemasukan = Transaksi::query()
            ->where('jenis', 'Masuk')
            ->whereBetween('tanggal', [$periode['start'], $periode['end']])
            ->selectRaw('kategori, COUNT(*) as jumlah_transaksi, SUM(total_bayar) as total')
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($totalPemasukan) {
                return [
                    'kategori' => $row->kategori ?: '-',
                    'jumlah_transaksi' => (int) $row->jumlah_transaksi,
                    'total' => (float) $row->total,
                    'persentase' => $totalPemasukan > 0 ? ((float) $row->total / (float) $totalPemasukan) * 100 : 0,
                ];
            })
            ->values()
            ->all();

        $rincianPengeluaran = Transaksi::query()
            ->where('jenis', 'Keluar')
            ->whereBetween('tanggal', [$periode['start'], $periode['end']])
            ->selectRaw('kategori, COUNT(*) as jumlah_transaksi, SUM(total_bayar) as total')
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) use ($totalPengeluaran) {
                return [
                    'kategori' => $row->kategori ?: '-',
                    'jumlah_transaksi' => (int) $row->jumlah_transaksi,
                    'total' => (float) $row->total,
                    'persentase' => $totalPengeluaran > 0 ? ((float) $row->total / (float) $totalPengeluaran) * 100 : 0,
                ];
            })
            ->values()
            ->all();

        // ===== Tren arus kas harian dalam periode (untuk grafik garis) =====
        $trenHarian = Transaksi::query()
            ->whereBetween('tanggal', [$periode['start'], $periode['end']])
            ->selectRaw("tanggal, jenis, SUM(total_bayar) as total")
            ->groupBy('tanggal', 'jenis')
            ->orderBy('tanggal')
            ->get();

        $tanggalMap = [];
        foreach ($trenHarian as $row) {
            $tgl = $row->tanggal instanceof \Illuminate\Support\Carbon
                ? $row->tanggal->format('Y-m-d')
                : Carbon::parse($row->tanggal)->format('Y-m-d');

            if (!isset($tanggalMap[$tgl])) {
                $tanggalMap[$tgl] = ['masuk' => 0.0, 'keluar' => 0.0];
            }

            if ($row->jenis === 'Masuk') {
                $tanggalMap[$tgl]['masuk'] += (float) $row->total;
            } else {
                $tanggalMap[$tgl]['keluar'] += (float) $row->total;
            }
        }
        ksort($tanggalMap);

        $saldoBerjalan = $saldoAwalKas;
        $trenLabel = [];
        $trenMasuk = [];
        $trenKeluar = [];
        $trenSaldo = [];
        foreach ($tanggalMap as $tgl => $nilai) {
            $saldoBerjalan += $nilai['masuk'] - $nilai['keluar'];
            $trenLabel[] = Carbon::parse($tgl)->translatedFormat('d M');
            $trenMasuk[] = $nilai['masuk'];
            $trenKeluar[] = $nilai['keluar'];
            $trenSaldo[] = $saldoBerjalan;
        }

        return [
            'saldoAwalKas' => $saldoAwalKas,
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'saldoAkhirKas' => $saldoAkhirKas,
            'rincianPemasukan' => $rincianPemasukan,
            'rincianPengeluaran' => $rincianPengeluaran,
            'detailPemasukan' => $this->buildDetailMasuk($masuk, 'kategori'),
            'detailPengeluaran' => $this->buildDetailKeluar($keluar),
            'tren' => [
                'label' => $trenLabel,
                'masuk' => $trenMasuk,
                'keluar' => $trenKeluar,
                'saldo' => $trenSaldo,
            ],
        ];
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
            'X' => '10',
            'XI' => '11',
            'XII' => '12',
            'XIII' => '13',
            default => $prefix,
        };

        return $angka . ($suffix !== '' ? ' ' . ltrim($suffix, '-/ ') : '');
    }

    private function kelasLegacyNumerik(?string $kelas): ?string
    {
        if ($kelas === null || strcasecmp($kelas, 'Lulus') === 0) {
            return $kelas;
        }

        if (!preg_match('/^(13|12|11|10)(.*)$/i', $kelas, $match)) {
            return $kelas;
        }

        $prefix = $match[1];
        $suffix = trim((string) ($match[2] ?? ''));
        $roman = match ($prefix) {
            '10' => 'X',
            '11' => 'XI',
            '12' => 'XII',
            '13' => 'XIII',
            default => $prefix,
        };

        return $roman . ($suffix !== '' ? ' ' . ltrim($suffix, '-/ ') : '');
    }

    private function resolveSiswaByNama(?string $namaSiswa, ?string $kelas): ?Siswa
    {
        $namaSiswa = trim((string) $namaSiswa);
        if ($namaSiswa === '') {
            return null;
        }

        $query = Siswa::query()->where('nama', $namaSiswa);

        if ($kelas !== null && $kelas !== '') {
            $kelasLegacy = $this->kelasLegacyNumerik($kelas);
            $query->whereIn('kelas', array_values(array_unique([$kelas, $kelasLegacy])));
        }

        return $query->orderBy('id')->first();
    }

    private function resolveItemPembayaranIdByNama(?string $namaItem): ?int
    {
        $namaItem = trim((string) $namaItem);
        if ($namaItem === '') {
            return null;
        }

        return ItemPembayaran::query()
            ->where('nama_item', $namaItem)
            ->value('id');
    }

    private function resolveJenjangKelas(?string $kelas): ?string
    {
        $kelas = strtoupper(trim((string) $kelas));
        if ($kelas === '') {
            return null;
        }

        if (preg_match('/^(12|XII)\b/', $kelas) === 1) {
            return '12';
        }

        if (preg_match('/^(11|XI)\b/', $kelas) === 1) {
            return '11';
        }

        if (preg_match('/^(10|X)\b/', $kelas) === 1) {
            return '10';
        }

        return null;
    }

    private function applyJenjangFilterToKelasQuery($kelasQuery, string $jenjang): void
    {
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
    }
}
