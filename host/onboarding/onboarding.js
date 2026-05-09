const counterItem = document.querySelectorAll(".counter-row");

counterItem.forEach((item) => {
  const minusButton = item.querySelector(".minus");
  const plusButton = item.querySelector(".plus");
  const counterValue = item.querySelector(".counter-value");

  const min = parseInt(item.dataset.min) || 0;
  const max = parseInt(item.dataset.max) || 10;
  let value = parseInt(counterValue.textContent);

  function updateCounter() {
    counterValue.textContent = value;
    minusButton.classList.toggle("disabled", value <= min);
    plusButton.classList.toggle("disabled", value >= max);
  }

  plusButton.addEventListener("click", () => {
    if (value < max) {
      value++;
      updateCounter();
    }
  });

  minusButton.addEventListener("click", () => {
    if (value > min) {
      value--;
      updateCounter();
    }
  });

  updateCounter();
});
