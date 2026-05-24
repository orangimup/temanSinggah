<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['tipe_properti'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit();
}

$allowed = ['apartemen', 'hotel', 'cabin', 'rumah', 'tradisional', 'villa'];
if (!in_array($data['tipe_properti'], $allowed)) {
    echo json_encode(['status' => 'error', 'message' => 'Tipe properti tidak valid']);
    exit();
}

$_SESSION['onboarding']['tipe_properti'] = $data['tipe_properti'];

echo json_encode(['status' => 'ok']);