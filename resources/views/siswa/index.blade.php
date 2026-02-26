@extends('layouts.app')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="fw-bold mb-1">Data Siswa</h4>
            <small class="text-muted">Master data siswa, detail tanggungan, dan biaya lunas per siswa.</small>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahSiswa">+ Tambah Siswa</button>
    </div>

    <div class="row g-2 mb-3">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2"><small class="text-muted">Kelas 10</small>
                    <h6 class="mb-0">{{ $ringkasJenjang['10'] ?? 0 }} siswa</h6>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2"><small class="text-muted">Kelas 11</small>
                    <h6 class="mb-0">{{ $ringkasJenjang['11'] ?? 0 }} siswa</h6>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-2"><small class="text-muted">Kelas 12</small>
                    <h6 class="mb-0">{{ $ringkasJenjang['12'] ?? 0 }} siswa</h6>
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
                <div class="col-md-8">
                    <form method="GET" class="row g-2">
                        <div class="col-12 col-md-4"><label class="form-label small">Cari NIS / Nama</label><input
                                name="q" class="form-control" value="{{ $q ?? '' }}"></div>
                        <div class="col-6 col-md-2"><label class="form-label small">Jenjang</label><select name="jenjang"
                                class="form-select">
                                <option value="">Semua</option>
                                <option value="10" {{ ($jenjang ?? '') === '10' ? 'selected' : '' }}>10</option>
                                <option value="11" {{ ($jenjang ?? '') === '11' ? 'selected' : '' }}>11</option>
                                <option value="12" {{ ($jenjang ?? '') === '12' ? 'selected' : '' }}>12</option>
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
                                class="btn btn-primary">Cari</button></div>
                        <div class="col-6 col-md-1 d-grid"><label class="form-label small">&nbsp;</label><a
                                href="{{ route('siswa.index') }}" class="btn btn-outline-secondary">Reset</a></div>
                    </form>
                </div>
                <div class="col-md-6">
                    <form action="{{ route('siswa.import') }}" method="POST" enctype="multipart/form-data"
                        class="row g-2">
                        @csrf
                        <div class="col-md-8"><label class="form-label small">Import Excel</label><input type="file"
                                name="file_excel" class="form-control" required></div>
                        <div class="col-md-4 d-grid"><label class="form-label small d-block">&nbsp;</label><button
                                class="btn btn-dark">Import</button></div>
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
                            @endphp
                            <tr>
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
                                    <span class="badge {{ $siswa->status === 'aktif' ? 'bg-success' : 'bg-dark' }}">
                                        {{ ucfirst($siswa->status) }}
                                    </span>
                                </td>
                                <td class="fw-bold text-danger">Rp {{ number_format($sisa, 0, ',', '.') }}</td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal"
                                        data-bs-target="#modalDetail{{ $siswa->id }}">Lihat Detail</button>
                                    <a href="{{ route('siswa.cetak', $siswa->id) }}" target="_blank"
                                        class="btn btn-sm btn-outline-dark">Cetak</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">Belum ada data siswa.</td>
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
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Periode</th>
                                        <th>Nominal</th>
                                        <th>Potongan</th>
                                        <th>Sudah Dibayar</th>
                                        <th>Sisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($siswa->tagihans as $tagihan)
                                        @php
                                            $potongan = (float) ($tagihan->total_potongan ?? 0);
                                            $bayar = (float) ($tagihan->total_pembayaran ?? 0);
                                            $sisaTagihan = max(0, (float) $tagihan->nominal_awal - $potongan - $bayar);
                                        @endphp
                                        <tr>
                                            <td>{{ $tagihan->itemPembayaran->nama_item ?? '-' }}</td>
                                            <td>{{ $tagihan->periode_bulan ? $tagihan->periode_bulan . '/' . $tagihan->periode_tahun : '-' }}
                                            </td>
                                            <td>Rp {{ number_format($tagihan->nominal_awal, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($potongan, 0, ',', '.') }}</td>
                                            <td>Rp {{ number_format($bayar, 0, ',', '.') }}</td>
                                            <td
                                                class="fw-semibold {{ $sisaTagihan > 0 ? 'text-danger' : 'text-success' }}">
                                                Rp {{ number_format($sisaTagihan, 0, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center text-muted">Belum ada tagihan</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <div class="modal fade" id="modalTambahSiswa" tabindex="-1">
        <div class="modal-dialog">
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
                        <div class="col-md-6"><label class="form-label small">Kelas</label><input name="kelas"
                                class="form-control" required></div>
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
                                <option value="lulus">Lulus</option>
                            </select></div>
                    </div>
                    <div class="modal-footer"><button class="btn btn-primary">Simpan</button></div>
                </form>
            </div>
        </div>
    </div>
@endsection
