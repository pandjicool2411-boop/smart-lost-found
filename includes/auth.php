<?php
if (session_status() === PHP_SESSION_NONE) session_start();

function isLoggedIn(){ return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0; }
function requireLogin(){ if(!isLoggedIn()){ header('Location: /smart-lost-found/login.php'); exit; } }
function isAdmin(){ return isLoggedIn() && strtoupper(trim($_SESSION['role'] ?? 'USER')) === 'ADMIN'; }
function requireAdmin(){ if(!isLoggedIn()){ header('Location: /smart-lost-found/login.php'); exit; } if(!isAdmin()){ header('Location: /smart-lost-found/dashboard.php'); exit; } }
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function userInitial($name){ return strtoupper(mb_substr(trim($name ?: 'U'),0,1)); }
function profileComplete($conn,$userId){
    $st=$conn->prepare('SELECT profile_completed FROM users WHERE id=? LIMIT 1');
    $st->bind_param('i',$userId); $st->execute(); $r=$st->get_result()->fetch_assoc(); $st->close();
    return !empty($r['profile_completed']);
}
function requireProfile($conn){
    requireLogin();
    if(isAdmin()) return;
    if(!profileComplete($conn,(int)$_SESSION['user_id'])){
        $current=basename($_SERVER['PHP_SELF']);
        if($current!=='profile.php'){ header('Location: /smart-lost-found/profile.php?required=1'); exit; }
    }
}
function statusLabel($status){
    return match($status){
        'PENDING'=>'Menunggu Verifikasi','VERIFIED'=>'Terverifikasi','CLAIMED'=>'Sedang Diklaim','RETURNED'=>'Selesai / Dikembalikan','REJECTED'=>'Ditolak',
        'APPROVED'=>'Disetujui','FINDER_APPROVED'=>'Disetujui Penemu','FINDER_REJECTED'=>'Ditolak Penemu','ADMIN_REJECTED'=>'Ditolak Admin','COMPLETED'=>'Selesai', default=>$status
    };
}
