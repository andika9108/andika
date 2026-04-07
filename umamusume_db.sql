-- phpMyAdmin SQL Dump
-- version 4.7.4
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: 07 Apr 2026 pada 08.31
-- Versi Server: 10.1.30-MariaDB
-- PHP Version: 5.6.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `umamusume_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `stok` int(11) NOT NULL,
  `harga` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `products`
--

INSERT INTO `products` (`id`, `nama_produk`, `stok`, `harga`) VALUES
(1, 'Genshin - 60 Genesis Crystals', 193, 16000),
(2, 'Genshin - Blessing of the Welkin Moon', 489, 79000),
(3, 'Genshin - 1980+260 Genesis Crystals', 49, 479000),
(4, 'HSR - 60 Oneiric Shards', 149, 16000),
(5, 'HSR - Express Supply Pass', 300, 79000),
(6, 'HSR - 1980+260 Oneiric Shards', 80, 479000),
(7, 'Uma - 50 Jewels', 1000, 15000),
(8, 'Uma - Daily Jewel Pack', 800, 75000),
(9, 'Uma - 1500 Jewels (Paid)', 100, 450000),
(10, 'MLBB - 5 Diamonds', 10000, 2000),
(11, 'MLBB - Weekly Diamond Pass', 1998, 29000),
(12, 'MLBB - Starlight Member', 500, 149000),
(13, 'MLBB - 966+149 Diamonds', 200, 299000),
(14, 'FF - 50 Diamonds', 5000, 8000),
(15, 'FF - Weekly Membership', 1000, 39000),
(18, 'Free Fire - 50 diamond', 1, 60);

-- --------------------------------------------------------

--
-- Struktur dari tabel `trainers`
--

CREATE TABLE `trainers` (
  `id` int(11) NOT NULL,
  `id_trainer` varchar(50) NOT NULL,
  `password` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `trainers`
--

INSERT INTO `trainers` (`id`, `id_trainer`, `password`) VALUES
(1, 'Teio', 'e2e764283eba8771088072e5718db23a');

-- --------------------------------------------------------

--
-- Struktur dari tabel `transactions`
--

CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `pembeli` varchar(50) NOT NULL,
  `nama_produk` varchar(100) NOT NULL,
  `jumlah` int(11) NOT NULL,
  `total_harga` int(11) NOT NULL,
  `tanggal` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `customer_name` varchar(100) DEFAULT 'Guest',
  `customer_wa` varchar(20) DEFAULT '-'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `transactions`
--

INSERT INTO `transactions` (`id`, `pembeli`, `nama_produk`, `jumlah`, `total_harga`, `tanggal`, `customer_name`, `customer_wa`) VALUES
(1, 'trainer', 'HSR - 60 Oneiric Shards', 1, 16000, '2026-04-07 05:11:32', 'dika', '083820294192');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(32) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `username`, `password`) VALUES
(1, 'trainer', '482c811da5d5b4bc6d497ffa98491e38');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trainers`
--
ALTER TABLE `trainers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transactions`
--
ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `trainers`
--
ALTER TABLE `trainers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `transactions`
--
ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
