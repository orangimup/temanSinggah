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

$booking_id = intval($_GET['id'] ?? 0);
if (!$booking_id) {
    header("Location: ./history.php");
    exit;
}

$sql = "
    SELECT
        b.*,
        l.judul            AS nama_listing,
        l.lokasi,
        l.tipe_properti,
        l.tipe_privasi,
        l.deskripsi        AS deskripsi_listing,
        l.jam_checkin,
        l.jam_checkout,
        l.kebijakan_pembatalan,
        l.max_tamu,
        l.kamar_tidur,
        l.kamar_mandi,
        l.harga_malam      AS harga_listing,
        r.nama             AS nama_kamar,
        r.foto             AS foto_kamar,
        r.deskripsi        AS deskripsi_kamar,
        r.ukuran_m2,
        r.fasilitas        AS fasilitas_kamar,
        (SELECT lp.nama_file
         FROM listing_photos lp
         WHERE lp.listing_id = l.id
           AND lp.adalah_cover = 1
         LIMIT 1) AS foto_cover
    FROM bookings b
    JOIN listings l ON l.id = b.listing_id
    LEFT JOIN listing_rooms r ON r.id = b.room_id
    WHERE b.id = ? AND b.user_id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'ii', $booking_id, $user['id']);
mysqli_stmt_execute($stmt);
$b = mysqli_stmt_get_result($stmt)->fetch_assoc();
mysqli_stmt_close($stmt);

if (!$b) {
    header("Location: ./history.php");
    exit;
}

function badge_class(string $status): string
{
    return match ($status) {
        'dikonfirmasi' => 'upcoming',
        'menunggu' => 'pending',
        'selesai' => 'completed',
        'dibatalkan' => 'cancelled',
        default => 'pending',
    };
}

function badge_label(string $status): string
{
    return match ($status) {
        'dikonfirmasi' => 'Dikonfirmasi',
        'menunggu' => 'Menunggu',
        'selesai' => 'Selesai',
        'dibatalkan' => 'Dibatalkan',
        default => ucfirst($status),
    };
}

function jumlah_malam(string $checkin, string $checkout): int
{
    return (new DateTime($checkin))->diff(new DateTime($checkout))->days;
}

function fmt_tanggal(string $date): string
{
    $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $d = date('d', strtotime($date));
    $m = $bulan[(int) date('n', strtotime($date)) - 1];
    $y = date('Y', strtotime($date));
    return "$d $m $y";
}

function fmt_harga(float $harga): string
{
    return 'Rp ' . number_format($harga, 0, ',', '.');
}

function get_foto(array $b): string
{
    $foto = !empty($b['foto_kamar']) ? $b['foto_kamar'] : ($b['foto_cover'] ?? '');
    if (empty($foto))
        return '';
    if (str_starts_with($foto, 'http'))
        return $foto;
    if (!empty($b['foto_kamar']))
        return "/teman_singgah/assets/uploads/rooms/" . htmlspecialchars($foto);
    return "/teman_singgah/assets/uploads/listings/" . htmlspecialchars($foto);
}

$malam = jumlah_malam($b['checkin'], $b['checkout']);
$id_rsv = '#RSV-' . date('Y', strtotime($b['dibuat_pada'])) . '-' . str_pad($b['id'], 4, '0', STR_PAD_LEFT);
$badge_cls = badge_class($b['status']);
$badge_lbl = badge_label($b['status']);
$foto = get_foto($b);
$fasilitas = [];
if (!empty($b['fasilitas_kamar'])) {
    $fasilitas = json_decode($b['fasilitas_kamar'], true) ?? [];
}

$biaya_layanan = round(floatval($b['total_harga']) * 0.05);
$subtotal = floatval($b['total_harga']) - $biaya_layanan;
$harga_per_malam_r = $malam > 0 ? round($subtotal / $malam) : $subtotal;
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Detail Pemesanan | Teman Singgah</title>
    <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
    <link rel="stylesheet" href="../../components/root.css" />
    <link rel="stylesheet" href="../../components/navbar.css" />
    <link rel="stylesheet" href="../../components/footer.css" />
    <link rel="stylesheet" href="../../popups/auth.css" />
    <link rel="stylesheet" href="../styles/account.css" />
    <link rel="stylesheet" href="../styles/booking_detail.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
        rel="stylesheet" />
    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
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
                <a href="../../host/onboarding/pages/about_place.html">
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
        <section class="account-hero" style="min-height:50vh;">
            <div class="account-hero-container">
                <div class="account-hero-badge">
                    <i class="ph-fill ph-receipt"></i>
                    <span>Detail Pemesanan</span>
                </div>
                <h1 class="account-hero-title">Detail Pemesanan</h1>
                <p class="account-hero-subtitle">Informasi lengkap pemesanan Anda.</p>
            </div>
        </section>

        <section class="account-section">
            <div class="detail-wrapper">
                <div class="detail-grid">
                    <div>
                        <div class="detail-card">
                            <?php if ($foto): ?>
                                <img src="<?= $foto ?>" class="booking-hero"
                                    alt="<?= htmlspecialchars($b['nama_listing']) ?>" />
                            <?php else: ?>
                                <div class="booking-hero-placeholder">
                                    <i class="ph-bold ph-image"></i>
                                </div>
                            <?php endif; ?>
                            <div class="listing-info">
                                <h2 class="listing-name"><?= htmlspecialchars($b['nama_listing']) ?></h2>
                                <?php if (!empty($b['lokasi'])): ?>
                                    <div class="listing-location">
                                        <i class="ph-bold ph-map-pin"></i>
                                        <?= htmlspecialchars($b['lokasi']) ?>
                                    </div>
                                <?php endif; ?>
                                <div class="listing-meta-chips">
                                    <?php if (!empty($b['tipe_properti'])): ?>
                                        <span class="meta-chip"><i
                                                class="ph-bold ph-buildings"></i><?= ucfirst($b['tipe_properti']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($b['nama_kamar'])): ?>
                                        <span class="meta-chip"><i
                                                class="ph-bold ph-door"></i><?= htmlspecialchars($b['nama_kamar']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($b['ukuran_m2'])): ?>
                                        <span class="meta-chip"><i class="ph-bold ph-ruler"></i><?= $b['ukuran_m2'] ?>
                                            m²</span>
                                    <?php endif; ?>
                                    <span class="meta-chip"><i
                                            class="ph-bold ph-users"></i><?= intval($b['jumlah_tamu']) ?> tamu</span>
                                </div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span class="detail-card-title"><i class="ph-bold ph-calendar-blank"></i> Jadwal
                                    Menginap</span>
                            </div>
                            <div class="detail-card-body">
                                <div class="date-row">
                                    <div class="date-box">
                                        <div class="date-box-label">Check-in</div>
                                        <div class="date-box-value"><?= fmt_tanggal($b['checkin']) ?></div>
                                        <?php if (!empty($b['jam_checkin'])): ?>
                                            <div class="date-box-sub">Mulai <?= substr($b['jam_checkin'], 0, 5) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="date-arrow">
                                        <i class="ph-bold ph-arrow-right"></i>
                                        <span class="date-arrow-nights"><?= $malam ?> malam</span>
                                    </div>
                                    <div class="date-box">
                                        <div class="date-box-label">Check-out</div>
                                        <div class="date-box-value"><?= fmt_tanggal($b['checkout']) ?></div>
                                        <?php if (!empty($b['jam_checkout'])): ?>
                                            <div class="date-box-sub">Sampai <?= substr($b['jam_checkout'], 0, 5) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($fasilitas)): ?>
                            <div class="detail-card">
                                <div class="detail-card-header">
                                    <span class="detail-card-title"><i class="ph-bold ph-sparkle"></i> Fasilitas
                                        Kamar</span>
                                </div>
                                <div class="detail-card-body">
                                    <div class="fasilitas-grid">
                                        <?php foreach ($fasilitas as $f): ?>
                                            <span class="fasilitas-item"><i
                                                    class="ph-bold ph-check"></i><?= htmlspecialchars($f) ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span class="detail-card-title"><i class="ph-bold ph-receipt"></i> Rincian Harga</span>
                            </div>
                            <div class="detail-card-body">
                                <div class="price-row">
                                    <span class="price-row-label"><?= fmt_harga($harga_per_malam_r) ?> × <?= $malam ?>
                                        malam</span>
                                    <span class="price-row-value"><?= fmt_harga($subtotal) ?></span>
                                </div>
                                <?php if (!empty($b['kode_promo'])): ?>
                                    <div class="price-row">
                                        <span class="price-row-label">Kode Promo <span
                                                class="promo-code">(<?= htmlspecialchars($b['kode_promo']) ?>)</span></span>
                                        <span class="price-row-value promo-applied">Diterapkan</span>
                                    </div>
                                <?php endif; ?>
                                <div class="price-row">
                                    <span class="price-row-label">Biaya Layanan</span>
                                    <span class="price-row-value"><?= fmt_harga($biaya_layanan) ?></span>
                                </div>
                                <?php if ($b['tipe_bayar'] === 'dp' && $b['dp_amount'] > 0): ?>
                                    <div class="price-row">
                                        <span class="price-row-label">DP <span class="badge-paid">Sudah
                                                Dibayar</span></span>
                                        <span class="price-row-value"><?= fmt_harga($b['dp_amount']) ?></span>
                                    </div>
                                    <div class="price-row">
                                        <span class="price-row-label sisa-label">Sisa Pembayaran</span>
                                        <span class="price-row-value sisa-value"><?= fmt_harga($b['sisa_bayar']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="price-row total">
                                    <span class="price-row-label">Total</span>
                                    <span class="price-row-value"><?= fmt_harga($b['total_harga']) ?></span>
                                </div>
                                <?php if ($b['tipe_bayar'] === 'dp' && $b['sisa_bayar'] > 0): ?>
                                    <div class="dp-info-box">
                                        <p>Masih ada sisa pembayaran sebesar
                                            <strong><?= fmt_harga($b['sisa_bayar']) ?></strong>. Lunasi sebelum tanggal
                                            check-in.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="sidebar-sticky">
                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span class="detail-card-title">Status Pemesanan</span>
                            </div>
                            <div class="detail-card-body">
                                <div class="status-large <?= $badge_cls ?>">
                                    <span class="badge-dot <?= $badge_cls ?>"></span>
                                    <?= $badge_lbl ?>
                                </div>
                                <p class="rsv-id"><?= $id_rsv ?></p>
                                <div>
                                    <div class="info-row">
                                        <span class="info-row-label"><i class="ph-bold ph-calendar-check"></i> Dipesan
                                            pada</span>
                                        <span class="info-row-value"><?= fmt_tanggal($b['dibuat_pada']) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-row-label"><i class="ph-bold ph-credit-card"></i> Metode
                                            Bayar</span>
                                        <span
                                            class="info-row-value"><?= htmlspecialchars($b['metode_bayar'] ?? '–') ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-row-label"><i class="ph-bold ph-wallet"></i> Tipe Bayar</span>
                                        <span
                                            class="info-row-value"><?= $b['tipe_bayar'] === 'lunas' ? 'Lunas' : 'DP / Cicil' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-row-label"><i class="ph-bold ph-users"></i> Jumlah Tamu</span>
                                        <span class="info-row-value"><?= intval($b['jumlah_tamu']) ?> orang</span>
                                    </div>
                                    <?php if (!empty($b['kode_promo'])): ?>
                                        <div class="info-row">
                                            <span class="info-row-label"><i class="ph-bold ph-tag"></i> Kode Promo</span>
                                            <span class="info-row-value"><?= htmlspecialchars($b['kode_promo']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($b['kebijakan_pembatalan'])): ?>
                                        <div class="info-row">
                                            <span class="info-row-label"><i class="ph-bold ph-shield-check"></i>
                                                Kebijakan</span>
                                            <span class="info-row-value"><?= ucfirst($b['kebijakan_pembatalan']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="detail-card">
                            <div class="detail-card-header">
                                <span class="detail-card-title">Aksi</span>
                            </div>
                            <div class="detail-card-body">
                                <div class="action-buttons-stack">
                                    <?php if ($b['status'] === 'menunggu' || $b['status'] === 'dikonfirmasi'): ?>
                                        <?php if ($b['tipe_bayar'] === 'dp' && $b['sisa_bayar'] > 0): ?>
                                            <a href="./payment.php?booking_id=<?= $b['id'] ?>"
                                                class="action-button-full primary">
                                                <i class="ph-bold ph-credit-card"></i> Lunasi Pembayaran
                                            </a>
                                        <?php endif; ?>
                                        <a href="./cancel_booking.php?id=<?= $b['id'] ?>" class="action-button-full danger">
                                            <i class="ph-bold ph-x-circle"></i> Batalkan Pemesanan
                                        </a>
                                    <?php elseif ($b['status'] === 'selesai'): ?>
                                        <a href="../../index.php?listing=<?= $b['listing_id'] ?? '' ?>"
                                            class="action-button-full primary">
                                            <i class="ph-bold ph-repeat"></i> Pesan Lagi
                                        </a>
                                        <a href="./review.php?booking_id=<?= $b['id'] ?>"
                                            class="action-button-full secondary">
                                            <i class="ph-bold ph-star"></i> Tulis Ulasan
                                        </a>
                                    <?php elseif ($b['status'] === 'dibatalkan'): ?>
                                        <a href="../../index.php" class="action-button-full primary">
                                            <i class="ph-bold ph-magnifying-glass"></i> Cari Penginapan Lain
                                        </a>
                                    <?php endif; ?>
                                    <a href="./history.php" class="action-button-full secondary">
                                        <i class="ph-bold ph-list"></i> Semua Perjalanan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
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
    <script src="../scripts/booking_detail.js"></script>
    <script src="../../components/navbar.js"></script>
    <script src="../../popups/auth.js"></script>
</body>

</html>