// Filter Button
const filterButton = document.querySelectorAll(".filter-item");

filterButton.forEach((filterItem) => {
  filterItem.addEventListener("click", () => {
    filterButton.forEach((i) => i.classList.remove("active"));
    filterItem.classList.add("active");
  });
});

// Sort Button
const sortButtons = document.querySelectorAll(".sort-button");

sortButtons.forEach((sortItem) => {
  sortItem.addEventListener("click", () => {
    const isActive = sortItem.classList.toggle("active");

    const textSpan = sortItem.querySelector("span");

    const textActive = sortItem.dataset.active;
    const textInactive = sortItem.dataset.inactive;

    if (textSpan && textActive && textInactive) {
      textSpan.textContent = isActive ? textActive : textInactive;
    }
  });
});

// Tab Group
const tabItems = document.querySelectorAll(".tab-item");
const tabIndicator = document.querySelector(".tab-indicator");

function moveIndicator(tab) {
  tabIndicator.style.left = `${tab.offsetLeft}px`;
  tabIndicator.style.width = `${tab.offsetWidth}px`;
}

tabItems.forEach((tab) => {
  tab.addEventListener("click", () => {
    tabItems.forEach((t) => t.classList.remove("active"));
    tab.classList.add("active");
    moveIndicator(tab);
  });
});

const activeTab = document.querySelector(".tab-item.active");
if (activeTab) moveIndicator(activeTab);

tabItems.forEach((tab) => {
  tab.addEventListener("click", () => {
    tabItems.forEach((t) => t.classList.remove("active"));
    tab.classList.add("active");
    moveIndicator(tab);

    document
      .querySelectorAll(".table-section")
      .forEach((c) => (c.style.display = "none"));

    document.querySelector(tab.dataset.target).style.display = "block";
  });
});
