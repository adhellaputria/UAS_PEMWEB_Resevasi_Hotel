-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Waktu pembuatan: 30 Des 2025 pada 11.32
-- Versi server: 8.0.40
-- Versi PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_reservasi_akomodasi`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `admin_logs`
--

CREATE TABLE `admin_logs` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `admin_logs`
--

INSERT INTO `admin_logs` (`id`, `admin_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'UPDATE ROOM', 'Mengupdate kamar: Aurora Superior', '::1', '2025-12-29 10:19:55'),
(2, 1, 'TOGGLE USER STATUS', 'Mengubah status user \\\"Rani\\\" menjadi inactive', '::1', '2025-12-29 10:20:05'),
(3, 1, 'UPDATE BLOG', 'Mengupdate artikel: Fasilitas Lengkap Hotel Delitha untuk Pengalaman Menginap Lebih Nyaman', '::1', '2025-12-29 10:20:45'),
(4, 1, 'APPROVE RESERVATION', 'Menyetujui reservasi BK202512297205 - 1 kamar dari Imperial President Suite', '::1', '2025-12-29 10:27:15'),
(5, 1, 'APPROVE RESERVATION', 'Menyetujui reservasi BK202512292369 - 2 kamar dari Aurora Superior', '::1', '2025-12-29 10:27:29'),
(6, 1, 'APPROVE RESERVATION', 'Menyetujui reservasi BK202512298583 - 1 kamar dari Celestia Deluxe', '::1', '2025-12-29 10:27:34'),
(7, 1, 'approve_cancellation', 'Menyetujui pembatalan booking BK202512298583 dengan refund Rp 1.260.000', '::1', '2025-12-29 10:28:40'),
(8, 1, 'CHECK IN', 'Check-in reservasi ID 1 - Kamar: Imperial President Suite', '::1', '2025-12-29 15:47:26'),
(9, 1, 'reject_cancellation', 'Menolak pembatalan booking BK202512292369', '::1', '2025-12-29 15:57:28'),
(10, 1, 'ADMIN CANCEL RESERVATION', 'Membatalkan reservasi BK202512299993 (2 kamar) - refund 100%', '::1', '2025-12-29 15:59:40'),
(11, 1, 'CHECK OUT', 'Check-out reservasi BK202512297205 - Mengembalikan 1 kamar Imperial President Suite', '::1', '2025-12-30 08:47:41'),
(12, 1, 'CHECK IN', 'Check-in reservasi ID 2 - Kamar: Aurora Superior', '::1', '2025-12-30 08:47:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `blogs`
--

CREATE TABLE `blogs` (
  `id` int NOT NULL,
  `title` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `slug` varchar(200) COLLATE utf8mb4_general_ci NOT NULL,
  `category` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `content` text COLLATE utf8mb4_general_ci NOT NULL,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `author_id` int NOT NULL,
  `views` int DEFAULT '0',
  `published` tinyint(1) DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `blogs`
--

INSERT INTO `blogs` (`id`, `title`, `slug`, `category`, `content`, `image`, `author_id`, `views`, `published`, `created_at`, `updated_at`) VALUES
(1, 'Tips Liburan Nyaman dan Seru Saat Berkunjung ke Kota Solo', 'tips-liburan-nyaman-dan-seru-saat-berkunjung-ke-kota-solo', 'tips', 'Solo merupakan kota yang kaya akan budaya, kuliner, dan destinasi wisata menarik. Agar perjalanan Anda semakin nyaman, pastikan untuk memilih penginapan yang strategis dan mudah dijangkau seperti Hotel Delitha.\r\n\r\nBeberapa tips saat berkunjung ke Solo antara lain adalah mengunjungi tempat wisata di pagi atau sore hari, mencicipi kuliner khas seperti serabi dan nasi liwet, serta memanfaatkan transportasi lokal untuk menjelajahi kota dengan lebih fleksibel.\r\n\r\nDengan perencanaan yang tepat dan akomodasi yang nyaman, liburan di Solo akan menjadi pengalaman yang menyenangkan dan tak terlupakan.', 'uploads/blogs/69505cfdeb68e_1766874365.jpg', 1, 1, 1, '2025-12-27 22:26:05', '2025-12-27 22:41:55'),
(2, 'Fasilitas Lengkap Hotel Delitha untuk Pengalaman Menginap Lebih Nyaman', 'fasilitas-lengkap-hotel-delitha-untuk-pengalaman-menginap-lebih-nyaman', 'facility', 'Hotel Delitha menghadirkan berbagai fasilitas unggulan yang dirancang khusus untuk memberikan kenyamanan maksimal bagi setiap tamu. Mulai dari kamar yang bersih, nyaman, dan berdesain estetik, hingga akses WiFi gratis yang stabil untuk menunjang aktivitas kerja maupun hiburan selama menginap.\r\n\r\nTak hanya itu, Hotel Delitha juga menyediakan layanan kamar yang responsif, area parkir yang aman, serta fasilitas pendukung lainnya yang dirawat secara rutin demi menjaga kualitas dan kebersihan. Setiap detail diperhatikan dengan seksama agar tamu dapat menikmati suasana menginap yang tenang dan menyenangkan.\r\n\r\nSebagai bentuk komitmen terhadap kepuasan tamu, Hotel Delitha terus melakukan peningkatan fasilitas dan pelayanan secara berkelanjutan. Dengan fasilitas yang lengkap, pelayanan yang ramah, dan lokasi yang strategis di Kota Solo, Hotel Delitha menjadi pilihan tepat bagi Anda yang mengutamakan kenyamanan, kualitas, dan pengalaman menginap yang berkesan.', 'uploads/blogs/69505dd663cc9_1766874582.jpg', 1, 2, 1, '2025-12-27 22:29:42', '2025-12-29 10:20:45'),
(3, 'Mengenal Hotel Delitha: Perpaduan Kenyamanan, Estetika, dan Pelayanan Terbaik di Solo', 'mengenal-hotel-delitha-perpaduan-kenyamanan-estetika-dan-pelayanan-terbaik-di-solo', 'hotel', 'Hotel Delitha hadir sebagai pilihan akomodasi yang mengutamakan kenyamanan, keindahan, dan pelayanan berkualitas di Kota Solo. Dengan desain interior yang estetik dan suasana yang tenang, Hotel Delitha cocok untuk wisatawan maupun tamu bisnis yang ingin beristirahat dengan nyaman setelah beraktivitas seharian.\r\n\r\nSetiap kamar di Hotel Delitha dirancang dengan konsep modern dan fungsional, dilengkapi fasilitas lengkap untuk menunjang kebutuhan tamu. Lokasinya yang strategis juga memudahkan akses ke berbagai destinasi wisata, pusat kuliner, dan area bisnis di Solo.\r\n\r\nKami percaya bahwa pengalaman menginap yang berkesan dimulai dari kenyamanan kecil. Oleh karena itu, Hotel Delitha selalu berkomitmen memberikan layanan terbaik agar setiap tamu merasa seperti di rumah sendiri.', 'uploads/blogs/69505e2aa68a1_1766874666.jpg', 1, 2, 1, '2025-12-27 22:30:10', '2025-12-27 22:42:09'),
(4, 'Pelayanan Ramah dan Profesional, Prioritas Utama Hotel Delitha', 'pelayanan-ramah-dan-profesional-prioritas-utama-hotel-delitha', 'service', 'Pelayanan merupakan salah satu nilai utama yang dijunjung tinggi di Hotel Delitha. Seluruh staf kami dilatih untuk memberikan layanan yang ramah, cepat, dan profesional kepada setiap tamu tanpa terkecuali.\r\n\r\nMulai dari proses check-in yang mudah, bantuan informasi wisata, hingga pelayanan kamar yang responsif, Hotel Delitha berusaha memastikan setiap kebutuhan tamu terpenuhi dengan baik. Kepuasan dan kenyamanan tamu menjadi fokus utama dalam setiap layanan yang kami berikan.\r\n\r\nDengan pelayanan yang tulus dan penuh perhatian, Hotel Delitha ingin menciptakan pengalaman menginap yang menyenangkan dan berkesan bagi setiap pengunjung.', 'uploads/blogs/69505e899b307_1766874761.jpg', 1, 1, 1, '2025-12-27 22:32:41', '2025-12-27 22:41:50');

-- --------------------------------------------------------

--
-- Struktur dari tabel `cancellations`
--

CREATE TABLE `cancellations` (
  `id` int NOT NULL,
  `reservation_id` int NOT NULL,
  `reason` text COLLATE utf8mb4_general_ci NOT NULL,
  `refund_amount` decimal(10,2) DEFAULT '0.00',
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_general_ci DEFAULT 'pending',
  `admin_notes` text COLLATE utf8mb4_general_ci,
  `processed_by` int DEFAULT NULL,
  `processed_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `cancellations`
--

INSERT INTO `cancellations` (`id`, `reservation_id`, `reason`, `refund_amount`, `status`, `admin_notes`, `processed_by`, `processed_at`, `created_at`) VALUES
(1, 3, 'Masalah jadwal / waktu', 1260000.00, 'approved', 'Pengajuan pembatalan disetujui. Refund sebesar 70% telah diproses sesuai kebijakan hotel.', 1, '2025-12-29 17:28:40', '2025-12-29 17:27:52'),
(2, 2, 'Masalah jadwal / waktu', 1680000.00, 'rejected', 'Pengajuan pembatalan belum dapat disetujui sesuai kebijakan hotel. Reservasi masih aktif dan dapat digunakan sesuai jadwal.', 1, '2025-12-29 22:57:28', '2025-12-29 22:55:45'),
(3, 4, 'Dibatalkan oleh admin', 3600000.00, 'approved', 'Silakan hubungi resepsionis untuk informasi lebih lanjut.', 1, '2025-12-29 22:59:40', '2025-12-29 22:59:40');

-- --------------------------------------------------------

--
-- Struktur dari tabel `if0_40792239_db_reservasi_akomodasi`
--

CREATE TABLE `if0_40792239_db_reservasi_akomodasi` (
  `id` int NOT NULL,
  `admin_id` int NOT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `if0_40792239_db_reservasi_akomodasi`
--

INSERT INTO `if0_40792239_db_reservasi_akomodasi` (`id`, `admin_id`, `action`, `description`, `ip_address`, `created_at`) VALUES
(1, 1, 'UPDATE ROOM', 'Mengupdate kamar: Aurora Superior', '::1', '2025-12-29 10:19:55'),
(2, 1, 'TOGGLE USER STATUS', 'Mengubah status user \\\"Rani\\\" menjadi inactive', '::1', '2025-12-29 10:20:05'),
(3, 1, 'UPDATE BLOG', 'Mengupdate artikel: Fasilitas Lengkap Hotel Delitha untuk Pengalaman Menginap Lebih Nyaman', '::1', '2025-12-29 10:20:45'),
(4, 1, 'APPROVE RESERVATION', 'Menyetujui reservasi BK202512297205 - 1 kamar dari Imperial President Suite', '::1', '2025-12-29 10:27:15'),
(5, 1, 'APPROVE RESERVATION', 'Menyetujui reservasi BK202512292369 - 2 kamar dari Aurora Superior', '::1', '2025-12-29 10:27:29'),
(6, 1, 'APPROVE RESERVATION', 'Menyetujui reservasi BK202512298583 - 1 kamar dari Celestia Deluxe', '::1', '2025-12-29 10:27:34'),
(7, 1, 'approve_cancellation', 'Menyetujui pembatalan booking BK202512298583 dengan refund Rp 1.260.000', '::1', '2025-12-29 10:28:40'),
(8, 1, 'CHECK IN', 'Check-in reservasi ID 1 - Kamar: Imperial President Suite', '::1', '2025-12-29 15:47:26'),
(9, 1, 'reject_cancellation', 'Menolak pembatalan booking BK202512292369', '::1', '2025-12-29 15:57:28'),
(10, 1, 'ADMIN CANCEL RESERVATION', 'Membatalkan reservasi BK202512299993 (2 kamar) - refund 100%', '::1', '2025-12-29 15:59:40'),
(11, 1, 'CHECK OUT', 'Check-out reservasi BK202512297205 - Mengembalikan 1 kamar Imperial President Suite', '::1', '2025-12-30 08:47:41'),
(12, 1, 'CHECK IN', 'Check-in reservasi ID 2 - Kamar: Aurora Superior', '::1', '2025-12-30 08:47:46');

-- --------------------------------------------------------

--
-- Struktur dari tabel `payments`
--

CREATE TABLE `payments` (
  `id` int NOT NULL,
  `reservation_id` int DEFAULT NULL,
  `booking_code` varchar(50) DEFAULT NULL,
  `amount` decimal(12,2) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data untuk tabel `payments`
--

INSERT INTO `payments` (`id`, `reservation_id`, `booking_code`, `amount`, `created_at`) VALUES
(1, 1, 'BK202512297205', 5500000.00, '2025-12-29 17:22:58'),
(2, 2, 'BK202512292369', 2400000.00, '2025-12-29 17:26:08'),
(3, 3, 'BK202512298583', 1800000.00, '2025-12-29 17:26:42'),
(4, 3, 'BK202512298583', -1260000.00, '2025-12-29 17:28:40'),
(5, 4, 'BK202512299993', 3600000.00, '2025-12-29 22:59:08'),
(6, 4, 'BK202512299993', -3600000.00, '2025-12-29 22:59:40'),
(7, 5, 'BK202512308322', 2500000.00, '2025-12-30 14:22:45');

-- --------------------------------------------------------

--
-- Struktur dari tabel `reservations`
--

CREATE TABLE `reservations` (
  `id` int NOT NULL,
  `booking_code` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `user_id` int NOT NULL,
  `room_id` int NOT NULL,
  `guest_name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `guest_email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `guest_phone` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `check_in_date` date NOT NULL,
  `check_out_date` date NOT NULL,
  `total_rooms` int NOT NULL,
  `total_guests` int NOT NULL,
  `total_nights` int NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','checked_in','checked_out','cancelled') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'pending',
  `checked_in_at` timestamp NULL DEFAULT NULL,
  `checked_out_at` datetime DEFAULT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `payment_status` enum('unpaid','paid','refunded') COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unpaid'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `reservations`
--

INSERT INTO `reservations` (`id`, `booking_code`, `user_id`, `room_id`, `guest_name`, `guest_email`, `guest_phone`, `check_in_date`, `check_out_date`, `total_rooms`, `total_guests`, `total_nights`, `total_price`, `status`, `checked_in_at`, `checked_out_at`, `notes`, `created_at`, `updated_at`, `payment_status`) VALUES
(1, 'BK202512297205', 3, 2, 'Della Hadid', 'della@user.com', '08882372692', '2025-12-29', '2025-12-30', 1, 4, 1, 5500000.00, 'checked_out', '2025-12-29 15:47:26', '2025-12-30 15:47:41', '', '2025-12-29 10:22:58', '2025-12-30 08:47:41', 'paid'),
(2, 'BK202512292369', 3, 3, 'Rania', 'rania@gmail.com', '089876543456', '2025-12-30', '2025-12-31', 2, 4, 1, 2400000.00, 'checked_in', '2025-12-30 08:47:46', NULL, '', '2025-12-29 10:26:08', '2025-12-30 08:47:46', 'paid'),
(3, 'BK202512298583', 3, 4, 'lala', 'lala@user.com', '089765239212', '2025-12-29', '2025-12-30', 1, 1, 1, 1800000.00, 'cancelled', NULL, NULL, '', '2025-12-29 10:26:42', '2025-12-29 10:28:40', 'refunded'),
(4, 'BK202512299993', 3, 4, 'Hadid', 'della@user.com', '089765434568', '2025-12-29', '2025-12-30', 2, 4, 1, 3600000.00, 'cancelled', NULL, NULL, '', '2025-12-29 15:59:08', '2025-12-29 15:59:40', 'refunded'),
(5, 'BK202512308322', 3, 1, 'Della Hadid', 'della@user.com', '08987634567', '2025-12-30', '2025-12-31', 1, 1, 1, 2500000.00, 'pending', NULL, NULL, '', '2025-12-30 07:22:45', '2025-12-30 07:22:45', 'paid');

-- --------------------------------------------------------

--
-- Struktur dari tabel `rooms`
--

CREATE TABLE `rooms` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `capacity` int NOT NULL,
  `total_rooms` int NOT NULL,
  `available_rooms` int NOT NULL,
  `facilities` text COLLATE utf8mb4_general_ci,
  `description` text COLLATE utf8mb4_general_ci,
  `image` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` enum('available','maintenance') COLLATE utf8mb4_general_ci DEFAULT 'available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `rooms`
--

INSERT INTO `rooms` (`id`, `name`, `type`, `price`, `capacity`, `total_rooms`, `available_rooms`, `facilities`, `description`, `image`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Harmonia Family', 'Family Room', 2500000.00, 10, 10, 10, 'AC, WiFi, Smart TV, Air Panas, Kamar Mandi Dalam, Tempat Tidur King &amp;amp;amp; Single, Meja Makan, Lemari Es', 'Kamar Family dirancang untuk kenyamanan keluarga, dengan ruang luas dan suasana hangat. Ideal untuk menginap bersama orang tercinta.', 'room_1766873913_69505b3913c11.jpg', 'available', '2025-12-27 21:54:09', '2025-12-28 16:02:14'),
(2, 'Imperial President Suite', 'President Suite', 5500000.00, 5, 10, 10, 'AC, WiFi, Smart TV, Air Panas, Kamar Mandi Dalam, Jacuzzi, Ruang Tamu, Mini Bar, Balkon Pribadi, Brankas', 'Kamar paling eksklusif dengan desain elegan dan pelayanan premium. Memberikan pengalaman menginap kelas atas layaknya hotel bintang lima internasional.', 'room_1766873290_695058cac9474.jpg', 'available', '2025-12-27 21:55:02', '2025-12-30 08:47:41'),
(3, 'Aurora Superior', 'Superior Room', 1200000.00, 2, 30, 29, 'AC, WiFi, Smart TV, Air Panas, Kamar Mandi Dalam, Meja Kerja, Lemari Pakaian', 'Kamar Superior dengan desain estetik modern, cocok untuk tamu yang menginginkan kenyamanan dan ketenangan. Dilengkapi pencahayaan hangat dan interior minimalis yang elegan.', 'room_1766873733_69505a850bab7.jpg', 'available', '2025-12-27 22:05:20', '2025-12-29 15:53:29'),
(4, 'Celestia Deluxe', 'Deluxe Room', 1800000.00, 2, 30, 30, 'AC, WiFi, Smart TV, Air Panas, Kamar Mandi Dalam, Mini Bar, Sofa, Balkon', 'Kamar Deluxe dengan ruang lebih luas dan nuansa mewah. Cocok untuk pasangan atau tamu bisnis yang menginginkan kenyamanan ekstra.', 'room_1766873873_69505b11e43b2.jpg', 'available', '2025-12-27 22:17:53', '2025-12-29 15:53:29');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `phone` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `gender` enum('male','female') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `birth_date` date DEFAULT NULL,
  `address` text COLLATE utf8mb4_general_ci,
  `role` enum('admin','staff','user') COLLATE utf8mb4_general_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `status` enum('active','inactive') COLLATE utf8mb4_general_ci DEFAULT 'active',
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `name`, `email`, `password`, `phone`, `gender`, `birth_date`, `address`, `role`, `created_at`, `updated_at`, `status`, `photo`) VALUES
(1, 'Admin Della', 'della@gmail.com', '$2y$10$FyWIjGCxusaDEJJdunOlIeUbT1AacIwPAGt005kCoxTLRFb7VexEe', '098765456789', 'female', '2004-12-07', 'surakarta', 'admin', '2025-12-17 11:15:05', '2025-12-28 08:39:44', 'active', NULL),
(2, 'Admin Ditha', 'ditha@gmail.com', '$2y$10$bjGh0d7yKtR7DIblwSifruj0po2zO6hwtHANFcd/LLHSfYJWUa2WC', '081234567890', NULL, NULL, NULL, 'admin', '2025-12-17 12:34:38', '2025-12-27 20:58:06', 'active', NULL),
(3, 'Della Hadid', 'della@user.com', '$2y$10$8fWfezNzroccezygnWMpq.34dQXJoAjvJSAWL6Q3j8WQEwVbifVXa', '081234567422', 'female', '2008-12-20', 'Semarang', 'user', '2025-12-17 12:34:38', '2025-12-28 08:39:25', 'active', 'uploads/profiles/user/user_3_1766911165.jpg'),
(4, 'Ditha', 'ditha@user.com', '$2y$10$27ytRrFMsNi2phe0Fq7LrufEz1Z9zA/oiolrP4kHg3/Rg/K9tGGUK', '081234567892', NULL, NULL, NULL, 'user', '2025-12-17 12:34:38', '2025-12-29 08:51:50', 'active', NULL),
(5, 'Rani', 'rani@staff.com', '$2y$10$LcHcToSP83RqFuwM5UsSUuQ.kIFKuTKgW66ig322fTsygHhuY0yFS', NULL, NULL, NULL, NULL, 'staff', '2025-12-28 16:08:57', '2025-12-29 10:20:05', 'inactive', NULL);

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indeks untuk tabel `blogs`
--
ALTER TABLE `blogs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `author_id` (`author_id`),
  ADD KEY `idx_blogs_category` (`category`),
  ADD KEY `idx_blogs_published` (`published`);

--
-- Indeks untuk tabel `cancellations`
--
ALTER TABLE `cancellations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reservation_id` (`reservation_id`),
  ADD KEY `processed_by` (`processed_by`);

--
-- Indeks untuk tabel `if0_40792239_db_reservasi_akomodasi`
--
ALTER TABLE `if0_40792239_db_reservasi_akomodasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indeks untuk tabel `payments`
--
ALTER TABLE `payments`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_code` (`booking_code`),
  ADD KEY `idx_reservations_user` (`user_id`),
  ADD KEY `idx_reservations_room` (`room_id`),
  ADD KEY `idx_reservations_status` (`status`),
  ADD KEY `idx_reservations_dates` (`check_in_date`,`check_out_date`);

--
-- Indeks untuk tabel `rooms`
--
ALTER TABLE `rooms`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `blogs`
--
ALTER TABLE `blogs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `cancellations`
--
ALTER TABLE `cancellations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `if0_40792239_db_reservasi_akomodasi`
--
ALTER TABLE `if0_40792239_db_reservasi_akomodasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT untuk tabel `payments`
--
ALTER TABLE `payments`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT untuk tabel `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `rooms`
--
ALTER TABLE `rooms`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `blogs`
--
ALTER TABLE `blogs`
  ADD CONSTRAINT `blogs_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `cancellations`
--
ALTER TABLE `cancellations`
  ADD CONSTRAINT `cancellations_ibfk_1` FOREIGN KEY (`reservation_id`) REFERENCES `reservations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cancellations_ibfk_2` FOREIGN KEY (`processed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
