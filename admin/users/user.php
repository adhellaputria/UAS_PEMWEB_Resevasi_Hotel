<?php
require_once '../../config/database.php';
requireLogin();
requireAdmin();

/* =====================
   AJAX TOGGLE STATUS (SAME FILE)
===================== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax_toggle'])) {
    header('Content-Type: application/json');

    $id = (int)$_POST['id'];

    if ($id == $_SESSION['user_id']) {
        echo json_encode(['success'=>false]);
        exit;
    }

    // ✅ AMBIL NAMA USER untuk log
    $u = mysqli_fetch_assoc(
        mysqli_query($conn,"SELECT name, status FROM users WHERE id=$id")
    );

    if (!$u) {
        echo json_encode(['success'=>false]);
        exit;
    }

    $newStatus = $u['status']==='active'?'inactive':'active';

    mysqli_query(
        $conn,
        "UPDATE users SET status='$newStatus' WHERE id=$id"
    );

    // ✅ LOG AKTIVITAS dengan nama user
    logActivity(
        $conn,
        'TOGGLE USER STATUS',
        'Mengubah status user "' . $u['name'] . '" menjadi ' . $newStatus
    );

    echo json_encode([
        'success'=>true,
        'status'=>$newStatus
    ]);
    exit;
}

/* =====================
   AJAX UPDATE ROLE
===================== */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['ajax_role'])) {
    header('Content-Type: application/json');

    $id = (int)$_POST['id'];
    $role = mysqli_real_escape_string($conn, $_POST['role']);

    if ($id == $_SESSION['user_id']) {
        echo json_encode(['success'=>false]);
        exit;
    }

    // ✅ AMBIL NAMA USER untuk log
    $u = mysqli_fetch_assoc(
        mysqli_query($conn, "SELECT name FROM users WHERE id=$id")
    );

    mysqli_query($conn, "UPDATE users SET role='$role' WHERE id=$id");

    // ✅ LOG AKTIVITAS dengan nama user
    if ($u) {
        logActivity(
            $conn,
            'UPDATE USER ROLE',
            'Mengubah role user "' . $u['name'] . '" menjadi ' . $role
        );
    }

    echo json_encode(['success'=>true]);
    exit;
}

/* =====================
   SEARCH + FILTER
===================== */
$limit = 10;
$page  = max(1,(int)($_GET['page'] ?? 1));
$start = ($page-1)*$limit;

$q = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$role = $_GET['role'] ?? '';

$where=[];
if($q!==''){
    $safe=mysqli_real_escape_string($conn,$q);
    $where[]="(name LIKE '%$safe%' OR email LIKE '%$safe%')";
}
if($status!==''){
    $where[]="status='".mysqli_real_escape_string($conn,$status)."'";
}
if($role!==''){
    $where[]="role='".mysqli_real_escape_string($conn,$role)."'";
}
$whereSql=$where?'WHERE '.implode(' AND ',$where):'';

$total=mysqli_fetch_row(
    mysqli_query($conn,"SELECT COUNT(*) FROM users $whereSql")
)[0];
$totalPage=ceil($total/$limit);

$users=mysqli_query($conn,"
    SELECT * FROM users
    $whereSql
    ORDER BY created_at DESC
    LIMIT $start,$limit
");

// Stats
$statsQuery = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status='active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status='inactive' THEN 1 ELSE 0 END) as inactive,
        SUM(CASE WHEN role='admin' THEN 1 ELSE 0 END) as admin_count
    FROM users
");
$stats = mysqli_fetch_assoc($statsQuery);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manajemen User - Admin Delitha</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background: #f8fafc;
    font-family: 'Inter', sans-serif;
}
/* =======================
   LAYOUT + SIDEBAR FIX
======================= */
.main-content {
    margin-left: 200px;        /* sama dengan sidebar */
    padding: 2rem 2.5rem;      /* biar ga mepet */
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
        padding-top: 5rem;     /* ruang hamburger */
    }
}


/* =======================
   PAGE HEADER with Button
======================= */
.page-header {
    margin-bottom: 2rem;
    animation: fadeInDown 0.6s ease-out;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
}

.page-header-left {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 12px;
}

.page-title i {
    color: #facc15;
    font-size: 1.8rem;
}

.page-subtitle {
    color: #64748b;
    font-size: 0.95rem;
}

/* =======================
   ADD USER BUTTON
======================= */
.btn-add-user {
    background: linear-gradient(135deg, #10b981, #059669);
    border: none;
    color: white;
    font-weight: 600;
    padding: 0.875rem 1.75rem;
    border-radius: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    text-decoration: none;
}

.btn-add-user:hover {
    background: linear-gradient(135deg, #059669, #047857);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
    color: white;
}

.btn-add-user i {
    font-size: 1.1rem;
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    .btn-add-user {
        width: 100%;
        justify-content: center;
    }
}

/* =======================
   STATS CARDS
======================= */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.25rem;
    margin-bottom: 2rem;
    animation: fadeInUp 0.6s ease-out 0.2s backwards;
}

.stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, var(--stat-color), transparent);
}

.stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    margin-bottom: 1rem;
}

.stat-label {
    color: #64748b;
    font-size: 0.875rem;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.stat-value {
    font-size: 1.75rem;
    font-weight: 800;
    color: #0f172a;
}

/* =======================
   FILTER SECTION
======================= */
.filter-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 1.5rem;
    animation: fadeInUp 0.6s ease-out 0.3s backwards;
}

.search-input {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    border-color: #facc15;
    box-shadow: 0 0 0 4px rgba(250, 204, 21, 0.1);
    outline: none;
}

.filter-select {
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.filter-select:focus {
    border-color: #facc15;
    box-shadow: 0 0 0 4px rgba(250, 204, 21, 0.1);
    outline: none;
}

.btn-search {
    background: linear-gradient(135deg, #facc15, #f59e0b);
    border: none;
    color: #0f172a;
    font-weight: 600;
    padding: 0.75rem 1.75rem;
    border-radius: 12px;
    transition: all 0.3s ease;
}

.btn-search:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(250, 204, 21, 0.3);
    color: #0f172a;
}

/* =======================
   TABLE CARD
======================= */
.table-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    animation: fadeInUp 0.6s ease-out 0.4s backwards;
}

.table {
    margin-bottom: 0;
}

.table thead th {
    background: #f8fafc;
    color: #475569;
    font-weight: 700;
    font-size: 0.875rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem;
    border: none;
}

.table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.table tbody tr:hover {
    background: #f8fafc;
}

.table tbody td {
    padding: 1.25rem 1rem;
    vertical-align: middle;
}

/* =======================
   BADGES
======================= */
.badge-active {
    background: linear-gradient(135deg, #dcfce7, #bbf7d0);
    color: #166534;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.8rem;
}

.badge-inactive {
    background: linear-gradient(135deg, #fee2e2, #fecaca);
    color: #991b1b;
    font-weight: 600;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-size: 0.8rem;
}

.badge-role {
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    color: #3730a3;
    font-weight: 600;
    padding: 0.4rem 0.8rem;
    border-radius: 6px;
    font-size: 0.75rem;
    display: inline-block;
}

/* =======================
   ROLE SELECT
======================= */
.role-select {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.3s ease;
    max-width: 120px;
}

.role-select:focus {
    border-color: #facc15;
    box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.1);
}

.role-select:disabled {
    background: #f1f5f9;
    opacity: 0.6;
}

/* =======================
   ACTION BUTTONS
======================= */
.btn-action {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    border: 2px solid;
}

.btn-action:hover {
    transform: scale(1.1);
}

/* =======================
   PAGINATION
======================= */
.pagination {
    gap: 0.5rem;
}

.page-item .page-link {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    color: #475569;
    font-weight: 600;
    padding: 0.5rem 1rem;
    transition: all 0.3s ease;
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #facc15, #f59e0b);
    border-color: #facc15;
    color: #0f172a;
}

.page-item .page-link:hover {
    background: #f8fafc;
    border-color: #facc15;
    color: #0f172a;
}

/* =======================
   RESPONSIVE TABLE
======================= */
@media (max-width: 768px) {
    .table thead {
        display: none;
    } 
    
    .table tbody tr {
        display: block;
        margin-bottom: 1.5rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        background: #fff;
    }
    
    .table tbody td {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border: none;
    }
    
    .table tbody td::before {
        content: attr(data-label);
        font-weight: 700;
        color: #475569;
        font-size: 0.875rem;
        text-transform: uppercase;
    }
}

/* =======================
   ANIMATIONS
======================= */
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

/* =======================
   EMPTY STATE
======================= */
.empty-state {
    text-align: center;
    padding: 3rem 1rem;
}

.empty-state i {
    font-size: 4rem;
    color: #cbd5e1;
    margin-bottom: 1rem;
}

.empty-state h5 {
    color: #64748b;
    font-weight: 600;
}
</style>
</head>

<body>

<!-- SIDEBAR WRAPPER - STICKY -->
<?php include_once '../../includes/sidebar.php'; ?>

<!-- MAIN CONTENT -->
<div class="col-md-10 p-4 main-content">

    <!-- PAGE HEADER WITH BUTTON -->
    <div class="page-header">
        <div class="page-header-left">
            <h1 class="page-title">
                <i class="fa-solid fa-users"></i>
                Manajemen User
            </h1>
            <p class="page-subtitle">Kelola semua pengguna sistem dan hak akses mereka</p>
        </div>
        <div class="page-header-right">
            <a href="user_add.php" class="btn-add-user">
                <i class="fa-solid fa-user-plus"></i>
                <span>Tambah User</span>
            </a>
        </div>
    </div>

    <!-- STATS CARDS -->
    <div class="stats-grid">
        <div class="stat-card" style="--stat-color: #3b82f6;">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="fa-solid fa-users"></i>
            </div>
            <div class="stat-label">Total User</div>
            <div class="stat-value"><?= $stats['total'] ?></div>
        </div>

        <div class="stat-card" style="--stat-color: #10b981;">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="fa-solid fa-user-check"></i>
            </div>
            <div class="stat-label">Aktif</div>
            <div class="stat-value"><?= $stats['active'] ?></div>
        </div>

        <div class="stat-card" style="--stat-color: #ef4444;">
            <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                <i class="fa-solid fa-user-xmark"></i>
            </div>
            <div class="stat-label">Nonaktif</div>
            <div class="stat-value"><?= $stats['inactive'] ?></div>
        </div>

        <div class="stat-card" style="--stat-color: #facc15;">
            <div class="stat-icon" style="background: rgba(250, 204, 21, 0.1); color: #f59e0b;">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="stat-label">Admin</div>
            <div class="stat-value"><?= $stats['admin_count'] ?></div>
        </div>
    </div>

    <!-- FILTER CARD -->
    <div class="filter-card">
        <form class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-semibold small text-muted mb-2">
                    <i class="fa-solid fa-magnifying-glass me-1"></i> Pencarian
                </label>
                <input
                    type="text"
                    name="q"
                    value="<?= htmlspecialchars($q) ?>"
                    class="form-control search-input"
                    placeholder="Cari nama atau email...">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small text-muted mb-2">
                    <i class="fa-solid fa-filter me-1"></i> Status
                </label>
                <select name="status" class="form-select filter-select">
                    <option value="">Semua Status</option>
                    <option value="active" <?= $status=='active'?'selected':'' ?>>Aktif</option>
                    <option value="inactive" <?= $status=='inactive'?'selected':'' ?>>Nonaktif</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-semibold small text-muted mb-2">
                    <i class="fa-solid fa-user-tag me-1"></i> Role
                </label>
                <select name="role" class="form-select filter-select">
                    <option value="">Semua Role</option>
                    <option value="user" <?= $role=='user'?'selected':'' ?>>User</option>
                    <option value="staff" <?= $role=='staff'?'selected':'' ?>>Staff</option>
                    <option value="admin" <?= $role=='admin'?'selected':'' ?>>Admin</option>
                </select>
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-search w-100">
                    <i class="fa-solid fa-search me-2"></i> Cari
                </button>
            </div>
        </form>
    </div>

    <!-- TABLE CARD -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th style="width: 100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($users) == 0): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <i class="fa-solid fa-users-slash"></i>
                                <h5>Tidak ada user ditemukan</h5>
                                <p class="text-muted">Coba ubah filter pencarian Anda</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php while($u = mysqli_fetch_assoc($users)): ?>
                    <tr data-id="<?= $u['id'] ?>">
                        <td data-label="User">
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-gradient rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2);">
                                    <span class="text-white fw-bold">
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    </span>
                                </div>
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($u['name']) ?></div>
                                    <?php if($u['id'] == $_SESSION['user_id']): ?>
                                        <span class="badge bg-warning text-dark" style="font-size: 0.7rem;">You</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>

                        <td data-label="Email">
                            <span class="text-muted"><?= htmlspecialchars($u['email']) ?></span>
                        </td>

                        <td data-label="Role">
                            <select
                                class="form-select role-select"
                                data-user-id="<?= $u['id'] ?>"
                                <?= $u['status']=='inactive' || $u['id']==$_SESSION['user_id'] ? 'disabled' : '' ?>>
                                <option value="user" <?= $u['role']=='user'?'selected':'' ?>>User</option>
                                <option value="staff" <?= $u['role']=='staff'?'selected':'' ?>>Staff</option>
                                <option value="admin" <?= $u['role']=='admin'?'selected':'' ?>>Admin</option>
                            </select>
                        </td>

                        <td data-label="Status">
                            <span class="badge status-badge <?= $u['status']=='active'?'badge-active':'badge-inactive' ?>">
                                <?= ucfirst($u['status']) ?>
                            </span>
                        </td>

                        <td data-label="Aksi">
                            <?php if($u['id'] != $_SESSION['user_id']): ?>
                            <button
                                type="button"
                                class="btn btn-action toggle-status
                                <?= $u['status']=='active'?'btn-outline-danger':'btn-outline-success' ?>"
                                title="<?= $u['status']=='active'?'Nonaktifkan':'Aktifkan' ?>">
                                <i class="fa <?= $u['status']=='active'?'fa-ban':'fa-check' ?>"></i>
                            </button>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        <?php if($totalPage > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for($i=1; $i<=$totalPage; $i++): ?>
                <li class="page-item <?= $i==$page?'active':'' ?>">
                    <a class="page-link" 
                       href="?page=<?= $i ?>&q=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>&role=<?= urlencode($role) ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

</div><!-- end main-content -->


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Toggle Status
document.querySelectorAll('.toggle-status').forEach(btn => {
    btn.onclick = () => {
        const row = btn.closest('tr');
        const id = row.dataset.id;
        const badge = row.querySelector('.status-badge');
        const roleSelect = row.querySelector('.role-select');

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `ajax_toggle=1&id=${id}`
        })
        .then(r => r.json())
        .then(res => {
            if(!res.success) return;

            if(res.status === 'active') {
                badge.textContent = 'Active';
                badge.className = 'badge status-badge badge-active';

                btn.className = 'btn btn-action toggle-status btn-outline-danger';
                btn.innerHTML = '<i class="fa fa-ban"></i>';
                btn.title = 'Nonaktifkan';

                roleSelect.disabled = false;
            } else {
                badge.textContent = 'Inactive';
                badge.className = 'badge status-badge badge-inactive';

                btn.className = 'btn btn-action toggle-status btn-outline-success';
                btn.innerHTML = '<i class="fa fa-check"></i>';
                btn.title = 'Aktifkan';

                roleSelect.disabled = true;
            }
        });
    };
});

// Update Role
document.querySelectorAll('.role-select').forEach(select => {
    select.addEventListener('change', function() {
        const id = this.dataset.userId;
        const role = this.value;

        fetch('', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `ajax_role=1&id=${id}&role=${role}`
        })
        .then(r => r.json())
        .then(res => {
            if(res.success) {
                // Show success feedback
                this.style.borderColor = '#10b981';
                setTimeout(() => {
                    this.style.borderColor = '';
                }, 1000);
            }
        });
    });
});
</script>

</body>
</html>