// ============================================================
//  REVENUE TREND CHART (dashboard.html)
// ============================================================
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

// ============================================================
//  DONUT CHART (dashboard.html)
// ============================================================
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

// ============================================================
//  VISITORS BAR CHART (dashboard.html)
// ============================================================
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

// ============================================================
//  FILTER (reviews.html & halaman lain yang punya .filter-item)
// ============================================================
const filterButtons = document.querySelectorAll(".filter-item");
filterButtons.forEach((filterItem) => {
  filterItem.addEventListener("click", () => {
    filterButtons.forEach((i) => i.classList.remove("active"));
    filterItem.classList.add("active");
  });
});

// ============================================================
//  SORT BUTTON (reviews.html & halaman lain yang punya .sort-button)
// ============================================================
const sortButtons = document.querySelectorAll(".sort-button");
sortButtons.forEach((sortItem) => {
  sortItem.addEventListener("click", () => {
    const isActive = sortItem.classList.toggle("active");
    const textSpan = sortItem.querySelector("span");
    const textActive = sortItem.dataset.active;
    const textInact = sortItem.dataset.inactive;
    if (textSpan && textActive && textInact) {
      textSpan.textContent = isActive ? textActive : textInact;
    }
  });
});

// ============================================================
//  TOGGLE SWITCH (settings.html)
// ============================================================
const toggleSwitches = document.querySelectorAll(".toggle-switch");

toggleSwitches.forEach((toggle) => {
  toggle.addEventListener("click", () => {
    toggle.classList.toggle("active");
  });
});

// ============================================================
//  TAB + INDICATOR (logs.html & halaman lain yang punya .tab-item)
// ============================================================
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
