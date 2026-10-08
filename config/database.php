<?php

$localConfig = __DIR__ . '/database.local.php';

if (file_exists($localConfig)) {
    $config = require $localConfig;

    $host = $config['host'];
    $port = $config['port'];
    $username = $config['username'];
    $password = $config['password'];
    $database = $config['database'];
    $ca = $config['ca'];
} else {
    $host = getenv('DB_HOST');
    $port = (int) (getenv('DB_PORT') ?: 4000);
    $username = getenv('DB_USERNAME');
    $password = getenv('DB_PASSWORD');
    $database = getenv('DB_DATABASE');

    $ca = __DIR__ . '/isrgrootx1.pem';
}

if (!$host || !$username || !$password || !$database) {
    die("Konfigurasi database belum lengkap.");
}

$conn = mysqli_init();

mysqli_ssl_set(
    $conn,
    NULL,
    NULL,
    $ca,
    NULL,
    NULL
);

if (!mysqli_real_connect(
    $conn,
    $host,
    $username,
    $password,
    $database,
    $port,
    NULL,
    MYSQLI_CLIENT_SSL
)) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

$conn->set_charset("utf8mb4");