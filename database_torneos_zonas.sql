-- =====================================================
-- ESQUEMA DE BASE DE DATOS PARA TORNEOS POR ZONAS
-- Sistema completo de gestión de torneos con zonas y eliminatorias
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- TABLA: campeonatos_formato
-- Almacena la configuración del torneo por zonas
-- =====================================================
CREATE TABLE IF NOT EXISTS `campeonatos_formato` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `campeonato_id` int(11) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `tipo_formato` enum('mixto','eliminatoria') DEFAULT 'mixto',
  `cantidad_zonas` int(11) NOT NULL DEFAULT 0,
  `equipos_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT 'Total de equipos que clasifican',
  `primeros_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad de primeros que clasifican (por zona)',
  `segundos_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad de segundos que clasifican',
  `terceros_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad de terceros que clasifican',
  `cuartos_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT 'Cantidad de cuartos que clasifican',
  `tiene_octavos` tinyint(1) DEFAULT 0,
  `tiene_cuartos` tinyint(1) DEFAULT 1,
  `tiene_semifinal` tinyint(1) DEFAULT 1,
  `tiene_tercer_puesto` tinyint(1) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `campeonato_id` (`campeonato_id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `campeonatos_formato_ibfk_1` FOREIGN KEY (`campeonato_id`) REFERENCES `campeonatos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `campeonatos_formato_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: zonas
-- Almacena las zonas del torneo
-- =====================================================
CREATE TABLE IF NOT EXISTS `zonas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formato_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 1,
  `activa` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `formato_id` (`formato_id`),
  CONSTRAINT `zonas_ibfk_1` FOREIGN KEY (`formato_id`) REFERENCES `campeonatos_formato` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: equipos_zonas
-- Relación entre equipos y zonas
-- =====================================================
CREATE TABLE IF NOT EXISTS `equipos_zonas` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zona_id` int(11) NOT NULL,
  `equipo_id` int(11) NOT NULL,
  `posicion` int(11) DEFAULT NULL COMMENT 'Posición inicial en la zona',
  `puntos` int(11) DEFAULT 0,
  `partidos_jugados` int(11) DEFAULT 0,
  `partidos_ganados` int(11) DEFAULT 0,
  `partidos_empatados` int(11) DEFAULT 0,
  `partidos_perdidos` int(11) DEFAULT 0,
  `goles_favor` int(11) DEFAULT 0,
  `goles_contra` int(11) DEFAULT 0,
  `diferencia_gol` int(11) DEFAULT 0,
  `tarjetas_amarillas` int(11) DEFAULT 0,
  `tarjetas_rojas` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_equipo_zona` (`zona_id`,`equipo_id`),
  KEY `equipo_id` (`equipo_id`),
  CONSTRAINT `equipos_zonas_ibfk_1` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `equipos_zonas_ibfk_2` FOREIGN KEY (`equipo_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: partidos_zona
-- Partidos de la fase de grupos (zonas)
-- =====================================================
CREATE TABLE IF NOT EXISTS `partidos_zona` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `zona_id` int(11) NOT NULL,
  `equipo_local_id` int(11) NOT NULL,
  `equipo_visitante_id` int(11) NOT NULL,
  `fecha_numero` int(11) NOT NULL COMMENT 'Número de jornada',
  `fecha_partido` date DEFAULT NULL,
  `hora_partido` time DEFAULT NULL,
  `cancha_id` int(11) DEFAULT NULL,
  `goles_local` int(11) DEFAULT NULL,
  `goles_visitante` int(11) DEFAULT NULL,
  `estado` enum('pendiente','programado','en_curso','finalizado','suspendido') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `finalizado_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `zona_id` (`zona_id`),
  KEY `equipo_local_id` (`equipo_local_id`),
  KEY `equipo_visitante_id` (`equipo_visitante_id`),
  KEY `cancha_id` (`cancha_id`),
  KEY `fecha_numero` (`fecha_numero`),
  CONSTRAINT `partidos_zona_ibfk_1` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partidos_zona_ibfk_2` FOREIGN KEY (`equipo_local_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partidos_zona_ibfk_3` FOREIGN KEY (`equipo_visitante_id`) REFERENCES `equipos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partidos_zona_ibfk_4` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: fases_eliminatorias
-- Fases eliminatorias (octavos, cuartos, semis, final)
-- =====================================================
CREATE TABLE IF NOT EXISTS `fases_eliminatorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formato_id` int(11) NOT NULL,
  `nombre` varchar(50) NOT NULL COMMENT 'octavos, cuartos, semifinal, tercer_puesto, final',
  `orden` int(11) NOT NULL DEFAULT 1,
  `activa` tinyint(1) DEFAULT 0 COMMENT 'Se activa cuando se generan los partidos',
  `generada` tinyint(1) DEFAULT 0 COMMENT 'Indica si ya se generaron los partidos',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `formato_id` (`formato_id`),
  CONSTRAINT `fases_eliminatorias_ibfk_1` FOREIGN KEY (`formato_id`) REFERENCES `campeonatos_formato` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLA: partidos_eliminatorios
-- Partidos de las fases eliminatorias
-- =====================================================
CREATE TABLE IF NOT EXISTS `partidos_eliminatorios` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `fase_id` int(11) NOT NULL,
  `numero_llave` int(11) NOT NULL COMMENT 'Número de llave (1, 2, 3, etc.)',
  `equipo_local_id` int(11) DEFAULT NULL,
  `equipo_visitante_id` int(11) DEFAULT NULL,
  `origen_local` varchar(255) DEFAULT NULL COMMENT 'De dónde viene el equipo local (ej: "1° Zona A")',
  `origen_visitante` varchar(255) DEFAULT NULL COMMENT 'De dónde viene el equipo visitante',
  `partido_anterior_local_id` int(11) DEFAULT NULL COMMENT 'ID del partido anterior que determina el local',
  `partido_anterior_visitante_id` int(11) DEFAULT NULL COMMENT 'ID del partido anterior que determina el visitante',
  `fecha_partido` date DEFAULT NULL,
  `hora_partido` time DEFAULT NULL,
  `cancha_id` int(11) DEFAULT NULL,
  `goles_local` int(11) DEFAULT NULL,
  `goles_visitante` int(11) DEFAULT NULL,
  `goles_local_penales` int(11) DEFAULT NULL,
  `goles_visitante_penales` int(11) DEFAULT NULL,
  `estado` enum('pendiente','programado','en_curso','finalizado','suspendido') DEFAULT 'pendiente',
  `observaciones` text DEFAULT NULL,
  `finalizado_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fase_id` (`fase_id`),
  KEY `equipo_local_id` (`equipo_local_id`),
  KEY `equipo_visitante_id` (`equipo_visitante_id`),
  KEY `cancha_id` (`cancha_id`),
  KEY `numero_llave` (`numero_llave`),
  CONSTRAINT `partidos_eliminatorios_ibfk_1` FOREIGN KEY (`fase_id`) REFERENCES `fases_eliminatorias` (`id`) ON DELETE CASCADE,
  CONSTRAINT `partidos_eliminatorios_ibfk_2` FOREIGN KEY (`equipo_local_id`) REFERENCES `equipos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `partidos_eliminatorios_ibfk_3` FOREIGN KEY (`equipo_visitante_id`) REFERENCES `equipos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `partidos_eliminatorios_ibfk_4` FOREIGN KEY (`cancha_id`) REFERENCES `canchas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VISTA: v_tabla_posiciones_zona
-- Vista para calcular tabla de posiciones por zona
-- =====================================================
CREATE OR REPLACE VIEW `v_tabla_posiciones_zona` AS
SELECT 
    ez.zona_id,
    z.nombre as zona,
    z.formato_id,
    ez.equipo_id,
    e.nombre as equipo,
    e.logo,
    ez.puntos as pts,
    ez.partidos_jugados as pj,
    ez.partidos_ganados as pg,
    ez.partidos_empatados as pe,
    ez.partidos_perdidos as pp,
    ez.goles_favor as gf,
    ez.goles_contra as gc,
    ez.diferencia_gol as dif,
    ez.tarjetas_amarillas as ta,
    ez.tarjetas_rojas as tr,
    -- Cálculo de enfrentamiento directo (para desempate)
    (SELECT 
        COUNT(*) 
     FROM partidos_zona pz 
     WHERE pz.zona_id = ez.zona_id 
       AND ((pz.equipo_local_id = ez.equipo_id AND pz.equipo_visitante_id = e2.id) 
            OR (pz.equipo_visitante_id = ez.equipo_id AND pz.equipo_local_id = e2.id))
       AND pz.estado = 'finalizado'
       AND (
           (pz.equipo_local_id = ez.equipo_id AND pz.goles_local > pz.goles_visitante)
           OR (pz.equipo_visitante_id = ez.equipo_id AND pz.goles_visitante > pz.goles_local)
       )
    ) as victorias_directas
FROM equipos_zonas ez
JOIN zonas z ON ez.zona_id = z.id
JOIN equipos e ON ez.equipo_id = e.id
LEFT JOIN equipos e2 ON e2.id != ez.equipo_id
WHERE z.activa = 1
GROUP BY ez.zona_id, ez.equipo_id
ORDER BY ez.zona_id, ez.puntos DESC, ez.diferencia_gol DESC, ez.goles_favor DESC, ez.tarjetas_rojas ASC, ez.tarjetas_amarillas ASC;

-- =====================================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- =====================================================
CREATE INDEX idx_partidos_zona_fecha ON partidos_zona(fecha_numero, zona_id);
CREATE INDEX idx_partidos_zona_estado ON partidos_zona(estado);
CREATE INDEX idx_partidos_eliminatorios_fase ON partidos_eliminatorios(fase_id, numero_llave);
CREATE INDEX idx_partidos_eliminatorios_estado ON partidos_eliminatorios(estado);

COMMIT;

