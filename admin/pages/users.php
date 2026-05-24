<?php
session_start();
include "../../koneksi.php";
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
    .content-area .table-toolbar .search-row .table-search-wrap {
      position: relative; display: flex; align-items: center;
      width: 100%; max-width: 320px; height: 40px;
      background: #ffffff; border: 1px solid var(--color-border);
      border-radius: var(--radius-md); box-sizing: border-box; padding: 0;
    }
    .content-area .table-toolbar .search-row .table-search-wrap .table-search-icon {
      position: absolute; left: 12px; top: 50%; transform: translateY(-50%);
      font-size: 1.1rem; color: var(--color-text-disabled); pointer-events: none; z-index: 2; margin: 0;
    }
    .content-area .table-toolbar .search-row .table-search-wrap .table-search-input {
      width: 100%; height: 100%; border: none; outline: none; background: transparent;
      font-family: var(--font-family); font-size: var(--text-sm); color: var(--color-text-primary);
      padding: 0 12px 0 38px; box-sizing: border-box;
    }
    .table-container .managed-table thead tr th.col-num { text-align: center; width: 65px; }
    .table-container .managed-table tbody tr td.col-num { text-align: center; width: 65px; font-size: var(--text-sm); color: var(--color-text-secondary); }

    /* Sort Dropdown */
    .sort-dropdown { position: relative; }
    .sort-menu {
      display: none; position: absolute; right: 0; top: calc(100% + 6px);
      background: #ffffff; border: 1px solid var(--color-border, #e5e7eb);
      border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,0.10);
      z-index: 200; min-width: 200px; overflow: hidden;
    }
    .sort-menu.open { display: block; }
    .sort-menu-item {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 16px; font-size: 14px; cursor: pointer;
      color: #374151; transition: background 0.15s; font-family: var(--font-family);
    }
    .sort-menu-item:hover { background: #f9fafb; }
    .sort-menu-item.active { color: var(--color-primary, #8b2500); font-weight: 600; background: #fff8f5; }
    .sort-menu-divider { height: 1px; background: #f3f4f6; margin: 4px 0; }
  </style>
</head>
<body>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <a class="logo-link" href="/teman_singgah/admin/pages/dashboard.html"></a>
        <div class="logo-section">
          <img alt="Logo Teman Singgah" class="logo-icon" src="/teman_singgah/assets/logo/logo_temansinggah.svg" />
          <img alt="Brand Name Teman Singgah" class="logo-name" src="/teman_singgah/assets/logo/label_temansinggah.svg" />
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title">Halaman Utama</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/dashboard.html"><i class="ph-bold ph-squares-four"></i>Dashboard</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Manajemen</div>
          <a class="nav-item active" href="/teman_singgah/admin/pages/users.php"><i class="ph-bold ph-users"></i>Pengguna</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/listings.html"><i class="ph-bold ph-house"></i>Properti</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/reservations.html"><i class="ph-bold ph-calendar-check"></i>Reservasi</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/transactions.html"><i class="ph-bold ph-currency-circle-dollar"></i>Transaksi</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Moderasi</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/reviews.html"><i class="ph-bold ph-star"></i>Ulasan</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/reports.html"><i class="ph-bold ph-flag"></i>Laporan</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Keuangan</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/payouts.html"><i class="ph-bold ph-money"></i>Pembayaran</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Sistem</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/settings.html"><i class="ph-bold ph-gear"></i>Pengaturan</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/logs.html"><i class="ph-bold ph-notepad"></i>Aktivitas</a>
        </div>
      </nav>
    </aside>

    <div class="main-container">
      <header class="topbar">
        <div class="topbar-left"><h1 class="page-title">Manajemen User</h1></div>
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
                  data-tanggal="<?= htmlspecialchars($row['tanggal_daftar']) ?>"
                >
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
                    <button aria-label="Edit user" class="action-button warning" title="Edit">
                      <i class="ph-bold ph-pencil"></i>
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
  <script src="/teman_singgah/admin/dashboard.js"></script>
</body>
</html>