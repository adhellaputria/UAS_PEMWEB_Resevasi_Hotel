<?php
require_once '../../config/database.php';
requireLogin();
requireAdmin();

$booking_id = (int)($_GET['id'] ?? 0);

if ($booking_id <= 0) {
    $_SESSION['error'] = 'ID booking tidak valid!';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

/* =========================
   CEK PENDING CANCELLATION
========================= */
$checkCancel = mysqli_prepare($conn, "
    SELECT id 
    FROM cancellations 
    WHERE reservation_id = ? AND status = 'pending'
");
mysqli_stmt_bind_param($checkCancel, "i", $booking_id);
mysqli_stmt_execute($checkCancel);
$hasPendingCancel = mysqli_fetch_assoc(mysqli_stmt_get_result($checkCancel));
mysqli_stmt_close($checkCancel);

if ($hasPendingCancel) {
    $_SESSION['error'] = 'Tidak dapat menyetujui reservasi. Selesaikan pembatalan terlebih dahulu.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

/* =========================
   AMBIL DATA RESERVASI + TOTAL_ROOMS
========================= */
$stmt = mysqli_prepare($conn, "
    SELECT room_id, status, total_rooms, booking_code
    FROM reservations 
    WHERE id = ?
");
mysqli_stmt_bind_param($stmt, "i", $booking_id);
mysqli_stmt_execute($stmt);
$res = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$res) {
    $_SESSION['error'] = 'Data reservasi tidak ditemukan.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

if ($res['status'] !== 'pending') {
    $_SESSION['error'] = 'Reservasi sudah diproses sebelumnya.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

$room_id = (int)$res['room_id'];
$total_rooms = (int)$res['total_rooms']; // ✅ AMBIL DARI RESERVASI

/* =========================
   CEK KETERSEDIAAN KAMAR
========================= */
$room_stmt = mysqli_prepare($conn, "
    SELECT available_rooms, name
    FROM rooms 
    WHERE id = ?
");
mysqli_stmt_bind_param($room_stmt, "i", $room_id);
mysqli_stmt_execute($room_stmt);
$room = mysqli_fetch_assoc(mysqli_stmt_get_result($room_stmt));
mysqli_stmt_close($room_stmt);

// ✅ VALIDASI STOK CUKUP SESUAI TOTAL_ROOMS
if (!$room || $room['available_rooms'] < $total_rooms) {
    $_SESSION['error'] = 'Kamar tersedia hanya ' . ($room['available_rooms'] ?? 0) . ' unit. ' .
                         'Reservasi ini memerlukan ' . $total_rooms . ' kamar. Tidak dapat disetujui.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

/* =========================
   UPDATE ROOM & RESERVATION
========================= */
mysqli_begin_transaction($conn);

try {
    // ✅ KURANGI STOK SESUAI TOTAL_ROOMS
    $update_room = mysqli_prepare($conn, "
        UPDATE rooms 
        SET available_rooms = available_rooms - ?
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($update_room, "ii", $total_rooms, $room_id);
    
    if (!mysqli_stmt_execute($update_room)) {
        throw new Exception('Gagal mengupdate stok kamar.');
    }
    mysqli_stmt_close($update_room);

    // ✅ UPDATE STATUS RESERVASI
    $update_reservation = mysqli_prepare($conn, "
        UPDATE reservations 
        SET status = 'confirmed'
        WHERE id = ?
    ");
    mysqli_stmt_bind_param($update_reservation, "i", $booking_id);
    
    if (!mysqli_stmt_execute($update_reservation)) {
        throw new Exception('Gagal mengupdate status reservasi.');
    }
    mysqli_stmt_close($update_reservation);

    // ✅ LOG ACTIVITY
    logActivity(
        $conn,
        'APPROVE RESERVATION',
        'Menyetujui reservasi ' . $res['booking_code'] . ' - ' . 
        $total_rooms . ' kamar dari ' . $room['name']
    );

    mysqli_commit($conn);

    $_SESSION['success'] = 'Reservasi berhasil disetujui. ' . $total_rooms . ' kamar telah dikurangi dari stok.';

} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error'] = 'Gagal menyetujui reservasi: ' . $e->getMessage();
}

header("Location: " . BASE_URL . "/admin/reservations.php");
exit;