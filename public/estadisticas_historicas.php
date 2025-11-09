<?php
require_once '../config.php';

$db = Database::getInstance()->getConnection();

// Obtener el campeonato seleccionado (por defecto todos los campeonatos)
$campeonato_id = isset($_GET['campeonato_id']) ? (int)$_GET['campeonato_id'] : 0;

// Filtro de campeonato para las consultas
$filtro_campeonato = $campeonato_id > 0 ? "AND c.id = $campeonato_id" : "";
$filtro_campeonato_directo = $campeonato_id > 0 ? "WHERE campeonato_id = $campeonato_id" : "";

// ====================================
// ESTADÍSTICAS GENERALES POR EQUIPO
// ====================================

// Equipo con más campeonatos ganados (requiere tabla de ganadores - por implementar)
// Por ahora dejamos preparada la estructura

// Equipo con más partidos jugados
$query_mas_partidos = "
SELECT e.nombre, e.logo,
       COUNT(DISTINCT p.id) as total_partidos
FROM equipos e
JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE p.estado = 'finalizado' $filtro_campeonato
GROUP BY e.id, e.nombre, e.logo
ORDER BY total_partidos DESC
LIMIT 1
";
$equipo_mas_partidos = $db->query($query_mas_partidos)->fetch();

// Equipo con más victorias
$query_mas_victorias = "
SELECT e.nombre, e.logo,
       SUM(CASE 
           WHEN p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante THEN 1
           WHEN p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local THEN 1
           ELSE 0
       END) as total_victorias
FROM equipos e
JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE p.estado = 'finalizado' $filtro_campeonato
GROUP BY e.id, e.nombre, e.logo
ORDER BY total_victorias DESC
LIMIT 1
";
$equipo_mas_victorias = $db->query($query_mas_victorias)->fetch();

// Equipo con más goles a favor
$query_mas_goles_favor = "
SELECT e.nombre, e.logo,
       SUM(CASE 
           WHEN p.equipo_local_id = e.id THEN COALESCE(p.goles_local, 0)
           WHEN p.equipo_visitante_id = e.id THEN COALESCE(p.goles_visitante, 0)
           ELSE 0
       END) as total_goles
FROM equipos e
JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE p.estado = 'finalizado' $filtro_campeonato
GROUP BY e.id, e.nombre, e.logo
ORDER BY total_goles DESC
LIMIT 1
";
$equipo_mas_goles_favor = $db->query($query_mas_goles_favor)->fetch();

// Equipo con más goles en contra
$query_mas_goles_contra = "
SELECT e.nombre, e.logo,
       SUM(CASE 
           WHEN p.equipo_local_id = e.id THEN COALESCE(p.goles_visitante, 0)
           WHEN p.equipo_visitante_id = e.id THEN COALESCE(p.goles_local, 0)
           ELSE 0
       END) as total_goles_contra
FROM equipos e
JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE p.estado = 'finalizado' $filtro_campeonato
GROUP BY e.id, e.nombre, e.logo
ORDER BY total_goles_contra DESC
LIMIT 1
";
$equipo_mas_goles_contra = $db->query($query_mas_goles_contra)->fetch();

// Mayor diferencia de gol histórica
$query_mejor_diferencia = "
SELECT e.nombre, e.logo,
       SUM(CASE 
           WHEN p.equipo_local_id = e.id THEN (COALESCE(p.goles_local, 0) - COALESCE(p.goles_visitante, 0))
           WHEN p.equipo_visitante_id = e.id THEN (COALESCE(p.goles_visitante, 0) - COALESCE(p.goles_local, 0))
           ELSE 0
       END) as diferencia_gol
FROM equipos e
JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE p.estado = 'finalizado' $filtro_campeonato
GROUP BY e.id, e.nombre, e.logo
ORDER BY diferencia_gol DESC
LIMIT 1
";
$equipo_mejor_diferencia = $db->query($query_mejor_diferencia)->fetch();

// ====================================
// ESTADÍSTICAS INDIVIDUALES
// ====================================

// Máximo goleador histórico
$query_goleador_historico = "
SELECT j.apellido_nombre, j.foto, e.nombre as equipo, e.logo,
       COUNT(*) as total_goles
FROM eventos_partido ep
JOIN jugadores j ON ep.jugador_id = j.id
JOIN equipos e ON j.equipo_id = e.id
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE ep.tipo_evento = 'gol' $filtro_campeonato
GROUP BY j.id, j.apellido_nombre, j.foto, e.nombre, e.logo
ORDER BY total_goles DESC
LIMIT 1
";
$goleador_historico = $db->query($query_goleador_historico)->fetch();

// Jugador con más goles en un partido
$query_mas_goles_partido = "
SELECT j.apellido_nombre, e.nombre as equipo, e.logo,
       COUNT(*) as goles_partido, p.fecha_partido,
       el.nombre as rival
FROM eventos_partido ep
JOIN jugadores j ON ep.jugador_id = j.id
JOIN equipos e ON j.equipo_id = e.id
JOIN partidos p ON ep.partido_id = p.id
JOIN equipos el ON (p.equipo_local_id = el.id OR p.equipo_visitante_id = el.id) AND el.id != e.id
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE ep.tipo_evento = 'gol' $filtro_campeonato
GROUP BY j.id, ep.partido_id, j.apellido_nombre, e.nombre, e.logo, p.fecha_partido, el.nombre
ORDER BY goles_partido DESC
LIMIT 1
";
$mas_goles_partido = $db->query($query_mas_goles_partido)->fetch();

// Jugador con más tarjetas amarillas
$query_mas_amarillas = "
SELECT j.apellido_nombre, j.foto, e.nombre as equipo, e.logo,
       COUNT(*) as total_amarillas
FROM eventos_partido ep
JOIN jugadores j ON ep.jugador_id = j.id
JOIN equipos e ON j.equipo_id = e.id
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE ep.tipo_evento = 'amarilla' $filtro_campeonato
GROUP BY j.id, j.apellido_nombre, j.foto, e.nombre, e.logo
ORDER BY total_amarillas DESC
LIMIT 1
";
$mas_amarillas = $db->query($query_mas_amarillas)->fetch();

// Jugador con más tarjetas rojas
$query_mas_rojas = "
SELECT j.apellido_nombre, j.foto, e.nombre as equipo, e.logo,
       COUNT(*) as total_rojas
FROM eventos_partido ep
JOIN jugadores j ON ep.jugador_id = j.id
JOIN equipos e ON j.equipo_id = e.id
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE ep.tipo_evento = 'roja' $filtro_campeonato
GROUP BY j.id, j.apellido_nombre, j.foto, e.nombre, e.logo
ORDER BY total_rojas DESC
LIMIT 1
";
$mas_rojas = $db->query($query_mas_rojas)->fetch();

// ====================================
// RÉCORDS DE PARTIDOS
// ====================================

// Mayor goleada en la historia
$query_mayor_goleada = "
SELECT 
    el.nombre as equipo_ganador, el.logo as logo_ganador,
    ev.nombre as equipo_perdedor, ev.logo as logo_perdedor,
    p.goles_local, p.goles_visitante,
    ABS(p.goles_local - p.goles_visitante) as diferencia,
    p.fecha_partido,
    c.nombre as cancha
FROM partidos p
JOIN equipos el ON p.equipo_local_id = el.id
JOIN equipos ev ON p.equipo_visitante_id = ev.id
JOIN categorias cat ON el.categoria_id = cat.id
JOIN campeonatos camp ON cat.campeonato_id = camp.id
LEFT JOIN canchas c ON p.cancha_id = c.id
WHERE p.estado = 'finalizado' 
AND p.goles_local IS NOT NULL 
AND p.goles_visitante IS NOT NULL
$filtro_campeonato
ORDER BY diferencia DESC, (p.goles_local + p.goles_visitante) DESC
LIMIT 1
";
$mayor_goleada = $db->query($query_mayor_goleada)->fetch();

// Partido con más goles
$query_mas_goles_partido_total = "
SELECT 
    el.nombre as equipo_local, el.logo as logo_local,
    ev.nombre as equipo_visitante, ev.logo as logo_visitante,
    p.goles_local, p.goles_visitante,
    (p.goles_local + p.goles_visitante) as total_goles,
    p.fecha_partido,
    c.nombre as cancha
FROM partidos p
JOIN equipos el ON p.equipo_local_id = el.id
JOIN equipos ev ON p.equipo_visitante_id = ev.id
JOIN categorias cat ON el.categoria_id = cat.id
JOIN campeonatos camp ON cat.campeonato_id = camp.id
LEFT JOIN canchas c ON p.cancha_id = c.id
WHERE p.estado = 'finalizado' 
AND p.goles_local IS NOT NULL 
AND p.goles_visitante IS NOT NULL
$filtro_campeonato
ORDER BY total_goles DESC
LIMIT 1
";
$mas_goles_partido_total = $db->query($query_mas_goles_partido_total)->fetch();

// Partido con más expulsados
$query_mas_expulsados = "
SELECT 
    el.nombre as equipo_local, el.logo as logo_local,
    ev.nombre as equipo_visitante, ev.logo as logo_visitante,
    COUNT(ep.id) as total_expulsiones,
    p.fecha_partido,
    p.goles_local, p.goles_visitante
FROM partidos p
JOIN equipos el ON p.equipo_local_id = el.id
JOIN equipos ev ON p.equipo_visitante_id = ev.id
JOIN categorias cat ON el.categoria_id = cat.id
JOIN campeonatos camp ON cat.campeonato_id = camp.id
JOIN eventos_partido ep ON p.id = ep.partido_id
WHERE ep.tipo_evento = 'roja'
AND p.estado = 'finalizado'
$filtro_campeonato
GROUP BY p.id
ORDER BY total_expulsiones DESC
LIMIT 1
";
$mas_expulsados = $db->query($query_mas_expulsados)->fetch();

// ====================================
// ESTADÍSTICAS ADICIONALES
// ====================================

// Top 5 Goleadores Históricos
$query_top_goleadores = "
SELECT j.apellido_nombre, j.foto, e.nombre as equipo, e.logo,
       COUNT(*) as total_goles
FROM eventos_partido ep
JOIN jugadores j ON ep.jugador_id = j.id
JOIN equipos e ON j.equipo_id = e.id
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE ep.tipo_evento = 'gol' $filtro_campeonato
GROUP BY j.id, j.apellido_nombre, j.foto, e.nombre, e.logo
ORDER BY total_goles DESC
LIMIT 5
";
$top_goleadores = $db->query($query_top_goleadores)->fetchAll();

// Obtener lista de campeonatos para el filtro
$query_campeonatos = "SELECT id, nombre FROM campeonatos ORDER BY fecha_inicio DESC";
$campeonatos = $db->query($query_campeonatos)->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas Históricas - Liga de Fútbol</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        
        .stats-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        
        .stat-card.gold {
            background: #fff9e6;
            border-color: #ffc107;
        }
        
        .stat-card.silver {
            background: #f8f9fa;
            border-color: #6c757d;
        }
        
        .stat-card.bronze {
            background: #fff3e6;
            border-color: #fd7e14;
        }
        
        .stat-icon {
            font-size: 2em;
            margin-bottom: 8px;
            color: #28a745;
        }
        
        .stat-value {
            font-size: 1.8em;
            font-weight: bold;
            color: #333;
            margin: 8px 0;
        }
        
        .stat-label {
            font-size: 0.75em;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }
        
        .team-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin: 8px auto;
            display: block;
        }
        
        .team-name {
            font-size: 0.9em;
            font-weight: 600;
            margin-top: 8px;
            color: #333;
        }
        
        .section-title {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid #28a745;
        }
        
        .record-item {
            background: #fff;
            border: 1px solid #dee2e6;
            border-left: 3px solid #28a745;
            padding: 15px;
            margin-bottom: 12px;
            border-radius: 5px;
        }
        
        .badge-custom {
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .vs-separator {
            color: #6c757d;
            font-weight: bold;
            margin: 0 8px;
        }
        
        .filter-section {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border: 1px solid #dee2e6;
        }
        
        .page-header {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
            border: 1px solid #dee2e6;
        }
        
        .navbar {
            margin-bottom: 0;
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
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../index.php"><i class="fas fa-home"></i> Inicio</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tablas.php"><i class="fas fa-table"></i> Tablas</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="resultados.php"><i class="fas fa-list"></i> Resultados</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="estadisticas_historicas.php">
                            <i class="fas fa-chart-line"></i> Estadísticas
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <!-- Filtro de Campeonato -->
        <div class="filter-section">
            <form method="GET" class="row align-items-center">
                <div class="col-md-4">
                    <label class="form-label"><strong>Filtrar por Campeonato:</strong></label>
                </div>
                <div class="col-md-6">
                    <select name="campeonato_id" class="form-select" onchange="this.form.submit()">
                        <option value="0" <?php echo $campeonato_id == 0 ? 'selected' : ''; ?>>
                            Todos los Campeonatos (Histórico)
                        </option>
                        <?php foreach ($campeonatos as $camp): ?>
                            <option value="<?php echo $camp['id']; ?>" 
                                    <?php echo $campeonato_id == $camp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($camp['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Título Principal -->
        <div class="page-header">
            <h1 class="mb-2">
                <i class="fas fa-chart-bar text-success"></i> 
                Estadísticas Históricas
            </h1>
            <p class="text-muted mb-0">
                Récords y estadísticas de todos los tiempos
            </p>
        </div>

        <!-- ESTADÍSTICAS GENERALES POR EQUIPO -->
        <div class="stats-container">
            <h2 class="section-title">
                <i class="fas fa-users"></i> Rendimiento Global por Equipo
            </h2>
            
            <div class="row">
                <!-- Equipo con más partidos jugados -->
                <?php if ($equipo_mas_partidos): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-calendar-alt stat-icon text-primary"></i>
                        <?php if ($equipo_mas_partidos['logo']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($equipo_mas_partidos['logo']); ?>" 
                                 class="team-logo" alt="Logo">
                        <?php endif; ?>
                        <div class="stat-value text-primary"><?php echo $equipo_mas_partidos['total_partidos']; ?></div>
                        <div class="stat-label">Partidos Jugados</div>
                        <div class="team-name"><?php echo htmlspecialchars($equipo_mas_partidos['nombre']); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Equipo con más victorias -->
                <?php if ($equipo_mas_victorias): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="stat-card gold text-center">
                        <i class="fas fa-trophy stat-icon text-warning"></i>
                        <?php if ($equipo_mas_victorias['logo']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($equipo_mas_victorias['logo']); ?>" 
                                 class="team-logo" alt="Logo">
                        <?php endif; ?>
                        <div class="stat-value text-warning"><?php echo $equipo_mas_victorias['total_victorias']; ?></div>
                        <div class="stat-label">Victorias</div>
                        <div class="team-name"><?php echo htmlspecialchars($equipo_mas_victorias['nombre']); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Equipo con más goles a favor -->
                <?php if ($equipo_mas_goles_favor): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-futbol stat-icon text-success"></i>
                        <?php if ($equipo_mas_goles_favor['logo']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($equipo_mas_goles_favor['logo']); ?>" 
                                 class="team-logo" alt="Logo">
                        <?php endif; ?>
                        <div class="stat-value text-success"><?php echo $equipo_mas_goles_favor['total_goles']; ?></div>
                        <div class="stat-label">Goles a Favor</div>
                        <div class="team-name"><?php echo htmlspecialchars($equipo_mas_goles_favor['nombre']); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Mejor diferencia de gol -->
                <?php if ($equipo_mejor_diferencia): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="stat-card silver text-center">
                        <i class="fas fa-chart-line stat-icon text-info"></i>
                        <?php if ($equipo_mejor_diferencia['logo']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($equipo_mejor_diferencia['logo']); ?>" 
                                 class="team-logo" alt="Logo">
                        <?php endif; ?>
                        <div class="stat-value text-info">+<?php echo $equipo_mejor_diferencia['diferencia_gol']; ?></div>
                        <div class="stat-label">Diferencia de Gol</div>
                        <div class="team-name"><?php echo htmlspecialchars($equipo_mejor_diferencia['nombre']); ?></div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Equipo con más goles en contra -->
                <?php if ($equipo_mas_goles_contra): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-exclamation-triangle stat-icon text-danger"></i>
                        <?php if ($equipo_mas_goles_contra['logo']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($equipo_mas_goles_contra['logo']); ?>" 
                                 class="team-logo" alt="Logo">
                        <?php endif; ?>
                        <div class="stat-value text-danger"><?php echo $equipo_mas_goles_contra['total_goles_contra']; ?></div>
                        <div class="stat-label">Goles en Contra</div>
                        <div class="team-name"><?php echo htmlspecialchars($equipo_mas_goles_contra['nombre']); ?></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ESTADÍSTICAS INDIVIDUALES -->
        <div class="stats-container">
            <h2 class="section-title">
                <i class="fas fa-user"></i> Récords Individuales
            </h2>
            
            <div class="row">
                <!-- Máximo goleador histórico -->
                <?php if ($goleador_historico): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card gold text-center">
                        <i class="fas fa-crown stat-icon text-warning"></i>
                        <?php if ($goleador_historico['logo']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($goleador_historico['logo']); ?>" 
                                 class="team-logo" alt="Logo">
                        <?php endif; ?>
                        <div class="stat-value text-warning"><?php echo $goleador_historico['total_goles']; ?></div>
                        <div class="stat-label">Goleador Histórico</div>
                        <div class="team-name" style="font-size: 0.85em;"><?php echo htmlspecialchars($goleador_historico['apellido_nombre']); ?></div>
                        <small class="text-muted" style="font-size: 0.75em;"><?php echo htmlspecialchars($goleador_historico['equipo']); ?></small>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Más goles en un partido -->
                <?php if ($mas_goles_partido): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card text-center">
                        <i class="fas fa-fire stat-icon text-danger"></i>
                        <?php if ($mas_goles_partido['logo']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($mas_goles_partido['logo']); ?>" 
                                 class="team-logo" alt="Logo">
                        <?php endif; ?>
                        <div class="stat-value text-danger"><?php echo $mas_goles_partido['goles_partido']; ?></div>
                        <div class="stat-label">Goles en Partido</div>
                        <div class="team-name" style="font-size: 0.85em;"><?php echo htmlspecialchars($mas_goles_partido['apellido_nombre']); ?></div>
                        <small class="text-muted" style="font-size: 0.7em;">
                            vs <?php echo htmlspecialchars($mas_goles_partido['rival']); ?>
                        </small>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Más amarillas -->
                <?php if ($mas_amarillas): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card text-center" style="background: #fffbf0; border-color: #ffc107;">
                        <i class="fas fa-square stat-icon" style="color: #ffc107;"></i>
                        <?php if ($mas_amarillas['logo']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($mas_amarillas['logo']); ?>" 
                                 class="team-logo" alt="Logo">
                        <?php endif; ?>
                        <div class="stat-value" style="color: #ffc107;"><?php echo $mas_amarillas['total_amarillas']; ?></div>
                        <div class="stat-label">Tarjetas Amarillas</div>
                        <div class="team-name" style="font-size: 0.85em;"><?php echo htmlspecialchars($mas_amarillas['apellido_nombre']); ?></div>
                        <small class="text-muted" style="font-size: 0.75em;"><?php echo htmlspecialchars($mas_amarillas['equipo']); ?></small>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Más rojas -->
                <?php if ($mas_rojas): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="stat-card text-center" style="background: #fff5f5; border-color: #dc3545;">
                        <i class="fas fa-square stat-icon text-danger"></i>
                        <?php if ($mas_rojas['logo']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($mas_rojas['logo']); ?>" 
                                 class="team-logo" alt="Logo">
                        <?php endif; ?>
                        <div class="stat-value text-danger"><?php echo $mas_rojas['total_rojas']; ?></div>
                        <div class="stat-label">Tarjetas Rojas</div>
                        <div class="team-name" style="font-size: 0.85em;"><?php echo htmlspecialchars($mas_rojas['apellido_nombre']); ?></div>
                        <small class="text-muted" style="font-size: 0.75em;"><?php echo htmlspecialchars($mas_rojas['equipo']); ?></small>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- TOP 5 GOLEADORES -->
        <?php if (!empty($top_goleadores)): ?>
        <div class="stats-container">
            <h2 class="section-title">
                <i class="fas fa-medal"></i> Top 5 Goleadores Históricos
            </h2>
            
            <div class="row">
                <?php foreach ($top_goleadores as $index => $goleador): ?>
                <div class="col-md-12">
                    <div class="record-item d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <?php if ($index == 0): ?>
                                    <i class="fas fa-medal fa-2x" style="color: #FFD700;"></i>
                                <?php elseif ($index == 1): ?>
                                    <i class="fas fa-medal fa-2x" style="color: #C0C0C0;"></i>
                                <?php elseif ($index == 2): ?>
                                    <i class="fas fa-medal fa-2x" style="color: #CD7F32;"></i>
                                <?php else: ?>
                                    <span class="badge bg-secondary" style="font-size: 1.2em;"><?php echo $index + 1; ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($goleador['logo']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($goleador['logo']); ?>" 
                                     style="width: 40px; height: 40px; object-fit: contain; margin-right: 15px;" alt="Logo">
                            <?php endif; ?>
                            <div>
                                <h5 class="mb-0"><?php echo htmlspecialchars($goleador['apellido_nombre']); ?></h5>
                                <small class="text-muted"><?php echo htmlspecialchars($goleador['equipo']); ?></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-0 text-success">
                                <i class="fas fa-futbol"></i> <?php echo $goleador['total_goles']; ?>
                            </h3>
                            <small class="text-muted">goles</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- RÉCORDS DE PARTIDOS -->
        <div class="stats-container">
            <h2 class="section-title">
                <i class="fas fa-fire"></i> Récords de Partidos
            </h2>
            
            <!-- Mayor Goleada -->
            <?php if ($mayor_goleada): ?>
            <div class="record-item">
                <h5 class="mb-3"><i class="fas fa-bolt text-warning"></i> Mayor Goleada</h5>
                <div class="row align-items-center">
                    <div class="col-md-5 text-center">
                        <?php if ($mayor_goleada['logo_ganador']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($mayor_goleada['logo_ganador']); ?>" 
                                 style="width: 50px; height: 50px; object-fit: contain; margin-bottom: 10px;" alt="Logo">
                        <?php endif; ?>
                        <h5><?php echo htmlspecialchars($mayor_goleada['equipo_ganador']); ?></h5>
                    </div>
                    <div class="col-md-2 text-center">
                        <h2 class="text-success mb-0">
                            <?php echo $mayor_goleada['goles_local']; ?>
                            <span class="vs-separator">-</span>
                            <?php echo $mayor_goleada['goles_visitante']; ?>
                        </h2>
                        <span class="badge badge-custom bg-danger">
                            Diferencia: <?php echo $mayor_goleada['diferencia']; ?> goles
                        </span>
                    </div>
                    <div class="col-md-5 text-center">
                        <?php if ($mayor_goleada['logo_perdedor']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($mayor_goleada['logo_perdedor']); ?>" 
                                 style="width: 50px; height: 50px; object-fit: contain; margin-bottom: 10px;" alt="Logo">
                        <?php endif; ?>
                        <h5><?php echo htmlspecialchars($mayor_goleada['equipo_perdedor']); ?></h5>
                    </div>
                </div>
                <p class="text-center text-muted mt-3 mb-0">
                    <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($mayor_goleada['fecha_partido'])); ?>
                    <?php if ($mayor_goleada['cancha']): ?>
                        | <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($mayor_goleada['cancha']); ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Partido con más goles -->
            <?php if ($mas_goles_partido_total): ?>
            <div class="record-item">
                <h5 class="mb-3"><i class="fas fa-trophy text-success"></i> Partido con Más Goles</h5>
                <div class="row align-items-center">
                    <div class="col-md-5 text-center">
                        <?php if ($mas_goles_partido_total['logo_local']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($mas_goles_partido_total['logo_local']); ?>" 
                                 style="width: 50px; height: 50px; object-fit: contain; margin-bottom: 10px;" alt="Logo">
                        <?php endif; ?>
                        <h5><?php echo htmlspecialchars($mas_goles_partido_total['equipo_local']); ?></h5>
                    </div>
                    <div class="col-md-2 text-center">
                        <h2 class="text-primary mb-0">
                            <?php echo $mas_goles_partido_total['goles_local']; ?>
                            <span class="vs-separator">-</span>
                            <?php echo $mas_goles_partido_total['goles_visitante']; ?>
                        </h2>
                        <span class="badge badge-custom bg-success">
                            Total: <?php echo $mas_goles_partido_total['total_goles']; ?> goles
                        </span>
                    </div>
                    <div class="col-md-5 text-center">
                        <?php if ($mas_goles_partido_total['logo_visitante']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($mas_goles_partido_total['logo_visitante']); ?>" 
                                 style="width: 50px; height: 50px; object-fit: contain; margin-bottom: 10px;" alt="Logo">
                        <?php endif; ?>
                        <h5><?php echo htmlspecialchars($mas_goles_partido_total['equipo_visitante']); ?></h5>
                    </div>
                </div>
                <p class="text-center text-muted mt-3 mb-0">
                    <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($mas_goles_partido_total['fecha_partido'])); ?>
                    <?php if ($mas_goles_partido_total['cancha']): ?>
                        | <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($mas_goles_partido_total['cancha']); ?>
                    <?php endif; ?>
                </p>
            </div>
            <?php endif; ?>

            <!-- Partido con más expulsados -->
            <?php if ($mas_expulsados): ?>
            <div class="record-item">
                <h5 class="mb-3"><i class="fas fa-exclamation-triangle text-danger"></i> Partido con Más Expulsiones</h5>
                <div class="row align-items-center">
                    <div class="col-md-5 text-center">
                        <?php if ($mas_expulsados['logo_local']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($mas_expulsados['logo_local']); ?>" 
                                 style="width: 50px; height: 50px; object-fit: contain; margin-bottom: 10px;" alt="Logo">
                        <?php endif; ?>
                        <h5><?php echo htmlspecialchars($mas_expulsados['equipo_local']); ?></h5>
                    </div>
                    <div class="col-md-2 text-center">
                        <h2 class="text-muted mb-0">
                            <?php echo $mas_expulsados['goles_local']; ?>
                            <span class="vs-separator">-</span>
                            <?php echo $mas_expulsados['goles_visitante']; ?>
                        </h2>
                        <span class="badge badge-custom bg-danger">
                            <?php echo $mas_expulsados['total_expulsiones']; ?> expulsiones
                        </span>
                    </div>
                    <div class="col-md-5 text-center">
                        <?php if ($mas_expulsados['logo_visitante']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($mas_expulsados['logo_visitante']); ?>" 
                                 style="width: 50px; height: 50px; object-fit: contain; margin-bottom: 10px;" alt="Logo">
                        <?php endif; ?>
                        <h5><?php echo htmlspecialchars($mas_expulsados['equipo_visitante']); ?></h5>
                    </div>
                </div>
                <p class="text-center text-muted mt-3 mb-0">
                    <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($mas_expulsados['fecha_partido'])); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Botón para volver -->
        <div class="text-center mb-4">
            <a href="../index.php" class="btn btn-lg btn-primary">
                <i class="fas fa-arrow-left"></i> Volver al Inicio
            </a>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>