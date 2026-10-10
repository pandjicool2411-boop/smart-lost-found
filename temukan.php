<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/ui.php';

requireProfile($conn);

$q = trim($_GET['q'] ?? '');
$type = $_GET['type'] ?? 'ALL';
$cat = (int) ($_GET['category_id'] ?? 0);
$loc = (int) ($_GET['location_id'] ?? 0);
$date = trim($_GET['date'] ?? '');

if (!in_array($type, ['ALL', 'LOST', 'FOUND'], true)) {
    $type = 'ALL';
}

/**
 * Normalize text for consistent matching.
 */
$normalize = static function ($text): string {
    $text = mb_strtolower(trim((string) $text), 'UTF-8');
    $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
    return trim($text ?? '');
};

/**
 * Calculate a relevance score from 0–100.
 * A query token must match a word (exactly or closely) in a field;
 * unrelated items therefore receive 0 instead of appearing as false matches.
 */
$fieldScore = static function ($query, $text) use ($normalize): float {
    $query = $normalize($query);
    $text = $normalize($text);

    if ($query === '' || $text === '') {
        return 0.0;
    }

    $queryWords = array_values(array_unique(explode(' ', $query)));
    $textWords = array_values(array_unique(explode(' ', $text)));
    $matched = 0;
    $qualityTotal = 0.0;

    foreach ($queryWords as $queryWord) {
        if ($queryWord === '') {
            continue;
        }

        $best = 0.0;

        foreach ($textWords as $textWord) {
            if ($queryWord === $textWord) {
                $best = 100.0;
                break;
            }

            // A query word contained in a longer word is a useful partial match.
            if (mb_strlen($queryWord, 'UTF-8') >= 3 &&
                (mb_strpos($textWord, $queryWord, 0, 'UTF-8') !== false ||
                 mb_strpos($queryWord, $textWord, 0, 'UTF-8') !== false)) {
                $best = max($best, 85.0);
                continue;
            }

            similar_text($queryWord, $textWord, $wordPercent);
            if ($wordPercent >= 75.0) {
                $best = max($best, $wordPercent);
            }
        }

        if ($best >= 75.0) {
            $matched++;
            $qualityTotal += $best;
        }
    }

    if ($matched === 0 || count($queryWords) === 0) {
        return 0.0;
    }

    $coverage = ($matched / count($queryWords)) * 100;
    $quality = $qualityTotal / $matched;

    // Coverage matters most: matching only one of several query words gets a lower score.
    return round(($coverage * 0.75) + ($quality * 0.25), 2);
};

/**
 * Build filter options.
 */
$categories = $conn->query('SELECT id, name FROM categories ORDER BY name');
$locations = $conn->query('SELECT id, name FROM locations ORDER BY name');

/**
 * Only verified reports are considered active/public in this search.
 * Keyword matching is handled in PHP so fuzzy/partial matches are not
 * prematurely discarded by a strict SQL LIKE condition.
 */
$where = ["r.status = 'VERIFIED'"];
$params = [];
$bindTypes = '';

if ($type === 'LOST' || $type === 'FOUND') {
    $where[] = 'r.type = ?';
    $bindTypes .= 's';
    $params[] = $type;
}

if ($cat > 0) {
    $where[] = 'r.category_id = ?';
    $bindTypes .= 'i';
    $params[] = $cat;
}

if ($loc > 0) {
    $where[] = 'r.location_id = ?';
    $bindTypes .= 'i';
    $params[] = $loc;
}

if ($date !== '') {
    $parsedDate = DateTime::createFromFormat('!Y-m-d', $date);

    if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date) {
        $date = '';
    } else {
        $where[] = 'r.incident_date = ?';
        $bindTypes .= 's';
        $params[] = $date;
    }
}

$sql = "
    SELECT
        r.id,
        r.type,
        r.item_name,
        r.description,
        r.image,
        r.incident_date,
        r.status,
        r.created_at,
        c.name AS category_name,
        l.name AS location_name,
        u.name AS reporter_name
    FROM reports r
    LEFT JOIN categories c ON c.id = r.category_id
    LEFT JOIN locations l ON l.id = r.location_id
    JOIN users u ON u.id = r.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    error_log('Search query prepare failed: ' . $conn->error);
    http_response_code(500);
    exit('Pencarian sedang bermasalah. Silakan coba lagi.');
}

if ($params) {
    $stmt->bind_param($bindTypes, ...$params);
}

if (!$stmt->execute()) {
    error_log('Search query failed: ' . $stmt->error);
    http_response_code(500);
    exit('Pencarian sedang bermasalah. Silakan coba lagi.');
}

$result = $stmt->get_result();
$items = [];

while ($row = $result->fetch_assoc()) {
    if ($q !== '') {
        $nameScore = $fieldScore($q, $row['item_name'] ?? '');
        $descriptionScore = $fieldScore($q, $row['description'] ?? '');
        $categoryScore = $fieldScore($q, $row['category_name'] ?? '');
        $locationScore = $fieldScore($q, $row['location_name'] ?? '');

        // Name is the strongest signal; other attributes can still make a result relevant.
        $score = ($nameScore * 0.65)
            + ($descriptionScore * 0.20)
            + ($categoryScore * 0.10)
            + ($locationScore * 0.05);

        // Do not show unrelated results with a tiny character-level similarity.
        $hasRelevantMatch = max(
            $nameScore,
            $descriptionScore,
            $categoryScore,
            $locationScore
        ) > 0;

        if (!$hasRelevantMatch) {
            continue;
        }

        $row['similarity'] = (int) round(min(100, $score));
    } else {
        $row['similarity'] = null;
    }

    $items[] = $row;
}

$stmt->close();

if ($q !== '') {
    usort($items, static function ($a, $b) {
        $scoreCompare = $b['similarity'] <=> $a['similarity'];
        if ($scoreCompare !== 0) {
            return $scoreCompare;
        }
        return strcmp((string) $b['created_at'], (string) $a['created_at']);
    });
}

$titleForTop = 'Cari Barang';
renderHead('Cari Barang');
renderUserNav('search');
?>

<div class="content animate-in">
    <div class="section-head">
        <div>
            <h1 class="hero-title">Cari Barang</h1>
            <p class="muted">
                Cari barang hilang maupun ditemukan berdasarkan nama, deskripsi, kategori, lokasi, dan tanggal.
            </p>
        </div>
        <a class="btn btn-primary" href="matching.php">✦ Smart Matching</a>
    </div>

    <form class="card searchbar" method="get">
        <input
            class="form-control wide"
            type="search"
            name="q"
            value="<?= e($q) ?>"
            placeholder="Cari nama barang atau kata kunci..."
        >

        <select class="form-select" name="type">
            <option value="ALL" <?= $type === 'ALL' ? 'selected' : '' ?>>Semua</option>
            <option value="LOST" <?= $type === 'LOST' ? 'selected' : '' ?>>Barang Hilang</option>
            <option value="FOUND" <?= $type === 'FOUND' ? 'selected' : '' ?>>Barang Ditemukan</option>
        </select>

        <select class="form-select" name="category_id">
            <option value="0">Semua Kategori</option>
            <?php while ($c = $categories->fetch_assoc()): ?>
                <option value="<?= (int) $c['id'] ?>" <?= $cat === (int) $c['id'] ? 'selected' : '' ?>>
                    <?= e($c['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select class="form-select" name="location_id">
            <option value="0">Semua Lokasi</option>
            <?php while ($l = $locations->fetch_assoc()): ?>
                <option value="<?= (int) $l['id'] ?>" <?= $loc === (int) $l['id'] ? 'selected' : '' ?>>
                    <?= e($l['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <button class="btn btn-blue" type="submit">Cari</button>

        <input
            class="form-control"
            type="date"
            name="date"
            value="<?= e($date) ?>"
            style="grid-column:1/-1"
        >
    </form>

    <section class="section">
        <div class="section-head">
            <div>
                <h2><?= count($items) ?> hasil relevan</h2>
                <?php if ($q !== ''): ?>
                    <p class="muted">
                        Hasil diurutkan berdasarkan kemiripan dengan "<?= e($q) ?>". Hanya laporan yang masih aktif dan terverifikasi yang ditampilkan.
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-3 stagger">
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $r): ?>
                    <a class="card item-card" href="detail-barang.php?id=<?= (int) $r['id'] ?>">
                        <div class="thumb">
                            <?php if (!empty($r['image'])): ?>
                                <img src="uploads/<?= e($r['image']) ?>" alt="<?= e($r['item_name']) ?>">
                            <?php else: ?>
                                📦
                            <?php endif; ?>
                        </div>

                        <div class="body">
                            <div class="section-head" style="margin-bottom:8px">
                                <span class="pill <?= $r['type'] === 'FOUND' ? 'pill-green' : 'pill-red' ?>">
                                    <?= $r['type'] === 'FOUND' ? 'Ditemukan' : 'Hilang' ?>
                                </span>

                                <?php if ($q !== ''): ?>
                                    <span class="pill pill-blue"><?= (int) $r['similarity'] ?>% mirip</span>
                                <?php endif; ?>
                            </div>

                            <h3><?= e($r['item_name']) ?></h3>

                            <div class="meta">
                                📂 <?= e($r['category_name'] ?? '-') ?><br>
                                📍 <?= e($r['location_name'] ?? '-') ?><br>
                                📅 <?= e($r['incident_date'] ?? '-') ?><br>
                                👤 <?= e($r['reporter_name']) ?>
                            </div>

                            <?php if (!empty($r['description'])): ?>
                                <p class="meta">
                                    <?= e(mb_strimwidth($r['description'], 0, 100, '...')) ?>
                                </p>
                            <?php endif; ?>

                            <div class="mini-actions">
                                <span class="pill pill-green">Terverifikasi</span>
                                <span class="top-link">Detail →</span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty" style="grid-column:1/-1">
                    <?php if ($q !== ''): ?>
                        Tidak ada barang aktif yang cocok dengan kata kunci "<?= e($q) ?>" dan filter yang dipilih.
                    <?php else: ?>
                        Belum ada laporan terverifikasi untuk filter ini.
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php renderFooter(); ?>
