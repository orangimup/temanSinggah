const listToggleButton = document.querySelector(".header-button");
const listContainer = document.querySelector(".list-section");
const icon = listToggleButton.querySelector("i");

const listView = document.querySelector(".list-view");
const gridView = document.querySelector(".grid-view");

listToggleButton.addEventListener("click", () => {
  const isGrid = listToggleButton.classList.toggle("active");

  listView.style.display = isGrid ? "none" : "flex";
  gridView.style.display = isGrid ? "grid" : "none";

  icon.className = isGrid ? "ph-bold ph-squares-four" : "ph-bold ph-rows";
});

document.querySelectorAll(".listing-row").forEach((row) => {
  row.style.cursor = "pointer";
  row.addEventListener("click", () => {
    const href = row.getAttribute("data-href");
    if (href) {
      window.location.href = href;
    }
  });
});
