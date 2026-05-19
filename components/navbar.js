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

  window.onLoginSuccess = function (userInitial = "A") {
    isLoggedIn = true;
    localStorage.setItem("isLoggedIn", "true");
    if (profileBtn) profileBtn.textContent = userInitial;
    window.closeAuthPopup();
    applyAuthState();
    isLoaded = false;
    dropdown.innerHTML = "";
    loadDropdown();

    if (intentToBeHost) {
      intentToBeHost = false;
      setTimeout(() => {
        window.location.href = "/host/onboarding/pages/about_place.html";
      }, 500);
    }
  };

  function onLogout() {
    isLoggedIn = false;
    localStorage.removeItem("isLoggedIn");
    closeDropdown();
    applyAuthState();
    isLoaded = false;
    dropdown.innerHTML = "";
    loadDropdown();

    if (isHostPage()) {
      setTimeout(() => {
        window.location.href = "/";
      }, 300);
    }
  }

  async function loadDropdown() {
    const userType = getUserType();
    const inlineTemplate = document.querySelector(
      `#dropdownTemplates [data-type="${userType}"]`,
    );

    if (inlineTemplate) {
      injectDropdown(inlineTemplate.innerHTML);
      return;
    }

    try {
      const res = await fetch("/popups/screen/hamburger.html");
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, "text/html");
      const template = doc.querySelector(`[data-type="${userType}"]`);
      if (!template) {
        console.warn(
          `[navbar] Template [data-type="${userType}"] tidak ditemukan`,
        );
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
      const res = await fetch("/popups/screen/language.html");
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
      } else {
        console.warn(
          "[navbar] #languageOverlay tidak ditemukan di language.html",
        );
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
      const res = await fetch("/popups/screen/auth.html");
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const html = await res.text();
      const doc = new DOMParser().parseFromString(html, "text/html");
      const overlay = doc.querySelector("#authOverlay");

      if (overlay) {
        const container = document.getElementById("authPopup");
        if (container) {
          container.appendChild(overlay);
          window.bindAuthPopupEvents?.();
        } else {
          console.warn("[navbar] #authPopup container tidak ditemukan");
        }
      } else {
        console.warn("[navbar] #authOverlay tidak ditemukan di auth.html");
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

        if (el.textContent.includes("Host")) {
          intentToBeHost = true;
        } else {
          intentToBeHost = false;
        }
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
