@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h2 class="fw-bold text-dark mb-1">Dashboard Keuangan</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Ringkasan aktivitas keuangan hari ini, {{ date('d F Y') }}</p>
    </div>
    <button class="btn btn-primary shadow-sm px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalInput">
        <i class="fas fa-plus me-2"></i> Transaksi Baru
    </button>
</div>

<div class="alert alert-info no-print d-flex align-items-center gap-2">
    <i class="fas fa-circle-info"></i>
    <span>Mulai dari tombol <strong>Transaksi Baru</strong> untuk mencatat pemasukan/pengeluaran harian.</span>
</div>

<div class="row g-3 mb-4 no-print">
    <div class="col-6 col-md"><div class="card card-stat p-3"><small class="text-muted">Total Pemasukan</small><h4 class="text-success fw-bold mb-0">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</h4></div></div>
    <div class="col-6 col-md"><div class="card card-stat p-3"><small class="text-muted">Total Pengeluaran</small><h4 class="text-danger fw-bold mb-0">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</h4></div></div>
    <div class="col-6 col-md"><div class="card card-stat p-3"><small class="text-muted">Saldo Kas</small><h4 class="text-primary fw-bold mb-0">Rp {{ number_format($saldo, 0, ',', '.') }}</h4></div></div>
    <div class="col-6 col-md"><div class="card card-stat p-3"><small class="text-muted">Transaksi Hari Ini</small><h4 class="fw-bold mb-0">{{ $transaksiHariIni }}</h4></div></div>
    <div class="col-6 col-md"><div class="card card-stat p-3"><small class="text-muted">Total Siswa Aktif</small><h4 class="text-warning fw-bold mb-0">{{ $totalSiswa }}</h4></div></div>
    <div class="col-6 col-md"><div class="card card-stat p-3"><small class="text-muted">Pemasukan Bulan Ini</small><h6 class="text-success fw-bold mb-0">Rp {{ number_format($pencapaianBulanIni, 0, ',', '.') }}</h6></div></div>
</div>

<div class="row g-4 mb-4 no-print">
    <div class="col-lg-7">
        <div class="card table-card p-3 h-100">
            <h6 class="fw-bold mb-3">Analitik Arus Kas (6 Bulan)</h6>
            <div style="height: 260px;"><canvas id="keuanganChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card table-card h-100">
            <div class="card-header bg-white fw-semibold">Transaksi Terkini</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                    @forelse($recentTransactions as $t)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ $t->nama_siswa ?? 'Transaksi Umum' }}</div>
                                <small class="text-muted">{{ $t->kategori }}</small>
                            </td>
                            <td class="text-end {{ $t->jenis == 'Masuk' ? 'text-success' : 'text-danger' }} fw-bold">
                                {{ $t->jenis == 'Masuk' ? '+' : '-' }} Rp {{ number_format($t->total_bayar, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr><td class="text-center py-3 text-muted">Belum ada transaksi.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card table-card no-print overflow-hidden">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Riwayat Transaksi</span>
        <span class="badge bg-light text-dark border">{{ $transaksis->total() }} Data</span>
    </div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Tanggal</th>
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
                            <a href="{{ route('cetak.nota', $t->id) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-print"></i></a>
                            <form action="{{ route('transaksi.destroy', $t->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data ini?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $transaksis->links() }}</div>
</div>

<div class="modal fade" id="modalInput" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('transaksi.store') }}" method="POST" id="formTransaksi">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Tambah Transaksi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4"><label class="form-label small">Jenis</label><select name="jenis" class="form-select" required><option value="Masuk">Masuk</option><option value="Keluar">Keluar</option></select></div>
                        <div class="col-md-4"><label class="form-label small">Tanggal</label><input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                        <div class="col-md-4"><label class="form-label small">Kategori</label><input name="kategori" class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small">Nama Siswa (opsional)</label><input name="nama_siswa" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label small">Kelas (opsional)</label><input name="kelas" class="form-control"></div>
                    </div>
                    <div id="itemContainer"></div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahItem()">+ Tambah Item</button>
                    <div class="mt-3">
                        <label class="form-label small">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
            </form>
        </div>
    </div>
</div>

<script>
let idx = 0;
function tambahItem() {
    const container = document.getElementById('itemContainer');
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2';
    row.innerHTML = `
        <div class="col-md-5"><input name="nama_item[]" class="form-control" placeholder="Nama Item" required></div>
        <div class="col-md-3"><input type="number" min="0" name="harga[]" class="form-control" placeholder="Harga" required></div>
        <div class="col-md-2"><input type="number" min="1" name="jumlah[]" class="form-control" placeholder="Jumlah" required></div>
        <div class="col-md-2 d-grid"><button type="button" class="btn btn-outline-danger" onclick="this.closest('.row').remove()">Hapus</button></div>
    `;
    container.appendChild(row);
    idx++;
}

if (document.getElementById('itemContainer').children.length === 0) {
    tambahItem();
}

const ctx = document.getElementById('keuanganChart');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($months),
            datasets: [
                { label: 'Masuk', data: @json($dataMasukChart), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.1)', tension: .3, fill: true },
                { label: 'Keluar', data: @json($dataKeluarChart), borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,.1)', tension: .3, fill: true }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
}
</script>
@endsection
