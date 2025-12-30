<?php
require_once '../config/database.php';

// HAPUS SEMUA DATA SESSION
session_unset();
session_destroy();

// BUAT SESSION BARU UNTUK FLASH MESSAGE
session_start();
$_SESSION['success'] = 'Anda telah berhasil logout!';

// REDIRECT KE LOGIN
header('Location: login.php');
exit;