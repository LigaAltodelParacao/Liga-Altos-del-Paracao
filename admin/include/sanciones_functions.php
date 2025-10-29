<?php
/**
 * SISTEMA AUTOMÁTICO DE CUMPLIMIENTO DE SANCIONES - VERSIÓN MEJORADA
 * 
 * Archivo: include/sanciones_functions.php
 * Versión: 2.0
 * Fecha: Octubre 2025
 * 
 * MEJORAS:
 * - Detección automática de 4, 8, 12 amarillas
 * - Detección de doble amarilla en mismo partido
 * - Sistema automático de cumplimiento de fechas
 */

// =====================================================
// FUNCIÓN: PROCESAR EVENTOS DEL PARTIDO Y CREAR SANCIONES
// =====================================================

/**
 * Procesa los eventos de un partido y crea sanciones automáticas
 * Se llama ANTES de finalizar el partido
 * 
 * @param int $partido_id ID del partido
 * @param PDO $db Conexión a la base de datos
 * @return array Resultado del procesamiento
 */
function procesarEventosYCrearSanciones($partido_id, $db) {
    try {
        $resultado = [
            'success' => true,
            'sanciones_creadas' => 0,
            'detalles' => []
        ];
        
        // 1. PROCESAR TARJETAS ROJAS DIRECTAS
        $stmt = $db->prepare("
            SELECT ep.jugador_id, j.apellido_nombre, e.nombre as equipo
            FROM eventos_partido ep
            JOIN jugadores j ON ep.jugador_id = j.id
            JOIN equipos e ON j.equipo_id = e.id
            WHERE ep.partido_id = ? 
            AND ep.tipo_evento = 'roja'
            AND ep.jugador_id NOT IN (
                SELECT jugador_id 
                FROM eventos_partido 
                WHERE partido_id = ? 
                AND tipo_evento = 'amarilla'
            )
        ");
        $stmt->execute([$partido_id, $partido_id]);
        $rojas_directas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rojas_directas as $roja) {
            // Verificar si ya existe una sanción para este jugador en este partido
            $stmt = $db->prepare("
                SELECT COUNT(*) as existe
                FROM sanciones
                WHERE jugador_id = ?
                AND tipo = 'roja_directa'
                AND fecha_sancion = CURDATE()
            ");
            $stmt->execute([$roja['jugador_id']]);
            $existe = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existe['existe'] == 0) {
                $stmt = $db->prepare("
                    INSERT INTO sanciones (jugador_id, tipo, partidos_suspension, descripcion, activa, fecha_sancion)
                    VALUES (?, 'roja_directa', 1, 'Tarjeta roja directa', 1, CURDATE())
                ");
                $stmt->execute([$roja['jugador_id']]);
                
                $resultado['sanciones_creadas']++;
                $resultado['detalles'][] = [
                    'jugador' => $roja['apellido_nombre'],
                    'equipo' => $roja['equipo'],
                    'tipo' => 'Roja Directa',
                    'fechas' => 1
                ];
            }
        }
        
        // 2. DETECTAR Y PROCESAR DOBLES AMARILLAS
        $stmt = $db->prepare("
            SELECT ep.jugador_id, j.apellido_nombre, e.nombre as equipo, COUNT(*) as amarillas
            FROM eventos_partido ep
            JOIN jugadores j ON ep.jugador_id = j.id
            JOIN equipos e ON j.equipo_id = e.id
            WHERE ep.partido_id = ? 
            AND ep.tipo_evento = 'amarilla'
            GROUP BY ep.jugador_id, j.apellido_nombre, e.nombre
            HAVING COUNT(*) >= 2
        ");
        $stmt->execute([$partido_id]);
        $dobles_amarillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($dobles_amarillas as $doble) {
            // Verificar si ya existe una sanción para este jugador por doble amarilla hoy
            $stmt = $db->prepare("
                SELECT COUNT(*) as existe
                FROM sanciones
                WHERE jugador_id = ?
                AND tipo = 'doble_amarilla'
                AND fecha_sancion = CURDATE()
            ");
            $stmt->execute([$doble['jugador_id']]);
            $existe = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existe['existe'] == 0) {
                // Crear sanción por doble amarilla
                $stmt = $db->prepare("
                    INSERT INTO sanciones (jugador_id, tipo, partidos_suspension, descripcion, activa, fecha_sancion)
                    VALUES (?, 'doble_amarilla', 1, 'Doble amarilla en partido', 1, CURDATE())
                ");
                $stmt->execute([$doble['jugador_id']]);
                
                // NO SUMAR estas amarillas al contador de acumuladas
                // (La doble amarilla reemplaza a las 2 amarillas individuales)
                
                $resultado['sanciones_creadas']++;
                $resultado['detalles'][] = [
                    'jugador' => $doble['apellido_nombre'],
                    'equipo' => $doble['equipo'],
                    'tipo' => 'Doble Amarilla',
                    'fechas' => 1
                ];
            }
        }
        
        // 3. PROCESAR AMARILLAS INDIVIDUALES (que no sean doble amarilla)
        $stmt = $db->prepare("
            SELECT DISTINCT ep.jugador_id, j.apellido_nombre, e.nombre as equipo,
                   (SELECT COUNT(*) FROM eventos_partido 
                    WHERE jugador_id = ep.jugador_id 
                    AND partido_id = ep.partido_id 
                    AND tipo_evento = 'amarilla') as amarillas_en_partido
            FROM eventos_partido ep
            JOIN jugadores j ON ep.jugador_id = j.id
            JOIN equipos e ON j.equipo_id = e.id
            WHERE ep.partido_id = ? 
            AND ep.tipo_evento = 'amarilla'
        ");
        $stmt->execute([$partido_id]);
        $jugadores_amarillas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($jugadores_amarillas as $jugador) {
            // Si tiene 2+ amarillas en este partido, ya se procesó como doble amarilla
            if ($jugador['amarillas_en_partido'] >= 2) {
                continue;
            }
            
            // Actualizar contador de amarillas acumuladas
            $stmt = $db->prepare("
                UPDATE jugadores 
                SET amarillas_acumuladas = amarillas_acumuladas + 1
                WHERE id = ?
            ");
            $stmt->execute([$jugador['jugador_id']]);
            
            // Obtener nuevo total de amarillas
            $stmt = $db->prepare("
                SELECT amarillas_acumuladas FROM jugadores WHERE id = ?
            ");
            $stmt->execute([$jugador['jugador_id']]);
            $total_amarillas = $stmt->fetch(PDO::FETCH_ASSOC)['amarillas_acumuladas'];
            
            // Verificar si alcanzó 4, 8 o 12 amarillas
            if (in_array($total_amarillas, [4, 8, 12])) {
                // Crear sanción por acumulación
                $stmt = $db->prepare("
                    INSERT INTO sanciones (jugador_id, tipo, partidos_suspension, descripcion, activa, fecha_sancion)
                    VALUES (?, 'amarillas_acumuladas', 1, '4 amarillas acumuladas en el campeonato', 1, CURDATE())
                ");
                $stmt->execute([$jugador['jugador_id']]);
                
                $resultado['sanciones_creadas']++;
                $resultado['detalles'][] = [
                    'jugador' => $jugador['apellido_nombre'],
                    'equipo' => $jugador['equipo'],
                    'tipo' => "Acumulación ($total_amarillas amarillas)",
                    'fechas' => 1
                ];
            }
        }
        
        return $resultado;
        
    } catch (Exception $e) {
        error_log("Error en procesarEventosYCrearSanciones: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'sanciones_creadas' => 0,
            'detalles' => []
        ];
    }
}

// =====================================================
// FUNCIÓN PRINCIPAL: CUMPLIR SANCIONES AUTOMÁTICAMENTE
// =====================================================

/**
 * Cumple automáticamente fechas de sanción cuando un equipo juega
 * Esta función se ejecuta cuando un partido se marca como finalizado
 * 
 * @param int $partido_id ID del partido finalizado
 * @param PDO $db Conexión a la base de datos
 * @return array ['success' => bool, 'message' => string, 'actualizados' => int, 'detalle' => array]
 */
function cumplirSancionesAutomaticas($partido_id, $db) {
    try {
        // Verificar que el partido existe y está finalizado
        $stmt = $db->prepare("
            SELECT p.id, p.equipo_local_id, p.equipo_visitante_id, p.fecha_id,
                   el.nombre as equipo_local, ev.nombre as equipo_visitante
            FROM partidos p
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            WHERE p.id = ? AND p.estado = 'finalizado'
        ");
        $stmt->execute([$partido_id]);
        $partido = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$partido) {
            return [
                'success' => false,
                'message' => 'Partido no encontrado o no está finalizado',
                'actualizados' => 0,
                'detalle' => []
            ];
        }
        
        $equipos = [
            $partido['equipo_local_id'] => $partido['equipo_local'],
            $partido['equipo_visitante_id'] => $partido['equipo_visitante']
        ];
        
        $total_actualizados = 0;
        $jugadores_actualizados = [];
        
        // Para cada equipo, buscar jugadores con sanciones activas
        foreach ($equipos as $equipo_id => $equipo_nombre) {
            // Obtener jugadores sancionados del equipo
            $stmt = $db->prepare("
                SELECT 
                    s.id as sancion_id,
                    s.jugador_id,
                    s.tipo,
                    s.partidos_suspension,
                    s.partidos_cumplidos,
                    j.apellido_nombre,
                    j.dni
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
                    SET partidos_cumplidos = ?, 
                        activa = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nuevo_cumplidos, $activa, $sancion['sancion_id']]);
                
                // Registrar en log
                registrarLogSancion($sancion['sancion_id'], $partido_id, $nuevo_cumplidos, $db);
                
                $total_actualizados++;
                $jugadores_actualizados[] = [
                    'nombre' => $sancion['apellido_nombre'],
                    'dni' => $sancion['dni'],
                    'equipo' => $equipo_nombre,
                    'tipo' => $sancion['tipo'],
                    'cumplidos' => $nuevo_cumplidos,
                    'total' => $sancion['partidos_suspension'],
                    'finalizada' => !$activa
                ];
            }
        }
        
        $message = $total_actualizados > 0 
            ? "Se actualizaron $total_actualizados sanción(es) automáticamente" 
            : "No había sanciones pendientes para este partido";
        
        return [
            'success' => true,
            'message' => $message,
            'actualizados' => $total_actualizados,
            'detalle' => $jugadores_actualizados
        ];
        
    } catch (Exception $e) {
        error_log("Error en cumplirSancionesAutomaticas: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al procesar sanciones: ' . $e->getMessage(),
            'actualizados' => 0,
            'detalle' => []
        ];
    }
}

// =====================================================
// FUNCIÓN: REGISTRAR LOG DE CUMPLIMIENTO
// =====================================================

/**
 * Registra un log de cumplimiento de sanción
 * 
 * @param int $sancion_id ID de la sanción
 * @param int $partido_id ID del partido donde se cumplió
 * @param int $fechas_cumplidas Cantidad de fechas cumplidas hasta ahora
 * @param PDO $db Conexión a la base de datos
 * @return bool True si se registró correctamente
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

// =====================================================
// FUNCIÓN: VERIFICAR SI JUGADOR PUEDE JUGAR
// =====================================================

/**
 * Verifica si un jugador puede jugar (no está sancionado)
 * 
 * @param int $jugador_id ID del jugador
 * @param PDO $db Conexión a la base de datos
 * @return bool True si puede jugar, False si está sancionado
 */
function jugadorPuedeJugar($jugador_id, $db) {
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as sanciones_activas
            FROM sanciones
            WHERE jugador_id = ? AND activa = 1
        ");
        $stmt->execute([$jugador_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['sanciones_activas'] == 0;
    } catch (Exception $e) {
        error_log("Error en jugadorPuedeJugar: " . $e->getMessage());
        return false;
    }
}

// =====================================================
// FUNCIÓN: OBTENER SANCIONES ACTIVAS DE UN JUGADOR
// =====================================================

/**
 * Obtiene el detalle de sanciones activas de un jugador
 * 
 * @param int $jugador_id ID del jugador
 * @param PDO $db Conexión a la base de datos
 * @return array Lista de sanciones activas con detalles
 */
function obtenerSancionesActivas($jugador_id, $db) {
    try {
        $stmt = $db->prepare("
            SELECT 
                s.*,
                (s.partidos_suspension - s.partidos_cumplidos) as fechas_restantes,
                CASE s.tipo
                    WHEN 'amarillas_acumuladas' THEN '4 Amarillas Acumuladas'
                    WHEN 'doble_amarilla' THEN 'Doble Amarilla'
                    WHEN 'roja_directa' THEN 'Roja Directa'
                    WHEN 'administrativa' THEN 'Sanción Administrativa'
                END as tipo_descripcion
            FROM sanciones s
            WHERE s.jugador_id = ? AND s.activa = 1
            ORDER BY s.fecha_sancion DESC
        ");
        $stmt->execute([$jugador_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error en obtenerSancionesActivas: " . $e->getMessage());
        return [];
    }
}

// =====================================================
// FUNCIÓN: OBTENER HISTORIAL DE UNA SANCIÓN
// =====================================================

/**
 * Obtiene el historial de cumplimiento de una sanción
 * 
 * @param int $sancion_id ID de la sanción
 * @param PDO $db Conexión a la base de datos  
 * @return array Lista de partidos donde se cumplió la sanción
 */
function obtenerHistorialSancion($sancion_id, $db) {
    try {
        $stmt = $db->prepare("
            SELECT 
                ls.*,
                p.fecha_partido,
                p.hora_partido,
                el.nombre as equipo_local,
                ev.nombre as equipo_visitante,
                CONCAT(p.goles_local, '-', p.goles_visitante) as resultado
            FROM log_sanciones ls
            JOIN partidos p ON ls.partido_id = p.id
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            WHERE ls.sancion_id = ?
            ORDER BY ls.fecha_registro ASC
        ");
        $stmt->execute([$sancion_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error en obtenerHistorialSancion: " . $e->getMessage());
        return [];
    }
}

// =====================================================
// FUNCIÓN: ESTADÍSTICAS POR EQUIPO
// =====================================================

/**
 * Obtiene estadísticas de sanciones por equipo
 * 
 * @param int|null $categoria_id ID de la categoría (opcional)
 * @param PDO $db Conexión a la base de datos
 * @return array Estadísticas por equipo
 */
function obtenerEstadisticasSancionesPorEquipo($categoria_id, $db) {
    try {
        $sql = "
            SELECT 
                e.id,
                e.nombre as equipo,
                c.nombre as categoria,
                COUNT(CASE WHEN s.activa = 1 THEN 1 END) as activas,
                COUNT(CASE WHEN s.activa = 0 THEN 1 END) as cumplidas,
                COUNT(CASE WHEN s.tipo = 'amarillas_acumuladas' THEN 1 END) as amarillas,
                COUNT(CASE WHEN s.tipo IN ('doble_amarilla', 'roja_directa') THEN 1 END) as rojas,
                COUNT(CASE WHEN s.tipo = 'administrativa' THEN 1 END) as administrativas,
                COUNT(*) as total
            FROM equipos e
            JOIN categorias c ON e.categoria_id = c.id
            LEFT JOIN jugadores j ON e.id = j.equipo_id
            LEFT JOIN sanciones s ON j.id = s.jugador_id
        ";
        
        if ($categoria_id) {
            $sql .= " WHERE e.categoria_id = ?";
        }
        
        $sql .= " GROUP BY e.id, e.nombre, c.nombre ORDER BY activas DESC, total DESC";
        
        $stmt = $db->prepare($sql);
        if ($categoria_id) {
            $stmt->execute([$categoria_id]);
        } else {
            $stmt->execute();
        }
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error en obtenerEstadisticasSancionesPorEquipo: " . $e->getMessage());
        return [];
    }
}

// =====================================================
// FUNCIÓN: OBTENER JUGADORES SANCIONADOS DE UN EQUIPO
// =====================================================

/**
 * Obtiene todos los jugadores sancionados de un equipo
 * 
 * @param int $equipo_id ID del equipo
 * @param PDO $db Conexión a la base de datos
 * @param bool $solo_activas Si true, solo devuelve sanciones activas
 * @return array Lista de jugadores con sus sanciones
 */
function obtenerJugadoresSancionadosEquipo($equipo_id, $db, $solo_activas = true) {
    try {
        $sql = "
            SELECT 
                j.id as jugador_id,
                j.apellido_nombre,
                j.dni,
                s.id as sancion_id,
                s.tipo,
                s.partidos_suspension,
                s.partidos_cumplidos,
                (s.partidos_suspension - s.partidos_cumplidos) as fechas_restantes,
                s.descripcion,
                s.fecha_sancion,
                s.activa
            FROM jugadores j
            JOIN sanciones s ON j.id = s.jugador_id
            WHERE j.equipo_id = ?
        ";
        
        if ($solo_activas) {
            $sql .= " AND s.activa = 1";
        }
        
        $sql .= " ORDER BY s.activa DESC, j.apellido_nombre ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$equipo_id]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error en obtenerJugadoresSancionadosEquipo: " . $e->getMessage());
        return [];
    }
}

// =====================================================
// FIN DEL ARCHIVO
// =====================================================

/**
 * INSTRUCCIONES DE USO:
 * 
 * 1. Al FINALIZAR un partido, llamar a estas funciones en este orden:
 *    a) procesarEventosYCrearSanciones($partido_id, $db) 
 *    b) cumplirSancionesAutomaticas($partido_id, $db)
 * 
 * 2. Ejemplo de uso al finalizar partido:
 *    
 *    // Marcar partido como finalizado
 *    $stmt->execute(...);
 *    
 *    // Procesar eventos y crear sanciones
 *    $result_sanciones = procesarEventosYCrearSanciones($partido_id, $db);
 *    
 *    // Cumplir fechas de sanción
 *    $result_cumplimiento = cumplirSancionesAutomaticas($partido_id, $db);
 *    
 *    // Mostrar resultados
 *    if ($result_sanciones['sanciones_creadas'] > 0) {
 *        echo "Se crearon {$result_sanciones['sanciones_creadas']} sanciones nuevas";
 *    }
 *    if ($result_cumplimiento['actualizados'] > 0) {
 *        echo "Se cumplieron {$result_cumplimiento['actualizados']} fechas de sanción";
 *    }
 */
?>