```php
<?php

/*
|--------------------------------------------------------------------------
| DATABASE SESSION HANDLER
|--------------------------------------------------------------------------
*/

class TiDBSessionHandler implements SessionHandlerInterface
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->conn->prepare(
            'SELECT data FROM sessions WHERE id = ? LIMIT 1'
        );

        if (!$stmt) {
            error_log('Session read prepare failed: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('s', $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stmt->close();

        return $row ? $row['data'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO sessions (id, data, last_activity)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
                data = VALUES(data),
                last_activity = VALUES(last_activity)'
        );

        if (!$stmt) {
            error_log('Session write prepare failed: ' . $this->conn->error);
            return false;
        }

        $now = time();

        $stmt->bind_param('ssi', $id, $data, $now);

        $ok = $stmt->execute();

        if (!$ok) {
            error_log('Session write failed: ' . $stmt->error);
        }

        $stmt->close();

        return $ok;
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->conn->prepare(
            'DELETE FROM sessions WHERE id = ?'
        );

        if (!$stmt) {
            error_log('Session destroy prepare failed: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('s', $id);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function gc(int $max_lifetime): int|false
    {
        $expired = time() - $max_lifetime;

        $stmt = $this->conn->prepare(
            'DELETE FROM sessions WHERE last_activity < ?'
        );

        if (!$stmt) {
            error_log('Session GC prepare failed: ' . $this->conn->error);
            return false;
        }

        $stmt->bind_param('i', $expired);
        $ok = $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        return $ok ? $deleted : false;
    }
}


/*
|--------------------------------------------------------------------------
| REGISTER SESSION HANDLER
|--------------------------------------------------------------------------
| Pastikan database.php dimuat SEBELUM auth.php.
*/

if (session_status() === PHP_SESSION_NONE) {

    if (!isset($conn) || !($conn instanceof mysqli)) {
        error_log('Session handler: database connection is unavailable.');
        http_response_code(500);
        exit('Koneksi database untuk session tidak tersedia.');
    }

    mysqli_report(MYSQLI_REPORT_OFF);

    $handler = new TiDBSessionHandler($conn);

    if (!session_set_save_handler($handler, true)) {
        http_response_code(500);
        exit('Gagal mengaktifkan penyimpanan session.');
    }

    $isHttps =
        (
            !empty($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] !== 'off'
        )
        || (
            strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
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
| AUTHENTICATION
|--------------------------------------------------------------------------
*/

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id'])
        && (int) $_SESSION['user_id'] > 0;
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function isAdmin(): bool
{
    return isLoggedIn()
        && strtoupper(trim($_SESSION['role'] ?? 'USER')) === 'ADMIN';
}

function requireAdmin(): void
{
    requireLogin();

    if (!isAdmin()) {
        header('Location: /dashboard.php');
        exit;
    }
}


/*
|--------------------------------------------------------------------------
| HELPERS
|--------------------------------------------------------------------------
*/

function e($v): string
{
    return htmlspecialchars(
        (string) $v,
        ENT_QUOTES,
        'UTF-8'
    );
}

function userInitial($name): string
{
    return strtoupper(
        mb_substr(trim($name ?: 'U'), 0, 1)
    );
}

function profileComplete($conn, $userId): bool
{
    $stmt = $conn->prepare(
        'SELECT profile_completed
         FROM users
         WHERE id = ?
         LIMIT 1'
    );

    if (!$stmt) {
        error_log('Profile query failed: ' . $conn->error);
        return false;
    }

    $stmt->bind_param('i', $userId);
    $stmt->execute();

    $row = $stmt->get_result()->fetch_assoc();

    $stmt->close();

    return !empty($row['profile_completed']);
}

function requireProfile($conn): void
{
    requireLogin();

    if (isAdmin()) {
        return;
    }

    $userId = (int) $_SESSION['user_id'];

    if (!profileComplete($conn, $userId)) {
        $current = basename($_SERVER['PHP_SELF'] ?? '');

        if ($current !== 'profile.php') {
            header('Location: /profile.php?required=1');
            exit;
        }
    }
}

function statusLabel($status): string
{
    return match ($status) {
        'PENDING' => 'Menunggu Verifikasi',
        'VERIFIED' => 'Terverifikasi',
        'CLAIMED' => 'Sedang Diklaim',
        'RETURNED' => 'Selesai / Dikembalikan',
        'REJECTED' => 'Ditolak',
        'APPROVED' => 'Disetujui',
        'FINDER_APPROVED' => 'Disetujui Penemu',
        'FINDER_REJECTED' => 'Ditolak Penemu',
        'ADMIN_REJECTED' => 'Ditolak Admin',
        'COMPLETED' => 'Selesai',
        default => $status
    };
}