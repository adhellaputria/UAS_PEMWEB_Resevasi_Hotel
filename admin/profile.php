<?php
require_once '../config/database.php';
requireLogin();
requireAdmin();

$user_id = $_SESSION['user_id'];
$page_title = 'Profil Admin';

$tab = $_GET['tab'] ?? 'info';
$success = $error = '';

/* ================= USER DATA ================= */
$stmt = mysqli_prepare($conn,"SELECT * FROM users WHERE id=?");
mysqli_stmt_bind_param($stmt,"i",$user_id);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

/* ================= UPDATE PROFILE ================= */
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_profile'])){
    $name = clean($_POST['name']);
    $phone = clean($_POST['phone']);
    $gender = $_POST['gender'] ?? null;
    $birth_date = $_POST['birth_date'] ?? null;
    $address = clean($_POST['address']);

    if($birth_date && $birth_date > date('Y-m-d',strtotime('-17 years'))){
        $error = 'Usia minimal 17 tahun.';
    }

    if($error===''){
        $stmt = mysqli_prepare($conn,
            "UPDATE users SET name=?, phone=?, gender=?, birth_date=?, address=? WHERE id=?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            "sssssi",
            $name,
            $phone,
            $gender,
            $birth_date,
            $address,
            $user_id
        );
        mysqli_stmt_execute($stmt);

        $_SESSION['name'] = $name;
        $success = 'Profil berhasil diperbarui.';

        // Refresh user data
        $stmt = mysqli_prepare($conn,"SELECT * FROM users WHERE id=?");
        mysqli_stmt_bind_param($stmt,"i",$user_id);
        mysqli_stmt_execute($stmt);
        $user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    }
}

/* ================= UPLOAD PHOTO ================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo'])) {

    if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload foto gagal.';
    } else {

        // 🔹 Ambil foto lama LANGSUNG dari DB (biar pasti)
        $stmt = mysqli_prepare($conn, "SELECT photo FROM users WHERE id=?");
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);
        $old = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        $oldPhoto = $old['photo'] ?? null;

        $file = $_FILES['photo'];
        $allowedExt = ['jpg','jpeg','png','gif'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExt)) {
            $error = 'Format foto tidak valid.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'Ukuran foto maksimal 5MB.';
        } else {

            $folder = '../uploads/profiles/admin/';
            if (!is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            // 🔥 HAPUS FOTO LAMA (KALAU ADA)
            if ($oldPhoto && file_exists('../' . $oldPhoto)) {
                unlink('../' . $oldPhoto);
            }

            // 🔹 Upload foto baru
            $filename = 'profile_' . $user_id . '_' . time() . '.' . $ext;
            $path = $folder . $filename;

            if (move_uploaded_file($file['tmp_name'], $path)) {

                $dbPath = 'uploads/profiles/admin/' . $filename;

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

    // Ambil foto TERBARU dari DB
    $stmt = mysqli_prepare($conn, "SELECT photo FROM users WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    $photo = $res['photo'] ?? null;

    // Hapus file fisik
    if ($photo && file_exists('../' . $photo)) {
        unlink('../' . $photo);
    }

    // Kosongkan DB (AMAN)
    $stmt = mysqli_prepare($conn, "UPDATE users SET photo=NULL WHERE id=?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    header("Location: profile.php?tab=edit&deleted=1");
    exit;
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

/* INFO BOX */
.info-box{
    background: #f8fafc;
    border-left:4px solid #fef3c7;
    padding:1rem;
    border-radius:8px;
    margin-top:1rem;
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
<div class="badge bg-danger mt-2">Administrator</div>

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
<a class="nav-link" href="dashboard.php">
    <i class="fa-solid fa-arrow-left"></i> <span>Kembali ke Dashboard</span>
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
            <div class="modal-label">Role</div>
            <div class="modal-value">
                <span class="badge bg-danger">Administrator</span>
            </div>
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
                           onchange="document.getElementById('photoForm').submit()">
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

<?php endif; ?>

</div>
</div>
</div>

<?php include '../includes/footer.php'; ?>