-- =====================================================
-- MIGRACIÓN: Normalización de Torneos Largos vs Zonales
-- =====================================================
-- Este script normaliza la base de datos para distinguir
-- correctamente entre campeonatos largos y campeonatos por zonas,
-- separando sus estadísticas excepto las tarjetas rojas.
-- =====================================================

-- Paso 1: Agregar campo tipo_campeonato a la tabla campeonatos
-- Si es_torneo_nocturno = 1, entonces es 'zonal', sino es 'largo'
ALTER TABLE `campeonatos` 
ADD COLUMN `tipo_campeonato` ENUM('largo', 'zonal') DEFAULT 'largo' 
COMMENT 'Tipo de campeonato: largo (Apertura/Clausura) o zonal (Torneo Nocturno, etc.)' 
AFTER `es_torneo_nocturno`;

-- Actualizar los valores existentes basándose en es_torneo_nocturno
UPDATE `campeonatos` 
SET `tipo_campeonato` = CASE 
    WHEN `es_torneo_nocturno` = 1 THEN 'zonal' 
    ELSE 'largo' 
END;

-- Paso 2: Agregar campo es_torneo_zonal a eventos_partido
-- Este campo indica si el evento pertenece a un torneo zonal
ALTER TABLE `eventos_partido`
ADD COLUMN `es_torneo_zonal` TINYINT(1) DEFAULT 0 
COMMENT 'Indica si este evento pertenece a un torneo por zonas (1) o torneo largo (0)' 
AFTER `tipo_partido`;

-- Paso 3: Modificar el trigger de eventos_partido para determinar es_torneo_zonal
DROP TRIGGER IF EXISTS `trg_eventos_partido_campeonato`;

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
        SELECT tipo_campeonato INTO v_tipo_campeonato
        FROM campeonatos
        WHERE id = v_campeonato_id;
        
        IF v_tipo_campeonato = 'zonal' OR v_tipo_torneo IN ('zona', 'eliminatoria') THEN
            SET v_es_torneo_zonal = 1;
        ELSE
            SET v_es_torneo_zonal = 0;
        END IF;
    ELSE
        -- Si no se puede determinar, usar el tipo_torneo como referencia
        IF v_tipo_torneo IN ('zona', 'eliminatoria') THEN
            SET v_es_torneo_zonal = 1;
        ELSE
            SET v_es_torneo_zonal = 0;
        END IF;
    END IF;
    
    SET NEW.campeonato_id = v_campeonato_id;
    SET NEW.tipo_partido = COALESCE(v_tipo_torneo, 'normal');
    SET NEW.es_torneo_zonal = v_es_torneo_zonal;
END$$
DELIMITER ;

-- Paso 4: Actualizar eventos_partido existentes con es_torneo_zonal
-- Basándose en el tipo_campeonato del campeonato relacionado
UPDATE `eventos_partido` ep
LEFT JOIN `campeonatos` c ON ep.campeonato_id = c.id
SET ep.`es_torneo_zonal` = CASE 
    WHEN c.`tipo_campeonato` = 'zonal' THEN 1
    WHEN ep.`tipo_partido` IN ('zona', 'eliminatoria') THEN 1
    ELSE 0
END
WHERE ep.`es_torneo_zonal` IS NULL OR ep.`es_torneo_zonal` = 0;

-- Paso 5: Agregar campos a jugadores_equipos_historial para separar estadísticas
-- Mantenemos los campos existentes para compatibilidad, pero agregamos campos separados
ALTER TABLE `jugadores_equipos_historial`
ADD COLUMN `partidos_largos` INT(11) DEFAULT 0 COMMENT 'Partidos jugados en torneos largos' AFTER `partidos_jugados`,
ADD COLUMN `partidos_zonales` INT(11) DEFAULT 0 COMMENT 'Partidos jugados en torneos zonales' AFTER `partidos_largos`,
ADD COLUMN `goles_largos` INT(11) DEFAULT 0 COMMENT 'Goles en torneos largos' AFTER `goles`,
ADD COLUMN `goles_zonales` INT(11) DEFAULT 0 COMMENT 'Goles en torneos zonales' AFTER `goles_largos`,
ADD COLUMN `amarillas_largos` INT(11) DEFAULT 0 COMMENT 'Amarillas en torneos largos' AFTER `amarillas`,
ADD COLUMN `amarillas_zonales` INT(11) DEFAULT 0 COMMENT 'Amarillas en torneos zonales' AFTER `amarillas_largos`,
ADD COLUMN `rojas_largos` INT(11) DEFAULT 0 COMMENT 'Rojas en torneos largos (compartidas)' AFTER `rojas`,
ADD COLUMN `rojas_zonales` INT(11) DEFAULT 0 COMMENT 'Rojas en torneos zonales (compartidas)' AFTER `rojas_largos`;

-- Paso 6: Crear índice para mejorar consultas
ALTER TABLE `eventos_partido`
ADD INDEX `idx_es_torneo_zonal` (`es_torneo_zonal`, `campeonato_id`);

ALTER TABLE `campeonatos`
ADD INDEX `idx_tipo_campeonato` (`tipo_campeonato`);

-- Paso 7: Crear vista para facilitar consultas de estadísticas por tipo de torneo
CREATE OR REPLACE VIEW `v_estadisticas_jugador_por_tipo` AS
SELECT 
    j.id AS jugador_id,
    j.dni AS jugador_dni,
    j.apellido_nombre AS jugador_nombre,
    -- Estadísticas de torneos largos
    COUNT(DISTINCT CASE WHEN ep.es_torneo_zonal = 0 THEN jp.partido_id END) AS partidos_largos,
    SUM(CASE WHEN ep.es_torneo_zonal = 0 AND ep.tipo_evento = 'gol' THEN 1 ELSE 0 END) AS goles_largos,
    SUM(CASE WHEN ep.es_torneo_zonal = 0 AND ep.tipo_evento = 'amarilla' THEN 1 ELSE 0 END) AS amarillas_largos,
    SUM(CASE WHEN ep.tipo_evento = 'roja' THEN 1 ELSE 0 END) AS rojas_totales, -- Rojas compartidas
    -- Estadísticas de torneos zonales
    COUNT(DISTINCT CASE WHEN ep.es_torneo_zonal = 1 THEN jp.partido_id END) AS partidos_zonales,
    SUM(CASE WHEN ep.es_torneo_zonal = 1 AND ep.tipo_evento = 'gol' THEN 1 ELSE 0 END) AS goles_zonales,
    SUM(CASE WHEN ep.es_torneo_zonal = 1 AND ep.tipo_evento = 'amarilla' THEN 1 ELSE 0 END) AS amarillas_zonales
FROM jugadores j
LEFT JOIN jugadores_partido jp ON j.id = jp.jugador_id
LEFT JOIN partidos p ON jp.partido_id = p.id AND p.estado = 'finalizado'
LEFT JOIN eventos_partido ep ON j.id = ep.jugador_id
GROUP BY j.id, j.dni, j.apellido_nombre;

-- =====================================================
-- FIN DE LA MIGRACIÓN
-- =====================================================
-- Nota: Después de ejecutar este script, será necesario
-- ejecutar un script de actualización para poblar los
-- nuevos campos de jugadores_equipos_historial con los
-- datos históricos existentes.
-- =====================================================

