```php
<?php

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Hapus seluruh data session dari TiDB
$_SESSION = [];

if (session_status() === PHP_SESSION_ACTIVE) {
    session_destroy();
}

// Hapus cookie session dari browser
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax'
        ]
    );
}

// Kembali ke halaman login
header('Location: /login.php');
exit;