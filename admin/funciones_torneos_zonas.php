<?php
/**
 * Funciones auxiliares para el sistema de torneos por zonas
 * Incluye: distribución, desempates, clasificación, generación de fixture
 */

require_once __DIR__ . '/../config.php';

/**
 * Calcula la distribución automática de equipos en zonas
 * Distribuye de forma homogénea cuando hay equipos impares
 */
function calcularDistribucionZonas($total_equipos, $num_zonas) {
    $equipos_por_zona = floor($total_equipos / $num_zonas);
    $equipos_sobrantes = $total_equipos % $num_zonas;
    
    $distribucion = [];
    for ($i = 0; $i < $num_zonas; $i++) {
        // Las primeras zonas llevan un equipo extra si hay sobrantes
        $distribucion[] = $equipos_por_zona + ($i < $equipos_sobrantes ? 1 : 0);
    }
    
    return $distribucion;
}

/**
 * Genera el fixture de una zona (todos contra todos)
 * Usa algoritmo Round Robin y crea partidos en la tabla partidos con fechas
 */
function generarFixtureZona($zona_id, $db, $fecha_inicio = null, $dias_entre_fechas = 7) {
    try {
        // Obtener información de la zona y categoría
        $stmt = $db->prepare("
            SELECT z.*, cf.categoria_id, cat.campeonato_id
            FROM zonas z
            JOIN campeonatos_formato cf ON z.formato_id = cf.id
            LEFT JOIN categorias cat ON cf.categoria_id = cat.id
            WHERE z.id = ?
        ");
        $stmt->execute([$zona_id]);
        $zona_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$zona_info || !$zona_info['categoria_id']) {
            error_log("generarFixtureZona: No se encontró información de zona o categoría para zona_id=$zona_id");
            return false;
        }
        
        // Limpiar partidos y fechas existentes de esta zona
        $stmt = $db->prepare("DELETE FROM partidos WHERE zona_id = ? AND tipo_torneo = 'zona'");
        $stmt->execute([$zona_id]);
        
        $stmt = $db->prepare("DELETE FROM fechas WHERE zona_id = ? AND tipo_fecha = 'zona'");
        $stmt->execute([$zona_id]);
        
        // Obtener equipos de la zona
        $stmt = $db->prepare("SELECT equipo_id FROM equipos_zonas WHERE zona_id = ? ORDER BY posicion");
        $stmt->execute([$zona_id]);
        $equipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $num_equipos = count($equipos);
        
        if ($num_equipos < 2) {
            error_log("generarFixtureZona: Zona $zona_id tiene menos de 2 equipos ($num_equipos)");
            return false;
        }
    
        // Si hay número impar, agregar "BYE" (no se crea partido)
        $es_impar = $num_equipos % 2 != 0;
        if ($es_impar) {
            $equipos[] = null; // BYE
            $num_equipos++;
        }
        
        // Usar fecha de inicio proporcionada o fecha actual
        if ($fecha_inicio) {
            $fecha_base = new DateTime($fecha_inicio);
        } else {
            $fecha_base = new DateTime();
        }
        
        // Algoritmo Round Robin correcto
        $jornadas = $num_equipos - 1;
        
        // Crear fechas y partidos para cada jornada
        for ($jornada = 1; $jornada <= $jornadas; $jornada++) {
            // Calcular fecha de esta jornada
            $fecha_jornada = clone $fecha_base;
            $fecha_jornada->modify('+' . (($jornada - 1) * $dias_entre_fechas) . ' days');
            
            // Crear fecha para esta jornada
            $stmt = $db->prepare("
                INSERT INTO fechas (categoria_id, numero_fecha, tipo_fecha, zona_id, fecha_programada)
                VALUES (?, ?, 'zona', ?, ?)
            ");
            $stmt->execute([
                $zona_info['categoria_id'],
                $jornada,
                $zona_id,
                $fecha_jornada->format('Y-m-d')
            ]);
            $fecha_id = $db->lastInsertId();
            
            // Calcular partidos de esta jornada usando Round Robin
            $partidos_jornada = [];
            
            // Algoritmo Round Robin: el primer equipo juega contra el último
            // Los demás se emparejan: segundo vs penúltimo, tercero vs antepenúltimo, etc.
            for ($i = 0; $i < floor($num_equipos / 2); $i++) {
                $idx1 = $i;
                $idx2 = $num_equipos - 1 - $i;
                
                $equipo1 = $equipos[$idx1];
                $equipo2 = $equipos[$idx2];
                
                // Si alguno es BYE, no crear partido
                if ($equipo1 === null || $equipo2 === null) {
                    continue;
                }
                
                // Alternar local/visitante cada jornada
                if ($jornada % 2 == 0) {
                    $partidos_jornada[] = [
                        'local' => $equipo2,
                        'visitante' => $equipo1
                    ];
                } else {
                    $partidos_jornada[] = [
                        'local' => $equipo1,
                        'visitante' => $equipo2
                    ];
                }
            }
            
            // Crear partidos en la tabla partidos
            foreach ($partidos_jornada as $partido) {
                $stmt = $db->prepare("
                    INSERT INTO partidos 
                    (fecha_id, equipo_local_id, equipo_visitante_id, zona_id, jornada_zona, 
                     tipo_torneo, estado, fecha_partido)
                    VALUES (?, ?, ?, ?, ?, 'zona', 'pendiente', ?)
                ");
                $stmt->execute([
                    $fecha_id,
                    $partido['local'],
                    $partido['visitante'],
                    $zona_id,
                    $jornada,
                    $fecha_jornada->format('Y-m-d')
                ]);
            }
            
            // Rotar equipos para la siguiente jornada (Round Robin estándar)
            // El primer equipo queda fijo, rotamos el resto
            if ($jornada < $jornadas) {
                // Guardar el último elemento
                $ultimo = array_pop($equipos);
                // Insertar el último después del primero
                array_splice($equipos, 1, 0, [$ultimo]);
            }
        }
        
        error_log("generarFixtureZona: Fixture generado exitosamente para zona_id=$zona_id con $num_equipos equipos y $jornadas jornadas");
        return true;
        
    } catch (Exception $e) {
        error_log("generarFixtureZona ERROR para zona_id=$zona_id: " . $e->getMessage());
        return false;
    }
}

/**
 * Actualiza las estadísticas de un equipo en su zona
 * Se llama después de finalizar un partido
 */
function actualizarEstadisticasZona($zona_id, $equipo_id, $db) {
    // Calcular estadísticas desde los partidos (usando tabla partidos)
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as partidos_jugados,
            SUM(CASE 
                WHEN (equipo_local_id = ? AND goles_local > goles_visitante) OR 
                     (equipo_visitante_id = ? AND goles_visitante > goles_local) 
                THEN 1 ELSE 0 END) as ganados,
            SUM(CASE 
                WHEN estado = 'finalizado' 
                AND goles_local = goles_visitante
                AND (equipo_local_id = ? OR equipo_visitante_id = ?)
                THEN 1 ELSE 0 END) as empatados,
            SUM(CASE 
                WHEN (equipo_local_id = ? AND goles_local < goles_visitante) OR 
                     (equipo_visitante_id = ? AND goles_visitante < goles_local) 
                THEN 1 ELSE 0 END) as perdidos,
            SUM(CASE WHEN equipo_local_id = ? THEN goles_local ELSE 0 END) +
            SUM(CASE WHEN equipo_visitante_id = ? THEN goles_visitante ELSE 0 END) as goles_favor,
            SUM(CASE WHEN equipo_local_id = ? THEN goles_visitante ELSE 0 END) +
            SUM(CASE WHEN equipo_visitante_id = ? THEN goles_local ELSE 0 END) as goles_contra,
            SUM(CASE WHEN equipo_local_id = ? THEN goles_local ELSE 0 END) +
            SUM(CASE WHEN equipo_visitante_id = ? THEN goles_visitante ELSE 0 END) -
            (SUM(CASE WHEN equipo_local_id = ? THEN goles_visitante ELSE 0 END) +
             SUM(CASE WHEN equipo_visitante_id = ? THEN goles_local ELSE 0 END)) as diferencia_gol
        FROM partidos
        WHERE zona_id = ? 
          AND tipo_torneo = 'zona'
          AND (equipo_local_id = ? OR equipo_visitante_id = ?)
          AND estado = 'finalizado'
    ");
    
    $stmt->execute([
        $equipo_id, $equipo_id,  // ganados
        $equipo_id, $equipo_id,  // empatados (verificando que el equipo esté en el partido)
        $equipo_id, $equipo_id,  // perdidos
        $equipo_id, $equipo_id,  // goles_favor
        $equipo_id, $equipo_id,  // goles_contra
        $equipo_id, $equipo_id,  // diferencia (goles_favor)
        $equipo_id, $equipo_id,  // diferencia (goles_contra)
        $zona_id, $equipo_id, $equipo_id  // WHERE
    ]);
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Calcular puntos
    $puntos = ($stats['ganados'] * 3) + ($stats['empatados'] * 1);
    
    // Calcular tarjetas (usando eventos_partido con partidos)
    // eventos_partido usa partido_id que se refiere a la tabla partidos
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN ep.tipo_evento = 'amarilla' THEN 1 ELSE 0 END) as tarjetas_amarillas,
            SUM(CASE WHEN ep.tipo_evento = 'roja' THEN 1 ELSE 0 END) as tarjetas_rojas
        FROM eventos_partido ep
        JOIN partidos p ON ep.partido_id = p.id
        JOIN jugadores j ON ep.jugador_id = j.id
        WHERE p.zona_id = ? 
          AND p.tipo_torneo = 'zona'
          AND j.equipo_id = ?
          AND p.estado = 'finalizado'
    ");
    $stmt->execute([$zona_id, $equipo_id]);
    $tarjetas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Actualizar tabla equipos_zonas
    $stmt = $db->prepare("
        UPDATE equipos_zonas 
        SET 
            puntos = ?,
            partidos_jugados = ?,
            partidos_ganados = ?,
            partidos_empatados = ?,
            partidos_perdidos = ?,
            goles_favor = ?,
            goles_contra = ?,
            diferencia_gol = ?,
            tarjetas_amarillas = ?,
            tarjetas_rojas = ?
        WHERE zona_id = ? AND equipo_id = ?
    ");
    
    $stmt->execute([
        $puntos,
        $stats['partidos_jugados'] ?? 0,
        $stats['ganados'] ?? 0,
        $stats['empatados'] ?? 0,
        $stats['perdidos'] ?? 0,
        $stats['goles_favor'] ?? 0,
        $stats['goles_contra'] ?? 0,
        $stats['diferencia_gol'] ?? 0,
        $tarjetas['tarjetas_amarillas'] ?? 0,
        $tarjetas['tarjetas_rojas'] ?? 0,
        $zona_id,
        $equipo_id
    ]);
    
    return true;
}

/**
 * Obtiene la tabla de posiciones de una zona con desempates aplicados
 * Criterios de desempate (en orden):
 * 1. Mayor diferencia de goles (GF - GC)
 * 2. Mayor cantidad de goles a favor
 * 3. Resultado entre equipos empatados (enfrentamiento directo)
 * 4. Mayor diferencia de goles en enfrentamientos directos
 * 5. Mayor cantidad de goles a favor en enfrentamientos directos
 * 6. Fairplay (menos tarjetas)
 * 7. Sorteo (requiere resolución manual)
 */
function obtenerTablaPosicionesZona($zona_id, $db) {
    // Incluir funciones de desempate
    require_once __DIR__ . '/include/desempate_functions.php';
    
    // Usar la función de desempate que detecta empates pendientes
    $equipos_ordenados = calcularTablaPosicionesConDesempate($zona_id, $db);
    
    // Agregar información adicional si es necesario
    $resultado = [];
    foreach ($equipos_ordenados as $equipo) {
        $resultado[] = [
            'equipo_id' => $equipo['id'],
            'equipo' => $equipo['nombre'],
            'logo' => $equipo['logo'],
            'puntos' => $equipo['puntos'],
            'partidos_jugados' => $equipo['partidos_jugados'] ?? 0,
            'partidos_ganados' => $equipo['partidos_ganados'] ?? 0,
            'partidos_empatados' => $equipo['partidos_empatados'] ?? 0,
            'partidos_perdidos' => $equipo['partidos_perdidos'] ?? 0,
            'goles_favor' => $equipo['goles_favor'],
            'goles_contra' => $equipo['goles_contra'],
            'diferencia_gol' => $equipo['diferencia_gol'],
            'posicion' => $equipo['posicion'] ?? 0
        ];
    }
    
    return $resultado;
}

/**
 * Actualiza las posiciones en la tabla equipos_zonas
 */
function actualizarPosicionesZona($zona_id, $db) {
    // Obtener tabla de posiciones con desempates
    $equipos = obtenerTablaPosicionesZona($zona_id, $db);
    
    // Actualizar posiciones en la base de datos
    foreach ($equipos as $equipo) {
        $stmt = $db->prepare("
            UPDATE equipos_zonas 
            SET posicion = ? 
            WHERE zona_id = ? AND equipo_id = ?
        ");
        $stmt->execute([$equipo['posicion'], $zona_id, $equipo['equipo_id']]);
    }
    
    return true;
}

/**
 * Aplica los criterios de desempate a equipos con igual puntaje
 */
function aplicarDesempates($equipos, $zona_id, $db) {
    // Agrupar por puntos
    $grupos = [];
    foreach ($equipos as $equipo) {
        $puntos = $equipo['puntos'];
        if (!isset($grupos[$puntos])) {
            $grupos[$puntos] = [];
        }
        $grupos[$puntos][] = $equipo;
    }
    
    $resultado = [];
    
    foreach ($grupos as $puntos => $equipos_grupo) {
        if (count($equipos_grupo) == 1) {
            // Sin desempate necesario
            $resultado[] = $equipos_grupo[0];
        } else {
            // Aplicar desempates
            $equipos_ordenados = desempateGrupo($equipos_grupo, $zona_id, $db);
            $resultado = array_merge($resultado, $equipos_ordenados);
        }
    }
    
    return $resultado;
}

/**
 * Desempata un grupo de equipos con igual puntaje
 */
function desempateGrupo($equipos, $zona_id, $db) {
    // 1. Ordenar por diferencia de goles (mayor a menor)
    usort($equipos, function($a, $b) {
        if ($b['diferencia_gol'] != $a['diferencia_gol']) {
            return $b['diferencia_gol'] - $a['diferencia_gol'];
        }
        return 0;
    });
    
    // Verificar si hay empate después de diferencia
    $subgrupos = [];
    $subgrupo_actual = [$equipos[0]];
    
    for ($i = 1; $i < count($equipos); $i++) {
        if ($equipos[$i]['diferencia_gol'] == $equipos[$i-1]['diferencia_gol']) {
            $subgrupo_actual[] = $equipos[$i];
        } else {
            $subgrupos[] = $subgrupo_actual;
            $subgrupo_actual = [$equipos[$i]];
        }
    }
    $subgrupos[] = $subgrupo_actual;
    
    $resultado = [];
    
    foreach ($subgrupos as $subgrupo) {
        if (count($subgrupo) == 1) {
            $resultado[] = $subgrupo[0];
        } else {
            // 2. Ordenar por goles a favor (mayor a menor)
            usort($subgrupo, function($a, $b) {
                if ($b['goles_favor'] != $a['goles_favor']) {
                    return $b['goles_favor'] - $a['goles_favor'];
                }
                return 0;
            });
            
            // Verificar si hay empate después de goles a favor
            $subgrupos2 = [];
            $subgrupo2_actual = [$subgrupo[0]];
            
            for ($i = 1; $i < count($subgrupo); $i++) {
                if ($subgrupo[$i]['goles_favor'] == $subgrupo[$i-1]['goles_favor']) {
                    $subgrupo2_actual[] = $subgrupo[$i];
                } else {
                    $subgrupos2[] = $subgrupo2_actual;
                    $subgrupo2_actual = [$subgrupo[$i]];
                }
            }
            $subgrupos2[] = $subgrupo2_actual;
            
            foreach ($subgrupos2 as $subgrupo2) {
                if (count($subgrupo2) == 1) {
                    $resultado[] = $subgrupo2[0];
                } else {
                    // 3. Enfrentamiento directo
                    $subgrupo2 = desempateEnfrentamientoDirecto($subgrupo2, $zona_id, $db);
                    
                    // Si aún hay empate, aplicar tarjetas
                    if (count($subgrupo2) > 1) {
                        usort($subgrupo2, function($a, $b) {
                            // 4. Menor cantidad de tarjetas rojas
                            if ($a['tarjetas_rojas'] != $b['tarjetas_rojas']) {
                                return $a['tarjetas_rojas'] - $b['tarjetas_rojas'];
                            }
                            // 5. Menor cantidad de tarjetas amarillas
                            if ($a['tarjetas_amarillas'] != $b['tarjetas_amarillas']) {
                                return $a['tarjetas_amarillas'] - $b['tarjetas_amarillas'];
                            }
                            // 6. Sorteo (orden aleatorio fijo)
                            return strcmp($a['equipo'], $b['equipo']);
                        });
                    }
                    
                    $resultado = array_merge($resultado, $subgrupo2);
                }
            }
        }
    }
    
    return $resultado;
}

/**
 * Desempata por enfrentamiento directo
 * Calcula puntos entre los equipos empatados
 */
function desempateEnfrentamientoDirecto($equipos, $zona_id, $db) {
    $equipos_ids = array_column($equipos, 'equipo_id');
    $placeholders = implode(',', array_fill(0, count($equipos_ids), '?'));
    
    // Obtener partidos entre estos equipos (desde tabla partidos)
    $stmt = $db->prepare("
        SELECT 
            equipo_local_id,
            equipo_visitante_id,
            goles_local,
            goles_visitante
        FROM partidos
        WHERE zona_id = ?
          AND tipo_torneo = 'zona'
          AND equipo_local_id IN ($placeholders)
          AND equipo_visitante_id IN ($placeholders)
          AND estado = 'finalizado'
    ");
    $stmt->execute(array_merge([$zona_id], $equipos_ids, $equipos_ids));
    $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular puntos y estadísticas entre estos equipos
    $stats = [];
    foreach ($equipos_ids as $equipo_id) {
        $stats[$equipo_id] = [
            'puntos' => 0,
            'diferencia' => 0,
            'goles_favor' => 0
        ];
    }
    
    foreach ($partidos as $partido) {
        $local_id = $partido['equipo_local_id'];
        $visitante_id = $partido['equipo_visitante_id'];
        $goles_local = $partido['goles_local'];
        $goles_visitante = $partido['goles_visitante'];
        
        if (in_array($local_id, $equipos_ids) && in_array($visitante_id, $equipos_ids)) {
            if ($goles_local > $goles_visitante) {
                $stats[$local_id]['puntos'] += 3;
            } elseif ($goles_local < $goles_visitante) {
                $stats[$visitante_id]['puntos'] += 3;
            } else {
                $stats[$local_id]['puntos'] += 1;
                $stats[$visitante_id]['puntos'] += 1;
            }
            
            $stats[$local_id]['goles_favor'] += $goles_local;
            $stats[$local_id]['diferencia'] += ($goles_local - $goles_visitante);
            $stats[$visitante_id]['goles_favor'] += $goles_visitante;
            $stats[$visitante_id]['diferencia'] += ($goles_visitante - $goles_local);
        }
    }
    
    // Agregar estadísticas a los equipos
    foreach ($equipos as &$equipo) {
        $equipo_id = $equipo['equipo_id'];
        $equipo['puntos_directo'] = $stats[$equipo_id]['puntos'];
        $equipo['diferencia_directo'] = $stats[$equipo_id]['diferencia'];
        $equipo['goles_favor_directo'] = $stats[$equipo_id]['goles_favor'];
    }
    
    // Ordenar por enfrentamiento directo
    usort($equipos, function($a, $b) {
        if ($b['puntos_directo'] != $a['puntos_directo']) {
            return $b['puntos_directo'] - $a['puntos_directo'];
        }
        if ($b['diferencia_directo'] != $a['diferencia_directo']) {
            return $b['diferencia_directo'] - $a['diferencia_directo'];
        }
        if ($b['goles_favor_directo'] != $a['goles_favor_directo']) {
            return $b['goles_favor_directo'] - $a['goles_favor_directo'];
        }
        return 0;
    });
    
    return $equipos;
}

/**
 * Verifica si todos los partidos de grupos están finalizados
 */
function todosPartidosGruposFinalizados($formato_id, $db) {
    $stmt = $db->prepare("
        SELECT COUNT(*) as pendientes
        FROM partidos p
        JOIN zonas z ON p.zona_id = z.id
        WHERE z.formato_id = ? 
          AND p.tipo_torneo = 'zona'
          AND p.estado != 'finalizado'
    ");
    $stmt->execute([$formato_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return ($result['pendientes'] == 0);
}

/**
 * Obtiene los equipos clasificados según la configuración
 */
function obtenerEquiposClasificados($formato_id, $db) {
    // Incluir funciones de desempate
    require_once __DIR__ . '/include/desempate_functions.php';
    
    $stmt = $db->prepare("
        SELECT 
            primeros_clasifican,
            segundos_clasifican,
            terceros_clasifican,
            cuartos_clasifican
        FROM campeonatos_formato
        WHERE id = ?
    ");
    $stmt->execute([$formato_id]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $clasificados = [];
    
    // Obtener zonas
    $stmt = $db->prepare("SELECT id, nombre FROM zonas WHERE formato_id = ? ORDER BY orden");
    $stmt->execute([$formato_id]);
    $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Recalcular tablas con desempate
    foreach ($zonas as $zona) {
        calcularTablaPosicionesConDesempate($zona['id'], $db);
    }
    
    // Obtener equipos clasificados según configuración (primeros siempre, luego según config)
    foreach ($zonas as $zona) {
        // Obtener todos los equipos ordenados por posición
        $stmt = $db->prepare("
            SELECT 
                e.id as equipo_id,
                e.nombre as equipo,
                e.logo,
                ez.posicion,
                ez.puntos,
                ez.diferencia_gol as diferencia,
                z.nombre as zona
            FROM equipos_zonas ez
            INNER JOIN equipos e ON ez.equipo_id = e.id
            INNER JOIN zonas z ON ez.zona_id = z.id
            WHERE ez.zona_id = ?
            ORDER BY ez.posicion ASC
        ");
        $stmt->execute([$zona['id']]);
        $equipos_zona = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Filtrar según configuración de clasificación
        foreach ($equipos_zona as $equipo) {
            $posicion = (int)$equipo['posicion'];
            $clasifica = false;
            
            // El primero siempre clasifica
            if ($posicion == 1) {
                $clasifica = true;
            } elseif ($posicion == 2 && $config['segundos_clasifican'] > 0) {
                $clasifica = true;
            } elseif ($posicion == 3 && $config['terceros_clasifican'] > 0) {
                $clasifica = true;
            } elseif ($posicion == 4 && $config['cuartos_clasifican'] > 0) {
                $clasifica = true;
            }
            
            if ($clasifica) {
                $clasificados[] = [
                    'equipo_id' => $equipo['equipo_id'],
                    'equipo' => $equipo['equipo'],
                    'logo' => $equipo['logo'],
                    'zona' => $equipo['zona'],
                    'posicion' => $equipo['posicion'],
                    'puntos' => $equipo['puntos'],
                    'diferencia' => $equipo['diferencia']
                ];
            }
        }
    }
    
    return $clasificados;
}

/**
 * Genera los partidos de la fase eliminatoria
 * Empareja: 1° vs Último, 2° vs Penúltimo, etc.
 */
function generarFixtureEliminatorias($formato_id, $db) {
    try {
        $db->beginTransaction();
        
        // Verificar que todos los partidos de grupos estén finalizados
        if (!todosPartidosGruposFinalizados($formato_id, $db)) {
            throw new Exception("Aún hay partidos de grupos pendientes");
        }
        
        // Incluir funciones de desempate
        require_once __DIR__ . '/include/desempate_functions.php';
        
        // Recalcular todas las tablas de posiciones con desempate
        $stmt = $db->prepare("SELECT id FROM zonas WHERE formato_id = ?");
        $stmt->execute([$formato_id]);
        $zonas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($zonas as $zona_id) {
            calcularTablaPosicionesConDesempate($zona_id, $db);
        }
        
        // Verificar si hay empates pendientes de resolución
        // Si hay empates pendientes, se pueden generar las eliminatorias con los equipos ya clasificados
        // Los empates pendientes se resuelven manualmente y esos equipos se cargan después
        $hay_empates_pendientes = hayEmpatesPendientes($formato_id, $db);
        if ($hay_empates_pendientes) {
            $empates = obtenerEmpatesPendientes($formato_id, $db);
            error_log("Advertencia: Hay " . count($empates) . " empate(s) pendiente(s) de resolución. Se generarán eliminatorias con equipos ya clasificados.");
        }
        
        // Obtener configuración
        $stmt = $db->prepare("SELECT * FROM campeonatos_formato WHERE id = ?");
        $stmt->execute([$formato_id]);
        $formato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Obtener clasificados
        $clasificados = obtenerEquiposClasificados($formato_id, $db);
        
        if (count($clasificados) < 2) {
            throw new Exception("Se necesitan al menos 2 equipos clasificados");
        }
        
        // Ordenar clasificados por puntos y diferencia (para emparejar)
        usort($clasificados, function($a, $b) {
            if ($b['puntos'] != $a['puntos']) {
                return $b['puntos'] - $a['puntos'];
            }
            return $b['diferencia'] - $a['diferencia'];
        });
        
        // Obtener fases eliminatorias
        $stmt = $db->prepare("SELECT * FROM fases_eliminatorias WHERE formato_id = ? ORDER BY orden");
        $stmt->execute([$formato_id]);
        $fases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Si no hay fases eliminatorias, crearlas automáticamente según cantidad de clasificados
        if (empty($fases)) {
            $total_clasificados = count($clasificados);
            $orden = 1;
            
            // Determinar qué fases crear según cantidad de clasificados
            if ($total_clasificados >= 16) {
                $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden, activa, generada) VALUES (?, 'octavos', ?, 1, 0)");
                $stmt->execute([$formato_id, $orden++]);
            }
            if ($total_clasificados >= 8) {
                $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden, activa, generada) VALUES (?, 'cuartos', ?, 1, 0)");
                $stmt->execute([$formato_id, $orden++]);
            }
            if ($total_clasificados >= 4) {
                $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden, activa, generada) VALUES (?, 'semifinal', ?, 1, 0)");
                $stmt->execute([$formato_id, $orden++]);
            }
            if ($total_clasificados >= 2) {
                $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden, activa, generada) VALUES (?, 'tercer_puesto', ?, 1, 0)");
                $stmt->execute([$formato_id, $orden++]);
                $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden, activa, generada) VALUES (?, 'final', ?, 1, 0)");
                $stmt->execute([$formato_id, $orden++]);
            }
            
            // Obtener las fases recién creadas
            $stmt = $db->prepare("SELECT * FROM fases_eliminatorias WHERE formato_id = ? ORDER BY orden");
            $stmt->execute([$formato_id]);
            $fases = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        if (empty($fases)) {
            throw new Exception("No se pudieron crear las fases eliminatorias. Se necesitan al menos 2 equipos clasificados.");
        }
        
        // Obtener categoría para crear fechas
        $categoria_id = $formato['categoria_id'];
        if (!$categoria_id) {
            throw new Exception("No se encontró categoría para el formato");
        }
        
        // Crear fecha para la fase eliminatoria
        $stmt = $db->prepare("
            INSERT INTO fechas (categoria_id, numero_fecha, tipo_fecha, fase_eliminatoria_id, fecha_programada)
            VALUES (?, 1, 'eliminatoria', ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY))
        ");
        $stmt->execute([$categoria_id, $fases[0]['id']]);
        $fecha_id = $db->lastInsertId();
        
        // Activar primera fase
        $primera_fase = $fases[0];
        $stmt = $db->prepare("UPDATE fases_eliminatorias SET activa = 1, generada = 1 WHERE id = ?");
        $stmt->execute([$primera_fase['id']]);
        
        // Generar cruces de primera fase
        $total_clasificados = count($clasificados);
        $cantidad_cruces = floor($total_clasificados / 2);
        
        // Emparejar: 1° vs Último, 2° vs Penúltimo, etc.
        for ($i = 0; $i < $cantidad_cruces; $i++) {
            $local = $clasificados[$i];
            $visitante = $clasificados[$total_clasificados - 1 - $i];
            
            $origen_local = "{$local['posicion']}° {$local['zona']}";
            $origen_visitante = "{$visitante['posicion']}° {$visitante['zona']}";
            
            // Crear partido en tabla partidos
            $stmt = $db->prepare("
                INSERT INTO partidos 
                (fecha_id, equipo_local_id, equipo_visitante_id, fase_eliminatoria_id, numero_llave,
                 origen_local, origen_visitante, tipo_torneo, estado, fecha_partido)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'eliminatoria', 'programado', DATE_ADD(CURDATE(), INTERVAL 7 DAY))
            ");
            
            $stmt->execute([
                $fecha_id,
                $local['equipo_id'],
                $visitante['equipo_id'],
                $primera_fase['id'],
                $i + 1,
                $origen_local,
                $origen_visitante
            ]);
        }
        
        $db->commit();
        return true;
        
    } catch (Exception $e) {
        $db->rollBack();
        error_log("Error en generarFixtureEliminatorias: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Avanza equipos ganadores a la siguiente fase
 * Se llama después de finalizar todos los partidos de una fase
 */
function avanzarSiguienteFase($fase_id, $db) {
    // Obtener información de la fase
    $stmt = $db->prepare("
        SELECT fe.*, cf.id as formato_id
        FROM fases_eliminatorias fe
        JOIN campeonatos_formato cf ON fe.formato_id = cf.id
        WHERE fe.id = ?
    ");
    $stmt->execute([$fase_id]);
    $fase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Verificar que todos los partidos estén finalizados
    $stmt = $db->prepare("
        SELECT COUNT(*) as pendientes
        FROM partidos
        WHERE fase_eliminatoria_id = ? 
          AND tipo_torneo = 'eliminatoria'
          AND estado != 'finalizado'
    ");
    $stmt->execute([$fase_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['pendientes'] > 0) {
        throw new Exception("Aún hay partidos pendientes en esta fase");
    }
    
    // Obtener siguiente fase
    $stmt = $db->prepare("
        SELECT * FROM fases_eliminatorias
        WHERE formato_id = ? AND orden > ?
        ORDER BY orden
        LIMIT 1
    ");
    $stmt->execute([$fase['formato_id'], $fase['orden']]);
    $siguiente_fase = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$siguiente_fase) {
        return false; // No hay siguiente fase
    }
    
    // Obtener ganadores de esta fase (desde tabla partidos)
    $stmt = $db->prepare("
        SELECT 
            id,
            numero_llave,
            equipo_local_id,
            equipo_visitante_id,
            goles_local,
            goles_visitante,
            goles_local_penales,
            goles_visitante_penales,
            origen_local,
            origen_visitante,
            fecha_id
        FROM partidos
        WHERE fase_eliminatoria_id = ?
          AND tipo_torneo = 'eliminatoria'
        ORDER BY numero_llave
    ");
    $stmt->execute([$fase_id]);
    $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener categoría para crear fecha
    $stmt = $db->prepare("
        SELECT f.categoria_id 
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        WHERE p.fase_eliminatoria_id = ?
        LIMIT 1
    ");
    $stmt->execute([$fase_id]);
    $categoria_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $categoria_id = $categoria_info['categoria_id'] ?? null;
    
    // Determinar ganadores
    $ganadores = [];
    foreach ($partidos as $partido) {
        $ganador_id = null;
        $origen = '';
        
        // Determinar ganador (considerando penales si aplica)
        if ($partido['goles_local_penales'] !== null) {
            // Hubo penales
            if ($partido['goles_local_penales'] > $partido['goles_visitante_penales']) {
                $ganador_id = $partido['equipo_local_id'];
                $origen = $partido['origen_local'];
            } else {
                $ganador_id = $partido['equipo_visitante_id'];
                $origen = $partido['origen_visitante'];
            }
        } else {
            // Sin penales
            if ($partido['goles_local'] > $partido['goles_visitante']) {
                $ganador_id = $partido['equipo_local_id'];
                $origen = $partido['origen_local'];
            } else {
                $ganador_id = $partido['equipo_visitante_id'];
                $origen = $partido['origen_visitante'];
            }
        }
        
        $ganadores[] = [
            'equipo_id' => $ganador_id,
            'origen' => $origen,
            'llave' => $partido['numero_llave']
        ];
    }
    
    // Activar siguiente fase
    $stmt = $db->prepare("UPDATE fases_eliminatorias SET activa = 1, generada = 1 WHERE id = ?");
    $stmt->execute([$siguiente_fase['id']]);
    
    // Crear fecha para la siguiente fase
    if ($categoria_id) {
        $stmt = $db->prepare("
            INSERT INTO fechas (categoria_id, numero_fecha, tipo_fecha, fase_eliminatoria_id, fecha_programada)
            VALUES (?, 1, 'eliminatoria', ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY))
        ");
        $stmt->execute([$categoria_id, $siguiente_fase['id']]);
        $fecha_id = $db->lastInsertId();
    } else {
        throw new Exception("No se pudo obtener categoría para crear fecha");
    }
    
    // Crear partidos de la siguiente fase
    $cantidad_cruces = floor(count($ganadores) / 2);
    
    for ($i = 0; $i < $cantidad_cruces; $i++) {
        $local = $ganadores[$i];
        $visitante = $ganadores[count($ganadores) - 1 - $i];
        
        $stmt = $db->prepare("
            INSERT INTO partidos 
            (fecha_id, equipo_local_id, equipo_visitante_id, fase_eliminatoria_id, numero_llave,
             origen_local, origen_visitante, tipo_torneo, estado, fecha_partido)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'eliminatoria', 'programado', DATE_ADD(CURDATE(), INTERVAL 7 DAY))
        ");
        
        $stmt->execute([
            $fecha_id,
            $local['equipo_id'],
            $visitante['equipo_id'],
            $siguiente_fase['id'],
            $i + 1,
            $local['origen'],
            $visitante['origen']
        ]);
    }
    
    return true;
}

?>

