<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/koneksi.php';

$guest_id = $_SESSION['user_id'];

if (isset($_GET['action']) && $_GET['action'] === 'conversations') {
  header('Content-Type: application/json');
  $sql = "
        SELECT c.id, c.property_name, c.last_message_at,
               h.nama AS host_name, h.user_id AS host_id, h.photo AS host_photo,
               m.message AS last_message, m.image_paths AS last_image_paths, m.sender_id AS last_sender_id,
               (SELECT COUNT(*) FROM messages WHERE conversation_id = c.id AND sender_id != '$guest_id' AND is_read = 0) AS unread_count
        FROM conversations c
        JOIN users h ON h.user_id = c.host_id
        LEFT JOIN messages m ON m.id = (SELECT MAX(id) FROM messages WHERE conversation_id = c.id)
        WHERE c.guest_id = '$guest_id'
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
    $preview = ($row['last_sender_id'] === $guest_id) ? 'Anda: ' : '';
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
      'id' => (int) $row['id'],
      'host_name' => $row['host_name'],
      'host_id' => $row['host_id'],
      'host_initial' => strtoupper(substr($row['host_name'], 0, 1)),
      'host_photo' => $row['host_photo'],
      'property_name' => $row['property_name'],
      'last_preview' => $preview,
      'time_label' => $time_label,
      'unread_count' => (int) $row['unread_count'],
    ];
  }
  echo json_encode($conversations);
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'start') {
  header('Content-Type: application/json');
  $host_id = isset($_GET['host_id']) ? (int) $_GET['host_id'] : 0;
  $listing_id = isset($_GET['listing_id']) ? (int) $_GET['listing_id'] : 0;
  if (!$host_id) {
    http_response_code(400);
    echo json_encode(['error' => 'host_id diperlukan']);
    exit;
  }
  $listing_cond = $listing_id ? "AND listing_id = '$listing_id'" : "";
  $check = mysqli_query($koneksi, "SELECT id FROM conversations WHERE guest_id = '$guest_id' AND host_id = '$host_id' $listing_cond LIMIT 1");
  if ($row = mysqli_fetch_assoc($check)) {
    echo json_encode(['conversation_id' => (int) $row['id']]);
    exit;
  }
  $prop_name = '';
  if ($listing_id) {
    $r = mysqli_query($koneksi, "SELECT judul FROM listings WHERE id = '$listing_id' LIMIT 1");
    if ($r && $pr = mysqli_fetch_assoc($r))
      $prop_name = $pr['judul'];
  }
  $prop_esc = mysqli_real_escape_string($koneksi, $prop_name);
  $listing_val = $listing_id ?: 'NULL';
  mysqli_query($koneksi, "INSERT INTO conversations (guest_id, host_id, listing_id, property_name, last_message_at) VALUES ('$guest_id', '$host_id', $listing_val, '$prop_esc', NOW())");
  echo json_encode(['conversation_id' => mysqli_insert_id($koneksi)]);
  exit;
}

if (isset($_GET['action']) && $_GET['action'] === 'read') {
  header('Content-Type: application/json');
  $conv_id = isset($_GET['conversation_id']) ? (int) $_GET['conversation_id'] : 0;
  if (!$conv_id) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id diperlukan']);
    exit;
  }
  $check = mysqli_query($koneksi, "SELECT id FROM conversations WHERE id='$conv_id' AND guest_id='$guest_id'");
  if (!$check || mysqli_num_rows($check) === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
  }
  $result = mysqli_query($koneksi, "
        SELECT m.id, m.sender_id, m.message, m.image_paths, m.is_read, m.sent_at, u.nama AS sender_name
        FROM messages m JOIN users u ON u.user_id = m.sender_id
        WHERE m.conversation_id = '$conv_id' ORDER BY m.sent_at ASC
    ");
  if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($koneksi)]);
    exit;
  }
  $messages = [];
  while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = [
      'id' => (int) $row['id'],
      'sender_id' => $row['sender_id'],
      'sender_name' => $row['sender_name'],
      'is_me' => ($row['sender_id'] === $guest_id),
      'message' => $row['message'],
      'images' => $row['image_paths'] ? json_decode($row['image_paths'], true) : [],
      'is_read' => (bool) $row['is_read'],
      'sent_at' => $row['sent_at'],
      'time_label' => date('H:i', strtotime($row['sent_at'])),
    ];
  }
  echo json_encode($messages);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send') {
  header('Content-Type: application/json');
  $conv_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : 0;
  $message = isset($_POST['message']) ? trim($_POST['message']) : '';
  if (!$conv_id) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id diperlukan']);
    exit;
  }
  $check = mysqli_query($koneksi, "SELECT id FROM conversations WHERE id='$conv_id' AND guest_id='$guest_id'");
  if (!$check || mysqli_num_rows($check) === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
  }
  $image_paths = [];
  $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/chat_images/';
  if (!is_dir($upload_dir))
    mkdir($upload_dir, 0755, true);
  if (!empty($_FILES['images']['name'][0])) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
      if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK)
        continue;
      if (!in_array(mime_content_type($tmp), $allowed))
        continue;
      if ($_FILES['images']['size'][$i] > 5 * 1024 * 1024)
        continue;
      $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
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
  $img_val = !empty($image_paths) ? "'" . mysqli_real_escape_string($koneksi, json_encode($image_paths)) . "'" : 'NULL';
  $insert = mysqli_query($koneksi, "INSERT INTO messages (conversation_id, sender_id, message, image_paths) VALUES ('$conv_id','$guest_id',$msg_val,$img_val)");
  if (!$insert) {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($koneksi)]);
    exit;
  }
  mysqli_query($koneksi, "UPDATE conversations SET last_message_at = NOW() WHERE id='$conv_id'");
  echo json_encode(['success' => true, 'message_id' => mysqli_insert_id($koneksi), 'images' => $image_paths, 'time_label' => date('H:i')]);
  exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
  header('Content-Type: application/json');
  $conv_id = isset($_POST['conversation_id']) ? (int) $_POST['conversation_id'] : 0;
  if (!$conv_id) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id diperlukan']);
    exit;
  }
  $check = mysqli_query($koneksi, "SELECT id FROM conversations WHERE id='$conv_id' AND guest_id='$guest_id'");
  if (!$check || mysqli_num_rows($check) === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
  }
  mysqli_query($koneksi, "UPDATE messages SET is_read=1 WHERE conversation_id='$conv_id' AND sender_id!='$guest_id' AND is_read=0");
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
  <link rel="icon" href="/teman_singgah/assets/logo/logo_temansinggah.svg" />
  <link rel="stylesheet" href="/teman_singgah/components/root.css" />
  <link rel="stylesheet" href="/teman_singgah/components/navbar.css" />
  <link rel="stylesheet" href="/teman_singgah/popups/auth.css" />
  <link rel="stylesheet" href="/teman_singgah/user/styles/messages.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link
    href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap"
    rel="stylesheet" />
  <script type="module" src="https://unpkg.com/@phosphor-icons/web@2.1.1/src/index.js"></script>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon/fonts/remixicon.css" />
</head>

<body>
  <header class="navbar">
    <nav class="navbar-container">
      <a href="/teman_singgah/index.php" class="logo-link"></a>
      <div class="logo-section">
        <img src="/teman_singgah/assets/logo/logo_temansinggah.svg" alt="Logo Teman Singgah" class="logo-icon" />
        <img src="/teman_singgah/assets/logo/label_temansinggah.svg" alt="Brand Name Teman Singgah" class="logo-name" />
      </div>
      <ul class="nav-menu">
        <li class="nav-item"><a href="/teman_singgah/index.php" class="nav-link">Cari Penginapan</a></li>
        <li class="nav-item"><a href="/teman_singgah/user/pages/promo_deals.php" class="nav-link">Promo & Deals</a></li>
        <li class="nav-item"><a href="/teman_singgah/user/pages/become_host.php" class="nav-link">Jadi Host</a></li>
        <li class="nav-item"><a href="/teman_singgah/user/pages/about_us.php" class="nav-link">Tentang Kami</a></li>
        <div class="nav-indicator"></div>
      </ul>
      <div class="nav-right">
        <a href="/teman_singgah/host/onboarding/pages/about_place.html">
          <button class="ghost-button">Ganti ke host</button>
        </a>
        <div class="icon-buttons">
          <button class="icon-button profile" aria-label="Profile"><?= htmlspecialchars($userInitial) ?></button>
          <button class="icon-button hamburger" aria-label="Hamburger"><i class="ph-bold ph-list"></i></button>
        </div>
        <div id="hamburgerDropdown"></div>
        <div id="languagePopup"></div>
        <div id="authPopup"></div>
      </div>
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
          <div class="empty-icon-wrap">
            <i class="ph-bold ph-chats"></i>
          </div>
          <p>Belum ada percakapan</p>
          <span>Pilih percakapan di sebelah kiri atau mulai chat dengan host dari halaman properti</span>
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
          <textarea class="chat-input" placeholder="Tulis pesan ke host..." rows="1"></textarea>
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

  <script src="/teman_singgah/user/scripts/messages.js"></script>
  <script src="/teman_singgah/components/navbar.js"></script>
  <script src="/teman_singgah/popups/auth.js"></script>
</body>

</html>