<?php
require_once '../../config/database.php';
requireLogin();
requireAdmin();

$booking_id = (int)($_GET['id'] ?? 0);

if ($booking_id <= 0) {
    $_SESSION['error'] = 'ID reservasi tidak valid.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

/* =========================
   AMBIL DATA RESERVASI
========================= */
$res = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT 
        r.status,
        r.room_id,
        r.check_in_date,
        ro.name AS room_name
    FROM reservations r
    JOIN rooms ro ON r.room_id = ro.id
    WHERE r.id = $booking_id
"));

if (!$res) {
    $_SESSION['error'] = 'Reservasi tidak ditemukan.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

/* =========================
   VALIDASI STATUS
========================= */
if ($res['status'] !== 'confirmed') {
    $_SESSION['error'] = 'Reservasi belum disetujui atau sudah check-in.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

/* =========================
   VALIDASI TANGGAL (HARI INI)
========================= */
$today = date('Y-m-d');

if ($res['check_in_date'] !== $today) {
    $_SESSION['error'] = 'Check-in hanya dapat dilakukan pada tanggal check-in.';
    header("Location: " . BASE_URL . "/admin/reservations.php");
    exit;
}

/* =========================
   UPDATE STATUS CHECK-IN
========================= */
$stmt = mysqli_prepare($conn, "
    UPDATE reservations
    SET status = 'checked_in',
        checked_in_at = NOW()
    WHERE id = ?
");

mysqli_stmt_bind_param($stmt, "i", $booking_id);

if (mysqli_stmt_execute($stmt)) {

    logActivity(
        $conn,
        'CHECK IN',
        'Check-in reservasi ID ' . $booking_id . ' - Kamar: ' . $res['room_name']
    );

    $_SESSION['success'] = 'Check-in berhasil.';
} else {
    $_SESSION['error'] = 'Gagal melakukan check-in.';
}

mysqli_stmt_close($stmt);

header("Location: " . BASE_URL . "/admin/reservations.php");
exit;
