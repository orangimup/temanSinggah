<?php
session_start();
session_destroy();
header("Location: /teman_singgah/index.php");
exit;
?>