// Sort Button
const sortButton = document.querySelectorAll(".sort-button");

sortButton.forEach((sortItem) => {
  sortItem.addEventListener("click", () => {
    const isActive = sortItem.classList.toggle("active");

    const iconI = sortItem.querySelector(".caret-icon");

    iconI.className = isActive
      ? "caret-icon ph-bold ph-caret-up"
      : "caret-icon ph-bold ph-caret-down";
  });
});

// Filter Button
const filterButton = document.querySelectorAll(".filter-item");

filterButton.forEach((filterItem) => {
  filterItem.addEventListener("click", () => {
    const isActive = filterItem.classList.contains("active");
    filterButton.forEach((filter) => filter.classList.remove("active"));

    if (!isActive) {
      filterItem.classList.add("active");
    }
  });
});

// Thread Item
const threadContact = document.querySelectorAll(".thread-item");

threadContact.forEach((threadItem) => {
  threadItem.addEventListener("click", () => {
    threadContact.forEach((thread) => thread.classList.remove("active"));
    threadItem.classList.add("active");
  });
});