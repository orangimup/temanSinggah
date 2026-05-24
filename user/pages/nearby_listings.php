<?php
//hitung jarak user vs listings pakai Haversine, return JSON
session_start();
header('Content-Type: application/json');

$lat = isset($_GET['lat']) ? (float) $_GET['lat'] : null;
$lng = isset($_GET['lng']) ? (float) $_GET['lng'] : null;

if ($lat === null || $lng === null) {
    echo json_encode(['status' => 'error', 'message' => 'Koordinat tidak ditemukan']);
    exit;
}

require dirname(__DIR__, 2) . '/koneksi.php';

$sql = "
    SELECT
        l.id, l.judul, l.lokasi, l.harga_malam,
        lp.nama_file AS foto_cover,
        ROUND(AVG(r.rating), 1) AS rating_avg,
        (
            6371 * ACOS(
                COS(RADIANS(?)) * COS(RADIANS(l.latitude))
                * COS(RADIANS(l.longitude) - RADIANS(?))
                + SIN(RADIANS(?)) * SIN(RADIANS(l.latitude))
            )
        ) AS jarak_km
    FROM listings l
    LEFT JOIN listing_photos lp ON lp.listing_id = l.id AND lp.adalah_cover = 1
    LEFT JOIN bookings b ON b.listing_id = l.id
    LEFT JOIN reviews r ON r.booking_id = b.id
    WHERE l.status = 'aktif'
      AND l.latitude IS NOT NULL
      AND l.longitude IS NOT NULL
    GROUP BY l.id, l.judul, l.lokasi, l.harga_malam, lp.nama_file, l.latitude, l.longitude
    HAVING jarak_km <= 50
    ORDER BY jarak_km ASC
    LIMIT 9
";

$stmt = mysqli_prepare($koneksi, $sql);
mysqli_stmt_bind_param($stmt, 'ddd', $lat, $lng, $lat);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$rows = [];
while ($row = mysqli_fetch_assoc($result)) {
    $rows[] = [
        'id' => (int) $row['id'],
        'judul' => $row['judul'],
        'lokasi' => $row['lokasi'],
        'harga_malam' => (int) $row['harga_malam'],
        'foto_cover' => $row['foto_cover'],
        'rating_avg' => $row['rating_avg'],
        'jarak_km' => round($row['jarak_km'], 1),
    ];
}

echo json_encode(['status' => 'ok', 'data' => $rows]);