<?php
/**
 * views/layout/header.php
 * Shared HTML Head + Sidebar + Topbar
 * ─────────────────────────────────────
 * Expects: $pageTitle (string), $activePage (string)
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../controllers/AuthController.php';

AuthController::startSession();

$pageTitle  = $pageTitle  ?? APP_NAME;
$activePage = $activePage ?? '';

$username    = $_SESSION[SESSION_USERNAME]  ?? 'User';
$role        = $_SESSION[SESSION_ROLE]      ?? 'staff';
$company     = $_SESSION[SESSION_COMPANY]   ?? APP_NAME;
$userInitial = strtoupper(substr($username, 0, 1));
$isAdmin     = ($role === ROLE_ADMIN);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars(APP_NAME) ?> — Enterprise Inventory & Supplier Management">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($pageTitle) ?> — <?= htmlspecialchars(APP_NAME) ?></title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="/Collaborative_project/assets/css/main.css">
    <link rel="stylesheet" href="/Collaborative_project/assets/css/dashboard.css">
    <link rel="stylesheet" href="/Collaborative_project/assets/css/theme.css">

    <!-- Favicon (inline SVG as data URI) -->
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
</head>
<body>

<!-- ═══════════════════════════════════════════════════════════ APP SHELL ═══ -->
<div class="app-shell" id="appShell">

    <!-- ══════════════════════════════════════════════════════ SIDEBAR ══════ -->
    <aside class="sidebar" id="sidebar" aria-label="Main navigation">

        <!-- Logo -->
        <div class="sidebar-logo">
            <div class="logo-icon" aria-hidden="true">📦</div>
            <div class="logo-text">Inven<span>Track</span></div>
        </div>

        <!-- Navigation -->
        <nav class="sidebar-nav" aria-label="Primary navigation">

            <div class="nav-section-label">Overview</div>

            <a href="/Collaborative_project/index.php"
               class="nav-item <?= $activePage === 'dashboard' ? 'active' : '' ?>"
               id="nav-dashboard">
                <span class="nav-icon" aria-hidden="true">🏠</span>
                <span>Dashboard</span>
            </a>

            <div class="nav-section-label">Inventory</div>

            <a href="/Collaborative_project/views/products.php"
               class="nav-item <?= $activePage === 'products' ? 'active' : '' ?>"
               id="nav-products">
                <span class="nav-icon" aria-hidden="true">📦</span>
                <span>Products</span>
                <span class="nav-badge" data-badge="low-stock" style="display:none;" aria-label="Low stock alerts">0</span>
            </a>

            <a href="/Collaborative_project/views/suppliers.php"
               class="nav-item <?= $activePage === 'suppliers' ? 'active' : '' ?>"
               id="nav-suppliers">
                <span class="nav-icon" aria-hidden="true">🏭</span>
                <span>Suppliers</span>
            </a>

            <a href="/Collaborative_project/views/stock_update.php"
               class="nav-item <?= $activePage === 'stock' ? 'active' : '' ?>"
               id="nav-stock">
                <span class="nav-icon" aria-hidden="true">🔄</span>
                <span>Stock Update</span>
            </a>

            <div class="nav-section-label">Procurement</div>

            <a href="/Collaborative_project/views/orders.php"
               class="nav-item <?= $activePage === 'orders' ? 'active' : '' ?>"
               id="nav-orders">
                <span class="nav-icon" aria-hidden="true">📋</span>
                <span>Purchase Orders</span>
            </a>

            <?php if ($isAdmin): ?>
            <div class="nav-section-label">Admin</div>
            <a href="/Collaborative_project/views/suppliers.php?action=new"
               class="nav-item <?= $activePage === 'add-supplier' ? 'active' : '' ?>">
                <span class="nav-icon" aria-hidden="true">➕</span>
                <span>Add Supplier</span>
            </a>
            <a href="/Collaborative_project/views/products.php?action=new"
               class="nav-item">
                <span class="nav-icon" aria-hidden="true">➕</span>
                <span>Add Product</span>
            </a>
            <?php endif; ?>

        </nav>

        <!-- User Footer -->
        <div class="sidebar-footer">
            <div class="user-avatar" aria-hidden="true"><?= $userInitial ?></div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($username) ?></div>
                <div class="user-role"><?= htmlspecialchars($role) ?> · <?= htmlspecialchars($company) ?></div>
            </div>
        </div>

    </aside>

    <!-- ═══════════════════════════════════════════════════════ TOPBAR ═══════ -->
    <header class="topbar" role="banner">

        <button class="topbar-toggle" id="sidebarToggle"
                aria-label="Toggle sidebar" aria-expanded="true">☰</button>

        <h1 class="topbar-title"><?= htmlspecialchars($pageTitle) ?></h1>

        <div class="topbar-actions">
            <!-- Org badge -->
            <span style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:6px;">
                <span style="width:8px;height:8px;border-radius:50%;background:var(--brand-success);display:inline-block;"></span>
                <?= htmlspecialchars($company) ?>
            </span>

            <!-- Theme toggle -->
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">🌙</button>

            <!-- Logout -->
            <a href="/Collaborative_project/logout.php"
               class="btn btn-secondary btn-sm"
               aria-label="Log out">⎋ Logout</a>
        </div>

    </header>

    <!-- ═════════════════════════════════════════════════ MAIN CONTENT ═══════ -->
    <main class="main-content" id="mainContent" role="main">
