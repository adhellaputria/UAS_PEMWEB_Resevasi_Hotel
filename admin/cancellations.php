<?php
require_once '../config/database.php';
requireLogin();
requireAdmin();

/* ================= PROSES APPROVE / REJECT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = (int)$_POST['cancel_id'];
    $action   = $_POST['action'];
    $note     = clean($_POST['admin_notes'] ?? '');
    $admin_id = $_SESSION['user_id'];

    /* ================= APPROVE ================= */
    if ($action === 'approve') {

        // 1. Ambil data booking untuk kalkulasi refund 70%
        $stmt = mysqli_prepare($conn, "
            SELECT c.reservation_id, r.booking_code, r.total_price, r.room_id
            FROM cancellations c
            JOIN reservations r ON c.reservation_id = r.id
            WHERE c.id = ?
        ");
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $cancelData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

        if ($cancelData) {
            // 2. Hitung refund 70% dari total harga
            $actualRefund = $cancelData['total_price'] * 0.7;

            // ✅ START TRANSACTION
            mysqli_begin_transaction($conn);

            try {
                // 3. Update cancellation dengan processed_by & refund 70%
                $stmt = mysqli_prepare($conn, "
                    UPDATE cancellations 
                    SET status='approved',
                        admin_notes=?,
                        refund_amount=?,
                        processed_by=?,
                        processed_at=NOW()
                    WHERE id=?
                ");
                mysqli_stmt_bind_param($stmt, "sdii", $note, $actualRefund, $admin_id, $id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception('Gagal update cancellation');
                }

                // 4. Catat transaksi refund ke payments (NEGATIF)
                $refundNegative = -abs($actualRefund);
                $stmt2 = mysqli_prepare($conn, "
                    INSERT INTO payments (reservation_id, booking_code, amount, created_at)
                    VALUES (?, ?, ?, NOW())
                ");
                mysqli_stmt_bind_param($stmt2, "isd", 
                    $cancelData['reservation_id'], 
                    $cancelData['booking_code'], 
                    $refundNegative
                );
                
                if (!mysqli_stmt_execute($stmt2)) {
                    throw new Exception('Gagal insert payment');
                }

                // 5. Update reservation status
                $stmt3 = mysqli_prepare($conn, "
                    UPDATE reservations
                    SET status='cancelled',
                        payment_status='refunded'
                    WHERE id = ?
                ");
                mysqli_stmt_bind_param($stmt3, "i", $cancelData['reservation_id']);
                
                if (!mysqli_stmt_execute($stmt3)) {
                    throw new Exception('Gagal update reservation');
                }

                // 6. Kembalikan stok kamar
                $stmt4 = mysqli_prepare($conn, "
                    UPDATE rooms
                    SET available_rooms = available_rooms + 1
                    WHERE id = ?
                ");
                mysqli_stmt_bind_param($stmt4, "i", $cancelData['room_id']);
                
                if (!mysqli_stmt_execute($stmt4)) {
                    throw new Exception('Gagal update room stock');
                }

                // ✅ COMMIT
                mysqli_commit($conn);
                
                // 📝 LOG ACTIVITY
                $ip_address = $_SERVER['REMOTE_ADDR'];
                $description = "Menyetujui pembatalan booking {$cancelData['booking_code']} dengan refund Rp " . number_format($actualRefund, 0, ',', '.');
                $log_stmt = mysqli_prepare($conn, "
                    INSERT INTO admin_logs (admin_id, action, description, ip_address, created_at)
                    VALUES (?, 'approve_cancellation', ?, ?, NOW())
                ");
                mysqli_stmt_bind_param($log_stmt, "iss", $admin_id, $description, $ip_address);
                mysqli_stmt_execute($log_stmt);
                
                $_SESSION['success'] = 'Pembatalan berhasil disetujui dan refund 70% telah diproses.';

            } catch (Exception $e) {
                // ❌ ROLLBACK
                mysqli_rollback($conn);
                $_SESSION['error'] = 'Gagal memproses: ' . $e->getMessage();
            }
        } else {
            $_SESSION['error'] = 'Data pembatalan tidak ditemukan.';
        }
    }

    /* ================= REJECT ================= */
    elseif ($action === 'reject') {

        $stmt = mysqli_prepare($conn, "
            UPDATE cancellations
            SET status='rejected',
                admin_notes=?,
                processed_by=?,
                processed_at=NOW()
            WHERE id=?
        ");
        mysqli_stmt_bind_param($stmt, "sii", $note, $admin_id, $id);
        
        if (mysqli_stmt_execute($stmt)) {
            // 📝 LOG ACTIVITY
            $ip_address = $_SERVER['REMOTE_ADDR'];
            
            // Ambil booking code untuk log
            $log_query = mysqli_query($conn, "
                SELECT r.booking_code 
                FROM cancellations c 
                JOIN reservations r ON c.reservation_id = r.id 
                WHERE c.id = $id
            ");
            $log_data = mysqli_fetch_assoc($log_query);
            $booking_code = $log_data['booking_code'] ?? 'Unknown';
            
            $description = "Menolak pembatalan booking {$booking_code}";
            $log_stmt = mysqli_prepare($conn, "
                INSERT INTO admin_logs (admin_id, action, description, ip_address, created_at)
                VALUES (?, 'reject_cancellation', ?, ?, NOW())
            ");
            mysqli_stmt_bind_param($log_stmt, "iss", $admin_id, $description, $ip_address);
            mysqli_stmt_execute($log_stmt);
            
            $_SESSION['success'] = 'Pembatalan ditolak.';
        } else {
            $_SESSION['error'] = 'Gagal menolak pembatalan.';
        }
    }

    header("Location: cancellations.php");
    exit;
}

/* ================= SEARCH & FILTER ================= */
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$status = isset($_GET['status']) ? clean($_GET['status']) : '';

$where = [];

if ($search) {
    $where[] = "(r.booking_code LIKE '%$search%' 
                 OR r.guest_name LIKE '%$search%'
                 OR r.guest_email LIKE '%$search%'
                 OR u.name LIKE '%$search%' 
                 OR u.email LIKE '%$search%' 
                 OR ro.name LIKE '%$search%')";
}

if ($status) {
    $where[] = "c.status = '$status'";
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ================= DATA ================= */
$cancellations = mysqli_query($conn, "
    SELECT 
        c.*,
        r.booking_code,
        r.guest_name,
        r.guest_email,
        r.total_price,
        r.check_in_date,
        r.check_out_date,
        r.total_nights,
        u.name AS user_account_name,
        u.email AS user_account_email,
        ro.name AS room_name
    FROM cancellations c
    JOIN reservations r ON c.reservation_id=r.id
    JOIN users u ON r.user_id=u.id
    JOIN rooms ro ON r.room_id=ro.id
    $whereClause
    ORDER BY 
        CASE c.status
            WHEN 'pending' THEN 1
            WHEN 'approved' THEN 2
            WHEN 'rejected' THEN 3
        END,
        c.created_at DESC
");

$total_results = mysqli_num_rows($cancellations);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pembatalan - Hotel Delitha</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 200px; padding: 2rem 2.5rem; }
        @media (max-width: 1200px) { .main-content { padding: 1.5rem 2rem; } }
        @media (max-width: 767.98px) { .main-content { margin-left: 0; padding: 1rem; padding-top: 5rem; } }
        
        .page-header { margin-bottom: 2rem; animation: fadeInDown .6s ease-out; }
        .page-header h3 { font-size: 2rem; font-weight: 800; color: #0f172a; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 12px; }
        .page-header h3 i { color: #facc15; font-size: 1.8rem; }
        .page-header p { color: #64748b; margin: 0; font-size: .95rem; }
        
        .filter-card { background: #fff; border-radius: 16px; padding: 1.5rem; box-shadow: 0 4px 20px rgba(0, 0, 0, .06); margin-bottom: 1.5rem; animation: fadeInUp .6s ease-out .1s backwards; }
        .filter-title { font-size: 1rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem; display: flex; align-items: center; gap: 8px; }
        .filter-title i { color: #facc15; }
        
        .search-wrapper { position: relative; }
        .search-input { border: 2px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 1rem 0.75rem 3rem !important; font-size: 0.9rem; }
        .search-input:focus { border-color: #facc15; box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.1); outline: none; }
        .search-icon { position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; }
        
        .filter-select { border: 2px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 1rem; font-size: .9rem; }
        .filter-select:focus { border-color: #facc15; box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.1); outline: none; }
        
        .btn-filter { background: linear-gradient(135deg, #facc15, #f59e0b); border: none; color: #0f172a; font-weight: 700; padding: 0.75rem 1.5rem; border-radius: 12px; transition: all 0.25s ease; }
        .btn-filter:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(250, 204, 21, 0.3); color: #0f172a; }
        .btn-reset { background: #f1f5f9; border: none; color: #475569; font-weight: 600; padding: 0.75rem 1.5rem; border-radius: 12px; }
        .btn-reset:hover { background: #e2e8f0; color: #334155; }
        
        .results-info { padding: 1rem 1.5rem; background: linear-gradient(135deg, #f8fafc, #e2e8f0); border-radius: 12px; margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; }
        .results-count { font-size: 0.9rem; color: #475569; font-weight: 600; }
        .results-count strong { color: #0f172a; font-size: 1.1rem; }
        
        .card-ui { background: #fff; border-radius: 16px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06); border: none; overflow: hidden; animation: fadeInUp .6s ease-out .2s backwards; }
        
        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        
        .table-responsive { border-radius: 16px; padding: 1.5rem; }
        .table { margin: 0; }
        .table td, .table th { vertical-align: middle; }
        .table thead th { background: #f8fafc; color: #475569; font-weight: 700; text-transform: uppercase; font-size: .875rem; border: none; padding: 1rem; }
        .table tbody td { padding: 1rem; vertical-align: middle; border-bottom: 1px solid #f1f5f9; color: #334155; }
        .table tbody tr { transition: .25s; }
        .table tbody tr:hover { background: #f8fafc; }
        
        .badge-status { padding: 0.4rem 0.75rem; border-radius: 8px; font-size: 0.75rem; font-weight: 600; display: inline-block; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        
        .btn-action { width: 36px; height: 36px; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; border: none; transition: all 0.2s ease; }
        .btn-action:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
        .btn-approve { background: #dcfce7; color: #166534; }
        .btn-approve:hover { background: #bbf7d0; }
        .btn-reject { background: #fee2e2; color: #991b1b; }
        .btn-reject:hover { background: #fecaca; }
        
        .guest-info { font-weight: 600; color: #0f172a; }
        .guest-email { color: #3b82f6; font-size: 0.85rem; }
        
        .modal-content { border: none; border-radius: 20px; box-shadow: 0 25px 50px rgba(0, 0, 0, 0.15); }
        .modal-header { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: white; border: none; padding: 1.5rem; border-radius: 20px 20px 0 0; }
        .modal-title { font-weight: 700; }
        .modal-body { padding: 2rem; }
        
        .form-label { font-weight: 600; color: #334155; margin-bottom: 0.5rem; }
        .form-control, .form-select { border: 2px solid #e2e8f0; border-radius: 12px; padding: 0.75rem 1rem; }
        .form-control:focus, .form-select:focus { border-color: #facc15; box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.1); }
        
        .info-box { background: #f8fafc; border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; }
        .info-box .info-label { font-size: 0.75rem; color: #64748b; text-transform: uppercase; font-weight: 600; margin-bottom: 0.25rem; }
        .info-box .info-value { font-size: 1rem; color: #0f172a; font-weight: 600; }
        
        .refund-info { background: #dcfce7; border-left: 4px solid #10b981; padding: 1rem; border-radius: 8px; margin-top: 0.5rem; }
        .btn-submit { background: #facc15; color: #0f172a; border: none; padding: 0.75rem 2rem; border-radius: 12px; font-weight: 700; transition: all 0.2s ease; }
        .btn-submit:hover { background: #eab308; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(250, 204, 21, 0.3); color: #0f172a; }
        
        .empty-state { text-align: center; padding: 3rem; color: #94a3b8; }
        .empty-state i { font-size: 3rem; margin-bottom: 1rem; opacity: 0.3; }
        .empty-state p { margin: 0; font-size: 1rem; }
    </style>
</head>
<body>
<?php include_once '../includes/sidebar.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h3><i class="fa-solid fa-ban"></i> Manajemen Pembatalan</h3>
        <p>Kelola pengajuan pembatalan reservasi dari tamu</p>
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
                <div class="col-md-6">
                    <div class="search-wrapper">
                        <i class="fa-solid fa-search search-icon"></i>
                        <input type="text" name="search" class="form-control search-input" 
                               placeholder="Cari Kode Booking, Nama Pemesan, Email, Kamar..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select filter-select">
                        <option value="">Semua Status</option>
                        <option value="pending" <?= $status == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status == 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-filter flex-grow-1">
                        <i class="fa-solid fa-search"></i> Cari
                    </button>
                    <?php if($search || $status): ?>
                    <a href="cancellations.php" class="btn btn-reset">
                        <i class="fa-solid fa-rotate-left"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <?php if($search || $status): ?>
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
            <?php if($status): ?>
            <span class="badge bg-info text-dark me-1">
                <i class="fa-solid fa-tag"></i> <?= ucfirst($status) ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card-ui">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Kode Booking</th>
                        <th>Pemesan</th>
                        <th>Kamar</th>
                        <th>Check In</th>
                        <th>Total Harga</th>
                        <th>Alasan</th>
                        <th>Tanggal Ajuan</th>
                        <th>Status</th>
                        <th class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $no = 0;
                    mysqli_data_seek($cancellations, 0);
                    while($c = mysqli_fetch_assoc($cancellations)): 
                        $no++;
                    ?>
                    <tr>
                        <td><strong><?= $c['booking_code'] ?></strong></td>
                        <td>
                            <div class="guest-info"><?= htmlspecialchars($c['guest_name']) ?></div>
                            <div class="guest-email">
                                <i class="fa-solid fa-envelope"></i> 
                                <?= htmlspecialchars($c['guest_email']) ?>
                            </div>
                            <small class="text-muted">
                                <i class="fa-solid fa-user"></i> Akun: <?= htmlspecialchars($c['user_account_name']) ?>
                            </small>
                        </td>
                        <td><?= $c['room_name'] ?></td>
                        <td>
                            <?= date('d M Y', strtotime($c['check_in_date'])) ?>
                            <small class="text-muted d-block"><?= $c['total_nights'] ?> malam</small>
                        </td>
                        <td><strong><?= formatRupiah($c['total_price']) ?></strong></td>
                        <td>
                            <?php 
                            $reason = htmlspecialchars($c['reason']);
                            echo strlen($reason) > 50 ? substr($reason, 0, 50) . '...' : $reason;
                            ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                        <td>
                            <span class="badge-status badge-<?= $c['status'] ?>">
                                <?= ucfirst($c['status']) ?>
                            </span>
                            <?php if($c['status'] == 'approved' && $c['refund_amount']): ?>
                            <small class="d-block text-success mt-1">
                                <i class="fa-solid fa-money-bill-wave"></i> 
                                Refund: <?= formatRupiah($c['refund_amount']) ?>
                            </small>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <?php if($c['status'] == 'pending'): ?>
                            <button class="btn btn-action btn-approve me-1" 
                                    onclick="openModal(<?= $c['id'] ?>, 'approve', <?= $c['total_price'] ?>, '<?= addslashes($c['booking_code']) ?>', '<?= addslashes($c['guest_name']) ?>', '<?= addslashes($c['room_name']) ?>', '<?= addslashes($c['reason']) ?>')"
                                    title="Setujui">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <button class="btn btn-action btn-reject" 
                                    onclick="openModal(<?= $c['id'] ?>, 'reject', 0, '<?= addslashes($c['booking_code']) ?>', '<?= addslashes($c['guest_name']) ?>', '<?= addslashes($c['room_name']) ?>', '<?= addslashes($c['reason']) ?>')"
                                    title="Tolak">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>

                    <?php if($no == 0): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted">
                            <div class="empty-state">
                                <i class="fa-solid fa-inbox"></i>
                                <p>
                                    <?php if($search || $status): ?>
                                        Tidak ada hasil yang sesuai dengan filter
                                    <?php else: ?>
                                        Belum ada pengajuan pembatalan
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

<!-- MODAL -->
<div class="modal fade" id="actionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">
                    <i class="fa-solid fa-clipboard-check"></i> Konfirmasi Pembatalan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="cancel_id" id="cancel_id">
                <input type="hidden" name="action" id="action_type">

                <div class="info-box">
                    <div class="row">
                        <div class="col-6">
                            <div class="info-label">Kode Booking</div>
                            <div class="info-value" id="info_booking_code">-</div>
                        </div>
                        <div class="col-6">
                            <div class="info-label">Nama Pemesan</div>
                            <div class="info-value" id="info_user_name">-</div>
                        </div>
                    </div>
                    <div class="mt-2">
                        <div class="info-label">Kamar</div>
                        <div class="info-value" id="info_room_name">-</div>
                    </div>
                    <div class="mt-2">
                        <div class="info-label">Alasan User</div>
                        <div class="info-value" style="font-weight:400;font-size:0.9rem" id="info_reason">-</div>
                    </div>
                </div>

                <div id="refund_section" style="display: none;">
                    <div class="refund-info mb-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <strong><i class="fa-solid fa-money-bill-wave"></i> Refund Otomatis 70%</strong>
                                <div class="text-muted" style="font-size:0.85rem">Total: <span id="total_price_display">-</span></div>
                            </div>
                            <div class="text-end">
                                <div class="text-success fw-bold" style="font-size:1.2rem" id="refund_amount_display">-</div>
                                <small class="text-muted">Jumlah Refund</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fa-solid fa-note-sticky"></i> Catatan Admin
                    </label>
                    <textarea name="admin_notes" class="form-control" rows="4" 
                              placeholder="Berikan catatan atau alasan keputusan Anda..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fa-solid fa-times"></i> Batal
                </button>
                <button type="submit" class="btn btn-submit">
                    <i class="fa-solid fa-check"></i> Proses
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openModal(id, action, totalPrice, bookingCode, userName, roomName, reason) {
    document.getElementById('cancel_id').value = id;
    document.getElementById('action_type').value = action;
    document.getElementById('info_booking_code').textContent = bookingCode;
    document.getElementById('info_user_name').textContent = userName;
    document.getElementById('info_room_name').textContent = roomName;
    document.getElementById('info_reason').textContent = reason;

    const modalTitle = document.getElementById('modalTitle');
    const refundSection = document.getElementById('refund_section');

    if (action === 'approve') {
        modalTitle.innerHTML = '<i class="fa-solid fa-check-circle"></i> Setujui Pembatalan';
        refundSection.style.display = 'block';
        
        const refund70 = totalPrice * 0.7;
        document.getElementById('total_price_display').textContent = 'Rp ' + totalPrice.toLocaleString('id-ID');
        document.getElementById('refund_amount_display').textContent = 'Rp ' + refund70.toLocaleString('id-ID');
    } else {
        modalTitle.innerHTML = '<i class="fa-solid fa-times-circle"></i> Tolak Pembatalan';
        refundSection.style.display = 'none';
    }

    new bootstrap.Modal(document.getElementById('actionModal')).show();
}
</script>
</body>
</html></parameter>