window.openAuthPopup = function () {
  const overlay = document.getElementById("authOverlay");
  if (!overlay) return;

  if (overlay.parentElement !== document.body) {
    document.body.appendChild(overlay);
  }

  overlay.classList.add("open");
  document.body.style.overflow = "hidden";
  window.bindAuthPopupEvents?.();
};

window.closeAuthPopup = function () {
  const overlay = document.getElementById("authOverlay");
  if (!overlay) return;
  overlay.classList.remove("open");
  document.body.style.overflow = "";
};

window.bindAuthPopupEvents = function () {
  const authOverlay = document.getElementById("authOverlay");
  if (!authOverlay) return;
  if (authOverlay.dataset.bound === "true") return;
  authOverlay.dataset.bound = "true";

  const form = authOverlay.querySelector(".auth-form");
  if (!form) return;

  // ── Tampilkan step ────────────────────────────────────────
  const showStep = (stepId) => {
    form.querySelectorAll(".auth-step").forEach((s) => s.classList.remove("active"));
    const target = form.querySelector(`#${stepId}`);
    if (target) target.classList.add("active");
  };

  // ── Tutup overlay ─────────────────────────────────────────
  authOverlay.querySelectorAll("[data-action='close-auth']").forEach((btn) => {
    btn.addEventListener("click", () => window.closeAuthPopup?.());
  });
  authOverlay.addEventListener("click", (e) => {
    if (e.target === authOverlay) window.closeAuthPopup?.();
  });

  // ── Tombol back berdasarkan data-action ───────────────────
  authOverlay.querySelectorAll("[data-action='ke-pilih']").forEach((btn) => {
    btn.addEventListener("click", () => showStep("authStepPilih"));
  });
  authOverlay.querySelectorAll("[data-action='ke-daftar1']").forEach((btn) => {
    btn.addEventListener("click", () => showStep("authStepDaftar1"));
  });
  authOverlay.querySelectorAll("[data-action='ke-daftar2']").forEach((btn) => {
    btn.addEventListener("click", () => showStep("authStepDaftar2"));
  });

  // ── Toggle show/hide password ─────────────────────────────
  authOverlay.querySelectorAll(".auth-toggle-password").forEach((btn) => {
    btn.addEventListener("click", () => {
      const wrap = btn.closest(".auth-password-wrap");
      const input = wrap.querySelector("input");
      const isHidden = input.type === "password";
      input.type = isHidden ? "text" : "password";
      btn.querySelector("i").className = isHidden ? "ph-bold ph-eye-slash" : "ph-bold ph-eye";
    });
  });

  // ══════════════════════════════════════════════════════════
  // STEP PILIH
  // ══════════════════════════════════════════════════════════
  form.querySelector("#btnKeLogin")?.addEventListener("click", () => showStep("authStepLogin"));
  form.querySelector("#btnKeDaftar")?.addEventListener("click", () => showStep("authStepDaftar1"));

  ["#btnSocialGoogle", "#btnSocialApple", "#btnSocialFacebook"].forEach((sel) => {
    form.querySelector(sel)?.addEventListener("click", () => {
      window.onLoginSuccess?.("S");
    });
  });

  // ══════════════════════════════════════════════════════════
  // STEP LOGIN
  // ══════════════════════════════════════════════════════════
  form.querySelector("#btnSubmitLogin")?.addEventListener("click", () => {
    const email    = form.querySelector("#loginEmail")?.value.trim();
    const password = form.querySelector("#loginPassword")?.value;

    if (!email)    { showError("loginEmail",    "Masukkan email kamu"); return; }
    if (!password) { showError("loginPassword", "Masukkan password kamu"); return; }

    window.onLoginSuccess?.(email.charAt(0).toUpperCase());
  });

  form.querySelector("#btnSwitchKeDaftar")?.addEventListener("click", () => showStep("authStepDaftar1"));

  form.querySelector("#btnLupaPassword")?.addEventListener("click", () => {
    alert("Fitur lupa password akan segera tersedia.");
  });

  // ══════════════════════════════════════════════════════════
  // STEP DAFTAR 1 — Nama & Tanggal Lahir
  // ══════════════════════════════════════════════════════════
  form.querySelector("#btnDaftar1Lanjut")?.addEventListener("click", () => {
    const namaDepan    = form.querySelector("#daftarNamaDepan")?.value.trim();
    const namaBelakang = form.querySelector("#daftarNamaBelakang")?.value.trim();
    const tglLahir     = form.querySelector("#daftarTanggalLahir")?.value;

    if (!namaDepan)    { showError("daftarNamaDepan",    "Masukkan nama depan"); return; }
    if (!namaBelakang) { showError("daftarNamaBelakang", "Masukkan nama belakang"); return; }
    if (!tglLahir)     { showError("daftarTanggalLahir", "Masukkan tanggal lahir"); return; }

    showStep("authStepDaftar2");
  });

  form.querySelector("#btnSwitchKeLogin")?.addEventListener("click", () => showStep("authStepLogin"));

  // ══════════════════════════════════════════════════════════
  // STEP DAFTAR 2 — Email & Password
  // ══════════════════════════════════════════════════════════
  form.querySelector("#btnDaftar2Lanjut")?.addEventListener("click", () => {
    const email    = form.querySelector("#daftarEmail")?.value.trim();
    const password = form.querySelector("#daftarPassword")?.value;

    if (!email)                           { showError("daftarEmail",    "Masukkan email yang valid"); return; }
    if (!password || password.length < 8) { showError("daftarPassword", "Password minimal 8 karakter"); return; }

    showStep("authStepDaftar3");
  });

  // ══════════════════════════════════════════════════════════
  // STEP DAFTAR 3 — Komunitas
  // ══════════════════════════════════════════════════════════
  form.querySelector("#btnDaftar3Selesai")?.addEventListener("click", () => {
    const namaDepan = form.querySelector("#daftarNamaDepan")?.value.trim();
    const inisial   = namaDepan ? namaDepan.charAt(0).toUpperCase() : "U";
    window.onLoginSuccess?.(inisial);
  });

  // ── Cegah submit HTML biasa ───────────────────────────────
  form.addEventListener("submit", (e) => e.preventDefault());

  // ── Enter key shortcut ────────────────────────────────────
  form.addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    e.preventDefault();
    const activeStep = form.querySelector(".auth-step.active");
    if (!activeStep) return;
    const map = {
      authStepLogin:   "#btnSubmitLogin",
      authStepDaftar1: "#btnDaftar1Lanjut",
      authStepDaftar2: "#btnDaftar2Lanjut",
      authStepDaftar3: "#btnDaftar3Selesai",
    };
    form.querySelector(map[activeStep.id])?.click();
  });

  showStep("authStepPilih");
};

// ── Helper: tampilkan error input ─────────────────────────
function showError(inputId, message) {
  const input = document.getElementById(inputId);
  if (!input) return;

  input.classList.add("input-error");
  input.focus();

  const old = input.closest(".auth-field")?.querySelector(".auth-error-msg");
  if (old) old.remove();

  const msg = document.createElement("p");
  msg.className = "auth-error-msg";
  msg.textContent = message;
  input.closest(".auth-field")?.appendChild(msg);

  input.addEventListener("input", () => {
    input.classList.remove("input-error");
    msg.remove();
  }, { once: true });
}