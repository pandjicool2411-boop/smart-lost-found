<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/ui.php';
requireProfile($conn);
$uid=(int)$_SESSION['user_id'];
$stats=[];
$st=$conn->prepare("SELECT COUNT(*) n FROM reports WHERE user_id=? AND type='LOST'");$st->bind_param('i',$uid);$st->execute();$stats['lost']=$st->get_result()->fetch_assoc()['n'];$st->close();
$st=$conn->prepare("SELECT COUNT(*) n FROM reports WHERE user_id=? AND type='FOUND'");$st->bind_param('i',$uid);$st->execute();$stats['found']=$st->get_result()->fetch_assoc()['n'];$st->close();
$st=$conn->prepare("SELECT COUNT(*) n FROM matches m JOIN reports r ON r.id=m.lost_report_id WHERE r.user_id=?");$st->bind_param('i',$uid);$st->execute();$stats['match']=$st->get_result()->fetch_assoc()['n'];$st->close();
$st=$conn->prepare("SELECT COUNT(*) n FROM claims WHERE user_id=?");$st->bind_param('i',$uid);$st->execute();$stats['claim']=$st->get_result()->fetch_assoc()['n'];$st->close();

$all=$conn->query("SELECT r.id,r.type,r.item_name,r.image,r.incident_date,r.status,c.name category_name,l.name location_name,u.name reporter_name
FROM reports r LEFT JOIN categories c ON c.id=r.category_id LEFT JOIN locations l ON l.id=r.location_id JOIN users u ON u.id=r.user_id
WHERE r.status IN ('VERIFIED','PENDING') ORDER BY r.created_at DESC LIMIT 12");
$recommend=$conn->prepare("SELECT m.score,m.matching_reason,f.id,f.item_name,f.image,f.incident_date,c.name category_name,l.name location_name
FROM matches m JOIN reports lost ON lost.id=m.lost_report_id JOIN reports f ON f.id=m.found_report_id
LEFT JOIN categories c ON c.id=f.category_id LEFT JOIN locations l ON l.id=f.location_id
WHERE lost.user_id=? AND f.status='VERIFIED' ORDER BY m.score DESC LIMIT 3");
$recommend->bind_param('i',$uid);$recommend->execute();$matches=$recommend->get_result();

$name=$_SESSION['name']??'Pengguna';$titleForTop='Dashboard';renderHead('Dashboard');renderUserNav('dashboard');
?>
<div class="content animate-in">
<section class="section-head"><div><h1 class="hero-title">Halo, <?= e($name) ?> 👋</h1><p class="muted">Temukan kembali barangmu melalui Smart Lost & Found Kampus.</p></div><a class="btn btn-blue" href="buat-laporan.php">＋ Buat Laporan</a></section>

<div class="dashboard-grid stagger">
<div class="card stat-card red"><small>Barang Hilang</small><div class="big animate-number"><?= $stats['lost'] ?></div><span class="meta">Laporan kehilangan kamu</span></div>
<div class="card stat-card green"><small>Barang Ditemukan</small><div class="big animate-number"><?= $stats['found'] ?></div><span class="meta">Laporan penemuan kamu</span></div>
<div class="card stat-card blue"><small>Kecocokan Barang</small><div class="big animate-number"><?= $stats['match'] ?></div><span class="meta">Hasil Smart Matching</span></div>
<div class="card stat-card yellow"><small>Total Klaim</small><div class="big animate-number"><?= $stats['claim'] ?></div><span class="meta">Klaim yang pernah dibuat</span></div>
</div>

<section class="section">
<div class="section-head"><div><h2>Semua Laporan Barang</h2><p class="muted">Barang hilang dan barang ditemukan yang sudah masuk ke sistem.</p></div><a class="top-link" href="temukan.php">Lihat semua →</a></div>
<div class="grid grid-3 stagger">
<?php if($all->num_rows): while($r=$all->fetch_assoc()): ?>
<a class="card item-card" href="detail-barang.php?id=<?= (int)$r['id'] ?>">
<div class="thumb"><?php if($r['image']): ?><img src="uploads/<?= e($r['image']) ?>" alt="<?= e($r['item_name']) ?>"><?php else: ?>📦<?php endif; ?></div>
<div class="body">
<span class="pill <?= $r['type']==='FOUND'?'pill-green':'pill-red' ?>"><?= $r['type']==='FOUND'?'Ditemukan':'Hilang' ?></span>
<h3><?= e($r['item_name']) ?></h3>
<div class="meta">📍 <?= e($r['location_name']??'-') ?><br>📅 <?= e($r['incident_date']) ?><br>👤 <?= e($r['reporter_name']) ?></div>
<div class="mini-actions"><span class="pill <?= $r['status']==='VERIFIED'?'pill-green':'pill-yellow' ?>"><?= e(statusLabel($r['status'])) ?></span><span class="top-link">Detail →</span></div>
</div></a>
<?php endwhile; else: ?><div class="empty" style="grid-column:1/-1">Belum ada laporan barang.</div><?php endif; ?>
</div></section>

<section class="section">
<div class="section-head"><div><h2 class="recommend-title">Rekomendasi Matching</h2><p class="muted">Kecocokan terbaik dari laporan kehilanganmu.</p></div><a class="top-link" href="matching.php">View All →</a></div>
<div class="grid grid-3 stagger">
<?php if($matches->num_rows): while($m=$matches->fetch_assoc()): ?>
<a class="card item-card" href="detail-barang.php?id=<?= (int)$m['id'] ?>"><div class="thumb"><?php if($m['image']): ?><img src="uploads/<?= e($m['image']) ?>" alt=""><?php else: ?>📦<?php endif; ?></div><div class="body"><div class="section-head" style="margin-bottom:5px"><h3><?= e($m['item_name']) ?></h3><span class="pill pill-green"><?= (int)$m['score'] ?>%</span></div><div class="meta">📍 <?= e($m['location_name']??'-') ?><br>📅 <?= e($m['incident_date']) ?></div><div class="progress" style="margin-top:12px"><span style="width:<?= min(100,(int)$m['score']) ?>%"></span></div></div></a>
<?php endwhile; else: ?><div class="empty" style="grid-column:1/-1">Belum ada rekomendasi. Buat laporan kehilangan lalu jalankan Smart Matching.</div><?php endif; ?>
</div></section>
</div>
<?php renderFooter(); ?>