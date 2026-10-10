<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/ui.php';
requireProfile($conn);

$uid = (int) ($_SESSION['user_id'] ?? 0);
$claimId = (int) ($_GET['claim_id'] ?? $_POST['claim_id'] ?? 0);

if ($claimId <= 0) {
    header('Location: klaim-saya.php');
    exit;
}

/* Only the claimant and the owner of the found report may access this conversation. */
$sql = "SELECT c.id, c.status, c.user_id AS claimant_id, c.report_id,
               f.user_id AS finder_id, f.item_name AS found_name,
               l.item_name AS lost_name, claimant.name AS claimant_name,
               finder.name AS finder_name
        FROM claims c
        JOIN reports f ON f.id = c.report_id
        JOIN reports l ON l.id = c.lost_report_id
        JOIN users claimant ON claimant.id = c.user_id
        JOIN users finder ON finder.id = f.user_id
        WHERE c.id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    exit('Chat sedang bermasalah. Silakan coba lagi.');
}
$stmt->bind_param('i', $claimId);
$stmt->execute();
$claim = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$claim || ($uid !== (int)$claim['claimant_id'] && $uid !== (int)$claim['finder_id'])) {
    http_response_code(403);
    exit('Kamu tidak memiliki akses ke percakapan ini.');
}

$otherId = ($uid === (int)$claim['claimant_id'])
    ? (int)$claim['finder_id']
    : (int)$claim['claimant_id'];
$otherName = ($uid === (int)$claim['claimant_id'])
    ? $claim['finder_name']
    : $claim['claimant_name'];

if (empty($_SESSION['chat_csrf'])) {
    $_SESSION['chat_csrf'] = bin2hex(random_bytes(32));
}
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = (string) ($_POST['csrf_token'] ?? '');
    $message = trim((string) ($_POST['message'] ?? ''));

    if (!hash_equals((string)$_SESSION['chat_csrf'], $token)) {
        $err = 'Sesi formulir tidak valid. Muat ulang halaman dan coba lagi.';
    } elseif ($message === '' || mb_strlen($message, 'UTF-8') > 2000) {
        $err = 'Pesan wajib diisi dan maksimal 2.000 karakter.';
    } else {
        $insert = $conn->prepare(
            'INSERT INTO claim_messages (claim_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)'
        );
        if (!$insert) {
            $err = 'Tabel chat belum tersedia. Jalankan file SQL chat_messages.sql terlebih dahulu.';
        } else {
            $insert->bind_param('iiis', $claimId, $uid, $otherId, $message);
            if ($insert->execute()) {
                $insert->close();
                header('Location: chat.php?claim_id=' . $claimId);
                exit;
            }
            $err = 'Pesan gagal dikirim. Pastikan tabel chat sudah dibuat.';
            $insert->close();
        }
    }
}

$messagesStmt = $conn->prepare(
    "SELECT m.id, m.sender_id, m.receiver_id, m.message, m.created_at, u.name AS sender_name
     FROM claim_messages m
     JOIN users u ON u.id = m.sender_id
     WHERE m.claim_id = ?
     ORDER BY m.created_at ASC, m.id ASC"
);
$messages = [];
if ($messagesStmt) {
    $messagesStmt->bind_param('i', $claimId);
    $messagesStmt->execute();
    $messages = $messagesStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $messagesStmt->close();
} else {
    $err = $err ?: 'Tabel chat belum tersedia. Jalankan file SQL chat_messages.sql terlebih dahulu.';
}

$titleForTop = 'Chat Klaim';
renderHead('Chat Klaim');
renderUserNav('klaim');
?>
<div class="content animate-in">
    <div class="section-head">
        <div>
            <h1 class="hero-title">Chat Klaim</h1>
            <p class="muted">Percakapan terkait klaim barang. Gunakan chat ini untuk mengonfirmasi ciri barang dan proses pengembalian.</p>
        </div>
        <a class="btn btn-outline" href="<?= $uid === (int)$claim['claimant_id'] ? 'klaim-saya.php' : 'klaim-masuk.php' ?>">← Kembali ke klaim</a>
    </div>

    <div class="card" style="margin-bottom:16px">
        <h2><?= e($claim['found_name']) ?></h2>
        <div class="meta">Laporan kehilangan: <?= e($claim['lost_name']) ?></div>
        <div class="meta">Chat dengan <?= e($otherName) ?> · Status klaim: <?= e(statusLabel($claim['status'])) ?></div>
    </div>

    <?php if ($err): ?><div class="notice notice-error"><?= e($err) ?></div><?php endif; ?>

    <div class="card" style="padding:16px">
        <div style="display:flex;flex-direction:column;gap:12px;max-height:55vh;overflow-y:auto;margin-bottom:18px">
            <?php if ($messages): ?>
                <?php foreach ($messages as $m): ?>
                    <div style="align-self:<?= (int)$m['sender_id'] === $uid ? 'flex-end' : 'flex-start' ?>;max-width:min(85%,520px);padding:12px 14px;border-radius:14px;background:<?= (int)$m['sender_id'] === $uid ? 'var(--primary,#dbeafe)' : 'var(--surface-secondary,#f3f4f6)' ?>;overflow-wrap:anywhere">
                        <div style="font-size:12px;font-weight:600;margin-bottom:5px"><?= e($m['sender_name']) ?></div>
                        <div><?= nl2br(e($m['message'])) ?></div>
                        <div style="font-size:11px;opacity:.7;margin-top:7px"><?= e($m['created_at']) ?></div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty">Belum ada pesan. Mulai percakapan dengan sopan dan jangan kirim kata sandi atau data rahasia.</div>
            <?php endif; ?>
        </div>

        <form method="post">
            <input type="hidden" name="claim_id" value="<?= $claimId ?>">
            <input type="hidden" name="csrf_token" value="<?= e($_SESSION['chat_csrf']) ?>">
            <div class="form-group">
                <label for="message">Pesan untuk <?= e($otherName) ?></label>
                <textarea class="form-control" id="message" name="message" rows="3" maxlength="2000" required placeholder="Tulis pesan..."></textarea>
            </div>
            <button class="btn btn-blue" type="submit">Kirim pesan</button>
        </form>
    </div>
</div>
<?php renderFooter(); ?>
