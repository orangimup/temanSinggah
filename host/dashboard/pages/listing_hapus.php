<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_host.php';
require_once '../../../koneksi.php';

header('Content-Type: application/json');

$hostId = (int)($_SESSION['id'] ?? 0);

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
$id   = (int)($data['id']   ?? $_POST['id']   ?? 0);
$aksi =       $data['aksi'] ?? $_POST['aksi']  ?? '';

if (!$id || !$aksi) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap.']);
    exit;
}

$cek = mysqli_fetch_assoc(mysqli_query($koneksi,
    "SELECT id, status FROM listings WHERE id = $id AND host_id = $hostId LIMIT 1"));

if (!$cek) {
    echo json_encode(['status' => 'error', 'message' => 'Listing tidak ditemukan.']);
    exit;
}

if ($aksi === 'hapus') {

    /* 1. Hapus file foto dari disk */
    $qp = mysqli_query($koneksi,
        "SELECT nama_file FROM listing_photos WHERE listing_id = $id");
    while ($f = mysqli_fetch_assoc($qp)) {
        $path = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/listings/' . $f['nama_file'];
        if (file_exists($path)) @unlink($path);
    }

    /* 2. Hapus data terkait dari DB */
    mysqli_query($koneksi, "DELETE FROM listing_photos    WHERE listing_id = $id");
    mysqli_query($koneksi, "DELETE FROM listing_amenities WHERE listing_id = $id");
    mysqli_query($koneksi, "DELETE FROM listings          WHERE id = $id AND host_id = $hostId");

    echo json_encode(['status' => 'ok']);

} elseif ($aksi === 'nonaktif') {

    mysqli_query($koneksi,
        "UPDATE listings SET status = 'nonaktif' WHERE id = $id AND host_id = $hostId");
    echo json_encode(['status' => 'ok']);

} else {
    echo json_encode(['status' => 'error', 'message' => 'Aksi tidak dikenal.']);
}