<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIKS SMA UNGGULAN BPPT DARUS SHOLAH</title>

    <!-- Preconnect to Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- External CSS -->
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* CSS Variables */
        :root {
            --blue-50: #eff6ff;
            --blue-100: #dbeafe;
            --blue-200: #bfdbfe;
            --blue-500: #3b82f6;
            --blue-600: #2563eb;
            --blue-700: #1d4ed8;
            --text: #17324d;
            --muted: #6b7c93;
            --line: #dbe7f5;
            --shadow: 0 24px 60px rgba(37, 99, 235, 0.14);
        }

        /* Base Styles */
        * {
            box-sizing: border-box;
        }

        html, body {
            min-height: 100%;
            margin: 0;
            font-family: 'Manrope', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 28px;
            background:
                radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 34%),
                radial-gradient(circle at bottom right, rgba(191, 219, 254, 0.5), transparent 30%),
                linear-gradient(180deg, #f8fbff 0%, #edf4ff 100%);
            color: var(--text);
        }

        /* Loading Screen Styles */
        .loading-screen {
            position: fixed;
            inset: 0;
            background: linear-gradient(180deg, #f8fbff 0%, #edf4ff 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.35s ease, visibility 0.35s ease;
        }

        .loading-screen.hidden {
            opacity: 0;
            visibility: hidden;
        }

        .loading-box {
            text-align: center;
            background: rgba(255, 255, 255, 0.88);
            border: 1px solid rgba(219, 231, 245, 0.9);
            box-shadow: var(--shadow);
            border-radius: 24px;
            padding: 28px 30px;
            min-width: 220px;
            backdrop-filter: blur(12px);
        }

        .loading-logo {
            width: 72px;
            height: 72px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--blue-600), var(--blue-500));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            box-shadow: 0 12px 30px rgba(37, 99, 235, 0.26);
            animation: pulse 1.6s ease-in-out infinite;
        }

        .loading-logo img {
            width: 46px;
            height: 46px;
            object-fit: cover;
            border-radius: 10px;
            background: #fff;
            padding: 3px;
        }

        .loading-text {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.08em;
            color: var(--blue-700);
            margin-bottom: 14px;
        }

        .loading-bar {
            width: 160px;
            height: 4px;
            border-radius: 999px;
            background: #dbeafe;
            overflow: hidden;
            margin: 0 auto;
        }

        .loading-bar span {
            display: block;
            width: 40%;
            height: 100%;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--blue-500), var(--blue-700));
            animation: loading 1.2s ease-in-out infinite;
        }

        /* Login Container Styles */
        .login-shell {
            width: 100%;
            max-width: 920px;
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(219, 231, 245, 0.95);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: 1fr 1fr;
            min-height: 540px;
            animation: fadeUp 0.55s ease;
        }

        /* Left Panel Styles */
        .left-panel {
            background: linear-gradient(135deg, var(--blue-600), #4f8cff 100%);
            color: #fff;
            padding: 46px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }

        .left-panel::before,
        .left-panel::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.12);
        }

        .left-panel::before {
            width: 180px;
            height: 180px;
            top: -70px;
            right: -50px;
        }

        .left-panel::after {
            width: 120px;
            height: 120px;
            bottom: -40px;
            left: -30px;
        }

        .brand-chip {
            position: relative;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: rgba(255, 255, 255, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 14px;
            padding: 10px 14px;
            width: fit-content;
            backdrop-filter: blur(10px);
        }

        .brand-chip img {
            width: 28px;
            height: 28px;
            object-fit: cover;
            border-radius: 7px;
            background: #fff;
        }

        .brand-chip span {
            font-weight: 800;
            letter-spacing: 0.03em;
            font-size: 13px;
        }

        .left-title {
            position: relative;
            z-index: 1;
            margin-top: 38px;
        }

        .left-title h1 {
            font-size: 34px;
            line-height: 1.06;
            font-weight: 800;
            margin-bottom: 14px;
            letter-spacing: -0.03em;
        }

        .left-title h1 .second-line {
            display: block;
            margin-top: 8px;
        }

        .left-title p {
            margin: 0;
            color: rgba(255, 255, 255, 0.88);
            font-size: 14px;
            line-height: 1.65;
            max-width: 320px;
        }

        .feature-list {
            position: relative;
            z-index: 1;
            display: grid;
            gap: 18px;
            margin-top: 36px;
        }

        .feature-item {
            display: flex;
            gap: 12px;
            align-items: flex-start;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            padding: 14px;
            backdrop-filter: blur(8px);
            transition: background 0.2s ease, transform 0.2s ease;
        }

        .feature-item:hover {
            background: rgba(255, 255, 255, 0.18);
            transform: translateY(-1px);
        }

        .feature-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            background: rgba(255, 255, 255, 0.18);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-item h6 {
            font-size: 14px;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .feature-item p {
            font-size: 12px;
            margin: 0;
            color: rgba(255, 255, 255, 0.85);
            line-height: 1.55;
        }

        /* Right Panel Styles */
        .right-panel {
            padding: 46px 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            width: 100%;
            max-width: 360px;
        }

        .login-title {
            color: var(--text);
            font-size: 28px;
            font-weight: 800;
            margin-bottom: 8px;
            letter-spacing: -0.03em;
        }

        .login-subtitle {
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 32px;
            line-height: 1.6;
        }

        /* Form Styles */
        .alert {
            border-radius: 14px;
            border: 1px solid #fecaca;
            background: #fef2f2;
            color: #991b1b;
            font-size: 13px;
            padding: 12px 14px;
            margin-bottom: 18px;
        }

        .field {
            margin-bottom: 20px;
        }

        .field label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #4f6787;
            margin-bottom: 8px;
        }

        .input-wrap {
            position: relative;
        }

        .input-wrap .icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7c93;
        }

        .form-control {
            width: 100%;
            height: 48px;
            border-radius: 12px;
            border: 1px solid var(--line);
            padding: 0 14px 0 42px;
            font-size: 14px;
            color: var(--text);
            background: #fff;
            transition: all 0.2s ease;
        }

        .form-control::placeholder {
            color: #94a3b8;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--blue-500);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.12);
        }

        .password-toggle {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #6b7c93;
            padding: 6px;
            cursor: pointer;
        }

        .password-toggle:hover {
            color: var(--blue-600);
        }

        .field-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            margin-top: 12px;
            margin-bottom: 28px;
        }

        .check {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        .check input {
            width: 15px;
            height: 15px;
            accent-color: var(--blue-600);
        }

        .forgot-link {
            font-size: 13px;
            color: var(--blue-600);
            text-decoration: none;
            font-weight: 700;
        }

        .forgot-link:hover {
            color: var(--blue-700);
        }

        .btn-login {
            width: 100%;
            height: 48px;
            border: 0;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--blue-600), var(--blue-500));
            color: #fff;
            font-weight: 700;
            box-shadow: 0 14px 28px rgba(37, 99, 235, 0.22);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn-login:hover {
            transform: translateY(-1px);
            box-shadow: 0 18px 32px rgba(37, 99, 235, 0.26);
        }

        .btn-login:disabled {
            opacity: 0.82;
            cursor: not-allowed;
            transform: none;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.2);
        }

        .helper-text {
            margin-top: 22px;
            font-size: 12px;
            color: #7c8da6;
            text-align: center;
        }

        /* Animations */
        @keyframes loading {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(250%); }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.04); }
        }

        @keyframes fadeUp {
            from {
                opacity: 0;
                transform: translateY(12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .login-shell {
                grid-template-columns: 1fr;
                max-width: 620px;
            }

            .left-panel {
                padding: 36px;
                min-height: 280px;
            }

            .left-title h1 {
                font-size: 29px;
            }

            .right-panel {
                padding: 36px;
            }
        }

        @media (max-width: 576px) {
            body {
                padding: 14px;
            }

            .login-shell {
                border-radius: 18px;
            }

            .left-panel,
            .right-panel {
                padding: 22px;
            }

            .left-title h1 {
                font-size: 24px;
            }

            .feature-list {
                display: none;
            }

            .login-title {
                font-size: 24px;
            }
        }

        /* Accessibility */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }

            .loading-screen {
                display: none;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loadingScreen">
        <div class="loading-box">
            <div class="loading-logo">
                <img src="{{ asset('image/logo_pondok.jpeg') }}" alt="Logo SIKS">
            </div>
            <div class="loading-text">MEMUAT SISTEM</div>
            <div class="loading-bar"><span></span></div>
        </div>
    </div>

    <!-- Main Login Container -->
    <main class="login-shell">
        <!-- Left Panel - Branding and Features -->
        <section class="left-panel">
            <div>
                <div class="brand-chip">
                    <img src="{{ asset('image/logo_pondok.jpeg') }}" alt="Logo SIKS">
                    <span>SIKS SMA UNGGULAN BPPT DARUS SHOLAH</span>
                </div>

                <div class="left-title">
                    <h1>Kelola keuangan <span class="second-line">Sekolah lebih mudah</span></h1>
                    <p>Masuk ke sistem bendahara sekolah dengan tampilan yang bersih, cepat, dan lebih nyaman dipakai setiap hari.</p>
                </div>

                <div class="feature-list">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-layer-group"></i></div>
                        <div>
                            <h6>Rapi dan terstruktur</h6>
                            <p>Manajemen data keuangan tersusun jelas dalam satu alur yang sederhana.</p>
                        </div>
                    </div>

                    <div class="feature-item">
                        <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                        <div>
                            <h6>Monitoring mudah</h6>
                            <p>Pantau tagihan, pembayaran, dan laporan dengan visual yang bersih.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Right Panel - Login Form -->
        <section class="right-panel">
            <div class="login-card">
                <h2 class="login-title">Masuk Akun</h2>
                <p class="login-subtitle">Silakan masuk menggunakan email dan kata sandi Anda.</p>

                @if ($errors->any())
                    <div class="alert" role="alert" aria-live="polite">
                        <i class="fas fa-exclamation-circle me-2"></i>{{ $errors->first('email') ?: 'Email atau kata sandi salah.' }}
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" id="loginForm">
                    @csrf

                    <div class="field">
                        <label for="email">Alamat Email</label>
                        <div class="input-wrap">
                            <span class="icon"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email" placeholder="admin@sekolah.sch.id" value="{{ old('email') }}" autocomplete="email" required autofocus>
                        </div>
                    </div>

                    <div class="field">
                        <label for="password">Kata Sandi</label>
                        <div class="input-wrap">
                            <span class="icon"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" id="togglePassword" aria-label="Tampilkan kata sandi">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="field-row">
                        <label class="check">
                            <input type="checkbox" id="remember" name="remember">
                            <span>Ingat saya</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="forgot-link">Lupa sandi?</a>
                    </div>

                    <button type="submit" class="btn-login" id="loginButton">Masuk</button>
                </form>

                <div class="helper-text">&copy; {{ date('Y') }} SIKS Bendahara Sekolah</div>
            </div>
        </section>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Element references
            const loadingScreen = document.getElementById('loadingScreen');
            const passwordInput = document.getElementById('password');
            const togglePassword = document.getElementById('togglePassword');
            const togglePasswordIcon = document.getElementById('togglePasswordIcon');
            const loginForm = document.getElementById('loginForm');
            const loginButton = document.getElementById('loginButton');
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            // Hide loading screen after delay (if not reduced motion)
            if (!prefersReducedMotion) {
                window.setTimeout(function () {
                    if (loadingScreen) {
                        loadingScreen.classList.add('hidden');
                        window.setTimeout(function () {
                            loadingScreen.remove();
                        }, 350);
                    }
                }, 900);
            } else if (loadingScreen) {
                loadingScreen.remove();
            }

            // Password visibility toggle
            if (togglePassword && passwordInput && togglePasswordIcon) {
                togglePassword.addEventListener('click', function () {
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    togglePasswordIcon.classList.toggle('fa-eye', !isPassword);
                    togglePasswordIcon.classList.toggle('fa-eye-slash', isPassword);
                    togglePassword.setAttribute('aria-label', isPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                });
            }

            // Form submission handling
            if (loginForm && loginButton) {
                loginForm.addEventListener('submit', function () {
                    loginButton.disabled = true;
                    loginButton.textContent = 'Memproses...';
                });
            }
        });
    </script>
</body>
</html>
