<?php
session_start();
include "../../koneksi.php";

header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$id = isset($data['id']) ? (int)$data['id'] : 0;

if (!$id) {
    echo json_encode(["status" => "error", "message" => "ID tidak valid"]);
    exit();
}

$res = mysqli_query($koneksi, "SELECT nama_file FROM listing_photos WHERE listing_id = $id");
$foto_files = [];
while ($row = mysqli_fetch_assoc($res)) {
    $foto_files[] = $row['nama_file'];
}

$stmt = mysqli_prepare($koneksi, "DELETE FROM listings WHERE id = ?");
mysqli_stmt_bind_param($stmt, "i", $id);

if (mysqli_stmt_execute($stmt)) {
    foreach ($foto_files as $file) {
        $path = $_SERVER['DOCUMENT_ROOT'] . "/teman_singgah/assets/uploads/listings/" . $file;
        if (file_exists($path)) unlink($path);
    }
    echo json_encode(["status" => "ok"]);
} else {
    echo json_encode(["status" => "error", "message" => "Gagal menghapus dari database"]);
}

mysqli_stmt_close($stmt);