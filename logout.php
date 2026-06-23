<?php
/**
 * logout.php
 * Session Termination
 */

declare(strict_types=1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/controllers/AuthController.php';

AuthController::logout();   // destroys session + redirects to login
