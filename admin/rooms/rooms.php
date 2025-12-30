<?php
require_once '../../config/database.php';
requireLogin();
requireAdmin();

$rooms = mysqli_query($conn,"
    SELECT * FROM rooms
    ORDER BY created_at DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manajemen Kamar</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ================= ROOMS STYLE MASTER ================= */
body{
    background:#f8fafc;
    font-family:'Inter',sans-serif;
}

/* =======================
   LAYOUT + SIDEBAR FIX
======================= */
.main-content {
    margin-left: 200px;
    padding: 1.25rem 2.5rem 2rem;
}

/* LAPTOP KECIL */
@media (max-width: 1200px) {
    .main-content {
        padding: 1.5rem 2rem;
    }
}

/* MOBILE */
@media (max-width: 767.98px) {
    .main-content {
        margin-left: 0;
        padding: 1rem;
        padding-top: 5rem;
    }
}

/* ===== PAGE HEADER ===== */
.page-header{
    margin-bottom:2rem;
    animation:fadeInDown .6s ease-out;
}
.page-title{
    font-size:2rem;
    font-weight:800;
    color:#0f172a;
    display:flex;
    align-items:center;
    gap:12px;
}
.page-title i{
    color:#facc15;
    font-size:1.8rem;
}
.page-subtitle{
    color:#64748b;
    font-size:.95rem;
}

/* ===== BUTTON ===== */
.btn-add{
    background:linear-gradient(135deg,#facc15,#f59e0b);
    border:none;
    color:#0f172a;
    font-weight:700;
    padding:.7rem 1.6rem;
    border-radius:14px;
    transition:.25s ease;
    text-decoration:none;          
    display:inline-flex;           
    align-items:center;
    gap:.5rem;
}

.btn-add:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 25px rgba(250,204,21,.35);
    color:#0f172a;
    text-decoration:none;        
}

/* ===== CARD ===== */
.table-card{
    background:#fff;
    border-radius:16px;
    padding:1.5rem;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
    animation:fadeInUp .6s ease-out .2s backwards;
}

/* TABLE */
.table thead th{
    background:#f8fafc;
    color:#475569;
    font-weight:700;
    font-size:.875rem;
    text-transform:uppercase;
}
.table tbody tr{
    transition:.25s;
}
.table tbody tr:hover{
    background:#f8fafc;
}
.table td,.table th{
    vertical-align:middle;
}

/* ROOM IMAGE THUMBNAIL */
.room-thumbnail {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,.1);
}

.no-image {
    width: 80px;
    height: 60px;
    background: linear-gradient(135deg, #e5e7eb, #d1d5db);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6b7280;
    font-size: 1.5rem;
}

/* BADGE */
.badge-available{
    background:#dcfce7;
    color:#166534;
    font-weight:600;
    border-radius:8px;
    padding:.4rem .75rem;
}
.badge-unavailable{
    background:#fee2e2;
    color:#991b1b;
    font-weight:600;
    border-radius:8px;
    padding:.4rem .75rem;
}
.badge{
    font-size:.7rem;
    border-radius:6px;
}
/* ACTION */
.btn-action{
    border-radius:10px;
}
td.fasilitas {
    max-width: 200px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

/* ===== ANIMATIONS ===== */
@keyframes fadeInDown{
    from{
        opacity:0;
        transform:translateY(-30px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}
@keyframes fadeInUp{
    from{
        opacity:0;
        transform:translateY(30px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}
</style>
</head>

<body>
<?php include_once '../../includes/sidebar.php'; ?>

<div class="main-content">
        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 page-header">
            <div>
                <h1 class="page-title">
                    <i class="fa-solid fa-bed"></i>
                    Manajemen Kamar
                </h1>
                <p class="page-subtitle">
                    Kelola data kamar dan ketersediaan hotel
                </p>
            </div>

            <a href="<?= BASE_URL ?>/admin/rooms/room_add.php" class="btn-add">
                <i class="fa-solid fa-plus"></i> Tambah Kamar
            </a>
        </div>

        <!-- ALERT SUCCESS -->
        <?php if (isset($_GET['added'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-check-circle"></i> Kamar berhasil ditambahkan!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['updated'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-check-circle"></i> Kamar berhasil diupdate!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['deleted'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-check-circle"></i> Kamar berhasil dihapus!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <!-- TABLE -->
        <div class="table-card">
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr>
                            <th>Foto</th>
                            <th>Kamar</th>
                            <th>Tipe</th>
                            <th>Harga</th>
                            <th>Kapasitas</th>
                            <th>Tersedia</th>
                            <th>Fasilitas</th> 
                            <th>Status</th>
                            <th width="110">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if(mysqli_num_rows($rooms)==0): ?>
                        <tr>
                            <td colspan="9" class="text-center text-muted py-5">
                                <i class="fa-solid fa-bed fa-3x mb-3 opacity-25"></i>
                                <p>Belum ada data kamar</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while($r=mysqli_fetch_assoc($rooms)): ?>
                        <tr>
                            <td>
                                <?php if(!empty($r['image']) && file_exists('../../uploads/rooms/'.$r['image'])): ?>
                                    <img src="<?= BASE_URL ?>/uploads/rooms/<?= htmlspecialchars($r['image']) ?>" 
                                         alt="<?= htmlspecialchars($r['name']) ?>" 
                                         class="room-thumbnail">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fa-solid fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($r['name']) ?></strong><br>
                                <small class="text-muted">
                                    <?= htmlspecialchars(substr($r['description'], 0, 50)) ?>...
                                </small>
                            </td>
                            <td><?= htmlspecialchars($r['type']) ?></td>
                            <td><?= formatRupiah($r['price']) ?></td>
                            <td><?= $r['capacity'] ?> Orang</td>
                            <td><?= $r['available_rooms'] ?> / <?= $r['total_rooms'] ?></td>
                            <td class="fasilitas">
                                <?= !empty($r['facilities']) ? htmlspecialchars($r['facilities']) : '-' ?>
                            </td>
                            <td>
                                <?php if($r['status']=='available'): ?>
                                    <span class="badge-available">Available</span>
                                <?php else: ?>
                                    <span class="badge-unavailable">Maintenance</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?= BASE_URL ?>/admin/rooms/room_edit.php?id=<?= $r['id'] ?>" 
                                   class="btn btn-outline-warning btn-sm btn-action"
                                   title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/admin/rooms/room_delete.php?id=<?= $r['id'] ?>" 
                                   class="btn btn-outline-danger btn-sm btn-action"
                                   title="Hapus">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>