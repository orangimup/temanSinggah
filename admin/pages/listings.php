<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
  header('Content-Type: application/json');
  $listing_id = (int) ($_POST['listing_id'] ?? 0);
  $new_status = trim($_POST['new_status'] ?? '');
  if (!$listing_id || !in_array($new_status, ['aktif', 'nonaktif'])) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
    exit;
  }
  $stmt = mysqli_prepare($koneksi, "UPDATE listings SET status = ? WHERE id = ?");
  mysqli_stmt_bind_param($stmt, 'si', $new_status, $listing_id);
  $ok = mysqli_stmt_execute($stmt);
  mysqli_stmt_close($stmt);
  echo json_encode(
    $ok
    ? ['success' => true, 'new_status' => $new_status]
    : ['success' => false, 'message' => 'Gagal memperbarui status.']
  );
  exit;
}

$result = mysqli_query($koneksi, "
    SELECT
        l.id,
        l.judul,
        l.tipe_properti,
        l.tipe_privasi,
        l.tipe_booking,
        l.lokasi,
        l.harga_malam,
        l.max_tamu,
        l.kamar_tidur,
        l.tempat_tidur,
        l.kamar_mandi,
        l.status,
        l.dibuat_pada,
        l.deskripsi,
        l.kebijakan_pembatalan,
        l.min_malam,
        l.jam_checkin,
        l.jam_checkout,
        u.nama        AS host_nama,
        u.photo       AS host_photo,
        lp.nama_file  AS foto_cover,
        lp2.boleh_hewan,
        lp2.boleh_merokok,
        lp2.boleh_anak,
        COUNT(DISTINCT b.id)      AS total_booking,
        ROUND(AVG(r.rating), 1)   AS rating_avg
    FROM listings l
    JOIN users u ON l.host_id = u.id
    LEFT JOIN listing_photos lp ON lp.listing_id = l.id AND lp.adalah_cover = 1
    LEFT JOIN listing_policies lp2 ON lp2.listing_id = l.id
    LEFT JOIN bookings b ON b.listing_id = l.id
    LEFT JOIN reviews r ON r.listing_id = l.id
    GROUP BY
        l.id, l.judul, l.tipe_properti, l.tipe_privasi, l.tipe_booking,
        l.lokasi, l.harga_malam, l.max_tamu, l.kamar_tidur, l.tempat_tidur,
        l.kamar_mandi, l.status, l.dibuat_pada, l.deskripsi,
        l.kebijakan_pembatalan, l.min_malam, l.jam_checkin, l.jam_checkout,
        u.nama, u.photo, lp.nama_file,
        lp2.boleh_hewan, lp2.boleh_merokok, lp2.boleh_anak
    ORDER BY l.dibuat_pada DESC
");

$rooms_map = [];
$rooms_result = mysqli_query($koneksi, "SELECT listing_id, nama FROM listing_rooms ORDER BY urutan ASC, id ASC");
while ($r = mysqli_fetch_assoc($rooms_result)) {
  $rooms_map[$r['listing_id']][] = $r['nama'];
}

$amenities_map = [];
$am_result = mysqli_query($koneksi, "SELECT listing_id, nama_fasilitas FROM listing_amenities");
while ($r = mysqli_fetch_assoc($am_result)) {
  $amenities_map[$r['listing_id']][] = $r['nama_fasilitas'];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Properti | Admin Teman Singgah</title>
  <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="/teman_singgah/components/root.css" />
  <link rel="stylesheet" href="/teman_singgah/admin/dashboard.css" />
  <link href="https://fonts.googleapis.com" rel="preconnect" />
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
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

    .pill {
      display: inline-block;
      padding: 2px 9px;
      border-radius: 99px;
      font-size: 11.5px;
      font-weight: 500;
      white-space: nowrap;
    }

    .pill-tipe {
      background: #f3f4f6;
      color: #374151;
    }

    .pill-privasi {
      background: #eff6ff;
      color: #1d4ed8;
    }

    .pill-instan {
      background: #f0fdf4;
      color: #15803d;
    }

    .pill-permintaan {
      background: #fefce8;
      color: #a16207;
    }

    .pill-properti {
      background: #fdf4ff;
      color: #7e22ce;
    }

    .rating-cell {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: var(--text-sm);
      font-weight: 500;
    }

    .rating-cell i {
      color: #f59e0b;
      font-size: .85rem;
    }

    .rating-cell .no-rating {
      color: var(--color-text-disabled);
      font-weight: 400;
    }

    .booking-count {
      font-size: var(--text-sm);
      color: var(--color-text-primary);
      font-weight: 500;
    }

    .booking-count span {
      color: var(--color-text-disabled);
      font-weight: 400;
      font-size: 11px;
    }

    .kamar-chips {
      display: flex;
      gap: 4px;
      flex-wrap: wrap;
    }

    .kamar-chip {
      font-size: 11px;
      color: var(--color-text-secondary);
      background: var(--color-bg-section, #f9fafb);
      border: 1px solid var(--color-border-subtle, #e5e7eb);
      border-radius: 6px;
      padding: 1px 7px;
      white-space: nowrap;
    }

    .kamar-chip-more {
      color: var(--color-text-hint);
      font-style: italic;
    }

    .detail-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .45);
      z-index: var(--z-modal);
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(3px);
      padding: 24px;
      box-sizing: border-box;
    }

    .detail-overlay.show {
      display: flex;
    }

    .detail-modal {
      background: var(--color-bg-card);
      border-radius: var(--radius-2xl);
      box-shadow: 0 20px 60px rgba(0, 0, 0, .20);
      border: 1.5px solid var(--color-border-subtle);
      width: 100%;
      max-width: 880px;
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .dm-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 20px 28px;
      border-bottom: 1.5px solid var(--color-border-subtle);
      flex-shrink: 0;
    }

    .dm-header-left {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .dm-title {
      font-family: var(--font-display);
      font-size: var(--text-xl);
      font-weight: var(--font-bold);
      color: var(--color-text-primary);
      margin: 0;
    }

    .dm-subtitle {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
    }

    .dm-close {
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
      flex-shrink: 0;
    }

    .dm-close:hover {
      border-color: var(--color-border-strong);
      color: var(--color-text-primary);
      background: var(--color-bg-section);
    }

    .dm-body {
      flex: 1;
      overflow-y: auto;
      padding: 28px;
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    /* ── Gallery in modal ── */
    .dm-gallery {
      width: 100%;
      display: flex;
      flex-direction: column;
      gap: 8px;
      flex-shrink: 0;
    }

    .dm-gallery-main {
      width: 100%;
      height: 220px;
      border-radius: var(--radius-xl);
      overflow: hidden;
      background: #f3f4f6;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }

    .dm-gallery-main img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: opacity 0.2s ease;
    }

    .dm-gallery-main .no-photo {
      color: var(--color-text-hint);
      font-size: 3rem;
    }

    .dm-gallery-thumbs {
      display: flex;
      gap: 8px;
      overflow-x: auto;
      padding-bottom: 4px;
      scrollbar-width: thin;
    }

    .dm-gallery-thumbs::-webkit-scrollbar {
      height: 4px;
    }

    .dm-gallery-thumbs::-webkit-scrollbar-track {
      background: transparent;
    }

    .dm-gallery-thumbs::-webkit-scrollbar-thumb {
      background: var(--color-border);
      border-radius: 99px;
    }

    .dm-thumb {
      width: 72px;
      height: 52px;
      border-radius: var(--radius-md);
      overflow: hidden;
      flex-shrink: 0;
      cursor: pointer;
      border: 2px solid transparent;
      transition: border-color 0.15s, transform 0.15s;
      background: #f3f4f6;
    }

    .dm-thumb:hover {
      transform: translateY(-2px);
      border-color: var(--color-primary);
    }

    .dm-thumb.active {
      border-color: var(--color-primary);
    }

    .dm-thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .dm-pills {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
    }

    .dm-cols {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
    }

    @media (max-width: 640px) {
      .dm-cols {
        grid-template-columns: 1fr;
      }
    }

    .dm-section-label {
      font-size: 11px;
      font-weight: var(--font-semibold);
      color: var(--color-text-disabled);
      text-transform: uppercase;
      letter-spacing: .07em;
      margin-bottom: 10px;
    }

    .dm-info-list {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .dm-info-row {
      display: flex;
      align-items: flex-start;
      gap: 10px;
      font-size: var(--text-sm);
    }

    .dm-info-row i {
      font-size: .95rem;
      color: var(--color-text-hint);
      width: 18px;
      flex-shrink: 0;
      margin-top: 1px;
    }

    .dm-info-row .lbl {
      color: var(--color-text-secondary);
      min-width: 100px;
      flex-shrink: 0;
    }

    .dm-info-row .val {
      color: var(--color-text-primary);
      font-weight: var(--font-medium);
    }

    .dm-stats {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
    }

    .dm-stat {
      display: flex;
      align-items: center;
      gap: 6px;
      padding: 5px 11px;
      border-radius: var(--radius-xl);
      background: var(--color-bg-section);
      border: 1px solid var(--color-border-subtle);
      font-size: 12.5px;
      color: var(--color-text-secondary);
    }

    .dm-stat i {
      font-size: .85rem;
      color: var(--color-text-hint);
    }

    .dm-stat strong {
      color: var(--color-text-primary);
    }

    .dm-host {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 14px 16px;
      background: var(--color-bg-section);
      border-radius: var(--radius-xl);
    }

    .dm-host-avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      overflow: hidden;
      background: var(--color-primary-light);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: var(--font-bold);
      font-size: var(--text-sm);
      color: var(--color-primary);
      flex-shrink: 0;
    }

    .dm-host-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .dm-host-name {
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      color: var(--color-text-primary);
    }

    .dm-host-sub {
      font-size: 11px;
      color: var(--color-text-secondary);
    }

    .dm-deskripsi {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
      line-height: 1.7;
      background: var(--color-bg-section);
      border-radius: var(--radius-xl);
      padding: 14px 16px;
      border-left: 3px solid var(--color-primary-light-active, #f3c4b0);
    }

    .dm-room-list {
      display: flex;
      flex-direction: column;
      gap: 7px;
    }

    .dm-room-item {
      display: flex;
      align-items: center;
      gap: 9px;
      padding: 8px 14px;
      border-radius: var(--radius-lg);
      background: var(--color-bg-section);
      border: 1px solid var(--color-border-subtle);
      font-size: var(--text-sm);
      color: var(--color-text-primary);
    }

    .dm-room-item i {
      color: var(--color-text-hint);
      font-size: .85rem;
    }

    .dm-amenity-list {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
    }

    .dm-amenity-chip {
      display: flex;
      align-items: center;
      gap: 5px;
      padding: 3px 10px;
      border-radius: var(--radius-full);
      background: var(--color-bg-section);
      border: 1px solid var(--color-border-subtle);
      font-size: 12px;
      color: var(--color-text-secondary);
    }

    .dm-amenity-chip i {
      font-size: .75rem;
    }

    .dm-policy-list {
      display: flex;
      flex-direction: column;
      gap: 9px;
    }

    .dm-policy-row {
      display: flex;
      align-items: center;
      gap: 9px;
      font-size: var(--text-sm);
    }

    .dm-policy-row i {
      color: var(--color-text-hint);
      font-size: .95rem;
      width: 18px;
      flex-shrink: 0;
    }

    .dm-policy-lbl {
      color: var(--color-text-secondary);
      min-width: 120px;
    }

    .dm-policy-val {
      color: var(--color-text-primary);
      font-weight: var(--font-medium);
    }

    .pol-ok {
      color: #16a34a;
    }

    .pol-no {
      color: #dc2626;
    }

    .dm-footer {
      padding: 18px 28px;
      border-top: 1.5px solid var(--color-border-subtle);
      flex-shrink: 0;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .dm-footer-status {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .dm-footer-label {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
    }

    .btn-toggle-status {
      padding: 10px 20px;
      border-radius: var(--radius-xl);
      border: 1.5px solid var(--color-border);
      background: transparent;
      color: var(--color-text-secondary);
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      cursor: pointer;
      font-family: var(--font-family);
      transition: all var(--transition-base);
      display: flex;
      align-items: center;
      gap: 7px;
    }

    .btn-toggle-status:hover {
      border-color: var(--color-border-strong);
      color: var(--color-text-primary);
      background: var(--color-bg-section);
    }

    .btn-toggle-status.state-nonaktifkan:hover {
      border-color: #dc2626;
      color: #dc2626;
      background: #fff1f0;
    }

    .btn-toggle-status.state-aktifkan {
      border-color: #16a34a;
      color: #16a34a;
    }

    .btn-toggle-status.state-aktifkan:hover {
      background: #f0fdf4;
    }

    .confirm-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, .40);
      z-index: calc(var(--z-modal) + 10);
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(3px);
    }

    .confirm-overlay.show {
      display: flex;
    }

    .confirm-box {
      background: var(--color-bg-card);
      border-radius: var(--radius-2xl);
      padding: 32px;
      max-width: 400px;
      width: 90%;
      box-shadow: 0 8px 40px rgba(0, 0, 0, .14);
      border: 1.5px solid var(--color-border-subtle);
      display: flex;
      flex-direction: column;
      gap: 24px;
    }

    .confirm-box h3 {
      font-family: var(--font-display);
      font-size: var(--text-xl);
      font-weight: var(--font-bold);
      color: var(--color-text-primary);
      margin: 0;
    }

    .confirm-box p {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
      margin: 8px 0 0;
      line-height: 1.65;
    }

    .confirm-actions {
      display: flex;
      gap: 10px;
    }

    .btn-batal {
      flex: 1;
      padding: 11px 20px;
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

    .btn-batal:hover {
      border-color: var(--color-border-strong);
      color: var(--color-text-primary);
      background: var(--color-bg-section);
    }

    .btn-hapus-confirm {
      flex: 1;
      padding: 11px 20px;
      border-radius: var(--radius-xl);
      border: none;
      background: var(--color-primary);
      color: #fff;
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      cursor: pointer;
      font-family: var(--font-family);
      transition: all var(--transition-base);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-hapus-confirm:hover {
      background: var(--color-primary-hover);
    }

    .btn-hapus-confirm:disabled {
      opacity: .6;
      cursor: not-allowed;
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
          <img alt="Brand Name Teman Singgah" class="logo-name"
            src="/teman_singgah/assets/logo/label_temansinggah.svg" />
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title">Halaman Utama</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/dashboard.php"><i
              class="ph-bold ph-squares-four"></i>Dashboard</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Manajemen</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/users.php"><i class="ph-bold ph-users"></i>Pengguna</a>
          <a class="nav-item active" href="/teman_singgah/admin/pages/listings.php"><i
              class="ph-bold ph-house"></i>Properti</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/reservations.php"><i
              class="ph-bold ph-calendar-check"></i>Reservasi</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/transactions.php"><i
              class="ph-bold ph-currency-circle-dollar"></i>Transaksi</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/promos.php"><i class="ph-bold ph-tag"></i>Promo &
            Deals</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Moderasi</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/reviews.php"><i class="ph-bold ph-star"></i>Ulasan</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/reports.php"><i class="ph-bold ph-flag"></i>Laporan</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Keuangan</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/payouts.php"><i
              class="ph-bold ph-money"></i>Pembayaran</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Sistem</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/settings.php"><i
              class="ph-bold ph-gear"></i>Pengaturan</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/logs.php"><i class="ph-bold ph-notepad"></i>Aktivitas</a>
        </div>
      </nav>
    </aside>

    <div class="main-container">
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">Manajemen Properti</h1>
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
              <input type="search" id="adminSearch" class="table-search-input"
                placeholder="Cari nama listing, host, atau lokasi..." />
            </div>
          </div>
        </div>

        <div class="filter-container">
          <div class="filter-group" id="filterGroup">
            <button class="filter-item active" data-filter="all">Semua</button>
            <button class="filter-item" data-filter="status:aktif">Aktif</button>
            <button class="filter-item" data-filter="status:nonaktif">Nonaktif</button>
            <button class="filter-item" data-filter="status:draft">Draft</button>
            <button class="filter-item" data-filter="booking:instan">Booking Instan</button>
            <button class="filter-item" data-filter="booking:permintaan">Perlu Konfirmasi</button>
          </div>
          <div class="sort-dropdown">
            <button class="sort-button" id="sortToggleBtn">
              <i class="ph-bold ph-faders-horizontal"></i>
              <span id="sortLabel">Urutkan: Terbaru</span>
              <i class="ph-bold ph-caret-down"></i>
            </button>
            <div class="sort-menu" id="sortMenu">
              <div class="sort-menu-item active" onclick="selectSort('date_newest','Terbaru',this)"><i
                  class="ph-bold ph-calendar"></i> Tanggal Terbaru</div>
              <div class="sort-menu-item" onclick="selectSort('date_oldest','Terlama',this)"><i
                  class="ph-bold ph-calendar"></i> Tanggal Terlama</div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('name_asc','Nama A-Z',this)"><i
                  class="ph-bold ph-sort-ascending"></i> Nama A-Z</div>
              <div class="sort-menu-item" onclick="selectSort('name_desc','Nama Z-A',this)"><i
                  class="ph-bold ph-sort-descending"></i> Nama Z-A</div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('price_high','Harga Tertinggi',this)"><i
                  class="ph-bold ph-trend-up"></i> Harga Tertinggi</div>
              <div class="sort-menu-item" onclick="selectSort('price_low','Harga Terendah',this)"><i
                  class="ph-bold ph-trend-down"></i> Harga Terendah</div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('rating_high','Rating Tertinggi',this)"><i
                  class="ph-bold ph-star"></i> Rating Tertinggi</div>
              <div class="sort-menu-item" onclick="selectSort('booking_most','Booking Terbanyak',this)"><i
                  class="ph-bold ph-calendar-check"></i> Booking Terbanyak</div>
            </div>
          </div>
        </div>

        <section class="table-section">
          <div class="table-container">
            <table class="managed-table" id="listingTable">
              <thead>
                <tr>
                  <th class="col-num">No.</th>
                  <th>Nama Listing</th>
                  <th>Host</th>
                  <th>Lokasi</th>
                  <th>Kamar</th>
                  <th>Harga/Malam</th>
                  <th>Tipe Properti</th>
                  <th>Tipe Privasi</th>
                  <th>Tipe Booking</th>
                  <th>Total Booking</th>
                  <th>Rating</th>
                  <th>Status</th>
                  <th>Tgl Dibuat</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)):
                  $foto_src = !empty($row['foto_cover'])
                    ? htmlspecialchars($row['foto_cover'])
                    : null;
                  $host_photo_path = !empty($row['host_photo']) &&
                    file_exists($_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $row['host_photo'])
                    ? '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($row['host_photo'])
                    : null;
                  $host_initial = strtoupper(mb_substr($row['host_nama'], 0, 2));
                  $status = strtolower($row['status']);
                  $status_class = match ($status) { 'aktif' => 'success', 'nonaktif' => 'danger', 'draft' => 'neutral', default => 'neutral'};
                  $tipe_booking = strtolower($row['tipe_booking']);
                  $booking_pcls = $tipe_booking === 'instan' ? 'pill-instan' : 'pill-permintaan';
                  $booking_lbl = $tipe_booking === 'instan' ? 'Instan' : 'Konfirmasi';
                  $tipe_privasi = strtolower($row['tipe_privasi'] ?? '');
                  $privasi_lbl = $tipe_privasi === 'seluruh' ? 'Seluruh Tempat' : 'Per Kamar';
                  $tipe_properti = ucfirst(strtolower($row['tipe_properti'] ?? '-'));
                  $harga = 'Rp ' . number_format($row['harga_malam'], 0, ',', '.');
                  $rating = $row['rating_avg'] ?: null;
                  $tgl = date('d M Y', strtotime($row['dibuat_pada']));
                  $listing_rooms = $rooms_map[$row['id']] ?? [];
                  $kamar_show = array_slice($listing_rooms, 0, 2);
                  $kamar_lebih = count($listing_rooms) - count($kamar_show);

                  // Ambil semua foto listing untuk gallery di modal
                  $listing_photos_stmt = mysqli_prepare($koneksi, "SELECT nama_file, adalah_cover FROM listing_photos WHERE listing_id = ? ORDER BY adalah_cover DESC, urutan ASC");
                  mysqli_stmt_bind_param($listing_photos_stmt, 'i', $row['id']);
                  mysqli_stmt_execute($listing_photos_stmt);
                  $listing_photos_result = mysqli_stmt_get_result($listing_photos_stmt);
                  $listing_photos_all = [];
                  while ($lp = mysqli_fetch_assoc($listing_photos_result)) {
                    $listing_photos_all[] = $lp['nama_file'];
                  }
                  mysqli_stmt_close($listing_photos_stmt);

                  $panel_data = htmlspecialchars(json_encode([
                    'id' => $row['id'],
                    'judul' => $row['judul'],
                    'tipe_properti' => $row['tipe_properti'],
                    'tipe_privasi' => $row['tipe_privasi'],
                    'tipe_booking' => $row['tipe_booking'],
                    'lokasi' => $row['lokasi'],
                    'harga_malam' => $row['harga_malam'],
                    'max_tamu' => $row['max_tamu'],
                    'kamar_tidur' => $row['kamar_tidur'],
                    'tempat_tidur' => $row['tempat_tidur'],
                    'kamar_mandi' => $row['kamar_mandi'],
                    'min_malam' => $row['min_malam'],
                    'jam_checkin' => substr($row['jam_checkin'] ?? '14:00:00', 0, 5),
                    'jam_checkout' => substr($row['jam_checkout'] ?? '12:00:00', 0, 5),
                    'status' => $row['status'],
                    'dibuat_pada' => $row['dibuat_pada'],
                    'deskripsi' => $row['deskripsi'] ?? '',
                    'kebijakan_pembatalan' => $row['kebijakan_pembatalan'] ?? '',
                    'boleh_hewan' => $row['boleh_hewan'],
                    'boleh_merokok' => $row['boleh_merokok'],
                    'boleh_anak' => $row['boleh_anak'],
                    'total_booking' => $row['total_booking'],
                    'rating_avg' => $rating,
                    'host_nama' => $row['host_nama'],
                    'host_photo' => $host_photo_path,
                    'foto_cover' => $foto_src,
                    'all_photos' => $listing_photos_all,   // ← semua foto
                    'rooms' => $rooms_map[$row['id']] ?? [],
                    'amenities' => $amenities_map[$row['id']] ?? [],
                  ]), ENT_QUOTES);
                  ?>
                  <tr data-status="<?= htmlspecialchars($status) ?>" data-booking="<?= htmlspecialchars($tipe_booking) ?>"
                    data-judul="<?= htmlspecialchars($row['judul']) ?>"
                    data-host="<?= htmlspecialchars($row['host_nama']) ?>"
                    data-lokasi="<?= htmlspecialchars($row['lokasi']) ?>" data-harga="<?= (int) $row['harga_malam'] ?>"
                    data-rating="<?= $rating ?? 0 ?>" data-booking-count="<?= (int) $row['total_booking'] ?>"
                    data-tanggal="<?= htmlspecialchars($row['dibuat_pada']) ?>" data-panel="<?= $panel_data ?>">

                    <td class="col-num"><?= $no++ ?></td>

                    <td>
                      <div class="table-cell">
                        <?php if ($foto_src): ?>
                          <img src="<?= $foto_src ?>" class="table-thumbnail" alt=""
                            onerror="this.outerHTML='<div class=\'table-thumbnail\' style=\'background:#f3f4f6;display:flex;align-items:center;justify-content:center;\'><i class=\'ph-bold ph-image\' style=\'color:#d1d5db;font-size:1.2rem;\'></i></div>'" />
                        <?php else: ?>
                          <div class="table-thumbnail"
                            style="background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                            <i class="ph-bold ph-image" style="color:#d1d5db;font-size:1.2rem;"></i>
                          </div>
                        <?php endif; ?>
                        <h3 class="table-name"><?= htmlspecialchars($row['judul']) ?></h3>
                      </div>
                    </td>

                    <td>
                      <div class="table-cell">
                        <?php if ($host_photo_path): ?>
                          <div class="table-avatar" style="padding:0;overflow:hidden;">
                            <img src="<?= $host_photo_path ?>" alt=""
                              style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                              onerror="this.parentElement.innerHTML='<?= addslashes($host_initial) ?>'" />
                          </div>
                        <?php else: ?>
                          <div class="table-avatar"><?= $host_initial ?></div>
                        <?php endif; ?>
                        <h3 class="table-name"><?= htmlspecialchars($row['host_nama']) ?></h3>
                      </div>
                    </td>

                    <td>
                      <?php
                      $lf = htmlspecialchars($row['lokasi']);
                      $ls = mb_strlen($row['lokasi']) > 30 ? htmlspecialchars(mb_substr($row['lokasi'], 0, 30)) . '...' : $lf;
                      ?>
                      <span title="<?= $lf ?>"><?= $ls ?></span>
                    </td>

                    <td>
                      <?php if (!empty($kamar_show)): ?>
                        <div class="kamar-chips">
                          <?php foreach ($kamar_show as $kn): ?><span
                              class="kamar-chip"><?= htmlspecialchars($kn) ?></span><?php endforeach; ?>
                          <?php if ($kamar_lebih > 0): ?><span class="kamar-chip kamar-chip-more">+<?= $kamar_lebih ?>
                              lainnya</span><?php endif; ?>
                        </div>
                      <?php else: ?><span style="font-size:12px;color:var(--color-text-hint);">-</span><?php endif; ?>
                    </td>

                    <td><?= $harga ?></td>

                    <td><span class="pill pill-properti"><?= htmlspecialchars($tipe_properti) ?></span></td>

                    <td><span class="pill pill-privasi"><?= htmlspecialchars($privasi_lbl) ?></span></td>

                    <td><span class="pill <?= $booking_pcls ?>"><?= $booking_lbl ?></span></td>

                    <td>
                      <div class="booking-count"><?= (int) $row['total_booking'] ?> <span>booking</span></div>
                    </td>

                    <td>
                      <?php if ($rating): ?>
                        <div class="rating-cell"><i class="ph-fill ph-star"></i><?= $rating ?></div>
                      <?php else: ?>
                        <div class="rating-cell"><span class="no-rating">-</span></div>
                      <?php endif; ?>
                    </td>

                    <td>
                      <span class="table-badge <?= $status_class ?>">
                        <span class="badge-dot"></span><?= ucfirst($row['status']) ?>
                      </span>
                    </td>

                    <td><?= $tgl ?></td>

                    <td>
                      <div class="action-group">
                        <button class="action-button info btn-detail" title="Lihat Detail">
                          <i class="ph-bold ph-eye"></i>
                        </button>
                        <button class="action-button error btn-hapus" title="Hapus Listing" data-id="<?= $row['id'] ?>"
                          data-judul="<?= htmlspecialchars($row['judul']) ?>">
                          <i class="ph-bold ph-trash"></i>
                        </button>
                      </div>
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

  <!-- ── DETAIL MODAL ── -->
  <div class="detail-overlay" id="detailOverlay">
    <div class="detail-modal">
      <div class="dm-header">
        <div class="dm-header-left">
          <h2 class="dm-title" id="dmJudul"></h2>
          <span class="dm-subtitle" id="dmSubtitle"></span>
        </div>
        <button class="dm-close" id="dmClose" type="button"><i class="ph-bold ph-x"></i></button>
      </div>

      <div class="dm-body">

        <!-- Gallery: main image + thumbnails -->
        <div class="dm-gallery" id="dmGallery">
          <div class="dm-gallery-main" id="dmGalleryMain">
            <i class="ph-bold ph-image no-photo"></i>
          </div>
          <div class="dm-gallery-thumbs" id="dmGalleryThumbs"></div>
        </div>

        <div class="dm-pills" id="dmPills"></div>

        <div class="dm-cols">

          <div style="display:flex;flex-direction:column;gap:20px;">

            <div>
              <div class="dm-section-label">Informasi Umum</div>
              <div class="dm-info-list">
                <div class="dm-info-row"><i class="ph-bold ph-map-pin"></i><span class="lbl">Lokasi</span><span
                    class="val" id="dmLokasi"></span></div>
                <div class="dm-info-row"><i class="ph-bold ph-currency-circle-dollar"></i><span
                    class="lbl">Harga/malam</span><span class="val" id="dmHarga"></span></div>
                <div class="dm-info-row"><i class="ph-bold ph-buildings"></i><span class="lbl">Tipe properti</span><span
                    class="val" id="dmTipeProperti"></span></div>
                <div class="dm-info-row"><i class="ph-bold ph-door"></i><span class="lbl">Tipe privasi</span><span
                    class="val" id="dmTipePrivasi"></span></div>
                <div class="dm-info-row"><i class="ph-bold ph-calendar-check"></i><span class="lbl">Tipe
                    booking</span><span class="val" id="dmBooking"></span></div>
                <div class="dm-info-row"><i class="ph-bold ph-calendar"></i><span class="lbl">Tgl dibuat</span><span
                    class="val" id="dmTanggal"></span></div>
              </div>
            </div>

            <div>
              <div class="dm-section-label">Host</div>
              <div class="dm-host">
                <div class="dm-host-avatar" id="dmHostAvatar"></div>
                <div>
                  <div class="dm-host-name" id="dmHostNama"></div>
                  <div class="dm-host-sub">Host properti ini</div>
                </div>
              </div>
            </div>

            <div id="dmDeskripsiWrap">
              <div class="dm-section-label">Deskripsi</div>
              <div class="dm-deskripsi" id="dmDeskripsi"></div>
            </div>

            <div id="dmKamarWrap">
              <div class="dm-section-label">Pilihan Kamar</div>
              <div class="dm-room-list" id="dmKamarList"></div>
            </div>

          </div>

          <div style="display:flex;flex-direction:column;gap:20px;">

            <div>
              <div class="dm-section-label">Statistik</div>
              <div class="dm-stats">
                <div class="dm-stat"><i class="ph-bold ph-users"></i><strong id="dmMaxTamu"></strong> tamu</div>
                <div class="dm-stat"><i class="ph-bold ph-bed"></i><strong id="dmKamarTidur"></strong> KT</div>
                <div class="dm-stat"><i class="ph-bold ph-bathtub"></i><strong id="dmKamarMandi"></strong> KM</div>
                <div class="dm-stat"><i class="ph-bold ph-moon"></i>min <strong id="dmMinMalam"></strong> mlm</div>
                <div class="dm-stat"><i class="ph-bold ph-calendar-check"></i><strong id="dmTotalBooking"></strong>
                  booking</div>
                <div class="dm-stat" id="dmRatingChip" style="display:none;"><i class="ph-fill ph-star"
                    style="color:#f59e0b;"></i><strong id="dmRating"></strong></div>
              </div>
            </div>

            <div>
              <div class="dm-section-label">Kebijakan</div>
              <div class="dm-policy-list">
                <div class="dm-policy-row"><i class="ph-bold ph-clock"></i><span
                    class="dm-policy-lbl">Check-in</span><span class="dm-policy-val" id="dmCheckin"></span></div>
                <div class="dm-policy-row"><i class="ph-bold ph-clock"></i><span
                    class="dm-policy-lbl">Check-out</span><span class="dm-policy-val" id="dmCheckout"></span></div>
                <div class="dm-policy-row"><i class="ph-bold ph-prohibit"></i><span
                    class="dm-policy-lbl">Pembatalan</span><span class="dm-policy-val" id="dmKebijakan"></span></div>
                <div class="dm-policy-row"><i class="ph-bold ph-paw-print"></i><span class="dm-policy-lbl">Hewan
                    peliharaan</span><span class="dm-policy-val" id="dmHewan"></span></div>
                <div class="dm-policy-row"><i class="ph-bold ph-cigarette"></i><span
                    class="dm-policy-lbl">Merokok</span><span class="dm-policy-val" id="dmRokok"></span></div>
                <div class="dm-policy-row"><i class="ph-bold ph-baby"></i><span
                    class="dm-policy-lbl">Anak-anak</span><span class="dm-policy-val" id="dmAnak"></span></div>
              </div>
            </div>

            <div id="dmFasilitasWrap">
              <div class="dm-section-label">Fasilitas</div>
              <div class="dm-amenity-list" id="dmFasilitas"></div>
            </div>

          </div>
        </div>
      </div>

      <div class="dm-footer">
        <div class="dm-footer-status">
          <span class="dm-footer-label">Status saat ini:</span>
          <div id="dmStatusBadge"></div>
        </div>
        <button class="btn-toggle-status" id="btnToggleStatus" type="button"></button>
      </div>
    </div>
  </div>

  <!-- ── CONFIRM HAPUS ── -->
  <div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
      <div>
        <h3>Hapus Listing?</h3>
        <p id="confirmMsg"></p>
      </div>
      <div class="confirm-actions">
        <button class="btn-batal" id="btnBatal" type="button">Batal</button>
        <button class="btn-hapus-confirm" id="btnHapusConfirm" type="button">
          <i class="ph-bold ph-trash"></i> Ya, Hapus Permanen
        </button>
      </div>
    </div>
  </div>

  <script>
    // ── Filter ────────────────────────────────────────────
    document.querySelectorAll(".filter-item").forEach(btn => {
      btn.addEventListener("click", () => {
        document.querySelectorAll(".filter-item").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        applyFilter();
      });
    });
    function applyFilter() {
      const table = document.querySelector(".managed-table"); if (!table) return;
      const f = document.querySelector(".filter-item.active")?.dataset.filter || "all";
      table.querySelectorAll("tbody tr").forEach(row => {
        let show = true;
        if (f !== "all") { const [k, v] = f.split(":"); if (k === "status") show = row.dataset.status === v; if (k === "booking") show = row.dataset.booking === v; }
        show ? delete row.dataset.hiddenFilter : (row.dataset.hiddenFilter = "1");
        rebuildHidden(row);
      });
      resetAndPaginate(table);
    }
    function rebuildHidden(row) { (row.dataset.hiddenFilter || row.dataset.hiddenSearch) ? (row.dataset.hidden = "1") : delete row.dataset.hidden; }

    // ── Sort ──────────────────────────────────────────────
    const sortToggleBtn = document.getElementById("sortToggleBtn");
    const sortMenu = document.getElementById("sortMenu");
    sortToggleBtn.addEventListener("click", e => { e.stopPropagation(); sortMenu.classList.toggle("open"); });
    document.addEventListener("click", e => { if (!sortMenu.contains(e.target) && e.target !== sortToggleBtn) sortMenu.classList.remove("open"); });
    function selectSort(by, label, el) {
      document.getElementById("sortLabel").textContent = "Urutkan: " + label;
      document.querySelectorAll(".sort-menu-item").forEach(i => i.classList.remove("active")); el?.classList.add("active");
      sortMenu.classList.remove("open"); applySort(by);
    }
    function applySort(by) {
      const table = document.querySelector(".managed-table"); const tbody = table?.querySelector("tbody"); if (!tbody) return;
      const rows = Array.from(tbody.querySelectorAll("tr"));
      rows.sort((a, b) => {
        if (by === "date_newest") return new Date(b.dataset.tanggal || 0) - new Date(a.dataset.tanggal || 0);
        if (by === "date_oldest") return new Date(a.dataset.tanggal || 0) - new Date(b.dataset.tanggal || 0);
        if (by === "name_asc") return (a.dataset.judul || "").localeCompare(b.dataset.judul || "", "id-ID");
        if (by === "name_desc") return (b.dataset.judul || "").localeCompare(a.dataset.judul || "", "id-ID");
        if (by === "price_high") return parseInt(b.dataset.harga || 0) - parseInt(a.dataset.harga || 0);
        if (by === "price_low") return parseInt(a.dataset.harga || 0) - parseInt(b.dataset.harga || 0);
        if (by === "rating_high") return parseFloat(b.dataset.rating || 0) - parseFloat(a.dataset.rating || 0);
        if (by === "booking_most") return parseInt(b.dataset.bookingCount || 0) - parseInt(a.dataset.bookingCount || 0);
        return 0;
      });
      rows.forEach(r => tbody.appendChild(r)); resetAndPaginate(table);
    }

    // ── Search ────────────────────────────────────────────
    document.getElementById("adminSearch")?.addEventListener("input", function () {
      const q = this.value.trim().toLowerCase(); const table = document.querySelector(".managed-table"); if (!table) return;
      table.querySelectorAll("tbody tr").forEach(row => {
        const match = !q || (row.dataset.judul || "").toLowerCase().includes(q) || (row.dataset.host || "").toLowerCase().includes(q) || (row.dataset.lokasi || "").toLowerCase().includes(q);
        match ? delete row.dataset.hiddenSearch : (row.dataset.hiddenSearch = "1"); rebuildHidden(row);
      }); resetAndPaginate(table);
    });

    // ── Pagination ────────────────────────────────────────
    const RPP = 10;
    function resetAndPaginate(t) { t._p = 1; paginate(t); }
    function paginate(table) {
      const page = table._p || 1, all = Array.from(table.querySelectorAll("tbody tr")), vis = all.filter(r => !r.dataset.hidden);
      const total = vis.length, tp = Math.max(1, Math.ceil(total / RPP)), safe = Math.min(page, tp);
      table._p = safe; const s = (safe - 1) * RPP, e = s + RPP;
      all.forEach(r => { r.style.display = r.dataset.hidden ? "none" : ""; });
      vis.forEach((r, i) => { r.style.display = (i >= s && i < e) ? "" : "none"; });
      let c = 0; all.forEach(row => { const cell = row.querySelector(".col-num"); if (!cell) return; cell.textContent = row.style.display === "none" ? "" : ++c; });
      renderPag(table, safe, tp, total);
    }
    function renderPag(table, page, tp, total) {
      const sec = table.closest(".table-section"), info = sec?.querySelector(".pagination-info"), ctrl = sec?.querySelector(".pagination-controls");
      if (!info || !ctrl) return;
      const s = total === 0 ? 0 : (page - 1) * RPP + 1, e = Math.min(page * RPP, total);
      info.textContent = total === 0 ? "Tidak ada data" : `${s}-${e} dari ${total} listing`;
      ctrl.innerHTML = "";
      const mk = (html, dis, cb) => { const b = document.createElement("button"); b.className = "page-btn nav-btn"; b.innerHTML = html; b.disabled = dis; if (!dis) b.addEventListener("click", cb); return b; };
      ctrl.appendChild(mk('<i class="ph-bold ph-caret-left"></i>', page <= 1, () => { table._p = page - 1; paginate(table); }));
      pgList(page, tp).forEach(p => {
        if (p === "...") { const sp = document.createElement("span"); sp.className = "page-ellipsis"; sp.textContent = "..."; ctrl.appendChild(sp); }
        else { const b = document.createElement("button"); b.className = "page-btn" + (p === page ? " active" : ""); b.textContent = p; b.addEventListener("click", () => { table._p = p; paginate(table); }); ctrl.appendChild(b); }
      });
      ctrl.appendChild(mk('<i class="ph-bold ph-caret-right"></i>', page >= tp, () => { table._p = page + 1; paginate(table); }));
    }
    function pgList(cur, tot) { if (tot <= 7) return Array.from({ length: tot }, (_, i) => i + 1); const p = [1]; if (cur > 3) p.push("..."); for (let i = Math.max(2, cur - 1); i <= Math.min(tot - 1, cur + 1); i++)p.push(i); if (cur < tot - 2) p.push("..."); p.push(tot); return p; }

    // ── Hapus listing ─────────────────────────────────────
    let pendingId = null;
    const confirmOverlay = document.getElementById("confirmOverlay");
    const btnBatal = document.getElementById("btnBatal");
    const btnHapusConfirm = document.getElementById("btnHapusConfirm");

    function showConfirmHapus(id, judul) {
      pendingId = id;
      document.getElementById("confirmMsg").textContent = `Listing "${judul}" akan dihapus secara permanen beserta semua foto dan data terkait. Tindakan ini tidak bisa dibatalkan.`;
      confirmOverlay.classList.add("show");
    }
    btnBatal.addEventListener("click", () => { confirmOverlay.classList.remove("show"); pendingId = null; });
    confirmOverlay.addEventListener("click", e => { if (e.target === confirmOverlay) { confirmOverlay.classList.remove("show"); pendingId = null; } });
    btnHapusConfirm.addEventListener("click", () => {
      if (!pendingId) return;
      btnHapusConfirm.disabled = true;
      btnHapusConfirm.innerHTML = '<i class="ph-bold ph-spinner"></i> Menghapus...';
      fetch("/teman_singgah/admin/pages/delete_listing.php", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ id: pendingId }) })
        .then(r => r.json())
        .then(d => {
          if (d.status === "ok") location.reload();
          else { alert("Gagal: " + d.message); btnHapusConfirm.disabled = false; btnHapusConfirm.innerHTML = '<i class="ph-bold ph-trash"></i> Ya, Hapus Permanen'; }
        })
        .catch(() => { alert("Koneksi bermasalah."); btnHapusConfirm.disabled = false; btnHapusConfirm.innerHTML = '<i class="ph-bold ph-trash"></i> Ya, Hapus Permanen'; });
    });

    // ── Detail modal ──────────────────────────────────────
    const detailOverlay = document.getElementById("detailOverlay");
    const btnClose = document.getElementById("dmClose");
    const btnToggle = document.getElementById("btnToggleStatus");
    let activeId = null;
    let activeStatus = null;
    let activeRow = null;

    const FALLBACK_IMG = 'https://placehold.co/800x400/f3f4f6/9ca3af?text=Tidak+Ada+Foto';

    const KEBmap = {
      fleksibel: 'Gratis hingga 24 jam sebelum check-in',
      moderat: 'Refund 50% jika dibatalkan 5 hari sebelum check-in',
      ketat: 'Tidak ada refund setelah konfirmasi',
    };

    function esc(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function ucf(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
    function rupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); }
    function tgl(s) { const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'], d = new Date(s); return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`; }
    function trunc(s, max) { if (!s) return '-'; return s.length > max ? s.substring(0, max) + '...' : s; }

    // ── Gallery helpers ────────────────────────────────────
    function buildGallery(photos) {
      const mainEl = document.getElementById('dmGalleryMain');
      const thumbsEl = document.getElementById('dmGalleryThumbs');
      thumbsEl.innerHTML = '';

      if (!photos || photos.length === 0) {
        mainEl.innerHTML = '<i class="ph-bold ph-image no-photo"></i>';
        return;
      }

      // Render main image
      function setMain(src, thumbEl) {
        const img = mainEl.querySelector('img');
        if (img) {
          img.style.opacity = '0';
          setTimeout(() => { img.src = src; img.style.opacity = '1'; }, 150);
        } else {
          mainEl.innerHTML = `<img src="${src}" alt="" style="transition:opacity .2s ease;"
            onerror="this.src='${FALLBACK_IMG}'" />`;
        }
        // Aktifkan thumb yang dipilih
        thumbsEl.querySelectorAll('.dm-thumb').forEach(t => t.classList.remove('active'));
        if (thumbEl) thumbEl.classList.add('active');
      }

      // Tampilkan foto pertama sebagai main
      mainEl.innerHTML = `<img src="${photos[0]}" alt="" style="transition:opacity .2s ease;"
        onerror="this.src='${FALLBACK_IMG}'" />`;

      // Buat thumbnails
      photos.forEach((src, idx) => {
        const div = document.createElement('div');
        div.className = 'dm-thumb' + (idx === 0 ? ' active' : '');
        div.innerHTML = `<img src="${src}" alt="Foto ${idx + 1}"
          onerror="this.src='${FALLBACK_IMG}'" />`;
        div.addEventListener('click', () => setMain(src, div));
        thumbsEl.appendChild(div);
      });

      // Sembunyikan thumbs row jika hanya 1 foto
      thumbsEl.style.display = photos.length > 1 ? '' : 'none';
    }

    function updateToggleBtn(status) {
      const isAktif = status === 'aktif';
      btnToggle.innerHTML = isAktif
        ? '<i class="ph-bold ph-prohibit"></i> Nonaktifkan'
        : '<i class="ph-bold ph-check-circle"></i> Aktifkan';
      btnToggle.className = 'btn-toggle-status ' + (isAktif ? 'state-nonaktifkan' : 'state-aktifkan');
    }

    function updateStatusBadge(status) {
      const map = { aktif: 'success', nonaktif: 'danger', draft: 'neutral' };
      document.getElementById("dmStatusBadge").innerHTML =
        `<span class="table-badge ${map[status] || 'neutral'}"><span class="badge-dot"></span>${ucf(status)}</span>`;
    }

    function openDetail(row) {
      const d = JSON.parse(row.dataset.panel);
      activeId = d.id;
      activeStatus = d.status.toLowerCase();
      activeRow = row;

      // Gallery — gunakan all_photos jika ada, fallback ke foto_cover saja
      const photos = (d.all_photos && d.all_photos.length > 0)
        ? d.all_photos
        : (d.foto_cover ? [d.foto_cover] : []);
      buildGallery(photos);

      document.getElementById("dmJudul").textContent = d.judul;
      document.getElementById("dmSubtitle").textContent = d.lokasi;

      document.getElementById("dmPills").innerHTML = `
        <span class="pill pill-properti">${ucf(d.tipe_properti)}</span>
        <span class="pill pill-privasi">${d.tipe_privasi === 'seluruh' ? 'Seluruh Tempat' : 'Per Kamar'}</span>
        <span class="pill ${d.tipe_booking === 'instan' ? 'pill-instan' : 'pill-permintaan'}">${d.tipe_booking === 'instan' ? 'Instan' : 'Konfirmasi'}</span>
      `;

      const av = document.getElementById("dmHostAvatar");
      if (d.host_photo) {
        av.innerHTML = `<img src="${d.host_photo}" alt=""
          onerror="this.parentElement.textContent='${(d.host_nama || 'H').substring(0, 2).toUpperCase()}'" />`;
      } else {
        av.textContent = (d.host_nama || 'H').substring(0, 2).toUpperCase();
      }
      document.getElementById("dmHostNama").textContent = d.host_nama;

      document.getElementById("dmLokasi").textContent = d.lokasi;
      document.getElementById("dmHarga").textContent = rupiah(d.harga_malam);
      document.getElementById("dmTipeProperti").textContent = ucf(d.tipe_properti);
      document.getElementById("dmTipePrivasi").textContent = d.tipe_privasi === 'seluruh' ? 'Seluruh Tempat' : 'Per Kamar';
      document.getElementById("dmBooking").textContent = d.tipe_booking === 'instan' ? 'Booking Instan' : 'Perlu Konfirmasi';
      document.getElementById("dmTanggal").textContent = tgl(d.dibuat_pada);

      document.getElementById("dmMaxTamu").textContent = d.max_tamu;
      document.getElementById("dmKamarTidur").textContent = d.kamar_tidur;
      document.getElementById("dmKamarMandi").textContent = d.kamar_mandi;
      document.getElementById("dmMinMalam").textContent = d.min_malam;
      document.getElementById("dmTotalBooking").textContent = d.total_booking;
      const rc = document.getElementById("dmRatingChip");
      if (d.rating_avg) { rc.style.display = ''; document.getElementById("dmRating").textContent = d.rating_avg; } else rc.style.display = 'none';

      const dw = document.getElementById("dmDeskripsiWrap");
      if (d.deskripsi) { dw.style.display = ''; document.getElementById("dmDeskripsi").textContent = trunc(d.deskripsi, 300); } else dw.style.display = 'none';

      const kw = document.getElementById("dmKamarWrap");
      if (d.rooms && d.rooms.length > 0) {
        kw.style.display = '';
        document.getElementById("dmKamarList").innerHTML = d.rooms.map(n => `<div class="dm-room-item"><i class="ph-bold ph-door"></i>${esc(n)}</div>`).join('');
      } else kw.style.display = 'none';

      const fw = document.getElementById("dmFasilitasWrap");
      if (d.amenities && d.amenities.length > 0) {
        fw.style.display = '';
        document.getElementById("dmFasilitas").innerHTML = d.amenities.map(a => `<span class="dm-amenity-chip"><i class="ph-bold ph-check-circle"></i>${esc(a)}</span>`).join('');
      } else fw.style.display = 'none';

      document.getElementById("dmCheckin").textContent = 'Dari pukul ' + d.jam_checkin + ' WIB';
      document.getElementById("dmCheckout").textContent = 'Sebelum pukul ' + d.jam_checkout + ' WIB';
      document.getElementById("dmKebijakan").textContent = KEBmap[d.kebijakan_pembatalan] || ucf(d.kebijakan_pembatalan || '-');

      const he = document.getElementById("dmHewan"); he.textContent = d.boleh_hewan ? 'Diperbolehkan' : 'Tidak diperbolehkan'; he.className = 'dm-policy-val ' + (d.boleh_hewan ? 'pol-ok' : 'pol-no');
      const re = document.getElementById("dmRokok"); re.textContent = d.boleh_merokok ? 'Diperbolehkan' : 'Tidak diperbolehkan'; re.className = 'dm-policy-val ' + (d.boleh_merokok ? 'pol-ok' : 'pol-no');
      const ae = document.getElementById("dmAnak"); ae.textContent = d.boleh_anak ? 'Selamat datang' : 'Tidak diperbolehkan'; ae.className = 'dm-policy-val ' + (d.boleh_anak ? 'pol-ok' : 'pol-no');

      updateStatusBadge(activeStatus);
      updateToggleBtn(activeStatus);

      detailOverlay.classList.add("show");
    }

    // ── Toggle status ─────────────────────────────────────
    btnToggle.addEventListener("click", () => {
      if (!activeId) return;
      const newStatus = activeStatus === 'aktif' ? 'nonaktif' : 'aktif';
      const aksi = activeStatus === 'aktif' ? 'menonaktifkan' : 'mengaktifkan';
      if (!confirm(`Yakin ingin ${aksi} listing "${document.getElementById('dmJudul').textContent}"?`)) return;

      btnToggle.disabled = true;
      const fd = new FormData();
      fd.append('action', 'toggle_status');
      fd.append('listing_id', activeId);
      fd.append('new_status', newStatus);

      fetch(window.location.pathname, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
          if (data.success) {
            activeStatus = data.new_status;
            if (activeRow) {
              activeRow.dataset.status = activeStatus;
              // Update panel JSON di data-attribute
              try {
                const panelObj = JSON.parse(activeRow.dataset.panel);
                panelObj.status = activeStatus;
                activeRow.dataset.panel = JSON.stringify(panelObj);
              } catch (e) { /* abaikan jika parse gagal */ }
              // Update badge di baris tabel
              const badge = activeRow.querySelector('td .table-badge');
              if (badge) {
                const map = { aktif: 'success', nonaktif: 'danger', draft: 'neutral' };
                badge.className = `table-badge ${map[activeStatus] || 'neutral'}`;
                badge.innerHTML = `<span class="badge-dot"></span>${ucf(activeStatus)}`;
              }
            }
            updateStatusBadge(activeStatus);
            updateToggleBtn(activeStatus);
          } else {
            alert('Gagal: ' + (data.message || 'Terjadi kesalahan.'));
          }
        })
        .catch(() => alert('Koneksi bermasalah.'))
        .finally(() => { btnToggle.disabled = false; });
    });

    // ── Delegasi klik tabel ───────────────────────────────
    document.querySelector("#listingTable tbody").addEventListener("click", e => {
      const bD = e.target.closest(".btn-detail"); if (bD) { openDetail(bD.closest("tr")); return; }
      const bH = e.target.closest(".btn-hapus"); if (bH) { showConfirmHapus(bH.dataset.id, bH.dataset.judul); }
    });

    btnClose.addEventListener("click", () => detailOverlay.classList.remove("show"));
    detailOverlay.addEventListener("click", e => { if (e.target === detailOverlay) detailOverlay.classList.remove("show"); });

    document.addEventListener("DOMContentLoaded", () => { const t = document.querySelector(".managed-table"); if (t) paginate(t); });
  </script>
</body>

</html>