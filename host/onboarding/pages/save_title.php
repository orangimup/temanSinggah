<?php
session_start();

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['judul']) || trim($data['judul']) === '') {
    echo json_encode(['status' => 'error', 'message' => 'Judul tidak boleh kosong']);
    exit();
}

$judul = trim($data['judul']);

if (mb_strlen($judul) > 100) {
    echo json_encode(['status' => 'error', 'message' => 'Judul maksimal 100 karakter']);
    exit();
}

$_SESSION['onboarding']['judul'] = $judul;

echo json_encode(['status' => 'ok']);