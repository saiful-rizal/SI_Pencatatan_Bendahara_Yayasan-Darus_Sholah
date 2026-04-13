@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Item Pembayaran</h4>
        <small class="text-muted">Kelola item tetap/fleksibel, kategori siswa, dan pengelola yayasan/sekolah.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahItem">+ Tambah Item</button>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4"><label class="form-label small">Cari Kode / Item</label><input name="q" class="form-control" value="{{ $search ?? '' }}"></div>
            <div class="col-md-2 d-grid"><button class="btn btn-primary">Cari</button></div>
            <div class="col-md-2 d-grid"><a href="{{ route('item.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Kode</th><th>Item</th><th>Nominal</th><th>Jenis</th><th>Berlaku Untuk</th><th>Pengelola</th><th>Status</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->kode }}</td>
                        <td>{{ $item->nama_item }}</td>
                        <td>Rp {{ number_format((float) ($item->nominal ?? 0), 0, ',', '.') }}</td>
                        <td>{{ $item->jenis_item }}</td>
                        <td>{{ $item->berlaku_untuk }}</td>
                        <td>{{ $item->pengelola }}</td>
                        <td>
                            @if($item->aktif)
                                <span class="badge bg-success-subtle text-success border">Aktif</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border">Nonaktif</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <form action="{{ route('item.toggle-aktif', $item->id) }}" method="POST" class="d-inline-flex align-items-center me-2">
                                @csrf
                                @method('PUT')
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" id="toggle-item-{{ $item->id }}" {{ $item->aktif ? 'checked' : '' }} onchange="this.form.submit()">
                                    <label class="form-check-label small" for="toggle-item-{{ $item->id }}">Aktif</label>
                                </div>
                            </form>
                            <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#modalEditItem{{ $item->id }}">Edit</button>
                            <form action="{{ route('item.destroy', $item->id) }}" method="POST" class="d-inline" id="delete-item-{{ $item->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"
                                    data-form-id="delete-item-{{ $item->id }}"
                                    data-delete-label="item pembayaran {{ $item->nama_item }}">
                                    Hapus
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4 text-muted">Belum ada item pembayaran.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">{{ $items->links() }}</div>
</div>

@foreach($items as $item)
    <div class="modal fade" id="modalEditItem{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('item.update', $item->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Item Pembayaran</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-2">
                        <div class="col-md-3"><label class="form-label small">Kode</label><input class="form-control" value="{{ $item->kode }}" readonly></div>
                        <div class="col-md-4"><label class="form-label small">Nama Item</label><input name="nama_item" class="form-control" value="{{ old('nama_item', $item->nama_item) }}" required></div>
                        <div class="col-md-5"><label class="form-label small">Nominal</label><input name="nominal" class="form-control" data-rupiah="true" value="{{ old('nominal', number_format((float) ($item->nominal ?? 0), 0, ',', '.')) }}" required></div>
                        <div class="col-md-4"><label class="form-label small">Jenis</label><select name="jenis_item" class="form-select"><option value="tetap" {{ $item->jenis_item === 'tetap' ? 'selected' : '' }}>Tetap</option><option value="fleksibel" {{ $item->jenis_item === 'fleksibel' ? 'selected' : '' }}>Fleksibel</option></select></div>
                        <div class="col-md-4"><label class="form-label small">Kategori Biaya</label><select name="berlaku_untuk" class="form-select"><option value="semua" {{ $item->berlaku_untuk === 'semua' ? 'selected' : '' }}>Semua</option><option value="mondok" {{ $item->berlaku_untuk === 'mondok' ? 'selected' : '' }}>Mondok</option><option value="non_mondok" {{ $item->berlaku_untuk === 'non_mondok' ? 'selected' : '' }}>Tidak Mondok</option></select></div>
                        <div class="col-md-4"><label class="form-label small">Pengelola</label><select name="pengelola" class="form-select"><option value="sekolah" {{ $item->pengelola === 'sekolah' ? 'selected' : '' }}>Sekolah</option><option value="yayasan" {{ $item->pengelola === 'yayasan' ? 'selected' : '' }}>Yayasan</option></select></div>
                        <div class="col-md-4 d-flex align-items-end">
                            <div class="form-check"><input type="hidden" name="aktif" value="0"><input class="form-check-input" type="checkbox" name="aktif" value="1" id="aktifEditItem{{ $item->id }}" {{ $item->aktif ? 'checked' : '' }}><label class="form-check-label" for="aktifEditItem{{ $item->id }}">Aktif</label></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-primary">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="modalTambahItem" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('item.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Item Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-2">
                    <div class="col-md-3"><label class="form-label small">Kode</label><input name="kode" class="form-control" value="{{ old('kode', $nextKodeItem ?? '') }}" readonly></div>
                    <div class="col-md-4"><label class="form-label small">Nama Item</label><input name="nama_item" class="form-control" required></div>
                    <div class="col-md-5"><label class="form-label small">Nominal</label><input name="nominal" class="form-control" data-rupiah="true" value="{{ old('nominal') }}" required></div>
                    <div class="col-md-4"><label class="form-label small">Jenis</label><select name="jenis_item" class="form-select"><option value="tetap">Tetap</option><option value="fleksibel">Fleksibel</option></select></div>
                    <div class="col-md-4"><label class="form-label small">Kategori Biaya</label><select name="berlaku_untuk" class="form-select"><option value="semua">Semua</option><option value="mondok">Mondok</option><option value="non_mondok">Tidak Mondok</option></select></div>
                    <div class="col-md-4"><label class="form-label small">Pengelola</label><select name="pengelola" class="form-select"><option value="sekolah">Sekolah</option><option value="yayasan">Yayasan</option></select></div>
                    <div class="col-md-4 d-flex align-items-end">
                        <div class="form-check"><input type="hidden" name="aktif" value="0"><input class="form-check-input" type="checkbox" name="aktif" value="1" id="aktifItem" checked><label class="form-check-label" for="aktifItem">Aktif</label></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const modalTambahItem = document.getElementById('modalTambahItem');
        const kodeInput = modalTambahItem ? modalTambahItem.querySelector('input[name="kode"]') : null;
        const nextKodeItem = @json($nextKodeItem ?? '');

        if (modalTambahItem && kodeInput && nextKodeItem) {
            modalTambahItem.addEventListener('show.bs.modal', function () {
                if (!kodeInput.value) {
                    kodeInput.value = nextKodeItem;
                }
            });
        }
    });
</script>
@endsection
