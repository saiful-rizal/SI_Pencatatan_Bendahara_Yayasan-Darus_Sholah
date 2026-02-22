<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
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
            width: 100%;
            max-width: 450px;
            padding: 40px;
            backdrop-filter: blur(10px);
            animation: fadeIn 0.8s ease-out;
        }

        .brand-logo {
            width: 90px;
            height: 90px;
            object-fit: cover;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: block;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            border: 4px solid white;
            background-color: white;
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
            color: white;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37, 99, 235, 0.4);
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
            <!-- Menggunakan logo yang sama dengan login -->
            <img src="{{ asset('image/logo_pondok.jpeg') }}" class="brand-logo" alt="Logo Sekolah">

            <h3 class="fw-bold text-dark mt-2">Lupa Password?</h3>
            <p class="text-muted">Masukkan email Anda yang terdaftar untuk menerima kode verifikasi.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success rounded-3 py-2 text-center">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger rounded-3 py-2">
                <i class="fas fa-exclamation-circle me-2"></i> {{ $errors->first('email') }}
            </div>
        @endif

        <!-- Form Input Email -->
        <form method="POST" action="{{ route('password.send') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label text-muted small fw-bold">ALAMAT EMAIL</label>
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0" style="border-radius: 10px 0 0 10px;">
                        <i class="fas fa-envelope text-muted"></i>
                    </span>
                    <input type="email" class="form-control border-start-0" id="email" name="email"
                           placeholder="contoh@sekolah.sch.id" required autofocus style="border-radius: 0 10px 10px 0;">
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                KIRIM KODE KE EMAIL
            </button>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="text-decoration-none small text-muted fw-bold">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Halaman Login
                </a>
            </div>
        </form>
    </div>

</body>
</html>
