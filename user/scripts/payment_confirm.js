// ===== State =====
let activeSection = "pay";
let selectedPay = "now";
let selectedMethod = "gopay";

// ===== Init =====
document.addEventListener("DOMContentLoaded", () => {
  renderAll();
});

// ===== Toggle section =====
function toggleSection(name) {
  activeSection = activeSection === name ? null : name;
  renderAll();
}

// ===== Select pay option =====
function selectPay(val) {
  selectedPay = val;
  renderRadios();
}

// ===== Select method option =====
function selectMethod(val) {
  selectedMethod = val;
  renderRadios();
  renderCardForm();
  renderMethodHeader();
}

// ===== Render semua state =====
function renderAll() {
  ["pay", "method"].forEach((name) => {
    const el = document.getElementById("expand-" + name);
    if (el) el.style.display = activeSection === name ? "block" : "none";
  });

  const review = document.getElementById("review-content");
  if (review) review.style.display = activeSection ? "none" : "block";

  renderRadios();
  renderCardForm();
  renderMethodHeader();
}

// ===== Render radio circles =====
function renderRadios() {
  setRadio("radio-now", selectedPay === "now");
  setRadio("radio-later", selectedPay === "later");
  setRadio("radio-gopay", selectedMethod === "gopay");
  setRadio("radio-card", selectedMethod === "card");
}

function setRadio(id, active) {
  const el = document.getElementById(id);
  if (!el) return;
  el.className = "cp-radio-circle" + (active ? " selected" : "");
}

// ===== Toggle form kartu =====
function renderCardForm() {
  const form = document.getElementById("card-form");
  if (!form) return;
  form.style.display = selectedMethod === "card" ? "block" : "none";
}

// ===== Update header collapsed step 2 =====
function renderMethodHeader() {
  const el = document.getElementById("method-header-display");
  if (!el) return;

  if (selectedMethod === "card") {
    el.innerHTML = `
      <div class="cp-card-icon"><i class="ph-bold ph-credit-card"></i></div>
      <span class="cp-step-sub">Credit or debit card</span>
    `;
  } else {
    el.innerHTML = `
      <div class="cp-gopay-icon"><span>GP</span></div>
      <span class="cp-step-sub">GoPay</span>
    `;
  }
}
