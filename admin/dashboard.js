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

function createGradients(ctx, chartHeight) {
  const blueGrad = ctx.createLinearGradient(0, 0, 0, chartHeight);
  blueGrad.addColorStop(0, "rgba(55,138,221,0.22)");
  blueGrad.addColorStop(1, "rgba(55,138,221,0.01)");

  const pinkGrad = ctx.createLinearGradient(0, 0, 0, chartHeight);
  pinkGrad.addColorStop(0, "rgba(224,91,122,0.18)");
  pinkGrad.addColorStop(1, "rgba(224,91,122,0.01)");

  return { blueGrad, pinkGrad };
}

// ── Spring physics ───────────────────────────────────────
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

// ── Spring dot plugin ─────────────────────────────────────
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

// ── Inisialisasi Revenue Chart ────────────────────────────
const canvas = document.getElementById("revenueTrendChart");
const ctx = canvas.getContext("2d");
const { blueGrad, pinkGrad } = createGradients(ctx, 300);

const chart = new Chart(canvas, {
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

// ── Revenue Tooltip ───────────────────────────────────────
const tooltip = document.getElementById("revTooltip");
const ttTitle = document.getElementById("revTt-title");
const ttVal1 = document.getElementById("revTt-val1");
const ttVal2 = document.getElementById("revTt-val2");
const wrap = document.getElementById("chartWrap");

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

  const canvasRect = canvas.getBoundingClientRect();
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

canvas.addEventListener("mousemove", (e) => {
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

canvas.addEventListener("mouseleave", scheduleHide);

// ----------------------------------------------------------
// DONUT CHART — Status Properti
// ----------------------------------------------------------
const donutData = {
  labels: ["Tersedia", "Penuh"],
  values: [66, 129], // ← ganti dengan data aslimu
  colors: ["#4ade80", "#fde68a"],
};

const donutTotal = donutData.values.reduce((a, b) => a + b, 0);

const donutCanvas = document.getElementById("donutChart");
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
    cutout: "68%",
    animation: { duration: 900, easing: "easeInOutQuart" },
    plugins: {
      legend: { display: false },
      tooltip: { enabled: false },
    },
  },
});

// ── Donut Tooltip (pola sama dengan revenue tooltip) ──────
const dTooltip = document.getElementById("donutTooltip");
const dttTitle = document.getElementById("dtt-title");
const dttBox = document.getElementById("dtt-box");
const dttLabel = document.getElementById("dtt-label");
const dttVal = document.getElementById("dtt-val");
const dWrap = document.getElementById("donutChartBox");

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
  const label = donutData.labels[idx];
  const value = donutData.values[idx];
  const color = donutData.colors[idx];
  const pct = ((value / donutTotal) * 100).toFixed(1);

  dttTitle.textContent = label;
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

// ----------------------------------------------------------
// FILTER BUTTON
// ----------------------------------------------------------
const filterButton = document.querySelectorAll(".filter-item");

filterButton.forEach((filterItem) => {
  filterItem.addEventListener("click", () => {
    filterButton.forEach((i) => i.classList.remove("active"));
    filterItem.classList.add("active");
  });
});

// ----------------------------------------------------------
// SORT BUTTON
// ----------------------------------------------------------
const sortButtons = document.querySelectorAll(".sort-button");

sortButtons.forEach((sortItem) => {
  sortItem.addEventListener("click", () => {
    const isActive = sortItem.classList.toggle("active");
    const textSpan = sortItem.querySelector("span");
    const textActive = sortItem.dataset.active;
    const textInactive = sortItem.dataset.inactive;

    if (textSpan && textActive && textInactive) {
      textSpan.textContent = isActive ? textActive : textInactive;
    }
  });
});

// ----------------------------------------------------------
// TAB GROUP
// ----------------------------------------------------------
const tabItems = document.querySelectorAll(".tab-item");
const tabIndicator = document.querySelector(".tab-indicator");

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

    document.querySelector(tab.dataset.target).style.display = "block";
  });
});

const activeTab = document.querySelector(".tab-item.active");
if (activeTab) moveIndicator(activeTab);
