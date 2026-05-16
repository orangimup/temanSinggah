// ============================================================
// AUTH STEP NAVIGATION & ROUTING
// ============================================================

window.bindAuthPopupEvents = function () {
  const authOverlay = document.getElementById("authOverlay");
  if (!authOverlay) return;

  // Cegah bind dobel
  if (authOverlay.dataset.bound === "true") return;
  authOverlay.dataset.bound = "true";

  const form = authOverlay.querySelector(".auth-form");
  if (!form) return;

  // ============================================================
  // STATE
  // ============================================================

  const authState = {
    isExistingUser: true,
    enteredEmail: "",
    selectedContactMethod: "",
    googleUserIsNew: false,
  };

  // ============================================================
  // HELPER: SHOW STEP — instan, tanpa delay
  // ============================================================

  const showStep = (stepId) => {
    form
      .querySelectorAll(".auth-step")
      .forEach((s) => s.classList.remove("active"));
    const target = form.querySelector(`#${stepId}`);
    if (target) target.classList.add("active");
    else console.error(`Step "${stepId}" tidak ditemukan`);
  };

  // ============================================================
  // MOCK
  // ============================================================

  const isEmailRegistered = (email) => email.includes("gmail");

  const simulateGoogleLogin = () => {
    authState.googleUserIsNew = Math.random() > 0.5;
    return true;
  };

  // ============================================================
  // CLOSE
  // ============================================================

  authOverlay.querySelectorAll("[data-action='close-auth']").forEach((btn) => {
    btn.addEventListener("click", () => window.closeAuthPopup?.());
  });

  authOverlay.addEventListener("click", (e) => {
    if (e.target === authOverlay) window.closeAuthPopup?.();
  });

  // ============================================================
  // STEP 1
  // pilih akun → Step 5
  // "Bukan kamu?" → Step 2
  // ============================================================

  form.querySelectorAll("#authStep1 .auth-option-item").forEach((btn) => {
    btn.addEventListener("click", () => {
      authState.isExistingUser = true;
      showStep("authStep5");
    });
  });

  form
    .querySelector("#authStep1 .auth-submit-button.outline")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      showStep("authStep2");
    });

  // ============================================================
  // STEP 2
  // submit email existing → Step 3
  // submit email baru → Step 4 (ditangani di form submit)
  // Google / Apple / Facebook → Step 4
  // ============================================================

  form
    .querySelector(
      "#authStep2 .auth-social-icon[aria-label='Login dengan Google']",
    )
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      authState.isExistingUser = false;
      showStep("authStep4");
    });

  form
    .querySelector(
      "#authStep2 .auth-social-icon[aria-label='Login dengan Apple']",
    )
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      authState.isExistingUser = false;
      showStep("authStep4");
    });

  form
    .querySelector(
      "#authStep2 .auth-social-icon[aria-label='Login dengan Facebook']",
    )
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      authState.isExistingUser = false;
      showStep("authStep4");
    });

  // ============================================================
  // STEP 3
  // back → Step 2
  // resend OTP
  // ============================================================

  form
    .querySelector("#authStep3 [data-action='prev-step']")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      showStep("authStep2");
    });

  form
    .querySelector("#authStep3 .auth-resend-button")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      const btn = e.currentTarget;
      btn.textContent = "Terkirim";
      setTimeout(() => {
        btn.textContent = "Kirim ulang";
      }, 3000);
    });

  // ============================================================
  // STEP 4
  // back → Step 2
  // ============================================================

  form
    .querySelector("#authStep4 [data-action='prev-step']")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      showStep("authStep2");
    });

  // ============================================================
  // STEP 5
  // "Coba cara lain" → Step 6
  // back → Step 2
  // ============================================================

  form
    .querySelector(
      "#authStep5 [data-action='next-step'][data-target='authStep6']",
    )
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      showStep("authStep6");
    });

  form
    .querySelector("#authStep5 [data-action='prev-step']")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      showStep("authStep2");
    });

  // ============================================================
  // STEP 6
  // Google → Step 5
  // SMS / WA / Email → Step 3
  // ============================================================

  form.querySelectorAll("#authStep6 .auth-option-item").forEach((item) => {
    item.addEventListener("click", () => {
      const label = item.querySelector(".auth-option-label")?.textContent || "";
      const sub = item.querySelector(".auth-option-sub")?.textContent || "";

      if (label.includes("Google")) {
        showStep("authStep5");
        return;
      }

      const subtitle = form.querySelector("#authStep3 .auth-subtitle");
      if (subtitle) subtitle.textContent = `Kami mengirimkan kode ke ${sub}`;

      if (label.includes("SMS")) authState.selectedContactMethod = "sms";
      if (label.includes("WhatsApp"))
        authState.selectedContactMethod = "whatsapp";
      if (label.includes("Email")) authState.selectedContactMethod = "email";

      showStep("authStep3");
    });
  });

  // ============================================================
  // STEP 7
  // back → Step 4
  // setuju → login success
  // ============================================================

  form
    .querySelector("#authStep7 [data-action='prev-step']")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      showStep("authStep4");
    });

  form
    .querySelector("#authStep7 [data-action='login-success']")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      window.onLoginSuccess?.("A");
    });

  // ============================================================
  // FORM SUBMIT — satu listener, tidak ada duplikat
  // ============================================================

  form.addEventListener("submit", (e) => {
    e.preventDefault();

    const activeStep = form.querySelector(".auth-step.active");
    if (!activeStep) return;
    const stepId = activeStep.id;

    // Step 2: email → selalu ke Step 3 dulu
    if (stepId === "authStep2") {
      const email =
        form.querySelector("#authStep2 .auth-input")?.value.trim() || "";
      if (!email) {
        alert("Masukkan email atau nomor telepon");
        return;
      }

      authState.enteredEmail = email;
      authState.isExistingUser = isEmailRegistered(email);

      // Existing maupun baru → Step 3 (verifikasi OTP)
      showStep("authStep3");
    }

    // Step 3: OTP
    else if (stepId === "authStep3") {
      const otp =
        form.querySelector("#authStep3 .auth-input.code")?.value.trim() || "";
      if (!otp || otp.length !== 6) {
        alert("Masukkan kode OTP 6 digit");
        return;
      }

      if (authState.isExistingUser) {
        showStep("authStep7"); // existing → komunitas
      } else {
        showStep("authStep4"); // baru → lengkapi data
      }
    }

    // Step 4: data akun
    else if (stepId === "authStep4") {
      const inputs = form.querySelectorAll("#authStep4 .auth-input");
      const firstName = inputs[0]?.value.trim();
      const lastName = inputs[1]?.value.trim();
      const dob = inputs[2]?.value.trim();
      const password = inputs[3]?.value.trim();

      if (!firstName || !lastName || !dob || !password) {
        alert("Lengkapi semua data akun");
        return;
      }

      showStep("authStep7");
    }

    // Step 5: Google login
    else if (stepId === "authStep5") {
      if (!simulateGoogleLogin()) {
        alert("Google login gagal");
        return;
      }

      if (authState.googleUserIsNew) {
        showStep("authStep7");
      } else {
        window.onLoginSuccess?.("A");
      }
    }
  });

  // Default step saat pertama bind
  showStep("authStep2");
};
