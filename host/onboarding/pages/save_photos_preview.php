<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['foto']) || !is_array($data['foto'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid']);
    exit();
}

$saved = $_SESSION['onboarding']['foto'] ?? [];
$cleaned = [];
foreach ($data['foto'] as $namaFile) {
    if (in_array($namaFile, $saved)) {
        $cleaned[] = $namaFile;
    }
}

$_SESSION['onboarding']['foto'] = $cleaned;

echo json_encode(['status' => 'ok']);