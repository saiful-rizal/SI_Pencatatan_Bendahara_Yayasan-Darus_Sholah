@extends('layouts.app')

@section('content')
<style>
    /* Modern Table & Card */
    .modern-card {
        border: none;
        border-radius: 1rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        background: white;
    }
    .table-custom th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        color: #64748b;
        border-bottom: 2px solid #f1f5f9;
    }
    .table-custom td {
        vertical-align: middle;
        border-bottom: 1px solid #f8fafc;
        font-size: 0.9rem;
    }

    /* Print Styling */
    @media print {
        .no-print { display: none !important; }
        .modern-card { box-shadow: none; border: 1px solid #ddd; }
    }
</style>

<!-- HEADER & TITLE -->
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="fw-bold text-dark mb-1">Laporan Pembayaran Siswa</h4>
        <small class="text-muted">Cari riwayat pembayaran berdasarkan nama, kelas, atau periode tanggal.</small>
    </div>
    @if($data->count() > 0)
        <button onclick="window.print()" class="btn btn-primary shadow-sm rounded-pill px-4">
            <i class="fas fa-print me-2"></i> Cetak Laporan
        </button>
    @endif
</div>

<!-- FORM FILTER (Card Abu-abu Terang) -->
<div class="card bg-light border-0 rounded-4 p-4 mb-4 no-print">
    <form method="GET" action="{{ route('laporan.wali') }}" class="row g-3">

        <!-- Filter Nama -->
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted">Cari Nama Siswa</label>
            <input type="text" name="nama_siswa" class="form-control" placeholder="Contoh: Ahmad" value="{{ $request->nama_siswa }}">
        </div>

        <!-- Filter Kelas -->
        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Kelas</label>
            <input type="text" name="kelas" class="form-control" placeholder="Mis: X-A" value="{{ $request->kelas }}">
        </div>

        <!-- Filter Tanggal Mulai -->
        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Dari Tanggal</label>
            <input type="date" name="tanggal_mulai" class="form-control" value="{{ $request->tanggal_mulai }}">
        </div>

        <!-- Filter Tanggal Akhir -->
        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Sampai Tanggal</label>
            <input type="date" name="tanggal_selesai" class="form-control" value="{{ $request->tanggal_selesai }}">
        </div>

        <!-- Tombol Aksi -->
        <div class="col-md-2 d-flex align-items-end gap-2">
            <button type="submit" class="btn btn-primary w-100 fw-bold">
                <i class="fas fa-search"></i> Cari
            </button>
            <a href="{{ route('laporan.wali') }}" class="btn btn-outline-secondary w-100" title="Reset Filter">
                <i class="fas fa-sync"></i>
            </a>
        </div>
    </form>
</div>

<!-- HASIL PENCARIAN -->
@if($request->hasAny(['nama_siswa', 'kelas', 'tanggal_mulai']) && $data->count() > 0)

    <div class="modern-card overflow-hidden">
        <div class="card-body p-4">

            <!-- Info Header Siswa -->
            <div class="text-center mb-4 border-bottom pb-3">
                <img src="{{ asset('image/logo_pondok.jpeg') }}" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; margin-bottom: 10px;" alt="Logo">
                <h3 class="fw-bold text-primary">{{ $data->first()->nama_siswa }}</h3>
                <span class="badge bg-info text-white px-3 py-1 rounded-pill">{{ $data->first()->kelas }}</span>
                <p class="text-muted small mt-1 mb-0">
                    @if($request->tanggal_mulai && $request->tanggal_selesai)
                        Periode: {{ $request->tanggal_mulai }} s/d {{ $request->tanggal_selesai }}
                    @else
                        Semua Riwayat Pembayaran
                    @endif
                </p>
            </div>

            <!-- Tabel Transaksi -->
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th width="15%">Tanggal</th>
                            <th width="20%">Kategori</th>
                            <th width="45%">Item Pembayaran</th>
                            <th class="text-end" width="20%">Jumlah Bayar</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $d)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $d->tanggal->format('d M') }}</div>
                                <div class="text-muted" style="font-size: 0.75rem;">{{ $d->tanggal->format('Y') }}</div>
                            </td>
                            <td>{{ $d->kategori }}</td>
                            <td>
                                @foreach($d->details as $det)
                                    <div class="mb-1">
                                        <i class="fas fa-check-circle text-success small me-1"></i>
                                        {{ $det->nama_item }}
                                        <span class="text-muted small">({{ $det->jumlah }} x {{ number_format($det->harga, 0, ',', '.') }})</span>
                                    </div>
                                @endforeach
                            </td>
                            <td class="text-end fw-bold">
                                Rp {{ number_format($d->total_bayar, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-light fw-bold">
                            <td colspan="3" class="text-end text-primary fs-5 border-top-2">TOTAL KESELURUHAN:</td>
                            <td class="text-end text-primary fs-4 border-top-2">Rp {{ number_format($data->sum('total_bayar'), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

@elseif($request->hasAny(['nama_siswa', 'kelas', 'tanggal_mulai']))
    <!-- State: Data Tidak Ditemukan -->
    <div class="alert alert-warning border-0 rounded-4 shadow-sm d-flex align-items-center" role="alert">
        <i class="fas fa-exclamation-circle fa-2x text-warning me-3"></i>
        <div>
            <h5 class="alert-heading fw-bold mb-1">Data Tidak Ditemukan</h5>
            <p class="mb-0 small text-muted">
                Tidak ada riwayat transaksi untuk siswa <strong>"{{ $request->nama_siswa ?? '...' }}"</strong>
                Kelas <strong>"{{ $request->kelas ?? '...' }}"</strong>
                pada periode tanggal tersebut.
            </p>
        </div>
    </div>
@else
    <!-- State: Awal (Belum Cari) -->
    <div class="text-center py-5 text-muted">
        <i class="fas fa-filter fa-3x mb-3 opacity-25"></i>
        <p>Masukkan kriteria filter di atas untuk melihat laporan.</p>
    </div>
@endif

@endsection
