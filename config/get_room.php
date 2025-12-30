<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID kamar tidak valid']);
    exit;
}

$room_id = (int)$_GET['id'];

$query = "SELECT * FROM rooms WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $room_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 1) {
    $room = mysqli_fetch_assoc($result);
    echo json_encode(['success' => true, 'room' => $room]);
} else {
    echo json_encode(['success' => false, 'message' => 'Kamar tidak ditemukan']);
}
?>
