document.addEventListener("DOMContentLoaded", () => {
  // ============================================================
  //  HELPERS
  // ============================================================

  let activeAnchor = null;

  function positionDropdown(dropdown, triggerEl) {
    const rect = triggerEl.getBoundingClientRect();
    dropdown.style.top = `${rect.bottom + 8}px`;
    dropdown.style.left = `${rect.left}px`;
    dropdown.style.right = "auto";
  }

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

  function openDropdown(dropdown, triggerEl) {
    positionDropdown(dropdown, triggerEl);
    dropdown.classList.add("open");
    activeAnchor = { dropdown, triggerEl };
  }

  // ============================================================
  //  CALENDAR DROPDOWN
  //  Trigger : checkinInput ATAU checkoutInput
  //  Posisi  : nempel di bawah .date-input-field
  // ============================================================

  const checkinInput = document.getElementById("checkinInput");
  const checkoutInput = document.getElementById("checkoutInput");
  const dateInputField = document.querySelector(".date-input-field");
  const calendarDropdown = document.getElementById("bookingCalendarDropdown");

  let calendarLoaded = false;
  let rangeStart = null;
  let rangeEnd = null;
  let hoverDate = null;
  let phase = "idle";
  let leftYear = new Date().getFullYear();
  let leftMonth = new Date().getMonth();

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

  function fmtDate(d) {
    return `${d.getDate()} ${MONTH_SHORT[d.getMonth()]} ${d.getFullYear()}`;
  }

  function isSameDay(a, b) {
    return (
      a &&
      b &&
      a.getFullYear() === b.getFullYear() &&
      a.getMonth() === b.getMonth() &&
      a.getDate() === b.getDate()
    );
  }

  function updateCalendarInputs() {
    checkinInput.value = rangeStart ? fmtDate(rangeStart) : "";
    checkoutInput.value = rangeEnd ? fmtDate(rangeEnd) : "";
    checkinInput.classList.toggle("has-value", !!rangeStart);
    checkoutInput.classList.toggle("has-value", !!rangeEnd);
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
        setTimeout(() => {
          calendarDropdown.classList.remove("open");
          activeAnchor = null;
        }, 300);
      }
    }
    updateCalendarInputs();
    renderCalendar();
  }

  function onDayHover(date) {
    if (phase !== "selecting" || !rangeStart) {
      if (hoverDate !== null) {
        hoverDate = null;
        updateDayClasses();
      }
      return;
    }
    if (hoverDate && isSameDay(date, hoverDate)) return;
    hoverDate = date;
    updateDayClasses();
  }

  function onBodyLeave() {
    if (hoverDate !== null) {
      hoverDate = null;
      updateDayClasses();
    }
  }

  function updateDayClasses() {
    calendarDropdown
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

  function addMonths(year, month, delta) {
    const d = new Date(year, month + delta, 1);
    return { year: d.getFullYear(), month: d.getMonth() };
  }

  function renderMonth(container, year, month) {
    container.querySelector(".calendar-month-name").textContent =
      `${MONTH_NAMES[month]} ${year}`;
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const body = container.querySelector(".calendar-body");
    body.innerHTML = "";
    body.addEventListener("mouseleave", onBodyLeave);

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

  function renderCalendar() {
    const right = addMonths(leftYear, leftMonth, 1);
    const containers = calendarDropdown.querySelectorAll(".calendar-month");
    renderMonth(containers[0], leftYear, leftMonth);
    renderMonth(containers[1], right.year, right.month);
    updateDayClasses();
    updateArrowState();
  }

  function updateArrowState() {
    const containers = calendarDropdown.querySelectorAll(".calendar-month");
    const lArr = containers[0].querySelector(".ph-caret-left");
    const rArr = containers[1].querySelector(".ph-caret-right");
    const atMin =
      leftYear === today.getFullYear() && leftMonth === today.getMonth();
    if (lArr) lArr.style.visibility = atMin ? "hidden" : "visible";
    const right = addMonths(leftYear, leftMonth, 1);
    const atMax =
      right.year === maxDate.getFullYear() &&
      right.month === maxDate.getMonth();
    if (rArr) rArr.style.visibility = atMax ? "hidden" : "visible";
  }

  function attachCalendarArrows() {
    const containers = calendarDropdown.querySelectorAll(".calendar-month");
    containers[0]
      .querySelector(".ph-caret-left")
      ?.addEventListener("click", () => {
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
    containers[1]
      .querySelector(".ph-caret-right")
      ?.addEventListener("click", () => {
        const right = addMonths(leftYear, leftMonth, 1),
          newRight = addMonths(leftYear, leftMonth, 2);
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
  }

  async function loadBookingCalendar() {
    try {
      const res = await fetch("/popups/calendar.html");
      const html = await res.text();
      calendarDropdown.innerHTML = html;
      calendarLoaded = true;
      attachCalendarArrows();
      renderCalendar();
    } catch (err) {
      console.error("Gagal memuat booking calendar:", err);
    }
  }

  // Posisi: nempel di bawah .date-input-field
  function toggleCalendarDropdown() {
    if (!calendarLoaded) return;
    guestDropdown?.classList.remove("open");
    if (calendarDropdown.classList.contains("open")) {
      calendarDropdown.classList.remove("open");
      activeAnchor = null;
    } else {
      openDropdown(calendarDropdown, dateInputField);
    }
  }

  // Trigger: checkinInput dan checkoutInput saja
  checkinInput?.addEventListener("click", (e) => {
    e.stopPropagation();
    toggleCalendarDropdown();
  });
  checkoutInput?.addEventListener("click", (e) => {
    e.stopPropagation();
    toggleCalendarDropdown();
  });
  checkinInput?.addEventListener("focus", (e) => {
    e.stopPropagation();
    if (!calendarDropdown.classList.contains("open")) toggleCalendarDropdown();
  });
  checkoutInput?.addEventListener("focus", (e) => {
    e.stopPropagation();
    if (!calendarDropdown.classList.contains("open")) toggleCalendarDropdown();
  });

  document.addEventListener("click", () => {
    calendarDropdown?.classList.remove("open");
  });
  window.addEventListener("resize", () => {
    calendarDropdown?.classList.remove("open");
  });
  calendarDropdown?.addEventListener("click", (e) => e.stopPropagation());

  loadBookingCalendar();

  // ============================================================
  //  GUEST COUNTER DROPDOWN
  //  Trigger : guestInput
  //  Posisi  : nempel di bawah .booking-field yang berisi guestInput
  // ============================================================

  const guestInput = document.getElementById("guestInput");
  const guestDropdown = document.getElementById("bookingGuestDropdown");
  const guestField = document.querySelector(".booking-field:has(#guestInput)");

  let guestLoaded = false;

  function updateGuestInput() {
    const getValue = (group) => {
      const row = guestDropdown.querySelector(`[data-group="${group}"]`);
      return row
        ? parseInt(row.querySelector(".counter-value").textContent)
        : 0;
    };
    const total = getValue("dewasa") + getValue("anak") + getValue("bayi");
    guestInput.value = total > 0 ? total : 1;
  }

  function initGuestCounter() {
    const counterItems = guestDropdown.querySelectorAll(".counter-row");
    const dewasaRow = guestDropdown.querySelector('[data-group="dewasa"]');
    const anakRow = guestDropdown.querySelector('[data-group="anak"]');

    counterItems.forEach((item) => {
      const minusBtn = item.querySelector(".minus");
      const plusBtn = item.querySelector(".plus");
      const counterValue = item.querySelector(".counter-value");
      const min = parseInt(item.dataset.min) || 0;
      const max = parseInt(item.dataset.max) || 10;
      let value = parseInt(counterValue.textContent);

      function getTotal() {
        return (
          parseInt(dewasaRow.querySelector(".counter-value").textContent) +
          parseInt(anakRow.querySelector(".counter-value").textContent)
        );
      }

      function updateSiblings() {
        counterItems.forEach((other) => {
          if (
            other !== item &&
            (other.dataset.group === "dewasa" || other.dataset.group === "anak")
          ) {
            const ov = parseInt(
                other.querySelector(".counter-value").textContent,
              ),
              om = parseInt(other.dataset.max);
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
          updateGuestInput();
        }
      });

      minusBtn.addEventListener("click", () => {
        if (value > min) {
          value--;
          updateCounter();
          if (item.dataset.group === "dewasa" || item.dataset.group === "anak")
            updateSiblings();
          updateGuestInput();
        }
      });

      updateCounter();
    });
  }

  async function loadBookingGuest() {
    try {
      const res = await fetch("/popups/guest_counter.html");
      const html = await res.text();
      guestDropdown.innerHTML = html;
      guestLoaded = true;
      initGuestCounter();
    } catch (err) {
      console.error("Gagal memuat booking guest counter:", err);
    }
  }

  // Posisi: nempel di bawah .booking-field yang berisi guestInput
  function toggleGuestDropdown() {
    if (!guestLoaded) return;
    calendarDropdown?.classList.remove("open");
    if (guestDropdown.classList.contains("open")) {
      guestDropdown.classList.remove("open");
      activeAnchor = null;
    } else {
      openDropdown(guestDropdown, guestField);
    }
  }

  // Trigger: guestInput saja
  guestInput?.addEventListener("click", (e) => {
    e.stopPropagation();
    toggleGuestDropdown();
  });
  guestInput?.addEventListener("focus", (e) => {
    e.stopPropagation();
    if (!guestDropdown.classList.contains("open")) toggleGuestDropdown();
  });

  document.addEventListener("click", () => {
    guestDropdown?.classList.remove("open");
  });
  window.addEventListener("resize", () => {
    guestDropdown?.classList.remove("open");
  });
  guestDropdown?.addEventListener("click", (e) => e.stopPropagation());

  loadBookingGuest();
});
