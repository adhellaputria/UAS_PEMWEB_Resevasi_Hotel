<?php
require_once '../config/database.php';

/*
|--------------------------------------------------------------------------
| CEK JIKA SUDAH LOGIN
|--------------------------------------------------------------------------
*/
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: ../admin/dashboard.php');
        exit();
    } else {
        header('Location: ../index.php');
        exit();
    }
}

$error = '';

/*
|--------------------------------------------------------------------------
| PROSES LOGIN
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = clean($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Email dan password harus diisi!';
    } else {
        $query = "SELECT id, name, email, password, role
                  FROM users
                  WHERE email = ? AND status = 'active'
                  LIMIT 1";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($result && mysqli_num_rows($result) === 1) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['name']    = $user['name'];
                $_SESSION['email']   = $user['email'];
                $_SESSION['role']    = strtolower(trim($user['role']));

                if ($_SESSION['role'] === 'admin') {
                    header('Location: ../admin/dashboard.php');
                } else {
                    header('Location: ../index.php');
                }
                exit();
            } else {
                $error = 'Password salah!';
            }
        } else {
            $error = 'Email tidak ditemukan atau akun tidak aktif!';
        }
    }
}

$page_title = 'Login - Hotel Delitha';
include '../includes/header.php';
?>

<style>
/* ================= GLOBAL ================= */
html, body {
    width: 100%;
    height: 100%;
    margin: 0;
    overflow-x: hidden;
}

/* pastel background */
body {
    background: linear-gradient(135deg, #f6f8fc 0%, #e9eef8 100%) !important;
}

/* netralin wrapper layout */
main, .container, .wrapper, .content {
    background: transparent !important;
}

/* ================= LOGIN AREA ================= */
.login-page {
    min-height: calc(100vh - 90px);
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* ================= CARD ================= */
.login-card {
    background: #ffffff;
    width: 100%;
    max-width: 460px;
    padding: 48px;
    border-radius: 22px;
    box-shadow: 0 25px 60px rgba(0,0,0,.08);
    color: #334155;
}

/* ICON */
.login-icon {
    width: 78px;
    height: 78px;
    margin: 0 auto 20px;
    border-radius: 50%;
    background: #fef3c7;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #f59e0b;
    font-size: 30px;
}

.login-card h3 {
    text-align: center;
    font-weight: 700;
    color: #1e293b;
}

.login-card p {
    text-align: center;
    color: #64748b;
    margin-bottom: 30px;
}

/* FORM */
.login-card .form-control {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #334155;
    padding: 14px;
    border-radius: 12px;
}

.login-card .form-control:focus {
    border-color: #a5b4fc;
    box-shadow: 0 0 0 3px rgba(165,180,252,.35);
}

/* BUTTON */
.login-btn {
    background: linear-gradient(135deg, #fde68a, #fcd34d);
    border: none;
    color: #78350f;
    font-weight: 700;
    padding: 14px;
    border-radius: 14px;
    transition: .3s;
}

.login-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px rgba(251,191,36,.45);
}

/* FOOTER TEXT */
.login-footer {
    margin-top: 26px;
    text-align: center;
    color: #64748b;
}

.login-footer a {
    color: #2a2b78ff;
    font-weight: 600;
    text-decoration: none;
}
</style>

<!-- ================= LOGIN CONTENT ================= -->
<div class="login-page">
    <div class="login-card">

        <div class="login-icon">
            <i class="fas fa-key"></i>
        </div>

        <h3>Selamat Datang</h3>
        <p>Login untuk melanjutkan reservasi Anda</p>

        <?php if ($error): ?>
            <div class="alert alert-danger text-center">
                <?= $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" name="email"
                       class="form-control"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                       required>
            </div>

            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password"
                       class="form-control"
                       required>
            </div>

            <button type="submit" class="login-btn w-100">
                Login
            </button>
        </form>

        <div class="login-footer">
            Belum punya akun?
            <a href="register.php">Daftar sekarang</a>
        </div>

    </div>
</div>

<?php include '../includes/footer.php'; ?>
