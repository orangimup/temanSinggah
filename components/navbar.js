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

document.addEventListener("DOMContentLoaded", () => {
  const profileBtn = document.querySelector(".icon-button.profile");
  const hamburgerBtn = document.querySelector(".icon-button.hamburger");
  const dropdown = document.getElementById("hamburgerDropdown");

  const isLoggedIn = true;
  let isLoaded = false; // Flag untuk mengecek apakah konten sudah siap

  // 1. Inisialisasi Tombol
  if (isLoggedIn) {
    profileBtn?.classList.remove("hidden");
    hamburgerBtn?.classList.add("hidden");
  } else {
    profileBtn?.classList.add("hidden");
    hamburgerBtn?.classList.remove("hidden");
  }

  // 2. Load isi dropdown (Hanya ambil yang dibutuhkan saja)
  async function loadDropdown() {
    try {
      const res = await fetch("/popups/hamburger.html");
      const html = await res.text();

      const parser = new DOMParser();
      const doc = parser.parseFromString(html, "text/html");

      const state = isLoggedIn ? "loggedin" : "guest";
      const section = doc.querySelector(`[data-type="${state}"]`);

      if (section) {
        dropdown.innerHTML = section.innerHTML; // Hanya ambil isi yang relevan
        isLoaded = true;
      }
    } catch (err) {
      console.error("Gagal memuat dropdown:", err);
    }
  }

  // 3. Buka / tutup dropdown
  function toggleDropdown(triggerBtn) {
    if (!isLoaded) return; // Jangan buka jika konten belum siap

    if (dropdown.classList.contains("open")) {
      dropdown.classList.remove("open");
    } else {
      const rect = triggerBtn.getBoundingClientRect();

      // Gunakan fixed agar aman saat scroll (sesuaikan CSS kamu)
      dropdown.style.top = `${rect.bottom + 8}px`;
      dropdown.style.right = `${window.innerWidth - rect.right}px`;
      dropdown.style.left = "auto";

      dropdown.classList.add("open");
    }
  }

  // Event Listeners
  const handleTrigger = (e, btn) => {
    e.stopPropagation();
    toggleDropdown(btn);
  };

  profileBtn?.addEventListener("click", (e) => handleTrigger(e, profileBtn));
  hamburgerBtn?.addEventListener("click", (e) =>
    handleTrigger(e, hamburgerBtn),
  );

  // Global Close
  const closeAll = () => dropdown.classList.remove("open");

  document.addEventListener("click", closeAll);
  window.addEventListener("resize", closeAll);
  window.addEventListener("scroll", closeAll); // Tambahan: tutup saat scroll

  dropdown.addEventListener("click", (e) => e.stopPropagation());

  loadDropdown();
});
