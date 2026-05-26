<?php
session_start();

$fasilitas_list = [
    'wifi'         => ['label' => 'Wi-Fi',                  'icon' => 'ph-wifi-high'],
    'tv'           => ['label' => 'TV',                     'icon' => 'ph-television'],
    'ac'           => ['label' => 'AC / Pendingin Ruangan', 'icon' => 'ph-thermometer-hot'],
    'dapur'        => ['label' => 'Dapur',                  'icon' => 'ph-cooking-pot'],
    'mesin_cuci'   => ['label' => 'Mesin Cuci',             'icon' => 'ph-washing-machine'],
    'parkir'       => ['label' => 'Parkir Gratis',          'icon' => 'ph-car'],
    'kolam_renang' => ['label' => 'Kolam Renang',           'icon' => 'ph-swimming-pool'],
    'p3k'          => ['label' => 'Kotak P3K',              'icon' => 'ph-first-aid-kit'],
    'pemadam'      => ['label' => 'Alat Pemadam',           'icon' => 'ph-fire-extinguisher'],
    'air_panas'    => ['label' => 'Shower Air Panas',       'icon' => 'ph-shower'],
    'ruang_kerja'  => ['label' => 'Ruang Kerja',            'icon' => 'ph-desk'],
    'hewan'        => ['label' => 'Ramah Hewan Peliharaan', 'icon' => 'ph-dog'],
];

$saved = isset($_SESSION['onboarding']['fasilitas']) ? $_SESSION['onboarding']['fasilitas'] : [];
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Fasilitas | Teman Singgah</title>
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
        <h2>Ceritakan fasilitas yang Anda tawarkan</h2>
        <p>Pilih semua fasilitas yang tersedia. Anda selalu bisa menambah lebih banyak nanti.</p>
      </div>

      <div class="amenities-grid">
        <?php foreach ($fasilitas_list as $key => $item): ?>
        <label class="amenity-item">
          <input type="checkbox" name="fasilitas[]" value="<?= $key ?>"
            <?= in_array($key, $saved) ? 'checked' : '' ?> />
          <div class="amenity-card">
            <div class="amenity-icon"><i class="ph-bold <?= $item['icon'] ?>"></i></div>
            <h3><?= $item['label'] ?></h3>
          </div>
        </label>
        <?php endforeach; ?>
      </div>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment"></div>
        <div class="progress-segment"></div>
      </div>
      <div class="footer-actions">
        <a href="stand_out.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      document.getElementById('btnSelanjutnya').addEventListener('click', function () {
        const checked = [...document.querySelectorAll('input[name="fasilitas[]"]:checked')]
          .map(el => el.value);

        fetch('save_amenities.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ fasilitas: checked })
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = 'rooms.php';
          }
        });
      });
    </script>
  </body>
</html>