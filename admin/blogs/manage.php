<?php
require_once '../../config/database.php';
requireLogin();
requireAdmin();

/* =========================
   HANDLE DELETE
========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Get image filename for deletion
    $result = mysqli_query($conn, "SELECT image, title FROM blogs WHERE id=$id");
    $blog = mysqli_fetch_assoc($result);
    
    // Delete from database
    mysqli_query($conn, "DELETE FROM blogs WHERE id=$id");
    
    // Delete image file if exists
    if (!empty($blog['image'])) {
        $image_path = '../../' . $blog['image'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }
    }
    
    // Log activity
    logActivity(
        $conn,
        'DELETE BLOG',
        'Menghapus artikel: ' . ($blog['title'] ?? 'ID ' . $id)
    );
    
    header("Location: " . BASE_URL . "/admin/blogs/manage.php?deleted=1");
    exit;
}

/* =========================
   GET ALL BLOGS
========================= */
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? clean($_GET['category']) : '';

$where = [];
if ($search) {
    $where[] = "(title LIKE '%$search%' OR content LIKE '%$search%')";
}
if ($category_filter) {
    $where[] = "category = '$category_filter'";
}

$where_clause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

$query = "
    SELECT b.*, u.name AS author_name
    FROM blogs b
    LEFT JOIN users u ON b.author_id = u.id
    $where_clause
    ORDER BY b.created_at DESC
";
$blogs = mysqli_query($conn, $query);
$total_results = mysqli_num_rows($blogs);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Blog - Hotel Delitha</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ================= BLOG MANAGEMENT STYLE ================= */
        body {
            background: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        /* LAYOUT */
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

        /* ===== PAGE HEADER ===== */
        .page-header {
            margin-bottom: 2rem;
            animation: fadeInDown .6s ease-out;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-title-group h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 0.5rem;
        }

        .page-title-group h1 i {
            color: #facc15;
            font-size: 1.8rem;
        }

        .page-title-group p {
            color: #64748b;
            font-size: .95rem;
            margin: 0;
        }

        .btn-add {
            background: linear-gradient(135deg, #facc15, #f59e0b);
            border: none;
            color: #0f172a;
            font-weight: 700;
            padding: 0.7rem 1.6rem;
            border-radius: 14px;
            transition: .25s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-add:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(250, 204, 21, .35);
            color: #0f172a;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* ===== ALERTS ===== */
        .alert-custom {
            border-radius: 12px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            animation: fadeInUp .6s ease-out .1s backwards;
        }

        /* ===== FILTER CARD ===== */
        .filter-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
            margin-bottom: 1.5rem;
            animation: fadeInUp .6s ease-out .2s backwards;
        }

        .search-wrapper {
            position: relative;
        }

        .search-input {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem 0.75rem 3rem;
            transition: all 0.2s ease;
            font-size: .9rem;
        }

        .search-input:focus {
            border-color: #facc15;
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.1);
            outline: none;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .filter-select {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            font-size: .9rem;
        }

        .filter-select:focus {
            border-color: #facc15;
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.1);
            outline: none;
        }

        /* ===== TABLE CARD ===== */
        .table-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
            animation: fadeInUp .6s ease-out .3s backwards;
        }

        /* ===== TABLE ===== */
        .table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: .875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        .table tbody tr {
            transition: .25s;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* Blog Thumbnail */
        .blog-thumb {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        /* Category Badge */
        .badge-category {
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .badge-hotel { background: #fef3c7; color: #92400e; }
        .badge-service { background: #dbeafe; color: #1e40af; }
        .badge-facility { background: #dcfce7; color: #166534; }
        .badge-tips { background: #f3e8ff; color: #6b21a8; }

        /* Status Badge */
        .badge-published {
            background: #dcfce7;
            color: #166534;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .badge-draft {
            background: #f3f4f6;
            color: #4b5563;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Action Buttons */
        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-view {
            background: #dbeafe;
            color: #1e40af;
        }

        .btn-view:hover {
            background: #bfdbfe;
            color: #1e40af;
        }

        .btn-edit {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-edit:hover {
            background: #fde68a;
            color: #92400e;
        }

        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-delete:hover {
            background: #fecaca;
            color: #991b1b;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .btn-add {
                width: 100%;
                text-align: center;
            }

            .table {
                font-size: 0.85rem;
            }

            .blog-thumb {
                width: 60px;
                height: 45px;
            }
        }
    </style>
</head>

<body>
    <!-- SIDEBAR -->
    <?php include_once '../../includes/sidebar.php'; ?>

    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- PAGE HEADER -->
        <div class="page-header">
            <div class="page-title-group">
                <h1>
                    <i class="fa-solid fa-blog"></i>
                    Manajemen Blog
                </h1>
                <p>Kelola artikel dan konten blog hotel</p>
            </div>
            <a href="<?= BASE_URL ?>/admin/blogs/form.php" class="btn-add">
                <i class="fa-solid fa-plus"></i> Tambah Artikel
            </a>
        </div>

        <!-- SUCCESS/ERROR ALERTS -->
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success alert-custom">
                <i class="fa-solid fa-check-circle"></i> Artikel berhasil ditambahkan!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-custom">
                <i class="fa-solid fa-check-circle"></i> Artikel berhasil diperbarui!
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success alert-custom">
                <i class="fa-solid fa-check-circle"></i> Artikel berhasil dihapus!
            </div>
        <?php endif; ?>

        <!-- FILTER CARD -->
        <div class="filter-card">
            <form method="GET" action="">
                <div class="row g-3">
                    <!-- Search -->
                    <div class="col-md-8">
                        <div class="search-wrapper">
                            <i class="fa-solid fa-search search-icon"></i>
                            <input type="text" 
                                   name="search" 
                                   class="form-control search-input" 
                                   placeholder="Cari judul atau konten artikel..."
                                   value="<?= htmlspecialchars($search) ?>">
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <div class="col-md-3">
                        <select name="category" class="form-select filter-select">
                            <option value="">Semua Kategori</option>
                            <option value="hotel" <?= $category_filter == 'hotel' ? 'selected' : '' ?>>Tentang Hotel</option>
                            <option value="service" <?= $category_filter == 'service' ? 'selected' : '' ?>>Pelayanan</option>
                            <option value="facility" <?= $category_filter == 'facility' ? 'selected' : '' ?>>Fasilitas</option>
                            <option value="tips" <?= $category_filter == 'tips' ? 'selected' : '' ?>>Tips</option>
                        </select>
                    </div>

                    <!-- Search Button -->
                    <div class="col-md-1">
                        <button type="submit" class="btn btn-add w-100" style="padding: 0.75rem;">
                            <i class="fa-solid fa-search"></i>
                        </button>
                    </div>
                </div>

                <!-- Reset Filter -->
                <?php if ($search || $category_filter): ?>
                <div class="row mt-3">
                    <div class="col-12">
                        <a href="<?= BASE_URL ?>/admin/blogs/manage.php" class="btn btn-secondary">
                            <i class="fa-solid fa-rotate-left"></i> Reset Filter
                        </a>
                        <span class="ms-2 text-muted">
                            Ditemukan <strong><?= $total_results ?></strong> artikel
                        </span>
                    </div>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- TABLE -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Gambar</th>
                            <th>Judul</th>
                            <th>Kategori</th>
                            <th>Penulis</th>
                            <th>Views</th>
                            <th>Status</th>
                            <th>Tanggal</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($total_results > 0): ?>
                            <?php while ($blog = mysqli_fetch_assoc($blogs)): ?>
                                <tr>
                                    <td>
                                        <?php if ($blog['image']): ?>
                                            <img src="<?= BASE_URL . '/' . $blog['image'] ?>" class="blog-thumb" alt="">
                                        <?php else: ?>
                                            <div class="blog-thumb bg-secondary d-flex align-items-center justify-content-center">
                                                <i class="fa-solid fa-image text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($blog['title']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge-category badge-<?= $blog['category'] ?>">
                                            <?= ucfirst($blog['category']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($blog['author_name'] ?? 'Admin') ?></td>
                                    <td>
                                        <i class="fa-solid fa-eye text-muted"></i>
                                        <?= number_format($blog['views']) ?>
                                    </td>
                                    <td>
                                        <?php if ($blog['published']): ?>
                                            <span class="badge-published">Published</span>
                                        <?php else: ?>
                                            <span class="badge-draft">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d M Y', strtotime($blog['created_at'])) ?></td>
                                    <td class="text-center">
                                        <a href="<?= BASE_URL ?>/admin/blogs/detail.php?id=<?= $blog['id'] ?>" 
                                           class="btn-action btn-view me-1" 
                                           title="Lihat">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="<?= BASE_URL ?>/admin/blogs/form.php?id=<?= $blog['id'] ?>" 
                                           class="btn-action btn-edit me-1" 
                                           title="Edit">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <a href="?delete=<?= $blog['id'] ?>" 
                                           class="btn-action btn-delete" 
                                           onclick="return confirm('Yakin ingin menghapus artikel ini?')"
                                           title="Hapus">
                                            <i class="fa-solid fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-inbox"></i>
                                        <p>
                                            <?php if ($search || $category_filter): ?>
                                                Tidak ada artikel yang sesuai dengan filter
                                            <?php else: ?>
                                                Belum ada artikel blog
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>