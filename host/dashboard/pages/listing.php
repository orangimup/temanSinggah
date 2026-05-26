<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_host.php';
require_once '../../../koneksi.php';

$hostId = (int) ($_SESSION['id'] ?? 0);

$listings = [];
$q = mysqli_query(
  $koneksi,
  "SELECT l.id, l.judul, l.tipe_properti, l.lokasi, l.status,
            lp.nama_file AS foto_cover
     FROM listings l
     LEFT JOIN listing_photos lp
           ON lp.listing_id = l.id AND lp.adalah_cover = 1
     WHERE l.host_id = $hostId
     ORDER BY l.dibuat_pada DESC"
);
while ($row = mysqli_fetch_assoc($q)) {
  $listings[] = $row;
}

function statusInfo(string $s): array
{
  return match ($s) {
    'aktif' => ['Aktif', 'success'],
    'draft' => ['Draft', 'warning'],
    'nonaktif' => ['Nonaktif', 'error'],
    default => ['Butuh aksi', 'error'],
  };
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Listing | Teman Singgah</title>
  <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../../components/root.css" />
  <link rel="stylesheet" href="../../../components/navbar.css" />
  <link rel="stylesheet" href="../../../components/footer.css" />
  <link rel="stylesheet" href="../../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/listing.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />

  <style>
    /* ── Action Buttons ── */
    .action-group {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .action-btn {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border-radius: var(--radius-md, 8px);
      border: 1.5px solid transparent;
      background: transparent;
      cursor: pointer;
      font-size: 0.95rem;
      transition: background 0.15s, border-color 0.15s, color 0.15s;
      text-decoration: none;
      flex-shrink: 0;
    }

    /* Lihat — biru */
    .action-btn.view {
      color: #2563eb;
      border-color: #bfdbfe;
      background: #eff6ff;
    }

    .action-btn.view:hover {
      background: #dbeafe;
      border-color: #93c5fd;
    }

    /* Edit — kuning/amber */
    .action-btn.edit {
      color: #d97706;
      border-color: #fde68a;
      background: #fffbeb;
    }

    .action-btn.edit:hover {
      background: #fef3c7;
      border-color: #fcd34d;
    }

    /* Hapus — merah */
    .action-btn.delete {
      color: #dc2626;
      border-color: #fecaca;
      background: #fef2f2;
    }

    .action-btn.delete:hover {
      background: #fee2e2;
      border-color: #fca5a5;
    }

    /* Kolom aksi — tengah */
    thead th:nth-child(5) {
      text-align: center;
      width: 120px;
    }

    td.action-cell {
      text-align: center;
    }

    .action-cell {
      position: relative;
      z-index: 2;
    }

    .action-cell .action-group {
      pointer-events: auto;
    }

    /* Grid card overlay actions */
    .listing-card {
      position: relative;
    }

    .listing-card-actions {
      position: absolute;
      top: 10px;
      right: 10px;
      display: flex;
      flex-direction: column;
      gap: 5px;
      opacity: 0;
      transform: translateX(6px);
      transition: opacity 0.18s, transform 0.18s;
      z-index: 10;
    }

    .listing-card:hover .listing-card-actions {
      opacity: 1;
      transform: translateX(0);
    }

    .listing-card-actions .action-btn {
      width: 30px;
      height: 30px;
      font-size: 0.85rem;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
    }

    /* Confirm modal overlay */
    .confirm-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.45);
      z-index: 9999;
      align-items: center;
      justify-content: center;
    }

    .confirm-overlay.open {
      display: flex;
    }

    .confirm-box {
      background: #fff;
      border-radius: 16px;
      padding: 28px 28px 24px;
      max-width: 380px;
      width: calc(100% - 32px);
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
    }

    .confirm-box .confirm-icon {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 48px;
      height: 48px;
      border-radius: 50%;
      font-size: 1.4rem;
      margin: 0 auto 16px;
    }

    .confirm-icon.danger {
      background: #fef2f2;
      color: #dc2626;
    }

    .confirm-icon.warning {
      background: #fffbeb;
      color: #d97706;
    }

    .confirm-box h3 {
      text-align: center;
      font-size: 1rem;
      font-weight: 700;
      margin: 0 0 8px;
      color: var(--color-text-primary, #111827);
    }

    .confirm-box p {
      text-align: center;
      font-size: 0.875rem;
      color: var(--color-text-secondary, #6b7280);
      margin: 0 0 22px;
      line-height: 1.5;
    }

    .confirm-actions {
      display: flex;
      gap: 10px;
    }

    .confirm-actions button {
      flex: 1;
      padding: 10px;
      border-radius: 10px;
      border: none;
      font-size: 0.875rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.15s;
    }

    .confirm-cancel {
      background: #f3f4f6;
      color: #374151;
    }

    .confirm-cancel:hover {
      background: #e5e7eb;
    }

    .confirm-confirm-danger {
      background: #dc2626;
      color: #fff;
    }

    .confirm-confirm-danger:hover {
      background: #b91c1c;
    }

    .confirm-confirm-warning {
      background: #d97706;
      color: #fff;
    }

    .confirm-confirm-warning:hover {
      background: #b45309;
    }

    /* Toast */
    .ts-toast {
      position: fixed;
      bottom: 28px;
      left: 50%;
      transform: translateX(-50%) translateY(12px);
      background: #111827;
      color: #fff;
      font-size: 0.875rem;
      font-weight: 500;
      padding: 11px 20px;
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.2s, transform 0.2s;
      z-index: 99999;
      white-space: nowrap;
    }

    .ts-toast.show {
      opacity: 1;
      transform: translateX(-50%) translateY(0);
    }

    .ts-toast.success {
      background: #15803d;
    }

    .ts-toast.error {
      background: #dc2626;
    }
  </style>
</head>

<body>

  <header class="navbar">
    <nav class="navbar-container">
      <a href="reservations.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="../../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
        <img src="../../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
      </div>
      <ul class="nav-menu">
        <li class="nav-item"><a href="reservations.php" class="nav-link">Reservasi</a></li>
        <li class="nav-item"><a href="calendar_router.php" class="nav-link">Kalender</a></li>
        <li class="nav-item"><a href="listing.php" class="nav-link active">Listing</a></li>
        <li class="nav-item"><a href="messages.php" class="nav-link">Pesan</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <?php include $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/components/navbar_profile_host.php'; ?>
    </nav>
  </header>

  <main class="main-content">
    <section class="content-header">
      <h2 class="content-title">Listing Saya</h2>
      <div class="header-buttons">
        <button class="header-button list-button" title="Tampilan list">
          <i class="ph-bold ph-rows"></i>
        </button>
        <a href="listing_edit.php" class="header-button" title="Tambah listing">
          <i class="ph-bold ph-plus"></i>
        </a>
      </div>
    </section>

    <!-- LIST VIEW -->
    <section class="list-section list-view">
      <?php if (empty($listings)): ?>
        <div style="text-align:center;padding:64px 24px;color:var(--color-text-secondary);">
          <i class="ph-bold ph-house-simple" style="font-size:2.5rem;margin-bottom:12px;display:block;"></i>
          <p style="font-size:1rem;margin-bottom:20px;">Belum ada listing. Yuk tambahkan properti pertamamu!</p>
          <a href="listing_edit.php" style="display:inline-flex;align-items:center;gap:8px;padding:10px 20px;
                  background:var(--color-primary);color:#fff;border-radius:var(--radius-full);
                  font-weight:600;font-size:0.875rem;text-decoration:none;">
            <i class="ph-bold ph-plus"></i> Tambah Listing
          </a>
        </div>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Listing</th>
              <th>Tipe</th>
              <th>Lokasi</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($listings as $l):
              $foto = !empty($l['foto_cover'])
                ? (str_starts_with($l['foto_cover'], 'http') ? $l['foto_cover'] : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($l['foto_cover']))
                : '';
              [$label, $dot] = statusInfo($l['status'] ?? '');

              $listingId = (int) $l['id'];
              $editHref = 'listing_edit.php?id=' . $listingId;
              $detailHref = 'listing_detail.php?id=' . $listingId;
              ?>
              <tr class="listing-row" data-href="<?= $detailHref ?>">
                <td>
                  <div class="listing-cell">
                    <?php if ($foto): ?>
                      <img src="<?= $foto ?>" class="listing-thumbnail" alt="" />
                    <?php else: ?>
                      <div class="listing-thumbnail"></div>
                    <?php endif; ?>
                    <h3 class="listing-name"><?= htmlspecialchars($l['judul']) ?></h3>
                  </div>
                </td>
                <td><?= htmlspecialchars(ucfirst($l['tipe_properti'] ?? '-')) ?></td>
                <td><?= htmlspecialchars($l['lokasi'] ?? '-') ?></td>
                <td>
                  <div class="status-cell">
                    <span class="status-dot <?= $dot ?>"></span>
                    <?= $label ?>
                  </div>
                </td>

                <!-- Kolom Aksi -->
                <td class="action-cell" onclick="event.stopPropagation()">
                  <div class="action-group">
                    <a href="<?= $detailHref ?>" class="action-btn view" title="Lihat detail listing">
                      <i class="ph-bold ph-eye"></i>
                    </a>
                    <a href="<?= $editHref ?>" class="action-btn edit" title="Edit listing">
                      <i class="ph-bold ph-pencil-simple"></i>
                    </a>
                    <button class="action-btn delete" title="Nonaktifkan / hapus listing"
                      onclick="openDeleteModal(<?= $listingId ?>, '<?= htmlspecialchars(addslashes($l['judul']), ENT_QUOTES) ?>', '<?= $l['status'] ?>')">
                      <i class="ph-bold ph-trash"></i>
                    </button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <!-- GRID VIEW -->
    <section class="list-section grid-view" style="display:none">
      <?php if (empty($listings)): ?>
        <div style="text-align:center;padding:64px 24px;color:var(--color-text-secondary);">
          <i class="ph-bold ph-house-simple" style="font-size:2.5rem;margin-bottom:12px;display:block;"></i>
          <p>Belum ada listing.</p>
        </div>
      <?php else: ?>
        <?php foreach ($listings as $l):
          $foto = !empty($l['foto_cover'])
            ? (str_starts_with($l['foto_cover'], 'http') ? $l['foto_cover'] : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($l['foto_cover']))
            : '';
          [$label, $dot] = statusInfo($l['status'] ?? '');
          $listingId = (int) $l['id'];
          $editHref = 'listing_edit.php?id=' . $listingId;
          $detailHref = 'listing_detail.php?id=' . $listingId;
          ?>
          <div class="listing-card" data-href="<?= $detailHref ?>" role="link" tabindex="0" style="cursor:pointer;">

            <div class="listing-card-image-container">
              <?php if ($foto): ?>
                <img src="<?= $foto ?>" class="listing-card-image" alt="<?= htmlspecialchars($l['judul']) ?>" />
              <?php else: ?>
                <div class="listing-card-placeholder">
                  <i class="ph-bold ph-house-simple"></i>
                </div>
              <?php endif; ?>

              <div class="listing-card-badge">
                <span class="badge-dot <?= $dot ?>"></span>
                <?= $label ?>
              </div>

              <div class="listing-card-actions" onclick="event.stopPropagation()">
                <a href="<?= $detailHref ?>" class="action-btn view" title="Lihat detail">
                  <i class="ph-bold ph-eye"></i>
                </a>
                <a href="<?= $editHref ?>" class="action-btn edit" title="Edit listing">
                  <i class="ph-bold ph-pencil-simple"></i>
                </a>
                <button class="action-btn delete" title="Nonaktifkan / hapus"
                  onclick="openDeleteModal(<?= $listingId ?>, '<?= htmlspecialchars(addslashes($l['judul']), ENT_QUOTES) ?>', '<?= $l['status'] ?>')">
                  <i class="ph-bold ph-trash"></i>
                </button>
              </div>
            </div>

            <div class="listing-card-body">
              <span class="listing-card-name"><?= htmlspecialchars($l['judul']) ?></span>
              <span class="listing-card-location">
                <?= htmlspecialchars(ucfirst($l['tipe_properti'] ?? '-')) ?>
                di <?= htmlspecialchars($l['lokasi'] ?? '-') ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </section>
  </main>

  <footer class="footer">
    <div class="footer-grid">
      <div class="footer-column">
        <span class="footer-brand">Teman Singgah</span>
        <p class="footer-description">Platform booking penginapan terpercaya di seluruh Indonesia, dari hotel berbintang
          hingga homestay lokal.</p>
        <div class="footer-social">
          <a href="" class="social-link"><i class="ri-instagram-line"></i></a>
          <a href="" class="social-link"><i class="ri-facebook-circle-line"></i></a>
          <a href="" class="social-link"><i class="ri-youtube-line"></i></a>
          <a href="" class="social-link"><i class="ri-twitter-line"></i></a>
          <a href="" class="social-link"><i class="ri-mail-line"></i></a>
        </div>
      </div>
      <div class="footer-column">
        <h3 class="footer-title">Navigasi</h3>
        <ul class="footer-links">
          <li><a href="../../../index.php" class="footer-link">Beranda</a></li>
          <li><a href="../../../user/pages/promo_deals.html" class="footer-link">Promo & Deals</a></li>
          <li><a href="../../../user/pages/become_host.html" class="footer-link">Jadi Host</a></li>
          <li><a href="../../../user/pages/about_us.html" class="footer-link">Tentang Kami</a></li>
          <li><a href="../../../user/pages/account.php" class="footer-link">Akun</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3 class="footer-title">Dukungan</h3>
        <ul class="footer-links">
          <li><a href="#" class="footer-link">Pusat Bantuan</a></li>
          <li><a href="#" class="footer-link">FAQ</a></li>
          <li><a href="../../../user/pages/become_host.html" class="footer-link">Cara Menjadi Host</a></li>
          <li><a href="#" class="footer-link">Cara Booking</a></li>
          <li><a href="../../../user/pages/about_us.html" class="footer-link">Tentang Kami</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p class="footer-copyright">© 2026 Teman Singgah — All rights reserved.</p>
      <div class="footer-legal">
        <a href="" class="footer-link bottom">Kebijakan Privasi</a>
        <span class="footer-dot">•</span>
        <a href="" class="footer-link bottom">Syarat & Ketentuan</a>
      </div>
    </div>
  </footer>

  <!-- CONFIRM MODAL -->
  <div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
      <div class="confirm-icon" id="confirmIcon">
        <i class="ph-bold ph-trash" id="confirmIconInner"></i>
      </div>
      <h3 id="confirmTitle">Hapus Listing?</h3>
      <p id="confirmDesc">Apakah kamu yakin ingin menghapus listing ini?</p>
      <div class="confirm-actions" id="confirmActions"></div>
    </div>
  </div>

  <!-- Toast -->
  <div class="ts-toast" id="tsToast"></div>

  <script src="../scripts/listing.js"></script>
  <script src="../../../components/navbar.js"></script>
  <script src="../../../popups/auth.js"></script>

  <script>
    function openDeleteModal(id, judul, status) {
      const overlay = document.getElementById('confirmOverlay');
      const icon = document.getElementById('confirmIcon');
      const title = document.getElementById('confirmTitle');
      const desc = document.getElementById('confirmDesc');
      const actions = document.getElementById('confirmActions');

      if (status === 'aktif') {
        icon.className = 'confirm-icon warning';
        document.getElementById('confirmIconInner').className = 'ph-bold ph-warning';
        title.textContent = 'Apa yang ingin kamu lakukan?';
        desc.textContent = `"${judul}" masih aktif. Nonaktifkan sementara atau hapus permanen?`;
        actions.innerHTML = `
      <button class="confirm-cancel" onclick="closeConfirm()">Batal</button>
      <button class="confirm-confirm-warning" onclick="doAction(${id},'nonaktif')">
        <i class="ph-bold ph-eye-slash" style="margin-right:5px;"></i>Nonaktifkan
      </button>
      <button class="confirm-confirm-danger" onclick="doAction(${id},'hapus')">
        <i class="ph-bold ph-trash" style="margin-right:5px;"></i>Hapus
      </button>`;
      } else {
        icon.className = 'confirm-icon danger';
        document.getElementById('confirmIconInner').className = 'ph-bold ph-trash';
        title.textContent = 'Hapus Listing?';
        desc.textContent = `"${judul}" akan dihapus secara permanen dan tidak bisa dikembalikan.`;
        actions.innerHTML = `
      <button class="confirm-cancel" onclick="closeConfirm()">Batal</button>
      <button class="confirm-confirm-danger" onclick="doAction(${id},'hapus')">
        <i class="ph-bold ph-trash" style="margin-right:5px;"></i>Hapus Permanen
      </button>`;
      }
      overlay.classList.add('open');
    }

    function closeConfirm() {
      document.getElementById('confirmOverlay').classList.remove('open');
    }

    document.getElementById('confirmOverlay').addEventListener('click', function (e) {
      if (e.target === this) closeConfirm();
    });

    function doAction(id, action) {
      closeConfirm();
      const fd = new FormData();
      fd.append('id', id);
      fd.append('aksi', action);

      fetch('listing_hapus.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(d => {
          if (d.status === 'ok') {
            showToast(action === 'hapus' ? 'Listing berhasil dihapus.' : 'Listing dinonaktifkan.', 'success');
            setTimeout(() => location.reload(), 1200);
          } else {
            showToast('Gagal: ' + (d.message || 'Terjadi kesalahan.'), 'error');
          }
        })
        .catch(() => showToast('Gagal terhubung ke server.', 'error'));
    }

    function showToast(msg, type = '') {
      const el = document.getElementById('tsToast');
      el.textContent = msg;
      el.className = 'ts-toast' + (type ? ' ' + type : '');
      el.classList.add('show');
      setTimeout(() => el.classList.remove('show'), 3000);
    }
  </script>
</body>

</html>