<?php
// user/pages/wishlist.php
session_start();
include '../../koneksi.php';

$isLoggedIn  = isset($_SESSION['id']);
$user_id     = $isLoggedIn ? (int) $_SESSION['id'] : 0;
$userInitial = $isLoggedIn ? strtoupper(mb_substr($_SESSION['nama'] ?? '', 0, 1)) : '';
$userName    = $_SESSION['nama'] ?? '';
$userPhoto   = '';
if (!empty($_SESSION['photo']) && file_exists("../../assets/uploads/photos/" . $_SESSION['photo'])) {
    $userPhoto = "/teman_singgah/assets/uploads/photos/" . htmlspecialchars($_SESSION['photo']);
}

// ── AJAX: toggle simpan/hapus ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    header('Content-Type: application/json');
    if (!$isLoggedIn) {
        echo json_encode(['status' => 'error', 'message' => 'login_required']);
        exit;
    }
    $listing_id = (int) ($_POST['listing_id'] ?? 0);
    if ($listing_id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'invalid_listing']);
        exit;
    }
    $stmt = mysqli_prepare($koneksi, "SELECT id FROM wishlists WHERE user_id = ? AND listing_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $user_id, $listing_id);
    mysqli_stmt_execute($stmt);
    $existing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if ($existing) {
        $stmt = mysqli_prepare($koneksi, "DELETE FROM wishlists WHERE user_id = ? AND listing_id = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $listing_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'ok', 'saved' => false]);
    } else {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO wishlists (user_id, listing_id, dibuat_pada) VALUES (?, ?, NOW())");
        mysqli_stmt_bind_param($stmt, 'ii', $user_id, $listing_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 'ok', 'saved' => true]);
    }
    exit;
}

// ── AJAX: ambil status semua listing yang disimpan ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'status') {
    header('Content-Type: application/json');
    if (!$isLoggedIn) {
        echo json_encode(['status' => 'ok', 'saved_ids' => []]);
        exit;
    }
    $result    = mysqli_query($koneksi, "SELECT listing_id FROM wishlists WHERE user_id = {$user_id}");
    $saved_ids = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $saved_ids[] = (int) $row['listing_id'];
    }
    echo json_encode(['status' => 'ok', 'saved_ids' => $saved_ids]);
    exit;
}

// ── Render halaman: ambil semua penginapan tersimpan ──────────────────────
$wishlisted = [];
if ($isLoggedIn) {
    $stmt = mysqli_prepare($koneksi, "
        SELECT
            l.id, l.judul, l.lokasi, l.harga_malam,
            lp.nama_file AS foto_cover,
            ROUND(AVG(r.rating), 1) AS rating_avg,
            w.dibuat_pada AS saved_at
        FROM wishlists w
        JOIN listings l ON l.id = w.listing_id AND l.status = 'aktif'
        LEFT JOIN listing_photos lp ON lp.listing_id = l.id AND lp.adalah_cover = 1
        LEFT JOIN reviews r ON r.listing_id = l.id
        WHERE w.user_id = ?
        GROUP BY l.id, l.judul, l.lokasi, l.harga_malam, lp.nama_file, w.dibuat_pada
        ORDER BY w.dibuat_pada DESC
    ");
    mysqli_stmt_bind_param($stmt, 'i', $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $wishlisted[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Favorit | Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../../components/navbar.css" />
  <link rel="stylesheet" href="../../components/footer.css" />
  <link rel="stylesheet" href="../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/wishlist.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
  <style>
    .favorite-card-wrapper { display: contents; }
    .favorite-card-meta {
      display: flex; align-items: center; justify-content: space-between;
      margin-top: 6px; gap: 8px;
    }
    .favorite-card-price { font-size: 14px; font-weight: 600; color: var(--color-primary, #8b2500); }
    .favorite-card-price .price-unit { font-weight: 400; color: #9ca3af; font-size: 12px; }
    .favorite-card-rating { display: flex; align-items: center; gap: 3px; font-size: 13px; font-weight: 500; }
    .favorite-card-rating i { color: #f59e0b; font-size: 12px; }
    .favorite-card-location { display: flex; align-items: center; gap: 4px; font-size: 12px; color: #9ca3af; margin-top: 2px; }
    .wishlist-empty-state {
      display: flex; flex-direction: column; align-items: center;
      text-align: center; padding: 80px 24px; gap: 12px;
    }
    .wishlist-empty-state > i { font-size: 48px; color: var(--color-primary, #8b2500); opacity: 0.35; margin-bottom: 8px; }
    .wishlist-empty-state h3 { font-size: 20px; font-weight: 600; margin: 0; }
    .wishlist-empty-state p { font-size: 14px; color: #6b7280; margin: 0; max-width: 320px; line-height: 1.5; }
    .wishlist-cta-btn {
      margin-top: 8px; padding: 10px 24px; background: var(--color-primary, #8b2500);
      color: #fff; border: none; border-radius: 8px; font-size: 14px; font-weight: 500;
      cursor: pointer; text-decoration: none; display: inline-block; transition: opacity 0.2s;
    }
    .wishlist-cta-btn:hover { opacity: 0.85; }
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
        <li class="nav-item"><a href="promo_deals.php" class="nav-link">Promo &amp; Deals</a></li>
        <li class="nav-item"><a href="become_host.php" class="nav-link">Jadi Host</a></li>
        <li class="nav-item"><a href="about_us.php" class="nav-link">Tentang Kami</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <div class="nav-right">
        <a href="../../host/dashboard/pages/reservations.php">
          <button class="ghost-button">Ganti ke host</button>
        </a>
        <div class="icon-buttons">
          <?php if ($isLoggedIn): ?>
            <button class="icon-button profile" aria-label="Profile" <?= $userPhoto ? 'style="padding:0;overflow:hidden;"' : '' ?>>
              <?php if ($userPhoto): ?>
                <img src="<?= $userPhoto ?>" alt="Foto Profil" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
              <?php else: ?>
                <?= htmlspecialchars($userInitial) ?>
              <?php endif; ?>
            </button>
          <?php else: ?>
            <button class="icon-button profile hidden" aria-label="Profile"></button>
          <?php endif; ?>
          <button class="icon-button hamburger <?= $isLoggedIn ? 'hidden' : '' ?>" aria-label="Hamburger">
            <i class="ph-bold ph-list"></i>
          </button>
        </div>
        <div id="hamburgerDropdown"></div>
        <div id="languagePopup"></div>
        <div id="authPopup"></div>
      </div>
    </nav>
  </header>

  <main class="main-content">
    <div class="section-header">
      <h2 class="section-title">Favorit</h2>
    </div>

    <?php if (!$isLoggedIn): ?>
      <div class="wishlist-empty-state">
        <i class="ph-bold ph-heart"></i>
        <h3>Masuk untuk melihat favoritmu</h3>
        <p>Simpan penginapan yang kamu suka dan akses kapan saja.</p>
        <button class="wishlist-cta-btn" onclick="document.getElementById('authOverlay')?.classList.add('active')">
          Masuk / Daftar
        </button>
      </div>

    <?php elseif (empty($wishlisted)): ?>
      <div class="wishlist-empty-state" id="emptyState">
        <i class="ph-bold ph-heart"></i>
        <h3>Belum ada penginapan tersimpan</h3>
        <p>Tekan ikon <strong>simpan</strong> pada penginapan yang kamu suka untuk menyimpannya di sini.</p>
        <a href="../../index.php" class="wishlist-cta-btn">Jelajahi Penginapan</a>
      </div>

    <?php else: ?>
      <section class="list-section" id="wishlistSection">
        <?php foreach ($wishlisted as $row):
          $foto = !empty($row['foto_cover'])
            ? (str_starts_with($row['foto_cover'], 'http')
                ? $row['foto_cover']
                : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($row['foto_cover']))
            : '/teman_singgah/assets/images/apurva_kempinski_bali.jpg';
          $harga  = 'Rp ' . number_format($row['harga_malam'], 0, ',', '.');
          $rating = $row['rating_avg'] ?? '–';
          $judul  = htmlspecialchars($row['judul']);
          $parts  = array_map('trim', explode(',', $row['lokasi']));
          $lokasi = htmlspecialchars(implode(', ', array_slice($parts, 0, 2)));
          $id     = (int) $row['id'];
        ?>
          <div class="favorite-card-wrapper" data-listing-id="<?= $id ?>">
            <a href="detail_card.php?id=<?= $id ?>" class="favorite-card">
              <div class="favorite-card-image-container">
                <img src="<?= $foto ?>" class="favorite-card-image" alt="<?= $judul ?>" />
                <button class="list-remove" aria-label="Hapus dari favorit" data-listing-id="<?= $id ?>">
                  <i class="ph-bold ph-x"></i>
                </button>
              </div>
              <div class="favorite-card-body">
                <span class="favorite-card-name"><?= $judul ?></span>
                <span class="favorite-card-location">
                  <i class="ph-bold ph-map-pin"></i><?= $lokasi ?>
                </span>
                <div class="favorite-card-meta">
                  <span class="favorite-card-price"><?= $harga ?><span class="price-unit"> / malam</span></span>
                  <?php if ($rating !== '–'): ?>
                    <span class="favorite-card-rating"><i class="ph-fill ph-star"></i><?= $rating ?></span>
                  <?php endif; ?>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </section>
    <?php endif; ?>
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
        <h3 class="footer-title">Navigasi</h3>
        <ul class="footer-links">
          <li><a href="../../index.php" class="footer-link">Beranda</a></li>
          <li><a href="promo_deals.php" class="footer-link">Promo &amp; Deals</a></li>
          <li><a href="become_host.php" class="footer-link">Jadi Host</a></li>
          <li><a href="about_us.php" class="footer-link">Tentang Kami</a></li>
          <li><a href="account.php" class="footer-link">Akun</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3 class="footer-title">Dukungan</h3>
        <ul class="footer-links">
          <li><a href="#" class="footer-link">Pusat Bantuan</a></li>
          <li><a href="#" class="footer-link">FAQ</a></li>
          <li><a href="become_host.php" class="footer-link">Cara Menjadi Host</a></li>
          <li><a href="#" class="footer-link">Cara Booking</a></li>
          <li><a href="about_us.php" class="footer-link">Tentang Kami</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p class="footer-copyright">© 2026 Teman Singgah — All rights reserved.</p>
      <div class="footer-legal">
        <a href="" class="footer-link bottom">Kebijakan Privasi</a>
        <span class="footer-dot">•</span>
        <a href="" class="footer-link bottom">Syarat &amp; Ketentuan</a>
      </div>
    </div>
  </footer>

  <!-- Auth overlay (minimal, untuk trigger login) -->
  <div id="authOverlay" class="auth-overlay">
    <div class="auth-form-card">
      <div class="auth-step active" id="authStepPilih">
        <div class="auth-header-section">
          <div class="empty-div"></div>
          <button type="button" class="auth-nav-button" aria-label="Tutup" data-action="close-auth">
            <i class="ph-bold ph-x"></i>
          </button>
        </div>
        <div class="auth-body-section">
          <div class="auth-logo-container">
            <div class="auth-logo">
              <img class="auth-logo-image" src="../../assets/logo/logo_temansinggah.svg" alt="Teman Singgah" />
            </div>
            <h2 class="auth-title center">Selamat datang</h2>
            <p class="auth-subtitle center">Masuk atau buat akun baru untuk mulai memesan.</p>
          </div>
          <div class="auth-fields">
            <button class="auth-submit-button" type="button" id="btnKeLogin">Masuk</button>
            <button class="auth-submit-button outline" type="button" id="btnKeDaftar">Daftar akun baru</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    <?php if ($isLoggedIn): ?>
      localStorage.setItem('isLoggedIn', 'true');
      localStorage.setItem('userInitial', '<?= htmlspecialchars($userInitial) ?>');
      localStorage.setItem('userName', '<?= htmlspecialchars($userName) ?>');
      localStorage.setItem('userPhoto', '<?= $userPhoto ?>');
    <?php else: ?>
      localStorage.removeItem('isLoggedIn');
    <?php endif; ?>

    // ── Hapus dari wishlist via tombol X ──────────────────────────────────
    document.querySelectorAll('.list-remove').forEach(btn => {
      btn.addEventListener('click', async function(e) {
        e.preventDefault();
        e.stopPropagation();

        const listingId = this.dataset.listingId;
        const wrapper   = document.querySelector(`.favorite-card-wrapper[data-listing-id="${listingId}"]`);

        if (wrapper) {
          wrapper.style.transition = 'opacity 0.3s, transform 0.3s';
          wrapper.style.opacity = '0';
          wrapper.style.transform = 'scale(0.95)';
        }

        try {
          const fd = new FormData();
          fd.append('action', 'toggle');
          fd.append('listing_id', listingId);
          const res  = await fetch('wishlist.php', { method: 'POST', body: fd });
          const data = await res.json();

          if (data.status === 'ok' && !data.saved) {
            setTimeout(() => {
              wrapper?.remove();
              if (!document.querySelector('.favorite-card-wrapper')) {
                document.getElementById('wishlistSection')?.remove();
                document.querySelector('.main-content').insertAdjacentHTML('beforeend', `
                  <div class="wishlist-empty-state">
                    <i class="ph-bold ph-heart"></i>
                    <h3>Belum ada penginapan tersimpan</h3>
                    <p>Tekan ikon <strong>simpan</strong> pada penginapan yang kamu suka untuk menyimpannya di sini.</p>
                    <a href="../../index.php" class="wishlist-cta-btn">Jelajahi Penginapan</a>
                  </div>
                `);
              }
            }, 300);
          } else {
            if (wrapper) { wrapper.style.opacity = '1'; wrapper.style.transform = ''; }
          }
        } catch {
          if (wrapper) { wrapper.style.opacity = '1'; wrapper.style.transform = ''; }
        }
      });
    });
  </script>

  <script src="../../components/navbar.js"></script>
  <script src="../../popups/auth.js"></script>
  <script src="../../components/wishlist.js"></script>
</body>
</html>