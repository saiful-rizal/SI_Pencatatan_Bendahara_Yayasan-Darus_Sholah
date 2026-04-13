@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="fw-bold mb-1">Pengeluaran</h4>
        <small class="text-muted">Daftar pengeluaran yayasan lengkap dengan detail item biaya per transaksi.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPengeluaran">+ Tambah Pengeluaran</button>
</div>

<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('pengeluaran.index') }}" class="row g-2 align-items-end js-auto-filter">
            <div class="col-md-4">
                <label class="form-label small">Cari Penerima / Catatan</label>
                <input type="text" name="q" class="form-control" value="{{ $request->q }}" placeholder="Contoh: toko, vendor, listrik">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Kategori Pengeluaran</label>
                <input type="text" name="kategori" class="form-control" value="{{ $request->kategori }}" placeholder="Operasional, Sosial, dll">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" class="form-control" value="{{ $request->tanggal_mulai }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" class="form-control" value="{{ $request->tanggal_selesai }}">
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-primary">Cari</button>
            </div>
            <div class="col-md-1 d-grid">
                <a href="{{ route('pengeluaran.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted d-block">Total Pengeluaran (hasil filter)</small>
                <h4 class="text-danger mb-0">Rp. {{ number_format($totalPengeluaran, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted d-block">Jumlah Transaksi</small>
                <h4 class="mb-0">{{ $pengeluarans->total() }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white fw-semibold">Data Pengeluaran Detail</div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Penerima/Pihak Dibayar</th>
                    <th>Keterangan</th>
                    <th class="text-end">Nominal</th>
                    <th class="text-center">Detail Item</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengeluarans as $row)
                    <tr>
                        <td>{{ $row->tanggal->format('d-m-Y') }}</td>
                        <td>{{ $row->kategori }}</td>
                        <td>{{ $row->nama_siswa ?: '-' }}</td>
                        <td>{{ $row->catatan ?: '-' }}</td>
                        <td class="text-end fw-bold text-danger">Rp. {{ number_format($row->total_bayar, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDetailPengeluaran{{ $row->id }}">Lihat Detail</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data pengeluaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $pengeluarans->links() }}</div>
</div>

@foreach($pengeluarans as $row)
    <div class="modal fade" id="modalDetailPengeluaran{{ $row->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6"><small class="text-muted d-block">Tanggal</small><strong>{{ $row->tanggal->format('d F Y') }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Kategori</small><strong>{{ $row->kategori }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Penerima/Pihak Dibayar</small><strong>{{ $row->nama_siswa ?: '-' }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Keterangan</small><strong>{{ $row->catatan ?: '-' }}</strong></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nama Item</th>
                                    <th class="text-end">Harga per Item</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($row->details as $detail)
                                    <tr>
                                        <td>{{ $detail->nama_item }}</td>
                                        <td class="text-end">Rp. {{ number_format($detail->harga, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ $detail->jumlah }}</td>
                                        <td class="text-end">Rp. {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">Detail item tidak tersedia.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">TOTAL</th>
                                    <th class="text-end text-danger">Rp. {{ number_format($row->total_bayar, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="modalTambahPengeluaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('pengeluaran.store') }}" id="formPengeluaran">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4"><label class="form-label small">Tanggal</label><input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                        <div class="col-md-4"><label class="form-label small">Kategori Pengeluaran</label><input type="text" name="kategori" class="form-control" placeholder="Operasional/Kegiatan Sosial" required></div>
                        <div class="col-md-4"><label class="form-label small">Penerima/Pihak Dibayar</label><input type="text" name="nama_siswa" class="form-control" placeholder="Nama toko/vendor/pihak"></div>
                        <div class="col-md-12"><label class="form-label small">Keterangan Penggunaan Dana</label><textarea name="catatan" class="form-control" rows="2" placeholder="Keterangan atau tujuan pengeluaran"></textarea></div>
                    </div>

                    <div id="pengeluaranItemContainer"></div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahItemPengeluaran()">+ Tambah Item</button>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan Pengeluaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function tambahItemPengeluaran() {
        const container = document.getElementById('pengeluaranItemContainer');
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2';
        row.innerHTML = `
            <div class="col-md-5"><input name="nama_item[]" class="form-control" placeholder="Nama Item" required></div>
            <div class="col-md-3"><input type="text" inputmode="numeric" name="harga[]" data-rupiah="true" class="form-control" placeholder="Harga per item (contoh: 100.000)" required></div>
            <div class="col-md-2"><input type="number" min="1" name="jumlah[]" class="form-control" placeholder="Jumlah" required></div>
            <div class="col-md-2 d-grid"><button type="button" class="btn btn-outline-danger" onclick="this.closest('.row').remove()">Hapus</button></div>
        `;
        container.appendChild(row);
    }

    if (document.getElementById('pengeluaranItemContainer').children.length === 0) {
        tambahItemPengeluaran();
    }
</script>
@endsection
