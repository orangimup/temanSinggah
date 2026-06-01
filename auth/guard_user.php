<?php
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'User') {
    header('Location: /teman_singgah/index.php');
    exit;
}

$userName    = $_SESSION['nama']  ?? '';
$userRole    = $_SESSION['role']  ?? 'User';
$userInitial = strtoupper(mb_substr($userName, 0, 1));
$userPhoto   = '';

if (!empty($_SESSION['photo'])) {
    $abs = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $_SESSION['photo'];
    if (file_exists($abs)) {
        $userPhoto = '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($_SESSION['photo']);
    }
}