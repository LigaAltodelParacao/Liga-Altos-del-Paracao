<?php
/**
 * Funciones para gestión de sanciones
 * Archivo requerido por control_fechas.php
 * 
 * CORRECCIONES IMPLEMENTADAS:
 * 1. Doble amarilla se convierte en roja por doble amarilla con 1 fecha de suspensión
 * 2. Roja directa tiene mínimo 2 fechas de suspensión
 */

/**
 * Procesar eventos y crear sanciones automáticamente
 */
function procesarEventosYCrearSanciones($partido_id, $db) {
    try {
        // Obtener información del partido para determinar tipo_torneo y campeonato_id
        $stmt = $db->prepare("
            SELECT 
                p.tipo_torneo,
                COALESCE(
                    (SELECT c.campeonato_id 
                     FROM fechas f 
                     JOIN categorias c ON f.categoria_id = c.id 
                     WHERE f.id = p.fecha_id AND p.tipo_torneo = 'normal'),
                    (SELECT cf.campeonato_id 
                     FROM zonas z 
                     JOIN campeonatos_formato cf ON z.formato_id = cf.id 
                     WHERE z.id = p.zona_id AND p.tipo_torneo = 'zona'),
                    (SELECT cf.campeonato_id 
                     FROM fases_eliminatorias fe 
                     JOIN campeonatos_formato cf ON fe.formato_id = cf.id 
                     WHERE fe.id = p.fase_eliminatoria_id AND p.tipo_torneo = 'eliminatoria')
                ) as campeonato_id
            FROM partidos p
            WHERE p.id = ?
        ");
        $stmt->execute([$partido_id]);
        $partido_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$partido_info) {
            return [
                'success' => false,
                'message' => 'Partido no encontrado'
            ];
        }
        
        $tipo_torneo = $partido_info['tipo_torneo'] ?? 'normal';
        $campeonato_id = $partido_info['campeonato_id'] ?? null;
        
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
        
        // Procesar tarjetas rojas
        foreach ($eventos as $evento) {
            if ($evento['tipo_evento'] === 'roja') {
                // Determinar tipo de sanción según las observaciones
                $es_doble_amarilla = !empty($evento['observaciones']) && 
                                    (strpos($evento['observaciones'], 'Doble amarilla') !== false || 
                                     strpos($evento['observaciones'], 'doble amarilla') !== false);
                
                $tipo_sancion = $es_doble_amarilla ? 'roja_doble_amarilla' : 'roja_directa';
                $partidos_suspension = $es_doble_amarilla ? 1 : 2; // 1 fecha para doble amarilla, 2 para roja directa
                $descripcion = $es_doble_amarilla ? 
                    'Roja por doble amarilla (1 fecha de suspensión)' : 
                    'Tarjeta roja directa (mínimo 2 fechas)';
                
                // Verificar si ya tiene sanción activa del mismo tipo
                $stmt = $db->prepare("
                    SELECT id FROM sanciones 
                    WHERE jugador_id = ? AND tipo = ? AND activa = 1 
                    AND tipo_torneo = ? AND (campeonato_id = ? OR (campeonato_id IS NULL AND ? IS NULL))
                ");
                $stmt->execute([$evento['jugador_id_completo'], $tipo_sancion, $tipo_torneo, $campeonato_id, $campeonato_id]);
                
                if (!$stmt->fetch()) {
                    // Crear la sanción
                    $stmt = $db->prepare("
                        INSERT INTO sanciones (jugador_id, campeonato_id, tipo_torneo, tipo, partidos_suspension, partidos_cumplidos, descripcion, activa, fecha_sancion, partido_origen_id)
                        VALUES (?, ?, ?, ?, ?, 0, ?, 1, CURDATE(), ?)
                    ");
                    $stmt->execute([
                        $evento['jugador_id_completo'], 
                        $campeonato_id, 
                        $tipo_torneo, 
                        $tipo_sancion, 
                        $partidos_suspension, 
                        $descripcion, 
                        $partido_id
                    ]);
                    $sanciones_creadas[] = $evento['jugador_id_completo'];
                }
            }
        }
        
        // Contar amarillas por jugador en el campeonato/torneo (excluyendo las que fueron convertidas a roja)
        $jugadores_amarillas = [];
        foreach ($eventos as $evento) {
            if ($evento['tipo_evento'] === 'amarilla') {
                $jugador_id = $evento['jugador_id_completo'];
                
                // Contar amarillas acumuladas en el campeonato/torneo
                if ($tipo_torneo === 'zona' || $tipo_torneo === 'eliminatoria') {
                    // Para torneos por zonas, obtener el formato_id del partido
                    $stmt = $db->prepare("
                        SELECT 
                            COALESCE(
                                (SELECT formato_id FROM zonas WHERE id = (SELECT zona_id FROM partidos WHERE id = ?)),
                                (SELECT formato_id FROM fases_eliminatorias WHERE id = (SELECT fase_eliminatoria_id FROM partidos WHERE id = ?))
                            ) as formato_id
                    ");
                    $stmt->execute([$partido_id, $partido_id]);
                    $formato_info = $stmt->fetch(PDO::FETCH_ASSOC);
                    $formato_id = $formato_info['formato_id'] ?? null;
                    
                    if ($formato_id) {
                        // Contar amarillas en todos los partidos del formato
                        $stmt = $db->prepare("
                            SELECT COUNT(*) as total
                            FROM eventos_partido ep
                            JOIN partidos p ON ep.partido_id = p.id
                            LEFT JOIN zonas z ON p.zona_id = z.id
                            LEFT JOIN fases_eliminatorias fe ON p.fase_eliminatoria_id = fe.id
                            WHERE ep.jugador_id = ?
                            AND ep.tipo_evento = 'amarilla'
                            AND (z.formato_id = ? OR fe.formato_id = ?)
                            AND ep.partido_id NOT IN (
                                SELECT partido_id FROM eventos_partido 
                                WHERE jugador_id = ? AND tipo_evento = 'roja' 
                                AND (observaciones LIKE '%Doble amarilla%' OR observaciones LIKE '%doble amarilla%')
                            )
                        ");
                        $stmt->execute([$jugador_id, $formato_id, $formato_id, $jugador_id]);
                    } else {
                        // Si no se puede determinar el formato, usar consulta simple
                        $stmt = $db->prepare("
                            SELECT COUNT(*) as total
                            FROM eventos_partido ep
                            JOIN partidos p ON ep.partido_id = p.id
                            WHERE ep.jugador_id = ?
                            AND ep.tipo_evento = 'amarilla'
                            AND p.tipo_torneo = ?
                            AND ep.partido_id NOT IN (
                                SELECT partido_id FROM eventos_partido 
                                WHERE jugador_id = ? AND tipo_evento = 'roja' 
                                AND (observaciones LIKE '%Doble amarilla%' OR observaciones LIKE '%doble amarilla%')
                            )
                        ");
                        $stmt->execute([$jugador_id, $tipo_torneo, $jugador_id]);
                    }
                } else {
                    // Para torneos normales, contar amarillas en el campeonato
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as total
                        FROM eventos_partido ep
                        JOIN partidos p ON ep.partido_id = p.id
                        JOIN fechas f ON p.fecha_id = f.id
                        JOIN categorias c ON f.categoria_id = c.id
                        WHERE ep.jugador_id = ?
                        AND ep.tipo_evento = 'amarilla'
                        AND c.campeonato_id = ?
                        AND ep.partido_id NOT IN (
                            SELECT partido_id FROM eventos_partido 
                            WHERE jugador_id = ? AND tipo_evento = 'roja' 
                            AND (observaciones LIKE '%Doble amarilla%' OR observaciones LIKE '%doble amarilla%')
                        )
                    ");
                    $stmt->execute([$jugador_id, $campeonato_id, $jugador_id]);
                }
                
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $jugadores_amarillas[$jugador_id] = (int)$result['total'];
            }
        }
        
        // Crear sanciones por 4 amarillas acumuladas
        foreach ($jugadores_amarillas as $jugador_id => $cantidad) {
            if ($cantidad >= 4 && $cantidad % 4 == 0) {
                // Verificar si ya tiene sanción activa por 4 amarillas
                $stmt = $db->prepare("
                    SELECT id FROM sanciones 
                    WHERE jugador_id = ? AND tipo = 'amarillas_acumuladas' AND activa = 1
                    AND tipo_torneo = ? AND (campeonato_id = ? OR (campeonato_id IS NULL AND ? IS NULL))
                ");
                $stmt->execute([$jugador_id, $tipo_torneo, $campeonato_id, $campeonato_id]);
                
                if (!$stmt->fetch()) {
                    $stmt = $db->prepare("
                        INSERT INTO sanciones (jugador_id, campeonato_id, tipo_torneo, tipo, partidos_suspension, partidos_cumplidos, descripcion, activa, fecha_sancion, partido_origen_id)
                        VALUES (?, ?, ?, 'amarillas_acumuladas', 1, 0, '4 amarillas acumuladas en el campeonato', 1, CURDATE(), ?)
                    ");
                    $stmt->execute([$jugador_id, $campeonato_id, $tipo_torneo, $partido_id]);
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
        // Obtener equipos que jugaron este partido y su fecha
        $stmt = $db->prepare("
            SELECT equipo_local_id, equipo_visitante_id, fecha_id, zona_id, fase_eliminatoria_id, fecha_partido
            FROM partidos 
            WHERE id = ?
        ");
        $stmt->execute([$partido_id]);
        $partido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$partido) {
            return ['success' => false, 'message' => 'Partido no encontrado'];
        }
        
        // Obtener sanciones activas EXCLUYENDO las que se crearon en este mismo partido
        // Las sanciones solo se cumplen en partidos POSTERIORES al partido donde se generaron
        $stmt = $db->prepare("
            SELECT s.*, j.equipo_id, e.nombre as equipo_nombre, j.apellido_nombre
            FROM sanciones s
            JOIN jugadores j ON s.jugador_id = j.id
            JOIN equipos e ON j.equipo_id = e.id
            WHERE s.activa = 1 
            AND j.equipo_id IN (?, ?)
            AND s.partidos_cumplidos < s.partidos_suspension
            AND (s.partido_origen_id IS NULL OR s.partido_origen_id != ?)
        ");
        $stmt->execute([$partido['equipo_local_id'], $partido['equipo_visitante_id'], $partido_id]);
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
                // Verificar que la sanción no se haya creado en este mismo partido
                // (doble verificación por si acaso)
                if ($sancion['partido_origen_id'] == $partido_id) {
                    continue; // No cumplir sanciones creadas en el mismo partido
                }
                
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