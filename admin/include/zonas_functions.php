<?php
/**
 * Funciones auxiliares para gestión de zonas y clasificación
 * Adaptado para la estructura de base de datos existente
 */

/**
 * Obtiene o crea el formato del campeonato
 * IMPORTANTE: Solo crea formato automáticamente para campeonatos zonales
 * Los campeonatos largos NO deben tener formato automático
 */
function obtenerFormatoCampeonato($pdo, $categoria_id) {
    // Obtener campeonato_id de la categoría
    $stmt = $pdo->prepare("SELECT campeonato_id FROM categorias WHERE id = ?");
    $stmt->execute([$categoria_id]);
    $campeonato_id = $stmt->fetchColumn();
    
    if (!$campeonato_id) return null;
    
    // Verificar el tipo de campeonato
    $stmt = $pdo->prepare("
        SELECT tipo_campeonato, es_torneo_nocturno 
        FROM campeonatos 
        WHERE id = ?
    ");
    $stmt->execute([$campeonato_id]);
    $campeonato = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$campeonato) return null;
    
    // Determinar si es zonal
    $es_zonal = ($campeonato['tipo_campeonato'] ?? 'largo') === 'zonal' || 
                ($campeonato['es_torneo_nocturno'] ?? 0) == 1;
    
    // Buscar formato existente
    $stmt = $pdo->prepare("SELECT * FROM campeonatos_formato WHERE campeonato_id = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$campeonato_id]);
    $formato = $stmt->fetch();
    
    // NO crear formato automáticamente para campeonatos largos
    // Solo los campeonatos zonales pueden tener formato
    if (!$formato && $es_zonal) {
        // Solo crear formato por defecto si es un campeonato zonal
        // Para campeonatos largos, el formato debe crearse manualmente si es necesario
        $stmt = $pdo->prepare("
            INSERT INTO campeonatos_formato 
            (campeonato_id, categoria_id, tipo_formato, cantidad_zonas, equipos_por_zona, equipos_clasifican, 
             tiene_cuartos, tiene_semifinal, tiene_tercer_puesto)
            VALUES (?, ?, 'mixto', 4, 4, 2, 1, 1, 1)
        ");
        $stmt->execute([$campeonato_id, $categoria_id]);
        return obtenerFormatoCampeonato($pdo, $categoria_id);
    }
    
    return $formato;
}

/**
 * Distribuye equipos automáticamente en zonas
 */
function distribuirEquiposEnZonas($pdo, $categoria_id, $equipos_por_zona = 4) {
    try {
        $pdo->beginTransaction();
        
        // Obtener o crear formato
        $formato = obtenerFormatoCampeonato($pdo, $categoria_id);
        if (!$formato) {
            throw new Exception("No se pudo obtener el formato del campeonato");
        }
        
        // Obtener equipos activos
        $stmt = $pdo->prepare("SELECT * FROM equipos WHERE categoria_id = ? AND activo = 1 ORDER BY nombre");
        $stmt->execute([$categoria_id]);
        $equipos = $stmt->fetchAll();
        
        $cantidad_equipos = count($equipos);
        $cantidad_zonas = ceil($cantidad_equipos / $equipos_por_zona);
        
        // Actualizar formato
        $stmt = $pdo->prepare("
            UPDATE campeonatos_formato 
            SET cantidad_zonas = ?, equipos_por_zona = ?
            WHERE id = ?
        ");
        $stmt->execute([$cantidad_zonas, $equipos_por_zona, $formato['id']]);
        
        // Eliminar zonas anteriores
        $pdo->prepare("DELETE FROM zonas WHERE formato_id = ?")->execute([$formato['id']]);
        
        // Crear zonas
        $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];
        $zonas_creadas = [];
        
        for ($i = 0; $i < $cantidad_zonas; $i++) {
            $stmt = $pdo->prepare("
                INSERT INTO zonas (formato_id, categoria_id, nombre, orden, activa) 
                VALUES (?, ?, ?, ?, 1)
            ");
            $stmt->execute([$formato['id'], $categoria_id, "Zona {$letras[$i]}", $i + 1]);
            $zonas_creadas[] = $pdo->lastInsertId();
        }
        
        // Distribuir equipos aleatoriamente (serpentina)
        shuffle($equipos);
        
        $zona_index = 0;
        $direccion = 1;
        
        foreach ($equipos as $equipo) {
            $zona_id = $zonas_creadas[$zona_index];
            
            $stmt = $pdo->prepare("
                INSERT INTO equipos_zonas (zona_id, equipo_id) 
                VALUES (?, ?)
            ");
            $stmt->execute([$zona_id, $equipo['id']]);
            
            // Movimiento serpentina
            $zona_index += $direccion;
            
            if ($zona_index >= count($zonas_creadas)) {
                $zona_index = count($zonas_creadas) - 1;
                $direccion = -1;
            } elseif ($zona_index < 0) {
                $zona_index = 0;
                $direccion = 1;
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error en distribuirEquiposEnZonas: " . $e->getMessage());
        return false;
    }
}

/**
 * Calcula los equipos clasificados según la configuración
 */
function calcularClasificados($pdo, $categoria_id, $clasifican_primeros = null, $clasifican_segundos = null) {
    $formato = obtenerFormatoCampeonato($pdo, $categoria_id);
    
    if (!$formato) {
        return [];
    }
    
    // Usar valores del formato si no se especifican
    $clasifican_primeros = $clasifican_primeros ?? $formato['equipos_clasifican'];
    $clasifican_segundos = $clasifican_segundos ?? 0;
    
    $clasificados = [];
    
    // Obtener zonas de la categoría
    $stmt = $pdo->prepare("
        SELECT * FROM zonas 
        WHERE categoria_id = ? AND activa = 1 
        ORDER BY orden
    ");
    $stmt->execute([$categoria_id]);
    $zonas = $stmt->fetchAll();
    
    // Obtener primeros de cada zona
    foreach ($zonas as $zona) {
        $stmt = $pdo->prepare("
            SELECT * FROM v_tabla_posiciones_zona 
            WHERE zona_id = ?
            ORDER BY pts DESC, dif DESC, gf DESC
            LIMIT ?
        ");
        $stmt->execute([$zona['id'], $clasifican_primeros]);
        $primeros = $stmt->fetchAll();
        
        foreach ($primeros as $equipo) {
            $clasificados[] = [
                'equipo_id' => $equipo['equipo_id'],
                'equipo' => $equipo['equipo'],
                'logo' => $equipo['logo'],
                'zona' => $equipo['zona'],
                'zona_id' => $equipo['zona_id'],
                'tipo' => 'Primero',
                'pts' => $equipo['pts'],
                'dif' => $equipo['dif'],
                'gf' => $equipo['gf']
            ];
        }
    }
    
    // Obtener mejores segundos
    if ($clasifican_segundos > 0) {
        $stmt = $pdo->prepare("
            SELECT * FROM (
                SELECT *, 
                    ROW_NUMBER() OVER (PARTITION BY zona_id ORDER BY pts DESC, dif DESC, gf DESC) as posicion_zona
                FROM v_tabla_posiciones_zona 
                WHERE categoria_id = ?
            ) t
            WHERE posicion_zona = 2
            ORDER BY pts DESC, dif DESC, gf DESC
            LIMIT ?
        ");
        $stmt->execute([$categoria_id, $clasifican_segundos]);
        $segundos = $stmt->fetchAll();
        
        foreach ($segundos as $equipo) {
            $clasificados[] = [
                'equipo_id' => $equipo['equipo_id'],
                'equipo' => $equipo['equipo'],
                'logo' => $equipo['logo'],
                'zona' => $equipo['zona'],
                'zona_id' => $equipo['zona_id'],
                'tipo' => 'Mejor Segundo',
                'pts' => $equipo['pts'],
                'dif' => $equipo['dif'],
                'gf' => $equipo['gf']
            ];
        }
    }
    
    return $clasificados;
}

/**
 * Genera los cruces para eliminatorias
 */
function generarCruces($clasificados) {
    $cruces = [];
    $total = count($clasificados);
    $cantidad_cruces = floor($total / 2);
    
    // Emparejar: 1° vs último, 2° vs penúltimo, etc.
    for ($i = 0; $i < $cantidad_cruces; $i++) {
        $cruces[] = [
            'llave' => $i + 1,
            'local' => $clasificados[$i],
            'visitante' => $clasificados[$total - 1 - $i]
        ];
    }
    
    return $cruces;
}

/**
 * Genera fixture de eliminatorias
 */
function generarFixtureEliminatorias($pdo, $categoria_id, $config = []) {
    try {
        $pdo->beginTransaction();
        
        $formato = obtenerFormatoCampeonato($pdo, $categoria_id);
        if (!$formato) {
            throw new Exception("No se encontró formato del campeonato");
        }
        
        // Obtener clasificados
        $clasificados = calcularClasificados($pdo, $categoria_id);
        $total_clasificados = count($clasificados);
        
        if ($total_clasificados < 2) {
            throw new Exception("Se necesitan al menos 2 equipos clasificados");
        }
        
        // Eliminar fases anteriores
        $pdo->prepare("DELETE FROM fases_eliminatorias WHERE formato_id = ?")->execute([$formato['id']]);
        
        $orden = 1;
        $ida_vuelta = $config['ida_vuelta'] ?? false;
        
        // Determinar fases necesarias
        $fases_crear = [];
        
        if ($total_clasificados >= 9 && $config['incluir_octavos']) {
            $fases_crear[] = ['nombre' => 'octavos', 'equipos' => 16];
        }
        
        if ($total_clasificados >= 5 && $config['incluir_cuartos']) {
            $fases_crear[] = ['nombre' => 'cuartos', 'equipos' => 8];
        }
        
        if ($total_clasificados >= 3) {
            $fases_crear[] = ['nombre' => 'semifinal', 'equipos' => 4];
        }
        
        if ($total_clasificados >= 2) {
            $fases_crear[] = ['nombre' => 'tercer_puesto', 'equipos' => 2];
            $fases_crear[] = ['nombre' => 'final', 'equipos' => 2];
        }
        
        // Crear fases
        $fase_ids = [];
        foreach ($fases_crear as $fase_config) {
            $stmt = $pdo->prepare("
                INSERT INTO fases_eliminatorias 
                (formato_id, categoria_id, nombre, cantidad_equipos, ida_vuelta, orden, activa)
                VALUES (?, ?, ?, ?, ?, ?, 1)
            ");
            $stmt->execute([
                $formato['id'],
                $categoria_id,
                $fase_config['nombre'],
                $fase_config['equipos'],
                ($fase_config['nombre'] == 'final' || $fase_config['nombre'] == 'tercer_puesto') ? 0 : $ida_vuelta,
                $orden++
            ]);
            $fase_ids[$fase_config['nombre']] = $pdo->lastInsertId();
        }
        
        // Generar cruces de primera fase
        $primera_fase = reset($fases_crear)['nombre'];
        if (isset($fase_ids[$primera_fase])) {
            $cruces = generarCruces($clasificados);
            
            foreach ($cruces as $cruce) {
                $stmt = $pdo->prepare("
                    INSERT INTO partidos_eliminatorios 
                    (fase_id, equipo_local_id, equipo_visitante_id, numero_llave, 
                     origen_local, origen_visitante, estado)
                    VALUES (?, ?, ?, ?, ?, ?, 'pendiente')
                ");
                $stmt->execute([
                    $fase_ids[$primera_fase],
                    $cruce['local']['equipo_id'],
                    $cruce['visitante']['equipo_id'],
                    $cruce['llave'],
                    $cruce['local']['tipo'] . ' - ' . $cruce['local']['zona'],
                    $cruce['visitante']['tipo'] . ' - ' . $cruce['visitante']['zona']
                ]);
            }
        }
        
        $pdo->commit();
        return true;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error en generarFixtureEliminatorias: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene tabla de posiciones de una zona
 */
function obtenerTablaPosicionesZona($pdo, $zona_id) {
    $stmt = $pdo->prepare("
        SELECT * FROM v_tabla_posiciones_zona 
        WHERE zona_id = ?
        ORDER BY posicion, pts DESC, dif DESC, gf DESC
    ");
    $stmt->execute([$zona_id]);
    return $stmt->fetchAll();
}

/**
 * Obtiene estadísticas de la categoría
 */
function obtenerEstadisticasCategoria($pdo, $categoria_id) {
    $stats = [];
    
    // Total de equipos
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM equipos WHERE categoria_id = ? AND activo = 1");
    $stmt->execute([$categoria_id]);
    $stats['total_equipos'] = $stmt->fetchColumn();
    
    // Total de zonas
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM zonas WHERE categoria_id = ? AND activa = 1");
    $stmt->execute([$categoria_id]);
    $stats['total_zonas'] = $stmt->fetchColumn();
    
    // Equipos por zona (promedio)
    if ($stats['total_zonas'] > 0) {
        $stats['equipos_por_zona'] = round($stats['total_equipos'] / $stats['total_zonas'], 1);
    } else {
        $stats['equipos_por_zona'] = 0;
    }
    
    // Partidos jugados de zona
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM partidos_zona pz
        INNER JOIN zonas z ON pz.zona_id = z.id
        WHERE z.categoria_id = ? AND pz.estado = 'finalizado'
    ");
    $stmt->execute([$categoria_id]);
    $stats['partidos_jugados'] = $stmt->fetchColumn();
    
    // Partidos pendientes de zona
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM partidos_zona pz
        INNER JOIN zonas z ON pz.zona_id = z.id
        WHERE z.categoria_id = ? AND pz.estado = 'programado'
    ");
    $stmt->execute([$categoria_id]);
    $stats['partidos_pendientes'] = $stmt->fetchColumn();
    
    // Total de goles
    $stmt = $pdo->prepare("
        SELECT SUM(pz.goles_local + pz.goles_visitante) FROM partidos_zona pz
        INNER JOIN zonas z ON pz.zona_id = z.id
        WHERE z.categoria_id = ? AND pz.estado = 'finalizado'
    ");
    $stmt->execute([$categoria_id]);
    $stats['total_goles'] = $stmt->fetchColumn() ?: 0;
    
    // Promedio de goles por partido
    if ($stats['partidos_jugados'] > 0) {
        $stats['promedio_goles'] = round($stats['total_goles'] / $stats['partidos_jugados'], 2);
    } else {
        $stats['promedio_goles'] = 0;
    }
    
    return $stats;
}

/**
 * Determina las fases necesarias según cantidad de equipos
 */
function determinarFasesNecesarias($total_equipos) {
    $fases = [];
    
    if ($total_equipos >= 9 && $total_equipos <= 16) {
        $fases[] = [
            'tipo' => 'octavos',
            'nombre' => 'Octavos de Final',
            'cantidad_equipos' => 16,
            'cantidad_partidos' => 8
        ];
    }
    
    if ($total_equipos >= 5 && $total_equipos <= 8) {
        $fases[] = [
            'tipo' => 'cuartos',
            'nombre' => 'Cuartos de Final',
            'cantidad_equipos' => 8,
            'cantidad_partidos' => 4
        ];
    }
    
    if ($total_equipos >= 3 && $total_equipos <= 4) {
        $fases[] = [
            'tipo' => 'semifinal',
            'nombre' => 'Semifinales',
            'cantidad_equipos' => 4,
            'cantidad_partidos' => 2
        ];
    }
    
    if ($total_equipos >= 2) {
        $fases[] = [
            'tipo' => 'tercer_puesto',
            'nombre' => 'Tercer Puesto',
            'cantidad_equipos' => 2,
            'cantidad_partidos' => 1
        ];
        
        $fases[] = [
            'tipo' => 'final',
            'nombre' => 'Final',
            'cantidad_equipos' => 2,
            'cantidad_partidos' => 1
        ];
    }
    
    return $fases;
}
?>