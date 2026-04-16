<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StockMaster — <?= ucfirst($current_page) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0a0f1e;
            --surface: #111827;
            --surface2: #1a2233;
            --border: #1f2d45;
            --accent: #f97316;
            --accent2: #fb923c;
            --text: #f1f5f9;
            --text2: #94a3b8;
            --muted: #64748b;
            --success: #22c55e;
            --warning: #eab308;
            --danger: #ef4444;
            --info: #3b82f6;
            --sidebar-w: 240px;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: flex;
        }

        /* SIDEBAR */
        .sidebar {
            width: var(--sidebar-w);
            background: var(--surface);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            position: fixed; top: 0; left: 0; bottom: 0;
            z-index: 100;
        }

        .sidebar-brand {
            padding: 24px 20px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; gap: 12px;
        }

        .sidebar-brand .icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }

        .sidebar-brand h2 {
            font-family: 'Syne', sans-serif;
            font-size: 1.1rem; font-weight: 800;
            line-height: 1;
        }

        .sidebar-brand h2 span { color: var(--accent); }
        .sidebar-brand p { font-size: 0.7rem; color: var(--muted); margin-top: 3px; }

        .sidebar-nav { flex: 1; padding: 16px 12px; overflow-y: auto; }

        .nav-section { margin-bottom: 24px; }
        .nav-label {
            font-size: 0.65rem; font-weight: 600;
            letter-spacing: 0.12em; text-transform: uppercase;
            color: var(--muted); padding: 0 8px;
            margin-bottom: 8px;
        }

        .nav-link {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px;
            border-radius: 10px;
            text-decoration: none;
            color: var(--text2);
            font-size: 0.875rem; font-weight: 500;
            transition: all 0.15s;
            margin-bottom: 2px;
        }

        .nav-link:hover { background: var(--surface2); color: var(--text); }
        .nav-link.active {
            background: rgba(249,115,22,0.15);
            color: var(--accent);
        }

        .nav-link .icon { font-size: 18px; width: 22px; text-align: center; }

        .sidebar-footer {
            padding: 16px 12px;
            border-top: 1px solid var(--border);
        }

        .admin-info {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 12px;
            background: var(--surface2);
            border-radius: 10px;
            margin-bottom: 8px;
        }

        .admin-avatar {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px; flex-shrink: 0;
        }

        .admin-info-text { flex: 1; min-width: 0; }
        .admin-info-text strong { display: block; font-size: 0.8rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .admin-info-text span { font-size: 0.7rem; color: var(--muted); }

        .btn-logout {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%;
            padding: 9px;
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: 8px;
            color: #fca5a5;
            text-decoration: none;
            font-size: 0.8rem; font-weight: 500;
            transition: all 0.15s;
        }

        .btn-logout:hover { background: rgba(239,68,68,0.2); }

        /* MAIN CONTENT */
        .main {
            margin-left: var(--sidebar-w);
            flex: 1;
            display: flex; flex-direction: column;
            min-height: 100vh;
        }

        .topbar {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 16px 28px;
            display: flex; align-items: center; justify-content: space-between;
            position: sticky; top: 0; z-index: 50;
        }

        .topbar h1 {
            font-family: 'Syne', sans-serif;
            font-size: 1.4rem; font-weight: 700;
        }

        .topbar-actions { display: flex; align-items: center; gap: 12px; }

        .badge-time {
            font-size: 0.8rem; color: var(--muted);
            background: var(--surface2);
            padding: 6px 12px; border-radius: 20px;
            border: 1px solid var(--border);
        }

        .content { padding: 28px; flex: 1; }

        /* CARDS & COMPONENTS */
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }

        .card-header {
            padding: 18px 22px;
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }

        .card-header h3 {
            font-family: 'Syne', sans-serif;
            font-size: 1rem; font-weight: 700;
        }

        .card-body { padding: 22px; }

        /* STAT CARDS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px; margin-bottom: 24px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 20px;
            position: relative; overflow: hidden;
            transition: transform 0.2s;
        }

        .stat-card:hover { transform: translateY(-2px); }

        .stat-card::before {
            content: '';
            position: absolute; top: 0; left: 0; right: 0; height: 3px;
        }

        .stat-card.orange::before { background: linear-gradient(90deg, var(--accent), var(--accent2)); }
        .stat-card.green::before  { background: linear-gradient(90deg, #22c55e, #4ade80); }
        .stat-card.blue::before   { background: linear-gradient(90deg, #3b82f6, #60a5fa); }
        .stat-card.red::before    { background: linear-gradient(90deg, #ef4444, #f87171); }

        .stat-icon { font-size: 28px; margin-bottom: 12px; }
        .stat-value {
            font-family: 'Syne', sans-serif;
            font-size: 1.8rem; font-weight: 800;
            line-height: 1;
        }

        .stat-label { font-size: 0.8rem; color: var(--muted); margin-top: 6px; }

        /* TABLES */
        .table-wrap { overflow-x: auto; }

        table { width: 100%; border-collapse: collapse; }

        th {
            font-size: 0.72rem; font-weight: 600;
            letter-spacing: 0.08em; text-transform: uppercase;
            color: var(--muted); padding: 12px 16px;
            border-bottom: 1px solid var(--border);
            text-align: left; white-space: nowrap;
        }

        td {
            padding: 13px 16px;
            font-size: 0.875rem;
            border-bottom: 1px solid rgba(31,45,69,0.5);
            vertical-align: middle;
        }

        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(249,115,22,0.03); }

        /* BADGES */
        .badge {
            display: inline-flex; align-items: center;
            padding: 4px 10px; border-radius: 20px;
            font-size: 0.72rem; font-weight: 600;
        }

        .badge-success { background: rgba(34,197,94,0.12); color: #4ade80; }
        .badge-danger  { background: rgba(239,68,68,0.12); color: #f87171; }
        .badge-warning { background: rgba(234,179,8,0.12); color: #fde047; }
        .badge-info    { background: rgba(59,130,246,0.12); color: #93c5fd; }

        /* BUTTONS */
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 8px;
            font-family: 'DM Sans', sans-serif;
            font-size: 0.85rem; font-weight: 500;
            cursor: pointer; border: none; text-decoration: none;
            transition: all 0.15s;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: white;
        }

        .btn-primary:hover { box-shadow: 0 4px 15px rgba(249,115,22,0.35); transform: translateY(-1px); }

        .btn-secondary {
            background: var(--surface2); color: var(--text);
            border: 1px solid var(--border);
        }

        .btn-secondary:hover { background: var(--border); }

        .btn-danger {
            background: rgba(239,68,68,0.12); color: #f87171;
            border: 1px solid rgba(239,68,68,0.2);
        }

        .btn-danger:hover { background: rgba(239,68,68,0.25); }

        .btn-sm { padding: 5px 10px; font-size: 0.78rem; }

        /* FORMS */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
        .form-group { margin-bottom: 16px; }
        .form-group.full { grid-column: 1 / -1; }

        label.form-label {
            display: block; font-size: 0.78rem; font-weight: 500;
            letter-spacing: 0.06em; text-transform: uppercase;
            color: var(--muted); margin-bottom: 7px;
        }

        input[type="text"], input[type="number"], input[type="password"],
        select, textarea {
            width: 100%; background: var(--bg);
            border: 1px solid var(--border); border-radius: 8px;
            padding: 10px 14px; color: var(--text);
            font-family: 'DM Sans', sans-serif; font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s; outline: none;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(249,115,22,0.1);
        }

        select option { background: var(--surface); }
        textarea { resize: vertical; min-height: 80px; }

        /* MODAL */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,0.7); z-index: 1000;
            align-items: center; justify-content: center;
            backdrop-filter: blur(4px);
        }

        .modal-overlay.active { display: flex; }

        .modal {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 20px; padding: 0;
            width: 90%; max-width: 560px;
            animation: modalIn 0.25s ease;
            max-height: 90vh; overflow-y: auto;
        }

        @keyframes modalIn {
            from { opacity: 0; transform: scale(0.95) translateY(10px); }
            to   { opacity: 1; transform: scale(1) translateY(0); }
        }

        .modal-header {
            padding: 20px 24px; border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
        }

        .modal-header h3 { font-family: 'Syne', sans-serif; font-size: 1.1rem; font-weight: 700; }

        .modal-close {
            width: 32px; height: 32px; border-radius: 8px;
            background: var(--surface2); border: 1px solid var(--border);
            color: var(--text2); font-size: 18px; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
        }

        .modal-close:hover { background: var(--border); }
        .modal-body { padding: 24px; }
        .modal-footer {
            padding: 16px 24px; border-top: 1px solid var(--border);
            display: flex; align-items: center; justify-content: flex-end; gap: 10px;
        }

        /* ALERTS */
        .alert {
            padding: 12px 16px; border-radius: 10px;
            font-size: 0.875rem; margin-bottom: 16px;
            display: flex; align-items: center; gap: 8px;
        }

        .alert-success { background: rgba(34,197,94,0.1); border: 1px solid rgba(34,197,94,0.2); color: #4ade80; }
        .alert-danger  { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.2); color: #f87171; }
        .alert-warning { background: rgba(234,179,8,0.1); border: 1px solid rgba(234,179,8,0.2); color: #fde047; }

        /* SEARCH */
        .search-bar {
            position: relative; max-width: 300px;
        }

        .search-bar input {
            padding-left: 38px;
        }

        .search-bar::before {
            content: '🔍'; position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
            font-size: 14px; pointer-events: none; z-index: 1;
        }

        /* EMPTY STATE */
        .empty-state {
            text-align: center; padding: 60px 20px;
            color: var(--muted);
        }

        .empty-state .icon { font-size: 48px; margin-bottom: 16px; }
        .empty-state h3 { font-family: 'Syne', sans-serif; color: var(--text2); margin-bottom: 8px; }
        .empty-state p { font-size: 0.875rem; }

        /* PAGINATION */
        .pagination {
            display: flex; align-items: center; gap: 6px;
            padding: 16px 22px; border-top: 1px solid var(--border);
        }

        .page-btn {
            min-width: 34px; height: 34px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: 8px; border: 1px solid var(--border);
            background: var(--surface2); color: var(--text2);
            font-size: 0.85rem; text-decoration: none;
            cursor: pointer; transition: all 0.15s;
        }

        .page-btn:hover, .page-btn.active {
            background: var(--accent); border-color: var(--accent); color: white;
        }

        /* RESPONSIVE */
        @media(max-width: 768px) {
            .sidebar { transform: translateX(-100%); transition: transform 0.3s; }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0; }
            .form-grid { grid-template-columns: 1fr; }
            .form-grid.cols-3 { grid-template-columns: 1fr; }
        }

        .low-stock-row td { background: rgba(239,68,68,0.04) !important; }
    </style>
</head>
<body>
<div class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="icon">🏪</div>
        <div>
            <h2>Stock<span>Master</span></h2>
            <p>General Store</p>
        </div>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-label">Main</div>
            <a href="dashboard.php" class="nav-link <?= $current_page === 'dashboard' ? 'active' : '' ?>">
                <span class="icon">📊</span> Dashboard
            </a>
        </div>
        <div class="nav-section">
            <div class="nav-label">Inventory</div>
            <a href="products.php" class="nav-link <?= $current_page === 'products' ? 'active' : '' ?>">
                <span class="icon">📦</span> Products
            </a>
            <a href="categories.php" class="nav-link <?= $current_page === 'categories' ? 'active' : '' ?>">
                <span class="icon">🏷️</span> Categories
            </a>
            <a href="stock_alerts.php" class="nav-link <?= $current_page === 'stock_alerts' ? 'active' : '' ?>">
                <span class="icon">⚠️</span> Stock Alerts
            </a>
        </div>
        <div class="nav-section">
            <div class="nav-label">Sales</div>
            <a href="new_sale.php" class="nav-link <?= $current_page === 'new_sale' ? 'active' : '' ?>">
                <span class="icon">🛒</span> New Sale
            </a>
            <a href="sales.php" class="nav-link <?= $current_page === 'sales' ? 'active' : '' ?>">
                <span class="icon">🧾</span> Sales History
            </a>
        </div>
        <div class="nav-section">
            <div class="nav-label">Analytics</div>
            <a href="reports.php" class="nav-link <?= $current_page === 'reports' ? 'active' : '' ?>">
                <span class="icon">📈</span> Reports
            </a>
        </div>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="admin-avatar">👤</div>
            <div class="admin-info-text">
                <strong><?= htmlspecialchars(getAdminName()) ?></strong>
                <span>Administrator</span>
            </div>
        </div>
        <a href="logout.php" class="btn-logout">🚪 Logout</a>
    </div>
</div>
<div class="main">
    <div class="topbar">
        <h1>
