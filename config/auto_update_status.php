<?php
/**
 * AUTO UPDATE STATUS RESERVASI
 * 
 * Script ini akan otomatis mengupdate status reservasi:
 * 1. EXPIRED: Pending/Confirmed yang lewat check-in date
 * 2. COMPLETED: Checked_in yang lewat check-out date
 * 
 * Cara menggunakan:
 * 1. Include di header.php: require_once '../config/auto_update_status.php';
 * 2. Atau setup sebagai cron job yang dijalankan setiap hari
 */

require_once __DIR__ . '/database.php';

// ============================================
// 1. UPDATE EXPIRED RESERVATIONS
// Reservasi yang tidak check-in sampai H+1
// ============================================
$expiredQuery = "
    UPDATE reservations 
    SET status = 'cancelled'
    WHERE status IN ('pending', 'confirmed')
      AND check_in_date < CURDATE()
      AND status != 'cancelled'
";

mysqli_query($conn, $expiredQuery);

// ============================================
// 2. UPDATE COMPLETED RESERVATIONS  
// Yang sudah checked_in dan melewati check-out date
// ============================================
$completedQuery = "
    UPDATE reservations 
    SET status = 'completed'
    WHERE status = 'checked_in'
      AND check_out_date < CURDATE()
";

mysqli_query($conn, $completedQuery);

// ============================================
// 3. OPTIONAL: Log aktivitas (jika diperlukan)
// ============================================
// Uncomment jika ingin log
/*
$logFile = __DIR__ . '/../logs/status_updates.log';
$timestamp = date('Y-m-d H:i:s');
$expiredCount = mysqli_affected_rows($conn);
file_put_contents($logFile, "[$timestamp] Updated $expiredCount reservations\n", FILE_APPEND);
*/
?>