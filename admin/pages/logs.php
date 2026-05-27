<?php
session_start();
require_once '../../koneksi.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
  header('Location: /teman_singgah/index.php?auth=login');
  exit;
}
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
        <a href="dashboard.php" class="logo-link"></a>
        <div class="logo-section">
          <img src="../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
          <img src="../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
        </div>
      </div>

      <nav class="sidebar-nav">
        <div class="nav-section">
          <div class="nav-section-title">Halaman Utama</div>
          <a href="dashboard.php" class="nav-item">
            <i class="ph-bold ph-squares-four"></i>
            Dashboard
          </a>
        </div>

        <div class="nav-section">
          <div class="nav-section-title">Manajemen</div>
          <a href="users.php" class="nav-item">
            <i class="ph-bold ph-users"></i>
            Pengguna
          </a>
          <a href="listings.php" class="nav-item">
            <i class="ph-bold ph-house"></i>
            Properti
          </a>
          <a href="reservations.php" class="nav-item">
            <i class="ph-bold ph-calendar-check"></i>
            Reservasi
          </a>
          <a href="transactions.php" class="nav-item">
            <i class="ph-bold ph-currency-circle-dollar"></i>
            Transaksi
          </a>
          <a class="nav-item" href="/teman_singgah/admin/pages/promos.php"><i class="ph-bold ph-tag"></i>Promo &
            Deals</a>
        </div>

        <div class="nav-section">
          <div class="nav-section-title">Moderasi</div>
          <a href="reviews.php" class="nav-item">
            <i class="ph-bold ph-star"></i>
            Ulasan
          </a>
          <a href="reports.php" class="nav-item">
            <i class="ph-bold ph-flag"></i>
            Laporan
          </a>
        </div>

        <div class="nav-section">
          <div class="nav-section-title">Keuangan</div>
          <a href="payouts.php" class="nav-item">
            <i class="ph-bold ph-money"></i>
            Pembayaran
          </a>
        </div>

        <div class="nav-section">
          <div class="nav-section-title">Sistem</div>
          <a href="settings.php" class="nav-item">
            <i class="ph-bold ph-gear"></i>
            Pengaturan
          </a>
          <a href="logs.php" class="nav-item active">
            <i class="ph-bold ph-notepad"></i>
            Aktivitas
          </a>
        </div>
      </nav>
    </aside>

    <div class="main-container">
      <header class="topbar">
        <div class="topbar-left">
          <h1 class="page-title">Catatan Aktivitas & Error</h1>
        </div>
        <div class="topbar-right">
          <span class="user-name">Admin utama</span>
          <div class="user-avatar">A</div>
        </div>
      </header>

      <main class="content-area">
        <div class="tab-group">
          <button class="tab-item active" data-target="#activityLog">
            Catatan Aktivitas
          </button>
          <button class="tab-item" data-target="#errorLog">Catatan Error</button>
          <div class="tab-indicator"></div>
        </div>

        <section class="table-section" id="activityLog">
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Waktu</th>
                  <th>User</th>
                  <th>Aksi</th>
                  <th>IP Address</th>
                  <th>Keterangan</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>2026-05-01 08:30:27</td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>
                    <span class="table-badge success">RESOLVE</span>
                  </td>
                  <td>192.168.1.45</td>
                  <td>
                    Menyelesaikan laporan #RPT-2026-0011 tentang kebisingan
                    konstruksi
                  </td>
                </tr>

                <tr>
                  <td>2026-05-01 08:30:27</td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>
                    <span class="table-badge success">RESOLVE</span>
                  </td>
                  <td>192.168.1.45</td>
                  <td>
                    Menyelesaikan laporan #RPT-2026-0011 tentang kebisingan
                    konstruksi
                  </td>
                </tr>

                <tr>
                  <td>2026-05-01 08:30:27</td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>
                    <span class="table-badge success">RESOLVE</span>
                  </td>
                  <td>192.168.1.45</td>
                  <td>
                    Menyelesaikan laporan #RPT-2026-0011 tentang kebisingan
                    konstruksi
                  </td>
                </tr>

                <tr>
                  <td>2026-05-01 08:30:27</td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>
                    <span class="table-badge success">RESOLVE</span>
                  </td>
                  <td>192.168.1.45</td>
                  <td>
                    Menyelesaikan laporan #RPT-2026-0011 tentang kebisingan
                    konstruksi
                  </td>
                </tr>

                <tr>
                  <td>2026-05-01 08:30:27</td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>
                    <span class="table-badge success">RESOLVE</span>
                  </td>
                  <td>192.168.1.45</td>
                  <td>
                    Menyelesaikan laporan #RPT-2026-0011 tentang kebisingan
                    konstruksi
                  </td>
                </tr>

                <tr>
                  <td>2026-05-01 08:30:27</td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>
                    <span class="table-badge success">RESOLVE</span>
                  </td>
                  <td>192.168.1.45</td>
                  <td>
                    Menyelesaikan laporan #RPT-2026-0011 tentang kebisingan
                    konstruksi
                  </td>
                </tr>

                <tr>
                  <td>2026-05-01 08:30:27</td>
                  <td>
                    <div class="table-cell">
                      <div class="table-avatar">BS</div>
                      <h3 class="table-name">Budi Santoso</h3>
                    </div>
                  </td>
                  <td>
                    <span class="table-badge success">RESOLVE</span>
                  </td>
                  <td>192.168.1.45</td>
                  <td>
                    Menyelesaikan laporan #RPT-2026-0011 tentang kebisingan
                    konstruksi
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="table-section" id="errorLog" style="display:none">
          <div class="table-container">
            <table>
              <thead>
                <tr>
                  <th>Waktu</th>
                  <th>Level</th>
                  <th>Pesan Error</th>
                  <th>Stack Trace</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>2026-05-02 14:15:03</td>
                  <td>
                    <span class="table-badge error">ERROR</span>
                  </td>
                  <td>Failed to connect to payment gateway: timeout</td>
                  <td class="table-description">
                    PaymentGatewayException: Connection timed out at
                    /src/services/payment.js:142
                  </td>
                </tr>
                <tr>
                  <td>2026-05-02 12:40:22</td>
                  <td>
                    <span class="table-badge warning">WARNING</span>
                  </td>
                  <td>High memory usage detected on worker node #3</td>
                  <td class="table-description">
                    MemoryMonitor: Heap usage 87% at /src/workers/queue.js:89
                  </td>
                </tr>

                <tr>
                  <td>2026-05-02 09:12:47</td>
                  <td>
                    <span class="table-badge info">INFO</span>
                  </td>
                  <td>Database backup completed successfully</td>
                  <td class="table-description">
                    BackupService: Daily backup finished at
                    /src/jobs/backup.js:55
                  </td>
                </tr>

                <tr>
                  <td>2026-05-01 23:05:11</td>
                  <td>
                    <span class="table-badge error">ERROR</span>
                  </td>
                  <td>Email service SMTP authentication failed</td>
                  <td class="table-description">
                    SMTPException: 535 Authentication failed at
                    /src/services/mailer.js:201
                  </td>
                </tr>

                <tr>
                  <td>2026-05-01 18:33:59</td>
                  <td>
                    <span class="table-badge warning">WARNING</span>
                  </td>
                  <td>Rate limit exceeded for API key ending in ...7a3f</td>
                  <td class="table-description">
                    RateLimiter: 429 Too Many Requests at
                    /src/middleware/rateLimit.js:34
                  </td>
                </tr>

                <tr>
                  <td>2026-05-01 15:20:08</td>
                  <td>
                    <span class="table-badge info">INFO</span>
                  </td>
                  <td>Scheduled payout job started for 12 hosts</td>
                  <td class="table-description">
                    PayoutScheduler: Job ID #PY-SCH-20260501 at
                    /src/jobs/payout.js:12
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>
      </main>
    </div>
  </div>

  <script src="../dashboard.js"></script>
</body>

</html>