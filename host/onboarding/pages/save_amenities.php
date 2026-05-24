<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

$allowed = [
    'wifi', 'tv', 'ac', 'dapur', 'mesin_cuci', 'parkir',
    'kolam_renang', 'p3k', 'pemadam', 'air_panas', 'ruang_kerja', 'hewan'
];

$fasilitas = [];
if (isset($data['fasilitas']) && is_array($data['fasilitas'])) {
    foreach ($data['fasilitas'] as $item) {
        if (in_array($item, $allowed)) {
            $fasilitas[] = $item;
        }
    }
}

$_SESSION['onboarding']['fasilitas'] = $fasilitas;

echo json_encode(['status' => 'ok']);