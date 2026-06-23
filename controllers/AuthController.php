<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/constants.php';
class AuthController {
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>false,'httponly'=>true,'samesite'=>'Strict']);
            session_start();
        }
    }
    public static function login(string $username, string $plainPassword): bool {
        self::startSession();
        $sql = 'SELECT u.id,u.tenant_id,u.username,u.password,u.role,t.company_name FROM users u JOIN tenants t ON t.id=u.tenant_id WHERE u.username=:username AND u.is_active=1 AND t.is_active=1 LIMIT 1';
        $user = DB::query($sql,[':username'=>$username])->fetch();
        if (!$user) return false;
        if (!password_verify($plainPassword,$user['password'])) return false;
        session_regenerate_id(true);
        $_SESSION[SESSION_USER_ID]=$user['id'];
        $_SESSION[SESSION_TENANT_ID]=$user['tenant_id'];
        $_SESSION[SESSION_ROLE]=$user['role'];
        $_SESSION[SESSION_USERNAME]=$user['username'];
        $_SESSION[SESSION_COMPANY]=$user['company_name'];
        return true;
    }
    public static function logout(): void {
        self::startSession();
        $_SESSION=[];
        session_destroy();
        header('Location: /Collaborative_project/login.php');
        exit;
    }
    public static function requireAuth(): void {
        self::startSession();
        if (empty($_SESSION[SESSION_USER_ID])) { header('Location: /Collaborative_project/login.php'); exit; }
    }
    public static function requireRole(string $role, bool $jsonMode=false): void {
        self::requireAuth();
        if ($_SESSION[SESSION_ROLE]!==$role) {
            if ($jsonMode) { http_response_code(403); header('Content-Type: application/json'); echo json_encode(['error'=>ERR_UNAUTHORIZED]); exit; }
            http_response_code(403);
            require_once __DIR__.'/../views/layout/header.php';
            echo '<div class="error-page"><div class="error-code">403</div><div class="error-msg">'.htmlspecialchars(ERR_UNAUTHORIZED).'</div><a href="/Collaborative_project/index.php" class="btn btn-primary">Dashboard</a></div>';
            require_once __DIR__.'/../views/layout/footer.php';
            exit;
        }
    }
    public static function isAdmin(): bool { return isset($_SESSION[SESSION_ROLE]) && $_SESSION[SESSION_ROLE]===ROLE_ADMIN; }
    public static function tenantId(): int { return (int)($_SESSION[SESSION_TENANT_ID]??0); }
    public static function userId(): int { return (int)($_SESSION[SESSION_USER_ID]??0); }
    public static function hashPassword(string $plain): string { return password_hash($plain, PASSWORD_BCRYPT, ['cost'=>12]); }
}
