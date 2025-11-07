<?php
require_once '../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$jugador_id = $_GET['id'] ?? 0;

// Obtener datos del jugador
$stmt = $db->prepare("
    SELECT j.*, e.nombre as equipo_actual, c.nombre as categoria_actual, camp.nombre as campeonato_actual, camp.id as campeonato_actual_id
    FROM jugadores j
    LEFT JOIN equipos e ON j.equipo_id = e.id
    LEFT JOIN categorias c ON e.categoria_id = c.id
    LEFT JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE j.id = ?
");
$stmt->execute([$jugador_id]);
$jugador = $stmt->fetch();

if (!$jugador) {
    redirect('jugadores.php');
}

// Obtener historial completo del jugador
$stmt = $db->prepare("
    SELECT 
        jeh.*,
        jeh.equipo_id,
        e.nombre as equipo_nombre,
        e.logo as equipo_logo,
        c.nombre as categoria_nombre,
        camp.id as campeonato_id,
        camp.nombre as campeonato_nombre,
        camp.fecha_inicio as campeonato_fecha_inicio,
        camp.fecha_fin as campeonato_fecha_fin
    FROM jugadores_equipos_historial jeh
    JOIN equipos e ON jeh.equipo_id = e.id
    JOIN categorias c ON e.categoria_id = c.id
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE jeh.jugador_dni = ?
    ORDER BY jeh.fecha_inicio DESC, camp.fecha_inicio DESC
");
$stmt->execute([$jugador['dni']]);
$historial = $stmt->fetchAll();

// Calcular totales
$totales = [
    'campeonatos' => count($historial),
    'equipos' => count(array_unique(array_column($historial, 'equipo_id'))),
    'partidos' => array_sum(array_column($historial, 'partidos_jugados')),
    'goles' => array_sum(array_column($historial, 'goles')),
    'amarillas' => array_sum(array_column($historial, 'amarillas')),
    'rojas' => array_sum(array_column($historial, 'rojas'))
];

function calculateAge($birthDate) {
    return date_diff(date_create($birthDate), date_create('today'))->y;
}

// Filtros para estadísticas destacadas y navegación
$campFiltro = isset($_GET['camp_id']) ? (int)$_GET['camp_id'] : null;
$teamFiltro = isset($_GET['team_id']) ? (int)$_GET['team_id'] : null;

// Determinar torneo por defecto
$campActualId = $jugador['campeonato_actual_id'] ?? null;
if (!$campFiltro && !$teamFiltro) {
    if ($campActualId) {
        $campFiltro = (int)$campActualId;
    } elseif (!empty($historial)) {
        $campFiltro = (int)$historial[0]['campeonato_id'];
    }
}

// Subset y etiqueta
$subset = $historial;
$scopeLabel = 'Historial completo';
if ($campFiltro) {
    $subset = array_values(array_filter($historial, fn($r) => (int)$r['campeonato_id'] === (int)$campFiltro));
    foreach ($historial as $r) { if ((int)$r['campeonato_id'] === (int)$campFiltro) { $scopeLabel = 'Campeonato: ' . $r['campeonato_nombre']; break; } }
}
if ($teamFiltro) {
    $subset = array_values(array_filter($historial, fn($r) => (int)$r['equipo_id'] === (int)$teamFiltro));
    foreach ($historial as $r) { if ((int)$r['equipo_id'] === (int)$teamFiltro) { $scopeLabel = 'Equipo: ' . $r['equipo_nombre']; break; } }
}

$destacadas = [
    'partidos' => array_sum(array_column($subset, 'partidos_jugados')),
    'goles' => array_sum(array_column($subset, 'goles')),
    'amarillas' => array_sum(array_column($subset, 'amarillas')),
    'rojas' => array_sum(array_column($subset, 'rojas'))
];

// Listas navegables
$torneosUnicos = [];
$equiposUnicos = [];
foreach ($historial as $r) {
    $torneosUnicos[$r['campeonato_id']] = $r['campeonato_nombre'];
    $equiposUnicos[$r['equipo_id']] = $r['equipo_nombre'];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de <?php echo htmlspecialchars($jugador['apellido_nombre']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .player-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 2rem;
            border-left: 2px solid #e9ecef;
        }
        .timeline-item:last-child {
            border-left: none;
            padding-bottom: 0;
        }
        .timeline-marker {
            position: absolute;
            left: -8px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #e9ecef;
        }
        .team-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 5px;
            background: white;
            padding: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-futbol"></i> Fútbol Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="jugadores.php">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Cabecera del jugador -->
        <div class="player-header">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <?php if ($jugador['foto']): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($jugador['foto']); ?>" 
                             alt="Foto" class="rounded-circle" 
                             style="width: 120px; height: 120px; object-fit: cover; border: 4px solid white;">
                    <?php else: ?>
                        <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 120px; height: 120px;">
                            <i class="fas fa-user fa-4x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-10">
                    <h1 class="mb-2"><?php echo htmlspecialchars($jugador['apellido_nombre']); ?></h1>
                    <div class="row">
                        <div class="col-md-3">
                            <p class="mb-1"><i class="fas fa-id-card"></i> DNI: <?php echo htmlspecialchars($jugador['dni']); ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><i class="fas fa-birthday-cake"></i> Edad: <?php echo calculateAge($jugador['fecha_nacimiento']); ?> años</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1">
                                <i class="fas fa-shield-alt"></i> 
                                <strong>Equipo Actual:</strong> 
                                <?php if ($jugador['equipo_actual']): ?>
                                    <?php echo htmlspecialchars($jugador['campeonato_actual'] . ' - ' . $jugador['categoria_actual'] . ' - ' . $jugador['equipo_actual']); ?>
                                <?php else: ?>
                                    Sin equipo asignado
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas del torneo/equipo seleccionado -->
        <div class="card mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-chart-line"></i> Estadísticas destacadas</h4>
                <small class="text-muted"><?php echo htmlspecialchars($scopeLabel); ?></small>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $destacadas['partidos']; ?></div>
                            <div class="stat-label">Partidos</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-success"><?php echo $destacadas['goles']; ?></div>
                            <div class="stat-label">Goles</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-warning"><?php echo $destacadas['amarillas']; ?></div>
                            <div class="stat-label">Amarillas</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="stat-card">
                            <div class="stat-number text-danger"><?php echo $destacadas['rojas']; ?></div>
                            <div class="stat-label">Rojas</div>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <strong class="me-2"><i class="fas fa-trophy"></i> Torneos:</strong>
                            <?php foreach ($torneosUnicos as $cid => $cname): ?>
                                <a class="badge bg-light text-dark text-decoration-none" href="?id=<?php echo (int)$jugador_id; ?>&camp_id=<?php echo (int)$cid; ?>">
                                    <?php echo htmlspecialchars($cname); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center flex-wrap gap-2">
                            <strong class="me-2"><i class="fas fa-users"></i> Equipos:</strong>
                            <?php foreach ($equiposUnicos as $eid => $ename): ?>
                                <a class="badge bg-light text-dark text-decoration-none" href="?id=<?php echo (int)$jugador_id; ?>&team_id=<?php echo (int)$eid; ?>">
                                    <?php echo htmlspecialchars($ename); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas totales (toda la liga) -->
        <div class="row g-4 mb-4">
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totales['campeonatos']; ?></div>
                    <div class="stat-label">Campeonatos</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totales['equipos']; ?></div>
                    <div class="stat-label">Equipos</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totales['partidos']; ?></div>
                    <div class="stat-label">Partidos</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number text-success"><?php echo $totales['goles']; ?></div>
                    <div class="stat-label">Goles</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number text-warning"><?php echo $totales['amarillas']; ?></div>
                    <div class="stat-label">Amarillas</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number text-danger"><?php echo $totales['rojas']; ?></div>
                    <div class="stat-label">Rojas</div>
                </div>
            </div>
        </div>

        <!-- Historial por campeonatos -->
        <div class="card">
            <div class="card-header bg-white">
                <h4 class="mb-0"><i class="fas fa-history"></i> Historial Completo</h4>
            </div>
            <div class="card-body">
                <?php if (empty($historial)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay historial registrado</h5>
                        <p class="text-muted">Este jugador aún no tiene datos en ningún campeonato</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($historial as $registro): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-1 text-center">
                                                <?php if ($registro['equipo_logo']): ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($registro['equipo_logo']); ?>" 
                                                         alt="Logo" class="team-logo">
                                                <?php else: ?>
                                                    <div class="team-logo d-flex align-items-center justify-content-center bg-light">
                                                        <i class="fas fa-shield-alt text-secondary"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-5">
                                                <h5 class="mb-1">
                                                    <a href="?id=<?php echo (int)$jugador_id; ?>&team_id=<?php echo (int)$registro['equipo_id']; ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($registro['equipo_nombre']); ?>
                                                    </a>
                                                </h5>
                                                <p class="text-muted mb-0">
                                                    <small>
                                                        <i class="fas fa-trophy"></i>
                                                        <a href="?id=<?php echo (int)$jugador_id; ?>&camp_id=<?php echo (int)$registro['campeonato_id']; ?>" class="text-decoration-none">
                                                            <?php echo htmlspecialchars($registro['campeonato_nombre']); ?>
                                                        </a>
                                                        <br>
                                                        <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($registro['categoria_nombre']); ?>
                                                    </small>
                                                </p>
                                            </div>
                                            <div class="col-md-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar-alt"></i> 
                                                    <?php 
                                                    echo date('d/m/Y', strtotime($registro['fecha_inicio']));
                                                    if ($registro['fecha_fin']) {
                                                        echo ' - ' . date('d/m/Y', strtotime($registro['fecha_fin']));
                                                    } else {
                                                        echo ' - <span class="badge bg-success">Actual</span>';
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="row g-2">
                                                    <div class="col-3 text-center">
                                                        <div class="badge bg-primary w-100">
                                                            <div style="font-size: 1.2rem;"><?php echo $registro['partidos_jugados']; ?></div>
                                                            <div style="font-size: 0.7rem;">PJ</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3 text-center">
                                                        <div class="badge bg-success w-100">
                                                            <div style="font-size: 1.2rem;"><?php echo $registro['goles']; ?></div>
                                                            <div style="font-size: 0.7rem;">Goles</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3 text-center">
                                                        <div class="badge bg-warning w-100">
                                                            <div style="font-size: 1.2rem;"><?php echo $registro['amarillas']; ?></div>
                                                            <div style="font-size: 0.7rem;">TA</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3 text-center">
                                                        <div class="badge bg-danger w-100">
                                                            <div style="font-size: 1.2rem;"><?php echo $registro['rojas']; ?></div>
                                                            <div style="font-size: 0.7rem;">TR</div>
                                                        </div>
                                                    </div>
                                                </div>
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
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>