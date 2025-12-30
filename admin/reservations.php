<?php
require_once '../config/database.php';
requireLogin();
requireAdmin();

/* =========================
   FILTER & SEARCH
========================= */
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? clean($_GET['status']) : '';
$date_from = isset($_GET['date_from']) ? clean($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? clean($_GET['date_to']) : '';

/* =========================
   QUERY BUILDER
========================= */
$where = [];
if ($search) {
    $where[] = "(r.booking_code LIKE '%$search%' OR r.guest_name LIKE '%$search%' OR r.guest_email LIKE '%$search%' OR u.name LIKE '%$search%' OR ro.name LIKE '%$search%')";
}
if ($status_filter) {
    $where[] = "r.status = '$status_filter'";
}
if ($date_from) {
    $where[] = "r.check_in_date >= '$date_from'";
}
if ($date_to) {
    $where[] = "r.check_in_date <= '$date_to'";
}

$where_clause = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';

// ✅ LEFT JOIN ke cancellations untuk cek pending cancellation
$query = "
    SELECT 
        r.*,
        u.name AS user_name,
        u.email AS user_account_email,
        u.phone AS user_phone,
        ro.name AS room_name,
        ro.type AS room_type,
        ro.price,
        c.id AS cancellation_id,
        c.status AS cancellation_status
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    JOIN rooms ro ON r.room_id = ro.id
    LEFT JOIN cancellations c ON r.id = c.reservation_id AND c.status = 'pending'
    $where_clause
    ORDER BY 
        CASE r.status
            WHEN 'pending' THEN 1
            WHEN 'confirmed' THEN 2
            WHEN 'checked_in' THEN 3
            WHEN 'checked_out' THEN 4
            WHEN 'cancelled' THEN 5
        END,
        r.created_at DESC
";
$reservations = mysqli_query($conn, $query);
$total_results = mysqli_num_rows($reservations);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen Reservasi - Hotel Delitha</title>

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
            transition: margin-left .3s ease, padding .3s ease;
        }

        @media (max-width: 1200px) {
            .main-content {
                padding: 1.5rem;
            }

            .filter-card .col-md-4,
            .filter-card .col-md-3 {
                flex: 0 0 50%;
                max-width: 50%;
            }

            .filter-card .col-md-2,
            .filter-card .col-md-1 {
                flex: 0 0 50%;
                max-width: 50%;
            }

            .filter-card .row {
                row-gap: 1rem;
            }

            .filter-card button {
                width: 100%;
            }
        }

        @media (max-width: 767.98px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 5rem;
            }

            .filter-card .row > div {
                flex: 0 0 100%;
                max-width: 100%;
            }

            .filter-card {
                padding: 1rem;
            }
        }

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
            margin: 0;
        }

        .filter-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
            margin-bottom: 1.5rem;
            animation: fadeInUp .6s ease-out .1s backwards;
        }

        .filter-title {
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .filter-title i {
            color: #facc15;
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
            pointer-events: none;
        }

        .filter-select, .filter-date {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            transition: all 0.2s ease;
            font-size: .9rem;
        }

        .filter-select:focus, .filter-date:focus {
            border-color: #facc15;
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.1);
            outline: none;
        }

        .filter-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #475569;
            margin-bottom: 0.5rem;
        }

        .btn-filter {
            background: linear-gradient(135deg, #facc15, #f59e0b);
            border: none;
            color: #0f172a;
            font-weight: 700;
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            transition: all 0.25s ease;
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
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            transition: all 0.25s ease;
        }

        .btn-reset:hover {
            background: #e2e8f0;
            color: #334155;
        }

        .results-info {
            padding: 1rem 1.5rem;
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .results-count {
            font-size: 0.9rem;
            color: #475569;
            font-weight: 600;
        }

        .results-count strong {
            color: #0f172a;
            font-size: 1.1rem;
        }

        .table-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
            animation: fadeInUp .6s ease-out .2s backwards;
        }

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

        .table td, .table th {
            vertical-align: middle;
        }

        .badge-status {
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-block;
        }

        .pending {
            background: #fef3c7;
            color: #92400e;
        }

        .confirmed {
            background: #dbeafe;
            color: #1e40af;
        }

        .checked_in {
            background: #e0f2fe;
            color: #0369a1;
        }

        .checked_out {
            background: #dcfce7;
            color: #166534;
        }

        .cancelled {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-action {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            transition: all 0.2s ease;
        }

        .btn-action:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-action:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        .btn-approve {
            background: #dcfce7;
            color: #166534;
        }

        .btn-approve:hover:not(:disabled) {
            background: #bbf7d0;
            color: #166534;
        }

        .btn-reject {
            background: #fee2e2;
            color: #991b1b;
        }

        .btn-reject:hover:not(:disabled) {
            background: #fecaca;
            color: #991b1b;
        }

        .btn-checkout {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-checkout:hover:not(:disabled) {
            background: #fde68a;
            color: #92400e;
        }

        /* ✅ BADGE PENDING CANCELLATION */
        .badge-warning-cancel {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffc107;
            padding: 0.35rem 0.6rem;
            border-radius: 8px;
            font-size: 0.7rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            animation: pulseWarning 2s infinite;
        }

        @keyframes pulseWarning {
            0%, 100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.7);
            }
            50% {
                box-shadow: 0 0 0 8px rgba(255, 193, 7, 0);
            }
        }

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

        .empty-state p {
            margin: 0;
            font-size: 1rem;
        }

        .guest-info {
            font-weight: 600;
            color: #0f172a;
        }
        
        .guest-email {
            color: #3b82f6;
            font-size: 0.85rem;
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                padding-top: 5rem;
            }

            .filter-card {
                padding: 1rem;
            }

            .results-info {
                padding: 0.75rem 1rem;
            }

            .table {
                font-size: 0.85rem;
            }

            .table thead th {
                font-size: 0.65rem;
                padding: 0.75rem 0.5rem;
            }

            .table tbody td {
                padding: 0.75rem 0.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .page-title i {
                font-size: 1.3rem;
            }

            .btn-action {
                width: 32px;
                height: 32px;
                font-size: 0.85rem;
            }

            .btn-filter, .btn-reset {
                width: 100%;
                margin-top: 0.5rem;
            }
        }
    </style>
</head>

<body>
<?php include_once '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fa-solid fa-calendar-check"></i>
            Manajemen Reservasi
        </h1>
        <p class="page-subtitle">
            Kelola seluruh data pemesanan kamar hotel
        </p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fa-solid fa-circle-check"></i>
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="filter-card">
        <div class="filter-title">
            <i class="fa-solid fa-filter"></i>
            Filter & Pencarian
        </div>

        <form method="GET" action="">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="filter-label">Cari Reservasi</label>
                    <div class="search-wrapper">
                        <i class="fa-solid fa-search search-icon"></i>
                        <input type="text" 
                               name="search" 
                               class="form-control search-input" 
                               placeholder="Kode, Nama Pemesan, Email, Kamar..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="filter-label">Status</label>
                    <select name="status" class="form-select filter-select">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $status_filter == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="checked_in" <?= $status_filter == 'checked_in' ? 'selected' : '' ?>>Checked In</option>
                        <option value="checked_out" <?= $status_filter == 'checked_out' ? 'selected' : '' ?>>Checked Out</option>
                        <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="filter-label">Dari Tanggal</label>
                    <input type="date" 
                           name="date_from" 
                           class="form-control filter-date" 
                           value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div class="col-md-2">
                    <label class="filter-label">Sampai Tanggal</label>
                    <input type="date" 
                           name="date_to" 
                           class="form-control filter-date" 
                           value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <div class="col-md-1 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-filter w-100" title="Terapkan Filter">
                        <i class="fa-solid fa-search"></i>
                    </button>
                </div>
            </div>

            <?php if($search || $status_filter || $date_from || $date_to): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <a href="<?= BASE_URL ?>/admin/reservations.php" class="btn btn-reset">
                        <i class="fa-solid fa-rotate-left"></i> Reset Filter
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <?php if($search || $status_filter || $date_from || $date_to): ?>
    <div class="results-info">
        <div class="results-count">
            <i class="fa-solid fa-circle-info"></i>
            Ditemukan <strong><?= $total_results ?></strong> hasil
        </div>
        <div>
            <?php if($search): ?>
                <span class="badge bg-warning text-dark me-1">
                    <i class="fa-solid fa-search"></i> "<?= htmlspecialchars($search) ?>"
                </span>
            <?php endif; ?>
            <?php if($status_filter): ?>
                <span class="badge bg-info text-dark me-1">
                    <i class="fa-solid fa-tag"></i> <?= ucfirst($status_filter) ?>
                </span>
            <?php endif; ?>
            <?php if($date_from || $date_to): ?>
                <span class="badge bg-secondary me-1">
                    <i class="fa-solid fa-calendar"></i> 
                    <?= $date_from ? date('d/m/Y', strtotime($date_from)) : '...' ?> - 
                    <?= $date_to ? date('d/m/Y', strtotime($date_to)) : '...' ?>
                </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="table-card">
        <div class="table-responsive">
            <table class="table align-middle">

                <thead>
                    <tr>
                        <th>Kode Booking</th>
                        <th>Pemesan</th>
                        <th>Kamar</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if($total_results > 0): ?>
                        <?php while($r = mysqli_fetch_assoc($reservations)): ?>
                            <?php 
                            // ✅ CEK APAKAH ADA PENDING CANCELLATION
                            $hasPendingCancel = !empty($r['cancellation_id']) && $r['cancellation_status'] === 'pending';
                            ?>
                            <tr>
                                <td><strong><?= $r['booking_code'] ?></strong></td>

                                <td>
                                    <div class="guest-info"><?= htmlspecialchars($r['guest_name']) ?></div>
                                    <div class="guest-email">
                                        <i class="fa-solid fa-envelope"></i> 
                                        <?= htmlspecialchars($r['guest_email']) ?>
                                    </div>
                                    <small class="text-muted">
                                        <i class="fa-solid fa-user"></i> Akun: <?= htmlspecialchars($r['user_name']) ?>
                                    </small>
                                </td>

                                <td>
                                    <strong><?= $r['room_name'] ?></strong><br>
                                    <small class="text-muted"><?= $r['room_type'] ?></small><br>
                                    <span class="badge bg-secondary mt-1">
                                        <?= (int)$r['total_rooms'] ?> kamar
                                    </span>
                                </td>


                                <td><?= date('d M Y', strtotime($r['check_in_date'])) ?></td>

                                <td>
                                    <?= date('d M Y', strtotime($r['check_out_date'])) ?>
                                    <small class="text-muted d-block"><?= $r['total_nights'] ?> malam</small>
                                </td>

                                <td><strong><?= formatRupiah($r['total_price']) ?></strong></td>

                                <td>
                                    <span class="badge-status <?= $r['status'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $r['status'])) ?>
                                    </span>
                                    
                                    <?php if($hasPendingCancel): ?>
                                    <div class="mt-2">
                                        <span class="badge-warning-cancel">
                                            <i class="fa-solid fa-clock"></i>
                                            Pending Cancellation
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </td>

                                <td class="text-center">

                                    <?php if ($r['status'] === 'pending'): ?>

                                        <!-- ✅ DISABLE jika ada pending cancellation -->
                                        <a href="<?= $hasPendingCancel ? '#' : 'action/approve_booking.php?id='.$r['id'] ?>"
                                           class="btn btn-action btn-approve me-1"
                                           title="<?= $hasPendingCancel ? 'Proses pembatalan dulu' : 'Setujui' ?>"
                                           <?= $hasPendingCancel ? 'onclick="return false;" style="pointer-events:none;"' : '' ?>>
                                            <i class="fa-solid fa-check"></i>
                                        </a>

                                        <button class="btn-action btn-reject mb-2"
                                                data-bs-toggle="modal"
                                                data-bs-target="#rejectModal<?= $r['id'] ?>"
                                                <?= $hasPendingCancel 
                                                    ? 'disabled title="Selesaikan proses pembatalan terlebih dahulu"' 
                                                    : 'title="Tolak reservasi"' ?>>
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>


                                        <?php if($hasPendingCancel): ?>
                                        <div class="mt-2">
                                            <a href="cancellations.php" class="btn btn-warning btn-sm">
                                                <i class="fa-solid fa-arrow-right"></i> Proses Pembatalan
                                            </a>
                                        </div>
                                        <?php endif; ?>

                                    <?php elseif ($r['status'] === 'confirmed'): ?>

                                        <!-- ✅ DISABLE jika ada pending cancellation -->
                                        <a href="<?= $hasPendingCancel ? '#' : 'action/checkin_booking.php?id='.$r['id'] ?>"
                                           class="btn btn-action btn-approve"
                                           title="<?= $hasPendingCancel ? 'Proses pembatalan dulu' : 'Check-in' ?>"
                                           <?= $hasPendingCancel ? 'onclick="return false;" style="pointer-events:none;"' : '' ?>>
                                            <i class="fa-solid fa-right-to-bracket"></i>
                                        </a>

                                        <?php if($hasPendingCancel): ?>
                                        <div class="mt-2">
                                            <a href="cancellations.php" class="btn btn-warning btn-sm">
                                                <i class="fa-solid fa-arrow-right"></i> Proses Pembatalan
                                            </a>
                                        </div>
                                        <?php endif; ?>

                                    <?php elseif ($r['status'] === 'checked_in'): ?>

                                        <a href="action/checkout_booking.php?id=<?= $r['id'] ?>"
                                           class="btn btn-action btn-checkout"
                                           onclick="return confirm('Check-out tamu ini?')"
                                           title="Check-out">
                                            <i class="fa-solid fa-right-from-bracket"></i>
                                        </a>

                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>

                                </td>
                            </tr>
                            
                            <!-- MODAL REJECT (PER ROW) -->
                            <div class="modal fade" id="rejectModal<?= $r['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-dialog-centered">
                                <form method="POST" action="action/reject_booking.php?id=<?= $r['id'] ?>">
                                <div class="modal-content">
                                    <div class="modal-header">
                                    <h5 class="modal-title">Tolak Reservasi</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>

                                    <div class="modal-body">
                                    <label class="fw-semibold mb-2">Alasan Pembatalan</label>
                                    <textarea name="admin_notes"
                                                class="form-control"
                                                rows="4"
                                                required
                                                placeholder="Tuliskan alasan pembatalan oleh admin..."></textarea>
                                    </div>

                                    <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        Batal
                                    </button>
                                    <button type="submit" class="btn btn-danger">
                                        Tolak Reservasi
                                    </button>
                                    </div>
                                </div>
                                </form>
                            </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">
                                <div class="empty-state">
                                    <i class="fa-solid fa-inbox"></i>
                                    <p>
                                        <?php if($search || $status_filter || $date_from || $date_to): ?>
                                            Tidak ada hasil yang sesuai dengan filter
                                        <?php else: ?>
                                            Belum ada data reservasi
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