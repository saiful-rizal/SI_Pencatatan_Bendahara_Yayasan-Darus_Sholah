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

        /* ===== Kop surat: kartu putih rapi dengan garis aksen biru,
           bukan sekadar garis bawah polos ===== */
        .report-head {
            border: 1px solid #d7e2f3;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #f3f7ff 0%, #ffffff 55%);
            border-left: 4px solid #2563eb;
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

        /* ===== Tabel: garis solid tegas + header dengan latar warna,
           menggantikan gaya garis putus-putus sebelumnya ===== */
        .table {
            width: 100% !important;
            border-collapse: collapse !important;
            font-size: 10.5px !important;
            color: #1c2b3f !important;
            border: 1px solid #c7d3e6 !important;
        }

        .table thead th {
            border: none !important;
            border-bottom: 1.5px solid #2563eb !important;
            background: #eef4ff !important;
            color: #17325c !important;
            padding: 7px 6px !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 9.5px;
            letter-spacing: .3px;
            text-align: center !important;
        }

        .table td,
        .table th {
            border: none !important;
            border-bottom: 1px solid #e6ecf5 !important;
            padding: 6px 6px !important;
            background: transparent !important;
            text-align: center !important;
        }

        .table tbody tr:nth-child(even) td {
            background: #f8faff !important;
        }

        .table tfoot th,
        .table tfoot td {
            border: none !important;
            border-top: 1.5px solid #2563eb !important;
            background: #eef4ff !important;
            padding: 7px 6px !important;
        }

        .table-responsive {
            overflow: visible !important;
        }

        .rekap-chart-wrap {
            page-break-inside: avoid;
        }

        /* Jangan paksa seluruh blok Pemasukan+Pengeluaran tetap dalam 1 halaman:
           jika datanya panjang (mis. 24 transaksi), pemaksaan ini justru membuat
           tabel terpotong kasar di tengah baris. Biarkan blok mengalir ke halaman
           berikutnya secara alami; yang dijaga cukup baris tabel & judulnya (lihat
           aturan .rekap-detail-table di bawah). */
        .rekap-detail-wrap {
            page-break-inside: auto;
        }

        .rekap-detail-wrap .card {
            page-break-inside: auto;
            box-shadow: none !important;
            border: none !important;
        }

        .rekap-detail-wrap .card-body {
            padding: 0 !important;
        }

        .rekap-detail-wrap .col-md-6 {
            flex: 0 0 100% !important;
            max-width: 100% !important;
            margin-bottom: 16px;
        }

        .rekap-detail-wrap .col-md-6:last-child {
            margin-bottom: 0;
        }

        /* ===== Judul "Rincian Pemasukan/Pengeluaran per Kategori": pita judul
           ringkas dengan aksen warna, ikut pindah bersama baris pertama
           tabelnya (tidak boleh menggantung sendirian di akhir halaman) ===== */
        .rekap-detail-wrap h6 {
            page-break-after: avoid !important;
            break-after: avoid !important;
            font-size: 10.5px !important;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .4px;
            margin-bottom: 6px !important;
            padding: 5px 8px !important;
            border-radius: 4px;
            border: none !important;
            text-align: center !important;
        }

        .rekap-detail-wrap h6.text-success {
            background: #e8f7ee !important;
            color: #15803d !important;
            border-left: 3px solid #16a34a !important;
        }

        .rekap-detail-wrap h6.text-danger {
            background: #fdeeee !important;
            color: #b91c1c !important;
            border-left: 3px solid #dc2626 !important;
        }

        .rekap-detail-wrap h6 i {
            font-size: 9.5px !important;
            margin-right: 4px !important;
        }

        /* Baris tabel tidak boleh terpotong di tengah oleh page break, dan
           header kolom diulang otomatis jika tabel berlanjut ke halaman baru. */
        .rekap-detail-table thead {
            display: table-header-group !important;
        }

        .rekap-detail-table tbody tr,
        .rekap-detail-table tfoot tr {
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        .rekap-detail-table {
            width: 100% !important;
        }

        canvas#rekapKasChart {
            max-width: 100% !important;
        }

        .rekap-chart-wrap [style*="height: 300px"] {
            height: 220px !important;
        }

        .rekap-chart-wrap.rekap-summary-card {
            border: 1px solid #d7e2f3 !important;
            border-radius: 8px !important;
            box-shadow: none !important;
            padding: 4px;
        }

        /* Kolom Proporsi: sembunyikan bar warna (tidak selalu tercetak jelas),
           tampilkan persentase rapi dengan jarak & pemisah jelas dari kolom Total. */
        .rekap-kategori-bar {
            display: none !important;
        }

        .rekap-proporsi-col {
            border-left: 1px dashed #9fb0c8 !important;
            padding-left: 10px !important;
            font-weight: 700;
            color: #2563eb !important;
        }

        .rekap-proporsi-col small {
            font-size: 10.5px;
            display: inline-block;
            color: inherit !important;
        }

        .rekap-legend-dot {
            border: 1px solid rgba(0,0,0,0.15);
        }

        /* ===== Ringkasan Posisi Kas: kartu berwarna per item, bukan kotak
           putih polos, supaya arus kas langsung terbaca sekilas ===== */
        .rekap-summary-card > .card-body > h6 {
            font-size: 12.5px;
            color: #17325c;
        }

        .rekap-flow-item {
            border-radius: 6px !important;
        }

        .rekap-flow-item:nth-child(1) {
            background: #f4f6fb !important;
            border-left: 3px solid #5b7091 !important;
        }

        .rekap-flow-item.rekap-flow-in {
            background: #e8f7ee !important;
            border-left: 3px solid #16a34a !important;
        }

        .rekap-flow-item.rekap-flow-out {
            background: #fdeeee !important;
            border-left: 3px solid #dc2626 !important;
        }

        .rekap-flow-item.rekap-flow-result {
            background: #eaf2ff !important;
            border-left: 3px solid #2563eb !important;
        }

        .rekap-status-badge {
            border: none !important;
            border-radius: 20px !important;
        }

        .rekap-status-badge.bg-success {
            background: #e8f7ee !important;
            color: #15803d !important;
        }

        .rekap-status-badge.bg-danger {
            background: #fdeeee !important;
            color: #b91c1c !important;
        }

        /* Tanda tangan: jangan sampai kepisah sendirian di halaman baru */
        .report-sign {
            page-break-inside: avoid;
            page-break-before: avoid;
            margin-top: 24px;
        }
    }

    .rekap-detail-table th,
    .rekap-detail-table td {
        white-space: nowrap;
    }

    .rekap-detail-table td:first-child,
    .rekap-detail-table th:first-child {
        white-space: normal;
    }

    .rekap-proporsi-col {
        padding-left: 18px !important;
        min-width: 90px;
    }

    /* ===== Filter Card (smart look) ===== */
    .rekap-filter-card {
        border: 1px solid #e6ecf5;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(30, 54, 85, 0.04);
    }

    .rekap-filter-head {
        background: linear-gradient(135deg, #eef4ff 0%, #f7faff 100%);
        border-bottom: 1px solid #e6ecf5;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .rekap-filter-head .rekap-filter-icon {
        width: 34px;
        height: 34px;
        border-radius: 9px;
        background: #2563eb;
        color: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex: 0 0 auto;
    }

    .rekap-filter-head h6 {
        margin: 0;
        font-weight: 800;
        color: #1e3655;
        font-size: 14.5px;
    }

    .rekap-filter-head small {
        color: #6b7f9d;
    }

    .rekap-filter-card .card-body {
        padding: 18px;
    }

    .rekap-filter-card .form-label {
        font-size: 12.5px;
        font-weight: 700;
        color: #445d7d;
        margin-bottom: 4px;
    }

    .rekap-filter-card .form-select,
    .rekap-filter-card .form-control {
        border-radius: 9px;
        border-color: #dbe3f0;
        font-size: 14px;
    }

    .rekap-filter-card .form-select:focus,
    .rekap-filter-card .form-control:focus {
        border-color: #93b4f5;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .rekap-filter-card .btn-primary {
        border-radius: 9px;
        font-weight: 600;
        padding-inline: 18px;
    }

    .rekap-filter-card .btn-outline-secondary {
        border-radius: 9px;
        font-weight: 600;
    }

    /* ===== Ringkasan header card ===== */
    .rekap-summary-card {
        border-radius: 16px;
        border: 1px solid #e6ecf5;
        background: linear-gradient(180deg, #ffffff 0%, #fbfcff 100%);
    }

    .rekap-kategori-bar {
        height: 8px;
        border-radius: 4px;
        background: #eef2f8;
        overflow: hidden;
    }

    .rekap-kategori-bar span {
        display: block;
        height: 100%;
        border-radius: 4px;
    }

    .rekap-legend-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 6px;
    }

    .rekap-flow {
        display: flex;
        align-items: stretch;
        flex-wrap: wrap;
        gap: 12px;
    }

    .rekap-flow-item {
        flex: 1 1 160px;
        position: relative;
        background: #f7f9fc;
        border: 1px solid #e6ecf5;
        border-radius: 12px;
        padding: 14px 16px 14px 44px;
        min-width: 150px;
        transition: box-shadow .15s ease, transform .15s ease;
    }

    .rekap-flow-item:hover {
        box-shadow: 0 6px 16px rgba(30, 54, 85, 0.08);
        transform: translateY(-1px);
    }

    .rekap-flow-item .rekap-flow-icon {
        position: absolute;
        top: 12px;
        left: 12px;
        width: 24px;
        height: 24px;
        border-radius: 7px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        background: #eef2f8;
        color: #5b7091;
    }

    .rekap-flow-item.rekap-flow-in .rekap-flow-icon {
        background: rgba(22, 163, 74, 0.12);
        color: #16a34a;
    }

    .rekap-flow-item.rekap-flow-out .rekap-flow-icon {
        background: rgba(220, 38, 38, 0.12);
        color: #dc2626;
    }

    .rekap-flow-item.rekap-flow-result {
        background: linear-gradient(135deg, #eaf2ff 0%, #f3f8ff 100%);
        border-color: #bcd6ff;
    }

    .rekap-flow-item.rekap-flow-result .rekap-flow-icon {
        background: rgba(37, 99, 235, 0.14);
        color: #2563eb;
    }

    .rekap-flow-op {
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        font-weight: 700;
        color: #9fb0c8;
        flex: 0 0 auto;
        padding: 0 2px;
    }

    .rekap-status-badge {
        font-size: 13px;
        padding: 7px 14px;
        border-radius: 20px;
        font-weight: 600;
        letter-spacing: .2px;
    }

    .rekap-chart-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 14px;
        justify-content: center;
        margin-top: 6px;
    }

    .rekap-chart-legend span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12.5px;
        color: #445d7d;
        font-weight: 600;
    }

    .rekap-chart-legend i {
        width: 10px;
        height: 10px;
        border-radius: 3px;
        display: inline-block;
    }

    @media print {
        /* Kertas portrait terlalu sempit untuk menampilkan 4 kotak + 3 operator
           berjajar horizontal (dulu inilah penyebab kotak Saldo Akhir kepotong).
           Di kertas, tampilkan sebagai daftar ringkas dua kolom (label : nilai)
           yang ditumpuk ke bawah, supaya semua item pasti muat & tidak terpotong. */
        .rekap-flow {
            display: block;
            border: 1px solid #000;
            border-radius: 0;
        }

        .rekap-flow-op {
            display: none !important;
        }

        .rekap-flow-item {
            display: flex !important;
            justify-content: space-between;
            align-items: center;
            flex: 0 0 100% !important;
            background: transparent !important;
            border: none !important;
            border-bottom: 1px dashed #000 !important;
            border-radius: 0 !important;
            padding: 7px 10px !important;
            text-align: left;
        }

        .rekap-flow-item:last-child {
            border-bottom: none !important;
        }

        .rekap-flow-icon {
            display: none !important;
        }

        .rekap-flow-item small {
            margin-bottom: 0 !important;
        }

        .rekap-flow-item .fw-bold {
            font-size: 13px !important;
        }

        .rekap-status-badge {
            border: 1px solid #000 !important;
            background: transparent !important;
            color: #000 !important;
            border-radius: 0 !important;
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

    // Helper format Rupiah yang benar untuk nilai negatif, dipakai di seluruh halaman
    // ini supaya tidak ada lagi tampilan aneh seperti "Rp. -6.330.000".
    $formatRp = function ($nilai) {
        $angka = (float) $nilai;
        $tanda = $angka < 0 ? '-' : '';
        return $tanda . 'Rp ' . number_format(abs($angka), 0, ',', '.');
    };
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
    <div><strong style="display:inline-block;min-width:180px;">Posisi Kas Akhir</strong>: {{ $formatRp($saldoAkhirKas) }}</div>
</div>

<div class="card mb-4 no-print rekap-filter-card">
    <div class="rekap-filter-head">
        <div class="rekap-filter-icon"><i class="fas fa-sliders-h"></i></div>
        <div>
            <h6>Filter Periode Laporan</h6>
            <small>Pilih jenis periode untuk menampilkan rekapitulasi kas</small>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('laporan.rekap-kas') }}" class="row g-3 align-items-end js-auto-filter">
            <div class="col-md-3">
                <label class="form-label"><i class="fas fa-calendar-alt me-1 text-primary"></i> Jenis Periode</label>
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

            <div class="col-md-3 d-flex gap-2 filter-actions-inline">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Tampilkan</button>
                <a href="{{ route('laporan.rekap-kas') }}" class="btn btn-outline-secondary"><i class="fas fa-rotate-left me-1"></i>Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-4 rekap-chart-wrap rekap-summary-card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
            <div>
                <h6 class="fw-bold mb-1">Ringkasan Posisi Kas</h6>
                <small class="text-muted">Periode: {{ $periode['label'] }}</small>
            </div>
            <span class="badge {{ $saldoAkhirKas >= 0 ? 'bg-success' : 'bg-danger' }} rekap-status-badge">
                <i class="fas {{ $saldoAkhirKas >= 0 ? 'fa-circle-check' : 'fa-triangle-exclamation' }} me-1"></i>
                {{ $saldoAkhirKas >= 0 ? 'Surplus (kas aman)' : 'Defisit (kas minus)' }}
            </span>
        </div>

        <p class="text-muted small mb-3">
            Cara membaca: <strong>Saldo Awal</strong> ditambah <strong>Pemasukan</strong>, dikurangi <strong>Pengeluaran</strong>,
            hasilnya adalah <strong>Saldo Akhir</strong> kas pada akhir periode ini.
        </p>

        <div class="rekap-flow">
            <div class="rekap-flow-item">
                <div class="rekap-flow-icon"><i class="fas fa-wallet"></i></div>
                <small class="text-muted d-block mb-1">Saldo Awal Kas</small>
                <div class="fw-bold fs-6">{{ $formatRp($saldoAwalKas) }}</div>
            </div>

            <div class="rekap-flow-op">+</div>

            <div class="rekap-flow-item rekap-flow-in">
                <div class="rekap-flow-icon"><i class="fas fa-arrow-down"></i></div>
                <small class="text-muted d-block mb-1">Total Pemasukan</small>
                <div class="fw-bold fs-6 text-success">{{ $formatRp($totalPemasukan) }}</div>
            </div>

            <div class="rekap-flow-op">−</div>

            <div class="rekap-flow-item rekap-flow-out">
                <div class="rekap-flow-icon"><i class="fas fa-arrow-up"></i></div>
                <small class="text-muted d-block mb-1">Total Pengeluaran</small>
                <div class="fw-bold fs-6 text-danger">{{ $formatRp($totalPengeluaran) }}</div>
            </div>

            <div class="rekap-flow-op">=</div>

            <div class="rekap-flow-item rekap-flow-result">
                <div class="rekap-flow-icon"><i class="fas fa-scale-balanced"></i></div>
                <small class="text-muted d-block mb-1">Saldo Akhir Kas</small>
                <div class="fw-bold fs-5 {{ $saldoAkhirKas >= 0 ? 'text-primary' : 'text-danger' }}">
                    {{ $formatRp($saldoAkhirKas) }}
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $paletteMasuk = ['#16a34a', '#22c55e', '#4ade80', '#86efac', '#bbf7d0', '#15803d', '#065f46'];
    $paletteKeluar = ['#dc2626', '#ef4444', '#f87171', '#fca5a5', '#fecaca', '#b91c1c', '#7f1d1d'];
@endphp

<div class="row g-3 mt-1 rekap-detail-wrap">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3 text-success">
                    <i class="fas fa-arrow-down me-1"></i> Rincian Pemasukan per Kategori
                </h6>
                @if(count($rincianPemasukan ?? []) === 0)
                    <p class="text-muted mb-0 small">Tidak ada data pemasukan pada periode ini.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 rekap-detail-table">
                            <colgroup>
                                <col style="width: 42%;">
                                <col style="width: 20%;">
                                <col style="width: 22%;">
                                <col style="width: 16%;">
                            </colgroup>
                            <thead>
                                <tr class="text-muted">
                                    <th>Kategori</th>
                                    <th class="text-center">Jml. Transaksi</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end rekap-proporsi-col">Proporsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rincianPemasukan as $i => $row)
                                    <tr>
                                        <td>
                                            <span class="rekap-legend-dot" style="background:{{ $paletteMasuk[$i % count($paletteMasuk)] }}"></span>
                                            {{ $row['kategori'] }}
                                        </td>
                                        <td class="text-center">{{ $row['jumlah_transaksi'] }}</td>
                                        <td class="text-end">Rp. {{ number_format($row['total'], 0, ',', '.') }}</td>
                                        <td class="text-end rekap-proporsi-col">
                                            <div class="rekap-kategori-bar mb-1">
                                                <span style="width:{{ round($row['persentase'], 1) }}%;background:{{ $paletteMasuk[$i % count($paletteMasuk)] }}"></span>
                                            </div>
                                            <small class="text-muted">{{ number_format($row['persentase'], 1, ',', '.') }}%</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total Pemasukan</td>
                                    <td class="text-center">{{ array_sum(array_column($rincianPemasukan, 'jumlah_transaksi')) }}</td>
                                    <td class="text-end text-success" colspan="2">Rp. {{ number_format($totalPemasukan, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <h6 class="fw-bold mb-3 text-danger">
                    <i class="fas fa-arrow-up me-1"></i> Rincian Pengeluaran per Kategori
                </h6>
                @if(count($rincianPengeluaran ?? []) === 0)
                    <p class="text-muted mb-0 small">Tidak ada data pengeluaran pada periode ini.</p>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 rekap-detail-table">
                            <colgroup>
                                <col style="width: 42%;">
                                <col style="width: 20%;">
                                <col style="width: 22%;">
                                <col style="width: 16%;">
                            </colgroup>
                            <thead>
                                <tr class="text-muted">
                                    <th>Kategori</th>
                                    <th class="text-center">Jml. Transaksi</th>
                                    <th class="text-end">Total</th>
                                    <th class="text-end rekap-proporsi-col">Proporsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rincianPengeluaran as $i => $row)
                                    <tr>
                                        <td>
                                            <span class="rekap-legend-dot" style="background:{{ $paletteKeluar[$i % count($paletteKeluar)] }}"></span>
                                            {{ $row['kategori'] }}
                                        </td>
                                        <td class="text-center">{{ $row['jumlah_transaksi'] }}</td>
                                        <td class="text-end">Rp. {{ number_format($row['total'], 0, ',', '.') }}</td>
                                        <td class="text-end rekap-proporsi-col">
                                            <div class="rekap-kategori-bar mb-1">
                                                <span style="width:{{ round($row['persentase'], 1) }}%;background:{{ $paletteKeluar[$i % count($paletteKeluar)] }}"></span>
                                            </div>
                                            <small class="text-muted">{{ number_format($row['persentase'], 1, ',', '.') }}%</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold">
                                    <td>Total Pengeluaran</td>
                                    <td class="text-center">{{ array_sum(array_column($rincianPengeluaran, 'jumlah_transaksi')) }}</td>
                                    <td class="text-end text-danger" colspan="2">Rp. {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm mt-4 rekap-chart-wrap rekap-summary-card">
    <div class="card-body">
        <h6 class="fw-bold mb-1"><i class="fas fa-chart-column me-1 text-primary"></i> Visual Ringkas Arus Kas</h6>
        <small class="text-muted d-block mb-2">Perbandingan saldo awal, pemasukan, pengeluaran, dan saldo akhir kas pada periode {{ $periode['label'] }}.</small>
        <div style="height: 300px;">
            <canvas id="rekapKasChart"></canvas>
        </div>
        <div class="rekap-chart-legend no-print">
            <span><i style="background:#16a34a"></i> Pemasukan</span>
            <span><i style="background:#dc2626"></i> Pengeluaran</span>
            <span><i style="background:#2563eb"></i> Saldo Akhir</span>
        </div>
    </div>
</div>

<div class="report-sign print-only">
    <div>{{ now()->translatedFormat('d F Y') }}</div>
    <div>Mengetahui,</div>
    <div class="sign-line">Bendahara</div>
</div>

<script>
    function printLaporanRekapKas() {
        const originalTitle = document.title;
        const tanggal = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        document.title = 'Rekap_Kas_Export_PDF_' + tanggal;

        // Beri jeda sesaat agar grafik (Chart.js) selesai digambar ulang
        // sebelum dialog cetak/PDF muncul, supaya diagram tidak kosong/kepotong.
        setTimeout(() => {
            window.print();
        }, 200);

        setTimeout(() => {
            document.title = originalTitle;
        }, 800);
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

    function formatRupiahSingkat(nilai) {
        const angka = Number(nilai) || 0;
        const abs = Math.abs(angka);
        const tanda = angka < 0 ? '-' : '';
        if (abs >= 1000000000) return tanda + 'Rp ' + (abs / 1000000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' M';
        if (abs >= 1000000) return tanda + 'Rp ' + (abs / 1000000).toLocaleString('id-ID', { maximumFractionDigits: 1 }) + ' Jt';
        if (abs >= 1000) return tanda + 'Rp ' + (abs / 1000).toLocaleString('id-ID', { maximumFractionDigits: 0 }) + ' Rb';
        return tanda + 'Rp ' + abs.toLocaleString('id-ID');
    }

    function formatRupiahPenuh(nilai) {
        return 'Rp ' + (Number(nilai) || 0).toLocaleString('id-ID', { maximumFractionDigits: 0 });
    }

    (function () {
        const ctx = document.getElementById('rekapKasChart');
        if (!ctx) {
            return;
        }

        const chartLabels = @json($chartLabels);
        const chartData = @json($chartData);

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Nilai (Rp)',
                    data: chartData,
                    backgroundColor: ['rgba(22, 163, 74, 0.75)', 'rgba(220, 38, 38, 0.75)', 'rgba(37, 99, 235, 0.75)'],
                    borderColor: ['#16a34a', '#dc2626', '#2563eb'],
                    borderWidth: 1,
                    borderRadius: 6,
                    maxBarThickness: 90
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { top: 28 }
                },
                animation: {
                    onComplete: function () {
                        const chartInstance = this;
                        const chartCtx = chartInstance.ctx;
                        const area = chartInstance.chartArea;
                        chartCtx.save();
                        chartCtx.font = 'bold 12px sans-serif';
                        chartCtx.fillStyle = '#1e3655';
                        chartCtx.textAlign = 'center';
                        chartInstance.data.datasets.forEach((dataset, di) => {
                            const meta = chartInstance.getDatasetMeta(di);
                            meta.data.forEach((bar, i) => {
                                const value = dataset.data[i];
                                const label = formatRupiahSingkat(value);

                                if (value >= 0) {
                                    // Jika label akan terpotong di atas area chart, taruh di dalam bar saja.
                                    const yLuar = bar.y - 8;
                                    const yDalam = bar.y + 16;
                                    chartCtx.textBaseline = 'bottom';
                                    if (yLuar - 14 < area.top) {
                                        chartCtx.fillStyle = '#ffffff';
                                        chartCtx.fillText(label, bar.x, yDalam);
                                    } else {
                                        chartCtx.fillStyle = '#1e3655';
                                        chartCtx.fillText(label, bar.x, yLuar);
                                    }
                                } else {
                                    chartCtx.textBaseline = 'top';
                                    chartCtx.fillStyle = '#1e3655';
                                    chartCtx.fillText(label, bar.x, bar.y + 8);
                                }
                            });
                        });
                        chartCtx.restore();
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (item) => ' ' + formatRupiahPenuh(item.raw)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grace: '15%',
                        ticks: {
                            callback: (value) => formatRupiahSingkat(value)
                        },
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    })();
</script>
@endsection
