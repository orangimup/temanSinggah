window.bindAuthPopupEvents = function () {
  const authOverlay = document.getElementById("authOverlay");
  if (!authOverlay) return;

  if (authOverlay.dataset.bound === "true") return;
  authOverlay.dataset.bound = "true";

  const form = authOverlay.querySelector(".auth-form");
  if (!form) return;

  const authState = {
    isExistingUser: true,
    enteredEmail: "",
    selectedContactMethod: "",
    googleUserIsNew: false,
  };

  const showStep = (stepId) => {
    console.log("[AUTH] Navigasi ke:", stepId);
    form
      .querySelectorAll(".auth-step")
      .forEach((s) => s.classList.remove("active"));
    const target = form.querySelector(`#${stepId}`);
    if (target) {
      target.classList.add("active");
      console.log("[AUTH] ✓ Berhasil ke:", stepId);
    } else {
      console.error(`[AUTH] ✗ Step "${stepId}" tidak ditemukan`);
    }
  };

  const isEmailRegistered = (email) => email.includes("gmail");

  authOverlay.querySelectorAll("[data-action='close-auth']").forEach((btn) => {
    btn.addEventListener("click", () => window.closeAuthPopup?.());
  });

  authOverlay.addEventListener("click", (e) => {
    if (e.target === authOverlay) window.closeAuthPopup?.();
  });

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

  form
    .querySelector("#authStep2 .auth-submit-button")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      const email =
        form.querySelector("#authStep2 .auth-input")?.value.trim() || "";
      if (!email) {
        alert("Masukkan email atau nomor telepon");
        return;
      }

      authState.enteredEmail = email;
      authState.isExistingUser = isEmailRegistered(email);
      showStep("authStep3");
    });

  form
    .querySelector(
      "#authStep2 .auth-social-icon[aria-label='Login dengan Google']",
    )
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      authState.isExistingUser = false;
      showStep("authStep4");
    });

  form
    .querySelector(
      "#authStep2 .auth-social-icon[aria-label='Login dengan Apple']",
    )
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      authState.isExistingUser = false;
      showStep("authStep4");
    });

  form
    .querySelector(
      "#authStep2 .auth-social-icon[aria-label='Login dengan Facebook']",
    )
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      authState.isExistingUser = false;
      showStep("authStep4");
    });

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

  form
    .querySelector("#authStep3 .auth-submit-button")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      console.log(
        "[AUTH] Step 3 submit - isExistingUser:",
        authState.isExistingUser,
      );

      const otp =
        form.querySelector("#authStep3 .auth-input.code")?.value.trim() || "";
      if (!otp || otp.length !== 6) {
        alert("Masukkan kode OTP 6 digit");
        return;
      }

      if (authState.isExistingUser) {
        console.log("[AUTH] Existing user → Step 7");
        showStep("authStep7");
      } else {
        console.log("[AUTH] New user → Step 4");
        showStep("authStep4");
      }
    });

  form
    .querySelector("#authStep4 [data-action='prev-step']")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      showStep("authStep2");
    });

  const step4Button = form.querySelector("#authStep4 .auth-submit-button");
  if (step4Button) {
    console.log("[AUTH] ✓ Step 4 button ditemukan");
    step4Button.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      console.log("[AUTH] Step 4 submit clicked");

      const nameInputs = form.querySelectorAll(
        "#authStep4 fieldset .auth-input",
      );
      const firstName = nameInputs[0]?.value.trim();
      const lastName = nameInputs[1]?.value.trim();
      const dob = nameInputs[2]?.value.trim();

      console.log("[AUTH] Step 4 data:", { firstName, lastName, dob });

      if (!firstName || !lastName || !dob) {
        alert("Lengkapi semua data akun");
        return;
      }

      console.log("[AUTH] Step 4 → Step 7");
      showStep("authStep7");
    });
  } else {
    console.error("[AUTH] ✗ Step 4 button TIDAK ditemukan!");
  }

  const step5GoogleBtn = form.querySelector(
    "#authStep5 .auth-submit-button:not(.outline)",
  );
  if (step5GoogleBtn) {
    console.log("[AUTH] ✓ Step 5 Google button ditemukan");
    step5GoogleBtn.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      console.log("[AUTH] Step 5 Google button clicked → Step 4");
      authState.isExistingUser = false;
      showStep("authStep7");
    });
  } else {
    console.error("[AUTH] ✗ Step 5 Google button TIDAK ditemukan!");
  }

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

  form
    .querySelector("#authStep7 [data-action='prev-step']")
    ?.addEventListener("click", (e) => {
      e.preventDefault();
      showStep("authStep4");
    });

  const step7Button = form.querySelector("#authStep7 .auth-submit-button");
  if (step7Button) {
    console.log("[AUTH] ✓ Step 7 button ditemukan");
    step7Button.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      console.log("[AUTH] Step 7 submit clicked → login success");
      window.onLoginSuccess?.("A");
    });
  } else {
    console.error("[AUTH] ✗ Step 7 button TIDAK ditemukan!");
  }

  form.addEventListener("submit", (e) => {
    e.preventDefault();
    console.log("[AUTH] Form submit prevented");
  });

  form.addEventListener("keydown", (e) => {
    if (e.key !== "Enter") return;
    e.preventDefault();

    const activeStep = form.querySelector(".auth-step.active");
    if (!activeStep) return;

    const stepId = activeStep.id;
    console.log("[AUTH] Enter pressed di:", stepId);

    let submitBtn = null;
    if (stepId === "authStep2") {
      submitBtn = form.querySelector("#authStep2 .auth-submit-button");
    } else if (stepId === "authStep3") {
      submitBtn = form.querySelector("#authStep3 .auth-submit-button");
    } else if (stepId === "authStep4") {
      submitBtn = form.querySelector("#authStep4 .auth-submit-button");
    } else if (stepId === "authStep7") {
      submitBtn = form.querySelector("#authStep7 .auth-submit-button");
    }

    if (submitBtn) {
      console.log("[AUTH] Trigger submit button via Enter");
      submitBtn.click();
    }
  });

  console.log("[AUTH] Binding selesai, navigasi ke Step 2");
  showStep("authStep2");
};
