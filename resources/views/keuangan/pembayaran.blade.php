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
        <form method="GET" class="row g-2 align-items-end js-auto-filter">
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
        })->filter(fn ($row) => $row['sisa'] > 0)->values();

        $totalBelumDibayar = $detailBelumLunas->sum('sisa');
    @endphp
    @if($totalBelumDibayar <= 0)
        @continue
    @endif

    <div class="modal fade" id="modalTinjauPembayaran{{ $siswa->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tinjau Pembayaran - {{ $siswa->nis }} / {{ $siswa->nama }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive mb-3">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Kelas Tagihan</th>
                                    <th>Periode</th>
                                    <th>Total Akhir</th>
                                    <th>Sudah Dibayar</th>
                                    <th>Belum Dibayar</th>
                                    <th class="text-center">Bayar Per Item</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($detailBelumLunas as $row)
                                    @php $tagihan = $row['tagihan']; @endphp
                                    <tr>
                                        <td>{{ $tagihan->itemPembayaran->nama_item ?? '-' }}</td>
                                        <td>{{ $tagihan->kelas ?? '-' }}</td>
                                        <td>{{ $tagihan->periode_label }}</td>
                                        <td>
                                            Rp {{ number_format($row['total_akhir'], 0, ',', '.') }}
                                            @php
                                                $keteranganPotongan = $tagihan->potongans->pluck('keterangan')->filter()->unique()->implode(', ');
                                            @endphp
                                            @if($keteranganPotongan !== '')
                                                <div><small class="text-muted">Potongan: {{ $keteranganPotongan }}</small></div>
                                            @endif
                                        </td>
                                        <td>Rp {{ number_format($row['sudah_bayar'], 0, ',', '.') }}</td>
                                        <td class="fw-semibold {{ $row['sisa'] > 0 ? 'text-danger' : 'text-success' }}">{{ $row['sisa'] > 0 ? 'Rp ' . number_format($row['sisa'], 0, ',', '.') : 'Lunas' }}</td>
                                        <td>
                                            <form action="{{ route('pembayaran.store', $tagihan->id) }}" method="POST" class="row g-1">
                                                @csrf
                                                <div class="col-md-3"><input type="date" name="tanggal_bayar" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required></div>
                                                <div class="col-md-4"><input type="text" name="nominal_bayar" data-rupiah="true" class="form-control form-control-sm" placeholder="Input nominal bayar" required></div>
                                                <div class="col-md-3">
                                                    <select name="metode_bayar" class="form-select form-select-sm" required>
                                                        <option value="">Metode</option>
                                                        <option value="cash">Cash</option>
                                                        <option value="cicil">Cicil</option>
                                                        <option value="qris">QRIS</option>
                                                        <option value="pinjaman">Pinjaman</option>
                                                    </select>
                                                </div>
                                                <input type="hidden" name="cetak_nota" value="0" class="js-cetak-nota-input">
                                                <div class="col-md-2 d-flex gap-1">
                                                    <button class="btn btn-sm btn-success w-100" type="submit" onclick="return submitPembayaranDenganKonfirmasiCetak(this.form)">Bayar</button>
                                                </div>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">Semua item sudah lunas.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="border rounded p-3 bg-light d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                        <div>
                            <small class="text-muted d-block">Total Keseluruhan Belum Dibayar</small>
                            <h5 class="mb-0 {{ $totalBelumDibayar > 0 ? 'text-danger' : 'text-success' }}">{{ $totalBelumDibayar > 0 ? 'Rp ' . number_format($totalBelumDibayar, 0, ',', '.') : 'Lunas' }}</h5>
                        </div>
                        @if($totalBelumDibayar > 0)
                            <form action="{{ route('pembayaran.bayar-semua') }}" method="POST" class="row g-1 align-items-end">
                                @csrf
                                <input type="hidden" name="siswa_id" value="{{ $siswa->id }}">
                                <div class="col-md-auto"><label class="form-label small">Tanggal</label><input type="date" name="tanggal_bayar" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required></div>
                                <div class="col-md-auto"><label class="form-label small">Metode</label>
                                    <select name="metode_bayar" class="form-select form-select-sm" required>
                                        <option value="">Pilih</option>
                                        <option value="cash">Cash</option>
                                        <option value="cicil">Cicil</option>
                                        <option value="qris">QRIS</option>
                                        <option value="pinjaman">Pinjaman</option>
                                    </select>
                                </div>
                                <input type="hidden" name="cetak_nota" value="0" class="js-cetak-nota-input">
                                <div class="col-md-auto d-flex gap-1">
                                    <div class="d-grid"><label class="form-label small">&nbsp;</label><button class="btn btn-sm btn-primary" type="submit" onclick="return submitPembayaranDenganKonfirmasiCetak(this.form, 'Bayar seluruh item belum lunas untuk {{ $siswa->nama }}?')">Bayar Sekaligus</button></div>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<script>
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
        const submitWithChoice = (inginCetak) => {
            if (cetakField) {
                cetakField.value = inginCetak ? '1' : '0';
            }

            if (inginCetak) {
                form.setAttribute('target', 'notaPembayaranPopup');
            } else {
                form.removeAttribute('target');
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        };

        if (typeof window.openPaymentConfirmModal === 'function') {
            window.openPaymentConfirmModal({
                message: pesanKonfirmasiBayar || 'Pilih tindakan untuk pembayaran ini.',
                onNoPrint: function () {
                    submitWithChoice(false);
                },
                onPrint: function () {
                    submitWithChoice(true);
                },
                showPrintButton: true,
                sourceModal: sourceModalElement,
            });

            return false;
        }

        return true;
    }
</script>
@endsection
