// Filter Button
const filterButton = document.querySelectorAll(".filter-item");

filterButton.forEach((filterItem) => {
  filterItem.addEventListener("click", () => {
    filterButton.forEach((i) => i.classList.remove("active"));
    filterItem.classList.add("active");
  });
});

// Sort Button
const sortButton = document.querySelectorAll(".sort-button");

sortButton.forEach((sortItem) => {
  sortItem.addEventListener("click", () => {
    const isActive = sortItem.classList.toggle("active");

    const textSpan = sortItem.querySelector("span");
    const iconI = sortItem.querySelector(".caret-icon");

    textSpan.textContent = isActive
      ? "Urutkan: Diskon Terkecil"
      : "Urutkan: Diskon Terbesar";

    iconI.className = isActive
      ? "caret-icon ph-bold ph-caret-up"
      : "caret-icon ph-bold ph-caret-down";
  });
});

// Save Button
const saveButton = document.querySelectorAll(".save-button");

saveButton.forEach((saveItem) => {
  saveItem.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    const isActive = saveItem.classList.toggle("active");

    saveItem.src = isActive
      ? "/assets/icons/save_fill.svg"
      : "/assets/icons/save.svg";
  });
});
