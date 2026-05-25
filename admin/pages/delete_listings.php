<?php
/**
 * admin/pages/delete_listing.php
 * Hapus listing oleh Admin.
 * Dipanggil via fetch() POST dengan body JSON: { "id": 123 }
 */
session_start();
require_once '../../koneksi.php';

header('Content-Type: application/json');

/* Guard: hanya Admin */
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'Admin') {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$id   = (int)($body['id'] ?? 0);

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'ID tidak valid.']);
    exit;
}

/* Pastikan listing ada */
$check = mysqli_query($koneksi, "SELECT id FROM listings WHERE id = $id LIMIT 1");
if (!mysqli_fetch_assoc($check)) {
    echo json_encode(['status' => 'error', 'message' => 'Listing tidak ditemukan.']);
    exit;
}

/* Ambil daftar foto untuk dihapus dari disk */
$photos = [];
$qPhoto = mysqli_query($koneksi, "SELECT nama_file FROM listing_photos WHERE listing_id = $id");
while ($p = mysqli_fetch_assoc($qPhoto)) {
    $photos[] = $p['nama_file'];
}

/* Hapus dari DB */
$del = mysqli_query($koneksi, "DELETE FROM listings WHERE id = $id");
if (!$del) {
    echo json_encode(['status' => 'error', 'message' => 'Gagal menghapus dari database.']);
    exit;
}

/* Hapus file foto dari disk */
foreach ($photos as $nama) {
    $path = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/listings/' . $nama;
    if (file_exists($path)) @unlink($path);
}

echo json_encode(['status' => 'ok']);