<?php
session_start();
require_once '../../../koneksi.php';

$host_id = $_SESSION['host_id'] ?? $_SESSION['id'] ?? 0;

if ($host_id == 0) {
    header("Location: /teman_singgah/index.php");
    exit;
}

$listing_id = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
$room_id = isset($_GET['room_id']) && $_GET['room_id'] !== '' ? (int) $_GET['room_id'] : null;

if ($listing_id == 0) {
    $q = mysqli_query($koneksi, "SELECT id FROM listings WHERE host_id = $host_id AND status = 'aktif' LIMIT 1");
    if ($q && $r = mysqli_fetch_assoc($q))
        $listing_id = $r['id'];
}

function getManualBlockedDates($koneksi, $listing_id, $room_id = null)
{
    $dates = [];
    $sql = "SELECT blocked_date, reason FROM blocked_dates WHERE listing_id = $listing_id AND blocked_by = 'host' AND unlocked_at IS NULL";
    if ($room_id)
        $sql .= " AND (room_id = $room_id OR room_id IS NULL)";
    $r = mysqli_query($koneksi, $sql);
    if ($r)
        while ($row = mysqli_fetch_assoc($r))
            $dates[$row['blocked_date']] = $row['reason'];
    return $dates;
}

function getAutoBlockedDates($koneksi, $listing_id, $room_id = null)
{
    $dates = [];

    // 1. Hitung total kamar aktif untuk listing ini
    if ($room_id) {
        // Kalau filter per room, stoknya ya 1 (kamar itu sendiri)
        $total_rooms = 1;
    } else {
        $r = mysqli_query($koneksi, "
            SELECT COUNT(*) AS total FROM listing_rooms 
            WHERE listing_id = $listing_id
        ");
        $total_rooms = $r ? (int) mysqli_fetch_assoc($r)['total'] : 0;
    }

    // 2. Kalau tidak ada kamar terdaftar, fallback ke blocked_dates system seperti biasa
    if ($total_rooms === 0) {
        $sql = "SELECT blocked_date, reason FROM blocked_dates 
                WHERE listing_id = $listing_id AND blocked_by = 'system' AND unlocked_at IS NULL";
        $r = mysqli_query($koneksi, $sql);
        if ($r)
            while ($row = mysqli_fetch_assoc($r))
                $dates[$row['blocked_date']] = $row['reason'];
        return $dates;
    }

    // 3. Ambil semua booking aktif, hitung berapa room terpakai per tanggal
    $sql = "SELECT checkin, checkout FROM bookings 
            WHERE listing_id = $listing_id AND status IN ('menunggu', 'dikonfirmasi')";
    if ($room_id)
        $sql .= " AND room_id = $room_id";

    $r = mysqli_query($koneksi, $sql);
    $room_count_per_date = [];

    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            try {
                $period = new DatePeriod(
                    new DateTime($row['checkin']),
                    new DateInterval('P1D'),
                    new DateTime($row['checkout'])
                );
                foreach ($period as $d) {
                    $ds = $d->format('Y-m-d');
                    $room_count_per_date[$ds] = ($room_count_per_date[$ds] ?? 0) + 1;
                }
            } catch (Exception $e) {
                // Skip kalau format tanggal rusak
            }
        }
    }

    // 4. Tanggal yang jumlah booking-nya >= total kamar → auto blocked
    foreach ($room_count_per_date as $date => $count) {
        if ($count >= $total_rooms) {
            $dates[$date] = 'Semua kamar sudah penuh';
        }
    }

    return $dates;
}

function getBookedDates($koneksi, $listing_id, $room_id = null)
{
    $dates = [];
    $chk = mysqli_query($koneksi, "SHOW TABLES LIKE 'bookings'");
    if (mysqli_num_rows($chk) == 0)
        return $dates;
    $sql = "SELECT checkin, checkout FROM bookings WHERE listing_id = $listing_id AND status IN ('menunggu','dikonfirmasi')";
    if ($room_id)
        $sql .= " AND (room_id = $room_id OR room_id IS NULL)";
    $r = mysqli_query($koneksi, $sql);
    if ($r) {
        while ($row = mysqli_fetch_assoc($r)) {
            $period = new DatePeriod(new DateTime($row['checkin']), new DateInterval('P1D'), new DateTime($row['checkout']));
            foreach ($period as $d)
                $dates[$d->format('Y-m-d')] = true;
        }
    }
    return $dates;
}

function getCustomPrices($koneksi, $listing_id, $room_id = null)
{
    $prices = [];
    $chk = mysqli_query($koneksi, "SHOW TABLES LIKE 'date_prices'");
    if (mysqli_num_rows($chk) == 0)
        return $prices;
    $sql = "SELECT price_date, custom_price FROM date_prices WHERE listing_id = $listing_id";
    if ($room_id)
        $sql .= " AND (room_id = $room_id OR room_id IS NULL)";
    $r = mysqli_query($koneksi, $sql);
    if ($r)
        while ($row = mysqli_fetch_assoc($r))
            $prices[$row['price_date']] = $row['custom_price'];
    return $prices;
}

function getHostListings($koneksi, $host_id)
{
    $listings = [];
    $r = mysqli_query($koneksi, "
        SELECT l.id, l.judul, 
               (SELECT lp.nama_file FROM listing_photos lp 
                WHERE lp.listing_id = l.id AND lp.adalah_cover = 1 
                LIMIT 1) AS foto_utama
        FROM listings l
        WHERE l.host_id = $host_id AND l.status = 'aktif'
    ");
    if ($r)
        while ($row = mysqli_fetch_assoc($r))
            $listings[] = $row;
    return $listings;
}

$listing = null;
$lr = mysqli_query($koneksi, "SELECT * FROM listings WHERE id = $listing_id");
if ($lr)
    $listing = mysqli_fetch_assoc($lr);

$manualBlocked = getManualBlockedDates($koneksi, $listing_id, $room_id);
$autoBlocked   = getAutoBlockedDates($koneksi, $listing_id, $room_id);
$bookedDates   = getBookedDates($koneksi, $listing_id, $room_id);
$customPrices  = getCustomPrices($koneksi, $listing_id, $room_id);
$hostListings  = getHostListings($koneksi, $host_id);

$settings = [
    'harga_malam'        => $listing['harga_malam'] ?? 399344,
    'harga_akhir_pekan'  => $listing['harga_akhir_pekan'] ?? 423305,
    'min_malam'          => $listing['min_malam'] ?? 1,
    'max_malam'          => $listing['max_malam'] ?? 365,
    'jam_checkin'        => substr($listing['jam_checkin'] ?? '14:00:00', 0, 5),
    'jam_checkout'       => substr($listing['jam_checkout'] ?? '12:00:00', 0, 5),
    'diskon_mingguan'    => $listing['diskon_mingguan'] ?? 0,
    'diskon_bulanan'     => $listing['diskon_bulanan'] ?? 0,
];

function fmt($n)
{
    return number_format($n, 0, ',', '.');
}
?>
<!doctype html>
<html lang="id">

<head>
    <?php
    $base = '/teman_singgah';
    ?>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Kalender | Teman Singgah</title>
    <link rel="icon" href="/assets/logo/logo_temansinggah.svg" />
    <link rel="stylesheet" href="/teman_singgah/components/root.css" />
    <link rel="stylesheet" href="/teman_singgah/components/navbar.css" />
    <link rel="stylesheet" href="/teman_singgah/components/footer.css" />
    <link rel="stylesheet" href="/teman_singgah/popups/auth.css" />
    <link rel="stylesheet" href="/teman_singgah/host/dashboard/styles/calendar_router.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />

    <style>
        /* ── Status warna blocked manual/auto ───────────── */
        .day-card.blocked-manual {
            background: #FEE2E2;
            border-color: #FCA5A5;
        }

        .day-card.blocked-manual .day-number {
            color: #DC2626;
            text-decoration: line-through;
        }

        .day-card.blocked-manual .day-price {
            color: #EF4444;
        }

        .day-card.blocked-auto {
            background: #FEF3C7;
            border-color: #FCD34D;
        }

        .day-card.blocked-auto .day-number {
            color: #D97706;
        }

        .day-card.blocked-auto .day-price {
            color: #F59E0B;
        }

        /* ── Tooltip ─────────────────────────────────────── */
        .day-card {
            position: relative;
        }

        .day-card:hover::after {
            content: attr(data-tooltip);
            position: absolute;
            bottom: calc(100% + 5px);
            left: 50%;
            transform: translateX(-50%);
            background: #1F2937;
            color: #fff;
            font-size: 11px;
            padding: 4px 8px;
            border-radius: 6px;
            white-space: nowrap;
            z-index: 200;
            pointer-events: none;
        }

        /* ── Date detail popup ───────────────────────────── */
        .date-detail-popup {
            position: fixed;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.18);
            z-index: 1001;
            width: 300px;
            display: none;
            overflow: hidden;
        }

        .ddp-inner {
            padding: 20px;
        }

        .ddp-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .ddp-title {
            font-size: 18px;
            font-weight: 700;
        }

        .ddp-close {
            background: none;
            border: none;
            font-size: 22px;
            cursor: pointer;
            color: #6B7280;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: background .15s;
        }

        .ddp-close:hover {
            background: #F3F4F6;
        }

        .ddp-status {
            font-size: 14px;
            font-weight: 600;
            padding: 8px 12px;
            border-radius: 8px;
            background: #F9FAFB;
            margin-bottom: 14px;
        }

        .popup-btn {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            margin-bottom: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: opacity .15s;
            font-family: inherit;
        }

        .popup-btn:last-child {
            margin-bottom: 0;
        }

        .popup-btn:hover {
            opacity: .88;
        }

        .block-btn {
            background: #EF4444;
            color: #fff;
        }

        .unblock-btn {
            background: #10B981;
            color: #fff;
        }

        .price-btn {
            background: #8B5CF6;
            color: #fff;
        }

        /* ── Loading overlay ─────────────────────────────── */
        .loading-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, .45);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            display: none;
        }

        .loading-overlay.active {
            display: flex;
        }

        .spinner {
            width: 48px;
            height: 48px;
            border: 4px solid rgba(255, 255, 255, .3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: spin .8s linear infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        /* ── Dropdown ────────────────────────────────────── */
        .dropdown-popup {
            position: fixed;
            background: #fff;
            border: 1.5px solid #E5E7EB;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, .1);
            z-index: 1000;
            min-width: 160px;
            display: none;
            overflow: hidden;
        }

        .dropdown-popup.open {
            display: block;
        }

        .dropdown-option {
            display: block;
            width: 100%;
            padding: 10px 16px;
            border: none;
            background: transparent;
            text-align: left;
            cursor: pointer;
            font-size: 14px;
            font-family: inherit;
            transition: background .15s;
        }

        .dropdown-option:hover {
            background: #F9FAFB;
        }
    </style>
</head>

<body>

    <header class="navbar">
        <nav class="navbar-container">
            <a href="/teman_singgah/host/dashboard/pages/reservations.php" class="logo-link"></a>
            <div class="logo-section">
                <img src="/teman_singgah/assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah"
                    class="logo-icon" />
                <img src="/teman_singgah/assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah"
                    class="logo-name" />
            </div>
            <ul class="nav-menu">
                <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/reservations.php"
                        class="nav-link">Reservasi</a>
                </li>
                <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/calendar_router.php"
                        class="nav-link active">Kalender</a></li>
                <li class="nav-item"><a href="/teman_singgah/host/dashboard/pages/listing.php"
                        class="nav-link">Listing</a></li>
                <div class="nav-indicator"></div>
            </ul>
            <div class="nav-right">
                <a href="/teman_singgah/index.php"><button class="ghost-button">Ganti ke pengunjung</button></a>
                <div class="icon-buttons">
                    <button class="icon-button profile" aria-label="Profile">A</button>
                    <button class="icon-button hamburger" aria-label="Hamburger"><i
                            class="ph-bold ph-list"></i></button>
                </div>
                <div id="hamburgerDropdown"></div>
                <div id="languagePopup"></div>
                <div id="authPopup"></div>
            </div>
        </nav>
    </header>

    <main class="main-content">

        <section class="calendar-area">
            <div class="content-header">
                <button id="listingBtn" class="header-button" title="Pilih Listing"
                    onclick="document.getElementById('listingSidebar').classList.toggle('open')">
                    <i class="ph-bold ph-list-bullets"></i>
                </button>
                <span class="content-title" id="monthYearSelector">
                    <h2 id="currentMonthLabel"></h2>
                    <i class="ph-bold ph-caret-down"></i>
                </span>
                <button class="header-filter" id="viewModeBtn">
                    <span class="filter-value" id="viewModeText">Bulan</span>
                    <i class="ph-bold ph-caret-down"></i>
                </button>
            </div>
            <div class="calendar-container" id="calendarContainer"></div>
        </section>

        <aside class="settings-sidebar">

            <div class="panel-group">
                <button class="panel-card button" aria-label="Buka pengaturan harga">
                    <div class="panel-card-info">
                        <div class="panel-card-title">Pengaturan harga</div>
                        <div class="panel-card-detail">
                            <span>Rp<?php echo fmt($settings['harga_malam']); ?> per malam</span>
                            <span>Rp<?php echo fmt($settings['harga_akhir_pekan']); ?> harga akhir pekan</span>
                            <span>Diskon mingguan <?php echo $settings['diskon_mingguan']; ?>%</span>
                        </div>
                    </div>
                    <i class="ph-bold ph-caret-right"></i>
                </button>
                <section class="panel-popup">
                    <div class="popup-header">
                        <i class="ph-bold ph-caret-left"></i>
                        <div class="popup-title">Pengaturan Harga</div>
                    </div>
                    <span class="popup-desc">Ini berlaku untuk semua malam, kecuali Anda menyesuaikannya berdasarkan
                        tanggal.</span>
                    <div class="panel-title">Harga</div>
                    <div class="panel-button editable" data-field="harga_malam" data-prefix="Rp" data-type="number">
                        <span class="panel-label">Per Malam</span>
                        <span class="panel-value"><?php echo fmt($settings['harga_malam']); ?></span>
                        <div class="panel-edit-wrap" style="display:none">
                            <span class="panel-edit-prefix">Rp</span>
                            <input class="panel-input" type="number" value="<?php echo $settings['harga_malam']; ?>"
                                min="0" />
                        </div>
                        <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
                    </div>
                    <div class="panel-button editable" data-field="harga_akhir_pekan" data-prefix="Rp"
                        data-type="number">
                        <span class="panel-label">Akhir Pekan</span>
                        <span class="panel-value"><?php echo fmt($settings['harga_akhir_pekan']); ?></span>
                        <div class="panel-edit-wrap" style="display:none">
                            <span class="panel-edit-prefix">Rp</span>
                            <input class="panel-input" type="number"
                                value="<?php echo $settings['harga_akhir_pekan']; ?>" min="0" />
                        </div>
                        <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
                    </div>
                    <div class="panel-title">Diskon</div>
                    <div class="panel-desc">Sesuaikan harga Anda untuk menarik lebih banyak tamu.</div>
                    <div class="panel-button editable" data-field="diskon_mingguan" data-suffix="%" data-type="number">
                        <span class="panel-label">Mingguan</span>
                        <span class="panel-value"><?php echo $settings['diskon_mingguan']; ?>%</span>
                        <div class="panel-edit-wrap" style="display:none">
                            <input class="panel-input" type="number" value="<?php echo $settings['diskon_mingguan']; ?>"
                                min="0" max="99" />
                            <span class="panel-edit-suffix">%</span>
                        </div>
                        <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
                    </div>
                    <div class="panel-button editable" data-field="diskon_bulanan" data-suffix="%" data-type="number">
                        <span class="panel-label">Bulanan</span>
                        <span class="panel-value"><?php echo $settings['diskon_bulanan']; ?>%</span>
                        <div class="panel-edit-wrap" style="display:none">
                            <input class="panel-input" type="number" value="<?php echo $settings['diskon_bulanan']; ?>"
                                min="0" max="99" />
                            <span class="panel-edit-suffix">%</span>
                        </div>
                        <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
                    </div>
                </section>
            </div>

            <div class="panel-group">
                <button class="panel-card button" aria-label="Buka pengaturan ketersediaan">
                    <div class="panel-card-info">
                        <div class="panel-card-title">Pengaturan ketersediaan</div>
                        <div class="panel-card-detail">
                            <span><?php echo $settings['min_malam']; ?> – <?php echo $settings['max_malam']; ?> malam
                                minimal menginap</span>
                        </div>
                    </div>
                    <i class="ph-bold ph-caret-right"></i>
                </button>
                <section class="panel-popup">
                    <div class="popup-header">
                        <i class="ph-bold ph-caret-left"></i>
                        <div class="popup-title">Pengaturan Ketersediaan</div>
                    </div>
                    <span class="popup-desc">Pengaturan lama minimal dan maksimal menginap.</span>
                    <div class="panel-title">Lama Perjalanan</div>
                    <div class="panel-button editable" data-field="min_malam" data-suffix=" malam" data-type="number">
                        <span class="panel-label">Minimal malam</span>
                        <span class="panel-value"><?php echo $settings['min_malam']; ?> malam</span>
                        <div class="panel-edit-wrap" style="display:none">
                            <input class="panel-input" type="number" value="<?php echo $settings['min_malam']; ?>"
                                min="1" max="365" />
                            <span class="panel-edit-suffix">malam</span>
                        </div>
                        <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
                    </div>
                    <div class="panel-button editable" data-field="max_malam" data-suffix=" malam" data-type="number">
                        <span class="panel-label">Maksimal malam</span>
                        <span class="panel-value"><?php echo $settings['max_malam']; ?> malam</span>
                        <div class="panel-edit-wrap" style="display:none">
                            <input class="panel-input" type="number" value="<?php echo $settings['max_malam']; ?>"
                                min="1" max="365" />
                            <span class="panel-edit-suffix">malam</span>
                        </div>
                        <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
                    </div>
                </section>
            </div>

            <div class="panel-group">
                <button class="panel-card button" aria-label="Buka aturan kedatangan & keberangkatan">
                    <div class="panel-card-info">
                        <div class="panel-card-title">Kedatangan & keberangkatan</div>
                        <div class="panel-card-detail">
                            <span>Check-in: <?php echo $settings['jam_checkin']; ?> – Check-out:
                                <?php echo $settings['jam_checkout']; ?></span>
                        </div>
                    </div>
                    <i class="ph-bold ph-caret-right"></i>
                </button>
                <section class="panel-popup">
                    <div class="popup-header">
                        <i class="ph-bold ph-caret-left"></i>
                        <div class="popup-title">Kedatangan & Keberangkatan</div>
                    </div>
                    <span class="popup-desc">Pengaturan jam check-in dan check-out.</span>
                    <div class="panel-title">Batas Check-in dan Check-out</div>
                    <div class="panel-button editable" data-field="jam_checkin" data-type="time">
                        <span class="panel-label">Check-in</span>
                        <span class="panel-value"><?php echo $settings['jam_checkin']; ?></span>
                        <div class="panel-edit-wrap" style="display:none">
                            <input class="panel-input" type="time" value="<?php echo $settings['jam_checkin']; ?>" />
                        </div>
                        <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
                    </div>
                    <div class="panel-button editable" data-field="jam_checkout" data-type="time">
                        <span class="panel-label">Check-out</span>
                        <span class="panel-value"><?php echo $settings['jam_checkout']; ?></span>
                        <div class="panel-edit-wrap" style="display:none">
                            <input class="panel-input" type="time" value="<?php echo $settings['jam_checkout']; ?>" />
                        </div>
                        <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
                    </div>
                </section>
            </div>

            <div class="panel-card">
                <div class="legend-section">
                    <div class="legend-title">Keterangan</div>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="legend-swatch available"></div><span>Tersedia</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-swatch booked"></div><span>Sudah dipesan</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-swatch blocked"></div><span>Diblokir</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-swatch today"></div><span>Hari ini</span>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Listing sidebar -->
        <aside class="listing-sidebar" id="listingSidebar">
            <div class="listing-sidebar-header">
                <span class="listing-sidebar-title">Listing</span>
                <button class="listing-sidebar-close"><i class="ph-bold ph-x"></i></button>
            </div>
            <div class="listing-sidebar-list">
                <?php foreach ($hostListings as $item): ?>
                    <div class="listing-sidebar-item <?php echo $item['id'] == $listing_id ? 'active' : ''; ?>"
                        data-listing-id="<?php echo $item['id']; ?>">
                        <img src="<?php echo $item['foto_utama']
                            ? (str_starts_with($item['foto_utama'], 'http')
                                ? htmlspecialchars($item['foto_utama'])
                                : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($item['foto_utama']))
                            : '/teman_singgah/assets/img/placeholder.jpg'; ?>" class="listing-sidebar-img"
                            alt="<?php echo htmlspecialchars($item['judul']); ?>"
                            onerror="this.src='/teman_singgah/assets/img/placeholder.jpg'" />
                        <span class="listing-sidebar-name"><?php echo htmlspecialchars($item['judul']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </aside>

        <!-- Templates -->
        <template id="weekdayHeaderTemplate">
            <div class="weekday-header">
                <div class="weekday-label">Min</div>
                <div class="weekday-label">Sen</div>
                <div class="weekday-label">Sel</div>
                <div class="weekday-label">Rab</div>
                <div class="weekday-label">Kam</div>
                <div class="weekday-label">Jum</div>
                <div class="weekday-label">Sab</div>
            </div>
        </template>
        <template id="monthSectionTemplate">
            <div class="month-section">
                <div class="month-label"></div>
                <div class="day-grid"></div>
            </div>
        </template>
        <template id="dayCardTemplate">
            <div class="day-card">
                <span class="day-number"></span>
                <span class="day-price"></span>
            </div>
        </template>
        <template id="dayCardStatusTemplate">
            <div class="day-card">
                <div class="day-status-dot"></div>
                <span class="day-number"></span>
                <span class="day-price"></span>
            </div>
        </template>
        <template id="dayCardEmptyTemplate">
            <div class="day-card day-empty"></div>
        </template>
    </main>

    <!-- Date detail popup -->
    <div id="dateDetailPopup" class="date-detail-popup">
        <div class="ddp-inner">
            <div class="ddp-header">
                <span id="ddpDate" class="ddp-title"></span>
                <button class="ddp-close" onclick="closeDatePopup()">×</button>
            </div>
            <div id="ddpStatus" class="ddp-status"></div>
            <div id="ddpActions"></div>
        </div>
    </div>

    <div id="loadingOverlay" class="loading-overlay">
        <div class="spinner"></div>
    </div>

    <script>
        window.calendarData = {
            listingId: <?php echo $listing_id; ?>,
            roomId: <?php echo json_encode($room_id); ?>,
            manualBlocked: <?php echo json_encode($manualBlocked); ?>,
            autoBlocked: <?php echo json_encode($autoBlocked); ?>,
            bookedDates: <?php echo json_encode($bookedDates); ?>,
            customPrices: <?php echo json_encode($customPrices); ?>,
            settings: {
                harga_malam: <?php echo $settings['harga_malam']; ?>,
                harga_akhir_pekan: <?php echo $settings['harga_akhir_pekan']; ?>,
                diskon_mingguan: <?php echo $settings['diskon_mingguan']; ?>,
                diskon_bulanan: <?php echo $settings['diskon_bulanan']; ?>
            }
        };
    </script>

    <script src="/teman_singgah/host/dashboard/scripts/calendar_router.js"></script>
    <script src="/teman_singgah/components/navbar.js"></script>
    <script src="/teman_singgah/popups/auth.js"></script>
</body>

</html>