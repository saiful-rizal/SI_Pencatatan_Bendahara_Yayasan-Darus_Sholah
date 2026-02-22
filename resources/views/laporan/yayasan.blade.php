@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2 class="fw-bold">Laporan Ke Yayasan</h2>
    <button onclick="window.print()" class="btn btn-dark"><i class="fas fa-print me-2"></i>Cetak</button>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Pemasukan (Per Kategori)</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    @foreach($reportMasuk as $kategori => $total)
                    <tr>
                        <td>{{ $kategori }}</td>
                        <td class="text-end">Rp {{ number_format($total, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </table>
                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total Pemasukan</span>
                    <span>Rp {{ number_format($totalMasuk, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Pengeluaran (Per Kategori)</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    @foreach($reportKeluar as $kategori => $total)
                    <tr>
                        <td>{{ $kategori }}</td>
                        <td class="text-end">Rp {{ number_format($total, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </table>
                <hr>
                <div class="d-flex justify-content-between fw-bold">
                    <span>Total Pengeluaran</span>
                    <span>Rp {{ number_format($totalKeluar, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card bg-light border-0 shadow-sm">
    <div class="card-body text-center">
        <h4>LABA / RUGI BERSIH</h4>
        <h1 class="{{ $saldo >= 0 ? 'text-success' : 'text-danger' }}">
            Rp {{ number_format($saldo, 0, ',', '.') }}
        </h1>
    </div>
</div>
@endsection
