// Sort Button
const sortButton = document.querySelectorAll(".sort-button");

sortButton.forEach((sortItem) => {
  sortItem.addEventListener("click", () => {
    const isActive = sortItem.classList.toggle("active");

    const iconI = sortItem.querySelector(".caret-icon");

    iconI.className = isActive
      ? "caret-icon ph-bold ph-caret-up"
      : "caret-icon ph-bold ph-caret-down";
  });
});

// Filter Button
const filterButton = document.querySelectorAll(".filter-item");

filterButton.forEach((filterItem) => {
  filterItem.addEventListener("click", () => {
    const isActive = filterItem.classList.contains("active");
    filterButton.forEach((filter) => filter.classList.remove("active"));

    if (!isActive) {
      filterItem.classList.add("active");
    }
  });
});

// Thread Item
const threadContact = document.querySelectorAll(".thread-item");

threadContact.forEach((threadItem) => {
  threadItem.addEventListener("click", () => {
    threadContact.forEach((thread) => thread.classList.remove("active"));
    threadItem.classList.add("active");
  });
});

// Send Messages
const chatInput = document.querySelector(".chat-input");
const sendButton = document.querySelector(".send-button");
const fileInput = document.querySelector(".attach-button input");
const imagePreview = document.querySelector("#imagePreview");
const chatArea = document.querySelector(".chat-messages");
const previewTemplate = document.querySelector("#previewItemTemplate");
const bubbleTemplate = document.querySelector("#bubbleImageTemplate");

let selectedFiles = [];

chatInput.addEventListener("input", () => {
  chatInput.style.height = "auto";
  chatInput.style.height = chatInput.scrollHeight + "px";
});

fileInput.addEventListener("change", () => {
  Array.from(fileInput.files).forEach((file) => {
    selectedFiles.push(file);

    const reader = new FileReader();
    reader.onload = (e) => {
      const clone = previewTemplate.content.cloneNode(true);
      const image = clone.querySelector(".preview-image");
      const removeButton = clone.querySelector(".remove-preview");

      image.src = e.target.result;

      removeButton.addEventListener("click", () => {
        selectedFiles = selectedFiles.filter((f) => f !== file);
        image.closest(".preview-item").remove();
      });

      imagePreview.appendChild(clone);
    };
    reader.readAsDataURL(file);
  });

  fileInput.value = "";
});

function sendMessage() {
  const text = chatInput.value.trim();
  if (!text && selectedFiles.length === 0) return;

  if (selectedFiles.length > 0) {
    const imageClone = bubbleTemplate.content.cloneNode(true);
    const imageGrid = imageClone.querySelector(".bubble-image-grid");

    selectedFiles.forEach((file) => {
      const reader = new FileReader();
      reader.onload = (e) => {
        const image = document.createElement("img");
        image.src = e.target.result;
        image.className = "bubble-image";
        imageGrid.appendChild(image);
      };
      reader.readAsDataURL(file);
    });

    chatArea.appendChild(imageClone);
  }

  if (text) {
    const textClone = bubbleTextTemplate.content.cloneNode(true);
    textClone.querySelector(".bubble-text").textContent = text;
    chatArea.appendChild(textClone);
  }

  chatArea.scrollTop = chatArea.scrollHeight;
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
