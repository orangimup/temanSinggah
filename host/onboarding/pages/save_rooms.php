<?php
// host/onboarding/pages/save_rooms.php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['rooms']) || !is_array($input['rooms'])) {
    echo json_encode(['status' => 'error', 'message' => 'Data rooms tidak valid.']);
    exit;
}

// Sanitasi tiap room sebelum disimpan ke session
$clean_rooms = [];
foreach ($input['rooms'] as $r) {
    if (empty($r['nama']) || empty($r['harga_malam'])) continue; // skip invalid

    $clean_rooms[] = [
        'nama'        => htmlspecialchars(trim($r['nama']),        ENT_QUOTES, 'UTF-8'),
        'deskripsi'   => htmlspecialchars(trim($r['deskripsi'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'ukuran_m2'   => isset($r['ukuran_m2'])  ? (int)$r['ukuran_m2']    : null,
        'max_tamu'    => isset($r['max_tamu'])    ? max(1, (int)$r['max_tamu']) : 1,
        'harga_malam' => max(0, (float)$r['harga_malam']),
        'fasilitas'   => isset($r['fasilitas']) && is_array($r['fasilitas'])
                            ? array_map('strval', $r['fasilitas'])
                            : [],
    ];
}

$_SESSION['onboarding']['rooms'] = $clean_rooms;

echo json_encode(['status' => 'ok', 'saved' => count($clean_rooms)]);