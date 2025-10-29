<?php
require_once '../config.php';

$error = '';
$message = '';

$db = Database::getInstance()->getConnection();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'crear_sancion') {
        try {
            $jugador_id = (int)$_POST['jugador_id'];
            $tipo = $_POST['tipo'];
            $partidos = max(1, (int)$_POST['partidos_suspension']);
            $descripcion = trim($_POST['descripcion'] ?? '');

            $stmt = $db->prepare("
                INSERT INTO sanciones (jugador_id, tipo, partidos_suspension, descripcion, activa, fecha_sancion)
                VALUES (?, ?, ?, ?, 1, CURDATE())
            ");
            $stmt->execute([$jugador_id, $tipo, $partidos, $descripcion]);
            $message = 'Sanción creada exitosamente. Se cumplirá automáticamente cuando el equipo juegue.';
        } catch (Exception $e) {
            $error = 'Error al crear sanción: ' . $e->getMessage();
        }
    }

    if ($action == 'editar_sancion') {
        try {
            $sancion_id = (int)$_POST['sancion_id'];
            $partidos = max(1, (int)$_POST['partidos_suspension']);
            $partidos_cumplidos = min($partidos, max(0, (int)$_POST['partidos_cumplidos']));
            $descripcion = trim($_POST['descripcion'] ?? '');
            $activa = isset($_POST['activa']) ? 1 : 0;

            $stmt = $db->prepare("
                UPDATE sanciones 
                SET partidos_suspension = ?, partidos_cumplidos = ?, descripcion = ?, activa = ?
                WHERE id = ?
            ");
            $stmt->execute([$partidos, $partidos_cumplidos, $descripcion, $activa, $sancion_id]);
            $message = 'Sanción actualizada exitosamente';
        } catch (Exception $e) {
            $error = 'Error al actualizar sanción: ' . $e->getMessage();
        }
    }

    // Mantener por compatibilidad, pero se recomienda usar el sistema automático
    if ($action == 'cumplir_fecha_manual') {
        try {
            $sancion_id = (int)$_POST['sancion_id'];

            $stmt = $db->prepare("
                UPDATE sanciones 
                SET 
                    partidos_cumplidos = partidos_cumplidos + 1,
                    activa = CASE 
                        WHEN partidos_cumplidos + 1 >= partidos_suspension THEN 0 
                        ELSE 1 
                    END
                WHERE id = ? AND partidos_cumplidos < partidos_suspension AND activa = 1
            ");
            $stmt->execute([$sancion_id]);

            if ($stmt->rowCount() > 0) {
                $message = 'Fecha de suspensión cumplida manualmente';
            } else {
                $message = 'La sanción ya está cumplida o no existe.';
            }
        } catch (Exception $e) {
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Filtros
$campeonato_id = $_GET['campeonato'] ?? null;
$categoria_id = $_GET['categoria'] ?? null;
$filtro_tipo = $_GET['tipo'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';

// Campeonatos
$stmt = $db->query("SELECT id, nombre FROM campeonatos WHERE activo = 1 ORDER BY nombre");
$campeonatos = $stmt->fetchAll();

$categorias = [];
if ($campeonato_id) {
    $stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1");
    $stmt->execute([$campeonato_id]);
    $categorias = $stmt->fetchAll();
}

// Consulta principal de sanciones
$where_conditions = [];
$params = [];

$sql = "
    SELECT s.*, j.apellido_nombre, j.dni, e.nombre as equipo, e.logo as equipo_logo, c.nombre as categoria,
           (s.partidos_suspension - s.partidos_cumplidos) as fechas_restantes,
           CASE 
               WHEN s.activa = 1 THEN 'Activa'
               ELSE 'Cumplida'
           END as estado_texto
    FROM sanciones s
    JOIN jugadores j ON s.jugador_id = j.id
    JOIN equipos e ON j.equipo_id = e.id
    JOIN categorias c ON e.categoria_id = c.id
";

if ($campeonato_id) {
    $where_conditions[] = "c.campeonato_id = ?";
    $params[] = $campeonato_id;
}

if ($categoria_id) {
    $where_conditions[] = "e.categoria_id = ?";
    $params[] = $categoria_id;
}

if ($filtro_tipo) {
    $where_conditions[] = "s.tipo = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_estado === 'activa') {
    $where_conditions[] = "s.activa = 1";
} elseif ($filtro_estado === 'cumplida') {
    $where_conditions[] = "s.activa = 0";
} else {
    // Por defecto: solo mostrar sanciones activas
    $where_conditions[] = "s.activa = 1";
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY s.fecha_sancion DESC, s.activa DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$sanciones = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sanciones</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .live-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #e74c3c;
            border-radius: 50%;
            margin-left: 8px;
            animation: blink 1.5s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .match-card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            background: white;
            overflow: hidden;
            height: 100%;
        }
        .match-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        .team-logo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            background: #f1f1f1;
        }
        .match-header {
            background: #f8f9fa;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .match-body {
            padding: 15px;
        }
        .score-display {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0 10px;
        }
        .match-timer {
            background: #e74c3c;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .event-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 20px;
            margin: 2px;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .event-gol { background: #2ecc71; color: white; }
        .event-amarilla { background: #f1c40f; color: black; }
        .event-roja { background: #e74c3c; color: white; }
        .eventos-container {
            min-height: 30px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 4px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #eee;
        }
        .resultado-clickeable {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .resultado-clickeable:hover {
            background-color: #f8f9fa;
        }
        .logo-placeholder {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ddd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #666;
        }
        .stats-card {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .stats-icon {
            font-size: 1.8rem;
            margin-bottom: 10px;
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
                    <li class="nav-item">
                        <a class="nav-link active" href="resultados.php">Resultados</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tablas.php">Posiciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="goleadores.php">Goleadores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="fixture.php">Fixture</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sanciones.php">Sanciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="historial_equipos.php">Equipos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="fairplay.php">Fairplay</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Panel Admin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Salir
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Ingresar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
            <div class="col-md-9 col-lg-10 p-4">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-ban"></i> Sanciones </h2>                    
                </div>
                <!-- Estadísticas rápidas -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card estadisticas">
                            <div class="card-body text-center">
                                <h3><?= count(array_filter($sanciones, fn($s) => $s['activa'] == 1)) ?></h3>
                                <p class="mb-0">Sanciones Activas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-warning text-dark">
                            <div class="card-body text-center">
                                <h3><?= count(array_filter($sanciones, fn($s) => $s['tipo'] == 'amarillas_acumuladas' && $s['activa'] == 1)) ?></h3>
                                <p class="mb-0">4 Amarillas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card" style="background: linear-gradient(135deg, #ffc107 50%, #dc3545 50%);">
                            <div class="card-body text-center text-white">
                                <h3><?= count(array_filter($sanciones, fn($s) => $s['tipo'] == 'doble_amarilla' && $s['activa'] == 1)) ?></h3>
                                <p class="mb-0">Doble Amarilla</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h3><?= count(array_filter($sanciones, fn($s) => $s['tipo'] == 'roja_directa' && $s['activa'] == 1)) ?></h3>
                                <p class="mb-0">Roja Directa</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h3><?= count(array_filter($sanciones, fn($s) => $s['tipo'] == 'administrativa' && $s['activa'] == 1)) ?></h3>
                                <p class="mb-0">Administrativas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h3><?= count(array_filter($sanciones, fn($s) => $s['activa'] == 0)) ?></h3>
                                <p class="mb-0">Cumplidas</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-2">
                                <select name="campeonato" class="form-select" onchange="this.form.submit()">
                                    <option value="">Todos los campeonatos</option>
                                    <?php foreach($campeonatos as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= $campeonato_id == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if($campeonato_id): ?>
                                <div class="col-md-2">
                                    <select name="categoria" class="form-select" onchange="this.form.submit()">
                                        <option value="">Todas las categorías</option>
                                        <?php foreach($categorias as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $categoria_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="campeonato" value="<?= $campeonato_id ?>">
                                </div>
                            <?php endif; ?>
                            <div class="col-md-2">
                                <select name="tipo" class="form-select" onchange="this.form.submit()">
                                    <option value="">Todos los tipos</option>
                                    <option value="amarillas_acumuladas" <?= $filtro_tipo == 'amarillas_acumuladas' ? 'selected' : '' ?>>4 Amarillas</option>
                                    <option value="doble_amarilla" <?= $filtro_tipo == 'doble_amarilla' ? 'selected' : '' ?>>Doble Amarilla</option>
                                    <option value="roja_directa" <?= $filtro_tipo == 'roja_directa' ? 'selected' : '' ?>>Roja Directa</option>
                                    <option value="administrativa" <?= $filtro_tipo == 'administrativa' ? 'selected' : '' ?>>Administrativa</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="estado" class="form-select" onchange="this.form.submit()">
                                    <option value="">Activas</option>
                                    <option value="cumplida" <?= $filtro_estado == 'cumplida' ? 'selected' : '' ?>>Cumplidas</option>
                                </select>
                            </div>
                            <?php if($campeonato_id || $categoria_id || $filtro_tipo || $filtro_estado): ?>
                                <div class="col-md-2">
                                    <a href="sanciones.php" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

                <!-- Lista de sanciones -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Lista de Sanciones (<?= count($sanciones) ?>)</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if(empty($sanciones)): ?>
                            <div class="text-center p-4">
                                <i class="fas fa-ban fa-3x text-muted mb-3"></i>
                                <p class="text-muted">
                                    <?php if ($filtro_estado === 'cumplida'): ?>
                                        No hay sanciones cumplidas.
                                    <?php else: ?>
                                        No hay sanciones activas.
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Jugador</th>
                                            <th>Equipo</th>
                                            <th>Tipo</th>
                                            <th>Fechas</th>
                                            <th>Estado</th>
                                            <th>Fecha Sanción</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($sanciones as $s): ?>
                                            <tr class="<?= $s['activa'] ? 'table-danger' : 'table-success' ?>">
                                                <td>
                                                    <strong><?= htmlspecialchars($s['apellido_nombre']) ?></strong><br>                                                    
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if(!empty($s['equipo_logo'])): ?>
                                                            <img src="../uploads/<?= htmlspecialchars($s['equipo_logo']) ?>" 
                                                                 alt="<?= htmlspecialchars($s['equipo']) ?>" 
                                                                 class="me-2"
                                                                 style="width: 30px; height: 30px; object-fit: contain;">
                                                        <?php else: ?>
                                                            <div class="me-2 bg-secondary rounded d-flex align-items-center justify-content-center" 
                                                                 style="width: 30px; height: 30px;">
                                                                <i class="fas fa-shield-alt text-white" style="font-size: 16px;"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <?= htmlspecialchars($s['equipo']) ?><br>
                                                            <small class="text-muted"><?= htmlspecialchars($s['categoria']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badges = [
                                                        'amarillas_acumuladas' => '<span class="badge bg-warning text-dark">🟨 4 Amarillas</span>',
                                                        'doble_amarilla' => '<span class="badge bg-danger">🟨🟥 Doble Amarilla</span>',
                                                        'roja_directa' => '<span class="badge bg-danger">🟥 Roja Directa</span>',
                                                        'administrativa' => '<span class="badge bg-info">📋 Administrativa</span>'
                                                    ];
                                                    echo $badges[$s['tipo']] ?? htmlspecialchars($s['tipo']);
                                                    ?>
                                                </td>
                                                <td>
                                                    <strong><?= min($s['partidos_cumplidos'], $s['partidos_suspension']) ?>/<?= $s['partidos_suspension'] ?></strong><br>
                                                    <?php if($s['fechas_restantes'] > 0): ?>
                                                        <small class="text-danger">Faltan <?= $s['fechas_restantes'] ?></small>
                                                    <?php else: ?>
                                                        <small class="text-success">✓ Cumplida</small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if($s['activa']): ?>
                                                        <span class="badge bg-danger">🔴 Activa</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">✓ Cumplida</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= date('d/m/Y', strtotime($s['fecha_sancion'])) ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if($s['activa']): ?>
                                                            <button type="button" class="btn btn-sm btn-warning" 
                                                                    onclick="editarSancion(<?= $s['id'] ?>, '<?= htmlspecialchars($s['apellido_nombre'], ENT_QUOTES) ?>', <?= $s['partidos_suspension'] ?>, <?= $s['partidos_cumplidos'] ?>, '<?= htmlspecialchars($s['descripcion'] ?? '', ENT_QUOTES) ?>', <?= $s['activa'] ?>)"
                                                                    title="Editar sanción">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-success" 
                                                                    onclick="cumplirFecha(<?= $s['id'] ?>, '<?= htmlspecialchars($s['apellido_nombre'], ENT_QUOTES) ?>', <?= $s['fechas_restantes'] ?>)"
                                                                    title="Cumplir fecha manualmente">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-secondary" 
                                                                    onclick="editarSancion(<?= $s['id'] ?>, '<?= htmlspecialchars($s['apellido_nombre'], ENT_QUOTES) ?>', <?= $s['partidos_suspension'] ?>, <?= $s['partidos_cumplidos'] ?>, '<?= htmlspecialchars($s['descripcion'] ?? '', ENT_QUOTES) ?>', <?= $s['activa'] ?>)"
                                                                    title="Ver/Editar sanción">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Información adicional -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información del Sistema de Sanciones</h5>
                    </div>
                    <div class="card-body">
                        <h6><strong>Tipos de Sanciones:</strong></h6>
                        <ul class="mb-3">
                            <li><strong>4 Amarillas Acumuladas:</strong> 1 fecha de suspensión automática</li>
                            <li><strong>Doble Amarilla:</strong> 1-2 fechas según reglamento</li>
                            <li><strong>Roja Directa:</strong> 2+ fechas según gravedad de la falta</li>
                            <li><strong>Administrativa:</strong> Cantidad determinada por autoridades del torneo</li>
                        </ul>
                        
                        <h6><strong>Sistema Automático:</strong></h6>
                        <ul class="mb-3">
                            <li>Las sanciones se cumplen automáticamente cuando el equipo del jugador juega un partido oficial</li>
                            <li>El sistema actualiza el contador de fechas cumplidas al cargar resultados</li>
                            <li>Una vez cumplidas todas las fechas, la sanción se marca como completada automáticamente</li>
                            <li>Los jugadores sancionados no pueden ser incluidos en planillas de juego</li>
                        </ul>

                        <h6><strong>Administración:</strong></h6>
                        <ul class="mb-0">
                            <li>Las sanciones se crean automáticamente al registrar tarjetas rojas o acumulación de amarillas</li>
                            <li>Los administradores pueden crear sanciones administrativas manualmente</li>
                            <li>Es posible editar la cantidad de fechas antes de que se cumpla la sanción</li>
                            <li>El historial de sanciones cumplidas se conserva para estadísticas y reportes</li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> Fútbol Manager. Sistema de Gestión de Torneos.</p>
        </div>
    </footer>

    <!-- Modal Nueva Sanción -->
    <div class="modal fade" id="modalNuevaSancion" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="" id="formNuevaSancion">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-plus-circle"></i> Crear Nueva Sanción
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear_sancion">
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Información:</strong> Use este formulario para crear sanciones administrativas o por informes del árbitro/planillero.
                        </div>

                        <!-- Selección de Campeonato -->
                        <div class="mb-3">
                            <label for="nuevo_campeonato" class="form-label">
                                <i class="fas fa-trophy"></i> Campeonato: *
                            </label>
                            <select class="form-select" id="nuevo_campeonato" required onchange="cargarCategoriasSancion()">
                                <option value="">Seleccione un campeonato</option>
                                <?php foreach($campeonatos as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Selección de Categoría -->
                        <div class="mb-3">
                            <label for="nuevo_categoria" class="form-label">
                                <i class="fas fa-layer-group"></i> Categoría: *
                            </label>
                            <select class="form-select" id="nuevo_categoria" required onchange="cargarEquiposSancion()">
                                <option value="">Seleccione primero un campeonato</option>
                            </select>
                        </div>

                        <!-- Selección de Equipo -->
                        <div class="mb-3">
                            <label for="nuevo_equipo" class="form-label">
                                <i class="fas fa-users"></i> Equipo: *
                            </label>
                            <select class="form-select" id="nuevo_equipo" required onchange="cargarJugadoresSancion()">
                                <option value="">Seleccione primero una categoría</option>
                            </select>
                        </div>

                        <!-- Selección de Jugador -->
                        <div class="mb-3">
                            <label for="jugador_id" class="form-label">
                                <i class="fas fa-user"></i> Jugador: *
                            </label>
                            <select class="form-select" name="jugador_id" id="jugador_id" required>
                                <option value="">Seleccione primero un equipo</option>
                            </select>
                        </div>

                        <hr>

                        <!-- Tipo de Sanción -->
                        <div class="mb-3">
                            <label for="tipo" class="form-label">
                                <i class="fas fa-exclamation-triangle"></i> Tipo de Sanción: *
                            </label>
                            <select class="form-select" name="tipo" id="tipo" required onchange="actualizarPartidosSancion()">
                                <option value="">Seleccione el tipo</option>
                                <option value="amarillas_acumuladas">🟨 Por 4 Amarillas Acumuladas (1 fecha)</option>
                                <option value="doble_amarilla">🟨🟥 Doble Amarilla (1-2 fechas)</option>
                                <option value="roja_directa">🟥 Roja Directa (2+ fechas)</option>
                                <option value="administrativa">📋 Administrativa (personalizada)</option>
                            </select>
                            <small class="text-muted">
                                Seleccione "Administrativa" para sanciones por deudas, conducta, o decisiones de la liga
                            </small>
                        </div>

                        <!-- Partidos de Suspensión -->
                        <div class="mb-3">
                            <label for="partidos_suspension" class="form-label">
                                <i class="fas fa-calendar-times"></i> Partidos de Suspensión: *
                            </label>
                            <input type="number" class="form-control" name="partidos_suspension" 
                                   id="partidos_suspension" min="1" value="1" required>
                            <small class="text-muted">Cantidad de partidos que el jugador no podrá participar</small>
                        </div>

                        <!-- Descripción/Motivo -->
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">
                                <i class="fas fa-file-alt"></i> Descripción/Motivo:
                            </label>
                            <textarea class="form-control" name="descripcion" id="descripcion" 
                                      rows="3" placeholder="Ej: Conducta violenta, deuda pendiente con el club, informe del árbitro, etc."></textarea>
                            <small class="text-muted">
                                Detalle el motivo de la sanción (opcional pero recomendado)
                            </small>
                        </div>

                        <!-- Información de Cumplimiento -->
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-robot"></i> 
                            <strong>Cumplimiento Automático:</strong> Esta sanción se cumplirá automáticamente cuando el equipo del jugador juegue sus próximos partidos.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Sanción
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Sanción -->
    <div class="modal fade" id="modalEditarSancion" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Sanción</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="editar_sancion">
                        <input type="hidden" name="sancion_id" id="edit_sancion_id">
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Jugador:</strong></label>
                            <p id="edit_jugador_nombre" class="form-control-plaintext"></p>
                        </div>

                        <div class="mb-3">
                            <label for="edit_partidos_suspension" class="form-label">Partidos de Suspensión:</label>
                            <input type="number" class="form-control" name="partidos_suspension" 
                                   id="edit_partidos_suspension" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_partidos_cumplidos" class="form-label">Partidos Cumplidos:</label>
                            <input type="number" class="form-control" name="partidos_cumplidos" 
                                   id="edit_partidos_cumplidos" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label for="edit_descripcion" class="form-label">Descripción:</label>
                            <textarea class="form-control" name="descripcion" id="edit_descripcion" rows="3"></textarea>
                        </div>

                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" name="activa" 
                                   id="edit_activa" value="1">
                            <label class="form-check-label" for="edit_activa">
                                Sanción Activa
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Cumplir Fecha -->
    <div class="modal fade" id="modalCumplirFecha" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title">Cumplir Fecha de Sanción</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="cumplir_fecha_manual">
                        <input type="hidden" name="sancion_id" id="cumplir_sancion_id">
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Atención:</strong> Normalmente las fechas se cumplen automáticamente. 
                            Use esta opción solo si necesita registrar manualmente el cumplimiento de una fecha.
                        </div>

                        <p>¿Está seguro de cumplir manualmente una fecha de sanción para:</p>
                        <p class="text-center"><strong id="cumplir_jugador_nombre"></strong></p>
                        <p class="text-center text-muted">Fechas restantes: <span id="cumplir_fechas_restantes"></span></p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Cumplimiento</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarSancion(id, jugador, partidos, cumplidos, descripcion, activa) {
            document.getElementById('edit_sancion_id').value = id;
            document.getElementById('edit_jugador_nombre').textContent = jugador;
            document.getElementById('edit_partidos_suspension').value = partidos;
            document.getElementById('edit_partidos_cumplidos').value = cumplidos;
            document.getElementById('edit_descripcion').value = descripcion;
            document.getElementById('edit_activa').checked = activa == 1;
            
            const modal = new bootstrap.Modal(document.getElementById('modalEditarSancion'));
            modal.show();
        }

        function cumplirFecha(id, jugador, fechasRestantes) {
            document.getElementById('cumplir_sancion_id').value = id;
            document.getElementById('cumplir_jugador_nombre').textContent = jugador;
            document.getElementById('cumplir_fechas_restantes').textContent = fechasRestantes;
            
            const modal = new bootstrap.Modal(document.getElementById('modalCumplirFecha'));
            modal.show();
        }

        // Funciones para el formulario de nueva sanción
        function cargarCategoriasSancion() {
            const campeonatoId = document.getElementById('nuevo_campeonato').value;
            const categoriaSelect = document.getElementById('nuevo_categoria');
            
            // Limpiar selects dependientes
            categoriaSelect.innerHTML = '<option value="">Cargando...</option>';
            document.getElementById('nuevo_equipo').innerHTML = '<option value="">Seleccione primero una categoría</option>';
            document.getElementById('jugador_id').innerHTML = '<option value="">Seleccione primero un equipo</option>';
            
            if (!campeonatoId) {
                categoriaSelect.innerHTML = '<option value="">Seleccione primero un campeonato</option>';
                return;
            }
            
            fetch(`../admin/ajax/get_categorias.php?campeonato_id=${campeonatoId}`)
                .then(response => response.json())
                .then(data => {
                    categoriaSelect.innerHTML = '<option value="">Seleccione una categoría</option>';
                    if (Array.isArray(data)) {
                        data.forEach(cat => {
                            categoriaSelect.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    categoriaSelect.innerHTML = '<option value="">Error al cargar categorías</option>';
                });
        }

        function cargarEquiposSancion() {
            const categoriaId = document.getElementById('nuevo_categoria').value;
            const equipoSelect = document.getElementById('nuevo_equipo');
            
            // Limpiar selects dependientes
            equipoSelect.innerHTML = '<option value="">Cargando...</option>';
            document.getElementById('jugador_id').innerHTML = '<option value="">Seleccione primero un equipo</option>';
            
            if (!categoriaId) {
                equipoSelect.innerHTML = '<option value="">Seleccione primero una categoría</option>';
                return;
            }
            
            fetch(`../admin/ajax/get_equipos.php?categoria_id=${categoriaId}`)
                .then(response => response.json())
                .then(data => {
                    equipoSelect.innerHTML = '<option value="">Seleccione un equipo</option>';
                    if (data.success && Array.isArray(data.equipos)) {
                        data.equipos.forEach(equipo => {
                            equipoSelect.innerHTML += `<option value="${equipo.id}">${equipo.nombre}</option>`;
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    equipoSelect.innerHTML = '<option value="">Error al cargar equipos</option>';
                });
        }

        function cargarJugadoresSancion() {
            const equipoId = document.getElementById('nuevo_equipo').value;
            const jugadorSelect = document.getElementById('jugador_id');
            
            jugadorSelect.innerHTML = '<option value="">Cargando...</option>';
            
            if (!equipoId) {
                jugadorSelect.innerHTML = '<option value="">Seleccione primero un equipo</option>';
                return;
            }
            
            // Usar la ruta correcta hacia admin/ajax/
            fetch(`../admin/ajax/get_jugadores.php?equipo_id=${equipoId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Jugadores recibidos:', data); // Para debug
                    jugadorSelect.innerHTML = '<option value="">Seleccione un jugador</option>';
                    
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(jugador => {
                            jugadorSelect.innerHTML += `<option value="${jugador.id}">${jugador.apellido_nombre}</option>`;
                        });
                    } else {
                        jugadorSelect.innerHTML = '<option value="">No hay jugadores en este equipo</option>';
                    }
                })
                .catch(error => {
                    console.error('Error al cargar jugadores:', error);
                    jugadorSelect.innerHTML = '<option value="">Error al cargar jugadores</option>';
                });
        }

        function actualizarPartidosSancion() {
            const tipo = document.getElementById('tipo').value;
            const partidosInput = document.getElementById('partidos_suspension');
            
            switch(tipo) {
                case 'amarillas_acumuladas':
                    partidosInput.value = 1;
                    partidosInput.readOnly = true;
                    break;
                case 'doble_amarilla':
                    partidosInput.value = 1;
                    partidosInput.readOnly = false;
                    partidosInput.max = 2;
                    break;
                case 'roja_directa':
                    partidosInput.value = 2;
                    partidosInput.readOnly = false;
                    partidosInput.max = 10;
                    break;
                case 'administrativa':
                    partidosInput.value = 1;
                    partidosInput.readOnly = false;
                    partidosInput.max = 10000;
                    break;
                default:
                    partidosInput.readOnly = false;
            }
        }
    </script>
</body>
</html>