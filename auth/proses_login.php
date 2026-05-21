<?php
header('Content-Type: application/json');

error_reporting(0);
ini_set('display_errors', 0);

require_once '../koneksi.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        echo json_encode(["status" => "error", "message" => "Email dan password tidak boleh kosong."]);
        exit();
    }

    // Mengambil data user berdasarkan email (kolom 'status' diperiksa di sini)
    $queryUser = mysqli_prepare($koneksi, "SELECT nama, password, status FROM users WHERE email = ?");
    mysqli_stmt_bind_param($queryUser, "s", $email);
    mysqli_stmt_execute($queryUser);
    $result = mysqli_stmt_get_result($queryUser);

    if ($user = mysqli_fetch_assoc($result)) {
        
        // Memastikan status akun milik user aktif
        if ($user['status'] !== 'Aktif') {
            echo json_encode(["status" => "error", "message" => "Akun kamu dinonaktifkan. Silakan hubungi admin."]);
            mysqli_stmt_close($queryUser);
            exit();
        }

        // Verifikasi password kecocokan hash
        if (password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            // Simpan ke session untuk keperluan kontrol akses halaman dashboard
            $_SESSION['user_id_custom'] = $user['user_id'];
            $_SESSION['user_nama']      = $user['nama'];

            // Mengembalikan struktur data berformat JSON tepat seperti kemauan auth.js
            echo json_encode([
                "status" => "success",
                "user" => [
                    "nama" => $user['nama']
                ]
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Password yang kamu masukkan salah."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Akun dengan email tersebut tidak ditemukan."]);
    }
    mysqli_stmt_close($queryUser);

} else {
    echo json_encode(["status" => "error", "message" => "Metode akses tidak sah."]);
}