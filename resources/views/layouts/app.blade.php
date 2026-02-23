<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Bendahara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3f4f6; font-family: 'Segoe UI', sans-serif; }

        .sidebar {
            width: 260px;
            background: #1e293b;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            z-index: 1000;
            transition: all 0.3s;
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 10px;
        }

        .sidebar-heading {
            color: #ffffff;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 0 20px;
            margin-top: 20px;
            margin-bottom: 10px;
            letter-spacing: 0.05em;
        }

        .sidebar a {
            color: #94a3b8;
            text-decoration: none;
            padding: 12px 20px;
            display: block;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 0.95rem;
        }

        .sidebar a:hover, .sidebar a.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
        }

        .sidebar a i { width: 25px; }

        .user-profile {
            padding: 20px;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .user-info small { color: #94a3b8; font-size: 0.75rem; }
        .user-info strong { color: white; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .btn-logout {
            background: rgba(255, 255, 255, 0.1);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.3);
            width: 100%;
            margin-top: 10px;
        }
        .btn-logout:hover {
            background: #f87171;
            color: white;
        }

        .main-content { margin-left: 260px; padding: 30px; }
        .card-stat { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
        .table-card { border-radius: 12px; border: none; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; }
        .table th { background-color: #f8fafc; font-weight: 600; color: #475569; }

        @media print {
            .sidebar, .no-print, .btn, .pagination { display: none !important; }
            .main-content { margin-left: 0; padding: 0; }
            .card { border: none; box-shadow: none; }
            body { background: white; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar no-print">
        <div>
            <div class="sidebar-header">
                <h4 class="mb-0 fw-bold"><i class="fas fa-wallet text-primary me-2"></i>Bendahara</h4>
            </div>

            <!-- LOGIKA ACTIVE MENU DIPERBARUI -->
            <a href="{{ route('home') }}" class="{{ request()->is('/') ? 'active' : '' }}">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>

            <div class="sidebar-heading">Laporan & Cetak</div>
            <a href="{{ route('laporan.sekolah') }}" class="{{ request()->is('laporan/sekolah') ? 'active' : '' }}">
                <i class="fas fa-school me-2"></i> Laporan Sekolah
            </a>
            <a href="{{ route('laporan.wali') }}" class="{{ request()->is('laporan/wali') ? 'active' : '' }}">
                <i class="fas fa-user-graduate me-2"></i> Laporan Wali Murid
            </a>
            <a href="{{ route('laporan.yayasan') }}" class="{{ request()->is('laporan/yayasan') ? 'active' : '' }}">
                <i class="fas fa-building me-2"></i> Laporan Yayasan
            </a>
        </div>

        <div class="user-profile">
            <div class="d-flex align-items-center mb-3 user-info">
                <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 35px; height: 35px;">
                    <i class="fas fa-user text-white small"></i>
                </div>
                <div style="line-height: 1.2;">
                    <small>Halo, Admin</small>
                    <strong>{{ auth()->user()->name ?? 'User' }}</strong>
                </div>
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-logout btn-sm">
                    <i class="fas fa-sign-out-alt me-1"></i> Keluar Aplikasi
                </button>
            </form>
        </div>

    </div>

    <!-- Main Content -->
    <div class="main-content">

        <!-- ALERT VALIDASI (Untuk error form input) -->
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show no-print shadow-sm" role="alert">
                <h6 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i> Mohon Periksa Kembali:</h6>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show no-print shadow-sm" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show no-print shadow-sm" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
