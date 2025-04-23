-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 18 avr. 2025 à 10:35
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `green_db`
--

-- --------------------------------------------------------

--
-- Structure de la table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admins`
--

INSERT INTO `admins` (`id`, `username`, `email`, `password`, `role`, `created_at`, `last_login`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$OOfwryf7/n6j14aFnvIblOC1l5fRy3vTUxOacSGmAUVw7Zq7t1/2C', 'admin', '2025-04-11 11:06:40', '2025-04-11 13:07:08');

-- --------------------------------------------------------

--
-- Structure de la table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` datetime NOT NULL,
  `CREATED_AT` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stations`
--

CREATE TABLE `stations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `location` varchar(255) NOT NULL,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `stations`
--

INSERT INTO `stations` (`id`, `name`, `location`, `status`, `created_at`, `updated_at`) VALUES
(8, 'naaa le s', '36.820279,10.185099', 'active', '2025-04-17 17:52:14', '2025-04-17 17:52:14'),
(9, 'le z en personne', '36.811484,10.135660', 'active', '2025-04-17 17:59:03', '2025-04-17 17:59:03'),
(10, '😂😂😂', '36.818149,10.173469', 'active', '2025-04-17 23:17:37', '2025-04-17 23:17:37');

-- --------------------------------------------------------

--
-- Structure de la table `trajets`
--

CREATE TABLE `trajets` (
  `id` int(11) NOT NULL,
  `start_station_id` int(11) DEFAULT NULL,
  `end_station_id` int(11) DEFAULT NULL,
  `distance` decimal(10,2) NOT NULL COMMENT 'Distance in kilometers',
  `description` text DEFAULT NULL,
  `route_coordinates` text DEFAULT NULL COMMENT 'JSON array of waypoints for the route',
  `route_description` text DEFAULT NULL COMMENT 'Detailed description of the route',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `start_point` varchar(255) DEFAULT NULL,
  `end_point` varchar(255) DEFAULT NULL,
  `start_point_name` varchar(255) DEFAULT NULL,
  `end_point_name` varchar(255) DEFAULT NULL,
  `co2_saved` float DEFAULT NULL,
  `battery_energy` float DEFAULT NULL,
  `fuel_saved` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `trajets`
--

INSERT INTO `trajets` (`id`, `start_station_id`, `end_station_id`, `distance`, `description`, `route_coordinates`, `route_description`, `created_at`, `updated_at`, `start_point`, `end_point`, `start_point_name`, `end_point_name`, `co2_saved`, `battery_energy`, `fuel_saved`) VALUES
(12, NULL, NULL, 2.49, 'skifon', '[{\"lat\":36.826985312011715,\"lng\":10.146496295928957},{\"lat\":36.83285920173124,\"lng\":10.155036449432375},{\"lat\":36.83921348680984,\"lng\":10.166923999786379},{\"lat\":36.839934750672676,\"lng\":10.16915559768677}]', 'bala bala blaa', '2025-04-17 21:29:25', '2025-04-17 21:29:25', '36.82649258036662,10.145616531372072', '36.83981990703076,10.169305801391603', 'Boulevard Mohamed Bouazizi, Ras Tabia, Délégation El Omrane, Tunis, Gouvernorat Tunis, 2062, Tunisie', 'Rue Tarek Ibn Zied, Mutuelleville, 01 Juin, Délégation El Menzah, Tunis, Gouvernorat Tunis, 1086, Tunisie', 398.4, 13.944, 0.18675),
(13, NULL, NULL, 2.49, 'skifon', '[{\"lat\":36.83939138846153,\"lng\":10.148588418960573},{\"lat\":36.84265418698594,\"lng\":10.147880315780641},{\"lat\":36.84323804151118,\"lng\":10.149425268173218},{\"lat\":36.84170970699228,\"lng\":10.150476694107057},{\"lat\":36.84225922404405,\"lng\":10.153008699417116},{\"lat\":36.84528149724389,\"lng\":10.157557725906374}]', 'bala bala blaa', '2025-04-17 21:47:17', '2025-04-17 22:39:41', '36.839130304900074,10.148942470550539', '36.84527800924885,10.157697200775148', 'Colisée Soula, El Manar, Délégation El Menzah, Tunis, Gouvernorat Tunis, 2092, Tunisie', 'Colisée Soula, El Manar, Délégation El Menzah, Tunis, Gouvernorat Tunis, 7102, Tunisie', 398.4, 13.944, 0.18675),
(14, NULL, NULL, 2.26, 'hhihihihih', '[{\"lat\":36.83670551247481,\"lng\":10.152595639228823},{\"lat\":36.83891228736861,\"lng\":10.149130225181581}]', 'd;s,n;sn', '2025-04-17 21:55:54', '2025-04-17 22:39:12', '36.8389929203865,10.148942470550539', '36.83664366068045,10.152729749679567', 'Colisée Soula, El Manar, Délégation El Menzah, Tunis, Gouvernorat Tunis, 2092, Tunisie', 'X 4, Colisée Soula, El Manar, Délégation El Menzah, Tunis, Gouvernorat Tunis, 2092, Tunisie', 361.6, 12.656, 0.1695);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_role` (`role`);

--
-- Index pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_token` (`token`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `idx_expiry` (`expiry`);

--
-- Index pour la table `stations`
--
ALTER TABLE `stations`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `trajets`
--
ALTER TABLE `trajets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_start_station` (`start_station_id`),
  ADD KEY `idx_end_station` (`end_station_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `stations`
--
ALTER TABLE `stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `trajets`
--
ALTER TABLE `trajets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `trajets`
--
ALTER TABLE `trajets`
  ADD CONSTRAINT `trajets_ibfk_1` FOREIGN KEY (`start_station_id`) REFERENCES `stations` (`id`),
  ADD CONSTRAINT `trajets_ibfk_2` FOREIGN KEY (`end_station_id`) REFERENCES `stations` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
