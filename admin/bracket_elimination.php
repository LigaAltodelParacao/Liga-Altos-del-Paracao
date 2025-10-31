<?php
// Generar bracket de eliminación directa con siembra
function generarBracketEliminatorio($torneo_id, $conn) {
    // Verificar que todos los partidos de fase clasificatoria estén completos
    $sql_verificar = "SELECT COUNT(*) as pendientes 
                      FROM partidos p
                      INNER JOIN zonas z ON p.zona_id = z.id
                      WHERE z.torneo_id = ? 
                      AND p.fase = 'clasificatoria'
                      AND (p.goles_local IS NULL OR p.goles_visitante IS NULL)";
    
    $stmt = $conn->prepare($sql_verificar);
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['pendientes'] > 0) {
        return ["error" => "Aún hay {$row['pendientes']} partidos pendientes en la fase clasificatoria"];
    }
    
    // Obtener equipos clasificados ordenados por ranking
    $equipos_clasificados = obtenerEquiposClasificados($torneo_id, $conn);
    
    if (empty($equipos_clasificados)) {
        return ["error" => "No hay equipos clasificados"];
    }
    
    // Calcular potencia de 2 más cercana
    $num_equipos = count($equipos_clasificados);
    $potencia_2 = pow(2, ceil(log($num_equipos, 2)));
    
    // Generar bracket con siembra
    $bracket = generarSiembra($equipos_clasificados, $potencia_2);
    
    // Crear partidos en la base de datos
    $ronda = calcularRondaInicial($num_equipos);
    crearPartidosEliminatorios($torneo_id, $bracket, $ronda, $conn);
    
    return ["success" => "Bracket generado exitosamente con {$num_equipos} equipos"];
}

function obtenerEquiposClasificados($torneo_id, $conn) {
    $clasificados = [];
    
    // Obtener configuración de clasificación
    $sql_config = "SELECT primeros, segundos, terceros, cuartos FROM torneos WHERE id = ?";
    $stmt = $conn->prepare($sql_config);
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $config = $stmt->get_result()->fetch_assoc();
    
    // Obtener todas las zonas del torneo
    $sql_zonas = "SELECT id FROM zonas WHERE torneo_id = ? ORDER BY nombre";
    $stmt = $conn->prepare($sql_zonas);
    $stmt->bind_param("i", $torneo_id);
    $stmt->execute();
    $zonas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    foreach ($zonas as $zona) {
        $zona_id = $zona['id'];
        
        // Calcular tabla de posiciones
        $posiciones = calcularTablaPosiciones($zona_id, $conn);
        
        // Agregar primeros (siempre clasifican)
        if (isset($posiciones[0])) {
            $clasificados[] = array_merge($posiciones[0], ['posicion_zona' => 1, 'zona_id' => $zona_id]);
        }
        
        // Agregar segundos si corresponde
        if ($config['segundos'] > 0 && isset($posiciones[1])) {
            $clasificados[] = array_merge($posiciones[1], ['posicion_zona' => 2, 'zona_id' => $zona_id]);
        }
        
        // Agregar terceros si corresponde
        if ($config['terceros'] > 0 && isset($posiciones[2])) {
            $clasificados[] = array_merge($posiciones[2], ['posicion_zona' => 3, 'zona_id' => $zona_id]);
        }
        
        // Agregar cuartos si corresponde
        if ($config['cuartos'] > 0 && isset($posiciones[3])) {
            $clasificados[] = array_merge($posiciones[3], ['posicion_zona' => 4, 'zona_id' => $zona_id]);
        }
    }
    
    // Ordenar por: posición en zona, diferencia de gol, goles a favor
    usort($clasificados, function($a, $b) {
        if ($a['posicion_zona'] != $b['posicion_zona']) {
            return $a['posicion_zona'] - $b['posicion_zona'];
        }
        if ($a['dif_gol'] != $b['dif_gol']) {
            return $b['dif_gol'] - $a['dif_gol'];
        }
        return $b['gf'] - $a['gf'];
    });
    
    return $clasificados;
}

function calcularTablaPosiciones($zona_id, $conn) {
    $sql = "SELECT e.id, e.nombre,
            COUNT(DISTINCT p.id) as pj,
            SUM(CASE 
                WHEN (p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante) OR
                     (p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local)
                THEN 1 ELSE 0 END) as pg,
            SUM(CASE 
                WHEN p.goles_local = p.goles_visitante
                THEN 1 ELSE 0 END) as pe,
            SUM(CASE 
                WHEN (p.equipo_local_id = e.id AND p.goles_local < p.goles_visitante) OR
                     (p.equipo_visitante_id = e.id AND p.goles_visitante < p.goles_local)
                THEN 1 ELSE 0 END) as pp,
            SUM(CASE 
                WHEN p.equipo_local_id = e.id THEN p.goles_local
                WHEN p.equipo_visitante_id = e.id THEN p.goles_visitante
                ELSE 0 END) as gf,
            SUM(CASE 
                WHEN p.equipo_local_id = e.id THEN p.goles_visitante
                WHEN p.equipo_visitante_id = e.id THEN p.goles_local
                ELSE 0 END) as gc,
            (SUM(CASE 
                WHEN p.equipo_local_id = e.id THEN p.goles_local
                WHEN p.equipo_visitante_id = e.id THEN p.goles_visitante
                ELSE 0 END) - SUM(CASE 
                WHEN p.equipo_local_id = e.id THEN p.goles_visitante
                WHEN p.equipo_visitante_id = e.id THEN p.goles_local
                ELSE 0 END)) as dif_gol,
            (SUM(CASE 
                WHEN (p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante) OR
                     (p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local)
                THEN 3
                WHEN p.goles_local = p.goles_visitante
                THEN 1 ELSE 0 END)) as puntos
            FROM equipos e
            INNER JOIN zona_equipos ze ON e.id = ze.equipo_id
            LEFT JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
                AND p.zona_id = ? AND p.fase = 'clasificatoria'
                AND p.goles_local IS NOT NULL AND p.goles_visitante IS NOT NULL
            WHERE ze.zona_id = ?
            GROUP BY e.id
            ORDER BY puntos DESC, dif_gol DESC, gf DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $zona_id, $zona_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function generarSiembra($equipos, $potencia_2) {
    $bracket = [];
    $num_equipos = count($equipos);
    
    // Si el número de equipos es potencia de 2, todos juegan en primera ronda
    if ($num_equipos == $potencia_2) {
        for ($i = 0; $i < $num_equipos / 2; $i++) {
            $bracket[] = [
                'equipo1' => $equipos[$i],
                'equipo2' => $equipos[$num_equipos - 1 - $i]
            ];
        }
    } else {
        // Algunos equipos tienen bye
        $con_bye = $potencia_2 - $num_equipos;
        $sin_bye = $num_equipos - $con_bye;
        
        // Los mejores sembrados tienen bye
        $idx = 0;
        for ($i = 0; $i < $sin_bye / 2; $i++) {
            $bracket[] = [
                'equipo1' => $equipos[$con_bye + $i],
                'equipo2' => $equipos[$num_equipos - 1 - $i]
            ];
        }
        
        // Equipos con bye pasan directo
        for ($i = 0; $i < $con_bye; $i++) {
            $bracket[] = [
                'equipo1' => $equipos[$i],
                'equipo2' => null // Bye
            ];
        }
    }
    
    return $bracket;
}

function calcularRondaInicial($num_equipos) {
    $rondas = [
        2 => 'Final',
        4 => 'Semifinal',
        8 => 'Cuartos',
        16 => 'Octavos',
        32 => 'Dieciseisavos'
    ];
    
    $potencia = pow(2, ceil(log($num_equipos, 2)));
    return isset($rondas[$potencia]) ? $rondas[$potencia] : "Ronda de {$potencia}";
}

function crearPartidosEliminatorios($torneo_id, $bracket, $ronda, $conn) {
    $orden = 1;
    
    foreach ($bracket as $enfrentamiento) {
        if ($enfrentamiento['equipo2'] === null) {
            // Equipo con bye, pasa automáticamente
            continue;
        }
        
        $sql = "INSERT INTO partidos (torneo_id, fase, ronda, equipo_local_id, equipo_visitante_id, orden)
                VALUES (?, 'eliminatoria', ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isiii", 
            $torneo_id,
            $ronda,
            $enfrentamiento['equipo1']['id'],
            $enfrentamiento['equipo2']['id'],
            $orden
        );
        $stmt->execute();
        $orden++;
    }
}
?>