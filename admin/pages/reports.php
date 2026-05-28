<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard | Admin Teman Singgah</title>
    <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />

    <link rel="stylesheet" href="../../components/root.css" />
    <link rel="stylesheet" href="../dashboard.css" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
      rel="stylesheet" />

    <script
      type="module"
      src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
  </head>

  <body>
    <div class="admin-layout">
      <aside class="sidebar">
        <div class="sidebar-header">
          <a href="dashboard.php" class="logo-link"></a>
          <div class="logo-section">
            <img
              src="../../assets/logo/logo_temansinggah.svg"
              alt="Logo Teman Singgah"
              class="logo-icon" />
            <img
              src="../../assets/logo/label_temansinggah.svg"
              alt="Brand Name Teman Singgah"
              class="logo-name" />
          </div>
        </div>

        <nav class="sidebar-nav">
          <div class="nav-section">
            <div class="nav-section-title">Halaman Utama</div>
            <a href="dashboard.php" class="nav-item" title="Dashboard">
              <i class="ph-bold ph-squares-four"></i>
              Dashboard
            </a>
          </div>

          <div class="nav-section">
            <div class="nav-section-title">Manajemen</div>
            <a href="users.php" class="nav-item" title="Pengguna">
              <i class="ph-bold ph-users"></i>
              Pengguna
            </a>
            <a href="listings.php" class="nav-item" title="Properti">
              <i class="ph-bold ph-house"></i>
              Properti
            </a>
            <a href="reservations.php" class="nav-item" title="Reservasi">
              <i class="ph-bold ph-calendar-check"></i>
              Reservasi
            </a>
            <a href="transactions.php" class="nav-item" title="Transaksi">
              <i class="ph-bold ph-currency-circle-dollar"></i>
              Transaksi
            </a>
            <a class="nav-item" href="/teman_singgah/admin/pages/promos.php"><i class="ph-bold ph-tag"></i>Promo &
            Deals</a>
          </div>

          <div class="nav-section">
            <div class="nav-section-title">Moderasi</div>
            <a href="reviews.php" class="nav-item" title="Ulasan">
              <i class="ph-bold ph-star"></i>
              Ulasan
            </a>
            <a href="reports.php" class="nav-item active" title="Laporan">
              <i class="ph-bold ph-flag"></i>
              Laporan
            </a>
          </div>

          <div class="nav-section">
            <div class="nav-section-title">Keuangan</div>
            <a href="payouts.php" class="nav-item" title="Pembayaran">
              <i class="ph-bold ph-money"></i>
              Pembayaran
            </a>
          </div>

          <div class="nav-section">
            <div class="nav-section-title">Sistem</div>
            <a href="settings.php" class="nav-item" title="Pengaturan">
              <i class="ph-bold ph-gear"></i>
              Pengaturan
            </a>
            <a href="logs.php" class="nav-item" title="Aktivitas">
              <i class="ph-bold ph-notepad"></i>
              Aktivitas
            </a>
          </div>
        </nav>
      </aside>

      <div class="main-container">
        <header class="topbar">
          <div class="topbar-left">
            <h1 class="page-title">Laporan & Komplain</h1>
          </div>
          <div class="topbar-right">
            <span class="user-name">Admin utama</span>
            <div class="user-avatar">A</div>
          </div>
        </header>

        <main class="content-area">
          <div class="filter-container">
            <div class="filter-group" id="filterGroup">
              <button class="filter-item active">Semua</button>
              <button class="filter-item" data-filter="report_type:Komplain">
                Komplain
              </button>
              <button class="filter-item" data-filter="report_type:Pelanggaran">
                Pelanggaran
              </button>
              <button class="filter-item" data-filter="report_type:Lainnya">
                Lainnya
              </button>
              <button class="filter-item" data-filter="report_status:Open">
                Open
              </button>
              <button class="filter-item" data-filter="report_status:Resolved">
                Resolved
              </button>
            </div>
            <div class="sort-dropdown">
              <button
                class="sort-button"
                data-sort="report"
                data-inactive="Urutkan: Tanggal Melapor"
                data-active="Urutkan: Nama Pelapor">
                <i class="ph-bold ph-faders-horizontal"></i>
                <span>Urutkan: Tanggal Melapor</span>
                <i class="ph-bold ph-caret-down"></i>
              </button>
            </div>
          </div>

          <section class="table-section">
            <div class="table-container">
              <table>
                <thead>
                  <tr>
                    <th>ID Laporan</th>
                    <th>Nama Pelapor</th>
                    <th>Tipe</th>
                    <th>Deskripsi</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td><span class="id-code">#RPT-2026-0015</span></td>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>
                      <span class="badge badge-warning">Komplain</span>
                    </td>
                    <td>
                      <div class="table-description">
                        Tamu merusak perabotan villa dan menolak membayar ganti
                        rugi
                      </div>
                    </td>
                    <td>
                      <span class="table-badge warning">
                        <span class="badge-dot"></span>
                        Open
                      </span>
                    </td>
                    <td>2 Mei 2026</td>
                    <td>
                      <button
                        class="action-button info"
                        aria-label="Lihat detail"
                        title="Lihat">
                        <i class="ph-bold ph-eye"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td><span class="id-code">#RPT-2026-0015</span></td>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>
                      <span class="badge badge-warning">Komplain</span>
                    </td>
                    <td>
                      <div class="table-description">
                        Tamu merusak perabotan villa dan menolak membayar ganti
                        rugi
                      </div>
                    </td>
                    <td>
                      <span class="table-badge warning">
                        <span class="badge-dot"></span>
                        Open
                      </span>
                    </td>
                    <td>2 Mei 2026</td>
                    <td>
                      <button
                        class="action-button info"
                        aria-label="Lihat detail"
                        title="Lihat">
                        <i class="ph-bold ph-eye"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td><span class="id-code">#RPT-2026-0015</span></td>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>
                      <span class="badge badge-warning">Komplain</span>
                    </td>
                    <td>
                      <div class="table-description">
                        Tamu merusak perabotan villa dan menolak membayar ganti
                        rugi
                      </div>
                    </td>
                    <td>
                      <span class="table-badge warning">
                        <span class="badge-dot"></span>
                        Open
                      </span>
                    </td>
                    <td>2 Mei 2026</td>
                    <td>
                      <button
                        class="action-button info"
                        aria-label="Lihat detail"
                        title="Lihat">
                        <i class="ph-bold ph-eye"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td><span class="id-code">#RPT-2026-0015</span></td>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>
                      <span class="badge badge-warning">Komplain</span>
                    </td>
                    <td>
                      <div class="table-description">
                        Tamu merusak perabotan villa dan menolak membayar ganti
                        rugi
                      </div>
                    </td>
                    <td>
                      <span class="table-badge warning">
                        <span class="badge-dot"></span>
                        Open
                      </span>
                    </td>
                    <td>2 Mei 2026</td>
                    <td>
                      <button
                        class="action-button info"
                        aria-label="Lihat detail"
                        title="Lihat">
                        <i class="ph-bold ph-eye"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td><span class="id-code">#RPT-2026-0015</span></td>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>
                      <span class="badge badge-warning">Komplain</span>
                    </td>
                    <td>
                      <div class="table-description">
                        Tamu merusak perabotan villa dan menolak membayar ganti
                        rugi
                      </div>
                    </td>
                    <td>
                      <span class="table-badge warning">
                        <span class="badge-dot"></span>
                        Open
                      </span>
                    </td>
                    <td>2 Mei 2026</td>
                    <td>
                      <button
                        class="action-button info"
                        aria-label="Lihat detail"
                        title="Lihat">
                        <i class="ph-bold ph-eye"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td><span class="id-code">#RPT-2026-0015</span></td>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>
                      <span class="badge badge-warning">Komplain</span>
                    </td>
                    <td>
                      <div class="table-description">
                        Tamu merusak perabotan villa dan menolak membayar ganti
                        rugi
                      </div>
                    </td>
                    <td>
                      <span class="table-badge warning">
                        <span class="badge-dot"></span>
                        Open
                      </span>
                    </td>
                    <td>2 Mei 2026</td>
                    <td>
                      <button
                        class="action-button info"
                        aria-label="Lihat detail"
                        title="Lihat">
                        <i class="ph-bold ph-eye"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td><span class="id-code">#RPT-2026-0015</span></td>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>
                      <span class="badge badge-warning">Komplain</span>
                    </td>
                    <td>
                      <div class="table-description">
                        Tamu merusak perabotan villa dan menolak membayar ganti
                        rugi
                      </div>
                    </td>
                    <td>
                      <span class="table-badge warning">
                        <span class="badge-dot"></span>
                        Open
                      </span>
                    </td>
                    <td>2 Mei 2026</td>
                    <td>
                      <button
                        class="action-button info"
                        aria-label="Lihat detail"
                        title="Lihat">
                        <i class="ph-bold ph-eye"></i>
                      </button>
                    </td>
                  </tr>

                  <tr>
                    <td><span class="id-code">#RPT-2026-0015</span></td>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>
                      <span class="badge badge-warning">Komplain</span>
                    </td>
                    <td>
                      <div class="table-description">
                        Tamu merusak perabotan villa dan menolak membayar ganti
                        rugi
                      </div>
                    </td>
                    <td>
                      <span class="table-badge warning">
                        <span class="badge-dot"></span>
                        Open
                      </span>
                    </td>
                    <td>2 Mei 2026</td>
                    <td>
                      <button
                        class="action-button info"
                        aria-label="Lihat detail"
                        title="Lihat">
                        <i class="ph-bold ph-eye"></i>
                      </button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </section>
        </main>
      </div>
    </div>

    <script src="../dashboard.js"></script>
  </body>
</html>