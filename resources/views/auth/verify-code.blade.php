<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Kode - SIKS</title>
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
            padding: 16px;
            font-family: 'Manrope', sans-serif;
            color: var(--text);
        }

        .login-card {
            background: rgba(255, 255, 255, 0.94);
            border: 1px solid rgba(219, 231, 245, 0.95);
            border-radius: 22px;
            padding: 32px 28px;
            width: 100%;
            max-width: 430px;
            box-shadow: var(--shadow);
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
        }

        .brand-chip span {
            font-size: 12px;
            font-weight: 800;
            color: var(--blue-700);
            letter-spacing: 0.03em;
        }

        .verify-icon {
            font-size: 44px;
            color: var(--blue-600);
            margin-bottom: 12px;
        }

        .title {
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.03em;
            color: var(--text);
        }

        .subtitle {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
            margin: 0;
        }

        .form-label {
            font-size: 12px;
            color: #4f6787;
            letter-spacing: 0.06em;
        }

        .form-control {
            border-radius: 12px;
            height: 52px;
            border: 1px solid var(--line);
            letter-spacing: 10px;
            text-align: center;
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--text);
        }

        .form-control:focus {
            border-color: var(--blue-500);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
        }

        .btn-login {
            background: linear-gradient(135deg, var(--blue-600), var(--blue-500));
            border: none;
            height: 48px;
            border-radius: 12px;
            color: white;
            font-weight: 700;
            width: 100%;
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.2);
            transition: all 0.2s;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 32px rgba(37, 99, 235, 0.26);
            color: #fff;
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
            <div><i class="fas fa-shield-alt verify-icon"></i></div>
            <h4 class="title">Verifikasi Kode</h4>
            <p class="subtitle">Masukkan kode 6 digit yang telah dikirim ke email Anda.</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success py-2 small text-center">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger py-2 small">{{ $errors->first('code') }}</div>
        @endif

        <form method="POST" action="{{ route('password.check') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-bold">KODE VERIFIKASI</label>
                <input type="text" name="code" class="form-control" placeholder="123456" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus>
            </div>
            <button type="submit" class="btn-login">VERIFIKASI</button>
            <div class="text-center mt-3">
                <a href="{{ route('password.request') }}" class="back-link">Kirim ulang kode</a>
            </div>
        </form>
    </div>
</body>
</html>
