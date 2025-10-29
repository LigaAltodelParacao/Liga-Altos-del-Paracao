<?php
/**
 * Funciones para el manejo automático de sanciones
 * Guardar como: include/sanciones_functions.php
 * 
 * IMPORTANTE: Incluir este archivo donde sea necesario con:
 * require_once __DIR__ . '/include/sanciones_functions.php';
 */

/**
 * Cumple automáticamente fechas de sanción cuando un equipo juega
 * Se debe llamar cuando un partido se marca como finalizado
 * 
 * @param int $partido_id ID del partido finalizado
 * @param PDO $db Conexión a la base de datos
 * @return array ['success' => bool, 'message' => string, 'actualizados' => int]
 */
function cumplirSancionesAutomaticas($partido_id, $db) {
    try {
        // Obtener información del partido
        $stmt = $db->prepare("
            SELECT p.equipo_local_id, p.equipo_visitante_id, p.fecha_id
            FROM partidos p
            WHERE p.id = ? AND p.estado = 'finalizado'
        ");
        $stmt->execute([$partido_id]);
        $partido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$partido) {
            return false;
        }
        
        $equipos = [$partido['equipo_local_id'], $partido['equipo_visitante_id']];
        
        // Para cada equipo, buscar jugadores con sanciones activas
        foreach ($equipos as $equipo_id) {
            // Obtener jugadores sancionados del equipo
            $stmt = $db->prepare("
                SELECT s.id, s.jugador_id, s.partidos_suspension, s.partidos_cumplidos
                FROM sanciones s
                JOIN jugadores j ON s.jugador_id = j.id
                WHERE j.equipo_id = ? 
                AND s.activa = 1 
                AND s.partidos_cumplidos < s.partidos_suspension
            ");
            $stmt->execute([$equipo_id]);
            $sanciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cumplir una fecha para cada jugador sancionado
            foreach ($sanciones as $sancion) {
                $nuevo_cumplidos = $sancion['partidos_cumplidos'] + 1;
                $activa = ($nuevo_cumplidos >= $sancion['partidos_suspension']) ? 0 : 1;
                
                // Actualizar la sanción
                $stmt = $db->prepare("
                    UPDATE sanciones 
                    SET partidos_cumplidos = ?, activa = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nuevo_cumplidos, $activa, $sancion['id']]);
                
                // Registrar en log (opcional pero recomendado)
                registrarLogSancion($sancion['id'], $partido_id, $nuevo_cumplidos, $db);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error en cumplirSancionesAutomaticas: " . $e->getMessage());
        return false;
    }
}

/**
 * Registra un log de cumplimiento de sanción
 * Crear esta tabla en la base de datos (ver SQL al final)
 */
function registrarLogSancion($sancion_id, $partido_id, $fechas_cumplidas, $db) {
    try {
        $stmt = $db->prepare("
            INSERT INTO log_sanciones (sancion_id, partido_id, fechas_cumplidas, fecha_registro)
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$sancion_id, $partido_id, $fechas_cumplidas]);
        return true;
    } catch (Exception $e) {
        error_log("Error en registrarLogSancion: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica si un jugador puede jugar (no está sancionado)
 */
function jugadorPuedeJugar($jugador_id, $db) {
    $stmt = $db->prepare("
        SELECT COUNT(*) as sanciones_activas
        FROM sanciones
        WHERE jugador_id = ? AND activa = 1
    ");
    $stmt->execute([$jugador_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['sanciones_activas'] == 0;
}

/**
 * Obtiene el detalle de sanciones activas de un jugador
 */
function obtenerSancionesActivas($jugador_id, $db) {
    $stmt = $db->prepare("
        SELECT 
            s.*,
            (s.partidos_suspension - s.partidos_cumplidos) as fechas_restantes
        FROM sanciones s
        WHERE s.jugador_id = ? AND s.activa = 1
        ORDER BY s.fecha_sancion DESC
    ");
    $stmt->execute([$jugador_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * SQL para crear la tabla de log (ejecutar una sola vez en la base de datos)
 */
/*
CREATE TABLE IF NOT EXISTS `log_sanciones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sancion_id` int(11) NOT NULL,
  `partido_id` int(11) NOT NULL,
  `fechas_cumplidas` int(11) NOT NULL,
  `fecha_registro` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `sancion_id` (`sancion_id`),
  KEY `partido_id` (`partido_id`),
  CONSTRAINT `log_sanciones_ibfk_1` FOREIGN KEY (`sancion_id`) REFERENCES `sanciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `log_sanciones_ibfk_2` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
*/

?>