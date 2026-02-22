<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background: linear-gradient(135deg, #1e293b 0%, #3b82f6 100%); height: 100vh; display: flex; align-items: center; justify-content: center; font-family: 'Segoe UI', sans-serif; }
        .login-card { background: white; border-radius: 20px; padding: 40px; width: 100%; max-width: 450px; box-shadow: 0 15px 35px rgba(0,0,0,0.2); }
        .form-control { border-radius: 10px; padding: 12px; }
        .btn-login { background: linear-gradient(to right, #2563eb, #3b82f6); border: none; padding: 12px; border-radius: 10px; color: white; font-weight: 600; width: 100%; }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="text-center mb-4">
            <i class="fas fa-lock fa-3x text-success mb-3"></i>
            <h4>Buat Password Baru</h4>
            <p class="text-muted small">Masukkan password baru untuk akun Anda.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <div class="mb-3">
                <label class="form-label small fw-bold">PASSWORD BARU</label>
                <input type="password" name="password" class="form-control" placeholder="******" required>
            </div>
            <div class="mb-3">
                <label class="form-label small fw-bold">KONFIRMASI PASSWORD</label>
                <input type="password" name="password_confirmation" class="form-control" placeholder="******" required>
            </div>
            <button type="submit" class="btn-login">SIMPAN PASSWORD</button>
        </form>
    </div>
</body>
</html>
