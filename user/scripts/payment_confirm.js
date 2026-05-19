let activeSection = "pay";
let selectedPay = "now";
let selectedMethod = "gopay";

document.addEventListener("DOMContentLoaded", () => {
  setupStepListeners();
  setupSummaryDropdowns();
  renderAll();
});

function setupStepListeners() {
  document.querySelectorAll("[data-toggle-section]").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      toggleSection(btn.getAttribute("data-toggle-section"));
    });
  });

  document.querySelectorAll("[data-select-pay]").forEach((row) => {
    row.addEventListener("click", () =>
      selectPay(row.getAttribute("data-select-pay")),
    );
  });

  document.querySelectorAll("[data-select-method]").forEach((row) => {
    row.addEventListener("click", () =>
      selectMethod(row.getAttribute("data-select-method")),
    );
  });

  document
    .querySelector(".back-button")
    ?.addEventListener("click", () => window.history.back());
}

function toggleSection(name) {
  activeSection = activeSection === name ? null : name;
  renderAll();
}
function selectPay(val) {
  selectedPay = val;
  renderAll();
}
function selectMethod(val) {
  selectedMethod = val;
  renderAll();
}

function renderAll() {
  document
    .getElementById("payExpand")
    ?.classList.toggle("active", activeSection === "pay");
  document
    .getElementById("methodExpand")
    ?.classList.toggle("active", activeSection === "method");
  document
    .getElementById("review-content")
    ?.classList.toggle("active", !activeSection);

  setRadio("nowRadio", selectedPay === "now");
  setRadio("radio-later", selectedPay === "later");
  setRadio("radioCard", selectedMethod === "card");
  setRadio("radio-gopay", selectedMethod === "gopay");

  const methodHeader = document.getElementById("method-header-display");
  if (methodHeader) {
    methodHeader.innerHTML =
      selectedMethod === "card"
        ? `<div class="expand-card-icon"><i class="ph-bold ph-credit-card"></i></div>
         <span class="expand-step-sub">Credit or debit card</span>`
        : `<div class="expand-gopay-icon"><span>GP</span></div>
         <span class="expand-step-sub">GoPay</span>`;
  }
}

function setRadio(id, active) {
  document.getElementById(id)?.classList.toggle("selected", active);
}

let activeAnchor = null;

function positionDropdown(dropdown, triggerEl) {
  const rect = triggerEl.getBoundingClientRect();
  const dropdownHeight = dropdown.offsetHeight || 300;
  const viewportHeight = window.innerHeight;
  const viewportWidth = window.innerWidth;

  let top = rect.bottom + 8;
  if (top + dropdownHeight > viewportHeight - 16) {

    top = rect.top - dropdownHeight - 8;
    if (top < 16) {

      top = Math.max(16, viewportHeight - dropdownHeight - 16);
    }
  }

  let left = rect.left;
  if (left + 380 > viewportWidth) {

    left = Math.max(16, viewportWidth - 380 - 16);
  }

  dropdown.style.top = `${top}px`;
  dropdown.style.left = `${left}px`;
  dropdown.style.right = "auto";
}

function openDropdown(dropdown, triggerEl) {
  positionDropdown(dropdown, triggerEl);
  dropdown.classList.add("open");
  activeAnchor = { dropdown, triggerEl };
}

function setupDropdownTracking() {
  function trackLoop() {
    if (activeAnchor) {
      const { dropdown, triggerEl } = activeAnchor;
      if (dropdown.classList.contains("open")) {
        positionDropdown(dropdown, triggerEl);
      } else {
        activeAnchor = null;
      }
    }
    requestAnimationFrame(trackLoop);
  }
  requestAnimationFrame(trackLoop);
}

function setupSummaryDropdowns() {
  const infoRows = document.querySelectorAll(".expand-info-row");
  const dateRow = infoRows[0];
  const guestRow = infoRows[1];

  if (dateRow) setupDateGantiButton(dateRow);
  if (guestRow) setupGuestGantiButton(guestRow);

  setupDropdownTracking();
}

function setupDateGantiButton(dateRow) {
  const gantiBtn = dateRow.querySelector(".expand-change-sm");
  if (!gantiBtn) return;

  const dropdown = document.createElement("div");
  dropdown.id = "summaryCalendarDropdown";
  dropdown.style.position = "fixed";
  dropdown.style.zIndex = "var(--z-dropdown)";
  dateRow.after(dropdown);

  let calLoaded = false;
  let calReady = false;

  gantiBtn.addEventListener("click", async (e) => {
    e.stopPropagation();
    const isOpen = dropdown.classList.contains("open");

    const guestDd = document.getElementById("summaryGuestDropdown");
    if (guestDd) guestDd.classList.remove("open");

    if (isOpen) {
      dropdown.classList.remove("open");
      return;
    }

    if (!calLoaded) {
      try {
        const res = await fetch("/popups/screen/calendar.html");
        const html = await res.text();
        dropdown.innerHTML = html;
        calLoaded = true;
        initSummaryCalendar(dropdown);
        calReady = true;
      } catch (err) {
        return;
      }
    }

    openDropdown(dropdown, gantiBtn);
  });

  document.addEventListener("click", (e) => {
    if (!dropdown.contains(e.target) && e.target !== gantiBtn) {
      dropdown.classList.remove("open");
    }
  });
}

function initSummaryCalendar(container) {
  let rangeStart = null;
  let rangeEnd = null;
  let hoverDate = null;
  let phase = "idle";

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const maxDate = new Date(
    today.getFullYear() + 2,
    today.getMonth(),
    today.getDate(),
  );

  const MONTH_NAMES = [
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
  const MONTH_SHORT = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "Mei",
    "Jun",
    "Jul",
    "Ags",
    "Sep",
    "Okt",
    "Nov",
    "Des",
  ];

  let leftYear = today.getFullYear();
  let leftMonth = today.getMonth();

  function isSameDay(a, b) {
    return (
      a &&
      b &&
      a.getFullYear() === b.getFullYear() &&
      a.getMonth() === b.getMonth() &&
      a.getDate() === b.getDate()
    );
  }

  function fmtDate(d) {
    return `${d.getDate()} ${MONTH_SHORT[d.getMonth()]} ${d.getFullYear()}`;
  }

  function addMonths(year, month, delta) {
    const d = new Date(year, month + delta, 1);
    return { year: d.getFullYear(), month: d.getMonth() };
  }

  function onDayClick(date) {
    if (phase === "idle") {
      rangeStart = date;
      rangeEnd = null;
      hoverDate = null;
      phase = "selecting";
    } else {
      if (isSameDay(date, rangeStart)) {
        rangeStart = null;
        rangeEnd = null;
        hoverDate = null;
        phase = "idle";
      } else if (date < rangeStart) {
        rangeStart = date;
        rangeEnd = null;
        hoverDate = null;
        phase = "selecting";
      } else {
        rangeEnd = date;
        hoverDate = null;
        phase = "idle";

        applyDateToSummary(rangeStart, rangeEnd);
        setTimeout(() => {
          container.classList.remove("open");
        }, 300);
      }
    }
    renderCalendar();
  }

  function applyDateToSummary(start, end) {
    const dateVal = document.querySelector(
      ".expand-info-row .expand-info-value",
    );
    if (dateVal && start && end) {
      const s = start < end ? start : end;
      const e = start < end ? end : start;
      dateVal.textContent = `${s.getDate()} ${MONTH_SHORT[s.getMonth()]} – ${e.getDate()} ${MONTH_SHORT[e.getMonth()]}, ${e.getFullYear()}`;
    }
  }

  function onDayHover(date) {
    if (phase !== "selecting" || !rangeStart) {
      if (hoverDate) {
        hoverDate = null;
        updateDayClasses();
      }
      return;
    }
    if (hoverDate && isSameDay(date, hoverDate)) return;
    hoverDate = date;
    updateDayClasses();
  }

  function updateDayClasses() {
    container
      .querySelectorAll(".calendar-day:not(.empty):not(.disabled)")
      .forEach((el) => {
        const [y, m, d] = el.dataset.date.split("-").map(Number);
        const date = new Date(y, m - 1, d);
        const cls = ["calendar-day"];
        if (el.classList.contains("today")) cls.push("today");

        const pStart =
          phase === "selecting" &&
          rangeStart &&
          hoverDate &&
          !isSameDay(hoverDate, rangeStart) &&
          hoverDate > rangeStart
            ? rangeStart
            : null;
        const pEnd = pStart ? hoverDate : null;

        if (phase === "idle" && rangeStart && rangeEnd) {
          const s = rangeStart < rangeEnd ? rangeStart : rangeEnd;
          const e = rangeStart < rangeEnd ? rangeEnd : rangeStart;
          if (isSameDay(date, s)) cls.push("range-start");
          else if (isSameDay(date, e)) cls.push("range-end");
          else if (date > s && date < e) cls.push("in-range");
        } else if (phase === "selecting") {
          if (isSameDay(date, rangeStart)) {
            cls.push(
              pStart && isSameDay(date, pStart) ? "range-start" : "selected",
            );
          } else if (pStart && pEnd) {
            if (isSameDay(date, pEnd)) cls.push("range-end");
            else if (date > pStart && date < pEnd) cls.push("in-range");
          }
        }
        el.className = cls.join(" ");
      });
  }

  function renderMonth(monthContainer, year, month) {
    monthContainer.querySelector(".calendar-month-name").textContent =
      `${MONTH_NAMES[month]} ${year}`;
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const body = monthContainer.querySelector(".calendar-body");
    body.innerHTML = "";
    body.addEventListener("mouseleave", () => {
      if (hoverDate) {
        hoverDate = null;
        updateDayClasses();
      }
    });

    for (let i = 0; i < firstDay; i++) {
      const el = document.createElement("div");
      el.className = "calendar-day empty";
      body.appendChild(el);
    }
    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(year, month, day);
      const dayEl = document.createElement("div");
      dayEl.className = "calendar-day";
      dayEl.textContent = day;
      dayEl.dataset.date = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
      if (date < today || date > maxDate) {
        dayEl.classList.add("disabled");
      } else {
        if (isSameDay(date, today)) dayEl.classList.add("today");
        dayEl.addEventListener("click", () => onDayClick(date));
        dayEl.addEventListener("mouseenter", () => onDayHover(date));
      }
      body.appendChild(dayEl);
    }
  }

  function updateArrowState() {
    const [leftM, rightM] = container.querySelectorAll(".calendar-month");
    const lArr = leftM?.querySelector(".ph-caret-left");
    const rArr = rightM?.querySelector(".ph-caret-right");
    const atMin =
      leftYear === today.getFullYear() && leftMonth === today.getMonth();
    if (lArr) lArr.style.visibility = atMin ? "hidden" : "visible";
    const right = addMonths(leftYear, leftMonth, 1);
    const atMax =
      right.year === maxDate.getFullYear() &&
      right.month === maxDate.getMonth();
    if (rArr) rArr.style.visibility = atMax ? "hidden" : "visible";
  }

  function renderCalendar() {
    const right = addMonths(leftYear, leftMonth, 1);
    const [leftM, rightM] = container.querySelectorAll(".calendar-month");
    if (!leftM || !rightM) return;
    renderMonth(leftM, leftYear, leftMonth);
    renderMonth(rightM, right.year, right.month);
    updateDayClasses();
    updateArrowState();
  }

  const [leftM, rightM] = container.querySelectorAll(".calendar-month");
  leftM?.querySelector(".ph-caret-left")?.addEventListener("click", () => {
    const prev = addMonths(leftYear, leftMonth, -1);
    if (
      prev.year < today.getFullYear() ||
      (prev.year === today.getFullYear() && prev.month < today.getMonth())
    )
      return;
    leftYear = prev.year;
    leftMonth = prev.month;
    renderCalendar();
  });
  rightM?.querySelector(".ph-caret-right")?.addEventListener("click", () => {
    const right = addMonths(leftYear, leftMonth, 1);
    const newRight = addMonths(leftYear, leftMonth, 2);
    if (
      newRight.year > maxDate.getFullYear() ||
      (newRight.year === maxDate.getFullYear() &&
        newRight.month > maxDate.getMonth())
    )
      return;
    leftYear = right.year;
    leftMonth = right.month;
    renderCalendar();
  });

  renderCalendar();
}

function setupGuestGantiButton(guestRow) {
  const gantiBtn = guestRow.querySelector(".expand-change-sm");
  if (!gantiBtn) return;

  const dropdown = document.createElement("div");
  dropdown.id = "summaryGuestDropdown";
  dropdown.style.position = "fixed";
  dropdown.style.zIndex = "var(--z-dropdown)";
  guestRow.after(dropdown);

  let guestLoaded = false;

  gantiBtn.addEventListener("click", async (e) => {
    e.stopPropagation();
    const isOpen = dropdown.classList.contains("open");

    const calDd = document.getElementById("summaryCalendarDropdown");
    if (calDd) calDd.classList.remove("open");

    if (isOpen) {
      dropdown.classList.remove("open");
      return;
    }

    if (!guestLoaded) {
      try {
        const res = await fetch("/popups/screen/guest_counter.html");
        const html = await res.text();
        dropdown.innerHTML = html;
        guestLoaded = true;
        initSummaryGuestCounter(dropdown, guestRow);
      } catch (err) {
        return;
      }
    }

    openDropdown(dropdown, gantiBtn);
  });

  document.addEventListener("click", (e) => {
    if (!dropdown.contains(e.target) && e.target !== gantiBtn) {
      dropdown.classList.remove("open");
    }
  });
}

function initSummaryGuestCounter(container, guestRow) {
  const MONTH_SHORT = [
    "Jan",
    "Feb",
    "Mar",
    "Apr",
    "Mei",
    "Jun",
    "Jul",
    "Ags",
    "Sep",
    "Okt",
    "Nov",
    "Des",
  ];
  const counterItems = container.querySelectorAll(".counter-row");
  const dewasaRow = container.querySelector('[data-group="dewasa"]');
  const anakRow = container.querySelector('[data-group="anak"]');

  function getTotal() {
    return (
      parseInt(dewasaRow.querySelector(".counter-value").textContent) +
      parseInt(anakRow.querySelector(".counter-value").textContent)
    );
  }

  function updateGuestDisplay() {
    const getValue = (group) => {
      const row = container.querySelector(`[data-group="${group}"]`);
      return row
        ? parseInt(row.querySelector(".counter-value").textContent)
        : 0;
    };
    const dewasa = getValue("dewasa");
    const anak = getValue("anak");
    const bayi = getValue("bayi");
    const hewan = getValue("hewan");

    const parts = [];
    if (dewasa > 0) parts.push(`${dewasa} pengunjung`);
    if (anak > 0) parts.push(`${anak} anak`);
    if (bayi > 0) parts.push(`${bayi} bayi`);
    if (hewan > 0) parts.push(`${hewan} peliharaan`);

    const allInfoRows = document.querySelectorAll(".expand-info-row");
    const guestVal = allInfoRows[1]?.querySelector(".expand-info-value");
    if (guestVal) guestVal.textContent = parts.join(", ") || "1 pengunjung";
  }

  counterItems.forEach((item) => {
    const minusBtn = item.querySelector(".minus");
    const plusBtn = item.querySelector(".plus");
    const counterValue = item.querySelector(".counter-value");
    const min = parseInt(item.dataset.min) || 0;
    const max = parseInt(item.dataset.max) || 10;
    let value = parseInt(counterValue.textContent);

    function updateSiblings() {
      counterItems.forEach((other) => {
        if (
          other !== item &&
          (other.dataset.group === "dewasa" || other.dataset.group === "anak")
        ) {
          const ov = parseInt(
            other.querySelector(".counter-value").textContent,
          );
          const om = parseInt(other.dataset.max);
          other
            .querySelector(".plus")
            .classList.toggle("disabled", ov >= om || getTotal() >= 16);
        }
      });
    }

    function updateCounter() {
      counterValue.textContent = value;
      minusBtn.classList.toggle("disabled", value <= min);
      const isOrang =
        item.dataset.group === "dewasa" || item.dataset.group === "anak";
      plusBtn.classList.toggle(
        "disabled",
        isOrang ? value >= max || getTotal() >= 16 : value >= max,
      );
    }

    plusBtn.addEventListener("click", () => {
      const isOrang =
        item.dataset.group === "dewasa" || item.dataset.group === "anak";
      if (isOrang ? value < max && getTotal() < 16 : value < max) {
        value++;
        updateCounter();
        if (isOrang) updateSiblings();
        updateGuestDisplay();
      }
    });

    minusBtn.addEventListener("click", () => {
      if (value > min) {
        value--;
        updateCounter();
        if (item.dataset.group === "dewasa" || item.dataset.group === "anak")
          updateSiblings();
        updateGuestDisplay();
      }
    });

    updateCounter();
  });
}
