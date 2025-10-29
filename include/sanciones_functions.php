<?php
/**
 * SISTEMA AUTOMÁTICO DE CUMPLIMIENTO DE SANCIONES
 * 
 * Archivo: include/sanciones_functions.php
 * Versión: 1.1 - CORREGIDO
 * Fecha: Octubre 2025
 * 
 * Este archivo contiene todas las funciones necesarias para el manejo
 * automático de sanciones en el sistema de gestión de fútbol.
 * 
 * IMPORTANTE: Este archivo debe estar en la carpeta include/
 */

// =====================================================
// FUNCIÓN PRINCIPAL: CUMPLIR SANCIONES AUTOMÁTICAMENTE
// =====================================================

/**
 * Cumple automáticamente fechas de sanción cuando un equipo juega
 * Esta función se ejecuta cuando un partido se marca como finalizado
 * 
 * @param int $partido_id ID del partido finalizado
 * @param PDO $db Conexión a la base de datos
 * @param bool $excluir_mismo_partido Si TRUE, no cuenta sanciones generadas en este mismo partido
 * @return array ['success' => bool, 'message' => string, 'actualizados' => int, 'detalle' => array]
 */
function cumplirSancionesAutomaticas($partido_id, $db, $excluir_mismo_partido = true) {
    try {
        // Verificar que el partido existe y está finalizado
        $stmt = $db->prepare("
            SELECT p.id, p.equipo_local_id, p.equipo_visitante_id, p.fecha_id,
                   el.nombre as equipo_local, ev.nombre as equipo_visitante,
                   p.fecha_partido
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
            // EXCLUIR sanciones generadas HOY (mismo día del partido)
            $sql = "
                SELECT 
                    s.id as sancion_id,
                    s.jugador_id,
                    s.tipo,
                    s.partidos_suspension,
                    s.partidos_cumplidos,
                    s.fecha_sancion,
                    j.apellido_nombre,
                    j.dni
                FROM sanciones s
                JOIN jugadores j ON s.jugador_id = j.id
                WHERE j.equipo_id = ? 
                AND s.activa = 1 
                AND s.partidos_cumplidos < s.partidos_suspension
            ";
            
            // Si se debe excluir el mismo partido, no contar sanciones del mismo día
            if ($excluir_mismo_partido) {
                $sql .= " AND DATE(s.fecha_sancion) < CURDATE()";
            }
            
            $stmt = $db->prepare($sql);
            $stmt->execute([$equipo_id]);
            $sanciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Cumplir una fecha para cada jugador sancionado
            foreach ($sanciones as $sancion) {
                $nuevo_cumplidos = $sancion['partidos_cumplidos'] + 1;
                
                // Si cumplió todas las fechas, marcar como inactiva
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
                    'finalizada' => ($activa == 0)
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
// FUNCIÓN: PROCESAR SANCIONES PENDIENTES (HISTORIAL)
// =====================================================

/**
 * Procesa todos los partidos finalizados para actualizar sanciones históricas
 * USAR CON CUIDADO - Solo ejecutar una vez para migración
 * 
 * @param PDO $db Conexión a la base de datos
 * @return array Resultado del procesamiento
 */
function procesarSancionesPendientes($db) {
    try {
        // Obtener todos los partidos finalizados en orden cronológico
        $stmt = $db->query("
            SELECT id, fecha_partido, hora_partido
            FROM partidos 
            WHERE estado = 'finalizado' 
            ORDER BY fecha_partido ASC, hora_partido ASC
        ");
        $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $procesados = 0;
        $errores = 0;
        $detalles = [];
        
        foreach ($partidos as $partido) {
            $resultado = cumplirSancionesAutomaticas($partido['id'], $db);
            
            if ($resultado['success']) {
                $procesados++;
                if ($resultado['actualizados'] > 0) {
                    $detalles[] = [
                        'partido_id' => $partido['id'],
                        'fecha' => $partido['fecha_partido'],
                        'actualizados' => $resultado['actualizados']
                    ];
                }
            } else {
                $errores++;
            }
        }
        
        return [
            'success' => true,
            'total_partidos' => count($partidos),
            'procesados' => $procesados,
            'errores' => $errores,
            'detalles' => $detalles
        ];
        
    } catch (Exception $e) {
        error_log("Error en procesarSancionesPendientes: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage(),
            'total_partidos' => 0,
            'procesados' => 0,
            'errores' => 0,
            'detalles' => []
        ];
    }
}

// =====================================================
// FUNCIÓN: CREAR SANCIÓN
// =====================================================

/**
 * Crea una nueva sanción
 * 
 * @param array $datos Datos de la sanción ['jugador_id', 'tipo', 'partidos', 'descripcion']
 * @param PDO $db Conexión a la base de datos
 * @return array Resultado de la operación
 */
function crearSancion($datos, $db) {
    try {
        // Validar datos requeridos
        if (empty($datos['jugador_id']) || empty($datos['tipo']) || empty($datos['partidos'])) {
            return [
                'success' => false,
                'message' => 'Faltan datos requeridos'
            ];
        }
        
        $stmt = $db->prepare("
            INSERT INTO sanciones (
                jugador_id, 
                tipo, 
                partidos_suspension, 
                descripcion, 
                activa, 
                fecha_sancion
            ) VALUES (?, ?, ?, ?, 1, CURDATE())
        ");
        
        $stmt->execute([
            $datos['jugador_id'],
            $datos['tipo'],
            $datos['partidos'],
            $datos['descripcion'] ?? ''
        ]);
        
        $sancion_id = $db->lastInsertId();
        
        return [
            'success' => true,
            'message' => 'Sanción creada exitosamente',
            'sancion_id' => $sancion_id
        ];
        
    } catch (Exception $e) {
        error_log("Error en crearSancion: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error al crear sanción: ' . $e->getMessage()
        ];
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
// FUNCIÓN: VERIFICAR INTEGRIDAD DEL SISTEMA
// =====================================================

/**
 * Verifica que el sistema esté funcionando correctamente
 * Útil para diagnóstico
 * 
 * @param PDO $db Conexión a la base de datos
 * @return array Reporte de estado del sistema
 */
function verificarSistemaSanciones($db) {
    $reporte = [
        'sistema_activo' => false,
        'tabla_log_existe' => false,
        'vista_existe' => false,
        'sanciones_inconsistentes' => 0,
        'errores' => []
    ];
    
    try {
        // Verificar tabla log_sanciones
        $stmt = $db->query("SHOW TABLES LIKE 'log_sanciones'");
        $reporte['tabla_log_existe'] = $stmt->rowCount() > 0;
        
        // Verificar vista (opcional)
        try {
            $stmt = $db->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
            $vistas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $reporte['vista_existe'] = in_array('v_sanciones_completas', $vistas);
        } catch (Exception $e) {
            $reporte['vista_existe'] = false; // No es crítico
        }
        
        // Verificar sanciones inconsistentes (cumplidas pero activas)
        $stmt = $db->query("
            SELECT COUNT(*) as total
            FROM sanciones
            WHERE partidos_cumplidos >= partidos_suspension
            AND activa = 1
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $reporte['sanciones_inconsistentes'] = $result['total'];
        
        // Sistema activo si tabla log existe y no hay inconsistencias
        $reporte['sistema_activo'] = $reporte['tabla_log_existe'] && 
                                     $reporte['sanciones_inconsistentes'] == 0;
        
    } catch (Exception $e) {
        $reporte['errores'][] = $e->getMessage();
    }
    
    return $reporte;
}

// =====================================================
// FIN DEL ARCHIVO
// =====================================================

/**
 * NOTAS DE USO:
 * 
 * 1. Este archivo debe estar en admin/include/sanciones_functions.php
 * 2. Se incluye automáticamente desde config.php
 * 3. Las funciones principales son:
 *    - cumplirSancionesAutomaticas() -> Llamar al finalizar partido
 *    - jugadorPuedeJugar() -> Verificar si un jugador puede jugar
 *    - obtenerSancionesActivas() -> Ver sanciones de un jugador
 * 
 * 4. Para usar en tu código:
 *    if (function_exists('cumplirSancionesAutomaticas')) {
 *        $resultado = cumplirSancionesAutomaticas($partido_id, $db);
 *    }
 */
?>