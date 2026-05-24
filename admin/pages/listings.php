<?php
session_start();
include "../../koneksi.php";

$result = mysqli_query($koneksi, "
    SELECT
        l.id,
        l.judul,
        l.tipe_properti,
        l.tipe_privasi,
        l.tipe_booking,
        l.lokasi,
        l.harga_malam,
        l.max_tamu,
        l.kamar_tidur,
        l.tempat_tidur,
        l.kamar_mandi,
        l.status,
        l.dibuat_pada,
        u.nama        AS host_nama,
        u.photo       AS host_photo,
        lp.nama_file  AS foto_cover,
        COUNT(DISTINCT b.id)      AS total_booking,
        ROUND(AVG(r.rating), 1)   AS rating_avg
    FROM listings l
    JOIN users u ON l.host_id = u.id
    LEFT JOIN listing_photos lp ON lp.listing_id = l.id AND lp.adalah_cover = 1
    LEFT JOIN bookings b ON b.listing_id = l.id
    LEFT JOIN reviews r ON r.booking_id = b.id
    GROUP BY
        l.id, l.judul, l.tipe_properti, l.tipe_privasi, l.tipe_booking,
        l.lokasi, l.harga_malam, l.max_tamu, l.kamar_tidur, l.tempat_tidur,
        l.kamar_mandi, l.status, l.dibuat_pada, u.nama, u.photo, lp.nama_file
    ORDER BY l.dibuat_pada DESC
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Properti | Admin Teman Singgah</title>
  <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="/teman_singgah/components/root.css" />
  <link rel="stylesheet" href="/teman_singgah/admin/dashboard.css" />
  <link href="https://fonts.googleapis.com" rel="preconnect" />
  <link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect" />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
  <script src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js" type="module"></script>
  <link href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" rel="stylesheet" />
  <style>
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

    /* Col num */
    .table-container .managed-table thead tr th.col-num {
      text-align: center;
      width: 65px;
      font-family: var(--font-family);
      font-size: var(--text-sm);
      color: var(--color-text-primary);
      font-weight: var(--font-semibold);
    }

    .table-container .managed-table tbody tr td.col-num {
      text-align: center;
      width: 65px;
      font-family: var(--font-family);
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
      font-weight: var(--font-regular);
    }

    /* Sort dropdown */
    .sort-dropdown {
      position: relative;
    }

    .sort-menu {
      display: none;
      position: absolute;
      right: 0;
      top: calc(100% + 6px);
      background: #ffffff;
      border: 1px solid var(--color-border, #e5e7eb);
      border-radius: 10px;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.10);
      z-index: 200;
      min-width: 210px;
      overflow: hidden;
    }

    .sort-menu.open {
      display: block;
    }

    .sort-menu-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 16px;
      font-size: 14px;
      cursor: pointer;
      color: #374151;
      transition: background 0.15s;
      font-family: var(--font-family);
    }

    .sort-menu-item:hover {
      background: #f9fafb;
    }

    .sort-menu-item.active {
      color: var(--color-primary, #8b2500);
      font-weight: 600;
      background: #fff8f5;
    }

    .sort-menu-divider {
      height: 1px;
      background: #f3f4f6;
      margin: 4px 0;
    }

    /* Rating cell */
    .rating-cell {
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: var(--text-sm);
      font-weight: 500;
    }

    .rating-cell i {
      color: #f59e0b;
      font-size: 0.85rem;
    }

    .rating-cell .no-rating {
      color: var(--color-text-disabled);
      font-weight: 400;
    }

    /* Pill badges untuk tipe */
    .pill {
      display: inline-block;
      padding: 2px 9px;
      border-radius: 99px;
      font-size: 11.5px;
      font-weight: 500;
      white-space: nowrap;
    }

    .pill-tipe {
      background: #f3f4f6;
      color: #374151;
    }

    .pill-privasi {
      background: #eff6ff;
      color: #1d4ed8;
    }

    .pill-instan {
      background: #f0fdf4;
      color: #15803d;
    }

    .pill-permintaan {
      background: #fefce8;
      color: #a16207;
    }

    /* Booking count */
    .booking-count {
      font-size: var(--text-sm);
      color: var(--color-text-primary);
      font-weight: 500;
    }

    .booking-count span {
      color: var(--color-text-disabled);
      font-weight: 400;
      font-size: 11px;
    }

    /* Floor plan mini info */
    .floor-info {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      font-size: 11.5px;
      color: var(--color-text-secondary);
    }

    .floor-info span {
      display: flex;
      align-items: center;
      gap: 3px;
    }
  </style>
</head>

<body>
  <div class="admin-layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <a class="logo-link" href="/teman_singgah/admin/pages/dashboard.html"></a>
        <div class="logo-section">
          <img alt="Logo Teman Singgah" class="logo-icon" src="/teman_singgah/assets/logo/logo_temansinggah.svg" />
          <img alt="Brand Name Teman Singgah" class="logo-name"
            src="/teman_singgah/assets/logo/label_temansinggah.svg" />
        </div>
      </div>
      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title">Halaman Utama</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/dashboard.html"><i
              class="ph-bold ph-squares-four"></i>Dashboard</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Manajemen</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/users.php"><i class="ph-bold ph-users"></i>Pengguna</a>
          <a class="nav-item active" href="/teman_singgah/admin/pages/listings.php"><i
              class="ph-bold ph-house"></i>Properti</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/reservations.html"><i
              class="ph-bold ph-calendar-check"></i>Reservasi</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/transactions.html"><i
              class="ph-bold ph-currency-circle-dollar"></i>Transaksi</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Moderasi</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/reviews.php"><i class="ph-bold ph-star"></i>Ulasan</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/reports.html"><i class="ph-bold ph-flag"></i>Laporan</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Keuangan</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/payouts.html"><i
              class="ph-bold ph-money"></i>Pembayaran</a>
        </div>
        <div class="nav-section">
          <div class="nav-section-title">Sistem</div>
          <a class="nav-item" href="/teman_singgah/admin/pages/settings.html"><i
              class="ph-bold ph-gear"></i>Pengaturan</a>
          <a class="nav-item" href="/teman_singgah/admin/pages/logs.html"><i
              class="ph-bold ph-notepad"></i>Aktivitas</a>
        </div>
      </nav>
    </aside>

    <div class="main-container">
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">Manajemen Properti</h1>
        </div>
        <div class="topbar-right">
          <span class="user-name">Admin utama</span>
          <div class="user-avatar">A</div>
        </div>
      </header>

      <main class="content-area">
        <div class="table-toolbar">
          <div class="search-row">
            <div class="table-search-wrap">
              <i class="ph-bold ph-magnifying-glass table-search-icon"></i>
              <input type="search" id="adminSearch" class="table-search-input"
                placeholder="Cari nama listing, host, atau lokasi..." />
            </div>
          </div>
        </div>

        <div class="filter-container">
          <div class="filter-group" id="filterGroup">
            <button class="filter-item active" data-filter="all">Semua</button>
            <button class="filter-item" data-filter="status:aktif">Aktif</button>
            <button class="filter-item" data-filter="status:nonaktif">Nonaktif</button>
            <button class="filter-item" data-filter="status:draft">Draft</button>
            <button class="filter-item" data-filter="booking:instan">Booking Instan</button>
            <button class="filter-item" data-filter="booking:permintaan">Perlu Konfirmasi</button>
          </div>
          <div class="sort-dropdown">
            <button class="sort-button" id="sortToggleBtn">
              <i class="ph-bold ph-faders-horizontal"></i>
              <span id="sortLabel">Urutkan: Terbaru</span>
              <i class="ph-bold ph-caret-down"></i>
            </button>
            <div class="sort-menu" id="sortMenu">
              <div class="sort-menu-item active" onclick="selectSort('date_newest','Terbaru',this)">
                <i class="ph-bold ph-calendar"></i> Tanggal Terbaru
              </div>
              <div class="sort-menu-item" onclick="selectSort('date_oldest','Terlama',this)">
                <i class="ph-bold ph-calendar"></i> Tanggal Terlama
              </div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('name_asc','Nama A–Z',this)">
                <i class="ph-bold ph-sort-ascending"></i> Nama A–Z
              </div>
              <div class="sort-menu-item" onclick="selectSort('name_desc','Nama Z–A',this)">
                <i class="ph-bold ph-sort-descending"></i> Nama Z–A
              </div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('price_high','Harga Tertinggi',this)">
                <i class="ph-bold ph-trend-up"></i> Harga Tertinggi
              </div>
              <div class="sort-menu-item" onclick="selectSort('price_low','Harga Terendah',this)">
                <i class="ph-bold ph-trend-down"></i> Harga Terendah
              </div>
              <div class="sort-menu-divider"></div>
              <div class="sort-menu-item" onclick="selectSort('rating_high','Rating Tertinggi',this)">
                <i class="ph-bold ph-star"></i> Rating Tertinggi
              </div>
              <div class="sort-menu-item" onclick="selectSort('booking_most','Booking Terbanyak',this)">
                <i class="ph-bold ph-calendar-check"></i> Booking Terbanyak
              </div>
            </div>
          </div>
        </div>

        <section class="table-section">
          <div class="table-container">
            <table class="managed-table" id="listingTable">
              <thead>
                <tr>
                  <th class="col-num">No.</th>
                  <th>Nama Listing</th>
                  <th>Host</th>
                  <th>Lokasi</th>
                  <th>Detail</th>
                  <th>Harga/Malam</th>
                  <th>Booking</th>
                  <th>Rating</th>
                  <th>Status</th>
                  <th>Tgl Dibuat</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $no = 1;
                while ($row = mysqli_fetch_assoc($result)):
                  // Foto listing (cover)
                  $foto_src = !empty($row['foto_cover'])
                    ? '/teman_singgah/assets/uploads/listings/' . htmlspecialchars($row['foto_cover'])
                    : null;

                  // Foto host dari users table
                  $host_photo_path = !empty($row['host_photo']) &&
                    file_exists($_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $row['host_photo'])
                    ? '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($row['host_photo'])
                    : null;
                  $host_initial = strtoupper(mb_substr($row['host_nama'], 0, 2));

                  // Status
                  $status = strtolower($row['status']);
                  $status_class = match ($status) {
                    'aktif' => 'success',
                    'nonaktif' => 'danger',
                    'draft' => 'neutral',
                    default => 'neutral'
                  };

                  // Tipe booking
                  $tipe_booking = strtolower($row['tipe_booking']);
                  $booking_pill_class = $tipe_booking === 'instan' ? 'pill-instan' : 'pill-permintaan';
                  $booking_label = $tipe_booking === 'instan' ? 'Instan' : 'Konfirmasi';

                  $harga = 'Rp ' . number_format($row['harga_malam'], 0, ',', '.');
                  $rating = $row['rating_avg'] ?: null;
                  $tgl = date('d M Y', strtotime($row['dibuat_pada']));
                  ?>
                  <tr data-status="<?= htmlspecialchars($status) ?>" data-booking="<?= htmlspecialchars($tipe_booking) ?>"
                    data-judul="<?= htmlspecialchars($row['judul']) ?>"
                    data-host="<?= htmlspecialchars($row['host_nama']) ?>"
                    data-lokasi="<?= htmlspecialchars($row['lokasi']) ?>" data-harga="<?= (int) $row['harga_malam'] ?>"
                    data-rating="<?= $rating ?? 0 ?>" data-booking-count="<?= (int) $row['total_booking'] ?>"
                    data-tanggal="<?= htmlspecialchars($row['dibuat_pada']) ?>">
                    <td class="col-num"><?= $no++ ?></td>

                    <!-- Nama Listing + foto cover -->
                    <td>
                      <div class="table-cell">
                        <?php if ($foto_src): ?>
                          <img src="<?= $foto_src ?>" class="table-thumbnail" alt="" />
                        <?php else: ?>
                          <div class="table-thumbnail"
                            style="background:#f3f4f6;display:flex;align-items:center;justify-content:center;">
                            <i class="ph-bold ph-image" style="color:#d1d5db;font-size:1.2rem;"></i>
                          </div>
                        <?php endif; ?>
                        <div>
                          <h3 class="table-name"><?= htmlspecialchars($row['judul']) ?></h3>
                          <div style="display:flex;gap:5px;margin-top:4px;flex-wrap:wrap;">
                            <span class="pill pill-tipe"><?= htmlspecialchars(ucfirst($row['tipe_properti'])) ?></span>
                            <span
                              class="pill pill-privasi"><?= $row['tipe_privasi'] === 'seluruh' ? 'Seluruh tempat' : 'Per kamar' ?></span>
                          </div>
                        </div>
                      </div>
                    </td>

                    <!-- Host + foto profil host yang beneran -->
                    <td>
                      <div class="table-cell">
                        <?php if ($host_photo_path): ?>
                          <div class="table-avatar" style="padding:0;overflow:hidden;">
                            <img src="<?= $host_photo_path ?>" alt="Foto Host"
                              style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
                          </div>
                        <?php else: ?>
                          <div class="table-avatar"><?= $host_initial ?></div>
                        <?php endif; ?>
                        <h3 class="table-name"><?= htmlspecialchars($row['host_nama']) ?></h3>
                      </div>
                    </td>

                    <!-- Lokasi -->
                    <td><?= htmlspecialchars($row['lokasi']) ?></td>

                    <!-- Detail (floor plan ringkas) -->
                    <td>
                      <div class="floor-info">
                        <span><i class="ph-bold ph-users"></i> <?= $row['max_tamu'] ?> tamu</span>
                        <span><i class="ph-bold ph-bed"></i> <?= $row['kamar_tidur'] ?> KT</span>
                        <span><i class="ph-bold ph-bathtub"></i> <?= $row['kamar_mandi'] ?> KM</span>
                      </div>
                    </td>

                    <!-- Harga -->
                    <td><?= $harga ?></td>

                    <!-- Booking: tipe + jumlah -->
                    <td>
                      <span class="pill <?= $booking_pill_class ?>"><?= $booking_label ?></span>
                      <div class="booking-count" style="margin-top:4px;">
                        <?= (int) $row['total_booking'] ?> <span>booking</span>
                      </div>
                    </td>

                    <!-- Rating -->
                    <td>
                      <?php if ($rating): ?>
                        <div class="rating-cell"><i class="ph-fill ph-star"></i><?= $rating ?></div>
                      <?php else: ?>
                        <div class="rating-cell"><span class="no-rating">–</span></div>
                      <?php endif; ?>
                    </td>

                    <!-- Status -->
                    <td>
                      <span class="table-badge <?= $status_class ?>">
                        <span class="badge-dot"></span><?= ucfirst($row['status']) ?>
                      </span>
                    </td>

                    <!-- Tgl dibuat -->
                    <td><?= $tgl ?></td>

                    <!-- Aksi -->
                    <td>
                      <div class="action-group">
                        <button class="action-button warning" aria-label="Edit listing" title="Edit">
                          <i class="ph-bold ph-pencil"></i>
                        </button>
                        <button class="action-button info" aria-label="Lihat detail" title="Lihat">
                          <i class="ph-bold ph-eye"></i>
                        </button>
                        <button class="action-button error" aria-label="Hapus listing" title="Hapus"
                          onclick="hapusListing(<?= $row['id'] ?>)">
                          <i class="ph-bold ph-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
            <div class="table-pagination">
              <span class="pagination-info" id="paginationInfo"></span>
              <div class="pagination-controls"></div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </div>

  <script>
    /* ── Filter ── */
    document.querySelectorAll(".filter-item").forEach(btn => {
      btn.addEventListener("click", () => {
        document.querySelectorAll(".filter-item").forEach(b => b.classList.remove("active"));
        btn.classList.add("active");
        applyFilter();
      });
    });

    function applyFilter() {
      const table = document.querySelector(".managed-table");
      if (!table) return;
      const filterAttr = document.querySelector(".filter-item.active")?.dataset.filter || "all";

      table.querySelectorAll("tbody tr").forEach(row => {
        let show = true;
        if (filterAttr !== "all") {
          const [key, value] = filterAttr.split(":");
          if (key === "status") show = row.dataset.status === value;
          if (key === "booking") show = row.dataset.booking === value;
        }
        show ? delete row.dataset.hiddenFilter : (row.dataset.hiddenFilter = "1");
        rebuildHidden(row);
      });
      resetAndPaginate(table);
    }

    function rebuildHidden(row) {
      (row.dataset.hiddenFilter || row.dataset.hiddenSearch)
        ? (row.dataset.hidden = "1")
        : delete row.dataset.hidden;
    }

    /* ── Sort ── */
    const sortToggleBtn = document.getElementById("sortToggleBtn");
    const sortMenu = document.getElementById("sortMenu");
    if (sortToggleBtn && sortMenu) {
      sortToggleBtn.addEventListener("click", e => { e.stopPropagation(); sortMenu.classList.toggle("open"); });
      document.addEventListener("click", e => {
        if (!sortMenu.contains(e.target) && e.target !== sortToggleBtn) sortMenu.classList.remove("open");
      });
    }

    function selectSort(sortBy, label, el) {
      document.getElementById("sortLabel").textContent = "Urutkan: " + label;
      document.querySelectorAll(".sort-menu-item").forEach(i => i.classList.remove("active"));
      el?.classList.add("active");
      sortMenu.classList.remove("open");
      applySort(sortBy);
    }

    function applySort(sortBy) {
      const table = document.querySelector(".managed-table");
      const tbody = table?.querySelector("tbody");
      if (!tbody) return;
      const rows = Array.from(tbody.querySelectorAll("tr"));

      rows.sort((a, b) => {
        if (sortBy === "date_newest") return new Date(b.dataset.tanggal || 0) - new Date(a.dataset.tanggal || 0);
        if (sortBy === "date_oldest") return new Date(a.dataset.tanggal || 0) - new Date(b.dataset.tanggal || 0);
        if (sortBy === "name_asc") return (a.dataset.judul || "").localeCompare(b.dataset.judul || "", "id-ID");
        if (sortBy === "name_desc") return (b.dataset.judul || "").localeCompare(a.dataset.judul || "", "id-ID");
        if (sortBy === "price_high") return parseInt(b.dataset.harga || 0) - parseInt(a.dataset.harga || 0);
        if (sortBy === "price_low") return parseInt(a.dataset.harga || 0) - parseInt(b.dataset.harga || 0);
        if (sortBy === "rating_high") return parseFloat(b.dataset.rating || 0) - parseFloat(a.dataset.rating || 0);
        if (sortBy === "booking_most") return parseInt(b.dataset.bookingCount || 0) - parseInt(a.dataset.bookingCount || 0);
        return 0;
      });

      rows.forEach(row => tbody.appendChild(row));
      resetAndPaginate(table);
    }

    /* ── Search ── */
    document.getElementById("adminSearch")?.addEventListener("input", function () {
      const q = this.value.trim().toLowerCase();
      const table = document.querySelector(".managed-table");
      if (!table) return;
      table.querySelectorAll("tbody tr").forEach(row => {
        const match = !q
          || (row.dataset.judul || "").toLowerCase().includes(q)
          || (row.dataset.host || "").toLowerCase().includes(q)
          || (row.dataset.lokasi || "").toLowerCase().includes(q);
        match ? delete row.dataset.hiddenSearch : (row.dataset.hiddenSearch = "1");
        rebuildHidden(row);
      });
      resetAndPaginate(table);
    });

    /* ── Pagination ── */
    const ROWS_PER_PAGE = 10;

    function resetAndPaginate(table) { table._adminPage = 1; applyPagination(table); }

    function applyPagination(table) {
      const page = table._adminPage || 1;
      const allRows = Array.from(table.querySelectorAll("tbody tr"));
      const visible = allRows.filter(r => !r.dataset.hidden);
      const total = visible.length;
      const totalPages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
      const safe = Math.min(page, totalPages);
      table._adminPage = safe;

      const start = (safe - 1) * ROWS_PER_PAGE;
      const end = start + ROWS_PER_PAGE;

      allRows.forEach(r => { r.style.display = r.dataset.hidden ? "none" : ""; });
      visible.forEach((r, i) => { r.style.display = (i >= start && i < end) ? "" : "none"; });

      let counter = 0;
      allRows.forEach(row => {
        const cell = row.querySelector(".col-num");
        if (!cell) return;
        cell.textContent = row.style.display === "none" ? "" : ++counter;
      });

      renderPagination(table, safe, totalPages, total);
    }

    function renderPagination(table, page, totalPages, total) {
      const section = table.closest(".table-section");
      const infoEl = section?.querySelector(".pagination-info");
      const controlsEl = section?.querySelector(".pagination-controls");
      if (!infoEl || !controlsEl) return;

      const start = total === 0 ? 0 : (page - 1) * ROWS_PER_PAGE + 1;
      const end = Math.min(page * ROWS_PER_PAGE, total);
      infoEl.textContent = total === 0 ? "Tidak ada data" : `${start}–${end} dari ${total} listing`;

      controlsEl.innerHTML = "";

      const mkBtn = (html, disabled, onClick) => {
        const btn = document.createElement("button");
        btn.className = "page-btn nav-btn";
        btn.innerHTML = html;
        btn.disabled = disabled;
        if (!disabled) btn.addEventListener("click", onClick);
        return btn;
      };

      controlsEl.appendChild(mkBtn('<i class="ph-bold ph-caret-left"></i>', page <= 1,
        () => { table._adminPage = page - 1; applyPagination(table); }));

      buildPageList(page, totalPages).forEach(p => {
        if (p === "...") {
          const el = document.createElement("span");
          el.className = "page-ellipsis"; el.textContent = "…";
          controlsEl.appendChild(el);
        } else {
          const btn = document.createElement("button");
          btn.className = "page-btn" + (p === page ? " active" : "");
          btn.textContent = p;
          btn.addEventListener("click", () => { table._adminPage = p; applyPagination(table); });
          controlsEl.appendChild(btn);
        }
      });

      controlsEl.appendChild(mkBtn('<i class="ph-bold ph-caret-right"></i>', page >= totalPages,
        () => { table._adminPage = page + 1; applyPagination(table); }));
    }

    function buildPageList(current, total) {
      if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
      const pages = [1];
      if (current > 3) pages.push("...");
      for (let p = Math.max(2, current - 1); p <= Math.min(total - 1, current + 1); p++) pages.push(p);
      if (current < total - 2) pages.push("...");
      pages.push(total);
      return pages;
    }

    /* ── Hapus Listing ── */
    function hapusListing(id) {
      if (!confirm("Yakin hapus listing ini? Semua foto dan data terkait akan ikut terhapus.")) return;
      fetch("/teman_singgah/admin/pages/delete_listing.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ id })
      })
        .then(r => r.json())
        .then(d => { if (d.status === "ok") location.reload(); else alert("Gagal: " + d.message); });
    }

    /* ── Init ── */
    document.addEventListener("DOMContentLoaded", () => {
      const table = document.querySelector(".managed-table");
      if (table) applyPagination(table);
    });
  </script>
</body>

</html>