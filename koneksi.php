<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "teman_singgah";

$koneksi = mysqli_connect($host, $user, $pass, $db);
$conn = $koneksi;

if (!$koneksi) {
    header('Content-Type: application/json');
    echo json_encode([
        "status" => "error",
        "message" => "Gagal terhubung ke database server."
    ]);
    exit();
}