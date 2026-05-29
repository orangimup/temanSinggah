<?php
session_start();
include "../../koneksi.php";

$isLoggedIn  = isset($_SESSION['nama']);
$userInitial = $isLoggedIn ? strtoupper(mb_substr($_SESSION['nama'], 0, 1)) : '';
$userName    = $isLoggedIn ? $_SESSION['nama'] : '';
$userPhoto   = '';
if (!empty($_SESSION['photo']) && file_exists("../../assets/uploads/photos/" . $_SESSION['photo'])) {
  $userPhoto = "/teman_singgah/assets/uploads/photos/" . htmlspecialchars($_SESSION['photo']);
}

$keyword   = trim($_GET['q']        ?? '');
$checkin   = trim($_GET['checkin']  ?? '');
$checkout  = trim($_GET['checkout'] ?? '');
$tamu      = (int)($_GET['tamu']      ?? 0);
$harga_min = (int)($_GET['harga_min'] ?? 0);
$harga_max = (int)($_GET['harga_max'] ?? 0);
$sort      = $_GET['sort'] ?? 'relevan';

$where  = ["l.status = 'aktif'"];
$params = [];
$types  = '';

if ($keyword !== '') {
  $where[]  = "(l.judul LIKE ? OR l.lokasi LIKE ? OR l.deskripsi LIKE ?)";
  $like     = "%{$keyword}%";
  $params[] = $like; $params[] = $like; $params[] = $like;
  $types   .= 'sss';
}
if ($harga_min > 0) { $where[] = "l.harga_malam >= ?"; $params[] = $harga_min; $types .= 'i'; }
if ($harga_max > 0) { $where[] = "l.harga_malam <= ?"; $params[] = $harga_max; $types .= 'i'; }
if ($tamu > 0)      { $where[] = "l.max_tamu >= ?";    $params[] = $tamu;      $types .= 'i'; }
if ($checkin !== '' && $checkout !== '') {
  $where[]  = "l.id NOT IN (
      SELECT b.listing_id FROM bookings b
      WHERE b.status NOT IN ('dibatalkan','ditolak')
      AND NOT (b.checkout <= ? OR b.checkin >= ?))";
  $params[] = $checkin; $params[] = $checkout;
  $types   .= 'ss';
}

$where_sql = implode(' AND ', $where);
$order_sql = match ($sort) {
  'harga_asc'  => 'l.harga_malam ASC',
  'harga_desc' => 'l.harga_malam DESC',
  'rating'     => 'rating_avg DESC',
  default      => 'l.dibuat_pada DESC',
};

$sql = "
    SELECT
        l.id, l.judul, l.lokasi, l.harga_malam, l.max_tamu AS kapasitas,
        l.latitude, l.longitude,
        lp.nama_file AS foto_cover,
        ROUND(AVG(r.rating), 1) AS rating_avg,
        COUNT(DISTINCT r.id)   AS jumlah_review
    FROM listings l
    LEFT JOIN listing_photos lp ON lp.listing_id = l.id AND lp.adalah_cover = 1
    LEFT JOIN reviews r ON r.listing_id = l.id
    WHERE {$where_sql}
    GROUP BY l.id, l.judul, l.lokasi, l.harga_malam, l.max_tamu,
             l.latitude, l.longitude, lp.nama_file
    ORDER BY {$order_sql}
    LIMIT 200
";

$listings = [];
$stmt = mysqli_prepare($koneksi, $sql);
if ($stmt) {
  if ($types) mysqli_stmt_bind_param($stmt, $types, ...$params);
  mysqli_stmt_execute($stmt);
  $res = mysqli_stmt_get_result($stmt);
  while ($row = mysqli_fetch_assoc($res)) $listings[] = $row;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Hasil Pencarian<?= $keyword ? ' · ' . htmlspecialchars($keyword) : '' ?> | Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../../components/navbar.css" />
  <link rel="stylesheet" href="../../components/footer.css" />
  <link rel="stylesheet" href="../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/search_result.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
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
        <li class="nav-item"><a href="../../index.php" class="nav-link active">Cari Penginapan</a></li>
        <li class="nav-item"><a href="promo_deals.php" class="nav-link">Promo & Deals</a></li>
        <li class="nav-item"><a href="become_host.php" class="nav-link">Jadi Host</a></li>
        <li class="nav-item"><a href="about_us.php" class="nav-link">Tentang Kami</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <div class="nav-right">
        <a href="../../host/onboarding/pages/about_place.html">
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
            <button class="icon-button profile hidden" aria-label="Profile"><?= htmlspecialchars($userInitial) ?></button>
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

    <form method="GET" action="" id="filterForm">
      <div class="filter-bar">

        <div class="filter-segment" id="segDest" style="flex:1.3" onclick="filterToggleDD('ddDest',event)">
          <span class="filter-segment-label">Destinasi</span>
          <input type="text" name="q" id="filterDestInput" class="filter-segment-input"
            placeholder="Kota atau penginapan" value="<?= htmlspecialchars($keyword) ?>" autocomplete="off" />
          <div class="filter-dropdown" id="ddDest" onclick="event.stopPropagation()">
            <p class="filter-dropdown-title">Populer</p>
            <div class="filter-dest-item" onclick="filterPickDest('Bali')"><i class="ph-bold ph-map-pin"></i>Bali</div>
            <div class="filter-dest-item" onclick="filterPickDest('Bandung')"><i class="ph-bold ph-map-pin"></i>Bandung</div>
            <div class="filter-dest-item" onclick="filterPickDest('Yogyakarta')"><i class="ph-bold ph-map-pin"></i>Yogyakarta</div>
            <div class="filter-dest-item" onclick="filterPickDest('Jakarta')"><i class="ph-bold ph-map-pin"></i>Jakarta</div>
            <div class="filter-dest-item" onclick="filterPickDest('Lombok')"><i class="ph-bold ph-map-pin"></i>Lombok</div>
          </div>
        </div>

        <div class="filter-divider"></div>

        <div class="filter-segment" id="segPrice" onclick="filterToggleDD('ddPrice',event)" style="flex:1.2">
          <span class="filter-segment-label">Harga / malam</span>
          <span class="filter-segment-value muted" id="filterPriceVal">Semua harga</span>
          <input type="hidden" name="harga_min" id="filterHargaMin" value="<?= $harga_min ?: '' ?>" />
          <input type="hidden" name="harga_max" id="filterHargaMax" value="<?= $harga_max ?: '' ?>" />
          <div class="filter-dropdown" id="ddPrice" onclick="event.stopPropagation()">
            <p class="filter-dropdown-title">Pilih rentang</p>
            <div class="filter-preset-grid">
              <div class="filter-preset-chip" data-min="0" data-max="300000" onclick="filterPickPreset(this)">
                <p class="prange">s/d Rp 300rb</p><p class="ptag">Budget</p>
              </div>
              <div class="filter-preset-chip" data-min="300000" data-max="700000" onclick="filterPickPreset(this)">
                <p class="prange">300rb – 700rb</p><p class="ptag">Standar</p>
              </div>
              <div class="filter-preset-chip" data-min="700000" data-max="1500000" onclick="filterPickPreset(this)">
                <p class="prange">700rb – 1,5jt</p><p class="ptag">Premium</p>
              </div>
              <div class="filter-preset-chip" data-min="1500000" data-max="0" onclick="filterPickPreset(this)">
                <p class="prange">Rp 1,5jt+</p><p class="ptag">Mewah</p>
              </div>
            </div>
            <div class="filter-price-custom">
              <input type="number" id="filterPMin" placeholder="Min" step="50000"
                value="<?= $harga_min ?: '' ?>" oninput="filterSyncPrice()" />
              <span class="filter-price-dash">—</span>
              <input type="number" id="filterPMax" placeholder="Max" step="50000"
                value="<?= $harga_max ?: '' ?>" oninput="filterSyncPrice()" />
            </div>
          </div>
        </div>

        <div class="filter-divider"></div>

        <div class="filter-segment" id="segSort" onclick="filterToggleDD('ddSort',event)" style="flex:1">
          <span class="filter-segment-label">Urutkan</span>
          <span class="filter-segment-value" id="filterSortVal">
            <?= match($sort) {
              'rating'     => 'Rating tertinggi',
              'harga_asc'  => 'Harga terendah',
              'harga_desc' => 'Harga tertinggi',
              default      => 'Relevan'
            } ?>
          </span>
          <input type="hidden" name="sort" id="filterSortInput" value="<?= htmlspecialchars($sort) ?>" />
          <div class="filter-dropdown" id="ddSort" style="min-width:200px;left:auto;right:0;" onclick="event.stopPropagation()">
            <p class="filter-dropdown-title">Tampilkan</p>
            <div class="filter-sort-item <?= $sort==='relevan'?'on':'' ?>" onclick="filterPickSort('relevan','Relevan',this)">
              <i class="ph-bold ph-list"></i>Relevan</div>
            <div class="filter-sort-item <?= $sort==='rating'?'on':'' ?>" onclick="filterPickSort('rating','Rating tertinggi',this)">
              <i class="ph-bold ph-star"></i>Rating tertinggi</div>
            <div class="filter-sort-item <?= $sort==='harga_asc'?'on':'' ?>" onclick="filterPickSort('harga_asc','Harga terendah',this)">
              <i class="ph-bold ph-sort-ascending"></i>Harga terendah</div>
            <div class="filter-sort-item <?= $sort==='harga_desc'?'on':'' ?>" onclick="filterPickSort('harga_desc','Harga tertinggi',this)">
              <i class="ph-bold ph-sort-descending"></i>Harga tertinggi</div>
          </div>
        </div>

        <button type="submit" class="filter-search-btn" aria-label="Cari penginapan">
          <i class="ph-bold ph-magnifying-glass"></i>
        </button>

        <?php if ($keyword || $harga_min || $harga_max || $tamu): ?>
          <a href="search_result.php" class="filter-reset-btn" title="Reset filter">
            <i class="ph-bold ph-x"></i>
          </a>
        <?php endif; ?>

      </div>
    </form>

    <div class="content-layout">

      <section class="card-section">
        <div class="card-section-container">
          <?php if (empty($listings)): ?>
            <div class="empty-state">
              <h3>Tidak ada penginapan ditemukan</h3>
              <p>Coba ubah kata kunci atau filter pencarian.</p>
            </div>
          <?php else: ?>
            <?php foreach ($listings as $row):
              $foto   = !empty($row['foto_cover'])
                ? (str_starts_with($row['foto_cover'], 'http')
                    ? $row['foto_cover']
                    : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($row['foto_cover']))
                : '/teman_singgah/assets/images/apurva_kempinski_bali.jpg';
              $harga  = 'Rp ' . number_format($row['harga_malam'], 0, ',', '.');
              $rating = isset($row['rating_avg']) && $row['rating_avg'] !== null ? $row['rating_avg'] : '–';
              $judul  = htmlspecialchars($row['judul']);
              $parts  = array_map('trim', explode(',', $row['lokasi']));
              $lokasi = htmlspecialchars(implode(', ', array_slice($parts, 0, 2)));
              $id     = (int)$row['id'];
              $ulasan = (int)$row['jumlah_review'];
            ?>
              <a href="detail_card.php?id=<?= $id ?>" class="hotel-card" data-id="<?= $id ?>">
                <div class="card-image-container">
                  <img src="<?= $foto ?>" alt="<?= $judul ?>" class="card-image" />
                  <img src="../../assets/icons/save.svg" alt="wishlist" class="save-button" />
                </div>
                <div class="card-content">
                  <div class="card-top">
                    <h3 class="card-title"><?= $judul ?></h3>
                    <span class="card-location-text"><i class="ph-bold ph-map-pin"></i><?= $lokasi ?></span>
                  </div>
                  <div class="card-bottom">
                    <div class="price-section">
                      <span class="card-price"><?= $harga ?></span>
                      <span class="price-unit">/ malam</span>
                    </div>
                    <span class="card-rating">
                      <i class="ph-fill ph-star"></i><?= $rating ?>
                      <?php if ($ulasan > 0): ?>
                        <span style="font-size:12px;color:#9ca3af;">(<?= $ulasan ?>)</span>
                      <?php endif; ?>
                    </span>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <div class="control-buttons">
          <button class="control-button prev" id="paginatePrev">
            <i class="ph-bold ph-caret-left"></i>
          </button>
          <div class="pagination-numbers" id="paginationNumbers"></div>
          <button class="control-button next" id="paginateNext">
            <i class="ph-bold ph-caret-right"></i>
          </button>
        </div>
      </section>

      <section class="map-section">
        <div id="propertyMap" class="map-container">
          <?php foreach ($listings as $row):
            $fotoMap = !empty($row['foto_cover'])
              ? (str_starts_with($row['foto_cover'], 'http')
                  ? $row['foto_cover']
                  : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($row['foto_cover']))
              : '/teman_singgah/assets/images/apurva_kempinski_bali.jpg';
            $hargaMap  = 'Rp ' . number_format($row['harga_malam'], 0, ',', '.');
            $ratingMap = $row['rating_avg'] ?: '–';
            $judulMap  = htmlspecialchars($row['judul']);
            $parts     = array_map('trim', explode(',', $row['lokasi']));
            $lokasiMap = htmlspecialchars(array_shift($parts) ?? '');
            $idMap     = (int)$row['id'];
            if (empty($row['latitude']) || empty($row['longitude'])) continue;
          ?>
            <a href="detail_card.php?id=<?= $idMap ?>" class="map-property-card" data-marker-id="<?= $idMap ?>">
              <div class="map-card-image-wrap">
                <img src="<?= $fotoMap ?>" alt="<?= $judulMap ?>" class="map-card-image" />
                <button class="map-card-button wishlist" aria-label="Simpan">
                  <img src="../../assets/icons/save.svg" alt="wishlist" />
                </button>
                <button class="map-card-button close map-card-close" aria-label="Tutup" data-marker-id="<?= $idMap ?>">
                  <i class="ph-bold ph-x"></i>
                </button>
              </div>
              <div class="map-card-body">
                <p class="map-card-type"><?= $lokasiMap ?></p>
                <p class="map-card-name"><?= $judulMap ?></p>
                <span class="map-card-body-bottom">
                  <p class="map-card-price"><strong><?= $hargaMap ?></strong> / malam</p>
                  <p class="map-card-rating"><strong><i class="ph-fill ph-star"></i></strong> <?= $ratingMap ?></p>
                </span>
              </div>
            </a>
          <?php endforeach; ?>

          <div class="custom-zoom-control">
            <button class="custom-zoom-button" id="zoomIn"><i class="ph-bold ph-plus"></i></button>
            <button class="custom-zoom-button" id="zoomOut"><i class="ph-bold ph-minus"></i></button>
          </div>
        </div>
      </section>

    </div>
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
          <li><a href="promo_deals.php" class="footer-link">Promo & Deals</a></li>
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
        <a href="" class="footer-link bottom">Syarat & Ketentuan</a>
      </div>
    </div>
  </footer>

  <div id="authOverlay" class="auth-overlay">
    <div class="auth-form-card">
      <div class="auth-step active" id="authStepPilih">
        <div class="auth-header-section">
          <div class="empty-div"></div>
          <button type="button" class="auth-nav-button" aria-label="Tutup" data-action="close-auth"><i class="ph-bold ph-x"></i></button>
        </div>
        <div class="auth-body-section">
          <div class="auth-logo-container">
            <div class="auth-logo"><img class="auth-logo-image" src="../../assets/logo/logo_temansinggah.svg" alt="Teman Singgah" /></div>
            <h2 class="auth-title center">Selamat datang</h2>
            <p class="auth-subtitle center">Masuk atau buat akun baru untuk mulai memesan.</p>
          </div>
          <div class="auth-fields">
            <button class="auth-submit-button" type="button" id="btnKeLogin">Masuk</button>
            <button class="auth-submit-button outline" type="button" id="btnKeDaftar">Daftar akun baru</button>
          </div>
        </div>
      </div>
      <div class="auth-step" id="authStepLogin">
        <form action="/teman_singgah/auth/proses_login.php" method="POST" autocomplete="off">
          <div class="auth-header-section">
            <button type="button" class="auth-nav-button" data-action="ke-pilih"><i class="ph-bold ph-caret-left"></i></button>
            <button type="button" class="auth-nav-button" data-action="close-auth"><i class="ph-bold ph-x"></i></button>
          </div>
          <div class="auth-body-section">
            <div><h2 class="auth-title">Masuk</h2><p class="auth-subtitle">Masukkan email dan password kamu.</p></div>
            <div class="auth-fields">
              <fieldset class="auth-field"><legend class="auth-input-label">Email</legend>
                <div class="auth-input-group"><input type="email" name="email" class="auth-input" placeholder="contoh@email.com" required /></div>
              </fieldset>
              <fieldset class="auth-field"><legend class="auth-input-label">Password</legend>
                <div class="auth-input-group auth-password-group">
                  <input type="password" name="password" class="auth-input" placeholder="Masukkan password" required />
                  <button type="button" class="auth-toggle-password"><i class="ph-bold ph-eye"></i></button>
                </div>
              </fieldset>
              <button type="button" class="auth-forgot-link" id="btnLupaPassword">Lupa password?</button>
            </div>
          </div>
          <div class="auth-footer-section">
            <div id="pesanLogin"></div>
            <button class="auth-submit-button" type="submit">Masuk</button>
            <p class="auth-switch-text">Belum punya akun? <button type="button" class="auth-switch-link" id="btnSwitchKeDaftar">Daftar sekarang</button></p>
          </div>
        </form>
      </div>
      <div class="auth-step" id="authStepDaftar1">
        <form action="/teman_singgah/auth/proses_register.php" method="POST" autocomplete="off">
          <div class="auth-header-section">
            <button type="button" class="auth-nav-button" data-action="ke-pilih"><i class="ph-bold ph-caret-left"></i></button>
            <button type="button" class="auth-nav-button" data-action="close-auth"><i class="ph-bold ph-x"></i></button>
          </div>
          <div class="auth-body-section">
            <div><h2 class="auth-title">Buat akun</h2><p class="auth-subtitle">Isi data di bawah untuk membuat akun baru.</p></div>
            <div class="auth-fields">
              <fieldset class="auth-field"><legend class="auth-input-label">Nama</legend>
                <div class="auth-input-group"><input type="text" name="nama" class="auth-input" placeholder="Masukkan namamu" required /></div>
              </fieldset>
              <fieldset class="auth-field"><legend class="auth-input-label">Email</legend>
                <div class="auth-input-group"><input type="email" name="email" class="auth-input" placeholder="contoh@email.com" required /></div>
              </fieldset>
              <fieldset class="auth-field"><legend class="auth-input-label">No. HP</legend>
                <div class="auth-input-group"><input type="tel" name="no_hp" class="auth-input" placeholder="Masukkan nomormu" required /></div>
              </fieldset>
              <fieldset class="auth-field"><legend class="auth-input-label">Password</legend>
                <div class="auth-input-group auth-password-group">
                  <input type="password" name="password" class="auth-input" placeholder="Minimal 8 karakter" required />
                  <button type="button" class="auth-toggle-password"><i class="ph-bold ph-eye"></i></button>
                </div>
              </fieldset>
            </div>
          </div>
          <div class="auth-footer-section">
            <div id="pesanDaftar"></div>
            <button class="auth-submit-button" type="submit">Buat akun</button>
            <p class="auth-switch-text">Sudah punya akun? <button type="button" class="auth-switch-link" id="btnSwitchKeLogin">Masuk</button></p>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    <?php if ($isLoggedIn): ?>
      localStorage.setItem('isLoggedIn',  'true');
      localStorage.setItem('userInitial', '<?= htmlspecialchars($userInitial) ?>');
      localStorage.setItem('userName',    '<?= htmlspecialchars($userName) ?>');
      localStorage.setItem('userPhoto',   '<?= $userPhoto ?>');
    <?php else: ?>
      localStorage.removeItem('isLoggedIn');
      localStorage.removeItem('userInitial');
      localStorage.removeItem('userName');
      localStorage.removeItem('userPhoto');
    <?php endif; ?>
  </script>

  <script>
    window.MAP_MARKERS = <?= json_encode(array_values(array_filter(
      array_map(function($r) {
        if (empty($r['latitude']) || empty($r['longitude'])) return null;
        return [
          'id'     => (int)$r['id'],
          'latlng' => [(float)$r['latitude'], (float)$r['longitude']],
          'price'  => 'Rp ' . number_format($r['harga_malam'], 0, ',', '.'),
        ];
      }, $listings)
    ))) ?>;
  </script>

  <script src="../scripts/search_result.js"></script>
  <script src="../../components/navbar.js"></script>
  <script src="../../popups/auth.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</body>
</html>