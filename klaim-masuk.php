<?php require_once 'config/database.php';require_once 'includes/auth.php';require_once 'includes/ui.php';requireProfile($conn);$uid=(int)$_SESSION['user_id'];$msg='';$err='';
if(($_GET['success']??'')==='approve') $msg='Klaim berhasil disetujui. Klaim sekarang diteruskan ke admin.';
if(($_GET['success']??'')==='reject') $msg='Klaim berhasil ditolak.';if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=(int)($_POST['claim_id']??0);
    $action=$_POST['action']??'';
    $note=trim($_POST['finder_note']??'');

    if($id<=0 || !in_array($action,['APPROVE','REJECT'],true)){
        $err='Aksi klaim tidak valid.';
    }else{
        // Pastikan klaim memang untuk barang yang ditemukan oleh user yang sedang login.
        $st=$conn->prepare("SELECT c.id,c.status,c.report_id,r.user_id AS finder_id
                            FROM claims c
                            JOIN reports r ON r.id=c.report_id
                            WHERE c.id=? AND r.user_id=? LIMIT 1");
        $st->bind_param('ii',$id,$uid);
        $st->execute();
        $c=$st->get_result()->fetch_assoc();
        $st->close();

        if(!$c){
            $err='Klaim tidak ditemukan atau bukan untuk barang yang kamu temukan.';
        }elseif($c['status']!=='PENDING'){
            $err='Klaim ini sudah diproses sebelumnya.';
        }else{
            $status=($action==='APPROVE')?'FINDER_APPROVED':'FINDER_REJECTED';
            $finderStatus=($action==='APPROVE')?'APPROVED':'REJECTED';

            // Sengaja hanya memakai kolom inti agar tetap kompatibel dengan database
            // yang sudah dipakai user, tanpa bergantung pada finder_approved_at.
            $st=$conn->prepare("UPDATE claims
                               SET status=?, finder_status=?, finder_note=?
                               WHERE id=? AND status='PENDING'");
            if(!$st){
                $err='Database error: '.$conn->error;
            }else{
                $st->bind_param('sssi',$status,$finderStatus,$note,$id);
                if($st->execute() && $st->affected_rows===1){
                    $st->close();
                    header('Location: klaim-masuk.php?success='.($action==='APPROVE'?'approve':'reject'));
                    exit;
                }
                $dbErr=$st->error;
                $st->close();
                $err='Klaim gagal diproses. '.($dbErr?'Detail: '.$dbErr:'Status klaim sudah berubah, silakan refresh halaman.');
            }
        }
    }
}

$st=$conn->prepare("SELECT c.*,f.item_name found_name,l.item_name lost_name,l.description lost_description,cl.name claimant,cl.email claimant_email,cl.student_status claimant_status,cl.semester claimant_semester,cl.fakultas claimant_fakultas,cl.nomor_hp claimant_hp FROM claims c JOIN reports f ON f.id=c.report_id JOIN reports l ON l.id=c.lost_report_id JOIN users cl ON cl.id=c.user_id WHERE f.user_id=? ORDER BY FIELD(c.status,'PENDING','FINDER_APPROVED','FINDER_REJECTED','APPROVED'),c.created_at DESC");$st->bind_param('i',$uid);$st->execute();$rows=$st->get_result();$titleForTop='Persetujuan Penemu';renderHead('Persetujuan Penemu');renderUserNav('masuk');?><div class="content animate-in"><div class="section-head"><div><h1 class="hero-title">Persetujuan Penemu</h1><p class="muted">Periksa data pelapor kehilangan sebelum menyetujui klaim barang yang kamu temukan.</p></div></div><?php if($msg): ?><div class="notice notice-ok"><?= e($msg) ?></div><?php endif; ?><?php if($err): ?><div class="notice notice-error"><?= e($err) ?></div><?php endif; ?><div class="grid grid-2 stagger"><?php if($rows->num_rows): while($c=$rows->fetch_assoc()): ?><div class="card claim-card"><span class="pill <?= $c['status']==='PENDING'?'pill-yellow':($c['status']==='FINDER_APPROVED'?'pill-green':'pill-red') ?>"><?= e(statusLabel($c['status'])) ?></span><h2><?= e($c['found_name']) ?></h2><div class="meta">Pelapor kehilangan: <?= e($c['claimant']) ?> (<?= e($c['claimant_email']) ?>)</div><div class="grid grid-2" style="margin-top:15px"><div><b>Status</b><p><?= e($c['claimant_status']??'-') ?></p><b>Semester</b><p><?= e($c['claimant_semester']??'-') ?></p></div><div><b>Fakultas</b><p><?= e($c['claimant_fakultas']??'-') ?></p><b>Nomor HP</b><p><?= e($c['claimant_hp']??'-') ?></p></div></div><b>Ciri-ciri yang diklaim</b><div class="notice notice-info"><?= nl2br(e($c['item_description'])) ?></div><b>Bukti kepemilikan</b><div class="notice notice-info"><?= nl2br(e($c['evidence'])) ?></div><?php if($c['status']==='PENDING'): ?><form method="post"><input type="hidden" name="claim_id" value="<?= $c['id'] ?>"><textarea class="form-control" name="finder_note" placeholder="Catatan untuk admin (opsional)"></textarea><div class="actions"><button type="submit" class="btn btn-green" name="action" value="APPROVE">✓ ACC Klaim</button><button type="submit" class="btn btn-red" data-confirm="Yakin menolak klaim ini?" name="action" value="REJECT">Tolak</button></div></form><?php elseif($c['finder_note']): ?><div class="notice notice-info">Catatan: <?= e($c['finder_note']) ?></div><?php endif; ?></div><?php endwhile;else: ?><div class="empty" style="grid-column:1/-1">Belum ada permintaan klaim untuk barang yang kamu temukan.</div><?php endif; ?></div></div><?php renderFooter(); ?>
