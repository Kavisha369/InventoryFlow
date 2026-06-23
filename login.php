<?php
/**
 * login.php
 * Login Page — Secure Session Initiation
 */

declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/controllers/AuthController.php';

AuthController::startSession();

// Already logged in → redirect to dashboard
if (!empty($_SESSION[SESSION_USER_ID])) {
    header('Location: /Collaborative_project/index.php');
    exit;
}

$error = '';

// ── Handle Login Submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter your username and password.';
    } else {
        if (AuthController::login($username, $password)) {
            header('Location: /Collaborative_project/index.php');
            exit;
        } else {
            $error = 'Invalid username or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sign in to InvenTrack Pro — Enterprise Inventory Management">
    <title>Sign In — <?= htmlspecialchars(APP_NAME) ?></title>
    <link rel="stylesheet" href="/Collaborative_project/assets/css/main.css">
    <link rel="stylesheet" href="/Collaborative_project/assets/css/theme.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>📦</text></svg>">
</head>
<body>

<div class="login-page">
    <div class="login-grid" aria-hidden="true"></div>

    <div class="login-card" role="main">

        <!-- Brand -->
        <div class="login-brand">
            <div class="logo-icon" aria-hidden="true">📦</div>
            <div class="brand-name">Inven<span>Track</span> Pro</div>
        </div>

        <h1 class="login-heading">Welcome back</h1>
        <p class="login-sub">Sign in to your inventory workspace</p>

        <!-- Error message -->
        <?php if ($error): ?>
        <div class="login-error" role="alert" aria-live="polite">
            ⚠️ <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form class="login-form" method="POST" action="/Collaborative_project/login.php" novalidate>
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <input type="text"
                       id="username"
                       name="username"
                       class="form-control"
                       placeholder="Enter your username"
                       value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                       required
                       autofocus
                       autocomplete="username"
                       aria-required="true">
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div style="position:relative;">
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control"
                           placeholder="Enter your password"
                           required
                           autocomplete="current-password"
                           aria-required="true"
                           style="padding-right:40px;">
                    <button type="button" id="togglePw" aria-label="Show/hide password"
                            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:rgba(148,163,184,0.5);font-size:16px;padding:4px;">
                        👁️
                    </button>
                </div>
            </div>

            <button type="submit" class="login-btn" id="loginBtn">
                Sign In →
            </button>
        </form>

        <!-- Demo Account Quick-Fill -->
        <div class="login-divider">Demo Accounts</div>
        <div class="login-demo-accounts">
            <div class="demo-account-title">Click to auto-fill credentials</div>

            <div class="demo-account" data-user="admin1" data-pass="password" tabindex="0" role="button" aria-label="Fill admin1 credentials">
                <div>
                    <div class="demo-name">admin1</div>
                    <div class="demo-role">Admin · NovaTech Solutions</div>
                </div>
                <div class="demo-creds">password</div>
            </div>

            <div class="demo-account" data-user="staff1" data-pass="password" tabindex="0" role="button" aria-label="Fill staff1 credentials">
                <div>
                    <div class="demo-name">staff1</div>
                    <div class="demo-role">Staff · NovaTech Solutions</div>
                </div>
                <div class="demo-creds">password</div>
            </div>

            <div class="demo-account" data-user="admin2" data-pass="password" tabindex="0" role="button" aria-label="Fill admin2 credentials">
                <div>
                    <div class="demo-name">admin2</div>
                    <div class="demo-role">Admin · Meridian Supplies</div>
                </div>
                <div class="demo-creds">password</div>
            </div>

        </div>
    </div>
</div>

<script>
// Demo account auto-fill
document.querySelectorAll('.demo-account').forEach(el => {
    const fill = () => {
        document.getElementById('username').value = el.dataset.user;
        document.getElementById('password').value = el.dataset.pass;
        document.getElementById('loginBtn').focus();
    };
    el.addEventListener('click', fill);
    el.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') fill(); });
});

// Password visibility toggle
document.getElementById('togglePw').addEventListener('click', () => {
    const pw = document.getElementById('password');
    pw.type  = pw.type === 'password' ? 'text' : 'password';
});

// Theme init (match system on login page)
const prefer = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
document.documentElement.setAttribute('data-theme', localStorage.getItem('inventrack_theme') || 'dark');
</script>

</body>
</html>
