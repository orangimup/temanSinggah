// ── Buka / tutup modal ──────────────────────────────────────
function bukaModal(step) {
  const overlay = document.getElementById('authOverlay');
  overlay.classList.add('open');
  tampilStep(step || 'authStepPilih');
}

function tutupModal() {
  const overlay = document.getElementById('authOverlay');
  overlay.classList.remove('open');
}

function tampilStep(stepId) {
  document.querySelectorAll('.auth-step').forEach(s => s.classList.remove('active'));
  const target = document.getElementById(stepId);
  if (target) target.classList.add('active');
}

// ── Tampilkan pesan error / sukses di dalam step ────────────
function tampilPesan(stepId, pesan, tipe) {
  const step = document.getElementById(stepId);
  if (!step) return;

  let box = step.querySelector('.auth-pesan');
  if (!box) {
    box = document.createElement('p');
    box.className = 'auth-pesan';
    step.querySelector('.auth-footer-section')?.prepend(box);
  }

  box.textContent = pesan;
  box.style.cssText = tipe === 'error'
    ? 'color:#cc2b2b;font-size:13px;margin-bottom:8px;'
    : 'color:#27500a;background:#eaf3de;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:8px;';
}

// ── Navigasi tombol ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {

  // Tutup modal
  document.querySelectorAll('[data-action="close-auth"]').forEach(btn => {
    btn.addEventListener('click', tutupModal);
  });

  // Klik backdrop
  document.getElementById('authOverlay')?.addEventListener('click', function (e) {
    if (e.target === this) tutupModal();
  });

  // Navigasi antar step
  document.getElementById('btnKeLogin')?.addEventListener('click', () => tampilStep('authStepLogin'));
  document.getElementById('btnKeDaftar')?.addEventListener('click', () => tampilStep('authStepDaftar1'));
  document.getElementById('btnSwitchKeDaftar')?.addEventListener('click', () => tampilStep('authStepDaftar1'));
  document.getElementById('btnSwitchKeLogin')?.addEventListener('click', () => tampilStep('authStepLogin'));
  document.getElementById('btnLupaPassword')?.addEventListener('click', () => tampilStep('authStepLupaPassword'));
  document.getElementById('btnSwitchKeLoginDariLupa')?.addEventListener('click', () => tampilStep('authStepLogin'));
  document.getElementById('btnKeLoginDariSukses')?.addEventListener('click', () => tampilStep('authStepLogin'));

  document.querySelectorAll('[data-action="ke-pilih"]').forEach(btn => {
    btn.addEventListener('click', () => tampilStep('authStepPilih'));
  });
  document.querySelectorAll('[data-action="ke-login"]').forEach(btn => {
    btn.addEventListener('click', () => tampilStep('authStepLogin'));
  });
  document.querySelectorAll('[data-action="ke-lupa"]').forEach(btn => {
    btn.addEventListener('click', () => tampilStep('authStepLupaPassword'));
  });

  // Toggle show/hide password
  document.querySelectorAll('.auth-toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
      const input = btn.closest('.auth-password-group').querySelector('input');
      const isHidden = input.type === 'password';
      input.type = isHidden ? 'text' : 'password';
      btn.querySelector('i').className = isHidden ? 'ph-bold ph-eye-slash' : 'ph-bold ph-eye';
    });
  });

  // ── Reset password flow (pure frontend) ──────────────────

  // Step 1: email → langsung ke password baru
  document.getElementById('btnLanjutKeToken')?.addEventListener('click', () => {
    const email = document.getElementById('lupaEmail')?.value.trim();
    if (!email) {
      tampilPesan('authStepLupaPassword', 'Masukkan email kamu terlebih dahulu.', 'error');
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      tampilPesan('authStepLupaPassword', 'Format email tidak valid.', 'error');
      return;
    }
    tampilStep('authStepPasswordBaru');
  });

  // Step 2: simpan password baru
  document.getElementById('btnSimpanPasswordBaru')?.addEventListener('click', () => {
    const pass = document.getElementById('passwordBaru')?.value;
    const konfirmasi = document.getElementById('passwordKonfirmasi')?.value;
    if (!pass || pass.length < 8) {
      tampilPesan('authStepPasswordBaru', 'Password minimal 8 karakter.', 'error');
      return;
    }
    if (pass !== konfirmasi) {
      tampilPesan('authStepPasswordBaru', 'Password dan konfirmasi tidak cocok.', 'error');
      return;
    }
    tampilStep('authStepResetSukses');
  });

  // ── Baca query param → buka modal + tampilkan pesan ───────
  const params = new URLSearchParams(window.location.search);
  const authStep = params.get('auth');
  const error = params.get('error');
  const success = params.get('success');

  const pesanError = {
    field_kosong:    'Semua field harus diisi.',
    email_invalid:   'Format email tidak valid.',
    password_pendek: 'Password minimal 8 karakter.',
    email_exists:    'Email sudah digunakan, coba email lain.',
    email_notfound:  'Email tidak ditemukan.',
    password_salah:  'Password salah.',
    nonaktif:        'Akun kamu telah dinonaktifkan.',
    gagal:           'Terjadi kesalahan, coba lagi.',
  };

  if (authStep === 'daftar') {
    bukaModal('authStepDaftar1');
    if (error) tampilPesan('authStepDaftar1', pesanError[error] || error, 'error');

  } else if (authStep === 'login') {
    bukaModal('authStepLogin');
    if (error)   tampilPesan('authStepLogin', pesanError[error] || error, 'error');
    if (success === 'registered') tampilPesan('authStepLogin', 'Akun berhasil dibuat! Silakan masuk.', 'success');
  }

  // Hapus query param dari URL tanpa reload
  if (authStep) {
    const url = new URL(window.location);
    url.searchParams.delete('auth');
    url.searchParams.delete('error');
    url.searchParams.delete('success');
    window.history.replaceState({}, '', url);
  }
});