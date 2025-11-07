-- =====================================================
-- ESQUEMA INTEGRADO PARA TORNEOS POR ZONAS
-- Versión SEGURA que verifica existencia antes de crear
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- =====================================================
-- FUNCIÓN PARA VERIFICAR SI UNA COLUMNA EXISTE
-- =====================================================
DELIMITER $$

DROP PROCEDURE IF EXISTS `add_column_if_not_exists`$$
CREATE PROCEDURE `add_column_if_not_exists`(
    IN table_name VARCHAR(128),
    IN column_name VARCHAR(128),
    IN column_definition TEXT
)
BEGIN
    DECLARE column_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO column_exists
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name
      AND COLUMN_NAME = column_name;
    
    IF column_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', table_name, '` ADD COLUMN `', column_name, '` ', column_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DROP PROCEDURE IF EXISTS `add_index_if_not_exists`$$
CREATE PROCEDURE `add_index_if_not_exists`(
    IN table_name VARCHAR(128),
    IN index_name VARCHAR(128),
    IN index_definition TEXT
)
BEGIN
    DECLARE index_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO index_exists
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name
      AND INDEX_NAME = index_name;
    
    IF index_exists = 0 THEN
        SET @sql = CONCAT('CREATE INDEX `', index_name, '` ON `', table_name, '` (', index_definition, ')');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- AGREGAR CAMPOS A PARTIDOS
-- =====================================================
CALL add_column_if_not_exists('partidos', 'zona_id', 'int(11) DEFAULT NULL COMMENT ''ID de zona si es partido de fase de grupos''');
CALL add_column_if_not_exists('partidos', 'fase_eliminatoria_id', 'int(11) DEFAULT NULL COMMENT ''ID de fase eliminatoria si es partido eliminatorio''');
CALL add_column_if_not_exists('partidos', 'numero_llave', 'int(11) DEFAULT NULL COMMENT ''Número de llave en fase eliminatoria''');
CALL add_column_if_not_exists('partidos', 'origen_local', 'varchar(255) DEFAULT NULL COMMENT ''Origen del equipo local (ej: "1° Zona A")''');
CALL add_column_if_not_exists('partidos', 'origen_visitante', 'varchar(255) DEFAULT NULL COMMENT ''Origen del equipo visitante''');
CALL add_column_if_not_exists('partidos', 'goles_local_penales', 'int(11) DEFAULT NULL COMMENT ''Goles en penales (equipo local)''');
CALL add_column_if_not_exists('partidos', 'goles_visitante_penales', 'int(11) DEFAULT NULL COMMENT ''Goles en penales (equipo visitante)''');
CALL add_column_if_not_exists('partidos', 'tipo_torneo', 'enum(''normal'',''zona'',''eliminatoria'') DEFAULT ''normal'' COMMENT ''Tipo de partido''');
CALL add_column_if_not_exists('partidos', 'jornada_zona', 'int(11) DEFAULT NULL COMMENT ''Número de jornada dentro de la zona''');

-- =====================================================
-- AGREGAR ÍNDICES A PARTIDOS
-- =====================================================
CALL add_index_if_not_exists('partidos', 'idx_zona_id', '`zona_id`');
CALL add_index_if_not_exists('partidos', 'idx_fase_eliminatoria_id', '`fase_eliminatoria_id`');
CALL add_index_if_not_exists('partidos', 'idx_tipo_torneo', '`tipo_torneo`');

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
-- AGREGAR CAMPOS A FECHAS
-- =====================================================
CALL add_column_if_not_exists('fechas', 'zona_id', 'int(11) DEFAULT NULL COMMENT ''ID de zona si esta fecha es específica de una zona''');
CALL add_column_if_not_exists('fechas', 'fase_eliminatoria_id', 'int(11) DEFAULT NULL COMMENT ''ID de fase eliminatoria si esta fecha es de eliminatorias''');
CALL add_column_if_not_exists('fechas', 'tipo_fecha', 'enum(''normal'',''zona'',''eliminatoria'') DEFAULT ''normal'' COMMENT ''Tipo de fecha''');

-- =====================================================
-- AGREGAR ÍNDICES A FECHAS
-- =====================================================
CALL add_index_if_not_exists('fechas', 'idx_zona_id', '`zona_id`');
CALL add_index_if_not_exists('fechas', 'idx_fase_eliminatoria_id', '`fase_eliminatoria_id`');

-- =====================================================
-- AGREGAR FOREIGN KEYS (solo si las tablas existen)
-- =====================================================
-- Nota: Estas foreign keys solo se pueden agregar después de crear las tablas zonas y fases_eliminatorias
-- Si ya existen, ignorar el error

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

-- =====================================================
-- LIMPIAR PROCEDIMIENTOS TEMPORALES (opcional)
-- =====================================================
-- DROP PROCEDURE IF EXISTS `add_column_if_not_exists`;
-- DROP PROCEDURE IF EXISTS `add_index_if_not_exists`;

