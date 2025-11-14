<?php
/**
 * SISTEMA DE DESEMPATE CORREGIDO PARA CAMPEONATOS POR ZONAS
 * ==========================================================
 * 
 * Criterios de desempate cuando dos o más equipos empatan en puntos:
 * 1. Diferencia de goles (GF - GC)
 * 2. Mayor cantidad de goles a favor
 * 3. Mayor cantidad de puntos obtenidos en los enfrentamientos entre los equipos empatados
 * 4. Mayor diferencia de goles entre esos equipos
 * 5. Mayor cantidad de goles a favor entre esos equipos
 * 6. Fairplay (menos tarjetas)
 */

/**
 * Calcula la tabla de posiciones de una zona con todos los criterios de desempate
 */
function calcularTablaPosicionesConDesempate($zona_id, $db) {
    // Obtener estadísticas básicas de cada equipo
    $stmt = $db->prepare("
        SELECT 
            e.id,
            e.nombre,
            e.logo,
            ez.puntos,
            ez.partidos_jugados,
            ez.partidos_ganados,
            ez.partidos_empatados,
            ez.partidos_perdidos,
            ez.goles_favor,
            ez.goles_contra,
            ez.diferencia_gol,
            COALESCE((
                SELECT COUNT(*) + (SUM(CASE WHEN ep.tipo_evento = 'roja' THEN 2 ELSE 0 END))
                FROM eventos_partido ep
                INNER JOIN jugadores j ON ep.jugador_id = j.id
                INNER JOIN partidos_zona pz ON ep.partido_id = pz.id
                WHERE j.equipo_id = e.id 
                AND pz.zona_id = :zona_id_fp
                AND ep.tipo_evento IN ('amarilla', 'roja')
            ), 0) as puntos_fairplay
        FROM equipos e
        INNER JOIN equipos_zonas ez ON e.id = ez.equipo_id
        WHERE ez.zona_id = :zona_id
        ORDER BY ez.posicion
    ");
    $stmt->bindValue(':zona_id', $zona_id, PDO::PARAM_INT);
    $stmt->bindValue(':zona_id_fp', $zona_id, PDO::PARAM_INT);
    $stmt->execute();
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($equipos)) {
        return [];
    }
    
    // Aplicar desempate
    $equipos_ordenados = aplicarCriteriosDesempate($equipos, $zona_id, $db);
    
    return $equipos_ordenados;
}

/**
 * Aplica todos los criterios de desempate
 */
function aplicarCriteriosDesempate($equipos, $zona_id, $db) {
    // Agrupar equipos por puntos
    $grupos_por_puntos = [];
    foreach ($equipos as $equipo) {
        $puntos = $equipo['puntos'];
        if (!isset($grupos_por_puntos[$puntos])) {
            $grupos_por_puntos[$puntos] = [];
        }
        $grupos_por_puntos[$puntos][] = $equipo;
    }
    
    // Ordenar descendente por puntos
    krsort($grupos_por_puntos);
    
    // Procesar cada grupo de equipos con mismos puntos
    $resultado_final = [];
    foreach ($grupos_por_puntos as $puntos => $grupo) {
        if (count($grupo) == 1) {
            // No hay empate, agregar directamente
            $resultado_final[] = $grupo[0];
        } else {
            // Hay empate, aplicar criterios
            $grupo_desempatado = desempatarGrupo($grupo, $zona_id, $db);
            foreach ($grupo_desempatado as $equipo) {
                $resultado_final[] = $equipo;
            }
        }
    }
    
    // Actualizar posiciones en la base de datos
    actualizarPosiciones($resultado_final, $zona_id, $db);
    
    return $resultado_final;
}

/**
 * Desempata un grupo de equipos con los mismos puntos
 */
function desempatarGrupo($grupo, $zona_id, $db) {
    // Criterio 1 y 2: Diferencia de goles y goles a favor
    usort($grupo, function($a, $b) {
        // Primero por diferencia de goles
        $diff_cmp = $b['diferencia_gol'] - $a['diferencia_gol'];
        if ($diff_cmp != 0) return $diff_cmp;
        
        // Luego por goles a favor
        return $b['goles_favor'] - $a['goles_favor'];
    });
    
    // Si todavía hay empate, aplicar criterios 3, 4 y 5 (enfrentamientos directos)
    $grupos_empatados = agruparEquiposEmpatados($grupo);
    $resultado = [];
    
    foreach ($grupos_empatados as $subgrupo) {
        if (count($subgrupo) == 1) {
            $resultado[] = $subgrupo[0];
        } else {
            // Aplicar enfrentamientos directos (criterios 3, 4, 5)
            $subgrupo_desempatado = desempatarPorEnfrentamientosDirectos($subgrupo, $zona_id, $db);
            foreach ($subgrupo_desempatado as $equipo) {
                $resultado[] = $equipo;
            }
        }
    }
    
    return $resultado;
}

/**
 * Agrupa equipos que siguen empatados después de criterios 1 y 2
 */
function agruparEquiposEmpatados($grupo) {
    $subgrupos = [];
    $temp_grupo = [];
    
    for ($i = 0; $i < count($grupo); $i++) {
        $temp_grupo[] = $grupo[$i];
        
        // Verificar si el siguiente equipo tiene las mismas estadísticas
        if ($i == count($grupo) - 1 || 
            $grupo[$i]['diferencia_gol'] != $grupo[$i + 1]['diferencia_gol'] ||
            $grupo[$i]['goles_favor'] != $grupo[$i + 1]['goles_favor']) {
            $subgrupos[] = $temp_grupo;
            $temp_grupo = [];
        }
    }
    
    return $subgrupos;
}

/**
 * Criterios 3, 4 y 5: Desempate por enfrentamientos directos
 */
function desempatarPorEnfrentamientosDirectos($grupo, $zona_id, $db) {
    $equipos_ids = array_map(function($e) { return $e['id']; }, $grupo);
    
    // Obtener todos los partidos entre estos equipos
    $placeholders = implode(',', array_fill(0, count($equipos_ids), '?'));
    $stmt = $db->prepare("
        SELECT 
            pz.id,
            pz.equipo_local_id,
            pz.equipo_visitante_id,
            pz.goles_local,
            pz.goles_visitante
        FROM partidos_zona pz
        WHERE pz.zona_id = ?
        AND pz.equipo_local_id IN ($placeholders)
        AND pz.equipo_visitante_id IN ($placeholders)
        AND pz.estado = 'finalizado'
    ");
    
    $params = array_merge([$zona_id], $equipos_ids, $equipos_ids);
    $stmt->execute($params);
    $partidos_directos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular estadísticas de enfrentamientos directos
    $stats_directos = [];
    foreach ($equipos_ids as $equipo_id) {
        $stats_directos[$equipo_id] = [
            'puntos' => 0,
            'gf' => 0,
            'gc' => 0,
            'dif' => 0
        ];
    }
    
    foreach ($partidos_directos as $partido) {
        $local_id = $partido['equipo_local_id'];
        $visitante_id = $partido['equipo_visitante_id'];
        $goles_local = (int)$partido['goles_local'];
        $goles_visitante = (int)$partido['goles_visitante'];
        
        // Actualizar goles a favor y contra
        $stats_directos[$local_id]['gf'] += $goles_local;
        $stats_directos[$local_id]['gc'] += $goles_visitante;
        $stats_directos[$visitante_id]['gf'] += $goles_visitante;
        $stats_directos[$visitante_id]['gc'] += $goles_local;
        
        // Asignar puntos
        if ($goles_local > $goles_visitante) {
            $stats_directos[$local_id]['puntos'] += 3;
        } elseif ($goles_local < $goles_visitante) {
            $stats_directos[$visitante_id]['puntos'] += 3;
        } else {
            $stats_directos[$local_id]['puntos'] += 1;
            $stats_directos[$visitante_id]['puntos'] += 1;
        }
    }
    
    // Calcular diferencia de goles
    foreach ($stats_directos as $equipo_id => &$stats) {
        $stats['dif'] = $stats['gf'] - $stats['gc'];
    }
    unset($stats); // Romper referencia
    
    // Agregar stats directos a cada equipo
    foreach ($grupo as &$equipo) {
        $equipo['stats_directos'] = $stats_directos[$equipo['id']];
    }
    unset($equipo); // Romper la referencia
    
    // Ordenar por criterios 3, 4, 5 y 6
    usort($grupo, function($a, $b) {
        // Criterio 3: Puntos en enfrentamientos directos
        $puntos_cmp = $b['stats_directos']['puntos'] - $a['stats_directos']['puntos'];
        if ($puntos_cmp != 0) return $puntos_cmp;
        
        // Criterio 4: Diferencia de goles en enfrentamientos directos
        $dif_cmp = $b['stats_directos']['dif'] - $a['stats_directos']['dif'];
        if ($dif_cmp != 0) return $dif_cmp;
        
        // Criterio 5: Goles a favor en enfrentamientos directos
        $gf_cmp = $b['stats_directos']['gf'] - $a['stats_directos']['gf'];
        if ($gf_cmp != 0) return $gf_cmp;
        
        // Criterio 6: Fairplay (menos puntos es mejor)
        $fairplay_cmp = $a['puntos_fairplay'] - $b['puntos_fairplay'];
        if ($fairplay_cmp != 0) return $fairplay_cmp;
        
        // Si todos los criterios están empatados, retornar 0 (empate total)
        return 0;
    });
    
    // Verificar si después de todos los criterios todavía hay empates
    // Si hay empates, necesitan sorteo manual
    $grupos_empate_final = [];
    $grupo_actual = [$grupo[0]];
    
    for ($i = 1; $i < count($grupo); $i++) {
        $equipo_actual = $grupo[$i - 1];
        $equipo_siguiente = $grupo[$i];
        
        // Comparar todos los criterios de desempate
        $stats_actual = $equipo_actual['stats_directos'] ?? ['puntos' => 0, 'dif' => 0, 'gf' => 0];
        $stats_siguiente = $equipo_siguiente['stats_directos'] ?? ['puntos' => 0, 'dif' => 0, 'gf' => 0];
        
        $mismo_puntos_directos = ($stats_actual['puntos'] ?? 0) == ($stats_siguiente['puntos'] ?? 0);
        $misma_dif_directos = ($stats_actual['dif'] ?? 0) == ($stats_siguiente['dif'] ?? 0);
        $mismo_gf_directos = ($stats_actual['gf'] ?? 0) == ($stats_siguiente['gf'] ?? 0);
        $mismo_fairplay = ($equipo_actual['puntos_fairplay'] ?? 0) == ($equipo_siguiente['puntos_fairplay'] ?? 0);
        
        // Si están empatados en todos los criterios, agregar al grupo actual
        if ($mismo_puntos_directos && $misma_dif_directos && $mismo_gf_directos && $mismo_fairplay) {
            $grupo_actual[] = $equipo_siguiente;
        } else {
            // Si hay empate en el grupo actual, guardarlo
            if (count($grupo_actual) > 1) {
                $grupos_empate_final[] = $grupo_actual;
            }
            $grupo_actual = [$equipo_siguiente];
        }
    }
    
    // Si el último grupo tiene empates, guardarlo
    if (count($grupo_actual) > 1) {
        $grupos_empate_final[] = $grupo_actual;
    }
    
    // Si hay empates finales, guardarlos para sorteo manual
    if (!empty($grupos_empate_final)) {
        foreach ($grupos_empate_final as $grupo_empate) {
            guardarEmpatePendiente($grupo_empate, $zona_id, $db);
        }
    }
    
    return $grupo;
}

/**
 * Actualiza las posiciones en la base de datos y marca clasificados
 */
function actualizarPosiciones($equipos_ordenados, $zona_id, $db) {
    // Obtener cuántos clasifican de esta zona
    $stmt_formato = $db->prepare("
        SELECT cf.equipos_clasifican, cf.cantidad_zonas, cf.tipo_clasificacion
        FROM campeonatos_formato cf
        INNER JOIN zonas z ON cf.id = z.formato_id
        WHERE z.id = ?
    ");
    $stmt_formato->execute([$zona_id]);
    $formato = $stmt_formato->fetch(PDO::FETCH_ASSOC);
    
    // Determinar cuántos clasifican por zona
    $clasifican_por_zona = 2; // Por defecto 2 primeros
    if ($formato) {
        switch ($formato['tipo_clasificacion']) {
            case '1_primero':
                $clasifican_por_zona = 1;
                break;
            case '2_primeros':
            case '2_primeros_2_mejores_terceros':
                $clasifican_por_zona = 2;
                break;
            case '4_primeros':
                $clasifican_por_zona = 4;
                break;
        }
    }
    
    // Verificar empates resueltos antes de actualizar posiciones
    // Si hay empates resueltos, usar esas posiciones
    $stmt_empates = $db->prepare("
        SELECT equipo_ganador_id, posicion
        FROM empates_pendientes
        WHERE zona_id = ? AND resuelto = 1
    ");
    $stmt_empates->execute([$zona_id]);
    $empates_resueltos = $stmt_empates->fetchAll(PDO::FETCH_ASSOC);
    
    // Crear mapa de posiciones resueltas
    $posiciones_resueltas = [];
    foreach ($empates_resueltos as $empate_resuelto) {
        $posiciones_resueltas[$empate_resuelto['equipo_ganador_id']] = $empate_resuelto['posicion'];
    }
    
    // Actualizar posiciones
    $stmt = $db->prepare("
        UPDATE equipos_zonas 
        SET posicion = ?, clasificado = ?, requiere_sorteo = 0
        WHERE zona_id = ? AND equipo_id = ?
    ");
    
    $posicion = 1;
    foreach ($equipos_ordenados as $equipo) {
        // Si el equipo tiene una posición resuelta por sorteo, usar esa
        $posicion_final = $posiciones_resueltas[$equipo['id']] ?? $posicion;
        $clasificado = ($posicion_final <= $clasifican_por_zona) ? 1 : 0;
        
        $stmt->execute([$posicion_final, $clasificado, $zona_id, $equipo['id']]);
        
        // Agregar posición al array del equipo
        $equipo['posicion'] = $posicion_final;
        
        $posicion++;
    }
    
    return $equipos_ordenados;
}

/**
 * Obtener equipos clasificados ordenados para fase eliminatoria
 */
function obtenerClasificadosOrdenados($formato_id, $db) {
    // Primero actualizar todas las tablas
    $stmt = $db->prepare("SELECT id FROM zonas WHERE formato_id = ?");
    $stmt->execute([$formato_id]);
    $zonas = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($zonas as $zona_id) {
        calcularTablaPosicionesConDesempate($zona_id, $db);
    }
    
    // Obtener clasificados
    $stmt = $db->prepare("
        SELECT 
            e.id,
            e.nombre,
            e.logo,
            ez.zona_id,
            z.nombre as zona_nombre,
            ez.posicion,
            ez.puntos,
            ez.diferencia_gol,
            ez.goles_favor
        FROM equipos_zonas ez
        INNER JOIN equipos e ON ez.equipo_id = e.id
        INNER JOIN zonas z ON ez.zona_id = z.id
        WHERE z.formato_id = ?
        AND ez.clasificado = 1
        ORDER BY ez.posicion ASC, ez.puntos DESC, ez.diferencia_gol DESC, ez.goles_favor DESC
    ");
    $stmt->execute([$formato_id]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Generar bracket eliminatorio con los clasificados correctamente ordenados
 */
function generarBracketEliminatorio($formato_id, $db) {
    $clasificados = obtenerClasificadosOrdenados($formato_id, $db);
    
    if (empty($clasificados)) {
        throw new Exception('No hay equipos clasificados');
    }
    
    $total_clasificados = count($clasificados);
    
    // Determinar fase inicial según cantidad de clasificados
    $fase_inicial = null;
    if ($total_clasificados == 16) {
        $fase_inicial = 'octavos';
    } elseif ($total_clasificados == 8) {
        $fase_inicial = 'cuartos';
    } elseif ($total_clasificados == 4) {
        $fase_inicial = 'semifinal';
    } else {
        throw new Exception("Cantidad de clasificados no válida: {$total_clasificados}. Se esperan 4, 8 o 16 equipos.");
    }
    
    // Obtener ID de la fase inicial
    $stmt = $db->prepare("
        SELECT id FROM fases_eliminatorias 
        WHERE formato_id = ? AND nombre = ?
    ");
    $stmt->execute([$formato_id, $fase_inicial]);
    $fase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$fase) {
        throw new Exception("Fase eliminatoria '{$fase_inicial}' no encontrada");
    }
    
    // Generar emparejamientos (1° vs último, 2° vs penúltimo, etc.)
    $emparejamientos = [];
    $mitad = intval($total_clasificados / 2);
    
    for ($i = 0; $i < $mitad; $i++) {
        $local = $clasificados[$i];
        $visitante = $clasificados[$total_clasificados - 1 - $i];
        
        $emparejamientos[] = [
            'fase_id' => $fase['id'],
            'numero_llave' => $i + 1,
            'equipo_local_id' => $local['id'],
            'equipo_visitante_id' => $visitante['id'],
            'origen_local' => "{$local['posicion']}° {$local['zona_nombre']}",
            'origen_visitante' => "{$visitante['posicion']}° {$visitante['zona_nombre']}"
        ];
    }
    
    return $emparejamientos;
}

/**
 * Detecta empates restantes después de aplicar todos los criterios
 */
function detectarEmpatesRestantes($equipos) {
    $empates = [];
    $grupo_actual = [];
    
    for ($i = 0; $i < count($equipos); $i++) {
        $grupo_actual[] = $equipos[$i];
        
        // Verificar si el siguiente equipo tiene exactamente las mismas estadísticas
        if ($i == count($equipos) - 1) {
            if (count($grupo_actual) > 1) {
                $empates[] = $grupo_actual;
            }
        } else {
            $equipo_actual = $equipos[$i];
            $equipo_siguiente = $equipos[$i + 1];
            
            // Comparar todos los criterios
            $mismo_puntos_directos = ($equipo_actual['stats_directos']['puntos'] ?? 0) == ($equipo_siguiente['stats_directos']['puntos'] ?? 0);
            $misma_dif_directos = ($equipo_actual['stats_directos']['dif'] ?? 0) == ($equipo_siguiente['stats_directos']['dif'] ?? 0);
            $mismo_gf_directos = ($equipo_actual['stats_directos']['gf'] ?? 0) == ($equipo_siguiente['stats_directos']['gf'] ?? 0);
            $mismo_fairplay = ($equipo_actual['puntos_fairplay'] ?? 0) == ($equipo_siguiente['puntos_fairplay'] ?? 0);
            
            if (!($mismo_puntos_directos && $misma_dif_directos && $mismo_gf_directos && $mismo_fairplay)) {
                if (count($grupo_actual) > 1) {
                    $empates[] = $grupo_actual;
                }
                $grupo_actual = [];
            }
        }
    }
    
    return $empates;
}

/**
 * Guarda un empate pendiente de resolución por sorteo
 */
function guardarEmpatePendiente($equipos_empatados, $zona_id, $db) {
    try {
        // Obtener formato_id desde la zona
        $stmt = $db->prepare("SELECT formato_id, nombre FROM zonas WHERE id = ?");
        $stmt->execute([$zona_id]);
        $zona = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$zona) {
            return false;
        }
        
        $formato_id = $zona['formato_id'];
        
        // Determinar la posición en la que están empatados
        // Basarse en cuántos equipos tienen más puntos
        $puntos = $equipos_empatados[0]['puntos'];
        $stmt = $db->prepare("
            SELECT COUNT(*) + 1 as posicion
            FROM equipos_zonas ez
            WHERE ez.zona_id = ? AND ez.puntos > ?
        ");
        $stmt->execute([$zona_id, $puntos]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $posicion = $result['posicion'] ?? 1;
        
        // Preparar datos de equipos
        $equipos_ids = array_map(function($e) { return (int)$e['id']; }, $equipos_empatados);
        $equipos_nombres = array_map(function($e) { return $e['nombre']; }, $equipos_empatados);
        
        // Criterios aplicados
        $criterios = [
            'puntos' => $puntos,
            'diferencia_gol' => $equipos_empatados[0]['diferencia_gol'] ?? 0,
            'goles_favor' => $equipos_empatados[0]['goles_favor'] ?? 0,
            'stats_directos' => [],
            'fairplay' => []
        ];
        
        // Agregar stats directos y fairplay de cada equipo
        foreach ($equipos_empatados as $equipo) {
            $criterios['stats_directos'][] = $equipo['stats_directos'] ?? ['puntos' => 0, 'dif' => 0, 'gf' => 0];
            $criterios['fairplay'][] = $equipo['puntos_fairplay'] ?? 0;
        }
        
        // Verificar si ya existe un empate pendiente para esta posición en esta zona
        $stmt = $db->prepare("
            SELECT id FROM empates_pendientes 
            WHERE zona_id = ? AND posicion = ? AND resuelto = 0
        ");
        $stmt->execute([$zona_id, $posicion]);
        $existe = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existe) {
            // Actualizar empate existente
            $stmt = $db->prepare("
                UPDATE empates_pendientes 
                SET equipos_ids = ?, equipos_nombres = ?, criterios_aplicados = ?
                WHERE id = ?
            ");
            $stmt->execute([
                json_encode($equipos_ids),
                json_encode($equipos_nombres),
                json_encode($criterios),
                $existe['id']
            ]);
        } else {
            // Crear nuevo empate pendiente
            $stmt = $db->prepare("
                INSERT INTO empates_pendientes 
                (formato_id, zona_id, posicion, equipos_ids, equipos_nombres, criterios_aplicados, resuelto)
                VALUES (?, ?, ?, ?, ?, ?, 0)
            ");
            $stmt->execute([
                $formato_id,
                $zona_id,
                $posicion,
                json_encode($equipos_ids),
                json_encode($equipos_nombres),
                json_encode($criterios)
            ]);
        }
        
        // Marcar el formato como con empates pendientes
        $stmt = $db->prepare("
            UPDATE campeonatos_formato 
            SET empates_pendientes = 1 
            WHERE id = ?
        ");
        $stmt->execute([$formato_id]);
        
        // Marcar equipos como que requieren sorteo
        if (count($equipos_ids) > 0) {
            $placeholders = implode(',', array_fill(0, count($equipos_ids), '?'));
            $stmt = $db->prepare("
                UPDATE equipos_zonas 
                SET requiere_sorteo = 1 
                WHERE zona_id = ? AND equipo_id IN ($placeholders)
            ");
            $params = array_merge([$zona_id], $equipos_ids);
            $stmt->execute($params);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error en guardarEmpatePendiente: " . $e->getMessage());
        return false;
    }
}

/**
 * Resuelve un empate pendiente asignando el ganador por sorteo
 */
function resolverEmpatePendiente($empate_id, $equipo_ganador_id, $db) {
    // Obtener el empate
    $stmt = $db->prepare("SELECT * FROM empates_pendientes WHERE id = ?");
    $stmt->execute([$empate_id]);
    $empate = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$empate) {
        throw new Exception("Empate no encontrado");
    }
    
    if ($empate['resuelto']) {
        throw new Exception("Este empate ya fue resuelto");
    }
    
    // Verificar que el equipo ganador esté en la lista de equipos empatados
    $equipos_ids = json_decode($empate['equipos_ids'], true);
    if (!in_array($equipo_ganador_id, $equipos_ids)) {
        throw new Exception("El equipo seleccionado no está en la lista de equipos empatados");
    }
    
    $db->beginTransaction();
    
    try {
        // Actualizar el empate como resuelto
        $stmt = $db->prepare("
            UPDATE empates_pendientes 
            SET equipo_ganador_id = ?, resuelto = 1, resuelto_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$equipo_ganador_id, $empate_id]);
        
        // Actualizar las posiciones de los equipos en la zona
        $stmt = $db->prepare("
            SELECT posicion FROM equipos_zonas 
            WHERE zona_id = ? AND equipo_id = ?
        ");
        $stmt->execute([$empate['zona_id'], $equipo_ganador_id]);
        $posicion_ganador = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // El ganador mantiene la posición
        // Los perdedores se mueven una posición más abajo
        $posicion_perdedores = $empate['posicion'] + 1;
        
        // Obtener equipos que están en posiciones inferiores y moverlos
        $stmt = $db->prepare("
            UPDATE equipos_zonas 
            SET posicion = posicion + ?
            WHERE zona_id = ? AND posicion >= ? AND equipo_id != ?
        ");
        $cantidad_perdedores = count($equipos_ids) - 1;
        $stmt->execute([$cantidad_perdedores, $empate['zona_id'], $posicion_perdedores, $equipo_ganador_id]);
        
        // Colocar perdedores en las posiciones siguientes
        $posicion_actual = $posicion_perdedores;
        foreach ($equipos_ids as $equipo_id) {
            if ($equipo_id != $equipo_ganador_id) {
                $stmt = $db->prepare("
                    UPDATE equipos_zonas 
                    SET posicion = ?, requiere_sorteo = 0
                    WHERE zona_id = ? AND equipo_id = ?
                ");
                $stmt->execute([$posicion_actual, $empate['zona_id'], $equipo_id]);
                $posicion_actual++;
            }
        }
        
        // Quitar marca de sorteo del ganador
        $stmt = $db->prepare("
            UPDATE equipos_zonas 
            SET requiere_sorteo = 0
            WHERE zona_id = ? AND equipo_id = ?
        ");
        $stmt->execute([$empate['zona_id'], $equipo_ganador_id]);
        
        // Verificar si hay más empates pendientes en este formato
        $stmt = $db->prepare("
            SELECT COUNT(*) as pendientes
            FROM empates_pendientes 
            WHERE formato_id = ? AND resuelto = 0
        ");
        $stmt->execute([$empate['formato_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Si no hay más empates pendientes, marcar el formato
        if ($result['pendientes'] == 0) {
            $stmt = $db->prepare("
                UPDATE campeonatos_formato 
                SET empates_pendientes = 0 
                WHERE id = ?
            ");
            $stmt->execute([$empate['formato_id']]);
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * Verifica si hay empates pendientes de resolución
 */
function hayEmpatesPendientes($formato_id, $db) {
    $stmt = $db->prepare("
        SELECT COUNT(*) as pendientes
        FROM empates_pendientes 
        WHERE formato_id = ? AND resuelto = 0
    ");
    $stmt->execute([$formato_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return ($result['pendientes'] > 0);
}

/**
 * Obtiene todos los empates pendientes de un formato
 */
function obtenerEmpatesPendientes($formato_id, $db) {
    $stmt = $db->prepare("
        SELECT 
            ep.*,
            z.nombre as zona_nombre
        FROM empates_pendientes ep
        INNER JOIN zonas z ON ep.zona_id = z.id
        WHERE ep.formato_id = ? AND ep.resuelto = 0
        ORDER BY z.orden, ep.posicion
    ");
    $stmt->execute([$formato_id]);
    $empates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decodificar JSON
    foreach ($empates as &$empate) {
        $empate['equipos_ids'] = json_decode($empate['equipos_ids'], true);
        $empate['equipos_nombres'] = json_decode($empate['equipos_nombres'], true);
        $empate['criterios_aplicados'] = json_decode($empate['criterios_aplicados'], true);
    }
    
    return $empates;
}