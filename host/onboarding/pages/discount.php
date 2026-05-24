<?php
session_start();

$saved = isset($_SESSION['onboarding']['diskon']) ? $_SESSION['onboarding']['diskon'] : [];
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambahkan Diskon | Teman Singgah</title>
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
        <h2>Tambahkan diskon untuk menarik tamu pertama</h2>
        <p>Host baru yang menawarkan diskon awal cenderung mendapatkan ulasan lebih cepat dan lebih banyak pemesanan. Anda bisa menonaktifkan ini kapan saja.</p>
      </div>

      <div class="discount-list">
        <label class="discount-item">
          <input type="checkbox" name="diskon[]" value="tamu_baru"
            <?= in_array('tamu_baru', $saved) ? 'checked' : '' ?> />
          <div class="discount-card">
            <div class="discount-badge">20%</div>
            <div class="discount-text">
              <h3>Diskon Tamu Baru</h3>
              <p>Berikan diskon 20% untuk 3 pemesanan pertama Anda. Cara terbaik untuk membangun ulasan awal.</p>
            </div>
            <div class="check-mark"><i class="ph-bold ph-check"></i></div>
          </div>
        </label>

        <label class="discount-item">
          <input type="checkbox" name="diskon[]" value="mingguan"
            <?= in_array('mingguan', $saved) ? 'checked' : '' ?> />
          <div class="discount-card">
            <div class="discount-badge">10%</div>
            <div class="discount-text">
              <h3>Diskon Mingguan</h3>
              <p>Diskon otomatis untuk tamu yang memesan 7 malam atau lebih. Cocok untuk meningkatkan tingkat hunian.</p>
            </div>
            <div class="check-mark"><i class="ph-bold ph-check"></i></div>
          </div>
        </label>

        <label class="discount-item">
          <input type="checkbox" name="diskon[]" value="bulanan"
            <?= in_array('bulanan', $saved) ? 'checked' : '' ?> />
          <div class="discount-card">
            <div class="discount-badge">15%</div>
            <div class="discount-text">
              <h3>Diskon Bulanan</h3>
              <p>Diskon otomatis untuk tamu yang memesan 28 malam atau lebih. Ideal untuk hunian jangka panjang.</p>
            </div>
            <div class="check-mark"><i class="ph-bold ph-check"></i></div>
          </div>
        </label>
      </div>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment completed"></div>
        <div class="progress-segment active"></div>
      </div>
      <div class="footer-actions">
        <a href="price.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      document.getElementById('btnSelanjutnya').addEventListener('click', function () {
        const checked = [...document.querySelectorAll('input[name="diskon[]"]:checked')]
          .map(el => el.value);

        fetch('save_discount.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ diskon: checked })
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = 'safety.php';
          }
        });
      });
    </script>
  </body>
</html>