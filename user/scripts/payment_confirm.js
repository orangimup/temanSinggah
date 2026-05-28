let activeSection = "pay";
let selectedPayTime = "now";
let selectedMethod = "gopay";

const ewalletMethods = ["gopay", "ovo", "dana"];
const cardMethods = ["visa", "mastercard"];

const methodLabels = {
  gopay: "GoPay",
  ovo: "OVO",
  dana: "DANA",
  visa: "Visa",
  mastercard: "Mastercard",
};

document.addEventListener("DOMContentLoaded", () => {
  setupToggleListeners();
  setupPayTimeListeners();
  setupCardFormatters();
  setupSubmitHandler();
  initMethodBoxes();
  renderSections();
  selectMethod("gopay");
});

function setupToggleListeners() {
  document.querySelectorAll("[data-toggle-section]").forEach((btn) => {
    btn.addEventListener("click", (e) => {
      e.preventDefault();
      const name = btn.getAttribute("data-toggle-section");
      activeSection = activeSection === name ? null : name;
      renderSections();
    });
  });
}

function renderSections() {
  const payExpand = document.getElementById("payExpand");
  const methodExpand = document.getElementById("methodExpand");
  if (payExpand) payExpand.classList.toggle("active", activeSection === "pay");
  if (methodExpand) methodExpand.classList.toggle("active", activeSection === "method");
}

function setupPayTimeListeners() {
  document.querySelectorAll("[data-select-pay]").forEach((row) => {
    row.addEventListener("click", function () {
      const val = this.getAttribute("data-select-pay");
      if (!val) return;
      selectedPayTime = val;
      applyPayTime(val);
    });
  });
}

function applyPayTime(val) {
  const radioNow = document.getElementById("radio-pay-now");
  const radioLater = document.getElementById("radio-pay-later");
  const dpInfoBox = document.getElementById("dpInfoBox");
  const summaryDp = document.getElementById("summaryDpBreakdown");
  const paySub = document.getElementById("paySub");
  const btnLabel = document.getElementById("btnPayLabel");
  const inputWT = document.getElementById("inputWaktuBayar");

  if (radioNow) radioNow.classList.toggle("selected", val === "now");
  if (radioLater) radioLater.classList.toggle("selected", val === "later");
  if (dpInfoBox) dpInfoBox.style.display = val === "later" ? "block" : "none";
  if (summaryDp) summaryDp.style.display = val === "later" ? "block" : "none";
  if (inputWT) inputWT.value = val;

  if (paySub)
    paySub.textContent = val === "now"
      ? "Bayar penuh " + (window.totalFormatted || "") + " sekarang"
      : "DP " + (window.dpFormatted || "") + " sekarang (30%)";

  if (btnLabel)
    btnLabel.textContent = val === "now"
      ? "Konfirmasi dan Bayar " + (window.totalFormatted || "")
      : "Bayar DP " + (window.dpFormatted || "");
}

function initMethodBoxes() {
  const methodExpand = document.getElementById("methodExpand");
  const doneRow = methodExpand && methodExpand.querySelector(".expand-done-row");
  const ewalletBox = document.getElementById("ewalletInputBox");
  const cardBox = document.getElementById("cardInputBox");

  if (doneRow) {
    if (ewalletBox) methodExpand.insertBefore(ewalletBox, doneRow);
    if (cardBox) methodExpand.insertBefore(cardBox, doneRow);
  }
}

function selectMethod(id) {
  selectedMethod = id;

  const inputMetode = document.getElementById("inputMetode");
  if (inputMetode) inputMetode.value = id;

  const sub = document.getElementById("methodSubLabel");
  if (sub) sub.textContent = methodLabels[id] || id;

  document.querySelectorAll(".method-radio").forEach((r) => r.classList.remove("selected"));
  document.querySelectorAll(".method-row").forEach((r) => r.classList.remove("selected-row"));

  const radioEl = document.getElementById("radio-" + id);
  if (radioEl) radioEl.classList.add("selected");

  const selectedRow = document.querySelector('[data-method-id="' + id + '"]');
  if (selectedRow) selectedRow.classList.add("selected-row");

  const ewalletBox = document.getElementById("ewalletInputBox");
  const cardBox = document.getElementById("cardInputBox");

  if (ewalletBox) ewalletBox.style.display = ewalletMethods.includes(id) ? "block" : "none";
  if (cardBox) cardBox.style.display = cardMethods.includes(id) ? "block" : "none";

  if (ewalletMethods.includes(id)) {
    const brandLabel = document.getElementById("ewalletBrandLabel");
    if (brandLabel) brandLabel.textContent = methodLabels[id];
  }
}

document.querySelectorAll(".method-row").forEach((row) => {
  row.addEventListener("click", () => selectMethod(row.dataset.methodId));
});

function setupCardFormatters() {
  const cardNumber = document.getElementById("cardNumber");
  const cardExpiry = document.getElementById("cardExpiry");
  const ewalletNum = document.getElementById("ewalletNumber");

  if (cardNumber) {
    cardNumber.addEventListener("input", function () {
      const v = this.value.replace(/\D/g, "").slice(0, 16);
      const parts = [];
      for (let i = 0; i < v.length; i += 4) parts.push(v.slice(i, i + 4));
      this.value = parts.join("  ");
    });
  }

  if (cardExpiry) {
    cardExpiry.addEventListener("input", function () {
      let v = this.value.replace(/\D/g, "").slice(0, 4);
      if (v.length > 2) v = v.slice(0, 2) + "/" + v.slice(2);
      this.value = v;
    });
  }

  if (ewalletNum) {
    ewalletNum.addEventListener("input", function () {
      this.value = this.value.replace(/\D/g, "").slice(0, 14);
    });
  }
}

function setupSubmitHandler() {
  const form = document.getElementById("bookingForm");
  if (!form) return;

  form.addEventListener("submit", function (e) {
    let detail = {};

    if (cardMethods.includes(selectedMethod)) {
      const nomor = (document.getElementById("cardNumber")?.value || "").replace(/\s/g, "");
      const expiry = document.getElementById("cardExpiry")?.value || "";
      const nama = (document.getElementById("cardName")?.value || "").trim();
      const cvv = document.getElementById("cardCvv")?.value || "";

      if (nomor.length < 13) { e.preventDefault(); alert("Masukkan nomor kartu yang valid."); return; }
      if (expiry.length < 5) { e.preventDefault(); alert("Masukkan tanggal berlaku kartu (MM/YY)."); return; }
      if (!cvv) { e.preventDefault(); alert("Masukkan CVV/CVC kartu."); return; }
      if (!nama) { e.preventDefault(); alert("Masukkan nama yang tertera di kartu."); return; }
      detail = { nomor, expiry, nama, brand: selectedMethod };

    } else if (ewalletMethods.includes(selectedMethod)) {
      const hp = (document.getElementById("ewalletNumber")?.value || "").trim();
      if (!hp || hp.length < 8) {
        e.preventDefault();
        alert("Masukkan nomor HP / akun " + (methodLabels[selectedMethod] || selectedMethod) + " yang valid.");
        return;
      }
      detail = { nomor_hp: hp };
    }

    const inputDetail = document.getElementById("inputDetailBayar");
    if (inputDetail) inputDetail.value = JSON.stringify(detail);

    const btn = document.getElementById("btnKonfirmasi");
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
        stroke-width="2.5" style="animation:spin 0.8s linear infinite;vertical-align:middle;margin-right:6px;">
        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4
                 M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg>Memproses...`;
    }
  });
}