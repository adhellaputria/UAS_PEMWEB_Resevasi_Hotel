<?php
require_once '../../config/database.php';
requireLogin();
requireAdmin();

$id = (int)($_GET['id'] ?? 0);

if ($id <= 0) {
    $_SESSION['error'] = 'ID reservasi tidak valid.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

/* =========================
   AMBIL DATA RESERVASI + TOTAL_ROOMS
========================= */
$stmt = mysqli_prepare($conn, "
    SELECT 
        r.*, 
        r.total_rooms,
        ro.name AS room_name, 
        ro.total_rooms AS room_total_rooms, 
        ro.available_rooms
    FROM reservations r
    JOIN rooms ro ON r.room_id = ro.id
    WHERE r.id = ?
");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$reservation = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$reservation) {
    $_SESSION['error'] = 'Reservasi tidak ditemukan.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

/* =========================
   VALIDASI STATUS
========================= */
if ($reservation['status'] !== 'checked_in') {
    $_SESSION['error'] = 'Hanya reservasi dengan status checked-in yang bisa check-out.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

$total_rooms = (int)$reservation['total_rooms']; // ✅ AMBIL DARI RESERVASI

/* =========================
   VALIDASI ANTI OVERFLOW
========================= */
$new_available = $reservation['available_rooms'] + $total_rooms;
if ($new_available > $reservation['room_total_rooms']) {
    $_SESSION['error'] = 'Gagal check-out. Penambahan stok akan melebihi kapasitas total kamar (' . 
                         $reservation['room_total_rooms'] . ' unit). ' .
                         'Stok saat ini: ' . $reservation['available_rooms'] . ', ' .
                         'akan ditambah: ' . $total_rooms;
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

/* =========================
   TRANSACTION START
========================= */
mysqli_begin_transaction($conn);

try {

    // ✅ UPDATE STATUS CHECK-OUT
    $stmt = mysqli_prepare($conn, "
        UPDATE reservations 
        SET status = 'checked_out',
            checked_out_at = NOW()
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Gagal update status reservasi.');
    }
    mysqli_stmt_close($stmt);

    // ✅ KEMBALIKAN STOK SESUAI TOTAL_ROOMS
    $stmt2 = mysqli_prepare($conn, "
        UPDATE rooms 
        SET available_rooms = available_rooms + ?
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($stmt2, "ii", $total_rooms, $reservation['room_id']);
    
    if (!mysqli_stmt_execute($stmt2)) {
        throw new Exception('Gagal kembalikan stok kamar.');
    }
    mysqli_stmt_close($stmt2);

    // ✅ LOG AKTIVITAS
    logActivity(
        $conn,
        'CHECK OUT',
        'Check-out reservasi ' . $reservation['booking_code'] . ' - ' .
        'Mengembalikan ' . $total_rooms . ' kamar ' . $reservation['room_name']
    );

    mysqli_commit($conn);
    
    $_SESSION['success'] = 'Check-out berhasil. ' . $total_rooms . ' kamar telah dikembalikan ke stok.';

} catch (Exception $e) {

    mysqli_rollback($conn);
    $_SESSION['error'] = 'Gagal check-out: ' . $e->getMessage();
}

header("Location: " . BASE_URL . "/admin/reservations.php");
exit;