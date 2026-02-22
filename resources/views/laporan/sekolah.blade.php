@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2 class="fw-bold">Laporan Ke Sekolah</h2>
    <button onclick="window.print()" class="btn btn-dark"><i class="fas fa-print me-2"></i>Cetak Laporan</button>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <div class="text-center mb-5">
            <h3>LAPORAN KEUANGAN SEKOLAH LENGKAP</h3>
            <p class="text-muted">Data Seluruh Transaksi Masuk & Keluar</p>
        </div>

        <table class="table table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Siswa / Keterangan</th>
                    <th>Rincian Item</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $d)
                <tr>
                    <td>{{ $d->tanggal->format('d-m-Y') }}</td>
                    <td>
                        <span class="badge {{ $d->jenis == 'Masuk' ? 'bg-success' : 'bg-danger' }}">
                            {{ $d->jenis }}
                        </span>
                        {{ $d->kategori }}
                    </td>
                    <td>
                        @if($d->nama_siswa)
                            <strong>{{ $d->nama_siswa }}</strong> <br>
                            <small class="text-muted">{{ $d->kelas }}</small>
                        @else
                            {{ $d->catatan ?? '-' }}
                        @endif
                    </td>
                    <td>
                        <ul class="list-unstyled mb-0 small">
                            @foreach($d->details as $det)
                                <li>- {{ $det->nama_item }} ({{ $det->jumlah }} x {{ number_format($det->harga) }})</li>
                            @endforeach
                        </ul>
                    </td>
                    <td class="text-end fw-bold">Rp {{ number_format($d->total_bayar, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="4" class="text-end">GRAND TOTAL SALDO:</td>
                    <td class="text-end">
                        Rp {{ number_format($data->where('jenis', 'Masuk')->sum('total_bayar') - $data->where('jenis', 'Keluar')->sum('total_bayar'), 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>

        <div class="mt-4 text-center no-print">
            <a href="{{ route('home') }}" class="btn btn-secondary">Kembali Dashboard</a>
        </div>
    </div>
</div>
@endsection
