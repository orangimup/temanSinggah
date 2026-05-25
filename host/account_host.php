<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_host.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/koneksi.php';

// Ambil data user terbaru dari DB
$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "s", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
    session_destroy();
    header("Location: /teman_singgah/index.php?auth=login");
    exit;
}

$inisial = strtoupper(mb_substr($user['nama'], 0, 1));
$photo_url = '';
if (!empty($user['photo'])) {
    $abs = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $user['photo'];
    if (file_exists($abs)) {
        $photo_url = '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($user['photo']);
    }
}

$tanggal_bergabung = '';
if (!empty($user['tanggal_daftar'])) {
    $bulan_id = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $ts = strtotime($user['tanggal_daftar']);
    $tanggal_bergabung = $bulan_id[date('n', $ts) - 1] . ' ' . date('Y', $ts);
}

$host_id = $user['id'];

// Statistik host
// Alias disesuaikan: total_booking (bukan total_reservasi) agar cocok dengan tabel bookings
$stats = ['total_listing' => 0, 'total_booking' => 0, 'rata_rating' => null, 'total_ulasan' => 0];

try {
    $q = mysqli_prepare(
        $koneksi,
        "SELECT
         COUNT(DISTINCT l.id)         AS total_listing,
         COUNT(DISTINCT b.id)         AS total_booking,
         ROUND(AVG(rv.rating), 1)     AS rata_rating,
         COUNT(DISTINCT rv.id)        AS total_ulasan
       FROM listings l
       LEFT JOIN bookings b  ON b.listing_id = l.id
       LEFT JOIN reviews  rv ON rv.listing_id = l.id
       WHERE l.host_id = ?"
    );
    if ($q) {
        mysqli_stmt_bind_param($q, "i", $host_id);
        mysqli_stmt_execute($q);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($q));
        if ($row) $stats = $row;
        mysqli_stmt_close($q);
    }
} catch (Exception $e) {
    error_log("Stats query failed: " . $e->getMessage());
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Akun Host | Teman Singgah</title>
    <link rel="icon" href="../assets/logo/logo_temansinggah.svg" />
    <link rel="stylesheet" href="../components/root.css" />
    <link rel="stylesheet" href="../components/navbar.css" />
    <link rel="stylesheet" href="../components/footer.css" />
    <link rel="stylesheet" href="../popups/auth.css" />
    <link rel="stylesheet" href="account_host.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
</head>

<body>

    <!-- ── NAVBAR ─────────────────────────────────────────────────────────── -->
    <header class="navbar">
        <nav class="navbar-container">
            <a href="dashboard/pages/reservations.php" class="logo-link"></a>
            <div class="logo-section">
                <img src="../assets/logo/logo_temansinggah.svg" alt="Logo" class="logo-icon" />
                <img src="../assets/logo/label_temansinggah.svg" alt="Teman Singgah" class="logo-name" />
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="dashboard/pages/reservations.php" class="nav-link">Reservasi</a></li>
                <li class="nav-item"><a href="dashboard/pages/listing.php" class="nav-link">Listing</a></li>
                <li class="nav-item"><a href="dashboard/pages/messages.php" class="nav-link">Pesan</a></li>
                <li class="nav-item"><a href="dashboard/pages/calendar_router.php" class="nav-link">Kalender</a></li>
                <div class="nav-indicator"></div>
            </ul>
            <?php include $_SERVER["DOCUMENT_ROOT"] . "/teman_singgah/components/navbar_profile_host.php"; ?>
        </nav>
    </header>

    <main class="main-content">

        <!-- ── HERO ───────────────────────────────────────────────────────────── -->
        <section class="account-hero">
            <div class="account-hero-container">
                <div class="account-hero-badge">
                    <i class="ph-fill ph-house"></i>
                    <span>Dashboard Host</span>
                </div>
                <h1 class="account-hero-title">Profil Host</h1>
                <p class="account-hero-subtitle">Kelola informasi profil dan pantau performa listing Anda.</p>
            </div>
        </section>

        <!-- ── MAIN SECTION ───────────────────────────────────────────────────── -->
        <section class="account-section">
            <div class="account-container">

                <!-- Sidebar -->
                <aside class="account-sidebar">
                    <h2 class="sidebar-title">Host</h2>
                    <nav class="sidebar-nav">
                        <a href="account_host.php" class="sidebar-link active">
                            <div class="link-icon primary-avatar"
                                style="<?= $photo_url ? 'padding:0;overflow:hidden;' : '' ?>">
                                <?php if ($photo_url): ?>
                                    <img src="<?= $photo_url ?>" alt="Foto"
                                        style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
                                <?php else: ?>
                                    <?= $inisial ?>
                                <?php endif; ?>
                            </div>
                            <span>Profil Saya</span>
                        </a>
                        <a href="dashboard/pages/listing.php" class="sidebar-link">
                            <div class="link-icon"><i class="ph-bold ph-house-line"></i></div>
                            <span>Listing Saya</span>
                        </a>
                        <a href="dashboard/pages/reservations.php" class="sidebar-link">
                            <div class="link-icon"><i class="ph-bold ph-calendar-check"></i></div>
                            <span>Reservasi</span>
                        </a>
                        <a href="/teman_singgah/host/setting_host.php" class="sidebar-link">
                            <div class="link-icon"><i class="ph-bold ph-gear"></i></div>
                            <span>Pengaturan</span>
                        </a>
                    </nav>
                </aside>

                <!-- Content -->
                <div class="account-content">

                    <div class="content-header">
                        <h2 class="content-title">Profil Saya</h2>
                        <a href="/teman_singgah/user/pages/edit_account.php">
                            <button class="edit-button">
                                <i class="ph-bold ph-pencil-simple"></i>
                                Edit
                            </button>
                        </a>
                    </div>

                    <!-- Avatar + Stats -->
                    <div class="profile-section">
                        <div class="profile-avatar-card">
                            <div class="avatar-large">
                                <?php if ($photo_url): ?>
                                    <img src="<?= $photo_url ?>" alt="Foto Profil" />
                                <?php else: ?>
                                    <?= $inisial ?>
                                <?php endif; ?>
                            </div>
                            <p class="profile-name"><?= htmlspecialchars($user['nama']) ?></p>
                            <span class="profile-role"><i class="ph-fill ph-house"></i> Host</span>
                            <?php if ($tanggal_bergabung): ?>
                                <span class="profile-joined">Bergabung <?= $tanggal_bergabung ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- Statistik -->
                        <div class="stats-panel">
                            <h3 class="stats-title">Statistik Host</h3>
                            <div class="stats-grid">
                                <div class="stat-card">
                                    <div class="stat-icon"><i class="ph-bold ph-house-line"></i></div>
                                    <div class="stat-body">
                                        <span class="stat-value"><?= (int) $stats['total_listing'] ?></span>
                                        <span class="stat-label">Listing Aktif</span>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon orange"><i class="ph-bold ph-calendar-check"></i></div>
                                    <div class="stat-body">
                                        <!-- FIXED: was $stats['total_reservasi'], alias di query adalah total_booking -->
                                        <span class="stat-value"><?= (int) $stats['total_booking'] ?></span>
                                        <span class="stat-label">Total Reservasi</span>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon yellow"><i class="ph-bold ph-star"></i></div>
                                    <div class="stat-body">
                                        <span class="stat-value">
                                            <?= $stats['rata_rating'] ? number_format($stats['rata_rating'], 1) : '—' ?>
                                        </span>
                                        <span class="stat-label">Rating Rata-rata</span>
                                    </div>
                                </div>
                                <div class="stat-card">
                                    <div class="stat-icon green"><i class="ph-bold ph-chat-circle-text"></i></div>
                                    <div class="stat-body">
                                        <span class="stat-value"><?= (int) $stats['total_ulasan'] ?></span>
                                        <span class="stat-label">Ulasan Diterima</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="section-divider"></div>

                    <!-- Info profil -->
                    <h3 class="subsection-title">Informasi Profil</h3>
                    <div class="info-grid">
                        <?php if (!empty($user['email'])): ?>
                            <div class="info-card">
                                <div class="info-icon"><i class="ph-bold ph-envelope"></i></div>
                                <div class="info-content">
                                    <span class="info-label">Email</span>
                                    <span class="info-value"><?= htmlspecialchars($user['email']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($user['lokasi'])): ?>
                            <div class="info-card">
                                <div class="info-icon"><i class="ph-bold ph-map-pin"></i></div>
                                <div class="info-content">
                                    <span class="info-label">Lokasi</span>
                                    <span class="info-value"><?= htmlspecialchars($user['lokasi']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($user['bahasa'])): ?>
                            <div class="info-card">
                                <div class="info-icon"><i class="ph-bold ph-globe"></i></div>
                                <div class="info-content">
                                    <span class="info-label">Bahasa</span>
                                    <span class="info-value"><?= htmlspecialchars($user['bahasa']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($user['pekerjaan'])): ?>
                            <div class="info-card">
                                <div class="info-icon"><i class="ph-bold ph-briefcase"></i></div>
                                <div class="info-content">
                                    <span class="info-label">Pekerjaan</span>
                                    <span class="info-value"><?= htmlspecialchars($user['pekerjaan']) ?></span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($user['tentang'])): ?>
                        <div class="info-card full-width">
                            <div class="info-icon"><i class="ph-bold ph-note-pencil"></i></div>
                            <div class="info-content">
                                <span class="info-label">Tentang Saya</span>
                                <span class="info-value"><?= nl2br(htmlspecialchars($user['tentang'])) ?></span>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="empty-about">
                            <i class="ph-bold ph-note-pencil"></i>
                            <p>Belum ada deskripsi. <a href="/teman_singgah/user/pages/edit_account.php">Tambahkan
                                    sekarang</a> agar tamu lebih mengenal Anda.</p>
                        </div>
                    <?php endif; ?>

                    <div class="section-divider"></div>

                    <!-- Aksi cepat -->
                    <h3 class="subsection-title">Aksi Cepat</h3>
                    <div class="quick-actions">
                        <a href="dashboard/pages/listing.php" class="quick-action-card">
                            <div class="qa-icon"><i class="ph-bold ph-plus-circle"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Tambah Listing</span>
                                <span class="qa-desc">Daftarkan properti baru</span>
                            </div>
                            <i class="ph-bold ph-caret-right qa-arrow"></i>
                        </a>
                        <a href="dashboard/pages/reservations.php" class="quick-action-card">
                            <div class="qa-icon orange"><i class="ph-bold ph-calendar-blank"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Kelola Reservasi</span>
                                <span class="qa-desc">Lihat & konfirmasi pemesanan</span>
                            </div>
                            <i class="ph-bold ph-caret-right qa-arrow"></i>
                        </a>
                        <a href="dashboard/pages/messages.php" class="quick-action-card">
                            <div class="qa-icon green"><i class="ph-bold ph-chats"></i></div>
                            <div class="qa-text">
                                <span class="qa-title">Pesan Masuk</span>
                                <span class="qa-desc">Balas pertanyaan tamu</span>
                            </div>
                            <i class="ph-bold ph-caret-right qa-arrow"></i>
                        </a>
                    </div>

                </div><!-- /account-content -->
            </div><!-- /account-container -->
        </section>

    </main>

    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-column">
                <span class="footer-brand">Teman Singgah</span>
                <p class="footer-description">Platform booking penginapan terpercaya di seluruh Indonesia, dari hotel berbintang hingga homestay lokal.</p>
                <div class="footer-social">
                    <a href="" class="social-link"><i class="ri-instagram-line"></i></a>
                    <a href="" class="social-link"><i class="ri-facebook-circle-line"></i></a>
                    <a href="" class="social-link"><i class="ri-youtube-line"></i></a>
                    <a href="" class="social-link"><i class="ri-twitter-line"></i></a>
                    <a href="" class="social-link"><i class="ri-mail-line"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3 class="footer-title">Dashboard Host</h3>
                <ul class="footer-links">
                    <li><a href="dashboard/pages/reservations.php" class="footer-link">Reservasi</a></li>
                    <li><a href="dashboard/pages/listing.php" class="footer-link">Listing</a></li>
                    <li><a href="dashboard/pages/messages.php" class="footer-link">Pesan</a></li>
                    <li><a href="dashboard/pages/calendar_router.php" class="footer-link">Kalender</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3 class="footer-title">Dukungan</h3>
                <ul class="footer-links">
                    <li><a href="" class="footer-link">Pusat Bantuan</a></li>
                    <li><a href="" class="footer-link">Cara Menjadi Host</a></li>
                    <li><a href="" class="footer-link">FAQ</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="footer-copyright">© 2026 Teman Singgah — All rights reserved.</p>
            <div class="footer-legal">
                <a href="" class="footer-link bottom">Kebijakan Privasi</a>
                <span class="footer-dot">•</span>
                <a href="" class="footer-link bottom">Syarat & Ketentuan</a>
            </div>
        </div>
    </footer>

    <script src="../components/navbar.js"></script>
    <script src="../popups/auth.js"></script>
</body>

</html>