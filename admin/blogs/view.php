<?php
require_once '../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Validasi ID
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid ID']);
    exit;
}

// =============================
// CEK BLOG ADA / TIDAK
// =============================
$check = mysqli_prepare($conn, "
    SELECT views 
    FROM blogs 
    WHERE id = ? AND published = 1
");
mysqli_stmt_bind_param($check, "i", $id);
mysqli_stmt_execute($check);
$result = mysqli_stmt_get_result($check);
$blog = mysqli_fetch_assoc($result);
mysqli_stmt_close($check);

if (!$blog) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Blog not found']);
    exit;
}

// =============================
// UPDATE VIEW COUNT
// =============================
$update = mysqli_prepare($conn, "
    UPDATE blogs 
    SET views = views + 1 
    WHERE id = ?
");
mysqli_stmt_bind_param($update, "i", $id);
mysqli_stmt_execute($update);
mysqli_stmt_close($update);

// =============================
// KIRIM VIEW TERBARU
// =============================
echo json_encode([
    'success' => true,
    'views' => $blog['views'] + 1
]);
exit;
