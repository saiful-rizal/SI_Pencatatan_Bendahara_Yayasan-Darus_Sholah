<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\SendCodeResetMail;
use Illuminate\Validation\Rules\Password;

class ForgotPasswordController extends Controller
{
    // 1. Tampilkan Form Input Email
    public function showForgotForm()
    {
        return view('auth.forgot-password');
    }

    // 2. Kirim Kode ke Email
    public function sendCode(Request $request)
    {
        // Validasi: Pastikan email ada di tabel users
        $validated = $request->validate(['email' => ['required', 'email:rfc,dns', 'exists:users,email']]);

        // Hapus kode lama jika ada untuk email ini
        PasswordResetCode::where('email', $validated['email'])->delete();

        // Generate OTP 6 digit dengan random_int (cryptographically secure)
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Simpan ke Database dengan masa berlaku 15 menit
        PasswordResetCode::create([
            'email' => $validated['email'],
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(15),
        ]);

        // Kirim Email dengan Error Handling
        try {
            Mail::to($validated['email'])->send(new SendCodeResetMail($code));
        } catch (\Exception $e) {
            // Jika gagal kirim email (misal konfigurasi .env salah)
            return back()->with('error', 'Gagal mengirim email. Silakan periksa konfigurasi email (.env) atau coba lagi.');
        }

        // Simpan email di session untuk langkah verifikasi selanjutnya
        session(['password_reset_email' => $validated['email']]);

        // Redirect ke halaman verifikasi kode
        return redirect()->route('password.verify')->with('success', 'Kode telah dikirim ke email Anda.');
    }

    // 3. Tampilkan Form Input Kode
    public function showVerifyForm()
    {
        // Cek apakah user sudah memasukkan email di langkah 1?
        if (!session('password_reset_email')) {
            return redirect()->route('password.request');
        }
        return view('auth.verify-code');
    }

    // 4. Verifikasi Kode
    public function verifyCode(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $email = session('password_reset_email');

        // Cek kode di database
        $record = PasswordResetCode::where('email', $email)
            ->where('expires_at', '>', now()) // Cek apakah masih berlaku
            ->first();

        if (!$record || !Hash::check($validated['code'], $record->code)) {
            return back()->withErrors(['code' => 'Kode salah atau sudah kadaluarsa.']);
        }

        // Jika kode benar:
        // 1. Tandai di session bahwa email ini sudah verified
        session(['password_verified' => true]);
        // 2. Hapus kode dari DB agar tidak bisa dipakai 2 kali
        $record->delete();

        // Redirect ke halaman reset password
        return redirect()->route('password.reset.form');
    }

    // 5. Tampilkan Form Reset Password
    public function showResetForm()
    {
        // Hanya bisa akses jika kode sudah diverifikasi
        if (!session('password_verified')) {
            return redirect()->route('password.request');
        }
        return view('auth.reset-password');
    }

    // 6. Proses Update Password
    public function resetPassword(Request $request)
    {
        // Validasi: Password minimal 6 karakter dan harus sama dengan field 'password_confirmation'
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);

        $email = session('password_reset_email');
        $user = User::where('email', $email)->first();

        if ($user) {
            // Enkripsi password baru
            $user->password = Hash::make($request->password);
            $user->save();
        }

        // Bersihkan semua session terkait reset password
        session()->forget(['password_reset_email', 'password_verified']);

        return redirect()->route('login')->with('success', 'Password berhasil diubah. Silakan login.');
    }
}
