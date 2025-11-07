-- =====================================================
-- SCRIPT PARA COMPLETAR INSTALACIÓN
-- Solo crea lo que falta
-- Ejecuta primero: verificar_instalacion_zonas.sql
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- =====================================================
-- AGREGAR SOLO LAS COLUMNAS QUE FALTAN EN PARTIDOS
-- =====================================================

-- Verificar y agregar zona_id (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND COLUMN_NAME = 'zona_id'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `partidos` ADD COLUMN `zona_id` int(11) DEFAULT NULL COMMENT ''ID de zona si es partido de fase de grupos''',
    'SELECT "Columna zona_id ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar fase_eliminatoria_id (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND COLUMN_NAME = 'fase_eliminatoria_id'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `partidos` ADD COLUMN `fase_eliminatoria_id` int(11) DEFAULT NULL COMMENT ''ID de fase eliminatoria si es partido eliminatorio''',
    'SELECT "Columna fase_eliminatoria_id ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar numero_llave (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND COLUMN_NAME = 'numero_llave'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `partidos` ADD COLUMN `numero_llave` int(11) DEFAULT NULL COMMENT ''Número de llave en fase eliminatoria''',
    'SELECT "Columna numero_llave ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar origen_local (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND COLUMN_NAME = 'origen_local'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `partidos` ADD COLUMN `origen_local` varchar(255) DEFAULT NULL COMMENT ''Origen del equipo local (ej: "1° Zona A")''',
    'SELECT "Columna origen_local ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar origen_visitante (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND COLUMN_NAME = 'origen_visitante'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `partidos` ADD COLUMN `origen_visitante` varchar(255) DEFAULT NULL COMMENT ''Origen del equipo visitante''',
    'SELECT "Columna origen_visitante ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar goles_local_penales (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND COLUMN_NAME = 'goles_local_penales'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `partidos` ADD COLUMN `goles_local_penales` int(11) DEFAULT NULL COMMENT ''Goles en penales (equipo local)''',
    'SELECT "Columna goles_local_penales ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar goles_visitante_penales (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND COLUMN_NAME = 'goles_visitante_penales'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `partidos` ADD COLUMN `goles_visitante_penales` int(11) DEFAULT NULL COMMENT ''Goles en penales (equipo visitante)''',
    'SELECT "Columna goles_visitante_penales ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar tipo_torneo (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND COLUMN_NAME = 'tipo_torneo'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `partidos` ADD COLUMN `tipo_torneo` enum(''normal'',''zona'',''eliminatoria'') DEFAULT ''normal'' COMMENT ''Tipo de partido''',
    'SELECT "Columna tipo_torneo ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar jornada_zona (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND COLUMN_NAME = 'jornada_zona'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `partidos` ADD COLUMN `jornada_zona` int(11) DEFAULT NULL COMMENT ''Número de jornada dentro de la zona''',
    'SELECT "Columna jornada_zona ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- AGREGAR SOLO LOS ÍNDICES QUE FALTAN EN PARTIDOS
-- =====================================================

-- Verificar y crear idx_zona_id (si no existe)
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND INDEX_NAME = 'idx_zona_id'
);
SET @sql = IF(@idx_exists = 0, 
    'CREATE INDEX `idx_zona_id` ON `partidos` (`zona_id`)',
    'SELECT "Índice idx_zona_id ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y crear idx_fase_eliminatoria_id (si no existe)
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND INDEX_NAME = 'idx_fase_eliminatoria_id'
);
SET @sql = IF(@idx_exists = 0, 
    'CREATE INDEX `idx_fase_eliminatoria_id` ON `partidos` (`fase_eliminatoria_id`)',
    'SELECT "Índice idx_fase_eliminatoria_id ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y crear idx_tipo_torneo (si no existe)
SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'partidos'
      AND INDEX_NAME = 'idx_tipo_torneo'
);
SET @sql = IF(@idx_exists = 0, 
    'CREATE INDEX `idx_tipo_torneo` ON `partidos` (`tipo_torneo`)',
    'SELECT "Índice idx_tipo_torneo ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- CREAR TABLAS NUEVAS (solo si no existen)
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
-- VERIFICAR Y AGREGAR COLUMNAS DE TARJETAS (si faltan)
-- =====================================================

-- Verificar y agregar tarjetas_amarillas (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'equipos_zonas'
      AND COLUMN_NAME = 'tarjetas_amarillas'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `equipos_zonas` ADD COLUMN `tarjetas_amarillas` int(11) DEFAULT 0',
    'SELECT "Columna tarjetas_amarillas ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verificar y agregar tarjetas_rojas (si no existe)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'equipos_zonas'
      AND COLUMN_NAME = 'tarjetas_rojas'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `equipos_zonas` ADD COLUMN `tarjetas_rojas` int(11) DEFAULT 0',
    'SELECT "Columna tarjetas_rojas ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- CREAR VISTA (usa COALESCE por si las columnas no existen)
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
    COALESCE(ez.tarjetas_amarillas, 0) as ta,
    COALESCE(ez.tarjetas_rojas, 0) as tr
FROM equipos_zonas ez
JOIN zonas z ON ez.zona_id = z.id
JOIN equipos e ON ez.equipo_id = e.id
WHERE z.activa = 1
ORDER BY ez.zona_id, ez.puntos DESC, ez.diferencia_gol DESC, ez.goles_favor DESC, COALESCE(ez.tarjetas_rojas, 0) ASC, COALESCE(ez.tarjetas_amarillas, 0) ASC;

-- =====================================================
-- AGREGAR COLUMNAS A FECHAS (si no existen)
-- =====================================================

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'fechas'
      AND COLUMN_NAME = 'zona_id'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fechas` ADD COLUMN `zona_id` int(11) DEFAULT NULL COMMENT ''ID de zona si esta fecha es específica de una zona''',
    'SELECT "Columna zona_id ya existe en fechas" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'fechas'
      AND COLUMN_NAME = 'fase_eliminatoria_id'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fechas` ADD COLUMN `fase_eliminatoria_id` int(11) DEFAULT NULL COMMENT ''ID de fase eliminatoria si esta fecha es de eliminatorias''',
    'SELECT "Columna fase_eliminatoria_id ya existe en fechas" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'fechas'
      AND COLUMN_NAME = 'tipo_fecha'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fechas` ADD COLUMN `tipo_fecha` enum(''normal'',''zona'',''eliminatoria'') DEFAULT ''normal'' COMMENT ''Tipo de fecha''',
    'SELECT "Columna tipo_fecha ya existe en fechas" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- AGREGAR ÍNDICES A FECHAS (si no existen)
-- =====================================================

SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'fechas'
      AND INDEX_NAME = 'idx_zona_id'
);
SET @sql = IF(@idx_exists = 0, 
    'CREATE INDEX `idx_zona_id` ON `fechas` (`zona_id`)',
    'SELECT "Índice idx_zona_id ya existe en fechas" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @idx_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'fechas'
      AND INDEX_NAME = 'idx_fase_eliminatoria_id'
);
SET @sql = IF(@idx_exists = 0, 
    'CREATE INDEX `idx_fase_eliminatoria_id` ON `fechas` (`fase_eliminatoria_id`)',
    'SELECT "Índice idx_fase_eliminatoria_id ya existe en fechas" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- AGREGAR FOREIGN KEYS (solo si no existen)
-- =====================================================

SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'partidos' 
      AND CONSTRAINT_NAME = 'partidos_ibfk_zona'
);
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `partidos` ADD CONSTRAINT `partidos_ibfk_zona` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE SET NULL',
    'SELECT "FK partidos_ibfk_zona ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'partidos' 
      AND CONSTRAINT_NAME = 'partidos_ibfk_fase'
);
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `partidos` ADD CONSTRAINT `partidos_ibfk_fase` FOREIGN KEY (`fase_eliminatoria_id`) REFERENCES `fases_eliminatorias` (`id`) ON DELETE SET NULL',
    'SELECT "FK partidos_ibfk_fase ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'fechas' 
      AND CONSTRAINT_NAME = 'fechas_ibfk_zona'
);
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `fechas` ADD CONSTRAINT `fechas_ibfk_zona` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE SET NULL',
    'SELECT "FK fechas_ibfk_zona ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'fechas' 
      AND CONSTRAINT_NAME = 'fechas_ibfk_fase'
);
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `fechas` ADD CONSTRAINT `fechas_ibfk_fase` FOREIGN KEY (`fase_eliminatoria_id`) REFERENCES `fases_eliminatorias` (`id`) ON DELETE SET NULL',
    'SELECT "FK fechas_ibfk_fase ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

