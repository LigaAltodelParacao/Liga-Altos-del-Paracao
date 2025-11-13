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
    
    // Cargar funciones auxiliares
    $funciones_torneo_path = __DIR__ . '/funciones_torneos_zonas.php';
    if (!file_exists($funciones_torneo_path)) {
        throw new Exception('No se encontró funciones_torneos_zonas.php en: ' . $funciones_torneo_path);
    }
    require_once $funciones_torneo_path;

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
            $stmt = $db->prepare("
                SELECT 
                    p.equipo_local_id,
                    p.equipo_visitante_id,
                    p.zona_id,
                    p.tipo_torneo,
                    p.fase_eliminatoria_id,
                    z.formato_id AS formato_zona_id,
                    fe.formato_id AS formato_eliminatoria_id
                FROM partidos p
                LEFT JOIN zonas z ON p.zona_id = z.id
                LEFT JOIN fases_eliminatorias fe ON p.fase_eliminatoria_id = fe.id
                WHERE p.id = ?
            ");
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

            // Registrar tarjetas
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

            // === CORREGIR DOBLE AMARILLA (convertir a roja) ===
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

            // === PROCESAR TODAS LAS SANCIONES AUTOMÁTICAMENTE ===
            $resultado_sanciones_creacion = procesarEventosYCrearSanciones($partido_id, $db);
            if (!$resultado_sanciones_creacion['success']) {
                throw new Exception($resultado_sanciones_creacion['message'] ?? 'Error al crear sanciones');
            }

            // === CUMPLIR SANCIONES AUTOMÁTICAMENTE ===
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

            $mensaje_base = $action == 'cargar_resultado' 
                ? 'Resultado y sanciones guardadas correctamente' 
                : 'Resultado editado y sanciones actualizadas';

            if ($sanciones_actualizadas > 0) {
                $mensaje_base .= ". ✅ Se actualizaron automáticamente $sanciones_actualizadas sanción(es).";
                $finalizadas = array_filter($detalle_sanciones, fn($d) => $d['finalizada'] ?? false);
                if (!empty($finalizadas)) {
                    $nombres = array_map(function($d) {
                        return $d['jugador'] ?? ($d['nombre'] ?? 'Desconocido');
                    }, $finalizadas);
                    $mensaje_base .= " 🎉 Sanción completada: " . implode(', ', $nombres);
                }
            }

            $resultado_auto = procesarPostFinalizacionPartido($partido_id, $db);
            if (!empty($resultado_auto['messages'])) {
                $mensaje_base .= ' ' . implode(' ', $resultado_auto['messages']);
            }

            $message = $mensaje_base;
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
$fase_id = $_GET['fase_id'] ?? null;
$categorias = [];
if ($campeonato_id) {
    try {
        // Primero intentamos cargar categorías declaradas directamente para el campeonato
        $stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
        $stmt->execute([$campeonato_id]);
        $categorias = $stmt->fetchAll();

        // Si no hay categorías directas, derivar categorías desde formatos de zonas activos
        if (empty($categorias)) {
            $stmt = $db->prepare("
                SELECT DISTINCT cat.id, cat.nombre
                FROM campeonatos_formato cf
                JOIN categorias cat ON cat.id = cf.categoria_id
                WHERE cf.campeonato_id = ?
                  AND cf.activo = 1
                  AND cat.activa = 1
                ORDER BY cat.nombre
            ");
            $stmt->execute([$campeonato_id]);
            $categorias = $stmt->fetchAll();
        }
    } catch (PDOException $e) {
        $categorias = [];
        if (empty($error)) $error = 'Error al cargar categorías: ' . $e->getMessage();
    }
}

// Detectar si la categoría tiene formato de zonas
$es_torneo_zonas = false;
$formato_zonas = null;
$zonas = [];
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

            // Cargar fases eliminatorias y partidos si existen
            $fases_eliminatorias_cf = [];
            $partidos_eliminatorias_cf = [];
            $fs = $db->prepare("SELECT * FROM fases_eliminatorias WHERE formato_id = ? ORDER BY orden");
            $fs->execute([$formato_zonas['id']]);
            $fases_eliminatorias_cf = $fs->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($fases_eliminatorias_cf)) {
                foreach ($fases_eliminatorias_cf as $fase) {
                    $ps = $db->prepare("
                        SELECT 
                            p.*,
                            el.nombre AS equipo_local, ev.nombre AS equipo_visitante,
                            can.nombre AS cancha,
                            el.id AS equipo_local_id, ev.id AS equipo_visitante_id
                        FROM partidos p
                        LEFT JOIN equipos el ON p.equipo_local_id = el.id
                        LEFT JOIN equipos ev ON p.equipo_visitante_id = ev.id
                        LEFT JOIN canchas can ON p.cancha_id = can.id
                        WHERE p.fase_eliminatoria_id = ? AND p.tipo_torneo = 'eliminatoria'
                        ORDER BY p.numero_llave
                    ");
                    $ps->execute([$fase['id']]);
                    $partidos_eliminatorias_cf[$fase['id']] = $ps->fetchAll(PDO::FETCH_ASSOC);
                }
            }
        }
    } catch (PDOException $e) {
        if (empty($error)) $error = 'Error al cargar formato de zonas: ' . $e->getMessage();
    }
}

$fechas_categoria = [];
$jornada_seleccionada = $_GET['jornada'] ?? null;

// Si es torneo de zonas, cargar jornadas únicas de todos los partidos
if ($categoria_id && $es_torneo_zonas) {
    try {
        // Obtener todas las jornadas únicas de todos los partidos de zonas de esta categoría
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
        
        // Crear array de fechas basado en jornadas
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
    // Para torneos normales, cargar fechas normales
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

if ($es_torneo_zonas && $categoria_id) {
    try {
        // Si se seleccionó una jornada, filtrar por jornada
        if ($jornada_seleccionada) {
            // Cargar partidos de todas las zonas para la jornada seleccionada
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
            // Si no hay jornada seleccionada, cargar todos los partidos agrupados por jornada
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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Fechas - Sistema de Campeonatos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .partido-card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        .partido-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        .partido-finalizado {
            border-left: 5px solid #28a745;
        }
        .partido-en-juego {
            border-left: 5px solid #dc3545;
        }
        .partido-programado {
            border-left: 5px solid #007bff;
        }
        .partido-sin-datos {
            border-left: 5px solid #6c757d;
            opacity: 0.7;
        }
        .resultado-final {
            font-size: 1.8rem;
            font-weight: bold;
            text-align: center;
            padding: 15px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: 8px;
            margin: 10px 0;
        }
        .eventos-equipo {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
        }
        .evento-gol {
            color: #28a745;
            margin-right: 10px;
        }
        .evento-amarilla {
            color: #ffc107;
            margin-right: 10px;
        }
        .evento-roja {
            color: #dc3545;
            margin-right: 10px;
        }
        .btn-disabled-info {
            background-color: #6c757d;
            border-color: #6c757d;
            cursor: not-allowed;
        }
        .is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff5f5 !important;
        }
        .alert-sistema {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
        }
        .jugador-item {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        .jugador-item:last-child {
            border-bottom: none;
        }
        .jugador-numero-input {
            width: 70px;
            text-align: center;
        }
        .grilla-jugadores {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            background-color: white;
        }
        .tab-eventos {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-futbol"></i> Fútbol Manager - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>                
                <a class="nav-link" href="<?php echo SITE_URL; ?>logout.php">Salir</a>
            </div>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 p-0">
                <?php 
                $sidebar_path = __DIR__ . '/include/sidebar.php';
                if (file_exists($sidebar_path)) {
                    include $sidebar_path;
                } else {
                    echo '<div class="alert alert-danger m-2">Error: No se encontró sidebar.php</div>';
                }
                ?>
            </div>
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-futbol"></i> Control de Fechas</h2>                    
                </div>
                <?php if($message): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                <?php if($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                <?php if (function_exists('cumplirSancionesAutomaticas')): ?>
                <div class="alert alert-sistema alert-dismissible fade show">
                    <i class="fas fa-robot"></i> <strong>Sistema Automático Activo:</strong> Las sanciones se descuentan automáticamente cuando finalizas un partido.
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>
                <div class="alert alert-info alert-dismissible fade show">
                    <i class="fas fa-info-circle"></i> <strong>Instrucciones:</strong> 
                    <ul class="mb-0 mt-2">
                        <li><strong>Sin números:</strong> Si NO cargas ningún número, se considera que TODOS los jugadores del equipo jugaron.</li>
                        <li><strong>Con números:</strong> Si cargas al menos un número, solo juegan los que tienen número asignado.</li>
                        <li><strong>Eventos:</strong> Puedes buscar por número o seleccionar directamente del listado.</li>
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <!-- Filtros -->
                <form method="GET" class="row g-2 mb-4">
                    <div class="col-md-3">
                        <select name="campeonato" class="form-select" onchange="this.form.submit()">
                            <option value="">Seleccionar Campeonato</option>
                            <?php foreach($campeonatos as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $campeonato_id==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if($campeonato_id): ?>
                    <div class="col-md-3">
                        <select name="categoria" class="form-select" onchange="this.form.submit()">
                            <option value="">Seleccionar Categoría</option>
                            <?php foreach($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $categoria_id==$cat['id']?'selected':'' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="campeonato" value="<?= $campeonato_id ?>">
                    </div>
                    <?php endif; ?>
                    <?php if($categoria_id): ?>
                    <div class="col-md-3">
                        <?php if($es_torneo_zonas): ?>
                            <select name="jornada" class="form-select" onchange="this.form.submit()">
                                <option value="">Seleccionar Jornada</option>
                                <?php foreach($fechas_categoria as $f): ?>
                                    <option value="<?= $f['jornada'] ?>" <?= $jornada_seleccionada==$f['jornada']?'selected':'' ?>>
                                        Jornada <?= $f['numero_fecha'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <select name="fecha" class="form-select" onchange="this.form.submit()">
                                <option value="">Seleccionar Fecha</option>
                                <?php foreach($fechas_categoria as $f): ?>
                                    <option value="<?= $f['id'] ?>" <?= $fecha_id==$f['id']?'selected':'' ?>>
                                        Fecha <?= $f['numero_fecha'] ?> (<?= date('d/m/Y', strtotime($f['fecha_programada'])) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                        <input type="hidden" name="campeonato" value="<?= $campeonato_id ?>">
                        <input type="hidden" name="categoria" value="<?= $categoria_id ?>">
                    </div>
                    <?php if($es_torneo_zonas && !empty($fases_eliminatorias_cf)): ?>
                    <div class="col-md-3">
                        <select name="fase_id" class="form-select" onchange="this.form.submit()">
                            <option value="">Seleccionar Fase Eliminatoria</option>
                            <?php 
                            $nombre_fase_sel = [
                                'dieciseisavos' => 'Dieciseisavos de Final',
                                'octavos' => 'Octavos de Final',
                                'cuartos' => 'Cuartos de Final',
                                'semifinal' => 'Semifinales',
                                'final' => 'Final',
                                'tercer_puesto' => 'Tercer Puesto'
                            ];
                            foreach($fases_eliminatorias_cf as $fase): ?>
                                <option value="<?= $fase['id'] ?>" <?= $fase_id==$fase['id']?'selected':'' ?>>
                                    <?= htmlspecialchars($nombre_fase_sel[$fase['nombre']] ?? $fase['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </form>
                
                <?php if($es_torneo_zonas && $categoria_id): ?>
                    <!-- Mostrar partidos de zonas -->
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> <strong>Torneo por Zonas:</strong> 
                        <?php if($fase_id): ?>
                            Mostrando partidos de la fase eliminatoria seleccionada.
                        <?php elseif($jornada_seleccionada): ?>
                            Mostrando partidos de la Jornada <?= $jornada_seleccionada ?> agrupados por zona.
                        <?php else: ?>
                            Selecciona una jornada o una fase eliminatoria para ver los partidos.
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($fase_id): ?>
                        <?php 
                        $fase_sel = null;
                        foreach (($fases_eliminatorias_cf ?? []) as $f) { if ($f['id'] == $fase_id) { $fase_sel = $f; break; } }
                        $lista = $fase_sel ? ($partidos_eliminatorias_cf[$fase_sel['id']] ?? []) : [];
                        $nombre_fase = [
                            'dieciseisavos' => 'Dieciseisavos de Final',
                            'octavos' => 'Octavos de Final',
                            'cuartos' => 'Cuartos de Final',
                            'semifinal' => 'Semifinales',
                            'final' => 'Final',
                            'tercer_puesto' => 'Tercer Puesto'
                        ];
                        ?>
                        <?php if (!$fase_sel): ?>
                            <div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Fase seleccionada no válida.</div>
                        <?php else: ?>
                            <h4 class="mb-4">
                                <span class="badge bg-warning text-dark"><?= htmlspecialchars($nombre_fase[$fase_sel['nombre']] ?? $fase_sel['nombre']) ?></span>
                            </h4>
                            <?php if (empty($lista)): ?>
                                <div class="alert alert-info"><i class="fas fa-info-circle"></i> No hay partidos generados para esta fase.</div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($lista as $p): 
                                        $tiene_cancha_y_horario = !empty($p['cancha']) && !empty($p['hora_partido']);
                                        $clase_card = 'partido-programado';
                                        if (!$tiene_cancha_y_horario) $clase_card = 'partido-sin-datos';
                                        elseif ($p['estado'] == 'finalizado') $clase_card = 'partido-finalizado';
                                        elseif ($p['estado'] == 'en_curso') $clase_card = 'partido-en-juego';
                                    ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card partido-card <?= $clase_card ?>">
                                                <div class="card-header d-flex justify-content-between">
                                                    <div>
                                                        <strong><?= htmlspecialchars($p['equipo_local'] ?? 'Por definir') ?></strong> vs 
                                                        <strong><?= htmlspecialchars($p['equipo_visitante'] ?? 'Por definir') ?></strong>
                                                    </div>
                                                    <span class="badge bg-<?= $p['estado']=='finalizado'?'success':($p['estado']=='en_curso'?'danger':'primary') ?>">
                                                        <?= ucfirst(str_replace('_', ' ', $p['estado'])) ?>
                                                    </span>
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
                                                                    onclick="editarPartido(<?= $p['id'] ?>, <?= $p['equipo_local_id'] ?? 'null' ?>, <?= $p['equipo_visitante_id'] ?? 'null' ?>, '<?= addslashes($p['equipo_local'] ?? 'Por definir') ?>', '<?= addslashes($p['equipo_visitante'] ?? 'Por definir') ?>', <?= (int)($p['goles_local'] ?? 0) ?>, <?= (int)($p['goles_visitante'] ?? 0) ?>, '<?= addslashes($p['observaciones'] ?? '') ?>')">
                                                                    <i class="fas fa-edit"></i> Editar
                                                                </button>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalResultado" 
                                                                    onclick="cargarPartido(<?= $p['id'] ?>, <?= $p['equipo_local_id'] ?? 'null' ?>, <?= $p['equipo_visitante_id'] ?? 'null' ?>, '<?= addslashes($p['equipo_local'] ?? 'Por definir') ?>', '<?= addslashes($p['equipo_visitante'] ?? 'Por definir') ?>')">
                                                                    <i class="fas fa-plus"></i> Cargar Resultado
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php elseif (empty($partidos_por_zona)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> <strong>No hay partidos generados.</strong>
                            <p class="mb-0 mt-2">El fixture aún no ha sido generado para este torneo. 
                            <?php if ($formato_zonas): ?>
                                <a href="crear_torneo_zonas.php?formato_id=<?= $formato_zonas['id'] ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-magic"></i> Generar Fixture
                                </a>
                            <?php endif; ?>
                            </p>
                        </div>
                    <?php elseif($jornada_seleccionada): ?>
                        <!-- Mostrar partidos de la jornada seleccionada agrupados por zona -->
                        <h4 class="mb-4">
                            <span class="badge bg-primary">Jornada <?= $jornada_seleccionada ?></span>
                        </h4>
                        <?php foreach ($partidos_por_zona as $data_zona): ?>
                            <div class="card mb-4">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-layer-group"></i> <?= htmlspecialchars($data_zona['zona']['nombre']) ?>
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($data_zona['partidos'])): ?>
                                        <p class="text-muted">No hay partidos para esta zona en la jornada <?= $jornada_seleccionada ?>.</p>
                                    <?php else: ?>
                                        <div class="row">
                                            <?php foreach ($data_zona['partidos'] as $p): 
                                                $tiene_cancha_y_horario = !empty($p['cancha']) && !empty($p['hora_partido']);
                                                $clase_card = 'partido-programado';
                                                if (!$tiene_cancha_y_horario) $clase_card = 'partido-sin-datos';
                                                elseif ($p['estado'] == 'finalizado') $clase_card = 'partido-finalizado';
                                                elseif ($p['estado'] == 'en_curso') $clase_card = 'partido-en-juego';
                                            ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="card partido-card <?= $clase_card ?>">
                                                        <div class="card-header">
                                                            <?php if($p['estado'] == 'finalizado'): ?>
                                                                <div class="resultado-final">
                                                                    <strong style="color:<?= $p['color_local'] ?>"><?= htmlspecialchars($p['equipo_local']) ?> <?= $p['goles_local'] ?></strong>
                                                                    <span class="mx-3">VS</span>
                                                                    <strong style="color:<?= $p['color_visitante'] ?>"><?= htmlspecialchars($p['equipo_visitante']) ?> <?= $p['goles_visitante'] ?></strong>
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
                                                                            onclick="editarPartido(<?= $p['id'] ?>, <?= $p['equipo_local_id'] ?>, <?= $p['equipo_visitante_id'] ?>, '<?= addslashes($p['equipo_local']) ?>', '<?= addslashes($p['equipo_visitante']) ?>', <?= $p['goles_local'] ?>, <?= $p['goles_visitante'] ?>, '<?= addslashes($p['observaciones'] ?? '') ?>')">
                                                                            <i class="fas fa-edit"></i> Editar
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalResultado" 
                                                                            onclick="cargarPartido(<?= $p['id'] ?>, <?= $p['equipo_local_id'] ?>, <?= $p['equipo_visitante_id'] ?>, '<?= addslashes($p['equipo_local']) ?>', '<?= addslashes($p['equipo_visitante']) ?>')">
                                                                            <i class="fas fa-plus"></i> Cargar Resultado
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Si no hay jornada seleccionada, mostrar todas las zonas con tabs -->
                        <ul class="nav nav-tabs mb-4" id="zonasTab" role="tablist">
                            <?php 
                            $partidos_por_zona_indexed = array_values($partidos_por_zona);
                            foreach ($partidos_por_zona_indexed as $index => $data_zona): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" 
                                            id="zona-<?= $data_zona['zona']['id'] ?>-tab" 
                                            data-bs-toggle="tab" 
                                            data-bs-target="#zona-<?= $data_zona['zona']['id'] ?>" 
                                            type="button">
                                        <?= htmlspecialchars($data_zona['zona']['nombre']) ?>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <div class="tab-content" id="zonasTabContent">
                            <?php 
                            $partidos_por_zona_indexed = array_values($partidos_por_zona);
                            foreach ($partidos_por_zona_indexed as $index => $data_zona): ?>
                                <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" 
                                     id="zona-<?= $data_zona['zona']['id'] ?>" 
                                     role="tabpanel">
                                    
                                    <?php if (empty($data_zona['partidos_por_jornada'])): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> No hay partidos generados para esta zona. 
                                            <?php if ($formato_zonas): ?>
                                                <a href="crear_torneo_zonas.php?formato_id=<?= $formato_zonas['id'] ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-magic"></i> Generar Fixture
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                    <?php foreach ($data_zona['partidos_por_jornada'] as $jornada => $partidos_jornada): ?>
                                        <h5 class="mt-4 mb-3">
                                            <span class="badge bg-primary">Jornada <?= $jornada ?></span>
                                        </h5>
                                        <div class="row">
                                            <?php foreach ($partidos_jornada as $p): 
                                                $tiene_cancha_y_horario = !empty($p['cancha']) && !empty($p['hora_partido']);
                                                $clase_card = 'partido-programado';
                                                if (!$tiene_cancha_y_horario) $clase_card = 'partido-sin-datos';
                                                elseif ($p['estado'] == 'finalizado') $clase_card = 'partido-finalizado';
                                                elseif ($p['estado'] == 'en_curso') $clase_card = 'partido-en-juego';
                                            ?>
                                                <div class="col-md-6">
                                                    <div class="card partido-card <?= $clase_card ?>">
                                                        <div class="card-header">
                                                            <?php if($p['estado'] == 'finalizado'): ?>
                                                                <div class="resultado-final">
                                                                    <strong style="color:<?= $p['color_local'] ?>"><?= htmlspecialchars($p['equipo_local']) ?> <?= $p['goles_local'] ?></strong>
                                                                    <span class="mx-3">VS</span>
                                                                    <strong style="color:<?= $p['color_visitante'] ?>"><?= htmlspecialchars($p['equipo_visitante']) ?> <?= $p['goles_visitante'] ?></strong>
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
                                                                            onclick="editarPartido(<?= $p['id'] ?>, <?= $p['equipo_local_id'] ?>, <?= $p['equipo_visitante_id'] ?>, '<?= addslashes($p['equipo_local']) ?>', '<?= addslashes($p['equipo_visitante']) ?>', <?= $p['goles_local'] ?>, <?= $p['goles_visitante'] ?>, '<?= addslashes($p['observaciones'] ?? '') ?>')">
                                                                            <i class="fas fa-edit"></i> Editar
                                                                        </button>
                                                                    <?php else: ?>
                                                                        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalResultado" 
                                                                            onclick="cargarPartido(<?= $p['id'] ?>, <?= $p['equipo_local_id'] ?>, <?= $p['equipo_visitante_id'] ?>, '<?= addslashes($p['equipo_local']) ?>', '<?= addslashes($p['equipo_visitante']) ?>')">
                                                                            <i class="fas fa-plus"></i> Cargar Resultado
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php if (!empty($fases_eliminatorias_cf) && !$fase_id): ?>
                    <h3 class="mb-3 mt-4"><i class="fas fa-trophy"></i> Eliminatorias</h3>
                    <?php
                    $nombre_fase = [
                        'dieciseisavos' => 'Dieciseisavos de Final',
                        'octavos' => 'Octavos de Final',
                        'cuartos' => 'Cuartos de Final',
                        'semifinal' => 'Semifinales',
                        'final' => 'Final',
                        'tercer_puesto' => 'Tercer Puesto'
                    ];
                    ?>
                    <?php foreach ($fases_eliminatorias_cf as $fase): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0"><?= htmlspecialchars($nombre_fase[$fase['nombre']] ?? $fase['nombre']) ?></h5>
                            </div>
                            <div class="card-body">
                                <?php $lista = $partidos_eliminatorias_cf[$fase['id']] ?? []; ?>
                                <?php if (empty($lista)): ?>
                                    <div class="text-muted"><i class="fas fa-info-circle"></i> Aún no hay partidos.</div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($lista as $p): 
                                            $tiene_cancha_y_horario = !empty($p['cancha']) && !empty($p['hora_partido']);
                                            $clase_card = 'partido-programado';
                                            if (!$tiene_cancha_y_horario) $clase_card = 'partido-sin-datos';
                                            elseif ($p['estado'] == 'finalizado') $clase_card = 'partido-finalizado';
                                            elseif ($p['estado'] == 'en_curso') $clase_card = 'partido-en-juego';
                                        ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="card partido-card <?= $clase_card ?>">
                                                    <div class="card-header d-flex justify-content-between">
                                                        <div>
                                                            <strong><?= htmlspecialchars($p['equipo_local'] ?? 'Por definir') ?></strong> vs 
                                                            <strong><?= htmlspecialchars($p['equipo_visitante'] ?? 'Por definir') ?></strong>
                                                        </div>
                                                        <span class="badge bg-<?= $p['estado']=='finalizado'?'success':($p['estado']=='en_curso'?'danger':'primary') ?>">
                                                            <?= ucfirst(str_replace('_', ' ', $p['estado'])) ?>
                                                        </span>
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
                                                                        onclick="editarPartido(<?= $p['id'] ?>, <?= $p['equipo_local_id'] ?? 'null' ?>, <?= $p['equipo_visitante_id'] ?? 'null' ?>, '<?= addslashes($p['equipo_local'] ?? 'Por definir') ?>', '<?= addslashes($p['equipo_visitante'] ?? 'Por definir') ?>', <?= (int)($p['goles_local'] ?? 0) ?>, <?= (int)($p['goles_visitante'] ?? 0) ?>, '<?= addslashes($p['observaciones'] ?? '') ?>')">
                                                                        <i class="fas fa-edit"></i> Editar
                                                                    </button>
                                                                <?php else: ?>
                                                                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalResultado" 
                                                                        onclick="cargarPartido(<?= $p['id'] ?>, <?= $p['equipo_local_id'] ?? 'null' ?>, <?= $p['equipo_visitante_id'] ?? 'null' ?>, '<?= addslashes($p['equipo_local'] ?? 'Por definir') ?>', '<?= addslashes($p['equipo_visitante'] ?? 'Por definir') ?>')">
                                                                        <i class="fas fa-plus"></i> Cargar Resultado
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php elseif($partidos): ?>
                <div class="row">
                    <?php foreach($partidos as $p): 
                        $tiene_cancha_y_horario = !empty($p['cancha']) && !empty($p['hora_partido']);
                        $clase_card = 'partido-programado';
                        if (!$tiene_cancha_y_horario) $clase_card = 'partido-sin-datos';
                        elseif ($p['estado'] == 'finalizado') $clase_card = 'partido-finalizado';
                        elseif ($p['estado'] == 'en_curso') $clase_card = 'partido-en-juego';
                    ?>
                    <div class="col-md-6">
                        <div class="card partido-card <?= $clase_card ?>">
                            <div class="card-header">
                                <?php if($p['estado'] == 'finalizado'): ?>
                                    <div class="resultado-final">
                                        <strong style="color:<?= $p['color_local'] ?>"><?= htmlspecialchars($p['equipo_local']) ?> <?= $p['goles_local'] ?></strong>
                                        <span class="mx-3">VS</span>
                                        <strong style="color:<?= $p['color_visitante'] ?>"><?= htmlspecialchars($p['equipo_visitante']) ?> <?= $p['goles_visitante'] ?></strong>
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
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <strong>Cancha:</strong> <?= $p['cancha'] ?: '<span class="text-danger">Sin asignar</span>' ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-clock"></i> 
                                            <strong>Hora:</strong> <?= $p['hora_partido'] ? date('H:i', strtotime($p['hora_partido'])) : '<span class="text-danger">Sin horario</span>' ?>
                                        </p>
                                        <?php if(!empty($p['observaciones'])): ?>
                                        <p class="mb-1">
                                            <i class="fas fa-clipboard"></i> 
                                            <strong>Obs:</strong> <?= htmlspecialchars($p['observaciones']) ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        <?php if(!$tiene_cancha_y_horario): ?>
                                            <button class="btn btn-sm btn-disabled-info" disabled title="Faltan datos: cancha y/o horario">
                                                <i class="fas fa-exclamation-triangle"></i> Sin Datos
                                            </button>
                                            <small class="d-block text-muted mt-1">Asignar cancha y horario primero</small>
                                        <?php elseif($p['estado'] == 'en_curso'): ?>
                                            <button class="btn btn-sm btn-warning" disabled>
                                                <i class="fas fa-broadcast-tower"></i> En Curso
                                            </button>
                                            <small class="d-block text-muted mt-1">No se puede modificar</small>
                                        <?php elseif($p['estado'] == 'finalizado'): ?>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#modalResultado" 
                                                onclick="editarPartido(<?= $p['id'] ?>, <?= $p['equipo_local_id'] ?>, <?= $p['equipo_visitante_id'] ?>, '<?= addslashes($p['equipo_local']) ?>', '<?= addslashes($p['equipo_visitante']) ?>', <?= $p['goles_local'] ?>, <?= $p['goles_visitante'] ?>, '<?= addslashes($p['observaciones']) ?>')">
                                                <i class="fas fa-edit"></i> Editar Resultado
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalResultado" 
                                                onclick="cargarPartido(<?= $p['id'] ?>, <?= $p['equipo_local_id'] ?>, <?= $p['equipo_visitante_id'] ?>, '<?= addslashes($p['equipo_local']) ?>', '<?= addslashes($p['equipo_visitante']) ?>')">
                                                <i class="fas fa-plus"></i> Cargar Resultado
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php elseif($fecha_id): ?>
                <div class="text-center py-5">
                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                    <h4>No hay partidos para esta fecha</h4>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-filter fa-4x text-muted mb-3"></i>
                    <h4>Selecciona campeonato, categoría y fecha para ver los partidos</h4>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Resultado -->
    <div class="modal fade" id="modalResultado">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <form method="POST" id="formResultado">
                    <input type="hidden" name="action" value="cargar_resultado" id="modal_action">
                    <input type="hidden" name="partido_id" id="partido_id">
                    <input type="hidden" id="equipo_local_id">
                    <input type="hidden" id="equipo_visitante_id">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title" id="modal_title">Cargar Resultado</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <ul class="nav nav-tabs mb-4" id="partidoTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="jugadores-tab" data-bs-toggle="tab" data-bs-target="#jugadores" type="button">
                                    <i class="fas fa-users"></i> 1. Números de Jugadores (Opcional)
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="eventos-tab" data-bs-toggle="tab" data-bs-target="#eventos" type="button">
                                    <i class="fas fa-clipboard-list"></i> 2. Cargar Eventos
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content" id="partidoTabsContent">
                            <div class="tab-pane fade show active" id="jugadores" role="tabpanel">
                                <div class="alert alert-warning">
                                    <i class="fas fa-info-circle"></i> <strong>Importante:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Si <strong>NO</strong> cargas ningún número, se considera que <strong>TODOS</strong> los jugadores jugaron.</li>
                                        <li>Si cargas al menos un número, solo se registran los jugadores con número.</li>
                                    </ul>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <h5 id="nombre_local_jugadores" class="mb-0"></h5>
                                            </div>
                                            <div class="card-body p-2">
                                                <div class="grilla-jugadores" id="jugadoresLocalContainer">
                                                    <div class="text-center text-muted py-3">
                                                        <i class="fas fa-spinner fa-spin"></i> Cargando jugadores...
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-danger text-white">
                                                <h5 id="nombre_visitante_jugadores" class="mb-0"></h5>
                                            </div>
                                            <div class="card-body p-2">
                                                <div class="grilla-jugadores" id="jugadoresVisitanteContainer">
                                                    <div class="text-center text-muted py-3">
                                                        <i class="fas fa-spinner fa-spin"></i> Cargando jugadores...
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-center mt-3">
                                    <button type="button" class="btn btn-primary btn-lg" onclick="irAEventos()">
                                        <i class="fas fa-arrow-right"></i> Continuar a Eventos
                                    </button>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="eventos" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-primary text-white">
                                                <h5 id="nombre_local_eventos" class="mb-0"></h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Goles Local</label>
                                                    <input type="number" class="form-control form-control-lg text-center" 
                                                           name="goles_local" id="goles_local" value="0" readonly 
                                                           style="font-size: 1.5rem; font-weight: bold;">
                                                </div>
                                                <div class="tab-eventos">
                                                    <h6><i class="fas fa-futbol text-success"></i> Goles</h6>
                                                    <div id="golesLocalContainer"></div>
                                                    <button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="addGol('local')">
                                                        <i class="fas fa-plus"></i> Agregar Gol
                                                    </button>
                                                </div>
                                                <div class="tab-eventos mt-3">
                                                    <h6><i class="fas fa-square text-warning"></i> Tarjetas</h6>
                                                    <div id="tarjetasLocalContainer"></div>
                                                    <button type="button" class="btn btn-sm btn-outline-warning mt-2" onclick="addTarjeta('local')">
                                                        <i class="fas fa-plus"></i> Agregar Tarjeta
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card">
                                            <div class="card-header bg-danger text-white">
                                                <h5 id="nombre_visitante_eventos" class="mb-0"></h5>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label fw-bold">Goles Visitante</label>
                                                    <input type="number" class="form-control form-control-lg text-center" 
                                                           name="goles_visitante" id="goles_visitante" value="0" readonly
                                                           style="font-size: 1.5rem; font-weight: bold;">
                                                </div>
                                                <div class="tab-eventos">
                                                    <h6><i class="fas fa-futbol text-success"></i> Goles</h6>
                                                    <div id="golesVisitanteContainer"></div>
                                                    <button type="button" class="btn btn-sm btn-outline-success mt-2" onclick="addGol('visitante')">
                                                        <i class="fas fa-plus"></i> Agregar Gol
                                                    </button>
                                                </div>
                                                <div class="tab-eventos mt-3">
                                                    <h6><i class="fas fa-square text-warning"></i> Tarjetas</h6>
                                                    <div id="tarjetasVisitanteContainer"></div>
                                                    <button type="button" class="btn btn-sm btn-outline-warning mt-2" onclick="addTarjeta('visitante')">
                                                        <i class="fas fa-plus"></i> Agregar Tarjeta
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-body">
                                        <label class="form-label fw-bold">Observaciones del Partido</label>
                                        <textarea class="form-control" name="observaciones" id="observaciones" rows="3" 
                                                  placeholder="Observaciones del partido, incidencias, etc."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success btn-lg" id="btnGuardar">
                            <i class="fas fa-save"></i> Guardar Resultado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        let jugadoresLocal = [];
        let jugadoresVisitante = [];
        let numerosLocal = {};
        let numerosVisitante = {};
        
        function cargarPartido(id, equipoLocal, equipoVisitante, nombreLocal, nombreVisitante) {
            resetModal();
            document.getElementById('modal_action').value = 'cargar_resultado';
            document.getElementById('modal_title').textContent = 'Cargar Resultado';
            document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-save"></i> Guardar Resultado';
            fillModalData(id, equipoLocal, equipoVisitante, nombreLocal, nombreVisitante);
            cargarJugadoresEquipo(equipoLocal, 'local');
            cargarJugadoresEquipo(equipoVisitante, 'visitante');
        }
        
        function editarPartido(id, equipoLocal, equipoVisitante, nombreLocal, nombreVisitante, golesLocal, golesVisitante, observaciones) {
            resetModal();
            document.getElementById('modal_action').value = 'editar_resultado';
            document.getElementById('modal_title').textContent = 'Editar Resultado';
            document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-edit"></i> Actualizar Resultado';
            fillModalData(id, equipoLocal, equipoVisitante, nombreLocal, nombreVisitante);
            document.getElementById('goles_local').value = golesLocal;
            document.getElementById('goles_visitante').value = golesVisitante;
            document.getElementById('observaciones').value = observaciones;
            cargarJugadoresEquipo(equipoLocal, 'local', true);
            cargarJugadoresEquipo(equipoVisitante, 'visitante', true);
            loadExistingEvents(id);
        }
        
        function fillModalData(id, equipoLocal, equipoVisitante, nombreLocal, nombreVisitante) {
            document.getElementById('partido_id').value = id;
            document.getElementById('equipo_local_id').value = equipoLocal;
            document.getElementById('equipo_visitante_id').value = equipoVisitante;
            document.getElementById('nombre_local_jugadores').textContent = nombreLocal;
            document.getElementById('nombre_visitante_jugadores').textContent = nombreVisitante;
            document.getElementById('nombre_local_eventos').textContent = nombreLocal;
            document.getElementById('nombre_visitante_eventos').textContent = nombreVisitante;
        }
        
        function resetModal() {
            numerosLocal = {};
            numerosVisitante = {};
            document.getElementById('golesLocalContainer').innerHTML = '';
            document.getElementById('golesVisitanteContainer').innerHTML = '';
            document.getElementById('tarjetasLocalContainer').innerHTML = '';
            document.getElementById('tarjetasVisitanteContainer').innerHTML = '';
            document.getElementById('goles_local').value = 0;
            document.getElementById('goles_visitante').value = 0;
            document.getElementById('observaciones').value = '';
            const tab = new bootstrap.Tab(document.getElementById('jugadores-tab'));
            tab.show();
        }
        
        async function cargarJugadoresEquipo(equipoId, lado, editarPartido = false) {
            const container = document.getElementById('jugadores' + (lado === 'local' ? 'Local' : 'Visitante') + 'Container');
            try {
                const jugadores = await getJugadores(equipoId, editarPartido);
                if (lado === 'local') {
                    jugadoresLocal = jugadores;
                } else {
                    jugadoresVisitante = jugadores;
                }
                if (jugadores.length === 0) {
                    container.innerHTML = '<div class="text-center text-muted py-3">No hay jugadores registrados</div>';
                    return;
                }
                let html = '';
                jugadores.forEach((j) => {
                    html += `
                        <div class="jugador-item d-flex align-items-center">
                            <label class="flex-grow-1">
                                ${j.apellido_nombre}
                            </label>
                            <input type="number" class="form-control jugador-numero-input" 
                                   id="num_${lado}_${j.id}" 
                                   placeholder="N°" min="0" max="99"
                                   onchange="actualizarNumero('${lado}', ${j.id}, this.value)">
                            <input type="hidden" name="numeros_${lado}[${j.id}][jugador_id]" value="${j.id}">
                            <input type="hidden" name="numeros_${lado}[${j.id}][numero]" id="hidden_num_${lado}_${j.id}" value="">
                        </div>
                    `;
                });
                container.innerHTML = html;
            } catch (error) {
                console.error('Error:', error);
                container.innerHTML = '<div class="text-center text-danger py-3">Error al cargar jugadores</div>';
            }
        }
        
        function actualizarNumero(lado, jugadorId, numero) {
            const hiddenInput = document.getElementById(`hidden_num_${lado}_${jugadorId}`);
            if (hiddenInput) {
                hiddenInput.value = numero || '';
            }
            if (lado === 'local') {
                if (numero) {
                    numerosLocal[jugadorId] = numero;
                } else {
                    delete numerosLocal[jugadorId];
                }
            } else {
                if (numero) {
                    numerosVisitante[jugadorId] = numero;
                } else {
                    delete numerosVisitante[jugadorId];
                }
            }
        }
        
        function irAEventos() {
            const tab = new bootstrap.Tab(document.getElementById('eventos-tab'));
            tab.show();
        }
        
        async function getJugadores(equipoId, editarPartido = false) {
            try {
                const url = 'ajax/get_jugadores.php?equipo_id=' + equipoId + (editarPartido ? '&editar_partido=1' : '');
                const resp = await fetch(url);
                if (!resp.ok) throw new Error('Error al cargar jugadores');
                return await resp.json();
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar los jugadores.');
                return [];
            }
        }
        
        async function addGol(lado) {
            const jugadores = lado === 'local' ? jugadoresLocal : jugadoresVisitante;
            const numeros = lado === 'local' ? numerosLocal : numerosVisitante;
            let container = document.getElementById('goles' + lado.charAt(0).toUpperCase() + lado.slice(1) + 'Container');
            let div = document.createElement('div');
            div.className = 'row mb-2 gol-item align-items-center';
            let options = '<option value="">Seleccionar jugador</option>';
            jugadores.forEach(j => {
                const numero = numeros[j.id] || '';
                const display = numero ? `#${numero} - ${j.apellido_nombre}` : j.apellido_nombre;
                options += `<option value="${j.id}" data-numero="${numero}">${display}</option>`;
            });
            const index = container.children.length;
            div.innerHTML = `
                <div class="col-2">
                    <input type="number" class="form-control text-center" placeholder="N°" 
                           onchange="seleccionarJugadorPorNumero(this, 'gol', ${index}, '${lado}')">
                </div>
                <div class="col-8">
                    <select class="form-select" name="goles[${index}][jugador_id]" 
                            id="gol_${lado}_${index}" required>
                        ${options}
                    </select>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeGol(this, '${lado}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
            updateGoles(lado);
        }
        
        async function addTarjeta(lado) {
            const jugadores = lado === 'local' ? jugadoresLocal : jugadoresVisitante;
            const numeros = lado === 'local' ? numerosLocal : numerosVisitante;
            let container = document.getElementById('tarjetas' + lado.charAt(0).toUpperCase() + lado.slice(1) + 'Container');
            let div = document.createElement('div');
            div.className = 'row mb-2 tarjeta-item align-items-center';
            let options = '<option value="">Seleccionar jugador</option>';
            jugadores.forEach(j => {
                const numero = numeros[j.id] || '';
                const display = numero ? `#${numero} - ${j.apellido_nombre}` : j.apellido_nombre;
                options += `<option value="${j.id}" data-numero="${numero}">${display}</option>`;
            });
            const index = container.children.length;
            div.innerHTML = `
                <div class="col-2">
                    <input type="number" class="form-control text-center" placeholder="N°" 
                           onchange="seleccionarJugadorPorNumero(this, 'tarjeta', ${index}, '${lado}')">
                </div>
                <div class="col-5">
                    <select class="form-select" name="tarjetas[${index}][jugador_id]" 
                            id="tarjeta_${lado}_${index}" required>
                        ${options}
                    </select>
                </div>
                <div class="col-3">
                    <select class="form-select" name="tarjetas[${index}][tipo]" required>
                        <option value="">Tipo</option>
                        <option value="amarilla">🟨 Amarilla</option>
                        <option value="roja">🟥 Roja</option>
                    </select>
                </div>
                <div class="col-2">
                    <button type="button" class="btn btn-sm btn-outline-danger w-100" onclick="removeTarjeta(this)">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
        }
        
        function seleccionarJugadorPorNumero(input, tipo, index, lado) {
            const numero = input.value;
            if (!numero) return;
            const select = document.getElementById(`${tipo}_${lado}_${index}`);
            if (!select) return;
            for (let i = 0; i < select.options.length; i++) {
                const option = select.options[i];
                if (option.dataset.numero == numero) {
                    select.value = option.value;
                    select.classList.remove('is-invalid');
                    return;
                }
            }
            alert(`No se encontró jugador con número ${numero}`);
            input.value = '';
        }
        
        function removeGol(button, lado) {
            button.closest('.gol-item').remove();
            updateGoles(lado);
        }
        
        function removeTarjeta(button) {
            button.closest('.tarjeta-item').remove();
        }
        
        function updateGoles(lado) {
            const container = document.getElementById('goles' + lado.charAt(0).toUpperCase() + lado.slice(1) + 'Container');
            const goles = container.querySelectorAll('.gol-item').length;
            document.getElementById('goles_' + lado).value = goles;
        }
        
        async function loadExistingEvents(partidoId) {
            try {
                const response = await fetch('ajax/get_eventos.php?partido_id=' + partidoId);
                if (!response.ok) throw new Error('Error al cargar eventos');
                const data = await response.json();
                
                if (data.jugadores_partido && Array.isArray(data.jugadores_partido)) {
                    data.jugadores_partido.forEach(jp => {
                        const lado = jp.equipo_id == document.getElementById('equipo_local_id').value ? 'local' : 'visitante';
                        const numeroInput = document.getElementById(`num_${lado}_${jp.jugador_id}`);
                        if (numeroInput && jp.numero_camiseta > 0) {
                            numeroInput.value = jp.numero_camiseta;
                            actualizarNumero(lado, jp.jugador_id, jp.numero_camiseta);
                        }
                    });
                }
                
                if (data.goles && Array.isArray(data.goles)) {
                    for (const evento of data.goles) {
                        const lado = evento.equipo_id == document.getElementById('equipo_local_id').value ? 'local' : 'visitante';
                        await addGol(lado);
                        const container = document.getElementById('goles' + lado.charAt(0).toUpperCase() + lado.slice(1) + 'Container');
                        const ultimoSelect = container.querySelector('.gol-item:last-child select[name*="jugador_id"]');
                        if (ultimoSelect) {
                            ultimoSelect.value = evento.jugador_id;
                        }
                    }
                }
                
                if (data.tarjetas && Array.isArray(data.tarjetas)) {
                    for (const evento of data.tarjetas) {
                        const lado = evento.equipo_id == document.getElementById('equipo_local_id').value ? 'local' : 'visitante';
                        await addTarjeta(lado);
                        const container = document.getElementById('tarjetas' + lado.charAt(0).toUpperCase() + lado.slice(1) + 'Container');
                        const ultimaFila = container.querySelector('.tarjeta-item:last-child');
                        if (ultimaFila) {
                            const selectJugador = ultimaFila.querySelector('select[name*="jugador_id"]');
                            const selectTipo = ultimaFila.querySelector('select[name*="tipo"]');
                            if (selectJugador) selectJugador.value = evento.jugador_id;
                            if (selectTipo) selectTipo.value = evento.tipo_evento;
                        }
                    }
                }
            } catch (error) {
                console.error('Error cargando eventos:', error);
            }
        }
        
        document.getElementById('formResultado').addEventListener('submit', function(e) {
            const golesLocal = parseInt(document.getElementById('goles_local').value) || 0;
            const golesVisitante = parseInt(document.getElementById('goles_visitante').value) || 0;
            if (golesLocal === 0 && golesVisitante === 0) {
                if (!confirm('El resultado es 0-0. ¿Está seguro de que es correcto?')) {
                    e.preventDefault();
                    return false;
                }
            }
            const selectsJugadores = this.querySelectorAll('select[name*="jugador_id"]');
            const selectsTipoTarjeta = this.querySelectorAll('select[name*="tipo"]');
            let hayErrores = false;
            selectsJugadores.forEach(select => {
                if (!select.value) {
                    select.classList.add('is-invalid');
                    hayErrores = true;
                } else {
                    select.classList.remove('is-invalid');
                }
            });
            selectsTipoTarjeta.forEach(select => {
                if (!select.value) {
                    select.classList.add('is-invalid');
                    hayErrores = true;
                } else {
                    select.classList.remove('is-invalid');
                }
            });
            if (hayErrores) {
                e.preventDefault();
                alert('Por favor, completa todos los campos obligatorios en rojo.');
                return false;
            }
            const action = document.getElementById('modal_action').value;
            const nombreLocal = document.getElementById('nombre_local_eventos').textContent;
            const nombreVisitante = document.getElementById('nombre_visitante_eventos').textContent;
            const cantNumerosLocal = Object.keys(numerosLocal).length;
            const cantNumerosVisitante = Object.keys(numerosVisitante).length;
            const totalLocal = cantNumerosLocal === 0 ? jugadoresLocal.length + ' (todos)' : cantNumerosLocal;
            const totalVisitante = cantNumerosVisitante === 0 ? jugadoresVisitante.length + ' (todos)' : cantNumerosVisitante;
            const mensaje = action === 'cargar_resultado' ? 
                `¿Confirmar resultado ${nombreLocal} ${golesLocal} - ${golesVisitante} ${nombreVisitante}?\nJugadores que participaron:\n- Local: ${totalLocal}\n- Visitante: ${totalVisitante}\nLas sanciones se descontarán automáticamente.` :
                `¿Confirmar cambios en el resultado ${nombreLocal} ${golesLocal} - ${golesVisitante} ${nombreVisitante}?\nJugadores que participaron:\n- Local: ${totalLocal}\n- Visitante: ${totalVisitante}\nLas sanciones se actualizarán automáticamente.`;
            if (!confirm(mensaje)) {
                e.preventDefault();
                return false;
            }
            const btnGuardar = document.getElementById('btnGuardar');
            btnGuardar.disabled = true;
            btnGuardar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
            return true;
        });
        
        document.addEventListener('change', function(e) {
            if (e.target.matches('select') && e.target.classList.contains('is-invalid')) {
                if (e.target.value) {
                    e.target.classList.remove('is-invalid');
                }
            }
        });
    </script>
</body>
</html>
<?php
ob_end_flush();
?>