@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Item Pembayaran</h4>
        <small class="text-muted">Kelola item tetap/fleksibel, kategori siswa, dan pengelola yayasan/sekolah.</small>
    </div>
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

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form action="{{ route('item.store') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-2"><label class="form-label small">Kode</label><input name="kode" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label small">Nama Item</label><input name="nama_item" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label small">Jenis</label><select name="jenis_item" class="form-select"><option value="tetap">Tetap</option><option value="fleksibel">Fleksibel</option></select></div>
            <div class="col-md-2"><label class="form-label small">Berlaku Untuk</label><select name="berlaku_untuk" class="form-select"><option value="semua">Semua</option><option value="mondok">Mondok</option><option value="non_mondok">Non Mondok</option></select></div>
            <div class="col-md-2"><label class="form-label small">Pengelola</label><select name="pengelola" class="form-select"><option value="sekolah">Sekolah</option><option value="yayasan">Yayasan</option></select></div>
            <div class="col-md-1">
                <div class="form-check"><input type="hidden" name="aktif" value="0"><input class="form-check-input" type="checkbox" name="aktif" value="1" id="aktifItem" checked><label class="form-check-label" for="aktifItem">Aktif</label></div>
            </div>
            <div class="col-md-1 d-grid"><button class="btn btn-primary">Simpan</button></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Kode</th><th>Item</th><th>Jenis</th><th>Berlaku Untuk</th><th>Pengelola</th><th>Status</th></tr></thead>
                <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>{{ $item->kode }}</td>
                        <td>{{ $item->nama_item }}</td>
                        <td>{{ $item->jenis_item }}</td>
                        <td>{{ $item->berlaku_untuk }}</td>
                        <td>{{ $item->pengelola }}</td>
                        <td>{{ $item->aktif ? 'Aktif' : 'Nonaktif' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada item pembayaran.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">{{ $items->links() }}</div>
</div>
@endsection
