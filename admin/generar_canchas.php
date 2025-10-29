<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Función para generar partidos de una ronda (round-robin)
function generarPartidosRonda($equipos, $ronda) {
    $total = count($equipos);
    $partidos = [];
    
    if ($total % 2 == 1) {
        $equipos[] = null; // Equipo "fantasma" para número impar
        $total++;
    }
    
    for ($i = 0; $i < $total / 2; $i++) {
        $local = ($ronda + $i) % ($total - 1);
        $visitante = ($total - 1 - $i + $ronda) % ($total - 1);
        
        if ($i == 0) {
            $visitante = $total - 1;
        }
        
        if (isset($equipos[$local]) && isset($equipos[$visitante]) && 
            $equipos[$local] !== null && $equipos[$visitante] !== null) {
            $partidos[] = [
                'local' => $equipos[$local],
                'visitante' => $equipos[$visitante]
            ];
        }
    }
    
    return $partidos;
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'generate_all_fixtures') {
        $campeonato_id = $_POST['campeonato_id'] ?? '';
        
        if (empty($campeonato_id)) {
            $error = 'Debe seleccionar un campeonato';
        } else {
            try {
                $db->beginTransaction();
                
                // Obtener todas las categorías activas del campeonato
                $stmt = $db->prepare("
                    SELECT c.*, camp.fecha_inicio, camp.nombre as campeonato_nombre
                    FROM categorias c
                    JOIN campeonatos camp ON c.campeonato_id = camp.id
                    WHERE c.campeonato_id = ? AND c.activa = 1
                    ORDER BY c.nombre
                ");
                $stmt->execute([$campeonato_id]);
                $categorias = $stmt->fetchAll();
                
                if (empty($categorias)) {
                    throw new Exception('No hay categorías activas en este campeonato');
                }
                
                $total_categorias_procesadas = 0;
                $total_partidos_generados = 0;
                $errores_por_categoria = [];
                
                foreach ($categorias as $categoria) {
                    try {
                        // Obtener equipos de la categoría
                        $stmt = $db->prepare("SELECT id, nombre FROM equipos WHERE categoria_id = ? AND activo = 1 ORDER BY nombre");
                        $stmt->execute([$categoria['id']]);
                        $equipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (count($equipos) < 2) {
                            $errores_por_categoria[] = "Categoría '{$categoria['nombre']}': Debe tener al menos 2 equipos activos";
                            continue;
                        }
                        
                        // Eliminar fixture existente de esta categoría
                        $stmt = $db->prepare("DELETE FROM fechas WHERE categoria_id = ?");
                        $stmt->execute([$categoria['id']]);
                        
                        // Generar fixture round-robin
                        $total_equipos = count($equipos);
                        $rondas = $total_equipos % 2 == 0 ? $total_equipos - 1 : $total_equipos;
                        
                        $fecha_actual = new DateTime($categoria['fecha_inicio']);
                        $numero_fecha = 1;
                        
                        for ($ronda = 0; $ronda < $rondas; $ronda++) {
                            // Encontrar próximo sábado
                            while ($fecha_actual->format('w') != 6) {
                                $fecha_actual->add(new DateInterval('P1D'));
                            }
                            
                            // Crear fecha
                            $stmt = $db->prepare("
                                INSERT INTO fechas (categoria_id, numero_fecha, fecha_programada) 
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$categoria['id'], $numero_fecha, $fecha_actual->format('Y-m-d')]);
                            $fecha_id = $db->lastInsertId();
                            
                            // Generar partidos para esta ronda (SIN cancha ni horario)
                            $partidos_ronda = generarPartidosRonda($equipos, $ronda);
                            
                            foreach ($partidos_ronda as $partido) {
                                $stmt = $db->prepare("
                                    INSERT INTO partidos (fecha_id, equipo_local_id, equipo_visitante_id, fecha_partido, estado) 
                                    VALUES (?, ?, ?, ?, 'sin_asignar')
                                ");
                                $stmt->execute([
                                    $fecha_id, 
                                    $partido['local'], 
                                    $partido['visitante'], 
                                    $fecha_actual->format('Y-m-d')
                                ]);
                                
                                $total_partidos_generados++;
                            }
                            
                            $fecha_actual->add(new DateInterval('P7D')); // Siguiente sábado
                            $numero_fecha++;
                        }
                        
                        $total_categorias_procesadas++;
                        
                    } catch (Exception $e) {
                        $errores_por_categoria[] = "Categoría '{$categoria['nombre']}': " . $e->getMessage();
                    }
                }
                
                $db->commit();
                
                $message = "Fixtures generados exitosamente:<br>";
                $message .= "- Categorías procesadas: $total_categorias_procesadas<br>";
                $message .= "- Total de partidos generados: $total_partidos_generados<br>";
                
                if (!empty($errores_por_categoria)) {
                    $message .= "<br><strong>Advertencias:</strong><br>";
                    foreach ($errores_por_categoria as $error_cat) {
                        $message .= "• $error_cat<br>";
                    }
                }
                
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Error al generar fixtures: ' . $e->getMessage();
            }
        }
    }
    
    if ($action == 'delete_all_fixtures') {
        $campeonato_id = $_POST['campeonato_id'] ?? '';
        
        if (empty($campeonato_id)) {
            $error = 'Debe seleccionar un campeonato';
        } else {
            try {
                $db->beginTransaction();
                
                // Eliminar todos los partidos y fechas del campeonato
                $stmt = $db->prepare("
                    DELETE p FROM partidos p
                    JOIN fechas f ON p.fecha_id = f.id
                    JOIN categorias c ON f.categoria_id = c.id
                    WHERE c.campeonato_id = ?
                ");
                $partidos_eliminados = $stmt->execute([$campeonato_id]);
                
                $stmt = $db->prepare("
                    DELETE f FROM fechas f
                    JOIN categorias c ON f.categoria_id = c.id
                    WHERE c.campeonato_id = ?
                ");
                $stmt->execute([$campeonato_id]);
                
                $db->commit();
                $message = 'Todos los fixtures del campeonato han sido eliminados';
                
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Error al eliminar fixtures: ' . $e->getMessage();
            }
        }
    }
}

// Obtener campeonatos
$stmt = $db->query("SELECT * FROM campeonatos WHERE activo = 1 ORDER BY nombre");
$campeonatos = $stmt->fetchAll();

// Obtener estadísticas de fixtures existentes
$estadisticas = [];
foreach ($campeonatos as $campeonato) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT c.id) as total_categorias,
            COUNT(DISTINCT f.id) as total_fechas,
            COUNT(p.id) as total_partidos,
            SUM(CASE WHEN p.estado = 'sin_asignar' THEN 1 ELSE 0 END) as partidos_sin_asignar,
            SUM(CASE WHEN p.estado = 'programado' THEN 1 ELSE 0 END) as partidos_programados
        FROM categorias c
        LEFT JOIN fechas f ON c.id = f.categoria_id
        LEFT JOIN partidos p ON f.id = p.fecha_id
        WHERE c.campeonato_id = ? AND c.activa = 1
    ");
    $stmt->execute([$campeonato['id']]);
    $estadisticas[$campeonato['id']] = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Fixtures Masivo - Sistema de Campeonatos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
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
                    <h2><i class="fas fa-magic"></i> Generador de Fixtures Masivo</h2>
                    <a href="asignar_horarios.php" class="btn btn-primary">
                        <i class="fas fa-clock"></i> Asignar Horarios y Canchas
                    </a>
                </div>

                <!-- Mensajes -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Información -->
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> Generador de Fixtures Masivo</h5>
                    <p class="mb-2">Esta herramienta permite generar automáticamente los fixtures para todas las categorías de un campeonato.</p>
                    <ul class="mb-0">
                        <li><strong>Genera partidos sin cancha ni horario</strong> - Solo crea la estructura de enfrentamientos</li>
                        <li><strong>Sistema round-robin</strong> - Todos los equipos juegan contra todos</li>
                        <li><strong>Fechas automáticas</strong> - Se programan los sábados según la fecha de inicio del campeonato</li>
                        <li><strong>Asignación posterior</strong> - Usa el módulo "Asignar Horarios y Canchas" para completar la programación</li>
                    </ul>
                </div>

                <!-- Estadísticas por campeonato -->
                <div class="row mb-4">
                    <?php foreach ($campeonatos as $campeonato): ?>
                        <div class="col-lg-6 mb-3">
                            <div class="card">
                                <div class="card-header d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-trophy"></i>
                                        <?php echo htmlspecialchars($campeonato['nombre']); ?>
                                    </h5>
                                    <span class="badge bg-<?php echo $campeonato['activo'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $campeonato['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </span>
                                </div>
                                <div class="card-body">
                                    <?php $stats = $estadisticas[$campeonato['id']]; ?>
                                    
                                    <div class="row text-center mb-3">
                                        <div class="col-4">
                                            <div class="text-primary">
                                                <i class="fas fa-list fa-2x"></i>
                                                <div class="h4 mb-0"><?php echo $stats['total_categorias'] ?? 0; ?></div>
                                                <small>Categorías</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-info">
                                                <i class="fas fa-calendar fa-2x"></i>
                                                <div class="h4 mb-0"><?php echo $stats['total_fechas'] ?? 0; ?></div>
                                                <small>Fechas</small>
                                            </div>
                                        </div>
                                        <div class="col-4">
                                            <div class="text-success">
                                                <i class="fas fa-futbol fa-2x"></i>
                                                <div class="h4 mb-0"><?php echo $stats['total_partidos'] ?? 0; ?></div>
                                                <small>Partidos</small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <?php if ($stats['total_partidos'] > 0): ?>
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between">
                                                <span>Sin asignar:</span>
                                                <span class="badge bg-warning"><?php echo $stats['partidos_sin_asignar'] ?? 0; ?></span>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <span>Programados:</span>
                                                <span class="badge bg-success"><?php echo $stats['partidos_programados'] ?? 0; ?></span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="d-grid gap-2 d-md-flex">
                                        <?php if ($campeonato['activo']): ?>
                                            <button class="btn btn-success btn-sm flex-fill" 
                                                    onclick="generarFixtures(<?php echo $campeonato['id']; ?>, '<?php echo htmlspecialchars($campeonato['nombre']); ?>')">
                                                <i class="fas fa-magic"></i> Generar
                                            </button>
                                            
                                            <?php if ($stats['total_partidos'] > 0): ?>
                                                <button class="btn btn-outline-danger btn-sm" 
                                                        onclick="eliminarFixtures(<?php echo $campeonato['id']; ?>, '<?php echo htmlspecialchars($campeonato['nombre']); ?>')">
                                                    <i class="fas fa-trash"></i> Limpiar
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <button class="btn btn-secondary btn-sm" disabled>
                                                <i class="fas fa-ban"></i> Inactivo
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (empty($campeonatos)): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-trophy fa-4x text-muted mb-3"></i>
                            <h4 class="text-muted">No hay campeonatos activos</h4>
                            <p class="text-muted">Crea un campeonato para poder generar fixtures</p>
                            <a href="campeonatos.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear Campeonato
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Instrucciones -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-question-circle"></i> Instrucciones</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Paso 1: Generar Fixtures</h6>
                                <ol>
                                    <li>Selecciona un campeonato activo</li>
                                    <li>Presiona "Generar" para crear todos los partidos</li>
                                    <li>Se crearán partidos sin cancha ni horario</li>
                                    <li>Todas las categorías del campeonato serán procesadas</li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h6>Paso 2: Asignar Horarios y Canchas</h6>
                                <ol>
                                    <li>Ve al módulo "Asignar Horarios y Canchas"</li>
                                    <li>Selecciona fecha y categorías</li>
                                    <li>Asigna canchas y horarios evitando conflictos</li>
                                    <li>Los partidos cambiarán a estado "programado"</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Forms ocultos -->
    <form method="POST" id="formGenerar" style="display: none;">
        <input type="hidden" name="action" value="generate_all_fixtures">
        <input type="hidden" name="campeonato_id" id="campeonato_generar">
    </form>

    <form method="POST" id="formEliminar" style="display: none;">
        <input type="hidden" name="action" value="delete_all_fixtures">
        <input type="hidden" name="campeonato_id" id="campeonato_eliminar">
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function generarFixtures(campeonatoId, nombre) {
            if (confirm(`¿Generar fixtures para el campeonato "${nombre}"?\n\nEsto creará partidos para todas las categorías activas del campeonato.\nSi ya existen fixtures, serán reemplazados.`)) {
                document.getElementById('campeonato_generar').value = campeonatoId;
                document.getElementById('formGenerar').submit();
            }
        }

        function eliminarFixtures(campeonatoId, nombre) {
            if (confirm(`¿Eliminar TODOS los fixtures del campeonato "${nombre}"?\n\nEsta acción no se puede deshacer.\nSe eliminarán todos los partidos y fechas del campeonato.`)) {
                if (confirm('¿Está completamente seguro?\nEsta acción eliminará permanentemente todos los fixtures.')) {
                    document.getElementById('campeonato_eliminar').value = campeonatoId;
                    document.getElementById('formEliminar').submit();
                }
            }
        }
    </script>
</body>
</html>