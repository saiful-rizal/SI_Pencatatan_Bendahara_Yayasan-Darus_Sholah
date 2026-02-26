@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h2 class="fw-bold text-dark mb-1">Riwayat Hapus Transaksi</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Data yang terhapus tersimpan sebagai backup dan bisa dipulihkan kapan saja.</p>
    </div>
</div>

<div class="card table-card no-print overflow-hidden">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Daftar Backup Transaksi</span>
        <span class="badge bg-light text-dark border">{{ $transaksis->total() }} Data</span>
    </div>

    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Dihapus Pada</th>
                    <th>Tanggal Transaksi</th>
                    <th>Siswa</th>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th class="text-end">Jumlah</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaksis as $t)
                    <tr>
                        <td>{{ optional($t->deleted_at)->format('d-m-Y H:i') }}</td>
                        <td>{{ $t->tanggal->format('d-m-Y') }}</td>
                        <td>{{ $t->nama_siswa ?? '-' }}<br><small class="text-muted">{{ $t->kelas ?? '-' }}</small></td>
                        <td>{{ $t->kategori }}</td>
                        <td>
                            @if($t->jenis === 'Masuk')
                                <span class="badge bg-success">Masuk</span>
                            @else
                                <span class="badge bg-danger">Keluar</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold">Rp {{ number_format($t->total_bayar, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <form action="{{ route('transaksi.restore', $t->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button class="btn btn-sm btn-outline-primary" onclick="return confirm('Pulihkan data transaksi ini?')">
                                    <i class="fas fa-rotate-left me-1"></i> Pulihkan
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada data transaksi terhapus.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white">{{ $transaksis->links() }}</div>
</div>
@endsection
