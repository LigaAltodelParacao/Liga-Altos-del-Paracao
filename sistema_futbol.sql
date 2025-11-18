-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 18-11-2025 a las 22:37:13
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
  `tipo_campeonato` enum('largo','zonal') NOT NULL COMMENT 'Tipo de campeonato: largo (Apertura/Clausura) o zonal (Torneo Nocturno, etc.)',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `campeonatos`
--

INSERT INTO `campeonatos` (`id`, `nombre`, `descripcion`, `fecha_inicio`, `fecha_fin`, `activo`, `es_torneo_nocturno`, `tipo_campeonato`, `created_at`) VALUES
(16, 'Nocturno 2026', '', '2025-11-15', NULL, 1, 1, 'zonal', '2025-11-15 14:21:10'),
(17, 'Clausura 2025', '', '2025-11-22', NULL, 1, 0, 'largo', '2025-11-18 15:18:59');

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
  `cuartos_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad de cuartos que clasifican',
  `empates_pendientes` tinyint(1) DEFAULT 0 COMMENT 'Indica si hay empates pendientes de resolución'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `campeonatos_formato`
--

INSERT INTO `campeonatos_formato` (`id`, `campeonato_id`, `tipo_formato`, `cantidad_zonas`, `equipos_por_zona`, `equipos_clasifican`, `tipo_clasificacion`, `tiene_octavos`, `tiene_cuartos`, `tiene_semifinal`, `tiene_tercer_puesto`, `activo`, `created_at`, `updated_at`, `categoria_id`, `primeros_clasifican`, `segundos_clasifican`, `terceros_clasifican`, `cuartos_clasifican`, `empates_pendientes`) VALUES
(34, 16, 'mixto', 2, 3, 4, NULL, 0, 1, 1, 1, 1, '2025-11-18 14:10:29', '2025-11-18 14:10:29', 38, 2, 2, 0, 0, 0);

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
(38, 16, 'Libre', '', 1),
(39, 17, 'M40', '', 1);

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
-- Estructura de tabla para la tabla `empates_pendientes`
--

CREATE TABLE `empates_pendientes` (
  `id` int(11) NOT NULL,
  `formato_id` int(11) NOT NULL,
  `zona_id` int(11) NOT NULL,
  `posicion` int(11) NOT NULL COMMENT 'Posición en la que están empatados (1=primero, 2=segundo, etc.)',
  `equipos_ids` text NOT NULL COMMENT 'JSON con los IDs de los equipos empatados',
  `equipos_nombres` text NOT NULL COMMENT 'JSON con los nombres de los equipos empatados',
  `criterios_aplicados` text DEFAULT NULL COMMENT 'JSON con los criterios de desempate aplicados',
  `equipo_ganador_id` int(11) DEFAULT NULL COMMENT 'ID del equipo que gana por sorteo (se establece manualmente)',
  `resuelto` tinyint(1) DEFAULT 0 COMMENT 'Indica si el empate ya fue resuelto',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resuelto_at` timestamp NULL DEFAULT NULL
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
(325, 38, 'Nono Gringo M40', NULL, '#007bff', '', 1, '2025-11-15 14:21:40'),
(326, 38, 'La 17', NULL, '#007bff', '', 1, '2025-11-15 14:21:46'),
(329, 38, 'Agrupación La Chimenea M30', 'equipos/AFCAPER_M40_2.png', '#007bff', '', 1, '2025-11-15 14:22:09'),
(330, 38, 'Villa Urquiza M40', NULL, '#007bff', '', 1, '2025-11-15 14:22:19'),
(331, 38, 'Taladro M40', NULL, '#007bff', '', 1, '2025-11-15 14:22:38'),
(332, 38, 'Distribuidora Tata', NULL, '#007bff', '', 1, '2025-11-15 14:22:45');

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
  `tarjetas_rojas` int(11) DEFAULT 0,
  `requiere_sorteo` tinyint(1) DEFAULT 0 COMMENT 'Indica si este equipo está en un empate que requiere sorteo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `equipos_zonas`
--

INSERT INTO `equipos_zonas` (`id`, `zona_id`, `equipo_id`, `puntos`, `partidos_jugados`, `partidos_ganados`, `partidos_empatados`, `partidos_perdidos`, `goles_favor`, `goles_contra`, `posicion`, `clasificado`, `tarjetas_amarillas`, `tarjetas_rojas`, `requiere_sorteo`) VALUES
(343, 99, 332, 1, 2, 0, 1, 1, 5, 6, 3, 0, 0, 0, 0),
(344, 99, 329, 4, 2, 1, 1, 0, 5, 3, 1, 1, 0, 0, 0),
(345, 99, 326, 3, 2, 1, 0, 1, 3, 4, 2, 1, 1, 2, 0),
(346, 100, 325, 1, 2, 0, 1, 1, 0, 1, 3, 0, 0, 0, 0),
(347, 100, 330, 4, 2, 1, 1, 0, 1, 0, 1, 1, 0, 2, 0),
(348, 100, 331, 2, 2, 0, 2, 0, 0, 0, 2, 1, 0, 0, 0);

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
  `es_torneo_zonal` tinyint(1) DEFAULT 0 COMMENT 'Indica si este evento pertenece a un torneo por zonas (1) o torneo largo (0)',
  `created_at` timestamp NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `eventos_partido`
--

INSERT INTO `eventos_partido` (`id`, `partido_id`, `campeonato_id`, `jugador_id`, `tipo_evento`, `minuto`, `observaciones`, `tipo_partido`, `es_torneo_zonal`, `created_at`) VALUES
(427, 1438, 16, 1681, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:12:29'),
(428, 1438, 16, 1679, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:12:29'),
(431, 1438, 16, 1716, 'roja', 0, NULL, 'zona', 1, '2025-11-18 14:12:29'),
(432, 1438, 16, 1733, 'amarilla', 0, NULL, 'zona', 1, '2025-11-18 14:12:29'),
(433, 1438, 16, 1707, 'roja', 0, 'Doble amarilla', 'zona', 1, '2025-11-18 14:12:29'),
(436, 1441, 16, 1803, 'roja', 0, NULL, 'zona', 1, '2025-11-18 14:14:33'),
(437, 1441, 16, 1796, 'roja', 0, 'Doble amarilla', 'zona', 1, '2025-11-18 14:14:33'),
(438, 1439, 16, 1744, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:14:58'),
(439, 1439, 16, 1706, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:14:58'),
(440, 1439, 16, 1727, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:14:58'),
(441, 1439, 16, 1689, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:14:58'),
(442, 1439, 16, 1692, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:14:58'),
(443, 1440, 16, 1698, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:15:25'),
(444, 1440, 16, 1689, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:15:25'),
(445, 1440, 16, 1701, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:15:25'),
(446, 1440, 16, 1681, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:15:25'),
(447, 1440, 16, 1681, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:15:25'),
(448, 1440, 16, 1679, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:15:25'),
(449, 1443, 16, 1788, 'gol', 0, NULL, 'zona', 1, '2025-11-18 14:15:33'),
(450, 1444, 16, 1779, 'gol', 0, NULL, 'eliminatoria', 1, '2025-11-18 22:09:40'),
(451, 1444, 16, 1779, 'gol', 0, NULL, 'eliminatoria', 1, '2025-11-18 22:09:40'),
(452, 1444, 16, 1772, 'amarilla', 0, NULL, 'eliminatoria', 1, '2025-11-18 22:09:40'),
(453, 1444, 16, 1673, 'amarilla', 0, NULL, 'eliminatoria', 1, '2025-11-18 22:09:40');

--
-- Disparadores `eventos_partido`
--
DELIMITER $$
CREATE TRIGGER `trg_eventos_partido_campeonato` BEFORE INSERT ON `eventos_partido` FOR EACH ROW BEGIN
    DECLARE v_campeonato_id INT;
    DECLARE v_tipo_torneo VARCHAR(20);
    DECLARE v_tipo_campeonato VARCHAR(10);
    DECLARE v_es_torneo_zonal TINYINT(1);
    
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
    
    -- Determinar si es torneo zonal
    -- Si el tipo_torneo es 'zona' o 'eliminatoria' (de un torneo zonal), entonces es zonal
    -- También verificamos el tipo_campeonato del campeonato
    IF v_campeonato_id IS NOT NULL THEN
        SELECT COALESCE(tipo_campeonato, 
                        CASE WHEN es_torneo_nocturno = 1 THEN 'zonal' ELSE 'largo' END) 
        INTO v_tipo_campeonato
        FROM campeonatos
        WHERE id = v_campeonato_id;
        
        -- Si el tipo_campeonato es 'zonal' O el tipo_torneo es 'zona'/'eliminatoria', es zonal
        IF (v_tipo_campeonato = 'zonal' OR v_tipo_torneo IN ('zona', 'eliminatoria')) THEN
            SET v_es_torneo_zonal = 1;
        ELSE
            SET v_es_torneo_zonal = 0;
        END IF;
    ELSE
        -- Si no se puede determinar el campeonato, usar el tipo_torneo como referencia
        IF v_tipo_torneo IN ('zona', 'eliminatoria') THEN
            SET v_es_torneo_zonal = 1;
        ELSE
            SET v_es_torneo_zonal = 0;
        END IF;
    END IF;
    
    SET NEW.campeonato_id = v_campeonato_id;
    SET NEW.tipo_partido = COALESCE(v_tipo_torneo, 'normal');
    SET NEW.es_torneo_zonal = v_es_torneo_zonal;
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

--
-- Volcado de datos para la tabla `fases_eliminatorias`
--

INSERT INTO `fases_eliminatorias` (`id`, `formato_id`, `nombre`, `orden`, `activa`, `generada`) VALUES
(87, 34, 'cuartos', 1, 1, 1),
(88, 34, 'semifinal', 2, 0, 0),
(89, 34, 'tercer_puesto', 3, 0, 0),
(90, 34, 'final', 4, 0, 0);

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
(2159, 38, 1, '2025-11-25', 1, NULL, NULL, 'eliminatoria'),
(2160, 38, 1, '2025-11-18', 1, 99, NULL, 'zona'),
(2161, 38, 2, '2025-11-25', 1, 99, NULL, 'zona'),
(2162, 38, 3, '2025-12-02', 1, 99, NULL, 'zona'),
(2163, 38, 1, '2025-11-18', 1, 100, NULL, 'zona'),
(2164, 38, 2, '2025-11-25', 1, 100, NULL, 'zona'),
(2165, 38, 3, '2025-12-02', 1, 100, NULL, 'zona'),
(2166, 38, 1, '2025-11-25', 1, NULL, 87, 'eliminatoria');

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
(1666, 329, '43889434', 'Amador Mata', '1981-02-06', NULL, 1, 0, '2025-11-15 14:23:10'),
(1667, 329, '32146246', 'Amor Huerta', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:10'),
(1668, 329, '33757075', 'Íñigo Angulo', '1987-05-06', NULL, 1, 0, '2025-11-15 14:23:10'),
(1669, 329, '31178802', 'Teobaldo Bayo', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:10'),
(1670, 329, '34143022', 'Narciso Ferrándiz', '1981-08-06', NULL, 1, 0, '2025-11-15 14:23:10'),
(1671, 329, '45334975', 'Alejandra Barroso', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:10'),
(1672, 329, '31321236', 'Clara Pulido', '1984-02-05', NULL, 1, 0, '2025-11-15 14:23:10'),
(1673, 329, '33724432', 'Francisco Javier Hernando', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:10'),
(1674, 329, '34560978', 'Amando Aller', '1982-04-08', NULL, 1, 0, '2025-11-15 14:23:10'),
(1675, 329, '46425262', 'Brunilda Baró', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:10'),
(1676, 329, '35402978', 'Caridad Piñol', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:10'),
(1677, 329, '33742321', 'Amaro Moliner', '1980-03-08', NULL, 1, 0, '2025-11-15 14:23:10'),
(1678, 329, '36193265', 'Soledad Grau', '1987-04-03', NULL, 1, 0, '2025-11-15 14:23:10'),
(1679, 329, '37784173', 'Gustavo Núñez', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:10'),
(1680, 329, '36583069', 'Fabricio Palomares', '1980-11-10', NULL, 1, 0, '2025-11-15 14:23:10'),
(1681, 329, '32460731', 'Gregorio Fonseca', '1986-04-01', NULL, 1, 0, '2025-11-15 14:23:10'),
(1682, 329, '48810339', 'Maricela Alfonso', '1981-06-10', NULL, 1, 0, '2025-11-15 14:23:10'),
(1683, 329, '31932444', 'Bienvenida Jordán', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:10'),
(1684, 329, '45994972', 'Adoración Cueto', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:10'),
(1685, 329, '41029699', 'Cecilia Franco', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:10'),
(1686, 332, '36578623', 'Ramona Cobos', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1687, 332, '45770508', 'Benjamín Becerra', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1688, 332, '38738436', 'Apolonia Roda', '1984-04-09', NULL, 1, 0, '2025-11-15 14:23:20'),
(1689, 332, '46879515', 'Leocadio Cases', '1987-04-04', NULL, 1, 0, '2025-11-15 14:23:20'),
(1690, 332, '31646694', 'Ema Solano', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1691, 332, '46875272', 'Azahar Lobo', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1692, 332, '30558144', 'Mariana Duque', '1985-08-09', NULL, 1, 0, '2025-11-15 14:23:20'),
(1693, 332, '49185286', 'Juanita Arnal', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1694, 332, '43984726', 'Elías Revilla', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1695, 332, '45902163', 'Dorotea Gálvez', '1983-03-10', NULL, 1, 0, '2025-11-15 14:23:20'),
(1696, 332, '33519848', 'Cecilia Aguilera', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1697, 332, '48112232', 'Severo Lobo', '1981-05-12', NULL, 1, 0, '2025-11-15 14:23:20'),
(1698, 332, '35710784', 'Juan Antonio Batlle', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1699, 332, '34761381', 'María José Ribas', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1700, 332, '44593278', 'Desiderio Tomé', '1982-07-10', NULL, 1, 0, '2025-11-15 14:23:20'),
(1701, 332, '42339734', 'Pascuala Barreda', '1980-11-07', NULL, 1, 0, '2025-11-15 14:23:20'),
(1702, 332, '36462133', 'Roberto Abascal', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1703, 332, '49051388', 'Maite Toledo', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1704, 332, '46656265', 'Elías Peñalver', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:20'),
(1705, 332, '32375362', 'Berto Guijarro', '1986-12-07', NULL, 1, 0, '2025-11-15 14:23:20'),
(1706, 326, '39290727', 'Eleuterio Carbajo', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1707, 326, '34430020', 'Eugenia Carretero', '1983-08-06', NULL, 1, 0, '2025-11-15 14:23:38'),
(1708, 326, '45191484', 'Maura Cabello', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1709, 326, '39647192', 'Dulce Osuna', '1987-06-02', NULL, 1, 0, '2025-11-15 14:23:38'),
(1710, 326, '49810588', 'Vicente Goñi', '1982-07-04', NULL, 1, 0, '2025-11-15 14:23:38'),
(1711, 326, '32143400', 'Felicidad Lopez', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1712, 326, '35729165', 'Ceferino Valentín', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1713, 326, '38394529', 'Celso Valencia', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1714, 326, '41555233', 'Ruth Castejón', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1715, 326, '30394173', 'Juan Pablo Gibert', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1716, 326, '41085890', 'Concha Ricart', '1983-02-11', NULL, 1, 0, '2025-11-15 14:23:38'),
(1717, 326, '34262960', 'Marciano Tomas', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1718, 326, '35027009', 'María Vallés', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1719, 326, '31870188', 'Otilia Beltrán', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1720, 326, '41667179', 'Sonia Viñas', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1721, 326, '35771628', 'Celestino Blanch', '1985-11-05', NULL, 1, 0, '2025-11-15 14:23:38'),
(1722, 326, '49799580', 'Modesta Taboada', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1723, 326, '39500918', 'Adoración Serna', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1724, 326, '47155845', 'Javi Borrell', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1725, 326, '36077431', 'Cleto Verdugo', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:38'),
(1726, 326, '37515535', 'Chita Crespo', '1990-09-08', NULL, 1, 0, '2025-11-15 14:23:49'),
(1727, 326, '44672170', 'Ester Real', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1728, 326, '49844584', 'Montserrat Jurado', '1990-04-03', NULL, 1, 0, '2025-11-15 14:23:49'),
(1729, 326, '44852444', 'Plácido Lladó', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1730, 326, '49784070', 'Fidela Naranjo', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1731, 326, '45255150', 'Jorge Domingo', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1732, 326, '48537798', 'Melania Cadenas', '1985-04-05', NULL, 1, 0, '2025-11-15 14:23:49'),
(1733, 326, '36324208', 'Fito Arroyo', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1734, 326, '47471182', 'Jesusa Lillo', '1984-05-12', NULL, 1, 0, '2025-11-15 14:23:49'),
(1735, 326, '44209852', 'Eutimio Quevedo', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1736, 326, '33498466', 'Ruperto Ros', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1737, 326, '39617702', 'Sancho Rosselló', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1738, 326, '44684052', 'Vito Amigó', '1987-12-08', NULL, 1, 0, '2025-11-15 14:23:49'),
(1739, 326, '30667576', 'Victorino Adán', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1740, 326, '37913008', 'Ximena Ríos', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1741, 326, '45597727', 'Caridad Ballesteros', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1742, 326, '43020284', 'Dolores Chamorro', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1743, 326, '30260448', 'Wilfredo Vilanova', '1969-12-31', NULL, 1, 0, '2025-11-15 14:23:49'),
(1744, 326, '33911532', 'Feliciano Calvo', '1982-10-05', NULL, 1, 0, '2025-11-15 14:23:49'),
(1745, 326, '31509333', 'Germán Mora', '1982-07-01', NULL, 1, 0, '2025-11-15 14:23:49'),
(1746, 325, '40371895', 'Adrián Busquets', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1747, 325, '39521155', 'Nilda Tejedor', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1748, 325, '35652596', 'Rebeca Tur', '1990-05-03', NULL, 1, 0, '2025-11-15 14:25:37'),
(1749, 325, '36439685', 'Benjamín Román', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1750, 325, '41326416', 'Fidela Heras', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1751, 325, '47072793', 'Fabricio Quiroga', '1980-04-12', NULL, 1, 0, '2025-11-15 14:25:37'),
(1752, 325, '45683458', 'Gema Romeu', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1753, 325, '35655902', 'Juan Antonio Juan', '1980-01-01', NULL, 1, 0, '2025-11-15 14:25:37'),
(1754, 325, '34075116', 'Clotilde Solana', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1755, 325, '35111676', 'Olalla Gallart', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1756, 325, '44006518', 'Kike Roman', '1981-06-06', NULL, 1, 0, '2025-11-15 14:25:37'),
(1757, 325, '34074636', 'Ainara Estevez', '1982-05-10', NULL, 1, 0, '2025-11-15 14:25:37'),
(1758, 325, '30233633', 'Valero Codina', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1759, 325, '45370722', 'Soraya Rodrigo', '1985-09-09', NULL, 1, 0, '2025-11-15 14:25:37'),
(1760, 325, '47309909', 'Osvaldo Casals', '1980-02-04', NULL, 1, 0, '2025-11-15 14:25:37'),
(1761, 325, '37234266', 'Nacho Marí', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1762, 325, '34031495', 'Ismael Marti', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1763, 325, '38514825', 'Valentina Garrido', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1764, 325, '31935976', 'Isidro Fuertes', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1765, 325, '31119132', 'Gilberto Gutierrez', '1969-12-31', NULL, 1, 0, '2025-11-15 14:25:37'),
(1766, 331, '38667815', 'Florinda Samper', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1767, 331, '40753868', 'Horacio Salgado', '1990-02-10', NULL, 1, 0, '2025-11-15 14:26:04'),
(1768, 331, '30789002', 'Manuelita Sevilla', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1769, 331, '40112241', 'Iris Ballesteros', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1770, 331, '38092339', 'Marciano Quintero', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1771, 331, '46065511', 'Eufemia Bastida', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1772, 331, '41588574', 'Leandro Coello', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1773, 331, '31093094', 'Claudia Rodrigo', '1984-12-09', NULL, 1, 0, '2025-11-15 14:26:04'),
(1774, 331, '46264650', 'Silvia Aparicio', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1775, 331, '40078221', 'Zaira Losa', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1776, 331, '39870134', 'Nicolasa Piquer', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1777, 331, '41463562', 'Isidora Manjón', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1778, 331, '44653973', 'Susana Espada', '1987-08-05', NULL, 1, 0, '2025-11-15 14:26:04'),
(1779, 331, '37427228', 'Bárbara Arribas', '1985-08-06', NULL, 1, 0, '2025-11-15 14:26:04'),
(1780, 331, '32503799', 'Danilo Guillen', '1986-10-08', NULL, 1, 0, '2025-11-15 14:26:04'),
(1781, 331, '38508175', 'Julio César Porta', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1782, 331, '38312626', 'Fabiana Tenorio', '1986-04-04', NULL, 1, 0, '2025-11-15 14:26:04'),
(1783, 331, '49071318', 'Pastor Palomo', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1784, 331, '37035118', 'Juan Luis Verdú', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:04'),
(1785, 331, '36465900', 'Elpidio Bauzà', '1985-08-04', NULL, 1, 0, '2025-11-15 14:26:04'),
(1786, 330, '47856071', 'Nerea Vaquero', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:44'),
(1787, 330, '31771358', 'Baudelio Diego', '1981-10-11', NULL, 1, 0, '2025-11-15 14:26:44'),
(1788, 330, '39442930', 'Luciana Carballo', '1985-05-06', NULL, 1, 0, '2025-11-15 14:26:44'),
(1789, 330, '49243585', 'Adora Serra', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:44'),
(1790, 330, '49646732', 'Leticia Villaverde', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:44'),
(1791, 330, '30491674', 'Tomasa Arana', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:44'),
(1792, 330, '42894283', 'Emiliana Casares', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:44'),
(1793, 330, '38794820', 'Paz Muro', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:44'),
(1794, 330, '30346865', 'Lorena Rius', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:44'),
(1795, 330, '33878020', 'Jacinto Collado', '1987-02-04', NULL, 1, 0, '2025-11-15 14:26:44'),
(1796, 330, '38853424', 'Quirino Chaves', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:44'),
(1797, 330, '47973892', 'Yésica Gallego', '1988-11-06', NULL, 1, 0, '2025-11-15 14:26:44'),
(1798, 330, '39937230', 'Azucena Guillen', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:44'),
(1799, 330, '44666601', 'Rosenda Machado', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:44'),
(1800, 330, '43245639', 'Josefina Noriega', '1981-08-03', NULL, 1, 0, '2025-11-15 14:26:44'),
(1801, 330, '41827983', 'Chelo Páez', '1982-06-11', NULL, 1, 0, '2025-11-15 14:26:44'),
(1802, 330, '33510542', 'Leoncio Borrás', '1989-03-08', NULL, 1, 0, '2025-11-15 14:26:44'),
(1803, 330, '37985624', 'Segismundo Prat', '1982-03-05', NULL, 1, 0, '2025-11-15 14:26:44'),
(1804, 330, '49596478', 'Marciano Rius', '1969-12-31', NULL, 1, 0, '2025-11-15 14:26:44'),
(1805, 330, '43274767', 'Liliana Gallo', '1980-11-05', NULL, 1, 0, '2025-11-15 14:26:44');

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

--
-- Volcado de datos para la tabla `jugadores_equipos_historial`
--

INSERT INTO `jugadores_equipos_historial` (`id`, `jugador_dni`, `jugador_nombre`, `equipo_id`, `campeonato_id`, `es_torneo_nocturno`, `fecha_inicio`, `fecha_fin`, `partidos_jugados`, `goles`, `amarillas`, `rojas`, `created_at`) VALUES
(1574, '43889434', 'Amador Mata', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1575, '32146246', 'Amor Huerta', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1576, '33757075', 'Íñigo Angulo', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1577, '31178802', 'Teobaldo Bayo', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1578, '34143022', 'Narciso Ferrándiz', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1579, '45334975', 'Alejandra Barroso', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1580, '31321236', 'Clara Pulido', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1581, '33724432', 'Francisco Javier Hernando', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1582, '34560978', 'Amando Aller', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1583, '46425262', 'Brunilda Baró', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1584, '35402978', 'Caridad Piñol', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1585, '33742321', 'Amaro Moliner', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1586, '36193265', 'Soledad Grau', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1587, '37784173', 'Gustavo Núñez', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1588, '36583069', 'Fabricio Palomares', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1589, '32460731', 'Gregorio Fonseca', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1590, '48810339', 'Maricela Alfonso', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1591, '31932444', 'Bienvenida Jordán', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1592, '45994972', 'Adoración Cueto', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1593, '41029699', 'Cecilia Franco', 329, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:10'),
(1594, '36578623', 'Ramona Cobos', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1595, '45770508', 'Benjamín Becerra', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1596, '38738436', 'Apolonia Roda', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1597, '46879515', 'Leocadio Cases', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1598, '31646694', 'Ema Solano', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1599, '46875272', 'Azahar Lobo', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1600, '30558144', 'Mariana Duque', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1601, '49185286', 'Juanita Arnal', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1602, '43984726', 'Elías Revilla', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1603, '45902163', 'Dorotea Gálvez', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1604, '33519848', 'Cecilia Aguilera', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1605, '48112232', 'Severo Lobo', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1606, '35710784', 'Juan Antonio Batlle', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1607, '34761381', 'María José Ribas', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1608, '44593278', 'Desiderio Tomé', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1609, '42339734', 'Pascuala Barreda', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1610, '36462133', 'Roberto Abascal', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1611, '49051388', 'Maite Toledo', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1612, '46656265', 'Elías Peñalver', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1613, '32375362', 'Berto Guijarro', 332, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:20'),
(1614, '39290727', 'Eleuterio Carbajo', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1615, '34430020', 'Eugenia Carretero', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1616, '45191484', 'Maura Cabello', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1617, '39647192', 'Dulce Osuna', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1618, '49810588', 'Vicente Goñi', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1619, '32143400', 'Felicidad Lopez', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1620, '35729165', 'Ceferino Valentín', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1621, '38394529', 'Celso Valencia', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1622, '41555233', 'Ruth Castejón', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1623, '30394173', 'Juan Pablo Gibert', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1624, '41085890', 'Concha Ricart', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1625, '34262960', 'Marciano Tomas', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1626, '35027009', 'María Vallés', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1627, '31870188', 'Otilia Beltrán', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1628, '41667179', 'Sonia Viñas', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1629, '35771628', 'Celestino Blanch', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1630, '49799580', 'Modesta Taboada', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1631, '39500918', 'Adoración Serna', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1632, '47155845', 'Javi Borrell', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1633, '36077431', 'Cleto Verdugo', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:38'),
(1634, '37515535', 'Chita Crespo', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1635, '44672170', 'Ester Real', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1636, '49844584', 'Montserrat Jurado', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1637, '44852444', 'Plácido Lladó', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1638, '49784070', 'Fidela Naranjo', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1639, '45255150', 'Jorge Domingo', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1640, '48537798', 'Melania Cadenas', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1641, '36324208', 'Fito Arroyo', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1642, '47471182', 'Jesusa Lillo', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1643, '44209852', 'Eutimio Quevedo', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1644, '33498466', 'Ruperto Ros', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1645, '39617702', 'Sancho Rosselló', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1646, '44684052', 'Vito Amigó', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1647, '30667576', 'Victorino Adán', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1648, '37913008', 'Ximena Ríos', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1649, '45597727', 'Caridad Ballesteros', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1650, '43020284', 'Dolores Chamorro', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1651, '30260448', 'Wilfredo Vilanova', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1652, '33911532', 'Feliciano Calvo', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1653, '31509333', 'Germán Mora', 326, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:23:49'),
(1654, '40371895', 'Adrián Busquets', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1655, '39521155', 'Nilda Tejedor', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1656, '35652596', 'Rebeca Tur', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1657, '36439685', 'Benjamín Román', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1658, '41326416', 'Fidela Heras', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1659, '47072793', 'Fabricio Quiroga', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1660, '45683458', 'Gema Romeu', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1661, '35655902', 'Juan Antonio Juan', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1662, '34075116', 'Clotilde Solana', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1663, '35111676', 'Olalla Gallart', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1664, '44006518', 'Kike Roman', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1665, '34074636', 'Ainara Estevez', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1666, '30233633', 'Valero Codina', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1667, '45370722', 'Soraya Rodrigo', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1668, '47309909', 'Osvaldo Casals', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1669, '37234266', 'Nacho Marí', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1670, '34031495', 'Ismael Marti', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1671, '38514825', 'Valentina Garrido', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1672, '31935976', 'Isidro Fuertes', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1673, '31119132', 'Gilberto Gutierrez', 325, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:25:37'),
(1674, '38667815', 'Florinda Samper', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1675, '40753868', 'Horacio Salgado', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1676, '30789002', 'Manuelita Sevilla', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1677, '40112241', 'Iris Ballesteros', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1678, '38092339', 'Marciano Quintero', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1679, '46065511', 'Eufemia Bastida', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1680, '41588574', 'Leandro Coello', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1681, '31093094', 'Claudia Rodrigo', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1682, '46264650', 'Silvia Aparicio', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1683, '40078221', 'Zaira Losa', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1684, '39870134', 'Nicolasa Piquer', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1685, '41463562', 'Isidora Manjón', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1686, '44653973', 'Susana Espada', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1687, '37427228', 'Bárbara Arribas', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1688, '32503799', 'Danilo Guillen', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1689, '38508175', 'Julio César Porta', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1690, '38312626', 'Fabiana Tenorio', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1691, '49071318', 'Pastor Palomo', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1692, '37035118', 'Juan Luis Verdú', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1693, '36465900', 'Elpidio Bauzà', 331, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:04'),
(1694, '47856071', 'Nerea Vaquero', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1695, '31771358', 'Baudelio Diego', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1696, '39442930', 'Luciana Carballo', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1697, '49243585', 'Adora Serra', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1698, '49646732', 'Leticia Villaverde', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1699, '30491674', 'Tomasa Arana', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1700, '42894283', 'Emiliana Casares', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1701, '38794820', 'Paz Muro', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1702, '30346865', 'Lorena Rius', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1703, '33878020', 'Jacinto Collado', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1704, '38853424', 'Quirino Chaves', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1705, '47973892', 'Yésica Gallego', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1706, '39937230', 'Azucena Guillen', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1707, '44666601', 'Rosenda Machado', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1708, '43245639', 'Josefina Noriega', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1709, '41827983', 'Chelo Páez', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1710, '33510542', 'Leoncio Borrás', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1711, '37985624', 'Segismundo Prat', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1712, '49596478', 'Marciano Rius', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44'),
(1713, '43274767', 'Liliana Gallo', 330, 16, 0, '2025-11-15', NULL, 0, 0, 0, 0, '2025-11-15 14:26:44');

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
(10819, 1438, 1666, 0, 'normal', '2025-11-18 14:12:29'),
(10820, 1438, 1667, 0, 'normal', '2025-11-18 14:12:29'),
(10821, 1438, 1668, 0, 'normal', '2025-11-18 14:12:29'),
(10822, 1438, 1669, 0, 'normal', '2025-11-18 14:12:29'),
(10823, 1438, 1670, 0, 'normal', '2025-11-18 14:12:29'),
(10824, 1438, 1671, 0, 'normal', '2025-11-18 14:12:29'),
(10825, 1438, 1672, 0, 'normal', '2025-11-18 14:12:29'),
(10826, 1438, 1673, 0, 'normal', '2025-11-18 14:12:29'),
(10827, 1438, 1674, 0, 'normal', '2025-11-18 14:12:29'),
(10828, 1438, 1675, 0, 'normal', '2025-11-18 14:12:29'),
(10829, 1438, 1676, 0, 'normal', '2025-11-18 14:12:29'),
(10830, 1438, 1677, 0, 'normal', '2025-11-18 14:12:29'),
(10831, 1438, 1678, 0, 'normal', '2025-11-18 14:12:29'),
(10832, 1438, 1679, 0, 'normal', '2025-11-18 14:12:29'),
(10833, 1438, 1680, 0, 'normal', '2025-11-18 14:12:29'),
(10834, 1438, 1681, 0, 'normal', '2025-11-18 14:12:29'),
(10835, 1438, 1682, 0, 'normal', '2025-11-18 14:12:29'),
(10836, 1438, 1683, 0, 'normal', '2025-11-18 14:12:29'),
(10837, 1438, 1684, 0, 'normal', '2025-11-18 14:12:29'),
(10838, 1438, 1685, 0, 'normal', '2025-11-18 14:12:29'),
(10839, 1438, 1706, 0, 'normal', '2025-11-18 14:12:29'),
(10840, 1438, 1707, 0, 'normal', '2025-11-18 14:12:29'),
(10841, 1438, 1708, 0, 'normal', '2025-11-18 14:12:29'),
(10842, 1438, 1709, 0, 'normal', '2025-11-18 14:12:29'),
(10843, 1438, 1710, 0, 'normal', '2025-11-18 14:12:29'),
(10844, 1438, 1711, 0, 'normal', '2025-11-18 14:12:29'),
(10845, 1438, 1712, 0, 'normal', '2025-11-18 14:12:29'),
(10846, 1438, 1713, 0, 'normal', '2025-11-18 14:12:29'),
(10847, 1438, 1714, 0, 'normal', '2025-11-18 14:12:29'),
(10848, 1438, 1715, 0, 'normal', '2025-11-18 14:12:29'),
(10849, 1438, 1716, 0, 'normal', '2025-11-18 14:12:29'),
(10850, 1438, 1717, 0, 'normal', '2025-11-18 14:12:29'),
(10851, 1438, 1718, 0, 'normal', '2025-11-18 14:12:29'),
(10852, 1438, 1719, 0, 'normal', '2025-11-18 14:12:29'),
(10853, 1438, 1720, 0, 'normal', '2025-11-18 14:12:29'),
(10854, 1438, 1721, 0, 'normal', '2025-11-18 14:12:29'),
(10855, 1438, 1722, 0, 'normal', '2025-11-18 14:12:29'),
(10856, 1438, 1723, 0, 'normal', '2025-11-18 14:12:29'),
(10857, 1438, 1724, 0, 'normal', '2025-11-18 14:12:29'),
(10858, 1438, 1725, 0, 'normal', '2025-11-18 14:12:29'),
(10859, 1438, 1726, 0, 'normal', '2025-11-18 14:12:29'),
(10860, 1438, 1727, 0, 'normal', '2025-11-18 14:12:29'),
(10861, 1438, 1728, 0, 'normal', '2025-11-18 14:12:29'),
(10862, 1438, 1729, 0, 'normal', '2025-11-18 14:12:29'),
(10863, 1438, 1730, 0, 'normal', '2025-11-18 14:12:29'),
(10864, 1438, 1731, 0, 'normal', '2025-11-18 14:12:29'),
(10865, 1438, 1732, 0, 'normal', '2025-11-18 14:12:29'),
(10866, 1438, 1733, 0, 'normal', '2025-11-18 14:12:29'),
(10867, 1438, 1734, 0, 'normal', '2025-11-18 14:12:29'),
(10868, 1438, 1735, 0, 'normal', '2025-11-18 14:12:29'),
(10869, 1438, 1736, 0, 'normal', '2025-11-18 14:12:29'),
(10870, 1438, 1737, 0, 'normal', '2025-11-18 14:12:29'),
(10871, 1438, 1738, 0, 'normal', '2025-11-18 14:12:29'),
(10872, 1438, 1739, 0, 'normal', '2025-11-18 14:12:29'),
(10873, 1438, 1740, 0, 'normal', '2025-11-18 14:12:29'),
(10874, 1438, 1741, 0, 'normal', '2025-11-18 14:12:29'),
(10875, 1438, 1742, 0, 'normal', '2025-11-18 14:12:29'),
(10876, 1438, 1743, 0, 'normal', '2025-11-18 14:12:29'),
(10877, 1438, 1744, 0, 'normal', '2025-11-18 14:12:29'),
(10878, 1438, 1745, 0, 'normal', '2025-11-18 14:12:29'),
(10879, 1441, 1786, 0, 'normal', '2025-11-18 14:14:33'),
(10880, 1441, 1787, 0, 'normal', '2025-11-18 14:14:33'),
(10881, 1441, 1788, 0, 'normal', '2025-11-18 14:14:33'),
(10882, 1441, 1789, 0, 'normal', '2025-11-18 14:14:33'),
(10883, 1441, 1790, 0, 'normal', '2025-11-18 14:14:33'),
(10884, 1441, 1791, 0, 'normal', '2025-11-18 14:14:33'),
(10885, 1441, 1792, 0, 'normal', '2025-11-18 14:14:33'),
(10886, 1441, 1793, 0, 'normal', '2025-11-18 14:14:33'),
(10887, 1441, 1794, 0, 'normal', '2025-11-18 14:14:33'),
(10888, 1441, 1795, 0, 'normal', '2025-11-18 14:14:33'),
(10889, 1441, 1796, 0, 'normal', '2025-11-18 14:14:33'),
(10890, 1441, 1797, 0, 'normal', '2025-11-18 14:14:33'),
(10891, 1441, 1798, 0, 'normal', '2025-11-18 14:14:33'),
(10892, 1441, 1799, 0, 'normal', '2025-11-18 14:14:33'),
(10893, 1441, 1800, 0, 'normal', '2025-11-18 14:14:33'),
(10894, 1441, 1801, 0, 'normal', '2025-11-18 14:14:33'),
(10895, 1441, 1802, 0, 'normal', '2025-11-18 14:14:33'),
(10896, 1441, 1803, 0, 'normal', '2025-11-18 14:14:33'),
(10897, 1441, 1804, 0, 'normal', '2025-11-18 14:14:33'),
(10898, 1441, 1805, 0, 'normal', '2025-11-18 14:14:33'),
(10899, 1441, 1766, 0, 'normal', '2025-11-18 14:14:33'),
(10900, 1441, 1767, 0, 'normal', '2025-11-18 14:14:33'),
(10901, 1441, 1768, 0, 'normal', '2025-11-18 14:14:33'),
(10902, 1441, 1769, 0, 'normal', '2025-11-18 14:14:33'),
(10903, 1441, 1770, 0, 'normal', '2025-11-18 14:14:33'),
(10904, 1441, 1771, 0, 'normal', '2025-11-18 14:14:33'),
(10905, 1441, 1772, 0, 'normal', '2025-11-18 14:14:33'),
(10906, 1441, 1773, 0, 'normal', '2025-11-18 14:14:33'),
(10907, 1441, 1774, 0, 'normal', '2025-11-18 14:14:33'),
(10908, 1441, 1775, 0, 'normal', '2025-11-18 14:14:33'),
(10909, 1441, 1776, 0, 'normal', '2025-11-18 14:14:33'),
(10910, 1441, 1777, 0, 'normal', '2025-11-18 14:14:33'),
(10911, 1441, 1778, 0, 'normal', '2025-11-18 14:14:33'),
(10912, 1441, 1779, 0, 'normal', '2025-11-18 14:14:33'),
(10913, 1441, 1780, 0, 'normal', '2025-11-18 14:14:33'),
(10914, 1441, 1781, 0, 'normal', '2025-11-18 14:14:33'),
(10915, 1441, 1782, 0, 'normal', '2025-11-18 14:14:33'),
(10916, 1441, 1783, 0, 'normal', '2025-11-18 14:14:33'),
(10917, 1441, 1784, 0, 'normal', '2025-11-18 14:14:33'),
(10918, 1441, 1785, 0, 'normal', '2025-11-18 14:14:33'),
(10919, 1439, 1706, 0, 'normal', '2025-11-18 14:14:58'),
(10920, 1439, 1707, 0, 'normal', '2025-11-18 14:14:58'),
(10921, 1439, 1708, 0, 'normal', '2025-11-18 14:14:58'),
(10922, 1439, 1709, 0, 'normal', '2025-11-18 14:14:58'),
(10923, 1439, 1710, 0, 'normal', '2025-11-18 14:14:58'),
(10924, 1439, 1711, 0, 'normal', '2025-11-18 14:14:58'),
(10925, 1439, 1712, 0, 'normal', '2025-11-18 14:14:58'),
(10926, 1439, 1713, 0, 'normal', '2025-11-18 14:14:58'),
(10927, 1439, 1714, 0, 'normal', '2025-11-18 14:14:58'),
(10928, 1439, 1715, 0, 'normal', '2025-11-18 14:14:58'),
(10929, 1439, 1716, 0, 'normal', '2025-11-18 14:14:58'),
(10930, 1439, 1717, 0, 'normal', '2025-11-18 14:14:58'),
(10931, 1439, 1718, 0, 'normal', '2025-11-18 14:14:58'),
(10932, 1439, 1719, 0, 'normal', '2025-11-18 14:14:58'),
(10933, 1439, 1720, 0, 'normal', '2025-11-18 14:14:58'),
(10934, 1439, 1721, 0, 'normal', '2025-11-18 14:14:58'),
(10935, 1439, 1722, 0, 'normal', '2025-11-18 14:14:58'),
(10936, 1439, 1723, 0, 'normal', '2025-11-18 14:14:58'),
(10937, 1439, 1724, 0, 'normal', '2025-11-18 14:14:58'),
(10938, 1439, 1725, 0, 'normal', '2025-11-18 14:14:58'),
(10939, 1439, 1726, 0, 'normal', '2025-11-18 14:14:58'),
(10940, 1439, 1727, 0, 'normal', '2025-11-18 14:14:58'),
(10941, 1439, 1728, 0, 'normal', '2025-11-18 14:14:58'),
(10942, 1439, 1729, 0, 'normal', '2025-11-18 14:14:58'),
(10943, 1439, 1730, 0, 'normal', '2025-11-18 14:14:58'),
(10944, 1439, 1731, 0, 'normal', '2025-11-18 14:14:58'),
(10945, 1439, 1732, 0, 'normal', '2025-11-18 14:14:58'),
(10946, 1439, 1733, 0, 'normal', '2025-11-18 14:14:58'),
(10947, 1439, 1734, 0, 'normal', '2025-11-18 14:14:58'),
(10948, 1439, 1735, 0, 'normal', '2025-11-18 14:14:58'),
(10949, 1439, 1736, 0, 'normal', '2025-11-18 14:14:58'),
(10950, 1439, 1737, 0, 'normal', '2025-11-18 14:14:58'),
(10951, 1439, 1738, 0, 'normal', '2025-11-18 14:14:58'),
(10952, 1439, 1739, 0, 'normal', '2025-11-18 14:14:58'),
(10953, 1439, 1740, 0, 'normal', '2025-11-18 14:14:58'),
(10954, 1439, 1741, 0, 'normal', '2025-11-18 14:14:58'),
(10955, 1439, 1742, 0, 'normal', '2025-11-18 14:14:58'),
(10956, 1439, 1743, 0, 'normal', '2025-11-18 14:14:58'),
(10957, 1439, 1744, 0, 'normal', '2025-11-18 14:14:58'),
(10958, 1439, 1745, 0, 'normal', '2025-11-18 14:14:58'),
(10959, 1439, 1686, 0, 'normal', '2025-11-18 14:14:58'),
(10960, 1439, 1687, 0, 'normal', '2025-11-18 14:14:58'),
(10961, 1439, 1688, 0, 'normal', '2025-11-18 14:14:58'),
(10962, 1439, 1689, 0, 'normal', '2025-11-18 14:14:58'),
(10963, 1439, 1690, 0, 'normal', '2025-11-18 14:14:58'),
(10964, 1439, 1691, 0, 'normal', '2025-11-18 14:14:58'),
(10965, 1439, 1692, 0, 'normal', '2025-11-18 14:14:58'),
(10966, 1439, 1693, 0, 'normal', '2025-11-18 14:14:58'),
(10967, 1439, 1694, 0, 'normal', '2025-11-18 14:14:58'),
(10968, 1439, 1695, 0, 'normal', '2025-11-18 14:14:58'),
(10969, 1439, 1696, 0, 'normal', '2025-11-18 14:14:58'),
(10970, 1439, 1697, 0, 'normal', '2025-11-18 14:14:58'),
(10971, 1439, 1698, 0, 'normal', '2025-11-18 14:14:58'),
(10972, 1439, 1699, 0, 'normal', '2025-11-18 14:14:58'),
(10973, 1439, 1700, 0, 'normal', '2025-11-18 14:14:58'),
(10974, 1439, 1701, 0, 'normal', '2025-11-18 14:14:58'),
(10975, 1439, 1702, 0, 'normal', '2025-11-18 14:14:58'),
(10976, 1439, 1703, 0, 'normal', '2025-11-18 14:14:58'),
(10977, 1439, 1704, 0, 'normal', '2025-11-18 14:14:58'),
(10978, 1439, 1705, 0, 'normal', '2025-11-18 14:14:58'),
(10979, 1442, 1766, 0, 'normal', '2025-11-18 14:15:06'),
(10980, 1442, 1767, 0, 'normal', '2025-11-18 14:15:06'),
(10981, 1442, 1768, 0, 'normal', '2025-11-18 14:15:06'),
(10982, 1442, 1769, 0, 'normal', '2025-11-18 14:15:06'),
(10983, 1442, 1770, 0, 'normal', '2025-11-18 14:15:06'),
(10984, 1442, 1771, 0, 'normal', '2025-11-18 14:15:06'),
(10985, 1442, 1772, 0, 'normal', '2025-11-18 14:15:06'),
(10986, 1442, 1773, 0, 'normal', '2025-11-18 14:15:06'),
(10987, 1442, 1774, 0, 'normal', '2025-11-18 14:15:06'),
(10988, 1442, 1775, 0, 'normal', '2025-11-18 14:15:06'),
(10989, 1442, 1776, 0, 'normal', '2025-11-18 14:15:06'),
(10990, 1442, 1777, 0, 'normal', '2025-11-18 14:15:06'),
(10991, 1442, 1778, 0, 'normal', '2025-11-18 14:15:06'),
(10992, 1442, 1779, 0, 'normal', '2025-11-18 14:15:06'),
(10993, 1442, 1780, 0, 'normal', '2025-11-18 14:15:06'),
(10994, 1442, 1781, 0, 'normal', '2025-11-18 14:15:06'),
(10995, 1442, 1782, 0, 'normal', '2025-11-18 14:15:06'),
(10996, 1442, 1783, 0, 'normal', '2025-11-18 14:15:06'),
(10997, 1442, 1784, 0, 'normal', '2025-11-18 14:15:06'),
(10998, 1442, 1785, 0, 'normal', '2025-11-18 14:15:06'),
(10999, 1442, 1746, 0, 'normal', '2025-11-18 14:15:06'),
(11000, 1442, 1747, 0, 'normal', '2025-11-18 14:15:06'),
(11001, 1442, 1748, 0, 'normal', '2025-11-18 14:15:06'),
(11002, 1442, 1749, 0, 'normal', '2025-11-18 14:15:06'),
(11003, 1442, 1750, 0, 'normal', '2025-11-18 14:15:06'),
(11004, 1442, 1751, 0, 'normal', '2025-11-18 14:15:06'),
(11005, 1442, 1752, 0, 'normal', '2025-11-18 14:15:06'),
(11006, 1442, 1753, 0, 'normal', '2025-11-18 14:15:06'),
(11007, 1442, 1754, 0, 'normal', '2025-11-18 14:15:06'),
(11008, 1442, 1755, 0, 'normal', '2025-11-18 14:15:06'),
(11009, 1442, 1756, 0, 'normal', '2025-11-18 14:15:06'),
(11010, 1442, 1757, 0, 'normal', '2025-11-18 14:15:06'),
(11011, 1442, 1758, 0, 'normal', '2025-11-18 14:15:06'),
(11012, 1442, 1759, 0, 'normal', '2025-11-18 14:15:06'),
(11013, 1442, 1760, 0, 'normal', '2025-11-18 14:15:06'),
(11014, 1442, 1761, 0, 'normal', '2025-11-18 14:15:06'),
(11015, 1442, 1762, 0, 'normal', '2025-11-18 14:15:06'),
(11016, 1442, 1763, 0, 'normal', '2025-11-18 14:15:06'),
(11017, 1442, 1764, 0, 'normal', '2025-11-18 14:15:06'),
(11018, 1442, 1765, 0, 'normal', '2025-11-18 14:15:06'),
(11019, 1440, 1686, 0, 'normal', '2025-11-18 14:15:25'),
(11020, 1440, 1687, 0, 'normal', '2025-11-18 14:15:25'),
(11021, 1440, 1688, 0, 'normal', '2025-11-18 14:15:25'),
(11022, 1440, 1689, 0, 'normal', '2025-11-18 14:15:25'),
(11023, 1440, 1690, 0, 'normal', '2025-11-18 14:15:25'),
(11024, 1440, 1691, 0, 'normal', '2025-11-18 14:15:25'),
(11025, 1440, 1692, 0, 'normal', '2025-11-18 14:15:25'),
(11026, 1440, 1693, 0, 'normal', '2025-11-18 14:15:25'),
(11027, 1440, 1694, 0, 'normal', '2025-11-18 14:15:25'),
(11028, 1440, 1695, 0, 'normal', '2025-11-18 14:15:25'),
(11029, 1440, 1696, 0, 'normal', '2025-11-18 14:15:25'),
(11030, 1440, 1697, 0, 'normal', '2025-11-18 14:15:25'),
(11031, 1440, 1698, 0, 'normal', '2025-11-18 14:15:25'),
(11032, 1440, 1699, 0, 'normal', '2025-11-18 14:15:25'),
(11033, 1440, 1700, 0, 'normal', '2025-11-18 14:15:25'),
(11034, 1440, 1701, 0, 'normal', '2025-11-18 14:15:25'),
(11035, 1440, 1702, 0, 'normal', '2025-11-18 14:15:25'),
(11036, 1440, 1703, 0, 'normal', '2025-11-18 14:15:25'),
(11037, 1440, 1704, 0, 'normal', '2025-11-18 14:15:25'),
(11038, 1440, 1705, 0, 'normal', '2025-11-18 14:15:25'),
(11039, 1440, 1666, 0, 'normal', '2025-11-18 14:15:25'),
(11040, 1440, 1667, 0, 'normal', '2025-11-18 14:15:25'),
(11041, 1440, 1668, 0, 'normal', '2025-11-18 14:15:25'),
(11042, 1440, 1669, 0, 'normal', '2025-11-18 14:15:25'),
(11043, 1440, 1670, 0, 'normal', '2025-11-18 14:15:25'),
(11044, 1440, 1671, 0, 'normal', '2025-11-18 14:15:25'),
(11045, 1440, 1672, 0, 'normal', '2025-11-18 14:15:25'),
(11046, 1440, 1673, 0, 'normal', '2025-11-18 14:15:25'),
(11047, 1440, 1674, 0, 'normal', '2025-11-18 14:15:25'),
(11048, 1440, 1675, 0, 'normal', '2025-11-18 14:15:25'),
(11049, 1440, 1676, 0, 'normal', '2025-11-18 14:15:25'),
(11050, 1440, 1677, 0, 'normal', '2025-11-18 14:15:25'),
(11051, 1440, 1678, 0, 'normal', '2025-11-18 14:15:25'),
(11052, 1440, 1679, 0, 'normal', '2025-11-18 14:15:25'),
(11053, 1440, 1680, 0, 'normal', '2025-11-18 14:15:25'),
(11054, 1440, 1681, 0, 'normal', '2025-11-18 14:15:25'),
(11055, 1440, 1682, 0, 'normal', '2025-11-18 14:15:25'),
(11056, 1440, 1683, 0, 'normal', '2025-11-18 14:15:25'),
(11057, 1440, 1684, 0, 'normal', '2025-11-18 14:15:25'),
(11058, 1440, 1685, 0, 'normal', '2025-11-18 14:15:25'),
(11059, 1443, 1746, 0, 'normal', '2025-11-18 14:15:33'),
(11060, 1443, 1747, 0, 'normal', '2025-11-18 14:15:33'),
(11061, 1443, 1748, 0, 'normal', '2025-11-18 14:15:33'),
(11062, 1443, 1749, 0, 'normal', '2025-11-18 14:15:33'),
(11063, 1443, 1750, 0, 'normal', '2025-11-18 14:15:33'),
(11064, 1443, 1751, 0, 'normal', '2025-11-18 14:15:33'),
(11065, 1443, 1752, 0, 'normal', '2025-11-18 14:15:33'),
(11066, 1443, 1753, 0, 'normal', '2025-11-18 14:15:33'),
(11067, 1443, 1754, 0, 'normal', '2025-11-18 14:15:33'),
(11068, 1443, 1755, 0, 'normal', '2025-11-18 14:15:33'),
(11069, 1443, 1756, 0, 'normal', '2025-11-18 14:15:33'),
(11070, 1443, 1757, 0, 'normal', '2025-11-18 14:15:33'),
(11071, 1443, 1758, 0, 'normal', '2025-11-18 14:15:33'),
(11072, 1443, 1759, 0, 'normal', '2025-11-18 14:15:33'),
(11073, 1443, 1760, 0, 'normal', '2025-11-18 14:15:33'),
(11074, 1443, 1761, 0, 'normal', '2025-11-18 14:15:33'),
(11075, 1443, 1762, 0, 'normal', '2025-11-18 14:15:33'),
(11076, 1443, 1763, 0, 'normal', '2025-11-18 14:15:33'),
(11077, 1443, 1764, 0, 'normal', '2025-11-18 14:15:33'),
(11078, 1443, 1765, 0, 'normal', '2025-11-18 14:15:33'),
(11079, 1443, 1786, 0, 'normal', '2025-11-18 14:15:33'),
(11080, 1443, 1787, 0, 'normal', '2025-11-18 14:15:33'),
(11081, 1443, 1788, 0, 'normal', '2025-11-18 14:15:33'),
(11082, 1443, 1789, 0, 'normal', '2025-11-18 14:15:33'),
(11083, 1443, 1790, 0, 'normal', '2025-11-18 14:15:33'),
(11084, 1443, 1791, 0, 'normal', '2025-11-18 14:15:33'),
(11085, 1443, 1792, 0, 'normal', '2025-11-18 14:15:33'),
(11086, 1443, 1793, 0, 'normal', '2025-11-18 14:15:33'),
(11087, 1443, 1794, 0, 'normal', '2025-11-18 14:15:33'),
(11088, 1443, 1795, 0, 'normal', '2025-11-18 14:15:33'),
(11089, 1443, 1796, 0, 'normal', '2025-11-18 14:15:33'),
(11090, 1443, 1797, 0, 'normal', '2025-11-18 14:15:33'),
(11091, 1443, 1798, 0, 'normal', '2025-11-18 14:15:33'),
(11092, 1443, 1799, 0, 'normal', '2025-11-18 14:15:33'),
(11093, 1443, 1800, 0, 'normal', '2025-11-18 14:15:33'),
(11094, 1443, 1801, 0, 'normal', '2025-11-18 14:15:33'),
(11095, 1443, 1802, 0, 'normal', '2025-11-18 14:15:33'),
(11096, 1443, 1803, 0, 'normal', '2025-11-18 14:15:33'),
(11097, 1443, 1804, 0, 'normal', '2025-11-18 14:15:33'),
(11098, 1443, 1805, 0, 'normal', '2025-11-18 14:15:33'),
(11099, 1444, 1666, 0, 'normal', '2025-11-18 22:09:40'),
(11100, 1444, 1667, 0, 'normal', '2025-11-18 22:09:40'),
(11101, 1444, 1668, 0, 'normal', '2025-11-18 22:09:40'),
(11102, 1444, 1669, 0, 'normal', '2025-11-18 22:09:40'),
(11103, 1444, 1670, 0, 'normal', '2025-11-18 22:09:40'),
(11104, 1444, 1671, 0, 'normal', '2025-11-18 22:09:40'),
(11105, 1444, 1672, 0, 'normal', '2025-11-18 22:09:40'),
(11106, 1444, 1673, 0, 'normal', '2025-11-18 22:09:40'),
(11107, 1444, 1674, 0, 'normal', '2025-11-18 22:09:40'),
(11108, 1444, 1675, 0, 'normal', '2025-11-18 22:09:40'),
(11109, 1444, 1676, 0, 'normal', '2025-11-18 22:09:40'),
(11110, 1444, 1677, 0, 'normal', '2025-11-18 22:09:40'),
(11111, 1444, 1678, 0, 'normal', '2025-11-18 22:09:40'),
(11112, 1444, 1679, 0, 'normal', '2025-11-18 22:09:40'),
(11113, 1444, 1680, 0, 'normal', '2025-11-18 22:09:40'),
(11114, 1444, 1681, 0, 'normal', '2025-11-18 22:09:40'),
(11115, 1444, 1682, 0, 'normal', '2025-11-18 22:09:40'),
(11116, 1444, 1683, 0, 'normal', '2025-11-18 22:09:40'),
(11117, 1444, 1684, 0, 'normal', '2025-11-18 22:09:40'),
(11118, 1444, 1685, 0, 'normal', '2025-11-18 22:09:40'),
(11119, 1444, 1766, 0, 'normal', '2025-11-18 22:09:40'),
(11120, 1444, 1767, 0, 'normal', '2025-11-18 22:09:40'),
(11121, 1444, 1768, 0, 'normal', '2025-11-18 22:09:40'),
(11122, 1444, 1769, 0, 'normal', '2025-11-18 22:09:40'),
(11123, 1444, 1770, 0, 'normal', '2025-11-18 22:09:40'),
(11124, 1444, 1771, 0, 'normal', '2025-11-18 22:09:40'),
(11125, 1444, 1772, 0, 'normal', '2025-11-18 22:09:40'),
(11126, 1444, 1773, 0, 'normal', '2025-11-18 22:09:40'),
(11127, 1444, 1774, 0, 'normal', '2025-11-18 22:09:40'),
(11128, 1444, 1775, 0, 'normal', '2025-11-18 22:09:40'),
(11129, 1444, 1776, 0, 'normal', '2025-11-18 22:09:40'),
(11130, 1444, 1777, 0, 'normal', '2025-11-18 22:09:40'),
(11131, 1444, 1778, 0, 'normal', '2025-11-18 22:09:40'),
(11132, 1444, 1779, 0, 'normal', '2025-11-18 22:09:40'),
(11133, 1444, 1780, 0, 'normal', '2025-11-18 22:09:40'),
(11134, 1444, 1781, 0, 'normal', '2025-11-18 22:09:40'),
(11135, 1444, 1782, 0, 'normal', '2025-11-18 22:09:40'),
(11136, 1444, 1783, 0, 'normal', '2025-11-18 22:09:40'),
(11137, 1444, 1784, 0, 'normal', '2025-11-18 22:09:40'),
(11138, 1444, 1785, 0, 'normal', '2025-11-18 22:09:40');

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
  `jornada_zona` int(11) DEFAULT NULL COMMENT 'Número de jornada dentro de la zona',
  `penales_definido` tinyint(1) DEFAULT 0 COMMENT 'Indica si el partido se definió por penales (para eliminatorias)',
  `tipo_partido` varchar(20) DEFAULT 'normal' COMMENT 'Valores: normal, octavos, cuartos, semifinal, final, tercer_puesto'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `partidos`
--

INSERT INTO `partidos` (`id`, `fecha_id`, `equipo_local_id`, `equipo_visitante_id`, `cancha_id`, `fecha_partido`, `hora_partido`, `goles_local`, `goles_visitante`, `estado`, `minuto_actual`, `minuto_periodo`, `segundos_transcurridos`, `tiempo_actual`, `iniciado_at`, `finalizado_at`, `observaciones`, `zona_id`, `fase_eliminatoria_id`, `numero_llave`, `origen_local`, `origen_visitante`, `goles_local_penales`, `goles_visitante_penales`, `tipo_torneo`, `jornada_zona`, `penales_definido`, `tipo_partido`) VALUES
(1438, 2160, 329, 326, 27, '2025-11-18', '13:30:00', 2, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-18 14:12:29', '', 99, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 1, 0, 'normal'),
(1439, 2161, 326, 332, 17, '2025-11-25', '13:30:00', 3, 2, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-18 14:14:58', '', 99, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 2, 0, 'normal'),
(1440, 2162, 332, 329, 27, '2025-12-02', '13:30:00', 3, 3, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-18 14:15:25', '', 99, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 3, 0, 'normal'),
(1441, 2163, 330, 331, 27, '2025-11-18', '14:00:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-18 14:14:33', '', 100, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 1, 0, 'normal'),
(1442, 2164, 331, 325, 17, '2025-11-25', '14:00:00', 0, 0, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-18 14:15:06', '', 100, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 2, 0, 'normal'),
(1443, 2165, 325, 330, 27, '2025-12-02', '14:00:00', 0, 1, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-18 14:15:33', '', 100, NULL, NULL, NULL, NULL, NULL, NULL, 'zona', 3, 0, 'normal'),
(1444, 2166, 329, 331, 19, '2025-11-25', '13:30:00', 2, 2, 'finalizado', 0, 0, 0, 'primer_tiempo', NULL, '2025-11-18 22:09:40', '', NULL, 87, 1, '1° Zona A', '2° Zona B', NULL, NULL, 'eliminatoria', NULL, 0, 'normal'),
(1445, 2166, 326, 330, 19, '2025-11-25', '14:00:00', 0, 0, 'programado', 0, 0, 0, 'primer_tiempo', NULL, NULL, NULL, NULL, 87, 2, '2° Zona A', '1° Zona B', NULL, NULL, 'eliminatoria', NULL, 0, 'normal');

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
  `equipo_tipo` enum('local','visitante') NOT NULL,
  `orden` int(11) NOT NULL COMMENT 'Orden del penal (1-5 iniciales, 6+ muerte súbita)',
  `convertido` tinyint(1) NOT NULL DEFAULT 0 COMMENT '1 = gol, 0 = errado',
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
  `fecha_sancion` date NOT NULL,
  `partido_origen_id` int(11) DEFAULT NULL COMMENT 'ID del partido donde se generó la sanción. Las sanciones no se cumplen en el mismo partido donde se generaron.'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `sanciones`
--

INSERT INTO `sanciones` (`id`, `jugador_id`, `campeonato_id`, `tipo_torneo`, `tipo`, `partidos_suspension`, `partidos_cumplidos`, `descripcion`, `activa`, `fecha_sancion`, `partido_origen_id`) VALUES
(117, 1681, NULL, 'normal', 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-11-17', NULL),
(118, 1689, 16, 'zona', 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-11-17', NULL),
(119, 1706, 16, 'zona', 'roja_directa', 1, 1, 'Tarjeta roja directa', 0, '2025-11-17', NULL),
(120, 1691, 16, 'zona', 'roja_directa', 2, 2, 'Tarjeta roja directa (mínimo 2 fechas)', 0, '2025-11-18', 1431),
(121, 1701, 16, 'zona', 'roja_directa', 2, 2, 'Tarjeta roja directa (mínimo 2 fechas)', 0, '2025-11-18', 1432),
(122, 1799, 16, 'zona', 'roja_directa', 2, 2, 'Tarjeta roja directa (mínimo 2 fechas)', 0, '2025-11-18', 1435),
(123, 1716, 16, 'zona', 'roja_directa', 2, 1, 'Tarjeta roja directa (mínimo 2 fechas)', 1, '2025-11-18', 1438),
(124, 1803, 16, 'zona', 'roja_directa', 2, 1, 'Tarjeta roja directa (mínimo 2 fechas)', 1, '2025-11-18', 1441);

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
-- Estructura Stand-in para la vista `v_estadisticas_jugador_por_tipo`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_estadisticas_jugador_por_tipo` (
`jugador_id` int(11)
,`jugador_dni` varchar(20)
,`jugador_nombre` varchar(150)
,`partidos_largos` bigint(21)
,`goles_largos` decimal(22,0)
,`amarillas_largos` decimal(22,0)
,`partidos_zonales` bigint(21)
,`goles_zonales` decimal(22,0)
,`amarillas_zonales` decimal(22,0)
,`rojas_totales` decimal(22,0)
);

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
(99, 34, 'Zona A', 1, 1),
(100, 34, 'Zona B', 2, 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `campeonatos`
--
ALTER TABLE `campeonatos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo_campeonato` (`tipo_campeonato`);

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
-- Indices de la tabla `empates_pendientes`
--
ALTER TABLE `empates_pendientes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_formato_id` (`formato_id`),
  ADD KEY `idx_zona_id` (`zona_id`),
  ADD KEY `idx_resuelto` (`resuelto`);

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
  ADD KEY `idx_campeonato_id` (`campeonato_id`),
  ADD KEY `idx_es_torneo_zonal` (`es_torneo_zonal`,`campeonato_id`);

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
  ADD KEY `idx_tipo_torneo` (`tipo_torneo`),
  ADD KEY `idx_partidos_penales` (`penales_definido`,`tipo_partido`);

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
  ADD KEY `idx_penales_partido_equipo` (`partido_id`,`equipo_tipo`);

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
  ADD KEY `idx_tipo_torneo` (`tipo_torneo`),
  ADD KEY `idx_partido_origen_id` (`partido_origen_id`);

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `campeonatos_formato`
--
ALTER TABLE `campeonatos_formato`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT de la tabla `canchas`
--
ALTER TABLE `canchas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=39;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT de la tabla `codigos_cancha`
--
ALTER TABLE `codigos_cancha`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1622;

--
-- AUTO_INCREMENT de la tabla `empates_pendientes`
--
ALTER TABLE `empates_pendientes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `equipos`
--
ALTER TABLE `equipos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=334;

--
-- AUTO_INCREMENT de la tabla `equipos_zonas`
--
ALTER TABLE `equipos_zonas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=349;

--
-- AUTO_INCREMENT de la tabla `eventos_partido`
--
ALTER TABLE `eventos_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=454;

--
-- AUTO_INCREMENT de la tabla `fases_eliminatorias`
--
ALTER TABLE `fases_eliminatorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=91;

--
-- AUTO_INCREMENT de la tabla `fechas`
--
ALTER TABLE `fechas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2167;

--
-- AUTO_INCREMENT de la tabla `horarios_canchas`
--
ALTER TABLE `horarios_canchas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=317;

--
-- AUTO_INCREMENT de la tabla `jugadores`
--
ALTER TABLE `jugadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1807;

--
-- AUTO_INCREMENT de la tabla `jugadores_equipos_historial`
--
ALTER TABLE `jugadores_equipos_historial`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1714;

--
-- AUTO_INCREMENT de la tabla `jugadores_partido`
--
ALTER TABLE `jugadores_partido`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11139;

--
-- AUTO_INCREMENT de la tabla `log_sanciones`
--
ALTER TABLE `log_sanciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `partidos`
--
ALTER TABLE `partidos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1446;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=125;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=101;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_estadisticas_jugador_por_tipo`
--
DROP TABLE IF EXISTS `v_estadisticas_jugador_por_tipo`;

CREATE ALGORITHM=UNDEFINED DEFINER=`u959527289_Nuevo`@`127.0.0.1` SQL SECURITY DEFINER VIEW `v_estadisticas_jugador_por_tipo`  AS SELECT `j`.`id` AS `jugador_id`, `j`.`dni` AS `jugador_dni`, `j`.`apellido_nombre` AS `jugador_nombre`, count(distinct case when `ep`.`es_torneo_zonal` = 0 and `p`.`estado` = 'finalizado' then `jp`.`partido_id` end) AS `partidos_largos`, sum(case when `ep`.`es_torneo_zonal` = 0 and `ep`.`tipo_evento` = 'gol' then 1 else 0 end) AS `goles_largos`, sum(case when `ep`.`es_torneo_zonal` = 0 and `ep`.`tipo_evento` = 'amarilla' then 1 else 0 end) AS `amarillas_largos`, count(distinct case when `ep`.`es_torneo_zonal` = 1 and `p`.`estado` = 'finalizado' then `jp`.`partido_id` end) AS `partidos_zonales`, sum(case when `ep`.`es_torneo_zonal` = 1 and `ep`.`tipo_evento` = 'gol' then 1 else 0 end) AS `goles_zonales`, sum(case when `ep`.`es_torneo_zonal` = 1 and `ep`.`tipo_evento` = 'amarilla' then 1 else 0 end) AS `amarillas_zonales`, sum(case when `ep`.`tipo_evento` = 'roja' then 1 else 0 end) AS `rojas_totales` FROM (((`jugadores` `j` left join `jugadores_partido` `jp` on(`j`.`id` = `jp`.`jugador_id`)) left join `partidos` `p` on(`jp`.`partido_id` = `p`.`id`)) left join `eventos_partido` `ep` on(`j`.`id` = `ep`.`jugador_id` and `ep`.`partido_id` = `p`.`id`)) GROUP BY `j`.`id`, `j`.`dni`, `j`.`apellido_nombre` ;

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
-- Filtros para la tabla `empates_pendientes`
--
ALTER TABLE `empates_pendientes`
  ADD CONSTRAINT `fk_empates_formato` FOREIGN KEY (`formato_id`) REFERENCES `campeonatos_formato` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_empates_zona` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE CASCADE;

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
  ADD CONSTRAINT `penales_partido_ibfk_1` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `penales_partido_ibfk_2` FOREIGN KEY (`jugador_id`) REFERENCES `jugadores` (`id`) ON DELETE CASCADE;

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
