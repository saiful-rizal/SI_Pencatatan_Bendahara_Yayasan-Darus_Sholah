<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Bendahara Sekolah</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            /* Background Gradient Modern */
            background: linear-gradient(135deg, #1e293b 0%, #3b82f6 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            padding: 40px;
            backdrop-filter: blur(10px);
            animation: fadeIn 0.8s ease-out;
        }

        /* --- CSS LOGO SUDAH DIUBAH --- */
        .brand-logo {
            width: 90px;
            height: 90px;
            object-fit: cover; /* Agar foto terpotong rapi (tidak gepeng) */
            border-radius: 50%; /* Membuat foto bulat */
            margin: 0 auto 20px; /* Tengah secara horizontal */
            display: block; /* Diperlukan agar margin auto berfungsi */
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            border: 4px solid white; /* Bingkai putih agar elegan */
            background-color: white; /* Warna dasar jika gambar gagal load */
        }

        .form-control {
            border-radius: 10px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            border-color: #3b82f6;
            background-color: white;
        }

        .btn-login {
            background: linear-gradient(to right, #2563eb, #3b82f6);
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);
            background: linear-gradient(to right, #1d4ed8, #2563eb);
            color: white;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="login-card">
        <div class="text-center mb-4">
            <img src="{{ asset('image/logo_pondok.jpeg') }}" class="brand-logo" alt="Logo Sekolah">

            <h3 class="fw-bold text-dark mt-2">Sistem Bendahara</h3>
            <p class="text-muted">Silakan masuk untuk melanjutkan</p>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger rounded-3 py-2">
                <i class="fas fa-exclamation-circle me-2"></i> {{ $errors->first('email') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <!-- Email Input -->
            <div class="mb-3">
                <label for="email" class="form-label text-muted small fw-bold">ALAMAT EMAIL</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="border-radius: 10px 0 0 10px;">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                    <input type="email" class="form-control border-start-0" id="email" name="email"
                           placeholder="admin@sekolah.sch.id" required autofocus style="border-radius: 0 10px 10px 0;">
                </div>
            </div>

            <!-- Password Input -->
            <div class="mb-4">
                <label for="password" class="form-label text-muted small fw-bold">KATA SANDI</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="border-radius: 10px 0 0 10px;">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password" class="form-control border-start-0" id="password" name="password"
                           placeholder="••••••••" required style="border-radius: 0 10px 10px 0;">
                </div>
            </div>

            <!-- Remember Me -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label small text-muted" for="remember">
                        Ingat Saya
                    </label>
                </div>
                    <a href="{{ route('password.request') }}" class="small text-decoration-none">Lupa sandi?</a>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                MASUK SEKARANG
            </button>

            <div class="text-center mt-4">
                <p class="small text-muted mb-0">&copy; {{ date('Y') }} Bendahara Sekolah. All rights reserved.</p>
            </div>
        </form>
    </div>

</body>
</html>
