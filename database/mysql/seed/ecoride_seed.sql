-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Hôte : mysql:3306
-- Généré le : ven. 11 sep. 2026 à 13:04
-- Version du serveur : 8.0.46
-- Version de PHP : 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `ecoride`
--

-- --------------------------------------------------------

--
-- Structure de la table `carpool`
--

CREATE TABLE `carpool` (
  `id` int NOT NULL,
  `departure_city` varchar(100) NOT NULL,
  `departure_address` varchar(255) NOT NULL,
  `arrival_city` varchar(100) NOT NULL,
  `arrival_address` varchar(255) NOT NULL,
  `departure_at` datetime NOT NULL,
  `arrival_at` datetime NOT NULL,
  `credit_cost_per_passenger` int NOT NULL,
  `initial_seat_count` smallint NOT NULL,
  `remaining_seat_count` smallint NOT NULL,
  `status` varchar(30) NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `driver_id` int NOT NULL,
  `vehicle_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `carpool`
--

INSERT INTO `carpool` (`id`, `departure_city`, `departure_address`, `arrival_city`, `arrival_address`, `departure_at`, `arrival_at`, `credit_cost_per_passenger`, `initial_seat_count`, `remaining_seat_count`, `status`, `created_at`, `updated_at`, `driver_id`, `vehicle_id`) VALUES
(3, 'Paris', '10 rue de Paris, 75001 Paris', 'Lyon', '1 place Bellecour, 69002 Lyon', '2026-09-14 08:00:00', '2026-09-14 12:00:00', 25, 4, 4, 'PLANNED', '2026-09-11 13:01:53', NULL, 11, 5);

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` json NOT NULL,
  `password` varchar(255) NOT NULL,
  `username` varchar(50) NOT NULL,
  `credits` int NOT NULL,
  `is_driver` tinyint NOT NULL,
  `is_passenger` tinyint NOT NULL,
  `accepts_smokers` tinyint NOT NULL,
  `accepts_pets` tinyint NOT NULL,
  `custom_preferences` longtext,
  `profile_picture` varchar(255) DEFAULT NULL,
  `is_active` tinyint NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_verified` tinyint NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `roles`, `password`, `username`, `credits`, `is_driver`, `is_passenger`, `accepts_smokers`, `accepts_pets`, `custom_preferences`, `profile_picture`, `is_active`, `created_at`, `updated_at`, `is_verified`) VALUES
(10, 'passager1@example.com', '[\"ROLE_USER\"]', '$2y$13$gWOwLQXBtfgXZ1y6tIkoFu9czxgzv5ndk02af0iNF9VlrEg1jHAyC', 'Passager1', 20, 0, 1, 0, 1, NULL, NULL, 1, '2026-09-11 13:01:51', NULL, 0),
(11, 'chauffeur1@example.com', '[\"ROLE_USER\"]', '$2y$13$v.7bKGAqAKOPiXpSiT7cG.1P2GlYKzTu.OacaMaSudmk5ca4UBgV2', 'Chauffeur1', 20, 1, 0, 0, 1, NULL, NULL, 1, '2026-09-11 13:01:52', NULL, 0),
(12, 'mixte@example.com', '[\"ROLE_USER\"]', '$2y$13$ienNwhr9pdW7je7ZO5PKheWFf5hDLM9Sk9q/s9kXTLHmadvfKxiKS', 'Mixte', 20, 1, 1, 0, 1, NULL, NULL, 1, '2026-09-11 13:01:52', NULL, 0),
(13, 'suspendu@example.com', '[\"ROLE_USER\"]', '$2y$13$Qpg4H8D52xJ8c/SDUJJ1/OzdTCFyc3xrNAsa6sosAsPx1XnYWytOS', 'Suspendu', 20, 1, 0, 0, 1, NULL, NULL, 0, '2026-09-11 13:01:52', NULL, 0),
(14, 'employee@ecoride.com', '[\"ROLE_EMPLOYEE\", \"ROLE_USER\"]', '$2y$13$TOmUVSgujJSSTbg/ZJFr9eb.7K08x3i2IDiz0.bzh1opjjVG.ahPC', 'Employee', 20, 0, 0, 0, 1, NULL, NULL, 1, '2026-09-11 13:01:53', NULL, 0),
(15, 'admin@ecoride.com', '[\"ROLE_ADMIN\", \"ROLE_USER\"]', '$2y$13$xoRQXBCMT.MQiH6/tO7B5uRwcSlwWNOnDSHmyKxjbiAcLAdkuSC9a', 'Admin', 20, 0, 0, 0, 1, NULL, NULL, 1, '2026-09-11 13:01:53', NULL, 0);

-- --------------------------------------------------------

--
-- Structure de la table `vehicle`
--

CREATE TABLE `vehicle` (
  `id` int NOT NULL,
  `registration_number` varchar(20) NOT NULL,
  `first_registration_date` date NOT NULL,
  `model` varchar(100) NOT NULL,
  `color` varchar(50) DEFAULT NULL,
  `energy_type` varchar(50) NOT NULL,
  `seat_count` smallint NOT NULL,
  `owner_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Déchargement des données de la table `vehicle`
--

INSERT INTO `vehicle` (`id`, `registration_number`, `first_registration_date`, `model`, `color`, `energy_type`, `seat_count`, `owner_id`) VALUES
(5, 'AB-123-CD', '2020-01-01', 'Toyota Prius', 'Gris', 'Hybrid', 4, 11),
(6, 'EF-456-GH', '2018-06-01', 'Renault Clio', 'Bleu', 'Diesel', 5, 12);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `carpool`
--
ALTER TABLE `carpool`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_E95D90CCC3423909` (`driver_id`),
  ADD KEY `IDX_E95D90CC545317D1` (`vehicle_id`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`);

--
-- Index pour la table `vehicle`
--
ALTER TABLE `vehicle`
  ADD PRIMARY KEY (`id`),
  ADD KEY `IDX_1B80E4867E3C61F9` (`owner_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `carpool`
--
ALTER TABLE `carpool`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT pour la table `vehicle`
--
ALTER TABLE `vehicle`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `carpool`
--
ALTER TABLE `carpool`
  ADD CONSTRAINT `FK_E95D90CC545317D1` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicle` (`id`),
  ADD CONSTRAINT `FK_E95D90CCC3423909` FOREIGN KEY (`driver_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `vehicle`
--
ALTER TABLE `vehicle`
  ADD CONSTRAINT `FK_1B80E4867E3C61F9` FOREIGN KEY (`owner_id`) REFERENCES `user` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
