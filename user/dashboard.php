<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$page_title = 'Dashboard Pengguna';

/* ================= STATISTIK ================= */
$statsStmt = mysqli_prepare($conn,
"SELECT
 COUNT(*) AS total,
 SUM(status='pending') AS pending,
 SUM(status='confirmed') AS confirmed,
 SUM(status='cancelled') AS cancelled
 FROM reservations WHERE user_id=?"
);
mysqli_stmt_bind_param($statsStmt,"i",$user_id);
mysqli_stmt_execute($statsStmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($statsStmt));

/* ================= RIWAYAT ================= */
$historyStmt = mysqli_prepare($conn,
"SELECT r.*, ro.name AS room_name, c.status AS cancel_status
 FROM reservations r
 JOIN rooms ro ON r.room_id = ro.id
 LEFT JOIN cancellations c ON c.reservation_id = r.id
 WHERE r.user_id=?
 ORDER BY r.created_at DESC
 LIMIT 5"
);
mysqli_stmt_bind_param($historyStmt,"i",$user_id);
mysqli_stmt_execute($historyStmt);
$history = mysqli_stmt_get_result($historyStmt);

include '../includes/header.php';
?>

<style>
:root{
    --navy:#0f172a;
    --gold:#d4af37;
    --soft:#f8fafc;
}

/* ================= GLOBAL CONSISTENCY ================= */
.section{margin-bottom:3rem}
.card-ui{
    background:#fff;
    border-radius:20px;
    box-shadow:0 16px 40px rgba(15,23,42,.12);
    height:100%;
}
.text-soft{color:#64748b}

/* CENTER WRAPPER */
.dashboard-wrapper{
    max-width:1200px;
    margin:0 auto;
}

/* ANIMATION */
.fade-in{animation:fade .8s ease}
@keyframes fade{
    from{opacity:0;transform:translateY(20px)}
    to{opacity:1;transform:none}
}

/* HERO */
.hero{
    background:linear-gradient(135deg,#020617,var(--navy));
    color:#fff;
    border-radius:24px;
    padding:2.2rem 2rem;
    box-shadow:0 30px 70px rgba(2,6,23,.6);
    text-align:center;
}

/* QUICK ACTION */
.action-card{
    padding:1.4rem 1rem;
    text-align:center;
    transition:.35s;
}
.action-card:hover{
    transform:translateY(-6px);
}
.action-icon{
    width:50px;
    height:50px;
    margin:0 auto 12px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg,#fde68a,var(--gold));
    color:var(--navy);
    font-size:1.25rem;
}



/* STAT */
.stat-value{
    font-size:1.8rem;
    font-weight:700;
}

/* HISTORY */
.history-item{
    padding:1rem 0;
    border-bottom:1px solid #e5e7eb;
}
.history-item:last-child{border-bottom:none}

/* BUTTON HISTORY */
.btn-history{
    border:1px solid var(--navy);
    color:var(--navy);
    border-radius:14px;
    padding:.45rem 1.1rem;
    font-size:.85rem;
    transition:.3s;
}
.btn-history:hover{
    background:var(--gold);
    border-color:var(--gold);
    color:#0f172a;
}

/* ================= MOBILE OPTIMIZATION ================= */
@media (max-width:768px){
    .hero{
        padding:1.8rem 1.2rem;
        border-radius:20px;
    }
    .stat-value{font-size:1.5rem}
    .action-card{padding:1.2rem .8rem}
}
</style>

<div class="container my-5 fade-in">
<div class="dashboard-wrapper">

<!-- ================= HERO ================= -->
<div class="hero section">
    <h2 class="fw-bold mb-2">
        Selamat Datang Kembali, <?= htmlspecialchars($_SESSION['name']) ?> 👋
    </h2>
    <p class="mb-0 text-light">
        Kelola reservasi, pembatalan, dan profil Anda dalam satu tempat
    </p>
</div>

<!-- ================= QUICK ACTION ================= -->
<div class="row g-4 section justify-content-center text-center">

<?php
$actions = [
 ['Edit Profil','profile.php?tab=edit','fa-user-pen'],
 ['Reservasi Baru','reserve.php','fa-calendar-plus'],
 ['Check In Online','checkin.php','fa-right-to-bracket'], // ✅ MENU BARU
 ['Reservasi Saya','profile.php?tab=history','fa-clock-rotate-left'],
 ['Pembatalan','cancel_reservation.php','fa-ban'],
 ['Blog & Informasi','../blog.php','fa-newspaper']
];
foreach($actions as $a):
?>
<div class="col-lg-2 col-md-4 col-6">
<a href="<?= $a[1] ?>" class="text-decoration-none text-dark">
    <div class="card-ui action-card">
        <div class="action-icon">
            <i class="fas <?= $a[2] ?>"></i>
        </div>
        <div class="fw-semibold"><?= $a[0] ?></div>
    </div>
</a>
</div>
<?php endforeach; ?>

</div>

<!-- ================= STATISTIK ================= -->
<div class="row g-4 section justify-content-center text-center">

<?php
$statsUI = [
 ['Total Reservasi',$stats['total'], 'text-dark'],
 ['Menunggu',$stats['pending'], 'text-warning'],
 ['Terkonfirmasi',$stats['confirmed'], 'text-success'],
 ['Dibatalkan',$stats['cancelled'], 'text-danger'],
];
foreach($statsUI as $s):
?>
<div class="col-lg-3 col-md-6 col-6">
<div class="card-ui p-4">
    <div class="stat-value <?= $s[2] ?>"><?= $s[1] ?? 0 ?></div>
    <small class="text-soft"><?= $s[0] ?></small>
</div>
</div>
<?php endforeach; ?>

</div>

<!-- ================= RIWAYAT ================= -->
<div class="card-ui p-4 section">
<h5 class="fw-semibold mb-4 text-center">Reservasi Terbaru</h5>

<?php if(mysqli_num_rows($history)>0): ?>
<?php while($h=mysqli_fetch_assoc($history)): ?>
<div class="history-item d-flex justify-content-between align-items-start">

<div>
    <div class="fw-semibold"><?= htmlspecialchars($h['room_name']) ?></div>
    <small class="text-muted">
        <?= date('d M Y',strtotime($h['check_in_date'])) ?> –
        <?= date('d M Y',strtotime($h['check_out_date'])) ?>
    </small><br>
    <small class="text-muted">Kode: <?= $h['booking_code'] ?></small>
</div>

<div class="text-end">
    <span class="badge bg-<?=
        $h['status']=='pending'?'warning':
        ($h['status']=='confirmed'?'success':'secondary')
    ?>">
        <?= ucfirst($h['status']) ?>
    </span>

    <?php if($h['cancel_status']=='pending'): ?>
        <div class="mt-2">
            <span class="badge bg-warning">Menunggu Admin</span>
        </div>
    <?php endif; ?>
</div>

</div>
<?php endwhile; ?>

<div class="text-center mt-4">
    <a href="profile.php?tab=history" class="btn-history">
        Lihat Semua Reservasi →
    </a>
</div>

<?php else: ?>
<p class="text-muted mb-0 text-center">Belum ada reservasi.</p>
<?php endif; ?>
</div>

</div>
</div>

<?php include '../includes/footer.php'; ?>