<?php
session_start();

$isLoggedIn = isset($_SESSION['nama']);
$userInitial = $isLoggedIn ? strtoupper(mb_substr($_SESSION['nama'], 0, 1)) : '';
$userName = $isLoggedIn ? $_SESSION['nama'] : '';

$userPhoto = '';
if (!empty($_SESSION['photo']) && file_exists("assets/uploads/photos/" . $_SESSION['photo'])) {
  $userPhoto = "/teman_singgah/assets/uploads/photos/" . htmlspecialchars($_SESSION['photo']);
}

include "koneksi.php";

// ── Helper functions ─────────────────────────────────────────────────────────
function fetchAll($result)
{
  $rows = [];
  while ($row = mysqli_fetch_assoc($result))
    $rows[] = $row;
  return $rows;
}

function renderHotelCard($row)
{
  $foto = !empty($row['foto_cover'])
    ? '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($row['foto_cover'])
    : '/teman_singgah/assets/images/apurva_kempinski_bali.jpg';
  $harga = 'Rp ' . number_format($row['harga_malam'], 0, ',', '.');
  $rating = $row['rating_avg'] ? $row['rating_avg'] : '–';
  $judul = htmlspecialchars($row['judul']);
  $parts = array_map('trim', explode(',', $row['lokasi']));
  $lokasi = htmlspecialchars(implode(', ', array_slice($parts, 0, 2)));
  $id = (int) $row['id'];
  echo "
        <a href=\"user/pages/detail_card.php?id={$id}\" class=\"hotel-card\">
          <div class=\"card-image-wrapper\">
            <img src=\"{$foto}\" alt=\"{$judul}\" class=\"card-image\" />
            <img src=\"assets/icons/save.svg\" alt=\"wishlist\" class=\"save-button\" />
          </div>
          <div class=\"card-content\">
            <div class=\"card-top\">
              <h3 class=\"card-title\">{$judul}</h3>
              <span class=\"card-location-text\"><i class=\"ph-bold ph-map-pin\"></i>{$lokasi}</span>
            </div>
            <div class=\"card-bottom\">
              <div class=\"price-section\">
                <span class=\"card-price\">{$harga}</span>
                <span class=\"price-unit\">/ malam</span>
              </div>
              <span class=\"card-rating\"><i class=\"ph-fill ph-star\"></i>{$rating}</span>
            </div>
          </div>
        </a>";
}

function renderSeeAllCard($rows_preview)
{
  echo '<a href="user/pages/search_result.html" class="see-all-card">';
  echo '<div class="see-all-photos">';
  $count = 0;
  foreach ($rows_preview as $r) {
    if ($count >= 3)
      break;
    $foto = !empty($r['foto_cover'])
      ? '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($r['foto_cover'])
      : '/teman_singgah/assets/images/apurva_kempinski_bali.jpg';
    echo "<img src=\"{$foto}\" alt=\"\" class=\"see-all-photo\" />";
    $count++;
  }
  while ($count < 3) {
    echo '<img src="assets/images/apurva_kempinski_bali.jpg" alt="" class="see-all-photo" />';
    $count++;
  }
  echo '</div><span class="see-all-label">Lihat Semua</span></a>';
}

// ── 1. Destinasi Populer ─────────────────────────────────────────────────────
// 1 listing per kota unik (max 5), sorted by jumlah booking terbanyak
$q_destinasi = mysqli_query($koneksi, "
    SELECT
        l.id, l.judul, l.lokasi,
        lp.nama_file AS foto_cover,
        COUNT(DISTINCT b.id) AS jumlah_booking,
        SUBSTRING_INDEX(l.lokasi, ',', 1) AS kota
    FROM listings l
    LEFT JOIN listing_photos lp ON lp.listing_id = l.id AND lp.adalah_cover = 1
    LEFT JOIN bookings b ON b.listing_id = l.id
    WHERE l.status = 'aktif'
    GROUP BY l.id, l.judul, l.lokasi, lp.nama_file
    ORDER BY jumlah_booking DESC, l.dibuat_pada DESC
");
$destinasi_rows = [];
$kota_seen = [];
while ($row = mysqli_fetch_assoc($q_destinasi)) {
  $kota = trim($row['kota']);
  if (isset($kota_seen[$kota]))
    continue;
  $kota_seen[$kota] = true;
  $destinasi_rows[] = $row;
  if (count($destinasi_rows) >= 5)
    break;
}

// ── 2. Rekomendasi Untukmu ───────────────────────────────────────────────────
// Random
$q_rekomendasi = mysqli_query($koneksi, "
    SELECT
        l.id, l.judul, l.lokasi, l.harga_malam,
        lp.nama_file AS foto_cover,
        ROUND(AVG(r.rating), 1) AS rating_avg
    FROM listings l
    LEFT JOIN listing_photos lp ON lp.listing_id = l.id AND lp.adalah_cover = 1
    LEFT JOIN bookings b ON b.listing_id = l.id
    LEFT JOIN reviews r ON r.booking_id = b.id
    WHERE l.status = 'aktif'
    GROUP BY l.id, l.judul, l.lokasi, l.harga_malam, lp.nama_file
    ORDER BY RAND()
    LIMIT 9
");
$rekomendasi_rows = fetchAll($q_rekomendasi);

// ── 3. Favorit Traveler ──────────────────────────────────────────────────────
// Skor kombinasi: rating (40%) + jumlah review (30%) + jumlah booking (30%)
// Dinormalisasi supaya bobot seimbang
$q_favorit = mysqli_query($koneksi, "
    SELECT
        l.id, l.judul, l.lokasi, l.harga_malam,
        lp.nama_file AS foto_cover,
        ROUND(AVG(r.rating), 1) AS rating_avg,
        COUNT(DISTINCT r.id) AS jumlah_review,
        COUNT(DISTINCT b.id) AS jumlah_booking
    FROM listings l
    LEFT JOIN listing_photos lp ON lp.listing_id = l.id AND lp.adalah_cover = 1
    LEFT JOIN bookings b ON b.listing_id = l.id
    LEFT JOIN reviews r ON r.booking_id = b.id
    WHERE l.status = 'aktif'
    GROUP BY l.id, l.judul, l.lokasi, l.harga_malam, lp.nama_file
    HAVING jumlah_review > 0
    ORDER BY (
        COALESCE(AVG(r.rating), 0) * 0.4
        + COUNT(DISTINCT r.id)     * 0.3
        + COUNT(DISTINCT b.id)     * 0.3
    ) DESC
    LIMIT 9
");
$favorit_rows = fetchAll($q_favorit);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Cari Penginapan | Teman Singgah</title>
  <link rel="icon" href="assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="components/root.css" />
  <link rel="stylesheet" href="components/navbar.css" />
  <link rel="stylesheet" href="components/search_bar.css" />
  <link rel="stylesheet" href="components/footer.css" />
  <link rel="stylesheet" href="popups/auth.css" />
  <link rel="stylesheet" href="user/styles/home.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
</head>

<body>
  <header class="navbar">
    <nav class="navbar-container">
      <a href="index.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
        <img src="assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
      </div>
      <ul class="nav-menu">
        <li class="nav-item"><a href="index.php" class="nav-link active">Cari Penginapan</a></li>
        <li class="nav-item"><a href="user/pages/promo_deals.php" class="nav-link">Promo & Deals</a></li>
        <li class="nav-item"><a href="user/pages/become_host.php" class="nav-link">Jadi Host</a></li>
        <li class="nav-item"><a href="user/pages/about_us.php" class="nav-link">Tentang Kami</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <div class="nav-right">
        <a href="host/onboarding/pages/about_place.html">
          <button class="ghost-button">Ganti ke host</button>
        </a>
        <div class="icon-buttons">
          <?php if ($isLoggedIn): ?>
            <button class="icon-button profile" aria-label="Profile" <?= $userPhoto ? 'style="padding:0;overflow:hidden;"' : '' ?>>
              <?php if ($userPhoto): ?>
                <img src="<?= $userPhoto ?>" alt="Foto Profil"
                  style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
              <?php else: ?>
                <?= htmlspecialchars($userInitial) ?>
              <?php endif; ?>
            </button>
          <?php else: ?>
            <button class="icon-button profile hidden" aria-label="Profile">
              <?= htmlspecialchars($userInitial) ?>
            </button>
          <?php endif; ?>
          <button class="icon-button hamburger <?= $isLoggedIn ? 'hidden' : '' ?>" aria-label="Hamburger">
            <i class="ph-bold ph-list"></i>
          </button>
        </div>
        <div id="hamburgerDropdown"></div>
        <div id="languagePopup"></div>
      </div>
    </nav>
  </header>

  <main class="main-content">

    <!-- Hero -->
    <section class="hero-section">
      <div class="hero-bg"></div>
      <div class="hero-overlay"></div>
      <div class="hero-container">
        <div class="hero-content">
          <h1 class="hero-title">Temukan Penginapan Impianmu di Seluruh Indonesia</h1>
          <p class="hero-description">
            Platform all-in-one untuk cari penginapan, bandingkan harga, dan
            pesan dalam hitungan menit. Perjalananmu makin mudah dan menyenangkan!
          </p>
          <div class="hero-buttons" id="hero-buttons">
            <a href="#hero-buttons">
              <button class="hero-button primary-button">Cari Penginapan</button>
            </a>
            <a href="user/pages/become_host.html">
              <button class="hero-button secondary-button">Jadi Host</button>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Search Bar -->
    <section class="search-bar">
      <div class="search-fields">
        <div class="search-field">
          <i class="ph-bold ph-map-pin"></i>
          <div class="field-text">
            <span class="field-label">Mau Kemana?</span>
            <input type="text" id="destinationInput" class="field-value input" placeholder="Cari Tempatnya"
              autocomplete="off" />
          </div>
        </div>
        <div class="search-field">
          <i class="ph-bold ph-calendar-blank"></i>
          <div class="field-text">
            <span class="field-label">Kapan?</span>
            <span class="field-value" id="dateSummary">Tambahkan Tanggal</span>
          </div>
        </div>
        <div class="search-field">
          <i class="ph-bold ph-users-three"></i>
          <div class="field-text">
            <span class="field-label">Siapa Saja?</span>
            <span class="field-value" id="guestSummary">Tambahkan Pengunjung</span>
          </div>
          <a href="user/pages/search_result.html">
            <button class="search-button" type="button">
              <i class="ph-bold ph-magnifying-glass"></i>
            </button>
          </a>
        </div>
      </div>
    </section>
    <div id="destinationDropdown"></div>
    <div id="calendarDropdown"></div>
    <div id="guestCounterDropdown"></div>


    <!-- ── 1. Destinasi Populer ──────────────────────────────────────────── -->
    <?php if (!empty($destinasi_rows)): ?>
      <section class="card-section">
        <span class="section-header">
          <h2 class="section-title">Destinasi Populer</h2>
        </span>
        <div class="card-grid">
          <?php foreach ($destinasi_rows as $i => $row):
            $foto = !empty($row['foto_cover'])
              ? '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($row['foto_cover'])
              : '/teman_singgah/assets/images/apurva_kempinski_bali.jpg';
            $parts = array_map('trim', explode(',', $row['lokasi']));
            $lokasi_display = htmlspecialchars(implode(', ', array_slice($parts, 0, 2)));
            $judul = htmlspecialchars($row['judul']);
            $id = (int) $row['id'];
            $is_big = ($i === 0) ? ' big' : '';
            ?>
            <a href="user/pages/detail_card.php?id=<?= $id ?>" class="grid-card<?= $is_big ?>">
              <img class="card-image" src="<?= $foto ?>" alt="<?= $judul ?>" />
              <div class="card-overlay"></div>
              <div class="card-text">
                <span class="card-location"><?= $lokasi_display ?></span>
                <span class="card-name"><?= $judul ?></span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </section>
    <?php endif; ?>


    <!-- ── 2. Penginapan di Dekat Anda (via JS geolocation) ─────────────── -->
    <div class="card-section" id="section-nearby" style="display:none;">
      <div class="section-header">
        <h2 class="section-title">Penginapan di Dekat Anda</h2>
        <div class="section-controls">
          <button class="control-button prev-button disabled" aria-label="Sebelumnya"><i
              class="ph-bold ph-caret-left"></i></button>
          <button class="control-button next-button" aria-label="Selanjutnya"><i
              class="ph-bold ph-caret-right"></i></button>
        </div>
      </div>
      <div class="card-list" id="nearby-card-list"></div>
    </div>


    <!-- ── 3. Rekomendasi Untukmu ────────────────────────────────────────── -->
    <div class="card-section">
      <div class="section-header">
        <h2 class="section-title">Rekomendasi Untukmu</h2>
        <div class="section-controls">
          <button class="control-button prev-button disabled" aria-label="Sebelumnya"><i
              class="ph-bold ph-caret-left"></i></button>
          <button class="control-button next-button" aria-label="Selanjutnya"><i
              class="ph-bold ph-caret-right"></i></button>
        </div>
      </div>
      <div class="card-list">
        <?php if (empty($rekomendasi_rows)): ?>
          <p style="padding:16px;color:#9ca3af;">Belum ada listing tersedia.</p>
        <?php else: ?>
          <?php foreach ($rekomendasi_rows as $row)
            renderHotelCard($row); ?>
          <?php if (count($rekomendasi_rows) >= 9)
            renderSeeAllCard($rekomendasi_rows); ?>
        <?php endif; ?>
      </div>
    </div>


    <!-- ── 4. Favorit Traveler ───────────────────────────────────────────── -->
    <div class="card-section">
      <div class="section-header">
        <h2 class="section-title">Favorit Traveler</h2>
        <div class="section-controls">
          <button class="control-button prev-button disabled" aria-label="Sebelumnya"><i
              class="ph-bold ph-caret-left"></i></button>
          <button class="control-button next-button" aria-label="Selanjutnya"><i
              class="ph-bold ph-caret-right"></i></button>
        </div>
      </div>
      <div class="card-list">
        <?php if (empty($favorit_rows)): ?>
          <p style="padding:16px;color:#9ca3af;">Belum ada listing tersedia.</p>
        <?php else: ?>
          <?php foreach ($favorit_rows as $row)
            renderHotelCard($row); ?>
          <?php if (count($favorit_rows) >= 9)
            renderSeeAllCard($favorit_rows); ?>
        <?php endif; ?>
      </div>
    </div>

  </main>

  <footer class="footer">
    <div class="footer-grid">
      <div class="footer-column">
        <span class="footer-brand">Teman Singgah</span>
        <p class="footer-description">
          Platform booking penginapan terpercaya di seluruh Indonesia, dari
          hotel berbintang hingga homestay lokal.
        </p>
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
          <li><a href="index.php" class="footer-link">Beranda</a></li>
          <li><a href="user/pages/promo_deals.php" class="footer-link">Promo & Deals</a></li>
          <li><a href="user/pages/become_host.php" class="footer-link">Jadi Host</a></li>
          <li><a href="user/pages/about_us.php" class="footer-link">Tentang Kami</a></li>
          <li><a href="user/pages/account.php" class="footer-link">Akun</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3 class="footer-title">Dukungan</h3>
        <ul class="footer-links">
          <li><a href="#" class="footer-link">Pusat Bantuan</a></li>
          <li><a href="#" class="footer-link">FAQ</a></li>
          <li><a href="user/pages/become_host.html" class="footer-link">Cara Menjadi Host</a></li>
          <li><a href="#" class="footer-link">Cara Booking</a></li>
          <li><a href="user/pages/about_us.php" class="footer-link">Tentang Kami</a></li>
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

  <script src="user/scripts/home.js"></script>
  <script src="popups/auth.js"></script>
  <script>
    <?php if ($isLoggedIn): ?>
      localStorage.setItem('isLoggedIn', 'true');
      localStorage.setItem('userInitial', '<?= htmlspecialchars($userInitial) ?>');
      localStorage.setItem('userName', '<?= htmlspecialchars($userName) ?>');
      localStorage.setItem('userPhoto', '<?= $userPhoto ?>');
    <?php else: ?>
      localStorage.removeItem('isLoggedIn');
      localStorage.removeItem('userInitial');
      localStorage.removeItem('userName');
      localStorage.removeItem('userPhoto');
    <?php endif; ?>
  </script>
  <script src="components/navbar.js"></script>
  <script src="components/search_bar.js"></script>

  <!-- ── Geolocation → Penginapan di Dekat Anda ──────────────────────────── -->
  <script>
      (function () {
        const section = document.getElementById('section-nearby');
        const cardList = document.getElementById('nearby-card-list');
        const FALLBACK = '/teman_singgah/assets/images/apurva_kempinski_bali.jpg';

        if (!navigator.geolocation) return;

        navigator.geolocation.getCurrentPosition(
          function (pos) {
            const lat = pos.coords.latitude;
            const lng = pos.coords.longitude;

            fetch('/teman_singgah/user/pages/nearby_listings.php?lat=' + lat + '&lng=' + lng)
              .then(function (res) { return res.json(); })
              .then(function (json) {
                if (json.status !== 'ok' || json.data.length === 0) return;

                const rows = json.data;
                let html = '';

                rows.forEach(function (r) {
                  const foto = r.foto_cover
                    ? '/teman_singgah/assets/uploads/listings/' + r.foto_cover
                    : FALLBACK;
                  const harga = 'Rp ' + parseInt(r.harga_malam).toLocaleString('id-ID');
                  const rating = r.rating_avg ? r.rating_avg : '–';
                  const parts = r.lokasi.split(',').map(function (s) { return s.trim(); });
                  const lokasi = parts.slice(0, 2).join(', ');
                  const jarak = r.jarak_km < 1
                    ? Math.round(r.jarak_km * 1000) + ' m'
                    : r.jarak_km + ' km';

                  html += '<a href="user/pages/detail_card.php?id=' + r.id + '" class="hotel-card">'
                    + '<div class="card-image-wrapper">'
                    + '<img src="' + foto + '" alt="' + r.judul + '" class="card-image" />'
                    + '<img src="assets/icons/save.svg" alt="wishlist" class="save-button" />'
                    + '</div>'
                    + '<div class="card-content">'
                    + '<div class="card-top">'
                    + '<h3 class="card-title">' + r.judul + '</h3>'
                    + '<span class="card-location-text"><i class="ph-bold ph-map-pin"></i>' + lokasi + ' · ' + jarak + '</span>'
                    + '</div>'
                    + '<div class="card-bottom">'
                    + '<div class="price-section">'
                    + '<span class="card-price">' + harga + '</span>'
                    + '<span class="price-unit">/ malam</span>'
                    + '</div>'
                    + '<span class="card-rating"><i class="ph-fill ph-star"></i>' + rating + '</span>'
                    + '</div>'
                    + '</div>'
                    + '</a>';
                });

                if (rows.length >= 9) {
                  const previews = rows.slice(0, 3).map(function (r) {
                    return r.foto_cover
                      ? '/teman_singgah/assets/uploads/listings/' + r.foto_cover
                      : FALLBACK;
                  });
                  html += '<a href="user/pages/search_result.html" class="see-all-card">'
                    + '<div class="see-all-photos">'
                    + previews.map(function (f) { return '<img src="' + f + '" alt="" class="see-all-photo" />'; }).join('')
                    + '</div>'
                    + '<span class="see-all-label">Lihat Semua</span>'
                    + '</a>';
                }

                cardList.innerHTML = html;
                section.style.display = '';
              })
              .catch(function () { });
          },
          function () { },
          { timeout: 8000 }
        );
      })();
  </script>

  <div id="authOverlay" class="auth-overlay">
    <div class="auth-form-card">

      <!-- STEP PILIH -->
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
              <img class="auth-logo-image" src="assets/logo/logo_temansinggah.svg" alt="Teman Singgah" />
            </div>
            <h2 class="auth-title center">Selamat datang</h2>
            <p class="auth-subtitle center">Masuk atau buat akun baru untuk mulai memesan.</p>
          </div>
          <div class="auth-fields">
            <button class="auth-submit-button" type="button" id="btnKeLogin">Masuk</button>
            <button class="auth-submit-button outline" type="button" id="btnKeDaftar">Daftar akun baru</button>
          </div>
          <div class="auth-divider">
            <span class="auth-divider-line"></span>
            <span class="auth-divider-text">atau lanjutkan dengan</span>
            <span class="auth-divider-line"></span>
          </div>
          <div class="auth-social-icons">
            <button type="button" class="auth-social-icon" aria-label="Lanjutkan dengan Google" id="btnSocialGoogle">
              <img src="assets/icons/google.svg" alt="Google" style="width:22px;height:22px;" />
            </button>
            <button type="button" class="auth-social-icon" aria-label="Lanjutkan dengan Apple" id="btnSocialApple">
              <img src="assets/icons/apple.svg" alt="Apple" style="width:22px;height:22px;" />
            </button>
            <button type="button" class="auth-social-icon" aria-label="Lanjutkan dengan Facebook"
              id="btnSocialFacebook">
              <img src="assets/icons/facebook.svg" alt="Facebook" style="width:22px;height:22px;" />
            </button>
          </div>
        </div>
      </div>

      <!-- STEP LOGIN -->
      <div class="auth-step" id="authStepLogin">
        <form action="/teman_singgah/auth/proses_login.php" method="POST" autocomplete="off">
          <div class="auth-header-section">
            <button type="button" class="auth-nav-button" aria-label="Kembali" data-action="ke-pilih"><i
                class="ph-bold ph-caret-left"></i></button>
            <button type="button" class="auth-nav-button" aria-label="Tutup" data-action="close-auth"><i
                class="ph-bold ph-x"></i></button>
          </div>
          <div class="auth-body-section">
            <div>
              <h2 class="auth-title">Masuk</h2>
              <p class="auth-subtitle">Masukkan email dan password kamu.</p>
            </div>
            <div class="auth-fields">
              <fieldset class="auth-field">
                <legend class="auth-input-label">Email</legend>
                <div class="auth-input-group">
                  <input type="email" name="email" id="loginEmail" class="auth-input" placeholder="contoh@email.com"
                    autocomplete="email" required />
                </div>
              </fieldset>
              <fieldset class="auth-field">
                <legend class="auth-input-label">Password</legend>
                <div class="auth-input-group auth-password-group">
                  <input type="password" name="password" id="loginPassword" class="auth-input"
                    placeholder="Masukkan password" autocomplete="current-password" required />
                  <button type="button" class="auth-toggle-password" aria-label="Tampilkan password"><i
                      class="ph-bold ph-eye"></i></button>
                </div>
              </fieldset>
              <button type="button" class="auth-forgot-link" id="btnLupaPassword">Lupa password?</button>
            </div>
          </div>
          <div class="auth-footer-section">
            <div id="pesanLogin"></div>
            <button class="auth-submit-button" type="submit">Masuk</button>
            <div class="auth-divider">
              <span class="auth-divider-line"></span>
              <span class="auth-divider-text">atau lanjutkan dengan</span>
              <span class="auth-divider-line"></span>
            </div>
            <div class="auth-social-icons">
              <button type="button" class="auth-social-icon" aria-label="Google"><img src="assets/icons/google.svg"
                  alt="Google" style="width:22px;height:22px;" /></button>
              <button type="button" class="auth-social-icon" aria-label="Apple"><img src="assets/icons/apple.svg"
                  alt="Apple" style="width:22px;height:22px;" /></button>
              <button type="button" class="auth-social-icon" aria-label="Facebook"><img src="assets/icons/facebook.svg"
                  alt="Facebook" style="width:22px;height:22px;" /></button>
            </div>
            <p class="auth-switch-text">Belum punya akun? <button type="button" class="auth-switch-link"
                id="btnSwitchKeDaftar">Daftar sekarang</button></p>
          </div>
        </form>
      </div>

      <!-- STEP DAFTAR -->
      <div class="auth-step" id="authStepDaftar1">
        <form action="/teman_singgah/auth/proses_register.php" method="POST" autocomplete="off">
          <div class="auth-header-section">
            <button type="button" class="auth-nav-button" aria-label="Kembali" data-action="ke-pilih"><i
                class="ph-bold ph-caret-left"></i></button>
            <button type="button" class="auth-nav-button" aria-label="Tutup" data-action="close-auth"><i
                class="ph-bold ph-x"></i></button>
          </div>
          <div class="auth-body-section">
            <div>
              <h2 class="auth-title">Buat akun</h2>
              <p class="auth-subtitle">Isi data di bawah untuk membuat akun baru.</p>
            </div>
            <div class="auth-fields">
              <fieldset class="auth-field">
                <legend class="auth-input-label">Nama</legend>
                <div class="auth-input-group">
                  <input type="text" name="nama" id="daftarNama" class="auth-input" placeholder="Masukkan namamu"
                    autocomplete="name" required />
                </div>
              </fieldset>
              <fieldset class="auth-field">
                <legend class="auth-input-label">Email</legend>
                <div class="auth-input-group">
                  <input type="email" name="email" id="daftarEmail" class="auth-input" placeholder="contoh@email.com"
                    autocomplete="email" required />
                </div>
              </fieldset>

              <fieldset class="auth-field">
                <legend class="auth-input-label">No. HP</legend>
                <div class="auth-input-group">
                  <input type="tel" name="no_hp" id="daftarNoHp" class="auth-input" placeholder="Masukkan nomormu"
                    autocomplete="tel" required />
                </div>
              </fieldset>

              <fieldset class="auth-field">
                <legend class="auth-input-label">Password</legend>
                <div class="auth-input-group auth-password-group">
                  <input type="password" name="password" id="daftarPassword" class="auth-input"
                    placeholder="Minimal 8 karakter" autocomplete="new-password" required />
                  <button type="button" class="auth-toggle-password" aria-label="Tampilkan password"><i
                      class="ph-bold ph-eye"></i></button>
                </div>
                <p class="auth-field-hint">Gunakan minimal 8 karakter, kombinasi huruf dan angka.</p>
              </fieldset>
            </div>
          </div>
          <div class="auth-footer-section">
            <div id="pesanDaftar"></div>
            <button class="auth-submit-button" type="submit">Buat akun</button>
            <div class="auth-divider">
              <span class="auth-divider-line"></span>
              <span class="auth-divider-text">atau lanjutkan dengan</span>
              <span class="auth-divider-line"></span>
            </div>
            <div class="auth-social-icons">
              <button type="button" class="auth-social-icon" aria-label="Google"><img src="assets/icons/google.svg"
                  alt="Google" style="width:22px;height:22px;" /></button>
              <button type="button" class="auth-social-icon" aria-label="Apple"><img src="assets/icons/apple.svg"
                  alt="Apple" style="width:22px;height:22px;" /></button>
              <button type="button" class="auth-social-icon" aria-label="Facebook"><img src="assets/icons/facebook.svg"
                  alt="Facebook" style="width:22px;height:22px;" /></button>
            </div>
            <p class="auth-switch-text">Sudah punya akun? <button type="button" class="auth-switch-link"
                id="btnSwitchKeLogin">Masuk</button></p>
          </div>
        </form>
      </div>

      <!-- STEP LUPA PASSWORD -->
      <div class="auth-step" id="authStepLupaPassword">
        <div class="auth-header-section">
          <button type="button" class="auth-nav-button" aria-label="Kembali" data-action="ke-login"><i
              class="ph-bold ph-caret-left"></i></button>
          <button type="button" class="auth-nav-button" aria-label="Tutup" data-action="close-auth"><i
              class="ph-bold ph-x"></i></button>
        </div>
        <div class="auth-body-section">
          <div>
            <h2 class="auth-title">Lupa password?</h2>
            <p class="auth-subtitle">Masukkan email akunmu untuk melanjutkan.</p>
          </div>
          <div class="auth-fields">
            <fieldset class="auth-field">
              <legend class="auth-input-label">Email</legend>
              <div class="auth-input-group">
                <input type="email" id="lupaEmail" class="auth-input" placeholder="contoh@email.com"
                  autocomplete="email" />
              </div>
            </fieldset>
          </div>
        </div>
        <div class="auth-footer-section">
          <button class="auth-submit-button" type="button" id="btnLanjutKeToken">Lanjutkan</button>
          <p class="auth-switch-text">Ingat password? <button type="button" class="auth-switch-link"
              id="btnSwitchKeLoginDariLupa">Kembali masuk</button></p>
        </div>
      </div>

      <!-- STEP PASSWORD BARU -->
      <div class="auth-step" id="authStepPasswordBaru">
        <div class="auth-header-section">
          <button type="button" class="auth-nav-button" aria-label="Kembali" data-action="ke-token"><i
              class="ph-bold ph-caret-left"></i></button>
          <button type="button" class="auth-nav-button" aria-label="Tutup" data-action="close-auth"><i
              class="ph-bold ph-x"></i></button>
        </div>
        <div class="auth-body-section">
          <div>
            <h2 class="auth-title">Password baru</h2>
            <p class="auth-subtitle">Buat password baru untuk akunmu.</p>
          </div>
          <div class="auth-fields">
            <fieldset class="auth-field">
              <legend class="auth-input-label">Password Baru</legend>
              <div class="auth-input-group auth-password-group">
                <input type="password" id="passwordBaru" class="auth-input" placeholder="Minimal 8 karakter"
                  autocomplete="new-password" />
                <button type="button" class="auth-toggle-password" aria-label="Tampilkan password"><i
                    class="ph-bold ph-eye"></i></button>
              </div>
            </fieldset>
            <fieldset class="auth-field">
              <legend class="auth-input-label">Konfirmasi Password</legend>
              <div class="auth-input-group auth-password-group">
                <input type="password" id="passwordKonfirmasi" class="auth-input" placeholder="Ulangi password baru"
                  autocomplete="new-password" />
                <button type="button" class="auth-toggle-password" aria-label="Tampilkan password"><i
                    class="ph-bold ph-eye"></i></button>
              </div>
              <p class="auth-field-hint">Gunakan minimal 8 karakter, kombinasi huruf dan angka.</p>
            </fieldset>
          </div>
        </div>
        <div class="auth-footer-section">
          <button class="auth-submit-button" type="button" id="btnSimpanPasswordBaru">Simpan password baru</button>
        </div>
      </div>

      <!-- STEP RESET SUKSES -->
      <div class="auth-step" id="authStepResetSukses">
        <div class="auth-header-section">
          <div class="empty-div"></div>
          <button type="button" class="auth-nav-button" aria-label="Tutup" data-action="close-auth"><i
              class="ph-bold ph-x"></i></button>
        </div>
        <div class="auth-body-section">
          <div class="auth-logo-container">
            <div class="auth-icon-success"><i class="ph-bold ph-check-circle"></i></div>
            <h2 class="auth-title center">Password berhasil diubah!</h2>
            <p class="auth-subtitle center">Kamu sekarang bisa masuk menggunakan password baru.</p>
          </div>
        </div>
        <div class="auth-footer-section">
          <button class="auth-submit-button" type="button" id="btnKeLoginDariSukses">Masuk sekarang</button>
        </div>
      </div>

    </div>
  </div>

</body>

</html>