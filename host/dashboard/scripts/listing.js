const listViewButton = document.querySelector(".header-button");
const listContainer = document.querySelector(".list-section");
const icon = listViewButton.querySelector("i");

listViewButton.addEventListener("click", () => {
  const isGrid = listContainer.classList.toggle("grid-view");

  icon.className = isGrid ? "ph-bold ph-squares-four" : "ph-bold ph-rows";
});

const listView = document.querySelector(".list-view")
const gridView = document.querySelector(".grid-view")