<?php
require_once '../../config/database.php';
requireLogin();
requireAdmin();

$is_edit = isset($_GET['id']);
$blog = null;

// Get blog data if editing
if ($is_edit) {
    $id = (int)$_GET['id'];
    $result = mysqli_query($conn, "SELECT * FROM blogs WHERE id=$id");
    $blog = mysqli_fetch_assoc($result);
    
    if (!$blog) {
        header("Location: " . BASE_URL . "/admin/blogs/manage.php");
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = clean($_POST['title']);
    $category = clean($_POST['category']);
    $content = clean($_POST['content']);
    $published = isset($_POST['published']) ? 1 : 0;
    $author_id = $_SESSION['user_id'];
    
    // Generate slug from title
    $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
    
    // Handle image upload
    $image_path = $is_edit ? $blog['image'] : '';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload_dir = '../../uploads/blogs/';
        
        // Create directory if not exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($file_ext, $allowed)) {
            $new_filename = uniqid() . '_' . time() . '.' . $file_ext;
            $upload_path = $upload_dir . $new_filename;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                // Delete old image if editing
                if ($is_edit && $blog['image'] && file_exists('../../' . $blog['image'])) {
                    unlink('../../' . $blog['image']);
                }
                $image_path = 'uploads/blogs/' . $new_filename;
            }
        }
    }
    
    if ($is_edit) {
        // UPDATE
        mysqli_query($conn, "
            UPDATE blogs SET
                title = '$title',
                slug = '$slug',
                category = '$category',
                content = '$content',
                image = '$image_path',
                published = $published,
                updated_at = NOW()
            WHERE id = $id
        ");

        logActivity(
            $conn,
            'UPDATE BLOG',
            'Mengupdate artikel: ' . $title
        );

        header("Location: " . BASE_URL . "/admin/blogs/manage.php?updated=1");
    } else {
        // INSERT
        mysqli_query($conn, "
            INSERT INTO blogs (title, slug, category, content, image, author_id, views, published, created_at, updated_at)
            VALUES ('$title', '$slug', '$category', '$content', '$image_path', $author_id, 0, $published, NOW(), NOW())
        ");

        logActivity(
            $conn,
            'CREATE BLOG',
            'Menambahkan artikel baru: ' . $title
        );

        header("Location: " . BASE_URL . "/admin/blogs/manage.php?added=1");
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Artikel - Hotel Delitha</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        body {
            background: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        .main-content {
            margin-left: 200px;
            padding: 2rem;
        }

        @media (max-width: 767.98px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 5rem;
            }
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
            animation: fadeInDown .6s ease-out;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 0.5rem;
        }

        .page-title i {
            color: #facc15;
            font-size: 1.8rem;
        }

        .page-subtitle {
            color: #64748b;
            font-size: .95rem;
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
        }

        /* Form Card */
        .form-card {
            background: #fff;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
            animation: fadeInUp .6s ease-out .2s backwards;
        }

        /* Form Elements */
        .form-label {
            font-weight: 600;
            color: #334155;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: #facc15;
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.1);
        }

        textarea.form-control {
            min-height: 200px;
            resize: vertical;
        }

        /* Image Preview */
        .image-preview {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 2rem;
            text-align: center;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .image-preview:hover {
            border-color: #facc15;
            background: #fffbeb;
        }

        .image-preview img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .image-preview-text {
            color: #64748b;
            font-size: 0.9rem;
        }

        /* Checkbox */
        .form-check-input:checked {
            background-color: #facc15;
            border-color: #facc15;
        }

        /* Buttons */
        .btn-submit {
            background: linear-gradient(135deg, #facc15, #f59e0b);
            border: none;
            color: #0f172a;
            font-weight: 700;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            transition: all 0.25s ease;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(250, 204, 21, 0.3);
            color: #0f172a;
        }

        .btn-cancel {
            background: #f1f5f9;
            border: none;
            color: #475569;
            font-weight: 600;
            padding: 0.75rem 2rem;
            border-radius: 12px;
            transition: all 0.25s ease;
            text-decoration: none;
        }

        .btn-cancel:hover {
            background: #e2e8f0;
            color: #334155;
        }

        /* Category Preview */
        .category-preview {
            display: inline-block;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 0.5rem;
        }

        .category-hotel { background: #fef3c7; color: #92400e; }
        .category-service { background: #dbeafe; color: #1e40af; }
        .category-facility { background: #dcfce7; color: #166534; }
        .category-tips { background: #f3e8ff; color: #6b21a8; }
    </style>
</head>

<body>
    <!-- SIDEBAR -->
    <?php include_once '../../includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- PAGE HEADER -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fa-solid fa-<?= $is_edit ? 'pen' : 'plus' ?>"></i>
                <?= $is_edit ? 'Edit' : 'Tambah' ?> Artikel Blog
            </h1>
            <p class="page-subtitle">
                <?= $is_edit ? 'Perbarui informasi artikel' : 'Buat artikel baru untuk blog hotel' ?>
            </p>
        </div>

        <!-- FORM -->
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="row g-4">
                    <!-- Title -->
                    <div class="col-12">
                        <label class="form-label">
                            <i class="fa-solid fa-heading"></i> Judul Artikel *
                        </label>
                        <input type="text" 
                               name="title" 
                               class="form-control" 
                               placeholder="Masukkan judul artikel yang menarik..."
                               value="<?= htmlspecialchars($blog['title'] ?? '') ?>"
                               required>
                    </div>

                    <!-- Category -->
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-tag"></i> Kategori *
                        </label>
                        <select name="category" class="form-select" id="categorySelect" required>
                            <option value="">Pilih Kategori</option>
                            <option value="hotel" <?= ($blog['category'] ?? '') == 'hotel' ? 'selected' : '' ?>>Tentang Hotel</option>
                            <option value="service" <?= ($blog['category'] ?? '') == 'service' ? 'selected' : '' ?>>Pelayanan</option>
                            <option value="facility" <?= ($blog['category'] ?? '') == 'facility' ? 'selected' : '' ?>>Fasilitas</option>
                            <option value="tips" <?= ($blog['category'] ?? '') == 'tips' ? 'selected' : '' ?>>Tips</option>
                        </select>
                        <span id="categoryBadge"></span>
                    </div>

                    <!-- Status -->
                    <div class="col-md-6">
                        <label class="form-label">
                            <i class="fa-solid fa-circle-check"></i> Status Publikasi
                        </label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   name="published" 
                                   id="publishedSwitch"
                                   <?= ($blog['published'] ?? 0) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="publishedSwitch">
                                Publikasikan artikel
                            </label>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="col-12">
                        <label class="form-label">
                            <i class="fa-solid fa-align-left"></i> Konten Artikel *
                        </label>
                        <textarea name="content" 
                                  class="form-control" 
                                  placeholder="Tulis konten artikel di sini..."
                                  required><?= htmlspecialchars($blog['content'] ?? '') ?></textarea>
                        <small class="text-muted">Gunakan enter untuk membuat paragraf baru</small>
                    </div>

                    <!-- Image Upload -->
                    <div class="col-12">
                        <label class="form-label">
                            <i class="fa-solid fa-image"></i> Gambar Artikel
                        </label>
                        <div class="image-preview" onclick="document.getElementById('imageInput').click()">
                            <?php if ($is_edit && $blog['image']): ?>
                                <img src="<?= BASE_URL . '/' . $blog['image'] ?>" id="imagePreview" alt="">
                            <?php else: ?>
                                <img id="imagePreview" style="display: none;">
                            <?php endif; ?>
                            <div class="image-preview-text" <?= ($is_edit && $blog['image']) ? 'style="display:none"' : '' ?>>
                                <i class="fa-solid fa-cloud-arrow-up fa-3x mb-3"></i>
                                <p class="mb-0"><strong>Klik untuk upload gambar</strong></p>
                                <small>JPG, PNG, atau WEBP (Max 5MB)</small>
                            </div>
                        </div>
                        <input type="file" 
                               name="image" 
                               id="imageInput" 
                               accept="image/*" 
                               style="display: none;"
                               onchange="previewImage(this)">
                    </div>

                    <!-- Buttons -->
                    <div class="col-12">
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-submit">
                                <i class="fa-solid fa-save"></i>
                                <?= $is_edit ? 'Update' : 'Simpan' ?> Artikel
                            </button>
                            <a href="<?= BASE_URL ?>/admin/blogs/manage.php" class="btn btn-cancel">
                                <i class="fa-solid fa-times"></i> Batal
                            </a>
                        </div>
                    </div>
                </div>

            </form>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Image Preview
        function previewImage(input) {
            const preview = document.getElementById('imagePreview');
            const previewText = document.querySelector('.image-preview-text');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                    if (previewText) previewText.style.display = 'none';
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Category Badge Preview
        const categorySelect = document.getElementById('categorySelect');
        const categoryBadge = document.getElementById('categoryBadge');
        
        categorySelect.addEventListener('change', function() {
            const category = this.value;
            if (category) {
                const categoryNames = {
                    'hotel': 'Tentang Hotel',
                    'service': 'Pelayanan',
                    'facility': 'Fasilitas',
                    'tips': 'Tips'
                };
                categoryBadge.innerHTML = `<span class="category-preview category-${category}">${categoryNames[category]}</span>`;
            } else {
                categoryBadge.innerHTML = '';
            }
        });

        // Trigger on page load if editing
        if (categorySelect.value) {
            categorySelect.dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>