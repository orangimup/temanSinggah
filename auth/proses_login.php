<?php
session_start();
include "../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: /teman_singgah/index.php");
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi kosong
if (empty($email) || empty($password)) {
    header("Location: /teman_singgah/index.php?auth=login&error=field_kosong");
    exit;
}

// Cari user
$stmt = mysqli_prepare($koneksi, "SELECT * FROM users WHERE email = ?");
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

if (!$data) {
    header("Location: /teman_singgah/index.php?auth=login&error=email_notfound");
    exit;
}

if (!password_verify($password, $data['password'])) {
    header("Location: /teman_singgah/index.php?auth=login&error=password_salah");
    exit;
}

if ($data['status'] === 'Nonaktif') {
    header("Location: /teman_singgah/index.php?auth=login&error=nonaktif");
    exit;
}

// Simpan session
$_SESSION['id'] = $data['id'];
$_SESSION['user_id'] = $data['user_id'];
$_SESSION['nama'] = $data['nama'];
$_SESSION['email'] = $data['email'];
$_SESSION['role'] = $data['role'];

// Redirect sesuai role
if ($data['role'] === 'Admin') {
    header("Location: /teman_singgah/admin/pages/dashboard.html");
} elseif ($data['role'] === 'Host') {
    header("Location: /teman_singgah/host/dashboard/pages/reservations.php");
} else {
    header("Location: /teman_singgah/index.php");
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
exit;
?>