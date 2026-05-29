<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');

  $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
  $action = trim($_POST['action'] ?? '');

  if ($id <= 0 || !in_array($action, ['approve', 'reject', 'delete'], true)) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
    $koneksi->close();
    exit;
  }

  $stmt = $koneksi->prepare("
    SELECT t.id, t.booking_id, t.status AS trx_status
    FROM transactions t
    JOIN bookings b ON b.id = t.booking_id
    WHERE t.id = ? LIMIT 1
  ");
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $row = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Transaksi tidak ditemukan.']);
    $koneksi->close();
    exit;
  }

  $booking_id = (int) $row['booking_id'];
  $trx_status = $row['trx_status'];

  $koneksi->begin_transaction();
  try {
    if ($action === 'approve') {
      if ($trx_status !== 'menunggu')
        throw new Exception('Hanya transaksi berstatus menunggu yang bisa diterima.');
      $s = $koneksi->prepare("UPDATE transactions SET status = 'sukses' WHERE id = ?");
      $s->bind_param('i', $id);
      $s->execute();
      $s->close();
      $s = $koneksi->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
      $s->bind_param('i', $booking_id);
      $s->execute();
      $s->close();

    } elseif ($action === 'reject') {
      if ($trx_status === 'gagal')
        throw new Exception('Transaksi sudah berstatus gagal.');
      $s = $koneksi->prepare("UPDATE transactions SET status = 'gagal' WHERE id = ?");
      $s->bind_param('i', $id);
      $s->execute();
      $s->close();
      $s = $koneksi->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
      $s->bind_param('i', $booking_id);
      $s->execute();
      $s->close();

    } elseif ($action === 'delete') {
      $s = $koneksi->prepare("DELETE FROM transactions WHERE id = ?");
      $s->bind_param('i', $id);
      $s->execute();
      $s->close();
    }

    $koneksi->commit();
    echo json_encode(['success' => true]);
  } catch (Exception $e) {
    $koneksi->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }

  $koneksi->close();
  exit;
}

$result = $koneksi->query("
    SELECT
        t.id, t.booking_id, t.jumlah, t.metode,
        t.no_hp, t.nama_kartu, t.nomor_kartu,
        t.status, t.dibayar_pada,
        u.nama AS nama_user, u.photo AS user_photo
    FROM transactions t
    JOIN bookings b ON b.id = t.booking_id
    JOIN users    u ON u.id = b.user_id
    ORDER BY t.id DESC
");

$rows = [];
while ($row = $result->fetch_assoc()) {
  $rows[] = $row;
}
$koneksi->close();

$metode_label = [
  'gopay' => 'GoPay',
  'ovo' => 'OVO',
  'dana' => 'DANA',
  'visa' => 'Visa',
  'mastercard' => 'Mastercard',
];

function statusBadge(string $status): string
{
  $map = [
    'sukses' => ['class' => 'success', 'label' => 'Sukses'],
    'menunggu' => ['class' => 'warning', 'label' => 'Pending'],
    'gagal' => ['class' => 'danger', 'label' => 'Gagal'],
  ];
  $s = $map[$status] ?? ['class' => 'info', 'label' => ucfirst($status)];
  return "<span class=\"table-badge {$s['class']}\"><span class=\"badge-dot\"></span>{$s['label']}</span>";
}

function initials(string $name): string
{
  $parts = explode(' ', trim($name));
  $ini = strtoupper(mb_substr($parts[0], 0, 1));
  if (count($parts) > 1)
    $ini .= strtoupper(mb_substr($parts[1], 0, 1));
  return $ini;
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Transaksi | Admin Teman Singgah</title>
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

    .table-container .managed-table thead tr th.col-num,
    .table-container .managed-table tbody tr td.col-num {
      text-align: center;
      width: 55px;
    }

    .table-container .managed-table tbody tr td.col-num {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
    }

    .action-group {
      display: flex;
      align-items: center;
      gap: 6px;
      justify-content: center;
    }

    .btn-action {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 32px;
      height: 32px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 15px;
      transition: background 0.15s, transform 0.1s, opacity 0.15s;
      flex-shrink: 0;
    }

    .btn-action:active {
      transform: scale(0.93);
    }

    .btn-action:disabled {
      opacity: 0.35;
      cursor: not-allowed;
      transform: none;
    }

    .btn-action.approve {
      background: rgba(22, 163, 74, 0.10);
      color: #16a34a;
    }

    .btn-action.approve:hover:not(:disabled) {
      background: rgba(22, 163, 74, 0.20);
    }

    .btn-action.reject {
      background: rgba(220, 38, 38, 0.10);
      color: #dc2626;
    }

    .btn-action.reject:hover:not(:disabled) {
      background: rgba(220, 38, 38, 0.20);
    }

    .btn-action.detail {
      background: rgba(139, 37, 0, 0.08);
      color: var(--color-primary, #8b2500);
    }

    .btn-action.detail:hover:not(:disabled) {
      background: rgba(139, 37, 0, 0.16);
    }

    .btn-action.delete {
      background: rgba(139, 37, 0, 0.06);
      color: var(--color-primary, #8b2500);
      opacity: 0.65;
    }

    .btn-action.delete:hover:not(:disabled) {
      background: rgba(139, 37, 0, 0.14);
      opacity: 1;
    }

    .sort-dropdown {
      position: relative;
    }

    .sort-menu {
      display: none;
      position: absolute;
      right: 0;
      top: calc(100% + 6px);
      background: var(--color-bg-card, #fff);
      border: 1px solid var(--color-border, #e5e7eb);
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.10);
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
      color: var(--color-text-primary, #374151);
      transition: background 0.15s;
      font-family: var(--font-family, 'Inter', sans-serif);
    }

    .sort-menu-item:hover {
      background: var(--color-bg-hover, #f9fafb);
    }

    .sort-menu-item.active {
      color: var(--color-primary, #8b2500);
      font-weight: 600;
      background: rgba(139, 37, 0, 0.04);
    }

    .sort-menu-divider {
      height: 1px;
      background: #f3f4f6;
      margin: 4px 0;
    }

    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.45);
      z-index: 9000;
      display: flex;
      align-items: center;
      justify-content: center;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.22s ease;
    }

    .modal-overlay.open {
      opacity: 1;
      pointer-events: all;
    }

    .modal-box {
      background: var(--color-bg-card, #fff);
      border-radius: 16px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.18);
      width: 100%;
      max-width: 460px;
      margin: 16px;
      transform: translateY(16px) scale(0.97);
      transition: transform 0.22s ease;
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

    .modal-icon-wrap.approve {
      background: rgba(22, 163, 74, 0.12);
      color: #16a34a;
    }

    .modal-icon-wrap.reject {
      background: rgba(220, 38, 38, 0.12);
      color: #dc2626;
    }

    .modal-icon-wrap.detail {
      background: rgba(139, 37, 0, 0.10);
      color: var(--color-primary, #8b2500);
    }

    .modal-icon-wrap.delete {
      background: rgba(139, 37, 0, 0.08);
      color: var(--color-primary, #8b2500);
    }

    .modal-title {
      font-size: 1rem;
      font-weight: 600;
      color: var(--color-text-primary, #111);
      margin: 0;
    }

    .modal-subtitle {
      font-size: 0.78rem;
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
      transition: background 0.15s;
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
      font-size: 0.72rem;
      font-weight: 600;
      color: var(--color-text-hint, #9ca3af);
      text-transform: uppercase;
      letter-spacing: 0.05em;
    }

    .modal-info-value {
      font-size: 0.875rem;
      font-weight: 500;
      color: var(--color-text-primary, #111);
    }

    .modal-info-value.amount {
      font-size: 1.1rem;
      font-weight: 700;
      color: var(--color-primary, #8b2500);
    }

    .refund-section {
      border: 1.5px solid var(--color-border, #e5e7eb);
      border-radius: 12px;
      overflow: hidden;
    }

    .refund-section-label {
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--color-text-secondary, #6b7280);
      padding: 10px 14px 8px;
      background: var(--color-bg-hover, #f9fafb);
      border-bottom: 1px solid var(--color-border, #e5e7eb);
    }

    .refund-option {
      display: flex;
      align-items: flex-start;
      gap: 12px;
      padding: 13px 14px;
      cursor: pointer;
      transition: background 0.12s;
      border-bottom: 1px solid var(--color-border, #e5e7eb);
    }

    .refund-option:last-child {
      border-bottom: none;
    }

    .refund-option:hover {
      background: var(--color-bg-hover, #f9fafb);
    }

    .refund-option input[type="radio"] {
      margin-top: 2px;
      accent-color: var(--color-primary, #8b2500);
      cursor: pointer;
      flex-shrink: 0;
    }

    .refund-option-text strong {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--color-text-primary, #111);
      margin-bottom: 2px;
    }

    .refund-option-text span {
      font-size: 0.78rem;
      color: var(--color-text-secondary, #6b7280);
      line-height: 1.4;
    }

    .modal-warning {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      background: rgba(239, 68, 68, 0.07);
      border: 1px solid rgba(239, 68, 68, 0.18);
      border-radius: 10px;
      padding: 12px 14px;
      margin-top: 14px;
    }

    .modal-warning i {
      color: #ef4444;
      font-size: 16px;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .modal-warning p {
      margin: 0;
      font-size: 0.82rem;
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
      font-size: 0.875rem;
      font-weight: 600;
      cursor: pointer;
      border: none;
      transition: all 0.15s;
    }

    .btn-modal.cancel {
      background: var(--color-bg-hover, #f3f4f6);
      color: var(--color-text-secondary, #6b7280);
      border: 1.5px solid var(--color-border, #e5e7eb);
    }

    .btn-modal.cancel:hover {
      background: var(--color-border, #e5e7eb);
    }

    .btn-modal.confirm-approve {
      background: #16a34a;
      color: #fff;
    }

    .btn-modal.confirm-approve:hover {
      background: #15803d;
    }

    .btn-modal.confirm-reject {
      background: #dc2626;
      color: #fff;
    }

    .btn-modal.confirm-reject:hover {
      background: #b91c1c;
    }

    .btn-modal.confirm-delete {
      background: var(--color-primary, #8b2500);
      color: #fff;
    }

    .btn-modal.confirm-delete:hover {
      background: #6b1a00;
    }
  </style>
</head>

<body>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <a href="../../pages/dashboard.php" class="logo-link"></a>
        <div class="logo-section">
          <img src="../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
          <img src="../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title">Halaman Utama</div>
          <a href="../pages/dashboard.php" class="nav-item"><i class="ph-bold ph-squares-four"></i>Dashboard</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Manajemen</div>
          <a href="../../pages/users.php" class="nav-item"><i class="ph-bold ph-users"></i>Pengguna</a>
          <a href="../pages/listings.php" class="nav-item"><i class="ph-bold ph-house"></i>Properti</a>
          <a href="../pages/reservations.php" class="nav-item"><i class="ph-bold ph-calendar-check"></i>Reservasi</a>
          <a href="../pages/transactions.php" class="nav-item active"><i
              class="ph-bold ph-currency-circle-dollar"></i>Transaksi</a>
          <a href="/teman_singgah/admin/pages/promos.php" class="nav-item"><i class="ph-bold ph-tag"></i>Promo &amp;
            Deals</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Moderasi</div>
          <a href="../pages/reviews.php" class="nav-item"><i class="ph-bold ph-star"></i>Ulasan</a>
          <a href="../pages/reports.php" class="nav-item"><i class="ph-bold ph-flag"></i>Laporan</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Keuangan</div>
          <a href="../pages/payouts.php" class="nav-item"><i class="ph-bold ph-money"></i>Pembayaran</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Sistem</div>
          <a href="../pages/settings.php" class="nav-item"><i class="ph-bold ph-gear"></i>Pengaturan</a>
          <a href="../pages/logs.php" class="nav-item"><i class="ph-bold ph-notepad"></i>Aktivitas</a>
        </div>
      </nav>
    </aside>

    <div class="main-container">
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">Riwayat Transaksi</h1>
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
              <input type="search" id="adminSearch" class="table-search-input" placeholder="Cari ID, nama, metode..."
                oninput="filterTable()" />
            </div>
          </div>
        </div>

        <div class="filter-container">
          <div class="filter-group" id="filterGroup">
            <button class="filter-item active" data-filter="">Semua</button>
            <button class="filter-item" data-filter="sukses">Sukses</button>
            <button class="filter-item" data-filter="menunggu">Pending</button>
            <button class="filter-item" data-filter="gagal">Gagal</button>
          </div>
          <div class="sort-dropdown">
            <button class="sort-button" id="sortBtn">
              <i class="ph-bold ph-faders-horizontal"></i>
              <span id="sortLabel">Urutkan: Terbaru</span>
              <i class="ph-bold ph-caret-down"></i>
            </button>
            <div class="sort-menu" id="sortMenu">
              <div class="sort-menu-item active" onclick="selectSort('newest', 'Terbaru', this)">
                <i class="ph-bold ph-clock-counter-clockwise"></i> Terbaru
              </div>
              <div class="sort-menu-item" onclick="selectSort('oldest', 'Terlama', this)">
                <i class="ph-bold ph-clock"></i> Terlama
              </div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('amount_desc', 'Jumlah Terbesar', this)">
                <i class="ph-bold ph-sort-descending"></i> Jumlah Terbesar
              </div>
              <div class="sort-menu-item" onclick="selectSort('amount_asc', 'Jumlah Terkecil', this)">
                <i class="ph-bold ph-sort-ascending"></i> Jumlah Terkecil
              </div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('name_asc', 'Nama A–Z', this)">
                <i class="ph-bold ph-sort-ascending"></i> Nama A–Z
              </div>
              <div class="sort-menu-item" onclick="selectSort('name_desc', 'Nama Z–A', this)">
                <i class="ph-bold ph-sort-descending"></i> Nama Z–A
              </div>
            </div>
          </div>
        </div>

        <section class="table-section">
          <div class="table-container">
            <table class="managed-table" id="transactionTable">
              <thead>
                <tr>
                  <th class="col-num">No.</th>
                  <th>ID Transaksi</th>
                  <th>User</th>
                  <th>Jumlah</th>
                  <th>Metode</th>
                  <th>Status</th>
                  <th>Tanggal &amp; Waktu</th>
                  <th style="text-align:center;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($rows)): ?>
                  <tr>
                    <td colspan="8" style="text-align:center; color:var(--color-text-hint); padding:32px;">Belum ada
                      transaksi.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($rows as $i => $t):
                    $trx_id = '#TRX-' . date('Y', strtotime($t['dibayar_pada'] ?? 'now')) . '-' . str_pad($t['id'], 4, '0', STR_PAD_LEFT);
                    $metode = $metode_label[$t['metode']] ?? ucfirst($t['metode']);
                    $waktu = $t['dibayar_pada'] ? date('d M Y, H:i', strtotime($t['dibayar_pada'])) : '-';
                    $timestamp = $t['dibayar_pada'] ? strtotime($t['dibayar_pada']) : 0;
                    $inisial = initials($t['nama_user']);
                    $jumlah = 'Rp' . number_format($t['jumlah'], 0, ',', '.');
                    $photo_path = !empty($t['user_photo']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $t['user_photo'])
                      ? '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($t['user_photo'])
                      : null;
                    ?>
                    <tr data-status="<?= htmlspecialchars($t['status']) ?>" data-id="<?= (int) $t['id'] ?>"
                      data-timestamp="<?= $timestamp ?>" data-trx="<?= htmlspecialchars($trx_id) ?>"
                      data-user="<?= htmlspecialchars($t['nama_user']) ?>" data-jumlah="<?= htmlspecialchars($jumlah) ?>"
                      data-metode="<?= htmlspecialchars($metode) ?>" data-waktu="<?= htmlspecialchars($waktu) ?>">
                      <td class="col-num"><?= $i + 1 ?></td>
                      <td><span class="id-code"><?= htmlspecialchars($trx_id) ?></span></td>
                      <td>
                        <div class="table-cell">
                          <?php if ($photo_path): ?>
                            <div class="table-avatar" style="padding:0; overflow:hidden;">
                              <img src="<?= $photo_path ?>" alt="Foto"
                                style="width:100%; height:100%; object-fit:cover; border-radius:50%;" />
                            </div>
                          <?php else: ?>
                            <div class="table-avatar"><?= $inisial ?></div>
                          <?php endif; ?>
                          <h3 class="table-name"><?= htmlspecialchars($t['nama_user']) ?></h3>
                        </div>
                      </td>
                      <td><?= $jumlah ?></td>
                      <td><?= htmlspecialchars($metode) ?></td>
                      <td><?= statusBadge($t['status']) ?></td>
                      <td><?= $waktu ?></td>
                      <td>
                        <div class="action-group">
                          <button class="btn-action approve" title="Terima transaksi" onclick="openApprove(this)"
                            <?= $t['status'] !== 'menunggu' ? 'disabled' : '' ?>>
                            <i class="ph-bold ph-check"></i>
                          </button>
                          <button class="btn-action reject" title="Tolak transaksi" onclick="openReject(this)"
                            <?= $t['status'] === 'gagal' ? 'disabled' : '' ?>>
                            <i class="ph-bold ph-x"></i>
                          </button>
                          <button class="btn-action detail" title="Lihat detail" onclick="openDetail(this)">
                            <i class="ph-bold ph-eye"></i>
                          </button>
                          <button class="btn-action delete" title="Hapus transaksi" onclick="openDelete(this)">
                            <i class="ph-bold ph-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
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

  <div class="modal-overlay" id="modalApprove">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon-wrap approve"><i class="ph-bold ph-check-circle"></i></div>
          <div>
            <p class="modal-title">Terima Transaksi</p>
            <p class="modal-subtitle" id="approveSubtitle"></p>
          </div>
        </div>
        <button class="modal-close" onclick="closeModal('modalApprove')"><i class="ph-bold ph-x"></i></button>
      </div>
      <div class="modal-body">
        <div class="modal-info-grid">
          <div class="modal-info-item"><span class="modal-info-label">ID Transaksi</span><span class="modal-info-value"
              id="approveTrxId">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Nama User</span><span class="modal-info-value"
              id="approveUser">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jumlah</span><span class="modal-info-value amount"
              id="approveJumlah">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Metode</span><span class="modal-info-value"
              id="approveMetode">—</span></div>
        </div>
        <div class="modal-warning" style="background:rgba(22,163,74,0.07); border-color:rgba(22,163,74,0.18);">
          <i class="ph-bold ph-info" style="color:#16a34a;"></i>
          <p>Status transaksi akan diubah menjadi <strong>Sukses</strong> dan reservasi terkait akan dikonfirmasi secara
            otomatis.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-modal cancel" onclick="closeModal('modalApprove')">Batal</button>
        <button class="btn-modal confirm-approve" onclick="submitAction('approve')"><i class="ph-bold ph-check"></i>
          Terima Transaksi</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="modalReject">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon-wrap reject"><i class="ph-bold ph-x-circle"></i></div>
          <div>
            <p class="modal-title">Tolak Transaksi</p>
            <p class="modal-subtitle" id="rejectSubtitle"></p>
          </div>
        </div>
        <button class="modal-close" onclick="closeModal('modalReject')"><i class="ph-bold ph-x"></i></button>
      </div>
      <div class="modal-body">
        <div class="modal-info-grid">
          <div class="modal-info-item"><span class="modal-info-label">ID Transaksi</span><span class="modal-info-value"
              id="rejectTrxId">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Nama User</span><span class="modal-info-value"
              id="rejectUser">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jumlah</span><span class="modal-info-value amount"
              id="rejectJumlah">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Metode</span><span class="modal-info-value"
              id="rejectMetode">—</span></div>
        </div>
        <div class="refund-section">
          <div class="refund-section-label">Kebijakan Pengembalian Dana</div>
          <label class="refund-option">
            <input type="radio" name="refundPolicy" value="refund" checked />
            <div class="refund-option-text">
              <strong>Refund ke pengguna</strong>
              <span>Dana dikembalikan ke metode pembayaran asal. Sesuai untuk penginapan dengan kebijakan
                fleksibel.</span>
            </div>
          </label>
          <label class="refund-option">
            <input type="radio" name="refundPolicy" value="no_refund" />
            <div class="refund-option-text">
              <strong>Tanpa refund</strong>
              <span>Dana tidak dikembalikan. Sesuai untuk penginapan dengan kebijakan ketat atau pembatalan
                mendadak.</span>
            </div>
          </label>
        </div>
        <div class="modal-warning">
          <i class="ph-bold ph-warning"></i>
          <p>Tindakan ini akan mengubah status menjadi <strong>Gagal</strong> dan membatalkan reservasi terkait.
            Pastikan kebijakan penginapan sudah sesuai sebelum melanjutkan.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-modal cancel" onclick="closeModal('modalReject')">Batal</button>
        <button class="btn-modal confirm-reject" onclick="submitAction('reject')"><i class="ph-bold ph-x"></i> Tolak
          Transaksi</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="modalDetail">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon-wrap detail"><i class="ph-bold ph-receipt"></i></div>
          <div>
            <p class="modal-title">Detail Transaksi</p>
            <p class="modal-subtitle" id="detailSubtitle"></p>
          </div>
        </div>
        <button class="modal-close" onclick="closeModal('modalDetail')"><i class="ph-bold ph-x"></i></button>
      </div>
      <div class="modal-body">
        <div class="modal-info-grid">
          <div class="modal-info-item"><span class="modal-info-label">ID Transaksi</span><span class="modal-info-value"
              id="detailTrxId">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">ID Booking</span><span class="modal-info-value"
              id="detailBookingId">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Nama User</span><span class="modal-info-value"
              id="detailUser">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Metode Bayar</span><span class="modal-info-value"
              id="detailMetode">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jumlah</span><span class="modal-info-value amount"
              id="detailJumlah">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Status</span><span class="modal-info-value"
              id="detailStatus">—</span></div>
          <div class="modal-info-item" style="grid-column:1/-1;"><span class="modal-info-label">Tanggal &amp;
              Waktu</span><span class="modal-info-value" id="detailWaktu">—</span></div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-modal cancel" onclick="closeModal('modalDetail')"
          style="color:var(--color-text-primary);">Tutup</button>
      </div>
    </div>
  </div>

  <div class="modal-overlay" id="modalDelete">
    <div class="modal-box">
      <div class="modal-header">
        <div class="modal-header-left">
          <div class="modal-icon-wrap delete"><i class="ph-bold ph-trash"></i></div>
          <div>
            <p class="modal-title">Hapus Transaksi</p>
            <p class="modal-subtitle" id="deleteSubtitle"></p>
          </div>
        </div>
        <button class="modal-close" onclick="closeModal('modalDelete')"><i class="ph-bold ph-x"></i></button>
      </div>
      <div class="modal-body">
        <div class="modal-info-grid">
          <div class="modal-info-item"><span class="modal-info-label">ID Transaksi</span><span class="modal-info-value"
              id="deleteTrxId">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Nama User</span><span class="modal-info-value"
              id="deleteUser">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Jumlah</span><span class="modal-info-value amount"
              id="deleteJumlah">—</span></div>
          <div class="modal-info-item"><span class="modal-info-label">Status</span><span class="modal-info-value"
              id="deleteStatus">—</span></div>
        </div>
        <div class="modal-warning">
          <i class="ph-bold ph-warning"></i>
          <p>Data transaksi ini akan <strong>dihapus permanen</strong> dari sistem dan tidak dapat dipulihkan.</p>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn-modal cancel" onclick="closeModal('modalDelete')">Batal</button>
        <button class="btn-modal confirm-delete" onclick="submitAction('delete')"><i class="ph-bold ph-trash"></i> Hapus
          Permanen</button>
      </div>
    </div>
  </div>

  <script>
    const allRows = Array.from(document.querySelectorAll('#transactionTable tbody tr[data-status]'));
    const tbody = document.querySelector('#transactionTable tbody');
    let activeFilter = '';
    const PAGE_SIZE = 10;
    let currentPage = 1;
    let activeRowId = null;
    let currentSort = 'newest';

    const statusLabel = { sukses: 'Sukses', menunggu: 'Pending', gagal: 'Gagal' };

    function getRowData(btn) {
      const tr = btn.closest('tr');
      return {
        id: tr.dataset.id,
        trxId: tr.dataset.trx,
        user: tr.dataset.user,
        jumlah: tr.dataset.jumlah,
        metode: tr.dataset.metode,
        waktu: tr.dataset.waktu,
        status: tr.dataset.status,
      };
    }

    function openApprove(btn) {
      const d = getRowData(btn); activeRowId = d.id;
      document.getElementById('approveSubtitle').textContent = d.trxId;
      document.getElementById('approveTrxId').textContent = d.trxId;
      document.getElementById('approveUser').textContent = d.user;
      document.getElementById('approveJumlah').textContent = d.jumlah;
      document.getElementById('approveMetode').textContent = d.metode;
      openModal('modalApprove');
    }

    function openReject(btn) {
      const d = getRowData(btn); activeRowId = d.id;
      document.getElementById('rejectSubtitle').textContent = d.trxId;
      document.getElementById('rejectTrxId').textContent = d.trxId;
      document.getElementById('rejectUser').textContent = d.user;
      document.getElementById('rejectJumlah').textContent = d.jumlah;
      document.getElementById('rejectMetode').textContent = d.metode;
      document.querySelector('input[name="refundPolicy"][value="refund"]').checked = true;
      openModal('modalReject');
    }

    function openDetail(btn) {
      const d = getRowData(btn); activeRowId = d.id;
      document.getElementById('detailSubtitle').textContent = d.trxId;
      document.getElementById('detailTrxId').textContent = d.trxId;
      document.getElementById('detailBookingId').textContent = '#BKG-' + String(d.id).padStart(4, '0');
      document.getElementById('detailUser').textContent = d.user;
      document.getElementById('detailMetode').textContent = d.metode;
      document.getElementById('detailJumlah').textContent = d.jumlah;
      document.getElementById('detailStatus').textContent = statusLabel[d.status] ?? d.status;
      document.getElementById('detailWaktu').textContent = d.waktu;
      openModal('modalDetail');
    }

    function openDelete(btn) {
      const d = getRowData(btn); activeRowId = d.id;
      document.getElementById('deleteSubtitle').textContent = d.trxId;
      document.getElementById('deleteTrxId').textContent = d.trxId;
      document.getElementById('deleteUser').textContent = d.user;
      document.getElementById('deleteJumlah').textContent = d.jumlah;
      document.getElementById('deleteStatus').textContent = statusLabel[d.status] ?? d.status;
      openModal('modalDelete');
    }

    function openModal(id) { document.getElementById(id).classList.add('open'); }
    function closeModal(id) { document.getElementById(id).classList.remove('open'); activeRowId = null; }

    document.querySelectorAll('.modal-overlay').forEach(o => {
      o.addEventListener('click', function (e) { if (e.target === this) closeModal(this.id); });
    });
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
    });

    function submitAction(type) {
      if (!activeRowId) return;
      const fd = new FormData();
      fd.append('id', activeRowId);
      fd.append('action', type);
      if (type === 'reject') fd.append('refund_policy', document.querySelector('input[name="refundPolicy"]:checked').value);

      fetch(window.location.pathname, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            document.querySelectorAll('.modal-overlay.open').forEach(m => closeModal(m.id));
            window.location.reload();
          } else {
            alert('Gagal: ' + (data.message ?? 'Terjadi kesalahan.'));
          }
        })
        .catch(() => alert('Terjadi kesalahan jaringan. Coba lagi.'));
    }

    document.querySelectorAll('#filterGroup .filter-item').forEach(btn => {
      btn.addEventListener('click', function () {
        document.querySelectorAll('#filterGroup .filter-item').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        activeFilter = this.dataset.filter || '';
        currentPage = 1;
        render();
      });
    });

    const sortMenu = document.getElementById('sortMenu');
    document.getElementById('sortBtn').addEventListener('click', function (e) {
      e.stopPropagation();
      sortMenu.classList.toggle('open');
    });
    document.addEventListener('click', function () { sortMenu.classList.remove('open'); });
    sortMenu.addEventListener('click', function (e) { e.stopPropagation(); });

    function selectSort(key, label, el) {
      currentSort = key;
      document.getElementById('sortLabel').textContent = 'Urutkan: ' + label;
      document.querySelectorAll('#sortMenu .sort-menu-item').forEach(i => i.classList.remove('active'));
      el.classList.add('active');
      sortMenu.classList.remove('open');
      currentPage = 1;
      render();
    }

    function filterTable() { currentPage = 1; render(); }

    function render() {
      const q = document.getElementById('adminSearch').value.toLowerCase().trim();

      let visible = allRows.filter(tr => {
        const matchQ = !q || tr.textContent.toLowerCase().includes(q);
        const matchF = !activeFilter || tr.dataset.status === activeFilter;
        return matchQ && matchF;
      });

      const getAmt = tr => parseInt((tr.dataset.jumlah || '').replace(/[^\d]/g, ''), 10) || 0;
      const getName = tr => tr.dataset.user.toLowerCase();
      const getTs = tr => parseInt(tr.dataset.timestamp, 10) || 0;

      if (currentSort === 'amount_desc') visible.sort((a, b) => getAmt(b) - getAmt(a));
      if (currentSort === 'amount_asc') visible.sort((a, b) => getAmt(a) - getAmt(b));
      if (currentSort === 'name_asc') visible.sort((a, b) => getName(a) < getName(b) ? -1 : 1);
      if (currentSort === 'name_desc') visible.sort((a, b) => getName(a) > getName(b) ? -1 : 1);
      if (currentSort === 'newest') visible.sort((a, b) => getTs(b) - getTs(a));
      if (currentSort === 'oldest') visible.sort((a, b) => getTs(a) - getTs(b));

      const total = visible.length;
      const maxPage = Math.max(1, Math.ceil(total / PAGE_SIZE));
      if (currentPage > maxPage) currentPage = maxPage;

      const start = (currentPage - 1) * PAGE_SIZE;
      const pageRows = visible.slice(start, start + PAGE_SIZE);

      allRows.forEach(tr => { tr.style.display = 'none'; });

      visible.forEach(tr => tbody.appendChild(tr));

      pageRows.forEach((tr, i) => {
        tr.style.display = '';
        tr.children[0].textContent = start + i + 1;
      });

      const info = document.getElementById('paginationInfo');
      info.textContent = total === 0
        ? 'Tidak ada data'
        : `Menampilkan ${start + 1}–${Math.min(start + PAGE_SIZE, total)} dari ${total} transaksi`;

      renderPagination(maxPage);
    }

    function renderPagination(maxPage) {
      const ctrl = document.getElementById('paginationControls');
      ctrl.innerHTML = '';
      if (maxPage <= 1) return;

      const mk = (label, page, disabled = false, active = false) => {
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (active ? ' active' : '');
        btn.textContent = label;
        btn.disabled = disabled;
        btn.addEventListener('click', () => { currentPage = page; render(); });
        return btn;
      };

      ctrl.appendChild(mk('‹', currentPage - 1, currentPage === 1));
      for (let p = 1; p <= maxPage; p++) {
        if (maxPage > 7 && p > 2 && p < maxPage - 1 && Math.abs(p - currentPage) > 1) {
          if (p === 3 || p === maxPage - 2) {
            const sp = document.createElement('span');
            sp.textContent = '…'; sp.style.padding = '0 4px';
            ctrl.appendChild(sp);
          }
          continue;
        }
        ctrl.appendChild(mk(p, p, false, p === currentPage));
      }
      ctrl.appendChild(mk('›', currentPage + 1, currentPage === maxPage));
    }

    document.addEventListener('DOMContentLoaded', render);
  </script>

  <script src="/teman_singgah/admin/dashboard.js"></script>
</body>

</html>