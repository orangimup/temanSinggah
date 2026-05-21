<?php
$host     = "localhost";
$user     = "root";
$password = "";
$dbname   = "teman_singgah";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>