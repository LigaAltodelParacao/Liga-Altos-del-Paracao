<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$user = getCurrentUser();

// Estadísticas generales
$stats = [];

// Campeonatos activos
$stmt = $db->query("SELECT COUNT(*) FROM campeonatos WHERE activo = 1");
$stats['campeonatos'] = $stmt->fetchColumn();

// Equipos totales
$stmt = $db->query("SELECT COUNT(*) FROM equipos WHERE activo = 1");
$stats['equipos'] = $stmt->fetchColumn();

// Jugadores totales
$stmt = $db->query("SELECT COUNT(*) FROM jugadores WHERE activo = 1");
$stats['jugadores'] = $stmt->fetchColumn();

// Partidos hoy
$stmt = $db->query("SELECT COUNT(*) FROM partidos WHERE DATE(fecha_partido) = CURDATE()");
$stats['partidos_hoy'] = $stmt->fetchColumn();

// Partidos en vivo
$stmt = $db->query("SELECT COUNT(*) FROM partidos WHERE estado = 'en_curso'");
$stats['partidos_vivo'] = $stmt->fetchColumn();

// Últimos partidos finalizados (mejor que "próximos" en dashboard)
$stmt = $db->query("
    SELECT p.goles_local, p.goles_visitante, 
           el.nombre as local, ev.nombre as visitante,
           p.fecha_partido
    FROM partidos p
    JOIN equipos el ON p.equipo_local_id = el.id
    JOIN equipos ev ON p.equipo_visitante_id = ev.id
    WHERE p.estado = 'finalizado'
    ORDER BY p.finalizado_at DESC
    LIMIT 5
");
$ultimos_resultados = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración - Sistema de Campeonatos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-futbol"></i> Fútbol Manager - Admin
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

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-tachometer-alt"></i> Dashboard</h2>
                    <div class="text-muted">
                        <i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i'); ?>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="icon text-primary">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="number text-primary"><?php echo $stats['campeonatos']; ?></div>
                            <div class="label">Campeonatos Activos</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="icon text-success">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="number text-success"><?php echo $stats['equipos']; ?></div>
                            <div class="label">Equipos</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="icon text-info">
                                <i class="fas fa-user-friends"></i>
                            </div>
                            <div class="number text-info"><?php echo $stats['jugadores']; ?></div>
                            <div class="label">Jugadores</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="stats-card">
                            <div class="icon text-warning">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div class="number text-warning"><?php echo $stats['partidos_hoy']; ?></div>
                            <div class="label">Partidos Hoy</div>
                        </div>
                    </div>
                </div>

                <?php if ($stats['partidos_vivo'] > 0): ?>
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-broadcast-tower"></i> Partidos en Vivo</h5>
                            <p>Hay <?php echo $stats['partidos_vivo']; ?> partido(s) en curso.</p>
                            <a href="eventos_vivo.php" class="btn btn-outline-danger">Ver Eventos en Vivo</a>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Quick Actions -->
                    <div class="col-lg-8 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bolt"></i> Acciones Rápidas</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <a href="campeonatos.php?action=new" class="btn btn-primary w-100">
                                            <i class="fas fa-plus"></i><br>
                                            Nuevo Campeonato
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="equipos.php?action=new" class="btn btn-success w-100">
                                            <i class="fas fa-users"></i><br>
                                            Registrar Equipo
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="partidos.php?action=new" class="btn btn-warning w-100">
                                            <i class="fas fa-calendar-plus"></i><br>
                                            Programar Partido
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="jugadores.php?action=import" class="btn btn-info w-100">
                                            <i class="fas fa-file-excel"></i><br>
                                            Importar Jugadores
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="eventos_vivo.php" class="btn btn-danger w-100">
                                            <i class="fas fa-play"></i><br>
                                            Eventos en Vivo
                                        </a>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <a href="descargar_planillas.php?action=generate" class="btn btn-secondary w-100">
                                            <i class="fas fa-file-alt"></i><br>
                                            Generar Planillas
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                   <!-- System Status -->
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-server"></i> Estado del Sistema</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <i class="fas fa-database fa-2x text-success mb-2"></i>
                                            <h6>Base de Datos</h6>
                                            <span class="badge bg-success">Conectada</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <i class="fas fa-folder fa-2x text-success mb-2"></i>
                                            <h6>Archivos</h6>
                                            <span class="badge bg-success">OK</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <i class="fas fa-users fa-2x text-info mb-2"></i>
                                            <h6>Usuarios Online</h6>
                                            <span class="badge bg-info">1</span>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="text-center">
                                            <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                                            <h6>Última Actualización</h6>
                                            <span class="badge bg-warning">Ahora</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh cada 30 segundos (opcional)
        // setInterval(function() {
        //     location.reload();
        // }, 30000);
    </script>
</body>
</html>