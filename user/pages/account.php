<?php
session_start();
include "../../koneksi.php";

// Redirect jika belum login
if (!isset($_SESSION['user_id'])) {
  header("Location: /teman_singgah/index.php?auth=login");
  exit;
}

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

// Inisial nama untuk avatar fallback
$inisial = strtoupper(mb_substr($user['nama'], 0, 1));

// Path foto profil
$photo_url = '';
if (!empty($user['photo']) && file_exists("../../assets/uploads/photos/" . $user['photo'])) {
  $photo_url = "/teman_singgah/assets/uploads/photos/" . htmlspecialchars($user['photo']);
}

// Format tanggal bergabung
$tanggal_bergabung = '';
if (!empty($user['tanggal_daftar'])) {
  $bulan_id = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
  $ts = strtotime($user['tanggal_daftar']);
  $tanggal_bergabung = $bulan_id[date('n', $ts) - 1] . ' ' . date('Y', $ts);
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Akun Saya | Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../../components/navbar.css" />
  <link rel="stylesheet" href="../../components/footer.css" />
  <link rel="stylesheet" href="../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/account.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
  <style>
    .avatar-large {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      object-fit: cover;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.2rem;
      font-weight: 700;
      background: var(--color-primary, #4f6ef7);
      color: #fff;
      overflow: hidden;
    }

    .avatar-large img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }
  </style>
</head>

<body>
  <header class="navbar">
    <nav class="navbar-container">
      <a href="../../index.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
        <img src="../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
      </div>
      <ul class="nav-menu">
        <li class="nav-item"><a href="../../index.php" class="nav-link">Cari Penginapan</a></li>
        <li class="nav-item"><a href="./promo_deals.php" class="nav-link">Promo & Deals</a></li>
        <li class="nav-item"><a href="./become_host.php" class="nav-link">Jadi Host</a></li>
        <li class="nav-item"><a href="./about_us.php" class="nav-link">Tentang Kami</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <div class="nav-right">
        <a href="../../host/onboarding/pages/about_place.html">
          <button class="ghost-button">Ganti ke host</button>
        </a>
        <div class="icon-buttons">
          <?php if ($photo_url): ?>
            <button class="icon-button profile" aria-label="Profile" style="padding:0;overflow:hidden;">
              <img src="<?= $photo_url ?>" alt="Foto Profil"
                style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
            </button>
          <?php else: ?>
            <button class="icon-button profile" aria-label="Profile"><?= $inisial ?></button>
          <?php endif; ?>
          <button class="icon-button hamburger" aria-label="Hamburger"><i class="ph-bold ph-list"></i></button>
        </div>
        <div id="hamburgerDropdown"></div>
        <div id="languagePopup"></div>
        <div id="authPopup"></div>
      </div>
    </nav>
  </header>

  <main class="main-content">
    <section class="account-hero">
      <div class="account-hero-container">
        <div class="account-hero-badge">
          <i class="ph-fill ph-user-circle"></i>
          <span>Akun Saya</span>
        </div>
        <h1 class="account-hero-title">Tentang Saya</h1>
        <p class="account-hero-subtitle">Kelola informasi profil dan lihat aktivitas perjalanan Anda.</p>
      </div>
    </section>

    <section class="account-section">
      <div class="account-container">
        <aside class="account-sidebar">
          <h2 class="sidebar-title">Profil</h2>
          <nav class="sidebar-nav">
            <a href="./account.php" class="sidebar-link active">
              <div class="link-icon primary-avatar" style="<?= $photo_url ? 'padding:0;overflow:hidden;' : '' ?>">
                <?php if ($photo_url): ?>
                  <img src="<?= $photo_url ?>" alt="Foto Profil"
                    style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
                <?php else: ?>
                  <?= $inisial ?>
                <?php endif; ?>
              </div>
              <span>Tentang Saya</span>
            </a>
            <a href="./history.php" class="sidebar-link">
              <div class="link-icon"><i class="ph-bold ph-suitcase"></i></div>
              <span>Riwayat Perjalanan</span>
            </a>
            <a href="./settings.php" class="sidebar-link">
              <div class="link-icon"><i class="ph-bold ph-gear"></i></div>
              <span>Pengaturan</span>
            </a>
          </nav>
        </aside>

        <div class="account-content">
          <div class="content-header">
            <h2 class="content-title">Tentang Saya</h2>
            <a href="./edit_account.php">
              <button class="edit-button">
                <i class="ph-bold ph-pencil-simple"></i>
                Edit
              </button>
            </a>
          </div>

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
              <span class="profile-role"><?= htmlspecialchars($user['role']) ?></span>
            </div>
            <div class="profile-panel">
              <h3 class="panel-title">Lengkapi profilmu</h3>
              <p class="panel-desc">Profil yang lengkap membuat host dan tamu lain lebih mudah mengenalmu, serta
                meningkatkan kepercayaan saat melakukan booking.</p>
              <a href="./edit_account.php">
                <button class="panel-button">
                  <i class="ph-bold ph-pencil-simple"></i>
                  Lengkapi Sekarang
                </button>
              </a>
            </div>
          </div>

          <div class="section-divider"></div>

          <div class="info-grid">
            <?php if (!empty($user['pekerjaan'])): ?>
              <div class="info-card">
                <div class="info-icon"><i class="ph-bold ph-briefcase"></i></div>
                <div class="info-content">
                  <span class="info-label">Pekerjaan</span>
                  <span class="info-value"><?= htmlspecialchars($user['pekerjaan']) ?></span>
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

            <?php if ($tanggal_bergabung): ?>
              <div class="info-card">
                <div class="info-icon"><i class="ph-bold ph-calendar"></i></div>
                <div class="info-content">
                  <span class="info-label">Bergabung</span>
                  <span class="info-value"><?= $tanggal_bergabung ?></span>
                </div>
              </div>
            <?php endif; ?>
          </div>

          <?php if (!empty($user['tentang'])): ?>
            <div class="section-divider"></div>
            <div class="info-card">
              <div class="info-content">
                <span class="info-label">Tentang Saya</span>
                <span class="info-value"><?= nl2br(htmlspecialchars($user['tentang'])) ?></span>
              </div>
            </div>
          <?php endif; ?>

          <div class="section-divider"></div>

          <div class="activity-section">
            <h3 class="activity-title">Aktivitas</h3>
            <div class="profile-items-list">
              <a href="#" class="profile-field-item">
                <div class="profile-field-icon"><i class="ph-bold ph-chats"></i></div>
                <div class="profile-field-content">
                  <div class="profile-field-text">
                    <span class="profile-field-label">Ulasan yang saya tulis</span>
                    <span class="profile-field-badge">12 ulasan</span>
                  </div>
                  <i class="ph-bold ph-caret-right"></i>
                </div>
              </a>
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="footer-grid">
      <div class="footer-column">
        <span class="footer-brand">Teman Singgah</span>
        <p class="footer-description">Platform booking penginapan terpercaya di seluruh Indonesia, dari hotel berbintang
          hingga homestay lokal.</p>
        <div class="footer-social">
          <a href="" class="social-link"><i class="ri-instagram-line"></i></a>
          <a href="" class="social-link"><i class="ri-facebook-circle-line"></i></a>
          <a href="" class="social-link"><i class="ri-youtube-line"></i></a>
          <a href="" class="social-link"><i class="ri-twitter-line"></i></a>
          <a href="" class="social-link"><i class="ri-mail-line"></i></a>
        </div>
      </div>
      <div class="footer-column">
        <h3 class="footer-title">Navigasi</h3>
        <ul class="footer-links">
          <li><a href="../../index.php" class="footer-link">Beranda</a></li>
          <li><a href="./promo_deals.php" class="footer-link">Promo & Deals</a></li>
          <li><a href="./become_host.php" class="footer-link">Jadi Host</a></li>
          <li><a href="./about_us.php" class="footer-link">Tentang Kami</a></li>
          <li><a href="./account.php" class="footer-link">Akun</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3 class="footer-title">Dukungan</h3>
        <ul class="footer-links">
          <li><a href="" class="footer-link">Pusat Bantuan</a></li>
          <li><a href="" class="footer-link">FAQ</a></li>
          <li><a href="" class="footer-link">Cara Menjadi Host</a></li>
          <li><a href="" class="footer-link">Cara Booking</a></li>
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

  <script src="../scripts/account.js"></script>
  <script src="../../components/navbar.js"></script>
  <script src="../../popups/auth.js"></script>
</body>

</html>