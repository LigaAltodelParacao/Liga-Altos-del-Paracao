-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 06-11-2025 a las 19:59:40
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
(3, 'Torneo Clausura 2025 - M40', 'M40 A', '2025-11-01', NULL, 1, '2025-10-31 21:55:43'),
(7, 'Torneo Apertura 2026', 'M40', '2026-02-01', NULL, 1, '2025-11-04 22:10:08'),
(8, 'Torneo Nocturno 2026 - M40', '', '2026-01-10', NULL, 1, '2025-11-05 00:38:57');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `campeonatos_formato`
--

CREATE TABLE `campeonatos_formato` (
  `id` int(11) NOT NULL,
  `campeonato_id` int(11) NOT NULL,
  `tipo_formato` enum('zonas','eliminacion_directa','mixto') NOT NULL DEFAULT 'mixto',
  `cantidad_zonas` int(11) NOT NULL DEFAULT 2,
  `equipos_por_zona` int(11) NOT NULL DEFAULT 3,
  `equipos_clasifican` int(11) NOT NULL DEFAULT 4,
  `tipo_clasificacion` varchar(50) DEFAULT NULL COMMENT '1_primero, 2_primeros, 2_primeros_2_mejores_terceros, etc',
  `tiene_octavos` tinyint(1) DEFAULT 0,
  `tiene_cuartos` tinyint(1) DEFAULT 0,
  `tiene_semifinal` tinyint(1) DEFAULT 1,
  `tiene_tercer_puesto` tinyint(1) DEFAULT 1,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `categoria_id` int(11) DEFAULT NULL,
  `primeros_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad de primeros que clasifican (por zona)',
  `segundos_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad de segundos que clasifican',
  `terceros_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad de terceros que clasifican',
  `cuartos_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad de cuartos que clasifican'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `campeonatos_formato`
--

INSERT INTO `campeonatos_formato` (`id`, `campeonato_id`, `tipo_formato`, `cantidad_zonas`, `equipos_por_zona`, `equipos_clasifican`, `tipo_clasificacion`, `tiene_octavos`, `tiene_cuartos`, `tiene_semifinal`, `tiene_tercer_puesto`, `activo`, `created_at`, `updated_at`, `categoria_id`, `primeros_clasifican`, `segundos_clasifican`, `terceros_clasifican`, `cuartos_clasifican`) VALUES
(13, 8, 'mixto', 3, 3, 12, NULL, 0, 0, 1, 0, 1, '2025-11-06 19:05:15', '2025-11-06 19:05:15', 28, 3, 4, 3, 2);

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
(21, 3, 'A', '', 1),
(22, 3, 'B', '', 1),
(27, 7, 'M40', '', 1),
(28, 8, 'M40', '', 1);

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
(1301, 38, 'NQHFJR', '2025-10-25', 1, 0, '2025-10-22 16:53:08', '2025-10-25 19:10:00'),
(1302, 26, 'JPX0ER', '2025-10-25', 1, 1, '2025-10-22 16:53:08', '2025-10-25 16:50:00'),
(1303, 17, 'GFU8H7', '2025-11-01', 0, 1, '2025-10-26 17:16:19', '2025-11-01 19:10:00'),
(1304, 30, '5XUPVF', '2025-11-01', 0, 1, '2025-10-26 17:16:19', '2025-11-01 16:50:00'),
(1413, 18, 'DYN921', '2025-12-20', 0, 1, '2025-10-26 21:48:02', '2025-12-20 19:10:00'),
(1414, 32, '1OFE6Y', '2025-12-20', 0, 1, '2025-10-26 21:48:02', '2025-12-20 16:50:00'),
(1525, 32, 'YC67LW', '2025-11-08', 0, 1, '2025-11-05 11:06:10', '2025-11-08 15:40:00'),
(1526, 33, 'WMN4R1', '2025-11-15', 0, 1, '2025-11-05 11:06:46', '2025-11-15 15:40:00'),
(1532, 26, 'G74CJI', '2026-02-28', 0, 1, '2025-11-05 11:27:50', '2026-02-28 15:00:00'),
(1533, 24, 'T0GVFJ', '2025-12-13', 0, 0, '2025-11-05 11:55:48', '2025-12-13 15:40:00'),
(1576, 17, 'CNLGJP', '2026-02-07', 1, 0, '2025-11-05 15:27:17', '2026-02-07 15:00:00');

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
(238, 21, 'La Pingüina M40', 'equipos/La_Ping__ina_M40_1.png', '#ff1900', '', 1, '2025-11-04 21:14:59'),
(239, 21, 'La 17', 'equipos/La_17_1.png', '#007bff', '', 1, '2025-11-04 21:15:26'),
(240, 21, 'Farmacia Abril', 'equipos/Farmaci_Abril_1.png', '#81d719', '', 1, '2025-11-04 21:15:51'),
(241, 21, 'El Fortin M40', 'equipos/El_Fortin_M40_1.png', '#69abf2', '', 1, '2025-11-04 21:16:13'),
(242, 21, 'Distribuidora Tata', 'equipos/Distribuidora_Tata_1.png', '#6eae0f', '', 1, '2025-11-04 21:16:36'),
(243, 21, 'Camioneros M40', NULL, '#29d63d', '', 1, '2025-11-04 21:16:50'),
(244, 21, 'Avenida Distribuciones M40', 'equipos/Avenida_Distribuciones_M40_1.png', '#007bff', '', 1, '2025-11-04 21:17:16'),
(245, 27, 'La Pingüina M40', 'equipos/La_Ping__ina_M40_1.png', '#ff1900', '', 1, '2025-11-04 22:10:47'),
(246, 27, 'La 17', 'equipos/La_17_1.png', '#007bff', '', 1, '2025-11-04 22:10:57'),
(247, 27, 'Farmacia Abril', 'equipos/Farmaci_Abril_1.png', '#81d719', '', 1, '2025-11-04 22:11:30'),
(248, 27, 'El Fortin M40', 'equipos/El_Fortin_M40_1.png', '#69abf2', '', 1, '2025-11-04 22:11:42'),
(249, 27, 'Distribuidora Tata', 'equipos/Distribuidora_Tata_1.png', '#6eae0f', '', 1, '2025-11-04 22:11:52'),
(251, 28, 'Nono Gringo M40', NULL, '#007bff', '', 1, '2025-11-05 00:57:26'),
(252, 28, 'La 17', 'equipos/La_17_1.png', '#007bff', '', 1, '2025-11-05 00:57:48'),
(253, 28, 'Agrupación La Chimenea M30', NULL, '#007bff', '', 1, '2025-11-05 00:57:57'),
(254, 28, 'Villa Urquiza M40', NULL, '#007bff', '', 1, '2025-11-05 00:58:06'),
(255, 28, 'Taladro M40', NULL, '#007bff', '', 1, '2025-11-05 00:58:13'),
(256, 28, 'La Rossana Futbol Ranch M30', NULL, '#007bff', '', 1, '2025-11-05 00:58:22'),
(257, 28, 'Agrupación Mariano Moreno FC M40', NULL, '#007bff', '', 1, '2025-11-05 00:58:30'),
(258, 28, 'Arrecife M40', NULL, '#007bff', '', 1, '2025-11-05 00:58:35'),
(259, 28, 'Agrupación Roma M40', NULL, '#007bff', '', 1, '2025-11-05 00:58:42'),
(260, 28, 'Agrupación Amadeus M40', NULL, '#007bff', '', 1, '2025-11-05 00:58:48'),
(261, 28, 'Atlético Las Rosas M30', NULL, '#007bff', '', 1, '2025-11-05 00:58:54'),
(262, 28, 'Sportivo Rustico M30', NULL, '#007bff', '', 1, '2025-11-05 00:59:00'),
(263, 28, 'Santos M30', NULL, '#007bff', '', 1, '2025-11-05 00:59:11'),
(264, 28, 'Coco´s Team M30', NULL, '#007bff', '', 1, '2025-11-05 00:59:22'),
(265, 28, 'Camioneros M40', NULL, '#29d63d', '', 1, '2025-11-05 00:59:31'),
(266, 28, 'Deportivo Branca M30', NULL, '#007bff', '', 1, '2025-11-05 00:59:36'),
(267, 28, 'Distribuidora Tata', 'equipos/Distribuidora_Tata_1.png', '#6eae0f', '', 1, '2025-11-05 00:59:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `equipos_zonas`
--

CREATE TABLE `equipos_zonas` (
  `id` int(11) NOT NULL,
  `zona_id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL,
  `puntos` int(11) DEFAULT 0,
  `partidos_jugados` int(11) DEFAULT 0,
  `partidos_ganados` int(11) DEFAULT 0,
  `partidos_empatados` int(11) DEFAULT 0,
  `partidos_perdidos` int(11) DEFAULT 0,
  `goles_favor` int(11) DEFAULT 0,
  `goles_contra` int(11) DEFAULT 0,
  `diferencia_gol` int(11) GENERATED ALWAYS AS (`goles_favor` - `goles_contra`) STORED,
  `posicion` int(11) DEFAULT 0,
  `clasificado` tinyint(1) DEFAULT 0,
  `tarjetas_amarillas` int(11) DEFAULT 0,
  `tarjetas_rojas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `equipos_zonas`
--

INSERT INTO `equipos_zonas` (`id`, `zona_id`, `equipo_id`, `puntos`, `partidos_jugados`, `partidos_ganados`, `partidos_empatados`, `partidos_perdidos`, `goles_favor`, `goles_contra`, `posicion`, `clasificado`, `tarjetas_amarillas`, `tarjetas_rojas`) VALUES
(160, 48, 252, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(161, 48, 253, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0),
(162, 48, 255, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0),
(163, 48, 254, 0, 0, 0, 0, 0, 0, 0, 4, 0, 0, 0),
(164, 48, 264, 0, 0, 0, 0, 0, 0, 0, 5, 0, 0, 0),
(165, 48, 262, 0, 0, 0, 0, 0, 0, 0, 6, 0, 0, 0),
(166, 49, 258, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(167, 49, 251, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0),
(168, 49, 266, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0),
(169, 49, 256, 0, 0, 0, 0, 0, 0, 0, 4, 0, 0, 0),
(170, 49, 261, 0, 0, 0, 0, 0, 0, 0, 5, 0, 0, 0),
(171, 49, 260, 0, 0, 0, 0, 0, 0, 0, 6, 0, 0, 0),
(172, 50, 257, 0, 0, 0, 0, 0, 0, 0, 1, 0, 0, 0),
(173, 50, 265, 0, 0, 0, 0, 0, 0, 0, 2, 0, 0, 0),
(174, 50, 259, 0, 0, 0, 0, 0, 0, 0, 3, 0, 0, 0),
(175, 50, 267, 0, 0, 0, 0, 0, 0, 0, 4, 0, 0, 0),
(176, 50, 263, 0, 0, 0, 0, 0, 0, 0, 5, 0, 0, 0);

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
  `tipo_partido` varchar(20) DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `eventos_partido`
--

INSERT INTO `eventos_partido` (`id`, `partido_id`, `jugador_id`, `tipo_evento`, `minuto`, `observaciones`, `tipo_partido`, `created_at`) VALUES
(35, 134, 594, 'gol', 0, NULL, 'normal', '2025-11-04 21:31:11'),
(36, 134, 590, 'amarilla', 0, NULL, 'normal', '2025-11-04 21:31:11'),
(37, 138, 590, 'amarilla', 0, NULL, 'normal', '2025-11-04 21:32:00'),
(38, 141, 590, 'gol', 0, NULL, 'normal', '2025-11-04 21:32:31'),
(39, 141, 590, 'amarilla', 0, NULL, 'normal', '2025-11-04 21:32:31'),
(40, 131, 502, 'roja', 0, NULL, 'normal', '2025-11-04 21:56:55'),
(43, 133, 503, 'roja', 0, 'Doble amarilla', 'normal', '2025-11-04 21:57:31'),
(44, 130, 594, 'gol', 0, NULL, 'normal', '2025-11-04 22:05:55'),
(45, 130, 590, 'amarilla', 0, NULL, 'normal', '2025-11-04 22:05:55'),
(46, 130, 593, 'roja', 0, NULL, 'normal', '2025-11-04 22:05:55'),
(47, 130, 583, 'roja', 0, NULL, 'normal', '2025-11-04 22:05:55'),
(48, 321, 593, 'gol', 1, NULL, 'normal', '2025-11-05 11:09:36'),
(49, 321, 590, 'amarilla', 1, NULL, 'normal', '2025-11-05 11:09:45'),
(50, 321, 596, 'roja', 1, NULL, 'normal', '2025-11-05 11:09:51'),
(53, 322, 523, 'gol', 1, NULL, 'normal', '2025-11-05 11:28:35'),
(54, 322, 520, 'roja', 5, NULL, 'normal', '2025-11-05 11:33:00'),
(57, 322, 529, 'roja', 0, 'Doble amarilla', 'normal', '2025-11-05 11:39:52'),
(58, 321, 595, 'roja', 0, 'Doble amarilla', 'normal', '2025-11-05 11:41:33'),
(59, 315, 594, 'gol', 0, NULL, 'normal', '2025-11-05 11:57:02'),
(60, 315, 590, 'gol', 12, NULL, 'normal', '2025-11-05 12:22:14'),
(61, 318, 594, 'gol', 0, NULL, 'normal', '2025-11-05 13:34:25'),
(62, 318, 594, 'gol', 0, NULL, 'normal', '2025-11-05 13:34:25'),
(63, 318, 594, 'gol', 0, NULL, 'normal', '2025-11-05 13:34:25'),
(64, 318, 594, 'gol', 0, NULL, 'normal', '2025-11-05 13:34:25'),
(65, 318, 592, 'gol', 0, NULL, 'normal', '2025-11-05 13:34:25'),
(66, 318, 592, 'gol', 0, NULL, 'normal', '2025-11-05 13:34:25'),
(67, 318, 592, 'gol', 0, NULL, 'normal', '2025-11-05 13:34:25'),
(68, 318, 590, 'gol', 0, NULL, 'normal', '2025-11-05 13:34:25'),
(69, 318, 590, 'gol', 0, NULL, 'normal', '2025-11-05 13:34:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fases_eliminatorias`
--

CREATE TABLE `fases_eliminatorias` (
  `id` int(11) NOT NULL,
  `formato_id` int(11) NOT NULL,
  `nombre` enum('dieciseisavos','octavos','cuartos','semifinal','final','tercer_puesto') NOT NULL,
  `orden` int(11) NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `generada` tinyint(1) DEFAULT 0 COMMENT 'Indica si ya se generaron los partidos'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fases_eliminatorias`
--

INSERT INTO `fases_eliminatorias` (`id`, `formato_id`, `nombre`, `orden`, `activa`, `generada`) VALUES
(35, 13, 'semifinal', 1, 0, 0),
(36, 13, 'final', 2, 0, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fechas`
--

CREATE TABLE `fechas` (
  `id` int(11) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `numero_fecha` int(11) NOT NULL,
  `fecha_programada` date NOT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `zona_id` int(11) DEFAULT NULL COMMENT 'ID de zona si esta fecha es específica de una zona',
  `fase_eliminatoria_id` int(11) DEFAULT NULL COMMENT 'ID de fase eliminatoria si esta fecha es de eliminatorias',
  `tipo_fecha` enum('normal','zona','eliminatoria') DEFAULT 'normal' COMMENT 'Tipo de fecha'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `fechas`
--

INSERT INTO `fechas` (`id`, `categoria_id`, `numero_fecha`, `fecha_programada`, `activa`, `zona_id`, `fase_eliminatoria_id`, `tipo_fecha`) VALUES
(1718, 21, 1, '2025-11-01', 1, NULL, NULL, 'normal'),
(1719, 21, 2, '2025-11-08', 1, NULL, NULL, 'normal'),
(1720, 21, 3, '2025-11-15', 1, NULL, NULL, 'normal'),
(1721, 21, 4, '2025-11-22', 1, NULL, NULL, 'normal'),
(1722, 21, 5, '2025-11-29', 1, NULL, NULL, 'normal'),
(1723, 21, 6, '2025-12-06', 1, NULL, NULL, 'normal'),
(1724, 21, 7, '2025-12-13', 1, NULL, NULL, 'normal'),
(1756, 27, 1, '2026-02-07', 1, NULL, NULL, 'normal'),
(1757, 27, 2, '2026-02-14', 1, NULL, NULL, 'normal'),
(1758, 27, 3, '2026-02-21', 1, NULL, NULL, 'normal'),
(1759, 27, 4, '2026-02-28', 1, NULL, NULL, 'normal'),
(1760, 27, 5, '2026-03-07', 1, NULL, NULL, 'normal'),
(1885, 28, 1, '2025-11-06', 1, 48, NULL, 'zona'),
(1886, 28, 2, '2025-11-13', 1, 48, NULL, 'zona'),
(1887, 28, 3, '2025-11-20', 1, 48, NULL, 'zona'),
(1888, 28, 4, '2025-11-27', 1, 48, NULL, 'zona'),
(1889, 28, 5, '2025-12-04', 1, 48, NULL, 'zona'),
(1890, 28, 1, '2025-11-06', 1, 49, NULL, 'zona'),
(1891, 28, 2, '2025-11-13', 1, 49, NULL, 'zona'),
(1892, 28, 3, '2025-11-20', 1, 49, NULL, 'zona'),
(1893, 28, 4, '2025-11-27', 1, 49, NULL, 'zona'),
(1894, 28, 5, '2025-12-04', 1, 49, NULL, 'zona'),
(1895, 28, 1, '2025-11-06', 1, 50, NULL, 'zona'),
(1896, 28, 2, '2025-11-13', 1, 50, NULL, 'zona'),
(1897, 28, 3, '2025-11-20', 1, 50, NULL, 'zona'),
(1898, 28, 4, '2025-11-27', 1, 50, NULL, 'zona'),
(1899, 28, 5, '2025-12-04', 1, 50, NULL, 'zona');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_canchas`
--

CREATE TABLE `horarios_canchas` (
  `id` int(11) NOT NULL,
  `cancha_id` int(11) NOT NULL,
  `hora` time NOT NULL,
  `temporada` enum('verano','invierno','noche') DEFAULT 'verano',
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
(228, 38, '18:10:00', 'verano', 1),
(229, 17, '14:00:00', 'verano', 1),
(230, 17, '15:30:00', 'verano', 1),
(231, 17, '18:30:00', 'verano', 1),
(232, 30, '14:00:00', 'verano', 1),
(233, 30, '15:30:00', 'verano', 1),
(234, 30, '18:30:00', 'verano', 1),
(235, 29, '14:00:00', 'verano', 1),
(236, 29, '15:30:00', 'verano', 1),
(237, 29, '18:30:00', 'verano', 1),
(238, 18, '14:00:00', 'verano', 1),
(239, 18, '15:30:00', 'verano', 1),
(240, 18, '18:30:00', 'verano', 1),
(241, 31, '14:00:00', 'verano', 1),
(242, 31, '15:30:00', 'verano', 1),
(243, 31, '18:30:00', 'verano', 1),
(244, 19, '14:00:00', 'verano', 1),
(245, 19, '15:30:00', 'verano', 1),
(246, 19, '18:30:00', 'verano', 1),
(247, 32, '14:00:00', 'verano', 1),
(248, 32, '15:30:00', 'verano', 1),
(249, 32, '18:30:00', 'verano', 1),
(250, 34, '14:00:00', 'verano', 1),
(251, 34, '15:30:00', 'verano', 1),
(252, 34, '18:30:00', 'verano', 1),
(253, 35, '14:00:00', 'verano', 1),
(254, 35, '15:30:00', 'verano', 1),
(255, 35, '18:30:00', 'verano', 1),
(256, 36, '14:00:00', 'verano', 1),
(257, 36, '15:30:00', 'verano', 1),
(258, 36, '18:30:00', 'verano', 1),
(259, 20, '14:00:00', 'verano', 1),
(260, 20, '15:30:00', 'verano', 1),
(261, 20, '18:30:00', 'verano', 1),
(262, 33, '14:00:00', 'verano', 1),
(263, 33, '15:30:00', 'verano', 1),
(264, 33, '18:30:00', 'verano', 1),
(265, 21, '14:00:00', 'verano', 1),
(266, 21, '15:30:00', 'verano', 1),
(267, 21, '18:30:00', 'verano', 1),
(268, 37, '14:00:00', 'verano', 1),
(269, 37, '15:30:00', 'verano', 1),
(270, 37, '18:30:00', 'verano', 1),
(271, 22, '14:00:00', 'verano', 1),
(272, 22, '15:30:00', 'verano', 1),
(273, 22, '18:30:00', 'verano', 1),
(274, 38, '14:00:00', 'verano', 1),
(275, 38, '15:30:00', 'verano', 1),
(276, 38, '18:30:00', 'verano', 1),
(277, 23, '14:00:00', 'verano', 1),
(278, 23, '15:30:00', 'verano', 1),
(279, 23, '18:30:00', 'verano', 1),
(280, 25, '14:00:00', 'verano', 1),
(281, 25, '15:30:00', 'verano', 1),
(282, 25, '18:30:00', 'verano', 1),
(283, 26, '14:00:00', 'verano', 1),
(284, 26, '15:30:00', 'verano', 1),
(285, 26, '18:30:00', 'verano', 1),
(286, 27, '14:00:00', 'verano', 1),
(287, 27, '15:30:00', 'verano', 1),
(288, 27, '18:30:00', 'verano', 1),
(289, 24, '14:00:00', 'verano', 1),
(290, 24, '15:30:00', 'verano', 1),
(291, 24, '18:30:00', 'verano', 1),
(292, 28, '14:00:00', 'verano', 1),
(293, 28, '15:30:00', 'verano', 1),
(294, 28, '18:30:00', 'verano', 1),
(295, 17, '19:00:00', 'verano', 1),
(296, 17, '20:30:00', 'verano', 1),
(297, 17, '22:00:00', 'verano', 1),
(298, 17, '23:30:00', 'verano', 1),
(299, 18, '19:00:00', 'verano', 1),
(300, 18, '20:30:00', 'verano', 1),
(301, 18, '22:00:00', 'verano', 1),
(302, 18, '23:30:00', 'verano', 1),
(303, 20, '19:00:00', 'verano', 1),
(304, 20, '20:30:00', 'verano', 1),
(305, 20, '22:00:00', 'verano', 1),
(306, 20, '23:30:00', 'verano', 1),
(307, 17, '20:00:00', 'verano', 1),
(308, 17, '21:30:00', 'verano', 1),
(309, 18, '20:00:00', 'verano', 1),
(310, 18, '21:30:00', 'verano', 1),
(311, 19, '20:00:00', 'verano', 1),
(312, 19, '21:30:00', 'verano', 1),
(313, 19, '22:00:00', 'verano', 1),
(314, 19, '23:30:00', 'verano', 1),
(315, 20, '20:00:00', 'verano', 1),
(316, 20, '21:30:00', 'verano', 1);

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
(446, 244, '29222222', 'Marcos Azcurrain', '1982-08-11', NULL, 1, 0, '2025-11-04 21:21:53'),
(447, 244, '29222223', 'Marcos ezequiel Chavez', '1982-08-12', NULL, 1, 0, '2025-11-04 21:21:53'),
(448, 244, '29222224', 'Edgar Martinez', '1982-08-13', NULL, 1, 0, '2025-11-04 21:21:53'),
(449, 244, '29222225', 'Miguel oscar Soriani', '1982-08-14', NULL, 1, 0, '2025-11-04 21:21:53'),
(450, 244, '29222226', 'Claudio rafael Pais', '1982-08-15', NULL, 1, 0, '2025-11-04 21:21:53'),
(451, 244, '29222227', 'Carlos Gonzalez', '1982-08-16', NULL, 1, 0, '2025-11-04 21:21:53'),
(452, 244, '29222228', 'Dante Andreoli', '1982-08-17', NULL, 1, 0, '2025-11-04 21:21:53'),
(453, 244, '29222229', 'Lucas gonzalo Cabaña', '1982-08-18', NULL, 1, 0, '2025-11-04 21:21:53'),
(454, 244, '29222230', 'Daniel ricardo Roldan', '1982-08-19', NULL, 1, 0, '2025-11-04 21:21:53'),
(455, 244, '29222231', 'Matias jose Delavalle', '1982-08-20', NULL, 1, 0, '2025-11-04 21:21:53'),
(456, 244, '29222232', 'Carlos Marquesin', '1982-08-21', NULL, 1, 0, '2025-11-04 21:21:53'),
(457, 244, '29222233', 'Raul Suarez', '1982-08-22', NULL, 1, 0, '2025-11-04 21:21:53'),
(458, 244, '29222234', 'Francisco Cian', '1982-08-23', NULL, 1, 0, '2025-11-04 21:21:53'),
(459, 244, '29222235', 'Luciano ruben Lorenzon', '1982-08-24', NULL, 1, 0, '2025-11-04 21:21:53'),
(460, 244, '29222236', 'Renzo gonzalo Vera', '1982-08-25', NULL, 1, 0, '2025-11-04 21:21:53'),
(461, 244, '29222237', 'Hernan Leonardo Vazquez', '1982-08-26', NULL, 1, 0, '2025-11-04 21:21:53'),
(462, 244, '29222238', 'Héctor Adrián Schneider', '1982-08-27', NULL, 1, 0, '2025-11-04 21:21:53'),
(463, 244, '29222239', 'Luis Ramón Miguel Acuña', '1982-08-28', NULL, 1, 0, '2025-11-04 21:21:53'),
(464, 244, '29222240', 'Omar Michelin', '1982-08-29', NULL, 1, 0, '2025-11-04 21:21:53'),
(465, 244, '29222241', 'Cristian fabian Nichea', '1982-08-30', NULL, 1, 0, '2025-11-04 21:21:53'),
(466, 244, '29222242', 'Javier Alejandro Garcilazo', '1982-08-31', NULL, 1, 0, '2025-11-04 21:21:53'),
(467, 244, '29222243', 'Luciano martin Navarro', '1982-09-01', NULL, 1, 0, '2025-11-04 21:21:53'),
(468, 244, '29222244', 'Facundo Delavalle', '1982-09-02', NULL, 1, 0, '2025-11-04 21:21:53'),
(469, 265, '28222222', 'Bruno Maximiliano Corino', '1982-08-11', NULL, 1, 0, '2025-11-04 21:22:23'),
(470, 265, '28222223', 'Diego Sebastian Guardoni', '1982-08-12', NULL, 1, 0, '2025-11-04 21:22:23'),
(471, 265, '28222224', 'Sergio Eduardo Almada', '1982-08-13', NULL, 1, 0, '2025-11-04 21:22:23'),
(472, 265, '28222225', 'Jonathan Erbes', '1982-08-14', NULL, 1, 0, '2025-11-04 21:22:23'),
(473, 265, '28222226', 'Jonatan Pereyra', '1982-08-15', NULL, 1, 0, '2025-11-04 21:22:23'),
(474, 265, '28222227', 'Sebastian Esmeri', '1982-08-16', NULL, 1, 0, '2025-11-04 21:22:23'),
(475, 265, '28222228', 'Ezequiel Adalberto Corino', '1982-08-17', NULL, 1, 0, '2025-11-04 21:22:23'),
(476, 265, '28222229', 'Alejandro Ruben Panelli', '1982-08-18', NULL, 1, 0, '2025-11-04 21:22:23'),
(477, 265, '28222230', 'Hernan Jesus Rivero', '1982-08-19', NULL, 1, 0, '2025-11-04 21:22:23'),
(478, 265, '28222231', 'Rolando Wenceslao Francisco Mansilla', '1982-08-20', NULL, 1, 0, '2025-11-04 21:22:23'),
(479, 265, '28222232', 'Roberto Ariel Panelli', '1982-08-21', NULL, 1, 0, '2025-11-04 21:22:23'),
(480, 265, '28222233', 'Gaston Silveyra', '1982-08-22', NULL, 1, 0, '2025-11-04 21:22:23'),
(481, 265, '28222234', 'rolando Alberto Flores', '1982-08-23', NULL, 1, 0, '2025-11-04 21:22:23'),
(482, 265, '28222235', 'Claudio Lionel carrere', '1982-08-24', NULL, 1, 0, '2025-11-04 21:22:23'),
(483, 265, '28222236', 'Gustavo Daniel Pressel', '1982-08-25', NULL, 1, 0, '2025-11-04 21:22:23'),
(484, 265, '28222237', 'Gonzalo Héctor Ariel Ledesma', '1982-08-26', NULL, 1, 0, '2025-11-04 21:22:23'),
(485, 265, '28222238', 'Diego Torilla', '1982-08-27', NULL, 1, 0, '2025-11-04 21:22:23'),
(486, 265, '28222239', 'Emanuel Andino', '1982-08-28', NULL, 1, 0, '2025-11-04 21:22:23'),
(487, 265, '28222240', 'Sebastian Wulfsohn', '1982-08-29', NULL, 1, 0, '2025-11-04 21:22:23'),
(488, 265, '28222241', 'Sebastian Martinez', '1982-08-30', NULL, 1, 0, '2025-11-04 21:22:23'),
(489, 267, '27222222', 'Gustavo Daniel Bozzo', '1982-08-11', NULL, 1, 0, '2025-11-04 21:22:38'),
(490, 267, '27222223', 'Gaston Emiliano Musuruana', '1982-08-12', NULL, 1, 0, '2025-11-04 21:22:38'),
(491, 267, '27222224', 'gabriel alejandro Gerstner', '1982-08-13', NULL, 1, 0, '2025-11-04 21:22:38'),
(492, 267, '27222225', 'Narciso Mallo', '1982-08-14', NULL, 1, 0, '2025-11-04 21:22:38'),
(493, 267, '27222226', 'Pablo Chimento', '1982-08-15', NULL, 1, 0, '2025-11-04 21:22:38'),
(494, 267, '27222227', 'Mariano Premaries', '1982-08-16', NULL, 1, 0, '2025-11-04 21:22:38'),
(495, 267, '27222228', 'Iván Alejandro Furios', '1982-08-17', NULL, 1, 0, '2025-11-04 21:22:38'),
(496, 267, '27222229', 'Gabriel Alejandro Prina', '1982-08-18', NULL, 1, 0, '2025-11-04 21:22:38'),
(497, 267, '27222230', 'Cesar andres Vazquez', '1982-08-19', NULL, 1, 0, '2025-11-04 21:22:38'),
(498, 267, '27222231', 'Javier Ricardo Zapata', '1982-08-20', NULL, 1, 0, '2025-11-04 21:22:38'),
(499, 267, '27222232', 'Matias Hernan Zapata', '1982-08-21', NULL, 1, 0, '2025-11-04 21:22:38'),
(500, 267, '27222233', 'Marcelo Fabian Sanchez', '1982-08-22', NULL, 1, 0, '2025-11-04 21:22:38'),
(501, 267, '27222234', 'JAVIER GADEA', '1982-08-23', NULL, 1, 0, '2025-11-04 21:22:38'),
(502, 267, '27222235', 'SEBASTIAN RAU', '1982-08-24', NULL, 1, 0, '2025-11-04 21:22:38'),
(503, 267, '27222236', 'GONZALO AYALA', '1982-08-25', NULL, 1, 0, '2025-11-04 21:22:38'),
(504, 267, '27222237', 'CRISTIAN SCHNEIDER', '1982-08-26', NULL, 1, 0, '2025-11-04 21:22:38'),
(505, 267, '27222238', 'Cristian Diego Feltes', '1982-08-27', NULL, 1, 0, '2025-11-04 21:22:38'),
(506, 267, '27222239', 'DARIO REGNER', '1982-08-28', NULL, 1, 0, '2025-11-04 21:22:38'),
(507, 267, '27222240', 'LEANDRO WACHTMEISTER', '1982-08-29', NULL, 1, 0, '2025-11-04 21:22:38'),
(508, 267, '27222241', 'PABLO ZAPATA', '1982-08-30', NULL, 1, 0, '2025-11-04 21:22:38'),
(509, 267, '27222242', 'MARTIN ZAPATA', '1982-08-31', NULL, 1, 0, '2025-11-04 21:22:38'),
(510, 267, '27222243', 'GABRIEL HOLOTTE', '1982-09-01', NULL, 1, 0, '2025-11-04 21:22:38'),
(511, 267, '27222244', 'Francisco Dubs', '1982-09-02', NULL, 1, 0, '2025-11-04 21:22:38'),
(512, 267, '27222245', 'Harold emilio Schneider', '1982-09-03', NULL, 1, 0, '2025-11-04 21:22:38'),
(513, 248, '26222222', 'Roberto Andres Saavedra', '1982-08-11', NULL, 1, 0, '2025-11-04 21:22:50'),
(514, 248, '26222223', 'Roberto Adrian Benavidez', '1982-08-12', NULL, 1, 0, '2025-11-04 21:22:50'),
(515, 248, '26222224', 'Juan Exequiel Cordoba', '1982-08-13', NULL, 1, 0, '2025-11-04 21:22:50'),
(516, 248, '26222225', 'Juan Pablo Diaz', '1982-08-14', NULL, 1, 0, '2025-11-04 21:22:50'),
(517, 248, '26222226', 'Jose Garcia Arroyo', '1982-08-15', NULL, 1, 0, '2025-11-04 21:22:50'),
(518, 248, '26222227', 'Santiago David Steinert', '1982-08-16', NULL, 1, 0, '2025-11-04 21:22:50'),
(519, 248, '26222228', 'julio cesar todaro', '1982-08-17', NULL, 1, 0, '2025-11-04 21:22:50'),
(520, 248, '26222229', 'Franco ivan Morelli', '1982-08-18', NULL, 1, 0, '2025-11-04 21:22:50'),
(521, 248, '26222230', 'César Alberto Goncebatt', '1982-08-19', NULL, 1, 0, '2025-11-04 21:22:50'),
(522, 248, '26222231', 'Mariano Sabadia', '1982-08-20', NULL, 1, 0, '2025-11-04 21:22:50'),
(523, 248, '26222232', 'Exequiel Mauricio Riera', '1982-08-21', NULL, 1, 0, '2025-11-04 21:22:50'),
(524, 248, '26222233', 'Amado Ramón Altamirano', '1982-08-22', NULL, 1, 0, '2025-11-04 21:22:50'),
(525, 248, '26222234', 'Iván Romero', '1982-08-23', NULL, 1, 0, '2025-11-04 21:22:50'),
(526, 248, '26222235', 'Lorenzo Daniel Alvarez', '1982-08-24', NULL, 1, 0, '2025-11-04 21:22:50'),
(527, 248, '26222236', 'Juan Domingo Cabrera', '1982-08-25', NULL, 1, 0, '2025-11-04 21:22:50'),
(528, 248, '26222237', 'Jesús Sebastián Vicentin', '1982-08-26', NULL, 1, 0, '2025-11-04 21:22:50'),
(529, 248, '26222238', 'CARLOS RODRIGUEZ', '1982-08-27', NULL, 1, 0, '2025-11-04 21:22:50'),
(530, 248, '26222239', 'Romeo Héctor Molina', '1982-08-28', NULL, 1, 0, '2025-11-04 21:22:50'),
(531, 248, '26222240', 'Gustavo Andres Romero', '1982-08-29', NULL, 1, 0, '2025-11-04 21:22:50'),
(532, 248, '26222241', 'Ruben Eduardo Lacoste', '1982-08-30', NULL, 1, 0, '2025-11-04 21:22:50'),
(533, 248, '26222242', 'Roque Vallejo', '1982-08-31', NULL, 1, 0, '2025-11-04 21:22:50'),
(534, 248, '26222243', 'Mario Luis Misere', '1982-09-01', NULL, 1, 0, '2025-11-04 21:22:50'),
(535, 248, '26222244', 'Mariano Moretto', '1982-09-02', NULL, 1, 0, '2025-11-04 21:22:50'),
(536, 248, '26222245', 'Norberto sebastian Mariani', '1982-09-03', NULL, 1, 0, '2025-11-04 21:22:50'),
(537, 248, '26222246', 'Fabricio Miguel Sarmiento', '1982-09-04', NULL, 1, 0, '2025-11-04 21:22:50'),
(538, 248, '26222247', 'Nicolas emanuel Mendoza', '1982-09-05', NULL, 1, 0, '2025-11-04 21:22:50'),
(539, 247, '25220515', 'ALEJANDRO ESCOBUE', '1978-04-02', NULL, 1, 0, '2025-11-04 21:22:59'),
(540, 247, '25612555', 'Alexis Jose Ekkert', '1978-04-03', NULL, 1, 0, '2025-11-04 21:22:59'),
(541, 247, '26004595', 'ORLANDO BURGOS', '1978-04-04', NULL, 1, 0, '2025-11-04 21:22:59'),
(542, 247, '26396635', 'JAVIER PANELLI', '1978-04-05', NULL, 1, 0, '2025-11-04 21:22:59'),
(543, 247, '26788675', 'Ruben Alejandro Barrientos', '1978-04-06', NULL, 1, 0, '2025-11-04 21:22:59'),
(544, 247, '27180715', 'MARCOS CLARIA', '1978-04-07', NULL, 1, 0, '2025-11-04 21:22:59'),
(545, 247, '27572755', 'JUAN IGARZA', '1978-04-08', NULL, 1, 0, '2025-11-04 21:22:59'),
(546, 247, '27964795', 'SEBASTIAN KRANS', '1978-04-09', NULL, 1, 0, '2025-11-04 21:22:59'),
(547, 247, '28356835', 'ALEJANDRO FRANSCONI', '1978-04-10', NULL, 1, 0, '2025-11-04 21:22:59'),
(548, 247, '28748875', 'DIEGO TORTUL', '1978-04-11', NULL, 1, 0, '2025-11-04 21:22:59'),
(549, 247, '29140915', 'CLAUDIO CEBALLOS', '1978-04-12', NULL, 1, 0, '2025-11-04 21:22:59'),
(550, 247, '29532955', 'Marcelo Fabián Diaz', '1978-04-13', NULL, 1, 0, '2025-11-04 21:22:59'),
(551, 247, '29924995', 'MATIAS ESCOBUE', '1978-04-14', NULL, 1, 0, '2025-11-04 21:22:59'),
(552, 247, '30317035', 'DANIEL BLANCO', '1978-04-15', NULL, 1, 0, '2025-11-04 21:22:59'),
(553, 247, '30709075', 'ANIBAL MOREYRA', '1978-04-16', NULL, 1, 0, '2025-11-04 21:22:59'),
(554, 247, '31101115', 'ALBERTO BERON', '1978-04-17', NULL, 1, 0, '2025-11-04 21:22:59'),
(555, 247, '31493155', 'SEBASTIAN COMAS', '1978-04-18', NULL, 1, 0, '2025-11-04 21:22:59'),
(556, 247, '31885195', 'GASTON SANGOY', '1978-04-19', NULL, 1, 0, '2025-11-04 21:22:59'),
(557, 247, '32277235', 'Sergio Chitero', '1978-04-20', NULL, 1, 0, '2025-11-04 21:22:59'),
(558, 247, '32669275', 'Jose Montenegro', '1978-04-21', NULL, 1, 0, '2025-11-04 21:22:59'),
(559, 247, '33061315', 'walter Gomez', '1978-04-22', NULL, 1, 0, '2025-11-04 21:22:59'),
(560, 247, '33453355', 'Oscar Vallejo', '1978-04-23', NULL, 1, 0, '2025-11-04 21:22:59'),
(561, 252, '24222222', 'Pablo Gauna', '1982-08-11', NULL, 1, 0, '2025-11-04 21:23:16'),
(562, 252, '24222223', 'EDGARDO JOSE SEGOVIA', '1982-08-12', NULL, 1, 0, '2025-11-04 21:23:16'),
(563, 252, '24222224', 'Santiago Emanuel chavez', '1982-08-13', NULL, 1, 0, '2025-11-04 21:23:16'),
(564, 252, '24222225', 'Roberto Nicolás Nuñez', '1982-08-14', NULL, 1, 0, '2025-11-04 21:23:16'),
(565, 252, '24222226', 'Jorge Andrés Giménez', '1982-08-15', NULL, 1, 0, '2025-11-04 21:23:16'),
(566, 252, '24222227', 'Javier Gaston Gonzalez', '1982-08-16', NULL, 1, 0, '2025-11-04 21:23:16'),
(567, 252, '24222228', 'Alberto Gregorio Alen', '1982-08-17', NULL, 1, 0, '2025-11-04 21:23:16'),
(568, 252, '24222229', 'Alejandro José Cabrera', '1982-08-18', NULL, 1, 0, '2025-11-04 21:23:16'),
(569, 252, '24222230', 'Julio Luciano Cerbin', '1982-08-19', NULL, 1, 0, '2025-11-04 21:23:16'),
(570, 252, '24222231', 'Marcos Barzola', '1982-08-20', NULL, 1, 0, '2025-11-04 21:23:16'),
(571, 252, '24222232', 'Claudio Torcuato', '1982-08-21', NULL, 1, 0, '2025-11-04 21:23:16'),
(572, 252, '24222233', 'Matias Sebastian Aguiar', '1982-08-22', NULL, 1, 0, '2025-11-04 21:23:16'),
(573, 252, '24222234', 'Gabriel Segovia', '1982-08-23', NULL, 1, 0, '2025-11-04 21:23:16'),
(574, 252, '24222235', 'Facundo Abrahan Diaz', '1982-08-24', NULL, 1, 0, '2025-11-04 21:23:16'),
(575, 252, '24222236', 'cesar marcelo Galloli', '1982-08-25', NULL, 1, 0, '2025-11-04 21:23:16'),
(576, 252, '24222237', 'Gustavo Jose Mendez', '1982-08-26', NULL, 1, 0, '2025-11-04 21:23:16'),
(577, 252, '24222238', 'Carlos Ramon Segovia', '1982-08-27', NULL, 1, 0, '2025-11-04 21:23:16'),
(578, 252, '24222239', 'Santiago Cardu', '1982-08-28', NULL, 1, 0, '2025-11-04 21:23:16'),
(579, 245, '2554255', 'Rodrigo Emiliano Lacaze', '1982-08-11', NULL, 1, 0, '2025-11-04 21:23:29'),
(580, 245, '2554256', 'Maximiliano Carelli', '1982-08-12', NULL, 1, 0, '2025-11-04 21:23:29'),
(581, 245, '2554257', 'Laureano Sebastián Gómez', '1982-08-13', NULL, 1, 0, '2025-11-04 21:23:29'),
(582, 245, '2554258', 'Juan Pablo Martinez', '1982-08-14', NULL, 1, 0, '2025-11-04 21:23:29'),
(583, 245, '2554259', 'Claudio Martin Calfacante', '1982-08-15', NULL, 1, 0, '2025-11-04 21:23:29'),
(584, 245, '2554260', 'Pablo Ramon Albornoz', '1982-08-16', NULL, 1, 0, '2025-11-04 21:23:29'),
(585, 245, '2554261', 'Ignacio Juan Pablo Comas', '1982-08-17', NULL, 1, 0, '2025-11-04 21:23:29'),
(586, 245, '2554262', 'Marcelo Cappellacci', '1982-08-18', NULL, 1, 0, '2025-11-04 21:23:29'),
(587, 245, '2554263', 'Juan Carlos Senger', '1982-08-19', NULL, 1, 0, '2025-11-04 21:23:29'),
(588, 245, '2554264', 'Juan Manuel Senger', '1982-08-20', NULL, 1, 0, '2025-11-04 21:23:29'),
(589, 245, '2554265', 'Hernan Pablo Herrlein', '1982-08-21', NULL, 1, 0, '2025-11-04 21:23:29'),
(590, 245, '2554266', 'Néstor Darío Alva', '1982-08-22', NULL, 1, 5, '2025-11-04 21:23:29'),
(591, 245, '2554267', 'Sebastián Falcón', '1982-08-23', NULL, 1, 0, '2025-11-04 21:23:29'),
(592, 245, '2554268', 'Blas Antonio Toso', '1982-08-24', NULL, 1, 0, '2025-11-04 21:23:29'),
(593, 245, '2554269', 'Luis Ivan Andres Jaureguiberry', '1982-08-25', NULL, 1, 0, '2025-11-04 21:23:29'),
(594, 245, '2554270', 'Walter Broder', '1982-08-26', 'jugadores/VerFoto_1.jpg', 1, 0, '2025-11-04 21:23:29'),
(595, 245, '2554271', 'Marcos Romero', '1982-08-27', NULL, 1, 0, '2025-11-04 21:23:29'),
(596, 245, '2554272', 'Nelson Fabian Factor', '1982-08-28', NULL, 1, 0, '2025-11-04 21:23:29'),
(597, 265, '2554273', 'Daniel Alberto Gonzalez', '1982-08-29', NULL, 1, 0, '2025-11-04 21:23:29'),
(598, 245, '2554274', 'Iván Noé Rabuffetti', '1982-08-30', NULL, 1, 0, '2025-11-04 21:23:29');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jugadores_equipos_historial`
--

CREATE TABLE `jugadores_equipos_historial` (
  `id` int(11) NOT NULL,
  `jugador_dni` varchar(15) NOT NULL,
  `jugador_nombre` varchar(100) NOT NULL,
  `equipo_id` int(11) NOT NULL,
  `campeonato_id` int(11) NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `partidos_jugados` int(11) DEFAULT 0,
  `goles` int(11) DEFAULT 0,
  `amarillas` int(11) DEFAULT 0,
  `rojas` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `jugadores_equipos_historial`
--

INSERT INTO `jugadores_equipos_historial` (`id`, `jugador_dni`, `jugador_nombre`, `equipo_id`, `campeonato_id`, `fecha_inicio`, `fecha_fin`, `partidos_jugados`, `goles`, `amarillas`, `rojas`, `created_at`) VALUES
(1, '29222222', 'Marcos Azcurrain', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(2, '29222223', 'Marcos ezequiel Chavez', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(3, '29222224', 'Edgar Martinez', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(4, '29222225', 'Miguel oscar Soriani', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(5, '29222226', 'Claudio rafael Pais', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(6, '29222227', 'Carlos Gonzalez', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(7, '29222228', 'Dante Andreoli', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(8, '29222229', 'Lucas gonzalo Cabaña', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(9, '29222230', 'Daniel ricardo Roldan', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(10, '29222231', 'Matias jose Delavalle', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(11, '29222232', 'Carlos Marquesin', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(12, '29222233', 'Raul Suarez', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(13, '29222234', 'Francisco Cian', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(14, '29222235', 'Luciano ruben Lorenzon', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(15, '29222236', 'Renzo gonzalo Vera', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(16, '29222237', 'Hernan Leonardo Vazquez', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(17, '29222238', 'Héctor Adrián Schneider', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(18, '29222239', 'Luis Ramón Miguel Acuña', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(19, '29222240', 'Omar Michelin', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(20, '29222241', 'Cristian fabian Nichea', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(21, '29222242', 'Javier Alejandro Garcilazo', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(22, '29222243', 'Luciano martin Navarro', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(23, '29222244', 'Facundo Delavalle', 244, 3, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 21:21:53'),
(24, '28222222', 'Bruno Maximiliano Corino', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(25, '28222223', 'Diego Sebastian Guardoni', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(26, '28222224', 'Sergio Eduardo Almada', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(27, '28222225', 'Jonathan Erbes', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(28, '28222226', 'Jonatan Pereyra', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(29, '28222227', 'Sebastian Esmeri', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(30, '28222228', 'Ezequiel Adalberto Corino', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(31, '28222229', 'Alejandro Ruben Panelli', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(32, '28222230', 'Hernan Jesus Rivero', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(33, '28222231', 'Rolando Wenceslao Francisco Mansilla', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(34, '28222232', 'Roberto Ariel Panelli', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(35, '28222233', 'Gaston Silveyra', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(36, '28222234', 'rolando Alberto Flores', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(37, '28222235', 'Claudio Lionel carrere', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(38, '28222236', 'Gustavo Daniel Pressel', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(39, '28222237', 'Gonzalo Héctor Ariel Ledesma', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(40, '28222238', 'Diego Torilla', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(41, '28222239', 'Emanuel Andino', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(42, '28222240', 'Sebastian Wulfsohn', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(43, '28222241', 'Sebastian Martinez', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:22:23'),
(44, '27222222', 'Gustavo Daniel Bozzo', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(45, '27222223', 'Gaston Emiliano Musuruana', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(46, '27222224', 'gabriel alejandro Gerstner', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(47, '27222225', 'Narciso Mallo', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(48, '27222226', 'Pablo Chimento', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(49, '27222227', 'Mariano Premaries', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(50, '27222228', 'Iván Alejandro Furios', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(51, '27222229', 'Gabriel Alejandro Prina', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(52, '27222230', 'Cesar andres Vazquez', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(53, '27222231', 'Javier Ricardo Zapata', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(54, '27222232', 'Matias Hernan Zapata', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(55, '27222233', 'Marcelo Fabian Sanchez', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(56, '27222234', 'JAVIER GADEA', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(57, '27222235', 'SEBASTIAN RAU', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(58, '27222236', 'GONZALO AYALA', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(59, '27222237', 'CRISTIAN SCHNEIDER', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(60, '27222238', 'Cristian Diego Feltes', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(61, '27222239', 'DARIO REGNER', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(62, '27222240', 'LEANDRO WACHTMEISTER', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(63, '27222241', 'PABLO ZAPATA', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(64, '27222242', 'MARTIN ZAPATA', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(65, '27222243', 'GABRIEL HOLOTTE', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(66, '27222244', 'Francisco Dubs', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(67, '27222245', 'Harold emilio Schneider', 242, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:38'),
(68, '26222222', 'Roberto Andres Saavedra', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(69, '26222223', 'Roberto Adrian Benavidez', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(70, '26222224', 'Juan Exequiel Cordoba', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(71, '26222225', 'Juan Pablo Diaz', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(72, '26222226', 'Jose Garcia Arroyo', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(73, '26222227', 'Santiago David Steinert', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(74, '26222228', 'julio cesar todaro', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(75, '26222229', 'Franco ivan Morelli', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(76, '26222230', 'César Alberto Goncebatt', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(77, '26222231', 'Mariano Sabadia', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(78, '26222232', 'Exequiel Mauricio Riera', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(79, '26222233', 'Amado Ramón Altamirano', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(80, '26222234', 'Iván Romero', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(81, '26222235', 'Lorenzo Daniel Alvarez', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(82, '26222236', 'Juan Domingo Cabrera', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(83, '26222237', 'Jesús Sebastián Vicentin', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(84, '26222238', 'CARLOS RODRIGUEZ', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(85, '26222239', 'Romeo Héctor Molina', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(86, '26222240', 'Gustavo Andres Romero', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(87, '26222241', 'Ruben Eduardo Lacoste', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(88, '26222242', 'Roque Vallejo', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(89, '26222243', 'Mario Luis Misere', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(90, '26222244', 'Mariano Moretto', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(91, '26222245', 'Norberto sebastian Mariani', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(92, '26222246', 'Fabricio Miguel Sarmiento', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(93, '26222247', 'Nicolas emanuel Mendoza', 241, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:50'),
(94, '25220515', 'ALEJANDRO ESCOBUE', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(95, '25612555', 'Alexis Jose Ekkert', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(96, '26004595', 'ORLANDO BURGOS', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(97, '26396635', 'JAVIER PANELLI', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(98, '26788675', 'Ruben Alejandro Barrientos', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(99, '27180715', 'MARCOS CLARIA', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(100, '27572755', 'JUAN IGARZA', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(101, '27964795', 'SEBASTIAN KRANS', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(102, '28356835', 'ALEJANDRO FRANSCONI', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(103, '28748875', 'DIEGO TORTUL', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(104, '29140915', 'CLAUDIO CEBALLOS', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(105, '29532955', 'Marcelo Fabián Diaz', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(106, '29924995', 'MATIAS ESCOBUE', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(107, '30317035', 'DANIEL BLANCO', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(108, '30709075', 'ANIBAL MOREYRA', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(109, '31101115', 'ALBERTO BERON', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(110, '31493155', 'SEBASTIAN COMAS', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(111, '31885195', 'GASTON SANGOY', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(112, '32277235', 'Sergio Chitero', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(113, '32669275', 'Jose Montenegro', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(114, '33061315', 'walter Gomez', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(115, '33453355', 'Oscar Vallejo', 240, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:22:59'),
(116, '24222222', 'Pablo Gauna', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(117, '24222223', 'EDGARDO JOSE SEGOVIA', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(118, '24222224', 'Santiago Emanuel chavez', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(119, '24222225', 'Roberto Nicolás Nuñez', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(120, '24222226', 'Jorge Andrés Giménez', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(121, '24222227', 'Javier Gaston Gonzalez', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(122, '24222228', 'Alberto Gregorio Alen', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(123, '24222229', 'Alejandro José Cabrera', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(124, '24222230', 'Julio Luciano Cerbin', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(125, '24222231', 'Marcos Barzola', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(126, '24222232', 'Claudio Torcuato', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(127, '24222233', 'Matias Sebastian Aguiar', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(128, '24222234', 'Gabriel Segovia', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(129, '24222235', 'Facundo Abrahan Diaz', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(130, '24222236', 'cesar marcelo Galloli', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(131, '24222237', 'Gustavo Jose Mendez', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(132, '24222238', 'Carlos Ramon Segovia', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(133, '24222239', 'Santiago Cardu', 239, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:16'),
(134, '2554255', 'Rodrigo Emiliano Lacaze', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(135, '2554256', 'Maximiliano Carelli', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(136, '2554257', 'Laureano Sebastián Gómez', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(137, '2554258', 'Juan Pablo Martinez', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(138, '2554259', 'Claudio Martin Calfacante', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(139, '2554260', 'Pablo Ramon Albornoz', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(140, '2554261', 'Ignacio Juan Pablo Comas', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(141, '2554262', 'Marcelo Cappellacci', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(142, '2554263', 'Juan Carlos Senger', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(143, '2554264', 'Juan Manuel Senger', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(144, '2554265', 'Hernan Pablo Herrlein', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(145, '2554266', 'Néstor Darío Alva', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(146, '2554267', 'Sebastián Falcón', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(147, '2554268', 'Blas Antonio Toso', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(148, '2554269', 'Luis Ivan Andres Jaureguiberry', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(149, '2554270', 'Walter Broder', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(150, '2554271', 'Marcos Romero', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(151, '2554272', 'Nelson Fabian Factor', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(152, '2554273', 'Daniel Alberto Gonzalez', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(153, '2554274', 'Iván Noé Rabuffetti', 238, 3, '2025-11-04', '2025-11-04', 0, 0, 0, 0, '2025-11-04 21:23:29'),
(154, '2554273', 'Daniel Alberto Gonzalez', 243, 3, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 21:58:43'),
(155, '2554255', 'Rodrigo Emiliano Lacaze', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(156, '2554256', 'Maximiliano Carelli', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(157, '2554257', 'Laureano Sebastián Gómez', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(158, '2554258', 'Juan Pablo Martinez', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(159, '2554259', 'Claudio Martin Calfacante', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(160, '2554260', 'Pablo Ramon Albornoz', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(161, '2554261', 'Ignacio Juan Pablo Comas', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(162, '2554262', 'Marcelo Cappellacci', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(163, '2554263', 'Juan Carlos Senger', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(164, '2554264', 'Juan Manuel Senger', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(165, '2554265', 'Hernan Pablo Herrlein', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(166, '2554266', 'Néstor Darío Alva', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(167, '2554267', 'Sebastián Falcón', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(168, '2554268', 'Blas Antonio Toso', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(169, '2554269', 'Luis Ivan Andres Jaureguiberry', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(170, '2554270', 'Walter Broder', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(171, '2554271', 'Marcos Romero', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(172, '2554272', 'Nelson Fabian Factor', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(173, '2554274', 'Iván Noé Rabuffetti', 245, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:10:47'),
(174, '24222222', 'Pablo Gauna', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(175, '24222223', 'EDGARDO JOSE SEGOVIA', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(176, '24222224', 'Santiago Emanuel chavez', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(177, '24222225', 'Roberto Nicolás Nuñez', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(178, '24222226', 'Jorge Andrés Giménez', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(179, '24222227', 'Javier Gaston Gonzalez', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(180, '24222228', 'Alberto Gregorio Alen', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(181, '24222229', 'Alejandro José Cabrera', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(182, '24222230', 'Julio Luciano Cerbin', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(183, '24222231', 'Marcos Barzola', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(184, '24222232', 'Claudio Torcuato', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(185, '24222233', 'Matias Sebastian Aguiar', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(186, '24222234', 'Gabriel Segovia', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(187, '24222235', 'Facundo Abrahan Diaz', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(188, '24222236', 'cesar marcelo Galloli', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(189, '24222237', 'Gustavo Jose Mendez', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(190, '24222238', 'Carlos Ramon Segovia', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(191, '24222239', 'Santiago Cardu', 246, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:10:57'),
(192, '25220515', 'ALEJANDRO ESCOBUE', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(193, '25612555', 'Alexis Jose Ekkert', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(194, '26004595', 'ORLANDO BURGOS', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(195, '26396635', 'JAVIER PANELLI', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(196, '26788675', 'Ruben Alejandro Barrientos', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(197, '27180715', 'MARCOS CLARIA', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(198, '27572755', 'JUAN IGARZA', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(199, '27964795', 'SEBASTIAN KRANS', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(200, '28356835', 'ALEJANDRO FRANSCONI', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(201, '28748875', 'DIEGO TORTUL', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(202, '29140915', 'CLAUDIO CEBALLOS', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(203, '29532955', 'Marcelo Fabián Diaz', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(204, '29924995', 'MATIAS ESCOBUE', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(205, '30317035', 'DANIEL BLANCO', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(206, '30709075', 'ANIBAL MOREYRA', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(207, '31101115', 'ALBERTO BERON', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(208, '31493155', 'SEBASTIAN COMAS', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(209, '31885195', 'GASTON SANGOY', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(210, '32277235', 'Sergio Chitero', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(211, '32669275', 'Jose Montenegro', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(212, '33061315', 'walter Gomez', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(213, '33453355', 'Oscar Vallejo', 247, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:30'),
(214, '26222222', 'Roberto Andres Saavedra', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(215, '26222223', 'Roberto Adrian Benavidez', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(216, '26222224', 'Juan Exequiel Cordoba', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(217, '26222225', 'Juan Pablo Diaz', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(218, '26222226', 'Jose Garcia Arroyo', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(219, '26222227', 'Santiago David Steinert', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(220, '26222228', 'julio cesar todaro', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(221, '26222229', 'Franco ivan Morelli', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(222, '26222230', 'César Alberto Goncebatt', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(223, '26222231', 'Mariano Sabadia', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(224, '26222232', 'Exequiel Mauricio Riera', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(225, '26222233', 'Amado Ramón Altamirano', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(226, '26222234', 'Iván Romero', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(227, '26222235', 'Lorenzo Daniel Alvarez', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(228, '26222236', 'Juan Domingo Cabrera', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(229, '26222237', 'Jesús Sebastián Vicentin', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(230, '26222238', 'CARLOS RODRIGUEZ', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(231, '26222239', 'Romeo Héctor Molina', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(232, '26222240', 'Gustavo Andres Romero', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(233, '26222241', 'Ruben Eduardo Lacoste', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(234, '26222242', 'Roque Vallejo', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(235, '26222243', 'Mario Luis Misere', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(236, '26222244', 'Mariano Moretto', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(237, '26222245', 'Norberto sebastian Mariani', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(238, '26222246', 'Fabricio Miguel Sarmiento', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(239, '26222247', 'Nicolas emanuel Mendoza', 248, 7, '2025-11-04', NULL, 0, 0, 0, 0, '2025-11-04 22:11:42'),
(240, '27222222', 'Gustavo Daniel Bozzo', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(241, '27222223', 'Gaston Emiliano Musuruana', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(242, '27222224', 'gabriel alejandro Gerstner', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(243, '27222225', 'Narciso Mallo', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(244, '27222226', 'Pablo Chimento', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(245, '27222227', 'Mariano Premaries', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(246, '27222228', 'Iván Alejandro Furios', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(247, '27222229', 'Gabriel Alejandro Prina', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(248, '27222230', 'Cesar andres Vazquez', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(249, '27222231', 'Javier Ricardo Zapata', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(250, '27222232', 'Matias Hernan Zapata', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(251, '27222233', 'Marcelo Fabian Sanchez', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(252, '27222234', 'JAVIER GADEA', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(253, '27222235', 'SEBASTIAN RAU', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(254, '27222236', 'GONZALO AYALA', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(255, '27222237', 'CRISTIAN SCHNEIDER', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(256, '27222238', 'Cristian Diego Feltes', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(257, '27222239', 'DARIO REGNER', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(258, '27222240', 'LEANDRO WACHTMEISTER', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(259, '27222241', 'PABLO ZAPATA', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(260, '27222242', 'MARTIN ZAPATA', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(261, '27222243', 'GABRIEL HOLOTTE', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(262, '27222244', 'Francisco Dubs', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(263, '27222245', 'Harold emilio Schneider', 249, 7, '2025-11-04', '2025-11-05', 0, 0, 0, 0, '2025-11-04 22:11:52'),
(264, '24222222', 'Pablo Gauna', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(265, '24222223', 'EDGARDO JOSE SEGOVIA', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(266, '24222224', 'Santiago Emanuel chavez', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(267, '24222225', 'Roberto Nicolás Nuñez', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(268, '24222226', 'Jorge Andrés Giménez', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(269, '24222227', 'Javier Gaston Gonzalez', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(270, '24222228', 'Alberto Gregorio Alen', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(271, '24222229', 'Alejandro José Cabrera', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(272, '24222230', 'Julio Luciano Cerbin', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(273, '24222231', 'Marcos Barzola', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(274, '24222232', 'Claudio Torcuato', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(275, '24222233', 'Matias Sebastian Aguiar', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(276, '24222234', 'Gabriel Segovia', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(277, '24222235', 'Facundo Abrahan Diaz', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(278, '24222236', 'cesar marcelo Galloli', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(279, '24222237', 'Gustavo Jose Mendez', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(280, '24222238', 'Carlos Ramon Segovia', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(281, '24222239', 'Santiago Cardu', 252, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:57:48'),
(282, '28222222', 'Bruno Maximiliano Corino', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(283, '28222223', 'Diego Sebastian Guardoni', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(284, '28222224', 'Sergio Eduardo Almada', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(285, '28222225', 'Jonathan Erbes', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(286, '28222226', 'Jonatan Pereyra', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(287, '28222227', 'Sebastian Esmeri', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(288, '28222228', 'Ezequiel Adalberto Corino', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(289, '28222229', 'Alejandro Ruben Panelli', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(290, '28222230', 'Hernan Jesus Rivero', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(291, '28222231', 'Rolando Wenceslao Francisco Mansilla', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(292, '28222232', 'Roberto Ariel Panelli', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(293, '28222233', 'Gaston Silveyra', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(294, '28222234', 'rolando Alberto Flores', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(295, '28222235', 'Claudio Lionel carrere', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(296, '28222236', 'Gustavo Daniel Pressel', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(297, '28222237', 'Gonzalo Héctor Ariel Ledesma', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(298, '28222238', 'Diego Torilla', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(299, '28222239', 'Emanuel Andino', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(300, '28222240', 'Sebastian Wulfsohn', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(301, '28222241', 'Sebastian Martinez', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(302, '2554273', 'Daniel Alberto Gonzalez', 265, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:31'),
(303, '27222222', 'Gustavo Daniel Bozzo', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(304, '27222223', 'Gaston Emiliano Musuruana', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(305, '27222224', 'gabriel alejandro Gerstner', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(306, '27222225', 'Narciso Mallo', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(307, '27222226', 'Pablo Chimento', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(308, '27222227', 'Mariano Premaries', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(309, '27222228', 'Iván Alejandro Furios', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(310, '27222229', 'Gabriel Alejandro Prina', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(311, '27222230', 'Cesar andres Vazquez', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(312, '27222231', 'Javier Ricardo Zapata', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(313, '27222232', 'Matias Hernan Zapata', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(314, '27222233', 'Marcelo Fabian Sanchez', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(315, '27222234', 'JAVIER GADEA', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(316, '27222235', 'SEBASTIAN RAU', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(317, '27222236', 'GONZALO AYALA', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(318, '27222237', 'CRISTIAN SCHNEIDER', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(319, '27222238', 'Cristian Diego Feltes', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(320, '27222239', 'DARIO REGNER', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(321, '27222240', 'LEANDRO WACHTMEISTER', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(322, '27222241', 'PABLO ZAPATA', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(323, '27222242', 'MARTIN ZAPATA', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(324, '27222243', 'GABRIEL HOLOTTE', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(325, '27222244', 'Francisco Dubs', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43'),
(326, '27222245', 'Harold emilio Schneider', 267, 8, '2025-11-05', NULL, 0, 0, 0, 0, '2025-11-05 00:59:43');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `jugadores_partido`
--

CREATE TABLE `jugadores_partido` (
  `id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL,
  `jugador_id` int(11) NOT NULL,
  `numero_camiseta` int(11) DEFAULT 0,
  `tipo_partido` varchar(20) DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `jugadores_partido`
--

INSERT INTO `jugadores_partido` (`id`, `partido_id`, `jugador_id`, `numero_camiseta`, `tipo_partido`, `created_at`) VALUES
(1775, 134, 513, 0, 'normal', '2025-11-04 21:31:11'),
(1776, 134, 514, 0, 'normal', '2025-11-04 21:31:11'),
(1777, 134, 515, 0, 'normal', '2025-11-04 21:31:11'),
(1778, 134, 516, 0, 'normal', '2025-11-04 21:31:11'),
(1779, 134, 517, 0, 'normal', '2025-11-04 21:31:11'),
(1780, 134, 518, 0, 'normal', '2025-11-04 21:31:11'),
(1781, 134, 519, 0, 'normal', '2025-11-04 21:31:11'),
(1782, 134, 520, 0, 'normal', '2025-11-04 21:31:11'),
(1783, 134, 521, 0, 'normal', '2025-11-04 21:31:11'),
(1784, 134, 522, 0, 'normal', '2025-11-04 21:31:11'),
(1785, 134, 523, 0, 'normal', '2025-11-04 21:31:11'),
(1786, 134, 524, 0, 'normal', '2025-11-04 21:31:11'),
(1787, 134, 525, 0, 'normal', '2025-11-04 21:31:11'),
(1788, 134, 526, 0, 'normal', '2025-11-04 21:31:11'),
(1789, 134, 527, 0, 'normal', '2025-11-04 21:31:11'),
(1790, 134, 528, 0, 'normal', '2025-11-04 21:31:11'),
(1791, 134, 529, 0, 'normal', '2025-11-04 21:31:11'),
(1792, 134, 530, 0, 'normal', '2025-11-04 21:31:11'),
(1793, 134, 531, 0, 'normal', '2025-11-04 21:31:11'),
(1794, 134, 532, 0, 'normal', '2025-11-04 21:31:11'),
(1795, 134, 533, 0, 'normal', '2025-11-04 21:31:11'),
(1796, 134, 534, 0, 'normal', '2025-11-04 21:31:11'),
(1797, 134, 535, 0, 'normal', '2025-11-04 21:31:11'),
(1798, 134, 536, 0, 'normal', '2025-11-04 21:31:11'),
(1799, 134, 537, 0, 'normal', '2025-11-04 21:31:11'),
(1800, 134, 538, 0, 'normal', '2025-11-04 21:31:11'),
(1801, 134, 579, 0, 'normal', '2025-11-04 21:31:11'),
(1802, 134, 580, 0, 'normal', '2025-11-04 21:31:11'),
(1803, 134, 581, 0, 'normal', '2025-11-04 21:31:11'),
(1804, 134, 582, 0, 'normal', '2025-11-04 21:31:11'),
(1805, 134, 583, 0, 'normal', '2025-11-04 21:31:11'),
(1806, 134, 584, 0, 'normal', '2025-11-04 21:31:11'),
(1807, 134, 585, 0, 'normal', '2025-11-04 21:31:11'),
(1808, 134, 586, 0, 'normal', '2025-11-04 21:31:11'),
(1809, 134, 587, 0, 'normal', '2025-11-04 21:31:11'),
(1810, 134, 588, 0, 'normal', '2025-11-04 21:31:11'),
(1811, 134, 589, 0, 'normal', '2025-11-04 21:31:11'),
(1812, 134, 590, 0, 'normal', '2025-11-04 21:31:11'),
(1813, 134, 591, 0, 'normal', '2025-11-04 21:31:11'),
(1814, 134, 592, 0, 'normal', '2025-11-04 21:31:11'),
(1815, 134, 593, 0, 'normal', '2025-11-04 21:31:11'),
(1816, 134, 594, 0, 'normal', '2025-11-04 21:31:11'),
(1817, 134, 595, 0, 'normal', '2025-11-04 21:31:11'),
(1818, 134, 596, 0, 'normal', '2025-11-04 21:31:11'),
(1819, 134, 597, 0, 'normal', '2025-11-04 21:31:11'),
(1820, 134, 598, 0, 'normal', '2025-11-04 21:31:11'),
(1821, 138, 561, 0, 'normal', '2025-11-04 21:32:00'),
(1822, 138, 562, 0, 'normal', '2025-11-04 21:32:00'),
(1823, 138, 563, 0, 'normal', '2025-11-04 21:32:00'),
(1824, 138, 564, 0, 'normal', '2025-11-04 21:32:00'),
(1825, 138, 565, 0, 'normal', '2025-11-04 21:32:00'),
(1826, 138, 566, 0, 'normal', '2025-11-04 21:32:00'),
(1827, 138, 567, 0, 'normal', '2025-11-04 21:32:00'),
(1828, 138, 568, 0, 'normal', '2025-11-04 21:32:00'),
(1829, 138, 569, 0, 'normal', '2025-11-04 21:32:00'),
(1830, 138, 570, 0, 'normal', '2025-11-04 21:32:00'),
(1831, 138, 571, 0, 'normal', '2025-11-04 21:32:00'),
(1832, 138, 572, 0, 'normal', '2025-11-04 21:32:00'),
(1833, 138, 573, 0, 'normal', '2025-11-04 21:32:00'),
(1834, 138, 574, 0, 'normal', '2025-11-04 21:32:00'),
(1835, 138, 575, 0, 'normal', '2025-11-04 21:32:00'),
(1836, 138, 576, 0, 'normal', '2025-11-04 21:32:00'),
(1837, 138, 577, 0, 'normal', '2025-11-04 21:32:00'),
(1838, 138, 578, 0, 'normal', '2025-11-04 21:32:00'),
(1839, 138, 579, 0, 'normal', '2025-11-04 21:32:00'),
(1840, 138, 580, 0, 'normal', '2025-11-04 21:32:00'),
(1841, 138, 581, 0, 'normal', '2025-11-04 21:32:00'),
(1842, 138, 582, 0, 'normal', '2025-11-04 21:32:00'),
(1843, 138, 583, 0, 'normal', '2025-11-04 21:32:00'),
(1844, 138, 584, 0, 'normal', '2025-11-04 21:32:00'),
(1845, 138, 585, 0, 'normal', '2025-11-04 21:32:00'),
(1846, 138, 586, 0, 'normal', '2025-11-04 21:32:00'),
(1847, 138, 587, 0, 'normal', '2025-11-04 21:32:00'),
(1848, 138, 588, 0, 'normal', '2025-11-04 21:32:00'),
(1849, 138, 589, 0, 'normal', '2025-11-04 21:32:00'),
(1850, 138, 590, 0, 'normal', '2025-11-04 21:32:00'),
(1851, 138, 591, 0, 'normal', '2025-11-04 21:32:00'),
(1852, 138, 592, 0, 'normal', '2025-11-04 21:32:00'),
(1853, 138, 593, 0, 'normal', '2025-11-04 21:32:00'),
(1854, 138, 594, 0, 'normal', '2025-11-04 21:32:00'),
(1855, 138, 595, 0, 'normal', '2025-11-04 21:32:00'),
(1856, 138, 596, 0, 'normal', '2025-11-04 21:32:00'),
(1857, 138, 597, 0, 'normal', '2025-11-04 21:32:00'),
(1858, 138, 598, 0, 'normal', '2025-11-04 21:32:00'),
(1859, 141, 579, 0, 'normal', '2025-11-04 21:32:31'),
(1860, 141, 580, 0, 'normal', '2025-11-04 21:32:31'),
(1861, 141, 581, 0, 'normal', '2025-11-04 21:32:31'),
(1862, 141, 582, 0, 'normal', '2025-11-04 21:32:31'),
(1863, 141, 583, 0, 'normal', '2025-11-04 21:32:31'),
(1864, 141, 584, 0, 'normal', '2025-11-04 21:32:31'),
(1865, 141, 585, 0, 'normal', '2025-11-04 21:32:31'),
(1866, 141, 586, 0, 'normal', '2025-11-04 21:32:31'),
(1867, 141, 587, 0, 'normal', '2025-11-04 21:32:31'),
(1868, 141, 588, 0, 'normal', '2025-11-04 21:32:31'),
(1869, 141, 589, 0, 'normal', '2025-11-04 21:32:31'),
(1870, 141, 590, 0, 'normal', '2025-11-04 21:32:31'),
(1871, 141, 591, 0, 'normal', '2025-11-04 21:32:31'),
(1872, 141, 592, 0, 'normal', '2025-11-04 21:32:31'),
(1873, 141, 593, 0, 'normal', '2025-11-04 21:32:31'),
(1874, 141, 594, 0, 'normal', '2025-11-04 21:32:31'),
(1875, 141, 595, 0, 'normal', '2025-11-04 21:32:31'),
(1876, 141, 596, 0, 'normal', '2025-11-04 21:32:31'),
(1877, 141, 597, 0, 'normal', '2025-11-04 21:32:31'),
(1878, 141, 598, 0, 'normal', '2025-11-04 21:32:31'),
(1879, 141, 446, 0, 'normal', '2025-11-04 21:32:31'),
(1880, 141, 447, 0, 'normal', '2025-11-04 21:32:31'),
(1881, 141, 448, 0, 'normal', '2025-11-04 21:32:31'),
(1882, 141, 449, 0, 'normal', '2025-11-04 21:32:31'),
(1883, 141, 450, 0, 'normal', '2025-11-04 21:32:31'),
(1884, 141, 451, 0, 'normal', '2025-11-04 21:32:31'),
(1885, 141, 452, 0, 'normal', '2025-11-04 21:32:31'),
(1886, 141, 453, 0, 'normal', '2025-11-04 21:32:31'),
(1887, 141, 454, 0, 'normal', '2025-11-04 21:32:31'),
(1888, 141, 455, 0, 'normal', '2025-11-04 21:32:31'),
(1889, 141, 456, 0, 'normal', '2025-11-04 21:32:31'),
(1890, 141, 457, 0, 'normal', '2025-11-04 21:32:31'),
(1891, 141, 458, 0, 'normal', '2025-11-04 21:32:31'),
(1892, 141, 459, 0, 'normal', '2025-11-04 21:32:31'),
(1893, 141, 460, 0, 'normal', '2025-11-04 21:32:31'),
(1894, 141, 461, 0, 'normal', '2025-11-04 21:32:31'),
(1895, 141, 462, 0, 'normal', '2025-11-04 21:32:31'),
(1896, 141, 463, 0, 'normal', '2025-11-04 21:32:31'),
(1897, 141, 464, 0, 'normal', '2025-11-04 21:32:31'),
(1898, 141, 465, 0, 'normal', '2025-11-04 21:32:31'),
(1899, 141, 466, 0, 'normal', '2025-11-04 21:32:31'),
(1900, 141, 467, 0, 'normal', '2025-11-04 21:32:31'),
(1901, 141, 468, 0, 'normal', '2025-11-04 21:32:31'),
(1902, 143, 579, 0, 'normal', '2025-11-04 21:34:00'),
(1903, 143, 580, 0, 'normal', '2025-11-04 21:34:00'),
(1904, 143, 581, 0, 'normal', '2025-11-04 21:34:00'),
(1905, 143, 582, 0, 'normal', '2025-11-04 21:34:00'),
(1906, 143, 583, 0, 'normal', '2025-11-04 21:34:00'),
(1907, 143, 584, 0, 'normal', '2025-11-04 21:34:00'),
(1908, 143, 585, 0, 'normal', '2025-11-04 21:34:00'),
(1909, 143, 586, 0, 'normal', '2025-11-04 21:34:00'),
(1910, 143, 587, 0, 'normal', '2025-11-04 21:34:00'),
(1911, 143, 588, 0, 'normal', '2025-11-04 21:34:00'),
(1912, 143, 589, 0, 'normal', '2025-11-04 21:34:00'),
(1913, 143, 590, 0, 'normal', '2025-11-04 21:34:00'),
(1914, 143, 591, 0, 'normal', '2025-11-04 21:34:00'),
(1915, 143, 592, 0, 'normal', '2025-11-04 21:34:00'),
(1916, 143, 593, 0, 'normal', '2025-11-04 21:34:00'),
(1917, 143, 594, 0, 'normal', '2025-11-04 21:34:00'),
(1918, 143, 595, 0, 'normal', '2025-11-04 21:34:00'),
(1919, 143, 596, 0, 'normal', '2025-11-04 21:34:00'),
(1920, 143, 597, 0, 'normal', '2025-11-04 21:34:00'),
(1921, 143, 598, 0, 'normal', '2025-11-04 21:34:00'),
(1922, 143, 489, 0, 'normal', '2025-11-04 21:34:00'),
(1923, 143, 490, 0, 'normal', '2025-11-04 21:34:00'),
(1924, 143, 491, 0, 'normal', '2025-11-04 21:34:00'),
(1925, 143, 492, 0, 'normal', '2025-11-04 21:34:00'),
(1926, 143, 493, 0, 'normal', '2025-11-04 21:34:00'),
(1927, 143, 494, 0, 'normal', '2025-11-04 21:34:00'),
(1928, 143, 495, 0, 'normal', '2025-11-04 21:34:00'),
(1929, 143, 496, 0, 'normal', '2025-11-04 21:34:00'),
(1930, 143, 497, 0, 'normal', '2025-11-04 21:34:00'),
(1931, 143, 498, 0, 'normal', '2025-11-04 21:34:00'),
(1932, 143, 499, 0, 'normal', '2025-11-04 21:34:00'),
(1933, 143, 500, 0, 'normal', '2025-11-04 21:34:00'),
(1934, 143, 501, 0, 'normal', '2025-11-04 21:34:00'),
(1935, 143, 502, 0, 'normal', '2025-11-04 21:34:00'),
(1936, 143, 503, 0, 'normal', '2025-11-04 21:34:00'),
(1937, 143, 504, 0, 'normal', '2025-11-04 21:34:00'),
(1938, 143, 505, 0, 'normal', '2025-11-04 21:34:00'),
(1939, 143, 506, 0, 'normal', '2025-11-04 21:34:00'),
(1940, 143, 507, 0, 'normal', '2025-11-04 21:34:00'),
(1941, 143, 508, 0, 'normal', '2025-11-04 21:34:00'),
(1942, 143, 509, 0, 'normal', '2025-11-04 21:34:00'),
(1943, 143, 510, 0, 'normal', '2025-11-04 21:34:00'),
(1944, 143, 511, 0, 'normal', '2025-11-04 21:34:00'),
(1945, 143, 512, 0, 'normal', '2025-11-04 21:34:00'),
(1946, 145, 579, 0, 'normal', '2025-11-04 21:49:00'),
(1947, 145, 580, 0, 'normal', '2025-11-04 21:49:00'),
(1948, 145, 581, 0, 'normal', '2025-11-04 21:49:00'),
(1949, 145, 582, 0, 'normal', '2025-11-04 21:49:00'),
(1950, 145, 583, 0, 'normal', '2025-11-04 21:49:00'),
(1951, 145, 584, 0, 'normal', '2025-11-04 21:49:00'),
(1952, 145, 585, 0, 'normal', '2025-11-04 21:49:00'),
(1953, 145, 586, 0, 'normal', '2025-11-04 21:49:00'),
(1954, 145, 587, 0, 'normal', '2025-11-04 21:49:00'),
(1955, 145, 588, 0, 'normal', '2025-11-04 21:49:00'),
(1956, 145, 589, 0, 'normal', '2025-11-04 21:49:00'),
(1957, 145, 590, 0, 'normal', '2025-11-04 21:49:00'),
(1958, 145, 591, 0, 'normal', '2025-11-04 21:49:00'),
(1959, 145, 592, 0, 'normal', '2025-11-04 21:49:00'),
(1960, 145, 593, 0, 'normal', '2025-11-04 21:49:00'),
(1961, 145, 594, 0, 'normal', '2025-11-04 21:49:00'),
(1962, 145, 595, 0, 'normal', '2025-11-04 21:49:00'),
(1963, 145, 596, 0, 'normal', '2025-11-04 21:49:00'),
(1964, 145, 597, 0, 'normal', '2025-11-04 21:49:00'),
(1965, 145, 598, 0, 'normal', '2025-11-04 21:49:00'),
(1966, 145, 539, 0, 'normal', '2025-11-04 21:49:00'),
(1967, 145, 540, 0, 'normal', '2025-11-04 21:49:00'),
(1968, 145, 541, 0, 'normal', '2025-11-04 21:49:00'),
(1969, 145, 542, 0, 'normal', '2025-11-04 21:49:00'),
(1970, 145, 543, 0, 'normal', '2025-11-04 21:49:00'),
(1971, 145, 544, 0, 'normal', '2025-11-04 21:49:00'),
(1972, 145, 545, 0, 'normal', '2025-11-04 21:49:00'),
(1973, 145, 546, 0, 'normal', '2025-11-04 21:49:00'),
(1974, 145, 547, 0, 'normal', '2025-11-04 21:49:00'),
(1975, 145, 548, 0, 'normal', '2025-11-04 21:49:00'),
(1976, 145, 549, 0, 'normal', '2025-11-04 21:49:00'),
(1977, 145, 550, 0, 'normal', '2025-11-04 21:49:00'),
(1978, 145, 551, 0, 'normal', '2025-11-04 21:49:00'),
(1979, 145, 552, 0, 'normal', '2025-11-04 21:49:00'),
(1980, 145, 553, 0, 'normal', '2025-11-04 21:49:00'),
(1981, 145, 554, 0, 'normal', '2025-11-04 21:49:00'),
(1982, 145, 555, 0, 'normal', '2025-11-04 21:49:00'),
(1983, 145, 556, 0, 'normal', '2025-11-04 21:49:00'),
(1984, 145, 557, 0, 'normal', '2025-11-04 21:49:00'),
(1985, 145, 558, 0, 'normal', '2025-11-04 21:49:00'),
(1986, 145, 559, 0, 'normal', '2025-11-04 21:49:00'),
(1987, 145, 560, 0, 'normal', '2025-11-04 21:49:00'),
(1988, 131, 489, 0, 'normal', '2025-11-04 21:56:55'),
(1989, 131, 490, 0, 'normal', '2025-11-04 21:56:55'),
(1990, 131, 491, 0, 'normal', '2025-11-04 21:56:55'),
(1991, 131, 492, 0, 'normal', '2025-11-04 21:56:55'),
(1992, 131, 493, 0, 'normal', '2025-11-04 21:56:55'),
(1993, 131, 494, 0, 'normal', '2025-11-04 21:56:55'),
(1994, 131, 495, 0, 'normal', '2025-11-04 21:56:55'),
(1995, 131, 496, 0, 'normal', '2025-11-04 21:56:55'),
(1996, 131, 497, 0, 'normal', '2025-11-04 21:56:55'),
(1997, 131, 498, 0, 'normal', '2025-11-04 21:56:55'),
(1998, 131, 499, 0, 'normal', '2025-11-04 21:56:55'),
(1999, 131, 500, 0, 'normal', '2025-11-04 21:56:55'),
(2000, 131, 501, 0, 'normal', '2025-11-04 21:56:55'),
(2001, 131, 502, 0, 'normal', '2025-11-04 21:56:55'),
(2002, 131, 503, 0, 'normal', '2025-11-04 21:56:55'),
(2003, 131, 504, 0, 'normal', '2025-11-04 21:56:55'),
(2004, 131, 505, 0, 'normal', '2025-11-04 21:56:55'),
(2005, 131, 506, 0, 'normal', '2025-11-04 21:56:55'),
(2006, 131, 507, 0, 'normal', '2025-11-04 21:56:55'),
(2007, 131, 508, 0, 'normal', '2025-11-04 21:56:55'),
(2008, 131, 509, 0, 'normal', '2025-11-04 21:56:55'),
(2009, 131, 510, 0, 'normal', '2025-11-04 21:56:55'),
(2010, 131, 511, 0, 'normal', '2025-11-04 21:56:55'),
(2011, 131, 512, 0, 'normal', '2025-11-04 21:56:55'),
(2012, 131, 561, 0, 'normal', '2025-11-04 21:56:55'),
(2013, 131, 562, 0, 'normal', '2025-11-04 21:56:55'),
(2014, 131, 563, 0, 'normal', '2025-11-04 21:56:55'),
(2015, 131, 564, 0, 'normal', '2025-11-04 21:56:55'),
(2016, 131, 565, 0, 'normal', '2025-11-04 21:56:55'),
(2017, 131, 566, 0, 'normal', '2025-11-04 21:56:55'),
(2018, 131, 567, 0, 'normal', '2025-11-04 21:56:55'),
(2019, 131, 568, 0, 'normal', '2025-11-04 21:56:55'),
(2020, 131, 569, 0, 'normal', '2025-11-04 21:56:55'),
(2021, 131, 570, 0, 'normal', '2025-11-04 21:56:55'),
(2022, 131, 571, 0, 'normal', '2025-11-04 21:56:55'),
(2023, 131, 572, 0, 'normal', '2025-11-04 21:56:55'),
(2024, 131, 573, 0, 'normal', '2025-11-04 21:56:55'),
(2025, 131, 574, 0, 'normal', '2025-11-04 21:56:55'),
(2026, 131, 575, 0, 'normal', '2025-11-04 21:56:55'),
(2027, 131, 576, 0, 'normal', '2025-11-04 21:56:55'),
(2028, 131, 577, 0, 'normal', '2025-11-04 21:56:55'),
(2029, 131, 578, 0, 'normal', '2025-11-04 21:56:55'),
(2030, 133, 489, 0, 'normal', '2025-11-04 21:57:31'),
(2031, 133, 490, 0, 'normal', '2025-11-04 21:57:31'),
(2032, 133, 491, 0, 'normal', '2025-11-04 21:57:31'),
(2033, 133, 492, 0, 'normal', '2025-11-04 21:57:31'),
(2034, 133, 493, 0, 'normal', '2025-11-04 21:57:31'),
(2035, 133, 494, 0, 'normal', '2025-11-04 21:57:31'),
(2036, 133, 495, 0, 'normal', '2025-11-04 21:57:31'),
(2037, 133, 496, 0, 'normal', '2025-11-04 21:57:31'),
(2038, 133, 497, 0, 'normal', '2025-11-04 21:57:31'),
(2039, 133, 498, 0, 'normal', '2025-11-04 21:57:31'),
(2040, 133, 499, 0, 'normal', '2025-11-04 21:57:31'),
(2041, 133, 500, 0, 'normal', '2025-11-04 21:57:31'),
(2042, 133, 501, 0, 'normal', '2025-11-04 21:57:31'),
(2043, 133, 502, 0, 'normal', '2025-11-04 21:57:31'),
(2044, 133, 503, 0, 'normal', '2025-11-04 21:57:31'),
(2045, 133, 504, 0, 'normal', '2025-11-04 21:57:31'),
(2046, 133, 505, 0, 'normal', '2025-11-04 21:57:31'),
(2047, 133, 506, 0, 'normal', '2025-11-04 21:57:31'),
(2048, 133, 507, 0, 'normal', '2025-11-04 21:57:31'),
(2049, 133, 508, 0, 'normal', '2025-11-04 21:57:31'),
(2050, 133, 509, 0, 'normal', '2025-11-04 21:57:31'),
(2051, 133, 510, 0, 'normal', '2025-11-04 21:57:31'),
(2052, 133, 511, 0, 'normal', '2025-11-04 21:57:31'),
(2053, 133, 512, 0, 'normal', '2025-11-04 21:57:31'),
(2054, 133, 446, 0, 'normal', '2025-11-04 21:57:31'),
(2055, 133, 447, 0, 'normal', '2025-11-04 21:57:31'),
(2056, 133, 448, 0, 'normal', '2025-11-04 21:57:31'),
(2057, 133, 449, 0, 'normal', '2025-11-04 21:57:31'),
(2058, 133, 450, 0, 'normal', '2025-11-04 21:57:31'),
(2059, 133, 451, 0, 'normal', '2025-11-04 21:57:31'),
(2060, 133, 452, 0, 'normal', '2025-11-04 21:57:31'),
(2061, 133, 453, 0, 'normal', '2025-11-04 21:57:31'),
(2062, 133, 454, 0, 'normal', '2025-11-04 21:57:31'),
(2063, 133, 455, 0, 'normal', '2025-11-04 21:57:31'),
(2064, 133, 456, 0, 'normal', '2025-11-04 21:57:31'),
(2065, 133, 457, 0, 'normal', '2025-11-04 21:57:31'),
(2066, 133, 458, 0, 'normal', '2025-11-04 21:57:31'),
(2067, 133, 459, 0, 'normal', '2025-11-04 21:57:31'),
(2068, 133, 460, 0, 'normal', '2025-11-04 21:57:31'),
(2069, 133, 461, 0, 'normal', '2025-11-04 21:57:31'),
(2070, 133, 462, 0, 'normal', '2025-11-04 21:57:31'),
(2071, 133, 463, 0, 'normal', '2025-11-04 21:57:31'),
(2072, 133, 464, 0, 'normal', '2025-11-04 21:57:31'),
(2073, 133, 465, 0, 'normal', '2025-11-04 21:57:31'),
(2074, 133, 466, 0, 'normal', '2025-11-04 21:57:31'),
(2075, 133, 467, 0, 'normal', '2025-11-04 21:57:31'),
(2076, 133, 468, 0, 'normal', '2025-11-04 21:57:31'),
(2077, 130, 469, 0, 'normal', '2025-11-04 22:05:55'),
(2078, 130, 470, 0, 'normal', '2025-11-04 22:05:55'),
(2079, 130, 471, 0, 'normal', '2025-11-04 22:05:55'),
(2080, 130, 472, 0, 'normal', '2025-11-04 22:05:55'),
(2081, 130, 473, 0, 'normal', '2025-11-04 22:05:55'),
(2082, 130, 474, 0, 'normal', '2025-11-04 22:05:55'),
(2083, 130, 475, 0, 'normal', '2025-11-04 22:05:55'),
(2084, 130, 476, 0, 'normal', '2025-11-04 22:05:55'),
(2085, 130, 477, 0, 'normal', '2025-11-04 22:05:55'),
(2086, 130, 478, 0, 'normal', '2025-11-04 22:05:55'),
(2087, 130, 479, 0, 'normal', '2025-11-04 22:05:55'),
(2088, 130, 480, 0, 'normal', '2025-11-04 22:05:55'),
(2089, 130, 481, 0, 'normal', '2025-11-04 22:05:55'),
(2090, 130, 482, 0, 'normal', '2025-11-04 22:05:55'),
(2091, 130, 483, 0, 'normal', '2025-11-04 22:05:55'),
(2092, 130, 484, 0, 'normal', '2025-11-04 22:05:55'),
(2093, 130, 485, 0, 'normal', '2025-11-04 22:05:55'),
(2094, 130, 486, 0, 'normal', '2025-11-04 22:05:55'),
(2095, 130, 487, 0, 'normal', '2025-11-04 22:05:55'),
(2096, 130, 488, 0, 'normal', '2025-11-04 22:05:55'),
(2097, 130, 597, 0, 'normal', '2025-11-04 22:05:55'),
(2098, 130, 579, 0, 'normal', '2025-11-04 22:05:55'),
(2099, 130, 580, 0, 'normal', '2025-11-04 22:05:55'),
(2100, 130, 581, 0, 'normal', '2025-11-04 22:05:55'),
(2101, 130, 582, 0, 'normal', '2025-11-04 22:05:55'),
(2102, 130, 583, 0, 'normal', '2025-11-04 22:05:55'),
(2103, 130, 584, 0, 'normal', '2025-11-04 22:05:55'),
(2104, 130, 585, 0, 'normal', '2025-11-04 22:05:55'),
(2105, 130, 586, 0, 'normal', '2025-11-04 22:05:55'),
(2106, 130, 587, 0, 'normal', '2025-11-04 22:05:55'),
(2107, 130, 588, 0, 'normal', '2025-11-04 22:05:55'),
(2108, 130, 589, 0, 'normal', '2025-11-04 22:05:55'),
(2109, 130, 590, 0, 'normal', '2025-11-04 22:05:55'),
(2110, 130, 591, 0, 'normal', '2025-11-04 22:05:55'),
(2111, 130, 592, 0, 'normal', '2025-11-04 22:05:55'),
(2112, 130, 593, 0, 'normal', '2025-11-04 22:05:55'),
(2113, 130, 594, 0, 'normal', '2025-11-04 22:05:55'),
(2114, 130, 595, 0, 'normal', '2025-11-04 22:05:55'),
(2115, 130, 596, 0, 'normal', '2025-11-04 22:05:55'),
(2116, 130, 598, 0, 'normal', '2025-11-04 22:05:55'),
(2117, 132, 513, 0, 'normal', '2025-11-04 22:06:18'),
(2118, 132, 514, 0, 'normal', '2025-11-04 22:06:18'),
(2119, 132, 515, 0, 'normal', '2025-11-04 22:06:18'),
(2120, 132, 516, 0, 'normal', '2025-11-04 22:06:18'),
(2121, 132, 517, 0, 'normal', '2025-11-04 22:06:18'),
(2122, 132, 518, 0, 'normal', '2025-11-04 22:06:18'),
(2123, 132, 519, 0, 'normal', '2025-11-04 22:06:18'),
(2124, 132, 520, 0, 'normal', '2025-11-04 22:06:18'),
(2125, 132, 521, 0, 'normal', '2025-11-04 22:06:18'),
(2126, 132, 522, 0, 'normal', '2025-11-04 22:06:18'),
(2127, 132, 523, 0, 'normal', '2025-11-04 22:06:18'),
(2128, 132, 524, 0, 'normal', '2025-11-04 22:06:18'),
(2129, 132, 525, 0, 'normal', '2025-11-04 22:06:18'),
(2130, 132, 526, 0, 'normal', '2025-11-04 22:06:18'),
(2131, 132, 527, 0, 'normal', '2025-11-04 22:06:18'),
(2132, 132, 528, 0, 'normal', '2025-11-04 22:06:18'),
(2133, 132, 529, 0, 'normal', '2025-11-04 22:06:18'),
(2134, 132, 530, 0, 'normal', '2025-11-04 22:06:18'),
(2135, 132, 531, 0, 'normal', '2025-11-04 22:06:18'),
(2136, 132, 532, 0, 'normal', '2025-11-04 22:06:18'),
(2137, 132, 533, 0, 'normal', '2025-11-04 22:06:18'),
(2138, 132, 534, 0, 'normal', '2025-11-04 22:06:18'),
(2139, 132, 535, 0, 'normal', '2025-11-04 22:06:18'),
(2140, 132, 536, 0, 'normal', '2025-11-04 22:06:18'),
(2141, 132, 537, 0, 'normal', '2025-11-04 22:06:18'),
(2142, 132, 538, 0, 'normal', '2025-11-04 22:06:18'),
(2143, 132, 539, 0, 'normal', '2025-11-04 22:06:18'),
(2144, 132, 540, 0, 'normal', '2025-11-04 22:06:18'),
(2145, 132, 541, 0, 'normal', '2025-11-04 22:06:18'),
(2146, 132, 542, 0, 'normal', '2025-11-04 22:06:18'),
(2147, 132, 543, 0, 'normal', '2025-11-04 22:06:18'),
(2148, 132, 544, 0, 'normal', '2025-11-04 22:06:18'),
(2149, 132, 545, 0, 'normal', '2025-11-04 22:06:18'),
(2150, 132, 546, 0, 'normal', '2025-11-04 22:06:18'),
(2151, 132, 547, 0, 'normal', '2025-11-04 22:06:18'),
(2152, 132, 548, 0, 'normal', '2025-11-04 22:06:18'),
(2153, 132, 549, 0, 'normal', '2025-11-04 22:06:18'),
(2154, 132, 550, 0, 'normal', '2025-11-04 22:06:18'),
(2155, 132, 551, 0, 'normal', '2025-11-04 22:06:18'),
(2156, 132, 552, 0, 'normal', '2025-11-04 22:06:18'),
(2157, 132, 553, 0, 'normal', '2025-11-04 22:06:18'),
(2158, 132, 554, 0, 'normal', '2025-11-04 22:06:18'),
(2159, 132, 555, 0, 'normal', '2025-11-04 22:06:18'),
(2160, 132, 556, 0, 'normal', '2025-11-04 22:06:18'),
(2161, 132, 557, 0, 'normal', '2025-11-04 22:06:18'),
(2162, 132, 558, 0, 'normal', '2025-11-04 22:06:18'),
(2163, 132, 559, 0, 'normal', '2025-11-04 22:06:18'),
(2164, 132, 560, 0, 'normal', '2025-11-04 22:06:18'),
(2165, 135, 539, 0, 'normal', '2025-11-04 22:06:32'),
(2166, 135, 540, 0, 'normal', '2025-11-04 22:06:32'),
(2167, 135, 541, 0, 'normal', '2025-11-04 22:06:32'),
(2168, 135, 542, 0, 'normal', '2025-11-04 22:06:32'),
(2169, 135, 543, 0, 'normal', '2025-11-04 22:06:32'),
(2170, 135, 544, 0, 'normal', '2025-11-04 22:06:32'),
(2171, 135, 545, 0, 'normal', '2025-11-04 22:06:32'),
(2172, 135, 546, 0, 'normal', '2025-11-04 22:06:32'),
(2173, 135, 547, 0, 'normal', '2025-11-04 22:06:32'),
(2174, 135, 548, 0, 'normal', '2025-11-04 22:06:32'),
(2175, 135, 549, 0, 'normal', '2025-11-04 22:06:32'),
(2176, 135, 550, 0, 'normal', '2025-11-04 22:06:32'),
(2177, 135, 551, 0, 'normal', '2025-11-04 22:06:32'),
(2178, 135, 552, 0, 'normal', '2025-11-04 22:06:32'),
(2179, 135, 553, 0, 'normal', '2025-11-04 22:06:32'),
(2180, 135, 554, 0, 'normal', '2025-11-04 22:06:32'),
(2181, 135, 555, 0, 'normal', '2025-11-04 22:06:32'),
(2182, 135, 556, 0, 'normal', '2025-11-04 22:06:32'),
(2183, 135, 557, 0, 'normal', '2025-11-04 22:06:32'),
(2184, 135, 558, 0, 'normal', '2025-11-04 22:06:32'),
(2185, 135, 559, 0, 'normal', '2025-11-04 22:06:32'),
(2186, 135, 560, 0, 'normal', '2025-11-04 22:06:32'),
(2187, 135, 561, 0, 'normal', '2025-11-04 22:06:32'),
(2188, 135, 562, 0, 'normal', '2025-11-04 22:06:32'),
(2189, 135, 563, 0, 'normal', '2025-11-04 22:06:32'),
(2190, 135, 564, 0, 'normal', '2025-11-04 22:06:32'),
(2191, 135, 565, 0, 'normal', '2025-11-04 22:06:32'),
(2192, 135, 566, 0, 'normal', '2025-11-04 22:06:32'),
(2193, 135, 567, 0, 'normal', '2025-11-04 22:06:32'),
(2194, 135, 568, 0, 'normal', '2025-11-04 22:06:32'),
(2195, 135, 569, 0, 'normal', '2025-11-04 22:06:32'),
(2196, 135, 570, 0, 'normal', '2025-11-04 22:06:32'),
(2197, 135, 571, 0, 'normal', '2025-11-04 22:06:32'),
(2198, 135, 572, 0, 'normal', '2025-11-04 22:06:32'),
(2199, 135, 573, 0, 'normal', '2025-11-04 22:06:32'),
(2200, 135, 574, 0, 'normal', '2025-11-04 22:06:32'),
(2201, 135, 575, 0, 'normal', '2025-11-04 22:06:32'),
(2202, 135, 576, 0, 'normal', '2025-11-04 22:06:32'),
(2203, 135, 577, 0, 'normal', '2025-11-04 22:06:32'),
(2204, 135, 578, 0, 'normal', '2025-11-04 22:06:32'),
(2205, 136, 513, 0, 'normal', '2025-11-04 22:06:45'),
(2206, 136, 514, 0, 'normal', '2025-11-04 22:06:45'),
(2207, 136, 515, 0, 'normal', '2025-11-04 22:06:45'),
(2208, 136, 516, 0, 'normal', '2025-11-04 22:06:45'),
(2209, 136, 517, 0, 'normal', '2025-11-04 22:06:45'),
(2210, 136, 518, 0, 'normal', '2025-11-04 22:06:45'),
(2211, 136, 519, 0, 'normal', '2025-11-04 22:06:45'),
(2212, 136, 520, 0, 'normal', '2025-11-04 22:06:45'),
(2213, 136, 521, 0, 'normal', '2025-11-04 22:06:45'),
(2214, 136, 522, 0, 'normal', '2025-11-04 22:06:45'),
(2215, 136, 523, 0, 'normal', '2025-11-04 22:06:45'),
(2216, 136, 524, 0, 'normal', '2025-11-04 22:06:45'),
(2217, 136, 525, 0, 'normal', '2025-11-04 22:06:45'),
(2218, 136, 526, 0, 'normal', '2025-11-04 22:06:45'),
(2219, 136, 527, 0, 'normal', '2025-11-04 22:06:45'),
(2220, 136, 528, 0, 'normal', '2025-11-04 22:06:45'),
(2221, 136, 529, 0, 'normal', '2025-11-04 22:06:45'),
(2222, 136, 530, 0, 'normal', '2025-11-04 22:06:45'),
(2223, 136, 531, 0, 'normal', '2025-11-04 22:06:45'),
(2224, 136, 532, 0, 'normal', '2025-11-04 22:06:45'),
(2225, 136, 533, 0, 'normal', '2025-11-04 22:06:45'),
(2226, 136, 534, 0, 'normal', '2025-11-04 22:06:45'),
(2227, 136, 535, 0, 'normal', '2025-11-04 22:06:45'),
(2228, 136, 536, 0, 'normal', '2025-11-04 22:06:45'),
(2229, 136, 537, 0, 'normal', '2025-11-04 22:06:45'),
(2230, 136, 538, 0, 'normal', '2025-11-04 22:06:45'),
(2231, 136, 469, 0, 'normal', '2025-11-04 22:06:45'),
(2232, 136, 470, 0, 'normal', '2025-11-04 22:06:45'),
(2233, 136, 471, 0, 'normal', '2025-11-04 22:06:45'),
(2234, 136, 472, 0, 'normal', '2025-11-04 22:06:45'),
(2235, 136, 473, 0, 'normal', '2025-11-04 22:06:45'),
(2236, 136, 474, 0, 'normal', '2025-11-04 22:06:45'),
(2237, 136, 475, 0, 'normal', '2025-11-04 22:06:45'),
(2238, 136, 476, 0, 'normal', '2025-11-04 22:06:45'),
(2239, 136, 477, 0, 'normal', '2025-11-04 22:06:45'),
(2240, 136, 478, 0, 'normal', '2025-11-04 22:06:45'),
(2241, 136, 479, 0, 'normal', '2025-11-04 22:06:45'),
(2242, 136, 480, 0, 'normal', '2025-11-04 22:06:45'),
(2243, 136, 481, 0, 'normal', '2025-11-04 22:06:45'),
(2244, 136, 482, 0, 'normal', '2025-11-04 22:06:45'),
(2245, 136, 483, 0, 'normal', '2025-11-04 22:06:45'),
(2246, 136, 484, 0, 'normal', '2025-11-04 22:06:45'),
(2247, 136, 485, 0, 'normal', '2025-11-04 22:06:45'),
(2248, 136, 486, 0, 'normal', '2025-11-04 22:06:45'),
(2249, 136, 487, 0, 'normal', '2025-11-04 22:06:45'),
(2250, 136, 488, 0, 'normal', '2025-11-04 22:06:45'),
(2251, 136, 597, 0, 'normal', '2025-11-04 22:06:45'),
(2252, 137, 539, 0, 'normal', '2025-11-04 22:06:54'),
(2253, 137, 540, 0, 'normal', '2025-11-04 22:06:54'),
(2254, 137, 541, 0, 'normal', '2025-11-04 22:06:54'),
(2255, 137, 542, 0, 'normal', '2025-11-04 22:06:54'),
(2256, 137, 543, 0, 'normal', '2025-11-04 22:06:54'),
(2257, 137, 544, 0, 'normal', '2025-11-04 22:06:54'),
(2258, 137, 545, 0, 'normal', '2025-11-04 22:06:54'),
(2259, 137, 546, 0, 'normal', '2025-11-04 22:06:54'),
(2260, 137, 547, 0, 'normal', '2025-11-04 22:06:54'),
(2261, 137, 548, 0, 'normal', '2025-11-04 22:06:54'),
(2262, 137, 549, 0, 'normal', '2025-11-04 22:06:54'),
(2263, 137, 550, 0, 'normal', '2025-11-04 22:06:54'),
(2264, 137, 551, 0, 'normal', '2025-11-04 22:06:54'),
(2265, 137, 552, 0, 'normal', '2025-11-04 22:06:54'),
(2266, 137, 553, 0, 'normal', '2025-11-04 22:06:54'),
(2267, 137, 554, 0, 'normal', '2025-11-04 22:06:54'),
(2268, 137, 555, 0, 'normal', '2025-11-04 22:06:54'),
(2269, 137, 556, 0, 'normal', '2025-11-04 22:06:54'),
(2270, 137, 557, 0, 'normal', '2025-11-04 22:06:54'),
(2271, 137, 558, 0, 'normal', '2025-11-04 22:06:54'),
(2272, 137, 559, 0, 'normal', '2025-11-04 22:06:54'),
(2273, 137, 560, 0, 'normal', '2025-11-04 22:06:54'),
(2274, 137, 446, 0, 'normal', '2025-11-04 22:06:54'),
(2275, 137, 447, 0, 'normal', '2025-11-04 22:06:54'),
(2276, 137, 448, 0, 'normal', '2025-11-04 22:06:54'),
(2277, 137, 449, 0, 'normal', '2025-11-04 22:06:54'),
(2278, 137, 450, 0, 'normal', '2025-11-04 22:06:54'),
(2279, 137, 451, 0, 'normal', '2025-11-04 22:06:54'),
(2280, 137, 452, 0, 'normal', '2025-11-04 22:06:54'),
(2281, 137, 453, 0, 'normal', '2025-11-04 22:06:54'),
(2282, 137, 454, 0, 'normal', '2025-11-04 22:06:54'),
(2283, 137, 455, 0, 'normal', '2025-11-04 22:06:54'),
(2284, 137, 456, 0, 'normal', '2025-11-04 22:06:54'),
(2285, 137, 457, 0, 'normal', '2025-11-04 22:06:54'),
(2286, 137, 458, 0, 'normal', '2025-11-04 22:06:54'),
(2287, 137, 459, 0, 'normal', '2025-11-04 22:06:54'),
(2288, 137, 460, 0, 'normal', '2025-11-04 22:06:54'),
(2289, 137, 461, 0, 'normal', '2025-11-04 22:06:54'),
(2290, 137, 462, 0, 'normal', '2025-11-04 22:06:54'),
(2291, 137, 463, 0, 'normal', '2025-11-04 22:06:54'),
(2292, 137, 464, 0, 'normal', '2025-11-04 22:06:54'),
(2293, 137, 465, 0, 'normal', '2025-11-04 22:06:54'),
(2294, 137, 466, 0, 'normal', '2025-11-04 22:06:54'),
(2295, 137, 467, 0, 'normal', '2025-11-04 22:06:54'),
(2296, 137, 468, 0, 'normal', '2025-11-04 22:06:54'),
(2297, 139, 539, 0, 'normal', '2025-11-04 22:07:05'),
(2298, 139, 540, 0, 'normal', '2025-11-04 22:07:05'),
(2299, 139, 541, 0, 'normal', '2025-11-04 22:07:05'),
(2300, 139, 542, 0, 'normal', '2025-11-04 22:07:05'),
(2301, 139, 543, 0, 'normal', '2025-11-04 22:07:05'),
(2302, 139, 544, 0, 'normal', '2025-11-04 22:07:05'),
(2303, 139, 545, 0, 'normal', '2025-11-04 22:07:05'),
(2304, 139, 546, 0, 'normal', '2025-11-04 22:07:05'),
(2305, 139, 547, 0, 'normal', '2025-11-04 22:07:05'),
(2306, 139, 548, 0, 'normal', '2025-11-04 22:07:05'),
(2307, 139, 549, 0, 'normal', '2025-11-04 22:07:05'),
(2308, 139, 550, 0, 'normal', '2025-11-04 22:07:05'),
(2309, 139, 551, 0, 'normal', '2025-11-04 22:07:05'),
(2310, 139, 552, 0, 'normal', '2025-11-04 22:07:05'),
(2311, 139, 553, 0, 'normal', '2025-11-04 22:07:05'),
(2312, 139, 554, 0, 'normal', '2025-11-04 22:07:05'),
(2313, 139, 555, 0, 'normal', '2025-11-04 22:07:05'),
(2314, 139, 556, 0, 'normal', '2025-11-04 22:07:05'),
(2315, 139, 557, 0, 'normal', '2025-11-04 22:07:05'),
(2316, 139, 558, 0, 'normal', '2025-11-04 22:07:05'),
(2317, 139, 559, 0, 'normal', '2025-11-04 22:07:05'),
(2318, 139, 560, 0, 'normal', '2025-11-04 22:07:05'),
(2319, 139, 489, 0, 'normal', '2025-11-04 22:07:05'),
(2320, 139, 490, 0, 'normal', '2025-11-04 22:07:05'),
(2321, 139, 491, 0, 'normal', '2025-11-04 22:07:05'),
(2322, 139, 492, 0, 'normal', '2025-11-04 22:07:05'),
(2323, 139, 493, 0, 'normal', '2025-11-04 22:07:05'),
(2324, 139, 494, 0, 'normal', '2025-11-04 22:07:05'),
(2325, 139, 495, 0, 'normal', '2025-11-04 22:07:05'),
(2326, 139, 496, 0, 'normal', '2025-11-04 22:07:05'),
(2327, 139, 497, 0, 'normal', '2025-11-04 22:07:05'),
(2328, 139, 498, 0, 'normal', '2025-11-04 22:07:05'),
(2329, 139, 499, 0, 'normal', '2025-11-04 22:07:05'),
(2330, 139, 500, 0, 'normal', '2025-11-04 22:07:05'),
(2331, 139, 501, 0, 'normal', '2025-11-04 22:07:05'),
(2332, 139, 502, 0, 'normal', '2025-11-04 22:07:05'),
(2333, 139, 503, 0, 'normal', '2025-11-04 22:07:05'),
(2334, 139, 504, 0, 'normal', '2025-11-04 22:07:05'),
(2335, 139, 505, 0, 'normal', '2025-11-04 22:07:05'),
(2336, 139, 506, 0, 'normal', '2025-11-04 22:07:05'),
(2337, 139, 507, 0, 'normal', '2025-11-04 22:07:05'),
(2338, 139, 508, 0, 'normal', '2025-11-04 22:07:05'),
(2339, 139, 509, 0, 'normal', '2025-11-04 22:07:05'),
(2340, 139, 510, 0, 'normal', '2025-11-04 22:07:05'),
(2341, 139, 511, 0, 'normal', '2025-11-04 22:07:05'),
(2342, 139, 512, 0, 'normal', '2025-11-04 22:07:05'),
(2343, 140, 561, 0, 'normal', '2025-11-04 22:07:14'),
(2344, 140, 562, 0, 'normal', '2025-11-04 22:07:14'),
(2345, 140, 563, 0, 'normal', '2025-11-04 22:07:14'),
(2346, 140, 564, 0, 'normal', '2025-11-04 22:07:14'),
(2347, 140, 565, 0, 'normal', '2025-11-04 22:07:14'),
(2348, 140, 566, 0, 'normal', '2025-11-04 22:07:14'),
(2349, 140, 567, 0, 'normal', '2025-11-04 22:07:14'),
(2350, 140, 568, 0, 'normal', '2025-11-04 22:07:14'),
(2351, 140, 569, 0, 'normal', '2025-11-04 22:07:14'),
(2352, 140, 570, 0, 'normal', '2025-11-04 22:07:14'),
(2353, 140, 571, 0, 'normal', '2025-11-04 22:07:14'),
(2354, 140, 572, 0, 'normal', '2025-11-04 22:07:14'),
(2355, 140, 573, 0, 'normal', '2025-11-04 22:07:14'),
(2356, 140, 574, 0, 'normal', '2025-11-04 22:07:14'),
(2357, 140, 575, 0, 'normal', '2025-11-04 22:07:14'),
(2358, 140, 576, 0, 'normal', '2025-11-04 22:07:14'),
(2359, 140, 577, 0, 'normal', '2025-11-04 22:07:14'),
(2360, 140, 578, 0, 'normal', '2025-11-04 22:07:14'),
(2361, 140, 469, 0, 'normal', '2025-11-04 22:07:14'),
(2362, 140, 470, 0, 'normal', '2025-11-04 22:07:14'),
(2363, 140, 471, 0, 'normal', '2025-11-04 22:07:14'),
(2364, 140, 472, 0, 'normal', '2025-11-04 22:07:14'),
(2365, 140, 473, 0, 'normal', '2025-11-04 22:07:14'),
(2366, 140, 474, 0, 'normal', '2025-11-04 22:07:14'),
(2367, 140, 475, 0, 'normal', '2025-11-04 22:07:14'),
(2368, 140, 476, 0, 'normal', '2025-11-04 22:07:14'),
(2369, 140, 477, 0, 'normal', '2025-11-04 22:07:14'),
(2370, 140, 478, 0, 'normal', '2025-11-04 22:07:14'),
(2371, 140, 479, 0, 'normal', '2025-11-04 22:07:14'),
(2372, 140, 480, 0, 'normal', '2025-11-04 22:07:14'),
(2373, 140, 481, 0, 'normal', '2025-11-04 22:07:14'),
(2374, 140, 482, 0, 'normal', '2025-11-04 22:07:14'),
(2375, 140, 483, 0, 'normal', '2025-11-04 22:07:14'),
(2376, 140, 484, 0, 'normal', '2025-11-04 22:07:14'),
(2377, 140, 485, 0, 'normal', '2025-11-04 22:07:14'),
(2378, 140, 486, 0, 'normal', '2025-11-04 22:07:14'),
(2379, 140, 487, 0, 'normal', '2025-11-04 22:07:14'),
(2380, 140, 488, 0, 'normal', '2025-11-04 22:07:14'),
(2381, 140, 597, 0, 'normal', '2025-11-04 22:07:14'),
(2382, 142, 561, 0, 'normal', '2025-11-04 22:07:28'),
(2383, 142, 562, 0, 'normal', '2025-11-04 22:07:28'),
(2384, 142, 563, 0, 'normal', '2025-11-04 22:07:28'),
(2385, 142, 564, 0, 'normal', '2025-11-04 22:07:28'),
(2386, 142, 565, 0, 'normal', '2025-11-04 22:07:28'),
(2387, 142, 566, 0, 'normal', '2025-11-04 22:07:28'),
(2388, 142, 567, 0, 'normal', '2025-11-04 22:07:28'),
(2389, 142, 568, 0, 'normal', '2025-11-04 22:07:28'),
(2390, 142, 569, 0, 'normal', '2025-11-04 22:07:28'),
(2391, 142, 570, 0, 'normal', '2025-11-04 22:07:28'),
(2392, 142, 571, 0, 'normal', '2025-11-04 22:07:28'),
(2393, 142, 572, 0, 'normal', '2025-11-04 22:07:28'),
(2394, 142, 573, 0, 'normal', '2025-11-04 22:07:28'),
(2395, 142, 574, 0, 'normal', '2025-11-04 22:07:28'),
(2396, 142, 575, 0, 'normal', '2025-11-04 22:07:28'),
(2397, 142, 576, 0, 'normal', '2025-11-04 22:07:28'),
(2398, 142, 577, 0, 'normal', '2025-11-04 22:07:28'),
(2399, 142, 578, 0, 'normal', '2025-11-04 22:07:28'),
(2400, 142, 513, 0, 'normal', '2025-11-04 22:07:28'),
(2401, 142, 514, 0, 'normal', '2025-11-04 22:07:28'),
(2402, 142, 515, 0, 'normal', '2025-11-04 22:07:28'),
(2403, 142, 516, 0, 'normal', '2025-11-04 22:07:28'),
(2404, 142, 517, 0, 'normal', '2025-11-04 22:07:28'),
(2405, 142, 518, 0, 'normal', '2025-11-04 22:07:28'),
(2406, 142, 519, 0, 'normal', '2025-11-04 22:07:28'),
(2407, 142, 520, 0, 'normal', '2025-11-04 22:07:28'),
(2408, 142, 521, 0, 'normal', '2025-11-04 22:07:28'),
(2409, 142, 522, 0, 'normal', '2025-11-04 22:07:28'),
(2410, 142, 523, 0, 'normal', '2025-11-04 22:07:28'),
(2411, 142, 524, 0, 'normal', '2025-11-04 22:07:28'),
(2412, 142, 525, 0, 'normal', '2025-11-04 22:07:28'),
(2413, 142, 526, 0, 'normal', '2025-11-04 22:07:28'),
(2414, 142, 527, 0, 'normal', '2025-11-04 22:07:28'),
(2415, 142, 528, 0, 'normal', '2025-11-04 22:07:28'),
(2416, 142, 529, 0, 'normal', '2025-11-04 22:07:28'),
(2417, 142, 530, 0, 'normal', '2025-11-04 22:07:28'),
(2418, 142, 531, 0, 'normal', '2025-11-04 22:07:28'),
(2419, 142, 532, 0, 'normal', '2025-11-04 22:07:28'),
(2420, 142, 533, 0, 'normal', '2025-11-04 22:07:28'),
(2421, 142, 534, 0, 'normal', '2025-11-04 22:07:28'),
(2422, 142, 535, 0, 'normal', '2025-11-04 22:07:28'),
(2423, 142, 536, 0, 'normal', '2025-11-04 22:07:28'),
(2424, 142, 537, 0, 'normal', '2025-11-04 22:07:28'),
(2425, 142, 538, 0, 'normal', '2025-11-04 22:07:28'),
(2426, 144, 446, 0, 'normal', '2025-11-04 22:07:34'),
(2427, 144, 447, 0, 'normal', '2025-11-04 22:07:34'),
(2428, 144, 448, 0, 'normal', '2025-11-04 22:07:34'),
(2429, 144, 449, 0, 'normal', '2025-11-04 22:07:34'),
(2430, 144, 450, 0, 'normal', '2025-11-04 22:07:34'),
(2431, 144, 451, 0, 'normal', '2025-11-04 22:07:34'),
(2432, 144, 452, 0, 'normal', '2025-11-04 22:07:34'),
(2433, 144, 453, 0, 'normal', '2025-11-04 22:07:34'),
(2434, 144, 454, 0, 'normal', '2025-11-04 22:07:34'),
(2435, 144, 455, 0, 'normal', '2025-11-04 22:07:34'),
(2436, 144, 456, 0, 'normal', '2025-11-04 22:07:34'),
(2437, 144, 457, 0, 'normal', '2025-11-04 22:07:34'),
(2438, 144, 458, 0, 'normal', '2025-11-04 22:07:34'),
(2439, 144, 459, 0, 'normal', '2025-11-04 22:07:34'),
(2440, 144, 460, 0, 'normal', '2025-11-04 22:07:34'),
(2441, 144, 461, 0, 'normal', '2025-11-04 22:07:34'),
(2442, 144, 462, 0, 'normal', '2025-11-04 22:07:34'),
(2443, 144, 463, 0, 'normal', '2025-11-04 22:07:34'),
(2444, 144, 464, 0, 'normal', '2025-11-04 22:07:34'),
(2445, 144, 465, 0, 'normal', '2025-11-04 22:07:34'),
(2446, 144, 466, 0, 'normal', '2025-11-04 22:07:34'),
(2447, 144, 467, 0, 'normal', '2025-11-04 22:07:34'),
(2448, 144, 468, 0, 'normal', '2025-11-04 22:07:34'),
(2449, 144, 469, 0, 'normal', '2025-11-04 22:07:34'),
(2450, 144, 470, 0, 'normal', '2025-11-04 22:07:34'),
(2451, 144, 471, 0, 'normal', '2025-11-04 22:07:34'),
(2452, 144, 472, 0, 'normal', '2025-11-04 22:07:34'),
(2453, 144, 473, 0, 'normal', '2025-11-04 22:07:34'),
(2454, 144, 474, 0, 'normal', '2025-11-04 22:07:34'),
(2455, 144, 475, 0, 'normal', '2025-11-04 22:07:34'),
(2456, 144, 476, 0, 'normal', '2025-11-04 22:07:34'),
(2457, 144, 477, 0, 'normal', '2025-11-04 22:07:34'),
(2458, 144, 478, 0, 'normal', '2025-11-04 22:07:34'),
(2459, 144, 479, 0, 'normal', '2025-11-04 22:07:34'),
(2460, 144, 480, 0, 'normal', '2025-11-04 22:07:34'),
(2461, 144, 481, 0, 'normal', '2025-11-04 22:07:34'),
(2462, 144, 482, 0, 'normal', '2025-11-04 22:07:34'),
(2463, 144, 483, 0, 'normal', '2025-11-04 22:07:34'),
(2464, 144, 484, 0, 'normal', '2025-11-04 22:07:34'),
(2465, 144, 485, 0, 'normal', '2025-11-04 22:07:34'),
(2466, 144, 486, 0, 'normal', '2025-11-04 22:07:34'),
(2467, 144, 487, 0, 'normal', '2025-11-04 22:07:34'),
(2468, 144, 488, 0, 'normal', '2025-11-04 22:07:34'),
(2469, 144, 597, 0, 'normal', '2025-11-04 22:07:34'),
(2470, 146, 446, 0, 'normal', '2025-11-04 22:07:48'),
(2471, 146, 447, 0, 'normal', '2025-11-04 22:07:48'),
(2472, 146, 448, 0, 'normal', '2025-11-04 22:07:48'),
(2473, 146, 449, 0, 'normal', '2025-11-04 22:07:48'),
(2474, 146, 450, 0, 'normal', '2025-11-04 22:07:48'),
(2475, 146, 451, 0, 'normal', '2025-11-04 22:07:48'),
(2476, 146, 452, 0, 'normal', '2025-11-04 22:07:48'),
(2477, 146, 453, 0, 'normal', '2025-11-04 22:07:48'),
(2478, 146, 454, 0, 'normal', '2025-11-04 22:07:48'),
(2479, 146, 455, 0, 'normal', '2025-11-04 22:07:48'),
(2480, 146, 456, 0, 'normal', '2025-11-04 22:07:48'),
(2481, 146, 457, 0, 'normal', '2025-11-04 22:07:48'),
(2482, 146, 458, 0, 'normal', '2025-11-04 22:07:48'),
(2483, 146, 459, 0, 'normal', '2025-11-04 22:07:48'),
(2484, 146, 460, 0, 'normal', '2025-11-04 22:07:48'),
(2485, 146, 461, 0, 'normal', '2025-11-04 22:07:48'),
(2486, 146, 462, 0, 'normal', '2025-11-04 22:07:48'),
(2487, 146, 463, 0, 'normal', '2025-11-04 22:07:48'),
(2488, 146, 464, 0, 'normal', '2025-11-04 22:07:48'),
(2489, 146, 465, 0, 'normal', '2025-11-04 22:07:48'),
(2490, 146, 466, 0, 'normal', '2025-11-04 22:07:48'),
(2491, 146, 467, 0, 'normal', '2025-11-04 22:07:48'),
(2492, 146, 468, 0, 'normal', '2025-11-04 22:07:48'),
(2493, 146, 513, 0, 'normal', '2025-11-04 22:07:48'),
(2494, 146, 514, 0, 'normal', '2025-11-04 22:07:48'),
(2495, 146, 515, 0, 'normal', '2025-11-04 22:07:48'),
(2496, 146, 516, 0, 'normal', '2025-11-04 22:07:48'),
(2497, 146, 517, 0, 'normal', '2025-11-04 22:07:48'),
(2498, 146, 518, 0, 'normal', '2025-11-04 22:07:48'),
(2499, 146, 519, 0, 'normal', '2025-11-04 22:07:48'),
(2500, 146, 520, 0, 'normal', '2025-11-04 22:07:48'),
(2501, 146, 521, 0, 'normal', '2025-11-04 22:07:48'),
(2502, 146, 522, 0, 'normal', '2025-11-04 22:07:48'),
(2503, 146, 523, 0, 'normal', '2025-11-04 22:07:48'),
(2504, 146, 524, 0, 'normal', '2025-11-04 22:07:48'),
(2505, 146, 525, 0, 'normal', '2025-11-04 22:07:48'),
(2506, 146, 526, 0, 'normal', '2025-11-04 22:07:48'),
(2507, 146, 527, 0, 'normal', '2025-11-04 22:07:48'),
(2508, 146, 528, 0, 'normal', '2025-11-04 22:07:48'),
(2509, 146, 529, 0, 'normal', '2025-11-04 22:07:48'),
(2510, 146, 530, 0, 'normal', '2025-11-04 22:07:48'),
(2511, 146, 531, 0, 'normal', '2025-11-04 22:07:48'),
(2512, 146, 532, 0, 'normal', '2025-11-04 22:07:48'),
(2513, 146, 533, 0, 'normal', '2025-11-04 22:07:48'),
(2514, 146, 534, 0, 'normal', '2025-11-04 22:07:48'),
(2515, 146, 535, 0, 'normal', '2025-11-04 22:07:48'),
(2516, 146, 536, 0, 'normal', '2025-11-04 22:07:48'),
(2517, 146, 537, 0, 'normal', '2025-11-04 22:07:48'),
(2518, 146, 538, 0, 'normal', '2025-11-04 22:07:48'),
(2519, 147, 469, 0, 'normal', '2025-11-04 22:07:54'),
(2520, 147, 470, 0, 'normal', '2025-11-04 22:07:54'),
(2521, 147, 471, 0, 'normal', '2025-11-04 22:07:54'),
(2522, 147, 472, 0, 'normal', '2025-11-04 22:07:54'),
(2523, 147, 473, 0, 'normal', '2025-11-04 22:07:54'),
(2524, 147, 474, 0, 'normal', '2025-11-04 22:07:54'),
(2525, 147, 475, 0, 'normal', '2025-11-04 22:07:54'),
(2526, 147, 476, 0, 'normal', '2025-11-04 22:07:54'),
(2527, 147, 477, 0, 'normal', '2025-11-04 22:07:54'),
(2528, 147, 478, 0, 'normal', '2025-11-04 22:07:54'),
(2529, 147, 479, 0, 'normal', '2025-11-04 22:07:54'),
(2530, 147, 480, 0, 'normal', '2025-11-04 22:07:54'),
(2531, 147, 481, 0, 'normal', '2025-11-04 22:07:54'),
(2532, 147, 482, 0, 'normal', '2025-11-04 22:07:54'),
(2533, 147, 483, 0, 'normal', '2025-11-04 22:07:54'),
(2534, 147, 484, 0, 'normal', '2025-11-04 22:07:54'),
(2535, 147, 485, 0, 'normal', '2025-11-04 22:07:54'),
(2536, 147, 486, 0, 'normal', '2025-11-04 22:07:54'),
(2537, 147, 487, 0, 'normal', '2025-11-04 22:07:54'),
(2538, 147, 488, 0, 'normal', '2025-11-04 22:07:54'),
(2539, 147, 597, 0, 'normal', '2025-11-04 22:07:54'),
(2540, 147, 489, 0, 'normal', '2025-11-04 22:07:54'),
(2541, 147, 490, 0, 'normal', '2025-11-04 22:07:54'),
(2542, 147, 491, 0, 'normal', '2025-11-04 22:07:54'),
(2543, 147, 492, 0, 'normal', '2025-11-04 22:07:54'),
(2544, 147, 493, 0, 'normal', '2025-11-04 22:07:54'),
(2545, 147, 494, 0, 'normal', '2025-11-04 22:07:54'),
(2546, 147, 495, 0, 'normal', '2025-11-04 22:07:54'),
(2547, 147, 496, 0, 'normal', '2025-11-04 22:07:54'),
(2548, 147, 497, 0, 'normal', '2025-11-04 22:07:54'),
(2549, 147, 498, 0, 'normal', '2025-11-04 22:07:54'),
(2550, 147, 499, 0, 'normal', '2025-11-04 22:07:54'),
(2551, 147, 500, 0, 'normal', '2025-11-04 22:07:54'),
(2552, 147, 501, 0, 'normal', '2025-11-04 22:07:54'),
(2553, 147, 502, 0, 'normal', '2025-11-04 22:07:54'),
(2554, 147, 503, 0, 'normal', '2025-11-04 22:07:54'),
(2555, 147, 504, 0, 'normal', '2025-11-04 22:07:54'),
(2556, 147, 505, 0, 'normal', '2025-11-04 22:07:54'),
(2557, 147, 506, 0, 'normal', '2025-11-04 22:07:54'),
(2558, 147, 507, 0, 'normal', '2025-11-04 22:07:54'),
(2559, 147, 508, 0, 'normal', '2025-11-04 22:07:54'),
(2560, 147, 509, 0, 'normal', '2025-11-04 22:07:54'),
(2561, 147, 510, 0, 'normal', '2025-11-04 22:07:54'),
(2562, 147, 511, 0, 'normal', '2025-11-04 22:07:54'),
(2563, 147, 512, 0, 'normal', '2025-11-04 22:07:54'),
(2564, 148, 446, 0, 'normal', '2025-11-04 22:08:07'),
(2565, 148, 447, 0, 'normal', '2025-11-04 22:08:07'),
(2566, 148, 448, 0, 'normal', '2025-11-04 22:08:07'),
(2567, 148, 449, 0, 'normal', '2025-11-04 22:08:07'),
(2568, 148, 450, 0, 'normal', '2025-11-04 22:08:07'),
(2569, 148, 451, 0, 'normal', '2025-11-04 22:08:07'),
(2570, 148, 452, 0, 'normal', '2025-11-04 22:08:07'),
(2571, 148, 453, 0, 'normal', '2025-11-04 22:08:07'),
(2572, 148, 454, 0, 'normal', '2025-11-04 22:08:07'),
(2573, 148, 455, 0, 'normal', '2025-11-04 22:08:07'),
(2574, 148, 456, 0, 'normal', '2025-11-04 22:08:07'),
(2575, 148, 457, 0, 'normal', '2025-11-04 22:08:07'),
(2576, 148, 458, 0, 'normal', '2025-11-04 22:08:07'),
(2577, 148, 459, 0, 'normal', '2025-11-04 22:08:07'),
(2578, 148, 460, 0, 'normal', '2025-11-04 22:08:07'),
(2579, 148, 461, 0, 'normal', '2025-11-04 22:08:07'),
(2580, 148, 462, 0, 'normal', '2025-11-04 22:08:07'),
(2581, 148, 463, 0, 'normal', '2025-11-04 22:08:07'),
(2582, 148, 464, 0, 'normal', '2025-11-04 22:08:07'),
(2583, 148, 465, 0, 'normal', '2025-11-04 22:08:07'),
(2584, 148, 466, 0, 'normal', '2025-11-04 22:08:07'),
(2585, 148, 467, 0, 'normal', '2025-11-04 22:08:07'),
(2586, 148, 468, 0, 'normal', '2025-11-04 22:08:07'),
(2587, 148, 561, 0, 'normal', '2025-11-04 22:08:07'),
(2588, 148, 562, 0, 'normal', '2025-11-04 22:08:07'),
(2589, 148, 563, 0, 'normal', '2025-11-04 22:08:07'),
(2590, 148, 564, 0, 'normal', '2025-11-04 22:08:07'),
(2591, 148, 565, 0, 'normal', '2025-11-04 22:08:07'),
(2592, 148, 566, 0, 'normal', '2025-11-04 22:08:07'),
(2593, 148, 567, 0, 'normal', '2025-11-04 22:08:07'),
(2594, 148, 568, 0, 'normal', '2025-11-04 22:08:07'),
(2595, 148, 569, 0, 'normal', '2025-11-04 22:08:07'),
(2596, 148, 570, 0, 'normal', '2025-11-04 22:08:07'),
(2597, 148, 571, 0, 'normal', '2025-11-04 22:08:07'),
(2598, 148, 572, 0, 'normal', '2025-11-04 22:08:07'),
(2599, 148, 573, 0, 'normal', '2025-11-04 22:08:07'),
(2600, 148, 574, 0, 'normal', '2025-11-04 22:08:07'),
(2601, 148, 575, 0, 'normal', '2025-11-04 22:08:07'),
(2602, 148, 576, 0, 'normal', '2025-11-04 22:08:07'),
(2603, 148, 577, 0, 'normal', '2025-11-04 22:08:07'),
(2604, 148, 578, 0, 'normal', '2025-11-04 22:08:07'),
(2605, 149, 469, 0, 'normal', '2025-11-04 22:08:18'),
(2606, 149, 470, 0, 'normal', '2025-11-04 22:08:18'),
(2607, 149, 471, 0, 'normal', '2025-11-04 22:08:18'),
(2608, 149, 472, 0, 'normal', '2025-11-04 22:08:18'),
(2609, 149, 473, 0, 'normal', '2025-11-04 22:08:18'),
(2610, 149, 474, 0, 'normal', '2025-11-04 22:08:18'),
(2611, 149, 475, 0, 'normal', '2025-11-04 22:08:18'),
(2612, 149, 476, 0, 'normal', '2025-11-04 22:08:18'),
(2613, 149, 477, 0, 'normal', '2025-11-04 22:08:18'),
(2614, 149, 478, 0, 'normal', '2025-11-04 22:08:18'),
(2615, 149, 479, 0, 'normal', '2025-11-04 22:08:18'),
(2616, 149, 480, 0, 'normal', '2025-11-04 22:08:18'),
(2617, 149, 481, 0, 'normal', '2025-11-04 22:08:18'),
(2618, 149, 482, 0, 'normal', '2025-11-04 22:08:18'),
(2619, 149, 483, 0, 'normal', '2025-11-04 22:08:18'),
(2620, 149, 484, 0, 'normal', '2025-11-04 22:08:18'),
(2621, 149, 485, 0, 'normal', '2025-11-04 22:08:18'),
(2622, 149, 486, 0, 'normal', '2025-11-04 22:08:18'),
(2623, 149, 487, 0, 'normal', '2025-11-04 22:08:18'),
(2624, 149, 488, 0, 'normal', '2025-11-04 22:08:18'),
(2625, 149, 597, 0, 'normal', '2025-11-04 22:08:18'),
(2626, 149, 539, 0, 'normal', '2025-11-04 22:08:18'),
(2627, 149, 540, 0, 'normal', '2025-11-04 22:08:18'),
(2628, 149, 541, 0, 'normal', '2025-11-04 22:08:18'),
(2629, 149, 542, 0, 'normal', '2025-11-04 22:08:18'),
(2630, 149, 543, 0, 'normal', '2025-11-04 22:08:18'),
(2631, 149, 544, 0, 'normal', '2025-11-04 22:08:18'),
(2632, 149, 545, 0, 'normal', '2025-11-04 22:08:18'),
(2633, 149, 546, 0, 'normal', '2025-11-04 22:08:18'),
(2634, 149, 547, 0, 'normal', '2025-11-04 22:08:18'),
(2635, 149, 548, 0, 'normal', '2025-11-04 22:08:18'),
(2636, 149, 549, 0, 'normal', '2025-11-04 22:08:18'),
(2637, 149, 550, 0, 'normal', '2025-11-04 22:08:18'),
(2638, 149, 551, 0, 'normal', '2025-11-04 22:08:18'),
(2639, 149, 552, 0, 'normal', '2025-11-04 22:08:18'),
(2640, 149, 553, 0, 'normal', '2025-11-04 22:08:18'),
(2641, 149, 554, 0, 'normal', '2025-11-04 22:08:18'),
(2642, 149, 555, 0, 'normal', '2025-11-04 22:08:18'),
(2643, 149, 556, 0, 'normal', '2025-11-04 22:08:18'),
(2644, 149, 557, 0, 'normal', '2025-11-04 22:08:18'),
(2645, 149, 558, 0, 'normal', '2025-11-04 22:08:18'),
(2646, 149, 559, 0, 'normal', '2025-11-04 22:08:18'),
(2647, 149, 560, 0, 'normal', '2025-11-04 22:08:18'),
(2648, 150, 489, 0, 'normal', '2025-11-04 22:08:32'),
(2649, 150, 490, 0, 'normal', '2025-11-04 22:08:32'),
(2650, 150, 491, 0, 'normal', '2025-11-04 22:08:32'),
(2651, 150, 492, 0, 'normal', '2025-11-04 22:08:32'),
(2652, 150, 493, 0, 'normal', '2025-11-04 22:08:32'),
(2653, 150, 494, 0, 'normal', '2025-11-04 22:08:32'),
(2654, 150, 495, 0, 'normal', '2025-11-04 22:08:32'),
(2655, 150, 496, 0, 'normal', '2025-11-04 22:08:32'),
(2656, 150, 497, 0, 'normal', '2025-11-04 22:08:32'),
(2657, 150, 498, 0, 'normal', '2025-11-04 22:08:32'),
(2658, 150, 499, 0, 'normal', '2025-11-04 22:08:32'),
(2659, 150, 500, 0, 'normal', '2025-11-04 22:08:32'),
(2660, 150, 501, 0, 'normal', '2025-11-04 22:08:32'),
(2661, 150, 502, 0, 'normal', '2025-11-04 22:08:32'),
(2662, 150, 503, 0, 'normal', '2025-11-04 22:08:32'),
(2663, 150, 504, 0, 'normal', '2025-11-04 22:08:32'),
(2664, 150, 505, 0, 'normal', '2025-11-04 22:08:32'),
(2665, 150, 506, 0, 'normal', '2025-11-04 22:08:32'),
(2666, 150, 507, 0, 'normal', '2025-11-04 22:08:32'),
(2667, 150, 508, 0, 'normal', '2025-11-04 22:08:32'),
(2668, 150, 509, 0, 'normal', '2025-11-04 22:08:32'),
(2669, 150, 510, 0, 'normal', '2025-11-04 22:08:32'),
(2670, 150, 511, 0, 'normal', '2025-11-04 22:08:32'),
(2671, 150, 512, 0, 'normal', '2025-11-04 22:08:32'),
(2672, 150, 513, 0, 'normal', '2025-11-04 22:08:32'),
(2673, 150, 514, 0, 'normal', '2025-11-04 22:08:32'),
(2674, 150, 515, 0, 'normal', '2025-11-04 22:08:32'),
(2675, 150, 516, 0, 'normal', '2025-11-04 22:08:32'),
(2676, 150, 517, 0, 'normal', '2025-11-04 22:08:32'),
(2677, 150, 518, 0, 'normal', '2025-11-04 22:08:32'),
(2678, 150, 519, 0, 'normal', '2025-11-04 22:08:32'),
(2679, 150, 520, 0, 'normal', '2025-11-04 22:08:32'),
(2680, 150, 521, 0, 'normal', '2025-11-04 22:08:32'),
(2681, 150, 522, 0, 'normal', '2025-11-04 22:08:32'),
(2682, 150, 523, 0, 'normal', '2025-11-04 22:08:32'),
(2683, 150, 524, 0, 'normal', '2025-11-04 22:08:32'),
(2684, 150, 525, 0, 'normal', '2025-11-04 22:08:32'),
(2685, 150, 526, 0, 'normal', '2025-11-04 22:08:32'),
(2686, 150, 527, 0, 'normal', '2025-11-04 22:08:32'),
(2687, 150, 528, 0, 'normal', '2025-11-04 22:08:32'),
(2688, 150, 529, 0, 'normal', '2025-11-04 22:08:32'),
(2689, 150, 530, 0, 'normal', '2025-11-04 22:08:32'),
(2690, 150, 531, 0, 'normal', '2025-11-04 22:08:32'),
(2691, 150, 532, 0, 'normal', '2025-11-04 22:08:32'),
(2692, 150, 533, 0, 'normal', '2025-11-04 22:08:32'),
(2693, 150, 534, 0, 'normal', '2025-11-04 22:08:32'),
(2694, 150, 535, 0, 'normal', '2025-11-04 22:08:32'),
(2695, 150, 536, 0, 'normal', '2025-11-04 22:08:32'),
(2696, 150, 537, 0, 'normal', '2025-11-04 22:08:32'),
(2697, 150, 538, 0, 'normal', '2025-11-04 22:08:32'),
(2698, 322, 524, 1, 'normal', '2025-11-05 11:39:52'),
(2699, 322, 529, 2, 'normal', '2025-11-05 11:39:52'),
(2700, 322, 521, 3, 'normal', '2025-11-05 11:39:52'),
(2701, 322, 523, 4, 'normal', '2025-11-05 11:39:52'),
(2739, 321, 592, 1, 'normal', '2025-11-05 11:53:50'),
(2740, 321, 583, 2, 'normal', '2025-11-05 11:53:50'),
(2741, 321, 589, 3, 'normal', '2025-11-05 11:53:50'),
(2742, 321, 585, 4, 'normal', '2025-11-05 11:53:50'),
(2743, 321, 598, 5, 'normal', '2025-11-05 11:53:50'),
(2744, 321, 587, 6, 'normal', '2025-11-05 11:53:50'),
(2745, 321, 588, 7, 'normal', '2025-11-05 11:53:50'),
(2746, 321, 582, 8, 'normal', '2025-11-05 11:53:50'),
(2747, 321, 581, 9, 'normal', '2025-11-05 11:53:50'),
(2748, 321, 593, 10, 'normal', '2025-11-05 11:53:50'),
(2749, 321, 586, 11, 'normal', '2025-11-05 11:53:50'),
(2750, 321, 595, 12, 'normal', '2025-11-05 11:53:50'),
(2751, 321, 580, 13, 'normal', '2025-11-05 11:53:50'),
(2752, 321, 596, 14, 'normal', '2025-11-05 11:53:50'),
(2753, 321, 590, 15, 'normal', '2025-11-05 11:53:50'),
(2754, 321, 539, 0, 'normal', '2025-11-05 11:53:50'),
(2755, 321, 540, 0, 'normal', '2025-11-05 11:53:50'),
(2756, 321, 541, 0, 'normal', '2025-11-05 11:53:50'),
(2757, 321, 542, 0, 'normal', '2025-11-05 11:53:50'),
(2758, 321, 543, 0, 'normal', '2025-11-05 11:53:50'),
(2759, 321, 544, 0, 'normal', '2025-11-05 11:53:50'),
(2760, 321, 545, 0, 'normal', '2025-11-05 11:53:50'),
(2761, 321, 546, 0, 'normal', '2025-11-05 11:53:50'),
(2762, 321, 547, 0, 'normal', '2025-11-05 11:53:50'),
(2763, 321, 548, 0, 'normal', '2025-11-05 11:53:50'),
(2764, 321, 549, 0, 'normal', '2025-11-05 11:53:50'),
(2765, 321, 550, 0, 'normal', '2025-11-05 11:53:50'),
(2766, 321, 551, 0, 'normal', '2025-11-05 11:53:50'),
(2767, 321, 552, 0, 'normal', '2025-11-05 11:53:50'),
(2768, 321, 553, 0, 'normal', '2025-11-05 11:53:50'),
(2769, 321, 554, 0, 'normal', '2025-11-05 11:53:50'),
(2770, 321, 555, 0, 'normal', '2025-11-05 11:53:50');
INSERT INTO `jugadores_partido` (`id`, `partido_id`, `jugador_id`, `numero_camiseta`, `tipo_partido`, `created_at`) VALUES
(2771, 321, 556, 0, 'normal', '2025-11-05 11:53:50'),
(2772, 321, 557, 0, 'normal', '2025-11-05 11:53:50'),
(2773, 321, 558, 0, 'normal', '2025-11-05 11:53:50'),
(2774, 321, 559, 0, 'normal', '2025-11-05 11:53:50'),
(2775, 321, 560, 0, 'normal', '2025-11-05 11:53:50'),
(2776, 315, 513, 0, 'normal', '2025-11-05 13:31:34'),
(2777, 315, 514, 0, 'normal', '2025-11-05 13:31:34'),
(2778, 315, 515, 0, 'normal', '2025-11-05 13:31:34'),
(2779, 315, 516, 0, 'normal', '2025-11-05 13:31:34'),
(2780, 315, 517, 0, 'normal', '2025-11-05 13:31:34'),
(2781, 315, 518, 0, 'normal', '2025-11-05 13:31:34'),
(2782, 315, 519, 0, 'normal', '2025-11-05 13:31:34'),
(2783, 315, 520, 0, 'normal', '2025-11-05 13:31:34'),
(2784, 315, 521, 0, 'normal', '2025-11-05 13:31:34'),
(2785, 315, 522, 0, 'normal', '2025-11-05 13:31:34'),
(2786, 315, 523, 0, 'normal', '2025-11-05 13:31:34'),
(2787, 315, 524, 0, 'normal', '2025-11-05 13:31:34'),
(2788, 315, 525, 0, 'normal', '2025-11-05 13:31:34'),
(2789, 315, 526, 0, 'normal', '2025-11-05 13:31:34'),
(2790, 315, 527, 0, 'normal', '2025-11-05 13:31:34'),
(2791, 315, 528, 0, 'normal', '2025-11-05 13:31:34'),
(2792, 315, 529, 0, 'normal', '2025-11-05 13:31:34'),
(2793, 315, 530, 0, 'normal', '2025-11-05 13:31:34'),
(2794, 315, 531, 0, 'normal', '2025-11-05 13:31:34'),
(2795, 315, 532, 0, 'normal', '2025-11-05 13:31:34'),
(2796, 315, 533, 0, 'normal', '2025-11-05 13:31:34'),
(2797, 315, 534, 0, 'normal', '2025-11-05 13:31:34'),
(2798, 315, 535, 0, 'normal', '2025-11-05 13:31:34'),
(2799, 315, 536, 0, 'normal', '2025-11-05 13:31:34'),
(2800, 315, 537, 0, 'normal', '2025-11-05 13:31:34'),
(2801, 315, 538, 0, 'normal', '2025-11-05 13:31:34'),
(2802, 315, 579, 0, 'normal', '2025-11-05 13:31:34'),
(2803, 315, 580, 0, 'normal', '2025-11-05 13:31:34'),
(2804, 315, 581, 0, 'normal', '2025-11-05 13:31:34'),
(2805, 315, 582, 0, 'normal', '2025-11-05 13:31:34'),
(2806, 315, 583, 0, 'normal', '2025-11-05 13:31:34'),
(2807, 315, 584, 0, 'normal', '2025-11-05 13:31:34'),
(2808, 315, 585, 0, 'normal', '2025-11-05 13:31:34'),
(2809, 315, 586, 0, 'normal', '2025-11-05 13:31:34'),
(2810, 315, 587, 0, 'normal', '2025-11-05 13:31:34'),
(2811, 315, 588, 0, 'normal', '2025-11-05 13:31:34'),
(2812, 315, 589, 0, 'normal', '2025-11-05 13:31:34'),
(2813, 315, 590, 0, 'normal', '2025-11-05 13:31:34'),
(2814, 315, 591, 0, 'normal', '2025-11-05 13:31:34'),
(2815, 315, 592, 0, 'normal', '2025-11-05 13:31:34'),
(2816, 315, 593, 0, 'normal', '2025-11-05 13:31:34'),
(2817, 315, 594, 0, 'normal', '2025-11-05 13:31:34'),
(2818, 315, 595, 0, 'normal', '2025-11-05 13:31:34'),
(2819, 315, 596, 0, 'normal', '2025-11-05 13:31:34'),
(2820, 315, 598, 0, 'normal', '2025-11-05 13:31:34'),
(2821, 318, 579, 0, 'normal', '2025-11-05 13:34:25'),
(2822, 318, 580, 0, 'normal', '2025-11-05 13:34:25'),
(2823, 318, 581, 0, 'normal', '2025-11-05 13:34:25'),
(2824, 318, 582, 0, 'normal', '2025-11-05 13:34:25'),
(2825, 318, 583, 0, 'normal', '2025-11-05 13:34:25'),
(2826, 318, 584, 0, 'normal', '2025-11-05 13:34:25'),
(2827, 318, 585, 0, 'normal', '2025-11-05 13:34:25'),
(2828, 318, 586, 0, 'normal', '2025-11-05 13:34:25'),
(2829, 318, 587, 0, 'normal', '2025-11-05 13:34:25'),
(2830, 318, 588, 0, 'normal', '2025-11-05 13:34:25'),
(2831, 318, 589, 0, 'normal', '2025-11-05 13:34:25'),
(2832, 318, 590, 0, 'normal', '2025-11-05 13:34:25'),
(2833, 318, 591, 0, 'normal', '2025-11-05 13:34:25'),
(2834, 318, 592, 0, 'normal', '2025-11-05 13:34:25'),
(2835, 318, 593, 0, 'normal', '2025-11-05 13:34:25'),
(2836, 318, 594, 0, 'normal', '2025-11-05 13:34:25'),
(2837, 318, 595, 0, 'normal', '2025-11-05 13:34:25'),
(2838, 318, 596, 0, 'normal', '2025-11-05 13:34:25'),
(2839, 318, 598, 0, 'normal', '2025-11-05 13:34:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log_sanciones`
--

CREATE TABLE `log_sanciones` (
  `id` int(11) NOT NULL,
  `sancion_id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL,
  `fechas_cumplidas` int(11) NOT NULL COMMENT 'Fechas cumplidas hasta este partido',
  `fecha_registro` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `log_sanciones`
--

INSERT INTO `log_sanciones` (`id`, `sancion_id`, `partido_id`, `fechas_cumplidas`, `fecha_registro`) VALUES
(43, 96, 145, 1, '2025-11-04 21:49:00'),
(44, 97, 145, 1, '2025-11-04 21:49:00'),
(45, 95, 145, 1, '2025-11-04 21:49:00'),
(46, 98, 133, 1, '2025-11-04 21:57:31'),
(47, 99, 139, 1, '2025-11-04 22:07:05'),
(48, 103, 318, 1, '2025-11-05 13:34:25'),
(49, 104, 318, 1, '2025-11-05 13:34:25'),
(50, 102, 318, 1, '2025-11-05 13:34:25');

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
  `minuto_periodo` int(11) DEFAULT 0,
  `segundos_transcurridos` int(11) DEFAULT 0,
  `tiempo_actual` enum('primer_tiempo','descanso','segundo_tiempo','finalizado') DEFAULT 'primer_tiempo',
  `iniciado_at` timestamp NULL DEFAULT NULL,
  `finalizado_at` timestamp NULL DEFAULT NULL,
  `observaciones` text DEFAULT NULL,
  `zona_id` int(11) DEFAULT NULL COMMENT 'ID de zona si es partido de fase de grupos',
  `fase_eliminatoria_id` int(11) DEFAULT NULL COMMENT 'ID de fase eliminatoria si es partido eliminatorio',
  `numero_llave` int(11) DEFAULT NULL COMMENT 'Número de llave en fase eliminatoria',
  `origen_local` varchar(255) DEFAULT NULL COMMENT 'Origen del equipo local (ej: "1° Zona A")',
  `origen_visitante` varchar(255) DEFAULT NULL COMMENT 'Origen del equipo visitante',
  `goles_local_penales` int(11) DEFAULT NULL COMMENT 'Goles en penales (equipo local)',
  `goles_visitante_penales` int(11) DEFAULT NULL COMMENT 'Goles en penales (equipo visitante)',
  `tipo_torneo` enum('normal','zona','eliminatoria') DEFAULT 'normal' COMMENT 'Tipo de partido',
  `jornada_zona` int(11) DEFAULT NULL COMMENT 'Número de jornada dentro de la zona'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `partidos`
--

INSERT INTO `partidos` (`id`, `fecha_id`, `equipo_local_id`, `equipo_visitante_id`, `cancha_id`, `fecha_partido`, `hora_partido`, `goles_local`, `goles_visitante`, `estado`, `minuto_actual`, `minuto_periodo`, `segundos_transcurridos`, `tiempo_actual`, `iniciado_at`, `finalizado_at`, `observaciones`, `zona_id`, `fase_eliminatoria_id`, `numero_llave`, `origen_local`, `origen_visitante`, `goles_local_penales`, `goles_visitante_penales`, `tipo_torneo`, `jornada_zona`) VALUES
(130, 1718, 243, 238, 17, '2025-11-01', '13:30:00', 0, 1, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:05:55', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(131, 1718, 242, 239, 17, '2025-11-01', '14:00:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 21:56:55', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(132, 1718, 241, 240, 17, '2025-11-01', '14:40:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:06:18', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(133, 1719, 242, 244, 32, '2025-11-08', '13:30:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 21:57:31', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(134, 1719, 241, 238, 32, '2025-11-08', '14:00:00', 0, 1, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 21:31:11', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(135, 1719, 240, 239, 32, '2025-11-08', '14:40:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:06:32', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(136, 1720, 241, 243, 33, '2025-11-15', '13:30:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:06:45', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(137, 1720, 240, 244, 33, '2025-11-15', '14:00:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:06:54', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(138, 1720, 239, 238, 33, '2025-11-15', '14:40:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 21:32:00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(139, 1721, 240, 242, 17, '2025-11-22', '13:30:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:07:05', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(140, 1721, 239, 243, 17, '2025-11-22', '14:00:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:07:14', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(141, 1721, 238, 244, 17, '2025-11-22', '14:40:00', 1, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 21:32:31', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(142, 1722, 239, 241, 38, '2025-11-29', '13:30:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:07:28', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(143, 1722, 238, 242, 38, '2025-11-29', '14:00:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 21:34:00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(144, 1722, 244, 243, 38, '2025-11-29', '14:40:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:07:34', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(145, 1723, 238, 240, 28, '2025-12-06', '13:30:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 21:49:00', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(146, 1723, 244, 241, 28, '2025-12-06', '14:00:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:07:48', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(147, 1723, 243, 242, 28, '2025-12-06', '14:40:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:07:54', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(148, 1724, 244, 239, 24, '2025-12-13', '13:30:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:08:07', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(149, 1724, 243, 240, 24, '2025-12-13', '14:00:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:08:18', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(150, 1724, 242, 241, 24, '2025-12-13', '14:40:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-04 22:08:32', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(315, 1756, 248, 245, 17, '2026-02-07', '13:30:00', 0, 2, 'finalizado', 29, -1, 1774, 'finalizado', NULL, '2025-11-05 13:31:34', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(316, 1756, 247, 246, 17, '2026-02-07', '14:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(317, 1757, 247, 249, 21, '2026-02-14', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(318, 1757, 246, 245, 21, '2026-02-14', '14:00:00', 0, 9, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-05 13:34:25', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(319, 1758, 246, 248, 26, '2026-02-21', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(320, 1758, 245, 249, 26, '2026-02-21', '14:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(321, 1759, 245, 247, 26, '2026-02-28', '13:30:00', 1, 0, 'finalizado', 11, -19, 698, 'finalizado', NULL, '2025-11-05 11:53:50', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(322, 1759, 249, 248, 26, '2026-02-28', '14:00:00', 0, 1, 'finalizado', 9, -21, 544, 'finalizado', NULL, '2025-11-05 11:39:52', '', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(323, 1760, 249, 246, NULL, '2026-03-07', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(324, 1760, 248, 247, NULL, '2026-03-07', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'normal', NULL),
(981, 1885, 252, 262, 17, '2025-11-06', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 1),
(982, 1885, 253, 264, 17, '2025-11-06', '14:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 1),
(983, 1885, 255, 254, 17, '2025-11-06', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 1),
(984, 1886, 264, 252, 38, '2025-11-13', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 2),
(985, 1886, 254, 262, 38, '2025-11-13', '14:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 2),
(986, 1886, 255, 253, 38, '2025-11-13', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 2),
(987, 1887, 252, 254, 30, '2025-11-20', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 3),
(988, 1887, 264, 255, 30, '2025-11-20', '14:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 3),
(989, 1887, 262, 253, 30, '2025-11-20', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 3),
(990, 1888, 255, 252, 32, '2025-11-27', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 4),
(991, 1888, 253, 254, 32, '2025-11-27', '14:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 4),
(992, 1888, 262, 264, 32, '2025-11-27', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 4),
(993, 1889, 252, 253, 21, '2025-12-04', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 5),
(994, 1889, 255, 262, 21, '2025-12-04', '14:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 5),
(995, 1889, 254, 264, 21, '2025-12-04', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 48, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 5),
(996, 1890, 258, 260, 17, '2025-11-06', '15:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 1),
(997, 1890, 251, 261, 17, '2025-11-06', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 1),
(998, 1890, 266, 256, 17, '2025-11-06', '17:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 1),
(999, 1891, 261, 258, 38, '2025-11-13', '15:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 2),
(1000, 1891, 256, 260, 38, '2025-11-13', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 2),
(1001, 1891, 266, 251, 38, '2025-11-13', '17:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 2),
(1002, 1892, 258, 256, 30, '2025-11-20', '15:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 3),
(1003, 1892, 261, 266, 30, '2025-11-20', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 3),
(1004, 1892, 260, 251, 30, '2025-11-20', '17:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 3),
(1005, 1893, 266, 258, 32, '2025-11-27', '15:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 4),
(1006, 1893, 251, 256, 32, '2025-11-27', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 4),
(1007, 1893, 260, 261, 32, '2025-11-27', '17:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 4),
(1008, 1894, 258, 251, 21, '2025-12-04', '15:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 5),
(1009, 1894, 266, 260, 21, '2025-12-04', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 5),
(1010, 1894, 256, 261, 21, '2025-12-04', '17:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 49, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 5),
(1011, 1895, 265, 263, 17, '2025-11-06', '18:10:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 50, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 1),
(1012, 1895, 259, 267, 17, '2025-11-06', '18:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 50, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 1),
(1013, 1896, 263, 257, 38, '2025-11-13', '18:10:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 50, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 2),
(1014, 1896, 259, 265, 38, '2025-11-13', '18:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 50, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 2),
(1015, 1897, 257, 267, 30, '2025-11-20', '18:10:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 50, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 3),
(1016, 1897, 263, 259, 30, '2025-11-20', '18:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 50, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 3),
(1017, 1898, 259, 257, 32, '2025-11-27', '18:10:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 50, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 4),
(1018, 1898, 265, 267, 32, '2025-11-27', '18:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 50, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 4),
(1019, 1899, 257, 265, 21, '2025-12-04', '18:10:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 50, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 5),
(1020, 1899, 267, 263, 21, '2025-12-04', '18:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, 50, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partidos_eliminatorios`
--

CREATE TABLE `partidos_eliminatorios` (
  `id` int(11) NOT NULL,
  `fase_id` int(11) NOT NULL,
  `equipo_local_id` int(11) DEFAULT NULL COMMENT 'NULL si aún no se define',
  `equipo_visitante_id` int(11) DEFAULT NULL,
  `partido_id` int(11) DEFAULT NULL COMMENT 'Referencia al partido real',
  `numero_llave` int(11) NOT NULL COMMENT '1,2,3,4 para identificar la llave',
  `origen_local` varchar(100) DEFAULT NULL COMMENT 'Descripción de origen: "1° Zona A", "Ganador Llave 1"',
  `origen_visitante` varchar(100) DEFAULT NULL,
  `fecha_partido` date DEFAULT NULL,
  `hora_partido` time DEFAULT NULL,
  `cancha_id` int(11) DEFAULT NULL,
  `goles_local` int(11) DEFAULT NULL,
  `goles_visitante` int(11) DEFAULT NULL,
  `penales_local` int(11) DEFAULT NULL,
  `penales_visitante` int(11) DEFAULT NULL,
  `ganador_id` int(11) DEFAULT NULL,
  `estado` enum('pendiente','programado','en_curso','finalizado','suspendido','cancelado') DEFAULT 'pendiente',
  `finalizado_at` timestamp NULL DEFAULT NULL
) ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `partidos_zona`
--

CREATE TABLE `partidos_zona` (
  `id` int(11) NOT NULL,
  `zona_id` int(11) NOT NULL,
  `equipo_local_id` int(11) NOT NULL,
  `equipo_visitante_id` int(11) NOT NULL,
  `partido_id` int(11) DEFAULT NULL COMMENT 'Referencia al partido real en tabla partidos',
  `fecha_numero` int(11) NOT NULL,
  `fecha_partido` date NOT NULL,
  `hora_partido` time DEFAULT NULL,
  `cancha_id` int(11) DEFAULT NULL,
  `goles_local` int(11) DEFAULT NULL,
  `goles_visitante` int(11) DEFAULT NULL,
  `estado` enum('programado','en_curso','finalizado','suspendido','cancelado') DEFAULT 'programado',
  `finalizado_at` timestamp NULL DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ;

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
(95, 593, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-11-04'),
(96, 583, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-11-04'),
(97, 590, 'amarillas_acumuladas', 1, 1, '4 amarillas acumuladas en el campeonato', 0, '2025-11-04'),
(98, 502, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-11-04'),
(99, 503, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-11-04'),
(100, 520, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-11-05'),
(101, 529, 'doble_amarilla', 1, 0, 'Doble amarilla en partido', 1, '2025-11-05'),
(102, 596, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-11-05'),
(103, 595, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-11-05'),
(104, 595, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-11-05');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `turnos_horarios`
--

CREATE TABLE `turnos_horarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `temporada` enum('verano','invierno') NOT NULL,
  `horarios` text NOT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `turnos_horarios`
--

INSERT INTO `turnos_horarios` (`id`, `nombre`, `temporada`, `horarios`, `descripcion`, `activo`, `created_at`) VALUES
(1, 'Noche', 'verano', '[\"20:00\",\"21:30\",\"22:00\",\"23:30\"]', 'nocturno', 1, '2025-10-31 02:20:29');

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
(2, 'Planillero', '$2y$10$g6yqCuSP4kB8z8IqgpGmfOUryVdK9/k8qnoQI7qKODmCA9xSE.RIW', 'wrbroder@gmail.com', 'Planillero', 'planillero', 1, 'H7I29G', '2025-09-16 01:47:00'),
(3, 'Walter', '$2y$10$17rq2Psh76ynZ2egkhFqdOepDsmznyApGnXDMrEwhhvknWPuNxl8C', 'wrbroder@gmail.com', 'Watler', 'admin', 1, NULL, '2025-09-16 01:53:16'),
(5, 'Pata', '$2y$10$nIn1tXPgAq7WI2J2odWZzeAdjs4Fdcm.hhhIFN/lgWMxJyQEXM0a6', '', 'Pata Romero', 'admin', 1, NULL, '2025-09-26 17:41:03');

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_sanciones_completas`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_sanciones_completas` (
`id` int(11)
,`jugador_id` int(11)
,`apellido_nombre` varchar(150)
,`dni` varchar(20)
,`equipo_id` int(11)
,`equipo` varchar(100)
,`categoria` varchar(50)
,`tipo` enum('amarillas_acumuladas','doble_amarilla','roja_directa','administrativa')
,`tipo_descripcion` varchar(14)
,`partidos_suspension` int(11)
,`partidos_cumplidos` int(11)
,`fechas_restantes` bigint(12)
,`descripcion` text
,`activa` tinyint(1)
,`fecha_sancion` date
,`registros_cumplimiento` bigint(21)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_tabla_posiciones_zona`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_tabla_posiciones_zona` (
`zona_id` int(11)
,`zona` varchar(50)
,`formato_id` int(11)
,`equipo_id` int(11)
,`equipo` varchar(100)
,`logo` varchar(255)
,`pts` int(11)
,`pj` int(11)
,`pg` int(11)
,`pe` int(11)
,`pp` int(11)
,`gf` int(11)
,`gc` int(11)
,`dif` int(11)
,`ta` int(11)
,`tr` int(11)
);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `zonas`
--

CREATE TABLE `zonas` (
  `id` int(11) NOT NULL,
  `formato_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL COMMENT 'Zona A, Zona B, etc',
  `orden` int(11) NOT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `zonas`
--

INSERT INTO `zonas` (`id`, `formato_id`, `nombre`, `orden`, `activa`) VALUES
(48, 13, 'Zona A', 1, 1),
(49, 13, 'Zona B', 2, 1),
(50, 13, 'Zona C', 3, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `campeonatos`
--
ALTER TABLE `campeonatos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `campeonatos_formato`
--
ALTER TABLE `campeonatos_formato`
  ADD PRIMARY KEY (`id`),
  ADD KEY `campeonato_id` (`campeonato_id`),
  ADD KEY `campeonatos_formato_ibfk_categoria` (`categoria_id`);

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
  ADD KEY `idx_equipos_categoria` (`categoria_id`);

--
-- Indices de la tabla `equipos_zonas`
--
ALTER TABLE `equipos_zonas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_equipo_zona` (`zona_id`,`equipo_id`),
  ADD KEY `equipo_id` (`equipo_id`),
  ADD KEY `idx_equipos_zonas_equipo` (`equipo_id`),
  ADD KEY `idx_equipos_zonas_posicion` (`posicion`,`puntos`);

--
-- Indices de la tabla `eventos_partido`
--
ALTER TABLE `eventos_partido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `jugador_id` (`jugador_id`),
  ADD KEY `idx_eventos_partido_tipo` (`tipo_partido`),
  ADD KEY `idx_eventos_tipo_partido` (`partido_id`,`tipo_partido`);

--
-- Indices de la tabla `fases_eliminatorias`
--
ALTER TABLE `fases_eliminatorias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_formato_nombre` (`formato_id`,`nombre`),
  ADD KEY `formato_id` (`formato_id`);

--
-- Indices de la tabla `fechas`
--
ALTER TABLE `fechas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categoria_id` (`categoria_id`),
  ADD KEY `idx_zona_id` (`zona_id`),
  ADD KEY `idx_fase_eliminatoria_id` (`fase_eliminatoria_id`);

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
  ADD KEY `idx_jugador_equipo` (`equipo_id`);

--
-- Indices de la tabla `jugadores_equipos_historial`
--
ALTER TABLE `jugadores_equipos_historial`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_jugador_dni` (`jugador_dni`),
  ADD KEY `idx_equipo_id` (`equipo_id`),
  ADD KEY `idx_campeonato_id` (`campeonato_id`);

--
-- Indices de la tabla `jugadores_partido`
--
ALTER TABLE `jugadores_partido`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_jugador_partido` (`partido_id`,`jugador_id`),
  ADD KEY `idx_partido` (`partido_id`),
  ADD KEY `idx_jugador` (`jugador_id`),
  ADD KEY `idx_jugadores_partido_tipo` (`tipo_partido`);

--
-- Indices de la tabla `log_sanciones`
--
ALTER TABLE `log_sanciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sancion_id` (`sancion_id`),
  ADD KEY `partido_id` (`partido_id`);

--
-- Indices de la tabla `partidos`
--
ALTER TABLE `partidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fecha_id` (`fecha_id`),
  ADD KEY `equipo_local_id` (`equipo_local_id`),
  ADD KEY `equipo_visitante_id` (`equipo_visitante_id`),
  ADD KEY `cancha_id` (`cancha_id`),
  ADD KEY `idx_partidos_estado` (`estado`,`fecha_partido`),
  ADD KEY `idx_zona_id` (`zona_id`),
  ADD KEY `idx_fase_eliminatoria_id` (`fase_eliminatoria_id`),
  ADD KEY `idx_tipo_torneo` (`tipo_torneo`);

--
-- Indices de la tabla `partidos_eliminatorios`
--
ALTER TABLE `partidos_eliminatorios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fase_id` (`fase_id`),
  ADD KEY `equipo_local_id` (`equipo_local_id`),
  ADD KEY `equipo_visitante_id` (`equipo_visitante_id`),
  ADD KEY `partido_id` (`partido_id`),
  ADD KEY `ganador_id` (`ganador_id`),
  ADD KEY `cancha_id` (`cancha_id`);

--
-- Indices de la tabla `partidos_zona`
--
ALTER TABLE `partidos_zona`
  ADD PRIMARY KEY (`id`),
  ADD KEY `zona_id` (`zona_id`),
  ADD KEY `equipo_local_id` (`equipo_local_id`),
  ADD KEY `equipo_visitante_id` (`equipo_visitante_id`),
  ADD KEY `partido_id` (`partido_id`),
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
  ADD KEY `jugador_id` (`jugador_id`),
  ADD KEY `idx_sanciones_activas` (`activa`,`jugador_id`),
  ADD KEY `idx_sanciones_fechas` (`partidos_cumplidos`,`partidos_suspension`);

--
-- Indices de la tabla `turnos_horarios`
--
ALTER TABLE `turnos_horarios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indices de la tabla `zonas`
--
ALTER TABLE `zonas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `formato_id` (`formato_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `campeonatos`
--
ALTER TABLE `campeonatos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `campeonatos_formato`
--
ALTER TABLE `campeonatos_formato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `canchas`
--
ALTER TABLE `canchas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT de la tabla `codigos_cancha`
--
ALTER TABLE `codigos_cancha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1577;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=268;

--
-- AUTO_INCREMENT de la tabla `equipos_zonas`
--
ALTER TABLE `equipos_zonas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=177;

--
-- AUTO_INCREMENT de la tabla `eventos_partido`
--
ALTER TABLE `eventos_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=70;

--
-- AUTO_INCREMENT de la tabla `fases_eliminatorias`
--
ALTER TABLE `fases_eliminatorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT de la tabla `fechas`
--
ALTER TABLE `fechas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1900;

--
-- AUTO_INCREMENT de la tabla `horarios_canchas`
--
ALTER TABLE `horarios_canchas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=317;

--
-- AUTO_INCREMENT de la tabla `jugadores`
--
ALTER TABLE `jugadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=599;

--
-- AUTO_INCREMENT de la tabla `jugadores_equipos_historial`
--
ALTER TABLE `jugadores_equipos_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=327;

--
-- AUTO_INCREMENT de la tabla `jugadores_partido`
--
ALTER TABLE `jugadores_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2840;

--
-- AUTO_INCREMENT de la tabla `log_sanciones`
--
ALTER TABLE `log_sanciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `partidos`
--
ALTER TABLE `partidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1021;

--
-- AUTO_INCREMENT de la tabla `partidos_eliminatorios`
--
ALTER TABLE `partidos_eliminatorios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `partidos_zona`
--
ALTER TABLE `partidos_zona`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `planillas`
--
ALTER TABLE `planillas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `sanciones`
--
ALTER TABLE `sanciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT de la tabla `turnos_horarios`
--
ALTER TABLE `turnos_horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `zonas`
--
ALTER TABLE `zonas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_sanciones_completas`
--
DROP TABLE IF EXISTS `v_sanciones_completas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u959527289_Nuevo`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_sanciones_completas`  AS SELECT `s`.`id` AS `id`, `s`.`jugador_id` AS `jugador_id`, `j`.`apellido_nombre` AS `apellido_nombre`, `j`.`dni` AS `dni`, `e`.`id` AS `equipo_id`, `e`.`nombre` AS `equipo`, `c`.`nombre` AS `categoria`, `s`.`tipo` AS `tipo`, CASE `s`.`tipo` WHEN 'amarillas_acumuladas' THEN '4 Amarillas' WHEN 'doble_amarilla' THEN 'Doble Amarilla' WHEN 'roja_directa' THEN 'Roja Directa' WHEN 'administrativa' THEN 'Administrativa' END AS `tipo_descripcion`, `s`.`partidos_suspension` AS `partidos_suspension`, `s`.`partidos_cumplidos` AS `partidos_cumplidos`, `s`.`partidos_suspension`- `s`.`partidos_cumplidos` AS `fechas_restantes`, `s`.`descripcion` AS `descripcion`, `s`.`activa` AS `activa`, `s`.`fecha_sancion` AS `fecha_sancion`, (select count(0) from `log_sanciones` where `log_sanciones`.`sancion_id` = `s`.`id`) AS `registros_cumplimiento` FROM (((`sanciones` `s` join `jugadores` `j` on(`s`.`jugador_id` = `j`.`id`)) join `equipos` `e` on(`j`.`equipo_id` = `e`.`id`)) join `categorias` `c` on(`e`.`categoria_id` = `c`.`id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_tabla_posiciones_zona`
--
DROP TABLE IF EXISTS `v_tabla_posiciones_zona`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u959527289_Nuevo`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_tabla_posiciones_zona`  AS SELECT `ez`.`zona_id` AS `zona_id`, `z`.`nombre` AS `zona`, `z`.`formato_id` AS `formato_id`, `ez`.`equipo_id` AS `equipo_id`, `e`.`nombre` AS `equipo`, `e`.`logo` AS `logo`, `ez`.`puntos` AS `pts`, `ez`.`partidos_jugados` AS `pj`, `ez`.`partidos_ganados` AS `pg`, `ez`.`partidos_empatados` AS `pe`, `ez`.`partidos_perdidos` AS `pp`, `ez`.`goles_favor` AS `gf`, `ez`.`goles_contra` AS `gc`, `ez`.`diferencia_gol` AS `dif`, coalesce(`ez`.`tarjetas_amarillas`,0) AS `ta`, coalesce(`ez`.`tarjetas_rojas`,0) AS `tr` FROM ((`equipos_zonas` `ez` join `zonas` `z` on(`ez`.`zona_id` = `z`.`id`)) join `equipos` `e` on(`ez`.`equipo_id` = `e`.`id`)) WHERE `z`.`activa` = 1 ORDER BY `ez`.`zona_id` ASC, `ez`.`puntos` DESC, `ez`.`diferencia_gol` DESC, `ez`.`goles_favor` DESC, coalesce(`ez`.`tarjetas_rojas`,0) ASC, coalesce(`ez`.`tarjetas_amarillas`,0) ASC ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `campeonatos_formato`
--
ALTER TABLE `campeonatos_formato`
  ADD CONSTRAINT `campeonatos_formato_ibfk_1` FOREIGN KEY (`campeonato_id`) REFERENCES `campeonatos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `campeonatos_formato_ibfk_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;

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
-- Filtros para la tabla `equipos_zonas`
--
ALTER TABLE `equipos_zonas`
  ADD CONSTRAINT `equipos_zonas_ibfk_1` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `equipos_zonas_ibfk_2` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `eventos_partido`
--
ALTER TABLE `eventos_partido`
  ADD CONSTRAINT `eventos_partido_ibfk_1` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `eventos_partido_ibfk_2` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`);

--
-- Filtros para la tabla `fases_eliminatorias`
--
ALTER TABLE `fases_eliminatorias`
  ADD CONSTRAINT `fases_eliminatorias_ibfk_1` FOREIGN KEY (`formato_id`) REFERENCES `campeonatos_formato` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `fechas`
--
ALTER TABLE `fechas`
  ADD CONSTRAINT `fechas_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fechas_ibfk_fase` FOREIGN KEY (`fase_eliminatoria_id`) REFERENCES `fases_eliminatorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fechas_ibfk_zona` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE SET NULL;

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
-- Filtros para la tabla `jugadores_equipos_historial`
--
ALTER TABLE `jugadores_equipos_historial`
  ADD CONSTRAINT `fk_historial_campeonato` FOREIGN KEY (`campeonato_id`) REFERENCES `campeonatos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_historial_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `jugadores_partido`
--
ALTER TABLE `jugadores_partido`
  ADD CONSTRAINT `jugadores_partido_ibfk_1` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `jugadores_partido_ibfk_2` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `log_sanciones`
--
ALTER TABLE `log_sanciones`
  ADD CONSTRAINT `log_sanciones_ibfk_1` FOREIGN KEY (`sancion_id`) REFERENCES `sanciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `log_sanciones_ibfk_2` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `partidos`
--
ALTER TABLE `partidos`
  ADD CONSTRAINT `partidos_ibfk_1` FOREIGN KEY (`fecha_id`) REFERENCES `fechas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_ibfk_2` FOREIGN KEY (`equipo_local_id`) REFERENCES `equipos` (`id`),
  ADD CONSTRAINT `partidos_ibfk_3` FOREIGN KEY (`equipo_visitante_id`) REFERENCES `equipos` (`id`),
  ADD CONSTRAINT `partidos_ibfk_4` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`),
  ADD CONSTRAINT `partidos_ibfk_fase` FOREIGN KEY (`fase_eliminatoria_id`) REFERENCES `fases_eliminatorias` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `partidos_ibfk_zona` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `partidos_eliminatorios`
--
ALTER TABLE `partidos_eliminatorios`
  ADD CONSTRAINT `partidos_eliminatorios_ibfk_1` FOREIGN KEY (`fase_id`) REFERENCES `fases_eliminatorias` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_eliminatorios_ibfk_2` FOREIGN KEY (`equipo_local_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_eliminatorios_ibfk_3` FOREIGN KEY (`equipo_visitante_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_eliminatorios_ibfk_4` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `partidos_eliminatorios_ibfk_5` FOREIGN KEY (`ganador_id`) REFERENCES `equipos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `partidos_eliminatorios_ibfk_6` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `partidos_eliminatorios_ibfk_cancha` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `partidos_zona`
--
ALTER TABLE `partidos_zona`
  ADD CONSTRAINT `partidos_zona_ibfk_1` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_zona_ibfk_2` FOREIGN KEY (`equipo_local_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_zona_ibfk_3` FOREIGN KEY (`equipo_visitante_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `partidos_zona_ibfk_4` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `partidos_zona_ibfk_5` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `sanciones`
--
ALTER TABLE `sanciones`
  ADD CONSTRAINT `sanciones_ibfk_1` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `zonas`
--
ALTER TABLE `zonas`
  ADD CONSTRAINT `zonas_ibfk_1` FOREIGN KEY (`formato_id`) REFERENCES `campeonatos_formato` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
