-- =====================================================
-- CORRECCIÓN COMPLETA: Base de datos para tipo_campeonato
-- =====================================================
-- Este script asegura que todos los campos necesarios existen
-- y que tienen valores correctos

-- 1. Agregar campo tipo_campeonato si no existe
ALTER TABLE `campeonatos` 
ADD COLUMN IF NOT EXISTS `tipo_campeonato` ENUM('largo', 'zonal') DEFAULT 'largo' 
COMMENT 'Tipo de campeonato: largo (Apertura/Clausura) o zonal (Torneo Nocturno, etc.)' 
AFTER `es_torneo_nocturno`;

-- 2. Actualizar los valores existentes basándose en es_torneo_nocturno
-- Si es_torneo_nocturno = 1, entonces es 'zonal', sino es 'largo'
UPDATE `campeonatos` 
SET `tipo_campeonato` = CASE 
    WHEN `es_torneo_nocturno` = 1 THEN 'zonal' 
    ELSE 'largo' 
END
WHERE `tipo_campeonato` IS NULL;

-- 3. Asegurar que no haya valores NULL
UPDATE `campeonatos` 
SET `tipo_campeonato` = 'largo'
WHERE `tipo_campeonato` IS NULL;

-- 4. Agregar campo es_torneo_zonal a eventos_partido si no existe
ALTER TABLE `eventos_partido`
ADD COLUMN IF NOT EXISTS `es_torneo_zonal` TINYINT(1) DEFAULT 0 
COMMENT 'Indica si este evento pertenece a un torneo por zonas (1) o torneo largo (0)' 
AFTER `tipo_partido`;

-- 5. Actualizar eventos_partido existentes con es_torneo_zonal
UPDATE `eventos_partido` ep
LEFT JOIN `campeonatos` c ON ep.campeonato_id = c.id
SET ep.`es_torneo_zonal` = CASE 
    WHEN c.`tipo_campeonato` = 'zonal' THEN 1
    WHEN ep.`tipo_partido` IN ('zona', 'eliminatoria') THEN 1
    ELSE 0
END
WHERE ep.`es_torneo_zonal` IS NULL;

-- 6. Agregar índices si no existen
ALTER TABLE `campeonatos`
ADD INDEX IF NOT EXISTS `idx_tipo_campeonato` (`tipo_campeonato`);

ALTER TABLE `eventos_partido`
ADD INDEX IF NOT EXISTS `idx_es_torneo_zonal` (`es_torneo_zonal`, `campeonato_id`);

-- =====================================================
-- FIN DE LA CORRECCIÓN
-- =====================================================

