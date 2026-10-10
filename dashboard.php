<?php

/*
|--------------------------------------------------------------------------
| PHP SESSION
|--------------------------------------------------------------------------
*/

if (session_status() === PHP_SESSION_NONE) {

    $isHttps =
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (
            isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
        );

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

function isLoggedIn()
{
    return isset($_SESSION['user_id'])
        && (int) $_SESSION['user_id'] > 0;
}


/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| ADMIN CHECK
|--------------------------------------------------------------------------
*/

function isAdmin()
{
    return isLoggedIn()
        && strtoupper(
            trim($_SESSION['role'] ?? 'USER')
        ) === 'ADMIN';
}


/*
|--------------------------------------------------------------------------
| REQUIRE ADMIN
|--------------------------------------------------------------------------
*/

function requireAdmin()
{
    requireLogin();

    if (!isAdmin()) {
        header('Location: /dashboard.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| HTML ESCAPE
|--------------------------------------------------------------------------
*/

function e($v)
{
    return htmlspecialchars(
        (string) $v,
        ENT_QUOTES,
        'UTF-8'
    );
}


/*
|--------------------------------------------------------------------------
| USER INITIAL
|--------------------------------------------------------------------------
*/

function userInitial($name)
{
    $name = trim($name ?: 'U');

    return strtoupper(
        mb_substr($name, 0, 1)
    );
}


/*
|--------------------------------------------------------------------------
| CHECK PROFILE
|--------------------------------------------------------------------------
*/

function profileComplete($conn, $userId)
{
    $st = $conn->prepare(
        'SELECT profile_completed
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    if (!$st) {
        return false;
    }

    $st->bind_param(
        'i',
        $userId
    );

    $st->execute();

    $result = $st->get_result();

    $row = $result->fetch_assoc();

    $st->close();

    return !empty($row['profile_completed']);
}


/*
|--------------------------------------------------------------------------
| REQUIRE PROFILE
|--------------------------------------------------------------------------
*/

function requireProfile($conn)
{
    requireLogin();

    if (isAdmin()) {
        return;
    }

    $userId = (int) ($_SESSION['user_id'] ?? 0);

    if ($userId <= 0) {
        header('Location: /login.php');
        exit;
    }

    if (!profileComplete($conn, $userId)) {

        $currentPage = basename(
            $_SERVER['PHP_SELF'] ?? ''
        );

        if ($currentPage !== 'profile.php') {
            header(
                'Location: /profile.php?required=1'
            );
            exit;
        }
    }
}


/*
|--------------------------------------------------------------------------
| STATUS LABEL
|--------------------------------------------------------------------------
*/

function statusLabel($status)
{
    return match ($status) {

        'PENDING'
            => 'Menunggu Verifikasi',

        'VERIFIED'
            => 'Terverifikasi',

        'CLAIMED'
            => 'Sedang Diklaim',

        'RETURNED'
            => 'Selesai / Dikembalikan',

        'REJECTED'
            => 'Ditolak',

        'APPROVED'
            => 'Disetujui',

        'FINDER_APPROVED'
            => 'Disetujui Penemu',

        'FINDER_REJECTED'
            => 'Ditolak Penemu',

        'ADMIN_REJECTED'
            => 'Ditolak Admin',

        'COMPLETED'
            => 'Selesai',

        default
            => $status
    };
}