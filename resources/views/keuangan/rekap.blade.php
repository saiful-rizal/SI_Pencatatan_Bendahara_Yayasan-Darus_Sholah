@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Rekap Keuangan</h4>
        <small class="text-muted">Rekap per siswa dan keseluruhan berdasarkan periode.</small>
    </div>
    <a href="{{ route('backup.database') }}" class="btn btn-outline-dark"><i class="fas fa-database me-1"></i> Backup Database</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small">NIS / Nama</label><input name="nis" value="{{ request('nis') }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label small">Tanggal Mulai</label><input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="form-control"></div>
            <div class="col-md-3"><label class="form-label small">Tanggal Selesai</label><input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" class="form-control"></div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary">Tampilkan</button></div>
        </form>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small>Nominal Awal</small><h6>Rp {{ number_format($ringkasan['nominal_awal'], 0, ',', '.') }}</h6></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small>Potongan</small><h6>Rp {{ number_format($ringkasan['potongan'], 0, ',', '.') }}</h6></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small>Total Akhir</small><h6>Rp {{ number_format($ringkasan['total_akhir'], 0, ',', '.') }}</h6></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small>Pembayaran</small><h6>Rp {{ number_format($ringkasan['pembayaran'], 0, ',', '.') }}</h6></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small>Sisa</small><h6 class="text-danger">Rp {{ number_format($ringkasan['sisa'], 0, ',', '.') }}</h6></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>NIS</th><th>Nama</th><th>Nominal Awal</th><th>Potongan</th><th>Total Akhir</th><th>Pembayaran</th><th>Sisa</th></tr></thead>
                <tbody>
                @forelse($rekap as $row)
                    <tr>
                        <td>{{ $row['nis'] }}</td>
                        <td>{{ $row['nama'] }}</td>
                        <td>Rp {{ number_format($row['nominal_awal'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($row['potongan'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($row['total_akhir'], 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($row['pembayaran'], 0, ',', '.') }}</td>
                        <td class="text-danger fw-bold">Rp {{ number_format($row['sisa'], 0, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data rekap.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
