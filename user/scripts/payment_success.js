document.addEventListener("DOMContentLoaded", () => {
  initCopyBtn();
  initCountdown();
});

function initCopyBtn() {
  const copyBtn = document.getElementById("copyBtn");
  const reservationId = document.getElementById("reservationId");

  if (!copyBtn || !reservationId) return;

  copyBtn.addEventListener("click", async () => {
    const text = reservationId.textContent.trim();

    try {
      await navigator.clipboard.writeText(text);
      showToast("ID berhasil disalin");
      setCopiedState(copyBtn);
    } catch {
      // Fallback for older browsers
      fallbackCopy(text);
      showToast("ID berhasil disalin");
      setCopiedState(copyBtn);
    }
  });
}

function setCopiedState(btn) {
  const icon = btn.querySelector("i");
  const label = btn.querySelector("span");

  icon.className = "ph-fill ph-check-circle";
  label.textContent = "Tersalin!";
  btn.style.color = "#2d8a57";
  btn.style.borderColor = "#b9dfc8";
  btn.style.background = "#e5f3ea";

  setTimeout(() => {
    icon.className = "ph-bold ph-copy";
    label.textContent = "Salin";
    btn.style.color = "";
    btn.style.borderColor = "";
    btn.style.background = "";
  }, 2000);
}

function fallbackCopy(text) {
  const textarea = document.createElement("textarea");
  textarea.value = text;
  textarea.style.cssText = "position:fixed;opacity:0;pointer-events:none;";
  document.body.appendChild(textarea);
  textarea.select();
  document.execCommand("copy");
  textarea.remove();
}

function showToast(message = "Berhasil") {
  const toast = document.getElementById("toast");
  const toastText = document.getElementById("toastText");

  if (!toast) return;

  if (toastText) toastText.textContent = message;

  toast.classList.add("show");

  setTimeout(() => {
    toast.classList.remove("show");
  }, 2500);
}

function initCountdown() {
  const countdownEl = document.getElementById("countdown");
  if (!countdownEl) return;

  let seconds = parseInt(countdownEl.textContent, 10) || 10;

  const interval = setInterval(() => {
    seconds--;
    countdownEl.textContent = seconds;

    if (seconds <= 0) {
      clearInterval(interval);
      window.location.href = "/index.html";
    }
  }, 1000);
}