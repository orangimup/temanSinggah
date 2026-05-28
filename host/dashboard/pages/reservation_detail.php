<?php
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

$user_id = $_SESSION['id'] ?? 0;
$listing_id = (int) ($_GET['id'] ?? 0);

if (!$listing_id) {
    header('Location: reservations.php');
    exit;
}

$sql = "
  SELECT
    b.id, b.listing_id, b.user_id, b.room_id,
    b.checkin, b.checkout, b.jumlah_tamu, b.total_harga,
    b.status, b.dibuat_pada,

    u.nama  AS guest_name,
    u.email AS guest_email,
    u.no_hp AS guest_phone,
    u.photo AS guest_photo,

    l.judul        AS property_name,
    lp.nama_file   AS property_image,

    lr.nama        AS room_name,
    lr.harga_malam AS room_price,

    DATEDIFF(b.checkout, b.checkin) AS durasi_malam,

    CASE
      WHEN b.status IN ('dibatalkan','batal')  THEN 'cancelled'
      WHEN b.status = 'selesai'                THEN 'completed'
      WHEN NOW() BETWEEN b.checkin AND b.checkout
           AND b.status NOT IN ('dibatalkan','batal','selesai') THEN 'ongoing'
      WHEN b.checkin > NOW()
           AND b.status NOT IN ('dibatalkan','batal')           THEN 'upcoming'
      ELSE 'completed'
    END AS status_group
  FROM bookings b
  JOIN listings  l            ON l.id  = b.listing_id AND l.host_id = :user_id
  LEFT JOIN listing_photos lp ON lp.listing_id = l.id AND lp.adalah_cover = 1
  JOIN users u                ON u.id  = b.user_id
  LEFT JOIN listing_rooms lr  ON lr.id = b.room_id
  WHERE b.id = :listing_id
  LIMIT 1
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':user_id' => $user_id, ':listing_id' => $listing_id]);
$r = $stmt->fetch();

if (!$r) {
    header('Location: reservations.php');
    exit;
}

$cancelReason = null;
if ($r['status_group'] === 'cancelled') {
    $cr = $pdo->prepare("SELECT alasan FROM booking_cancellations WHERE listing_id = ? LIMIT 1");
    $cr->execute([$listing_id]);
    $cancelReason = $cr->fetchColumn();
}

function initials(string $name): string
{
    $parts = array_filter(explode(' ', trim($name)));
    $first = strtoupper(substr($parts[0] ?? 'T', 0, 1));
    $last = count($parts) > 1 ? strtoupper(substr(end($parts), 0, 1)) : '';
    return $first . $last;
}
function fmtDate(string $date): string
{
    $b = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    [$y, $m, $d] = explode('-', $date);
    return (int) $d . ' ' . $b[(int) $m] . ' ' . $y;
}
function fmtDateShort(string $date): string
{
    $b = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    [$y, $m, $d] = explode('-', $date);
    return (int) $d . ' ' . $b[(int) $m] . ' ' . $y;
}
function fmtRupiah(float $n): string
{
    return 'Rp ' . number_format($n, 0, ',', '.');
}
function badgeInfo(string $g): array
{
    return match ($g) {
        'upcoming' => ['label' => 'Mendatang', 'cls' => 'badge-upcoming'],
        'ongoing' => ['label' => 'Berlangsung', 'cls' => 'badge-ongoing'],
        'completed' => ['label' => 'Selesai', 'cls' => 'badge-completed'],
        'cancelled' => ['label' => 'Dibatalkan', 'cls' => 'badge-cancelled'],
        default => ['label' => 'Lainnya', 'cls' => 'badge-completed'],
    };
}

$badge = badgeInfo($r['status_group']);
$init = initials($r['guest_name']);
$malam = (int) $r['durasi_malam'];
$roomTxt = $r['room_name'] ? htmlspecialchars($r['room_name']) : 'Kamar Standar';
$bookingCode = '#RSV-' . date('Y') . '-' . str_pad($r['id'], 4, '0', STR_PAD_LEFT);

$guestPhotoFile = $r['guest_photo'] ?? '';
$guestPhotoPath = '/teman_singgah/assets/uploads/photos/' . $guestPhotoFile;
$hasGuestPhoto = !empty($guestPhotoFile) && file_exists($_SERVER['DOCUMENT_ROOT'] . $guestPhotoPath);

$imgSrc = !empty($r['property_image'])
    ? (str_starts_with($r['property_image'], 'http')
        ? $r['property_image']
        : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($r['property_image']))
    : '/teman_singgah/assets/images/apurva_kempinski_bali.jpg';

$nightlyRate = $malam > 0 ? ($r['total_harga'] / $malam) : ($r['room_price'] ?? $r['total_harga']);
$serviceFee = round($r['total_harga'] * 0.05);
$netEarning = $r['total_harga'] - $serviceFee;
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= $bookingCode ?> | Teman Singgah</title>
    <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />
    <link rel="stylesheet" href="../../../components/root.css" />
    <link rel="stylesheet" href="../../../components/navbar.css" />
    <link rel="stylesheet" href="../../../components/footer.css" />
    <link rel="stylesheet" href="../../../popups/auth.css" />
    <link rel="stylesheet" href="../styles/reservations.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />

    <style>
        * {
            font-family: 'Inter', sans-serif;
        }

        .rsv-wrap {
            max-width: var(--container-xl, 1200px);
            margin: 0 auto;
            margin-top: 100px;
            padding: 0 var(--space-24, 24px) 96px;
        }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.85rem;
            color: var(--color-text-secondary, #6b7280);
            margin-bottom: 20px;
        }

        .breadcrumb a {
            color: var(--color-primary, #2563eb);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        /* Hero banner */
        .rsv-banner {
            position: relative;
            width: 100%;
            height: 320px;
            border-radius: 24px;
            overflow: hidden;
            margin-bottom: 32px;
        }

        .rsv-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .rsv-banner-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, .6) 0%, transparent 55%);
        }

        .rsv-banner-content {
            position: absolute;
            bottom: 24px;
            left: 28px;
            right: 28px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 16px;
        }

        .rsv-banner-title {
            color: #fff;
            font-size: 1.6rem;
            font-weight: 700;
            line-height: 1.25;
        }

        .rsv-banner-sub {
            color: rgba(255, 255, 255, .8);
            font-size: 0.875rem;
            margin-top: 4px;
        }

        /* Badge */
        .rsv-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .rsv-badge .dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
        }

        .badge-upcoming {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .badge-upcoming .dot {
            background: #1d4ed8;
        }

        .badge-ongoing {
            background: #f0fdf4;
            color: #15803d;
        }

        .badge-ongoing .dot {
            background: #15803d;
        }

        .badge-completed {
            background: #f5f5f5;
            color: #555;
        }

        .badge-completed .dot {
            background: #888;
        }

        .badge-cancelled {
            background: #fff1f2;
            color: #be123c;
        }

        .badge-cancelled .dot {
            background: #be123c;
        }

        /* Highlight row */
        .highlight-row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 28px;
        }

        .highlight-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            background: #fff;
            border: 1px solid var(--color-border-subtle, #e5e7eb);
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--color-text-primary, #111827);
        }

        .highlight-badge i {
            color: var(--color-primary, #2563eb);
            font-size: 1rem;
        }

        /* Main grid */
        .rsv-grid {
            display: grid;
            grid-template-columns: 1fr 340px;
            gap: 28px;
            align-items: start;
        }

        @media (max-width: 800px) {
            .rsv-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Card */
        .rsv-card {
            background: #fff;
            border: 1px solid var(--color-border-subtle, #e5e7eb);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 20px;
        }

        .rsv-card:last-child {
            margin-bottom: 0;
        }

        .rsv-card-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--color-text-primary, #111827);
            margin: 0 0 18px;
            padding-bottom: 14px;
            border-bottom: 1px solid var(--color-border-subtle, #f0f0f0);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .rsv-card-title i {
            color: var(--color-primary, #2563eb);
            font-size: 1.1rem;
        }

        /* Info rows */
        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid #f5f5f5;
        }

        .info-row:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .info-icon {
            flex-shrink: 0;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: #f0f4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--color-primary, #2563eb);
            font-size: 1rem;
        }

        .info-body {
            flex: 1;
        }

        .info-label {
            font-size: 0.72rem;
            color: #9ca3af;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .04em;
            margin-bottom: 2px;
        }

        .info-value {
            font-size: 0.9rem;
            color: #111827;
            font-weight: 500;
        }

        .stay-timeline-wrap {
            margin: 4px 0 16px;
        }

        .stay-labels {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
        }

        .stay-node-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .07em;
            color: #9ca3af;
        }

        .stay-timeline {
            display: flex;
            align-items: center;
            gap: 0;
        }

        .stay-node-date {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .stay-divider {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            padding: 0 40px;
        }

        .stay-line {
            width: 100%;
            height: 2px;
            background: #e5e7eb;
            position: relative;
        }

        .stay-line::before,
        .stay-line::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--color-primary, #2563eb);
            transform: translateY(-50%);
        }

        .stay-line::before {
            left: 0;
        }

        .stay-line::after {
            right: 0;
        }

        .stay-caption {
            font-size: 0.75rem;
            color: #9ca3af;
            white-space: nowrap;
            font-weight: 600;
        }

        /* Guest card */
        .guest-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .guest-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--color-primary, #2563eb);
            color: #fff;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .guest-name {
            font-size: 1rem;
            font-weight: 700;
            color: #111827;
        }

        .guest-sub {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 2px;
        }

        /* Price rows */
        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            font-size: 0.875rem;
            border-bottom: 1px solid #f5f5f5;
        }

        .price-row:last-child {
            border-bottom: none;
        }

        .price-row.total {
            font-weight: 700;
            font-size: 1rem;
            padding-top: 14px;
            margin-top: 4px;
            border-top: 2px solid #111827;
            border-bottom: none;
        }

        .price-label {
            color: #6b7280;
        }

        .price-row.total .price-label {
            color: #111827;
        }

        .price-minus {
            color: #ef4444;
        }

        .price-earn {
            color: #16a34a;
            font-weight: 700;
        }

        .sidebar-sticky {
            position: sticky;
            top: 100px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-card {
            background: #fff;
            border: 1px solid var(--color-border-subtle, #e5e7eb);
            border-radius: 20px;
            padding: 24px;
        }

        .sidebar-title {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            color: #9ca3af;
            margin-bottom: 14px;
        }

        .sidebar-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            padding: 9px 0;
            border-bottom: 1px solid #f5f5f5;
            color: #6b7280;
        }

        .sidebar-row:last-child {
            border-bottom: none;
        }

        .sidebar-row strong {
            color: #111827;
            font-weight: 600;
        }

        .sidebar-divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 16px 0;
        }

        /* Buttons */
        .btn-block {
            width: 100%;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 0.875rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-decoration: none;
            transition: opacity .18s, background .18s;
            margin-bottom: 10px;
        }

        .btn-block:last-child {
            margin-bottom: 0;
        }

        .btn-block:active {
            opacity: .85;
        }

        .btn-blue {
            background: var(--color-primary, #2563eb);
            color: #fff;
        }

        .btn-blue:hover {
            opacity: .88;
        }

        .btn-gray {
            background: #f3f4f6;
            color: #111827;
        }

        .btn-gray:hover {
            background: #e5e7eb;
        }

        /* Cancel box */
        .cancel-box {
            background: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 12px;
            padding: 16px;
        }

        .cancel-box-head {
            display: flex;
            align-items: center;
            gap: 7px;
            font-size: 0.8rem;
            font-weight: 700;
            color: #be123c;
            margin-bottom: 8px;
        }

        .cancel-box-text {
            font-size: 0.875rem;
            color: #6b7280;
            line-height: 1.6;
        }

        @media (max-width: 600px) {
            .rsv-banner {
                height: 220px;
            }

            .rsv-banner-title {
                font-size: 1.2rem;
            }

            .rsv-grid {
                grid-template-columns: 1fr;
            }
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
                        class="nav-link active">Reservasi</a></li>
                <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/calendar_router.php"
                        class="nav-link">Kalender</a></li>
                <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/listing.php"
                        class="nav-link">Listing</a></li>
                <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/messages.php"
                        class="nav-link">Pesan</a></li>
                <div class="nav-indicator"></div>
            </ul>
            <?php include $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/components/navbar_profile_host.php'; ?>
        </nav>
    </header>

    <main class="rsv-wrap">

        <nav class="breadcrumb">
            <a href="/teman_singgah/host/dashboard/pages/reservations.php" class="breadcrumb-link">
                <i class="ph-bold ph-calendar-blank"></i> Reservasi
            </a>
            <i class="ph-bold ph-caret-right"></i>
            <span><?= $bookingCode ?></span>
        </nav>

        <!-- Hero Banner -->
        <div class="rsv-banner">
            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($r['property_name']) ?>" />
            <div class="rsv-banner-overlay"></div>
            <div class="rsv-banner-content">
                <div>
                    <div class="rsv-banner-title"><?= htmlspecialchars($r['property_name']) ?></div>
                    <div class="rsv-banner-sub">
                        <?= $bookingCode ?> &nbsp;·&nbsp; Dipesan <?= fmtDate($r['dibuat_pada']) ?>
                    </div>
                </div>
                <span class="rsv-badge <?= $badge['cls'] ?>">
                    <span class="dot"></span><?= $badge['label'] ?>
                </span>
            </div>
        </div>

        <!-- Highlight chips -->
        <div class="highlight-row">
            <span class="highlight-badge"><i class="ph-bold ph-door"></i><?= $roomTxt ?></span>
            <span class="highlight-badge"><i class="ph-bold ph-moon"></i><?= $malam ?> malam</span>
            <span class="highlight-badge"><i class="ph-bold ph-users"></i><?= (int) $r['jumlah_tamu'] ?> tamu</span>
            <span class="highlight-badge"><i class="ph-bold ph-calendar-blank"></i><?= fmtDateShort($r['checkin']) ?> →
                <?= fmtDateShort($r['checkout']) ?></span>
            <span class="highlight-badge"><i
                    class="ph-bold ph-wallet"></i><?= fmtRupiah((float) $r['total_harga']) ?></span>
        </div>

        <!-- Main grid -->
        <div class="rsv-grid">
            <div>
                <div class="rsv-card">
                    <h2 class="rsv-card-title"><i class="ph-bold ph-calendar-check"></i> Masa Menginap</h2>
                    <div class="stay-timeline-wrap">
                        <div class="stay-labels">
                            <span class="stay-node-label">Check-in</span>
                            <span class="stay-node-label">Check-out</span>
                        </div>
                        <div class="stay-timeline">
                            <div class="stay-node-date"><?= fmtDateShort($r['checkin']) ?></div>
                            <div class="stay-divider">
                                <div class="stay-line"></div>
                                <div class="stay-caption"><?= $malam ?> malam</div>
                            </div>
                            <div class="stay-node-date" style="text-align:right"><?= fmtDateShort($r['checkout']) ?>
                            </div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ph-bold ph-users"></i></div>
                        <div class="info-body">
                            <div class="info-label">Jumlah Tamu</div>
                            <div class="info-value"><?= (int) $r['jumlah_tamu'] ?> tamu</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ph-bold ph-door"></i></div>
                        <div class="info-body">
                            <div class="info-label">Kamar</div>
                            <div class="info-value"><?= $roomTxt ?></div>
                        </div>
                    </div>
                </div>

                <!-- Info Pemesanan -->
                <div class="rsv-card">
                    <h2 class="rsv-card-title"><i class="ph-bold ph-receipt"></i> Info Pemesanan</h2>
                    <div class="info-row">
                        <div class="info-icon"><i class="ph-bold ph-hash"></i></div>
                        <div class="info-body">
                            <div class="info-label">Kode Reservasi</div>
                            <div class="info-value"><?= $bookingCode ?></div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ph-bold ph-clock"></i></div>
                        <div class="info-body">
                            <div class="info-label">Tanggal Pemesanan</div>
                            <div class="info-value"><?= fmtDate($r['dibuat_pada']) ?></div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-icon"><i class="ph-bold ph-tag"></i></div>
                        <div class="info-body">
                            <div class="info-label">Status</div>
                            <div class="info-value">
                                <span class="rsv-badge <?= $badge['cls'] ?>" style="font-size:.75rem;padding:4px 12px;">
                                    <span class="dot"></span><?= $badge['label'] ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Alasan Batal -->
                <?php if ($r['status_group'] === 'cancelled'): ?>
                    <div class="rsv-card" id="cancel-reason">
                        <h2 class="rsv-card-title"><i class="ph-bold ph-x-circle"></i> Alasan Pembatalan</h2>
                        <div class="cancel-box">
                            <div class="cancel-box-head"><i class="ph-bold ph-warning"></i> Reservasi Dibatalkan</div>
                            <div class="cancel-box-text">
                                <?= $cancelReason ? nl2br(htmlspecialchars($cancelReason)) : 'Tidak ada alasan yang dicatat.' ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

            </div><!-- /left -->

            <!-- RIGHT (sidebar) -->
            <div>

                <!-- Tamu -->
                <div class="sidebar-card" style="margin-bottom:20px;">
                    <p class="sidebar-title">Informasi Tamu</p>
                    <div class="guest-header">
                        <?php if ($hasGuestPhoto): ?>
                            <div class="guest-avatar" style="padding:0;overflow:hidden;">
                                <img src="<?= htmlspecialchars($guestPhotoPath) ?>"
                                    style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
                            </div>
                        <?php else: ?>
                            <div class="guest-avatar"><?= htmlspecialchars($init) ?></div>
                        <?php endif; ?>
                        <div>
                            <div class="guest-name"><?= htmlspecialchars($r['guest_name']) ?></div>
                            <div class="guest-sub"><?= (int) $r['jumlah_tamu'] ?> tamu</div>
                        </div>
                    </div>
                    <?php if (!empty($r['guest_email'])): ?>
                        <div class="sidebar-row">
                            <span><i class="ph-bold ph-envelope" style="margin-right:4px;"></i>Email</span>
                            <strong
                                style="font-size:.8rem;max-width:160px;text-align:right;word-break:break-all;"><?= htmlspecialchars($r['guest_email']) ?></strong>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($r['guest_phone'])): ?>
                        <div class="sidebar-row">
                            <span><i class="ph-bold ph-phone" style="margin-right:6px;color:#2563eb"></i>Nomor HP</span>
                            <strong><?= htmlspecialchars($r['guest_phone']) ?></strong>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pendapatan -->
                <div class="sidebar-card" style="margin-bottom:20px;">
                    <p class="sidebar-title">Rincian Pendapatan</p>
                    <div class="price-row">
                        <span class="price-label"><?= fmtRupiah($nightlyRate) ?> × <?= $malam ?> malam</span>
                        <span><?= fmtRupiah((float) $r['total_harga']) ?></span>
                    </div>
                    <div class="price-row">
                        <span class="price-label">Biaya layanan (5%)</span>
                        <span class="price-minus">− <?= fmtRupiah($serviceFee) ?></span>
                    </div>
                    <div class="price-row total">
                        <span class="price-label">Pendapatan bersih</span>
                        <span class="price-earn">
                            <?= $r['status_group'] === 'cancelled' ? 'Dibatalkan' : fmtRupiah($netEarning) ?>
                        </span>
                    </div>
                </div>

                <!-- Tindakan -->
                <div class="sidebar-card">
                    <p class="sidebar-title">Tindakan</p>

                    <?php if ($r['status_group'] === 'upcoming' || $r['status_group'] === 'ongoing'): ?>
                        <a href="/teman_singgah/host/dashboard/pages/messages.php?to=<?= (int) $r['user_id'] ?>"
                            class="btn-block btn-blue">
                            <i class="ph-bold ph-chat-circle"></i> Hubungi Tamu
                        </a>
                        <a href="/teman_singgah/host/dashboard/pages/calendar_router.php" class="btn-block btn-gray">
                            <i class="ph-bold ph-calendar-blank"></i> Lihat di Kalender
                        </a>

                    <?php elseif ($r['status_group'] === 'completed'): ?>
                        <button class="btn-block btn-blue">
                            <i class="ph-bold ph-star"></i> Beri Ulasan Tamu
                        </button>
                        <a href="/teman_singgah/host/dashboard/pages/messages.php?to=<?= (int) $r['user_id'] ?>"
                            class="btn-block btn-gray">
                            <i class="ph-bold ph-chat-circle"></i> Hubungi Tamu
                        </a>

                    <?php elseif ($r['status_group'] === 'cancelled'): ?>
                        <a href="/teman_singgah/host/dashboard/pages/listing.php" class="btn-block btn-gray">
                            <i class="ph-bold ph-list-bullets"></i> Kelola Listing
                        </a>
                    <?php endif; ?>

                </div>

            </div><!-- /right -->
        </div><!-- /grid -->

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
    <script src="../../../popups/auth.js"></script>

    <?php if ($r['status_group'] === 'cancelled' && str_contains($_SERVER['REQUEST_URI'], '#cancel-reason')): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                document.getElementById('cancel-reason')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        </script>
    <?php endif; ?>

</body>

</html>