-- =====================================================
-- CORRECCIÓN: Sistema de Empates y Fases Eliminatorias
-- =====================================================
-- Este script crea la estructura necesaria para manejar
-- empates después de todos los criterios de desempate
-- =====================================================

-- 1. Crear tabla para empates pendientes de decisión
CREATE TABLE IF NOT EXISTS `empates_clasificacion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formato_id` int(11) NOT NULL,
  `zona_id` int(11) NOT NULL,
  `posicion` int(11) NOT NULL COMMENT 'Posición en la que están empatados (1=primero, 2=segundo, etc.)',
  `equipos_ids` text NOT NULL COMMENT 'IDs de los equipos empatados separados por coma',
  `equipos_nombres` text NOT NULL COMMENT 'Nombres de los equipos empatados separados por coma',
  `equipo_seleccionado_id` int(11) DEFAULT NULL COMMENT 'ID del equipo seleccionado por el admin para pasar',
  `resuelto` tinyint(1) DEFAULT 0 COMMENT '1 si ya se resolvió el empate',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resolved_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_formato_id` (`formato_id`),
  KEY `idx_zona_id` (`zona_id`),
  KEY `idx_resuelto` (`resuelto`),
  CONSTRAINT `fk_empates_formato` FOREIGN KEY (`formato_id`) REFERENCES `campeonatos_formato` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_empates_zona` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Agregar campo para indicar si hay empates pendientes en formato
ALTER TABLE `campeonatos_formato` 
ADD COLUMN IF NOT EXISTS `tiene_empates_pendientes` TINYINT(1) DEFAULT 0 
COMMENT 'Indica si hay empates pendientes de decisión manual' 
AFTER `activo`;

-- 3. Agregar índice si no existe
ALTER TABLE `campeonatos_formato`
ADD INDEX IF NOT EXISTS `idx_tiene_empates_pendientes` (`tiene_empates_pendientes`);

-- =====================================================
-- FIN DE LA CORRECCIÓN
-- =====================================================

