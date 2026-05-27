<?php
session_start();
require_once '../../koneksi.php';

// Wajib login
if (!isset($_SESSION['id'])) {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

// Ambil semua parameter dari URL
$listing_id   = intval($_GET['listing_id'] ?? 0);
$room_id      = intval($_GET['room_id'] ?? 0);
$checkin_str  = trim($_GET['checkin'] ?? '');
$checkout_str = trim($_GET['checkout'] ?? '');
$jumlah_tamu  = intval($_GET['jumlah_tamu'] ?? 1);
$kode_promo   = trim($_GET['promo'] ?? '');

// Validasi parameter wajib
if (!$listing_id || !$checkin_str || !$checkout_str) {
  header('Location: ../../index.php');
  exit;
}

// ── Parse tanggal format DD-MM-YYYY ────────────────────────────────────────
$checkin_dt  = DateTime::createFromFormat('d-m-Y', $checkin_str);
$checkout_dt = DateTime::createFromFormat('d-m-Y', $checkout_str);

// Fallback: coba format Y-m-d juga (untuk kompatibilitas)
if (!$checkin_dt)  $checkin_dt  = DateTime::createFromFormat('Y-m-d', $checkin_str);
if (!$checkout_dt) $checkout_dt = DateTime::createFromFormat('Y-m-d', $checkout_str);

if (!$checkin_dt || !$checkout_dt || $checkout_dt <= $checkin_dt) {
  header('Location: ../../index.php');
  exit;
}

// ── Ambil data listing ──────────────────────────────────────────────────────
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

// ── Ambil data kamar (jika dipilih) ────────────────────────────────────────
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

// ── Hitung harga dasar ──────────────────────────────────────────────────────
$harga_malam  = $room ? (float)$room['harga_malam'] : (float)$listing['harga_malam'];
$nama_kamar   = $room ? $room['nama'] : null;
$foto_kamar   = $room ? ($room['foto'] ?? null) : null;
$jumlah_malam = (int)$checkin_dt->diff($checkout_dt)->days;
$subtotal     = $harga_malam * $jumlah_malam;

// ── Validasi promo ──────────────────────────────────────────────────────────
$diskon_persen = 0;
$diskon_amt    = 0;
$promo_valid   = false;
$promo_error   = '';

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
      $promo_valid   = true;
      $diskon_persen = $promo_data['diskon_persen'];
    }
  }
}

// ── Hitung total akhir ──────────────────────────────────────────────────────
$diskon_amt    = $promo_valid ? $subtotal * ($diskon_persen / 100) : 0;
$biaya_layanan = (int)round(($subtotal - $diskon_amt) * 0.05);
$total_harga   = $subtotal - $diskon_amt + $biaya_layanan;

// ── Format tanggal tampilan ─────────────────────────────────────────────────
$fmt_in      = $checkin_dt->format('d M Y');
$fmt_out     = $checkout_dt->format('d M Y');
$batas_batal = (clone $checkin_dt)->modify('-7 days')->format('d M Y');
$bayar_nanti = (clone $checkin_dt)->modify('-2 days')->format('d M');

// Simpan string tanggal dalam format Y-m-d untuk dikirim ke process_booking
$checkin_val  = $checkin_dt->format('Y-m-d');
$checkout_val = $checkout_dt->format('Y-m-d');

// ── URL gambar listing ──────────────────────────────────────────────────────
$gambar     = $listing['gambar_utama'] ?? '';
$gambar_src = $gambar
  ? (str_starts_with($gambar, 'http') ? $gambar
    : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($gambar))
  : 'https://placehold.co/80x60/8b2500/ffffff?text=Foto';

// ── Error dari proses sebelumnya ────────────────────────────────────────────
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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>

  <style>
    .method-section-label {
      font-size: 11px;
      font-weight: 700;
      color: #aaa;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      margin: 18px 0 8px;
    }

    .method-row {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 4px;
      border-bottom: 1px solid #f0f0f0;
      cursor: pointer;
      transition: background 0.12s;
      border-radius: 8px;
    }

    .method-row:last-of-type {
      border-bottom: none;
    }

    .method-row:hover {
      background: #fafafa;
    }

    .method-logo {
      width: 40px;
      height: 40px;
      border-radius: 9px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 800;
      color: #fff;
      flex-shrink: 0;
      letter-spacing: -0.3px;
    }

    .method-info {
      flex: 1;
    }

    .method-name {
      font-size: 14px;
      font-weight: 600;
      color: #222;
      margin: 0 0 2px;
    }

    .method-desc {
      font-size: 12px;
      color: #aaa;
      margin: 0;
    }

    .method-radio {
      width: 20px;
      height: 20px;
      border-radius: 50%;
      border: 2px solid #ddd;
      flex-shrink: 0;
      transition: border-color 0.15s, background 0.15s;
    }

    .method-radio.selected {
      border-color: #8b2500;
      background: #8b2500;
      box-shadow: inset 0 0 0 3px #fff;
    }

    .pay-input-box {
      background: #fafafa;
      border: 1px solid #ebebeb;
      border-radius: 12px;
      padding: 16px;
      margin: 12px 0 4px;
    }

    .pay-input-label {
      font-size: 12px;
      font-weight: 600;
      color: #666;
      display: block;
      margin-bottom: 5px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .pay-input {
      width: 100%;
      padding: 10px 13px;
      border: 1.5px solid #e2e2e2;
      border-radius: 9px;
      font-size: 14px;
      box-sizing: border-box;
      font-family: inherit;
      background: #fff;
      transition: border-color 0.15s;
    }

    .pay-input:focus {
      outline: none;
      border-color: #8b2500;
    }

    .room-badge {
      display: inline-flex;
      align-items: center;
      gap: 5px;
      background: #fdf0ea;
      color: #8b2500;
      font-size: 12px;
      font-weight: 600;
      padding: 4px 10px;
      border-radius: 20px;
      margin-top: 5px;
    }
  </style>
</head>

<body>

  <!-- NAVBAR -->
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

    <!-- Error banner -->
    <?php if ($error_msg): ?>
      <div style="background:#fff0f0; border:1px solid #fcc; color:#c0392b;
          padding:12px 20px; border-radius:10px; margin:16px auto;
          max-width:960px; font-size:14px;">
        <i class="ph-bold ph-warning-circle"></i>
        <?= htmlspecialchars($error_msg) ?>
      </div>
    <?php endif; ?>

    <!-- Judul halaman -->
    <section class="page-title">
      <button class="back-button" onclick="history.back()" aria-label="Kembali">
        <i class="ph-bold ph-caret-left"></i>
      </button>
      Konfirmasi dan Bayar
    </section>

    <div class="content-layout">

      <!-- ══════════════════════════════════
           KIRI: 3 Step
      ══════════════════════════════════ -->
      <section class="step-section">

        <!-- ── Step 1: Kapan bayar ── -->
        <div class="step-card" id="payCard">
          <div class="step-card-header">
            <div class="step-card-text">
              <div class="step-card-title">1. Pilih kapan akan membayar</div>
              <div class="step-card-sub" id="paySub">
                Bayar Rp<?= number_format($total_harga, 0, ',', '.') ?> sekarang
              </div>
            </div>
            <button class="change-button" data-toggle-section="pay">Ganti</button>
          </div>

          <div class="step-expand" id="payExpand">
            <!-- Bayar sekarang -->
            <div class="expand-radio-row" data-select-pay="now">
              <div>
                <div class="expand-radio-label">
                  Bayar Rp<?= number_format($total_harga, 0, ',', '.') ?> sekarang
                </div>
                <div class="expand-radio-desc">Langsung dikonfirmasi setelah pembayaran.</div>
              </div>
              <div class="expand-radio-circle selected" id="radio-pay-now"></div>
            </div>
            <!-- Bayar nanti -->
            <div class="expand-radio-row" data-select-pay="later">
              <div class="step-card-text">
                <div class="expand-radio-label">Bayar Rp0 sekarang</div>
                <div class="expand-radio-desc">
                  Rp<?= number_format($total_harga, 0, ',', '.') ?> ditagih pada <?= $bayar_nanti ?>
                </div>
              </div>
              <div class="expand-radio-circle" id="radio-pay-later"></div>
            </div>
            <div class="expand-done-row">
              <button class="expand-done-button" data-toggle-section="pay">Selanjutnya</button>
            </div>
          </div>
        </div>

        <!-- ── Step 2: Metode pembayaran ── -->
        <div class="step-card" id="card-method">
          <div class="step-card-header">
            <div class="step-card-text">
              <div class="step-card-title">2. Metode pembayaran</div>
              <div class="step-card-sub" id="methodSubLabel">GoPay</div>
            </div>
            <button class="change-button" data-toggle-section="method">Ganti</button>
          </div>

          <div class="step-expand" id="methodExpand">

            <!-- Dompet Digital -->
            <div class="method-section-label">Dompet Digital</div>

            <div class="method-row" data-method-id="gopay">
              <div class="method-logo" style="background:#00AED6;">GP</div>
              <div class="method-info">
                <p class="method-name">GoPay</p>
                <p class="method-desc">Dompet digital Gojek</p>
              </div>
              <div class="method-radio selected" id="radio-gopay"></div>
            </div>

            <div class="method-row" data-method-id="ovo">
              <div class="method-logo" style="background:#4C3494;">OVO</div>
              <div class="method-info">
                <p class="method-name">OVO</p>
                <p class="method-desc">Dompet digital OVO</p>
              </div>
              <div class="method-radio" id="radio-ovo"></div>
            </div>

            <div class="method-row" data-method-id="dana">
              <div class="method-logo" style="background:#108EEA;">DANA</div>
              <div class="method-info">
                <p class="method-name">DANA</p>
                <p class="method-desc">Dompet digital DANA</p>
              </div>
              <div class="method-radio" id="radio-dana"></div>
            </div>

            <div class="method-row" data-method-id="shopeepay">
              <div class="method-logo" style="background:#EE4D2D;">SPay</div>
              <div class="method-info">
                <p class="method-name">ShopeePay</p>
                <p class="method-desc">Dompet digital Shopee</p>
              </div>
              <div class="method-radio" id="radio-shopeepay"></div>
            </div>

            <!-- Input nomor e-wallet -->
            <div id="ewalletInputBox" class="pay-input-box" style="display:block;">
              <label class="pay-input-label">
                Nomor HP / Akun <span id="ewalletBrandLabel">GoPay</span>
              </label>
              <input type="tel" id="ewalletNumber" class="pay-input" placeholder="Contoh: 08123456789" maxlength="14" />
            </div>

            <!-- QRIS -->
            <div class="method-section-label">QRIS</div>

            <div class="method-row" data-method-id="qris">
              <div class="method-logo" style="background:#E84545;">QR</div>
              <div class="method-info">
                <p class="method-name">QRIS</p>
                <p class="method-desc">Scan QR dengan semua aplikasi bank &amp; e-wallet</p>
              </div>
              <div class="method-radio" id="radio-qris"></div>
            </div>

            <!-- Transfer Bank -->
            <div class="method-section-label">Transfer Bank</div>

            <div class="method-row" data-method-id="bca">
              <div class="method-logo" style="background:#003F88;">BCA</div>
              <div class="method-info">
                <p class="method-name">BCA</p>
                <p class="method-desc">Virtual account / m-BCA / transfer manual</p>
              </div>
              <div class="method-radio" id="radio-bca"></div>
            </div>

            <div class="method-row" data-method-id="bni">
              <div class="method-logo" style="background:#F68B1F;">BNI</div>
              <div class="method-info">
                <p class="method-name">BNI</p>
                <p class="method-desc">Virtual account BNI / BNI Mobile</p>
              </div>
              <div class="method-radio" id="radio-bni"></div>
            </div>

            <div class="method-row" data-method-id="mandiri">
              <div class="method-logo" style="background:#003087; font-size:10px;">MDR</div>
              <div class="method-info">
                <p class="method-name">Mandiri</p>
                <p class="method-desc">Virtual account / Livin by Mandiri</p>
              </div>
              <div class="method-radio" id="radio-mandiri"></div>
            </div>

            <div class="method-row" data-method-id="bri">
              <div class="method-logo" style="background:#00529B;">BRI</div>
              <div class="method-info">
                <p class="method-name">BRI</p>
                <p class="method-desc">Virtual account / BRImo</p>
              </div>
              <div class="method-radio" id="radio-bri"></div>
            </div>

            <!-- Kartu Kredit/Debit -->
            <div class="method-section-label">Kartu Kredit &amp; Debit</div>

            <div class="method-row" data-method-id="card">
              <div class="method-logo" style="background:#6b7280;">
                <i class="ph-bold ph-credit-card" style="font-size:18px;"></i>
              </div>
              <div class="method-info">
                <p class="method-name">Kartu Kredit / Debit</p>
                <p class="method-desc">Visa, Mastercard, JCB, Amex</p>
              </div>
              <div class="method-radio" id="radio-card"></div>
            </div>

            <!-- Input detail kartu -->
            <div id="cardInputBox" class="pay-input-box" style="display:none;">
              <div style="margin-bottom:12px;">
                <label class="pay-input-label">Nomor Kartu</label>
                <input type="text" id="cardNumber" class="pay-input" placeholder="1234  5678  9012  3456" maxlength="19"
                  style="letter-spacing:0.08em;" />
              </div>
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                  <label class="pay-input-label">Berlaku s/d</label>
                  <input type="text" id="cardExpiry" class="pay-input" placeholder="MM/YY" maxlength="5" />
                </div>
                <div>
                  <label class="pay-input-label">CVV / CVC</label>
                  <input type="password" id="cardCvv" class="pay-input" placeholder="•••" maxlength="4" />
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

        <!-- ── Step 3: Konfirmasi ── -->
        <div class="step-card">
          <div class="expand-card-header">
            <div class="expand-step-title">3. Review &amp; konfirmasi reservasi</div>
          </div>
          <div class="expand-review-content">

            <div style="font-size:13px; color:#888; margin-bottom:16px; line-height:1.7;">
              <div><i class="ph-bold ph-calendar"></i>
                <?= $fmt_in ?> &rarr; <?= $fmt_out ?>
                (<?= $jumlah_malam ?> malam)
              </div>
              <div><i class="ph-bold ph-users"></i>
                <?= $jumlah_tamu ?> tamu
              </div>
              <?php if ($nama_kamar): ?>
                <div><i class="ph-bold ph-door"></i>
                  <?= htmlspecialchars($nama_kamar) ?>
                </div>
              <?php endif; ?>
              <?php if ($kode_promo && $promo_valid): ?>
                <div style="color:#27ae60;">
                  <i class="ph-bold ph-tag"></i>
                  Promo: <?= htmlspecialchars($kode_promo) ?>
                </div>
              <?php endif; ?>
            </div>

            <form method="POST" action="./process_booking.php" id="bookingForm">
              <input type="hidden" name="listing_id"   value="<?= $listing_id ?>">
              <input type="hidden" name="room_id"      value="<?= $room_id ?>">
              <input type="hidden" name="checkin"      value="<?= htmlspecialchars($checkin_val) ?>">
              <input type="hidden" name="checkout"     value="<?= htmlspecialchars($checkout_val) ?>">
              <input type="hidden" name="jumlah_tamu"  value="<?= $jumlah_tamu ?>">
              <input type="hidden" name="total_harga"  value="<?= $total_harga ?>">
              <input type="hidden" name="kode_promo"   value="<?= htmlspecialchars($kode_promo) ?>">
              <input type="hidden" name="metode_bayar" value="gopay" id="inputMetode">
              <input type="hidden" name="detail_bayar" value=""      id="inputDetailBayar">
              <input type="hidden" name="waktu_bayar"  value="now"   id="inputWaktuBayar">

              <button type="submit" class="expand-confirm-button" id="btnKonfirmasi">
                <i class="ph-bold ph-lock-simple"></i>
                Konfirmasi dan Bayar
              </button>
            </form>

            <p style="font-size:12px; color:#bbb; text-align:center; margin-top:12px;">
              <i class="ph-bold ph-shield-check"></i>
              Pembayaran dienkripsi dan aman. Data kartu tidak disimpan.
            </p>
          </div>
        </div>

      </section>
      <!-- /.step-section -->

      <!-- ══════════════════════════════════
           KANAN: Ringkasan Harga
      ══════════════════════════════════ -->
      <div class="expand-summary">
        <div class="expand-summary-card">

          <!-- Foto + Nama properti -->
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

          <!-- Gratis pembatalan -->
          <div class="expand-cancel-box">
            <div class="expand-cancel-title">Gratis pembatalan</div>
            <div class="expand-cancel-desc">
              Batalkan sebelum <strong><?= $batas_batal ?></strong>
              untuk pengembalian uang penuh.
            </div>
          </div>

          <!-- Tanggal -->
          <div class="expand-info-row">
            <div>
              <div class="expand-info-label">Tanggal</div>
              <div class="expand-info-value">
                <?= $fmt_in ?> &ndash; <?= $fmt_out ?>
              </div>
            </div>
            <button class="expand-change-sm" onclick="history.back()">Ganti</button>
          </div>
          <hr class="expand-divider" />

          <!-- Tamu -->
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

          <!-- Detail harga -->
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
            <div style="font-size:12px; color:#c0392b; background:#fff0f0; padding:8px 10px; border-radius:8px; margin:6px 0;">
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
      <!-- /.expand-summary -->

    </div><!-- /.content-layout -->
  </main>

  <script src="../scripts/payment_confirm.js"></script>
  <script src="../../components/navbar.js"></script>
  <script src="../../popups/auth.js"></script>

  <script>
    // ═══════════════════════════════════════════════════════
    //  DATA DARI PHP
    // ═══════════════════════════════════════════════════════
    var totalHarga   = <?= $total_harga ?>;
    var bayarNanti   = '<?= $bayar_nanti ?>';
    var totalFormatted = 'Rp<?= number_format($total_harga, 0, ',', '.') ?>';

    // ═══════════════════════════════════════════════════════
    //  PILIH KAPAN BAYAR
    // ═══════════════════════════════════════════════════════
    var selectedPayTime = 'now';

    document.querySelectorAll('.expand-radio-row').forEach(function (row) {
      row.addEventListener('click', function () {
        var val = this.dataset.selectPay;
        if (!val) return;
        selectedPayTime = val;

        document.getElementById('radio-pay-now').classList.toggle('selected', val === 'now');
        document.getElementById('radio-pay-later').classList.toggle('selected', val === 'later');
        document.getElementById('inputWaktuBayar').value = val;

        document.getElementById('paySub').textContent = val === 'now'
          ? 'Bayar ' + totalFormatted + ' sekarang'
          : 'Bayar nanti pada ' + bayarNanti;
      });
    });

    // ═══════════════════════════════════════════════════════
    //  METODE PEMBAYARAN
    // ═══════════════════════════════════════════════════════
    var selectedMethod = 'gopay';
    var ewalletMethods = ['gopay', 'ovo', 'dana', 'shopeepay'];

    var methodLabels = {
      gopay:     'GoPay',
      ovo:       'OVO',
      dana:      'DANA',
      shopeepay: 'ShopeePay',
      qris:      'QRIS',
      bca:       'Transfer BCA',
      bni:       'Transfer BNI',
      mandiri:   'Transfer Mandiri',
      bri:       'Transfer BRI',
      card:      'Kartu Kredit/Debit'
    };

    document.querySelectorAll('.method-row').forEach(function (row) {
      row.addEventListener('click', function () {
        selectMethod(this.dataset.methodId);
      });
    });

    function selectMethod(id) {
      selectedMethod = id;

      document.querySelectorAll('.method-radio').forEach(function (r) {
        r.classList.remove('selected');
      });
      var radioEl = document.getElementById('radio-' + id);
      if (radioEl) radioEl.classList.add('selected');

      document.getElementById('inputMetode').value = id;
      document.getElementById('methodSubLabel').textContent = methodLabels[id] || id;

      var cardBox   = document.getElementById('cardInputBox');
      var walletBox = document.getElementById('ewalletInputBox');

      cardBox.style.display   = (id === 'card') ? 'block' : 'none';
      walletBox.style.display = ewalletMethods.includes(id) ? 'block' : 'none';

      if (walletBox.style.display === 'block') {
        document.getElementById('ewalletBrandLabel').textContent = methodLabels[id];
      }
    }

    // ═══════════════════════════════════════════════════════
    //  FORMAT INPUT
    // ═══════════════════════════════════════════════════════

    // Format nomor kartu (spasi tiap 4 digit)
    document.getElementById('cardNumber').addEventListener('input', function () {
      var v = this.value.replace(/\D/g, '').slice(0, 16);
      var parts = [];
      for (var i = 0; i < v.length; i += 4) parts.push(v.slice(i, i + 4));
      this.value = parts.join('  ');
    });

    // Format expiry MM/YY
    document.getElementById('cardExpiry').addEventListener('input', function () {
      var v = this.value.replace(/\D/g, '').slice(0, 4);
      if (v.length > 2) v = v.slice(0, 2) + '/' + v.slice(2);
      this.value = v;
    });

    // Format nomor HP (hanya angka)
    document.getElementById('ewalletNumber').addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 14);
    });

    // ═══════════════════════════════════════════════════════
    //  SUBMIT: kumpulkan detail & validasi
    // ═══════════════════════════════════════════════════════
    document.getElementById('bookingForm').addEventListener('submit', function (e) {
      var detail = {};

      if (selectedMethod === 'card') {
        var nomor  = document.getElementById('cardNumber').value.replace(/\s/g, '');
        var expiry = document.getElementById('cardExpiry').value;
        var nama   = document.getElementById('cardName').value.trim();

        if (nomor.length < 13) {
          e.preventDefault();
          alert('Masukkan nomor kartu yang valid.');
          return;
        }
        if (!expiry || expiry.length < 5) {
          e.preventDefault();
          alert('Masukkan tanggal berlaku kartu (MM/YY).');
          return;
        }
        if (!nama) {
          e.preventDefault();
          alert('Masukkan nama yang tertera di kartu.');
          return;
        }
        detail = { nomor: nomor, expiry: expiry, nama: nama };

      } else if (ewalletMethods.includes(selectedMethod)) {
        var hp = document.getElementById('ewalletNumber').value.trim();
        if (!hp || hp.length < 8) {
          e.preventDefault();
          alert('Masukkan nomor HP / akun ' + methodLabels[selectedMethod] + ' yang valid.');
          return;
        }
        detail = { nomor_hp: hp };
      }

      document.getElementById('inputDetailBayar').value = JSON.stringify(detail);

      // Disable tombol agar tidak double-submit
      var btn = document.getElementById('btnKonfirmasi');
      btn.disabled = true;
      btn.innerHTML = '<i class="ph-bold ph-spinner"></i> Memproses...';
    });
  </script>

</body>
</html>