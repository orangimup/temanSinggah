<?php
session_start();
header('Content-Type: application/json');

echo json_encode([
    'loggedIn'    => isset($_SESSION['nama']),
    'role'        => $_SESSION['role']        ?? null,
    'initial'     => isset($_SESSION['nama'])
                       ? strtoupper(mb_substr($_SESSION['nama'], 0, 1))
                       : null,
    'nama'        => $_SESSION['nama']        ?? null,
]);