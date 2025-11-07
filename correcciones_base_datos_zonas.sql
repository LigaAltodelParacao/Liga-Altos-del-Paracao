-- =====================================================
-- CORRECCIONES PARA BASE DE DATOS TORNEOS POR ZONAS
-- Basado en sistema_futbol.sql
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- =====================================================
-- 1. AGREGAR COLUMNAS DE TARJETAS A equipos_zonas
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
-- 2. AGREGAR categoria_id A campeonatos_formato
-- =====================================================

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'campeonatos_formato'
      AND COLUMN_NAME = 'categoria_id'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `campeonatos_formato` ADD COLUMN `categoria_id` int(11) DEFAULT NULL',
    'SELECT "Columna categoria_id ya existe en campeonatos_formato" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar foreign key si no existe
SET @fk_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
    WHERE TABLE_SCHEMA = DATABASE() 
      AND TABLE_NAME = 'campeonatos_formato' 
      AND CONSTRAINT_NAME = 'campeonatos_formato_ibfk_categoria'
);
SET @sql = IF(@fk_exists = 0, 
    'ALTER TABLE `campeonatos_formato` ADD CONSTRAINT `campeonatos_formato_ibfk_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL',
    'SELECT "FK campeonatos_formato_ibfk_categoria ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar índices de clasificación detallados (si no existen)
SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'campeonatos_formato'
      AND COLUMN_NAME = 'primeros_clasifican'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `campeonatos_formato` ADD COLUMN `primeros_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT ''Cantidad de primeros que clasifican (por zona)''',
    'SELECT "Columna primeros_clasifican ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'campeonatos_formato'
      AND COLUMN_NAME = 'segundos_clasifican'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `campeonatos_formato` ADD COLUMN `segundos_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT ''Cantidad de segundos que clasifican''',
    'SELECT "Columna segundos_clasifican ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'campeonatos_formato'
      AND COLUMN_NAME = 'terceros_clasifican'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `campeonatos_formato` ADD COLUMN `terceros_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT ''Cantidad de terceros que clasifican''',
    'SELECT "Columna terceros_clasifican ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'campeonatos_formato'
      AND COLUMN_NAME = 'cuartos_clasifican'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `campeonatos_formato` ADD COLUMN `cuartos_clasifican` int(11) NOT NULL DEFAULT 0 COMMENT ''Cantidad de cuartos que clasifican''',
    'SELECT "Columna cuartos_clasifican ya existe" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 3. AGREGAR CAMPOS A fechas PARA ZONAS
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

-- Agregar índices a fechas (si no existen)
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

-- Agregar foreign keys a fechas (solo si las tablas existen)
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

-- =====================================================
-- 4. AGREGAR COLUMNA generada A fases_eliminatorias
-- =====================================================

SET @col_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'fases_eliminatorias'
      AND COLUMN_NAME = 'generada'
);
SET @sql = IF(@col_exists = 0, 
    'ALTER TABLE `fases_eliminatorias` ADD COLUMN `generada` tinyint(1) DEFAULT 0 COMMENT ''Indica si ya se generaron los partidos''',
    'SELECT "Columna generada ya existe en fases_eliminatorias" AS info'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 5. CREAR VISTA v_tabla_posiciones_zona
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

