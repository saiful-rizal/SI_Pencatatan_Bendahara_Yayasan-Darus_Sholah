@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1">Backup Tersimpan</h4>
        <p class="text-muted mb-0">Daftar backup yang tersimpan di server. Backup dijalankan otomatis setiap pukul 02:00.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('backup.database') }}" class="btn btn-primary">
            <i class="fas fa-download me-2"></i> Backup Baru
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nama File</th>
                        <th>Tipe</th>
                        <th>Ukuran</th>
                        <th>Terakhir Diubah</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($backups as $backup)
                        <tr>
                            <td class="fw-semibold">{{ $backup['filename'] }}</td>
                            <td><span class="badge bg-success">{{ $backup['type'] }}</span></td>
                            <td>{{ $backup['size_formatted'] }}</td>
                            <td>{{ $backup['last_modified_formatted'] }}</td>
                            <td class="text-center">
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('backup.database.stored.download', $backup['filename']) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i> Unduh
                                    </a>
                                    <form action="{{ route('backup.database.stored.delete', $backup['filename']) }}" method="POST" class="d-inline" id="delete-backup-{{ $loop->index }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"
                                            data-form-id="delete-backup-{{ $loop->index }}"
                                            data-delete-label="backup {{ $backup['filename'] }}">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                Belum ada backup tersimpan. Backup otomatis berjalan setiap pukul 02:00.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
