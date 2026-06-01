<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_user.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/koneksi.php';

header('Content-Type: application/json');

$guest_id = $_SESSION['user_id'];
$conv_id  = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;

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

$result = mysqli_query($koneksi, "
    SELECT
        m.id,
        m.sender_id,
        m.message,
        m.image_paths,
        m.is_read,
        m.sent_at,
        u.nama AS sender_name
    FROM messages m
    JOIN users u ON u.user_id = m.sender_id
    WHERE m.conversation_id = '$conv_id'
    ORDER BY m.sent_at ASC
");

if (!$result) {
    http_response_code(500);
    echo json_encode(['error' => mysqli_error($koneksi)]);
    exit;
}

$messages = [];
while ($row = mysqli_fetch_assoc($result)) {
    $messages[] = [
        'id'          => (int)$row['id'],
        'sender_id'   => $row['sender_id'],
        'sender_name' => $row['sender_name'],
        'is_me'       => ($row['sender_id'] === $guest_id),
        'message'     => $row['message'],
        'images'      => $row['image_paths'] ? json_decode($row['image_paths'], true) : [],
        'is_read'     => (bool)$row['is_read'],
        'sent_at'     => $row['sent_at'],
        'time_label'  => date('H:i', strtotime($row['sent_at'])),
    ];
}

echo json_encode($messages);