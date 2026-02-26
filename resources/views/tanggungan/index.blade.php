@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Tanggungan</h4>
        <small class="text-muted">Daftar siswa yang masih memiliki sisa tagihan dan pelunasan sekali klik.</small>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4"><label class="form-label small">Cari NIS / Nama</label><input name="q" class="form-control" value="{{ $q ?? '' }}"></div>
            <div class="col-6 col-md-2"><label class="form-label small">Jenjang</label><select name="jenjang" class="form-select"><option value="">Semua</option><option value="10" {{ ($jenjang ?? '') === '10' ? 'selected' : '' }}>10</option><option value="11" {{ ($jenjang ?? '') === '11' ? 'selected' : '' }}>11</option><option value="12" {{ ($jenjang ?? '') === '12' ? 'selected' : '' }}>12</option></select></div>
            <div class="col-6 col-md-2"><label class="form-label small">Per Halaman</label><select name="per_page" class="form-select"><option value="10" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10</option><option value="25" {{ ($perPage ?? 10) == 25 ? 'selected' : '' }}>25</option><option value="50" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50</option><option value="100" {{ ($perPage ?? 10) == 100 ? 'selected' : '' }}>100</option></select></div>
            <div class="col-6 col-md-2 d-grid"><button class="btn btn-primary">Cari</button></div>
            <div class="col-6 col-md-2 d-grid"><a href="{{ route('tanggungan.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>NIS</th><th>Nama</th><th>Kelas</th><th>Sisa Tanggungan</th><th>Pembayaran</th></tr></thead>
                <tbody>
                @forelse($siswas as $siswa)
                    @php
                        $sisaTanggungan = $siswa->tagihans->sum(function ($tagihan) {
                            $potongan = (float) ($tagihan->total_potongan ?? 0);
                            $bayar = (float) ($tagihan->total_pembayaran ?? 0);
                            return max(0, (float) $tagihan->nominal_awal - $potongan - $bayar);
                        });
                    @endphp
                    <tr>
                        <td>{{ $siswa->nis }}</td>
                        <td>{{ $siswa->nama }}</td>
                        <td>{{ $siswa->kelas }}</td>
                        <td class="fw-bold text-danger">Rp {{ number_format($sisaTanggungan, 0, ',', '.') }}</td>
                        <td>
                            <form action="{{ route('tanggungan.bayar-semua') }}" method="POST" class="row g-1">
                                @csrf
                                <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                                <div class="col-md-4"><input type="date" name="tanggal_bayar" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required></div>
                                <div class="col-md-4"><input type="text" name="metode_bayar" class="form-control form-control-sm" placeholder="Metode"></div>
                                <div class="col-md-4 d-grid"><button class="btn btn-sm btn-success" onclick="return confirm('Bayar semua tanggungan {{ $siswa->nama }}?')">Bayar Semua</button></div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted">Semua siswa sudah lunas.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">{{ $siswas->links() }}</div>
</div>
@endsection
