<?php
session_start();
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Keamanan & Peraturan | Teman Singgah</title>
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
        <h2>Tinggal selangkah lagi!</h2>
        <p>Konfirmasi bahwa properti Anda memenuhi standar keamanan dan kenyamanan dasar kami.</p>
      </div>

      <div class="safety-list">
        <label class="safety-item">
          <input type="checkbox" name="safety[]" value="detektor_asap" />
          <div class="safety-text">
            <h3>Detektor asap terpasang</h3>
            <p>Ada minimal satu detektor asap yang berfungsi di properti.</p>
          </div>
          <div class="check-mark"><i class="ph-bold ph-check"></i></div>
        </label>
        <label class="safety-item">
          <input type="checkbox" name="safety[]" value="alat_pemadam" />
          <div class="safety-text">
            <h3>Alat pemadam kebakaran tersedia</h3>
            <p>Ada alat pemadam api ringan yang mudah dijangkau oleh tamu.</p>
          </div>
          <div class="check-mark"><i class="ph-bold ph-check"></i></div>
        </label>
        <label class="safety-item">
          <input type="checkbox" name="safety[]" value="kotak_p3k" />
          <div class="safety-text">
            <h3>Kotak P3K tersedia</h3>
            <p>Ada kotak pertolongan pertama dasar di dalam properti.</p>
          </div>
          <div class="check-mark"><i class="ph-bold ph-check"></i></div>
        </label>
        <label class="safety-item">
          <input type="checkbox" name="safety[]" value="properti_layak" />
          <div class="safety-text">
            <h3>Properti layak huni dan bersih</h3>
            <p>Kondisi properti sesuai dengan foto yang diunggah, bersih, aman, dan terawat.</p>
          </div>
          <div class="check-mark"><i class="ph-bold ph-check"></i></div>
        </label>
        <label class="safety-item">
          <input type="checkbox" name="safety[]" value="no_kamera" />
          <div class="safety-text">
            <h3>Tidak ada kamera tersembunyi di area privat</h3>
            <p>Tidak ada perangkat perekam tersembunyi di kamar tidur, kamar mandi, atau area pribadi.</p>
          </div>
          <div class="check-mark"><i class="ph-bold ph-check"></i></div>
        </label>
      </div>

      <div class="info-section">
        <i class="ph-bold ph-info"></i>
        <p>Standar keamanan dasar membantu melindungi tamu dan host. Properti yang memenuhi standar ini mendapatkan lebih banyak kepercayaan dari tamu.</p>
      </div>

      <div id="pesanError" style="display:none;color:var(--color-text-danger);margin-top:12px;font-size:14px;"></div>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment completed"></div>
        <div class="progress-segment completed"></div>
      </div>
      <div class="footer-actions">
        <a href="discount.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelesai">Selesaikan listing</button>
      </div>
    </footer>

    <script>
      document.getElementById('btnSelesai').addEventListener('click', function () {
        const checked = document.querySelectorAll('input[name="safety[]"]:checked');
        const total = document.querySelectorAll('input[name="safety[]"]');
        const pesan = document.getElementById('pesanError');

        if (checked.length < total.length) {
          pesan.style.display = 'block';
          pesan.textContent = 'Centang semua pernyataan keamanan untuk melanjutkan.';
          return;
        }

        pesan.style.display = 'none';
        this.disabled = true;
        this.textContent = 'Menyimpan...';

        fetch('save_listing.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({})
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = '/teman_singgah/host/dashboard/pages/listing.php';
          } else {
            pesan.style.display = 'block';
            pesan.textContent = 'Gagal menyimpan: ' + data.message;
            this.disabled = false;
            this.textContent = 'Selesaikan listing';
          }
        });
      });
    </script>
  </body>
</html>