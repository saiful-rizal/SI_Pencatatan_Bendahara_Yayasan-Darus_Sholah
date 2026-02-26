<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Bendahara</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f3f4f6; font-family: 'Segoe UI', sans-serif; font-size: 14px; }

        .sidebar {
            width: 280px;
            background: #1e293b;
            color: white;
            position: fixed;
            height: 100vh;
            padding-top: 15px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            z-index: 1000;
            transition: all 0.3s;
            overflow-y: auto;
            box-shadow: 2px 0 8px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 0 20px 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 10px;
        }

        .sidebar-header h4 {
            font-size: 1.15rem;
            font-weight: 700;
        }

        .sidebar-heading {
            color: #ffffff;
            font-size: 0.8rem;
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
            padding: 11px 20px;
            display: block;
            margin: 2px 10px;
            border-radius: 8px;
            transition: all 0.3s;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .sidebar a:hover, .sidebar a.active {
            background: #3b82f6;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.4);
        }

        .sidebar a i { width: 22px; }

        .user-profile {
            padding: 15px 20px;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .user-info small { color: #94a3b8; font-size: 0.75rem; }
        .user-info strong { color: white; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; font-size: 0.9rem; }

        .btn-logout {
            background: rgba(255, 255, 255, 0.1);
            color: #f87171;
            border: 1px solid rgba(248, 113, 113, 0.3);
            width: 100%;
            margin-top: 10px;
            font-size: 0.85rem;
        }
        .btn-logout:hover {
            background: #f87171;
            color: white;
        }

        .main-content { 
            margin-left: 280px; 
            padding: 25px 30px;
        }

        .main-content h2 { 
            font-size: 1.6rem; 
            font-weight: 700; 
            color: #1e293b;
            margin-bottom: 1.5rem;
        }

        .main-content h4 { 
            font-size: 1.1rem; 
            font-weight: 600;
            color: #334155;
        }

        .table { font-size: 0.9rem; }
        .table th { 
            background-color: #f8fafc; 
            font-weight: 700; 
            color: #475569;
            font-size: 0.85rem;
            padding: 0.75rem;
        }
        .table td { 
            padding: 0.75rem;
            vertical-align: middle;
        }

        .form-label { 
            font-size: 0.85rem; 
            font-weight: 600; 
            color: #475569;
            margin-bottom: 0.4rem;
        }

        .form-control, .form-select { 
            font-size: 0.9rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn { 
            padding: 0.55rem 1rem; 
            font-size: 0.85rem; 
            white-space: nowrap;
            font-weight: 500;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3); }
        .btn-sm { padding: 0.35rem 0.6rem; font-size: 0.75rem; }

        .card { 
            border-radius: 12px; 
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .card-stat { 
            border: none; 
            border-radius: 12px; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); 
            transition: transform 0.2s; 
            background: white;
        }
        .card-stat:hover { transform: translateY(-5px); box-shadow: 0 8px 12px rgba(0, 0, 0, 0.12); }
        
        .table-card { 
            border-radius: 12px; 
            border: none; 
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); 
            overflow: hidden; 
        }

        .badge { font-size: 0.8rem; padding: 0.4rem 0.6rem; }

        /* Responsive Mobile */
        @media (max-width: 992px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                flex-direction: row;
                overflow-x: auto;
                overflow-y: visible;
                justify-content: flex-start;
            }

            .sidebar-header {
                padding: 10px 20px;
                margin-bottom: 0;
                border-bottom: none;
                border-right: 1px solid rgba(255,255,255,0.1);
                white-space: nowrap;
            }

            .sidebar-header h4 {
                font-size: 0.95rem;
                margin: 0;
            }

            .sidebar a {
                padding: 10px 15px;
                margin: 0;
                white-space: nowrap;
                font-size: 0.8rem;
            }

            .user-profile {
                margin-left: auto;
                padding: 10px 20px;
                border-left: 1px solid rgba(255,255,255,0.1);
                border-top: none;
            }

            .main-content { 
                margin-left: 0; 
                padding: 20px 15px;
            }

            .main-content h2 {
                font-size: 1.35rem;
            }

            .table { font-size: 0.85rem; }
            .table th, .table td { padding: 0.5rem 0.35rem; }
            
            .btn { padding: 0.4rem 0.7rem; font-size: 0.8rem; }
            
            .col-12 { max-width: 100%; }
        }

        @media (max-width: 576px) {
            .sidebar-toggle { padding: 6px 10px; font-size: 0.9rem; }
            .main-content { padding: 60px 12px 15px; }
            .main-content h2 { font-size: 1.2rem; margin-bottom: 1rem; }
            .table { font-size: 0.8rem; }
            .table th, .table td { padding: 0.4rem 0.25rem; }
            .btn { padding: 0.35rem 0.55rem; font-size: 0.75rem; }
            .form-label { font-size: 0.8rem; }
            .form-control, .form-select { font-size: 0.85rem; }
        }

        @media print {
            .sidebar, .sidebar-toggle, .no-print, .btn, .pagination { display: none !important; }
            .main-content { margin-left: 0; padding: 20px; }
            .card { border: 1px solid #e2e8f0; box-shadow: none; }
            body { background: white; }
        }
    </style>
</head>
<body>

    <!-- Sidebar -->
    <div class="sidebar no-print">
        <div>
            <div class="sidebar-header">
                <h4 class="mb-0 fw-bold"><i class="fas fa-building-columns text-primary me-2"></i>SIKS DARUS SHOLAH</h4>
            </div>

            <!-- LOGIKA ACTIVE MENU DIPERBARUI -->
            <a href="{{ route('home') }}" class="{{ request()->is('/') ? 'active' : '' }}">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
            <a href="{{ route('siswa.index') }}" class="{{ request()->is('data-siswa') ? 'active' : '' }}">
                <i class="fas fa-user-graduate me-2"></i> Data Siswa
            </a>
            <a href="{{ route('item.index') }}" class="{{ request()->is('item-pembayaran') ? 'active' : '' }}">
                <i class="fas fa-list me-2"></i> Item Pembayaran
            </a>
            <a href="{{ route('tagihan.index') }}" class="{{ request()->is('tagihan') ? 'active' : '' }}">
                <i class="fas fa-file-invoice me-2"></i> Tagihan
            </a>
            <a href="{{ route('pembayaran.index') }}" class="{{ request()->is('transaksi-pembayaran') ? 'active' : '' }}">
                <i class="fas fa-money-check-dollar me-2"></i> Transaksi Pembayaran
            </a>
            <a href="{{ route('tanggungan.index') }}" class="{{ request()->is('tanggungan') ? 'active' : '' }}">
                <i class="fas fa-file-invoice-dollar me-2"></i> Tanggungan
            </a>
            <a href="{{ route('rekap.index') }}" class="{{ request()->is('rekap') ? 'active' : '' }}">
                <i class="fas fa-chart-line me-2"></i> Rekap
            </a>
            <a href="{{ route('transaksi.riwayat') }}" class="{{ request()->is('riwayat-hapus') ? 'active' : '' }}">
                <i class="fas fa-clock-rotate-left me-2"></i> Riwayat Hapus
            </a>
            <a href="{{ route('backup.database') }}">
                <i class="fas fa-database me-2"></i> Backup Database
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
