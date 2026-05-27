<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

// ── Parameter filter & pencarian ──────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$status_f = trim($_GET['status'] ?? '');
$sort = trim($_GET['sort'] ?? 'checkin_asc');

// ── Paginasi ──────────────────────────────────────────────────────────────────
$per_page = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

// ── Bangun klausa WHERE ───────────────────────────────────────────────────────
$conditions = [];
$params = [];
$types = '';

if ($search !== '') {
  $like = '%' . $search . '%';
  $conditions[] = "(u.nama LIKE ? OR l.judul LIKE ? OR b.id LIKE ?)";
  $params[] = $like;
  $params[] = $like;
  $params[] = $like;
  $types .= 'sss';
}

if ($status_f !== '') {
  $conditions[] = "b.status = ?";
  $params[] = $status_f;
  $types .= 's';
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

// ── ORDER BY ──────────────────────────────────────────────────────────────────
$order_map = [
  'checkin_asc' => 'b.checkin ASC',
  'checkin_desc' => 'b.checkin DESC',
  'checkout_asc' => 'b.checkout ASC',
  'checkout_desc' => 'b.checkout DESC',
  'harga_desc' => 'b.total_harga DESC',
  'harga_asc' => 'b.total_harga ASC',
  'terbaru' => 'b.dibuat_pada DESC',
];
$order = $order_map[$sort] ?? 'b.checkin ASC';

// ── Hitung total data (untuk paginasi) ────────────────────────────────────────
$count_sql = "
    SELECT COUNT(*) AS total
    FROM bookings b
    JOIN users    u ON u.id = b.user_id
    JOIN listings l ON l.id = b.listing_id
    $where
";
$count_stmt = $conn->prepare($count_sql);
if ($params) {
  $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_data = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = (int) ceil($total_data / $per_page);
$count_stmt->close();

// ── Ambil data utama ──────────────────────────────────────────────────────────
$data_sql = "
    SELECT
        b.id,
        b.checkin,
        b.checkout,
        b.jumlah_tamu,
        b.total_harga,
        b.status,
        b.dibuat_pada,
        u.nama  AS nama_tamu,
        u.email AS email_tamu,
        l.judul AS nama_listing
    FROM bookings b
    JOIN users    u ON u.id = b.user_id
    JOIN listings l ON l.id = b.listing_id
    $where
    ORDER BY $order
    LIMIT ? OFFSET ?
";

$params_data = array_merge($params, [$per_page, $offset]);
$types_data = $types . 'ii';
$data_stmt = $conn->prepare($data_sql);
$data_stmt->bind_param($types_data, ...$params_data);
$data_stmt->execute();
$rows = $data_stmt->get_result();
$data_stmt->close();

// ── Map status ke label & badge ───────────────────────────────────────────────
$badge_map = [
  'menunggu' => ['label' => 'Menunggu', 'class' => 'warning'],
  'dikonfirmasi' => ['label' => 'Dikonfirmasi', 'class' => 'success'],
  'dibatalkan' => ['label' => 'Dibatalkan', 'class' => 'danger'],
  'selesai' => ['label' => 'Selesai', 'class' => 'info'],
];

// Fungsi bantu: buat URL query string dengan override parameter tertentu
function build_url(array $overrides = []): string
{
  $params = array_merge($_GET, $overrides);
  return '?' . http_build_query($params);
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Manajemen Reservasi | Admin Teman Singgah</title>
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
      box-shadow: 0 0 0 4px rgba(139, 37, 0, .08);
    }

    .table-search-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1rem;
      color: var(--color-text-hint);
      pointer-events: none;
    }

    .table-search-input {
      width: 100%;
      height: 100%;
      border: none;
      outline: none;
      background: transparent;
      font-family: var(--font-family);
      font-size: var(--text-sm);
      color: var(--color-text-primary);
      padding: 0 16px 0 40px;
    }

    .table-search-input::placeholder {
      color: var(--color-text-hint);
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

    /* Modal detail */
    .modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .45);
      z-index: 999;
      align-items: center;
      justify-content: center;
    }

    .modal-overlay.open {
      display: flex;
    }

    .modal-box {
      background: var(--color-bg-card);
      border-radius: 16px;
      padding: 32px;
      width: 100%;
      max-width: 480px;
      box-shadow: 0 8px 40px rgba(0, 0, 0, .18);
    }

    .modal-title {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 20px;
    }

    .modal-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }

    .modal-item {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .modal-label {
      font-size: 12px;
      color: var(--color-text-hint);
    }

    .modal-value {
      font-size: 14px;
      font-weight: 500;
      color: var(--color-text-primary);
    }

    .modal-footer {
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      margin-top: 24px;
    }

    .modal-close {
      padding: 8px 20px;
      border-radius: 8px;
      border: 1.5px solid var(--color-border);
      background: transparent;
      cursor: pointer;
      font-size: 14px;
      color: var(--color-text-secondary);
    }

    .modal-close:hover {
      background: var(--color-bg-hover);
    }

    /* Paginasi */
    .pagination {
      display: flex;
      align-items: center;
      gap: 6px;
    }

    .page-btn {
      width: 34px;
      height: 34px;
      border-radius: 8px;
      border: 1.5px solid var(--color-border);
      background: transparent;
      cursor: pointer;
      font-size: 13px;
      color: var(--color-text-secondary);
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .page-btn:hover {
      background: var(--color-bg-hover);
    }

    .page-btn.active {
      background: var(--color-primary);
      color: #fff;
      border-color: var(--color-primary);
    }

    .page-btn:disabled {
      opacity: .4;
      cursor: not-allowed;
    }
  </style>
</head>

<body>
  <div class="admin-layout">

    <!-- ── Sidebar ─────────────────────────────────────────────────────────── -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <div class="logo-section">
          <img src="../../assets/logo/logo_temansinggah.svg" alt="Logo" class="logo-icon" />
          <img src="../../assets/logo/label_temansinggah.svg" alt="Teman Singgah" class="logo-name" />
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title">Halaman Utama</div>
          <a href="dashboard.php" class="nav-item"><i class="ph-bold ph-squares-four"></i>Dashboard</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Manajemen</div>
          <a href="users.php" class="nav-item"><i class="ph-bold ph-users"></i>Pengguna</a>
          <a href="listings.php" class="nav-item"><i class="ph-bold ph-house"></i>Properti</a>
          <a href="reservations.php" class="nav-item active"><i class="ph-bold ph-calendar-check"></i>Reservasi</a>
          <a href="transactions.php" class="nav-item"><i class="ph-bold ph-currency-circle-dollar"></i>Transaksi</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/promos.php"><i class="ph-bold ph-tag"></i>Promo &
            Deals</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Moderasi</div>
          <a href="reviews.php" class="nav-item"><i class="ph-bold ph-star"></i>Ulasan</a>
          <a href="reports.php" class="nav-item"><i class="ph-bold ph-flag"></i>Laporan</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Keuangan</div>
          <a href="payouts.php" class="nav-item"><i class="ph-bold ph-money"></i>Pembayaran</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Sistem</div>
          <a href="settings.php" class="nav-item"><i class="ph-bold ph-gear"></i>Pengaturan</a>
          <a href="logs.php" class="nav-item"><i class="ph-bold ph-notepad"></i>Aktivitas</a>
        </div>
      </nav>
    </aside>

    <!-- ── Main ────────────────────────────────────────────────────────────── -->
    <div class="main-container">
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">Manajemen Reservasi</h1>
        </div>
        <div class="topbar-right">
          <span class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
          <div class="user-avatar">
            <?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?>
          </div>
        </div>
      </header>

      <main class="content-area">

        <!-- ── Toolbar pencarian ─────────────────────────────────────────────── -->
        <form method="GET" action="" id="filterForm">
          <div class="table-toolbar">
            <div class="search-row">
              <div class="table-search-wrap">
                <i class="ph-bold ph-magnifying-glass table-search-icon"></i>
                <input type="search" name="q" id="adminSearch" class="table-search-input"
                  placeholder="Cari ID, tamu, atau properti..." value="<?= htmlspecialchars($search) ?>"
                  autocomplete="off" />
              </div>
            </div>
          </div>

          <!-- ── Filter status ──────────────────────────────────────────────── -->
          <div class="filter-container">
            <div class="filter-group" id="filterGroup">
              <a href="<?= build_url(['status' => '', 'page' => 1]) ?>"
                class="filter-item <?= $status_f === '' ? 'active' : '' ?>">Semua</a>
              <a href="<?= build_url(['status' => 'menunggu', 'page' => 1]) ?>"
                class="filter-item <?= $status_f === 'menunggu' ? 'active' : '' ?>">Menunggu</a>
              <a href="<?= build_url(['status' => 'dikonfirmasi', 'page' => 1]) ?>"
                class="filter-item <?= $status_f === 'dikonfirmasi' ? 'active' : '' ?>">Dikonfirmasi</a>
              <a href="<?= build_url(['status' => 'dibatalkan', 'page' => 1]) ?>"
                class="filter-item <?= $status_f === 'dibatalkan' ? 'active' : '' ?>">Dibatalkan</a>
              <a href="<?= build_url(['status' => 'selesai', 'page' => 1]) ?>"
                class="filter-item <?= $status_f === 'selesai' ? 'active' : '' ?>">Selesai</a>
            </div>

            <div class="sort-dropdown">
              <select name="sort" class="sort-button" onchange="document.getElementById('filterForm').submit()"
                style="appearance:none;padding:8px 36px 8px 14px;border-radius:8px;border:1.5px solid var(--color-border);background:var(--color-bg-card);font-size:13px;cursor:pointer;">
                <option value="checkin_asc" <?= $sort === 'checkin_asc' ? 'selected' : '' ?>>Urutkan: Check-in ↑</option>
                <option value="checkin_desc" <?= $sort === 'checkin_desc' ? 'selected' : '' ?>>Urutkan: Check-in ↓</option>
                <option value="checkout_asc" <?= $sort === 'checkout_asc' ? 'selected' : '' ?>>Urutkan: Check-out ↑
                </option>
                <option value="checkout_desc" <?= $sort === 'checkout_desc' ? 'selected' : '' ?>>Urutkan: Check-out ↓
                </option>
                <option value="harga_desc" <?= $sort === 'harga_desc' ? 'selected' : '' ?>>Harga Tertinggi</option>
                <option value="harga_asc" <?= $sort === 'harga_asc' ? 'selected' : '' ?>>Harga Terendah</option>
                <option value="terbaru" <?= $sort === 'terbaru' ? 'selected' : '' ?>>Terbaru</option>
              </select>
            </div>
          </div>
          <input type="hidden" name="page" value="1" />
        </form>

        <!-- ── Tabel ──────────────────────────────────────────────────────── -->
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
                <?php
                $no = $offset + 1;
                $booking_list = []; // simpan untuk modal
                while ($row = $rows->fetch_assoc()):
                  $booking_list[] = $row;
                  $badge = $badge_map[$row['status']] ?? ['label' => ucfirst($row['status']), 'class' => 'info'];
                  $id_rsv = '#RSV-' . date('Y') . '-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                  $initials = strtoupper(substr($row['nama_tamu'], 0, 2));
                  ?>
                  <tr>
                    <td class="col-num"><?= $no++ ?></td>
                    <td><span class="id-code"><?= htmlspecialchars($id_rsv) ?></span></td>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar"><?= $initials ?></div>
                        <h3 class="table-name"><?= htmlspecialchars($row['nama_tamu']) ?></h3>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($row['nama_listing']) ?></td>
                    <td><?= date('d M Y', strtotime($row['checkin'])) ?></td>
                    <td><?= date('d M Y', strtotime($row['checkout'])) ?></td>
                    <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                    <td>
                      <span class="table-badge <?= $badge['class'] ?>">
                        <span class="badge-dot"></span>
                        <?= $badge['label'] ?>
                      </span>
                    </td>
                    <td>
                      <button class="action-button info" aria-label="Lihat detail" title="Lihat detail"
                        onclick="bukaModal(<?= $row['id'] ?>)">
                        <i class="ph-bold ph-eye"></i>
                      </button>
                    </td>
                  </tr>
                <?php endwhile; ?>

                <?php if ($no === $offset + 1): ?>
                  <tr>
                    <td colspan="9" style="text-align:center;padding:40px;color:var(--color-text-hint);">
                      Tidak ada data reservasi.
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>

            <!-- Paginasi -->
            <div class="table-pagination">
              <span class="pagination-info">
                Menampilkan <?= min($offset + 1, $total_data) ?>–<?= min($offset + $per_page, $total_data) ?>
                dari <?= $total_data ?> reservasi
              </span>
              <div class="pagination pagination-controls">
                <a href="<?= build_url(['page' => max(1, $page - 1)]) ?>">
                  <button class="page-btn" <?= $page <= 1 ? 'disabled' : '' ?> aria-label="Sebelumnya">
                    <i class="ph-bold ph-caret-left"></i>
                  </button>
                </a>
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                  <a href="<?= build_url(['page' => $p]) ?>">
                    <button class="page-btn <?= $p === $page ? 'active' : '' ?>"><?= $p ?></button>
                  </a>
                <?php endfor; ?>
                <a href="<?= build_url(['page' => min($total_pages, $page + 1)]) ?>">
                  <button class="page-btn" <?= $page >= $total_pages ? 'disabled' : '' ?> aria-label="Berikutnya">
                    <i class="ph-bold ph-caret-right"></i>
                  </button>
                </a>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <!-- ── Modal Detail Reservasi ─────────────────────────────────────────────── -->
  <div class="modal-overlay" id="detailModal">
    <div class="modal-box">
      <div class="modal-title">Detail Reservasi</div>
      <div class="modal-grid" id="modalContent"></div>
      <div class="modal-footer">
        <button class="modal-close" onclick="tutupModal()">Tutup</button>
      </div>
    </div>
  </div>

  <!-- Data booking untuk modal (JSON) -->
  <script>
    const bookings = <?= json_encode(array_values($booking_list)) ?>;

    function bukaModal(id) {
      const b = bookings.find(x => x.id == id);
      if (!b) return;

      const idRsv = '#RSV-' + new Date().getFullYear() + '-' + String(b.id).padStart(4, '0');
      const badge = { menunggu: 'Menunggu', dikonfirmasi: 'Dikonfirmasi', dibatalkan: 'Dibatalkan', selesai: 'Selesai' };
      const total = 'Rp ' + Number(b.total_harga).toLocaleString('id-ID');

      document.getElementById('modalContent').innerHTML = `
      <div class="modal-item">
        <span class="modal-label">ID Reservasi</span>
        <span class="modal-value">${idRsv}</span>
      </div>
      <div class="modal-item">
        <span class="modal-label">Status</span>
        <span class="modal-value">${badge[b.status] || b.status}</span>
      </div>
      <div class="modal-item">
        <span class="modal-label">Nama Tamu</span>
        <span class="modal-value">${b.nama_tamu}</span>
      </div>
      <div class="modal-item">
        <span class="modal-label">Email</span>
        <span class="modal-value">${b.email_tamu ?? '-'}</span>
      </div>
      <div class="modal-item">
        <span class="modal-label">Properti</span>
        <span class="modal-value">${b.nama_listing}</span>
      </div>
      <div class="modal-item">
        <span class="modal-label">Jumlah Tamu</span>
        <span class="modal-value">${b.jumlah_tamu} orang</span>
      </div>
      <div class="modal-item">
        <span class="modal-label">Check-in</span>
        <span class="modal-value">${b.checkin}</span>
      </div>
      <div class="modal-item">
        <span class="modal-label">Check-out</span>
        <span class="modal-value">${b.checkout}</span>
      </div>
      <div class="modal-item" style="grid-column:span 2">
        <span class="modal-label">Total Harga</span>
        <span class="modal-value" style="font-size:18px">${total}</span>
      </div>
    `;
      document.getElementById('detailModal').classList.add('open');
    }

    function tutupModal() {
      document.getElementById('detailModal').classList.remove('open');
    }

    // Tutup modal klik di luar
    document.getElementById('detailModal').addEventListener('click', function (e) {
      if (e.target === this) tutupModal();
    });

    // Submit pencarian dengan enter / auto
    document.getElementById('adminSearch').addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('filterForm').submit();
      }
    });
  </script>

  <script src="../dashboard.js"></script>
</body>

</html>