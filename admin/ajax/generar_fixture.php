<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $formato_id = $_POST['formato_id'] ?? null;
    $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
    $dias_entre_fechas = (int)($_POST['dias_entre_fechas'] ?? 7);
    $tipo_fixture = $_POST['tipo_fixture'] ?? 'todos_contra_todos';
    
    if (!$formato_id) {
        throw new Exception('Formato no especificado');
    }
    
    $db->beginTransaction();
    
    // Obtener formato
    $stmt = $db->prepare("SELECT * FROM campeonatos_formato WHERE id = ?");
    $stmt->execute([$formato_id]);
    $formato = $stmt->fetch();
    
    if (!$formato) {
        throw new Exception('Formato no encontrado');
    }
    
    // Limpiar partidos existentes
    $stmt = $db->prepare("
        DELETE pz FROM partidos_zona pz
        JOIN zonas z ON pz.zona_id = z.id
        WHERE z.formato_id = ?
    ");
    $stmt->execute([$formato_id]);
    
    $stmt = $db->prepare("
        DELETE pe FROM partidos_eliminatorios pe
        JOIN fases_eliminatorias fe ON pe.fase_id = fe.id
        WHERE fe.formato_id = ?
    ");
    $stmt->execute([$formato_id]);
    
    // Obtener zonas
    $stmt = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
    $stmt->execute([$formato_id]);
    $zonas = $stmt->fetchAll();
    
    $partidos_zona_count = 0;
    $fecha_actual = new DateTime($fecha_inicio);
    
    // ============= GENERAR PARTIDOS DE ZONA =============
    foreach ($zonas as $zona) {
        // Obtener equipos de la zona
        $stmt = $db->prepare("
            SELECT e.id, e.nombre
            FROM equipos_zonas ez
            JOIN equipos e ON ez.equipo_id = e.id
            WHERE ez.zona_id = ?
            ORDER BY ez.posicion
        ");
        $stmt->execute([$zona['id']]);
        $equipos = $stmt->fetchAll();
        
        if (count($equipos) < 2) {
            throw new Exception("La zona {$zona['nombre']} no tiene suficientes equipos");
        }
        
        // Generar fixture todos contra todos
        $partidos = generarTodosContraTodos($equipos);
        
        // Si es ida y vuelta, duplicar partidos invirtiendo local/visitante
        if ($tipo_fixture === 'ida_vuelta') {
            $partidos_vuelta = [];
            foreach ($partidos as $fecha => $partidos_fecha) {
                foreach ($partidos_fecha as $partido) {
                    $partidos_vuelta[$fecha][] = [
                        'local' => $partido['visitante'],
                        'visitante' => $partido['local']
                    ];
                }
            }
            // Agregar partidos de vuelta después de la ida
            $ultima_fecha = max(array_keys($partidos));
            foreach ($partidos_vuelta as $fecha => $partidos_fecha) {
                $partidos[$ultima_fecha + $fecha] = $partidos_fecha;
            }
        }
        
        // Insertar partidos en la base de datos SIN cancha ni horario
        $stmt_insert = $db->prepare("
            INSERT INTO partidos_zona 
            (zona_id, equipo_local_id, equipo_visitante_id, fecha_numero, fecha_partido, hora_partido, cancha_id, estado)
            VALUES (?, ?, ?, ?, ?, NULL, NULL, 'programado')
        ");
        
        $fecha_temp = clone $fecha_actual;
        
        foreach ($partidos as $numero_fecha => $partidos_fecha) {
            foreach ($partidos_fecha as $partido) {
                $stmt_insert->execute([
                    $zona['id'],
                    $partido['local']['id'],
                    $partido['visitante']['id'],
                    $numero_fecha,
                    $fecha_temp->format('Y-m-d')
                ]);
                $partidos_zona_count++;
            }
            $fecha_temp->modify("+{$dias_entre_fechas} days");
        }
    }
    
    // ============= GENERAR PARTIDOS ELIMINATORIOS =============
    $stmt = $db->prepare("
        SELECT * FROM fases_eliminatorias
        WHERE formato_id = ?
        ORDER BY orden
    ");
    $stmt->execute([$formato_id]);
    $fases = $stmt->fetchAll();
    
    $partidos_eliminatorios_count = 0;
    $fecha_eliminatorias = clone $fecha_temp;
    
    foreach ($fases as $fase) {
        $cantidad_partidos = 0;
        
        switch ($fase['nombre']) {
            case 'octavos':
                $cantidad_partidos = 8;
                break;
            case 'cuartos':
                $cantidad_partidos = 4;
                break;
            case 'semifinal':
                $cantidad_partidos = 2;
                break;
            case 'final':
                $cantidad_partidos = 1;
                break;
            case 'tercer_puesto':
                $cantidad_partidos = 1;
                break;
        }
        
        $stmt_insert = $db->prepare("
            INSERT INTO partidos_eliminatorios 
            (fase_id, numero_llave, origen_local, origen_visitante, fecha_partido, hora_partido, cancha_id, estado)
            VALUES (?, ?, ?, ?, ?, NULL, NULL, 'pendiente')
        ");
        
        // Generar descripciones de origen según la fase
        for ($i = 1; $i <= $cantidad_partidos; $i++) {
            $origen_local = '';
            $origen_visitante = '';
            
            if ($fase['nombre'] === 'octavos') {
                // Los octavos se llenan con clasificados de zonas
                $zona_local = chr(64 + (($i - 1) * 2 + 1)); // A, C, E, G...
                $zona_visitante = chr(64 + (($i - 1) * 2 + 2)); // B, D, F, H...
                $posicion_local = (($i - 1) % 2) + 1; // 1° o 2°
                $posicion_visitante = 3 - $posicion_local; // 2° o 1°
                
                $origen_local = "{$posicion_local}° Zona {$zona_local}";
                $origen_visitante = "{$posicion_visitante}° Zona {$zona_visitante}";
            } elseif ($fase['nombre'] === 'cuartos' && !$formato['tiene_octavos']) {
                // Si no hay octavos, los cuartos se llenan con clasificados de zonas
                $zona_local = chr(64 + (($i - 1) * 2 + 1));
                $zona_visitante = chr(64 + (($i - 1) * 2 + 2));
                $posicion_local = (($i - 1) % 2) + 1;
                $posicion_visitante = 3 - $posicion_local;
                
                $origen_local = "{$posicion_local}° Zona {$zona_local}";
                $origen_visitante = "{$posicion_visitante}° Zona {$zona_visitante}";
            } else {
                // Para otras fases, se llenan con ganadores de la fase anterior
                $origen_local = "Ganador Llave " . (($i - 1) * 2 + 1);
                $origen_visitante = "Ganador Llave " . (($i - 1) * 2 + 2);
            }
            
            $stmt_insert->execute([
                $fase['id'],
                $i,
                $origen_local,
                $origen_visitante,
                $fecha_eliminatorias->format('Y-m-d')
            ]);
            $partidos_eliminatorios_count++;
        }
        
        // Avanzar fecha para la siguiente fase
        $fecha_eliminatorias->modify("+{$dias_entre_fechas} days");
    }
    
    $db->commit();
    
    logActivity("Fixture generado para formato $formato_id: $partidos_zona_count partidos de zona, $partidos_eliminatorios_count eliminatorios");
    
    echo json_encode([
        'success' => true,
        'message' => 'Fixture generado exitosamente. Ahora puede asignar canchas y horarios.',
        'partidos_zona' => $partidos_zona_count,
        'partidos_eliminatorios' => $partidos_eliminatorios_count
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error al generar fixture: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Genera fixture todos contra todos usando algoritmo Round-Robin
 */
function generarTodosContraTodos($equipos) {
    $n = count($equipos);
    
    // Si hay número impar de equipos, agregar un "dummy" (equipo fantasma)
    $tiene_dummy = ($n % 2 != 0);
    if ($tiene_dummy) {
        $equipos[] = ['id' => null, 'nombre' => 'DESCANSO'];
        $n++;
    }
    
    $total_fechas = $n - 1;
    $partidos_por_fecha = $n / 2;
    
    $fixture = [];
    
    // Algoritmo Round-Robin
    for ($fecha = 1; $fecha <= $total_fechas; $fecha++) {
        $fixture[$fecha] = [];
        
        for ($i = 0; $i < $partidos_por_fecha; $i++) {
            $local_idx = ($fecha + $i - 1) % ($n - 1);
            $visitante_idx = ($n - 1 - $i + $fecha - 1) % ($n - 1);
            
            // El último equipo siempre está fijo
            if ($i == 0) {
                $visitante_idx = $n - 1;
            }
            
            $local = $equipos[$local_idx];
            $visitante = $equipos[$visitante_idx];
            
            // Alternar local/visitante en fechas pares
            if ($fecha % 2 == 0) {
                $temp = $local;
                $local = $visitante;
                $visitante = $temp;
            }
            
            // No agregar partidos con el equipo dummy
            if ($local['id'] !== null && $visitante['id'] !== null) {
                $fixture[$fecha][] = [
                    'local' => $local,
                    'visitante' => $visitante
                ];
            }
        }
    }
    
    return $fixture;
}
?>