<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

// ── AJAX handler ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json');

  $payout_id = (int) ($_POST['payout_id'] ?? 0);
  $action    = $_POST['action'] ?? '';

  if (!$payout_id || !in_array($action, ['proses', 'retry', 'cancel'], true)) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak valid.']);
    exit;
  }

  $row = $koneksi->query("SELECT status FROM payouts WHERE id = $payout_id LIMIT 1")->fetch_assoc();

  if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Payout tidak ditemukan.']);
    exit;
  }

  $valid = [
    'proses' => ['Dijadwalkan'],
    'retry'  => ['Failed'],
    'cancel' => ['Processing'],
  ];

  if (!in_array($row['status'], $valid[$action])) {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid untuk status saat ini.']);
    exit;
  }

  $now = date('Y-m-d H:i:s');

  $koneksi->begin_transaction();
  try {
    if ($action === 'proses') {
      $s = $koneksi->prepare("UPDATE payouts SET status='Processing', processed_at=?, updated_at=? WHERE id=?");
      $s->bind_param('ssi', $now, $now, $payout_id);
    } elseif ($action === 'retry') {
      $s = $koneksi->prepare("UPDATE payouts SET status='Dijadwalkan', failure_reason=NULL, processed_at=NULL, updated_at=? WHERE id=?");
      $s->bind_param('si', $now, $payout_id);
    } elseif ($action === 'cancel') {
      $reason = $koneksi->real_escape_string($_POST['reason'] ?? 'Dibatalkan admin');
      $s = $koneksi->prepare("UPDATE payouts SET status='Failed', failure_reason=?, updated_at=? WHERE id=?");
      $s->bind_param('ssi', $reason, $now, $payout_id);
    }
    $s->execute();
    $s->close();
    $koneksi->commit();
    echo json_encode(['success' => true]);
  } catch (Exception $e) {
    $koneksi->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  }
  exit;
}

// ── Filter & sort ─────────────────────────────────────────────────────────────
$allowed_statuses = ['Dijadwalkan', 'Processing', 'Completed', 'Failed'];
$filter_status    = isset($_GET['status']) && in_array($_GET['status'], $allowed_statuses)
  ? $_GET['status'] : null;

$sort_by  = ($_GET['sort'] ?? '') === 'payout_amount' ? 'payout_amount' : 'scheduled_date';
$sort_dir = strtoupper($_GET['dir'] ?? '') === 'ASC' ? 'ASC' : 'DESC';

$where = $filter_status
  ? "WHERE p.status = '" . $koneksi->real_escape_string($filter_status) . "'"
  : '';

// ── Fetch payouts ─────────────────────────────────────────────────────────────
$sql = "
    SELECT
        p.id, p.status, p.payout_amount,
        p.bank_name, p.account_number,
        p.scheduled_date, p.processed_at,
        p.failure_reason,
        u.nama AS host_nama
    FROM payouts p
    JOIN users u ON u.id = p.host_id
    $where
    ORDER BY p.$sort_by $sort_dir
";
$result  = $koneksi->query($sql);
$payouts = [];
while ($r = $result->fetch_assoc()) $payouts[] = $r;

// ── Helpers ───────────────────────────────────────────────────────────────────
function fmt_rupiah(int $n): string {
  return 'Rp' . number_format($n, 0, ',', '.');
}
function fmt_tgl(?string $d): string {
  if (!$d) return '—';
  $months = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  $ts = strtotime($d);
  return date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}
function fmt_dt(?string $d): string {
  if (!$d) return '—';
  $months = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
  $ts = strtotime($d);
  return date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . date('Y', $ts) . ', ' . date('H:i', $ts);
}
function badge(string $s): string {
  $map = [
    'Dijadwalkan' => ['warning', 'Dijadwalkan'],
    'Processing'  => ['info',    'Diproses'],
    'Completed'   => ['success', 'Selesai'],
    'Failed'      => ['error',   'Gagal'],
  ];
  [$cls, $lbl] = $map[$s] ?? ['warning', $s];
  return "<span class=\"table-badge $cls\"><span class=\"badge-dot\"></span>$lbl</span>";
}
function initials(string $nama): string {
  $p = explode(' ', trim($nama));
  $i = strtoupper(mb_substr($p[0], 0, 1));
  if (count($p) > 1) $i .= strtoupper(mb_substr($p[1], 0, 1));
  return $i;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pembayaran | Admin Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../dashboard.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
</head>
<body>
<div class="admin-layout">
  <aside class="sidebar">
    <div class="sidebar-header">
      <a href="dashboard.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="../../assets/logo/logo_temansinggah.svg" alt="Logo" class="logo-icon" />
        <img src="../../assets/logo/label_temansinggah.svg" alt="Teman Singgah" class="logo-name" />
      </div>
    </div>
    <nav class="sidebar-nav">
      <div class="nav-section">
        <div class="nav-section-title">Halaman Utama</div>
        <a href="dashboard.php" class="nav-item"><i class="ph-bold ph-squares-four"></i>Dashboard</a>
      </div>
      <div class="nav-section">
        <div class="nav-section-title">Manajemen</div>
        <a href="users.php" class="nav-item"><i class="ph-bold ph-users"></i>Pengguna</a>
        <a href="listings.php" class="nav-item"><i class="ph-bold ph-house"></i>Properti</a>
        <a href="reservations.php" class="nav-item"><i class="ph-bold ph-calendar-check"></i>Reservasi</a>
        <a href="transactions.php" class="nav-item"><i class="ph-bold ph-currency-circle-dollar"></i>Transaksi</a>
        <a href="/teman_singgah/admin/pages/promos.php" class="nav-item"><i class="ph-bold ph-tag"></i>Promo &amp; Deals</a>
      </div>
      <div class="nav-section">
        <div class="nav-section-title">Moderasi</div>
        <a href="reviews.php" class="nav-item"><i class="ph-bold ph-star"></i>Ulasan</a>
      </div>
      <div class="nav-section">
        <div class="nav-section-title">Keuangan</div>
        <a href="payouts.php" class="nav-item active"><i class="ph-bold ph-money"></i>Pembayaran</a>
      </div>
      <div class="nav-section">
        <div class="nav-section-title">Sistem</div>
        <a href="settings.php" class="nav-item"><i class="ph-bold ph-gear"></i>Pengaturan</a>
        <a href="logs.php" class="nav-item"><i class="ph-bold ph-notepad"></i>Aktivitas</a>
      </div>
    </nav>
  </aside>

  <div class="main-container">
    <header class="topbar">
      <div class="topbar-left">
        <h1 class="page-title">Pencairan Dana Host</h1>
      </div>
      <div class="topbar-right">
        <span class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
        <div class="user-avatar"><?= strtoupper(mb_substr($_SESSION['nama'] ?? 'A', 0, 1)) ?></div>
      </div>
    </header>

    <main class="content-area">
      <?php
        $status_qs = $filter_status ? "&status=$filter_status" : '';
        $next_sort = $sort_by === 'scheduled_date' ? 'payout_amount' : 'scheduled_date';
        $sort_label = $sort_by === 'payout_amount' ? 'Jumlah Payout' : 'Jadwal Transfer';
      ?>
      <div class="filter-container">
        <div class="filter-group">
          <a href="payouts.php" class="filter-item <?= !$filter_status ? 'active' : '' ?>">Semua</a>
          <a href="payouts.php?status=Dijadwalkan" class="filter-item <?= $filter_status === 'Dijadwalkan' ? 'active' : '' ?>">Dijadwalkan</a>
          <a href="payouts.php?status=Processing"  class="filter-item <?= $filter_status === 'Processing'  ? 'active' : '' ?>">Diproses</a>
          <a href="payouts.php?status=Completed"   class="filter-item <?= $filter_status === 'Completed'   ? 'active' : '' ?>">Selesai</a>
          <a href="payouts.php?status=Failed"      class="filter-item <?= $filter_status === 'Failed'      ? 'active' : '' ?>">Gagal</a>
        </div>
        <div class="sort-dropdown">
          <a href="payouts.php?sort=<?= $next_sort ?><?= $status_qs ?>" class="sort-button">
            <i class="ph-bold ph-faders-horizontal"></i>
            <span>Urutkan: <?= $sort_label ?></span>
            <i class="ph-bold ph-caret-down"></i>
          </a>
        </div>
      </div>

      <section class="table-section">
        <div class="table-container">
          <table>
            <thead>
              <tr>
                <th>Nama Host</th>
                <th>Jumlah Payout</th>
                <th>Bank Tujuan</th>
                <th>Jadwal Transfer</th>
                <th>Status</th>
                <th>Tanggal Diproses</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($payouts)): ?>
                <tr>
                  <td colspan="7" style="text-align:center;padding:2rem;color:var(--color-text-secondary)">
                    Tidak ada data pencairan dana.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($payouts as $p): ?>
                <tr>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar"><?= initials($p['host_nama']) ?></div>
                      <h3 class="table-name"><?= htmlspecialchars($p['host_nama']) ?></h3>
                    </div>
                  </td>
                  <td><?= fmt_rupiah((int)$p['payout_amount']) ?></td>
                  <td>
                    <?php if ($p['bank_name'] && $p['account_number']): ?>
                      <?= htmlspecialchars($p['bank_name']) ?> — <?= htmlspecialchars($p['account_number']) ?>
                    <?php else: ?>
                      <span style="color:var(--color-text-secondary)">Belum diisi</span>
                    <?php endif; ?>
                  </td>
                  <td><?= fmt_tgl($p['scheduled_date']) ?></td>
                  <td><?= badge($p['status']) ?></td>
                  <td><?= fmt_dt($p['processed_at']) ?></td>
                  <td>
                    <div class="action-group">
                      <?php if ($p['status'] === 'Dijadwalkan'): ?>
                        <button class="action-button success btn-proses" data-id="<?= $p['id'] ?>" title="Proses">
                          <i class="ph-bold ph-play-circle"></i>
                        </button>
                      <?php endif; ?>
                      <?php if ($p['status'] === 'Processing'): ?>
                        <button class="action-button error btn-cancel" data-id="<?= $p['id'] ?>" title="Batalkan">
                          <i class="ph-bold ph-x"></i>
                        </button>
                      <?php endif; ?>
                      <?php if ($p['status'] === 'Failed'): ?>
                        <button class="action-button success btn-retry" data-id="<?= $p['id'] ?>" title="Coba lagi">
                          <i class="ph-bold ph-arrow-clockwise"></i>
                        </button>
                      <?php endif; ?>
                      <?php if ($p['status'] === 'Completed'): ?>
                        <a href="payout_export.php?id=<?= $p['id'] ?>" class="action-button success" title="Unduh">
                          <i class="ph-bold ph-download"></i>
                        </a>
                      <?php endif; ?>
                      <button class="action-button info btn-detail" data-id="<?= $p['id'] ?>" title="Lihat">
                        <i class="ph-bold ph-eye"></i>
                      </button>
                    </div>
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

<script src="../dashboard.js"></script>
<script src="../scripts/payout_actions.js"></script>
</body>
</html>