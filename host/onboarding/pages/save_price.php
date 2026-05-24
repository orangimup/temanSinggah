<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['harga_malam'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit();
}

$harga = (int) $data['harga_malam'];

if ($harga < 100000 || $harga > 5000000) {
    echo json_encode(['status' => 'error', 'message' => 'Harga tidak valid']);
    exit();
}

$_SESSION['onboarding']['harga_malam'] = $harga;

echo json_encode(['status' => 'ok']);