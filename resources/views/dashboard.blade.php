@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h2 class="fw-bold text-dark">Dashboard Keuangan</h2>
    <button class="btn btn-primary btn-lg shadow-sm" data-bs-toggle="modal" data-bs-target="#modalInput">
        <i class="fas fa-plus-circle"></i> Input Transaksi Baru
    </button>
</div>

<!-- Kartu Statistik -->
<div class="row g-4 mb-5 no-print">
    <div class="col-md-4">
        <div class="card card-stat bg-primary text-white p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">Pemasukan</h6>
                    <h3 class="fw-bold mt-2">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</h3>
                </div>
                <i class="fas fa-arrow-down fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-stat bg-danger text-white p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">Pengeluaran</h6>
                    <h3 class="fw-bold mt-2">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</h3>
                </div>
                <i class="fas fa-arrow-up fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-stat bg-success text-white p-4">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-0">Saldo Akhir</h6>
                    <h3 class="fw-bold mt-2">Rp {{ number_format($saldo, 0, ',', '.') }}</h3>
                </div>
                <i class="fas fa-wallet fa-2x opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Transaksi -->
<div class="card table-card no-print">
    <div class="card-header bg-white py-3">
        <h5 class="mb-0 fw-bold text-secondary">Riwayat Transaksi</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Tanggal</th>
                        <th>Siswa</th>
                        <th>Kategori</th>
                        <th>Jenis</th>
                        <th>Total</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transaksis as $t)
                    <tr>
                        <td class="ps-4">{{ $t->tanggal->format('d-m-Y') }}</td>
                        <td>
                            <span class="fw-bold">{{ $t->nama_siswa ?? '-' }}</span>
                            <small class="d-block text-muted">{{ $t->kelas ?? '' }}</small>
                        </td>
                        <td>{{ $t->kategori }}</td>
                        <td>
                            @if($t->jenis == 'Masuk')
                                <span class="badge bg-success bg-opacity-10 text-success">Masuk</span>
                            @else
                                <span class="badge bg-danger bg-opacity-10 text-danger">Keluar</span>
                            @endif
                        </td>
                        <td class="fw-bold">Rp {{ number_format($t->total_bayar, 0, ',', '.') }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('cetak.nota', $t->id) }}" target="_blank" class="btn btn-sm btn-outline-dark me-1" title="Cetak Nota">
                                <i class="fas fa-print"></i>
                            </a>
                            <form action="{{ route('transaksi.destroy', $t->id) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus data ini?')"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada data transaksi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $transaksis->links() }}</div>
    </div>
</div>

<!-- MODAL INPUT (Dengan Tambah Item Dinamis) -->
<div class="modal fade" id="modalInput" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('transaksi.store') }}" method="POST" id="formTransaksi">
                @csrf
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Input Transaksi</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Jenis Transaksi</label>
                            <select name="jenis" class="form-select" required>
                                <option value="Masuk">Pemasukan</option>
                                <option value="Keluar">Pengeluaran</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Siswa (Opsional)</label>
                            <input type="text" name="nama_siswa" class="form-control" placeholder="Jika untuk pembayaran siswa">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kelas</label>
                            <input type="text" name="kelas" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Kategori</label>
                            <input type="text" name="kategori" class="form-control" placeholder="SPP, Buku, ATK" required>
                        </div>
                    </div>

                    <hr class="my-4">
                    <h6 class="mb-3 text-primary"><i class="fas fa-list-ul me-2"></i>Rincian Item / Opsi Harga</h6>

                    <div id="itemContainer">
                        <!-- Item akan muncul di sini -->
                    </div>

                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 w-100" onclick="tambahItem()">
                        <i class="fas fa-plus"></i> Tambah Item Lain
                    </button>

                    <div class="mt-3 text-end">
                        <h5>Total: <span id="displayTotal">Rp 0</span></h5>
                        <input type="hidden" name="total_hidden" id="total_hidden" value="0">
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Catatan Tambahan</label>
                        <textarea name="catatan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary px-4">Simpan Data</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let itemCount = 0;

    function tambahItem() {
        itemCount++;
        let html = `
            <div class="row mb-2 item-row" id="row-${itemCount}">
                <div class="col-md-5">
                    <input type="text" name="nama_item[]" class="form-control form-control-sm" placeholder="Nama Item (Mis: SPP)" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="harga[]" class="form-control form-control-sm harga-input" placeholder="Harga" oninput="hitungTotal()" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="jumlah[]" class="form-control form-control-sm jumlah-input" value="1" placeholder="Jml" oninput="hitungTotal()" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm btn-danger w-100" onclick="hapusItem(${itemCount})"><i class="fas fa-times"></i></button>
                </div>
            </div>
        `;
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
            let h = parseFloat(el.value) || 0;
            let j = parseFloat(jumlahs[index].value) || 0;
            total += (h * j);
        });

        document.getElementById('displayTotal').innerText = 'Rp ' + total.toLocaleString('id-ID');
        document.getElementById('total_hidden').value = total;
    }

    // Tambah 1 baris item otomatis saat load
    tambahItem();
</script>
@endsection
