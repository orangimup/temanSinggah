<?php
header('Content-Type: application/json');
require_once '../../../koneksi.php';

$data = json_decode(file_get_contents('php://input'), true);

$listing_id = $data['listing_id'] ?? 0;
$date = $data['date'] ?? '';
$action = $data['action'] ?? '';
$reason = $data['reason'] ?? 'manual';
$room_id = $data['room_id'] ?? null;

if (!$listing_id || !$date || !in_array($action, ['block', 'unblock'])) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
    exit;
}

if ($action === 'block') {
    $checkSql = "SELECT id FROM blocked_dates WHERE listing_id = $listing_id AND blocked_date = '$date' AND unlocked_at IS NULL";
    if ($room_id) {
        $checkSql .= " AND (room_id = $room_id OR room_id IS NULL)";
    }
    $check = mysqli_query($koneksi, $checkSql);
    
    if (mysqli_num_rows($check) == 0) {
        $roomValue = $room_id ? $room_id : 'NULL';
        $sql = "INSERT INTO blocked_dates (listing_id, blocked_date, room_id, reason, blocked_by) 
                VALUES ($listing_id, '$date', $roomValue, '$reason', 'host')";
        mysqli_query($koneksi, $sql);
    }
} else {
    $sql = "UPDATE blocked_dates SET unlocked_at = NOW() 
            WHERE listing_id = $listing_id AND blocked_date = '$date' AND unlocked_at IS NULL";
    if ($room_id) {
        $sql .= " AND (room_id = $room_id OR room_id IS NULL)";
    }
    mysqli_query($koneksi, $sql);
}

echo json_encode(['success' => true]);
?>