@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <div>
        <h4 class="fw-bold mb-1">Pengeluaran</h4>
        <small class="text-muted">Daftar pengeluaran yayasan lengkap dengan detail item biaya per transaksi.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahPengeluaran">+ Tambah Pengeluaran</button>
</div>

<div class="card border-0 shadow-sm mb-3 no-print">
    <div class="card-body">
        <form method="GET" action="{{ route('pengeluaran.index') }}" class="row g-2 align-items-end js-auto-filter" data-auto-submit>
            <div class="col-md-4">
                <label class="form-label small">Cari Penerima / Catatan</label>
                <input type="text" name="q" class="form-control" value="{{ $request->q }}" placeholder="Contoh: toko, vendor, listrik">
            </div>
            <div class="col-md-3">
                <label class="form-label small">Kategori Pengeluaran</label>
                <input type="text" name="kategori" class="form-control" value="{{ $request->kategori }}" placeholder="Operasional, Sosial, dll">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Tanggal Mulai</label>
                <input type="date" name="tanggal_mulai" class="form-control" value="{{ $request->tanggal_mulai }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Tanggal Selesai</label>
                <input type="date" name="tanggal_selesai" class="form-control" value="{{ $request->tanggal_selesai }}">
            </div>
            <div class="col-md-1 d-grid">
                <button type="submit" class="btn btn-primary">Cari</button>
            </div>
            <div class="col-md-1 d-grid">
                <a href="{{ route('pengeluaran.index') }}" class="btn btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted d-block">Total Pengeluaran (hasil filter)</small>
                <h4 class="text-danger mb-0">Rp. {{ number_format($totalPengeluaran, 0, ',', '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <small class="text-muted d-block">Jumlah Transaksi</small>
                <h4 class="mb-0">{{ $pengeluarans->total() }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white fw-semibold">Data Pengeluaran Detail</div>
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Penerima/Pihak Dibayar</th>
                    <th>Keterangan</th>
                    <th class="text-end">Nominal</th>
                    <th class="text-center">Detail Item</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pengeluarans as $row)
                    <tr>
                        <td>{{ $row->tanggal->format('d-m-Y') }}</td>
                        <td>{{ $row->kategori }}</td>
                        <td>{{ $row->nama_siswa ?: '-' }}</td>
                        <td>{{ $row->catatan ?: '-' }}</td>
                        <td class="text-end fw-bold text-danger">Rp. {{ number_format($row->total_bayar, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalDetailPengeluaran{{ $row->id }}">Lihat Detail</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Belum ada data pengeluaran.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer bg-white">{{ $pengeluarans->links() }}</div>
</div>

@foreach($pengeluarans as $row)
    <div class="modal fade" id="modalDetailPengeluaran{{ $row->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detail Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6"><small class="text-muted d-block">Tanggal</small><strong>{{ $row->tanggal->format('d F Y') }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Kategori</small><strong>{{ $row->kategori }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Penerima/Pihak Dibayar</small><strong>{{ $row->nama_siswa ?: '-' }}</strong></div>
                        <div class="col-md-6"><small class="text-muted d-block">Keterangan</small><strong>{{ $row->catatan ?: '-' }}</strong></div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Nama Item</th>
                                    <th class="text-end">Harga per Item</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($row->details as $detail)
                                    <tr>
                                        <td>{{ $detail->nama_item }}</td>
                                        <td class="text-end">Rp. {{ number_format($detail->harga, 0, ',', '.') }}</td>
                                        <td class="text-center">{{ $detail->jumlah }}</td>
                                        <td class="text-end">Rp. {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">Detail item tidak tersedia.</td></tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">TOTAL</th>
                                    <th class="text-end text-danger">Rp. {{ number_format($row->total_bayar, 0, ',', '.') }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<div class="modal fade" id="modalTambahPengeluaran" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('pengeluaran.store') }}" id="formPengeluaran">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-3"><label class="form-label small">Tanggal</label><input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required></div>
                        <div class="col-md-5">
                            <label class="form-label small">Sumber Dana (Item Pembayaran)</label>
                            <div class="dropdown w-100" data-bs-auto-close="outside">
                                <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start py-2 px-3" type="button" id="sumberDanaDropdownButton" data-bs-toggle="dropdown" aria-expanded="false" style="white-space: normal;">
                                    Pilih Sumber Dana
                                </button>
                                <div class="dropdown-menu p-2 shadow-sm border-0" id="sumberDanaDropdownMenu" aria-labelledby="sumberDanaDropdownButton" style="max-height: 360px; overflow-y: auto; width: max(100%, 480px);">
                                    <div class="dropdown-item rounded px-2 py-2 mb-1 d-flex align-items-center border-bottom" style="gap: 8px;">
                                        <input class="form-check-input my-0 flex-shrink-0" type="checkbox" id="checkAllSumberDana" style="margin-top: 0;">
                                        <label class="small fw-semibold w-100 mb-0" for="checkAllSumberDana">Pilih Semua</label>
                                    </div>
                                    @foreach($itemPembayaranList as $item)
                                    <div class="dropdown-item rounded px-2 py-1 mb-1 sumber-dana-option d-flex align-items-center" style="gap: 8px;">
                                        <input class="form-check-input sumber-dana-checkbox my-0 flex-shrink-0" type="checkbox" name="kategori[]" value="{{ $item->kode }} - {{ $item->nama_item }}" data-nominal="{{ (float) ($item->nominal ?? 0) }}" data-label="{{ $item->kode }} - {{ $item->nama_item }} - Rp {{ number_format((float) ($item->nominal ?? 0), 0, ',', '.') }}" id="sumberDanaItem{{ $item->id }}" style="margin-top: 0;">
                                        <label class="small w-100 mb-0 d-flex justify-content-between align-items-center" style="gap: 8px;" for="sumberDanaItem{{ $item->id }}">
                                            <span class="fw-semibold">{{ $item->kode }} - {{ $item->nama_item }}</span>
                                            <span class="text-muted text-nowrap">Rp {{ number_format((float) ($item->nominal ?? 0), 0, ',', '.') }}</span>
                                        </label>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4"><label class="form-label small">Penerima/Pihak Dibayar</label><input type="text" name="nama_siswa" class="form-control" placeholder="Nama toko/vendor/pihak"></div>
                        <div class="col-md-12">
                            <div id="sumberDanaRingkasan" class="bg-light rounded p-3 border small d-none">
                                <div class="fw-semibold mb-1">Sumber Dana Terpilih</div>
                                <div id="daftarSumberDana"></div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between">
                                    <span>Total Nominal Sumber Dana</span>
                                    <span id="totalSumberDana" class="fw-bold text-primary">Rp 0</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12"><label class="form-label small">Keterangan Penggunaan Dana</label><textarea name="catatan" class="form-control" rows="2" placeholder="Keterangan atau tujuan pengeluaran"></textarea></div>
                    </div>

                    <div id="pengeluaranItemContainer"></div>
                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="tambahItemPengeluaran()">+ Tambah Item</button>
                    <div id="sumberDanaWarning" class="alert alert-danger mt-2 mb-0 d-none small py-2">Total item pengeluaran melebihi total nominal sumber dana. Silakan sesuaikan jumlah item atau pilih sumber dana lain.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan Pengeluaran</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function tambahItemPengeluaran() {
        const container = document.getElementById('pengeluaranItemContainer');
        const row = document.createElement('div');
        row.className = 'row g-2 mb-2';
        row.innerHTML = `
            <div class="col-md-5"><input name="nama_item[]" class="form-control" placeholder="Nama Item" required></div>
            <div class="col-md-3"><input type="text" inputmode="numeric" name="harga[]" data-rupiah="true" class="form-control harga-input" placeholder="Harga per item (contoh: 100.000)" required></div>
            <div class="col-md-2"><input type="number" min="1" name="jumlah[]" class="form-control jumlah-input" placeholder="Jumlah" required></div>
            <div class="col-md-2 d-grid"><button type="button" class="btn btn-outline-danger" onclick="this.closest('.row').remove(); cekSaldoSumberDana();">Hapus</button></div>
        `;
        container.appendChild(row);
        row.querySelector('.harga-input').addEventListener('input', cekSaldoSumberDana);
        row.querySelector('.jumlah-input').addEventListener('input', cekSaldoSumberDana);
    }

    if (document.getElementById('pengeluaranItemContainer').children.length === 0) {
        tambahItemPengeluaran();
    }

    function getTotalSumberDana() {
        const checkboxes = document.querySelectorAll('.sumber-dana-checkbox:checked');
        let total = 0;
        checkboxes.forEach(function (cb) {
            total += parseFloat(cb.getAttribute('data-nominal')) || 0;
        });
        return total;
    }

    function getTotalItemPengeluaran() {
        const hargaInputs = document.querySelectorAll('.harga-input');
        const jumlahInputs = document.querySelectorAll('.jumlah-input');
        let total = 0;
        for (let i = 0; i < hargaInputs.length; i++) {
            const hargaRaw = (hargaInputs[i].value || '').replace(/[^0-9]/g, '');
            const harga = parseInt(hargaRaw) || 0;
            const jumlah = parseInt(jumlahInputs[i]?.value) || 0;
            total += harga * jumlah;
        }
        return total;
    }

    function cekSaldoSumberDana() {
        const totalSumberDana = getTotalSumberDana();
        const totalItem = getTotalItemPengeluaran();
        const elWarning = document.getElementById('sumberDanaWarning');
        const elSubmit = document.querySelector('#formPengeluaran button[type="submit"]');

        if (totalSumberDana > 0 && totalItem > totalSumberDana) {
            elWarning.classList.remove('d-none');
            if (elSubmit) elSubmit.disabled = true;
        } else {
            elWarning.classList.add('d-none');
            if (elSubmit) elSubmit.disabled = false;
        }
    }

    document.getElementById('formPengeluaran').addEventListener('submit', function (e) {
        if (getTotalSumberDana() > 0 && getTotalItemPengeluaran() > getTotalSumberDana()) {
            e.preventDefault();
            document.getElementById('sumberDanaWarning').classList.remove('d-none');
        }
    });

    // Reset form saat modal ditutup
    document.getElementById('modalTambahPengeluaran').addEventListener('hidden.bs.modal', function () {
        document.getElementById('formPengeluaran').reset();
        document.getElementById('pengeluaranItemContainer').innerHTML = '';
        tambahItemPengeluaran();
        document.querySelectorAll('.sumber-dana-checkbox').forEach(function (cb) { cb.checked = false; });
        document.getElementById('sumberDanaRingkasan').classList.add('d-none');
        document.getElementById('sumberDanaWarning').classList.add('d-none');
        document.querySelector('#formPengeluaran button[type="submit"]').disabled = false;
        document.getElementById('sumberDanaDropdownButton').textContent = 'Pilih Sumber Dana';
        document.getElementById('checkAllSumberDana').checked = false;
        document.getElementById('checkAllSumberDana').indeterminate = false;
    });

    // Sumber Dana dropdown
    document.addEventListener('DOMContentLoaded', function () {
        const menu = document.getElementById('sumberDanaDropdownMenu');
        if (!menu) return;

        menu.addEventListener('click', function (event) {
            event.stopPropagation();
        });

        const checkboxes = Array.from(document.querySelectorAll('.sumber-dana-checkbox'));
        const button = document.getElementById('sumberDanaDropdownButton');

        function hitungTotalSumberDana() {
            const items = checkboxes.filter(cb => cb.checked);
            const elRingkasan = document.getElementById('sumberDanaRingkasan');
            const elDaftar = document.getElementById('daftarSumberDana');
            const elTotal = document.getElementById('totalSumberDana');

            if (items.length === 0) {
                elRingkasan.classList.add('d-none');
                return;
            }

            let total = 0;
            let html = '';
            items.forEach(function (cb) {
                const label = cb.getAttribute('data-label') || cb.value;
                const nominal = parseFloat(cb.getAttribute('data-nominal')) || 0;
                total += nominal;
                html += '<div class="d-flex justify-content-between"><span>' + label + '</span><span>Rp ' + Number(nominal).toLocaleString('id-ID') + '</span></div>';
            });
            elDaftar.innerHTML = html;
            elTotal.textContent = 'Rp ' + Number(total).toLocaleString('id-ID');
            elRingkasan.classList.remove('d-none');
            cekSaldoSumberDana();
        }

        function updateButtonLabel() {
            const items = checkboxes.filter(cb => cb.checked);
            button.textContent = items.length > 0 ? items.length + ' sumber dana dipilih' : 'Pilih Sumber Dana';

            const all = document.getElementById('checkAllSumberDana');
            if (all) {
                all.checked = checkboxes.length > 0 && checkboxes.every(cb => cb.checked);
                all.indeterminate = items.length > 0 && items.length < checkboxes.length;
            }
            hitungTotalSumberDana();
        }

        document.getElementById('checkAllSumberDana').addEventListener('change', function () {
            checkboxes.forEach(cb => { cb.checked = this.checked; });
            updateButtonLabel();
        });

        checkboxes.forEach(function (cb) {
            cb.addEventListener('change', updateButtonLabel);
        });

        document.querySelectorAll('.sumber-dana-option').forEach(function (option) {
            option.addEventListener('click', function (event) {
                if (event.target instanceof HTMLInputElement) return;
                event.preventDefault();
                const cb = option.querySelector('.sumber-dana-checkbox');
                if (!cb) return;
                cb.checked = !cb.checked;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        updateButtonLabel();

        // Initial validation for existing item rows
        document.querySelectorAll('.harga-input').forEach(function (el) {
            el.addEventListener('input', cekSaldoSumberDana);
        });
        document.querySelectorAll('.jumlah-input').forEach(function (el) {
            el.addEventListener('input', cekSaldoSumberDana);
        });
    });
</script>
@endsection
