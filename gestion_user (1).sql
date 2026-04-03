-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : ven. 03 avr. 2026 à 22:57
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `gestion_user`
--

-- --------------------------------------------------------

--
-- Structure de la table `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `logs`
--

INSERT INTO `logs` (`id`, `user_id`, `user_email`, `action`, `details`, `ip_address`, `created_at`) VALUES
(1, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.180', '2026-02-26 03:44:11'),
(2, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.180', '2026-02-26 03:49:28'),
(3, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.34', '2026-02-26 10:25:39'),
(4, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.34', '2026-02-26 10:29:03'),
(5, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.34', '2026-02-26 10:29:51'),
(6, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.34', '2026-02-26 10:33:12'),
(7, NULL, NULL, 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:33:12'),
(8, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:33:17'),
(9, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:35:12'),
(10, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.34', '2026-02-26 10:35:15'),
(11, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.34', '2026-02-26 10:46:53'),
(12, NULL, NULL, 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:46:53'),
(13, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:46:56'),
(14, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:47:00'),
(15, 1, 'admin@test.com', 'PAGINATION', 'Page 2/2 (20 par page)', '192.168.1.34', '2026-02-26 10:47:02'),
(16, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:47:05'),
(17, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:47:31'),
(18, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:47:31'),
(19, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:48:13'),
(20, 1, 'admin@test.com', 'PAGINATION', 'Page 2/2 (20 par page)', '192.168.1.34', '2026-02-26 10:48:53'),
(21, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:48:55'),
(22, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:49:03'),
(23, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-26 10:49:03'),
(24, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.34', '2026-02-26 10:58:32'),
(25, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.34', '2026-02-26 10:58:34'),
(26, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.34', '2026-02-26 11:03:28'),
(27, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-26 11:03:28'),
(28, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-26 11:04:38'),
(29, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-26 11:04:43'),
(30, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-26 11:04:47'),
(31, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.51', '2026-02-26 12:28:53'),
(32, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 12:28:53'),
(33, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.51', '2026-02-26 12:31:10'),
(34, NULL, NULL, 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 12:31:11'),
(35, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.51', '2026-02-26 12:38:01'),
(36, NULL, NULL, 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 12:38:01'),
(37, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 12:38:05'),
(38, 1, 'admin@test.com', 'IMPORT_CSV', 'Import de 3 utilisateurs sur 3 (Échecs: 0)', '192.168.1.51', '2026-02-26 12:41:06'),
(39, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 12:42:03'),
(40, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 12:42:43'),
(41, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 12:42:44'),
(42, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 12:42:48'),
(43, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie depuis l\'interface', '192.168.1.51', '2026-02-26 12:45:47'),
(44, NULL, NULL, 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 12:45:47'),
(45, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 12:45:53'),
(46, 1, 'admin@test.com', 'CREATION_UTILISATEUR', 'Ajout de l\'utilisateur: molkaaa@gmail.com', '192.168.1.51', '2026-02-26 12:46:45'),
(47, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 12:46:45'),
(48, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 12:46:45'),
(49, 1, 'admin@test.com', 'EXPORT_CSV', 'Export de 0 utilisateurs', '192.168.1.51', '2026-02-26 12:50:18'),
(50, 1, 'admin@test.com', 'EXPORT_CSV', 'Export de 0 utilisateurs', '192.168.1.51', '2026-02-26 12:50:25'),
(51, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:18:59'),
(52, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:19:00'),
(53, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:19:06'),
(54, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:19:11'),
(55, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:19:15'),
(56, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:19:22'),
(57, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:19:55'),
(58, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:19:56'),
(59, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:22:04'),
(60, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:22:05'),
(61, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:22:05'),
(62, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:25:07'),
(63, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:25:07'),
(64, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:25:07'),
(65, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:26:27'),
(66, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:26:27'),
(67, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:35:54'),
(68, NULL, 'molkaraissi@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: molkaraissi@gmail.com', '192.168.1.51', '2026-02-26 13:44:56'),
(69, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:45:00'),
(70, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:45:01'),
(71, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:45:02'),
(72, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:45:17'),
(73, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:45:17'),
(74, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:45:24'),
(75, 4, 'molkaraissi11@gmail.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur: molkaraissi11@gmail.com', '192.168.1.51', '2026-02-26 13:45:40'),
(76, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:45:40'),
(77, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:46:02'),
(78, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:46:03'),
(79, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:46:11'),
(80, 1, 'admin@test.com', 'PAGINATION', 'Page 2/2 (20 par page)', '192.168.1.51', '2026-02-26 13:46:16'),
(81, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:46:18'),
(82, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:46:20'),
(83, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:49:00'),
(84, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:50:50'),
(85, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:50:50'),
(86, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:51:32'),
(87, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 13:56:03'),
(88, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:56:03'),
(89, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:56:06'),
(90, 1, 'admin@test.com', 'PAGINATION', 'Page 2/2 (20 par page)', '192.168.1.51', '2026-02-26 13:56:13'),
(91, 1, 'admin@test.com', 'CHANGEMENT_STATUT', 'Statut changé de active à deactivated pour: molka@gmail.com', '192.168.1.51', '2026-02-26 13:56:19'),
(92, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:56:19'),
(93, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.51', '2026-02-26 13:56:35'),
(94, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.51', '2026-02-26 13:56:35'),
(95, 4, 'molkaraissi11@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: molkaraissi11@gmail.com', '192.168.1.51', '2026-02-26 13:57:09'),
(96, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:57:09'),
(97, 4, 'molkaraissi11@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: molkaraissi11@gmail.com', '192.168.1.51', '2026-02-26 13:58:09'),
(98, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:58:09'),
(99, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 13:58:20'),
(100, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '192.168.1.51', '2026-02-26 14:02:36'),
(101, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 14:02:36'),
(102, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 14:02:47'),
(103, 4, 'molkaraissi11@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: molkaraissi11@gmail.com', '192.168.1.51', '2026-02-26 14:19:34'),
(104, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.51', '2026-02-26 14:19:34'),
(105, NULL, 'molkaraissi11@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: molkaraissi11@gmail.com', '192.168.1.121', '2026-02-26 20:37:16'),
(106, NULL, 'molkaraissi11@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: molkaraissi11@gmail.com', '192.168.1.121', '2026-02-26 20:37:24'),
(107, NULL, 'molkaraissi11@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: molkaraissi11@gmail.com', '192.168.1.121', '2026-02-26 20:37:25'),
(108, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.121', '2026-02-26 20:37:40'),
(109, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-26 20:37:40'),
(110, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-26 20:37:50'),
(111, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-26 20:37:55'),
(112, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.121', '2026-02-26 20:39:19'),
(113, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-26 20:39:19'),
(114, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.121', '2026-02-26 21:01:34'),
(115, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-26 21:01:35'),
(116, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-26 22:15:43'),
(117, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-26 22:19:15'),
(118, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.121', '2026-02-26 22:19:16'),
(119, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-26 22:19:16'),
(120, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.121', '2026-02-26 22:31:16'),
(121, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-26 22:31:16'),
(122, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.121', '2026-02-26 22:31:36'),
(123, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.121', '2026-02-27 08:32:47'),
(124, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-27 08:32:47'),
(125, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.121', '2026-02-27 08:32:51'),
(126, NULL, 'molkaraissi11@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: molkaraissi11@gmail.com', '192.168.1.121', '2026-02-27 08:33:10'),
(127, NULL, 'molkaraissi11@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: molkaraissi11@gmail.com', '192.168.1.121', '2026-02-27 08:33:59'),
(128, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '192.168.1.121', '2026-02-27 08:34:07'),
(129, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.121', '2026-02-27 08:34:07'),
(130, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.121', '2026-02-27 08:34:07'),
(131, 4, 'molkaraissi11@gmail.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur: molkaraissi11@gmail.com', '192.168.1.121', '2026-02-27 08:34:14'),
(132, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.121', '2026-02-27 08:34:25'),
(133, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-27 08:34:26'),
(134, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-27 08:34:28'),
(135, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-27 08:34:36'),
(136, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.121', '2026-02-27 08:37:23'),
(137, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.121', '2026-02-27 08:37:23'),
(138, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.121', '2026-02-27 08:38:45'),
(139, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 10:36:36'),
(140, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 10:36:36'),
(141, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 10:36:57'),
(142, 1, 'admin@test.com', 'CREATION_UTILISATEUR', 'Ajout de l\'utilisateur: manelhosni@gmail.com', '192.168.1.34', '2026-02-27 10:38:16'),
(143, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 10:38:16'),
(144, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 10:38:16'),
(145, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:10:00'),
(146, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 11:10:01'),
(147, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 11:10:15'),
(148, 1, 'admin@test.com', 'CREATION_UTILISATEUR', 'Ajout de l\'utilisateur: hadir.trabelsi@gmail.com', '192.168.1.34', '2026-02-27 11:11:07'),
(149, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 11:11:07'),
(150, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 11:11:07'),
(151, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:18:43'),
(152, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 11:18:43'),
(153, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 11:18:49'),
(154, 1, 'admin@test.com', 'CREATION_UTILISATEUR', 'Ajout de l\'utilisateur: mxms@gmail.com', '192.168.1.34', '2026-02-27 11:19:16'),
(155, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 11:19:16'),
(156, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 11:19:16'),
(157, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:20:58'),
(158, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 11:20:58'),
(159, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 11:21:06'),
(160, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2 (20 par page)', '192.168.1.34', '2026-02-27 11:22:23'),
(161, NULL, 'molkaraissi11@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: molkaraissi11@gmail.com', '192.168.1.34', '2026-02-27 11:23:56'),
(162, NULL, 'molkaraissi11@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: molkaraissi11@gmail.com', '192.168.1.34', '2026-02-27 11:24:01'),
(163, 4, 'molkaraissi11@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: molkaraissi11@gmail.com', '192.168.1.34', '2026-02-27 11:24:34'),
(164, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 11:24:35'),
(165, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 11:24:35'),
(166, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:29:27'),
(167, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:29:27'),
(168, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:29:29'),
(169, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:30:58'),
(170, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:30:58'),
(171, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:31:00'),
(172, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:33:30'),
(173, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:33:30'),
(174, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:33:33'),
(175, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:37:52'),
(176, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:37:52'),
(177, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:37:56'),
(178, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:40:58'),
(179, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:40:58'),
(180, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:41:01'),
(181, 1, 'admin@test.com', 'CREATION_UTILISATEUR', 'Ajout de l\'utilisateur: nesrineallouch@gmail.com', '192.168.1.34', '2026-02-27 11:43:33'),
(182, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:43:33'),
(183, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:43:33'),
(184, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 11:43:33'),
(185, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 11:48:15'),
(186, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 11:48:20'),
(187, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 11:48:37'),
(188, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 11:48:38'),
(189, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 11:49:06'),
(190, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 11:49:07'),
(191, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:49:49'),
(192, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:49:57'),
(193, NULL, 'molkaraissi11@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: molkaraissi11@gmail.com', '192.168.1.34', '2026-02-27 11:52:26'),
(194, NULL, 'molkaraissi11@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: molkaraissi11@gmail.com', '192.168.1.34', '2026-02-27 11:52:29'),
(195, NULL, 'molkaraissi11@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: molkaraissi11@gmail.com', '192.168.1.34', '2026-02-27 11:52:31'),
(196, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:52:45'),
(197, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 11:57:31'),
(198, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 12:03:40'),
(199, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 12:03:46'),
(200, 26, 'glowandmbusiness@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: glowandmbusiness@gmail.com', '192.168.1.34', '2026-02-27 12:04:17'),
(201, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:04:17'),
(202, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:04:17'),
(203, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:04:22'),
(204, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:04:22'),
(205, 26, 'glowandmbusiness@gmail.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur: glowandmbusiness@gmail.com', '192.168.1.34', '2026-02-27 12:04:33'),
(206, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 12:06:32'),
(207, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:06:32'),
(208, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:06:40'),
(209, 1, 'admin@test.com', 'MODIFICATION_UTILISATEUR', 'Modification de l\'utilisateur: nesrineallouch@gmail.com', '192.168.1.34', '2026-02-27 12:06:53'),
(210, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:06:53'),
(211, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:06:53'),
(212, 1, 'admin@test.com', 'PAGINATION', 'Page 2/2', '192.168.1.34', '2026-02-27 12:06:58'),
(213, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:07:00'),
(214, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:07:02'),
(215, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 12:08:54'),
(216, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:08:54'),
(217, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 12:12:08'),
(218, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:12:08'),
(219, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:12:11'),
(220, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:12:19'),
(221, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.34', '2026-02-27 12:13:29'),
(222, 26, 'glowandmbusiness@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: glowandmbusiness@gmail.com', '192.168.1.34', '2026-02-27 12:13:42'),
(223, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:13:42'),
(224, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:13:42'),
(225, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 12:17:59'),
(226, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:17:59'),
(227, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:18:02'),
(228, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.34', '2026-02-27 12:18:43'),
(229, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 12:18:54'),
(230, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:18:54'),
(231, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:18:55'),
(232, 1, 'admin@test.com', 'CREATION_UTILISATEUR', 'Ajout de l\'utilisateur: professeur@test.com', '192.168.1.34', '2026-02-27 12:19:23'),
(233, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:19:23'),
(234, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 12:19:23'),
(235, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.34', '2026-02-27 12:19:32'),
(236, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 12:19:41'),
(237, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:19:41'),
(238, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:19:41'),
(239, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:20:00'),
(240, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:20:00'),
(241, NULL, 'professeur@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: professeur@test.com', '192.168.1.34', '2026-02-27 12:25:05'),
(242, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 12:25:11'),
(243, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:25:11'),
(244, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:25:11'),
(245, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 12:28:51'),
(246, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:28:51'),
(247, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:28:51'),
(248, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:30:06'),
(249, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:30:06'),
(250, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 12:31:30'),
(251, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:31:31'),
(252, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:31:31'),
(253, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:31:39'),
(254, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 12:31:39'),
(255, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:09:08'),
(256, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:09:08'),
(257, 38, 'professeur@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur: professeur@test.com', '192.168.1.34', '2026-02-27 13:09:11'),
(258, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:09:26'),
(259, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 13:09:26'),
(260, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 13:09:29'),
(261, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:11:52'),
(262, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:11:58'),
(263, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:11:58'),
(264, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:12:16'),
(265, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:12:21'),
(266, 38, 'professeur@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:12:21'),
(267, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:12:32'),
(268, 1, 'admin@test.com', 'CREATION_UTILISATEUR', 'Ajout de l\'utilisateur: psy@test.com', '192.168.1.34', '2026-02-27 13:13:39'),
(269, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 13:13:39'),
(270, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 13:13:39'),
(271, 1, 'admin@test.com', 'CREATION_UTILISATEUR', 'Ajout de l\'utilisateur: enfant@test.com', '192.168.1.34', '2026-02-27 13:14:11'),
(272, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 13:14:11'),
(273, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 13:14:11'),
(274, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:16:15'),
(275, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:16:22'),
(276, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:16:24'),
(277, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:20:50'),
(278, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:20:56'),
(279, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:22:16'),
(280, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:23:49'),
(281, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 13:23:49'),
(282, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 13:23:53'),
(283, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.34', '2026-02-27 13:24:13'),
(284, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:24:23'),
(285, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:24:39'),
(286, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:24:47'),
(287, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:24:47'),
(288, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:26:29'),
(289, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:26:36'),
(290, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:26:36'),
(291, 40, 'enfant@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur: enfant@test.com', '192.168.1.34', '2026-02-27 13:26:41'),
(292, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:26:52'),
(293, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:33:45'),
(294, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:33:52'),
(295, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:33:56'),
(296, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:33:56'),
(297, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:33:59'),
(298, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.34', '2026-02-27 13:33:59'),
(299, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:42:42'),
(300, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:42:51'),
(301, 40, 'enfant@test.com', 'DECONNEXION', 'Déconnexion de l\'enfant', '192.168.1.34', '2026-02-27 13:43:01'),
(302, 40, 'enfant@test.com', 'DECONNEXION', 'Déconnexion de l\'enfant', '192.168.1.34', '2026-02-27 13:43:04'),
(303, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 13:57:59'),
(304, 40, 'enfant@test.com', 'DECONNEXION', 'Déconnexion de l\'enfant', '192.168.1.34', '2026-02-27 13:58:19'),
(305, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:01:16'),
(306, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:01:52'),
(307, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:02:05'),
(308, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 14:02:05'),
(309, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 14:02:17'),
(310, 1, 'admin@test.com', 'MODIFICATION_UTILISATEUR', 'Modification de l\'utilisateur: psy@test.com', '192.168.1.34', '2026-02-27 14:02:31'),
(311, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 14:02:31'),
(312, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 14:02:31'),
(313, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.34', '2026-02-27 14:02:35'),
(314, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:02:53'),
(315, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:06:44'),
(316, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:06:46'),
(317, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:09:37'),
(318, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:12:05'),
(319, NULL, 'prof@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: prof@test.com', '192.168.1.34', '2026-02-27 14:12:13'),
(320, NULL, 'prof@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: prof@test.com', '192.168.1.34', '2026-02-27 14:12:14'),
(321, NULL, 'prof@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: prof@test.com', '192.168.1.34', '2026-02-27 14:12:18'),
(322, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:12:35'),
(323, 38, 'professeur@test.com', 'DECONNEXION', 'Déconnexion du professeur', '192.168.1.34', '2026-02-27 14:12:42'),
(324, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:13:19'),
(325, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:19:07'),
(326, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:19:24'),
(327, 40, 'enfant@test.com', 'DECONNEXION', 'Déconnexion de l\'enfant: enfant@test.com', '192.168.1.34', '2026-02-27 14:19:28'),
(328, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:22:05'),
(329, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:31:11'),
(330, NULL, 'parent@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: parent@test.com', '192.168.1.34', '2026-02-27 14:31:20'),
(331, NULL, 'parent@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: parent@test.com', '192.168.1.34', '2026-02-27 14:31:22'),
(332, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 14:31:31'),
(333, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 14:31:33'),
(334, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 14:31:33'),
(335, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 14:31:33'),
(336, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 14:31:34'),
(337, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:31:38'),
(338, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 14:31:39'),
(339, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 14:31:41'),
(340, 1, 'admin@test.com', 'MODIFICATION_UTILISATEUR', 'Modification de l\'utilisateur: enfant@test.com', '192.168.1.34', '2026-02-27 14:31:50'),
(341, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 14:31:50'),
(342, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 14:31:50'),
(343, 1, 'admin@test.com', 'CREATION_UTILISATEUR', 'Ajout de l\'utilisateur: parent@test.com', '192.168.1.34', '2026-02-27 14:32:15'),
(344, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 14:32:15'),
(345, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.34', '2026-02-27 14:32:15'),
(346, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.34', '2026-02-27 14:32:17'),
(347, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:32:27'),
(348, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:32:31'),
(349, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:42:09'),
(350, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:42:11'),
(351, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:44:12'),
(352, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:44:14'),
(353, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:44:31'),
(354, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 14:46:29'),
(355, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 15:12:37'),
(356, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 15:12:39'),
(357, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 15:18:02'),
(358, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 15:21:18'),
(359, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 15:21:45'),
(360, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 15:25:41'),
(361, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 15:30:35'),
(362, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 15:30:53'),
(363, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 15:35:22'),
(364, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.34', '2026-02-27 15:41:11'),
(365, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 15:41:18'),
(366, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 15:42:05'),
(367, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.34', '2026-02-27 16:28:28'),
(368, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 16:29:24'),
(369, 41, 'parent@test.com', 'DECONNEXION', 'Déconnexion du parent', '192.168.1.180', '2026-02-27 16:29:51'),
(370, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 16:30:15'),
(371, 39, 'psy@test.com', 'DECONNEXION', 'Déconnexion du psychologue', '192.168.1.180', '2026-02-27 16:30:36'),
(372, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 16:30:51'),
(373, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 16:31:03'),
(374, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 16:31:04'),
(375, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 16:31:43'),
(376, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 16:32:39'),
(377, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 16:33:07'),
(378, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 16:43:06'),
(379, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 16:55:15'),
(380, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.180', '2026-02-27 17:02:30'),
(381, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 17:02:35'),
(382, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 17:02:35'),
(383, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 17:02:57'),
(384, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 17:03:20'),
(385, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 17:08:38'),
(386, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 17:08:39'),
(387, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 17:09:58'),
(388, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 17:18:06'),
(389, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 17:18:06'),
(390, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 17:18:08'),
(391, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 17:23:46'),
(392, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 17:23:46'),
(393, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.180', '2026-02-27 17:24:01'),
(394, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 17:24:19'),
(395, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 17:34:20'),
(396, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 17:34:21'),
(397, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 17:34:26'),
(398, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 18:58:45'),
(399, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 18:58:45'),
(400, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 18:59:59'),
(401, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 18:59:59'),
(402, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 19:09:32'),
(403, 1, 'admin@test.com', 'PAGINATION', 'Page 1/2', '192.168.1.180', '2026-02-27 19:09:32'),
(404, NULL, 'psy@admin.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: psy@admin.com', '192.168.1.180', '2026-02-27 19:10:31'),
(405, NULL, 'psy@admin.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: psy@admin.com', '192.168.1.180', '2026-02-27 19:10:35'),
(406, NULL, 'psy@admin.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: psy@admin.com', '192.168.1.180', '2026-02-27 19:10:37'),
(407, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 19:12:51'),
(408, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.180', '2026-02-27 19:13:12'),
(409, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 19:13:24'),
(410, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 19:14:37'),
(411, 1, 'admin@test.com', 'CHANGEMENT_STATUT', 'Statut changé de active à deactivated pour: parent@test.com', '192.168.1.180', '2026-02-27 19:18:56'),
(412, 1, 'admin@test.com', 'CHANGEMENT_STATUT', 'Statut changé de deactivated à active pour: parent@test.com', '192.168.1.180', '2026-02-27 19:19:03'),
(413, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:29:51'),
(414, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:30:14'),
(415, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:32:49'),
(416, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:32:57'),
(417, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:35:34'),
(418, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:38:21'),
(419, 39, 'psy@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:39:45'),
(420, 39, 'psy@test.com', 'DECONNEXION', 'Déconnexion du psychologue', '192.168.1.180', '2026-02-27 21:40:14'),
(421, 40, 'enfant@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:40:27'),
(422, 40, 'enfant@test.com', 'DECONNEXION', 'Déconnexion de l\'enfant: enfant@test.com', '192.168.1.180', '2026-02-27 21:40:36'),
(423, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:40:48'),
(424, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:42:16'),
(425, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:44:38'),
(426, 38, 'professeur@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 21:57:01'),
(427, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.1.180', '2026-02-27 22:29:24'),
(428, 1, 'admin@test.com', 'CREATION_UTILISATEUR', 'Ajout de l\'utilisateur: Manelhosni813@gmail.com', '192.168.1.180', '2026-02-27 22:30:12'),
(429, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.180', '2026-02-27 22:30:16'),
(430, 4, 'molkaraissi11@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: molkaraissi11@gmail.com', '192.168.1.180', '2026-02-28 00:38:23'),
(431, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 00:38:23'),
(432, 4, 'molkaraissi11@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 00:38:23'),
(433, NULL, 'enfant@tes.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: enfant@tes.com', '192.168.1.180', '2026-02-28 00:52:20'),
(434, 26, 'glowandmbusiness@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: glowandmbusiness@gmail.com', '192.168.1.180', '2026-02-28 01:28:11'),
(435, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 01:28:11'),
(436, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 01:28:11'),
(437, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.180', '2026-02-28 01:47:16'),
(438, NULL, 'enfant@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: enfant@test.com', '192.168.1.180', '2026-02-28 01:47:34'),
(439, NULL, 'enfant@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: enfant@test.com', '192.168.1.180', '2026-02-28 01:47:35'),
(440, NULL, 'enfant@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: enfant@test.com', '192.168.1.180', '2026-02-28 01:47:35'),
(441, NULL, 'enfant@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: enfant@test.com', '192.168.1.180', '2026-02-28 01:47:35'),
(442, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:03:25'),
(443, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:03:25'),
(444, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur: admin@test.com', '192.168.1.180', '2026-02-28 02:03:31'),
(445, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:03:56');
INSERT INTO `logs` (`id`, `user_id`, `user_email`, `action`, `details`, `ip_address`, `created_at`) VALUES
(446, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:03:57'),
(447, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:04:55'),
(448, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:04:55'),
(449, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:04:55'),
(450, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:04:55'),
(451, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:04:55'),
(452, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:04:55'),
(453, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:04:55'),
(454, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:19:56'),
(455, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:19:56'),
(456, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:19:56'),
(457, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:19:56'),
(458, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:19:56'),
(459, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:19:56'),
(460, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:19:56'),
(461, 1, 'admin@test.com', 'MODIFICATION_UTILISATEUR', 'Modification de l\'utilisateur: molkaraissi11@gmail.com', '192.168.1.180', '2026-02-28 02:20:43'),
(462, 1, 'admin@test.com', 'EXPORT_CSV', 'Export de 35 utilisateurs', '192.168.1.180', '2026-02-28 02:21:57'),
(463, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:27:06'),
(464, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:27:06'),
(465, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:27:06'),
(466, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:27:06'),
(467, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:27:06'),
(468, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:27:06'),
(469, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:27:06'),
(470, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.180', '2026-02-28 02:27:22'),
(471, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:27:36'),
(472, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:27:37'),
(473, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '192.168.1.180', '2026-02-28 02:33:32'),
(474, 4, 'molkaraissi11@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: molkaraissi11@gmail.com', '192.168.1.180', '2026-02-28 02:33:47'),
(475, 4, 'molkaraissi11@gmail.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.180', '2026-02-28 02:34:12'),
(476, 26, 'glowandmbusiness@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: glowandmbusiness@gmail.com', '192.168.1.180', '2026-02-28 02:34:19'),
(477, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:34:19'),
(478, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:34:19'),
(479, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:34:21'),
(480, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:34:23'),
(481, 26, 'glowandmbusiness@gmail.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:34:23'),
(482, 26, 'glowandmbusiness@gmail.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur: glowandmbusiness@gmail.com', '192.168.1.180', '2026-02-28 02:34:28'),
(483, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:38:34'),
(484, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:38:35'),
(485, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:38:35'),
(486, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:38:35'),
(487, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:38:35'),
(488, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:38:35'),
(489, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:38:35'),
(490, 1, 'admin@test.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '192.168.1.180', '2026-02-28 02:38:47'),
(491, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:39:01'),
(492, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:39:01'),
(493, 40, 'enfant@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:39:04'),
(494, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(495, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(496, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(497, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(498, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(499, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(500, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(501, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(502, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(503, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(504, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(505, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:09'),
(506, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(507, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(508, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(509, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(510, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(511, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(512, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(513, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(514, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(515, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(516, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(517, 39, 'psy@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '192.168.1.180', '2026-02-28 02:40:10'),
(518, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 08:57:47'),
(519, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 08:57:48'),
(520, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 08:57:55'),
(521, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 08:59:30'),
(522, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 09:03:29'),
(523, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 09:08:56'),
(524, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 09:10:38'),
(525, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 09:14:32'),
(526, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 09:22:47'),
(527, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 09:26:50'),
(528, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 09:28:04'),
(529, NULL, 'parent@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: parent@test.com', '10.168.215.124', '2026-02-28 10:04:56'),
(530, NULL, 'parent@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: parent@test.com', '10.168.215.124', '2026-02-28 10:05:13'),
(531, NULL, 'parent@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: parent@test.com', '10.168.215.124', '2026-02-28 10:05:14'),
(532, 41, 'parent@test.com', 'CONNEXION', 'Connexion réussie', '10.168.215.124', '2026-02-28 10:05:26'),
(533, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '10.168.215.124', '2026-02-28 10:09:08'),
(534, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '10.168.215.124', '2026-02-28 10:09:08'),
(535, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '10.168.215.124', '2026-02-28 10:09:08'),
(536, 1, 'admin@test.com', 'DASHBOARD', 'Chargement du tableau de bord utilisateur', '10.168.215.124', '2026-02-28 10:09:08'),
(537, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '172.18.2.250', '2026-03-02 09:44:46'),
(538, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '172.18.2.250', '2026-03-02 09:44:54'),
(539, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '172.18.2.250', '2026-03-02 09:49:51'),
(540, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '172.18.2.250', '2026-03-02 10:31:10'),
(541, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '172.18.2.250', '2026-03-02 10:31:19'),
(542, NULL, 'admin@test', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test', '172.18.2.250', '2026-03-02 10:40:56'),
(543, NULL, 'fares@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: fares@gmail.com', '172.18.2.250', '2026-03-02 10:52:49'),
(544, NULL, 'fars@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: fars@gmail.com', '172.18.2.250', '2026-03-02 10:52:52'),
(545, NULL, 'fars@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: fars@gmail.com', '172.18.2.250', '2026-03-02 10:52:53'),
(546, NULL, 'fars@gmail.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: fars@gmail.com', '172.18.2.250', '2026-03-02 10:52:57'),
(547, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '172.18.2.250', '2026-03-02 10:56:50'),
(548, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '172.18.2.250', '2026-03-02 10:59:46'),
(549, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '192.168.56.1', '2026-03-02 13:00:28'),
(550, 1, 'admin@test.com', 'EXPORT_CSV', 'Export de 36 utilisateurs', '192.168.56.1', '2026-03-02 13:03:16'),
(551, 4, 'molkaraissi11@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: molkaraissi11@gmail.com', '172.18.2.250', '2026-03-02 13:39:48'),
(552, 4, 'molkaraissi11@gmail.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '172.18.2.250', '2026-03-02 13:39:53'),
(553, 4, 'molkaraissi11@gmail.com', 'CONNEXION_GOOGLE', 'Connexion Google réussie pour: molkaraissi11@gmail.com', '172.18.2.250', '2026-03-02 13:40:03'),
(554, 4, 'molkaraissi11@gmail.com', 'DECONNEXION', 'Déconnexion de l\'utilisateur', '172.18.2.250', '2026-03-02 13:40:08'),
(555, 4, 'molkaraissi11@gmail.com', 'CONNEXION', 'Connexion réussie', '172.18.2.250', '2026-03-02 13:41:48'),
(556, 4, 'molkaraissi11@gmail.com', 'CHANGEMENT_STATUT', 'Statut changé de merged à active pour: molkaraissi11@gmail.com', '172.18.2.250', '2026-03-02 13:42:39'),
(557, 4, 'molkaraissi11@gmail.com', 'MODIFICATION_UTILISATEUR', 'Modification de l\'utilisateur: malek@test.com', '172.18.2.250', '2026-03-02 13:46:48'),
(558, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '172.18.2.250', '2026-03-02 13:49:48'),
(559, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '172.18.2.250', '2026-03-02 14:06:15'),
(560, 1, 'admin@test.com', 'CREATION_UTILISATEUR', 'Ajout de l\'utilisateur: aaa@test.com', '172.18.2.250', '2026-03-02 14:06:40'),
(561, 1, 'admin@test.com', 'CONNEXION', 'Connexion réussie', '172.18.2.250', '2026-03-02 14:19:01'),
(562, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '172.18.2.250', '2026-03-02 14:48:09'),
(563, NULL, 'admin@test.com', 'ECHEC_CONNEXION', 'Tentative échouée pour: admin@test.com', '172.18.2.250', '2026-03-02 14:48:14');

-- --------------------------------------------------------

--
-- Structure de la table `merge_log`
--

CREATE TABLE `merge_log` (
  `id` int(11) NOT NULL,
  `primary_user_id` int(11) DEFAULT NULL,
  `merged_user_id` int(11) DEFAULT NULL,
  `merge_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `fields_merged` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `merge_log`
--

INSERT INTO `merge_log` (`id`, `primary_user_id`, `merged_user_id`, `merge_date`, `fields_merged`) VALUES
(1, 4, 4, '2026-02-23 08:48:51', 'Smart Merge: molkaraissi11@gmail.com');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','error') DEFAULT 'info',
  `is_read` tinyint(1) DEFAULT 0,
  `link` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `type`, `is_read`, `link`, `created_at`) VALUES
(1, 1, '👥 Nouvel utilisateur', 'L\'utilisateur aa aaa a été créé.', 'info', 1, '/users', '2026-02-26 12:46:45'),
(2, 33, '🎉 Bienvenue sur AutiCare !', 'Bienvenue aa ! Nous sommes ravis de vous accueillir.', 'success', 0, NULL, '2026-02-26 12:46:45'),
(3, 1, '👥 Nouvel utilisateur', 'L\'utilisateur hosni manel a été créé.', 'info', 1, '/users', '2026-02-27 10:38:16'),
(4, 34, '🎉 Bienvenue sur AutiCare !', 'Bienvenue hosni ! Nous sommes ravis de vous accueillir.', 'success', 0, NULL, '2026-02-27 10:38:16'),
(5, 1, '👥 Nouvel utilisateur', 'L\'utilisateur trabelsi hadir a été créé.', 'info', 1, '/users', '2026-02-27 11:11:07'),
(6, 35, '🎉 Bienvenue sur AutiCare !', 'Bienvenue trabelsi ! Nous sommes ravis de vous accueillir.', 'success', 0, NULL, '2026-02-27 11:11:07'),
(7, 1, '👥 Nouvel utilisateur', 'L\'utilisateur knfvkd lkdnzkc a été créé.', 'info', 1, '/users', '2026-02-27 11:19:16'),
(8, 36, '🎉 Bienvenue sur AutiCare !', 'Bienvenue knfvkd ! Nous sommes ravis de vous accueillir.', 'success', 0, NULL, '2026-02-27 11:19:16'),
(9, 1, '👥 Nouvel utilisateur', 'L\'utilisateur allouch nesrine a été créé.', 'info', 1, '/users', '2026-02-27 11:43:33'),
(10, 37, '🎉 Bienvenue sur AutiCare !', 'Bienvenue allouch ! Nous sommes ravis de vous accueillir.', 'success', 0, NULL, '2026-02-27 11:43:33'),
(11, 1, '✏️ Utilisateur modifié', 'L\'utilisateur allouch nesrine a été modifié.', 'info', 1, NULL, '2026-02-27 12:06:53'),
(12, 1, '👥 Nouvel utilisateur', 'L\'utilisateur professeur professeur a été créé.', 'info', 1, '/users', '2026-02-27 12:19:23'),
(13, 38, '🎉 Bienvenue sur AutiCare !', 'Bienvenue professeur ! Nous sommes ravis de vous accueillir.', 'success', 0, NULL, '2026-02-27 12:19:23'),
(14, 1, '👥 Nouvel utilisateur', 'L\'utilisateur psy psy a été créé.', 'info', 1, '/users', '2026-02-27 13:13:39'),
(15, 39, '🎉 Bienvenue sur AutiCare !', 'Bienvenue psy ! Nous sommes ravis de vous accueillir.', 'success', 0, NULL, '2026-02-27 13:13:39'),
(16, 1, '👥 Nouvel utilisateur', 'L\'utilisateur enfant enfant a été créé.', 'info', 1, '/users', '2026-02-27 13:14:11'),
(17, 40, '🎉 Bienvenue sur AutiCare !', 'Bienvenue enfant ! Nous sommes ravis de vous accueillir.', 'success', 0, NULL, '2026-02-27 13:14:11'),
(18, 1, '✏️ Utilisateur modifié', 'L\'utilisateur psy psy a été modifié.', 'info', 1, NULL, '2026-02-27 14:02:31'),
(19, 1, '✏️ Utilisateur modifié', 'L\'utilisateur enfant enfant a été modifié.', 'info', 1, NULL, '2026-02-27 14:31:50'),
(20, 1, '👥 Nouvel utilisateur', 'L\'utilisateur parent parent a été créé.', 'info', 1, '/users', '2026-02-27 14:32:15'),
(21, 41, '🎉 Bienvenue sur AutiCare !', 'Bienvenue parent ! Nous sommes ravis de vous accueillir.', 'success', 0, NULL, '2026-02-27 14:32:15'),
(22, 1, '👥 Nouvel utilisateur', 'L\'utilisateur hosni manel a été créé.', 'info', 1, '/users', '2026-02-27 22:30:12'),
(23, 42, '🎉 Bienvenue sur AutiCare !', 'Bienvenue hosni ! Nous sommes ravis de vous accueillir.', 'success', 0, NULL, '2026-02-27 22:30:12'),
(24, 1, '✏️ Utilisateur modifié', 'L\'utilisateur molka raissi a été modifié.', 'info', 1, NULL, '2026-02-28 02:20:43'),
(25, 4, '✏️ Utilisateur modifié', 'L\'utilisateur malek malek a été modifié.', 'info', 1, NULL, '2026-03-02 13:46:48'),
(26, 1, '👥 Nouvel utilisateur', 'L\'utilisateur aa aa a été créé.', 'info', 0, '/users', '2026-03-02 14:06:40'),
(27, 45, '🎉 Bienvenue sur AutiCare !', 'Bienvenue aa ! Nous sommes ravis de vous accueillir.', 'success', 0, NULL, '2026-03-02 14:06:40');

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `nom` varchar(50) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `role` enum('admin','user','professeur','psychologue','enfant','parent') DEFAULT 'user',
  `otp_code` varchar(6) DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `face_model_path` varchar(255) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `status` enum('active','merged','deactivated') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `otp_expiry` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `nom`, `prenom`, `email`, `password`, `role`, `otp_code`, `image_path`, `face_model_path`, `phone`, `status`, `created_at`, `otp_expiry`) VALUES
(1, 'Admin', 'Molka', 'admin@test.com', 'JAvlGPq9JyTdtvBO6x2llnRI1+gxwIyPqCKAn3THIKk=', 'admin', NULL, NULL, 'models/user_1_model.xml', '', 'active', '2026-02-23 00:32:24', NULL),
(3, 'mariem', 'Mariem', '', 'iqGfw4oQFa+qziLWiHO/pUImPK/d6yW+L9QU+JzeyWk=', 'user', NULL, NULL, NULL, NULL, 'active', '2026-02-23 00:32:24', NULL),
(4, 'raissi', 'molka', 'molkaraissi11@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'admin', '374584', 'C:\\Users\\Molka\\Downloads\\USER\\uploads\\profiles\\u_1771836448186_426097984_1389551015012138_5360244664267240294_n.jpg', NULL, '', 'active', '2026-02-23 00:32:24', '2026-03-02 14:51:00'),
(5, 'molka', 'raissi', 'molka@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'admin', NULL, NULL, NULL, NULL, 'deactivated', '2026-02-23 00:32:24', NULL),
(8, 'aaaaa', 'azdaaa', 'molkaraissi11g@mail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'user', NULL, NULL, NULL, NULL, 'active', '2026-02-23 00:32:24', NULL),
(10, 'Utilisateur', 'mohamed', 'ahmed@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'user', NULL, NULL, NULL, NULL, 'active', '2026-02-23 00:32:24', NULL),
(11, 'Utilisateur', 'manel', 'manel@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'user', NULL, NULL, NULL, NULL, 'active', '2026-02-23 00:32:24', NULL),
(12, 'malek', 'raissi', 'malek@gmail', 'Wec/ihckBjkiA9EQpoyeg0qJqI/pAwdocEF5n4DDkas=', 'user', NULL, 'C:\\Users\\Molka\\Downloads\\USER\\uploads\\profiles\\user_12_1771845632140_logo.jpg', NULL, NULL, 'active', '2026-02-23 00:32:24', NULL),
(13, 'mmmm', 'malekhaj', 'malekhaj@gmail.Com', 'J+zhrlKxaCTPTV7m0S4qRCdXeSWvA3gOXFQO2bVB+V8=', 'user', NULL, NULL, NULL, NULL, 'deactivated', '2026-02-23 00:32:24', NULL),
(14, '&&&', 'lkfslqf', 'molk@g', 'U0pKjq/NhImvMjVtWnol+Ixwz+BEhTmnxClkwbiXo1k=', 'user', NULL, NULL, NULL, NULL, 'active', '2026-02-23 00:32:24', NULL),
(18, 'ééé', 'qqqq', 'ssss@cc.tn', '123456', 'user', NULL, NULL, NULL, NULL, 'active', '2026-02-23 00:32:24', NULL),
(19, 'aaa', 'azzee', 'azzeee@gmail.Com', 'XfHsWkX4yHt5/1C49WLWMwtjkdVjKPo3tTcnb3CND2o=', 'user', NULL, NULL, NULL, NULL, 'deactivated', '2026-02-23 00:32:24', NULL),
(20, 'Utilisateur', 'mohamed', 'mohameddd@gmail.com', 'qI3M9Vnd9duUAQqia+zVLtpcgCOa5EWyUMTWSGFpfi0=', 'user', NULL, 'C:\\Users\\Molka\\Downloads\\USER\\uploads\\profiles\\user_20_1771804486616_logo.jpg', NULL, NULL, 'active', '2026-02-23 00:32:24', NULL),
(21, 'aaa', 'aa', 'aa@.com', '5/wKksdgwAJtYpQrdZNO897SpB7c1ytUXfjUfwKD6x0=', 'user', NULL, NULL, NULL, NULL, 'deactivated', '2026-02-23 00:32:24', NULL),
(22, 'Allouch', 'Nesrine', 'nesrine@gmail.com', 'J+zhrlKxaCTPTV7m0S4qRCdXeSWvA3gOXFQO2bVB+V8=', 'user', NULL, 'C:\\Users\\Molka\\Downloads\\USER\\uploads\\profiles\\u_1771807109933_78402280-f75c-4efb-890a-672223bfcdec.jfif', NULL, '23', 'active', '2026-02-23 00:39:01', NULL),
(24, 'Utilisateur', 'molka', 'molkaraissi1@gmail.com', 'hqnPJPk8r/BFLg1wQ+0sAFHVT5+xtJOJEr/znwupXWg=', 'user', '528558', NULL, NULL, '98115123', 'active', '2026-02-25 10:53:20', '2026-02-25 12:23:00'),
(25, 'Utilisateur', 'mayssem', 'maysem@gmail.com', 'hqnPJPk8r/BFLg1wQ+0sAFHVT5+xtJOJEr/znwupXWg=', 'user', NULL, NULL, NULL, 'mayssem', 'active', '2026-02-25 14:02:13', NULL),
(26, 'and m', 'Glow', 'glowandmbusiness@gmail.com', '47DEQpj8HBSa+/TImW+5JCeuQeRkm5NMpJWZG3hSuFU=', 'user', NULL, 'C:\\Users\\Molka\\Downloads\\USER\\uploads\\profiles\\google_glowandmbusiness_gmail.com_1772068242225.jpg', NULL, NULL, 'active', '2026-02-26 01:10:42', NULL),
(27, 'aaa', '123', 'aaa', 'pmWkWSBCL51Bfkhn79xPuKBKHz//H6B+mY6G9/eieuM=', 'user', NULL, 'C:\\Users\\Molka\\Downloads\\USER\\uploads\\profiles\\u_1772073714511_logo.jpg', NULL, '123', 'active', '2026-02-26 02:42:07', NULL),
(28, 'ddd', '5c', 'ddd', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'user', NULL, NULL, NULL, '123', 'active', '2026-02-26 02:55:06', NULL),
(29, 'edf', 'aaa', 'molkaa@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'user', NULL, 'C:\\Users\\Molka\\Downloads\\USER\\uploads\\profiles\\user_1772075678524_google-icon.png', NULL, '22222222', 'active', '2026-02-26 03:14:40', NULL),
(30, 'Dupont', 'Jean', 'jean.dupont@gmail.com', 'p3xWahiiB+1Db0+nb0jnX3Mq3JCKiM/oRbZfL7INQxQ=', 'user', NULL, NULL, NULL, '0612345678', 'active', '2026-02-26 12:41:06', NULL),
(31, 'Martin', 'Marie', 'marie.martin@outlook.fr', 'rP6zHklds7c8xRMOTX8YAP0+HdqT67XElCt2LpE+/Oo=', 'admin', NULL, NULL, NULL, '0687654321', 'active', '2026-02-26 12:41:06', NULL),
(32, 'BenAli', 'Sami', 'sami.benali@topnet.tn', 'r5H5jsT7mJOSPb9FO7CDrzmKD9+ooDTnlVrqj+DAQ18=', 'user', NULL, NULL, NULL, '0712345678', 'active', '2026-02-26 12:41:06', NULL),
(33, 'aaa', 'aa', 'molkaaa@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'user', NULL, 'C:\\Users\\Molka\\Downloads\\USER\\uploads\\profiles\\user_1772109961537_820ad6c34de26e510becbaffc45d8d4b.jpg', NULL, '12345678', 'active', '2026-02-26 12:46:45', NULL),
(34, 'manel', 'hosni', 'manelhosni@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'professeur', NULL, 'C:\\Users\\Molka\\Downloads\\USER\\uploads\\profiles\\user_1772188634860_logo.jpg', NULL, '94397345', 'active', '2026-02-27 10:38:16', NULL),
(35, 'hadir', 'trabelsi', 'hadir.trabelsi@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'psychologue', NULL, NULL, NULL, '98115124', 'active', '2026-02-27 11:11:07', NULL),
(36, 'lkdnzkc', 'knfvkd', 'mxms@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'enfant', NULL, NULL, NULL, '777777777777', 'active', '2026-02-27 11:19:16', NULL),
(37, 'nesrine', 'allouch', 'nesrineallouch@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'enfant', NULL, 'C:\\Users\\Molka\\Downloads\\USER\\uploads\\profiles\\user_1772192604227_fb4c933a9e6a1a1cb5cef947365029bc.jpg', NULL, '123456789', 'active', '2026-02-27 11:43:32', NULL),
(38, 'professeur', 'professeur', 'professeur@test.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'professeur', NULL, NULL, NULL, '123456789', 'active', '2026-02-27 12:19:23', NULL),
(39, 'psy', 'psy', 'psy@test.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'psychologue', NULL, NULL, 'models/user_39_model.xml', '', 'active', '2026-02-27 13:13:39', NULL),
(40, 'enfant', 'enfant', 'enfant@test.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'enfant', NULL, NULL, 'models/user_40_model.xml', '12345678', 'active', '2026-02-27 13:14:11', NULL),
(41, 'malek', 'malek', 'malek@test.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'professeur', NULL, NULL, 'models/user_41_model.xml', '22555666', 'active', '2026-02-27 14:32:15', NULL),
(42, 'manel', 'hosni', 'Manelhosni813@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'professeur', '492408', NULL, NULL, '94397345', 'active', '2026-02-27 22:30:12', '2026-02-27 23:51:32'),
(43, 'Utilisateur', 'feres', 'feres@gmail.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'enfant', NULL, NULL, NULL, '99966666', 'active', '2026-03-02 10:52:14', NULL),
(44, 'Utilisateur', 'talel', 'talel@test.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'parent', NULL, NULL, 'models/user_44_model.xml', '55666999', 'active', '2026-03-02 10:53:32', NULL),
(45, 'aa', 'aa', 'aaa@test.com', 'jZae727K08KaOmKSgOaGzww/XVqGr/PKEgIMkjrcbJI=', 'enfant', NULL, NULL, NULL, '', 'active', '2026-03-02 14:06:40', NULL),
(46, 'Utilisateur', 'ridha', 'metjaku@gmail.com', 't8LTg5Yzl4FUgsX3QZNAuGDPQnZuLAj8byrI1wLbKQY=', 'parent', NULL, NULL, NULL, '58249469', 'active', '2026-03-02 14:49:43', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password_hash` varchar(255) DEFAULT NULL,
  `face_model_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_logs_user` (`user_id`),
  ADD KEY `idx_logs_action` (`action`),
  ADD KEY `idx_logs_date` (`created_at`);

--
-- Index pour la table `merge_log`
--
ALTER TABLE `merge_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `primary_user_id` (`primary_user_id`),
  ADD KEY `merged_user_id` (`merged_user_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user` (`user_id`,`is_read`),
  ADD KEY `idx_notifications_date` (`created_at`);

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=564;

--
-- AUTO_INCREMENT pour la table `merge_log`
--
ALTER TABLE `merge_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=47;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `logs`
--
ALTER TABLE `logs`
  ADD CONSTRAINT `logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `merge_log`
--
ALTER TABLE `merge_log`
  ADD CONSTRAINT `merge_log_ibfk_1` FOREIGN KEY (`primary_user_id`) REFERENCES `user` (`id`),
  ADD CONSTRAINT `merge_log_ibfk_2` FOREIGN KEY (`merged_user_id`) REFERENCES `user` (`id`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
