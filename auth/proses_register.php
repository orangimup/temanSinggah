<?php
header('Content-Type: application/json');

// Mematikan tampilan error mentah HTML agar tidak merusak JSON parser di auth.js
error_reporting(0);
ini_set('display_errors', 0);

require_once '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($nama) || empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Semua kolom pendaftaran wajib diisi."]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Format email tidak valid."]);
        exit();
    }

    if (strlen($password) < 8) {
        echo json_encode(["status" => "error", "message" => "Password minimal harus 8 karakter."]);
        exit();
    }

    // 1. Periksa apakah email sudah terdaftar sebelumnya
    $cekEmail = mysqli_prepare($koneksi, "SELECT email FROM users WHERE email = ?");
    mysqli_stmt_bind_param($cekEmail, "s", $email);
    mysqli_stmt_execute($cekEmail);
    mysqli_stmt_store_result($cekEmail);

    if (mysqli_stmt_num_rows($cekEmail) > 0) {
        echo json_encode(["status" => "error", "message" => "Email ini sudah terdaftar."]);
        mysqli_stmt_close($cekEmail);
        exit();
    }
    mysqli_stmt_close($cekEmail);

    // 2. Membuat user_id kustom secara otomatis (Contoh: USR-001)
    $queryMax  = mysqli_query($koneksi, "SELECT id FROM users ORDER BY id DESC LIMIT 1");
    $increment = 1;
    if ($row = mysqli_fetch_assoc($queryMax)) {
        $increment = $row['id'] + 1;
    }
    $userIdCustom = "USR-" . str_pad($increment, 3, "0", STR_PAD_LEFT);

    // 3. Mengamankan password dengan enkripsi BCRYPT hash
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    // 4. Masukkan data ke database sesuai struktur tabel kamu (menggunakan kolom 'status')
    $insertData = mysqli_prepare($koneksi, "INSERT INTO users (user_id, nama, email, password, role, status) VALUES (?, ?, ?, ?, 'User', 'Aktif')");
    mysqli_stmt_bind_param($insertData, "ssss", $userIdCustom, $nama, $email, $passwordHash);

    if (mysqli_stmt_execute($insertData)) {
        echo json_encode(["status" => "success", "message" => "Registrasi akun berhasil!"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Gagal mendaftarkan pengguna baru."]);
    }
    mysqli_stmt_close($insertData);

} else {
    echo json_encode(["status" => "error", "message" => "Metode akses tidak diizinkan."]);
}