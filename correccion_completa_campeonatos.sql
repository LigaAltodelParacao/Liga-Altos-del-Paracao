-- =====================================================
-- CORRECCIÓN COMPLETA: Sistema de Campeonatos
-- =====================================================
-- Este script corrige todos los problemas relacionados con
-- la creación de campeonatos y tipos de campeonato
-- =====================================================

-- 1. Agregar campo tipo_campeonato a la tabla campeonatos si no existe
-- Nota: MariaDB no soporta IF NOT EXISTS en ALTER TABLE ADD COLUMN
-- Por lo tanto, verificamos primero si existe
-- IMPORTANTE: NO ponemos DEFAULT para forzar que siempre se especifique el tipo

SET @dbname = DATABASE();
SET @tablename = 'campeonatos';
SET @columnname = 'tipo_campeonato';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD COLUMN ', @columnname, ' ENUM(\'largo\', \'zonal\') NULL COMMENT \'Tipo de campeonato: largo (Apertura/Clausura) o zonal (Torneo Nocturno, etc.)\' AFTER `es_torneo_nocturno`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 2. Actualizar los valores existentes basándose en es_torneo_nocturno
-- Si es_torneo_nocturno = 1, entonces es 'zonal', sino es 'largo'
UPDATE `campeonatos` 
SET `tipo_campeonato` = CASE 
    WHEN `es_torneo_nocturno` = 1 THEN 'zonal' 
    ELSE 'largo' 
END
WHERE `tipo_campeonato` IS NULL;

-- 3. Asegurar que no haya valores NULL (por si acaso)
UPDATE `campeonatos` 
SET `tipo_campeonato` = 'largo'
WHERE `tipo_campeonato` IS NULL;

-- 4. Modificar el campo para que NO tenga DEFAULT (debe ser obligatorio)
-- Primero eliminamos el DEFAULT y hacemos el campo NOT NULL sin valor por defecto
-- Esto fuerza a que siempre se deba especificar el tipo al crear
ALTER TABLE `campeonatos` 
MODIFY COLUMN `tipo_campeonato` ENUM('largo', 'zonal') NOT NULL 
COMMENT 'Tipo de campeonato: largo (Apertura/Clausura) o zonal (Torneo Nocturno, etc.)';

-- 4.1. Si hay algún campeonato sin tipo_campeonato (NULL), establecerlo basándose en es_torneo_nocturno
-- Esto es solo para datos existentes, no para nuevos
UPDATE `campeonatos` 
SET `tipo_campeonato` = CASE 
    WHEN `es_torneo_nocturno` = 1 THEN 'zonal' 
    ELSE 'largo' 
END
WHERE `tipo_campeonato` IS NULL OR `tipo_campeonato` = '';

-- 5. Agregar índices si no existen
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (INDEX_NAME = 'idx_tipo_campeonato')
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename, ' ADD INDEX `idx_tipo_campeonato` (`tipo_campeonato`)')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 6. Verificar que campeonatos_formato tenga categoria_id si es necesario
-- (Esto es importante para las fases eliminatorias)
SET @tablename2 = 'campeonatos_formato';
SET @columnname2 = 'categoria_id';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename2)
      AND (COLUMN_NAME = @columnname2)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename2, ' ADD COLUMN ', @columnname2, ' INT(11) DEFAULT NULL COMMENT \'ID de la categoría asociada\' AFTER `activo`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- 7. Asegurar que las fases eliminatorias tengan el campo 'generada'
SET @tablename3 = 'fases_eliminatorias';
SET @columnname3 = 'generada';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename3)
      AND (COLUMN_NAME = @columnname3)
  ) > 0,
  'SELECT 1',
  CONCAT('ALTER TABLE ', @tablename3, ' ADD COLUMN ', @columnname3, ' TINYINT(1) DEFAULT 0 COMMENT \'Indica si ya se generaron los partidos\' AFTER `activa`')
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- =====================================================
-- FIN DE LA CORRECCIÓN
-- =====================================================
-- IMPORTANTE: Después de ejecutar este script, asegúrate de que:
-- 1. Todos los campeonatos existentes tengan tipo_campeonato definido
-- 2. El formulario de creación siempre pregunte por el tipo
-- 3. Las fases eliminatorias se generen correctamente
-- =====================================================

