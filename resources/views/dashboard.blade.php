@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@php
    $rasioPengeluaran = $totalMasuk > 0 ? ($totalKeluar / $totalMasuk) * 100 : 0;
    $rataTransaksiTerkini = $recentTransactions->count() > 0 ? $recentTransactions->avg('total_bayar') : 0;
    $transaksiMasukTerkini = $recentTransactions->where('jenis', 'Masuk')->count();
    $persentaseMasukTerkini = $recentTransactions->count() > 0 ? ($transaksiMasukTerkini / $recentTransactions->count()) * 100 : 0;
    $jumlahMasukHalaman = $transaksis->getCollection()->where('jenis', 'Masuk')->count();
    $jumlahKeluarHalaman = $transaksis->getCollection()->where('jenis', 'Keluar')->count();
    $totalTransaksiHalaman = $jumlahMasukHalaman + $jumlahKeluarHalaman;
    $persentaseMasukHalaman = $totalTransaksiHalaman > 0 ? ($jumlahMasukHalaman / $totalTransaksiHalaman) * 100 : 0;
    $persentaseKeluarHalaman = $totalTransaksiHalaman > 0 ? ($jumlahKeluarHalaman / $totalTransaksiHalaman) * 100 : 0;
@endphp

<style>
    .dashboard-hero {
        background: linear-gradient(135deg, #ffffff 0%, #f4f8ff 100%);
        border: 1px solid #dfeaf7;
        border-radius: 14px;
        box-shadow: 0 10px 24px rgba(36, 76, 136, 0.08);
        padding: 18px 20px;
    }

    .dashboard-date-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 600;
        color: #35557e;
        background: #eaf2ff;
        border: 1px solid #c9ddfb;
        border-radius: 999px;
        padding: 7px 12px;
    }

    .dashboard-subtitle {
        color: #5e7393;
        font-size: 13px;
    }

    .dashboard-stat {
        background: #ffffff;
        border: 1px solid #dfeaf7;
        border-radius: 12px;
        box-shadow: 0 8px 18px rgba(45, 86, 146, 0.08);
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;
    }

    .dashboard-stat:hover {
        transform: translateY(-3px);
        border-color: #c8dcf8;
        box-shadow: 0 12px 24px rgba(45, 86, 146, 0.14);
    }

    .dashboard-stat .stat-label {
        font-size: 12px;
        font-weight: 600;
        color: #6c81a0;
        margin-bottom: 6px;
    }

    .dashboard-stat .stat-value {
        font-size: 20px;
        font-weight: 700;
        color: #17314d;
        line-height: 1.2;
        margin-bottom: 4px;
    }

    .dashboard-stat .stat-meta {
        font-size: 12px;
        color: #7f91ac;
        line-height: 1.35;
    }

    .stat-chip {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        margin-bottom: 10px;
    }

    .chip-income { background: #eaf7ef; color: #187a44; }
    .chip-outcome { background: #fff0f2; color: #bf2434; }
    .chip-balance { background: #eaf2ff; color: #1f59b7; }
    .chip-month { background: #eff8ff; color: #0f7da8; }
    .chip-today { background: #f2f4ff; color: #4d3dba; }
    .chip-student { background: #fff8ea; color: #b87b12; }
    .chip-ratio { background: #f2f7ff; color: #335f9d; }
    .chip-recent { background: #ecfbf7; color: #147c6a; }

    .indicator-strip {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 6px;
    }

    .indicator-strip .progress {
        flex: 1;
        height: 7px;
        background: #edf3fb;
    }

    .indicator-strip .progress-bar {
        border-radius: 999px;
    }

    .dashboard-table-title {
        color: #203a57;
        font-size: 15px;
    }

    .table-card {
        border: 1px solid #dfeaf7;
        border-radius: 14px;
        box-shadow: 0 10px 22px rgba(45, 86, 146, 0.08);
    }

    .table-card .card-header {
        border-bottom: 1px solid #e6eef9;
    }

    .table-card tbody tr {
        transition: background-color 0.2s ease;
    }

    .table-card tbody tr:hover {
        background-color: #f7fbff;
    }

    .indicator-badges {
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .indicator-badges .badge {
        padding: 7px 10px;
        font-weight: 700;
        letter-spacing: 0.15px;
    }

    .recent-kind {
        font-size: 10px;
        font-weight: 700;
        border-radius: 999px;
        padding: 3px 8px;
        margin-left: 6px;
        vertical-align: middle;
    }

    .recent-kind.masuk {
        background: #e9f9ef;
        color: #167546;
    }

    .recent-kind.keluar {
        background: #fff0f2;
        color: #bb2433;
    }

    .recent-line {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .recent-print-btn {
        flex-shrink: 0;
    }

    .table-responsive .table td,
    .table-responsive .table th {
        white-space: nowrap;
    }
</style>

<div class="dashboard-hero d-flex justify-content-between align-items-center mb-4 no-print">
    <div>
        <h2 class="fw-bold text-dark mb-1">Dashboard Keuangan</h2>
        <p class="dashboard-subtitle mb-0">Ringkasan aktivitas keuangan hari ini, {{ date('d F Y') }}</p>
    </div>
    <div class="dashboard-date-pill"><i class="fas fa-calendar-day"></i> Update Harian</div>
</div>

<div class="row g-3 mb-4 no-print">
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-stat p-3 h-100">
            <span class="stat-chip chip-income"><i class="fas fa-arrow-down"></i></span>
            <div class="stat-label">Total Pemasukan</div>
            <div class="stat-value">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</div>
            <div class="stat-meta">Akumulasi seluruh transaksi masuk</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-stat p-3 h-100">
            <span class="stat-chip chip-outcome"><i class="fas fa-arrow-up"></i></span>
            <div class="stat-label">Total Pengeluaran</div>
            <div class="stat-value">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</div>
            <div class="stat-meta">Akumulasi seluruh transaksi keluar</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-stat p-3 h-100">
            <span class="stat-chip chip-balance"><i class="fas fa-wallet"></i></span>
            <div class="stat-label">Saldo Kas</div>
            <div class="stat-value">Rp {{ number_format($saldo, 0, ',', '.') }}</div>
            <div class="stat-meta">Sisa kas aktif yang tersedia</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-stat p-3 h-100">
            <span class="stat-chip chip-month"><i class="fas fa-calendar-days"></i></span>
            <div class="stat-label">Pemasukan Bulan Ini</div>
            <div class="stat-value">Rp {{ number_format($pencapaianBulanIni, 0, ',', '.') }}</div>
            <div class="stat-meta">Total pemasukan pada bulan berjalan</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-stat p-3 h-100">
            <span class="stat-chip chip-today"><i class="fas fa-clock"></i></span>
            <div class="stat-label">Transaksi Hari Ini</div>
            <div class="stat-value">{{ number_format($transaksiHariIni, 0, ',', '.') }}</div>
            <div class="stat-meta">Aktivitas transaksi pada tanggal ini</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-stat p-3 h-100">
            <span class="stat-chip chip-student"><i class="fas fa-user-graduate"></i></span>
            <div class="stat-label">Total Siswa Aktif</div>
            <div class="stat-value">{{ number_format($totalSiswa, 0, ',', '.') }}</div>
            <div class="stat-meta">Siswa aktif yang terdaftar</div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-stat p-3 h-100">
            <span class="stat-chip chip-ratio"><i class="fas fa-percent"></i></span>
            <div class="stat-label">Rasio Pengeluaran</div>
            <div class="stat-value">{{ number_format($rasioPengeluaran, 1, ',', '.') }}%</div>
            <div class="indicator-strip">
                <div class="progress">
                    <div class="progress-bar bg-danger" role="progressbar" style="width: {{ min(100, max(0, $rasioPengeluaran)) }}%"></div>
                </div>
                <small class="stat-meta mb-0">dari pemasukan</small>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 col-xl-3">
        <div class="card dashboard-stat p-3 h-100">
            <span class="stat-chip chip-recent"><i class="fas fa-chart-line"></i></span>
            <div class="stat-label">Rata-rata 5 Transaksi Terkini</div>
            <div class="stat-value">Rp {{ number_format($rataTransaksiTerkini, 0, ',', '.') }}</div>
            <div class="stat-meta">Transaksi masuk terkini {{ number_format($persentaseMasukTerkini, 0, ',', '.') }}%</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4 no-print">
    <div class="col-lg-7">
        <div class="card table-card p-3 h-100">
            <h6 class="fw-bold mb-3 dashboard-table-title">Analitik Arus Kas (6 Bulan)</h6>
            <div style="height: 260px;"><canvas id="keuanganChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card table-card h-100">
            <div class="card-header bg-white fw-semibold dashboard-table-title">Transaksi Terkini</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <tbody>
                    @forelse($recentTransactions as $t)
                        <tr>
                            <td>
                                <div class="recent-line">
                                    <button type="button" class="btn btn-sm btn-outline-primary recent-print-btn" onclick="openPrintPopup('{{ route('cetak.nota', $t->id) }}')"><i class="fas fa-print"></i></button>
                                    {{ $t->nama_siswa ?? 'Transaksi Umum' }}
                                    <span class="recent-kind {{ $t->jenis === 'Masuk' ? 'masuk' : 'keluar' }}">{{ $t->jenis }}</span>
                                </div>
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
        <span class="fw-semibold dashboard-table-title">Riwayat Transaksi</span>
        <div class="indicator-badges">
            <span class="badge bg-success-subtle text-success border border-success-subtle">Masuk {{ number_format($persentaseMasukHalaman, 0, ',', '.') }}%</span>
            <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Keluar {{ number_format($persentaseKeluarHalaman, 0, ',', '.') }}%</span>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Total {{ number_format($totalTransaksiHalaman, 0, ',', '.') }}</span>
        </div>
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
                        <td>{{ $t->tanggal->format('d-m-Y H:i') }}</td>
                        <td>
                            <span>{{ $t->nama_siswa ?? '-' }}</span>
                            <small class="text-muted ms-1">{{ $t->kelas ?? '-' }}</small>
                        </td>
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
                            <a href="{{ route('cetak.nota', $t->id) }}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-print"></i></a>
                            <form action="{{ route('transaksi.destroy', $t->id) }}" method="POST" class="d-inline" id="delete-transaksi-{{ $t->id }}">
                                @csrf @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"
                                    data-form-id="delete-transaksi-{{ $t->id }}"
                                    data-delete-label="transaksi {{ $t->kategori }} ({{ $t->tanggal->format('d-m-Y') }})">
                                    <i class="fas fa-trash"></i>
                                </button>
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

<script>
const ctx = document.getElementById('keuanganChart');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: @json($months),
            datasets: [
                { label: 'Masuk', data: @json($dataMasukChart), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.1)', tension: .3, fill: true },
                { label: 'Keluar', data: @json($dataKeluarChart), borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,.08)', tension: .3, fill: true }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#3a4d69',
                        font: { size: 12, weight: '600' }
                    }
                }
            },
            scales: {
                x: {
                    ticks: { color: '#6e819d' },
                    grid: { color: 'rgba(161, 177, 201, 0.18)' }
                },
                y: {
                    ticks: { color: '#6e819d' },
                    grid: { color: 'rgba(161, 177, 201, 0.18)' }
                }
            }
        }
    });
}
</script>
@endsection
