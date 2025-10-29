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
        try {
            $db->beginTransaction();
            
            // Obtener TODOS los campeonatos activos
            $stmt = $db->query("SELECT * FROM campeonatos WHERE activo = 1 ORDER BY nombre");
            $campeonatos = $stmt->fetchAll();
            
            if (empty($campeonatos)) {
                throw new Exception('No hay campeonatos activos');
            }
            
            $total_campeonatos = 0;
            $total_categorias = 0;
            $total_partidos = 0;
            $errores = [];
            
            foreach ($campeonatos as $campeonato) {
                // Obtener categorías activas del campeonato
                $stmt = $db->prepare("
                    SELECT c.*, camp.fecha_inicio
                    FROM categorias c
                    JOIN campeonatos camp ON c.campeonato_id = camp.id
                    WHERE c.campeonato_id = ? AND c.activa = 1
                    ORDER BY c.nombre
                ");
                $stmt->execute([$campeonato['id']]);
                $categorias = $stmt->fetchAll();
                
                if (empty($categorias)) {
                    $errores[] = "Campeonato '{$campeonato['nombre']}': No tiene categorías activas";
                    continue;
                }
                
                foreach ($categorias as $categoria) {
                    try {
                        // Obtener equipos de la categoría
                        $stmt = $db->prepare("SELECT id FROM equipos WHERE categoria_id = ? AND activo = 1 ORDER BY nombre");
                        $stmt->execute([$categoria['id']]);
                        $equipos = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        
                        if (count($equipos) < 2) {
                            $errores[] = "Categoría '{$categoria['nombre']}' ({$campeonato['nombre']}): Necesita al menos 2 equipos";
                            continue;
                        }
                        
                        // Eliminar fixture existente
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
                            
                            // Generar partidos SIN cancha ni horario
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
                                
                                $total_partidos++;
                            }
                            
                            $fecha_actual->add(new DateInterval('P7D'));
                            $numero_fecha++;
                        }
                        
                        $total_categorias++;
                        
                    } catch (Exception $e) {
                        $errores[] = "Error en categoría '{$categoria['nombre']}': " . $e->getMessage();
                    }
                }
                
                $total_campeonatos++;
            }
            
            $db->commit();
            
            $message = "Fixtures generados para TODOS los campeonatos activos:<br>";
            $message .= "• Campeonatos procesados: $total_campeonatos<br>";
            $message .= "• Categorías procesadas: $total_categorias<br>";
            $message .= "• Total partidos creados: $total_partidos<br>";
            
            if (!empty($errores)) {
                $message .= "<br><strong>Advertencias:</strong><br>";
                foreach ($errores as $error_msg) {
                    $message .= "• $error_msg<br>";
                }
            }
            
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Error al generar fixtures: ' . $e->getMessage();
        }
    }
    
    if ($action == 'delete_all_fixtures') {
        try {
            $db->beginTransaction();
            
            // Eliminar TODOS los partidos y fechas de campeonatos activos
            $stmt = $db->query("
                DELETE p FROM partidos p
                JOIN fechas f ON p.fecha_id = f.id
                JOIN categorias c ON f.categoria_id = c.id
                JOIN campeonatos camp ON c.campeonato_id = camp.id
                WHERE camp.activo = 1
            ");
            $partidos_eliminados = $stmt->rowCount();
            
            $stmt = $db->query("
                DELETE f FROM fechas f
                JOIN categorias c ON f.categoria_id = c.id
                JOIN campeonatos camp ON c.campeonato_id = camp.id
                WHERE camp.activo = 1
            ");
            $fechas_eliminadas = $stmt->rowCount();
            
            $db->commit();
            $message = "Fixtures eliminados: $partidos_eliminados partidos y $fechas_eliminadas fechas";
            
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Error al eliminar fixtures: ' . $e->getMessage();
        }
    }
}

// Obtener estadísticas generales
$stmt = $db->query("
    SELECT 
        COUNT(DISTINCT camp.id) as campeonatos_activos,
        COUNT(DISTINCT c.id) as categorias_activas,
        COUNT(DISTINCT f.id) as total_fechas,
        COUNT(p.id) as total_partidos,
        SUM(CASE WHEN p.estado = 'sin_asignar' THEN 1 ELSE 0 END) as partidos_sin_asignar,
        SUM(CASE WHEN p.estado = 'programado' THEN 1 ELSE 0 END) as partidos_programados,
        SUM(CASE WHEN p.estado = 'finalizado' THEN 1 ELSE 0 END) as partidos_finalizados
    FROM campeonatos camp
    LEFT JOIN categorias c ON camp.id = c.campeonato_id AND c.activa = 1
    LEFT JOIN fechas f ON c.id = f.categoria_id
    LEFT JOIN partidos p ON f.id = p.fecha_id
    WHERE camp.activo = 1
");
$estadisticas_generales = $stmt->fetch();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generador de Fixtures - Sistema de Campeonatos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
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
                    <h2><i class="fas fa-futbol"></i> Generador de Fixtures</h2>
                    <div>
                        <a href="control_fechas.php" class="btn btn-info me-2">
                            <i class="fas fa-calendar-check"></i> Control de Fechas
                        </a>
                        <a href="asignar_canchas.php" class="btn btn-primary">
                            <i class="fas fa-map-marker-alt"></i> Asignar Canchas y Horarios
                        </a>
                    </div>
                </div>

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

                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-magic"></i> Generador Automático de Fixtures</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-4">
                            <h6><i class="fas fa-info-circle"></i> Generación Automática</h6>
                            <p class="mb-2">Este sistema genera fixtures para <strong>TODOS los campeonatos activos</strong> automáticamente:</p>
                            <ul class="mb-0">
                                <li>Procesa todas las categorías de todos los campeonatos activos</li>
                                <li>Crea partidos sin cancha ni horario (estado: sin_asignar)</li>
                                <li>Usa sistema round-robin (todos contra todos)</li>
                                <li>Programa fechas en sábados desde la fecha de inicio de cada campeonato</li>
                                <li>Después usa "Asignar Canchas y Horarios" para completar la programación</li>
                            </ul>
                        </div>

                        <div class="text-center">
                            <button class="btn btn-success btn-lg me-3" onclick="generarTodos()">
                                <i class="fas fa-magic"></i> Generar Fixtures para Todos los Campeonatos
                            </button>
                            <?php if ($estadisticas_generales['total_partidos'] > 0): ?>
                                <button class="btn btn-outline-danger btn-lg" onclick="eliminarTodos()">
                                    <i class="fas fa-trash"></i> Eliminar Todos los Fixtures
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Estadísticas generales -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-primary text-white text-center">
                            <div class="card-body">
                                <i class="fas fa-trophy fa-2x mb-2"></i>
                                <h4><?php echo $estadisticas_generales['campeonatos_activos'] ?? 0; ?></h4>
                                <small>Campeonatos Activos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-info text-white text-center">
                            <div class="card-body">
                                <i class="fas fa-list fa-2x mb-2"></i>
                                <h4><?php echo $estadisticas_generales['categorias_activas'] ?? 0; ?></h4>
                                <small>Categorías Activas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-warning text-white text-center">
                            <div class="card-body">
                                <i class="fas fa-calendar fa-2x mb-2"></i>
                                <h4><?php echo $estadisticas_generales['total_fechas'] ?? 0; ?></h4>
                                <small>Fechas Creadas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card bg-success text-white text-center">
                            <div class="card-body">
                                <i class="fas fa-futbol fa-2x mb-2"></i>
                                <h4><?php echo $estadisticas_generales['total_partidos'] ?? 0; ?></h4>
                                <small>Partidos Totales</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-light border">
                    <h6><i class="fas fa-arrow-right"></i> Próximos pasos:</h6>
                    <ol class="mb-0">
                        <li>Generar fixtures con este módulo</li>
                        <li>Ir a <strong>"Asignar Canchas y Horarios"</strong> para completar la programación</li>
                        <li>Usar <strong>"Control de Fechas"</strong> para seguimiento en vivo y cargar resultados</li>
                    </ol>
                </div>

            </div>
        </div>
    </div>

    <form method="POST" id="formGenerar" style="display: none;">
        <input type="hidden" name="action" value="generate_all_fixtures">
    </form>
    <form method="POST" id="formEliminar" style="display: none;">
        <input type="hidden" name="action" value="delete_all_fixtures">
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function generarTodos() {
            if (confirm('¿Desea generar fixtures para todos los campeonatos activos? Esto puede sobrescribir datos existentes.')) {
                document.getElementById('formGenerar').submit();
            }
        }

        function eliminarTodos() {
            if (confirm('¿Desea eliminar todos los fixtures de todos los campeonatos activos?')) {
                document.getElementById('formEliminar').submit();
            }
        }
    </script>
</body>
</html>