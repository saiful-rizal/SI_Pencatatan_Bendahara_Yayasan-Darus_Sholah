@extends('layouts.app')

@section('content')
@php
    $bulanOptions = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Tagihan Siswa</h4>
        <small class="text-muted">Ditampilkan per siswa. Klik Tinjau Tagihan untuk rincian item per siswa.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahTagihan">+ Tambah Tagihan</button>
</div>

<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end js-auto-filter" data-auto-submit>
            <div class="col-12 col-md-4"><label class="form-label small">Cari NIS / Nama</label><input name="q" class="form-control" value="{{ $q ?? '' }}"></div>
            <div class="col-6 col-md-2"><label class="form-label small">Jenjang</label><select name="jenjang" class="form-select"><option value="">Semua</option><option value="10" {{ in_array(strtoupper($jenjang ?? ''), ['10', 'X'], true) ? 'selected' : '' }}>10</option><option value="11" {{ in_array(strtoupper($jenjang ?? ''), ['11', 'XI'], true) ? 'selected' : '' }}>11</option><option value="12" {{ in_array(strtoupper($jenjang ?? ''), ['12', 'XII'], true) ? 'selected' : '' }}>12</option></select></div>
            <div class="col-6 col-md-2"><label class="form-label small">Per Halaman</label><select name="per_page" class="form-select"><option value="10" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10</option><option value="25" {{ ($perPage ?? 10) == 25 ? 'selected' : '' }}>25</option><option value="50" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50</option><option value="100" {{ ($perPage ?? 10) == 100 ? 'selected' : '' }}>100</option></select></div>
            <div class="col-6 col-md-2 d-grid"><button class="btn btn-primary" type="submit">Cari</button></div>
            <div class="col-6 col-md-2 d-grid"><a href="{{ route('tagihan.index') }}" class="btn btn-outline-secondary">Reset</a></div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                <tr>
                    <th>NIS</th><th>Nama</th><th>Kelas Siswa</th><th>Jumlah Item Tagihan</th><th>Total Sisa</th><th class="text-center">Aksi</th>
                </tr>
                </thead>
                <tbody>
                @forelse($siswaRows as $siswa)
                    @php
                        $tagihanAktif = $siswa->tagihans->filter(function ($tagihan) {
                            $potongan = (float) ($tagihan->total_potongan ?? 0);
                            $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
                            $sisa = max(0, (float) $tagihan->nominal_awal - $potongan - $pembayaran);
                            return $sisa > 0;
                        })->sortBy(fn ($tagihan) => (int) ($tagihan->periode_bulan ?? 0))->values();

                        $totalSisa = $tagihanAktif->sum(function ($tagihan) {
                            $potongan = (float) ($tagihan->total_potongan ?? 0);
                            $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
                            return max(0, (float) $tagihan->nominal_awal - $potongan - $pembayaran);
                        });
                    @endphp
                    @if($totalSisa <= 0)
                        @continue
                    @endif
                    <tr>
                        <td>{{ $siswa->nis }}</td>
                        <td>{{ $siswa->nama }}</td>
                        <td>{{ $siswa->kelas }}</td>
                        <td>{{ $tagihanAktif->count() }} item</td>
                        <td class="fw-bold text-danger">Rp {{ number_format($totalSisa, 0, ',', '.') }}</td>
                        <td class="text-center">
                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalTinjauTagihan{{ $siswa->id }}">Tinjau Tagihan</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada tagihan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white">{{ $siswaRows->links() }}</div>
</div>

@foreach($siswaRows as $siswa)
    @php
        $tagihanAktif = $siswa->tagihans->filter(function ($tagihan) {
            $potongan = (float) ($tagihan->total_potongan ?? 0);
            $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
            $sisa = max(0, (float) $tagihan->nominal_awal - $potongan - $pembayaran);
            return $sisa > 0;
        })->sortBy(fn ($tagihan) => (int) ($tagihan->periode_bulan ?? 0))->values();

        $totalSisa = $tagihanAktif->sum(function ($tagihan) {
            $potongan = (float) ($tagihan->total_potongan ?? 0);
            $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
            return max(0, (float) $tagihan->nominal_awal - $potongan - $pembayaran);
        });
    @endphp
    @if($totalSisa <= 0)
        @continue
    @endif
    <div class="modal fade" id="modalTinjauTagihan{{ $siswa->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Tinjau Tagihan - {{ $siswa->nis }} / {{ $siswa->nama }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    {{-- Info jumlah tagihan --}}
                    @if($tagihanAktif->count() > 0)
                    <div class="d-flex align-items-center justify-content-between mb-2 px-1">
                        <small class="text-muted">Total <strong>{{ $tagihanAktif->count() }}</strong> tagihan belum lunas</small>
                        <div class="d-flex align-items-center gap-2">
                            @if($tagihanAktif->count() > 20)
                            <small class="text-muted fst-italic">Ditampilkan 20 per halaman</small>
                            @endif
                            <form action="{{ route('tagihan.destroy-all-siswa', $siswa->id) }}" method="POST" class="d-inline" id="delete-all-tagihan-{{ $siswa->id }}">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"
                                    data-form-id="delete-all-tagihan-{{ $siswa->id }}"
                                    data-confirm-title="Konfirmasi Hapus Semua Tagihan"
                                    data-confirm-message="Anda yakin ingin menghapus SEMUA tagihan belum lunas milik {{ $siswa->nama }} ({{ $tagihanAktif->count() }} item)? Tindakan ini tidak dapat dibatalkan."
                                    data-confirm-action-text="Ya, Hapus Semua">
                                    <i class="fas fa-trash me-1"></i> Hapus Semua
                                </button>
                            </form>
                        </div>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" id="tabelTagihan{{ $siswa->id }}">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>Item</th>
                                <th>Kelas Tagihan</th>
                                <th>Periode</th>
                                <th>Nominal Awal</th>
                                <th>Total Potongan</th>
                                <th>Sudah Dibayar</th>
                                <th>Sisa</th>
                                <th>Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                            </thead>
                            <tbody id="tbodyTagihan{{ $siswa->id }}">
                            @forelse($tagihanAktif as $loopIndex => $tagihan)
                                @php
                                    $potongan = (float) ($tagihan->total_potongan ?? 0);
                                    $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
                                    $totalAkhir = max(0, (float) $tagihan->nominal_awal - $potongan);
                                    $sisa = max(0, $totalAkhir - $pembayaran);
                                @endphp
                                <tr class="tagihan-row-{{ $siswa->id }}" data-row-index="{{ $loopIndex }}">
                                    <td class="text-muted">{{ $loopIndex + 1 }}</td>
                                    <td>{{ optional($tagihan->itemPembayaran)->nama_item ?? '-' }}</td>
                                    <td>{{ $tagihan->kelas ?? '-' }}</td>
                                    <td>{{ $tagihan->periode_label }}</td>
                                    <td>Rp {{ number_format($tagihan->nominal_awal, 0, ',', '.') }}</td>
                                    <td>
                                        Rp {{ number_format($potongan, 0, ',', '.') }}
                                        @php
                                            $keteranganPotongan = $tagihan->potongans->pluck('keterangan')->filter()->unique()->implode(', ');
                                        @endphp
                                        @if($keteranganPotongan !== '')
                                            <div><small class="text-muted">{{ $keteranganPotongan }}</small></div>
                                        @endif
                                    </td>
                                    <td>Rp {{ number_format($pembayaran, 0, ',', '.') }}</td>
                                    <td class="fw-semibold {{ $sisa > 0 ? 'text-danger' : 'text-success' }}">{{ $sisa > 0 ? 'Rp ' . number_format($sisa, 0, ',', '.') : 'LUNAS' }}</td>
                                    <td><span class="badge bg-secondary">{{ $tagihan->status }}</span></td>
                                    <td class="text-center">
                                        <div class="d-flex flex-nowrap gap-1 justify-content-center">
                                            <button class="btn btn-sm btn-outline-dark px-2" data-bs-toggle="modal" data-bs-target="#modalEditTagihan{{ $tagihan->id }}" data-bs-dismiss="modal">Edit</button>
                                            <button class="btn btn-sm btn-outline-primary px-2" data-bs-toggle="modal" data-bs-target="#modalPotongan{{ $tagihan->id }}">Potongan</button>
                                            <form action="{{ route('tagihan.destroy', $tagihan->id) }}" method="POST" class="d-inline" id="delete-tagihan-{{ $tagihan->id }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="btn btn-sm btn-outline-danger px-2 btn-delete-confirm"
                                                    data-form-id="delete-tagihan-{{ $tagihan->id }}"
                                                    data-delete-label="tagihan {{ optional($tagihan->itemPembayaran)->nama_item ?? 'Item' }} untuk {{ $siswa->nama }}">
                                                    Hapus
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="text-center text-muted">Belum ada item tagihan.</td></tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination client-side (muncul jika tagihan > 20) --}}
                    <div id="paginasiTagihan{{ $siswa->id }}" class="paginasi-tagihan-wrapper d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2" style="display:none!important">
                        <div class="text-muted small" id="infoHalamanTagihan{{ $siswa->id }}"></div>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="ulPaginasiTagihan{{ $siswa->id }}"></ul>
                        </nav>
                    </div>

                    <div class="border rounded bg-light p-3 mt-3">
                        <small class="text-muted d-block">Total Sisa Seluruh Tagihan</small>
                        <h5 class="mb-0 {{ $totalSisa > 0 ? 'text-danger' : 'text-success' }}">{{ $totalSisa > 0 ? 'Rp ' . number_format($totalSisa, 0, ',', '.') : 'LUNAS' }}</h5>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

@foreach($siswaRows as $siswa)
    @php
        $tagihanAktif = $siswa->tagihans->filter(function ($tagihan) {
            $potongan = (float) ($tagihan->total_potongan ?? 0);
            $pembayaran = (float) ($tagihan->total_pembayaran ?? 0);
            $sisa = max(0, (float) $tagihan->nominal_awal - $potongan - $pembayaran);
            return $sisa > 0;
        })->sortBy(fn ($tagihan) => (int) ($tagihan->periode_bulan ?? 0))->values();
    @endphp
    @foreach($tagihanAktif as $tagihan)
        <div class="modal fade" id="modalEditTagihan{{ $tagihan->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('tagihan.update', $tagihan->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Tagihan</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body row g-2">
                            <div class="col-12">
                                <small class="text-muted d-block">{{ $siswa->nis }} - {{ $siswa->nama }}</small>
                                <div class="fw-semibold">{{ $tagihan->itemPembayaran->nama_item ?? '-' }}</div>
                            </div>
                            <div class="col-6"><label class="form-label small">Bulan</label>
                                <select name="periode_bulan" class="form-select">
                                    <option value="">Pilih Bulan</option>
                                    @foreach($bulanOptions as $bulanAngka => $bulanNama)
                                        <option value="{{ $bulanAngka }}" {{ (int) $tagihan->periode_bulan === (int) $bulanAngka ? 'selected' : '' }}>{{ $bulanNama }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6"><label class="form-label small">Tahun</label><input type="number" name="periode_tahun" min="2000" max="2100" class="form-control" value="{{ $tagihan->periode_tahun }}"></div>
                            <div class="col-12"><label class="form-label small">Nominal Awal</label><input type="text" name="nominal_awal" data-rupiah="true" class="form-control" value="{{ number_format((float) $tagihan->nominal_awal, 0, ',', '.') }}" required></div>
                            <div class="col-12"><label class="form-label small">Catatan</label><input name="catatan" class="form-control" value="{{ $tagihan->catatan }}"></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="modalPotongan{{ $tagihan->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Riwayat Potongan - {{ $siswa->nama }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3 p-2 border rounded bg-light">
                            <small class="text-muted d-block">Tagihan</small>
                            <div class="fw-semibold">{{ optional($tagihan->itemPembayaran)->nama_item ?? '-' }} ({{ $tagihan->periode_label }})</div>
                        </div>

                        <form action="{{ route('tagihan.potongan.store', $tagihan->id) }}" method="POST" class="row g-2 mb-3">
                            @csrf
                            <div class="col-md-3">
                                <label class="form-label small">Tanggal</label>
                                <input type="date" name="tanggal_potongan" class="form-control" value="{{ old('tanggal_potongan', date('Y-m-d')) }}" required>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small">Keterangan</label>
                                <input name="keterangan" class="form-control" placeholder="Keterangan" value="{{ old('keterangan') }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small">Nominal</label>
                                <input type="text" name="nominal_potongan" data-rupiah="true" class="form-control" value="{{ old('nominal_potongan') }}" required>
                            </div>
                            <div class="col-md-2 d-grid">
                                <label class="form-label small">&nbsp;</label>
                                <button class="btn btn-primary">Simpan</button>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th style="width: 130px;">Tanggal</th>
                                        <th>Keterangan</th>
                                        <th style="width: 170px;">Nominal</th>
                                        <th style="width: 170px;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tagihan->potongans as $potongan)
                                        <tr>
                                            <td>{{ optional($potongan->tanggal_potongan)->format('d/m/Y') }}</td>
                                            <td>{{ $potongan->keterangan }}</td>
                                            <td>Rp {{ number_format((float) $potongan->nominal_potongan, 0, ',', '.') }}</td>
                                            <td class="text-center">
                                                <form action="{{ route('tagihan.potongan.destroy', [$tagihan->id, $potongan->id]) }}" method="POST" class="d-inline" id="delete-potongan-{{ $potongan->id }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"
                                                        data-form-id="delete-potongan-{{ $potongan->id }}"
                                                        data-delete-label="potongan {{ optional($tagihan->itemPembayaran)->nama_item ?? 'Item' }} - {{ $siswa->nama }}">
                                                        Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-muted">Belum ada riwayat potongan.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endforeach

<div class="modal fade" id="modalTambahTagihan" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('tagihan.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Tagihan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-2">
                    <div class="col-md-4">
                        <label class="form-label small">Cari Siswa (NIS / Nama)</label>
                        <div class="position-relative">
                            <input type="text" id="siswa_search" class="form-control" placeholder="Ketik minimal 2 huruf..." autocomplete="off" required>
                            <input type="hidden" name="siswa_id" id="siswa_id">
                            <div id="siswa_search_results" class="list-group position-absolute w-100 shadow-sm d-none" style="z-index: 1056; max-height: 220px; overflow-y: auto;"></div>
                        </div>
                        <small class="text-muted">Pilih siswa untuk otomatis memfilter item pembayaran sesuai kategori siswa (Mondok/Non Mondok/Alumni/Non Alumni).</small>
                        <small class="text-muted">Pilih dari hasil pencarian agar data siswa valid.</small>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Item Pembayaran</label>
                        <div class="dropdown w-100" data-bs-auto-close="outside">
                            <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start py-2 px-3" type="button" id="itemDropdownButtonTagihan" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="min-height: 48px; white-space: normal;">
                                Pilih Item
                            </button>
                            <div class="dropdown-menu p-2 shadow-sm border-0" aria-labelledby="itemDropdownButtonTagihan" id="itemDropdownMenuTagihan" style="max-height: 420px; overflow-y: auto; width: max(100%, 620px);">
                                <div class="dropdown-item rounded px-2 py-2 mb-1 d-flex align-items-center gap-2 border-bottom">
                                    <input class="form-check-input mt-0" type="checkbox" id="checkAllItemTagihan">
                                    <label class="small fw-semibold w-100 mb-0" for="checkAllItemTagihan">Pilih Semua Item</label>
                                </div>
                                @foreach($items as $item)
                                    <div class="dropdown-item rounded px-2 py-2 mb-1 item-option-tagihan d-flex align-items-center gap-2" data-berlaku="{{ $item->berlaku_untuk }}">
                                        <input class="form-check-input item-checkbox-tagihan mt-0" type="checkbox" name="item_pembayaran_ids[]" value="{{ $item->id }}" data-label="{{ $item->kode }} - {{ $item->nama_item }} - Rp {{ number_format((float) ($item->nominal ?? 0), 0, ',', '.') }} ({{ ['non_mondok' => 'Tidak Mondok', 'non_alumni' => 'Non Alumni'][$item->berlaku_untuk] ?? ucfirst($item->berlaku_untuk) }})" data-nominal="{{ (float) ($item->nominal ?? 0) }}" data-berlaku="{{ $item->berlaku_untuk }}" id="itemTagihan{{ $item->id }}" {{ in_array((string) $item->id, array_map('strval', old('item_pembayaran_ids', [])), true) ? 'checked' : '' }}>
                                        <label class="small w-100 lh-sm mb-0" for="itemTagihan{{ $item->id }}">
                                            <span class="d-block fw-semibold">{{ $item->kode }} - {{ $item->nama_item }}</span>
                                            <span class="d-block text-muted">Rp {{ number_format((float) ($item->nominal ?? 0), 0, ',', '.') }} · {{ ['non_mondok' => 'Tidak Mondok', 'non_alumni' => 'Non Alumni'][$item->berlaku_untuk] ?? ucfirst($item->berlaku_untuk) }}</span>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small d-block">Bulan (centang bisa lebih dari satu)</label>
                        <div class="dropdown w-100" data-bs-auto-close="outside">
                            <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start py-2 px-3" type="button" id="bulanDropdownButtonTagihan" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" style="min-height: 44px; white-space: normal;">
                                Pilih Bulan
                            </button>
                            <div class="dropdown-menu p-3 shadow-sm border-0" id="bulanDropdownMenuTagihan" style="max-height: 300px; overflow-y: auto; width: max(100%, 420px);">
                                <div class="dropdown-item rounded px-3 py-2 mb-2 d-flex align-items-center gap-2 border-bottom">
                                    <input class="form-check-input mt-0" type="checkbox" id="checkAllBulanTagihan">
                                    <label class="small fw-semibold w-100 mb-0" for="checkAllBulanTagihan">Pilih Semua Bulan</label>
                                </div>
                                @foreach($bulanOptions as $bulanAngka => $bulanNama)
                                    <div class="dropdown-item rounded px-3 py-2 mb-2 bulan-option-tagihan d-flex align-items-center gap-2">
                                        <input class="form-check-input bulan-checkbox-tagihan mt-0" type="checkbox" name="periode_bulan[]" value="{{ $bulanAngka }}" id="bulanTagihan{{ $bulanAngka }}" {{ in_array((string) $bulanAngka, array_map('strval', old('periode_bulan', [])), true) ? 'checked' : '' }}>
                                        <label class="small w-100 mb-0" for="bulanTagihan{{ $bulanAngka }}">{{ $bulanNama }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2"><label class="form-label small">Tahun</label><input type="number" name="periode_tahun" min="2000" max="2100" class="form-control"></div>
                    <div class="col-md-12"><label class="form-label small">Catatan</label><input name="catatan" class="form-control"></div>
                    <div class="col-12">
                        <div id="totalRingkasanModal" class="bg-light rounded p-3 border small d-none">
                            <div class="fw-semibold mb-1">Ringkasan Tagihan</div>
                            <div id="daftarItemModal"></div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between">
                                <span>Total Tagihan</span>
                                <span id="totalNominalModal" class="fw-bold text-primary">Rp 0</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan Tagihan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const siswaData = @json($siswaSearch);
        const itemDropdownButtonTagihan = document.getElementById('itemDropdownButtonTagihan');
        const itemCheckboxesTagihan = Array.from(document.querySelectorAll('.item-checkbox-tagihan'));
        const bulanDropdownButtonTagihan = document.getElementById('bulanDropdownButtonTagihan');
        const bulanCheckboxesTagihan = Array.from(document.querySelectorAll('.bulan-checkbox-tagihan'));
        const itemDropdownMenuTagihan = document.getElementById('itemDropdownMenuTagihan');
        const bulanDropdownMenuTagihan = document.getElementById('bulanDropdownMenuTagihan');

        [itemDropdownMenuTagihan, bulanDropdownMenuTagihan].forEach((menu) => {
            if (!menu) {
                return;
            }

            menu.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        });

        const searchInput = document.getElementById('siswa_search');
        const siswaIdInput = document.getElementById('siswa_id');
        const resultsBox = document.getElementById('siswa_search_results');
        const tagihanForm = searchInput ? searchInput.closest('form') : null;

        if (!searchInput || !siswaIdInput || !resultsBox || !tagihanForm) {
            return;
        }

        function hitungTotalModal() {
            const nominalMap = {};
            itemCheckboxesTagihan.forEach(function (cb) {
                if (cb.checked) {
                    const namaEl = cb.nextElementSibling?.querySelector('.fw-semibold');
                    const label = namaEl ? namaEl.textContent.trim() : (cb.getAttribute('data-label') || 'Item');
                    const nominal = parseFloat(cb.getAttribute('data-nominal')) || 0;
                    nominalMap[cb.value] = { label: label, nominal: nominal };
                }
            });

            const bulanCount = bulanCheckboxesTagihan.filter(function (cb) { return cb.checked; }).length;
            const elRingkasan = document.getElementById('totalRingkasanModal');
            const elDaftar = document.getElementById('daftarItemModal');
            const elTotal = document.getElementById('totalNominalModal');

            if (Object.keys(nominalMap).length === 0 || bulanCount === 0) {
                elRingkasan.classList.add('d-none');
                return;
            }

            let total = 0;
            let html = '';
            for (const key in nominalMap) {
                const item = nominalMap[key];
                const subtotal = item.nominal * bulanCount;
                total += subtotal;
                html += '<div class="d-flex justify-content-between"><span>' + item.label + '</span><span>Rp ' + Number(subtotal).toLocaleString('id-ID') + '</span></div>';
            }
            elDaftar.innerHTML = html;
            elTotal.textContent = 'Rp ' + Number(total).toLocaleString('id-ID');
            elRingkasan.classList.remove('d-none');
        }

        function setSelectedItemTagihan(label) {
            if (itemDropdownButtonTagihan) {
                itemDropdownButtonTagihan.textContent = label;
            }
        }

        function updateSelectedItemsTagihanLabel() {
            const selected = itemCheckboxesTagihan
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.getAttribute('data-label') || '')
                .filter(Boolean);

            if (selected.length === 0) {
                setSelectedItemTagihan('Pilih Item');
            } else if (selected.length <= 2) {
                setSelectedItemTagihan(selected.join(', '));
            } else {
                setSelectedItemTagihan(`${selected.length} item dipilih`);
            }

            hitungTotalModal();
        }

        const checkAllItemTagihanCheckbox = document.getElementById('checkAllItemTagihan');
        if (checkAllItemTagihanCheckbox) {
            checkAllItemTagihanCheckbox.addEventListener('change', function () {
                const isChecked = this.checked;
                itemCheckboxesTagihan.forEach(function (checkbox) {
                    const option = checkbox.closest('.item-option-tagihan');
                    const isVisible = !option || !option.classList.contains('d-none');
                    if (!checkbox.disabled && isVisible) {
                        checkbox.checked = isChecked;
                    }
                });
                updateSelectedItemsTagihanLabel();
            });
        }

        itemCheckboxesTagihan.forEach((checkbox) => {
            checkbox.addEventListener('change', function () {
                const allChecked = itemCheckboxesTagihan.every(function (cb) { return cb.checked || cb.disabled; });
                if (checkAllItemTagihanCheckbox) {
                    checkAllItemTagihanCheckbox.checked = allChecked;
                }
                updateSelectedItemsTagihanLabel();
            });
        });

        document.querySelectorAll('.item-option-tagihan').forEach((option) => {
            option.addEventListener('click', function (event) {
                if (event.target instanceof HTMLInputElement) {
                    return;
                }

                event.preventDefault();

                const checkbox = option.querySelector('.item-checkbox-tagihan');
                if (!checkbox || checkbox.disabled) {
                    return;
                }

                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        function updateBulanButtonLabelTagihan() {
            if (!bulanDropdownButtonTagihan) {
                return;
            }

            const selected = bulanCheckboxesTagihan
                .filter((checkbox) => checkbox.checked)
                .map((checkbox) => checkbox.nextElementSibling ? checkbox.nextElementSibling.textContent.trim() : '')
                .filter(Boolean);

            if (selected.length === 0) {
                bulanDropdownButtonTagihan.textContent = 'Pilih Bulan';
                return;
            }

            if (selected.length <= 2) {
                bulanDropdownButtonTagihan.textContent = selected.join(', ');
                return;
            }

            bulanDropdownButtonTagihan.textContent = `${selected.length} bulan dipilih`;
        }

        const checkAllBulanTagihanCheckbox = document.getElementById('checkAllBulanTagihan');
        if (checkAllBulanTagihanCheckbox) {
            checkAllBulanTagihanCheckbox.addEventListener('change', function () {
                const isChecked = this.checked;
                bulanCheckboxesTagihan.forEach(function (checkbox) {
                    if (!checkbox.disabled) {
                        checkbox.checked = isChecked;
                    }
                });
                updateBulanButtonLabelTagihan();
                hitungTotalModal();
            });
        }

        bulanCheckboxesTagihan.forEach((checkbox) => {
            checkbox.addEventListener('change', function () {
                const allChecked = bulanCheckboxesTagihan.every(function (cb) { return cb.checked || cb.disabled; });
                if (checkAllBulanTagihanCheckbox) {
                    checkAllBulanTagihanCheckbox.checked = allChecked;
                }
                updateBulanButtonLabelTagihan();
                hitungTotalModal();
            });
        });

        document.querySelectorAll('.bulan-option-tagihan').forEach((option) => {
            option.addEventListener('click', function (event) {
                if (event.target instanceof HTMLInputElement || event.target instanceof HTMLLabelElement) {
                    return;
                }

                const checkbox = option.querySelector('.bulan-checkbox-tagihan');
                if (!checkbox || checkbox.disabled) {
                    return;
                }

                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });

        updateSelectedItemsTagihanLabel();
        updateBulanButtonLabelTagihan();

        const hideResults = () => {
            resultsBox.classList.add('d-none');
            resultsBox.innerHTML = '';
        };

        const renderResults = (keyword) => {
            const q = keyword.trim().toLowerCase();

            if (q.length < 2) {
                hideResults();
                return;
            }

            const filtered = siswaData
                .filter((siswa) => (`${siswa.nis} ${siswa.nama}`).toLowerCase().includes(q))
                .slice(0, 10);

            if (filtered.length === 0) {
                resultsBox.innerHTML = '<div class="list-group-item small text-muted">Siswa tidak ditemukan.</div>';
                resultsBox.classList.remove('d-none');
                return;
            }

            const kategoriLabelMap = { mondok: 'Mondok', non_mondok: 'Non Mondok', alumni: 'Alumni', non_alumni: 'Non Alumni' };

            resultsBox.innerHTML = filtered.map((siswa) => {
                const labelKategori = kategoriLabelMap[siswa.kategori] || siswa.kategori;
                return `<button type="button" class="list-group-item list-group-item-action" data-id="${siswa.id}" data-label="${siswa.nis} - ${siswa.nama} (${labelKategori})">` +
                    `<div class="fw-semibold">${siswa.nis} - ${siswa.nama}</div>` +
                    `<small class="text-muted">${labelKategori}</small>` +
                `</button>`;
            }).join('');

            resultsBox.classList.remove('d-none');
        };

        const itemOptionsTagihan = Array.from(document.querySelectorAll('.item-option-tagihan'));

        function filterItemsByPilihanSiswa(siswa) {
            const kategori = siswa ? siswa.kategori : null;

            itemOptionsTagihan.forEach((option) => {
                const berlaku = option.getAttribute('data-berlaku');
                const visible = !kategori || berlaku === 'semua' || berlaku === kategori;
                option.classList.toggle('d-none', !visible);
            });

            itemCheckboxesTagihan.forEach((checkbox) => {
                if (checkbox.checked && checkbox.closest('.item-option-tagihan')?.classList.contains('d-none')) {
                    checkbox.checked = false;
                }
            });

            if (checkAllItemTagihanCheckbox) {
                checkAllItemTagihanCheckbox.checked = false;
                checkAllItemTagihanCheckbox.indeterminate = false;
            }

            updateSelectedItemsTagihanLabel();
        }

        searchInput.addEventListener('input', function () {
            siswaIdInput.value = '';
            renderResults(this.value);
            filterItemsByPilihanSiswa(null);
        });

        resultsBox.addEventListener('click', function (event) {
            const button = event.target.closest('button[data-id]');
            if (!button) {
                return;
            }

            siswaIdInput.value = button.getAttribute('data-id') || '';
            searchInput.value = button.getAttribute('data-label') || '';
            hideResults();

            const siswaTerpilih = siswaData.find((siswa) => String(siswa.id) === siswaIdInput.value);
            filterItemsByPilihanSiswa(siswaTerpilih || null);
        });

        document.addEventListener('click', function (event) {
            if (!event.target.closest('#siswa_search_results') && !event.target.closest('#siswa_search')) {
                hideResults();
            }
        });

        tagihanForm.addEventListener('submit', function (event) {
            if (!siswaIdInput.value) {
                event.preventDefault();
                alert('Pilih siswa dari hasil pencarian terlebih dahulu.');
                searchInput.focus();
            }
        });

        const openPotonganModalTagihanId = @json(session('open_potongan_modal_tagihan_id'));
        if (openPotonganModalTagihanId) {
            const modalElement = document.getElementById(`modalPotongan${openPotonganModalTagihanId}`);
            if (modalElement && window.bootstrap && window.bootstrap.Modal) {
                window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
            }
        }
    });
</script>

<style>
    .item-option-tagihan {
        cursor: pointer;
        user-select: none;
        transition: background-color 0.15s ease;
    }

    .item-option-tagihan:hover {
        background-color: #eef4ff;
    }

    .item-option-tagihan:active {
        background-color: #dde9ff;
    }

    /* Pagination client-side di dalam modal tagihan */
    .paginasi-tagihan-wrapper .pagination .page-link {
        border-radius: 6px;
        margin: 0 2px;
        min-width: 34px;
        text-align: center;
        font-size: 0.82rem;
    }

    .paginasi-tagihan-wrapper .pagination .page-item.active .page-link {
        background-color: #0d6efd;
        border-color: #0d6efd;
    }
</style>

<script>
(function () {
    const PER_PAGE = 20; // Jumlah tagihan per halaman di dalam modal

    /**
     * Inisialisasi pagination client-side untuk tabel tagihan di dalam modal.
     * Semua baris sudah ada di DOM; JS hanya menyembunyikan/menampilkan
     * baris sesuai halaman aktif.
     *
     * @param {string|number} siswaId
     */
    function initPaginasiTagihan(siswaId) {
        const rows  = Array.from(document.querySelectorAll('.tagihan-row-' + siswaId));
        const total = rows.length;

        const wrapperEl = document.getElementById('paginasiTagihan' + siswaId);
        const infoEl    = document.getElementById('infoHalamanTagihan' + siswaId);
        const ulEl      = document.getElementById('ulPaginasiTagihan' + siswaId);

        if (total === 0) return;

        if (total <= PER_PAGE) {
            // Tidak perlu pagination: tampilkan semua baris
            rows.forEach(function (row) { row.style.display = ''; });
            if (wrapperEl) wrapperEl.style.setProperty('display', 'none', 'important');
            return;
        }

        // Tampilkan wrapper pagination
        if (wrapperEl) {
            wrapperEl.style.removeProperty('display');
            wrapperEl.style.display = 'flex';
        }

        if (!infoEl || !ulEl) return;

        const totalPages = Math.ceil(total / PER_PAGE);

        function tampilHalaman(page) {
            const start = (page - 1) * PER_PAGE;
            const end   = start + PER_PAGE;

            rows.forEach(function (row, idx) {
                row.style.display = (idx >= start && idx < end) ? '' : 'none';
            });

            // Info teks
            infoEl.textContent = 'Menampilkan ' + (start + 1) + '\u2013' + Math.min(end, total) + ' dari ' + total + ' tagihan';

            // Render navigasi halaman
            renderNav(page);
        }

        function renderNav(page) {
            ulEl.innerHTML = '';

            // Tombol « Prev
            var liPrev = buatLi(page === 1);
            var btnPrev = buatBtn('\u2039');
            if (page > 1) btnPrev.addEventListener('click', function () { tampilHalaman(page - 1); });
            liPrev.appendChild(btnPrev);
            ulEl.appendChild(liPrev);

            // Range halaman: ±2 dari halaman aktif + elipsis
            var delta = 2;
            var range = [];
            for (var i = Math.max(1, page - delta); i <= Math.min(totalPages, page + delta); i++) {
                range.push(i);
            }

            if (range[0] > 1) {
                ulEl.appendChild(buatPageBtn(1, page));
                if (range[0] > 2) ulEl.appendChild(buatEllipsis());
            }

            range.forEach(function (p) { ulEl.appendChild(buatPageBtn(p, page)); });

            var last = range[range.length - 1];
            if (last < totalPages) {
                if (last < totalPages - 1) ulEl.appendChild(buatEllipsis());
                ulEl.appendChild(buatPageBtn(totalPages, page));
            }

            // Tombol Next »
            var liNext = buatLi(page === totalPages);
            var btnNext = buatBtn('\u203a');
            if (page < totalPages) btnNext.addEventListener('click', function () { tampilHalaman(page + 1); });
            liNext.appendChild(btnNext);
            ulEl.appendChild(liNext);
        }

        function buatLi(disabled) {
            var li = document.createElement('li');
            li.className = 'page-item' + (disabled ? ' disabled' : '');
            return li;
        }

        function buatBtn(label) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'page-link';
            btn.innerHTML = label;
            return btn;
        }

        function buatPageBtn(p, currentPage) {
            var li  = buatLi(false);
            var btn = buatBtn(p);
            if (p === currentPage) {
                li.classList.add('active');
            } else {
                btn.addEventListener('click', function () { tampilHalaman(p); });
            }
            li.appendChild(btn);
            return li;
        }

        function buatEllipsis() {
            var li   = document.createElement('li');
            li.className = 'page-item disabled';
            var span = document.createElement('span');
            span.className = 'page-link';
            span.textContent = '\u2026';
            li.appendChild(span);
            return li;
        }

        // Tampilkan halaman pertama saat inisialisasi
        tampilHalaman(1);
    }

    // Pasang listener pada setiap modal "Tinjau Tagihan"
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[id^="modalTinjauTagihan"]').forEach(function (modalEl) {
            modalEl.addEventListener('show.bs.modal', function () {
                var siswaId = this.id.replace('modalTinjauTagihan', '');
                // Inisialisasi hanya sekali per modal
                if (!this.dataset.paginasiReady) {
                    this.dataset.paginasiReady = '1';
                    initPaginasiTagihan(siswaId);
                }
            });
        });
    });
})();
</script>
@endsection
