<!-- HAMBURGER MENU -->
<button class="hamburger" id="hamburgerBtn">
    <span></span>
    <span></span>
    <span></span>
</button>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
    <h4><i class="fa-solid fa-hotel"></i> Admin Delitha</h4>

    <a href="<?= BASE_URL ?>/admin/dashboard.php"
       class="<?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-chart-line"></i> Dashboard
    </a>

    <a href="<?= BASE_URL ?>/admin/reservations.php"
       class="<?= basename($_SERVER['PHP_SELF']) == 'reservations.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-calendar-check"></i> Reservasi
    </a>

    <a href="<?= BASE_URL ?>/admin/cancellations.php"
       class="<?= basename($_SERVER['PHP_SELF']) == 'cancellations.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-ban"></i> Pembatalan
    </a>
    <a href="<?= BASE_URL ?>/admin/rooms/rooms.php"
        class="<?= strpos($_SERVER['PHP_SELF'], '/admin/rooms/') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-users"></i> Kamar
    </a>
    <a href="<?= BASE_URL ?>/admin/users/user.php"
    class="<?= strpos($_SERVER['PHP_SELF'], '/admin/users/') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-users"></i> User
    </a>


    <a href="<?= BASE_URL ?>/admin/blogs/manage.php"
       class="<?= strpos($_SERVER['PHP_SELF'], '/admin/blogs/') !== false ? 'active' : '' ?>">
        <i class="fa-solid fa-blog"></i> Blog
    </a>

    <a href="<?= BASE_URL ?>/admin/finance_dashboard.php"
       class="<?= basename($_SERVER['PHP_SELF']) == 'finance_dashboard.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-coins"></i> Keuangan
    </a>

    <a href="<?= BASE_URL ?>/admin/logs.php"
       class="<?= basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active' : '' ?>">
        <i class="fa-solid fa-clipboard-list"></i> Activity Logs
    </a>

    <hr style="border-color: rgba(255,255,255,0.1); margin: 0.75rem 0;">

    <a href="<?= BASE_URL ?>/index.php">
        <i class="fa-solid fa-globe"></i> Website
    </a>

    <a href="<?= BASE_URL ?>/auth/logout.php" class="text-warning">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
</div>


<style>
/* =======================
   SIDEBAR STYLES - FIXED
======================= */
.sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 200px;
    background: linear-gradient(180deg, #0f172a, #020617);
    color: #fff;
    padding: 1.25rem 1rem;
    overflow-y: auto;
    z-index: 1000;
    transition: transform .3s ease;
}

/* Custom Scrollbar */
.sidebar::-webkit-scrollbar {
    width: 6px;
}

.sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb {
    background: rgba(250, 204, 21, 0.3);
    border-radius: 10px;
}

.sidebar::-webkit-scrollbar-thumb:hover {
    background: rgba(250, 204, 21, 0.5);
}

.sidebar h4 {
    font-weight: 800;
    letter-spacing: .6px;
    color: #facc15;
    margin-bottom: 1.25rem;
    font-size: 1rem;
}

.sidebar a {
    color: #cbd5f5;
    text-decoration: none;
    display: block;
    padding: 10px 12px;
    border-radius: 12px;
    margin-bottom: 4px;
    transition: all .3s ease;
    font-weight: 500;
    font-size: 0.9rem;
}

.sidebar a:hover,
.sidebar a.active {
    background: rgba(250, 204, 21, .15);
    color: #facc15;
    font-weight: 700;
}

.sidebar a i {
    width: 18px;
    margin-right: 8px;
    font-size: 0.9rem;
}

/* =======================
   HAMBURGER MENU
======================= */
.hamburger {
    display: none;
    position: fixed;
    top: 15px;
    left: 15px;
    z-index: 1001;
    background: #facc15;
    border: none;
    border-radius: 8px;
    width: 45px;
    height: 45px;
    cursor: pointer;
    padding: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
}

.hamburger:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 15px rgba(250, 204, 21, 0.4);
}

.hamburger span {
    display: block;
    height: 3px;
    width: 100%;
    background: #0f172a;
    margin: 6px 0;
    transition: 0.3s;
    border-radius: 2px;
}

.hamburger.active span:nth-child(1) {
    transform: rotate(-45deg) translate(-5px, 6px);
}

.hamburger.active span:nth-child(2) {
    opacity: 0;
}

.hamburger.active span:nth-child(3) {
    transform: rotate(45deg) translate(-5px, -6px);
}

/* =======================
   SIDEBAR MOBILE (SLIDE)
======================= */
@media (max-width: 767.98px) {
    .sidebar {
        width: 250px;
        transform: translateX(-100%);
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
    }

    .sidebar.show {
        transform: translateX(0);
    }

    .hamburger {
        display: block;
    }
}

/* =======================
   SIDEBAR DESKTOP (FIXED)
======================= */
@media (min-width: 768px) {
    .sidebar {
        position: fixed !important;
        transform: none !important;
        width: 200px !important;
        height: 100vh !important;
        left: 0 !important;
        top: 0 !important;
    }
    
    /* Hamburger hidden di desktop */
    .hamburger {
        display: none !important;
    }
}

/* =======================
   OVERLAY FOR MOBILE
======================= */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 999;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.sidebar-overlay.show {
    display: block;
    opacity: 1;
}

@media (min-width: 768px) {
    .sidebar-overlay {
        display: none !important;
    }
}
</style>

<!-- OVERLAY FOR MOBILE -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<script>
// =============================================
// SIDEBAR TOGGLE SYSTEM - UNIVERSAL
// =============================================
(function() {
    'use strict';
    
    const hamburger = document.getElementById('hamburgerBtn');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (!hamburger || !sidebar || !overlay) {
        console.warn('Sidebar elements not found');
        return;
    }
    
    // Toggle Sidebar Function
    function toggleSidebar() {
        sidebar.classList.toggle('show');
        hamburger.classList.toggle('active');
        overlay.classList.toggle('show');
        
        // Prevent body scroll when sidebar is open on mobile
        if (window.innerWidth <= 767) {
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        }
    }
    
    // Close Sidebar Function
    function closeSidebar() {
        if (window.innerWidth <= 767) {
            sidebar.classList.remove('show');
            hamburger.classList.remove('active');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    }
    
    // Event: Hamburger Click
    hamburger.addEventListener('click', function(e) {
        e.stopPropagation();
        toggleSidebar();
    });
    
    // Event: Overlay Click (Close sidebar)
    overlay.addEventListener('click', closeSidebar);
    
    // Event: Sidebar Links Click (Close on mobile)
    const sidebarLinks = sidebar.querySelectorAll('a');
    sidebarLinks.forEach(function(link) {
        link.addEventListener('click', closeSidebar);
    });
    
    // Event: Window Resize (Reset on desktop)
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth > 767) {
                closeSidebar();
            }
        }, 250);
    });
    
    // Event: ESC Key (Close sidebar)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebar();
        }
    });
    
})();
</script>