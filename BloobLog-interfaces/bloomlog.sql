-- phpMyAdmin SQL Dump
-- version 5.1.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Nov 03, 2025 at 08:00 PM
-- Server version: 5.7.24
-- PHP Version: 8.2.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `bloomlog`
--

-- --------------------------------------------------------

--
-- Table structure for table `plantcatalog`
--

CREATE TABLE `plantcatalog` (
  `plantid` int(11) NOT NULL,
  `plantName` varchar(100) NOT NULL,
  `wateringfrequency` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `plant_Info` varchar(255) NOT NULL,
  `mintemperature` decimal(4,2) DEFAULT NULL,
  `maxtemperature` decimal(4,2) DEFAULT NULL,
  `minhumidity` decimal(4,2) DEFAULT NULL,
  `maxhumidity` decimal(4,2) DEFAULT NULL,
  `plant_summary` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `plantcatalog`
--

INSERT INTO `plantcatalog` (`plantid`, `plantName`, `wateringfrequency`, `image_path`, `plant_Info`, `mintemperature`, `maxtemperature`, `minhumidity`, `maxhumidity`, `plant_summary`) VALUES
(1, 'Snake Plant', 14, 'image/snake.JPG', 'Light: Tolerates low to bright indirect light.\nWater: Allow soil to dry completely between waterings.\nSoil: Well-draining potting mix recommended.', '18.00', '30.00', '40.00', '60.00', 'Hardy plant that requires minimal care'),
(2, 'Pothos', 7, 'image/pothos.JPG', 'Light: Prefers bright indirect sunlight.\nWater: Water when top inch of soil is dry.\nSoil: Standard potting soil works well.', '20.00', '32.00', '50.00', '70.00', 'Trailing vines, easy to grow');

-- --------------------------------------------------------

--
-- Table structure for table `userplants`
--

CREATE TABLE `userplants` (
  `user_plant_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `plant_catalog_id` int(11) NOT NULL,
  `nickname` varchar(100) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `last_watered_date` date NOT NULL,
  `next_watered_date` date NOT NULL,
  `date_added` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `userplants`
--

INSERT INTO `userplants` (`user_plant_id`, `user_id`, `plant_catalog_id`, `nickname`, `notes`, `last_watered_date`, `next_watered_date`, `date_added`) VALUES
(4, 1, 1, 'Snakey', 'Growing well in the corner', '2025-11-03', '2025-11-17', '2025-11-03'),
(5, 1, 2, 'Vinnie', 'Needs more light', '2025-11-03', '2025-11-10', '2025-11-03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `userid` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `humidity` decimal(4,2) NOT NULL,
  `city` varchar(100) NOT NULL,
  `createdAt` date NOT NULL,
  `temperature` decimal(4,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`userid`, `name`, `email`, `password`, `humidity`, `city`, `createdAt`, `temperature`) VALUES
(1, 'John Doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '60.50', 'Riyadh', '2025-11-03', '25.50'),
(2, 'Admin User', 'admin@ploomlog.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '55.00', 'Riyadh', '2025-11-03', '24.00');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `plantcatalog`
--
ALTER TABLE `plantcatalog`
  ADD PRIMARY KEY (`plantid`);

--
-- Indexes for table `userplants`
--
ALTER TABLE `userplants`
  ADD PRIMARY KEY (`user_plant_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `plant_catalog_id` (`plant_catalog_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`userid`),
  ADD UNIQUE KEY `Email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `plantcatalog`
--
ALTER TABLE `plantcatalog`
  MODIFY `plantid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `userplants`
--
ALTER TABLE `userplants`
  MODIFY `user_plant_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `userid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `userplants`
--
ALTER TABLE `userplants`
  ADD CONSTRAINT `userplants_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`userid`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `userplants_ibfk_2` FOREIGN KEY (`plant_catalog_id`) REFERENCES `plantcatalog` (`plantid`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
