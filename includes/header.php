<?php
require_once __DIR__ . '/../config/database.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? $page_title : 'Hotel Delitha - Luxury Stay in Surakarta'; ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        :root {
            --primary-gold: #d4af37;
            --primary-dark: #1a1a2e;
            --secondary-dark: #16213e;
            --accent-gold: #f4d03f;
            --text-light: #f8f9fa;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        /* NAVBAR */
        .navbar-premium {
            background: linear-gradient(135deg, rgba(26, 26, 46, 0.98), rgba(22, 33, 62, 0.98));
            backdrop-filter: blur(20px);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            padding: 0.8rem 0;
            position: sticky;
            top: 0;
            z-index: 9999;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-bottom: 2px solid var(--primary-gold);
        }

        .navbar-premium.scrolled {
            padding: 0.5rem 0;
            background: linear-gradient(135deg, rgba(26, 26, 46, 1), rgba(22, 33, 62, 1));
            box-shadow: 0 5px 30px rgba(0, 0, 0, 0.5);
        }

        .navbar-brand-delitha {
            font-family: 'Playfair Display', serif;
            font-weight: 800;
            font-size: 1.5rem;
            color: var(--primary-gold) !important;
            text-transform: uppercase;
            letter-spacing: 2px;
            transition: all 0.3s ease;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
        }
        .navbar-brand-delitha:hover{
            color: var(--accent-gold) !important;
            transform: scale(1.05);
        }


        .navbar-brand-delitha i {
            margin-right: 8px;
            font-size: 1.3rem;
        }

        .navbar-brand-delitha,
        .navbar-brand-delitha:hover,
        .navbar-brand-delitha:focus,
        .navbar-brand-delitha:active,
        .navbar-brand-delitha:visited {
            text-decoration: none !important;
        }

        .nav-link-premium {
            color: var(--text-light) !important;
            font-weight: 500;
            font-size: 0.9rem;
            letter-spacing: 1px;
            padding: 0.5rem 1rem !important;
            position: relative;
            transition: all 0.3s ease;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            white-space: nowrap;
        }

        .nav-link-premium::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--primary-gold);
            transition: width 0.3s ease;
        }

        .nav-link-premium:hover {
            color: var(--primary-gold) !important;
        }

        .nav-link-premium:hover::before {
            width: 80%;
        }

        .nav-link-premium i {
            margin-right: 6px;
            font-size: 0.9rem;
        }

        .dropdown-menu-premium {
            background: rgba(26, 26, 46, 0.98);
            border: 1px solid var(--primary-gold);
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            margin-top: 10px;
            padding: 0.5rem 0;
            min-width: 220px;
        }

        .dropdown-item-premium {
            color: var(--text-light) !important;
            padding: 0.7rem 1.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .dropdown-item-premium:hover {
            background: linear-gradient(135deg, var(--primary-gold), var(--accent-gold));
            color: var(--primary-dark) !important;
            transform: translateX(5px);
        }

        .dropdown-item-premium i {
            margin-right: 10px;
            width: 20px;
        }

        .dropdown-divider {
            border-color: rgba(212, 175, 55, 0.3) !important;
            margin: 0.5rem 0;
        }

        .dropdown-item-premium.logout-item {
            color: #ff6b6b !important;
        }

        .dropdown-item-premium.logout-item:hover {
            background: linear-gradient(135deg, #ff6b6b, #ff5252);
            color: white !important;
        }

        .btn-login-premium {
            background: transparent;
            border: 2px solid var(--primary-gold);
            color: var(--primary-gold) !important;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .btn-login-premium:hover {
            background: var(--primary-gold);
            color: var(--primary-dark) !important;
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
        }

        .btn-register-premium {
            background: linear-gradient(135deg, var(--primary-gold), var(--accent-gold));
            border: none;
            color: var(--primary-dark) !important;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            box-shadow: 0 5px 20px rgba(212, 175, 55, 0.3);
            font-size: 0.85rem;
            white-space: nowrap;
        }

        .btn-register-premium:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(212, 175, 55, 0.5);
        }

        .navbar-toggler-premium {
            border: 2px solid var(--primary-gold);
            padding: 0.4rem 0.6rem;
        }

        .navbar-toggler-premium:focus {
            box-shadow: 0 0 0 0.2rem rgba(212, 175, 55, 0.3);
        }

        .navbar-toggler-icon-premium {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(212, 175, 55, 1)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* RESPONSIVE */
        @media (max-width: 991px) {
            .navbar-brand-delitha {
                font-size: 1.3rem;
            }

            .navbar-collapse {
                background: rgba(26, 26, 46, 0.98);
                margin-top: 1rem;
                padding: 1rem;
                border-radius: 10px;
                border: 1px solid var(--primary-gold);
            }

            .nav-link-premium {
                padding: 0.8rem 1rem !important;
                border-bottom: 1px solid rgba(212, 175, 55, 0.2);
                justify-content: center;
            }

            .btn-login-premium,
            .btn-register-premium {
                width: 100%;
                margin: 0.5rem 0;
                text-align: center;
                display: flex;
                justify-content: center;
            }

            .nav-item.ms-lg-2 {
                margin-left: 0 !important;
            }

            .dropdown-menu-premium {
                background: rgba(22, 33, 62, 0.98);
                margin-top: 0.5rem;
                width: 100%;
            }

            .dropdown-item-premium {
                text-align: center;
                justify-content: center;
            }
        }

        @media (max-width: 576px) {
            .navbar-premium {
                padding: 0.6rem 0;
            }

            .navbar-brand-delitha {
                font-size: 1.1rem;
            }

            .nav-link-premium {
                font-size: 0.85rem;
            }

            .btn-login-premium,
            .btn-register-premium {
                font-size: 0.8rem;
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-premium navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand-delitha" href="<?= BASE_URL ?>/index.php">
            <i class="fas fa-gem"></i> HOTEL DELITHA
        </a>

        <button class="navbar-toggler navbar-toggler-premium" type="button" data-bs-toggle="collapse" data-bs-target="#navbarDelitha">
            <span class="navbar-toggler-icon navbar-toggler-icon-premium"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarDelitha">
            <ul class="navbar-nav ms-auto align-items-lg-center">

                <li class="nav-item">
                    <a class="nav-link nav-link-premium" href="<?= BASE_URL ?>/index.php">
                        <i class="fas fa-home"></i> Beranda
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link nav-link-premium" href="<?= BASE_URL ?>/blog.php">
                        <i class="fas fa-newspaper"></i> Blog
                    </a>
                </li>

                <?php if (isLoggedIn()): ?>

                    <?php if (isAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link nav-link-premium" href="<?= BASE_URL ?>/admin/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link nav-link-premium" href="<?= BASE_URL ?>/user/dashboard.php">
                                <i class="fas fa-calendar-check"></i> Dashboard
                            </a>
                        </li>
                    <?php endif; ?>

                    <li class="nav-item dropdown">
                        <a class="nav-link nav-link-premium dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> <?= $_SESSION['name']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-premium dropdown-menu-end">
                            <li>
                                <a class="dropdown-item dropdown-item-premium" href="<?= BASE_URL ?><?= isAdmin() ? '/admin/profile.php' : '/user/profile.php' ?>">
                                    <i class="fas fa-user"></i> Profil Saya
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item dropdown-item-premium logout-item"
                                   href="<?= BASE_URL ?>/auth/logout.php"
                                   onclick="return confirm('Yakin ingin logout?')">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </li>

                <?php else: ?>

                    <li class="nav-item">
                        <a class="nav-link btn-login-premium" href="<?= BASE_URL ?>/auth/login.php">
                            <i class="fas fa-sign-in-alt"></i> LOGIN
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="nav-link btn-register-premium" href="<?= BASE_URL ?>/auth/register.php">
                            <i class="fas fa-user-plus"></i> REGISTER
                        </a>
                    </li>

                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const navbar = document.querySelector('.navbar-premium');
    
    // Scroll effect
    window.addEventListener('scroll', function() {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Auto close mobile menu when clicking a link (non-dropdown)
    const navLinks = document.querySelectorAll('.nav-link-premium:not(.dropdown-toggle)');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth < 992) {
                const navbarCollapse = document.querySelector('.navbar-collapse');
                const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
                if (bsCollapse) {
                    bsCollapse.hide();
                }
            }
        });
    });
});
</script>