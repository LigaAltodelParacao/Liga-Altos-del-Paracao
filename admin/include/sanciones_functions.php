<?php
/**
 * Funciones para gestión de sanciones
 * Archivo requerido por control_fechas.php
 * 
 * NOTA: Las funciones isLoggedIn(), hasPermission(), redirect() y logActivity()
 * ya están definidas en config.php, por lo que no se redefinen aquí para evitar
 * errores de "Cannot redeclare function"
 */

/**
 * Procesar eventos y crear sanciones automáticamente
 */
function procesarEventosYCrearSanciones($partido_id, $db) {
    try {
        // Obtener todos los eventos del partido
        $stmt = $db->prepare("
            SELECT e.*, j.equipo_id, j.id as jugador_id_completo
            FROM eventos_partido e
            JOIN jugadores j ON e.jugador_id = j.id
            WHERE e.partido_id = ?
        ");
        $stmt->execute([$partido_id]);
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sanciones_creadas = [];
        
        // Procesar tarjetas rojas directas
        foreach ($eventos as $evento) {
            if ($evento['tipo_evento'] === 'roja' && empty($evento['observaciones'])) {
                // Tarjeta roja directa
                $stmt = $db->prepare("
                    INSERT INTO sanciones (jugador_id, tipo, partidos_suspension, descripcion, fecha_sancion)
                    VALUES (?, 'roja_directa', 1, 'Tarjeta roja directa', CURDATE())
                    ON DUPLICATE KEY UPDATE activa = 1
                ");
                $stmt->execute([$evento['jugador_id_completo']]);
                $sanciones_creadas[] = $evento['jugador_id_completo'];
            }
        }
        
        // Contar amarillas por jugador
        $jugadores_amarillas = [];
        foreach ($eventos as $evento) {
            if ($evento['tipo_evento'] === 'amarilla') {
                if (!isset($jugadores_amarillas[$evento['jugador_id_completo']])) {
                    $jugadores_amarillas[$evento['jugador_id_completo']] = 0;
                }
                $jugadores_amarillas[$evento['jugador_id_completo']]++;
            }
        }
        
        // Crear sanciones por 4 amarillas acumuladas
        foreach ($jugadores_amarillas as $jugador_id => $cantidad) {
            if ($cantidad >= 4) {
                // Verificar si ya tiene sanción activa por 4 amarillas
                $stmt = $db->prepare("
                    SELECT id FROM sanciones 
                    WHERE jugador_id = ? AND tipo = 'amarillas_acumuladas' AND activa = 1
                ");
                $stmt->execute([$jugador_id]);
                
                if (!$stmt->fetch()) {
                    $stmt = $db->prepare("
                        INSERT INTO sanciones (jugador_id, tipo, partidos_suspension, descripcion, fecha_sancion)
                        VALUES (?, 'amarillas_acumuladas', 1, '4 amarillas acumuladas en el campeonato', CURDATE())
                    ");
                    $stmt->execute([$jugador_id]);
                    $sanciones_creadas[] = $jugador_id;
                }
            }
        }
        
        return [
            'success' => true,
            'message' => 'Sanciones procesadas correctamente',
            'sanciones_creadas' => $sanciones_creadas
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al procesar sanciones: ' . $e->getMessage()
        ];
    }
}

/**
 * Cumplir sanciones automáticamente
 */
function cumplirSancionesAutomaticas($partido_id, $db) {
    try {
        // Obtener equipos que jugaron este partido
        $stmt = $db->prepare("
            SELECT equipo_local_id, equipo_visitante_id 
            FROM partidos 
            WHERE id = ?
        ");
        $stmt->execute([$partido_id]);
        $partido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$partido) {
            return ['success' => false, 'message' => 'Partido no encontrado'];
        }
        
        // Obtener sanciones activas
        $stmt = $db->prepare("
            SELECT s.*, j.equipo_id, e.nombre as equipo_nombre, j.apellido_nombre
            FROM sanciones s
            JOIN jugadores j ON s.jugador_id = j.id
            JOIN equipos e ON j.equipo_id = e.id
            WHERE s.activa = 1 
            AND j.equipo_id IN (?, ?)
            AND s.partidos_cumplidos < s.partidos_suspension
        ");
        $stmt->execute([$partido['equipo_local_id'], $partido['equipo_visitante_id']]);
        $sanciones_activas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $sanciones_actualizadas = 0;
        $detalle = [];
        
        foreach ($sanciones_activas as $sancion) {
            // Verificar si el jugador participó en el partido
            $stmt = $db->prepare("
                SELECT COUNT(*) as participo
                FROM jugadores_partido jp
                JOIN partidos p ON jp.partido_id = p.id
                WHERE jp.jugador_id = ? AND p.id = ?
            ");
            $stmt->execute([$sancion['jugador_id'], $partido_id]);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado['participo'] > 0) {
                // Incrementar partidos cumplidos
                $nuevo_cumplido = $sancion['partidos_cumplidos'] + 1;
                $finalizada = $nuevo_cumplido >= $sancion['partidos_suspension'];
                
                $stmt = $db->prepare("
                    UPDATE sanciones 
                    SET partidos_cumplidos = ?,
                        activa = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nuevo_cumplido, $finalizada ? 0 : 1, $sancion['id']]);
                
                $sanciones_actualizadas++;
                $detalle[] = [
                    'jugador' => $sancion['apellido_nombre'],
                    'equipo' => $sancion['equipo_nombre'],
                    'cumplidos' => $nuevo_cumplido,
                    'total' => $sancion['partidos_suspension'],
                    'finalizada' => $finalizada
                ];
                
                // Registrar en log
                if ($finalizada) {
                    logActivity("Sanción completada para {$sancion['apellido_nombre']} ({$sancion['equipo_nombre']})");
                }
            }
        }
        
        return [
            'success' => true,
            'actualizados' => $sanciones_actualizadas,
            'detalle' => $detalle
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error al cumplir sanciones: ' . $e->getMessage()
        ];
    }
}

/**
 * Obtener jugadores sancionados
 */
function getJugadoresSancionados($equipo_id, $db) {
    $stmt = $db->prepare("
        SELECT j.*, s.tipo, s.partidos_suspension, s.partidos_cumplidos, s.descripcion
        FROM jugadores j
        JOIN sanciones s ON j.id = s.jugador_id
        WHERE j.equipo_id = ? 
        AND s.activa = 1 
        AND s.partidos_cumplidos < s.partidos_suspension
        ORDER BY j.apellido_nombre
    ");
    $stmt->execute([$equipo_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>