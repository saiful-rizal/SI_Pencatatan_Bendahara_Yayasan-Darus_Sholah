@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Tagihan Siswa</h4>
        <small class="text-muted">Catat nominal awal, potongan, pembayaran, dan sisa tunggakan per siswa.</small>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4"><label class="form-label small">Cari NIS / Nama</label><input name="q" class="form-control" value="{{ $q ?? '' }}"></div>
            <div class="col-6 col-md-2"><label class="form-label small">Jenjang</label><select name="jenjang" class="form-select"><option value="">Semua</option><option value="10" {{ ($jenjang ?? '') === '10' ? 'selected' : '' }}>10</option><option value="11" {{ ($jenjang ?? '') === '11' ? 'selected' : '' }}>11</option><option value="12" {{ ($jenjang ?? '') === '12' ? 'selected' : '' }}>12</option></select></div>
            <div class="col-6 col-md-2"><label class="form-label small">Per Halaman</label><select name="per_page" class="form-select"><option value="10" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10</option><option value="25" {{ ($perPage ?? 10) == 25 ? 'selected' : '' }}>25</option><option value="50" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50</option><option value="100" {{ ($perPage ?? 10) == 100 ? 'selected' : '' }}>100</option></select></div>
            <div class="col-6 col-md-2 d-grid"><button class="btn btn-primary">Cari</button></div>
            <div class="col-6 col-md-2 d-grid"><a href="{{ route('tagihan.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">Buat Tagihan</h6>
        <form action="{{ route('tagihan.store') }}" method="POST" class="row g-2">
            @csrf
            <div class="col-md-3">
                <label class="form-label small">Siswa (NIS - Nama)</label>
                <select name="siswa_id" class="form-select" required>
                    <option value="">Pilih Siswa</option>
                    @foreach($siswas as $siswa)
                        <option value="{{ $siswa->id }}">{{ $siswa->nis }} - {{ $siswa->nama }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Item Pembayaran</label>
                <select name="item_pembayaran_id" class="form-select" required>
                    <option value="">Pilih Item</option>
                    @foreach($items as $item)
                        <option value="{{ $item->id }}">{{ $item->kode }} - {{ $item->nama_item }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1"><label class="form-label small">Bulan</label><input type="number" name="periode_bulan" min="1" max="12" class="form-control"></div>
            <div class="col-md-1"><label class="form-label small">Tahun</label><input type="number" name="periode_tahun" min="2000" max="2100" class="form-control"></div>
            <div class="col-md-2"><label class="form-label small">Nominal Awal</label><input type="number" name="nominal_awal" min="1" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label small">Jatuh Tempo</label><input type="date" name="jatuh_tempo" class="form-control"></div>
            <div class="col-md-12"><label class="form-label small">Catatan</label><input name="catatan" class="form-control"></div>
            <div class="col-md-2 d-grid"><button class="btn btn-dark">Simpan Tagihan</button></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th>NIS / Nama</th><th>Item</th><th>Periode</th><th>Nominal Awal</th><th>Total Potongan</th><th>Total Pembayaran</th><th>Sisa</th><th>Status</th><th>Tambah Potongan</th>
                </tr>
                </thead>
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
                        <td>Rp {{ number_format($tagihan->nominal_awal, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($potongan, 0, ',', '.') }}</td>
                        <td>Rp {{ number_format($pembayaran, 0, ',', '.') }}</td>
                        <td class="fw-bold text-danger">Rp {{ number_format($sisa, 0, ',', '.') }}</td>
                        <td><span class="badge bg-secondary">{{ $tagihan->status }}</span></td>
                        <td>
                            <form action="{{ route('tagihan.potongan.store', $tagihan->id) }}" method="POST" class="d-flex gap-1">
                                @csrf
                                <input type="date" name="tanggal_potongan" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                                <input name="keterangan" class="form-control form-control-sm" placeholder="Keterangan" required>
                                <input type="number" name="nominal_potongan" class="form-control form-control-sm" placeholder="Nominal" min="1" required>
                                <button class="btn btn-sm btn-outline-primary">+ Potongan</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-4 text-muted">Belum ada tagihan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">{{ $tagihans->links() }}</div>
</div>
@endsection
