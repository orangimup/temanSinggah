window.addEventListener("load", () => {
  const mapEl = document.getElementById("propertyMap");
  if (!mapEl) return;

  const lat = (window.LISTING_LAT && !isNaN(window.LISTING_LAT))
    ? parseFloat(window.LISTING_LAT) : -8.5069;
  const lng = (window.LISTING_LNG && !isNaN(window.LISTING_LNG))
    ? parseFloat(window.LISTING_LNG) : 115.2625;
  const name = window.LISTING_NAME || "Penginapan";
  const loc = window.LISTING_LOC || "";

  const map = L.map("propertyMap", {
    center: [lat, lng],
    zoom: 15,
    scrollWheelZoom: false,
    zoomControl: false,
  });

  L.tileLayer(
    "https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",
    { attribution: "© OpenStreetMap contributors", maxZoom: 19 },
  ).addTo(map);

  const icon = L.divIcon({
    html: `<i class="ph-fill ph-map-pin" style="font-size:32px;color:#8b2500;display:block;"></i>`,
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    className: "",
  });

  L.marker([lat, lng], { icon })
    .addTo(map)
    .bindPopup(`<div style="display:flex;flex-direction:column;gap:2px;font-family:inherit;">
      <strong style="font-size:13px;">${name}</strong>
      <span style="font-size:12px;color:#6b7280;">${loc}</span>
    </div>`)
    .openPopup();

  document.getElementById("zoomIn")?.addEventListener("click", () => map.zoomIn());
  document.getElementById("zoomOut")?.addEventListener("click", () => map.zoomOut());
});

let nights = 0;
let rangeStart = null;
let rangeEnd = null;

function formatRupiah(amount) {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(amount);
}

document.querySelectorAll(".room-button").forEach((btn) => {
  btn.addEventListener("click", () => {
    const card = btn.closest(".room-card");
    if (!card) return;
    document.querySelectorAll(".room-card").forEach((c) => { c.style.outline = "none"; });
    card.style.outline = "2px solid var(--color-primary)";
    card.style.borderRadius = "var(--radius-3xl)";
    const price = parseInt(btn.dataset.price, 10);
    if (!isNaN(price)) {
      const priceAmountEl = document.querySelector(".booking-price-amount");
      if (priceAmountEl) {
        priceAmountEl.textContent = formatRupiah(price);
        priceAmountEl.classList.remove("price-highlight");
        void priceAmountEl.offsetWidth;
        priceAmountEl.classList.add("price-highlight");
      }
      updateNightsDisplay(price);
    }
    document.querySelector(".booking-sidebar")?.scrollIntoView({ behavior: "smooth", block: "start" });
  });
});

function updateNightsDisplay(overridePrice) {
  let el = document.getElementById("bookingNightsSummary");
  if (!el) {
    el = document.createElement("div");
    el.id = "bookingNightsSummary";
    el.style.cssText = `
      font-size: var(--text-sm);
      color: var(--color-text-secondary);
      padding-top: var(--space-8);
      border-top: 1.5px solid var(--color-border-subtle);
    `;
    const submitBtn = document.querySelector(".booking-submit");
    submitBtn?.parentNode.insertBefore(el, submitBtn);
  }
  if (nights > 0) {
    el.textContent = `${nights} malam dipilih`;
    el.style.display = "block";
  } else {
    el.style.display = "none";
  }
}

document.addEventListener("DOMContentLoaded", () => {
  updateNightsDisplay();

  const isLoggedIn = window.IS_LOGGED_IN === true;

  function openAuthPopup() {
    const overlay = document.getElementById("authOverlay");
    if (overlay) overlay.classList.add("active");
  }

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

  const checkinInput = document.getElementById("checkinInput");
  const checkoutInput = document.getElementById("checkoutInput");
  const dateInputField = document.querySelector(".date-input-field");
  const calendarDropdown = document.getElementById("bookingCalendarDropdown");

  let calendarLoaded = false;
  let hoverDate = null;
  let phase = "idle";
  let leftYear = new Date().getFullYear();
  let leftMonth = new Date().getMonth();

  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const maxDate = new Date(today.getFullYear() + 2, today.getMonth(), today.getDate());

  const MONTH_NAMES = ["Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
  const MONTH_SHORT = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun",
    "Jul", "Ags", "Sep", "Okt", "Nov", "Des"];

  function isSameDay(a, b) {
    return a && b &&
      a.getFullYear() === b.getFullYear() &&
      a.getMonth() === b.getMonth() &&
      a.getDate() === b.getDate();
  }

  function fmtDisplay(d) {
    return `${d.getDate()} ${MONTH_SHORT[d.getMonth()]} ${d.getFullYear()}`;
  }

  function fmtValue(d) {
    const day = String(d.getDate()).padStart(2, "0");
    const month = String(d.getMonth() + 1).padStart(2, "0");
    return `${day}-${month}-${d.getFullYear()}`;
  }

  function updateCalendarInputs() {
    checkinInput.value = rangeStart ? fmtDisplay(rangeStart) : "";
    checkoutInput.value = rangeEnd ? fmtDisplay(rangeEnd) : "";
    checkinInput.dataset.value = rangeStart ? fmtValue(rangeStart) : "";
    checkoutInput.dataset.value = rangeEnd ? fmtValue(rangeEnd) : "";
    if (rangeStart && rangeEnd) {
      const diff = Math.round((rangeEnd - rangeStart) / (1000 * 60 * 60 * 24));
      nights = diff > 0 ? diff : 0;
    } else {
      nights = 0;
    }
    updateNightsDisplay();
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
        const unavailable = window.UNAVAILABLE_DATES || [];
        let hasUnavailable = false;
        const check = new Date(rangeStart);
        check.setDate(check.getDate() + 1);
        while (check <= date) {
          const str = `${check.getFullYear()}-${String(check.getMonth() + 1).padStart(2, "0")}-${String(check.getDate()).padStart(2, "0")}`;
          if (unavailable.includes(str)) { hasUnavailable = true; break; }
          check.setDate(check.getDate() + 1);
        }
        if (hasUnavailable) {
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
    }
    updateCalendarInputs();
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
    calendarDropdown.querySelectorAll(".calendar-day:not(.empty):not(.disabled)").forEach((el) => {
      const [y, m, d] = el.dataset.date.split("-").map(Number);
      const date = new Date(y, m - 1, d);
      const cls = ["calendar-day"];
      if (el.classList.contains("today")) cls.push("today");
      const pStart = phase === "selecting" && rangeStart && hoverDate &&
        !isSameDay(hoverDate, rangeStart) && hoverDate > rangeStart ? rangeStart : null;
      const pEnd = pStart ? hoverDate : null;
      if (phase === "idle" && rangeStart && rangeEnd) {
        const s = rangeStart < rangeEnd ? rangeStart : rangeEnd;
        const e = rangeStart < rangeEnd ? rangeEnd : rangeStart;
        if (isSameDay(date, s)) cls.push("range-start");
        else if (isSameDay(date, e)) cls.push("range-end");
        else if (date > s && date < e) cls.push("in-range");
      } else if (phase === "selecting") {
        if (isSameDay(date, rangeStart)) {
          cls.push(pStart && isSameDay(date, pStart) ? "range-start" : "selected");
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
    container.querySelector(".calendar-month-name").textContent = `${MONTH_NAMES[month]} ${year}`;
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
      const unavailable = window.UNAVAILABLE_DATES || [];
      const dateStr = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;

      if (date < today || date > maxDate || unavailable.includes(dateStr)) {
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
    const atMin = leftYear === today.getFullYear() && leftMonth === today.getMonth();
    if (lArr) lArr.style.visibility = atMin ? "hidden" : "visible";
    const right = addMonths(leftYear, leftMonth, 1);
    const atMax = right.year === maxDate.getFullYear() && right.month === maxDate.getMonth();
    if (rArr) rArr.style.visibility = atMax ? "hidden" : "visible";
  }

  function attachCalendarArrows() {
    const containers = calendarDropdown.querySelectorAll(".calendar-month");
    containers[0].querySelector(".ph-caret-left")?.addEventListener("click", () => {
      const prev = addMonths(leftYear, leftMonth, -1);
      if (prev.year < today.getFullYear() ||
        (prev.year === today.getFullYear() && prev.month < today.getMonth())) return;
      leftYear = prev.year;
      leftMonth = prev.month;
      renderCalendar();
    });
    containers[1].querySelector(".ph-caret-right")?.addEventListener("click", () => {
      const right = addMonths(leftYear, leftMonth, 1);
      const newRight = addMonths(leftYear, leftMonth, 2);
      if (newRight.year > maxDate.getFullYear() ||
        (newRight.year === maxDate.getFullYear() && newRight.month > maxDate.getMonth())) return;
      leftYear = right.year;
      leftMonth = right.month;
      renderCalendar();
    });
  }

  function loadBookingCalendar() {
    calendarDropdown.innerHTML = `
      <div class="calendar-card">
        <div class="calendar-months-container">
          <div class="calendar-month">
            <div class="calendar-header">
              <i class="ph-bold ph-caret-left"></i>
              <span class="calendar-month-name"></span>
              <div class="empty-div"></div>
            </div>
            <div class="weekday-row">
              <div class="weekday-name">S</div><div class="weekday-name">S</div>
              <div class="weekday-name">S</div><div class="weekday-name">R</div>
              <div class="weekday-name">K</div><div class="weekday-name">J</div>
              <div class="weekday-name">S</div>
            </div>
            <div class="calendar-body"></div>
          </div>
          <div class="calendar-month">
            <div class="calendar-header">
              <div class="empty-div"></div>
              <span class="calendar-month-name"></span>
              <i class="ph-bold ph-caret-right"></i>
            </div>
            <div class="weekday-row">
              <div class="weekday-name">S</div><div class="weekday-name">S</div>
              <div class="weekday-name">S</div><div class="weekday-name">R</div>
              <div class="weekday-name">K</div><div class="weekday-name">J</div>
              <div class="weekday-name">S</div>
            </div>
            <div class="calendar-body"></div>
          </div>
        </div>
      </div>`;
    calendarLoaded = true;
    attachCalendarArrows();
    renderCalendar();
  }

  // ── FIXED: checkin & checkout listeners ───────────────────────────────────

  function openCalendarDropdown() {
    if (!calendarLoaded) return;
    guestDropdown?.classList.remove("open");
    openDropdown(calendarDropdown, dateInputField);
    renderCalendar();
  }

  calendarDropdown?.addEventListener("click", (e) => e.stopPropagation());

  loadBookingCalendar();

  const guestInput = document.getElementById("guestInput");
  const guestDropdown = document.getElementById("bookingGuestDropdown");
  const guestField = document.querySelector(".booking-field:has(#guestInput)");

  let guestLoaded = false;
  let calendarOpenedByClick = false;

  checkinInput?.addEventListener("click", (e) => {
    e.stopPropagation();
    calendarOpenedByClick = true;

    if (calendarDropdown.classList.contains("open") && phase === "idle" && !rangeStart) {
      calendarDropdown.classList.remove("open");
      activeAnchor = null;
      return;
    }

    if (rangeStart && rangeEnd) {
      rangeStart = null;
      rangeEnd = null;
      hoverDate = null;
      phase = "idle";
      updateCalendarInputs();
    }

    openCalendarDropdown();
  });

  checkoutInput?.addEventListener("click", (e) => {
    e.stopPropagation();
    calendarOpenedByClick = true;

    if (rangeStart && !rangeEnd) {
      phase = "selecting";
    }
    
    if (rangeStart && rangeEnd) {
      rangeEnd = null;
      hoverDate = null;
      phase = "selecting";
      updateCalendarInputs();
    }

    openCalendarDropdown();
  });

  checkinInput?.addEventListener("focus", (e) => {
    if (calendarOpenedByClick) { calendarOpenedByClick = false; return; }
    if (!calendarDropdown.classList.contains("open")) {
      openCalendarDropdown();
    }
  });

  checkoutInput?.addEventListener("focus", (e) => {
    if (calendarOpenedByClick) { calendarOpenedByClick = false; return; }
    if (!calendarDropdown.classList.contains("open")) {
      if (rangeStart && !rangeEnd) phase = "selecting";
      openCalendarDropdown();
    }
  });

  document.addEventListener("click", () => { calendarDropdown?.classList.remove("open"); });
  window.addEventListener("resize", () => { calendarDropdown?.classList.remove("open"); });
  function updateGuestInput() {
    const getValue = (group) => {
      const row = guestDropdown.querySelector(`[data-group="${group}"]`);
      return row ? parseInt(row.querySelector(".counter-value").textContent) : 0;
    };
    const dewasa = getValue("dewasa");
    const anak = getValue("anak");
    const bayi = getValue("bayi");
    const hewan = getValue("hewan");
    const parts = [];
    if (dewasa > 0) parts.push(`${dewasa} Pengunjung`);
    if (anak > 0) parts.push(`${anak} Anak`);
    if (bayi > 0) parts.push(`${bayi} Bayi`);
    if (hewan > 0) parts.push(`${hewan} Peliharaan`);
    guestInput.value = parts.length > 0 ? parts.join(", ") : "1 Pengunjung";
  }

  // ── ADDED: sync max tamu ke guest counter saat kamar dipilih/clear ────────
  function syncGuestToRoomMax(maxTamu) {
    const dewasaRow = guestDropdown?.querySelector('[data-group="dewasa"]');
    const anakRow = guestDropdown?.querySelector('[data-group="anak"]');
    if (!dewasaRow || !anakRow) return;

    // Update batas max di dataset
    dewasaRow.dataset.max = maxTamu;
    anakRow.dataset.max = maxTamu;

    // Kalau nilai sekarang melebihi max baru, kurangi
    const dewasaVal = dewasaRow.querySelector(".counter-value");
    const anakVal = anakRow.querySelector(".counter-value");
    const currentDewasa = parseInt(dewasaVal.textContent) || 1;
    const currentAnak = parseInt(anakVal.textContent) || 0;
    const total = currentDewasa + currentAnak;

    if (total > maxTamu) {
      // Kurangi anak dulu, baru dewasa kalau masih kurang
      let lebih = total - maxTamu;
      const newAnak = Math.max(0, currentAnak - lebih);
      lebih -= (currentAnak - newAnak);
      const newDewasa = Math.max(1, currentDewasa - lebih);
      anakVal.textContent = newAnak;
      dewasaVal.textContent = newDewasa;
      updateGuestInput();
    }

    // Re-init counter supaya tombol plus/minus ikut batas baru
    initGuestCounter();
  }

  function initGuestCounter() {
    const counterItems = guestDropdown.querySelectorAll(".counter-row");
    const dewasaRow = guestDropdown.querySelector('[data-group="dewasa"]');
    const anakRow = guestDropdown.querySelector('[data-group="anak"]');

    counterItems.forEach((item) => {
      // Clone node untuk hapus listener lama sebelum re-init
      const newItem = item.cloneNode(true);
      item.parentNode.replaceChild(newItem, item);
    });

    // Query ulang setelah clone
    const freshItems = guestDropdown.querySelectorAll(".counter-row");
    const freshDewasa = guestDropdown.querySelector('[data-group="dewasa"]');
    const freshAnak = guestDropdown.querySelector('[data-group="anak"]');

    freshItems.forEach((item) => {
      const minusBtn = item.querySelector(".minus");
      const plusBtn = item.querySelector(".plus");
      const counterValue = item.querySelector(".counter-value");
      const min = parseInt(item.dataset.min) || 0;
      const max = parseInt(item.dataset.max) || 10;
      let value = parseInt(counterValue.textContent);

      const activeMax = () => parseInt(item.dataset.max) || 10;

      function getTotal() {
        return parseInt(freshDewasa.querySelector(".counter-value").textContent) +
          parseInt(freshAnak.querySelector(".counter-value").textContent);
      }

      function updateSiblings() {
        freshItems.forEach((other) => {
          if (other !== item && (other.dataset.group === "dewasa" || other.dataset.group === "anak")) {
            const ov = parseInt(other.querySelector(".counter-value").textContent);
            const om = parseInt(other.dataset.max) || 10;
            other.querySelector(".plus").classList.toggle(
              "disabled", ov >= om || getTotal() >= (window.ACTIVE_MAX_TAMU || window.MAX_TAMU || 16)
            );
          }
        });
      }

      function updateCounter() {
        counterValue.textContent = value;
        minusBtn.classList.toggle("disabled", value <= min);
        const isOrang = item.dataset.group === "dewasa" || item.dataset.group === "anak";
        plusBtn.classList.toggle("disabled",
          isOrang
            ? value >= activeMax() || getTotal() >= (window.ACTIVE_MAX_TAMU || window.MAX_TAMU || 16)
            : value >= activeMax()
        );
      }

      plusBtn.addEventListener("click", () => {
        const isOrang = item.dataset.group === "dewasa" || item.dataset.group === "anak";
        const limit = window.ACTIVE_MAX_TAMU || window.MAX_TAMU || 16;
        if (isOrang ? value < activeMax() && getTotal() < limit : value < activeMax()) {
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
          if (item.dataset.group === "dewasa" || item.dataset.group === "anak") updateSiblings();
          updateGuestInput();
        }
      });

      updateCounter();
    });
  }

  function loadBookingGuestEager() {
    const maxTamu = window.MAX_TAMU || 16;
    const bolehHewan = window.BOLEH_HEWAN !== false;
    const hewanDisabledAttr = !bolehHewan ? 'data-disabled="true"' : '';
    const hewanDisabledStyle = !bolehHewan
      ? 'style="opacity:0.4;pointer-events:none;" title="Hewan peliharaan tidak diperbolehkan"'
      : '';
    guestDropdown.innerHTML = `
      <div class="counter-list">
        <div class="counter-row" data-min="1" data-max="${maxTamu}" data-group="dewasa">
          <div class="counter-info">
            <h3 class="counter-label">Orang Dewasa</h3>
            <h4 class="counter-desc">17 Tahun ke Atas</h4>
          </div>
          <div class="counter-control">
            <button type="button" class="counter-button minus">−</button>
            <span class="counter-value">1</span>
            <button type="button" class="counter-button plus">+</button>
          </div>
        </div>
        <div class="counter-row" data-min="0" data-max="${maxTamu}" data-group="anak">
          <div class="counter-info">
            <h3 class="counter-label">Anak-anak</h3>
            <h4 class="counter-desc">2–17 Tahun</h4>
          </div>
          <div class="counter-control">
            <button type="button" class="counter-button minus">−</button>
            <span class="counter-value">0</span>
            <button type="button" class="counter-button plus">+</button>
          </div>
        </div>
        <div class="counter-row" data-min="0" data-max="5" data-group="bayi">
          <div class="counter-info">
            <h3 class="counter-label">Bayi</h3>
            <h4 class="counter-desc">2 Tahun ke Bawah</h4>
          </div>
          <div class="counter-control">
            <button type="button" class="counter-button minus">−</button>
            <span class="counter-value">0</span>
            <button type="button" class="counter-button plus">+</button>
          </div>
        </div>
        <div class="counter-row" data-min="0" data-max="${bolehHewan ? 5 : 0}" data-group="hewan" ${hewanDisabledAttr} ${hewanDisabledStyle}>
          <div class="counter-info">
            <h3 class="counter-label">Hewan Peliharaan</h3>
            ${!bolehHewan ? '<h4 class="counter-desc" style="color:#c0392b;">Tidak diperbolehkan</h4>' : ''}
          </div>
          <div class="counter-control">
            <button type="button" class="counter-button minus">−</button>
            <span class="counter-value">0</span>
            <button type="button" class="counter-button plus">+</button>
          </div>
        </div>
      </div>`;
    guestLoaded = true;
    window.ACTIVE_MAX_TAMU = maxTamu; // init ke max listing
    initGuestCounter();
  }

  loadBookingGuestEager();

  function toggleGuestDropdown() {
    calendarDropdown?.classList.remove("open");
    if (guestDropdown.classList.contains("open")) {
      guestDropdown.classList.remove("open");
      activeAnchor = null;
    } else {
      openDropdown(guestDropdown, guestField);
    }
  }

  guestInput?.addEventListener("click", (e) => { e.stopPropagation(); if (guestLoaded) toggleGuestDropdown(); });
  guestInput?.addEventListener("focus", (e) => {
    e.stopPropagation();
    if (guestLoaded && !guestDropdown.classList.contains("open")) toggleGuestDropdown();
  });
  guestInput?.addEventListener("keydown", (e) => { e.preventDefault(); });

  document.addEventListener("click", () => { guestDropdown?.classList.remove("open"); });
  window.addEventListener("resize", () => { guestDropdown?.classList.remove("open"); });
  guestDropdown?.addEventListener("click", (e) => e.stopPropagation());

  // ── Room selection ────────────────────────────────────────────────────────
  // State dikelola di luar forEach supaya semua button share satu selectedRoomId
  let selectedRoomId = null;

  const priceEl = document.getElementById("bookingPriceAmount");
  const selectedRoomEl = document.getElementById("bookingSelectedRoom");
  const selectedRoomNameEl = document.getElementById("bookingSelectedRoomName");
  const clearRoomBtn = document.getElementById("clearRoomBtn");
  const roomIdInput = document.getElementById("selectedRoomIdInput");
  const noRoomWarning = document.getElementById("bookingNoRoomWarning");
  const BASE_PRICE = window.BASE_LISTING_PRICE || 0;

  function fmtRupiah(num) {
    return "Rp " + parseInt(num).toLocaleString("id-ID");
  }

  function clearSelection() {
    selectedRoomId = null;
    if (roomIdInput) roomIdInput.value = "";
    document.querySelectorAll(".room-card").forEach((c) => c.classList.remove("selected"));
    document.querySelectorAll(".room-book-btn").forEach((b) => {
      b.classList.remove("is-selected");
      b.textContent = "Pilih Kamar";
    });
    if (priceEl) priceEl.textContent = fmtRupiah(BASE_PRICE);
    if (selectedRoomEl) selectedRoomEl.classList.remove("visible");
    if (selectedRoomNameEl) selectedRoomNameEl.textContent = "";
    if (noRoomWarning) noRoomWarning.classList.remove("visible");

    // ADDED: kembalikan max tamu ke batas listing saat kamar di-clear
    window.ACTIVE_MAX_TAMU = window.MAX_TAMU || 16;
    syncGuestToRoomMax(window.ACTIVE_MAX_TAMU);
  }

  function selectRoom(roomId, roomName, roomPrice, roomMaxTamu) {
    selectedRoomId = roomId;
    if (roomIdInput) roomIdInput.value = roomId;
    document.querySelectorAll(".room-card").forEach((c) => {
      c.classList.toggle("selected", c.dataset.roomId === roomId);
    });
    document.querySelectorAll(".room-book-btn").forEach((b) => {
      const isThis = b.dataset.roomId === roomId;
      b.classList.toggle("is-selected", isThis);
      b.textContent = isThis ? "✓ Dipilih" : "Pilih Kamar";
    });
    if (priceEl) priceEl.textContent = fmtRupiah(roomPrice);
    if (selectedRoomNameEl) selectedRoomNameEl.textContent = roomName;
    if (selectedRoomEl) selectedRoomEl.classList.add("visible");
    if (noRoomWarning) noRoomWarning.classList.remove("visible");

    // ADDED: update max tamu sesuai kamar yang dipilih
    const effectiveMax = roomMaxTamu && roomMaxTamu > 0
      ? Math.min(roomMaxTamu, window.MAX_TAMU || 16)
      : (window.MAX_TAMU || 16);
    window.ACTIVE_MAX_TAMU = effectiveMax;
    syncGuestToRoomMax(effectiveMax);

    document.querySelector(".booking-card")?.scrollIntoView({ behavior: "smooth", block: "nearest" });
  }

  document.querySelectorAll(".room-book-btn").forEach((btn) => {
    if (!isLoggedIn) {
      btn.disabled = true;
      btn.title = "Masuk untuk memilih kamar";
      btn.style.opacity = "0.5";
      btn.style.cursor = "not-allowed";
      return;
    }

    btn.addEventListener("click", () => {
      const roomId = btn.dataset.roomId;
      const roomName = btn.dataset.roomName;
      const roomPrice = btn.dataset.roomPrice;
      const roomMaxTamu = parseInt(btn.dataset.roomMaxTamu) || 0; // ADDED: baca dari data attribute
      if (selectedRoomId === roomId) { clearSelection(); return; }
      selectRoom(roomId, roomName, roomPrice, roomMaxTamu);
    });
  });

  clearRoomBtn?.addEventListener("click", clearSelection);

  // ── Booking submit ────────────────────────────────────────────────────────
  const submitBtn = document.getElementById("bookingSubmitBtn");
  submitBtn?.addEventListener("click", () => {
    if (!isLoggedIn) {
      if (noRoomWarning) {
        noRoomWarning.textContent = "Silahkan daftar atau masuk terlebih dahulu untuk memesan.";
        noRoomWarning.classList.add("visible");
      }
      openAuthPopup();
      return;
    }

    const HAS_ROOMS = document.querySelectorAll(".room-book-btn").length > 0;
    const currentRoomId = roomIdInput?.value || "";

    if (HAS_ROOMS && !currentRoomId) {
      if (noRoomWarning) {
        noRoomWarning.classList.add("visible");
        document.querySelector(".rooms-section")?.scrollIntoView({ behavior: "smooth", block: "start" });
      }
      return;
    }

    const checkin = document.getElementById("checkinInput")?.dataset.value || "";
    const checkout = document.getElementById("checkoutInput")?.dataset.value || "";
    const promo = document.getElementById("promoInput")?.value || "";
    const guest = document.getElementById("guestInput")?.value || "";
    const BASE_LISTING_ID = window.BASE_LISTING_ID || 0;

    let url = `./payment_confirm.php?listing_id=${BASE_LISTING_ID}`;
    if (currentRoomId) url += `&room_id=${encodeURIComponent(currentRoomId)}`;
    if (checkin) url += `&checkin=${encodeURIComponent(checkin)}`;
    if (checkout) url += `&checkout=${encodeURIComponent(checkout)}`;
    if (guest) url += `&jumlah_tamu=${encodeURIComponent(guest)}`;
    if (promo) url += `&promo=${encodeURIComponent(promo)}`;
    window.location.href = url;
  });
});