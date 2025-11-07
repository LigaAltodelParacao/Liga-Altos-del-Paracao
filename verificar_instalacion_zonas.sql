-- =====================================================
-- SCRIPT DE VERIFICACIÓN
-- Ejecuta esto primero para ver qué columnas e índices ya existen
-- =====================================================

-- Verificar columnas en partidos
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'partidos'
  AND COLUMN_NAME IN (
    'zona_id',
    'fase_eliminatoria_id',
    'numero_llave',
    'origen_local',
    'origen_visitante',
    'goles_local_penales',
    'goles_visitante_penales',
    'tipo_torneo',
    'jornada_zona'
  )
ORDER BY COLUMN_NAME;

-- Verificar índices en partidos
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'partidos'
  AND INDEX_NAME IN (
    'idx_zona_id',
    'idx_fase_eliminatoria_id',
    'idx_tipo_torneo'
  )
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- Verificar columnas en fechas
SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'fechas'
  AND COLUMN_NAME IN (
    'zona_id',
    'fase_eliminatoria_id',
    'tipo_fecha'
  )
ORDER BY COLUMN_NAME;

-- Verificar índices en fechas
SELECT 
    INDEX_NAME,
    COLUMN_NAME,
    SEQ_IN_INDEX
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'fechas'
  AND INDEX_NAME IN (
    'idx_zona_id',
    'idx_fase_eliminatoria_id'
  )
ORDER BY INDEX_NAME, SEQ_IN_INDEX;

-- Verificar si las tablas nuevas existen
SELECT 
    TABLE_NAME,
    TABLE_TYPE
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME IN (
    'campeonatos_formato',
    'zonas',
    'equipos_zonas',
    'fases_eliminatorias'
  )
ORDER BY TABLE_NAME;

-- Verificar si la vista existe
SELECT 
    TABLE_NAME,
    VIEW_DEFINITION
FROM INFORMATION_SCHEMA.VIEWS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'v_tabla_posiciones_zona';

