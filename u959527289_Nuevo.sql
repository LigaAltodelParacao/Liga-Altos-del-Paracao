-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 17-10-2025 a las 11:38:47
-- Versión del servidor: 11.8.3-MariaDB-log
-- Versión de PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `u959527289_Nuevo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `campeonatos`
--

CREATE TABLE `campeonatos` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `campeonatos`
--

INSERT INTO `campeonatos` (`id`, `nombre`, `descripcion`, `fecha_inicio`, `fecha_fin`, `activo`, `created_at`) VALUES
(1, 'Torneo Clausura 2025 - M40', 'Campeonato Clausura 2025', '2025-08-30', NULL, 1, '2025-09-15 01:30:36'),
(3, 'Torneo Clausura 2025 - M30 A', '', '2025-08-23', NULL, 1, '2025-09-15 21:32:50'),
(4, 'Torneo Clausura 2025 - M30 B', '', '2025-08-23', NULL, 1, '2025-09-15 21:33:10');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `canchas`
--

CREATE TABLE `canchas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `ubicacion` varchar(200) DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `canchas`
--

INSERT INTO `canchas` (`id`, `nombre`, `ubicacion`, `activa`) VALUES
(17, 'Cancha 1 - Parera', 'Parera', 1),
(18, 'Cancha 2 - Parera', 'Parera', 1),
(19, 'Cancha 3 - Parera', 'Parera', 1),
(20, 'Cancha 4 - Parera', 'Parera', 1),
(21, 'Cancha 5 - Parera', 'Parera', 1),
(22, 'Cancha 6 - Parera', 'Parera', 1),
(23, 'Cancha 7 - Parera', 'Parera', 1),
(24, 'Cancha 8 - Parera', 'Parera', 1),
(25, 'Cancha 8 - A - Parera', 'Parera', 1),
(26, 'Cancha 8 - B - Parera', 'Parera', 1),
(27, 'Cancha 8 - C- Parera', 'Parera', 1),
(28, 'Cancha 9 - Parera', 'Parera', 1),
(29, 'Cancha 10 - Parera', 'Parera', 1),
(30, 'Cancha 1 - Ramírez', 'Ramírez', 1),
(31, 'Cancha 2 - Ramírez', 'Ramírez', 1),
(32, 'Cancha 3 - Ramírez', 'Ramírez', 1),
(33, 'Cancha 4 - Ramírez', 'Ramírez', 1),
(34, 'Cancha 4 - A - Ramírez', 'Ramírez', 1),
(35, 'Cancha 4 - B - Ramírez', 'Ramírez', 1),
(36, 'Cancha 4 - C - Ramírez', 'Ramírez', 1),
(37, 'Cancha 5 - Ramírez', 'Ramírez', 1),
(38, 'Cancha 6 - Ramírez', 'Ramírez', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL,
  `campeonato_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `campeonato_id`, `nombre`, `descripcion`, `activa`) VALUES
(1, 1, 'M40', 'Categoría M40', 1),
(2, 3, 'M30 A', 'Categoría M30 A', 1),
(3, 4, 'M30 B', 'Categoría M30 B', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `codigos_cancha`
--

CREATE TABLE `codigos_cancha` (
  `id` int(11) NOT NULL,
  `cancha_id` int(11) NOT NULL,
  `codigo` varchar(10) NOT NULL,
  `fecha_partidos` date NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `usado` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `expires_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `codigos_cancha`
--

INSERT INTO `codigos_cancha` (`id`, `cancha_id`, `codigo`, `fecha_partidos`, `activo`, `usado`, `created_at`, `expires_at`) VALUES
(338, 17, 'R4VWAN', '2025-09-23', 1, 1, '2025-09-23 23:42:24', '2025-09-24 00:59:00'),
(339, 18, '4PB8MQ', '2025-09-23', 1, 0, '2025-09-23 23:42:24', '2025-09-23 18:00:00'),
(810, 17, 'Z8I0A5', '2025-09-24', 1, 0, '2025-09-25 01:21:52', '2025-09-25 00:50:00'),
(811, 30, 'GZFD5R', '2025-09-24', 1, 0, '2025-09-25 01:21:52', '2025-09-24 14:30:00'),
(812, 18, '3O8PUT', '2025-09-24', 1, 0, '2025-09-25 01:21:52', '2025-09-25 00:10:00'),
(813, 32, 'TAR7SX', '2025-09-24', 1, 0, '2025-09-25 01:21:52', '2025-09-24 16:50:00'),
(814, 34, 'WTA5QH', '2025-09-24', 1, 0, '2025-09-25 01:21:52', '2025-09-25 00:50:00'),
(844, 17, 'G8PALR', '2025-09-25', 1, 0, '2025-09-25 12:47:55', '2025-09-25 19:10:00'),
(845, 30, 'WS914J', '2025-09-25', 1, 0, '2025-09-25 12:47:55', '2025-09-25 16:50:00'),
(849, 17, 'H79KXV', '2025-10-08', 1, 1, '2025-10-08 23:05:22', '2025-10-09 00:50:00'),
(850, 30, 'Q59UXB', '2025-10-08', 1, 0, '2025-10-08 23:05:22', '2025-10-09 00:50:00'),
(851, 17, '4L8ARB', '2025-10-11', 1, 1, '2025-10-09 22:44:17', '2025-10-11 18:00:00'),
(942, 17, 'UGFS2X', '2025-10-15', 1, 0, '2025-10-15 23:07:05', '2025-10-16 00:50:00'),
(943, 30, 'AB13W0', '2025-10-15', 1, 0, '2025-10-15 23:07:05', '2025-10-16 00:30:00'),
(1026, 17, 'V29R1F', '2025-10-16', 1, 0, '2025-10-17 00:37:54', '2025-10-17 00:50:00'),
(1027, 30, 'XPV7K2', '2025-10-16', 1, 0, '2025-10-17 00:37:54', '2025-10-17 00:50:00'),
(1220, 17, 'GBQLW5', '2025-10-18', 1, 0, '2025-10-17 06:02:39', '2025-10-19 00:40:00'),
(1221, 20, '9LY7P5', '2025-10-18', 1, 0, '2025-10-17 06:02:39', '2025-10-19 00:30:00'),
(1222, 21, 'UW0C1H', '2025-10-18', 1, 0, '2025-10-17 06:02:39', '2025-10-19 00:50:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos`
--

CREATE TABLE `equipos` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `logo` varchar(255) DEFAULT NULL,
  `color_camiseta` varchar(50) DEFAULT NULL,
  `director_tecnico` varchar(100) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `equipos`
--

INSERT INTO `equipos` (`id`, `categoria_id`, `nombre`, `logo`, `color_camiseta`, `director_tecnico`, `activo`, `created_at`) VALUES
(1, 1, 'Distribuidora Tata', 'equipos/Distribuidora_Tata.png', '#2e8e0b', '', 1, '2025-09-15 01:30:36'),
(2, 1, 'La Pingüina M40', 'equipos/La_Ping__ina_M40.png', '#de2e02', '', 1, '2025-09-15 01:30:36'),
(3, 1, 'Nono Gringo M40', 'equipos/Nono_Gringo_M40.png', '#d4e0ed', '', 1, '2025-09-15 01:30:36'),
(4, 1, 'Camioneros M40', 'equipos/Camioneros_M40.png', '#11c304', '', 1, '2025-09-15 01:30:36'),
(5, 1, 'AVA M40', 'equipos/AVA_M40.png', '#fafafa', '', 1, '2025-09-15 01:30:36'),
(6, 1, 'Agrupación Roma M40', 'equipos/Agrupaci__n_Roma_M40.png', '#f58656', '', 1, '2025-09-15 01:30:36'),
(7, 1, 'Avenida Distribuciones M40', 'equipos/Avenida_Distribuciones_M40.png', '#368ce7', '', 1, '2025-09-15 01:30:36'),
(8, 1, 'Taladro M40', 'equipos/Taladro_M40.png', '#219712', '', 1, '2025-09-15 01:30:36'),
(9, 1, 'Villa Urquiza M40', 'equipos/Villa_Urquiza_M40.png', '#0a4d94', '', 1, '2025-09-15 01:30:36'),
(10, 1, 'AFCAPER M40', 'equipos/AFCAPER_M40.png', '#71051a', '', 1, '2025-09-15 01:30:36'),
(11, 1, 'Farmacia Abril', 'equipos/Farmaci_Abril.png', '#37b356', '', 1, '2025-09-15 01:30:36'),
(12, 1, 'El Fortin M40', 'equipos/El_Fortin_M40.png', '#007bff', '', 1, '2025-09-15 01:30:36'),
(13, 1, 'Arrecife M40', 'equipos/Arrecife_M40.png', '#570f13', '', 1, '2025-09-15 01:30:36'),
(14, 1, 'Agrupación Amadeus M40', 'equipos/Agrupaci__n_Amadeus_M40.png', '#525e6b', '', 1, '2025-09-15 01:30:36'),
(15, 1, 'Agrupación Mariano Moreno FC M40', 'equipos/Agrupaci__n_Mariano_Moreno_FC_M40.png', '#007bff', '', 1, '2025-09-15 01:30:36'),
(16, 2, 'Agrupación La Chimenea M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(17, 2, 'Atlético Las Rosas M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(18, 2, 'Coco´s Team M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(19, 2, 'Deportivo Branca M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(20, 2, 'Ever + 10 M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(21, 2, 'Hay equipo M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(22, 2, 'La Pingüina M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(23, 2, 'La Rossana Futbol Ranch M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(24, 2, 'Librería Francisco M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(25, 2, 'Los Amigos M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(26, 2, 'Monos Team M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(27, 2, 'Noreste M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(28, 2, 'Olympiakos M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(29, 2, 'Once Calvas M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(30, 2, 'PSV FC M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(31, 2, 'Santos M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(32, 2, 'Sportivo Rustico M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(33, 2, 'Ta Lento M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(34, 2, 'Unión de Amigos M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(35, 2, 'Vialenses FC M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(36, 3, 'Unión de Viale M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(37, 3, 'Mistico M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(38, 3, 'Bar Munich - Der Klub M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(39, 3, 'Nono Gringo M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(40, 3, 'Las Rosas M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(41, 3, 'LS Celulares M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(42, 3, 'Erio FC M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(43, 3, 'Los Murcielagos FC', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(44, 3, 'Paso a Paso M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(45, 3, 'Los del Palmar M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(46, 3, 'Atlético Yerman M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(47, 3, 'Tercer Tiempo M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(48, 3, 'TT Fútbol Club M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(49, 3, 'AQNV M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(50, 3, 'Bayer FC M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(51, 3, 'Panteras M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(52, 3, 'Celtic Paraná M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(53, 3, 'La 20 FC M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(54, 3, 'Gambeta FC M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(55, 3, 'RGB M30', NULL, NULL, NULL, 1, '2025-09-15 01:30:36'),
(56, 1, 'La 17', 'equipos/La_17.png', '#007bff', '', 1, '2025-09-15 12:31:28');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos_partido`
--

CREATE TABLE `eventos_partido` (
  `id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL,
  `jugador_id` int(11) NOT NULL,
  `tipo_evento` enum('gol','amarilla','roja') NOT NULL,
  `minuto` int(11) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `eventos_partido`
--

INSERT INTO `eventos_partido` (`id`, `partido_id`, `jugador_id`, `tipo_evento`, `minuto`, `observaciones`, `created_at`) VALUES
(264, 14226, 84, 'gol', 0, NULL, '2025-09-25 02:46:35'),
(265, 14226, 84, 'gol', 0, NULL, '2025-09-25 02:46:35'),
(266, 14226, 320, 'gol', 0, NULL, '2025-09-25 02:46:35'),
(302, 14227, 103, 'gol', 0, NULL, '2025-09-25 03:11:44'),
(303, 14227, 98, 'gol', 0, NULL, '2025-09-25 03:11:44'),
(304, 14227, 307, 'gol', 0, NULL, '2025-09-25 03:11:44'),
(305, 14227, 307, 'gol', 0, NULL, '2025-09-25 03:11:44'),
(306, 14227, 307, 'gol', 0, NULL, '2025-09-25 03:11:44'),
(307, 14227, 307, 'gol', 0, NULL, '2025-09-25 03:11:44'),
(308, 14227, 307, 'gol', 0, NULL, '2025-09-25 03:11:44'),
(309, 14228, 121, 'gol', 0, NULL, '2025-09-25 03:16:31'),
(310, 14228, 126, 'gol', 0, NULL, '2025-09-25 03:16:31'),
(323, 14232, 447, 'gol', 0, NULL, '2025-09-25 12:41:02'),
(334, 14234, 103, 'gol', 0, NULL, '2025-09-25 13:07:08'),
(335, 14234, 103, 'gol', 0, NULL, '2025-09-25 13:07:08'),
(339, 14233, 123, 'gol', 0, NULL, '2025-09-25 14:59:55'),
(340, 14233, 124, 'gol', 0, NULL, '2025-09-25 14:59:55'),
(341, 14233, 125, 'gol', 0, NULL, '2025-09-25 14:59:55'),
(342, 14235, 120, 'gol', 0, '', '2025-09-25 15:16:24'),
(348, 14236, 296, 'gol', 0, '', '2025-09-25 15:28:35'),
(349, 14236, 308, 'gol', 0, '', '2025-09-25 15:28:35'),
(350, 14236, 305, 'roja', 0, NULL, '2025-09-25 15:28:35'),
(351, 14236, 294, 'amarilla', 0, NULL, '2025-09-25 15:28:35'),
(352, 14236, 294, 'roja', 0, 'Doble amarilla', '2025-09-25 15:28:35'),
(356, 14224, 445, 'gol', 0, '', '2025-09-25 15:35:52'),
(357, 14224, 441, 'amarilla', 0, NULL, '2025-09-25 15:35:52'),
(358, 14224, 424, 'amarilla', 0, NULL, '2025-09-25 15:35:52'),
(359, 14224, 442, 'roja', 0, NULL, '2025-09-25 15:35:52'),
(360, 14229, 142, 'gol', 0, NULL, '2025-09-26 14:28:12'),
(361, 14229, 142, 'gol', 0, NULL, '2025-09-26 14:28:12'),
(362, 14229, 269, 'gol', 0, NULL, '2025-09-26 14:28:12'),
(363, 14230, 166, 'gol', 0, NULL, '2025-09-26 14:30:09'),
(364, 14230, 166, 'gol', 0, NULL, '2025-09-26 14:30:09'),
(365, 14230, 182, 'gol', 0, NULL, '2025-09-26 14:30:09'),
(397, 14225, 447, 'gol', 0, NULL, '2025-09-27 01:38:26'),
(398, 14225, 64, 'gol', 0, NULL, '2025-09-27 01:38:26'),
(399, 14225, 359, 'gol', 0, NULL, '2025-09-27 01:38:26'),
(400, 14225, 346, 'gol', 0, NULL, '2025-09-27 01:38:26'),
(401, 14276, 269, 'gol', 0, NULL, '2025-10-08 22:48:05'),
(402, 14276, 268, 'gol', 0, NULL, '2025-10-08 22:48:05'),
(535, 14275, 239, 'gol', 0, NULL, '2025-10-15 23:05:27'),
(536, 14275, 244, 'gol', 0, NULL, '2025-10-15 23:05:27'),
(542, 14264, 142, 'gol', 0, NULL, '2025-10-16 11:27:42'),
(543, 14264, 142, 'gol', 0, NULL, '2025-10-16 11:27:42'),
(551, 14265, 166, 'gol', 0, NULL, '2025-10-16 11:29:29'),
(552, 14265, 166, 'gol', 0, NULL, '2025-10-16 11:29:29'),
(553, 14265, 166, 'gol', 0, NULL, '2025-10-16 11:29:29'),
(554, 14265, 126, 'gol', 0, NULL, '2025-10-16 11:29:29'),
(555, 14277, 282, 'gol', 0, NULL, '2025-10-16 21:59:18'),
(556, 14277, 282, 'gol', 0, NULL, '2025-10-16 21:59:18'),
(557, 14277, 282, 'gol', 0, NULL, '2025-10-16 21:59:18'),
(558, 14277, 447, 'gol', 0, NULL, '2025-10-16 21:59:18'),
(567, 14268, 239, 'gol', 0, NULL, '2025-10-16 22:28:02'),
(568, 14268, 239, 'gol', 0, NULL, '2025-10-16 22:28:02'),
(569, 14268, 239, 'gol', 0, NULL, '2025-10-16 22:28:02'),
(570, 14268, 447, 'gol', 0, NULL, '2025-10-16 22:28:02'),
(575, 14267, 212, 'gol', 0, NULL, '2025-10-16 22:37:07'),
(614, 14269, 269, 'gol', 0, NULL, '2025-10-16 22:56:46'),
(615, 14269, 269, 'gol', 0, NULL, '2025-10-16 22:56:46'),
(616, 14269, 269, 'gol', 0, NULL, '2025-10-16 22:56:46'),
(617, 14269, 431, 'gol', 0, NULL, '2025-10-16 22:56:46'),
(618, 14269, 431, 'gol', 0, NULL, '2025-10-16 22:56:46'),
(625, 14231, 212, 'gol', 0, NULL, '2025-10-17 00:20:56'),
(626, 14231, 212, 'gol', 0, NULL, '2025-10-17 00:20:56'),
(627, 14231, 212, 'gol', 0, NULL, '2025-10-17 00:20:56'),
(628, 14231, 212, 'gol', 0, NULL, '2025-10-17 00:20:56'),
(629, 14231, 212, 'gol', 0, NULL, '2025-10-17 00:20:56'),
(630, 14231, 212, 'gol', 0, NULL, '2025-10-17 00:20:56'),
(631, 14237, 181, 'gol', 0, NULL, '2025-10-17 00:34:30'),
(632, 14237, 168, 'gol', 0, NULL, '2025-10-17 00:34:30'),
(633, 14237, 280, 'gol', 0, NULL, '2025-10-17 00:34:30'),
(634, 14238, 202, 'gol', 0, NULL, '2025-10-17 00:34:53'),
(635, 14238, 191, 'gol', 0, NULL, '2025-10-17 00:34:53'),
(636, 14238, 265, 'gol', 0, NULL, '2025-10-17 00:34:53'),
(637, 14239, 227, 'gol', 0, NULL, '2025-10-17 00:35:02'),
(709, 14241, 103, 'gol', 0, NULL, '2025-10-17 00:46:07'),
(710, 14241, 103, 'gol', 0, NULL, '2025-10-17 00:46:07'),
(711, 14241, 447, 'gol', 0, NULL, '2025-10-17 00:46:07'),
(712, 14241, 447, 'gol', 0, NULL, '2025-10-17 00:46:07');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fechas`
--

CREATE TABLE `fechas` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `numero_fecha` int(11) NOT NULL,
  `fecha_programada` date NOT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fechas`
--

INSERT INTO `fechas` (`id`, `categoria_id`, `numero_fecha`, `fecha_programada`, `activa`) VALUES
(1484, 2, 1, '2025-08-23', 1),
(1485, 2, 2, '2025-08-30', 1),
(1486, 2, 3, '2025-09-06', 1),
(1487, 2, 4, '2025-09-13', 1),
(1488, 2, 5, '2025-09-20', 1),
(1489, 2, 6, '2025-09-27', 1),
(1490, 2, 7, '2025-10-04', 1),
(1491, 2, 8, '2025-10-11', 1),
(1492, 2, 9, '2025-10-18', 1),
(1493, 2, 10, '2025-10-25', 1),
(1494, 2, 11, '2025-11-01', 1),
(1495, 2, 12, '2025-11-08', 1),
(1496, 2, 13, '2025-11-15', 1),
(1497, 2, 14, '2025-11-22', 1),
(1498, 2, 15, '2025-11-29', 1),
(1499, 2, 16, '2025-12-06', 1),
(1500, 2, 17, '2025-12-13', 1),
(1501, 2, 18, '2025-12-20', 1),
(1502, 2, 19, '2025-12-27', 1),
(1503, 3, 1, '2025-08-23', 1),
(1504, 3, 2, '2025-08-30', 1),
(1505, 3, 3, '2025-09-06', 1),
(1506, 3, 4, '2025-09-13', 1),
(1507, 3, 5, '2025-09-20', 1),
(1508, 3, 6, '2025-09-27', 1),
(1509, 3, 7, '2025-10-04', 1),
(1510, 3, 8, '2025-10-11', 1),
(1511, 3, 9, '2025-10-18', 1),
(1512, 3, 10, '2025-10-25', 1),
(1513, 3, 11, '2025-11-01', 1),
(1514, 3, 12, '2025-11-08', 1),
(1515, 3, 13, '2025-11-15', 1),
(1516, 3, 14, '2025-11-22', 1),
(1517, 3, 15, '2025-11-29', 1),
(1518, 3, 16, '2025-12-06', 1),
(1519, 3, 17, '2025-12-13', 1),
(1520, 3, 18, '2025-12-20', 1),
(1521, 3, 19, '2025-12-27', 1),
(1522, 1, 1, '2025-08-30', 1),
(1523, 1, 2, '2025-09-06', 1),
(1524, 1, 3, '2025-09-13', 1),
(1525, 1, 4, '2025-09-20', 1),
(1526, 1, 5, '2025-09-27', 1),
(1527, 1, 6, '2025-10-04', 1),
(1528, 1, 7, '2025-10-11', 1),
(1529, 1, 8, '2025-10-18', 1),
(1530, 1, 9, '2025-10-25', 1),
(1531, 1, 10, '2025-11-01', 1),
(1532, 1, 11, '2025-11-08', 1),
(1533, 1, 12, '2025-11-15', 1),
(1534, 1, 13, '2025-11-22', 1),
(1535, 1, 14, '2025-11-29', 1),
(1536, 1, 15, '2025-12-06', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_canchas`
--

CREATE TABLE `horarios_canchas` (
  `id` int(11) NOT NULL,
  `cancha_id` int(11) NOT NULL,
  `hora` time NOT NULL,
  `temporada` enum('verano','invierno') DEFAULT 'verano',
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `horarios_canchas`
--

INSERT INTO `horarios_canchas` (`id`, `cancha_id`, `hora`, `temporada`, `activa`) VALUES
(1, 17, '12:30:00', 'invierno', 1),
(2, 17, '13:40:00', 'invierno', 1),
(3, 17, '14:50:00', 'invierno', 1),
(4, 17, '16:00:00', 'invierno', 1),
(5, 17, '17:10:00', 'invierno', 1),
(6, 17, '18:20:00', 'invierno', 1),
(7, 17, '19:30:00', 'invierno', 1),
(8, 18, '12:30:00', 'invierno', 1),
(9, 18, '13:40:00', 'invierno', 1),
(10, 18, '14:50:00', 'invierno', 1),
(11, 18, '16:00:00', 'invierno', 1),
(12, 18, '17:10:00', 'invierno', 1),
(13, 18, '18:20:00', 'invierno', 1),
(14, 18, '19:30:00', 'invierno', 1),
(15, 19, '12:30:00', 'invierno', 1),
(16, 19, '13:40:00', 'invierno', 1),
(17, 19, '14:50:00', 'invierno', 1),
(18, 19, '16:00:00', 'invierno', 1),
(19, 19, '17:10:00', 'invierno', 1),
(20, 19, '18:20:00', 'invierno', 1),
(21, 19, '19:30:00', 'invierno', 1),
(22, 20, '12:30:00', 'invierno', 1),
(23, 20, '13:40:00', 'invierno', 1),
(24, 20, '14:50:00', 'invierno', 1),
(25, 20, '16:00:00', 'invierno', 1),
(26, 20, '17:10:00', 'invierno', 1),
(27, 20, '18:20:00', 'invierno', 1),
(28, 20, '19:30:00', 'invierno', 1),
(29, 21, '12:30:00', 'invierno', 1),
(30, 21, '13:40:00', 'invierno', 1),
(31, 21, '14:50:00', 'invierno', 1),
(32, 21, '16:00:00', 'invierno', 1),
(33, 21, '17:10:00', 'invierno', 1),
(34, 22, '12:30:00', 'invierno', 1),
(35, 22, '13:40:00', 'invierno', 1),
(36, 22, '14:50:00', 'invierno', 1),
(37, 22, '16:00:00', 'invierno', 1),
(38, 22, '17:10:00', 'invierno', 1),
(39, 23, '12:30:00', 'invierno', 1),
(40, 23, '13:40:00', 'invierno', 1),
(41, 23, '14:50:00', 'invierno', 1),
(42, 23, '16:00:00', 'invierno', 1),
(43, 23, '17:10:00', 'invierno', 1),
(44, 24, '12:30:00', 'invierno', 1),
(45, 24, '13:40:00', 'invierno', 1),
(46, 24, '14:50:00', 'invierno', 1),
(47, 24, '16:00:00', 'invierno', 1),
(48, 24, '17:10:00', 'invierno', 1),
(49, 25, '12:30:00', 'invierno', 1),
(50, 25, '13:40:00', 'invierno', 1),
(51, 25, '14:50:00', 'invierno', 1),
(52, 25, '16:00:00', 'invierno', 1),
(53, 25, '17:10:00', 'invierno', 1),
(54, 26, '12:30:00', 'invierno', 1),
(55, 26, '13:40:00', 'invierno', 1),
(56, 26, '14:50:00', 'invierno', 1),
(57, 26, '16:00:00', 'invierno', 1),
(58, 26, '17:10:00', 'invierno', 1),
(59, 27, '12:30:00', 'invierno', 1),
(60, 27, '13:40:00', 'invierno', 1),
(61, 27, '14:50:00', 'invierno', 1),
(62, 27, '16:00:00', 'invierno', 1),
(63, 27, '17:10:00', 'invierno', 1),
(64, 28, '12:30:00', 'invierno', 1),
(65, 28, '13:40:00', 'invierno', 1),
(66, 28, '14:50:00', 'invierno', 1),
(67, 28, '16:00:00', 'invierno', 1),
(68, 28, '17:10:00', 'invierno', 1),
(69, 29, '12:30:00', 'invierno', 1),
(70, 29, '13:40:00', 'invierno', 1),
(71, 29, '14:50:00', 'invierno', 1),
(72, 29, '16:00:00', 'invierno', 1),
(73, 29, '17:10:00', 'invierno', 1),
(74, 30, '12:30:00', 'invierno', 1),
(75, 30, '13:40:00', 'invierno', 1),
(76, 30, '14:50:00', 'invierno', 1),
(77, 30, '16:00:00', 'invierno', 1),
(78, 30, '17:10:00', 'invierno', 1),
(79, 31, '12:30:00', 'invierno', 1),
(80, 31, '13:40:00', 'invierno', 1),
(81, 31, '14:50:00', 'invierno', 1),
(82, 31, '16:00:00', 'invierno', 1),
(83, 31, '17:10:00', 'invierno', 1),
(84, 32, '12:30:00', 'invierno', 1),
(85, 32, '13:40:00', 'invierno', 1),
(86, 32, '14:50:00', 'invierno', 1),
(87, 32, '16:00:00', 'invierno', 1),
(88, 32, '17:10:00', 'invierno', 1),
(89, 33, '12:30:00', 'invierno', 1),
(90, 33, '13:40:00', 'invierno', 1),
(91, 33, '14:50:00', 'invierno', 1),
(92, 33, '16:00:00', 'invierno', 1),
(93, 33, '17:10:00', 'invierno', 1),
(94, 34, '12:30:00', 'invierno', 1),
(95, 34, '13:40:00', 'invierno', 1),
(96, 34, '14:50:00', 'invierno', 1),
(97, 34, '16:00:00', 'invierno', 1),
(98, 34, '17:10:00', 'invierno', 1),
(99, 35, '12:30:00', 'invierno', 1),
(100, 35, '13:40:00', 'invierno', 1),
(101, 35, '14:50:00', 'invierno', 1),
(102, 35, '16:00:00', 'invierno', 1),
(103, 35, '17:10:00', 'invierno', 1),
(104, 36, '12:30:00', 'invierno', 1),
(105, 36, '13:40:00', 'invierno', 1),
(106, 36, '14:50:00', 'invierno', 1),
(107, 36, '16:00:00', 'invierno', 1),
(108, 36, '17:10:00', 'invierno', 1),
(109, 37, '12:30:00', 'invierno', 1),
(110, 37, '13:40:00', 'invierno', 1),
(111, 37, '14:50:00', 'invierno', 1),
(112, 37, '16:00:00', 'invierno', 1),
(113, 37, '17:10:00', 'invierno', 1),
(114, 38, '12:30:00', 'invierno', 1),
(115, 38, '13:40:00', 'invierno', 1),
(116, 38, '14:50:00', 'invierno', 1),
(117, 38, '16:00:00', 'invierno', 1),
(118, 38, '17:10:00', 'invierno', 1),
(119, 17, '13:30:00', 'verano', 1),
(120, 17, '14:40:00', 'verano', 1),
(121, 17, '15:50:00', 'verano', 1),
(122, 17, '17:00:00', 'verano', 1),
(123, 17, '18:10:00', 'verano', 1),
(124, 18, '13:30:00', 'verano', 1),
(125, 18, '14:40:00', 'verano', 1),
(126, 18, '15:50:00', 'verano', 1),
(127, 18, '17:00:00', 'verano', 1),
(128, 18, '18:10:00', 'verano', 1),
(129, 19, '13:30:00', 'verano', 1),
(130, 19, '14:40:00', 'verano', 1),
(131, 19, '15:50:00', 'verano', 1),
(132, 19, '17:00:00', 'verano', 1),
(133, 19, '18:10:00', 'verano', 1),
(134, 20, '13:30:00', 'verano', 1),
(135, 20, '14:40:00', 'verano', 1),
(136, 20, '15:50:00', 'verano', 1),
(137, 20, '17:00:00', 'verano', 1),
(138, 20, '18:10:00', 'verano', 1),
(139, 21, '13:30:00', 'verano', 1),
(140, 21, '14:40:00', 'verano', 1),
(141, 21, '15:50:00', 'verano', 1),
(142, 21, '17:00:00', 'verano', 1),
(143, 21, '18:10:00', 'verano', 1),
(144, 22, '13:30:00', 'verano', 1),
(145, 22, '14:40:00', 'verano', 1),
(146, 22, '15:50:00', 'verano', 1),
(147, 22, '17:00:00', 'verano', 1),
(148, 22, '18:10:00', 'verano', 1),
(149, 23, '13:30:00', 'verano', 1),
(150, 23, '14:40:00', 'verano', 1),
(151, 23, '15:50:00', 'verano', 1),
(152, 23, '17:00:00', 'verano', 1),
(153, 23, '18:10:00', 'verano', 1),
(154, 24, '13:30:00', 'verano', 1),
(155, 24, '14:40:00', 'verano', 1),
(156, 24, '15:50:00', 'verano', 1),
(157, 24, '17:00:00', 'verano', 1),
(158, 24, '18:10:00', 'verano', 1),
(159, 25, '13:30:00', 'verano', 1),
(160, 25, '14:40:00', 'verano', 1),
(161, 25, '15:50:00', 'verano', 1),
(162, 25, '17:00:00', 'verano', 1),
(163, 25, '18:10:00', 'verano', 1),
(164, 26, '13:30:00', 'verano', 1),
(165, 26, '14:40:00', 'verano', 1),
(166, 26, '15:50:00', 'verano', 1),
(167, 26, '17:00:00', 'verano', 1),
(168, 26, '18:10:00', 'verano', 1),
(169, 27, '13:30:00', 'verano', 1),
(170, 27, '14:40:00', 'verano', 1),
(171, 27, '15:50:00', 'verano', 1),
(172, 27, '17:00:00', 'verano', 1),
(173, 27, '18:10:00', 'verano', 1),
(174, 28, '13:30:00', 'verano', 1),
(175, 28, '14:40:00', 'verano', 1),
(176, 28, '15:50:00', 'verano', 1),
(177, 28, '17:00:00', 'verano', 1),
(178, 28, '18:10:00', 'verano', 1),
(179, 29, '13:30:00', 'verano', 1),
(180, 29, '14:40:00', 'verano', 1),
(181, 29, '15:50:00', 'verano', 1),
(182, 29, '17:00:00', 'verano', 1),
(183, 29, '18:10:00', 'verano', 1),
(184, 30, '13:30:00', 'verano', 1),
(185, 30, '14:40:00', 'verano', 1),
(186, 30, '15:50:00', 'verano', 1),
(187, 30, '17:00:00', 'verano', 1),
(188, 30, '18:10:00', 'verano', 1),
(189, 31, '13:30:00', 'verano', 1),
(190, 31, '14:40:00', 'verano', 1),
(191, 31, '15:50:00', 'verano', 1),
(192, 31, '17:00:00', 'verano', 1),
(193, 31, '18:10:00', 'verano', 1),
(194, 32, '13:30:00', 'verano', 1),
(195, 32, '14:40:00', 'verano', 1),
(196, 32, '15:50:00', 'verano', 1),
(197, 32, '17:00:00', 'verano', 1),
(198, 32, '18:10:00', 'verano', 1),
(199, 33, '13:30:00', 'verano', 1),
(200, 33, '14:40:00', 'verano', 1),
(201, 33, '15:50:00', 'verano', 1),
(202, 33, '17:00:00', 'verano', 1),
(203, 33, '18:10:00', 'verano', 1),
(204, 34, '13:30:00', 'verano', 1),
(205, 34, '14:40:00', 'verano', 1),
(206, 34, '15:50:00', 'verano', 1),
(207, 34, '17:00:00', 'verano', 1),
(208, 34, '18:10:00', 'verano', 1),
(209, 35, '13:30:00', 'verano', 1),
(210, 35, '14:40:00', 'verano', 1),
(211, 35, '15:50:00', 'verano', 1),
(212, 35, '17:00:00', 'verano', 1),
(213, 35, '18:10:00', 'verano', 1),
(214, 36, '13:30:00', 'verano', 1),
(215, 36, '14:40:00', 'verano', 1),
(216, 36, '15:50:00', 'verano', 1),
(217, 36, '17:00:00', 'verano', 1),
(218, 36, '18:10:00', 'verano', 1),
(219, 37, '13:30:00', 'verano', 1),
(220, 37, '14:40:00', 'verano', 1),
(221, 37, '15:50:00', 'verano', 1),
(222, 37, '17:00:00', 'verano', 1),
(223, 37, '18:10:00', 'verano', 1),
(224, 38, '13:30:00', 'verano', 1),
(225, 38, '14:40:00', 'verano', 1),
(226, 38, '15:50:00', 'verano', 1),
(227, 38, '17:00:00', 'verano', 1),
(228, 38, '18:10:00', 'verano', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jugadores`
--

CREATE TABLE `jugadores` (
  `id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `apellido_nombre` varchar(150) NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `amarillas_acumuladas` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `jugadores`
--

INSERT INTO `jugadores` (`id`, `equipo_id`, `dni`, `apellido_nombre`, `fecha_nacimiento`, `foto`, `activo`, `amarillas_acumuladas`, `created_at`) VALUES
(58, 14, '24777777', 'Christian Vazquez', '1968-05-11', NULL, 1, 0, '2025-09-19 23:36:55'),
(59, 14, '24777778', 'Germán Gustavo González', '1968-05-12', NULL, 1, 0, '2025-09-19 23:36:55'),
(60, 14, '24777779', 'Esteban Daniel Badaracco', '1968-05-13', NULL, 1, 0, '2025-09-19 23:36:55'),
(61, 14, '24777780', 'Christian Nelson Ojeda Pelletti', '1968-05-14', NULL, 1, 0, '2025-09-19 23:36:55'),
(62, 14, '24777781', 'Diego Julian Kotlirevsky', '1968-05-15', NULL, 1, 0, '2025-09-19 23:36:55'),
(63, 14, '24777782', 'Sergio Milera', '1968-05-16', NULL, 1, 0, '2025-09-19 23:36:55'),
(64, 14, '24777783', 'Juan Pablo Antonio Zuiani', '1968-05-17', NULL, 1, 0, '2025-09-19 23:36:55'),
(65, 14, '24777784', 'Silvio Javier Ramirez', '1968-05-18', NULL, 1, 0, '2025-09-19 23:36:55'),
(67, 14, '24777786', 'Andres Mario Zapata', '1968-05-20', NULL, 1, 0, '2025-09-19 23:36:55'),
(68, 14, '24777787', 'Salomón Alceiba', '1968-05-21', NULL, 1, 0, '2025-09-19 23:36:55'),
(69, 14, '24777788', 'Duche Dario', '1968-05-22', NULL, 1, 0, '2025-09-19 23:36:55'),
(70, 14, '24777789', 'Diego Alejandro Decoud', '1968-05-23', NULL, 1, 0, '2025-09-19 23:36:55'),
(71, 14, '24777790', 'Marcos Ariel Tarabine', '1968-05-24', NULL, 1, 0, '2025-09-19 23:36:55'),
(72, 14, '24777791', 'Hugo Matias Alegre', '1968-05-25', NULL, 1, 0, '2025-09-19 23:36:55'),
(73, 14, '24777792', 'Guillermo Gabriel Caruso', '1968-05-26', NULL, 1, 0, '2025-09-19 23:36:55'),
(74, 14, '24777793', 'Sergio Hernán Ojeda Peletti', '1968-05-27', NULL, 1, 0, '2025-09-19 23:36:55'),
(75, 14, '24777794', 'Gastón alberto Gramajo', '1968-05-28', NULL, 1, 0, '2025-09-19 23:36:55'),
(76, 15, '22888888', 'Manuel eduardo Vazquez', '1998-08-04', NULL, 1, 0, '2025-09-19 23:37:05'),
(77, 15, '22888889', 'Martin Romero', '1998-08-05', NULL, 1, 0, '2025-09-19 23:37:05'),
(78, 15, '22888890', 'luis milano', '1998-08-06', NULL, 1, 0, '2025-09-19 23:37:05'),
(79, 15, '22888891', 'Marcos jose Bogado', '1998-08-07', NULL, 1, 0, '2025-09-19 23:37:05'),
(80, 15, '22888892', 'Damian angel Acosta', '1998-08-08', NULL, 1, 0, '2025-09-19 23:37:05'),
(81, 15, '22888893', 'JUAN LUIS BRIAND', '1998-08-09', NULL, 1, 0, '2025-09-19 23:37:05'),
(82, 15, '22888894', 'Walter daniel Martinez', '1998-08-10', NULL, 1, 0, '2025-09-19 23:37:05'),
(83, 15, '22888895', 'Mauricio Caluva', '1998-08-11', NULL, 1, 0, '2025-09-19 23:37:05'),
(84, 15, '22888896', 'ALEXANDER ALBERTO ESCALADA', '1998-08-12', NULL, 1, 0, '2025-09-19 23:37:05'),
(85, 15, '22888897', 'Ariel Caluva', '1998-08-13', NULL, 1, 0, '2025-09-19 23:37:05'),
(86, 15, '22888898', 'Walter daniel Cabrera', '1998-08-14', NULL, 1, 0, '2025-09-19 23:37:05'),
(87, 15, '22888899', 'JORGE ALEJANDRO ALMADA', '1998-08-15', NULL, 1, 0, '2025-09-19 23:37:05'),
(88, 15, '22888900', 'Claudio Figueroa', '1998-08-16', NULL, 1, 0, '2025-09-19 23:37:05'),
(89, 15, '22888901', 'Carlos ernesto Bogado', '1998-08-17', NULL, 1, 0, '2025-09-19 23:37:05'),
(90, 15, '22888902', 'Rogelio daniel Gauna', '1998-08-18', NULL, 1, 0, '2025-09-19 23:37:05'),
(91, 15, '22888903', 'Mario isabelino Bogado', '1998-08-19', NULL, 1, 0, '2025-09-19 23:37:05'),
(92, 15, '22888904', 'Sebastian Cabrera', '1998-08-20', NULL, 1, 0, '2025-09-19 23:37:05'),
(93, 6, '20000000', 'DARIO HORACIO ACOSTA', '1982-08-11', NULL, 1, 0, '2025-09-19 23:37:20'),
(94, 6, '20000001', 'JORGE LUIS MOREYRA', '1982-08-12', NULL, 1, 0, '2025-09-19 23:37:20'),
(95, 6, '20000002', 'DARIO MONTOVANI', '1982-08-13', NULL, 1, 0, '2025-09-19 23:37:20'),
(96, 6, '20000003', 'EDUARDO BARZOLA', '1982-08-14', NULL, 1, 0, '2025-09-19 23:37:20'),
(97, 6, '20000004', 'WALTER RESTANO', '1982-08-15', NULL, 1, 0, '2025-09-19 23:37:20'),
(98, 6, '20000005', 'IVAN MATIAS ZAMPIERI', '1982-08-16', NULL, 1, 0, '2025-09-19 23:37:20'),
(99, 6, '20000006', 'HERNAN RESTANO', '1982-08-17', NULL, 1, 0, '2025-09-19 23:37:20'),
(100, 6, '20000007', 'CARLOS ALBERTO SCHAAB', '1982-08-18', NULL, 1, 0, '2025-09-19 23:37:20'),
(101, 6, '20000008', 'LEONARDO JACOB', '1982-08-19', NULL, 1, 0, '2025-09-19 23:37:20'),
(102, 6, '20000009', 'ANGEL ORTIZ', '1982-08-20', NULL, 1, 0, '2025-09-19 23:37:20'),
(103, 6, '20000010', 'ALEJANDRO MONTOVANI', '1982-08-21', NULL, 1, 0, '2025-09-19 23:37:20'),
(104, 6, '20000011', 'Ivan Alberto Pross', '1982-08-22', NULL, 1, 0, '2025-09-19 23:37:20'),
(105, 6, '20000012', 'Ernesto Maestro', '1982-08-23', NULL, 1, 0, '2025-09-19 23:37:20'),
(106, 6, '20000013', 'Daniel alejandro Blanco', '1982-08-24', NULL, 1, 0, '2025-09-19 23:37:20'),
(107, 6, '20000014', 'Maximiliano andres Romero', '1982-08-25', NULL, 1, 0, '2025-09-19 23:37:20'),
(108, 6, '20000015', 'Carlos omar Yfran', '1982-08-26', NULL, 1, 0, '2025-09-19 23:37:20'),
(109, 6, '20000016', 'Ariel Mario Alberto Gastaldi', '1982-08-27', NULL, 1, 0, '2025-09-19 23:37:20'),
(110, 6, '20000017', 'Emanuel Aristimuño', '1982-08-28', NULL, 1, 0, '2025-09-19 23:37:20'),
(111, 6, '20000018', 'Leonardo Luna', '1982-08-29', NULL, 1, 0, '2025-09-19 23:37:20'),
(112, 6, '20000019', 'José edgardo Ledesma', '1982-08-30', NULL, 1, 0, '2025-09-19 23:37:20'),
(113, 6, '20000020', 'SEBASTIAN BARZOLA', '1982-08-31', NULL, 1, 0, '2025-09-19 23:37:20'),
(114, 6, '20000021', 'DIEGO FERNANDO BUSCHIAZZO', '1982-09-01', NULL, 1, 0, '2025-09-19 23:37:20'),
(115, 6, '20000022', 'Patricio Damian Lescano', '1982-09-02', NULL, 1, 0, '2025-09-19 23:37:20'),
(116, 13, '21111112', 'Ricardo Rettore', '1975-06-11', NULL, 1, 0, '2025-09-19 23:37:30'),
(117, 13, '21111113', 'Jose maria Fanchinelli', '1975-06-12', NULL, 1, 0, '2025-09-19 23:37:30'),
(118, 13, '21111114', 'Juan Pablo Bolognesi', '1975-06-13', NULL, 1, 0, '2025-09-19 23:37:30'),
(119, 13, '21111115', 'Carlos Moreyra', '1975-06-14', NULL, 1, 0, '2025-09-19 23:37:30'),
(120, 13, '21111116', 'JUAN ARIEL BARRIOS', '1975-06-15', NULL, 1, 0, '2025-09-19 23:37:30'),
(121, 13, '21111117', 'JOSE MARIA ARANDA', '1975-06-16', NULL, 1, 0, '2025-09-19 23:37:30'),
(122, 13, '21111118', 'JUAN MANUEL CASTILLO', '1975-06-17', NULL, 1, 0, '2025-09-19 23:37:30'),
(123, 13, '21111119', 'GABRIEL GARCIA', '1975-06-18', NULL, 1, 0, '2025-09-19 23:37:30'),
(124, 13, '21111120', 'Pedro Antonio Bazan', '1975-06-19', NULL, 1, 0, '2025-09-19 23:37:30'),
(125, 13, '21111121', 'VICTOR VERON', '1975-06-20', NULL, 1, 0, '2025-09-19 23:37:30'),
(126, 13, '21111122', 'ANGEL MACIEL', '1975-06-21', NULL, 1, 0, '2025-09-19 23:37:30'),
(127, 13, '21111123', 'PABLO MARTIN LEDESMA', '1975-06-22', NULL, 1, 0, '2025-09-19 23:37:30'),
(128, 13, '21111124', 'FABRICIO PEREZ', '1975-06-23', NULL, 1, 0, '2025-09-19 23:37:30'),
(129, 13, '21111125', 'Rodolfo Esteban Gaitan', '1975-06-24', NULL, 1, 0, '2025-09-19 23:37:30'),
(130, 13, '21111126', 'Gabriel Nicolas NIETO', '1975-06-25', NULL, 1, 0, '2025-09-19 23:37:30'),
(131, 13, '21111127', 'Carlos martin Herrera', '1975-06-26', NULL, 1, 0, '2025-09-19 23:37:30'),
(132, 13, '21111128', 'Matias Jorge Jesus Valdez', '1975-06-27', NULL, 1, 0, '2025-09-19 23:37:30'),
(133, 13, '21111129', 'Ernesto Santiago Diaz', '1975-06-28', NULL, 1, 0, '2025-09-19 23:37:30'),
(134, 13, '21111130', 'Gustavo javier Mendoza', '1975-06-29', NULL, 1, 0, '2025-09-19 23:37:30'),
(135, 13, '21111131', 'Emiliano jose Montero', '1975-06-30', NULL, 1, 0, '2025-09-19 23:37:30'),
(136, 13, '21111132', 'Gabriel exequiel Blanca', '1975-07-01', NULL, 1, 0, '2025-09-19 23:37:30'),
(137, 5, '25666666', 'Roberto Dario Cristo', '1982-08-11', NULL, 1, 0, '2025-09-19 23:37:42'),
(138, 5, '25666667', 'Ezequiel Edgardo Benitez', '1982-08-12', NULL, 1, 0, '2025-09-19 23:37:42'),
(139, 5, '25666668', 'Luciano Ubaldo Caraballo', '1982-08-13', NULL, 1, 0, '2025-09-19 23:37:42'),
(140, 5, '25666669', 'Fernando Emanuel Dalesio Crespo', '1982-08-14', NULL, 1, 0, '2025-09-19 23:37:42'),
(141, 5, '25666670', 'Jonatan Wagner', '1982-08-15', NULL, 1, 0, '2025-09-19 23:37:42'),
(142, 5, '25666671', 'Angel Claudio Pioto', '1982-08-16', NULL, 1, 0, '2025-09-19 23:37:42'),
(143, 5, '25666672', 'Juan Manuel Mendoza', '1982-08-17', NULL, 1, 0, '2025-09-19 23:37:42'),
(144, 5, '25666673', 'Fernando Daniel Bordis', '1982-08-18', NULL, 1, 0, '2025-09-19 23:37:42'),
(145, 5, '25666674', 'Luis Adrian Bello', '1982-08-19', NULL, 1, 0, '2025-09-19 23:37:42'),
(146, 5, '25666675', 'Claudio Maria Bescos', '1982-08-20', NULL, 1, 0, '2025-09-19 23:37:42'),
(147, 5, '25666676', 'Matias Jesus Alvarez', '1982-08-21', NULL, 1, 0, '2025-09-19 23:37:42'),
(148, 5, '25666677', 'Guillermo Luis Ullan', '1982-08-22', NULL, 1, 0, '2025-09-19 23:37:42'),
(149, 5, '25666678', 'Raul Dario Rodagua', '1982-08-23', NULL, 1, 0, '2025-09-19 23:37:42'),
(150, 5, '25666679', 'Claudio Adrian Salgado', '1982-08-24', NULL, 1, 0, '2025-09-19 23:37:42'),
(151, 5, '25666680', 'Natalio Alvarez', '1982-08-25', NULL, 1, 0, '2025-09-19 23:37:42'),
(152, 5, '25666681', 'Mauricio Andriolo', '1982-08-26', NULL, 1, 0, '2025-09-19 23:37:42'),
(153, 5, '25666682', 'Martin Montenegro', '1982-08-27', NULL, 1, 0, '2025-09-19 23:37:42'),
(154, 5, '25666683', 'Cristian Romeo Peraita', '1982-08-28', NULL, 1, 0, '2025-09-19 23:37:42'),
(155, 5, '25666684', 'Sergio Altamirano', '1982-08-29', NULL, 1, 0, '2025-09-19 23:37:42'),
(156, 5, '25666685', 'miguel angel Bordis', '1982-08-30', NULL, 1, 0, '2025-09-19 23:37:42'),
(157, 5, '25666686', 'Carlos Mariano Bordis', '1982-08-31', NULL, 1, 0, '2025-09-19 23:37:42'),
(158, 5, '25666687', 'raul german gonzalez', '1982-09-01', NULL, 1, 0, '2025-09-19 23:37:42'),
(159, 5, '25666688', 'Matias Leonardo Siboldi', '1982-09-02', NULL, 1, 0, '2025-09-19 23:37:42'),
(160, 5, '25666689', 'Jose Antonio Albornoz', '1982-09-03', NULL, 1, 0, '2025-09-19 23:37:42'),
(161, 7, '29222222', 'Marcos Azcurrain', '1982-08-11', NULL, 1, 0, '2025-09-19 23:37:54'),
(162, 7, '29222223', 'Marcos ezequiel Chavez', '1982-08-12', NULL, 1, 0, '2025-09-19 23:37:54'),
(163, 7, '29222224', 'Edgar Martinez', '1982-08-13', NULL, 1, 0, '2025-09-19 23:37:54'),
(164, 7, '29222225', 'Miguel oscar Soriani', '1982-08-14', NULL, 1, 0, '2025-09-19 23:37:54'),
(165, 7, '29222226', 'Claudio rafael Pais', '1982-08-15', NULL, 1, 0, '2025-09-19 23:37:54'),
(166, 7, '29222227', 'Carlos Gonzalez', '1982-08-16', NULL, 1, 0, '2025-09-19 23:37:54'),
(167, 7, '29222228', 'Dante Andreoli', '1982-08-17', NULL, 1, 0, '2025-09-19 23:37:54'),
(168, 7, '29222229', 'Lucas gonzalo Cabaña', '1982-08-18', NULL, 1, 0, '2025-09-19 23:37:54'),
(169, 7, '29222230', 'Daniel ricardo Roldan', '1982-08-19', NULL, 1, 0, '2025-09-19 23:37:54'),
(170, 7, '29222231', 'Matias jose Delavalle', '1982-08-20', NULL, 1, 0, '2025-09-19 23:37:54'),
(171, 7, '29222232', 'Carlos Marquesin', '1982-08-21', NULL, 1, 0, '2025-09-19 23:37:54'),
(172, 7, '29222233', 'Raul Suarez', '1982-08-22', NULL, 1, 0, '2025-09-19 23:37:54'),
(173, 7, '29222234', 'Francisco Cian', '1982-08-23', NULL, 1, 0, '2025-09-19 23:37:54'),
(174, 7, '29222235', 'Luciano ruben Lorenzon', '1982-08-24', NULL, 1, 0, '2025-09-19 23:37:54'),
(175, 7, '29222236', 'Renzo gonzalo Vera', '1982-08-25', NULL, 1, 0, '2025-09-19 23:37:54'),
(176, 7, '29222237', 'Hernan Leonardo Vazquez', '1982-08-26', NULL, 1, 0, '2025-09-19 23:37:54'),
(177, 7, '29222238', 'Héctor Adrián Schneider', '1982-08-27', NULL, 1, 0, '2025-09-19 23:37:54'),
(178, 7, '29222239', 'Luis Ramón Miguel Acuña', '1982-08-28', NULL, 1, 0, '2025-09-19 23:37:54'),
(179, 7, '29222240', 'Omar Michelin', '1982-08-29', NULL, 1, 0, '2025-09-19 23:37:54'),
(180, 7, '29222241', 'Cristian fabian Nichea', '1982-08-30', NULL, 1, 0, '2025-09-19 23:37:54'),
(181, 7, '29222242', 'Javier Alejandro Garcilazo', '1982-08-31', NULL, 1, 0, '2025-09-19 23:37:54'),
(182, 7, '29222243', 'Luciano martin Navarro', '1982-09-01', NULL, 1, 0, '2025-09-19 23:37:54'),
(183, 7, '29222244', 'Facundo Delavalle', '1982-09-02', NULL, 1, 0, '2025-09-19 23:37:54'),
(184, 4, '28222222', 'Bruno Maximiliano Corino', '1982-08-11', NULL, 1, 0, '2025-09-19 23:38:03'),
(185, 4, '28222223', 'Diego Sebastian Guardoni', '1982-08-12', NULL, 1, 0, '2025-09-19 23:38:03'),
(186, 4, '28222224', 'Sergio Eduardo Almada', '1982-08-13', NULL, 1, 0, '2025-09-19 23:38:03'),
(187, 4, '28222225', 'Jonathan Erbes', '1982-08-14', NULL, 1, 0, '2025-09-19 23:38:03'),
(188, 4, '28222226', 'Jonatan Pereyra', '1982-08-15', NULL, 1, 0, '2025-09-19 23:38:03'),
(189, 4, '28222227', 'Sebastian Esmeri', '1982-08-16', NULL, 1, 0, '2025-09-19 23:38:03'),
(190, 4, '28222228', 'Ezequiel Adalberto Corino', '1982-08-17', NULL, 1, 0, '2025-09-19 23:38:03'),
(191, 4, '28222229', 'Alejandro Ruben Panelli', '1982-08-18', NULL, 1, 0, '2025-09-19 23:38:03'),
(192, 4, '28222230', 'Hernan Jesus Rivero', '1982-08-19', NULL, 1, 0, '2025-09-19 23:38:03'),
(193, 4, '28222231', 'Rolando Wenceslao Francisco Mansilla', '1982-08-20', NULL, 1, 0, '2025-09-19 23:38:03'),
(194, 4, '28222232', 'Roberto Ariel Panelli', '1982-08-21', NULL, 1, 0, '2025-09-19 23:38:03'),
(195, 4, '28222233', 'Gaston Silveyra', '1982-08-22', NULL, 1, 0, '2025-09-19 23:38:03'),
(196, 4, '28222234', 'rolando Alberto Flores', '1982-08-23', NULL, 1, 0, '2025-09-19 23:38:03'),
(197, 4, '28222235', 'Claudio Lionel carrere', '1982-08-24', NULL, 1, 0, '2025-09-19 23:38:03'),
(198, 4, '28222236', 'Gustavo Daniel Pressel', '1982-08-25', NULL, 1, 0, '2025-09-19 23:38:03'),
(199, 4, '28222237', 'Gonzalo Héctor Ariel Ledesma', '1982-08-26', NULL, 1, 0, '2025-09-19 23:38:03'),
(200, 4, '28222238', 'Diego Torilla', '1982-08-27', NULL, 1, 0, '2025-09-19 23:38:03'),
(201, 4, '28222239', 'Emanuel Andino', '1982-08-28', NULL, 1, 0, '2025-09-19 23:38:03'),
(202, 4, '28222240', 'Sebastian Wulfsohn', '1982-08-29', NULL, 1, 0, '2025-09-19 23:38:03'),
(203, 4, '28222241', 'Sebastian Martinez', '1982-08-30', NULL, 1, 0, '2025-09-19 23:38:03'),
(204, 1, '27222222', 'Gustavo Daniel Bozzo', '1982-08-11', NULL, 1, 0, '2025-09-19 23:38:15'),
(205, 1, '27222223', 'Gaston Emiliano Musuruana', '1982-08-12', NULL, 1, 0, '2025-09-19 23:38:15'),
(206, 1, '27222224', 'gabriel alejandro Gerstner', '1982-08-13', NULL, 1, 0, '2025-09-19 23:38:15'),
(207, 1, '27222225', 'Narciso Mallo', '1982-08-14', NULL, 1, 0, '2025-09-19 23:38:15'),
(208, 1, '27222226', 'Pablo Chimento', '1982-08-15', NULL, 1, 0, '2025-09-19 23:38:15'),
(209, 1, '27222227', 'Mariano Premaries', '1982-08-16', NULL, 1, 0, '2025-09-19 23:38:15'),
(210, 1, '27222228', 'Iván Alejandro Furios', '1982-08-17', NULL, 1, 0, '2025-09-19 23:38:15'),
(211, 1, '27222229', 'Gabriel Alejandro Prina', '1982-08-18', NULL, 1, 0, '2025-09-19 23:38:15'),
(212, 1, '27222230', 'Cesar andres Vazquez', '1982-08-19', NULL, 1, 0, '2025-09-19 23:38:15'),
(213, 1, '27222231', 'Javier Ricardo Zapata', '1982-08-20', NULL, 1, 0, '2025-09-19 23:38:15'),
(214, 1, '27222232', 'Matias Hernan Zapata', '1982-08-21', NULL, 1, 0, '2025-09-19 23:38:15'),
(215, 1, '27222233', 'Marcelo Fabian Sanchez', '1982-08-22', NULL, 1, 0, '2025-09-19 23:38:15'),
(216, 1, '27222234', 'JAVIER GADEA', '1982-08-23', NULL, 1, 0, '2025-09-19 23:38:15'),
(217, 1, '27222235', 'SEBASTIAN RAU', '1982-08-24', NULL, 1, 0, '2025-09-19 23:38:15'),
(218, 1, '27222236', 'GONZALO AYALA', '1982-08-25', NULL, 1, 0, '2025-09-19 23:38:15'),
(219, 1, '27222237', 'CRISTIAN SCHNEIDER', '1982-08-26', NULL, 1, 0, '2025-09-19 23:38:15'),
(220, 1, '27222238', 'Cristian Diego Feltes', '1982-08-27', NULL, 1, 0, '2025-09-19 23:38:15'),
(221, 1, '27222239', 'DARIO REGNER', '1982-08-28', NULL, 1, 0, '2025-09-19 23:38:15'),
(222, 1, '27222240', 'LEANDRO WACHTMEISTER', '1982-08-29', NULL, 1, 0, '2025-09-19 23:38:15'),
(223, 1, '27222241', 'PABLO ZAPATA', '1982-08-30', NULL, 1, 0, '2025-09-19 23:38:15'),
(224, 1, '27222242', 'MARTIN ZAPATA', '1982-08-31', NULL, 1, 0, '2025-09-19 23:38:15'),
(225, 1, '27222243', 'GABRIEL HOLOTTE', '1982-09-01', NULL, 1, 0, '2025-09-19 23:38:15'),
(226, 1, '27222244', 'Francisco Dubs', '1982-09-02', NULL, 1, 0, '2025-09-19 23:38:15'),
(227, 1, '27222245', 'Harold emilio Schneider', '1982-09-03', NULL, 1, 0, '2025-09-19 23:38:15'),
(228, 12, '26222222', 'Roberto Andres Saavedra', '1982-08-11', NULL, 1, 0, '2025-09-19 23:38:26'),
(229, 12, '26222223', 'Roberto Adrian Benavidez', '1982-08-12', NULL, 1, 0, '2025-09-19 23:38:26'),
(230, 12, '26222224', 'Juan Exequiel Cordoba', '1982-08-13', NULL, 1, 0, '2025-09-19 23:38:26'),
(231, 12, '26222225', 'Juan Pablo Diaz', '1982-08-14', NULL, 1, 0, '2025-09-19 23:38:26'),
(232, 12, '26222226', 'Jose Garcia Arroyo', '1982-08-15', NULL, 1, 0, '2025-09-19 23:38:26'),
(233, 12, '26222227', 'Santiago David Steinert', '1982-08-16', NULL, 1, 0, '2025-09-19 23:38:26'),
(234, 12, '26222228', 'julio cesar todaro', '1982-08-17', NULL, 1, 0, '2025-09-19 23:38:26'),
(235, 12, '26222229', 'Franco ivan Morelli', '1982-08-18', NULL, 1, 0, '2025-09-19 23:38:26'),
(236, 12, '26222230', 'César Alberto Goncebatt', '1982-08-19', NULL, 1, 0, '2025-09-19 23:38:26'),
(237, 12, '26222231', 'Mariano Sabadia', '1982-08-20', NULL, 1, 0, '2025-09-19 23:38:26'),
(238, 12, '26222232', 'Exequiel Mauricio Riera', '1982-08-21', NULL, 1, 0, '2025-09-19 23:38:26'),
(239, 12, '26222233', 'Amado Ramón Altamirano', '1982-08-22', NULL, 1, 0, '2025-09-19 23:38:26'),
(240, 12, '26222234', 'Iván Romero', '1982-08-23', NULL, 1, 0, '2025-09-19 23:38:26'),
(241, 12, '26222235', 'Lorenzo Daniel Alvarez', '1982-08-24', NULL, 1, 0, '2025-09-19 23:38:26'),
(242, 12, '26222236', 'Juan Domingo Cabrera', '1982-08-25', NULL, 1, 0, '2025-09-19 23:38:26'),
(243, 12, '26222237', 'Jesús Sebastián Vicentin', '1982-08-26', NULL, 1, 0, '2025-09-19 23:38:26'),
(244, 12, '26222238', 'CARLOS RODRIGUEZ', '1982-08-27', NULL, 1, 0, '2025-09-19 23:38:26'),
(245, 12, '26222239', 'Romeo Héctor Molina', '1982-08-28', NULL, 1, 0, '2025-09-19 23:38:26'),
(246, 12, '26222240', 'Gustavo Andres Romero', '1982-08-29', NULL, 1, 0, '2025-09-19 23:38:26'),
(247, 12, '26222241', 'Ruben Eduardo Lacoste', '1982-08-30', NULL, 1, 0, '2025-09-19 23:38:26'),
(248, 12, '26222242', 'Roque Vallejo', '1982-08-31', NULL, 1, 0, '2025-09-19 23:38:26'),
(249, 12, '26222243', 'Mario Luis Misere', '1982-09-01', NULL, 1, 0, '2025-09-19 23:38:26'),
(250, 12, '26222244', 'Mariano Moretto', '1982-09-02', NULL, 1, 0, '2025-09-19 23:38:26'),
(251, 12, '26222245', 'Norberto sebastian Mariani', '1982-09-03', NULL, 1, 0, '2025-09-19 23:38:26'),
(252, 12, '26222246', 'Fabricio Miguel Sarmiento', '1982-09-04', NULL, 1, 0, '2025-09-19 23:38:26'),
(253, 12, '26222247', 'Nicolas emanuel Mendoza', '1982-09-05', NULL, 1, 0, '2025-09-19 23:38:26'),
(254, 11, '25220515', 'ALEJANDRO ESCOBUE', '1978-04-02', NULL, 1, 0, '2025-09-19 23:38:37'),
(255, 11, '25612555', 'Alexis Jose Ekkert', '1978-04-03', NULL, 1, 0, '2025-09-19 23:38:37'),
(256, 11, '26004595', 'ORLANDO BURGOS', '1978-04-04', NULL, 1, 0, '2025-09-19 23:38:37'),
(257, 11, '26396635', 'JAVIER PANELLI', '1978-04-05', NULL, 1, 0, '2025-09-19 23:38:37'),
(258, 11, '26788675', 'Ruben Alejandro Barrientos', '1978-04-06', NULL, 1, 0, '2025-09-19 23:38:37'),
(259, 11, '27180715', 'MARCOS CLARIA', '1978-04-07', NULL, 1, 0, '2025-09-19 23:38:37'),
(260, 11, '27572755', 'JUAN IGARZA', '1978-04-08', NULL, 1, 0, '2025-09-19 23:38:37'),
(261, 11, '27964795', 'SEBASTIAN KRANS', '1978-04-09', NULL, 1, 0, '2025-09-19 23:38:37'),
(262, 11, '28356835', 'ALEJANDRO FRANSCONI', '1978-04-10', NULL, 1, 0, '2025-09-19 23:38:37'),
(263, 11, '28748875', 'DIEGO TORTUL', '1978-04-11', NULL, 1, 0, '2025-09-19 23:38:37'),
(264, 11, '29140915', 'CLAUDIO CEBALLOS', '1978-04-12', NULL, 1, 0, '2025-09-19 23:38:37'),
(265, 11, '29532955', 'Marcelo Fabián Diaz', '1978-04-13', NULL, 1, 0, '2025-09-19 23:38:37'),
(266, 11, '29924995', 'MATIAS ESCOBUE', '1978-04-14', NULL, 1, 0, '2025-09-19 23:38:37'),
(267, 11, '30317035', 'DANIEL BLANCO', '1978-04-15', NULL, 1, 0, '2025-09-19 23:38:37'),
(268, 11, '30709075', 'ANIBAL MOREYRA', '1978-04-16', NULL, 1, 0, '2025-09-19 23:38:37'),
(269, 11, '31101115', 'ALBERTO BERON', '1978-04-17', NULL, 1, 0, '2025-09-19 23:38:37'),
(270, 11, '31493155', 'SEBASTIAN COMAS', '1978-04-18', NULL, 1, 0, '2025-09-19 23:38:37'),
(271, 11, '31885195', 'GASTON SANGOY', '1978-04-19', NULL, 1, 0, '2025-09-19 23:38:37'),
(272, 11, '32277235', 'Sergio Chitero', '1978-04-20', NULL, 1, 0, '2025-09-19 23:38:37'),
(273, 11, '32669275', 'Jose Montenegro', '1978-04-21', NULL, 1, 0, '2025-09-19 23:38:37'),
(274, 11, '33061315', 'walter Gomez', '1978-04-22', NULL, 1, 0, '2025-09-19 23:38:37'),
(275, 11, '33453355', 'Oscar Vallejo', '1978-04-23', NULL, 1, 0, '2025-09-19 23:38:37'),
(276, 56, '24222222', 'Pablo Gauna', '1982-08-11', NULL, 1, 0, '2025-09-19 23:38:47'),
(277, 56, '24222223', 'EDGARDO JOSE SEGOVIA', '1982-08-12', NULL, 1, 0, '2025-09-19 23:38:47'),
(278, 56, '24222224', 'Santiago Emanuel chavez', '1982-08-13', NULL, 1, 0, '2025-09-19 23:38:47'),
(279, 56, '24222225', 'Roberto Nicolás Nuñez', '1982-08-14', NULL, 1, 0, '2025-09-19 23:38:47'),
(280, 56, '24222226', 'Jorge Andrés Giménez', '1982-08-15', NULL, 1, 0, '2025-09-19 23:38:47'),
(281, 56, '24222227', 'Javier Gaston Gonzalez', '1982-08-16', NULL, 1, 0, '2025-09-19 23:38:47'),
(282, 56, '24222228', 'Alberto Gregorio Alen', '1982-08-17', NULL, 1, 0, '2025-09-19 23:38:47'),
(283, 56, '24222229', 'Alejandro José Cabrera', '1982-08-18', NULL, 1, 0, '2025-09-19 23:38:47'),
(284, 56, '24222230', 'Julio Luciano Cerbin', '1982-08-19', NULL, 1, 0, '2025-09-19 23:38:47'),
(285, 56, '24222231', 'Marcos Barzola', '1982-08-20', NULL, 1, 0, '2025-09-19 23:38:47'),
(286, 56, '24222232', 'Claudio Torcuato', '1982-08-21', NULL, 1, 0, '2025-09-19 23:38:47'),
(287, 56, '24222233', 'Matias Sebastian Aguiar', '1982-08-22', NULL, 1, 0, '2025-09-19 23:38:47'),
(288, 56, '24222234', 'Gabriel Segovia', '1982-08-23', NULL, 1, 0, '2025-09-19 23:38:47'),
(289, 56, '24222235', 'Facundo Abrahan Diaz', '1982-08-24', NULL, 1, 0, '2025-09-19 23:38:47'),
(290, 56, '24222236', 'cesar marcelo Galloli', '1982-08-25', NULL, 1, 0, '2025-09-19 23:38:47'),
(291, 56, '24222237', 'Gustavo Jose Mendez', '1982-08-26', NULL, 1, 0, '2025-09-19 23:38:47'),
(292, 56, '24222238', 'Carlos Ramon Segovia', '1982-08-27', NULL, 1, 0, '2025-09-19 23:38:47'),
(293, 56, '24222239', 'Santiago Cardu', '1982-08-28', NULL, 1, 0, '2025-09-19 23:38:47'),
(294, 2, '2554255', 'Rodrigo Emiliano Lacaze', '1982-08-11', NULL, 1, 0, '2025-09-19 23:38:58'),
(295, 2, '2554256', 'Maximiliano Carelli', '1982-08-12', NULL, 1, 0, '2025-09-19 23:38:58'),
(296, 2, '2554257', 'Laureano Sebastián Gómez', '1982-08-13', NULL, 1, 0, '2025-09-19 23:38:58'),
(297, 2, '2554258', 'Juan Pablo Martinez', '1982-08-14', NULL, 1, 0, '2025-09-19 23:38:58'),
(298, 2, '2554259', 'Claudio Martin Calfacante', '1982-08-15', NULL, 1, 0, '2025-09-19 23:38:58'),
(299, 2, '2554260', 'Pablo Ramon Albornoz', '1982-08-16', NULL, 1, 0, '2025-09-19 23:38:58'),
(300, 2, '2554261', 'Ignacio Juan Pablo Comas', '1982-08-17', NULL, 1, 0, '2025-09-19 23:38:58'),
(301, 2, '2554262', 'Marcelo Cappellacci', '1982-08-18', NULL, 1, 0, '2025-09-19 23:38:58'),
(302, 2, '2554263', 'Juan Carlos Senger', '1982-08-19', NULL, 1, 0, '2025-09-19 23:38:58'),
(303, 2, '2554264', 'Juan Manuel Senger', '1982-08-20', NULL, 1, 0, '2025-09-19 23:38:58'),
(304, 2, '2554265', 'Hernan Pablo Herrlein', '1982-08-21', NULL, 1, 0, '2025-09-19 23:38:58'),
(305, 2, '2554266', 'Néstor Darío Alva', '1982-08-22', NULL, 1, 0, '2025-09-19 23:38:58'),
(306, 2, '2554267', 'Sebastián Falcón', '1982-08-23', NULL, 1, 0, '2025-09-19 23:38:58'),
(307, 2, '2554268', 'Blas Antonio Toso', '1982-08-24', NULL, 1, 0, '2025-09-19 23:38:58'),
(308, 2, '2554269', 'Luis Ivan Andres Jaureguiberry', '1982-08-25', NULL, 1, 0, '2025-09-19 23:38:58'),
(309, 2, '2554270', 'Walter Broder', '1982-08-26', NULL, 1, 0, '2025-09-19 23:38:58'),
(310, 2, '2554271', 'Marcos Romero', '1982-08-27', NULL, 1, 0, '2025-09-19 23:38:58'),
(311, 2, '2554272', 'Nelson Fabian Factor', '1982-08-28', NULL, 1, 0, '2025-09-19 23:38:58'),
(312, 2, '2554273', 'Daniel Alberto Gonzalez', '1982-08-29', NULL, 1, 0, '2025-09-19 23:38:58'),
(313, 2, '2554274', 'Iván Noé Rabuffetti', '1982-08-30', NULL, 1, 0, '2025-09-19 23:38:58'),
(314, 3, '23222222', 'Roman Ezequiel Galiano', '1982-08-11', NULL, 1, 0, '2025-09-19 23:39:13'),
(315, 3, '23222223', 'Gonzalo Claria', '1982-08-12', NULL, 1, 0, '2025-09-19 23:39:13'),
(316, 3, '23222224', 'Jorge Lespiault', '1982-08-13', NULL, 1, 0, '2025-09-19 23:39:13'),
(317, 3, '23222225', 'Exequiel Alejandro Vera', '1982-08-14', NULL, 1, 0, '2025-09-19 23:39:13'),
(318, 3, '23222226', 'Mario Gustavo Neto', '1982-08-15', NULL, 1, 0, '2025-09-19 23:39:13'),
(319, 3, '23222227', 'Fernando Ariel Villaverde', '1982-08-16', NULL, 1, 0, '2025-09-19 23:39:13'),
(320, 3, '23222228', 'Carlos Reynoso', '1982-08-17', NULL, 1, 0, '2025-09-19 23:39:13'),
(321, 3, '23222229', 'Guillermo Galiano', '1982-08-18', NULL, 1, 0, '2025-09-19 23:39:13'),
(322, 3, '23222230', 'Maximiliano Della Ghelfa', '1982-08-19', NULL, 1, 0, '2025-09-19 23:39:13'),
(323, 3, '23222231', 'Enrique Manuel Franco', '1982-08-20', NULL, 1, 0, '2025-09-19 23:39:13'),
(324, 3, '23222232', 'Ricardo Lespiault', '1982-08-21', NULL, 1, 0, '2025-09-19 23:39:13'),
(325, 3, '23222233', 'Eduardo Adrián Martinez', '1982-08-22', NULL, 1, 0, '2025-09-19 23:39:13'),
(326, 3, '23222234', 'Ramon Fabio Lozano', '1982-08-23', NULL, 1, 0, '2025-09-19 23:39:13'),
(327, 3, '23222235', 'Martin Diaz', '1982-08-24', NULL, 1, 0, '2025-09-19 23:39:13'),
(328, 3, '23222236', 'Jorge Daniel Feruglio', '1982-08-25', NULL, 1, 0, '2025-09-19 23:39:13'),
(329, 3, '23222237', 'Javier Netto', '1982-08-26', NULL, 1, 0, '2025-09-19 23:39:13'),
(330, 3, '23222238', 'Sebastian Simiane', '1982-08-27', NULL, 1, 0, '2025-09-19 23:39:13'),
(331, 3, '23222239', 'Fabio Toffolini', '1982-08-28', NULL, 1, 0, '2025-09-19 23:39:13'),
(332, 3, '23222240', 'Daniel Alberto Romero', '1982-08-29', NULL, 1, 0, '2025-09-19 23:39:13'),
(333, 3, '23222241', 'Mariano Toffolini', '1982-08-30', NULL, 1, 0, '2025-09-19 23:39:13'),
(334, 3, '23222242', 'Oscar Luis Zurdo', '1982-08-31', NULL, 1, 0, '2025-09-19 23:39:13'),
(335, 3, '23222243', 'Juan pablo Diaz', '1982-09-01', NULL, 1, 0, '2025-09-19 23:39:13'),
(336, 3, '23222244', 'Cristian Toffolini', '1982-09-02', NULL, 1, 0, '2025-09-19 23:39:13'),
(337, 3, '23222245', 'Milton Schonfeld', '1982-09-03', NULL, 1, 0, '2025-09-19 23:39:13'),
(338, 3, '23222246', 'Rubén Angelino', '1982-09-04', NULL, 1, 0, '2025-09-19 23:39:13'),
(339, 3, '23222247', 'Franco Damian Hereñu', '1982-09-05', NULL, 1, 0, '2025-09-19 23:39:13'),
(340, 3, '23222248', 'Jose Pablo Dreise', '1982-09-06', NULL, 1, 0, '2025-09-19 23:39:13'),
(341, 8, 'DNI', 'apellido y nombre', '1969-12-31', NULL, 1, 0, '2025-09-19 23:39:23'),
(342, 8, '21222222', 'Gastón Carrasco', '1982-08-11', NULL, 1, 0, '2025-09-19 23:39:23'),
(343, 8, '21222223', 'Emiliano Matias Savat', '1982-08-12', NULL, 1, 0, '2025-09-19 23:39:23'),
(344, 8, '21222224', 'Roberto Arostegui', '1982-08-13', NULL, 1, 0, '2025-09-19 23:39:23'),
(345, 8, '21222225', 'Maximiliano Petrucci', '1982-08-14', NULL, 1, 0, '2025-09-19 23:39:23'),
(346, 8, '21222226', 'Luis Marcelo Vargas', '1982-08-15', NULL, 1, 0, '2025-09-19 23:39:23'),
(347, 8, '21222227', 'Julio Salguero', '1982-08-16', NULL, 1, 0, '2025-09-19 23:39:23'),
(348, 8, '21222228', 'Carlos Alberto Galli', '1982-08-17', NULL, 1, 0, '2025-09-19 23:39:23'),
(349, 8, '21222229', 'Maximiliano Jesus Villagra', '1982-08-18', NULL, 1, 0, '2025-09-19 23:39:23'),
(350, 8, '21222230', 'Julio Cesar Velazquez', '1982-08-19', NULL, 1, 0, '2025-09-19 23:39:23'),
(351, 8, '21222231', 'Ruben Dario Invernizzi', '1982-08-20', NULL, 1, 0, '2025-09-19 23:39:23'),
(352, 8, '21222232', 'Damian Alejandro Vergara', '1982-08-21', NULL, 1, 0, '2025-09-19 23:39:23'),
(353, 8, '21222233', 'Norberto Gabriel Gambino', '1982-08-22', NULL, 1, 0, '2025-09-19 23:39:23'),
(354, 8, '21222234', 'Gerardo Collantes', '1982-08-23', NULL, 1, 0, '2025-09-19 23:39:23'),
(355, 8, '21222235', 'Efrain Quijada', '1982-08-24', NULL, 1, 0, '2025-09-19 23:39:23'),
(356, 8, '21222236', 'Fabricio Gerardo Diaz', '1982-08-25', NULL, 1, 0, '2025-09-19 23:39:23'),
(357, 8, '21222237', 'Diego Jesus Ruben Frank', '1982-08-26', NULL, 1, 0, '2025-09-19 23:39:23'),
(358, 8, '21222238', 'Mario Sebastián Silguero', '1982-08-27', NULL, 1, 0, '2025-09-19 23:39:23'),
(359, 8, '21222239', 'Alexis Miguel Iturbide', '1982-08-28', NULL, 1, 0, '2025-09-19 23:39:23'),
(360, 8, '21222240', 'Mario Sebastian Ramon Godoy', '1982-08-29', NULL, 1, 0, '2025-09-19 23:39:23'),
(361, 8, '21222241', 'Pablo Roberto Solari', '1982-08-30', NULL, 1, 0, '2025-09-19 23:39:23'),
(362, 8, '21222242', 'Jose Antonio Servin', '1982-08-31', NULL, 1, 0, '2025-09-19 23:39:23'),
(363, 8, '21222243', 'Edgardo Miguel Corales', '1982-09-01', NULL, 1, 0, '2025-09-19 23:39:23'),
(364, 9, '20222222', 'JAVIER OMAR BERON', '1982-08-11', NULL, 1, 0, '2025-09-19 23:39:34'),
(365, 9, '20222223', 'Guillermo Javier Silaur', '1982-08-12', NULL, 1, 0, '2025-09-19 23:39:34'),
(366, 9, '20222224', 'Luis maximiliano vargas', '1982-08-13', NULL, 1, 0, '2025-09-19 23:39:34'),
(367, 9, '20222225', 'Walter Miguel Angel Cardenia', '1982-08-14', NULL, 1, 0, '2025-09-19 23:39:34'),
(368, 9, '20222226', 'Manuel Federico Ludi', '1982-08-15', NULL, 1, 0, '2025-09-19 23:39:34'),
(369, 9, '20222227', 'ENRIQUE PORTMANN', '1982-08-16', NULL, 1, 0, '2025-09-19 23:39:34'),
(370, 9, '20222228', 'Luis maría Carletti', '1982-08-17', NULL, 1, 0, '2025-09-19 23:39:34'),
(371, 9, '20222229', 'SERGIO DAMIAN GERDAU', '1982-08-18', NULL, 1, 0, '2025-09-19 23:39:34'),
(372, 9, '20222230', 'ANGEL ROBERTO FLORES', '1982-08-19', NULL, 1, 0, '2025-09-19 23:39:34'),
(373, 9, '20222231', 'Lisandro Ariel Peltzer', '1982-08-20', NULL, 1, 0, '2025-09-19 23:39:34'),
(374, 9, '20222232', 'Milton Ezequiel Escobar', '1982-08-21', NULL, 1, 0, '2025-09-19 23:39:34'),
(375, 9, '20222233', 'Oscar Jaime', '1982-08-22', NULL, 1, 0, '2025-09-19 23:39:34'),
(376, 9, '20222234', 'Juan Avelino Carletti', '1982-08-23', NULL, 1, 0, '2025-09-19 23:39:34'),
(377, 9, '20222235', 'Matias Rivero', '1982-08-24', NULL, 1, 0, '2025-09-19 23:39:34'),
(378, 9, '20222236', 'Leopoldo Gariboglio', '1982-08-25', NULL, 1, 0, '2025-09-19 23:39:34'),
(379, 9, '20222237', 'Fabian Anibal Peltzer', '1982-08-26', NULL, 1, 0, '2025-09-19 23:39:34'),
(380, 9, '20222238', 'Matias santiago Machado', '1982-08-27', NULL, 1, 0, '2025-09-19 23:39:34'),
(381, 9, '20222239', 'Eduardo Martin Springli', '1982-08-28', NULL, 1, 0, '2025-09-19 23:39:34'),
(420, 10, '13555444', 'Eduardo Javier Cersofios', '2000-08-11', NULL, 1, 0, '2025-09-19 23:48:25'),
(421, 10, '13555445', 'Hernán Santiago Cimento', '2000-08-12', NULL, 1, 0, '2025-09-19 23:48:25'),
(422, 10, '13555446', 'Mauricio Martin Gandola', '2000-08-13', NULL, 1, 0, '2025-09-19 23:48:25'),
(423, 10, '13555447', 'Hernan Alejandro Heinze', '2000-08-14', NULL, 1, 0, '2025-09-19 23:48:25'),
(424, 10, '13555448', 'Gerardo Hollmann', '2000-08-15', NULL, 1, 0, '2025-09-19 23:48:25'),
(425, 10, '13555449', 'Exequiel Federico Rodrigo', '2000-08-16', NULL, 1, 0, '2025-09-19 23:48:25'),
(426, 10, '13555450', 'Matias Hugo Adrian Troncoso', '2000-08-17', NULL, 1, 0, '2025-09-19 23:48:25'),
(427, 10, '13555451', 'Walter Adrián Agustín Ortiz', '2000-08-18', NULL, 1, 0, '2025-09-19 23:48:25'),
(428, 10, '13555452', 'sergio daniel manavella', '2000-08-19', NULL, 1, 0, '2025-09-19 23:48:25'),
(429, 10, '13555453', 'Daniel Jara', '2000-08-20', NULL, 1, 0, '2025-09-19 23:48:25'),
(430, 10, '13555454', 'Facundo Zamora', '2000-08-21', NULL, 1, 0, '2025-09-19 23:48:25'),
(431, 10, '13555455', 'BLASON RAUL A.', '2000-08-22', NULL, 1, 0, '2025-09-19 23:48:25'),
(432, 10, '13555456', 'Marcelo Andres Candia', '2000-08-23', NULL, 1, 0, '2025-09-19 23:48:25'),
(433, 10, '13555457', 'Omar Alberto Goyeneche', '2000-08-24', NULL, 1, 0, '2025-09-19 23:48:25'),
(434, 10, '13555458', 'Jose Maria Lopez', '2000-08-25', NULL, 1, 0, '2025-09-19 23:48:25'),
(435, 10, '13555459', 'Silvio Malinberni', '2000-08-26', NULL, 1, 0, '2025-09-19 23:48:25'),
(436, 10, '13555460', 'Sergio Sebastian Ojeda', '2000-08-27', NULL, 1, 0, '2025-09-19 23:48:25'),
(437, 10, '13555461', 'Mario Alberto Oxhoran', '2000-08-28', NULL, 1, 0, '2025-09-19 23:48:25'),
(438, 10, '13555462', 'Bruno Manuel Cabral', '2000-08-29', NULL, 1, 0, '2025-09-19 23:48:25'),
(439, 10, '13555463', 'Hugo Cesar Correa', '2000-08-30', NULL, 1, 0, '2025-09-19 23:48:25'),
(440, 10, '13555464', 'Claudia Salvador LIVELI', '2000-08-31', NULL, 1, 0, '2025-09-19 23:48:25'),
(441, 10, '13555465', 'Juan pablo Lechmann', '2000-09-01', NULL, 1, 0, '2025-09-19 23:48:25'),
(442, 10, '13555466', 'Leonel Nicolas Nahuel Centurion', '2000-09-02', NULL, 1, 0, '2025-09-19 23:48:25'),
(443, 10, '13555467', 'DAVID EZEQUIEL ALOY', '2000-09-03', NULL, 1, 0, '2025-09-19 23:48:25'),
(444, 10, '13555468', 'Mario Ficher', '2000-09-04', NULL, 1, 0, '2025-09-19 23:48:25'),
(445, 10, '13555469', 'Jesus Cabrera', '2000-09-05', NULL, 1, 0, '2025-09-19 23:48:25'),
(446, 10, '13555470', 'Jonatan Maximiliano RODAS', '2000-09-06', NULL, 1, 0, '2025-09-19 23:48:25'),
(447, 14, '24777785', 'Andres Humberto Villagra', '1968-05-19', NULL, 1, 0, '2025-09-19 23:48:33');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partidos`
--

CREATE TABLE `partidos` (
  `id` int(11) NOT NULL,
  `fecha_id` int(11) NOT NULL,
  `equipo_local_id` int(11) NOT NULL,
  `equipo_visitante_id` int(11) NOT NULL,
  `cancha_id` int(11) DEFAULT NULL,
  `fecha_partido` date NOT NULL,
  `hora_partido` time DEFAULT NULL,
  `goles_local` int(11) DEFAULT 0,
  `goles_visitante` int(11) DEFAULT 0,
  `estado` enum('programado','en_curso','finalizado','suspendido') DEFAULT 'programado',
  `minuto_actual` int(11) DEFAULT 0,
  `segundos_transcurridos` int(11) DEFAULT 0,
  `tiempo_actual` enum('primer_tiempo','descanso','segundo_tiempo','finalizado') DEFAULT 'primer_tiempo',
  `iniciado_at` timestamp NULL DEFAULT NULL,
  `finalizado_at` timestamp NULL DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `partidos`
--

INSERT INTO `partidos` (`id`, `fecha_id`, `equipo_local_id`, `equipo_visitante_id`, `cancha_id`, `fecha_partido`, `hora_partido`, `goles_local`, `goles_visitante`, `estado`, `minuto_actual`, `segundos_transcurridos`, `tiempo_actual`, `iniciado_at`, `finalizado_at`, `observaciones`) VALUES
(13844, 1484, 16, 35, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13845, 1484, 17, 34, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13846, 1484, 18, 33, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13847, 1484, 19, 32, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13848, 1484, 20, 31, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13849, 1484, 21, 30, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13850, 1484, 22, 29, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13851, 1484, 23, 28, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13852, 1484, 24, 27, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13853, 1484, 25, 26, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13854, 1485, 17, 35, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13855, 1485, 18, 16, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13856, 1485, 19, 34, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13857, 1485, 20, 33, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13858, 1485, 21, 32, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13859, 1485, 22, 31, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13860, 1485, 23, 30, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13861, 1485, 24, 29, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13862, 1485, 25, 28, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13863, 1485, 26, 27, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13864, 1486, 18, 35, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13865, 1486, 19, 17, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13866, 1486, 20, 16, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13867, 1486, 21, 34, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13868, 1486, 22, 33, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13869, 1486, 23, 32, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13870, 1486, 24, 31, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13871, 1486, 25, 30, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13872, 1486, 26, 29, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13873, 1486, 27, 28, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13874, 1487, 19, 35, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13875, 1487, 20, 18, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13876, 1487, 21, 17, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13877, 1487, 22, 16, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13878, 1487, 23, 34, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13879, 1487, 24, 33, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13880, 1487, 25, 32, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13881, 1487, 26, 31, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13882, 1487, 27, 30, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13883, 1487, 28, 29, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13884, 1488, 20, 35, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13885, 1488, 21, 19, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13886, 1488, 22, 18, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13887, 1488, 23, 17, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13888, 1488, 24, 16, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13889, 1488, 25, 34, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13890, 1488, 26, 33, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13891, 1488, 27, 32, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13892, 1488, 28, 31, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13893, 1488, 29, 30, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13894, 1489, 21, 35, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13895, 1489, 22, 20, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13896, 1489, 23, 19, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13897, 1489, 24, 18, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13898, 1489, 25, 17, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13899, 1489, 26, 16, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13900, 1489, 27, 34, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13901, 1489, 28, 33, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13902, 1489, 29, 32, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13903, 1489, 30, 31, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13904, 1490, 22, 35, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13905, 1490, 23, 21, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13906, 1490, 24, 20, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13907, 1490, 25, 19, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13908, 1490, 26, 18, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13909, 1490, 27, 17, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13910, 1490, 28, 16, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13911, 1490, 29, 34, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13912, 1490, 30, 33, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13913, 1490, 31, 32, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13914, 1491, 23, 35, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13915, 1491, 24, 22, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13916, 1491, 25, 21, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13917, 1491, 26, 20, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13918, 1491, 27, 19, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13919, 1491, 28, 18, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13920, 1491, 29, 17, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13921, 1491, 30, 16, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13922, 1491, 31, 34, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13923, 1491, 32, 33, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13924, 1492, 24, 35, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13925, 1492, 25, 23, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13926, 1492, 26, 22, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13927, 1492, 27, 21, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13928, 1492, 28, 20, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13929, 1492, 29, 19, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13930, 1492, 30, 18, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13931, 1492, 31, 17, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13932, 1492, 32, 16, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13933, 1492, 33, 34, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13934, 1493, 25, 35, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13935, 1493, 26, 24, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13936, 1493, 27, 23, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13937, 1493, 28, 22, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13938, 1493, 29, 21, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13939, 1493, 30, 20, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13940, 1493, 31, 19, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13941, 1493, 32, 18, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13942, 1493, 33, 17, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13943, 1493, 34, 16, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13944, 1494, 26, 35, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13945, 1494, 27, 25, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13946, 1494, 28, 24, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13947, 1494, 29, 23, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13948, 1494, 30, 22, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13949, 1494, 31, 21, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13950, 1494, 32, 20, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13951, 1494, 33, 19, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13952, 1494, 34, 18, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13953, 1494, 16, 17, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13954, 1495, 27, 35, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13955, 1495, 28, 26, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13956, 1495, 29, 25, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13957, 1495, 30, 24, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13958, 1495, 31, 23, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13959, 1495, 32, 22, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13960, 1495, 33, 21, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13961, 1495, 34, 20, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13962, 1495, 16, 19, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13963, 1495, 17, 18, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13964, 1496, 28, 35, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13965, 1496, 29, 27, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13966, 1496, 30, 26, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13967, 1496, 31, 25, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13968, 1496, 32, 24, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13969, 1496, 33, 23, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13970, 1496, 34, 22, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13971, 1496, 16, 21, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13972, 1496, 17, 20, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13973, 1496, 18, 19, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13974, 1497, 29, 35, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13975, 1497, 30, 28, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13976, 1497, 31, 27, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13977, 1497, 32, 26, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13978, 1497, 33, 25, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13979, 1497, 34, 24, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13980, 1497, 16, 23, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13981, 1497, 17, 22, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13982, 1497, 18, 21, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13983, 1497, 19, 20, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13984, 1498, 30, 35, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13985, 1498, 31, 29, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13986, 1498, 32, 28, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13987, 1498, 33, 27, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13988, 1498, 34, 26, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13989, 1498, 16, 25, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13990, 1498, 17, 24, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13991, 1498, 18, 23, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13992, 1498, 19, 22, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13993, 1498, 20, 21, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13994, 1499, 31, 35, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13995, 1499, 32, 30, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13996, 1499, 33, 29, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13997, 1499, 34, 28, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13998, 1499, 16, 27, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(13999, 1499, 17, 26, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14000, 1499, 18, 25, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14001, 1499, 19, 24, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14002, 1499, 20, 23, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14003, 1499, 21, 22, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14004, 1500, 32, 35, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14005, 1500, 33, 31, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14006, 1500, 34, 30, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14007, 1500, 16, 29, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14008, 1500, 17, 28, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14009, 1500, 18, 27, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14010, 1500, 19, 26, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14011, 1500, 20, 25, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14012, 1500, 21, 24, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14013, 1500, 22, 23, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14014, 1501, 33, 35, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14015, 1501, 34, 32, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14016, 1501, 16, 31, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14017, 1501, 17, 30, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14018, 1501, 18, 29, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14019, 1501, 19, 28, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14020, 1501, 20, 27, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14021, 1501, 21, 26, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14022, 1501, 22, 25, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14023, 1501, 23, 24, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14024, 1502, 34, 35, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14025, 1502, 16, 33, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14026, 1502, 17, 32, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14027, 1502, 18, 31, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14028, 1502, 19, 30, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14029, 1502, 20, 29, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14030, 1502, 21, 28, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14031, 1502, 22, 27, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14032, 1502, 23, 26, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14033, 1502, 24, 25, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14034, 1503, 49, 36, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14035, 1503, 46, 48, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14036, 1503, 38, 47, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14037, 1503, 50, 55, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14038, 1503, 52, 44, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14039, 1503, 42, 51, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14040, 1503, 54, 39, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14041, 1503, 53, 37, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14042, 1503, 40, 41, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14043, 1503, 45, 43, NULL, '2025-08-23', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14044, 1504, 46, 36, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14045, 1504, 38, 49, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14046, 1504, 50, 48, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14047, 1504, 52, 47, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14048, 1504, 42, 55, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14049, 1504, 54, 44, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14050, 1504, 53, 51, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14051, 1504, 40, 39, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14052, 1504, 45, 37, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14053, 1504, 43, 41, NULL, '2025-08-30', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14054, 1505, 38, 36, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14055, 1505, 50, 46, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14056, 1505, 52, 49, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14057, 1505, 42, 48, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14058, 1505, 54, 47, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14059, 1505, 53, 55, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14060, 1505, 40, 44, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14061, 1505, 45, 51, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14062, 1505, 43, 39, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14063, 1505, 41, 37, NULL, '2025-09-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14064, 1506, 50, 36, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14065, 1506, 52, 38, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14066, 1506, 42, 46, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14067, 1506, 54, 49, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14068, 1506, 53, 48, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14069, 1506, 40, 47, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14070, 1506, 45, 55, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14071, 1506, 43, 44, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14072, 1506, 41, 51, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14073, 1506, 37, 39, NULL, '2025-09-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14074, 1507, 52, 36, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14075, 1507, 42, 50, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14076, 1507, 54, 38, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14077, 1507, 53, 46, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14078, 1507, 40, 49, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14079, 1507, 45, 48, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14080, 1507, 43, 47, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14081, 1507, 41, 55, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14082, 1507, 37, 44, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14083, 1507, 39, 51, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14084, 1508, 42, 36, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14085, 1508, 54, 52, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14086, 1508, 53, 50, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14087, 1508, 40, 38, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14088, 1508, 45, 46, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14089, 1508, 43, 49, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14090, 1508, 41, 48, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14091, 1508, 37, 47, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14092, 1508, 39, 55, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14093, 1508, 51, 44, NULL, '2025-09-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14094, 1509, 54, 36, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14095, 1509, 53, 42, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14096, 1509, 40, 52, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14097, 1509, 45, 50, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14098, 1509, 43, 38, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14099, 1509, 41, 46, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14100, 1509, 37, 49, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14101, 1509, 39, 48, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14102, 1509, 51, 47, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14103, 1509, 44, 55, NULL, '2025-10-04', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14104, 1510, 53, 36, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14105, 1510, 40, 54, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14106, 1510, 45, 42, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14107, 1510, 43, 52, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14108, 1510, 41, 50, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14109, 1510, 37, 38, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14110, 1510, 39, 46, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14111, 1510, 51, 49, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14112, 1510, 44, 48, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14113, 1510, 55, 47, NULL, '2025-10-11', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14114, 1511, 40, 36, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14115, 1511, 45, 53, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14116, 1511, 43, 54, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14117, 1511, 41, 42, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14118, 1511, 37, 52, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14119, 1511, 39, 50, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14120, 1511, 51, 38, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14121, 1511, 44, 46, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14122, 1511, 55, 49, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14123, 1511, 47, 48, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14124, 1512, 45, 36, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14125, 1512, 43, 40, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14126, 1512, 41, 53, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14127, 1512, 37, 54, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14128, 1512, 39, 42, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14129, 1512, 51, 52, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14130, 1512, 44, 50, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14131, 1512, 55, 38, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14132, 1512, 47, 46, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14133, 1512, 48, 49, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14134, 1513, 43, 36, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14135, 1513, 41, 45, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14136, 1513, 37, 40, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14137, 1513, 39, 53, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14138, 1513, 51, 54, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14139, 1513, 44, 42, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14140, 1513, 55, 52, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14141, 1513, 47, 50, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14142, 1513, 48, 38, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14143, 1513, 49, 46, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14144, 1514, 41, 36, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14145, 1514, 37, 43, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14146, 1514, 39, 45, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14147, 1514, 51, 40, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14148, 1514, 44, 53, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14149, 1514, 55, 54, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14150, 1514, 47, 42, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14151, 1514, 48, 52, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14152, 1514, 49, 50, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14153, 1514, 46, 38, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14154, 1515, 37, 36, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14155, 1515, 39, 41, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14156, 1515, 51, 43, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14157, 1515, 44, 45, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14158, 1515, 55, 40, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14159, 1515, 47, 53, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14160, 1515, 48, 54, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14161, 1515, 49, 42, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14162, 1515, 46, 52, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14163, 1515, 38, 50, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14164, 1516, 39, 36, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14165, 1516, 51, 37, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14166, 1516, 44, 41, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14167, 1516, 55, 43, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14168, 1516, 47, 45, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14169, 1516, 48, 40, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14170, 1516, 49, 53, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14171, 1516, 46, 54, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14172, 1516, 38, 42, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14173, 1516, 50, 52, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14174, 1517, 51, 36, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14175, 1517, 44, 39, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14176, 1517, 55, 37, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14177, 1517, 47, 41, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14178, 1517, 48, 43, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14179, 1517, 49, 45, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14180, 1517, 46, 40, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14181, 1517, 38, 53, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14182, 1517, 50, 54, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14183, 1517, 52, 42, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14184, 1518, 44, 36, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14185, 1518, 55, 51, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14186, 1518, 47, 39, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14187, 1518, 48, 37, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14188, 1518, 49, 41, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14189, 1518, 46, 43, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14190, 1518, 38, 45, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14191, 1518, 50, 40, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14192, 1518, 52, 53, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14193, 1518, 42, 54, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14194, 1519, 55, 36, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14195, 1519, 47, 44, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14196, 1519, 48, 51, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14197, 1519, 49, 39, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14198, 1519, 46, 37, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14199, 1519, 38, 41, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14200, 1519, 50, 43, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14201, 1519, 52, 45, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14202, 1519, 42, 40, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14203, 1519, 54, 53, NULL, '2025-12-13', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14204, 1520, 47, 36, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14205, 1520, 48, 55, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14206, 1520, 49, 44, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14207, 1520, 46, 51, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14208, 1520, 38, 39, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14209, 1520, 50, 37, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14210, 1520, 52, 41, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14211, 1520, 42, 43, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14212, 1520, 54, 45, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14213, 1520, 53, 40, NULL, '2025-12-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14214, 1521, 48, 36, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14215, 1521, 49, 47, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14216, 1521, 46, 55, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14217, 1521, 38, 44, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14218, 1521, 50, 51, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14219, 1521, 52, 39, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14220, 1521, 42, 37, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14221, 1521, 54, 41, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14222, 1521, 53, 43, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14223, 1521, 40, 45, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14224, 1522, 10, 9, 17, '2025-09-25', '13:30:00', 1, 0, 'finalizado', 0, 0, 'primer_tiempo', NULL, NULL, 'no paso nada'),
(14225, 1522, 14, 8, 17, '2025-09-25', '14:40:00', 2, 2, 'finalizado', 0, 0, 'primer_tiempo', NULL, '2025-09-27 01:38:26', ''),
(14226, 1522, 15, 3, 17, '2025-09-25', '15:50:00', 2, 1, 'finalizado', 0, 0, 'primer_tiempo', NULL, NULL, 'no paso nada de nada la re concha de la lora'),
(14227, 1522, 6, 2, 17, '2025-09-25', '17:00:00', 2, 5, 'finalizado', 0, 0, 'primer_tiempo', NULL, NULL, 'kdjflkasjdflksdjkfsd'),
(14228, 1522, 13, 56, 17, '2025-09-25', '18:10:00', 2, 0, 'finalizado', 0, 0, 'primer_tiempo', NULL, NULL, ''),
(14229, 1522, 5, 11, 30, '2025-09-25', '13:30:00', 2, 1, 'finalizado', 0, 33, 'finalizado', NULL, '2025-09-26 14:28:12', 'Jugador Alejandro Escobue insultó al juez de linea'),
(14230, 1522, 7, 12, 30, '2025-09-25', '14:40:00', 3, 0, 'finalizado', 0, 22, 'finalizado', NULL, '2025-09-26 14:30:09', ''),
(14231, 1522, 4, 1, 30, '2025-09-25', '15:50:00', 0, 6, 'finalizado', 0, 30, 'finalizado', NULL, '2025-10-17 00:20:56', ''),
(14232, 1523, 14, 9, 17, '2025-09-06', '13:30:00', 1, 0, 'finalizado', 0, 0, 'primer_tiempo', NULL, NULL, '444444'),
(14233, 1523, 15, 10, 17, '2025-09-06', '14:40:00', 0, 0, 'finalizado', 0, 0, 'primer_tiempo', NULL, NULL, ''),
(14234, 1523, 6, 8, 17, '2025-09-06', '15:50:00', 2, 0, 'finalizado', 0, 0, 'primer_tiempo', NULL, NULL, ''),
(14235, 1523, 13, 3, 17, '2025-09-06', '17:00:00', 1, 0, 'finalizado', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14236, 1523, 5, 2, 17, '2025-09-06', '18:10:00', 0, 2, 'finalizado', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14237, 1523, 7, 56, 30, '2025-09-06', '13:30:00', 2, 1, 'finalizado', 0, 0, 'primer_tiempo', NULL, '2025-10-17 00:34:30', ''),
(14238, 1523, 4, 11, 30, '2025-09-06', '14:40:00', 2, 1, 'finalizado', 0, 0, 'primer_tiempo', NULL, '2025-10-17 00:34:53', ''),
(14239, 1523, 1, 12, 30, '2025-09-06', '15:50:00', 1, 0, 'finalizado', 0, 0, 'primer_tiempo', NULL, '2025-10-17 00:35:02', ''),
(14240, 1524, 15, 9, 20, '2025-10-18', '23:30:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14241, 1524, 6, 14, 17, '2025-10-18', '23:40:00', 2, 2, 'finalizado', 3, 236, 'finalizado', '2025-10-17 00:41:57', '2025-10-17 00:46:07', ''),
(14242, 1524, 13, 10, 21, '2025-10-18', '23:50:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14243, 1524, 5, 8, 20, '2025-10-16', '23:00:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14244, 1524, 7, 3, 20, '2025-10-16', '23:10:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14245, 1524, 4, 2, 33, '2025-10-16', '23:30:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14246, 1524, 1, 56, 33, '2025-10-16', '23:40:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14247, 1524, 12, 11, 33, '2025-10-16', '23:50:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14248, 1525, 6, 9, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14249, 1525, 13, 15, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14250, 1525, 5, 14, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14251, 1525, 7, 10, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14252, 1525, 4, 8, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14253, 1525, 1, 3, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14254, 1525, 12, 2, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14255, 1525, 11, 56, NULL, '2025-09-20', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14256, 1526, 13, 9, 17, '2025-10-15', '22:30:00', 0, 0, 'finalizado', 0, 49, 'finalizado', '2025-10-15 19:50:02', '2025-10-15 19:50:57', ''),
(14257, 1526, 5, 6, 17, '2025-10-15', '22:40:00', 0, 0, 'finalizado', 0, 0, 'finalizado', NULL, NULL, ''),
(14258, 1526, 7, 15, 17, '2025-10-15', '22:50:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14259, 1526, 4, 14, 17, '2025-10-15', '22:00:00', 0, 0, 'finalizado', 0, 45, 'finalizado', '2025-10-15 19:27:04', '2025-10-15 19:27:58', ''),
(14260, 1526, 1, 10, 17, '2025-09-27', '18:10:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14261, 1526, 12, 8, 30, '2025-09-27', '13:30:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14262, 1526, 11, 3, 30, '2025-09-27', '14:40:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14263, 1526, 56, 2, 30, '2025-09-27', '15:50:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14264, 1527, 5, 9, 17, '2025-10-16', '22:30:00', 2, 0, 'finalizado', 0, 0, 'finalizado', NULL, '2025-10-16 10:57:47', ''),
(14265, 1527, 7, 13, 17, '2025-10-16', '22:40:00', 3, 1, 'finalizado', 0, 57, 'finalizado', '2025-10-16 11:27:48', '2025-10-16 11:29:29', ''),
(14266, 1527, 4, 6, 17, '2025-10-04', '22:50:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14267, 1527, 1, 15, 17, '2025-10-16', '22:00:00', 1, 0, 'finalizado', 1, 84, 'finalizado', '2025-10-16 11:29:39', '2025-10-16 22:37:07', ''),
(14268, 1527, 12, 14, 17, '2025-10-16', '22:10:00', 3, 1, 'finalizado', 1, 98, 'finalizado', '2025-10-16 22:26:14', '2025-10-16 22:28:02', ''),
(14269, 1527, 11, 10, 30, '2025-10-16', '22:30:00', 3, 2, 'finalizado', 1, 87, 'finalizado', '2025-10-16 22:55:08', '2025-10-16 22:56:46', ''),
(14270, 1527, 56, 8, 30, '2025-10-16', '22:40:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14271, 1527, 2, 3, 30, '2025-10-16', '22:50:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14272, 1528, 7, 9, 17, '2025-10-15', '23:30:00', 0, 0, 'finalizado', 0, 36, 'finalizado', NULL, '2025-10-08 22:48:52', ''),
(14273, 1528, 4, 5, 17, '2025-10-08', '23:40:00', 0, 0, '', 2, 140, 'segundo_tiempo', NULL, NULL, NULL),
(14274, 1528, 1, 13, 17, '2025-10-15', '23:50:00', 0, 0, 'finalizado', 5, 303, 'finalizado', '2025-10-15 16:03:00', '2025-10-15 18:03:24', ''),
(14275, 1528, 12, 6, 17, '2025-10-15', '17:00:00', 2, 0, 'finalizado', 0, 0, 'finalizado', NULL, '2025-10-09 22:45:56', 'sasdasdas'),
(14276, 1528, 11, 15, 17, '2025-10-15', '23:10:00', 2, 0, 'finalizado', 2, 153, 'finalizado', NULL, '2025-10-08 22:48:05', ''),
(14277, 1528, 56, 14, 30, '2025-10-15', '23:30:00', 3, 1, 'finalizado', 0, 49, 'finalizado', '2025-10-15 18:20:18', '2025-10-15 18:21:27', 'asdfdsafsdfasdadsdasf'),
(14278, 1528, 2, 10, 30, '2025-10-08', '23:40:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14279, 1528, 3, 8, 30, '2025-10-08', '23:50:00', 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14280, 1529, 4, 9, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14281, 1529, 1, 7, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14282, 1529, 12, 5, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14283, 1529, 11, 13, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14284, 1529, 56, 6, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14285, 1529, 2, 15, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14286, 1529, 3, 14, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14287, 1529, 8, 10, NULL, '2025-10-18', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14288, 1530, 1, 9, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14289, 1530, 12, 4, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14290, 1530, 11, 7, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14291, 1530, 56, 5, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14292, 1530, 2, 13, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14293, 1530, 3, 6, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14294, 1530, 8, 15, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14295, 1530, 10, 14, NULL, '2025-10-25', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14296, 1531, 12, 9, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14297, 1531, 11, 1, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14298, 1531, 56, 4, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14299, 1531, 2, 7, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14300, 1531, 3, 5, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14301, 1531, 8, 13, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14302, 1531, 10, 6, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14303, 1531, 14, 15, NULL, '2025-11-01', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14304, 1532, 11, 9, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14305, 1532, 56, 12, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14306, 1532, 2, 1, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14307, 1532, 3, 4, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14308, 1532, 8, 7, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14309, 1532, 10, 5, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14310, 1532, 14, 13, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14311, 1532, 15, 6, NULL, '2025-11-08', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14312, 1533, 56, 9, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14313, 1533, 2, 11, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14314, 1533, 3, 12, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14315, 1533, 8, 1, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14316, 1533, 10, 4, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14317, 1533, 14, 7, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14318, 1533, 15, 5, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14319, 1533, 6, 13, NULL, '2025-11-15', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14320, 1534, 2, 9, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14321, 1534, 3, 56, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14322, 1534, 8, 11, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14323, 1534, 10, 12, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14324, 1534, 14, 1, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14325, 1534, 15, 4, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14326, 1534, 6, 7, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14327, 1534, 13, 5, NULL, '2025-11-22', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14328, 1535, 3, 9, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14329, 1535, 8, 2, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14330, 1535, 10, 56, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14331, 1535, 14, 11, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14332, 1535, 15, 12, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14333, 1535, 6, 1, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14334, 1535, 13, 4, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14335, 1535, 5, 7, NULL, '2025-11-29', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14336, 1536, 8, 9, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14337, 1536, 10, 3, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14338, 1536, 14, 2, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14339, 1536, 15, 56, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14340, 1536, 6, 11, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL);
INSERT INTO `partidos` (`id`, `fecha_id`, `equipo_local_id`, `equipo_visitante_id`, `cancha_id`, `fecha_partido`, `hora_partido`, `goles_local`, `goles_visitante`, `estado`, `minuto_actual`, `segundos_transcurridos`, `tiempo_actual`, `iniciado_at`, `finalizado_at`, `observaciones`) VALUES
(14341, 1536, 13, 12, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14342, 1536, 5, 1, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14343, 1536, 7, 4, NULL, '2025-12-06', NULL, 0, 0, '', 0, 0, 'primer_tiempo', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `planillas`
--

CREATE TABLE `planillas` (
  `id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL,
  `planillero_id` int(11) NOT NULL,
  `codigo_acceso` varchar(50) NOT NULL,
  `descargada` tinyint(1) DEFAULT 0,
  `fecha_descarga` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sanciones`
--

CREATE TABLE `sanciones` (
  `id` int(11) NOT NULL,
  `jugador_id` int(11) NOT NULL,
  `tipo` enum('amarillas_acumuladas','doble_amarilla','roja_directa','administrativa') NOT NULL,
  `partidos_suspension` int(11) NOT NULL,
  `partidos_cumplidos` int(11) DEFAULT 0,
  `descripcion` text DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `fecha_sancion` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sanciones`
--

INSERT INTO `sanciones` (`id`, `jugador_id`, `tipo`, `partidos_suspension`, `partidos_cumplidos`, `descripcion`, `activa`, `fecha_sancion`) VALUES
(14, 307, 'doble_amarilla', 1, 142, 'Doble tarjeta amarilla en partido', 0, '2025-09-22'),
(15, 306, 'roja_directa', 2, 5, 'Tarjeta roja directa - Verificar cantidad de fechas según reglamento', 0, '2025-09-22'),
(16, 282, 'doble_amarilla', 1, 0, 'Doble amarilla en partido', 1, '2025-10-16'),
(17, 64, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-10-16'),
(18, 447, 'doble_amarilla', 1, 0, 'Doble amarilla en partido', 1, '2025-10-16'),
(19, 191, 'doble_amarilla', 1, 0, 'Doble amarilla en partido', 1, '2025-10-16'),
(20, 191, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-10-16'),
(21, 191, 'doble_amarilla', 1, 0, 'Doble amarilla en partido', 1, '2025-10-16'),
(22, 191, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-10-16'),
(23, 191, 'doble_amarilla', 1, 0, 'Doble amarilla en partido', 1, '2025-10-16'),
(24, 191, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-10-16');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `tipo` enum('superadmin','admin','planillero') NOT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `codigo_planillero` varchar(10) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `email`, `nombre`, `tipo`, `activo`, `codigo_planillero`, `created_at`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', NULL, 'Administrador', 'superadmin', 1, NULL, '2025-09-15 01:30:36'),
(2, 'Planillero', '$2y$10$g6yqCuSP4kB8z8IqgpGmfOUryVdK9/k8qnoQI7qKODmCA9xSE.RIW', 'wrbroder@gmail.com', 'Planillero', 'planillero', 1, 'PTXJ8F', '2025-09-16 01:47:00'),
(3, 'Walter', '$2y$10$17rq2Psh76ynZ2egkhFqdOepDsmznyApGnXDMrEwhhvknWPuNxl8C', 'wrbroder@gmail.com', 'Watler', 'admin', 1, NULL, '2025-09-16 01:53:16'),
(5, 'Pata', '$2y$10$nIn1tXPgAq7WI2J2odWZzeAdjs4Fdcm.hhhIFN/lgWMxJyQEXM0a6', '', 'Pata Romero', 'admin', 1, NULL, '2025-09-26 17:41:03');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `campeonatos`
--
ALTER TABLE `campeonatos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `canchas`
--
ALTER TABLE `canchas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campeonato_id` (`campeonato_id`);

--
-- Indices de la tabla `codigos_cancha`
--
ALTER TABLE `codigos_cancha`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `cancha_id` (`cancha_id`);

--
-- Indices de la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `eventos_partido`
--
ALTER TABLE `eventos_partido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partido_id` (`partido_id`),
  ADD KEY `jugador_id` (`jugador_id`);

--
-- Indices de la tabla `fechas`
--
ALTER TABLE `fechas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`);

--
-- Indices de la tabla `horarios_canchas`
--
ALTER TABLE `horarios_canchas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `cancha_id` (`cancha_id`);

--
-- Indices de la tabla `jugadores`
--
ALTER TABLE `jugadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dni` (`dni`),
  ADD KEY `equipo_id` (`equipo_id`);

--
-- Indices de la tabla `partidos`
--
ALTER TABLE `partidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fecha_id` (`fecha_id`),
  ADD KEY `equipo_local_id` (`equipo_local_id`),
  ADD KEY `equipo_visitante_id` (`equipo_visitante_id`),
  ADD KEY `cancha_id` (`cancha_id`);

--
-- Indices de la tabla `planillas`
--
ALTER TABLE `planillas`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sanciones`
--
ALTER TABLE `sanciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jugador_id` (`jugador_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `campeonatos`
--
ALTER TABLE `campeonatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `canchas`
--
ALTER TABLE `canchas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `codigos_cancha`
--
ALTER TABLE `codigos_cancha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1223;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=57;

--
-- AUTO_INCREMENT de la tabla `eventos_partido`
--
ALTER TABLE `eventos_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=713;

--
-- AUTO_INCREMENT de la tabla `fechas`
--
ALTER TABLE `fechas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1537;

--
-- AUTO_INCREMENT de la tabla `horarios_canchas`
--
ALTER TABLE `horarios_canchas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=229;

--
-- AUTO_INCREMENT de la tabla `jugadores`
--
ALTER TABLE `jugadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=448;

--
-- AUTO_INCREMENT de la tabla `partidos`
--
ALTER TABLE `partidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14344;

--
-- AUTO_INCREMENT de la tabla `planillas`
--
ALTER TABLE `planillas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sanciones`
--
ALTER TABLE `sanciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD CONSTRAINT `categorias_ibfk_1` FOREIGN KEY (`campeonato_id`) REFERENCES `campeonatos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `codigos_cancha`
--
ALTER TABLE `codigos_cancha`
  ADD CONSTRAINT `codigos_cancha_ibfk_1` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `equipos`
--
ALTER TABLE `equipos`
  ADD CONSTRAINT `equipos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `eventos_partido`
--
ALTER TABLE `eventos_partido`
  ADD CONSTRAINT `eventos_partido_ibfk_1` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `eventos_partido_ibfk_2` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`);

--
-- Filtros para la tabla `fechas`
--
ALTER TABLE `fechas`
  ADD CONSTRAINT `fechas_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `horarios_canchas`
--
ALTER TABLE `horarios_canchas`
  ADD CONSTRAINT `horarios_canchas_ibfk_1` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `jugadores`
--
ALTER TABLE `jugadores`
  ADD CONSTRAINT `jugadores_ibfk_1` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `partidos`
--
ALTER TABLE `partidos`
  ADD CONSTRAINT `partidos_ibfk_1` FOREIGN KEY (`fecha_id`) REFERENCES `fechas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_ibfk_2` FOREIGN KEY (`equipo_local_id`) REFERENCES `equipos` (`id`),
  ADD CONSTRAINT `partidos_ibfk_3` FOREIGN KEY (`equipo_visitante_id`) REFERENCES `equipos` (`id`),
  ADD CONSTRAINT `partidos_ibfk_4` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`);

--
-- Filtros para la tabla `sanciones`
--
ALTER TABLE `sanciones`
  ADD CONSTRAINT `sanciones_ibfk_1` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
