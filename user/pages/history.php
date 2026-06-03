<?php
session_start();
include "../../koneksi.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: /teman_singgah/index.php?auth=login");
  exit;
}

$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "s", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$user) {
  session_destroy();
  header("Location: /teman_singgah/index.php?auth=login");
  exit;
}

$inisial = strtoupper(mb_substr($user['nama'], 0, 1));
$photo_url = '';
if (!empty($user['photo'])) {
  if (str_starts_with($user['photo'], 'http')) {
    $photo_url = $user['photo'];
  } elseif (file_exists("../../assets/uploads/photos/" . $user['photo'])) {
    $photo_url = "/teman_singgah/assets/uploads/photos/" . htmlspecialchars($user['photo']);
  }
}

try {
    $stmt = mysqli_prepare($koneksi, "
        UPDATE bookings
        SET status = 'selesai'
        WHERE status = 'dikonfirmasi'
          AND checkout < CURDATE()
          AND user_id = ?
    ");
    mysqli_stmt_bind_param($stmt, 'i', $user['id']);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
} catch (mysqli_sql_exception $e) {
}

$filter = $_GET['filter'] ?? 'semua';
$allowed_filters = ['semua', 'menunggu', 'berlangsung', 'mendatang', 'selesai', 'dibatalkan'];
if (!in_array($filter, $allowed_filters))
  $filter = 'semua';

$where_status = '';
switch ($filter) {
  case 'menunggu':
    $where_status = "AND b.status = 'menunggu'";
    break;
  case 'berlangsung':
    $where_status = "AND b.status = 'dikonfirmasi' AND b.checkin <= CURDATE() AND b.checkout >= CURDATE()";
    break;
  case 'mendatang':
    $where_status = "AND b.status = 'dikonfirmasi' AND b.checkin > CURDATE()";
    break;
  case 'selesai':
    $where_status = "AND b.status = 'selesai'";
    break;
  case 'dibatalkan':
    $where_status = "AND b.status = 'dibatalkan'";
    break;
}

$sql = "
    SELECT
        b.id,
        b.checkin,
        b.checkout,
        b.jumlah_tamu,
        b.total_harga,
        b.status,
        b.metode_bayar,
        b.kode_promo,
        b.dibuat_pada,
        b.tipe_bayar,
        b.listing_id,
        l.judul         AS nama_listing,
        l.lokasi,
        l.tipe_properti,
        r.nama          AS nama_kamar,
        r.foto          AS foto_kamar,
        (SELECT lp.nama_file
         FROM listing_photos lp
         WHERE lp.listing_id = l.id AND lp.adalah_cover = 1
         LIMIT 1)       AS foto_cover
    FROM bookings b
    JOIN listings l ON l.id = b.listing_id
    LEFT JOIN listing_rooms r ON r.id = b.room_id
    WHERE b.user_id = ?
    $where_status
    ORDER BY b.dibuat_pada DESC
";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'i', $user['id']);
mysqli_stmt_execute($stmt);
$bookings = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// Ambil daftar booking_id yang sudah diulas oleh user ini
$reviewed_ids = [];
if (!empty($bookings)) {
  $booking_ids = array_column($bookings, 'id');
  $placeholders = implode(',', array_fill(0, count($booking_ids), '?'));
  $types = str_repeat('i', count($booking_ids));
  $stmt2 = mysqli_prepare($koneksi, "SELECT booking_id FROM reviews WHERE user_id = ? AND booking_id IN ($placeholders)");
  $params = array_merge([$user['id']], $booking_ids);
  mysqli_stmt_bind_param($stmt2, 'i' . $types, ...$params);
  mysqli_stmt_execute($stmt2);
  $res2 = mysqli_stmt_get_result($stmt2);
  while ($row = mysqli_fetch_assoc($res2)) {
    $reviewed_ids[] = $row['booking_id'];
  }
  mysqli_stmt_close($stmt2);
}

function get_badge(string $status, string $checkin, string $checkout): array
{
  $today = date('Y-m-d');
  $checkin = substr($checkin, 0, 10);
  $checkout = substr($checkout, 0, 10);
  if ($status === 'menunggu')
    return ['label' => 'Menunggu', 'class' => 'pending'];
  if ($status === 'dikonfirmasi') {
    if ($checkin <= $today && $checkout >= $today)
      return ['label' => 'Berlangsung', 'class' => 'ongoing'];
    if ($checkin > $today)
      return ['label' => 'Mendatang', 'class' => 'upcoming'];
    return ['label' => 'Dikonfirmasi', 'class' => 'upcoming'];
  }
  if ($status === 'selesai')
    return ['label' => 'Selesai', 'class' => 'completed'];
  if ($status === 'dibatalkan')
    return ['label' => 'Dibatalkan', 'class' => 'cancelled'];
  return ['label' => ucfirst($status), 'class' => 'pending'];
}

function jumlah_malam(string $checkin, string $checkout): int
{
  return (new DateTime($checkin))->diff(new DateTime($checkout))->days;
}

function fmt_tanggal(string $date): string
{
  return date('d M Y', strtotime($date));
}

function fmt_harga(float $harga): string
{
  return 'Rp ' . number_format($harga, 0, ',', '.');
}

function listing_img(array $b): string
{
  $foto = $b['foto_cover'] ?? '';
  if (empty($foto))
    return '';
  if (str_starts_with($foto, 'http'))
    return $foto;
  return "/teman_singgah/assets/uploads/listings/" . htmlspecialchars($foto);
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Riwayat Perjalanan | Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../../components/navbar.css" />
  <link rel="stylesheet" href="../../components/footer.css" />
  <link rel="stylesheet" href="../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/account.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
  <style>
    .badge-dot.pending   { background-color: #f59e0b; }
    .badge-dot.ongoing   { background-color: #16a34a; }
    .badge-dot.upcoming  { background-color: #2563eb; }
    .badge-dot.completed { background-color: #6b7280; }
    .badge-dot.cancelled { background-color: #dc2626; }

    /* ── Review Modal ── */
    .review-modal-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,.45);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 1rem;
    }
    .review-modal-overlay.open {
      display: flex;
    }
    .review-modal {
      background: #fff;
      border-radius: 1.25rem;
      padding: 2rem;
      width: 100%;
      max-width: 520px;
      max-height: 90vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 20px 60px rgba(0,0,0,.2);
      animation: modalIn .22s ease;
    }
    @keyframes modalIn {
      from { opacity:0; transform:translateY(20px) scale(.97); }
      to   { opacity:1; transform:translateY(0) scale(1); }
    }
    .review-modal-close {
      position: absolute;
      top: 1rem; right: 1rem;
      background: none;
      border: none;
      font-size: 1.4rem;
      cursor: pointer;
      color: #6b7280;
      line-height: 1;
      padding: 0.25rem;
      border-radius: 50%;
      transition: background .15s;
    }
    .review-modal-close:hover { background: #f3f4f6; }

    .review-modal-title {
      font-family: 'Inter', sans-serif;
      font-size: 1.3rem;
      font-weight: 700;
      margin: 0 0 1.25rem;
      color: #111;
    }

    /* Booking summary mini */
    .review-booking-info {
      display: flex;
      gap: .75rem;
      align-items: center;
      background: #f9fafb;
      border: 1px solid #e5e7eb;
      border-radius: .75rem;
      padding: .75rem;
      margin-bottom: 1.5rem;
    }
    .review-booking-img {
      width: 56px;
      height: 56px;
      border-radius: .5rem;
      object-fit: cover;
      flex-shrink: 0;
      background: #e5e7eb;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #9ca3af;
    }
    .review-booking-img img {
      width: 100%; height: 100%;
      object-fit: cover;
      border-radius: .5rem;
    }
    .review-booking-text h4 {
      font-size: .9rem;
      font-weight: 600;
      margin: 0 0 .2rem;
      color: #111;
    }
    .review-booking-text p {
      font-size: .75rem;
      color: #6b7280;
      margin: 0;
    }

    /* Stars */
    .star-group {
      display: flex;
      flex-direction: row-reverse;
      gap: .2rem;
      justify-content: flex-end;
      margin: .4rem 0 .5rem;
    }
    .star-group input[type="radio"] { display:none; }
    .star-group label {
      font-size: 2.2rem;
      color: #d1d5db;
      cursor: pointer;
      transition: color .12s, transform .12s;
      line-height: 1;
    }
    .star-group label:hover,
    .star-group label:hover ~ label,
    .star-group input[type="radio"]:checked ~ label { color: #f59e0b; }
    .star-group label:hover { transform: scale(1.12); }

    .star-hint {
      font-size: .78rem;
      color: #9ca3af;
      margin-bottom: 1.25rem;
      min-height: 1.2em;
    }

    .review-label {
      display: block;
      font-size: .85rem;
      font-weight: 600;
      color: #111;
      margin-bottom: .4rem;
    }
    .review-textarea {
      width: 100%;
      min-height: 120px;
      border: 1.5px solid #e5e7eb;
      border-radius: .75rem;
      padding: .75rem 1rem;
      font-family: 'Inter', sans-serif;
      font-size: .875rem;
      color: #111;
      resize: vertical;
      transition: border-color .2s;
      box-sizing: border-box;
    }
    .review-textarea:focus {
      outline: none;
      border-color: var(--color-primary, #7c3aed);
    }
    .char-count {
      font-size: .72rem;
      color: #9ca3af;
      text-align: right;
      margin: .2rem 0 1.25rem;
    }
    .review-submit {
      width: 100%;
      padding: .8rem;
      background: var(--color-primary, #7c3aed);
      color: #fff;
      border: none;
      border-radius: .75rem;
      font-family: 'Inter', sans-serif;
      font-size: .9rem;
      font-weight: 600;
      cursor: pointer;
      transition: opacity .2s;
    }
    .review-submit:hover { opacity: .9; }
    .review-submit:disabled { opacity: .5; cursor: not-allowed; }

    .review-alert-error {
      background: #fef2f2;
      color: #dc2626;
      border: 1px solid #fecaca;
      border-radius: .6rem;
      padding: .7rem .9rem;
      font-size: .82rem;
      margin-bottom: 1rem;
      display: none;
    }

    /* Success state inside modal */
    .review-success-inner {
      text-align: center;
      padding: 1.5rem 0 .5rem;
      display: none;
    }
    .review-success-icon {
      width: 60px; height: 60px;
      background: #d1fae5;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 1rem;
      font-size: 1.6rem;
      color: #16a34a;
    }
    .review-success-inner h3 {
      font-family: 'Inter', sans-serif;
      font-size: 1.2rem;
      font-weight: 700;
      margin: 0 0 .4rem;
    }
    .review-success-inner p {
      font-size: .85rem;
      color: #6b7280;
      margin: 0 0 1.25rem;
    }
    .review-success-inner button {
      padding: .7rem 1.5rem;
      background: var(--color-primary, #7c3aed);
      color: #fff;
      border: none;
      border-radius: .75rem;
      font-size: .875rem;
      font-weight: 600;
      cursor: pointer;
    }

    /* Already reviewed badge on button */
    .action-button.reviewed {
      opacity: .55;
      cursor: default;
      pointer-events: none;
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
        <li class="nav-item"><a href="promo_deals.php" class="nav-link">Promo &amp; Deals</a></li>
        <li class="nav-item"><a href="become_host.php" class="nav-link">Jadi Host</a></li>
        <li class="nav-item"><a href="about_us.php" class="nav-link">Tentang Kami</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <div class="nav-right">
        <a href="../../host/dashboard/pages/reservations.php">
          <button class="ghost-button">Ganti ke host</button>
        </a>
        <div class="icon-buttons">
          <?php if ($photo_url): ?>
            <button class="icon-button profile" aria-label="Profile" style="padding:0;overflow:hidden;">
              <img src="<?= $photo_url ?>" alt="Foto Profil"
                style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
            </button>
          <?php else: ?>
            <button class="icon-button profile" aria-label="Profile"><?= $inisial ?></button>
          <?php endif; ?>
          <button class="icon-button hamburger" aria-label="Hamburger">
            <i class="ph-bold ph-list"></i>
          </button>
        </div>
        <div id="hamburgerDropdown"></div>
        <div id="languagePopup"></div>
        <div id="authPopup"></div>
      </div>
    </nav>
  </header>

  <main class="main-content">
    <section class="account-hero">
      <div class="account-hero-container">
        <div class="account-hero-badge">
          <i class="ph-fill ph-suitcase"></i>
          <span>Perjalanan</span>
        </div>
        <h1 class="account-hero-title">Riwayat Perjalanan</h1>
        <p class="account-hero-subtitle">Lihat dan kelola semua perjalanan Anda di sini.</p>
      </div>
    </section>

    <section class="account-section">
      <div class="account-container">
        <aside class="account-sidebar">
          <h2 class="sidebar-title">Profil</h2>
          <nav class="sidebar-nav">
            <a href="./account.php" class="sidebar-link">
              <div class="link-icon primary-avatar" style="<?= $photo_url ? 'padding:0;overflow:hidden;' : '' ?>">
                <?php if ($photo_url): ?>
                  <img src="<?= $photo_url ?>" alt="Foto Profil"
                    style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
                <?php else: ?>
                  <?= $inisial ?>
                <?php endif; ?>
              </div>
              <span>Tentang Saya</span>
            </a>
            <a href="./history.php" class="sidebar-link active">
              <div class="link-icon"><i class="ph-bold ph-suitcase"></i></div>
              <span>Riwayat Perjalanan</span>
            </a>
            <a href="./settings.php" class="sidebar-link">
              <div class="link-icon"><i class="ph-bold ph-gear"></i></div>
              <span>Pengaturan</span>
            </a>
          </nav>
        </aside>

        <div class="account-content">
          <div class="content-header">
            <h2 class="content-title">Perjalanan Saya</h2>
          </div>

          <div class="filter-group">
            <?php
            $filters = [
              'semua'       => 'Semua',
              'menunggu'    => 'Menunggu',
              'berlangsung' => 'Berlangsung',
              'mendatang'   => 'Mendatang',
              'selesai'     => 'Selesai',
              'dibatalkan'  => 'Dibatalkan',
            ];
            foreach ($filters as $key => $label): ?>
              <a href="?filter=<?= $key ?>" class="filter-item <?= $filter === $key ? 'active' : '' ?>">
                <?= $label ?>
              </a>
            <?php endforeach; ?>
          </div>

          <?php if (empty($bookings)): ?>
            <div class="empty-state">
              <div class="empty-icon"><i class="ph-bold ph-suitcase"></i></div>
              <h3 class="empty-title">
                <?= $filter === 'semua' ? 'Belum Ada Perjalanan' : 'Tidak Ada Perjalanan' ?>
              </h3>
              <p class="empty-desc">
                <?php if ($filter === 'semua'): ?>
                  Anda belum memiliki riwayat perjalanan. Yuk jelajahi penginapan menarik dan buat pengalaman pertama Anda!
                <?php else: ?>
                  Tidak ada perjalanan dengan status <strong><?= $filters[$filter] ?></strong>.
                <?php endif; ?>
              </p>
              <a href="../../index.php" class="empty-button">
                <i class="ph-bold ph-magnifying-glass"></i>
                Cari Penginapan
              </a>
            </div>

          <?php else: ?>
            <div class="trips-list">
              <?php foreach ($bookings as $b):
                $badge      = get_badge($b['status'], $b['checkin'], $b['checkout']);
                $malam      = jumlah_malam($b['checkin'], $b['checkout']);
                $id_rsv     = '#RSV-' . date('Y', strtotime($b['dibuat_pada'])) . '-' . str_pad($b['id'], 4, '0', STR_PAD_LEFT);
                $img        = listing_img($b);
                $lokasi     = htmlspecialchars($b['lokasi'] ?? '');
                $nama       = htmlspecialchars($b['nama_listing']);
                $kamar      = $b['nama_kamar'] ? ' · ' . htmlspecialchars($b['nama_kamar']) : '';
                $sudah_review = in_array($b['id'], $reviewed_ids);
                ?>
                <div class="trip-card">
                  <div class="trip-image-container">
                    <?php if ($img): ?>
                      <img src="<?= $img ?>" class="trip-image" alt="<?= $nama ?>" />
                    <?php else: ?>
                      <div class="trip-image"
                        style="background:#e5e7eb;display:flex;align-items:center;justify-content:center;">
                        <i class="ph-bold ph-image" style="font-size:2rem;color:#9ca3af;"></i>
                      </div>
                    <?php endif; ?>
                    <div class="trip-badge">
                      <span class="badge-dot <?= $badge['class'] ?>"></span>
                      <?= $badge['label'] ?>
                    </div>
                  </div>
                  <div class="trip-content">
                    <h3 class="trip-name"><?= $nama ?></h3>
                    <?php if ($lokasi): ?>
                      <p class="trip-location"><?= $lokasi ?></p>
                    <?php endif; ?>
                    <p class="trip-meta">
                      <?= fmt_tanggal($b['checkin']) ?> – <?= fmt_tanggal($b['checkout']) ?>
                      · <?= intval($b['jumlah_tamu']) ?> tamu
                      · <?= $malam ?> malam<?= $kamar ?>
                    </p>
                    <div class="trip-price">
                      <?= fmt_harga($b['total_harga']) ?>
                      <span>/ total</span>
                    </div>
                    <p class="trip-id" style="font-size:0.75rem;color:var(--color-muted,#888);margin-top:2px;">
                      <?= $id_rsv ?>
                    </p>

                    <div class="trip-actions">
                      <?php if ($b['status'] === 'menunggu' || $b['status'] === 'dikonfirmasi'): ?>
                        <a href="./booking_detail.php?id=<?= $b['id'] ?>">
                          <button class="action-button primary">Lihat detail</button>
                        </a>
                        <a href="./cancel_booking.php?id=<?= $b['id'] ?>">
                          <button class="action-button secondary">Batalkan</button>
                        </a>
                      <?php elseif ($b['status'] === 'selesai'): ?>
                        <a href="../../index.php?listing=<?= $b['listing_id'] ?? '' ?>">
                          <button class="action-button primary">Pesan lagi</button>
                        </a>
                        <?php if ($sudah_review): ?>
                          <button class="action-button secondary reviewed" disabled title="Sudah diulas">
                            <i class="ph-bold ph-check"></i> Sudah Diulas
                          </button>
                        <?php else: ?>
                          <button
                            class="action-button secondary"
                            onclick="bukaReviewModal(<?= $b['id'] ?>, <?= (int)$b['listing_id'] ?>, <?= htmlspecialchars(json_encode($nama)) ?>, <?= htmlspecialchars(json_encode($lokasi)) ?>, <?= htmlspecialchars(json_encode($b['nama_kamar'] ?? '')) ?>, <?= htmlspecialchars(json_encode(fmt_tanggal($b['checkin']) . ' – ' . fmt_tanggal($b['checkout']))) ?>, <?= htmlspecialchars(json_encode($img)) ?>)"
                          >Tulis ulasan</button>
                        <?php endif; ?>
                      <?php elseif ($b['status'] === 'dibatalkan'): ?>
                        <a href="../../index.php">
                          <button class="action-button primary">Pesan lagi</button>
                        </a>
                        <a href="./booking_detail.php?id=<?= $b['id'] ?>">
                          <button class="action-button secondary">Lihat detail</button>
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </section>
  </main>

  <!-- ── Review Modal ── -->
  <div class="review-modal-overlay" id="reviewModalOverlay" onclick="tutupReviewModal(event)">
    <div class="review-modal" role="dialog" aria-modal="true" aria-labelledby="reviewModalTitle">
      <button class="review-modal-close" onclick="tutupReviewModal(null, true)" aria-label="Tutup">
        <i class="ph-bold ph-x"></i>
      </button>

      <!-- Form state -->
      <div id="reviewFormState">
        <h2 class="review-modal-title" id="reviewModalTitle">Tulis Ulasan</h2>

        <div class="review-booking-info" id="modalBookingInfo">
          <div class="review-booking-img" id="modalImg">
            <i class="ph-bold ph-image"></i>
          </div>
          <div class="review-booking-text">
            <h4 id="modalNama">—</h4>
            <p id="modalLokasi">—</p>
            <p id="modalTanggal">—</p>
          </div>
        </div>

        <div class="review-alert-error" id="reviewError"></div>

        <form id="reviewForm" onsubmit="submitReview(event)">
          <input type="hidden" id="modalBookingId" name="booking_id" value="" />
          <input type="hidden" id="modalListingId" name="listing_id" value="" />

          <label class="review-label">Rating Anda</label>
          <div class="star-group" id="starGroup">
            <input type="radio" name="rating" id="mstar5" value="5" /><label for="mstar5">★</label>
            <input type="radio" name="rating" id="mstar4" value="4" /><label for="mstar4">★</label>
            <input type="radio" name="rating" id="mstar3" value="3" /><label for="mstar3">★</label>
            <input type="radio" name="rating" id="mstar2" value="2" /><label for="mstar2">★</label>
            <input type="radio" name="rating" id="mstar1" value="1" /><label for="mstar1">★</label>
          </div>
          <p class="star-hint" id="starHint">Klik bintang untuk memberi rating</p>

          <label class="review-label" for="modalKomentar">Komentar</label>
          <textarea
            class="review-textarea"
            id="modalKomentar"
            name="komentar"
            maxlength="1000"
            placeholder="Ceritakan pengalaman menginap Anda — apa yang Anda suka, suasana, pelayanan, dll."
          ></textarea>
          <p class="char-count"><span id="charCount">0</span> / 1000 karakter</p>

          <button type="submit" class="review-submit" id="reviewSubmitBtn">
            <i class="ph-bold ph-paper-plane-tilt"></i>
            Kirim Ulasan
          </button>
        </form>
      </div>

      <!-- Success state -->
      <div class="review-success-inner" id="reviewSuccessState">
        <div class="review-success-icon"><i class="ph-bold ph-check"></i></div>
        <h3>Ulasan Terkirim!</h3>
        <p>Terima kasih telah berbagi pengalaman Anda. Ulasan ini sangat membantu tamu lainnya.</p>
        <button onclick="tutupReviewModal(null, true); location.reload();">Selesai</button>
      </div>
    </div>
  </div>

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
          <li><a href="promo_deals.php" class="footer-link">Promo &amp; Deals</a></li>
          <li><a href="become_host.php" class="footer-link">Jadi Host</a></li>
          <li><a href="./account.php" class="footer-link">Akun</a></li>
        </ul>
      </div>
      <div class="footer-column">
        <h3 class="footer-title">Dukungan</h3>
        <ul class="footer-links">
          <li><a href="" class="footer-link">Pusat Bantuan</a></li>
          <li><a href="" class="footer-link">FAQ</a></li>
          <li><a href="" class="footer-link">Cara Menjadi Host</a></li>
          <li><a href="" class="footer-link">Cara Booking</a></li>
          <li><a href="about_us.php" class="footer-link">Tentang Kami</a></li>
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

  <script src="../scripts/account.js"></script>
  <script src="../../components/navbar.js"></script>
  <script src="../../popups/auth.js"></script>
  <script>
    const starHints = ['', 'Sangat Buruk', 'Buruk', 'Cukup', 'Bagus', 'Sangat Bagus'];

    function bukaReviewModal(bookingId, listingId, nama, lokasi, kamar, tanggal, imgSrc) {
      // Reset form
      document.getElementById('reviewForm').reset();
      document.getElementById('charCount').textContent = '0';
      document.getElementById('starHint').textContent = 'Klik bintang untuk memberi rating';
      document.getElementById('reviewError').style.display = 'none';
      document.getElementById('reviewFormState').style.display = 'block';
      document.getElementById('reviewSuccessState').style.display = 'none';
      document.getElementById('reviewSubmitBtn').disabled = false;
      document.getElementById('reviewSubmitBtn').innerHTML = '<i class="ph-bold ph-paper-plane-tilt"></i> Kirim Ulasan';

      // Isi data booking
      document.getElementById('modalBookingId').value = bookingId;
      document.getElementById('modalListingId').value = listingId;
      document.getElementById('modalNama').textContent = nama;
      document.getElementById('modalLokasi').textContent = lokasi + (kamar ? ' · ' + kamar : '');
      document.getElementById('modalTanggal').textContent = tanggal;

      const imgEl = document.getElementById('modalImg');
      if (imgSrc) {
        imgEl.innerHTML = `<img src="${imgSrc}" alt="${nama}" />`;
      } else {
        imgEl.innerHTML = '<i class="ph-bold ph-image"></i>';
      }

      document.getElementById('reviewModalOverlay').classList.add('open');
      document.body.style.overflow = 'hidden';
    }

    function tutupReviewModal(e, force) {
      if (!force && e && e.target !== document.getElementById('reviewModalOverlay')) return;
      document.getElementById('reviewModalOverlay').classList.remove('open');
      document.body.style.overflow = '';
    }

    // Tutup dengan Escape
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape') tutupReviewModal(null, true);
    });

    // Star hints
    document.querySelectorAll('.star-group input[type="radio"]').forEach(r => {
      r.addEventListener('change', () => {
        document.getElementById('starHint').textContent = starHints[r.value] || '';
      });
    });
    document.querySelectorAll('.star-group label').forEach(lbl => {
      const val = lbl.getAttribute('for').replace('mstar', '');
      lbl.addEventListener('mouseenter', () => {
        document.getElementById('starHint').textContent = starHints[val] || '';
      });
      lbl.addEventListener('mouseleave', () => {
        const checked = document.querySelector('.star-group input:checked');
        document.getElementById('starHint').textContent =
          checked ? (starHints[checked.value] || '') : 'Klik bintang untuk memberi rating';
      });
    });

    // Char counter
    document.getElementById('modalKomentar').addEventListener('input', function () {
      document.getElementById('charCount').textContent = this.value.length;
    });

    // Submit via fetch
    async function submitReview(e) {
      e.preventDefault();
      const errorEl = document.getElementById('reviewError');
      errorEl.style.display = 'none';

      const bookingId = document.getElementById('modalBookingId').value;
      const rating    = document.querySelector('.star-group input[type="radio"]:checked')?.value;
      const komentar  = document.getElementById('modalKomentar').value.trim();

      if (!rating) {
        errorEl.textContent = 'Pilih rating antara 1 hingga 5 bintang.';
        errorEl.style.display = 'block';
        return;
      }
      if (komentar.length < 10) {
        errorEl.textContent = 'Komentar minimal 10 karakter.';
        errorEl.style.display = 'block';
        return;
      }

      const btn = document.getElementById('reviewSubmitBtn');
      btn.disabled = true;
      btn.textContent = 'Mengirim...';

      const formData = new FormData();
      formData.append('booking_id', bookingId);
      formData.append('listing_id', document.getElementById('modalListingId').value);
      formData.append('rating', rating);
      formData.append('komentar', komentar);

      try {
        const res  = await fetch('./submit_review.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
          document.getElementById('reviewFormState').style.display = 'none';
          document.getElementById('reviewSuccessState').style.display = 'block';
        } else {
          errorEl.textContent = data.message || 'Terjadi kesalahan, coba lagi.';
          errorEl.style.display = 'block';
          btn.disabled = false;
          btn.innerHTML = '<i class="ph-bold ph-paper-plane-tilt"></i> Kirim Ulasan';
        }
      } catch {
        errorEl.textContent = 'Koneksi gagal, coba lagi.';
        errorEl.style.display = 'block';
        btn.disabled = false;
        btn.innerHTML = '<i class="ph-bold ph-paper-plane-tilt"></i> Kirim Ulasan';
      }
    }
  </script>
</body>
</html>