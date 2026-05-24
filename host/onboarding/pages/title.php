<?php
session_start();
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Judul Properti | Teman Singgah</title>
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
        <h2>Sekarang, beri judul untuk properti Anda</h2>
        <p>Judul yang singkat bekerja paling baik. Anda selalu bisa mengubahnya nanti.</p>
      </div>

      <div class="textarea-container">
        <textarea id="judulInput" maxlength="100" placeholder="Contoh: Villa Sunset nyaman di tepi pantai..."><?= isset($_SESSION['onboarding']['judul']) ? htmlspecialchars($_SESSION['onboarding']['judul']) : '' ?></textarea>
      </div>
      <p class="char-count"><span id="charCount">0</span>/100</p>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment"></div>
        <div class="progress-segment"></div>
      </div>
      <div class="footer-actions">
        <a href="photos_preview.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      const textarea = document.getElementById('judulInput');
      const charCount = document.getElementById('charCount');

      charCount.textContent = textarea.value.length;

      textarea.addEventListener('input', function () {
        charCount.textContent = this.value.length;
      });

      document.getElementById('btnSelanjutnya').addEventListener('click', function () {
        const judul = textarea.value.trim();
        if (!judul) {
          alert('Judul properti tidak boleh kosong!');
          return;
        }

        fetch('save_title.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ judul: judul })
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = 'description.php';
          }
        });
      });
    </script>
  </body>
</html>