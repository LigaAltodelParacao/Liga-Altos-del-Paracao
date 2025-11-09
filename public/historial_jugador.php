<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$jugador_id = $_GET['id'] ?? 0;

// Obtener datos del jugador
$stmt = $db->prepare("
    SELECT j.*, e.nombre as equipo_actual, c.nombre as categoria_actual, camp.nombre as campeonato_actual
    FROM jugadores j
    LEFT JOIN equipos e ON j.equipo_id = e.id
    LEFT JOIN categorias c ON e.categoria_id = c.id
    LEFT JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE j.id = ?
");
$stmt->execute([$jugador_id]);
$jugador = $stmt->fetch();

if (!$jugador) {
    redirect('jugadores.php');
}

// Obtener historial completo del jugador
$stmt = $db->prepare("
    SELECT 
        jeh.*,
        e.nombre as equipo_nombre,
        e.logo as equipo_logo,
        c.nombre as categoria_nombre,
        camp.nombre as campeonato_nombre,
        camp.fecha_inicio as campeonato_fecha_inicio,
        camp.fecha_fin as campeonato_fecha_fin
    FROM jugadores_equipos_historial jeh
    JOIN equipos e ON jeh.equipo_id = e.id
    JOIN categorias c ON e.categoria_id = c.id
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE jeh.jugador_dni = ?
    ORDER BY jeh.fecha_inicio DESC, camp.fecha_inicio DESC
");
$stmt->execute([$jugador['dni']]);
$historial = $stmt->fetchAll();

// Calcular totales
$totales = [
    'campeonatos' => count($historial),
    'equipos' => count(array_unique(array_column($historial, 'equipo_id'))),
    'partidos' => array_sum(array_column($historial, 'partidos_jugados')),
    'goles' => array_sum(array_column($historial, 'goles')),
    'amarillas' => array_sum(array_column($historial, 'amarillas')),
    'rojas' => array_sum(array_column($historial, 'rojas'))
];

function calculateAge($birthDate) {
    return date_diff(date_create($birthDate), date_create('today'))->y;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial de <?php echo htmlspecialchars($jugador['apellido_nombre']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .player-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
            padding-bottom: 2rem;
            border-left: 2px solid #e9ecef;
        }
        .timeline-item:last-child {
            border-left: none;
            padding-bottom: 0;
        }
        .timeline-marker {
            position: absolute;
            left: -8px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: #667eea;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #e9ecef;
        }
        .team-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            border-radius: 5px;
            background: white;
            padding: 5px;
        }
        
        /* Estilos móviles */
        @media (max-width: 768px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            h5 {
                font-size: 1.25rem;
            }
            
            .stat-card {
                padding: 0.75rem;
            }
            
            .stat-number {
                font-size: 1.5rem;
            }
            
            .col-3, .col-6, .col-md-3, .col-md-4, .col-md-5 {
                margin-bottom: 0.75rem;
            }
            
            .chips {
                flex-direction: column;
                align-items: flex-start !important;
            }
            
            .chips .badge {
                margin-bottom: 0.25rem;
            }
            
            .timeline-item {
                padding-left: 1rem;
            }
            
            .col-md-4, .col-md-3 {
                margin-bottom: 0.5rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            img {
                width: 40px !important;
                height: 40px !important;
            }
        }
        
        @media (max-width: 576px) {
            h5 {
                font-size: 1rem;
            }
            
            .stat-number {
                font-size: 1.25rem;
            }
            
            .col-3, .col-6 {
                margin-bottom: 0.5rem;
            }
            
            .timeline-item {
                padding-left: 0.75rem;
                padding-bottom: 0.75rem;
            }
            
            img {
                width: 30px !important;
                height: 30px !important;
            }
            
            .badge {
                font-size: 0.7rem;
                padding: 0.25rem 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-futbol"></i> Fútbol Manager
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="jugadores.php">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <a class="nav-link" href="<?php echo SITE_URL; ?>logout.php">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <!-- Cabecera del jugador -->
        <div class="player-header">
            <div class="row align-items-center">
                <div class="col-md-2 text-center">
                    <?php if ($jugador['foto']): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($jugador['foto']); ?>" 
                             alt="Foto" class="rounded-circle" 
                             style="width: 120px; height: 120px; object-fit: cover; border: 4px solid white;">
                    <?php else: ?>
                        <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                             style="width: 120px; height: 120px;">
                            <i class="fas fa-user fa-4x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-10">
                    <h1 class="mb-2"><?php echo htmlspecialchars($jugador['apellido_nombre']); ?></h1>
                    <div class="row">
                        <div class="col-md-3">
                            <p class="mb-1"><i class="fas fa-id-card"></i> DNI: <?php echo htmlspecialchars($jugador['dni']); ?></p>
                        </div>
                        <div class="col-md-3">
                            <p class="mb-1"><i class="fas fa-birthday-cake"></i> Edad: <?php echo calculateAge($jugador['fecha_nacimiento']); ?> años</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1">
                                <i class="fas fa-shield-alt"></i> 
                                <strong>Equipo Actual:</strong> 
                                <?php if ($jugador['equipo_actual']): ?>
                                    <?php echo htmlspecialchars($jugador['campeonato_actual'] . ' - ' . $jugador['categoria_actual'] . ' - ' . $jugador['equipo_actual']); ?>
                                <?php else: ?>
                                    Sin equipo asignado
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas totales -->
        <div class="row g-4 mb-4">
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totales['campeonatos']; ?></div>
                    <div class="stat-label">Campeonatos</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totales['equipos']; ?></div>
                    <div class="stat-label">Equipos</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totales['partidos']; ?></div>
                    <div class="stat-label">Partidos</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number text-success"><?php echo $totales['goles']; ?></div>
                    <div class="stat-label">Goles</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number text-warning"><?php echo $totales['amarillas']; ?></div>
                    <div class="stat-label">Amarillas</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number text-danger"><?php echo $totales['rojas']; ?></div>
                    <div class="stat-label">Rojas</div>
                </div>
            </div>
        </div>

        <!-- Historial por campeonatos -->
        <div class="card">
            <div class="card-header bg-white">
                <h4 class="mb-0"><i class="fas fa-history"></i> Historial Completo</h4>
            </div>
            <div class="card-body">
                <?php if (empty($historial)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No hay historial registrado</h5>
                        <p class="text-muted">Este jugador aún no tiene datos en ningún campeonato</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach ($historial as $registro): ?>
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row align-items-center">
                                            <div class="col-md-1 text-center">
                                                <?php if ($registro['equipo_logo']): ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($registro['equipo_logo']); ?>" 
                                                         alt="Logo" class="team-logo">
                                                <?php else: ?>
                                                    <div class="team-logo d-flex align-items-center justify-content-center bg-light">
                                                        <i class="fas fa-shield-alt text-secondary"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-5">
                                                <h5 class="mb-1"><?php echo htmlspecialchars($registro['equipo_nombre']); ?></h5>
                                                <p class="text-muted mb-0">
                                                    <small>
                                                        <i class="fas fa-trophy"></i> <?php echo htmlspecialchars($registro['campeonato_nombre']); ?>
                                                        <br>
                                                        <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($registro['categoria_nombre']); ?>
                                                    </small>
                                                </p>
                                            </div>
                                            <div class="col-md-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar-alt"></i> 
                                                    <?php 
                                                    echo date('d/m/Y', strtotime($registro['fecha_inicio']));
                                                    if ($registro['fecha_fin']) {
                                                        echo ' - ' . date('d/m/Y', strtotime($registro['fecha_fin']));
                                                    } else {
                                                        echo ' - <span class="badge bg-success">Actual</span>';
                                                    }
                                                    ?>
                                                </small>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="row g-2">
                                                    <div class="col-3 text-center">
                                                        <div class="badge bg-primary w-100">
                                                            <div style="font-size: 1.2rem;"><?php echo $registro['partidos_jugados']; ?></div>
                                                            <div style="font-size: 0.7rem;">PJ</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3 text-center">
                                                        <div class="badge bg-success w-100">
                                                            <div style="font-size: 1.2rem;"><?php echo $registro['goles']; ?></div>
                                                            <div style="font-size: 0.7rem;">Goles</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3 text-center">
                                                        <div class="badge bg-warning w-100">
                                                            <div style="font-size: 1.2rem;"><?php echo $registro['amarillas']; ?></div>
                                                            <div style="font-size: 0.7rem;">TA</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-3 text-center">
                                                        <div class="badge bg-danger w-100">
                                                            <div style="font-size: 1.2rem;"><?php echo $registro['rojas']; ?></div>
                                                            <div style="font-size: 0.7rem;">TR</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>