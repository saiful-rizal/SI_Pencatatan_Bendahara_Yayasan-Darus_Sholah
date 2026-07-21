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

    /* Custom Styles untuk Tampilan Modern */
    .modern-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        background: white;
        transition: transform 0.2s;
    }
    .modern-header {
        border-top-left-radius: 1rem;
        border-top-right-radius: 1rem;
        padding: 1.25rem;
        color: white;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Gradients untuk Header */
    .bg-success-gradient { background: linear-gradient(45deg, #10b981, #059669); }
    .bg-danger-gradient { background: linear-gradient(45deg, #ef4444, #b91c1c); }
    .bg-primary-gradient { background: linear-gradient(45deg, #3b82f6, #2563eb); }

    /* Style List Item */
    .list-item {
        display: flex;
        justify-content: space-between;
        padding: 0.75rem 0;
        border-bottom: 1px dashed #e2e8f0;
    }
    .list-item:last-child { border-bottom: none; }
    .list-label { color: #64748b; font-weight: 500; }
    .list-value { font-weight: 600; color: #1e293b; }

    .print-only {
        display: none;
    }

    /* Print Styling */
    @media print {
        .no-print { display: none !important; }
        .print-only { display: block !important; }
        .modern-card { box-shadow: none; border: 1px solid #ddd; }
        body { background-color: white; }

        .report-head {
            border: none;
            border-bottom: 2px solid #1f3657;
            border-radius: 0;
            padding: 0 0 10px 0;
            margin-bottom: 14px;
        }

        .official-meta td {
            font-size: 12px;
            padding: 2px 4px;
            vertical-align: top;
        }
    }
</style>

<!-- 1. Header Laporan (Tombol Export & Cetak) -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print flex-wrap gap-2">
    <div>
        <h2 class="fw-bold text-dark mb-1">Laporan Keuangan Yayasan</h2>
        <small class="text-muted">Rekapitulasi Pemasukan dan Pengeluaran{{ isset($filterItem) ? ' - ' . $filterItem->nama_item : (($groupBy ?? 'kategori') === 'per_item' ? ' Per Item' : ' Per Kategori') }}</small>
    </div>
    <div class="d-flex gap-2">
        <!-- Tombol Export Excel BARU -->
        <a href="{{ route('laporan.yayasan.export', request()->query()) }}" class="btn btn-success rounded-pill px-4 shadow-sm text-white text-decoration-none">
            <i class="fas fa-file-excel me-2"></i> Export Excel
        </a>
        <!-- Tombol Cetak -->
        <button onclick="printLaporanYayasan()" class="btn btn-dark rounded-pill px-4 shadow-sm">
            <i class="fas fa-file-pdf me-2"></i> Cetak PDF
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
                <div class="report-meta">SMA UNGGULAN BPPT DARUS SHOLAH | Laporan Keuangan Yayasan</div>
                <div class="report-meta">Dicetak: {{ now()->format('d-m-Y H:i') }}</div>
            </td>
        </tr>
    </table>
</div>

@php
    $groupLabel = isset($filterItem) ? $filterItem->nama_item : (($groupBy ?? 'kategori') === 'per_item' ? 'Item' : 'Kategori');
    $nomorSurat = '001/B/SMA.U.BPPT.DS/' . now()->format('d/m/Y');
    $jenisDokumen = isset($filterItem) ? 'Laporan Keuangan Yayasan Per Item - ' . $filterItem->nama_item : (($groupBy ?? 'kategori') === 'per_item' ? 'Laporan Keuangan Yayasan Per Item' : 'Laporan Keuangan Yayasan Per Kategori');
    $periodeYayasan = ($request->tanggal_mulai && $request->tanggal_selesai)
        ? date('d-m-Y', strtotime($request->tanggal_mulai)) . ' s/d ' . date('d-m-Y', strtotime($request->tanggal_selesai))
        : 'Semua Periode';
@endphp

<div class="print-only mb-3" style="font-size:12px;color:#2f4564;line-height:1.7;">
    <table class="official-meta" style="width:100%;border-collapse:collapse;margin-bottom:10px;">
        <tr><td style="width:90px;">Nomor</td><td style="width:12px;">:</td><td>{{ $nomorSurat }}</td><td style="text-align:right;">{{ now()->translatedFormat('d F Y') }}</td></tr>
        <tr><td>Sifat</td><td>:</td><td colspan="2">Penting</td></tr>
        <tr><td>Lampiran</td><td>:</td><td colspan="2">1 berkas</td></tr>
        <tr><td>Hal</td><td>:</td><td colspan="2">Laporan Keuangan Yayasan</td></tr>
    </table>
    <div style="margin-bottom:8px;">Yth. Ketua SMA UNGGULAN BPPT DARUS SHOLAH<br>di Tempat</div>
    <div style="text-align:justify;margin-bottom:8px;">Bersama ini kami sampaikan laporan keuangan yayasan untuk periode {{ $periodeYayasan }} meliputi rekap pemasukan, pengeluaran, serta posisi saldo akhir.</div>
    <div><strong style="display:inline-block;min-width:180px;">Jenis Dokumen</strong>: {{ $jenisDokumen }}</div>
    <div><strong style="display:inline-block;min-width:180px;">Nomor Dokumen</strong>: {{ $nomorSurat }}</div>
    <div><strong style="display:inline-block;min-width:180px;">Periode Laporan</strong>: {{ $periodeYayasan }}</div>
</div>

<!-- 2. Filter Tanggal (BARU) -->
<div class="card bg-light border-0 rounded-4 p-4 mb-4 no-print">
    <form method="GET" action="{{ route('laporan.yayasan') }}" class="row g-3 align-items-end js-auto-filter" data-auto-submit>
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Dari Tanggal</label>
            <input type="date" name="tanggal_mulai" class="form-control" value="{{ $request->tanggal_mulai }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Sampai Tanggal</label>
            <input type="date" name="tanggal_selesai" class="form-control" value="{{ $request->tanggal_selesai }}">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Kelompokkan Berdasarkan</label>
            <select name="group_by" class="form-select">
                <option value="kategori" {{ ($groupBy ?? 'kategori') === 'kategori' ? 'selected' : '' }}>Per Kategori</option>
                <option value="per_item" {{ ($groupBy ?? '') === 'per_item' ? 'selected' : '' }}>Per Item</option>
                @foreach($itemPembayaranList as $item)
                    <option value="{{ $item->id }}" {{ (string) ($groupBy ?? '') === (string) $item->id ? 'selected' : '' }}>
                        {{ $item->kode }} - {{ $item->nama_item }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100">Filter</button>
                <a href="{{ route('laporan.yayasan') }}" class="btn btn-outline-secondary" title="Reset"><i class="fas fa-undo"></i></a>
            </div>
        </div>
    </form>
</div>

<!-- 3. Kartu Pemasukan & Pengeluaran (Desain Diperbarui) -->
<div class="row g-4 no-print">
    <!-- Card Pemasukan -->
    <div class="col-md-6">
        <div class="modern-card h-100">
            <div class="modern-header bg-success-gradient">
                <span><i class="fas fa-arrow-down me-2"></i>PEMASUKAN</span>
                <span class="badge bg-white text-success rounded-pill">{{ $reportMasuk->count() }} {{ $groupLabel }}</span>
            </div>
            <div class="card-body p-3">
                @forelse($reportMasuk as $key => $total)
                <div class="list-item flex-column align-items-stretch">
                    <div class="d-flex justify-content-between align-items-center w-100" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#detailMasuk{{ $loop->index }}">
                        <span class="list-label"><i class="fas fa-chevron-right me-1 small"></i>{{ $key }}</span>
                        <span class="list-value text-success">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                    @if(!empty($detailMasuk[$key]))
                    <div class="collapse mt-2" id="detailMasuk{{ $loop->index }}">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0" style="font-size:12px;">
                                <thead>
                                    <tr class="text-muted"><th>Tanggal</th><th>Nama Siswa</th><th>Kelas</th><th>Item</th><th class="text-end">Nominal</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($detailMasuk[$key] as $row)
                                    <tr>
                                        <td>{{ $row['tanggal'] }}</td>
                                        <td>{{ $row['nama_siswa'] }}</td>
                                        <td>{{ $row['kelas'] }}</td>
                                        <td>{{ $row['item'] }}</td>
                                        <td class="text-end">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
                @empty
                <div class="text-center text-muted py-3">Tidak ada data pemasukan.</div>
                @endforelse

                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center pt-1">
                    <span class="fw-bold text-dark">Total Pemasukan</span>
                    <h5 class="fw-bold text-success mb-0">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Pengeluaran -->
    <div class="col-md-6">
        <div class="modern-card h-100">
            <div class="modern-header bg-danger-gradient">
                <span><i class="fas fa-arrow-up me-2"></i>PENGELUARAN</span>
                <span class="badge bg-white text-danger rounded-pill">{{ $reportKeluar->count() }} {{ $groupLabel }}</span>
            </div>
            <div class="card-body p-3">
                @forelse($reportKeluar as $key => $total)
                <div class="list-item flex-column align-items-stretch">
                    <div class="d-flex justify-content-between align-items-center w-100" style="cursor:pointer;" data-bs-toggle="collapse" data-bs-target="#detailKeluar{{ $loop->index }}">
                        <span class="list-label"><i class="fas fa-chevron-right me-1 small"></i>{{ $key }}</span>
                        <span class="list-value text-danger">Rp {{ number_format($total, 0, ',', '.') }}</span>
                    </div>
                    @if(!empty($detailKeluar[$key]))
                    <div class="collapse mt-2" id="detailKeluar{{ $loop->index }}">
                        <div class="table-responsive">
                            <table class="table table-sm mb-0" style="font-size:12px;">
                                <thead>
                                    <tr class="text-muted"><th>Tanggal</th><th>Keterangan</th><th class="text-end">Nominal</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($detailKeluar[$key] as $row)
                                    <tr>
                                        <td>{{ $row['tanggal'] }}</td>
                                        <td>{{ $row['keterangan'] }}</td>
                                        <td class="text-end">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
                @empty
                <div class="text-center text-muted py-3">Tidak ada data pengeluaran.</div>
                @endforelse

                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center pt-1">
                    <span class="fw-bold text-dark">Total Pengeluaran</span>
                    <h5 class="fw-bold text-danger mb-0">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 4. Kartu Saldo Akhir (Desain Diperbarui) -->
<div class="mt-4 no-print">
    <div class="modern-card overflow-hidden">
        <div class="modern-header bg-primary-gradient">
            <h5 class="mb-0"><i class="fas fa-balance-scale me-2"></i>PERHITUNGAN AKHIR</h5>
        </div>
        <div class="card-body text-center py-5">
            <small class="text-muted text-uppercase fw-bold tracking-wider">Laba / Rugi Bersih</small>
            <h1 class="display-4 fw-bold mt-2 {{ $saldo >= 0 ? 'text-primary' : 'text-danger' }}">
                Rp {{ number_format($saldo, 0, ',', '.') }}
            </h1>
            <div class="mt-3">
                @if($saldo >= 0)
                    <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill">Surplus / Laba</span>
                @else
                    <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill">Defisit / Rugi</span>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="print-only">
    <table class="table table-bordered table-sm" style="width:100%;border-collapse:collapse;font-size:11px;">
        <thead>
            <tr>
                <th style="width:28px;">No</th>
                <th style="width:65px;">Tanggal</th>
                <th>Uraian / Nama Siswa</th>
                <th style="width:55px;">Kelas</th>
                <th>Item / Keterangan</th>
                <th style="width:90px;">Metode</th>
                <th style="width:105px;">Nominal</th>
            </tr>
        </thead>
        <tbody>
            <tr><td colspan="7" style="font-weight:700;background:#e5e7eb;">I. REKAPITULASI PEMASUKAN</td></tr>
            @php $noMasuk = 1; @endphp
            @forelse($reportMasuk as $key => $total)
                <tr style="background:#f3f4f6;">
                    <td colspan="5" style="font-weight:700;">{{ $key }}</td>
                    <td style="font-weight:700;">Subtotal</td>
                    <td style="font-weight:700;">Rp {{ number_format($total, 0, ',', '.') }}</td>
                </tr>
                @foreach(($detailMasuk[$key] ?? []) as $row)
                <tr>
                    <td>{{ $noMasuk++ }}</td>
                    <td>{{ $row['tanggal'] }}</td>
                    <td>{{ $row['nama_siswa'] }}</td>
                    <td>{{ $row['kelas'] }}</td>
                    <td>{{ $row['item'] }}</td>
                    <td>{{ $row['metode'] ?? '-' }}</td>
                    <td>Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            @empty
                <tr><td colspan="7">Tidak ada data pemasukan.</td></tr>
            @endforelse
            <tr>
                <td colspan="5" style="font-weight:700;text-align:right;">Total Pemasukan</td>
                <td></td>
                <td style="font-weight:700;">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</td>
            </tr>

            <tr><td colspan="7" style="font-weight:700;background:#e5e7eb;">II. REKAPITULASI PENGELUARAN</td></tr>
            @if($reportKeluar->isNotEmpty())
            <tr style="background:#f8fafc;font-size:10px;color:#475569;">
                <th style="text-align:left;">No</th>
                <th style="text-align:left;">Tanggal</th>
                <th style="text-align:left;">Item</th>
                <th style="text-align:left;">Jumlah x Harga</th>
                <th style="text-align:left;">Keterangan</th>
                <th style="text-align:left;">Dicatat Oleh</th>
                <th style="text-align:left;">Nominal</th>
            </tr>
            @endif
            @php $noKeluar = 1; @endphp
            @forelse($reportKeluar as $key => $total)
                <tr style="background:#f3f4f6;">
                    <td colspan="5" style="font-weight:700;">{{ $key }}</td>
                    <td style="font-weight:700;">Subtotal</td>
                    <td style="font-weight:700;">Rp {{ number_format($total, 0, ',', '.') }}</td>
                </tr>
                @foreach(($detailKeluar[$key] ?? []) as $row)
                <tr>
                    <td>{{ $noKeluar++ }}</td>
                    <td>{{ $row['tanggal'] }}</td>
                    <td>{{ $row['item'] }}</td>
                    <td>{{ $row['jumlah_label'] }}</td>
                    <td>{{ $row['keterangan'] }}</td>
                    <td>{{ $row['dicatat_oleh'] ?? '-' }}</td>
                    <td>Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            @empty
                <tr><td colspan="7">Tidak ada data pengeluaran.</td></tr>
            @endforelse
            <tr>
                <td colspan="5" style="font-weight:700;text-align:right;">Total Pengeluaran</td>
                <td></td>
                <td style="font-weight:700;">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</td>
            </tr>

            <tr><td colspan="7" style="font-weight:700;background:#e5e7eb;">III. POSISI AKHIR</td></tr>
            <tr>
                <td colspan="5" style="font-weight:700;text-align:right;">Laba / Rugi Bersih</td>
                <td></td>
                <td style="font-weight:700;">Rp {{ number_format($saldo, 0, ',', '.') }}</td>
            </tr>
            <tr><td colspan="7" style="border:none;"><br><strong>Jenis Dokumen:</strong> {{ $jenisDokumen }}<br><strong>Nomor Dokumen:</strong> {{ $nomorSurat }}<br><strong>Periode:</strong> {{ $periodeYayasan }}<br><strong>Dicetak Oleh:</strong> {{ auth()->user()->name ?? '-' }}</td></tr>
        </tbody>
    </table>

    <table style="width:100%;border-collapse:collapse;margin-top:36px;font-size:11px;">
        <tr>
            <td style="width:60%;"></td>
            <td style="width:40%;text-align:center;">Jember, {{ now()->translatedFormat('d F Y') }}<br>Dibuat oleh, Bendahara</td>
        </tr>
        <tr>
            <td></td>
            <td style="height:60px;"></td>
        </tr>
        <tr>
            <td></td>
            <td style="text-align:center;text-decoration:underline;">( {{ auth()->user()->name ?? '..........................................' }} )</td>
        </tr>
    </table>
</div>



<script>
    function printLaporanYayasan() {
        const originalTitle = document.title;
        const tanggal = new Date().toISOString().slice(0, 10).replace(/-/g, '');
        document.title = 'Yayasan_Export_PDF_' + tanggal;
        window.print();
        setTimeout(() => {
            document.title = originalTitle;
        }, 400);
    }
</script>
@endsection
