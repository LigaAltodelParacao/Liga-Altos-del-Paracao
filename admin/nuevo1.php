<?php
// Configuración de errores mejorada
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php_errors.log');

// Iniciar output buffering para capturar errores
ob_start();

// Manejar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        echo '<!DOCTYPE html><html><head><title>Error</title><style>body{font-family:Arial;padding:20px;background:#f5f5f5;} .error{background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}</style></head><body><div class="error"><h2 style="color:#dc3545;">Error Fatal</h2><p><strong>Archivo:</strong> ' . htmlspecialchars($error['file']) . '</p><p><strong>Línea:</strong> ' . $error['line'] . '</p><p><strong>Mensaje:</strong> ' . htmlspecialchars($error['message']) . '</p></div></body></html>';
        ob_end_flush();
        exit;
    }
});

try {
    // Cargar config
    $config_path = __DIR__ . '/../config.php';
    if (!file_exists($config_path)) {
        throw new Exception('No se encontró config.php en: ' . $config_path);
    }
    require_once $config_path;
    
    // Cargar funciones de sanciones
    $sanciones_path = __DIR__ . '/include/sanciones_functions.php';
    if (!file_exists($sanciones_path)) {
        throw new Exception('No se encontró sanciones_functions.php en: ' . $sanciones_path);
    }
    require_once $sanciones_path;
    
    // Verificar autenticación
    if (!function_exists('isLoggedIn') || !function_exists('hasPermission')) {
        throw new Exception('Funciones de autenticación no disponibles');
    }
    
    if (!isLoggedIn() || !hasPermission('admin')) {
        if (function_exists('redirect')) {
            redirect('../login.php');
        } else {
            header('Location: ../login.php');
            exit;
        }
    }
} catch (Exception $e) {
    ob_clean();
    echo '<!DOCTYPE html><html><head><title>Error</title><style>body{font-family:Arial;padding:20px;background:#f5f5f5;} .error{background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}</style></head><body><div class="error"><h2 style="color:#dc3545;">Error de Inicialización</h2><p>' . htmlspecialchars($e->getMessage()) . '</p><p><strong>Directorio actual:</strong> ' . htmlspecialchars(__DIR__) . '</p></div></body></html>';
    ob_end_flush();
    exit;
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action == 'cargar_resultado' || $action == 'editar_resultado') {
        try {
            $db->beginTransaction();
            $partido_id = (int)$_POST['partido_id'];
            $goles_local = (int)$_POST['goles_local'];
            $goles_visitante = (int)$_POST['goles_visitante'];
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Actualizar resultado del partido
            $stmt = $db->prepare("
                UPDATE partidos 
                SET goles_local = ?, goles_visitante = ?, observaciones = ?, estado = 'finalizado', finalizado_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$goles_local, $goles_visitante, $observaciones, $partido_id]);

            // === REGISTRAR JUGADORES QUE JUGARON ===
            $stmt = $db->prepare("DELETE FROM jugadores_partido WHERE partido_id = ?");
            $stmt->execute([$partido_id]);

            // Obtener info del partido
            $stmt = $db->prepare("SELECT equipo_local_id, equipo_visitante_id, fase_eliminatoria_id FROM partidos WHERE id = ?");
            $stmt->execute([$partido_id]);
            $partido_info = $stmt->fetch();

            // Función para procesar jugadores por equipo
            $procesarJugadores = function($equipo_id, $numeros_array) use ($db, $partido_id) {
                // Obtener todos los jugadores del equipo
                $stmt = $db->prepare("SELECT id FROM jugadores WHERE equipo_id = ? AND activo = 1");
                $stmt->execute([$equipo_id]);
                $todos_jugadores = $stmt->fetchAll(PDO::FETCH_COLUMN);

                // Extraer IDs de jugadores que tienen número asignado
                $jugadores_con_numero = [];
                foreach ($numeros_array as $num) {
                    if (!empty($num['numero']) && !empty($num['jugador_id'])) {
                        $jugadores_con_numero[] = (int)$num['jugador_id'];
                    }
                }

                // Si hay al menos un número cargado → solo juegan esos
                // Si no hay números → juegan todos
                $jugadores_a_registrar = empty($jugadores_con_numero) ? $todos_jugadores : $jugadores_con_numero;

                // Insertar en jugadores_partido
                foreach ($jugadores_a_registrar as $jug_id) {
                    $stmt = $db->prepare("INSERT INTO jugadores_partido (partido_id, jugador_id) VALUES (?, ?)");
                    $stmt->execute([$partido_id, $jug_id]);
                }
            };

            // Procesar local y visitante
            if (!empty($_POST['numeros_local'])) {
                $procesarJugadores($partido_info['equipo_local_id'], $_POST['numeros_local']);
            }
            if (!empty($_POST['numeros_visitante'])) {
                $procesarJugadores($partido_info['equipo_visitante_id'], $_POST['numeros_visitante']);
            }

            // Limpiar eventos anteriores del partido
            $stmt = $db->prepare("DELETE FROM eventos_partido WHERE partido_id = ?");
            $stmt->execute([$partido_id]);

            // Registrar goles
            if (!empty($_POST['goles']) && is_array($_POST['goles'])) {
                foreach ($_POST['goles'] as $gol) {
                    if (!empty($gol['jugador_id'])) {
                        $stmt = $db->prepare("INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto) VALUES (?, ?, 'gol', 0)");
                        $stmt->execute([$partido_id, (int)$gol['jugador_id']]);
                    }
                }
            }

            // Registrar tarjetas (sin modificar - el sistema las procesará después)
            if (!empty($_POST['tarjetas']) && is_array($_POST['tarjetas'])) {
                foreach ($_POST['tarjetas'] as $tarjeta) {
                    if (!empty($tarjeta['jugador_id']) && !empty($tarjeta['tipo'])) {
                        $jugador_id = (int)$tarjeta['jugador_id'];
                        $tipo = $tarjeta['tipo'];
                        $stmt = $db->prepare("INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto) VALUES (?, ?, ?, 0)");
                        $stmt->execute([$partido_id, $jugador_id, $tipo]);
                    }
                }
            }

            // === CORREGIR DOBLE AMARILLA AUTOMÁTICAMENTE ===
            $stmt = $db->prepare("
                SELECT jugador_id, COUNT(*) as amarillas
                FROM eventos_partido
                WHERE partido_id = ? AND tipo_evento = 'amarilla'
                GROUP BY jugador_id
                HAVING COUNT(*) >= 2
            ");
            $stmt->execute([$partido_id]);
            $dobles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($dobles as $doble) {
                $stmt = $db->prepare("DELETE FROM eventos_partido WHERE partido_id = ? AND jugador_id = ? AND tipo_evento = 'amarilla'");
                $stmt->execute([$partido_id, $doble['jugador_id']]);
                
                $stmt = $db->prepare("INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto, observaciones) VALUES (?, ?, 'roja', 0, 'Doble amarilla')");
                $stmt->execute([$partido_id, $doble['jugador_id']]);
            }

            // === PROCESAR TANDA DE PENALES (SOLO PARA FASES ELIMINATORIAS) ===
            if (!empty($partido_info['fase_eliminatoria_id'])) {
                $hubo_empate = ($goles_local == $goles_visitante);
                $hay_penales = !empty($_POST['penales']) && is_array($_POST['penales']);
                
                if ($hubo_empate && $hay_penales) {
                    $stmt = $db->prepare("DELETE FROM penales_partido WHERE partido_id = ?");
                    $stmt->execute([$partido_id]);
                    
                    $penales_local_anotados = 0;
                    $penales_visitante_anotados = 0;
                    
                    foreach ($_POST['penales'] as $penal) {
                        if (!empty($penal['jugador_id']) && isset($penal['anotado'])) {
                            $jugador_id = (int)$penal['jugador_id'];
                            $anotado = (int)$penal['anotado'];
                            $orden = (int)($penal['orden'] ?? 0);
                            
                            $stmt = $db->prepare("SELECT equipo_id FROM jugadores WHERE id = ?");
                            $stmt->execute([$jugador_id]);
                            $jugador_equipo = $stmt->fetchColumn();
                            
                            $stmt = $db->prepare("
                                INSERT INTO penales_partido (partido_id, jugador_id, anotado, orden) 
                                VALUES (?, ?, ?, ?)
                            ");
                            $stmt->execute([$partido_id, $jugador_id, $anotado, $orden]);
                            
                            if ($anotado == 1) {
                                if ($jugador_equipo == $partido_info['equipo_local_id']) {
                                    $penales_local_anotados++;
                                } else {
                                    $penales_visitante_anotados++;
                                }
                            }
                        }
                    }
                    
                    $stmt = $db->prepare("
                        UPDATE partidos 
                        SET penales_local = ?, penales_visitante = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$penales_local_anotados, $penales_visitante_anotados, $partido_id]);
                    
                    $message .= " Tanda de penales registrada: {$penales_local_anotados}-{$penales_visitante_anotados}.";
                }
            }

            // === PROCESAR SANCIONES ===
            $resultado_sanciones_creacion = procesarEventosYCrearSanciones($partido_id, $db);
            if (!$resultado_sanciones_creacion['success']) {
                throw new Exception($resultado_sanciones_creacion['message'] ?? 'Error al crear sanciones');
            }

            $sanciones_actualizadas = 0;
            $detalle_sanciones = [];
            if (function_exists('cumplirSancionesAutomaticas')) {
                $resultado_sanciones = cumplirSancionesAutomaticas($partido_id, $db);
                if ($resultado_sanciones['success']) {
                    $sanciones_actualizadas = $resultado_sanciones['actualizados'];
                    $detalle_sanciones = $resultado_sanciones['detalle'] ?? [];
                    if ($sanciones_actualizadas > 0) {
                        $log_msg = "Sanciones cumplidas en partido $partido_id: ";
                        foreach ($detalle_sanciones as $det) {
                            $jugador_nombre = $det['jugador'] ?? ($det['nombre'] ?? 'Desconocido');
                            $equipo_nombre = $det['equipo'] ?? 'Desconocido';
                            $log_msg .= "$jugador_nombre ($equipo_nombre): {$det['cumplidos']}/{$det['total']} fechas";
                            if ($det['finalizada']) {
                                $log_msg .= " [FINALIZADA]";
                            }
                            $log_msg .= "; ";
                        }
                        logActivity($log_msg);
                    }
                }
            }

            $db->commit();
            $message = $action == 'cargar_resultado' 
                ? 'Resultado y sanciones guardadas correctamente' 
                : 'Resultado editado y sanciones actualizadas';
            if ($sanciones_actualizadas > 0) {
                $message .= ". ✅ Se actualizaron automáticamente $sanciones_actualizadas sanción(es).";
                $finalizadas = array_filter($detalle_sanciones, fn($d) => $d['finalizada'] ?? false);
                if (!empty($finalizadas)) {
                    $nombres = array_map(function($d) {
                        return $d['jugador'] ?? ($d['nombre'] ?? 'Desconocido');
                    }, $finalizadas);
                    $message .= " 🎉 Sanción completada: " . implode(', ', $nombres);
                }
            }
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Error: ' . $e->getMessage();
            error_log("Error en control_fechas.php: " . $e->getMessage());
        }
    }
}

// === CARGAR DATOS PARA FILTROS ===
try {
    $stmt = $db->query("SELECT id, nombre FROM campeonatos WHERE activo = 1 ORDER BY nombre");
    $campeonatos = $stmt->fetchAll();
} catch (PDOException $e) {
    $campeonatos = [];
    $error = 'Error al cargar campeonatos: ' . $e->getMessage();
}

$campeonato_id = $_GET['campeonato'] ?? null;
$categoria_id = $_GET['categoria'] ?? null;
$fecha_id = $_GET['fecha'] ?? null;
$categorias = [];
if ($campeonato_id) {
    try {
        $stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1");
        $stmt->execute([$campeonato_id]);
        $categorias = $stmt->fetchAll();
    } catch (PDOException $e) {
        $categorias = [];
        if (empty($error)) $error = 'Error al cargar categorías: ' . $e->getMessage();
    }
}

// Detectar si la categoría tiene formato de zonas
$es_torneo_zonas = false;
$formato_zonas = null;
$zonas = [];
$tiene_eliminatorias = false;
$fase_actual = null;
$fases = [];

if ($categoria_id) {
    try {
        $stmt = $db->prepare("
            SELECT cf.* 
            FROM campeonatos_formato cf
            WHERE cf.categoria_id = ? AND cf.activo = 1
            LIMIT 1
        ");
        $stmt->execute([$categoria_id]);
        $formato_zonas = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($formato_zonas) {
            $es_torneo_zonas = true;
            
            $stmt = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
            $stmt->execute([$formato_zonas['id']]);
            $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stmt = $db->prepare("
                SELECT * 
                FROM fases_eliminatorias 
                WHERE formato_id = ? AND activa = 1
                ORDER BY orden
            ");
            $stmt->execute([$formato_zonas['id']]);
            $fases = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tiene_eliminatorias = !empty($fases);
            
            if ($tiene_eliminatorias) {
                $stmt = $db->prepare("
                    SELECT fe.nombre, fe.id, fe.orden
                    FROM fases_eliminatorias fe
                    WHERE fe.formato_id = ? AND fe.activa = 1
                    AND EXISTS (SELECT 1 FROM partidos p WHERE p.fase_eliminatoria_id = fe.id)
                    ORDER BY fe.orden DESC
                    LIMIT 1
                ");
                $stmt->execute([$formato_zonas['id']]);
                $fase_actual = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        }
    } catch (PDOException $e) {
        if (empty($error)) $error = 'Error al cargar formato de zonas: ' . $e->getMessage();
    }
}

$fechas_categoria = [];
$jornada_seleccionada = $_GET['jornada'] ?? null;
$fase_seleccionada = $_GET['fase'] ?? null;

if ($categoria_id && $es_torneo_zonas && !$fase_seleccionada) {
    try {
        $stmt = $db->prepare("
            SELECT DISTINCT p.jornada_zona as jornada
            FROM partidos p
            JOIN zonas z ON p.zona_id = z.id
            JOIN campeonatos_formato cf ON z.formato_id = cf.id
            WHERE cf.categoria_id = ? AND p.tipo_torneo = 'zona' AND p.jornada_zona IS NOT NULL
            ORDER BY p.jornada_zona ASC
        ");
        $stmt->execute([$categoria_id]);
        $jornadas_disponibles = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($jornadas_disponibles as $jornada) {
            $fechas_categoria[] = [
                'id' => 'jornada_' . $jornada,
                'numero_fecha' => $jornada,
                'fecha_programada' => null,
                'jornada' => $jornada
            ];
        }
    } catch (PDOException $e) {
        $fechas_categoria = [];
        if (empty($error)) $error = 'Error al cargar jornadas: ' . $e->getMessage();
    }
} elseif ($categoria_id && !$es_torneo_zonas) {
    try {
        $stmt = $db->prepare("SELECT id, numero_fecha, fecha_programada FROM fechas WHERE categoria_id = ? AND tipo_fecha != 'zona' ORDER BY numero_fecha");
        $stmt->execute([$categoria_id]);
        $fechas_categoria = $stmt->fetchAll();
    } catch (PDOException $e) {
        $fechas_categoria = [];
        if (empty($error)) $error = 'Error al cargar fechas: ' . $e->getMessage();
    }
}

// Cargar partidos
$partidos = [];
$partidos_por_zona = [];
$partidos_eliminatorias = [];

if ($fase_seleccionada && $es_torneo_zonas) {
    try {
        $stmt = $db->prepare("
            SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante,
                   can.nombre as cancha, el.color_camiseta as color_local, ev.color_camiseta as color_visitante,
                   el.id as equipo_local_id, ev.id as equipo_visitante_id,
                   fe.nombre as fase_nombre
            FROM partidos p
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            LEFT JOIN canchas can ON p.cancha_id = can.id
            JOIN fases_eliminatorias fe ON p.fase_eliminatoria_id = fe.id
            WHERE p.fase_eliminatoria_id = ?
            ORDER BY p.fecha_partido ASC, p.hora_partido ASC
        ");
        $stmt->execute([$fase_seleccionada]);
        $partidos_eliminatorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        if (empty($error)) $error = 'Error al cargar partidos de fase eliminatoria: ' . $e->getMessage();
    }
} elseif ($es_torneo_zonas && $categoria_id && !$fase_seleccionada) {
    try {
        if ($jornada_seleccionada) {
            foreach ($zonas as $zona) {
                $stmt = $db->prepare("
                    SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante,
                           can.nombre as cancha, el.color_camiseta as color_local, ev.color_camiseta as color_visitante,
                           el.id as equipo_local_id, ev.id as equipo_visitante_id,
                           z.nombre as zona_nombre, z.id as zona_id
                    FROM partidos p
                    JOIN equipos el ON p.equipo_local_id = el.id
                    JOIN equipos ev ON p.equipo_visitante_id = ev.id
                    JOIN zonas z ON p.zona_id = z.id
                    LEFT JOIN canchas can ON p.cancha_id = can.id
                    WHERE p.zona_id = ? AND p.tipo_torneo = 'zona' AND p.jornada_zona = ?
                    ORDER BY p.fecha_partido ASC, p.hora_partido ASC
                ");
                $stmt->execute([$zona['id'], $jornada_seleccionada]);
                $partidos_zona = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($partidos_zona)) {
                    $partidos_por_zona[] = [
                        'zona' => $zona,
                        'partidos' => $partidos_zona
                    ];
                }
            }
        } else {
            foreach ($zonas as $zona) {
                $stmt = $db->prepare("
                    SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante,
                           can.nombre as cancha, el.color_camiseta as color_local, ev.color_camiseta as color_visitante,
                           el.id as equipo_local_id, ev.id as equipo_visitante_id,
                           z.nombre as zona_nombre
                    FROM partidos p
                    JOIN equipos el ON p.equipo_local_id = el.id
                    JOIN equipos ev ON p.equipo_visitante_id = ev.id
                    JOIN zonas z ON p.zona_id = z.id
                    LEFT JOIN canchas can ON p.cancha_id = can.id
                    WHERE p.zona_id = ? AND p.tipo_torneo = 'zona'
                    ORDER BY p.jornada_zona ASC, p.fecha_partido ASC, p.hora_partido ASC
                ");
                $stmt->execute([$zona['id']]);
                $partidos_zona = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $por_jornada = [];
                foreach ($partidos_zona as $p) {
                    $jornada = $p['jornada_zona'] ?? 1;
                    if (!isset($por_jornada[$jornada])) {
                        $por_jornada[$jornada] = [];
                    }
                    $por_jornada[$jornada][] = $p;
                }
                ksort($por_jornada);
                
                $partidos_por_zona[] = [
                    'zona' => $zona,
                    'partidos_por_jornada' => $por_jornada
                ];
            }
        }
    } catch (PDOException $e) {
        if (empty($error)) $error = 'Error al cargar partidos de zonas: ' . $e->getMessage();
    }
} elseif ($fecha_id) {
    try {
        $stmt = $db->prepare("
            SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante,
                   can.nombre as cancha, el.color_camiseta as color_local, ev.color_camiseta as color_visitante,
                   el.id as equipo_local_id, ev.id as equipo_visitante_id
            FROM partidos p
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            LEFT JOIN canchas can ON p.cancha_id = can.id
            WHERE p.fecha_id = ? AND (p.tipo_torneo = 'normal' OR p.tipo_torneo IS NULL)
            ORDER BY p.hora_partido ASC
        ");
        $stmt->execute([$fecha_id]);
        $partidos = $stmt->fetchAll();
    } catch (PDOException $e) {
        $partidos = [];
        if (empty($error)) $error = 'Error al cargar partidos: ' . $e->getMessage();
    }
}

// Función auxiliar para renderizar una tarjeta de partido
function renderPartidoCard($p) {
    $tiene_cancha_y_horario = !empty($p['cancha']) && !empty($p['hora_partido']);
    $clase_card = 'partido-programado';
    if (!$tiene_cancha_y_horario) $clase_card = 'partido-sin-datos';
    elseif ($p['estado'] == 'finalizado') $clase_card = 'partido-finalizado';
    elseif ($p['estado'] == 'en_curso') $clase_card = 'partido-en-juego';
    
    $es_fase_eliminatoria = !empty($p['fase_eliminatoria_id']);
    $goles_local = $p['goles_local'] ?? 0;
    $goles_visitante = $p['goles_visitante'] ?? 0;
    $hubo_empate = ($goles_local == $goles_visitante);
    $tiene_penales = !empty($p['penales_local']) || !empty($p['penales_visitante']);
    ?>
    <div class="col-md-6 mb-3">
        <div class="card partido-card <?= $clase_card ?>">
            <div class="card-header">
                <?php if($p['estado'] == 'finalizado'): ?>
                    <div class="resultado-final">
                        <strong style="color:<?= $p['color_local'] ?>"><?= htmlspecialchars($p['equipo_local']) ?> <?= $p['goles_local'] ?></strong>
                        <span class="mx-3">VS</span>
                        <strong style="color:<?= $p['color_visitante'] ?>"><?= htmlspecialchars($p['equipo_visitante']) ?> <?= $p['goles_visitante'] ?></strong>
                        <?php if($es_fase_eliminatoria && $hubo_empate && $tiene_penales): ?>
                            <div class="penales-resultado mt-2">
                                <small>Penales: <?= $p['penales_local'] ?> - <?= $p['penales_visitante'] ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong style="color:<?= $p['color_local'] ?>"><?= htmlspecialchars($p['equipo_local']) ?></strong> vs 
                            <strong style="color:<?= $p['color_visitante'] ?>"><?= htmlspecialchars($p['equipo_visitante']) ?></strong>
                        </div>
                        <span class="badge bg-<?= $p['estado']=='finalizado'?'success':($p['estado']=='en_curso'?'danger':'primary') ?>">
                            <?= ucfirst(str_replace('_', ' ', $p['estado'])) ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1">
                            <i class="fas fa-calendar"></i> 
                            <strong>Fecha:</strong> <?= $p['fecha_partido'] ? date('d/m/Y', strtotime($p['fecha_partido'])) : 'Sin fecha' ?>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-map-marker-alt"></i> 
                            <strong>Cancha:</strong> <?= $p['cancha'] ?: '<span class="text-danger">Sin asignar</span>' ?>
                        </p>
                        <p class="mb-1">
                            <i class="fas fa-clock"></i> 
                            <strong>Hora:</strong> <?= $p['hora_partido'] ? date('H:i', strtotime($p['hora_partido'])) : '<span class="text-danger">Sin horario</span>' ?>
                        </p>
                    </div>
                    <div class="col-md-6 text-end">
                        <?php if(!$tiene_cancha_y_horario): ?>
                            <button class="btn btn-sm btn-disabled-info" disabled>
                                <i class="fas fa-exclamation-triangle"></i> Sin Datos
                            </button>
                        <?php elseif($p['estado'] == 'finalizado'): ?>
                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalResultado" 
                                onclick="editarPartido(<?= $p['id'] ?>, <?= $p['equipo_local_id'] ?>, <?= $p['equipo_visitante_id'] ?>, '<?= addslashes($p['equipo_local']) ?>', '<?= addslashes($p['equipo_visitante']) ?>', <?= $p['goles