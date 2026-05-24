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

  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />

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
  </style>
</head>

<body>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <a href="dashboard.php" class="logo-link"></a>
        <div class="logo-section">
          <img src="../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
          <img src="../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
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
          <a href="reservations.php" class="nav-item active" title="Reservasi">
            <i class="ph-bold ph-calendar-check"></i>
            Reservasi
          </a>
          <a href="transactions.php" class="nav-item" title="Transaksi">
            <i class="ph-bold ph-currency-circle-dollar"></i>
            Transaksi
          </a>
        </div>

        <div class="nav-section">
          <div class="nav-section-title">Moderasi</div>
          <a href="reviews.php" class="nav-item" title="Ulasan">
            <i class="ph-bold ph-star"></i>
            Ulasan
          </a>
          <a href="reports.php" class="nav-item" title="Laporan">
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
          <h1 class="page-title">Manajemen Reservasi</h1>
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
              <input type="search" id="adminSearch" class="table-search-input" placeholder="Cari data..."
                onkeyup="refreshTable()" />
            </div>
          </div>
        </div>
        <div class="filter-container">
          <div class="filter-group" id="filterGroup">
            <button class="filter-item active">Semua</button>
            <button class="filter-item" data-filter="reservation_status:Pending">
              Menunggu
            </button>
            <button class="filter-item" data-filter="reservation_status:Confirmed">
              Dikonfirmasi
            </button>
            <button class="filter-item" data-filter="reservation_status:Cancelled">
              Dibatalkan
            </button>
            <button class="filter-item" data-filter="reservation_status:Completed">
              Selesai
            </button>
          </div>
          <div class="sort-dropdown">
            <button class="sort-button" data-sort="reservation" data-inactive="Urutkan: Check-in"
              data-active="Urutkan: Check-out">
              <i class="ph-bold ph-faders-horizontal"></i>
              <span>Urutkan: Check-in</span>
              <i class="ph-bold ph-caret-down"></i>
            </button>
          </div>
        </div>

        <section class="table-section">
          <div class="table-container">
            <table class="managed-table" id="reservationTable">
              <thead>
                <tr>
                  <th class="col-num">No.</th>
                  <th>ID Reservasi</th>
                  <th>Nama Tamu</th>
                  <th>Listing</th>
                  <th>Check-in</th>
                  <th>Check-out</th>
                  <th>Total Harga</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td class="col-num">-</td>
                  <td><span class="id-code">#RSV-2026-0042</span></td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>Villa Sunset Tepi Pantai</td>
                  <td>5 Mei 2026</td>
                  <td>7 Mei 2026</td>
                  <td>Rp2.450.000</td>
                  <td>
                    <span class="table-badge success">
                      <span class="badge-dot"></span>
                      Dikonfirmasi
                    </span>
                  </td>
                  <td>
                    <button class="action-button info" aria-label="Lihat detail" title="Lihat">
                      <i class="ph-bold ph-eye"></i>
                    </button>
                  </td>
                </tr>

                <tr>
                  <td class="col-num">-</td>
                  <td><span class="id-code">#RSV-2026-0042</span></td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>Villa Sunset Tepi Pantai</td>
                  <td>5 Mei 2026</td>
                  <td>7 Mei 2026</td>
                  <td>Rp2.450.000</td>
                  <td>
                    <span class="table-badge success">
                      <span class="badge-dot"></span>
                      Dikonfirmasi
                    </span>
                  </td>
                  <td>
                    <button class="action-button info" aria-label="Lihat detail" title="Lihat">
                      <i class="ph-bold ph-eye"></i>
                    </button>
                  </td>
                </tr>

                <tr>
                  <td class="col-num">-</td>
                  <td><span class="id-code">#RSV-2026-0042</span></td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>Villa Sunset Tepi Pantai</td>
                  <td>6 Mei 2026</td>
                  <td>7 Mei 2026</td>
                  <td>Rp2.450.000</td>
                  <td>
                    <span class="table-badge success">
                      <span class="badge-dot"></span>
                      Dikonfirmasi
                    </span>
                  </td>
                  <td>
                    <button class="action-button info" aria-label="Lihat detail" title="Lihat">
                      <i class="ph-bold ph-eye"></i>
                    </button>
                  </td>
                </tr>

                <tr>
                  <td class="col-num">-</td>
                  <td><span class="id-code">#RSV-2026-0042</span></td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>Villa Sunset Tepi Pantai</td>
                  <td>5 Mei 2026</td>
                  <td>7 Mei 2026</td>
                  <td>Rp2.450.000</td>
                  <td>
                    <span class="table-badge success">
                      <span class="badge-dot"></span>
                      Dikonfirmasi
                    </span>
                  </td>
                  <td>
                    <button class="action-button info" aria-label="Lihat detail" title="Lihat">
                      <i class="ph-bold ph-eye"></i>
                    </button>
                  </td>
                </tr>

                <tr>
                  <td class="col-num">-</td>
                  <td><span class="id-code">#RSV-2026-0042</span></td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>Villa Sunset Tepi Pantai</td>
                  <td>5 Mei 2026</td>
                  <td>7 Mei 2026</td>
                  <td>Rp2.450.000</td>
                  <td>
                    <span class="table-badge success">
                      <span class="badge-dot"></span>
                      Dikonfirmasi
                    </span>
                  </td>
                  <td>
                    <button class="action-button info" aria-label="Lihat detail" title="Lihat">
                      <i class="ph-bold ph-eye"></i>
                    </button>
                  </td>
                </tr>

                <tr>
                  <td class="col-num">-</td>
                  <td><span class="id-code">#RSV-2026-0042</span></td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>Villa Sunset Tepi Pantai</td>
                  <td>5 Mei 2026</td>
                  <td>7 Mei 2026</td>
                  <td>Rp2.450.000</td>
                  <td>
                    <span class="table-badge success">
                      <span class="badge-dot"></span>
                      Dikonfirmasi
                    </span>
                  </td>
                  <td>
                    <button class="action-button info" aria-label="Lihat detail" title="Lihat">
                      <i class="ph-bold ph-eye"></i>
                    </button>
                  </td>
                </tr>

                <tr>
                  <td class="col-num">-</td>
                  <td><span class="id-code">#RSV-2026-0042</span></td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>Villa Sunset Tepi Pantai</td>
                  <td>5 Mei 2026</td>
                  <td>7 Mei 2026</td>
                  <td>Rp2.450.000</td>
                  <td>
                    <span class="table-badge success">
                      <span class="badge-dot"></span>
                      Dikonfirmasi
                    </span>
                  </td>
                  <td>
                    <button class="action-button info" aria-label="Lihat detail" title="Lihat">
                      <i class="ph-bold ph-eye"></i>
                    </button>
                  </td>
                </tr>

                <tr>
                  <td class="col-num">-</td>
                  <td><span class="id-code">#RSV-2026-0042</span></td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>Villa Sunset Tepi Pantai</td>
                  <td>5 Mei 2026</td>
                  <td>7 Mei 2026</td>
                  <td>Rp2.450.000</td>
                  <td>
                    <span class="table-badge success">
                      <span class="badge-dot"></span>
                      Dikonfirmasi
                    </span>
                  </td>
                  <td>
                    <button class="action-button info" aria-label="Lihat detail" title="Lihat">
                      <i class="ph-bold ph-eye"></i>
                    </button>
                  </td>
                </tr>

                <tr>
                  <td class="col-num">-</td>
                  <td><span class="id-code">#RSV-2026-0042</span></td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>Villa Sunset Tepi Pantai</td>
                  <td>5 Mei 2026</td>
                  <td>7 Mei 2026</td>
                  <td>Rp2.450.000</td>
                  <td>
                    <span class="table-badge success">
                      <span class="badge-dot"></span>
                      Dikonfirmasi
                    </span>
                  </td>
                  <td>
                    <button class="action-button info" aria-label="Lihat detail" title="Lihat">
                      <i class="ph-bold ph-eye"></i>
                    </button>
                  </td>
                </tr>

                <tr>
                  <td class="col-num">-</td>
                  <td><span class="id-code">#RSV-2026-0042</span></td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>Villa Sunset Tepi Pantai</td>
                  <td>5 Mei 2026</td>
                  <td>7 Mei 2026</td>
                  <td>Rp2.450.000</td>
                  <td>
                    <span class="table-badge success">
                      <span class="badge-dot"></span>
                      Dikonfirmasi
                    </span>
                  </td>
                  <td>
                    <button class="action-button info" aria-label="Lihat detail" title="Lihat">
                      <i class="ph-bold ph-eye"></i>
                    </button>
                  </td>
                </tr>
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

  <script>
    function refreshTable() {
      const input = document.getElementById("adminSearch");
      const filter = input.value.toLowerCase();
      const table = document.getElementById("reservationTable");
      const tr = table.getElementsByTagName("tr");

      let numCounter = 1;

      for (let i = 1; i < tr.length; i++) {
        let match = false;
        const numCell = tr[i].getElementsByTagName("td")[0];
        const idCell = tr[i].getElementsByTagName("td")[1];
        const guestCell = tr[i].getElementsByTagName("td")[2];
        const listingCell = tr[i].getElementsByTagName("td")[3];

        if (idCell || guestCell || listingCell) {
          const idText = idCell.querySelector('.id-code')?.textContent || idCell.textContent || "";
          const guestText = guestCell.querySelector('.table-name')?.textContent || guestCell.textContent || "";
          const listingText = listingCell.textContent || listingCell.innerText || "";

          if (
            idText.toLowerCase().indexOf(filter) > -1 ||
            guestText.toLowerCase().indexOf(filter) > -1 ||
            listingText.toLowerCase().indexOf(filter) > -1
          ) {
            match = true;
          }
        }

        if (match) {
          tr[i].style.display = "";
          if (numCell) numCell.innerText = numCounter;
          numCounter++;
        } else {
          tr[i].style.display = "none";
          if (numCell) numCell.innerText = "";
        }
      }

      const totalVisible = numCounter - 1;
      const paginationInfo = document.getElementById("paginationInfo");

      if (filter === "") {
        paginationInfo.innerText = `Menampilkan 1-${totalVisible} dari ${totalVisible} listing`;
      } else {
        paginationInfo.innerText = `Menampilkan ${totalVisible} hasil pencarian`;
      }
    }

    document.addEventListener("DOMContentLoaded", function () {
      refreshTable();
    });
  </script>
  <script src="../dashboard.js"></script>
</body>

</html>