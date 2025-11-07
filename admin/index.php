<?php
<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

// DEBUG: Ver partidos en curso
$stmt = $db->query("SELECT * FROM partidos WHERE estado = 'en_curso'");
$debug_partidos = $stmt->fetchAll();
echo "<pre style='background:#f8f9fa;padding:15px;border:1px solid #ddd;'>";
echo "=== DEBUG: Partidos en curso ===\n";
echo "Total encontrados: " . count($debug_partidos) . "\n\n";
print_r($debug_partidos);
echo "</pre>";
// FIN DEBUG

// ... resto del código

// require_once 'config.php';

// $db = Database::getInstance()->getConnection();

// Obtener campeonatos activos
$stmt = $db->query("SELECT * FROM campeonatos WHERE activo = 1 ORDER BY fecha_inicio DESC");
$campeonatos = $stmt->fetchAll();

// Obtener partidos en vivo
$query_vivo = "
    SELECT p.*, 
           el.nombre as equipo_local, 
           el.logo as logo_local,
           ev.nombre as equipo_visitante, 
           ev.logo as logo_visitante,
           c.nombre as cancha,
           COALESCE(p.goles_local, 0) as goles_local,
           COALESCE(p.goles_visitante, 0) as goles_visitante,
           COALESCE(p.minuto_actual, 0) as minuto_actual
    FROM partidos p
    JOIN equipos el ON p.equipo_local_id = el.id
    JOIN equipos ev ON p.equipo_visitante_id = ev.id
    LEFT JOIN canchas c ON p.cancha_id = c.id
    WHERE p.estado = 'en_curso'
    ORDER BY p.fecha_partido DESC, p.hora_partido DESC
";

$stmt = $db->query($query_vivo);
$partidos_vivo = $stmt->fetchAll();

// Obtener próximos partidos
$query_proximos = "
    SELECT p.*, 
           el.nombre as equipo_local, 
           el.logo as logo_local,
           ev.nombre as equipo_visitante, 
           ev.logo as logo_visitante,
           c.nombre as cancha 
    FROM partidos p
    JOIN equipos el ON p.equipo_local_id = el.id
    JOIN equipos ev ON p.equipo_visitante_id = ev.id
    LEFT JOIN canchas c ON p.cancha_id = c.id
    WHERE p.estado IN ('programado', 'sin_asignar') 
    AND p.fecha_partido >= CURDATE()
    ORDER BY p.fecha_partido ASC, p.hora_partido ASC
    LIMIT 10
";

$stmt = $db->query($query_proximos);
$proximos_partidos = $stmt->fetchAll();

// Debug: Descomentar para ver qué está pasando
// echo "<pre>Partidos en vivo: " . count($partidos_vivo) . "</pre>";
// echo "<pre>"; print_r($partidos_vivo); echo "</pre>";
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Campeonatos de Fútbol</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .live-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background-color: #ff0000;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
            margin-left: 10px;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.3; transform: scale(1.1); }
        }
        
        .match-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: #fff;
            transition: all 0.3s;
        }
        
        .match-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .match-card.live {
            border-color: #dc3545;
            background: linear-gradient(135deg, #fff5f5 0%, #ffe5e5 100%);
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.3);
        }
        
        .match-teams {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .team {
            flex: 1;
            text-align: center;
        }
        
        .team img {
            margin-bottom: 8px;
        }
        
        .score {
            font-size: 2.5em;
            font-weight: bold;
            color: #dc3545;
            margin: 0 15px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
        }
        
        .vs {
            color: #6c757d;
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .match-info {
            text-align: center;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }
        
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 80px 0;
            color: white;
            margin-bottom: 40px;
        }
        
        .quick-link {
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s;
            display: block;
            padding: 20px;
            border-radius: 8px;
        }
        
        .quick-link:hover {
            transform: translateY(-5px);
            color: inherit;
            background: #f8f9fa;
        }

        .match-teams-simple {
            flex: 1;
        }

        .match-details {
            text-align: right;
        }

        .championship-card {
            padding: 15px 0;
        }

        .live-pulse {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-futbol"></i> Fútbol Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="public/resultados.php">Resultados</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/tablas.php">Posiciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/goleadores.php">Goleadores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/fixture.php">Fixture</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/sanciones.php">Sanciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/historial_equipos.php">Equipos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/fairplay.php">Fairplay</a>
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

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-white">
                        <i class="fas fa-trophy text-warning"></i>
                        Campeonatos de Fútbol
                    </h1>
                    <p class="lead opacity-75">
                        Seguí todos los campeonatos, resultados en vivo, tablas de posiciones y estadísticas de tus equipos favoritos.
                    </p>
                    <div class="mt-4">
                        <a href="public/tablas.php" class="btn btn-warning btn-lg me-3">
                            <i class="fas fa-list"></i> Ver Tablas
                        </a>
                        <a href="public/resultados.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-play"></i> Resultados
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <i class="fas fa-futbol fa-10x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-5">
        <div class="row">
            <!-- Partidos en Vivo -->
            <?php if (!empty($partidos_vivo)): ?>
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-broadcast-tower"></i> 
                            EN VIVO
                            <span class="live-indicator"></span>
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($partidos_vivo as $partido): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="match-card live">
                                    <div class="match-teams">
                                        <div class="team">
                                            <?php if (!empty($partido['logo_local'])): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($partido['logo_local']); ?>" 
                                                     alt="Logo" style="width: 50px; height: 50px; object-fit: contain;">
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($partido['equipo_local']); ?></strong>
                                            </div>
                                        </div>
                                        <div class="text-center">
                                            <div class="score"><?php echo $partido['goles_local']; ?></div>
                                            <div class="vs">VS</div>
                                            <div class="score"><?php echo $partido['goles_visitante']; ?></div>
                                        </div>
                                        <div class="team">
                                            <?php if (!empty($partido['logo_visitante'])): ?>
                                                <img src="uploads/<?php echo htmlspecialchars($partido['logo_visitante']); ?>" 
                                                     alt="Logo" style="width: 50px; height: 50px; object-fit: contain;">
                                            <?php endif; ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($partido['equipo_visitante']); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="match-info">
                                        <small class="text-danger fw-bold live-pulse">
                                            <i class="fas fa-clock"></i> <?php echo $partido['minuto_actual']; ?>' 
                                        </small>
                                        <?php if (!empty($partido['cancha'])): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($partido['cancha']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Próximos Partidos -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-calendar-alt"></i> Próximos Partidos
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($proximos_partidos)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay partidos programados.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($proximos_partidos as $partido): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="match-teams-simple">
                                            <div class="mb-1">
                                                <strong><?php echo htmlspecialchars($partido['equipo_local']); ?></strong>
                                                <span class="text-muted mx-2">vs</span>
                                                <strong><?php echo htmlspecialchars($partido['equipo_visitante']); ?></strong>
                                            </div>
                                            <?php if ($partido['estado'] == 'sin_asignar'): ?>
                                                <span class="badge bg-warning text-dark">Por confirmar</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="match-details">
                                            <div class="text-end">
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-calendar"></i> 
                                                    <?php echo formatDate($partido['fecha_partido']); ?>
                                                </small>
                                                <?php if (!empty($partido['hora_partido'])): ?>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-clock"></i> 
                                                    <?php echo date('H:i', strtotime($partido['hora_partido'])); ?>
                                                </small>
                                                <?php endif; ?>
                                                <?php if (!empty($partido['cancha'])): ?>
                                                <small class="text-muted d-block">
                                                    <i class="fas fa-map-marker-alt"></i> 
                                                    <?php echo htmlspecialchars($partido['cancha']); ?>
                                                </small>
                                                <?php endif; ?>
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

            <!-- Campeonatos Activos -->
            <div class="col-lg-4 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-trophy"></i> Campeonatos
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if (empty($campeonatos)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay campeonatos activos.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($campeonatos as $campeonato): ?>
                            <div class="championship-card">
                                <h5 class="text-success mb-1">
                                    <?php echo htmlspecialchars($campeonato['nombre']); ?>
                                </h5>
                                <p class="text-muted mb-1">
                                    <small>
                                        <i class="fas fa-calendar"></i> 
                                        Inicio: <?php echo formatDate($campeonato['fecha_inicio']); ?>
                                    </small>
                                </p>
                                <?php if (!empty($campeonato['descripcion'])): ?>
                                <p class="small text-muted mb-0">
                                    <?php echo htmlspecialchars($campeonato['descripcion']); ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <?php if ($campeonato !== end($campeonatos)): ?>
                                <hr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light">
                        <h4 class="mb-0">
                            <i class="fas fa-chart-bar"></i> Accesos Rápidos
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3">
                                <a href="public/tablas.php" class="quick-link">
                                    <i class="fas fa-list fa-3x text-primary mb-2"></i>
                                    <h5>Tablas de Posiciones</h5>
                                    <p class="text-muted small mb-0">Ver clasificaciones</p>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="public/goleadores.php" class="quick-link">
                                    <i class="fas fa-futbol fa-3x text-success mb-2"></i>
                                    <h5>Goleadores</h5>
                                    <p class="text-muted small mb-0">Top scorers</p>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="public/fixture.php" class="quick-link">
                                    <i class="fas fa-calendar-week fa-3x text-warning mb-2"></i>
                                    <h5>Fixture</h5>
                                    <p class="text-muted small mb-0">Calendario completo</p>
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="public/fairplay.php" class="quick-link">
                                    <i class="fas fa-chart-pie fa-3x text-info mb-2"></i>
                                    <h5>Fair Play</h5>
                                    <p class="text-muted small mb-0">Tarjetas y sanciones</p>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-futbol"></i> Sistema de Campeonatos</h5>
                    <p class="text-muted mb-0">Gestión completa de torneos de fútbol</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">© <?php echo date('Y'); ?> Todos los derechos reservados</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    
    <!-- Auto refresh para partidos en vivo -->
    <?php if (!empty($partidos_vivo)): ?>
    <script>
        // Actualizar cada 30 segundos cuando hay partidos en vivo
        setInterval(function() {
            location.reload();
        }, 30000);
        
        console.log('Actualización automática activada - Hay <?php echo count($partidos_vivo); ?> partido(s) en vivo');
    </script>
    <?php endif; ?>
</body>
</html>