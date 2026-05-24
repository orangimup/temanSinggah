// Di reservations.php, listing.php, dll.
<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_host.php'; ?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pesan | Teman Singgah</title>
    <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />

    <link rel="stylesheet" href="../../../components/root.css" />
    <link rel="stylesheet" href="../../../components/navbar.css" />
    <link rel="stylesheet" href="../../../popups/auth.css" />
    <link rel="stylesheet" href="../styles/messages.css" />

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
        <a href="reservations.php" class="logo-link"></a>
        <div class="logo-section">
          <img
            src="../../../assets/logo/logo_temansinggah.svg"
            alt="Logo Teman Singgah"
            class="logo-icon" />
          <img
            src="../../../assets/logo/label_temansinggah.svg"
            alt="Brand Name Teman Singgah"
            class="logo-name" />
        </div>

        <ul class="nav-menu">
          <li class="nav-item">
            <a href="reservations.php" class="nav-link">Reservasi</a>
          </li>
          <li class="nav-item">
            <a href="calendar_router.php" class="nav-link">Kalender</a>
          </li>
          <li class="nav-item">
            <a href="listing.php" class="nav-link">Listing</a>
          </li>
          <li class="nav-item">
            <a href="messages.php" class="nav-link active">Pesan</a>
          </li>
          <div class="nav-indicator"></div>
        </ul>

        <?php include '/teman_singgah/components/navbar_profile_host.php'; ?>

      </nav>
    </header>

    <main class="main-content">
      <aside class="messages-sidebar">
        <div class="sidebar-header">
          <h2 class="sidebar-title">Pesan</h2>
          <div class="sidebar-actions">
            <button class="sidebar-button" aria-label="Cari">
              <i class="ph-bold ph-magnifying-glass"></i>
            </button>
            <button class="sidebar-button" aria-label="Pengaturan">
              <i class="ph-bold ph-gear"></i>
            </button>
          </div>
        </div>

        <div class="sidebar-filter">
          <button class="sort-button">
            Semua <i class="caret-icon ph-bold ph-caret-down"></i>
          </button>
          <button class="filter-item">Belum Dibaca</button>
        </div>

        <div class="thread-list">
          <a href="#" class="thread-item active">
            <div class="thread-avatar">A</div>
            <div class="thread-info">
              <div class="thread-header">
                <span class="thread-name">Afa</span>
                <span class="thread-time">12:25</span>
              </div>
              <span class="thread-preview">Anda: Gambar dikirim</span>
              <span class="thread-role">Admin</span>
            </div>
          </a>
          <a href="#" class="thread-item">
            <div class="thread-avatar">A</div>
            <div class="thread-info">
              <div class="thread-header">
                <span class="thread-name">Afa</span>
                <span class="thread-time">12:25</span>
              </div>
              <span class="thread-preview">Anda: Gambar dikirim</span>
              <span class="thread-role">Admin</span>
            </div>
          </a>
        </div>
      </aside>

      <section class="chat-area">
        <div class="chat-header">
          <div class="chat-avatar">A</div>
          <span class="chat-name">Stevie</span>
          <button class="chat-detail-button" aria-label="Detail">
            <i class="ph-bold ph-caret-right"></i>
          </button>
        </div>

        <div class="chat-messages">
          <div class="bubble you">
            <p>Halo, apakah properti tersedia untuk akhir pekan ini?</p>
          </div>
          <div class="bubble me">
            <p>Halo! Ya, masih tersedia. Silakan lanjutkan pemesanan.</p>
          </div>

          <template id="bubbleImageTemplate">
            <div class="bubble me bubble-image-only">
              <div class="bubble-image-grid"></div>
            </div>
          </template>

          <template id="bubbleTextTemplate">
            <div class="bubble me">
              <p class="bubble-text"></p>
            </div>
          </template>
        </div>

        <div class="chat-input-container">
          <div class="image-preview-container" id="imagePreview"></div>

          <template id="previewItemTemplate">
            <div class="preview-item">
              <img class="preview-image" src="/" alt="preview" />
              <button class="remove-preview">
                <i class="ph-bold ph-x"></i>
              </button>
            </div>
          </template>

          <div class="chat-input-box">
            <textarea
              class="chat-input"
              placeholder="Tulis pesan..."
              rows="1"></textarea>
            <div class="chat-input-actions">
              <label class="attach-button" aria-label="Lampirkan gambar">
                <i class="ph-bold ph-image"></i>
                <input type="file" accept="image/*" style="display: none" />
              </label>
              <button class="send-button" aria-label="Kirim">
                <i class="ph-bold ph-arrow-up"></i>
              </button>
            </div>
          </div>
        </div>
      </section>
    </main>

    <script src="../scripts/messages.js"></script>
    <script src="../../../components/navbar.js"></script>
    <script src="../../../popups/auth.js"></script>
  </body>
</html>