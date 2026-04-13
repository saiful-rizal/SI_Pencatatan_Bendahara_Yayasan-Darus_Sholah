<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - SIKS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;
            --text: #17324d;
            --muted: #6b7c93;
            --line: #dbe7f5;
            --shadow: 0 24px 60px rgba(37, 99, 235, 0.14);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 34%),
                radial-gradient(circle at bottom right, rgba(191, 219, 254, 0.5), transparent 30%),
                linear-gradient(180deg, #f8fbff 0%, #edf4ff 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Manrope', sans-serif;
            padding: 16px;
            color: var(--text);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.94);
            border-radius: 22px;
            border: 1px solid rgba(219, 231, 245, 0.95);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 430px;
            padding: 32px 28px;
            backdrop-filter: blur(12px);
            animation: fadeIn 0.5s ease-out;
        }

        .brand-chip {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(37, 99, 235, 0.08);
            border: 1px solid rgba(37, 99, 235, 0.12);
            border-radius: 12px;
            padding: 8px 12px;
            margin-bottom: 18px;
        }

        .brand-chip img {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            object-fit: cover;
            background: #fff;
        }

        .brand-chip span {
            font-size: 12px;
            font-weight: 800;
            color: var(--blue-700);
            letter-spacing: 0.03em;
        }

        .brand-logo {
            width: 76px;
            height: 76px;
            object-fit: cover;
            border-radius: 18px;
            margin: 0 auto 14px;
            display: block;
            box-shadow: 0 12px 26px rgba(37, 99, 235, 0.22);
            border: 3px solid white;
            background-color: white;
        }

        .title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.03em;
            color: var(--text);
        }

        .subtitle {
            margin: 0;
            font-size: 13px;
            line-height: 1.6;
            color: var(--muted);
        }

        .alert {
            border-radius: 12px;
            font-size: 13px;
            padding: 10px 12px;
        }

        .form-control {
            border-radius: 12px;
            height: 48px;
            border: 1px solid var(--line);
            background-color: #fff;
            font-size: 14px;
        }

        .form-control:focus {
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            border-color: var(--blue-500);
            background-color: white;
        }

        .form-label {
            font-size: 12px;
            color: #4f6787;
            letter-spacing: 0.06em;
        }

        .input-group-text {
            border-radius: 12px 0 0 12px;
            border-color: var(--line);
            background: #fff;
            color: #6b7c93;
        }

        .input-group .form-control {
            border-radius: 0 12px 12px 0;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--blue-600), var(--blue-500));
            border: none;
            height: 48px;
            border-radius: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: white;
            width: 100%;
            transition: all 0.2s;
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.2);
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 32px rgba(37, 99, 235, 0.26);
            color: white;
        }

        .back-link {
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            text-decoration: none;
        }

        .back-link:hover {
            color: var(--blue-700);
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
            <div class="brand-chip">
                <img src="{{ asset('image/logo_pondok.jpeg') }}" alt="Logo SIKS">
                <span>SIKS SMA UNGGULAN BPPT DARUS SHOLAH</span>
            </div>
            <img src="{{ asset('image/logo_pondok.jpeg') }}" class="brand-logo" alt="Logo Sekolah">
            <h3 class="title">Lupa Password?</h3>
            <p class="subtitle">Masukkan email terdaftar untuk menerima kode verifikasi.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success text-center">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i> {{ $errors->first('email') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.send') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label small fw-bold">ALAMAT EMAIL</label>
                <div class="input-group">
                    <span class="input-group-text border-end-0">
                        <i class="fas fa-envelope"></i>
                    </span>
                    <input type="email" class="form-control border-start-0" id="email" name="email"
                           placeholder="contoh@sekolah.sch.id" value="{{ old('email') }}" autocomplete="email" required autofocus>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-login">
                KIRIM KODE KE EMAIL
            </button>

            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="back-link">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Halaman Login
                </a>
            </div>
        </form>
    </div>

</body>
</html>
