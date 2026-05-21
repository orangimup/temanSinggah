<?php
header('Content-Type: application/json');
require_once '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Metode tidak valid.']);
    exit;
}

$nama     = trim($_POST['nama'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input
if (empty($nama) || empty($email) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Semua field wajib diisi.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['status' => 'error', 'message' => 'Password minimal 8 karakter.']);
    exit;
}

// Cek email sudah terdaftar
$cek = $conn->prepare("SELECT id_user FROM users WHERE email = ?");
$cek->bind_param('s', $email);
$cek->execute();
$cek->store_result();

if ($cek->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email sudah terdaftar.']);
    $cek->close();
    exit;
}
$cek->close();

// Hash password
$password_hash = password_hash($password, PASSWORD_BCRYPT);

// Insert user (id_user diisi otomatis oleh trigger)
$stmt = $conn->prepare("INSERT INTO users (nama, email, password_hash) VALUES (?, ?, ?)");
$stmt->bind_param('sss', $nama, $email, $password_hash);

if ($stmt->execute()) {
    echo json_encode([
        'status'  => 'success',
        'message' => 'Akun berhasil dibuat! Silakan masuk.'
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Gagal membuat akun. Coba lagi.']);
}

$stmt->close();
$conn->close();