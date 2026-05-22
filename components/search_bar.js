document.addEventListener("DOMContentLoaded", () => {

  // ── Semua elemen dideklarasikan di sini dulu ──────────────────────────────
  const searchBar = document.querySelector(".search-bar");
  const searchFields = document.querySelector(".search-fields");

  const destinationField    = searchFields?.children[0];
  const dateField           = searchFields?.children[1];
  const guestField          = searchFields?.children[2];

  const destinationDropdown = document.getElementById("destinationDropdown");
  const calendarDropdown    = document.getElementById("calendarDropdown");
  const guestDropdown       = document.getElementById("guestCounterDropdown");

  const destinationInput = document.getElementById("destinationInput");
  const guestSummary     = document.getElementById("guestSummary");
  const dateSummary      = document.getElementById("dateSummary");
  // ──────────────────────────────────────────────────────────────────────────

  function closeAllDropdowns() {
    destinationDropdown?.classList.remove("open");
    calendarDropdown?.classList.remove("open");
    guestDropdown?.classList.remove("open");
  }

  if (searchBar) {
    const observer = new IntersectionObserver(
      ([entry]) => { if (!entry.isIntersecting) closeAllDropdowns(); },
      { threshold: 0 }
    );
    observer.observe(searchBar);
  }

  let activeAnchor = null;

  function positionDropdown(dropdown, triggerEl, align) {
    const searchBarRect = searchBar.getBoundingClientRect();
    const triggerRect   = triggerEl.getBoundingClientRect();
    dropdown.style.top = `${triggerRect.bottom + 8}px`;
    if (align === "right") {
      dropdown.style.left  = "auto";
      dropdown.style.right = `${window.innerWidth - searchBarRect.right}px`;
    } else {
      dropdown.style.left  = `${searchBarRect.left}px`;
      dropdown.style.right = "auto";
    }
  }

  function trackLoop() {
    if (activeAnchor) {
      const { dropdown, triggerEl, align } = activeAnchor;
      if (dropdown.classList.contains("open")) {
        positionDropdown(dropdown, triggerEl, align);
      } else {
        activeAnchor = null;
      }
    }
    requestAnimationFrame(trackLoop);
  }
  requestAnimationFrame(trackLoop);

  function openDropdown(dropdown, triggerEl, align) {
    positionDropdown(dropdown, triggerEl, align);
    dropdown.classList.add("open");
    activeAnchor = { dropdown, triggerEl, align };
  }

  // ── DESTINATION ───────────────────────────────────────────────────────────
  let destinationLoaded = false;

  async function loadDestinationDropdown() {
    try {
      const res = await fetch("/teman_singgah/popups/screen/destinations.html");
      const html = await res.text();
      destinationDropdown.innerHTML = html;
      destinationLoaded = true;
      initDestinationItems();
    } catch (err) {}
  }

  function toggleDestinationDropdown(triggerEl) {
    if (!destinationLoaded) return;
    calendarDropdown.classList.remove("open");
    guestDropdown.classList.remove("open");
    if (destinationDropdown.classList.contains("open")) {
      destinationDropdown.classList.remove("open");
      activeAnchor = null;
    } else {
      openDropdown(destinationDropdown, triggerEl, "left");
    }
  }

  destinationField?.addEventListener("click", (e) => {
    e.stopPropagation();
    toggleDestinationDropdown(destinationField);
  });

  destinationInput?.addEventListener("focus", (e) => {
    e.stopPropagation();
    if (!destinationDropdown.classList.contains("open")) {
      toggleDestinationDropdown(destinationField);
    }
  });

  destinationInput?.addEventListener("click", (e) => e.stopPropagation());

  document.addEventListener("click", () => destinationDropdown.classList.remove("open"));
  window.addEventListener("resize",  () => destinationDropdown.classList.remove("open"));
  destinationDropdown.addEventListener("click", (e) => e.stopPropagation());

  function initDestinationItems() {
    const items = destinationDropdown.querySelectorAll(".destination-item");
    items.forEach((item) => {
      item.addEventListener("click", () => {
        const nameEl = item.querySelector(".dest-name");
        if (!nameEl) return;
        destinationInput.value = nameEl.textContent.trim();
        destinationInput.classList.add("has-value");
        destinationDropdown.classList.remove("open");
      });
    });
  }

  loadDestinationDropdown();

  // ── GUEST COUNTER ─────────────────────────────────────────────────────────
  let guestLoaded   = false;
  let hasInteracted = false;

  async function loadGuestDropdown() {
    try {
      const res = await fetch("/teman_singgah/popups/screen/guest_counter.html");
      const html = await res.text();
      guestDropdown.innerHTML = html;
      guestLoaded = true;
      initCounter();
    } catch (err) {}
  }

  function updateGuestSummary() {
    const getValue = (group) => {
      const row = guestDropdown.querySelector(`[data-group="${group}"]`);
      return row ? parseInt(row.querySelector(".counter-value").textContent) : 0;
    };
    const dewasa = getValue("dewasa");
    const anak   = getValue("anak");
    const bayi   = getValue("bayi");
    const hewan  = getValue("hewan");
    const totalPengunjung = dewasa + anak + bayi;

    if (totalPengunjung === 0 && hewan === 0) {
      guestSummary.textContent = "Tambahkan Pengunjung";
      guestSummary.classList.remove("has-value");
      return;
    }
    const parts = [];
    if (totalPengunjung > 0) parts.push(`${totalPengunjung} Pengunjung`);
    if (hewan > 0)           parts.push(`${hewan} Peliharaan`);
    guestSummary.textContent = parts.join(", ");
    guestSummary.classList.add("has-value");
  }

  function toggleGuestDropdown(triggerEl) {
    if (!guestLoaded) return;
    calendarDropdown.classList.remove("open");
    destinationDropdown.classList.remove("open");
    if (!hasInteracted) { hasInteracted = true; updateGuestSummary(); }
    if (guestDropdown.classList.contains("open")) {
      guestDropdown.classList.remove("open");
      activeAnchor = null;
    } else {
      openDropdown(guestDropdown, triggerEl, "right");
    }
  }

  guestField?.addEventListener("click", (e) => {
    e.stopPropagation();
    toggleGuestDropdown(guestField);
  });

  document.addEventListener("click", () => guestDropdown.classList.remove("open"));
  window.addEventListener("resize",  () => guestDropdown.classList.remove("open"));
  guestDropdown.addEventListener("click", (e) => e.stopPropagation());

  function initCounter() {
    const counterItem = guestDropdown.querySelectorAll(".counter-row");
    const dewasaRow   = guestDropdown.querySelector('[data-group="dewasa"]');
    const anakRow     = guestDropdown.querySelector('[data-group="anak"]');

    counterItem.forEach((item) => {
      const minusButton  = item.querySelector(".minus");
      const plusButton   = item.querySelector(".plus");
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

      function updateSiblingOrangGroup() {
        counterItem.forEach((other) => {
          if (other !== item && (other.dataset.group === "dewasa" || other.dataset.group === "anak")) {
            const otherVal = parseInt(other.querySelector(".counter-value").textContent);
            const otherMax = parseInt(other.dataset.max);
            other.querySelector(".plus").classList.toggle("disabled", otherVal >= otherMax || getTotal() >= 16);
          }
        });
      }

      function updateCounter() {
        counterValue.textContent = value;
        minusButton.classList.toggle("disabled", value <= min);
        const isOrangGroup = item.dataset.group === "dewasa" || item.dataset.group === "anak";
        plusButton.classList.toggle("disabled", isOrangGroup ? (value >= max || getTotal() >= 16) : value >= max);
      }

      plusButton.addEventListener("click", () => {
        const isOrangGroup = item.dataset.group === "dewasa" || item.dataset.group === "anak";
        if (isOrangGroup) {
          if (value < max && getTotal() < 16) { value++; updateCounter(); updateSiblingOrangGroup(); updateGuestSummary(); }
        } else {
          if (value < max) { value++; updateCounter(); updateGuestSummary(); }
        }
      });

      minusButton.addEventListener("click", () => {
        if (value > min) {
          value--;
          updateCounter();
          if (item.dataset.group === "dewasa" || item.dataset.group === "anak") updateSiblingOrangGroup();
          updateGuestSummary();
        }
      });

      updateCounter();
    });
  }

  loadGuestDropdown();

  // ── CALENDAR ──────────────────────────────────────────────────────────────
  let calendarLoaded = false;

  const MONTH_SHORT = ["Jan","Feb","Mar","Apr","Mei","Jun","Jul","Ags","Sep","Okt","Nov","Des"];
  const MONTH_NAMES = ["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];

  const today   = new Date(); today.setHours(0, 0, 0, 0);
  const maxDate = new Date(today.getFullYear() + 2, today.getMonth(), today.getDate());

  let leftYear  = today.getFullYear();
  let leftMonth = today.getMonth();
  let rangeStart = null;
  let rangeEnd   = null;
  let hoverDate  = null;
  let phase      = "idle";

  function updateDateSummary() {
    if (!rangeStart) {
      dateSummary.textContent = "Tambahkan Tanggal";
      dateSummary.classList.remove("has-value");
      return;
    }
    const fmt = (d) => `${d.getDate()} ${MONTH_SHORT[d.getMonth()]} ${d.getFullYear()}`;
    dateSummary.textContent = rangeEnd ? `${fmt(rangeStart)} - ${fmt(rangeEnd)}` : fmt(rangeStart);
    dateSummary.classList.add("has-value");
  }

  function toggleCalendarDropdown(triggerEl) {
    if (!calendarLoaded) return;
    guestDropdown.classList.remove("open");
    destinationDropdown.classList.remove("open");
    if (calendarDropdown.classList.contains("open")) {
      calendarDropdown.classList.remove("open");
      activeAnchor = null;
    } else {
      openDropdown(calendarDropdown, triggerEl, "left");
    }
  }

  dateField?.addEventListener("click", (e) => {
    e.stopPropagation();
    toggleCalendarDropdown(dateField);
  });

  document.addEventListener("click", () => calendarDropdown.classList.remove("open"));
  window.addEventListener("resize",  () => calendarDropdown.classList.remove("open"));
  calendarDropdown.addEventListener("click", (e) => e.stopPropagation());

  async function loadCalendarDropdown() {
    try {
      const res = await fetch("/teman_singgah/popups/screen/calendar.html");
      const html = await res.text();
      calendarDropdown.innerHTML = html;
      calendarLoaded = true;
      attachArrows();
      renderCalendar();
    } catch (err) {}
  }

  loadCalendarDropdown();

  function addMonths(year, month, delta) {
    const d = new Date(year, month + delta, 1);
    return { year: d.getFullYear(), month: d.getMonth() };
  }

  function isSameDay(a, b) {
    return a && b &&
      a.getFullYear() === b.getFullYear() &&
      a.getMonth()    === b.getMonth()    &&
      a.getDate()     === b.getDate();
  }

  function onDayClick(date) {
    if (phase === "idle") {
      rangeStart = date; rangeEnd = null; hoverDate = null; phase = "selecting";
    } else {
      if (isSameDay(date, rangeStart)) {
        rangeStart = null; rangeEnd = null; hoverDate = null; phase = "idle";
      } else if (date < rangeStart) {
        rangeStart = date; rangeEnd = null; hoverDate = null; phase = "selecting";
      } else {
        rangeEnd = date; hoverDate = null; phase = "idle";
      }
    }
    updateDateSummary();
    renderCalendar();
  }

  function onDayHover(date) {
    if (phase !== "selecting" || !rangeStart) {
      if (hoverDate !== null) { hoverDate = null; updateDayClasses(); }
      return;
    }
    if (hoverDate && isSameDay(date, hoverDate)) return;
    hoverDate = date;
    updateDayClasses();
  }

  function onBodyLeave() {
    if (hoverDate !== null) { hoverDate = null; updateDayClasses(); }
  }

  function updateDayClasses() {
    const allDays = calendarDropdown.querySelectorAll(".calendar-day:not(.empty):not(.disabled)");
    let previewStart = null, previewEnd = null;

    if (phase === "selecting" && rangeStart && hoverDate && !isSameDay(hoverDate, rangeStart) && hoverDate > rangeStart) {
      previewStart = rangeStart;
      previewEnd   = hoverDate;
    }

    allDays.forEach((el) => {
      const dateAttr = el.dataset.date;
      if (!dateAttr) return;
      const [y, m, d] = dateAttr.split("-").map(Number);
      const date = new Date(y, m - 1, d);
      const classes = ["calendar-day"];
      if (el.classList.contains("today")) classes.push("today");

      if (phase === "idle" && rangeStart && rangeEnd) {
        const s = rangeStart < rangeEnd ? rangeStart : rangeEnd;
        const e = rangeStart < rangeEnd ? rangeEnd   : rangeStart;
        if      (isSameDay(date, s))   classes.push("range-start");
        else if (isSameDay(date, e))   classes.push("range-end");
        else if (date > s && date < e) classes.push("in-range");
      } else if (phase === "selecting") {
        if (isSameDay(date, rangeStart)) {
          classes.push(previewStart && previewEnd && isSameDay(date, previewStart) ? "range-start" : "selected");
        } else if (previewStart && previewEnd) {
          if      (isSameDay(date, previewEnd))              classes.push("range-end");
          else if (date > previewStart && date < previewEnd) classes.push("in-range");
        }
      }
      el.className = classes.join(" ");
    });
  }

  function renderMonth(container, year, month) {
    container.querySelector(".calendar-month-name").textContent = `${MONTH_NAMES[month]} ${year}`;
    const firstDay    = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const body = container.querySelector(".calendar-body");
    body.innerHTML = "";
    body.addEventListener("mouseleave", onBodyLeave);

    for (let i = 0; i < firstDay; i++) {
      const empty = document.createElement("div");
      empty.className = "calendar-day empty";
      body.appendChild(empty);
    }

    for (let d = 1; d <= daysInMonth; d++) {
      const date  = new Date(year, month, d);
      const dayEl = document.createElement("div");
      dayEl.className   = "calendar-day";
      dayEl.textContent = d;
      dayEl.dataset.date = `${date.getFullYear()}-${String(date.getMonth()+1).padStart(2,"0")}-${String(date.getDate()).padStart(2,"0")}`;

      if (date < today || date > maxDate) {
        dayEl.classList.add("disabled");
      } else {
        if (isSameDay(date, today)) dayEl.classList.add("today");
        dayEl.addEventListener("click",      () => onDayClick(date));
        dayEl.addEventListener("mouseenter", () => onDayHover(date));
      }
      body.appendChild(dayEl);
    }
  }

  function renderCalendar() {
    const right      = addMonths(leftYear, leftMonth, 1);
    const containers = calendarDropdown.querySelectorAll(".calendar-month");
    renderMonth(containers[0], leftYear, leftMonth);
    renderMonth(containers[1], right.year, right.month);
    updateDayClasses();
    updateArrowState();
  }

  function updateArrowState() {
    const containers = calendarDropdown.querySelectorAll(".calendar-month");
    const leftArrow  = containers[0].querySelector(".ph-caret-left");
    const rightArrow = containers[1].querySelector(".ph-caret-right");
    const atMin = leftYear === today.getFullYear() && leftMonth === today.getMonth();
    if (leftArrow) leftArrow.style.visibility = atMin ? "hidden" : "visible";
    const right = addMonths(leftYear, leftMonth, 1);
    const atMax = right.year === maxDate.getFullYear() && right.month === maxDate.getMonth();
    if (rightArrow) rightArrow.style.visibility = atMax ? "hidden" : "visible";
  }

  function attachArrows() {
    const containers = calendarDropdown.querySelectorAll(".calendar-month");
    const leftArrow  = containers[0].querySelector(".ph-caret-left");
    const rightArrow = containers[1].querySelector(".ph-caret-right");

    leftArrow?.addEventListener("click", () => {
      const prev = addMonths(leftYear, leftMonth, -1);
      if (prev.year < today.getFullYear() || (prev.year === today.getFullYear() && prev.month < today.getMonth())) return;
      leftYear = prev.year; leftMonth = prev.month;
      renderCalendar();
    });

    rightArrow?.addEventListener("click", () => {
      const right    = addMonths(leftYear, leftMonth, 1);
      const newRight = addMonths(leftYear, leftMonth, 2);
      if (newRight.year > maxDate.getFullYear() || (newRight.year === maxDate.getFullYear() && newRight.month > maxDate.getMonth())) return;
      leftYear = right.year; leftMonth = right.month;
      renderCalendar();
    });
  }
});