// ============================================================
// FILTER — Reservasi Host
// ============================================================

const filterButtons = document.querySelectorAll(".filter-item");
const reservationCards = document.querySelectorAll(".reservation-card");
const emptyState = document.getElementById("emptyState");

filterButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    filterButtons.forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");

    const filter = btn.dataset.filter;
    let visibleCount = 0;

    reservationCards.forEach((card) => {
      const match = filter === "all" || card.dataset.status === filter;
      if (match) {
        card.classList.remove("hidden");
        visibleCount++;
      } else {
        card.classList.add("hidden");
      }
    });

    if (emptyState) {
      emptyState.style.display = visibleCount === 0 ? "flex" : "none";
    }
  });
});
