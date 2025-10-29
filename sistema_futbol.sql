-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 29-10-2025 a las 22:17:09
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
(1, 'Torneo Clausura 2025 - M40', 'Campeonato Clausura 2025', '2025-10-25', NULL, 1, '2025-09-15 01:30:36'),
(5, 'Torneo Clausura 2025 - M30', '', '2025-10-25', NULL, 1, '2025-10-22 13:44:19'),
(10, 'Torneo Nocturno 2026 - M40', '', '2025-11-01', NULL, 1, '2025-10-28 20:16:54');

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
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `campeonatos_formato`
--

INSERT INTO `campeonatos_formato` (`id`, `campeonato_id`, `tipo_formato`, `cantidad_zonas`, `equipos_por_zona`, `equipos_clasifican`, `tipo_clasificacion`, `tiene_octavos`, `tiene_cuartos`, `tiene_semifinal`, `tiene_tercer_puesto`, `activo`, `created_at`, `updated_at`) VALUES
(1, 1, 'mixto', 4, 3, 8, '2_primeros', 0, 1, 1, 1, 1, '2025-10-28 12:40:28', '2025-10-28 12:40:28');

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
(4, 5, 'A', 'M30 A', 1),
(5, 5, 'B', 'M30 B', 1),
(9, 10, 'M40', '', 1);

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
(1477, 17, '7LD8XP', '2025-11-15', 0, 1, '2025-10-27 02:00:12', '2025-11-15 20:30:00'),
(1478, 18, '4JYKED', '2025-11-15', 0, 1, '2025-10-27 02:00:12', '2025-11-15 13:30:00'),
(1523, 32, 'R3FKOW', '2025-12-13', 1, 0, '2025-10-27 20:52:11', '2025-12-13 19:10:00'),
(1524, 36, 'Q0HG9M', '2025-12-13', 1, 0, '2025-10-27 20:52:11', '2025-12-13 16:50:00');

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
(56, 1, 'La 17', 'equipos/La_17.png', '#007bff', '', 1, '2025-09-15 12:31:28'),
(75, 1, 'AFCAPER M40', NULL, '#007bff', '', 1, '2025-10-28 14:00:40'),
(88, 9, 'AFCAPER M40', NULL, '#007bff', '', 1, '2025-10-28 20:34:50'),
(89, 9, 'Agrupación Amadeus M40', NULL, '#007bff', '', 1, '2025-10-28 20:34:59'),
(90, 9, 'Agrupación Mariano Moreno FC M40', NULL, '#007bff', '', 1, '2025-10-28 20:35:09'),
(91, 9, 'Agrupación Roma M40', NULL, '#007bff', '', 1, '2025-10-28 20:35:19'),
(92, 9, 'Arrecife M40', NULL, '#007bff', '', 1, '2025-10-28 20:35:25'),
(93, 9, 'AVA M40', NULL, '#007bff', '', 1, '2025-10-28 20:35:32'),
(94, 9, 'Avenida Distribuciones M40', NULL, '#007bff', '', 1, '2025-10-28 20:35:39'),
(95, 9, 'Camioneros M40', NULL, '#007bff', '', 1, '2025-10-28 20:35:48'),
(96, 9, 'Distribuidora Tata', NULL, '#007bff', '', 1, '2025-10-28 20:35:56'),
(97, 9, 'El Fortin M40', NULL, '#007bff', '', 1, '2025-10-28 20:36:05'),
(98, 9, 'Farmacia Abril', NULL, '#007bff', '', 1, '2025-10-28 20:36:12'),
(99, 9, 'La Pingüina M40', NULL, '#007bff', '', 1, '2025-10-28 20:36:19'),
(100, 9, 'La 17', NULL, '#007bff', '', 1, '2025-10-28 20:36:27'),
(101, 9, 'Nono Gringo M40', NULL, '#007bff', '', 1, '2025-10-28 20:36:34'),
(102, 9, 'Taladro M40', NULL, '#007bff', '', 1, '2025-10-28 20:36:42'),
(103, 9, 'Villa Urquiza M40', NULL, '#007bff', '', 1, '2025-10-28 20:37:07'),
(104, 9, 'Unión de Viale M30', NULL, '#007bff', '', 1, '2025-10-28 20:37:28');

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
  `clasificado` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `equipos_zonas`
--

INSERT INTO `equipos_zonas` (`id`, `zona_id`, `equipo_id`, `puntos`, `partidos_jugados`, `partidos_ganados`, `partidos_empatados`, `partidos_perdidos`, `goles_favor`, `goles_contra`, `posicion`, `clasificado`) VALUES
(31, 16, 56, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(32, 17, 4, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(33, 18, 15, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(34, 19, 1, 0, 0, 0, 0, 0, 0, 0, 1, 0),
(35, 16, 12, 0, 0, 0, 0, 0, 0, 0, 2, 0),
(36, 17, 7, 0, 0, 0, 0, 0, 0, 0, 2, 0),
(37, 18, 2, 0, 0, 0, 0, 0, 0, 0, 2, 0),
(38, 19, 11, 0, 0, 0, 0, 0, 0, 0, 2, 0),
(39, 16, 14, 0, 0, 0, 0, 0, 0, 0, 3, 0),
(40, 17, 9, 0, 0, 0, 0, 0, 0, 0, 3, 0),
(41, 18, 13, 0, 0, 0, 0, 0, 0, 0, 3, 0),
(42, 19, 5, 0, 0, 0, 0, 0, 0, 0, 3, 0);

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
(964, 14846, 85, 'gol', 0, NULL, 'normal', '2025-10-22 16:47:13'),
(965, 14846, 84, 'gol', 0, NULL, 'normal', '2025-10-22 16:47:13'),
(966, 14846, 90, 'roja', 0, NULL, 'normal', '2025-10-22 16:47:13'),
(973, 14847, 312, 'gol', 0, NULL, 'normal', '2025-10-22 16:48:06'),
(974, 14847, 298, 'gol', 0, NULL, 'normal', '2025-10-22 16:48:06'),
(975, 14847, 296, 'gol', 0, NULL, 'normal', '2025-10-22 16:48:06'),
(976, 14847, 310, 'gol', 0, NULL, 'normal', '2025-10-22 16:48:06'),
(977, 14847, 309, 'gol', 0, NULL, 'normal', '2025-10-22 16:48:06'),
(978, 14847, 305, 'amarilla', 0, NULL, 'normal', '2025-10-22 16:48:06'),
(1008, 14844, 431, 'gol', 0, NULL, 'normal', '2025-10-23 19:33:12'),
(1014, 14849, 142, 'gol', 0, NULL, 'normal', '2025-10-23 19:37:26'),
(1015, 14849, 152, 'amarilla', 0, NULL, 'normal', '2025-10-23 19:37:26'),
(1016, 14849, 152, 'amarilla', 0, NULL, 'normal', '2025-10-23 19:37:26'),
(1017, 14850, 230, 'gol', 0, NULL, 'normal', '2025-10-23 21:10:40'),
(1018, 14850, 162, 'roja', 0, NULL, 'normal', '2025-10-23 21:10:40'),
(1019, 14857, 279, 'roja', 0, NULL, 'normal', '2025-10-23 21:12:09'),
(1022, 14856, 310, 'gol', 0, NULL, 'normal', '2025-10-23 21:14:59'),
(1023, 14856, 305, 'amarilla', 0, NULL, 'normal', '2025-10-23 21:14:59'),
(1024, 14865, 295, 'gol', 0, NULL, 'normal', '2025-10-23 21:56:34'),
(1025, 14865, 305, 'amarilla', 0, NULL, 'normal', '2025-10-23 21:56:34'),
(1026, 14867, 259, 'gol', 0, NULL, 'normal', '2025-10-23 22:23:57'),
(1027, 14867, 275, 'amarilla', 0, NULL, 'normal', '2025-10-23 22:23:57'),
(1030, 14883, 309, 'gol', 0, NULL, 'normal', '2025-10-23 22:27:02'),
(1031, 14883, 305, 'amarilla', 0, NULL, 'normal', '2025-10-23 22:27:02'),
(1032, 14883, 310, 'amarilla', 0, NULL, 'normal', '2025-10-23 22:27:02'),
(1033, 14883, 310, 'amarilla', 0, NULL, 'normal', '2025-10-23 22:27:02'),
(1038, 14852, 72, 'gol', 0, NULL, 'normal', '2025-10-24 22:13:18'),
(1039, 14852, 64, 'amarilla', 0, NULL, 'normal', '2025-10-24 22:13:18'),
(1040, 14863, 143, 'gol', 0, NULL, 'normal', '2025-10-24 22:15:43'),
(1041, 14863, 145, 'amarilla', 0, NULL, 'normal', '2025-10-24 22:15:43'),
(1042, 14863, 145, 'amarilla', 0, NULL, 'normal', '2025-10-24 22:15:43'),
(1043, 14863, 156, 'roja', 0, NULL, 'normal', '2025-10-24 22:15:43'),
(1044, 14862, 117, 'amarilla', 0, NULL, 'normal', '2025-10-24 22:17:10'),
(1045, 14862, 117, 'amarilla', 0, NULL, 'normal', '2025-10-24 22:17:10'),
(1046, 14864, 166, 'gol', 0, NULL, 'normal', '2025-10-24 22:26:36'),
(1047, 14861, 103, 'gol', 0, NULL, 'normal', '2025-10-24 22:40:27'),
(1048, 14861, 114, 'amarilla', 0, NULL, 'normal', '2025-10-24 22:40:27'),
(1049, 14891, 339, 'gol', 0, NULL, 'normal', '2025-10-24 22:43:14'),
(1050, 14891, 311, 'gol', 0, NULL, 'normal', '2025-10-24 22:43:14'),
(1051, 14891, 320, 'roja', 0, NULL, 'normal', '2025-10-24 22:43:14'),
(1052, 14891, 301, 'amarilla', 0, NULL, 'normal', '2025-10-24 22:43:14'),
(1053, 14884, 152, 'roja', 0, NULL, 'normal', '2025-10-24 22:44:17'),
(1054, 14889, 266, 'amarilla', 0, NULL, 'normal', '2025-10-24 22:45:24'),
(1055, 14889, 266, 'amarilla', 0, NULL, 'normal', '2025-10-24 22:45:24'),
(1056, 14874, 308, 'gol', 0, NULL, 'normal', '2025-10-24 22:59:14'),
(1057, 14874, 308, 'gol', 0, NULL, 'normal', '2025-10-24 22:59:14'),
(1058, 14874, 311, 'gol', 0, NULL, 'normal', '2025-10-24 22:59:14'),
(1059, 14874, 305, 'amarilla', 0, NULL, 'normal', '2025-10-24 22:59:14'),
(1060, 14868, 99, 'amarilla', 0, NULL, 'normal', '2025-10-24 23:01:35'),
(1061, 14868, 99, 'amarilla', 0, NULL, 'normal', '2025-10-24 23:01:35'),
(1065, 14875, 260, 'gol', 0, NULL, 'normal', '2025-10-24 23:23:28'),
(1066, 14875, 256, 'roja', 0, NULL, 'normal', '2025-10-24 23:23:28'),
(1070, 14860, 79, 'gol', 0, NULL, 'normal', '2025-10-24 23:31:14'),
(1071, 14860, 86, 'amarilla', 0, NULL, 'normal', '2025-10-24 23:31:14'),
(1074, 14882, 260, 'roja', 0, 'Doble amarilla', 'normal', '2025-10-24 23:52:18'),
(1077, 14878, 161, 'roja', 0, NULL, 'normal', '2025-10-24 23:54:23'),
(1078, 14878, 168, 'roja', 0, 'Doble amarilla', 'normal', '2025-10-24 23:54:23'),
(1081, 14896, 77, 'roja', 0, 'Doble amarilla', 'normal', '2025-10-25 00:01:30'),
(1082, 14895, 232, 'gol', 0, NULL, 'normal', '2025-10-25 01:58:37'),
(1085, 14895, 231, 'roja', 0, NULL, 'normal', '2025-10-25 01:58:37'),
(1086, 14895, 241, 'roja', 0, 'Doble amarilla', 'normal', '2025-10-25 01:58:37'),
(1087, 14890, 342, 'gol', 0, NULL, 'normal', '2025-10-26 15:07:48'),
(1088, 14890, 347, 'amarilla', 0, NULL, 'normal', '2025-10-26 15:07:48'),
(1089, 14890, 283, 'amarilla', 0, NULL, 'normal', '2025-10-26 15:07:48'),
(1090, 14890, 282, 'roja', 0, NULL, 'normal', '2025-10-26 15:07:48'),
(1091, 14908, 210, 'gol', 0, NULL, 'normal', '2025-10-26 17:18:52'),
(1092, 14908, 380, 'gol', 0, NULL, 'normal', '2025-10-26 17:18:52'),
(1093, 14908, 213, 'amarilla', 0, NULL, 'normal', '2025-10-26 17:18:52'),
(1094, 14908, 213, 'amarilla', 0, NULL, 'normal', '2025-10-26 17:18:52'),
(1095, 14908, 365, 'roja', 0, NULL, 'normal', '2025-10-26 17:18:52'),
(1096, 14909, 242, 'gol', 0, NULL, 'normal', '2025-10-26 17:41:51'),
(1097, 14911, 280, 'gol', 0, NULL, 'normal', '2025-10-26 22:11:05'),
(1099, 14911, 153, 'roja', 0, NULL, 'normal', '2025-10-26 22:11:39'),
(1100, 14911, 281, 'roja', 1, NULL, 'normal', '2025-10-26 22:12:11'),
(1102, 14911, 293, 'gol', 1, NULL, 'normal', '2025-10-26 22:12:18'),
(1103, 14911, 159, 'gol', 1, NULL, 'normal', '2025-10-26 22:12:48'),
(1104, 14911, 285, 'roja', 0, 'Doble amarilla', 'normal', '2025-10-26 22:12:59'),
(1105, 14912, 305, 'gol', 0, NULL, 'normal', '2025-10-26 22:27:20'),
(1108, 14912, 124, 'roja', 0, NULL, 'normal', '2025-10-26 22:27:42'),
(1109, 14912, 308, 'gol', 1, NULL, 'normal', '2025-10-26 22:28:58'),
(1110, 14912, 311, 'roja', 0, 'Doble amarilla', 'normal', '2025-10-26 22:29:53'),
(1111, 14913, 335, 'gol', 1, NULL, 'normal', '2025-10-26 22:47:25'),
(1114, 14913, 111, 'gol', 2, NULL, 'normal', '2025-10-26 22:48:29'),
(1115, 14913, 101, 'roja', 3, NULL, 'normal', '2025-10-26 22:48:34'),
(1116, 14913, 337, 'roja', 0, 'Doble amarilla', 'normal', '2025-10-26 22:50:06'),
(1117, 14914, 359, 'gol', 0, NULL, 'normal', '2025-10-26 22:57:14'),
(1120, 14915, 438, 'gol', 0, NULL, 'normal', '2025-10-26 23:23:06'),
(1121, 14915, 63, 'gol', 0, NULL, 'normal', '2025-10-26 23:23:14'),
(1122, 14870, 152, 'gol', 2, NULL, 'normal', '2025-10-26 23:27:09'),
(1125, 14870, 160, 'roja', 2, NULL, 'normal', '2025-10-26 23:27:56'),
(1126, 14870, 147, 'gol', 3, NULL, 'normal', '2025-10-26 23:28:46'),
(1127, 14870, 74, 'gol', 3, NULL, 'normal', '2025-10-26 23:28:53'),
(1128, 14870, 63, 'roja', 3, NULL, 'normal', '2025-10-26 23:28:55'),
(1129, 14870, 65, 'amarilla', 3, NULL, 'normal', '2025-10-26 23:28:57'),
(1130, 14870, 65, 'roja', 5, NULL, 'normal', '2025-10-26 23:30:11'),
(1131, 14870, 71, 'amarilla', 0, NULL, 'normal', '2025-10-26 23:42:05'),
(1132, 14870, 74, 'gol', 0, NULL, 'normal', '2025-10-26 23:42:58'),
(1133, 14873, 222, 'gol', 0, NULL, 'normal', '2025-10-27 01:28:50'),
(1134, 14873, 329, 'gol', 0, NULL, 'normal', '2025-10-27 01:28:54'),
(1135, 14869, 118, 'gol', 0, NULL, 'normal', '2025-10-27 01:54:59'),
(1136, 14869, 90, 'gol', 1, NULL, 'normal', '2025-10-27 01:55:51'),
(1137, 14869, 118, 'gol', 0, NULL, 'normal', '2025-10-27 02:33:32'),
(1138, 14869, 90, 'gol', 2, NULL, 'normal', '2025-10-27 02:35:52'),
(1139, 14873, 227, 'gol', 13, NULL, 'normal', '2025-10-27 13:15:56'),
(1140, 14873, 329, 'gol', 13, NULL, 'normal', '2025-10-27 13:15:59'),
(1141, 14873, 316, 'gol', 2, NULL, 'normal', '2025-10-27 13:31:39'),
(1142, 14871, 174, 'gol', 0, NULL, 'normal', '2025-10-27 13:51:18'),
(1143, 14871, 446, 'gol', 0, NULL, 'normal', '2025-10-27 13:51:20'),
(1146, 14871, 162, 'roja', 0, NULL, 'normal', '2025-10-27 13:51:34'),
(1147, 14871, 176, 'gol', 1, NULL, 'normal', '2025-10-27 15:11:32'),
(1148, 14871, 178, 'amarilla', 1, NULL, 'normal', '2025-10-27 15:11:35'),
(1149, 14871, 442, 'roja', 0, 'Doble amarilla', 'normal', '2025-10-27 15:26:10'),
(1150, 14869, 90, 'gol', 0, NULL, 'normal', '2025-10-27 15:27:40'),
(1151, 14869, 117, 'gol', 14, NULL, 'normal', '2025-10-27 15:57:45'),
(1152, 14870, 153, 'roja', 0, 'Doble amarilla', 'normal', '2025-10-27 19:00:22'),
(1153, 14900, 188, 'gol', 2, NULL, 'normal', '2025-10-27 19:04:44'),
(1154, 14900, 193, 'amarilla', 2, NULL, 'normal', '2025-10-27 19:04:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fases_eliminatorias`
--

CREATE TABLE `fases_eliminatorias` (
  `id` int(11) NOT NULL,
  `formato_id` int(11) NOT NULL,
  `nombre` enum('dieciseisavos','octavos','cuartos','semifinal','final','tercer_puesto') NOT NULL,
  `orden` int(11) NOT NULL,
  `activa` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
(1590, 1, 1, '2025-10-25', 1),
(1591, 1, 2, '2025-11-01', 1),
(1592, 1, 3, '2025-11-08', 1),
(1593, 1, 4, '2025-11-15', 1),
(1594, 1, 5, '2025-11-22', 1),
(1595, 1, 6, '2025-11-29', 1),
(1596, 1, 7, '2025-12-06', 1),
(1597, 1, 8, '2025-12-13', 1),
(1598, 1, 9, '2025-12-20', 1),
(1599, 1, 10, '2025-12-27', 1),
(1600, 1, 11, '2026-01-03', 1),
(1601, 1, 12, '2026-01-10', 1),
(1602, 1, 13, '2026-01-17', 1),
(1603, 1, 14, '2026-01-24', 1),
(1604, 1, 15, '2026-01-31', 1),
(1639, 9, 1, '2025-10-29', 1),
(1640, 9, 2, '2025-11-05', 1),
(1641, 9, 3, '2025-11-12', 1),
(1642, 9, 4, '2025-11-19', 1),
(1643, 9, 5, '2025-11-26', 1),
(1644, 9, 6, '2025-12-03', 1),
(1645, 9, 7, '2025-12-10', 1),
(1646, 9, 8, '2025-12-17', 1),
(1647, 9, 9, '2025-12-24', 1),
(1648, 9, 10, '2025-12-31', 1),
(1649, 9, 11, '2026-01-07', 1),
(1650, 9, 12, '2026-01-14', 1),
(1651, 9, 13, '2026-01-21', 1),
(1652, 9, 14, '2026-01-28', 1),
(1653, 9, 15, '2026-02-04', 1),
(1654, 9, 16, '2026-02-11', 1),
(1655, 9, 17, '2026-02-18', 1);

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
(1, 14890, 282, 1, 'normal', '2025-10-26 15:07:48'),
(2, 14890, 283, 2, 'normal', '2025-10-26 15:07:48'),
(3, 14890, 292, 5, 'normal', '2025-10-26 15:07:48'),
(4, 14890, 290, 7, 'normal', '2025-10-26 15:07:48'),
(5, 14890, 286, 8, 'normal', '2025-10-26 15:07:48'),
(6, 14890, 277, 9, 'normal', '2025-10-26 15:07:48'),
(7, 14890, 289, 10, 'normal', '2025-10-26 15:07:48'),
(8, 14890, 288, 11, 'normal', '2025-10-26 15:07:48'),
(9, 14890, 291, 12, 'normal', '2025-10-26 15:07:48'),
(10, 14890, 281, 25, 'normal', '2025-10-26 15:07:48'),
(11, 14890, 280, 53, 'normal', '2025-10-26 15:07:48'),
(12, 14890, 284, 63, 'normal', '2025-10-26 15:07:48'),
(13, 14890, 285, 10, 'normal', '2025-10-26 15:07:48'),
(14, 14890, 341, 0, 'normal', '2025-10-26 15:07:48'),
(15, 14890, 342, 0, 'normal', '2025-10-26 15:07:48'),
(16, 14890, 343, 0, 'normal', '2025-10-26 15:07:48'),
(17, 14890, 344, 0, 'normal', '2025-10-26 15:07:48'),
(18, 14890, 345, 0, 'normal', '2025-10-26 15:07:48'),
(19, 14890, 346, 0, 'normal', '2025-10-26 15:07:48'),
(20, 14890, 347, 0, 'normal', '2025-10-26 15:07:48'),
(21, 14890, 348, 0, 'normal', '2025-10-26 15:07:48'),
(22, 14890, 349, 0, 'normal', '2025-10-26 15:07:48'),
(23, 14890, 350, 0, 'normal', '2025-10-26 15:07:48'),
(24, 14890, 351, 0, 'normal', '2025-10-26 15:07:48'),
(25, 14890, 352, 0, 'normal', '2025-10-26 15:07:48'),
(26, 14890, 353, 0, 'normal', '2025-10-26 15:07:48'),
(27, 14890, 354, 0, 'normal', '2025-10-26 15:07:48'),
(28, 14890, 355, 0, 'normal', '2025-10-26 15:07:48'),
(29, 14890, 356, 0, 'normal', '2025-10-26 15:07:48'),
(30, 14890, 357, 0, 'normal', '2025-10-26 15:07:48'),
(31, 14890, 358, 0, 'normal', '2025-10-26 15:07:48'),
(32, 14890, 359, 0, 'normal', '2025-10-26 15:07:48'),
(33, 14890, 360, 0, 'normal', '2025-10-26 15:07:48'),
(34, 14890, 361, 0, 'normal', '2025-10-26 15:07:48'),
(35, 14890, 362, 0, 'normal', '2025-10-26 15:07:48'),
(36, 14890, 363, 0, 'normal', '2025-10-26 15:07:48'),
(37, 14909, 228, 0, 'normal', '2025-10-26 17:41:51'),
(38, 14909, 229, 0, 'normal', '2025-10-26 17:41:51'),
(39, 14909, 230, 0, 'normal', '2025-10-26 17:41:51'),
(40, 14909, 231, 0, 'normal', '2025-10-26 17:41:51'),
(41, 14909, 232, 0, 'normal', '2025-10-26 17:41:51'),
(42, 14909, 233, 0, 'normal', '2025-10-26 17:41:51'),
(43, 14909, 234, 0, 'normal', '2025-10-26 17:41:51'),
(44, 14909, 235, 0, 'normal', '2025-10-26 17:41:51'),
(45, 14909, 236, 0, 'normal', '2025-10-26 17:41:51'),
(46, 14909, 237, 0, 'normal', '2025-10-26 17:41:51'),
(47, 14909, 238, 0, 'normal', '2025-10-26 17:41:51'),
(48, 14909, 239, 0, 'normal', '2025-10-26 17:41:51'),
(49, 14909, 240, 0, 'normal', '2025-10-26 17:41:51'),
(50, 14909, 241, 0, 'normal', '2025-10-26 17:41:51'),
(51, 14909, 242, 0, 'normal', '2025-10-26 17:41:51'),
(52, 14909, 243, 0, 'normal', '2025-10-26 17:41:51'),
(53, 14909, 244, 0, 'normal', '2025-10-26 17:41:51'),
(54, 14909, 245, 0, 'normal', '2025-10-26 17:41:51'),
(55, 14909, 246, 0, 'normal', '2025-10-26 17:41:51'),
(56, 14909, 247, 0, 'normal', '2025-10-26 17:41:51'),
(57, 14909, 248, 0, 'normal', '2025-10-26 17:41:51'),
(58, 14909, 249, 0, 'normal', '2025-10-26 17:41:51'),
(59, 14909, 250, 0, 'normal', '2025-10-26 17:41:51'),
(60, 14909, 251, 0, 'normal', '2025-10-26 17:41:51'),
(61, 14909, 252, 0, 'normal', '2025-10-26 17:41:51'),
(62, 14909, 253, 0, 'normal', '2025-10-26 17:41:51'),
(63, 14909, 184, 0, 'normal', '2025-10-26 17:41:51'),
(64, 14909, 185, 0, 'normal', '2025-10-26 17:41:51'),
(65, 14909, 186, 0, 'normal', '2025-10-26 17:41:51'),
(66, 14909, 187, 0, 'normal', '2025-10-26 17:41:51'),
(67, 14909, 188, 0, 'normal', '2025-10-26 17:41:51'),
(68, 14909, 189, 0, 'normal', '2025-10-26 17:41:51'),
(69, 14909, 190, 0, 'normal', '2025-10-26 17:41:51'),
(70, 14909, 191, 0, 'normal', '2025-10-26 17:41:51'),
(71, 14909, 192, 0, 'normal', '2025-10-26 17:41:51'),
(72, 14909, 193, 0, 'normal', '2025-10-26 17:41:51'),
(73, 14909, 194, 0, 'normal', '2025-10-26 17:41:51'),
(74, 14909, 195, 0, 'normal', '2025-10-26 17:41:51'),
(75, 14909, 196, 0, 'normal', '2025-10-26 17:41:51'),
(76, 14909, 197, 0, 'normal', '2025-10-26 17:41:51'),
(77, 14909, 198, 0, 'normal', '2025-10-26 17:41:51'),
(78, 14909, 199, 0, 'normal', '2025-10-26 17:41:51'),
(79, 14909, 200, 0, 'normal', '2025-10-26 17:41:51'),
(80, 14909, 201, 0, 'normal', '2025-10-26 17:41:51'),
(81, 14909, 202, 0, 'normal', '2025-10-26 17:41:51'),
(82, 14909, 203, 0, 'normal', '2025-10-26 17:41:51'),
(83, 14910, 254, 0, 'normal', '2025-10-26 17:43:01'),
(84, 14910, 255, 0, 'normal', '2025-10-26 17:43:01'),
(85, 14910, 256, 0, 'normal', '2025-10-26 17:43:01'),
(86, 14910, 257, 0, 'normal', '2025-10-26 17:43:01'),
(87, 14910, 258, 0, 'normal', '2025-10-26 17:43:01'),
(88, 14910, 259, 0, 'normal', '2025-10-26 17:43:01'),
(89, 14910, 260, 0, 'normal', '2025-10-26 17:43:01'),
(90, 14910, 261, 0, 'normal', '2025-10-26 17:43:01'),
(91, 14910, 262, 0, 'normal', '2025-10-26 17:43:01'),
(92, 14910, 263, 0, 'normal', '2025-10-26 17:43:01'),
(93, 14910, 264, 0, 'normal', '2025-10-26 17:43:01'),
(94, 14910, 265, 0, 'normal', '2025-10-26 17:43:01'),
(95, 14910, 266, 0, 'normal', '2025-10-26 17:43:01'),
(96, 14910, 267, 0, 'normal', '2025-10-26 17:43:01'),
(97, 14910, 268, 0, 'normal', '2025-10-26 17:43:01'),
(98, 14910, 269, 0, 'normal', '2025-10-26 17:43:01'),
(99, 14910, 270, 0, 'normal', '2025-10-26 17:43:01'),
(100, 14910, 271, 0, 'normal', '2025-10-26 17:43:01'),
(101, 14910, 272, 0, 'normal', '2025-10-26 17:43:01'),
(102, 14910, 273, 0, 'normal', '2025-10-26 17:43:01'),
(103, 14910, 274, 0, 'normal', '2025-10-26 17:43:01'),
(104, 14910, 275, 0, 'normal', '2025-10-26 17:43:01'),
(105, 14910, 161, 0, 'normal', '2025-10-26 17:43:01'),
(106, 14910, 162, 0, 'normal', '2025-10-26 17:43:01'),
(107, 14910, 163, 0, 'normal', '2025-10-26 17:43:01'),
(108, 14910, 164, 0, 'normal', '2025-10-26 17:43:01'),
(109, 14910, 165, 0, 'normal', '2025-10-26 17:43:01'),
(110, 14910, 166, 0, 'normal', '2025-10-26 17:43:01'),
(111, 14910, 167, 0, 'normal', '2025-10-26 17:43:01'),
(112, 14910, 168, 0, 'normal', '2025-10-26 17:43:01'),
(113, 14910, 169, 0, 'normal', '2025-10-26 17:43:01'),
(114, 14910, 170, 0, 'normal', '2025-10-26 17:43:01'),
(115, 14910, 171, 0, 'normal', '2025-10-26 17:43:01'),
(116, 14910, 172, 0, 'normal', '2025-10-26 17:43:01'),
(117, 14910, 173, 0, 'normal', '2025-10-26 17:43:01'),
(118, 14910, 174, 0, 'normal', '2025-10-26 17:43:01'),
(119, 14910, 175, 0, 'normal', '2025-10-26 17:43:01'),
(120, 14910, 176, 0, 'normal', '2025-10-26 17:43:01'),
(121, 14910, 177, 0, 'normal', '2025-10-26 17:43:01'),
(122, 14910, 178, 0, 'normal', '2025-10-26 17:43:01'),
(123, 14910, 179, 0, 'normal', '2025-10-26 17:43:01'),
(124, 14910, 180, 0, 'normal', '2025-10-26 17:43:01'),
(125, 14910, 181, 0, 'normal', '2025-10-26 17:43:01'),
(126, 14910, 182, 0, 'normal', '2025-10-26 17:43:01'),
(127, 14910, 183, 0, 'normal', '2025-10-26 17:43:01'),
(128, 14911, 282, 1, 'normal', '2025-10-26 22:12:59'),
(129, 14911, 283, 23, 'normal', '2025-10-26 22:12:59'),
(130, 14911, 292, 2, 'normal', '2025-10-26 22:12:59'),
(131, 14911, 290, 4, 'normal', '2025-10-26 22:12:59'),
(132, 14911, 286, 5, 'normal', '2025-10-26 22:12:59'),
(133, 14911, 277, 3, 'normal', '2025-10-26 22:12:59'),
(134, 14911, 289, 6, 'normal', '2025-10-26 22:12:59'),
(135, 14911, 288, 7, 'normal', '2025-10-26 22:12:59'),
(136, 14911, 291, 8, 'normal', '2025-10-26 22:12:59'),
(137, 14911, 281, 9, 'normal', '2025-10-26 22:12:59'),
(138, 14911, 280, 10, 'normal', '2025-10-26 22:12:59'),
(139, 14911, 284, 11, 'normal', '2025-10-26 22:12:59'),
(140, 14911, 285, 12, 'normal', '2025-10-26 22:12:59'),
(141, 14911, 287, 13, 'normal', '2025-10-26 22:12:59'),
(142, 14911, 276, 14, 'normal', '2025-10-26 22:12:59'),
(143, 14911, 137, 0, 'normal', '2025-10-26 22:12:59'),
(144, 14911, 138, 0, 'normal', '2025-10-26 22:12:59'),
(145, 14911, 139, 0, 'normal', '2025-10-26 22:12:59'),
(146, 14911, 140, 0, 'normal', '2025-10-26 22:12:59'),
(147, 14911, 141, 0, 'normal', '2025-10-26 22:12:59'),
(148, 14911, 142, 0, 'normal', '2025-10-26 22:12:59'),
(149, 14911, 143, 0, 'normal', '2025-10-26 22:12:59'),
(150, 14911, 144, 0, 'normal', '2025-10-26 22:12:59'),
(151, 14911, 145, 0, 'normal', '2025-10-26 22:12:59'),
(152, 14911, 146, 0, 'normal', '2025-10-26 22:12:59'),
(153, 14911, 147, 0, 'normal', '2025-10-26 22:12:59'),
(154, 14911, 148, 0, 'normal', '2025-10-26 22:12:59'),
(155, 14911, 149, 0, 'normal', '2025-10-26 22:12:59'),
(156, 14911, 150, 0, 'normal', '2025-10-26 22:12:59'),
(157, 14911, 151, 0, 'normal', '2025-10-26 22:12:59'),
(158, 14911, 152, 0, 'normal', '2025-10-26 22:12:59'),
(159, 14911, 153, 0, 'normal', '2025-10-26 22:12:59'),
(160, 14911, 154, 0, 'normal', '2025-10-26 22:12:59'),
(161, 14911, 155, 0, 'normal', '2025-10-26 22:12:59'),
(162, 14911, 156, 0, 'normal', '2025-10-26 22:12:59'),
(163, 14911, 157, 0, 'normal', '2025-10-26 22:12:59'),
(164, 14911, 158, 0, 'normal', '2025-10-26 22:12:59'),
(165, 14911, 159, 0, 'normal', '2025-10-26 22:12:59'),
(166, 14911, 160, 0, 'normal', '2025-10-26 22:12:59'),
(167, 14912, 294, 0, 'normal', '2025-10-26 22:29:53'),
(168, 14912, 295, 0, 'normal', '2025-10-26 22:29:53'),
(169, 14912, 296, 0, 'normal', '2025-10-26 22:29:53'),
(170, 14912, 297, 0, 'normal', '2025-10-26 22:29:53'),
(171, 14912, 298, 0, 'normal', '2025-10-26 22:29:53'),
(172, 14912, 299, 0, 'normal', '2025-10-26 22:29:53'),
(173, 14912, 300, 0, 'normal', '2025-10-26 22:29:53'),
(174, 14912, 301, 0, 'normal', '2025-10-26 22:29:53'),
(175, 14912, 302, 0, 'normal', '2025-10-26 22:29:53'),
(176, 14912, 303, 0, 'normal', '2025-10-26 22:29:53'),
(177, 14912, 304, 0, 'normal', '2025-10-26 22:29:53'),
(178, 14912, 305, 0, 'normal', '2025-10-26 22:29:53'),
(179, 14912, 306, 0, 'normal', '2025-10-26 22:29:53'),
(180, 14912, 307, 0, 'normal', '2025-10-26 22:29:53'),
(181, 14912, 308, 0, 'normal', '2025-10-26 22:29:53'),
(182, 14912, 309, 0, 'normal', '2025-10-26 22:29:53'),
(183, 14912, 310, 0, 'normal', '2025-10-26 22:29:53'),
(184, 14912, 311, 0, 'normal', '2025-10-26 22:29:53'),
(185, 14912, 312, 0, 'normal', '2025-10-26 22:29:53'),
(186, 14912, 313, 0, 'normal', '2025-10-26 22:29:53'),
(187, 14912, 116, 0, 'normal', '2025-10-26 22:29:53'),
(188, 14912, 117, 0, 'normal', '2025-10-26 22:29:53'),
(189, 14912, 118, 0, 'normal', '2025-10-26 22:29:53'),
(190, 14912, 119, 0, 'normal', '2025-10-26 22:29:53'),
(191, 14912, 120, 0, 'normal', '2025-10-26 22:29:53'),
(192, 14912, 121, 0, 'normal', '2025-10-26 22:29:53'),
(193, 14912, 122, 0, 'normal', '2025-10-26 22:29:53'),
(194, 14912, 123, 0, 'normal', '2025-10-26 22:29:53'),
(195, 14912, 124, 0, 'normal', '2025-10-26 22:29:53'),
(196, 14912, 125, 0, 'normal', '2025-10-26 22:29:53'),
(197, 14912, 126, 0, 'normal', '2025-10-26 22:29:53'),
(198, 14912, 127, 0, 'normal', '2025-10-26 22:29:53'),
(199, 14912, 128, 0, 'normal', '2025-10-26 22:29:53'),
(200, 14912, 129, 0, 'normal', '2025-10-26 22:29:53'),
(201, 14912, 130, 0, 'normal', '2025-10-26 22:29:53'),
(202, 14912, 131, 0, 'normal', '2025-10-26 22:29:53'),
(203, 14912, 132, 0, 'normal', '2025-10-26 22:29:53'),
(204, 14912, 133, 0, 'normal', '2025-10-26 22:29:53'),
(205, 14912, 134, 0, 'normal', '2025-10-26 22:29:53'),
(206, 14912, 135, 0, 'normal', '2025-10-26 22:29:53'),
(207, 14912, 136, 0, 'normal', '2025-10-26 22:29:53'),
(208, 14913, 317, 5, 'normal', '2025-10-26 22:50:06'),
(209, 14913, 331, 6, 'normal', '2025-10-26 22:50:06'),
(210, 14913, 319, 4, 'normal', '2025-10-26 22:50:06'),
(211, 14913, 339, 7, 'normal', '2025-10-26 22:50:06'),
(212, 14913, 315, 12, 'normal', '2025-10-26 22:50:06'),
(213, 14913, 321, 11, 'normal', '2025-10-26 22:50:06'),
(214, 14913, 329, 10, 'normal', '2025-10-26 22:50:06'),
(215, 14913, 328, 9, 'normal', '2025-10-26 22:50:06'),
(216, 14913, 316, 8, 'normal', '2025-10-26 22:50:06'),
(217, 14913, 340, 7, 'normal', '2025-10-26 22:50:06'),
(218, 14913, 335, 1, 'normal', '2025-10-26 22:50:06'),
(219, 14913, 93, 0, 'normal', '2025-10-26 22:50:06'),
(220, 14913, 94, 0, 'normal', '2025-10-26 22:50:06'),
(221, 14913, 95, 0, 'normal', '2025-10-26 22:50:06'),
(222, 14913, 96, 0, 'normal', '2025-10-26 22:50:06'),
(223, 14913, 97, 0, 'normal', '2025-10-26 22:50:06'),
(224, 14913, 98, 0, 'normal', '2025-10-26 22:50:06'),
(225, 14913, 99, 0, 'normal', '2025-10-26 22:50:06'),
(226, 14913, 100, 0, 'normal', '2025-10-26 22:50:06'),
(227, 14913, 101, 0, 'normal', '2025-10-26 22:50:06'),
(228, 14913, 102, 0, 'normal', '2025-10-26 22:50:06'),
(229, 14913, 103, 0, 'normal', '2025-10-26 22:50:06'),
(230, 14913, 104, 0, 'normal', '2025-10-26 22:50:06'),
(231, 14913, 105, 0, 'normal', '2025-10-26 22:50:06'),
(232, 14913, 106, 0, 'normal', '2025-10-26 22:50:06'),
(233, 14913, 107, 0, 'normal', '2025-10-26 22:50:06'),
(234, 14913, 108, 0, 'normal', '2025-10-26 22:50:06'),
(235, 14913, 109, 0, 'normal', '2025-10-26 22:50:06'),
(236, 14913, 110, 0, 'normal', '2025-10-26 22:50:06'),
(237, 14913, 111, 0, 'normal', '2025-10-26 22:50:06'),
(238, 14913, 112, 0, 'normal', '2025-10-26 22:50:06'),
(239, 14913, 113, 0, 'normal', '2025-10-26 22:50:06'),
(240, 14913, 114, 0, 'normal', '2025-10-26 22:50:06'),
(241, 14913, 115, 0, 'normal', '2025-10-26 22:50:06'),
(242, 14914, 341, 0, 'normal', '2025-10-26 23:12:22'),
(243, 14914, 342, 0, 'normal', '2025-10-26 23:12:22'),
(244, 14914, 343, 0, 'normal', '2025-10-26 23:12:22'),
(245, 14914, 344, 0, 'normal', '2025-10-26 23:12:22'),
(246, 14914, 345, 0, 'normal', '2025-10-26 23:12:22'),
(247, 14914, 346, 0, 'normal', '2025-10-26 23:12:22'),
(248, 14914, 347, 0, 'normal', '2025-10-26 23:12:22'),
(249, 14914, 348, 0, 'normal', '2025-10-26 23:12:22'),
(250, 14914, 349, 0, 'normal', '2025-10-26 23:12:22'),
(251, 14914, 350, 0, 'normal', '2025-10-26 23:12:22'),
(252, 14914, 351, 0, 'normal', '2025-10-26 23:12:22'),
(253, 14914, 352, 0, 'normal', '2025-10-26 23:12:22'),
(254, 14914, 353, 0, 'normal', '2025-10-26 23:12:22'),
(255, 14914, 354, 0, 'normal', '2025-10-26 23:12:22'),
(256, 14914, 355, 0, 'normal', '2025-10-26 23:12:22'),
(257, 14914, 356, 0, 'normal', '2025-10-26 23:12:22'),
(258, 14914, 357, 0, 'normal', '2025-10-26 23:12:22'),
(259, 14914, 358, 0, 'normal', '2025-10-26 23:12:22'),
(260, 14914, 359, 0, 'normal', '2025-10-26 23:12:22'),
(261, 14914, 360, 0, 'normal', '2025-10-26 23:12:22'),
(262, 14914, 361, 0, 'normal', '2025-10-26 23:12:22'),
(263, 14914, 362, 0, 'normal', '2025-10-26 23:12:22'),
(264, 14914, 363, 0, 'normal', '2025-10-26 23:12:22'),
(265, 14914, 76, 0, 'normal', '2025-10-26 23:12:22'),
(266, 14914, 77, 0, 'normal', '2025-10-26 23:12:22'),
(267, 14914, 78, 0, 'normal', '2025-10-26 23:12:22'),
(268, 14914, 79, 0, 'normal', '2025-10-26 23:12:22'),
(269, 14914, 80, 0, 'normal', '2025-10-26 23:12:22'),
(270, 14914, 81, 0, 'normal', '2025-10-26 23:12:22'),
(271, 14914, 82, 0, 'normal', '2025-10-26 23:12:22'),
(272, 14914, 83, 0, 'normal', '2025-10-26 23:12:22'),
(273, 14914, 84, 0, 'normal', '2025-10-26 23:12:22'),
(274, 14914, 85, 0, 'normal', '2025-10-26 23:12:22'),
(275, 14914, 86, 0, 'normal', '2025-10-26 23:12:22'),
(276, 14914, 87, 0, 'normal', '2025-10-26 23:12:22'),
(277, 14914, 88, 0, 'normal', '2025-10-26 23:12:22'),
(278, 14914, 89, 0, 'normal', '2025-10-26 23:12:22'),
(279, 14914, 90, 0, 'normal', '2025-10-26 23:12:22'),
(280, 14914, 91, 0, 'normal', '2025-10-26 23:12:22'),
(281, 14914, 92, 0, 'normal', '2025-10-26 23:12:22'),
(282, 14915, 420, 0, 'normal', '2025-10-26 23:24:16'),
(283, 14915, 421, 0, 'normal', '2025-10-26 23:24:16'),
(284, 14915, 422, 0, 'normal', '2025-10-26 23:24:16'),
(285, 14915, 423, 0, 'normal', '2025-10-26 23:24:16'),
(286, 14915, 424, 0, 'normal', '2025-10-26 23:24:16'),
(287, 14915, 425, 0, 'normal', '2025-10-26 23:24:16'),
(288, 14915, 426, 0, 'normal', '2025-10-26 23:24:16'),
(289, 14915, 427, 0, 'normal', '2025-10-26 23:24:16'),
(290, 14915, 428, 0, 'normal', '2025-10-26 23:24:16'),
(291, 14915, 429, 0, 'normal', '2025-10-26 23:24:16'),
(292, 14915, 430, 0, 'normal', '2025-10-26 23:24:16'),
(293, 14915, 431, 0, 'normal', '2025-10-26 23:24:16'),
(294, 14915, 432, 0, 'normal', '2025-10-26 23:24:16'),
(295, 14915, 433, 0, 'normal', '2025-10-26 23:24:16'),
(296, 14915, 434, 0, 'normal', '2025-10-26 23:24:16'),
(297, 14915, 435, 0, 'normal', '2025-10-26 23:24:16'),
(298, 14915, 436, 0, 'normal', '2025-10-26 23:24:16'),
(299, 14915, 437, 0, 'normal', '2025-10-26 23:24:16'),
(300, 14915, 438, 0, 'normal', '2025-10-26 23:24:16'),
(301, 14915, 439, 0, 'normal', '2025-10-26 23:24:16'),
(302, 14915, 440, 0, 'normal', '2025-10-26 23:24:16'),
(303, 14915, 441, 0, 'normal', '2025-10-26 23:24:16'),
(304, 14915, 442, 0, 'normal', '2025-10-26 23:24:16'),
(305, 14915, 443, 0, 'normal', '2025-10-26 23:24:16'),
(306, 14915, 444, 0, 'normal', '2025-10-26 23:24:16'),
(307, 14915, 445, 0, 'normal', '2025-10-26 23:24:16'),
(308, 14915, 446, 0, 'normal', '2025-10-26 23:24:16'),
(309, 14915, 58, 0, 'normal', '2025-10-26 23:24:16'),
(310, 14915, 59, 0, 'normal', '2025-10-26 23:24:16'),
(311, 14915, 60, 0, 'normal', '2025-10-26 23:24:16'),
(312, 14915, 61, 0, 'normal', '2025-10-26 23:24:16'),
(313, 14915, 62, 0, 'normal', '2025-10-26 23:24:16'),
(314, 14915, 63, 0, 'normal', '2025-10-26 23:24:16'),
(315, 14915, 64, 0, 'normal', '2025-10-26 23:24:16'),
(316, 14915, 65, 0, 'normal', '2025-10-26 23:24:16'),
(317, 14915, 67, 0, 'normal', '2025-10-26 23:24:16'),
(318, 14915, 68, 0, 'normal', '2025-10-26 23:24:16'),
(319, 14915, 69, 0, 'normal', '2025-10-26 23:24:16'),
(320, 14915, 70, 0, 'normal', '2025-10-26 23:24:16'),
(321, 14915, 71, 0, 'normal', '2025-10-26 23:24:16'),
(322, 14915, 72, 0, 'normal', '2025-10-26 23:24:16'),
(323, 14915, 73, 0, 'normal', '2025-10-26 23:24:16'),
(324, 14915, 74, 0, 'normal', '2025-10-26 23:24:16'),
(325, 14915, 75, 0, 'normal', '2025-10-26 23:24:16'),
(326, 14915, 447, 0, 'normal', '2025-10-26 23:24:16'),
(327, 14871, 161, 0, 'normal', '2025-10-27 15:26:10'),
(328, 14871, 162, 0, 'normal', '2025-10-27 15:26:10'),
(329, 14871, 163, 0, 'normal', '2025-10-27 15:26:10'),
(330, 14871, 164, 0, 'normal', '2025-10-27 15:26:10'),
(331, 14871, 165, 0, 'normal', '2025-10-27 15:26:10'),
(332, 14871, 166, 0, 'normal', '2025-10-27 15:26:10'),
(333, 14871, 167, 0, 'normal', '2025-10-27 15:26:10'),
(334, 14871, 168, 0, 'normal', '2025-10-27 15:26:10'),
(335, 14871, 169, 0, 'normal', '2025-10-27 15:26:10'),
(336, 14871, 170, 0, 'normal', '2025-10-27 15:26:10'),
(337, 14871, 171, 0, 'normal', '2025-10-27 15:26:10'),
(338, 14871, 172, 0, 'normal', '2025-10-27 15:26:10'),
(339, 14871, 173, 0, 'normal', '2025-10-27 15:26:10'),
(340, 14871, 174, 0, 'normal', '2025-10-27 15:26:10'),
(341, 14871, 175, 0, 'normal', '2025-10-27 15:26:10'),
(342, 14871, 176, 0, 'normal', '2025-10-27 15:26:10'),
(343, 14871, 177, 0, 'normal', '2025-10-27 15:26:10'),
(344, 14871, 178, 0, 'normal', '2025-10-27 15:26:10'),
(345, 14871, 179, 0, 'normal', '2025-10-27 15:26:10'),
(346, 14871, 180, 0, 'normal', '2025-10-27 15:26:10'),
(347, 14871, 181, 0, 'normal', '2025-10-27 15:26:10'),
(348, 14871, 182, 0, 'normal', '2025-10-27 15:26:10'),
(349, 14871, 183, 0, 'normal', '2025-10-27 15:26:10'),
(350, 14871, 420, 0, 'normal', '2025-10-27 15:26:10'),
(351, 14871, 421, 0, 'normal', '2025-10-27 15:26:10'),
(352, 14871, 422, 0, 'normal', '2025-10-27 15:26:10'),
(353, 14871, 423, 0, 'normal', '2025-10-27 15:26:10'),
(354, 14871, 424, 0, 'normal', '2025-10-27 15:26:10'),
(355, 14871, 425, 0, 'normal', '2025-10-27 15:26:10'),
(356, 14871, 426, 0, 'normal', '2025-10-27 15:26:10'),
(357, 14871, 427, 0, 'normal', '2025-10-27 15:26:10'),
(358, 14871, 428, 0, 'normal', '2025-10-27 15:26:10'),
(359, 14871, 429, 0, 'normal', '2025-10-27 15:26:10'),
(360, 14871, 430, 0, 'normal', '2025-10-27 15:26:10'),
(361, 14871, 431, 0, 'normal', '2025-10-27 15:26:10'),
(362, 14871, 432, 0, 'normal', '2025-10-27 15:26:10'),
(363, 14871, 433, 0, 'normal', '2025-10-27 15:26:10'),
(364, 14871, 434, 0, 'normal', '2025-10-27 15:26:10'),
(365, 14871, 435, 0, 'normal', '2025-10-27 15:26:10'),
(366, 14871, 436, 0, 'normal', '2025-10-27 15:26:10'),
(367, 14871, 437, 0, 'normal', '2025-10-27 15:26:10'),
(368, 14871, 438, 0, 'normal', '2025-10-27 15:26:10'),
(369, 14871, 439, 0, 'normal', '2025-10-27 15:26:10'),
(370, 14871, 440, 0, 'normal', '2025-10-27 15:26:10'),
(371, 14871, 441, 0, 'normal', '2025-10-27 15:26:10'),
(372, 14871, 442, 0, 'normal', '2025-10-27 15:26:10'),
(373, 14871, 443, 0, 'normal', '2025-10-27 15:26:10'),
(374, 14871, 444, 0, 'normal', '2025-10-27 15:26:10'),
(375, 14871, 445, 0, 'normal', '2025-10-27 15:26:10'),
(376, 14871, 446, 0, 'normal', '2025-10-27 15:26:10'),
(377, 14869, 116, 0, 'normal', '2025-10-27 19:00:12'),
(378, 14869, 117, 0, 'normal', '2025-10-27 19:00:12'),
(379, 14869, 118, 0, 'normal', '2025-10-27 19:00:12'),
(380, 14869, 119, 0, 'normal', '2025-10-27 19:00:12'),
(381, 14869, 120, 0, 'normal', '2025-10-27 19:00:12'),
(382, 14869, 121, 0, 'normal', '2025-10-27 19:00:12'),
(383, 14869, 122, 0, 'normal', '2025-10-27 19:00:12'),
(384, 14869, 123, 0, 'normal', '2025-10-27 19:00:12'),
(385, 14869, 124, 0, 'normal', '2025-10-27 19:00:12'),
(386, 14869, 125, 0, 'normal', '2025-10-27 19:00:12'),
(387, 14869, 126, 0, 'normal', '2025-10-27 19:00:12'),
(388, 14869, 127, 0, 'normal', '2025-10-27 19:00:12'),
(389, 14869, 128, 0, 'normal', '2025-10-27 19:00:12'),
(390, 14869, 129, 0, 'normal', '2025-10-27 19:00:12'),
(391, 14869, 130, 0, 'normal', '2025-10-27 19:00:12'),
(392, 14869, 131, 0, 'normal', '2025-10-27 19:00:12'),
(393, 14869, 132, 0, 'normal', '2025-10-27 19:00:12'),
(394, 14869, 133, 0, 'normal', '2025-10-27 19:00:12'),
(395, 14869, 134, 0, 'normal', '2025-10-27 19:00:12'),
(396, 14869, 135, 0, 'normal', '2025-10-27 19:00:12'),
(397, 14869, 136, 0, 'normal', '2025-10-27 19:00:12'),
(398, 14869, 76, 0, 'normal', '2025-10-27 19:00:12'),
(399, 14869, 77, 0, 'normal', '2025-10-27 19:00:12'),
(400, 14869, 78, 0, 'normal', '2025-10-27 19:00:12'),
(401, 14869, 79, 0, 'normal', '2025-10-27 19:00:12'),
(402, 14869, 80, 0, 'normal', '2025-10-27 19:00:12'),
(403, 14869, 81, 0, 'normal', '2025-10-27 19:00:12'),
(404, 14869, 82, 0, 'normal', '2025-10-27 19:00:12'),
(405, 14869, 83, 0, 'normal', '2025-10-27 19:00:12'),
(406, 14869, 84, 0, 'normal', '2025-10-27 19:00:12'),
(407, 14869, 85, 0, 'normal', '2025-10-27 19:00:12'),
(408, 14869, 86, 0, 'normal', '2025-10-27 19:00:12'),
(409, 14869, 87, 0, 'normal', '2025-10-27 19:00:12'),
(410, 14869, 88, 0, 'normal', '2025-10-27 19:00:12'),
(411, 14869, 89, 0, 'normal', '2025-10-27 19:00:12'),
(412, 14869, 90, 0, 'normal', '2025-10-27 19:00:12'),
(413, 14869, 91, 0, 'normal', '2025-10-27 19:00:12'),
(414, 14869, 92, 0, 'normal', '2025-10-27 19:00:12'),
(415, 14870, 137, 0, 'normal', '2025-10-27 19:00:22'),
(416, 14870, 138, 0, 'normal', '2025-10-27 19:00:22'),
(417, 14870, 139, 0, 'normal', '2025-10-27 19:00:22'),
(418, 14870, 140, 0, 'normal', '2025-10-27 19:00:22'),
(419, 14870, 141, 0, 'normal', '2025-10-27 19:00:22'),
(420, 14870, 142, 0, 'normal', '2025-10-27 19:00:22'),
(421, 14870, 143, 0, 'normal', '2025-10-27 19:00:22'),
(422, 14870, 144, 0, 'normal', '2025-10-27 19:00:22'),
(423, 14870, 145, 0, 'normal', '2025-10-27 19:00:22'),
(424, 14870, 146, 0, 'normal', '2025-10-27 19:00:22'),
(425, 14870, 147, 0, 'normal', '2025-10-27 19:00:22'),
(426, 14870, 148, 0, 'normal', '2025-10-27 19:00:22'),
(427, 14870, 149, 0, 'normal', '2025-10-27 19:00:22'),
(428, 14870, 150, 0, 'normal', '2025-10-27 19:00:22'),
(429, 14870, 151, 0, 'normal', '2025-10-27 19:00:22'),
(430, 14870, 152, 0, 'normal', '2025-10-27 19:00:22'),
(431, 14870, 153, 0, 'normal', '2025-10-27 19:00:22'),
(432, 14870, 154, 0, 'normal', '2025-10-27 19:00:22'),
(433, 14870, 155, 0, 'normal', '2025-10-27 19:00:22'),
(434, 14870, 156, 0, 'normal', '2025-10-27 19:00:22'),
(435, 14870, 157, 0, 'normal', '2025-10-27 19:00:22'),
(436, 14870, 158, 0, 'normal', '2025-10-27 19:00:22'),
(437, 14870, 159, 0, 'normal', '2025-10-27 19:00:22'),
(438, 14870, 160, 0, 'normal', '2025-10-27 19:00:22'),
(439, 14870, 58, 0, 'normal', '2025-10-27 19:00:22'),
(440, 14870, 59, 0, 'normal', '2025-10-27 19:00:22'),
(441, 14870, 60, 0, 'normal', '2025-10-27 19:00:22'),
(442, 14870, 61, 0, 'normal', '2025-10-27 19:00:22'),
(443, 14870, 62, 0, 'normal', '2025-10-27 19:00:22'),
(444, 14870, 63, 0, 'normal', '2025-10-27 19:00:22'),
(445, 14870, 64, 0, 'normal', '2025-10-27 19:00:22'),
(446, 14870, 65, 0, 'normal', '2025-10-27 19:00:22'),
(447, 14870, 67, 0, 'normal', '2025-10-27 19:00:22'),
(448, 14870, 68, 0, 'normal', '2025-10-27 19:00:22'),
(449, 14870, 69, 0, 'normal', '2025-10-27 19:00:22'),
(450, 14870, 70, 0, 'normal', '2025-10-27 19:00:22'),
(451, 14870, 71, 0, 'normal', '2025-10-27 19:00:22'),
(452, 14870, 72, 0, 'normal', '2025-10-27 19:00:22'),
(453, 14870, 73, 0, 'normal', '2025-10-27 19:00:22'),
(454, 14870, 74, 0, 'normal', '2025-10-27 19:00:22'),
(455, 14870, 75, 0, 'normal', '2025-10-27 19:00:22'),
(456, 14870, 447, 0, 'normal', '2025-10-27 19:00:22'),
(457, 14872, 184, 0, 'normal', '2025-10-27 19:00:46'),
(458, 14872, 185, 0, 'normal', '2025-10-27 19:00:46'),
(459, 14872, 186, 0, 'normal', '2025-10-27 19:00:46'),
(460, 14872, 187, 0, 'normal', '2025-10-27 19:00:46'),
(461, 14872, 188, 0, 'normal', '2025-10-27 19:00:46'),
(462, 14872, 189, 0, 'normal', '2025-10-27 19:00:46'),
(463, 14872, 190, 0, 'normal', '2025-10-27 19:00:46'),
(464, 14872, 191, 0, 'normal', '2025-10-27 19:00:46'),
(465, 14872, 192, 0, 'normal', '2025-10-27 19:00:46'),
(466, 14872, 193, 0, 'normal', '2025-10-27 19:00:46'),
(467, 14872, 194, 0, 'normal', '2025-10-27 19:00:46'),
(468, 14872, 195, 0, 'normal', '2025-10-27 19:00:46'),
(469, 14872, 196, 0, 'normal', '2025-10-27 19:00:46'),
(470, 14872, 197, 0, 'normal', '2025-10-27 19:00:46'),
(471, 14872, 198, 0, 'normal', '2025-10-27 19:00:46'),
(472, 14872, 199, 0, 'normal', '2025-10-27 19:00:46'),
(473, 14872, 200, 0, 'normal', '2025-10-27 19:00:46'),
(474, 14872, 201, 0, 'normal', '2025-10-27 19:00:46'),
(475, 14872, 202, 0, 'normal', '2025-10-27 19:00:46'),
(476, 14872, 203, 0, 'normal', '2025-10-27 19:00:46'),
(477, 14872, 341, 0, 'normal', '2025-10-27 19:00:46'),
(478, 14872, 342, 0, 'normal', '2025-10-27 19:00:46'),
(479, 14872, 343, 0, 'normal', '2025-10-27 19:00:46'),
(480, 14872, 344, 0, 'normal', '2025-10-27 19:00:46'),
(481, 14872, 345, 0, 'normal', '2025-10-27 19:00:46'),
(482, 14872, 346, 0, 'normal', '2025-10-27 19:00:46'),
(483, 14872, 347, 0, 'normal', '2025-10-27 19:00:46'),
(484, 14872, 348, 0, 'normal', '2025-10-27 19:00:46'),
(485, 14872, 349, 0, 'normal', '2025-10-27 19:00:46'),
(486, 14872, 350, 0, 'normal', '2025-10-27 19:00:46'),
(487, 14872, 351, 0, 'normal', '2025-10-27 19:00:46'),
(488, 14872, 352, 0, 'normal', '2025-10-27 19:00:46'),
(489, 14872, 353, 0, 'normal', '2025-10-27 19:00:46'),
(490, 14872, 354, 0, 'normal', '2025-10-27 19:00:46'),
(491, 14872, 355, 0, 'normal', '2025-10-27 19:00:46'),
(492, 14872, 356, 0, 'normal', '2025-10-27 19:00:46'),
(493, 14872, 357, 0, 'normal', '2025-10-27 19:00:46'),
(494, 14872, 358, 0, 'normal', '2025-10-27 19:00:46'),
(495, 14872, 359, 0, 'normal', '2025-10-27 19:00:46'),
(496, 14872, 360, 0, 'normal', '2025-10-27 19:00:46'),
(497, 14872, 361, 0, 'normal', '2025-10-27 19:00:46'),
(498, 14872, 362, 0, 'normal', '2025-10-27 19:00:46'),
(499, 14872, 363, 0, 'normal', '2025-10-27 19:00:46'),
(500, 14873, 204, 0, 'normal', '2025-10-27 19:00:59'),
(501, 14873, 205, 0, 'normal', '2025-10-27 19:00:59'),
(502, 14873, 206, 0, 'normal', '2025-10-27 19:00:59'),
(503, 14873, 207, 0, 'normal', '2025-10-27 19:00:59'),
(504, 14873, 208, 0, 'normal', '2025-10-27 19:00:59'),
(505, 14873, 209, 0, 'normal', '2025-10-27 19:00:59'),
(506, 14873, 210, 0, 'normal', '2025-10-27 19:00:59'),
(507, 14873, 211, 0, 'normal', '2025-10-27 19:00:59'),
(508, 14873, 212, 0, 'normal', '2025-10-27 19:00:59'),
(509, 14873, 213, 0, 'normal', '2025-10-27 19:00:59'),
(510, 14873, 214, 0, 'normal', '2025-10-27 19:00:59'),
(511, 14873, 215, 0, 'normal', '2025-10-27 19:00:59'),
(512, 14873, 216, 0, 'normal', '2025-10-27 19:00:59'),
(513, 14873, 217, 0, 'normal', '2025-10-27 19:00:59'),
(514, 14873, 218, 0, 'normal', '2025-10-27 19:00:59'),
(515, 14873, 219, 0, 'normal', '2025-10-27 19:00:59'),
(516, 14873, 220, 0, 'normal', '2025-10-27 19:00:59'),
(517, 14873, 221, 0, 'normal', '2025-10-27 19:00:59'),
(518, 14873, 222, 0, 'normal', '2025-10-27 19:00:59'),
(519, 14873, 223, 0, 'normal', '2025-10-27 19:00:59'),
(520, 14873, 224, 0, 'normal', '2025-10-27 19:00:59'),
(521, 14873, 225, 0, 'normal', '2025-10-27 19:00:59'),
(522, 14873, 226, 0, 'normal', '2025-10-27 19:00:59'),
(523, 14873, 227, 0, 'normal', '2025-10-27 19:00:59'),
(524, 14873, 314, 0, 'normal', '2025-10-27 19:00:59'),
(525, 14873, 315, 0, 'normal', '2025-10-27 19:00:59'),
(526, 14873, 316, 0, 'normal', '2025-10-27 19:00:59'),
(527, 14873, 317, 0, 'normal', '2025-10-27 19:00:59'),
(528, 14873, 318, 0, 'normal', '2025-10-27 19:00:59'),
(529, 14873, 319, 0, 'normal', '2025-10-27 19:00:59'),
(530, 14873, 320, 0, 'normal', '2025-10-27 19:00:59'),
(531, 14873, 321, 0, 'normal', '2025-10-27 19:00:59'),
(532, 14873, 322, 0, 'normal', '2025-10-27 19:00:59'),
(533, 14873, 323, 0, 'normal', '2025-10-27 19:00:59'),
(534, 14873, 324, 0, 'normal', '2025-10-27 19:00:59'),
(535, 14873, 325, 0, 'normal', '2025-10-27 19:00:59'),
(536, 14873, 326, 0, 'normal', '2025-10-27 19:00:59'),
(537, 14873, 327, 0, 'normal', '2025-10-27 19:00:59'),
(538, 14873, 328, 0, 'normal', '2025-10-27 19:00:59'),
(539, 14873, 329, 0, 'normal', '2025-10-27 19:00:59'),
(540, 14873, 330, 0, 'normal', '2025-10-27 19:00:59'),
(541, 14873, 331, 0, 'normal', '2025-10-27 19:00:59'),
(542, 14873, 332, 0, 'normal', '2025-10-27 19:00:59'),
(543, 14873, 333, 0, 'normal', '2025-10-27 19:00:59'),
(544, 14873, 334, 0, 'normal', '2025-10-27 19:00:59'),
(545, 14873, 335, 0, 'normal', '2025-10-27 19:00:59'),
(546, 14873, 336, 0, 'normal', '2025-10-27 19:00:59'),
(547, 14873, 337, 0, 'normal', '2025-10-27 19:00:59'),
(548, 14873, 338, 0, 'normal', '2025-10-27 19:00:59'),
(549, 14873, 339, 0, 'normal', '2025-10-27 19:00:59'),
(550, 14873, 340, 0, 'normal', '2025-10-27 19:00:59'),
(551, 14900, 184, 0, 'normal', '2025-10-27 20:58:27'),
(552, 14900, 185, 0, 'normal', '2025-10-27 20:58:27'),
(553, 14900, 186, 0, 'normal', '2025-10-27 20:58:27'),
(554, 14900, 187, 0, 'normal', '2025-10-27 20:58:27'),
(555, 14900, 188, 0, 'normal', '2025-10-27 20:58:27'),
(556, 14900, 189, 0, 'normal', '2025-10-27 20:58:27'),
(557, 14900, 190, 0, 'normal', '2025-10-27 20:58:27'),
(558, 14900, 191, 0, 'normal', '2025-10-27 20:58:27'),
(559, 14900, 192, 0, 'normal', '2025-10-27 20:58:27'),
(560, 14900, 193, 0, 'normal', '2025-10-27 20:58:27'),
(561, 14900, 194, 0, 'normal', '2025-10-27 20:58:27'),
(562, 14900, 195, 0, 'normal', '2025-10-27 20:58:27'),
(563, 14900, 196, 0, 'normal', '2025-10-27 20:58:27'),
(564, 14900, 197, 0, 'normal', '2025-10-27 20:58:27'),
(565, 14900, 198, 0, 'normal', '2025-10-27 20:58:27'),
(566, 14900, 199, 0, 'normal', '2025-10-27 20:58:27'),
(567, 14900, 200, 0, 'normal', '2025-10-27 20:58:27'),
(568, 14900, 201, 0, 'normal', '2025-10-27 20:58:27'),
(569, 14900, 202, 0, 'normal', '2025-10-27 20:58:27'),
(570, 14900, 203, 0, 'normal', '2025-10-27 20:58:27'),
(571, 14900, 364, 0, 'normal', '2025-10-27 20:58:27'),
(572, 14900, 365, 0, 'normal', '2025-10-27 20:58:27'),
(573, 14900, 366, 0, 'normal', '2025-10-27 20:58:27'),
(574, 14900, 367, 0, 'normal', '2025-10-27 20:58:27'),
(575, 14900, 368, 0, 'normal', '2025-10-27 20:58:27'),
(576, 14900, 369, 0, 'normal', '2025-10-27 20:58:27'),
(577, 14900, 370, 0, 'normal', '2025-10-27 20:58:27'),
(578, 14900, 371, 0, 'normal', '2025-10-27 20:58:27'),
(579, 14900, 372, 0, 'normal', '2025-10-27 20:58:27'),
(580, 14900, 373, 0, 'normal', '2025-10-27 20:58:27'),
(581, 14900, 374, 0, 'normal', '2025-10-27 20:58:27'),
(582, 14900, 375, 0, 'normal', '2025-10-27 20:58:27'),
(583, 14900, 376, 0, 'normal', '2025-10-27 20:58:27'),
(584, 14900, 377, 0, 'normal', '2025-10-27 20:58:27'),
(585, 14900, 378, 0, 'normal', '2025-10-27 20:58:27'),
(586, 14900, 379, 0, 'normal', '2025-10-27 20:58:27'),
(587, 14900, 380, 0, 'normal', '2025-10-27 20:58:27'),
(588, 14900, 381, 0, 'normal', '2025-10-27 20:58:27');

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
(1, 45, 14867, 1, '2025-10-23 22:23:57'),
(2, 48, 14874, 16, '2025-10-23 22:25:14'),
(3, 47, 14874, 1, '2025-10-23 22:25:14'),
(4, 51, 14874, 1, '2025-10-23 22:25:14'),
(5, 50, 14883, 1, '2025-10-23 22:27:02'),
(6, 47, 14883, 2, '2025-10-23 22:27:02'),
(7, 48, 14883, 17, '2025-10-23 22:27:02'),
(8, 52, 14883, 1, '2025-10-23 22:27:02'),
(9, 53, 14883, 1, '2025-10-23 22:27:02'),
(10, 43, 14863, 1, '2025-10-24 22:15:43'),
(11, 55, 14863, 1, '2025-10-24 22:15:43'),
(12, 46, 14863, 1, '2025-10-24 22:15:43'),
(13, 56, 14863, 1, '2025-10-24 22:15:43'),
(14, 57, 14862, 1, '2025-10-24 22:17:10'),
(15, 40, 14862, 1, '2025-10-24 22:17:10'),
(16, 41, 14862, 1, '2025-10-24 22:17:10'),
(17, 49, 14864, 1, '2025-10-24 22:26:37'),
(18, 48, 14891, 18, '2025-10-24 22:43:14'),
(19, 47, 14891, 3, '2025-10-24 22:43:14'),
(20, 54, 14891, 1, '2025-10-24 22:43:14'),
(21, 58, 14891, 1, '2025-10-24 22:43:14'),
(22, 43, 14884, 2, '2025-10-24 22:44:17'),
(23, 59, 14884, 1, '2025-10-24 22:44:17'),
(24, 60, 14889, 1, '2025-10-24 22:45:25'),
(25, 48, 14874, 19, '2025-10-24 22:59:14'),
(26, 47, 14874, 4, '2025-10-24 22:59:14'),
(27, 61, 14868, 1, '2025-10-24 23:01:35'),
(28, 42, 14860, 1, '2025-10-24 23:31:14'),
(29, 65, 14892, 1, '2025-10-25 00:00:56'),
(30, 64, 14892, 1, '2025-10-25 00:00:56'),
(31, 62, 14896, 1, '2025-10-25 00:01:30'),
(32, 63, 14896, 1, '2025-10-25 00:01:30'),
(33, 68, 14909, 1, '2025-10-26 17:41:52'),
(34, 67, 14909, 1, '2025-10-26 17:41:52'),
(35, 48, 14912, 20, '2025-10-26 22:29:54'),
(36, 47, 14912, 5, '2025-10-26 22:29:54'),
(37, 66, 14914, 1, '2025-10-26 23:12:22'),
(38, 76, 14869, 1, '2025-10-27 19:00:13'),
(39, 73, 14870, 1, '2025-10-27 19:00:22'),
(40, 70, 14873, 1, '2025-10-27 19:01:00'),
(41, 77, 14873, 1, '2025-10-27 19:01:00'),
(42, 71, 14900, 1, '2025-10-27 20:58:27');

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
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `partidos`
--

INSERT INTO `partidos` (`id`, `fecha_id`, `equipo_local_id`, `equipo_visitante_id`, `cancha_id`, `fecha_partido`, `hora_partido`, `goles_local`, `goles_visitante`, `estado`, `minuto_actual`, `minuto_periodo`, `segundos_transcurridos`, `tiempo_actual`, `iniciado_at`, `finalizado_at`, `observaciones`) VALUES
(14844, 1590, 10, 9, 38, '2025-10-25', '13:30:00', 1, 0, 'finalizado', 0, 0, 0, 'finalizado', NULL, '2025-10-23 19:33:12', ''),
(14845, 1590, 14, 8, 38, '2025-10-25', '14:40:00', 0, 0, 'finalizado', 0, 0, 0, 'finalizado', NULL, '2025-10-22 13:59:20', ''),
(14846, 1590, 15, 3, 38, '2025-10-25', '15:50:00', 2, 0, 'finalizado', 0, 0, 0, 'finalizado', NULL, '2025-10-22 14:18:56', ''),
(14847, 1590, 6, 2, 38, '2025-10-25', '17:00:00', 0, 5, 'finalizado', 1, 0, 62, 'finalizado', '2025-10-22 14:21:07', '2025-10-22 16:47:46', ''),
(14848, 1590, 13, 56, 38, '2025-10-25', '18:10:00', 0, 0, 'finalizado', 0, 0, 24, 'segundo_tiempo', NULL, '2025-10-23 21:10:54', ''),
(14849, 1590, 5, 11, 26, '2025-10-25', '13:30:00', 1, 1, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 19:37:26', ''),
(14850, 1590, 7, 12, 26, '2025-10-25', '14:40:00', 1, 1, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 21:10:40', ''),
(14851, 1590, 4, 1, 26, '2025-10-25', '15:50:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 21:11:01', ''),
(14852, 1591, 14, 9, 17, '2025-11-01', '13:30:00', 1, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 22:13:18', ''),
(14853, 1591, 15, 10, 17, '2025-11-01', '14:40:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 21:12:15', ''),
(14854, 1591, 6, 8, 17, '2025-11-01', '15:50:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 21:12:28', ''),
(14855, 1591, 13, 3, 17, '2025-11-01', '17:00:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 21:12:40', ''),
(14856, 1591, 5, 2, 17, '2025-11-01', '18:10:00', 0, 1, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 21:14:59', ''),
(14857, 1591, 7, 56, 30, '2025-11-01', '13:30:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 21:12:09', ''),
(14858, 1591, 4, 11, 30, '2025-11-01', '14:40:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 21:12:22', ''),
(14859, 1591, 1, 12, 30, '2025-11-01', '15:50:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 21:12:33', ''),
(14860, 1592, 15, 9, 17, '2025-11-08', '13:30:00', 1, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 23:31:14', ''),
(14861, 1592, 6, 14, 17, '2025-11-08', '14:40:00', 1, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 22:40:27', ''),
(14862, 1592, 13, 10, 17, '2025-11-08', '15:50:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 22:17:10', ''),
(14863, 1592, 5, 8, 17, '2025-11-08', '17:00:00', 1, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 22:15:43', ''),
(14864, 1592, 7, 3, 17, '2025-11-08', '18:10:00', 1, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 22:26:36', ''),
(14865, 1592, 4, 2, 18, '2025-11-08', '13:30:00', 0, 1, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 21:56:34', ''),
(14866, 1592, 1, 56, 18, '2025-11-08', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14867, 1592, 12, 11, 18, '2025-11-08', '15:50:00', 0, 1, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 22:23:57', ''),
(14868, 1593, 6, 9, 17, '2025-11-15', '12:30:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 23:01:35', ''),
(14869, 1593, 13, 15, 17, '2025-11-15', '13:40:00', 3, 3, 'finalizado', 16, -14, 991, 'finalizado', NULL, '2025-10-27 19:00:12', ''),
(14870, 1593, 5, 14, 17, '2025-11-15', '14:50:00', 2, 2, 'finalizado', 0, -30, 1, 'finalizado', NULL, '2025-10-27 19:00:22', ''),
(14871, 1593, 7, 10, 17, '2025-11-15', '16:00:00', 2, 1, 'finalizado', 7, 0, 428, 'finalizado', NULL, '2025-10-27 15:26:10', ''),
(14872, 1593, 4, 8, 17, '2025-11-15', '17:10:00', 0, 0, 'finalizado', 0, -30, 13, 'finalizado', NULL, '2025-10-27 19:00:46', ''),
(14873, 1593, 1, 3, 17, '2025-11-15', '18:20:00', 2, 3, 'finalizado', 0, -30, 1, 'finalizado', NULL, '2025-10-27 19:00:59', ''),
(14874, 1593, 12, 2, 17, '2025-11-15', '19:30:00', 0, 3, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 22:59:14', ''),
(14875, 1593, 11, 56, 18, '2025-11-15', '12:30:00', 1, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 23:23:28', ''),
(14876, 1594, 13, 9, 32, '2025-11-22', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14877, 1594, 5, 6, 32, '2025-11-22', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14878, 1594, 7, 15, 32, '2025-11-22', '15:50:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 23:54:23', ''),
(14879, 1594, 4, 14, 32, '2025-11-22', '17:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14880, 1594, 1, 10, 32, '2025-11-22', '18:10:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14881, 1594, 12, 8, 36, '2025-11-22', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14882, 1594, 11, 3, 36, '2025-11-22', '14:40:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 23:52:18', ''),
(14883, 1594, 56, 2, 36, '2025-11-22', '15:50:00', 0, 1, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-23 22:27:02', ''),
(14884, 1595, 5, 9, 17, '2025-11-29', '13:30:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 22:44:17', ''),
(14885, 1595, 7, 13, 17, '2025-11-29', '14:40:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 23:54:56', ''),
(14886, 1595, 4, 6, 17, '2025-11-29', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14887, 1595, 1, 15, 17, '2025-11-29', '17:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14888, 1595, 12, 14, 17, '2025-11-29', '18:10:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14889, 1595, 11, 10, 18, '2025-11-29', '13:30:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 22:45:24', ''),
(14890, 1595, 56, 8, 18, '2025-11-29', '14:40:00', 1, 1, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-26 15:07:48', ''),
(14891, 1595, 2, 3, 18, '2025-11-29', '15:50:00', 2, 1, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-24 22:43:14', ''),
(14892, 1596, 7, 9, 18, '2025-12-06', '13:30:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-25 00:00:56', ''),
(14893, 1596, 4, 5, 18, '2025-12-06', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14894, 1596, 1, 13, 18, '2025-12-06', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14895, 1596, 12, 6, 18, '2025-12-06', '17:00:00', 1, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-25 01:58:37', ''),
(14896, 1596, 11, 15, 18, '2025-12-06', '18:10:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-25 00:01:30', ''),
(14897, 1596, 56, 14, 32, '2025-12-06', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14898, 1596, 2, 10, 32, '2025-12-06', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14899, 1596, 3, 8, 32, '2025-12-06', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14900, 1597, 4, 9, 32, '2025-12-13', '13:30:00', 1, 0, 'finalizado', 30, 0, 1834, 'finalizado', NULL, '2025-10-27 20:58:27', ''),
(14901, 1597, 1, 7, 32, '2025-12-13', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14902, 1597, 12, 5, 32, '2025-12-13', '15:50:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-10-25 01:59:41', ''),
(14903, 1597, 11, 13, 32, '2025-12-13', '17:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14904, 1597, 56, 6, 32, '2025-12-13', '18:10:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14905, 1597, 2, 15, 36, '2025-12-13', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14906, 1597, 3, 14, 36, '2025-12-13', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14907, 1597, 8, 10, 36, '2025-12-13', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14908, 1598, 1, 9, 18, '2025-12-20', '13:30:00', 1, 1, 'finalizado', 0, 0, 0, 'finalizado', NULL, '2025-10-26 17:18:52', ''),
(14909, 1598, 12, 4, 18, '2025-12-20', '14:40:00', 1, 0, 'finalizado', 0, 0, 0, 'finalizado', NULL, '2025-10-26 17:41:51', ''),
(14910, 1598, 11, 7, 18, '2025-12-20', '15:50:00', 0, 0, 'finalizado', 0, 0, 0, 'finalizado', NULL, '2025-10-26 17:43:01', ''),
(14911, 1598, 56, 5, 18, '2025-12-20', '17:00:00', 2, 1, 'finalizado', 0, 0, 0, 'finalizado', NULL, '2025-10-26 22:12:59', ''),
(14912, 1598, 2, 13, 18, '2025-12-20', '18:10:00', 2, 0, 'finalizado', 0, 0, 0, 'finalizado', NULL, '2025-10-26 22:29:53', ''),
(14913, 1598, 3, 6, 32, '2025-12-20', '13:30:00', 1, 1, 'finalizado', 0, 0, 0, 'finalizado', NULL, '2025-10-26 22:50:06', ''),
(14914, 1598, 8, 15, 32, '2025-12-20', '14:40:00', 1, 0, 'finalizado', 0, 0, 0, 'finalizado', NULL, '2025-10-26 23:12:22', ''),
(14915, 1598, 10, 14, 32, '2025-12-20', '15:50:00', 1, 1, 'finalizado', 0, 0, 0, 'finalizado', NULL, '2025-10-26 23:24:16', ''),
(14916, 1599, 12, 9, 32, '2025-12-27', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14917, 1599, 11, 1, 32, '2025-12-27', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14918, 1599, 56, 4, 32, '2025-12-27', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14919, 1599, 2, 7, 32, '2025-12-27', '17:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14920, 1599, 3, 5, 32, '2025-12-27', '18:10:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14921, 1599, 8, 13, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14922, 1599, 10, 6, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14923, 1599, 14, 15, NULL, '2025-12-27', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14924, 1600, 11, 9, 17, '2026-01-03', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14925, 1600, 56, 12, 17, '2026-01-03', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14926, 1600, 2, 1, 17, '2026-01-03', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14927, 1600, 3, 4, 17, '2026-01-03', '17:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14928, 1600, 8, 7, 17, '2026-01-03', '18:10:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14929, 1600, 10, 5, 18, '2026-01-03', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14930, 1600, 14, 13, 18, '2026-01-03', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14931, 1600, 15, 6, 18, '2026-01-03', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14932, 1601, 56, 9, 18, '2026-01-10', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14933, 1601, 2, 11, 18, '2026-01-10', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14934, 1601, 3, 12, 18, '2026-01-10', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14935, 1601, 8, 1, 18, '2026-01-10', '17:00:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14936, 1601, 10, 4, 18, '2026-01-10', '18:10:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14937, 1601, 14, 7, 32, '2026-01-10', '13:30:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14938, 1601, 15, 5, 32, '2026-01-10', '14:40:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14939, 1601, 6, 13, 32, '2026-01-10', '15:50:00', 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14940, 1602, 2, 9, NULL, '2026-01-17', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14941, 1602, 3, 56, NULL, '2026-01-17', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14942, 1602, 8, 11, NULL, '2026-01-17', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14943, 1602, 10, 12, NULL, '2026-01-17', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14944, 1602, 14, 1, NULL, '2026-01-17', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14945, 1602, 15, 4, NULL, '2026-01-17', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14946, 1602, 6, 7, NULL, '2026-01-17', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14947, 1602, 13, 5, NULL, '2026-01-17', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14948, 1603, 3, 9, NULL, '2026-01-24', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14949, 1603, 8, 2, NULL, '2026-01-24', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14950, 1603, 10, 56, NULL, '2026-01-24', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14951, 1603, 14, 11, NULL, '2026-01-24', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14952, 1603, 15, 12, NULL, '2026-01-24', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14953, 1603, 6, 1, NULL, '2026-01-24', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14954, 1603, 13, 4, NULL, '2026-01-24', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14955, 1603, 5, 7, NULL, '2026-01-24', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14956, 1604, 8, 9, NULL, '2026-01-31', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14957, 1604, 10, 3, NULL, '2026-01-31', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14958, 1604, 14, 2, NULL, '2026-01-31', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14959, 1604, 15, 56, NULL, '2026-01-31', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14960, 1604, 6, 11, NULL, '2026-01-31', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14961, 1604, 13, 12, NULL, '2026-01-31', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14962, 1604, 5, 1, NULL, '2026-01-31', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(14963, 1604, 7, 4, NULL, '2026-01-31', NULL, 0, 0, '', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15236, 1639, 89, 103, NULL, '2025-10-29', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15237, 1639, 90, 104, NULL, '2025-10-29', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15238, 1639, 91, 102, NULL, '2025-10-29', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15239, 1639, 92, 101, NULL, '2025-10-29', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15240, 1639, 93, 99, NULL, '2025-10-29', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15241, 1639, 94, 100, NULL, '2025-10-29', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15242, 1639, 95, 98, NULL, '2025-10-29', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15243, 1639, 96, 97, NULL, '2025-10-29', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15244, 1640, 88, 90, NULL, '2025-11-05', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15245, 1640, 103, 91, NULL, '2025-11-05', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15246, 1640, 104, 92, NULL, '2025-11-05', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15247, 1640, 102, 93, NULL, '2025-11-05', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15248, 1640, 101, 94, NULL, '2025-11-05', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15249, 1640, 99, 95, NULL, '2025-11-05', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15250, 1640, 100, 96, NULL, '2025-11-05', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15251, 1640, 98, 97, NULL, '2025-11-05', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15252, 1641, 91, 89, NULL, '2025-11-12', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15253, 1641, 92, 88, NULL, '2025-11-12', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15254, 1641, 93, 103, NULL, '2025-11-12', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15255, 1641, 94, 104, NULL, '2025-11-12', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15256, 1641, 95, 102, NULL, '2025-11-12', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15257, 1641, 96, 101, NULL, '2025-11-12', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15258, 1641, 97, 99, NULL, '2025-11-12', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15259, 1641, 98, 100, NULL, '2025-11-12', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15260, 1642, 90, 92, NULL, '2025-11-19', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15261, 1642, 89, 93, NULL, '2025-11-19', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15262, 1642, 88, 94, NULL, '2025-11-19', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15263, 1642, 103, 95, NULL, '2025-11-19', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15264, 1642, 104, 96, NULL, '2025-11-19', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15265, 1642, 102, 97, NULL, '2025-11-19', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15266, 1642, 101, 98, NULL, '2025-11-19', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15267, 1642, 99, 100, NULL, '2025-11-19', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15268, 1643, 93, 91, NULL, '2025-11-26', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15269, 1643, 94, 90, NULL, '2025-11-26', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15270, 1643, 95, 89, NULL, '2025-11-26', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15271, 1643, 96, 88, NULL, '2025-11-26', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15272, 1643, 97, 103, NULL, '2025-11-26', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15273, 1643, 98, 104, NULL, '2025-11-26', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15274, 1643, 100, 102, NULL, '2025-11-26', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15275, 1643, 99, 101, NULL, '2025-11-26', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15276, 1644, 92, 94, NULL, '2025-12-03', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15277, 1644, 91, 95, NULL, '2025-12-03', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15278, 1644, 90, 96, NULL, '2025-12-03', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15279, 1644, 89, 97, NULL, '2025-12-03', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15280, 1644, 88, 98, NULL, '2025-12-03', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15281, 1644, 103, 100, NULL, '2025-12-03', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15282, 1644, 104, 99, NULL, '2025-12-03', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15283, 1644, 102, 101, NULL, '2025-12-03', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15284, 1645, 95, 93, NULL, '2025-12-10', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15285, 1645, 96, 92, NULL, '2025-12-10', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15286, 1645, 97, 91, NULL, '2025-12-10', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15287, 1645, 98, 90, NULL, '2025-12-10', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15288, 1645, 100, 89, NULL, '2025-12-10', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15289, 1645, 99, 88, NULL, '2025-12-10', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15290, 1645, 101, 103, NULL, '2025-12-10', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15291, 1645, 102, 104, NULL, '2025-12-10', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15292, 1646, 94, 96, NULL, '2025-12-17', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15293, 1646, 93, 97, NULL, '2025-12-17', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15294, 1646, 92, 98, NULL, '2025-12-17', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15295, 1646, 91, 100, NULL, '2025-12-17', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15296, 1646, 90, 99, NULL, '2025-12-17', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15297, 1646, 89, 101, NULL, '2025-12-17', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15298, 1646, 88, 102, NULL, '2025-12-17', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15299, 1646, 103, 104, NULL, '2025-12-17', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15300, 1647, 97, 95, NULL, '2025-12-24', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15301, 1647, 98, 94, NULL, '2025-12-24', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15302, 1647, 100, 93, NULL, '2025-12-24', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15303, 1647, 99, 92, NULL, '2025-12-24', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15304, 1647, 101, 91, NULL, '2025-12-24', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15305, 1647, 102, 90, NULL, '2025-12-24', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15306, 1647, 104, 89, NULL, '2025-12-24', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15307, 1647, 103, 88, NULL, '2025-12-24', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15308, 1648, 96, 98, NULL, '2025-12-31', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15309, 1648, 95, 100, NULL, '2025-12-31', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15310, 1648, 94, 99, NULL, '2025-12-31', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15311, 1648, 93, 101, NULL, '2025-12-31', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15312, 1648, 92, 102, NULL, '2025-12-31', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15313, 1648, 91, 104, NULL, '2025-12-31', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15314, 1648, 90, 103, NULL, '2025-12-31', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15315, 1648, 89, 88, NULL, '2025-12-31', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15316, 1649, 100, 97, NULL, '2026-01-07', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15317, 1649, 99, 96, NULL, '2026-01-07', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15318, 1649, 101, 95, NULL, '2026-01-07', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15319, 1649, 102, 94, NULL, '2026-01-07', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15320, 1649, 104, 93, NULL, '2026-01-07', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15321, 1649, 103, 92, NULL, '2026-01-07', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15322, 1649, 88, 91, NULL, '2026-01-07', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15323, 1649, 89, 90, NULL, '2026-01-07', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15324, 1650, 98, 99, NULL, '2026-01-14', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15325, 1650, 97, 101, NULL, '2026-01-14', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15326, 1650, 96, 102, NULL, '2026-01-14', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15327, 1650, 95, 104, NULL, '2026-01-14', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15328, 1650, 94, 103, NULL, '2026-01-14', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15329, 1650, 93, 88, NULL, '2026-01-14', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15330, 1650, 92, 89, NULL, '2026-01-14', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15331, 1650, 91, 90, NULL, '2026-01-14', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15332, 1651, 101, 100, NULL, '2026-01-21', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15333, 1651, 102, 98, NULL, '2026-01-21', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15334, 1651, 104, 97, NULL, '2026-01-21', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15335, 1651, 103, 96, NULL, '2026-01-21', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15336, 1651, 88, 95, NULL, '2026-01-21', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15337, 1651, 89, 94, NULL, '2026-01-21', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15338, 1651, 90, 93, NULL, '2026-01-21', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15339, 1651, 91, 92, NULL, '2026-01-21', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15340, 1652, 99, 102, NULL, '2026-01-28', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15341, 1652, 100, 104, NULL, '2026-01-28', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15342, 1652, 98, 103, NULL, '2026-01-28', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15343, 1652, 97, 88, NULL, '2026-01-28', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15344, 1652, 96, 89, NULL, '2026-01-28', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15345, 1652, 95, 90, NULL, '2026-01-28', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15346, 1652, 94, 91, NULL, '2026-01-28', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15347, 1652, 93, 92, NULL, '2026-01-28', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15348, 1653, 104, 101, NULL, '2026-02-04', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15349, 1653, 103, 99, NULL, '2026-02-04', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15350, 1653, 88, 100, NULL, '2026-02-04', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15351, 1653, 89, 98, NULL, '2026-02-04', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15352, 1653, 90, 97, NULL, '2026-02-04', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15353, 1653, 91, 96, NULL, '2026-02-04', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15354, 1653, 92, 95, NULL, '2026-02-04', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15355, 1653, 93, 94, NULL, '2026-02-04', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15356, 1654, 102, 103, NULL, '2026-02-11', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15357, 1654, 101, 88, NULL, '2026-02-11', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15358, 1654, 99, 89, NULL, '2026-02-11', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15359, 1654, 100, 90, NULL, '2026-02-11', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15360, 1654, 98, 91, NULL, '2026-02-11', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15361, 1654, 97, 92, NULL, '2026-02-11', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15362, 1654, 96, 93, NULL, '2026-02-11', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15363, 1654, 95, 94, NULL, '2026-02-11', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15364, 1655, 88, 104, NULL, '2026-02-18', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15365, 1655, 89, 102, NULL, '2026-02-18', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15366, 1655, 90, 101, NULL, '2026-02-18', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15367, 1655, 91, 99, NULL, '2026-02-18', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15368, 1655, 92, 100, NULL, '2026-02-18', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15369, 1655, 93, 98, NULL, '2026-02-18', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15370, 1655, 94, 97, NULL, '2026-02-18', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL),
(15371, 1655, 95, 96, NULL, '2026-02-18', NULL, 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL);

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
  `finalizado_at` timestamp NULL DEFAULT NULL
) ;

--
-- Volcado de datos para la tabla `partidos_zona`
--

INSERT INTO `partidos_zona` (`id`, `zona_id`, `equipo_local_id`, `equipo_visitante_id`, `partido_id`, `fecha_numero`, `fecha_partido`, `hora_partido`, `cancha_id`, `goles_local`, `goles_visitante`, `estado`, `finalizado_at`) VALUES
(41, 16, 12, 14, NULL, 1, '2025-10-28', '15:00:00', 17, NULL, NULL, 'programado', NULL),
(42, 16, 56, 14, NULL, 2, '2025-11-04', '15:00:00', 17, NULL, NULL, 'programado', NULL),
(43, 16, 56, 12, NULL, 3, '2025-11-11', '15:00:00', 17, NULL, NULL, 'programado', NULL),
(44, 17, 7, 9, NULL, 1, '2025-10-28', '15:00:00', 17, NULL, NULL, 'programado', NULL),
(45, 17, 4, 9, NULL, 2, '2025-11-04', '15:00:00', 17, NULL, NULL, 'programado', NULL),
(46, 17, 4, 7, NULL, 3, '2025-11-11', '15:00:00', 17, NULL, NULL, 'programado', NULL),
(47, 18, 2, 13, NULL, 1, '2025-10-28', '15:00:00', 17, NULL, NULL, 'programado', NULL),
(48, 18, 15, 13, NULL, 2, '2025-11-04', '15:00:00', 17, NULL, NULL, 'programado', NULL),
(49, 18, 15, 2, NULL, 3, '2025-11-11', '15:00:00', 17, NULL, NULL, 'programado', NULL),
(50, 19, 11, 5, NULL, 1, '2025-10-28', '15:00:00', 17, NULL, NULL, 'programado', NULL),
(51, 19, 1, 5, NULL, 2, '2025-11-04', '15:00:00', 17, NULL, NULL, 'programado', NULL),
(52, 19, 1, 11, NULL, 3, '2025-11-11', '15:00:00', 17, NULL, NULL, 'programado', NULL);

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
(40, 439, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-22'),
(41, 441, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-22'),
(42, 90, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-22'),
(43, 139, 'roja_directa', 2, 2, 'Tarjeta roja directa', 0, '2025-10-23'),
(44, 260, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-23'),
(45, 273, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-23'),
(46, 152, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-23'),
(47, 297, 'administrativa', 1000, 5, 'Escupió al referí', 1, '2025-10-23'),
(48, 296, 'administrativa', 100, 20, '', 1, '2025-10-23'),
(49, 162, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-23'),
(50, 279, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-23'),
(51, 305, 'amarillas_acumuladas', 1, 1, '4 amarillas acumuladas en el campeonato', 0, '2025-10-23'),
(52, 310, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-23'),
(53, 305, 'amarillas_acumuladas', 1, 1, '4 amarillas acumuladas en el campeonato', 0, '2025-10-23'),
(54, 305, 'amarillas_acumuladas', 1, 1, '4 amarillas acumuladas en el campeonato', 0, '2025-10-24'),
(55, 145, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-24'),
(56, 156, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-24'),
(57, 117, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-24'),
(58, 320, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-24'),
(59, 152, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-24'),
(60, 266, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-24'),
(61, 99, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-24'),
(62, 256, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-24'),
(63, 260, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-24'),
(64, 168, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-24'),
(65, 161, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-24'),
(66, 77, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-25'),
(67, 241, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-25'),
(68, 231, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-25'),
(69, 282, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-10-26'),
(70, 213, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-26'),
(71, 365, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-26'),
(72, 285, 'doble_amarilla', 1, 0, 'Doble amarilla en partido', 1, '2025-10-26'),
(73, 153, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-26'),
(74, 281, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-10-26'),
(75, 311, 'doble_amarilla', 1, 0, 'Doble amarilla en partido', 1, '2025-10-26'),
(76, 124, 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-10-26'),
(77, 337, 'doble_amarilla', 1, 1, 'Doble amarilla en partido', 0, '2025-10-26'),
(78, 101, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-10-26'),
(79, 442, 'doble_amarilla', 1, 0, 'Doble amarilla en partido', 1, '2025-10-27'),
(80, 162, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-10-27'),
(81, 153, 'doble_amarilla', 1, 0, 'Doble amarilla en partido', 1, '2025-10-27'),
(82, 160, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-10-27'),
(83, 63, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-10-27'),
(84, 65, 'roja_directa', 1, 0, 'Tarjeta roja directa', 1, '2025-10-27');

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
(16, 1, 'Zona A', 1, 1),
(17, 1, 'Zona B', 2, 1),
(18, 1, 'Zona C', 3, 1),
(19, 1, 'Zona D', 4, 1);

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
  ADD KEY `campeonato_id` (`campeonato_id`);

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
-- Indices de la tabla `equipos_zonas`
--
ALTER TABLE `equipos_zonas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_equipo_zona` (`zona_id`,`equipo_id`),
  ADD KEY `equipo_id` (`equipo_id`);

--
-- Indices de la tabla `eventos_partido`
--
ALTER TABLE `eventos_partido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partido_id` (`partido_id`),
  ADD KEY `jugador_id` (`jugador_id`),
  ADD KEY `idx_eventos_partido_tipo` (`tipo_partido`);

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
  ADD KEY `idx_jugador_equipo` (`equipo_id`);

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
  ADD KEY `idx_partidos_estado` (`estado`,`fecha_partido`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `campeonatos_formato`
--
ALTER TABLE `campeonatos_formato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `canchas`
--
ALTER TABLE `canchas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `codigos_cancha`
--
ALTER TABLE `codigos_cancha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1525;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=105;

--
-- AUTO_INCREMENT de la tabla `equipos_zonas`
--
ALTER TABLE `equipos_zonas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `eventos_partido`
--
ALTER TABLE `eventos_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1155;

--
-- AUTO_INCREMENT de la tabla `fases_eliminatorias`
--
ALTER TABLE `fases_eliminatorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `fechas`
--
ALTER TABLE `fechas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1656;

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
-- AUTO_INCREMENT de la tabla `jugadores_partido`
--
ALTER TABLE `jugadores_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=589;

--
-- AUTO_INCREMENT de la tabla `log_sanciones`
--
ALTER TABLE `log_sanciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT de la tabla `partidos`
--
ALTER TABLE `partidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15372;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `zonas`
--
ALTER TABLE `zonas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_sanciones_completas`
--
DROP TABLE IF EXISTS `v_sanciones_completas`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u959527289_Nuevo`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_sanciones_completas`  AS SELECT `s`.`id` AS `id`, `s`.`jugador_id` AS `jugador_id`, `j`.`apellido_nombre` AS `apellido_nombre`, `j`.`dni` AS `dni`, `e`.`id` AS `equipo_id`, `e`.`nombre` AS `equipo`, `c`.`nombre` AS `categoria`, `s`.`tipo` AS `tipo`, CASE `s`.`tipo` WHEN 'amarillas_acumuladas' THEN '4 Amarillas' WHEN 'doble_amarilla' THEN 'Doble Amarilla' WHEN 'roja_directa' THEN 'Roja Directa' WHEN 'administrativa' THEN 'Administrativa' END AS `tipo_descripcion`, `s`.`partidos_suspension` AS `partidos_suspension`, `s`.`partidos_cumplidos` AS `partidos_cumplidos`, `s`.`partidos_suspension`- `s`.`partidos_cumplidos` AS `fechas_restantes`, `s`.`descripcion` AS `descripcion`, `s`.`activa` AS `activa`, `s`.`fecha_sancion` AS `fecha_sancion`, (select count(0) from `log_sanciones` where `log_sanciones`.`sancion_id` = `s`.`id`) AS `registros_cumplimiento` FROM (((`sanciones` `s` join `jugadores` `j` on(`s`.`jugador_id` = `j`.`id`)) join `equipos` `e` on(`j`.`equipo_id` = `e`.`id`)) join `categorias` `c` on(`e`.`categoria_id` = `c`.`id`)) ;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `campeonatos_formato`
--
ALTER TABLE `campeonatos_formato`
  ADD CONSTRAINT `campeonatos_formato_ibfk_1` FOREIGN KEY (`campeonato_id`) REFERENCES `campeonatos` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `partidos_ibfk_4` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`);

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
