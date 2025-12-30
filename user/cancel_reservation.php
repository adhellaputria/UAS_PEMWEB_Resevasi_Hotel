<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$page_title = 'Pembatalan Reservasi';

/* ================= SUBMIT PEMBATALAN ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reservation_id = (int)$_POST['reservation_id'];
    $reason_option  = $_POST['reason_option'] ?? '';
    $other_reason   = clean($_POST['other_reason'] ?? '');

    if ($reservation_id <= 0 || $reason_option === '') {
        $_SESSION['error'] = 'Pilih reservasi dan alasan pembatalan.';
    } else {
        // Ambil data reservasi untuk hitung refund
        $checkRes = mysqli_query($conn, "
            SELECT total_price, status, payment_status, check_in_date 
            FROM reservations 
            WHERE id = $reservation_id AND user_id = $user_id
        ");
        $resData = mysqli_fetch_assoc($checkRes);
        
        if (!$resData) {
            $_SESSION['error'] = 'Reservasi tidak ditemukan.';
        } elseif ($resData['payment_status'] !== 'paid') {
            $_SESSION['error'] = 'Hanya reservasi yang sudah dibayar yang bisa dibatalkan dengan refund.';
        } elseif (!in_array($resData['status'], ['pending', 'confirmed'])) {
            $_SESSION['error'] = 'Hanya reservasi dengan status Pending atau Confirmed yang dapat dibatalkan.';
        } elseif ($resData['check_in_date'] < date('Y-m-d')) {
            $_SESSION['error'] = 'Tidak dapat membatalkan reservasi yang sudah melewati tanggal check-in.';
        } else {
            // Hitung refund 70%
            $refund_amount = $resData['total_price'] * 0.7;
            
            if ($reason_option === 'Lainnya') {
                if ($other_reason === '') {
                    $_SESSION['error'] = 'Silakan jelaskan alasan pembatalan Anda.';
                } else {
                    $final_reason = 'Lainnya: ' . $other_reason;
                }
            } else {
                $final_reason = $reason_option;
            }
            
            // Insert jika tidak ada error
            if (!isset($_SESSION['error'])) {
                $insert = mysqli_prepare($conn,
                    "INSERT INTO cancellations (reservation_id, reason, refund_amount, status)
                     VALUES (?, ?, ?, 'pending')"
                );
                mysqli_stmt_bind_param($insert, "isd", $reservation_id, $final_reason, $refund_amount);
                
                if (mysqli_stmt_execute($insert)) {
                    $_SESSION['success'] = 'Pengajuan pembatalan berhasil dikirim. Refund sebesar ' . formatRupiah($refund_amount) . ' (70% dari total pembayaran) akan diproses setelah disetujui admin.';
                } else {
                    $_SESSION['error'] = 'Gagal mengirim pengajuan pembatalan.';
                }
            }
        }
    }
    
    // ✅ REDIRECT untuk refresh data
    header("Location: cancel_reservation.php");
    exit;
}

/* ================= CEK PEMBATALAN PENDING ================= */
$pendingStmt = mysqli_prepare($conn,
    "SELECT c.*, r.booking_code, ro.name AS room_name
     FROM cancellations c
     JOIN reservations r ON c.reservation_id = r.id
     JOIN rooms ro ON r.room_id = ro.id
     WHERE r.user_id = ? AND c.status = 'pending'
     ORDER BY c.created_at DESC
     LIMIT 1"
);
mysqli_stmt_bind_param($pendingStmt, "i", $user_id);
mysqli_stmt_execute($pendingStmt);
$pendingResult = mysqli_stmt_get_result($pendingStmt);
$pendingCancel = mysqli_fetch_assoc($pendingResult);

/* ================= DATA RESERVASI ================= */
$resStmt = mysqli_prepare($conn,
    "SELECT r.*, ro.name AS room_name
     FROM reservations r
     JOIN rooms ro ON r.room_id = ro.id
     WHERE r.user_id = ?
       AND r.status IN ('pending', 'confirmed')
       AND r.payment_status = 'paid'
       AND r.check_in_date >= CURDATE()
       AND NOT EXISTS (
           SELECT 1 FROM cancellations c
           WHERE c.reservation_id = r.id
             AND c.status = 'pending'
       )
     ORDER BY r.created_at DESC"
);

mysqli_stmt_bind_param($resStmt, "i", $user_id);
mysqli_stmt_execute($resStmt);
$reservations = mysqli_stmt_get_result($resStmt);

// Store in array untuk bisa digunakan multiple kali
$reservationList = [];
while($r = mysqli_fetch_assoc($reservations)) {
    $reservationList[] = $r;
}

// Ambil pesan dari session
$error = $_SESSION['error'] ?? '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['error'], $_SESSION['success']);

include '../includes/header.php';
?>

<style>
:root{
    --gold:#d4af37;
    --navy:#0f172a;
}

/* ENTRY */
.page-cancel{animation:fadeSlide .8s ease}
@keyframes fadeSlide{
    from{opacity:0;transform:translateY(30px)}
    to{opacity:1;transform:translateY(0)}
}

/* CARD */
.card-premium{
    border-radius:22px;
    border:1px solid rgba(212,175,55,.28);
    box-shadow:0 25px 60px rgba(15,23,42,.18);
    transition:.4s;
    background:#fff;
}
.card-premium:hover{
    transform:translateY(-3px);
}

/* WAITING HERO */
.waiting-card{
    background:linear-gradient(135deg,#fff7cc,#fde68a);
    border-radius:22px;
    padding:2rem;
    text-align:center;
    animation:pulse 1.8s infinite;
}
@keyframes pulse{
    0%{box-shadow:0 0 0 0 rgba(212,175,55,.5)}
    70%{box-shadow:0 0 0 20px rgba(212,175,55,0)}
    100%{box-shadow:0 0 0 0 rgba(212,175,55,0)}
}

/* FORM */
.form-select,.form-control{
    border-radius:14px;
    padding:.7rem .85rem;
}
.form-select:focus,.form-control:focus{
    border-color:var(--gold);
    box-shadow:0 0 0 3px rgba(212,175,55,.25);
}

/* BUTTON */
.btn-gold{
    background:linear-gradient(135deg,#f7d774,#d4af37);
    border:none;
    color:#0f172a;
    font-weight:600;
    border-radius:14px;
    padding:.75rem;
}
.btn-gold:disabled{
    opacity:.7;
}

/* TIMELINE */
.timeline{
    list-style:none;
    padding-left:0;
}
.timeline li{
    position:relative;
    padding-left:28px;
    margin-bottom:18px;
    color:#334155;
}
.timeline li::before{
    content:'';
    width:10px;
    height:10px;
    background:var(--gold);
    border-radius:50%;
    position:absolute;
    left:0;
    top:6px;
}

/* LOADING */
.btn-loading{
    pointer-events:none;
}
.spinner{
    width:18px;height:18px;
    border:3px solid #fff;
    border-top:3px solid transparent;
    border-radius:50%;
    animation:spin .8s linear infinite;
    display:inline-block;
}
@keyframes spin{to{transform:rotate(360deg)}}

.refund-info{
    background:#f0fdf4;
    border-left:4px solid #22c55e;
    padding:1rem;
    border-radius:8px;
    margin-top:1rem;
}
</style>

<div class="container my-5 page-cancel">

<!-- HEADER -->
<div class="text-center mb-5">
    <h2 class="fw-bold mb-2">Pembatalan Reservasi</h2>
    <p class="text-muted col-lg-6 mx-auto">
        Reservasi dengan status <strong>Pending/Confirmed</strong> yang sudah dibayar dapat dibatalkan. Refund 70% akan diproses setelah disetujui admin.
    </p>
</div>

<!-- WAITING HERO -->
<?php if ($pendingCancel): ?>
<div class="row justify-content-center mb-5">
    <div class="col-lg-8">
        <div class="waiting-card">
            <div style="font-size:2.2rem">⏳</div>
            <h5 class="fw-bold mt-2">Menunggu Persetujuan Admin</h5>
            <p class="mb-1">
                Permintaan pembatalan untuk
                <strong><?= htmlspecialchars($pendingCancel['room_name']) ?></strong>
                (<strong><?= htmlspecialchars($pendingCancel['booking_code']) ?></strong>)
                sedang diproses.
            </p>
            <small class="text-muted">
                Anda tidak dapat mengajukan pembatalan lain sampai proses ini selesai.
            </small>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4 justify-content-center">

<!-- FORM -->
<div class="col-lg-5">
<div class="card card-premium h-100">
<div class="card-body p-4">

<h5 class="fw-semibold mb-4">Form Pengajuan Pembatalan</h5>

<?php if ($error): ?><div class="alert alert-danger alert-dismissible fade show">
    <?= $error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div><?php endif; ?>

<?php if ($success): ?><div class="alert alert-success alert-dismissible fade show">
    <?= $success ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div><?php endif; ?>

<form method="POST" id="cancelForm">

<div class="mb-3">
<label class="fw-semibold">Reservasi</label>
<select name="reservation_id" class="form-select" id="reservationSelect" required <?= $pendingCancel?'disabled':'' ?>>
<option value="">-- Pilih Reservasi --</option>
<?php 
$hasReservations = count($reservationList) > 0;
foreach($reservationList as $r): 
?>
<option value="<?= $r['id'] ?>" data-price="<?= $r['total_price'] ?>">
<?= $r['booking_code'] ?> — <?= $r['room_name'] ?> (<?= formatRupiah($r['total_price']) ?>)
</option>
<?php endforeach; ?>
<?php if(!$hasReservations): ?>
<option value="" disabled>Tidak ada reservasi yang dapat dibatalkan</option>
<?php endif; ?>
</select>
<?php if(!$hasReservations && !$pendingCancel): ?>
<small class="text-muted d-block mt-2">
    💡 Syarat pembatalan: Status <strong>Pending/Confirmed</strong> dan <strong>sudah dibayar (Paid)</strong>
</small>
<?php endif; ?>
</div>

<div class="refund-info d-none" id="refundInfo">
    <strong>💰 Estimasi Refund:</strong>
    <div class="mt-2">
        <div>Harga Reservasi: <span id="originalPrice">-</span></div>
        <div class="text-success fw-bold">Refund 70%: <span id="refundAmount">-</span></div>
    </div>
</div>

<div class="mb-3">
<label class="fw-semibold">Alasan Pembatalan</label>
<select name="reason_option" id="reasonOption"
        class="form-select" required <?= $pendingCancel?'disabled':'' ?>>
<option value="">-- Pilih Alasan --</option>
<option>Perubahan rencana perjalanan</option>
<option>Kesalahan pemesanan</option>
<option>Masalah jadwal / waktu</option>
<option>Kendala pribadi / darurat</option>
<option value="Lainnya">Lainnya</option>
</select>
</div>

<div class="mb-3 d-none" id="otherReasonWrapper">
<textarea name="other_reason" id="otherReason"
          class="form-control" rows="4"
          placeholder="Tuliskan alasan Anda..."></textarea>
</div>

<button class="btn btn-gold w-100 <?= ($pendingCancel || !$hasReservations)?'disabled':'' ?>" 
        id="submitBtn" 
        type="submit"
        <?= ($pendingCancel || !$hasReservations)?'disabled':'' ?>>
<span id="btnText">
    <?php if($pendingCancel): ?>
        Menunggu Persetujuan Admin
    <?php elseif(!$hasReservations): ?>
        Tidak Ada Reservasi yang Dapat Dibatalkan
    <?php else: ?>
        Kirim Pengajuan
    <?php endif; ?>
</span>
</button>

</form>

</div>
</div>
</div>

<!-- TIMELINE -->
<div class="col-lg-5">
<div class="card card-premium h-100">
<div class="card-body p-4">

<h5 class="fw-semibold mb-4">Alur Pembatalan</h5>

<ul class="timeline">
    <li>User mengajukan pembatalan reservasi yang sudah dibayar</li>
    <li>Sistem menghitung refund 70% dari total pembayaran</li>
    <li>Admin meninjau dan memproses permohonan</li>
    <li>Jika disetujui: Refund diproses & stok kamar dikembalikan</li>
    <li>Status reservasi diupdate menjadi Cancelled</li>
</ul>

<div class="alert alert-info mt-3">
    <small>
        <strong>ℹ️ Catatan:</strong><br>
        Refund sebesar 70% dari total pembayaran akan dikembalikan jika pembatalan disetujui.
    </small>
</div>

</div>
</div>
</div>

</div>
</div>

<script>
const reasonSelect=document.getElementById('reasonOption');
const otherWrap=document.getElementById('otherReasonWrapper');
const otherText=document.getElementById('otherReason');
const form=document.getElementById('cancelForm');
const btn=document.getElementById('submitBtn');
const btnText=document.getElementById('btnText');
const resSelect=document.getElementById('reservationSelect');
const refundInfo=document.getElementById('refundInfo');
const originalPrice=document.getElementById('originalPrice');
const refundAmount=document.getElementById('refundAmount');

// Show refund calculation
if(resSelect){
resSelect.addEventListener('change',()=>{
    const selected = resSelect.options[resSelect.selectedIndex];
    const price = parseFloat(selected.dataset.price || 0);
    
    if(price > 0){
        const refund = price * 0.7;
        originalPrice.textContent = 'Rp ' + price.toLocaleString('id-ID');
        refundAmount.textContent = 'Rp ' + refund.toLocaleString('id-ID');
        refundInfo.classList.remove('d-none');
    } else {
        refundInfo.classList.add('d-none');
    }
});
}

// Show other reason field
if(reasonSelect){
reasonSelect.addEventListener('change',()=>{
    if(reasonSelect.value==='Lainnya'){
        otherWrap.classList.remove('d-none');
        otherText.required=true;
    }else{
        otherWrap.classList.add('d-none');
        otherText.required=false;
        otherText.value='';
    }
});
}

// Form submit loading
if(form){
form.addEventListener('submit',(e)=>{
    // Validasi
    if(!resSelect.value){
        e.preventDefault();
        alert('Silakan pilih reservasi terlebih dahulu');
        return;
    }
    if(!reasonSelect.value){
        e.preventDefault();
        alert('Silakan pilih alasan pembatalan');
        return;
    }
    if(reasonSelect.value==='Lainnya' && !otherText.value.trim()){
        e.preventDefault();
        alert('Silakan jelaskan alasan pembatalan Anda');
        return;
    }
    
    // Show loading
    btn.classList.add('btn-loading');
    btn.disabled = true;
    btnText.innerHTML='<span class="spinner"></span> Mengirim...';
});
}
</script>

<?php include '../includes/footer.php'; ?>