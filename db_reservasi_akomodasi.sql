-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:8889
-- Waktu pembuatan: 30 Des 2025 pada 09.45
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
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `admin_logs`
--
ALTER TABLE `admin_logs`
  ADD CONSTRAINT `admin_logs_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
