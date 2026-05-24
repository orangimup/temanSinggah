<?php
session_start();
include "../../koneksi.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    header("Location: /teman_singgah/index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Sanitasi input teks
$nama               = trim($_POST['nama'] ?? '');
$pekerjaan          = trim($_POST['pekerjaan'] ?? '');
$lokasi             = trim($_POST['lokasi'] ?? '');
$bahasa             = trim($_POST['bahasa'] ?? '');
$tentang            = trim($_POST['tentang'] ?? '');
$destinasi_impian   = trim($_POST['destinasi_impian'] ?? '');
$hobi               = trim($_POST['hobi'] ?? '');
$hewan_peliharaan   = trim($_POST['hewan_peliharaan'] ?? '');
$dekade_lahir       = trim($_POST['dekade_lahir'] ?? '');
$sekolah            = trim($_POST['sekolah'] ?? '');
$lagu_favorit       = trim($_POST['lagu_favorit'] ?? '');

// Validasi nama tidak kosong
if (empty($nama)) {
    header("Location: ./edit_account.php?error=nama_kosong");
    exit;
}

// ── Handle upload foto ──────────────────────────────────────────────
$photo_filename = null; // null = tidak ada perubahan foto

if (!empty($_FILES['photo']['name'])) {
    $file      = $_FILES['photo'];
    $allowed   = ['image/jpeg', 'image/png', 'image/webp'];
    $max_size  = 2 * 1024 * 1024; // 2MB

    // Validasi tipe MIME (gunakan finfo, bukan hanya extension)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed)) {
        header("Location: ./edit_account.php?error=foto_tipe");
        exit;
    }

    if ($file['size'] > $max_size) {
        header("Location: ./edit_account.php?error=foto_ukuran");
        exit;
    }

    // Buat nama file unik
    $ext            = pathinfo($file['name'], PATHINFO_EXTENSION);
    $photo_filename = 'photo_' . $user_id . '_' . time() . '.' . strtolower($ext);
    $upload_dir     = "../../assets/uploads/photos/";

    // Pastikan folder ada
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    if (!move_uploaded_file($file['tmp_name'], $upload_dir . $photo_filename)) {
        header("Location: ./edit_account.php?error=foto_gagal");
        exit;
    }

    // Hapus foto lama kalau ada
    $stmt_old = mysqli_prepare($koneksi, "SELECT photo FROM users WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt_old, "s", $user_id);
    mysqli_stmt_execute($stmt_old);
    $res_old = mysqli_stmt_get_result($stmt_old);
    $old     = mysqli_fetch_assoc($res_old);
    mysqli_stmt_close($stmt_old);

    if (!empty($old['photo'])) {
        $old_path = $upload_dir . $old['photo'];
        if (file_exists($old_path)) {
            unlink($old_path);
        }
    }
}

// ── Update database ─────────────────────────────────────────────────
if ($photo_filename !== null) {
    // Update termasuk foto
    $stmt = mysqli_prepare($koneksi,
        "UPDATE users SET
            nama = ?, pekerjaan = ?, lokasi = ?, bahasa = ?, tentang = ?,
            destinasi_impian = ?, hobi = ?, hewan_peliharaan = ?,
            dekade_lahir = ?, sekolah = ?, lagu_favorit = ?, photo = ?
         WHERE user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "sssssssssssss",
        $nama, $pekerjaan, $lokasi, $bahasa, $tentang,
        $destinasi_impian, $hobi, $hewan_peliharaan,
        $dekade_lahir, $sekolah, $lagu_favorit, $photo_filename,
        $user_id
    );
} else {
    // Update tanpa foto
    $stmt = mysqli_prepare($koneksi,
        "UPDATE users SET
            nama = ?, pekerjaan = ?, lokasi = ?, bahasa = ?, tentang = ?,
            destinasi_impian = ?, hobi = ?, hewan_peliharaan = ?,
            dekade_lahir = ?, sekolah = ?, lagu_favorit = ?
         WHERE user_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "ssssssssssss",
        $nama, $pekerjaan, $lokasi, $bahasa, $tentang,
        $destinasi_impian, $hobi, $hewan_peliharaan,
        $dekade_lahir, $sekolah, $lagu_favorit,
        $user_id
    );
}

if (mysqli_stmt_execute($stmt)) {
    // Update session
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