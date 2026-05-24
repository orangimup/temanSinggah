<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

$fields = ['max_tamu', 'kamar_tidur', 'tempat_tidur', 'kamar_mandi'];
foreach ($fields as $field) {
    if (!isset($data[$field])) {
        echo json_encode(['status' => 'error', 'message' => 'Data tidak lengkap']);
        exit();
    }
}

$_SESSION['onboarding']['max_tamu']     = max(1, min(16, (int) $data['max_tamu']));
$_SESSION['onboarding']['kamar_tidur']  = max(1, min(8, (int) $data['kamar_tidur']));
$_SESSION['onboarding']['tempat_tidur'] = max(1, min(8, (int) $data['tempat_tidur']));
$_SESSION['onboarding']['kamar_mandi']  = max(1, min(8, (int) $data['kamar_mandi']));

echo json_encode(['status' => 'ok']);