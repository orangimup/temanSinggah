<?php
session_start();
include "../../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: /teman_singgah/index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$nama = trim($_POST['nama'] ?? '');
$pekerjaan = trim($_POST['pekerjaan'] ?? '');
$lokasi = trim($_POST['lokasi'] ?? '');
$bahasa = trim($_POST['bahasa'] ?? '');
$tentang = trim($_POST['tentang'] ?? '');
$destinasi_impian = trim($_POST['destinasi_impian'] ?? '');
$hobi = trim($_POST['hobi'] ?? '');
$hewan_peliharaan = trim($_POST['hewan_peliharaan'] ?? '');
$dekade_lahir = trim($_POST['dekade_lahir'] ?? '');
$sekolah = trim($_POST['sekolah'] ?? '');
$lagu_favorit = trim($_POST['lagu_favorit'] ?? '');
$no_hp = trim($_POST['no_hp'] ?? '');

if (empty($nama)) {
    header("Location: ./edit_account.php?error=nama_kosong");
    exit;
}

$photo_filename = null;

if (!empty($_FILES['photo']['name'])) {
    $file = $_FILES['photo'];
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size = 2 * 1024 * 1024;

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed)) {
        header("Location: ./edit_account.php?error=foto_tipe");
        exit;
    }

    if ($file['size'] > $max_size) {
        header("Location: ./edit_account.php?error=foto_ukuran");
        exit;
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $photo_filename = 'photo_' . $user_id . '_' . time() . '.' . strtolower($ext);
    $upload_dir = "../../assets/uploads/photos/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $photo_filename)) {
        header("Location: ./edit_account.php?error=foto_gagal");
        exit;
    }

    $stmt_old = mysqli_prepare($koneksi, "SELECT photo FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt_old, "s", $user_id);
    mysqli_stmt_execute($stmt_old);
    $res_old = mysqli_stmt_get_result($stmt_old);
    $old = mysqli_fetch_assoc($res_old);
    mysqli_stmt_close($stmt_old);

    if (!empty($old['photo'])) {
        $old_path = $upload_dir . $old['photo'];
        if (file_exists($old_path)) {
            unlink($old_path);
        }
    }
}

if ($photo_filename !== null) {
    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE users SET
            nama = ?, pekerjaan = ?, lokasi = ?, bahasa = ?, tentang = ?,
            destinasi_impian = ?, hobi = ?, hewan_peliharaan = ?,
            dekade_lahir = ?, sekolah = ?, lagu_favorit = ?, no_hp = ?, photo = ?
         WHERE user_id = ?"
    );
    mysqli_stmt_bind_param(
        $stmt,
        "ssssssssssssss",
        $nama,
        $pekerjaan,
        $lokasi,
        $bahasa,
        $tentang,
        $destinasi_impian,
        $hobi,
        $hewan_peliharaan,
        $dekade_lahir,
        $sekolah,
        $lagu_favorit,
        $no_hp,
        $photo_filename,
        $user_id
    );
} else {
    $stmt = mysqli_prepare(
        $koneksi,
        "UPDATE users SET
            nama = ?, pekerjaan = ?, lokasi = ?, bahasa = ?, tentang = ?,
            destinasi_impian = ?, hobi = ?, hewan_peliharaan = ?,
            dekade_lahir = ?, sekolah = ?, lagu_favorit = ?, no_hp = ?
         WHERE user_id = ?"
    );
    mysqli_stmt_bind_param(
        $stmt,
        "sssssssssssss",
        $nama,
        $pekerjaan,
        $lokasi,
        $bahasa,
        $tentang,
        $destinasi_impian,
        $hobi,
        $hewan_peliharaan,
        $dekade_lahir,
        $sekolah,
        $lagu_favorit,
        $no_hp,
        $user_id
    );
}

if (mysqli_stmt_execute($stmt)) {
    $_SESSION['nama'] = $nama;
    if ($photo_filename !== null) {
        $_SESSION['photo'] = $photo_filename;
    }
    header("Location: /teman_singgah/index.php");
} else {
    header("Location: ./edit_account.php?error=gagal");
}

mysqli_stmt_close($stmt);
mysqli_close($koneksi);
exit;
?>