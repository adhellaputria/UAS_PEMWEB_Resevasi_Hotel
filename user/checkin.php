<?php
require_once '../config/database.php';
$page_title = 'Check-in Online - Hotel Akomodasi';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $booking_code = clean($_POST['booking_code']);
    $email = clean($_POST['email']);
    
    if (empty($booking_code) || empty($email)) {
        $error = 'Kode booking dan email harus diisi!';
    } else {
        $query = "SELECT r.*, ro.name as room_name 
                  FROM reservations r 
                  JOIN rooms ro ON r.room_id = ro.id 
                  WHERE r.booking_code = ? AND r.guest_email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $booking_code, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) === 0) {
            $error = 'Kode booking atau email tidak valid!';
        } else {
            $reservation = mysqli_fetch_assoc($result);
            
            if ($reservation['status'] !== 'confirmed') {
                $error = 'Reservasi ini belum dikonfirmasi atau sudah check-in/dibatalkan!';
            } elseif (strtotime($reservation['check_in_date']) > strtotime(date('Y-m-d'))) {
                $error = 'Check-in hanya bisa dilakukan pada tanggal: ' . formatTanggal($reservation['check_in_date']);
            } else {
                $update_query = "UPDATE reservations SET status='checked_in', checked_in_at=NOW() WHERE id=?";
                $update_stmt = mysqli_prepare($conn, $update_query);
                mysqli_stmt_bind_param($update_stmt, "i", $reservation['id']);
                if (mysqli_stmt_execute($update_stmt)) {
                    $success = 'Check-in berhasil! Selamat menikmati masa menginap Anda.';
                } else {
                    $error = 'Check-in gagal! Silakan hubungi front desk.';
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<style>
/* ================= ANIMATION ================= */
@keyframes fadeUp {
    from { opacity:0; transform:translateY(30px); }
    to { opacity:1; transform:none; }
}

/* ================= CARD ================= */
.checkin-card{
    border-radius:22px;
    border:none;
    box-shadow:0 20px 50px rgba(15,23,42,.15);
    animation:fadeUp .6s ease;
}

/* ================= HEADER ================= */
.checkin-header{
    background:linear-gradient(135deg,#0f172a,#020617);
    color:#fff;
    border-radius:22px 22px 0 0;
    padding:2.2rem 1.5rem;
    text-align:center;
    position:relative;
}
.checkin-header::after{
    content:'';
    position:absolute;
    bottom:0;
    left:25%;
    width:50%;
    height:3px;
    background:#d4af37;
    border-radius:3px;
}
.checkin-header i{
    font-size:2.6rem;
    color:#d4af37;
    margin-bottom:.5rem;
}

/* ================= FORM ================= */
.form-control{
    border-radius:14px;
    padding:.8rem 1rem;
    border:1px solid #e5e7eb;
}
.form-control:focus{
    border-color:#d4af37;
    box-shadow:0 0 0 .15rem rgba(212,175,55,.25);
}

/* ================= BUTTON ================= */
.btn-gold{
    background:#d4af37;
    color:#0f172a;
    border:none;
    border-radius:14px;
    padding:.8rem;
    font-weight:800;
}
.btn-gold:hover{
    background:#c9a227;
    color:#0f172a;
}

/* ================= INFO ================= */
.info-box{
    background:#f8fafc;
    border-radius:16px;
    padding:1.1rem 1.25rem;
    font-size:.9rem;
    color:#475569;
    border-left:4px solid #d4af37;
}

/* ================= SUCCESS ================= */
.success-box{
    background:#f8fafc;
    border-radius:18px;
    padding:1.5rem;
    animation:fadeUp .6s ease;
}
.success-box i{
    color:#d4af37;
}
</style>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card checkin-card">
                <div class="checkin-header">
                    <i class="fas fa-key"></i>
                    <h4 class="fw-bold mb-0">Check-in Online</h4>
                </div>

                <div class="card-body p-4">

                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-circle-exclamation"></i> <?= $error ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="success-box text-center">
                            <i class="fas fa-circle-check fa-3x mb-3"></i>
                            <h5 class="fw-bold"><?= $success ?></h5>

                            <?php if (isset($reservation)): ?>
                                <hr>
                                <div class="text-start">
                                    <p><strong>Kode Booking:</strong> <?= $reservation['booking_code'] ?></p>
                                    <p><strong>Nama:</strong> <?= $reservation['guest_name'] ?></p>
                                    <p><strong>Kamar:</strong> <?= $reservation['room_name'] ?></p>
                                    <p><strong>Check-out:</strong> <?= formatTanggal($reservation['check_out_date']) ?></p>
                                </div>
                            <?php endif; ?>

                            <a href="../index.php" class="btn btn-gold mt-3">
                                <i class="fas fa-home"></i> Kembali ke Beranda
                            </a>
                        </div>

                    <?php else: ?>

                        <div class="info-box mb-4">
                            <strong>Petunjuk Check-in:</strong>
                            <ol class="mb-0 ps-3">
                                <li>Reservasi harus sudah dikonfirmasi</li>
                                <li>Gunakan kode booking & email pemesan</li>
                                <li>Check-in hanya sesuai tanggal</li>
                            </ol>
                        </div>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="fw-bold mb-1">Kode Booking</label>
                                <input type="text" name="booking_code" class="form-control"
                                       placeholder="Contoh: BK20241217001" required>
                            </div>

                            <div class="mb-3">
                                <label class="fw-bold mb-1">Email Pemesan</label>
                                <input type="email" name="email" class="form-control"
                                       placeholder="email@example.com" required>
                            </div>

                            <button type="submit" class="btn btn-gold w-100">
                                <i class="fas fa-key"></i> Check-in Sekarang
                            </button>
                        </form>

                        <hr>

                        <div class="text-center">
                            <p class="text-muted mb-2">Belum punya reservasi?</p>
                            <a href="reserve.php" class="btn btn-outline-dark">
                                <i class="fas fa-calendar-check"></i> Buat Reservasi
                            </a>
                        </div>

                    <?php endif; ?>

                </div>
            </div>

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
