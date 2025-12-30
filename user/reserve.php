<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$page_title = 'Reservasi Kamar';

$rooms = mysqli_query($conn, "SELECT * FROM rooms WHERE status='available'");

$error = '';
$success = '';
$booking_code = '';

/* ================= PROSES SUBMIT ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $error = '';
    $success = '';

    // 🚫 ADMIN TIDAK BOLEH MELAKUKAN RESERVASI
    if (isAdmin()) {
        $error = 'Reservasi kamar hanya dapat dilakukan menggunakan akun user.';
    }

    if (!$error) {

        // Ambil data
        $room_id       = intval($_POST['room_id'] ?? 0);
        $check_in      = $_POST['check_in'] ?? '';
        $check_out     = $_POST['check_out'] ?? '';
        $guest_name    = clean($_POST['guest_name'] ?? '');
        $guest_email   = clean($_POST['guest_email'] ?? '');
        $guest_phone   = clean($_POST['guest_phone'] ?? '');
        $notes         = clean($_POST['notes'] ?? '');
        $payment       = clean($_POST['payment_method'] ?? '');
        
        // ✅ AMBIL DATA JUMLAH KAMAR & TAMU
        $total_rooms    = intval($_POST['total_rooms'] ?? 1);
        $total_adults   = intval($_POST['total_adults'] ?? 1);
        $total_children = intval($_POST['total_children'] ?? 0);
        $total_guests   = $total_adults + $total_children;

        // Validasi tanggal
        if (!empty($check_in) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_in)) {
            $error = 'Format tanggal check-in tidak valid.';
        } elseif (!empty($check_out) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $check_out)) {
            $error = 'Format tanggal check-out tidak valid.';
        } elseif (
            empty($room_id) || empty($check_in) || empty($check_out) ||
            empty($guest_name) || empty($guest_email) || empty($guest_phone) ||
            empty($payment)
        ) {
            $error = 'Semua data wajib diisi.';
        } elseif (strtotime($check_out) <= strtotime($check_in)) {
            $error = 'Tanggal check-out harus setelah check-in.';
        } 
        // ✅ VALIDASI JUMLAH KAMAR & TAMU
        elseif ($total_rooms < 1) {
            $error = 'Jumlah kamar minimal 1.';
        } elseif ($total_adults < 1) {
            $error = 'Minimal 1 tamu dewasa.';
        } elseif ($total_children < 0) {
            $error = 'Jumlah anak tidak boleh negatif.';
        } else {

            $booking_code = generateBookingCode();

            // Hitung malam
            $date1  = new DateTime($check_in);
            $date2  = new DateTime($check_out);
            $nights = $date1->diff($date2)->days;

            // ✅ Ambil data kamar (harga, kapasitas, stok)
            $room_query = mysqli_query($conn, "
                SELECT price, capacity, available_rooms, name 
                FROM rooms 
                WHERE id = $room_id
            ");
            $room_data = mysqli_fetch_assoc($room_query);

            if (!$room_data) {
                $error = 'Kamar tidak ditemukan.';
            } 
            // ✅ VALIDASI STOK KAMAR
            elseif ($total_rooms > $room_data['available_rooms']) {
                $error = 'Kamar tersedia hanya ' . $room_data['available_rooms'] . ' unit. Anda memesan ' . $total_rooms . ' kamar.';
            } 
            // ✅ VALIDASI KAPASITAS TAMU
            elseif ($total_guests > ($room_data['capacity'] * $total_rooms)) {
                $max_capacity = $room_data['capacity'] * $total_rooms;
                $error = 'Kapasitas maksimal ' . $max_capacity . ' orang untuk ' . $total_rooms . ' kamar. Total tamu Anda: ' . $total_guests . ' orang.';
            } else {

                // ✅ HITUNG TOTAL HARGA
                $total_price = $room_data['price'] * $nights * $total_rooms;

                // ✅ START TRANSACTION untuk memastikan data konsisten
                mysqli_begin_transaction($conn);

                try {
                    // ✅ INSERT RESERVASI dengan total_rooms & total_guests
                    $stmt = mysqli_prepare($conn, "
                        INSERT INTO reservations
                        (booking_code, user_id, room_id,
                         guest_name, guest_email, guest_phone,
                         check_in_date, check_out_date,
                         total_rooms, total_guests, total_nights, total_price,
                         notes, status, payment_status)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,'pending','paid')
                    ");

                    mysqli_stmt_bind_param(
                        $stmt,
                        "siisssssiiids",
                        $booking_code,
                        $user_id,
                        $room_id,
                        $guest_name,
                        $guest_email,
                        $guest_phone,
                        $check_in,
                        $check_out,
                        $total_rooms,
                        $total_guests,
                        $nights,
                        $total_price,
                        $notes
                    );

                    if (!mysqli_stmt_execute($stmt)) {
                        throw new Exception('Gagal menyimpan reservasi.');
                    }

                    // 2️⃣ AMBIL ID RESERVASI YANG BARU SAJA DI-INSERT
                    $reservation_id = mysqli_insert_id($conn);

                    // 3️⃣ INSERT KE TABEL PAYMENTS (POSITIF = Pemasukan)
                    $stmt_payment = mysqli_prepare($conn, "
                        INSERT INTO payments
                        (reservation_id, booking_code, amount, created_at)
                        VALUES (?, ?, ?, NOW())
                    ");

                    mysqli_stmt_bind_param(
                        $stmt_payment,
                        "isd",
                        $reservation_id,
                        $booking_code,
                        $total_price
                    );

                    if (!mysqli_stmt_execute($stmt_payment)) {
                        throw new Exception('Gagal menyimpan data pembayaran.');
                    }

                    // ✅ COMMIT TRANSACTION - Semua berhasil
                    mysqli_commit($conn);
                    $success = 'Reservasi berhasil dan pembayaran telah tercatat.';

                } catch (Exception $e) {
                    // ❌ ROLLBACK jika ada error
                    mysqli_rollback($conn);
                    $error = $e->getMessage();
                }
            }
        }
    }
}

include '../includes/header.php';
?>

<style>
/* ================= UI FIX ================= */
.reserve-card{
    border:none;
    border-radius:32px;
    box-shadow:0 30px 70px rgba(0,0,0,.08);
    overflow:hidden;
}

.progress-step{
    display:flex;
    justify-content:space-between;
    padding:1.5rem 2rem;
    background:#f8fafc;
}
.step{
    flex:1;
    text-align:center;
    font-weight:700;
    color:#94a3b8;
}
.step.active{color:#0f172a}
.step.done{color:#16a34a}

.step-body{
    padding:3rem 2.5rem;
    display:none;
    animation:slide .4s ease;
}
.step-body.active{display:block}

@keyframes slide{
    from{opacity:0;transform:translateX(40px)}
    to{opacity:1;transform:none}
}

/* FORM */
.form-group{margin-bottom:1.6rem}
.form-control,.form-select,textarea{
    border-radius:14px;
    min-height:48px;
}

/* BUTTON AREA */
.step-actions{
    margin-top:2.5rem;
    padding-top:1.8rem;
    border-top:1px solid #e5e7eb;
    display:flex;
    justify-content:space-between;
    gap:1rem;
}

.btn-gold{
    background:linear-gradient(135deg,#fde68a,#facc15);
    border-radius:999px;
    font-weight:800;
    padding:.85rem 2.2rem;
    border:none;
}
.btn-outline{
    border-radius:999px;
    padding:.85rem 2.2rem;
}

/* ERROR MESSAGE */
.error-msg{
    background:#fee2e2;
    border:1px solid #fca5a5;
    color:#dc2626;
    padding:.75rem 1rem;
    border-radius:8px;
    margin-bottom:1rem;
    font-size:.9rem;
    display:none;
}
.error-msg.show{display:block}

.payment-info{
    background:#f0fdf4;
    border-left:4px solid #22c55e;
    padding:1rem;
    border-radius:8px;
    margin-top:1rem;
}

/* ✅ HIGHLIGHT EMAIL INFO */
.email-info-box {
    background: #dbeafe;
    border-left: 4px solid #3b82f6;
    padding: 1rem;
    border-radius: 8px;
    margin-top: 1rem;
}

.email-info-box strong {
    color: #1e40af;
}

/* ✅ INFO BOX UNTUK KAPASITAS */
.capacity-info {
    background: #f0f9ff;
    border: 2px solid #3b82f6;
    border-radius: 12px;
    padding: 1rem;
    margin-top: 1rem;
    font-size: 0.9rem;
}

.capacity-info strong {
    color: #1e40af;
    display: block;
    margin-bottom: 0.5rem;
}

/* ✅ GUEST INPUT ROW */
.guest-input-row {
    display: flex;
    gap: 1rem;
    align-items: end;
}

.guest-input-row .form-group {
    flex: 1;
    margin-bottom: 0;
}

/* ✅ SUMMARY BOX */
.booking-summary {
    background: #fef3c7;
    border-left: 4px solid #f59e0b;
    padding: 1.2rem;
    border-radius: 12px;
    margin-top: 1rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.95rem;
}

.summary-row:last-child {
    margin-bottom: 0;
    padding-top: 0.75rem;
    border-top: 2px solid #fbbf24;
    font-weight: 700;
    font-size: 1.1rem;
    color: #92400e;
}
</style>

<div class="container my-5">
<div class="col-lg-8 mx-auto">

<div class="reserve-card">

<!-- PROGRESS -->
<div class="progress-step">
    <div class="step active" id="s1">Pilih Kamar & Jumlah</div>
    <div class="step" id="s2">Data Pemesan</div>
    <div class="step" id="s3">Konfirmasi</div>
</div>

<?php if ($error): ?>
<div class="alert alert-danger m-4"><?= $error ?></div>
<?php endif; ?>

<?php if ($success): ?>
<div class="p-5 text-center">
    <h4 class="fw-bold mb-2">Reservasi & Pembayaran Berhasil 🎉</h4>
    <p class="text-muted">Kode Booking Anda</p>
    <h3 class="fw-bold text-primary"><?= $booking_code ?></h3>
    
    <!-- ✅ TAMPILKAN DETAIL RESERVASI -->
    <div class="booking-summary text-start mx-auto" style="max-width:450px">
        <strong>📋 Detail Reservasi:</strong>
        <div class="summary-row">
            <span>Jumlah Kamar:</span>
            <strong><?= $total_rooms ?> kamar</strong>
        </div>
        <div class="summary-row">
            <span>Total Tamu:</span>
            <strong><?= $total_guests ?> orang (<?= $total_adults ?> dewasa, <?= $total_children ?> anak)</strong>
        </div>
        <div class="summary-row">
            <span>Durasi:</span>
            <strong><?= $nights ?> malam</strong>
        </div>
        <div class="summary-row">
            <span>Total Bayar:</span>
            <strong><?= formatRupiah($total_price) ?></strong>
        </div>
    </div>
    
    <!-- ✅ TAMPILKAN EMAIL YANG DIGUNAKAN -->
    <div class="email-info-box text-start mx-auto mt-3" style="max-width:450px">
        <strong>📧 Email Reservasi:</strong><br>
        <span class="text-primary fs-5"><?= htmlspecialchars($guest_email) ?></span>
        <br><small class="text-muted">
            Simpan email ini untuk keperluan konfirmasi dan tracking reservasi Anda.
        </small>
    </div>
    
    <div class="payment-info text-start mx-auto mt-3" style="max-width:450px">
        <strong>✅ Status Pembayaran: LUNAS</strong><br>
        <small class="text-muted">
            Pembayaran telah tercatat dalam sistem. Reservasi Anda menunggu konfirmasi admin.
        </small>
    </div>
    
    <a href="dashboard.php" class="btn btn-gold mt-4">
        Ke Dashboard
    </a>
</div>

<?php else: ?>

<form method="POST" id="reservationForm">

<!-- STEP 1 -->
<div class="step-body active" id="step1">
    <h5 class="fw-bold mb-4">Pilih Kamar, Tanggal & Jumlah</h5>

    <div class="error-msg" id="error1"></div>

    <div class="form-group">
        <label class="fw-semibold">Tipe Kamar</label>
        <select name="room_id" id="room_id" class="form-select" required>
            <option value="">Pilih Kamar</option>
            <?php 
            mysqli_data_seek($rooms, 0);
            while($r=mysqli_fetch_assoc($rooms)): 
            ?>
            <option 
                value="<?= $r['id']; ?>"
                data-price="<?= $r['price']; ?>"
                data-capacity="<?= $r['capacity']; ?>"
                data-available="<?= $r['available_rooms']; ?>"
            >
                <?= $r['name']; ?> — <?= formatRupiah($r['price']); ?>/malam
            </option>

            <?php endwhile; ?>
        </select>
    </div>

    <div class="row">
        <div class="col-md-6 form-group">
            <label class="fw-semibold">Check-in</label>
            <input type="date" id="check_in" name="check_in"
                   class="form-control" required>
        </div>
        <div class="col-md-6 form-group">
            <label class="fw-semibold">Check-out</label>
            <input type="date" id="check_out" name="check_out"
                   class="form-control" required>
        </div>
    </div>

    <!-- ✅ JUMLAH KAMAR -->
    <div class="form-group">
        <label class="fw-semibold">Jumlah Kamar</label>
        <input type="number" id="total_rooms" name="total_rooms" 
            class="form-control" min="1" value="1" required>
        <small class="text-muted d-none" id="availableInfo"></small>
    </div>


    <!-- ✅ JUMLAH TAMU -->
    <div class="guest-input-row">
        <div class="form-group">
            <label class="fw-semibold">Tamu Dewasa (18+)</label>
            <input type="number" id="total_adults" name="total_adults" 
                   class="form-control" min="1" value="1" required>
        </div>
        <div class="form-group">
            <label class="fw-semibold">Anak-anak (0-17 tahun)</label>
            <input type="number" id="total_children" name="total_children" 
                   class="form-control" min="0" value="0">
        </div>
    </div>

    <!-- ✅ INFO KAPASITAS -->
    <div class="capacity-info d-none" id="capacityInfo">
        <strong><i class="fa-solid fa-users"></i> Info Kapasitas:</strong>
        <div id="capacityText">-</div>
    </div>

    <div class="step-actions justify-content-end">
        <button type="button" class="btn btn-gold" onclick="nextStep(1)">
            Lanjut →
        </button>
    </div>
</div>

<!-- STEP 2 -->
<div class="step-body" id="step2">
    <h5 class="fw-bold mb-4">Data Pemesan</h5>

    <div class="error-msg" id="error2"></div>

    <div class="form-group">
        <label class="fw-semibold">Nama Pemesan</label>
        <input type="text" name="guest_name" id="guest_name" class="form-control"
               value="<?= htmlspecialchars($_SESSION['name'] ?? '') ?>" required>
    </div>

    <div class="form-group">
        <label class="fw-semibold">Email <span class="text-danger">*</span></label>
        <input type="email" name="guest_email" id="guest_email" class="form-control"
               value="<?= htmlspecialchars($_SESSION['email'] ?? '') ?>" required>
        <small class="text-muted">
            <i class="fa-solid fa-info-circle"></i> Email ini akan digunakan untuk konfirmasi dan tracking reservasi Anda
        </small>
    </div>

    <div class="form-group">
        <label class="fw-semibold">Nomor Telepon</label>
        <input type="tel" name="guest_phone" id="guest_phone" class="form-control"
               placeholder="Contoh: 0812 3456 7890" required>
        <small class="text-muted">
            Gunakan nomor aktif untuk konfirmasi reservasi
        </small>
    </div>

    <div class="step-actions">
        <button type="button" class="btn btn-outline btn-secondary" onclick="prevStep(2)">
            ← Kembali
        </button>
        <button type="button" class="btn btn-gold" onclick="nextStep(2)">
            Lanjut →
        </button>
    </div>
</div>

<!-- STEP 3 -->
<div class="step-body" id="step3">
    <h5 class="fw-bold mb-4">Konfirmasi & Pembayaran</h5>

    <div class="error-msg" id="error3"></div>

    <!-- ✅ RINGKASAN PESANAN -->
    <div class="booking-summary">
        <strong><i class="fa-solid fa-file-invoice"></i> Ringkasan Pesanan:</strong>
        <div class="summary-row">
            <span>Kamar:</span>
            <strong id="summary_room">-</strong>
        </div>
        <div class="summary-row">
            <span>Jumlah Kamar:</span>
            <strong id="summary_rooms">-</strong>
        </div>
        <div class="summary-row">
            <span>Total Tamu:</span>
            <strong id="summary_guests">-</strong>
        </div>
        <div class="summary-row">
            <span>Check-in:</span>
            <strong id="summary_checkin">-</strong>
        </div>
        <div class="summary-row">
            <span>Check-out:</span>
            <strong id="summary_checkout">-</strong>
        </div>
        <div class="summary-row">
            <span>Durasi:</span>
            <strong id="summary_nights">-</strong>
        </div>
        <div class="summary-row">
            <span>Total Pembayaran:</span>
            <strong id="summary_price">-</strong>
        </div>
    </div>

    <div class="form-group">
        <label class="fw-semibold">Catatan Tambahan</label>
        <textarea name="notes" class="form-control"
                  placeholder="Permintaan khusus (opsional)" rows="3"></textarea>
    </div>

    <div class="form-group">
        <label class="fw-semibold">Metode Pembayaran</label>
        <select name="payment_method" id="payment_method" class="form-select" required>
            <option value="">Pilih Metode</option>
            <option value="Transfer Bank">Transfer Bank</option>
            <option value="E-Wallet">E-Wallet (OVO/GoPay/Dana)</option>
            <option value="Kartu Kredit">Kartu Kredit</option>
        </select>
        <small class="text-muted">
            💳 Pembayaran akan langsung tercatat setelah reservasi dikonfirmasi
        </small>
    </div>

    <div class="step-actions">
        <button type="button" class="btn btn-outline btn-secondary" onclick="prevStep(3)">
            ← Kembali
        </button>
        <button type="submit" class="btn btn-gold">
            💳 Bayar & Konfirmasi
        </button>
    </div>
</div>

</form>
<?php endif; ?>

</div>
</div>
</div>

<script>
// Set tanggal minimum untuk check-in (hari ini)
const checkInInput  = document.getElementById('check_in');
const checkOutInput = document.getElementById('check_out');

function getLocalDateString(date = new Date()) {
    const year  = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day   = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

// ===== CHECK-IN =====
const todayStr = getLocalDateString();
checkInInput.min = todayStr;
checkInInput.value = todayStr;

// ===== CHECK-OUT =====
function updateCheckoutMin() {
    if (!checkInInput.value) return;

    const checkInDate = new Date(checkInInput.value + 'T00:00:00');
    checkInDate.setDate(checkInDate.getDate() + 1);

    const minCheckout = getLocalDateString(checkInDate);
    checkOutInput.min = minCheckout;

    if (!checkOutInput.value || checkOutInput.value < minCheckout) {
        checkOutInput.value = minCheckout;
    }
}

// EVENT
checkInInput.addEventListener('change', updateCheckoutMin);

// INIT
updateCheckoutMin();

// ✅ GLOBAL VARIABLES
let selectedRoomPrice = 0;
let selectedRoomCapacity = 0;
let selectedRoomAvailable = 0;
let selectedRoomName = '';

// ✅ UPDATE INFO SAAT PILIH KAMAR
document.getElementById('room_id').addEventListener('change', function() {
    const selected = this.options[this.selectedIndex];
    
    if (this.value) {
        selectedRoomPrice = parseFloat(selected.dataset.price || 0);
        selectedRoomCapacity = parseInt(selected.dataset.capacity || 0);
        selectedRoomAvailable = parseInt(selected.dataset.available || 0);
        selectedRoomName = selected.textContent.split('—')[0].trim();
        updateCapacityInfo();
    } else {
        document.getElementById('availableInfo').innerHTML = '';
        document.getElementById('capacityInfo').classList.add('d-none');    
    }
});

// ✅ UPDATE KAPASITAS INFO
function updateCapacityInfo() {
    const totalRooms = parseInt(document.getElementById('total_rooms').value || 1);
    const totalAdults = parseInt(document.getElementById('total_adults').value || 0);
    const totalChildren = parseInt(document.getElementById('total_children').value || 0);
    const totalGuests = totalAdults + totalChildren;

    const capacityInfo = document.getElementById('capacityInfo');
    const capacityText = document.getElementById('capacityText');

    // Kalau kamar belum dipilih
    if (!selectedRoomCapacity || totalRooms < 1) {
        capacityInfo.classList.add('d-none');
        return;
    }

    const maxCapacity = selectedRoomCapacity * totalRooms;
    capacityInfo.classList.remove('d-none');

    /* =========================
       KONDISI MELEBIHI KAPASITAS
    ========================= */
    if (totalGuests > maxCapacity) {
        capacityInfo.style.background = '#fee2e2';
        capacityInfo.style.borderColor = '#ef4444';

        capacityText.innerHTML = `
            <div class="text-danger" style="line-height:1.6">
                <strong>⚠️ Kapasitas terlampaui</strong><br><br>

                Total tamu Anda: <strong>${totalGuests} orang</strong><br>
                Batas maksimal: <strong>${maxCapacity} orang</strong><br><br>

                <small>
                    ${totalRooms} kamar × ${selectedRoomCapacity} orang per kamar<br>
                    Silakan tambah jumlah kamar atau kurangi jumlah tamu.
                </small>
            </div>
        `;
        return;
    }

    /* =========================
       KONDISI NORMAL (VALID)
    ========================= */
    capacityInfo.style.background = '#ecfeff';
    capacityInfo.style.borderColor = '#67e8f9';

    capacityText.innerHTML = `
        <div style="line-height:1.6">
            <strong>Total tamu saat ini:</strong><br>
            ${totalGuests} orang
            (${totalAdults} dewasa${totalChildren > 0 ? `, ${totalChildren} anak` : ''})
            <br><br>

            <strong>Batas maksimal:</strong><br>
            ${totalRooms} kamar × ${selectedRoomCapacity} orang = 
            <strong>${maxCapacity} orang</strong>
        </div>
    `;
}

// ✅ LISTENER UNTUK UPDATE KAPASITAS
document.getElementById('total_rooms').addEventListener('input', updateCapacityInfo);
document.getElementById('total_adults').addEventListener('input', updateCapacityInfo);
document.getElementById('total_children').addEventListener('input', updateCapacityInfo);

// Update minimum check-out saat check-in dipilih
document.getElementById('check_in').addEventListener('change', function() {
    const checkIn = new Date(this.value);
    const nextDay = new Date(checkIn);
    nextDay.setDate(checkIn.getDate() + 1);
    
    const minCheckOut = nextDay.toISOString().split('T')[0];
    document.getElementById('check_out').setAttribute('min', minCheckOut);
    
    const checkOutInput = document.getElementById('check_out');
    if (checkOutInput.value && checkOutInput.value <= this.value) {
        checkOutInput.value = '';
    }
});

// Validasi check-out
document.getElementById('check_out').addEventListener('change', function() {
    const checkIn = document.getElementById('check_in').value;
    if (checkIn && this.value <= checkIn) {
        showError(1, 'Tanggal check-out harus setelah tanggal check-in');
        this.value = '';
    }
});

function showError(step, message) {
    const errorEl = document.getElementById('error' + step);
    errorEl.textContent = message;
    errorEl.classList.add('show');
    
    setTimeout(() => {
        errorEl.classList.remove('show');
    }, 5000);
}

function hideError(step) {
    const errorEl = document.getElementById('error' + step);
    errorEl.classList.remove('show');
}

function nextStep(n) {
    hideError(n);
    
    // ✅ VALIDASI STEP 1
    if (n === 1) {
        const roomId = document.getElementById('room_id').value;
        const checkIn = document.getElementById('check_in').value;
        const checkOut = document.getElementById('check_out').value;
        const totalRooms = parseInt(document.getElementById('total_rooms').value || 0);
        const totalAdults = parseInt(document.getElementById('total_adults').value || 0);
        const totalChildren = parseInt(document.getElementById('total_children').value || 0);
        const totalGuests = totalAdults + totalChildren;
        
        if (!roomId) {
            showError(1, 'Silakan pilih kamar terlebih dahulu');
            return;
        }
        if (!checkIn) {
            showError(1, 'Silakan pilih tanggal check-in');
            return;
        }
        if (!checkOut) {
            showError(1, 'Silakan pilih tanggal check-out');
            return;
        }
        if (checkOut <= checkIn) {
            showError(1, 'Tanggal check-out harus setelah tanggal check-in');
            return;
        }
        if (totalRooms < 1) {
            showError(1, 'Jumlah kamar minimal 1');
            return;
        }
        if (totalRooms > selectedRoomAvailable) {
            showError(1, `Kamar tersedia hanya ${selectedRoomAvailable} unit`);
            return;
        }
        if (totalAdults < 1) {
            showError(1, 'Minimal 1 tamu dewasa');
            return;
        }
        if (totalChildren < 0) {
            showError(1, 'Jumlah anak tidak boleh negatif');
            return;
        }
        
        const maxCapacity = selectedRoomCapacity * totalRooms;
        if (totalGuests > maxCapacity) {
            showError(1, `Kapasitas maksimal ${maxCapacity} orang untuk ${totalRooms} kamar`);
            return;
        }
    }
    
    // ✅ VALIDASI STEP 2
    if (n === 2) {
        const guestName = document.getElementById('guest_name').value.trim();
        const guestEmail = document.getElementById('guest_email').value.trim();
        const guestPhone = document.getElementById('guest_phone').value.trim();
        
        if (!guestName) {
            showError(2, 'Silakan isi nama pemesan');
            return;
        }
        if (!guestEmail) {
            showError(2, 'Silakan isi email');
            return;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(guestEmail)) {
            showError(2, 'Format email tidak valid');
            return;
        }
        if (!guestPhone) {
            showError(2, 'Silakan isi nomor telepon');
            return;
        }
        const phoneDigits = guestPhone.replace(/\D/g, '');
        if (phoneDigits.length < 10) {
            showError(2, 'Nomor telepon minimal 10 digit');
            return;
        }
        
        // ✅ UPDATE SUMMARY
        updateSummary();
    }
    
    // Pindah step
    document.getElementById('step'+n).classList.remove('active');
    document.getElementById('step'+(n+1)).classList.add('active');
    document.getElementById('s'+n).classList.add('done');
    document.getElementById('s'+(n+1)).classList.add('active');
    
    document.querySelector('.reserve-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function prevStep(n) {
    hideError(n);
    
    document.getElementById('step'+n).classList.remove('active');
    document.getElementById('step'+(n-1)).classList.add('active');
    document.getElementById('s'+n).classList.remove('active');
    document.getElementById('s'+(n-1)).classList.remove('done');
    
    document.querySelector('.reserve-card').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ✅ UPDATE SUMMARY DI STEP 3
function updateSummary() {
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const totalRooms = parseInt(document.getElementById('total_rooms').value || 1);
    const totalAdults = parseInt(document.getElementById('total_adults').value || 1);
    const totalChildren = parseInt(document.getElementById('total_children').value || 0);
    const totalGuests = totalAdults + totalChildren;
    
    // Hitung malam
    const date1 = new Date(checkIn);
    const date2 = new Date(checkOut);
    const nights = Math.ceil((date2 - date1) / (1000 * 60 * 60 * 24));
    
    // Hitung total harga
    const totalPrice = selectedRoomPrice * nights * totalRooms;
    
    // Update tampilan
    document.getElementById('summary_room').textContent = selectedRoomName;
    document.getElementById('summary_rooms').textContent = totalRooms + ' kamar';
    document.getElementById('summary_guests').textContent = 
        `${totalGuests} orang (${totalAdults} dewasa, ${totalChildren} anak)`;
    document.getElementById('summary_checkin').textContent = formatDate(checkIn);
    document.getElementById('summary_checkout').textContent = formatDate(checkOut);
    document.getElementById('summary_nights').textContent = nights + ' malam';
    document.getElementById('summary_price').textContent = formatRupiah(totalPrice);
}

// Helper format date
function formatDate(dateString) {
    const options = { day: 'numeric', month: 'long', year: 'numeric' };
    return new Date(dateString).toLocaleDateString('id-ID', options);
}

// Helper format rupiah
function formatRupiah(amount) {
    return 'Rp ' + amount.toLocaleString('id-ID');
}

// Validasi form sebelum submit
document.getElementById('reservationForm').addEventListener('submit', function(e) {
    hideError(3);
    
    const paymentMethod = document.getElementById('payment_method').value;
    
    if (!paymentMethod) {
        e.preventDefault();
        showError(3, 'Silakan pilih metode pembayaran');
        return false;
    }
});
</script>

<?php include '../includes/footer.php'; ?>