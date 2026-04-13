@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1">Hak Akses</h4>
        <small class="text-muted">Kelola akun admin super dan admin anggota.</small>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTambahUser">+ Tambah Pengguna</button>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status Akun</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge {{ $user->role === 'super_admin' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ $user->role === 'super_admin' ? 'Super Admin' : 'Admin Anggota' }}
                                </span>
                            </td>
                            <td>
                                @if($user->is_super_permanent)
                                    <span class="badge bg-success">Permanen</span>
                                @else
                                    <span class="badge bg-light text-dark border">Biasa</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#modalEditUser{{ $user->id }}">Edit</button>
                                @if(!$user->is_super_permanent)
                                    <form action="{{ route('hak-akses.destroy', $user->id) }}" method="POST" class="d-inline" id="delete-user-{{ $user->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-confirm"
                                            data-form-id="delete-user-{{ $user->id }}"
                                            data-delete-label="pengguna {{ $user->name }}">
                                            Hapus
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">Belum ada data pengguna.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="modalTambahUser" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form action="{{ route('hak-akses.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body row g-2">
                    <div class="col-12"><label class="form-label small">Nama</label><input name="name" class="form-control" required></div>
                    <div class="col-12"><label class="form-label small">Email</label><input type="email" name="email" class="form-control" required></div>
                    <div class="col-12">
                        <label class="form-label small">Password</label>
                        <div class="input-group">
                            <input type="password" name="password" class="form-control password-toggle-input" required>
                            <button class="btn btn-outline-secondary password-toggle-btn" type="button"><i class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="col-12"><label class="form-label small">Role</label>
                        <select name="role" class="form-select" required>
                            <option value="admin_anggota">Admin Anggota</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

@foreach($users as $user)
    <div class="modal fade" id="modalEditUser{{ $user->id }}" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form action="{{ route('hak-akses.update', $user->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Edit Pengguna</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row g-2">
                        <div class="col-12"><label class="form-label small">Nama</label><input name="name" class="form-control" value="{{ $user->name }}" required></div>
                        <div class="col-12"><label class="form-label small">Email</label><input type="email" name="email" class="form-control" value="{{ $user->email }}" required></div>
                        <div class="col-12">
                            <label class="form-label small">Password Baru (opsional)</label>
                            <div class="input-group">
                                <input type="password" name="password" class="form-control password-toggle-input">
                                <button class="btn btn-outline-secondary password-toggle-btn" type="button"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div class="col-12"><label class="form-label small">Role</label>
                            <select name="role" class="form-select" {{ $user->is_super_permanent ? 'disabled' : '' }}>
                                <option value="admin_anggota" {{ $user->role === 'admin_anggota' ? 'selected' : '' }}>Admin Anggota</option>
                                <option value="super_admin" {{ $user->role === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                            </select>
                            @if($user->is_super_permanent)
                                <input type="hidden" name="role" value="super_admin">
                                <small class="text-muted">Akun super admin permanen tidak dapat diubah role-nya.</small>
                            @endif
                        </div>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.password-toggle-btn').forEach((button) => {
            button.addEventListener('click', function () {
                const inputGroup = this.closest('.input-group');
                const input = inputGroup ? inputGroup.querySelector('.password-toggle-input') : null;
                const icon = this.querySelector('i');

                if (!input || !icon) {
                    return;
                }

                const isPassword = input.getAttribute('type') === 'password';
                input.setAttribute('type', isPassword ? 'text' : 'password');
                icon.classList.toggle('fa-eye', !isPassword);
                icon.classList.toggle('fa-eye-slash', isPassword);
            });
        });
    });
</script>
@endsection
