-- =====================================================
-- ESQUEMA INTEGRADO PARA TORNEOS POR ZONAS
-- Usa las tablas existentes: partidos, eventos_partido, fechas
-- =====================================================
-- IMPORTANTE: Ejecutar este script en orden
-- Si alguna columna ya existe, ignorar el error y continuar

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- =====================================================
-- MODIFICAR TABLA PARTIDOS PARA SOPORTAR ZONAS
-- =====================================================

-- Agregar campos a partidos (ignorar errores si ya existen)
ALTER TABLE `partidos` 
ADD COLUMN `zona_id` int(11) DEFAULT NULL COMMENT 'ID de zona si es partido de fase de grupos';

ALTER TABLE `partidos` 
ADD COLUMN `fase_eliminatoria_id` int(11) DEFAULT NULL COMMENT 'ID de fase eliminatoria si es partido eliminatorio';

ALTER TABLE `partidos` 
ADD COLUMN `numero_llave` int(11) DEFAULT NULL COMMENT 'Número de llave en fase eliminatoria';

ALTER TABLE `partidos` 
ADD COLUMN `origen_local` varchar(255) DEFAULT NULL COMMENT 'Origen del equipo local (ej: "1° Zona A")';

ALTER TABLE `partidos` 
ADD COLUMN `origen_visitante` varchar(255) DEFAULT NULL COMMENT 'Origen del equipo visitante';

ALTER TABLE `partidos` 
ADD COLUMN `goles_local_penales` int(11) DEFAULT NULL COMMENT 'Goles en penales (equipo local)';

ALTER TABLE `partidos` 
ADD COLUMN `goles_visitante_penales` int(11) DEFAULT NULL COMMENT 'Goles en penales (equipo visitante)';

ALTER TABLE `partidos` 
ADD COLUMN `tipo_torneo` enum('normal','zona','eliminatoria') DEFAULT 'normal' COMMENT 'Tipo de partido';

ALTER TABLE `partidos` 
ADD COLUMN `jornada_zona` int(11) DEFAULT NULL COMMENT 'Número de jornada dentro de la zona';

-- Agregar índices (ignorar errores si ya existen)
CREATE INDEX `idx_zona_id` ON `partidos` (`zona_id`);
CREATE INDEX `idx_fase_eliminatoria_id` ON `partidos` (`fase_eliminatoria_id`);
CREATE INDEX `idx_tipo_torneo` ON `partidos` (`tipo_torneo`);

-- =====================================================
-- TABLA: campeonatos_formato
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
-- TABLA: fases_eliminatorias
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
-- VISTA: v_tabla_posiciones_zona
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
    ez.tarjetas_rojas as tr
FROM equipos_zonas ez
JOIN zonas z ON ez.zona_id = z.id
JOIN equipos e ON ez.equipo_id = e.id
WHERE z.activa = 1
ORDER BY ez.zona_id, ez.puntos DESC, ez.diferencia_gol DESC, ez.goles_favor DESC, ez.tarjetas_rojas ASC, ez.tarjetas_amarillas ASC;

-- =====================================================
-- MODIFICAR TABLA FECHAS PARA SOPORTAR ZONAS
-- =====================================================

-- Agregar campos a fechas (ignorar errores si ya existen)
ALTER TABLE `fechas`
ADD COLUMN `zona_id` int(11) DEFAULT NULL COMMENT 'ID de zona si esta fecha es específica de una zona';

ALTER TABLE `fechas`
ADD COLUMN `fase_eliminatoria_id` int(11) DEFAULT NULL COMMENT 'ID de fase eliminatoria si esta fecha es de eliminatorias';

ALTER TABLE `fechas`
ADD COLUMN `tipo_fecha` enum('normal','zona','eliminatoria') DEFAULT 'normal' COMMENT 'Tipo de fecha';

-- Agregar índices (ignorar errores si ya existen)
CREATE INDEX `idx_zona_id` ON `fechas` (`zona_id`);
CREATE INDEX `idx_fase_eliminatoria_id` ON `fechas` (`fase_eliminatoria_id`);

-- Agregar foreign keys (ignorar errores si ya existen)
-- Nota: Solo se pueden agregar si las tablas zonas y fases_eliminatorias ya existen
ALTER TABLE `partidos`
ADD CONSTRAINT `partidos_ibfk_zona` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE SET NULL;

ALTER TABLE `partidos`
ADD CONSTRAINT `partidos_ibfk_fase` FOREIGN KEY (`fase_eliminatoria_id`) REFERENCES `fases_eliminatorias` (`id`) ON DELETE SET NULL;

ALTER TABLE `fechas`
ADD CONSTRAINT `fechas_ibfk_zona` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE SET NULL;

ALTER TABLE `fechas`
ADD CONSTRAINT `fechas_ibfk_fase` FOREIGN KEY (`fase_eliminatoria_id`) REFERENCES `fases_eliminatorias` (`id`) ON DELETE SET NULL;

COMMIT;

