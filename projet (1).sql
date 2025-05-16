-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 15, 2025 at 05:49 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `projet`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id_admin` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id_admin`, `username`, `password`) VALUES
(1, 'admin', 'admin123'),
(2, 'admin@admin.com', 'admin123');

-- --------------------------------------------------------

--
-- Table structure for table `avis`
--

CREATE TABLE `avis` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `titre` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `note` int(11) NOT NULL CHECK (`note` between 1 and 5),
  `date_creation` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `avis`
--

INSERT INTO `avis` (`id`, `user_id`, `titre`, `description`, `note`, `date_creation`) VALUES
(27, 20, 'Super service !', 'J\'ai utilisé le service de réservation et tout s\'est bien passé. Le personnel est très accueillant.', 5, '2025-04-20 10:00:00'),
(28, 26, 'Problème avec ma réservation', 'J\'ai eu un souci avec ma réservation, le vélo n\'était pas disponible à l\'heure convenue.', 2, '2025-04-22 14:30:00'),
(29, 50, 'Expérience moyenne', 'Le service est correct, mais il y a des améliorations à faire au niveau de la communication.', 3, '2025-04-25 09:15:00'),
(30, 52, 'Très satisfait', 'Le vélo était en bon état, et le processus de réservation était simple. Je recommande !', 4, '2025-04-27 16:00:00'),
(31, 56, 'Déçu par la maintenance', 'Le vélo que j\'ai loué avait un problème de frein, ce n\'était pas sûr.', 1, '2025-04-28 11:45:00'),
(37, 26, 'problemea', 'qqqqqqqsssssssfffffffffff', 5, '2025-05-15 03:44:01');

-- --------------------------------------------------------

--
-- Table structure for table `bikes`
--

CREATE TABLE `bikes` (
  `id` int(11) NOT NULL,
  `type` varchar(100) NOT NULL,
  `image` varchar(255) NOT NULL,
  `available` tinyint(1) DEFAULT 1,
  `type_velo` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `bikes`
--

INSERT INTO `bikes` (`id`, `type`, `image`, `available`, `type_velo`) VALUES
(1, 'Vélo de ville', 'bike.jpg', 1, 'Mountain Bike'),
(2, 'Vélo de course', 'image/bike.jpg', 1, 'City Bike'),
(3, 'Vélo de montagne', 'image/bike.jpg', 1, 'Electric Bike');

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE `comments` (
  `comment_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL,
  `is_deleted` tinyint(1) DEFAULT 0,
  `is_reported` tinyint(1) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `dislikes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `comments`
--

INSERT INTO `comments` (`comment_id`, `post_id`, `id`, `content`, `created_at`, `is_deleted`, `is_reported`, `likes`, `dislikes`) VALUES
(1, 2, 50, 'mppooo', '2025-05-04 18:34:53', 0, 1, 1, 0),
(2, 2, 52, 'mpooo', '2025-05-04 18:35:18', 1, 0, 0, 0),
(3, 4, 52, 'jjjjjjjjjjj', '2025-05-04 20:01:23', 0, 0, 0, 0),
(6, 1, 52, 'lllllllllllll', '2025-05-04 20:36:39', 0, 0, 0, 0),
(7, 8, 50, 'kkkkkkkkkkkkkkkkkkk', '2025-05-04 20:47:04', 0, 0, 0, 0),
(8, 8, 50, 'mmmmmmmmmmmmmmmmm', '2025-05-04 20:48:26', 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `comment_likes`
--

CREATE TABLE `comment_likes` (
  `user_id` int(11) NOT NULL,
  `comment_id` int(11) NOT NULL,
  `is_like` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `comment_likes`
--

INSERT INTO `comment_likes` (`user_id`, `comment_id`, `is_like`) VALUES
(52, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `corbeille_velos`
--

CREATE TABLE `corbeille_velos` (
  `id_velo` int(11) NOT NULL,
  `nom_velo` varchar(255) NOT NULL,
  `type_velo` varchar(255) NOT NULL,
  `prix_par_jour` decimal(10,2) NOT NULL,
  `disponibilite` tinyint(1) NOT NULL,
  `description` text DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `corbeille_velos`
--

INSERT INTO `corbeille_velos` (`id_velo`, `nom_velo`, `type_velo`, `prix_par_jour`, `disponibilite`, `description`, `deleted_at`) VALUES
(68, 'amira', 'Vélo de route', 32655433.00, 1, NULL, '2025-04-30 14:10:15'),
(71, 'fatma', 'VTy', 399.00, 0, NULL, '2025-05-01 21:51:08'),
(72, 'amira', 'Vélo de route', 32655433.00, 1, NULL, '2025-05-01 09:43:40'),
(79, 'pro', 'VTy', 22.00, 0, NULL, '2025-05-11 09:56:26');

-- --------------------------------------------------------

--
-- Table structure for table `notification`
--

CREATE TABLE `notification` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `reclamation_id` int(11) NOT NULL,
  `message` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `message`, `is_read`, `created_at`) VALUES
(8, 38, 'Nouvel utilisateur inscrit : jouini ahlem', 1, '2025-04-26 11:22:33'),
(9, 39, 'Nouvel utilisateur inscrit : jammel rahma', 1, '2025-04-26 11:47:22'),
(10, 40, 'Nouvel utilisateur inscrit : jammel rahma', 1, '2025-04-26 11:49:03'),
(11, 41, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-04-26 11:55:29'),
(12, 42, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-04-26 12:08:59'),
(13, 43, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-04-26 12:21:06'),
(14, 44, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-04-26 12:24:05'),
(15, 45, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-04-26 12:25:10'),
(16, 46, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-04-26 12:44:22'),
(17, 47, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-04-26 12:48:58'),
(18, 48, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-04-26 12:59:11'),
(19, 49, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-04-26 13:43:26'),
(20, 50, 'Nouvel utilisateur inscrit : maryem maryem', 1, '2025-04-26 21:57:06'),
(21, 51, 'Nouvel utilisateur inscrit : maryem maryem', 1, '2025-04-26 22:11:07'),
(22, 52, 'Nouvel utilisateur inscrit : ben tarjem fatma', 1, '2025-04-26 22:13:34'),
(23, 53, 'Nouvel utilisateur inscrit : ben tarjem fatma', 1, '2025-04-26 22:17:00'),
(24, 54, 'Nouvel utilisateur inscrit : ben tarjem fatma', 1, '2025-04-27 16:49:13'),
(25, 55, 'Nouvel utilisateur inscrit : ben tarjem fatma', 1, '2025-04-27 19:47:54'),
(26, 56, 'Nouvel utilisateur inscrit : znaidi habib', 1, '2025-04-27 22:35:29'),
(27, 57, 'Nouvel utilisateur inscrit : Mohamed Aziz Miaoui', 1, '2025-04-28 14:28:54'),
(28, 58, 'Nouvel utilisateur inscrit : Mohamed Aziz Miaoui', 1, '2025-04-28 22:19:39'),
(29, 59, 'Nouvel utilisateur inscrit : Mohamed Aziz Miaoui', 1, '2025-04-28 22:20:03'),
(30, 61, 'Nouvel utilisateur inscrit : ghada jouini', 1, '2025-05-02 10:37:27'),
(31, 62, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-05-04 10:31:30'),
(32, 63, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-05-04 10:38:24'),
(33, 64, 'Nouvel utilisateur inscrit : emna jouini', 1, '2025-05-11 21:25:24'),
(34, 65, 'Nouvel utilisateur inscrit : hamza slimani', 1, '2025-05-12 13:07:45');

-- --------------------------------------------------------

--
-- Table structure for table `notification_reservation`
--

CREATE TABLE `notification_reservation` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `created_at` datetime NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `reservation_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `notification_reservation`
--

INSERT INTO `notification_reservation` (`id`, `user_id`, `message`, `created_at`, `is_read`, `reservation_id`) VALUES
(2, 20, 'Nouvelle rÃ©servation #141 par jouiniii aziz pour le vÃ©lo type eeevoici (ID: 73, du 2025-05-11 au 2025-05-24).', '2025-05-01 23:50:38', 0, 141),
(3, 60, 'Nouvelle rÃ©servation #141 par jouiniii aziz pour le vÃ©lo type eeevoici (ID: 73, du 2025-05-11 au 2025-05-24).', '2025-05-01 23:50:38', 0, 141),
(12, 20, 'Nouvelle rÃ©servation #143 par jouiniii aziz pour le vÃ©lo type Mountain Bike (ID: 1, du 2025-04-28 au 2025-04-30).', '2025-05-02 00:13:31', 1, 143),
(13, 60, 'Nouvelle rÃ©servation #143 par jouiniii aziz pour le vÃ©lo type Mountain Bike (ID: 1, du 2025-04-28 au 2025-04-30).', '2025-05-02 00:13:31', 0, 143),
(15, 20, 'Nouvelle rÃ©servation #144 par jouiniiiiiii amira pour le vÃ©lo type Urbain (ID: 6, du 2025-04-29 au 2025-04-30).', '2025-05-02 11:45:23', 1, 144),
(16, 60, 'Nouvelle rÃ©servation #144 par jouiniiiiiii amira pour le vÃ©lo type Urbain (ID: 6, du 2025-04-29 au 2025-04-30).', '2025-05-02 11:45:23', 0, 144),
(17, 20, 'Nouvelle rÃ©servation #145 par ben tarjem fatma pour le vÃ©lo type Urbain (ID: 6, du 2025-05-10 au 2025-05-30).', '2025-05-04 11:42:19', 0, 145),
(18, 60, 'Nouvelle rÃ©servation #145 par ben tarjem fatma pour le vÃ©lo type Urbain (ID: 6, du 2025-05-10 au 2025-05-30).', '2025-05-04 11:42:19', 0, 145),
(23, 20, 'Nouvelle rÃ©servation #147 par maryem maryem pour le vÃ©lo type eee (ID: 70, du 2025-05-10 au 2025-05-23).', '2025-05-04 12:30:30', 0, 147),
(24, 60, 'Nouvelle rÃ©servation #147 par maryem maryem pour le vÃ©lo type eee (ID: 70, du 2025-05-10 au 2025-05-23).', '2025-05-04 12:30:30', 0, 147),
(25, 50, 'Votre rÃ©servation #147 pour le vÃ©lo emna (du 2025-05-10 au 2025-05-23) a Ã©tÃ© acceptÃ©e.', '2025-05-04 12:31:56', 0, 147),
(26, 20, 'Nouvelle rÃ©servation #148 par maryem maryem pour le vÃ©lo type Urbain (ID: 6, du 2025-05-07 au 2025-05-09).', '2025-05-04 12:38:10', 0, 148),
(27, 60, 'Nouvelle rÃ©servation #148 par maryem maryem pour le vÃ©lo type Urbain (ID: 6, du 2025-05-07 au 2025-05-09).', '2025-05-04 12:38:10', 0, 148),
(28, 50, 'Votre rÃ©servation #148 pour le vÃ©lo eee (du 2025-05-07 au 2025-05-09) a Ã©tÃ© acceptÃ©e.', '2025-05-04 12:38:35', 0, 148),
(29, 20, 'Nouvelle rÃ©servation #149 par maryem maryem pour le vÃ©lo type eeevoici (ID: 73, du 2025-05-07 au 2025-05-17).', '2025-05-04 13:03:10', 0, 149),
(30, 60, 'Nouvelle rÃ©servation #149 par maryem maryem pour le vÃ©lo type eeevoici (ID: 73, du 2025-05-07 au 2025-05-17).', '2025-05-04 13:03:10', 0, 149),
(31, 20, 'RÃ©servation #147 modifiÃ©e par maryem maryem pour le vÃ©lo emna (ID: 70, du 2025-05-10 au 2025-05-23).', '2025-05-04 13:03:27', 0, 147),
(32, 60, 'RÃ©servation #147 modifiÃ©e par maryem maryem pour le vÃ©lo emna (ID: 70, du 2025-05-10 au 2025-05-23).', '2025-05-04 13:03:27', 0, 147),
(33, 50, 'Votre rÃ©servation #149 pour le vÃ©lo emna (du 2025-05-07 au 2025-05-17) a Ã©tÃ© acceptÃ©e.', '2025-05-04 13:04:00', 0, 149),
(40, 20, 'Nouvelle rÃ©servation #152 par ben tarjem fatma pour le vÃ©lo type electro (ID: 78, du 2025-05-09 au 2025-05-17).', '2025-05-04 21:32:45', 0, 152),
(41, 60, 'Nouvelle rÃ©servation #152 par ben tarjem fatma pour le vÃ©lo type electro (ID: 78, du 2025-05-09 au 2025-05-17).', '2025-05-04 21:32:45', 0, 152),
(42, 20, 'RÃ©servation #145 modifiÃ©e par ben tarjem fatma pour le vÃ©lo eee (ID: 6, du 2025-05-23 au 2025-05-30).', '2025-05-11 10:47:40', 0, 145),
(43, 60, 'RÃ©servation #145 modifiÃ©e par ben tarjem fatma pour le vÃ©lo eee (ID: 6, du 2025-05-23 au 2025-05-30).', '2025-05-11 10:47:40', 0, 145),
(44, 52, 'Votre rÃ©servation #152 pour le vÃ©lo velo5 (du 2025-05-09 au 2025-05-17) a Ã©tÃ© acceptÃ©e.', '2025-05-11 10:50:36', 0, 152),
(45, 52, 'Votre rÃ©servation #152 pour le vÃ©lo velo5 (du 2025-05-09 au 2025-05-17) a Ã©tÃ© refusÃ©e et dÃ©placÃ©e vers la corbeille.', '2025-05-11 23:45:07', 0, 152);

-- --------------------------------------------------------

--
-- Table structure for table `notif_comm`
--

CREATE TABLE `notif_comm` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `commenter_id` int(11) NOT NULL,
  `content` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL,
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `notif_comm`
--

INSERT INTO `notif_comm` (`id`, `user_id`, `post_id`, `commenter_id`, `content`, `created_at`, `is_read`) VALUES
(1, 52, 8, 50, 'new_comment_notif_comm_content', '2025-05-04 20:48:27', 1);

-- --------------------------------------------------------

--
-- Table structure for table `pannes`
--

CREATE TABLE `pannes` (
  `id` int(11) NOT NULL,
  `description` text NOT NULL,
  `date_declaration` datetime DEFAULT current_timestamp(),
  `status` enum('en cours','résolu','en attente') NOT NULL DEFAULT 'en attente'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `pannes`
--

INSERT INTO `pannes` (`id`, `description`, `date_declaration`, `status`) VALUES
(1, 'Panne du vélo numéro 5', '2025-04-16 21:28:56', 'en cours'),
(2, 'Problème de batterie sur vélo 3', '2025-04-16 21:28:56', 'résolu'),
(3, 'Problème de frein sur le vélo 7', '2025-04-16 21:29:29', 'en cours'),
(4, 'Défaillance du système de batterie sur vélo 2', '2025-04-16 21:29:29', 'en attente'),
(5, 'Problème de lumière sur le vélo 10', '2025-04-16 21:29:29', 'résolu'),
(6, 'Roues dégonflées sur le vélo 4', '2025-04-16 21:29:29', 'en cours'),
(7, 'Batterie de vélo 8 ne charge plus', '2025-04-16 21:29:29', 'en attente'),
(8, 'Vélo 6 a une roue cassée', '2025-04-16 21:29:29', 'résolu'),
(9, 'Problème de transmission sur le vélo 3', '2025-04-16 21:29:29', 'en cours'),
(10, 'Batterie défectueuse sur vélo 9', '2025-04-16 21:29:29', 'en attente'),
(11, 'Système de guidon mal réglé sur vélo 5', '2025-04-16 21:29:30', 'résolu'),
(12, 'Problème d\'accélérateur sur le vélo 12', '2025-04-16 21:29:30', 'en cours');

-- --------------------------------------------------------

--
-- Table structure for table `password_reset`
--

CREATE TABLE `password_reset` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expire` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_reset`
--

INSERT INTO `password_reset` (`id`, `email`, `token`, `created_at`, `expire`) VALUES
(1, 'emnajouinii2000@gmail.com', '54a912a742a488cd0267ff85a13c5e928bebe4433e21dd3e82897200b9fecd43', '2025-04-26 13:27:53', '2025-04-26 16:27:53'),
(2, 'emnaj936@gmail.com', '5aa7e59e02c04b9962b4f16949a80b4911129b3a89590ac16735170393ebe754', '2025-04-26 13:36:21', '2025-04-26 16:36:21'),
(3, 'emnaj936@gmail.com', 'b877489e3457e7d60cfe2cf46fa2cfea7dfeb26d56caffaead4f76f0393e4fdc', '2025-04-27 10:22:59', '2025-04-27 13:22:59'),
(5, 'emnaj936@gmail.com', 'c9a7a91627ddc6a6f003cc0eabc39c5596d0f2621b51ffac0091a5832b41366a', '2025-05-02 10:40:19', '2025-05-02 13:40:19'),
(6, 'emnaj936@gmail.com', '9a83c23ef036b8170551346a98ae4213e144fd35fb92edb0cc891f84291616e2', '2025-05-02 10:40:30', '2025-05-02 13:40:30'),
(7, 'emnaj936@gmail.com', 'e2bedf869e3ec21c123d4b6dbb601577117e135ad6c473cf76739d88698c114d', '2025-05-11 23:06:37', '2025-05-12 02:06:37');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `post_id` int(11) NOT NULL,
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL,
  `is_anonymous` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `is_reported` tinyint(1) DEFAULT 0,
  `likes` int(11) DEFAULT 0,
  `dislikes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`post_id`, `id`, `title`, `content`, `created_at`, `updated_at`, `is_anonymous`, `is_deleted`, `is_reported`, `likes`, `dislikes`) VALUES
(1, 50, 'hhhkkkkkkkkkkkkkkkkkkk', 'lllljjjjjjjjjjjjjjjjjjjjjmmmmmmmmmm', '2025-05-04 18:33:56', '2025-05-04 18:34:11', 0, 0, 0, 2, 0),
(2, 50, 'hhhkkkkkkkkkkkkkkkkkkk', 'mmmmmmmmmmmmmmmmmmmmmmmmmmmm', '2025-05-04 18:34:39', NULL, 1, 0, 1, 1, 0),
(3, 52, 'mmmmmmmmmmmmmmmmmmm', 'iiiiiiiiiiiiiiiiiiiiiii', '2025-05-04 18:36:45', NULL, 0, 1, 0, 0, 0),
(4, 52, 'lllloooooooooo', 'mmmmmmmmmmmm', '2025-05-04 20:00:20', NULL, 0, 1, 0, 0, 0),
(5, 52, 'mmmmmmmmmmmmmmmmmmm', 'ppppppppppppppppppppppp', '2025-05-04 20:01:33', NULL, 0, 0, 0, 0, 0),
(6, 52, 'ooooooooooooooooooooooooooooooo', 'ppppppppppppppppppppppppppppppppp', '2025-05-04 20:01:41', NULL, 0, 0, 1, 0, 0),
(8, 52, 'kkkkkkkkkkkkppppppppp', 'mmmmmmmmmmmmmmmmmmmm', '2025-05-04 20:07:32', '2025-05-04 20:22:29', 0, 1, 0, 0, 0),
(12, 52, 'mmmmmmmmmmmmmmmmmmm', 'LLLLLLLLLLLLLLLLLLL', '2025-05-11 10:47:11', NULL, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `post_likes`
--

CREATE TABLE `post_likes` (
  `user_id` int(11) NOT NULL,
  `post_id` int(11) NOT NULL,
  `is_like` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `post_likes`
--

INSERT INTO `post_likes` (`user_id`, `post_id`, `is_like`) VALUES
(50, 1, 1),
(52, 1, 1),
(52, 2, 1);

-- --------------------------------------------------------

--
-- Table structure for table `reactions`
--

CREATE TABLE `reactions` (
  `reaction_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `target_id` int(11) NOT NULL,
  `target_type` enum('post','comment') NOT NULL,
  `reaction_type` enum('like','dislike') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reclamations`
--

CREATE TABLE `reclamations` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `lieu` varchar(255) DEFAULT NULL,
  `type_probleme` enum('mécanique','batterie','écran','pneu','autre') NOT NULL,
  `statut` enum('ouverte','en cours','résolue') NOT NULL DEFAULT 'ouverte',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `priorite` varchar(10) NOT NULL DEFAULT 'moyenne'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reclamations`
--

INSERT INTO `reclamations` (`id`, `utilisateur_id`, `titre`, `description`, `lieu`, `type_probleme`, `statut`, `date_creation`, `priorite`) VALUES
(90, 50, 'Pneu crevé', 'J\'ai trouvé un pneu crevé après 10 minutes d\'utilisation.', 'Ariana, station 3', 'pneu', 'résolue', '2025-04-26 14:30:00', 'moyenne'),
(91, 52, 'Problème d\'écran', 'L\'écran affiche des erreurs incompréhensibles.', 'Nabeul, gare', 'écran', 'résolue', '2025-04-27 09:00:00', 'basse'),
(92, 56, 'Frein défectueux', 'Les freins ne fonctionnent pas correctement, dangereux.', 'Bizerte, port', 'mécanique', 'ouverte', '2025-04-29 11:15:00', 'haute'),
(93, 61, 'Chargeur défectueux', 'Le chargeur fourni ne fonctionne pas.', 'Ariana, quartier nord', 'autre', 'en cours', '2025-05-03 07:45:00', 'moyenne'),
(94, 40, 'aaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaa', 'écran', 'ouverte', '2025-05-15 00:30:29', 'moyenne'),
(95, 40, 'probleme', 'qqqqqqqqqqqqqqq', 'qqqqqqqqqqqqqqqqq', 'écran', 'ouverte', '2025-05-15 00:37:26', 'moyenne'),
(96, 40, 'probleme', 'aaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaa', 'batterie', 'ouverte', '2025-05-15 00:40:56', 'moyenne'),
(97, 40, 'probleme', 'aaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaa', 'batterie', 'ouverte', '2025-05-15 00:42:49', 'moyenne'),
(99, 40, 'malfunction', 'qqqqqqqqqqqqq', 'qqqqqqqq', 'batterie', 'ouverte', '2025-05-15 00:44:06', 'moyenne'),
(100, 40, 'malfunction', 'qqqqqqqqqqqqq', 'qqqqqqqq', 'batterie', 'ouverte', '2025-05-15 00:44:28', 'moyenne'),
(101, 40, 'malfunction', 'qqqqqqqqqqqqq', 'qqqqqqqq', 'batterie', 'ouverte', '2025-05-15 00:45:49', 'moyenne'),
(102, 40, 'eeeeaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaa', 'batterie', 'ouverte', '2025-05-15 00:45:59', 'moyenne'),
(103, 40, 'eeeeaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaa', 'aaaaaaaaaaaaaaaaaaaaaaaa', 'batterie', 'ouverte', '2025-05-15 00:47:38', 'moyenne'),
(106, 26, 'probleme', 'TESSSSSSSSSSSST', 'qqqqqqqqqqqqqq', 'mécanique', 'ouverte', '2025-05-15 02:12:28', 'moyenne'),
(107, 26, 'malfunctiona', 'aaaaaaaaaaaa', 'aaaaaaaaaaaa', 'batterie', 'ouverte', '2025-05-15 03:47:03', 'moyenne');

-- --------------------------------------------------------

--
-- Table structure for table `reponses`
--

CREATE TABLE `reponses` (
  `id` int(11) NOT NULL,
  `reclamation_id` int(11) DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `contenu` text NOT NULL,
  `role` enum('admin','utilisateur') NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reponses`
--

INSERT INTO `reponses` (`id`, `reclamation_id`, `utilisateur_id`, `contenu`, `role`, `date_creation`) VALUES
(46, 52, NULL, 'c\'est pas grave', 'admin', '2025-04-27 22:42:53'),
(47, 54, NULL, 'ok ont va voir', 'admin', '2025-05-02 08:08:05'),
(48, 54, NULL, 'okkkkkkkkkkkk', 'admin', '2025-05-02 11:16:08'),
(49, 54, NULL, 'sdfghjkiolm', 'admin', '2025-05-04 17:03:04'),
(50, 54, NULL, 'sedrftgyhujikopl', 'admin', '2025-05-04 17:03:07'),
(51, 54, NULL, 'zdqfghjklrfthgyhujkl', 'admin', '2025-05-04 17:03:11'),
(52, 54, 4, 'zertfhyjukijlompù', 'utilisateur', '2025-05-04 17:05:20'),
(53, 54, 4, 'êsrttyuiopp', 'utilisateur', '2025-05-04 17:05:25'),
(54, 54, 4, 'êrtyuiop^$', 'utilisateur', '2025-05-04 17:05:27'),
(55, 54, 4, 'ertyuiopo^p', 'utilisateur', '2025-05-04 17:05:29'),
(56, 54, 4, 'ersdtyuiop', 'utilisateur', '2025-05-04 17:05:31'),
(57, 54, 4, 'ersdtyuiop', 'utilisateur', '2025-05-04 17:05:38'),
(58, 54, NULL, 'rtgtfrdgthyju', 'admin', '2025-05-05 00:18:59'),
(59, 54, NULL, 'sdfghjklyu', 'admin', '2025-05-05 00:19:13'),
(64, 54, NULL, 'qzsedrftgyhujikolpm', 'admin', '2025-05-07 16:53:02'),
(65, 52, 4, 'yuijokpl^m', 'utilisateur', '2025-05-08 23:52:00'),
(66, 86, NULL, 'OK ON VA LE REGLER', 'admin', '2025-05-09 08:50:03'),
(67, 54, NULL, 'Bonjour,\r\n\r\nNous sommes désolés d\'apprendre que vous rencontrez un problème avec vos freins. Votre sécurité est notre priorité. Afin de résoudre ce problème rapidement, veuillez nous indiquer le modèle du véhicule concerné et la date d\'achat. Nous vous recommandons de ne plus utiliser le véhicule jusqu\'à ce que le problème soit résolu et de contacter un professionnel qualifié pour une inspection immédiate. Nous restons à votre disposition pour vous aider davantage.\r\n\r\nCordialement,\r\n[Votre Nom/Nom de l\'Entreprise]', 'admin', '2025-05-10 23:50:17'),
(68, 54, NULL, 'Bonjour, nous sommes désolés d\'apprendre que vous rencontrez des problèmes avec vos freins. Pourriez-vous nous fournir plus de détails concernant la situation, par exemple, quand le problème est-il apparu et dans quelles circonstances ? Nous vous recommandons de ne plus utiliser le véhicule tant que le problème n\'est pas résolu. Nous vous suggérons de nous contacter au [Numéro de téléphone] ou de prendre rendez-vous avec notre service d\'assistance via [Lien vers la prise de rendez-vous] afin qu\'un technicien qualifié puisse examiner votre véhicule au plus vite. Merci pour votre patience et votre compréhension.', 'admin', '2025-05-10 23:50:37'),
(69, 54, NULL, 'sssssssssss', 'admin', '2025-05-10 23:52:26'),
(70, 54, NULL, 'Bonjour,\r\n\r\nNous sommes désolés d\'apprendre que vous rencontrez un problème avec vos freins. La sécurité est notre priorité absolue. Afin de résoudre ce problème rapidement, veuillez nous indiquer la date et le numéro de votre commande ou de votre transaction, ainsi que plus de détails concernant le dysfonctionnement. Nous vous contacterons dans les plus brefs délais pour organiser une inspection ou une réparation. \r\n\r\nCordialement,\r\n[Your Company Name/Customer Service]', 'admin', '2025-05-10 23:52:49'),
(71, 52, NULL, 'Bonjour,\r\n\r\nNous sommes sincèrement désolés d\'apprendre que vous avez rencontré un problème avec une pédale qui s\'est détachée pendant votre trajet. Nous prenons cet incident très au sérieux.\r\n\r\nPour résoudre ce problème, nous vous proposons de [proposer une solution spécifique - ex: nous retourner le produit pour inspection/réparation; nous envoyer des photos de la pédale et du vélo pour évaluer la situation; vous fournir une nouvelle paire de pédales gratuitement; vous offrir un remboursement partiel]. \r\n\r\nVeuillez nous indiquer la solution que vous préférez. Nous restons à votre disposition pour toute question.\r\n\r\nCordialement,\r\n[Votre Nom/Nom de l\'Entreprise]', 'admin', '2025-05-11 16:59:57'),
(72, 86, NULL, 'mmmmmmmmmmmmmmmmm', 'admin', '2025-05-14 15:05:51'),
(73, 86, NULL, 'Thank you for reporting the issue with your device\'s screen. We understand that the display malfunction is preventing normal operation. To resolve this, we recommend that you [Option 1: bring the device to our service center for diagnostics and repair.] OR [Option 2: contact our technical support team at [phone number] or [email address] for troubleshooting assistance and potential remote diagnosis.]. Our technicians will thoroughly investigate the issue and determine the best course of action to restore your device to full functionality. We apologize for any inconvenience this may cause.', 'admin', '2025-05-14 15:29:56'),
(74, 93, NULL, 'Nous sommes désolés d\'apprendre que le chargeur fourni ne fonctionne pas. Veuillez nous excuser pour ce désagrément. Pour résoudre ce problème, nous vous proposons de vous envoyer un chargeur de remplacement immédiatement. Pourriez-vous nous confirmer votre adresse de livraison actuelle ? En attendant, si possible, merci de bien vouloir tester le chargeur sur une autre prise pour vérifier que le problème ne vient pas de la source d\'alimentation. Merci pour votre compréhension.', 'admin', '2025-05-14 22:53:06'),
(75, 104, 40, 'zzzzzzzzzzzzzzzzzzz', '', '2025-05-15 00:28:35'),
(76, 104, NULL, 'aaaaaaaaaaaaaaaaaaaaaaaaa', 'admin', '2025-05-15 00:59:47'),
(77, 89, NULL, 'aaaaaaaaaaaaaaaaaaaaaa', 'admin', '2025-05-15 01:02:42'),
(78, 106, NULL, 'Thank you for bringing this to our attention. We understand you\'re experiencing an issue with a test. To better understand the problem and find a resolution, could you please provide more details regarding the test you\'re referring to, such as the name, platform, and any specific error messages encountered? We\'re committed to resolving this issue promptly.', 'admin', '2025-05-15 01:25:25'),
(79, 106, NULL, 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa', 'admin', '2025-05-15 01:32:07'),
(80, 106, NULL, 'Thank you for bringing this to our attention. We understand you\'re experiencing an issue. To best assist you, could you please provide more details about the problem you are encountering? Knowing more will help us investigate and resolve the issue efficiently. We appreciate your patience and look forward to hearing from you.', 'admin', '2025-05-15 01:37:13'),
(81, 106, NULL, 'azqqqqqqqqqqqqqqqws', 'admin', '2025-05-15 01:41:06'),
(82, 106, NULL, 'qqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq', 'admin', '2025-05-15 01:50:02'),
(83, 106, NULL, 'dddddddddddddddddddddddd', 'admin', '2025-05-15 01:50:07');

-- --------------------------------------------------------

--
-- Table structure for table `reservation`
--

CREATE TABLE `reservation` (
  `id_reservation` int(11) NOT NULL,
  `id_client` int(11) NOT NULL,
  `id_velo` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `gouvernorat` varchar(50) DEFAULT NULL,
  `telephone` varchar(15) DEFAULT NULL,
  `duree_reservation` int(11) DEFAULT NULL,
  `date_reservation` date DEFAULT NULL,
  `statut` enum('en_attente','acceptee','refusee') DEFAULT 'en_attente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `reservation`
--

INSERT INTO `reservation` (`id_reservation`, `id_client`, `id_velo`, `date_debut`, `date_fin`, `gouvernorat`, `telephone`, `duree_reservation`, `date_reservation`, `statut`) VALUES
(108, 1, 69, '2025-04-29', '2025-04-30', 'Sousse', '23659788', 1, '2025-04-27', 'en_attente'),
(109, 1, 68, '2025-04-13', '2025-04-15', 'Tunis', '34566789', 2, '2025-04-27', 'acceptee'),
(126, 26, 3, '2025-05-08', '2025-05-17', 'Mahdia', '24531890', NULL, '2025-05-01', 'en_attente'),
(128, 26, 1, '2025-05-14', '2025-05-24', 'BÃ©ja', '24531890', NULL, '2025-05-01', 'acceptee'),
(132, 26, 1, '2025-05-16', '2025-05-17', 'Monastir', '24531890', NULL, '2025-05-01', 'acceptee'),
(133, 26, 2, '2025-05-22', '2025-05-25', 'sfax', '21458796', NULL, '2025-05-01', ''),
(134, 26, 1, '2025-05-11', '2025-05-30', 'MÃ©denine', '52757570', NULL, '2025-05-01', 'acceptee'),
(135, 26, 1, '2025-05-11', '2025-05-24', 'Le Kef', '21458796', NULL, '2025-05-01', 'refusee'),
(136, 26, 70, '2025-05-02', '2025-05-06', 'Mahdia', '24531890', NULL, '2025-05-01', 'refusee'),
(137, 26, 6, '2025-05-03', '2025-05-15', 'Le Kef', '21458796', NULL, '2025-05-01', 'refusee'),
(138, 26, 6, '2025-05-24', '2025-05-25', 'Nabeul', '21458796', NULL, '2025-05-02', 'acceptee'),
(139, 26, 6, '2025-05-17', '2025-05-25', 'La Manouba', '12345678', NULL, '2025-05-02', 'acceptee'),
(140, 1, 1, '2025-05-10', '2025-05-12', 'Tunis', '12345678', NULL, NULL, 'refusee'),
(141, 26, 73, '2025-05-11', '2025-05-24', 'Mahdia', '21458796', NULL, '2025-05-02', 'en_attente'),
(143, 26, 1, '2025-04-28', '2025-04-30', 'Mahdia', '24531890', NULL, '2025-05-02', 'en_attente'),
(144, 26, 6, '2025-04-29', '2025-04-30', 'La Manouba', '21458796', NULL, '2025-05-02', 'en_attente'),
(145, 52, 6, '2025-05-23', '2025-05-30', 'La Manouba', '11111111', NULL, '2025-05-04', ''),
(147, 50, 70, '2025-05-10', '2025-05-23', 'La Manouba', '325698777', NULL, '2025-05-04', ''),
(148, 50, 6, '2025-05-07', '2025-05-09', 'Mahdia', '25698445', NULL, '2025-05-04', 'acceptee'),
(149, 50, 73, '2025-05-07', '2025-05-17', 'GabÃ¨s', '14741471', NULL, '2025-05-04', 'acceptee'),
(152, 52, 78, '2025-05-09', '2025-05-17', 'GabÃ¨s', '24531890', NULL, '2025-05-04', 'refusee');

-- --------------------------------------------------------

--
-- Table structure for table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `bike_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `duration` int(11) NOT NULL,
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `bike_id`, `start_date`, `end_date`, `duration`, `status`) VALUES
(1, 26, 1, '2025-05-01', '2025-05-08', 7, 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `stations`
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
-- Dumping data for table `stations`
--

INSERT INTO `stations` (`id`, `name`, `location`, `status`, `created_at`, `updated_at`, `city`, `admin_id`) VALUES
(8, 'naaa le s1222', '36.820279,10.185099', 'active', '2025-04-17 15:52:14', '2025-05-05 14:02:20', 'Gouvernorat Tunis', NULL),
(9, 'le z en personne', '36.811484,10.135660', 'inactive', '2025-04-17 15:59:03', '2025-04-23 18:46:37', 'Gouvernorat Tunis', NULL),
(11, 'class1', '36.819454,10.182180', 'inactive', '2025-04-18 07:29:14', '2025-04-23 18:46:38', 'Gouvernorat Tunis', NULL),
(13, 'hamzaa 0302220', '36.801863,10.182009', 'active', '2025-04-18 09:17:50', '2025-04-23 18:46:40', 'Gouvernorat Tunis', NULL),
(15, 'mou7awla1', '36.855450,11.097565', 'inactive', '2025-04-23 17:37:36', '2025-04-23 18:46:42', 'Gouvernorat Nabeul', NULL),
(16, 'mou7awlaa 5', '37.274053,9.830017', 'inactive', '2025-04-23 17:37:53', '2025-04-24 09:58:57', 'Gouvernorat Bizerte', NULL),
(18, 'mou7awlaa 999', '35.697456,10.774841', 'active', '2025-04-23 17:38:31', '2025-04-24 11:28:44', 'Gouvernorat Monastir', NULL),
(20, 'yooooooo hamzaz', '34.746126,10.763855', 'active', '2025-04-23 18:30:55', '2025-04-23 18:46:48', 'Gouvernorat Sfax', NULL),
(21, 'ye rabi sahell77777', '35.474092,11.038513', 'active', '2025-04-23 18:32:35', '2025-04-23 18:46:49', 'Gouvernorat Mahdia', NULL),
(22, 'ppppp1', '36.802000,10.137892', 'active', '2025-04-28 20:25:47', '2025-04-28 20:26:08', 'Gouvernorat Tunis', NULL),
(53, 'nbnbbnb', '36.804337,10.162447', 'active', '2025-05-05 15:40:03', '2025-05-05 15:40:03', 'Gouvernorat Tunis', NULL),
(54, 'resauu', '36.803375,10.135333', 'active', '2025-05-06 09:00:12', '2025-05-06 09:00:12', 'Gouvernorat Tunis', NULL),
(55, 'aziz gathounn11', '36.795540,10.180807', 'inactive', '2025-05-06 15:52:15', '2025-05-06 15:52:56', 'Gouvernorat Tunis', NULL),
(56, 'tesst55', '36.905259,10.183232', 'active', '2025-05-09 06:55:28', '2025-05-09 06:55:28', 'Gouvernorat Ariana', NULL),
(57, 'bechir11', '37.288668,9.869413', 'active', '2025-05-09 17:56:39', '2025-05-09 17:58:24', 'Gouvernorat Bizerte', NULL),
(0, 'HBIB1', '36.865838,11.091432', 'active', '2025-05-12 22:10:53', '2025-05-12 22:11:15', 'Gouvernorat Nabeul', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `trajets`
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
-- Dumping data for table `trajets`
--

INSERT INTO `trajets` (`id`, `start_station_id`, `end_station_id`, `distance`, `description`, `route_coordinates`, `route_description`, `created_at`, `updated_at`, `start_point`, `end_point`, `start_point_name`, `end_point_name`, `co2_saved`, `battery_energy`, `fuel_saved`, `admin_id`) VALUES
(13, NULL, NULL, 2.49, 'skifon22222', '[{\"lat\":36.475567112326885,\"lng\":10.787732205825696},{\"lat\":36.48053620270968,\"lng\":10.80163632377545},{\"lat\":36.487989240824,\"lng\":10.815197130170896},{\"lat\":36.499581430494146,\"lng\":10.825324821023175},{\"lat\":36.5126895540891,\"lng\":10.83133277322367}]', 'Ce trajet entre Route Nationale Tunis - Bizerte, Zone Industrielle La Charguia I, Ech-Charguia, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 2035, Tunisie et Route Nationale Tunis - La Marsa, Cité Les Pins, La Marsa El Montazeh, Délégation La Marsa, Tunis, Gouvernorat Tunis, 2070, Tunisie met en avant la mobilité durable. Parcourez des paysages naturels, découvrez des points d\'intérêt locaux et profitez d\'un itinéraire respectueux de l\'environnement.', '2025-04-17 19:47:17', '2025-05-12 22:38:10', '36.47534427952087,10.786666199988215', '36.5125515827978,10.83081714511634', 'RR27, Beni Khiar, Délégation Beni Khiar, Gouvernorat Nabeul, 8060, Tunisie', 'RR27, Tazarka, Délégation Korba, Gouvernorat Nabeul, 8024, Tunisie', 398.4, 13.944, 0.18675, NULL),
(14, NULL, NULL, 2.26, 'hhihihihih', '[{\"lat\":36.83670551247481,\"lng\":10.152595639228823},{\"lat\":36.83891228736861,\"lng\":10.149130225181581}]', 'd;s,n;sn', '2025-04-17 19:55:54', '2025-04-17 20:39:12', '36.8389929203865,10.148942470550539', '36.83664366068045,10.152729749679567', 'Colisée Soula, El Manar, Délégation El Menzah, Tunis, Gouvernorat Tunis, 2092, Tunisie', 'X 4, Colisée Soula, El Manar, Délégation El Menzah, Tunis, Gouvernorat Tunis, 2092, Tunisie', 361.6, 12.656, 0.1695, NULL),
(15, NULL, NULL, 1.37, 'new new 1', '[{\"lat\":36.801586177322015,\"lng\":10.188810825347902},{\"lat\":36.80498803388556,\"lng\":10.18855333328247},{\"lat\":36.8086302582343,\"lng\":10.188252925872805},{\"lat\":36.81141335062683,\"lng\":10.188167095184328},{\"lat\":36.81368098073698,\"lng\":10.189454555511476}]', 'ballaa blaa blaa', '2025-04-18 07:30:29', '2025-04-18 07:30:56', '36.80116898848699,10.189561843872072', '36.81367614921016,10.189390182495119', 'Avenue Habib Bourguiba, Habib Thameur, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 2058, Tunisie', 'Route Nationale Bizerte - Tunis, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1073, Tunisie', 219.2, 7.672, 0.10275, NULL),
(17, NULL, NULL, 1.41, 'satar alahh', '[{\"lat\":36.80388107797049,\"lng\":10.230669379234316},{\"lat\":36.804645626896026,\"lng\":10.235443711280825}]', 'le 2ileha ela lahhhhhhhh', '2025-04-23 21:11:19', '2025-04-23 21:11:19', '36.80391799018142,10.230588912963869', '36.804722940407345,10.235443711280825', 'Route de La Goulette, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1053, Tunisie', 'Route de La Goulette, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1053, Tunisie', 225.6, 7.896, 0.10575, NULL),
(18, NULL, NULL, 1.16, 'test test45454', '[{\"lat\":36.80065837203224,\"lng\":10.188767910003664},{\"lat\":36.80519420215369,\"lng\":10.188810825347902},{\"lat\":36.80900821498964,\"lng\":10.188167095184328},{\"lat\":36.81106976444747,\"lng\":10.18786668777466}]', 'ahaaaahhahah', '2025-04-28 20:27:44', '2025-04-28 20:27:44', '36.8002068145812,10.189218521118166', '36.81120236742129,10.188016891479494', 'Hôtel El Bahy Tunis, Rue Christophe Colomb, Lac De Tunis, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 2058, Tunisie', 'Route Nationale Bizerte - Tunis, Les Jardins, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 1073, Tunisie', 185.6, 6.496, 0.087, NULL),
(34, NULL, NULL, 2.74, 'dawerrr', '[{\"lat\":36.80773689848675,\"lng\":10.253870487213137},{\"lat\":36.80275450873501,\"lng\":10.223701000213625}]', 'L\'itinéraire entre Route de La Goulette, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1055, Tunisie et Route de La Goulette, Lac De Tunis, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 1053, Tunisie traverse des zones naturelles protégées et met en lumière les efforts locaux pour la durabilité.', '2025-05-05 14:00:52', '2025-05-05 14:00:52', '36.80804130769649,10.254106521606447', '36.80240605146099,10.222692489624025', 'Route de La Goulette, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1055, Tunisie', 'Route de La Goulette, Lac De Tunis, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 1053, Tunisie', 438.4, 15.344, 0.2055, NULL),
(35, NULL, NULL, 3.37, 'ygiuhgfyut', '[{\"lat\":36.78350913241294,\"lng\":10.114824771881105},{\"lat\":36.784918332919275,\"lng\":10.119545459747314},{\"lat\":36.790829818506154,\"lng\":10.119287967681887},{\"lat\":36.79378539026806,\"lng\":10.13044595718384},{\"lat\":36.79588171506938,\"lng\":10.138556957244875},{\"lat\":36.79206704846779,\"lng\":10.140573978424074}]', 'Voyagez de façon durable entre Ezzahrouni, Délégation El Hrairia, Tunis, Gouvernorat Tunis, 1095, Tunisie et Route Nationale Tunis - Sakiet Sidi Youssef, Cite Tayarane, Délégation Ezzouhour, Tunis, Gouvernorat Tunis, 2052, Tunisie grâce à cet itinéraire pensé pour l\'écologie.', '2025-05-09 18:08:34', '2025-05-09 18:08:34', '36.78343555682716,10.114545822143556', '36.79237151996513,10.14115333557129', 'Ezzahrouni, Délégation El Hrairia, Tunis, Gouvernorat Tunis, 1095, Tunisie', 'Route Nationale Tunis - Sakiet Sidi Youssef, Cite Tayarane, Délégation Ezzouhour, Tunis, Gouvernorat Tunis, 2052, Tunisie', 539.2, 18.872, 0.25275, NULL),
(0, NULL, NULL, 2.08, 'sqdsqdsq', '[{\"lat\":36.80119602909762,\"lng\":10.213384136407047},{\"lat\":36.804769711014096,\"lng\":10.236343096601843}]', 'Ce trajet entre Route de La Goulette, Lac De Tunis, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 2058, Tunisie et Route de La Goulette, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1053, Tunisie est conçu pour sensibiliser à l\'écologie tout en offrant une expérience agréable et enrichissante.', '2025-05-12 22:23:35', '2025-05-12 22:23:35', '36.80124786543063,10.212830297822531', '36.80495899080918,10.236862106624562', 'Route de La Goulette, Lac De Tunis, Délégation Bab Bhar, Tunis, Gouvernorat Tunis, 2058, Tunisie', 'Route de La Goulette, El Bouhaira, Délégation Cité El Khadra, Tunis, Gouvernorat Tunis, 1053, Tunisie', 332.8, 11.648, 0.156, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `trash`
--

CREATE TABLE `trash` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `role` enum('admin','technicien','user') NOT NULL,
  `age` int(11) DEFAULT NULL,
  `gouvernorats` varchar(255) DEFAULT NULL,
  `cin` varchar(20) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Table structure for table `trash_users`
--

CREATE TABLE `trash_users` (
  `id` int(11) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `prenom` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `telephone` varchar(8) DEFAULT NULL,
  `role` enum('admin','technicien','user') NOT NULL,
  `age` int(11) DEFAULT NULL,
  `gouvernorats` varchar(255) DEFAULT NULL,
  `cin` varchar(8) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `trash_users`
--

INSERT INTO `trash_users` (`id`, `nom`, `prenom`, `email`, `telephone`, `role`, `age`, `gouvernorats`, `cin`, `photo`, `deleted_at`) VALUES
(23, 'jouini', 'hedi', 'emnaj9366@gmail.com', '24531890', 'technicien', 18, 'Ariana', '2587412', NULL, '2025-05-12 13:57:42'),
(33, 'jouini', 'ggggggggg', 'jjjngggaj936@gmail.com', '56329890', 'user', 5, 'Ariana', '14788478', NULL, '2025-04-27 16:48:39'),
(35, 'hinda', 'jammel', 'mmhmffa@espriit.tn', '21458796', 'user', 5, 'Ariana', '74222222', NULL, '2025-05-02 10:48:48'),
(37, 'maher', 'jouini', 'maher936@gmail.com', '24531890', 'user', 5, 'Ariana', '23345678', NULL, '2025-05-11 22:44:25');

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`id`, `email`, `password`, `role`) VALUES
(1, 'admin@gmaiil.com', 'emna', 'admin');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gouvernorats` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT 'default.jpg',
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('user','admin','technicien') NOT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `motdepasse` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `cin` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `nom`, `prenom`, `email`, `telephone`, `age`, `gouvernorats`, `photo`, `mot_de_passe`, `role`, `date_creation`, `motdepasse`, `password`, `created_at`, `cin`) VALUES
(12, 'emna', 'adminn', 'emna@admin.tn', '24531890', 25, 'Ariana', NULL, '$2y$10$OYS2CNQ2khU2BaxCKBB47u5U703gJzqhCwQaBscySnbl/0dxPI//S', 'technicien', '2025-04-16 22:27:40', NULL, '', '2025-04-24 09:41:42', '14785236'),
(20, 'emnouch', 'Admin', 'adminn@example.com', '96325874', 54, 'Beja', 'default.jpg', 'admin123', 'admin', '2025-04-17 07:13:20', NULL, '', '2025-04-24 09:41:42', '25639858'),
(26, 'amira', 'jouiniiiiiii', 'ab@cd.com', '24531890', 21, 'Nabeul', 'uploads/345632458_792028665599243_1372631530496978978_n.jpg', '123456', 'user', '2025-04-18 10:35:13', '12345', '12345', '2025-04-24 09:41:42', '25487965'),
(32, 'jouini', 'ggggggggg', 'emngggaj936@gmail.com', '56329890', 24, 'Ariana', NULL, '', 'user', '2025-05-02 10:49:08', NULL, '', '2025-05-02 11:49:08', '63698478'),
(36, 'jouini', 'emna', 'rrrnaj936@gmail.com', '24531890', 5, 'Ariana', NULL, '', 'user', '2025-05-11 22:44:34', NULL, '', '2025-05-11 23:44:34', '12345678'),
(38, 'ahlem', 'jouini', 'ahlem936@gmail.com', '24531890', 5, 'Ariana', 'Uploads/680cc1f924334.jpg', '$2y$10$2a4.HJimF8ZPJonQaNSoYejmgdPqBpMBEFyBg1xloylB9EhYY7w36', 'user', '2025-04-26 11:22:33', NULL, '', '2025-04-26 12:22:33', '99345678'),
(39, 'rahma', 'jammel', 'jjijhgfa@espriit.tn', '21458796', 5, 'Ariana', 'Uploads/680cc7ca6a735.jpg', '$2y$10$NOw21zPm9AneynXxnmFWkO5kk4iSXK81aSl9ct8VbxOijwT9Ija5K', 'user', '2025-04-26 11:47:22', NULL, '', '2025-04-26 12:47:22', '33222222'),
(40, 'rahma', 'jammel', 'Jouini.emnaa@esprit.tn', '21458796', 5, 'Ariana', 'Uploads/680cc82f06cf3.jpg', '$2y$10$7/OI/MuYm5dWP2WP2e76qOnb1wz10mc9OGNPdASBO2GsSTsH5Mr.6', 'user', '2025-04-26 11:49:03', NULL, '', '2025-04-26 12:49:03', '33222221'),
(41, 'jouini', 'emna', 'Jouini.emna@esprit.tn', '24531890', 5, 'Ariana', 'Uploads/680cc9b19f927.jpg', '$2y$10$MpzzqGhlDibm15o8eibKhugdBknwx.RXyVw6sQobKwghnmsDyH7Ke', 'user', '2025-04-26 11:55:29', NULL, '', '2025-04-26 12:55:29', '12365897'),
(42, 'jouini', 'emna', 'emnajouinniii2000@gmail.com', '24531890', 5, 'Ariana', 'Uploads/680cccdbbef26.jpg', '$2y$10$DJiccGXtjuaMZbR6ywxrd.IPWHgyRYjGGFgNsUyhWBQgWD6ZaXE7q', 'user', '2025-04-26 12:08:59', NULL, '', '2025-04-26 13:08:59', '22365897'),
(43, 'jouini', 'emna', 'emnajouinnnii2000@gmail.com', '24531890', 5, 'Ariana', 'Uploads/680ccfb272086.jpg', '$2y$10$3VlY/odUDmrCWlqulgKdRuPbIzUb2tCaJuhUN.chxnUG/QqEP1Z1m', 'user', '2025-04-26 12:21:06', NULL, '', '2025-04-26 13:21:06', '52365897'),
(44, 'jouini', 'emna', 'emnajouibbnii2000@gmail.com', '24531890', 5, 'Monastir', 'Uploads/680cd0657d2ba.jpg', '$2y$10$xmx6SK4hAtaHYMrUc5XXZuUMGnzsCw8HkEt8RUAdQUlVj8qFw246a', 'user', '2025-04-26 12:24:05', NULL, '', '2025-04-26 13:24:05', '21365897'),
(45, 'jouini', 'emna', 'emnajouiniiii2000@gmail.com\r\n\r\n', '24531890', 5, 'Ariana', 'Uploads/680cd0a6b8510.jpg', '$2y$10$eMtt7OhI7PzXcZfZwDAENuP.vqHhFobjrJj7sF8XFQvsISlDnro4O', 'user', '2025-04-26 12:25:10', NULL, '', '2025-04-26 13:25:10', '21565897'),
(46, 'jouini', 'emna', 'emnajounsii2000@gmail.com', '24531890', 5, 'Ariana', 'Uploads/680cd526ddea5.jpg', '$2y$10$S0UH.11KCHPg8pYQCC2X5e.CsYZnCBsS.Twa1ExruDzM1Ew/6Lzvy', 'user', '2025-04-26 12:44:22', NULL, '', '2025-04-26 13:44:22', '99565897'),
(47, 'jouini', 'emna', 'emnajouihhnii2000@gmail.com', '24531890', 5, 'Ariana', 'Uploads/680cd63af03df.jpg', '$2y$10$JEfsbEHS5bZdr7MvQfMY3eKkCw/OozVgSkB2Q.WY2O127clOtyY9i', 'user', '2025-04-26 12:48:58', NULL, '', '2025-04-26 13:48:58', '91565897'),
(48, 'jouini', 'emna', 'emnajouiinii2000@gmail.com', '24531890', 5, 'Ariana', 'Uploads/680cd89fe0f3c.jpg', '$2y$10$6UqLCdiJ0.BfZ9VSwSPV/.lHgg9TKbM8uegqPWAlQa3hJOAKBT5Dy', 'user', '2025-04-26 12:59:11', NULL, '', '2025-04-26 13:59:11', '91745897'),
(49, 'jouini', 'emna', 'emnaj9536@gmail.com', '33531890', 5, 'Sidi Bouzid', 'Uploads/680ce2feb5d8a.jpg', '$2y$10$uD/v44Df3spomh1raz.EjOuPoYC9FeI7s9bBE9iKk7oTq70TlNzO2', 'user', '2025-04-26 13:43:26', NULL, '', '2025-04-26 14:43:26', '36545897'),
(50, 'maryem', 'maryemmm', 'jouiniimrym@gmail.com', '41731890', 5, 'Ariana', 'Uploads/680d56b283c15_345632458_792028665599243_1372631530496978978_n.jpg', '$2y$10$7KuDp2R/5dpoXJyHWhWnyOAf3y/NNayPgZkMTxBKRJscszW98X0JG', 'user', '2025-04-26 21:57:06', NULL, '', '2025-04-26 22:57:06', '23658974'),
(51, 'maryem', 'maryem', 'ttttimrym@gmail.com', '41731890', 5, 'Ariana', 'Uploads/680d59fbd3b50.jpg', '$2y$10$0lGWVWiZG2qmXB4IhD2hq.zsIGXHANq/gmoj44/LH6qvYNvGgWQdC', 'user', '2025-04-26 22:11:07', NULL, '', '2025-04-26 23:11:07', '63658974'),
(52, 'fatma', 'ben tarjem', 'btarjemfatma1@gmail.com', '55698874', 14, 'Nabeul', 'Uploads/680d5a8edbd9e.jpg', '$2y$10$fQb47/rXRnQtmbvRwSLIpu7KtKnhf8MHfXl9z7a5J7AVeTrru/tBS', 'user', '2025-04-26 22:13:34', NULL, '', '2025-04-26 23:13:34', '22587412'),
(53, 'fatma', 'ben tarjem', 'emnaj93666666@gmail.com', '55698874', 5, 'Ariana', 'Uploads/680d5b5c75b7d.jpg', '$2y$10$ydoV.E0JaGOPyQmoElU5zeKdoPHB7FdiQa9o8HEi5FL5ewyMlaOuy', 'user', '2025-04-26 22:17:00', NULL, '', '2025-04-26 23:17:00', '11587412'),
(54, 'fatma', 'ben tarjem', 'emnaj93j6@gmail.com', '55698874', 5, 'Ariana', 'Uploads/680e6009093af.jpg', '$2y$10$Z5Ujbb7wNaZA4ENKkHjeqO0ApOuhnWhBo2v1cti3vtZyzamLkH4F.', 'user', '2025-04-27 16:49:13', NULL, '', '2025-04-27 17:49:13', '31587412'),
(55, 'fatma', 'ben tarjem', 'emnaj93sj6@gmail.com', '55698874', 23, 'Ariana', 'Uploads/680e89eac7e68.jpg', '$2y$10$IODgkwGGnglf28sDxP7BeescPU3NJoU7g9J7SaZ9UHGbD0CIZH1He', 'user', '2025-04-27 19:47:54', NULL, '', '2025-04-27 20:47:54', '61587412'),
(56, 'habib', 'znaidi', 'habib.znaidi@gmail.com', '21458796', 27, 'Bizerte', 'Uploads/680eb131d813f.jpg', '$2y$10$Tdgs8dTsAHtD.0QRfiFL3eoI1DJz7dqgDFP95Yr9WUGIRiCv838W6', 'user', '2025-04-27 22:35:29', NULL, '', '2025-04-27 23:35:29', '36985471'),
(57, 'Miaoui', 'Mohamed Aziz', 'hddhcyd@gmail.com', '56918997', 21, 'Bizerte', 'Uploads/680f90a66ecb7.jpg', '$2y$10$9agmnO6bAX9nYmB310huP.mMKh5rDj.hAoxhtY1z.bj.Kdf00kXxe', 'technicien', '2025-04-28 14:28:54', NULL, '', '2025-04-28 15:28:54', '11456672'),
(58, 'Miaoui', 'Mohamed Aziz', 'mmmhcyd@gmail.com', '56918997', 5, 'Ariana', 'Uploads/680ffefb149f2.jpg', '$2y$10$dYEkf1wfwe98TTFNSHWCN.lyXk3XnlVov6eeAcsZ4j0.Ncrsh4VWC', 'user', '2025-04-28 22:19:39', NULL, '', '2025-04-28 23:19:39', '33456672'),
(59, 'Miaoui', 'Mohamed Aziz', 'ooooooooohcyd@gmail.com', '56918997', 5, 'Ariana', 'Uploads/680fff1379590.jpg', '$2y$10$KZ45PiDdhudzzBTKFnOQj.cUHeHEJpsi74RKbrZV7Jy4L7Vu80mTq', 'user', '2025-04-28 22:20:03', NULL, '', '2025-04-28 23:20:03', '99456672'),
(60, 'Test', 'Admin', 'admin@test.com', NULL, NULL, NULL, 'default.jpg', 'hashed_password', 'admin', '2025-05-01 10:08:43', NULL, '', '2025-05-01 11:08:43', NULL),
(61, 'jouini', 'ghada', 'ghada.benkhalifa@esprit.tn', '24531890', 5, 'Ariana', 'Uploads/6814a0675a641.jpg', '$2y$10$j9WYyYioXnpoA66YJs4lU.UVo2lOHAW//FNliAGZzfuba9Ruj53.G', 'user', '2025-05-02 10:37:27', NULL, '', '2025-05-02 11:37:27', '22222222'),
(62, 'jouini', 'emna', 'emnaj936@gmail.com', '24531890', 5, 'Ariana', 'Uploads/681742023d782.jpg', '$2y$10$zd2FJvGxtpHF9wbG/ZIbPu3Xns/3/9kDopy53VxnLs3HGpyTIFDeK', 'user', '2025-05-04 10:31:30', NULL, '', '2025-05-04 11:31:30', '36987412'),
(63, 'jouini', 'emna', 'emnajouinii2000@gmail.com', '24531890', 5, 'Ariana', 'Uploads/681743a0251d8_IHXO0945.JPG', '$2y$10$2g1xq4EJKMtDCFvfbv5f1O48szWb.kHDIZDwhRAR7K9EpCD.Hojsy', 'user', '2025-05-04 10:38:24', NULL, '', '2025-05-04 11:38:24', '36987418'),
(64, 'jouini', 'emna', 'Jouini.kemnakkk@esprit.tn', '24531890', 5, 'Ariana', 'Uploads/682115c4a4206.jpg', '$2y$10$Bz8ArJD9E4eGP.0wXQPU4.QyQgK8EQ//8pyp4Tikh8celd2JvCOJK', 'user', '2025-05-11 21:25:24', NULL, '', '2025-05-11 22:25:24', '22222226'),
(65, 'slimani', 'hamza', 'zusslimani001122@gmail.com', '21788895', 5, 'Ariana', 'Uploads/6821f2a1c82ab_How To Draw Dizzy Emoji.jpg', '$2y$10$MgjlQ.eyWdp0E3e/5cJXeuseCGgZpES2RYGV6QIvOovy50MnfqeRi', 'user', '2025-05-12 13:07:45', NULL, '', '2025-05-12 15:07:45', '11450761');

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `nom`, `email`, `password`) VALUES
(1, 'connextion', 'connextion@connextion.com', 'connextion'),
(11, 'Dupont', 'jean.dupont@example.com', 'jean');

-- --------------------------------------------------------

--
-- Table structure for table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `age` int(11) DEFAULT NULL,
  `gouvernorats` varchar(100) DEFAULT NULL,
  `photo` varchar(255) DEFAULT 'default.jpg',
  `mot_de_passe` varchar(255) NOT NULL,
  `role` enum('utilisateur','admin','technicien') DEFAULT 'utilisateur',
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `nom`, `prenom`, `email`, `telephone`, `age`, `gouvernorats`, `photo`, `mot_de_passe`, `role`, `date_creation`) VALUES
(1, 'Admin', 'Emna J.', 'emnaj@gmail.com', 'e', 30, 'Tunis', 'emna.jpg', 'e', 'admin', '2025-04-16 20:41:55');

-- --------------------------------------------------------

--
-- Table structure for table `velos`
--

CREATE TABLE `velos` (
  `id_velo` int(11) NOT NULL,
  `nom_velo` varchar(255) NOT NULL,
  `type_velo` varchar(255) NOT NULL,
  `prix_par_jour` decimal(10,2) NOT NULL,
  `disponibilite` tinyint(1) DEFAULT 1,
  `etat_velo` varchar(255) NOT NULL DEFAULT 'NEUF'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `velos`
--

INSERT INTO `velos` (`id_velo`, `nom_velo`, `type_velo`, `prix_par_jour`, `disponibilite`, `etat_velo`) VALUES
(1, 'VTT Pro', 'Mountain Bike', 15.00, 1, 'excellent'),
(2, 'Vélo d\'Aventure', 'Adventure Bike', 20.00, 0, 'NEUF'),
(6, 'eee', 'Urbain', 12.00, 1, 'Excellent'),
(70, 'emna', 'eee', 50.00, 1, 'NEUF'),
(73, 'emna', 'eeevoici', 500.00, 1, 'BON'),
(75, 'emna', 'eee', 44.00, 1, 'NEUF'),
(76, 'pro', 'electro', 50.00, 0, 'NEUF'),
(77, 'eheheheh', 'eee', 60.00, 1, 'NEUF'),
(78, 'velo5', 'electro', 602.00, 1, 'NEUF');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `avis`
--
ALTER TABLE `avis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `bikes`
--
ALTER TABLE `bikes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `comments`
--
ALTER TABLE `comments`
  ADD PRIMARY KEY (`comment_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD PRIMARY KEY (`user_id`,`comment_id`),
  ADD KEY `comment_id` (`comment_id`);

--
-- Indexes for table `corbeille_velos`
--
ALTER TABLE `corbeille_velos`
  ADD PRIMARY KEY (`id_velo`);

--
-- Indexes for table `notification`
--
ALTER TABLE `notification`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notifications_user` (`user_id`),
  ADD KEY `fk_notifications_reclamation` (`reclamation_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `notification_reservation`
--
ALTER TABLE `notification_reservation`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reservation_id` (`reservation_id`);

--
-- Indexes for table `notif_comm`
--
ALTER TABLE `notif_comm`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `post_id` (`post_id`),
  ADD KEY `commenter_id` (`commenter_id`);

--
-- Indexes for table `pannes`
--
ALTER TABLE `pannes`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_reset`
--
ALTER TABLE `password_reset`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_email` (`email`(191)),
  ADD KEY `idx_token` (`token`(191));

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`post_id`),
  ADD KEY `id` (`id`);

--
-- Indexes for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD PRIMARY KEY (`user_id`,`post_id`),
  ADD KEY `post_id` (`post_id`);

--
-- Indexes for table `reactions`
--
ALTER TABLE `reactions`
  ADD PRIMARY KEY (`reaction_id`),
  ADD UNIQUE KEY `unique_reaction` (`user_id`,`target_id`,`target_type`);

--
-- Indexes for table `reclamations`
--
ALTER TABLE `reclamations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Indexes for table `reponses`
--
ALTER TABLE `reponses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `reclamation_id` (`reclamation_id`),
  ADD KEY `fk_reponses_utilisateur` (`utilisateur_id`);

--
-- Indexes for table `reservation`
--
ALTER TABLE `reservation`
  ADD PRIMARY KEY (`id_reservation`);

--
-- Indexes for table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `bike_id` (`bike_id`);

--
-- Indexes for table `trash`
--
ALTER TABLE `trash`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `trash_users`
--
ALTER TABLE `trash_users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`email`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `velos`
--
ALTER TABLE `velos`
  ADD PRIMARY KEY (`id_velo`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `avis`
--
ALTER TABLE `avis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `bikes`
--
ALTER TABLE `bikes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `comments`
--
ALTER TABLE `comments`
  MODIFY `comment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `corbeille_velos`
--
ALTER TABLE `corbeille_velos`
  MODIFY `id_velo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- AUTO_INCREMENT for table `notification`
--
ALTER TABLE `notification`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `notification_reservation`
--
ALTER TABLE `notification_reservation`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `notif_comm`
--
ALTER TABLE `notif_comm`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pannes`
--
ALTER TABLE `pannes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `password_reset`
--
ALTER TABLE `password_reset`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `post_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `reactions`
--
ALTER TABLE `reactions`
  MODIFY `reaction_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reclamations`
--
ALTER TABLE `reclamations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=108;

--
-- AUTO_INCREMENT for table `reponses`
--
ALTER TABLE `reponses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=84;

--
-- AUTO_INCREMENT for table `reservation`
--
ALTER TABLE `reservation`
  MODIFY `id_reservation` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=153;

--
-- AUTO_INCREMENT for table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `trash`
--
ALTER TABLE `trash`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `velos`
--
ALTER TABLE `velos`
  MODIFY `id_velo` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=80;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `comments`
--
ALTER TABLE `comments`
  ADD CONSTRAINT `comments_ibfk_1` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`),
  ADD CONSTRAINT `comments_ibfk_2` FOREIGN KEY (`id`) REFERENCES `users` (`id`);

--
-- Constraints for table `comment_likes`
--
ALTER TABLE `comment_likes`
  ADD CONSTRAINT `comment_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `comment_likes_ibfk_2` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`comment_id`);

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `notification_reservation`
--
ALTER TABLE `notification_reservation`
  ADD CONSTRAINT `notification_reservation_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `notification_reservation_ibfk_2` FOREIGN KEY (`reservation_id`) REFERENCES `reservation` (`id_reservation`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `notif_comm`
--
ALTER TABLE `notif_comm`
  ADD CONSTRAINT `notif_comm_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `notif_comm_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`),
  ADD CONSTRAINT `notif_comm_ibfk_3` FOREIGN KEY (`commenter_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `posts`
--
ALTER TABLE `posts`
  ADD CONSTRAINT `posts_ibfk_1` FOREIGN KEY (`id`) REFERENCES `users` (`id`);

--
-- Constraints for table `post_likes`
--
ALTER TABLE `post_likes`
  ADD CONSTRAINT `post_likes_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `post_likes_ibfk_2` FOREIGN KEY (`post_id`) REFERENCES `posts` (`post_id`);

--
-- Constraints for table `reactions`
--
ALTER TABLE `reactions`
  ADD CONSTRAINT `reactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`);

--
-- Constraints for table `trash`
--
ALTER TABLE `trash`
  ADD CONSTRAINT `trash_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
