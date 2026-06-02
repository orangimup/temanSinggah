<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

// ── 1. Total Reservasi ────────────────────────────────────────────────────────
$total_reservasi = 0;
$r = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM bookings");
if ($r)
  $total_reservasi = mysqli_fetch_assoc($r)['total'];

// ── 2. Total Pendapatan (hanya transaksi sukses) ───────────────────────────────
$total_pendapatan = 0;
$r = mysqli_query($koneksi, "SELECT COALESCE(SUM(jumlah), 0) AS total FROM transactions WHERE status = 'sukses'");
if ($r)
  $total_pendapatan = mysqli_fetch_assoc($r)['total'];

// ── 3. Tingkat Hunian (%) ─────────────────────────────────────────────────────
// Rumus: booking dikonfirmasi atau selesai / total booking * 100
$tingkat_hunian = 0;
$r = mysqli_query($koneksi, "
    SELECT
        COUNT(*) AS total,
        SUM(status IN ('dikonfirmasi','selesai')) AS aktif
    FROM bookings
");
if ($r) {
  $row = mysqli_fetch_assoc($r);
  if ($row['total'] > 0)
    $tingkat_hunian = round(($row['aktif'] / $row['total']) * 100, 1);
}

// ── 4. Pengguna Aktif ─────────────────────────────────────────────────────────
$pengguna_aktif = 0;
$r = mysqli_query($koneksi, "SELECT COUNT(*) AS total FROM users WHERE role != 'Admin' AND status = 'Aktif'");
if ($r)
  $pengguna_aktif = mysqli_fetch_assoc($r)['total'];

// ── 5. Chart: Pendapatan & Jumlah Transaksi per Bulan (tahun ini) ──────────────
$tahun = date('Y');
$chart_labels = [];
$chart_pendapatan = [];
$chart_reservasi = [];

$nama_bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

// Inisialisasi semua bulan ke 0
for ($i = 1; $i <= 12; $i++) {
  $chart_labels[] = $nama_bulan[$i - 1];
  $chart_pendapatan[] = 0;
  $chart_reservasi[] = 0;
}

$r = mysqli_query($koneksi, "
    SELECT
        MONTH(dibayar_pada)     AS bulan,
        COALESCE(SUM(jumlah),0) AS pendapatan,
        COUNT(*)                AS jumlah_trx
    FROM transactions
    WHERE status = 'sukses'
      AND YEAR(dibayar_pada) = $tahun
    GROUP BY MONTH(dibayar_pada)
    ORDER BY bulan ASC
");
if ($r) {
  while ($row = mysqli_fetch_assoc($r)) {
    $idx = (int) $row['bulan'] - 1;
    $chart_pendapatan[$idx] = (float) $row['pendapatan'];
    $chart_reservasi[$idx] = (int) $row['jumlah_trx'];
  }
}

// ── 6. Reservasi Terbaru (8 data) ─────────────────────────────────────────────
$reservasi_terbaru = [];
$r = mysqli_query($koneksi, "
    SELECT
        b.id,
        b.checkin,
        b.total_harga,
        b.status,
        u.nama  AS nama_tamu,
        l.judul AS nama_listing
    FROM bookings b
    JOIN users    u ON u.id = b.user_id
    JOIN listings l ON l.id = b.listing_id
    ORDER BY b.dibuat_pada DESC
    LIMIT 8
");
if ($r) {
  while ($row = mysqli_fetch_assoc($r)) {
    $reservasi_terbaru[] = $row;
  }
}

// ── Helper: format angka pendapatan ringkas (892,5 Jt / 1,2 M) ────────────────
function format_pendapatan(float $n): string
{
  if ($n >= 1_000_000_000)
    return number_format($n / 1_000_000_000, 1, ',', '.') . ' M';
  if ($n >= 1_000_000)
    return number_format($n / 1_000_000, 1, ',', '.') . ' Jt';
  if ($n >= 1_000)
    return number_format($n / 1_000, 1, ',', '.') . ' Rb';
  return number_format($n, 0, ',', '.');
}

// Badge status reservasi
$badge_map = [
  'menunggu' => ['label' => 'Menunggu', 'class' => 'warning'],
  'dikonfirmasi' => ['label' => 'Dikonfirmasi', 'class' => 'success'],
  'dibatalkan' => ['label' => 'Dibatalkan', 'class' => 'error'],
  'selesai' => ['label' => 'Selesai', 'class' => 'info'],
];
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard | Admin Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />

  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../dashboard.css" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>

  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />

  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
</head>

<body>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <a href="../dashboard.php" class="logo-link"></a>
        <div class="logo-section">
          <img src="../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
          <img src="../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title">Halaman Utama</div>
          <a href="../dashboard.php" class="nav-item active">
            <i class="ph-bold ph-squares-four"></i>
            Dashboard
          </a>
        </div>

        <div class="nav-section">
          <div class="nav-section-title">Manajemen</div>
          <a href="../pages/users.php" class="nav-item">
            <i class="ph-bold ph-users"></i>
            Pengguna
          </a>
          <a href="../pages/listings.php" class="nav-item">
            <i class="ph-bold ph-house"></i>
            Properti
          </a>
          <a href="../pages/reservations.php" class="nav-item">
            <i class="ph-bold ph-calendar-check"></i>
            Reservasi
          </a>
          <a href="../pages/transactions.php" class="nav-item">
            <i class="ph-bold ph-currency-circle-dollar"></i>
            Transaksi
          </a>
          <a class="nav-item" href="/teman_singgah/admin/pages/promos.php">
            <i class="ph-bold ph-tag"></i>Promo & Deals
          </a>
        </div>

        <div class="nav-section">
          <div class="nav-section-title">Moderasi</div>
          <a href="../pages/reviews.php" class="nav-item">
            <i class="ph-bold ph-star"></i>
            Ulasan
          </a>
        </div>

        <div class="nav-section">
          <div class="nav-section-title">Keuangan</div>
          <a href="../pages/payouts.php" class="nav-item">
            <i class="ph-bold ph-money"></i>
            Pembayaran
          </a>
        </div>

        <div class="nav-section">
          <div class="nav-section-title">Sistem</div>
          <a href="../pages/settings.php" class="nav-item">
            <i class="ph-bold ph-gear"></i>
            Pengaturan
          </a>
          <a href="../pages/logs.php" class="nav-item">
            <i class="ph-bold ph-notepad"></i>
            Aktivitas
          </a>
        </div>
      </nav>
    </aside>

    <div class="main-container">
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">Dashboard</h1>
        </div>
        <div class="topbar-right">
          <span class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
          <div class="user-avatar"><?= strtoupper(substr($_SESSION['nama'] ?? 'A', 0, 1)) ?></div>
        </div>
      </header>

      <main class="content-area">
        <section class="metric-grid">
          <div class="metric-card">
            <div class="metric-icon">
              <i class="ph-bold ph-calendar-check"></i>
            </div>
            <div class="metric-text">
              <div class="metric-value"><?= number_format($total_reservasi, 0, ',', '.') ?></div>
              <div class="metric-label">Total Reservasi</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon">
              <i class="ph-bold ph-currency-circle-dollar"></i>
            </div>
            <div class="metric-text">
              <div class="metric-value"><?= format_pendapatan($total_pendapatan) ?></div>
              <div class="metric-label">Total Pendapatan</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon">
              <i class="ph-bold ph-chart-pie"></i>
            </div>
            <div class="metric-text">
              <div class="metric-value"><?= $tingkat_hunian ?>%</div>
              <div class="metric-label">Tingkat Hunian</div>
            </div>
          </div>

          <div class="metric-card">
            <div class="metric-icon">
              <i class="ph-bold ph-user-check"></i>
            </div>
            <div class="metric-text">
              <div class="metric-value"><?= number_format($pengguna_aktif, 0, ',', '.') ?></div>
              <div class="metric-label">Pengguna Aktif</div>
            </div>
          </div>
        </section>

        <section class="chart-section big">
          <div class="chart-card-header">
            <span class="chart-card-title">Pertumbuhan pendapatan</span>
            <span class="chart-card-subtitle">Pendapatan Tahun <?= $tahun ?></span>
          </div>
          <div id="chartWrap">
            <canvas id="revenueTrendChart" role="img" aria-label="Grafik tren pendapatan dan reservasi"></canvas>

            <div id="revTooltip">
              <div id="revTt-title"></div>
              <div class="tt-row">
                <div class="tt-box" style="background: #378add"></div>
                <span>Pendapatan</span>
                <span class="tt-val" id="revTt-val1"></span>
              </div>
              <div class="tt-row">
                <div class="tt-box" style="background: #e05b7a"></div>
                <span>Reservasi</span>
                <span class="tt-val" id="revTt-val2"></span>
              </div>
            </div>
          </div>
        </section>

        <section class="table-section">
          <div class="section-header">
            <h2 class="section-title">Reservasi Terbaru</h2>
            <a href="../pages/reservations.php" class="section-link">Lihat Semua</a>
          </div>
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>ID Reservasi</th>
                  <th>Nama Tamu</th>
                  <th>Listing</th>
                  <th>Check-in</th>
                  <th>Total</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($reservasi_terbaru)): ?>
                  <tr>
                    <td colspan="6" style="text-align:center;padding:32px;color:var(--color-text-hint);">
                      Belum ada data reservasi.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($reservasi_terbaru as $b):
                    $id_rsv = '#RSV-' . date('Y') . '-' . str_pad($b['id'], 4, '0', STR_PAD_LEFT);
                    $badge = $badge_map[$b['status']] ?? ['label' => ucfirst($b['status']), 'class' => 'info'];
                    ?>
                    <tr>
                      <td><span class="id-code"><?= htmlspecialchars($id_rsv) ?></span></td>
                      <td><?= htmlspecialchars($b['nama_tamu']) ?></td>
                      <td><?= htmlspecialchars($b['nama_listing']) ?></td>
                      <td><?= date('d M Y', strtotime($b['checkin'])) ?></td>
                      <td>Rp <?= number_format($b['total_harga'], 0, ',', '.') ?></td>
                      <td>
                        <span class="table-badge <?= $badge['class'] ?>">
                          <span class="badge-dot"></span>
                          <?= $badge['label'] ?>
                        </span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
      </main>
    </div>
  </div>

  <script>
    const chartLabels = <?= json_encode($chart_labels) ?>;
    const chartPendapatan = <?= json_encode($chart_pendapatan) ?>;
    const chartReservasi = <?= json_encode($chart_reservasi) ?>;
    const tahunChart = <?= json_encode($tahun) ?>;
  </script>
  <script src="../dashboard.js"></script>
</body>

</html>