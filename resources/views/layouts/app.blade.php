<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Bendahara</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg-page: #f5f8fc;
            --bg-page-2: #eff3f9;
            --sidebar-bg: #ffffff;
            --panel-bg: #ffffff;
            --line-soft: #e2e8f0;
            --line-medium: #d1dce8;
            --text-main: #1a2d4a;
            --text-muted: #6b7c93;
            --accent: #3b82f6;
            --accent-strong: #2563eb;
            --ok: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --shadow-soft: 0 2px 8px rgba(0, 0, 0, 0.06);
            --shadow-md: 0 4px 12px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 10px 24px rgba(0, 0, 0, 0.1);

            --font-base: 14px;
            --font-xs: 12px;
            --font-sm: 13px;
            --font-md: 14px;
            --font-lg: 16px;
            --font-xl: 24px;
            --font-2xl: 28px;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at 10% 12%, rgba(59, 130, 246, 0.08), transparent 35%),
                radial-gradient(circle at 86% 16%, rgba(96, 165, 250, 0.08), transparent 32%),
                linear-gradient(170deg, var(--bg-page) 0%, var(--bg-page-2) 100%);
            background-attachment: fixed;
            font-family: 'Manrope', sans-serif;
            font-size: var(--font-base);
            line-height: 1.6;
            color: var(--text-main);
            letter-spacing: 0.3px;
            overflow-x: hidden;
        }

        .sidebar {
            width: 280px;
            background: var(--sidebar-bg);
            color: var(--text-main);
            position: fixed;
            top: 18px;
            left: 18px;
            bottom: 18px;
            height: auto;
            padding-top: 12px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            border-radius: 14px;
            border: 1px solid var(--line-soft);
            box-shadow: var(--shadow-md);
        }

        .sidebar-header {
            padding: 6px 20px 16px;
            border-bottom: 1px solid var(--line-soft);
            margin-bottom: 8px;
        }

        .sidebar-header h4 {
            font-size: 0.92rem;
            font-weight: 800;
            color: #1a2d4a;
            letter-spacing: -0.3px;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .brand-logo {
            width: 24px;
            height: 24px;
            border-radius: 5px;
            object-fit: cover;
            vertical-align: -3px;
            transition: transform 0.2s ease;
            flex-shrink: 0;
        }

        .sidebar-header:hover .brand-logo {
            transform: scale(1.05);
        }

        .sidebar-heading {
            color: #8d9aaf;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            padding: 0 20px;
            margin-top: 18px;
            margin-bottom: 8px;
            letter-spacing: 0.08em;
        }

        .sidebar a {
            color: #6b7c93;
            text-decoration: none;
            padding: 10px 20px;
            display: block;
            margin: 2px 8px;
            border-radius: 9px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            font-size: var(--font-sm);
            font-weight: 600;
            border: 1px solid transparent;
        }

        .sidebar a:hover, .sidebar a.active {
            background: linear-gradient(135deg, #3b82f6 0%, #60a5fa 100%);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
        }

        .sidebar-group-toggle {
            width: calc(100% - 16px);
            margin: 4px 8px 0;
            border: 0;
            background: transparent;
            color: #8d9aaf;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 11px 12px;
            text-align: left;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: color 0.2s ease;
        }

        .sidebar-group-toggle:hover {
            color: #3b82f6;
        }

        .sidebar-group-toggle .group-label {
            display: inline-flex;
            align-items: center;
        }

        .sidebar-group-toggle .chevron {
            font-size: 0.72rem;
            transition: transform 0.2s;
        }

        .sidebar-group-toggle[aria-expanded="true"] .chevron {
            transform: rotate(180deg);
        }

        .sidebar-submenu {
            margin-bottom: 4px;
        }

        .sidebar-submenu a {
            padding: 9px 18px 9px 42px;
            font-size: var(--font-sm);
            margin: 2px 8px;
            border-radius: 9px;
        }

        .sidebar a i { width: 22px; }

        .user-profile {
            padding: 16px 20px;
            background: #f8fbff;
            border-top: 1px solid var(--line-soft);
            border-radius: 0 0 14px 14px;
        }

        .profile-trigger {
            border: 0;
            background: transparent;
            padding: 0;
            border-radius: 999px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .profile-trigger:hover,
        .profile-trigger:focus-visible {
            transform: translateY(-1px);
            box-shadow: 0 6px 14px rgba(37, 99, 235, 0.2);
            outline: none;
        }

        .profile-avatar {
            width: 35px;
            height: 35px;
            border-radius: 999px;
            background: var(--accent);
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-info small {
            color: #8d9aaf;
            font-size: var(--font-xs);
            letter-spacing: 0.2px;
        }
        .user-info strong {
            color: #1a2d4a;
            display: block;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-size: var(--font-sm);
            font-weight: 700;
            margin-top: 2px;
        }

        .btn-logout {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
            width: 100%;
            margin-top: 12px;
            font-size: var(--font-xs);
            font-weight: 600;
            transition: all 0.25s ease;
        }
        .btn-logout:hover {
            background: #ef4444;
            color: white;
            border-color: #dc2626;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.2);
        }

        .main-content {
            margin-left: 308px;
            padding: 28px 32px;
            max-width: calc(100vw - 332px);
            min-width: 0;
        }

        .main-content h2 {
            font-size: var(--font-2xl);
            font-weight: 800;
            color: #1a2d4a;
            margin-bottom: 28px;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }

        .main-content h4 {
            font-size: 17px;
            font-weight: 700;
            color: #2a3f58;
            line-height: 1.35;
            margin-bottom: 12px;
        }

        .table {
            font-size: var(--font-sm);
            color: #334a68;
            margin-bottom: 0;
        }

        .table-responsive {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .table th {
            background-color: #f8fafc;
            font-weight: 700;
            color: #4b5c7a;
            font-size: 12px;
            padding: 13px 15px;
            border-color: var(--line-soft);
            letter-spacing: 0.2px;
        }

        .table td {
            padding: 14px 15px;
            vertical-align: middle;
            background: #ffffff;
            border-color: var(--line-soft);
        }

        .table > :not(caption) > * > * {
            border-bottom-color: var(--line-soft);
        }

        .form-label {
            font-size: var(--font-sm);
            font-weight: 700;
            color: #3a4f6a;
            margin-bottom: 8px;
            letter-spacing: 0.2px;
        }

        .form-control, .form-select {
            font-size: var(--font-sm);
            border-radius: 8px;
            border: 1px solid #d1dce8;
            background: #ffffff;
            color: #27415f;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: #ffffff;
            color: #1f334d;
        }

        .form-control::placeholder {
            color: #9ca3af;
        }

        form.js-auto-filter .d-flex.gap-1:not(.filter-actions-inline),
        form.js-auto-filter .d-flex.gap-2:not(.filter-actions-inline),
        form.js-auto-filter .d-flex.gap-3:not(.filter-actions-inline) {
            flex-wrap: wrap;
            min-width: 0;
        }

        form.js-auto-filter .d-flex.gap-1:not(.filter-actions-inline) > .btn,
        form.js-auto-filter .d-flex.gap-2:not(.filter-actions-inline) > .btn,
        form.js-auto-filter .d-flex.gap-3:not(.filter-actions-inline) > .btn {
            flex: 1 1 0;
            min-width: 0;
        }

        form.js-auto-filter .d-flex.gap-1:not(.filter-actions-inline) > .btn.w-100,
        form.js-auto-filter .d-flex.gap-2:not(.filter-actions-inline) > .btn.w-100,
        form.js-auto-filter .d-flex.gap-3:not(.filter-actions-inline) > .btn.w-100 {
            width: auto !important;
        }

        .filter-actions-inline {
            flex-wrap: nowrap !important;
            align-items: end;
            min-width: 0;
        }

        .filter-actions-inline .btn {
            font-size: 11px;
            padding: 0.45rem 0.65rem;
            white-space: nowrap;
        }

        .filter-actions-inline .btn-primary {
            flex: 1 1 auto;
            min-width: 0;
        }

        .filter-actions-inline .btn-outline-secondary {
            flex: 0 0 auto;
        }

        .form-check-input {
            background-color: #ffffff;
            border-color: #d1dce8;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .form-check-input:checked {
            background-color: var(--accent);
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .btn {
            padding: 0.6rem 1.1rem;
            font-size: var(--font-xs);
            white-space: nowrap;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            letter-spacing: 0.2px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, #60a5fa 100%);
            border-color: var(--accent-strong);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, var(--accent-strong) 0%, #3b82f6 100%);
            box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
        }

        .btn-sm {
            padding: 0.4rem 0.7rem;
            font-size: 11px;
            border-radius: 6px;
        }

        .card {
            border-radius: 12px;
            border: 1px solid var(--line-soft);
            box-shadow: var(--shadow-soft);
            background: var(--panel-bg);
            color: #22374f;
            transition: box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--line-medium);
        }

        .card p,
        .card small,
        .card span,
        .card li {
            line-height: 1.5;
        }

        .card-stat {
            border-radius: 12px;
            border: none;
            box-shadow: var(--shadow-md);
            transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s ease;
            color: #ffffff;
        }

        .card-stat small {
            color: rgba(255, 255, 255, 0.9) !important;
        }

        .row .col-6:nth-child(1) .card-stat,
        .row .col-md:nth-child(1) .card-stat {
            background: linear-gradient(120deg, #2a7fff 0%, #55a2ff 100%);
        }

        .row .col-6:nth-child(2) .card-stat,
        .row .col-md:nth-child(2) .card-stat {
            background: linear-gradient(120deg, #11b8d7 0%, #40d2d4 100%);
        }

        .row .col-6:nth-child(3) .card-stat,
        .row .col-md:nth-child(3) .card-stat {
            background: linear-gradient(120deg, #8a6df7 0%, #9f85ff 100%);
        }

        .row .col-6:nth-child(4) .card-stat,
        .row .col-md:nth-child(4) .card-stat {
            background: linear-gradient(120deg, #f49b4e 0%, #f7b36f 100%);
        }

        .row .col-6:nth-child(5) .card-stat,
        .row .col-md:nth-child(5) .card-stat {
            background: linear-gradient(120deg, #2b8dff 0%, #67b1ff 100%);
        }

        .row .col-6:nth-child(6) .card-stat,
        .row .col-md:nth-child(6) .card-stat {
            background: linear-gradient(120deg, #14b8a6 0%, #43d3c0 100%);
        }

        .card-stat:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); }

        .table-card {
            border-radius: 12px;
            border: 1px solid var(--line-soft);
            box-shadow: var(--shadow-soft);
            overflow: hidden;
            background: #ffffff;
        }

        .card-header,
        .card-footer,
        .modal-header,
        .modal-footer {
            background: #ffffff !important;
            border-color: var(--line-soft);
            color: #2a3f58;
            font-size: var(--font-sm);
            padding: 16px 20px;
        }

        .modal-content {
            background: #ffffff;
            border: 1px solid var(--line-soft);
            color: #2a3f58;
            border-radius: 12px;
            box-shadow: 0 20px 25px rgba(0, 0, 0, 0.15);
        }

        .payment-confirm-modal .modal-content {
            border: none;
            border-radius: 26px;
            padding: 12px;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18);
        }

        .payment-confirm-modal .modal-dialog {
            z-index: 1076;
        }

        .payment-confirm-modal .modal-body {
            padding: 24px 28px 20px;
            text-align: center;
        }

        .payment-confirm-icon {
            width: 74px;
            height: 74px;
            border-radius: 999px;
            margin: 0 auto 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            box-shadow: 0 10px 24px rgba(34, 197, 94, 0.28);
            color: #ffffff;
            font-size: 32px;
        }

        .payment-confirm-title {
            font-size: 18px;
            font-weight: 800;
            color: #1e2f4d;
            margin-bottom: 10px;
        }

        .payment-confirm-text {
            font-size: 14px;
            line-height: 1.65;
            color: #64748b;
            margin-bottom: 0;
        }

        .payment-confirm-modal .modal-footer {
            border-top: 0;
            justify-content: center;
            gap: 10px;
            padding: 0 28px 26px;
        }

        .payment-confirm-modal .btn {
            min-width: 132px;
            border-radius: 14px;
            font-size: 13px;
            font-weight: 700;
            padding: 0.7rem 1rem;
        }

        .payment-confirm-modal .btn-primary {
            background: linear-gradient(135deg, #16a34a 0%, #22c55e 100%);
            border-color: #16a34a;
            box-shadow: 0 10px 18px rgba(34, 197, 94, 0.22);
        }

        .payment-confirm-modal .btn-primary:hover {
            background: linear-gradient(135deg, #15803d 0%, #16a34a 100%);
            transform: translateY(-1px);
        }

        .payment-confirm-modal .btn-outline-secondary {
            color: #64748b;
            border-color: #dbe4ef;
            background: #ffffff;
        }

        .payment-confirm-modal .btn-outline-secondary:hover {
            background: #f8fafc;
            color: #334155;
        }

        .modal-title,
        .modal-body {
            color: #2a3f58;
        }

        .modal-title {
            font-size: 18px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }

        .modal-body {
            font-size: var(--font-sm);
            line-height: 1.6;
        }

        .btn-close {
            filter: none;
        }

        .badge {
            font-size: 11px;
            font-weight: 700;
            padding: 0.4rem 0.6rem;
            border-radius: 6px;
            letter-spacing: 0.2px;
            display: inline-block;
            text-transform: capitalize;
        }

        .bg-white,
        .bg-light {
            background-color: #ffffff !important;
            color: #2a3f58 !important;
        }

        .text-dark {
            color: #1f334d !important;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .text-success {
            color: #10b981 !important;
        }

        .text-danger {
            color: #ef4444 !important;
        }

        .text-primary {
            color: #3b82f6 !important;
        }

        .text-warning {
            color: #f59e0b !important;
        }

        .alert {
            border-radius: 10px;
            border: 1px solid #e2ebf7;
            background: #f8fbff;
            color: #2a3f58;
            font-size: var(--font-sm);
            animation: slideInDown 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .alert-success {
            border-color: #86efac;
            background: #f0fdf4;
            color: #166534;
        }

        .alert-danger {
            border-color: #fca5a5;
            background: #fef2f2;
            color: #991b1b;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-12px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .pagination {
            --bs-pagination-bg: #ffffff;
            --bs-pagination-border-color: var(--line-soft);
            --bs-pagination-color: #6b7c93;
            --bs-pagination-hover-bg: #eff6ff;
            --bs-pagination-hover-color: var(--accent);
            --bs-pagination-focus-bg: #eff6ff;
            --bs-pagination-focus-color: var(--accent);
            --bs-pagination-active-bg: var(--accent);
            --bs-pagination-active-border-color: var(--accent);
        }

        .page-item .page-link {
            transition: all 0.2s ease;
            border-radius: 6px;
        }

        .page-item.active .page-link {
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.2);
        }

        .dropdown-menu {
            background: #ffffff;
            border: 1px solid var(--line-soft);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }

        .dropdown-item {
            color: #3d5473;
            font-size: var(--font-sm);
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .dropdown-item:hover,
        .dropdown-item:focus {
            color: #1f334d;
            background: #eff6ff;
            border-radius: 6px;
            margin: 0 8px;
            padding: 0.5rem 1rem;
        }

        hr,
        .border {
            border-color: var(--line-soft) !important;
        }

        .reveal-init {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1), transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            transition-delay: var(--reveal-delay, 0ms);
        }

        .reveal-in {
            opacity: 1;
            transform: translateY(0);
        }

        .sidebar-fade-in {
            opacity: 0;
            transform: translateX(-12px);
            animation: sidebarItemIn 0.4s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes sidebarItemIn {
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
                scroll-behavior: auto !important;
            }
        }

        /* ===== Mobile Hamburger (floating) ===== */
        .sidebar-toggle-mobile {
            display: none;
            position: fixed;
            top: 12px;
            left: 12px;
            z-index: 1100;
            width: 40px;
            height: 40px;
            border: none;
            border-radius: 10px;
            background: #fff;
            color: var(--text-main);
            font-size: 18px;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            align-items: center;
            justify-content: center;
            transition: all 0.25s ease;
        }

        .sidebar-toggle-mobile:hover {
            background: var(--accent);
            color: #fff;
            box-shadow: 0 4px 12px rgba(59,130,246,0.3);
        }

        @media (min-width: 993px) {
            .sidebar-toggle-mobile,
            .sidebar-overlay {
                display: none !important;
            }
        }

        /* ===== Sidebar Overlay (mobile only) ===== */
        .sidebar-overlay {
            display: none;
            position: fixed;
            inset: 0;
            z-index: 998;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(2px);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .sidebar-overlay.show {
            opacity: 1;
        }

        /* ===== Responsive Mobile ===== */
        @media (max-width: 992px) {
            .sidebar-toggle-mobile {
                display: flex;
            }

            .sidebar {
                position: fixed;
                top: 0;
                left: -300px;
                width: 280px;
                bottom: 0;
                height: 100vh;
                border-radius: 0 14px 14px 0;
                border: none;
                box-shadow: var(--shadow-lg);
                z-index: 999;
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                padding-top: 60px;
            }

            .sidebar.open {
                left: 0;
            }

            .sidebar-overlay.show {
                display: block;
            }

            .sidebar-header {
                padding: 8px 20px 12px;
            }

            .sidebar-header h4 {
                font-size: 0.85rem;
            }

            .sidebar a {
                padding: 10px 16px;
                margin: 2px 6px;
                font-size: var(--font-xs);
            }

            .user-profile {
                border-radius: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 60px 20px 24px;
                max-width: 100%;
            }

            .main-content h2 {
                font-size: 22px;
            }

            .table th,
            .table td {
                padding: 12px;
            }

            .btn {
                padding: 0.5rem 0.8rem;
                font-size: 12px;
            }
        }

        @media (max-width: 576px) {
            .main-content {
                padding: 56px 14px 20px;
            }

            .main-content h2 {
                font-size: 20px;
                margin-bottom: 20px;
            }

            .table {
                font-size: 11px;
            }

            .table th,
            .table td {
                padding: 10px 8px;
            }

            .btn {
                padding: 0.4rem 0.7rem;
                font-size: 11px;
            }

            .form-label,
            .form-control,
            .form-select {
                font-size: var(--font-xs);
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 56px 12px 20px;
                max-width: 100%;
            }

            .main-content .card-body,
            .main-content .card-header,
            .main-content .card-footer {
                padding-left: 12px;
                padding-right: 12px;
            }

            .main-content .table {
                min-width: 680px;
            }

            .main-content .d-flex.gap-1,
            .main-content .d-flex.gap-2,
            .main-content .d-flex.gap-3 {
                flex-wrap: wrap;
            }

            form.js-auto-filter .d-flex.gap-1:not(.filter-actions-inline),
            form.js-auto-filter .d-flex.gap-2:not(.filter-actions-inline),
            form.js-auto-filter .d-flex.gap-3:not(.filter-actions-inline) {
                display: grid !important;
                grid-template-columns: 1fr 1fr;
                width: 100%;
            }

            form.js-auto-filter .d-flex.gap-1:not(.filter-actions-inline) .btn,
            form.js-auto-filter .d-flex.gap-2:not(.filter-actions-inline) .btn,
            form.js-auto-filter .d-flex.gap-3:not(.filter-actions-inline) .btn {
                width: 100%;
            }

            .btn {
                white-space: normal;
            }
        }

        @media (max-width: 420px) {
            form.js-auto-filter .d-flex.gap-1:not(.filter-actions-inline),
            form.js-auto-filter .d-flex.gap-2:not(.filter-actions-inline),
            form.js-auto-filter .d-flex.gap-3:not(.filter-actions-inline) {
                grid-template-columns: 1fr;
            }
        }

        .footer {
            margin-top: auto;
            padding: 20px 0;
            text-align: center;
            border-top: 1px solid var(--line-soft);
            background: transparent;
            font-size: 12px;
            color: var(--text-muted);
            letter-spacing: 0.2px;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .footer p {
            margin: 0;
        }

        .footer a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s ease;
        }

        .footer a:hover {
            color: var(--accent-strong);
        }

        @media print {
            .sidebar,
            .sidebar-toggle-mobile,
            .sidebar-overlay,
            .no-print,
            .btn,
            .pagination,
            .footer {
                display: none !important;
            }

            .main-content {
                margin-left: 0;
                padding: 20px;
            }

            body {
                background: white;
                color: #0f172a;
            }

            .card {
                border: 1px solid #e2e8f0;
                box-shadow: none;
            }

            .table td,
            .table th {
                background: white;
                color: #0f172a;
            }
        }
    </style>
<body>

    <button class="sidebar-toggle-mobile no-print" id="sidebarToggleMobile" aria-label="Toggle sidebar">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar-overlay no-print" id="sidebarOverlay"></div>

    <div class="sidebar no-print">
        <div>
            <div class="sidebar-header">
                <h4 class="mb-0 fw-bold"><img src="{{ asset('image/logo_pondok.jpeg') }}" alt="Logo SIKS" class="brand-logo me-2">SMA BPPT DARUS SHOLAH</h4>
            </div>

            @php
                $manajemenOpen = request()->is('data-siswa') || request()->is('item-pembayaran');
                $keuanganOpen = request()->is('tagihan') || request()->is('pengeluaran') || request()->is('transaksi-pembayaran') || request()->is('rekap');
                $laporanOpen = request()->is('laporan/wali-murid')
                    || request()->is('laporan/pemasukan-dana')
                    || request()->is('laporan/pengeluaran-dana')
                    || request()->is('laporan/rekap-kas')
                    || request()->is('laporan/yayasan')
                    || request()->is('laporan/yayasan/export');
                $sistemOpen = request()->is('hak-akses') || request()->is('riwayat-hapus') || request()->is('backup/database');
            @endphp

            <a href="{{ route('home') }}" class="{{ request()->is('/') ? 'active' : '' }}">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>

            @if(auth()->user() && auth()->user()->isSuperAdmin())
                <button type="button" class="sidebar-group-toggle {{ $manajemenOpen ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuManajemen" aria-expanded="{{ $manajemenOpen ? 'true' : 'false' }}" aria-controls="menuManajemen">
                    <span class="group-label"><i class="fas fa-users-cog me-2"></i>Manajemen</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <div id="menuManajemen" class="sidebar-submenu collapse {{ $manajemenOpen ? 'show' : '' }}">
                    <a href="{{ route('siswa.index') }}" class="{{ request()->is('data-siswa') ? 'active' : '' }}">
                        <i class="fas fa-user-graduate me-2"></i> Data Siswa
                    </a>
                    <a href="{{ route('item.index') }}" class="{{ request()->is('item-pembayaran') ? 'active' : '' }}">
                        <i class="fas fa-list me-2"></i> Item Pembayaran
                    </a>
                </div>

                <button type="button" class="sidebar-group-toggle {{ $keuanganOpen ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuKeuangan" aria-expanded="{{ $keuanganOpen ? 'true' : 'false' }}" aria-controls="menuKeuangan">
                    <span class="group-label"><i class="fas fa-wallet me-2"></i>Keuangan</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <div id="menuKeuangan" class="sidebar-submenu collapse {{ $keuanganOpen ? 'show' : '' }}">
                    <a href="{{ route('tagihan.index') }}" class="{{ request()->is('tagihan') ? 'active' : '' }}">
                        <i class="fas fa-file-invoice me-2"></i> Tagihan
                    </a>
                    <a href="{{ route('pembayaran.index') }}" class="{{ request()->is('transaksi-pembayaran') ? 'active' : '' }}">
                        <i class="fas fa-money-check-dollar me-2"></i> Transaksi Pembayaran
                    </a>
                    <a href="{{ route('pengeluaran.index') }}" class="{{ request()->is('pengeluaran') ? 'active' : '' }}">
                        <i class="fas fa-receipt me-2"></i> Pengeluaran
                    </a>
                    <a href="{{ route('rekap.index') }}" class="{{ request()->is('rekap') ? 'active' : '' }}">
                        <i class="fas fa-chart-line me-2"></i> Rekap
                    </a>
                </div>

                <button type="button" class="sidebar-group-toggle {{ $laporanOpen ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuLaporan" aria-expanded="{{ $laporanOpen ? 'true' : 'false' }}" aria-controls="menuLaporan">
                    <span class="group-label"><i class="fas fa-file-lines me-2"></i>Laporan</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <div id="menuLaporan" class="sidebar-submenu collapse {{ $laporanOpen ? 'show' : '' }}">
                    <a href="{{ route('laporan.pemasukan') }}" class="{{ request()->is('laporan/pemasukan-dana') ? 'active' : '' }}">
                        <i class="fas fa-arrow-down me-2"></i> Laporan Pemasukan
                    </a>
                    <a href="{{ route('laporan.pengeluaran') }}" class="{{ request()->is('laporan/pengeluaran-dana') ? 'active' : '' }}">
                        <i class="fas fa-arrow-up me-2"></i> Laporan Pengeluaran
                    </a>
                    <a href="{{ route('laporan.rekap-kas') }}" class="{{ request()->is('laporan/rekap-kas') ? 'active' : '' }}">
                        <i class="fas fa-wallet me-2"></i> Rekapitulasi Kas
                    </a>
                    <a href="{{ route('laporan.wali') }}" class="{{ request()->is('laporan/wali-murid') ? 'active' : '' }}">
                        <i class="fas fa-user-group me-2"></i> Laporan Wali Murid
                    </a>
                    <a href="{{ route('laporan.yayasan') }}" class="{{ request()->is('laporan/yayasan') || request()->is('laporan/yayasan/export') ? 'active' : '' }}">
                        <i class="fas fa-building-columns me-2"></i> Laporan Yayasan
                    </a>
                </div>

                <button type="button" class="sidebar-group-toggle {{ $sistemOpen ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuSistem" aria-expanded="{{ $sistemOpen ? 'true' : 'false' }}" aria-controls="menuSistem">
                    <span class="group-label"><i class="fas fa-gear me-2"></i>Sistem</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <div id="menuSistem" class="sidebar-submenu collapse {{ $sistemOpen ? 'show' : '' }}">
                    <a href="{{ route('hak-akses.index') }}" class="{{ request()->is('hak-akses') ? 'active' : '' }}">
                        <i class="fas fa-user-shield me-2"></i> Hak Akses
                    </a>
                    <a href="{{ route('transaksi.riwayat') }}" class="{{ request()->is('riwayat-hapus') ? 'active' : '' }}">
                        <i class="fas fa-clock-rotate-left me-2"></i> Riwayat Hapus
                    </a>
                    <a href="{{ route('backup.database') }}" class="{{ request()->is('backup/database') ? 'active' : '' }}">
                        <i class="fas fa-database me-2"></i> Backup Database
                    </a>
                </div>
            @else
                <button type="button" class="sidebar-group-toggle {{ $manajemenOpen ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuManajemenAnggota" aria-expanded="{{ $manajemenOpen ? 'true' : 'false' }}" aria-controls="menuManajemenAnggota">
                    <span class="group-label"><i class="fas fa-users-cog me-2"></i>Manajemen</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <div id="menuManajemenAnggota" class="sidebar-submenu collapse {{ $manajemenOpen ? 'show' : '' }}">
                    <a href="{{ route('siswa.index') }}" class="{{ request()->is('data-siswa') ? 'active' : '' }}">
                        <i class="fas fa-user-graduate me-2"></i> Data Siswa
                    </a>
                </div>

                <button type="button" class="sidebar-group-toggle {{ $keuanganOpen ? '' : 'collapsed' }}" data-bs-toggle="collapse" data-bs-target="#menuKeuanganAnggota" aria-expanded="{{ $keuanganOpen ? 'true' : 'false' }}" aria-controls="menuKeuanganAnggota">
                    <span class="group-label"><i class="fas fa-wallet me-2"></i>Keuangan</span>
                    <i class="fas fa-chevron-down chevron"></i>
                </button>
                <div id="menuKeuanganAnggota" class="sidebar-submenu collapse {{ $keuanganOpen ? 'show' : '' }}">
                    <a href="{{ route('tagihan.index') }}" class="{{ request()->is('tagihan') ? 'active' : '' }}">
                        <i class="fas fa-file-invoice me-2"></i> Tagihan
                    </a>
                    <a href="{{ route('pembayaran.index') }}" class="{{ request()->is('transaksi-pembayaran') ? 'active' : '' }}">
                        <i class="fas fa-money-check-dollar me-2"></i> Transaksi Pembayaran
                    </a>
                    <a href="{{ route('rekap.index') }}" class="{{ request()->is('rekap') ? 'active' : '' }}">
                        <i class="fas fa-chart-line me-2"></i> Rekap
                    </a>
                </div>
            @endif
        </div>

        <div class="user-profile">
            @php
                $currentUser = auth()->user();
            @endphp
            <div class="d-flex align-items-center mb-3 user-info">
                <button type="button" class="profile-trigger me-2" data-bs-toggle="modal" data-bs-target="#userProfileModal" aria-label="Lihat profil pengguna">
                    <span class="profile-avatar">
                        <i class="fas fa-user small"></i>
                    </span>
                </button>
                <div>
                    <small>Halo, Admin</small>
                    <strong>{{ $currentUser->name ?? 'User' }}</strong>
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

    <div class="modal fade" id="userProfileModal" tabindex="-1" aria-labelledby="userProfileModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userProfileModalLabel"><i class="fas fa-id-badge me-2 text-primary"></i>Profil Pengguna</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <small class="text-muted d-block">Nama</small>
                        <div class="fw-semibold">{{ $currentUser->name ?? '-' }}</div>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted d-block">Email</small>
                        <div class="fw-semibold">{{ $currentUser->email ?? '-' }}</div>
                    </div>
                    <div>
                        <small class="text-muted d-block">Level Akses</small>
                        <div class="fw-semibold">{{ $currentUser && $currentUser->isSuperAdmin() ? 'Super Admin' : 'Anggota' }}</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmTitle">Konfirmasi Hapus</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="deleteConfirmMessage">Anda yakin ingin menghapus <strong id="deleteTargetLabel">data ini</strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Ya, Hapus</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="infoAlertModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="infoAlertTitle">Informasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="infoAlertMessage">Pesan informasi.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade payment-confirm-modal" id="paymentConfirmModal" tabindex="-1" aria-labelledby="paymentConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentConfirmModalLabel">Konfirmasi Pembayaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2" id="paymentConfirmModalText">Lanjutkan proses pembayaran ini?</p>
                    <div class="table-responsive" id="paymentConfirmItemsWrapper" style="display:none;">
                        <table class="table table-sm table-bordered mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Item &amp; Periode</th>
                                    <th class="text-end">Nominal</th>
                                    <th class="text-center">Metode</th>
                                </tr>
                            </thead>
                            <tbody id="paymentConfirmItemsBody"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="paymentConfirmCancelBtn" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="paymentConfirmNoPrintBtn">Bayar</button>
                    <button type="button" class="btn btn-outline-primary" id="paymentConfirmPrintBtn">Bayar + Cetak</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmActionTitle">Konfirmasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-0" id="confirmActionMessage">Yakin?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary" id="confirmActionOkBtn">Ya</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="printPreviewModal" tabindex="-1" aria-labelledby="printPreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="printPreviewModalLabel"><i class="fas fa-print me-2 text-primary"></i>Preview Cetak</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body p-0" style="min-height:70vh;">
                    <iframe id="printPreviewFrame" title="Preview cetak" style="width:100%;height:70vh;border:0;display:block;"></iframe>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
                    <button type="button" class="btn btn-primary" id="printPreviewBtn"><i class="fas fa-file-pdf me-1"></i> Cetak PDF</button>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer no-print">
        <div class="footer-content">
            <p>&copy; {{ date('Y') }} <strong>SIKS Bendahara Sekolah</strong></p>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const printPreviewModalElement = document.getElementById('printPreviewModal');
            const printPreviewFrame = document.getElementById('printPreviewFrame');
            const printPreviewBtn = document.getElementById('printPreviewBtn');
            const printPreviewModal = printPreviewModalElement ? new bootstrap.Modal(printPreviewModalElement) : null;
            const paymentConfirmModalElement = document.getElementById('paymentConfirmModal');
            const paymentConfirmText = document.getElementById('paymentConfirmModalText');
            const paymentConfirmNoPrintBtn = document.getElementById('paymentConfirmNoPrintBtn');
            const paymentConfirmPrintBtn = document.getElementById('paymentConfirmPrintBtn');
            const paymentConfirmItemsWrapper = document.getElementById('paymentConfirmItemsWrapper');
            const paymentConfirmItemsBody = document.getElementById('paymentConfirmItemsBody');
            const paymentConfirmModal = paymentConfirmModalElement ? new bootstrap.Modal(paymentConfirmModalElement) : null;
            const infoAlertModalElement = document.getElementById('infoAlertModal');
            const infoAlertTitle = document.getElementById('infoAlertTitle');
            const infoAlertMessage = document.getElementById('infoAlertMessage');
            const infoAlertModal = infoAlertModalElement ? new bootstrap.Modal(infoAlertModalElement) : null;
            let paymentConfirmSourceModal = null;
            let paymentConfirmState = null;
            let paymentConfirmCommitted = false;

            window.openInfoAlertModal = function (message, title) {
                if (!infoAlertModal || !infoAlertMessage) {
                    window.alert(message || 'Terjadi informasi.');
                    return false;
                }

                infoAlertMessage.textContent = message || 'Terjadi informasi.';
                if (infoAlertTitle) {
                    infoAlertTitle.textContent = title || 'Informasi';
                }

                infoAlertModal.show();
                return true;
            };

            const confirmActionModalElement = document.getElementById('confirmActionModal');
            const confirmActionTitle = document.getElementById('confirmActionTitle');
            const confirmActionMessage = document.getElementById('confirmActionMessage');
            const confirmActionOkBtn = document.getElementById('confirmActionOkBtn');
            const confirmActionModal = confirmActionModalElement ? new bootstrap.Modal(confirmActionModalElement) : null;
            let confirmActionCallback = null;

            window.openConfirmActionModal = function (message, title, onConfirm) {
                if (!confirmActionModal || !confirmActionMessage) {
                    if (typeof onConfirm === 'function' && confirm(message)) onConfirm();
                    return;
                }
                confirmActionMessage.textContent = message || 'Yakin?';
                if (confirmActionTitle) confirmActionTitle.textContent = title || 'Konfirmasi';
                confirmActionCallback = onConfirm || null;
                confirmActionModal.show();
            };

            if (confirmActionOkBtn) {
                confirmActionOkBtn.addEventListener('click', function () {
                    if (typeof confirmActionCallback === 'function') confirmActionCallback();
                    confirmActionModal?.hide();
                });
            }

            if (confirmActionModalElement) {
                confirmActionModalElement.addEventListener('hidden.bs.modal', function () {
                    confirmActionCallback = null;
                });
            }

            window.openPrintPopup = function (url) {
                if (!printPreviewModal || !printPreviewFrame || !url) {
                    return;
                }

                printPreviewFrame.setAttribute('src', url);
                printPreviewModal.show();
            };

            if (printPreviewBtn && printPreviewFrame) {
                printPreviewBtn.addEventListener('click', function () {
                    const frameWindow = printPreviewFrame.contentWindow;
                    if (!frameWindow) {
                        return;
                    }

                    frameWindow.focus();
                    frameWindow.print();
                });
            }

            if (printPreviewModalElement && printPreviewFrame) {
                printPreviewModalElement.addEventListener('hidden.bs.modal', function () {
                    printPreviewFrame.setAttribute('src', 'about:blank');
                });
            }

            window.openPaymentConfirmModal = function (options) {
                if (!paymentConfirmModal || !paymentConfirmText) {
                    return false;
                }

                paymentConfirmState = options || null;
                paymentConfirmSourceModal = options?.sourceModal || null;
                paymentConfirmCommitted = false;
                paymentConfirmText.textContent = options?.message || 'Lanjutkan proses pembayaran ini?';

                const showPrintButton = options?.showPrintButton !== false;
                if (paymentConfirmPrintBtn) {
                    paymentConfirmPrintBtn.style.display = showPrintButton ? '' : 'none';
                }

                const items = options?.items || [];
                if (paymentConfirmItemsWrapper && paymentConfirmItemsBody) {
                    if (items.length > 0) {
                        paymentConfirmItemsBody.innerHTML = '';
                        let totalNominal = 0;
                        items.forEach(function (item) {
                            const tr = document.createElement('tr');
                            const nominal = Number(item.nominal || 0);
                            totalNominal += nominal;
                            tr.innerHTML = '<td class="fw-semibold">' + escapeHtml(item.nama || '-') + '</td>' +
                                '<td class="text-end">Rp ' + nominal.toLocaleString('id-ID') + '</td>' +
                                '<td class="text-center">' + escapeHtml(item.metode || '-') + '</td>';
                            paymentConfirmItemsBody.appendChild(tr);
                        });
                        const trTotal = document.createElement('tr');
                        trTotal.classList.add('table-light', 'fw-bold');
                        trTotal.innerHTML = '<td class="text-end">Total</td>' +
                            '<td class="text-end">Rp ' + totalNominal.toLocaleString('id-ID') + '</td>' +
                            '<td></td>';
                        paymentConfirmItemsBody.appendChild(trTotal);
                        paymentConfirmItemsWrapper.style.display = '';
                    } else {
                        paymentConfirmItemsWrapper.style.display = 'none';
                        paymentConfirmItemsBody.innerHTML = '';
                    }
                }

                paymentConfirmModal.show();
                return true;
            };

            function escapeHtml(str) {
                var div = document.createElement('div');
                div.textContent = str;
                return div.innerHTML;
            }

            if (paymentConfirmNoPrintBtn) {
                paymentConfirmNoPrintBtn.addEventListener('click', function () {
                    paymentConfirmCommitted = true;
                    if (paymentConfirmState && typeof paymentConfirmState.onNoPrint === 'function') {
                        paymentConfirmState.onNoPrint();
                    }
                    paymentConfirmModal?.hide();
                });
            }

            if (paymentConfirmPrintBtn) {
                paymentConfirmPrintBtn.addEventListener('click', function () {
                    paymentConfirmCommitted = true;
                    if (paymentConfirmState && typeof paymentConfirmState.onPrint === 'function') {
                        paymentConfirmState.onPrint();
                    }
                    if (paymentConfirmSourceModal) {
                        bootstrap.Modal.getOrCreateInstance(paymentConfirmSourceModal).hide();
                    }
                    paymentConfirmModal?.hide();
                });
            }

            if (paymentConfirmModalElement) {
                paymentConfirmModalElement.addEventListener('hidden.bs.modal', function () {
                    if (!paymentConfirmCommitted && paymentConfirmSourceModal) {
                        bootstrap.Modal.getOrCreateInstance(paymentConfirmSourceModal).show();
                    }
                    paymentConfirmState = null;
                    paymentConfirmSourceModal = null;
                    paymentConfirmCommitted = false;
                });
            }

            // ===== Sidebar Toggle (Mobile only) =====
            var sidebarToggleMobile = document.getElementById('sidebarToggleMobile');
            var sidebarOverlay = document.getElementById('sidebarOverlay');
            var sidebar = document.querySelector('.sidebar');

            function toggleMobile(open) {
                if (!sidebar) return;
                var isOpen = open !== undefined ? open : !sidebar.classList.contains('open');
                sidebar.classList.toggle('open', isOpen);
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('show', isOpen);
                }
                document.body.style.overflow = isOpen ? 'hidden' : '';
                var icon = sidebarToggleMobile?.querySelector('i');
                if (icon) {
                    icon.className = isOpen ? 'fas fa-times' : 'fas fa-bars';
                }
            }

            if (sidebarToggleMobile) {
                sidebarToggleMobile.addEventListener('click', function () { toggleMobile(); });
            }
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function () { toggleMobile(false); });
            }

            // Close sidebar when clicking a nav link (mobile)
            if (sidebar) {
                sidebar.querySelectorAll('a').forEach(function (link) {
                    link.addEventListener('click', function () {
                        if (window.innerWidth <= 992) {
                            toggleMobile(false);
                        }
                    });
                });
            }

            // Reset on resize to desktop
            window.addEventListener('resize', function () {
                if (window.innerWidth > 992) {
                    sidebar?.classList.remove('open');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('show');
                    }
                    document.body.style.overflow = '';
                    var icon = sidebarToggleMobile?.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-bars';
                    }
                }
            });

            // Hover to temporarily reveal sidebar on desktop when collapsed
            if (sidebar) {
                sidebar.addEventListener('mouseenter', function () {
                    if (!isMobile() && sidebar.classList.contains('collapsed')) {
                        sidebar.classList.remove('no-hover');
                    }
                });
                sidebar.addEventListener('mouseleave', function () {
                    if (!isMobile() && sidebar.classList.contains('collapsed')) {
                        sidebar.classList.add('no-hover');
                    }
                });
            }

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (!prefersReducedMotion) {
                const sidebarItems = document.querySelectorAll('.sidebar a, .sidebar-group-toggle');
                sidebarItems.forEach((item, index) => {
                    item.classList.add('sidebar-fade-in');
                    item.style.animationDelay = `${Math.min(80 + (index * 32), 520)}ms`;
                });

                const revealTargets = document.querySelectorAll('.main-content h2, .main-content .alert, .main-content .card, .main-content .table-responsive');
                revealTargets.forEach((element, index) => {
                    element.classList.add('reveal-init');
                    element.style.setProperty('--reveal-delay', `${Math.min(index * 48, 420)}ms`);
                });

                const revealObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach((entry) => {
                        if (!entry.isIntersecting) {
                            return;
                        }

                        entry.target.classList.add('reveal-in');
                        observer.unobserve(entry.target);
                    });
                }, { threshold: 0.1 });

                revealTargets.forEach((element) => revealObserver.observe(element));
            }

            function initTablePaginator() {
                document.querySelectorAll('table[data-paginate]').forEach(function (table) {
                    const tbody = table.querySelector('tbody');
                    if (!tbody) return;
                    const rows = Array.from(tbody.querySelectorAll('tr'));
                    const perPage = parseInt(table.getAttribute('data-page-size')) || 5;
                    if (rows.length <= perPage) return;

                    const wrapper = document.createElement('div');
                    wrapper.className = 'd-flex justify-content-between align-items-center mt-2 px-2';

                    const info = document.createElement('small');
                    info.className = 'text-muted';

                    const nav = document.createElement('div');
                    nav.className = 'btn-group btn-group-sm';

                    const prevBtn = document.createElement('button');
                    prevBtn.className = 'btn btn-outline-secondary';
                    prevBtn.textContent = '\u00ab Sebelumnya';
                    prevBtn.type = 'button';

                    const nextBtn = document.createElement('button');
                    nextBtn.className = 'btn btn-outline-secondary';
                    nextBtn.textContent = 'Selanjutnya \u00bb';
                    nextBtn.type = 'button';

                    nav.appendChild(prevBtn);
                    nav.appendChild(nextBtn);
                    wrapper.appendChild(info);
                    wrapper.appendChild(nav);

                    table.parentNode.appendChild(wrapper);

                    let currentPage = 1;
                    const totalPages = Math.ceil(rows.length / perPage);

                    function showPage(page) {
                        currentPage = page;
                        rows.forEach(function (row, index) {
                            row.style.display = (index >= (page - 1) * perPage && index < page * perPage) ? '' : 'none';
                        });
                        prevBtn.disabled = page === 1;
                        nextBtn.disabled = page === totalPages;
                        info.textContent = 'Halaman ' + page + ' dari ' + totalPages + ' (total ' + rows.length + ' data)';
                    }

                    prevBtn.addEventListener('click', function () {
                        if (currentPage > 1) showPage(currentPage - 1);
                    });
                    nextBtn.addEventListener('click', function () {
                        if (currentPage < totalPages) showPage(currentPage + 1);
                    });

                    showPage(1);
                });
            }

            const rupiahInputs = document.querySelectorAll('input[data-rupiah="true"]');

            const formatRupiah = (value) => {
                const numeric = (value || '').toString().replace(/[^\d]/g, '');
                if (!numeric) return '';
                return new Intl.NumberFormat('id-ID').format(Number(numeric));
            };

            rupiahInputs.forEach((input) => {
                input.value = formatRupiah(input.value);

                input.addEventListener('input', function () {
                    const caret = this.selectionStart;
                    this.value = formatRupiah(this.value);
                    if (caret !== null) {
                        this.setSelectionRange(this.value.length, this.value.length);
                    }
                });
            });

            document.addEventListener('input', function (event) {
                const target = event.target;
                if (!target || target.getAttribute('data-rupiah') !== 'true') {
                    return;
                }

                const caret = target.selectionStart;
                target.value = formatRupiah(target.value);
                if (caret !== null) {
                    target.setSelectionRange(target.value.length, target.value.length);
                }
            });

            document.querySelectorAll('form').forEach((form) => {
                form.addEventListener('submit', function () {
                    this.querySelectorAll('input[data-rupiah="true"]').forEach((input) => {
                        input.value = (input.value || '').replace(/[^\d]/g, '');
                    });
                });
            });

            const deleteModalElement = document.getElementById('deleteConfirmModal');
            const deleteConfirmTitle = document.getElementById('deleteConfirmTitle');
            const deleteConfirmMessage = document.getElementById('deleteConfirmMessage');
            const deleteTargetLabel = document.getElementById('deleteTargetLabel');
            const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
            const deleteModal = deleteModalElement ? new bootstrap.Modal(deleteModalElement) : null;
            let activeDeleteForm = null;

            const defaultConfirmTitle = 'Konfirmasi Hapus';
            const defaultConfirmActionText = 'Ya, Hapus';
            const defaultConfirmActionClass = 'btn btn-danger';

            document.querySelectorAll('.btn-delete-confirm').forEach((button) => {
                button.addEventListener('click', function () {
                    const formId = this.getAttribute('data-form-id');
                    const label = this.getAttribute('data-delete-label') || 'data ini';
                    const customTitle = this.getAttribute('data-confirm-title');
                    const customMessage = this.getAttribute('data-confirm-message');
                    const customActionText = this.getAttribute('data-confirm-action-text');
                    const customActionClass = this.getAttribute('data-confirm-action-class');
                    const form = formId ? document.getElementById(formId) : null;

                    if (!form || !deleteModal) {
                        return;
                    }

                    activeDeleteForm = form;

                    if (deleteConfirmTitle) {
                        deleteConfirmTitle.textContent = customTitle || defaultConfirmTitle;
                    }

                    if (confirmDeleteBtn) {
                        confirmDeleteBtn.textContent = customActionText || defaultConfirmActionText;
                        confirmDeleteBtn.className = customActionClass || defaultConfirmActionClass;
                    }

                    if (deleteConfirmMessage) {
                        if (customMessage) {
                            deleteConfirmMessage.textContent = customMessage;
                        } else {
                            deleteConfirmMessage.innerHTML = 'Anda yakin ingin menghapus <strong id="deleteTargetLabel">data ini</strong>?';
                            const refreshedLabel = document.getElementById('deleteTargetLabel');
                            if (refreshedLabel) {
                                refreshedLabel.textContent = label;
                            }
                        }
                    } else if (deleteTargetLabel) {
                        deleteTargetLabel.textContent = label;
                    }

                    deleteModal.show();
                });
            });

            if (confirmDeleteBtn) {
                confirmDeleteBtn.addEventListener('click', function () {
                    if (activeDeleteForm) {
                        activeDeleteForm.submit();
                    }
                });
            }

            if (deleteModalElement) {
                deleteModalElement.addEventListener('hidden.bs.modal', function () {
                    activeDeleteForm = null;
                    if (deleteConfirmTitle) {
                        deleteConfirmTitle.textContent = defaultConfirmTitle;
                    }
                    if (deleteConfirmMessage) {
                        deleteConfirmMessage.innerHTML = 'Anda yakin ingin menghapus <strong id="deleteTargetLabel">data ini</strong>?';
                    }
                    if (confirmDeleteBtn) {
                        confirmDeleteBtn.textContent = defaultConfirmActionText;
                        confirmDeleteBtn.className = defaultConfirmActionClass;
                    }
                });
            }

            initTablePaginator();

            document.querySelectorAll('form.js-auto-filter').forEach((form) => {
                const hasSubmitButton = !!form.querySelector('button[type="submit"], input[type="submit"]');
                const forceAutoSubmit = form.hasAttribute('data-auto-submit');

                if (hasSubmitButton && !forceAutoSubmit) {
                    return;
                }

                let timer = null;
                const scheduleSubmit = () => {
                    clearTimeout(timer);
                    timer = setTimeout(() => form.submit(), 450);
                };

                form.querySelectorAll('input, select, textarea').forEach((field) => {
                    const type = (field.getAttribute('type') || '').toLowerCase();

                    if (type === 'hidden' || field.hasAttribute('data-no-auto-submit')) {
                        return;
                    }

                    if (field.tagName === 'SELECT' || type === 'date' || type === 'checkbox' || type === 'radio') {
                        field.addEventListener('change', scheduleSubmit);
                    } else {
                        field.addEventListener('input', scheduleSubmit);
                    }
                });
            });
        });
    </script>
</body>
</html>
