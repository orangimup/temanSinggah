<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');

  $payout_id = (int) ($_POST['payout_id'] ?? 0);
  $action = $_POST['action'] ?? '';

  if (!$payout_id || !in_array($action, ['proses', 'retry', 'cancel', 'complete'], true)) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
    exit;
  }

  $row = $koneksi->query("SELECT status FROM payouts WHERE id = $payout_id LIMIT 1")->fetch_assoc();

  if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Payout tidak ditemukan.']);
    exit;
  }

  $valid = [
    'proses' => ['Dijadwalkan'],
    'retry' => ['Failed'],
    'cancel' => ['Processing'],
    'complete' => ['Processing'],
  ];

  if (!in_array($row['status'], $valid[$action])) {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid untuk status saat ini.']);
    exit;
  }

  $now = date('Y-m-d H:i:s');
  $koneksi->begin_transaction();
  try {
    if ($action === 'proses') {
      $s = $koneksi->prepare("UPDATE payouts SET status='Processing', processed_at=?, updated_at=? WHERE id=?");
      $s->bind_param('ssi', $now, $now, $payout_id);
    } elseif ($action === 'retry') {
      $s = $koneksi->prepare("UPDATE payouts SET status='Dijadwalkan', failure_reason=NULL, processed_at=NULL, updated_at=? WHERE id=?");
      $s->bind_param('si', $now, $payout_id);
    } elseif ($action === 'cancel') {
      $reason = $koneksi->real_escape_string($_POST['reason'] ?? 'Dibatalkan admin');
      $s = $koneksi->prepare("UPDATE payouts SET status='Failed', failure_reason=?, updated_at=? WHERE id=?");
      $s->bind_param('ssi', $reason, $now, $payout_id);
    } elseif ($action === 'complete') {
      $s = $koneksi->prepare("UPDATE payouts SET status='Completed', updated_at=? WHERE id=?");
      $s->bind_param('si', $now, $payout_id);
    }
    $s->execute();
    $s->close();
    $koneksi->commit();
    echo json_encode(['success' => true]);
    exit;
  } catch (Exception $e) {
    $koneksi->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
  }
}

$sql = "
  SELECT
    p.id, p.status, p.payout_amount,
    p.bank_name, p.account_number,
    p.scheduled_date, p.processed_at,
    p.failure_reason,
    u.nama AS host_nama
  FROM payouts p
  JOIN users u ON u.id = p.host_id
  ORDER BY p.scheduled_date DESC
";
$result = $koneksi->query($sql);
$payouts = [];
while ($r = $result->fetch_assoc())
  $payouts[] = $r;

function fmt_rupiah(int $n): string
{
  return 'Rp' . number_format($n, 0, ',', '.');
}
function fmt_tgl(?string $d): string
{
  if (!$d)
    return '—';
  $m = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  $ts = strtotime($d);
  return date('j', $ts) . ' ' . $m[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}
function fmt_dt(?string $d): string
{
  if (!$d)
    return '—';
  $m = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
  $ts = strtotime($d);
  return date('j', $ts) . ' ' . $m[(int) date('n', $ts)] . ' ' . date('Y', $ts) . ', ' . date('H:i', $ts);
}
function badge(string $s): string
{
  $map = [
    'Dijadwalkan' => ['warning', 'Dijadwalkan'],
    'Processing' => ['info', 'Diproses'],
    'Completed' => ['success', 'Selesai'],
    'Failed' => ['error', 'Gagal'],
  ];
  [$cls, $lbl] = $map[$s] ?? ['warning', $s];
  return "<span class=\"table-badge $cls\"><span class=\"badge-dot\"></span>$lbl</span>";
}
function initials(string $nama): string
{
  $p = explode(' ', trim($nama));
  $i = strtoupper(mb_substr($p[0], 0, 1));
  if (count($p) > 1)
    $i .= strtoupper(mb_substr($p[1], 0, 1));
  return $i;
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pembayaran | Admin Teman Singgah</title>
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
      font-weight: var(--font-medium);
      color: var(--color-text-primary);
      padding: 0 16px 0 40px;
      box-sizing: border-box;
    }

    .table-search-input::placeholder {
      color: var(--color-text-hint);
      font-weight: var(--font-regular);
    }

    .filter-container {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
      flex-wrap: wrap;
      margin-bottom: 1rem;
    }

    .filter-group {
      display: flex;
      gap: .5rem;
      flex-wrap: wrap;
    }

    .filter-item {
      padding: 8px 18px;
      border: 1.5px solid var(--color-border);
      border-radius: var(--radius-full);
      background: transparent;
      color: var(--color-text-secondary);
      font-weight: var(--font-semibold);
      font-size: var(--text-sm);
      cursor: pointer;
      transition: all var(--transition-base);
      font-family: var(--font-family);
    }

    .filter-item:hover {
      border-color: var(--color-primary);
      color: var(--color-primary);
    }

    .filter-item.active {
      background: var(--color-primary);
      border-color: var(--color-primary);
      color: #fff;
    }

    .sort-dropdown {
      position: relative;
    }

    .sort-button {
      display: flex;
      align-items: center;
      gap: 8px;
      padding: 8px 16px;
      border: 1.5px solid var(--color-border);
      border-radius: var(--radius-full);
      background: var(--color-bg-card);
      color: var(--color-text-secondary);
      font-family: var(--font-family);
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      cursor: pointer;
      transition: all var(--transition-fast);
      white-space: nowrap;
    }

    .sort-button:hover {
      border-color: var(--color-primary);
      color: var(--color-primary);
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

    .col-num {
      text-align: center;
      width: 55px;
    }

    tbody .col-num {
      color: var(--color-text-secondary);
      font-size: var(--text-sm);
    }

    .table-pagination {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 1.25rem;
      border-top: 1px solid var(--color-border-subtle);
      flex-wrap: wrap;
      gap: .5rem;
    }

    .pagination-info {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
    }

    .pagination-controls {
      display: flex;
      gap: .35rem;
    }

    .page-btn {
      min-width: 34px;
      height: 34px;
      border: 1.5px solid var(--color-border);
      border-radius: 8px;
      background: var(--color-bg-card);
      color: var(--color-text-secondary);
      font-family: var(--font-family);
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      transition: all var(--transition-fast);
      padding: 0 6px;
    }

    .page-btn:hover:not(:disabled) {
      border-color: var(--color-primary);
      color: var(--color-primary);
    }

    .page-btn.active {
      background: var(--color-primary);
      border-color: var(--color-primary);
      color: #fff;
    }

    .page-btn:disabled {
      opacity: .35;
      cursor: not-allowed;
    }

    .empty-row td {
      text-align: center;
      padding: 2.5rem;
      color: var(--color-text-secondary);
      font-size: var(--text-sm);
    }

    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .45);
      z-index: 9000;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity .22s ease;
    }

    .modal-overlay.open {
      opacity: 1;
      pointer-events: all;
    }

    .modal-box {
      background: var(--color-bg-card, #fff);
      border-radius: 16px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, .18);
      width: 100%;
      max-width: 460px;
      margin: 16px;
      transform: translateY(16px) scale(.97);
      transition: transform .22s ease;
      overflow: hidden;
    }

    .modal-overlay.open .modal-box {
      transform: translateY(0) scale(1);
    }

    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 24px 16px;
      border-bottom: 1px solid var(--color-border, #e5e7eb);
    }

    .modal-header-left {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .modal-icon-wrap {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
      flex-shrink: 0;
    }

    .modal-icon-wrap.proses {
      background: rgba(139, 37, 0, .10);
      color: var(--color-primary, #8b2500);
    }

    .modal-icon-wrap.complete {
      background: rgba(139, 37, 0, .10);
      color: var(--color-primary, #8b2500);
    }

    .modal-icon-wrap.cancel {
      background: rgba(220, 38, 38, .12);
      color: #dc2626;
    }

    .modal-icon-wrap.retry {
      background: rgba(234, 179, 8, .12);
      color: #ca8a04;
    }

    .modal-icon-wrap.detail {
      background: rgba(139, 37, 0, .10);
      color: var(--color-primary, #8b2500);
    }

    .modal-title {
      font-size: 1rem;
      font-weight: 600;
      color: var(--color-text-primary, #111);
      margin: 0;
    }

    .modal-subtitle {
      font-size: .78rem;
      color: var(--color-text-hint, #9ca3af);
      margin: 2px 0 0;
    }

    .modal-close {
      width: 32px;
      height: 32px;
      border: none;
      background: var(--color-bg-hover, #f3f4f6);
      border-radius: 8px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 16px;
      color: var(--color-text-secondary, #6b7280);
      transition: background .15s;
    }

    .modal-close:hover {
      background: var(--color-border, #e5e7eb);
    }

    .modal-body {
      padding: 20px 24px;
    }

    .modal-info-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 16px;
    }

    .modal-info-item {
      display: flex;
      flex-direction: column;
      gap: 3px;
    }

    .modal-info-label {
      font-size: .72rem;
      font-weight: 600;
      color: var(--color-text-hint, #9ca3af);
      text-transform: uppercase;
      letter-spacing: .05em;
    }

    .modal-info-value {
      font-size: .875rem;
      font-weight: 500;
      color: var(--color-text-primary, #111);
    }

    .modal-info-value.amount {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--color-primary, #8b2500);
    }

    .modal-warning {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      border-radius: 10px;
      padding: 12px 14px;
      margin-top: 14px;
      background: rgba(239, 68, 68, .07);
      border: 1px solid rgba(239, 68, 68, .18);
    }

    .modal-warning.green {
      background: rgba(139, 37, 0, .05);
      border-color: rgba(139, 37, 0, .15);
    }

    .modal-warning.green i {
      color: var(--color-primary, #8b2500);
    }

    .modal-warning.yellow {
      background: rgba(234, 179, 8, .07);
      border-color: rgba(234, 179, 8, .18);
    }

    .modal-warning i {
      font-size: 16px;
      flex-shrink: 0;
      margin-top: 1px;
      color: #ef4444;
    }

    .modal-warning.green i {
      color: #16a34a;
    }

    .modal-warning.yellow i {
      color: #ca8a04;
    }

    .modal-warning p {
      margin: 0;
      font-size: .82rem;
      color: var(--color-text-secondary, #6b7280);
      line-height: 1.5;
    }

    .modal-footer {
      padding: 16px 24px 20px;
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      border-top: 1px solid var(--color-border, #e5e7eb);
    }

    .btn-modal {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 9px 20px;
      border-radius: 10px;
      font-family: var(--font-family, 'Inter', sans-serif);
      font-size: .875rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      transition: all .15s;
    }

    .btn-modal.cancel-btn {
      background: var(--color-bg-hover, #f3f4f6);
      color: var(--color-text-secondary, #6b7280);
      border: 1.5px solid var(--color-border, #e5e7eb);
    }

    .btn-modal.cancel-btn:hover {
      background: var(--color-border, #e5e7eb);
    }

    .btn-modal.green-btn {
      background: var(--color-primary, #8b2500);
      color: #fff;
    }

    .btn-modal.green-btn:hover {
      background: #6b1a00;
    }

    .btn-modal.red-btn {
      background: #dc2626;
      color: #fff;
    }

    .btn-modal.red-btn:hover {
      background: #b91c1c;
    }

    .btn-modal.yellow-btn {
      background: #ca8a04;
      color: #fff;
    }

    .btn-modal.yellow-btn:hover {
      background: #a16207;
    }

    .btn-modal.primary-btn {
      background: var(--color-primary, #8b2500);
      color: #fff;
    }

    .btn-modal.primary-btn:hover {
      background: #6b1a00;
    }
  </style>
</head>

<body>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <a href="dashboard.php" class="logo-link"></a>
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
          <a href="reservations.php" class="nav-item"><i class="ph-bold ph-calendar-check"></i>Reservasi</a>
          <a href="transactions.php" class="nav-item"><i class="ph-bold ph-currency-circle-dollar"></i>Transaksi</a>
          <a href="/teman_singgah/admin/pages/promos.php" class="nav-item"><i class="ph-bold ph-tag"></i>Promo &amp;
            Deals</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Moderasi</div>
          <a href="reviews.php" class="nav-item"><i class="ph-bold ph-star"></i>Ulasan</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Keuangan</div>
          <a href="payouts.php" class="nav-item active"><i class="ph-bold ph-money"></i>Pembayaran</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Sistem</div>
          <a href="settings.php" class="nav-item"><i class="ph-bold ph-gear"></i>Pengaturan</a>
        </div>
      </nav>
    </aside>

    <div class="main-container">
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">Pencairan Dana Host</h1>
        </div>
        <div class="topbar-right">
          <span class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
          <div class="user-avatar"><?= strtoupper(mb_substr($_SESSION['nama'] ?? 'A', 0, 1)) ?></div>
        </div>
      </header>

      <main class="content-area">

        <div class="table-toolbar">
          <div class="search-row">
            <div class="table-search-wrap">
              <i class="ph-bold ph-magnifying-glass table-search-icon"></i>
              <input type="search" id="payoutSearch" class="table-search-input"
                placeholder="Cari nama host, bank, nomor rekening..." />
            </div>
          </div>
        </div>

        <div class="filter-container">
          <div class="filter-group" id="filterGroup">
            <button class="filter-item active" data-filter="all">Semua</button>
            <button class="filter-item" data-filter="status:Dijadwalkan">Dijadwalkan</button>
            <button class="filter-item" data-filter="status:Processing">Diproses</button>
            <button class="filter-item" data-filter="status:Completed">Selesai</button>
            <button class="filter-item" data-filter="status:Failed">Gagal</button>
          </div>
          <div class="sort-dropdown">
            <button class="sort-button" id="sortToggleBtn">
              <i class="ph-bold ph-faders-horizontal"></i>
              <span id="sortLabel">Urutkan: Jadwal Terbaru</span>
              <i class="ph-bold ph-caret-down"></i>
            </button>
            <div class="sort-menu" id="sortMenu">
              <div class="sort-menu-item active" onclick="selectSort('date_desc','Jadwal Terbaru',this)">
                <i class="ph-bold ph-calendar"></i> Jadwal Terbaru
              </div>
              <div class="sort-menu-item" onclick="selectSort('date_asc','Jadwal Terlama',this)">
                <i class="ph-bold ph-calendar"></i> Jadwal Terlama
              </div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('amount_desc','Jumlah Terbesar',this)">
                <i class="ph-bold ph-sort-descending"></i> Jumlah Terbesar
              </div>
              <div class="sort-menu-item" onclick="selectSort('amount_asc','Jumlah Terkecil',this)">
                <i class="ph-bold ph-sort-ascending"></i> Jumlah Terkecil
              </div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('host_asc','Nama Host A–Z',this)">
                <i class="ph-bold ph-sort-ascending"></i> Nama Host A–Z
              </div>
              <div class="sort-menu-item" onclick="selectSort('host_desc','Nama Host Z–A',this)">
                <i class="ph-bold ph-sort-descending"></i> Nama Host Z–A
              </div>
            </div>
          </div>
        </div>

        <section class="table-section">
          <div class="table-container">
            <table id="payoutTable">
              <thead>
                <tr>
                  <th>No.</th>
                  <th>Nama Host</th>
                  <th>Jumlah Payout</th>
                  <th>Bank Tujuan</th>
                  <th>Jadwal Transfer</th>
                  <th>Status</th>
                  <th>Tanggal Diproses</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody id="payoutTbody">
                <?php foreach ($payouts as $p): ?>
                  <tr data-id="<?= $p['id'] ?>" data-status="<?= htmlspecialchars($p['status']) ?>"
                    data-host="<?= htmlspecialchars(strtolower($p['host_nama'])) ?>"
                    data-host-display="<?= htmlspecialchars($p['host_nama']) ?>"
                    data-bank="<?= htmlspecialchars(strtolower($p['bank_name'] ?? '')) ?>"
                    data-bank-display="<?= htmlspecialchars($p['bank_name'] ?? '') ?>"
                    data-account="<?= htmlspecialchars($p['account_number'] ?? '') ?>"
                    data-amount="<?= (int) $p['payout_amount'] ?>"
                    data-amount-fmt="<?= fmt_rupiah((int) $p['payout_amount']) ?>"
                    data-date="<?= htmlspecialchars($p['scheduled_date']) ?>"
                    data-date-fmt="<?= fmt_tgl($p['scheduled_date']) ?>"
                    data-processed="<?= fmt_dt($p['processed_at']) ?>"
                    data-failure="<?= htmlspecialchars($p['failure_reason'] ?? '') ?>"
                    data-initials="<?= initials($p['host_nama']) ?>">
                    <td class="col-num">—</td>
                    <td>
                      <div class="table-cell">
                        <div class="table-avatar"><?= initials($p['host_nama']) ?></div>
                        <h3 class="table-name"><?= htmlspecialchars($p['host_nama']) ?></h3>
                      </div>
                    </td>
                    <td><?= fmt_rupiah((int) $p['payout_amount']) ?></td>
                    <td>
                      <?php if ($p['bank_name'] && $p['account_number']): ?>
                        <?= htmlspecialchars($p['bank_name']) ?> — <?= htmlspecialchars($p['account_number']) ?>
                      <?php else: ?>
                        <span style="color:var(--color-text-secondary)">Belum diisi</span>
                      <?php endif; ?>
                    </td>
                    <td><?= fmt_tgl($p['scheduled_date']) ?></td>
                    <td><?= badge($p['status']) ?></td>
                    <td><?= fmt_dt($p['processed_at']) ?></td>
                    <td>
                      <div class="action-group">
                        <?php if ($p['status'] === 'Dijadwalkan'): ?>
                          <button class="action-button success btn-proses" data-id="<?= $p['id'] ?>" title="Proses">
                            <i class="ph-bold ph-play-circle"></i>
                          </button>
                        <?php endif; ?>
                        <?php if ($p['status'] === 'Processing'): ?>
                          <button class="action-button success btn-complete" data-id="<?= $p['id'] ?>" title="Selesai">
                            <i class="ph-bold ph-check"></i>
                          </button>
                          <button class="action-button error btn-cancel" data-id="<?= $p['id'] ?>" title="Batalkan">
                            <i class="ph-bold ph-x"></i>
                          </button>
                        <?php endif; ?>
                        <?php if ($p['status'] === 'Failed'): ?>
                          <button class="action-button success btn-retry" data-id="<?= $p['id'] ?>" title="Coba lagi">
                            <i class="ph-bold ph-arrow-clockwise"></i>
                          </button>
                        <?php endif; ?>
                        <?php if ($p['status'] === 'Completed'): ?>
                          <a href="../payout/export_pdf.php?id=<?= $p['id'] ?>" class="action-button success" title="Unduh">
                            <i class="ph-bold ph-download"></i>
                          </a>
                        <?php endif; ?>
                        <button class="action-button info btn-detail" data-id="<?= $p['id'] ?>" title="Lihat">
                          <i class="ph-bold ph-eye"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
            <div class="table-pagination">
              <span class="pagination-info" id="paginationInfo"></span>
              <div class="pagination-controls" id="paginationControls"></div>
            </div>
          </div>
        </section>

      </main>
    </div>
  </div>

  <!-- Modal Proses -->
  <div class="modal-overlay" id="modalProses">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon-wrap proses"><i class="ph-bold ph-play-circle"></i></div>
          <div>
            <p class="modal-title">Proses Pencairan</p>
            <p class="modal-subtitle" id="prosesSubtitle"></p>
          </div>
        </div>
        <button class="modal-close" onclick="closeModal('modalProses')"><i class="ph-bold ph-x"></i></button>
      </div>
      <div class="modal-body">
        <div class="modal-info-grid">
          <div class="modal-info-item"><span class="modal-info-label">Host</span><span class="modal-info-value"
              id="prosesHost">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jumlah</span><span class="modal-info-value amount"
              id="prosesAmount">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Bank</span><span class="modal-info-value"
              id="prosesBank">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jadwal</span><span class="modal-info-value"
              id="prosesDate">—</span></div>
        </div>
        <div class="modal-warning green">
          <i class="ph-bold ph-info"></i>
          <p>Status akan berubah menjadi <strong>Diproses</strong>. Transfer akan segera dilakukan ke rekening host.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-modal cancel-btn" onclick="closeModal('modalProses')">Batal</button>
        <button class="btn-modal green-btn" onclick="submitPayout('proses')"><i class="ph-bold ph-play-circle"></i>
          Proses Sekarang</button>
      </div>
    </div>
  </div>

  <!-- Modal Complete -->
  <div class="modal-overlay" id="modalComplete">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon-wrap complete"><i class="ph-bold ph-check-circle"></i></div>
          <div>
            <p class="modal-title">Tandai Selesai</p>
            <p class="modal-subtitle" id="completeSubtitle"></p>
          </div>
        </div>
        <button class="modal-close" onclick="closeModal('modalComplete')"><i class="ph-bold ph-x"></i></button>
      </div>
      <div class="modal-body">
        <div class="modal-info-grid">
          <div class="modal-info-item"><span class="modal-info-label">Host</span><span class="modal-info-value"
              id="completeHost">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jumlah</span><span class="modal-info-value amount"
              id="completeAmount">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Bank</span><span class="modal-info-value"
              id="completeBank">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jadwal</span><span class="modal-info-value"
              id="completeDate">—</span></div>
        </div>
        <div class="modal-warning green">
          <i class="ph-bold ph-check"></i>
          <p>Konfirmasi bahwa transfer sudah berhasil dikirim. Status akan berubah menjadi <strong>Selesai</strong>.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-modal cancel-btn" onclick="closeModal('modalComplete')">Batal</button>
        <button class="btn-modal green-btn" onclick="submitPayout('complete')"><i class="ph-bold ph-check"></i> Tandai
          Selesai</button>
      </div>
    </div>
  </div>

  <!-- Modal Cancel -->
  <div class="modal-overlay" id="modalCancel">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon-wrap cancel"><i class="ph-bold ph-x-circle"></i></div>
          <div>
            <p class="modal-title">Batalkan Pencairan</p>
            <p class="modal-subtitle" id="cancelSubtitle"></p>
          </div>
        </div>
        <button class="modal-close" onclick="closeModal('modalCancel')"><i class="ph-bold ph-x"></i></button>
      </div>
      <div class="modal-body">
        <div class="modal-info-grid">
          <div class="modal-info-item"><span class="modal-info-label">Host</span><span class="modal-info-value"
              id="cancelHost">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jumlah</span><span class="modal-info-value amount"
              id="cancelAmount">—</span></div>
        </div>
        <div class="modal-warning">
          <i class="ph-bold ph-warning"></i>
          <p>Pencairan akan ditandai <strong>Gagal</strong>. Masukkan alasan pembatalan:</p>
        </div>
        <textarea id="cancelReason" rows="3"
          style="width:100%;margin-top:12px;padding:10px 14px;border:1.5px solid var(--color-border);border-radius:10px;font-family:var(--font-family);font-size:.875rem;resize:vertical;box-sizing:border-box;outline:none;"
          placeholder="Contoh: Nomor rekening tidak valid..."></textarea>
      </div>
      <div class="modal-footer">
        <button class="btn-modal cancel-btn" onclick="closeModal('modalCancel')">Batal</button>
        <button class="btn-modal red-btn" onclick="submitPayout('cancel')"><i class="ph-bold ph-x"></i> Batalkan
          Pencairan</button>
      </div>
    </div>
  </div>

  <!-- Modal Retry -->
  <div class="modal-overlay" id="modalRetry">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon-wrap retry"><i class="ph-bold ph-arrow-clockwise"></i></div>
          <div>
            <p class="modal-title">Coba Lagi</p>
            <p class="modal-subtitle" id="retrySubtitle"></p>
          </div>
        </div>
        <button class="modal-close" onclick="closeModal('modalRetry')"><i class="ph-bold ph-x"></i></button>
      </div>
      <div class="modal-body">
        <div class="modal-info-grid">
          <div class="modal-info-item"><span class="modal-info-label">Host</span><span class="modal-info-value"
              id="retryHost">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jumlah</span><span class="modal-info-value amount"
              id="retryAmount">—</span></div>
        </div>
        <div class="modal-warning yellow">
          <i class="ph-bold ph-arrow-clockwise"></i>
          <p>Payout akan dikembalikan ke status <strong>Dijadwalkan</strong> dan bisa diproses ulang.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-modal cancel-btn" onclick="closeModal('modalRetry')">Batal</button>
        <button class="btn-modal yellow-btn" onclick="submitPayout('retry')"><i class="ph-bold ph-arrow-clockwise"></i>
          Coba Lagi</button>
      </div>
    </div>
  </div>

  <!-- Modal Detail -->
  <div class="modal-overlay" id="modalDetail">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon-wrap detail"><i class="ph-bold ph-receipt"></i></div>
          <div>
            <p class="modal-title">Detail Pencairan Dana</p>
            <p class="modal-subtitle" id="detailSubtitle"></p>
          </div>
        </div>
        <button class="modal-close" onclick="closeModal('modalDetail')"><i class="ph-bold ph-x"></i></button>
      </div>
      <div class="modal-body">
        <div class="modal-info-grid">
          <div class="modal-info-item"><span class="modal-info-label">Host</span><span class="modal-info-value"
              id="dHost">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jumlah</span><span class="modal-info-value amount"
              id="dAmount">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Bank</span><span class="modal-info-value"
              id="dBank">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">No. Rekening</span><span class="modal-info-value"
              id="dAccount">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jadwal Transfer</span><span
              class="modal-info-value" id="dDate">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Tanggal Diproses</span><span
              class="modal-info-value" id="dProcessed">—</span></div>
          <div class="modal-info-item" style="grid-column:1/-1;"><span class="modal-info-label">Status</span><span
              class="modal-info-value" id="dStatus">—</span></div>
        </div>
        <div id="dFailureWrap" style="display:none; margin-top:4px;">
          <div
            style="background:#fff1f0;border:1px solid #fca5a5;border-radius:10px;padding:12px 14px;font-size:.875rem;color:#dc2626;"
            id="dFailure"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-modal cancel-btn" onclick="closeModal('modalDetail')"
          style="color:var(--color-text-primary);">Tutup</button>
      </div>
    </div>
  </div>
  <script src="../dashboard.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {

      const ROWS_PER_PAGE = 10;
      let currentPage = 1, activeFilter = 'all', activeSort = 'date_desc', searchQuery = '';
      let activePayoutId = null;

      const allRows = Array.from(document.querySelectorAll('#payoutTbody tr'));

      function getVal(r, a) { return r.dataset[a] || ''; }

      function matchesFilter(r) {
        if (activeFilter === 'all') return true;
        const [k, v] = activeFilter.split(':');
        return k === 'status' ? r.dataset.status === v : true;
      }

      function matchesSearch(r) {
        if (!searchQuery) return true;
        const q = searchQuery.toLowerCase();
        return getVal(r, 'host').includes(q) || getVal(r, 'bank').includes(q) || getVal(r, 'account').includes(q);
      }

      function sortRows(rows) {
        return [...rows].sort((a, b) => {
          switch (activeSort) {
            case 'date_desc': return getVal(b, 'date') > getVal(a, 'date') ? 1 : -1;
            case 'date_asc': return getVal(a, 'date') > getVal(b, 'date') ? 1 : -1;
            case 'amount_desc': return parseInt(getVal(b, 'amount')) - parseInt(getVal(a, 'amount'));
            case 'amount_asc': return parseInt(getVal(a, 'amount')) - parseInt(getVal(b, 'amount'));
            case 'host_asc': return getVal(a, 'host').localeCompare(getVal(b, 'host'));
            case 'host_desc': return getVal(b, 'host').localeCompare(getVal(a, 'host'));
            default: return 0;
          }
        });
      }

      function render() {
        const filtered = sortRows(allRows.filter(r => matchesFilter(r) && matchesSearch(r)));
        const total = filtered.length;
        const pages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
        currentPage = Math.min(currentPage, pages);
        const start = (currentPage - 1) * ROWS_PER_PAGE;
        const end = start + ROWS_PER_PAGE;

        allRows.forEach(r => { r.style.display = 'none'; r.querySelector('.col-num').textContent = '—'; });
        filtered.slice(start, end).forEach((r, i) => { r.style.display = ''; r.querySelector('.col-num').textContent = start + i + 1; });

        document.getElementById('paginationInfo').textContent =
          total === 0 ? 'Tidak ada data' : `Menampilkan ${start + 1}–${Math.min(end, total)} dari ${total} payout`;

        buildPagination(pages);
      }

      function buildPagination(pages) {
        const ctrl = document.getElementById('paginationControls');
        ctrl.innerHTML = '';
        const mk = (html, page, disabled, active) => {
          const b = document.createElement('button');
          b.className = 'page-btn' + (active ? ' active' : '');
          b.innerHTML = html; b.disabled = disabled;
          b.addEventListener('click', () => { currentPage = page; render(); });
          return b;
        };
        ctrl.appendChild(mk('<i class="ph-bold ph-caret-left"></i>', currentPage - 1, currentPage === 1, false));
        let ps = Math.max(1, currentPage - 2), pe = Math.min(pages, ps + 4);
        if (pe - ps < 4) ps = Math.max(1, pe - 4);
        if (ps > 1) { ctrl.appendChild(mk('1', 1, false, false)); if (ps > 2) { const e = document.createElement('span'); e.className = 'page-btn'; e.textContent = '…'; e.style.cursor = 'default'; ctrl.appendChild(e); } }
        for (let p = ps; p <= pe; p++) ctrl.appendChild(mk(p, p, false, p === currentPage));
        if (pe < pages) { if (pe < pages - 1) { const e = document.createElement('span'); e.className = 'page-btn'; e.textContent = '…'; e.style.cursor = 'default'; ctrl.appendChild(e); } ctrl.appendChild(mk(pages, pages, false, false)); }
        ctrl.appendChild(mk('<i class="ph-bold ph-caret-right"></i>', currentPage + 1, currentPage === pages, false));
      }

      document.getElementById('filterGroup').addEventListener('click', e => {
        const btn = e.target.closest('.filter-item'); if (!btn) return;
        document.querySelectorAll('#filterGroup .filter-item').forEach(b => b.classList.remove('active'));
        btn.classList.add('active'); activeFilter = btn.dataset.filter; currentPage = 1; render();
      });

      document.getElementById('payoutSearch').addEventListener('input', function () {
        searchQuery = this.value.trim().toLowerCase(); currentPage = 1; render();
      });

      const sortToggle = document.getElementById('sortToggleBtn');
      const sortMenu = document.getElementById('sortMenu');
      sortToggle.addEventListener('click', () => sortMenu.classList.toggle('open'));
      document.addEventListener('click', e => {
        if (!sortToggle.contains(e.target) && !sortMenu.contains(e.target)) sortMenu.classList.remove('open');
      });
      window.selectSort = function (key, label, el) {
        activeSort = key;
        document.getElementById('sortLabel').textContent = 'Urutkan: ' + label;
        document.querySelectorAll('.sort-menu-item').forEach(i => i.classList.remove('active'));
        el.classList.add('active'); sortMenu.classList.remove('open'); currentPage = 1; render();
      };

      function openModal(id) { document.getElementById(id).classList.add('open'); }
      function closeModal(id) { document.getElementById(id).classList.remove('open'); }
      window.closeModal = closeModal;

      document.querySelectorAll('.modal-overlay').forEach(o => {
        o.addEventListener('click', e => { if (e.target === o) closeModal(o.id); });
      });
      document.addEventListener('keydown', e => {
        if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
      });

      function rowData(row) {
        return {
          id: row.dataset.id,
          host: row.dataset.hostDisplay,
          bank: row.dataset.bankDisplay || '—',
          account: row.dataset.account || '—',
          amount: row.dataset.amountFmt,
          date: row.dataset.dateFmt,
          processed: row.dataset.processed,
          status: row.dataset.status,
          failure: row.dataset.failure,
          initials: row.dataset.initials,
        };
      }

      const badgeLabel = { Dijadwalkan: 'Dijadwalkan', Processing: 'Diproses', Completed: 'Selesai', Failed: 'Gagal' };
      const badgeCls = { Dijadwalkan: 'warning', Processing: 'info', Completed: 'success', Failed: 'error' };

      function openProses(row) {
        const d = rowData(row); activePayoutId = d.id;
        document.getElementById('prosesSubtitle').textContent = 'Host: ' + d.host;
        document.getElementById('prosesHost').textContent = d.host;
        document.getElementById('prosesAmount').textContent = d.amount;
        document.getElementById('prosesBank').textContent = d.bank !== '—' ? d.bank + ' — ' + d.account : 'Belum diisi';
        document.getElementById('prosesDate').textContent = d.date;
        openModal('modalProses');
      }

      function openComplete(row) {
        const d = rowData(row); activePayoutId = d.id;
        document.getElementById('completeSubtitle').textContent = 'Host: ' + d.host;
        document.getElementById('completeHost').textContent = d.host;
        document.getElementById('completeAmount').textContent = d.amount;
        document.getElementById('completeBank').textContent = d.bank !== '—' ? d.bank + ' — ' + d.account : 'Belum diisi';
        document.getElementById('completeDate').textContent = d.date;
        openModal('modalComplete');
      }

      function openCancel(row) {
        const d = rowData(row); activePayoutId = d.id;
        document.getElementById('cancelSubtitle').textContent = 'Host: ' + d.host;
        document.getElementById('cancelHost').textContent = d.host;
        document.getElementById('cancelAmount').textContent = d.amount;
        document.getElementById('cancelReason').value = '';
        openModal('modalCancel');
      }

      function openRetry(row) {
        const d = rowData(row); activePayoutId = d.id;
        document.getElementById('retrySubtitle').textContent = 'Host: ' + d.host;
        document.getElementById('retryHost').textContent = d.host;
        document.getElementById('retryAmount').textContent = d.amount;
        openModal('modalRetry');
      }

      function openDetail(row) {
        const d = rowData(row);
        document.getElementById('detailSubtitle').textContent = 'Payout #' + d.id;
        document.getElementById('dHost').textContent = d.host;
        document.getElementById('dAmount').textContent = d.amount;
        document.getElementById('dBank').textContent = d.bank;
        document.getElementById('dAccount').textContent = d.account;
        document.getElementById('dDate').textContent = d.date;
        document.getElementById('dProcessed').textContent = d.processed !== '—' ? d.processed : 'Belum diproses';
        document.getElementById('dStatus').innerHTML =
          `<span class="table-badge ${badgeCls[d.status] ?? 'warning'}"><span class="badge-dot"></span>${badgeLabel[d.status] ?? d.status}</span>`;

        const fw = document.getElementById('dFailureWrap');
        if (d.status === 'Failed' && d.failure) {
          document.getElementById('dFailure').textContent = d.failure;
          fw.style.display = '';
        } else {
          fw.style.display = 'none';
        }
        openModal('modalDetail');
      }

      document.getElementById('payoutTbody').addEventListener('click', function (e) {
        const row = e.target.closest('tr'); if (!row) return;
        if (e.target.closest('.btn-proses')) openProses(row);
        if (e.target.closest('.btn-complete')) openComplete(row);
        if (e.target.closest('.btn-cancel')) openCancel(row);
        if (e.target.closest('.btn-retry')) openRetry(row);
        if (e.target.closest('.btn-detail')) openDetail(row);
      });

      window.submitPayout = function (action) {
        if (!activePayoutId) return;
        const fd = new FormData();
        fd.append('payout_id', activePayoutId);
        fd.append('action', action);
        if (action === 'cancel') {
          const reason = document.getElementById('cancelReason').value.trim() || 'Dibatalkan admin';
          fd.append('reason', reason);
        }

        fetch(window.location.pathname, { method: 'POST', body: fd })
          .then(r => r.text())        
          .then(text => {
            console.log('RAW:', text);  
            const data = JSON.parse(text);
            if (data.success) {
              document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
              window.location.reload();
            } else {
              alert('Gagal: ' + (data.message ?? 'Terjadi kesalahan.'));
            }
          })
          .catch(err => console.error('Parse error:', err));
      };

      render();
    });
  </script>
</body>

</html>