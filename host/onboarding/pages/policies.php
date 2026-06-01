<?php
session_start();

$saved = isset($_SESSION['onboarding']['policies']) ? $_SESSION['onboarding']['policies'] : [];

$checkin = $saved['jam_checkin'] ?? '14:00';
$checkout = $saved['jam_checkout'] ?? '12:00';
$pembatalan = $saved['kebijakan_pembatalan'] ?? 'Gratis hingga 24 jam sebelum check-in';
$boleh_hewan = isset($saved['boleh_hewan']) ? (bool) $saved['boleh_hewan'] : false;
$boleh_merokok = isset($saved['boleh_merokok']) ? (bool) $saved['boleh_merokok'] : false;
$boleh_anak = isset($saved['boleh_anak']) ? (bool) $saved['boleh_anak'] : true;
$catatan = $saved['catatan_tambahan'] ?? '';

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
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>

  <style>
    :root {
      --color-primary: #8b2500;
      --color-primary-hover: #7a1f00;
      --color-primary-active: #5c1800;
      --color-primary-light: #f9ede8;
      --color-primary-light-hover: #f4e0d8;
      --color-primary-light-active: #eecfc2;
      --color-primary-subtle: #fdf5f2;

      --color-text-primary: #1a0a00;
      --color-text-secondary: #6b4f3a;
      --color-text-disabled: #b0907a;
      --color-text-inverse: #ffffff;
      --color-text-hint: #c0a090;

      --color-border: #e8d5c0;
      --color-border-strong: #c8b5a0;
      --color-border-subtle: #f0e4d8;

      --color-bg: #fffaf5;
      --color-bg-card: #ffffff;
      --color-bg-section: #f5e6d8;
      --color-bg-overlay: rgba(26, 10, 0, 0.5);
      --color-bg-skeleton: #f0e4d8;

      --color-error: #cc2b2b;
      --color-error-light: #fdeaea;

      --font-family: "Inter", sans-serif;

      --text-xs: 0.75rem;
      --text-sm: 0.875rem;
      --text-base: 1rem;
      --text-md: 1.125rem;
      --text-lg: 1.25rem;
      --text-xl: 1.5rem;
      --text-3xl: 2.25rem;

      --font-medium: 500;
      --font-semibold: 600;
      --font-bold: 700;

      --space-4: 0.25rem;
      --space-6: 0.375rem;
      --space-8: 0.5rem;
      --space-12: 0.75rem;
      --space-16: 1rem;
      --space-20: 1.25rem;
      --space-24: 1.5rem;
      --space-32: 2rem;
      --space-48: 3rem;
      --space-40: 2.5rem;
      --space-64: 4rem;
      --space-10: 0.625rem;
      --space-letter: 0.03125rem;
      --navbar-height: 68px;
      --container-xl: 1280px;

      --radius-md: 8px;
      --radius-xl: 12px;
      --radius-2xl: 16px;
      --radius-3xl: 24px;
      --radius-full: 9999px;

      --shadow-card: 0 2px 8px rgba(26, 10, 0, 0.08);
      --shadow-navbar: 0 1px 3px rgba(26, 10, 0, 0.08);

      --z-sticky: 200;
      --z-navbar: 300;

      --transition-fast: 200ms ease;
      --transition-base: 300ms ease;
    }

    *,
    *::before,
    *::after {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      background: var(--color-bg);
      font-family: var(--font-family);
      -webkit-font-smoothing: antialiased;
      color: var(--color-text-primary);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    a {
      text-decoration: none;
      color: inherit;
    }

    button,
    input,
    textarea,
    select {
      font-family: var(--font-family);
    }

    .navbar {
      position: fixed;
      top: 0;
      background: var(--color-bg-card);
      backdrop-filter: blur(10px);
      display: flex;
      align-items: center;
      height: var(--navbar-height);
      z-index: var(--z-navbar);
      width: 100%;
      box-shadow: var(--shadow-navbar);
    }

    .navbar-container {
      max-width: var(--container-xl);
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 var(--space-40);
      width: 100%;
    }

    .logo-link {
      position: absolute;
      width: 180px;
      z-index: 3;
      height: var(--navbar-height);
      display: flex;
      align-items: center;
    }

    .logo-section {
      display: flex;
      align-items: center;
      position: relative;
      text-decoration: none;
      gap: var(--space-4);
    }

    .logo-icon {
      width: 38px;
      z-index: 2;
      cursor: pointer;
      display: block;
      flex-shrink: 0;
    }

    .logo-name {
      width: 140px;
      z-index: 2;
      cursor: pointer;
      display: block;
      flex-shrink: 0;
      transform: translateY(-14px);
    }

    .header-actions {
      display: flex;
      gap: var(--space-8);
    }

    .ghost-button {
      padding: var(--space-12) var(--space-20);
      border: 1.5px solid var(--color-border);
      background: transparent;
      border-radius: var(--radius-full);
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      color: var(--color-text-primary);
      cursor: pointer;
      font-family: var(--font-family);
      transition: all var(--transition-base);
    }

    .ghost-button:hover {
      border-color: var(--color-border-strong);
      background: var(--color-bg-section);
    }

    .ghost-button:active {
      transform: scale(0.99);
      background: var(--color-bg-skeleton);
    }

    .main-content {
      flex: 1;
      padding: var(--space-48) var(--space-32);
      padding-top: calc(var(--navbar-height) + var(--space-48));
      max-width: 960px;
      margin: 0 auto;
      width: 100%;
    }

    .page-header {
      margin-bottom: var(--space-32);
    }

    .page-header h2 {
      font-size: var(--text-3xl);
      font-weight: var(--font-bold);
      line-height: 1.2;
      color: var(--color-text-primary);
    }

    .page-header p {
      font-size: var(--text-base);
      color: var(--color-text-secondary);
      margin-top: var(--space-12);
      line-height: 1.6;
      max-width: 560px;
    }

    .policy-form {
      display: flex;
      flex-direction: column;
      gap: var(--space-32);
    }

    .policy-section-group {
      background: var(--color-bg-card);
      border: 1.5px solid var(--color-border-subtle);
      border-radius: var(--radius-3xl);
      padding: var(--space-32);
      box-shadow: var(--shadow-card);
    }

    .policy-group-title {
      font-size: var(--text-md);
      font-weight: var(--font-semibold);
      color: var(--color-text-primary);
      margin-bottom: var(--space-24);
      display: flex;
      align-items: center;
      gap: var(--space-8);
    }

    .policy-group-title i {
      color: var(--color-primary);
      font-size: var(--text-lg);
    }

    .policy-time-row {
      display: flex;
      gap: var(--space-24);
    }

    .policy-field {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: var(--space-8);
    }

    .policy-field label {
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      color: var(--color-text-secondary);
      letter-spacing: var(--space-letter);
    }

    .policy-field input[type="time"] {
      padding: var(--space-16) var(--space-20);
      border: 1.5px solid var(--color-border);
      border-radius: var(--radius-xl);
      font-size: var(--text-base);
      font-weight: var(--font-semibold);
      color: var(--color-text-primary);
      background: var(--color-bg);
      outline: none;
      transition: all var(--transition-fast);
      cursor: pointer;
      width: 100%;
    }

    .policy-field input[type="time"]:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 4px rgba(139, 37, 0, 0.08);
      background: var(--color-bg-card);
    }

    .policy-field input[type="time"]:hover {
      border-color: var(--color-border-strong);
    }

    .policy-options-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: var(--space-12);
    }

    .policy-option-card {
      display: flex;
      align-items: center;
      gap: var(--space-12);
      padding: var(--space-16) var(--space-20);
      border: 1.5px solid var(--color-border-subtle);
      border-radius: var(--radius-2xl);
      cursor: pointer;
      transition: all var(--transition-fast);
      background: var(--color-bg);
    }

    .policy-option-card input[type="radio"] {
      accent-color: var(--color-primary);
      width: 18px;
      height: 18px;
      flex-shrink: 0;
      cursor: pointer;
    }

    .policy-option-card:hover {
      border-color: var(--color-border-strong);
      background: var(--color-primary-subtle);
    }

    .policy-option-card:has(input:checked) {
      border-color: var(--color-primary);
      background: var(--color-primary-subtle);
    }

    .policy-option-label {
      font-size: var(--text-sm);
      font-weight: var(--font-medium);
      color: var(--color-text-primary);
      line-height: 1.4;
    }

    .policy-toggles {
      display: flex;
      flex-direction: column;
    }

    .policy-toggle-item {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: var(--space-20) 0;
      border-bottom: 1.5px solid var(--color-border-subtle);
    }

    .policy-toggle-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .policy-toggle-item:first-child {
      padding-top: 0;
    }

    .toggle-info {
      display: flex;
      align-items: center;
      gap: var(--space-16);
    }

    .toggle-icon {
      width: 44px;
      height: 44px;
      background: var(--color-primary-light);
      border-radius: var(--radius-xl);
      display: flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .toggle-icon i {
      font-size: var(--text-xl);
      color: var(--color-primary);
    }

    .toggle-text {
      display: flex;
      flex-direction: column;
      gap: var(--space-4);
    }

    .toggle-text strong {
      font-size: var(--text-base);
      font-weight: var(--font-semibold);
      color: var(--color-text-primary);
    }

    .toggle-text span {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
    }

    .toggle-switch {
      position: relative;
      display: inline-block;
      width: 52px;
      height: 28px;
      flex-shrink: 0;
      cursor: pointer;
    }

    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
      position: absolute;
    }

    .toggle-slider {
      position: absolute;
      inset: 0;
      background: var(--color-border-strong);
      border-radius: var(--radius-full);
      transition: background var(--transition-fast);
    }

    .toggle-slider::before {
      content: '';
      position: absolute;
      width: 22px;
      height: 22px;
      left: 3px;
      top: 3px;
      background: white;
      border-radius: 50%;
      transition: transform var(--transition-fast);
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
    }

    .toggle-switch input:checked+.toggle-slider {
      background: var(--color-primary);
    }

    .toggle-switch input:checked+.toggle-slider::before {
      transform: translateX(24px);
    }

    .policy-textarea {
      width: 100%;
      padding: var(--space-16) var(--space-20);
      border: 1.5px solid var(--color-border);
      border-radius: var(--radius-2xl);
      font-size: var(--text-base);
      font-family: var(--font-family);
      color: var(--color-text-primary);
      background: var(--color-bg);
      resize: none;
      outline: none;
      line-height: 1.6;
      transition: all var(--transition-fast);
    }

    .policy-textarea::placeholder {
      color: var(--color-text-hint);
    }

    .policy-textarea:focus {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 4px rgba(139, 37, 0, 0.08);
      background: var(--color-bg-card);
    }

    .char-hint {
      display: block;
      text-align: right;
      font-size: var(--text-xs);
      color: var(--color-text-hint);
      margin-top: var(--space-8);
    }

    .policies-error {
      display: flex;
      align-items: center;
      gap: var(--space-8);
      padding: var(--space-16) var(--space-20);
      background: var(--color-error-light);
      border: 1.5px solid var(--color-error);
      border-radius: var(--radius-xl);
      color: var(--color-error);
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      margin-top: var(--space-8);
    }

    .policies-error i {
      font-size: var(--text-md);
      flex-shrink: 0;
    }

    .onboarding-footer {
      position: sticky;
      bottom: 0;
      z-index: var(--z-sticky);
      background: var(--color-bg-card);
      box-shadow: 0 -1px 3px rgba(26, 10, 0, 0.08);
    }

    .progress-bar {
      display: flex;
      gap: var(--space-8);
      height: 6px;
    }

    .progress-segment {
      flex: 1;
      background: var(--color-border-subtle);
      transition: background var(--transition-base);
    }

    .progress-segment.completed {
      background: var(--color-primary);
    }

    .progress-segment.active {
      background: var(--color-primary);
      opacity: 0.5;
    }

    .footer-actions {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: var(--space-16) var(--space-64);
      max-width: 1280px;
      margin: 0 auto;
      width: 100%;
    }

    .back-button {
      font-size: var(--text-base);
      font-weight: var(--font-semibold);
      color: var(--color-text-secondary);
      background: transparent;
      padding: var(--space-12) var(--space-16);
      border: 1.5px solid transparent;
      border-radius: var(--radius-md);
      cursor: pointer;
      font-family: var(--font-family);
      transition: all var(--transition-base);
      text-decoration: none;
      display: inline-block;
    }

    .back-button:hover {
      color: var(--color-primary);
      background: var(--color-bg-section);
    }

    .next-button {
      background: var(--color-primary);
      color: var(--color-text-inverse);
      letter-spacing: var(--space-letter);
      border-radius: var(--radius-xl);
      cursor: pointer;
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      transition: all var(--transition-base);
      font-family: var(--font-family);
      padding: var(--space-16) var(--space-24);
      border: 1.5px solid transparent;
    }

    .next-button:hover {
      background: var(--color-primary-hover);
    }

    .next-button:active {
      transform: scale(0.99);
      background: var(--color-primary-active);
    }
  </style>
</head>

<body>
  <header class="navbar">
    <nav class="navbar-container">
      <a href="../../../index.php" class="logo-link">
        <div class="logo-section">
          <img src="../../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
          <img src="../../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
        </div>
      </a>
      <div style="width:180px;"></div>
      <div class="header-actions">
        <a href="../../../user/pages/messages.html"><button class="ghost-button">Pertanyaan?</button></a>
        <a href="../../../index.php"><button class="ghost-button">Simpan &amp; keluar</button></a>
      </div>
    </nav>
  </header>

  <main class="main-content">
    <div class="page-header">
      <h2>Atur kebijakan penginapan Anda</h2>
      <p>Beri tahu tamu tentang peraturan dan kebijakan yang berlaku di properti Anda.</p>
    </div>

    <div class="policy-form">

      <div class="policy-section-group">
        <h3 class="policy-group-title">
          <i class="ph-bold ph-clock"></i> Jam Check-in &amp; Check-out
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

      <div class="policy-section-group">
        <h3 class="policy-group-title">
          <i class="ph-bold ph-prohibit"></i> Kebijakan Pembatalan
        </h3>
        <div class="policy-options-grid">
          <?php foreach ($pembatalan_options as $opt): ?>
            <label class="policy-option-card">
              <input type="radio" name="kebijakan_pembatalan" value="<?= htmlspecialchars($opt) ?>" <?= $pembatalan === $opt ? 'checked' : '' ?> />
              <span class="policy-option-content">
                <span class="policy-option-label"><?= htmlspecialchars($opt) ?></span>
              </span>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

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

      <div class="policy-section-group">
        <h3 class="policy-group-title">
          <i class="ph-bold ph-note-pencil"></i> Catatan Tambahan (Opsional)
        </h3>
        <textarea id="catatanTambahan" class="policy-textarea" rows="3" maxlength="500"
          placeholder="cth: Dilarang membuat kebisingan setelah pukul 22.00. Tamu wajib menunjukkan KTP saat check-in..."><?= htmlspecialchars($catatan) ?></textarea>
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
    const catatanEl = document.getElementById('catatanTambahan');
    const catatanLen = document.getElementById('catatanLen');
    catatanEl.addEventListener('input', () => {
      catatanLen.textContent = catatanEl.value.length;
    });

    document.getElementById('btnSelanjutnya').addEventListener('click', () => {
      const checkin = document.getElementById('jamCheckin').value;
      const checkout = document.getElementById('jamCheckout').value;
      const errEl = document.getElementById('policiesError');

      if (!checkin || !checkout) {
        errEl.style.display = 'flex';
        errEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        return;
      }
      errEl.style.display = 'none';

      const pembatalan = document.querySelector('input[name="kebijakan_pembatalan"]:checked')?.value
        || 'Gratis hingga 24 jam sebelum check-in';

      const payload = {
        jam_checkin: checkin,
        jam_checkout: checkout,
        kebijakan_pembatalan: pembatalan,
        boleh_hewan: document.getElementById('bolehHewan').checked ? 1 : 0,
        boleh_merokok: document.getElementById('bolehMerokok').checked ? 1 : 0,
        boleh_anak: document.getElementById('bolehAnak').checked ? 1 : 0,
        catatan_tambahan: catatanEl.value.trim(),
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