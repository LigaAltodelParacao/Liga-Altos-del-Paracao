-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 13-11-2025 a las 14:11:05
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
  `es_torneo_nocturno` tinyint(1) DEFAULT 0 COMMENT 'Indica si es un torneo nocturno (por zonas)',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `campeonatos`
--

INSERT INTO `campeonatos` (`id`, `nombre`, `descripcion`, `fecha_inicio`, `fecha_fin`, `activo`, `es_torneo_nocturno`, `created_at`) VALUES
(13, 'Nocturno 2026', '', '2025-11-14', NULL, 1, 0, '2025-11-13 13:51:32');

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
(35, 13, 'Femenino', '', 1);

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
(306, 35, 'Pichi FC Fem', NULL, '#007bff', '', 1, '2025-11-13 13:53:22'),
(307, 35, 'Pikis', NULL, '#007bff', '', 1, '2025-11-13 13:53:30'),
(308, 35, 'Monas Team Fem', NULL, '#007bff', '', 1, '2025-11-13 13:53:36'),
(309, 35, 'La Ternera Fem', NULL, '#007bff', '', 1, '2025-11-13 13:53:42'),
(310, 35, 'EPAP Femenino', NULL, '#007bff', '', 1, '2025-11-13 13:53:48'),
(311, 35, 'The Yegüas FC', NULL, '#007bff', '', 1, '2025-11-13 13:53:54');

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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `eventos_partido`
--

CREATE TABLE `eventos_partido` (
  `id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL,
  `campeonato_id` int(11) DEFAULT NULL COMMENT 'ID del campeonato (calculado desde partido)',
  `jugador_id` int(11) NOT NULL,
  `tipo_evento` enum('gol','amarilla','roja') NOT NULL,
  `minuto` int(11) NOT NULL,
  `observaciones` varchar(255) DEFAULT NULL,
  `tipo_partido` varchar(20) DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `eventos_partido`
--
DELIMITER $$
CREATE TRIGGER `trg_eventos_partido_campeonato` BEFORE INSERT ON `eventos_partido` FOR EACH ROW BEGIN
    DECLARE v_campeonato_id INT;
    DECLARE v_tipo_torneo VARCHAR(20);
    
    -- Obtener campeonato_id y tipo_torneo del partido
    SELECT 
        COALESCE(
            -- Si es partido normal, obtener desde fecha -> categoria -> campeonato
            (SELECT c.campeonato_id 
             FROM partidos p 
             JOIN fechas f ON p.fecha_id = f.id 
             JOIN categorias c ON f.categoria_id = c.id 
             WHERE p.id = NEW.partido_id AND p.tipo_torneo = 'normal'),
            -- Si es partido de zona, obtener desde zona -> formato -> campeonato
            (SELECT cf.campeonato_id 
             FROM partidos p 
             JOIN zonas z ON p.zona_id = z.id 
             JOIN campeonatos_formato cf ON z.formato_id = cf.id 
             WHERE p.id = NEW.partido_id AND p.tipo_torneo = 'zona'),
            -- Si es partido eliminatorio, obtener desde fase -> formato -> campeonato
            (SELECT cf.campeonato_id 
             FROM partidos p 
             JOIN fases_eliminatorias fe ON p.fase_eliminatoria_id = fe.id 
             JOIN campeonatos_formato cf ON fe.formato_id = cf.id 
             WHERE p.id = NEW.partido_id AND p.tipo_torneo = 'eliminatoria')
        ) INTO v_campeonato_id;
    
    SELECT tipo_torneo INTO v_tipo_torneo
    FROM partidos 
    WHERE id = NEW.partido_id;
    
    SET NEW.campeonato_id = v_campeonato_id;
    SET NEW.tipo_partido = COALESCE(v_tipo_torneo, 'normal');
END
$$
DELIMITER ;

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
  `es_torneo_nocturno` tinyint(1) DEFAULT 0 COMMENT 'Indica si este registro es de un torneo nocturno',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `partidos_jugados` int(11) DEFAULT 0,
  `goles` int(11) DEFAULT 0,
  `amarillas` int(11) DEFAULT 0,
  `rojas` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
-- Estructura de tabla para la tabla `penales_partido`
--

CREATE TABLE `penales_partido` (
  `id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL,
  `jugador_id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL COMMENT 'Equipo al que pertenece el jugador',
  `numero_penal` int(11) NOT NULL COMMENT 'Número de penal en la tanda (1, 2, 3, etc.)',
  `convertido` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 si convirtió, 0 si erró',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `campeonato_id` int(11) DEFAULT NULL COMMENT 'ID del campeonato al que pertenece la sanción',
  `tipo_torneo` enum('normal','zona','eliminatoria') DEFAULT 'normal' COMMENT 'Tipo de torneo donde se aplica la sanción',
  `tipo` enum('amarillas_acumuladas','doble_amarilla','roja_directa','administrativa') NOT NULL,
  `partidos_suspension` int(11) NOT NULL,
  `partidos_cumplidos` int(11) DEFAULT 0,
  `descripcion` text DEFAULT NULL,
  `activa` tinyint(1) DEFAULT 1,
  `fecha_sancion` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  ADD KEY `idx_eventos_tipo_partido` (`partido_id`,`tipo_partido`),
  ADD KEY `idx_campeonato_id` (`campeonato_id`);

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
-- Indices de la tabla `penales_partido`
--
ALTER TABLE `penales_partido`
  ADD PRIMARY KEY (`id`),
  ADD KEY `partido_id` (`partido_id`),
  ADD KEY `jugador_id` (`jugador_id`),
  ADD KEY `equipo_id` (`equipo_id`);

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
  ADD KEY `idx_sanciones_fechas` (`partidos_cumplidos`,`partidos_suspension`),
  ADD KEY `idx_campeonato_id` (`campeonato_id`),
  ADD KEY `idx_tipo_torneo` (`tipo_torneo`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de la tabla `campeonatos_formato`
--
ALTER TABLE `campeonatos_formato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT de la tabla `canchas`
--
ALTER TABLE `canchas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `codigos_cancha`
--
ALTER TABLE `codigos_cancha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1622;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=312;

--
-- AUTO_INCREMENT de la tabla `equipos_zonas`
--
ALTER TABLE `equipos_zonas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=300;

--
-- AUTO_INCREMENT de la tabla `eventos_partido`
--
ALTER TABLE `eventos_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=296;

--
-- AUTO_INCREMENT de la tabla `fases_eliminatorias`
--
ALTER TABLE `fases_eliminatorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=73;

--
-- AUTO_INCREMENT de la tabla `fechas`
--
ALTER TABLE `fechas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2069;

--
-- AUTO_INCREMENT de la tabla `horarios_canchas`
--
ALTER TABLE `horarios_canchas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=317;

--
-- AUTO_INCREMENT de la tabla `jugadores`
--
ALTER TABLE `jugadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1404;

--
-- AUTO_INCREMENT de la tabla `jugadores_equipos_historial`
--
ALTER TABLE `jugadores_equipos_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1312;

--
-- AUTO_INCREMENT de la tabla `jugadores_partido`
--
ALTER TABLE `jugadores_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8604;

--
-- AUTO_INCREMENT de la tabla `log_sanciones`
--
ALTER TABLE `log_sanciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `partidos`
--
ALTER TABLE `partidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1335;

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
-- AUTO_INCREMENT de la tabla `penales_partido`
--
ALTER TABLE `penales_partido`
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=113;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=85;

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
-- Filtros para la tabla `penales_partido`
--
ALTER TABLE `penales_partido`
  ADD CONSTRAINT `fk_penales_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_penales_jugador` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_penales_partido` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE;

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
