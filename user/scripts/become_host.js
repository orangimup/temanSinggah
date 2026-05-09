// FAQ Question
const faqItem = document.querySelectorAll(".faq-item");

faqItem.forEach((item) => {
  const question = item.querySelector(".faq-question");

  question.addEventListener("click", () => {
    item.classList.toggle("open");

    faqItems.forEach((openItem) => {
      openItem.classList.toggle("open");
    });
  });
});
