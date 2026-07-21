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
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border">{{ $transaksis->total() }} Data</span>
            <form action="{{ route('transaksi.restore.all') }}" method="POST" class="d-inline" id="restore-transaksi-all-form">
                @csrf
                <button type="button" class="btn btn-sm btn-outline-primary btn-delete-confirm"
                    data-form-id="restore-transaksi-all-form"
                    data-confirm-title="Konfirmasi Pulihkan"
                    data-confirm-message="Pulihkan SEMUA data transaksi di backup ini (semua halaman)?"
                    data-confirm-action-text="Ya, Pulihkan Semua"
                    data-confirm-action-class="btn btn-primary" {{ $transaksis->total() === 0 ? 'disabled' : '' }}>
                    <i class="fas fa-rotate-left me-1"></i> Pulihkan Semua
                </button>
            </form>
            <form action="{{ route('transaksi.riwayat.purge') }}" method="POST" class="d-inline" id="purge-transaksi-form">
                @csrf
                <input type="hidden" name="scope" value="transaksi">
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"
                    data-form-id="purge-transaksi-form"
                    data-confirm-title="Konfirmasi Hapus"
                    data-confirm-message="Hapus semua backup transaksi secara permanen?"
                    data-confirm-action-text="Ya, Hapus Semua"
                    data-confirm-action-class="btn btn-danger">Hapus Semua</button>
            </form>
        </div>
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
                        <td>{{ $t->tanggal->format('d-m-Y H:i') }}</td>
                        <td>{{ $t->nama_siswa ?? '-' }}<br><small class="text-muted">{{ $t->kelas ?? '-' }}</small></td>
                        <td>
                            {{ $t->kategori }}
                            @if(($t->item_count ?? 1) > 1)
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle ms-1">{{ $t->item_count }} item</span>
                            @endif
                        </td>
                        <td>
                            @if($t->jenis === 'Masuk')
                                <span class="badge bg-success">Masuk</span>
                            @else
                                <span class="badge bg-danger">Keluar</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold">Rp {{ number_format($t->total_bayar, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <form action="{{ route('transaksi.restore', $t->id) }}" method="POST" class="d-inline" id="restore-transaksi-{{ $t->id }}">
                                @csrf
                                <button type="button" class="btn btn-sm btn-outline-primary btn-delete-confirm"
                                    data-form-id="restore-transaksi-{{ $t->id }}"
                                    data-confirm-title="Konfirmasi Pulihkan"
                                    data-confirm-message="Pulihkan data transaksi ini?"
                                    data-confirm-action-text="Ya, Pulihkan"
                                    data-confirm-action-class="btn btn-primary">
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

<div class="card table-card no-print overflow-hidden mt-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Log Penghapusan Semua Menu</span>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-light text-dark border">{{ $deletionHistories->total() }} Data</span>
            <form action="{{ route('riwayat.log.restore.all') }}" method="POST" class="d-inline" id="restore-log-all-form">
                @csrf
                <button type="button" class="btn btn-sm btn-outline-primary btn-delete-confirm"
                    data-form-id="restore-log-all-form"
                    data-confirm-title="Konfirmasi Pulihkan"
                    data-confirm-message="Pulihkan SEMUA data di log penghapusan ini (semua halaman)?"
                    data-confirm-action-text="Ya, Pulihkan Semua"
                    data-confirm-action-class="btn btn-primary" {{ $deletionHistories->total() === 0 ? 'disabled' : '' }}>
                    <i class="fas fa-rotate-left me-1"></i> Pulihkan Semua
                </button>
            </form>
            <form action="{{ route('transaksi.riwayat.purge') }}" method="POST" class="d-inline" id="purge-log-form">
                @csrf
                <input type="hidden" name="scope" value="log">
                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"
                    data-form-id="purge-log-form"
                    data-confirm-title="Konfirmasi Hapus"
                    data-confirm-message="Hapus semua log penghapusan?"
                    data-confirm-action-text="Ya, Hapus Semua"
                    data-confirm-action-class="btn btn-danger">Hapus Semua</button>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Waktu Hapus</th>
                    <th>Menu</th>
                    <th>Tipe Data</th>
                    <th>Data Dihapus</th>
                    <th>Oleh</th>
                    <th class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deletionHistories as $history)
                    <tr>
                        <td>{{ optional($history->deleted_at)->format('d-m-Y H:i') }}</td>
                        <td>{{ $history->menu }}</td>
                        <td>{{ $history->entity_type }}</td>
                        <td>{{ $history->label ?? '-' }}</td>
                        <td>{{ $history->user->name ?? 'Sistem' }}</td>
                        <td class="text-end">
                            @if(in_array($history->entity_type, ['Siswa', 'Tagihan', 'ItemPembayaran', 'Transaksi'], true))
                                <form action="{{ route('riwayat.log.restore', $history->id) }}" method="POST" class="d-inline" id="restore-log-{{ $history->id }}">
                                    @csrf
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-delete-confirm"
                                        data-form-id="restore-log-{{ $history->id }}"
                                        data-confirm-title="Konfirmasi Pulihkan"
                                        data-confirm-message="Pulihkan data ini ke menu asalnya?"
                                        data-confirm-action-text="Ya, Pulihkan"
                                        data-confirm-action-class="btn btn-primary">
                                        <i class="fas fa-rotate-left me-1"></i> Pulihkan
                                    </button>
                                </form>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada log penghapusan data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white">{{ $deletionHistories->links() }}</div>
</div>
@endsection
