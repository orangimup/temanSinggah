<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/koneksi.php';

$host_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($host_id <= 0) {
    header('Location: /teman_singgah/index.php');
    exit;
}

$stmt = mysqli_prepare($koneksi, "
    SELECT u.id AS user_id, u.nama, u.photo, u.lokasi, u.tanggal_daftar,
           COUNT(DISTINCT l.id) AS total_listing
    FROM users u
    LEFT JOIN listings l ON l.host_id = u.id AND l.status = 'aktif'
    WHERE u.id = ?
    GROUP BY u.id
");
mysqli_stmt_bind_param($stmt, 'i', $host_id);
mysqli_stmt_execute($stmt);
$host = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$host) {
    header('Location: /teman_singgah/index.php');
    exit;
}

$stmt = mysqli_prepare($koneksi, "
    SELECT l.id, l.judul, l.lokasi, l.harga_malam, l.tipe_properti,
           l.max_tamu, l.kamar_tidur, l.kamar_mandi,
           (SELECT nama_file FROM listing_photos WHERE listing_id = l.id AND adalah_cover = 1 LIMIT 1) AS cover_foto,
           ROUND(AVG(r.rating), 1) AS avg_rating,
           COUNT(DISTINCT r.id) AS total_ulasan
    FROM listings l
    LEFT JOIN reviews r ON r.listing_id = l.id
    WHERE l.host_id = ? AND l.status = 'aktif'
    GROUP BY l.id
    ORDER BY l.dibuat_pada DESC
");

mysqli_stmt_bind_param($stmt, 'i', $host_id);
mysqli_stmt_execute($stmt);
$listings = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$all_ratings = array_filter(array_column($listings, 'avg_rating'));
$host_avg_rating = count($all_ratings) > 0 ? round(array_sum($all_ratings) / count($all_ratings), 1) : 0;
$total_ulasan = array_sum(array_column($listings, 'total_ulasan'));

$host_initial = strtoupper(mb_substr($host['nama'], 0, 1));
$host_year = date('Y', strtotime($host['tanggal_daftar']));
$host_photo = null;
if (!empty($host['photo'])) {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $host['photo'];
    if (file_exists($path))
        $host_photo = '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($host['photo']);
}

$user_logged_in = isset($_SESSION['user_id']);
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= htmlspecialchars($host['nama']) ?> | Teman Singgah</title>
    <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />
    <link rel="stylesheet" href="/teman_singgah/components/root.css" />
    <link rel="stylesheet" href="/teman_singgah/components/navbar.css" />
    <link rel="stylesheet" href="/teman_singgah/components/footer.css" />
    <link rel="stylesheet" href="/teman_singgah/popups/auth.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
    <style>
        /* ── Layout ───────────────────────────────────────────── */
        .host-profile-page {
            max-width: var(--container-xl);
            margin: var(--space-center);
            margin-top: calc(var(--navbar-height) + var(--space-32));
            padding-bottom: var(--space-64);
            display: grid;
            grid-template-columns: 320px 1fr;
            gap: var(--space-40);
            align-items: start;
        }

        /* ── Sidebar kartu host ──────────────────────────────── */
        .host-card-sidebar {
            position: sticky;
            top: calc(var(--navbar-height) + var(--space-24));
            border: 1.5px solid var(--color-border);
            border-radius: var(--radius-3xl);
            padding: var(--space-32);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: var(--space-16);
            background: var(--color-bg-card);
            box-shadow: var(--shadow-card);
        }

        .host-profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: var(--radius-full);
            background: var(--color-primary);
            color: var(--color-text-inverse);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            font-weight: var(--font-bold);
            overflow: hidden;
            flex-shrink: 0;
        }

        .host-profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .host-profile-name {
            font-size: var(--text-xl);
            font-weight: var(--font-bold);
            color: var(--color-text-primary);
            text-align: center;
        }

        .host-profile-since {
            font-size: var(--text-sm);
            color: var(--color-text-secondary);
        }

        .host-badge-list {
            display: flex;
            flex-direction: column;
            gap: var(--space-10);
            width: 100%;
            border-top: 1.5px solid var(--color-border-subtle);
            padding-top: var(--space-20);
        }

        .host-badge-item {
            display: flex;
            align-items: center;
            gap: var(--space-10);
            font-size: var(--text-sm);
            color: var(--color-text-secondary);
        }

        .host-badge-item i {
            color: var(--color-primary);
            font-size: var(--text-lg);
            width: 20px;
            flex-shrink: 0;
        }

        .host-badge-item strong {
            color: var(--color-text-primary);
            font-weight: var(--font-semibold);
        }

        .host-chat-btn {
            width: 100%;
            padding: var(--space-14) var(--space-20);
            border-radius: var(--radius-full);
            border: none;
            background: var(--color-primary);
            color: var(--color-text-inverse);
            font-size: var(--text-base);
            font-weight: var(--font-semibold);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--space-8);
            transition: all var(--transition-base);
            text-decoration: none;
            margin-top: var(--space-8);
        }

        .host-chat-btn:hover {
            background: var(--color-primary-hover);
        }

        .host-chat-btn:active {
            background: var(--color-primary-active);
            transform: scale(0.99);
        }

        /* ── Main content ────────────────────────────────────── */
        .host-profile-main {
            display: flex;
            flex-direction: column;
            gap: var(--space-32);
        }

        .section-heading {
            font-size: var(--text-xl);
            font-weight: var(--font-bold);
            color: var(--color-text-primary);
            margin-bottom: var(--space-20);
        }

        /* ── Listing grid ────────────────────────────────────── */
        .host-listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: var(--space-24);
        }

        .listing-card {
            border: 1.5px solid var(--color-border);
            border-radius: var(--radius-2xl);
            overflow: hidden;
            background: var(--color-bg-card);
            box-shadow: var(--shadow-card);
            text-decoration: none;
            color: inherit;
            transition: all var(--transition-base);
            display: flex;
            flex-direction: column;
        }

        .listing-card:hover {
            box-shadow: var(--shadow-card-hover, 0 8px 24px rgba(0, 0, 0, 0.12));
            transform: translateY(-2px);
        }

        .listing-card-img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            background: var(--color-bg-skeleton);
        }

        .listing-card-img-placeholder {
            width: 100%;
            height: 180px;
            background: var(--color-primary-light);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .listing-card-img-placeholder i {
            font-size: 40px;
            color: var(--color-primary);
            opacity: 0.4;
        }

        .listing-card-body {
            padding: var(--space-16) var(--space-20);
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: var(--space-8);
        }

        .listing-card-name {
            font-size: var(--text-base);
            font-weight: var(--font-semibold);
            color: var(--color-text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .listing-card-location {
            font-size: var(--text-sm);
            color: var(--color-text-secondary);
            display: flex;
            align-items: center;
            gap: var(--space-4);
        }

        .listing-card-rating {
            display: flex;
            align-items: center;
            gap: var(--space-4);
            font-size: var(--text-sm);
            color: var(--color-text-secondary);
        }

        .listing-card-rating i {
            color: #f59e0b;
            font-size: var(--text-base);
        }

        .listing-card-price {
            margin-top: auto;
            padding-top: var(--space-8);
            font-size: var(--text-sm);
            color: var(--color-text-secondary);
        }

        .listing-card-price strong {
            font-size: var(--text-base);
            font-weight: var(--font-bold);
            color: var(--color-text-primary);
        }

        /* ── Empty state ─────────────────────────────────────── */
        .listings-empty {
            padding: var(--space-40);
            text-align: center;
            color: var(--color-text-secondary);
            font-size: var(--text-base);
            background: var(--color-bg-skeleton);
            border-radius: var(--radius-2xl);
        }

        /* ── Back link ───────────────────────────────────────── */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: var(--space-8);
            font-size: var(--text-sm);
            font-weight: var(--font-semibold);
            color: var(--color-primary);
            text-decoration: none;
            margin-bottom: var(--space-8);
        }

        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <header class="navbar">
        <nav class="navbar-container">
            <a href="/teman_singgah/index.php" class="logo-link"></a>
            <div class="logo-section">
                <img src="/teman_singgah/assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah"
                    class="logo-icon" />
                <img src="/teman_singgah/assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah"
                    class="logo-name" />
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="/teman_singgah/index.php" class="nav-link">Cari Penginapan</a></li>
                <li class="nav-item"><a href="/teman_singgah/user/pages/promo_deals.php" class="nav-link">Promo &amp;
                        Deals</a></li>
                <li class="nav-item"><a href="/teman_singgah/user/pages/become_host.php" class="nav-link">Jadi Host</a>
                </li>
                <li class="nav-item"><a href="/teman_singgah/user/pages/about_us.php" class="nav-link">Tentang Kami</a>
                </li>
                <div class="nav-indicator"></div>
            </ul>
            <div class="nav-right">
                <div class="icon-buttons">
                    <button class="icon-button profile" aria-label="Profile">
                        <?= isset($_SESSION['nama']) ? strtoupper(mb_substr($_SESSION['nama'], 0, 1)) : '' ?>
                    </button>
                    <button class="icon-button hamburger" aria-label="Hamburger">
                        <i class="ph-bold ph-list"></i>
                    </button>
                </div>
                <div id="hamburgerDropdown"></div>
                <div id="authPopup"></div>
            </div>
        </nav>
    </header>

    <main>
        <div class="host-profile-page">

            <!-- ── Sidebar ── -->
            <aside class="host-card-sidebar">
                <div class="host-profile-avatar">
                    <?php if ($host_photo): ?>
                        <img src="<?= $host_photo ?>" alt="Foto <?= htmlspecialchars($host['nama']) ?>" />
                    <?php else: ?>
                        <?= $host_initial ?>
                    <?php endif; ?>
                </div>

                <h1 class="host-profile-name"><?= htmlspecialchars($host['nama']) ?></h1>
                <span class="host-profile-since">Host sejak <?= $host_year ?></span>

                <div class="host-badge-list">
                    <?php if ($host_avg_rating > 0): ?>
                        <div class="host-badge-item">
                            <i class="ph-fill ph-star"></i>
                            <span><strong><?= $host_avg_rating ?></strong> rating rata-rata</span>
                        </div>
                    <?php endif; ?>
                    <div class="host-badge-item">
                        <i class="ph-bold ph-buildings"></i>
                        <span><strong><?= $host['total_listing'] ?></strong> listing aktif</span>
                    </div>
                    <?php if ($total_ulasan > 0): ?>
                        <div class="host-badge-item">
                            <i class="ph-bold ph-chat-circle-text"></i>
                            <span><strong><?= $total_ulasan ?></strong> ulasan diterima</span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($host['lokasi'])): ?>
                        <div class="host-badge-item">
                            <i class="ph-bold ph-map-pin"></i>
                            <span><?= htmlspecialchars($host['lokasi']) ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="host-badge-item">
                        <i class="ph-bold ph-chats"></i>
                        <span>Respons cepat</span>
                    </div>
                </div>

                <?php if ($user_logged_in): ?>
                    <a href="messages.php?host=<?= $host_id ?>" class="host-chat-btn">
                        <i class="ph-bold ph-chat-circle-text"></i> Hubungi Host
                    </a>
                <?php else: ?>
                    <button class="host-chat-btn"
                        onclick="document.getElementById('authPopup')?.querySelector('[data-tab=login]')?.click()">
                        <i class="ph-bold ph-chat-circle-text"></i> Hubungi Host
                    </button>
                <?php endif; ?>
            </aside>

            <!-- ── Main ── -->
            <div class="host-profile-main">
                <a href="javascript:history.back()" class="back-link">
                    <i class="ph-bold ph-arrow-left"></i> Kembali
                </a>

                <section>
                    <h2 class="section-heading">Listing <?= htmlspecialchars($host['nama']) ?></h2>

                    <?php if (empty($listings)): ?>
                        <div class="listings-empty">
                            <i class="ph-bold ph-house-simple"
                                style="font-size:40px;display:block;margin-bottom:12px;opacity:.3"></i>
                            Belum ada listing aktif dari host ini.
                        </div>
                    <?php else: ?>
                        <div class="host-listings-grid">
                            <?php foreach ($listings as $l):
                                $cover_src = null;
                                if (!empty($l['cover_foto'])) {
                                    $cover_src = str_starts_with($l['cover_foto'], 'http')
                                        ? $l['cover_foto']
                                        : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($l['cover_foto']);
                                }
                                ?>
                                <a href="detail_card.php?id=<?= $l['id'] ?>" class="listing-card">
                                    <?php if ($cover_src): ?>
                                        <img src="<?= $cover_src ?>" alt="<?= htmlspecialchars($l['judul']) ?>"
                                            class="listing-card-img" />
                                    <?php else: ?>
                                        <div class="listing-card-img-placeholder">
                                            <i class="ph-bold ph-house-simple"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="listing-card-body">
                                        <div class="listing-card-name"><?= htmlspecialchars($l['judul']) ?></div>
                                        <div class="listing-card-location">
                                            <i class="ph-fill ph-map-pin"></i>
                                            <?= htmlspecialchars($l['lokasi']) ?>
                                        </div>
                                        <?php if ($l['avg_rating']): ?>
                                            <div class="listing-card-rating">
                                                <i class="ph-fill ph-star"></i>
                                                <?= $l['avg_rating'] ?>
                                                <span>(<?= $l['total_ulasan'] ?> ulasan)</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="listing-card-price">
                                            <strong>Rp <?= number_format($l['harga_malam'], 0, ',', '.') ?></strong> / malam
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

        </div>
    </main>

    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-column">
                <span class="footer-brand">Teman Singgah</span>
                <p class="footer-description">Platform booking penginapan terpercaya di seluruh Indonesia.</p>
                <div class="footer-social">
                    <a href="" class="social-link"><i class="ri-instagram-line"></i></a>
                    <a href="" class="social-link"><i class="ri-facebook-circle-line"></i></a>
                    <a href="" class="social-link"><i class="ri-youtube-line"></i></a>
                    <a href="" class="social-link"><i class="ri-twitter-line"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3 class="footer-title">Navigasi</h3>
                <ul class="footer-links">
                    <li><a href="/teman_singgah/index.php" class="footer-link">Beranda</a></li>
                    <li><a href="promo_deals.php" class="footer-link">Promo &amp; Deals</a></li>
                    <li><a href="become_host.php" class="footer-link">Jadi Host</a></li>
                    <li><a href="about_us.php" class="footer-link">Tentang Kami</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3 class="footer-title">Dukungan</h3>
                <ul class="footer-links">
                    <li><a href="#" class="footer-link">Pusat Bantuan</a></li>
                    <li><a href="#" class="footer-link">FAQ</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="footer-copyright">© 2026 Teman Singgah — All rights reserved.</p>
        </div>
    </footer>

    <script src="/teman_singgah/components/navbar.js"></script>
    <script src="/teman_singgah/popups/auth.js"></script>
</body>

</html>