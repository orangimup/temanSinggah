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
$rating     = (int) ($_POST['rating']     ?? 0);
$komentar   = trim($_POST['komentar']     ?? '');
$booking_id = isset($_POST['booking_id']) && (int)$_POST['booking_id'] > 0
                ? (int)$_POST['booking_id']
                : null;
$listing_id = (int) ($_POST['listing_id'] ?? 0);

// Validasi rating & komentar
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

// ── Kondisi 1: dari history (ada booking_id) ──
if ($booking_id !== null) {
    // Pastikan booking milik user & statusnya selesai, ambil listing_id dari sana
    $stmt = mysqli_prepare($koneksi, "
        SELECT id, listing_id FROM bookings
        WHERE id = ? AND user_id = ? AND status = 'selesai'
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 'ii', $booking_id, $user_id);
    mysqli_stmt_execute($stmt);
    $booking = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);

    if (!$booking) {
        echo json_encode(['success' => false, 'message' => 'Booking tidak ditemukan atau belum selesai.']);
        exit;
    }

    $listing_id = $booking['listing_id'];

    // Cek duplikat per booking
    $stmt = mysqli_prepare($koneksi, "SELECT id FROM reviews WHERE booking_id = ? AND user_id = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ii', $booking_id, $user_id);
    mysqli_stmt_execute($stmt);
    $dup = mysqli_stmt_get_result($stmt)->fetch_assoc();
    mysqli_stmt_close($stmt);

    if ($dup) {
        echo json_encode(['success' => false, 'message' => 'Anda sudah mengulas perjalanan ini.']);
        exit;
    }

// ── Kondisi 2: dari detail_card (hanya listing_id) ──
} else {
    if ($listing_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
        exit;
    }

    // Pastikan listing ada dan aktif
    $stmt = mysqli_prepare($koneksi, "SELECT id FROM listings WHERE id = ? AND status = 'aktif'");
    mysqli_stmt_bind_param($stmt, 'i', $listing_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    $found = mysqli_stmt_num_rows($stmt) > 0;
    mysqli_stmt_close($stmt);

    if (!$found) {
        echo json_encode(['success' => false, 'message' => 'Listing tidak ditemukan.']);
        exit;
    }
}

// ── Insert review ──
$stmt = mysqli_prepare($koneksi, "
    INSERT INTO reviews (booking_id, user_id, listing_id, rating, komentar, dibuat_pada)
    VALUES (?, ?, ?, ?, ?, NOW())
");
mysqli_stmt_bind_param($stmt, 'iiiis', $booking_id, $user_id, $listing_id, $rating, $komentar);

if (mysqli_stmt_execute($stmt)) {
    $review_id = mysqli_insert_id($koneksi);
    mysqli_stmt_close($stmt);
    echo json_encode([
        'success'   => true,
        'message'   => 'Ulasan berhasil disimpan.',
        'review_id' => $review_id,
    ]);
} else {
    mysqli_stmt_close($stmt);
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan ulasan. Silakan coba lagi.']);
}