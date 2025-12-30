<?php
require_once 'config/database.php';
$page_title = 'Beranda - Hotel Delitha';

// Get all available rooms for carousel
$query_rooms = "SELECT * FROM rooms WHERE status='available' ORDER BY created_at DESC";
$result_rooms = mysqli_query($conn, $query_rooms);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include 'includes/header.php';
?>

<style>
:root{
    --navy:#0b1220;
    --soft:#f8fafc;
    --gold:#d4af37;
    --gold-dark:#b8941f;
    --text:#475569;
}

/* ===== GLOBAL ===== */
body{
    background:#fff;
}
section{overflow:hidden}
.wrap{max-width:1200px;margin:auto}

/* ===== HERO ===== */
.hero{
    padding:6rem 1.5rem;
    border-radius:42px;
    text-align:center;
    color:#fff;

    background:
        radial-gradient(60% 40% at 80% 0%, rgba(212,175,55,.25), transparent 60%),
        radial-gradient(40% 30% at 10% 100%, rgba(212,175,55,.18), transparent 65%),
        linear-gradient(
            rgba(8,12,24,.72),
            rgba(8,12,24,.72)
        ),
        url('uploads/hotel-delitha-hero.jpg') center/cover no-repeat;

    box-shadow:
        0 40px 90px rgba(0,0,0,.45),
        inset 0 0 0 1px rgba(255,255,255,.04);

    animation: fadeInUp .8s ease;
}


.hero::after{
    content:'';
    position:absolute;
    inset:0;
    background:url('assets/img/hero.jpg') center/cover;
    opacity:.08;
    transform:translateY(var(--scroll));
}
.hero-content{
    position:relative;
    z-index:2;
    max-width:780px;
    margin:auto;
}
.hero h1{
    font-size:3.2rem;
    font-weight:800;
    letter-spacing:-.02em;
    animation:slideDown .9s ease;
}
.hero p{
    margin:1.5rem auto 2.8rem;
    color:#e5e7eb;
    line-height:1.9;
    font-size:1.1rem;
    animation:fadeUp .9s ease .2s both;
}
.hero .cta{
    animation:fadeUp .9s ease .4s both;
}

/* ===== BUTTON ===== */
.btn-gold{
    background:linear-gradient(135deg,var(--gold),var(--gold-dark));
    color:#0b1220;
    border-radius:999px;
    font-weight:800;
    padding:1rem 2.6rem;
    box-shadow:0 18px 45px rgba(212,175,55,.45);
    transition:.35s;
    text-decoration:none;
    display:inline-block;
}
.btn-gold:hover{
    transform:translateY(-4px) scale(1.02);
    color:#0b1220;
}

/* ===== ANIMATION ===== */
@keyframes fadeUp{
    from{opacity:0;transform:translateY(30px)}
    to{opacity:1;transform:none}
}
@keyframes slideDown{
    from{opacity:0;transform:translateY(-40px)}
    to{opacity:1;transform:none}
}

/* ===== FEATURES ===== */
.feature-card{
    background:#fff;
    border-radius:26px;
    padding:2.4rem 2rem;
    box-shadow:0 20px 50px rgba(0,0,0,.08);
    text-align:center;
    transition:.4s;
}
.feature-card:hover{
    transform:translateY(-10px);
}
.feature-icon{
    width:70px;
    height:70px;
    margin:0 auto 1.2rem;
    border-radius:22px;
    background:linear-gradient(135deg,var(--gold),var(--gold-dark));
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:28px;
    color:#0b1220;
}

/* ===== ROOMS CAROUSEL ===== */
.rooms-carousel-wrapper{
    position:relative;
    padding:0 60px;
}

.rooms-carousel{
    overflow:hidden;
    position:relative;
}

.rooms-track{
    display:flex;
    transition:transform .5s cubic-bezier(0.4, 0, 0.2, 1);
}

.room-slide{
    min-width:calc(33.333% - 20px);
    margin:0 10px;
}

.room-card{
    background:#fff;
    border-radius:28px;
    box-shadow:0 20px 55px rgba(0,0,0,.08);
    overflow:hidden;
    transition:.4s;
    height:100%;
    display:flex;
    flex-direction:column;
}
.room-card:hover{
    transform:translateY(-12px);
    box-shadow:0 30px 70px rgba(0,0,0,.15);
}

.room-img-wrapper{
    position:relative;
    height:240px;
    overflow:hidden;
    background:linear-gradient(135deg, #e5e7eb, #d1d5db);
}

.room-img{
    width:100%;
    height:100%;
    object-fit:cover;
    transition:transform .5s ease;
}

.room-card:hover .room-img{
    transform:scale(1.1);
}

.room-img-placeholder{
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:3.5rem;
    color:#9ca3af;
}

.room-body{
    padding:1.8rem;
    flex-grow:1;
    display:flex;
    flex-direction:column;
}

.room-title{
    font-size:1.3rem;
    font-weight:700;
    margin-bottom:.75rem;
    color:var(--navy);
}

.room-meta{
    display:flex;
    gap:.5rem;
    align-items:center;
    color:#64748b;
    font-size:.9rem;
    margin-bottom:1rem;
}

.room-meta i{
    color:var(--gold);
}

.room-description{
    color:#64748b;
    line-height:1.6;
    margin-bottom:1.25rem;
    flex-grow:1;
}

.room-price{
    font-weight:800;
    color:var(--gold-dark);
    font-size:1.3rem;
    margin-bottom:1rem;
}

/* CAROUSEL CONTROLS */
.carousel-btn{
    position:absolute;
    top:50%;
    transform:translateY(-50%);
    width:50px;
    height:50px;
    border-radius:50%;
    background:linear-gradient(135deg,var(--gold),var(--gold-dark));
    color:#0b1220;
    border:none;
    font-size:1.2rem;
    cursor:pointer;
    transition:.3s;
    box-shadow:0 8px 25px rgba(212,175,55,.4);
    z-index:10;
}

.carousel-btn:hover{
    transform:translateY(-50%) scale(1.1);
    box-shadow:0 12px 35px rgba(212,175,55,.6);
}

.carousel-btn-prev{
    left:0;
}

.carousel-btn-next{
    right:0;
}

/* CAROUSEL DOTS */
.carousel-dots{
    display:flex;
    justify-content:center;
    gap:.75rem;
    margin-top:2rem;
}

.carousel-dot{
    width:12px;
    height:12px;
    border-radius:50%;
    background:#d1d5db;
    border:none;
    cursor:pointer;
    transition:.3s;
}

.carousel-dot.active{
    background:var(--gold);
    width:32px;
    border-radius:6px;
}

/* VIEW ALL BUTTON */
.btn-view-all{
    display:inline-block;
    margin-top:2rem;
    padding:.85rem 2rem;
    border-radius:999px;
    background:transparent;
    border:2px solid var(--gold);
    color:var(--gold);
    font-weight:700;
    transition:.3s;
    text-decoration:none;
}

.btn-view-all:hover{
    background:var(--gold);
    color:var(--navy);
    transform:translateY(-3px);
}

/* ===== REVEAL ===== */
.reveal{
    opacity:0;
    transform:translateY(50px);
    transition:1s ease;
}
.reveal.show{
    opacity:1;
    transform:none;
}

/* ===== CTA FINAL ===== */
.cta-final{
    background:linear-gradient(135deg,#0b1220,#111827);
    color:#fff;
    border-radius:36px;
    padding:5rem 2rem;
    text-align:center;
}

/* ===== RESPONSIVE ===== */
@media(max-width:992px){
    .room-slide{
        min-width:calc(50% - 20px);
    }
}

@media(max-width:768px){
    .hero h1{font-size:2.2rem}
    .room-slide{
        min-width:calc(100% - 20px);
    }
    .rooms-carousel-wrapper{
        padding:0 50px;
    }
    .carousel-btn{
        width:40px;
        height:40px;
        font-size:1rem;
    }
}
</style>

<!-- ================= HERO ================= -->
<section class="hero">
    <div class="hero-content">
        <h1>HOTEL DELITHA</h1>
        <p>
            Pengalaman menginap modern di Kota Surakarta
            dengan sistem reservasi online, check-in mandiri,
            dan pengelolaan pemesanan terintegrasi.
        </p>
        <div class="cta">
            <a href="user/reserve.php" class="btn btn-gold btn-lg">
                <i class="fa-solid fa-calendar-check me-2"></i>Mulai Reservasi
            </a>
        </div>
    </div>
</section>

<!-- ================= FITUR ================= -->
<section class="py-5">
<div class="container wrap">
<div class="text-center mb-5 reveal">
    <h2 class="fw-bold">Fitur Utama</h2>
    <p class="text-muted">Semua layanan dalam satu sistem</p>
</div>

<div class="row g-4">
<?php
$features=[
    ['Reservasi Online','calendar-check'],
    ['Check-in Online','sign-in-alt'],
    ['Pembatalan Reservasi','ban'],
    ['Infografis Data','chart-bar'],
];
foreach($features as $i=>$f):
?>
<div class="col-md-3 reveal" style="transition-delay:<?= $i*0.1 ?>s">
    <div class="feature-card h-100">
        <div class="feature-icon">
            <i class="fas fa-<?= $f[1]; ?>"></i>
        </div>
        <h6 class="fw-bold"><?= $f[0]; ?></h6>
        <p class="text-muted small mb-0">
            Dikelola melalui dashboard pengguna
        </p>
    </div>
</div>
<?php endforeach; ?>
</div>
</div>
</section>

<!-- ================= ROOMS CAROUSEL ================= -->
<section class="py-5 bg-light">
<div class="container wrap">
<div class="text-center mb-5 reveal">
    <h2 class="fw-bold">Pilihan Kamar</h2>
    <p class="text-muted">Nyaman & fleksibel untuk setiap kebutuhan</p>
</div>

<div class="rooms-carousel-wrapper reveal">
    <button class="carousel-btn carousel-btn-prev" onclick="moveCarousel(-1)">
        <i class="fa-solid fa-chevron-left"></i>
    </button>
    
    <div class="rooms-carousel">
        <div class="rooms-track" id="roomsTrack">
            <?php 
            mysqli_data_seek($result_rooms, 0); // Reset pointer
            if(mysqli_num_rows($result_rooms) > 0):
                while($room=mysqli_fetch_assoc($result_rooms)): 
            ?>
            <div class="room-slide">
                <div class="room-card">
                    <div class="room-img-wrapper">
                        <?php if(!empty($room['image']) && file_exists('uploads/rooms/'.$room['image'])): ?>
                            <img 
                                src="uploads/rooms/<?= htmlspecialchars($room['image']); ?>" 
                                class="room-img"
                                alt="<?= htmlspecialchars($room['name']); ?>"
                            >
                        <?php else: ?>
                            <div class="room-img-placeholder">
                                <i class="fa-solid fa-bed"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="room-body">
                        <h5 class="room-title"><?= htmlspecialchars($room['name']); ?></h5>
                        
                        <div class="room-meta">
                            <span><i class="fa-solid fa-tag"></i> <?= ucfirst($room['type']); ?></span>
                            <span>•</span>
                            <span><i class="fa-solid fa-users"></i> <?= $room['capacity']; ?> orang</span>
                        </div>
                        
                        <p class="room-description">
                            <?= htmlspecialchars(substr($room['description'],0,100)); ?>...
                        </p>
                        
                        <div class="room-price">
                            <?= formatRupiah($room['price']); ?> / malam
                        </div>
                        
                        <a href="user/reserve.php?room_id=<?= $room['id']; ?>"
                           class="btn btn-gold w-100">
                           <i class="fa-solid fa-calendar-check me-2"></i>Pesan Sekarang
                        </a>
                    </div>
                </div>
            </div>
            <?php 
                endwhile;
            else:
            ?>
            <div class="text-center w-100 py-5">
                <p class="text-muted">Belum ada kamar tersedia</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <button class="carousel-btn carousel-btn-next" onclick="moveCarousel(1)">
        <i class="fa-solid fa-chevron-right"></i>
    </button>
</div>

<!-- CAROUSEL DOTS -->
<div class="carousel-dots" id="carouselDots"></div>

<!-- VIEW ALL BUTTON -->
<div class="text-center">
    <a href="user/rooms.php" class="btn-view-all">
        <i class="fa-solid fa-grid me-2"></i>Lihat Semua Kamar
    </a>
</div>

</div>
</section>
<!-- ================= CARA RESERVASI ================= -->
<section class="py-5">
<div class="container wrap">
<div class="text-center mb-5 reveal">
    <h2 class="fw-bold">Cara Reservasi</h2>
    <p class="text-muted">Mudah & cepat dalam 5 langkah</p>
</div>

<div class="row g-4">
    <div class="col-md-4 reveal" style="transition-delay:0s">
        <div class="step-card">
            <div class="step-number">1</div>
            <div class="step-icon">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h5 class="fw-bold mb-3">Login / Register</h5>
            <p class="text-muted">Buat akun baru atau login dengan akun yang sudah ada</p>
        </div>
    </div>
    
    <div class="col-md-4 reveal" style="transition-delay:0.1s">
        <div class="step-card">
            <div class="step-number">2</div>
            <div class="step-icon">
                <i class="fa-solid fa-bed"></i>
            </div>
            <h5 class="fw-bold mb-3">Pilih Kamar</h5>
            <p class="text-muted">Pilih kamar sesuai kebutuhan dan preferensi Anda</p>
        </div>
    </div>
    
    <div class="col-md-4 reveal" style="transition-delay:0.2s">
        <div class="step-card">
            <div class="step-number">3</div>
            <div class="step-icon">
                <i class="fa-solid fa-calendar-check"></i>
            </div>
            <h5 class="fw-bold mb-3">Isi Detail</h5>
            <p class="text-muted">Tentukan tanggal check-in, check-out, dan jumlah tamu</p>
        </div>
    </div>
    
    <div class="col-md-4 reveal" style="transition-delay:0.3s">
        <div class="step-card">
            <div class="step-number">4</div>
            <div class="step-icon">
                <i class="fa-solid fa-check-circle"></i>
            </div>
            <h5 class="fw-bold mb-3">Konfirmasi</h5>
            <p class="text-muted">Review detail reservasi dan konfirmasi pemesanan</p>
        </div>
    </div>
    
    <div class="col-md-4 reveal" style="transition-delay:0.4s">
        <div class="step-card">
            <div class="step-number">5</div>
            <div class="step-icon">
                <i class="fa-solid fa-door-open"></i>
            </div>
            <h5 class="fw-bold mb-3">Check-in Online</h5>
            <p class="text-muted">Lakukan check-in mandiri melalui dashboard</p>
        </div>
    </div>
    
    <div class="col-md-4 reveal" style="transition-delay:0.5s">
        <div class="step-card">
            <div class="step-number">✓</div>
            <div class="step-icon">
                <i class="fa-solid fa-star"></i>
            </div>
            <h5 class="fw-bold mb-3">Nikmati Menginap</h5>
            <p class="text-muted">Selamat menikmati pengalaman menginap di Hotel Delitha</p>
        </div>
    </div>
</div>
</div>
</section>
<!-- ================= CTA ================= -->
<section class="py-5">
<div class="container wrap reveal">
<div class="cta-final">
<?php if(isset($_SESSION['user_id'])): ?>
    <h2 class="fw-bold mb-3">Kelola Reservasi Anda</h2>
    <p class="text-muted mb-4">
        Akses dashboard untuk check-in, pembatalan,
        dan riwayat pemesanan.
    </p>
    <?php if (isAdmin()): ?>
    <a href="admin/dashboard.php" class="btn btn-gold btn-lg">
        <i class="fa-solid fa-gauge me-2"></i>Buka Dashboard
    </a>
<?php else: ?>
    <a href="user/dashboard.php" class="btn btn-gold btn-lg">
        <i class="fa-solid fa-gauge me-2"></i>Buka Dashboard
    </a>
<?php endif; ?>
<?php else: ?>
    <h2 class="fw-bold mb-3">Siap Menginap di Hotel Delitha?</h2>
    <p class="text-muted mb-4">
        Login untuk mengelola reservasi dan check-in online.
    </p>
    <a href="auth/login.php" class="btn btn-gold btn-lg me-2">
        <i class="fa-solid fa-sign-in me-2"></i>Login
    </a>
    <a href="auth/register.php" class="btn btn-outline-light btn-lg">
        <i class="fa-solid fa-user-plus me-2"></i>Daftar
    </a>
<?php endif; ?>
</div>
</div>
</section>

<script>
/* PARALLAX HERO */
window.addEventListener('scroll',()=>{
    document.documentElement.style.setProperty(
        '--scroll',
        window.scrollY * .15 + 'px'
    );
});

/* REVEAL */
const reveal=document.querySelectorAll('.reveal');
const obs=new IntersectionObserver(e=>{
    e.forEach(x=>{
        if(x.isIntersecting){
            x.target.classList.add('show');
        }
    });
},{threshold:.15});
reveal.forEach(el=>obs.observe(el));

/* CAROUSEL */
let currentSlide = 0;
const track = document.getElementById('roomsTrack');
const slides = document.querySelectorAll('.room-slide');
const totalSlides = slides.length;

// Calculate slides per view based on screen size
function getSlidesPerView() {
    if (window.innerWidth < 768) return 1;
    if (window.innerWidth < 992) return 2;
    return 3;
}

let slidesPerView = getSlidesPerView();
const maxSlide = Math.max(0, totalSlides - slidesPerView);

// Create dots
function createDots() {
    const dotsContainer = document.getElementById('carouselDots');
    dotsContainer.innerHTML = '';
    
    for(let i = 0; i <= maxSlide; i++) {
        const dot = document.createElement('button');
        dot.className = 'carousel-dot';
        if(i === 0) dot.classList.add('active');
        dot.onclick = () => goToSlide(i);
        dotsContainer.appendChild(dot);
    }
}

// Update dots
function updateDots() {
    const dots = document.querySelectorAll('.carousel-dot');
    dots.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlide);
    });
}

// Move carousel
function moveCarousel(direction) {
    currentSlide += direction;
    
    if(currentSlide < 0) currentSlide = maxSlide;
    if(currentSlide > maxSlide) currentSlide = 0;
    
    updateCarousel();
}

// Go to specific slide
function goToSlide(index) {
    currentSlide = index;
    updateCarousel();
}

// Update carousel position
function updateCarousel() {
    const slideWidth = slides[0]?.offsetWidth || 0;
    const gap = 20; // Total gap (10px each side)
    const offset = -(currentSlide * (slideWidth + gap));
    track.style.transform = `translateX(${offset}px)`;
    updateDots();
}

// Handle window resize
window.addEventListener('resize', () => {
    const newSlidesPerView = getSlidesPerView();
    if(newSlidesPerView !== slidesPerView) {
        slidesPerView = newSlidesPerView;
        currentSlide = 0;
        createDots();
        updateCarousel();
    }
});

// Initialize
if(totalSlides > 0) {
    createDots();
    updateCarousel();
    
    // Auto slide every 5 seconds
    setInterval(() => {
        moveCarousel(1);
    }, 5000);
}
</script>

<?php include 'includes/footer.php'; ?>