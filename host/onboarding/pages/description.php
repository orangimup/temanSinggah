<?php
session_start();
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Deskripsi Properti | Teman Singgah</title>
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
        <h2>Buat deskripsi properti Anda</h2>
        <p>Bagikan apa yang membuat tempat Anda istimewa. Tamu akan membaca ini sebelum memesan.</p>
      </div>

      <div class="textarea-container tall">
        <textarea id="deskripsiInput" maxlength="500" placeholder="Ceritakan suasana tempat Anda, apa yang bisa dilakukan tamu di sekitar lokasi, dan apa yang membuat pengalaman menginap di sini tak terlupakan..."><?= isset($_SESSION['onboarding']['deskripsi']) ? htmlspecialchars($_SESSION['onboarding']['deskripsi']) : '' ?></textarea>
      </div>
      <p class="char-count"><span id="charCount">0</span>/500</p>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment"></div>
        <div class="progress-segment"></div>
      </div>
      <div class="footer-actions">
        <a href="title.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      const textarea = document.getElementById('deskripsiInput');
      const charCount = document.getElementById('charCount');

      charCount.textContent = textarea.value.length;

      textarea.addEventListener('input', function () {
        charCount.textContent = this.value.length;
      });

      document.getElementById('btnSelanjutnya').addEventListener('click', function () {
        const deskripsi = textarea.value.trim();
        if (!deskripsi) {
          alert('Deskripsi properti tidak boleh kosong!');
          return;
        }

        fetch('save_description.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ deskripsi: deskripsi })
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = 'finish.html';
          }
        });
      });
    </script>
  </body>
</html>