window.bindAuthPopupEvents = function () {
  const authOverlay = document.getElementById("authOverlay");
  if (!authOverlay) return;
  if (authOverlay.dataset.bound === "true") return;
  authOverlay.dataset.bound = "true";

  const form = authOverlay.querySelector(".auth-form");
  if (!form) return;

  // ── helper: tampilkan step ──────────────────────────────────
  const showStep = (stepId) => {
    form.querySelectorAll(".auth-step").forEach((s) => s.classList.remove("active"));
    const target = form.querySelector(`#${stepId}`);
    if (target) target.classList.add("active");
  };

  // ── helper: loading state pada tombol ──────────────────────
  const setLoading = (btn, loading) => {
    btn.disabled = loading;
    btn.dataset.originalText = btn.dataset.originalText || btn.textContent;
    btn.textContent = loading ? "Memproses..." : btn.dataset.originalText;
  };

  // ── helper: pesan error umum (bukan per-field) ─────────────
  const showFormError = (stepId, message) => {
    const step = form.querySelector(`#${stepId}`);
    if (!step) return;
    let errBox = step.querySelector(".auth-form-error");
    if (!errBox) {
      errBox = document.createElement("p");
      errBox.className = "auth-form-error auth-error-msg";
      step.querySelector(".auth-footer-section")?.prepend(errBox);
    }
    errBox.textContent = message;
  };

  const clearFormError = (stepId) => {
    form.querySelector(`#${stepId} .auth-form-error`)?.remove();
  };

  // ── tutup overlay ───────────────────────────────────────────
  authOverlay.querySelectorAll("[data-action='close-auth']").forEach((btn) => {
    btn.addEventListener("click", () => window.closeAuthPopup?.());
  });
  authOverlay.addEventListener("click", (e) => {
    if (e.target === authOverlay) window.closeAuthPopup?.();
  });

  // ── toggle show/hide password ───────────────────────────────
  authOverlay.querySelectorAll(".auth-toggle-password").forEach((btn) => {
    btn.addEventListener("click", () => {
      const input = btn.closest(".auth-password-group").querySelector("input");
      const isHidden = input.type === "password";
      input.type = isHidden ? "text" : "password";
      btn.querySelector("i").className = isHidden
        ? "ph-bold ph-eye-slash"
        : "ph-bold ph-eye";
    });
  });

  // ══════════════════════════════════════════════════════════════
  // STEP PILIH — tombol Masuk / Daftar / Social
  // ══════════════════════════════════════════════════════════════
  form.querySelector("#btnKeLogin")?.addEventListener("click", () => showStep("authStepLogin"));
  form.querySelector("#btnKeDaftar")?.addEventListener("click", () => showStep("authStepDaftar1"));

  form.querySelector("#btnSocialGoogle")?.addEventListener("click", () => {
    window.location.href = "/auth/google_redirect.php";
  });

  form.querySelector("#btnSocialApple")?.addEventListener("click", () => {
    alert("Login dengan Apple akan segera tersedia.");
  });

  form.querySelector("#btnSocialFacebook")?.addEventListener("click", () => {
    alert("Login dengan Facebook akan segera tersedia.");
  });

  // ══════════════════════════════════════════════════════════════
  // STEP LOGIN
  // ══════════════════════════════════════════════════════════════
  form.querySelectorAll("[data-action='ke-pilih']").forEach((btn) => {
    btn.addEventListener("click", () => showStep("authStepPilih"));
  });

  form.querySelector("#btnSubmitLogin")?.addEventListener("click", async () => {
    const email = form.querySelector("#loginEmail")?.value.trim();
    const password = form.querySelector("#loginPassword")?.value;

    if (!email) { showInputError("loginEmail", "Masukkan email kamu"); return; }
    if (!password) { showInputError("loginPassword", "Masukkan password kamu"); return; }

    clearFormError("authStepLogin");
    const btn = form.querySelector("#btnSubmitLogin");
    setLoading(btn, true);

    try {
      const formData = new FormData();
      formData.append("email", email);
      formData.append("password", password);

      const res = await fetch("/auth/proses_login.php", { method: "POST", body: formData });
      const data = await res.json();

      if (data.status === "success") {
        window.onLoginSuccess?.(data.user.nama.charAt(0).toUpperCase());
        window.closeAuthPopup?.();
        // Opsional: reload halaman untuk update UI navbar dll
        // window.location.reload();
      } else {
        showFormError("authStepLogin", data.message);
      }
    } catch (err) {
      showFormError("authStepLogin", "Terjadi kesalahan. Coba lagi.");
    } finally {
      setLoading(btn, false);
    }
  });

  form.querySelector("#btnSwitchKeDaftar")?.addEventListener("click", () => showStep("authStepDaftar1"));

  form.querySelector("#btnLupaPassword")?.addEventListener("click", () => {
    alert("Fitur lupa password akan segera tersedia.");
  });

  // ══════════════════════════════════════════════════════════════
  // STEP DAFTAR — Nama, Email & Password
  // ══════════════════════════════════════════════════════════════
  form.querySelector("#btnDaftar1Lanjut")?.addEventListener("click", async () => {
    const nama = form.querySelector("#daftarNama")?.value.trim();
    const email = form.querySelector("#daftarEmail")?.value.trim();
    const password = form.querySelector("#daftarPassword")?.value;

    if (!nama) { showInputError("daftarNama", "Masukkan namamu"); return; }
    if (!email) { showInputError("daftarEmail", "Masukkan email yang valid"); return; }
    if (!password || password.length < 8) {
      showInputError("daftarPassword", "Password minimal 8 karakter");
      return;
    }

    clearFormError("authStepDaftar1");
    const btn = form.querySelector("#btnDaftar1Lanjut");
    setLoading(btn, true);

    try {
      const formData = new FormData();
      formData.append("nama", nama);
      formData.append("email", email);
      formData.append("password", password);

      const res = await fetch("/auth/proses_register.php", { method: "POST", body: formData });
      const data = await res.json();

      if (data.status === "success") {
        // Langsung arahkan ke step login setelah daftar berhasil
        showStep("authStepLogin");
        // Tampilkan pesan sukses di step login
        const successMsg = document.createElement("p");
        successMsg.className = "auth-success-msg";
        successMsg.textContent = "Akun berhasil dibuat! Silakan masuk.";
        form.querySelector("#authStepLogin .auth-body-section")?.prepend(successMsg);
        setTimeout(() => successMsg.remove(), 4000);
      } else {
        showFormError("authStepDaftar1", data.message);
      }
    } catch (err) {
      showFormError("authStepDaftar1", err.message || "Terjadi kesalahan. Coba lagi.");
    } finally {
      setLoading(btn, false);
    }
  });

  form.querySelector("#btnSwitchKeLogin")?.addEventListener("click", () => showStep("authStepLogin"));

  // ── cegah submit HTML form biasa ───────────────────────────
  form.addEventListener("submit", (e) => e.preventDefault());

  // ── Enter key shortcut ──────────────────────────────────────
  form.addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    e.preventDefault();
    const activeStep = form.querySelector(".auth-step.active");
    if (!activeStep) return;
    const map = {
      authStepLogin: "#btnSubmitLogin",
      authStepDaftar1: "#btnDaftar1Lanjut",
    };
    const sel = map[activeStep.id];
    if (sel) form.querySelector(sel)?.click();
  });

  // ── mulai dari step pilih ───────────────────────────────────
  showStep("authStepPilih");
};

// ── helper: tampilkan error pada input ──────────────────────
function showInputError(inputId, message) {
  const input = document.getElementById(inputId);
  if (!input) return;
  input.classList.add("input-error");
  input.focus();

  const existingMsg = input.parentElement.querySelector(".auth-error-msg");
  if (existingMsg) existingMsg.remove();

  const msg = document.createElement("p");
  msg.className = "auth-error-msg";
  msg.textContent = message;
  input.parentElement.insertAdjacentElement("afterend", msg);

  input.addEventListener("input", () => {
    input.classList.remove("input-error");
    msg.remove();
  }, { once: true });
}