<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_host.php';
require_once '../../../koneksi.php';

$hostId    = (int)($_SESSION['id'] ?? 0);
$listingId = (int)($_GET['id']    ?? 0);

if (!$listingId) {
    header('Location: listing.php');
    exit;
}

/* ── Ambil data listing ── */
$q = mysqli_query(
    $koneksi,
    "SELECT l.*,
            u.nama AS nama_host
     FROM listings l
     JOIN users u ON u.id = l.host_id
     WHERE l.id = $listingId AND l.host_id = $hostId
     LIMIT 1"
);
$listing = mysqli_fetch_assoc($q);
if (!$listing) {
    header('Location: listing.php');
    exit;
}

/* ── Foto ── */
$photos = [];
$qp = mysqli_query(
    $koneksi,
    "SELECT nama_file, adalah_cover FROM listing_photos
     WHERE listing_id = $listingId
     ORDER BY adalah_cover DESC, id ASC"
);
while ($row = mysqli_fetch_assoc($qp)) $photos[] = $row;

/* ── Amenities (listing_amenities, kolom nama_fasilitas) ── */
$amenities = [];
$qa = mysqli_query(
    $koneksi,
    "SELECT nama_fasilitas FROM listing_amenities WHERE listing_id = $listingId"
);
while ($row = mysqli_fetch_assoc($qa)) $amenities[] = $row['nama_fasilitas'];

/* ── Statistik reservasi ── */
$stats = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT
       COUNT(*)                                                        AS total,
       SUM(CASE WHEN status = 'dikonfirmasi' THEN 1 ELSE 0 END)       AS confirmed,
       SUM(CASE WHEN status = 'menunggu'     THEN 1 ELSE 0 END)       AS pending,
       SUM(CASE WHEN status = 'selesai'      THEN 1 ELSE 0 END)       AS done,
       COALESCE(SUM(CASE WHEN status = 'selesai' THEN total_harga ELSE 0 END), 0) AS revenue
     FROM bookings
     WHERE listing_id = $listingId"
));

/* ── Rating rata-rata ── */
$ratingRow = mysqli_fetch_assoc(mysqli_query(
    $koneksi,
    "SELECT ROUND(AVG(rating), 1) AS avg_rating, COUNT(*) AS total_review
     FROM reviews WHERE listing_id = $listingId"
));
$avgRating   = $ratingRow['avg_rating']    ?? 0;
$totalReview = $ratingRow['total_review']  ?? 0;

/* ── Helper status ── */
function statusBadge(string $s): array
{
    return match ($s) {
        'aktif'    => ['Aktif',    'success'],
        'draft'    => ['Draft',    'warning'],
        'nonaktif' => ['Nonaktif', 'error'],
        default    => ['Butuh Aksi', 'error'],
    };
}
[$statusLabel, $statusDot] = statusBadge($listing['status'] ?? '');

$editHref = 'listing_edit.php?id=' . $listingId;
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($listing['judul']) ?> — Detail Listing</title>
    <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />
    <link rel="stylesheet" href="../../../components/root.css" />
    <link rel="stylesheet" href="../../../components/navbar.css" />
    <link rel="stylesheet" href="../../../components/footer.css" />
    <link rel="stylesheet" href="../../../popups/auth.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />

    <style>
        .detail-wrap {
            max-width: var(--container-xl);
            margin: 0 auto;
            margin-top: 100px;
            padding: 0 var(--space-24) var(--space-96);
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: var(--color-text-secondary);
            margin-bottom: var(--space-20);
        }
        .breadcrumb a { color: var(--color-primary); text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }

        /* Foto Grid */
        .photo-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: 220px 220px;
            gap: 10px;
            border-radius: var(--radius-3xl);
            overflow: hidden;
            margin-bottom: var(--space-32);
        }
        .photo-grid .photo-main  { grid-row: 1 / 3; width: 100%; height: 100%; object-fit: cover; }
        .photo-grid .photo-thumb { width: 100%; height: 100%; object-fit: cover; }
        .photo-placeholder {
            background: var(--color-bg-skeleton);
            display: flex; align-items: center; justify-content: center;
        }
        .photo-placeholder i { font-size: 2.5rem; color: var(--color-border-strong); }

        /* Header */
        .detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: var(--space-24);
            margin-bottom: var(--space-32);
        }
        .detail-title-group { flex: 1; }
        .detail-title {
            font-size: var(--text-3xl);
            font-weight: var(--font-bold);
            color: var(--color-text-primary);
            line-height: 1.2;
            margin-bottom: var(--space-8);
            font-family: 'Playfair Display', serif;
        }
        .detail-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: var(--space-12);
            color: var(--color-text-secondary);
            font-size: var(--text-sm);
        }
        .detail-meta .sep { opacity: 0.4; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: var(--radius-full);
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-badge.success  { background: #dcfce7; color: #15803d; }
        .status-badge.warning  { background: #fef9c3; color: #a16207; }
        .status-badge.error    { background: #fee2e2; color: #dc2626; }

        .detail-actions { display: flex; gap: var(--space-10); flex-shrink: 0; }

        .btn-outline {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 18px;
            border: 1.5px solid var(--color-border-strong);
            border-radius: var(--radius-xl);
            background: transparent;
            color: var(--color-text-primary);
            font-size: 0.875rem; font-weight: 600;
            cursor: pointer; text-decoration: none;
            transition: background 0.15s, border-color 0.15s;
        }
        .btn-outline:hover { background: var(--color-border-subtle); }

        .btn-primary {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 18px;
            border-radius: var(--radius-xl);
            background: var(--color-primary);
            color: #fff;
            font-size: 0.875rem; font-weight: 600;
            cursor: pointer; border: none; text-decoration: none;
            transition: background 0.15s;
        }
        .btn-primary:hover { background: var(--color-primary-hover); }

        /* Grid 2 kolom */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: var(--space-32);
            align-items: start;
        }

        /* Sections */
        .detail-section {
            margin-bottom: var(--space-32);
            padding-bottom: var(--space-32);
            border-bottom: 1px solid var(--color-border-subtle);
        }
        .detail-section:last-child { border-bottom: none; }

        .section-title {
            font-size: var(--text-lg);
            font-weight: var(--font-bold);
            color: var(--color-text-primary);
            margin-bottom: var(--space-16);
        }

        /* Highlight badges baris atas */
        .highlight-row {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-10);
            margin-bottom: var(--space-20);
        }
        .highlight-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            background: var(--color-bg-card, #fff);
            border: 1px solid var(--color-border-subtle);
            border-radius: var(--radius-xl);
            font-size: 0.875rem;
            color: var(--color-text-primary);
            font-weight: 500;
        }
        .highlight-badge i { color: var(--color-primary); font-size: 1rem; }

        /* Rating */
        .rating-row {
            display: flex;
            align-items: center;
            gap: var(--space-8);
            margin-bottom: var(--space-12);
        }
        .star-icon { color: #f59e0b; font-size: 1rem; }

        /* Stats */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--space-12);
        }
        .stat-card {
            background: var(--color-bg-card, #fff);
            border: 1px solid var(--color-border-subtle);
            border-radius: var(--radius-2xl);
            padding: var(--space-16) var(--space-20);
        }
        .stat-card .stat-label {
            font-size: 0.75rem;
            color: var(--color-text-secondary);
            font-weight: 500;
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .stat-card .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-text-primary);
            line-height: 1;
        }
        .stat-card .stat-value.revenue { font-size: 1.1rem; }

        /* Fasilitas */
        .facility-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: var(--space-12);
        }
        .facility-item {
            display: flex;
            align-items: center;
            gap: var(--space-10);
            padding: var(--space-12) var(--space-16);
            border: 1px solid var(--color-border-subtle);
            border-radius: var(--radius-xl);
            font-size: 0.875rem;
            color: var(--color-text-primary);
        }
        .facility-item i { font-size: 1.1rem; color: var(--color-primary); }

        /* Info Rows */
        .info-row {
            display: flex;
            align-items: flex-start;
            gap: var(--space-12);
            padding: var(--space-12) 0;
            border-bottom: 1px solid var(--color-border-subtle);
        }
        .info-row:last-child { border-bottom: none; }
        .info-label {
            width: 160px; flex-shrink: 0;
            font-size: 0.875rem;
            color: var(--color-text-secondary);
            font-weight: 500;
        }
        .info-value { font-size: 0.875rem; color: var(--color-text-primary); flex: 1; }

        /* Sidebar */
        .sidebar-card {
            background: var(--color-bg-card, #fff);
            border: 1px solid var(--color-border-subtle);
            border-radius: var(--radius-3xl);
            padding: var(--space-24);
            position: sticky;
            top: 100px;
        }
        .sidebar-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-text-primary);
            margin-bottom: var(--space-4);
        }
        .sidebar-price span {
            font-size: 0.875rem;
            font-weight: 400;
            color: var(--color-text-secondary);
        }
        .sidebar-divider { border: none; border-top: 1px solid var(--color-border-subtle); margin: var(--space-20) 0; }
        .sidebar-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            color: var(--color-text-secondary);
            padding: var(--space-8) 0;
        }
        .sidebar-row strong { color: var(--color-text-primary); font-weight: 600; }

        .btn-danger-outline {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 10px 18px;
            border: 1.5px solid #fecaca;
            border-radius: var(--radius-xl);
            background: #fef2f2;
            color: #dc2626;
            font-size: 0.875rem; font-weight: 600;
            cursor: pointer; width: 100%; justify-content: center;
            transition: background 0.15s;
        }
        .btn-danger-outline:hover { background: #fee2e2; }

        /* Confirm Modal */
        .confirm-overlay {
            display: none;
            position: fixed; inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 9999;
            align-items: center; justify-content: center;
        }
        .confirm-overlay.open { display: flex; }
        .confirm-box {
            background: #fff;
            border-radius: 16px;
            padding: 28px 28px 24px;
            max-width: 380px;
            width: calc(100% - 32px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.18);
        }
        .confirm-icon {
            display: flex; align-items: center; justify-content: center;
            width: 48px; height: 48px;
            border-radius: 50%;
            font-size: 1.4rem;
            margin: 0 auto 16px;
        }
        .confirm-icon.danger  { background: #fef2f2; color: #dc2626; }
        .confirm-icon.warning { background: #fffbeb; color: #d97706; }
        .confirm-box h3 {
            text-align: center; font-size: 1rem; font-weight: 700;
            margin: 0 0 8px; color: var(--color-text-primary, #111827);
        }
        .confirm-box p {
            text-align: center; font-size: 0.875rem;
            color: var(--color-text-secondary, #6b7280);
            margin: 0 0 22px; line-height: 1.5;
        }
        .confirm-actions { display: flex; gap: 10px; }
        .confirm-actions button {
            flex: 1; padding: 10px; border-radius: 10px;
            border: none; font-size: 0.875rem; font-weight: 600;
            cursor: pointer; transition: background 0.15s;
        }
        .confirm-cancel              { background: #f3f4f6; color: #374151; }
        .confirm-cancel:hover        { background: #e5e7eb; }
        .confirm-confirm-danger      { background: #dc2626; color: #fff; }
        .confirm-confirm-danger:hover{ background: #b91c1c; }
        .confirm-confirm-warning      { background: #d97706; color: #fff; }
        .confirm-confirm-warning:hover{ background: #b45309; }

        /* Toast */
        .ts-toast {
            position: fixed; bottom: 28px; left: 50%;
            transform: translateX(-50%) translateY(12px);
            background: #111827; color: #fff;
            font-size: 0.875rem; font-weight: 500;
            padding: 11px 20px; border-radius: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.18);
            opacity: 0; pointer-events: none;
            transition: opacity 0.2s, transform 0.2s;
            z-index: 99999;
        }
        .ts-toast.show    { opacity: 1; transform: translateX(-50%) translateY(0); }
        .ts-toast.success { background: #15803d; }
        .ts-toast.error   { background: #dc2626; }
    </style>
</head>
<body>

<header class="navbar">
    <nav class="navbar-container">
        <div class="logo-section">
            <img src="../../../assets/logo/logo_temansinggah.svg" alt="Logo" class="logo-icon" />
            <img src="../../../assets/logo/label_temansinggah.svg" alt="Teman Singgah" class="logo-name" />
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="reservations.php"   class="nav-link">Reservasi</a></li>
            <li class="nav-item"><a href="calendar_router.php" class="nav-link">Kalender</a></li>
            <li class="nav-item"><a href="listing.php"        class="nav-link active">Listing</a></li>
            <li class="nav-item"><a href="messages.php"       class="nav-link">Pesan</a></li>
            <div class="nav-indicator"></div>
        </ul>
        <?php include $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/components/navbar_profile_host.php'; ?>
    </nav>
</header>

<main class="detail-wrap">

    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="listing.php"><i class="ph-bold ph-house-simple"></i> Listing Saya</a>
        <i class="ph-bold ph-caret-right"></i>
        <span><?= htmlspecialchars($listing['judul']) ?></span>
    </nav>

    <!-- Foto Grid -->
    <div class="photo-grid">
        <?php
        $cover  = null;
        $thumbs = [];
        foreach ($photos as $p) {
            if ($p['adalah_cover'] && !$cover) $cover = $p;
            else $thumbs[] = $p;
        }
        $coverSrc = $cover
            ? '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($cover['nama_file'])
            : null;
        ?>
        <?php if ($coverSrc): ?>
            <img src="<?= $coverSrc ?>" class="photo-main" alt="Cover" />
        <?php else: ?>
            <div class="photo-main photo-placeholder"><i class="ph-bold ph-house-simple"></i></div>
        <?php endif; ?>

        <?php for ($i = 0; $i < 4; $i++):
            $src = isset($thumbs[$i])
                ? '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($thumbs[$i]['nama_file'])
                : null;
        ?>
            <?php if ($src): ?>
                <img src="<?= $src ?>" class="photo-thumb" alt="Foto <?= $i + 1 ?>" />
            <?php else: ?>
                <div class="photo-thumb photo-placeholder"><i class="ph-bold ph-image"></i></div>
            <?php endif; ?>
        <?php endfor; ?>
    </div>

    <!-- Header Info + Tombol -->
    <div class="detail-header">
        <div class="detail-title-group">
            <h1 class="detail-title"><?= htmlspecialchars($listing['judul']) ?></h1>
            <div class="detail-meta">
                <span class="status-badge <?= $statusDot ?>"><?= $statusLabel ?></span>
                <span class="sep">•</span>
                <i class="ph-bold ph-map-pin"></i>
                <?= htmlspecialchars($listing['lokasi'] ?? '-') ?>
                <span class="sep">•</span>
                <?= htmlspecialchars(ucfirst($listing['tipe_properti'] ?? '-')) ?>
                <?php if ($avgRating > 0): ?>
                    <span class="sep">•</span>
                    <i class="ph-fill ph-star" style="color:#f59e0b"></i>
                    <?= $avgRating ?> (<?= $totalReview ?> ulasan)
                <?php endif; ?>
            </div>
        </div>
        <div class="detail-actions">
            <button class="btn-outline"
                onclick="openDeleteModal(<?= $listingId ?>, '<?= htmlspecialchars(addslashes($listing['judul']), ENT_QUOTES) ?>', '<?= $listing['status'] ?>')">
                <i class="ph-bold ph-trash"></i> Hapus
            </button>
            <a href="<?= $editHref ?>" class="btn-primary">
                <i class="ph-bold ph-pencil-simple"></i> Edit Listing
            </a>
        </div>
    </div>

    <!-- Highlight Badges -->
    <div class="highlight-row">
        <span class="highlight-badge">
            <i class="ph-bold ph-users"></i>
            <?= (int)($listing['max_tamu'] ?? 0) ?> tamu maks.
        </span>
        <span class="highlight-badge">
            <i class="ph-bold ph-bed"></i>
            <?= (int)($listing['kamar_tidur'] ?? 0) ?> kamar tidur
        </span>
        <span class="highlight-badge">
            <i class="ph-bold ph-bathtub"></i>
            <?= (int)($listing['kamar_mandi'] ?? 0) ?> kamar mandi
        </span>
        <span class="highlight-badge">
            <i class="ph-bold ph-moon"></i>
            Min. <?= (int)($listing['min_malam'] ?? 1) ?> malam
        </span>
        <?php if (!empty($listing['tipe_booking'])): ?>
        <span class="highlight-badge">
            <i class="ph-bold ph-lightning"></i>
            <?= $listing['tipe_booking'] === 'instan' ? 'Booking Instan' : 'Perlu Konfirmasi' ?>
        </span>
        <?php endif; ?>
    </div>

    <!-- Grid Utama -->
    <div class="detail-grid">

        <!-- Kolom Kiri -->
        <div>

            <!-- Deskripsi -->
            <div class="detail-section">
                <h2 class="section-title">Deskripsi</h2>
                <p style="font-size:0.9375rem;color:var(--color-text-secondary);line-height:1.75;">
                    <?= nl2br(htmlspecialchars($listing['deskripsi'] ?? 'Belum ada deskripsi.')) ?>
                </p>
            </div>

            <!-- Informasi Properti -->
            <div class="detail-section">
                <h2 class="section-title">Informasi Properti</h2>
                <div>
                    <div class="info-row">
                        <span class="info-label">Tipe Properti</span>
                        <span class="info-value"><?= htmlspecialchars(ucfirst($listing['tipe_properti'] ?? '-')) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tamu Maks.</span>
                        <span class="info-value"><?= (int)($listing['max_tamu'] ?? 0) ?> orang</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kamar Tidur</span>
                        <span class="info-value"><?= (int)($listing['kamar_tidur'] ?? 0) ?> kamar</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kamar Mandi</span>
                        <span class="info-value"><?= (int)($listing['kamar_mandi'] ?? 0) ?> kamar mandi</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Lokasi</span>
                        <span class="info-value"><?= htmlspecialchars($listing['lokasi'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Kebijakan Batal</span>
                        <span class="info-value"><?= htmlspecialchars(ucfirst($listing['kebijakan_pembatalan'] ?? '-')) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-in</span>
                        <span class="info-value"><?= htmlspecialchars($listing['jam_checkin'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Check-out</span>
                        <span class="info-value"><?= htmlspecialchars($listing['jam_checkout'] ?? '-') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Tipe Booking</span>
                        <span class="info-value"><?= htmlspecialchars(ucfirst($listing['tipe_booking'] ?? '-')) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Dibuat</span>
                        <span class="info-value">
                            <?= date('d M Y', strtotime($listing['dibuat_pada'] ?? 'now')) ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Fasilitas -->
            <?php if (!empty($amenities)): ?>
                <div class="detail-section">
                    <h2 class="section-title">Fasilitas</h2>
                    <div class="facility-grid">
                        <?php foreach ($amenities as $nama): ?>
                            <div class="facility-item">
                                <i class="ph-bold ph-check"></i>
                                <?= htmlspecialchars($nama) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Statistik Reservasi -->
            <div class="detail-section">
                <h2 class="section-title">Statistik Reservasi</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <p class="stat-label">Total Reservasi</p>
                        <p class="stat-value"><?= (int)$stats['total'] ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Dikonfirmasi</p>
                        <p class="stat-value" style="color:#2563eb"><?= (int)$stats['confirmed'] ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Menunggu</p>
                        <p class="stat-value" style="color:#d97706"><?= (int)$stats['pending'] ?></p>
                    </div>
                    <div class="stat-card">
                        <p class="stat-label">Pendapatan</p>
                        <p class="stat-value revenue">
                            Rp <?= number_format((float)$stats['revenue'], 0, ',', '.') ?>
                        </p>
                    </div>
                </div>
            </div>

        </div><!-- /kolom kiri -->

        <!-- Sidebar Kanan -->
        <div>
            <div class="sidebar-card">
                <p class="sidebar-price">
                    Rp <?= number_format((float)($listing['harga_malam'] ?? 0), 0, ',', '.') ?>
                    <span>/ malam</span>
                </p>
                <?php if (!empty($listing['harga_akhir_pekan'])): ?>
                    <p style="font-size:0.8rem;color:var(--color-text-secondary);margin-top:2px;">
                        Akhir pekan: Rp <?= number_format((float)$listing['harga_akhir_pekan'], 0, ',', '.') ?> / malam
                    </p>
                <?php endif; ?>

                <hr class="sidebar-divider" />

                <div class="sidebar-row">
                    <span>Status</span>
                    <span class="status-badge <?= $statusDot ?>"><?= $statusLabel ?></span>
                </div>
                <div class="sidebar-row">
                    <span>Tipe</span>
                    <strong><?= htmlspecialchars(ucfirst($listing['tipe_properti'] ?? '-')) ?></strong>
                </div>
                <div class="sidebar-row">
                    <span>Maks. tamu</span>
                    <strong><?= (int)($listing['max_tamu'] ?? 0) ?> orang</strong>
                </div>
                <div class="sidebar-row">
                    <span>Kamar tidur</span>
                    <strong><?= (int)($listing['kamar_tidur'] ?? 0) ?></strong>
                </div>
                <div class="sidebar-row">
                    <span>Kamar mandi</span>
                    <strong><?= (int)($listing['kamar_mandi'] ?? 0) ?></strong>
                </div>
                <div class="sidebar-row">
                    <span>Min. menginap</span>
                    <strong><?= (int)($listing['min_malam'] ?? 1) ?> malam</strong>
                </div>
                <?php if ($avgRating > 0): ?>
                <div class="sidebar-row">
                    <span>Rating</span>
                    <strong>⭐ <?= $avgRating ?> / 5 (<?= $totalReview ?>)</strong>
                </div>
                <?php endif; ?>

                <hr class="sidebar-divider" />

                <a href="<?= $editHref ?>" class="btn-primary"
                    style="width:100%;justify-content:center;margin-bottom:10px;display:flex;">
                    <i class="ph-bold ph-pencil-simple"></i> Edit Listing
                </a>
                <button class="btn-danger-outline"
                    onclick="openDeleteModal(<?= $listingId ?>, '<?= htmlspecialchars(addslashes($listing['judul']), ENT_QUOTES) ?>', '<?= $listing['status'] ?>')">
                    <i class="ph-bold ph-trash"></i> Hapus Listing
                </button>
            </div>
        </div><!-- /sidebar -->

    </div><!-- /detail-grid -->
</main>

<footer class="footer">
    <div class="footer-grid">
        <div class="footer-column">
            <span class="footer-brand">Teman Singgah</span>
            <p class="footer-description">Platform booking penginapan terpercaya di seluruh Indonesia.</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p class="footer-copyright">© 2026 Teman Singgah — All rights reserved.</p>
    </div>
</footer>

<!-- Confirm Modal -->
<div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
        <div class="confirm-icon" id="confirmIcon">
            <i class="ph-bold ph-trash" id="confirmIconInner"></i>
        </div>
        <h3 id="confirmTitle">Hapus Listing?</h3>
        <p id="confirmDesc">Apakah kamu yakin?</p>
        <div class="confirm-actions" id="confirmActions"></div>
    </div>
</div>

<!-- Toast -->
<div class="ts-toast" id="tsToast"></div>

<script src="../../../components/navbar.js"></script>
<script src="../../../popups/auth.js"></script>
<script>
    function openDeleteModal(id, judul, status) {
        const overlay = document.getElementById('confirmOverlay');
        const icon    = document.getElementById('confirmIcon');
        const title   = document.getElementById('confirmTitle');
        const desc    = document.getElementById('confirmDesc');
        const actions = document.getElementById('confirmActions');

        if (status === 'aktif') {
            icon.className = 'confirm-icon warning';
            document.getElementById('confirmIconInner').className = 'ph-bold ph-warning';
            title.textContent = 'Apa yang ingin kamu lakukan?';
            desc.textContent  = `"${judul}" masih aktif. Nonaktifkan sementara atau hapus permanen?`;
            actions.innerHTML = `
                <button class="confirm-cancel" onclick="closeConfirm()">Batal</button>
                <button class="confirm-confirm-warning" onclick="doAction(${id},'nonaktif')">
                    <i class="ph-bold ph-eye-slash" style="margin-right:5px"></i>Nonaktifkan
                </button>
                <button class="confirm-confirm-danger" onclick="doAction(${id},'hapus')">
                    <i class="ph-bold ph-trash" style="margin-right:5px"></i>Hapus
                </button>`;
        } else {
            icon.className = 'confirm-icon danger';
            document.getElementById('confirmIconInner').className = 'ph-bold ph-trash';
            title.textContent = 'Hapus Listing?';
            desc.textContent  = `"${judul}" akan dihapus permanen dan tidak bisa dikembalikan.`;
            actions.innerHTML = `
                <button class="confirm-cancel" onclick="closeConfirm()">Batal</button>
                <button class="confirm-confirm-danger" onclick="doAction(${id},'hapus')">
                    <i class="ph-bold ph-trash" style="margin-right:5px"></i>Hapus Permanen
                </button>`;
        }
        overlay.classList.add('open');
    }

    function closeConfirm() {
        document.getElementById('confirmOverlay').classList.remove('open');
    }

    document.getElementById('confirmOverlay').addEventListener('click', function(e) {
        if (e.target === this) closeConfirm();
    });

    function doAction(id, action) {
        closeConfirm();
        fetch('../../../host/api/listing_hapus.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id, aksi: action })
        })
        .then(r => r.json())
        .then(d => {
            if (d.status === 'ok') {
                showToast(action === 'hapus' ? 'Listing dihapus.' : 'Listing dinonaktifkan.', 'success');
                setTimeout(() => window.location.href = 'listing.php', 1200);
            } else {
                showToast('Gagal: ' + (d.message || 'Terjadi kesalahan.'), 'error');
            }
        })
        .catch(() => showToast('Gagal terhubung ke server.', 'error'));
    }

    function showToast(msg, type = '') {
        const el = document.getElementById('tsToast');
        el.textContent = msg;
        el.className   = 'ts-toast' + (type ? ' ' + type : '');
        el.classList.add('show');
        setTimeout(() => el.classList.remove('show'), 3000);
    }
</script>
</body>
</html>