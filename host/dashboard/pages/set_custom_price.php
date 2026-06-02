<?php
header('Content-Type: application/json');
require_once '../../../koneksi.php';

$data = json_decode(file_get_contents('php://input'), true);

$listing_id = $data['listing_id'] ?? 0;
$date = $data['date'] ?? '';
$price = $data['price'] ?? 0;
$room_id = $data['room_id'] ?? null;

if (!$listing_id || !$date || !$price) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
    exit;
}

$checkSql = "SELECT id FROM date_prices WHERE listing_id = $listing_id AND price_date = '$date'";
if ($room_id) {
    $checkSql .= " AND (room_id = $room_id OR room_id IS NULL)";
}
$check = mysqli_query($koneksi, $checkSql);

if (mysqli_num_rows($check) > 0) {
    $sql = "UPDATE date_prices SET custom_price = $price WHERE listing_id = $listing_id AND price_date = '$date'";
    if ($room_id) {
        $sql .= " AND room_id = $room_id";
    }
} else {
    $roomValue = $room_id ? $room_id : 'NULL';
    $sql = "INSERT INTO date_prices (listing_id, price_date, custom_price, room_id) 
            VALUES ($listing_id, '$date', $price, $roomValue)";
}
mysqli_query($koneksi, $sql);

echo json_encode(['success' => true]);
?>