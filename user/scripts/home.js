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

// Slide Button
const cardSections = document.querySelectorAll(".card-section");

cardSections.forEach((section) => {
  const cardList = section.querySelector(".card-list");

  section.querySelector(".next-button")?.addEventListener("click", () => {
    const hotelCard = section.querySelector(".hotel-card");
    if (hotelCard) {
      const cardWidth = hotelCard.offsetWidth + 16;
      cardList.scrollBy({ left: cardWidth * 5, behavior: "smooth" });
    }
  });

  section.querySelector(".prev-button")?.addEventListener("click", () => {
    const hotelCard = section.querySelector(".hotel-card");
    if (hotelCard) {
      const cardWidth = hotelCard.offsetWidth + 16;
      cardList.scrollBy({ left: -(cardWidth * 5), behavior: "smooth" });
    }
  });
});
