<?php
require_once '../config/database.php';
requireAdmin();

/* =========================
   SEARCH TRANSAKSI (MINI)
========================= */
$search = isset($_GET['search']) ? clean($_GET['search']) : '';
$where = $search ? "WHERE booking_code LIKE '%$search%'" : '';

/* =====================
   DATA KEUANGAN
===================== */
$total = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT SUM(amount) AS total FROM payments
"));

$today = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT SUM(amount) AS total
    FROM payments
    WHERE DATE(created_at)=CURDATE()
"));

$month = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT SUM(amount) AS total
    FROM payments
    WHERE MONTH(created_at)=MONTH(CURDATE())
      AND YEAR(created_at)=YEAR(CURDATE())
"));

/* =====================
   BREAKDOWN INCOME vs REFUND
===================== */
$income = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT SUM(amount) AS total 
    FROM payments 
    WHERE amount > 0
"));

$refunds = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT SUM(ABS(amount)) AS total 
    FROM payments 
    WHERE amount < 0
"));

$transactions = mysqli_query($conn,"
    SELECT booking_code, amount, created_at
    FROM payments
    $where
    ORDER BY created_at DESC
    LIMIT 20
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard Keuangan</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ================= BASE ================= */
body{
    background:#f8fafc;
    font-family:'Inter',sans-serif;
    margin:0;
}

/* ================= MAIN CONTENT ================= */
.main-content{
    margin-left:220px;
    padding:2rem;
    min-height:100vh;
}

@media(max-width:768px){
    .main-content{
        margin-left:0;
        padding:1rem;
        padding-top:5rem;
    }
}

/* ================= TITLE ================= */
.finance-title{
    font-weight:800;
    margin-bottom:1.5rem;
}

/* ================= CARD ================= */
.card-finance{
    background:#fff;
    border-radius:18px;
    padding:1.5rem;
    box-shadow:0 8px 30px rgba(0,0,0,.08);
    transition:.25s;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    height:100%;
}

.card-finance:hover{
    transform:translateY(-5px);
    box-shadow:0 16px 40px rgba(0,0,0,.12);
}

.card-finance h6{
    color:#64748b;
    font-size:.85rem;
    font-weight:600;
}
.card-finance h2{
    font-weight:800;
}

.card-success{
    background:linear-gradient(135deg,#d1fae5,#a7f3d0);
}

.card-danger{
    background:linear-gradient(135deg,#fee2e2,#fecaca);
}

.card-primary{
    background:linear-gradient(135deg,#dbeafe,#bfdbfe);
}

/* ================= TABLE ================= */
.finance-table{
    background:#fff;
    border-radius:18px;
    padding:1.5rem;
    margin-top:1.5rem;
    box-shadow:0 8px 30px rgba(0,0,0,.08);
}

.badge-income{
    background:#d1fae5;
    color:#065f46;
}

.badge-refund{
    background:#fee2e2;
    color:#991b1b;
}

/* ================= MINI SEARCH ================= */
.search-mini .form-control{
    min-width:220px;
    font-size:.85rem;
    border-radius:10px;
}
.search-mini .input-group-text{
    border-radius:10px 0 0 10px;
}
.search-mini .btn{
    border-radius:0 10px 10px 0;
}

/* ===== OPENING ANIMATION ===== */
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

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.finance-title{
    animation: fadeInDown .6s ease-out;
}

.card-finance{
    animation: scaleIn .6s ease-out backwards;
}

.card-finance:nth-child(1){ animation-delay:.1s }
.card-finance:nth-child(2){ animation-delay:.2s }
.card-finance:nth-child(3){ animation-delay:.3s }
.card-finance:nth-child(4){ animation-delay:.4s }
.card-finance:nth-child(5){ animation-delay:.5s }

.finance-table{
    animation: fadeInUp .6s ease-out .6s backwards;
}
</style>
</head>

<body>

<!-- SIDEBAR -->
<?php include_once '../includes/sidebar.php'; ?>

<!-- MAIN CONTENT -->
<div class="main-content">

    <h2 class="finance-title">
        <i class="fa-solid fa-coins text-warning"></i> Dashboard Keuangan
    </h2>

    <!-- SUMMARY CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card-finance card-primary h-100">
                <h6>Pendapatan Bersih (Net)</h6>
                <h2 class="text-primary"><?= formatRupiah($total['total'] ?? 0) ?></h2>
                <small class="text-muted">Total setelah refund</small>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-finance">
                <h6>Pendapatan Hari Ini</h6>
                <h2><?= formatRupiah($today['total'] ?? 0) ?></h2>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card-finance">
                <h6>Pendapatan Bulan Ini</h6>
                <h2><?= formatRupiah($month['total'] ?? 0) ?></h2>
            </div>
        </div>
    </div>

    <!-- BREAKDOWN INCOME VS REFUND -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card-finance card-success h-100">
                <h6>💰 Total Pemasukan</h6>
                <h2 class="text-success"><?= formatRupiah($income['total'] ?? 0) ?></h2>
                <small class="text-muted">Dari pembayaran reservasi</small>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card-finance card-danger">
                <h6>💸 Total Refund</h6>
                <h2 class="text-danger"><?= formatRupiah($refunds['total'] ?? 0) ?></h2>
                <small class="text-muted">Pengembalian pembatalan (70%)</small>
            </div>
        </div>
    </div>

    <!-- TRANSACTION TABLE -->
    <div class="finance-table">

        <!-- HEADER + MINI SEARCH -->
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <h5 class="fw-bold mb-0">
                <i class="fa-solid fa-receipt"></i> Riwayat Transaksi
            </h5>

            <form method="GET" class="search-mini">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fa-solid fa-search text-muted"></i>
                    </span>
                    <input type="text"
                           name="search"
                           class="form-control border-start-0"
                           placeholder="Cari kode booking..."
                           value="<?= htmlspecialchars($search) ?>">
                    <?php if($search): ?>
                    <a href="finance_dashboard.php" class="btn btn-light border">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- TABLE -->
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>Kode Booking</th>
                        <th>Tanggal</th>
                        <th>Tipe</th>
                        <th>Nominal</th>
                    </tr>
                </thead>
                <tbody>
                <?php if(mysqli_num_rows($transactions) > 0): ?>
                    <?php while($t = mysqli_fetch_assoc($transactions)): ?>
                    <?php 
                        $isRefund = $t['amount'] < 0;
                        $displayAmount = abs($t['amount']);
                    ?>
                    <tr>
                        <td><strong><?= $t['booking_code'] ?></strong></td>
                        <td><?= date('d M Y H:i', strtotime($t['created_at'])) ?></td>
                        <td>
                            <?php if($isRefund): ?>
                                <span class="badge badge-refund">
                                    <i class="fa-solid fa-undo"></i> Refund
                                </span>
                            <?php else: ?>
                                <span class="badge badge-income">
                                    <i class="fa-solid fa-arrow-down"></i> Pemasukan
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong class="<?= $isRefund ? 'text-danger' : 'text-success' ?>">
                                <?= $isRefund ? '-' : '+' ?> <?= formatRupiah($displayAmount) ?>
                            </strong>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="text-center text-muted">
                            <?= $search ? 'Tidak ada transaksi dengan kode booking tersebut' : 'Belum ada transaksi' ?>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- LEGEND -->
        <div class="mt-3 pt-3 border-top">
            <small class="text-muted">
                <i class="fa-solid fa-info-circle"></i> 
                <strong>Catatan:</strong> Refund dihitung 70% dari total pembayaran reservasi yang dibatalkan.
            </small>
        </div>

    </div>

</div>

</body>
</html>