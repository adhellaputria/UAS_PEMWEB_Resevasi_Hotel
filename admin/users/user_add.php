<?php
require_once '../../config/database.php';
requireAdmin();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = clean($_POST['name']);
    $email    = clean($_POST['email']);
    $password = $_POST['password'];
    $role     = clean($_POST['role']);

    if (!$name || !$email || !$password || !$role) {
        $error = "Semua field wajib diisi.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = mysqli_prepare($conn, "
            INSERT INTO users (name,email,password,role,status,created_at)
            VALUES (?,?,?,?, 'active', NOW())
        ");
        mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $hash, $role);

        if (mysqli_stmt_execute($stmt)) {

        // ✅ LOG AKTIVITAS ADMIN
        logActivity(
            $conn,
            'CREATE USER',
            'Menambahkan user baru dengan email ' . $email
        );

        header("Location: " . BASE_URL . "/admin/users/user.php?success=User berhasil ditambahkan");
        exit;

    }
    else {
            $error = "Email sudah digunakan.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tambah User</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
/* ================= MASTER STYLE (SAMA ROOMS.PHP) ================= */
body{
    background:#f8fafc;
    font-family:'Inter',sans-serif;
}

/* =======================
   LAYOUT + SIDEBAR FIX
======================= */
.main-content {
    margin-left: 200px;        /* sama dengan sidebar */
    padding: 2rem 2.5rem;      /* biar ga mepet */
}

/* LAPTOP KECIL */
@media (max-width: 1200px) {
    .main-content {
        padding: 1.5rem 2rem;
    }
}

/* MOBILE */
@media (max-width: 767.98px) {
    .main-content {
        margin-left: 0;
        padding: 1rem;
        padding-top: 5rem;     /* ruang hamburger */
    }
}


/* CARD FORM */
.user-card{
    background:#fff;
    border-radius:16px;
    padding:1.75rem;
    max-width:640px;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
    animation:fadeInUp .6s ease-out;
}

/* HEADER */
.user-card h4{
    font-weight:800;
    color:#0f172a;
}
.user-card p{
    color:#64748b;
    margin-bottom:1.5rem;
}

/* LABEL */
.form-label{
    font-size:.75rem;
    font-weight:700;
    color:#475569;
    text-transform:uppercase;
}

/* INPUT */
.form-control,
.form-select{
    font-size:.85rem;
    border-radius:10px;
    padding:.55rem .75rem;
}

/* BUTTON */
.btn-save{
    background:linear-gradient(135deg,#facc15,#f59e0b);
    border:none;
    color:#0f172a;
    font-weight:700;
    padding:.6rem 1.4rem;
    border-radius:12px;
    transition:.25s ease;
}
.btn-save:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(250,204,21,.35);
}
.btn-outline-secondary{
    font-size:.85rem;
    border-radius:12px;
    padding:.6rem 1.4rem;
}

/* ANIMATION */
@keyframes fadeInUp{
    from{
        opacity:0;
        transform:translateY(30px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}
</style>
</head>

<body>

<div class="container-fluid">
  <div class="row min-vh-100">

    <!-- SIDEBAR (SAMA DENGAN ROOMS.PHP) -->
<?php include_once '../../includes/sidebar.php'; ?>
<div class="main-content">
    <div class="col-md-10">


        <div class="user-card">
            <h4>
                <i class="fa-solid fa-user-plus text-warning"></i>
                Tambah User
            </h4>
            <p>Tambahkan akun baru ke dalam sistem</p>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">

                <div class="mb-3">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="mb-4">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select" required>
                        <option value="user">User</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button class="btn btn-save">
                        <i class="fa-solid fa-floppy-disk"></i> Simpan
                    </button>
                    <a href="<?= BASE_URL ?>/admin/users/user.php" class="btn btn-outline-secondary">
                        Batal
                    </a>

                </div>

            </form>
        </div>

    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
