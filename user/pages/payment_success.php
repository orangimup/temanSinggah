<?php
session_start();
require_once '../../koneksi.php';

// Hanya bisa diakses setelah proses booking berhasil
if (!isset($_SESSION['last_booking_id'])) {
    header('Location: ../../index.php');
    exit;
}

$booking_id = intval($_SESSION['last_booking_id']);
unset($_SESSION['last_booking_id']); // hanya sekali pakai

// Ambil detail booking lengkap
$stmt = $koneksi->prepare("
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
        u.nama          AS nama_tamu,
        l.judul         AS nama_listing,
        l.tipe_properti,
        r.nama          AS nama_kamar
    FROM bookings b
    JOIN users    u ON u.id = b.user_id
    JOIN listings l ON l.id = b.listing_id
    LEFT JOIN listing_rooms r ON r.id = b.room_id
    WHERE b.id = ?
    LIMIT 1
");
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();
$koneksi->close();

if (!$booking) {
    header('Location: ../../index.php');
    exit;
}

// Format data tampilan
$id_reservasi = '#RSV-' . date('Y') . '-' . str_pad($booking['id'], 4, '0', STR_PAD_LEFT);
$fmt_checkin  = date('d M Y', strtotime($booking['checkin']));
$fmt_checkout = date('d M Y', strtotime($booking['checkout']));
$jumlah_malam = (new DateTime($booking['checkin']))->diff(new DateTime($booking['checkout']))->days;
$total_fmt    = 'Rp ' . number_format($booking['total_harga'], 0, ',', '.');

$metode_label = [
    'gopay'     => 'GoPay',
    'ovo'       => 'OVO',
    'dana'      => 'DANA',
    'shopeepay' => 'ShopeePay',
    'qris'      => 'QRIS',
    'bca'       => 'Transfer BCA',
    'bni'       => 'Transfer BNI',
    'mandiri'   => 'Transfer Mandiri',
    'bri'       => 'Transfer BRI',
    'card'      => 'Kartu Kredit/Debit',
];
$metode_tampil = $metode_label[$booking['metode_bayar']] ?? ucfirst($booking['metode_bayar']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Booking Berhasil | Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../../components/navbar.css" />
  <link rel="stylesheet" href="../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/payment_success.css" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
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
        <li class="nav-item"><a href="./promo_deals.php" class="nav-link">Promo &amp; Deals</a></li>
        <li class="nav-item"><a href="./become_host.php" class="nav-link">Jadi Host</a></li>
        <li class="nav-item"><a href="./about_us.php" class="nav-link">Tentang Kami</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <div class="nav-right">
        <a href="../../host/onboarding/pages/about_place.html">
          <button class="ghost-button">Ganti ke host</button>
        </a>
        <div class="icon-buttons">
          <button class="icon-button profile" aria-label="Profile">
            <?= strtoupper(mb_substr($_SESSION['nama'] ?? 'U', 0, 1)) ?>
          </button>
          <button class="icon-button hamburger" aria-label="Hamburger">
            <i class="ph-bold ph-list"></i>
          </button>
        </div>
        <div id="hamburgerDropdown"></div>
        <div id="authPopup"></div>
      </div>
    </nav>
  </header>

  <main class="success-layout">
    <section class="success-main">

      <div class="success-hero">
        <div class="success-icon-wrap">
          <i class="ph-fill ph-check-circle"></i>
        </div>
        <h1 class="success-title">Booking Berhasil!</h1>
        <p class="success-desc">
          Pembayaranmu telah dikonfirmasi sistem. Detail reservasi telah tercatat
          di dashboard dan email konfirmasi telah dikirim.
        </p>
      </div>

      <div class="receipt-card">
        <div class="receipt-header">
          <div class="receipt-id-block">
            <span class="receipt-label">ID RESERVASI</span>
            <div class="receipt-id-row">
              <span class="receipt-id" id="reservationId">
                <?= htmlspecialchars($id_reservasi) ?>
              </span>
              <button class="receipt-copy" id="copyBtn" aria-label="Salin ID">
                <i class="ph-bold ph-copy"></i>
                <span>Salin</span>
              </button>
            </div>
          </div>
        </div>

        <hr class="receipt-line" />

        <div class="receipt-grid">
          <div class="receipt-item">
            <span class="receipt-label">Nama Tamu</span>
            <span class="receipt-value"><?= htmlspecialchars($booking['nama_tamu']) ?></span>
          </div>
          <div class="receipt-item">
            <span class="receipt-label">Akomodasi</span>
            <span class="receipt-value"><?= htmlspecialchars($booking['nama_listing']) ?></span>
          </div>
          <?php if (!empty($booking['nama_kamar'])): ?>
          <div class="receipt-item">
            <span class="receipt-label">Kamar</span>
            <span class="receipt-value"><?= htmlspecialchars($booking['nama_kamar']) ?></span>
          </div>
          <?php endif; ?>
          <div class="receipt-item">
            <span class="receipt-label">Check-In</span>
            <span class="receipt-value"><?= $fmt_checkin ?></span>
          </div>
          <div class="receipt-item">
            <span class="receipt-label">Check-Out</span>
            <span class="receipt-value"><?= $fmt_checkout ?> (<?= $jumlah_malam ?> Malam)</span>
          </div>
          <div class="receipt-item">
            <span class="receipt-label">Jumlah Tamu</span>
            <span class="receipt-value"><?= intval($booking['jumlah_tamu']) ?> Orang</span>
          </div>
          <div class="receipt-item">
            <span class="receipt-label">Metode Bayar</span>
            <span class="receipt-value"><?= htmlspecialchars($metode_tampil) ?></span>
          </div>
          <?php if (!empty($booking['kode_promo'])): ?>
          <div class="receipt-item">
            <span class="receipt-label">Kode Promo</span>
            <span class="receipt-value" style="color:#27ae60;">
              <i class="ph-bold ph-tag"></i>
              <?= htmlspecialchars($booking['kode_promo']) ?>
            </span>
          </div>
          <?php endif; ?>
        </div>

        <hr class="receipt-line" />

        <div class="receipt-footer">
          <div class="receipt-price-block">
            <span class="receipt-label">Total Pembayaran</span>
            <span class="receipt-total"><?= $total_fmt ?></span>
          </div>
          <span class="receipt-status">
            <i class="ph-fill ph-seal-check"></i>
            Lunas
          </span>
        </div>
      </div>

      <div class="success-actions">
        <a href="./history.php" class="btn btn-primary">
          <i class="ph-bold ph-receipt"></i>
          Lihat Riwayat
        </a>
        <a href="../../index.php" class="btn btn-secondary">
          <i class="ph-bold ph-house"></i>
          Kembali Beranda
        </a>
      </div>

      <p class="success-countdown">
        Mengalihkan otomatis dalam <strong id="countdown">10</strong> detik
      </p>

    </section>
  </main>

  <div class="toast" id="toast">
    <i class="ph-fill ph-check-circle"></i>
    <span id="toastText">ID berhasil disalin</span>
  </div>

  <script>
    // Countdown redirect
    let sisa = 10;
    const el = document.getElementById('countdown');
    const timer = setInterval(function () {
      sisa--;
      el.textContent = sisa;
      if (sisa <= 0) {
        clearInterval(timer);
        window.location.href = '../../index.php';
      }
    }, 1000);

    // Salin ID reservasi
    document.getElementById('copyBtn').addEventListener('click', function () {
      const id = document.getElementById('reservationId').textContent.trim();
      navigator.clipboard.writeText(id).then(function () {
        const toast = document.getElementById('toast');
        toast.classList.add('show');
        setTimeout(function () { toast.classList.remove('show'); }, 2500);
      });
    });
  </script>

  <script src="../../components/navbar.js"></script>
  <script src="../../popups/auth.js"></script>
</body>
</html>