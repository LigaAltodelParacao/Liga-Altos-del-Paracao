<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config.php';
$db = Database::getInstance()->getConnection();

// Obtener categorías activas
$stmt = $db->query("
    SELECT c.*, camp.nombre as campeonato_nombre
    FROM categorias c
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE c.activa = 1 AND camp.activo = 1
    ORDER BY camp.fecha_inicio DESC, c.nombre ASC
");
$categorias = $stmt->fetchAll();

// Categoría seleccionada
$categoria_id = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int)$_GET['categoria'] : null;

$categoria_valida = null;
if ($categoria_id !== null) {
    foreach ($categorias as $cat) {
        if ($cat['id'] == $categoria_id) {
            $categoria_valida = $cat;
            break;
        }
    }
    if (!$categoria_valida) {
        $categoria_id = null;
    }
}

// ==================== PARTIDOS EN VIVO ====================
if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT p.*, 
               el.nombre as equipo_local, el.logo as logo_local, el.id as equipo_local_id,
               ev.nombre as equipo_visitante, ev.logo as logo_visitante, ev.id as equipo_visitante_id,
               c.nombre as cancha,
               cat.nombre as categoria, camp.nombre as campeonato,
               f.numero_fecha, cat.id as categoria_id
        FROM partidos p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        JOIN canchas c ON p.cancha_id = c.id
        JOIN fechas f ON p.fecha_id = f.id
        JOIN categorias cat ON f.categoria_id = cat.id
        JOIN campeonatos camp ON cat.campeonato_id = camp.id
        WHERE p.estado = 'en_curso' AND cat.id = ?
        ORDER BY p.cancha_id, p.hora_partido ASC
    ");
    $stmt->execute([$categoria_id]);
} else {
    $stmt = $db->query("
        SELECT p.*, 
               el.nombre as equipo_local, el.logo as logo_local, el.id as equipo_local_id,
               ev.nombre as equipo_visitante, ev.logo as logo_visitante, ev.id as equipo_visitante_id,
               c.nombre as cancha,
               cat.nombre as categoria, camp.nombre as campeonato,
               f.numero_fecha, cat.id as categoria_id
        FROM partidos p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        JOIN canchas c ON p.cancha_id = c.id
        JOIN fechas f ON p.fecha_id = f.id
        JOIN categorias cat ON f.categoria_id = cat.id
        JOIN campeonatos camp ON cat.campeonato_id = camp.id
        WHERE p.estado = 'en_curso'
        ORDER BY p.cancha_id, p.hora_partido ASC
    ");
}
$partidos_vivo = $stmt->fetchAll();

// ==================== ÚLTIMOS RESULTADOS ====================
if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT p.*, 
               el.nombre as equipo_local, el.logo as logo_local,
               ev.nombre as equipo_visitante, ev.logo as logo_visitante,
               c.nombre as cancha,
               cat.nombre as categoria, camp.nombre as campeonato,
               f.numero_fecha
        FROM partidos p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        JOIN canchas c ON p.cancha_id = c.id
        JOIN fechas f ON p.fecha_id = f.id
        JOIN categorias cat ON f.categoria_id = cat.id
        JOIN campeonatos camp ON cat.campeonato_id = camp.id
        WHERE p.estado = 'finalizado' AND cat.id = ?
        ORDER BY p.finalizado_at DESC
        LIMIT 100
    ");
    $stmt->execute([$categoria_id]);
} else {
    $stmt = $db->query("
        SELECT p.*, 
               el.nombre as equipo_local, el.logo as logo_local,
               ev.nombre as equipo_visitante, ev.logo as logo_visitante,
               c.nombre as cancha,
               cat.nombre as categoria, camp.nombre as campeonato,
               f.numero_fecha
        FROM partidos p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        JOIN canchas c ON p.cancha_id = c.id
        JOIN fechas f ON p.fecha_id = f.id
        JOIN categorias cat ON f.categoria_id = cat.id
        JOIN campeonatos camp ON cat.campeonato_id = camp.id
        WHERE p.estado = 'finalizado' 
          AND p.finalizado_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY p.finalizado_at DESC
        LIMIT 50
    ");
}
$ultimos_resultados = $stmt->fetchAll();

// Función para obtener eventos y calcular período por minuto
function obtenerEventosPorPartido($partido_id, $db) {
    $stmt = $db->prepare("
        SELECT e.*, j.apellido_nombre, j.equipo_id, eq.nombre as nombre_equipo
        FROM eventos_partido e
        JOIN jugadores j ON e.jugador_id = j.id
        JOIN equipos eq ON j.equipo_id = eq.id
        WHERE e.partido_id = ?
        ORDER BY e.minuto ASC, e.created_at ASC
    ");
    $stmt->execute([$partido_id]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular período basado en minuto
    // Los eventos se guardan con offset:
    // Primer tiempo: minuto = minuto_periodo (1-30)
    // Segundo tiempo: minuto = minuto_periodo + 30 (31-60)
    foreach ($eventos as &$e) {
        $min = (int)$e['minuto'];
        if ($min >= 1 && $min <= 30) {
            $e['periodo'] = "1°T";
            $e['minuto_display'] = $min; // Minuto del período
        } elseif ($min >= 31 && $min <= 60) {
            $e['periodo'] = "2°T";
            $e['minuto_display'] = $min - 30; // Minuto del período (sin offset)
        } elseif ($min > 60) {
            $e['periodo'] = "ET";
            $e['minuto_display'] = $min;
        } else {
            $e['periodo'] = "";
            $e['minuto_display'] = $min;
        }
    }
    return $eventos;
}

// ==================== ESTADÍSTICAS DEL DÍA ====================
if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(CASE WHEN p.estado = 'en_curso' THEN 1 END) as en_vivo,
            COUNT(CASE WHEN p.estado = 'finalizado' AND DATE(p.finalizado_at) = CURDATE() THEN 1 END) as finalizados_hoy,
            COUNT(CASE WHEN p.estado = 'programado' AND DATE(p.fecha_partido) = CURDATE() THEN 1 END) as programados_hoy,
            COALESCE(SUM(CASE WHEN p.estado = 'finalizado' AND DATE(p.finalizado_at) = CURDATE() THEN p.goles_local + p.goles_visitante END), 0) as goles_hoy
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        WHERE f.categoria_id = ?
    ");
    $stmt->execute([$categoria_id]);
} else {
    $stmt = $db->query("
        SELECT 
            COUNT(CASE WHEN estado = 'en_curso' THEN 1 END) as en_vivo,
            COUNT(CASE WHEN estado = 'finalizado' AND DATE(finalizado_at) = CURDATE() THEN 1 END) as finalizados_hoy,
            COUNT(CASE WHEN estado = 'programado' AND DATE(fecha_partido) = CURDATE() THEN 1 END) as programados_hoy,
            COALESCE(SUM(CASE WHEN estado = 'finalizado' AND DATE(finalizado_at) = CURDATE() THEN goles_local + goles_visitante END), 0) as goles_hoy
        FROM partidos
    ");
}
$stats = $stmt->fetch();

// ==================== ENDPOINTS AJAX ====================
if (!empty($_GET['ajax'])) {
    header('Content-Type: application/json');
    if ($_GET['ajax'] === 'vivo') {
        $filtro = !empty($_GET['categoria']) ? (int)$_GET['categoria'] : null;
        if ($filtro) {
            $stmt = $db->prepare("
                SELECT p.*, 
                       el.nombre as equipo_local, el.logo as logo_local, el.id as equipo_local_id,
                       ev.nombre as equipo_visitante, ev.logo as logo_visitante, ev.id as equipo_visitante_id,
                       c.nombre as cancha,
                       cat.nombre as categoria, camp.nombre as campeonato,
                       f.numero_fecha, cat.id as categoria_id
                FROM partidos p
                JOIN equipos el ON p.equipo_local_id = el.id
                JOIN equipos ev ON p.equipo_visitante_id = ev.id
                JOIN canchas c ON p.cancha_id = c.id
                JOIN fechas f ON p.fecha_id = f.id
                JOIN categorias cat ON f.categoria_id = cat.id
                JOIN campeonatos camp ON cat.campeonato_id = camp.id
                WHERE p.estado = 'en_curso' AND cat.id = ?
                ORDER BY p.cancha_id, p.hora_partido ASC
            ");
            $stmt->execute([$filtro]);
        } else {
            $stmt = $db->query("
                SELECT p.*, 
                       el.nombre as equipo_local, el.logo as logo_local, el.id as equipo_local_id,
                       ev.nombre as equipo_visitante, ev.logo as logo_visitante, ev.id as equipo_visitante_id,
                       c.nombre as cancha,
                       cat.nombre as categoria, camp.nombre as campeonato,
                       f.numero_fecha, cat.id as categoria_id
                FROM partidos p
                JOIN equipos el ON p.equipo_local_id = el.id
                JOIN equipos ev ON p.equipo_visitante_id = ev.id
                JOIN canchas c ON p.cancha_id = c.id
                JOIN fechas f ON p.fecha_id = f.id
                JOIN categorias cat ON f.categoria_id = cat.id
                JOIN campeonatos camp ON cat.campeonato_id = camp.id
                WHERE p.estado = 'en_curso'
                ORDER BY p.cancha_id, p.hora_partido ASC
            ");
        }
        echo json_encode($stmt->fetchAll());
        exit;
    }
    elseif ($_GET['ajax'] === 'eventos' && !empty($_GET['partido_id'])) {
        $eventos = obtenerEventosPorPartido((int)$_GET['partido_id'], $db);
        echo json_encode($eventos);
        exit;
    }
    elseif ($_GET['ajax'] === 'eventos_partido' && !empty($_GET['partido_id'])) {
        $partido_id = (int)$_GET['partido_id'];
        $eventos = obtenerEventosPorPartido($partido_id, $db);
        $stmt = $db->prepare("
            SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante
            FROM partidos p
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            WHERE p.id = ?
        ");
        $stmt->execute([$partido_id]);
        $partido = $stmt->fetch();
        echo json_encode(['partido' => $partido, 'eventos' => $eventos]);
        exit;
    }
    elseif ($_GET['ajax'] === 'update_minutes') {
        $stmt = $db->query("
            SELECT id, minuto_periodo, segundos_transcurridos, tiempo_actual, iniciado_at, 
                   goles_local, goles_visitante
            FROM partidos
            WHERE estado = 'en_curso'
        ");
        $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calcular segundos del período para cada partido (igual que en index.php)
        foreach ($partidos as &$p) {
            $tiempo_actual = $p['tiempo_actual'] ?? '';
            $segundos_totales = (int)($p['segundos_transcurridos'] ?? 0);
            $minuto_periodo = (int)($p['minuto_periodo'] ?? 0);
            
            if ($tiempo_actual === 'primer_tiempo') {
                $p['segundos_periodo'] = $segundos_totales % 60;
            } elseif ($tiempo_actual === 'segundo_tiempo') {
                $p['segundos_periodo'] = $segundos_totales % 60;
            } else {
                $p['segundos_periodo'] = 0;
            }
        }
        unset($p);
        
        echo json_encode($partidos);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultados en Vivo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .match-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .team-logo {
            width: 32px;
            height: 32px;
            object-fit: cover;
            border-radius: 50%;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        .logo-placeholder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            font-weight: bold;
            font-size: 14px;
        }
        .score-display {
            font-size: 1.4rem;
            font-weight: bold;
            color: #212529;
        }
        .match-timer {
            font-size: 0.9rem;
            color: #dc3545;
            font-weight: bold;
        }
        .event-badge {
            display: inline-block;
            margin: 2px 4px;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.85rem;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        .event-gol { color: #198754; font-weight: bold; }
        .event-amarilla { color: #ffc107; }
        .event-roja { color: #dc3545; }
        .stats-card {
            padding: 1rem;
        }
        .stats-icon {
            font-size: 1.5rem;
        }
        .live-indicator::after {
            content: "";
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #dc3545;
            border-radius: 50%;
            margin-left: 8px;
            animation: blink 1.5s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        .eventos-equipo {
            margin-top: 8px;
            padding: 6px 0;
            border-top: 1px solid #eee;
        }
        .eventos-equipo h6 {
            font-size: 0.85rem;
            color: #6c757d;
            margin-bottom: 4px;
        }
        .eventos-lista {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        .match-header {
            padding: 0.75rem 1rem;
            background-color: #f8f9fa;
        }
        .match-body {
            padding: 1rem;
        }
        
        /* Estilos móviles */
        @media (max-width: 768px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            h2, h3 {
                font-size: 1.5rem;
            }
            
            .match-card {
                margin-bottom: 0.75rem;
            }
            
            .team-logo, .logo-placeholder {
                width: 24px;
                height: 24px;
                font-size: 12px;
            }
            
            .score-display {
                font-size: 1.2rem;
            }
            
            .match-timer {
                font-size: 0.8rem;
            }
            
            .match-header {
                padding: 0.5rem 0.75rem;
                font-size: 0.85rem;
            }
            
            .match-body {
                padding: 0.75rem;
            }
            
            .event-badge {
                font-size: 0.75rem;
                padding: 2px 4px;
                margin: 1px 2px;
            }
            
            .col-lg-6, .col-xl-4 {
                padding-left: 5px;
                padding-right: 5px;
                margin-bottom: 10px;
            }
            
            .stats-card {
                padding: 0.75rem;
            }
            
            .stats-icon {
                font-size: 1.2rem;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 0.5rem 0.3rem;
                font-size: 0.8rem;
            }
            
            .table th.d-none-mobile,
            .table td.d-none-mobile {
                display: none;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
            }
        }
        
        @media (max-width: 576px) {
            h2, h3 {
                font-size: 1.25rem;
            }
            
            .team-logo, .logo-placeholder {
                width: 20px;
                height: 20px;
                font-size: 10px;
            }
            
            .score-display {
                font-size: 1rem;
            }
            
            .event-badge {
                font-size: 0.7rem;
            }
            
            .col-md-3 {
                margin-bottom: 0.75rem;
            }
            
            .table th, .table td {
                padding: 0.4rem 0.2rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../include/header.php'; ?>
	    <div class="container my-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-trophy"></i> Resultados en Vivo</h2>
                </div>

                <?php if (empty($categorias)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No hay categorías disponibles.
                    </div>
                <?php else: ?>
                    <!-- Selector de Categoría -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-0">Seleccionar Categoría:</h5>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" onchange="cambiarCategoria(this.value)">
                                        <option value="">Todas las categorías (últimos 7 días)</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $categoria_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['campeonato_nombre'] . ' - ' . $cat['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Estadísticas del Día -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header">
                                    <h4 class="mb-0"><i class="fas fa-chart-pie"></i> Estadísticas de Hoy</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-3 mb-3">
                                            <div class="stats-card">
                                                <div class="stats-icon text-danger"><i class="fas fa-broadcast-tower"></i></div>
                                                <div class="h4 text-danger"><?= $stats['en_vivo'] ?></div>
                                                <div class="text-muted">En Vivo</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="stats-card">
                                                <div class="stats-icon text-success"><i class="fas fa-check-circle"></i></div>
                                                <div class="h4 text-success"><?= $stats['finalizados_hoy'] ?></div>
                                                <div class="text-muted">Finalizados Hoy</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="stats-card">
                                                <div class="stats-icon text-warning"><i class="fas fa-clock"></i></div>
                                                <div class="h4 text-warning"><?= $stats['programados_hoy'] ?></div>
                                                <div class="text-muted">Programados Hoy</div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <div class="stats-card">
                                                <div class="stats-icon text-info"><i class="fas fa-futbol"></i></div>
                                                <div class="h4 text-info"><?= $stats['goles_hoy'] ?></div>
                                                <div class="text-muted">Goles Hoy</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Partidos en Vivo -->
                    <div class="mb-5">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <h3 class="text-danger m-0">
                                <i class="fas fa-broadcast-tower"></i> EN VIVO
                                <span class="live-indicator"></span>
                            </h3>
                            <small class="text-muted">Actualizado en tiempo real</small>
                        </div>
                        <div class="row g-3" id="partidosEnVivo">
                            <?php if (empty($partidos_vivo)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info text-center">
                                        <i class="fas fa-info-circle"></i> No hay partidos en vivo.
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($partidos_vivo as $partido): 
                                    $eventos = obtenerEventosPorPartido($partido['id'], $db);
                                    $eventos_local = [];
                                    $eventos_visitante = [];
                                    foreach ($eventos as $e) {
                                        if ($e['equipo_id'] == $partido['equipo_local_id']) {
                                            $eventos_local[] = $e;
                                        } else {
                                            $eventos_visitante[] = $e;
                                        }
                                    }
                                ?>
                                <div class="col-lg-6 col-xl-4" data-partido-id="<?= $partido['id'] ?>">
                                    <div class="match-card">
                                        <div class="match-header">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <small class="text-muted"><?= htmlspecialchars($partido['categoria']) ?></small>
                                                <small class="text-muted"><?= htmlspecialchars($partido['cancha']) ?></small>
                                            </div>
                                        </div>
                                        <div class="match-body">
                                            <!-- Local -->
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($partido['logo_local']): ?>
                                                        <img src="../uploads/<?= $partido['logo_local'] ?>" class="team-logo me-2" alt="<?= $partido['equipo_local'] ?>">
                                                    <?php else: ?>
                                                        <span class="logo-placeholder me-2"><?= substr($partido['equipo_local'], 0, 1) ?></span>
                                                    <?php endif; ?>
                                                    <span class="fw-bold"><?= htmlspecialchars($partido['equipo_local']) ?></span>
                                                </div>
                                                <span class="badge bg-primary"><?= $partido['goles_local'] ?></span>
                                            </div>
                                            <?php if (!empty($eventos_local)): ?>
                                            <div class="eventos-equipo">
                                                <h6><?= htmlspecialchars($partido['equipo_local']) ?></h6>
                                                <div class="eventos-lista">
                                                    <?php foreach ($eventos_local as $evento): 
                                                        $icono = '';
                                                        if ($evento['tipo_evento'] === 'gol') $icono = '⚽';
                                                        elseif ($evento['tipo_evento'] === 'amarilla') $icono = '🟨';
                                                        elseif ($evento['tipo_evento'] === 'roja') $icono = '🟥';
                                                        // Usar minuto_display si está disponible (ya calculado con offset), sino usar minuto
                                                        $minutoDisplay = isset($evento['minuto_display']) ? $evento['minuto_display'] : $evento['minuto'];
                                                    ?>
                                                    <span class="event-badge event-<?= $evento['tipo_evento'] ?>" title="<?= htmlspecialchars($evento['apellido_nombre']) ?>">
                                                        <?= $icono ?> <?= htmlspecialchars($evento['apellido_nombre']) ?> <?= $minutoDisplay ?>' <?= $evento['periodo'] ?>
                                                    </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>

                                            <!-- Marcador central -->
                                            <div class="text-center my-3">
                                                <div class="score-display"><?= $partido['goles_local'] ?> - <?= $partido['goles_visitante'] ?></div>
                                                <div class="match-timer" id="timer-<?= $partido['id'] ?>" 
                                                     data-partido-id="<?= $partido['id'] ?>"
                                                     data-segundos="<?= $partido['segundos_transcurridos'] ?? 0 ?>"
                                                     data-tiempo="<?= htmlspecialchars($partido['tiempo_actual'] ?? '') ?>">
                                                    <?php 
                                                        if (($partido['tiempo_actual'] ?? '') === 'descanso') {
                                                            echo 'Descanso';
                                                        } elseif (($partido['tiempo_actual'] ?? '') === 'finalizado') {
                                                            echo 'Finalizado';
                                                        } else {
                                                            // Usar minuto_periodo del servidor (calculado correctamente en partido_live.php)
                                                            $minutoPeriodo = (int)($partido['minuto_periodo'] ?? 0);
                                                            $tiempoActual = $partido['tiempo_actual'] ?? '';
                                                            $segundosTotales = (int)($partido['segundos_transcurridos'] ?? 0);
                                                            $secs = $segundosTotales % 60;
                                                            
                                                            // Formato igual que partido_live.php: mostrar MM:SS
                                                            if ($minutoPeriodo <= 30) {
                                                                $texto = str_pad($minutoPeriodo, 2, '0', STR_PAD_LEFT) . ':' . str_pad($secs, 2, '0', STR_PAD_LEFT);
                                                            } else {
                                                                $minutosExtra = $minutoPeriodo - 30;
                                                                $texto = "30'" . ($minutosExtra > 0 ? '+' . $minutosExtra : '') . "'";
                                                            }
                                                            
                                                            // Agregar período
                                                            $periodo = ($tiempoActual === 'primer_tiempo') ? ' 1°T' : ' 2°T';
                                                            echo $texto . $periodo;
                                                        }
                                                    ?>
                                                </div>
                                                <small class="text-muted d-block mt-1">
                                                    <?= ucfirst(str_replace('_', ' ', $partido['tiempo_actual'] ?? '')) ?>
                                                </small>
                                            </div>

                                            <!-- Visitante -->
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($partido['logo_visitante']): ?>
                                                        <img src="../uploads/<?= $partido['logo_visitante'] ?>" class="team-logo me-2" alt="<?= $partido['equipo_visitante'] ?>">
                                                    <?php else: ?>
                                                        <span class="logo-placeholder me-2"><?= substr($partido['equipo_visitante'], 0, 1) ?></span>
                                                    <?php endif; ?>
                                                    <span class="fw-bold"><?= htmlspecialchars($partido['equipo_visitante']) ?></span>
                                                </div>
                                                <span class="badge bg-primary"><?= $partido['goles_visitante'] ?></span>
                                            </div>
                                            <?php if (!empty($eventos_visitante)): ?>
                                            <div class="eventos-equipo">
                                                <h6><?= htmlspecialchars($partido['equipo_visitante']) ?></h6>
                                                <div class="eventos-lista">
                                                    <?php foreach ($eventos_visitante as $evento): 
                                                        $icono = '';
                                                        if ($evento['tipo_evento'] === 'gol') $icono = '⚽';
                                                        elseif ($evento['tipo_evento'] === 'amarilla') $icono = '🟨';
                                                        elseif ($evento['tipo_evento'] === 'roja') $icono = '🟥';
                                                        // Usar minuto_display si está disponible (ya calculado con offset), sino usar minuto
                                                        $minutoDisplay = isset($evento['minuto_display']) ? $evento['minuto_display'] : $evento['minuto'];
                                                    ?>
                                                    <span class="event-badge event-<?= $evento['tipo_evento'] ?>" title="<?= htmlspecialchars($evento['apellido_nombre']) ?>">
                                                        <?= $icono ?> <?= htmlspecialchars($evento['apellido_nombre']) ?> <?= $minutoDisplay ?>' <?= $evento['periodo'] ?>
                                                    </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Últimos Resultados -->
                    <div class="row g-4">
                        <div class="col-12">
                            <div class="card shadow-sm">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-history"></i> 
                                        <?php 
                                        if ($categoria_id) {
                                            echo 'Todos los Partidos - ' . htmlspecialchars($categoria_valida['campeonato_nombre'] . ' - ' . $categoria_valida['nombre']);
                                        } else {
                                            echo 'Últimos 7 Días (Todas las Categorías)';
                                        }
                                        ?>
                                    </h4>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($ultimos_resultados)): ?>
                                        <div class="text-center py-4">
                                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">
                                                <?php if ($categoria_id): ?>No hay resultados en esta categoría<?php else: ?>No hay resultados en los últimos 7 días<?php endif; ?>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>Partido</th>
                                                        <th class="text-center">Resultado</th>
                                                        <th class="text-center d-none-mobile">Fecha</th>
                                                        <th class="text-center hide-xs">Categoría</th>
                                                        <th class="text-center">Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($ultimos_resultados as $r): ?>
                                                    <tr>
                                                        <td>
                                                            <div class="d-flex align-items-center">
                                                                <?php if ($r['logo_local']): ?>
                                                                    <img src="../uploads/<?= $r['logo_local'] ?>" width="24" height="24" class="rounded me-2">
                                                                <?php else: ?>
                                                                    <span class="logo-placeholder me-2" style="width: 24px; height: 24px; font-size: 12px;"><?= substr($r['equipo_local'], 0, 1) ?></span>
                                                                <?php endif; ?>
                                                                <span><?= htmlspecialchars($r['equipo_local']) ?></span>
                                                                <span class="mx-2 text-muted">vs</span>
                                                                <span><?= htmlspecialchars($r['equipo_visitante']) ?></span>
                                                                <?php if ($r['logo_visitante']): ?>
                                                                    <img src="../uploads/<?= $r['logo_visitante'] ?>" width="24" height="24" class="rounded ms-2">
                                                                <?php else: ?>
                                                                    <span class="logo-placeholder ms-2" style="width: 24px; height: 24px; font-size: 12px;"><?= substr($r['equipo_visitante'], 0, 1) ?></span>
                                                                <?php endif; ?>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <span class="badge bg-success"><?= $r['goles_local'] ?> - <?= $r['goles_visitante'] ?></span>
                                                        </td>
                                                        <td class="text-center d-none-mobile">
                                                            <?= date('d/m/Y', strtotime($r['fecha_partido'])) ?><br>
                                                            <small class="text-muted">Fecha <?= $r['numero_fecha'] ?></small>
                                                        </td>
                                                        <td class="text-center hide-xs">
                                                            <span class="badge bg-secondary"><?= htmlspecialchars($r['categoria']) ?></span>
                                                        </td>
                                                        <td class="text-center">
                                                            <button class="btn btn-sm btn-outline-primary" onclick="mostrarModalEventos(<?= $r['id'] ?>)">
                                                                <i class="fas fa-list"></i> <span class="d-none d-sm-inline">Ver Eventos</span>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-futbol"></i> Sistema de Campeonatos</h5>
                    <p class="text-muted">Resultados en tiempo real</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">© 2025 Todos los derechos reservados</p>
                    <small class="text-muted" id="ultimaActualizacion">
                        <i class="fas fa-sync-alt"></i> Actualizado: <?= date('d/m/Y H:i:s') ?>
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
    function cambiarCategoria(categoriaId) {
        if (categoriaId === '') {
            window.location.href = 'resultados.php';
        } else {
            window.location.href = 'resultados.php?categoria=' + categoriaId;
        }
    }

    function mostrarModalEventos(partidoId) {
        fetch(`?ajax=eventos_partido&partido_id=${partidoId}`)
            .then(response => response.json())
            .then(data => {
                const { partido, eventos } = data;
                if (!partido) return;
                let eventosHtml = '<div class="list-group">';
                if (eventos.length === 0) {
                    eventosHtml += '<div class="list-group-item text-center text-muted py-3">No hay eventos</div>';
                } else {
                    eventos.forEach(e => {
                        let icono = '', color = '';
                        if (e.tipo_evento === 'gol') {
                            icono = '⚽';
                            color = 'text-success';
                        } else if (e.tipo_evento === 'amarilla') {
                            icono = '🟨';
                            color = 'text-warning';
                        } else if (e.tipo_evento === 'roja') {
                            icono = '🟥';
                            color = 'text-danger';
                        }
                        // Calcular período en JS también (por coherencia)
                        let periodo = '';
                        const min = parseInt(e.minuto);
                        if (min >= 1 && min <= 45) periodo = "1ºT";
                        else if (min >= 46 && min <= 90) periodo = "2ºT";
                        else if (min > 90) periodo = "ET";

                        eventosHtml += `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="${color}">${icono}</strong>
                                ${e.apellido_nombre} (${e.nombre_equipo})
                            </div>
                            <span class="badge bg-light text-dark">${e.minuto}' ${periodo}</span>
                        </div>`;
                    });
                }
                eventosHtml += '</div>';
                const modalHtml = `
                <div class="modal fade" id="eventosModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title"><i class="fas fa-list"></i> Eventos del Partido</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="text-center mb-3">
                                    <h6>${partido.equipo_local} <span class="text-muted mx-2">vs</span> ${partido.equipo_visitante}</h6>
                                    <div class="h5 text-success">${partido.goles_local} - ${partido.goles_visitante}</div>
                                    <small class="text-muted">Fecha: ${new Date(partido.fecha_partido).toLocaleDateString('es-ES')}</small>
                                </div>
                                ${eventosHtml}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>`;
                const oldModal = document.getElementById('eventosModal');
                if (oldModal) oldModal.remove();
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                const modal = new bootstrap.Modal(document.getElementById('eventosModal'));
                modal.show();
            })
            .catch(err => {
                console.error('Error al cargar eventos:', err);
                alert('No se pudieron cargar los eventos.');
            });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const categoriaFiltro = urlParams.get('categoria');
        const partidosEnVivoContainer = document.getElementById('partidosEnVivo');

        function construirUrlAjax(base) {
            let url = base;
            if (categoriaFiltro) {
                url += (url.includes('?') ? '&' : '?') + 'categoria=' + encodeURIComponent(categoriaFiltro);
            }
            return url;
        }

        function actualizarPartidosEnVivo() {
            const scrollY = window.scrollY;

            fetch(construirUrlAjax('?ajax=vivo'))
                .then(response => response.json())
                .then(partidos => {
                    if (!partidosEnVivoContainer) return;

                    if (partidos.length === 0) {
                        partidosEnVivoContainer.innerHTML = `
                            <div class="col-12">
                                <div class="alert alert-info text-center">
                                    <i class="fas fa-info-circle"></i> No hay partidos en vivo.
                                </div>
                            </div>`;
                        window.scrollTo(0, scrollY);
                        return;
                    }

                    let html = '';
                    partidos.forEach(p => {
                        const logoLocal = p.logo_local ? 
                            `<img src="../uploads/${p.logo_local}" class="team-logo me-2" alt="${p.equipo_local}">` : 
                            `<span class="logo-placeholder me-2">${p.equipo_local.charAt(0)}</span>`;
                        const logoVisitante = p.logo_visitante ? 
                            `<img src="../uploads/${p.logo_visitante}" class="team-logo me-2" alt="${p.equipo_visitante}">` : 
                            `<span class="logo-placeholder me-2">${p.equipo_visitante.charAt(0)}</span>`;

                        const tiempoTexto = (p.tiempo_actual || '').replace(/_/g, ' ');
                        
                        // Calcular tiempo igual que en partido_live.php
                        let tiempoDisplay = '';
                        if (p.tiempo_actual === 'descanso') {
                            tiempoDisplay = 'Descanso';
                        } else if (p.tiempo_actual === 'finalizado') {
                            tiempoDisplay = 'Finalizado';
                        } else {
                            // Usar minuto_periodo del servidor (calculado correctamente en partido_live.php)
                            const minutoPeriodo = parseInt(p.minuto_periodo) || 0;
                            const tiempoActual = p.tiempo_actual || '';
                            const segundosPeriodo = parseInt(p.segundos_periodo) || 0;
                            
                            // Formato igual que partido_live.php: mostrar MM:SS
                            if (minutoPeriodo <= 30) {
                                tiempoDisplay = String(minutoPeriodo).padStart(2, '0') + ':' + String(segundosPeriodo).padStart(2, '0');
                            } else {
                                const minutosExtra = minutoPeriodo - 30;
                                tiempoDisplay = `30'${minutosExtra > 0 ? '+' + minutosExtra : ''}'`;
                            }
                            
                            // Agregar período
                            const periodo = (tiempoActual === 'primer_tiempo') ? ' 1°T' : ' 2°T';
                            tiempoDisplay += periodo;
                        }

                        html += `
                        <div class="col-lg-6 col-xl-4" data-partido-id="${p.id}">
                            <div class="match-card">
                                <div class="match-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">${p.categoria}</small>
                                        <small class="text-muted">${p.cancha}</small>
                                    </div>
                                </div>
                                <div class="match-body">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            ${logoLocal}
                                            <span class="fw-bold">${p.equipo_local}</span>
                                        </div>
                                        <span class="badge bg-primary">${p.goles_local}</span>
                                    </div>
                                    <div class="eventos-equipo" id="eventos-local-${p.id}"></div>

                                    <div class="text-center my-3">
                                        <div class="score-display">${p.goles_local} - ${p.goles_visitante}</div>
                                        <div class="match-timer" id="timer-${p.id}" 
                                             data-partido-id="${p.id}"
                                             data-segundos="${p.segundos_transcurridos || 0}"
                                             data-tiempo="${p.tiempo_actual || ''}">${tiempoDisplay}</div>
                                        <small class="text-muted d-block mt-1">${tiempoTexto}</small>
                                    </div>

                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <div class="d-flex align-items-center">
                                            ${logoVisitante}
                                            <span class="fw-bold">${p.equipo_visitante}</span>
                                        </div>
                                        <span class="badge bg-primary">${p.goles_visitante}</span>
                                    </div>
                                    <div class="eventos-equipo" id="eventos-visitante-${p.id}"></div>
                                </div>
                            </div>
                        </div>`;
                    });

                    partidosEnVivoContainer.innerHTML = html;

                    partidos.forEach(p => {
                        actualizarEventosPartido(p.id);
                    });

                    document.getElementById('ultimaActualizacion').innerHTML = 
                        '<i class="fas fa-sync-alt"></i> Actualizado: ' + new Date().toLocaleString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        });

                    window.scrollTo(0, scrollY);
                })
                .catch(err => console.error('Error al actualizar partidos:', err));
        }

        function actualizarEventosPartido(partidoId) {
            fetch(`?ajax=eventos_partido&partido_id=${partidoId}`)
                .then(res => res.json())
                .then(data => {
                    const { partido, eventos } = data;
                    const contenedorLocal = document.getElementById('eventos-local-' + partidoId);
                    const contenedorVisitante = document.getElementById('eventos-visitante-' + partidoId);
                    if (!contenedorLocal || !contenedorVisitante) return;

                    let localHtml = '', visitanteHtml = '';
                    eventos.forEach(e => {
                        let icono = '', clase = '';
                        if (e.tipo_evento === 'gol') { icono = '⚽'; clase = 'event-gol'; }
                        else if (e.tipo_evento === 'amarilla') { icono = '🟨'; clase = 'event-amarilla'; }
                        else if (e.tipo_evento === 'roja') { icono = '🟥'; clase = 'event-roja'; }

                        // Usar minuto_display y periodo si están disponibles (ya calculados con offset)
                        // Si no están disponibles, calcular desde minuto
                        let minutoDisplay = e.minuto_display !== undefined ? e.minuto_display : e.minuto;
                        let periodo = e.periodo || '';
                        
                        if (!periodo) {
                            // Calcular período desde minuto si no está disponible
                            const min = parseInt(e.minuto);
                            if (min >= 1 && min <= 30) {
                                periodo = "1°T";
                                minutoDisplay = min;
                            } else if (min >= 31 && min <= 60) {
                                periodo = "2°T";
                                minutoDisplay = min - 30;
                            } else if (min > 60) {
                                periodo = "ET";
                                minutoDisplay = min;
                            }
                        }

                        const eventoStr = `${icono} ${e.apellido_nombre} ${minutoDisplay}' ${periodo}`;
                        const eventoHtml = `<span class="event-badge ${clase}" title="${e.apellido_nombre}">${eventoStr}</span>`;

                        if (e.equipo_id == partido.equipo_local_id) {
                            localHtml += eventoHtml;
                        } else {
                            visitanteHtml += eventoHtml;
                        }
                    });

                    if (localHtml) {
                        contenedorLocal.innerHTML = `<h6>${partido.equipo_local}</h6><div class="eventos-lista">${localHtml}</div>`;
                    } else {
                        contenedorLocal.innerHTML = '';
                    }
                    if (visitanteHtml) {
                        contenedorVisitante.innerHTML = `<h6>${partido.equipo_visitante}</h6><div class="eventos-lista">${visitanteHtml}</div>`;
                    } else {
                        contenedorVisitante.innerHTML = '';
                    }
                })
                .catch(err => console.error('Error al actualizar eventos:', err));
        }

        // Función para actualizar solo los relojes (más frecuente)
        function actualizarRelojes() {
            fetch('?ajax=update_minutes')
                .then(response => response.json())
                .then(partidos => {
                    partidos.forEach(partido => {
                        const timerDisplay = document.getElementById(`timer-${partido.id}`);
                        if (timerDisplay) {
                            let texto = '';
                            if (partido.tiempo_actual === 'descanso') {
                                texto = 'Descanso';
                            } else if (partido.tiempo_actual === 'finalizado') {
                                texto = 'Finalizado';
                            } else {
                                // Usar minuto_periodo del servidor (calculado correctamente en partido_live.php)
                                const minutoPeriodo = parseInt(partido.minuto_periodo) || 0;
                                const tiempoActual = partido.tiempo_actual || '';
                                const segundosPeriodo = parseInt(partido.segundos_periodo) || 0;
                                
                                // Formato igual que partido_live.php: mostrar MM:SS
                                if (minutoPeriodo <= 30) {
                                    texto = String(minutoPeriodo).padStart(2, '0') + ':' + String(segundosPeriodo).padStart(2, '0');
                                } else {
                                    const minutosExtra = minutoPeriodo - 30;
                                    texto = `30'${minutosExtra > 0 ? '+' + minutosExtra : ''}'`;
                                }
                                
                                // Agregar período
                                const periodo = (tiempoActual === 'primer_tiempo') ? ' 1°T' : ' 2°T';
                                texto += periodo;
                            }
                            timerDisplay.textContent = texto;
                        }
                    });
                })
                .catch(error => console.error('Error actualizando relojes:', error));
        }

        const hayPartidosVivo = partidosEnVivoContainer?.querySelector('[data-partido-id]');
        if (hayPartidosVivo) {
            setInterval(actualizarPartidosEnVivo, 8000);
            // Actualizar relojes más frecuentemente (cada 3 segundos)
            setInterval(actualizarRelojes, 3000);
        } else {
            setInterval(actualizarPartidosEnVivo, 30000);
        }
    });
    </script>
</body>
</html>