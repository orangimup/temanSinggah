<?php
session_start();
include "../../koneksi.php";

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

/* ── AJAX: Toggle Status ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
  header('Content-Type: application/json');

  $user_id    = trim($_POST['user_id']    ?? '');
  $new_status = trim($_POST['new_status'] ?? '');

  if (!$user_id || !in_array($new_status, ['Aktif', 'Nonaktif'])) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
    exit;
  }

  $check = mysqli_query($koneksi, "SELECT role FROM users WHERE user_id = '" . mysqli_real_escape_string($koneksi, $user_id) . "'");
  $user  = mysqli_fetch_assoc($check);

  if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User tidak ditemukan.']);
    exit;
  }

  if ($user['role'] === 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Tidak dapat mengubah status Admin.']);
    exit;
  }

  $stmt = mysqli_prepare($koneksi, "UPDATE users SET status = ? WHERE user_id = ?");
  mysqli_stmt_bind_param($stmt, 'ss', $new_status, $user_id);
  $ok = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);

  echo json_encode($ok
    ? ['success' => true, 'new_status' => $new_status]
    : ['success' => false, 'message' => 'Gagal memperbarui status.']
  );
  exit;
}

$result = mysqli_query($koneksi, "SELECT * FROM users WHERE role != 'Admin' ORDER BY id ASC");
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8" />
  <meta content="width=device-width, initial-scale=1.0" name="viewport" />
  <title>Pengguna | Admin Teman Singgah</title>
  <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="/teman_singgah/components/root.css" />
  <link rel="stylesheet" href="/teman_singgah/admin/dashboard.css" />
  <link href="https://fonts.googleapis.com" rel="preconnect" />
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js" type="module"></script>
  <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet" />
  <style>
    .table-search-wrap {
      position: relative;
      display: flex;
      align-items: center;
      width: 100%;
      max-width: 320px;
      height: 40px;
      background: var(--color-bg-card);
      border: 1.5px solid var(--color-border);
      border-radius: var(--radius-full);
      box-sizing: border-box;
      transition: all var(--transition-fast);
    }

    .table-search-wrap:focus-within {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 4px rgba(139, 37, 0, 0.08);
    }

    .table-search-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1rem;
      color: var(--color-text-hint);
      pointer-events: none;
      z-index: 2;
    }

    .table-search-input {
      width: 100%;
      height: 100%;
      border: none;
      outline: none;
      background: transparent;
      font-family: var(--font-family);
      font-size: var(--text-sm);
      font-weight: var(--font-medium);
      color: var(--color-text-primary);
      padding: 0 16px 0 40px;
      box-sizing: border-box;
    }

    .table-search-input::placeholder {
      color: var(--color-text-hint);
      font-weight: var(--font-regular);
    }

    .table-container .managed-table thead tr th.col-num {
      text-align: center;
      width: 65px;
      font-family: var(--font-family);
      font-size: var(--text-sm);
      color: var(--color-text-primary);
      font-weight: var(--font-semibold);
    }

    .table-container .managed-table tbody tr td.col-num {
      text-align: center;
      width: 65px;
      font-family: var(--font-family);
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
      font-weight: var(--font-regular);
    }

    .sort-dropdown {
      position: relative;
    }

    .sort-menu {
      display: none;
      position: absolute;
      right: 0;
      top: calc(100% + 6px);
      background: #ffffff;
      border: 1px solid var(--color-border, #e5e7eb);
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.10);
      z-index: 200;
      min-width: 200px;
      overflow: hidden;
    }

    .sort-menu.open {
      display: block;
    }

    .sort-menu-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      font-size: 14px;
      cursor: pointer;
      color: #374151;
      transition: background 0.15s;
      font-family: var(--font-family);
    }

    .sort-menu-item:hover {
      background: #f9fafb;
    }

    .sort-menu-item.active {
      color: var(--color-primary, #8b2500);
      font-weight: 600;
      background: #fff8f5;
    }

    .sort-menu-divider {
      height: 1px;
      background: #f3f4f6;
      margin: 4px 0;
    }

    /* ── Detail Panel ── */
    .detail-panel {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      width: 400px;
      background: var(--color-bg-card);
      border-left: 1.5px solid var(--color-border-subtle);
      box-shadow: var(--shadow-search);
      z-index: var(--z-modal);
      transform: translateX(100%);
      transition: transform var(--transition-base);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .detail-panel.show {
      transform: translateX(0);
    }

    .detail-panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: var(--space-24) var(--space-32);
      border-bottom: 1.5px solid var(--color-border-subtle);
      flex-shrink: 0;
    }

    .detail-panel-title {
      font-family: var(--font-display);
      font-size: var(--text-lg);
      font-weight: var(--font-bold);
      color: var(--color-text-primary);
    }

    .detail-close {
      width: 36px;
      height: 36px;
      border: 1.5px solid var(--color-border-subtle);
      border-radius: var(--radius-md);
      background: transparent;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--color-text-secondary);
      font-size: var(--text-base);
      transition: all var(--transition-fast);
    }

    .detail-close:hover {
      border-color: var(--color-border-strong);
      color: var(--color-text-primary);
      background: var(--color-bg-section);
    }

    .detail-panel-body {
      flex: 1;
      overflow-y: auto;
      padding: var(--space-32);
      display: flex;
      flex-direction: column;
      gap: var(--space-24);
    }

    .detail-user-card {
      display: flex;
      align-items: center;
      gap: var(--space-16);
      padding: var(--space-20);
      background: var(--color-bg-section);
      border-radius: var(--radius-xl);
    }

    .detail-user-card .detail-avatar {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      overflow: hidden;
      flex-shrink: 0;
      background: var(--color-primary-light);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: var(--font-bold);
      font-size: var(--text-lg);
      color: var(--color-primary);
    }

    .detail-user-card .detail-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .detail-user-info h3 {
      font-size: var(--text-base);
      font-weight: var(--font-bold);
      color: var(--color-text-primary);
      margin: 0 0 var(--space-4) 0;
    }

    .detail-user-info small {
      font-size: var(--text-xs);
      color: var(--color-text-secondary);
    }

    .detail-section-label {
      font-size: var(--text-xs);
      font-weight: var(--font-semibold);
      color: var(--color-text-disabled);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: var(--space-8);
    }

    .detail-info-grid {
      display: flex;
      flex-direction: column;
      gap: var(--space-12);
    }

    .detail-info-row {
      display: flex;
      align-items: center;
      gap: var(--space-12);
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
    }

    .detail-info-row i {
      font-size: 1rem;
      color: var(--color-text-hint);
      width: 18px;
      flex-shrink: 0;
    }

    .detail-info-row span {
      color: var(--color-text-primary);
      font-weight: var(--font-medium);
    }

    .detail-panel-footer {
      padding: var(--space-20) var(--space-32);
      border-top: 1.5px solid var(--color-border-subtle);
      flex-shrink: 0;
    }

    .detail-footer-actions {
      display: flex;
      gap: var(--space-10);
    }

    .btn-detail-nonaktif {
      flex: 1;
      padding: var(--space-12) var(--space-20);
      border-radius: var(--radius-xl);
      border: 1.5px solid var(--color-border);
      background: transparent;
      color: var(--color-text-secondary);
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      cursor: pointer;
      font-family: var(--font-family);
      transition: all var(--transition-base);
    }

    .btn-detail-nonaktif:hover {
      border-color: var(--color-error);
      color: var(--color-error);
      background: var(--color-error-light, #fff1f0);
    }

    .btn-detail-nonaktif.state-aktifkan {
      border-color: #16a34a;
      color: #16a34a;
    }

    .btn-detail-nonaktif.state-aktifkan:hover {
      background: #f0fdf4;
    }

    .btn-detail-hapus {
      flex: 1;
      padding: var(--space-12) var(--space-20);
      border-radius: var(--radius-xl);
      border: none;
      background: var(--color-primary);
      color: #fff;
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      cursor: pointer;
      font-family: var(--font-family);
      transition: all var(--transition-base);
    }

    .btn-detail-hapus:hover {
      background: var(--color-primary-hover);
    }
  </style>
</head>

<body>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <a class="logo-link" href="/teman_singgah/admin/pages/dashboard.php"></a>
        <div class="logo-section">
          <img alt="Logo Teman Singgah" class="logo-icon" src="/teman_singgah/assets/logo/logo_temansinggah.svg" />
          <img alt="Brand Name Teman Singgah" class="logo-name" src="/teman_singgah/assets/logo/label_temansinggah.svg" />
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title">Halaman Utama</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/dashboard.php"><i class="ph-bold ph-squares-four"></i>Dashboard</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Manajemen</div>
          <a class="nav-item active" href="/teman_singgah/admin/pages/users.php"><i class="ph-bold ph-users"></i>Pengguna</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/listings.php"><i class="ph-bold ph-house"></i>Properti</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/reservations.php"><i class="ph-bold ph-calendar-check"></i>Reservasi</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/transactions.php"><i class="ph-bold ph-currency-circle-dollar"></i>Transaksi</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Moderasi</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/reviews.php"><i class="ph-bold ph-star"></i>Ulasan</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/reports.php"><i class="ph-bold ph-flag"></i>Laporan</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Keuangan</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/payouts.php"><i class="ph-bold ph-money"></i>Pembayaran</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Sistem</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/settings.php"><i class="ph-bold ph-gear"></i>Pengaturan</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/logs.php"><i class="ph-bold ph-notepad"></i>Aktivitas</a>
        </div>
      </nav>
    </aside>

    <div class="main-container">
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">Manajemen User</h1>
        </div>
        <div class="topbar-right">
          <span class="user-name">Admin utama</span>
          <div class="user-avatar">A</div>
        </div>
      </header>

      <main class="content-area">
        <div class="table-toolbar">
          <div class="search-row">
            <div class="table-search-wrap">
              <i class="ph-bold ph-magnifying-glass table-search-icon"></i>
              <input type="search" id="adminSearch" class="table-search-input" placeholder="Cari ID, nama, atau email..." />
            </div>
          </div>
        </div>

        <div class="filter-container">
          <div class="filter-group" id="filterGroup">
            <button class="filter-item active" data-filter="all">Semua</button>
            <button class="filter-item" data-filter="role:Host">Host</button>
            <button class="filter-item" data-filter="role:User">User</button>
            <button class="filter-item" data-filter="status:Nonaktif">Nonaktif</button>
          </div>
          <div class="sort-dropdown">
            <button class="sort-button" id="sortToggleBtn">
              <i class="ph-bold ph-faders-horizontal"></i>
              <span id="sortLabel">Urutkan: No. Urut</span>
              <i class="ph-bold ph-caret-down"></i>
            </button>
            <div class="sort-menu" id="sortMenu">
              <div class="sort-menu-item active" onclick="selectSort('default', 'No. Urut', this)">
                <i class="ph-bold ph-list-numbers"></i> No. Urut (Default)
              </div>
              <div class="sort-menu-item" onclick="selectSort('userid', 'ID User', this)">
                <i class="ph-bold ph-identification-card"></i> ID User
              </div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('name_asc', 'Nama A–Z', this)">
                <i class="ph-bold ph-sort-ascending"></i> Nama A–Z
              </div>
              <div class="sort-menu-item" onclick="selectSort('name_desc', 'Nama Z–A', this)">
                <i class="ph-bold ph-sort-descending"></i> Nama Z–A
              </div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('date_newest', 'Tanggal Terbaru', this)">
                <i class="ph-bold ph-calendar"></i> Tanggal Terbaru
              </div>
              <div class="sort-menu-item" onclick="selectSort('date_oldest', 'Tanggal Terlama', this)">
                <i class="ph-bold ph-calendar"></i> Tanggal Terlama
              </div>
            </div>
          </div>
        </div>

        <section class="table-section">
          <div class="table-container">
            <table class="managed-table" id="userTable">
              <thead>
                <tr>
                  <th class="col-num">No.</th>
                  <th>ID User</th>
                  <th>Nama</th>
                  <th>Email</th>
                  <th>Role</th>
                  <th>Status Akun</th>
                  <th>Tanggal Daftar</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)):
                  $photo_path = !empty($row['photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $row['photo'])
                    ? '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($row['photo'])
                    : null;
                ?>
                  <tr
                    data-role="<?= htmlspecialchars($row['role']) ?>"
                    data-status="<?= htmlspecialchars($row['status']) ?>"
                    data-userid="<?= htmlspecialchars($row['user_id']) ?>"
                    data-nama="<?= htmlspecialchars($row['nama']) ?>"
                    data-email="<?= htmlspecialchars($row['email']) ?>"
                    data-nohp="<?= htmlspecialchars($row['no_hp'] ?? '') ?>"
                    data-tanggal="<?= htmlspecialchars($row['tanggal_daftar']) ?>"
                    data-photo="<?= $photo_path ? htmlspecialchars($photo_path) : '' ?>">
                    <td class="col-num"><?= $no++ ?></td>
                    <td><?= htmlspecialchars($row['user_id']) ?></td>
                    <td>
                      <div class="table-cell">
                        <?php if ($photo_path): ?>
                          <div class="table-avatar" style="padding:0;overflow:hidden;">
                            <img src="<?= $photo_path ?>" alt="Foto" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
                          </div>
                        <?php else: ?>
                          <div class="table-avatar"><?= strtoupper(mb_substr($row['nama'], 0, 2)) ?></div>
                        <?php endif; ?>
                        <h3 class="table-name"><?= htmlspecialchars($row['nama']) ?></h3>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><span class="table-badge neutral"><?= htmlspecialchars($row['role']) ?></span></td>
                    <td>
                      <span class="table-badge <?= $row['status'] === 'Aktif' ? 'success' : 'danger' ?>">
                        <span class="badge-dot"></span><?= htmlspecialchars($row['status']) ?>
                      </span>
                    </td>
                    <td><?= date('d M Y', strtotime($row['tanggal_daftar'])) ?></td>
                    <td>
                      <button aria-label="Detail user" class="action-button info btn-detail" title="Detail">
                        <i class="ph-bold ph-eye"></i>
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
            <div class="table-pagination">
              <span class="pagination-info" id="paginationInfo"></span>
              <div class="pagination-controls"></div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <!-- ── Detail Panel ── -->
  <div class="detail-panel" id="detailPanel">
    <div class="detail-panel-header">
      <span class="detail-panel-title">Detail Pengguna</span>
      <button class="detail-close" id="detailClose" type="button">
        <i class="ph-bold ph-x"></i>
      </button>
    </div>
    <div class="detail-panel-body">

      <div class="detail-user-card">
        <div class="detail-avatar" id="detailAvatar"></div>
        <div class="detail-user-info">
          <h3 id="detailNama"></h3>
          <small id="detailUserId"></small>
        </div>
      </div>

      <div>
        <div class="detail-section-label">Informasi Akun</div>
        <div class="detail-info-grid">
          <div class="detail-info-row">
            <i class="ph-bold ph-envelope"></i>
            <span id="detailEmail"></span>
          </div>
          <div class="detail-info-row">
            <i class="ph-bold ph-phone"></i>
            <span id="detailNoHp"></span>
          </div>
          <div class="detail-info-row">
            <i class="ph-bold ph-shield-check"></i>
            <span id="detailRole"></span>
          </div>
          <div class="detail-info-row">
            <i class="ph-bold ph-calendar"></i>
            <span id="detailTanggal"></span>
          </div>
        </div>
      </div>

      <div>
        <div class="detail-section-label">Status</div>
        <div id="detailStatusBadge"></div>
      </div>

    </div>
    <div class="detail-panel-footer">
      <div class="detail-footer-actions">
        <button class="btn-detail-nonaktif" id="btnToggleStatus" type="button">Nonaktifkan</button>
      </div>
    </div>
  </div>

  <script src="/teman_singgah/admin/dashboard.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {

      const panel     = document.getElementById('detailPanel');
      const btnClose  = document.getElementById('detailClose');
      const btnToggle = document.getElementById('btnToggleStatus');

      let activeRow    = null;
      let activeUserId = '';
      let activeStatus = '';

      /* ── Update tampilan panel sesuai status ── */
      function updateStatusUI(status) {
        const isAktif = status === 'Aktif';

        document.getElementById('detailStatusBadge').innerHTML =
          `<span class="table-badge ${isAktif ? 'success' : 'danger'}">
            <span class="badge-dot"></span>${status}
          </span>`;

        btnToggle.textContent = isAktif ? 'Nonaktifkan' : 'Aktifkan';
        btnToggle.classList.toggle('state-aktifkan', !isAktif);
      }

      /* ── Buka panel & isi data ── */
      function openDetail(row) {
        activeRow    = row;
        activeUserId = row.dataset.userid;
        activeStatus = row.dataset.status;

        const nama    = row.dataset.nama;
        const email   = row.dataset.email;
        const noHp    = row.dataset.nohp;
        const role    = row.dataset.role;
        const tanggal = row.dataset.tanggal;
        const photo   = row.dataset.photo;

        const avatarEl = document.getElementById('detailAvatar');
        avatarEl.innerHTML = photo
          ? `<img src="${photo}" alt="Foto" style="width:100%;height:100%;object-fit:cover;" />`
          : nama.substring(0, 2).toUpperCase();

        document.getElementById('detailNama').textContent    = nama;
        document.getElementById('detailUserId').textContent  = activeUserId;
        document.getElementById('detailEmail').textContent   = email;
        document.getElementById('detailNoHp').textContent    = noHp || 'Belum diatur';
        document.getElementById('detailRole').textContent    = role;
        document.getElementById('detailTanggal').textContent = new Date(tanggal).toLocaleDateString('id-ID', {
          day: 'numeric', month: 'long', year: 'numeric'
        });

        updateStatusUI(activeStatus);
        panel.classList.add('show');
      }

      /* ── Tombol Nonaktifkan / Aktifkan ── */
      btnToggle.addEventListener('click', function () {
        if (!activeUserId) return;

        const newStatus = activeStatus === 'Aktif' ? 'Nonaktif' : 'Aktif';
        const aksi      = activeStatus === 'Aktif' ? 'menonaktifkan' : 'mengaktifkan';
        const nama      = document.getElementById('detailNama').textContent;

        if (!confirm(`Yakin ingin ${aksi} akun "${nama}"?`)) return;

        btnToggle.disabled    = true;
        btnToggle.textContent = 'Menyimpan…';

        const fd = new FormData();
        fd.append('action',     'toggle_status');
        fd.append('user_id',    activeUserId);
        fd.append('new_status', newStatus);

        fetch(window.location.pathname, { method: 'POST', body: fd })
          .then(r => r.json())
          .then(data => {
            if (data.success) {
              /* Update state lokal */
              activeStatus = data.new_status;

              /* Update atribut data-* di baris tabel */
              if (activeRow) {
                activeRow.dataset.status = activeStatus;

                /* Update badge di kolom Status tabel */
                const badgeEl = activeRow.querySelector('td .table-badge.success, td .table-badge.danger');
                if (badgeEl) {
                  badgeEl.className = `table-badge ${activeStatus === 'Aktif' ? 'success' : 'danger'}`;
                  badgeEl.innerHTML = `<span class="badge-dot"></span>${activeStatus}`;
                }
              }

              updateStatusUI(activeStatus);
            } else {
              alert('Gagal: ' + (data.message || 'Terjadi kesalahan.'));
              updateStatusUI(activeStatus);
            }
          })
          .catch(() => {
            alert('Koneksi bermasalah. Coba lagi.');
            updateStatusUI(activeStatus);
          })
          .finally(() => {
            btnToggle.disabled = false;
          });
      });

      /* ── Delegasi klik tombol detail ── */
      document.querySelector('#userTable tbody').addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-detail');
        if (btn) openDetail(btn.closest('tr'));
      });

      /* ── Tutup panel ── */
      btnClose.addEventListener('click', () => panel.classList.remove('show'));
      panel.addEventListener('click', e => {
        if (e.target === panel) panel.classList.remove('show');
      });

    });
  </script>
</body>

</html>