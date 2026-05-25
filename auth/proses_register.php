<?php
session_start();
include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /teman_singgah/index.php");
    exit;
}

$nama     = trim($_POST['nama'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$no_hp    = trim($_POST['no_hp'] ?? '');

// Validasi kosong
if (empty($nama) || empty($email) || empty($password) || empty($no_hp)) {
    header("Location: /teman_singgah/index.php?auth=daftar&error=field_kosong");
    exit;
}

// Validasi email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: /teman_singgah/index.php?auth=daftar&error=email_invalid");
    exit;
}

// Validasi password
if (strlen($password) < 8) {
    header("Location: /teman_singgah/index.php?auth=daftar&error=password_pendek");
    exit;
}

// Validasi no HP (angka, 8–15 digit)
if (!preg_match('/^[0-9]{8,15}$/', $no_hp)) {
    header("Location: /teman_singgah/index.php?auth=daftar&error=hp_invalid");
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
mysqli_query($koneksi, "LOCK TABLES users WRITE");

$result  = mysqli_query($koneksi, "SELECT MAX(id) as max_id FROM users");
$row     = mysqli_fetch_assoc($result);
$next_id = ($row['max_id'] ?? 0) + 1;
$user_id = 'USR-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);

$hash   = password_hash($password, PASSWORD_DEFAULT);
$role   = 'User';
$status = 'Aktif';

$stmt = mysqli_prepare($koneksi,
    "INSERT INTO users (user_id, nama, email, no_hp, password, role, status)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);
mysqli_stmt_bind_param($stmt, "sssssss", $user_id, $nama, $email, $no_hp, $hash, $role, $status);
mysqli_stmt_execute($stmt);

mysqli_query($koneksi, "UNLOCK TABLES");

if (mysqli_stmt_affected_rows($stmt) > 0) {
    header("Location: /teman_singgah/index.php?auth=login&success=registered");
} else {
    header("Location: /teman_singgah/index.php?auth=daftar&error=gagal");
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
exit;
?>