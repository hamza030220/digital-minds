-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 11 mai 2025 à 01:06
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
(1, 'admin', 'admin@example.com', '$2y$10$MxgFDHGDItlkKWfnPwrtyOuKQFeVl50r8zPQGylhn71wOxViemJd2', 'admin', '2025-04-11 11:06:40', '2025-04-11 13:07:08');

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

--
-- Déchargement des données de la table `password_resets`
--

INSERT INTO `password_resets` (`id`, `user_id`, `token`, `expiry`, `CREATED_AT`) VALUES
(1, 1, 'f1e234b5dc6e20f7b7ef780ed73a2606b25a0fb4650203b6c83e31206ad2cab1', '2025-05-05 16:44:00', '2025-05-05 13:44:00'),
(2, 1, 'e851b84de4ef7130c6b51c79914dc6a9035afe92658e291fe7aa95c766bf3c1b', '2025-05-05 16:45:49', '2025-05-05 13:45:49'),
(3, 1, '6d3f4849e64f4ede96c83d0d46acf6c975d8b0f42a4cbe6e76c884b5dbef7499', '2025-05-05 17:16:07', '2025-05-05 14:16:07');

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
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `city` varchar(100) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `stations`
--

INSERT INTO `stations` (`id`, `name`, `location`, `status`, `created_at`, `updated_at`, `city`, `admin_id`) VALUES
(8, 'naaa le s1222', '36.820279,10.185099', 'active', '2025-04-17 17:52:14', '2025-05-05 16:02:20', 'Gouvernorat Tunis', NULL),
(9, 'le z en personne', '36.811484,10.135660', 'inactive', '2025-04-17 17:59:03', '2025-04-23 20:46:37', 'Gouvernorat Tunis', NULL),
(11, 'class1', '36.819454,10.182180', 'inactive', '2025-04-18 09:29:14', '2025-04-23 20:46:38', 'Gouvernorat Tunis', NULL),
(13, 'hamzaa 0302220', '36.801863,10.182009', 'active', '2025-04-18 11:17:50', '2025-04-23 20:46:40', 'Gouvernorat Tunis', NULL),
(15, 'mou7awla1', '36.855450,11.097565', 'inactive', '2025-04-23 19:37:36', '2025-04-23 20:46:42', 'Gouvernorat Nabeul', NULL),
(16, 'mou7awlaa 5', '37.274053,9.830017', 'inactive', '2025-04-23 19:37:53', '2025-04-24 11:58:57', 'Gouvernorat Bizerte', NULL),
(18, 'mou7awlaa 999', '35.697456,10.774841', 'active', '2025-04-23 19:38:31', '2025-04-24 13:28:44', 'Gouvernorat Monastir', NULL),
(20, 'yooooooo hamzaz', '34.746126,10.763855', 'active', '2025-04-23 20:30:55', '2025-04-23 20:46:48', 'Gouvernorat Sfax', NULL),
(21, 'ye rabi sahell77777', '35.474092,11.038513', 'active', '2025-04-23 20:32:35', '2025-04-23 20:46:49', 'Gouvernorat Mahdia', NULL),
(22, 'ppppp1', '36.802000,10.137892', 'active', '2025-04-28 22:25:47', '2025-04-28 22:26:08', 'Gouvernorat Tunis', NULL),
(53, 'nbnbbnb', '36.804337,10.162447', 'active', '2025-05-05 17:40:03', '2025-05-05 17:40:03', 'Gouvernorat Tunis', NULL),
(54, 'resauu', '36.803375,10.135333', 'active', '2025-05-06 11:00:12', '2025-05-06 11:00:12', 'Gouvernorat Tunis', NULL),
(55, 'aziz gathounn11', '36.795540,10.180807', 'inactive', '2025-05-06 17:52:15', '2025-05-06 17:52:56', 'Gouvernorat Tunis', NULL),
(56, 'tesst55', '36.905259,10.183232', 'active', '2025-05-09 08:55:28', '2025-05-09 08:55:28', 'Gouvernorat Ariana', NULL),
(57, 'bechir11', '37.288668,9.869413', 'active', '2025-05-09 19:56:39', '2025-05-09 19:58:24', 'Gouvernorat Bizerte', NULL);

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
  `fuel_saved` float DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `trajets`
--

INSERT INTO `trajets` (`id`, `start_station_id`, `end_station_id`, `distance`, `description`, `route_coordinates`, `route_description`, `created_at`, `updated_at`, `start_point`, `end_point`, `start_point_name`, `end_point_name`, `co2_saved`, `battery_energy`, `fuel_saved`, `admin_id`) VALUES
(13, NULL, NULL, 2.49, 'skifon22222', '[{\"lat\":36.826846832196736,\"lng\":10.20260810852051},{\"lat\":36.83564033977185,\"lng\":10.225954055786135},{\"lat\":36.84168528988323,\"lng\":10.245866775512697},{\"lat\":36.85308696319283,\"lng\":10.273160934448244},{\"lat\":36.862152140387806,\"lng\":10.295133590698244},{\"lat\":36.8687443211593,\"lng\":10.309209823608398},{\"lat\":36.870529605571406,\"lng\":10.313673019409181}]', 'Ce trajet entre Route Nationale Tunis - Bizerte, Zone Industrielle La Charguia I, Ech-Charguia, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 2035, Tunisie et Route Nationale Tunis - La Marsa, Cité Les Pins, La Marsa El Montazeh, Délégation La Marsa, Tunis, Gouvernorat Tunis, 2070, Tunisie met en avant la mobilité durable. Parcourez des paysages naturels, découvrez des points d\'intérêt locaux et profitez d\'un itinéraire respectueux de l\'environnement.', '2025-04-17 21:47:17', '2025-05-05 18:34:46', '36.82544484414305,10.20029067993164', '36.870227054298226,10.313243865966799', 'Route Nationale Tunis - Bizerte, Zone Industrielle La Charguia I, Ech-Charguia, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 2035, Tunisie', 'Route Nationale Tunis - La Marsa, Cité Les Pins, La Marsa El Montazeh, Délégation La Marsa, Tunis, Gouvernorat Tunis, 2070, Tunisie', 398.4, 13.944, 0.18675, NULL),
(14, NULL, NULL, 2.26, 'hhihihihih', '[{\"lat\":36.83670551247481,\"lng\":10.152595639228823},{\"lat\":36.83891228736861,\"lng\":10.149130225181581}]', 'd;s,n;sn', '2025-04-17 21:55:54', '2025-04-17 22:39:12', '36.8389929203865,10.148942470550539', '36.83664366068045,10.152729749679567', 'Colisée Soula, El Manar, Délégation El Menzah, Tunis, Gouvernorat Tunis, 2092, Tunisie', 'X 4, Colisée Soula, El Manar, Délégation El Menzah, Tunis, Gouvernorat Tunis, 2092, Tunisie', 361.6, 12.656, 0.1695, NULL),
(15, NULL, NULL, 1.37, 'new new 1', '[{\"lat\":36.801586177322015,\"lng\":10.188810825347902},{\"lat\":36.80498803388556,\"lng\":10.18855333328247},{\"lat\":36.8086302582343,\"lng\":10.188252925872805},{\"lat\":36.81141335062683,\"lng\":10.188167095184328},{\"lat\":36.81368098073698,\"lng\":10.189454555511476}]', 'ballaa blaa blaa', '2025-04-18 09:30:29', '2025-04-18 09:30:56', '36.80116898848699,10.189561843872072', '36.81367614921016,10.189390182495119', 'Avenue Habib Bourguiba, Habib Thameur, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 2058, Tunisie', 'Route Nationale Bizerte - Tunis, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1073, Tunisie', 219.2, 7.672, 0.10275, NULL),
(17, NULL, NULL, 1.41, 'satar alahh', '[{\"lat\":36.80388107797049,\"lng\":10.230669379234316},{\"lat\":36.804645626896026,\"lng\":10.235443711280825}]', 'le 2ileha ela lahhhhhhhh', '2025-04-23 23:11:19', '2025-04-23 23:11:19', '36.80391799018142,10.230588912963869', '36.804722940407345,10.235443711280825', 'Route de La Goulette, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1053, Tunisie', 'Route de La Goulette, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1053, Tunisie', 225.6, 7.896, 0.10575, NULL),
(18, NULL, NULL, 1.16, 'test test45454', '[{\"lat\":36.80065837203224,\"lng\":10.188767910003664},{\"lat\":36.80519420215369,\"lng\":10.188810825347902},{\"lat\":36.80900821498964,\"lng\":10.188167095184328},{\"lat\":36.81106976444747,\"lng\":10.18786668777466}]', 'ahaaaahhahah', '2025-04-28 22:27:44', '2025-04-28 22:27:44', '36.8002068145812,10.189218521118166', '36.81120236742129,10.188016891479494', 'Hôtel El Bahy Tunis, Rue Christophe Colomb, Lac De Tunis, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 2058, Tunisie', 'Route Nationale Bizerte - Tunis, Les Jardins, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 1073, Tunisie', 185.6, 6.496, 0.087, NULL),
(34, NULL, NULL, 2.74, 'dawerrr', '[{\"lat\":36.80773689848675,\"lng\":10.253870487213137},{\"lat\":36.80275450873501,\"lng\":10.223701000213625}]', 'L\'itinéraire entre Route de La Goulette, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1055, Tunisie et Route de La Goulette, Lac De Tunis, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 1053, Tunisie traverse des zones naturelles protégées et met en lumière les efforts locaux pour la durabilité.', '2025-05-05 16:00:52', '2025-05-05 16:00:52', '36.80804130769649,10.254106521606447', '36.80240605146099,10.222692489624025', 'Route de La Goulette, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1055, Tunisie', 'Route de La Goulette, Lac De Tunis, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 1053, Tunisie', 438.4, 15.344, 0.2055, NULL),
(35, NULL, NULL, 3.37, 'ygiuhgfyut', '[{\"lat\":36.78350913241294,\"lng\":10.114824771881105},{\"lat\":36.784918332919275,\"lng\":10.119545459747314},{\"lat\":36.790829818506154,\"lng\":10.119287967681887},{\"lat\":36.79378539026806,\"lng\":10.13044595718384},{\"lat\":36.79588171506938,\"lng\":10.138556957244875},{\"lat\":36.79206704846779,\"lng\":10.140573978424074}]', 'Voyagez de façon durable entre Ezzahrouni, Délégation El Hrairia, Tunis, Gouvernorat Tunis, 1095, Tunisie et Route Nationale Tunis - Sakiet Sidi Youssef, Cite Tayarane, Délégation Ezzouhour, Tunis, Gouvernorat Tunis, 2052, Tunisie grâce à cet itinéraire pensé pour l\'écologie.', '2025-05-09 20:08:34', '2025-05-09 20:08:34', '36.78343555682716,10.114545822143556', '36.79237151996513,10.14115333557129', 'Ezzahrouni, Délégation El Hrairia, Tunis, Gouvernorat Tunis, 1095, Tunisie', 'Route Nationale Tunis - Sakiet Sidi Youssef, Cite Tayarane, Délégation Ezzouhour, Tunis, Gouvernorat Tunis, 2052, Tunisie', 539.2, 18.872, 0.25275, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`, `last_login`, `reset_token`, `reset_expires`) VALUES
(1, 'admin', 'admin@example.com', '$2y$10$JkAbcPemL4vihQ6hXhOyFO0ctEObV2zJq5gL17YZJyifJ6rALyaZq', 'admin', '2025-05-05 13:43:49', '2025-05-05 14:16:07', '55c2b5f72ef0a7b454789f59b87dee601005c035cf63dbb7939bb43408c31b51', '2025-05-07 01:18:29');

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
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_station_admin` (`admin_id`);

--
-- Index pour la table `trajets`
--
ALTER TABLE `trajets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_start_station` (`start_station_id`),
  ADD KEY `idx_end_station` (`end_station_id`),
  ADD KEY `fk_trajet_admin` (`admin_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_email` (`email`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `stations`
--
ALTER TABLE `stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=58;

--
-- AUTO_INCREMENT pour la table `trajets`
--
ALTER TABLE `trajets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `password_resets`
--
ALTER TABLE `password_resets`
  ADD CONSTRAINT `password_resets_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admins` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `stations`
--
ALTER TABLE `stations`
  ADD CONSTRAINT `fk_station_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `trajets`
--
ALTER TABLE `trajets`
  ADD CONSTRAINT `fk_trajet_admin` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
