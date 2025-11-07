<?php
require_once '../config.php';

$db = Database::getInstance()->getConnection();

// Obtener categorías activas con campeonato
$stmt = $db->query("
    SELECT c.*, camp.nombre as campeonato_nombre
    FROM categorias c
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE c.activa = 1 AND camp.activo = 1
    ORDER BY camp.fecha_inicio DESC, c.nombre ASC
");
$categorias = $stmt->fetchAll();

// Categoría seleccionada
$categoria_id = $_GET['categoria'] ?? ($categorias[0]['id'] ?? null);

// Obtener tabla Fair Play con más detalles
$fairplay = [];
if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT 
            e.id,
            e.nombre AS equipo,
            e.logo,
            e.color_camiseta,
            COUNT(DISTINCT CASE WHEN p.estado = 'finalizado' THEN p.id END) AS partidos_jugados,
            COALESCE(SUM(CASE WHEN ep.tipo_evento = 'amarilla' THEN 1 ELSE 0 END), 0) AS amarillas,
            COALESCE(SUM(CASE 
                WHEN ep.tipo_evento = 'roja' AND ep.observaciones LIKE '%doble%amarilla%' THEN 1 
                ELSE 0 
            END), 0) AS rojas_doble,
            COALESCE(SUM(CASE 
                WHEN ep.tipo_evento = 'roja' AND (ep.observaciones NOT LIKE '%doble%amarilla%' OR ep.observaciones IS NULL) THEN 1 
                ELSE 0 
            END), 0) AS rojas_directa,
            COALESCE(SUM(CASE WHEN ep.tipo_evento = 'roja' THEN 1 ELSE 0 END), 0) AS rojas_total,
            (COALESCE(SUM(CASE WHEN ep.tipo_evento = 'amarilla' THEN 1 ELSE 0 END), 0) * 1 +
             COALESCE(SUM(CASE 
                WHEN ep.tipo_evento = 'roja' AND ep.observaciones LIKE '%doble%amarilla%' THEN 1 
                ELSE 0 
             END), 0) * 3 +
             COALESCE(SUM(CASE 
                WHEN ep.tipo_evento = 'roja' AND (ep.observaciones NOT LIKE '%doble%amarilla%' OR ep.observaciones IS NULL) THEN 1 
                ELSE 0 
             END), 0) * 5) AS puntos,
            ROUND((COALESCE(SUM(CASE WHEN ep.tipo_evento = 'amarilla' THEN 1 ELSE 0 END), 0) * 1 +
             COALESCE(SUM(CASE 
                WHEN ep.tipo_evento = 'roja' AND ep.observaciones LIKE '%doble%amarilla%' THEN 1 
                ELSE 0 
             END), 0) * 3 +
             COALESCE(SUM(CASE 
                WHEN ep.tipo_evento = 'roja' AND (ep.observaciones NOT LIKE '%doble%amarilla%' OR ep.observaciones IS NULL) THEN 1 
                ELSE 0 
             END), 0) * 5) / 
             NULLIF(COUNT(DISTINCT CASE WHEN p.estado = 'finalizado' THEN p.id END), 0), 2)
        FROM equipos e
        LEFT JOIN jugadores j ON j.equipo_id = e.id
        LEFT JOIN eventos_partido ep ON ep.jugador_id = j.id
        LEFT JOIN partidos p ON ep.partido_id = p.id AND p.estado = 'finalizado'
        LEFT JOIN fechas f ON p.fecha_id = f.id
        WHERE e.categoria_id = ? AND e.activo = 1
          AND (f.categoria_id = ? OR f.categoria_id IS NULL)
        GROUP BY e.id, e.nombre, e.logo, e.color_camiseta        
        ORDER BY puntos ASC, amarillas ASC, e.nombre ASC
    ");
    $stmt->execute([$categoria_id, $categoria_id]);
    $fairplay = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Información de categoría actual
$categoria_actual = null;
foreach ($categorias as $cat) {
    if ($cat['id'] == $categoria_id) {
        $categoria_actual = $cat;
        break;
    }
}

// Estadísticas generales
$stats = [];
if ($categoria_id && !empty($fairplay)) {
    $total_amarillas = array_sum(array_column($fairplay, 'amarillas'));
    $total_rojas_doble = array_sum(array_column($fairplay, 'rojas_doble'));
    $total_rojas_directa = array_sum(array_column($fairplay, 'rojas_directa'));
    $total_rojas = array_sum(array_column($fairplay, 'rojas_total'));
    $total_puntos = array_sum(array_column($fairplay, 'puntos'));
    $total_equipos = count($fairplay);
    
    $stats = [
        'total_amarillas' => $total_amarillas,
        'total_rojas_doble' => $total_rojas_doble,
        'total_rojas_directa' => $total_rojas_directa,
        'total_rojas' => $total_rojas,
        'total_puntos' => $total_puntos,
        'total_equipos' => $total_equipos,
    ];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fair Play - Disciplina</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .fairplay-badge {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .badge-gold { background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; box-shadow: 0 4px 8px rgba(255, 215, 0, 0.3); }
        .badge-silver { background: linear-gradient(135deg, #C0C0C0, #A8A8A8); color: #000; box-shadow: 0 4px 8px rgba(192, 192, 192, 0.3); }
        .badge-bronze { background: linear-gradient(135deg, #CD7F32, #8B4513); color: #fff; box-shadow: 0 4px 8px rgba(205, 127, 50, 0.3); }
        .badge-good { background: linear-gradient(135deg, #4CAF50, #45a049); color: #fff; }
        .badge-warning { background: linear-gradient(135deg, #FFC107, #FF9800); color: #000; }
        .badge-danger { background: linear-gradient(135deg, #DC3545, #C82333); color: #fff; }
        
        .fairplay-row {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .fairplay-row:hover {
            background-color: #f0f8ff !important;
            border-left-color: #28a745;
            transform: translateX(5px);
        }
        
        .fairplay-row.best-team {
            background: linear-gradient(90deg, rgba(76, 175, 80, 0.1) 0%, rgba(255, 255, 255, 0) 100%) !important;
            border-left-color: #4CAF50;
        }
        
        .fairplay-row.worst-team {
            background: linear-gradient(90deg, rgba(220, 53, 69, 0.1) 0%, rgba(255, 255, 255, 0) 100%) !important;
            border-left-color: #DC3545;
        }
        
        .stats-card {
            text-align: center;
            padding: 15px;
        }
        .stats-card .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
        }
        .stats-card .label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .trophy-icon {
            font-size: 1.2rem;
            margin-right: 5px;
        }
        
        .card-amarilla {
            display: inline-block;
            width: 20px;
            height: 28px;
            background: linear-gradient(135deg, #FFC107, #FFD54F);
            border: 1px solid #FFA000;
            border-radius: 2px;
            margin: 0 2px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .card-roja {
            display: inline-block;
            width: 20px;
            height: 28px;
            background: linear-gradient(135deg, #DC3545, #E57373);
            border: 1px solid #C62828;
            border-radius: 2px;
            margin: 0 2px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        
        .card-roja-doble {
            display: inline-block;
            width: 20px;
            height: 28px;
            background: linear-gradient(135deg, #FF6B6B, #EE5A6F);
            border: 1px solid #C62828;
            border-radius: 2px;
            margin: 0 2px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            position: relative;
        }
        
        .card-roja-doble::before {
            content: "2x";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 8px;
            color: white;
            font-weight: bold;
        }
        
        .card-roja-directa {
            display: inline-block;
            width: 20px;
            height: 28px;
            background: linear-gradient(135deg, #8B0000, #DC143C);
            border: 1px solid #8B0000;
            border-radius: 2px;
            margin: 0 2px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        
        .podio-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .tabla-fairplay th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        
        .tabla-fairplay td {
            vertical-align: middle;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="../index.php"><i class="fas fa-futbol"></i> Fútbol Manager</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="resultados.php">Resultados</a></li>
                <li class="nav-item"><a class="nav-link" href="tablas.php">Posiciones</a></li>
                <li class="nav-item"><a class="nav-link" href="goleadores.php">Goleadores</a></li>
                <li class="nav-item"><a class="nav-link" href="fixture.php">Fixture</a></li>
                <li class="nav-item"><a class="nav-link" href="sanciones.php">Sanciones</a></li>
                <li class="nav-item"><a class="nav-link" href="historial_equipos.php">Equipos</a></li>
                <li class="nav-item"><a class="nav-link active" href="fairplay.php">Fairplay</a></li>
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
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-star"></i> Tabla Fair Play</h2>
        <div>
            <small class="text-muted">
                <i class="fas fa-info-circle"></i> Amarilla = 1 | Doble Amarilla = 3 | Roja directa = 5
            </small>
        </div>
    </div>

    <?php if (empty($categorias)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No hay categorías disponibles en este momento.
        </div>
    <?php else: ?>
        <!-- Selector de categoría -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">Seleccionar Categoría:</h5>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select" onchange="cambiarCategoria(this.value)">
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $categoria_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['campeonato_nombre'] . ' - ' . $cat['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($categoria_actual && !empty($stats)): ?>
            <!-- Estadísticas -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stats-card">
                                <div class="icon text-warning">
                                    <i class="fas fa-square"></i>
                                </div>
                                <div class="number text-warning"><?= $stats['total_amarillas'] ?></div>
                                <div class="label">Amarillas</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="stats-card">
                                <div class="icon" style="color: #FF8C00;">
                                    <i class="fas fa-square"></i>
                                </div>
                                <div class="number" style="color: #FF8C00;"><?= $stats['total_rojas_doble'] ?></div>
                                <div class="label">Doble Amarilla</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="stats-card">
                                <div class="icon" style="color: #8B0000;">
                                    <i class="fas fa-square"></i>
                                </div>
                                <div class="number" style="color: #8B0000;"><?= $stats['total_rojas_directa'] ?></div>
                                <div class="label">Rojas Directas</div>
                            </div>
                        </div>
                        <div class="col-md-2 col-6 mb-3">
                            <div class="stats-card">
                                <div class="icon text-primary">
                                    <i class="fas fa-calculator"></i>
                                </div>
                                <div class="number text-primary"><?= $stats['total_puntos'] ?></div>
                                <div class="label">Puntos Totales</div>
                            </div>
                        </div>                        
                    </div>
                </div>
            </div>

            <!-- Podio Fair Play (Top 3) -->
            <?php if (count($fairplay) >= 3): ?>
            <div class="row mb-4">
                <!-- 1er Lugar - Más disciplinado -->
                <div class="col-md-4">
                    <div class="podio-card" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #000;">
                        <div class="trophy-icon">
                            <i class="fas fa-trophy" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="mt-3">🥇 Fair Play de Oro</h5>
                        <div class="d-flex align-items-center justify-content-center my-2">
                            <?php if ($fairplay[0]['logo']): ?>
                                <img src="../uploads/<?= htmlspecialchars($fairplay[0]['logo']) ?>" 
                                     alt="Logo" class="me-2 rounded" width="30" height="30">
                            <?php endif; ?>
                            <h6 class="mb-0"><?= htmlspecialchars($fairplay[0]['equipo']) ?></h6>
                        </div>
                        <p class="mb-1"><strong><?= $fairplay[0]['puntos'] ?></strong> puntos</p>
                        <small>
                            <?= $fairplay[0]['amarillas'] ?> <span class="card-amarilla"></span> 
                            <?= $fairplay[0]['rojas_doble'] ?> <span class="card-roja-doble"></span>
                            <?= $fairplay[0]['rojas_directa'] ?> <span class="card-roja-directa"></span>
                        </small>
                    </div>
                </div>

                <!-- 2do Lugar -->
                <div class="col-md-4">
                    <div class="podio-card" style="background: linear-gradient(135deg, #C0C0C0, #A8A8A8); color: #000;">
                        <div class="trophy-icon">
                            <i class="fas fa-medal" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="mt-3">🥈 Fair Play de Plata</h5>
                        <div class="d-flex align-items-center justify-content-center my-2">
                            <?php if ($fairplay[1]['logo']): ?>
                                <img src="../uploads/<?= htmlspecialchars($fairplay[1]['logo']) ?>" 
                                     alt="Logo" class="me-2 rounded" width="30" height="30">
                            <?php endif; ?>
                            <h6 class="mb-0"><?= htmlspecialchars($fairplay[1]['equipo']) ?></h6>
                        </div>
                        <p class="mb-1"><strong><?= $fairplay[1]['puntos'] ?></strong> puntos</p>
                        <small>
                            <?= $fairplay[1]['amarillas'] ?> <span class="card-amarilla"></span> 
                            <?= $fairplay[1]['rojas_doble'] ?> <span class="card-roja-doble"></span>
                            <?= $fairplay[1]['rojas_directa'] ?> <span class="card-roja-directa"></span>
                        </small>
                    </div>
                </div>

                <!-- 3er Lugar -->
                <div class="col-md-4">
                    <div class="podio-card" style="background: linear-gradient(135deg, #CD7F32, #8B4513); color: #fff;">
                        <div class="trophy-icon">
                            <i class="fas fa-medal" style="font-size: 3rem;"></i>
                        </div>
                        <h5 class="mt-3">🥉 Fair Play de Bronce</h5>
                        <div class="d-flex align-items-center justify-content-center my-2">
                            <?php if ($fairplay[2]['logo']): ?>
                                <img src="../uploads/<?= htmlspecialchars($fairplay[2]['logo']) ?>" 
                                     alt="Logo" class="me-2 rounded" width="30" height="30">
                            <?php endif; ?>
                            <h6 class="mb-0"><?= htmlspecialchars($fairplay[2]['equipo']) ?></h6>
                        </div>
                        <p class="mb-1"><strong><?= $fairplay[2]['puntos'] ?></strong> puntos</p>
                        <small>
                            <?= $fairplay[2]['amarillas'] ?> <span class="card-amarilla"></span> 
                            <?= $fairplay[2]['rojas_doble'] ?> <span class="card-roja-doble"></span>
                            <?= $fairplay[2]['rojas_directa'] ?> <span class="card-roja-directa"></span>
                        </small>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tabla Fair Play -->
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-star"></i> 
                        <?= htmlspecialchars($categoria_actual['campeonato_nombre'] . ' - ' . $categoria_actual['nombre']); ?>
                    </h4>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover tabla-fairplay mb-0">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 8%;">Pos</th>
                                <th style="width: 30%;">Equipo</th>
                                <th class="text-center" style="width: 8%;">PJ</th>
                                <th class="text-center" style="width: 10%;">
                                    <span class="card-amarilla"></span><br>
									<small>Amarilla</small>
                                </th>
                                <th class="text-center" style="width: 12%;">
                                    <span class="card-roja-doble"></span><br>
                                    <small>Doble Amarilla</small>
                                </th>
                                <th class="text-center" style="width: 12%;">
                                    <span class="card-roja-directa"></span><br>
                                    <small>Directa</small>
                                </th>
                                <th class="text-center" style="width: 10%;">Puntos</th>                                
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_equipos = count($fairplay);
                            foreach ($fairplay as $i => $f): 
                                $posicion = $i + 1;
                                $es_mejor = ($i == 0);
                                $es_peor = ($i == $total_equipos - 1);
                                
                                // Definir clase de badge según posición
                                $badge_class = 'badge-good';
                                if ($posicion == 1) $badge_class = 'badge-gold';
                                elseif ($posicion == 2) $badge_class = 'badge-silver';
                                elseif ($posicion == 3) $badge_class = 'badge-bronze';                                
                                                                
                                $row_class = '';
                                if ($es_mejor) $row_class = 'best-team';
                                elseif ($es_peor) $row_class = 'worst-team';
                            ?>
                            <tr class="fairplay-row <?= $row_class ?>" style="background-color: <?= $i % 2 == 0 ? '#f9f9f9' : '#ffffff' ?>;">
                                <td class="text-center">
                                    <div class="fairplay-badge <?= $badge_class ?>">
                                        <?= $posicion ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <?php if ($f['logo']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($f['logo']) ?>" 
                                                 alt="Logo" class="me-2 rounded" width="35" height="35" 
                                                 style="object-fit: cover;">
                                        <?php else: ?>
                                            <div class="rounded me-2 d-flex align-items-center justify-content-center" 
                                                 style="width: 35px; height: 35px; background-color: <?= $f['color_camiseta'] ?: '#6c757d' ?>;">
                                                <i class="fas fa-shield-alt text-white"></i>
                                            </div>
                                        <?php endif; ?>
                                        <strong><?= htmlspecialchars($f['equipo']) ?></strong>
                                        <?php if ($es_mejor): ?>
                                            <span class="ms-2 trophy-icon text-warning">🏆</span>
                                        <?php elseif ($es_peor): ?>
                                            <span class="ms-2 trophy-icon text-danger">⚠️</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $f['partidos_jugados'] ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-warning text-dark">
                                        <?= $f['amarillas'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge" style="background-color: #FF6B6B;">
                                        <?= $f['rojas_doble'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge" style="background-color: #8B0000;">
                                        <?= $f['rojas_directa'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <strong class="text-primary"><?= $f['puntos'] ?></strong>
                                </td>                                
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Leyenda -->
            <div class="card mt-3">
                <div class="card-body">
                    <h6><i class="fas fa-info-circle"></i> Leyenda:</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small>
                                <strong>PJ:</strong> Partidos Jugados<br>                                
                            </small>
                        </div>
                        <div class="col-md-6">
                            <small>
                                🏆 = Equipo más disciplinado<br>
                                ⚠️ = Equipo menos disciplinado<br>
                            </small>
                        </div>
                    </div>
                    <hr>
                    <small class="text-muted">
                        <i class="fas fa-calculator"></i> 
                        <strong>Sistema de puntuación:</strong>
                        <br>
                        • Tarjeta Amarilla = 1 punto
                        <br>
                        • Tarjeta Roja por Doble Amarilla = 3 puntos
                        <br>
                        • Tarjeta Roja Directa = 5 puntos
                        <br>
                        <em>Menor puntuación = Mayor disciplina</em>
                        <br><br>
                        <strong class="text-info">Nota importante:</strong>
                    </small>
                </div>
            </div>

        <?php elseif ($categoria_actual): ?>
            <div class="alert alert-info text-center mt-3">
                <i class="fas fa-info-circle fa-3x mb-3"></i>
                <p class="mb-0">No se encontraron datos de Fair Play para esta categoría o no hay partidos finalizados</p>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Footer -->
<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5><i class="fas fa-futbol"></i> Sistema de Campeonatos</h5>
                <p class="text-muted">Tabla Fair Play actualizada en tiempo real</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="text-muted mb-0">© 2024 Todos los derechos reservados</p>
                <small class="text-muted">Actualizado: <?= date('d/m/Y H:i') ?></small>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script>
function cambiarCategoria(categoriaId) {
    window.location.href = 'fairplay.php?categoria=' + categoriaId;
}

// Auto actualización cada 2 minutos
setInterval(function() {
    location.reload();
}, 120000);
</script>
</body>
</html>