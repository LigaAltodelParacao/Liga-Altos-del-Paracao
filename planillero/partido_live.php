<?php
// partido_live.php - Versión con tiempos de 30 minutos por período - CORREGIDO
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('planillero')) {
    redirect('../login.php');
}

if (!isset($_SESSION['codigo_cancha_activo'])) {
    redirect('ingreso_codigo.php');
}

$db = Database::getInstance()->getConnection();
$codigo_activo = $_SESSION['codigo_cancha_activo'];
$cancha_id = $codigo_activo['cancha_id'];

$partido_id = $_GET['partido_id'] ?? null;
if (!$partido_id) {
    die("Error: No se especificó ID de partido");
}

// Consulta del partido
try {
    $stmt = $db->prepare("
        SELECT p.*, 
               el.nombre AS equipo_local, el.id AS equipo_local_id, el.color_camiseta as color_local,
               ev.nombre AS equipo_visitante, ev.id AS equipo_visitante_id, ev.color_camiseta as color_visitante,
               c.nombre AS cancha_nombre, 
               cat.nombre AS categoria, cat.id as categoria_id, cat.campeonato_id,
               f.numero_fecha
        FROM partidos p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        JOIN canchas c ON p.cancha_id = c.id
        JOIN fechas f ON p.fecha_id = f.id
        JOIN categorias cat ON f.categoria_id = cat.id
        WHERE p.id = ?
    ");
    $stmt->execute([$partido_id]);
    $partido = $stmt->fetch();
    
    if (!$partido) {
        die("Error: Partido no encontrado");
    }
} catch (Exception $e) {
    die("Error en consulta: " . $e->getMessage());
}

// Función crear sanción automática
function crearSancionAutomatica($db, $jugador_id, $tipo, $partidos_suspension, $descripcion) {
    $stmt = $db->prepare("SELECT id FROM sanciones WHERE jugador_id = ? AND tipo = ? AND activa = 1");
    $stmt->execute([$jugador_id, $tipo]);
    if ($stmt->fetch()) {
        return;
    }
    
    $stmt = $db->prepare("
        INSERT INTO sanciones (jugador_id, tipo, partidos_suspension, partidos_cumplidos, descripcion, activa, fecha_sancion)
        VALUES (?, ?, ?, 0, ?, 1, CURDATE())
    ");
    $stmt->execute([$jugador_id, $tipo, $partidos_suspension, $descripcion]);
}

// PROCESAR ACCIONES AJAX
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    $action = $_POST['ajax_action'];
    
    try {
        if ($action == 'cambiar_estado') {
            $nuevo_estado = $_POST['nuevo_estado'];
            $tiempo_actual = $_POST['tiempo_actual'];
            $segundos = (int)$_POST['segundos'];
            
            // Calcular minuto total (para stats)
            $minuto_total = floor($segundos / 60);
            
            // Calcular minuto dentro del período actual
            $minuto_periodo = 0;
            if ($tiempo_actual === 'primer_tiempo') {
                $minuto_periodo = min(30, floor($segundos / 60)); // Máximo 30
            } elseif ($tiempo_actual === 'segundo_tiempo') {
                $minuto_periodo = min(30, floor(($segundos - 1800) / 60)); // 0-30
            } elseif ($tiempo_actual === 'descanso' || $tiempo_actual === 'finalizado') {
                $minuto_periodo = 0;
            }
            
            $stmt = $db->prepare("UPDATE partidos SET estado = ?, tiempo_actual = ?, segundos_transcurridos = ?, minuto_actual = ?, minuto_periodo = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $tiempo_actual, $segundos, $minuto_total, $minuto_periodo, $partido_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($action == 'guardar_evento') {
            $jugador_id = (int)$_POST['jugador_id'];
            $tipo_evento = $_POST['tipo_evento'];
            $minuto = (int)$_POST['minuto'];
            
            $stmt = $db->prepare("INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto) VALUES (?, ?, ?, ?)");
            $stmt->execute([$partido_id, $jugador_id, $tipo_evento, $minuto]);
            $evento_id = $db->lastInsertId();
            
            if ($tipo_evento == 'gol') {
                $stmt = $db->prepare("SELECT equipo_id FROM jugadores WHERE id = ?");
                $stmt->execute([$jugador_id]);
                $equipo_id = $stmt->fetchColumn();
                
                if ($equipo_id == $partido['equipo_local_id']) {
                    $stmt = $db->prepare("UPDATE partidos SET goles_local = goles_local + 1 WHERE id = ?");
                    $stmt->execute([$partido_id]);
                } else {
                    $stmt = $db->prepare("UPDATE partidos SET goles_visitante = goles_visitante + 1 WHERE id = ?");
                    $stmt->execute([$partido_id]);
                }
            }
            
            echo json_encode(['success' => true, 'evento_id' => $evento_id]);
            exit;
        }
        
        if ($action == 'eliminar_evento') {
            $evento_id = (int)$_POST['evento_id'];
            
            $stmt = $db->prepare("SELECT tipo_evento, jugador_id FROM eventos_partido WHERE id = ?");
            $stmt->execute([$evento_id]);
            $evento = $stmt->fetch();
            
            if ($evento && $evento['tipo_evento'] == 'gol') {
                $stmt = $db->prepare("SELECT equipo_id FROM jugadores WHERE id = ?");
                $stmt->execute([$evento['jugador_id']]);
                $equipo_id = $stmt->fetchColumn();
                
                if ($equipo_id == $partido['equipo_local_id']) {
                    $stmt = $db->prepare("UPDATE partidos SET goles_local = GREATEST(0, goles_local - 1) WHERE id = ?");
                    $stmt->execute([$partido_id]);
                } else {
                    $stmt = $db->prepare("UPDATE partidos SET goles_visitante = GREATEST(0, goles_visitante - 1) WHERE id = ?");
                    $stmt->execute([$partido_id]);
                }
            }
            
            $stmt = $db->prepare("DELETE FROM eventos_partido WHERE id = ?");
            $stmt->execute([$evento_id]);
            
            echo json_encode(['success' => true]);
            exit;
        }
        
        if ($action == 'finalizar_partido') {
            $db->beginTransaction();
            
            $goles_local = (int)$_POST['goles_local'];
            $goles_visitante = (int)$_POST['goles_visitante'];
            $observaciones = trim($_POST['observaciones'] ?? '');
            
            $stmt = $db->prepare("
                UPDATE partidos 
                SET goles_local = ?, goles_visitante = ?, observaciones = ?, estado = 'finalizado', tiempo_actual = 'finalizado', finalizado_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$goles_local, $goles_visitante, $observaciones, $partido_id]);
            
            $stmt = $db->prepare("DELETE FROM jugadores_partido WHERE partido_id = ?");
            $stmt->execute([$partido_id]);
            
            $numerosLocal = json_decode($_POST['numeros_local'], true);
            $numerosVisitante = json_decode($_POST['numeros_visitante'], true);
            
            $procesarJugadores = function($equipo_id, $numeros_array) use ($db, $partido_id) {
                $stmt = $db->prepare("SELECT id FROM jugadores WHERE equipo_id = ? AND activo = 1");
                $stmt->execute([$equipo_id]);
                $todos_jugadores = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (empty($numeros_array)) {
                    foreach ($todos_jugadores as $jug_id) {
                        $stmt = $db->prepare("INSERT INTO jugadores_partido (partido_id, jugador_id, numero_camiseta) VALUES (?, ?, 0)");
                        $stmt->execute([$partido_id, $jug_id]);
                    }
                } else {
                    foreach ($numeros_array as $num) {
                        if (!empty($num['numero']) && !empty($num['jugador_id'])) {
                            $stmt = $db->prepare("INSERT INTO jugadores_partido (partido_id, jugador_id, numero_camiseta) VALUES (?, ?, ?)");
                            $stmt->execute([$partido_id, (int)$num['jugador_id'], (int)$num['numero']]);
                        }
                    }
                }
            };
            
            $procesarJugadores($partido['equipo_local_id'], $numerosLocal);
            $procesarJugadores($partido['equipo_visitante_id'], $numerosVisitante);
            
            $stmt = $db->prepare("SELECT * FROM eventos_partido WHERE partido_id = ?");
            $stmt->execute([$partido_id]);
            $eventos = $stmt->fetchAll();
            
            $tarjetas_por_jugador = [];
            foreach ($eventos as $evento) {
                if ($evento['tipo_evento'] == 'amarilla' || $evento['tipo_evento'] == 'roja') {
                    $jugador_id = $evento['jugador_id'];
                    if (!isset($tarjetas_por_jugador[$jugador_id])) {
                        $tarjetas_por_jugador[$jugador_id] = ['amarillas' => 0, 'rojas' => 0];
                    }
                    if ($evento['tipo_evento'] == 'amarilla') {
                        $tarjetas_por_jugador[$jugador_id]['amarillas']++;
                    } else {
                        $tarjetas_por_jugador[$jugador_id]['rojas']++;
                    }
                }
            }
            
            foreach ($tarjetas_por_jugador as $jugador_id => $stats) {
                if ($stats['amarillas'] >= 2) {
                    $stmt = $db->prepare("DELETE FROM eventos_partido WHERE partido_id = ? AND jugador_id = ? AND tipo_evento = 'amarilla'");
                    $stmt->execute([$partido_id, $jugador_id]);
                    
                    $stmt = $db->prepare("INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto, observaciones) VALUES (?, ?, 'roja', 0, 'Doble amarilla')");
                    $stmt->execute([$partido_id, $jugador_id]);
                }
            }
            
            foreach ($tarjetas_por_jugador as $jugador_id => $stats) {
                if ($stats['amarillas'] >= 2) {
                    crearSancionAutomatica($db, $jugador_id, 'doble_amarilla', 1, 'Doble amarilla en partido');
                }
                if ($stats['rojas'] > 0) {
                    crearSancionAutomatica($db, $jugador_id, 'roja_directa', 1, 'Tarjeta roja directa');
                }
            }
            
            $campeonato_id = $partido['campeonato_id'];
            if ($campeonato_id) {
                $stmt = $db->prepare("SELECT DISTINCT j.id FROM jugadores j JOIN equipos e ON j.equipo_id = e.id JOIN categorias cat ON e.categoria_id = cat.id WHERE cat.campeonato_id = ?");
                $stmt->execute([$campeonato_id]);
                $jugadores_campeonato = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($jugadores_campeonato as $jugador_id) {
                    $stmt = $db->prepare("
                        SELECT COUNT(*) FROM eventos_partido ev
                        JOIN partidos p ON ev.partido_id = p.id
                        JOIN fechas f ON p.fecha_id = f.id
                        JOIN categorias cat ON f.categoria_id = cat.id
                        WHERE cat.campeonato_id = ? AND ev.jugador_id = ? AND ev.tipo_evento = 'amarilla'
                        AND ev.partido_id NOT IN (
                            SELECT partido_id FROM eventos_partido 
                            WHERE jugador_id = ev.jugador_id AND tipo_evento = 'amarilla'
                            GROUP BY partido_id HAVING COUNT(*) >= 2
                        )
                    ");
                    $stmt->execute([$campeonato_id, $jugador_id]);
                    $amarillas = (int)$stmt->fetchColumn();
                    
                    if ($amarillas >= 4 && $amarillas % 4 == 0) {
                        crearSancionAutomatica($db, $jugador_id, 'amarillas_acumuladas', 1, '4 amarillas acumuladas');
                    }
                }
            }
            
            $sanciones_actualizadas = 0;
            if (function_exists('cumplirSancionesAutomaticas')) {
                $resultado_sanciones = cumplirSancionesAutomaticas($partido_id, $db);
                if ($resultado_sanciones['success']) {
                    $sanciones_actualizadas = $resultado_sanciones['actualizados'];
                }
            }
            
            $db->commit();
            echo json_encode(['success' => true, 'sanciones_actualizadas' => $sanciones_actualizadas]);
            exit;
        }
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollback();
        }
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Cargar jugadores
$stmt = $db->prepare("SELECT id, apellido_nombre, equipo_id FROM jugadores WHERE equipo_id = ? AND activo = 1 ORDER BY apellido_nombre");
$stmt->execute([$partido['equipo_local_id']]);
$jugadores_local = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT id, apellido_nombre, equipo_id FROM jugadores WHERE equipo_id = ? AND activo = 1 ORDER BY apellido_nombre");
$stmt->execute([$partido['equipo_visitante_id']]);
$jugadores_visitante = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar eventos existentes
$stmt = $db->prepare("SELECT e.*, j.apellido_nombre, j.equipo_id FROM eventos_partido e JOIN jugadores j ON e.jugador_id = j.id WHERE e.partido_id = ? ORDER BY e.minuto ASC, e.created_at ASC");
$stmt->execute([$partido_id]);
$eventos_existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cargar números de jugadores
$stmt = $db->prepare("SELECT jugador_id, numero_camiseta FROM jugadores_partido WHERE partido_id = ?");
$stmt->execute([$partido_id]);
$numeros_existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Partido en Vivo - Sistema de Campeonatos</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
<style>
    .partido-header {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    .cronometro {
        font-size: 3.5rem;
        font-weight: bold;
        font-family: 'Courier New', monospace;
        color: #dc3545;
        text-align: center;
        padding: 20px;
        background: white;
        border-radius: 15px;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .marcador {
        font-size: 3rem;
        font-weight: bold;
        text-align: center;
        padding: 20px;
        background: white;
        border-radius: 15px;
        margin-bottom: 20px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    .btn-control {
        font-size: 1.2rem;
        padding: 15px 30px;
        border-radius: 10px;
        font-weight: bold;
    }
    .numeros-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .jugador-item {
        padding: 10px;
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
        background-color: #f8f9fa;
    }
    .estado-badge {
        font-size: 1.2rem;
        padding: 10px 20px;
        border-radius: 20px;
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
    .tab-eventos {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 10px;
    }
    .gol-item, .tarjeta-item {
        margin-bottom: 10px;
    }
    .is-invalid {
        border-color: #dc3545 !important;
        background-color: #fff5f5 !important;
    }

    /* --- Ajustes para móvil --- */
    @media (max-width: 576px) {
        .partido-header {
            padding: 16px;
            margin-bottom: 16px;
        }
        .cronometro {
            font-size: 2.4rem;
            padding: 12px;
            margin-bottom: 12px;
        }
        .marcador {
            font-size: 2rem;
            padding: 12px;
            margin-bottom: 12px;
        }
        .btn-control {
            font-size: 1rem;
            padding: 14px 18px;
            border-radius: 12px;
        }
        /* Botón principal fijo en la parte inferior para uso cómodo en móvil */
        #btnControl {
            position: fixed;
            left: 12px;
            right: 12px;
            bottom: 12px;
            z-index: 1050;
        }
        body {
            padding-bottom: 84px; /* espacio para el botón fijo */
        }
        .numeros-card {
            padding: 16px;
        }
        .grilla-jugadores {
            max-height: 50vh;
        }
        .jugador-numero-input {
            height: 42px;
            font-size: 1rem;
        }
        .tab-eventos h6 {
            font-size: 1rem;
        }
        .estado-badge {
            font-size: 1rem;
            padding: 8px 14px;
        }
    }
</style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-futbol"></i> Fútbol Manager - Planillero
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="planillero.php">Mis Partidos</a>
                <a class="nav-link" href="<?php echo SITE_URL; ?>logout.php">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4 mb-5">
        <div class="partido-header">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2><i class="fas fa-broadcast-tower text-danger"></i> Partido en Vivo</h2>
                <a href="planillero.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
            
            <div class="resultado-final">
                <strong style="color:<?= $partido['color_local'] ?>"><?= htmlspecialchars($partido['equipo_local']) ?></strong>
                <span class="mx-4" style="font-size: 2.5rem;" id="marcadorHeader">
                    <span id="golesLocalHeader"><?= $partido['goles_local'] ?></span> - <span id="golesVisitanteHeader"><?= $partido['goles_visitante'] ?></span>
                </span>
                <strong style="color:<?= $partido['color_visitante'] ?>"><?= htmlspecialchars($partido['equipo_visitante']) ?></strong>
            </div>
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($partido['cancha_nombre']) ?> |
                    <i class="fas fa-trophy"></i> <?= htmlspecialchars($partido['categoria']) ?> |
                    <i class="fas fa-calendar"></i> Fecha <?= $partido['numero_fecha'] ?>
                </small>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="cronometro" id="cronometro">00:00</div>
                <div class="text-center mb-3">
                    <span class="badge estado-badge" id="estadoPartido">PROGRAMADO</span>
                </div>
            </div>
            <div class="col-md-6">
                <div class="marcador">
                    <span id="golesLocal"><?= $partido['goles_local'] ?></span> - <span id="golesVisitante"><?= $partido['goles_visitante'] ?></span>
                </div>
                <div class="text-center">
                    <button class="btn btn-control btn-success" id="btnControl">
                        <i class="fas fa-play"></i> Iniciar Partido
                    </button>
                </div>
            </div>
        </div>

        <?php if (function_exists('cumplirSancionesAutomaticas')): ?>
        <div class="alert alert-info alert-dismissible fade show">
            <i class="fas fa-robot"></i> <strong>Sistema Automático:</strong> Las sanciones se aplicarán automáticamente al finalizar el partido.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <ul class="nav nav-tabs mb-4" id="partidoTabs">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabNumeros" id="tab-numeros-btn">
                    <i class="fas fa-users"></i> 1. Números de Jugadores (Opcional)
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabEventos" id="tab-eventos-btn">
                    <i class="fas fa-clipboard-list"></i> 2. Cargar Eventos
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tabNumeros">
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> <strong>Instrucciones:</strong>
                    <ul class="mb-0 mt-2">
                        <li>Si <strong>NO</strong> cargas ningún número, se considera que <strong>TODOS</strong> los jugadores jugaron.</li>
                        <li>Si cargas al menos un número, solo se registran los jugadores con número.</li>
                    </ul>
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <div class="numeros-card">
                            <h5 class="mb-3" style="color:<?= $partido['color_local'] ?>">
                                <i class="fas fa-users"></i> <?= htmlspecialchars($partido['equipo_local']) ?>
                            </h5>
                            <div class="grilla-jugadores">
                                <?php foreach ($jugadores_local as $j): ?>
                                <div class="jugador-item d-flex align-items-center">
                                    <label class="flex-grow-1"><?= htmlspecialchars($j['apellido_nombre']) ?></label>
                                    <input type="number" class="form-control jugador-numero-input" 
                                           id="num_local_<?= $j['id'] ?>" placeholder="N°" min="0" max="99">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="numeros-card">
                            <h5 class="mb-3" style="color:<?= $partido['color_visitante'] ?>">
                                <i class="fas fa-users"></i> <?= htmlspecialchars($partido['equipo_visitante']) ?>
                            </h5>
                            <div class="grilla-jugadores">
                                <?php foreach ($jugadores_visitante as $j): ?>
                                <div class="jugador-item d-flex align-items-center">
                                    <label class="flex-grow-1"><?= htmlspecialchars($j['apellido_nombre']) ?></label>
                                    <input type="number" class="form-control jugador-numero-input" 
                                           id="num_visitante_<?= $j['id'] ?>" placeholder="N°" min="0" max="99">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tabEventos">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><?= htmlspecialchars($partido['equipo_local']) ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Goles Local</label>
                                    <input type="number" class="form-control form-control-lg text-center" 
                                           id="golesLocalDisplay" value="<?= $partido['goles_local'] ?>" readonly 
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
                                <h5 class="mb-0"><?= htmlspecialchars($partido['equipo_visitante']) ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Goles Visitante</label>
                                    <input type="number" class="form-control form-control-lg text-center" 
                                           id="golesVisitanteDisplay" value="<?= $partido['goles_visitante'] ?>" readonly
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
            </div>
        </div>
    </div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script>
const jugadores_local = <?= json_encode($jugadores_local) ?>;
const jugadores_visitante = <?= json_encode($jugadores_visitante) ?>;
const equipo_local_id = <?= $partido['equipo_local_id'] ?>;
const equipo_visitante_id = <?= $partido['equipo_visitante_id'] ?>;
const partidoId = <?= (int)$partido_id ?>;

let estadoPartido = '<?= $partido['estado'] ?>';
let tiempoActual = '<?= $partido['tiempo_actual'] ?? '' ?>';
let segundosCronometro = <?= $partido['segundos_transcurridos'] ?? 0 ?>;
let intervaloCronometro = null; // manejaremos por tiempo real
let eventos = [];
let segundosInicioPeriodo = 0;

// --- Persistencia local del cronómetro para resiliencia ---
const STORAGE_KEY = `cronometro_partido_${partidoId}`;
// Estructura: { segundosAcumulados: number, lastStartAt: number|null, tiempoActual: string }

function loadCronometroFromStorage() {
	try {
		const raw = localStorage.getItem(STORAGE_KEY);
		if (!raw) return null;
		return JSON.parse(raw);
	} catch (_) { return null; }
}

function saveCronometroToStorage(state) {
	try {
		localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
	} catch (_) {}
}

function clearCronometroStorage() {
	try { localStorage.removeItem(STORAGE_KEY); } catch (_) {}
}

let segundosAcumulados = 0; // segundos previos acumulados al último start/pausa
let lastStartAt = null; // timestamp ms cuando empezó a correr, null si pausado
let ultimoSegundoMostrado = -1; // evitar repintado innecesario
let syncThrottleMs = 5000; // sincronizar con servidor cada 5s
let ultimoSyncAt = 0;

// Prevenir recarga accidental
window.addEventListener('beforeunload', function (e) {
    if (estadoPartido === 'en_curso' && tiempoActual !== 'descanso') {
        e.preventDefault();
        e.returnValue = '';
        return '';
    }
});

// Cargar números existentes
<?php if (!empty($numeros_existentes)): ?>
const numerosExistentes = <?= json_encode($numeros_existentes) ?>;
numerosExistentes.forEach(n => {
    const jugador = jugadores_local.find(j => j.id == n.jugador_id) || jugadores_visitante.find(j => j.id == n.jugador_id);
    if (jugador) {
        const lado = jugador.equipo_id == equipo_local_id ? 'local' : 'visitante';
        const input = document.getElementById(`num_${lado}_${n.jugador_id}`);
        if (input && n.numero_camiseta > 0) {
            input.value = n.numero_camiseta;
        }
    }
});
<?php endif; ?>

// Cargar eventos existentes
<?php if (!empty($eventos_existentes)): ?>
eventos = <?= json_encode($eventos_existentes) ?>;
cargarEventosExistentes();
<?php endif; ?>

// Inicializar
document.addEventListener('DOMContentLoaded', function() {
    console.log('Página cargada - Estado:', estadoPartido, 'Tiempo:', tiempoActual);
    inicializarBotonControl();
    actualizarDisplayCronometro();
    actualizarContadoresGoles();
	// Configurar inicio de período para mostrar el tiempo desde cero en cada tiempo
	if (tiempoActual === 'segundo_tiempo') {
		segundosInicioPeriodo = Math.min(segundosCronometro, 1800);
	} else {
		segundosInicioPeriodo = 0;
	}
	actualizarDisplayCronometro();
    
    // Agregar event listener al botón
    const btnControl = document.getElementById('btnControl');
    if (btnControl) {
        btnControl.addEventListener('click', controlarPartido);
        console.log('Event listener agregado al botón');
    }
    // Restaurar cronómetro desde almacenamiento local
    const saved = loadCronometroFromStorage();
    if (saved) {
        segundosAcumulados = parseInt(saved.segundosAcumulados || 0);
        lastStartAt = saved.lastStartAt ? parseInt(saved.lastStartAt) : null;
        if (estadoPartido === 'en_curso' && tiempoActual !== 'descanso') {
            if (!lastStartAt) {
                lastStartAt = Date.now();
            }
            segundosCronometro = segundosAcumulados + Math.floor((Date.now() - lastStartAt) / 1000);
            if (tiempoActual === 'segundo_tiempo') {
                segundosInicioPeriodo = Math.min(segundosCronometro, 1800);
            }
            iniciarCronometro();
        }
    } else {
        // Inicializar almacenamiento acorde al estado actual
        segundosAcumulados = segundosCronometro;
        lastStartAt = (estadoPartido === 'en_curso' && tiempoActual !== 'descanso') ? Date.now() : null;
        saveCronometroToStorage({ segundosAcumulados, lastStartAt, tiempoActual });
        if (lastStartAt) iniciarCronometro();
    }
});

function cargarEventosExistentes() {
    eventos.forEach(e => {
        const lado = e.equipo_id == equipo_local_id ? 'local' : 'visitante';
        
        if (e.tipo_evento === 'gol') {
            addGol(lado, e);
        } else if (e.tipo_evento === 'amarilla' || e.tipo_evento === 'roja') {
            addTarjeta(lado, e);
        }
    });
}

function actualizarContadoresGoles() {
    const golesLocal = document.querySelectorAll('#golesLocalContainer .gol-item').length;
    const golesVisitante = document.querySelectorAll('#golesVisitanteContainer .gol-item').length;
    
    document.getElementById('golesLocal').textContent = golesLocal;
    document.getElementById('golesLocalHeader').textContent = golesLocal;
    document.getElementById('golesLocalDisplay').value = golesLocal;
    
    document.getElementById('golesVisitante').textContent = golesVisitante;
    document.getElementById('golesVisitanteHeader').textContent = golesVisitante;
    document.getElementById('golesVisitanteDisplay').value = golesVisitante;
}

function inicializarBotonControl() {
    const btn = document.getElementById('btnControl');
    const estadoSpan = document.getElementById('estadoPartido');
    
    console.log('Inicializando botón - Estado:', estadoPartido, 'Tiempo:', tiempoActual);
    
    if (!estadoPartido || estadoPartido === 'programado' || !tiempoActual || tiempoActual === 'programado') {
        btn.innerHTML = '<i class="fas fa-play"></i> Iniciar Partido';
        btn.className = 'btn btn-control btn-success';
        estadoSpan.textContent = 'PROGRAMADO';
        estadoSpan.className = 'badge estado-badge bg-secondary';
    } else if (estadoPartido === 'en_curso') {
        if (tiempoActual === 'primer_tiempo') {
			segundosInicioPeriodo = 0;
            btn.innerHTML = '<i class="fas fa-pause"></i> Fin 1° Tiempo';
            btn.className = 'btn btn-control btn-warning';
            estadoSpan.textContent = 'PRIMER TIEMPO';
            estadoSpan.className = 'badge estado-badge bg-danger';
            iniciarCronometro();
        } else if (tiempoActual === 'descanso') {
            btn.innerHTML = '<i class="fas fa-play"></i> Iniciar 2° Tiempo';
            btn.className = 'btn btn-control btn-info';
            estadoSpan.textContent = 'DESCANSO';
            estadoSpan.className = 'badge estado-badge bg-warning';
        } else if (tiempoActual === 'segundo_tiempo') {
			segundosInicioPeriodo = Math.min(segundosCronometro, 1800);
            btn.innerHTML = '<i class="fas fa-flag-checkered"></i> Finalizar Partido';
            btn.className = 'btn btn-control btn-danger';
            estadoSpan.textContent = 'SEGUNDO TIEMPO';
            estadoSpan.className = 'badge estado-badge bg-danger';
            iniciarCronometro();
        }
    } else if (estadoPartido === 'finalizado') {
        btn.innerHTML = '<i class="fas fa-check"></i> Partido Finalizado';
        btn.className = 'btn btn-control btn-secondary';
        btn.disabled = true;
        estadoSpan.textContent = 'FINALIZADO';
        estadoSpan.className = 'badge estado-badge bg-success';
    }
}

function getSegundosNow() {
    return (segundosAcumulados + (lastStartAt ? Math.floor((Date.now() - lastStartAt) / 1000) : 0));
}

function iniciarCronometro() {
    if (!lastStartAt) {
        lastStartAt = Date.now();
        saveCronometroToStorage({ segundosAcumulados, lastStartAt, tiempoActual });
    }
    if (intervaloCronometro) clearInterval(intervaloCronometro);
    intervaloCronometro = setInterval(() => {
        segundosCronometro = getSegundosNow();
        if (segundosCronometro !== ultimoSegundoMostrado) {
            ultimoSegundoMostrado = segundosCronometro;
            actualizarDisplayCronometro();
        }
        // Sincronizar con servidor cada 5s para reducir carga
        const now = Date.now();
        if (estadoPartido === 'en_curso' && tiempoActual !== 'descanso' && (now - ultimoSyncAt) >= syncThrottleMs) {
            ultimoSyncAt = now;
            const formData = new FormData();
            formData.append('ajax_action', 'cambiar_estado');
            formData.append('nuevo_estado', 'en_curso');
            formData.append('tiempo_actual', tiempoActual);
            formData.append('segundos', segundosCronometro);
            fetch('', { method: 'POST', body: formData }).catch(() => {});
        }
    }, 1000);
}

function detenerCronometro() {
    if (intervaloCronometro) {
        clearInterval(intervaloCronometro);
        intervaloCronometro = null;
    }
    // Persistir acumulado y pausar base de tiempo
    if (lastStartAt) {
        segundosAcumulados = getSegundosNow();
        lastStartAt = null;
        saveCronometroToStorage({ segundosAcumulados, lastStartAt, tiempoActual });
    }
}

function actualizarDisplayCronometro() {
	const transcurrido = Math.max(0, segundosCronometro - segundosInicioPeriodo);
	const mins = Math.floor(transcurrido / 60);
	const secs = transcurrido % 60;
    
    let displayText = '';
    
	if (transcurrido <= 1800) { // 30 minutos = 1800 segundos
        displayText = String(mins).padStart(2, '0') + ':' + String(secs).padStart(2, '0');
    } else {
		const minutosExtra = mins - 30;
        displayText = `30'${minutosExtra > 0 ? '+' + minutosExtra : ''}'`;
    }
    
    document.getElementById('cronometro').textContent = displayText;
}

async function controlarPartido() {
    const btn = document.getElementById('btnControl');
    const estadoSpan = document.getElementById('estadoPartido');
    
    console.log('Control partido - Estado:', estadoPartido, 'Tiempo:', tiempoActual);
    
    btn.disabled = true;
    
    // Estado inicial: Iniciar Partido
    if (!estadoPartido || estadoPartido === 'programado' || !tiempoActual || tiempoActual === 'programado') {
        console.log('Iniciando primer tiempo...');
        
        const exito = await cambiarEstado('en_curso', 'primer_tiempo');
        if (!exito) {
            alert('Error al iniciar el partido');
            btn.disabled = false;
            return;
        }
        
		estadoPartido = 'en_curso';
		tiempoActual = 'primer_tiempo';
		segundosInicioPeriodo = 0;
        iniciarCronometro();
        btn.innerHTML = '<i class="fas fa-pause"></i> Fin 1° Tiempo';
        btn.className = 'btn btn-control btn-warning';
        estadoSpan.textContent = 'PRIMER TIEMPO';
        estadoSpan.className = 'badge estado-badge bg-danger';
        btn.disabled = false;
        console.log('Primer tiempo iniciado');
        return;
        
    // Fin del primer tiempo
    } else if (estadoPartido === 'en_curso' && tiempoActual === 'primer_tiempo') {
        console.log('Finalizando primer tiempo...');
        detenerCronometro();
        
        const exito = await cambiarEstado('en_curso', 'descanso');
        if (!exito) {
            alert('Error al finalizar el primer tiempo');
            btn.disabled = false;
            return;
        }
        
        tiempoActual = 'descanso';
        saveCronometroToStorage({ segundosAcumulados, lastStartAt: null, tiempoActual });
        btn.innerHTML = '<i class="fas fa-play"></i> Iniciar 2° Tiempo';
        btn.className = 'btn btn-control btn-info';
        estadoSpan.textContent = 'DESCANSO';
        estadoSpan.className = 'badge estado-badge bg-warning';
        btn.disabled = false;
        console.log('Descanso iniciado');
        return;
        
    // Iniciar segundo tiempo
    } else if (estadoPartido === 'en_curso' && tiempoActual === 'descanso') {
        console.log('Iniciando segundo tiempo...');
		// Reiniciar visualmente el período (mostrar desde 00:00)
		segundosInicioPeriodo = segundosCronometro;
		
        const exito = await cambiarEstado('en_curso', 'segundo_tiempo');
        if (!exito) {
            alert('Error al iniciar el segundo tiempo');
            btn.disabled = false;
            return;
        }
        
        tiempoActual = 'segundo_tiempo';
        // reanudar base de tiempo manteniendo acumulado del 1°T
        lastStartAt = Date.now();
        saveCronometroToStorage({ segundosAcumulados, lastStartAt, tiempoActual });
        iniciarCronometro();
        btn.innerHTML = '<i class="fas fa-flag-checkered"></i> Finalizar Partido';
        btn.className = 'btn btn-control btn-danger';
        estadoSpan.textContent = 'SEGUNDO TIEMPO';
        estadoSpan.className = 'badge estado-badge bg-danger';
        btn.disabled = false;
        console.log('Segundo tiempo iniciado');
        return;
        
    // Finalizar partido
    } else if (estadoPartido === 'en_curso' && tiempoActual === 'segundo_tiempo') {
        console.log('Intentando finalizar partido...');
        
        if (!confirm('¿Está seguro de finalizar el partido? Esta acción no se puede deshacer.')) {
            btn.disabled = false;
            return;
        }
        
        detenerCronometro();
        clearCronometroStorage();
        
        const golesLocalCount = document.querySelectorAll('#golesLocalContainer .gol-item').length;
        const golesVisitanteCount = document.querySelectorAll('#golesVisitanteContainer .gol-item').length;
        
        let hayGolesIncompletos = false;
        document.querySelectorAll('.select-jugador-gol').forEach(select => {
            if (!select.value) {
                select.classList.add('is-invalid');
                hayGolesIncompletos = true;
            }
        });
        
        let hayTarjetasIncompletas = false;
        document.querySelectorAll('.tarjeta-item').forEach(fila => {
            const selectJugador = fila.querySelector('.select-jugador-tarjeta');
            const selectTipo = fila.querySelector('.select-tipo-tarjeta');
            if (!selectJugador.value) {
                selectJugador.classList.add('is-invalid');
                hayTarjetasIncompletas = true;
            }
            if (!selectTipo.value) {
                selectTipo.classList.add('is-invalid');
                hayTarjetasIncompletas = true;
            }
        });
        
        if (hayGolesIncompletos || hayTarjetasIncompletas) {
            alert('Por favor complete todos los eventos (goles y tarjetas) antes de finalizar el partido.');
            btn.disabled = false;
            return;
        }
        
        const numerosLocal = [];
        jugadores_local.forEach(j => {
            const input = document.getElementById(`num_local_${j.id}`);
            if (input && input.value) {
                numerosLocal.push({ jugador_id: j.id, numero: input.value });
            }
        });
        
        const numerosVisitante = [];
        jugadores_visitante.forEach(j => {
            const input = document.getElementById(`num_visitante_${j.id}`);
            if (input && input.value) {
                numerosVisitante.push({ jugador_id: j.id, numero: input.value });
            }
        });
        
        const formData = new FormData();
        formData.append('ajax_action', 'finalizar_partido');
        formData.append('goles_local', golesLocalCount);
        formData.append('goles_visitante', golesVisitanteCount);
        formData.append('observaciones', '');
        formData.append('numeros_local', JSON.stringify(numerosLocal));
        formData.append('numeros_visitante', JSON.stringify(numerosVisitante));
        
        try {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Finalizando...';
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                estadoPartido = 'finalizado';
                btn.innerHTML = '<i class="fas fa-check"></i> Partido Finalizado';
                btn.className = 'btn btn-control btn-secondary';
                estadoSpan.textContent = 'FINALIZADO';
                estadoSpan.className = 'badge estado-badge bg-success';
                
                let mensaje = 'Partido finalizado correctamente';
                if (result.sanciones_actualizadas > 0) {
                    mensaje += `\n\n✅ Se actualizaron ${result.sanciones_actualizadas} sanción(es) automáticamente.`;
                }
                
                alert(mensaje);
                
                setTimeout(() => {
                    window.location.href = 'planillero.php?mensaje=partido_finalizado';
                }, 2000);
            } else {
                alert('Error al finalizar: ' + result.error);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-flag-checkered"></i> Finalizar Partido';
            }
        } catch (error) {
            alert('Error: ' + error.message);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-flag-checkered"></i> Finalizar Partido';
        }
        return;
    }
    
    btn.disabled = false;
}

async function cambiarEstado(nuevoEstado, nuevoTiempo) {
    const formData = new FormData();
    formData.append('ajax_action', 'cambiar_estado');
    formData.append('nuevo_estado', nuevoEstado);
    formData.append('tiempo_actual', nuevoTiempo);
    formData.append('segundos', segundosCronometro); // ✅ Envía el total acumulado
    
    try {
        console.log('Cambiando estado a:', nuevoEstado, nuevoTiempo);
        const response = await fetch('', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        console.log('Respuesta:', result);
        return result.success;
    } catch (error) {
        console.error('Error en cambiarEstado:', error);
        return false;
    }
}

async function addGol(lado, eventoExistente = null) {
    const jugadores = lado === 'local' ? jugadores_local : jugadores_visitante;
    const numeros = obtenerNumerosLado(lado);
    
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
    const eventoId = eventoExistente ? eventoExistente.id : `new_${Date.now()}_${index}`;
    const jugadorSeleccionado = eventoExistente ? eventoExistente.jugador_id : '';
    
    div.innerHTML = `
        <div class="col-2">
            <input type="number" class="form-control text-center" placeholder="N°" 
                   onchange="seleccionarJugadorPorNumero(this, 'gol', ${index}, '${lado}')">
        </div>
        <div class="col-8">
            <select class="form-select select-jugador-gol" data-evento-id="${eventoId}" 
                    data-lado="${lado}" data-tipo="gol">
                ${options}
            </select>
        </div>
        <div class="col-2">
            <button type="button" class="btn btn-sm btn-outline-danger w-100" 
                    onclick="removeGol(this, '${lado}', ${eventoExistente ? eventoExistente.id : 'null'})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
    
    if (jugadorSeleccionado) {
        const select = div.querySelector('select');
        select.value = jugadorSeleccionado;
    }
    
    actualizarContadoresGoles();
}

async function addTarjeta(lado, eventoExistente = null) {
    const jugadores = lado === 'local' ? jugadores_local : jugadores_visitante;
    const numeros = obtenerNumerosLado(lado);
    
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
    const eventoId = eventoExistente ? eventoExistente.id : `new_${Date.now()}_${index}`;
    const jugadorSeleccionado = eventoExistente ? eventoExistente.jugador_id : '';
    const tipoSeleccionado = eventoExistente ? eventoExistente.tipo_evento : '';
    
    div.innerHTML = `
        <div class="col-2">
            <input type="number" class="form-control text-center" placeholder="N°" 
                   onchange="seleccionarJugadorPorNumero(this, 'tarjeta', ${index}, '${lado}')">
        </div>
        <div class="col-5">
            <select class="form-select select-jugador-tarjeta" data-evento-id="${eventoId}" 
                    data-lado="${lado}" data-tipo="tarjeta">
                ${options}
            </select>
        </div>
        <div class="col-3">
            <select class="form-select select-tipo-tarjeta" data-evento-id="${eventoId}">
                <option value="">Tipo</option>
                <option value="amarilla" ${tipoSeleccionado === 'amarilla' ? 'selected' : ''}>🟨 Amarilla</option>
                <option value="roja" ${tipoSeleccionado === 'roja' ? 'selected' : ''}>🟥 Roja</option>
            </select>
        </div>
        <div class="col-2">
            <button type="button" class="btn btn-sm btn-outline-danger w-100" 
                    onclick="removeTarjeta(this, ${eventoExistente ? eventoExistente.id : 'null'})">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
    
    if (jugadorSeleccionado) {
        const select = div.querySelector('.select-jugador-tarjeta');
        select.value = jugadorSeleccionado;
    }
}

function obtenerNumerosLado(lado) {
    const numeros = {};
    const jugadores = lado === 'local' ? jugadores_local : jugadores_visitante;
    
    jugadores.forEach(j => {
        const input = document.getElementById(`num_${lado}_${j.id}`);
        if (input && input.value) {
            numeros[j.id] = input.value;
        }
    });
    
    return numeros;
}

function seleccionarJugadorPorNumero(input, tipo, index, lado) {
    const numero = input.value;
    if (!numero) return;
    
    const fila = input.closest('.row');
    const select = fila.querySelector('select');
    
    if (!select) return;
    
    for (let i = 0; i < select.options.length; i++) {
        const option = select.options[i];
        if (option.dataset.numero == numero) {
            select.value = option.value;
            select.classList.remove('is-invalid');
            
            const event = new Event('change', { bubbles: true });
            select.dispatchEvent(event);
            return;
        }
    }
    
    alert(`No se encontró jugador con número ${numero}`);
    input.value = '';
}

async function removeGol(button, lado, eventoId) {
    if (eventoId && eventoId !== 'null') {
        if (!confirm('¿Eliminar este gol?')) return;
        
        const formData = new FormData();
        formData.append('ajax_action', 'eliminar_evento');
        formData.append('evento_id', eventoId);
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                eventos = eventos.filter(e => e.id != eventoId);
                button.closest('.gol-item').remove();
                actualizarContadoresGoles();
            } else {
                alert('Error al eliminar el gol');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    } else {
        button.closest('.gol-item').remove();
        actualizarContadoresGoles();
    }
}

async function removeTarjeta(button, eventoId) {
    if (eventoId && eventoId !== 'null') {
        if (!confirm('¿Eliminar esta tarjeta?')) return;
        
        const formData = new FormData();
        formData.append('ajax_action', 'eliminar_evento');
        formData.append('evento_id', eventoId);
        
        try {
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            
            if (result.success) {
                eventos = eventos.filter(e => e.id != eventoId);
                button.closest('.tarjeta-item').remove();
            } else {
                alert('Error al eliminar la tarjeta');
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    } else {
        button.closest('.tarjeta-item').remove();
    }
}

document.addEventListener('change', async function(e) {
    if (e.target.matches('select') && e.target.classList.contains('is-invalid')) {
        if (e.target.value) {
            e.target.classList.remove('is-invalid');
        }
    }
    
    if (e.target.matches('.select-jugador-gol')) {
        const jugadorId = e.target.value;
        if (!jugadorId) return;
        
        const eventoId = e.target.dataset.eventoId;
        const lado = e.target.dataset.lado;
        
        if (eventoId.startsWith('new_')) {
            const minuto = Math.floor(segundosCronometro / 60);
            
            const formData = new FormData();
            formData.append('ajax_action', 'guardar_evento');
            formData.append('jugador_id', jugadorId);
            formData.append('tipo_evento', 'gol');
            formData.append('minuto', minuto);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    const jugador = jugadores_local.find(j => j.id == jugadorId) || jugadores_visitante.find(j => j.id == jugadorId);
                    
                    eventos.push({
                        id: result.evento_id,
                        jugador_id: jugadorId,
                        apellido_nombre: jugador.apellido_nombre,
                        tipo_evento: 'gol',
                        minuto: minuto,
                        equipo_id: jugador.equipo_id
                    });
                    
                    e.target.dataset.eventoId = result.evento_id;
                    
                    const botonEliminar = e.target.closest('.gol-item').querySelector('button');
                    botonEliminar.setAttribute('onclick', `removeGol(this, '${lado}', ${result.evento_id})`);
                    
                    actualizarContadoresGoles();
                } else {
                    alert('Error al guardar el gol');
                    e.target.value = '';
                }
            } catch (error) {
                alert('Error: ' + error.message);
                e.target.value = '';
            }
        }
    }
    
    if (e.target.matches('.select-jugador-tarjeta') || e.target.matches('.select-tipo-tarjeta')) {
        const fila = e.target.closest('.tarjeta-item');
        const selectJugador = fila.querySelector('.select-jugador-tarjeta');
        const selectTipo = fila.querySelector('.select-tipo-tarjeta');
        
        const jugadorId = selectJugador.value;
        const tipoTarjeta = selectTipo.value;
        const eventoId = selectJugador.dataset.eventoId;
        
        if (jugadorId && tipoTarjeta && eventoId.startsWith('new_')) {
            const minuto = Math.floor(segundosCronometro / 60);
            
            const formData = new FormData();
            formData.append('ajax_action', 'guardar_evento');
            formData.append('jugador_id', jugadorId);
            formData.append('tipo_evento', tipoTarjeta);
            formData.append('minuto', minuto);
            
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    const jugador = jugadores_local.find(j => j.id == jugadorId) || jugadores_visitante.find(j => j.id == jugadorId);
                    
                    eventos.push({
                        id: result.evento_id,
                        jugador_id: jugadorId,
                        apellido_nombre: jugador.apellido_nombre,
                        tipo_evento: tipoTarjeta,
                        minuto: minuto,
                        equipo_id: jugador.equipo_id
                    });
                    
                    selectJugador.dataset.eventoId = result.evento_id;
                    selectTipo.dataset.eventoId = result.evento_id;
                    
                    const botonEliminar = fila.querySelector('button');
                    botonEliminar.setAttribute('onclick', `removeTarjeta(this, ${result.evento_id})`);
                } else {
                    alert('Error al guardar la tarjeta');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }
    }
});
</script>
</body>
</html>