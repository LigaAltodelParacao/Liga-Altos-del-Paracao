<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$formato_id = $_GET['id'] ?? null;

if (!$formato_id) {
    redirect('campeonatos_zonas.php');
}

// Obtener información del formato
$stmt = $db->prepare("
    SELECT cf.*, c.nombre as campeonato_nombre, cat.id as categoria_id
    FROM campeonatos_formato cf
    JOIN campeonatos c ON cf.campeonato_id = c.id
    JOIN categorias cat ON cat.campeonato_id = c.id
    WHERE cf.id = ?
    LIMIT 1
");
$stmt->execute([$formato_id]);
$formato = $stmt->fetch();

if (!$formato) {
    redirect('campeonatos_zonas.php');
}

// Obtener zonas del formato
$stmt = $db->prepare("
    SELECT z.*,
           (SELECT COUNT(*) FROM equipos_zonas WHERE zona_id = z.id) as equipos_asignados
    FROM zonas z
    WHERE z.formato_id = ?
    ORDER BY z.orden
");
$stmt->execute([$formato_id]);
$zonas = $stmt->fetchAll();

// Obtener equipos de la categoría
$stmt = $db->prepare("
    SELECT e.*,
           CASE 
               WHEN ez.id IS NOT NULL THEN 1
               ELSE 0
           END as ya_asignado,
           z.nombre as zona_actual
    FROM equipos e
    LEFT JOIN equipos_zonas ez ON e.id = ez.equipo_id 
        AND ez.zona_id IN (SELECT id FROM zonas WHERE formato_id = ?)
    LEFT JOIN zonas z ON ez.zona_id = z.id
    WHERE e.categoria_id = ? AND e.activo = 1
    ORDER BY e.nombre
");
$stmt->execute([$formato_id, $formato['categoria_id']]);
$equipos = $stmt->fetchAll();

// Obtener equipos ya asignados por zona
$equipos_por_zona = [];
foreach ($zonas as $zona) {
    $stmt = $db->prepare("
        SELECT e.*, ez.puntos, ez.posicion
        FROM equipos_zonas ez
        JOIN equipos e ON ez.equipo_id = e.id
        WHERE ez.zona_id = ?
        ORDER BY ez.posicion, e.nombre
    ");
    $stmt->execute([$zona['id']]);
    $equipos_por_zona[$zona['id']] = $stmt->fetchAll();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Equipos a Zonas</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .zona-container {
            border: 3px solid #dee2e6;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            min-height: 300px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .zona-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-weight: bold;
            font-size: 1.2em;
        }
        .equipo-item {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .equipo-item:hover {
            border-color: #198754;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .equipo-item.asignado {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .equipo-item.en-zona {
            background: #d4edda;
            border-color: #c3e6cb;
        }
        .equipo-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-right: 10px;
        }
        .drop-zone {
            border: 2px dashed #adb5bd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            color: #6c757d;
            min-height: 100px;
        }
        .drop-zone.drag-over {
            border-color: #198754;
            background: #d4edda;
        }
        .btn-distribuir {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
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
                    <div>
                        <h2><i class="fas fa-layer-group"></i> Asignar Equipos a Zonas</h2>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($formato['campeonato_nombre']); ?></p>
                    </div>
                    <div>
                        <button class="btn btn-distribuir me-2" onclick="distribuirAutomaticamente()">
                            <i class="fas fa-random"></i> Distribuir Automáticamente
                        </button>
                        <button class="btn btn-danger" onclick="limpiarAsignaciones()">
                            <i class="fas fa-trash"></i> Limpiar Todo
                        </button>
                        <a href="campeonatos_zonas_fixture.php?id=<?php echo $formato_id; ?>" class="btn btn-success">
                            <i class="fas fa-arrow-right"></i> Siguiente: Fixture
                        </a>
                    </div>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Instrucciones:</strong> Arrastra los equipos desde la lista hacia las zonas, 
                    o usa el botón "Distribuir Automáticamente" para una distribución equitativa.
                    Se necesitan <strong><?php echo $formato['equipos_por_zona']; ?> equipos por zona</strong>.
                </div>

                <div class="row">
                    <!-- Lista de equipos disponibles -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-users"></i> Equipos Disponibles</h5>
                                <small><?php echo count($equipos); ?> equipos totales</small>
                            </div>
                            <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                                <div id="equiposDisponibles">
                                    <?php foreach ($equipos as $equipo): ?>
                                    <div class="equipo-item <?php echo $equipo['ya_asignado'] ? 'asignado' : ''; ?>" 
                                         draggable="<?php echo $equipo['ya_asignado'] ? 'false' : 'true'; ?>"
                                         data-equipo-id="<?php echo $equipo['id']; ?>"
                                         ondragstart="drag(event)">
                                        <div class="d-flex align-items-center">
                                            <?php if ($equipo['logo']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($equipo['logo']); ?>" 
                                                 class="equipo-logo" alt="Logo">
                                            <?php else: ?>
                                            <div class="equipo-logo bg-secondary rounded d-flex align-items-center justify-content-center">
                                                <i class="fas fa-shield-alt text-white"></i>
                                            </div>
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($equipo['nombre']); ?></strong>
                                                <?php if ($equipo['ya_asignado']): ?>
                                                <br><small class="text-success">
                                                    <i class="fas fa-check"></i> <?php echo $equipo['zona_actual']; ?>
                                                </small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!$equipo['ya_asignado']): ?>
                                        <i class="fas fa-grip-vertical text-muted"></i>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Zonas -->
                    <div class="col-lg-8">
                        <div class="row">
                            <?php foreach ($zonas as $index => $zona): ?>
                            <div class="col-md-6 mb-4">
                                <div class="zona-container" 
                                     data-zona-id="<?php echo $zona['id']; ?>"
                                     ondrop="drop(event)" 
                                     ondragover="allowDrop(event)"
                                     ondragleave="dragLeave(event)">
                                    <div class="zona-header">
                                        <?php echo htmlspecialchars($zona['nombre']); ?>
                                        <span class="float-end">
                                            <span class="equipos-count"><?php echo count($equipos_por_zona[$zona['id']]); ?></span> / 
                                            <?php echo $formato['equipos_por_zona']; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="equipos-zona" id="zona-<?php echo $zona['id']; ?>">
                                        <?php if (empty($equipos_por_zona[$zona['id']])): ?>
                                        <div class="drop-zone">
                                            <i class="fas fa-hand-pointer fa-2x mb-2"></i>
                                            <p>Arrastra equipos aquí</p>
                                        </div>
                                        <?php else: ?>
                                        <?php foreach ($equipos_por_zona[$zona['id']] as $equipo): ?>
                                        <div class="equipo-item en-zona" data-equipo-id="<?php echo $equipo['id']; ?>">
                                            <div class="d-flex align-items-center">
                                                <?php if ($equipo['logo']): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($equipo['logo']); ?>" 
                                                     class="equipo-logo" alt="Logo">
                                                <?php else: ?>
                                                <div class="equipo-logo bg-secondary rounded d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-shield-alt text-white"></i>
                                                </div>
                                                <?php endif; ?>
                                                <strong><?php echo htmlspecialchars($equipo['nombre']); ?></strong>
                                            </div>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="quitarEquipoZona(<?php echo $equipo['id']; ?>, <?php echo $zona['id']; ?>)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        function drag(ev) {
            ev.dataTransfer.setData("equipoId", ev.target.getAttribute('data-equipo-id'));
        }

        function allowDrop(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.add('drag-over');
        }

        function dragLeave(ev) {
            ev.currentTarget.classList.remove('drag-over');
        }

        function drop(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.remove('drag-over');
            
            const equipoId = ev.dataTransfer.getData("equipoId");
            const zonaContainer = ev.currentTarget.closest('.zona-container');
            const zonaId = zonaContainer.getAttribute('data-zona-id');
            
            asignarEquipoZona(equipoId, zonaId);
        }

        function asignarEquipoZona(equipoId, zonaId) {
            $.ajax({
                url: 'ajax/asignar_equipo_zona.php',
                method: 'POST',
                data: {
                    formato_id: <?php echo $formato_id; ?>,
                    equipo_id: equipoId,
                    zona_id: zonaId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error al asignar equipo');
                }
            });
        }

        function quitarEquipoZona(equipoId, zonaId) {
            if (confirm('¿Quitar este equipo de la zona?')) {
                $.ajax({
                    url: 'ajax/quitar_equipo_zona.php',
                    method: 'POST',
                    data: {
                        equipo_id: equipoId,
                        zona_id: zonaId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            }
        }

        function distribuirAutomaticamente() {
            if (confirm('¿Distribuir todos los equipos automáticamente? Esto eliminará las asignaciones actuales.')) {
                $.ajax({
                    url: 'ajax/distribuir_equipos_automatico.php',
                    method: 'POST',
                    data: { formato_id: <?php echo $formato_id; ?> },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            alert('Equipos distribuidos correctamente');
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            }
        }

        function limpiarAsignaciones() {
            if (confirm('¿Eliminar todas las asignaciones de equipos?')) {
                $.ajax({
                    url: 'ajax/limpiar_equipos_zonas.php',
                    method: 'POST',
                    data: { formato_id: <?php echo $formato_id; ?> },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + response.message);
                        }
                    }
                });
            }
        }
    </script>
</body>
</html>