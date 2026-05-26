<?php
session_start();

$saved = isset($_SESSION['onboarding']['policies']) ? $_SESSION['onboarding']['policies'] : [];

$checkin  = $saved['jam_checkin']  ?? '14:00';
$checkout = $saved['jam_checkout'] ?? '12:00';
$pembatalan = $saved['kebijakan_pembatalan'] ?? 'Gratis hingga 24 jam sebelum check-in';
$boleh_hewan   = isset($saved['boleh_hewan'])   ? (bool)$saved['boleh_hewan']   : false;
$boleh_merokok = isset($saved['boleh_merokok']) ? (bool)$saved['boleh_merokok'] : false;
$boleh_anak    = isset($saved['boleh_anak'])    ? (bool)$saved['boleh_anak']    : true;
$catatan       = $saved['catatan_tambahan'] ?? '';

$pembatalan_options = [
  'Gratis hingga 24 jam sebelum check-in',
  'Gratis hingga 48 jam sebelum check-in',
  'Gratis hingga 7 hari sebelum check-in',
  'Tidak dapat dibatalkan',
  'Bisa dibatalkan kapan saja',
];
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kebijakan Penginapan | Teman Singgah</title>
    <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />

    <link rel="stylesheet" href="../../../components/root.css" />
    <link rel="stylesheet" href="../../../components/navbar.css" />
    <link rel="stylesheet" href="../onboarding.css" />
    <link rel="stylesheet" href="../policies.css" />

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
        <h2>Atur kebijakan penginapan Anda</h2>
        <p>Beri tahu tamu tentang peraturan dan kebijakan yang berlaku di properti Anda.</p>
      </div>

      <div class="policy-form">

        <!-- Check-in & Check-out -->
        <div class="policy-section-group">
          <h3 class="policy-group-title">
            <i class="ph-bold ph-clock"></i> Jam Check-in & Check-out
          </h3>
          <div class="policy-time-row">
            <div class="policy-field">
              <label for="jamCheckin">Check-in (mulai dari)</label>
              <input type="time" id="jamCheckin" value="<?= htmlspecialchars($checkin) ?>" />
            </div>
            <div class="policy-field">
              <label for="jamCheckout">Check-out (sebelum)</label>
              <input type="time" id="jamCheckout" value="<?= htmlspecialchars($checkout) ?>" />
            </div>
          </div>
        </div>

        <!-- Pembatalan -->
        <div class="policy-section-group">
          <h3 class="policy-group-title">
            <i class="ph-bold ph-prohibit"></i> Kebijakan Pembatalan
          </h3>
          <div class="policy-options-grid">
            <?php foreach ($pembatalan_options as $opt): ?>
              <label class="policy-option-card">
                <input type="radio" name="kebijakan_pembatalan" value="<?= htmlspecialchars($opt) ?>"
                  <?= $pembatalan === $opt ? 'checked' : '' ?> />
                <span class="policy-option-content">
                  <span class="policy-option-label"><?= htmlspecialchars($opt) ?></span>
                </span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Toggle aturan -->
        <div class="policy-section-group">
          <h3 class="policy-group-title">
            <i class="ph-bold ph-list-checks"></i> Aturan Penginapan
          </h3>
          <div class="policy-toggles">

            <div class="policy-toggle-item">
              <div class="toggle-info">
                <span class="toggle-icon"><i class="ph-bold ph-paw-print"></i></span>
                <div class="toggle-text">
                  <strong>Hewan Peliharaan</strong>
                  <span>Apakah tamu boleh membawa hewan peliharaan?</span>
                </div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="bolehHewan" <?= $boleh_hewan ? 'checked' : '' ?> />
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div class="policy-toggle-item">
              <div class="toggle-info">
                <span class="toggle-icon"><i class="ph-bold ph-cigarette"></i></span>
                <div class="toggle-text">
                  <strong>Merokok</strong>
                  <span>Apakah merokok diperbolehkan di dalam properti?</span>
                </div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="bolehMerokok" <?= $boleh_merokok ? 'checked' : '' ?> />
                <span class="toggle-slider"></span>
              </label>
            </div>

            <div class="policy-toggle-item">
              <div class="toggle-info">
                <span class="toggle-icon"><i class="ph-bold ph-baby"></i></span>
                <div class="toggle-text">
                  <strong>Anak-anak</strong>
                  <span>Apakah anak-anak diperbolehkan menginap?</span>
                </div>
              </div>
              <label class="toggle-switch">
                <input type="checkbox" id="bolehAnak" <?= $boleh_anak ? 'checked' : '' ?> />
                <span class="toggle-slider"></span>
              </label>
            </div>

          </div>
        </div>

        <!-- Catatan tambahan -->
        <div class="policy-section-group">
          <h3 class="policy-group-title">
            <i class="ph-bold ph-note-pencil"></i> Catatan Tambahan (Opsional)
          </h3>
          <textarea id="catatanTambahan" class="policy-textarea" rows="3"
            maxlength="500"
            placeholder="cth: Dilarang membuat kebisingan setelah pukul 22.00. Tamu wajib menunjukkan KTP saat check-in..."
            ><?= htmlspecialchars($catatan) ?></textarea>
          <span class="char-hint"><span id="catatanLen"><?= strlen($catatan) ?></span>/500</span>
        </div>

      </div>

      <div id="policiesError" class="policies-error" style="display:none;">
        <i class="ph-bold ph-warning"></i> Pastikan jam check-in dan check-out sudah diisi.
      </div>
    </main>

    <footer class="onboarding-footer">
      <div class="progress-bar">
        <div class="progress-segment completed"></div>
        <div class="progress-segment completed"></div>
        <div class="progress-segment completed"></div>
      </div>
      <div class="footer-actions">
        <a href="rooms.php" class="back-button">Kembali</a>
        <button class="next-button" id="btnSelanjutnya">Selanjutnya</button>
      </div>
    </footer>

    <script>
      // Char counter catatan
      const catatanEl  = document.getElementById('catatanTambahan');
      const catatanLen = document.getElementById('catatanLen');
      catatanEl.addEventListener('input', () => {
        catatanLen.textContent = catatanEl.value.length;
      });

      // Selanjutnya
      document.getElementById('btnSelanjutnya').addEventListener('click', () => {
        const checkin  = document.getElementById('jamCheckin').value;
        const checkout = document.getElementById('jamCheckout').value;
        const errEl    = document.getElementById('policiesError');

        if (!checkin || !checkout) {
          errEl.style.display = 'flex';
          return;
        }
        errEl.style.display = 'none';

        const pembatalan = document.querySelector('input[name="kebijakan_pembatalan"]:checked')?.value
          || 'Gratis hingga 24 jam sebelum check-in';

        const payload = {
          jam_checkin:           checkin,
          jam_checkout:          checkout,
          kebijakan_pembatalan:  pembatalan,
          boleh_hewan:           document.getElementById('bolehHewan').checked   ? 1 : 0,
          boleh_merokok:         document.getElementById('bolehMerokok').checked ? 1 : 0,
          boleh_anak:            document.getElementById('bolehAnak').checked    ? 1 : 0,
          catatan_tambahan:      catatanEl.value.trim(),
        };

        fetch('save_policies.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
          if (data.status === 'ok') {
            window.location.href = 'photos.php';
          } else {
            alert('Gagal menyimpan: ' + (data.message || 'Error tidak diketahui'));
          }
        })
        .catch(() => alert('Terjadi kesalahan jaringan.'));
      });
    </script>
  </body>
</html>