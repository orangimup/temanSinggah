<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id'])) {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

$listing_id = intval($_GET['listing_id'] ?? 0);
$room_id = intval($_GET['room_id'] ?? 0);
$checkin_str = trim($_GET['checkin'] ?? '');
$checkout_str = trim($_GET['checkout'] ?? '');
$jumlah_tamu = intval($_GET['jumlah_tamu'] ?? 1);
$kode_promo = trim($_GET['promo'] ?? '');

if (!$listing_id || !$checkin_str || !$checkout_str) {
  header('Location: ../../index.php');
  exit;
}

$checkin_dt = DateTime::createFromFormat('d-m-Y', $checkin_str);
$checkout_dt = DateTime::createFromFormat('d-m-Y', $checkout_str);
if (!$checkin_dt)
  $checkin_dt = DateTime::createFromFormat('Y-m-d', $checkin_str);
if (!$checkout_dt)
  $checkout_dt = DateTime::createFromFormat('Y-m-d', $checkout_str);

if (!$checkin_dt || !$checkout_dt || $checkout_dt <= $checkin_dt) {
  header('Location: ../../index.php');
  exit;
}

$stmt = mysqli_prepare($koneksi, "
    SELECT l.id, l.judul, l.harga_malam, l.lokasi, l.tipe_properti,
           p.nama_file AS gambar_utama
    FROM listings l
    LEFT JOIN listing_photos p ON p.listing_id = l.id AND p.adalah_cover = 1
    WHERE l.id = ? AND l.status = 'aktif'
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 'i', $listing_id);
mysqli_stmt_execute($stmt);
$listing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$listing) {
  header('Location: ../../index.php');
  exit;
}

$room = null;
if ($room_id) {
  $stmt = mysqli_prepare($koneksi, "
      SELECT id, nama, harga_malam, deskripsi, max_tamu, foto
      FROM listing_rooms
      WHERE id = ? AND listing_id = ?
      LIMIT 1
  ");
  mysqli_stmt_bind_param($stmt, 'ii', $room_id, $listing_id);
  mysqli_stmt_execute($stmt);
  $room = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
  mysqli_stmt_close($stmt);
}

$harga_malam = $room ? (float) $room['harga_malam'] : (float) $listing['harga_malam'];
$nama_kamar = $room ? $room['nama'] : null;
$jumlah_malam = (int) $checkin_dt->diff($checkout_dt)->days;
$subtotal = $harga_malam * $jumlah_malam;

$diskon_persen = 0;
$diskon_amt = 0;
$promo_valid = false;
$promo_error = '';

if ($kode_promo) {
  $stmt = mysqli_prepare($koneksi, "
      SELECT * FROM promo_codes
      WHERE kode = ?
        AND status = 'aktif'
        AND berlaku_dari  <= CURDATE()
        AND berlaku_hingga >= CURDATE()
      LIMIT 1
  ");
  mysqli_stmt_bind_param($stmt, 's', $kode_promo);
  mysqli_stmt_execute($stmt);
  $promo_data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
  mysqli_stmt_close($stmt);

  if (!$promo_data) {
    $promo_error = 'Kode promo tidak valid atau sudah kedaluwarsa.';
  } elseif ($jumlah_malam < $promo_data['min_malam']) {
    $promo_error = "Promo ini minimal {$promo_data['min_malam']} malam.";
  } elseif ($promo_data['maks_pakai'] !== null && $promo_data['sudah_dipakai'] >= $promo_data['maks_pakai']) {
    $promo_error = 'Kuota promo sudah habis.';
  } else {
    $stmt2 = mysqli_prepare($koneksi, "
        SELECT COUNT(*) AS total FROM promo_usage
        WHERE promo_id = ? AND user_id = ?
    ");
    $uid = $_SESSION['id'];
    mysqli_stmt_bind_param($stmt2, 'ii', $promo_data['id'], $uid);
    mysqli_stmt_execute($stmt2);
    $used = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt2))['total'];
    mysqli_stmt_close($stmt2);

    if ($promo_data['maks_pakai_per_user'] !== null && $used >= $promo_data['maks_pakai_per_user']) {
      $promo_error = $promo_data['maks_pakai_per_user'] == 1
        ? 'Kamu sudah pernah memakai promo ini.'
        : "Kamu sudah memakai promo ini {$promo_data['maks_pakai_per_user']}x.";
    } else {
      $promo_valid = true;
      $diskon_persen = $promo_data['diskon_persen'];
    }
  }
}

$diskon_amt = $promo_valid ? $subtotal * ($diskon_persen / 100) : 0;
$biaya_layanan = (int) round(($subtotal - $diskon_amt) * 0.05);
$total_harga = $subtotal - $diskon_amt + $biaya_layanan;

$dp_persen = 30;
$dp_amount = (int) round($total_harga * $dp_persen / 100);
$sisa_bayar = $total_harga - $dp_amount;

$fmt_in = $checkin_dt->format('d M Y');
$fmt_out = $checkout_dt->format('d M Y');
$batas_batal = (clone $checkin_dt)->modify('-7 days')->format('d M Y');
$bayar_nanti = (clone $checkin_dt)->modify('-2 days')->format('d M');

$checkin_val = $checkin_dt->format('Y-m-d');
$checkout_val = $checkout_dt->format('Y-m-d');

$gambar = $listing['gambar_utama'] ?? '';
$gambar_src = $gambar
  ? (str_starts_with($gambar, 'http') ? $gambar
    : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($gambar))
  : 'https://placehold.co/80x60/8b2500/ffffff?text=Foto';

$error_msg = $_SESSION['booking_error'] ?? '';
unset($_SESSION['booking_error']);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Konfirmasi &amp; Pembayaran | Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../../components/navbar.css" />
  <link rel="stylesheet" href="../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/payment_confirm.css" />
  <link rel="stylesheet" href="../styles/payment_confirm_patch.css" />
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

  <main class="main-content">

    <?php if ($error_msg): ?>
      <div style="background:#fff0f0; border:1px solid #fcc; color:#c0392b;
          padding:12px 20px; border-radius:10px; margin:16px auto;
          max-width:960px; font-size:14px;">
        <i class="ph-bold ph-warning-circle"></i>
        <?= htmlspecialchars($error_msg) ?>
      </div>
    <?php endif; ?>

    <section class="page-title">
      <button class="back-button" onclick="history.back()" aria-label="Kembali">
        <i class="ph-bold ph-caret-left"></i>
      </button>
      Konfirmasi dan Bayar
    </section>

    <div class="content-layout">

      <section class="step-section">

        <div class="step-card" id="payCard">
          <div class="step-card-header">
            <div class="step-card-text">
              <div class="step-card-title">1. Pilih kapan akan membayar</div>
              <div class="step-card-sub" id="paySub">
                Bayar penuh Rp<?= number_format($total_harga, 0, ',', '.') ?> sekarang
              </div>
            </div>
            <button class="change-button" data-toggle-section="pay">Ganti</button>
          </div>

          <div class="step-expand" id="payExpand">

            <div class="expand-radio-row" data-select-pay="now">
              <div>
                <div class="expand-radio-label">
                  Bayar penuh Rp<?= number_format($total_harga, 0, ',', '.') ?> sekarang
                </div>
                <div class="expand-radio-desc">Langsung dikonfirmasi setelah pembayaran.</div>
              </div>
              <div class="expand-radio-circle selected" id="radio-pay-now"></div>
            </div>

            <div class="expand-radio-row" data-select-pay="later">
              <div>
                <div class="expand-radio-label">
                  Bayar DP Rp<?= number_format($dp_amount, 0, ',', '.') ?> sekarang (30%)
                </div>
                <div class="expand-radio-desc">
                  Sisa Rp<?= number_format($sisa_bayar, 0, ',', '.') ?> dibayar saat check-in.
                  DP hangus jika dibatalkan setelah <?= $batas_batal ?>.
                </div>
              </div>
              <div class="expand-radio-circle" id="radio-pay-later"></div>
            </div>

            <div class="dp-info-box" id="dpInfoBox" style="display:none;">
              <strong>Info DP:</strong> Kamu membayar 30% sebagai jaminan reservasi.
              Sisa <strong>Rp<?= number_format($sisa_bayar, 0, ',', '.') ?></strong> wajib
              dilunasi saat check-in. Jika dibatalkan setelah <strong><?= $batas_batal ?></strong>,
              DP tidak dapat dikembalikan.
            </div>

            <div class="expand-done-row">
              <button class="expand-done-button" data-toggle-section="pay">Selanjutnya</button>
            </div>
          </div>
        </div>

        <div class="step-card" id="card-method">
          <div class="step-card-header">
            <div class="step-card-text">
              <div class="step-card-title">2. Metode pembayaran</div>
              <div class="step-card-sub" id="methodSubLabel">GoPay</div>
            </div>
            <button class="change-button" data-toggle-section="method">Ganti</button>
          </div>

          <div class="step-expand" id="methodExpand">

            <div class="method-section-label">Dompet Digital</div>

            <div class="method-row selected-row" data-method-id="gopay">
              <div class="method-logo"
                style="background:#fff; padding:4px; border:.5px solid #e0e0e0; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                <img src="assets/logo/gopay.svg" alt="GoPay" style="width:48px; height:28px; object-fit:contain;" />
              </div>
              <div class="method-info">
                <p class="method-name">GoPay</p>
                <p class="method-desc">Dompet digital Gojek</p>
              </div>
              <div class="method-radio selected" id="radio-gopay"></div>
            </div>

            <div id="ewalletInputBox" class="pay-input-box" style="display:block;">
              <label class="pay-input-label">
                Nomor HP / Akun <span id="ewalletBrandLabel">GoPay</span>
              </label>
              <input type="tel" id="ewalletNumber" class="pay-input" placeholder="Contoh: 08123456789" maxlength="14" />
            </div>

            <div class="method-row" data-method-id="ovo">
              <div class="method-logo"
                style="background:#fff; padding:4px; border:.5px solid #e0e0e0; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                <img src="assets/logo/ovo.svg" alt="OVO" style="width:48px; height:28px; object-fit:contain;" />
              </div>
              <div class="method-info">
                <p class="method-name">OVO</p>
                <p class="method-desc">Dompet digital OVO</p>
              </div>
              <div class="method-radio" id="radio-ovo"></div>
            </div>

            <div class="method-row" data-method-id="dana">
              <div class="method-logo"
                style="background:#fff; padding:4px; border:.5px solid #e0e0e0; border-radius:8px; display:flex; align-items:center; justify-content:center;">
                <img src="assets/logo/dana.svg" alt="DANA" style="width:48px; height:28px; object-fit:contain;" />
              </div>
              <div class="method-info">
                <p class="method-name">DANA</p>
                <p class="method-desc">Dompet digital DANA</p>
              </div>
              <div class="method-radio" id="radio-dana"></div>
            </div>

            <div class="method-section-label">Kartu Kredit &amp; Debit</div>

            <div class="method-row" data-method-id="visa">
              <div class="method-logo" style="background:#fff; border:.5px solid #d0d5e8;">
                <svg viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" width="56" height="36">
                  <rect width="56" height="36" rx="7" fill="#fff" />
                  <text x="28" y="24" font-family="Arial Black,sans-serif" font-size="17" font-weight="900"
                    fill="#1A1F71" text-anchor="middle" font-style="italic" letter-spacing="-0.5">VISA</text>
                </svg>
              </div>
              <div class="method-info">
                <p class="method-name">Visa</p>
                <p class="method-desc">Kartu kredit &amp; debit Visa</p>
              </div>
              <div class="method-radio" id="radio-visa"></div>
            </div>

            <div class="method-row" data-method-id="mastercard">
              <div class="method-logo" style="background:#252525;">
                <svg viewBox="0 0 56 36" xmlns="http://www.w3.org/2000/svg" width="56" height="36">
                  <rect width="56" height="36" rx="7" fill="#252525" />
                  <circle cx="22" cy="18" r="10" fill="#EB001B" />
                  <circle cx="34" cy="18" r="10" fill="#F79E1B" />
                  <path
                    d="M28 9.8C30.1 11.8 31.4 14.7 31.4 18C31.4 21.3 30.1 24.2 28 26.2C25.9 24.2 24.6 21.3 24.6 18C24.6 14.7 25.9 11.8 28 9.8Z"
                    fill="#FF5F00" />
                </svg>
              </div>
              <div class="method-info">
                <p class="method-name">Mastercard</p>
                <p class="method-desc">Kartu kredit &amp; debit Mastercard</p>
              </div>
              <div class="method-radio" id="radio-mastercard"></div>
            </div>

            <div id="cardInputBox" class="pay-input-box" style="display:none;">

              <div style="margin-bottom:10px;">
                <label class="pay-input-label">Nomor Kartu</label>
                <input type="text" id="cardNumber" class="pay-input" placeholder="1234  5678  9012  3456" maxlength="24"
                  style="letter-spacing:.1em; font-size:15px;" />
              </div>

              <div class="card-grid-2">
                <div>
                  <label class="pay-input-label">Berlaku s/d</label>
                  <input type="text" id="cardExpiry" class="pay-input" placeholder="MM / YY" maxlength="5" />
                </div>
                <div>
                  <label class="pay-input-label">CVV / CVC</label>
                  <input type="password" id="cardCvv" class="pay-input" placeholder="•••" maxlength="3" />
                </div>
              </div>

              <div>
                <label class="pay-input-label">Nama di Kartu</label>
                <input type="text" id="cardName" class="pay-input" placeholder="Sesuai nama tertera di kartu" />
              </div>

            </div>

            <div class="expand-done-row">
              <button class="expand-done-button" data-toggle-section="method">Selesai</button>
            </div>
          </div>
        </div>

        <div class="step-card">
          <div class="expand-card-header">
            <div class="expand-step-title">3. Review &amp; konfirmasi reservasi</div>
          </div>

          <div style="margin-top:16px;">

            <div style="font-size:13px; color:#888; margin-bottom:16px; line-height:1.8;">
              <div>
                <i class="ph-bold ph-calendar"></i>
                <?= $fmt_in ?> &rarr; <?= $fmt_out ?> (<?= $jumlah_malam ?> malam)
              </div>
              <div><i class="ph-bold ph-users"></i> <?= $jumlah_tamu ?> tamu</div>
              <?php if ($nama_kamar): ?>
                <div><i class="ph-bold ph-door"></i> <?= htmlspecialchars($nama_kamar) ?></div>
              <?php endif; ?>
              <?php if ($kode_promo && $promo_valid): ?>
                <div style="color:#27ae60;">
                  <i class="ph-bold ph-tag"></i> Promo: <?= htmlspecialchars($kode_promo) ?>
                </div>
              <?php endif; ?>
            </div>

            <div style="background:#fafafa; border:1px solid #ebebeb; border-radius:12px;
                padding:14px 16px; margin-bottom:16px; font-size:13px; color:#555; line-height:1.9;">

              <div style="display:flex; justify-content:space-between;">
                <span><?= $jumlah_malam ?> malam &times; Rp<?= number_format($harga_malam, 0, ',', '.') ?></span>
                <span>Rp<?= number_format($subtotal, 0, ',', '.') ?></span>
              </div>

              <?php if ($promo_valid): ?>
                <div style="display:flex; justify-content:space-between; color:#27ae60;">
                  <span><i class="ph-bold ph-tag"></i> Diskon <?= $diskon_persen ?>%
                    (<?= htmlspecialchars($kode_promo) ?>)</span>
                  <span>- Rp<?= number_format($diskon_amt, 0, ',', '.') ?></span>
                </div>
              <?php endif; ?>

              <div style="display:flex; justify-content:space-between; color:#aaa;">
                <span>Biaya layanan (5%)</span>
                <span>Rp<?= number_format($biaya_layanan, 0, ',', '.') ?></span>
              </div>

              <div style="display:flex; justify-content:space-between; font-weight:700;
                  color:#1a1a1a; border-top:1.5px solid #e8e8e8; margin-top:10px; padding-top:10px;">
                <span>Total Bayar</span>
                <span style="color:#8b2500;">Rp<?= number_format($total_harga, 0, ',', '.') ?></span>
              </div>

              <div id="summaryDpBreakdown" style="display:none; margin-top:10px;
                  background:#fffbf0; border:1px solid #f5d97a; border-radius:8px;
                  padding:10px 12px; color:#7a5a00; font-size:12.5px; line-height:1.7;">
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                  <span>Dibayar sekarang (DP 30%)</span>
                  <strong>Rp<?= number_format($dp_amount, 0, ',', '.') ?></strong>
                </div>
                <div style="display:flex; justify-content:space-between; color:#a07820;">
                  <span>Sisa saat check-in</span>
                  <span>Rp<?= number_format($sisa_bayar, 0, ',', '.') ?></span>
                </div>
              </div>
            </div>

            <form method="POST" action="./process_booking.php" id="bookingForm">
              <input type="hidden" name="listing_id" value="<?= $listing_id ?>">
              <input type="hidden" name="room_id" value="<?= $room_id ?>">
              <input type="hidden" name="checkin" value="<?= htmlspecialchars($checkin_val) ?>">
              <input type="hidden" name="checkout" value="<?= htmlspecialchars($checkout_val) ?>">
              <input type="hidden" name="jumlah_tamu" value="<?= $jumlah_tamu ?>">
              <input type="hidden" name="total_harga" value="<?= $total_harga ?>">
              <input type="hidden" name="dp_amount" value="<?= $dp_amount ?>">
              <input type="hidden" name="kode_promo" value="<?= htmlspecialchars($kode_promo) ?>">
              <input type="hidden" name="metode_bayar" value="gopay" id="inputMetode">
              <input type="hidden" name="detail_bayar" value="" id="inputDetailBayar">
              <input type="hidden" name="waktu_bayar" value="now" id="inputWaktuBayar">

              <button type="submit" class="expand-confirm-button" id="btnKonfirmasi">
                <i class="ph-bold ph-lock-simple"></i>
                <span id="btnPayLabel">Konfirmasi dan Bayar Rp<?= number_format($total_harga, 0, ',', '.') ?></span>
              </button>
            </form>

            <p style="font-size:12px; color:#bbb; text-align:center; margin-top:12px;">
              <i class="ph-bold ph-shield-check"></i>
              Pembayaran dienkripsi dan aman. Data kartu tidak disimpan.
            </p>
          </div>
        </div>

      </section>

      <div class="expand-summary">
        <div class="expand-summary-card">

          <div class="expand-prop-row">
            <div class="expand-prop-image">
              <img src="<?= $gambar_src ?>" alt="<?= htmlspecialchars($listing['judul']) ?>"
                style="width:100%; height:100%; object-fit:cover;" />
            </div>
            <div style="flex:1; min-width:0;">
              <div class="expand-prop-name"><?= htmlspecialchars($listing['judul']) ?></div>
              <div style="font-size:12px; color:#aaa; margin-top:3px;">
                <?= htmlspecialchars(ucfirst($listing['tipe_properti'])) ?>
                &bull; <?= htmlspecialchars($listing['lokasi']) ?>
              </div>
              <?php if ($nama_kamar): ?>
                <div class="room-badge">
                  <i class="ph-bold ph-door" style="font-size:12px;"></i>
                  <?= htmlspecialchars($nama_kamar) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="expand-cancel-box">
            <div class="expand-cancel-title">Gratis pembatalan</div>
            <div class="expand-cancel-desc">
              Batalkan sebelum <strong><?= $batas_batal ?></strong>
              untuk pengembalian uang penuh.
            </div>
          </div>

          <div class="expand-info-row">
            <div>
              <div class="expand-info-label">Tanggal</div>
              <div class="expand-info-value"><?= $fmt_in ?> &ndash; <?= $fmt_out ?></div>
            </div>
            <button class="expand-change-sm" onclick="history.back()">Ganti</button>
          </div>
          <hr class="expand-divider" />

          <div class="expand-info-row">
            <div>
              <div class="expand-info-label">Pengunjung</div>
              <div class="expand-info-value"><?= $jumlah_tamu ?> orang</div>
            </div>
          </div>

          <?php if ($kode_promo && $promo_valid): ?>
            <hr class="expand-divider" />
            <div class="expand-info-row">
              <div>
                <div class="expand-info-label">Kode Promo</div>
                <div class="expand-info-value" style="color:#27ae60; font-weight:600;">
                  <i class="ph-bold ph-tag"></i> <?= htmlspecialchars($kode_promo) ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <hr class="expand-divider" />

          <div class="expand-info-label expand-price-label">Rincian Harga</div>

          <?php if ($nama_kamar): ?>
            <div class="expand-price-row" style="color:#888; font-size:13px;">
              <span>Kamar dipilih</span>
              <span><?= htmlspecialchars($nama_kamar) ?></span>
            </div>
          <?php endif; ?>

          <div class="expand-price-row">
            <span><?= $jumlah_malam ?> malam &times; Rp<?= number_format($harga_malam, 0, ',', '.') ?></span>
            <span>Rp<?= number_format($subtotal, 0, ',', '.') ?></span>
          </div>

          <?php if ($promo_valid): ?>
            <div class="expand-price-row" style="color:#27ae60; font-size:13px;">
              <span><i class="ph-bold ph-tag"></i> Diskon <?= $diskon_persen ?>%
                (<?= htmlspecialchars($kode_promo) ?>)</span>
              <span>- Rp<?= number_format($diskon_amt, 0, ',', '.') ?></span>
            </div>
          <?php endif; ?>

          <?php if ($promo_error): ?>
            <div style="font-size:12px; color:#c0392b; background:#fff0f0;
                padding:8px 10px; border-radius:8px; margin:6px 0;">
              <i class="ph-bold ph-warning-circle"></i> <?= htmlspecialchars($promo_error) ?>
            </div>
          <?php endif; ?>

          <div class="expand-price-row" style="color:#888; font-size:13px;">
            <span>Biaya layanan (5%)</span>
            <span>Rp<?= number_format($biaya_layanan, 0, ',', '.') ?></span>
          </div>

          <hr class="expand-divider" />

          <div class="expand-total-row">
            <span>Total Bayar</span>
            <span style="color:#8b2500; font-weight:700;">
              Rp<?= number_format($total_harga, 0, ',', '.') ?>
            </span>
          </div>

        </div>
      </div>

    </div>
  </main>

  <script>
    var totalFormatted = 'Rp<?= number_format($total_harga, 0, ',', '.') ?>';
    var dpFormatted = 'Rp<?= number_format($dp_amount, 0, ',', '.') ?>';
    var bayarNanti = '<?= $bayar_nanti ?>';
  </script>

  <script src="../scripts/payment_confirm.js"></script>
  <script>
    (function () {
      const ewalletIds = ['gopay', 'ovo', 'dana'];
      const cardIds = ['visa', 'mastercard'];
      const allMethods = [...ewalletIds, ...cardIds];

      const ewalletBox = document.getElementById('ewalletInputBox');
      const cardBox = document.getElementById('cardInputBox');
      const brandLabel = document.getElementById('ewalletBrandLabel');
      const methodLabel = document.getElementById('methodSubLabel');
      const inputMetode = document.getElementById('inputMetode');

      const names = {
        gopay: 'GoPay', ovo: 'OVO', dana: 'DANA',
        visa: 'Visa', mastercard: 'Mastercard'
      };

      function selectMethod(id) {
        allMethods.forEach(m => {
          const radio = document.getElementById('radio-' + m);
          const row = document.querySelector(`[data-method-id="${m}"]`);
          if (!radio) return;
          if (m === id) {
            radio.classList.add('selected');
            row && row.classList.add('selected-row');
          } else {
            radio.classList.remove('selected');
            row && row.classList.remove('selected-row');
          }
        });

        const isEwallet = ewalletIds.includes(id);
        const isCard = cardIds.includes(id);

        ewalletBox.style.display = isEwallet ? 'block' : 'none';
        cardBox.style.display = isCard ? 'block' : 'none';

        if (isEwallet && brandLabel) {
          brandLabel.textContent = names[id];
        }

        if (isEwallet) {
          const selectedRow = document.querySelector(`[data-method-id="${id}"]`);
          if (selectedRow && selectedRow.nextSibling !== ewalletBox) {
            selectedRow.insertAdjacentElement('afterend', ewalletBox);
          }
        }

        if (isCard) {
          const selectedRow = document.querySelector(`[data-method-id="${id}"]`);
          if (selectedRow && selectedRow.nextSibling !== cardBox) {
            selectedRow.insertAdjacentElement('afterend', cardBox);
          }
        }

        methodLabel && (methodLabel.textContent = names[id] || id);
        inputMetode && (inputMetode.value = id);
      }

      allMethods.forEach(id => {
        const row = document.querySelector(`[data-method-id="${id}"]`);
        if (row) row.addEventListener('click', () => selectMethod(id));
      });

      const cardNumberInput = document.getElementById('cardNumber');
      if (cardNumberInput) {
        cardNumberInput.addEventListener('input', function () {
          let v = this.value.replace(/\D/g, '').substring(0, 16);
          this.value = v.replace(/(.{4})/g, '$1  ').trim();
        });
      }

      const cardExpiryInput = document.getElementById('cardExpiry');
      if (cardExpiryInput) {
        cardExpiryInput.addEventListener('input', function () {
          let v = this.value.replace(/\D/g, '').substring(0, 4);
          if (v.length >= 3) v = v.substring(0, 2) + ' / ' + v.substring(2);
          this.value = v;
        });
      }

      selectMethod('gopay');
    })();
  </script>
  <script src="../../components/navbar.js"></script>
  <script src="../../popups/auth.js"></script>

</body>

</html>