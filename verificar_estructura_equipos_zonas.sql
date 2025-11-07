-- =====================================================
-- VERIFICAR ESTRUCTURA DE equipos_zonas
-- Ejecuta esto para ver qué columnas tiene actualmente
-- =====================================================

SELECT 
    COLUMN_NAME,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'equipos_zonas'
ORDER BY ORDINAL_POSITION;

