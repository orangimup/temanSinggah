<?php
session_start();
include "../../koneksi.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: /teman_singgah/index.php?auth=login");
  exit;
}

// Fetch current user
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
  if (str_starts_with($user['photo'], 'http')) {
    $photo_url = $user['photo'];
  } elseif (file_exists("../../assets/uploads/photos/" . $user['photo'])) {
    $photo_url = "/teman_singgah/assets/uploads/photos/" . htmlspecialchars($user['photo']);
  }
}

// Get booking id from query string
$booking_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$booking_id) {
  header("Location: ./history.php");
  exit;
}

// Fetch booking — must belong to this user and be cancellable
$stmt = mysqli_prepare($koneksi, "
  SELECT
    b.*,
    l.judul       AS nama_listing,
    l.lokasi,
    l.tipe_properti,
    r.nama        AS nama_kamar,
    (SELECT lp.nama_file
     FROM listing_photos lp
     WHERE lp.listing_id = l.id AND lp.adalah_cover = 1
     LIMIT 1)     AS foto_cover
  FROM bookings b
  JOIN listings l ON l.id = b.listing_id
  LEFT JOIN listing_rooms r ON r.id = b.room_id
  WHERE b.id = ? AND b.user_id = ?
");
mysqli_stmt_bind_param($stmt, "ii", $booking_id, $user['id']);
mysqli_stmt_execute($stmt);
$booking = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$booking) {
  header("Location: ./history.php");
  exit;
}

// Only menunggu or dikonfirmasi can be cancelled
$cancellable = in_array($booking['status'], ['menunggu', 'dikonfirmasi']);

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $cancellable) {
  $alasan = trim($_POST['alasan'] ?? '');

  if (empty($alasan)) {
    $error = 'Mohon pilih alasan pembatalan.';
  } else {
    $stmt = mysqli_prepare(
      $koneksi,
      "UPDATE bookings SET status = 'dibatalkan', catatan_pembatalan = ?, dibatalkan_pada = NOW() WHERE id = ? AND user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "sii", $alasan, $booking_id, $user['id']);
    if (mysqli_stmt_execute($stmt)) {
      $success = true;

      if (!empty($booking['room_id'])) {
        require_once 'check_room_stock.php';
        syncRoomStock($koneksi, (int) $booking['listing_id'], (int) $booking['room_id']);
      }

    } else {
      $error = 'Terjadi kesalahan. Silakan coba lagi.';
    }
    mysqli_stmt_close($stmt);
  }
}

// Helpers
function fmt_tanggal(string $date): string
{
  return date('d M Y', strtotime($date));
}
function fmt_harga(float $harga): string
{
  return 'Rp ' . number_format($harga, 0, ',', '.');
}
function jumlah_malam(string $checkin, string $checkout): int
{
  return (new DateTime($checkin))->diff(new DateTime($checkout))->days;
}
function listing_img(array $b): string
{
  $foto = $b['foto_cover'] ?? '';
  if (empty($foto))
    return '';
  if (str_starts_with($foto, 'http'))
    return $foto;
  return "/teman_singgah/assets/uploads/listings/" . htmlspecialchars($foto);
}

$malam = jumlah_malam($booking['checkin'], $booking['checkout']);
$img = listing_img($booking);
$id_rsv = '#RSV-' . date('Y', strtotime($booking['dibuat_pada'])) . '-' . str_pad($booking['id'], 4, '0', STR_PAD_LEFT);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Batalkan Pemesanan | Teman Singgah</title>
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
    /* ── Cancel page specific styles ── */
    .cancel-wrapper {
      max-width: 680px;
      margin: 0 auto;
      padding: 2rem 1rem 4rem;
    }

    .cancel-back {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      color: var(--color-muted, #6b7280);
      font-size: 0.875rem;
      text-decoration: none;
      margin-bottom: 1.5rem;
      transition: color 0.2s;
    }

    .cancel-back:hover {
      color: var(--color-primary, #1a1a1a);
    }

    .cancel-heading {
      font-family: 'Inter', sans-serif;
      font-size: 1.75rem;
      font-weight: 600;
      margin: 0 0 0.25rem;
    }

    .cancel-subheading {
      color: var(--color-muted, #6b7280);
      font-size: 0.9rem;
      margin: 0 0 2rem;
    }

    /* Booking summary card */
    .booking-summary {
      display: flex;
      gap: 1rem;
      background: var(--color-surface, #f9f9f9);
      border: 1px solid var(--color-border, #e5e7eb);
      border-radius: 12px;
      padding: 1rem;
      margin-bottom: 2rem;
    }

    .booking-summary-img {
      width: 88px;
      height: 88px;
      border-radius: 8px;
      object-fit: cover;
      flex-shrink: 0;
      background: #e5e7eb;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .booking-summary-body {
      flex: 1;
      min-width: 0;
    }

    .booking-summary-name {
      font-weight: 600;
      font-size: 1rem;
      margin: 0 0 0.2rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .booking-summary-loc {
      font-size: 0.8rem;
      color: var(--color-muted, #6b7280);
      margin: 0 0 0.4rem;
    }

    .booking-summary-meta {
      font-size: 0.82rem;
      color: var(--color-muted, #6b7280);
    }

    .booking-summary-price {
      font-size: 0.95rem;
      font-weight: 600;
      margin-top: 0.4rem;
    }

    .booking-summary-id {
      font-size: 0.72rem;
      color: #aaa;
    }

    /* Warning notice */
    .cancel-notice {
      display: flex;
      gap: 0.75rem;
      background: #fff7ed;
      border: 1px solid #fed7aa;
      border-radius: 10px;
      padding: 1rem 1.125rem;
      margin-bottom: 2rem;
      align-items: flex-start;
    }

    .cancel-notice i {
      color: #f97316;
      font-size: 1.25rem;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .cancel-notice-text {
      font-size: 0.875rem;
      line-height: 1.55;
      color: #7c3a1e;
    }

    .cancel-notice-text strong {
      display: block;
      margin-bottom: 0.2rem;
    }

    /* Form */
    .cancel-form {
      display: flex;
      flex-direction: column;
      gap: 1.25rem;
    }

    .form-label {
      display: block;
      font-weight: 500;
      font-size: 0.9rem;
      margin-bottom: 0.6rem;
    }

    .reason-options {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .reason-option {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem 1rem;
      border: 1.5px solid var(--color-border, #e5e7eb);
      border-radius: 10px;
      cursor: pointer;
      transition: border-color 0.18s, background 0.18s;
      font-size: 0.9rem;
    }

    .reason-option:has(input:checked) {
      border-color: var(--color-primary, #1a1a1a);
      background: #f5f5f5;
    }

    .reason-option input[type="radio"] {
      accent-color: var(--color-primary, #1a1a1a);
    }

    .form-actions {
      display: flex;
      gap: 0.75rem;
      margin-top: 0.5rem;
    }

    .btn-cancel-confirm {
      flex: 1;
      padding: 0.8rem 1.5rem;
      background: #dc2626;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.18s, transform 0.1s;
    }

    .btn-cancel-confirm:hover {
      background: #b91c1c;
    }

    .btn-cancel-confirm:active {
      transform: scale(0.98);
    }

    .btn-back-secondary {
      flex: 1;
      padding: 0.8rem 1.5rem;
      background: transparent;
      color: var(--color-primary, #1a1a1a);
      border: 1.5px solid var(--color-border, #e5e7eb);
      border-radius: 10px;
      font-size: 0.95rem;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.18s, border-color 0.18s;
      text-decoration: none;
      text-align: center;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .btn-back-secondary:hover {
      background: #f3f4f6;
      border-color: #9ca3af;
    }

    /* Error alert */
    .alert-error {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #991b1b;
      border-radius: 9px;
      padding: 0.75rem 1rem;
      font-size: 0.875rem;
    }

    /* Success state */
    .success-state {
      text-align: center;
      padding: 3rem 1.5rem;
    }

    .success-icon {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: #dcfce7;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1.25rem;
      font-size: 2rem;
      color: #16a34a;
    }

    .success-title {
      font-family: 'Inter', sans-serif;
      font-size: 1.5rem;
      font-weight: 600;
      margin: 0 0 0.5rem;
    }

    .success-desc {
      color: var(--color-muted, #6b7280);
      font-size: 0.9rem;
      margin: 0 0 2rem;
    }

    .success-actions {
      display: flex;
      gap: 0.75rem;
      justify-content: center;
      flex-wrap: wrap;
    }

    /* Not cancellable state */
    .not-cancellable {
      text-align: center;
      padding: 3rem 1.5rem;
    }

    .not-cancellable-icon {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      background: #fef9c3;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 1.75rem;
      color: #ca8a04;
    }
  </style>
</head>

<body>

  <!-- ── Navbar ── -->
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
    <div class="cancel-wrapper">

      <a href="./history.php" class="cancel-back">
        <i class="ph-bold ph-arrow-left"></i>
        Kembali ke Riwayat Perjalanan
      </a>

      <?php if ($success): ?>
        <!-- ── Success state ── -->
        <div class="success-state">
          <div class="success-icon"><i class="ph-bold ph-check"></i></div>
          <h2 class="success-title">Pemesanan Dibatalkan</h2>
          <p class="success-desc">
            Pemesanan <?= htmlspecialchars($id_rsv) ?> telah berhasil dibatalkan.<br>
            Pengembalian dana (jika ada) akan diproses dalam 3–7 hari kerja.
          </p>
          <div class="success-actions">
            <a href="./history.php" class="btn-back-secondary">Riwayat Perjalanan</a>
            <a href="../../index.php" class="btn-cancel-confirm"
              style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center;flex:unset;padding:0.8rem 2rem;">
              Cari Penginapan Lain
            </a>
          </div>
        </div>

      <?php elseif (!$cancellable): ?>
        <!-- ── Not cancellable ── -->
        <div class="not-cancellable">
          <div class="not-cancellable-icon"><i class="ph-bold ph-warning"></i></div>
          <h2 class="success-title">Tidak Dapat Dibatalkan</h2>
          <p class="success-desc">
            Pemesanan dengan status <strong><?= ucfirst(htmlspecialchars($booking['status'])) ?></strong>
            tidak dapat dibatalkan.
          </p>
          <a href="./history.php" class="btn-back-secondary" style="display:inline-flex;margin-top:0.5rem;">
            Kembali
          </a>
        </div>

      <?php else: ?>
        <!-- ── Cancellation form ── -->
        <h1 class="cancel-heading">Batalkan Pemesanan</h1>
        <p class="cancel-subheading">Tinjau detail pemesanan Anda sebelum melanjutkan pembatalan.</p>

        <!-- Booking summary -->
        <div class="booking-summary">
          <?php if ($img): ?>
            <img src="<?= $img ?>" alt="<?= htmlspecialchars($booking['nama_listing']) ?>" class="booking-summary-img" />
          <?php else: ?>
            <div class="booking-summary-img">
              <i class="ph-bold ph-image" style="font-size:1.5rem;color:#9ca3af;"></i>
            </div>
          <?php endif; ?>
          <div class="booking-summary-body">
            <p class="booking-summary-name"><?= htmlspecialchars($booking['nama_listing']) ?></p>
            <?php if (!empty($booking['lokasi'])): ?>
              <p class="booking-summary-loc"><?= htmlspecialchars($booking['lokasi']) ?></p>
            <?php endif; ?>
            <p class="booking-summary-meta">
              <?= fmt_tanggal($booking['checkin']) ?> – <?= fmt_tanggal($booking['checkout']) ?>
              · <?= $malam ?> malam · <?= intval($booking['jumlah_tamu']) ?> tamu
            </p>
            <p class="booking-summary-price"><?= fmt_harga($booking['total_harga']) ?> <span
                style="font-weight:400;font-size:0.82rem;color:#6b7280;">/ total</span></p>
            <p class="booking-summary-id"><?= $id_rsv ?></p>
          </div>
        </div>

        <!-- Warning notice -->
        <div class="cancel-notice">
          <i class="ph-fill ph-warning-circle"></i>
          <div class="cancel-notice-text">
            <strong>Perhatikan kebijakan pembatalan</strong>
            Pembatalan mungkin dikenakan biaya sesuai kebijakan host. Pengembalian dana akan diproses dalam 3–7 hari kerja
            setelah pembatalan dikonfirmasi.
          </div>
        </div>

        <!-- Error alert -->
        <?php if ($error): ?>
          <div class="alert-error" style="margin-bottom:1rem;">
            <i class="ph-fill ph-x-circle"></i>
            <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" class="cancel-form">
          <div>
            <label class="form-label">Alasan Pembatalan <span style="color:#dc2626;">*</span></label>
            <div class="reason-options">
              <?php
              $reasons = [
                'Perubahan rencana perjalanan',
                'Kesalahan dalam pemesanan',
                'Menemukan penginapan yang lebih baik',
                'Kondisi darurat atau tidak terduga',
                'Alasan kesehatan',
                'Lainnya',
              ];
              foreach ($reasons as $r): ?>
                <label class="reason-option">
                  <input type="radio" name="alasan" value="<?= htmlspecialchars($r) ?>" <?= (($_POST['alasan'] ?? '') === $r) ? 'checked' : '' ?> />
                  <?= htmlspecialchars($r) ?>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="form-actions">
            <a href="./history.php" class="btn-back-secondary">Kembali</a>
            <button type="submit" class="btn-cancel-confirm">
              <i class="ph-bold ph-x-circle" style="margin-right:0.4rem;"></i>
              Batalkan Pemesanan
            </button>
          </div>
        </form>

      <?php endif; ?>

    </div>
  </main>

  <!-- ── Footer ── -->
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