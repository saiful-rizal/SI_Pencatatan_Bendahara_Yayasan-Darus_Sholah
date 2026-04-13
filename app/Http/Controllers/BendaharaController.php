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

        $transaksis = Transaksi::latest()->paginate(10);

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

        // Rekap harian: ambil 1 transaksi terbaru per nama_siswa untuk hari ini.
        // Jika nama_siswa null, dikelompokkan sebagai transaksi umum.
        $recentTransactionIds = Transaksi::query()
            ->whereDate('tanggal', today())
            ->selectRaw('MAX(id) as id')
            ->groupBy(DB::raw("COALESCE(nama_siswa, '__UMUM__')"))
            ->pluck('id');

        $recentTransactions = Transaksi::query()
            ->whereIn('id', $recentTransactionIds)
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
                $totalBayar += ($harga * $validated['jumlah'][$key]);
            }

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
                    DetailTransaksi::create([
                        'transaksi_id' => $transaksi->id,
                        'item_pembayaran_id' => $this->resolveItemPembayaranIdByNama($nama),
                        'nama_item' => $nama,
                        'harga' => $validated['harga'][$key],
                        'jumlah' => $validated['jumlah'][$key],
                        'subtotal' => $validated['harga'][$key] * $validated['jumlah'][$key],
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
                $totalBayar += ($harga * $validated['jumlah'][$key]);
            }

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
                    DetailTransaksi::create([
                        'transaksi_id' => $transaksi->id,
                        'item_pembayaran_id' => $this->resolveItemPembayaranIdByNama($nama),
                        'nama_item' => $nama,
                        'harga' => $validated['harga'][$key],
                        'jumlah' => $validated['jumlah'][$key],
                        'subtotal' => $validated['harga'][$key] * $validated['jumlah'][$key],
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
        $isSearchRequested = $request->has('cari')
            || $request->filled('nama_siswa')
            || $request->filled('kelas')
            || $request->filled('tanggal_mulai')
            || $request->filled('tanggal_selesai');

        if (!$isSearchRequested) {
            $data = PembayaranTagihan::query()
                ->whereRaw('1 = 0')
                ->paginate($perPage)
                ->appends($request->query());

            $totalPembayaran = 0;

            return view('laporan.wali', compact('data', 'request', 'totalPembayaran'));
        }

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
        $periode = $this->resolvePeriode($request);

        $data = Transaksi::query()
            ->with([
                'details:id,transaksi_id,nama_item',
                'pembayaranTagihan:id,tagihan_id',
                'pembayaranTagihan.tagihan:id,item_pembayaran_id,periode_bulan,periode_tahun',
                'pembayaranTagihan.tagihan.itemPembayaran:id,nama_item',
            ])
            ->where('jenis', 'Masuk')
            ->whereBetween('tanggal', [$periode['start']->toDateString(), $periode['end']->toDateString()])
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
            ->get();

        $totalPemasukan = $data->sum('total_bayar');

        return view('laporan.pemasukan-dana', [
            'data' => $data,
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
                'pembayaranTagihan:id,tagihan_id',
                'pembayaranTagihan.tagihan:id,item_pembayaran_id,periode_bulan,periode_tahun',
                'pembayaranTagihan.tagihan.itemPembayaran:id,nama_item',
            ])
            ->where('jenis', 'Masuk')
            ->whereBetween('tanggal', [$periode['start']->toDateString(), $periode['end']->toDateString()])
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
            ->get();

        $fileName = 'Laporan_Pemasukan_Dana_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new PemasukanDanaExport($data, $periode['label']), $fileName);
    }

    // 2B. Laporan Pengeluaran Dana
    public function laporanPengeluaranDana(Request $request)
    {
        $periode = $this->resolvePeriode($request);

        $data = Transaksi::query()
            ->where('jenis', 'Keluar')
            ->whereBetween('tanggal', [$periode['start']->toDateString(), $periode['end']->toDateString()])
            ->orderBy('tanggal', 'desc')
            ->get();

        $totalPengeluaran = $data->sum('total_bayar');

        return view('laporan.pengeluaran-dana', [
            'data' => $data,
            'totalPengeluaran' => $totalPengeluaran,
            'periode' => $periode,
            'request' => $request,
        ]);
    }

    public function exportPengeluaranDana(Request $request)
    {
        $periode = $this->resolvePeriode($request);

        $data = Transaksi::query()
            ->where('jenis', 'Keluar')
            ->whereBetween('tanggal', [$periode['start']->toDateString(), $periode['end']->toDateString()])
            ->orderBy('tanggal', 'desc')
            ->get();

        $fileName = 'Laporan_Pengeluaran_Dana_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new PengeluaranDanaExport($data, $periode['label']), $fileName);
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
        ]);

        $queryMasuk = Transaksi::where('jenis', 'Masuk');
        $queryKeluar = Transaksi::where('jenis', 'Keluar');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $queryMasuk->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
            $queryKeluar->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        $masuk = $queryMasuk->get();
        $keluar = $queryKeluar->get();

        $reportMasuk = $masuk->groupBy('kategori')->map(function ($item) {
            return $item->sum('total_bayar');
        });
        $reportKeluar = $keluar->groupBy('kategori')->map(function ($item) {
            return $item->sum('total_bayar');
        });

        $totalMasuk = $masuk->sum('total_bayar');
        $totalKeluar = $keluar->sum('total_bayar');
        $saldo = $totalMasuk - $totalKeluar;

        return view('laporan.yayasan', compact('reportMasuk', 'reportKeluar', 'totalMasuk', 'totalKeluar', 'saldo', 'request'));
    }

    // Export Excel Yayasan
    public function exportYayasan(Request $request)
    {
        $request->validate([
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal_mulai'],
        ]);

        // 1. Lakukan logika perhitungan yang sama dengan laporanYayasan
        $queryMasuk = Transaksi::where('jenis', 'Masuk');
        $queryKeluar = Transaksi::where('jenis', 'Keluar');

        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $queryMasuk->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
            $queryKeluar->whereBetween('tanggal', [$request->tanggal_mulai, $request->tanggal_selesai]);
        }

        $masuk = $queryMasuk->get();
        $keluar = $queryKeluar->get();

        $reportMasuk = $masuk->groupBy('kategori')->map(function ($item) {
            return $item->sum('total_bayar');
        });
        $reportKeluar = $keluar->groupBy('kategori')->map(function ($item) {
            return $item->sum('total_bayar');
        });

        $totalMasuk = $masuk->sum('total_bayar');
        $totalKeluar = $keluar->sum('total_bayar');
        $saldo = $totalMasuk - $totalKeluar;

        // 2. Download Excel menggunakan YayasanExport
        $fileName = 'Laporan_Yayasan_' . now()->format('Y-m-d_His') . '.xlsx';
        return Excel::download(new YayasanExport($reportMasuk, $reportKeluar, $totalMasuk, $totalKeluar, $saldo), $fileName);
    }

    // 4. Cetak Nota
    public function cetakNota($id)
    {
        $transaksi = Transaksi::with('details')->findOrFail($id);
        return view('laporan.nota', compact('transaksi'));
    }

    public function destroy($id)
    {
        DB::transaction(function () use ($id) {
            $transaksi = Transaksi::findOrFail($id);

            DeletionHistory::create([
                'menu' => 'Dashboard',
                'entity_type' => 'Transaksi',
                'entity_id' => $transaksi->id,
                'label' => ($transaksi->kategori ?? 'Transaksi') . ' - ' . ($transaksi->nama_siswa ?? 'Umum'),
                'deleted_by' => auth()->id(),
                'deleted_at' => now(),
            ]);

            $transaksi->delete();
        });

        return back()->with('success', 'Data dipindahkan ke menu Riwayat dan dapat dipulihkan.');
    }

    public function riwayat()
    {
        $transaksis = Transaksi::onlyTrashed()->latest('deleted_at')->paginate(10, ['*'], 'transaksi_page');
        $deletionHistories = DeletionHistory::with('user')->latest('deleted_at')->paginate(10, ['*'], 'history_page');

        return view('keuangan.riwayat', compact('transaksis', 'deletionHistories'));
    }

    public function restore($id)
    {
        DB::transaction(function () use ($id) {
            $transaksi = Transaksi::onlyTrashed()->findOrFail($id);
            $transaksi->restore();
        });

        return back()->with('success', 'Data transaksi berhasil dipulihkan.');
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
            ->whereBetween('tanggal', [$periode['start']->toDateString(), $periode['end']->toDateString()])
            ->sum('total_bayar');

        $totalPengeluaran = Transaksi::query()
            ->where('jenis', 'Keluar')
            ->whereBetween('tanggal', [$periode['start']->toDateString(), $periode['end']->toDateString()])
            ->sum('total_bayar');

        $saldoAwalMasuk = Transaksi::query()
            ->where('jenis', 'Masuk')
            ->whereDate('tanggal', '<', $periode['start']->toDateString())
            ->sum('total_bayar');

        $saldoAwalKeluar = Transaksi::query()
            ->where('jenis', 'Keluar')
            ->whereDate('tanggal', '<', $periode['start']->toDateString())
            ->sum('total_bayar');

        $saldoAwalKas = $saldoAwalMasuk - $saldoAwalKeluar;
        $saldoAkhirKas = $saldoAwalKas + $totalPemasukan - $totalPengeluaran;

        return [
            'saldoAwalKas' => $saldoAwalKas,
            'totalPemasukan' => $totalPemasukan,
            'totalPengeluaran' => $totalPengeluaran,
            'saldoAkhirKas' => $saldoAkhirKas,
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
