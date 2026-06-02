<?php
session_start();
error_log("save_rooms.php session_id: " . session_id());
header('Content-Type: application/json');

$input = json_decode($_POST['rooms_json'] ?? '', true);

if (!is_array($input)) {
    echo json_encode(['status' => 'error', 'message' => 'Data rooms tidak valid.']);
    exit;
}

$uploadDir = '../../../uploads/rooms/';
if (!is_dir($uploadDir))
    mkdir($uploadDir, 0755, true);

$clean_rooms = [];
foreach ($input as $i => $r) {
    if (empty($r['nama']) || empty($r['harga_malam']))
        continue;

    $foto = $r['foto'] ?? '';

    if (!empty($_FILES["foto_$i"]["tmp_name"])) {
        $mime = mime_content_type($_FILES["foto_$i"]["tmp_name"]);
        $ext = strtolower(pathinfo($_FILES["foto_$i"]["name"], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif']) || $ext === 'jfif') {
            $ext = 'jpg';
            $filename = uniqid('room_') . '.' . $ext;
            move_uploaded_file($_FILES["foto_$i"]["tmp_name"], $uploadDir . $filename);
            $foto = $filename;
        }
    }

    $clean_rooms[] = [
        'nama' => htmlspecialchars(trim($r['nama']), ENT_QUOTES, 'UTF-8'),
        'deskripsi' => htmlspecialchars(trim($r['deskripsi'] ?? ''), ENT_QUOTES, 'UTF-8'),
        'ukuran_m2' => isset($r['ukuran_m2']) ? (int) $r['ukuran_m2'] : null,
        'max_tamu' => isset($r['max_tamu']) ? max(1, (int) $r['max_tamu']) : 1,
        'stok' => isset($r['stok']) ? max(1, (int)$r['stok']) : 1,
        'harga_malam' => max(0, (float) $r['harga_malam']),
        'fasilitas' => isset($r['fasilitas']) && is_array($r['fasilitas'])
            ? array_map('strval', $r['fasilitas'])
            : [],
        'foto' => $foto,
    ];
}

$_SESSION['onboarding']['rooms'] = $clean_rooms;
echo json_encode(['status' => 'ok', 'saved' => count($clean_rooms)]);