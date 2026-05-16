// ============================================================
//  NAV INDICATOR
// ============================================================

const navLinks = document.querySelectorAll(".nav-link");
const navIndicator = document.querySelector(".nav-indicator");

function moveNavIndicator(link) {
  if (!navIndicator) return;
  const containerRect = navIndicator.parentElement.getBoundingClientRect();
  const linkRect = link.getBoundingClientRect();
  navIndicator.style.left = `${linkRect.left - containerRect.left}px`;
  navIndicator.style.width = `${linkRect.width}px`;
}

navLinks.forEach((link) => {
  link.addEventListener("click", () => {
    navLinks.forEach((l) => l.classList.remove("active"));
    link.classList.add("active");
    moveNavIndicator(link);
  });
});

const activeNavLink = document.querySelector(".nav-link.active");
if (activeNavLink) requestAnimationFrame(() => moveNavIndicator(activeNavLink));

window.addEventListener("resize", () => {
  const active = document.querySelector(".nav-link.active");
  if (active) moveNavIndicator(active);
});

// ============================================================
//  HAMBURGER DROPDOWN + LANGUAGE POPUP + AUTH POPUP
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
  const profileBtn = document.querySelector(".icon-button.profile");
  const hamburgerBtn = document.querySelector(".icon-button.hamburger");
  const dropdown = document.getElementById("hamburgerDropdown");

  let isLoggedIn = localStorage.getItem("isLoggedIn") === "true";
  let isLoaded = false;

  // ── Terapkan tampilan navbar sesuai state ──────────────────
  function applyAuthState() {
    if (isLoggedIn) {
      profileBtn?.classList.remove("hidden");
      hamburgerBtn?.classList.add("hidden");
    } else {
      profileBtn?.classList.add("hidden");
      hamburgerBtn?.classList.remove("hidden");
    }
  }

  applyAuthState();

  // ── Login berhasil ─────────────────────────────────────────
  // Dijadikan global agar bisa dipanggil dari auth.js
  window.onLoginSuccess = function (userInitial = "A") {
    isLoggedIn = true;
    localStorage.setItem("isLoggedIn", "true");
    if (profileBtn) profileBtn.textContent = userInitial;
    window.closeAuthPopup();
    applyAuthState();
    isLoaded = false;
    dropdown.innerHTML = "";
    loadDropdown();
  };

  // ── Logout ─────────────────────────────────────────────────
  function onLogout() {
    isLoggedIn = false;
    localStorage.removeItem("isLoggedIn");
    closeDropdown();
    applyAuthState();
    isLoaded = false;
    dropdown.innerHTML = "";
    loadDropdown();
  }

  // ── Muat dropdown ───────────────────────────────────────────
  async function loadDropdown() {
    const inlineTemplate = document.querySelector(
      `#dropdownTemplates [data-type="${isLoggedIn ? "loggedin" : "guest"}"]`,
    );

    if (inlineTemplate) {
      injectDropdown(inlineTemplate.innerHTML);
      return;
    }

    try {
      const res = await fetch("/popups/hamburger.html");
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, "text/html");
      const state = isLoggedIn ? "loggedin" : "guest";
      const template = doc.querySelector(`[data-type="${state}"]`);
      if (!template) {
        console.error(
          `[navbar] Template [data-type="${state}"] tidak ditemukan`,
        );
        return;
      }
      injectDropdown(template.innerHTML);
    } catch (err) {
      console.error("[navbar] Gagal memuat dropdown:", err);
    }
  }

  function injectDropdown(html) {
    dropdown.innerHTML = html;
    isLoaded = true;
    bindLanguageOption();
    bindAuthOption();
    bindLogoutOption();
  }

  // ── Muat language popup ─────────────────────────────────────
  async function loadLanguagePopup() {
    if (document.getElementById("languageOverlay")) {
      bindLanguagePopupEvents();
      return;
    }

    try {
      const res = await fetch("/popups/language.html");
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, "text/html");
      const overlay = doc.querySelector("#languageOverlay");

      if (overlay) {
        const container = document.getElementById("languagePopup");
        if (container) {
          container.appendChild(overlay);
          bindLanguagePopupEvents();
        } else {
          console.error("[navbar] #languagePopup container tidak ditemukan");
        }
      } else {
        console.error(
          "[navbar] #languageOverlay tidak ditemukan di language.html",
        );
      }
    } catch (err) {
      console.error("[navbar] Gagal memuat language popup:", err);
    }
  }

  // ── Muat auth popup ─────────────────────────────────────────
  async function loadAuthPopup() {
    if (document.getElementById("authOverlay")) {
      // Panggil fungsi global dari auth.js
      window.bindAuthPopupEvents?.();
      return;
    }

    try {
      const res = await fetch("/popups/auth.html");
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, "text/html");
      const overlay = doc.querySelector("#authOverlay");

      if (overlay) {
        const container = document.getElementById("authPopup");
        if (container) {
          container.appendChild(overlay);
          // Panggil fungsi global dari auth.js
          window.bindAuthPopupEvents?.();
        } else {
          console.error("[navbar] #authPopup container tidak ditemukan");
        }
      } else {
        console.error("[navbar] #authOverlay tidak ditemukan di auth.html");
      }
    } catch (err) {
      console.error("[navbar] Gagal memuat auth popup:", err);
    }
  }

  // ── Buka / tutup dropdown ───────────────────────────────────
  function openDropdown(triggerBtn) {
    const rect = triggerBtn.getBoundingClientRect();
    dropdown.style.top = `${rect.bottom + 8}px`;
    dropdown.style.right = `${window.innerWidth - rect.right}px`;
    dropdown.style.left = "auto";
    dropdown.classList.add("open");
  }

  function closeDropdown() {
    dropdown.classList.remove("open");
  }

  function toggleDropdown(triggerBtn) {
    if (!isLoaded) return;
    dropdown.classList.contains("open")
      ? closeDropdown()
      : openDropdown(triggerBtn);
  }

  profileBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    toggleDropdown(profileBtn);
  });
  hamburgerBtn?.addEventListener("click", (e) => {
    e.stopPropagation();
    toggleDropdown(hamburgerBtn);
  });

  document.addEventListener("click", closeDropdown);
  window.addEventListener("resize", closeDropdown);
  dropdown.addEventListener("click", (e) => e.stopPropagation());

  // ── Bind opsi dropdown ─────────────────────────────────────
  function bindLanguageOption() {
    dropdown.querySelectorAll("[data-action='language']").forEach((el) => {
      el.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        openLanguagePopup();
      });
    });
  }

  function bindAuthOption() {
    dropdown.querySelectorAll("[data-action='auth']").forEach((el) => {
      el.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        window.openAuthPopup();
      });
    });
  }

  function bindLogoutOption() {
    dropdown.querySelectorAll("[data-action='logout']").forEach((el) => {
      el.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        onLogout();
      });
    });
  }

  // ── Language popup events ───────────────────────────────────
  function bindLanguagePopupEvents() {
    const langOverlay = document.getElementById("languageOverlay");
    if (!langOverlay) return;

    langOverlay
      .querySelector(".close-button")
      ?.addEventListener("click", closeLanguagePopup);

    langOverlay.addEventListener("click", (e) => {
      if (e.target === langOverlay) closeLanguagePopup();
    });

    const tabItems = langOverlay.querySelectorAll(".tab-item");
    const tabIndicator = langOverlay.querySelector(".tab-indicator");
    const tabContents = langOverlay.querySelectorAll(".tab-content");

    function moveTabIndicator(tab) {
      if (!tabIndicator) return;
      tabIndicator.style.left = `${tab.offsetLeft}px`;
      tabIndicator.style.width = `${tab.offsetWidth}px`;
    }

    tabItems.forEach((tab) => {
      tab.addEventListener("click", () => {
        tabItems.forEach((t) => t.classList.remove("active"));
        tabContents.forEach((c) => c.classList.remove("active"));
        tab.classList.add("active");
        moveTabIndicator(tab);
        const target = langOverlay.querySelector(tab.dataset.target);
        if (target) target.classList.add("active");
      });
    });

    langOverlay._moveTabIndicator = moveTabIndicator;
  }

  // ── Buka / tutup language popup ────────────────────────────
  function openLanguagePopup() {
    closeDropdown();
    const langOverlay = document.getElementById("languageOverlay");
    if (!langOverlay) return;
    langOverlay.classList.add("open");
    document.body.style.overflow = "hidden";
    requestAnimationFrame(() => {
      const activeTab = langOverlay.querySelector(".tab-item.active");
      if (activeTab && langOverlay._moveTabIndicator) {
        langOverlay._moveTabIndicator(activeTab);
      }
    });
  }

  function closeLanguagePopup() {
    const langOverlay = document.getElementById("languageOverlay");
    if (!langOverlay) return;
    langOverlay.classList.remove("open");
    document.body.style.overflow = "";
  }

  // ── Buka / tutup auth popup — dijadikan global ─────────────
  // agar bisa dipanggil dari auth.js
  window.openAuthPopup = function () {
    closeDropdown();
    const authOverlay = document.getElementById("authOverlay");
    if (!authOverlay) return;
    // Reset ke step awal setiap kali popup dibuka
    authOverlay
      .querySelectorAll(".auth-step")
      .forEach((el) => el.classList.remove("active"));
    const defaultStep = authOverlay.querySelector("#authStep1");
    if (defaultStep) defaultStep.classList.add("active");
    authOverlay.classList.add("open");
    document.body.style.overflow = "hidden";
  };

  window.closeAuthPopup = function () {
    const authOverlay = document.getElementById("authOverlay");
    if (!authOverlay) return;
    authOverlay.classList.remove("open");
    document.body.style.overflow = "";
  };

  // Escape menutup semua popup
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeLanguagePopup();
      window.closeAuthPopup();
    }
  });

  // ── Mulai ───────────────────────────────────────────────────
  loadDropdown();
  loadLanguagePopup();
  loadAuthPopup();
});
