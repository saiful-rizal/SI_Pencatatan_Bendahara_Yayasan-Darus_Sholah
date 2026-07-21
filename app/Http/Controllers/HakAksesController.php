<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class HakAksesController extends Controller
{
    public function index()
    {
        $users = User::query()->orderByDesc('is_super_permanent')->orderBy('name')->get();

        return view('admin.hak-akses', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'in:super_admin,admin_anggota'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        $user->is_super_permanent = false;
        $user->save();

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'in:super_admin,admin_anggota'],
        ]);

        if ($user->is_super_permanent && $validated['role'] !== 'super_admin') {
            return back()->with('error', 'Super Admin permanen tidak dapat diubah role-nya.');
        }

        if ($user->id === auth()->id() && $validated['role'] !== 'super_admin') {
            return back()->with('error', 'Akun Anda sendiri tidak dapat diturunkan dari Super Admin.');
        }

        $superAdminCount = User::query()->where('role', 'super_admin')->count();
        if ($user->role === 'super_admin' && $validated['role'] !== 'super_admin' && $superAdminCount <= 1) {
            return back()->with('error', 'Minimal harus ada satu Super Admin aktif.');
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->is_super_permanent) {
            return back()->with('error', 'Super Admin permanen tidak dapat dihapus.');
        }

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->role === 'super_admin') {
            $superAdminCount = User::query()->where('role', 'super_admin')->count();
            if ($superAdminCount <= 1) {
                return back()->with('error', 'Minimal harus ada satu Super Admin aktif.');
            }
        }

        $user->delete();

        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
