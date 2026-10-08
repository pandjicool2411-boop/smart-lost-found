<?php
function renderHead($title){ ?>
<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= e($title) ?> — Smart Lost & Found Kampus</title><link rel="stylesheet" href="/assets/app.css"></head><body>
<?php }
function mobileShortcuts($active='dashboard',$admin=false){
    if($admin){
        $items=[
            ['dashboard','⌂','Home','/admin/dashboard.php'],
            ['reports','▣','Laporan','/admin/reports.php'],
            ['claims','✓','Verifikasi','/admin/claims.php'],
            ['search','⌕','Cari','/temukan.php'],
            ['profile','◉','Profil','/profile.php']
        ];
    }else{
        $items=[
            ['dashboard','⌂','Home','/dashboard.php'],
            ['laporan','▣','Laporan','/laporan.php'],
            ['search','⌕','Cari','/temukan.php'],
            ['matching','✦','Match','/matching.php'],
            ['profile','◉','Profil','/profile.php']
        ];
    }
    echo '<nav class="mobile-shortcuts">';
    foreach($items as $it) echo '<a class="'.($active===$it[0]?'active':'').'" href="'.$it[3].'"><span class="shortcut-icon">'.$it[1].'</span><span>'.$it[2].'</span></a>';
    echo '</nav>';
}
function renderUserNav($active=''){
    $name=$_SESSION['name']??'Pengguna'; $initial=userInitial($name); ?>
<aside class="sidebar"><div class="brand">◆ <span>Smart Lost &<br>Found<br>Kampus</span></div><div class="menu-title">MENU</div><nav>
<a class="<?= $active==='dashboard'?'active':'' ?>" href="/dashboard.php">⌂ <span>Dashboard</span></a>
<a class="<?= $active==='laporan'?'active':'' ?>" href="/laporan.php">▣ <span>Laporan Saya</span></a>
<a class="<?= $active==='search'?'active':'' ?>" href="/temukan.php">⌕ <span>Cari Barang</span></a>
<a class="<?= $active==='matching'?'active':'' ?>" href="/matching.php">✦ <span>Smart Matching</span></a>
<a class="<?= $active==='klaim'?'active':'' ?>" href="/klaim-saya.php">◇ <span>Klaim Saya</span></a>
<a class="<?= $active==='masuk'?'active':'' ?>" href="/klaim-masuk.php">✓ <span>Persetujuan Penemu</span></a>
<div class="menu-title">AKUN</div>
<a class="<?= $active==='profile'?'active':'' ?>" href="/profile.php">◉ <span>Profil</span></a>
<a href="/logout.php">↪ <span>Logout</span></a>
</nav></aside>
<div class="mobile-top"><b>Smart Lost & Found</b><a href="/profile.php"><span class="avatar"><?= e($initial) ?></span></a></div>
<main class="main"><header class="topbar"><div><strong><?= e($titleForTop ?? '') ?></strong></div><div class="user-chip"><span class="avatar"><?= e($initial) ?></span><span><?= e($name) ?></span></div></header>
<?php mobileShortcuts($active,false); ?><a class="fab" href="/buat-laporan.php" aria-label="Buat laporan">+</a>
<?php }
function renderAdminNav($active=''){
    $name=$_SESSION['name']??'Admin'; $initial=userInitial($name); ?>
<aside class="sidebar admin-sidebar"><div class="brand">◆ <span>Smart Lost &<br>Found<br>Kampus</span></div><div class="menu-title">ADMIN</div><nav>
<a class="<?= $active==='dashboard'?'active':'' ?>" href="/admin/dashboard.php">⌂ <span>Dashboard</span></a>
<a class="<?= $active==='reports'?'active':'' ?>" href="/admin/reports.php">▣ <span>Verifikasi Laporan</span></a>
<a class="<?= $active==='claims'?'active':'' ?>" href="/admin/claims.php">◇ <span>Verifikasi Klaim</span></a>
<a href="/temukan.php">⌕ <span>Cari Barang</span></a>
<div class="menu-title">AKUN</div>
<a class="<?= $active==='profile'?'active':'' ?>" href="/profile.php">◉ <span>Profil</span></a>
<a href="/logout.php">↪ <span>Logout</span></a>
</nav></aside>
<div class="mobile-top"><b>Smart Lost & Found</b><a href="/profile.php"><span class="avatar"><?= e($initial) ?></span></a></div>
<main class="main"><header class="topbar"><strong><?= e($titleForTop ?? 'Dashboard Admin') ?></strong><div class="user-chip"><span class="avatar"><?= e($initial) ?></span><span><?= e($name) ?></span></div></header>
<?php mobileShortcuts($active,true); ?>
<?php }
function renderFooter(){ ?></main><script src="/assets/app.js"></script></body></html><?php }
?>