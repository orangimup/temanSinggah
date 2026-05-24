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

document.addEventListener("DOMContentLoaded", () => {
  const profileBtn = document.querySelector(".icon-button.profile");
  const hamburgerBtn = document.querySelector(".icon-button.hamburger");
  const dropdown = document.getElementById("hamburgerDropdown");

  let isLoggedIn = localStorage.getItem("isLoggedIn") === "true";
  let isLoaded = false;
  let intentToBeHost = false;

  function getBasePath() {
    const path = window.location.pathname;
    if (path.includes("/host/onboarding/pages/")) return "../../../";
    if (path.includes("/host/dashboard/pages/")) return "../../../";
    if (path.includes("/host/")) return "../../";
    if (path.includes("/user/pages/")) return "../../";
    if (path.includes("/admin/pages/")) return "../../";
    if (path.includes("/popups/screen/")) return "../../";
    return "";
  }
  const BASE = getBasePath();

  function isHostPage() {
    return window.location.pathname.includes("/host/");
  }

  function getUserType() {
    if (!isLoggedIn) return "guest";
    return isHostPage() ? "host" : "loggedin";
  }

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

  // PERBAIKAN: navbar.js hanya update foto jika PHP belum render foto di HTML
  // (jika PHP sudah render <img> di dalam profileBtn, jangan overwrite)
  const profileBtnHasImg = profileBtn?.querySelector("img");
  if (!profileBtnHasImg && profileBtn) {
    const userPhoto = localStorage.getItem('userPhoto') || '';
    const userInitial = localStorage.getItem('userInitial') || '';
    if (userPhoto) {
      profileBtn.style.padding = '0';
      profileBtn.style.overflow = 'hidden';
      profileBtn.innerHTML = `<img src="${userPhoto}" alt="Foto" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />`;
    } else if (userInitial) {
      profileBtn.textContent = userInitial;
    }
  }

  // PERBAIKAN: onLoginSuccess juga terima photo
  window.onLoginSuccess = function (userInitialVal = "A", userPhotoVal = "") {
    isLoggedIn = true;
    localStorage.setItem("isLoggedIn", "true");
    localStorage.setItem("userInitial", userInitialVal);
    if (userPhotoVal) {
      localStorage.setItem("userPhoto", userPhotoVal);
    }
    if (profileBtn) {
      if (userPhotoVal) {
        profileBtn.style.padding = '0';
        profileBtn.style.overflow = 'hidden';
        profileBtn.innerHTML = `<img src="${userPhotoVal}" alt="Foto" style="width:100%;height:100%;object-fit:cover;border-radius:50%;" />`;
      } else {
        profileBtn.textContent = userInitialVal;
      }
    }
    window.closeAuthPopup();
    applyAuthState();
    isLoaded = false;
    dropdown.innerHTML = "";
    loadDropdown();

    if (intentToBeHost) {
      intentToBeHost = false;
      setTimeout(() => {
        window.location.href = `${BASE}host/onboarding/pages/about_place.html`;
      }, 500);
    }
  };

  function onLogout() {
    localStorage.removeItem("isLoggedIn");
    localStorage.removeItem("userInitial");
    localStorage.removeItem("userName");
    localStorage.removeItem("userPhoto");
    window.location.href = "/teman_singgah/auth/proses_logout.php";
  }

  async function loadDropdown() {
    const userType = getUserType();

    const inlineTemplate = document.querySelector(
      `#dropdownTemplates [data-type="${userType}"]`
    );
    if (inlineTemplate) {
      injectDropdown(inlineTemplate.innerHTML);
      return;
    }

    try {
      const res = await fetch(`${BASE}popups/screen/hamburger.html`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, "text/html");
      const template = doc.querySelector(`[data-type="${userType}"]`);
      if (!template) {
        console.warn(`[navbar] Template [data-type="${userType}"] tidak ditemukan`);
        return;
      }
      injectDropdown(template.innerHTML);
    } catch (err) {
      console.error("[navbar] Error loading dropdown:", err);
    }
  }

  function injectDropdown(html) {
    dropdown.innerHTML = html;
    isLoaded = true;
    bindLanguageOption();
    bindAuthOption();
    bindLogoutOption();
  }

  async function loadLanguagePopup() {
    if (document.getElementById("languageOverlay")) {
      bindLanguagePopupEvents();
      return;
    }

    try {
      const res = await fetch(`${BASE}popups/screen/language.html`);
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
          console.warn("[navbar] #languagePopup container tidak ditemukan");
        }
      }
    } catch (err) {
      console.error("[navbar] Error loading language popup:", err);
    }
  }

  async function loadAuthPopup() {
    if (document.getElementById("authOverlay")) {
      window.bindAuthPopupEvents?.();
      return;
    }

    try {
      const res = await fetch(`${BASE}popups/screen/auth.html`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, "text/html");
      const overlay = doc.querySelector("#authOverlay");

      if (overlay) {
        const container = document.getElementById("authPopup");
        if (container) {
          container.appendChild(overlay);
          window.bindAuthPopupEvents?.();
        }
      }
    } catch (err) {
      console.error("[navbar] Error loading auth popup:", err);
    }
  }

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
        intentToBeHost = el.textContent.includes("Host");
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

  function bindLanguagePopupEvents() {
    const langOverlay = document.getElementById("languageOverlay");
    if (!langOverlay) return;

    langOverlay.querySelector(".close-button")?.addEventListener("click", closeLanguagePopup);
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

  window.openAuthPopup = function () {
    closeDropdown();
    const authOverlay = document.getElementById("authOverlay");
    if (!authOverlay) return;
    authOverlay.querySelectorAll(".auth-step").forEach((el) => el.classList.remove("active"));
    const defaultStep = authOverlay.querySelector("#authStepPilih");
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

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeLanguagePopup();
      window.closeAuthPopup();
    }
  });

  loadDropdown();
  loadLanguagePopup();
  loadAuthPopup();
});