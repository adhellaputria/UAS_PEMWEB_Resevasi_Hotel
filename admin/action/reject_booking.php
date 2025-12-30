<?php
require_once '../../config/database.php';
requireLogin();
requireAdmin();

$booking_id = (int)($_GET['id'] ?? 0);
$admin_note = clean($_POST['admin_notes'] ?? 'Dibatalkan oleh admin');
$admin_id   = $_SESSION['user_id'];

if ($booking_id <= 0) {
    $_SESSION['error'] = 'ID reservasi tidak valid';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

/* =========================
   CEK PENDING CANCELLATION USER
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
    $_SESSION['error'] = 'Selesaikan proses pembatalan user terlebih dahulu.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

mysqli_begin_transaction($conn);

try {

    /* =========================
       AMBIL DATA RESERVASI + ROOM
    ========================= */
    $stmt = mysqli_prepare($conn, "
        SELECT 
            r.id,
            r.booking_code,
            r.total_price,
            r.total_rooms,
            r.status,
            r.room_id,
            ro.total_rooms AS room_total,
            ro.available_rooms
        FROM reservations r
        JOIN rooms ro ON r.room_id = ro.id
        WHERE r.id = ?
          AND r.status IN ('pending','confirmed','checked_in')
    ");
    mysqli_stmt_bind_param($stmt, "i", $booking_id);
    mysqli_stmt_execute($stmt);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$r) {
        throw new Exception('Reservasi tidak valid atau sudah selesai.');
    }

    /* =========================
       CEK DOUBLE CANCEL
    ========================= */
    $check = mysqli_prepare($conn, "
        SELECT id FROM cancellations 
        WHERE reservation_id = ? AND status = 'approved'
    ");
    mysqli_stmt_bind_param($check, "i", $booking_id);
    mysqli_stmt_execute($check);
    $existingCancel = mysqli_fetch_assoc(mysqli_stmt_get_result($check));
    mysqli_stmt_close($check);

    if ($existingCancel) {
        throw new Exception('Reservasi ini sudah dibatalkan sebelumnya.');
    }

    /* =========================
       HITUNG REFUND (100%)
    ========================= */
    $refundAmount = (float)$r['total_price'];

    /* =========================
       INSERT CANCELLATION
    ========================= */
    $stmt = mysqli_prepare($conn, "
        INSERT INTO cancellations
        (reservation_id, reason, status, admin_notes, refund_amount, processed_by, processed_at, created_at)
        VALUES (?, 'Dibatalkan oleh admin', 'approved', ?, ?, ?, NOW(), NOW())
    ");
    mysqli_stmt_bind_param(
        $stmt,
        "isdi",
        $booking_id,
        $admin_note,
        $refundAmount,
        $admin_id
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    /* =========================
       INSERT REFUND (NEGATIVE)
    ========================= */
    $negative = -abs($refundAmount);
    $stmt = mysqli_prepare($conn, "
        INSERT INTO payments (reservation_id, booking_code, amount, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    mysqli_stmt_bind_param(
        $stmt,
        "isd",
        $booking_id,
        $r['booking_code'],
        $negative
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    /* =========================
       UPDATE RESERVASI
    ========================= */
    $stmt = mysqli_prepare($conn, "
        UPDATE reservations
        SET status='cancelled', payment_status='refunded'
        WHERE id=?
    ");
    mysqli_stmt_bind_param($stmt, "i", $booking_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    /* =========================
       KEMBALIKAN STOK (HANYA JIKA PERNAH DIKURANGI)
    ========================= */
    if (
        in_array($r['status'], ['confirmed','checked_in']) &&
        $r['available_rooms'] + $r['total_rooms'] <= $r['room_total']
    ) {
        $stmt = mysqli_prepare($conn, "
            UPDATE rooms
            SET available_rooms = available_rooms + ?
            WHERE id=?
        ");
        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $r['total_rooms'],
            $r['room_id']
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    /* =========================
       LOG AKTIVITAS
    ========================= */
    logActivity(
        $conn,
        'ADMIN CANCEL RESERVATION',
        'Membatalkan reservasi ' . $r['booking_code'] .
        ' (' . $r['total_rooms'] . ' kamar) - refund 100%'
    );

    mysqli_commit($conn);
    $_SESSION['success'] = 'Reservasi berhasil dibatalkan & refund 100% diproses.';

} catch (Exception $e) {

    mysqli_rollback($conn);
    $_SESSION['error'] = $e->getMessage();
}

header("Location: " . BASE_URL . "/admin/reservations.php");
exit;
