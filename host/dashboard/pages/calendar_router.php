<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_host.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/koneksi.php';

$host_id = $_SESSION['host_id'] ?? $_SESSION['user_id'] ?? 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $body   = json_decode(file_get_contents('php://input'), true);
    $action = $body['action'] ?? '';
    $lid    = intval($body['listing_id'] ?? 0);

    $chk = $conn->prepare("SELECT id FROM listings WHERE id = ? AND host_id = ?");
    $chk->bind_param("ii", $lid, $host_id);
    $chk->execute();
    if (!$chk->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false]); exit;
    }

    if ($action === 'update_pricing') {
        $hm  = floatval($body['harga_malam']       ?? 0);
        $hwk = floatval($body['harga_akhir_pekan']  ?? 0);
        $s = $conn->prepare("UPDATE listings SET harga_malam=?, harga_akhir_pekan=? WHERE id=?");
        $s->bind_param("ddi", $hm, $hwk, $lid);
        $s->execute();

        // Upsert diskon mingguan & bulanan ke listing_discounts
        foreach (['mingguan' => 'diskon_mingguan', 'bulanan' => 'diskon_bulanan'] as $tipe => $key) {
            $pct = intval($body[$key] ?? 0);
            $s2 = $conn->prepare("
                INSERT INTO listing_discounts (listing_id, tipe, persentase)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE persentase = VALUES(persentase)
            ");
            $s2->bind_param("isi", $lid, $tipe, $pct);
            $s2->execute();
        }
        echo json_encode(['success' => true]); exit;
    }

    if ($action === 'update_availability') {
        $min = intval($body['min_malam']    ?? 1);
        $max = intval($body['max_malam']    ?? 365);
        $ci  = $body['jam_checkin']  ?? '14:00';
        $co  = $body['jam_checkout'] ?? '11:00';
        $s = $conn->prepare("UPDATE listings SET min_malam=?, max_malam=?, jam_checkin=?, jam_checkout=? WHERE id=?");
        $s->bind_param("iissi", $min, $max, $ci, $co, $lid);
        $s->execute();
        echo json_encode(['success' => true]); exit;
    }

    if ($action === 'block_date') {
        $date    = $body['date']    ?? '';
        $unblock = boolval($body['unblock'] ?? false);
        if ($unblock) {
            $s = $conn->prepare("DELETE FROM blocked_dates WHERE listing_id=? AND blocked_date=?");
        } else {
            $s = $conn->prepare("INSERT IGNORE INTO blocked_dates (listing_id, blocked_date) VALUES (?,?)");
        }
        $s->bind_param("is", $lid, $date);
        $s->execute();
        echo json_encode(['success' => true]); exit;
    }

    if ($action === 'get_calendar') {
        $year  = intval($body['year']  ?? date('Y'));
        $month = intval($body['month'] ?? date('n'));
        $start = sprintf('%04d-%02d-01', $year, $month);
        $end   = date('Y-m-t', strtotime($start));

        // Total stok per kamar di listing ini
        $s = $conn->prepare("SELECT id, stok FROM listing_rooms WHERE listing_id = ?");
        $s->bind_param("i", $lid);
        $s->execute();
        $rooms = $s->get_result()->fetch_all(MYSQLI_ASSOC);

        // Kalau tidak ada kamar sama sekali, skip cek stok
        $dates = [];

        if (!empty($rooms)) {
            $stokPerKamar = array_column($rooms, 'stok', 'id'); // [room_id => stok]
            $totalStok    = array_sum($stokPerKamar);

            // Hitung booking aktif per kamar per tanggal yang overlap bulan ini
            // Hasilnya: berapa unit tiap kamar yang sudah terpakai di rentang checkin-checkout
            $s2 = $conn->prepare("
                SELECT room_id, checkin, checkout
                FROM bookings
                WHERE listing_id = ?
                  AND status IN ('menunggu','dikonfirmasi')
                  AND checkin < ? AND checkout > ?
                  AND room_id IS NOT NULL
            ");
            $s2->bind_param("iss", $lid, $end, $start);
            $s2->execute();
            $bookingRows = $s2->get_result()->fetch_all(MYSQLI_ASSOC);

            // Bangun map: tanggal → [room_id => jumlah booking aktif]
            $bookingMap = []; // ['2026-05-03']['room_id'] = count
            foreach ($bookingRows as $b) {
                $cur    = strtotime($b['checkin']);
                $endTs  = strtotime($b['checkout']);
                $roomId = $b['room_id'];
                while ($cur < $endTs) {
                    $d = date('Y-m-d', $cur);
                    $bookingMap[$d][$roomId] = ($bookingMap[$d][$roomId] ?? 0) + 1;
                    $cur = strtotime('+1 day', $cur);
                }
            }

            // Tentukan status tiap tanggal di bulan ini
            $cur   = strtotime($start);
            $endTs = strtotime($end);
            while ($cur <= $endTs) {
                $d = date('Y-m-d', $cur);

                // Hitung sisa stok total di tanggal ini
                $sisaTotal = 0;
                foreach ($stokPerKamar as $roomId => $stok) {
                    $terpakai   = $bookingMap[$d][$roomId] ?? 0;
                    $sisaTotal += max(0, $stok - $terpakai);
                }

                if ($sisaTotal === 0) $dates[$d] = 'booked'; // semua kamar penuh
                $cur = strtotime('+1 day', $cur);
            }
        }

        // Blocked manual host (override — tampil sebagai 'blocked' meski masih ada stok)
        $s3 = $conn->prepare("
            SELECT blocked_date FROM blocked_dates
            WHERE listing_id = ? AND blocked_date BETWEEN ? AND ?
        ");
        $s3->bind_param("iss", $lid, $start, $end);
        $s3->execute();
        $manualBlocked = array_column($s3->get_result()->fetch_all(MYSQLI_ASSOC), 'blocked_date');
        foreach ($manualBlocked as $d) $dates[$d] = 'blocked';

        echo json_encode(['dates' => $dates]); exit;
    }

    echo json_encode(['success' => false]); exit;
}

// ── Ambil semua listing host ──────────────────────────────────────────────────
$stmt = $conn->prepare("
    SELECT l.id, l.judul, l.harga_malam, l.harga_akhir_pekan,
           l.min_malam, l.max_malam, l.jam_checkin, l.jam_checkout,
           MAX(CASE WHEN d.tipe='mingguan' THEN d.persentase ELSE 0 END) AS diskon_mingguan,
           MAX(CASE WHEN d.tipe='bulanan'  THEN d.persentase ELSE 0 END) AS diskon_bulanan
    FROM listings l
    LEFT JOIN listing_discounts d ON d.listing_id = l.id AND d.tipe IN ('mingguan','bulanan')
    WHERE l.host_id = ? AND l.status IN ('aktif', 'draft')
    GROUP BY l.id
    ORDER BY l.id ASC
");
$stmt->bind_param("i", $host_id);
$stmt->execute();
$listings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$firstListing = $listings[0] ?? null;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Kalender | Teman Singgah</title>
  <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../../components/root.css" />
  <link rel="stylesheet" href="../../../components/navbar.css" />
  <link rel="stylesheet" href="../../../components/footer.css" />
  <link rel="stylesheet" href="../../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/calendar_router.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />

  <script>
    // Data listing di-inject langsung dari PHP — JS tinggal pakai
    const LISTINGS = <?= json_encode($listings) ?>;
  </script>
</head>

<body>
  <header class="navbar">
    <nav class="navbar-container">
      <a href="reservations.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="../../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
        <img src="../../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
      </div>
      <ul class="nav-menu">
        <li class="nav-item"><a href="reservations.php" class="nav-link">Reservasi</a></li>
        <li class="nav-item"><a href="calendar_router.php" class="nav-link active">Kalender</a></li>
        <li class="nav-item"><a href="listing.php" class="nav-link">Listing</a></li>
        <li class="nav-item"><a href="messages.php" class="nav-link">Pesan</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <?php include $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/components/navbar_profile_host.php'; ?>
    </nav>
  </header>

  <main class="main-content">
    <section class="calendar-area">
      <div class="content-header">
        <span class="content-title">
          <h2>Mei</h2>
          <i class="ph-bold ph-caret-down"></i>
        </span>
        <button class="header-filter">
          <span class="filter-value">Bulan</span>
          <i class="ph-bold ph-caret-down"></i>
        </button>
      </div>
      <div class="calendar-container"></div>
    </section>

    <aside class="settings-sidebar">
      <div class="panel-group">
        <button class="panel-card button" aria-label="Buka pengaturan harga">
          <div class="panel-card-info">
            <div class="panel-card-title">Pengaturan harga</div>
            <div class="panel-card-detail">
              <span><?= $firstListing ? 'Rp' . number_format($firstListing['harga_malam'],0,',','.') . ' per malam' : '-' ?></span>
              <span><?= $firstListing ? 'Rp' . number_format($firstListing['harga_akhir_pekan'],0,',','.') . ' harga akhir pekan' : '-' ?></span>
              <span><?= $firstListing ? 'Diskon mingguan ' . $firstListing['diskon_mingguan'] . '%' : '-' ?></span>
            </div>
          </div>
          <i class="ph-bold ph-caret-right"></i>
        </button>

        <section class="panel-popup">
          <div class="popup-header">
            <i class="ph-bold ph-caret-left"></i>
            <div class="popup-title">Pengaturan Harga</div>
          </div>
          <span class="popup-desc">Ini berlaku untuk semua malam, kecuali Anda menyesuaikannya berdasarkan tanggal.</span>

          <div class="panel-title">Harga</div>
          <div class="panel-button editable" data-field="harga-malam" data-prefix="Rp" data-type="number">
            <span class="panel-label">Per Malam</span>
            <span class="panel-value"><?= $firstListing ? number_format($firstListing['harga_malam'],0,',','.') : '0' ?></span>
            <div class="panel-edit-wrap" style="display:none">
              <span class="panel-edit-prefix">Rp</span>
              <input class="panel-input" type="number" value="<?= $firstListing['harga_malam'] ?? 0 ?>" min="0" />
            </div>
            <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
          </div>

          <div class="panel-button editable" data-field="harga-weekend" data-prefix="Rp" data-type="number">
            <span class="panel-label">Akhir Pekan</span>
            <span class="panel-value"><?= $firstListing ? number_format($firstListing['harga_akhir_pekan'],0,',','.') : '0' ?></span>
            <div class="panel-edit-wrap" style="display:none">
              <span class="panel-edit-prefix">Rp</span>
              <input class="panel-input" type="number" value="<?= $firstListing['harga_akhir_pekan'] ?? 0 ?>" min="0" />
            </div>
            <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
          </div>

          <div class="panel-title">Diskon</div>
          <div class="panel-desc">Sesuaikan harga Anda untuk menarik lebih banyak tamu.</div>

          <div class="panel-button editable" data-field="diskon-mingguan" data-suffix="%" data-type="number">
            <span class="panel-label">Mingguan</span>
            <span class="panel-value"><?= ($firstListing['diskon_mingguan'] ?? 0) . '%' ?></span>
            <div class="panel-edit-wrap" style="display:none">
              <input class="panel-input" type="number" value="<?= $firstListing['diskon_mingguan'] ?? 0 ?>" min="0" max="99" />
              <span class="panel-edit-suffix">%</span>
            </div>
            <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
          </div>

          <div class="panel-button editable" data-field="diskon-bulanan" data-suffix="%" data-type="number">
            <span class="panel-label">Bulanan</span>
            <span class="panel-value"><?= ($firstListing['diskon_bulanan'] ?? 0) . '%' ?></span>
            <div class="panel-edit-wrap" style="display:none">
              <input class="panel-input" type="number" value="<?= $firstListing['diskon_bulanan'] ?? 0 ?>" min="0" max="99" />
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
              <span><?= $firstListing ? $firstListing['min_malam'] . ' – ' . $firstListing['max_malam'] . ' malam minimal menginap' : '-' ?></span>
              <span>Pemberitahuan hari yang sama</span>
            </div>
          </div>
          <i class="ph-bold ph-caret-right"></i>
        </button>

        <section class="panel-popup">
          <div class="popup-header">
            <i class="ph-bold ph-caret-left"></i>
            <div class="popup-title">Pengaturan Ketersediaan</div>
          </div>
          <span class="popup-desc">Ini berlaku untuk semua malam, kecuali Anda menyesuaikannya berdasarkan tanggal.</span>

          <div class="panel-title">Lama Perjalanan</div>
          <div class="panel-button editable" data-field="min-malam" data-suffix=" malam" data-type="number">
            <span class="panel-label">Minimal malam</span>
            <span class="panel-value"><?= ($firstListing['min_malam'] ?? 1) . ' malam' ?></span>
            <div class="panel-edit-wrap" style="display:none">
              <input class="panel-input" type="number" value="<?= $firstListing['min_malam'] ?? 1 ?>" min="1" max="365" />
              <span class="panel-edit-suffix">malam</span>
            </div>
            <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
          </div>

          <div class="panel-button editable" data-field="max-malam" data-suffix=" malam" data-type="number">
            <span class="panel-label">Maksimal malam</span>
            <span class="panel-value"><?= ($firstListing['max_malam'] ?? 365) . ' malam' ?></span>
            <div class="panel-edit-wrap" style="display:none">
              <input class="panel-input" type="number" value="<?= $firstListing['max_malam'] ?? 365 ?>" min="1" max="365" />
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
              <span>Fleksibel – semua hari</span>
              <span>Check-in: <?= $firstListing ? substr($firstListing['jam_checkin'],0,5) . ' – ' . substr($firstListing['jam_checkout'],0,5) : '14.00 – 22.00' ?></span>
            </div>
          </div>
          <i class="ph-bold ph-caret-right"></i>
        </button>

        <section class="panel-popup">
          <div class="popup-header">
            <i class="ph-bold ph-caret-left"></i>
            <div class="popup-title">Kedatangan & Keberangkatan</div>
          </div>
          <span class="popup-desc">Ini berlaku untuk semua malam, kecuali Anda menyesuaikannya berdasarkan tanggal.</span>

          <div class="panel-title">Batas Check-in dan Check-out</div>
          <div class="panel-button editable" data-field="checkin" data-type="time">
            <span class="panel-label">Check-in</span>
            <span class="panel-value"><?= $firstListing ? substr($firstListing['jam_checkin'],0,5) : '14:00' ?></span>
            <div class="panel-edit-wrap" style="display:none">
              <input class="panel-input" type="time" value="<?= $firstListing ? substr($firstListing['jam_checkin'],0,5) : '14:00' ?>" />
            </div>
            <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
          </div>

          <div class="panel-button editable" data-field="checkout" data-type="time">
            <span class="panel-label">Check-out</span>
            <span class="panel-value"><?= $firstListing ? substr($firstListing['jam_checkout'],0,5) : '11:00' ?></span>
            <div class="panel-edit-wrap" style="display:none">
              <input class="panel-input" type="time" value="<?= $firstListing ? substr($firstListing['jam_checkout'],0,5) : '11:00' ?>" />
            </div>
            <i class="ph-bold ph-pencil-simple panel-edit-icon"></i>
          </div>
        </section>
      </div>

      <div class="panel-card">
        <div class="legend-section">
          <div class="legend-title">Keterangan</div>
          <div class="legend-items">
            <div class="legend-item"><div class="legend-swatch available"></div><span>Tersedia</span></div>
            <div class="legend-item"><div class="legend-swatch booked"></div><span>Sudah dipesan</span></div>
            <div class="legend-item"><div class="legend-swatch blocked"></div><span>Diblokir</span></div>
            <div class="legend-item"><div class="legend-swatch today"></div><span>Hari ini</span></div>
          </div>
        </div>
      </div>
    </aside>

    <aside class="listing-sidebar" id="listingSidebar">
      <div class="listing-sidebar-header">
        <span class="listing-sidebar-title">Listing</span>
        <button class="listing-sidebar-close"><i class="ph-bold ph-x"></i></button>
      </div>
      <div class="listing-sidebar-list">
        <?php foreach ($listings as $i => $l): ?>
        <div class="listing-sidebar-item <?= $i === 0 ? 'active' : '' ?>" data-id="<?= $l['id'] ?>">
          <img src="../../../assets/images/listing_<?= $l['id'] ?>.jpg"
               onerror="this.src='../../../assets/images/apurva_kempinski_bali.jpg'"
               class="listing-sidebar-img" alt="<?= htmlspecialchars($l['judul']) ?>" />
          <span class="listing-sidebar-name"><?= htmlspecialchars($l['judul']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </aside>

    <template id="weekdayHeaderTemplate">
      <div class="weekday-header">
        <div class="weekday-label">Min</div><div class="weekday-label">Sen</div>
        <div class="weekday-label">Sel</div><div class="weekday-label">Rab</div>
        <div class="weekday-label">Kam</div><div class="weekday-label">Jum</div>
        <div class="weekday-label">Sab</div>
      </div>
    </template>
    <template id="monthSectionTemplate">
      <div class="month-section"><div class="month-label"></div><div class="day-grid"></div></div>
    </template>
    <template id="dayCardTemplate">
      <div class="day-card"><span class="day-number"></span><span class="day-price"></span></div>
    </template>
    <template id="dayCardStatusTemplate">
      <div class="day-card"><div class="day-status-dot"></div><span class="day-number"></span><span class="day-price"></span></div>
    </template>
    <template id="dayCardEmptyTemplate">
      <div class="day-card day-empty"></div>
    </template>
  </main>

  <script src="../scripts/calendar_router.js"></script>
  <script src="../../../components/navbar.js"></script>
  <script src="../../../popups/auth.js"></script>
</body>
</html>