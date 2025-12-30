<?php
require_once '../../config/database.php';
requireLogin();
requireAdmin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = clean($_POST['name']);
    $type     = clean($_POST['type']);
    $price    = (int)$_POST['price'];
    $capacity = (int)$_POST['capacity'];
    $total    = (int)$_POST['total_rooms'];
    $available= (int)$_POST['available_rooms'];
    $desc     = clean($_POST['description']);
    $facilities = clean($_POST['facilities']);
    $status   = clean($_POST['status']);

    /* =====================
       UPLOAD IMAGE
    ===================== */
    $image_name = '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $filename = $_FILES['image']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            $new_image_name = 'room_' . time() . '_' . uniqid() . '.' . $file_ext;
            $upload_path = '../../uploads/rooms/';
            
            if (!is_dir($upload_path)) {
                mkdir($upload_path, 0755, true);
            }
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path . $new_image_name)) {
                $image_name = $new_image_name;
            } else {
                $error = 'Gagal mengupload foto';
            }
        } else {
            $error = 'Format foto tidak valid';
        }
    }

    /* =====================
       VALIDASI
    ===================== */
    if (!$name || !$type || !$price || !$total) {
        $error = 'Semua field wajib diisi.';
    }

    /* =====================
       INSERT DATA
    ===================== */
    if (empty($error)) {

        $stmt = mysqli_prepare($conn, "
            INSERT INTO rooms
            (name, type, price, capacity, total_rooms, available_rooms, facilities, description, image, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        mysqli_stmt_bind_param(
            $stmt,
            "ssiiisssss",
            $name,
            $type,
            $price,
            $capacity,
            $total,
            $available,
            $facilities,  
            $desc,
            $image_name,
            $status
        );

        if (mysqli_stmt_execute($stmt)) {
            
            logActivity(
                $conn,
                'CREATE ROOM',
                'Menambahkan kamar baru: ' . $name . ' (Tipe: ' . $type . ')'
            );

            mysqli_stmt_close($stmt);
            header("Location: " . BASE_URL . "/admin/rooms/rooms.php?added=1");
            exit;

        } else {
            $error = 'Gagal menambahkan kamar: ' . mysqli_error($conn);
        }
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tambah Kamar</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
body{background:#f8fafc;font-family:'Inter',sans-serif}

.main-content {
    margin-left: 200px;
    padding: 2rem 2.5rem;
}

@media (max-width: 767.98px) {
    .main-content {
        margin-left: 0;
        padding: 1rem;
        padding-top: 5rem;
    }
}

.form-wrapper{max-width:760px}
.card-form{
    background:#fff;
    border-radius:16px;
    padding:1.75rem;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
}
.card-form h5{
    font-size:1.25rem;
    font-weight:800;
    margin-bottom:1.5rem;
}

.form-label{
    font-size:.75rem;
    font-weight:700;
    text-transform:uppercase;
    color:#475569;
}
.form-control, .form-select{
    font-size:.85rem;
    border-radius:10px;
    padding:.55rem .75rem;
    border:2px solid #e2e8f0;
    transition:.25s;
}

.form-control:focus, .form-select:focus{
    border-color:#facc15;
    box-shadow:0 0 0 3px rgba(250,204,21,.15);
}

/* Image Upload */
.image-upload-wrapper{
    position:relative;
    border:2px dashed #cbd5e1;
    border-radius:12px;
    padding:2rem;
    text-align:center;
    background:#f8fafc;
    transition:.25s;
    cursor:pointer;
}

.image-upload-wrapper:hover{
    border-color:#facc15;
    background:#fffbeb;
}

.image-upload-wrapper.has-image{
    border-style:solid;
    border-color:#16a34a;
    background:#f0fdf4;
}

#imagePreview{
    max-width:100%;
    max-height:300px;
    border-radius:8px;
    margin-top:1rem;
    display:none;
}

.upload-icon{
    font-size:3rem;
    color:#94a3b8;
    margin-bottom:1rem;
}

.btn-save{
    background:linear-gradient(135deg,#facc15,#f59e0b);
    border:none;
    font-weight:700;
    border-radius:12px;
    padding:.6rem 1.4rem;
    color:#0f172a;
}
.btn-save:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(250,204,21,.35);
}
.btn-outline-secondary{
    border-radius:12px;
    padding:.6rem 1.4rem;
}
</style>
</head>

<body>
<?php include_once '../../includes/sidebar.php'; ?>

<div class="main-content">
        <div class="form-wrapper">

            <div class="card-form">
                <h5><i class="fa-solid fa-plus-circle me-2"></i>Tambah Kamar</h5>

                <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= $error ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data">

                    <!-- Foto Kamar -->
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fa-solid fa-camera me-2"></i>Foto Kamar
                        </label>
                        <div class="image-upload-wrapper" onclick="document.getElementById('imageInput').click()">
                            <input type="file" 
                                   id="imageInput" 
                                   name="image" 
                                   accept="image/*" 
                                   style="display:none"
                                   onchange="previewImage(this)">
                            <div id="uploadPlaceholder">
                                <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
                                <p class="mb-0 text-muted">Klik untuk upload foto kamar</p>
                                <small class="text-muted">JPG, JPEG, PNG, atau WEBP</small>
                            </div>
                            <img id="imagePreview" alt="Preview">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Nama Kamar</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tipe</label>
                        <input type="text" name="type" class="form-control" required>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Harga</label>
                            <input type="number" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kapasitas</label>
                            <input type="number" name="capacity" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Total</label>
                            <input type="number" name="total_rooms" class="form-control" required>
                        </div>
                    </div>

                    <!-- ✅ TAMBAHAN: Tersedia & Status (SAMA DENGAN EDIT) -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Tersedia</label>
                            <input type="number" name="available_rooms" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="available" selected>Available</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fa-solid fa-star me-2"></i>Fasilitas (pisahkan dengan koma)
                        </label>
                        <input 
                            type="text" 
                            name="facilities" 
                            class="form-control"
                            placeholder="AC, WiFi, TV, Air Panas, Kamar Mandi Dalam">
                        <small class="text-muted">Contoh: AC, WiFi, TV, Air Panas, Kamar Mandi Dalam</small>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn-save">
                            <i class="fa-solid fa-save me-2"></i>Simpan
                        </button>
                        <a href="<?= BASE_URL ?>/admin/rooms/rooms.php" class="btn btn-outline-secondary">
                            <i class="fa-solid fa-arrow-left me-2"></i>Batal
                        </a>
                    </div>

                </form>
            </div>

        </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(input) {
    const wrapper = document.querySelector('.image-upload-wrapper');
    const preview = document.getElementById('imagePreview');
    const placeholder = document.getElementById('uploadPlaceholder');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            placeholder.style.display = 'none';
            wrapper.classList.add('has-image');
        }
        
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
</body>
</html>