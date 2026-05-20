// ===================================================
// messages.js — Sisi User
// Disimpan di: /user/scripts/messages.js
// ===================================================

// ─── Filter tab (Semua / Belum Dibaca) ─────────────
const filterButtons = document.querySelectorAll(".filter-item");

filterButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    const isActive = btn.classList.contains("active");
    filterButtons.forEach((b) => b.classList.remove("active"));
    if (!isActive) btn.classList.add("active");
  });
});

// ─── Pilih thread percakapan ────────────────────────
const threadItems = document.querySelectorAll(".thread-item");

threadItems.forEach((item) => {
  item.addEventListener("click", (e) => {
    e.preventDefault();
    threadItems.forEach((t) => t.classList.remove("active"));
    item.classList.add("active");
    // TODO: load chat sesuai data-chat ketika sudah ada backend
  });
});

// ─── Chat input & kirim pesan ──────────────────────
const chatInput = document.querySelector(".chat-input");
const sendButton = document.querySelector(".send-button");
const fileInput = document.querySelector(".attach-button input");
const imagePreview = document.querySelector("#imagePreview");
const chatMessages = document.querySelector(".chat-messages");
const previewTemplate = document.querySelector("#previewItemTemplate");
const bubbleImageTemplate = document.querySelector("#bubbleImageTemplate");
const bubbleTextTemplate = document.querySelector("#bubbleTextTemplate");

let selectedFiles = [];

// Auto-resize textarea
chatInput.addEventListener("input", () => {
  chatInput.style.height = "auto";
  chatInput.style.height = chatInput.scrollHeight + "px";
});

// Attach gambar
fileInput.addEventListener("change", () => {
  Array.from(fileInput.files).forEach((file) => {
    selectedFiles.push(file);

    const reader = new FileReader();
    reader.onload = (e) => {
      const clone = previewTemplate.content.cloneNode(true);
      const img = clone.querySelector(".preview-image");
      const removeBtn = clone.querySelector(".remove-preview");

      img.src = e.target.result;

      removeBtn.addEventListener("click", () => {
        selectedFiles = selectedFiles.filter((f) => f !== file);
        img.closest(".preview-item").remove();
      });

      imagePreview.appendChild(clone);
    };
    reader.readAsDataURL(file);
  });

  fileInput.value = "";
});

// Kirim pesan
function sendMessage() {
  const text = chatInput.value.trim();
  if (!text && selectedFiles.length === 0) return;

  // Bubble gambar — posisi kanan (user = "you")
  if (selectedFiles.length > 0) {
    const imageClone = bubbleImageTemplate.content.cloneNode(true);
    const imageGrid = imageClone.querySelector(".bubble-image-grid");

    selectedFiles.forEach((file) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        const img = document.createElement("img");
        img.src = e.target.result;
        img.className = "bubble-image";
        imageGrid.appendChild(img);
      };
      reader.readAsDataURL(file);
    });

    chatMessages.appendChild(imageClone);
  }

  // Bubble teks
  if (text) {
    const textClone = bubbleTextTemplate.content.cloneNode(true);
    textClone.querySelector(".bubble-text").textContent = text;
    chatMessages.appendChild(textClone);
  }

  // Reset
  chatMessages.scrollTop = chatMessages.scrollHeight;
  chatInput.value = "";
  chatInput.style.height = "auto";
  selectedFiles = [];
  imagePreview.innerHTML = "";
}

sendButton.addEventListener("click", sendMessage);

chatInput.addEventListener("keydown", (e) => {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});