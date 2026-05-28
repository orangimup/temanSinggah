<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_host.php';

$host = 'localhost';
$dbname = 'teman_singgah';
$user = 'root';
$pass = '';

try {
  $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  ]);
} catch (PDOException $e) {
  die('Koneksi gagal: ' . $e->getMessage());
}

$host_id = $_SESSION['id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['booking_id'])) {
  $booking_id = (int) $_POST['booking_id'];
  $action = $_POST['action'];

  if (in_array($action, ['accept', 'reject'])) {
    $newStatus = $action === 'accept' ? 'dikonfirmasi' : 'dibatalkan';
    $upd = $pdo->prepare("
      UPDATE bookings b
      JOIN listings l ON l.id = b.listing_id AND l.host_id = :host_id
      SET b.status = :status
      WHERE b.id = :id
    ");
    $upd->execute([':host_id' => $host_id, ':status' => $newStatus, ':id' => $booking_id]);
  }

  header('Location: reservations.php');
  exit;
}

$sql = "
    SELECT
        b.id,
        b.listing_id,
        b.user_id,
        b.room_id,
        b.checkin,
        b.checkout,
        b.jumlah_tamu,
        b.total_harga,
        b.status,
        b.dibuat_pada,
        u.nama AS guest_name,
        u.photo AS guest_photo,
        l.judul AS property_name,
        l.tipe_booking,
        lp.nama_file AS property_image,
        lr.nama AS room_name,
        DATEDIFF(b.checkout, b.checkin) AS durasi_malam,
        CASE
            WHEN b.status IN ('dibatalkan', 'batal') THEN 'cancelled'
            WHEN b.status = 'selesai'                THEN 'completed'
            WHEN b.status = 'menunggu'               THEN 'pending'
            WHEN NOW() BETWEEN b.checkin AND b.checkout
                 AND b.status NOT IN ('dibatalkan','batal','selesai') THEN 'ongoing'
            WHEN b.checkin > NOW()
                 AND b.status NOT IN ('dibatalkan','batal')           THEN 'upcoming'
            ELSE 'completed'
        END AS status_group
    FROM bookings b
    JOIN listings l             ON l.id  = b.listing_id AND l.host_id = :host_id
    LEFT JOIN listing_photos lp ON lp.listing_id = l.id AND lp.adalah_cover = 1
    JOIN users u                ON u.id  = b.user_id
    LEFT JOIN listing_rooms lr  ON lr.id = b.room_id
    ORDER BY b.dibuat_pada DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':host_id' => $host_id]);
$reservations = $stmt->fetchAll();

function initials(string $name): string
{
  $parts = array_filter(explode(' ', trim($name)));
  $first = strtoupper(substr($parts[0] ?? 'T', 0, 1));
  $last = count($parts) > 1 ? strtoupper(substr(end($parts), 0, 1)) : '';
  return $first . $last;
}
function fmtDate(string $date): string
{
  $b = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  [$y, $m, $d] = explode('-', $date);
  return (int) $d . ' ' . $b[(int) $m] . ' ' . $y;
}
function fmtRupiah(float $n): string
{
  return 'Rp ' . number_format($n, 0, ',', '.');
}
function badgeInfo(string $g): array
{
  return match ($g) {
    'pending' => ['label' => 'Menunggu', 'class' => 'pending'],
    'upcoming' => ['label' => 'Mendatang', 'class' => 'upcoming'],
    'ongoing' => ['label' => 'Berlangsung', 'class' => 'ongoing'],
    'completed' => ['label' => 'Selesai', 'class' => 'completed'],
    'cancelled' => ['label' => 'Dibatalkan', 'class' => 'cancelled'],
    default => ['label' => 'Lainnya', 'class' => 'completed'],
  };
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Reservasi | Teman Singgah</title>
  <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../../components/root.css" />
  <link rel="stylesheet" href="../../../components/navbar.css" />
  <link rel="stylesheet" href="../../../components/footer.css" />
  <link rel="stylesheet" href="../../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/reservations.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />

  <style>
    .card-badge.pending {
      background: #fff7ed;
      color: #c2410c;
    }

    .card-badge.pending .badge-dot {
      background: #c2410c;
    }

    .card-back {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      font-size: 0.78rem;
      color: #aaa;
      text-decoration: none;
      transition: color .15s;
    }

    .card-back:hover {
      color: #7c3a1e;
    }

    .card-footer {
      display: flex;
      flex-direction: column;
      gap: 8px;
      margin-top: auto;
      width: 100%;
    }

    .footer-main {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 12px;
      width: 100%;
    }

    .card-earning {
      flex: 1;
      align-self: flex-end;
      min-width: 0;
    }

    .earning-label {
      font-size: 11px;
      color: #999;
      margin: 2px 0 1px;
    }

    .earning-value {
      font-size: 15px;
      font-weight: 600;
      color: #1a1a1a;
      margin: 2px 0 1px;
    }

    .card-actions {
      display: flex;
      flex-direction: column;
      gap: 6px;
      align-items: flex-end;
    }

    .confirm-actions {
      display: flex;
      gap: 8px;
      margin-top: 4px;
      padding-top: 12px;
      border-top: 1px solid #f0f0f0;
      width: 100%;
    }

    .btn-confirm {
      flex: 1;
      padding: 9px 14px;
      border-radius: 10px;
      font-size: 0.8rem;
      font-weight: 600;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      transition: opacity .15s;
    }

    .btn-confirm:active {
      opacity: .8;
    }

    .btn-accept {
      background: #f0fdf4;
      color: #15803d;
      border: 1px solid #bbf7d0;
    }

    .btn-accept:hover {
      background: #dcfce7;
    }

    .btn-reject {
      background: #fff1f2;
      color: #be123c;
      border: 1px solid #fecdd3;
    }

    .btn-reject:hover {
      background: #ffe4e6;
    }

    .pending-bar {
      background: #fff7ed;
      border: 1px solid #fed7aa;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 0.8rem;
      color: #9a3412;
      display: flex;
      align-items: center;
      gap: 8px;
      margin-top: 10px;
    }
  </style>
</head>

<body>

  <header class="navbar">
    <nav class="navbar-container">
      <a href="reservations.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="../../../assets/logo/logo_temansinggah.svg" alt="Logo" class="logo-icon" />
        <img src="../../../assets/logo/label_temansinggah.svg" alt="Teman Singgah" class="logo-name" />
      </div>
      <ul class="nav-menu">
        <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/reservations.php"
            class="nav-link active">Reservasi</a></li>
        <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/calendar_router.php"
            class="nav-link">Kalender</a></li>
        <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/listing.php" class="nav-link">Listing</a></li>
        <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/messages.php" class="nav-link">Pesan</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <?php include $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/components/navbar_profile_host.php'; ?>
    </nav>
  </header>

  <main class="main-content">
    <section class="filter-section">
      <div class="filter-group">
        <button class="filter-item active" data-filter="all">Semua</button>
        <button class="filter-item" data-filter="pending">Menunggu</button>
        <button class="filter-item" data-filter="upcoming">Mendatang</button>
        <button class="filter-item" data-filter="ongoing">Berlangsung</button>
        <button class="filter-item" data-filter="completed">Selesai</button>
        <button class="filter-item" data-filter="cancelled">Dibatalkan</button>
      </div>
    </section>

    <section class="reservations-grid" id="reservationsGrid">
      <?php if (empty($reservations)): ?>
        <div class="empty-state">
          <div class="empty-icon"><i class="ph-bold ph-book-open-text"></i></div>
          <h3 class="empty-title">Belum Ada Reservasi</h3>
          <p class="empty-desc">Belum ada tamu yang melakukan reservasi.</p>
          <a href="/teman_singgah/index.php" class="empty-button">
            <i class="ph-bold ph-plus"></i> Tambahkan properti baru
          </a>
        </div>
      <?php else: ?>
        <?php foreach ($reservations as $r):
          $badge = badgeInfo($r['status_group']);
          $init = initials($r['guest_name']);
          $isPending = ($r['status_group'] === 'pending' && $r['tipe_booking'] === 'permintaan');

          $guestPhotoFile = $r['guest_photo'] ?? '';
          $guestPhotoPath = '/teman_singgah/assets/uploads/photos/' . $guestPhotoFile;
          $hasGuestPhoto = !empty($guestPhotoFile) && file_exists($_SERVER['DOCUMENT_ROOT'] . $guestPhotoPath);

          $imgSrc = !empty($r['property_image'])
            ? (str_starts_with($r['property_image'], 'http')
              ? $r['property_image']
              : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($r['property_image']))
            : '/teman_singgah/assets/images/apurva_kempinski_bali.jpg';

          $malam = (int) $r['durasi_malam'];
          $roomTxt = $r['room_name'] ? '1 kamar (' . htmlspecialchars($r['room_name']) . ')' : '1 kamar';
          ?>
          <article class="reservation-card" data-status="<?= $r['status_group'] ?>">

            <div class="card-header">
              <span class="card-badge <?= $badge['class'] ?>">
                <span class="badge-dot"></span><?= $badge['label'] ?>
              </span>
              <span class="card-id">#RSV-<?= date('Y') ?>-<?= str_pad($r['id'], 4, '0', STR_PAD_LEFT) ?></span>
            </div>

            <!-- Guest -->
            <div class="card-guest">
              <?php if ($hasGuestPhoto): ?>
                <div class="guest-avatar" style="padding:0;overflow:hidden;">
                  <img src="<?= htmlspecialchars($guestPhotoPath) ?>"
                    style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
                </div>
              <?php else: ?>
                <div class="guest-avatar"><?= htmlspecialchars($init) ?></div>
              <?php endif; ?>
              <div class="guest-info">
                <p class="guest-name"><?= htmlspecialchars($r['guest_name']) ?></p>
                <p class="guest-meta"><?= (int) $r['jumlah_tamu'] ?> tamu</p>
              </div>
            </div>

            <!-- Property -->
            <div class="card-property">
              <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($r['property_name']) ?>" class="property-image" />
              <div class="property-info">
                <p class="property-name"><?= htmlspecialchars($r['property_name']) ?></p>
                <p class="property-dates">
                  <i class="ph-bold ph-calendar-blank"></i>
                  <?= fmtDate($r['checkin']) ?> – <?= fmtDate($r['checkout']) ?>
                </p>
                <p class="property-duration"><?= $malam ?> malam · <?= $roomTxt ?></p>
              </div>
            </div>

            <div class="card-divider"></div>

            <div class="card-footer">
              <div class="footer-main">
                <div class="card-earning">
                  <p class="earning-label">Pendapatan</p>
                  <p class="earning-value"><?= fmtRupiah($r['total_harga']) ?></p>
                </div>
                <div class="card-actions">
                  <a href="reservation_detail.php?id=<?= $r['id'] ?>" class="card-button primary">Lihat detail</a>
                  <a href="messages.php?to=<?= $r['user_id'] ?>" class="card-button secondary">💬 Hubungi tamu</a>
                </div>
              </div>
              <div class="card-divider"></div>
              <?php if ($isPending): ?>
                <div class="pending-bar">
                  <i class="ph-bold ph-clock"></i>
                  Tamu sudah membayar — menunggu konfirmasimu
                </div>
                <div class="confirm-actions">
                  <form method="POST" style="flex:1;display:flex;">
                    <input type="hidden" name="booking_id" value="<?= (int) $r['id'] ?>">
                    <input type="hidden" name="action" value="reject">
                    <button type="submit" class="btn-confirm btn-reject" style="width:100%;">
                      <i class="ph-bold ph-x"></i> Tolak
                    </button>
                  </form>
                  <form method="POST" style="flex:1;display:flex;">
                    <input type="hidden" name="booking_id" value="<?= (int) $r['id'] ?>">
                    <input type="hidden" name="action" value="accept">
                    <button type="submit" class="btn-confirm btn-accept" style="width:100%;">
                      <i class="ph-bold ph-check"></i> Terima
                    </button>
                  </form>
                </div>
              <?php endif; ?>
            </div>

          </article>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>

    <section class="empty-state" id="emptyState" style="display:none;">
      <div class="empty-icon"><i class="ph-bold ph-book-open-text"></i></div>
      <h3 class="empty-title">Belum Ada Reservasi</h3>
      <p class="empty-desc">Tidak ada reservasi pada kategori ini.</p>
      <a href="/teman_singgah/index.php" class="empty-button">
        <i class="ph-bold ph-plus"></i> Tambahkan properti baru
      </a>
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

  <script src="../scripts/reservations.js"></script>
  <script src="../../../components/navbar.js"></script>
  <script src="../../../popups/auth.js"></script>
</body>

</html>