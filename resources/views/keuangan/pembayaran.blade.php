@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Transaksi Pembayaran</h4>
        <small class="text-muted">Ditampilkan per siswa. Klik Tinjau Pembayaran untuk rincian item dan total keseluruhan.</small>
    </div>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end js-auto-filter" data-auto-submit>
            <div class="col-12 col-md-4"><label class="form-label small">Cari NIS / Nama</label><input name="q" class="form-control" value="{{ $q ?? '' }}"></div>
            <div class="col-6 col-md-2"><label class="form-label small">Jenjang</label><select name="jenjang" class="form-select"><option value="">Semua</option><option value="10" {{ in_array(strtoupper($jenjang ?? ''), ['10', 'X'], true) ? 'selected' : '' }}>10</option><option value="11" {{ in_array(strtoupper($jenjang ?? ''), ['11', 'XI'], true) ? 'selected' : '' }}>11</option><option value="12" {{ in_array(strtoupper($jenjang ?? ''), ['12', 'XII'], true) ? 'selected' : '' }}>12</option></select></div>
            <div class="col-6 col-md-2"><label class="form-label small">Per Halaman</label><select name="per_page" class="form-select"><option value="10" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10</option><option value="25" {{ ($perPage ?? 10) == 25 ? 'selected' : '' }}>25</option><option value="50" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50</option><option value="100" {{ ($perPage ?? 10) == 100 ? 'selected' : '' }}>100</option></select></div>
            <div class="col-6 col-md-2 d-grid"><button class="btn btn-primary" type="submit">Cari</button></div>
            <div class="col-6 col-md-2 d-grid"><a href="{{ route('pembayaran.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead><tr><th>NIS</th><th>Nama</th><th>Kelas Siswa</th><th>Item Belum Lunas</th><th>Total Belum Dibayar</th><th class="text-center">Aksi</th></tr></thead>
                <tbody>
                @forelse($siswas as $siswa)
                    @php
                        $detailBelumLunas = $siswa->tagihans->map(function ($tagihan) {
                            $potongan = (float) ($tagihan->total_potongan ?? 0);
                            $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
                            $totalAkhir = max(0, (float) $tagihan->nominal_awal - $potongan);
                            $sisaRaw = max(0, $totalAkhir - $pembayaran);
                            $sisa = max(0, (float) round($sisaRaw));
                            return [
                                'tagihan' => $tagihan,
                                'total_akhir' => $totalAkhir,
                                'sudah_bayar' => $pembayaran,
                                'sisa' => $sisa,
                            ];
                        })->filter(fn ($row) => $row['sisa'] > 0)->values();

                        $totalBelumDibayar = $detailBelumLunas->sum('sisa');
                    @endphp
                    @if($totalBelumDibayar <= 0)
                        @continue
                    @endif
                    <tr>
                        <td>{{ $siswa->nis }}</td>
                        <td>{{ $siswa->nama }}</td>
                        <td>{{ $siswa->kelas }}</td>
                        <td>{{ $detailBelumLunas->count() }} item</td>
                        <td class="fw-bold {{ $totalBelumDibayar > 0 ? 'text-danger' : 'text-success' }}">{{ $totalBelumDibayar > 0 ? 'Rp ' . number_format($totalBelumDibayar, 0, ',', '.') : 'Lunas' }}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalTinjauPembayaran{{ $siswa->id }}">Tinjau Pembayaran</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Tidak ada data pembayaran tertunda.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">{{ $siswas->links() }}</div>
</div>

@foreach($siswas as $siswa)
    @php
        $detailBelumLunas = $siswa->tagihans->map(function ($tagihan) {
            $potongan = (float) ($tagihan->total_potongan ?? 0);
            $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
            $totalAkhir = max(0, (float) $tagihan->nominal_awal - $potongan);
            $sisaRaw = max(0, $totalAkhir - $pembayaran);
            $sisa = max(0, (float) round($sisaRaw));
            return [
                'tagihan' => $tagihan,
                'total_akhir' => $totalAkhir,
                'sudah_bayar' => $pembayaran,
                'sisa' => $sisa,
            ];
        })->filter(fn ($row) => $row['sisa'] > 0)
            ->sortBy(fn ($row) => (int) ($row['tagihan']->periode_bulan ?? 0))
            ->values();

        $totalBelumDibayar = $detailBelumLunas->sum('sisa');
    @endphp
    @if($totalBelumDibayar <= 0)
        @continue
    @endif

    <div class="modal fade" id="modalTinjauPembayaran{{ $siswa->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <div>
                        <h5 class="modal-title mb-0">Tinjau Pembayaran</h5>
                        <div class="small text-muted">
                            <span class="fw-semibold text-dark">{{ $siswa->nama }}</span>
                            &middot; NIS {{ $siswa->nis }}
                            &middot; Kelas {{ $siswa->kelas }}
                            &middot; <span class="text-danger fw-semibold">{{ $detailBelumLunas->count() }} item belum lunas</span>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body bg-light">
                    <div class="alert alert-info small mb-3 mb-md-3 d-flex gap-2">
                        <i class="bi bi-info-circle-fill mt-1"></i>
                        <div>
                            Isi <strong>nominal</strong> dan pilih <strong>metode</strong> untuk item yang mau dibayar. Boleh beberapa item sekaligus, boleh sebagian saja. Pakai tombol <strong>Penuh</strong> untuk otomatis isi sisa tagihan. Setelah selesai, klik <strong>Bayar Item yang Diisi</strong> satu kali &mdash; semua item terisi akan tersimpan dan tercetak dalam satu nota.
                        </div>
                    </div>
                    <form action="{{ route('pembayaran.bayar-custom') }}" method="POST" class="form-bayar-custom" id="formBayarCustom{{ $siswa->id }}">
                        @csrf
                        <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                        <input type="hidden" name="cetak_nota" value="0" class="js-cetak-nota-input">
                        <div class="card border-0 shadow-sm mb-3">
                            <div class="card-body py-2 d-flex flex-wrap gap-3 align-items-end">
                                <div>
                                    <label class="form-label small mb-1">Tanggal Bayar (untuk semua item terisi)</label>
                                    <input type="date" name="tanggal_bayar" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required style="min-width: 160px;">
                                </div>
                                <div class="ms-md-auto d-flex flex-column">
                                    <label class="form-label small mb-1">&nbsp;</label>
                                    <button type="button" class="btn btn-sm btn-outline-secondary js-isi-penuh-semua">
                                        <i class="bi bi-lightning-charge-fill"></i> Isi Penuh Semua Item
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <input type="text" class="form-control form-control-sm js-search-item" placeholder="Cari item...">
                        </div>
                        <div class="table-responsive mb-3 rounded border bg-white" style="max-height: 420px; overflow-y: auto;">
                            <table class="table table-sm align-middle mb-0 table-hover js-table-item">
                                <thead class="table-light">
                                    <tr>
                                        <th style="min-width:180px;">Item &amp; Periode</th>
                                        <th class="text-end" style="min-width:130px;">Sisa Tagihan</th>
                                        <th style="min-width:340px;">Bayar Item Ini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($detailBelumLunas as $row)
                                        @php
                                            $tagihan = $row['tagihan'];
                                            $keteranganPotongan = $tagihan->potongans->pluck('keterangan')->filter()->unique()->implode(', ');
                                        @endphp
                                        <tr data-item-nama="{{ strtolower($tagihan->itemPembayaran->nama_item ?? '') }}">
                                            <td>
                                                <div class="fw-semibold">{{ $tagihan->itemPembayaran->nama_item ?? '-' }}</div>
                                                <div class="small text-muted">
                                                    {{ $tagihan->periode_label }}
                                                    <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1">Kelas {{ $tagihan->kelas ?? '-' }}</span>
                                                </div>
                                                @if($keteranganPotongan !== '')
                                                    <div class="small text-muted">Potongan: {{ $keteranganPotongan }}</div>
                                                @endif
                                            </td>
                                            <td class="text-end">
                                                <div class="fw-bold {{ $row['sisa'] > 0 ? 'text-danger' : 'text-success' }}">{{ $row['sisa'] > 0 ? 'Rp ' . number_format($row['sisa'], 0, ',', '.') : 'Lunas' }}</div>
                                                <div class="small text-muted">dari Rp {{ number_format($row['total_akhir'], 0, ',', '.') }}</div>
                                                @if($row['sudah_bayar'] > 0)
                                                    <div class="small text-muted">Sudah: Rp {{ number_format($row['sudah_bayar'], 0, ',', '.') }}</div>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="input-group input-group-sm mb-1">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="text" name="items[{{ $tagihan->id }}][nominal_bayar]" data-rupiah="true" class="form-control js-nominal-item" placeholder="0" data-max="{{ (int) $row['sisa'] }}">
                                                    <button type="button" class="btn btn-outline-secondary js-isi-penuh" data-max="{{ (int) $row['sisa'] }}" title="Isi penuh sisa tagihan">Penuh</button>
                                                </div>
                                                <div class="row g-1">
                                                    <div class="col-6">
                                                        <select name="items[{{ $tagihan->id }}][metode_bayar]" class="form-select form-select-sm metode-select">
                                                            <option value="">Pilih metode</option>
                                                            <option value="cash">Cash</option>
                                                            <option value="cicil">Cicil</option>
                                                            <option value="transfer">Transfer</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-6 norek-field d-none">
                                                        <input type="text" name="items[{{ $tagihan->id }}][nama_bank]" class="form-control form-control-sm" placeholder="Nama Bank">
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-muted py-4">Semua item sudah lunas.</td></tr>
                                    @endforelse
                                    <tr class="js-row-no-match d-none"><td colspan="3" class="text-center text-muted py-4">Item tidak ditemukan.</td></tr>
                                </tbody>
                            </table>
                        </div>
                        @if($detailBelumLunas->isNotEmpty())
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-success" onclick="return submitBayarCustomDenganKonfirmasiCetak(this.form)">
                                    <i class="bi bi-check2-circle"></i> Bayar Item yang Diisi
                                </button>
                            </div>
                        @endif
                    </form>

                    <hr class="my-3">

                    <div class="card border-0 shadow-sm">
                        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <div>
                                <small class="text-muted d-block">Total Keseluruhan Belum Dibayar ({{ $detailBelumLunas->count() }} item)</small>
                                <h4 class="mb-0 {{ $totalBelumDibayar > 0 ? 'text-danger' : 'text-success' }}">{{ $totalBelumDibayar > 0 ? 'Rp ' . number_format($totalBelumDibayar, 0, ',', '.') : 'Lunas' }}</h4>
                                <small class="text-muted">Gunakan ini untuk melunasi <strong>semua</strong> item sekaligus.</small>
                            </div>
                        @if($totalBelumDibayar > 0)
                            <form action="{{ route('pembayaran.bayar-semua') }}" method="POST" class="d-flex flex-wrap gap-2 align-items-end">
                                @csrf
                                <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                                <div><label class="form-label small mb-1">Tanggal</label><input type="date" name="tanggal_bayar" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required></div>
                                <div><label class="form-label small mb-1">Metode</label>
                                    <select name="metode_bayar" class="form-select form-select-sm metode-select" required>
                                        <option value="">Pilih</option>
                                        <option value="cash">Cash</option>
                                        <option value="transfer">Transfer</option>
                                    </select>
                                </div>
                                <div class="norek-field d-none">
                                    <label class="form-label small mb-1">Nama Bank</label>
                                    <input type="text" name="nama_bank" class="form-control form-control-sm" placeholder="Nama Bank">
                                </div>
                                <input type="hidden" name="cetak_nota" value="0" class="js-cetak-nota-input">
                                <button class="btn btn-primary" type="submit" onclick="return submitPembayaranDenganKonfirmasiCetak(this.form, 'Bayar seluruh item belum lunas untuk {{ $siswa->nama }}?')">
                                    <i class="bi bi-cash-stack"></i> Bayar Sekaligus (Lunas Semua)
                                </button>
                            </form>
                        @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<script>
    function toggleNorekFields(root) {
        (root || document).querySelectorAll('.metode-select').forEach(function(select) {
            var norekFields = select.closest('.row, .d-flex')?.querySelectorAll('.norek-field');
            if (!norekFields || norekFields.length === 0) {
                norekFields = select.closest('form')?.querySelectorAll('.norek-field');
            }
            if (!norekFields) {
                return;
            }
            norekFields.forEach(function(norekField) {
                if (select.value === 'transfer') {
                    norekField.classList.remove('d-none');
                } else {
                    norekField.classList.add('d-none');
                    norekField.querySelectorAll('input').forEach(function(input) {
                        input.value = '';
                    });
                }
            });
        });
    }

    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('metode-select')) {
            toggleNorekFields(e.target.closest('form') || document);
        }
    });

    // Jangan proses SEMUA modal (bisa ratusan select) saat halaman baru dimuat —
    // itu yang bikin browser sempat freeze/blank ketika siswa punya banyak item (mis. 156 item).
    // Cukup proses modal yang benar-benar dibuka.
    document.addEventListener('shown.bs.modal', function (e) {
        toggleNorekFields(e.target);
    });

    // Search item pada modal Tinjau Pembayaran: baris yang tidak cocok
    // dengan kata kunci otomatis disembunyikan, hanya item yang cocok yang tampil.
    function filterItemTable(input) {
        const table = input.closest('.modal-body')?.querySelector('.js-table-item');
        if (!table) {
            return;
        }

        const keyword = input.value.trim().toLowerCase();
        const rows = table.querySelectorAll('tbody tr[data-item-nama]');
        const noMatchRow = table.querySelector('.js-row-no-match');
        let adaYangCocok = false;

        rows.forEach(function (row) {
            const namaItem = row.getAttribute('data-item-nama') || '';
            const cocok = keyword === '' || namaItem.includes(keyword);
            row.classList.toggle('d-none', !cocok);
            if (cocok) {
                adaYangCocok = true;
            }
        });

        if (noMatchRow) {
            noMatchRow.classList.toggle('d-none', adaYangCocok || rows.length === 0);
        }
    }

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('js-search-item')) {
            filterItemTable(e.target);
        }
    });

    function cekBatasNominal(input) {
        if (!input) {
            return true;
        }

        const tr = input.closest('tr');
        const wrapper = input.closest('.input-group');
        let warningEl = tr ? tr.querySelector('.js-warning-melebihi') : null;

        const max = Number(input.getAttribute('data-max') || 0);
        const nominal = Number((input.value || '').toString().replace(/[^\d]/g, '')) || 0;
        const melebihi = max > 0 && nominal > max;

        if (melebihi) {
            input.classList.add('is-invalid');
            wrapper?.classList.add('border', 'border-danger', 'rounded');

            if (!warningEl && tr) {
                warningEl = document.createElement('div');
                warningEl.className = 'js-warning-melebihi small text-danger mt-1';
                warningEl.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Nominal melebihi sisa tagihan (maks Rp ' + max.toLocaleString('id-ID') + ')';
                wrapper?.insertAdjacentElement('afterend', warningEl);
            }
        } else {
            input.classList.remove('is-invalid');
            wrapper?.classList.remove('border', 'border-danger', 'rounded');
            warningEl?.remove();
        }

        return !melebihi;
    }

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('js-nominal-item')) {
            cekBatasNominal(e.target);
        }
    });

    function isiNominalPenuh(input, max) {
        if (!input || !max || Number(max) <= 0) {
            return;
        }
        input.value = String(Number(max));
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }

    document.addEventListener('click', function (e) {
        const btnPenuh = e.target.closest('.js-isi-penuh');
        if (btnPenuh) {
            const max = btnPenuh.getAttribute('data-max');
            const tr = btnPenuh.closest('tr');
            const input = tr ? tr.querySelector('input.js-nominal-item') : null;
            isiNominalPenuh(input, max);
            return;
        }

        const btnPenuhSemua = e.target.closest('.js-isi-penuh-semua');
        if (btnPenuhSemua) {
            const form = btnPenuhSemua.closest('form');
            if (!form) return;
            form.querySelectorAll('tr[data-item-nama]').forEach((tr) => {
                if (tr.classList.contains('d-none')) {
                    // Baris disembunyikan oleh filter pencarian item -> lewati
                    return;
                }
                const input = tr.querySelector('input.js-nominal-item');
                const max = input ? input.getAttribute('data-max') : null;
                isiNominalPenuh(input, max);
            });
        }
    });

    // Filter metode pembayaran otomatis sesuai nominal yang diisi:
    // - Nominal = sisa tagihan (bayar penuh) -> opsi "Cicil" disembunyikan dari dropdown
    // - Nominal < sisa tagihan (bayar sebagian) -> hanya "Cicil" yang boleh (Cash dinonaktifkan)
    // - "Transfer" selalu tersedia dan tidak terpengaruh aturan ini, bebas dipilih kapan saja.
    // Metode pembayaran otomatis terpilih sesuai nominal yang diisi, tanpa perlu klik manual:
    // - Nominal = sisa tagihan (bayar penuh) -> otomatis pilih "Cash"
    // - Nominal < sisa tagihan (bayar sebagian) -> otomatis pilih "Cicil"
    // - "Transfer" tidak pernah dipilih otomatis, murni pilihan manual user kapan saja.
    function filterMetodeByNominal(input) {
        if (!input) {
            return;
        }

        const tr = input.closest('tr');
        const select = tr ? tr.querySelector('select.metode-select') : null;
        if (!select) {
            return;
        }

        const optCash = select.querySelector('option[value="cash"]');
        const optCicil = select.querySelector('option[value="cicil"]');
        if (!optCash || !optCicil) {
            return;
        }

        const max = Number(input.getAttribute('data-max') || 0);
        const nominal = Number((input.value || '').toString().replace(/[^\d]/g, '')) || 0;

        // Reset dulu supaya tidak ada opsi yang ke-lock/ke-sembunyikan kalau nominal kosong/direset
        optCash.disabled = false;
        optCicil.disabled = false;
        optCicil.hidden = false;

        if (nominal <= 0 || max <= 0) {
            // Nominal dikosongkan -> jangan paksa reset pilihan manual user (misal Transfer)
            return;
        }

        if (nominal >= max) {
            // Bayar penuh -> otomatis pilih Cash, sembunyikan Cicil dari daftar pilihan
            optCicil.disabled = true;
            optCicil.hidden = true;
            if (select.value === 'cicil') {
                select.value = 'cash';
            } else if (select.value !== 'transfer') {
                select.value = 'cash';
            }
        } else {
            // Bayar sebagian -> otomatis pilih Cicil, kunci Cash
            optCash.disabled = true;
            if (select.value !== 'transfer') {
                select.value = 'cicil';
            }
        }

        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('js-nominal-item')) {
            filterMetodeByNominal(e.target);
            cekBatasNominal(e.target);
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('input.js-nominal-item').forEach(filterMetodeByNominal);
    });

    function submitBayarCustomDenganKonfirmasiCetak(form) {
        if (!form) {
            return false;
        }

        const rows = Array.from(form.querySelectorAll('tbody tr')).filter((tr) => tr.querySelector('input[name*="[nominal_bayar]"]'));
        let jumlahDiisi = 0;

        for (const tr of rows) {
            const nominalInput = tr.querySelector('input[name*="[nominal_bayar]"]');
            if (!cekBatasNominal(nominalInput)) {
                if (typeof window.openInfoAlertModal === 'function') {
                    window.openInfoAlertModal('Ada nominal yang melebihi sisa tagihan. Perbaiki dulu sebelum menyimpan.', 'Peringatan');
                } else {
                    alert('Ada nominal yang melebihi sisa tagihan. Perbaiki dulu sebelum menyimpan.');
                }
                nominalInput?.focus();
                return false;
            }
        }

        for (const tr of rows) {
            const nominalInput = tr.querySelector('input[name*="[nominal_bayar]"]');
            const metodeSelect = tr.querySelector('select[name*="[metode_bayar]"]');
            const nominalRaw = (nominalInput?.value || '').replace(/[^\d]/g, '');

            if (nominalRaw && Number(nominalRaw) > 0) {
                jumlahDiisi++;

                if (!metodeSelect || !String(metodeSelect.value || '').trim()) {
                    const namaItem = tr.querySelector('td')?.textContent?.trim() || 'item';
                    if (typeof window.openInfoAlertModal === 'function') {
                        window.openInfoAlertModal('Metode pembayaran untuk item "' + namaItem + '" belum dipilih.', 'Peringatan');
                    } else {
                        alert('Metode pembayaran untuk item "' + namaItem + '" belum dipilih.');
                    }
                    metodeSelect?.focus();
                    return false;
                }
            } else if (nominalInput) {
                // Kosongkan field metode/norek pada baris yang tidak diisi agar tidak ikut divalidasi di server
                const metodeKosong = tr.querySelector('select[name*="[metode_bayar]"]');
                if (metodeKosong) {
                    metodeKosong.value = '';
                }
            }
        }

        if (jumlahDiisi === 0) {
            if (typeof window.openInfoAlertModal === 'function') {
                window.openInfoAlertModal('Isi dulu nominal minimal 1 item yang mau dibayar.', 'Peringatan');
            } else {
                alert('Isi dulu nominal minimal 1 item yang mau dibayar.');
            }
            return false;
        }

        const sourceModalElement = form.closest('.modal');
        const cetakField = form.querySelector('.js-cetak-nota-input');
        const rowsDiisi = [];
        const semuaBaris = Array.from(form.querySelectorAll('tbody tr[data-item-nama]'));
        for (const tr of semuaBaris) {
            const nominalInput = tr.querySelector('input[name*="[nominal_bayar]"]');
            const nominalRaw = (nominalInput?.value || '').replace(/[^\d]/g, '');
            if (!nominalRaw || Number(nominalRaw) <= 0) continue;
            const namaEl = tr.querySelector('td:first-child .fw-semibold');
            const periodeEl = tr.querySelector('td:first-child .small.text-muted');
            const namaItem = (namaEl?.textContent || '').trim();
            const periode = (periodeEl?.textContent || '').trim();
            const metodeSelect = tr.querySelector('select[name*="[metode_bayar]"]');
            const metode = metodeSelect ? metodeSelect.options[metodeSelect.selectedIndex]?.text || metodeSelect.value : '-';
            rowsDiisi.push({
                nama: periode ? namaItem + ' (' + periode + ')' : namaItem,
                nominal: Number(nominalRaw),
                metode: metode,
            });
        }

        var tutupSumberLaluTampilkanKonfirmasi = function () {
            if (sourceModalElement) {
                bootstrap.Modal.getOrCreateInstance(sourceModalElement).hide();
            }
            setTimeout(function () {
                window.openPaymentConfirmModal({
                    message: jumlahDiisi + ' item akan dibayar:',
                    items: rowsDiisi,
                    onNoPrint: function () {
                        window.openConfirmActionModal(
                            'Yakin akan membayar ' + rowsDiisi.length + ' item ini?',
                            'Konfirmasi Akhir',
                            function () { form.submit(); }
                        );
                    },
                    onPrint: function () {
                        window.openConfirmActionModal(
                            'Yakin akan membayar ' + rowsDiisi.length + ' item ini?',
                            'Konfirmasi Akhir',
                            function () {
                                if (cetakField) cetakField.value = '1';
                                form.setAttribute('target', 'notaPembayaranPopup');
                                form.submit();
                            }
                        );
                    },
                    showPrintButton: true,
                    sourceModal: sourceModalElement,
                });
            }, 300);
        };
        tutupSumberLaluTampilkanKonfirmasi();
        return false;
    }

    function submitPembayaranDenganKonfirmasiCetak(form, pesanKonfirmasiBayar) {
        if (!form) {
            return false;
        }

        const metodeField = form.querySelector('select[name="metode_bayar"]');
        if (metodeField && !String(metodeField.value || '').trim()) {
            if (typeof window.openInfoAlertModal === 'function') {
                window.openInfoAlertModal('Metode pembayaran belum dipilih. Silakan pilih metode pembayaran terlebih dahulu.', 'Peringatan');
            } else {
                alert('Metode pembayaran belum dipilih. Silakan pilih metode pembayaran terlebih dahulu.');
            }
            metodeField.focus();
            return false;
        }

        const sourceModalElement = form.closest('.modal');
        const cetakField = form.querySelector('.js-cetak-nota-input');
        const itemsBayarSemua = [];
        const tabelItem = sourceModalElement?.querySelector('.js-table-item');
        if (tabelItem) {
            tabelItem.querySelectorAll('tbody tr[data-item-nama]').forEach(function (tr) {
                if (tr.classList.contains('d-none')) return;
                const sisaEl = tr.querySelector('td:nth-child(2) .fw-bold');
                const namaEl = tr.querySelector('td:first-child .fw-semibold');
                const periodeEl = tr.querySelector('td:first-child .small.text-muted');
                const namaItem = (namaEl?.textContent || '').trim();
                const periode = (periodeEl?.textContent || '').trim();
                const sisaText = (sisaEl?.textContent || '').replace(/[^\d]/g, '');
                const nominal = Number(sisaText) || 0;
                if (nominal <= 0) return;
                itemsBayarSemua.push({
                    nama: periode ? namaItem + ' (' + periode + ')' : namaItem,
                    nominal: nominal,
                    metode: metodeField ? metodeField.options[metodeField.selectedIndex]?.text || metodeField.value : '-',
                });
            });
        }

        var tutupSumberLaluTampilkanKonfirmasi = function () {
            if (sourceModalElement) {
                bootstrap.Modal.getOrCreateInstance(sourceModalElement).hide();
            }
            setTimeout(function () {
                window.openPaymentConfirmModal({
                    message: pesanKonfirmasiBayar || 'Konfirmasi pembayaran:',
                    items: itemsBayarSemua,
                    onNoPrint: function () {
                        window.openConfirmActionModal(
                            'Yakin akan membayar ' + itemsBayarSemua.length + ' item ini?',
                            'Konfirmasi Akhir',
                            function () { form.submit(); }
                        );
                    },
                    onPrint: function () {
                        window.openConfirmActionModal(
                            'Yakin akan membayar ' + itemsBayarSemua.length + ' item ini?',
                            'Konfirmasi Akhir',
                            function () {
                                if (cetakField) cetakField.value = '1';
                                form.setAttribute('target', 'notaPembayaranPopup');
                                form.submit();
                            }
                        );
                    },
                    showPrintButton: true,
                    sourceModal: sourceModalElement,
                });
            }, 300);
        };
        tutupSumberLaluTampilkanKonfirmasi();
        return false;
    }
</script>
@endsection
