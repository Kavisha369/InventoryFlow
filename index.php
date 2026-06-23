<?php
/**
 * index.php
 * Application Entry Point
 * ─────────────────────────
 * Redirects authenticated users to the dashboard.
 * Redirects unauthenticated users to login.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/AuthController.php';

AuthController::startSession();

if (!empty($_SESSION[SESSION_USER_ID])) {
    // Authenticated — show dashboard
    require_once __DIR__ . '/views/dashboard.php';
} else {
    // Not authenticated — go to login
    header('Location: /Collaborative_project/login.php');
    exit;
}
