<?php
session_start();
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tipe Tempat | Teman Singgah</title>
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
        <h2>Tamu akan mendapatkan tipe tempat yang mana?</h2>
      </div>

      <div class="option-list">
        <label class="option-item">
          <input type="radio" name="place-type" value="seluruh" <?= (isset($_SESSION['onboarding']['tipe_privasi']) && $_SESSION['onboarding']['tipe_privasi'] === 'seluruh') ? 'checked' : '' ?> />
          <div class="option-card">
            <div class="option-text">
              <h3>Seluruh tempat</h3>
              <p>Tamu memiliki akses penuh ke seluruh properti untuk diri mereka sendiri.</p>
            </div>
            <div class="option-icon"><i class="ph-bold ph-house-line"></i></div>
          </div>
        </label>

        <label class="option-item">
          <input type="radio" name="place-type" value="kamar" <?= (isset($_SESSION['onboarding']['tipe_privasi']) && $_SESSION['onboarding']['tipe_privasi'] === 'kamar') ? 'checked' : '' ?> />
          <div class="option-card">
            <div class="option-text">
              <h3>Sebuah kamar</h3>
              <p>Tamu memiliki kamar pribadi di dalam rumah, plus akses ke area bersama.</p>
            </div>
            <div class="option-icon"><i class="ph-bold ph-door"></i></div>
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
        <a href="category.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      document.getElementById('btnSelanjutnya').addEventListener('click', function () {
        const selected = document.querySelector('input[name="place-type"]:checked');
        if (!selected) {
          alert('Pilih salah satu tipe tempat dulu ya!');
          return;
        }

        fetch('save_privacy_type.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tipe_privasi: selected.value })
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = 'location.php';
          }
        });
      });
    </script>
  </body>
</html>