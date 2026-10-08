<?php

require_once 'config/database.php';
require_once 'includes/auth.php';

$message = '';
$type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 6
    ) {
        $message = 'Isi nama, email valid, dan password minimal 6 karakter.';
        $type = 'error';

    } else {

        // Cek apakah email sudah terdaftar
        $st = $conn->prepare(
            'SELECT id FROM users WHERE email = ? LIMIT 1'
        );

        $st->bind_param('s', $email);
        $st->execute();

        $exists = $st->get_result()->fetch_assoc();

        $st->close();

        if ($exists) {

            $message = 'Email sudah terdaftar.';
            $type = 'error';

        } else {

            // Hash password
            $hash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            // Simpan user baru
            $st = $conn->prepare(
                "INSERT INTO users
                (name, email, password, role, profile_completed)
                VALUES (?, ?, ?, 'USER', 0)"
            );

            $st->bind_param(
                'sss',
                $name,
                $email,
                $hash
            );

            if ($st->execute()) {

                $st->close();

                header('Location: login.php?registered=1');
                exit;

            }

            $message = 'Registrasi gagal.';
            $type = 'error';

            $st->close();
        }
    }
}

?>

<!doctype html>
<html lang="id">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Daftar — Smart Lost & Found</title>

    <!-- FIX VERCEL -->
    <link
        rel="stylesheet"
        href="/assets/app.css"
    >

</head>

<body>

    <div
        class="content"
        style="
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
        "
    >

        <div
            class="card form-card animate-in"
            style="max-width:480px"
        >

            <h1>Daftar Akun</h1>

            <p class="muted">
                Buat akun untuk melaporkan dan menemukan barang di kampus.
            </p>

            <?php if ($message): ?>

                <div
                    class="notice notice-<?= $type === 'error' ? 'error' : 'ok' ?>"
                >
                    <?= e($message) ?>
                </div>

            <?php endif; ?>

            <form method="post">

                <div class="form-group">

                    <label>
                        Nama Lengkap
                    </label>

                    <input
                        class="form-control"
                        name="name"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <input
                        class="form-control"
                        type="email"
                        name="email"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Password
                    </label>

                    <input
                        class="form-control"
                        type="password"
                        name="password"
                        minlength="6"
                        required
                    >

                </div>

                <button
                    class="btn btn-primary btn-full"
                    type="submit"
                >
                    Buat Akun
                </button>

            </form>

            <p
                class="muted"
                style="text-align:center"
            >
                Sudah punya akun?

                <a href="login.php">
                    Login
                </a>
            </p>

        </div>

    </div>

    <!-- FIX VERCEL -->
    <script src="/assets/app.js"></script>

</body>

</html>