@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Tanggungan</h4>
        <small class="text-muted">Ditampilkan per siswa. Klik Tinjau Tanggungan untuk detail item yang belum lunas.</small>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-12 col-md-4"><label class="form-label small">Cari NIS / Nama</label><input name="q" class="form-control" value="{{ $q ?? '' }}"></div>
            <div class="col-6 col-md-2"><label class="form-label small">Jenjang</label><select name="jenjang" class="form-select"><option value="">Semua</option><option value="X" {{ strtoupper($jenjang ?? '') === 'X' ? 'selected' : '' }}>X</option><option value="XI" {{ strtoupper($jenjang ?? '') === 'XI' ? 'selected' : '' }}>XI</option><option value="XII" {{ strtoupper($jenjang ?? '') === 'XII' ? 'selected' : '' }}>XII</option></select></div>
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
                <thead><tr><th>NIS</th><th>Nama</th><th>Kelas Siswa</th><th>Total Belum Dibayar</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                @forelse($siswas as $siswa)
                    @php
                        $detailBelumLunas = $siswa->tagihans->map(function ($tagihan) {
                            $potongan = (float) ($tagihan->total_potongan ?? 0);
                            $bayar = (float) ($tagihan->total_pembayaran ?? 0);
                            $sisa = max(0, (float) $tagihan->nominal_awal - $potongan - $bayar);
                            $sisa = round($sisa, 2);
                            return [
                                'tagihan' => $tagihan,
                                'sisa' => $sisa,
                            ];
                        })->filter(fn ($row) => $row['sisa'] > 0)->values();

                        $sisaTanggungan = $detailBelumLunas->sum('sisa');
                    @endphp
                    <tr>
                        <td>{{ $siswa->nis }}</td>
                        <td>{{ $siswa->nama }}</td>
                        <td>{{ $siswa->kelas }}</td>
                        <td class="fw-bold {{ $sisaTanggungan > 0 ? 'text-danger' : 'text-success' }}">{{ $sisaTanggungan > 0 ? 'Rp ' . number_format($sisaTanggungan, 0, ',', '.') : 'Lunas' }}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalTinjauTanggungan{{ $siswa->id }}">Tinjau Tanggungan</button>
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

@foreach($siswas as $siswa)
    @php
        $detailBelumLunas = $siswa->tagihans->map(function ($tagihan) {
            $potongan = (float) ($tagihan->total_potongan ?? 0);
            $bayar = (float) ($tagihan->total_pembayaran ?? 0);
            $sisa = max(0, (float) $tagihan->nominal_awal - $potongan - $bayar);
            $sisa = round($sisa, 2);
            return [
                'tagihan' => $tagihan,
                'sisa' => $sisa,
            ];
        })->filter(fn ($row) => $row['sisa'] > 0)->values();

        $sisaTanggungan = $detailBelumLunas->sum('sisa');
    @endphp

    <div class="modal fade" id="modalTinjauTanggungan{{ $siswa->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tinjau Tanggungan - {{ $siswa->nis }} / {{ $siswa->nama }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Kelas Tagihan</th>
                                    <th>Periode</th>
                                    <th>Belum Dibayar</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($detailBelumLunas as $row)
                                    @php $tagihan = $row['tagihan']; @endphp
                                    <tr>
                                        <td>{{ $tagihan->itemPembayaran->nama_item ?? '-' }}</td>
                                        <td>{{ $tagihan->kelas ?? '-' }}</td>
                                        <td>{{ $tagihan->periode_label }}</td>
                                        <td class="fw-semibold {{ $row['sisa'] > 0 ? 'text-danger' : 'text-success' }}">{{ $row['sisa'] > 0 ? 'Rp ' . number_format($row['sisa'], 0, ',', '.') : 'Lunas' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">Tidak ada item tanggungan.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border rounded p-3 bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                        <div>
                            <small class="text-muted d-block">Total Keseluruhan Tanggungan</small>
                            <h5 class="mb-0 {{ $sisaTanggungan > 0 ? 'text-danger' : 'text-success' }}">{{ $sisaTanggungan > 0 ? 'Rp ' . number_format($sisaTanggungan, 0, ',', '.') : 'Lunas' }}</h5>
                        </div>
                        @if($sisaTanggungan > 0)
                            <form action="{{ route('tanggungan.bayar-semua') }}" method="POST" class="row g-1 align-items-end" id="bayar-semua-{{ $siswa->id }}">
                                @csrf
                                <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                                <div class="col-md-auto"><label class="form-label small">Tanggal</label><input type="date" name="tanggal_bayar" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required></div>
                                <div class="col-md-auto"><label class="form-label small">Metode</label><input type="text" name="metode_bayar" class="form-control form-control-sm" placeholder="Metode"></div>
                                <div class="col-md-auto d-grid"><label class="form-label small">&nbsp;</label><button type="button" class="btn btn-sm btn-success btn-delete-confirm"
                                    data-form-id="bayar-semua-{{ $siswa->id }}"
                                    data-confirm-title="Konfirmasi Pembayaran"
                                    data-confirm-message="Bayar semua tanggungan {{ $siswa->nama }}?"
                                    data-confirm-action-text="Ya, Bayar"
                                    data-confirm-action-class="btn btn-success">Bayar Sekaligus</button></div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection
