<?php
session_start();
include "../../koneksi.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: /teman_singgah/index.php?auth=login");
  exit;
}

$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "s", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user   = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
  session_destroy();
  header("Location: /teman_singgah/index.php?auth=login");
  exit;
}

$inisial   = strtoupper(mb_substr($user['nama'], 0, 1));
$photo_url = '';
if (!empty($user['photo'])) {
  if (str_starts_with($user['photo'], 'http')) {
    $photo_url = $user['photo'];
  } elseif (file_exists("../../assets/uploads/photos/" . $user['photo'])) {
    $photo_url = "/teman_singgah/assets/uploads/photos/" . htmlspecialchars($user['photo']);
  }
}

$filter          = $_GET['filter'] ?? 'semua';
$allowed_filters = ['semua', 'menunggu', 'berlangsung', 'mendatang', 'selesai', 'dibatalkan'];
if (!in_array($filter, $allowed_filters)) $filter = 'semua';

$where_status = '';
switch ($filter) {
  case 'menunggu':
    $where_status = "AND b.status = 'menunggu'";
    break;
  case 'berlangsung':
    $where_status = "AND b.status = 'dikonfirmasi' AND b.checkin <= CURDATE() AND b.checkout >= CURDATE()";
    break;
  case 'mendatang':
    $where_status = "AND b.status = 'dikonfirmasi' AND b.checkin > CURDATE()";
    break;
  case 'selesai':
    $where_status = "AND b.status = 'selesai'";
    break;
  case 'dibatalkan':
    $where_status = "AND b.status = 'dibatalkan'";
    break;
}

$sql = "
    SELECT
        b.id,
        b.checkin,
        b.checkout,
        b.jumlah_tamu,
        b.total_harga,
        b.status,
        b.metode_bayar,
        b.kode_promo,
        b.dibuat_pada,
        b.tipe_bayar,
        l.judul         AS nama_listing,
        l.lokasi,
        l.tipe_properti,
        r.nama          AS nama_kamar,
        r.foto          AS foto_kamar,
        (SELECT lp.nama_file
         FROM listing_photos lp
         WHERE lp.listing_id = l.id AND lp.adalah_cover = 1
         LIMIT 1)       AS foto_cover
    FROM bookings b
    JOIN listings l ON l.id = b.listing_id
    LEFT JOIN listing_rooms r ON r.id = b.room_id
    WHERE b.user_id = ?
    $where_status
    ORDER BY b.dibuat_pada DESC
";

$stmt     = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user['id']);
mysqli_stmt_execute($stmt);
$bookings = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

function get_badge(string $status, string $checkin, string $checkout): array
{
  $today    = date('Y-m-d');
  $checkin  = substr($checkin,  0, 10);
  $checkout = substr($checkout, 0, 10);

  if ($status === 'menunggu')
    return ['label' => 'Menunggu',     'class' => 'pending'];

  if ($status === 'dikonfirmasi') {
    if ($checkin <= $today && $checkout >= $today)
      return ['label' => 'Berlangsung', 'class' => 'ongoing'];
    if ($checkin > $today)
      return ['label' => 'Mendatang',   'class' => 'upcoming'];
    return   ['label' => 'Dikonfirmasi','class' => 'upcoming'];
  }

  if ($status === 'selesai')
    return ['label' => 'Selesai',    'class' => 'completed'];
  if ($status === 'dibatalkan')
    return ['label' => 'Dibatalkan', 'class' => 'cancelled'];

  return ['label' => ucfirst($status), 'class' => 'pending'];
}

function jumlah_malam(string $checkin, string $checkout): int
{
  return (new DateTime($checkin))->diff(new DateTime($checkout))->days;
}

function fmt_tanggal(string $date): string
{
  return date('d M Y', strtotime($date));
}

function fmt_harga(float $harga): string
{
  return 'Rp ' . number_format($harga, 0, ',', '.');
}

function listing_img(array $b): string
{
  $foto = $b['foto_cover'] ?? '';
  if (empty($foto)) return '';
  if (str_starts_with($foto, 'http')) return $foto;
  return "/teman_singgah/assets/uploads/listings/" . htmlspecialchars($foto);
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Riwayat Perjalanan | Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../../components/navbar.css" />
  <link rel="stylesheet" href="../../components/footer.css" />
  <link rel="stylesheet" href="../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/account.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
  <style>
    .badge-dot.pending   { background-color: #f59e0b; }
    .badge-dot.ongoing   { background-color: #16a34a; }
    .badge-dot.upcoming  { background-color: #2563eb; }
    .badge-dot.completed { background-color: #6b7280; }
    .badge-dot.cancelled { background-color: #dc2626; }
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
          <button class="icon-button hamburger" aria-label="Hamburger">
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
    <section class="account-hero">
      <div class="account-hero-container">
        <div class="account-hero-badge">
          <i class="ph-fill ph-suitcase"></i>
          <span>Perjalanan</span>
        </div>
        <h1 class="account-hero-title">Riwayat Perjalanan</h1>
        <p class="account-hero-subtitle">Lihat dan kelola semua perjalanan Anda di sini.</p>
      </div>
    </section>

    <section class="account-section">
      <div class="account-container">
        <aside class="account-sidebar">
          <h2 class="sidebar-title">Profil</h2>
          <nav class="sidebar-nav">
            <a href="./account.php" class="sidebar-link">
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
            <a href="./history.php" class="sidebar-link active">
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
            <h2 class="content-title">Perjalanan Saya</h2>
          </div>

          <div class="filter-group">
            <?php
            $filters = [
              'semua'       => 'Semua',
              'menunggu'    => 'Menunggu',
              'berlangsung' => 'Berlangsung',
              'mendatang'   => 'Mendatang',
              'selesai'     => 'Selesai',
              'dibatalkan'  => 'Dibatalkan',
            ];
            foreach ($filters as $key => $label): ?>
              <a href="?filter=<?= $key ?>" class="filter-item <?= $filter === $key ? 'active' : '' ?>">
                <?= $label ?>
              </a>
            <?php endforeach; ?>
          </div>

          <?php if (empty($bookings)): ?>
            <div class="empty-state">
              <div class="empty-icon">
                <i class="ph-bold ph-suitcase"></i>
              </div>
              <h3 class="empty-title">
                <?= $filter === 'semua' ? 'Belum Ada Perjalanan' : 'Tidak Ada Perjalanan' ?>
              </h3>
              <p class="empty-desc">
                <?php if ($filter === 'semua'): ?>
                  Anda belum memiliki riwayat perjalanan. Yuk jelajahi penginapan menarik dan buat pengalaman pertama Anda!
                <?php else: ?>
                  Tidak ada perjalanan dengan status <strong><?= $filters[$filter] ?></strong>.
                <?php endif; ?>
              </p>
              <a href="../../index.php" class="empty-button">
                <i class="ph-bold ph-magnifying-glass"></i>
                Cari Penginapan
              </a>
            </div>

          <?php else: ?>
            <div class="trips-list">
              <?php foreach ($bookings as $b):
                $badge   = get_badge($b['status'], $b['checkin'], $b['checkout']);
                $malam   = jumlah_malam($b['checkin'], $b['checkout']);
                $id_rsv  = '#RSV-' . date('Y', strtotime($b['dibuat_pada'])) . '-' . str_pad($b['id'], 4, '0', STR_PAD_LEFT);
                $img     = listing_img($b);
                $lokasi  = htmlspecialchars($b['lokasi'] ?? '');
                $nama    = htmlspecialchars($b['nama_listing']);
                $kamar   = $b['nama_kamar'] ? ' · ' . htmlspecialchars($b['nama_kamar']) : '';
                ?>
                <div class="trip-card">
                  <div class="trip-image-container">
                    <?php if ($img): ?>
                      <img src="<?= $img ?>" class="trip-image" alt="<?= $nama ?>" />
                    <?php else: ?>
                      <div class="trip-image"
                        style="background:#e5e7eb;display:flex;align-items:center;justify-content:center;">
                        <i class="ph-bold ph-image" style="font-size:2rem;color:#9ca3af;"></i>
                      </div>
                    <?php endif; ?>
                    <div class="trip-badge">
                      <span class="badge-dot <?= $badge['class'] ?>"></span>
                      <?= $badge['label'] ?>
                    </div>
                  </div>
                  <div class="trip-content">
                    <h3 class="trip-name"><?= $nama ?></h3>
                    <?php if ($lokasi): ?>
                      <p class="trip-location"><?= $lokasi ?></p>
                    <?php endif; ?>
                    <p class="trip-meta">
                      <?= fmt_tanggal($b['checkin']) ?> – <?= fmt_tanggal($b['checkout']) ?>
                      · <?= intval($b['jumlah_tamu']) ?> tamu
                      · <?= $malam ?> malam<?= $kamar ?>
                    </p>
                    <div class="trip-price">
                      <?= fmt_harga($b['total_harga']) ?>
                      <span>/ total</span>
                    </div>
                    <p class="trip-id" style="font-size:0.75rem;color:var(--color-muted,#888);margin-top:2px;">
                      <?= $id_rsv ?>
                    </p>

                    <div class="trip-actions">
                      <?php if ($b['status'] === 'menunggu' || $b['status'] === 'dikonfirmasi'): ?>
                        <a href="./booking_detail.php?id=<?= $b['id'] ?>">
                          <button class="action-button primary">Lihat detail</button>
                        </a>
                        <a href="./cancel_booking.php?id=<?= $b['id'] ?>">
                          <button class="action-button secondary">Batalkan</button>
                        </a>
                      <?php elseif ($b['status'] === 'selesai'): ?>
                        <a href="../../index.php?listing=<?= $b['listing_id'] ?? '' ?>">
                          <button class="action-button primary">Pesan lagi</button>
                        </a>
                        <a href="./review.php?booking_id=<?= $b['id'] ?>">
                          <button class="action-button secondary">Tulis ulasan</button>
                        </a>
                      <?php elseif ($b['status'] === 'dibatalkan'): ?>
                        <a href="../../index.php">
                          <button class="action-button primary">Pesan lagi</button>
                        </a>
                        <a href="./booking_detail.php?id=<?= $b['id'] ?>">
                          <button class="action-button secondary">Lihat detail</button>
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      </div>
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
          <li><a href="../../index.php" class="footer-link">Beranda</a></li>
          <li><a href="promo_deals.php" class="footer-link">Promo &amp; Deals</a></li>
          <li><a href="become_host.php" class="footer-link">Jadi Host</a></li>
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

  <script src="../scripts/account.js"></script>
  <script src="../../components/navbar.js"></script>
  <script src="../../popups/auth.js"></script>
</body>

</html>