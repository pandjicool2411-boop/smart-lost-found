<?php

session_start();

if (isset($_GET['set'])) {
    $_SESSION['test_user'] = 'SMART-LOST-FOUND';
    $_SESSION['test_time'] = date('Y-m-d H:i:s');

    echo '<h2>Session SET</h2>';
    echo '<pre>';
    print_r($_SESSION);
    echo '</pre>';

    echo '<a href="/session-test.php">Cek Session</a>';
    exit;
}

echo '<h2>Session CHECK</h2>';
echo '<pre>';
print_r($_SESSION);
echo '</pre>';

echo '<br>';
echo '<a href="/session-test.php?set=1">Set Session</a>';