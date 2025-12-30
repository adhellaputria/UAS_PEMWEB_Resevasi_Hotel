<?php
require_once '../config/database.php';
include '../includes/header.php';

/* =====================
   AMBIL DATA KAMAR (USER)
   HANYA YANG AVAILABLE
===================== */
$rooms = mysqli_query($conn, "
    SELECT *
    FROM rooms
    WHERE status = 'available'
    ORDER BY created_at DESC
");
?>
<style>
:root{
    --navy:#0b1220;
    --gold:#d4af37;
    --gold-dark:#b8941f;
    --blue:#60a5fa;
    --text:#374151;
    --muted:#6b7280;
}

/* ===== GLOBAL ===== */
body{background:#f8fafc}

/* ===== HERO SECTION ===== */
.rooms-hero{
    padding:6rem 1.5rem 4rem;
    border-radius:42px;
    background:
        radial-gradient(circle at top right, rgba(212,175,55,.45), transparent 45%),
        radial-gradient(circle at bottom left, rgba(96,165,250,.35), transparent 50%),
        linear-gradient(135deg,#0b1220,#111827);
    color:#fff;
    text-align:center;
    margin-bottom:4rem;
    animation:fadeInDown .8s ease;
}

.rooms-hero-badge{
    font-size:.7rem;
    letter-spacing:.15em;
    text-transform:uppercase;
    background:rgba(255,255,255,.15);
    padding:.35rem .9rem;
    border-radius:999px;
    display:inline-block;
    margin-bottom:1rem;
}

.rooms-hero h1{
    font-size:2.8rem;
    font-weight:800;
    margin-bottom:1rem;
}

.rooms-hero p{
    max-width:650px;
    margin:auto;
    line-height:1.9;
    color:#e5e7eb;
    font-size:1.05rem;
}

/* ===== ROOM CARD ===== */
.room-card {
    border-radius:24px;
    overflow:hidden;
    background:#fff;
    box-shadow:0 8px 30px rgba(0,0,0,.08);
    transition:all .4s cubic-bezier(0.4, 0, 0.2, 1);
    animation:fadeUp .7s ease forwards;
    opacity:0;
    height:100%;
    display:flex;
    flex-direction:column;
}

.room-card:hover {
    transform:translateY(-12px);
    box-shadow:0 20px 45px rgba(0,0,0,.15);
}

/* ===== IMAGE WRAPPER ===== */
.room-img-wrapper{
    position:relative;
    overflow:hidden;
    height:260px;
    background:linear-gradient(135deg, #e5e7eb, #d1d5db);
}

.room-img {
    width:100%;
    height:100%;
    object-fit:cover;
    transition:transform .5s ease;
}

.room-card:hover .room-img{
    transform:scale(1.08);
}

.room-img-placeholder{
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:4rem;
    color:#9ca3af;
    background:linear-gradient(135deg, #f3f4f6, #e5e7eb);
}

/* ===== BADGE ===== */
.badge-available {
    position:absolute;
    top:1rem;
    right:1rem;
    background:linear-gradient(135deg, #16a34a, #15803d);
    color:#fff;
    padding:.5rem 1rem;
    border-radius:999px;
    font-weight:700;
    font-size:.85rem;
    box-shadow:0 4px 12px rgba(22,163,74,.4);
    z-index:10;
}

/* ===== ROOM BODY ===== */
.room-body{
    padding:1.75rem;
    flex-grow:1;
    display:flex;
    flex-direction:column;
}

.room-title{
    font-size:1.4rem;
    font-weight:700;
    color:var(--navy);
    margin-bottom:.75rem;
}

.room-meta{
    display:flex;
    gap:.75rem;
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

/* ===== FOOTER ===== */
.room-footer{
    display:flex;
    justify-content:space-between;
    align-items:center;
    padding-top:1rem;
    border-top:2px solid #f1f5f9;
}

.room-price-wrapper{
    flex-grow:1;
}

.room-price-label{
    font-size:.75rem;
    color:#94a3b8;
    font-weight:500;
}

.room-price {
    font-weight:800;
    color:var(--gold-dark);
    font-size:1.5rem;
}

.btn-detail{
    background:linear-gradient(135deg, var(--gold), var(--gold-dark));
    color:var(--navy);
    border:none;
    border-radius:999px;
    padding:.7rem 1.75rem;
    font-weight:700;
    transition:.3s;
    box-shadow:0 4px 15px rgba(212,175,55,.3);
    text-decoration:none;
    display:inline-block;
    cursor:pointer;
}

.btn-detail:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 25px rgba(212,175,55,.45);
    color:var(--navy);
}

.rooms-available{
    display:flex;
    align-items:center;
    gap:.5rem;
    color:#64748b;
    font-size:.9rem;
    margin-top:.5rem;
}

.rooms-available i{
    color:#16a34a;
}

/* ===== EMPTY STATE ===== */
.empty-state{
    text-align:center;
    padding:5rem 2rem;
    color:#94a3b8;
}

.empty-state i{
    font-size:5rem;
    margin-bottom:1.5rem;
    opacity:.3;
}

.empty-state h3{
    font-weight:700;
    color:var(--navy);
    margin-bottom:.5rem;
}

/* ===== MODAL ===== */
.modal-room{
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
.modal-room.show{display:flex}

.modal-room-box{
    width:90%;
    max-width:900px;
    max-height:85vh;
    background:#fff;
    border-radius:32px;
    overflow:hidden;
    display:flex;
    flex-direction:column;
    animation:pop .4s ease;
    position:relative;
}

@keyframes pop{
    from{opacity:0;transform:scale(.92)}
    to{opacity:1;transform:scale(1)}
}

.modal-room-img{
    width:100%;
    height:200px;
    object-fit:cover;
    cursor:pointer;
    transition:transform .3s ease;
}

.modal-room-img:hover{
    transform:scale(1.02);
}

.modal-room-img-placeholder{
    width:100%;
    height:200px;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:3rem;
    color:#9ca3af;
    background:linear-gradient(135deg, #f3f4f6, #e5e7eb);
}

/* ===== IMAGE POPUP ===== */
.image-popup{
    position:fixed;
    inset:0;
    background:rgba(0,0,0,.95);
    backdrop-filter:blur(10px);
    display:none;
    align-items:center;
    justify-content:center;
    z-index:99999;
    padding:2rem;
}

.image-popup.show{display:flex}

.image-popup-img{
    max-width:90%;
    max-height:90vh;
    border-radius:12px;
    box-shadow:0 20px 60px rgba(0,0,0,.8);
    animation:zoomIn .3s ease;
}

@keyframes zoomIn{
    from{opacity:0;transform:scale(.8)}
    to{opacity:1;transform:scale(1)}
}

.image-popup-close{
    position:absolute;
    top:2rem;
    right:2rem;
    width:50px;
    height:50px;
    font-size:2rem;
    background:rgba(255,255,255,.1);
    backdrop-filter:blur(8px);
    border:2px solid rgba(255,255,255,.2);
    border-radius:50%;
    color:#fff;
    cursor:pointer;
    transition:all .3s ease;
}

.image-popup-close:hover{
    background:rgba(255,255,255,.2);
    transform:rotate(90deg);
}

.modal-room-body{
    padding:2.5rem;
    overflow-y:auto;
}

.modal-room-header{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-bottom:1.5rem;
}

.modal-room-title{
    font-size:2rem;
    font-weight:800;
    color:var(--navy);
    margin-bottom:.5rem;
}

.modal-room-type{
    display:inline-block;
    padding:.4rem .9rem;
    border-radius:8px;
    font-size:.8rem;
    font-weight:600;
    background:#fef3c7;
    color:#92400e;
}

.modal-room-price-big{
    text-align:right;
}

.modal-room-price-label{
    font-size:.85rem;
    color:#94a3b8;
    margin-bottom:.25rem;
}

.modal-room-price-value{
    font-size:2rem;
    font-weight:800;
    color:var(--gold-dark);
}

.modal-room-meta{
    display:flex;
    gap:2rem;
    padding:1.5rem 0;
    border-top:2px solid #f1f5f9;
    border-bottom:2px solid #f1f5f9;
    margin-bottom:1.5rem;
}

.modal-room-meta-item{
    display:flex;
    align-items:center;
    gap:.75rem;
    color:#64748b;
    font-size:.95rem;
}

.modal-room-meta-item i{
    color:var(--gold);
    font-size:1.25rem;
}

.modal-room-meta-item strong{
    color:var(--navy);
}

.modal-room-description{
    margin-bottom:2rem;
}

.modal-room-description h4{
    font-size:1.1rem;
    font-weight:700;
    color:var(--navy);
    margin-bottom:1rem;
}

.modal-room-description p{
    line-height:1.9;
    color:var(--text);
    white-space:pre-line;
}

.modal-room-facilities{
    margin-bottom:2rem;
}

.modal-room-facilities h4{
    font-size:1.1rem;
    font-weight:700;
    color:var(--navy);
    margin-bottom:1rem;
}

.facilities-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(200px, 1fr));
    gap:1rem;
}

.facility-item{
    display:flex;
    align-items:center;
    gap:.75rem;
    padding:.75rem 1rem;
    background:#f8fafc;
    border-radius:12px;
    color:#64748b;
}

.facility-item i{
    color:var(--gold);
    font-size:1.1rem;
}

.modal-room-actions{
    display:flex;
    gap:1rem;
    padding-top:1.5rem;
    border-top:2px solid #f1f5f9;
}

.btn-reserve{
    flex:1;
    padding:1rem 2rem;
    border-radius:999px;
    background:linear-gradient(135deg, var(--gold), var(--gold-dark));
    color:var(--navy);
    font-weight:700;
    font-size:1.05rem;
    border:none;
    cursor:pointer;
    transition:.3s;
    text-decoration:none;
    text-align:center;
}

.btn-reserve:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 25px rgba(212,175,55,.45);
}

.modal-room-close{
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
    z-index:10;
}

.modal-room-close:hover {
    background:rgba(0,0,0,0.8);
    transform: rotate(90deg);
}

/* ===== ANIMATIONS ===== */
@keyframes fadeUp {
    from {
        opacity:0;
        transform:translateY(40px);
    }
    to {
        opacity:1;
        transform:translateY(0);
    }
}

@keyframes fadeInDown{
    from{
        opacity:0;
        transform:translateY(-30px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

/* ===== RESPONSIVE ===== */
@media(max-width: 768px){
    .rooms-hero{
        padding:4rem 1.5rem 3rem;
    }
    
    .rooms-hero h1{
        font-size:2rem;
    }
    
    .room-img-wrapper{
        height:220px;
    }
    
    .modal-room{
        padding:1rem;
    }
    
    .modal-room-box{
        border-radius:24px;
    }
    
    .modal-room-img,
    .modal-room-img-placeholder{
        height:180px;
    }
    
    .modal-room-body{
        padding:1.5rem;
    }
    
    .modal-room-title{
        font-size:1.5rem;
    }
    
    .modal-room-header{
        flex-direction:column;
        gap:1rem;
    }
    
    .modal-room-price-big{
        text-align:left;
    }
    
    .modal-room-meta{
        flex-direction:column;
        gap:1rem;
    }
    
    .facilities-grid{
        grid-template-columns:1fr;
    }
}
</style>

<div class="container my-5">
    <!-- HERO -->
    <div class="rooms-hero">
        <span class="rooms-hero-badge">Pilihan Kamar</span>
        <h1>Kamar Hotel Delitha</h1>
        <p>
            Temukan kamar yang sempurna untuk pengalaman menginap Anda.
            Semua kamar dilengkapi fasilitas modern dan kenyamanan maksimal.
        </p>
    </div>

    <!-- ROOMS GRID -->
    <div class="row g-4">
    <?php 
    $delay = 0;
    if (mysqli_num_rows($rooms) > 0):
        while ($r = mysqli_fetch_assoc($rooms)): 
    ?>
        <div class="col-md-6 col-lg-4" style="animation-delay: <?= $delay ?>ms">
            <div class="room-card">
                <div class="room-img-wrapper">
                    <?php if(!empty($r['image']) && file_exists('../uploads/rooms/'.$r['image'])): ?>
                        <img 
                            src="../uploads/rooms/<?= htmlspecialchars($r['image']); ?>" 
                            class="room-img"
                            alt="<?= htmlspecialchars($r['name']); ?>"
                        >
                    <?php else: ?>
                        <div class="room-img-placeholder">
                            <i class="fa-solid fa-bed"></i>
                        </div>
                    <?php endif; ?>
                    <span class="badge-available">
                        <i class="fa-solid fa-check me-1"></i>Available
                    </span>
                </div>
                
                <div class="room-body">
                    <h5 class="room-title"><?= htmlspecialchars($r['name']); ?></h5>
                    
                    <div class="room-meta">
                        <span>
                            <i class="fa-solid fa-tag"></i>
                            <?= ucfirst($r['type']); ?>
                        </span>
                        <span>•</span>
                        <span>
                            <i class="fa-solid fa-users"></i>
                            <?= $r['capacity']; ?> orang
                        </span>
                    </div>
                    
                    <p class="room-description">
                        <?= htmlspecialchars(substr($r['description'], 0, 120)); ?>...
                    </p>
                    
                    <div class="room-footer">
                        <div class="room-price-wrapper">
                            <div class="room-price-label">Mulai dari</div>
                            <div class="room-price">
                                Rp <?= number_format($r['price'], 0, ',', '.'); ?>
                            </div>
                            <div class="rooms-available">
                                <i class="fa-solid fa-door-open"></i>
                                <span><?= $r['available_rooms']; ?> kamar tersedia</span>
                            </div>
                        </div>
                        <button 
                            class="btn-detail"
                            onclick="openRoomModal(
                                '<?= htmlspecialchars($r['name'], ENT_QUOTES); ?>',
                                '<?= ucfirst($r['type']); ?>',
                                <?= $r['capacity']; ?>,
                                <?= $r['price']; ?>,
                                <?= $r['available_rooms']; ?>,
                                `<?= htmlspecialchars($r['description'], ENT_QUOTES); ?>`,
                                `<?= !empty($r['facilities']) ? htmlspecialchars($r['facilities'], ENT_QUOTES) : ''; ?>`,
                                '<?= !empty($r['image']) ? '../uploads/rooms/'.htmlspecialchars($r['image']) : ''; ?>',
                                <?= $r['id']; ?>
                            )"
                        >
                            Detail
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php 
        $delay += 120;
        endwhile;
    else:
    ?>
        <div class="col-12">
            <div class="empty-state">
                <i class="fa-solid fa-bed-empty"></i>
                <h3>Belum Ada Kamar Tersedia</h3>
                <p>Silakan cek kembali nanti untuk melihat pilihan kamar kami</p>
            </div>
        </div>
    <?php endif; ?>
    </div>
</div>

<!-- MODAL -->
<div class="modal-room" id="modalRoom">
    <div class="modal-room-box">
        <button class="modal-room-close" onclick="closeRoomModal()">×</button>
        
        <img id="modalRoomImg" class="modal-room-img" style="display:none;" onclick="openImagePopup(this.src)" title="Klik untuk memperbesar">
        <div id="modalRoomImgPlaceholder" class="modal-room-img-placeholder" style="display:none;">
            <i class="fa-solid fa-bed"></i>
        </div>
        
        <div class="modal-room-body">
            <div class="modal-room-header">
                <div>
                    <h3 class="modal-room-title" id="modalRoomTitle"></h3>
                    <span class="modal-room-type" id="modalRoomType"></span>
                </div>
                <div class="modal-room-price-big">
                    <div class="modal-room-price-label">Harga per malam</div>
                    <div class="modal-room-price-value" id="modalRoomPrice"></div>
                </div>
            </div>
            
            <div class="modal-room-meta">
                <div class="modal-room-meta-item">
                    <i class="fa-solid fa-users"></i>
                    <span>Kapasitas: <strong id="modalRoomCapacity"></strong></span>
                </div>
                <div class="modal-room-meta-item">
                    <i class="fa-solid fa-door-open"></i>
                    <span><strong id="modalRoomAvailable"></strong> kamar tersedia</span>
                </div>
                <div class="modal-room-meta-item">
                    <i class="fa-solid fa-check-circle"></i>
                    <span><strong>Available</strong></span>
                </div>
            </div>
            
            <div class="modal-room-description">
                <h4><i class="fa-solid fa-align-left"></i> Deskripsi</h4>
                <p id="modalRoomDescription"></p>
            </div>
            
            <div class="modal-room-facilities" id="modalRoomFacilitiesWrapper" style="display:none;">
                <h4><i class="fa-solid fa-star"></i> Fasilitas</h4>
                <div class="facilities-grid" id="modalRoomFacilities"></div>
            </div>
            
            <div class="modal-room-actions">
                <a href="reserve.php" class="btn-reserve" id="btnReserveRoom">
                    <i class="fa-solid fa-calendar-check"></i> Reservasi Sekarang
                </a>
            </div>
        </div>
    </div>
</div>

<!-- IMAGE POPUP -->
<div class="image-popup" id="imagePopup">
    <button class="image-popup-close" onclick="closeImagePopup()">×</button>
    <img id="imagePopupImg" class="image-popup-img" alt="Room Image">
</div>

<script>
/* MODAL FUNCTIONS */
function openRoomModal(name, type, capacity, price, available, description, facilities, image, roomId) {
    const modal = document.getElementById('modalRoom');
    
    // Set content
    document.getElementById('modalRoomTitle').innerText = name;
    document.getElementById('modalRoomType').innerText = type;
    document.getElementById('modalRoomCapacity').innerText = capacity + ' orang';
    document.getElementById('modalRoomPrice').innerText = 'Rp ' + price.toLocaleString('id-ID');
    document.getElementById('modalRoomAvailable').innerText = available;
    document.getElementById('modalRoomDescription').innerText = description;
    
    // Set image
    const modalImg = document.getElementById('modalRoomImg');
    const modalImgPlaceholder = document.getElementById('modalRoomImgPlaceholder');
    
    if (image) {
        modalImg.src = image;
        modalImg.style.display = 'block';
        modalImgPlaceholder.style.display = 'none';
    } else {
        modalImg.style.display = 'none';
        modalImgPlaceholder.style.display = 'flex';
    }
    
    // Set facilities
    const facilitiesWrapper = document.getElementById('modalRoomFacilitiesWrapper');
    const facilitiesGrid = document.getElementById('modalRoomFacilities');
    
    if (facilities && facilities.trim() !== '') {
        const facilityList = facilities.split(',').map(f => f.trim()).filter(f => f);
        
        if (facilityList.length > 0) {
            facilitiesGrid.innerHTML = facilityList.map(facility => `
                <div class="facility-item">
                    <i class="fa-solid fa-check"></i>
                    <span>${facility}</span>
                </div>
            `).join('');
            facilitiesWrapper.style.display = 'block';
        } else {
            facilitiesWrapper.style.display = 'none';
        }
    } else {
        facilitiesWrapper.style.display = 'none';
    }
    
    // Update reserve button with room ID
    const btnReserve = document.getElementById('btnReserveRoom');
    btnReserve.href = 'reserve.php?room_id=' + roomId;
    
    // Show modal
    modal.classList.add('show');
}

function closeRoomModal() {
    document.getElementById('modalRoom').classList.remove('show');
}

// Close on outside click
document.getElementById('modalRoom').onclick = e => {
    if (e.target.id === 'modalRoom') closeRoomModal();
}

// Close on ESC key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeRoomModal();
        closeImagePopup();
    }
});

/* IMAGE POPUP FUNCTIONS */
function openImagePopup(imageSrc) {
    const popup = document.getElementById('imagePopup');
    const img = document.getElementById('imagePopupImg');
    img.src = imageSrc;
    popup.classList.add('show');
}

function closeImagePopup() {
    document.getElementById('imagePopup').classList.remove('show');
}

// Close image popup on outside click
document.getElementById('imagePopup').onclick = e => {
    if (e.target.id === 'imagePopup') closeImagePopup();
}
</script>

<?php include '../includes/footer.php'; ?>