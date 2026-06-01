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

mysqli_query($koneksi, "
    UPDATE messages
    SET is_read = 1
    WHERE conversation_id = '$conv_id'
      AND sender_id != '$guest_id'
      AND is_read = 0
");

echo json_encode([
    'success'       => true,
    'rows_affected' => mysqli_affected_rows($koneksi),
]);