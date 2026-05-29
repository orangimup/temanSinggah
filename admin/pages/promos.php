<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: /teman_singgah/index.php?auth=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    header('Content-Type: application/json');
    $kode = strtoupper(trim($_POST['kode'] ?? ''));
    $judul = trim($_POST['judul'] ?? '');
    $desk = trim($_POST['deskripsi'] ?? '');
    $diskon = intval($_POST['diskon_persen'] ?? 0);
    $min = intval($_POST['min_malam'] ?? 1);
    $dari = trim($_POST['berlaku_dari'] ?? '');
    $hingga = trim($_POST['berlaku_hingga'] ?? '');
    $maks = $_POST['maks_pakai'] !== '' ? intval($_POST['maks_pakai']) : null;
    $maks_per = $_POST['maks_pakai_per_user'] !== '' ? intval($_POST['maks_pakai_per_user']) : null;

    if (!$kode || !$judul || !$diskon || !$dari || !$hingga) {
        echo json_encode(['success' => false, 'message' => 'Isi semua field wajib.']);
        exit;
    }
    if ($diskon < 1 || $diskon > 100) {
        echo json_encode(['success' => false, 'message' => 'Diskon harus antara 1–100%.']);
        exit;
    }

    $stmt = $koneksi->prepare("
        INSERT INTO promo_codes
            (kode, judul, deskripsi, diskon_persen, min_malam,
             berlaku_dari, berlaku_hingga, maks_pakai, maks_pakai_per_user)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param('sssiissii', $kode, $judul, $desk, $diskon, $min, $dari, $hingga, $maks, $maks_per);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        $err = $koneksi->errno === 1062 ? 'Kode sudah dipakai.' : 'Gagal menyimpan.';
        echo json_encode(['success' => false, 'message' => $err]);
    }
    $stmt->close();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    header('Content-Type: application/json');
    $id = intval($_POST['id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    if (!$id || !in_array($new_status, ['aktif', 'nonaktif'])) {
        echo json_encode(['success' => false]);
        exit;
    }
    $stmt = $koneksi->prepare("UPDATE promo_codes SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $new_status, $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok, 'new_status' => $new_status]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    header('Content-Type: application/json');
    $id = intval($_POST['id'] ?? 0);
    $stmt = $koneksi->prepare("DELETE FROM promo_codes WHERE id = ?");
    $stmt->bind_param('i', $id);
    $ok = $stmt->execute();
    $stmt->close();
    echo json_encode(['success' => $ok]);
    exit;
}

$promos = $koneksi->query("
    SELECT p.*,
           COUNT(pu.id) AS total_usage_real
    FROM promo_codes p
    LEFT JOIN promo_usage pu ON pu.promo_id = p.id
    GROUP BY p.id
    ORDER BY p.dibuat_pada DESC
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Promo & Deals | Admin Teman Singgah</title>
    <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />
    <link rel="stylesheet" href="/teman_singgah/components/root.css" />
    <link rel="stylesheet" href="/teman_singgah/admin/dashboard.css" />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <style>
        .promo-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            gap: 12px;
            flex-wrap: wrap;
        }

        .btn-add-promo {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: var(--color-primary);
            color: #fff;
            border: none;
            border-radius: var(--radius-xl);
            font-size: var(--text-sm);
            font-weight: var(--font-semibold);
            cursor: pointer;
            font-family: var(--font-family);
            transition: background var(--transition-fast);
        }

        .btn-add-promo:hover {
            background: var(--color-primary-hover);
        }

        .table-search-wrap {
            position: relative;
            display: flex;
            align-items: center;
            width: 100%;
            max-width: 300px;
            height: 40px;
            background: #fff;
            border: 1.5px solid var(--color-border);
            border-radius: var(--radius-full);
            box-sizing: border-box;
        }

        .table-search-wrap:focus-within {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(139, 37, 0, .08);
        }

        .table-search-icon {
            position: absolute;
            left: 14px;
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
            font-family: inherit;
            font-size: var(--text-sm);
            color: var(--color-text-primary);
            padding: 0 16px 0 40px;
            box-sizing: border-box;
        }

        .table-search-input::placeholder {
            color: var(--color-text-hint);
        }

        .voucher-code-cell {
            font-family: monospace;
            font-weight: 700;
            font-size: 13px;
            color: var(--color-primary);
            background: rgba(139, 37, 0, .07);
            padding: 3px 9px;
            border-radius: 6px;
            display: inline-block;
        }

        .validity-cell {
            font-size: 12px;
            color: var(--color-text-secondary);
            line-height: 1.6;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100px;
        }

        .progress-bar-wrap {
            width: 100%;
            max-width: 90px;
        }

        .progress-bar-track {
            height: 5px;
            background: #f3f4f6;
            border-radius: 99px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: var(--color-primary);
            border-radius: 99px;
            transition: width .3s;
        }

        .progress-label {
            font-size: 11px;
            color: var(--color-text-secondary);
            margin-top: 3px;
        }

        .per-user-badge {
            font-size: 11px;
            color: #888;
            margin-top: 3px;
        }

        .judul-cell {
            max-width: 160px;
        }

        .judul-text {
            font-weight: 600;
            font-size: 13px;
            color: var(--color-text-primary);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        .desk-text {
            font-size: 12px;
            color: #aaa;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            z-index: 300;
            align-items: center;
            justify-content: center;
            padding: 24px;
            box-sizing: border-box;
        }

        .modal-overlay.show {
            display: flex;
        }

        .modal-box {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, .18);
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-title {
            font-family: var(--font-display);
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--color-text-primary);
            margin: 0;
        }

        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }

        .modal-field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .modal-field.full {
            grid-column: 1/-1;
        }

        .modal-label {
            font-size: 11px;
            font-weight: 600;
            color: #666;
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .modal-hint {
            font-size: 11px;
            color: #bbb;
            margin-top: 2px;
        }

        .modal-input {
            padding: 10px 13px;
            border: 1.5px solid #e2e2e2;
            border-radius: 9px;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: border-color .15s;
            box-sizing: border-box;
            width: 100%;
        }

        .modal-input:focus {
            border-color: var(--color-primary);
        }

        .modal-divider {
            height: 1px;
            background: #f3f4f6;
            margin: 4px 0;
        }

        .modal-section-label {
            font-size: 11px;
            font-weight: 700;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: .06em;
        }

        .modal-actions {
            display: flex;
            gap: 10px;
        }

        .btn-modal-batal {
            flex: 1;
            padding: 11px;
            border-radius: var(--radius-xl);
            border: 1.5px solid #ddd;
            background: transparent;
            color: #666;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all .15s;
        }

        .btn-modal-batal:hover {
            border-color: #aaa;
            color: #333;
        }

        .btn-modal-submit {
            flex: 1;
            padding: 11px;
            border-radius: var(--radius-xl);
            border: none;
            background: var(--color-primary);
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        .btn-modal-submit:hover {
            background: var(--color-primary-hover);
        }

        .modal-error {
            font-size: 13px;
            color: #c0392b;
            text-align: center;
            display: none;
            padding: 8px 12px;
            background: #fff0f0;
            border-radius: 8px;
        }

        .confirm-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            z-index: 400;
            align-items: center;
            justify-content: center;
            padding: 24px;
            box-sizing: border-box;
        }

        .confirm-overlay.show {
            display: flex;
        }

        .confirm-box {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, .18);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .confirm-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .confirm-icon-wrap.danger { background: #fff1f0; color: #dc2626; }
        .confirm-icon-wrap.warning { background: #fffbeb; color: #d97706; }
        .confirm-icon-wrap.success { background: #f0fdf4; color: #16a34a; }

        .confirm-box h3 {
            font-family: var(--font-display);
            font-size: var(--text-lg);
            font-weight: 700;
            color: var(--color-text-primary);
            margin: 0;
        }

        .confirm-box p {
            font-size: 13px;
            color: var(--color-text-secondary);
            margin: 6px 0 0;
            line-height: 1.6;
        }

        .confirm-actions {
            display: flex;
            gap: 10px;
            margin-top: 4px;
        }

        .btn-confirm-batal {
            flex: 1;
            padding: 11px;
            border-radius: var(--radius-xl);
            border: 1.5px solid #ddd;
            background: transparent;
            color: #666;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-confirm-batal:hover { border-color: #aaa; color: #333; }

        .btn-confirm-ok {
            flex: 1;
            padding: 11px;
            border-radius: var(--radius-xl);
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }

        .btn-confirm-ok.danger { background: #dc2626; color: #fff; }
        .btn-confirm-ok.danger:hover { background: #b91c1c; }
        .btn-confirm-ok.warning { background: #d97706; color: #fff; }
        .btn-confirm-ok.warning:hover { background: #b45309; }
        .btn-confirm-ok.success { background: #16a34a; color: #fff; }
        .btn-confirm-ok.success:hover { background: #15803d; }
        .btn-confirm-ok:disabled { opacity: .6; cursor: not-allowed; }

        .detail-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            z-index: 999;
            align-items: center;
            justify-content: center;
            padding: 24px;
            box-sizing: border-box;
        }

        .detail-overlay.show { display: flex; }

        .detail-box {
            background: #fff;
            border-radius: 16px;
            padding: 32px;
            width: 100%;
            max-width: 480px;
            box-shadow: 0 8px 40px rgba(0, 0, 0, .18);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .detail-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .detail-kode {
            font-family: monospace;
            font-weight: 700;
            font-size: 18px;
            color: var(--color-primary);
            background: rgba(139, 37, 0, .07);
            padding: 5px 14px;
            border-radius: 8px;
            display: inline-block;
        }

        .detail-judul {
            font-family: var(--font-display);
            font-size: var(--text-xl);
            font-weight: 700;
            color: var(--color-text-primary);
            margin: 8px 0 0;
        }

        .detail-desk {
            font-size: 13px;
            color: #888;
            margin: 4px 0 0;
            line-height: 1.6;
        }

        .btn-detail-close {
            background: #f5f5f5;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            font-size: 1rem;
            color: #666;
        }

        .btn-detail-close:hover { background: #eee; }

        .detail-divider {
            height: 1px;
            background: #f3f4f6;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .detail-item-label {
            font-size: 11px;
            font-weight: 600;
            color: #aaa;
            text-transform: uppercase;
            letter-spacing: .05em;
        }

        .detail-item-value {
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text-primary);
        }
    </style>
</head>

<body>
    <div class="admin-layout">
        <aside class="sidebar">
            <div class="sidebar-header">
                <a class="logo-link" href="/teman_singgah/admin/pages/dashboard.php"></a>
                <div class="logo-section">
                    <img class="logo-icon" src="/teman_singgah/assets/logo/logo_temansinggah.svg" alt="" />
                    <img class="logo-name" src="/teman_singgah/assets/logo/label_temansinggah.svg" alt="" />
                </div>
            </div>
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Halaman Utama</div>
                    <a class="nav-item" href="/teman_singgah/admin/pages/dashboard.php"><i class="ph-bold ph-squares-four"></i>Dashboard</a>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Manajemen</div>
                    <a class="nav-item" href="/teman_singgah/admin/pages/users.php"><i class="ph-bold ph-users"></i>Pengguna</a>
                    <a class="nav-item" href="/teman_singgah/admin/pages/listings.php"><i class="ph-bold ph-house"></i>Properti</a>
                    <a class="nav-item" href="/teman_singgah/admin/pages/reservations.php"><i class="ph-bold ph-calendar-check"></i>Reservasi</a>
                    <a class="nav-item" href="/teman_singgah/admin/pages/transactions.php"><i class="ph-bold ph-currency-circle-dollar"></i>Transaksi</a>
                    <a class="nav-item active" href="/teman_singgah/admin/pages/promos.php"><i class="ph-bold ph-tag"></i>Promo & Deals</a>
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
                    <h1 class="page-title">Promo &amp; Deals</h1>
                </div>
                <div class="topbar-right">
                    <span class="user-name">Admin utama</span>
                    <div class="user-avatar">A</div>
                </div>
            </header>

            <main class="content-area">
                <div class="promo-toolbar">
                    <div class="table-search-wrap">
                        <i class="ph-bold ph-magnifying-glass table-search-icon"></i>
                        <input type="search" id="promoSearch" class="table-search-input" placeholder="Cari kode atau judul..." />
                    </div>
                    <button class="btn-add-promo" id="btnAddPromo">
                        <i class="ph-bold ph-plus"></i> Tambah Promo
                    </button>
                </div>

                <section class="table-section">
                    <div class="table-container">
                        <table class="managed-table" id="promoTable">
                            <thead>
                                <tr>
                                    <th>No.</th>
                                    <th>Kode</th>
                                    <th>Judul</th>
                                    <th>Diskon</th>
                                    <th>Min. Malam</th>
                                    <th>Berlaku</th>
                                    <th>Pemakaian</th>
                                    <th>Limit/User</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($promos as $i => $p):
                                    $pct = $p['maks_pakai'] ? min(100, round($p['sudah_dipakai'] / $p['maks_pakai'] * 100)) : 0;
                                    $tgl_dari = date('d M Y', strtotime($p['berlaku_dari']));
                                    $tgl_hgga = date('d M Y', strtotime($p['berlaku_hingga']));
                                    $expired = strtotime($p['berlaku_hingga']) < time();
                                    $scls = $p['status'] === 'aktif' && !$expired ? 'success' : 'danger';
                                    $slbl = $expired ? 'Kedaluwarsa' : ucfirst($p['status']);
                                    $pakai_label = $p['maks_pakai'] ? $p['sudah_dipakai'].'/'.$p['maks_pakai'].' total' : $p['sudah_dipakai'].'× (∞ kuota)';
                                    $limit_user_label = $p['maks_pakai_per_user'] ? $p['maks_pakai_per_user'].'× per user' : 'Tidak dibatasi';
                                ?>
                                    <tr data-id="<?= $p['id'] ?>"
                                        data-status="<?= htmlspecialchars($p['status']) ?>"
                                        data-kode="<?= htmlspecialchars($p['kode']) ?>"
                                        data-judul="<?= htmlspecialchars($p['judul']) ?>"
                                        data-desk="<?= htmlspecialchars($p['deskripsi'] ?? '') ?>"
                                        data-diskon="<?= $p['diskon_persen'] ?>%"
                                        data-min="<?= $p['min_malam'] ?> malam"
                                        data-dari="<?= $tgl_dari ?>"
                                        data-hingga="<?= $tgl_hgga ?>"
                                        data-pakai="<?= htmlspecialchars($pakai_label) ?>"
                                        data-limit-user="<?= htmlspecialchars($limit_user_label) ?>"
                                        data-status-label="<?= $slbl ?>">
                                        <td><?= $i + 1 ?></td>
                                        <td><span class="voucher-code-cell"><?= htmlspecialchars($p['kode']) ?></span></td>
                                        <td class="judul-cell">
                                            <div class="judul-text"><?= htmlspecialchars($p['judul']) ?></div>
                                            <?php if ($p['deskripsi']): ?>
                                                <div class="desk-text"><?= htmlspecialchars($p['deskripsi']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td style="font-weight:700;color:var(--color-primary);font-size:15px;">
                                            <?= $p['diskon_persen'] ?>%
                                        </td>
                                        <td><?= $p['min_malam'] ?> malam</td>
                                        <td style="max-width:90px;">
                                            <div class="validity-cell"><?= date('d M Y', strtotime($p['berlaku_dari'])) ?></div>
                                            <div class="validity-cell">s/d <?= date('d M Y', strtotime($p['berlaku_hingga'])) ?></div>
                                        </td>
                                        <td>
                                            <?php if ($p['maks_pakai']): ?>
                                                <div class="progress-bar-wrap">
                                                    <div class="progress-bar-track">
                                                        <div class="progress-bar-fill" style="width:<?= $pct ?>%;"></div>
                                                    </div>
                                                    <div class="progress-label"><?= $p['sudah_dipakai'] ?>/<?= $p['maks_pakai'] ?> total</div>
                                                </div>
                                            <?php else: ?>
                                                <span style="font-size:12px;color:#aaa;"><?= $p['sudah_dipakai'] ?>× (∞ kuota)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($p['maks_pakai_per_user']): ?>
                                                <span style="font-size:13px;font-weight:600;color:var(--color-text-primary);"><?= $p['maks_pakai_per_user'] ?>×</span>
                                                <div class="per-user-badge">per user</div>
                                            <?php else: ?>
                                                <span style="font-size:12px;color:#aaa;">Tidak dibatasi</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="table-badge <?= $scls ?>">
                                                <span class="badge-dot"></span><?= $slbl ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="action-group">
                                                <button class="action-button secondary btn-view" title="Lihat Detail">
                                                    <i class="ph-bold ph-eye"></i>
                                                </button>
                                                <button class="action-button info btn-toggle" title="Toggle Status">
                                                    <i class="ph-bold ph-power"></i>
                                                </button>
                                                <button class="action-button error btn-delete" title="Hapus">
                                                    <i class="ph-bold ph-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($promos)): ?>
                                    <tr>
                                        <td colspan="10" style="text-align:center;color:#aaa;padding:40px;font-size:14px;">
                                            <i class="ph-bold ph-tag" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                                            Belum ada kode promo. Klik "Tambah Promo" untuk membuat.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>
        </div>
    </div>

    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box">
            <h2 class="modal-title">Tambah Kode Promo</h2>
            <div class="modal-grid">
                <div class="modal-field">
                    <label class="modal-label">Kode Promo *</label>
                    <input type="text" class="modal-input" id="fKode" placeholder="HEMAT20" style="text-transform:uppercase;font-family:monospace;font-weight:700;" />
                </div>
                <div class="modal-field">
                    <label class="modal-label">Diskon (%) *</label>
                    <input type="number" class="modal-input" id="fDiskon" min="1" max="100" placeholder="20" />
                </div>
                <div class="modal-field full">
                    <label class="modal-label">Judul *</label>
                    <input type="text" class="modal-input" id="fJudul" placeholder="Weekend Getaway" />
                </div>
                <div class="modal-field full">
                    <label class="modal-label">Deskripsi</label>
                    <input type="text" class="modal-input" id="fDeskripsi" placeholder="Deskripsi singkat (opsional)" />
                </div>
                <div class="modal-field">
                    <label class="modal-label">Berlaku Dari *</label>
                    <input type="date" class="modal-input" id="fDari" />
                </div>
                <div class="modal-field">
                    <label class="modal-label">Berlaku Hingga *</label>
                    <input type="date" class="modal-input" id="fHingga" />
                </div>
                <div class="modal-field">
                    <label class="modal-label">Min. Malam</label>
                    <input type="number" class="modal-input" id="fMinMalam" min="1" value="1" />
                </div>
            </div>
            <div class="modal-divider"></div>
            <div class="modal-section-label">Pengaturan Limit</div>
            <div class="modal-grid">
                <div class="modal-field">
                    <label class="modal-label">Maks. Total Pemakaian</label>
                    <input type="number" class="modal-input" id="fMaks" min="1" placeholder="Kosong = tidak dibatasi" />
                    <span class="modal-hint">Batas total semua user gabungan</span>
                </div>
                <div class="modal-field">
                    <label class="modal-label">Maks. Per User</label>
                    <input type="number" class="modal-input" id="fMaksPer" min="1" value="1" placeholder="1" />
                    <span class="modal-hint">Kosong = user bisa pakai berkali-kali</span>
                </div>
            </div>
            <div class="modal-error" id="modalError"></div>
            <div class="modal-actions">
                <button class="btn-modal-batal" id="btnModalBatal">Batal</button>
                <button class="btn-modal-submit" id="btnModalSubmit">
                    <i class="ph-bold ph-floppy-disk"></i> Simpan Promo
                </button>
            </div>
        </div>
    </div>

    <div class="confirm-overlay" id="confirmOverlay">
        <div class="confirm-box">
            <div style="display:flex;align-items:flex-start;gap:14px;">
                <div class="confirm-icon-wrap" id="confirmIcon">
                    <i id="confirmIconI" class="ph-bold ph-question"></i>
                </div>
                <div style="flex:1;">
                    <h3 id="confirmTitle"></h3>
                    <p id="confirmMsg"></p>
                </div>
            </div>
            <div class="confirm-actions">
                <button class="btn-confirm-batal" id="btnConfirmBatal">Batal</button>
                <button class="btn-confirm-ok" id="btnConfirmOk"></button>
            </div>
        </div>
    </div>

    <div class="detail-overlay" id="detailOverlay">
        <div class="detail-box">
            <div class="detail-header">
                <div>
                    <span class="detail-kode" id="dKode"></span>
                    <div class="detail-judul" id="dJudul"></div>
                    <div class="detail-desk" id="dDesk"></div>
                </div>
                <button class="btn-detail-close" id="btnDetailClose">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>
            <div class="detail-divider"></div>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-item-label">Diskon</span>
                    <span class="detail-item-value" id="dDiskon"></span>
                </div>
                <div class="detail-item">
                    <span class="detail-item-label">Min. Malam</span>
                    <span class="detail-item-value" id="dMin"></span>
                </div>
                <div class="detail-item">
                    <span class="detail-item-label">Berlaku Dari</span>
                    <span class="detail-item-value" id="dDari"></span>
                </div>
                <div class="detail-item">
                    <span class="detail-item-label">Berlaku Hingga</span>
                    <span class="detail-item-value" id="dHingga"></span>
                </div>
                <div class="detail-item">
                    <span class="detail-item-label">Total Pemakaian</span>
                    <span class="detail-item-value" id="dPakai"></span>
                </div>
                <div class="detail-item">
                    <span class="detail-item-label">Limit/User</span>
                    <span class="detail-item-value" id="dLimitUser"></span>
                </div>
                <div class="detail-item">
                    <span class="detail-item-label">Status</span>
                    <span class="detail-item-value" id="dStatus"></span>
                </div>
            </div>
        </div>
    </div>

    <script src="/teman_singgah/admin/dashboard.js"></script>
    <script>
        document.getElementById('promoSearch').addEventListener('input', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll('#promoTable tbody tr').forEach(row => {
                const match = !q
                    || (row.dataset.kode || '').toLowerCase().includes(q)
                    || (row.dataset.judul || '').toLowerCase().includes(q);
                row.style.display = match ? '' : 'none';
            });
        });

        const addOverlay = document.getElementById('modalOverlay');
        const errEl = document.getElementById('modalError');

        document.getElementById('btnAddPromo').addEventListener('click', () => {
            errEl.style.display = 'none';
            addOverlay.classList.add('show');
        });
        document.getElementById('btnModalBatal').addEventListener('click', () => addOverlay.classList.remove('show'));
        addOverlay.addEventListener('click', e => { if (e.target === addOverlay) addOverlay.classList.remove('show'); });

        document.getElementById('btnModalSubmit').addEventListener('click', () => {
            errEl.style.display = 'none';
            const fd = new FormData();
            fd.append('action', 'add');
            fd.append('kode', document.getElementById('fKode').value.toUpperCase().trim());
            fd.append('judul', document.getElementById('fJudul').value.trim());
            fd.append('deskripsi', document.getElementById('fDeskripsi').value.trim());
            fd.append('diskon_persen', document.getElementById('fDiskon').value);
            fd.append('min_malam', document.getElementById('fMinMalam').value || 1);
            fd.append('berlaku_dari', document.getElementById('fDari').value);
            fd.append('berlaku_hingga', document.getElementById('fHingga').value);
            fd.append('maks_pakai', document.getElementById('fMaks').value);
            fd.append('maks_pakai_per_user', document.getElementById('fMaksPer').value);

            const btn = document.getElementById('btnModalSubmit');
            btn.disabled = true;
            btn.innerHTML = '<i class="ph-bold ph-spinner"></i> Menyimpan...';

            fetch(window.location.pathname, { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        location.reload();
                    } else {
                        errEl.textContent = d.message || 'Gagal menyimpan.';
                        errEl.style.display = 'block';
                        btn.disabled = false;
                        btn.innerHTML = '<i class="ph-bold ph-floppy-disk"></i> Simpan Promo';
                    }
                })
                .catch(() => {
                    errEl.textContent = 'Koneksi bermasalah.';
                    errEl.style.display = 'block';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="ph-bold ph-floppy-disk"></i> Simpan Promo';
                });
        });

        const confirmOverlay = document.getElementById('confirmOverlay');
        const confirmIcon = document.getElementById('confirmIcon');
        const confirmIconI = document.getElementById('confirmIconI');
        const confirmTitle = document.getElementById('confirmTitle');
        const confirmMsg = document.getElementById('confirmMsg');
        const btnOk = document.getElementById('btnConfirmOk');
        const btnBatal = document.getElementById('btnConfirmBatal');
        let confirmCallback = null;

        function showConfirm({ type = 'danger', icon, title, msg, okLabel, okIcon, onOk }) {
            confirmIcon.className = `confirm-icon-wrap ${type}`;
            confirmIconI.className = `ph-bold ${icon}`;
            confirmTitle.textContent = title;
            confirmMsg.textContent = msg;
            btnOk.className = `btn-confirm-ok ${type}`;
            btnOk.innerHTML = `<i class="ph-bold ${okIcon}"></i> ${okLabel}`;
            btnOk.disabled = false;
            confirmCallback = onOk;
            confirmOverlay.classList.add('show');
        }

        function closeConfirm() {
            confirmOverlay.classList.remove('show');
            confirmCallback = null;
        }

        btnBatal.addEventListener('click', closeConfirm);
        confirmOverlay.addEventListener('click', e => { if (e.target === confirmOverlay) closeConfirm(); });

        btnOk.addEventListener('click', () => {
            if (!confirmCallback) return;
            btnOk.disabled = true;
            btnOk.innerHTML = '<i class="ph-bold ph-spinner"></i> Memproses...';
            confirmCallback();
        });

        const detailOverlay = document.getElementById('detailOverlay');

        document.getElementById('btnDetailClose').addEventListener('click', () => detailOverlay.classList.remove('show'));
        detailOverlay.addEventListener('click', e => { if (e.target === detailOverlay) detailOverlay.classList.remove('show'); });

        function openDetail(row) {
            document.getElementById('dKode').textContent      = row.dataset.kode;
            document.getElementById('dJudul').textContent     = row.dataset.judul;
            document.getElementById('dDesk').textContent      = row.dataset.desk || '—';
            document.getElementById('dDiskon').textContent    = row.dataset.diskon;
            document.getElementById('dMin').textContent       = row.dataset.min;
            document.getElementById('dDari').textContent      = row.dataset.dari;
            document.getElementById('dHingga').textContent    = row.dataset.hingga;
            document.getElementById('dPakai').textContent     = row.dataset.pakai;
            document.getElementById('dLimitUser').textContent = row.dataset.limitUser;
            document.getElementById('dStatus').textContent    = row.dataset.statusLabel;
            detailOverlay.classList.add('show');
        }

        document.querySelector('#promoTable tbody').addEventListener('click', function (e) {
            const row = e.target.closest('tr');
            if (!row || !row.dataset.id) return;

            if (e.target.closest('.btn-view')) {
                openDetail(row);
                return;
            }

            if (e.target.closest('.btn-toggle')) {
                const cur = row.dataset.status;
                const next = cur === 'aktif' ? 'nonaktif' : 'aktif';
                const nonaktif = next === 'nonaktif';
                showConfirm({
                    type: nonaktif ? 'warning' : 'success',
                    icon: nonaktif ? 'ph-power' : 'ph-check-circle',
                    title: nonaktif ? 'Nonaktifkan Promo?' : 'Aktifkan Promo?',
                    msg: `Kode "${row.dataset.kode}" akan ${nonaktif ? 'dinonaktifkan sehingga tidak bisa dipakai user.' : 'diaktifkan kembali dan bisa dipakai user.'}`,
                    okLabel: nonaktif ? 'Ya, Nonaktifkan' : 'Ya, Aktifkan',
                    okIcon: nonaktif ? 'ph-power' : 'ph-check-circle',
                    onOk: () => {
                        const fd = new FormData();
                        fd.append('action', 'toggle');
                        fd.append('id', row.dataset.id);
                        fd.append('new_status', next);
                        fetch(window.location.pathname, { method: 'POST', body: fd })
                            .then(r => r.json())
                            .then(d => { if (d.success) location.reload(); else closeConfirm(); });
                    }
                });
            }

            if (e.target.closest('.btn-delete')) {
                showConfirm({
                    type: 'danger',
                    icon: 'ph-trash',
                    title: 'Hapus Promo?',
                    msg: `Kode "${row.dataset.kode}" akan dihapus permanen beserta semua riwayat pemakaiannya.`,
                    okLabel: 'Ya, Hapus',
                    okIcon: 'ph-trash',
                    onOk: () => {
                        const fd = new FormData();
                        fd.append('action', 'delete');
                        fd.append('id', row.dataset.id);
                        fetch(window.location.pathname, { method: 'POST', body: fd })
                            .then(r => r.json())
                            .then(d => { if (d.success) { closeConfirm(); row.remove(); } else closeConfirm(); });
                    }
                });
            }
        });
    </script>
</body>

</html>