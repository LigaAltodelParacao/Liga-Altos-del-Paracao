<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// === FUNCIÓN: Crear sanción automática (evita duplicados) ===
function crearSancionAutomatica($db, $jugador_id, $tipo, $partidos_suspension, $descripcion) {
    // Verificar si ya existe una sanción activa del mismo tipo
    $stmt = $db->prepare("SELECT id FROM sanciones WHERE jugador_id = ? AND tipo = ? AND activa = 1");
    $stmt->execute([$jugador_id, $tipo]);
    if ($stmt->fetch()) {
        return; // Ya existe, no duplicar
    }
    
    $stmt = $db->prepare("
        INSERT INTO sanciones (jugador_id, tipo, partidos_suspension, partidos_cumplidos, descripcion, activa, fecha_sancion)
        VALUES (?, ?, ?, 0, ?, 1, CURDATE())
    ");
    $stmt->execute([$jugador_id, $tipo, $partidos_suspension, $descripcion]);
}

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

            // Limpiar eventos anteriores del partido
            $stmt = $db->prepare("DELETE FROM eventos_partido WHERE partido_id = ?");
            $stmt->execute([$partido_id]);

            $tarjetas_por_jugador = [];

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
                        
                        // Guardar en eventos_partido
                        $stmt = $db->prepare("INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto) VALUES (?, ?, ?, 0)");
                        $stmt->execute([$partido_id, $jugador_id, $tipo]);

                        // Contar tarjetas por jugador
                        if (!isset($tarjetas_por_jugador[$jugador_id])) {
                            $tarjetas_por_jugador[$jugador_id] = ['amarillas' => 0, 'rojas' => 0];
                        }
                        
                        if ($tipo === 'amarilla') {
                            $tarjetas_por_jugador[$jugador_id]['amarillas']++;
                        } else {
                            $tarjetas_por_jugador[$jugador_id]['rojas']++;
                        }
                    }
                }
            }

            // === CORREGIR DOBLE AMARILLA: Eliminar amarillas y dejar solo ROJA ===
            foreach ($tarjetas_por_jugador as $jugador_id => $stats) {
                // Si tiene 2 o más amarillas en el mismo partido = DOBLE AMARILLA
                if ($stats['amarillas'] >= 2) {
                    // ELIMINAR todas las amarillas de este jugador en este partido
                    $stmt = $db->prepare("DELETE FROM eventos_partido WHERE partido_id = ? AND jugador_id = ? AND tipo_evento = 'amarilla'");
                    $stmt->execute([$partido_id, $jugador_id]);
                    
                    // INSERTAR UNA SOLA ROJA (representando la doble amarilla)
                    $stmt = $db->prepare("INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto, observaciones) VALUES (?, ?, 'roja', 0, 'Doble amarilla')");
                    $stmt->execute([$partido_id, $jugador_id]);
                }
            }

            // === SANCIONES POR DOBLE AMARILLA O ROJA DIRECTA ===
            foreach ($tarjetas_por_jugador as $jugador_id => $stats) {
                // Doble amarilla = 1 fecha de suspensión
                if ($stats['amarillas'] >= 2) {
                    crearSancionAutomatica($db, $jugador_id, 'doble_amarilla', 1, 'Doble amarilla en partido');
                }
                // Roja directa = 1 fecha de suspensión
                if ($stats['rojas'] > 0) {
                    crearSancionAutomatica($db, $jugador_id, 'roja_directa', 1, 'Tarjeta roja directa');
                }
            }

            // === 4 AMARILLAS ACUMULADAS EN EL CAMPEONATO COMPLETO ===
            $stmt = $db->prepare("
                SELECT cat.campeonato_id
                FROM partidos p
                JOIN fechas f ON p.fecha_id = f.id
                JOIN categorias cat ON f.categoria_id = cat.id
                WHERE p.id = ?
            ");
            $stmt->execute([$partido_id]);
            $campeonato_id = $stmt->fetchColumn();

            if ($campeonato_id) {
                // Obtener todos los jugadores del campeonato
                $stmt = $db->prepare("
                    SELECT DISTINCT j.id
                    FROM jugadores j
                    JOIN equipos e ON j.equipo_id = e.id
                    JOIN categorias cat ON e.categoria_id = cat.id
                    WHERE cat.campeonato_id = ?
                ");
                $stmt->execute([$campeonato_id]);
                $jugadores_campeonato = $stmt->fetchAll(PDO::FETCH_COLUMN);

                foreach ($jugadores_campeonato as $jugador_id) {
                    // Contar SOLO amarillas simples (no contar si fue doble amarilla en el mismo partido)
                    $stmt = $db->prepare("
                        SELECT COUNT(*)
                        FROM eventos_partido ev
                        JOIN partidos p ON ev.partido_id = p.id
                        JOIN fechas f ON p.fecha_id = f.id
                        JOIN categorias cat ON f.categoria_id = cat.id
                        WHERE cat.campeonato_id = ? 
                        AND ev.jugador_id = ? 
                        AND ev.tipo_evento = 'amarilla'
                        AND ev.partido_id NOT IN (
                            SELECT partido_id 
                            FROM eventos_partido 
                            WHERE jugador_id = ev.jugador_id 
                            AND tipo_evento = 'amarilla'
                            GROUP BY partido_id
                            HAVING COUNT(*) >= 2
                        )
                    ");
                    $stmt->execute([$campeonato_id, $jugador_id]);
                    $amarillas = (int)$stmt->fetchColumn();

                    // Si tiene 4 o más amarillas simples acumuladas, crear sanción
                    if ($amarillas >= 4 && $amarillas % 4 == 0) {
                        crearSancionAutomatica($db, $jugador_id, 'amarillas_acumuladas', 1, '4 amarillas acumuladas - Se resetean las amarillas');
                    }
                }
            }

            // === ⭐ NUEVO: CUMPLIR SANCIONES AUTOMÁTICAMENTE ⭐ ===
            $sanciones_actualizadas = 0;
            $detalle_sanciones = [];
            
            if (function_exists('cumplirSancionesAutomaticas')) {
                // IMPORTANTE: Pasar TRUE para excluir sanciones del mismo partido
                $resultado_sanciones = cumplirSancionesAutomaticas($partido_id, $db);
                
                if ($resultado_sanciones['success']) {
                    $sanciones_actualizadas = $resultado_sanciones['actualizados'];
                    $detalle_sanciones = $resultado_sanciones['detalle'] ?? [];
                    
                    // Log detallado de las sanciones cumplidas
                    if ($sanciones_actualizadas > 0) {
                        $log_msg = "Sanciones cumplidas en partido $partido_id: ";
                        foreach ($detalle_sanciones as $det) {
                            $log_msg .= "{$det['nombre']} ({$det['equipo']}): {$det['cumplidos']}/{$det['total']} fechas";
                            if ($det['finalizada']) {
                                $log_msg .= " [FINALIZADA]";
                            }
                            $log_msg .= "; ";
                        }
                        logActivity($log_msg);
                    }
                }
            } else {
                error_log("ADVERTENCIA: cumplirSancionesAutomaticas() no está disponible");
            }

            $db->commit();
            
            $message = $action == 'cargar_resultado' 
                ? 'Resultado y sanciones guardadas correctamente' 
                : 'Resultado editado y sanciones actualizadas';
                
            // Agregar información detallada de sanciones cumplidas
            if ($sanciones_actualizadas > 0) {
                $message .= ". ✅ Se actualizaron automáticamente $sanciones_actualizadas sanción(es).";
                
                // Mostrar jugadores que completaron sus sanciones
                $finalizadas = array_filter($detalle_sanciones, fn($d) => $d['finalizada']);
                if (!empty($finalizadas)) {
                    $nombres = array_map(fn($d) => $d['nombre'], $finalizadas);
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
$stmt = $db->query("SELECT id, nombre FROM campeonatos WHERE activo = 1 ORDER BY nombre");
$campeonatos = $stmt->fetchAll();

$campeonato_id = $_GET['campeonato'] ?? null;
$categoria_id = $_GET['categoria'] ?? null;
$fecha_id = $_GET['fecha'] ?? null;

$categorias = [];
if ($campeonato_id) {
    $stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1");
    $stmt->execute([$campeonato_id]);
    $categorias = $stmt->fetchAll();
}

$fechas_categoria = [];
if ($categoria_id) {
    $stmt = $db->prepare("SELECT id, numero_fecha, fecha_programada FROM fechas WHERE categoria_id = ? ORDER BY numero_fecha");
    $stmt->execute([$categoria_id]);
    $fechas_categoria = $stmt->fetchAll();
}

$partidos = [];
if ($fecha_id) {
    $stmt = $db->prepare("
        SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante,
               can.nombre as cancha, el.color_camiseta as color_local, ev.color_camiseta as color_visitante,
               el.id as equipo_local_id, ev.id as equipo_visitante_id
        FROM partidos p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        LEFT JOIN canchas can ON p.cancha_id = can.id
        WHERE p.fecha_id = ?
        ORDER BY p.hora_partido ASC
    ");
    $stmt->execute([$fecha_id]);
    $partidos = $stmt->fetchAll();
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
    <link href="../assets/css/style.css" rel="stylesheet">
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
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-futbol"></i> Fútbol Manager - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>                
                <a class="nav-link" href="../logout.php">Salir</a>
            </div>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 p-0">
                <?php include 'include/sidebar.php'; ?>
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

                <!-- Alerta informativa del sistema automático -->
                <?php if (function_exists('cumplirSancionesAutomaticas')): ?>
                <div class="alert alert-sistema alert-dismissible fade show">
                    <i class="fas fa-robot"></i> <strong>Sistema Automático Activo:</strong> Las sanciones se descuentan automáticamente cuando finalizas un partido. Los jugadores sancionados cumplen 1 fecha por cada partido jugado.
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
                </div>
                <?php else: ?>
                <div class="alert alert-warning alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle"></i> <strong>Advertencia:</strong> El sistema automático de sanciones no está disponible. Verifica que el archivo include/sanciones_functions.php exista.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

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
                        <select name="fecha" class="form-select" onchange="this.form.submit()">
                            <option value="">Seleccionar Fecha</option>
                            <?php foreach($fechas_categoria as $f): ?>
                                <option value="<?= $f['id'] ?>" <?= $fecha_id==$f['id']?'selected':'' ?>>
                                    Fecha <?= $f['numero_fecha'] ?> (<?= date('d/m/Y', strtotime($f['fecha_programada'])) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="campeonato" value="<?= $campeonato_id ?>">
                        <input type="hidden" name="categoria" value="<?= $categoria_id ?>">
                    </div>
                    <?php endif; ?>
                </form>

                <?php if($partidos): ?>
                <div class="row">
                    <?php foreach($partidos as $p): ?>
                    <?php 
                        $tiene_cancha_y_horario = !empty($p['cancha']) && !empty($p['hora_partido']);
                        $eventos_partido = [];
                        if ($p['estado'] == 'finalizado') {
                            $stmt = $db->prepare("
                                SELECT e.*, j.apellido_nombre, j.equipo_id
                                FROM eventos_partido e
                                JOIN jugadores j ON e.jugador_id = j.id
                                WHERE e.partido_id = ?
                                ORDER BY e.tipo_evento, e.created_at
                            ");
                            $stmt->execute([$p['id']]);
                            $eventos_partido = $stmt->fetchAll();
                        }
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

                                <?php if($p['estado'] == 'finalizado' && !empty($eventos_partido)): ?>
                                <div class="row mt-3">
                                    <div class="col-md-6">
                                        <div class="eventos-equipo">
                                            <h6 style="color:<?= $p['color_local'] ?>"><?= htmlspecialchars($p['equipo_local']) ?></h6>
                                            <?php 
                                            $eventos_local = array_filter($eventos_partido, fn($e) => $e['equipo_id'] == $p['equipo_local_id']);
                                            if(empty($eventos_local)): ?>
                                                <small class="text-muted">Sin eventos</small>
                                            <?php else: ?>
                                                <?php foreach($eventos_local as $evento): ?>
                                                    <div class="mb-1">
                                                        <?php if($evento['tipo_evento'] == 'gol'): ?>
                                                            <i class="fas fa-futbol evento-gol"></i>
                                                        <?php elseif($evento['tipo_evento'] == 'amarilla'): ?>
                                                            <i class="fas fa-square evento-amarilla"></i>
                                                        <?php elseif($evento['tipo_evento'] == 'roja'): ?>
                                                            <i class="fas fa-square evento-roja"></i>
                                                        <?php endif; ?>
                                                        <small><?= htmlspecialchars($evento['apellido_nombre']) ?></small>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="eventos-equipo">
                                            <h6 style="color:<?= $p['color_visitante'] ?>"><?= htmlspecialchars($p['equipo_visitante']) ?></h6>
                                            <?php 
                                            $eventos_visitante = array_filter($eventos_partido, fn($e) => $e['equipo_id'] == $p['equipo_visitante_id']);
                                            if(empty($eventos_visitante)): ?>
                                                <small class="text-muted">Sin eventos</small>
                                            <?php else: ?>
                                                <?php foreach($eventos_visitante as $evento): ?>
                                                    <div class="mb-1">
                                                        <?php if($evento['tipo_evento'] == 'gol'): ?>
                                                            <i class="fas fa-futbol evento-gol"></i>
                                                        <?php elseif($evento['tipo_evento'] == 'amarilla'): ?>
                                                            <i class="fas fa-square evento-amarilla"></i>
                                                        <?php elseif($evento['tipo_evento'] == 'roja'): ?>
                                                            <i class="fas fa-square evento-roja"></i>
                                                        <?php endif; ?>
                                                        <small><?= htmlspecialchars($evento['apellido_nombre']) ?></small>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
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

    <!-- Modal -->
    <div class="modal fade" id="modalResultado">
        <div class="modal-dialog modal-xl">
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
                        <div class="row">
                            <div class="col-md-6 border-end">
                                <h5 id="nombre_local" class="text-center mb-3"></h5>
                                <div class="mb-3">
                                    <label class="form-label">Goles Local</label>
                                    <input type="number" class="form-control form-control-lg text-center" 
                                           name="goles_local" id="goles_local" value="0" readonly 
                                           style="font-size: 1.5rem; font-weight: bold;">
                                </div>
                                <h6><i class="fas fa-futbol text-success"></i> Goles</h6>
                                <div id="golesLocalContainer"></div>
                                <button type="button" class="btn btn-sm btn-outline-success mb-3" onclick="addGol('local')">
                                    <i class="fas fa-plus"></i> Agregar Gol
                                </button>
                                <h6><i class="fas fa-square text-warning"></i> Tarjetas</h6>
                                <div id="tarjetasLocalContainer"></div>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="addTarjeta('local')">
                                    <i class="fas fa-plus"></i> Agregar Tarjeta
                                </button>
                            </div>
                            <div class="col-md-6">
                                <h5 id="nombre_visitante" class="text-center mb-3"></h5>
                                <div class="mb-3">
                                    <label class="form-label">Goles Visitante</label>
                                    <input type="number" class="form-control form-control-lg text-center" 
                                           name="goles_visitante" id="goles_visitante" value="0" readonly
                                           style="font-size: 1.5rem; font-weight: bold;">
                                </div>
                                <h6><i class="fas fa-futbol text-success"></i> Goles</h6>
                                <div id="golesVisitanteContainer"></div>
                                <button type="button" class="btn btn-sm btn-outline-success mb-3" onclick="addGol('visitante')">
                                    <i class="fas fa-plus"></i> Agregar Gol
                                </button>
                                <h6><i class="fas fa-square text-warning"></i> Tarjetas</h6>
                                <div id="tarjetasVisitanteContainer"></div>
                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="addTarjeta('visitante')">
                                    <i class="fas fa-plus"></i> Agregar Tarjeta
                                </button>
                            </div>
                        </div>
                        <div class="mt-4">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" id="observaciones" rows="3" 
                                      placeholder="Observaciones del partido, incidencias, etc."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-success" id="btnGuardar">
                            <i class="fas fa-save"></i> Guardar Resultado
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function cargarPartido(id, equipoLocal, equipoVisitante, nombreLocal, nombreVisitante) {
            resetModal();
            document.getElementById('modal_action').value = 'cargar_resultado';
            document.getElementById('modal_title').textContent = 'Cargar Resultado';
            document.getElementById('btnGuardar').innerHTML = '<i class="fas fa-save"></i> Guardar Resultado';
            fillModalData(id, equipoLocal, equipoVisitante, nombreLocal, nombreVisitante);
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
            loadExistingEvents(id);
        }
        
        function fillModalData(id, equipoLocal, equipoVisitante, nombreLocal, nombreVisitante) {
            document.getElementById('partido_id').value = id;
            document.getElementById('equipo_local_id').value = equipoLocal;
            document.getElementById('equipo_visitante_id').value = equipoVisitante;
            document.getElementById('nombre_local').textContent = nombreLocal;
            document.getElementById('nombre_visitante').textContent = nombreVisitante;
        }
        
        function resetModal() {
            document.getElementById('golesLocalContainer').innerHTML = '';
            document.getElementById('golesVisitanteContainer').innerHTML = '';
            document.getElementById('tarjetasLocalContainer').innerHTML = '';
            document.getElementById('tarjetasVisitanteContainer').innerHTML = '';
            document.getElementById('goles_local').value = 0;
            document.getElementById('goles_visitante').value = 0;
            document.getElementById('observaciones').value = '';
        }
        
        async function getJugadores(equipoId) {
            try {
                const resp = await fetch('get_jugadores.php?equipo_id=' + equipoId);
                if (!resp.ok) throw new Error('Error al cargar jugadores');
                return await resp.json();
            } catch (error) {
                console.error('Error:', error);
                alert('Error al cargar los jugadores. Asegúrate de que el archivo get_jugadores.php existe.');
                return [];
            }
        }
        
        async function addGol(lado) {
            const equipoId = document.getElementById('equipo_' + lado + '_id').value;
            const jugadores = await getJugadores(equipoId);
            
            if (jugadores.length === 0) {
                alert('No se pudieron cargar los jugadores o el equipo no tiene jugadores registrados.');
                return;
            }
            
            let container = document.getElementById('goles' + lado.charAt(0).toUpperCase() + lado.slice(1) + 'Container');
            let div = document.createElement('div');
            div.className = 'row mb-2 gol-item';
            
            let options = '<option value="">Seleccionar jugador</option>';
            jugadores.forEach(j => options += `<option value="${j.id}">${j.apellido_nombre}</option>`);
            
            div.innerHTML = `
                <div class="col-10">
                    <select class="form-select" name="goles[${container.children.length}][jugador_id]" required>${options}</select>
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
            const equipoId = document.getElementById('equipo_' + lado + '_id').value;
            const jugadores = await getJugadores(equipoId);
            
            if (jugadores.length === 0) {
                alert('No se pudieron cargar los jugadores o el equipo no tiene jugadores registrados.');
                return;
            }
            
            let container = document.getElementById('tarjetas' + lado.charAt(0).toUpperCase() + lado.slice(1) + 'Container');
            let div = document.createElement('div');
            div.className = 'row mb-2 tarjeta-item';
            
            let options = '<option value="">Seleccionar jugador</option>';
            jugadores.forEach(j => options += `<option value="${j.id}">${j.apellido_nombre}</option>`);
            
            const index = container.children.length;
            div.innerHTML = `
                <div class="col-6">
                    <select class="form-select" name="tarjetas[${index}][jugador_id]" required>${options}</select>
                </div>
                <div class="col-4">
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
                const response = await fetch('get_eventos.php?partido_id=' + partidoId);
                if (!response.ok) throw new Error('Error al cargar eventos');
                const data = await response.json();
                
                console.log('Eventos cargados:', data); // Debug
                
                // Cargar goles
                if (data.goles && Array.isArray(data.goles)) {
                    for (const evento of data.goles) {
                        const lado = evento.equipo_id == document.getElementById('equipo_local_id').value ? 'local' : 'visitante';
                        await addGol(lado);
                        
                        // Seleccionar el jugador en el último select agregado
                        const container = document.getElementById('goles' + lado.charAt(0).toUpperCase() + lado.slice(1) + 'Container');
                        const ultimoSelect = container.querySelector('.gol-item:last-child select[name*="jugador_id"]');
                        if (ultimoSelect) {
                            ultimoSelect.value = evento.jugador_id;
                        }
                    }
                }
                
                // Cargar tarjetas
                if (data.tarjetas && Array.isArray(data.tarjetas)) {
                    for (const evento of data.tarjetas) {
                        const lado = evento.equipo_id == document.getElementById('equipo_local_id').value ? 'local' : 'visitante';
                        await addTarjeta(lado);
                        
                        // Seleccionar jugador y tipo en el último select agregado
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
                alert('Error al cargar los eventos del partido. Verifica que get_eventos.php esté funcionando correctamente.');
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
            const nombreLocal = document.getElementById('nombre_local').textContent;
            const nombreVisitante = document.getElementById('nombre_visitante').textContent;
            const mensaje = action === 'cargar_resultado' ? 
                `¿Confirmar resultado ${nombreLocal} ${golesLocal} - ${golesVisitante} ${nombreVisitante}? Las sanciones se descontarán automáticamente.` :
                `¿Confirmar cambios en el resultado ${nombreLocal} ${golesLocal} - ${golesVisitante} ${nombreVisitante}? Las sanciones se actualizarán automáticamente.`;
            
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