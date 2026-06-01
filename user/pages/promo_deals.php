<?php include '../../auth/auth_session.php'; ?>
<?php require_once '../../koneksi.php'; ?>
<?php
$global_promos = $koneksi->query("
    SELECT * FROM promo_codes
    WHERE status = 'aktif' AND berlaku_dari <= CURDATE() AND berlaku_hingga >= CURDATE()
    ORDER BY diskon_persen DESC
")->fetch_all(MYSQLI_ASSOC);

$listing_rows = $koneksi->query("
    SELECT l.id, l.judul, l.lokasi, l.harga_malam, l.diskon_mingguan, l.diskon_bulanan,
        (SELECT COALESCE(lp.foto_url, lp.nama_file)
         FROM listing_photos lp
         WHERE lp.listing_id = l.id
         ORDER BY lp.adalah_cover DESC, lp.urutan ASC LIMIT 1) AS foto,
        (SELECT ROUND(AVG(r.rating),1) FROM reviews r WHERE r.listing_id = l.id) AS rating,
        (SELECT COUNT(*) FROM reviews r WHERE r.listing_id = l.id) AS jml_ulasan,
        (SELECT COUNT(*) FROM listing_discounts ld WHERE ld.listing_id = l.id AND ld.tipe = 'tamu_baru') AS ada_tamu_baru
    FROM listings l
    WHERE l.status = 'aktif'
      AND (l.diskon_mingguan > 0 OR l.diskon_bulanan > 0
           OR EXISTS (SELECT 1 FROM listing_discounts ld WHERE ld.listing_id = l.id AND ld.tipe = 'tamu_baru'))
    ORDER BY GREATEST(
        COALESCE(l.diskon_mingguan,0), COALESCE(l.diskon_bulanan,0),
        IF(EXISTS(SELECT 1 FROM listing_discounts ld2 WHERE ld2.listing_id = l.id AND ld2.tipe='tamu_baru'),20,0)
    ) DESC LIMIT 8
")->fetch_all(MYSQLI_ASSOC);

function diskon_badges(array $row): array
{
  $b = [];
  if (!empty($row['ada_tamu_baru']))
    $b[] = ['label' => 'Tamu Baru', 'pct' => 20, 'color' => 'orange'];
  if (($row['diskon_mingguan'] ?? 0) > 0)
    $b[] = ['label' => 'Mingguan', 'pct' => (int) $row['diskon_mingguan'], 'color' => 'blue'];
  if (($row['diskon_bulanan'] ?? 0) > 0)
    $b[] = ['label' => 'Bulanan', 'pct' => (int) $row['diskon_bulanan'], 'color' => 'green'];
  return $b;
}
foreach ($listing_rows as &$r) {
  $d = [];
  if (!empty($r['ada_tamu_baru']))
    $d[] = 20;
  if (($r['diskon_mingguan'] ?? 0) > 0)
    $d[] = (int) $r['diskon_mingguan'];
  if (($r['diskon_bulanan'] ?? 0) > 0)
    $d[] = (int) $r['diskon_bulanan'];
  $r['diskon_terbesar'] = !empty($d) ? max($d) : 0;
}
unset($r);

$featured_db = null;
if (!empty($listing_rows)) {
  usort($listing_rows, fn($a, $b) => $b['diskon_terbesar'] - $a['diskon_terbesar']);
  $featured_db = $listing_rows[0];
}
function rupiah(float $n): string
{
  return 'Rp ' . number_format($n, 0, ',', '.');
}
function foto_url(?string $f): string
{
  if (!$f)
    return '/teman_singgah/assets/images/apurva_kempinski_bali.jpg';
  if (str_starts_with($f, 'http'))
    return $f;
  return '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($f);
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Promo & Deals | Teman Singgah</title>
  <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="/teman_singgah/components/root.css" />
  <link rel="stylesheet" href="/teman_singgah/components/navbar.css" />
  <link rel="stylesheet" href="/teman_singgah/components/footer.css" />
  <link rel="stylesheet" href="/teman_singgah/popups/auth.css" />
  <link rel="stylesheet" href="/teman_singgah/user/styles/promo_deals.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
  <style>
    .listing-badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 11px;
      font-weight: 700;
      padding: 3px 8px;
      border-radius: 99px
    }

    .listing-badge.orange {
      background: #fff3e0;
      color: #e65100
    }

    .listing-badge.blue {
      background: #e3f2fd;
      color: #1565c0
    }

    .listing-badge.green {
      background: #e8f5e9;
      color: #2e7d32
    }

    .badge-stack {
      display: flex;
      flex-wrap: wrap;
      gap: 4px;
      margin-top: 6px
    }

    .empty-state {
      text-align: center;
      padding: 40px 20px;
      color: #aaa;
      font-size: 14px
    }

    .empty-state i {
      font-size: 2.5rem;
      display: block;
      margin-bottom: 10px
    }
  </style>
</head>

<body>
  <header class="navbar">
    <nav class="navbar-container">
      <a href="/teman_singgah/index.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="/teman_singgah/assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
        <img src="/teman_singgah/assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
      </div>
      <ul class="nav-menu">
        <li class="nav-item"><a href="/teman_singgah/index.php" class="nav-link">Cari Penginapan</a></li>
        <li class="nav-item"><a href="/teman_singgah/user/pages/promo_deals.php" class="nav-link active">Promo &
            Deals</a></li>
        <li class="nav-item"><a href="/teman_singgah/user/pages/become_host.php" class="nav-link">Jadi Host</a></li>
        <li class="nav-item"><a href="/teman_singgah/user/pages/about_us.php" class="nav-link">Tentang Kami</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <div class="nav-right">
        <a href="/teman_singgah/host/onboarding/pages/about_place.html">
          <button class="ghost-button">Ganti ke host</button>
        </a>
        <?php include '../../components/navbar_profile.php'; ?>
      </div>
    </nav>
  </header>

  <main class="main-content">

    <section class="hero-section">
      <div class="hero-container">
        <div class="promo-badge"><i class="ph-fill ph-fire"></i><span>Penawaran Aktif</span></div>
        <h1 class="hero-title">Promo & Deals Eksklusif</h1>
        <p class="hero-description">Hemat hingga 70% untuk penginapan pilihan di seluruh Indonesia. Penawaran terbatas,
          jangan sampai terlewat!</p>
      </div>
    </section>

    <section class="featured-section">
      <div class="featured-container">
        <div class="featured-content">
          <?php if ($featured_db):
            $fbadges = diskon_badges($featured_db); ?>
            <span class="featured-label"><i class="ph-fill ph-crown"></i> Diskon Terbesar Saat Ini</span>
            <h2 class="featured-title"><?= htmlspecialchars($featured_db['judul']) ?></h2>
            <p class="featured-description">
              <?= htmlspecialchars($featured_db['lokasi'] ?? '') ?>
              <?php if (!empty($fbadges)): ?>
                — diskon <?= implode(', ', array_map(fn($b) => $b['pct'] . '% (' . $b['label'] . ')', $fbadges)) ?>
              <?php endif; ?>
            </p>
            <div class="featured-stats">
              <div class="stat-item"><span class="stat-value"><?= $featured_db['diskon_terbesar'] ?>%</span><span
                  class="stat-label">Hemat</span></div>
              <div class="stat-item"><span
                  class="stat-value"><?= rupiah((float) $featured_db['harga_malam']) ?></span><span class="stat-label">/
                  malam</span></div>
              <?php if ($featured_db['rating']): ?>
                <div class="stat-item"><span class="stat-value"><?= $featured_db['rating'] ?></span><span
                    class="stat-label">Rating</span></div>
              <?php endif; ?>
            </div>
            <a href="/teman_singgah/user/pages/detail_card.php?id=<?= $featured_db['id'] ?>">
              <button class="featured-button">Lihat Penginapan</button>
            </a>
          <?php else: ?>
            <span class="featured-label"><i class="ph-fill ph-crown"></i> Deal of the Month</span>
            <h2 class="featured-title">Paket Liburan Keluarga ke Bali</h2>
            <p class="featured-description">Nikmati pengalaman menginap 3 malam di resort bintang 5 dengan harga spesial.
              Termasuk sarapan dan voucher spa.</p>
            <div class="featured-stats">
              <div class="stat-item"><span class="stat-value">60%</span><span class="stat-label">Hemat</span></div>
              <div class="stat-item"><span class="stat-value">3 Malam</span><span class="stat-label">Menginap</span></div>
              <div class="stat-item"><span class="stat-value">4.9</span><span class="stat-label">Rating</span></div>
            </div>
            <button class="featured-button">Klaim Sekarang</button>
          <?php endif; ?>
        </div>
        <div class="featured-image">
          <?php if ($featured_db && $featured_db['foto']): ?>
            <img src="<?= foto_url($featured_db['foto']) ?>" alt="<?= htmlspecialchars($featured_db['judul']) ?>" />
          <?php else: ?>
            <img src="/teman_singgah/assets/images/padma_resort_ubud_bali.jpg" alt="Bali Family Package" />
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- KODE VOCER -->
    <section class="voucher-section">
      <h2 class="section-title">Kode Vocer</h2>
      <div class="voucher-grid">
        <?php if (empty($global_promos)): ?>
          <div class="empty-state" style="grid-column:1/-1"><i class="ph-bold ph-tag"></i>Belum ada kode promo aktif saat
            ini.</div>
        <?php else: ?>
          <?php foreach ($global_promos as $gp):
            $tgl = date('d M Y', strtotime($gp['berlaku_hingga']));
            $sisa = $gp['maks_pakai'] ? ($gp['maks_pakai'] - $gp['sudah_dipakai']) : null;
            ?>
            <div class="voucher-card">
              <div class="voucher-header">
                <span class="voucher-code"><?= htmlspecialchars($gp['kode']) ?></span>
                <span class="voucher-discount"><?= $gp['diskon_persen'] ?>%</span>
              </div>
              <p class="voucher-title"><?= htmlspecialchars($gp['judul']) ?></p>
              <?php if ($gp['deskripsi']): ?>
                <p style="font-size:12px;color:#888;margin:2px 0 6px"><?= htmlspecialchars($gp['deskripsi']) ?></p>
              <?php endif; ?>
              <div class="voucher-validity"><i class="ph-bold ph-clock"></i> Berlaku hingga <?= $tgl ?></div>
              <?php if ($sisa !== null): ?>
                <div style="font-size:11px;color:#aaa;margin-top:3px">Sisa kuota: <?= $sisa ?></div><?php endif; ?>
              <?php if ($gp['min_malam'] > 1): ?>
                <div style="font-size:11px;color:#aaa;margin-top:2px">Min. <?= $gp['min_malam'] ?> malam</div><?php endif; ?>
              <button class="copy-button" data-code="<?= htmlspecialchars($gp['kode']) ?>"><i
                  class="ph-bold ph-copy"></i></button>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </section>

    <!-- PENAWARAN TERBATAS -->
    <section class="card-section">
      <h2 class="section-title">Penawaran Terbatas</h2>
      <div class="promo-grid">
        <?php if (empty($listing_rows)): ?>
          <div class="empty-state" style="grid-column:1/-1"><i class="ph-bold ph-house"></i>Belum ada penginapan berpromo
            saat ini.</div>
        <?php else: ?>
          <?php foreach ($listing_rows as $l):
            $badges = diskon_badges($l);
            $top = $l['diskon_terbesar'];
            $harga_asli = (float) $l['harga_malam'];
            $harga_disc = round($harga_asli * (1 - $top / 100));
            ?>
            <a href="/teman_singgah/user/pages/detail_card.php?id=<?= $l['id'] ?>" class="promo-card">
              <div class="card-image-wrapper">
                <span class="discount-tag">-<?= $top ?>%</span>
                <img src="<?= foto_url($l['foto']) ?>" alt="<?= htmlspecialchars($l['judul']) ?>" class="card-image" />
                <img src="/teman_singgah/assets/icons/save.svg" alt="wishlist" class="save-button" />
              </div>
              <div class="promo-content">
                <h3 class="promo-title"><?= htmlspecialchars($l['judul']) ?></h3>
                <div class="badge-stack">
                  <?php foreach ($badges as $b): ?>
                    <span class="listing-badge <?= $b['color'] ?>"><?= $b['pct'] ?>% <?= $b['label'] ?></span>
                  <?php endforeach; ?>
                </div>
                <div class="promo-amenities" style="margin-top:8px">
                  <span class="amenity-item"><i
                      class="ph-bold ph-map-pin"></i><?= htmlspecialchars($l['lokasi'] ?? '—') ?></span>
                  <?php if ($l['rating']): ?>
                    <span class="amenity-item">
                      <i class="ph-fill ph-star" style="color:#f59e0b"></i>
                      <?= $l['rating'] ?> <span style="color:#aaa">(<?= $l['jml_ulasan'] ?>)</span>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="promo-price">
                  <span class="original-price"><?= rupiah($harga_asli) ?></span>
                  <span class="current-price"><?= rupiah($harga_disc) ?></span>
                  <span class="price-unit">/ malam</span>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        <?php endif; ?>
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
          <li><a href="/teman_singgah/index.php" class="footer-link">Beranda</a></li>
          <li><a href="/teman_singgah/user/pages/promo_deals.php" class="footer-link">Promo & Deals</a></li>
          <li><a href="/teman_singgah/user/pages/become_host.php" class="footer-link">Jadi Host</a></li>
          <li><a href="/teman_singgah/user/pages/about_us.php" class="footer-link">Tentang Kami</a></li>
          <li><a href="/teman_singgah/user/pages/account.php" class="footer-link">Akun</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3 class="footer-title">Dukungan</h3>
        <ul class="footer-links">
          <li><a href="#" class="footer-link">Pusat Bantuan</a></li>
          <li><a href="#" class="footer-link">FAQ</a></li>
          <li><a href="/teman_singgah/user/pages/become_host.php" class="footer-link">Cara Menjadi Host</a></li>
          <li><a href="#" class="footer-link">Cara Booking</a></li>
          <li><a href="/teman_singgah/user/pages/about_us.php" class="footer-link">Tentang Kami</a></li>
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

  <?php include '../../popups/auth_overlay.php'; ?>
  <script src="/teman_singgah/user/scripts/promo_deals.js"></script>
  <script src="/teman_singgah/components/navbar.js"></script>
  <script src="/teman_singgah/popups/auth.js"></script>
  <script>
    document.querySelectorAll('.copy-button[data-code]').forEach(btn => {
      btn.addEventListener('click', e => {
        e.preventDefault();
        navigator.clipboard.writeText(btn.dataset.code).then(() => {
          const orig = btn.innerHTML;
          btn.innerHTML = '<i class="ph-bold ph-check"></i>';
          btn.style.color = '#16a34a';
          setTimeout(() => { btn.innerHTML = orig; btn.style.color = ''; }, 1800);
        });
      });
    });
  </script>
</body>

</html>