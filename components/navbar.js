// ============================================================
//  NAV INDICATOR
// ============================================================

const navLinks = document.querySelectorAll(".nav-link");
const navIndicator = document.querySelector(".nav-indicator");

function moveNavIndicator(link) {
  if (!navIndicator) return;

  const navContainer = navIndicator.parentElement;
  const containerRect = navContainer.getBoundingClientRect();
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
//  HAMBURGER DROPDOWN + LANGUAGE POPUP
// ============================================================

document.addEventListener("DOMContentLoaded", () => {
  const profileBtn = document.querySelector(".icon-button.profile");
  const hamburgerBtn = document.querySelector(".icon-button.hamburger");
  const dropdown = document.getElementById("hamburgerDropdown");

  const isLoggedIn = true;
  let isLoaded = false;

  // Tampilkan tombol sesuai status login
  if (isLoggedIn) {
    profileBtn?.classList.remove("hidden");
    hamburgerBtn?.classList.add("hidden");
  } else {
    profileBtn?.classList.add("hidden");
    hamburgerBtn?.classList.remove("hidden");
  }

  // ── Muat hamburger dropdown ─────────────────────────────────
  async function loadDropdown() {
    try {
      const res = await fetch("/popups/hamburger.html");
      const html = await res.text();

      const parser = new DOMParser();
      const doc = parser.parseFromString(html, "text/html");
      const state = isLoggedIn ? "loggedin" : "guest";
      const section = doc.querySelector(`[data-type="${state}"]`);

      if (section) {
        dropdown.innerHTML = section.innerHTML;
        isLoaded = true;
        bindLanguageOption();
      }
    } catch (err) {
      console.error("Gagal memuat dropdown:", err);
    }
  }

  // ── Muat language popup dari /popups/language.html ──────────
  async function loadLanguagePopup() {
    try {
      const res = await fetch("/popups/language.html");
      const html = await res.text();

      const parser = new DOMParser();
      const doc = parser.parseFromString(html, "text/html");

      // Ambil #languageOverlay dari file language.html
      const overlay = doc.querySelector("#languageOverlay");

      if (overlay) {
        // Inject ke #languagePopup placeholder di index.html
        const container = document.getElementById("languagePopup");
        if (container) {
          container.appendChild(overlay);
          bindLanguagePopupEvents();
        }
      }
    } catch (err) {
      console.error("Gagal memuat language popup:", err);
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

  // ── Bind opsi "Bahasa dan Mata Uang" di dropdown ───────────
  function bindLanguageOption() {
    const langOption = dropdown.querySelector("[data-action='language']");
    langOption?.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      openLanguagePopup();
    });
  }

  // ── Language popup events (dipanggil setelah popup di-inject)
  function bindLanguagePopupEvents() {
    const langOverlay = document.getElementById("languageOverlay");
    if (!langOverlay) return;

    // Tombol close
    const closeBtn = langOverlay.querySelector(".close-button");
    closeBtn?.addEventListener("click", closeLanguagePopup);

    // Klik backdrop menutup popup
    langOverlay.addEventListener("click", (e) => {
      if (e.target === langOverlay) closeLanguagePopup();
    });

    // Tab indicator
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

    // Simpan moveTabIndicator agar bisa dipakai openLanguagePopup
    langOverlay._moveTabIndicator = moveTabIndicator;
  }

  // ── Buka / tutup language popup ────────────────────────────
  function openLanguagePopup() {
    closeDropdown();

    const langOverlay = document.getElementById("languageOverlay");
    if (!langOverlay) return;

    langOverlay.classList.add("open");
    document.body.style.overflow = "hidden";

    // Set tab indicator setelah popup tampil
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

  // Escape key menutup popup
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeLanguagePopup();
  });

  // ── Mulai muat keduanya ─────────────────────────────────────
  loadDropdown();
  loadLanguagePopup();
});
