<?php
session_start();
require_once '../../koneksi.php';

$listing_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($listing_id <= 0) {
  header('Location: ../../index.php');
  exit;
}

$stmt = mysqli_prepare($koneksi, "
    SELECT l.*, u.nama AS host_nama, u.photo AS host_photo,
           u.tanggal_daftar AS host_bergabung,
           COUNT(DISTINCT b.id) AS total_bookings
    FROM listings l
    JOIN users u ON l.host_id = u.id
    LEFT JOIN bookings b ON b.listing_id = l.id AND b.status = 'dikonfirmasi'
    WHERE l.id = ? AND l.status = 'aktif'
    GROUP BY l.id
");
mysqli_stmt_bind_param($stmt, 'i', $listing_id);
mysqli_stmt_execute($stmt);
$listing = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$listing) {
  header('Location: ../../index.php');
  exit;
}

$stmt = mysqli_prepare($koneksi, "
    SELECT * FROM listing_photos
    WHERE listing_id = ?
    ORDER BY adalah_cover DESC, urutan ASC
");
mysqli_stmt_bind_param($stmt, 'i', $listing_id);
mysqli_stmt_execute($stmt);
$photos = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($koneksi, "
    SELECT r.*,
           u.nama   AS user_nama,
           u.lokasi AS user_lokasi,
           u.photo  AS user_photo
    FROM reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.listing_id = ?
    ORDER BY r.dibuat_pada DESC
");
mysqli_stmt_bind_param($stmt, 'i', $listing_id);
mysqli_stmt_execute($stmt);
$reviews = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$avg_rating = 0;
if (count($reviews) > 0) {
  $total_rating = array_sum(array_column($reviews, 'rating'));
  $avg_rating = round($total_rating / count($reviews), 1);
}

$stmt = mysqli_prepare($koneksi, "
    SELECT * FROM listing_amenities WHERE listing_id = ?
");
mysqli_stmt_bind_param($stmt, 'i', $listing_id);
mysqli_stmt_execute($stmt);
$amenities = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$amenity_icons = [
  'Wi-Fi' => ['ph-bold ph-wifi-high', 'Wi-Fi'],
  'TV' => ['ph-bold ph-television', 'TV'],
  'AC / Pendingin Ruangan' => ['ph-bold ph-snowflake', 'AC'],
  'Dapur' => ['ph-bold ph-cooking-pot', 'Dapur'],
  'Mesin Cuci' => ['ph-bold ph-washing-machine', 'Mesin Cuci'],
  'Parkir Gratis' => ['ph-bold ph-letter-circle-p', 'Parkir Gratis'],
  'Kolam Renang' => ['ph-bold ph-person-simple-swim', 'Kolam Renang'],
  'Kotak P3K' => ['ph-bold ph-first-aid-kit', 'Kotak P3K'],
  'Alat Pemadam' => ['ph-bold ph-fire-extinguisher', 'Alat Pemadam'],
  'Shower Air Panas' => ['ph-bold ph-shower', 'Shower Air Panas'],
  'Ruang Kerja' => ['ph-bold ph-desk', 'Ruang Kerja'],
  'Ramah Hewan Peliharaan' => ['ph-bold ph-dog', 'Ramah Hewan Peliharaan'],
];

$bulan_id = [
  'January' => 'Januari',
  'February' => 'Februari',
  'March' => 'Maret',
  'April' => 'April',
  'May' => 'Mei',
  'June' => 'Juni',
  'July' => 'Juli',
  'August' => 'Agustus',
  'September' => 'September',
  'October' => 'Oktober',
  'November' => 'November',
  'December' => 'Desember',
];

$host_initial = strtoupper(mb_substr($listing['host_nama'], 0, 1));
$host_year = date('Y', strtotime($listing['host_bergabung']));
$tipe_label = ucfirst($listing['tipe_properti']);
$can_review = isset($_SESSION['id']);

$stmt = mysqli_prepare($koneksi, "
    SELECT * FROM listing_rooms
    WHERE listing_id = ?
    ORDER BY urutan ASC, id ASC
");
mysqli_stmt_bind_param($stmt, 'i', $listing_id);
mysqli_stmt_execute($stmt);
$rooms = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($koneksi, "
    SELECT * FROM listing_policies
    WHERE listing_id = ?
    LIMIT 1
");
mysqli_stmt_bind_param($stmt, 'i', $listing_id);
mysqli_stmt_execute($stmt);
$policies = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$kebijakan_map = [
  'fleksibel' => 'Gratis hingga 24 jam sebelum check-in',
  'moderat' => 'Refund 50% jika dibatalkan 5 hari sebelum check-in',
  'ketat' => 'Tidak ada refund setelah konfirmasi',
];

if (!$policies) {
  $policies = [
    'jam_checkin' => $listing['jam_checkin'] ?? '14:00:00',
    'jam_checkout' => $listing['jam_checkout'] ?? '12:00:00',
    'kebijakan_pembatalan' => $kebijakan_map[$listing['kebijakan_pembatalan'] ?? 'fleksibel'],
    'boleh_hewan' => 0,
    'boleh_merokok' => 0,
    'boleh_anak' => 1,
    'catatan_tambahan' => '',
  ];
}

$jam_checkin = substr($policies['jam_checkin'], 0, 5);
$jam_checkout = substr($policies['jam_checkout'], 0, 5);
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($listing['judul']) ?> | Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../../components/navbar.css" />
  <link rel="stylesheet" href="../../components/footer.css" />
  <link rel="stylesheet" href="../../popups/auth.css" />
  <link rel="stylesheet" href="../../popups/review_popup.css" />
  <link rel="stylesheet" href="../styles/detail_card.css" />
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />

  <style>
    /* ── Room Selection States ───────────────────────── */
    .room-card {
      transition: border 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    }

    .room-card.selected {
      border: 2px solid #8b2500;
      box-shadow: 0 0 0 4px rgba(139, 37, 0, 0.12);
      background: rgba(139, 37, 0, 0.025);
    }

    .room-book-btn.is-selected {
      background: #8b2500 !important;
      color: #fff !important;
      opacity: 1 !important;
    }

    /* ── Sidebar room label ──────────────────────────── */
    .booking-selected-room {
      display: none;
      align-items: center;
      gap: 8px;
      font-size: 13px;
      color: #8b2500;
      background: rgba(139, 37, 0, 0.07);
      border-radius: 8px;
      padding: 8px 12px;
      margin-bottom: 12px;
      font-weight: 500;
    }

    .booking-selected-room.visible {
      display: flex;
    }

    .booking-selected-room i {
      font-size: 15px;
      flex-shrink: 0;
    }

    .booking-selected-room .clear-room {
      margin-left: auto;
      cursor: pointer;
      opacity: 0.6;
      transition: opacity 0.15s;
      background: none;
      border: none;
      padding: 0;
      color: inherit;
      font-size: 14px;
      line-height: 1;
    }

    .booking-selected-room .clear-room:hover {
      opacity: 1;
    }

    /* ── No room warning on submit ───────────────────── */
    .booking-no-room-warning {
      display: none;
      font-size: 12px;
      color: #c0392b;
      margin-top: 8px;
      text-align: center;
    }

    .booking-no-room-warning.visible {
      display: block;
    }
  </style>
</head>

<body>
  <header class="navbar">
    <nav class="navbar-container">
      <a href="../../index.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
        <img src="../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
      </div>
      <ul class="nav-menu">
        <li class="nav-item"><a href="../../index.php" class="nav-link">Cari Penginapan</a></li>
        <li class="nav-item"><a href="./promo_deals.php" class="nav-link">Promo &amp; Deals</a></li>
        <li class="nav-item"><a href="./become_host.php" class="nav-link">Jadi Host</a></li>
        <li class="nav-item"><a href="./about_us.php" class="nav-link">Tentang Kami</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <div class="nav-right">
        <a href="../../host/onboarding/pages/about_place.html">
          <button class="ghost-button">Ganti ke host</button>
        </a>
        <div class="icon-buttons">
          <button class="icon-button profile" aria-label="Profile">
            <?= isset($_SESSION['nama']) ? strtoupper(mb_substr($_SESSION['nama'], 0, 1)) : '' ?>
          </button>
          <button class="icon-button hamburger" aria-label="Hamburger">
            <i class="ph-bold ph-list"></i>
          </button>
        </div>
        <div id="hamburgerDropdown"></div>
      </div>
    </nav>
  </header>

  <main class="main-content">
    <span class="content-header">
      <h1 class="property-name"><?= htmlspecialchars($listing['judul']) ?></h1>
      <div class="header-buttons">
        <button class="header-button share" id="btnShare">
          <i class="ph-bold ph-share"></i> Bagikan
        </button>
        <button class="header-button save" id="btnSave">
          <i class="ph-bold ph-heart"></i> Simpan
        </button>
      </div>
    </span>

    <!-- GALLERY -->
    <section class="gallery-section">
      <div class="gallery-grid">
        <?php
        $cover = null;
        $thumbs = [];
        foreach ($photos as $p) {
          if ($p['adalah_cover'] && !$cover)
            $cover = $p;
          else
            $thumbs[] = $p;
        }
        $cover_src = $cover
          ? (str_starts_with($cover['nama_file'], 'http')
            ? $cover['nama_file']
            : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($cover['nama_file']))
          : 'https://placehold.co/800x500/8b2500/ffffff?text=' . urlencode($listing['judul']);
        ?>
        <div class="gallery-card big">
          <img src="<?= $cover_src ?>" alt="Foto Utama" class="gallery-image" />
        </div>
        <?php for ($i = 0; $i < 4; $i++):
          $src = isset($thumbs[$i])
            ? (str_starts_with($thumbs[$i]['nama_file'], 'http')
              ? $thumbs[$i]['nama_file']
              : '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($thumbs[$i]['nama_file']))
            : 'https://placehold.co/400x240/6b4f3a/ffffff?text=Foto+' . ($i + 1);
          ?>
          <div class="gallery-card">
            <img src="<?= $src ?>" alt="Foto <?= $i + 1 ?>" class="gallery-image" />
          </div>
        <?php endfor; ?>
      </div>
    </section>

    <!-- INFO -->
    <section class="info-section">
      <h2 class="info-title">
        <?= htmlspecialchars($tipe_label) ?> di <?= htmlspecialchars($listing['lokasi']) ?>
      </h2>
      <div class="info-body">
        <div class="info-meta">
          <div class="info-rating">
            <?php
            $full = floor($avg_rating);
            $half = ($avg_rating - $full) >= 0.5 ? 1 : 0;
            $empty = 5 - $full - $half;
            for ($i = 0; $i < $full; $i++)
              echo '<i class="ph-fill ph-star"></i>';
            if ($half)
              echo '<i class="ph-fill ph-star-half"></i>';
            for ($i = 0; $i < $empty; $i++)
              echo '<i class="ph-bold ph-star"></i>';
            ?>
          </div>
          <span class="info-reviews"><?= count($reviews) ?> ulasan</span>
          <span class="info-location">
            <i class="ph-fill ph-map-pin"></i>
            <?= htmlspecialchars($listing['lokasi']) ?>
          </span>
        </div>
        <div class="info-badges">
          <span class="info-badge"><?= htmlspecialchars($tipe_label) ?></span>
          <span class="info-badge"><?= $listing['max_tamu'] ?> Tamu</span>
          <span class="info-badge"><?= $listing['kamar_tidur'] ?> Kamar Tidur</span>
          <span class="info-badge"><?= $listing['kamar_mandi'] ?> Kamar Mandi</span>
          <?php if ($listing['tipe_booking'] === 'instan'): ?>
            <span class="info-badge">Booking Instan</span>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <div class="detail-layout">
      <div class="detail-main">

        <!-- DESKRIPSI -->
        <section class="description-section">
          <h3 class="section-title">Tentang Penginapan</h3>
          <?php foreach (array_filter(explode("\n", $listing['deskripsi'])) as $p): ?>
            <p class="description-text"><?= nl2br(htmlspecialchars(trim($p))) ?></p>
          <?php endforeach; ?>
        </section>

        <!-- FASILITAS UMUM -->
        <?php if (!empty($amenities)): ?>
          <section class="amenities-section">
            <h3 class="section-title">Fasilitas Umum</h3>
            <div class="amenities-grid">
              <?php foreach ($amenities as $am):
                $nama = $am['nama_fasilitas'];
                $icon = $amenity_icons[$nama][0] ?? 'ph-bold ph-check-circle';
                $label = $amenity_icons[$nama][1] ?? $nama;
                ?>
                <div class="amenity-item">
                  <span class="amenity-icon"><i class="<?= $icon ?>"></i></span>
                  <span class="amenity-label"><?= htmlspecialchars($label) ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <!-- PILIHAN KAMAR -->
        <?php if (!empty($rooms)): ?>
          <section class="rooms-section">
            <h2 class="section-title">Pilihan Kamar</h2>
            <div class="rooms-grid">
              <?php foreach ($rooms as $room):
                $fasilitas_kamar = json_decode($room['fasilitas'] ?? '[]', true) ?: [];
                ?>
                <!-- data-room-id ditambahkan untuk seleksi JS -->
                <div class="room-card" data-room-id="<?= $room['id'] ?>">

                  <!-- Foto / Placeholder -->
                  <div class="room-card-photo">
                    <?php if (!empty($room['foto'])): ?>
                      <?php $foto_src = str_starts_with($room['foto'], 'http')
                        ? $room['foto']
                        : '/teman_singgah/assets/uploads/rooms/' . htmlspecialchars($room['foto']); ?>
                      <img src="<?= $foto_src ?>" alt="<?= htmlspecialchars($room['nama']) ?>" />
                    <?php else: ?>
                      <div class="room-photo-placeholder">
                        <span><?= htmlspecialchars($room['nama']) ?></span>
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Body -->
                  <div class="room-card-body">

                    <h4 class="room-card-name"><?= htmlspecialchars($room['nama']) ?></h4>

                    <?php if (!empty($room['deskripsi'])): ?>
                      <p class="room-card-desc"><?= htmlspecialchars($room['deskripsi']) ?></p>
                    <?php endif; ?>

                    <div class="room-card-meta">
                      <?php if (!empty($room['ukuran_m2'])): ?>
                        <span>
                          <i class="ph-bold ph-arrows-out-simple"></i>
                          <?= (int) $room['ukuran_m2'] ?> m²
                        </span>
                      <?php endif; ?>
                      <span>
                        <i class="ph-bold ph-users"></i>
                        <?= (int) $room['max_tamu'] ?> Tamu
                      </span>
                    </div>

                    <?php if (!empty($fasilitas_kamar)): ?>
                      <ul class="room-amenities">
                        <?php foreach ($fasilitas_kamar as $f): ?>
                          <li><?= htmlspecialchars($f) ?></li>
                        <?php endforeach; ?>
                      </ul>
                    <?php endif; ?>

                    <!-- Footer: harga + tombol -->
                    <div class="room-card-footer">
                      <div class="room-price-wrap">
                        <span class="room-price-amount">
                          Rp <?= number_format($room['harga_malam'], 0, ',', '.') ?>
                        </span>
                        <span class="room-price-unit">/ malam</span>
                      </div>
                      <!-- Tombol sekarang tidak redirect, tapi trigger seleksi -->
                      <button class="room-book-btn" data-room-id="<?= $room['id'] ?>"
                        data-room-name="<?= htmlspecialchars($room['nama'], ENT_QUOTES) ?>"
                        data-room-price="<?= $room['harga_malam'] ?>">
                        Pilih Kamar
                      </button>
                    </div>

                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>

        <!-- KEBIJAKAN -->
        <section class="policy-section">
          <h2 class="section-title">Kebijakan Penginapan</h2>
          <div class="policy-grid">
            <div class="policy-item">
              <span class="policy-icon"><i class="ph-bold ph-clock"></i></span>
              <div class="policy-text">
                <strong>Check-in</strong>
                <span>Dari pukul <?= $jam_checkin ?> WIB</span>
              </div>
            </div>
            <div class="policy-item">
              <span class="policy-icon"><i class="ph-bold ph-clock"></i></span>
              <div class="policy-text">
                <strong>Check-out</strong>
                <span>Sebelum pukul <?= $jam_checkout ?> WIB</span>
              </div>
            </div>
            <div class="policy-item">
              <span class="policy-icon"><i class="ph-bold ph-prohibit"></i></span>
              <div class="policy-text">
                <strong>Pembatalan</strong>
                <span><?= htmlspecialchars($policies['kebijakan_pembatalan']) ?></span>
              </div>
            </div>
            <div class="policy-item">
              <span class="policy-icon">
                <i class="ph-bold ph-paw-print"></i>
              </span>
              <div class="policy-text">
                <strong>Hewan Peliharaan</strong>
                <span><?= $policies['boleh_hewan'] ? 'Diperbolehkan' : 'Tidak diperbolehkan' ?></span>
              </div>
            </div>
            <div class="policy-item">
              <span class="policy-icon">
                <i class="ph-bold <?= $policies['boleh_merokok'] ? 'ph-cigarette' : 'ph-cigarette-slash' ?>"></i>
              </span>
              <div class="policy-text">
                <strong>Merokok</strong>
                <span><?= $policies['boleh_merokok'] ? 'Diperbolehkan di area tertentu' : 'Dilarang di semua area dalam ruangan' ?></span>
              </div>
            </div>
            <div class="policy-item">
              <span class="policy-icon"><i class="ph-bold ph-baby"></i></span>
              <div class="policy-text">
                <strong>Anak-anak</strong>
                <span><?= $policies['boleh_anak'] ? 'Selamat datang, kasur tambahan tersedia' : 'Tidak diperbolehkan' ?></span>
              </div>
            </div>
          </div>

          <?php if (!empty($policies['catatan_tambahan'])): ?>
            <div class="policy-notes">
              <i class="ph-bold ph-note-pencil"></i>
              <p><?= nl2br(htmlspecialchars($policies['catatan_tambahan'])) ?></p>
            </div>
          <?php endif; ?>
        </section>

        <!-- MAP -->
        <section class="map-section">
          <h2 class="section-title">Lokasi</h2>
          <div id="propertyMap" class="map-container">
            <div class="custom-zoom-control">
              <button class="custom-zoom-button" id="zoomIn"><i class="ph-bold ph-plus"></i></button>
              <button class="custom-zoom-button" id="zoomOut"><i class="ph-bold ph-minus"></i></button>
            </div>
          </div>
          <p class="map-address"><?= htmlspecialchars($listing['lokasi']) ?></p>
        </section>

        <!-- ULASAN -->
        <section class="reviews-section">
          <div class="reviews-header">
            <h3 class="section-title">Ulasan Tamu</h3>
            <div class="reviews-summary-wrap">
              <?php if (count($reviews) > 0): ?>
                <div class="reviews-summary">
                  <span class="summary-rating"><i class="ph-fill ph-star"></i> <?= $avg_rating ?></span>
                  <span class="summary-text">dari 5 · <?= count($reviews) ?> ulasan</span>
                </div>
              <?php endif; ?>
              <?php if ($can_review): ?>
                <button class="write-review-btn" id="openReviewPopup">
                  <i class="ph-bold ph-pencil-simple"></i> Tulis Ulasan
                </button>
              <?php endif; ?>
            </div>
          </div>

          <?php if (empty($reviews)): ?>
            <div class="reviews-empty">
              <i class="ph-bold ph-chat-circle-dots"></i>
              <p>Belum ada ulasan untuk penginapan ini.</p>
            </div>
          <?php else: ?>
            <div class="reviews-grid">
              <?php foreach ($reviews as $rv):
                $initials = strtoupper(mb_substr($rv['user_nama'] ?? 'T', 0, 2));
                $rev_date = strtr(date('F Y', strtotime($rv['dibuat_pada'])), $bulan_id);
                $photo_url = null;
                if (!empty($rv['user_photo'])) {
                  $path = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $rv['user_photo'];
                  if (file_exists($path))
                    $photo_url = '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($rv['user_photo']);
                }
                ?>
                <article class="review-card">
                  <div class="review-top">
                    <?php if ($photo_url): ?>
                      <img src="<?= $photo_url ?>" alt="foto" class="review-avatar" style="padding:0;object-fit:cover;" />
                    <?php else: ?>
                      <div class="review-avatar"><?= $initials ?></div>
                    <?php endif; ?>
                    <div class="review-meta">
                      <strong class="review-name"><?= htmlspecialchars($rv['user_nama'] ?? 'Tamu') ?></strong>
                      <?php if (!empty($rv['user_lokasi'])): ?>
                        <span class="review-origin"><?= htmlspecialchars($rv['user_lokasi']) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="review-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                      <i class="<?= $i <= $rv['rating'] ? 'ph-fill' : 'ph-bold' ?> ph-star"></i>
                    <?php endfor; ?>
                  </div>
                  <span class="review-date"><?= $rev_date ?></span>
                  <p class="review-text"><?= htmlspecialchars($rv['komentar']) ?></p>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </section>

        <!-- HOST -->
        <section class="host-section">
          <h3 class="section-title">Tentang Host</h3>
          <div class="host-card">
            <div class="host-card-left">
              <?php
              $host_photo_path = !empty($listing['host_photo'])
                && file_exists($_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $listing['host_photo'])
                ? '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($listing['host_photo'])
                : null;
              ?>
              <?php if ($host_photo_path): ?>
                <div class="host-avatar" style="padding:0;overflow:hidden;">
                  <img src="<?= $host_photo_path ?>" alt="Foto Host"
                    style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
                </div>
              <?php else: ?>
                <div class="host-avatar"><?= $host_initial ?></div>
              <?php endif; ?>
              <div class="host-info">
                <span class="host-name"><?= htmlspecialchars($listing['host_nama']) ?></span>
                <span class="host-joined">Bergabung sejak <?= $host_year ?></span>
                <div class="host-stats">
                  <?php if ($avg_rating > 0): ?>
                    <span class="host-stat"><i class="ph-fill ph-star"></i> <?= $avg_rating ?> rating</span>
                  <?php endif; ?>
                  <span class="host-stat"><i class="ph-bold ph-chats"></i> Respons cepat</span>
                </div>
              </div>
            </div>
            <a href="./messages.php?host=<?= (int) $listing['host_id'] ?>&listing=<?= $listing_id ?>">
              <button class="host-chat-button">
                <i class="ph-bold ph-chat-circle-text"></i> Hubungi Host
              </button>
            </a>
          </div>
        </section>

      </div>

      <!-- BOOKING SIDEBAR -->
      <aside class="booking-sidebar">
        <div class="booking-card">
          <h3 class="booking-title">Pesan Kamar</h3>
          <div class="booking-price">
            <!-- id ditambahkan agar harga bisa diupdate JS saat kamar dipilih -->
            <span class="booking-price-amount" id="bookingPriceAmount">
              Rp <?= number_format($listing['harga_malam'], 0, ',', '.') ?>
            </span>
            <span class="booking-price-unit">/ malam</span>
          </div>

          <!-- Label kamar yang dipilih (tersembunyi sampai ada kamar dipilih) -->
          <div class="booking-selected-room" id="bookingSelectedRoom">
            <i class="ph-bold ph-bed"></i>
            <span id="bookingSelectedRoomName"></span>
            <button class="clear-room" id="clearRoomBtn" title="Hapus pilihan kamar">
              <i class="ph-bold ph-x"></i>
            </button>
          </div>

          <form class="booking-form" id="bookingForm">
            <!-- Hidden input untuk room_id yang dipilih -->
            <input type="hidden" id="selectedRoomIdInput" name="room_id" value="" />

            <div class="date-input-field">
              <div class="booking-field">
                <label for="checkin">Check-in</label>
                <input type="text" inputmode="date" class="calendar-input" id="checkinInput"
                  placeholder="Tambahkan Tanggal" required />
              </div>
              <div class="booking-field">
                <label for="checkout">Check-out</label>
                <input type="text" inputmode="date" class="calendar-input" id="checkoutInput"
                  placeholder="Tambahkan Tanggal" required />
              </div>
            </div>
            <div class="booking-field">
              <label>Jumlah Tamu</label>
              <input type="text" class="guest-input" id="guestInput" value="1 Pengunjung" />
            </div>
            <div class="booking-field">
              <label>Kode Promo</label>
              <input type="text" class="promo-input" id="promoInput" placeholder="Masukkan kode promo" />
            </div>

            <!-- Tombol submit — tidak pakai <a> wrapper, redirect dihandle JS -->
            <button type="button" class="booking-submit" id="bookingSubmitBtn">Pesan Sekarang</button>

            <!-- Warning muncul kalau ada banyak kamar tapi belum pilih -->
            <?php if (!empty($rooms)): ?>
              <p class="booking-no-room-warning" id="bookingNoRoomWarning">
                Pilih kamar terlebih dahulu dari daftar di atas.
              </p>
            <?php endif; ?>
          </form>
          <div id="bookingCalendarDropdown" class="calendar-dropdown"></div>
          <div id="bookingGuestDropdown" class="guest-counter-dropdown"></div>
        </div>
      </aside>
    </div>
  </main>

  <!-- POPUP TULIS ULASAN -->
  <?php if ($can_review): ?>
    <div id="reviewPopupOverlay" class="review-overlay" aria-hidden="true">
      <div class="review-popup" role="dialog" aria-modal="true" aria-labelledby="reviewPopupTitle">
        <div class="review-popup-header">
          <h2 id="reviewPopupTitle" class="review-popup-title">Tulis Ulasan</h2>
          <button class="review-popup-close" id="closeReviewPopup" aria-label="Tutup popup">
            <i class="ph-bold ph-x"></i>
          </button>
        </div>
        <div class="review-popup-body">
          <p class="review-popup-subtitle">
            Bagikan pengalaman menginapmu di
            <strong><?= htmlspecialchars($listing['judul']) ?></strong>
          </p>
          <div class="review-stars-wrap">
            <label class="review-field-label">Rating</label>
            <div class="star-selector" id="starSelector" role="group" aria-label="Pilih rating">
              <?php for ($s = 1; $s <= 5; $s++): ?>
                <button type="button" class="star-btn" data-value="<?= $s ?>" aria-label="<?= $s ?> bintang">
                  <i class="ph-bold ph-star"></i>
                </button>
              <?php endfor; ?>
            </div>
            <span class="star-label-text" id="starLabelText">Pilih rating</span>
          </div>
          <form id="reviewForm" class="review-form">
            <input type="hidden" name="listing_id" value="<?= $listing_id ?>" />
            <input type="hidden" name="rating" id="ratingInput" value="0" />
            <div class="review-field">
              <label class="review-field-label" for="reviewComment">Komentar</label>
              <textarea id="reviewComment" name="komentar" class="review-textarea"
                placeholder="Ceritakan pengalamanmu menginap di sini..." rows="5" maxlength="1000" required></textarea>
              <span class="review-char-count"><span id="charCount">0</span>/1000</span>
            </div>
            <div id="reviewError" class="review-error" style="display:none;"></div>
            <div id="reviewSuccess" class="review-success" style="display:none;"></div>
            <div class="review-popup-footer">
              <button type="button" class="review-cancel-btn" id="cancelReviewBtn">Batal</button>
              <button type="submit" class="review-submit-btn" id="submitReviewBtn">
                <i class="ph-bold ph-paper-plane-tilt"></i> Kirim Ulasan
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <footer class="footer">
    <div class="footer-grid">
      <div class="footer-column">
        <span class="footer-brand">Teman Singgah</span>
        <p class="footer-description">Platform booking penginapan terpercaya di seluruh Indonesia, dari hotel berbintang
          hingga homestay lokal.</p>
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
          <li><a href="../../index.php" class="footer-link">Beranda</a></li>
          <li><a href="./promo_deals.php" class="footer-link">Promo &amp; Deals</a></li>
          <li><a href="./become_host.php" class="footer-link">Jadi Host</a></li>
          <li><a href="./about_us.php" class="footer-link">Tentang Kami</a></li>
          <li><a href="./account.php" class="footer-link">Akun</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3 class="footer-title">Dukungan</h3>
        <ul class="footer-links">
          <li><a href="#" class="footer-link">Pusat Bantuan</a></li>
          <li><a href="#" class="footer-link">FAQ</a></li>
          <li><a href="./become_host.php" class="footer-link">Cara Menjadi Host</a></li>
          <li><a href="#" class="footer-link">Cara Booking</a></li>
          <li><a href="./about_us.php" class="footer-link">Tentang Kami</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <p class="footer-copyright">© 2026 Teman Singgah — All rights reserved.</p>
      <div class="footer-legal">
        <a href="" class="footer-link bottom">Kebijakan Privasi</a>
        <span class="footer-dot">•</span>
        <a href="" class="footer-link bottom">Syarat &amp; Ketentuan</a>
      </div>
    </div>
  </footer>

  <script>
    window.LISTING_LAT = <?= json_encode((float) ($listing['latitude'] ?? -8.5069)) ?>;
    window.LISTING_LNG = <?= json_encode((float) ($listing['longitude'] ?? 115.2625)) ?>;
    window.LISTING_NAME = <?= json_encode($listing['judul']) ?>;
    window.LISTING_LOC = <?= json_encode($listing['lokasi']) ?>;
  </script>

  <script src="../../components/navbar.js"></script>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <script src="../scripts/detail_card.js"></script>
  <?php if ($can_review): ?>
    <script src="../../popups/review_popup.js"></script>
  <?php endif; ?>

  <script>
    document.getElementById('btnShare')?.addEventListener('click', async () => {
      const data = {
        title: <?= json_encode($listing['judul']) ?>,
        text: 'Lihat penginapan ini di Teman Singgah!',
        url: window.location.href,
      };
      if (navigator.share) {
        try { await navigator.share(data); } catch (_) { }
      } else {
        await navigator.clipboard.writeText(window.location.href);
        const btn = document.getElementById('btnShare');
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="ph-bold ph-check"></i> Link disalin!';
        setTimeout(() => btn.innerHTML = orig, 2000);
      }
    });
    
    const SAVE_KEY = 'saved_listings';
    const listingId = <?= $listing_id ?>;
    const btnSave = document.getElementById('btnSave');

    function getSaved() {
      try { return JSON.parse(localStorage.getItem(SAVE_KEY) || '[]'); } catch { return []; }
    }
    function updateSaveBtn() {
      const saved = getSaved().includes(listingId);
      btnSave.classList.toggle('active', saved);
      btnSave.innerHTML = saved
        ? '<i class="ph-fill ph-heart"></i> Tersimpan'
        : '<i class="ph-bold ph-heart"></i> Simpan';
    }

    btnSave?.addEventListener('click', () => {
      let saved = getSaved();
      saved = saved.includes(listingId)
        ? saved.filter(id => id !== listingId)
        : [...saved, listingId];
      localStorage.setItem(SAVE_KEY, JSON.stringify(saved));
      updateSaveBtn();
    });

    updateSaveBtn();

    (function () {
      const BASE_PRICE = <?= (int) $listing['harga_malam'] ?>;
      const HAS_ROOMS = <?= !empty($rooms) ? 'true' : 'false' ?>;
      const BASE_LISTING_ID = <?= $listing_id ?>;

      let selectedRoomId = null;

      const priceEl = document.getElementById('bookingPriceAmount');
      const selectedRoomEl = document.getElementById('bookingSelectedRoom');
      const selectedRoomName = document.getElementById('bookingSelectedRoomName');
      const clearRoomBtn = document.getElementById('clearRoomBtn');
      const roomIdInput = document.getElementById('selectedRoomIdInput');
      const submitBtn = document.getElementById('bookingSubmitBtn');
      const noRoomWarning = document.getElementById('bookingNoRoomWarning');

      function formatRupiah(num) {
        return 'Rp ' + parseInt(num).toLocaleString('id-ID');
      }

      function clearSelection() {
        selectedRoomId = null;
        roomIdInput.value = '';

        document.querySelectorAll('.room-card').forEach(c => c.classList.remove('selected'));
        document.querySelectorAll('.room-book-btn').forEach(b => {
          b.classList.remove('is-selected');
          b.textContent = 'Pilih Kamar';
        });

        priceEl.textContent = formatRupiah(BASE_PRICE);
        selectedRoomEl.classList.remove('visible');
        selectedRoomName.textContent = '';
        if (noRoomWarning) noRoomWarning.classList.remove('visible');
      }

      function selectRoom(roomId, roomName, roomPrice) {
        selectedRoomId = roomId;
        roomIdInput.value = roomId;

        document.querySelectorAll('.room-card').forEach(c => {
          c.classList.toggle('selected', c.dataset.roomId === roomId);
        });
        document.querySelectorAll('.room-book-btn').forEach(b => {
          const isThis = b.dataset.roomId === roomId;
          b.classList.toggle('is-selected', isThis);
          b.textContent = isThis ? '✓ Dipilih' : 'Pilih Kamar';
        });

        priceEl.textContent = formatRupiah(roomPrice);

        selectedRoomName.textContent = roomName;
        selectedRoomEl.classList.add('visible');

        if (noRoomWarning) noRoomWarning.classList.remove('visible');

        document.querySelector('.booking-card')?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      }

      document.querySelectorAll('.room-book-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const roomId = btn.dataset.roomId;
          const roomName = btn.dataset.roomName;
          const roomPrice = btn.dataset.roomPrice;

          if (selectedRoomId === roomId) {
            clearSelection();
            return;
          }

          selectRoom(roomId, roomName, roomPrice);
        });
      });

      clearRoomBtn?.addEventListener('click', clearSelection);

      submitBtn?.addEventListener('click', () => {
        if (HAS_ROOMS && !selectedRoomId) {
          if (noRoomWarning) {
            noRoomWarning.classList.add('visible');
            document.querySelector('.rooms-section')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
          return;
        }

        const checkin = document.getElementById('checkinInput')?.value || '';
        const checkout = document.getElementById('checkoutInput')?.value || '';
        const promo = document.getElementById('promoInput')?.value || '';
        const guest = document.getElementById('guestInput')?.value || '';

        let url = `./payment_confirm.php?listing_id=${BASE_LISTING_ID}`;
        if (selectedRoomId) url += `&room_id=${encodeURIComponent(selectedRoomId)}`;
        if (checkin) url += `&checkin=${encodeURIComponent(checkin)}`;
        if (checkout) url += `&checkout=${encodeURIComponent(checkout)}`;
        if (guest) url += `&jumlah_tamu=${encodeURIComponent(guest)}`;
        if (promo) url += `&promo=${encodeURIComponent(promo)}`;

        window.location.href = url;
      });
    })();
  </script>
</body>

</html>