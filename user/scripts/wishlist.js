document.querySelectorAll(".save-button").forEach((saveItem) => {
  saveItem.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    const isActive = saveItem.classList.toggle("active");
    saveItem.src = isActive
      ? "/assets/icons/save.svg"
      : "/assets/icons/save_fill.svg";
  });
});

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
    document.querySelectorAll(".map-property-card.open").forEach((c) => {
      c.classList.remove("open");
    });
  }

  properties.forEach(({ id, latlng, price }) => {
    const latLng = L.latLng(...latlng);

    const icon = L.divIcon({
      html: `<i class="ph-fill ph-map-pin" style="font-size:32px;color:#8b2500;display:block;"></i>`,
      iconSize: [32, 32],
      iconAnchor: [16, 32],
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
      cardLayer._update();
    }

    function closeCard() {
      card.classList.remove("open");
    }

    marker.on("click", (e) => {
      L.DomEvent.stopPropagation(e);
      card.classList.contains("open") ? closeCard() : openCard();
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
            ? "/assets/icons/save.svg"
            : "/assets/icons/save_fill.svg";
        }
      });
    }
  });

  map.on("click", closeAllCards);

  document
    .getElementById("zoomIn")
    ?.addEventListener("click", () => map.zoomIn());
  document
    .getElementById("zoomOut")
    ?.addEventListener("click", () => map.zoomOut());
});
