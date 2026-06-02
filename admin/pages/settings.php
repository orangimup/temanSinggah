<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

// ── AJAX: simpan settings ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save') {
  header('Content-Type: application/json');

  $fields = [
    'support_email'            => $_POST['support_email']            ?? '',
    'instagram'                => $_POST['instagram']                ?? '',
    'facebook'                 => $_POST['facebook']                 ?? '',
    'youtube'                  => $_POST['youtube']                  ?? '',
    'twitter'                  => $_POST['twitter']                  ?? '',
    'komisi_persen'            => $_POST['komisi_persen']            ?? '0',
    'min_payout'               => $_POST['min_payout']               ?? '0',
    'cancellation_policy'      => $_POST['cancellation_policy']      ?? '',
    'notif_reservasi'          => $_POST['notif_reservasi']          ?? '0',
    'notif_pembatalan'         => $_POST['notif_pembatalan']         ?? '0',
    'notif_payout'             => $_POST['notif_payout']             ?? '0',
    'notif_review'             => $_POST['notif_review']             ?? '0',
    'notif_laporan'            => $_POST['notif_laporan']            ?? '0',
  ];

  $koneksi->begin_transaction();
  try {
    $stmt = $koneksi->prepare(
      "INSERT INTO platform_settings (setting_key, value)
       VALUES (?, ?)
       ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = CURRENT_TIMESTAMP"
    );
    foreach ($fields as $key => $val) {
      $val = $koneksi->real_escape_string(trim($val));
      $stmt->bind_param('ss', $key, $val);
      $stmt->execute();
    }
    $stmt->close();
    $koneksi->commit();
    echo json_encode(['success' => true]);
  } catch (Exception $e) {
    $koneksi->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  exit;
}

// ── Load semua settings ──────────────────────────────────────────────────────
$res  = $koneksi->query("SELECT setting_key, value FROM platform_settings");
$cfg  = [];
while ($r = $res->fetch_assoc()) $cfg[$r['setting_key']] = $r['value'];

function cfg(string $key, string $default = ''): string {
  global $cfg;
  return htmlspecialchars($cfg[$key] ?? $default);
}
function cfgOn(string $key): bool {
  global $cfg;
  return ($cfg[$key] ?? '0') === '1';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pengaturan | Admin Teman Singgah</title>
  <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="/teman_singgah/components/root.css" />
  <link rel="stylesheet" href="/teman_singgah/admin/dashboard.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
  <style>
    .toast {
      position: fixed; bottom: 2rem; right: 2rem; z-index: 9999;
      padding: .75rem 1.25rem; border-radius: 8px; font-size: .875rem;
      font-weight: 500; opacity: 0; transform: translateY(8px);
      transition: opacity .3s, transform .3s; pointer-events: none;
    }
    .toast.show { opacity: 1; transform: translateY(0); }
    .toast.success { background: var(--color-success, #1D9E75); color: #fff; }
    .toast.error   { background: var(--color-error,   #E24B4A); color: #fff; }
  </style>
</head>
<body>
<div class="admin-layout">
  <!-- ── Sidebar ────────────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <a href="/teman_singgah/admin/pages/dashboard.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="/teman_singgah/assets/logo/logo_temansinggah.svg" alt="Logo" class="logo-icon" />
        <img src="/teman_singgah/assets/logo/label_temansinggah.svg" alt="Teman Singgah" class="logo-name" />
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section">
        <div class="nav-section-title">Halaman Utama</div>
        <a href="/teman_singgah/admin/pages/dashboard.php" class="nav-item"><i class="ph-bold ph-squares-four"></i>Dashboard</a>
      </div>
      <div class="nav-section">
        <div class="nav-section-title">Manajemen</div>
        <a href="/teman_singgah/admin/pages/users.php"        class="nav-item"><i class="ph-bold ph-users"></i>Pengguna</a>
        <a href="/teman_singgah/admin/pages/listings.php"     class="nav-item"><i class="ph-bold ph-house"></i>Properti</a>
        <a href="/teman_singgah/admin/pages/reservations.php" class="nav-item"><i class="ph-bold ph-calendar-check"></i>Reservasi</a>
        <a href="/teman_singgah/admin/pages/transactions.php" class="nav-item"><i class="ph-bold ph-currency-circle-dollar"></i>Transaksi</a>
        <a href="/teman_singgah/admin/pages/promos.php"       class="nav-item"><i class="ph-bold ph-tag"></i>Promo &amp; Deals</a>
      </div>
      <div class="nav-section">
        <div class="nav-section-title">Moderasi</div>
        <a href="/teman_singgah/admin/pages/reviews.php" class="nav-item"><i class="ph-bold ph-star"></i>Ulasan</a>
      </div>
      <div class="nav-section">
        <div class="nav-section-title">Keuangan</div>
        <a href="/teman_singgah/admin/pages/payouts.php" class="nav-item"><i class="ph-bold ph-money"></i>Pembayaran</a>
      </div>
      <div class="nav-section">
        <div class="nav-section-title">Sistem</div>
        <a href="/teman_singgah/admin/pages/settings.php" class="nav-item active"><i class="ph-bold ph-gear"></i>Pengaturan</a>
      </div>
    </nav>
  </aside>

  <div class="main-container">
    <header class="topbar">
      <div class="topbar-left">
        <h1 class="page-title">Konfigurasi Platform</h1>
      </div>
      <div class="topbar-right">
        <span class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
        <div class="user-avatar"><?= strtoupper(mb_substr($_SESSION['nama'] ?? 'A', 0, 1)) ?></div>
      </div>
    </header>

    <main class="content-area">

      <!-- Informasi Umum -->
      <div class="settings-card">
        <h2 class="settings-card-title">Informasi Umum</h2>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="support-email">Email Support</label>
            <input type="email" id="support-email" name="support_email" class="form-input"
              value="<?= cfg('support_email', 'halotemansinggah@gmail.com') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label" for="instagram-name">Nama Instagram</label>
            <input type="text" id="instagram-name" name="instagram" class="form-input"
              value="<?= cfg('instagram') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label" for="facebook-name">Nama Facebook</label>
            <input type="text" id="facebook-name" name="facebook" class="form-input"
              value="<?= cfg('facebook') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label" for="youtube-name">Nama Youtube</label>
            <input type="text" id="youtube-name" name="youtube" class="form-input"
              value="<?= cfg('youtube') ?>" />
          </div>
          <div class="form-group">
            <label class="form-label" for="twitter-name">Nama Twitter / X</label>
            <input type="text" id="twitter-name" name="twitter" class="form-input"
              value="<?= cfg('twitter') ?>" />
          </div>
        </div>
      </div>

      <!-- Keuangan -->
      <div class="settings-card">
        <h2 class="settings-card-title">Keuangan</h2>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label" for="commission-rate">Persentase Komisi Platform (%)</label>
            <input type="number" id="commission-rate" name="komisi_persen" class="form-input"
              value="<?= cfg('komisi_persen', '15') ?>" min="0" max="100" step="0.01" />
            <span class="form-hint">Persentase yang dipotong dari setiap transaksi tamu</span>
          </div>
          <div class="form-group">
            <label class="form-label" for="min-payout">Batas Minimum Payout (Rp)</label>
            <input type="number" id="min-payout" name="min_payout" class="form-input"
              value="<?= cfg('min_payout', '500000') ?>" min="0" />
            <span class="form-hint">Jumlah minimum sebelum host dapat mencairkan dana</span>
          </div>
        </div>
      </div>

      <!-- Kebijakan Pembatalan -->
      <div class="settings-card">
        <h2 class="settings-card-title">Kebijakan Pembatalan</h2>
        <div class="form-group">
          <label class="form-label" for="cancellation-policy">Kebijakan Default</label>
          <textarea id="cancellation-policy" name="cancellation_policy" class="form-textarea" rows="4"><?= cfg('cancellation_policy', 'Pembatalan dilakukan oleh tamu minimal 48 jam sebelum check-in untuk mendapatkan pengembalian dana penuh. Pembatalan dalam waktu 48 jam akan dikenakan biaya sebesar 1 malam menginap. No-show akan dikenakan biaya penuh.') ?></textarea>
          <span class="form-hint">Kebijakan ini akan ditampilkan sebagai default untuk listing baru</span>
        </div>
      </div>

      <!-- Notifikasi Email -->
      <div class="settings-card">
        <h2 class="settings-card-title">Pengaturan Notifikasi Email</h2>

        <?php
        $notifs = [
          ['notif_reservasi', 'Notifikasi Reservasi Baru',  'Kirim email ke host saat ada reservasi baru masuk'],
          ['notif_pembatalan','Notifikasi Pembatalan',       'Kirim email ke host dan tamu saat reservasi dibatalkan'],
          ['notif_payout',    'Notifikasi Payout',           'Kirim email ke host saat dana payout berhasil diproses'],
          ['notif_review',    'Notifikasi Review Baru',      'Kirim email ke host saat menerima ulasan dari tamu'],
          ['notif_laporan',   'Notifikasi Laporan',          'Kirim email ke admin saat ada laporan atau komplain masuk'],
        ];
        foreach ($notifs as [$key, $title, $desc]):
          $on = cfgOn($key);
        ?>
        <div class="toggle-row">
          <div class="toggle-label">
            <h4><?= $title ?></h4>
            <p><?= $desc ?></p>
          </div>
          <div class="toggle-switch <?= $on ? 'active' : '' ?>" data-key="<?= $key ?>"></div>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Footer -->
      <div class="settings-footer">
        <button class="back-button" type="button" onclick="window.history.back()">Batal</button>
        <button class="save-button" type="button" id="btn-save">
          <i class="ph-bold ph-floppy-disk"></i>
          Simpan Perubahan
        </button>
      </div>

    </main>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="/teman_singgah/admin/dashboard.js"></script>
<script>
// Toggle switch
document.querySelectorAll('.toggle-switch').forEach(el => {
  el.addEventListener('click', () => el.classList.toggle('active'));
});

// Toast helper
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = `toast ${type} show`;
  setTimeout(() => t.classList.remove('show'), 3000);
}

// Simpan
document.getElementById('btn-save').addEventListener('click', async () => {
  const data = new FormData();
  data.append('action', 'save');

  // Teks / angka
  const fields = {
    support_email:       document.getElementById('support-email').value,
    instagram:           document.getElementById('instagram-name').value,
    facebook:            document.getElementById('facebook-name').value,
    youtube:             document.getElementById('youtube-name').value,
    twitter:             document.getElementById('twitter-name').value,
    komisi_persen:       document.getElementById('commission-rate').value,
    min_payout:          document.getElementById('min-payout').value,
    cancellation_policy: document.getElementById('cancellation-policy').value,
  };
  for (const [k, v] of Object.entries(fields)) data.append(k, v);

  // Toggle
  document.querySelectorAll('.toggle-switch').forEach(el => {
    const key = el.dataset.key;
    if (key) data.append(key, el.classList.contains('active') ? '1' : '0');
  });

  try {
    const res  = await fetch('settings.php', { method: 'POST', body: data });
    const json = await res.json();
    if (json.success) showToast('Pengaturan berhasil disimpan!', 'success');
    else              showToast(json.message || 'Gagal menyimpan.', 'error');
  } catch {
    showToast('Terjadi kesalahan jaringan.', 'error');
  }
});
</script>
</body>
</html>