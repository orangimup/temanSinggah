<?php
header('Content-Type: application/json');
session_start();
require_once '../../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method tidak diizinkan.']);
    exit;
}

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Anda harus login terlebih dahulu.']);
    exit;
}

$user_id    = (int) $_SESSION['id'];
$listing_id = (int) ($_POST['listing_id'] ?? 0);
$rating     = (int) ($_POST['rating']     ?? 0);
$komentar   = trim($_POST['komentar']     ?? '');

if ($listing_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
    exit;
}
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating harus antara 1 sampai 5.']);
    exit;
}
if (mb_strlen($komentar) < 10) {
    echo json_encode(['success' => false, 'message' => 'Komentar terlalu pendek, minimal 10 karakter.']);
    exit;
}
if (mb_strlen($komentar) > 1000) {
    echo json_encode(['success' => false, 'message' => 'Komentar terlalu panjang, maksimal 1000 karakter.']);
    exit;
}

// Pastikan listing ada dan aktif
$stmt = mysqli_prepare($koneksi, "SELECT id FROM listings WHERE id = ? AND status = 'aktif'");
mysqli_stmt_bind_param($stmt, 'i', $listing_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
if (mysqli_stmt_num_rows($stmt) === 0) {
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => false, 'message' => 'Listing tidak ditemukan.']);
    exit;
}
mysqli_stmt_close($stmt);

// Simpan review — langsung tampil, tidak perlu status
$stmt = mysqli_prepare($koneksi, "
    INSERT INTO reviews (booking_id, user_id, listing_id, rating, komentar, dibuat_pada)
    VALUES (NULL, ?, ?, ?, ?, NOW())
");
mysqli_stmt_bind_param($stmt, 'iiis', $user_id, $listing_id, $rating, $komentar);

if (mysqli_stmt_execute($stmt)) {
    $review_id = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);
    echo json_encode([
        'success'   => true,
        'message'   => 'Ulasan berhasil disimpan.',
        'review_id' => $review_id
    ]);
} else {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ulasan. Silakan coba lagi.']);
}