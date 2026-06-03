<?php
session_start();
require_once '../../koneksi.php';

header('Content-Type: application/json');

$listing_id = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;
$checkin    = isset($_GET['checkin'])    ? $_GET['checkin']          : '';
$checkout   = isset($_GET['checkout'])  ? $_GET['checkout']         : '';

if (!$listing_id || !$checkin || !$checkout) {
    echo json_encode(['error' => 'Parameter kurang']);
    exit;
}

function parseDate($str) {
    $parts = explode('-', $str);
    if (count($parts) === 3 && strlen($parts[2]) === 4) {
        return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
    }
    return $str;
}

$checkin_db  = parseDate($checkin);
$checkout_db = parseDate($checkout);

$rooms_result = mysqli_query($koneksi, "SELECT id, nama, stok FROM listing_rooms WHERE listing_id = $listing_id ORDER BY urutan ASC, id ASC");
$availability = [];

while ($room = mysqli_fetch_assoc($rooms_result)) {
    $room_id = (int)$room['id'];
    $stok    = (int)$room['stok'];

    $sql = "SELECT COUNT(*) AS booked FROM bookings 
            WHERE listing_id = $listing_id 
            AND room_id = $room_id
            AND status IN ('menunggu','dikonfirmasi')
            AND checkin < '$checkout_db'
            AND checkout > '$checkin_db'";

    $res    = mysqli_query($koneksi, $sql);
    $booked = $res ? (int)mysqli_fetch_assoc($res)['booked'] : 0;
    $sisa   = max(0, $stok - $booked);

    $availability[$room_id] = [
        'stok'     => $stok,
        'booked'   => $booked,
        'sisa'     => $sisa,
        'tersedia' => $sisa > 0,
    ];
}

echo json_encode(['success' => true, 'availability' => $availability]);