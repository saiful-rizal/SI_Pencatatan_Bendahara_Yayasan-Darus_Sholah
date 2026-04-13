@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Data Siswa</h4>
            <small class="text-muted">Master data siswa, detail tanggungan, dan biaya lunas per siswa.</small>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-warning" type="button" data-bs-toggle="modal" data-bs-target="#modalNaikKelas">
                <i class="fas fa-arrow-up me-1"></i> Naik Kelas
            </button>
            <button class="btn btn-outline-dark" type="button" data-bs-toggle="modal" data-bs-target="#modalImportExcel">
                Import Excel
            </button>
            <a href="{{ route('siswa.export', request()->query()) }}" class="btn btn-success">
                <i class="fas fa-file-excel me-1"></i> Export Excel
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahSiswa">+ Tambah Siswa</button>
        </div>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2"><small class="text-muted">Kelas 10</small>
                    <h6 class="mb-0">{{ $ringkasJenjang['10'] ?? ($ringkasJenjang['X'] ?? 0) }} siswa</h6>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2"><small class="text-muted">Kelas 11</small>
                    <h6 class="mb-0">{{ $ringkasJenjang['11'] ?? ($ringkasJenjang['XI'] ?? 0) }} siswa</h6>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2"><small class="text-muted">Kelas 12</small>
                    <h6 class="mb-0">{{ $ringkasJenjang['12'] ?? ($ringkasJenjang['XII'] ?? 0) }} siswa</h6>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2"><small class="text-muted">Data Ditampilkan</small>
                    <h6 class="mb-0">{{ $siswas->total() }} siswa</h6>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-12">
                    <form method="GET" action="{{ route('siswa.index') }}" class="row g-2">
                        <div class="col-12 col-md-4"><label class="form-label small">Cari NIS / Nama</label><input
                                name="q" class="form-control" value="{{ $q ?? '' }}"></div>
                        <div class="col-6 col-md-2"><label class="form-label small">Jenjang</label><select name="jenjang"
                                class="form-select">
                                <option value="">Semua</option>
                            <option value="10" {{ in_array(strtoupper($jenjang ?? ''), ['10', 'X'], true) ? 'selected' : '' }}>10</option>
                            <option value="11" {{ in_array(strtoupper($jenjang ?? ''), ['11', 'XI'], true) ? 'selected' : '' }}>11</option>
                            <option value="12" {{ in_array(strtoupper($jenjang ?? ''), ['12', 'XII'], true) ? 'selected' : '' }}>12</option>
                            </select></div>
                        <div class="col-6 col-md-2"><label class="form-label small">Status</label><select name="status"
                                class="form-select">
                                <option value="">Semua</option>
                                <option value="aktif" {{ ($status ?? '') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                                <option value="lulus" {{ ($status ?? '') === 'lulus' ? 'selected' : '' }}>Lulus</option>
                            </select></div>
                        <div class="col-6 col-md-2"><label class="form-label small">Per Halaman</label><select
                                name="per_page" class="form-select">
                                <option value="10" {{ ($perPage ?? 10) == 10 ? 'selected' : '' }}>10</option>
                                <option value="25" {{ ($perPage ?? 10) == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ ($perPage ?? 10) == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ ($perPage ?? 10) == 100 ? 'selected' : '' }}>100</option>
                            </select></div>
                        <div class="col-6 col-md-1 d-grid"><label class="form-label small">&nbsp;</label><button
                            class="btn btn-primary" type="submit">Cari</button></div>
                        <div class="col-6 col-md-1 d-grid"><label class="form-label small">&nbsp;</label><a
                                href="{{ route('siswa.index') }}" class="btn btn-outline-secondary">Reset</a></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th class="text-center" style="width: 44px;">
                                <input type="checkbox" id="checkAllSiswa" class="form-check-input" title="Pilih semua">
                            </th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Kategori</th>
                            <th>Status</th>
                            <th>Sisa Tanggungan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siswas as $siswa)
                            @php
                                $sisa = $siswa->tagihans->sum(function ($tagihan) {
                                    $potongan = (float) ($tagihan->total_potongan ?? 0);
                                    $bayar = (float) ($tagihan->total_pembayaran ?? 0);
                                    return max(0, (float) $tagihan->nominal_awal - $potongan - $bayar);
                                });
                                $sisa = round($sisa, 2);
                            @endphp
                            <tr>
                                <td class="text-center">
                                    <input type="checkbox" class="form-check-input siswa-select-checkbox" value="{{ $siswa->id }}" {{ $siswa->status !== 'aktif' ? 'disabled' : '' }}>
                                </td>
                                <td>{{ $siswa->nis }}</td>
                                <td>{{ $siswa->nama }}</td>
                                <td>{{ $siswa->kelas }}</td>
                                <td>
                                    <span
                                        class="badge {{ $siswa->kategori === 'mondok' ? 'bg-primary' : 'bg-secondary' }}">
                                        {{ $siswa->kategori === 'mondok' ? 'Mondok' : 'Non Mondok' }}
                                    </span>
                                </td>
                                <td>
                                    @php
                                        $statusLabel = match ($siswa->status) {
                                            'aktif' => 'Aktif',
                                            'tidak_aktif' => 'Tidak Aktif',
                                            'lulus' => 'Lulus',
                                            default => ucfirst((string) $siswa->status),
                                        };
                                        $statusClass = match ($siswa->status) {
                                            'aktif' => 'bg-success',
                                            'tidak_aktif' => 'bg-secondary',
                                            'lulus' => 'bg-dark',
                                            default => 'bg-dark',
                                        };
                                    @endphp
                                    <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td>
                                    @if($siswa->tagihans->isEmpty())
                                        <span class="badge bg-secondary">Belum Ada Tagihan</span>
                                    @elseif($sisa <= 0)
                                        <span class="badge bg-success">Lunas</span>
                                    @else
                                        <span class="fw-bold text-danger">Rp {{ number_format($sisa, 0, ',', '.') }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#modalDetail{{ $siswa->id }}">Lihat Detail</button>
                                    <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal"
                                        data-bs-target="#modalEditSiswa{{ $siswa->id }}">Edit</button>
                                    <form action="{{ route('siswa.destroy', $siswa->id) }}" method="POST"
                                        class="d-inline" id="delete-siswa-{{ $siswa->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"
                                            data-form-id="delete-siswa-{{ $siswa->id }}"
                                            data-delete-label="siswa {{ $siswa->nama }} ({{ $siswa->nis }})">
                                            Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">Belum ada data siswa.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white">{{ $siswas->links() }}</div>
    </div>

    @foreach ($siswas as $siswa)
        <div class="modal fade" id="modalDetail{{ $siswa->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Detail Siswa - {{ $siswa->nama }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        @if($siswa->tagihans->isEmpty())
                            <div class="alert alert-secondary mb-3">Siswa ini belum memiliki tagihan.</div>
                        @endif

                        @php
                            $statusTagihan = $siswa->status_tagihan;
                            $statusTagihanClass = $statusTagihan === 'lunas'
                                ? 'bg-success'
                                : ($statusTagihan === 'belum ada tagihan' ? 'bg-secondary' : 'bg-warning text-dark');
                        @endphp

                        <div class="mb-3">
                            <small class="text-muted d-block">Status Tagihan</small>
                            <span class="badge {{ $statusTagihanClass }}">{{ ucwords($statusTagihan) }}</span>
                        </div>

                        @php
                            $riwayatTagihan = $siswa->tagihans->map(function ($tagihan) {
                                $potongan = (float) ($tagihan->total_potongan ?? 0);
                                $bayar = (float) ($tagihan->total_pembayaran ?? 0);
                                $sisaTagihan = max(0, (float) $tagihan->nominal_awal - $potongan - $bayar);
                                // Round to 2 decimals to avoid floating point precision issues
                                $sisaTagihan = round($sisaTagihan, 2);

                                return [
                                    'tagihan' => $tagihan,
                                    'potongan' => $potongan,
                                    'bayar' => $bayar,
                                    'sisa' => $sisaTagihan,
                                ];
                            });

                            $riwayatBelumLunas = $riwayatTagihan->filter(fn ($row) => $row['sisa'] > 0)->values();
                            $riwayatLunas = $riwayatTagihan->filter(fn ($row) => $row['sisa'] <= 0)->values();
                        @endphp

                        <h6 class="fw-semibold mb-2">Riwayat Belum Lunas</h6>
                        <div class="table-responsive mb-3">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Kelas Tagihan</th>
                                        <th>Periode</th>
                                        <th>Nominal</th>
                                        <th>Potongan</th>
                                        <th>Sudah Dibayar</th>
                                        <th>Sisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($riwayatBelumLunas as $row)
                                        <tr>
                                            <td>{{ $row['tagihan']->itemPembayaran->nama_item ?? '-' }}</td>
                                            <td>{{ $row['tagihan']->kelas ?? '-' }}</td>
                                            <td>{{ $row['tagihan']->periode_label }}</td>
                                            <td>Rp {{ number_format($row['tagihan']->nominal_awal, 0, ',', '.') }}</td>
                                            <td>
                                                Rp {{ number_format($row['potongan'], 0, ',', '.') }}
                                                @php
                                                    $keteranganPotongan = $row['tagihan']->potongans->pluck('keterangan')->filter()->unique()->implode(', ');
                                                @endphp
                                                @if($keteranganPotongan !== '')
                                                    <div><small class="text-muted">{{ $keteranganPotongan }}</small></div>
                                                @endif
                                            </td>
                                            <td>Rp {{ number_format($row['bayar'], 0, ',', '.') }}</td>
                                            <td class="fw-semibold text-danger">Rp {{ number_format($row['sisa'], 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Tidak ada riwayat belum lunas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <h6 class="fw-semibold mb-2">Riwayat Lunas</h6>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Kelas Tagihan</th>
                                        <th>Periode</th>
                                        <th>Nominal</th>
                                        <th>Potongan</th>
                                        <th>Sudah Dibayar</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($riwayatLunas as $row)
                                        <tr>
                                            <td>{{ $row['tagihan']->itemPembayaran->nama_item ?? '-' }}</td>
                                            <td>{{ $row['tagihan']->kelas ?? '-' }}</td>
                                            <td>{{ $row['tagihan']->periode_label }}</td>
                                            <td>Rp {{ number_format($row['tagihan']->nominal_awal, 0, ',', '.') }}</td>
                                            <td>
                                                Rp {{ number_format($row['potongan'], 0, ',', '.') }}
                                                @php
                                                    $keteranganPotongan = $row['tagihan']->potongans->pluck('keterangan')->filter()->unique()->implode(', ');
                                                @endphp
                                                @if($keteranganPotongan !== '')
                                                    <div><small class="text-muted">{{ $keteranganPotongan }}</small></div>
                                                @endif
                                            </td>
                                            <td>Rp {{ number_format($row['bayar'], 0, ',', '.') }}</td>
                                            <td><span class="badge bg-success">Lunas</span></td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">Belum ada riwayat lunas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('siswa.export', ['q' => $siswa->nis]) }}" class="btn btn-success">
                            <i class="fas fa-file-excel me-1"></i> Export Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    @foreach ($siswas as $siswa)
        <div class="modal fade" id="modalEditSiswa{{ $siswa->id }}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('siswa.update', $siswa->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header">
                            <h5 class="modal-title">Edit Siswa - {{ $siswa->nama }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body row g-2">
                            <div class="col-md-6"><label class="form-label small">NIS</label><input name="nis"
                                    class="form-control" value="{{ $siswa->nis }}" required></div>
                            <div class="col-md-6"><label class="form-label small">Nama</label><input name="nama"
                                    class="form-control" value="{{ $siswa->nama }}" required></div>
                            <div class="col-md-6"><label class="form-label small">Jenis Kelamin</label><select
                                    name="jenis_kelamin" class="form-select">
                                    <option value="L" {{ $siswa->jenis_kelamin === 'L' ? 'selected' : '' }}>L</option>
                                    <option value="P" {{ $siswa->jenis_kelamin === 'P' ? 'selected' : '' }}>P</option>
                                </select></div>
                            <div class="col-md-6"><label class="form-label small">Kelas</label><select name="kelas"
                                    class="form-select" required>
                                    <option value="10" {{ str_starts_with((string) $siswa->kelas, '10') ? 'selected' : '' }}>10</option>
                                    <option value="11" {{ str_starts_with((string) $siswa->kelas, '11') ? 'selected' : '' }}>11</option>
                                    <option value="12" {{ str_starts_with((string) $siswa->kelas, '12') ? 'selected' : '' }}>12</option>
                                </select></div>
                            <div class="col-md-6"><label class="form-label small">Angkatan</label><input name="angkatan"
                                    class="form-control" value="{{ $siswa->angkatan }}" required></div>
                            <div class="col-md-6"><label class="form-label small">Kategori</label><select name="kategori"
                                    class="form-select">
                                    <option value="non_mondok" {{ $siswa->kategori === 'non_mondok' ? 'selected' : '' }}>Non Mondok</option>
                                    <option value="mondok" {{ $siswa->kategori === 'mondok' ? 'selected' : '' }}>Mondok</option>
                                </select></div>
                            <div class="col-md-6"><label class="form-label small">Status</label><select name="status"
                                    class="form-select">
                                    <option value="aktif" {{ $siswa->status === 'aktif' ? 'selected' : '' }}>Aktif</option>
                                    <option value="tidak_aktif" {{ $siswa->status === 'tidak_aktif' ? 'selected' : '' }}>Tidak Aktif</option>
                                    <option value="lulus" {{ $siswa->status === 'lulus' ? 'selected' : '' }}>Lulus</option>
                                </select></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                            <button class="btn btn-primary">Simpan Perubahan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="modalTambahSiswa" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('siswa.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Siswa</h5><button type="button" class="btn-close"
                            data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-2">
                        <div class="col-md-6"><label class="form-label small">NIS</label><input name="nis"
                                class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small">Nama</label><input name="nama"
                                class="form-control" required></div>
                        <div class="col-md-6"><label class="form-label small">Jenis Kelamin</label><select
                                name="jenis_kelamin" class="form-select">
                                <option value="L">L</option>
                                <option value="P">P</option>
                            </select></div>
                        <div class="col-md-6"><label class="form-label small">Kelas</label><select name="kelas"
                                class="form-select" required>
                                <option value="10">10</option>
                                <option value="11">11</option>
                                <option value="12">12</option>
                            </select></div>
                        <div class="col-md-6"><label class="form-label small">Angkatan</label><input name="angkatan"
                                class="form-control" value="{{ date('Y') }}" required></div>
                        <div class="col-md-6"><label class="form-label small">Kategori</label><select name="kategori"
                                class="form-select">
                                <option value="non_mondok">Non Mondok</option>
                                <option value="mondok">Mondok</option>
                            </select></div>
                        <div class="col-md-6"><label class="form-label small">Status</label><select name="status"
                                class="form-select">
                                <option value="aktif">Aktif</option>
                                <option value="tidak_aktif">Tidak Aktif</option>
                                <option value="lulus">Lulus</option>
                            </select></div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalImportExcel" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('siswa.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Import Data Siswa</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <label class="form-label small">File Excel</label>
                        <input type="file" name="file_excel" class="form-control" required>
                        <div class="alert alert-info mt-3 mb-0 small">
                            <div class="fw-semibold mb-1">Format Excel yang didukung</div>
                            <div class="mb-1">Baris pertama wajib berisi header kolom berikut:</div>
                            <code>nis | nama | kelas | kategori</code>
                            <div class="mt-2 mb-1">Contoh isi:</div>
                            <code>12345 | Ahmad Fauzi | 7A | mondok</code><br>
                            <code>12346 | Siti Aminah | 8B | non_mondok</code>
                            <div class="mt-2">Catatan: `kategori` hanya boleh <code>mondok</code> atau <code>non_mondok</code>.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button class="btn btn-dark">Import</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalNaikKelas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('siswa.naik-kelas') }}" method="POST" id="formNaikKelas">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Naik Kelas Massal</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Mode Pemilihan</label>
                            <select name="mode" id="naikKelasMode" class="form-select" required>
                                <option value="selected">Siswa Terpilih (checkbox)</option>
                                <option value="all">Semua Siswa Aktif</option>
                            </select>
                            <small class="text-muted">Anda bisa centang satu siswa, beberapa siswa, atau pakai pilih semua.</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold">Jenis Kenaikan</label>
                            <input type="text" class="form-control" value="Naik 1 tingkat" readonly>
                        </div>
                        <div id="selectedSiswaContainer"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">Proses Naik Kelas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const hasImportError = @json($errors->has('file_excel'));
            const checkAllSiswa = document.getElementById('checkAllSiswa');
            const siswaCheckboxes = document.querySelectorAll('.siswa-select-checkbox');
            const formNaikKelas = document.getElementById('formNaikKelas');
            const naikKelasMode = document.getElementById('naikKelasMode');
            const selectedSiswaContainer = document.getElementById('selectedSiswaContainer');

            if (hasImportError) {
                const importModalElement = document.getElementById('modalImportExcel');
                if (importModalElement && window.bootstrap && window.bootstrap.Modal) {
                    window.bootstrap.Modal.getOrCreateInstance(importModalElement).show();
                }
            }

            if (checkAllSiswa) {
                checkAllSiswa.addEventListener('change', function () {
                    siswaCheckboxes.forEach((checkbox) => {
                        if (!checkbox.disabled) {
                            checkbox.checked = checkAllSiswa.checked;
                        }
                    });
                });
            }

            if (formNaikKelas && selectedSiswaContainer && naikKelasMode) {
                formNaikKelas.addEventListener('submit', function (event) {
                    selectedSiswaContainer.innerHTML = '';

                    if (naikKelasMode.value === 'selected') {
                        const checkedIds = Array.from(document.querySelectorAll('.siswa-select-checkbox:checked')).map((checkbox) => checkbox.value);

                        if (checkedIds.length === 0) {
                            event.preventDefault();
                            if (typeof window.openInfoAlertModal === 'function') {
                                window.openInfoAlertModal('Pilih minimal satu siswa menggunakan checkbox terlebih dahulu.', 'Peringatan');
                            } else {
                                alert('Pilih minimal satu siswa menggunakan checkbox terlebih dahulu.');
                            }
                            return;
                        }

                        checkedIds.forEach((id) => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'siswa_ids[]';
                            input.value = id;
                            selectedSiswaContainer.appendChild(input);
                        });
                    }
                });
            }
        });
    </script>
@endsection
