<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['tipe_privasi'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit();
}

$allowed = ['seluruh', 'kamar'];
if (!in_array($data['tipe_privasi'], $allowed)) {
    echo json_encode(['status' => 'error', 'message' => 'Tipe privasi tidak valid']);
    exit();
}

$_SESSION['onboarding']['tipe_privasi'] = $data['tipe_privasi'];

echo json_encode(['status' => 'ok']);