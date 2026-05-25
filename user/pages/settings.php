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
if (!empty($user['photo']) && file_exists("../../assets/uploads/photos/" . $user['photo'])) {
    $photo_url = "/teman_singgah/assets/uploads/photos/" . htmlspecialchars($user['photo']);
}
?>
<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pengaturan | Teman Singgah</title>
    <link rel="icon" href="../../assets/logo/logo_temansinggah.svg" />
    <link rel="stylesheet" href="../../components/root.css" />
    <link rel="stylesheet" href="../../components/navbar.css" />
    <link rel="stylesheet" href="../../components/footer.css" />
    <link rel="stylesheet" href="../../popups/auth.css" />
    <link rel="stylesheet" href="../styles/account.css" />
    <link rel="stylesheet" href="../styles/settings.css" />
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
        <section class="account-hero">
            <div class="account-hero-container">
                <div class="account-hero-badge">
                    <i class="ph-fill ph-gear"></i>
                    <span>Pengaturan</span>
                </div>
                <h1 class="account-hero-title">Pengaturan Akun</h1>
                <p class="account-hero-subtitle">Kelola preferensi, keamanan, dan notifikasi akunmu di sini.</p>
            </div>
        </section>

        <section class="account-section">
            <div class="account-container">

                <aside class="account-sidebar">
                    <h2 class="sidebar-title">Profil</h2>
                    <nav class="sidebar-nav">
                        <a href="./account.php" class="sidebar-link">
                            <div class="link-icon primary-avatar"
                                style="<?= $photo_url ? 'padding:0;overflow:hidden;' : '' ?>">
                                <?php if ($photo_url): ?>
                                    <img src="<?= $photo_url ?>" alt="Foto Profil"
                                        style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />
                                <?php else: ?>
                                    <?= $inisial ?>
                                <?php endif; ?>
                            </div>
                            <span>Tentang Saya</span>
                        </a>
                        <a href="./history.php" class="sidebar-link">
                            <div class="link-icon"><i class="ph-bold ph-suitcase"></i></div>
                            <span>Riwayat Perjalanan</span>
                        </a>
                        <a href="./settings.php" class="sidebar-link active">
                            <div class="link-icon"><i class="ph-bold ph-gear"></i></div>
                            <span>Pengaturan</span>
                        </a>
                    </nav>
                </aside>

                <div class="account-content">
                    <div class="content-header">
                        <h2 class="content-title">Pengaturan</h2>
                    </div>

                    <div class="settings-group">
                        <h3 class="settings-group-title">Keamanan Akun</h3>

                        <div class="settings-item">
                            <div class="settings-item-info">
                                <span class="settings-item-label">Nama</span>
                                <span class="settings-item-value"><?= htmlspecialchars($user['nama']) ?></span>
                            </div>
                            <button class="settings-btn-outline" data-modal="nama">Ubah</button>
                        </div>

                        <div class="settings-item">
                            <div class="settings-item-info">
                                <span class="settings-item-label">Email</span>
                                <span class="settings-item-value"><?= htmlspecialchars($user['email']) ?></span>
                            </div>
                            <button class="settings-btn-outline" data-modal="email">Ubah</button>
                        </div>

                        <div class="settings-item">
                            <div class="settings-item-info">
                                <span class="settings-item-label">No. HP</span>
                                <span
                                    class="settings-item-value"><?= !empty($user['no_hp']) ? htmlspecialchars($user['no_hp']) : 'Belum diatur' ?></span>
                            </div>
                            <button class="settings-btn-outline" data-modal="phone">Ubah</button>
                        </div>

                        <div class="settings-item">
                            <div class="settings-item-info">
                                <span class="settings-item-label">Password</span>
                                <span class="settings-item-value">••••••••</span>
                            </div>
                            <button class="settings-btn-outline" data-modal="password">Ubah</button>
                        </div>

                        <div class="section-divider"></div>

                        <div class="settings-group">
                            <h3 class="settings-group-title">Notifikasi</h3>

                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <span class="settings-item-label">Email konfirmasi booking</span>
                                    <span class="settings-item-sub">Kirim email saat pesanan berhasil dibuat</span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="toggleEmailBooking" checked />
                                    <span class="toggle-track"></span>
                                </label>
                            </div>

                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <span class="settings-item-label">Pengingat check-in</span>
                                    <span class="settings-item-sub">Notifikasi H-1 sebelum tanggal check-in</span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="toggleCheckinReminder" checked />
                                    <span class="toggle-track"></span>
                                </label>
                            </div>

                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <span class="settings-item-label">Promo &amp; penawaran</span>
                                    <span class="settings-item-sub">Terima info diskon dan promo terbaru</span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="togglePromo" />
                                    <span class="toggle-track"></span>
                                </label>
                            </div>

                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <span class="settings-item-label">Pesan dari host</span>
                                    <span class="settings-item-sub">Notifikasi saat ada pesan masuk</span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" id="toggleMessages" checked />
                                    <span class="toggle-track"></span>
                                </label>
                            </div>
                        </div>

                        <div class="section-divider"></div>

                        <div class="settings-group">
                            <h3 class="settings-group-title">Bahasa &amp; Tampilan</h3>

                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <span class="settings-item-label">Bahasa</span>
                                    <span class="settings-item-value">Bahasa Indonesia</span>
                                </div>
                                <button class="settings-btn-outline" data-modal="language">Ubah</button>
                            </div>

                            <div class="settings-item">
                                <div class="settings-item-info">
                                    <span class="settings-item-label">Mata Uang</span>
                                    <span class="settings-item-value">IDR — Rupiah Indonesia</span>
                                </div>
                                <button class="settings-btn-outline" data-modal="currency">Ubah</button>
                            </div>
                        </div>

                        <div class="section-divider"></div>

                        <div class="settings-item">
                            <div class="settings-item-info">
                                <span class="settings-item-label">Hapus Akun</span>
                                <span class="settings-item-sub">Tindakan ini permanen dan tidak bisa dibatalkan</span>
                            </div>
                            <button class="settings-btn-danger" data-modal="delete-account">Hapus Akun</button>
                        </div>
                    </div>

                </div>
        </section>
    </main>

    <div class="settings-modal-overlay" id="modal-nama">
        <div class="settings-modal">
            <div class="settings-modal-header">
                <h3>Ubah Nama</h3>
                <button class="settings-modal-close" data-close-modal><i class="ph-bold ph-x"></i></button>
            </div>
            <div class="settings-modal-body">
                <div class="settings-form-field">
                    <label>Nama baru</label>
                    <input type="text" placeholder="Masukkan nama baru" class="settings-input"
                        value="<?= htmlspecialchars($user['nama']) ?>" />
                </div>
            </div>
            <div class="settings-modal-footer">
                <button class="settings-btn-outline" data-close-modal>Batal</button>
                <button class="settings-btn-primary" onclick="showToast('Nama berhasil diubah.')">Simpan
                    Perubahan</button>
            </div>
        </div>
    </div>

    <div class="settings-modal-overlay" id="modal-email">
        <div class="settings-modal">
            <div class="settings-modal-header">
                <h3>Ubah Email</h3>
                <button class="settings-modal-close" data-close-modal><i class="ph-bold ph-x"></i></button>
            </div>
            <div class="settings-modal-body">
                <div class="settings-form-field">
                    <label>Email baru</label>
                    <input type="email" placeholder="contoh@email.com" class="settings-input" />
                </div>
                <div class="settings-form-field">
                    <label>Konfirmasi password</label>
                    <input type="password" placeholder="Masukkan passwordmu" class="settings-input" />
                </div>
                <p class="settings-modal-hint">Link verifikasi akan dikirim ke email baru kamu.</p>
            </div>
            <div class="settings-modal-footer">
                <button class="settings-btn-outline" data-close-modal>Batal</button>
                <button class="settings-btn-primary"
                    onclick="showToast('Link verifikasi telah dikirim ke email baru.')">Simpan Perubahan</button>
            </div>
        </div>
    </div>

    <div class="settings-modal-overlay" id="modal-password">
        <div class="settings-modal">
            <div class="settings-modal-header">
                <h3>Ubah Password</h3>
                <button class="settings-modal-close" data-close-modal><i class="ph-bold ph-x"></i></button>
            </div>
            <div class="settings-modal-body">
                <div class="settings-form-field">
                    <label>Password saat ini</label>
                    <div class="settings-input-group">
                        <input type="password" placeholder="Masukkan password lama" class="settings-input"
                            id="oldPassInput" />
                        <button type="button" class="settings-eye-btn" data-target="oldPassInput">
                            <i class="ph-bold ph-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="settings-form-field">
                    <label>Password baru</label>
                    <div class="settings-input-group">
                        <input type="password" placeholder="Minimal 8 karakter" class="settings-input"
                            id="newPassInput" />
                        <button type="button" class="settings-eye-btn" data-target="newPassInput">
                            <i class="ph-bold ph-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="settings-form-field">
                    <label>Konfirmasi password baru</label>
                    <div class="settings-input-group">
                        <input type="password" placeholder="Ulangi password baru" class="settings-input"
                            id="confirmPassInput" />
                        <button type="button" class="settings-eye-btn" data-target="confirmPassInput">
                            <i class="ph-bold ph-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="settings-modal-footer">
                <button class="settings-btn-outline" data-close-modal>Batal</button>
                <button class="settings-btn-primary" onclick="showToast('Password berhasil diubah.')">Simpan</button>
            </div>
        </div>
    </div>

    <div class="settings-modal-overlay" id="modal-phone">
        <div class="settings-modal">
            <div class="settings-modal-header">
                <h3>Ubah No. HP</h3>
                <button class="settings-modal-close" data-close-modal><i class="ph-bold ph-x"></i></button>
            </div>
            <form method="POST" action="/teman_singgah/user/pages/update_account.php">
                <input type="hidden" name="nama" value="<?= htmlspecialchars($user['nama']) ?>">
                <input type="hidden" name="pekerjaan" value="<?= htmlspecialchars($user['pekerjaan'] ?? '') ?>">
                <input type="hidden" name="lokasi" value="<?= htmlspecialchars($user['lokasi'] ?? '') ?>">
                <input type="hidden" name="bahasa" value="<?= htmlspecialchars($user['bahasa'] ?? '') ?>">
                <input type="hidden" name="tentang" value="<?= htmlspecialchars($user['tentang'] ?? '') ?>">
                <input type="hidden" name="destinasi_impian"
                    value="<?= htmlspecialchars($user['destinasi_impian'] ?? '') ?>">
                <input type="hidden" name="hobi" value="<?= htmlspecialchars($user['hobi'] ?? '') ?>">
                <input type="hidden" name="hewan_peliharaan"
                    value="<?= htmlspecialchars($user['hewan_peliharaan'] ?? '') ?>">
                <input type="hidden" name="dekade_lahir" value="<?= htmlspecialchars($user['dekade_lahir'] ?? '') ?>">
                <input type="hidden" name="sekolah" value="<?= htmlspecialchars($user['sekolah'] ?? '') ?>">
                <input type="hidden" name="lagu_favorit" value="<?= htmlspecialchars($user['lagu_favorit'] ?? '') ?>">
                <div class="settings-modal-body">
                    <div class="settings-form-field">
                        <label>Nomor HP baru</label>
                        <input type="tel" name="no_hp" placeholder="Contoh: 08123456789" class="settings-input"
                            value="<?= htmlspecialchars($user['no_hp'] ?? '') ?>" required />
                    </div>
                </div>
                <div class="settings-modal-footer">
                    <button type="button" class="settings-btn-outline" data-close-modal>Batal</button>
                    <button type="submit" class="settings-btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="settings-modal-overlay" id="modal-delete-account">
        <div class="settings-modal">
            <div class="settings-modal-header">
                <h3 class="danger-title">Hapus Akun</h3>
                <button class="settings-modal-close" data-close-modal><i class="ph-bold ph-x"></i></button>
            </div>
            <div class="settings-modal-body">
                <div class="settings-danger-box">
                    <i class="ph-bold ph-warning-circle"></i>
                    <p>Semua data akunmu termasuk riwayat perjalanan dan favorit akan <strong>dihapus permanen</strong>
                        dan tidak bisa dipulihkan.</p>
                </div>
                <div class="settings-form-field">
                    <label>Ketik <strong>HAPUS AKUN</strong> untuk konfirmasi</label>
                    <input type="text" placeholder="HAPUS AKUN" class="settings-input" id="deleteConfirmInput" />
                </div>
            </div>
            <div class="settings-modal-footer">
                <button class="settings-btn-outline" data-close-modal>Batal</button>
                <button class="settings-btn-danger" id="btnConfirmDelete">Hapus Akun Saya</button>
            </div>
        </div>
    </div>

    <div class="settings-toast" id="settingsToast"></div>

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
                    <li><a href="about_us.php" class="footer-link">Tentang Kami</a></li>
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

    <script src="../../components/navbar.js"></script>
    <script src="../../popups/auth.js"></script>
    <script src="../scripts/settings.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-modal]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const modal = document.getElementById('modal-' + btn.getAttribute('data-modal'));
                    if (modal) modal.classList.add('open');
                });
            });

            document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    btn.closest('.settings-modal-overlay').classList.remove('open');
                });
            });

            document.querySelectorAll('.settings-modal-overlay').forEach(function (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) overlay.classList.remove('open');
                });
            });

            document.querySelectorAll('.settings-eye-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const input = document.getElementById(btn.getAttribute('data-target'));
                    if (!input) return;
                    const isHidden = input.type === 'password';
                    input.type = isHidden ? 'text' : 'password';
                    btn.querySelector('i').className = isHidden ? 'ph-bold ph-eye-slash' : 'ph-bold ph-eye';
                });
            });

            const btnDelete = document.getElementById('btnConfirmDelete');
            if (btnDelete) {
                btnDelete.addEventListener('click', function () {
                    const input = document.getElementById('deleteConfirmInput').value.trim();
                    if (input !== 'HAPUS AKUN') {
                        if (typeof showToast === 'function') {
                            showToast('Ketik HAPUS AKUN untuk konfirmasi.');
                        } else {
                            alert('Ketik HAPUS AKUN untuk konfirmasi.');
                        }
                        return;
                    }
                    window.location.href = '/teman_singgah/user/pages/delete_account.php';
                });
            }
        });
    </script>
</body>

</html>