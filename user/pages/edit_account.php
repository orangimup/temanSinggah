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

$inisial = strtoupper(mb_substr($user['nama'], 0, 1));
$abs_path = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $user['photo'];
$photo_url = '';
if (!empty($user['photo']) && file_exists($abs_path)) {
  $photo_url = '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($user['photo']);
}
// Tangkap pesan sukses/error dari redirect
$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Edit Akun | Teman Singgah</title>
  <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../components/root.css" />
  <link rel="stylesheet" href="../../components/navbar.css" />
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
    /* ── Avatar preview ── */
    .edit-avatar-container {
      display: flex;
      justify-content: center;
      padding: 2.5rem 0 1rem;
    }

    .edit-avatar {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 1rem;
    }

    .avatar-large.edit {
      width: 140px;
      height: 140px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      font-weight: 700;
      background: var(--color-primary, #7c3a2d);
      color: #fff;
      overflow: hidden;
      position: relative;
      border: 4px solid #fff;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12);
    }

    .avatar-large.edit img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }

    .avatar-photo-button {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      cursor: pointer;
      padding: 8px 18px;
      border-radius: 20px;
      font-size: 0.875rem;
      font-weight: 500;
      background: #f5f0ef;
      color: #7c3a2d;
      border: 1.5px solid #e8d5d0;
      transition: background 0.2s;
    }

    .avatar-photo-button:hover {
      background: #ecdbd8;
    }

    /* ── Toast notifikasi ── */
    .toast {
      position: fixed;
      bottom: 80px;
      left: 50%;
      transform: translateX(-50%);
      background: #1a1a2e;
      color: #fff;
      padding: 14px 26px;
      border-radius: 99px;
      font-size: 0.875rem;
      z-index: 9999;
      opacity: 0;
      transition: opacity 0.3s;
      white-space: nowrap;
    }

    .toast.show {
      opacity: 1;
    }

    .toast.error {
      background: #c0392b;
    }

    /* ── Input fields ── */
    .edit-input {
      width: 100%;
      padding: 20px 0;
      border: none;
      border-bottom: 1.5px solid #e0e0e0;
      font-size: 0.95rem;
      font-family: inherit;
      background: transparent;
      outline: none;
      color: inherit;
      transition: border-color 0.2s;
      box-sizing: border-box;
    }

    .edit-input:focus {
      border-bottom-color: #7c3a2d;
    }

    .edit-input::placeholder {
      color: #aaa;
    }

    .profile-field-content.input {
      flex-direction: column;
      gap: 4px;
      flex: 1;
    }

    textarea.edit-input {
      resize: vertical;
      min-height: 90px;
    }

    /* ── Section layout ── */
    .edit-section {
      margin-bottom: 2rem;
    }

    .edit-section-header {
      margin-bottom: 1.5rem;
    }

    .edit-section-title {
      font-size: 1.2rem;
      font-weight: 600;
      margin-bottom: 0.4rem;
    }

    .edit-section-desc {
      font-size: 0.875rem;
      color: #888;
      line-height: 1.5;
    }

    .profile-field-label.edit {
      font-size: 0.78rem;
      color: #333;
      font-weight: 500;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      padding: 0.5rem;
    }

    .profile-fields-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.5rem 2rem;
    }

    @media (max-width: 700px) {
      .profile-fields-grid {
        grid-template-columns: 1fr;
      }
    }

    /* ── Account content edit mode ── */
    .account-content.edit {
      padding: 0 2rem 2rem;
    }

    /* ── Edit footer ── */
    .edit-footer {
      position: sticky;
      bottom: 0;
      background: #fff;
      border-top: 1px solid #f0eae8;
      padding: 1rem 2rem;
      z-index: 10;
    }

    .footer-actions {
      display: flex;
      justify-content: flex-end;
    }

    .submit-button {
      padding: 12px 36px;
      background: #7c3a2d;
      color: #fff;
      border: none;
      border-radius: 10px;
      font-size: 0.95rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s, transform 0.1s;
    }

    .submit-button:hover {
      background: #6b3025;
    }

    .submit-button:active {
      transform: scale(0.98);
    }
  </style>
</head>

<body>
  <!-- Toast -->
  <?php if ($success === 'saved'): ?>
    <div class="toast show" id="toast">Profil berhasil disimpan!</div>
  <?php elseif ($error): ?>
    <div class="toast show error" id="toast">
      <?php
      $err_msg = [
        'foto_tipe' => 'Format foto tidak didukung. Gunakan JPG, PNG, atau WebP.',
        'foto_ukuran' => 'Ukuran foto maksimal 2MB.',
        'foto_gagal' => 'Gagal mengupload foto.',
        'gagal' => 'Gagal menyimpan profil, coba lagi.',
      ];
      echo $err_msg[$error] ?? 'Terjadi kesalahan.';
      ?>
    </div>
  <?php endif; ?>

  <header class="navbar">
    <nav class="navbar-container">
      <a href="../../index.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
        <img src="../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
      </div>
      <ul class="nav-menu">
        <li class="nav-item"><a href="../../index.php" class="nav-link">Cari Penginapan</a></li>
        <li class="nav-item"><a href="promo_deals.php" class="nav-link">Promo & Deals</a></li>
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
          <button class="icon-button hamburger" aria-label="Hamburger"><i class="ph-bold ph-list"></i></button>
        </div>
        <div id="hamburgerDropdown"></div>
        <div id="languagePopup"></div>
        <div id="authPopup"></div>
      </div>
    </nav>
  </header>

  <main class="main-content">
    <section class="account-section">
      <!-- Form pakai enctype multipart untuk upload foto -->
      <form method="POST" action="./update_account.php" enctype="multipart/form-data">
        <div class="account-container">

          <!-- Avatar -->
          <div class="edit-avatar-container">
            <div class="edit-avatar">
              <div class="avatar-large edit" id="avatarPreview">
                <?php if ($photo_url): ?>
                  <img src="<?= $photo_url ?>" alt="Foto Profil" id="avatarImg" />
                <?php else: ?>
                  <span id="avatarInitial"><?= $inisial ?></span>
                <?php endif; ?>
              </div>
              <label class="avatar-photo-button" for="photoInput">
                <i class="ph-bold ph-camera"></i>
                <?= $photo_url ? 'Ganti' : 'Tambah' ?>
              </label>
              <!-- Input file tersembunyi -->
              <input type="file" id="photoInput" name="photo" accept="image/jpeg,image/png,image/webp"
                style="display:none;" />
            </div>
          </div>

          <div class="account-content edit">

            <!-- Profil Saya -->
            <div class="edit-section">
              <div class="edit-section-header">
                <h2 class="edit-section-title">Profil Saya</h2>
                <p class="edit-section-desc">Host dan tamu dapat melihat profilmu dan ini membantu membangun kepercayaan
                  di komunitas Teman Singgah.</p>
              </div>

              <div class="profile-fields-grid">

                <div class="profile-field-item">
                  <div class="profile-field-icon"><i class="ph-bold ph-user"></i></div>
                  <div class="profile-field-content input">
                    <span class="profile-field-label edit">Nama lengkap</span>
                    <input class="edit-input" type="text" name="nama" value="<?= htmlspecialchars($user['nama']) ?>"
                      placeholder="Nama kamu" required />
                  </div>
                </div>

                <div class="profile-field-item">
                  <div class="profile-field-icon"><i class="ph-bold ph-briefcase"></i></div>
                  <div class="profile-field-content input">
                    <span class="profile-field-label edit">Pekerjaan saya</span>
                    <input class="edit-input" type="text" name="pekerjaan"
                      value="<?= htmlspecialchars($user['pekerjaan'] ?? '') ?>"
                      placeholder="Contoh: Software Engineer" />
                  </div>
                </div>

                <div class="profile-field-item">
                  <div class="profile-field-icon"><i class="ph-bold ph-house"></i></div>
                  <div class="profile-field-content input">
                    <span class="profile-field-label edit">Tempat tinggal saya</span>
                    <input class="edit-input" type="text" name="lokasi"
                      value="<?= htmlspecialchars($user['lokasi'] ?? '') ?>" placeholder="Contoh: Jakarta, Indonesia" />
                  </div>
                </div>

                <div class="profile-field-item">
                  <div class="profile-field-icon"><i class="ph-bold ph-translate"></i></div>
                  <div class="profile-field-content input">
                    <span class="profile-field-label edit">Bahasa yang saya gunakan</span>
                    <input class="edit-input" type="text" name="bahasa"
                      value="<?= htmlspecialchars($user['bahasa'] ?? '') ?>" placeholder="Contoh: Indonesia, English" />
                  </div>
                </div>

                <div class="profile-field-item">
                  <div class="profile-field-icon"><i class="ph-bold ph-globe-hemisphere-east"></i></div>
                  <div class="profile-field-content input">
                    <span class="profile-field-label edit">Destinasi impian saya</span>
                    <input class="edit-input" type="text" name="destinasi_impian"
                      value="<?= htmlspecialchars($user['destinasi_impian'] ?? '') ?>"
                      placeholder="Contoh: Bali, Raja Ampat" />
                  </div>
                </div>

                <div class="profile-field-item">
                  <div class="profile-field-icon"><i class="ph-bold ph-clock"></i></div>
                  <div class="profile-field-content input">
                    <span class="profile-field-label edit">Saya menghabiskan waktu untuk</span>
                    <input class="edit-input" type="text" name="hobi"
                      value="<?= htmlspecialchars($user['hobi'] ?? '') ?>"
                      placeholder="Contoh: Membaca, Memasak, Hiking" />
                  </div>
                </div>

                <div class="profile-field-item">
                  <div class="profile-field-icon"><i class="ph-bold ph-paw-print"></i></div>
                  <div class="profile-field-content input">
                    <span class="profile-field-label edit">Hewan peliharaan</span>
                    <input class="edit-input" type="text" name="hewan_peliharaan"
                      value="<?= htmlspecialchars($user['hewan_peliharaan'] ?? '') ?>"
                      placeholder="Contoh: Kucing, Anjing" />
                  </div>
                </div>

                <div class="profile-field-item">
                  <div class="profile-field-icon"><i class="ph-bold ph-balloon"></i></div>
                  <div class="profile-field-content input">
                    <span class="profile-field-label edit">Dekade kelahiran saya</span>
                    <input class="edit-input" type="text" name="dekade_lahir"
                      value="<?= htmlspecialchars($user['dekade_lahir'] ?? '') ?>" placeholder="Contoh: 2000-an" />
                  </div>
                </div>

                <div class="profile-field-item">
                  <div class="profile-field-icon"><i class="ph-bold ph-graduation-cap"></i></div>
                  <div class="profile-field-content input">
                    <span class="profile-field-label edit">Tempat saya bersekolah</span>
                    <input class="edit-input" type="text" name="sekolah"
                      value="<?= htmlspecialchars($user['sekolah'] ?? '') ?>"
                      placeholder="Contoh: Universitas Indonesia" />
                  </div>
                </div>

                <div class="profile-field-item">
                  <div class="profile-field-icon"><i class="ph-bold ph-music-note"></i></div>
                  <div class="profile-field-content input">
                    <span class="profile-field-label edit">Lagu favorit saya</span>
                    <input class="edit-input" type="text" name="lagu_favorit"
                      value="<?= htmlspecialchars($user['lagu_favorit'] ?? '') ?>"
                      placeholder="Contoh: Apa yang ingin kamu ceritakan" />
                  </div>
                </div>

              </div>
            </div>

            <div class="section-divider"></div>

            <!-- Tentang Saya -->
            <div class="edit-section">
              <div class="edit-section-header">
                <h2 class="edit-section-title">Tentang Saya</h2>
              </div>
              <div class="profile-field-item">
                <div class="profile-field-icon"><i class="ph-bold ph-pencil-line"></i></div>
                <div class="profile-field-content input" style="width:100%;">
                  <span class="profile-field-label edit">Tulis sesuatu yang seru dan menarik</span>
                  <textarea class="edit-input" name="tentang" rows="3"
                    placeholder="Ceritakan sedikit tentang dirimu..."><?= htmlspecialchars($user['tentang'] ?? '') ?></textarea>
                </div>
              </div>
            </div>

            <div class="section-divider"></div>

          </div>
        </div>

        <footer class="edit-footer">
          <div class="footer-actions">
            <div class="empty-div"></div>
            <button type="submit" class="submit-button">Selesai</button>
          </div>
        </footer>

      </form>
    </section>
  </main>

  <script src="../../user/scripts/account.js"></script>
  <script src="../../components/navbar.js"></script>
  <script src="../../popups/auth.js"></script>
  <script>
    // Preview foto sebelum upload
    const photoInput = document.getElementById('photoInput');
    const avatarPreview = document.getElementById('avatarPreview');

    photoInput.addEventListener('change', function () {
      const file = this.files[0];
      if (!file) return;

      // Validasi tipe di client-side
      const allowed = ['image/jpeg', 'image/png', 'image/webp'];
      if (!allowed.includes(file.type)) {
        alert('Format tidak didukung. Gunakan JPG, PNG, atau WebP.');
        return;
      }
      // Validasi ukuran (2MB)
      if (file.size > 2 * 1024 * 1024) {
        alert('Ukuran foto maksimal 2MB.');
        return;
      }

      const reader = new FileReader();
      reader.onload = function (e) {
        avatarPreview.innerHTML = `<img src="${e.target.result}" alt="Preview" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />`;
      };
      reader.readAsDataURL(file);
    });

    // Auto-hide toast
    const toast = document.getElementById('toast');
    if (toast) {
      setTimeout(() => { toast.style.opacity = '0'; }, 3500);
    }
  </script>
</body>

</html>