<?php
require_once 'config/database.php';

// Get published blogs only
$blogs = mysqli_query($conn, "
    SELECT id, title, slug, category, content, image, views, created_at
    FROM blogs
    WHERE published = 1
    ORDER BY created_at DESC
");

$total_blogs = mysqli_num_rows($blogs);

$page_title = 'Blog Hotel Delitha';
include 'includes/header.php';
?>

<style>
:root{
    --navy:#0b1220;
    --gold:#d4af37;
    --blue:#60a5fa;
    --green:#22c55e;
    --purple:#a855f7;
    --text:#374151;
    --muted:#6b7280;
}

/* ===== PAGE ===== */
.wrap{max-width:1100px;margin:auto;padding:0 1rem}
.section{margin:5rem 0}

/* ===== HERO ===== */
.hero{
    padding:6rem 1.5rem;
    border-radius:42px;
    text-align:center;
    color:#fff;

    background:
        radial-gradient(60% 40% at 80% 0%, rgba(212,175,55,.35), transparent 60%),
        radial-gradient(40% 30% at 10% 100%, rgba(212,175,55,.22), transparent 65%),
        linear-gradient(135deg,#0b1220,#111827);

    box-shadow:
        0 40px 90px rgba(0,0,0,.45),
        inset 0 0 0 1px rgba(255,255,255,.04);

    animation: fadeInUp .8s ease;
}
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to{
        opacity:1;
        transform:none;
    }
}

.hero-badge{
    font-size:.7rem;
    letter-spacing:.15em;
    text-transform:uppercase;
    background:rgba(255,255,255,.15);
    padding:.35rem .9rem;
    border-radius:999px;
    display:inline-block;
}
.hero h1{
    font-size:2.6rem;
    font-weight:800;
    margin:1.4rem 0;
}
.hero p{
    max-width:720px;
    margin:auto;
    line-height:1.9;
    color:#e5e7eb;
}
.btn-primary{
    display:inline-block;
    margin-top:2.4rem;
    padding:.95rem 2.4rem;
    border-radius:999px;
    background:linear-gradient(135deg,#fde68a,#d4af37);
    color:#111827;
    font-weight:800;
    box-shadow:0 15px 40px rgba(212,175,55,.45);
    text-decoration:none;
    transition: all .3s ease;
}
.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow:0 20px 50px rgba(212,175,55,.55);
}

/* ===== ARTICLES ===== */
.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:3.2rem;
}
.article{
    opacity:0;
    transform:translateY(40px);
    transition:.7s ease;
    background:#fff;
    border-radius:20px;
    padding:1.5rem;
    box-shadow:0 4px 20px rgba(0,0,0,.06);
}
.article:hover {
    transform: translateY(-8px);
    box-shadow:0 12px 35px rgba(0,0,0,.12);
}
.article.show{opacity:1;transform:none}
.article.show:hover{transform: translateY(-8px);}

.article-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 1rem;
}

.article-no-image {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    border-radius: 12px;
    margin-bottom: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 3rem;
}

.article span{
    font-size:.7rem;
    letter-spacing:.12em;
    text-transform:uppercase;
    color:var(--muted);
    display: block;
    margin-bottom: 0.5rem;
}
.article h3{
    font-size:1.25rem;
    font-weight:700;
    margin:.5rem 0;
    color: var(--navy);
}
.article-excerpt{
    line-height:1.85;
    color:var(--text);
    margin: 0.75rem 0;
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.article-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 1rem;
    font-size: 0.85rem;
    color: var(--muted);
}

.article-meta i {
    margin-right: 0.25rem;
}

.read{
    margin-top:.6rem;
    display:inline-block;
    font-weight:700;
    cursor:pointer;
    color:var(--gold);
    transition: all .3s ease;
}
.read:hover {
    color: var(--navy);
    transform: translateX(5px);
}

/* ===== MODAL ===== */
.modal{
    position:fixed;
    inset:0;
    background:rgba(15,23,42,.8);
    backdrop-filter:blur(8px);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:9999;
    padding: 2rem;
}
.modal.show{display:flex}
.modal-box{
    width:90%;
    max-width:800px;
    max-height:85vh;
    background:#fff;
    border-radius:32px;
    overflow:hidden;
    display:flex;
    flex-direction:column;
    animation:pop .4s ease;
}
@keyframes pop{
    from{opacity:0;transform:scale(.92)}
    to{opacity:1;transform:scale(1)}
}
.modal-img{
    width:100%;
    height:300px;
    object-fit:cover;
}
.modal-body{
    padding:2.4rem;
    overflow-y:auto;
}
.modal-category {
    display: inline-block;
    padding: 0.4rem 0.75rem;
    border-radius: 8px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 1rem;
}
.modal-body h3{
    font-size:1.8rem;
    font-weight:800;
    color:var(--navy);
    margin-bottom: 1rem;
}
.modal-meta {
    display: flex;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #f3f4f6;
    font-size: 0.9rem;
    color: var(--muted);
}
.modal-meta i {
    margin-right: 0.5rem;
    color: var(--gold);
}
.modal-body p{
    margin-top:1rem;
    white-space:pre-line;
    line-height:1.9;
    color:var(--text);
    font-size: 1.05rem;
}
.modal-close{
    position:absolute;
    top:18px;
    right:22px;
    width: 40px;
    height: 40px;
    font-size:1.8rem;
    background:rgba(0,0,0,0.5);
    backdrop-filter: blur(8px);
    border:none;
    border-radius: 50%;
    color:#fff;
    cursor:pointer;
    transition: all .3s ease;
}
.modal-close:hover {
    background:rgba(0,0,0,0.8);
    transform: rotate(90deg);
}

/* ===== CATEGORY COLOR ===== */
.hotel{border-top:6px solid var(--gold)}
.service{border-top:6px solid var(--blue)}
.facility{border-top:6px solid var(--green)}
.tips{border-top:6px solid var(--purple)}

.cat-hotel {background: #fef3c7; color: #92400e;}
.cat-service {background: #dbeafe; color: #1e40af;}
.cat-facility {background: #dcfce7; color: #166534;}
.cat-tips {background: #f3e8ff; color: #6b21a8;}

/* ===== FACILITIES SECTION (NEW!) ===== */
.facilities-section{
    background: linear-gradient(135deg, #f8fafc 0%, #e8eef5 100%);
    border-radius: 42px;
    padding: 5rem 2rem;
    margin: 5rem 0;
    box-shadow: 0 20px 60px rgba(0,0,0,.08);
    animation: fadeInUp .8s ease;
}

.facilities-header{
    text-align: center;
    margin-bottom: 4rem;
}

.facilities-badge{
    display: inline-block;
    background: linear-gradient(135deg, #fde68a, #d4af37);
    color: #422006;
    font-size: .75rem;
    font-weight: 800;
    letter-spacing: .15em;
    text-transform: uppercase;
    padding: .5rem 1.2rem;
    border-radius: 999px;
    margin-bottom: 1rem;
}

.facilities-header h2{
    font-size: 2.5rem;
    font-weight: 800;
    color: var(--navy);
    margin-bottom: 1rem;
}

.facilities-header p{
    color: var(--muted);
    font-size: 1.1rem;
    max-width: 600px;
    margin: 0 auto;
    line-height: 1.8;
}

.facilities-grid{
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 2rem;
    max-width: 1000px;
    margin: 0 auto 3rem;
}

.facility-card{
    background: #fff;
    border-radius: 20px;
    padding: 2.5rem 2rem;
    text-align: center;
    transition: all .4s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 4px 20px rgba(0,0,0,.06);
    position: relative;
    overflow: hidden;
}

.facility-card::before{
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #d4af37, #fde68a);
    transform: scaleX(0);
    transition: transform .4s ease;
}

.facility-card:hover::before{
    transform: scaleX(1);
}

.facility-card:hover{
    transform: translateY(-10px);
    box-shadow: 0 20px 40px rgba(212,175,55,.25);
}

.facility-icon{
    width: 70px;
    height: 70px;
    margin: 0 auto 1.5rem;
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all .4s ease;
}

.facility-card:hover .facility-icon{
    transform: scale(1.1) rotate(5deg);
    background: linear-gradient(135deg, #fde68a, #d4af37);
}

.facility-icon i{
    font-size: 2rem;
    color: #92400e;
}

.facility-card h4{
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: .75rem;
}

.facility-card p{
    color: var(--muted);
    font-size: .9rem;
    line-height: 1.6;
    margin: 0;
}

.facilities-cta{
    text-align: center;
    margin-top: 3rem;
}

.btn-reserve{
    display: inline-block;
    background: linear-gradient(135deg, var(--navy), #1e293b);
    color: #fff;
    padding: 1rem 2.5rem;
    border-radius: 999px;
    font-weight: 800;
    text-decoration: none;
    transition: all .3s ease;
    box-shadow: 0 10px 30px rgba(11,18,32,.4);
}

.btn-reserve:hover{
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(11,18,32,.5);
    background: linear-gradient(135deg, #1e293b, var(--navy));
    color: #fff;
}

/* ===== EMPTY STATE ===== */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--muted);
}
.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.3;
}

/* ===== MOBILE ===== */
@media(max-width:968px){
    .facilities-grid{
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
}

@media(max-width:768px){
    .grid{grid-template-columns:1fr;gap:2rem}
    .hero{padding:4rem 1.5rem}
    .hero h1{font-size:2.1rem}
    .modal{padding:1rem}
    .modal-box{border-radius:24px}
    .modal-img{height:200px}
    .modal-body{padding:1.5rem}
    .modal-body h3{font-size:1.4rem}
    
    .facilities-section{
        padding: 3rem 1.5rem;
        border-radius: 28px;
    }
    
    .facilities-header h2{
        font-size: 1.8rem;
    }
    
    .facilities-grid{
        grid-template-columns: 1fr;
        gap: 1.25rem;
    }
    
    .facility-card{
        padding: 2rem 1.5rem;
    }
}
</style>

<div class="container my-5">
<div class="wrap">

<!-- HERO -->
<section class="hero">
    <span class="hero-badge">Blog Hotel Delitha</span>
    <h1>Mengenal Hotel Delitha Lebih Dekat</h1>
    <p>
        Cerita, panduan, dan wawasan seputar Hotel Delitha untuk membantu
        Anda memahami pengalaman menginap sebelum melakukan reservasi.
    </p>
    <a href="<?= BASE_URL ?>/user/reserve.php" class="btn-primary">
        <i class="fa-solid fa-calendar-check"></i> Mulai Reservasi
    </a>
</section>

<!-- ARTICLES -->
<section class="section">
    <?php if ($total_blogs > 0): ?>
        <div class="grid">
            <?php while ($blog = mysqli_fetch_assoc($blogs)): 
                // Extract excerpt (first 150 characters)
                $excerpt = strip_tags($blog['content']);
                $excerpt = substr($excerpt, 0, 150) . '...';
                
                // Category names
                $category_names = [
                    'hotel' => 'Tentang Hotel',
                    'service' => 'Pelayanan',
                    'facility' => 'Fasilitas',
                    'tips' => 'Tips Menginap'
                ];
            ?>
            <article class="article <?= $blog['category'] ?>">
                <?php if ($blog['image']): ?>
                    <img src="<?= BASE_URL . '/' . $blog['image'] ?>"
                         class="article-image"
                         alt="<?= htmlspecialchars($blog['title']) ?>">
                <?php else: ?>
                    <div class="article-no-image">
                        <i class="fa-solid fa-image"></i>
                    </div>
                <?php endif; ?>
                
                <span><?= $category_names[$blog['category']] ?? ucfirst($blog['category']) ?></span>
                <h3><?= htmlspecialchars($blog['title']) ?></h3>
                <p class="article-excerpt"><?= htmlspecialchars($excerpt) ?></p>
                
                <div class="article-meta">
                    <span><i class="fa-solid fa-eye"></i><?= number_format($blog['views']) ?> views</span>
                    <span><i class="fa-solid fa-calendar"></i><?= date('d M Y', strtotime($blog['created_at'])) ?></span>
                </div>
                
                <div class="read" onclick="openModal(
                    '<?= $blog['category'] ?>',
                    '<?= htmlspecialchars($blog['title'], ENT_QUOTES) ?>',
                    `<?= htmlspecialchars($blog['content'], ENT_QUOTES) ?>`,
                    '<?= $blog['image'] ? BASE_URL . '/' . $blog['image'] : '' ?>',
                    <?= $blog['id'] ?>,
                    '<?= date('d M Y', strtotime($blog['created_at'])) ?>',
                    <?= $blog['views'] ?>
                )">
                    Baca selengkapnya <i class="fa-solid fa-arrow-right"></i>
                </div>
            </article>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-inbox"></i>
            <h3>Belum Ada Artikel</h3>
            <p>Artikel blog akan segera hadir. Silakan cek kembali nanti!</p>
        </div>
    <?php endif; ?>
</section>

<!-- ✨ FACILITIES SECTION (NEW!) ✨ -->
<section class="facilities-section">
    <div class="facilities-header">
        <span class="facilities-badge">Fasilitas Premium</span>
        <h2>Kenyamanan Tak Tertandingi</h2>
        <p>Nikmati berbagai fasilitas kelas dunia yang dirancang untuk memberikan pengalaman menginap yang tak terlupakan</p>
    </div>
    
    <div class="facilities-grid">
        <div class="facility-card">
            <div class="facility-icon">
                <i class="fa-solid fa-spa"></i>
            </div>
            <h4>Spa & Wellness</h4>
            <p>Relaksasi sempurna dengan perawatan spa profesional dan fasilitas wellness terlengkap</p>
        </div>
        
        <div class="facility-card">
            <div class="facility-icon">
                <i class="fa-solid fa-utensils"></i>
            </div>
            <h4>Restaurant & Bar</h4>
            <p>Sajian kuliner internasional dan lokal yang memanjakan lidah Anda</p>
        </div>
        
        <div class="facility-card">
            <div class="facility-icon">
                <i class="fa-solid fa-person-swimming"></i>
            </div>
            <h4>Kolam Renang</h4>
            <p>Kolam renang outdoor dengan pemandangan menakjubkan untuk bersantai</p>
        </div>
        
        <div class="facility-card">
            <div class="facility-icon">
                <i class="fa-solid fa-dumbbell"></i>
            </div>
            <h4>Fitness Center</h4>
            <p>Pusat kebugaran modern dengan peralatan lengkap untuk menjaga stamina Anda</p>
        </div>
        
        <div class="facility-card">
            <div class="facility-icon">
                <i class="fa-solid fa-users"></i>
            </div>
            <h4>Meeting Room</h4>
            <p>Ruang pertemuan profesional dengan teknologi konferensi terkini</p>
        </div>
        
        <div class="facility-card">
            <div class="facility-icon">
                <i class="fa-solid fa-wifi"></i>
            </div>
            <h4>High-Speed WiFi</h4>
            <p>Internet berkecepatan tinggi gratis di seluruh area hotel</p>
        </div>
    </div>
    
    <div class="facilities-cta">
        <a href="<?= BASE_URL ?>/user/reserve.php" class="btn-reserve">
            <i class="fa-solid fa-calendar-check"></i> Pesan Kamar Sekarang
        </a>
    </div>
</section>

</div>
</div>

<!-- MODAL -->
<div class="modal" id="modal">
    <div class="modal-box" id="modalBox">
        <button class="modal-close" onclick="closeModal()">×</button>
        <img id="modalImg" class="modal-img">
        <div class="modal-body">
            <span id="modalCategory" class="modal-category"></span>
            <h3 id="modalTitle"></h3>
            <div class="modal-meta">
                <span><i class="fa-solid fa-calendar"></i><span id="modalDate"></span></span>
                <span><i class="fa-solid fa-eye"></i><span id="modalViews"></span> views</span>
            </div>
            <p id="modalText"></p>
        </div>
    </div>
</div>

<script>
/* REVEAL ANIMATION */
const items = document.querySelectorAll('.article');
const obs = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('show');
        }
    });
}, {threshold: .2});
items.forEach(item => obs.observe(item));

/* MODAL */
function openModal(category, title, text, img, blogId, date, views) {
    const modal = document.getElementById('modal');
    const box = document.getElementById('modalBox');
    
    box.className = 'modal-box ' + category;
    modal.classList.add('show');
    
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalText').innerText = text;
    document.getElementById('modalDate').innerText = date;
    document.getElementById('modalViews').innerText = views;
    
    const modalImg = document.getElementById('modalImg');
    if (img) {
        modalImg.src = img;
        modalImg.style.display = 'block';
    } else {
        modalImg.style.display = 'none';
    }
    
    const categoryNames = {
        'hotel': 'Tentang Hotel',
        'service': 'Pelayanan',
        'facility': 'Fasilitas',
        'tips': 'Tips Menginap'
    };
    const categoryBadge = document.getElementById('modalCategory');
    categoryBadge.innerText = categoryNames[category] || category;
    categoryBadge.className = 'modal-category cat-' + category;
    
    const BASE_URL = '<?= BASE_URL ?>';
    fetch(BASE_URL + "/admin/blog/view.php?id=" + blogId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.views) {
                document.getElementById('modalViews').innerText = data.views;
            }
        })
        .catch(error => console.log('View counter error:', error));
}

function closeModal() {
    document.getElementById('modal').classList.remove('show');
}

document.getElementById('modal').onclick = e => {
    if (e.target.id === 'modal') closeModal();
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php include 'includes/footer.php'; ?>