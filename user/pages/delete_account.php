<?php
session_start();
include "../../koneksi.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: /teman_singgah/index.php?auth=login");
    exit;
}

$stmt = mysqli_prepare($koneksi, "UPDATE users SET status = 'Nonaktif' WHERE user_id = ?");
mysqli_stmt_bind_param($stmt, "s", $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

session_destroy();
header("Location: /teman_singgah/index.php?deleted=1");
exit;