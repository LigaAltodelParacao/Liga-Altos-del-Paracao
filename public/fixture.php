<?php
require_once '../config.php';

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
$categoria_id = $_GET['categoria'] ?? null;
if (!$categoria_id && !empty($categorias)) {
    $categoria_id = $categorias[0]['id'];
}

// *** DETECTAR SI ES TORNEO CON ZONAS ***
$es_torneo_zonas = false;
$formato_zonas = null;
$zonas = [];
$fases_eliminatorias = [];

if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT cf.* 
        FROM campeonatos_formato cf
        JOIN categorias cat ON cf.campeonato_id = cat.campeonato_id
        WHERE cat.id = ? AND cf.activo = 1
        LIMIT 1
    ");
    $stmt->execute([$categoria_id]);
    $formato_zonas = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($formato_zonas) {
        $es_torneo_zonas = true;
        
        // Obtener zonas
        $stmt = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
        $stmt->execute([$formato_zonas['id']]);
        $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener fases eliminatorias
        $stmt = $db->prepare("SELECT * FROM fases_eliminatorias WHERE formato_id = ? ORDER BY orden");
        $stmt->execute([$formato_zonas['id']]);
        $fases_eliminatorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Categoría actual
$categoria_actual = null;
foreach ($categorias as $cat) {
    if ($cat['id'] == $categoria_id) {
        $categoria_actual = $cat;
        break;
    }
}

// *** FIXTURE PARA TORNEOS CON ZONAS ***
$partidos_zonas = [];
$partidos_eliminatorios_por_fase = [];

if ($es_torneo_zonas) {
    // Obtener partidos de zona
    foreach ($zonas as $zona) {
        $stmt = $db->prepare("
            SELECT 
                pz.*,
                el.nombre as equipo_local, el.logo as logo_local,
                ev.nombre as equipo_visitante, ev.logo as logo_visitante,
                c.nombre as cancha
            FROM partidos_zona pz
            JOIN equipos el ON pz.equipo_local_id = el.id
            JOIN equipos ev ON pz.equipo_visitante_id = ev.id
            LEFT JOIN canchas c ON pz.cancha_id = c.id
            WHERE pz.zona_id = ?
            ORDER BY pz.fecha_numero, pz.fecha_partido, pz.hora_partido
        ");
        $stmt->execute([$zona['id']]);
        $partidos_zonas[$zona['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener partidos eliminatorios
    foreach ($fases_eliminatorias as $fase) {
        $stmt = $db->prepare("
            SELECT 
                pe.*,
                el.nombre as equipo_local,
                ev.nombre as equipo_visitante,
                c.nombre as cancha
            FROM partidos_eliminatorios pe
            LEFT JOIN equipos el ON pe.equipo_local_id = el.id
            LEFT JOIN equipos ev ON pe.equipo_visitante_id = ev.id
            LEFT JOIN canchas c ON pe.cancha_id = c.id
            WHERE pe.fase_id = ?
            ORDER BY pe.numero_llave
        ");
        $stmt->execute([$fase['id']]);
        $partidos_eliminatorios_por_fase[$fase['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// *** FIXTURE PARA TORNEOS NORMALES ***
$fixture_por_fecha = [];
if (!$es_torneo_zonas && $categoria_id) {
    $stmt = $db->prepare("
        SELECT 
            f.numero_fecha,
            f.fecha_programada,
            p.id as partido_id,
            p.fecha_partido,
            p.hora_partido,
            p.goles_local,
            p.goles_visitante,
            p.estado,
            el.nombre as equipo_local, el.logo as logo_local,
            ev.nombre as equipo_visitante, ev.logo as logo_visitante,
            c.nombre as cancha
        FROM fechas f
        JOIN partidos p ON f.id = p.fecha_id
        LEFT JOIN equipos el ON p.equipo_local_id = el.id
        LEFT JOIN equipos ev ON p.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        WHERE f.categoria_id = ?
        ORDER BY f.numero_fecha ASC, p.hora_partido ASC
    ");
    $stmt->execute([$categoria_id]);
    $partidos = $stmt->fetchAll();

    foreach ($partidos as $partido) {
        $fecha_key = $partido['numero_fecha'];
        if (!isset($fixture_por_fecha[$fecha_key])) {
            $fixture_por_fecha[$fecha_key] = [
                'numero_fecha' => $partido['numero_fecha'],
                'fecha_programada' => $partido['fecha_programada'],
                'partidos' => []
            ];
        }
        $fixture_por_fecha[$fecha_key]['partidos'][] = $partido;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixture Completo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .fixture-match {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            background: #fff;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
        }
        .fixture-vs {
            font-weight: bold;
            margin: 0.5rem 0;
            color: #6c757d;
        }
        .fixture-team {
            font-size: 0.95rem;
        }
        .fixture-team img {
            width: 30px;
            height: 30px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 4px;
        }
        .fixture-result {
            font-weight: bold;
            font-size: 1.1rem;
            margin: 0.4rem 0;
        }
        .badge-finalizado {
            background: #28a745;
            color: white;
        }
        .badge-programado {
            background: #6c757d;
            color: white;
        }
        .eliminatoria-match {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 10px;
            background: white;
            margin-bottom: 10px;
        }
        .eliminatoria-team {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .eliminatoria-team:last-child {
            border-bottom: none;
        }
        .eliminatoria-team.ganador {
            background: #d4edda;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-futbol"></i> Fútbol Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="resultados.php">Resultados</a></li>
                    <li class="nav-item"><a class="nav-link" href="tablas.php">Posiciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="goleadores.php">Goleadores</a></li>
                    <li class="nav-item"><a class="nav-link active" href="fixture.php">Fixture</a></li>
                    <li class="nav-item"><a class="nav-link" href="sanciones.php">Sanciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="historial_equipos.php">Equipos</a></li>
                    <li class="nav-item"><a class="nav-link" href="fairplay.php">Fairplay</a></li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Panel Admin</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Ingresar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-week"></i> Fixture Completo</h2>
                </div>

                <?php if (empty($categorias)): ?>
                    <div class="alert alert-info"><i class="fas fa-info-circle"></i> No hay categorías disponibles.</div>
                <?php else: ?>
                    <!-- Filtros -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <label class="form-label">Categoría</label>
                            <select class="form-select" onchange="cambiarCategoria(this.value)">
                                <option value="">Seleccionar categoría</option>
                                <?php foreach ($categorias as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $categoria_id ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['campeonato_nombre'] . ' - ' . $cat['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <?php if ($categoria_actual): ?>
                        <?php if ($es_torneo_zonas): ?>
                            <!-- *** FIXTURE DE TORNEO CON ZONAS *** -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Este es un torneo con fase de grupos por zonas y eliminatorias
                            </div>

                            <!-- Fase de Grupos -->
                            <h3 class="mb-3"><i class="fas fa-layer-group"></i> Fase de Grupos</h3>
                            
                            <!-- Tabs de Zonas -->
                            <ul class="nav nav-tabs mb-3" id="zonasTab" role="tablist">
                                <?php foreach ($zonas as $index => $zona): ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" 
                                                id="zona-<?= $zona['id'] ?>-tab" 
                                                data-bs-toggle="tab" 
                                                data-bs-target="#zona-<?= $zona['id'] ?>" 
                                                type="button">
                                            <?= htmlspecialchars($zona['nombre']) ?>
                                        </button>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <!-- Contenido de Tabs con Fixture por Zona -->
                            <div class="tab-content mb-5" id="zonasTabContent">
                                <?php foreach ($zonas as $index => $zona): ?>
                                    <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" 
                                         id="zona-<?= $zona['id'] ?>" 
                                         role="tabpanel">
                                        <div class="card">
                                            <div class="card-header bg-success text-white">
                                                <h5 class="mb-0"><?= htmlspecialchars($zona['nombre']) ?></h5>
                                            </div>
                                            <div class="card-body">
                                                <?php if (empty($partidos_zonas[$zona['id']])): ?>
                                                    <div class="text-center text-muted py-4">
                                                        <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                                        <p>No hay partidos programados en esta zona</p>
                                                    </div>
                                                <?php else: ?>
                                                    <?php
                                                    // Agrupar por fecha
                                                    $partidos_por_fecha = [];
                                                    foreach ($partidos_zonas[$zona['id']] as $p) {
                                                        $fecha_key = $p['fecha_numero'];
                                                        if (!isset($partidos_por_fecha[$fecha_key])) {
                                                            $partidos_por_fecha[$fecha_key] = [];
                                                        }
                                                        $partidos_por_fecha[$fecha_key][] = $p;
                                                    }
                                                    ?>
                                                    
                                                    <?php foreach ($partidos_por_fecha as $fecha_num => $partidos_fecha): ?>
                                                        <h6 class="mt-3 mb-2">Fecha <?= $fecha_num ?></h6>
                                                        <div class="row g-3">
                                                            <?php foreach ($partidos_fecha as $partido): ?>
                                                                <div class="col-md-6 col-lg-4">
                                                                    <div class="fixture-match">
                                                                        <?php if ($partido['fecha_partido']): ?>
                                                                            <div class="text-muted small mb-1">
                                                                                <i class="fas fa-calendar"></i> <?= formatDate($partido['fecha_partido']) ?>
                                                                                <?php if ($partido['hora_partido']): ?>
                                                                                    - <?= date('H:i', strtotime($partido['hora_partido'])) ?>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        
                                                                        <div class="fixture-team">
                                                                            <?php if ($partido['logo_local']): ?>
                                                                                <img src="../uploads/<?= $partido['logo_local'] ?>" alt="">
                                                                            <?php endif; ?>
                                                                            <?= htmlspecialchars($partido['equipo_local']) ?>
                                                                        </div>
                                                                        
                                                                        <?php if ($partido['estado'] == 'finalizado'): ?>
                                                                            <div class="fixture-result">
                                                                                <?= $partido['goles_local'] ?> - <?= $partido['goles_visitante'] ?>
                                                                            </div>
                                                                        <?php else: ?>
                                                                            <div class="fixture-vs">VS</div>
                                                                        <?php endif; ?>
                                                                        
                                                                        <div class="fixture-team">
                                                                            <?php if ($partido['logo_visitante']): ?>
                                                                                <img src="../uploads/<?= $partido['logo_visitante'] ?>" alt="">
                                                                            <?php endif; ?>
                                                                            <?= htmlspecialchars($partido['equipo_visitante']) ?>
                                                                        </div>
                                                                        
                                                                        <?php if ($partido['cancha']): ?>
                                                                            <div class="text-muted small mt-1">
                                                                                <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($partido['cancha']) ?>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <!-- Fases Eliminatorias -->
                            <?php if (!empty($fases_eliminatorias)): ?>
                                <h3 class="mb-3 mt-5"><i class="fas fa-trophy"></i> Fases Eliminatorias</h3>
                                
                                <?php foreach ($fases_eliminatorias as $fase): ?>
                                    <div class="card mb-3">
                                        <div class="card-header bg-warning text-dark">
                                            <h5 class="mb-0">
                                                <?php
                                                $nombres_fases = [
                                                    'dieciseisavos' => 'Dieciseisavos de Final',
                                                    'octavos' => 'Octavos de Final',
                                                    'cuartos' => 'Cuartos de Final',
                                                    'semifinal' => 'Semifinales',
                                                    'final' => 'Final',
                                                    'tercer_puesto' => 'Tercer Puesto'
                                                ];
                                                echo $nombres_fases[$fase['nombre']] ?? ucfirst($fase['nombre']);
                                                ?>
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($partidos_eliminatorios_por_fase[$fase['id']])): ?>
                                                <div class="text-center text-muted">
                                                    <i class="fas fa-hourglass-half"></i> Los partidos se definirán al finalizar la fase anterior
                                                </div>
                                            <?php else: ?>
                                                <div class="row">
                                                    <?php foreach ($partidos_eliminatorios_por_fase[$fase['id']] as $partido): ?>
                                                        <div class="col-md-6 col-lg-4 mb-3">
                                                            <div class="eliminatoria-match">
                                                                <div class="text-center mb-2">
                                                                    <strong>Llave <?= $partido['numero_llave'] ?></strong>
                                                                    <?php if ($partido['fecha_partido']): ?>
                                                                        <br><small class="text-muted"><?= formatDate($partido['fecha_partido']) ?></small>
                                                                    <?php endif; ?>
                                                                </div>
                                                                
                                                                <div class="eliminatoria-team <?= $partido['ganador_id'] == $partido['equipo_local_id'] ? 'ganador' : '' ?>">
                                                                    <?php if ($partido['equipo_local']): ?>
                                                                        <?= htmlspecialchars($partido['equipo_local']) ?>
                                                                        <?php if (isset($partido['goles_local'])): ?>
                                                                            <span class="float-end"><?= $partido['goles_local'] ?></span>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <em class="text-muted"><?= htmlspecialchars($partido['origen_local']) ?></em>
                                                                    <?php endif; ?>
                                                                </div>
                                                                
                                                                <div class="eliminatoria-team <?= $partido['ganador_id'] == $partido['equipo_visitante_id'] ? 'ganador' : '' ?>">
                                                                    <?php if ($partido['equipo_visitante']): ?>
                                                                        <?= htmlspecialchars($partido['equipo_visitante']) ?>
                                                                        <?php if (isset($partido['goles_visitante'])): ?>
                                                                            <span class="float-end"><?= $partido['goles_visitante'] ?></span>
                                                                        <?php endif; ?>
                                                                    <?php else: ?>
                                                                        <em class="text-muted"><?= htmlspecialchars($partido['origen_visitante']) ?></em>
                                                                    <?php endif; ?>
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

                        <?php else: ?>
                            <!-- *** FIXTURE DE TORNEO NORMAL *** -->
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-calendar-alt"></i> 
                                        <?php echo htmlspecialchars($categoria_actual['campeonato_nombre'] . ' - ' . $categoria_actual['nombre']); ?>
                                    </h4>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($fixture_por_fecha)): ?>
                                        <div class="text-center py-5">
                                            <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                            <h4 class="text-muted">No hay fixture generado</h4>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($fixture_por_fecha as $fecha_num => $fecha_data): ?>
                                            <div class="mb-5">
                                                <h5 class="mb-3">
                                                    <i class="fas fa-calendar-day"></i> Fecha <?php echo $fecha_data['numero_fecha']; ?>
                                                    <small class="text-muted ms-2">(<?php echo formatDate($fecha_data['fecha_programada']); ?>)</small>
                                                </h5>
                                                <div class="row g-3">
                                                    <?php foreach ($fecha_data['partidos'] as $partido): ?>
                                                        <div class="col-lg-6 col-xl-4">
                                                            <div class="fixture-match">
                                                                <div class="text-muted small mb-1">
                                                                    <?php if (!empty($partido['hora_partido'])): ?>
                                                                        <i class="fas fa-clock"></i> <?php echo date('H:i', strtotime($partido['hora_partido'])); ?>
                                                                    <?php endif; ?>
                                                                    <?php if (!empty($partido['cancha'])): ?>
                                                                        &nbsp;•&nbsp; <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($partido['cancha']); ?>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="fixture-team">
                                                                    <?php if (!empty($partido['logo_local'])): ?>
                                                                        <img src="../uploads/<?php echo $partido['logo_local']; ?>" alt="">
                                                                    <?php endif; ?>
                                                                    <?php echo htmlspecialchars($partido['equipo_local']); ?>
                                                                </div>
                                                                <?php if (isset($partido['estado']) && $partido['estado'] == 'finalizado'): ?>
                                                                    <div class="fixture-result"><?php echo $partido['goles_local']; ?> - <?php echo $partido['goles_visitante']; ?></div>
                                                                <?php else: ?>
                                                                    <div class="fixture-vs">VS</div>
                                                                <?php endif; ?>
                                                                <div class="fixture-team">
                                                                    <?php if (!empty($partido['logo_visitante'])): ?>
                                                                        <img src="../uploads/<?php echo $partido['logo_visitante']; ?>" alt="">
                                                                    <?php endif; ?>
                                                                    <?php echo htmlspecialchars($partido['equipo_visitante']); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">Selecciona una categoría para ver el fixture.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-futbol"></i> Sistema de Campeonatos</h5>
                    <p class="text-muted">Fixture completo del campeonato</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">© 2025 Todos los derechos reservados</p>
                    <small class="text-muted">Actualizado: <?php echo date('d/m/Y H:i'); ?></small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function cambiarCategoria(catId) {
            if (catId) {
                window.location.href = 'fixture.php?categoria=' + catId;
            } else {
                window.location.href = 'fixture.php';
            }
        }
    </script>
</body>
</html>
