<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/koneksi.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method tidak diizinkan']);
    exit;
}

$guest_id = $_SESSION['user_id'];
$conv_id  = isset($_POST['conversation_id']) ? (int)$_POST['conversation_id'] : 0;
$message  = isset($_POST['message']) ? trim($_POST['message']) : '';

if (!$conv_id) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id diperlukan']);
    exit;
}

$check = mysqli_query($koneksi, "
    SELECT id FROM conversations WHERE id = '$conv_id' AND guest_id = '$guest_id'
");
if (!$check || mysqli_num_rows($check) === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Akses ditolak']);
    exit;
}

$image_paths = [];
$upload_dir  = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/chat_images/';

if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

if (!empty($_FILES['images']['name'][0])) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size      = 5 * 1024 * 1024;

    foreach ($_FILES['images']['tmp_name'] as $i => $tmp) {
        if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $mime = mime_content_type($tmp);
        if (!in_array($mime, $allowed_types)) continue;
        if ($_FILES['images']['size'][$i] > $max_size) continue;
        $ext      = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
        $filename = 'chat_' . uniqid() . '.' . strtolower($ext);
        if (move_uploaded_file($tmp, $upload_dir . $filename)) {
            $image_paths[] = '/teman_singgah/assets/uploads/chat_images/' . $filename;
        }
    }
}

if (!$message && empty($image_paths)) {
    http_response_code(400);
    echo json_encode(['error' => 'Pesan tidak boleh kosong']);
    exit;
}

$message_esc     = mysqli_real_escape_string($koneksi, $message ?: '');
$images_esc      = !empty($image_paths) ? mysqli_real_escape_string($koneksi, json_encode($image_paths)) : null;
$message_val     = $message_esc ? "'$message_esc'" : 'NULL';
$images_val      = $images_esc  ? "'$images_esc'"  : 'NULL';

$insert = mysqli_query($koneksi, "
    INSERT INTO messages (conversation_id, sender_id, message, image_paths)
    VALUES ('$conv_id', '$guest_id', $message_val, $images_val)
");

if (!$insert) {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($koneksi)]);
    exit;
}

$new_id = mysqli_insert_id($koneksi);

mysqli_query($koneksi, "
    UPDATE conversations SET last_message_at = NOW() WHERE id = '$conv_id'
");

echo json_encode([
    'success'    => true,
    'message_id' => $new_id,
    'images'     => $image_paths,
    'time_label' => date('H:i'),
]);