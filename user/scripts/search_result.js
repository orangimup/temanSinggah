(function () {
  const ITEMS_PER_PAGE = 10;

  const allItems = Array.from(document.querySelectorAll(".hotel-card"));
  const totalPages = Math.ceil(allItems.length / ITEMS_PER_PAGE);
  let currentPage = 1;

  const prevBtn = document.getElementById("paginatePrev");
  const nextBtn = document.getElementById("paginateNext");
  const numbersEl = document.getElementById("paginationNumbers");

  function showPage(page) {
    const start = (page - 1) * ITEMS_PER_PAGE;
    const end = start + ITEMS_PER_PAGE;
    allItems.forEach((item, i) => {
      item.style.display = i >= start && i < end ? "" : "none";
    });
  }

  function buildPageList(current, total) {
    if (total <= 7) {
      return Array.from({ length: total }, (_, i) => i + 1);
    }
    const delta = 1;
    const left = current - delta;
    const right = current + delta;
    const pages = [];
    pages.push(1);
    if (left > 2) pages.push("...");
    for (let p = Math.max(2, left); p <= Math.min(total - 1, right); p++) {
      pages.push(p);
    }
    if (right < total - 1) pages.push("...");
    pages.push(total);
    return pages;
  }

  function renderNumbers(page) {
    numbersEl.innerHTML = "";
    buildPageList(page, totalPages).forEach((p) => {
      if (p === "...") {
        const el = document.createElement("span");
        el.className = "pagination-ellipsis";
        el.textContent = "...";
        numbersEl.appendChild(el);
      } else {
        const btn = document.createElement("button");
        btn.className = "control-button" + (p === page ? " active" : "");
        btn.textContent = p;
        btn.addEventListener("click", () => goTo(p));
        numbersEl.appendChild(btn);
      }
    });
  }

  function updateControls(page) {
    prevBtn.disabled = page === 1;
    nextBtn.disabled = page === totalPages;
  }

  function goTo(page) {
    if (page < 1 || page > totalPages) return;
    currentPage = page;
    showPage(currentPage);
    renderNumbers(currentPage);
    updateControls(currentPage);
    allItems[0]?.scrollIntoView({ behavior: "smooth", block: "start" });
  }

  prevBtn.addEventListener("click", () => goTo(currentPage - 1));
  nextBtn.addEventListener("click", () => goTo(currentPage + 1));

  if (allItems.length > 0) {
    goTo(1);
  } else {
    document
      .querySelector(".control-buttons")
      ?.style.setProperty("display", "none");
  }
})();

window.addEventListener("load", () => {
  const map = L.map("propertyMap", {
    center: [-8.5069, 115.2625],
    zoom: 15,
    scrollWheelZoom: false,
    zoomControl: false,
  });

  L.tileLayer(
    "https://api.maptiler.com/maps/streets-v4/{z}/{x}/{y}@2x.png?key=zXLv2UJENN51Ss9xxDAM",
    {
      attribution: "© MapTiler © OpenStreetMap",
      tileSize: 512,
      zoomOffset: -1,
      maxZoom: 20,
    },
  ).addTo(map);

  const properties = [
    { id: "1", latlng: [-8.5069, 115.2625], price: "Rp 850.000" },
    { id: "2", latlng: [-8.508, 115.264], price: "Rp 650.000" },
  ];

  const CardLayer = L.Layer.extend({
    initialize(latlng, card, options) {
      this._latlng = latlng;
      this._card = card;
      L.setOptions(this, options);
    },
    onAdd(map) {
      this._map = map;
      map.getPane("overlayPane").appendChild(this._card);
      map.on("zoom move zoomend moveend", this._update, this);
      this._update();
    },
    onRemove(map) {
      map.off("zoom move zoomend moveend", this._update, this);
    },
    _update() {
      if (!this._map || !this._card) return;
      const pos = this._map.latLngToLayerPoint(this._latlng);
      const cardW = this._card.offsetWidth || 260;
      const cardH = this._card.offsetHeight || 220;
      L.DomUtil.setPosition(
        this._card,
        L.point(pos.x - cardW / 2, pos.y - cardH - 28),
      );
    },
  });

  function closeAllCards() {
    document
      .querySelectorAll(".map-property-card.open")
      .forEach((c) => c.classList.remove("open"));
    document
      .querySelectorAll(".map-price-pill.active")
      .forEach((p) => p.classList.remove("active"));
  }

  properties.forEach(({ id, latlng, price }) => {
    const latLng = L.latLng(...latlng);

    const icon = L.divIcon({
      html: `<button class="map-price-pill" data-marker-id="${id}">${price}</button>`,
      iconSize: null,
      iconAnchor: [50, 18],
      className: "",
    });

    const marker = L.marker(latLng, { icon }).addTo(map);

    const card = document.querySelector(
      `.map-property-card[data-marker-id="${id}"]`,
    );
    if (!card) return;

    const cardLayer = new CardLayer(latLng, card);
    cardLayer.addTo(map);

    function openCard() {
      closeAllCards();
      card.classList.add("open");
      marker
        .getElement()
        ?.querySelector(".map-price-pill")
        ?.classList.add("active");
      cardLayer._update();
    }

    function closeCard() {
      card.classList.remove("open");
      marker
        .getElement()
        ?.querySelector(".map-price-pill")
        ?.classList.remove("active");
    }

    marker.on("click", (e) => {
      L.DomEvent.stopPropagation(e);
      card.classList.contains("open") ? closeCard() : openCard();
    });

    marker.on("add", () => {
      const pillEl = marker.getElement();
      if (pillEl) {
        L.DomEvent.disableClickPropagation(pillEl);
        L.DomEvent.on(pillEl, "dblclick", L.DomEvent.stopPropagation);
      }
    });

    card.querySelector(".map-card-close")?.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      closeCard();
    });

    card.addEventListener("mouseenter", () => {
      map.dragging.disable();
      map.scrollWheelZoom.disable();
      map.doubleClickZoom.disable();
      map.touchZoom.disable();
      map.boxZoom.disable();
      map.keyboard.disable();
    });

    card.addEventListener("mouseleave", () => {
      map.dragging.enable();
      map.doubleClickZoom.enable();
      map.touchZoom.enable();
      map.boxZoom.enable();
      map.keyboard.enable();
    });

    ["click", "dblclick", "mousedown"].forEach((evt) => {
      card.addEventListener(evt, (e) => e.stopPropagation());
    });
    ["touchstart", "touchmove", "wheel"].forEach((evt) => {
      card.addEventListener(evt, (e) => e.stopPropagation(), { passive: true });
    });

    const saveBtn = card.querySelector(".map-card-button.wishlist");
    if (saveBtn) {
      const saveImg = saveBtn.querySelector("img");
      saveBtn.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        const isActive = saveBtn.classList.toggle("active");
        if (saveImg) {
          saveImg.src = isActive
            ? "/assets/icons/save_fill.svg"
            : "/assets/icons/save.svg";
        }
      });
    }
  });

  map.on("click", closeAllCards);

  document.querySelectorAll(".save-button").forEach((saveItem) => {
    saveItem.addEventListener("click", (e) => {
      e.preventDefault();
      e.stopPropagation();
      const isActive = saveItem.classList.toggle("active");
      saveItem.src = isActive
        ? "/assets/icons/save_fill.svg"
        : "/assets/icons/save.svg";
    });
  });

  document
    .getElementById("zoomIn")
    ?.addEventListener("click", () => map.zoomIn());
  document
    .getElementById("zoomOut")
    ?.addEventListener("click", () => map.zoomOut());
});
