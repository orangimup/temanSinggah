const filterButton = document.querySelectorAll(".filter-item");

filterButton.forEach((item) => {
  item.addEventListener("click", () => {
    filterButton.forEach((i) => i.classList.remove("active"));
    item.classList.add("active");
  });
});
