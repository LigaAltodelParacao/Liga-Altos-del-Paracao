<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/funciones_torneos_zonas.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

$formato_id = $_GET['formato_id'] ?? null;

if (!$formato_id) {
    redirect('torneos_zonas.php');
}

// Obtener información del formato
$stmt = $db->prepare("
    SELECT 
        cf.*,
        c.nombre as campeonato_nombre,
        cat.nombre as categoria_nombre,
        cat.id as categoria_id
    FROM campeonatos_formato cf
    JOIN campeonatos c ON cf.campeonato_id = c.id
    LEFT JOIN categorias cat ON cf.categoria_id = cat.id
    WHERE cf.id = ?
");
$stmt->execute([$formato_id]);
$formato = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$formato) {
    redirect('torneos_zonas.php');
}

$message = '';
$error = '';

// Procesar generación de fixture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_fixture'])) {
    try {
        $db->beginTransaction();
        
        $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $dias_entre_fechas = (int)($_POST['dias_entre_fechas'] ?? 7);
        $tipo_fixture = $_POST['tipo_fixture'] ?? 'todos_contra_todos';
        
        // Obtener configuración de zonas por día
        $zonas_por_dia = [];
        if (!empty($_POST['zonas_dias']) && is_array($_POST['zonas_dias'])) {
            foreach ($_POST['zonas_dias'] as $zona_id => $dia_semana) {
                if (!empty($dia_semana) && $dia_semana != '') {
                    $zonas_por_dia[$zona_id] = $dia_semana;
                }
            }
        }
        
        // ========== LIMPIAR FIXTURE ANTERIOR ==========
        // Obtener todas las zonas del formato
        $stmt = $db->prepare("SELECT id FROM zonas WHERE formato_id = ?");
        $stmt->execute([$formato_id]);
        $zona_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Obtener todas las fases eliminatorias del formato
        $stmt = $db->prepare("SELECT id FROM fases_eliminatorias WHERE formato_id = ?");
        $stmt->execute([$formato_id]);
        $fase_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // 1. Eliminar partidos de zonas
        if (!empty($zona_ids)) {
            $placeholders = str_repeat('?,', count($zona_ids) - 1) . '?';
            $stmt = $db->prepare("
                DELETE FROM partidos 
                WHERE zona_id IN ($placeholders) AND tipo_torneo = 'zona'
            ");
            $stmt->execute($zona_ids);
        }
        
        // 2. Eliminar partidos eliminatorios
        if (!empty($fase_ids)) {
            $placeholders = str_repeat('?,', count($fase_ids) - 1) . '?';
            $stmt = $db->prepare("
                DELETE FROM partidos 
                WHERE fase_eliminatoria_id IN ($placeholders) AND tipo_torneo = 'eliminatoria'
            ");
            $stmt->execute($fase_ids);
        }
        
        // 3. Eliminar fechas relacionadas que no tienen más partidos
        // Obtener fechas de zonas y eliminatorias que no tienen partidos
        if (!empty($formato['categoria_id'])) {
            $stmt = $db->prepare("
                SELECT f.id 
                FROM fechas f
                LEFT JOIN partidos p ON f.id = p.fecha_id
                WHERE f.categoria_id = ? 
                  AND (f.tipo_fecha = 'zona' OR f.tipo_fecha = 'eliminatoria')
                  AND p.id IS NULL
            ");
            $stmt->execute([$formato['categoria_id']]);
            $fechas_a_eliminar = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($fechas_a_eliminar)) {
                $placeholders = str_repeat('?,', count($fechas_a_eliminar) - 1) . '?';
                $stmt = $db->prepare("
                    DELETE FROM fechas 
                    WHERE id IN ($placeholders)
                ");
                $stmt->execute($fechas_a_eliminar);
            }
        }
        
        // ========== GENERAR NUEVO FIXTURE ==========
        // Obtener zonas
        $stmt = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
        $stmt->execute([$formato_id]);
        $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($zonas)) {
            throw new Exception('No hay zonas configuradas para este torneo');
        }
        
        // Verificar que todas las zonas tengan equipos
        foreach ($zonas as $zona) {
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM equipos_zonas WHERE zona_id = ?");
            $stmt->execute([$zona['id']]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($count['total'] < 2) {
                throw new Exception("La zona {$zona['nombre']} no tiene suficientes equipos (mínimo 2)");
            }
        }
        
        // Generar fixture para cada zona con su día asignado
        $partidos_generados = 0;
        // Mapeo de días: PHP usa 0=domingo, 1=lunes, ..., 6=sábado
        $dias_semana = [
            'lunes' => 1, 
            'martes' => 2, 
            'miercoles' => 3, 
            'jueves' => 4, 
            'viernes' => 5, 
            'sabado' => 6, 
            'domingo' => 0
        ];
        
        foreach ($zonas as $zona) {
            // Calcular fecha de inicio para esta zona basada en el día asignado
            $fecha_base = new DateTime($fecha_inicio);
            
            // Si la zona tiene un día asignado, calcular la fecha del primer día de esa zona
            if (!empty($zonas_por_dia[$zona['id']])) {
                $dia_asignado = $zonas_por_dia[$zona['id']];
                $dia_deseado = $dias_semana[$dia_asignado] ?? 1;
                $dia_actual = (int)$fecha_base->format('w'); // 0=domingo, 1=lunes, etc.
                
                // Calcular días a sumar para llegar al día deseado
                if ($dia_actual != $dia_deseado) {
                    $dias_a_sumar = ($dia_deseado - $dia_actual + 7) % 7;
                    if ($dias_a_sumar == 0) {
                        $dias_a_sumar = 7; // Si es el mismo día, usar la siguiente semana
                    }
                    $fecha_base->modify("+{$dias_a_sumar} days");
                }
            }
            
            // Generar fixture
            generarFixtureZona($zona['id'], $db, $fecha_base->format('Y-m-d'), $dias_entre_fechas);
            
            // Contar partidos generados
            $stmt = $db->prepare("SELECT COUNT(*) as total FROM partidos WHERE zona_id = ? AND tipo_torneo = 'zona'");
            $stmt->execute([$zona['id']]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            $partidos_generados += $count['total'];
        }
        
        $db->commit();
        
        $message = "Fixture generado exitosamente. Se eliminaron los fixtures anteriores y se crearon {$partidos_generados} partidos en " . count($zonas) . " zona(s).";
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error al generar fixture: ' . $e->getMessage();
    }
}

// Obtener zonas con información
$stmt = $db->prepare("
    SELECT 
        z.*,
        (SELECT COUNT(*) FROM equipos_zonas WHERE zona_id = z.id) as total_equipos,
        (SELECT COUNT(*) FROM partidos WHERE zona_id = z.id AND tipo_torneo = 'zona') as total_partidos,
        (SELECT COUNT(*) FROM partidos WHERE zona_id = z.id AND tipo_torneo = 'zona' AND estado = 'finalizado') as partidos_finalizados
    FROM zonas z
    WHERE z.formato_id = ?
    ORDER BY z.orden
");
$stmt->execute([$formato_id]);
$zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener canchas disponibles
$canchas = $db->query("SELECT * FROM canchas WHERE activa = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Fixture - Torneo con Zonas</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
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
                <?php include __DIR__ . '/../include/sidebar.php'; ?>
            </div>

            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-calendar-alt"></i> Generar Fixture</h2>
                        <p class="text-muted mb-0">
                            <?= htmlspecialchars($formato['campeonato_nombre']) ?> - 
                            <?= htmlspecialchars($formato['categoria_nombre']) ?>
                        </p>
                    </div>
                    <a href="torneos_zonas.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Resumen de Zonas -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-layer-group"></i> Zonas del Torneo</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($zonas as $zona): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6><?= htmlspecialchars($zona['nombre']) ?></h6>
                                            <p class="mb-1">
                                                <strong>Equipos:</strong> <?= $zona['total_equipos'] ?>
                                            </p>
                                            <p class="mb-1">
                                                <strong>Partidos:</strong> 
                                                <?= $zona['partidos_finalizados'] ?> / <?= $zona['total_partidos'] ?>
                                                <?php if ($zona['total_partidos'] > 0): ?>
                                                    <div class="progress mt-1" style="height: 5px;">
                                                        <div class="progress-bar" 
                                                             style="width: <?= ($zona['partidos_finalizados'] / $zona['total_partidos'] * 100) ?>%">
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Formulario de Generación -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Configuración del Fixture</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Fecha de Inicio *</label>
                                    <input type="date" name="fecha_inicio" class="form-control" 
                                           value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Días entre Fechas *</label>
                                    <input type="number" name="dias_entre_fechas" class="form-control" 
                                           value="7" min="1" required>
                                    <small class="text-muted">Días entre cada jornada</small>
                                </div>
                            </div>

                            <!-- Selección de Días por Zona -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-calendar-week"></i> Asignar Días de Juego por Zona
                                </label>
                                <p class="text-muted small">
                                    Selecciona el día de la semana en que jugará cada zona. Puedes dejar sin asignar si todas las zonas juegan el mismo día.
                                </p>
                                <div class="row">
                                    <?php foreach ($zonas as $zona): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card">
                                                <div class="card-body p-3">
                                                    <label class="form-label fw-bold mb-2">
                                                        <?= htmlspecialchars($zona['nombre']) ?>
                                                        <small class="text-muted">(<?= $zona['total_equipos'] ?> equipos)</small>
                                                    </label>
                                                    <select name="zonas_dias[<?= $zona['id'] ?>]" class="form-select">
                                                        <option value="">Sin asignar (usa fecha de inicio)</option>
                                                        <option value="lunes">Lunes</option>
                                                        <option value="martes">Martes</option>
                                                        <option value="miercoles">Miércoles</option>
                                                        <option value="jueves">Jueves</option>
                                                        <option value="viernes">Viernes</option>
                                                        <option value="sabado">Sábado</option>
                                                        <option value="domingo">Domingo</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Advertencia:</strong> Al generar un nuevo fixture, se eliminarán automáticamente:
                                <ul class="mb-0 mt-2">
                                    <li>Todos los partidos de fases de grupos (zonas)</li>
                                    <li>Todos los partidos de fases eliminatorias</li>
                                    <li>Las fechas asociadas a estos partidos</li>
                                </ul>
                                <strong class="text-danger">Esta acción no se puede deshacer.</strong>
                            </div>

                            <div class="text-end">
                                <a href="torneos_zonas.php" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                                <button type="submit" name="generar_fixture" class="btn btn-success btn-lg">
                                    <i class="fas fa-calendar-alt"></i> Generar Fixture
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Información -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información</h5>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>El fixture se genera usando el algoritmo <strong>Round-Robin</strong> (todos contra todos).</li>
                            <li>Los partidos se crearán con estado <strong>pendiente</strong> y deberán ser asignados a canchas y horarios posteriormente.</li>
                            <li>Después de generar el fixture, puedes gestionar los partidos desde <strong>Control de Partidos</strong>.</li>
                            <li>Una vez finalizados todos los partidos de zona, podrás generar automáticamente las fases eliminatorias.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>

