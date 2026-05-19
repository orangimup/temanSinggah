const state = {
  month: new Date().getMonth(),
  year: new Date().getFullYear(),
  mode: "month",
};

const monthName = [
  "Januari",
  "Februari",
  "Maret",
  "April",
  "Mei",
  "Juni",
  "Juli",
  "Agustus",
  "September",
  "Oktober",
  "November",
  "Desember",
];

const priceData = {
  "2026-05-03": { status: "booked", price: "Dipesan" },
  "2026-05-10": { status: "blocked", price: "Diblokir" },
  "2026-05-15": { status: "booked", price: "Dipesan" },
};

function getDaysInMonth(month, year) {
  return new Date(year, month + 1, 0).getDate();
}

function getFirstDayOfMonth(month, year) {
  return new Date(year, month, 1).getDay();
}

function getStatusCard(date, month, year) {
  const key = `${year}-${String(month + 1).padStart(2, "0")}-${String(date).padStart(2, "0")}`;
  const today = new Date();
  const isToday =
    date === today.getDate() &&
    month === today.getMonth() &&
    year === today.getFullYear();

  const status = priceData[key]?.status || "available";
  const price = priceData[key]?.price || "Rp399rb";

  if (isToday) return { class: "day-today", label: "Hari ini" };
  if (status === "booked") return { class: "day-booked", label: "Dipesan" };
  if (status === "blocked") return { class: "day-blocked", label: "Diblokir" };
  return { class: "", label: price };
}

function renderMonthSection(month, year) {
  const totalDay = getDaysInMonth(month, year);
  const firstDay = getFirstDayOfMonth(month, year);

  const sectionClone = document
    .querySelector("#monthSectionTemplate")
    .content.cloneNode(true);
  sectionClone.querySelector(".month-label").textContent =
    `${monthName[month]} ${year}`;
  const grid = sectionClone.querySelector(".day-grid");

  for (let i = 0; i < firstDay; i++) {
    const empty = document
      .querySelector("#dayCardEmptyTemplate")
      .content.cloneNode(true);
    grid.appendChild(empty);
  }

  for (let day = 1; day <= totalDay; day++) {
    const { class: cls, label } = getStatusCard(day, month, year);

    let card;
    if (cls) {
      card = document
        .querySelector("#dayCardStatusTemplate")
        .content.cloneNode(true);
      card.querySelector(".day-card").classList.add(cls);
    } else {
      card = document.querySelector("#dayCardTemplate").content.cloneNode(true);
    }

    card.querySelector(".day-number").textContent = day;
    card.querySelector(".day-price").textContent = label;
    grid.appendChild(card);
  }

  return sectionClone;
}

function renderCalendar() {
  const container = document.querySelector(".calendar-container");
  container.innerHTML = "";

  const header = document
    .querySelector("#weekdayHeaderTemplate")
    .content.cloneNode(true);
  container.appendChild(header);

  if (state.mode === "month") {
    container.appendChild(renderMonthSection(state.month, state.year));
  } else {
    for (let b = 0; b < 12; b++) {
      container.appendChild(renderMonthSection(b, state.year));
    }
  }

  container.querySelectorAll(".day-card:not(.day-empty)").forEach((card) => {
    card.addEventListener("click", () => {
      document.querySelector("#listingSidebar").classList.add("open");
    });
  });

  document.querySelector(".content-title h2").textContent =
    state.mode === "month" ? monthName[state.month] : String(state.year);
}

Promise.all([
  fetch("/popups/screen/calendar_title.html").then((res) => res.text()),
  fetch("/popups/screen/calendar_filter.html").then((res) => res.text()),
]).then(([monthHtml, filterHtml]) => {
  document.body.insertAdjacentHTML("beforeend", monthHtml);
  document.body.insertAdjacentHTML("beforeend", filterHtml);

  const titlePopup = document.querySelector("#titlePopup");
  const filterPopup = document.querySelector("#filterPopup");

  document.querySelector(".content-title").addEventListener("click", (e) => {
    e.stopPropagation();
    filterPopup.classList.remove("open");

    if (state.mode === "month") {
      titlePopup.innerHTML = monthName
        .map(
          (b, i) =>
            `<button class="dropdown-option" data-index="${i}">${b}</button>`,
        )
        .join("");
    } else {
      const currentYear = new Date().getFullYear();
      const years = Array.from({ length: 5 }, (_, i) => currentYear + i);
      titlePopup.innerHTML = years
        .map(
          (y) =>
            `<button class="dropdown-option" data-year="${y}">${y}</button>`,
        )
        .join("");
    }

    titlePopup.querySelectorAll(".dropdown-option").forEach((option) => {
      option.onclick = (e) => {
        e.stopPropagation();
        if (state.mode === "month") {
          state.month = parseInt(option.dataset.index);
        } else {
          state.year = parseInt(option.dataset.year);
        }
        renderCalendar();
        titlePopup.classList.remove("open");
      };
    });

    const rect = e.currentTarget.getBoundingClientRect();
    titlePopup.style.top = `${rect.bottom + window.scrollY + 8}px`;
    titlePopup.style.left = `${rect.left + window.scrollX}px`;
    titlePopup.classList.toggle("open");
  });

  document.querySelector(".header-filter").addEventListener("click", (e) => {
    e.stopPropagation();
    titlePopup.classList.remove("open");

    filterPopup.querySelectorAll(".dropdown-option").forEach((option) => {
      option.onclick = (e) => {
        e.stopPropagation();
        const isYear = option.textContent === "Tahun";
        state.mode = isYear ? "year" : "month";
        document.querySelector(".filter-value").textContent =
          option.textContent;
        document
          .querySelector(".calendar-container")
          .classList.toggle("year-view", isYear);
        renderCalendar();
        filterPopup.classList.remove("open");
      };
    });

    const rect = e.currentTarget.getBoundingClientRect();
    filterPopup.style.top = `${rect.bottom + window.scrollY + 8}px`;
    filterPopup.style.left = `${rect.left + window.scrollX}px`;
    filterPopup.classList.toggle("open");
  });

  document.addEventListener("click", () => {
    titlePopup.classList.remove("open");
    filterPopup.classList.remove("open");
  });
});

document
  .querySelector(".listing-sidebar-close")
  .addEventListener("click", () => {
    document.querySelector("#listingSidebar").classList.remove("open");
  });

document.querySelectorAll(".listing-sidebar-item").forEach((item) => {
  item.addEventListener("click", () => {
    document
      .querySelectorAll(".listing-sidebar-item")
      .forEach((i) => i.classList.remove("active"));
    item.classList.add("active");
  });
});

renderCalendar();

document.querySelectorAll(".panel-group").forEach((group) => {
  const btn = group.querySelector(".panel-card.button");
  const popup = group.querySelector(".panel-popup");

  btn.addEventListener("click", () => {
    document
      .querySelectorAll(".panel-popup")
      .forEach((p) => p.classList.remove("open"));
    popup.classList.add("open");
  });

  popup.querySelector(".popup-header i")?.addEventListener("click", () => {
    popup.classList.remove("open");
  });
});

function displayValue(btn) {
  const prefix = btn.dataset.prefix || "";
  const suffix = btn.dataset.suffix || "";
  const valueEl = btn.querySelector(".panel-value");
  const input = btn.querySelector(".panel-input");

  if (!input || !valueEl) return;

  const raw = input.value;

  if (prefix === "Rp") {
    const num = parseFloat(raw);
    valueEl.textContent = isNaN(num) ? raw : "Rp" + num.toLocaleString("id-ID");
  } else if (suffix) {
    valueEl.textContent = raw + suffix;
  } else {
    valueEl.textContent = raw;
  }
}

function openEdit(btn, saveBtn) {
  document
    .querySelectorAll(".panel-button.editable.editing")
    .forEach((other) => {
      if (other !== btn) closeEdit(other);
    });

  btn.classList.add("editing");
  saveBtn.classList.add("visible");

  const input = btn.querySelector(".panel-input");
  input?.focus();
  input?.select();
}

function closeEdit(btn) {
  btn.classList.remove("editing");
  const saveBtn = btn.nextElementSibling;
  if (saveBtn?.classList.contains("panel-save-button")) {
    saveBtn.classList.remove("visible");
  }
}

function saveEdit(btn) {
  displayValue(btn);
  closeEdit(btn);
}

document.querySelectorAll(".panel-button.editable").forEach((btn) => {
  const input = btn.querySelector(".panel-input");

  const saveBtn = document.createElement("button");
  saveBtn.className = "panel-save-button";
  saveBtn.textContent = "Simpan";
  btn.after(saveBtn);

  btn.addEventListener("click", (e) => {
    if (e.target === input) return; 
    openEdit(btn, saveBtn);
  });

  input?.addEventListener("click", (e) => e.stopPropagation());

  input?.addEventListener("keydown", (e) => {
    if (e.key === "Enter") {
      e.preventDefault();
      saveEdit(btn);
    }
    if (e.key === "Escape") {
      closeEdit(btn);
    }
  });

  saveBtn.addEventListener("click", () => {
    saveEdit(btn);
  });
});
