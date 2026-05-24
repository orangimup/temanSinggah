<?php
session_start();

$isLoggedIn  = isset($_SESSION['nama']);
$userInitial = $isLoggedIn ? strtoupper(mb_substr($_SESSION['nama'], 0, 1)) : '';
$userName    = $isLoggedIn ? $_SESSION['nama'] : '';
$userPhoto   = '';

if ($isLoggedIn && !empty($_SESSION['photo'])) {
    $photoPath = $_SERVER['DOCUMENT_ROOT'] . '/teman_singgah/assets/uploads/photos/' . $_SESSION['photo'];
    if (file_exists($photoPath)) {
        $userPhoto = '/teman_singgah/assets/uploads/photos/' . htmlspecialchars($_SESSION['photo']);
    }
}