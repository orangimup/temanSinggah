<?php include '../../auth/auth_session.php'; ?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Promo & Deals | Teman Singgah</title>
    <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />

    <link rel="stylesheet" href="/teman_singgah/components/root.css" />
    <link rel="stylesheet" href="/teman_singgah/components/navbar.css" />
    <link rel="stylesheet" href="/teman_singgah/components/footer.css" />
    <link rel="stylesheet" href="/teman_singgah/popups/auth.css" />
    <link rel="stylesheet" href="/teman_singgah/user/styles/promo_deals.css" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />

    <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
  </head>

  <body>
    <header class="navbar">
      <nav class="navbar-container">
        <a href="/teman_singgah/index.php" class="logo-link"></a>
        <div class="logo-section">
          <img src="/teman_singgah/assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
          <img src="/teman_singgah/assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
        </div>

        <ul class="nav-menu">
          <li class="nav-item"><a href="/teman_singgah/index.php" class="nav-link">Cari Penginapan</a></li>
          <li class="nav-item"><a href="/teman_singgah/user/pages/promo_deals.php" class="nav-link active">Promo & Deals</a></li>
          <li class="nav-item"><a href="/teman_singgah/user/pages/become_host.php" class="nav-link">Jadi Host</a></li>
          <li class="nav-item"><a href="/teman_singgah/user/pages/about_us.php" class="nav-link">Tentang Kami</a></li>
          <div class="nav-indicator"></div>
        </ul>

        <div class="nav-right">
          <a href="/teman_singgah/host/onboarding/pages/about_place.html">
            <button class="ghost-button">Ganti ke host</button>
          </a>
          <?php include '../../components/navbar_profile.php'; ?>
        </div>
      </nav>
    </header>

    <main class="main-content">
      <section class="hero-section">
        <div class="hero-container">
          <div class="promo-badge">
            <i class="ph-fill ph-fire"></i>
            <span>Flash Sale Berlangsung</span>
          </div>
          <h1 class="hero-title">Promo & Deals Eksklusif</h1>
          <p class="hero-description">Hemat hingga 70% untuk penginapan pilihan di seluruh Indonesia. Penawaran terbatas, jangan sampai terlewat!</p>
        </div>
      </section>

      <section class="filter-section">
        <div class="filter-container">
          <div class="filter-group">
            <button class="filter-item active"><i class="ph-bold ph-squares-four"></i> Semua Promo</button>
            <button class="filter-item"><i class="ph-bold ph-lightning"></i> Flash Sale</button>
            <button class="filter-item"><i class="ph-bold ph-sun"></i> Weekend Deal</button>
            <button class="filter-item"><i class="ph-bold ph-calendar-check"></i> Early Bird</button>
            <button class="filter-item"><i class="ph-bold ph-clock-countdown"></i> Last Minute</button>
          </div>
          <div class="sort-dropdown">
            <button class="sort-button">
              <i class="ph-bold ph-faders-horizontal"></i>
              <span>Urutkan: Diskon Terbesar</span>
              <i class="caret-icon ph-bold ph-caret-down"></i>
            </button>
          </div>
        </div>
      </section>

      <section class="featured-section">
        <div class="featured-container">
          <div class="featured-content">
            <span class="featured-label"><i class="ph-fill ph-crown"></i> Deal of the Month</span>
            <h2 class="featured-title">Paket Liburan Keluarga ke Bali</h2>
            <p class="featured-description">Nikmati pengalaman menginap 3 malam di resort bintang 5 dengan harga spesial. Termasuk sarapan dan voucher spa.</p>
            <div class="featured-stats">
              <div class="stat-item"><span class="stat-value">60%</span><span class="stat-label">Hemat</span></div>
              <div class="stat-item"><span class="stat-value">3 Malam</span><span class="stat-label">Menginap</span></div>
              <div class="stat-item"><span class="stat-value">4.9</span><span class="stat-label">Rating</span></div>
            </div>
            <button class="featured-button">Klaim Sekarang</button>
          </div>
          <div class="featured-image">
            <img src="/teman_singgah/assets/images/padma_resort_ubud_bali.jpg" alt="Bali Family Package" />
          </div>
        </div>
      </section>

      <section class="voucher-section">
        <h2 class="section-title">Kode Vocer</h2>
        <div class="voucher-grid">
          <div class="voucher-card">
            <div class="voucher-header"><span class="voucher-code">WEEKEND20</span><span class="voucher-discount">20%</span></div>
            <p class="voucher-title">Weekend Getaway</p>
            <div class="voucher-validity"><i class="ph-bold ph-clock"></i> Berlaku hingga 31 Maret 2026</div>
            <button class="copy-button"><i class="ph-bold ph-copy"></i></button>
          </div>
          <div class="voucher-card">
            <div class="voucher-header"><span class="voucher-code">NEWMEMBER50</span><span class="voucher-discount">50%</span></div>
            <p class="voucher-title">New Member Special</p>
            <div class="voucher-validity"><i class="ph-bold ph-clock"></i> Khusus pendaftar baru</div>
            <button class="copy-button"><i class="ph-bold ph-copy"></i></button>
          </div>
          <div class="voucher-card">
            <div class="voucher-header"><span class="voucher-code">STAY3PAY2</span><span class="voucher-discount">33%</span></div>
            <p class="voucher-title">Stay 3 Pay 2</p>
            <div class="voucher-validity"><i class="ph-bold ph-clock"></i> Berlaku di hotel pilihan</div>
            <button class="copy-button"><i class="ph-bold ph-copy"></i></button>
          </div>
        </div>
      </section>

      <section class="card-section">
        <h2 class="section-title">Penawaran Terbatas</h2>
        <div class="promo-grid">
          <a href="/teman_singgah/user/pages/detail_card.html" class="promo-card" data-category="flash">
            <div class="card-image-wrapper">
              <span class="discount-tag">-45%</span>
              <img src="/teman_singgah/assets/images/nihi_sumba.webp" alt="Nihi Sumba" class="card-image" />
              <img src="/teman_singgah/assets/icons/save.svg" alt="wishlist" class="save-button" />
              <div class="promo-timer"><i class="ph-bold ph-clock-countdown"></i><span>Berakhir dalam 5 jam</span></div>
            </div>
            <div class="promo-content">
              <h3 class="promo-title">Nihi Sumba - Villa Ocean View</h3>
              <div class="promo-amenities">
                <span class="amenity-item"><i class="ph-bold ph-map-pin"></i>Sumba, NTT</span>
                <span class="amenity-item"><i class="ph-bold ph-swimming-pool"></i>WiFi</span>
                <span class="amenity-item"><i class="ph-bold ph-wifi-high"></i>Pool</span>
              </div>
              <div class="promo-price">
                <span class="original-price">Rp 8.500.000</span>
                <span class="current-price">Rp 4.675.000</span>
                <span class="price-unit">/ malam</span>
              </div>
            </div>
          </a>
          <a href="/teman_singgah/user/pages/detail_card.html" class="promo-card" data-category="weekend">
            <div class="card-image-wrapper">
              <span class="discount-tag">-30%</span>
              <img src="/teman_singgah/assets/images/padma_resort_ubud_bali.jpg" alt="Padma Resort Ubud" class="card-image" />
              <img src="/teman_singgah/assets/icons/save.svg" alt="wishlist" class="save-button" />
              <div class="promo-timer"><i class="ph-bold ph-clock-countdown"></i><span>Berakhir dalam 12 jam</span></div>
            </div>
            <div class="promo-content">
              <h3 class="promo-title">Padma Resort Ubud</h3>
              <div class="promo-amenities">
                <span class="amenity-item"><i class="ph-bold ph-map-pin"></i>Ubud, Bali</span>
                <span class="amenity-item"><i class="ph-bold ph-swimming-pool"></i>Pool</span>
                <span class="amenity-item"><i class="ph-bold ph-fork-knife"></i>Breakfast</span>
              </div>
              <div class="promo-price">
                <span class="original-price">Rp 3.000.000</span>
                <span class="current-price">Rp 2.100.000</span>
                <span class="price-unit">/ malam</span>
              </div>
            </div>
          </a>
          <a href="/teman_singgah/user/pages/detail_card.html" class="promo-card" data-category="flash">
            <div class="card-image-wrapper">
              <span class="discount-tag">-20%</span>
              <img src="/teman_singgah/assets/images/apurva_kempinski_bali.jpg" alt="Apurva Kempinski" class="card-image" />
              <img src="/teman_singgah/assets/icons/save.svg" alt="wishlist" class="save-button" />
              <div class="promo-timer"><i class="ph-bold ph-clock-countdown"></i><span>Berakhir dalam 2 hari</span></div>
            </div>
            <div class="promo-content">
              <h3 class="promo-title">Apurva Kempinski Bali</h3>
              <div class="promo-amenities">
                <span class="amenity-item"><i class="ph-bold ph-map-pin"></i>Nusa Dua, Bali</span>
                <span class="amenity-item"><i class="ph-bold ph-sparkle"></i>Spa</span>
                <span class="amenity-item"><i class="ph-bold ph-fork-knife"></i>Breakfast</span>
              </div>
              <div class="promo-price">
                <span class="original-price">Rp 1.062.500</span>
                <span class="current-price">Rp 850.000</span>
                <span class="price-unit">/ malam</span>
              </div>
            </div>
          </a>
          <a href="/teman_singgah/user/pages/detail_card.html" class="promo-card" data-category="earlybird">
            <div class="card-image-wrapper">
              <span class="discount-tag">-25%</span>
              <img src="/teman_singgah/assets/images/desa_potato_head_bali.webp" alt="Desa Potato Head" class="card-image" />
              <img src="/teman_singgah/assets/icons/save.svg" alt="wishlist" class="save-button" />
              <div class="promo-timer"><i class="ph-bold ph-clock-countdown"></i><span>Berakhir dalam 3 hari</span></div>
            </div>
            <div class="promo-content">
              <h3 class="promo-title">Desa Potato Head Bali</h3>
              <div class="promo-amenities">
                <span class="amenity-item"><i class="ph-bold ph-map-pin"></i>Kuta, Bali</span>
                <span class="amenity-item"><i class="ph-bold ph-swimming-pool"></i>Pool</span>
                <span class="amenity-item"><i class="ph-bold ph-music-note"></i>Beach Club</span>
              </div>
              <div class="promo-price">
                <span class="original-price">Rp 4.266.000</span>
                <span class="current-price">Rp 3.200.000</span>
                <span class="price-unit">/ malam</span>
              </div>
            </div>
          </a>
        </div>
      </section>
    </main>

    <footer class="footer">
      <div class="footer-grid">
        <div class="footer-column">
          <span class="footer-brand">Teman Singgah</span>
          <p class="footer-description">Platform booking penginapan terpercaya di seluruh Indonesia, dari hotel berbintang hingga homestay lokal.</p>
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
            <li><a href="/teman_singgah/index.php" class="footer-link">Beranda</a></li>
            <li><a href="/teman_singgah/user/pages/promo_deals.php" class="footer-link">Promo & Deals</a></li>
            <li><a href="/teman_singgah/user/pages/become_host.php" class="footer-link">Jadi Host</a></li>
            <li><a href="/teman_singgah/user/pages/about_us.php" class="footer-link">Tentang Kami</a></li>
            <li><a href="/teman_singgah/user/pages/account.php" class="footer-link">Akun</a></li>
          </ul>
        </div>
        <div class="footer-column">
          <h3 class="footer-title">Dukungan</h3>
          <ul class="footer-links">
            <li><a href="#" class="footer-link">Pusat Bantuan</a></li>
            <li><a href="#" class="footer-link">FAQ</a></li>
            <li><a href="/teman_singgah/user/pages/become_host.php" class="footer-link">Cara Menjadi Host</a></li>
            <li><a href="#" class="footer-link">Cara Booking</a></li>
            <li><a href="/teman_singgah/user/pages/about_us.php" class="footer-link">Tentang Kami</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <p class="footer-copyright">© 2026 Teman Singgah — All rights reserved.</p>
        <div class="footer-legal">
          <a href="" class="footer-link bottom">Kebijakan Privasi</a>
          <span class="footer-dot">•</span>
          <a href="" class="footer-link bottom">Syarat & Ketentuan</a>
        </div>
      </div>
    </footer>

    <?php include '../../popups/auth_overlay.php'; ?>

    <script src="/teman_singgah/user/scripts/promo_deals.js"></script>
    <script src="/teman_singgah/components/navbar.js"></script>
    <script src="/teman_singgah/popups/auth.js"></script>
  </body>
</html>