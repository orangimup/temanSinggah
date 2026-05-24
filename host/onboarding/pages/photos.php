<?php
session_start();
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tambah Foto | Teman Singgah</title>
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
        <h2>Tambahkan beberapa foto properti Anda</h2>
        <p>Anda membutuhkan minimal 5 foto untuk memulai. Anda dapat menambahkan lebih banyak atau mengubahnya nanti.</p>
      </div>

      <label class="photo-upload-area" id="uploadArea">
        <input type="file" id="fotoInput" accept="image/*" multiple />
        <div class="upload-icon"><i class="ph-bold ph-camera"></i></div>
        <span class="upload-text">Pilih dari perangkat Anda</span>
      </label>

      <div id="previewGrid" class="photo-grid" style="margin-top: 24px;"></div>
      <p id="fotoCount" style="margin-top: 8px; font-size: 13px; color: var(--color-text-secondary);"></p>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment"></div>
        <div class="progress-segment"></div>
      </div>
      <div class="footer-actions">
        <a href="amenities.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      let selectedFiles = [];

      document.getElementById('fotoInput').addEventListener('change', function () {
        const newFiles = Array.from(this.files);
        selectedFiles = [...selectedFiles, ...newFiles];
        renderPreview();
      });

      function renderPreview() {
        const grid = document.getElementById('previewGrid');
        const count = document.getElementById('fotoCount');
        grid.innerHTML = '';

        selectedFiles.forEach((file, index) => {
          const reader = new FileReader();
          reader.onload = function (e) {
            const wrapper = document.createElement('div');
            wrapper.style.cssText = 'position:relative;display:inline-block;';

            const img = document.createElement('img');
            img.src = e.target.result;
            img.style.cssText = 'width:160px;height:120px;object-fit:cover;border-radius:8px;';

            const hapus = document.createElement('button');
            hapus.innerHTML = '<i class="ph-bold ph-x"></i>';
            hapus.style.cssText = 'position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.5);color:#fff;border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;display:flex;align-items:center;justify-content:center;';
            hapus.addEventListener('click', () => {
              selectedFiles.splice(index, 1);
              renderPreview();
            });

            wrapper.appendChild(img);
            wrapper.appendChild(hapus);
            grid.appendChild(wrapper);
          };
          reader.readAsDataURL(file);
        });

        count.textContent = selectedFiles.length > 0
          ? `${selectedFiles.length} foto dipilih${selectedFiles.length < 5 ? ` — butuh minimal ${5 - selectedFiles.length} foto lagi` : ''}`
          : '';
      }

      document.getElementById('btnSelanjutnya').addEventListener('click', function () {
        if (selectedFiles.length < 5) {
          alert('Upload minimal 5 foto dulu ya!');
          return;
        }

        const formData = new FormData();
        selectedFiles.forEach((file, index) => {
          formData.append('foto[]', file);
        });

        fetch('save_photos.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = 'photos_preview.php';
          } else {
            alert('Gagal upload foto: ' + data.message);
          }
        });
      });
    </script>
  </body>
</html>