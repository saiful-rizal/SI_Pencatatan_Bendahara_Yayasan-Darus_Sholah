@extends('layouts.app')

@section('content')
<style>
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
    .clickable-row {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    .clickable-row:hover {
        background-color: #f1f5f9;
    }
    @media print {
        .no-print { display: none !important; }
        .modern-card { box-shadow: none; border: 1px solid #ddd; }
        .clickable-row:hover { background-color: transparent; }
    }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h4 class="fw-bold text-dark mb-1">Laporan Transaksi Sekolah</h4>
        <small class="text-muted">Cari dan filter data, lalu klik baris untuk melihat detail.</small>
    </div>
    @if($data->count() > 0)
        <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm">
            <i class="fas fa-print me-2"></i> Cetak Tampilan
        </button>
    @endif
</div>

<div class="card bg-light border-0 rounded-4 p-4 mb-4 no-print">
    <form method="GET" action="{{ route('laporan.sekolah') }}" class="row g-3 align-items-end">
        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Jenis</label>
            <select name="jenis" class="form-select form-select-sm bg-white border">
                <option value="">Semua</option>
                <option value="Masuk" {{ $request->jenis == 'Masuk' ? 'selected' : '' }}">Masuk</option>
                <option value="Keluar" {{ $request->jenis == 'Keluar' ? 'selected' : '' }}">Keluar</option>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted">Kategori</label>
            <input type="text" name="kategori" class="form-control form-control-sm bg-white border" placeholder="Cth: SPP, ATK" value="{{ $request->kategori }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Dari Tgl</label>
            <input type="date" name="tanggal_mulai" class="form-control form-control-sm bg-white border" value="{{ $request->tanggal_mulai }}">
        </div>
        <div class="col-md-2">
            <label class="form-label small fw-bold text-muted">Smpai Tgl</label>
            <input type="date" name="tanggal_selesai" class="form-control form-control-sm bg-white border" value="{{ $request->tanggal_selesai }}">
        </div>
        <div class="col-md-3">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-1"></i> Cari Data</button>
                <a href="{{ route('laporan.sekolah') }}" class="btn btn-outline-secondary" title="Reset"><i class="fas fa-sync"></i></a>
            </div>
        </div>
    </form>
</div>

@if($data->count() > 0)
    <div class="modern-card overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th width="10%">Tanggal</th>
                            <th width="15%">Kategori</th>
                            <th width="40%">Siswa / Keterangan</th>
                            <th width="20%">Jenis</th>
                            <th class="text-end" width="15%">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $d)
                        <tr class="clickable-row" onclick="openDetailModal(this)"
                            data-id="{{ $d->id }}"
                            data-date="{{ $d->tanggal->format('d F Y') }}"
                            data-student="{{ $d->nama_siswa ?? '-' }}"
                            data-class="{{ $d->kelas ?? '-' }}"
                            data-cat="{{ $d->kategori }}"
                            data-note="{{ $d->catatan ?? '-' }}"
                            data-jenis="{{ $d->jenis }}"
                            data-total="{{ number_format($d->total_bayar, 0, ',', '.') }}"
                            data-items='@json($d->details)'>

                            <td>
                                <span class="fw-bold text-dark">{{ $d->tanggal->format('d M') }}</span>
                                <br><small class="text-muted">{{ $d->tanggal->format('Y') }}</small>
                            </td>
                            <td>{{ $d->kategori }}</td>
                            <td>
                                <div class="fw-bold">{{ $d->nama_siswa ?? '-' }}</div>
                                <small class="text-muted">{{ $d->catatan ?? '-' }}</small>
                            </td>
                            <td>
                                @if($d->jenis == 'Masuk')
                                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill">Masuk</span>
                                @else
                                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill">Keluar</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">Rp {{ number_format($d->total_bayar, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        <div class="bg-light p-3 text-center small text-muted no-print">
            Klik baris di atas untuk melihat rincian item pembayaran.
        </div>
    </div>
@else
    <div class="text-center py-5 no-print">
        <img src="{{ asset('image/logo_pondok.jpeg') }}" style="width: 80px; opacity: 0.5; border-radius: 50%;" alt="Logo">
        <h5 class="mt-3 text-muted">Belum ada data yang sesuai filter</h5>
        <p>Silakan ubah kriteria pencarian.</p>
    </div>
@endif

<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge bg-primary mb-1" id="modalJenis">Jenis</span>
                        <h6 class="fw-bold text-dark mb-0" id="modalKategori">Kategori</h6>
                        <small class="text-muted" id="modalDate">Tanggal</small>
                    </div>
                    <div class="text-end">
                        <h5 class="fw-bold text-primary m-0" id="modalTotal">Rp 0</h5>
                    </div>
                </div>

                <div class="bg-light p-3 rounded-3 mb-3">
                    <div class="row g-2">
                        <div class="col-6">
                            <small class="text-muted d-block">Nama Siswa</small>
                            <span class="fw-bold text-dark" id="modalStudent">-</span>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Kelas</small>
                            <span class="fw-bold text-dark" id="modalClass">-</span>
                        </div>
                    </div>
                    @if(false)
                    <div class="mt-2">
                        <small class="text-muted d-block">Catatan</small>
                        <span class="small" id="modalNote">-</span>
                    </div>
                    @endif
                </div>

                <h6 class="small fw-bold text-muted text-uppercase mb-2">Rincian Item</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Harga</th>
                                <th class="text-center">Jml</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="modalItemsBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-primary rounded-pill" onclick="printNota()">Cetak Nota</button>
            </div>
        </div>
    </div>
</div>

<script>
    let currentTransactionId = null;

    function openDetailModal(row) {
        const id = row.getAttribute('data-id');
        const date = row.getAttribute('data-date');
        const student = row.getAttribute('data-student');
        const kelas = row.getAttribute('data-class');
        const cat = row.getAttribute('data-cat');
        const note = row.getAttribute('data-note');
        const jenis = row.getAttribute('data-jenis');
        const total = row.getAttribute('data-total');
        const itemsJson = row.getAttribute('data-items');

        const items = JSON.parse(itemsJson);

        currentTransactionId = id;

        document.getElementById('modalDate').textContent = date;
        document.getElementById('modalStudent').textContent = student;
        document.getElementById('modalClass').textContent = kelas;
        document.getElementById('modalKategori').textContent = cat;
        document.getElementById('modalJenis').textContent = jenis;
        document.getElementById('modalTotal').textContent = 'Rp ' + total;

        let itemsHtml = '';
        items.forEach(item => {
            itemsHtml += `
                <tr>
                    <td>${item.nama_item}</td>
                    <td class="text-end">${number_format(item.harga)}</td>
                    <td class="text-center">${item.jumlah}</td>
                    <td class="text-end fw-bold">${number_format(item.subtotal)}</td>
                </tr>
            `;
        });
        document.getElementById('modalItemsBody').innerHTML = itemsHtml;

        const myModal = new bootstrap.Modal(document.getElementById('detailModal'));
        myModal.show();
    }

    function number_format(amount) {
        return parseFloat(amount).toLocaleString('id-ID');
    }

    function printNota() {
        if(currentTransactionId) {
            window.open('{{ route('cetak.nota', ':id') }}'.replace(':id', currentTransactionId), '_blank');
        }
    }
</script>
@endsection
