<?php
/**
 * install.php
 * ─────────────────────────────────────────────────────────────────────────────
 * One-time setup script:
 *   1. Imports database/schema.sql into MySQL
 *   2. Generates correct bcrypt hashes and updates user passwords
 *   3. Verifies all tables were created
 *
 * RUN ONCE via browser: http://localhost/Collaborative_project/install.php
 * DELETE or RESTRICT access after successful installation.
 * ─────────────────────────────────────────────────────────────────────────────
 */

declare(strict_types=1);

// ── DB Credentials (match config/db.php) ──────────────────────────────────────
$host    = 'localhost';
$port    = '3306';
$user    = 'root';
$pass    = '';
$dbname  = 'inventory_system_db';

// ── Real passwords for seed accounts ──────────────────────────────────────────
$accounts = [
    ['username' => 'admin1', 'password' => 'Admin@123'],
    ['username' => 'staff1', 'password' => 'Staff@123'],
    ['username' => 'admin2', 'password' => 'Admin@123'],
    ['username' => 'staff2', 'password' => 'Staff@123'],
];

$log = [];

function logMsg(string $msg, bool $ok = true): void {
    global $log;
    $log[] = ['msg' => $msg, 'ok' => $ok];
}

// ── Step 1: Connect WITHOUT selecting a database (for initial creation) ────────
try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    logMsg('✅ Connected to MySQL server.');
} catch (PDOException $e) {
    die('<pre style="color:red">❌ Cannot connect to MySQL: ' . htmlspecialchars($e->getMessage()) . "\n\nPlease check your XAMPP MySQL service is running.</pre>");
}

// ── Step 2: Execute schema.sql ─────────────────────────────────────────────────
$schemaFile = __DIR__ . '/database/schema.sql';
if (!file_exists($schemaFile)) {
    die('<pre style="color:red">❌ database/schema.sql not found.</pre>');
}

$sql = file_get_contents($schemaFile);

// Split on semicolon (multi-statement) and execute each
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    static fn($s) => $s !== ''
);

$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

foreach ($statements as $stmt) {
    if (preg_match('/^\s*(--|#|\/\*)/m', $stmt)) continue;  // skip comments
    try {
        $pdo->exec($stmt);
    } catch (PDOException $e) {
        // Tolerate "already exists" for idempotent re-runs
        if (strpos($e->getMessage(), 'already exists') === false &&
            strpos($e->getMessage(), 'Duplicate entry') === false) {
            logMsg('⚠️ SQL warning: ' . htmlspecialchars(substr($stmt, 0, 80)) . '… — ' . $e->getMessage(), false);
        }
    }
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
logMsg('✅ Schema imported: database created, all tables installed.');

// ── Step 3: Switch to the target database ─────────────────────────────────────
$pdo->exec("USE $dbname");

// ── Step 4: Update user passwords with correct bcrypt hashes ──────────────────
foreach ($accounts as $acct) {
    $hash = password_hash($acct['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $stmt = $pdo->prepare('UPDATE users SET password = :hash WHERE username = :username');
    $stmt->execute([':hash' => $hash, ':username' => $acct['username']]);
    logMsg("✅ Password set for user: <strong>{$acct['username']}</strong> → {$acct['password']}");
}

// ── Step 5: Verify tables ─────────────────────────────────────────────────────
$expectedTables = ['tenants','users','suppliers','products','orders','order_items','stock_history'];
$existing = $pdo->query("SHOW TABLES FROM $dbname")->fetchAll(PDO::FETCH_COLUMN);

foreach ($expectedTables as $table) {
    if (in_array($table, $existing, true)) {
        logMsg("✅ Table verified: <strong>$table</strong>");
    } else {
        logMsg("❌ Table MISSING: <strong>$table</strong>", false);
    }
}

// ── Step 6: Count seeded rows ─────────────────────────────────────────────────
foreach (['tenants','users','suppliers','products','orders','order_items','stock_history'] as $t) {
    $count = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
    logMsg("📊 <strong>$t</strong>: $count row(s) seeded.");
}

// ── Render Results ────────────────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>InvenTrack Pro — Installation</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', sans-serif; background: #0f172a; color: #e2e8f0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: #1e293b; border: 1px solid #334155; border-radius: 14px; padding: 40px; max-width: 700px; width: 100%; box-shadow: 0 24px 64px rgba(0,0,0,0.4); }
        h1 { font-size: 24px; font-weight: 800; margin-bottom: 6px; }
        h1 span { color: #6366f1; }
        .sub { color: #94a3b8; font-size: 13px; margin-bottom: 28px; }
        .log-item { padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 13px; display: flex; align-items: flex-start; gap: 10px; border: 1px solid transparent; }
        .log-ok   { background: rgba(34,197,94,0.08);  border-color: rgba(34,197,94,0.15); }
        .log-warn { background: rgba(239,68,68,0.08);  border-color: rgba(239,68,68,0.15); color: #fca5a5; }
        .cta { margin-top: 28px; display: flex; gap: 12px; flex-wrap: wrap; }
        .btn { display: inline-block; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 700; text-decoration: none; cursor: pointer; }
        .btn-primary { background: #6366f1; color: #fff; }
        .btn-primary:hover { background: #4f46e5; }
        .warning-box { background: rgba(245,158,11,0.12); border: 1px solid rgba(245,158,11,0.25); border-radius: 10px; padding: 14px 18px; font-size: 12px; color: #fcd34d; margin-top: 20px; }
    </style>
</head>
<body>
<div class="card">
    <h1>Inven<span>Track</span> Pro</h1>
    <p class="sub">Installation Complete — <?= date('Y-m-d H:i:s') ?></p>

    <?php foreach ($log as $entry): ?>
    <div class="log-item <?= $entry['ok'] ? 'log-ok' : 'log-warn' ?>">
        <?= $entry['msg'] ?>
    </div>
    <?php endforeach; ?>

    <div class="warning-box">
        ⚠️ <strong>Security Notice:</strong> Delete or restrict access to <code>install.php</code> after installation.
        This file exposes database credentials and performs destructive operations.
    </div>

    <div class="cta">
        <a href="/Collaborative_project/login.php" class="btn btn-primary">→ Go to Login Page</a>
    </div>

    <div style="margin-top:20px;padding:16px;background:#0f172a;border-radius:8px;font-size:12px;color:#94a3b8;line-height:1.8;">
        <strong style="color:#e2e8f0;display:block;margin-bottom:8px;">Demo Login Credentials:</strong>
        admin1 / Admin@123 (NovaTech — full access)<br>
        staff1 / Staff@123 (NovaTech — read + stock update only)<br>
        admin2 / Admin@123 (Meridian — separate tenant data)<br>
        staff2 / Staff@123 (Meridian — read + stock update only)
    </div>
</div>
</body>
</html>
