<div id="authOverlay" class="auth-overlay">
    <div class="auth-form-card">

        <!-- STEP PILIH -->
        <div class="auth-step active" id="authStepPilih">
            <div class="auth-header-section">
                <div class="empty-div"></div>
                <button type="button" class="auth-nav-button" data-action="close-auth">
                    <i class="ph-bold ph-x"></i>
                </button>
            </div>
            <div class="auth-body-section">
                <div class="auth-logo-container">
                    <div class="auth-logo">
                        <img class="auth-logo-image" src="/teman_singgah/assets/logo/logo_temansinggah.svg"
                            alt="Teman Singgah" />
                    </div>
                    <h2 class="auth-title center">Selamat datang</h2>
                    <p class="auth-subtitle center">Masuk atau buat akun baru untuk mulai memesan.</p>
                </div>
                <div class="auth-fields">
                    <button class="auth-submit-button" type="button" id="btnKeLogin">Masuk</button>
                    <button class="auth-submit-button outline" type="button" id="btnKeDaftar">Daftar akun baru</button>
                </div>
                <div class="auth-divider">
                    <span class="auth-divider-line"></span>
                    <span class="auth-divider-text">atau lanjutkan dengan</span>
                    <span class="auth-divider-line"></span>
                </div>
                <div class="auth-social-icons">
                    <button type="button" class="auth-social-icon">
                        <img src="/teman_singgah/assets/icons/google.svg" alt="Google"
                            style="width:22px;height:22px;" />
                    </button>
                    <button type="button" class="auth-social-icon">
                        <img src="/teman_singgah/assets/icons/apple.svg" alt="Apple" style="width:22px;height:22px;" />
                    </button>
                    <button type="button" class="auth-social-icon">
                        <img src="/teman_singgah/assets/icons/facebook.svg" alt="Facebook"
                            style="width:22px;height:22px;" />
                    </button>
                </div>
            </div>
        </div>

        <!-- STEP LOGIN -->
        <div class="auth-step" id="authStepLogin">
            <form action="/teman_singgah/auth/proses_login.php" method="POST" autocomplete="off">
                <div class="auth-header-section">
                    <button type="button" class="auth-nav-button" aria-label="Kembali" data-action="ke-pilih"><i
                            class="ph-bold ph-caret-left"></i></button>
                    <button type="button" class="auth-nav-button" aria-label="Tutup" data-action="close-auth"><i
                            class="ph-bold ph-x"></i></button>
                </div>
                <div class="auth-body-section">
                    <div>
                        <h2 class="auth-title">Masuk</h2>
                        <p class="auth-subtitle">Masukkan email dan password kamu.</p>
                    </div>
                    <div class="auth-fields">
                        <fieldset class="auth-field">
                            <legend class="auth-input-label">Email</legend>
                            <div class="auth-input-group">
                                <input type="email" name="email" id="loginEmail" class="auth-input"
                                    placeholder="contoh@email.com" autocomplete="email" required />
                            </div>
                        </fieldset>

                        <fieldset class="auth-field">
                            <legend class="auth-input-label">Password</legend>
                            <div class="auth-input-group auth-password-group">
                                <input type="password" name="password" id="loginPassword" class="auth-input"
                                    placeholder="Masukkan password" autocomplete="current-password" required />
                                <button type="button" class="auth-toggle-password" aria-label="Tampilkan password"><i
                                        class="ph-bold ph-eye"></i></button>
                            </div>
                        </fieldset>
                        <button type="button" class="auth-forgot-link" id="btnLupaPassword">Lupa password?</button>
                    </div>
                </div>
                <div class="auth-footer-section">
                    <div id="pesanLogin"></div>
                    <button class="auth-submit-button" type="submit">Masuk</button>
                    <div class="auth-divider">
                        <span class="auth-divider-line"></span>
                        <span class="auth-divider-text">atau lanjutkan dengan</span>
                        <span class="auth-divider-line"></span>
                    </div>
                    <div class="auth-social-icons">
                        <button type="button" class="auth-social-icon" aria-label="Google"><img
                                src="assets/icons/google.svg" alt="Google" style="width:22px;height:22px;" /></button>
                        <button type="button" class="auth-social-icon" aria-label="Apple"><img
                                src="assets/icons/apple.svg" alt="Apple" style="width:22px;height:22px;" /></button>
                        <button type="button" class="auth-social-icon" aria-label="Facebook"><img
                                src="assets/icons/facebook.svg" alt="Facebook"
                                style="width:22px;height:22px;" /></button>
                    </div>
                    <p class="auth-switch-text">Belum punya akun? <button type="button" class="auth-switch-link"
                            id="btnSwitchKeDaftar">Daftar sekarang</button></p>
                </div>
            </form>
        </div>

        <!-- STEP DAFTAR -->
        <div class="auth-step" id="authStepDaftar1">
            <form action="/teman_singgah/auth/proses_register.php" method="POST" autocomplete="off">
                <div class="auth-header-section">
                    <button type="button" class="auth-nav-button" aria-label="Kembali" data-action="ke-pilih"><i
                            class="ph-bold ph-caret-left"></i></button>
                    <button type="button" class="auth-nav-button" aria-label="Tutup" data-action="close-auth"><i
                            class="ph-bold ph-x"></i></button>
                </div>
                <div class="auth-body-section">
                    <div>
                        <h2 class="auth-title">Buat akun</h2>
                        <p class="auth-subtitle">Isi data di bawah untuk membuat akun baru.</p>
                    </div>
                    <div class="auth-fields">
                        <fieldset class="auth-field">
                            <legend class="auth-input-label">Nama</legend>
                            <div class="auth-input-group">
                                <input type="text" name="nama" id="daftarNama" class="auth-input"
                                    placeholder="Masukkan namamu" autocomplete="name" required />
                            </div>
                        </fieldset>
                        <fieldset class="auth-field">
                            <legend class="auth-input-label">Email</legend>
                            <div class="auth-input-group">
                                <input type="email" name="email" id="daftarEmail" class="auth-input"
                                    placeholder="contoh@email.com" autocomplete="email" required />
                            </div>
                        </fieldset>

                        <fieldset class="auth-field">
                            <legend class="auth-input-label">No. HP</legend>
                            <div class="auth-input-group">
                                <input type="tel" name="no_hp" id="daftarNoHp" class="auth-input"
                                    placeholder="Contoh: 08123456789" autocomplete="tel" required />
                            </div>
                        </fieldset>

                        <fieldset class="auth-field">
                            <legend class="auth-input-label">Password</legend>
                            <div class="auth-input-group auth-password-group">
                                <input type="password" name="password" id="daftarPassword" class="auth-input"
                                    placeholder="Minimal 8 karakter" autocomplete="new-password" required />
                                <button type="button" class="auth-toggle-password" aria-label="Tampilkan password"><i
                                        class="ph-bold ph-eye"></i></button>
                            </div>
                            <p class="auth-field-hint">Gunakan minimal 8 karakter, kombinasi huruf dan angka.</p>
                        </fieldset>
                    </div>
                </div>
                <div class="auth-footer-section">
                    <div id="pesanDaftar"></div>
                    <button class="auth-submit-button" type="submit">Buat akun</button>
                    <div class="auth-divider">
                        <span class="auth-divider-line"></span>
                        <span class="auth-divider-text">atau lanjutkan dengan</span>
                        <span class="auth-divider-line"></span>
                    </div>
                    <div class="auth-social-icons">
                        <button type="button" class="auth-social-icon" aria-label="Google"><img
                                src="assets/icons/google.svg" alt="Google" style="width:22px;height:22px;" /></button>
                        <button type="button" class="auth-social-icon" aria-label="Apple"><img
                                src="assets/icons/apple.svg" alt="Apple" style="width:22px;height:22px;" /></button>
                        <button type="button" class="auth-social-icon" aria-label="Facebook"><img
                                src="assets/icons/facebook.svg" alt="Facebook"
                                style="width:22px;height:22px;" /></button>
                    </div>
                    <p class="auth-switch-text">Sudah punya akun? <button type="button" class="auth-switch-link"
                            id="btnSwitchKeLogin">Masuk</button></p>
                </div>
            </form>
        </div>

        <!-- STEP LUPA PASSWORD -->
        <div class="auth-step" id="authStepLupaPassword">
            <div class="auth-header-section">
                <button type="button" class="auth-nav-button" data-action="ke-login"><i
                        class="ph-bold ph-caret-left"></i></button>
                <button type="button" class="auth-nav-button" data-action="close-auth"><i
                        class="ph-bold ph-x"></i></button>
            </div>
            <div class="auth-body-section">
                <div>
                    <h2 class="auth-title">Lupa password?</h2>
                    <p class="auth-subtitle">Masukkan email akunmu untuk melanjutkan.</p>
                </div>
                <div class="auth-fields">
                    <fieldset class="auth-field">
                        <legend class="auth-input-label">Email</legend>
                        <div class="auth-input-group">
                            <input type="email" id="lupaEmail" class="auth-input" placeholder="contoh@email.com" />
                        </div>
                    </fieldset>
                </div>
            </div>
            <div class="auth-footer-section">
                <button class="auth-submit-button" type="button" id="btnLanjutKeToken">Lanjutkan</button>
                <p class="auth-switch-text">Ingat password? <button type="button" class="auth-switch-link"
                        id="btnSwitchKeLoginDariLupa">Kembali masuk</button></p>
            </div>
        </div>

        <!-- STEP PASSWORD BARU -->
        <div class="auth-step" id="authStepPasswordBaru">
            <div class="auth-header-section">
                <button type="button" class="auth-nav-button" data-action="ke-token"><i
                        class="ph-bold ph-caret-left"></i></button>
                <button type="button" class="auth-nav-button" data-action="close-auth"><i
                        class="ph-bold ph-x"></i></button>
            </div>
            <div class="auth-body-section">
                <div>
                    <h2 class="auth-title">Password baru</h2>
                    <p class="auth-subtitle">Buat password baru untuk akunmu.</p>
                </div>
                <div class="auth-fields">
                    <fieldset class="auth-field">
                        <legend class="auth-input-label">Password Baru</legend>
                        <div class="auth-input-group auth-password-group">
                            <input type="password" id="passwordBaru" class="auth-input"
                                placeholder="Minimal 8 karakter" />
                            <button type="button" class="auth-toggle-password"><i class="ph-bold ph-eye"></i></button>
                        </div>
                    </fieldset>
                    <fieldset class="auth-field">
                        <legend class="auth-input-label">Konfirmasi Password</legend>
                        <div class="auth-input-group auth-password-group">
                            <input type="password" id="passwordKonfirmasi" class="auth-input"
                                placeholder="Ulangi password baru" />
                            <button type="button" class="auth-toggle-password"><i class="ph-bold ph-eye"></i></button>
                        </div>
                    </fieldset>
                </div>
            </div>
            <div class="auth-footer-section">
                <button class="auth-submit-button" type="button" id="btnSimpanPasswordBaru">Simpan password
                    baru</button>
            </div>
        </div>

        <!-- STEP RESET SUKSES -->
        <div class="auth-step" id="authStepResetSukses">
            <div class="auth-header-section">
                <div class="empty-div"></div>
                <button type="button" class="auth-nav-button" data-action="close-auth"><i
                        class="ph-bold ph-x"></i></button>
            </div>
            <div class="auth-body-section">
                <div class="auth-logo-container">
                    <div class="auth-icon-success"><i class="ph-bold ph-check-circle"></i></div>
                    <h2 class="auth-title center">Password berhasil diubah!</h2>
                    <p class="auth-subtitle center">Kamu sekarang bisa masuk menggunakan password baru.</p>
                </div>
            </div>
            <div class="auth-footer-section">
                <button class="auth-submit-button" type="button" id="btnKeLoginDariSukses">Masuk sekarang</button>
            </div>
        </div>

    </div>
</div>