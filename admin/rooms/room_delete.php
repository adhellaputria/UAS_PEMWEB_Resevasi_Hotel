<?php
require_once '../../config/database.php';
requireLogin();
requireAdmin();

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: " . BASE_URL . "/admin/rooms/rooms.php");
    exit;
}

$room = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT * FROM rooms WHERE id='$id'")
);

if (!$room) {
    header("Location: " . BASE_URL . "/admin/rooms/rooms.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Hapus foto jika ada
    if (!empty($room['image'])) {
        $image_path = '../../uploads/rooms/' . $room['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }

    // Hapus data kamar (prepared statement)
    $stmt = mysqli_prepare($conn, "DELETE FROM rooms WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {

        logActivity(
            $conn,
            'DELETE ROOM',
            'Menghapus kamar: ' . $room['name'] . ' (ID: ' . $id . ')'
        );

        mysqli_stmt_close($stmt);
        header("Location: " . BASE_URL . "/admin/rooms/rooms.php?deleted=1");
        exit;
    } else {
        $_SESSION['error'] = 'Gagal menghapus kamar.';
        header("Location: " . BASE_URL . "/admin/rooms/rooms.php");
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hapus Kamar</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
body{
    background:#f8fafc;
    font-family:'Inter',sans-serif;
}

.main-content {
    margin-left: 200px;
    padding: 2rem;
    min-height: 100vh;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (max-width: 767.98px) {
    .main-content {
        margin-left: 0;
        padding: 1rem;
        padding-top: 5rem;
    }
}

/* CARD DELETE */
.card-confirm{
    background:#fff;
    border-radius:16px;
    padding:2rem;
    max-width:520px;
    width:100%;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
    text-align:center;
}

/* ICON */
.card-confirm .icon{
    font-size:2.6rem;
    color:#ef4444;
    margin-bottom:.75rem;
}

/* TEXT */
.card-confirm h5{
    font-size:1.25rem;
    font-weight:800;
    color:#0f172a;
}
.card-confirm p{
    font-size:.9rem;
    color:#64748b;
    margin-bottom:1.5rem;
}

/* BUTTON */
.btn-delete{
    background:#ef4444;
    color:#fff;
    font-weight:700;
    border-radius:12px;
    padding:.6rem 1.5rem;
    border:none;
}
.btn-delete:hover{
    background:#dc2626;
    color:#fff;
}
.btn-outline-secondary{
    border-radius:12px;
    padding:.6rem 1.5rem;
}
</style>
</head>

<body>

<?php include_once '../../includes/sidebar.php'; ?>

<div class="main-content">

    <div class="card-confirm">

        <div class="icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <h5>Hapus Kamar?</h5>
        <p>
            Kamar <strong><?= htmlspecialchars($room['name']) ?></strong>
            akan dihapus secara permanen dan tidak dapat dikembalikan.
        </p>

        <form method="post">
            <div class="d-flex gap-2 justify-content-center">
                <button type="submit" class="btn btn-delete">
                    <i class="fa-solid fa-trash me-2"></i>Hapus
                </button>
                <a href="<?= BASE_URL ?>/admin/rooms/rooms.php" class="btn btn-outline-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Batal
                </a>
            </div>
        </form>

    </div>

</div>

</body>
</html>