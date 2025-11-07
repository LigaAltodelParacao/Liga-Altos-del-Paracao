-- =====================================================
-- AGREGAR COLUMNAS DE TARJETAS A equipos_zonas
-- Si la tabla ya existe pero le faltan estas columnas
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

-- Ahora recrear la vista
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

