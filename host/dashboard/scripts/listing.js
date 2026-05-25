/* ══════════════════════════════════════════════
   listing.js  —  Host Listing Page
   Handles: list/grid toggle, row & card navigation
══════════════════════════════════════════════ */

const listToggleButton = document.querySelector(".list-button");
const icon             = listToggleButton?.querySelector("i");

const listView = document.querySelector(".list-view");
const gridView = document.querySelector(".grid-view");

/* ── Toggle list ↔ grid ── */
listToggleButton?.addEventListener("click", () => {
  const isGrid = listToggleButton.classList.toggle("active");

  listView.style.display = isGrid ? "none" : "";
  gridView.style.display = isGrid ? ""    : "none";

  if (icon) {
    icon.className = isGrid ? "ph-bold ph-squares-four" : "ph-bold ph-rows";
  }
});

/* ── List view: klik baris → navigasi (kecuali kolom aksi) ── */
document.querySelectorAll(".listing-row").forEach((row) => {
  row.style.cursor = "pointer";
  row.addEventListener("click", (e) => {
    if (e.target.closest(".action-cell")) return;
    const href = row.getAttribute("data-href");
    if (href) window.location.href = href;
  });
});

/* ── Grid view: klik card → navigasi (kecuali area .listing-card-actions) ── */
document.querySelectorAll(".listing-card[data-href]").forEach((card) => {
  card.addEventListener("click", (e) => {
    if (e.target.closest(".listing-card-actions")) return;
    const href = card.getAttribute("data-href");
    if (href) window.location.href = href;
  });

  card.addEventListener("keydown", (e) => {
    if (e.key === "Enter" || e.key === " ") {
      e.preventDefault();
      const href = card.getAttribute("data-href");
      if (href) window.location.href = href;
    }
  });
});