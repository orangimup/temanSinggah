<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['deskripsi']) || trim($data['deskripsi']) === '') {
    echo json_encode(['status' => 'error', 'message' => 'Deskripsi tidak boleh kosong']);
    exit();
}

$deskripsi = trim($data['deskripsi']);

if (mb_strlen($deskripsi) > 500) {
    echo json_encode(['status' => 'error', 'message' => 'Deskripsi maksimal 500 karakter']);
    exit();
}

$_SESSION['onboarding']['deskripsi'] = $deskripsi;

echo json_encode(['status' => 'ok']);