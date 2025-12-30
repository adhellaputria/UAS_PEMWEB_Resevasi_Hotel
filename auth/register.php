<?php
require_once '../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isLoggedIn()) {
    redirect(isAdmin() ? '../admin/dashboard.php' : '../user/dashboard.php');
}

/* ================= AMBIL ENUM GENDER DARI DB ================= */
$enumGender = [];
$qEnum = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'gender'");
if ($qEnum) {
    $row = mysqli_fetch_assoc($qEnum);
    if (preg_match("/^enum\((.*)\)$/", $row['Type'], $matches)) {
        $enumGender = array_map(
            fn($v) => trim($v, "'"),
            explode(',', $matches[1])
        );
    }
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name       = clean($_POST['name'] ?? '');
    $email      = clean($_POST['email'] ?? '');
    $phone      = clean($_POST['phone'] ?? '');
    $gender     = $_POST['gender'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $address    = clean($_POST['address'] ?? '');
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    /* ================= VALIDASI ================= */

    if ($name === '' || $email === '' || $password === '') {
        $error = 'Nama, email, dan password wajib diisi.';
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    }
    elseif (!preg_match('/^[0-9]{10,13}$/', $phone)) {
        $error = 'Nomor telepon harus 10–13 digit.';
    }
    elseif (!in_array($gender, $enumGender, true)) {
        $error = 'Jenis kelamin tidak valid.';
    }
    elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    }
    elseif ($password !== $confirm) {
        $error = 'Konfirmasi password tidak cocok.';
    }
    else {

        /* ===== VALIDASI UMUR ≥ 17 ===== */
        if ($birth_date !== '') {
            $birth = new DateTime($birth_date);
            $today = new DateTime();
            if ($today->diff($birth)->y < 17) {
                $error = 'Usia minimal untuk registrasi adalah 17 tahun.';
            }
        }

        if (!$error) {

            $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email=?");
            mysqli_stmt_bind_param($check, "s", $email);
            mysqli_stmt_execute($check);

            if (mysqli_num_rows(mysqli_stmt_get_result($check)) > 0) {
                $error = 'Email sudah terdaftar.';
            } else {

                $hash = password_hash($password, PASSWORD_DEFAULT);
                $birth_date_db = $birth_date !== '' ? $birth_date : null;

                $stmt = mysqli_prepare($conn,
                    "INSERT INTO users
                    (name, email, phone, gender, birth_date, address, password, role, status)
                    VALUES (?,?,?,?,?,?,?,'user','active')"
                );

                mysqli_stmt_bind_param(
                    $stmt,
                    "sssssss",
                    $name,
                    $email,
                    $phone,
                    $gender,
                    $birth_date_db,
                    $address,
                    $hash
                );

                if (mysqli_stmt_execute($stmt)) {
                    $success = true;
                } else {
                    $error = 'Registrasi gagal.';
                }
            }
        }
    }
}

$page_title = 'Daftar Akun - Hotel Delitha';
include '../includes/header.php';
?>

<style>
:root{
    --soft:#f8fafc;
    --gold1:#fde68a;
    --gold2:#fcd34d;
}
body{background:var(--soft);}
.card-register{
    border:none;
    border-radius:22px;
    box-shadow:0 16px 40px rgba(0,0,0,.07);
}
.btn-gold{
    background:linear-gradient(135deg,var(--gold1),var(--gold2));
    color:#78350f;
    font-weight:600;
    border-radius:999px;
}
</style>

<div class="container my-5">
<div class="col-lg-7 mx-auto">

<div class="card card-register">
<div class="p-4 border-bottom text-center">
<h4 class="fw-bold mb-1">Buat Akun Baru</h4>
<p class="text-muted mb-0">Daftar untuk mulai reservasi di Hotel Delitha</p>
</div>

<div class="p-4">

<?php if ($error): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<form method="POST">

<div class="mb-3">
<label class="fw-bold">Nama Lengkap</label>
<input name="name" class="form-control" required
value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
</div>

<div class="mb-3">
<label class="fw-bold">Email</label>
<input type="email" name="email" class="form-control" required
value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
</div>

<div class="mb-3">
<label class="fw-bold">No. Telepon</label>
<input name="phone" class="form-control" required
value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label class="fw-bold">Password</label>
<input type="password" name="password" class="form-control" required>
</div>
<div class="col-md-6 mb-3">
<label class="fw-bold">Konfirmasi Password</label>
<input type="password" name="confirm_password" class="form-control" required>
</div>
</div>

<div class="row">
<div class="col-md-6 mb-3">
<label class="fw-bold">Jenis Kelamin</label>
<select name="gender" class="form-select" required>
<option value="">Pilih</option>
<?php foreach ($enumGender as $g): ?>
<option value="<?= $g ?>" <?= (($_POST['gender'] ?? '') === $g) ? 'selected' : '' ?>>
<?= $g ?>
</option>
<?php endforeach; ?>
</select>
</div>

<div class="col-md-6 mb-3">
<label class="fw-bold">Tanggal Lahir</label>
<input type="date" name="birth_date" class="form-control"
max="<?= date('Y-m-d', strtotime('-17 years')) ?>"
value="<?= htmlspecialchars($_POST['birth_date'] ?? '') ?>">
</div>
</div>

<div class="mb-3">
<label class="fw-bold">Alamat</label>
<textarea name="address" class="form-control"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>
</div>

<button class="btn btn-gold w-100 mt-3">
Daftar Sekarang
</button>

</form>

</div>
</div>

</div>
</div>

<?php if ($success): ?>
<script>
alert('Registrasi berhasil! Silakan login.');
window.location.href = 'login.php';
</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
