<?php
session_start();

if (!isset($_FILES['foto'])) {
    echo json_encode(['status' => 'error', 'message' => 'Tidak ada foto yang diupload']);
    exit();
}

$uploadDir = '../../../assets/uploads/listings/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$allowed_ext = ['jpg', 'jpeg', 'png', 'webp'];
$uploaded = [];

foreach ($_FILES['foto']['tmp_name'] as $index => $tmp) {
    if ($_FILES['foto']['error'][$index] !== UPLOAD_ERR_OK) continue;

    $originalName = $_FILES['foto']['name'][$index];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext)) continue;

    $namaFile = uniqid('listing_', true) . '.' . $ext;
    $destination = $uploadDir . $namaFile;

    if (move_uploaded_file($tmp, $destination)) {
        $uploaded[] = $namaFile;
    }
}

if (count($uploaded) < 5) {
    echo json_encode(['status' => 'error', 'message' => 'Minimal 5 foto harus berhasil diupload']);
    exit();
}

$_SESSION['onboarding']['foto'] = $uploaded;

echo json_encode(['status' => 'ok']);