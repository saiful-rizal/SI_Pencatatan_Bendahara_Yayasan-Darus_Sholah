@extends('layouts.app')

@section('content')
<div class="card shadow-sm border-0 mb-4 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('laporan.wali') }}" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label fw-bold">Cari Nama Siswa</label>
                <input type="text" name="nama_siswa" class="form-control" placeholder="Ketik nama siswa..." value="{{ $request->nama_siswa }}">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-search me-2"></i>Cari Data</button>
            </div>
        </form>
    </div>
</div>

@if($request->nama_siswa && $data->count() > 0)
    <div class="d-flex justify-content-between align-items-center mb-4 no-print">
        <h4 class="fw-bold">Hasil Pencarian: "{{ $request->nama_siswa }}"</h4>
        <button onclick="window.print()" class="btn btn-success"><i class="fas fa-print me-2"></i>Cetak</button>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="text-center mb-4">
                <h3>REKAP PEMBAYARAN SISWA</h3>
                <h2 class="text-primary">{{ $data->first()->nama_siswa }}</h2>
                <p>Kelas: {{ $data->first()->kelas }}</p>
            </div>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Item yang Dibayar</th>
                        <th class="text-end">Jumlah Bayar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($data as $d)
                    <tr>
                        <td>{{ $d->tanggal->format('d-m-Y') }}</td>
                        <td>{{ $d->kategori }}</td>
                        <td>
                            @foreach($d->details as $det)
                                <div>{{ $det->nama_item }}</div>
                            @endforeach
                        </td>
                        <td class="text-end fw-bold">Rp {{ number_format($d->total_bayar, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td colspan="3" class="text-end">TOTAL KESELURUHAN:</td>
                        <td class="text-end text-primary">Rp {{ number_format($data->sum('total_bayar'), 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
@elseif($request->nama_siswa)
    <div class="alert alert-warning">Data tidak ditemukan untuk nama: "{{ $request->nama_siswa }}"</div>
@endif
@endsection
