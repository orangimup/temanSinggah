<?php
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/auth/guard_host.php';

header('Content-Type: application/json');

$pdo = new PDO("mysql:host=localhost;dbname=teman_singgah;charset=utf8mb4", 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$host_id        = (int) ($_SESSION['id'] ?? 0);
$bank_name      = trim($_POST['bank_name'] ?? '');
$account_number = trim($_POST['account_number'] ?? '');
$account_name   = trim($_POST['account_name'] ?? '');

if (!$bank_name || !$account_number || !$account_name) {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi.']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET bank_name=?, account_number=?, account_name=? WHERE id=?");
$stmt->execute([$bank_name, $account_number, $account_name, $host_id]); 

$sync = $pdo->prepare("
    UPDATE payouts 
    SET bank_name = ?, account_number = ?, account_name = ?
    WHERE host_id = ? 
      AND status IN ('Dijadwalkan', 'Processing', 'Failed')
");
$sync->execute([$bank_name, $account_number, $account_name, $host_id]);

echo json_encode(['success' => true]);