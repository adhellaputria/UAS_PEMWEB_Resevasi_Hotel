<?php
require_once '../config/database.php';
requireAdmin();

/* =====================
   STATISTIK DASHBOARD
===================== */

$today_bookings = mysqli_num_rows(mysqli_query($conn,"
    SELECT id FROM reservations WHERE DATE(created_at)=CURDATE()
"));

$pending_bookings = mysqli_num_rows(mysqli_query($conn,"
    SELECT id FROM reservations WHERE status='pending'
"));

$pending_cancellations = mysqli_num_rows(mysqli_query($conn,"
    SELECT id FROM cancellations WHERE status='pending'
"));

$revenue = mysqli_fetch_assoc(mysqli_query($conn,"
    SELECT SUM(amount) AS total
    FROM payments
"));
$total_revenue = $revenue['total'] ?? 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Admin - Hotel Delitha</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        /* ================= DASHBOARD STYLE MASTER ================= */
        body {
            background: #f8fafc;
            font-family: 'Inter', sans-serif;
        }

        /* LAYOUT */
        .main-content {
            padding: 2rem;
        }
        @media (min-width: 768px) {
            .main-content {
                margin-left: 200px;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                padding-top: 5rem;
            }
        }

        /* ===== PAGE HEADER ===== */
        .page-header {
            margin-bottom: 2rem;
            animation: fadeInDown .6s ease-out;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 800;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 0.5rem;
        }

        .page-title i {
            color: #facc15;
            font-size: 1.8rem;
        }

        .page-subtitle {
            color: #64748b;
            font-size: .95rem;
            margin: 0;
        }

        .welcome-name {
            color: #0f172a;
            font-weight: 700;
        }

        /* ===== ANIMATIONS ===== */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes scaleIn {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* ===== INFO ALERT ===== */
        .info-alert {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border: 2px solid #facc15;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            animation: fadeInUp .6s ease-out .1s backwards;
        }

        .info-alert strong {
            color: #92400e;
            font-weight: 700;
        }

        .info-alert p {
            color: #78350f;
            margin: 0.25rem 0 0 0;
            font-size: 0.9rem;
        }

        /* ===== STAT CARDS ===== */
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            animation: scaleIn .6s ease-out backwards;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .stat-card:nth-child(1) { animation-delay: .2s; }
        .stat-card:nth-child(2) { animation-delay: .3s; }
        .stat-card:nth-child(3) { animation-delay: .4s; }
        .stat-card:nth-child(4) { animation-delay: .5s; }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, var(--card-color-1), var(--card-color-2));
        }

        /* Card Colors */
        .stat-card.card-primary {
            --card-color-1: #3b82f6;
            --card-color-2: #1d4ed8;
        }

        .stat-card.card-warning {
            --card-color-1: #f59e0b;
            --card-color-2: #d97706;
        }

        .stat-card.card-danger {
            --card-color-1: #ef4444;
            --card-color-2: #dc2626;
        }

        .stat-card.card-success {
            --card-color-1: #10b981;
            --card-color-2: #059669;
        }

        .stat-content {
            flex: 1;
        }

        .stat-label {
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1;
            margin-bottom: 0;
        }

        .stat-value.revenue {
            font-size: 1.5rem;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            flex-shrink: 0;
        }

        .stat-card.card-primary .stat-icon {
            background: rgba(59, 130, 246, 0.15);
            color: #3b82f6;
        }

        .stat-card.card-warning .stat-icon {
            background: rgba(245, 158, 11, 0.15);
            color: #f59e0b;
        }

        .stat-card.card-danger .stat-icon {
            background: rgba(239, 68, 68, 0.15);
            color: #ef4444;
        }

        .stat-card.card-success .stat-icon {
            background: rgba(16, 185, 129, 0.15);
            color: #10b981;
        }

        /* HILANGKAN SEMUA UNDERLINE LINK */
        a,
        a:link,
        a:visited,
        a:hover,
        a:active,
        a:focus {
            text-decoration: none !important;
            color: inherit;
        }

        /* KHUSUS LINK YANG BUNGKUS CARD */
        .stat-link {
            display: block;
            text-decoration: none !important;
            color: inherit;
        }

        /* ===== TABLE CARD ===== */
        .table-card {
            background: #fff;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .06);
            animation: fadeInUp .6s ease-out .6s backwards;
        }

        .table-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-title i {
            color: #facc15;
        }

        /* ===== TABLE ===== */
        .table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            font-size: .875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: none;
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            color: #334155;
        }

        .table tbody tr {
            transition: .25s;
        }

        .table tbody tr:hover {
            background: #f8fafc;
        }

        /* ===== STATUS BADGE ===== */
        .badge-paid {
            background: #dcfce7;
            color: #166534;
            padding: 0.4rem 0.75rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* ===== EMPTY STATE ===== */
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #94a3b8;
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.3;
        }

        .empty-state p {
            margin: 0;
            font-size: 1rem;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .main-content {
                padding: 1rem;
                padding-top: 5rem;
            }

            .stats-grid {
                gap: 0.75rem;
            }

            .stat-card {
                padding: 1rem 1.25rem;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .stat-value.revenue {
                font-size: 1.25rem;
            }

            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
            }

            .table {
                font-size: 0.85rem;
            }

            .table thead th {
                font-size: 0.65rem;
                padding: 0.75rem 0.5rem;
            }

            .table tbody td {
                padding: 0.75rem 0.5rem;
            }

            .page-title {
                font-size: 1.5rem;
            }

            .page-title i {
                font-size: 1.3rem;
            }

            .table-card {
                padding: 1rem;
            }
            .stat-link {
                text-decoration: none;
                color: inherit;
                display: block;
            }

            .stat-link:focus,
            .stat-link:hover {
                color: inherit;
            }

        }
    </style>
</head>

<body>
    <!-- SIDEBAR -->
     <?php include_once '../includes/sidebar.php'; ?>


    <!-- MAIN CONTENT -->
    <div class="main-content">

        <!-- PAGE HEADER -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fa-solid fa-chart-line"></i>
                Dashboard Admin
            </h1>
            <p class="page-subtitle">
                Selamat datang, <span class="welcome-name"><?= $_SESSION['name']; ?></span>
            </p>
        </div>

        <!-- INFO ALERT -->
        <div class="info-alert">
            <strong><i class="fa-solid fa-circle-info"></i> Ringkasan Operasional</strong>
            <p>Monitoring reservasi harian, pembatalan menunggu approval, dan total pendapatan sistem</p>
        </div>

        <!-- STATISTICS CARDS -->
        <div class="stats-grid">

        <!-- Card 1: Reservasi Hari Ini -->
        <a href="reservations.php" class="stat-link">
            <div class="stat-card card-primary">
                <div class="stat-content">
                    <div class="stat-label">Reservasi Hari Ini</div>
                    <h2 class="stat-value"><?= $today_bookings ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-calendar-day"></i>
                </div>
            </div>
        </a>

        <!-- Card 2: Booking Pending -->
        <a href="reservations.php?status=pending" class="stat-link">
            <div class="stat-card card-warning">
                <div class="stat-content">
                    <div class="stat-label">Booking Pending</div>
                    <h2 class="stat-value"><?= $pending_bookings ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
            </div>
        </a>

        <!-- Card 3: Pembatalan Pending -->
        <a href="cancellations.php" class="stat-link">
            <div class="stat-card card-danger">
                <div class="stat-content">
                    <div class="stat-label">Pembatalan Pending</div>
                    <h2 class="stat-value"><?= $pending_cancellations ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-ban"></i>
                </div>
            </div>
        </a>

        <!-- Card 4: Total Pendapatan -->
        <a href="finance_dashboard.php" class="stat-link">
            <div class="stat-card card-success">
                <div class="stat-content">
                    <div class="stat-label">Total Pendapatan</div>
                    <h2 class="stat-value revenue"><?= formatRupiah($total_revenue) ?></h2>
                </div>
                <div class="stat-icon">
                    <i class="fa-solid fa-coins"></i>
                </div>
            </div>
        </a>
    </div> 
</div> 
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>