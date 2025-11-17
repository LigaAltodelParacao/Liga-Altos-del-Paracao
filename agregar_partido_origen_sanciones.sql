-- =====================================================
-- AGREGAR CAMPO partido_origen_id A TABLA sanciones
-- =====================================================
-- Este script agrega el campo partido_origen_id para
-- rastrear en qué partido se generó cada sanción y
-- evitar que se cumpla en el mismo partido
-- =====================================================

-- Agregar campo partido_origen_id si no existe
ALTER TABLE `sanciones` 
ADD COLUMN IF NOT EXISTS `partido_origen_id` INT(11) NULL DEFAULT NULL 
COMMENT 'ID del partido donde se generó la sanción. Las sanciones no se cumplen en el mismo partido donde se generaron.' 
AFTER `fecha_sancion`;

-- Agregar índice para mejorar las consultas
ALTER TABLE `sanciones`
ADD INDEX IF NOT EXISTS `idx_partido_origen_id` (`partido_origen_id`);

-- Agregar foreign key si no existe
ALTER TABLE `sanciones`
ADD CONSTRAINT IF NOT EXISTS `fk_sanciones_partido_origen` 
FOREIGN KEY (`partido_origen_id`) REFERENCES `partidos` (`id`) 
ON DELETE SET NULL 
ON UPDATE CASCADE;

-- =====================================================
-- FIN DEL SCRIPT
-- =====================================================

