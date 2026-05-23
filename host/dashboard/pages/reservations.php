<?php require_once '../../../auth/guard_host.php'; ?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Reservasi | Teman Singgah</title>
    <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />

    <link rel="stylesheet" href="/teman_singgah/components/root.css" />
    <link rel="stylesheet" href="/teman_singgah/components/navbar.css" />
    <link rel="stylesheet" href="/teman_singgah/components/footer.css" />
    <link rel="stylesheet" href="/teman_singgah/popups/auth.css" />
    <link rel="stylesheet" href="/teman_singgah/host/dashboard/styles/reservations.css" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
      rel="stylesheet" />

    <script
      type="module"
      src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <link
      rel="stylesheet"
      href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
  </head>

  <body>
    <header class="navbar">
      <nav class="navbar-container">
        <a href="/teman_singgah/host/dashboard/pages/reservations.php" class="logo-link"></a>
        <div class="logo-section">
          <img
            src="/teman_singgah/assets/logo/logo_temansinggah.svg"
            alt="Logo Teman Singgah"
            class="logo-icon" />
          <img
            src="/teman_singgah/assets/logo/label_temansinggah.svg"
            alt="Brand Name Teman Singgah"
            class="logo-name" />
        </div>

        <ul class="nav-menu">
          <li class="nav-item">
            <a
              href="/teman_singgah/host/dashboard/pages/reservations.php"
              class="nav-link active"
              >Reservasi</a
            >
          </li>
          <li class="nav-item">
            <a
              href="/teman_singgah/host/dashboard/pages/calendar_router.php"
              class="nav-link"
              >Kalender</a
            >
          </li>
          <li class="nav-item">
            <a href="/teman_singgah/host/dashboard/pages/listing.php" class="nav-link"
              >Listing</a
            >
          </li>
          <li class="nav-item">
            <a href="/teman_singgah/host/dashboard/pages/messages.php" class="nav-link"
              >Pesan</a
            >
          </li>
          <div class="nav-indicator"></div>
        </ul>

        <div class="nav-right">
          <a href="../../../index.php">
            <button class="ghost-button">Ganti ke pengunjung</button>
          </a>
          <div class="icon-buttons">
            <button class="icon-button profile" aria-label="Profile">A</button>
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
      <section class="filter-section">
        <div class="filter-group">
          <button class="filter-item active" data-filter="all">Semua</button>
          <button class="filter-item" data-filter="upcoming">Mendatang</button>
          <button class="filter-item" data-filter="ongoing">
            Sedang berlangsung
          </button>
          <button class="filter-item" data-filter="completed">Selesai</button>
          <button class="filter-item" data-filter="cancelled">
            Dibatalkan
          </button>
        </div>
      </section>

      <section class="reservations-grid" id="reservationsGrid">
        <article class="reservation-card" data-status="upcoming">
          <div class="card-header">
            <span class="card-badge upcoming">
              <span class="badge-dot"></span>Mendatang
            </span>
            <span class="card-id">#TS-00421</span>
          </div>

          <div class="card-guest">
            <div class="guest-avatar">SR</div>
            <div class="guest-info">
              <p class="guest-name">Sari Rahmawati</p>
              <p class="guest-meta">2 tamu</p>
            </div>
          </div>

          <div class="card-divider"></div>

          <div class="card-property">
            <img
              src="/teman_singgah/assets/images/apurva_kempinski_bali.jpg"
              alt="Nihi Sumba Resort"
              class="property-image" />
            <div class="property-info">
              <p class="property-name">Nihi Sumba Resort</p>
              <p class="property-dates">
                <i class="ph-bold ph-calendar-blank"></i>
                15 – 18 Jun 2025
              </p>
              <p class="property-duration">3 malam · 1 kamar</p>
            </div>
          </div>

          <div class="card-divider"></div>

          <div class="card-footer">
            <div class="card-earning">
              <p class="earning-label">Pendapatan</p>
              <p class="earning-value">Rp 13.500.000</p>
            </div>
            <div class="card-actions">
              <a href="#" class="card-button primary">Lihat detail</a>
              <button class="card-button secondary">
                <i class="ph-bold ph-chat-circle"></i>
                Hubungi tamu
              </button>
            </div>
          </div>
        </article>

        <article class="reservation-card" data-status="ongoing">
          <div class="card-header">
            <span class="card-badge ongoing">
              <span class="badge-dot"></span>Berlangsung
            </span>
            <span class="card-id">#TS-00398</span>
          </div>

          <div class="card-guest">
            <div class="guest-avatar">BH</div>
            <div class="guest-info">
              <p class="guest-name">Budi Hartono</p>
              <p class="guest-meta">3 tamu</p>
            </div>
          </div>

          <div class="card-divider"></div>

          <div class="card-property">
            <img
              src="/teman_singgah/assets/images/apurva_kempinski_bali.jpg"
              alt="Apurva Kempinski Bali"
              class="property-image" />
            <div class="property-info">
              <p class="property-name">Apurva Kempinski Bali</p>
              <p class="property-dates">
                <i class="ph-bold ph-calendar-blank"></i>
                10 – 14 Jun 2025
              </p>
              <p class="property-duration">4 malam · 2 kamar</p>
            </div>
          </div>

          <div class="card-divider"></div>

          <div class="card-footer">
            <div class="card-earning">
              <p class="earning-label">Pendapatan</p>
              <p class="earning-value">Rp 32.800.000</p>
            </div>
            <div class="card-actions">
              <a href="#" class="card-button primary">Lihat detail</a>
              <button class="card-button secondary">
                <i class="ph-bold ph-chat-circle"></i>
                Hubungi tamu
              </button>
            </div>
          </div>
        </article>

        <article class="reservation-card" data-status="completed">
          <div class="card-header">
            <span class="card-badge completed">
              <span class="badge-dot"></span>Selesai
            </span>
            <span class="card-id">#TS-00371</span>
          </div>

          <div class="card-guest">
            <div class="guest-avatar">DP</div>
            <div class="guest-info">
              <p class="guest-name">Dewi Puspita</p>
              <p class="guest-meta">2 tamu</p>
            </div>
          </div>

          <div class="card-divider"></div>

          <div class="card-property">
            <img
              src="/teman_singgah/assets/images/apurva_kempinski_bali.jpg"
              alt="Alila Manggis"
              class="property-image" />
            <div class="property-info">
              <p class="property-name">Alila Manggis</p>
              <p class="property-dates">
                <i class="ph-bold ph-calendar-blank"></i>
                1 – 5 Mei 2025
              </p>
              <p class="property-duration">4 malam · 1 kamar</p>
            </div>
          </div>

          <div class="card-divider"></div>

          <div class="card-footer">
            <div class="card-earning">
              <p class="earning-label">Pendapatan</p>
              <p class="earning-value">Rp 12.400.000</p>
            </div>
            <div class="card-actions">
              <a href="#" class="card-button primary">Lihat detail</a>
              <button class="card-button secondary">
                <i class="ph-bold ph-star"></i>
                Beri ulasan
              </button>
            </div>
          </div>
        </article>

        <article class="reservation-card" data-status="cancelled">
          <div class="card-header">
            <span class="card-badge cancelled">
              <span class="badge-dot"></span>Dibatalkan
            </span>
            <span class="card-id">#TS-00355</span>
          </div>

          <div class="card-guest">
            <div class="guest-avatar">AF</div>
            <div class="guest-info">
              <p class="guest-name">Ahmad Fauzi</p>
              <p class="guest-meta">1 tamu</p>
            </div>
          </div>

          <div class="card-divider"></div>

          <div class="card-property">
            <img
              src="/teman_singgah/assets/images/apurva_kempinski_bali.jpg"
              alt="The Legian Seminyak"
              class="property-image" />
            <div class="property-info">
              <p class="property-name">The Legian Seminyak</p>
              <p class="property-dates">
                <i class="ph-bold ph-calendar-blank"></i>
                20 – 23 Apr 2025
              </p>
              <p class="property-duration">3 malam · 1 kamar</p>
            </div>
          </div>

          <div class="card-divider"></div>

          <div class="card-footer">
            <div class="card-earning">
              <p class="earning-label">Pendapatan</p>
              <p class="earning-value earning-cancelled">Dibatalkan</p>
            </div>
            <div class="card-actions">
              <a href="#" class="card-button primary">Lihat detail</a>
              <button class="card-button secondary danger">
                <i class="ph-bold ph-x-circle"></i>
                Alasan batal
              </button>
            </div>
          </div>
        </article>
      </section>

      <section class="empty-state" id="emptyState" style="display: none">
        <div class="empty-icon">
          <i class="ph-bold ph-book-open-text"></i>
        </div>
        <h3 class="empty-title">Belum Ada Reservasi</h3>
        <p class="empty-desc">Tidak ada reservasi pada kategori ini.</p>
        <a href="/teman_singgah/index.html" class="empty-button">
          <i class="ph-bold ph-plus"></i>
          Tambahkan properti baru
        </a>
      </section>
    </main>

    <footer class="footer">
      <div class="footer-grid">
        <div class="footer-column">
          <span class="footer-brand">Teman Singgah</span>
          <p class="footer-description">
            Platform booking penginapan terpercaya di seluruh Indonesia, dari
            hotel berbintang hingga homestay lokal.
          </p>
          <div class="footer-social">
            <a href="" class="social-link"><i class="ri-instagram-line"></i></a>
            <a href="" class="social-link"
              ><i class="ri-facebook-circle-line"></i
            ></a>
            <a href="" class="social-link"><i class="ri-youtube-line"></i></a>
            <a href="" class="social-link"><i class="ri-twitter-line"></i></a>
            <a href="" class="social-link"><i class="ri-mail-line"></i></a>
          </div>
        </div>
        <div class="footer-column">
          <h3 class="footer-title">Navigasi</h3>
          <ul class="footer-links">
            <li><a href="/teman_singgah/index.html" class="footer-link">Beranda</a></li>
            <li>
              <a href="/teman_singgah/user/pages/promo_deals.html" class="footer-link"
                >Promo & Deals</a
              >
            </li>
            <li>
              <a href="/teman_singgah/user/pages/become_host.html" class="footer-link"
                >Jadi Host</a
              >
            </li>
            <li>
              <a href="/teman_singgah/user/pages/about_us.html" class="footer-link"
                >Tentang Kami</a
              >
            </li>
            <li>
              <a href="/teman_singgah/user/pages/account.html" class="footer-link">Akun</a>
            </li>
          </ul>
        </div>
        <div class="footer-column">
          <h3 class="footer-title">Dukungan</h3>
          <ul class="footer-links">
            <li><a href="#" class="footer-link">Pusat Bantuan</a></li>
            <li><a href="#" class="footer-link">FAQ</a></li>
            <li>
              <a href="/teman_singgah/user/pages/become_host.html" class="footer-link"
                >Cara Menjadi Host</a
              >
            </li>
            <li><a href="#" class="footer-link">Cara Booking</a></li>
            <li>
              <a href="/teman_singgah/user/pages/about_us.html" class="footer-link"
                >Tentang Kami</a
              >
            </li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p class="footer-copyright">
          © 2026 Teman Singgah — All rights reserved.
        </p>
        <div class="footer-legal">
          <a href="" class="footer-link bottom">Kebijakan Privasi</a>
          <span class="footer-dot">•</span>
          <a href="" class="footer-link bottom">Syarat & Ketentuan</a>
        </div>
      </div>
    </footer>

    <script src="/teman_singgah/host/dashboard/scripts/reservations.js"></script>
    <script src="/teman_singgah/components/navbar.js"></script>
    <script src="/teman_singgah/popups/auth.js"></script>
  </body>
</html>