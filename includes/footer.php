<?php
// footer.php
?>

<style>
/* ================= RESET ================= */
html, body {
    margin: 0;
    padding: 0;
    width: 100%;
    overflow-x: hidden;
}

/* ================= FOOTER ================= */
.footer-premium {
    width: 100vw;
    position: relative;
    left: 50%;
    right: 50%;
    margin-left: -50vw;
    margin-right: -50vw;
    background: linear-gradient(135deg, #0b1020, #0f172a);
    color: #e5e7eb;
    margin-top: 120px;
}

/* WRAPPER BIAR MIRIP HEADER */
.footer-wrapper {
    max-width: 1400px;
    margin: auto;
    padding: 80px 80px 40px;
}

/* BRAND */
.footer-brand {
    font-size: 2rem;
    font-weight: 700;
    color: #d4af37;
    display: flex;
    align-items: center;
    gap: 10px;
}

/* TEXT */
.footer-description {
    margin-top: 16px;
    line-height: 1.8;
    color: #cbd5f5;
}

/* SECTION */
.footer-section-title {
    color: #d4af37;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 20px;
    letter-spacing: 1px;
}

/* LINKS */
.footer-link {
    display: block;
    color: #e5e7eb;
    text-decoration: none;
    margin-bottom: 12px;
    transition: 0.3s;
    cursor: pointer;
}

.footer-link:hover {
    color: #d4af37;
    transform: translateX(4px);
}

/* SOCIAL */
.social-links-premium {
    margin-top: 25px;
    display: flex;
    gap: 10px;
}

.social-link-premium {
    display: inline-flex;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    border: 1px solid #d4af37;
    align-items: center;
    justify-content: center;
    color: #d4af37;
    transition: 0.3s;
    text-decoration: none;
}

.social-link-premium:hover {
    background: #d4af37;
    color: #0f172a;
}

/* CONTACT */
.contact-info-item {
    display: flex;
    gap: 12px;
    margin-bottom: 16px;
    line-height: 1.6;
    align-items: flex-start;
}

.contact-info-item i {
    color: #d4af37;
    margin-top: 4px;
    flex-shrink: 0;
}

/* BOTTOM */
.footer-bottom {
    border-top: 1px solid rgba(255,255,255,.1);
    padding: 25px 0;
    text-align: center;
    color: #9ca3af;
    font-size: .9rem;
}

/* ================= RESPONSIVE ================= */
@media (max-width: 992px) {
    .footer-wrapper {
        padding: 60px 40px 30px;
    }
    
    .footer-brand {
        font-size: 1.75rem;
    }
    
    .footer-section-title {
        font-size: 1rem;
        margin-bottom: 15px;
    }
}

@media (max-width: 768px) {
    .footer-wrapper {
        padding: 50px 30px 25px;
    }
    
    .footer-brand {
        justify-content: center;
        font-size: 1.5rem;
    }
    
    .footer-description {
        text-align: center;
        font-size: 0.95rem;
    }
    
    .social-links-premium {
        justify-content: center;
    }
    
    .footer-section-title {
        text-align: center;
        margin-top: 30px;
    }
    
    .footer-link {
        text-align: center;
    }
    
    .contact-info-item {
        justify-content: center;
        text-align: left;
    }
    
    .row.g-5 {
        row-gap: 2rem !important;
    }
}

@media (max-width: 576px) {
    .footer-wrapper {
        padding: 40px 20px 20px;
    }
    
    .footer-brand {
        font-size: 1.3rem;
        gap: 8px;
    }
    
    .footer-brand i {
        font-size: 1.2rem;
    }
    
    .footer-description {
        font-size: 0.9rem;
        margin-top: 12px;
    }
    
    .social-link-premium {
        width: 38px;
        height: 38px;
    }
    
    .footer-section-title {
        font-size: 0.95rem;
        margin-top: 25px;
        margin-bottom: 12px;
    }
    
    .footer-link {
        font-size: 0.9rem;
        margin-bottom: 10px;
    }
    
    .contact-info-item {
        font-size: 0.9rem;
        margin-bottom: 14px;
    }
    
    .footer-bottom {
        font-size: 0.85rem;
        padding: 20px 15px;
    }
}
</style>

<footer class="footer-premium">
    <div class="footer-wrapper">
        <div class="row g-5">

            <div class="col-lg-3 col-md-6">
                <div class="footer-brand">
                    <i class="fas fa-gem"></i> HOTEL DELITHA
                </div>
                <p class="footer-description">
                    Pengalaman menginap mewah di jantung Kota Surakarta
                    dengan pelayanan premium dan fasilitas terbaik.
                </p>

                <div class="social-links-premium">
                    <a href="https://www.facebook.com/soloparagonhotel" target="_blank" class="social-link-premium">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://www.instagram.com/soloparagon" target="_blank" class="social-link-premium">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="https://twitter.com/SoloParagonhtl" target="_blank" class="social-link-premium">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://wa.me/6282198924088" target="_blank" class="social-link-premium">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5 class="footer-section-title">NAVIGASI</h5>
                <a href="<?= BASE_URL ?>/user/rooms.php" class="footer-link">Kamar</a>
                <a href="<?= BASE_URL ?>/user/reserve.php" class="footer-link">
                    Reservasi
                </a>

                <a href="<?= BASE_URL ?>/blog.php" class="footer-link">
                    Blog & Artikel
                </a>

                <a href="<?= BASE_URL ?>/user/checkin.php" class="footer-link">
                    Check-in Online
                </a>

                <a href="<?= BASE_URL ?>/index.php#about" class="footer-link">
                    Tentang Kami
                </a>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5 class="footer-section-title">LAYANAN</h5>
                <a href="#" class="footer-link">Spa & Wellness</a>
                <a href="#" class="footer-link">Restaurant & Bar</a>
                <a href="#" class="footer-link">Kolam Renang</a>
                <a href="#" class="footer-link">Fitness Center</a>
                <a href="#" class="footer-link">Meeting Room</a>
            </div>

            <div class="col-lg-4 col-md-6">
                <h5 class="footer-section-title">HUBUNGI KAMI</h5>

                <div class="contact-info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>Jl. Dr. Sutomo, Surakarta, Jawa Tengah</span>
                </div>

                <div class="contact-info-item">
                    <i class="fas fa-phone"></i>
                    <span>+62 821-9892-4088</span>
                </div>

                <div class="contact-info-item">
                    <i class="fas fa-envelope"></i>
                    <span>info@delithahotel.com</span>
                </div>

                <div class="contact-info-item">
                    <i class="fas fa-clock"></i>
                    <span>Layanan 24/7</span>
                </div>
            </div>

        </div>
    </div>

    <div class="footer-bottom">
        © 2025 <strong style="color:#d4af37">Hotel Delitha</strong>. Crafted with ❤️ in Surakarta
    </div>
</footer>