@extends('layouts.app')

@section('content')
<!-- Library Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom Styles for this page -->
<style>
    /* Modern Card Look */
    .modern-card {
        border: none;
        border-radius: 1rem; /* Sudut lebih bulat */
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        background: white;
    }
    .modern-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }

    /* Table Styling */
    .table-custom th {
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        color: #64748b; /* Slate 500 */
        border-bottom: 1px solid #f1f5f9;
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
    .table-custom td {
        vertical-align: middle;
        border-bottom: 1px solid #f8fafc;
        color: #334155;
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }
    .table-custom tr:last-child td { border-bottom: none; }
    .table-custom tr:hover td { background-color: #f8fafc; }

    /* Badge Soft Colors */
    .badge-soft-success { background-color: #dcfce7; color: #166534; }
    .badge-soft-danger { background-color: #fee2e2; color: #991b1b; }
    .badge-soft-primary { background-color: #dbeafe; color: #1e40af; }

    /* Icon Background in Widgets */
    .icon-box {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
    }

    .no-print { @media print { display: none !important; } }
</style>

<!-- Header Dashboard -->
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h2 class="fw-bold text-dark mb-1">Dashboard Keuangan</h2>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">Ringkasan aktivitas keuangan hari ini, {{ date('d F Y') }}</p>
    </div>
    <button class="btn btn-primary shadow-sm px-4 rounded-pill" data-bs-toggle="modal" data-bs-target="#modalInput">
        <i class="fas fa-plus me-2"></i> Transaksi Baru
    </button>
</div>

<!-- ROW 1: 5 WIDGET STATISTIK (Modern Style) -->
<div class="row g-3 mb-4 no-print">
    <!-- Widget 1: Pemasukan -->
    <div class="col-6 col-md">
        <div class="modern-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted fw-semibold text-uppercase" style="font-size: 0.7rem;">Total Pemasukan</small>
                    <h4 class="fw-bold mt-1 text-success">{{ number_format($totalMasuk, 0, ',', '.') }}</h4>
                </div>
                <div class="icon-box bg-success bg-opacity-10 text-success">
                    <i class="fas fa-arrow-trend-down"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget 2: Pengeluaran -->
    <div class="col-6 col-md">
        <div class="modern-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted fw-semibold text-uppercase" style="font-size: 0.7rem;">Total Pengeluaran</small>
                    <h4 class="fw-bold mt-1 text-danger">{{ number_format($totalKeluar, 0, ',', '.') }}</h4>
                </div>
                <div class="icon-box bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-arrow-trend-up"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget 3: Saldo -->
    <div class="col-6 col-md">
        <div class="modern-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted fw-semibold text-uppercase" style="font-size: 0.7rem;">Saldo Kas</small>
                    <h4 class="fw-bold mt-1 text-primary">{{ number_format($saldo, 0, ',', '.') }}</h4>
                </div>
                <div class="icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="fas fa-wallet"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget 4: Trx Hari Ini -->
    <div class="col-6 col-md">
        <div class="modern-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted fw-semibold text-uppercase" style="font-size: 0.7rem;">Transaksi Hari Ini</small>
                    <h4 class="fw-bold mt-1 text-dark">{{ $transaksiHariIni }}</h4>
                </div>
                <div class="icon-box bg-info bg-opacity-10 text-info">
                    <i class="fas fa-clipboard-check"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Widget 5: SISWA BERTRANSAKSI (DISESUAIKAN) -->
    <div class="col-6 col-md">
        <div class="modern-card p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <small class="text-muted fw-semibold text-uppercase" style="font-size: 0.7rem;">Siswa Bertransaksi</small>
                    <h4 class="fw-bold mt-1 text-warning">{{ $totalSiswa }}</h4>
                </div>
                <div class="icon-box bg-warning bg-opacity-10 text-warning">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 2: GRAFIK (Line Chart Modern) + LIST MINI -->
<div class="row g-4 mb-4 no-print">
    <!-- Grafik -->
    <div class="col-lg-7">
        <div class="modern-card h-100 p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold text-dark m-0">Analitik Arus Kas</h6>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2 py-1 small">Masuk</span>
                    <span class="badge bg-danger bg-opacity-10 text-danger rounded-pill px-2 py-1 small">Keluar</span>
                </div>
            </div>
            <div style="position: relative; height: 250px; width: 100%;">
                <canvas id="keuanganChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Tabel Mini -->
    <div class="col-lg-5">
        <div class="modern-card h-100 p-0 overflow-hidden">
            <div class="p-3 border-bottom bg-light">
                <h6 class="fw-bold text-dark m-0">Transaksi Terkini</h6>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                        <tbody class="text-secondary">
                            @forelse($recentTransactions as $t)
                            <tr style="cursor: pointer;">
                                <td class="ps-3 py-3">
                                    <div class="fw-bold text-dark small">{{ $t->nama_siswa ?? 'Transaksi Umum' }}</div>
                                    <div class="small">{{ $t->kategori }}</div>
                                </td>
                                <td class="text-end pe-3 py-3">
                                    <div class="fw-bold {{ $t->jenis == 'Masuk' ? 'text-success' : 'text-danger' }}">
                                        {{ $t->jenis == 'Masuk' ? '+' : '-' }} {{ number_format($t->total_bayar, 0, ',', '.') }}
                                    </div>
                                    <div class="small" style="font-size: 0.7rem;">{{ \Carbon\Carbon::parse($t->tanggal)->format('d M') }}</div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="text-center py-3 text-muted">Belum ada transaksi.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="text-center p-2 border-top">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ROW 3: TABEL LENGKAP UTAMA -->
<div class="modern-card no-print overflow-hidden">
    <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold text-dark m-0">Riwayat Transaksi</h6>
        <span class="badge bg-white border text-secondary">{{ $transaksis->total() }} Data</span>
    </div>
    <div class="table-responsive">
        <table class="table table-custom mb-0 align-middle">
            <thead>
                <tr>
                    <th class="ps-4">Tanggal</th>
                    <th>Siswa / Keterangan</th>
                    <th>Kategori</th>
                    <th>Status</th>
                    <th class="text-end pe-4">Jumlah</th>
                    <th class="text-end pe-4 no-print">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transaksis as $t)
                <tr>
                    <td class="ps-4">
                        <span class="fw-bold text-dark">{{ $t->tanggal->format('d') }}</span>
                        <span class="text-muted small">{{ $t->tanggal->format('M Y') }}</span>
                    </td>
                    <td>
                        <div class="fw-bold text-dark">{{ $t->nama_siswa ?? '-' }}</div>
                        <div class="small text-muted">{{ $t->kelas ?? '-' }}</div>
                    </td>
                    <td><span class="fw-medium">{{ $t->kategori }}</span></td>
                    <td>
                        @if($t->jenis == 'Masuk')
                            <span class="badge badge-soft-success rounded-pill px-3 py-2">Masuk</span>
                        @else
                            <span class="badge badge-soft-danger rounded-pill px-3 py-2">Keluar</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold text-dark pe-4">Rp {{ number_format($t->total_bayar, 0, ',', '.') }}</td>
                    <td class="text-end pe-4 no-print">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="{{ route('cetak.nota', $t->id) }}" target="_blank" class="btn btn-light btn-sm rounded-circle text-primary" title="Cetak Nota">
                                <i class="fas fa-print"></i>
                            </a>
                            <form action="{{ route('transaksi.destroy', $t->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-light btn-sm rounded-circle text-danger" onclick="return confirm('Hapus data ini?')" title="Hapus">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data transaksi.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center">
        <small class="text-muted">Halaman {{ $transaksis->currentPage() }} dari {{ $transaksis->lastPage() }}</small>
        {{ $transaksis->links() }}
    </div>
</div>

<!-- MODAL INPUT (Clean Style) -->
<div class="modal fade" id="modalInput" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <form action="{{ route('transaksi.store') }}" method="POST" id="formTransaksi">
                @csrf
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-dark">Tambah Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4 pt-2">
                    <div class="row g-3 mb-2">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Jenis Transaksi</label>
                            <select name="jenis" class="form-select form-select-sm bg-light border-0" required>
                                <option value="Masuk">Pemasukan</option>
                                <option value="Keluar">Pengeluaran</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control form-control-sm bg-light border-0" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold text-muted">Kategori</label>
                            <input type="text" name="kategori" class="form-control form-control-sm bg-light border-0" placeholder="Contoh: SPP" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Nama Siswa (Opsional)</label>
                            <input type="text" name="nama_siswa" class="form-control form-control-sm" placeholder="Nama Siswa">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Kelas</label>
                            <input type="text" name="kelas" class="form-control form-control-sm" placeholder="Kelas">
                        </div>
                    </div>

                    <div class="bg-light p-3 rounded-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="small fw-bold text-dark m-0">Rincian Item</h6>
                        </div>
                        <div id="itemContainer"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-2 rounded-pill" onclick="tambahItem()">
                            <i class="fas fa-plus me-1"></i> Tambah Item
                        </button>
                    </div>

                    <div class="d-flex justify-content-end align-items-center border-top pt-3">
                        <span class="text-muted small me-3">Total Pembayaran:</span>
                        <h4 class="fw-bold text-primary m-0" id="displayTotal">Rp 0</h4>
                        <input type="hidden" name="total_hidden" id="total_hidden" value="0">
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 shadow-sm">Simpan Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SCRIPT: Modern Line Chart -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('keuanganChart').getContext('2d');

        // Buat Gradient untuk Chart
        let gradientMasuk = ctx.createLinearGradient(0, 0, 0, 400);
        gradientMasuk.addColorStop(0, 'rgba(16, 185, 129, 0.4)');
        gradientMasuk.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

        let gradientKeluar = ctx.createLinearGradient(0, 0, 0, 400);
        gradientKeluar.addColorStop(0, 'rgba(239, 68, 68, 0.4)');
        gradientKeluar.addColorStop(1, 'rgba(239, 68, 68, 0.0)');

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($months),
                datasets: [
                    {
                        label: 'Pemasukan',
                        data: @json($dataMasukChart),
                        borderColor: '#10b981',
                        backgroundColor: gradientMasuk,
                        borderWidth: 2,
                        pointBackgroundColor: '#10b981',
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Pengeluaran',
                        data: @json($dataKeluarChart),
                        borderColor: '#ef4444',
                        backgroundColor: gradientKeluar,
                        borderWidth: 2,
                        pointBackgroundColor: '#ef4444',
                        pointRadius: 3,
                        pointHoverRadius: 6,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.9)',
                        padding: 10,
                        cornerRadius: 8,
                        displayColors: true
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        border: { display: false }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false }
                    }
                }
            }
        });
    });

    // Logic Form Input
    let itemCount = 0;
    function tambahItem() {
        itemCount++;
        let html = `
            <div class="row mb-2 item-row align-items-center" id="row-${itemCount}">
                <div class="col-md-6">
                    <input type="text" name="nama_item[]" class="form-control form-control-sm bg-white border" placeholder="Nama Item (Misal: SPP)" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="harga[]" class="form-control form-control-sm bg-white border harga-input" placeholder="Harga" oninput="hitungTotal()" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="jumlah[]" class="form-control form-control-sm bg-white border jumlah-input" value="1" placeholder="Jml" oninput="hitungTotal()" required>
                </div>
                <div class="col-md-1">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100 rounded-circle" onclick="hapusItem(${itemCount})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>`;
        document.getElementById('itemContainer').insertAdjacentHTML('beforeend', html);
        hitungTotal();
    }
    function hapusItem(id) {
        document.getElementById(`row-${id}`).remove();
        hitungTotal();
    }
    function hitungTotal() {
        let hargas = document.querySelectorAll('.harga-input');
        let jumlahs = document.querySelectorAll('.jumlah-input');
        let total = 0;
        hargas.forEach((el, index) => {
            total += (parseFloat(el.value)||0) * (parseFloat(jumlahs[index].value)||0);
        });
        document.getElementById('displayTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
        document.getElementById('total_hidden').value = total;
    }
    tambahItem();
</script>
@endsection
