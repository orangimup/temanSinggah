<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['lokasi']) || !isset($data['latitude']) || !isset($data['longitude'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
    exit();
}

$_SESSION['onboarding']['lokasi']    = trim($data['lokasi']);
$_SESSION['onboarding']['latitude']  = (float) $data['latitude'];
$_SESSION['onboarding']['longitude'] = (float) $data['longitude'];

echo json_encode(['status' => 'ok']);