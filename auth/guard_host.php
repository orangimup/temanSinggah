<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Host') {
    header('Location: /teman_singgah/index.php');
    exit;
}
$userInitial = strtoupper(mb_substr($_SESSION['nama'], 0, 1));
$userName    = $_SESSION['nama'];