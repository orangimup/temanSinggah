<?php
session_start();
include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /teman_singgah/index.php");
    exit;
}

$nama = trim($_POST['nama'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi kosong
if (empty($nama) || empty($email) || empty($password)) {
    header("Location: /teman_singgah/index.php?auth=daftar&error=field_kosong");
    exit;
}

// Validasi email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: /teman_singgah/index.php?auth=daftar&error=email_invalid");
    exit;
}

// Validasi panjang password
if (strlen($password) < 8) {
    header("Location: /teman_singgah/index.php?auth=daftar&error=password_pendek");
    exit;
}

// Cek email duplikat
$cek = mysqli_prepare($koneksi, "SELECT id FROM users WHERE email = ?");
mysqli_stmt_bind_param($cek, "s", $email);
mysqli_stmt_execute($cek);
mysqli_stmt_store_result($cek);

if (mysqli_stmt_num_rows($cek) > 0) {
    header("Location: /teman_singgah/index.php?auth=daftar&error=email_exists");
    exit;
}
mysqli_stmt_close($cek);

// Generate user_id
$user_id = 'USR-' . strtoupper(substr(uniqid(), -6));
$hash = password_hash($password, PASSWORD_DEFAULT);
$role = 'User';
$status = 'Aktif';

$stmt = mysqli_prepare($koneksi, "INSERT INTO users (user_id, nama, email, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "ssssss", $user_id, $nama, $email, $hash, $role, $status);

if (mysqli_stmt_execute($stmt)) {
    header("Location: /teman_singgah/index.php?auth=login&success=registered");
} else {
    header("Location: /teman_singgah/index.php?auth=daftar&error=gagal");
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
exit;
?>