<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_host.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/koneksi.php';

$host_id = $_SESSION['id'] ?? $_SESSION['user_id'] ?? null;
if (!$host_id) {
  http_response_code(401);
  echo json_encode(['error' => 'Tidak terautentikasi']);
  exit;
}

// ── GET conversations ──────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'conversations') {
  header('Content-Type: application/json');

  $sql = "
    SELECT
      c.id,
      c.guest_id,
      c.property_name,
      c.last_message_at,
      g.nama   AS guest_name,
      g.photo  AS guest_photo,
      m.message        AS last_message,
      m.image_paths    AS last_image_paths,
      m.sender_id      AS last_sender_id,
      (SELECT COUNT(*) FROM messages ms
       JOIN conversations cs ON cs.id = ms.conversation_id
       WHERE cs.host_id = '$host_id' AND cs.guest_id = c.guest_id
         AND ms.sender_id != '$host_id' AND ms.is_read = 0) AS unread_count
    FROM conversations c
    JOIN users g ON g.id = c.guest_id
    LEFT JOIN messages m ON m.id = (
      SELECT MAX(msg2.id) FROM messages msg2
      JOIN conversations cc ON cc.id = msg2.conversation_id
      WHERE cc.host_id = '$host_id' AND cc.guest_id = c.guest_id
    )
    WHERE c.host_id = '$host_id'
      AND c.id = (
        SELECT MAX(c2.id) FROM conversations c2
        WHERE c2.host_id = '$host_id' AND c2.guest_id = c.guest_id
      )
    ORDER BY c.last_message_at DESC
  ";

  $result = mysqli_query($koneksi, $sql);
  if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($koneksi)]);
    exit;
  }

  $conversations = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $preview = ($row['last_sender_id'] === $host_id) ? 'Anda: ' : '';
    if ($row['last_message'])
      $preview .= $row['last_message'];
    elseif ($row['last_image_paths'])
      $preview .= '📷 Gambar dikirim';
    else
      $preview .= 'Belum ada pesan';

    $sent_ts = strtotime($row['last_message_at']);
    if ($sent_ts >= strtotime('today'))
      $time_label = date('H:i', $sent_ts);
    elseif ($sent_ts >= strtotime('yesterday'))
      $time_label = 'Kemarin';
    else
      $time_label = date('d M', $sent_ts);

    $conversations[] = [
      'id'            => (int) $row['id'],
      'guest_id'      => $row['guest_id'],
      'guest_name'    => $row['guest_name'],
      'guest_initial' => strtoupper(substr($row['guest_name'], 0, 1)),
      'guest_photo'   => $row['guest_photo'],
      'last_preview'  => $preview,
      'time_label'    => $time_label,
      'unread_count'  => (int) $row['unread_count'],
    ];
  }
  echo json_encode($conversations);
  exit;
}

// ── GET messages ───────────────────────────────────────────────────────────────
if (isset($_GET['action']) && $_GET['action'] === 'read') {
  header('Content-Type: application/json');
  $conv_id = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : 0;
  if (!$conv_id) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id diperlukan']);
    exit;
  }

  $check = mysqli_query(
    $koneksi,
    "SELECT id FROM conversations WHERE id='$conv_id' AND host_id='$host_id'"
  );
  if (!$check || mysqli_num_rows($check) === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
  }

  $result = mysqli_query(
    $koneksi,
    "SELECT m.id, m.sender_id, m.message, m.image_paths, m.is_read, m.sent_at,
            u.nama AS sender_name
     FROM messages m
     JOIN users u ON u.id = m.sender_id
     WHERE m.conversation_id = '$conv_id'
     ORDER BY m.sent_at ASC"
  );
  if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($koneksi)]);
    exit;
  }

  $messages = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = [
      'id'          => (int) $row['id'],
      'sender_id'   => $row['sender_id'],
      'property_name' => $row['property_name'],
      'sender_name' => $row['sender_name'],
      'is_me'       => ($row['sender_id'] == $host_id),
      'message'     => $row['message'],
      'images'      => $row['image_paths'] ? json_decode($row['image_paths'], true) : [],
      'is_read'     => (bool) $row['is_read'],
      'sent_at'     => $row['sent_at'],
      'time_label'  => date('H:i', strtotime($row['sent_at'])),
    ];
  }
  echo json_encode($messages);
  exit;
}

// ── POST send ─────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
  header('Content-Type: application/json');

  $conv_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : 0;
  $message = isset($_POST['message']) ? trim($_POST['message']) : '';

  if (!$conv_id) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id diperlukan']);
    exit;
  }

  $check = mysqli_query(
    $koneksi,
    "SELECT id FROM conversations WHERE id='$conv_id' AND host_id='$host_id'"
  );
  if (!$check || mysqli_num_rows($check) === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
  }

  $image_paths = [];
  $upload_dir  = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/chat_images/';
  if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

  if (!empty($_FILES['images']['name'][0])) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
      if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
      if (!in_array(mime_content_type($tmp), $allowed)) continue;
      if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024) continue;
      $ext      = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
      $filename = 'chat_' . uniqid() . '.' . strtolower($ext);
      if (move_uploaded_file($tmp, $upload_dir . $filename))
        $image_paths[] = '/teman_singgah/assets/uploads/chat_images/' . $filename;
    }
  }

  if (!$message && empty($image_paths)) {
    http_response_code(400);
    echo json_encode(['error' => 'Pesan kosong']);
    exit;
  }

  $msg_val = $message ? "'" . mysqli_real_escape_string($koneksi, $message) . "'" : 'NULL';
  $img_val = !empty($image_paths)
    ? "'" . mysqli_real_escape_string($koneksi, json_encode($image_paths)) . "'"
    : 'NULL';

  $insert = mysqli_query(
    $koneksi,
    "INSERT INTO messages (conversation_id, sender_id, message, image_paths)
     VALUES ('$conv_id','$host_id',$msg_val,$img_val)"
  );
  if (!$insert) {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($koneksi)]);
    exit;
  }

  mysqli_query($koneksi, "UPDATE conversations SET last_message_at = NOW() WHERE id='$conv_id'");

  echo json_encode([
    'success'    => true,
    'message_id' => mysqli_insert_id($koneksi),
    'images'     => $image_paths,
    'time_label' => date('H:i'),
  ]);
  exit;
}

// ── POST mark_read ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
  header('Content-Type: application/json');
  $conv_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : 0;
  if (!$conv_id) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id diperlukan']);
    exit;
  }

  $check = mysqli_query(
    $koneksi,
    "SELECT id FROM conversations WHERE id='$conv_id' AND host_id='$host_id'"
  );
  if (!$check || mysqli_num_rows($check) === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
  }

  mysqli_query(
    $koneksi,
    "UPDATE messages SET is_read=1
     WHERE conversation_id='$conv_id'
       AND sender_id != '$host_id'
       AND is_read=0"
  );
  echo json_encode(['success' => true]);
  exit;
}
?>

<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Pesan | Teman Singgah</title>
  <link rel="icon" href="../../../assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="../../../components/root.css" />
  <link rel="stylesheet" href="../../../components/navbar.css" />
  <link rel="stylesheet" href="../../../popups/auth.css" />
  <link rel="stylesheet" href="../styles/messages.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
</head>

<body>
  <header class="navbar">
    <nav class="navbar-container">
      <a href="reservations.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="../../../assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
        <img src="../../../assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
      </div>
      <ul class="nav-menu">
        <li class="nav-item"><a href="reservations.php" class="nav-link">Reservasi</a></li>
        <li class="nav-item"><a href="calendar_router.php" class="nav-link">Kalender</a></li>
        <li class="nav-item"><a href="listing.php" class="nav-link">Listing</a></li>
        <li class="nav-item"><a href="messages.php" class="nav-link active">Pesan</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <?php include $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/components/navbar_profile_host.php'; ?>
    </nav>
  </header>

  <main class="main-content">
    <aside class="messages-sidebar">
      <div class="sidebar-header">
        <h2 class="sidebar-title">Pesan</h2>
        <div class="sidebar-actions">
          <button class="sidebar-button" aria-label="Cari"><i class="ph-bold ph-magnifying-glass"></i></button>
        </div>
      </div>
      <div class="sidebar-filter">
        <button class="filter-item active">Semua</button>
        <button class="filter-item">Belum Dibaca</button>
      </div>
      <div class="thread-list"></div>
    </aside>

    <section class="chat-area">
      <div class="chat-header">
        <div class="chat-avatar"><i class="ph-bold ph-user"></i></div>
        <div class="chat-header-info">
          <span class="chat-name">Pilih percakapan</span>
          <span class="chat-property-label"></span>
        </div>
        <button class="chat-detail-button" aria-label="Detail"><i class="ph-bold ph-caret-right"></i></button>
      </div>

      <div class="chat-messages">
        <div class="chat-empty-state">
          <div class="empty-icon-wrap"><i class="ph-bold ph-chats"></i></div>
          <p>Belum ada percakapan</p>
          <span>Pilih percakapan di sebelah kiri</span>
        </div>
      </div>

      <div class="chat-input-container">
        <div class="image-preview-container" id="imagePreview"></div>
        <template id="previewItemTemplate">
          <div class="preview-item">
            <img class="preview-image" src="" alt="preview" />
            <button class="remove-preview"><i class="ph-bold ph-x"></i></button>
          </div>
        </template>
        <template id="bubbleImageTemplate">
          <div class="bubble bubble-image-only">
            <div class="bubble-image-grid"></div>
          </div>
        </template>
        <template id="bubbleTextTemplate">
          <div class="bubble">
            <p class="bubble-text"></p>
          </div>
        </template>
        <div class="chat-input-box">
          <textarea class="chat-input" placeholder="Tulis pesan ke tamu..." rows="1"></textarea>
          <div class="chat-input-actions">
            <label class="attach-button" aria-label="Lampirkan gambar">
              <i class="ph-bold ph-image"></i>
              <input type="file" accept="image/*" style="display: none" multiple />
            </label>
            <button class="send-button" aria-label="Kirim"><i class="ph-bold ph-arrow-up"></i></button>
          </div>
        </div>
      </div>
    </section>
  </main>

  <script>
    const PAGE_URL = '/teman_singgah/host/pages/messages.php';

    // ── State ───────────────────────────────────────────────────────────────────
    let activeConversationId = null;
    let selectedFiles = [];
    let pollInterval = null;
    let showUnreadOnly = false;
    let allConversations = [];

    // ── DOM refs ────────────────────────────────────────────────────────────────
    const threadList       = document.querySelector('.thread-list');
    const chatMessages     = document.querySelector('.chat-messages');
    const chatName         = document.querySelector('.chat-name');
    const chatPropertyLabel = document.querySelector('.chat-property-label');
    const chatAvatar       = document.querySelector('.chat-header .chat-avatar');
    const chatInput        = document.querySelector('.chat-input');
    const sendButton       = document.querySelector('.send-button');
    const fileInput        = document.querySelector('.attach-button input');
    const imagePreview     = document.querySelector('#imagePreview');
    const previewTemplate  = document.querySelector('#previewItemTemplate');
    const bubbleImgTpl     = document.querySelector('#bubbleImageTemplate');
    const bubbleTextTpl    = document.querySelector('#bubbleTextTemplate');
    const filterButtons    = document.querySelectorAll('.filter-item');

    // ── Filter ──────────────────────────────────────────────────────────────────
    filterButtons.forEach(btn => {
      btn.addEventListener('click', () => {
        filterButtons.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        showUnreadOnly = btn.textContent.trim() === 'Belum Dibaca';
        renderThreadList(allConversations);
      });
    });

    // ── Load conversation list ──────────────────────────────────────────────────
    function loadConversations() {
      fetch(`${PAGE_URL}?action=conversations`)
        .then(r => r.json())
        .then(data => {
          allConversations = data;
          renderThreadList(data);
        });
    }

    function renderThreadList(data) {
      const filtered = showUnreadOnly ? data.filter(c => c.unread_count > 0) : data;
      threadList.innerHTML = '';

      if (filtered.length === 0) {
        threadList.innerHTML = '<p style="padding:1rem;color:var(--color-muted,#aaa);font-size:.85rem">Tidak ada percakapan</p>';
        return;
      }

      filtered.forEach(conv => {
        const item = document.createElement('div');
        item.className = 'thread-item' + (conv.id === activeConversationId ? ' active' : '');
        item.dataset.id = conv.id;

        const avatarHtml = conv.guest_photo
          ? `<img src="${conv.guest_photo}" class="thread-avatar-img" alt="${conv.guest_name}" />`
          : `<div class="thread-avatar">${conv.guest_initial}</div>`;

        item.innerHTML = `
          ${avatarHtml}
          <div class="thread-info">
            <div class="thread-header">
              <span class="thread-name">${conv.guest_name}</span>
              <span class="thread-time">${conv.time_label}</span>
            </div>
            <span class="thread-preview">${conv.last_preview}</span>
            ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ''}
          </div>
        `;

        item.addEventListener('click', () => openConversation(conv));
        threadList.appendChild(item);
      });
    }

    // ── Open conversation ───────────────────────────────────────────────────────
    function openConversation(conv) {
      activeConversationId = conv.id;

      // highlight sidebar
      document.querySelectorAll('.thread-item').forEach(el => {
        el.classList.toggle('active', parseInt(el.dataset.id) === conv.id);
      });

      // update header
      if (conv.guest_photo) {
        chatAvatar.innerHTML = `<img src="${conv.guest_photo}" style="width:100%;height:100%;object-fit:cover;border-radius:50%" />`;
      } else {
        chatAvatar.textContent = conv.guest_initial;
      }
      chatName.textContent = conv.guest_name;
      if (chatPropertyLabel) chatPropertyLabel.textContent = conv.property_name ?? '';

      loadMessages(conv.id);
      markRead(conv.id);

      // refresh unread badge in sidebar
      const item = threadList.querySelector(`[data-id="${conv.id}"]`);
      if (item) {
        const badge = item.querySelector('.unread-badge');
        if (badge) badge.remove();
      }

      // poll every 5s
      clearInterval(pollInterval);
      pollInterval = setInterval(() => {
        if (activeConversationId) loadMessages(activeConversationId, true);
      }, 5000);
    }

    // ── Load messages ───────────────────────────────────────────────────────────
    let lastMessageCount = 0;

    function loadMessages(convId, silent = false) {
      fetch(`${PAGE_URL}?action=read&conversation_id=${convId}`)
        .then(r => r.json())
        .then(messages => {
          if (!silent || messages.length !== lastMessageCount) {
            lastMessageCount = messages.length;
            renderMessages(messages);
          }
        });
    }

    function renderMessages(messages) {
      chatMessages.innerHTML = '';

      if (messages.length === 0) {
        chatMessages.innerHTML = `
          <div class="chat-empty-state">
            <div class="empty-icon-wrap"><i class="ph-bold ph-chats"></i></div>
            <p>Belum ada pesan</p>
            <span>Mulai percakapan dengan tamu</span>
          </div>`;
        return;
      }

      messages.forEach(msg => appendBubble(msg));
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function appendBubble(msg) {
      const wrapper = document.createElement('div');
      wrapper.className = 'bubble-wrapper ' + (msg.is_me ? 'me' : 'you');

      if (msg.images && msg.images.length > 0) {
        const clone = bubbleImgTpl.content.cloneNode(true);
        const bubble = clone.querySelector('.bubble');
        bubble.classList.add(msg.is_me ? 'me' : 'you');
        const grid = clone.querySelector('.bubble-image-grid');
        msg.images.forEach(src => {
          const img = document.createElement('img');
          img.src = src;
          img.className = 'bubble-image';
          grid.appendChild(img);
        });
        const time = document.createElement('span');
        time.className = 'bubble-time';
        time.textContent = msg.time_label;
        clone.querySelector('.bubble').appendChild(time);
        wrapper.appendChild(clone);
      }

      if (msg.message) {
        const clone = bubbleTextTpl.content.cloneNode(true);
        const bubble = clone.querySelector('.bubble');
        bubble.classList.add(msg.is_me ? 'me' : 'you');
        clone.querySelector('.bubble-text').textContent = msg.message;
        const time = document.createElement('span');
        time.className = 'bubble-time';
        time.textContent = msg.time_label;
        bubble.appendChild(time);
        wrapper.appendChild(clone);
      }

      chatMessages.appendChild(wrapper);
    }

    // ── Mark read ───────────────────────────────────────────────────────────────
    function markRead(convId) {
      const fd = new FormData();
      fd.append('action', 'mark_read');
      fd.append('conversation_id', convId);
      fetch(PAGE_URL, { method: 'POST', body: fd });
    }

    // ── Send message ────────────────────────────────────────────────────────────
    function sendMessage() {
      const text = chatInput.value.trim();
      if (!activeConversationId) return;
      if (!text && selectedFiles.length === 0) return;

      const fd = new FormData();
      fd.append('action', 'send');
      fd.append('conversation_id', activeConversationId);
      if (text) fd.append('message', text);
      selectedFiles.forEach(f => fd.append('images[]', f));

      // optimistic UI
      if (selectedFiles.length > 0) {
        const clone = bubbleImgTpl.content.cloneNode(true);
        const bubble = clone.querySelector('.bubble');
        bubble.classList.add('me');
        const grid = clone.querySelector('.bubble-image-grid');
        selectedFiles.forEach(file => {
          const reader = new FileReader();
          reader.onload = e => {
            const img = document.createElement('img');
            img.src = e.target.result;
            img.className = 'bubble-image';
            grid.appendChild(img);
          };
          reader.readAsDataURL(file);
        });
        chatMessages.appendChild(clone);
      }
      if (text) {
        const clone = bubbleTextTpl.content.cloneNode(true);
        const bubble = clone.querySelector('.bubble');
        bubble.classList.add('me');
        clone.querySelector('.bubble-text').textContent = text;
        chatMessages.appendChild(clone);
      }
      chatMessages.scrollTop = chatMessages.scrollHeight;

      chatInput.value = '';
      chatInput.style.height = 'auto';
      selectedFiles = [];
      imagePreview.innerHTML = '';

      fetch(PAGE_URL, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(() => {
          loadConversations();
          loadMessages(activeConversationId);
        });
    }

    // ── Image attach ────────────────────────────────────────────────────────────
    fileInput.addEventListener('change', () => {
      Array.from(fileInput.files).forEach(file => {
        selectedFiles.push(file);
        const reader = new FileReader();
        reader.onload = e => {
          const clone = previewTemplate.content.cloneNode(true);
          const img = clone.querySelector('.preview-image');
          const removeBtn = clone.querySelector('.remove-preview');
          img.src = e.target.result;
          removeBtn.addEventListener('click', () => {
            selectedFiles = selectedFiles.filter(f => f !== file);
            img.closest('.preview-item').remove();
          });
          imagePreview.appendChild(clone);
        };
        reader.readAsDataURL(file);
      });
      fileInput.value = '';
    });

    // ── Textarea auto-resize ────────────────────────────────────────────────────
    chatInput.addEventListener('input', () => {
      chatInput.style.height = 'auto';
      chatInput.style.height = chatInput.scrollHeight + 'px';
    });

    chatInput.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });

    sendButton.addEventListener('click', sendMessage);

    // ── Init ────────────────────────────────────────────────────────────────────
    loadConversations();
    setInterval(loadConversations, 10000); // refresh sidebar tiap 10s
  </script>

  <script src="../../../components/navbar.js"></script>
  <script src="../../../popups/auth.js"></script>
</body>
</html>