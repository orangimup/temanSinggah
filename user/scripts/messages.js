const filterButtons = document.querySelectorAll(".filter-item");
const chatInput = document.querySelector(".chat-input");
const sendButton = document.querySelector(".send-button");
const fileInput = document.querySelector(".attach-button input");
const imagePreview = document.querySelector("#imagePreview");
const chatMessages = document.querySelector(".chat-messages");
const previewTemplate = document.querySelector("#previewItemTemplate");
const bubbleImageTemplate = document.querySelector("#bubbleImageTemplate");
const bubbleTextTemplate = document.querySelector("#bubbleTextTemplate");
const threadList = document.querySelector(".thread-list");
const chatAvatar = document.querySelector(".chat-avatar");
const chatName = document.querySelector(".chat-name");
const chatPropertyLabel = document.querySelector(".chat-property-label");
const chatDetailBtn = document.querySelector(".chat-detail-button");

let selectedFiles = [];
let activeConvId = null;
let activeHostId = null;
let lastMessageId = 0;
let pollingInterval = null;

// ── Pending conversation (belum ada conv_id, baru akan dibuat saat kirim) ─────
let pendingHostId = null;
let pendingListingId = null;
let pendingPropName = null;

// ── Filter (Semua / Belum Dibaca) ─────────────────────────────────────────────
filterButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    const isActive = btn.classList.contains("active");
    filterButtons.forEach((b) => b.classList.remove("active"));
    if (!isActive) btn.classList.add("active");
    loadConversations(btn.textContent.trim());
  });
});

// ── Auto-resize textarea ──────────────────────────────────────────────────────
chatInput.addEventListener("input", () => {
  chatInput.style.height = "auto";
  chatInput.style.height = chatInput.scrollHeight + "px";
});

// ── Preview gambar sebelum kirim ──────────────────────────────────────────────
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

// ── Load daftar percakapan ────────────────────────────────────────────────────
async function loadConversations(filter = "Semua") {
  const res = await fetch("messages.php?action=conversations");
  if (!res.ok) return;
  const data = await res.json();

  threadList.innerHTML = "";

  let list =
    filter === "Belum Dibaca" ? data.filter((c) => c.unread_count > 0) : data;

  if (list.length === 0) {
    threadList.innerHTML = `<p style="padding:var(--space-16);color:var(--color-text-secondary);font-size:var(--text-sm)">Tidak ada percakapan.</p>`;
    return;
  }

  list.forEach((conv) => {
    const avatarContent =
      conv.host_initial && conv.host_initial.trim()
        ? conv.host_initial
        : '<i class="ph-bold ph-user"></i>';

    const a = document.createElement("a");
    a.href = "#";
    a.className =
      "thread-item" + (conv.id === activeConvId ? " active" : "");
    a.dataset.convId = conv.id;
    a.innerHTML = `
      <div class="thread-avatar">${avatarContent}</div>
      <div class="thread-info">
        <div class="thread-header">
          <span class="thread-name">${conv.host_name} (Host)</span>
          <span class="thread-time">${conv.time_label}</span>
        </div>
        <span class="thread-preview">${conv.last_preview}</span>
        <span class="thread-property">${conv.property_name ?? ""}</span>
      </div>
      ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ""}
    `;
    a.addEventListener("click", (e) => {
      e.preventDefault();
      document
        .querySelectorAll(".thread-item")
        .forEach((t) => t.classList.remove("active"));
      a.classList.add("active");
      openConversation(conv);
    });
    threadList.appendChild(a);
  });

  // ── Auto-open: prioritaskan activeConvId (dari URL atau klik sebelumnya) ──
  if (activeConvId) {
    const target = list.find((c) => c.id === activeConvId);
    if (target) {
      threadList
        .querySelector(`[data-conv-id="${target.id}"]`)
        ?.classList.add("active");
      openConversation(target);
    } else {
      openConversationById(activeConvId);
    }
  } else if (!pendingHostId && list.length > 0) {
    // Tidak ada activeConvId dan tidak ada pending → buka percakapan pertama
    threadList.querySelector(".thread-item")?.classList.add("active");
    openConversation(list[0]);
  }
}

// ── Buka percakapan berdasarkan ID saja (fallback jika belum ada di list) ─────
async function openConversationById(convId) {
  const res = await fetch("messages.php?action=conversations");
  if (!res.ok) return;
  const data = await res.json();
  const target = data.find((c) => c.id === convId);
  if (target) {
    openConversation(target);
  } else {
    showEmptyConversation(convId);
  }
}

// ── Tampilkan room chat kosong untuk percakapan baru ──────────────────────────
function showEmptyConversation(convId) {
  activeConvId = convId;
  lastMessageId = 0;

  chatMessages.innerHTML = `
    <div class="chat-empty-state">
      <div class="empty-icon-wrap">
        <i class="ph-bold ph-chats"></i>
      </div>
      <p>Belum ada pesan</p>
      <span>Mulai percakapan dengan mengirimkan pesan ke host</span>
    </div>
  `;

  clearInterval(pollingInterval);
  pollingInterval = setInterval(loadMessages, 3000);
}

// ── Buka satu percakapan ──────────────────────────────────────────────────────
function openConversation(conv) {
  activeConvId = conv.id;
  activeHostId = conv.host_id ?? null;
  lastMessageId = 0;

  // Reset pending kalau buka conv yang sudah ada
  pendingHostId = null;
  pendingListingId = null;
  pendingPropName = null;

  // Update header
  if (conv.host_initial && conv.host_initial.trim()) {
    chatAvatar.textContent = conv.host_initial;
  } else {
    chatAvatar.innerHTML = '<i class="ph-bold ph-user"></i>';
  }
  chatName.textContent = conv.host_name;
  if (chatPropertyLabel) chatPropertyLabel.textContent = conv.property_name ?? "";

  chatMessages.innerHTML = "";
  clearInterval(pollingInterval);
  loadMessages();
  markRead();
  pollingInterval = setInterval(loadMessages, 3000);
}

// ── Load pesan dalam percakapan aktif ─────────────────────────────────────────
async function loadMessages() {
  if (!activeConvId) return;
  const res = await fetch(
    `messages.php?action=read&conversation_id=${activeConvId}`
  );
  if (!res.ok) return;
  const data = await res.json();

  const newMessages = data.filter((m) => m.id > lastMessageId);
  if (newMessages.length === 0) return;

  // Hapus empty state jika ada
  const emptyState = chatMessages.querySelector(".chat-empty-state");
  if (emptyState) emptyState.remove();

  newMessages.forEach((msg) => {
    if (msg.images && msg.images.length > 0) {
      const clone = bubbleImageTemplate.content.cloneNode(true);
      const bubble = clone.querySelector(".bubble");
      const grid = clone.querySelector(".bubble-image-grid");
      bubble.classList.add(msg.is_me ? "you" : "me");
      msg.images.forEach((src) => {
        const img = document.createElement("img");
        img.src = src;
        img.className = "bubble-image";
        img.alt = "Gambar";
        grid.appendChild(img);
      });
      chatMessages.appendChild(clone);
    }

    if (msg.message) {
      const clone = bubbleTextTemplate.content.cloneNode(true);
      clone.querySelector(".bubble").classList.add(msg.is_me ? "you" : "me");
      clone.querySelector(".bubble-text").textContent = msg.message;
      chatMessages.appendChild(clone);
    }

    if (msg.id > lastMessageId) lastMessageId = msg.id;
  });

  chatMessages.scrollTop = chatMessages.scrollHeight;
}

// ── Kirim pesan ───────────────────────────────────────────────────────────────
async function sendMessage() {
  const text = chatInput.value.trim();
  if (!text && selectedFiles.length === 0) return;
  if (!activeConvId && !pendingHostId) return;

  const formData = new FormData();
  formData.append("action", "send");

  if (activeConvId) {
    formData.append("conversation_id", activeConvId);
  } else {
    // Pending: buat conversation sekaligus kirim pesan pertama
    formData.append("conversation_id", "0");
    formData.append("host_id", pendingHostId);
    formData.append("listing_id", pendingListingId ?? "");
    formData.append("prop_name", pendingPropName ?? "");
  }

  formData.append("message", text);
  selectedFiles.forEach((f) => formData.append("images[]", f));

  const res = await fetch("messages.php", { method: "POST", body: formData });
  if (!res.ok) return;
  const data = await res.json();

  // Kalau tadi pending, sekarang sudah punya conv ID
  if (data.conversation_id) {
    activeConvId = data.conversation_id;
    pendingHostId = null;
    pendingListingId = null;
    pendingPropName = null;
  }

  chatInput.value = "";
  chatInput.style.height = "auto";
  selectedFiles = [];
  imagePreview.innerHTML = "";

  await loadMessages();
  await loadConversations(
    document.querySelector(".filter-item.active")?.textContent.trim() ?? "Semua"
  );
}

// ── Tandai sudah dibaca ───────────────────────────────────────────────────────
async function markRead() {
  if (!activeConvId) return;
  const fd = new FormData();
  fd.append("action", "mark_read");
  fd.append("conversation_id", activeConvId);
  await fetch("messages.php", { method: "POST", body: fd });
  await loadConversations(
    document.querySelector(".filter-item.active")?.textContent.trim() ?? "Semua"
  );
}

// ── Buka percakapan dari URL ?host=X&listing=Y ────────────────────────────────
async function openFromUrl() {
  const params = new URLSearchParams(window.location.search);
  const hostId = params.get("host");
  const listingId = params.get("listing");
  if (!hostId) return;

  const res = await fetch(
    `messages.php?action=start&host_id=${hostId}&listing_id=${listingId ?? ""}`
  );
  if (!res.ok) return;
  const data = await res.json();
  window.history.replaceState({}, "", "messages.php");

  if (data.conversation_id) {
    // Sudah pernah chat → buka langsung via loadConversations
    activeConvId = data.conversation_id;
  } else if (data.is_new) {
    pendingHostId    = data.pending_host_id;
    pendingListingId = data.pending_listing_id;
    pendingPropName  = data.pending_prop_name;

    if (data.pending_host_initial) {
      chatAvatar.textContent = data.pending_host_initial;
    } else {
      chatAvatar.innerHTML = '<i class="ph-bold ph-user"></i>';
    }
    chatName.textContent = data.pending_host_name ?? "Host";
    if (chatPropertyLabel) chatPropertyLabel.textContent = data.pending_prop_name ?? "";

    chatMessages.innerHTML = `
      <div class="chat-empty-state">
        <div class="empty-icon-wrap"><i class="ph-bold ph-chats"></i></div>
        <p>Belum ada pesan</p>
        <span>Mulai percakapan dengan mengirimkan pesan ke host</span>
      </div>`;

    clearInterval(pollingInterval);
  } 
}   

// ── Event listeners kirim ─────────────────────────────────────────────────────
sendButton.addEventListener("click", sendMessage);
chatInput.addEventListener("keydown", (e) => {
  if (e.key === "Enter" && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
});

// ── Init ──────────────────────────────────────────────────────────────────────
openFromUrl().then(() => loadConversations());