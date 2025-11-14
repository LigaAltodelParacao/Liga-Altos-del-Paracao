-- =====================================================
-- CORRECCIÓN: Sistema de Empates y Sorteo
-- =====================================================
-- Este script crea las tablas y campos necesarios para
-- manejar empates que requieren sorteo manual
-- =====================================================

-- 1. Crear tabla para almacenar empates pendientes de resolución
CREATE TABLE IF NOT EXISTS `empates_pendientes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `formato_id` int(11) NOT NULL,
  `zona_id` int(11) NOT NULL,
  `posicion` int(11) NOT NULL COMMENT 'Posición en la que están empatados (1=primero, 2=segundo, etc.)',
  `equipos_ids` text NOT NULL COMMENT 'JSON con los IDs de los equipos empatados',
  `equipos_nombres` text NOT NULL COMMENT 'JSON con los nombres de los equipos empatados',
  `criterios_aplicados` text DEFAULT NULL COMMENT 'JSON con los criterios de desempate aplicados',
  `equipo_ganador_id` int(11) DEFAULT NULL COMMENT 'ID del equipo que gana por sorteo (se establece manualmente)',
  `resuelto` tinyint(1) DEFAULT 0 COMMENT 'Indica si el empate ya fue resuelto',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `resuelto_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_formato_id` (`formato_id`),
  KEY `idx_zona_id` (`zona_id`),
  KEY `idx_resuelto` (`resuelto`),
  CONSTRAINT `fk_empates_formato` FOREIGN KEY (`formato_id`) REFERENCES `campeonatos_formato` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_empates_zona` FOREIGN KEY (`zona_id`) REFERENCES `zonas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Agregar campo a equipos_zonas para marcar si requiere sorteo
ALTER TABLE `equipos_zonas`
ADD COLUMN IF NOT EXISTS `requiere_sorteo` tinyint(1) DEFAULT 0 COMMENT 'Indica si este equipo está en un empate que requiere sorteo';

-- 3. Agregar campo a campeonatos_formato para indicar si hay empates pendientes
ALTER TABLE `campeonatos_formato`
ADD COLUMN IF NOT EXISTS `empates_pendientes` tinyint(1) DEFAULT 0 COMMENT 'Indica si hay empates pendientes de resolución';

-- =====================================================
-- FIN DE LA CORRECCIÓN
-- =====================================================

