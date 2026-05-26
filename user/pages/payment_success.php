<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Konfirmasi & Pembayaran | Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />

  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../../components/navbar.css" />
  <link rel="stylesheet" href="../../components/search_bar.css" />
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
        <li class="nav-item">
          <a href="../../index.php" class="nav-link">Cari Penginapan</a>
        </li>
        <li class="nav-item">
          <a href="./promo_deals.php" class="nav-link">Promo & Deals</a>
        </li>
        <li class="nav-item">
          <a href="./become_host.php" class="nav-link">Jadi Host</a>
        </li>
        <li class="nav-item">
          <a href="./about_us.php" class="nav-link">Tentang Kami</a>
        </li>
        <div class="nav-indicator"></div>
      </ul>

      <div class="nav-right">
        <a href="../../host/onboarding/pages/about_place.html">
          <button class="ghost-button">Ganti ke host</button>
        </a>
        <div class="icon-buttons">
          <button class="icon-button profile" aria-label="Profile">A</button>
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

  <main class="success-layout">
    <section class="success-main">
      <div class="success-hero">
        <div class="success-icon-wrap">
          <i class="ph-fill ph-check-circle"></i>
        </div>
        <h1 class="success-title">Booking Berhasil!</h1>
        <p class="success-desc">
          Pembayaranmu telah dikonfirmasi sistem. Detail reservasi telah tercatat di dashboard dan email konfirmasi
          telah dikirim.
        </p>
      </div>

      <div class="receipt-card">
        <div class="receipt-header">
          <div class="receipt-id-block">
            <span class="receipt-label">ID RESERVASI</span>
            <div class="receipt-id-row">
              <span class="receipt-id" id="reservationId">#RSV-2026-0044</span>
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
            <span class="receipt-value">Soren</span>
          </div>
          <div class="receipt-item">
            <span class="receipt-label">Akomodasi</span>
            <span class="receipt-value">Villa Damai Sejahtera</span>
          </div>
          <div class="receipt-item">
            <span class="receipt-label">Tipe Kamar</span>
            <span class="receipt-value">Deluxe Room Suite</span>
          </div>
          <div class="receipt-item">
            <span class="receipt-label">Check-In</span>
            <span class="receipt-value">22 Mei 2026</span>
          </div>
          <div class="receipt-item">
            <span class="receipt-label">Check-Out</span>
            <span class="receipt-value">24 Mei 2026 (2 Malam)</span>
          </div>
          <div class="receipt-item">
            <span class="receipt-label">Jumlah Tamu</span>
            <span class="receipt-value">2 Orang</span>
          </div>
        </div>

        <hr class="receipt-line" />

        <div class="receipt-footer">
          <div class="receipt-price-block">
            <span class="receipt-label">Total Pembayaran</span>
            <span class="receipt-total">Rp 1.250.000</span>
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

  <script src="../scripts/payment_success.js"></script>
</body>

</html>