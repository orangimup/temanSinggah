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

const priceSlider = document.querySelector(".price-slider");
const priceValue = document.querySelector(".price-display-value");
const priceInput = document.querySelector(".price-input");

function rupiahFormatting(angka) {
  return angka.toLocaleString("id-ID");
}

function clearNonNumeric(teks) {
  return teks.replace(/[^0-9]/g, "");
}

priceSlider.addEventListener("input", function () {
  const currentValue = parseInt(priceSlider.value) || 0;
  const formatResult = rupiahFormatting(currentValue);

  priceValue.textContent = formatResult;
  priceInput.value = formatResult;
});

priceInput.addEventListener("keydown", function (event) {
  const allowedKeys = [
    "Backspace",
    "Tab",
    "ArrowLeft",
    "ArrowRight",
    "Delete",
    "Enter",
  ];

  if (
    !allowedKeys.includes(event.key) &&
    (event.key < "0" || event.key > "9")
  ) {
    event.preventDefault();
  }
});

priceInput.addEventListener("input", function () {
  const cleanedValue = clearNonNumeric(priceInput.value);
  const parsedNumber = parseInt(cleanedValue) || 0;

  priceSlider.value = parsedNumber;
  priceValue.textContent = rupiahFormatting(parsedNumber);

  priceInput.value = rupiahFormatting(parsedNumber);
});