<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_host.php';

$host = 'localhost';
$dbname = 'teman_singgah';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die('Koneksi gagal: ' . $e->getMessage());
}

$host_id = (int) ($_SESSION['id'] ?? 0);

// Stats
$stmt = $pdo->prepare("
  SELECT
    COALESCE(SUM(CASE WHEN status = 'Completed' THEN payout_amount END), 0) AS total_cair,
    COALESCE(SUM(CASE WHEN status IN ('Dijadwalkan','Processing') THEN payout_amount END), 0) AS pending,
    COUNT(CASE WHEN status = 'Completed' THEN 1 END) AS jumlah_cair,
    COUNT(CASE WHEN status = 'Failed' THEN 1 END) AS jumlah_gagal
  FROM payouts WHERE host_id = ?
");
$stmt->execute([$host_id]);
$stats = $stmt->fetch();

// Pendapatan bulan ini
$stmt2 = $pdo->prepare("
  SELECT COALESCE(SUM(b.pendapatan_host), 0) AS total
  FROM bookings b
  JOIN listings l ON l.id = b.listing_id
  WHERE l.host_id = ?
    AND b.status = 'dikonfirmasi'
    AND MONTH(b.dibuat_pada) = MONTH(CURDATE())
    AND YEAR(b.dibuat_pada) = YEAR(CURDATE())
");
$stmt2->execute([$host_id]);
$bulan_ini = $stmt2->fetch();

// Filter payout
$allowed_statuses = ['Dijadwalkan', 'Processing', 'Completed', 'Failed'];
$filter = isset($_GET['status']) && in_array($_GET['status'], $allowed_statuses)
    ? $_GET['status'] : null;

if ($filter) {
    $stmt3 = $pdo->prepare("
      SELECT p.id, p.payout_amount, p.bank_name, p.account_number,
             p.scheduled_date, p.processed_at, p.status, p.failure_reason
      FROM payouts p
      WHERE p.host_id = ? AND p.status = ?
      ORDER BY p.scheduled_date DESC
    ");
    $stmt3->execute([$host_id, $filter]);
} else {
    $stmt3 = $pdo->prepare("
      SELECT p.id, p.payout_amount, p.bank_name, p.account_number,
             p.scheduled_date, p.processed_at, p.status, p.failure_reason
      FROM payouts p
      WHERE p.host_id = ?
      ORDER BY p.scheduled_date DESC
    ");
    $stmt3->execute([$host_id]);
}
$payouts = $stmt3->fetchAll();

// Ambil data rekening dari tabel users
$bank_stmt = $pdo->prepare("SELECT bank_name, account_number, account_name FROM users WHERE id = ?");
$bank_stmt->execute([$host_id]);
$bank_info = $bank_stmt->fetch();

// Chart 6 bulan
$stmt4 = $pdo->prepare("
  SELECT
    DATE_FORMAT(m.bulan, '%b %Y') AS bulan,
    DATE_FORMAT(m.bulan, '%Y-%m') AS bulan_sort,
    COALESCE(SUM(p.payout_amount), 0) AS total
  FROM (
    SELECT DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL n MONTH), '%Y-%m-01') AS bulan
    FROM (
      SELECT 0 n UNION SELECT 1 UNION SELECT 2
      UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
    ) nums
  ) m
  LEFT JOIN payouts p
    ON DATE_FORMAT(p.processed_at, '%Y-%m') = DATE_FORMAT(m.bulan, '%Y-%m')
    AND p.host_id = ?
    AND p.status = 'Completed'
  GROUP BY bulan_sort, bulan
  ORDER BY bulan_sort ASC
");
$stmt4->execute([$host_id]);  
$chart_data = $stmt4->fetchAll(); 

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmt_rp(float $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}
function fmt_tgl(?string $d): string
{
    if (!$d)
        return '—';
    $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($d);
    return date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts);
}
function fmt_dt(?string $d): string
{
    if (!$d)
        return '—';
    $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $ts = strtotime($d);
    return date('j', $ts) . ' ' . $months[(int) date('n', $ts)] . ' ' . date('Y', $ts) . ', ' . date('H:i', $ts);
}
function badge_payout(string $s): array
{
    return match ($s) {
        'Dijadwalkan' => ['class' => 'scheduled', 'label' => 'Dijadwalkan'],
        'Processing' => ['class' => 'processing', 'label' => 'Diproses'],
        'Completed' => ['class' => 'completed', 'label' => 'Selesai'],
        'Failed' => ['class' => 'failed', 'label' => 'Gagal'],
        default => ['class' => 'scheduled', 'label' => $s],
    };
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pendapatan | Teman Singgah</title>
    <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />
    <link rel="stylesheet" href="../../../components/root.css" />
    <link rel="stylesheet" href="../../../components/navbar.css" />
    <link rel="stylesheet" href="../../../components/footer.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />

    <style>
        .main-content {
            max-width: var(--container-xl);
            margin: 0 auto;
            margin-top: 112px;
            padding: 0 var(--space-24) var(--space-80);
        }

        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-family: 'Inter', sans-serif;
            font-size: 1.75rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        .page-header p {
            font-size: .875rem;
            color: #888;
            margin-top: .25rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.75rem;
        }

        .stat-card {
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 16px;
            padding: 1.25rem 1.5rem 1.375rem;
            transition: box-shadow .2s;
        }

        .stat-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, .07);
        }

        .stat-icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            margin-bottom: .875rem;
        }

        .stat-icon.green {
            background: #f0fdf4;
            color: #1D9E75;
        }

        .stat-icon.yellow {
            background: #fff7ed;
            color: #d97706;
        }

        .stat-icon.blue {
            background: #eff6ff;
            color: #3b82f6;
        }

        .stat-icon.red {
            background: #fff1f2;
            color: #e24b4a;
        }

        .stat-label {
            font-size: .72rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #999;
            margin-bottom: .375rem;
        }

        .stat-value {
            font-size: 1.4rem;
            font-weight: 700;
            line-height: 1.2;
            color: #1a1a1a;
        }

        .stat-sub {
            font-size: .75rem;
            color: #bbb;
            margin-top: .3rem;
        }

        .bank-info-card {
            background: var(--color-bg-card, #fff);
            border: 1px solid var(--color-border-subtle, #ececec);
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .bank-icon {
            width: 40px;
            height: 40px;
            background: var(--color-primary-light, #f5e6d3);
            border-radius: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary, #8B5A2B);
            font-size: 1.3rem;
        }

        .bank-info-text {
            flex: 1;
        }

        .bank-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--color-text-secondary, #666);
        }

        .bank-value {
            font-weight: 600;
            color: var(--color-text-primary, #1a1a1a);
        }

        .bank-warning {
            color: var(--color-error, #e24b4a);
        }

        .btn-ubah-rekening {
            background: transparent;
            border: 1.5px solid var(--color-border-strong, #ccc);
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            color: var(--color-text-primary, #333);
        }

        .btn-ubah-rekening:hover {
            background: var(--color-bg-section, #f5f5f5);
            border-color: var(--color-primary, #8B5A2B);
        }

        .chart-card {
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 16px;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.75rem;
        }

        .chart-card-title {
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 1.5rem;
        }

        .bar-chart {
            display: flex;
            align-items: flex-end;
            gap: .875rem;
            height: 150px;
        }

        .bar-col {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: .4rem;
            height: 100%;
            justify-content: flex-end;
        }

        .bar-amount {
            font-size: .6rem;
            color: #aaa;
            text-align: center;
            white-space: nowrap;
        }

        .bar-fill {
            width: 100%;
            background: var(--color-primary, #8B5A2B);
            border-radius: 6px 6px 0 0;
            min-height: 4px;
            opacity: .85;
            transition: opacity .2s;
        }

        .bar-col:hover .bar-fill {
            opacity: 1;
        }

        .bar-label {
            font-size: .67rem;
            color: #aaa;
            white-space: nowrap;
        }

        .chart-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 150px;
            color: #bbb;
            font-size: .875rem;
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            visibility: hidden;
            opacity: 0;
            transition: all 0.2s;
        }

        .modal-overlay.active {
            visibility: visible;
            opacity: 1;
        }

        .modal-container {
            background: var(--color-bg-card, #fff);
            border-radius: 24px;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 20px 35px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.2s ease;
        }

        @keyframes modalFadeIn {
            from {
                transform: scale(0.95);
                opacity: 0;
            }

            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem 1.5rem;
            border-bottom: 1px solid var(--color-border-subtle, #ececec);
        }

        .modal-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--color-text-primary, #1a1a1a);
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: var(--color-text-secondary, #999);
        }

        .modal-body {
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group label {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--color-text-primary, #333);
        }

        .form-group input {
            padding: 0.7rem 1rem;
            border: 1.5px solid var(--color-border, #ddd);
            border-radius: 12px;
            font-family: inherit;
            background: var(--color-bg-input, #fff);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--color-primary, #8B5A2B);
        }

        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--color-border-subtle, #ececec);
            display: flex;
            justify-content: flex-end;
            gap: 0.8rem;
        }

        .btn-primary {
            background: var(--color-primary, #8B5A2B);
            color: white;
            border: none;
            padding: 0.6rem 1.5rem;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary:hover {
            background: var(--color-primary-hover, #7a4a1f);
        }

        .btn-secondary {
            background: transparent;
            border: 1.5px solid var(--color-border-strong, #ccc);
            padding: 0.6rem 1.5rem;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .section-title {
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            color: #1a1a1a;
        }

        .filter-group {
            display: flex;
            gap: .5rem;
            flex-wrap: wrap;
        }

        .filter-item {
            padding: var(--space-8) var(--space-20);
            border: 1.5px solid var(--color-border);
            border-radius: var(--radius-full);
            background: transparent;
            color: var(--color-text-secondary);
            font-weight: var(--font-semibold);
            font-size: var(--text-sm);
            text-decoration: none;
            transition: all var(--transition-base);
        }

        .filter-item:hover {
            border-color: var(--color-primary);
            color: var(--color-primary);
        }

        .filter-item.active {
            background: var(--color-primary);
            border-color: var(--color-primary);
            color: var(--color-text-inverse);
        }

        .payouts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 1rem;
        }

        .payout-card {
            background: #fff;
            border: 1px solid #ececec;
            border-radius: 16px;
            padding: 1.25rem 1.375rem;
            display: flex;
            flex-direction: column;
            gap: .875rem;
            transition: box-shadow .2s;
        }

        .payout-card:hover {
            box-shadow: 0 4px 20px rgba(0, 0, 0, .07);
        }

        .payout-card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .payout-id {
            font-size: .72rem;
            font-weight: 500;
            color: #bbb;
            letter-spacing: .04em;
        }

        .card-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: .3rem .75rem;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
        }

        .badge-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .card-badge.scheduled {
            background: #fff7ed;
            color: #c2410c;
        }

        .card-badge.scheduled .badge-dot {
            background: #c2410c;
        }

        .card-badge.processing {
            background: #eff6ff;
            color: #2563eb;
        }

        .card-badge.processing .badge-dot {
            background: #2563eb;
        }

        .card-badge.completed {
            background: #f0fdf4;
            color: #15803d;
        }

        .card-badge.completed .badge-dot {
            background: #15803d;
        }

        .card-badge.failed {
            background: #fff1f2;
            color: #be123c;
        }

        .card-badge.failed .badge-dot {
            background: #be123c;
        }

        .payout-amount {
            font-family: 'Inter', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a1a;
            letter-spacing: -.01em;
        }

        .payout-divider {
            height: 1px;
            background: #f0f0f0;
        }

        .payout-detail {
            display: flex;
            flex-direction: column;
            gap: .5rem;
        }

        .detail-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .5rem;
        }

        .detail-label {
            font-size: .75rem;
            color: #999;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .detail-value {
            font-size: .775rem;
            font-weight: 500;
            color: #333;
            text-align: right;
        }

        .failure-note {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 10px;
            padding: .625rem .875rem;
            font-size: .75rem;
            color: #be123c;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 1rem;
            color: #bbb;
            grid-column: 1 / -1;
        }

        .empty-state i {
            font-size: 2.5rem;
            opacity: .4;
            display: block;
            margin-bottom: .75rem;
        }

        .empty-state p {
            font-size: .875rem;
        }
    </style>
</head>

<body>

    <header class="navbar">
        <nav class="navbar-container">
            <a href="reservations.php" class="logo-link"></a>
            <div class="logo-section">
                <img src="../../../assets/logo/logo_temansinggah.svg" alt="Logo" class="logo-icon" />
                <img src="../../../assets/logo/label_temansinggah.svg" alt="Teman Singgah" class="logo-name" />
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/reservations.php"
                        class="nav-link">Reservasi</a></li>
                <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/calendar_router.php"
                        class="nav-link">Kalender</a></li>
                <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/listing.php"
                        class="nav-link">Listing</a></li>
                <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/earnings.php"
                        class="nav-link active">Pendapatan</a></li>
                <div class="nav-indicator"></div>
            </ul>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/components/navbar_profile_host.php'; ?>
        </nav>
    </header>

    <main class="main-content">

        <div class="page-header">
            <h1>Pendapatan</h1>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon green"><i class="ph-bold ph-money"></i></div>
                <div class="stat-label">Total Sudah Cair</div>
                <div class="stat-value"><?= fmt_rp((float) $stats['total_cair']) ?></div>
                <div class="stat-sub"><?= $stats['jumlah_cair'] ?> kali pencairan</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon yellow"><i class="ph-bold ph-clock"></i></div>
                <div class="stat-label">Menunggu Dicairkan</div>
                <div class="stat-value"><?= fmt_rp((float) $stats['pending']) ?></div>
                <div class="stat-sub">Dijadwalkan / sedang diproses</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon blue"><i class="ph-bold ph-calendar-blank"></i></div>
                <div class="stat-label">Pendapatan Bulan Ini</div>
                <div class="stat-value"><?= fmt_rp((float) $bulan_ini['total']) ?></div>
                <div class="stat-sub">Dari booking dikonfirmasi</div>
            </div>
            <?php if ($stats['jumlah_gagal'] > 0): ?>
                <div class="stat-card">
                    <div class="stat-icon red"><i class="ph-bold ph-warning-circle"></i></div>
                    <div class="stat-label">Pencairan Gagal</div>
                    <div class="stat-value"><?= $stats['jumlah_gagal'] ?> kali</div>
                    <div class="stat-sub">Hubungi admin untuk info lebih lanjut</div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Info Rekening Host -->
        <div class="bank-info-card">
            <div class="bank-icon"><i class="ph-bold ph-bank"></i></div>
            <div class="bank-info-text">
                <div class="bank-label">REKENING TUJUAN PENCAIRAN</div>
                <div class="bank-value" id="bankDisplay">
                    <?php if ($bank_info && $bank_info['bank_name'] && $bank_info['account_number']): ?>
                        <?= htmlspecialchars($bank_info['bank_name']) ?> –
                        <?= htmlspecialchars($bank_info['account_number']) ?> a.n.
                        <?= htmlspecialchars($bank_info['account_name']) ?>
                    <?php else: ?>
                        <span class="bank-warning">Rekening belum diisi. Pencairan tidak dapat diproses.</span>
                    <?php endif; ?>
                </div>
            </div>
            <button class="btn-ubah-rekening" id="openModalBtn">Ubah Rekening</button>
        </div>

        <div class="modal-overlay" id="bankModal">
            <div class="modal-container">
                <div class="modal-header">
                    <div class="modal-title">Informasi Rekening Bank</div>
                    <button class="modal-close" id="closeModalBtn">&times;</button>
                </div>
                <form id="bankForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Bank (contoh: BCA, Mandiri, BRI)</label>
                            <input type="text" name="bank_name" id="bank_name"
                                value="<?= htmlspecialchars($bank_info['bank_name'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nomor Rekening</label>
                            <input type="text" name="account_number" id="account_number"
                                value="<?= htmlspecialchars($bank_info['account_number'] ?? '') ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Nama Pemilik Rekening (sesuai KTP)</label>
                            <input type="text" name="account_name" id="account_name"
                                value="<?= htmlspecialchars($bank_info['account_name'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-secondary" id="cancelModalBtn">Batal</button>
                        <button type="submit" class="btn-primary">Simpan Rekening</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="chart-card">
            <div class="chart-card-title">Pencairan 6 Bulan Terakhir</div>
            <?php if (empty($chart_data)): ?>
                <div class="chart-empty">Belum ada data pencairan.</div>
            <?php else:
                $max = max(array_column($chart_data, 'total')) ?: 1;
                ?>
                <div class="bar-chart">
                    <?php foreach ($chart_data as $row):
                        $pct = round(($row['total'] / $max) * 100);
                        ?>
                        <div class="bar-col">
                            <div class="bar-amount"><?= fmt_rp((float) $row['total']) ?></div>
                            <div class="bar-fill" style="height:<?= $pct ?>%"></div>
                            <div class="bar-label"><?= htmlspecialchars($row['bulan']) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="section-header">
            <div class="section-title">Riwayat Payout</div>
            <div class="filter-group">
                <?php
                $base = 'earnings.php';
                $tabs = [
                    '' => 'Semua',
                    'Dijadwalkan' => 'Dijadwalkan',
                    'Processing' => 'Diproses',
                    'Completed' => 'Selesai',
                    'Failed' => 'Gagal'
                ];
                foreach ($tabs as $val => $lbl):
                    $active = ($filter === ($val ?: null)) ? 'active' : '';
                    $href = $val ? "$base?status=$val" : $base;
                    ?>
                    <a href="<?= $href ?>" class="filter-item <?= $active ?>"><?= $lbl ?></a>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="payouts-grid">
            <?php if (empty($payouts)): ?>
                <div class="empty-state">
                    <i class="ph-bold ph-receipt"></i>
                    <p>Tidak ada riwayat pencairan dana.</p>
                </div>
            <?php else: ?>
                <?php foreach ($payouts as $p):
                    $badge = badge_payout($p['status']);
                    ?>
                    <div class="payout-card">

                        <div class="payout-card-header">
                            <span class="payout-id">#PAY-<?= str_pad($p['id'], 4, '0', STR_PAD_LEFT) ?></span>
                            <span class="card-badge <?= $badge['class'] ?>">
                                <span class="badge-dot"></span><?= $badge['label'] ?>
                            </span>
                        </div>

                        <div class="payout-amount"><?= fmt_rp((float) $p['payout_amount']) ?></div>

                        <div class="payout-divider"></div>

                        <div class="payout-detail">
                            <div class="detail-row">
                                <span class="detail-label"><i class="ph-bold ph-bank"></i> Bank Tujuan</span>
                                <span class="detail-value">
                                    <?php if ($p['bank_name'] && $p['account_number']): ?>
                                        <?= htmlspecialchars($p['bank_name']) ?> — <?= htmlspecialchars($p['account_number']) ?>
                                    <?php else: ?>
                                        <span style="color:#ccc">Belum diisi</span>
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label"><i class="ph-bold ph-calendar-blank"></i> Jadwal Transfer</span>
                                <span class="detail-value"><?= fmt_tgl($p['scheduled_date']) ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label"><i class="ph-bold ph-check-circle"></i> Diproses Pada</span>
                                <span class="detail-value"><?= fmt_dt($p['processed_at']) ?></span>
                            </div>
                        </div>

                        <?php if ($p['status'] === 'Failed' && $p['failure_reason']): ?>
                            <div class="failure-note">
                                <i class="ph-bold ph-warning" style="flex-shrink:0;margin-top:1px;"></i>
                                <?= htmlspecialchars($p['failure_reason']) ?>
                            </div>
                        <?php endif; ?>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </main>

    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-column">
                <span class="footer-brand">Teman Singgah</span>
                <p class="footer-description">Platform booking penginapan terpercaya di seluruh Indonesia, dari hotel
                    berbintang hingga homestay lokal.</p>
                <div class="footer-social">
                    <a href="" class="social-link"><i class="ri-instagram-line"></i></a>
                    <a href="" class="social-link"><i class="ri-facebook-circle-line"></i></a>
                    <a href="" class="social-link"><i class="ri-youtube-line"></i></a>
                    <a href="" class="social-link"><i class="ri-twitter-line"></i></a>
                    <a href="" class="social-link"><i class="ri-mail-line"></i></a>
                </div>
            </div>
            <div class="footer-column">
                <h3 class="footer-title">Navigasi</h3>
                <ul class="footer-links">
                    <li><a href="/teman_singgah/index.php" class="footer-link">Beranda</a></li>
                    <li><a href="/teman_singgah/user/pages/promo_deals.php" class="footer-link">Promo & Deals</a></li>
                    <li><a href="/teman_singgah/user/pages/become_host.php" class="footer-link">Jadi Host</a></li>
                    <li><a href="/teman_singgah/user/pages/about_us.php" class="footer-link">Tentang Kami</a></li>
                    <li><a href="/teman_singgah/user/pages/account.php" class="footer-link">Akun</a></li>
                </ul>
            </div>
            <div class="footer-column">
                <h3 class="footer-title">Dukungan</h3>
                <ul class="footer-links">
                    <li><a href="#" class="footer-link">Pusat Bantuan</a></li>
                    <li><a href="#" class="footer-link">FAQ</a></li>
                    <li><a href="/teman_singgah/user/pages/become_host.php" class="footer-link">Cara Menjadi Host</a>
                    </li>
                    <li><a href="#" class="footer-link">Cara Booking</a></li>
                    <li><a href="/teman_singgah/user/pages/about_us.php" class="footer-link">Tentang Kami</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="footer-copyright">© 2026 Teman Singgah — All rights reserved.</p>
            <div class="footer-legal">
                <a href="" class="footer-link bottom">Kebijakan Privasi</a>
                <span class="footer-dot">•</span>
                <a href="" class="footer-link bottom">Syarat & Ketentuan</a>
            </div>
        </div>
    </footer>

    <script src="../../../components/navbar.js"></script>
    <script>
        const modal = document.getElementById('bankModal');
        const openBtn = document.getElementById('openModalBtn');
        const closeBtn = document.getElementById('closeModalBtn');
        const cancelBtn = document.getElementById('cancelModalBtn');
        const bankForm = document.getElementById('bankForm');

        openBtn.addEventListener('click', () => modal.classList.add('active'));
        closeBtn.addEventListener('click', () => modal.classList.remove('active'));
        cancelBtn.addEventListener('click', () => modal.classList.remove('active'));

        modal.addEventListener('click', (e) => {
            if (e.target === modal) modal.classList.remove('active');
        });

        bankForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const data = new FormData(bankForm);

            const res = await fetch('/teman_singgah/host/dashboard/pages/update_bank.php', {
                method: 'POST',
                body: data
            });
            const json = await res.json();

            if (json.success) {
                document.getElementById('bankDisplay').textContent =
                    `${data.get('bank_name')} – ${data.get('account_number')} a.n. ${data.get('account_name')}`;
                modal.classList.remove('active');
            } else {
                alert('Gagal menyimpan: ' + (json.message ?? 'Coba lagi'));
            }
        });
    </script>
</body>

</html>