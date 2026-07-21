@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Backup Database</h4>
        <p class="text-muted mb-0">Pilih menu yang ingin dibackup, lalu unduh dalam format Excel.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('backup.database.stored') }}" class="btn btn-outline-primary">
            <i class="fas fa-folder-open me-2"></i> Lihat Backup Tersimpan
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <h5 class="fw-semibold mb-1">Format Backup</h5>
                        <small class="text-muted">Backup akan diunduh dalam format Excel (.xlsx), dipisahkan per sheet.</small>
                    </div>
                    <i class="fas fa-file-export fa-lg text-primary"></i>
                </div>

                <form action="{{ route('backup.database.download') }}" method="POST" id="backupForm">
                    @csrf

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download me-2"></i> Unduh Backup
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="btnPilihSemua">Pilih Semua Menu</button>
                        <button type="button" class="btn btn-outline-danger" id="btnKosongkan">Kosongkan Pilihan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <div>
                        <h5 class="fw-semibold mb-1">Pilihan Menu Backup</h5>
                        <small class="text-muted">Centang menu yang ingin disimpan. Jika tidak ada pilihan, sistem akan membackup semua data yang tersedia.</small>
                    </div>
                    <span class="badge bg-light text-dark border">10 tabel tersedia</span>
                </div>

                <div class="accordion" id="backupAccordion">
                    @foreach($backupGroups as $groupIndex => $group)
                        <div class="accordion-item border rounded-3 overflow-hidden mb-3">
                            <h2 class="accordion-header" id="heading{{ $groupIndex }}">
                                <button class="accordion-button {{ $groupIndex === 0 ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapse{{ $groupIndex }}" aria-expanded="{{ $groupIndex === 0 ? 'true' : 'false' }}" aria-controls="collapse{{ $groupIndex }}">
                                    <div class="text-start">
                                        <div class="fw-semibold">{{ $group['label'] }}</div>
                                        <small class="text-muted">{{ $group['description'] }}</small>
                                    </div>
                                </button>
                            </h2>
                            <div id="collapse{{ $groupIndex }}" class="accordion-collapse collapse {{ $groupIndex === 0 ? 'show' : '' }}" aria-labelledby="heading{{ $groupIndex }}" data-bs-parent="#backupAccordion">
                                <div class="accordion-body">
                                    <div class="row g-3">
                                        @foreach($group['tables'] as $table)
                                            <div class="col-md-6">
                                                <label class="border rounded-3 p-3 d-flex align-items-start gap-3 h-100 cursor-pointer">
                                                    <input type="checkbox" name="tables[]" value="{{ $table }}" class="form-check-input mt-1 backup-table-check" checked>
                                                    <span>
                                                        <span class="d-block fw-semibold">{{ $backupTables[$table]['label'] ?? $table }}</span>
                                                        <small class="text-muted">Tabel: {{ $table }}</small>
                                                    </span>
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="alert alert-info border-0 shadow-sm">
            <strong>Catatan:</strong> backup Excel akan dibuat per sheet sesuai menu yang dipilih.
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checks = Array.from(document.querySelectorAll('.backup-table-check'));
        const pilihSemua = document.getElementById('btnPilihSemua');
        const kosongkan = document.getElementById('btnKosongkan');

        if (pilihSemua) {
            pilihSemua.addEventListener('click', function () {
                checks.forEach(function (checkbox) {
                    checkbox.checked = true;
                });
            });
        }

        if (kosongkan) {
            kosongkan.addEventListener('click', function () {
                checks.forEach(function (checkbox) {
                    checkbox.checked = false;
                });
            });
        }
    });
</script>
@endsection
