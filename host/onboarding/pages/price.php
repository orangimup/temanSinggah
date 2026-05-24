<?php
session_start();
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tetapkan Harga | Teman Singgah</title>
    <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />

    <link rel="stylesheet" href="../../../components/root.css" />
    <link rel="stylesheet" href="../../../components/navbar.css" />
    <link rel="stylesheet" href="../onboarding.css" />

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  </head>

  <body class="onboarding-page">
    <header class="navbar">
      <nav class="navbar-container">
        <a href="../../../index.php" class="logo-link"></a>
        <div class="logo-section">
          <img src="../../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
          <img src="../../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
        </div>
        <div class="header-actions">
          <a href="../../../user/pages/messages.html"><button class="ghost-button">Pertanyaan?</button></a>
          <a href="../../../index.php"><button class="ghost-button">Simpan & keluar</button></a>
        </div>
      </nav>
    </header>

    <main class="main-content">
      <div class="page-header">
        <h2>Sekarang, tetapkan harga Anda</h2>
        <p>Anda bisa mengubahnya kapan saja. Harga ditampilkan dalam Rupiah per malam.</p>
      </div>

      <div class="price-display">
        <span class="price-display-currency">Rp</span>
        <span class="price-display-value" id="priceDisplay"><?= isset($_SESSION['onboarding']['harga_malam']) ? number_format($_SESSION['onboarding']['harga_malam'], 0, ',', '.') : '350.000' ?></span>
        <p class="price-display-unit">per malam</p>
      </div>

      <div class="price-slider-container">
        <div class="slider-label">
          <span>Geser untuk mengatur harga</span>
          <div class="price-input-container">
            <span>Rp</span>
            <input class="price-input" type="text" id="priceInput" value="<?= isset($_SESSION['onboarding']['harga_malam']) ? number_format($_SESSION['onboarding']['harga_malam'], 0, ',', '.') : '350.000' ?>" />
            <span>/ malam</span>
          </div>
        </div>
        <input type="range" id="priceSlider" min="100000" max="5000000" step="50000"
          value="<?= isset($_SESSION['onboarding']['harga_malam']) ? $_SESSION['onboarding']['harga_malam'] : 350000 ?>"
          class="price-slider" />
        <div class="slider-hints">
          <span>Rp 100.000</span>
          <span>Rp 5.000.000</span>
        </div>
      </div>

      <div class="info-section">
        <i class="ph-bold ph-info"></i>
        <p>Properti serupa di area Anda rata-rata dihargai <strong>Rp 280.000 – Rp 480.000</strong> per malam. Harga kompetitif membantu Anda mendapatkan pemesanan pertama lebih cepat.</p>
      </div>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment completed"></div>
        <div class="progress-segment active"></div>
      </div>
      <div class="footer-actions">
        <a href="booking_settings.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      const slider = document.getElementById('priceSlider');
      const display = document.getElementById('priceDisplay');
      const input = document.getElementById('priceInput');

      function formatRupiah(angka) {
        return parseInt(angka).toLocaleString('id-ID');
      }

      function parseRupiah(str) {
        return parseInt(str.replace(/\./g, '')) || 0;
      }

      slider.addEventListener('input', function () {
        const val = formatRupiah(this.value);
        display.textContent = val;
        input.value = val;
      });

      input.addEventListener('input', function () {
        const raw = parseRupiah(this.value);
        if (raw >= 100000 && raw <= 5000000) {
          slider.value = raw;
          display.textContent = formatRupiah(raw);
        }
      });

      input.addEventListener('blur', function () {
        const raw = parseRupiah(this.value);
        const clamped = Math.max(100000, Math.min(5000000, raw));
        slider.value = clamped;
        display.textContent = formatRupiah(clamped);
        this.value = formatRupiah(clamped);
      });

      document.getElementById('btnSelanjutnya').addEventListener('click', function () {
        const harga = parseRupiah(input.value);
        if (harga < 100000 || harga > 5000000) {
          alert('Harga harus antara Rp 100.000 sampai Rp 5.000.000!');
          return;
        }

        fetch('save_price.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ harga_malam: harga })
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = 'discount.php';
          }
        });
      });
    </script>
  </body>
</html>