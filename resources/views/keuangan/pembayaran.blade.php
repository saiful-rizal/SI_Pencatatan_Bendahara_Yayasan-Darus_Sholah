@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Transaksi Pembayaran</h4>
        <small class="text-muted">Pembayaran terhubung ke NIS siswa dan periode tagihan.</small>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4"><label class="form-label small">Cari NIS / Nama</label><input name="q" class="form-control" value="{{ $q ?? '' }}"></div>
            <div class="col-6 col-md-2"><label class="form-label small">Jenjang</label><select name="jenjang" class="form-select"><option value="">Semua</option><option value="10" {{ ($jenjang ?? '') === '10' ? 'selected' : '' }}>10</option><option value="11" {{ ($jenjang ?? '') === '11' ? 'selected' : '' }}>11</option><option value="12" {{ ($jenjang ?? '') === '12' ? 'selected' : '' }}>12</option></select></div>
            <div class="col-6 col-md-2"><label class="form-label small">Per Halaman</label><select name="per_page" class="form-select"><option value="10" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10</option><option value="25" {{ ($perPage ?? 10) == 25 ? 'selected' : '' }}>25</option><option value="50" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50</option><option value="100" {{ ($perPage ?? 10) == 100 ? 'selected' : '' }}>100</option></select></div>
            <div class="col-6 col-md-2 d-grid"><button class="btn btn-primary">Cari</button></div>
            <div class="col-6 col-md-2 d-grid"><a href="{{ route('pembayaran.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>Siswa</th><th>Item</th><th>Periode</th><th>Total Akhir</th><th>Sudah Dibayar</th><th>Sisa</th><th>Aksi Pembayaran</th></tr></thead>
                <tbody>
                @forelse($tagihans as $tagihan)
                    @php
                        $potongan = (float) ($tagihan->total_potongan ?? 0);
                        $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
                        $totalAkhir = max(0, (float) $tagihan->nominal_awal - $potongan);
                        $sisa = max(0, $totalAkhir - $pembayaran);
                    @endphp
                    <tr>
                        <td>{{ $tagihan->siswa->nis }}<br><small class="text-muted">{{ $tagihan->siswa->nama }}</small></td>
                        <td>{{ $tagihan->itemPembayaran->nama_item }}</td>
                        <td>{{ $tagihan->periode_bulan ? $tagihan->periode_bulan . '/' . $tagihan->periode_tahun : '-' }}</td>
                        <td>Rp {{ number_format($totalAkhir, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($pembayaran, 0, ',', '.') }}</td>
                        <td class="fw-bold {{ $sisa > 0 ? 'text-danger' : 'text-success' }}">Rp {{ number_format($sisa, 0, ',', '.') }}</td>
                        <td>
                            @if($sisa > 0)
                                <form action="{{ route('pembayaran.store', $tagihan->id) }}" method="POST" class="row g-1">
                                    @csrf
                                    <div class="col-md-4"><input type="date" name="tanggal_bayar" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required></div>
                                    <div class="col-md-3"><input type="number" name="nominal_bayar" class="form-control form-control-sm" min="1" max="{{ (int) $sisa }}" placeholder="Nominal" required></div>
                                    <div class="col-md-3"><input type="text" name="metode_bayar" class="form-control form-control-sm" placeholder="Metode"></div>
                                    <div class="col-md-2 d-grid"><button class="btn btn-sm btn-success">Bayar</button></div>
                                </form>
                            @else
                                <span class="badge bg-success">Lunas</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data tagihan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">{{ $tagihans->links() }}</div>
</div>
@endsection
