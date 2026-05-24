<?php
session_start();

if (empty($_SESSION['onboarding']['foto'])) {
    header('Location: photos.php');
    exit();
}

$foto = $_SESSION['onboarding']['foto'];
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pratinjau Foto | Teman Singgah</title>
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
        <div>
          <h2>Ta-da! Bagaimana tampilannya?</h2>
          <p>Foto pertama akan menjadi foto cover. Seret untuk mengurutkan ulang.</p>
        </div>
      </div>

      <div class="photo-grid" id="photoGrid">
        <?php foreach ($foto as $index => $namaFile): ?>
        <div class="photo-item" draggable="true" data-index="<?= $index ?>">
          <img
            src="/teman_singgah/assets/uploads/listings/<?= htmlspecialchars($namaFile) ?>"
            alt="Foto <?= $index + 1 ?>"
            style="width:100%;height:160px;object-fit:cover;border-radius:8px;" />
          <?php if ($index === 0): ?>
          <span style="position:absolute;top:8px;left:8px;background:rgba(0,0,0,0.6);color:#fff;font-size:12px;padding:2px 8px;border-radius:4px;">Cover</span>
          <?php endif; ?>
          <button class="hapus-foto" data-index="<?= $index ?>" style="position:absolute;top:8px;right:8px;background:rgba(0,0,0,0.5);color:#fff;border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;display:flex;align-items:center;justify-content:center;">
            <i class="ph-bold ph-x"></i>
          </button>
        </div>
        <?php endforeach; ?>
      </div>

      <button class="ghost-button" style="margin-top:16px;" onclick="window.location.href='photos.php'">
        <i class="ph-bold ph-plus"></i> Tambah foto lagi
      </button>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment"></div>
        <div class="progress-segment"></div>
      </div>
      <div class="footer-actions">
        <a href="photos.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      let fotoOrder = <?= json_encode($foto) ?>;

      const grid = document.getElementById('photoGrid');
      let dragSrc = null;

      grid.querySelectorAll('.photo-item').forEach(item => {
        item.addEventListener('dragstart', function () { dragSrc = this; });
        item.addEventListener('dragover', function (e) { e.preventDefault(); });
        item.addEventListener('drop', function (e) {
          e.preventDefault();
          if (dragSrc === this) return;

          const fromIndex = parseInt(dragSrc.dataset.index);
          const toIndex = parseInt(this.dataset.index);

          const moved = fotoOrder.splice(fromIndex, 1)[0];
          fotoOrder.splice(toIndex, 0, moved);

          fetch('save_photos_preview.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ foto: fotoOrder })
          })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'ok') location.reload();
          });
        });
      });

      document.querySelectorAll('.hapus-foto').forEach(btn => {
        btn.addEventListener('click', function () {
          const index = parseInt(this.dataset.index);
          fotoOrder.splice(index, 1);

          fetch('save_photos_preview.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ foto: fotoOrder })
          })
          .then(res => res.json())
          .then(data => {
            if (data.status === 'ok') location.reload();
          });
        });
      });

      document.getElementById('btnSelanjutnya').addEventListener('click', function () {
        if (fotoOrder.length < 5) {
          alert('Minimal 5 foto diperlukan!');
          return;
        }
        window.location.href = 'title.php';
      });
    </script>
  </body>
</html>