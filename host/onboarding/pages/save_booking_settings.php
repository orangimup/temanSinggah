<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['tipe_booking'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit();
}

$allowed = ['instan', 'permintaan'];
if (!in_array($data['tipe_booking'], $allowed)) {
    echo json_encode(['status' => 'error', 'message' => 'Tipe booking tidak valid']);
    exit();
}

$_SESSION['onboarding']['tipe_booking'] = $data['tipe_booking'];

echo json_encode(['status' => 'ok']);