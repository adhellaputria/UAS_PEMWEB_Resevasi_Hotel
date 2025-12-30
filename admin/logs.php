<?php
require_once '../config/database.php';
requireLogin();
requireAdmin();

/* =====================
   FILTER & PAGINATION
===================== */
$limit = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$start = ($page - 1) * $limit;

// Filter parameters
$admin_filter = $_GET['admin_id'] ?? '';
$action_filter = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = trim($_GET['search'] ?? '');

// Build WHERE clause
$where = [];
if ($admin_filter !== '') {
    $where[] = "l.admin_id = " . (int)$admin_filter;
}
if ($action_filter !== '') {
    $safe_action = mysqli_real_escape_string($conn, $action_filter);
    $where[] = "l.action = '$safe_action'";
}
if ($date_from !== '') {
    $where[] = "DATE(l.created_at) >= '" . mysqli_real_escape_string($conn, $date_from) . "'";
}
if ($date_to !== '') {
    $where[] = "DATE(l.created_at) <= '" . mysqli_real_escape_string($conn, $date_to) . "'";
}
if ($search !== '') {
    $safe_search = mysqli_real_escape_string($conn, $search);
    $where[] = "(l.description LIKE '%$safe_search%' OR u.name LIKE '%$safe_search%')";
}

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$total = mysqli_fetch_row(
    mysqli_query($conn, "
        SELECT COUNT(*) 
        FROM admin_logs l
        LEFT JOIN users u ON l.admin_id = u.id
        $whereSql
    ")
)[0];
$totalPage = ceil($total / $limit);

// Get logs
$logs = mysqli_query($conn, "
    SELECT l.*, u.name as admin_name, u.email as admin_email
    FROM admin_logs l
    LEFT JOIN users u ON l.admin_id = u.id
    $whereSql
    ORDER BY l.created_at DESC
    LIMIT $start, $limit
");

// Get stats
$stats = mysqli_query($conn, "
    SELECT 
        COUNT(*) as total_logs,
        COUNT(DISTINCT admin_id) as total_admins,
        COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_logs,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_logs
    FROM admin_logs
");
$stats_data = mysqli_fetch_assoc($stats);

// Get admin list for filter
$admins = mysqli_query($conn, "
    SELECT DISTINCT u.id, u.name 
    FROM users u
    INNER JOIN admin_logs l ON u.id = l.admin_id
    ORDER BY u.name
");

// Get action types for filter
$actions = mysqli_query($conn, "
    SELECT DISTINCT action 
    FROM admin_logs 
    ORDER BY action
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Activity Logs - Hotel Delitha</title>

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
    padding: 2rem 2.5rem;
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
    animation: fadeInDown 0.6s ease-out;
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
    font-size: 0.95rem;
}

/* Stats Cards */
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
    transition: all 0.3s ease;
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

/* Filter Card */
.filter-card {
    background: #fff;
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    margin-bottom: 1.5rem;
    animation: fadeInUp 0.6s ease-out 0.3s backwards;
}

.form-label {
    font-weight: 600;
    color: #475569;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
}

.form-control, .form-select {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    padding: 0.625rem 0.875rem;
    font-size: 0.9rem;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #facc15;
    box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.1);
}

.btn-filter {
    background: linear-gradient(135deg, #facc15, #f59e0b);
    border: none;
    color: #0f172a;
    font-weight: 600;
    padding: 0.625rem 1.5rem;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(250, 204, 21, 0.3);
    color: #0f172a;
}

.btn-reset {
    background: #f1f5f9;
    border: none;
    color: #475569;
    font-weight: 600;
    padding: 0.625rem 1.5rem;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.btn-reset:hover {
    background: #e2e8f0;
    color: #334155;
}

/* Table Card */
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
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 1rem 0.75rem;
    border: none;
}

.table tbody tr {
    border-bottom: 1px solid #f1f5f9;
    transition: all 0.2s ease;
}

.table tbody tr:hover {
    background: #f8fafc;
}

.table tbody td {
    padding: 1rem 0.75rem;
    vertical-align: middle;
    font-size: 0.875rem;
}

/* Action Badges */
.action-badge {
    display: inline-block;
    padding: 0.35rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
}

.action-create { background: #dcfce7; color: #166534; }
.action-update { background: #dbeafe; color: #1e40af; }
.action-delete { background: #fee2e2; color: #991b1b; }
.action-approve { background: #d1fae5; color: #065f46; }
.action-reject { background: #fef3c7; color: #92400e; }
.action-toggle { background: #e0e7ff; color: #3730a3; }
.action-check { background: #fce7f3; color: #831843; }
.action-default { background: #f1f5f9; color: #475569; }

/* Admin Avatar */
.admin-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.875rem;
    color: white;
    background: linear-gradient(135deg, #667eea, #764ba2);
    margin-right: 0.5rem;
}

/* Pagination */
.pagination {
    gap: 0.5rem;
}

.page-item .page-link {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    color: #475569;
    font-weight: 600;
    padding: 0.5rem 0.875rem;
    transition: all 0.2s ease;
}

.page-item.active .page-link {
    background: linear-gradient(135deg, #facc15, #f59e0b);
    border-color: #facc15;
    color: #0f172a;
}

.page-item .page-link:hover {
    background: #f8fafc;
    border-color: #facc15;
}

/* Animations */
@keyframes fadeInDown {
    from { opacity: 0; transform: translateY(-30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Empty State */
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

/* Responsive */
@media (max-width: 768px) {
    .table thead {
        display: none;
    }
    
    .table tbody tr {
        display: block;
        margin-bottom: 1rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        padding: 1rem;
        background: #fff;
    }
    
    .table tbody td {
        display: flex;
        justify-content: space-between;
        padding: 0.5rem 0;
        border: none;
    }
    
    .table tbody td::before {
        content: attr(data-label);
        font-weight: 700;
        color: #475569;
        font-size: 0.75rem;
        text-transform: uppercase;
    }
}
</style>
</head>

<body>
<?php include_once '../includes/sidebar.php'; ?>

<div class="main-content">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <h1 class="page-title">
            <i class="fa-solid fa-clipboard-list"></i>
            Admin Activity Logs
        </h1>
        <p class="page-subtitle">Monitor semua aktivitas admin di sistem</p>
    </div>

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card" style="--stat-color: #3b82f6;">
            <div class="stat-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div class="stat-label">Total Aktivitas</div>
            <div class="stat-value"><?= number_format($stats_data['total_logs']) ?></div>
        </div>

        <div class="stat-card" style="--stat-color: #10b981;">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <i class="fa-solid fa-calendar-day"></i>
            </div>
            <div class="stat-label">Hari Ini</div>
            <div class="stat-value"><?= $stats_data['today_logs'] ?></div>
        </div>

        <div class="stat-card" style="--stat-color: #facc15;">
            <div class="stat-icon" style="background: rgba(250, 204, 21, 0.1); color: #f59e0b;">
                <i class="fa-solid fa-calendar-week"></i>
            </div>
            <div class="stat-label">7 Hari Terakhir</div>
            <div class="stat-value"><?= $stats_data['week_logs'] ?></div>
        </div>

        <div class="stat-card" style="--stat-color: #8b5cf6;">
            <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div class="stat-label">Admin Aktif</div>
            <div class="stat-value"><?= $stats_data['total_admins'] ?></div>
        </div>
    </div>

    <!-- FILTER -->
    <div class="filter-card">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">
                    <i class="fa-solid fa-user"></i> Admin
                </label>
                <select name="admin_id" class="form-select">
                    <option value="">Semua Admin</option>
                    <?php while($admin = mysqli_fetch_assoc($admins)): ?>
                    <option value="<?= $admin['id'] ?>" <?= $admin_filter == $admin['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($admin['name']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label">
                    <i class="fa-solid fa-tag"></i> Tipe Aktivitas
                </label>
                <select name="action" class="form-select">
                    <option value="">Semua Aktivitas</option>
                    <?php while($action = mysqli_fetch_assoc($actions)): ?>
                    <option value="<?= $action['action'] ?>" <?= $action_filter == $action['action'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($action['action']) ?>
                    </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">
                    <i class="fa-solid fa-calendar"></i> Dari Tanggal
                </label>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($date_from) ?>">
            </div>

            <div class="col-md-2">
                <label class="form-label">
                    <i class="fa-solid fa-calendar"></i> Sampai Tanggal
                </label>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($date_to) ?>">
            </div>

            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-filter flex-fill">
                    <i class="fa-solid fa-filter"></i> Filter
                </button>
                <a href="admin_logs.php" class="btn btn-reset">
                    <i class="fa-solid fa-rotate-right"></i>
                </a>
            </div>

            <div class="col-12">
                <label class="form-label">
                    <i class="fa-solid fa-magnifying-glass"></i> Cari Deskripsi
                </label>
                <input type="text" 
                       name="search" 
                       class="form-control" 
                       placeholder="Cari berdasarkan deskripsi atau nama admin..."
                       value="<?= htmlspecialchars($search) ?>">
            </div>
        </form>
    </div>

    <!-- TABLE -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th style="width: 180px;">Admin</th>
                        <th style="width: 150px;">Aktivitas</th>
                        <th>Deskripsi</th>
                        <th style="width: 120px;">IP Address</th>
                        <th style="width: 150px;">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(mysqli_num_rows($logs) == 0): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <h5>Tidak ada log ditemukan</h5>
                                <p class="text-muted">Coba ubah filter pencarian</p>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php while($log = mysqli_fetch_assoc($logs)): ?>
                    <tr>
                        <td data-label="ID">
                            <span class="text-muted">#<?= $log['id'] ?></span>
                        </td>

                        <td data-label="Admin">
                            <div class="d-flex align-items-center">
                                <span class="admin-avatar">
                                    <?= strtoupper(substr($log['admin_name'] ?? 'U', 0, 1)) ?>
                                </span>
                                <div>
                                    <div class="fw-semibold"><?= htmlspecialchars($log['admin_name'] ?? 'Unknown') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($log['admin_email'] ?? '') ?></small>
                                </div>
                            </div>
                        </td>

                        <td data-label="Aktivitas">
                            <?php
                            $action = $log['action'];
                            $badge_class = 'action-default';
                            
                            if (strpos($action, 'CREATE') !== false) $badge_class = 'action-create';
                            elseif (strpos($action, 'UPDATE') !== false) $badge_class = 'action-update';
                            elseif (strpos($action, 'DELETE') !== false) $badge_class = 'action-delete';
                            elseif (strpos($action, 'APPROVE') !== false) $badge_class = 'action-approve';
                            elseif (strpos($action, 'CANCEL') !== false || strpos($action, 'REJECT') !== false) $badge_class = 'action-reject';
                            elseif (strpos($action, 'TOGGLE') !== false) $badge_class = 'action-toggle';
                            elseif (strpos($action, 'CHECK') !== false) $badge_class = 'action-check';
                            ?>
                            <span class="action-badge <?= $badge_class ?>">
                                <?= htmlspecialchars($action) ?>
                            </span>
                        </td>

                        <td data-label="Deskripsi">
                            <?= htmlspecialchars($log['description']) ?>
                        </td>

                        <td data-label="IP">
                            <code style="font-size: 0.75rem;"><?= htmlspecialchars($log['ip_address']) ?></code>
                        </td>

                        <td data-label="Waktu">
                            <small class="text-muted">
                                <?= date('d M Y', strtotime($log['created_at'])) ?><br>
                                <?= date('H:i:s', strtotime($log['created_at'])) ?>
                            </small>
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
                <?php
                // Build query string for pagination
                $query_params = $_GET;
                
                for($i = 1; $i <= $totalPage; $i++): 
                    $query_params['page'] = $i;
                    $query_string = http_build_query($query_params);
                ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= $query_string ?>">
                        <?= $i ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>