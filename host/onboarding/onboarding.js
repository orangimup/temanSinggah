const counterItem = document.querySelectorAll(".counter-row");

counterItem.forEach((item) => {
  const minusButton = item.querySelector(".minus");
  const plusButton = item.querySelector(".plus");
  const counterValue = item.querySelector(".counter-value");

  const min = parseInt(item.dataset.min) || 0;
  const max = parseInt(item.dataset.max) || 10;
  let value = parseInt(counterValue.textContent);

  function updateCounter() {
    counterValue.textContent = value;
    minusButton.classList.toggle("disabled", value <= min);
    plusButton.classList.toggle("disabled", value >= max);
  }

  plusButton.addEventListener("click", () => {
    if (value < max) {
      value++;
      updateCounter();
    }
  });

  minusButton.addEventListener("click", () => {
    if (value > min) {
      value--;
      updateCounter();
    }
  });

  updateCounter();
});

const priceSlider = document.querySelector(".price-slider");
const priceValue = document.querySelector(".price-display-value");
const priceInput = document.querySelector(".price-input");

function rupiahFormatting(angka) {
  return angka.toLocaleString("id-ID");
}

function clearNonNumeric(teks) {
  return teks.replace(/[^0-9]/g, "");
}

if (priceSlider && priceValue && priceInput) {
  priceSlider.addEventListener("input", function () {
    const currentValue = parseInt(priceSlider.value) || 0;
    const formatResult = rupiahFormatting(currentValue);
    priceValue.textContent = formatResult;
    priceInput.value = formatResult;
  });

  priceInput.addEventListener("keydown", function (event) {
    const allowedKeys = [
      "Backspace",
      "Tab",
      "ArrowLeft",
      "ArrowRight",
      "Delete",
      "Enter",
    ];
    if (
      !allowedKeys.includes(event.key) &&
      (event.key < "0" || event.key > "9")
    ) {
      event.preventDefault();
    }
  });

  priceInput.addEventListener("input", function () {
    const cleanedValue = clearNonNumeric(priceInput.value);
    const parsedNumber = parseInt(cleanedValue) || 0;
    priceSlider.value = parsedNumber;
    priceValue.textContent = rupiahFormatting(parsedNumber);
    priceInput.value = rupiahFormatting(parsedNumber);
  });
}

function initializeMap() {
  if (typeof L === "undefined") return;

  const mapElement = document.getElementById("propertyMap");
  if (!mapElement) return;

  try {
    const map = L.map("propertyMap", {
      center: [-7.9797, 112.6304],
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

    const icon = L.divIcon({
      html: `<i class="ph-fill ph-map-pin" style="font-size:40px;color:#8b2500;display:block;cursor:pointer;"></i>`,
      iconSize: [40, 40],
      iconAnchor: [20, 40],
      className: "",
    });

    const marker = L.marker([-7.9797, 112.6304], { icon }).addTo(map);

    let clickTimer = null;

    map.on("click", (e) => {
      if (clickTimer) return;

      clickTimer = setTimeout(async () => {
        clickTimer = null;
        marker.setLatLng(e.latlng);

        const { lat, lng } = e.latlng;
        try {
          const res = await fetch(
            `https:
          );
          const data = await res.json();
          const input = document.querySelector(".location-search input");
          if (input && data.display_name) {
            input.value = data.display_name;
          }
        } catch (err) {
        }
      }, 250);
    });

    map.on("dblclick", (e) => {
      if (clickTimer) {
        clearTimeout(clickTimer);
        clickTimer = null;
      }
      map.zoomIn();
    });

    const zoomIn = document.getElementById("zoomIn");
    const zoomOut = document.getElementById("zoomOut");

    if (zoomIn) {
      L.DomEvent.on(zoomIn, "click", (e) => {
        L.DomEvent.stopPropagation(e);
        map.zoomIn();
      });
    }

    if (zoomOut) {
      L.DomEvent.on(zoomOut, "click", (e) => {
        L.DomEvent.stopPropagation(e);
        map.zoomOut();
      });
    }
  } catch (error) {
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializeMap);
} else {
  initializeMap();
}

window.addEventListener("load", initializeMap);

const input = document.querySelector('.photo-upload-area input[type="file"]');

if (input) {
  input.addEventListener("change", () => {
    const files = Array.from(input.files);
    if (!files.length) return;

    const existing = JSON.parse(localStorage.getItem("fotoProperti") || "[]");
    let loaded = 0;

    files.forEach((file) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        existing.push(e.target.result);
        loaded++;
        if (loaded === files.length) {
          try {
            localStorage.setItem("fotoProperti", JSON.stringify(existing));
          } catch {
            alert("Foto terlalu besar. Coba kurangi jumlah atau ukuran foto.");
          }
        }
      };
      reader.readAsDataURL(file);
    });
  });
}

const grid = document.querySelector(".photo-grid");

if (grid) {
  const fotos = JSON.parse(localStorage.getItem("fotoProperti") || "[]");

  if (fotos.length > 0) {

    grid.innerHTML = "";

    fotos.forEach((src, index) => {
      const item = document.createElement("div");
      item.classList.add("photo-item");
      item.draggable = true;

      item.innerHTML = `
        <img src="${src}" alt="Foto ${index + 1}" />
        ${index === 0 ? '<span class="photo-badge">Foto Cover</span>' : ""}
        <div class="photo-remove"><i class="ph-bold ph-x"></i></div>
      `;

      item.querySelector(".photo-remove").addEventListener("click", () => {
        fotos.splice(index, 1);
        localStorage.setItem("fotoProperti", JSON.stringify(fotos));
        renderGrid(fotos);
      });

      grid.appendChild(item);
    });

    initDragSort();
  }
}

function renderGrid(fotos) {
  if (!grid) return;
  grid.innerHTML = "";
  fotos.forEach((src, index) => {
    const item = document.createElement("div");
    item.classList.add("photo-item");
    item.draggable = true;

    item.innerHTML = `
      <img src="${src}" alt="Foto ${index + 1}" />
      ${index === 0 ? '<span class="photo-badge">Foto Cover</span>' : ""}
      <div class="photo-remove"><i class="ph-bold ph-x"></i></div>
    `;

    item.querySelector(".photo-remove").addEventListener("click", () => {
      fotos.splice(index, 1);
      localStorage.setItem("fotoProperti", JSON.stringify(fotos));
      renderGrid(fotos);
    });

    grid.appendChild(item);
  });

  initDragSort();
}

function initDragSort() {
  const items = grid.querySelectorAll(".photo-item");
  let dragSrc = null;

  items.forEach((item) => {
    item.addEventListener("dragstart", () => {
      dragSrc = item;
      item.classList.add("dragging");
    });

    item.addEventListener("dragend", () => {
      item.classList.remove("dragging");

      const newOrder = Array.from(grid.querySelectorAll(".photo-item img")).map(
        (img) => img.src
      );
      localStorage.setItem("fotoProperti", JSON.stringify(newOrder));

      const badges = grid.querySelectorAll(".photo-badge");
      badges.forEach((b) => b.remove());
      const firstImg = grid.querySelector(".photo-item");
      if (firstImg) {
        const badge = document.createElement("span");
        badge.className = "photo-badge";
        badge.textContent = "Foto Cover";
        firstImg.appendChild(badge);
      }
    });

    item.addEventListener("dragover", (e) => {
      e.preventDefault();
      if (item !== dragSrc) {
        const rect = item.getBoundingClientRect();
        const mid = rect.top + rect.height / 2;
        if (e.clientY < mid) {
          grid.insertBefore(dragSrc, item);
        } else {
          grid.insertBefore(dragSrc, item.nextSibling);
        }
      }
    });
  });
}
