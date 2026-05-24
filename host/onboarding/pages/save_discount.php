<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

$allowed = ['tamu_baru', 'mingguan', 'bulanan'];
$diskon = [];

if (isset($data['diskon']) && is_array($data['diskon'])) {
    foreach ($data['diskon'] as $item) {
        if (in_array($item, $allowed)) {
            $diskon[] = $item;
        }
    }
}

$_SESSION['onboarding']['diskon'] = $diskon;

echo json_encode(['status' => 'ok']);