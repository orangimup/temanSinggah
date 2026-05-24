<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aksi'], $_POST['review_id'])) {
  if ($_POST['aksi'] === 'tolak') {
    $review_id = (int) $_POST['review_id'];
    $del = mysqli_prepare($koneksi, "DELETE FROM reviews WHERE id = ?");
    mysqli_stmt_bind_param($del, 'i', $review_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);
  }
  header('Location: reviews.php');
  exit;
}

$sql = "
    SELECT r.*,
           u.nama   AS user_nama,
           u.photo  AS user_photo,
           l.judul  AS listing_judul
    FROM reviews r
    LEFT JOIN users u    ON r.user_id    = u.id
    LEFT JOIN listings l ON r.listing_id = l.id
    ORDER BY r.dibuat_pada DESC
";
$result  = mysqli_query($koneksi, $sql);
$reviews = mysqli_fetch_all($result, MYSQLI_ASSOC);

$bulan_id = [
  'January' => 'Januari', 'February' => 'Februari', 'March'    => 'Maret',
  'April'   => 'April',   'May'      => 'Mei',       'June'     => 'Juni',
  'July'    => 'Juli',    'August'   => 'Agustus',   'September'=> 'September',
  'October' => 'Oktober', 'November' => 'November',  'December' => 'Desember'
];

function potong_teks(string $teks, int $maks = 80): string {
  if (mb_strlen($teks) <= $maks) return htmlspecialchars($teks);
  return htmlspecialchars(mb_substr($teks, 0, $maks)) . '...';
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Moderasi Ulasan | Admin Teman Singgah</title>
  <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="/teman_singgah/components/root.css" />
  <link rel="stylesheet" href="/teman_singgah/admin/dashboard.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
  <style>
    /* ── Toolbar ── */
    .toolbar-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 16px;
    }

    .table-search-wrap {
      position: relative;
      display: flex;
      align-items: center;
      width: 100%;
      max-width: 320px;
      height: 40px;
      background: var(--color-bg-card);
      border: 1.5px solid var(--color-border);
      border-radius: var(--radius-full);
      box-sizing: border-box;
      transition: all var(--transition-fast);
    }

    .table-search-wrap:focus-within {
      border-color: var(--color-primary);
      box-shadow: 0 0 0 4px rgba(139, 37, 0, 0.08);
    }

    .table-search-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 1rem;
      color: var(--color-text-hint);
      pointer-events: none;
      z-index: 2;
    }

    .table-search-input {
      width: 100%;
      height: 100%;
      border: none;
      outline: none;
      background: transparent;
      font-family: var(--font-family);
      font-size: var(--text-sm);
      font-weight: var(--font-medium);
      color: var(--color-text-primary);
      padding: 0 16px 0 40px;
      box-sizing: border-box;
    }

    .table-search-input::placeholder {
      color: var(--color-text-hint);
      font-weight: var(--font-regular);
    }

    /* ── Sort dropdown ── */
    .sort-dropdown {
      position: relative;
      display: inline-block;
    }

    .sort-menu {
      display: none;
      position: absolute;
      right: 0;
      top: calc(100% + 8px);
      background: var(--color-bg-card);
      border: 1.5px solid var(--color-border-subtle);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-dropdown);
      z-index: var(--z-dropdown);
      min-width: 220px;
      overflow: hidden;
    }

    .sort-menu.open { display: block !important; }

    .sort-menu-item {
      display: flex;
      align-items: center;
      gap: var(--space-10);
      padding: var(--space-12) var(--space-16);
      font-size: var(--text-sm);
      font-weight: var(--font-medium);
      cursor: pointer;
      color: var(--color-text-secondary);
      transition: background var(--transition-fast);
      font-family: var(--font-family);
      user-select: none;
    }

    .sort-menu-item:hover { background: var(--color-bg-section); color: var(--color-text-primary); }

    .sort-menu-item.active {
      color: var(--color-primary);
      font-weight: var(--font-semibold);
      background: var(--color-primary-light);
    }

    .sort-menu-divider {
      height: 1.5px;
      background: var(--color-border-subtle);
      margin: 4px 0;
    }

    /* ── Review photo ── */
    .review-photo {
      width: 36px;
      height: 36px;
      border-radius: var(--radius-full);
      object-fit: cover;
      display: block;
    }

    /* ── Confirm overlay ── */
    .confirm-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: var(--color-bg-overlay);
      z-index: var(--z-modal);
      align-items: center;
      justify-content: center;
      backdrop-filter: blur(2px);
    }

    .confirm-overlay.show { display: flex; }

    .confirm-box {
      background: var(--color-bg-card);
      border-radius: var(--radius-3xl);
      padding: var(--space-32);
      max-width: 420px;
      width: 90%;
      box-shadow: var(--shadow-dropdown);
      border: 1.5px solid var(--color-border-subtle);
      display: flex;
      flex-direction: column;
      gap: var(--space-20);
    }

    .confirm-icon {
      width: 52px;
      height: 52px;
      border-radius: var(--radius-xl);
      background: var(--color-error-light);
      display: flex;
      align-items: center;
      justify-content: center;
      color: var(--color-error);
      font-size: var(--text-xl);
    }

    .confirm-body { display: flex; flex-direction: column; gap: var(--space-6); }

    .confirm-box h3 {
      font-family: var(--font-display);
      font-size: var(--text-lg);
      font-weight: var(--font-bold);
      color: var(--color-text-primary);
      margin: 0;
    }

    .confirm-box p {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
      margin: 0;
      line-height: 1.6;
    }

    .confirm-divider {
      height: 1.5px;
      background: var(--color-border-subtle);
      margin: 0 calc(var(--space-32) * -1);
    }

    .confirm-actions {
      display: flex;
      gap: var(--space-12);
      justify-content: flex-end;
    }

    .btn-batal {
      padding: var(--space-12) var(--space-20);
      border-radius: var(--radius-xl);
      border: 1.5px solid var(--color-border);
      background: transparent;
      color: var(--color-text-secondary);
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      cursor: pointer;
      font-family: var(--font-family);
      transition: all var(--transition-base);
      display: inline-flex;
      align-items: center;
      gap: var(--space-8);
    }

    .btn-batal:hover {
      border-color: var(--color-border-strong);
      color: var(--color-text-primary);
      background: var(--color-bg-section);
    }

    .btn-lihat {
      padding: var(--space-12) var(--space-20);
      border-radius: var(--radius-xl);
      border: 1.5px solid transparent;
      background: var(--color-info-light);
      color: var(--color-info);
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      cursor: pointer;
      font-family: var(--font-family);
      transition: all var(--transition-base);
      display: inline-flex;
      align-items: center;
      gap: var(--space-8);
    }

    .btn-lihat:hover { background: var(--color-info-light-hover); }

    .btn-tolak {
      padding: var(--space-12) var(--space-20);
      border-radius: var(--radius-xl);
      border: none;
      background: var(--color-error);
      color: var(--color-text-inverse);
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      cursor: pointer;
      font-family: var(--font-family);
      transition: all var(--transition-base);
      display: inline-flex;
      align-items: center;
      gap: var(--space-8);
    }

    .btn-tolak:hover  { background: var(--color-error-hover); }
    .btn-tolak:active { background: var(--color-error-active); transform: scale(0.99); }

    /* ── Detail panel ── */
    .detail-panel {
      position: fixed;
      top: 0;
      right: 0;
      bottom: 0;
      width: 400px;
      background: var(--color-bg-card);
      border-left: 1.5px solid var(--color-border-subtle);
      box-shadow: var(--shadow-search);
      z-index: var(--z-modal);
      transform: translateX(100%);
      transition: transform var(--transition-base);
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .detail-panel.show { transform: translateX(0); }

    .detail-panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: var(--space-24) var(--space-32);
      border-bottom: 1.5px solid var(--color-border-subtle);
      flex-shrink: 0;
    }

    .detail-panel-title {
      font-family: var(--font-display);
      font-size: var(--text-lg);
      font-weight: var(--font-bold);
      color: var(--color-text-primary);
    }

    .detail-close {
      width: 36px;
      height: 36px;
      border: 1.5px solid var(--color-border-subtle);
      border-radius: var(--radius-md);
      background: transparent;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      color: var(--color-text-secondary);
      font-size: var(--text-base);
      transition: all var(--transition-fast);
    }

    .detail-close:hover {
      border-color: var(--color-border-strong);
      color: var(--color-text-primary);
      background: var(--color-bg-section);
    }

    .detail-panel-body {
      flex: 1;
      overflow-y: auto;
      padding: var(--space-32);
      display: flex;
      flex-direction: column;
      gap: var(--space-24);
    }

    .detail-user {
      display: flex;
      align-items: center;
      gap: var(--space-16);
      padding: var(--space-20);
      background: var(--color-bg-section);
      border-radius: var(--radius-xl);
    }

    .detail-user-info h3 {
      font-size: var(--text-base);
      font-weight: var(--font-bold);
      color: var(--color-text-primary);
      margin-bottom: var(--space-2);
    }

    .detail-user-info small {
      font-size: var(--text-xs);
      color: var(--color-text-secondary);
    }

    .detail-section-label {
      font-size: var(--text-xs);
      font-weight: var(--font-semibold);
      color: var(--color-text-disabled);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: var(--space-8);
    }

    .detail-rating {
      display: flex;
      align-items: center;
      gap: var(--space-8);
    }

    .detail-rating .star-rating { font-size: var(--text-base); }

    .detail-rating span {
      font-size: var(--text-sm);
      font-weight: var(--font-semibold);
      color: var(--color-text-primary);
    }

    .detail-comment {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
      line-height: 1.7;
      background: var(--color-bg-section);
      border-radius: var(--radius-xl);
      padding: var(--space-20);
      border-left: 3px solid var(--color-primary-light-active);
    }

    .detail-date {
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
      display: flex;
      align-items: center;
      gap: var(--space-8);
    }

    .detail-panel-footer {
      padding: var(--space-24) var(--space-32);
      border-top: 1.5px solid var(--color-border-subtle);
      flex-shrink: 0;
    }

    .detail-delete-btn {
      width: 100%;
      padding: var(--space-14) var(--space-20);
      background: var(--color-error);
      color: var(--color-text-inverse);
      border: none;
      border-radius: var(--radius-xl);
      font-size: var(--text-base);
      font-weight: var(--font-semibold);
      cursor: pointer;
      font-family: var(--font-family);
      transition: all var(--transition-base);
      display: flex;
      align-items: center;
      justify-content: center;
      gap: var(--space-8);
    }

    .detail-delete-btn:hover  { background: var(--color-error-hover); }
    .detail-delete-btn:active { background: var(--color-error-active); transform: scale(0.99); }
  </style>
</head>

<body>
  <div class="admin-layout">

    <!-- ── Sidebar ── -->
    <aside class="sidebar">
      <div class="sidebar-header">
        <a href="/teman_singgah/admin/pages/dashboard.html" class="logo-link"></a>
        <div class="logo-section">
          <img src="/teman_singgah/assets/logo/logo_temansinggah.svg" alt="Logo" class="logo-icon" />
          <img src="/teman_singgah/assets/logo/label_temansinggah.svg" alt="Brand" class="logo-name" />
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title">Halaman Utama</div>
          <a href="/teman_singgah/admin/pages/dashboard.html" class="nav-item"><i class="ph-bold ph-squares-four"></i> Dashboard</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Manajemen</div>
          <a href="/teman_singgah/admin/pages/users.php"         class="nav-item"><i class="ph-bold ph-users"></i> Pengguna</a>
          <a href="/teman_singgah/admin/pages/listings.php"      class="nav-item"><i class="ph-bold ph-house"></i> Properti</a>
          <a href="/teman_singgah/admin/pages/reservations.html" class="nav-item"><i class="ph-bold ph-calendar-check"></i> Reservasi</a>
          <a href="/teman_singgah/admin/pages/transactions.html" class="nav-item"><i class="ph-bold ph-currency-circle-dollar"></i> Transaksi</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Moderasi</div>
          <a href="/teman_singgah/admin/pages/reviews.php" class="nav-item active"><i class="ph-bold ph-star"></i> Ulasan</a>
          <a href="/teman_singgah/admin/pages/reports.html"      class="nav-item"><i class="ph-bold ph-flag"></i> Laporan</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Keuangan</div>
          <a href="/teman_singgah/admin/pages/payouts.html" class="nav-item"><i class="ph-bold ph-money"></i> Pembayaran</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Sistem</div>
          <a href="/teman_singgah/admin/pages/settings.html" class="nav-item"><i class="ph-bold ph-gear"></i> Pengaturan</a>
          <a href="/teman_singgah/admin/pages/logs.html"     class="nav-item"><i class="ph-bold ph-notepad"></i> Aktivitas</a>
        </div>
      </nav>
    </aside>

    <!-- ── Main ── -->
    <div class="main-container">
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">Moderasi Ulasan</h1>
        </div>
        <div class="topbar-right">
          <span class="user-name"><?= htmlspecialchars($_SESSION['nama'] ?? 'Admin') ?></span>
          <div class="user-avatar"><?= strtoupper(mb_substr($_SESSION['nama'] ?? 'A', 0, 1)) ?></div>
        </div>
      </header>

      <main class="content-area">

        <!-- Toolbar -->
        <div class="toolbar-row">
          <div class="table-search-wrap">
            <i class="ph-bold ph-magnifying-glass table-search-icon"></i>
            <input type="search" id="adminSearch" class="table-search-input" placeholder="Cari nama atau listing..." />
          </div>

          <div class="sort-dropdown" id="sortDropdown">
            <button class="sort-button" id="sortToggleBtn" type="button">
              <i class="ph-bold ph-faders-horizontal"></i>
              <span id="sortLabel">Urutkan: Terbaru</span>
              <i class="ph-bold ph-caret-down"></i>
            </button>
            <div class="sort-menu" id="sortMenu">
              <div class="sort-menu-item active" data-sort="date_newest" data-label="Terbaru">
                <i class="ph-bold ph-calendar"></i> Tanggal Terbaru
              </div>
              <div class="sort-menu-item" data-sort="date_oldest" data-label="Terlama">
                <i class="ph-bold ph-calendar"></i> Tanggal Terlama
              </div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" data-sort="rating_high" data-label="Rating Tertinggi">
                <i class="ph-bold ph-star"></i> Rating Tertinggi
              </div>
              <div class="sort-menu-item" data-sort="rating_low" data-label="Rating Terendah">
                <i class="ph-bold ph-star"></i> Rating Terendah
              </div>
            </div>
          </div>
        </div>

        <!-- Table -->
        <section class="table-section">
          <div class="table-container">
            <table class="managed-table" id="reviewTable">
              <thead>
                <tr>
                  <th class="col-num">No.</th>
                  <th>Nama Tamu</th>
                  <th>Listing</th>
                  <th>Rating</th>
                  <th>Komentar</th>
                  <th>Tanggal</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($reviews)): ?>
                  <tr>
                    <td colspan="7" style="text-align:center;padding:48px;color:var(--color-text-hint);">
                      <i class="ph-bold ph-chat-circle-dots" style="font-size:2rem;display:block;margin-bottom:8px;"></i>
                      Belum ada ulasan.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($reviews as $rv):
                    $initials  = strtoupper(mb_substr($rv['user_nama'] ?? 'T', 0, 2));
                    $tanggal   = strtr(date('j F Y', strtotime($rv['dibuat_pada'])), $bulan_id);
                    $preview   = potong_teks($rv['komentar'], 80);
                    $photo_url = null;
                    if (!empty($rv['user_photo'])) {
                      $path = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $rv['user_photo'];
                      if (file_exists($path))
                        $photo_url = '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($rv['user_photo']);
                    }
                  ?>
                    <tr data-nama="<?= htmlspecialchars($rv['user_nama'] ?? '') ?>"
                        data-listing="<?= htmlspecialchars($rv['listing_judul'] ?? '') ?>"
                        data-rating="<?= (int) $rv['rating'] ?>"
                        data-tanggal="<?= htmlspecialchars($rv['dibuat_pada']) ?>">
                      <td class="col-num"></td>
                      <td>
                        <div class="table-cell">
                          <?php if ($photo_url): ?>
                            <img src="<?= $photo_url ?>" alt="foto" class="table-avatar review-photo" style="padding:0;" />
                          <?php else: ?>
                            <div class="table-avatar"><?= $initials ?></div>
                          <?php endif; ?>
                          <h3 class="table-name"><?= htmlspecialchars($rv['user_nama'] ?? 'Tamu') ?></h3>
                        </div>
                      </td>
                      <td><?= htmlspecialchars($rv['listing_judul'] ?? '—') ?></td>
                      <td>
                        <div class="star-rating">
                          <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="<?= $i <= $rv['rating'] ? 'ph-fill' : 'ph-bold' ?> ph-star"></i>
                          <?php endfor; ?>
                        </div>
                      </td>
                      <td>
                        <div class="table-description" title="<?= htmlspecialchars($rv['komentar']) ?>">
                          <?= $preview ?>
                        </div>
                      </td>
                      <td><?= $tanggal ?></td>
                      <td>
                        <div class="action-group">
                          <button class="action-button info" title="Lihat Detail"
                            data-id="<?= $rv['id'] ?>"
                            data-nama="<?= htmlspecialchars($rv['user_nama'] ?? 'Tamu') ?>">
                            <i class="ph-bold ph-eye"></i>
                          </button>
                          <button class="action-button error" title="Tolak & Hapus"
                            data-id="<?= $rv['id'] ?>"
                            data-nama="<?= htmlspecialchars($rv['user_nama'] ?? 'Tamu') ?>">
                            <i class="ph-bold ph-trash"></i>
                          </button>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>

            <div class="table-pagination">
              <span class="pagination-info" id="paginationInfo"></span>
              <div class="pagination-controls" id="paginationControls"></div>
            </div>
          </div>
        </section>

      </main>
    </div>
  </div>

  <!-- ── Confirm overlay ── -->
  <div class="confirm-overlay" id="confirmOverlay">
    <div class="confirm-box">
      <div class="confirm-icon">
        <i class="ph-bold ph-trash"></i>
      </div>
      <div class="confirm-body">
        <h3>Tolak Ulasan?</h3>
        <p id="confirmMsg"></p>
      </div>
      <div class="confirm-divider"></div>
      <div class="confirm-actions">
        <button class="btn-batal" id="btnBatal" type="button">
          <i class="ph-bold ph-x"></i> Batal
        </button>
        <button class="btn-lihat" id="btnLihat" type="button">
          <i class="ph-bold ph-eye"></i> Lihat Detail
        </button>
        <form method="POST" style="display:inline;">
          <input type="hidden" name="aksi" value="tolak" />
          <input type="hidden" name="review_id" id="confirmReviewId" />
          <button type="submit" class="btn-tolak">
            <i class="ph-bold ph-trash"></i> Ya, Hapus
          </button>
        </form>
      </div>
    </div>
  </div>

  <!-- ── Detail panel ── -->
  <div class="detail-panel" id="detailPanel">
    <div class="detail-panel-header">
      <span class="detail-panel-title">Detail Ulasan</span>
      <button class="detail-close" id="detailClose" type="button">
        <i class="ph-bold ph-x"></i>
      </button>
    </div>
    <div class="detail-panel-body">
      <div class="detail-user">
        <div id="detailAvatar"></div>
        <div class="detail-user-info">
          <h3 id="detailNama"></h3>
          <small id="detailListingLabel"></small>
        </div>
      </div>
      <div>
        <div class="detail-section-label">Rating</div>
        <div class="detail-rating">
          <div class="star-rating" id="detailStars"></div>
          <span id="detailRatingNum"></span>
        </div>
      </div>
      <div>
        <div class="detail-section-label">Komentar</div>
        <div class="detail-comment" id="detailKomentar"></div>
      </div>
      <div>
        <div class="detail-section-label">Tanggal</div>
        <div class="detail-date">
          <i class="ph-bold ph-calendar"></i>
          <span id="detailTanggal"></span>
        </div>
      </div>
    </div>
    <div class="detail-panel-footer">
      <form method="POST">
        <input type="hidden" name="aksi" value="tolak" />
        <input type="hidden" name="review_id" id="detailDeleteId" />
        <button type="submit" class="detail-delete-btn">
          <i class="ph-bold ph-trash"></i> Tolak & Hapus Ulasan
        </button>
      </form>
    </div>
  </div>

  <script src="/teman_singgah/admin/dashboard.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const ROWS_PER_PAGE = 10;
      const table      = document.getElementById('reviewTable');
      const tbody      = table.querySelector('tbody');
      const masterRows = Array.from(tbody.querySelectorAll('tr[data-tanggal]'));

      let state     = { sort: 'date_newest', search: '', page: 1 };
      let activeRow = null;

      /* ── Render ── */
      function render() {
        const q = state.search;

        let rows = masterRows.filter(row =>
          !q ||
          (row.dataset.nama    || '').toLowerCase().includes(q) ||
          (row.dataset.listing || '').toLowerCase().includes(q)
        );

        rows = [...rows].sort((a, b) => {
          switch (state.sort) {
            case 'date_newest': return new Date(b.dataset.tanggal) - new Date(a.dataset.tanggal);
            case 'date_oldest': return new Date(a.dataset.tanggal) - new Date(b.dataset.tanggal);
            case 'rating_high': return Number(b.dataset.rating)    - Number(a.dataset.rating);
            case 'rating_low':  return Number(a.dataset.rating)    - Number(b.dataset.rating);
            default: return 0;
          }
        });

        const total      = rows.length;
        const totalPages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
        if (state.page > totalPages) state.page = totalPages;
        const start    = (state.page - 1) * ROWS_PER_PAGE;
        const pageRows = rows.slice(start, start + ROWS_PER_PAGE);

        masterRows.forEach(r => { r.style.display = 'none'; });
        pageRows.forEach((r, i) => {
          tbody.appendChild(r);
          r.style.display = '';
          const cell = r.querySelector('.col-num');
          if (cell) cell.textContent = start + i + 1;
        });

        renderPagination(total, totalPages);
      }

      /* ── Pagination ── */
      function renderPagination(total, totalPages) {
        const infoEl = document.getElementById('paginationInfo');
        const ctrlEl = document.getElementById('paginationControls');
        const s = total === 0 ? 0 : (state.page - 1) * ROWS_PER_PAGE + 1;
        const e = Math.min(state.page * ROWS_PER_PAGE, total);
        infoEl.textContent = total === 0 ? 'Tidak ada data' : `${s}–${e} dari ${total} ulasan`;
        ctrlEl.innerHTML   = '';

        function mkBtn(html, disabled, cb) {
          const btn = document.createElement('button');
          btn.className = 'page-btn nav-btn';
          btn.innerHTML = html;
          btn.disabled  = disabled;
          if (!disabled) btn.addEventListener('click', () => { cb(); render(); });
          return btn;
        }

        ctrlEl.appendChild(mkBtn('<i class="ph-bold ph-caret-left"></i>', state.page <= 1, () => state.page--));

        buildPageList(state.page, totalPages).forEach(p => {
          if (p === '...') {
            const sp = document.createElement('span');
            sp.className   = 'page-ellipsis';
            sp.textContent = '…';
            ctrlEl.appendChild(sp);
          } else {
            const btn = document.createElement('button');
            btn.className   = 'page-btn' + (p === state.page ? ' active' : '');
            btn.textContent = p;
            btn.addEventListener('click', () => { state.page = p; render(); });
            ctrlEl.appendChild(btn);
          }
        });

        ctrlEl.appendChild(mkBtn('<i class="ph-bold ph-caret-right"></i>', state.page >= totalPages, () => state.page++));
      }

      function buildPageList(cur, total) {
        if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
        const pages = [1];
        if (cur > 3) pages.push('...');
        for (let p = Math.max(2, cur - 1); p <= Math.min(total - 1, cur + 1); p++) pages.push(p);
        if (cur < total - 2) pages.push('...');
        pages.push(total);
        return pages;
      }

      /* ── Sort dropdown ── */
      const sortDropdown  = document.getElementById('sortDropdown');
      const sortToggleBtn = document.getElementById('sortToggleBtn');
      const sortMenu      = document.getElementById('sortMenu');
      const sortLabel     = document.getElementById('sortLabel');
      let sortOpen = false;

      function openSort()  { sortOpen = true;  sortMenu.classList.add('open'); }
      function closeSort() { sortOpen = false; sortMenu.classList.remove('open'); }

      sortToggleBtn.addEventListener('mousedown', function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        sortOpen ? closeSort() : openSort();
      });

      sortMenu.querySelectorAll('.sort-menu-item').forEach(item => {
        item.addEventListener('mousedown', function (e) {
          e.preventDefault();
          e.stopImmediatePropagation();
          state.sort  = this.dataset.sort;
          state.page  = 1;
          sortLabel.textContent = 'Urutkan: ' + this.dataset.label;
          sortMenu.querySelectorAll('.sort-menu-item').forEach(i => i.classList.remove('active'));
          this.classList.add('active');
          closeSort();
          render();
        });
      });

      document.addEventListener('mousedown', function (e) {
        if (!sortDropdown.contains(e.target)) closeSort();
      });

      /* ── Search ── */
      document.getElementById('adminSearch').addEventListener('input', function () {
        state.search = this.value.trim().toLowerCase();
        state.page   = 1;
        render();
      });

      /* ── Detail panel ── */
      function openDetail(row) {
        const deletBtn  = row.querySelector('.action-button.error');
        const id        = deletBtn.dataset.id;
        const nama      = row.dataset.nama;
        const listing   = row.dataset.listing;
        const rating    = Number(row.dataset.rating);
        const tgl       = row.querySelector('td:nth-child(6)').textContent.trim();
        const komentar  = row.querySelector('.table-description').getAttribute('title');
        const photoEl   = row.querySelector('.review-photo');

        const avatarWrap = document.getElementById('detailAvatar');
        if (photoEl) {
          avatarWrap.innerHTML = `<img src="${photoEl.src}" class="table-avatar review-photo" style="width:48px;height:48px;padding:0;" />`;
        } else {
          const initials = nama ? nama.substring(0, 2).toUpperCase() : 'T';
          avatarWrap.innerHTML = `<div class="table-avatar" style="width:48px;height:48px;font-size:1rem;">${initials}</div>`;
        }

        document.getElementById('detailNama').textContent         = nama    || 'Tamu';
        document.getElementById('detailListingLabel').textContent = listing || '—';
        document.getElementById('detailKomentar').textContent     = komentar || '';
        document.getElementById('detailTanggal').textContent      = tgl;
        document.getElementById('detailDeleteId').value           = id;
        document.getElementById('detailRatingNum').textContent    = rating + '/5';

        let stars = '';
        for (let i = 1; i <= 5; i++)
          stars += `<i class="${i <= rating ? 'ph-fill' : 'ph-bold'} ph-star"></i>`;
        document.getElementById('detailStars').innerHTML = stars;

        document.getElementById('detailPanel').classList.add('show');
      }

      /* ── Event: tombol di tabel ── */
      tbody.addEventListener('click', e => {
        // Tombol lihat
        const btnLihat = e.target.closest('.action-button.info');
        if (btnLihat) {
          activeRow = btnLihat.closest('tr');
          openDetail(activeRow);
          return;
        }
        // Tombol hapus
        const btnHapus = e.target.closest('.action-button.error');
        if (btnHapus) {
          activeRow = btnHapus.closest('tr');
          document.getElementById('confirmReviewId').value = btnHapus.dataset.id;
          document.getElementById('confirmMsg').textContent =
            'Ulasan dari "' + btnHapus.dataset.nama + '" akan dihapus permanen dan tidak bisa dikembalikan.';
          document.getElementById('confirmOverlay').classList.add('show');
        }
      });

      /* ── Event: modal confirm ── */
      document.getElementById('btnBatal').addEventListener('click', () =>
        document.getElementById('confirmOverlay').classList.remove('show')
      );

      document.getElementById('btnLihat').addEventListener('click', () => {
        document.getElementById('confirmOverlay').classList.remove('show');
        if (activeRow) openDetail(activeRow);
      });

      document.getElementById('confirmOverlay').addEventListener('click', e => {
        if (e.target === document.getElementById('confirmOverlay'))
          document.getElementById('confirmOverlay').classList.remove('show');
      });

      /* ── Event: detail panel ── */
      document.getElementById('detailClose').addEventListener('click', () =>
        document.getElementById('detailPanel').classList.remove('show')
      );

      /* ── Init ── */
      render();
    });
  </script>
</body>
</html>