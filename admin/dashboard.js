const months = [
  "Jan",
  "Feb",
  "Mar",
  "Apr",
  "Mei",
  "Jun",
  "Jul",
  "Agu",
  "Sep",
  "Okt",
  "Nov",
  "Des",
];

const revData = [
  38000000, 34000000, 44000000, 52000000, 61000000, 75000000, 88000000,
  95000000, 79000000, 58000000, 51000000, 70000000,
];

const resData = [
  28000000, 25000000, 33000000, 40000000, 48000000, 60000000, 71000000,
  78000000, 64000000, 45000000, 38000000, 55000000,
];

const fmtIDR = (v) =>
  new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(v);

const fmtAxis = (v) => {
  if (v === 0) return "Rp 0";
  return v >= 1_000_000
    ? `Rp ${(v / 1_000_000).toFixed(0)}Jt`
    : `Rp ${(v / 1_000).toFixed(0)}K`;
};

const revenueTrendCanvas = document.getElementById("revenueTrendChart");

if (revenueTrendCanvas && typeof Chart !== "undefined") {
  function createGradients(ctx, chartHeight) {
    const blueGrad = ctx.createLinearGradient(0, 0, 0, chartHeight);
    blueGrad.addColorStop(0, "rgba(55,138,221,0.22)");
    blueGrad.addColorStop(1, "rgba(55,138,221,0.01)");

    const pinkGrad = ctx.createLinearGradient(0, 0, 0, chartHeight);
    pinkGrad.addColorStop(0, "rgba(224,91,122,0.18)");
    pinkGrad.addColorStop(1, "rgba(224,91,122,0.01)");

    return { blueGrad, pinkGrad };
  }

  const RADIUS_TARGET = 6.5;
  const STIFFNESS = 0.18;
  const DAMPING = 0.72;

  const dots = [
    { color: "#378ADD", x: 0, y: 0, r: 0, tr: 0 },
    { color: "#E05B7A", x: 0, y: 0, r: 0, tr: 0 },
  ];
  const dotVelocities = [0, 0];
  let rafId = null;

  function springStep(chart) {
    let stillMoving = false;
    dots.forEach((d, i) => {
      const force = (d.tr - d.r) * STIFFNESS;
      dotVelocities[i] = (dotVelocities[i] + force) * DAMPING;
      d.r += dotVelocities[i];
      if (Math.abs(dotVelocities[i]) > 0.01 || Math.abs(d.tr - d.r) > 0.01) {
        stillMoving = true;
      }
    });
    chart.render();
    if (stillMoving) {
      rafId = requestAnimationFrame(() => springStep(chart));
    } else {
      rafId = null;
    }
  }

  function startSpring(chart) {
    if (!rafId) rafId = requestAnimationFrame(() => springStep(chart));
  }

  const springDotPlugin = {
    id: "springDot",
    afterDraw(chart) {
      const { ctx: c } = chart;
      dots.forEach((d) => {
        if (d.r < 0.2) return;
        c.save();
        c.beginPath();
        c.arc(d.x, d.y, d.r, 0, Math.PI * 2);
        c.fillStyle = d.color;
        c.fill();
        c.lineWidth = 2.5;
        c.strokeStyle = "#ffffff";
        c.stroke();
        c.restore();
      });
    },
  };

  Chart.register(springDotPlugin);

  const ctx = revenueTrendCanvas.getContext("2d");
  const { blueGrad, pinkGrad } = createGradients(ctx, 300);

  const chart = new Chart(revenueTrendCanvas, {
    type: "line",
    data: {
      labels: months,
      datasets: [
        {
          label: "Pendapatan",
          data: revData,
          borderColor: "#378ADD",
          backgroundColor: blueGrad,
          fill: true,
          tension: 0.42,
          borderWidth: 2.5,
          pointRadius: 0,
          pointHoverRadius: 0,
          yAxisID: "y",
        },
        {
          label: "Reservasi",
          data: resData,
          borderColor: "#E05B7A",
          backgroundColor: pinkGrad,
          fill: true,
          tension: 0.42,
          borderWidth: 2.5,
          pointRadius: 0,
          pointHoverRadius: 0,
          yAxisID: "y",
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      animation: { duration: 900, easing: "easeInOutQuart" },
      interaction: { mode: "index", intersect: false },
      plugins: {
        legend: {
          display: true,
          position: "top",
          align: "end",
          labels: {
            usePointStyle: true,
            pointStyle: "circle",
            boxWidth: 6,
            boxHeight: 6,
            padding: 20,
            font: { size: 13 },
            color: "#6b7280",
          },
        },
        tooltip: { enabled: false },
      },
      scales: {
        x: {
          display: true,
          grid: { display: false },
          border: { display: false },
          ticks: { font: { size: 11 }, color: "#9ca3af" },
        },
        y: {
          position: "left",
          grid: { color: "rgba(128,128,128,0.08)" },
          border: { display: false },
          ticks: {
            font: { size: 11 },
            color: "#9ca3af",
            maxTicksLimit: 5,
            callback: fmtAxis,
          },
        },
      },
    },
  });

  const tooltip = document.getElementById("revTooltip");
  const ttTitle = document.getElementById("revTt-title");
  const ttVal1 = document.getElementById("revTt-val1");
  const ttVal2 = document.getElementById("revTt-val2");
  const wrap = document.getElementById("chartWrap");

  if (tooltip && ttTitle && ttVal1 && ttVal2 && wrap) {
    let hideTimer = null;
    let isVisible = false;

    function showTooltip(i) {
      clearTimeout(hideTimer);
      [0, 1].forEach((di) => {
        const meta = chart.getDatasetMeta(di);
        const point = meta.data[i];
        dots[di].x = point.x;
        dots[di].y = point.y;
        dots[di].tr = RADIUS_TARGET;
      });
      startSpring(chart);

      const canvasRect = revenueTrendCanvas.getBoundingClientRect();
      const wrapRect = wrap.getBoundingClientRect();
      const relX = canvasRect.left - wrapRect.left + dots[0].x;
      const relY = canvasRect.top - wrapRect.top + dots[0].y;

      ttTitle.textContent = months[i] + " 2024";
      ttVal1.textContent = fmtIDR(revData[i]);
      ttVal2.textContent = fmtIDR(resData[i]);

      if (!isVisible) {
        tooltip.style.transition = "opacity 0.22s ease";
        tooltip.classList.add("visible");
        isVisible = true;
      } else {
        tooltip.style.transition =
          "opacity 0.22s ease, left 0.18s cubic-bezier(0.25,0.46,0.45,0.94), top 0.18s cubic-bezier(0.25,0.46,0.45,0.94)";
      }

      const tw = tooltip.offsetWidth || 215;
      const th = tooltip.offsetHeight || 82;
      let left = relX - tw / 2;
      let top = relY - th - 16;

      if (left < 4) left = 4;
      if (left + tw > wrapRect.width - 4) left = wrapRect.width - tw - 4;
      if (top < 4) top = relY + 20;

      tooltip.style.left = `${Math.round(left)}px`;
      tooltip.style.top = `${Math.round(top)}px`;
    }

    function scheduleHide() {
      dots.forEach((d) => {
        d.tr = 0;
      });
      startSpring(chart);
      hideTimer = setTimeout(() => {
        tooltip.style.transition = "opacity 0.3s ease";
        tooltip.classList.remove("visible");
        isVisible = false;
      }, 120);
    }

    revenueTrendCanvas.addEventListener("mousemove", (e) => {
      const pts = chart.getElementsAtEventForMode(
        e,
        "index",
        { intersect: false },
        true,
      );
      if (!pts.length) {
        scheduleHide();
        return;
      }
      showTooltip(pts[0].index);
    });

    revenueTrendCanvas.addEventListener("mouseleave", scheduleHide);
  }
}

const donutCanvas = document.getElementById("donutChart");

if (donutCanvas && typeof Chart !== "undefined") {
  const donutData = {
    labels: ["Available", "Sold Out"],
    values: [66, 129],
    colors: ["#4ade80", "#fde68a"],
  };

  const donutTotal = donutData.values.reduce((a, b) => a + b, 0);
  const donutCtx = donutCanvas.getContext("2d");

  const donutChart = new Chart(donutCtx, {
    type: "doughnut",
    data: {
      labels: donutData.labels,
      datasets: [
        {
          data: donutData.values,
          backgroundColor: donutData.colors,
          borderColor: "transparent",
          borderWidth: 0,
          hoverOffset: 6,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      cutout: "60%",
      animation: { duration: 900, easing: "easeInOutQuart" },
      plugins: {
        legend: { display: false },
        tooltip: { enabled: false },
      },
    },
  });

  const legendAvailable = document.getElementById("legend-available");
  const legendSoldout = document.getElementById("legend-soldout");
  if (legendAvailable)
    legendAvailable.textContent = `${donutData.values[0]} Rooms`;
  if (legendSoldout) legendSoldout.textContent = `${donutData.values[1]} Rooms`;

  const dTooltip = document.getElementById("donutTooltip");
  const dttTitle = document.getElementById("dtt-title");
  const dttBox = document.getElementById("dtt-box");
  const dttLabel = document.getElementById("dtt-label");
  const dttVal = document.getElementById("dtt-val");
  const dWrap = document.getElementById("doughnutChartBox");

  if (dTooltip && dttTitle && dttBox && dttLabel && dttVal && dWrap) {
    let dHideTimer = null;
    let dVisible = false;

    function showDonutTooltip(e) {
      const pts = donutChart.getElementsAtEventForMode(
        e,
        "nearest",
        { intersect: true },
        true,
      );
      if (!pts.length) {
        hideDonutTooltip();
        return;
      }
      clearTimeout(dHideTimer);

      const idx = pts[0].index;
      const value = donutData.values[idx];
      const color = donutData.colors[idx];
      const pct = ((value / donutTotal) * 100).toFixed(1);

      dttTitle.textContent = donutData.labels[idx];
      dttBox.style.background = color;
      dttLabel.textContent = `${value} Rooms`;
      dttVal.textContent = `${pct}%`;

      const wrapRect = dWrap.getBoundingClientRect();
      const mouseX = e.clientX - wrapRect.left;
      const mouseY = e.clientY - wrapRect.top;

      if (!dVisible) {
        dTooltip.style.transition = "opacity 0.22s ease";
        dTooltip.classList.add("visible");
        dVisible = true;
      } else {
        dTooltip.style.transition =
          "opacity 0.22s ease, left 0.18s cubic-bezier(0.25,0.46,0.45,0.94), top 0.18s cubic-bezier(0.25,0.46,0.45,0.94)";
      }

      const tw = dTooltip.offsetWidth || 170;
      const th = dTooltip.offsetHeight || 70;
      let left = mouseX - tw / 2;
      let top = mouseY - th - 14;

      if (left < 4) left = 4;
      if (left + tw > wrapRect.width - 4) left = wrapRect.width - tw - 4;
      if (top < 4) top = mouseY + 14;

      dTooltip.style.left = `${Math.round(left)}px`;
      dTooltip.style.top = `${Math.round(top)}px`;
    }

    function hideDonutTooltip() {
      dHideTimer = setTimeout(() => {
        dTooltip.style.transition = "opacity 0.3s ease";
        dTooltip.classList.remove("visible");
        dVisible = false;
      }, 120);
    }

    donutCanvas.addEventListener("mousemove", showDonutTooltip);
    donutCanvas.addEventListener("mouseleave", hideDonutTooltip);
  }
}

(function () {
  const canvas = document.getElementById("visitorsBarChart");
  if (!canvas || typeof Chart === "undefined") return;

  const VISITORS_DATA = {
    monthly: {
      labels: ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun"],
      host: [6800, 7400, 5100, 6200, 7900, 8500],
      user: [4900, 6100, 6800, 5600, 6500, 7200],
      total: "12.456",
      change: "+53%",
      positive: true,
    },
    weekly: {
      labels: ["Mg 1", "Mg 2", "Mg 3", "Mg 4"],
      host: [2100, 2800, 1900, 2400],
      user: [1600, 2200, 1700, 2000],
      total: "3.120",
      change: "+21%",
      positive: true,
    },
    daily: {
      labels: ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"],
      host: [580, 620, 490, 710, 830, 540, 360],
      user: [420, 510, 380, 590, 690, 460, 280],
      total: "1.500",
      change: "-4%",
      positive: false,
    },
  };

  const PRIMARY = "#8b2500";
  const ACCENT = "#c9933a";

  function fmtAxisBar(v) {
    if (v === 0) return "0";
    return v >= 1000 ? (v / 1000).toFixed(0) + "K" : v;
  }

  const totalEl = document.getElementById("visitors-total");
  const badgeEl = document.getElementById("visitors-badge");

  let visitorsChart = null;

  function renderChart(period) {
    const d = VISITORS_DATA[period];

    if (totalEl) totalEl.textContent = d.total;
    if (badgeEl) {
      badgeEl.className =
        "visitors-badge " + (d.positive ? "positive" : "negative");
      badgeEl.innerHTML = d.positive
        ? `<i class="ph-bold ph-trend-up"></i>${d.change}`
        : `<i class="ph-bold ph-trend-down"></i>${d.change}`;
    }

    if (visitorsChart) {
      visitorsChart.data.labels = d.labels;
      visitorsChart.data.datasets[0].data = d.host;
      visitorsChart.data.datasets[1].data = d.user;
      visitorsChart.update("active");
      return;
    }

    visitorsChart = new Chart(canvas, {
      type: "bar",
      data: {
        labels: d.labels,
        datasets: [
          {
            label: "Host",
            data: d.host,
            backgroundColor: PRIMARY,
            hoverBackgroundColor: PRIMARY,
            borderRadius: 5,
            borderSkipped: false,
            barPercentage: 0.68,
            categoryPercentage: 0.72,
          },
          {
            label: "User",
            data: d.user,
            backgroundColor: ACCENT,
            hoverBackgroundColor: ACCENT,
            borderRadius: 5,
            borderSkipped: false,
            barPercentage: 0.68,
            categoryPercentage: 0.72,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 600, easing: "easeInOutQuart" },
        interaction: { mode: "index", intersect: false },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: "#1e2235",
            titleColor: "#ffffff",
            bodyColor: "#ffffff",
            padding: 10,
            cornerRadius: 10,
            callbacks: {
              label: (ctx) =>
                ` ${ctx.dataset.label}: ${ctx.parsed.y.toLocaleString("id-ID")}`,
            },
          },
        },
        scales: {
          x: {
            grid: { display: false },
            border: { display: false },
            ticks: {
              font: { size: 11 },
              color: "#c0a090",
              autoSkip: false,
              maxRotation: 0,
            },
          },
          y: {
            grid: { color: "rgba(240,228,216,0.8)" },
            border: { display: false },
            ticks: {
              font: { size: 11 },
              color: "#c0a090",
              maxTicksLimit: 5,
              callback: fmtAxisBar,
            },
          },
        },
      },
    });
  }

  const tabs = document.querySelectorAll(".visitors-tab");
  tabs.forEach((tab) => {
    tab.addEventListener("click", () => {
      tabs.forEach((t) => t.classList.remove("active"));
      tab.classList.add("active");
      renderChart(tab.dataset.period);
    });
  });

  renderChart("monthly");
})();

const filterButtons = document.querySelectorAll(".filter-item");
const filterGroup = document.querySelector(".filter-group");

filterButtons.forEach((filterItem) => {
  filterItem.addEventListener("click", () => {
    filterButtons.forEach((i) => i.classList.remove("active"));
    filterItem.classList.add("active");

    applyTableFilter();
  });
});

function applyTableFilter() {
  const table = document.querySelector("table");
  if (!table) return;

  const tbody = table.querySelector("tbody");
  if (!tbody) return;

  const rows = tbody.querySelectorAll("tr");
  const activeFilter = document.querySelector(".filter-item.active");
  if (!activeFilter) return;

  const filterText = activeFilter.textContent.trim().toLowerCase();
  const isShowAll = filterText === "semua";

  const dataFilter = activeFilter.dataset.filter;

  rows.forEach((row) => {
    if (isShowAll) {
      row.style.display = "";
      return;
    }

    let matches = false;

    if (dataFilter) {

      const [columnKey, ...filterValues] = dataFilter.split(":");
      matches = checkRowMatchesFilter(row, columnKey, filterValues);
    } else {

      const pageContext = detectPageContext();
      const filterColumnIndex = pageContext.columnIndex;
      const filterKeywords = pageContext.keywords[filterText] || [];

      const cell = row.querySelectorAll("td")[filterColumnIndex];
      if (cell) {
        const cellText = cell.textContent.toLowerCase();
        const cellBadges = cell.querySelectorAll(".table-badge");

        for (const badge of cellBadges) {
          const badgeText = badge.textContent.toLowerCase();
          if (filterKeywords.some((keyword) => badgeText.includes(keyword))) {
            matches = true;
            break;
          }
        }

        if (!matches) {
          if (filterKeywords.some((keyword) => cellText.includes(keyword))) {
            matches = true;
          }
        }
      }
    }

    row.style.display = matches ? "" : "none";
  });
}

function checkRowMatchesFilter(row, columnKey, filterValues) {

  const columnMap = {
    role: 2,
    status: 3,
    account_status: 3,
    verification: 4,
    listing_status: 4,
    reservation_status: 6,
    review_status: 4,
    check_in: 3,
    rating: 2,
    report_status: 4,
    report_type: 2,
    transaction_status: 4,
    transaction_type: 2,
    payout_status: 4,
    name: 0,
    email: 1,
  };

  let columnIndex = parseInt(columnKey);
  if (isNaN(columnIndex)) {
    columnIndex = columnMap[columnKey];
  }
  if (columnIndex === undefined) return true;

  const cell = row.querySelectorAll("td")[columnIndex];
  if (!cell) return true;

  const cellText = cell.textContent.toLowerCase();
  const cellBadges = cell.querySelectorAll(".table-badge");

  for (const badge of cellBadges) {
    const badgeText = badge.textContent.toLowerCase();
    if (filterValues.some((val) => badgeText.includes(val.toLowerCase()))) {
      return true;
    }
  }

  if (filterValues.some((val) => cellText.includes(val.toLowerCase()))) {
    return true;
  }

  return false;
}

function detectPageContext() {

  const filterItems = Array.from(document.querySelectorAll(".filter-item")).map(
    (f) => f.textContent.toLowerCase(),
  );

  if (filterItems.includes("host") && filterItems.includes("tamu")) {
    return {
      columnIndex: 2,
      keywords: {
        semua: [],
        host: ["host"],
        tamu: ["tamu"],
        suspended: ["suspended"],
      },
    };
  }

  if (filterItems.includes("aktif") && filterItems.includes("nonaktif")) {
    return {
      columnIndex: 4,
      keywords: {
        semua: [],
        aktif: ["aktif"],
        nonaktif: ["nonaktif"],
        "pending review": ["pending"],
      },
    };
  }

  if (filterItems.includes("check-in") && filterItems.includes("check-out")) {
    return {
      columnIndex: 5,
      keywords: {
        semua: [],
        confirmed: ["confirmed", "terkonfirmasi"],
        completed: ["completed", "selesai"],
        cancelled: ["cancelled", "dibatalkan"],
      },
    };
  }

  if (filterItems.includes("verified")) {
    return {
      columnIndex: 3,
      keywords: {
        semua: [],
        verified: ["verified", "terverifikasi"],
        unverified: ["unverified", "belum"],
      },
    };
  }

  if (filterItems.includes("open") && filterItems.includes("resolved")) {
    return {
      columnIndex: 4,
      keywords: {
        semua: [],
        open: ["open", "dibuka"],
        resolved: ["resolved", "terselesaikan"],
        pending: ["pending"],
      },
    };
  }

  if (filterItems.includes("pending") && filterItems.includes("completed")) {
    return {
      columnIndex: 4,
      keywords: {
        semua: [],
        pending: ["pending"],
        completed: ["completed", "selesai"],
        failed: ["failed", "gagal"],
      },
    };
  }

  if (filterItems.includes("requested") && filterItems.includes("processed")) {
    return {
      columnIndex: 5,
      keywords: {
        semua: [],
        requested: ["requested", "diminta"],
        processed: ["processed", "diproses"],
        paid: ["paid", "dibayar"],
      },
    };
  }

  return {
    columnIndex: 3,
    keywords: {},
  };
}

const sortButtons = document.querySelectorAll(".sort-button");
sortButtons.forEach((sortItem) => {
  sortItem.addEventListener("click", () => {
    const isActive = sortItem.classList.toggle("active");
    const textSpan = sortItem.querySelector("span");
    const textActive = sortItem.dataset.active;
    const textInact = sortItem.dataset.inactive;
    const sortBy = sortItem.dataset.sort;

    if (textSpan && textActive && textInact) {
      textSpan.textContent = isActive ? textActive : textInact;
    }

    if (sortBy) {
      sortTableByColumn(sortBy, isActive);
    }
  });
});

function parseValue(text, sortBy) {

  const cleaned = text.replace(/[Rp\s.]/g, "").replace(/,/g, "");
  const asNum = parseFloat(cleaned);

  const BULAN = {
    Jan: 0,
    Feb: 1,
    Mar: 2,
    Apr: 3,
    Mei: 4,
    Jun: 5,
    Jul: 6,
    Agu: 7,
    Sep: 8,
    Okt: 9,
    Nov: 10,
    Des: 11,
  };
  const dateMatch = text.match(/(\d{1,2})\s(\w+)\s(\d{4})/);
  if (dateMatch) {
    const [, tgl, bln, thn] = dateMatch;
    const bulanIdx = BULAN[bln];
    if (bulanIdx !== undefined) {
      return new Date(+thn, bulanIdx, +tgl).getTime();
    }
  }

  if (sortBy === "review") return NaN;

  if (!isNaN(asNum)) return asNum;

  return text.trim().toLowerCase();
}

function sortTableByColumn(sortBy, isDescending) {
  const table = document.querySelector("table");
  if (!table) return;

  const tbody = table.querySelector("tbody");
  if (!tbody) return;

  const rows = Array.from(tbody.querySelectorAll("tr"));

  const columnMap = {
    user: 0,
    listing: 0,
    reservation: 3,
    review: 2,
    report: 5,
    transaction: 3,
    payout: 1,
    date: 5,
    status: 3,
  };

  const columnIndex = columnMap[sortBy] ?? 0;

  rows.sort((a, b) => {
    const cellA = a.querySelectorAll("td")[columnIndex];
    const cellB = b.querySelectorAll("td")[columnIndex];
    if (!cellA || !cellB) return 0;

    if (sortBy === "review") {
      const countA = cellA.querySelectorAll(
        ".ph-fill.ph-star, .ph-fill.ph-star-half",
      ).length;
      const countB = cellB.querySelectorAll(
        ".ph-fill.ph-star, .ph-fill.ph-star-half",
      ).length;
      return isDescending ? countB - countA : countA - countB;
    }

    const nameElemA = cellA.querySelector(".table-name, .listing-name");
    const nameElemB = cellB.querySelector(".table-name, .listing-name");
    const rawA = (nameElemA ?? cellA).textContent.trim();
    const rawB = (nameElemB ?? cellB).textContent.trim();

    const valA = parseValue(rawA, sortBy);
    const valB = parseValue(rawB, sortBy);

    if (typeof valA === "number" && typeof valB === "number") {
      if (isNaN(valA) && isNaN(valB)) return 0;
      if (isNaN(valA)) return 1;
      if (isNaN(valB)) return -1;
      return isDescending ? valB - valA : valA - valB;
    }

    const cmp = String(valA).localeCompare(String(valB), "id-ID");
    return isDescending ? -cmp : cmp;
  });

  rows.forEach((row) => tbody.appendChild(row));
}

const toggleSwitches = document.querySelectorAll(".toggle-switch");

toggleSwitches.forEach((toggle) => {
  toggle.addEventListener("click", () => {
    toggle.classList.toggle("active");
  });
});

const tabItems = document.querySelectorAll(".tab-item");
const tabIndicator = document.querySelector(".tab-indicator");

if (tabItems.length && tabIndicator) {
  function moveIndicator(tab) {
    tabIndicator.style.left = `${tab.offsetLeft}px`;
    tabIndicator.style.width = `${tab.offsetWidth}px`;
  }

  tabItems.forEach((tab) => {
    tab.addEventListener("click", () => {
      tabItems.forEach((t) => t.classList.remove("active"));
      tab.classList.add("active");
      moveIndicator(tab);

      document
        .querySelectorAll(".table-section")
        .forEach((c) => (c.style.display = "none"));

      const target = document.querySelector(tab.dataset.target);
      if (target) target.style.display = "block";
    });
  });

  const activeTab = document.querySelector(".tab-item.active");
  if (activeTab) moveIndicator(activeTab);
}

/* ═══════════════════════════════════════════════════════
   ADMIN TABLE MANAGER – search + numbering + pagination
   Works alongside existing filter & sort logic.
   ═══════════════════════════════════════════════════════ */
(function () {
  const ROWS_PER_PAGE = 10;

  // Only run on pages that have a table-toolbar (added via HTML)
  const toolbar = document.querySelector('.table-toolbar');
  if (!toolbar) return;

  const searchInput = document.getElementById('adminSearch');
  const resultCount = document.getElementById('searchResultCount');

  // Support multiple tables (e.g. logs page with tabs)
  // We manage ALL tables present
  function getAllTables() {
    return Array.from(document.querySelectorAll('.managed-table'));
  }

  // ── Numbering ──────────────────────────────────────────
  function refreshNumbers(table) {
    const visibleRows = Array.from(table.querySelectorAll('tbody tr'))
      .filter(r => r.style.display !== 'none');
    visibleRows.forEach((row, i) => {
      const numCell = row.querySelector('.col-num');
      if (numCell) numCell.textContent = i + 1;
    });
  }

  // ── Pagination ────────────────────────────────────────
  function getPaginationEl(table) {
    // Each managed table has a sibling .table-pagination
    return table.closest('.table-section')?.querySelector('.table-pagination');
  }

  function getState(table) {
    if (!table._adminPage) table._adminPage = 1;
    return table._adminPage;
  }

  function setState(table, page) {
    table._adminPage = page;
  }

  function getVisibleRows(table) {
    return Array.from(table.querySelectorAll('tbody tr'))
      .filter(r => r.dataset.hidden !== 'filter'); // respect filter hide
  }

  function applyPagination(table) {
    const page = getState(table);
    const allRows = Array.from(table.querySelectorAll('tbody tr'));
    // rows hidden by search or filter have data-hidden set
    const visibleRows = allRows.filter(r => !r.dataset.hidden);
    const total = visibleRows.length;
    const totalPages = Math.max(1, Math.ceil(total / ROWS_PER_PAGE));
    const safePage = Math.min(page, totalPages);
    if (safePage !== page) setState(table, safePage);

    const start = (safePage - 1) * ROWS_PER_PAGE;
    const end = start + ROWS_PER_PAGE;

    // Hide all rows first
    allRows.forEach(r => {
      if (r.dataset.hidden) {
        r.style.display = 'none';
      }
    });

    // Show only current page slice of visible rows
    visibleRows.forEach((r, i) => {
      r.style.display = (i >= start && i < end) ? '' : 'none';
    });

    renderPagination(table, safePage, totalPages, total);
    refreshNumbers(table);
    updateResultCount(table);
  }

  function renderPagination(table, page, totalPages, total) {
    const paginEl = getPaginationEl(table);
    if (!paginEl) return;

    const infoEl = paginEl.querySelector('.pagination-info');
    const controlsEl = paginEl.querySelector('.pagination-controls');
    if (!infoEl || !controlsEl) return;

    const start = total === 0 ? 0 : (page - 1) * ROWS_PER_PAGE + 1;
    const end = Math.min(page * ROWS_PER_PAGE, total);
    infoEl.textContent = total === 0 ? 'Tidak ada data' : `${start}–${end} dari ${total} data`;

    controlsEl.innerHTML = '';

    // Prev button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'page-btn nav-btn';
    prevBtn.innerHTML = '<i class="ph-bold ph-caret-left"></i>';
    prevBtn.disabled = page <= 1;
    prevBtn.addEventListener('click', () => { setState(table, page - 1); applyPagination(table); });
    controlsEl.appendChild(prevBtn);

    // Page numbers
    buildPageList(page, totalPages).forEach(p => {
      if (p === '...') {
        const el = document.createElement('span');
        el.className = 'page-ellipsis';
        el.textContent = '…';
        controlsEl.appendChild(el);
      } else {
        const btn = document.createElement('button');
        btn.className = 'page-btn' + (p === page ? ' active' : '');
        btn.textContent = p;
        btn.addEventListener('click', () => { setState(table, p); applyPagination(table); });
        controlsEl.appendChild(btn);
      }
    });

    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'page-btn nav-btn';
    nextBtn.innerHTML = '<i class="ph-bold ph-caret-right"></i>';
    nextBtn.disabled = page >= totalPages;
    nextBtn.addEventListener('click', () => { setState(table, page + 1); applyPagination(table); });
    controlsEl.appendChild(nextBtn);
  }

  function buildPageList(current, total) {
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const pages = [1];
    if (current > 3) pages.push('...');
    for (let p = Math.max(2, current - 1); p <= Math.min(total - 1, current + 1); p++) pages.push(p);
    if (current < total - 2) pages.push('...');
    pages.push(total);
    return pages;
  }

  // ── Search ─────────────────────────────────────────────
  function applySearch(query) {
    const q = query.trim().toLowerCase();
    getAllTables().forEach(table => {
      const rows = table.querySelectorAll('tbody tr');
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (!q || text.includes(q)) {
          delete row.dataset.hiddenSearch;
        } else {
          row.dataset.hiddenSearch = '1';
        }
        // Combine search + filter hidden flags
        row.dataset.hidden = (row.dataset.hiddenSearch || row.dataset.hiddenFilter) ? '1' : '';
        if (!row.dataset.hidden) delete row.dataset.hidden;
      });
      setState(table, 1);
      applyPagination(table);
    });
  }

  function updateResultCount(table) {
    if (!resultCount) return;
    const total = Array.from(table.querySelectorAll('tbody tr'))
      .filter(r => !r.dataset.hidden).length;
    const allTotal = table.querySelectorAll('tbody tr').length;
    if (searchInput && searchInput.value.trim()) {
      resultCount.textContent = `${total} dari ${allTotal} hasil`;
    } else {
      resultCount.textContent = `${allTotal} data`;
    }
  }

  // ── Hook into existing filter logic ───────────────────
  // Override applyTableFilter to also update hidden flags & pagination
  const _origFilter = window.applyTableFilter;
  window.applyTableFilter = function () {
    if (_origFilter) _origFilter();
    // After filter hides rows via style.display, translate to data-hidden flags
    getAllTables().forEach(table => {
      table.querySelectorAll('tbody tr').forEach(row => {
        // existing filter sets display:none for non-matching rows
        const hiddenByFilter = row.style.display === 'none';
        if (hiddenByFilter) {
          row.dataset.hiddenFilter = '1';
        } else {
          delete row.dataset.hiddenFilter;
        }
        // Combine with search
        const hiddenSearch = !!row.dataset.hiddenSearch;
        if (hiddenByFilter || hiddenSearch) {
          row.dataset.hidden = '1';
          row.style.display = 'none';
        } else {
          delete row.dataset.hidden;
        }
      });
      setState(table, 1);
      applyPagination(table);
    });
  };

  // ── Hook into existing sort logic ─────────────────────
  const _origSort = window.sortTableByColumn;
  window.sortTableByColumn = function (sortBy, isDescending) {
    if (_origSort) _origSort(sortBy, isDescending);
    getAllTables().forEach(table => { setState(table, 1); applyPagination(table); });
  };

  // ── Event listeners ────────────────────────────────────
  if (searchInput) {
    searchInput.addEventListener('input', () => applySearch(searchInput.value));
  }

  // ── Tab switching – re-paginate active table ──────────
  document.querySelectorAll('.tab-item').forEach(tab => {
    tab.addEventListener('click', () => {
      setTimeout(() => {
        getAllTables().forEach(table => {
          const section = table.closest('.table-section');
          if (section && section.style.display !== 'none') {
            applyPagination(table);
          }
        });
      }, 50);
    });
  });

  // ── Init ───────────────────────────────────────────────
  getAllTables().forEach(table => applyPagination(table));
})();