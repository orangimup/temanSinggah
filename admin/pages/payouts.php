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
            <a href="dashboard.php" class="nav-item">
              <i class="ph-bold ph-squares-four"></i>
              Dashboard
            </a>
          </div>

          <div class="nav-section">
            <div class="nav-section-title">Manajemen</div>
            <a href="users.php" class="nav-item">
              <i class="ph-bold ph-users"></i>
              Pengguna
            </a>
            <a href="listings.php" class="nav-item">
              <i class="ph-bold ph-house"></i>
              Properti
            </a>
            <a href="reservations.php" class="nav-item">
              <i class="ph-bold ph-calendar-check"></i>
              Reservasi
            </a>
            <a href="transactions.php" class="nav-item">
              <i class="ph-bold ph-currency-circle-dollar"></i>
              Transaksi
            </a>
            <a class="nav-item" href="/teman_singgah/admin/pages/promos.php"><i class="ph-bold ph-tag"></i>Promo &
            Deals</a>
          </div>

          <div class="nav-section">
            <div class="nav-section-title">Moderasi</div>
            <a href="reviews.php" class="nav-item">
              <i class="ph-bold ph-star"></i>
              Ulasan
            </a>
            <a href="reports.php" class="nav-item">
              <i class="ph-bold ph-flag"></i>
              Laporan
            </a>
          </div>

          <div class="nav-section">
            <div class="nav-section-title">Keuangan</div>
            <a href="payouts.php" class="nav-item active">
              <i class="ph-bold ph-money"></i>
              Pembayaran
            </a>
          </div>

          <div class="nav-section">
            <div class="nav-section-title">Sistem</div>
            <a href="settings.php" class="nav-item">
              <i class="ph-bold ph-gear"></i>
              Pengaturan
            </a>
            <a href="logs.php" class="nav-item">
              <i class="ph-bold ph-notepad"></i>
              Aktivitas
            </a>
          </div>
        </nav>
      </aside>

      <div class="main-container">
        <header class="topbar">
          <div class="topbar-left">
            <h1 class="page-title">Pencairan Dana Host</h1>
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
              <button class="filter-item" data-filter="payout_status:Dijadwalkan">
                Dijadwalkan
              </button>
              <button
                class="filter-item"
                data-filter="payout_status:Processing">
                Diproses
              </button>
              <button class="filter-item" data-filter="payout_status:Completed">
                Selesai
              </button>
              <button class="filter-item" data-filter="payout_status:Failed">
                Gagal
              </button>
            </div>
            <div class="sort-dropdown">
              <button
                class="sort-button"
                data-sort="payout"
                data-inactive="Urutkan: Jadwal Transfer"
                data-active="Urutkan: Jumlah Payout">
                <i class="ph-bold ph-faders-horizontal"></i>
                <span>Urutkan: Jadwal Transfer</span>
                <i class="ph-bold ph-caret-down"></i>
              </button>
            </div>
          </div>

          <section class="table-section">
            <div class="table-container">
              <table>
                <thead>
                  <tr>
                    <th>Nama Host</th>
                    <th>Jumlah Payout</th>
                    <th>Bank Tujuan</th>
                    <th>Jadwal Transfer</th>
                    <th>Status</th>
                    <th>Tanggal Diproses</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>Rp4.875.000</td>
                    <td>BCA — 1234567890</td>
                    <td>5 Mei 2026</td>
                    <td>
                      <span class="table-badge warning">
                        <span class="badge-dot"></span>
                        Dijadwalkan
                      </span>
                    </td>
                    <td>—</td>
                    <td>
                      <div class="action-group">
                        <button
                          class="action-button success"
                          aria-label="Proses"
                          title="Proses">
                          <i class="ph-bold ph-play-circle"></i>
                        </button>
                        <button
                          class="action-button info"
                          aria-label="Lihat detail"
                          title="Lihat">
                          <i class="ph-bold ph-eye"></i>
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>Rp2.340.000</td>
                    <td>Mandiri — 9876543210</td>
                    <td>5 Mei 2026</td>
                    <td>
                      <span class="table-badge info">
                        <span class="badge-dot"></span>
                        Diproses
                      </span>
                    </td>
                    <td>2 Mei 2026, 09:00</td>
                    <td>
                      <div class="action-group">
                        <button
                          class="action-button info"
                          aria-label="Lihat detail"
                          title="Lihat">
                          <i class="ph-bold ph-eye"></i>
                        </button>
                        <button
                          class="action-button error"
                          aria-label="Hapus listing"
                          title="Hapus">
                          <i class="ph-bold ph-x"></i>
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>Rp6.120.000</td>
                    <td>BRI — 1122334455</td>
                    <td>2 Mei 2026</td>
                    <td>
                      <span class="table-badge success">
                        <span class="badge-dot"></span>
                        Selesai
                      </span>
                    </td>
                    <td>2 Mei 2026, 08:15</td>
                    <td>
                      <div class="action-group">
                        <button
                          class="action-button info"
                          aria-label="Lihat detail"
                          title="Lihat">
                          <i class="ph-bold ph-eye"></i>
                        </button>
                        <button
                          class="action-button success"
                          aria-label="Hapus listing"
                          title="Hapus">
                          <i class="ph-bold ph-download"></i>
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>Rp1.890.000</td>
                    <td>BCA — 5566778899</td>
                    <td>2 Mei 2026</td>
                    <td>
                      <span class="table-badge error">
                        <span class="badge-dot"></span>
                        Gagal
                      </span>
                    </td>
                    <td>2 Mei 2026, 08:00</td>
                    <td>
                      <div class="action-group">
                        <button
                          class="action-button success"
                          aria-label="Edit properti"
                          title="Edit">
                          <i class="ph-bold ph-arrow-clockwise"></i>
                        </button>
                        <button
                          class="action-button info"
                          aria-label="Lihat detail"
                          title="Lihat">
                          <i class="ph-bold ph-eye"></i>
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>Rp3.450.000</td>
                    <td>BNI — 9988776655</td>
                    <td>12 Mei 2026</td>
                    <td>
                      <span class="table-badge warning">
                        <span class="badge-dot"></span>
                        Dijadwalkan
                      </span>
                    </td>
                    <td>—</td>
                    <td>
                      <div class="action-group">
                        <button
                          class="action-button success"
                          aria-label="Edit properti"
                          title="Edit">
                          <i class="ph-bold ph-play-circle"></i>
                        </button>
                        <button
                          class="action-button info"
                          aria-label="Lihat detail"
                          title="Lihat">
                          <i class="ph-bold ph-eye"></i>
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>Rp5.670.000</td>
                    <td>Mandiri — 2233445566</td>
                    <td>12 Mei 2026</td>
                    <td>
                      <span class="table-badge info">
                        <span class="badge-dot"></span>
                        Diproses
                      </span>
                    </td>
                    <td>2 Mei 2026, 10:30</td>
                    <td>
                      <div class="action-group">
                        <button
                          class="action-button info"
                          aria-label="Lihat detail"
                          title="Lihat">
                          <i class="ph-bold ph-eye"></i>
                        </button>
                        <button
                          class="action-button error"
                          aria-label="Hapus listing"
                          title="Hapus">
                          <i class="ph-bold ph-x"></i>
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>Rp7.230.000</td>
                    <td>BCA — 3344556677</td>
                    <td>29 Apr 2026</td>
                    <td>
                      <span class="table-badge success">
                        <span class="badge-dot"></span>
                        Selesai
                      </span>
                    </td>
                    <td>29 Apr 2026, 08:00</td>
                    <td>
                      <div class="action-group">
                        <button
                          class="action-button info"
                          aria-label="Lihat detail"
                          title="Lihat">
                          <i class="ph-bold ph-eye"></i>
                        </button>
                        <button
                          class="action-button success"
                          aria-label="Hapus listing"
                          title="Hapus">
                          <i class="ph-bold ph-download"></i>
                        </button>
                      </div>
                    </td>
                  </tr>

                  <tr>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar">BS</div>
                        <h3 class="table-name">Budi Santoso</h3>
                      </div>
                    </td>
                    <td>Rp980.000</td>
                    <td>BRI — 4455667788</td>
                    <td>29 Apr 2026</td>
                    <td>
                      <span class="table-badge error">
                        <span class="badge-dot"></span>
                        Gagal
                      </span>
                    </td>
                    <td>29 Apr 2026, 08:00</td>
                    <td>
                      <div class="action-group">
                        <button
                          class="action-button success"
                          aria-label="Edit properti"
                          title="Edit">
                          <i class="ph-bold ph-arrow-clockwise"></i>
                        </button>
                        <button
                          class="action-button info"
                          aria-label="Lihat detail"
                          title="Lihat">
                          <i class="ph-bold ph-eye"></i>
                        </button>
                      </div>
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