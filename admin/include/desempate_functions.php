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
        return $a['puntos_fairplay'] - $b['puntos_fairplay'];
    });
    
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
    
    // Actualizar posiciones
    $stmt = $db->prepare("
        UPDATE equipos_zonas 
        SET posicion = ?, clasificado = ?
        WHERE zona_id = ? AND equipo_id = ?
    ");
    
    $posicion = 1;
    foreach ($equipos_ordenados as $equipo) {
        $clasificado = ($posicion <= $clasifican_por_zona) ? 1 : 0;
        $stmt->execute([$posicion, $clasificado, $zona_id, $equipo['id']]);
        $posicion++;
    }
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
 * Ejemplo de inserción de emparejamientos en la base de datos
 */
function insertarEmparejamientos($emparejamientos, $db) {
    $stmt = $db->prepare("
        INSERT INTO partidos_eliminatorios 
        (fase_id, numero_llave, equipo_local_id, equipo_visitante_id, origen_local, origen_visitante)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($emparejamientos as $emp) {
        $stmt->execute([
            $emp['fase_id'],
            $emp['numero_llave'],
            $emp['equipo_local_id'],
            $emp['equipo_visitante_id'],
            $emp['origen_local'],
            $emp['origen_visitante']
        ]);
    }
    
    return true;
}

// EJEMPLO DE USO:
// try {
//     $db->beginTransaction();
//     
//     // Calcular tabla de una zona específica
//     $tabla = calcularTablaPosicionesConDesempate($zona_id, $db);
//     
//     // Obtener todos los clasificados
//     $clasificados = obtenerClasificadosOrdenados($formato_id, $db);
//     
//     // Generar bracket eliminatorio
//     $emparejamientos = generarBracketEliminatorio($formato_id, $db);
//     
//     // Insertar emparejamientos
//     insertarEmparejamientos($emparejamientos, $db);
//     
//     $db->commit();
// } catch (Exception $e) {
//     $db->rollBack();
//     error_log("Error en sistema de desempate: " . $e->getMessage());
//     throw $e;
// }