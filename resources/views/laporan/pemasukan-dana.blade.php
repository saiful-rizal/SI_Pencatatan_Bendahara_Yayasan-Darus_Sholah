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

    .print-summary-grid {
        display: flex;
        gap: 10px;
        margin-bottom: 14px;
    }

    .print-summary-grid .box {
        flex: 1;
        border-radius: 6px;
        padding: 8px 10px;
    }

    .print-summary-grid .box .label {
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: .4px;
        font-weight: 700;
        display: block;
        margin-bottom: 3px;
    }

    .print-summary-grid .box .value {
        font-size: 13px;
        font-weight: 800;
    }

    .print-section-title {
        font-size: 10.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .4px;
        margin: 16px 0 6px;
        padding: 5px 8px;
        border-radius: 4px;
        page-break-after: avoid;
        break-after: avoid;
    }

    @media print {
        @page {
            size: A4 portrait;
            margin: 13mm 12mm;
        }

        body {
            background: #ffffff !important;
            color: #1c2b3f !important;
            font-family: "Segoe UI", Arial, Helvetica, sans-serif !important;
            font-size: 12px !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .no-print {
            display: none !important;
        }

        .print-only {
            display: block !important;
        }

        .report-head {
            border: 1px solid #d7e2f3;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #f3f7ff 0%, #ffffff 55%);
            border-left: 4px solid #16a34a;
        }

        .report-title {
            font-size: 14px;
            color: #17325c;
            white-space: normal;
            letter-spacing: -0.2px;
        }

        .report-meta {
            color: #5b7091;
        }

        .official-meta td {
            font-size: 11px;
            padding: 2px 4px;
            vertical-align: top;
        }

        .print-summary-grid {
            page-break-inside: avoid;
        }

        .print-summary-grid .box:nth-child(1) {
            background: #eaf2ff !important;
            border-left: 3px solid #2563eb !important;
        }

        .print-summary-grid .box:nth-child(1) .label { color: #2563eb !important; }
        .print-summary-grid .box:nth-child(1) .value { color: #17325c !important; }

        .print-summary-grid .box:nth-child(2) {
            background: #e8f7ee !important;
            border-left: 3px solid #16a34a !important;
        }

        .print-summary-grid .box:nth-child(2) .label { color: #15803d !important; }
        .print-summary-grid .box:nth-child(2) .value { color: #15803d !important; }

        .print-summary-grid .box:nth-child(3) {
            background: #f4f6fb !important;
            border-left: 3px solid #5b7091 !important;
        }

        .print-summary-grid .box:nth-child(3) .label { color: #5b7091 !important; }
        .print-summary-grid .box:nth-child(3) .value { color: #17325c !important; }

        .print-section-title {
            background: #e8f7ee !important;
            color: #15803d !important;
            border-left: 3px solid #16a34a !important;
            text-align: left;
        }

        .table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10px !important;
            color: #1c2b3f !important;
            border: 1px solid #c7d3e6 !important;
        }

        .table thead {
            display: table-header-group !important;
        }

        .table thead th {
            border: none !important;
            border-bottom: 1.5px solid #16a34a !important;
            background: #e8f7ee !important;
            color: #15803d !important;
            padding: 6px 5px !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9px;
            letter-spacing: .3px;
            text-align: center !important;
        }

        .table td,
        .table th {
            border: none !important;
            border-bottom: 1px solid #e6ecf5 !important;
            padding: 5px 5px !important;
            background: transparent !important;
        }

        .table tbody tr:nth-child(even) td {
            background: #f9fafc !important;
        }

        .table tbody tr,
        .table tfoot tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .table tfoot th,
        .table tfoot td {
            border: none !important;
            border-top: 1.5px solid #16a34a !important;
            background: #e8f7ee !important;
            padding: 6px 5px !important;
        }

        .table-responsive {
            overflow: visible !important;
        }

        .report-sign {
            page-break-inside: avoid;
            page-break-before: avoid;
            margin-top: 24px;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Laporan Pemasukan Dana</h4>
        <small class="text-muted">Mencatat seluruh penerimaan dana yayasan berdasarkan periode.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('laporan.pemasukan.export', request()->query()) }}" class="btn btn-success">
            <i class="fas fa-file-excel me-1"></i> Export Excel
        </a>
        <button onclick="printLaporanPemasukan()" class="btn btn-primary">
            <i class="fas fa-file-pdf me-1"></i> Cetak PDF
        </button>
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
                <div class="report-meta">Laporan Pemasukan Dana</div>
                <div class="report-meta">Periode: {{ $periode['label'] }} &bull; Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
            </td>
        </tr>
    </table>
</div>

@php
    $totalTransaksi = method_exists($data, 'total') ? $data->total() : $data->count();
    $dokumenPemasukan = 'LPD-' . now()->format('Ymd') . '-' . str_pad((string) $totalTransaksi, 3, '0', STR_PAD_LEFT);
    $nomorSuratPemasukan = 'DS/KEU/' . now()->format('Y/m') . '/' . str_pad((string) $totalTransaksi, 3, '0', STR_PAD_LEFT);
    $jumlahTransaksiCetak = $dataCetak->count();
    $rataRataCetak = $jumlahTransaksiCetak > 0 ? $totalPemasukan / $jumlahTransaksiCetak : 0;
@endphp

<div class="print-only mb-3" style="font-size:12px;color:#2f4564;line-height:1.7;">
    <table class="official-meta" style="width:100%;border-collapse:collapse;margin-bottom:10px;">
        <tr><td style="width:90px;">Nomor</td><td style="width:12px;">:</td><td>{{ $nomorSuratPemasukan }}</td><td style="text-align:right;">{{ now()->translatedFormat('d F Y') }}</td></tr>
        <tr><td>Sifat</td><td>:</td><td colspan="2">Penting</td></tr>
        <tr><td>Lampiran</td><td>:</td><td colspan="2">1 berkas</td></tr>
        <tr><td>Hal</td><td>:</td><td colspan="2">Laporan Pemasukan Dana</td></tr>
    </table>
    <div style="margin-bottom:8px;">Yth. Ketua SMA UNGGULAN BPPT DARUS SHOLAH<br>di Tempat</div>
    <div style="text-align:justify;margin-bottom:10px;">Sehubungan dengan administrasi keuangan yayasan, berikut kami sampaikan laporan pemasukan dana untuk periode {{ $periode['label'] }} sebagai bahan evaluasi dan dokumentasi. Dokumen: {{ $dokumenPemasukan }}.</div>
</div>

<div class="print-summary-grid print-only">
    <div class="box">
        <span class="label">Jumlah Transaksi</span>
        <span class="value">{{ $jumlahTransaksiCetak }} transaksi</span>
    </div>
    <div class="box">
        <span class="label">Total Pemasukan</span>
        <span class="value">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</span>
    </div>
</div>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('laporan.pemasukan') }}" class="row g-3 align-items-end js-auto-filter" data-auto-submit>
            <div class="col-md-3">
                <label class="form-label">Jenis Periode</label>
                <select name="periode" class="form-select" id="periodePemasukan">
                    <option value="harian" {{ ($request->periode ?? 'bulanan') === 'harian' ? 'selected' : '' }}>Harian</option>
                    <option value="bulanan" {{ ($request->periode ?? 'bulanan') === 'bulanan' ? 'selected' : '' }}>Bulanan</option>
                    <option value="tahunan" {{ ($request->periode ?? 'bulanan') === 'tahunan' ? 'selected' : '' }}>Tahunan</option>
                    <option value="custom" {{ ($request->periode ?? 'bulanan') === 'custom' ? 'selected' : '' }}>Custom</option>
                </select>
            </div>

            <div class="col-md-3 filter-harian">
                <label class="form-label">Tanggal</label>
                <input type="date" name="tanggal" class="form-control" value="{{ $request->tanggal ?? now()->toDateString() }}">
            </div>

            <div class="col-md-3 filter-bulanan">
                <label class="form-label">Bulan</label>
                <input type="month" name="bulan" class="form-control" value="{{ $request->bulan ?? now()->format('Y-m') }}">
            </div>

            <div class="col-md-3 filter-tahunan">
                <label class="form-label">Tahun</label>
                <input type="number" name="tahun" min="2000" max="2100" class="form-control" value="{{ $request->tahun ?? now()->year }}">
            </div>

            <div class="col-md-3 filter-custom">
                <label class="form-label">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" class="form-control" value="{{ $request->tanggal_mulai }}">
            </div>
            <div class="col-md-3 filter-custom">
                <label class="form-label">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" class="form-control" value="{{ $request->tanggal_selesai }}">
            </div>

            <div class="col-md-4">
                <label class="form-label">Keterangan / Tujuan Dana</label>
                <input type="text" name="keterangan_tujuan" class="form-control" value="{{ $request->keterangan_tujuan }}" placeholder="Cari keterangan/tujuan dana">
            </div>

            <div class="col-md-1">
                <label class="form-label small">Per Hal</label>
                <select name="per_page" class="form-select">
                    @foreach([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', 10) === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1 filter-actions-inline">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
                <a href="{{ route('laporan.pemasukan') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4 no-print">
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted d-block">Periode Laporan</small>
                <h5 class="mb-0">{{ $periode['label'] }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted d-block">Total Pemasukan</small>
                <h4 class="text-success mb-0">Rp. {{ number_format($totalPemasukan, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm overflow-hidden no-print">
    <div class="card-header bg-white fw-semibold">Detail Penerimaan Dana</div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Sumber Dana</th>
                    <th>Nama Pemberi Dana</th>
                    <th>Keterangan / Tujuan Dana</th>
                    <th class="text-end">Jumlah Dana Diterima</th>
                </tr>
            </thead>
            <tbody>
                @forelse($data as $row)
                    <tr>
                        <td>{{ $row->tanggal->format('d-m-Y') }}</td>
                        <td>{{ $row->kategori }}</td>
                        <td>{{ $row->nama_siswa ?: '-' }}</td>
                        <td>{{ $row->catatan_tujuan_dana }}</td>
                        <td class="text-end fw-bold text-success">Rp. {{ number_format($row->total_bayar, 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">Belum ada data pemasukan pada periode ini.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-end">TOTAL PEMASUKAN</th>
                    <th class="text-end text-success">Rp. {{ number_format($totalPemasukan, 0, ',', '.') }}</th>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="card-footer bg-white no-print">
        {{ $data->links() }}
    </div>
</div>

{{--
    Tabel khusus cetak: memuat SEMUA transaksi pemasukan pada periode/filter
    yang dipilih (dari $dataCetak, tidak dipaginasi) — jadi hasil PDF selalu
    lengkap satu periode penuh, tidak dibatasi oleh pengaturan "Per Hal" yang
    hanya untuk tampilan di layar.
--}}
<div class="print-only">
    <div class="print-section-title">Rincian Penerimaan Dana</div>
    <table class="table mb-0 align-middle">
        <thead>
            <tr>
                <th style="width:24px;">No</th>
                <th>No Transaksi</th>
                <th>Tanggal</th>
                <th>Sumber Dana</th>
                <th>Pemberi Dana</th>
                <th>Keterangan / Tujuan Dana</th>
                <th class="text-end">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($dataCetak as $index => $row)
                @php
                    $noTransaksiCetak = 'TRX' . optional($row->tanggal)->format('ymd') . str_pad((string) $row->id, 4, '0', STR_PAD_LEFT);
                @endphp
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $noTransaksiCetak }}</td>
                    <td>{{ optional($row->tanggal)->format('d-m-Y') }}</td>
                    <td>{{ $row->kategori }}</td>
                    <td>{{ $row->nama_siswa ?: '-' }}</td>
                    <td>{{ $row->catatan_tujuan_dana ?: '-' }}</td>
                    <td class="text-end">Rp {{ number_format($row->total_bayar, 0, ',', '.') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-3">Tidak ada data pemasukan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        @if($dataCetak->count() > 0)
        <tfoot>
            <tr>
                <th colspan="6" class="text-end">TOTAL PEMASUKAN</th>
                <th class="text-end">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</th>
            </tr>
        </tfoot>
        @endif
    </table>

    @if(count($rekapItemCetak) > 0)
        <div class="print-section-title">Ringkasan per Sumber / Item Dana</div>
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th style="width:24px;">No</th>
                    <th>Sumber / Item Dana</th>
                    <th class="text-end">Jumlah Transaksi</th>
                    <th class="text-end">Total Nominal</th>
                    <th class="text-end">% dari Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rekapItemCetak as $item => $rekap)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item }}</td>
                        <td class="text-end">{{ $rekap['jumlah_transaksi'] }}</td>
                        <td class="text-end">Rp {{ number_format($rekap['total'], 0, ',', '.') }}</td>
                        <td class="text-end">{{ $totalPemasukan > 0 ? number_format($rekap['total'] / $totalPemasukan * 100, 1, ',', '.') : '0,0' }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>

<div class="report-sign print-only">
    <div>{{ now()->translatedFormat('d F Y') }}</div>
    <div>Mengetahui,</div>
    <div class="sign-line">Bendahara</div>
</div>

<script>
    function printLaporanPemasukan() {
        const originalTitle = document.title;
        const tanggal = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        document.title = 'Pemasukan_Dana_Export_PDF_' + tanggal;
        window.print();
        setTimeout(() => {
            document.title = originalTitle;
        }, 400);
    }

    (function () {
        const select = document.getElementById('periodePemasukan');
        const scope = select ? select.closest('form') : null;
        const toggle = () => {
            const value = select.value;
            document.querySelectorAll('.filter-harian').forEach((el) => el.style.display = value === 'harian' ? '' : 'none');
            document.querySelectorAll('.filter-bulanan').forEach((el) => el.style.display = value === 'bulanan' ? '' : 'none');
            document.querySelectorAll('.filter-tahunan').forEach((el) => el.style.display = value === 'tahunan' ? '' : 'none');
            document.querySelectorAll('.filter-custom').forEach((el) => el.style.display = value === 'custom' ? '' : 'none');

            if (!scope) {
                return;
            }

            scope.querySelectorAll('input[name="tanggal"], input[name="bulan"], input[name="tahun"], input[name="tanggal_mulai"], input[name="tanggal_selesai"]').forEach((input) => {
                input.disabled = true;
            });

            if (value === 'harian') {
                const input = scope.querySelector('input[name="tanggal"]');
                if (input) {
                    input.disabled = false;
                }
            } else if (value === 'bulanan') {
                const input = scope.querySelector('input[name="bulan"]');
                if (input) {
                    input.disabled = false;
                }
            } else if (value === 'tahunan') {
                const input = scope.querySelector('input[name="tahun"]');
                if (input) {
                    input.disabled = false;
                }
            } else if (value === 'custom') {
                const start = scope.querySelector('input[name="tanggal_mulai"]');
                const end = scope.querySelector('input[name="tanggal_selesai"]');
                if (start) {
                    start.disabled = false;
                }
                if (end) {
                    end.disabled = false;
                }
            }
        };

        select.addEventListener('change', toggle);
        toggle();
    })();
</script>
@endsection
