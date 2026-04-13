@extends('layouts.app')

@section('content')
<style>
    .report-head {
        background: #fff;
        border: 1px solid #e1e9f5;
        border-radius: 12px;
        padding: 16px;
    }

    .report-title {
        font-size: 18px;
        font-weight: 800;
        color: #1e3655;
        margin-bottom: 2px;
    }

    .report-meta {
        font-size: 12px;
        color: #6b7f9d;
    }

    .wali-table th {
        font-size: 12px;
        color: #6f83a3;
        text-transform: uppercase;
        letter-spacing: .03em;
        white-space: nowrap;
    }

    .wali-table td {
        font-size: 13px;
        vertical-align: middle;
        color: #334a68;
    }

    .wali-doc-meta {
        font-size: 14px;
        color: #2f4564;
        line-height: 1.7;
        margin-bottom: 14px;
    }

    .wali-doc-meta strong {
        display: inline-block;
        min-width: 170px;
        color: #1f3657;
    }

    .report-sign {
        margin-top: 32px;
        width: 280px;
        margin-left: auto;
        text-align: center;
        color: #445d7d;
        font-size: 13px;
    }

    .report-sign .sign-line {
        margin-top: 56px;
        border-top: 1px solid #9fb0c8;
        padding-top: 6px;
    }

    .print-only {
        display: none;
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 10mm;
        }

        .no-print {
            display: none !important;
        }

        .print-only {
            display: block !important;
        }

        .report-head {
            border: none;
            border-bottom: 2px solid #1f3657;
            border-radius: 0;
            padding: 0 0 10px 0;
            margin-bottom: 14px;
        }

        .table-responsive {
            overflow: visible !important;
        }

        .wali-table {
            width: 100% !important;
            table-layout: fixed;
        }

        .wali-table th,
        .wali-table td {
            white-space: normal !important;
            word-break: break-word;
            font-size: 11px;
            padding: 4px 6px;
        }

        .official-meta td {
            font-size: 12px;
            padding: 2px 4px;
            vertical-align: top;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="fw-bold text-dark mb-1">Laporan Pembayaran Wali Murid</h4>
                <small class="text-muted">Data diambil dari transaksi pembayaran tagihan siswa dan dipisah per kelas tagihan (10, 11, 12).</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('laporan.wali.export', request()->query()) }}" class="btn btn-success">
            <i class="fas fa-file-excel me-2"></i>Export Excel
        </a>
        @if($data->count() > 0)
            <button onclick="printLaporanWali()" class="btn btn-primary">
                <i class="fas fa-file-pdf me-2"></i>Cetak PDF
            </button>
        @endif
    </div>
</div>

<div class="report-head mb-3 print-only">
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="width:90px;vertical-align:top;text-align:left;">
                <img src="{{ asset('image/logo_pondok.jpeg') }}" alt="Logo" style="width:70px;height:auto;">
            </td>
            <td style="text-align:center;">
                <div class="report-title">SMA UNGGULAN BPPT DARUS SHOLAH</div>
                <div class="report-meta">SMA UNGGULAN BPPT DARUS SHOLAH | Laporan Pembayaran Wali Murid</div>
                <div class="report-meta">Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
            </td>
        </tr>
    </table>
</div>

<div class="card border-0 shadow-sm p-3 mb-4 no-print">
    <form method="GET" action="{{ route('laporan.wali') }}" class="row g-3">
        <div class="col-md-4">
            <label class="form-label">Nama Siswa</label>
            <input type="text" name="nama_siswa" class="form-control" placeholder="Contoh: Ahmad" value="{{ $request->nama_siswa }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Kelas</label>
            <select name="kelas" class="form-select">
                <option value="">Semua</option>
                <option value="10" {{ (string) $request->kelas === '10' ? 'selected' : '' }}>10</option>
                <option value="11" {{ (string) $request->kelas === '11' ? 'selected' : '' }}>11</option>
                <option value="12" {{ (string) $request->kelas === '12' ? 'selected' : '' }}>12</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label">Dari Tanggal</label>
            <input type="date" name="tanggal_mulai" class="form-control" value="{{ $request->tanggal_mulai }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Sampai Tanggal</label>
            <input type="date" name="tanggal_selesai" class="form-control" value="{{ $request->tanggal_selesai }}">
        </div>
        <div class="col-md-2">
            <label class="form-label">Per Halaman</label>
            <select name="per_page" class="form-select">
                @foreach([10, 25, 50, 100] as $size)
                    <option value="{{ $size }}" {{ (int) ($request->per_page ?? 25) === $size ? 'selected' : '' }}>{{ $size }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" name="cari" value="1" class="btn btn-primary w-100">Cari</button>
            <a href="{{ route('laporan.wali') }}" class="btn btn-outline-secondary w-100">Reset</a>
        </div>
    </form>
</div>

@php
    $isSearchRequested = $request->has('cari') || $request->hasAny(['nama_siswa', 'kelas', 'tanggal_mulai', 'tanggal_selesai']);
@endphp

@if($isSearchRequested && $data->count() > 0)
    @php
        $totalPembayaranNominal = isset($totalPembayaran) ? (float) $totalPembayaran : (float) $data->sum('nominal_bayar');
        $totalKelas10 = 0;
        $totalKelas11 = 0;
        $totalKelas12 = 0;
        $totalDokumen = method_exists($data, 'total') ? $data->total() : $data->count();
        $periodeLabel = ($request->tanggal_mulai && $request->tanggal_selesai)
            ? date('d-m-Y', strtotime($request->tanggal_mulai)) . ' s/d ' . date('d-m-Y', strtotime($request->tanggal_selesai))
            : 'Semua Periode';
        $kelasLabel = $request->kelas ? 'Kelas ' . $request->kelas : 'Semua Kelas';
        $dokumenLabel = 'LW-' . now()->format('Ymd') . '-' . str_pad((string) $totalDokumen, 3, '0', STR_PAD_LEFT);
    @endphp

    <div class="wali-doc-meta print-only">
        <table class="official-meta" style="width:100%;border-collapse:collapse;margin-bottom:10px;">
            <tr><td style="width:90px;">Nomor</td><td style="width:12px;">:</td><td>DS/WALI/{{ now()->format('Y/m') }}/{{ str_pad((string) $totalDokumen, 3, '0', STR_PAD_LEFT) }}</td><td style="text-align:right;">{{ now()->translatedFormat('d F Y') }}</td></tr>
            <tr><td>Sifat</td><td>:</td><td colspan="2">Penting</td></tr>
            <tr><td>Lampiran</td><td>:</td><td colspan="2">1 berkas</td></tr>
            <tr><td>Hal</td><td>:</td><td colspan="2">Laporan Pembayaran Wali Murid</td></tr>
        </table>
        <div style="margin-bottom:8px;">Yth. Ketua SMA UNGGULAN BPPT DARUS SHOLAH<br>di Tempat</div>
        <div style="text-align:justify;margin-bottom:8px;">Dengan hormat, berikut kami sampaikan laporan pembayaran wali murid untuk {{ $periodeLabel }} pada {{ $kelasLabel }} sebagai bahan evaluasi realisasi pembayaran.</div>
        <div><strong>Nama Instansi</strong>: SMA UNGGULAN BPPT DARUS SHOLAH</div>
        <div><strong>Jenis Dokumen</strong>: Laporan Pembayaran Wali Murid</div>
        <div><strong>Nomor Dokumen</strong>: {{ $dokumenLabel }}</div>
        <div><strong>Periode Data</strong>: {{ $periodeLabel }}</div>
        <div><strong>Ruang Kelas</strong>: {{ $kelasLabel }}</div>
    </div>

    @php
        $kelasRows = [
            '10' => collect(),
            '11' => collect(),
            '12' => collect(),
        ];

        foreach ($data as $pembayaran) {
            $tagihan = $pembayaran->tagihan;
            $kelasTagihanRaw = trim((string) ($tagihan?->kelas ?? ''));
            $kelasTagihanNorm = preg_replace('/\s+/', ' ', $kelasTagihanRaw) ?: '';
            $kelasAngka = null;

            if (preg_match('/^(XII|XI|X|12|11|10)\b/i', $kelasTagihanNorm, $kelasMatch)) {
                $prefix = strtoupper($kelasMatch[1]);
                $kelasAngka = match ($prefix) {
                    'X' => '10',
                    'XI' => '11',
                    'XII' => '12',
                    default => $prefix,
                };
            }

            if (isset($kelasRows[$kelasAngka])) {
                $kelasRows[$kelasAngka]->push($pembayaran);
            }
        }

        $totalKelas10 = (float) $kelasRows['10']->sum('nominal_bayar');
        $totalKelas11 = (float) $kelasRows['11']->sum('nominal_bayar');
        $totalKelas12 = (float) $kelasRows['12']->sum('nominal_bayar');
    @endphp

    @php
        $kelasDipilih = null;
        if ($request->kelas !== null && $request->kelas !== '') {
            $kelasRaw = strtoupper(trim((string) $request->kelas));
            $kelasDipilih = match ($kelasRaw) {
                'X' => '10',
                'XI' => '11',
                'XII' => '12',
                default => in_array($kelasRaw, ['10', '11', '12'], true) ? $kelasRaw : null,
            };
        }

        $kelasTablesToShow = $kelasDipilih !== null ? [$kelasDipilih] : ['10', '11', '12'];
    @endphp

    @foreach($kelasTablesToShow as $kelasTable)
        @php
            $rowsTable = $kelasRows[$kelasTable];
            $totalTable = (float) $rowsTable->sum('nominal_bayar');
        @endphp
        <div class="card border-0 shadow-sm overflow-hidden mb-3">
            <div class="card-header bg-light fw-bold text-dark">
                List Pembayaran Kelas Tagihan {{ $kelasTable }}
            </div>
            <div class="table-responsive">
                <table class="table wali-table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas Tagihan</th>
                            <th>Item Tagihan</th>
                            <th>Periode</th>
                            <th>Metode</th>
                            <th class="text-end">Nominal Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($rowsTable as $pembayaran)
                            @php
                                $tagihan = $pembayaran->tagihan;
                                $siswa = $tagihan?->siswa;
                                $item = $tagihan?->itemPembayaran;
                                $kelasTagihanRaw = trim((string) ($tagihan?->kelas ?? ''));
                                $kelasTagihanNorm = preg_replace('/\s+/', ' ', $kelasTagihanRaw) ?: '-';
                            @endphp
                            <tr>
                                <td>{{ optional($pembayaran->tanggal_bayar)->format('d-m-Y') }}</td>
                                <td>{{ $siswa?->nis ?? '-' }}</td>
                                <td>{{ $siswa?->nama ?? '-' }}</td>
                                <td>{{ $kelasTagihanNorm }}</td>
                                <td>{{ $item?->nama_item ?? '-' }}</td>
                                <td>{{ $tagihan?->periode_label ?? '-' }}</td>
                                <td>{{ strtoupper((string) ($pembayaran->metode_bayar ?? '-')) }}</td>
                                <td class="text-end fw-bold">Rp. {{ number_format((float) $pembayaran->nominal_bayar, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Tidak ada pembayaran untuk kelas tagihan {{ $kelasTable }}.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="7" class="text-end fw-bold">TOTAL KELAS {{ $kelasTable }}</td>
                            <td class="text-end fw-bold text-primary">Rp. {{ number_format($totalTable, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endforeach

    <div class="card border-0 shadow-sm overflow-hidden mb-3">
        <div class="table-responsive">
            <table class="table wali-table mb-0 align-middle">
                <tfoot>
                    <tr>
                        <td class="text-end fw-bold">TOTAL KELAS 10</td>
                        <td class="text-end fw-bold text-primary">Rp. {{ number_format($totalKelas10, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">TOTAL KELAS 11</td>
                        <td class="text-end fw-bold text-primary">Rp. {{ number_format($totalKelas11, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">TOTAL KELAS 12</td>
                        <td class="text-end fw-bold text-primary">Rp. {{ number_format($totalKelas12, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">GRAND TOTAL</td>
                        <td class="text-end fw-bold text-success">Rp. {{ number_format($totalPembayaranNominal, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if(method_exists($data, 'links'))
        <div class="mt-3 no-print">
            {{ $data->links() }}
        </div>
    @endif

    <div class="report-sign print-only">
        <div>{{ now()->translatedFormat('d F Y') }}</div>
        <div>Mengetahui,</div>
        <div class="sign-line">Bendahara</div>
    </div>
@elseif($isSearchRequested)
    <div class="alert alert-warning">
        Data pembayaran tidak ditemukan pada filter yang dipilih.
    </div>
@else
    <div class="text-center py-5 text-muted">
        Masukkan filter untuk menampilkan laporan pembayaran wali murid.
    </div>
@endif

<script>
    function printLaporanWali() {
        const originalTitle = document.title;
        const tanggal = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        document.title = 'Wali_Murid_Export_PDF_' + tanggal;
        window.print();
        setTimeout(() => {
            document.title = originalTitle;
        }, 400);
    }
</script>
@endsection
