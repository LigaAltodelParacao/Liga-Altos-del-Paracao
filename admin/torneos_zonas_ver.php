<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

$formato_id = $_GET['id'] ?? null;

if (!$formato_id) {
    redirect('torneos_zonas.php');
}

// Obtener información del torneo
$stmt = $db->prepare("
    SELECT 
        cf.*,
        c.nombre as campeonato_nombre,
        cat.nombre as categoria_nombre,
        cat.id as categoria_id
    FROM campeonatos_formato cf
    JOIN campeonatos c ON cf.campeonato_id = c.id
    LEFT JOIN categorias cat ON cat.campeonato_id = c.id
    WHERE cf.id = ?
    LIMIT 1
");
$stmt->execute([$formato_id]);
$torneo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$torneo) {
    redirect('torneos_zonas.php');
}

// Obtener zonas con equipos y estadísticas
$stmt = $db->prepare("
    SELECT 
        z.*,
        (SELECT COUNT(*) FROM equipos_zonas WHERE zona_id = z.id) as total_equipos
    FROM zonas z
    WHERE z.formato_id = ?
    ORDER BY z.orden
");
$stmt->execute([$formato_id]);
$zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener equipos por zona con estadísticas
$equipos_por_zona = [];
foreach ($zonas as $zona) {
    $stmt = $db->prepare("
        SELECT 
            ez.*,
            e.nombre as equipo_nombre,
            e.logo as equipo_logo
        FROM equipos_zonas ez
        JOIN equipos e ON ez.equipo_id = e.id
        WHERE ez.zona_id = ?
        ORDER BY ez.puntos DESC, ez.diferencia_gol DESC, ez.goles_favor DESC
    ");
    $stmt->execute([$zona['id']]);
    $equipos_por_zona[$zona['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener fases eliminatorias
$stmt = $db->prepare("
    SELECT * FROM fases_eliminatorias 
    WHERE formato_id = ? 
    ORDER BY orden
");
$stmt->execute([$formato_id]);
$fases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener partidos eliminatorios
$partidos_eliminatorios = [];
foreach ($fases as $fase) {
    $stmt = $db->prepare("
        SELECT 
            pe.*,
            el.nombre as equipo_local_nombre,
            ev.nombre as equipo_visitante_nombre,
            c.nombre as cancha_nombre
        FROM partidos_eliminatorios pe
        LEFT JOIN equipos el ON pe.equipo_local_id = el.id
        LEFT JOIN equipos ev ON pe.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON pe.cancha_id = c.id
        WHERE pe.fase_id = ?
        ORDER BY pe.numero_llave
    ");
    $stmt->execute([$fase['id']]);
    $partidos_eliminatorios[$fase['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Torneo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .zona-card {
            border-left: 4px solid #28a745;
        }
        .eliminatoria-bracket {
            overflow-x: auto;
        }
        .bracket-match {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            margin: 5px;
            background: white;
            min-width: 200px;
        }
        .bracket-team {
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .bracket-team:last-child {
            border-bottom: none;
        }
        .bracket-team.winner {
            background: #d4edda;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-futbol"></i> Fútbol Manager
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
                <?php include __DIR__ . '/include/sidebar.php'; ?>
            </div>

            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-eye"></i> Detalles del Torneo</h2>
                    <div>
                        <a href="campeonatos_zonas_fixture.php?formato_id=<?= $formato_id ?>" class="btn btn-primary">
                            <i class="fas fa-calendar-alt"></i> Generar Fixture
                        </a>
                        <a href="torneos_zonas.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>

                <!-- Información General -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información General</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Campeonato:</strong> <?= htmlspecialchars($torneo['campeonato_nombre']) ?></p>
                                <p><strong>Categoría:</strong> <?= htmlspecialchars($torneo['categoria_nombre']) ?></p>
                                <p><strong>Cantidad de Zonas:</strong> <?= $torneo['cantidad_zonas'] ?></p>
                                <p><strong>Equipos por Zona:</strong> <?= $torneo['equipos_por_zona'] ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Equipos Clasifican:</strong> <?= $torneo['equipos_clasifican'] ?></p>
                                <p><strong>Tipo Clasificación:</strong> <?= htmlspecialchars($torneo['tipo_clasificacion']) ?></p>
                                <p><strong>Estado:</strong> 
                                    <span class="badge bg-<?= $torneo['activo'] ? 'success' : 'secondary' ?>">
                                        <?= $torneo['activo'] ? 'Activo' : 'Inactivo' ?>
                                    </span>
                                </p>
                                <p><strong>Fases:</strong> 
                                    <?php
                                    $fases_nombres = [];
                                    if ($torneo['tiene_octavos']) $fases_nombres[] = '1/8';
                                    if ($torneo['tiene_cuartos']) $fases_nombres[] = '1/4';
                                    if ($torneo['tiene_semifinal']) $fases_nombres[] = 'Semis';
                                    echo implode(' → ', $fases_nombres) . ' → Final';
                                    ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tablas de Posiciones por Zona -->
                <h3 class="mb-3"><i class="fas fa-layer-group"></i> Fase de Grupos</h3>
                
                <div class="row">
                    <?php foreach ($zonas as $zona): ?>
                        <div class="col-lg-6 mb-4">
                            <div class="card zona-card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><?= htmlspecialchars($zona['nombre']) ?></h5>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($equipos_por_zona[$zona['id']])): ?>
                                        <div class="p-3 text-center text-muted">
                                            <i class="fas fa-info-circle"></i> No hay equipos asignados
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Pos</th>
                                                        <th>Equipo</th>
                                                        <th class="text-center">PJ</th>
                                                        <th class="text-center">Pts</th>
                                                        <th class="text-center">DG</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php 
                                                    $posicion = 1;
                                                    foreach ($equipos_por_zona[$zona['id']] as $equipo): 
                                                    ?>
                                                        <tr>
                                                            <td><?= $posicion++ ?></td>
                                                            <td>
                                                                <?php if ($equipo['equipo_logo']): ?>
                                                                    <img src="../uploads/<?= htmlspecialchars($equipo['equipo_logo']) ?>" 
                                                                         width="20" height="20" class="me-1 rounded">
                                                                <?php endif; ?>
                                                                <?= htmlspecialchars($equipo['equipo_nombre']) ?>
                                                            </td>
                                                            <td class="text-center"><?= $equipo['partidos_jugados'] ?></td>
                                                            <td class="text-center"><strong><?= $equipo['puntos'] ?></strong></td>
                                                            <td class="text-center"><?= $equipo['diferencia_gol'] ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Fases Eliminatorias -->
                <?php if (!empty($fases)): ?>
                    <h3 class="mb-3 mt-4"><i class="fas fa-trophy"></i> Fases Eliminatorias</h3>
                    
                    <?php foreach ($fases as $fase): ?>
                        <div class="card mb-3">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="mb-0">
                                    <?php
                                    $nombre_fase = [
                                        'dieciseisavos' => 'Dieciseisavos de Final',
                                        'octavos' => 'Octavos de Final',
                                        'cuartos' => 'Cuartos de Final',
                                        'semifinal' => 'Semifinales',
                                        'final' => 'Final',
                                        'tercer_puesto' => 'Tercer Puesto'
                                    ];
                                    echo $nombre_fase[$fase['nombre']] ?? $fase['nombre'];
                                    ?>
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($partidos_eliminatorios[$fase['id']])): ?>
                                    <div class="text-center text-muted">
                                        <i class="fas fa-info-circle"></i> Los partidos se generarán automáticamente al finalizar la fase de grupos
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <?php foreach ($partidos_eliminatorios[$fase['id']] as $partido): ?>
                                            <div class="col-md-6 col-lg-4 mb-3">
                                                <div class="bracket-match">
                                                    <div class="text-center mb-2">
                                                        <small class="text-muted">Llave <?= $partido['numero_llave'] ?></small>
                                                        <?php if ($partido['fecha_partido']): ?>
                                                            <br><small><?= formatDate($partido['fecha_partido']) ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="bracket-team <?= $partido['ganador_id'] == $partido['equipo_local_id'] ? 'winner' : '' ?>">
                                                        <?php if ($partido['equipo_local_id']): ?>
                                                            <?= htmlspecialchars($partido['equipo_local_nombre']) ?>
                                                            <?php if (isset($partido['goles_local'])): ?>
                                                                <span class="float-end"><?= $partido['goles_local'] ?></span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <em class="text-muted"><?= htmlspecialchars($partido['origen_local']) ?></em>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="bracket-team <?= $partido['ganador_id'] == $partido['equipo_visitante_id'] ? 'winner' : '' ?>">
                                                        <?php if ($partido['equipo_visitante_id']): ?>
                                                            <?= htmlspecialchars($partido['equipo_visitante_nombre']) ?>
                                                            <?php if (isset($partido['goles_visitante'])): ?>
                                                                <span class="float-end"><?= $partido['goles_visitante'] ?></span>
                                                            <?php endif; ?>
                                                        <?php else: ?>
                                                            <em class="text-muted"><?= htmlspecialchars($partido['origen_visitante']) ?></em>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <div class="text-center mt-2">
                                                        <span class="badge bg-<?= $partido['estado'] === 'finalizado' ? 'success' : ($partido['estado'] === 'programado' ? 'primary' : 'secondary') ?>">
                                                            <?= ucfirst($partido['estado']) ?>
                                                        </span>
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
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
