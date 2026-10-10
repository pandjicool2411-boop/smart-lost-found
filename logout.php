<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Simpan nama cookie sebelum session dihancurkan
$cookieName = session_name();
$cookieParams = session_get_cookie_params();

// Kosongkan data session
$_SESSION = [];

// Hapus session dari penyimpanan TiDB
if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Hapus cookie session di browser
if (ini_get('session.use_cookies')) {
    setcookie(
        $cookieName,
        '',
        [
            'expires' => time() - 3600,
            'path' => $cookieParams['path'] ?: '/',
            'domain' => $cookieParams['domain'],
            'secure' => $cookieParams['secure'],
            'httponly' => $cookieParams['httponly'],
            'samesite' => $cookieParams['samesite'] ?? 'Lax'
        ]
    );
}

// Redirect ke login
header('Location: /login.php');
exit;