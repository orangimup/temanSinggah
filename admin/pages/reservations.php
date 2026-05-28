<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

$conn->query("
    UPDATE bookings b
    JOIN listings l ON l.id = b.listing_id
    SET b.status = 'dikonfirmasi'
    WHERE b.status = 'menunggu'
      AND l.tipe_booking = 'instan'
");

$search = trim($_GET['q'] ?? '');
$filter_f = trim($_GET['filter'] ?? '');
$sort = trim($_GET['sort'] ?? 'terbaru');

$per_page = 10;
$page = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $per_page;

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

switch ($filter_f) {
  case 'berlangsung':
    $conditions[] = "b.checkin <= CURDATE() AND b.checkout >= CURDATE() AND b.status = 'dikonfirmasi'";
    break;
  case 'mendatang':
    $conditions[] = "b.checkin > CURDATE()";
    break;
  case 'dikonfirmasi':
    $conditions[] = "b.status = 'dikonfirmasi'";
    break;
  case 'dibatalkan':
    $conditions[] = "b.status = 'dibatalkan'";
    break;
  case 'selesai':
    $conditions[] = "b.status = 'selesai'";
    break;
}

$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$order_map = [
  'terbaru' => 'b.dibuat_pada DESC',
  'checkin_terbaru' => 'b.checkin DESC',
  'checkin_terlama' => 'b.checkin ASC',
  'harga_desc' => 'b.total_harga DESC',
  'harga_asc' => 'b.total_harga ASC',
];
$order = $order_map[$sort] ?? 'b.dibuat_pada DESC';

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

$data_sql = "
    SELECT
        b.id,
        b.checkin,
        b.checkout,
        b.jumlah_tamu,
        b.total_harga,
        b.status,
        b.dibuat_pada,
        b.metode_bayar,
        b.tipe_bayar,
        b.dp_amount,
        b.kode_promo,
        b.sisa_bayar,
        u.nama  AS nama_tamu,
        u.email AS email_tamu,
        u.photo AS photo_tamu,
        l.judul AS nama_listing,
        r.nama  AS nama_kamar
    FROM bookings b
    JOIN users    u ON u.id = b.user_id
    JOIN listings l ON l.id = b.listing_id
    LEFT JOIN listing_rooms r ON r.id = b.room_id
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

$badge_map = [
  'menunggu' => ['label' => 'Menunggu', 'class' => 'warning'],
  'dikonfirmasi' => ['label' => 'Dikonfirmasi', 'class' => 'success'],
  'dibatalkan' => ['label' => 'Dibatalkan', 'class' => 'danger'],
  'selesai' => ['label' => 'Selesai', 'class' => 'info'],
];

$sort_labels = [
  'terbaru' => 'Terbaru',
  'checkin_terbaru' => 'Check-in Terbaru',
  'checkin_terlama' => 'Check-in Terlama',
  'harga_desc' => 'Harga Tertinggi',
  'harga_asc' => 'Harga Terendah',
];

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

    .sort-dropdown {
      position: relative;
    }

    .sort-menu {
      display: none;
      position: absolute;
      right: 0;
      top: calc(100% + 6px);
      background: #fff;
      border: 1px solid var(--color-border, #e5e7eb);
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, .10);
      z-index: 200;
      min-width: 210px;
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
      transition: background .15s;
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

    /* Badge Berlangsung */
    .table-badge.primary {
      background: #eff6ff;
      color: #1d4ed8;
    }

    .table-badge.primary .badge-dot {
      background: #1d4ed8;
    }
  </style>
</head>

<body>
  <div class="admin-layout">

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

          <div class="filter-container">
            <div class="filter-group" id="filterGroup">
              <a href="<?= build_url(['filter' => '', 'page' => 1]) ?>"
                class="filter-item <?= $filter_f === '' ? 'active' : '' ?>">Semua</a>
              <a href="<?= build_url(['filter' => 'berlangsung', 'page' => 1]) ?>"
                class="filter-item <?= $filter_f === 'berlangsung' ? 'active' : '' ?>">Berlangsung</a>
              <a href="<?= build_url(['filter' => 'mendatang', 'page' => 1]) ?>"
                class="filter-item <?= $filter_f === 'mendatang' ? 'active' : '' ?>">Mendatang</a>
              <a href="<?= build_url(['filter' => 'dikonfirmasi', 'page' => 1]) ?>"
                class="filter-item <?= $filter_f === 'dikonfirmasi' ? 'active' : '' ?>">Dikonfirmasi</a>
              <a href="<?= build_url(['filter' => 'dibatalkan', 'page' => 1]) ?>"
                class="filter-item <?= $filter_f === 'dibatalkan' ? 'active' : '' ?>">Dibatalkan</a>
              <a href="<?= build_url(['filter' => 'selesai', 'page' => 1]) ?>"
                class="filter-item <?= $filter_f === 'selesai' ? 'active' : '' ?>">Selesai</a>
            </div>

            <div class="sort-dropdown">
              <button class="sort-button" id="sortToggleBtn" type="button">
                <i class="ph-bold ph-faders-horizontal"></i>
                <span id="sortLabel">Urutkan: <?= htmlspecialchars($sort_labels[$sort] ?? 'Terbaru') ?></span>
                <i class="ph-bold ph-caret-down"></i>
              </button>

              <div class="sort-menu" id="sortMenu">
                <div class="sort-menu-item <?= $sort === 'terbaru' ? 'active' : '' ?>" onclick="selectSort('terbaru')">
                  <i class="ph-bold ph-clock-counter-clockwise"></i> Terbaru Dibuat
                </div>
                <div class="sort-menu-divider"></div>
                <div class="sort-menu-item <?= $sort === 'checkin_terbaru' ? 'active' : '' ?>"
                  onclick="selectSort('checkin_terbaru')">
                  <i class="ph-bold ph-calendar-blank"></i> Check-in Terbaru
                </div>
                <div class="sort-menu-item <?= $sort === 'checkin_terlama' ? 'active' : '' ?>"
                  onclick="selectSort('checkin_terlama')">
                  <i class="ph-bold ph-calendar-blank"></i> Check-in Terlama
                </div>
                <div class="sort-menu-divider"></div>
                <div class="sort-menu-item <?= $sort === 'harga_desc' ? 'active' : '' ?>"
                  onclick="selectSort('harga_desc')">
                  <i class="ph-bold ph-trend-up"></i> Harga Tertinggi
                </div>
                <div class="sort-menu-item <?= $sort === 'harga_asc' ? 'active' : '' ?>"
                  onclick="selectSort('harga_asc')">
                  <i class="ph-bold ph-trend-down"></i> Harga Terendah
                </div>
              </div>

              <input type="hidden" name="sort" id="sortInput" value="<?= htmlspecialchars($sort) ?>" />
            </div>
          </div>

          <input type="hidden" name="filter" id="filterInput" value="<?= htmlspecialchars($filter_f) ?>" />
          <input type="hidden" name="page" value="1" />
        </form>

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
                $booking_list = [];
                $today = date('Y-m-d');
                while ($row = $rows->fetch_assoc()):
                  $booking_list[] = $row;

                  if (
                    $row['status'] === 'dikonfirmasi' &&
                    $row['checkin'] <= $today &&
                    $row['checkout'] >= $today
                  ) {
                    $badge = ['label' => 'Berlangsung', 'class' => 'primary'];
                  } else {
                    $badge = $badge_map[$row['status']] ?? ['label' => ucfirst($row['status']), 'class' => 'info'];
                  }

                  $id_rsv = '#RSV-' . date('Y') . '-' . str_pad($row['id'], 4, '0', STR_PAD_LEFT);
                  $initials = strtoupper(substr($row['nama_tamu'], 0, 2));
                  $photo_tamu = !empty($row['photo_tamu']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $row['photo_tamu'])
                    ? '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($row['photo_tamu'])
                    : null;
                  ?>
                  <tr>
                    <td class="col-num"><?= $no++ ?></td>
                    <td><span class="id-code"><?= htmlspecialchars($id_rsv) ?></span></td>
                    <td>
                      <div class="table-cell">
                        <?php if ($photo_tamu): ?>
                          <div class="table-avatar" style="padding:0;overflow:hidden;">
                            <img src="<?= $photo_tamu ?>" alt="Foto"
                              style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
                          </div>
                        <?php else: ?>
                          <div class="table-avatar"><?= $initials ?></div>
                        <?php endif; ?>
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

  <div class="modal-overlay" id="detailModal">
    <div class="modal-box">
      <div class="modal-title">Detail Reservasi</div>
      <div class="modal-grid" id="modalContent"></div>
      <div class="modal-footer">
        <button class="modal-close" onclick="tutupModal()">Tutup</button>
      </div>
    </div>
  </div>

  <script>
    const bookings = <?= json_encode(array_values($booking_list)) ?>;

    function getStatusLabel(b) {
      const today = new Date().toISOString().split('T')[0];
      if (b.status === 'dikonfirmasi' && b.checkin <= today && b.checkout >= today) {
        return 'Berlangsung';
      }
      const map = {
        menunggu: 'Menunggu',
        dikonfirmasi: 'Dikonfirmasi',
        dibatalkan: 'Dibatalkan',
        selesai: 'Selesai',
      };
      return map[b.status] || b.status;
    }

    function bukaModal(id) {
      const b = bookings.find(x => x.id == id);
      if (!b) return;

      const idRsv = '#RSV-' + new Date().getFullYear() + '-' + String(b.id).padStart(4, '0');
      const status = getStatusLabel(b);
      const total = 'Rp ' + Number(b.total_harga).toLocaleString('id-ID');

      document.getElementById('modalContent').innerHTML = `
        <div class="modal-item">
          <span class="modal-label">ID Reservasi</span>
          <span class="modal-value">${idRsv}</span>
        </div>
        <div class="modal-item">
          <span class="modal-label">Status</span>
          <span class="modal-value">${status}</span>
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
        <div class="modal-item">
          <span class="modal-label">Metode Bayar</span>
          <span class="modal-value">${b.metode_bayar ?? '-'}</span>
        </div>
        <div class="modal-item">
          <span class="modal-label">Tipe Bayar</span>
          <span class="modal-value">${b.tipe_bayar === 'dp' ? 'DP' : 'Lunas'}</span>
        </div>
        <div class="modal-item">
          <span class="modal-label">DP Dibayar</span>
          <span class="modal-value">${b.tipe_bayar === 'dp' ? 'Rp ' + Number(b.dp_amount).toLocaleString('id-ID') : '-'}</span>
        </div>
        <div class="modal-item">
          <span class="modal-label">Sisa Bayar</span>
          <span class="modal-value">${b.tipe_bayar === 'dp' ? 'Rp ' + Number(b.sisa_bayar).toLocaleString('id-ID') : '-'}</span>
        </div>
        <div class="modal-item">
          <span class="modal-label">Kode Promo</span>
          <span class="modal-value">${b.kode_promo || '-'}</span>
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

    document.getElementById('detailModal').addEventListener('click', function (e) {
      if (e.target === this) tutupModal();
    });

    document.getElementById('adminSearch').addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('filterForm').submit();
      }
    });

    const sortToggleBtn = document.getElementById('sortToggleBtn');
    const sortMenu = document.getElementById('sortMenu');

    sortToggleBtn.addEventListener('click', e => {
      e.stopPropagation();
      sortMenu.classList.toggle('open');
    });

    document.addEventListener('click', e => {
      if (!sortMenu.contains(e.target) && e.target !== sortToggleBtn)
        sortMenu.classList.remove('open');
    });

    function selectSort(value) {
      document.getElementById('sortInput').value = value;
      document.querySelector('input[name="page"]').value = 1;
      document.getElementById('filterForm').submit();
    }
  </script>

  <script src="../dashboard.js"></script>
</body>

</html>