<?php
session_start();
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detail Dasar | Teman Singgah</title>
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
        <h2>Berikan beberapa detail dasar tentang tempat Anda</h2>
        <p>Anda dapat menambahkan detail lebih lanjut nanti, seperti tipe tempat tidur.</p>
      </div>

      <div class="counter-list">
        <div class="counter-row" data-min="1" data-max="16" data-key="max_tamu">
          <h3 class="counter-label">Tamu</h3>
          <div class="counter-control">
            <button type="button" class="counter-button minus">−</button>
            <span class="counter-value"><?= isset($_SESSION['onboarding']['max_tamu']) ? $_SESSION['onboarding']['max_tamu'] : 1 ?></span>
            <button type="button" class="counter-button plus">+</button>
          </div>
        </div>
        <div class="counter-row" data-min="1" data-max="8" data-key="kamar_tidur">
          <h3 class="counter-label">Kamar tidur</h3>
          <div class="counter-control">
            <button type="button" class="counter-button minus">−</button>
            <span class="counter-value"><?= isset($_SESSION['onboarding']['kamar_tidur']) ? $_SESSION['onboarding']['kamar_tidur'] : 1 ?></span>
            <button type="button" class="counter-button plus">+</button>
          </div>
        </div>
        <div class="counter-row" data-min="1" data-max="8" data-key="tempat_tidur">
          <h3 class="counter-label">Tempat tidur</h3>
          <div class="counter-control">
            <button type="button" class="counter-button minus">−</button>
            <span class="counter-value"><?= isset($_SESSION['onboarding']['tempat_tidur']) ? $_SESSION['onboarding']['tempat_tidur'] : 1 ?></span>
            <button type="button" class="counter-button plus">+</button>
          </div>
        </div>
        <div class="counter-row" data-min="1" data-max="8" data-key="kamar_mandi">
          <h3 class="counter-label">Kamar mandi</h3>
          <div class="counter-control">
            <button type="button" class="counter-button minus">−</button>
            <span class="counter-value"><?= isset($_SESSION['onboarding']['kamar_mandi']) ? $_SESSION['onboarding']['kamar_mandi'] : 1 ?></span>
            <button type="button" class="counter-button plus">+</button>
          </div>
        </div>
      </div>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment"></div>
        <div class="progress-segment"></div>
      </div>
      <div class="footer-actions">
        <a href="location.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      document.querySelectorAll('.counter-row').forEach(row => {
        const min = parseInt(row.dataset.min);
        const max = parseInt(row.dataset.max);
        const valueEl = row.querySelector('.counter-value');
        const minusBtn = row.querySelector('.minus');
        const plusBtn = row.querySelector('.plus');

        function update() {
          const val = parseInt(valueEl.textContent);
          minusBtn.classList.toggle('disabled', val <= min);
          plusBtn.classList.toggle('disabled', val >= max);
        }

        minusBtn.addEventListener('click', () => {
          const val = parseInt(valueEl.textContent);
          if (val > min) { valueEl.textContent = val - 1; update(); }
        });

        plusBtn.addEventListener('click', () => {
          const val = parseInt(valueEl.textContent);
          if (val < max) { valueEl.textContent = val + 1; update(); }
        });

        update();
      });

      document.getElementById('btnSelanjutnya').addEventListener('click', function () {
        const data = {};
        document.querySelectorAll('.counter-row').forEach(row => {
          data[row.dataset.key] = parseInt(row.querySelector('.counter-value').textContent);
        });

        fetch('save_floor_plan.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(data)
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = 'stand_out.html';
          }
        });
      });
    </script>
  </body>
</html>