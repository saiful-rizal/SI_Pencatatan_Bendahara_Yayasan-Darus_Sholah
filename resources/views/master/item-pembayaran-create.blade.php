@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Tambah Item Pembayaran</h4>
        <small class="text-muted">Form terpisah agar input item tidak tercampur dengan fitur cari.</small>
    </div>
    <a href="{{ route('item.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form action="{{ route('item.store') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-2"><label class="form-label small">Kode</label><input name="kode" class="form-control" value="{{ old('kode', $nextKodeItem ?? '') }}" readonly></div>
            <div class="col-md-3"><label class="form-label small">Nama Item</label><input name="nama_item" class="form-control" required></div>
            <div class="col-md-2"><label class="form-label small">Nominal</label><input name="nominal" class="form-control" data-rupiah="true" value="{{ old('nominal') }}" required></div>
            <div class="col-md-2"><label class="form-label small">Jenis</label><select name="jenis_item" class="form-select"><option value="tetap">Tetap</option><option value="fleksibel">Fleksibel</option></select></div>
            <div class="col-md-2"><label class="form-label small">Kategori Biaya</label><select name="berlaku_untuk" class="form-select"><option value="semua">Semua</option><option value="mondok">Mondok</option><option value="non_mondok">Tidak Mondok</option></select></div>
            <div class="col-md-2"><label class="form-label small">Pengelola</label><select name="pengelola" class="form-select"><option value="sekolah">Sekolah</option><option value="yayasan">Yayasan</option></select></div>
            <div class="col-md-1">
                <div class="form-check"><input type="hidden" name="aktif" value="0"><input class="form-check-input" type="checkbox" name="aktif" value="1" id="aktifItem" checked><label class="form-check-label" for="aktifItem">Aktif</label></div>
            </div>
            <div class="col-md-12 d-grid mt-2"><button class="btn btn-primary">Simpan Item</button></div>
        </form>
    </div>
</div>
@endsection
