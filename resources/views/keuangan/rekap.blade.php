@extends('layouts.app')

@section('content')
@php
    $kelasRequest = strtoupper(trim((string) request('kelas')));
    $kelasRequest = match ($kelasRequest) {
        '10' => 'X',
        '11' => 'XI',
        '12' => 'XII',
        '13' => 'XIII',
        default => $kelasRequest,
    };
@endphp
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Rekap Keuangan</h4>
        <small class="text-muted">Ditampilkan per siswa. Klik Tinjau Keseluruhan untuk rincian per item.</small>
    </div>
    <div class="d-flex gap-2 flex-wrap justify-content-end">
        <a href="{{ route('rekap.export', request()->query()) }}" class="btn btn-success btn-sm"><i class="fas fa-file-excel me-1"></i> Export Excel</a>
        <a href="{{ route('backup.database') }}" class="btn btn-outline-dark btn-sm"><i class="fas fa-database me-1"></i> Backup Database</a>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end js-auto-filter">
            <div class="col-md-3"><label class="form-label small">NIS / Nama</label><input name="nis" value="{{ request('nis') }}" class="form-control"></div>
            <div class="col-md-2">
                <label class="form-label small">Kelas</label>
                <select name="kelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    @foreach(($kelasOptions ?? collect()) as $kelas)
                        <option value="{{ $kelas }}" {{ $kelasRequest === strtoupper((string) $kelas) ? 'selected' : '' }}>{{ $kelas }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Angkatan</label>
                <select name="angkatan" class="form-select">
                    <option value="">Semua Angkatan</option>
                    @foreach(($angkatanOptions ?? collect()) as $angkatan)
                        <option value="{{ $angkatan }}" {{ request('angkatan') === $angkatan ? 'selected' : '' }}>{{ $angkatan }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2"><label class="form-label small">Tanggal Mulai</label><input type="date" name="tanggal_mulai" value="{{ request('tanggal_mulai') }}" class="form-control"></div>
            <div class="col-md-2"><label class="form-label small">Tanggal Selesai</label><input type="date" name="tanggal_selesai" value="{{ request('tanggal_selesai') }}" class="form-control"></div>
            <div class="col-md-1">
                <label class="form-label small">Per Hal</label>
                <select name="per_page" class="form-select">
                    @foreach([10, 25, 50, 100] as $size)
                        <option value="{{ $size }}" {{ (int) request('per_page', 25) === $size ? 'selected' : '' }}>{{ $size }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1 d-grid"><button class="btn btn-primary" type="submit">Tampilkan</button></div>
        </form>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small>Nominal Awal</small><h6>Rp. {{ number_format($ringkasan['nominal_awal'], 0, ',', '.') }}</h6></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small>Potongan</small><h6>Rp. {{ number_format($ringkasan['potongan'], 0, ',', '.') }}</h6></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small>Total Akhir</small><h6>Rp. {{ number_format($ringkasan['total_akhir'], 0, ',', '.') }}</h6></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small>Pembayaran</small><h6>Rp. {{ number_format($ringkasan['pembayaran'], 0, ',', '.') }}</h6></div></div></div>
    <div class="col-md-2"><div class="card border-0 shadow-sm"><div class="card-body"><small>Sisa</small><h6 class="{{ $ringkasan['sisa'] > 0 ? 'text-danger' : 'text-success' }}">{{ $ringkasan['sisa'] > 0 ? 'Rp. ' . number_format($ringkasan['sisa'], 0, ',', '.') : 'Lunas' }}</h6></div></div></div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Angkatan</th><th>Nominal Awal</th><th>Potongan</th><th>Total Akhir</th><th>Pembayaran</th><th>Sisa</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                @forelse($rekap as $row)
                    <tr>
                        <td>{{ $row['nis'] }}</td>
                        <td>{{ $row['nama'] }}</td>
                        <td>{{ $row['kelas'] ?? '-' }}</td>
                        <td>{{ $row['angkatan'] ?? '-' }}</td>
                        <td>Rp. {{ number_format($row['nominal_awal'], 0, ',', '.') }}</td>
                        <td>Rp. {{ number_format($row['potongan'], 0, ',', '.') }}</td>
                        <td>Rp. {{ number_format($row['total_akhir'], 0, ',', '.') }}</td>
                        <td>Rp. {{ number_format($row['pembayaran'], 0, ',', '.') }}</td>
                        <td class="fw-bold {{ $row['sisa'] > 0 ? 'text-danger' : 'text-success' }}">{{ $row['sisa'] > 0 ? 'Rp. ' . number_format($row['sisa'], 0, ',', '.') : 'LUNAS' }}</td>
                        <td class="text-center"><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDetailRekap{{ $row['id'] }}">Tinjau Keseluruhan</button></td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center py-4 text-muted">Belum ada data rekap.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mt-3">
    {{ $rekapPaginator->links() }}
</div>

@foreach($rekap as $row)
    <div class="modal fade" id="modalDetailRekap{{ $row['id'] }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tinjau Keseluruhan - {{ $row['nis'] }} / {{ $row['nama'] }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Kelas Tagihan</th>
                                    <th>Periode</th>
                                    <th>Nominal Awal</th>
                                    <th>Potongan</th>
                                    <th>Total Akhir</th>
                                    <th>Pembayaran</th>
                                    <th>Sisa</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($row['detail'] ?? collect()) as $detail)
                                    <tr>
                                        <td>{{ $detail['item'] }}</td>
                                        <td>{{ $detail['kelas'] ?? '-' }}</td>
                                        <td>{{ $detail['periode'] }}</td>
                                        <td>Rp. {{ number_format($detail['nominal_awal'], 0, ',', '.') }}</td>
                                        <td>
                                            Rp. {{ number_format($detail['potongan'], 0, ',', '.') }}
                                            @if(($detail['potongan_keterangan'] ?? '-') !== '-')
                                                <div><small class="text-muted">{{ $detail['potongan_keterangan'] }}</small></div>
                                            @endif
                                        </td>
                                        <td>Rp. {{ number_format($detail['total_akhir'], 0, ',', '.') }}</td>
                                        <td>Rp. {{ number_format($detail['pembayaran'], 0, ',', '.') }}</td>
                                        <td class="fw-semibold {{ $detail['sisa'] > 0 ? 'text-danger' : 'text-success' }}">{{ $detail['sisa'] > 0 ? 'Rp. ' . number_format($detail['sisa'], 0, ',', '.') : 'LUNAS' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="8" class="text-center text-muted">Tidak ada detail item.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
