<?php
// host/onboarding/pages/save_policies.php
session_start();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'Data tidak valid.']);
    exit;
}

$valid_pembatalan = [
    'Gratis hingga 24 jam sebelum check-in',
    'Gratis hingga 48 jam sebelum check-in',
    'Gratis hingga 7 hari sebelum check-in',
    'Tidak dapat dibatalkan',
    'Bisa dibatalkan kapan saja',
];

// Validasi format waktu sederhana (HH:MM)
$checkin  = preg_match('/^\d{2}:\d{2}$/', $input['jam_checkin']  ?? '') ? $input['jam_checkin']  : '14:00';
$checkout = preg_match('/^\d{2}:\d{2}$/', $input['jam_checkout'] ?? '') ? $input['jam_checkout'] : '12:00';

$pembatalan = in_array($input['kebijakan_pembatalan'] ?? '', $valid_pembatalan)
    ? $input['kebijakan_pembatalan']
    : $valid_pembatalan[0];

$_SESSION['onboarding']['policies'] = [
    'jam_checkin'          => $checkin,
    'jam_checkout'         => $checkout,
    'kebijakan_pembatalan' => $pembatalan,
    'boleh_hewan'          => isset($input['boleh_hewan'])   ? (int)(bool)$input['boleh_hewan']   : 0,
    'boleh_merokok'        => isset($input['boleh_merokok']) ? (int)(bool)$input['boleh_merokok'] : 0,
    'boleh_anak'           => isset($input['boleh_anak'])    ? (int)(bool)$input['boleh_anak']    : 1,
    'catatan_tambahan'     => htmlspecialchars(trim($input['catatan_tambahan'] ?? ''), ENT_QUOTES, 'UTF-8'),
];

echo json_encode(['status' => 'ok']);