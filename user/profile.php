<?php
require_once '../config/database.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$page_title = 'Profil Saya';

$tab = $_GET['tab'] ?? 'info';
$success = $error = '';

/* ================= USER DATA ================= */
$stmt = mysqli_prepare($conn,"SELECT * FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt,"i",$user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

/* ================= UPLOAD PHOTO ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {

    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload foto gagal.';
    } else {

        $file = $_FILES['photo'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            $error = 'Format foto tidak valid.';
        } elseif ($file['size'] > $maxSize) {
            $error = 'Ukuran foto maksimal 5MB.';
        } else {

            $folder = '../uploads/profiles/user/';
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
            $path = $folder . $filename;

            if (move_uploaded_file($file['tmp_name'], $path)) {

                $dbPath = 'uploads/profiles/user/' . $filename;

                $stmt = mysqli_prepare($conn, "UPDATE users SET photo=? WHERE id=?");
                mysqli_stmt_bind_param($stmt, "si", $dbPath, $user_id);
                mysqli_stmt_execute($stmt);

                header("Location: profile.php?tab=edit&uploaded=1");
                exit;

            } else {
                $error = 'Gagal menyimpan foto.';
            }
        }
    }
}

/* ================= DELETE PHOTO ================= */
if (isset($_GET['delete_photo']) && $_GET['delete_photo'] === '1') {

    if ($user['photo'] && file_exists('../' . $user['photo'])) {
        unlink('../' . $user['photo']);
    }

    $stmt = mysqli_prepare($conn, "UPDATE users SET photo = NULL WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $user['photo'] = null;

    header("Location: profile.php?tab=edit&deleted=1");
    exit;
}

/* ================= UPDATE PROFILE ================= */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])){
    $name = clean($_POST['name']);
    $phone = clean($_POST['phone'] ?? '');
    $gender = clean($_POST['gender'] ?? '');
    $birth_date = clean($_POST['birth_date'] ?? '');
    $address = clean($_POST['address'] ?? '');
    
    if(empty($name)){
        $error = 'Nama lengkap wajib diisi.';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, phone=?, gender=?, birth_date=?, address=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, "sssssi", $name, $phone, $gender, $birth_date, $address, $user_id);
        
        if(mysqli_stmt_execute($stmt)){
            $success = 'Profil berhasil diperbarui.';
            // Refresh user data
            $stmt = mysqli_prepare($conn,"SELECT * FROM users WHERE id=?");
            mysqli_stmt_bind_param($stmt,"i",$user_id);
            mysqli_stmt_execute($stmt);
            $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        } else {
            $error = 'Gagal memperbarui profil.';
        }
    }
}

/* ================= UPDATE PASSWORD ================= */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_password'])){
    if(!password_verify($_POST['old_password'],$user['password'])){
        $error='Password lama salah.';
    } elseif($_POST['new_password']!==$_POST['confirm_password']){
        $error='Konfirmasi password tidak cocok.';
    } elseif(strlen($_POST['new_password'])<6){
        $error='Password minimal 6 karakter.';
    } else {
        $hash=password_hash($_POST['new_password'],PASSWORD_DEFAULT);
        $stmt=mysqli_prepare($conn,"UPDATE users SET password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt,"si",$hash,$user_id);
        mysqli_stmt_execute($stmt);
        $success='Password berhasil diubah.';
    }
}

/* ================= RIWAYAT (WITH EMAIL & CANCELLATION DATA) ================= */
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$where = "WHERE r.user_id=?";
$params = [$user_id];
$types = "i";

if($search){
    $where .= " AND (r.booking_code LIKE ? OR ro.name LIKE ? OR r.guest_email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "sss";
}
if($status){
    $where .= " AND r.status=?";
    $params[] = $status;
    $types .= "s";
}

$limit = 4;
$page = max(1,(int)($_GET['page'] ?? 1));
$offset = ($page-1)*$limit;

$stmt = mysqli_prepare($conn,
    "SELECT COUNT(*) FROM reservations r JOIN rooms ro ON r.room_id=ro.id $where"
);
mysqli_stmt_bind_param($stmt,$types,...$params);
mysqli_stmt_execute($stmt);
$total = mysqli_fetch_row(mysqli_stmt_get_result($stmt))[0];
$totalPages = ceil($total/$limit);

$query = "
SELECT 
    r.*,
    ro.name AS room_name,
    c.reason AS cancel_reason,
    c.admin_notes AS cancel_admin_notes,
    c.status AS cancel_status,
    c.refund_amount
FROM reservations r
JOIN rooms ro ON r.room_id=ro.id
LEFT JOIN cancellations c ON r.id=c.reservation_id
$where
ORDER BY r.created_at DESC
LIMIT $limit OFFSET $offset";
$stmt = mysqli_prepare($conn,$query);
mysqli_stmt_bind_param($stmt,$types,...$params);
mysqli_stmt_execute($stmt);
$history = mysqli_stmt_get_result($stmt);


function getInitials($name) {
    $words = explode(' ', $name);
    if(count($words) >= 2) {
        return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    }
    return strtoupper(substr($name, 0, 2));
}

function getAvatarColor($name) {
    $colors = [
        '#ef4444', '#f59e0b', '#10b981', '#3b82f6', 
        '#8b5cf6', '#ec4899', '#06b6d4', '#84cc16'
    ];
    $index = ord(strtolower($name[0])) % count($colors);
    return $colors[$index];
}

include '../includes/header.php';
?>

<style>
:root{
    --bg:#f8fafc;
    --card:#ffffff;
    --primary:#0f172a;
    --accent:#facc15;
    --accent-dark:#eab308;
    --text:#475569;
    --muted:#94a3b8;
    --radius:22px;
}
body{background:var(--bg);color:var(--text)}

/* ANIMATIONS */
@keyframes fadeUp{
    from{opacity:0;transform:translateY(20px)}
    to{opacity:1;transform:translateY(0)}
}
@keyframes fadeIn{
    from{opacity:0}
    to{opacity:1}
}
@keyframes slideDown{
    from{opacity:0;transform:translateY(-20px)}
    to{opacity:1;transform:translateY(0)}
}

.card-ui{
    background:var(--card);
    border-radius:var(--radius);
    box-shadow:0 20px 45px rgba(0,0,0,.08);
    animation:fadeUp .4s ease;
}

/* AVATAR */
.profile-avatar{
    width:120px;
    height:120px;
    border-radius:50%;
    margin:0 auto 1rem;
    position:relative;
    overflow:hidden;
    box-shadow:0 8px 20px rgba(0,0,0,.15);
}
.avatar-img{
    width:100%;
    height:100%;
    object-fit:cover;
}
.avatar-initials{
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:2.5rem;
    font-weight:700;
    color:#fff;
}

/* PHOTO ACTIONS */
.photo-actions{
    display:flex;
    gap:.5rem;
    justify-content:center;
    margin-top:1rem;
    flex-wrap:wrap;
}
.photo-actions .btn{
    border-radius:12px;
    font-size:.85rem;
    padding:.5rem 1rem;
}

/* NAV PILLS */
.nav-pills .nav-link{
    border-radius:14px;
    font-weight:600;
    color:var(--text);
    padding:.75rem 1.25rem;
    margin-bottom:.5rem;
    transition:.3s;
    display:flex;
    align-items:center;
    gap:.75rem;
}
.nav-pills .nav-link:hover{
    background:#f1f5f9;
}
.nav-pills .nav-link.active{
    background:linear-gradient(135deg,var(--accent),var(--accent-dark));
    color:#422006;
}
.nav-pills .nav-link i{
    width:20px;
    text-align:center;
}

/* FORMS */
.form-control,.form-select,textarea{
    border-radius:14px;
    padding:.75rem 1rem;
    border:2px solid #e2e8f0;
    transition:.2s;
}
.form-control:focus,.form-select:focus,textarea:focus{
    border-color:var(--accent);
    box-shadow:0 0 0 3px rgba(250,204,21,.1);
}
.form-label{
    font-weight:600;
    color:var(--primary);
    margin-bottom:.5rem;
}

/* BUTTONS */
.btn{
    border-radius:12px;
    font-weight:700;
    padding:.75rem 1.5rem;
    transition:.3s;
}
.btn-warning{
    background:linear-gradient(135deg,var(--accent),var(--accent-dark));
    border:none;
    color:#422006;
}
.btn-warning:hover{
    transform:translateY(-2px);
    box-shadow:0 6px 20px rgba(250,204,21,.3);
}
.btn-dark{
    background:var(--primary);
    border:none;
    color:#fff;
}
.btn-dark:hover{
    background:#1e293b;
    transform:translateY(-2px);
}

/* STATUS BADGES */
.status{
    font-size:.75rem;
    padding:.4rem .9rem;
    border-radius:999px;
    font-weight:600;
    display:inline-block;
}
.pending{background:#fef3c7;color:#92400e}
.confirmed{background:#dcfce7;color:#166534}
.cancelled{background:#fee2e2;color:#991b1b}
.completed{background:#dbeafe;color:#1e40af}
.checked_in{background:#e0e7ff;color:#4338ca}
.checked_out{background:#d4cdcdff;color:#504f4fff;}

/* HISTORY CARD */
.history-card{
    background:#fff;
    border-radius:18px;
    padding:1.5rem;
    box-shadow:0 8px 24px rgba(0,0,0,.08);
    transition:.3s;
    height:100%;
}
.history-card:hover{
    transform:translateY(-4px);
    box-shadow:0 12px 32px rgba(0,0,0,.12);
}

/* ✅ EMAIL BADGE IN HISTORY CARD */
.email-badge {
    display: inline-block;
    background: #dbeafe;
    color: #1e40af;
    padding: 0.25rem 0.6rem;
    border-radius: 6px;
    font-size: 0.7rem;
    font-weight: 600;
    margin-top: 0.25rem;
}

/* FILTER BAR */
.filter-bar{
    background:#f8fafc;
    padding:1.25rem;
    border-radius:16px;
    border:2px solid #e2e8f0;
}

/* PAGINATION */
.page-link{
    border-radius:10px;
    border:none;
    font-weight:600;
    margin:0 .25rem;
    color:var(--text);
}
.page-item.active .page-link{
    background:linear-gradient(135deg,var(--accent),var(--accent-dark));
    color:#422006;
}

/* MODAL */
.modal-bg{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.7);
    backdrop-filter:blur(4px);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding:1rem;
    animation:fadeIn .3s ease;
}
.modal-bg.show{display:flex}
.modal-box{
    background:#fff;
    border-radius:24px;
    padding:2rem;
    max-width:500px;
    width:100%;
    max-height:90vh;
    overflow-y:auto;
    animation:slideDown .3s ease;
    box-shadow:0 25px 60px rgba(0,0,0,.3);
}
.modal-section{
    padding:1rem;
    background:#f8fafc;
    border-radius:12px;
    margin-bottom:1rem;
}
.modal-section:last-of-type{
    margin-bottom:0;
}
.modal-label{
    font-size:.8rem;
    color:var(--muted);
    text-transform:uppercase;
    font-weight:600;
    margin-bottom:.25rem;
}
.modal-value{
    color:var(--primary);
    font-weight:600;
}

/* INFO BOX */
.info-box{
    background: #f8fafc;
    border-left:4px solid #fee450f8;
    padding:1rem;
    border-radius:8px;
    margin-top:1rem;
}
.info-box.warning{
    background:#fef3c7;
    border-left-color:#f59e0b;
}
.info-box.danger{
    background:#fee2e2;
    border-left-color:#ef4444;
}
.info-box.success{
    background:#dcfce7;
    border-left-color:#10b981;
}

/* RESPONSIVE */
@media(max-width:768px){
    .profile-avatar{
        width:100px;
        height:100px;
    }
    .avatar-initials{
        font-size:2rem;
    }
    .nav-pills{
        display:flex;
        overflow-x:auto;
        flex-wrap:nowrap;
        gap:.5rem;
        padding-bottom:.5rem;
    }
    .nav-pills .nav-link{
        white-space:nowrap;
        padding:.6rem 1rem;
        font-size:.9rem;
    }
    .filter-bar{
        padding:1rem;
    }
    .filter-bar .col-md-3,
    .filter-bar .col-md-4,
    .filter-bar .col-md-5{
        margin-bottom:.5rem;
    }
    .modal-box{
        padding:1.5rem;
        margin:1rem;
    }
    .history-card{
        padding:1.25rem;
    }
}

@media(max-width:576px){
    .card-ui{
        padding:1.5rem !important;
    }
    .profile-avatar{
        width:90px;
        height:90px;
    }
    .btn{
        padding:.65rem 1.25rem;
        font-size:.9rem;
    }
}

.pagination .page-link{
    min-width:42px;
    text-align:center;
    transition:.3s;
}

.pagination .page-link:hover{
    transform:translateY(-2px);
}

.pagination .page-item.disabled .page-link{
    opacity:.4;
    pointer-events:none;
}
</style>

<div class="container my-5">
<div class="row g-4">

<!-- SIDEBAR -->
<div class="col-lg-3 col-md-4">
<div class="card-ui p-4 text-center">

<!-- AVATAR -->
<div class="profile-avatar">
    <?php if($user['photo'] && file_exists('../' . $user['photo'])): ?>
        <img src="../<?= $user['photo'] ?>?v=<?= time() ?>" class="avatar-img" alt="Profile">
    <?php else: ?>
        <div class="avatar-initials" style="background:<?= getAvatarColor($user['name']) ?>">
            <?= getInitials($user['name']) ?>
        </div>
    <?php endif; ?>
</div>

<h6 class="fw-bold mb-1"><?= htmlspecialchars($user['name']) ?></h6>
<small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>

<div class="nav flex-column nav-pills mt-4">
<a class="nav-link <?= $tab=='info'?'active':'' ?>" href="?tab=info">
    <i class="fa-solid fa-user"></i> <span>Profil</span>
</a>
<a class="nav-link <?= $tab=='edit'?'active':'' ?>" href="?tab=edit">
    <i class="fa-solid fa-edit"></i> <span>Edit Profil</span>
</a>
<a class="nav-link <?= $tab=='password'?'active':'' ?>" href="?tab=password">
    <i class="fa-solid fa-lock"></i> <span>Ubah Password</span>
</a>
<a class="nav-link <?= $tab=='history'?'active':'' ?>" href="?tab=history">
    <i class="fa-solid fa-history"></i> <span>Riwayat</span>
</a>
</div>
</div>
</div>

<!-- CONTENT -->
<div class="col-lg-9 col-md-8">

<?php if($success):?><div class="alert alert-success alert-dismissible fade show">
<?= $success ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div><?php endif;?>

<?php if($error):?><div class="alert alert-danger alert-dismissible fade show">
<?= $error ?>
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div><?php endif;?>

<?php if(isset($_GET['deleted'])):?><div class="alert alert-success alert-dismissible fade show">
Foto profil berhasil dihapus
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div><?php endif;?>

<?php if(isset($_GET['uploaded'])):?><div class="alert alert-success alert-dismissible fade show">
Foto profil berhasil diupload
<button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div><?php endif;?>

<?php if($tab=='info'): ?>
<div class="card-ui p-4">
<h5 class="fw-bold mb-4">
    <i class="fa-solid fa-user-circle text-warning"></i> Informasi Profil
</h5>

<div class="row g-3">
    <div class="col-md-6">
        <div class="info-box">
            <div class="modal-label">Nama Lengkap</div>
            <div class="modal-value"><?= htmlspecialchars($user['name']) ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="info-box">
            <div class="modal-label">Email</div>
            <div class="modal-value"><?= htmlspecialchars($user['email']) ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="info-box">
            <div class="modal-label">Nomor Telepon</div>
            <div class="modal-value"><?= $user['phone'] ?? '-' ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="info-box">
            <div class="modal-label">Jenis Kelamin</div>
            <div class="modal-value">
            <?php
            if($user['gender']==='male') echo 'Laki-laki';
            elseif($user['gender']==='female') echo 'Perempuan';
            else echo '-';
            ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="info-box">
            <div class="modal-label">Tanggal Lahir</div>
            <div class="modal-value"><?= $user['birth_date'] ? date('d F Y', strtotime($user['birth_date'])) : '-' ?></div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="info-box">
            <div class="modal-label">Terdaftar Sejak</div>
            <div class="modal-value"><?= date('d F Y', strtotime($user['created_at'])) ?></div>
        </div>
    </div>
    <div class="col-12">
        <div class="info-box">
            <div class="modal-label">Alamat</div>
            <div class="modal-value"><?= $user['address'] ?? '-' ?></div>
        </div>
    </div>
</div>
</div>

<?php elseif($tab=='edit'): ?>
<div class="card-ui p-4">
<h5 class="fw-bold mb-4">
    <i class="fa-solid fa-edit text-warning"></i> Edit Profil
</h5>

<!-- FOTO PROFIL SECTION -->
<div class="mb-4 pb-4 border-bottom">
    <label class="form-label">
        <i class="fa-solid fa-camera"></i> Foto Profil
    </label>
    
    <div class="d-flex align-items-center gap-3 flex-wrap">
        <div class="profile-avatar" style="width:80px;height:80px;margin:0">
            <?php if($user['photo'] && file_exists('../' . $user['photo'])): ?>
                <img src="../<?= $user['photo'] ?>?v=<?= time() ?>" class="avatar-img" alt="Profile">
            <?php else: ?>
                <div class="avatar-initials" style="background:<?= getAvatarColor($user['name']) ?>;font-size:1.5rem">
                    <?= getInitials($user['name']) ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="photo-actions flex-grow-1">
            <form method="POST" enctype="multipart/form-data" id="photoForm" class="d-inline">
                <input type="hidden" name="upload_photo">
                <label class="btn btn-warning btn-sm mb-0" style="cursor:pointer">
                    <i class="fa-solid fa-upload"></i> Upload Foto
                    <input type="file" name="photo" accept="image/*" style="display:none" 
                           onchange="validateAndSubmit(this)">
                </label>
            </form>
            
            <?php if($user['photo']): ?>
            <a href="?delete_photo=1&tab=edit" class="btn btn-outline-danger btn-sm" 
               onclick="return confirm('Hapus foto profil?')">
                <i class="fa-solid fa-trash"></i> Hapus Foto
            </a>
            <?php endif; ?>
        </div>
    </div>
    <small class="text-muted d-block mt-2">
        <i class="fa-solid fa-info-circle"></i> Format: JPG, PNG, GIF. Maksimal 5MB
    </small>
</div>

<!-- FORM EDIT DATA -->
<form method="POST">
<input type="hidden" name="update_profile">

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
        <input class="form-control" name="name" value="<?= htmlspecialchars($user['name']) ?>" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">Nomor Telepon</label>
        <input class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" 
               placeholder="08123456789">
    </div>

    <div class="col-md-6">
        <label class="form-label">Jenis Kelamin</label>
        <select name="gender" class="form-select">
            <option value="">Pilih Jenis Kelamin</option>
            <option value="male" <?= $user['gender']=='male'?'selected':'' ?>>Laki-laki</option>
            <option value="female" <?= $user['gender']=='female'?'selected':'' ?>>Perempuan</option>
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Tanggal Lahir</label>
        <input type="date" name="birth_date" class="form-control"
        max="<?= date('Y-m-d',strtotime('-17 years')) ?>"
        value="<?= $user['birth_date'] ?>">
        <small class="text-muted">Minimal berusia 17 tahun</small>
    </div>

    <div class="col-12">
        <label class="form-label">Alamat</label>
        <textarea name="address" class="form-control" rows="3" 
                  placeholder="Alamat lengkap"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
    </div>

    <div class="col-12">
        <button class="btn btn-warning w-100">
            <i class="fa-solid fa-save"></i> Simpan Perubahan
        </button>
    </div>
</div>
</form>
</div>

<?php elseif($tab=='password'): ?>

<div class="card-ui p-4">
<h5 class="fw-bold mb-4">
    <i class="fa-solid fa-lock text-warning"></i> Ubah Password
</h5>

<form method="POST">
<input type="hidden" name="update_password">

<div class="mb-3">
    <label class="form-label">Password Lama <span class="text-danger">*</span></label>
    <input type="password" class="form-control" name="old_password" required>
</div>

<div class="mb-3">
    <label class="form-label">Password Baru <span class="text-danger">*</span></label>
    <input type="password" class="form-control" name="new_password" required>
    <small class="text-muted">Minimal 6 karakter</small>
</div>

<div class="mb-3">
    <label class="form-label">Konfirmasi Password Baru <span class="text-danger">*</span></label>
    <input type="password" class="form-control" name="confirm_password" required>
</div>

<button class="btn btn-warning w-100">
    <i class="fa-solid fa-key"></i> Ubah Password
</button>
</form>
</div>

<?php elseif($tab=='history'): ?>
<div class="card-ui p-4">
<h5 class="fw-bold mb-4">
    <i class="fa-solid fa-history text-warning"></i> Riwayat Reservasi
</h5>

<form class="filter-bar row g-2 mb-4">
<input type="hidden" name="tab" value="history">
<div class="col-md-5">
<input class="form-control" name="search" placeholder="Cari kode booking / kamar / email" value="<?= htmlspecialchars($search) ?>">
</div>
<div class="col-md-4">
<select name="status" class="form-select">
<option value="">Semua Status</option>
<option value="pending" <?= $status=='pending'?'selected':'' ?>>Pending</option>
<option value="confirmed" <?= $status=='confirmed'?'selected':'' ?>>Confirmed</option>
<option value="checked_in" <?= $status=='checked_in'?'selected':'' ?>>Checked In</option>
<option value="checked_out" <?= $status=='checked_out'?'selected':'' ?>>Checked Out</option>
<option value="completed" <?= $status=='completed'?'selected':'' ?>>Completed</option>
<option value="cancelled" <?= $status=='cancelled'?'selected':'' ?>>Cancelled</option>
</select>
</div>
<div class="col-md-3">
<button class="btn btn-dark w-100">
    <i class="fa-solid fa-filter"></i> Filter
</button>
</div>
</form>
<div class="row g-3">
<?php if(mysqli_num_rows($history) > 0): ?>
    <?php while($r=mysqli_fetch_assoc($history)): ?>
    <div class="col-lg-6 col-md-12">
    <div class="history-card">
    <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
            <strong style="font-size:1.1rem"><?= $r['booking_code'] ?></strong>
            <p class="mb-1 text-muted"><?= htmlspecialchars($r['room_name']) ?></p>
            <!-- ✅ TAMPILKAN EMAIL PEMESAN -->
            <?php if($r['guest_email']): ?>
            <span class="email-badge">
                <i class="fa-solid fa-envelope"></i> <?= htmlspecialchars($r['guest_email']) ?>
            </span>
            <?php endif; ?>
        </div>
        <span class="status <?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span>
    </div>
    <div class="mb-2">
        <small class="text-muted d-block">
            <i class="fa-solid fa-calendar"></i> 
            <?= date('d M', strtotime($r['check_in_date'])) ?> - <?= date('d M Y', strtotime($r['check_out_date'])) ?>
            <span class="ms-2">(<?= $r['total_nights'] ?> malam)</span>
        </small>
        <small class="text-muted d-block mt-1">
            <i class="fa-solid fa-door-open"></i>
            <?= $r['total_rooms'] ?> kamar
        </small>
        <small class="text-muted d-block mt-1">
            <i class="fa-solid fa-money-bill"></i> 
            <strong><?= formatRupiah($r['total_price']) ?></strong>
        </small>
    </div>
    
    <?php if($r['status'] == 'cancelled' && $r['cancel_reason']): ?>
    <div class="alert alert-warning alert-sm mb-2 p-2">
        <small><strong><i class="fa-solid fa-info-circle"></i> Alasan Batal:</strong><br>
        <?= htmlspecialchars(substr($r['cancel_reason'], 0, 80)) ?><?= strlen($r['cancel_reason']) > 80 ? '...' : '' ?>
        </small>
    </div>
    <?php endif; ?>
    
    <button class="btn btn-sm btn-outline-dark mt-2 w-100"
    onclick='openDetail(<?= json_encode([
        "code" => $r['booking_code'],
        "room" => $r['room_name'],
        "guest_email" => $r['guest_email'] ?? '',
        "check_in" => date('d M Y', strtotime($r['check_in_date'])),
        "check_out" => date('d M Y', strtotime($r['check_out_date'])),
        "status" => $r['status'],
        "price" => formatRupiah($r['total_price']),
        "nights" => $r['total_nights'],
        "payment_status" => $r['payment_status'],
        "cancel_reason" => $r['cancel_reason'] ?? null,
        "cancel_admin_notes" => $r['cancel_admin_notes'] ?? null,
        "cancel_status" => $r['cancel_status'] ?? null,
        "refund_amount" => $r['refund_amount'] ?? null
    ]) ?>)'>
        <i class="fa-solid fa-info-circle"></i> Lihat Detail
    </button>
    </div>
    </div>
    <?php endwhile; ?>
<?php else: ?>
    <div class="col-12 text-center text-muted py-5">
        <i class="fa-solid fa-inbox fa-3x mb-3 opacity-25"></i>
        <p class="mb-0">
            <?php if($search || $status): ?>
                Tidak ada hasil yang sesuai dengan filter
            <?php else: ?>
                Belum ada riwayat reservasi
            <?php endif; ?>
        </p>
    </div>
<?php endif; ?>
</div>
<!-- PAGINATION -->
<?php if($totalPages > 1): ?>
<nav class="mt-4">
<ul class="pagination justify-content-center align-items-center">

<!-- PREV -->
<li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
<a class="page-link" href="?tab=history&page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
    ‹
</a>
</li>

<?php
$start = max(1, $page - 1);
$end   = min($totalPages, $page + 1);

for($i=$start;$i<=$end;$i++):
?>
<li class="page-item <?= $i==$page?'active':'' ?>">
<a class="page-link" href="?tab=history&page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
<?= $i ?>
</a>
</li>
<?php endfor; ?>

<!-- NEXT -->
<li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
<a class="page-link" href="?tab=history&page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status) ?>">
    ›
</a>
</li>

</ul>
</nav>
<?php endif; ?>

</div>
<?php endif; ?>

</div>
</div>
</div>

<!-- MODAL DETAIL -->
<div class="modal-bg" id="modal" onclick="if(event.target===this) closeDetail()">
<div class="modal-box">
<div class="d-flex justify-content-between align-items-start mb-3">
    <h5 class="fw-bold mb-0">
        <i class="fa-solid fa-receipt text-warning"></i>
        Detail Reservasi
    </h5>
    <button class="btn btn-sm btn-light" onclick="closeDetail()">
        <i class="fa-solid fa-times"></i>
    </button>
</div>

<div class="modal-section">
    <div class="modal-label">Kode Booking</div>
    <div class="modal-value" id="mCode"></div>
</div>

<div class="modal-section">
    <div class="modal-label">Kamar</div>
    <div class="modal-value" id="mRoom"></div>
</div>

<!-- ✅ TAMPILKAN EMAIL PEMESAN DI MODAL -->
<div class="modal-section" id="guestEmailSection">
    <div class="modal-label">Email Pemesan</div>
    <div class="modal-value" style="color:#3b82f6" id="mGuestEmail"></div>
</div>

<div class="modal-section">
    <div class="modal-label">Periode Menginap</div>
    <div class="modal-value" id="mDate"></div>
    <small class="text-muted" id="mNights"></small>
</div>

<div class="modal-section">
    <div class="modal-label">Total Pembayaran</div>
    <div class="modal-value fs-5" id="mPrice"></div>
    <small class="text-muted" id="mPaymentStatus"></small>
</div>

<div class="modal-section">
    <div class="modal-label">Status Reservasi</div>
    <span id="mStatus" class="status"></span>
</div>

<!-- CANCELLATION INFO -->
<div id="cancelInfo" style="display:none">
    <hr class="my-3">
    <h6 class="fw-bold mb-3">
        <i class="fa-solid fa-ban text-danger"></i> Informasi Pembatalan
    </h6>
    
    <div class="info-box warning" id="cancelReasonBox" style="display:none">
        <div class="modal-label">Alasan Pembatalan (User)</div>
        <div id="mCancelReason" class="modal-value"></div>
    </div>
    
    <div class="info-box" id="cancelAdminBox" style="display:none">
        <div class="modal-label">Catatan Admin</div>
        <div id="mAdminNotes" class="modal-value"></div>
    </div>
    
    <div class="info-box success" id="refundBox" style="display:none">
        <div class="modal-label">Jumlah Refund</div>
        <div id="mRefund" class="modal-value fs-5 text-success"></div>
    </div>
    
    <div class="modal-section">
        <div class="modal-label">Status Pembatalan</div>
        <span id="mCancelStatus" class="status"></span>
    </div>
</div>

<button class="btn btn-dark w-100 mt-3" onclick="closeDetail()">
    <i class="fa-solid fa-times"></i> Tutup
</button>
</div>
</div>

<script>
// ✅ VALIDASI UKURAN FOTO SEBELUM SUBMIT
function validateAndSubmit(input) {
    const file = input.files[0];
    if (!file) return;
    
    const maxSize = 5 * 1024 * 1024; // 5MB in bytes
    const fileSize = file.size;
    const fileName = file.name;
    const fileExt = fileName.split('.').pop().toLowerCase();
    
    // Validasi format
    const allowedFormats = ['jpg', 'jpeg', 'png', 'gif'];
    if (!allowedFormats.includes(fileExt)) {
        alert('❌ Format file tidak valid!\n\nFormat yang diperbolehkan: JPG, JPEG, PNG, GIF');
        input.value = '';
        return false;
    }
    
    // Validasi ukuran
    if (fileSize > maxSize) {
        const sizeMB = (fileSize / 1024 / 1024).toFixed(2);
        alert(`❌ Ukuran file terlalu besar!\n\nUkuran file Anda: ${sizeMB} MB\nMaksimal: 5 MB\n\nSilakan pilih foto yang lebih kecil.`);
        input.value = '';
        return false;
    }
    
    // Jika lolos validasi, submit form
    document.getElementById('photoForm').submit();
}

function openDetail(data){
    document.getElementById('mCode').innerText = data.code;
    document.getElementById('mRoom').innerText = data.room;
    
    // ✅ TAMPILKAN EMAIL PEMESAN
    if(data.guest_email) {
        document.getElementById('guestEmailSection').style.display = 'block';
        document.getElementById('mGuestEmail').innerText = data.guest_email;
    } else {
        document.getElementById('guestEmailSection').style.display = 'none';
    }
    
    document.getElementById('mDate').innerText = data.check_in + ' → ' + data.check_out;
    document.getElementById('mNights').innerText = data.nights + ' malam';
    document.getElementById('mPrice').innerText = data.price;
    
    // Payment Status
    let paymentText = '';
    switch(data.payment_status) {
        case 'pending': paymentText = 'Belum Dibayar'; break;
        case 'paid': paymentText = 'Sudah Dibayar'; break;
        case 'refunded': paymentText = 'Sudah Direfund'; break;
        default: paymentText = data.payment_status;
    }
    document.getElementById('mPaymentStatus').innerText = paymentText;
    
    // Status
    const statusEl = document.getElementById('mStatus');
    statusEl.innerText = data.status.charAt(0).toUpperCase() + data.status.slice(1).replace('_', ' ');
    statusEl.className = 'status ' + data.status;
    
    // Cancellation Info
    const cancelInfo = document.getElementById('cancelInfo');
    if(data.status === 'cancelled' && (data.cancel_reason || data.cancel_admin_notes)) {
        cancelInfo.style.display = 'block';
        
        // User's Reason
        if(data.cancel_reason) {
            document.getElementById('cancelReasonBox').style.display = 'block';
            document.getElementById('mCancelReason').innerText = data.cancel_reason;
        } else {
            document.getElementById('cancelReasonBox').style.display = 'none';
        }
        
        // Admin Notes
        if(data.cancel_admin_notes) {
            document.getElementById('cancelAdminBox').style.display = 'block';
            document.getElementById('mAdminNotes').innerText = data.cancel_admin_notes;
        } else {
            document.getElementById('cancelAdminBox').style.display = 'none';
        }
        
        // Refund Amount
        if(data.refund_amount && data.refund_amount > 0) {
            document.getElementById('refundBox').style.display = 'block';
            document.getElementById('mRefund').innerText = 'Rp ' + Number(data.refund_amount).toLocaleString('id-ID');
        } else {
            document.getElementById('refundBox').style.display = 'none';
        }
        
        // Cancel Status
        if(data.cancel_status) {
            const cancelStatusEl = document.getElementById('mCancelStatus');
            let statusText = '';
            let statusClass = '';
            
            switch(data.cancel_status) {
                case 'pending':
                    statusText = 'Menunggu Persetujuan';
                    statusClass = 'pending';
                    break;
                case 'approved':
                    statusText = 'Disetujui';
                    statusClass = 'confirmed';
                    break;
                case 'rejected':
                    statusText = 'Ditolak';
                    statusClass = 'cancelled';
                    break;
            }
            
            cancelStatusEl.innerText = statusText;
            cancelStatusEl.className = 'status ' + statusClass;
        }
    } else {
        cancelInfo.style.display = 'none';
    }
    
    document.getElementById('modal').classList.add('show');
}

function closeDetail(){
    document.getElementById('modal').classList.remove('show');
}

// Close modal with ESC key
document.addEventListener('keydown', function(e){
    if(e.key === 'Escape' && document.getElementById('modal').classList.contains('show')){
        closeDetail();
    }
});
</script>

<?php include '../includes/footer.php'; ?>