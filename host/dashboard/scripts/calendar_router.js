fetch("/popups/month_dropdown.html")
  .then((res) => res.text())
  .then((html) => {
    document.body.insertAdjacentHTML("beforeend", html);

    const monthPopupTrigger = document.querySelector(".header-filter");
    const monthPopup = document.querySelector(".dropdown-popup");

    const monthOptions = document.querySelectorAll(".dropdown-option");

    monthOptions.forEach((option) => {
      option.addEventListener("click", () => {
        const selectedMonthOption = option.textContent;

        const filterValue = monthPopupTrigger.querySelector(".filter-value");

        if (filterValue) {
          filterValue.textContent = selectedMonthOption;
        }

        monthPopup.classList.remove("open");
      });
    });

    if (monthPopupTrigger && monthPopup) {
      monthPopupTrigger.addEventListener("click", (e) => {
        e.stopPropagation();
        monthPopup.classList.toggle("open");

        const rect = monthPopupTrigger.getBoundingClientRect();
        monthPopup.style.top = `${rect.bottom + window.scrollY}px`;
        monthPopup.style.left = `${rect.left + window.scrollX}px`;
      });
    }

    document.addEventListener("click", () => {
      if (monthPopup) monthPopup.classList.remove("open");
    });
  });
