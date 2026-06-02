const cardSections = document.querySelectorAll(".card-section");

cardSections.forEach((section) => {
  const cardList = section.querySelector(".card-list");
  if (!cardList) return;

  const prevButton = section.querySelector(".prev-button");
  const nextButton = section.querySelector(".next-button");

  function updateButtons() {
    const atStart = cardList.scrollLeft <= 0;
    const atEnd =
      cardList.scrollLeft >= cardList.scrollWidth - cardList.clientWidth - 1;

    prevButton?.classList.toggle("disabled", atStart);
    nextButton?.classList.toggle("disabled", atEnd);
  }

  nextButton?.addEventListener("click", () => {
    const hotelCard = section.querySelector(".hotel-card");

    const cardWidth = hotelCard.offsetWidth + 16;
    cardList.scrollBy({ left: cardWidth * 5, behavior: "smooth" });
  });

  prevButton?.addEventListener("click", () => {
    const hotelCard = section.querySelector(".hotel-card");

    const cardWidth = hotelCard.offsetWidth + 16;
    cardList.scrollBy({ left: -(cardWidth * 5), behavior: "smooth" });
  });

  cardList.addEventListener("scroll", updateButtons);
  updateButtons();
});
