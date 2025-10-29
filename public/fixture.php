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

// Categoría y fecha seleccionadas
$categoria_id = $_GET['categoria'] ?? null;
$fecha_seleccionada = $_GET['fecha'] ?? null;

// Si no hay categoría y hay categorías disponibles, tomar la primera
if (!$categoria_id && !empty($categorias)) {
    $categoria_id = $categorias[0]['id'];
}

// Fechas disponibles (solo si hay categoría)
$fechas_disponibles = [];
if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT DISTINCT f.numero_fecha, f.fecha_programada
        FROM fechas f
        WHERE f.categoria_id = ?
        ORDER BY f.numero_fecha ASC
    ");
    $stmt->execute([$categoria_id]);
    $fechas_disponibles = $stmt->fetchAll();
}

// Obtener fixture
$fixture_por_fecha = [];
if ($categoria_id) {
    if ($fecha_seleccionada !== null) {
        // Mostrar una fecha específica (con resultados, hora y cancha)
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
            WHERE f.categoria_id = ? AND f.numero_fecha = ?
            ORDER BY p.hora_partido ASC
        ");
        $stmt->execute([$categoria_id, $fecha_seleccionada]);
    } else {
        // Mostrar TODO el fixture SIN resultados, SIN hora, SIN cancha
        $stmt = $db->prepare("
            SELECT 
                f.numero_fecha,
                el.nombre as equipo_local, el.logo as logo_local,
                ev.nombre as equipo_visitante, ev.logo as logo_visitante
            FROM fechas f
            JOIN partidos p ON f.id = p.fecha_id
            LEFT JOIN equipos el ON p.equipo_local_id = el.id
            LEFT JOIN equipos ev ON p.equipo_visitante_id = ev.id
            WHERE f.categoria_id = ?
            ORDER BY f.numero_fecha ASC, p.id ASC
        ");
        $stmt->execute([$categoria_id]);
    }

    $partidos = $stmt->fetchAll();

    foreach ($partidos as $partido) {
        $fecha_key = $partido['numero_fecha'];
        if (!isset($fixture_por_fecha[$fecha_key])) {
            $fixture_por_fecha[$fecha_key] = [
                'numero_fecha' => $partido['numero_fecha'],
                'fecha_programada' => $partido['fecha_programada'] ?? null,
                'partidos' => []
            ];
        }
        $fixture_por_fecha[$fecha_key]['partidos'][] = $partido;
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
                            <div class="row g-3">
                                <div class="col-md-6">
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
                                <div class="col-md-6">
                                    <label class="form-label">Fecha</label>
                                    <select class="form-select" onchange="cambiarFecha(this.value)" <?php echo !$categoria_id ? 'disabled' : ''; ?>>
                                        <option value="">Mostrar todo el fixture</option>
                                        <?php foreach ($fechas_disponibles as $f): ?>
                                            <option value="<?php echo $f['numero_fecha']; ?>" <?php echo ($fecha_seleccionada == $f['numero_fecha']) ? 'selected' : ''; ?>>
                                                Fecha <?php echo $f['numero_fecha']; ?> (<?php echo formatDate($f['fecha_programada']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($categoria_actual): ?>
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h4 class="mb-0">
                                    <i class="fas fa-calendar-alt"></i> 
                                    <?php echo htmlspecialchars($categoria_actual['campeonato_nombre'] . ' - ' . $categoria_actual['nombre']); ?>
                                    <?php if ($fecha_seleccionada): ?>
                                        - Fecha <?php echo $fecha_seleccionada; ?>
                                    <?php else: ?>
                                        - Fixture completo
                                    <?php endif; ?>
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
                                                <?php if ($fecha_seleccionada): ?>
                                                    <small class="text-muted ms-2">(<?php echo formatDate($fecha_data['fecha_programada']); ?>)</small>
                                                <?php endif; ?>
                                            </h5>
                                            <div class="row g-3">
                                                <?php foreach ($fecha_data['partidos'] as $partido): ?>
                                                    <div class="col-lg-6 col-xl-4">
                                                        <div class="fixture-match">
                                                            <?php if (!$fecha_seleccionada): ?>
                                                                <!-- Vista resumida: solo equipos -->
                                                                <div class="fixture-team">
                                                                    <?php if (!empty($partido['logo_local'])): ?>
                                                                        <img src="../uploads/<?php echo $partido['logo_local']; ?>" alt="">
                                                                    <?php endif; ?>
                                                                    <?php echo htmlspecialchars($partido['equipo_local']); ?>
                                                                </div>
                                                                <div class="fixture-vs">VS</div>
                                                                <div class="fixture-team">
                                                                    <?php if (!empty($partido['logo_visitante'])): ?>
                                                                        <img src="../uploads/<?php echo $partido['logo_visitante']; ?>" alt="">
                                                                    <?php endif; ?>
                                                                    <?php echo htmlspecialchars($partido['equipo_visitante']); ?>
                                                                </div>
                                                            <?php else: ?>
                                                                <!-- Vista detallada: con hora, cancha y resultado -->
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
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
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
                    <p class="text-muted">Fixture original del campeonato</p>
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

        function cambiarFecha(fechaNum) {
            const url = new URL(window.location);
            if (fechaNum) {
                url.searchParams.set('fecha', fechaNum);
            } else {
                url.searchParams.delete('fecha');
            }
            window.location.href = url.toString();
        }
    </script>
</body>
</html>