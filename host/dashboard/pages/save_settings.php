<?php
header('Content-Type: application/json');
require_once '../../../koneksi.php';

$data = json_decode(file_get_contents('php://input'), true);

$listing_id = $data['listing_id'] ?? 0;
$field = $data['field'] ?? '';
$value = $data['value'] ?? 0;

$allowed = ['harga_malam', 'harga_akhir_pekan', 'min_malam', 'max_malam', 'jam_checkin', 'jam_checkout', 'diskon_mingguan', 'diskon_bulanan'];

if (!$listing_id || !in_array($field, $allowed)) {
    echo json_encode(['success' => false, 'message' => 'Parameter tidak valid']);
    exit;
}

$sql = "UPDATE listings SET $field = '$value' WHERE id = $listing_id";
mysqli_query($koneksi, $sql);

echo json_encode(['success' => true]);
?>