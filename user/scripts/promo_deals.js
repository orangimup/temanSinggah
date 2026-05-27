const allPromos = [];
let currentFilter = "semua";
let sortOrder = "descending";

function initializePromoData() {
  const promoCards = document.querySelectorAll(".promo-card");
  allPromos.length = 0;

  promoCards.forEach((card) => {
    const discountText =
      card.querySelector(".discount-tag")?.textContent || "0%";
    const discount = parseInt(discountText.replace(/[^0-9]/g, "")) || 0;

    allPromos.push({
      element: card,
      category: card.dataset.category || "semua",
      discount: discount,
      title: card.querySelector(".promo-title")?.textContent || "",
    });
  });
}

function filterPromos(category) {
  currentFilter = category;
  const promoGrid = document.querySelector(".promo-grid");

  allPromos.forEach((promo) => {
    if (category === "semua" || promo.category === category) {
      promo.element.style.display = "";

      promo.element.style.animation = "none";
      setTimeout(() => {
        promo.element.style.animation = "fadeIn 0.3s ease-in";
      }, 10);
    } else {
      promo.element.style.display = "none";
    }
  });

  sortPromos(sortOrder);
}

function sortPromos(order) {
  sortOrder = order;
  const promoGrid = document.querySelector(".promo-grid");
  const visiblePromos = allPromos.filter(
    (p) => p.element.style.display !== "none",
  );

  visiblePromos.sort((a, b) => {
    if (order === "descending") {
      return b.discount - a.discount;
    } else {
      return a.discount - b.discount; 
    }
  });

  visiblePromos.forEach((promo) => {
    promoGrid.appendChild(promo.element);
  });
}

const style = document.createElement("style");
style.textContent = `
  @keyframes fadeIn {
    from {
      opacity: 0;
      transform: translateY(10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
`;
document.head.appendChild(style);

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initializePromoData);
} else {
  initializePromoData();
}

const filterButton = document.querySelectorAll(".filter-item");
const filterMapping = {
  "Semua Promo": "semua",
  "Flash Sale": "flash",
  "Weekend Deal": "weekend",
  "Early Bird": "earlybird",
  "Last Minute": "lastminute",
};

filterButton.forEach((filterItem) => {
  filterItem.addEventListener("click", () => {
    filterButton.forEach((i) => i.classList.remove("active"));
    filterItem.classList.add("active");

    const buttonText = filterItem.textContent.trim();
    const category = filterMapping[buttonText] || "semua";

    filterPromos(category);
  });
});

const sortButton = document.querySelector(".sort-button");

sortButton.addEventListener("click", () => {
    const isActive = sortButton.classList.toggle("active");

    const textSpan = sortItem.querySelector("span");
    const iconI = sortItem.querySelector(".caret-icon");

    if (isActive) {
      textSpan.textContent = "Urutkan: Diskon Terkecil";
      iconI.className = "caret-icon ph-bold ph-caret-up";
      sortPromos("ascending");
    } else {
      textSpan.textContent = "Urutkan: Diskon Terbesar";
      iconI.className = "caret-icon ph-bold ph-caret-down";
      sortPromos("descending");
    }
  });

const copyButton = document.querySelectorAll(".copy-button");
copyButton.forEach((copyItem) => {
  copyItem.addEventListener("click", () => {
    const voucherCode = copyItem
      .closest(".voucher-card")
      .querySelector(".voucher-code").textContent;
    navigator.clipboard.writeText(voucherCode);
  });
});

const saveButton = document.querySelectorAll(".save-button");

saveButton.forEach((saveItem) => {
  saveItem.addEventListener("click", (e) => {
    e.preventDefault();
    e.stopPropagation();
    const isActive = saveItem.classList.toggle("active");

    saveItem.src = isActive
      ? "/assets/icons/save_fill.svg"
      : "/assets/icons/save.svg";
  });
});
