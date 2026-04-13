@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

    @media print {
        body {
            background: #ffffff !important;
            color: #000000 !important;
            font-family: "Courier New", monospace !important;
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

        .report-title {
            font-size: 13px;
            white-space: nowrap;
            letter-spacing: -0.2px;
        }

        .official-meta td {
            font-size: 12px;
            padding: 2px 4px;
            vertical-align: top;
        }

        .table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 12px;
            color: #000 !important;
        }

        .table thead th {
            border-top: 1px dashed #000 !important;
            border-bottom: 1px dashed #000 !important;
            background: transparent !important;
            color: #000 !important;
            padding: 6px 4px !important;
            font-weight: 700;
        }

        .table td,
        .table th {
            border: 0 !important;
            padding: 5px 4px !important;
            background: transparent !important;
        }

        .table tfoot th {
            border-top: 1px dashed #000 !important;
            border-bottom: 1px dashed #000 !important;
        }

        .table-responsive {
            overflow: visible !important;
        }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Laporan Rekapitulasi Keuangan / Kas</h4>
        <small class="text-muted">Ringkasan kondisi kas yayasan berdasarkan periode.</small>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('laporan.rekap-kas.export', request()->query()) }}" class="btn btn-success">
            <i class="fas fa-file-excel me-1"></i> Export Excel
        </a>
        <button onclick="printLaporanRekapKas()" class="btn btn-primary">
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
                <div class="report-meta">SMA UNGGULAN BPPT DARUS SHOLAH | Laporan Rekapitulasi Keuangan / Kas</div>
                <div class="report-meta">Periode: {{ $periode['label'] }} | Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
            </td>
        </tr>
    </table>
</div>

@php
    $dokumenRekapKas = 'LRK-' . now()->format('Ymd') . '-' . str_pad((string) (abs((int) $saldoAkhirKas) % 1000), 3, '0', STR_PAD_LEFT);
    $nomorSuratRekapKas = 'DS/REKAP/' . now()->format('Y/m') . '/' . str_pad((string) (abs((int) $saldoAkhirKas) % 1000), 3, '0', STR_PAD_LEFT);
@endphp

<div class="print-only mb-3" style="font-size:12px;color:#2f4564;line-height:1.7;">
    <table class="official-meta" style="width:100%;border-collapse:collapse;margin-bottom:10px;">
        <tr><td style="width:90px;">Nomor</td><td style="width:12px;">:</td><td>{{ $nomorSuratRekapKas }}</td><td style="text-align:right;">{{ now()->translatedFormat('d F Y') }}</td></tr>
        <tr><td>Sifat</td><td>:</td><td colspan="2">Penting</td></tr>
        <tr><td>Lampiran</td><td>:</td><td colspan="2">1 berkas</td></tr>
        <tr><td>Hal</td><td>:</td><td colspan="2">Laporan Rekapitulasi Kas</td></tr>
    </table>
    <div style="margin-bottom:8px;">Yth. Ketua SMA UNGGULAN BPPT DARUS SHOLAH<br>di Tempat</div>
    <div style="text-align:justify;margin-bottom:8px;">Dengan hormat, melalui surat ini kami sampaikan rekapitulasi posisi kas pada periode {{ $periode['label'] }} yang mencakup saldo awal, arus masuk, arus keluar, dan saldo akhir kas.</div>
    <div><strong style="display:inline-block;min-width:180px;">Jenis Dokumen</strong>: Laporan Rekapitulasi Kas</div>
    <div><strong style="display:inline-block;min-width:180px;">Nomor Dokumen</strong>: {{ $dokumenRekapKas }}</div>
    <div><strong style="display:inline-block;min-width:180px;">Posisi Kas Akhir</strong>: Rp {{ number_format($saldoAkhirKas, 0, ',', '.') }}</div>
</div>

<div class="card mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('laporan.rekap-kas') }}" class="row g-3 align-items-end js-auto-filter">
            <div class="col-md-3">
                <label class="form-label">Jenis Periode</label>
                <select name="periode" class="form-select" id="periodeRekapKas">
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

            <div class="col-md-3 d-flex gap-1 filter-actions-inline">
                <button type="submit" class="btn btn-primary">Tampilkan</button>
                <a href="{{ route('laporan.rekap-kas') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 no-print">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="text-muted">Periode Laporan</span>
            <strong>{{ $periode['label'] }}</strong>
        </div>
    </div>
</div>

<div class="row g-3 no-print">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted d-block">Saldo Awal Kas</small>
                <h5 class="mb-0">Rp. {{ number_format($saldoAwalKas, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted d-block">Total Pemasukan Dana</small>
                <h5 class="text-success mb-0">Rp. {{ number_format($totalPemasukan, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted d-block">Total Pengeluaran Dana</small>
                <h5 class="text-danger mb-0">Rp. {{ number_format($totalPengeluaran, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <small class="text-muted d-block">Saldo Akhir Kas</small>
                <h5 class="{{ $saldoAkhirKas >= 0 ? 'text-primary' : 'text-danger' }} mb-0">Rp. {{ number_format($saldoAkhirKas, 0, ',', '.') }}</h5>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4 no-print">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Ringkasan Laporan Kas</h6>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <tbody>
                    <tr>
                        <th style="width: 40%;">Periode Laporan</th>
                        <td>{{ $periode['label'] }}</td>
                    </tr>
                    <tr>
                        <th>Saldo Awal Kas</th>
                        <td>Rp. {{ number_format($saldoAwalKas, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Total Pemasukan Dana</th>
                        <td class="text-success">Rp. {{ number_format($totalPemasukan, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Total Pengeluaran Dana</th>
                        <td class="text-danger">Rp. {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <th>Saldo Akhir Kas</th>
                        <td class="fw-bold {{ $saldoAkhirKas >= 0 ? 'text-primary' : 'text-danger' }}">Rp. {{ number_format($saldoAkhirKas, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="report-sign print-only">
    <div>{{ now()->translatedFormat('d F Y') }}</div>
    <div>Mengetahui,</div>
    <div class="sign-line">Bendahara</div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Visual Ringkas Arus Kas</h6>
        <div style="height: 280px;">
            <canvas id="rekapKasChart"></canvas>
        </div>
    </div>
</div>

<script>
    function printLaporanRekapKas() {
        const originalTitle = document.title;
        const tanggal = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        document.title = 'Rekap_Kas_Export_PDF_' + tanggal;
        window.print();
        setTimeout(() => {
            document.title = originalTitle;
        }, 400);
    }

    (function () {
        const select = document.getElementById('periodeRekapKas');
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

    (function () {
        const ctx = document.getElementById('rekapKasChart');
        if (!ctx) {
            return;
        }

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($chartLabels),
                datasets: [{
                    label: 'Nilai (Rp)',
                    data: @json($chartData),
                    backgroundColor: ['rgba(22, 163, 74, 0.7)', 'rgba(220, 38, 38, 0.7)', 'rgba(37, 99, 235, 0.7)'],
                    borderColor: ['#16a34a', '#dc2626', '#2563eb'],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    })();
</script>
@endsection
