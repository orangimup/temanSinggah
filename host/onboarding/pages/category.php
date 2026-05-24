<?php
session_start();
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tipe Properti | Teman Singgah</title>
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

    <main class="main-content wide">
      <div class="page-header">
        <h2>Properti Anda termasuk yang mana?</h2>
        <p>Pilih kategori yang paling menggambarkan properti Anda.</p>
      </div>

      <div class="category-grid">
        <label class="category-item">
          <input type="radio" name="category" value="apartemen" <?= (isset($_SESSION['onboarding']['tipe_properti']) && $_SESSION['onboarding']['tipe_properti'] === 'apartemen') ? 'checked' : '' ?> />
          <div class="category-card">
            <div class="category-icon"><i class="ph-bold ph-buildings"></i></div>
            <h3>Apartemen</h3>
          </div>
        </label>
        <label class="category-item">
          <input type="radio" name="category" value="hotel" <?= (isset($_SESSION['onboarding']['tipe_properti']) && $_SESSION['onboarding']['tipe_properti'] === 'hotel') ? 'checked' : '' ?> />
          <div class="category-card">
            <div class="category-icon"><i class="ph-bold ph-building"></i></div>
            <h3>Hotel</h3>
          </div>
        </label>
        <label class="category-item">
          <input type="radio" name="category" value="cabin" <?= (isset($_SESSION['onboarding']['tipe_properti']) && $_SESSION['onboarding']['tipe_properti'] === 'cabin') ? 'checked' : '' ?> />
          <div class="category-card">
            <div class="category-icon"><i class="ph-bold ph-tree-evergreen"></i></div>
            <h3>Pondok / Cabin</h3>
          </div>
        </label>
        <label class="category-item">
          <input type="radio" name="category" value="rumah" <?= (isset($_SESSION['onboarding']['tipe_properti']) && $_SESSION['onboarding']['tipe_properti'] === 'rumah') ? 'checked' : '' ?> />
          <div class="category-card">
            <div class="category-icon"><i class="ph-bold ph-house"></i></div>
            <h3>Rumah</h3>
          </div>
        </label>
        <label class="category-item">
          <input type="radio" name="category" value="tradisional" <?= (isset($_SESSION['onboarding']['tipe_properti']) && $_SESSION['onboarding']['tipe_properti'] === 'tradisional') ? 'checked' : '' ?> />
          <div class="category-card">
            <div class="category-icon"><i class="ph-bold ph-storefront"></i></div>
            <h3>Rumah Tradisional</h3>
          </div>
        </label>
        <label class="category-item">
          <input type="radio" name="category" value="villa" <?= (isset($_SESSION['onboarding']['tipe_properti']) && $_SESSION['onboarding']['tipe_properti'] === 'villa') ? 'checked' : '' ?> />
          <div class="category-card">
            <div class="category-icon"><i class="ph-bold ph-house-line"></i></div>
            <h3>Villa</h3>
          </div>
        </label>
      </div>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment"></div>
        <div class="progress-segment"></div>
      </div>
      <div class="footer-actions">
        <a href="about_place.html" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      document.getElementById('btnSelanjutnya').addEventListener('click', function () {
        const selected = document.querySelector('input[name="category"]:checked');
        if (!selected) {
          alert('Pilih salah satu tipe properti dulu ya!');
          return;
        }

        fetch('save_category.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tipe_properti: selected.value })
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = 'privacy_type.php';
          }
        });
      });
    </script>
  </body>
</html>