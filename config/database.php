<?php
// =============================================
// DATABASE CONNECTION
// =============================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'db_reservasi_akomodasi');
define('BASE_URL', 'http://localhost:8888/reservasi_akomodasi');


// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

// Set charset to UTF-8
mysqli_set_charset($conn, "utf8mb4");

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Helper function untuk mencegah SQL Injection
function clean($data) {
    global $conn;

    if ($data === null) {
        return '';
    }

    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    $data = mysqli_real_escape_string($conn, $data);

    return $data;
}


// Helper function untuk redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper function untuk cek login
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function untuk cek admin
function isAdmin() {
    return isset($_SESSION['role']) && strtolower(trim($_SESSION['role'])) === 'admin';
}


// Helper function untuk proteksi halaman
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/auth/login.php');
    }
}


// Helper function untuk proteksi halaman admin
function requireAdmin() {
    if (!isLoggedIn()) {
        redirect(BASE_URL . '/auth/login.php');
    }

    if (!isAdmin()) {
        redirect(BASE_URL . '/index.php');
    }
}

// Helper function untuk format rupiah
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

// Helper function untuk format tanggal Indonesia
function formatTanggal($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// Helper function untuk generate booking code
function generateBookingCode() {
    return 'BK' . date('Ymd') . rand(1000, 9999);
}

// Helper function untuk hitung malam
function hitungMalam($checkin, $checkout) {
    $date1 = new DateTime($checkin);
    $date2 = new DateTime($checkout);
    $diff = $date1->diff($date2);
    return $diff->days;
}
// =============================================
// LOG AKTIVITAS ADMIN
// =============================================
// ✅ FUNGSI LOGGING YANG SUDAH DIPERBAIKI
// Taruh di config/database.php

function logActivity($conn, $action, $description) {
    // Cek apakah user sudah login
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $admin_id = (int)$_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    // Escape string untuk keamanan
    $action_safe = mysqli_real_escape_string($conn, $action);
    $desc_safe = mysqli_real_escape_string($conn, $description);
    $ip_safe = mysqli_real_escape_string($conn, $ip);
    
    // ✅ PERBAIKAN: Gunakan prepared statement yang lebih aman
    $stmt = mysqli_prepare($conn, "
        INSERT INTO admin_logs 
        (admin_id, action, description, ip_address, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    
    if (!$stmt) {
        error_log("logActivity ERROR: Prepare failed - " . mysqli_error($conn));
        return false;
    }
    
    mysqli_stmt_bind_param($stmt, "isss", $admin_id, $action_safe, $desc_safe, $ip_safe);
    
    $result = mysqli_stmt_execute($stmt);
    
    if (!$result) {
        error_log("logActivity ERROR: Execute failed - " . mysqli_stmt_error($stmt));
    }
    
    mysqli_stmt_close($stmt);
    
    // ✅ PENTING: Return hasil eksekusi
    return $result;
}