<?php
require_once '../../../auth/guard_host.php';
require_once '../../../koneksi.php';

// guard_host.php sudah session_start(), ambil id dari session
$hostId = (int)($_SESSION['id'] ?? 0);

$listings = [];
$q = mysqli_query($koneksi,
    "SELECT l.id, l.judul, l.tipe_properti, l.lokasi, l.status,
            lp.nama_file AS foto_cover
     FROM listings l
     LEFT JOIN listing_photos lp
           ON lp.listing_id = l.id AND lp.adalah_cover = 1
     WHERE l.host_id = $hostId
     ORDER BY l.dibuat_pada DESC"
);
while ($row = mysqli_fetch_assoc($q)) {
    $listings[] = $row;
}

function statusInfo(string $s): array {
    return match($s) {
        'aktif'    => ['Aktif',        'success'],
        'draft'    => ['Draft',        'warning'],
        'nonaktif' => ['Nonaktif',     'error'],
        default    => ['Butuh aksi',   'error'],
    };
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Listing | Teman Singgah</title>
  <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../../components/root.css" />
  <link rel="stylesheet" href="../../../components/navbar.css" />
  <link rel="stylesheet" href="../../../components/footer.css" />
  <link rel="stylesheet" href="../../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/listing.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
</head>
<body>

<header class="navbar">
  <nav class="navbar-container">
    <a href="reservations.php" class="logo-link"></a>
    <div class="logo-section">
      <img src="../../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
      <img src="../../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
    </div>
    <ul class="nav-menu">
      <li class="nav-item"><a href="reservations.php" class="nav-link">Reservasi</a></li>
      <li class="nav-item"><a href="calendar_router.php" class="nav-link">Kalender</a></li>
      <li class="nav-item"><a href="listing.php" class="nav-link active">Listing</a></li>
      <li class="nav-item"><a href="messages.php" class="nav-link">Pesan</a></li>
      <div class="nav-indicator"></div>
    </ul>
    <?php include '/teman_singgah/components/navbar_profile_host.php'; ?>
  </nav>
</header>

<main class="main-content">
  <section class="content-header">
    <h2 class="content-title">Listing Saya</h2>
    <div class="header-buttons">
      <button class="header-button list-button" title="Tampilan list">
        <i class="ph-bold ph-rows"></i>
      </button>
      <a href="../../../host/onboarding/pages/about_place.html"
         class="header-button" title="Tambah listing">
        <i class="ph-bold ph-plus"></i>
      </a>
    </div>
  </section>

  <!-- LIST VIEW -->
  <section class="list-section list-view">
    <?php if (empty($listings)): ?>
      <div style="text-align:center;padding:64px 24px;color:var(--color-text-secondary);">
        <i class="ph-bold ph-house-simple" style="font-size:2.5rem;margin-bottom:12px;display:block;"></i>
        <p style="font-size:1rem;margin-bottom:20px;">Belum ada listing. Yuk tambahkan properti pertamamu!</p>
        <a href="../../../host/onboarding/pages/about_place.html"
           style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;
                  background:var(--color-primary);color:#fff;border-radius:var(--radius-full);
                  font-weight:600;font-size:0.875rem;text-decoration:none;">
          <i class="ph-bold ph-plus"></i> Tambah Listing
        </a>
      </div>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Listing</th>
          <th>Tipe</th>
          <th>Lokasi</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($listings as $l):
          $foto = !empty($l['foto_cover'])
              ? '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($l['foto_cover'])
              : '';
          [$label, $dot] = statusInfo($l['status'] ?? '');
          // Arahkan ke halaman edit onboarding dengan id listing
          $editHref = '../../../host/onboarding/pages/about_place.html?id=' . (int)$l['id'];
        ?>
        <tr class="listing-row" data-href="<?= $editHref ?>">
          <td>
            <div class="listing-cell">
              <?php if ($foto): ?>
                <img src="<?= $foto ?>" class="listing-thumbnail" alt="" />
              <?php else: ?>
                <div class="listing-thumbnail"></div>
              <?php endif; ?>
              <h3 class="listing-name"><?= htmlspecialchars($l['judul']) ?></h3>
            </div>
          </td>
          <td><?= htmlspecialchars(ucfirst($l['tipe_properti'] ?? '-')) ?></td>
          <td><?= htmlspecialchars($l['lokasi'] ?? '-') ?></td>
          <td>
            <div class="status-cell">
              <span class="status-dot <?= $dot ?>"></span>
              <?= $label ?>
            </div>
          </td>
          <td class="chevron-cell">
            <i class="ph-bold ph-caret-right"></i>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </section>

  <!-- GRID VIEW -->
  <section class="list-section grid-view" style="display:none">
    <?php if (empty($listings)): ?>
      <div style="text-align:center;padding:64px 24px;color:var(--color-text-secondary);">
        <i class="ph-bold ph-house-simple" style="font-size:2.5rem;margin-bottom:12px;display:block;"></i>
        <p>Belum ada listing.</p>
      </div>
    <?php else: ?>
      <?php foreach ($listings as $l):
        $foto = !empty($l['foto_cover'])
            ? '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($l['foto_cover'])
            : '';
        [$label, $dot] = statusInfo($l['status'] ?? '');
        $editHref = '../../../host/onboarding/pages/about_place.html?id=' . (int)$l['id'];
      ?>
      <a href="<?= $editHref ?>" class="listing-card">
        <div class="listing-card-image-container">
          <?php if ($foto): ?>
            <img src="<?= $foto ?>" class="listing-card-image"
                 alt="<?= htmlspecialchars($l['judul']) ?>" />
          <?php else: ?>
            <div class="listing-card-placeholder">
              <i class="ph-bold ph-house-simple"></i>
            </div>
          <?php endif; ?>
          <div class="listing-card-badge">
            <span class="badge-dot <?= $dot ?>"></span>
            <?= $label ?>
          </div>
        </div>
        <div class="listing-card-body">
          <span class="listing-card-name"><?= htmlspecialchars($l['judul']) ?></span>
          <span class="listing-card-location">
            <?= htmlspecialchars(ucfirst($l['tipe_properti'] ?? '-')) ?>
            di <?= htmlspecialchars($l['lokasi'] ?? '-') ?>
          </span>
        </div>
      </a>
      <?php endforeach; ?>
    <?php endif; ?>
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
      <h3 class="footer-title">Navigasi</h3>
      <ul class="footer-links">
        <li><a href="../../../index.php" class="footer-link">Beranda</a></li>
        <li><a href="../../../user/pages/promo_deals.html" class="footer-link">Promo & Deals</a></li>
        <li><a href="../../../user/pages/become_host.html" class="footer-link">Jadi Host</a></li>
        <li><a href="../../../user/pages/about_us.html" class="footer-link">Tentang Kami</a></li>
        <li><a href="../../../user/pages/account.php" class="footer-link">Akun</a></li>
      </ul>
    </div>
    <div class="footer-column">
      <h3 class="footer-title">Dukungan</h3>
      <ul class="footer-links">
        <li><a href="#" class="footer-link">Pusat Bantuan</a></li>
        <li><a href="#" class="footer-link">FAQ</a></li>
        <li><a href="../../../user/pages/become_host.html" class="footer-link">Cara Menjadi Host</a></li>
        <li><a href="#" class="footer-link">Cara Booking</a></li>
        <li><a href="../../../user/pages/about_us.html" class="footer-link">Tentang Kami</a></li>
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

<script src="../scripts/listing.js"></script>
<script src="../../../components/navbar.js"></script>
<script src="../../../popups/auth.js"></script>
</body>
</html>