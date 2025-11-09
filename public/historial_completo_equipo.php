<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config.php';

if (!function_exists('calculateAge')) {
    function calculateAge($fechaNacimiento) {
        if (!$fechaNacimiento) return '?';
        $dob = new DateTime($fechaNacimiento);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        return $age;
    }
}

$db = Database::getInstance()->getConnection();

$nombre_equipo = $_GET['equipo'] ?? '';
if (empty($nombre_equipo)) {
    redirect('historial_equipos.php');
}

$stmt = $db->prepare("
    SELECT e.*, c.nombre as categoria, camp.nombre as campeonato,
           camp.fecha_inicio, camp.fecha_fin, camp.id as campeonato_id,
           c.id as categoria_id
    FROM equipos e
    JOIN categorias c ON e.categoria_id = c.id
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE LOWER(TRIM(e.nombre)) = LOWER(TRIM(?))
    ORDER BY camp.fecha_inicio DESC
");
$stmt->execute([$nombre_equipo]);
$instancias_equipo = $stmt->fetchAll();

if (empty($instancias_equipo)) {
    redirect('historial_equipos.php');
}

$stats_totales = [
    'partidos_jugados' => 0,
    'partidos_ganados' => 0,
    'partidos_empatados' => 0,
    'partidos_perdidos' => 0,
    'goles_favor' => 0,
    'goles_contra' => 0,
    'torneos_participados' => count($instancias_equipo),
    'campeonatos_ganados' => 0
];

$stats_por_torneo = [];
foreach ($instancias_equipo as $instancia) {
    $equipo_id = $instancia['id'];
    $categoria_id = $instancia['categoria_id'];

    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as partidos_jugados,
            SUM(CASE 
                WHEN (p.equipo_local_id = ? AND p.goles_local > p.goles_visitante) OR
                     (p.equipo_visitante_id = ? AND p.goles_visitante > p.goles_local)
                THEN 1 ELSE 0 END) as ganados,
            SUM(CASE 
                WHEN p.goles_local = p.goles_visitante
                THEN 1 ELSE 0 END) as empatados,
            SUM(CASE 
                WHEN (p.equipo_local_id = ? AND p.goles_local < p.goles_visitante) OR
                     (p.equipo_visitante_id = ? AND p.goles_visitante < p.goles_local)
                THEN 1 ELSE 0 END) as perdidos,
            SUM(CASE 
                WHEN p.equipo_local_id = ? THEN p.goles_local
                ELSE p.goles_visitante END) as goles_favor,
            SUM(CASE 
                WHEN p.equipo_local_id = ? THEN p.goles_visitante
                ELSE p.goles_local END) as goles_contra
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        WHERE (p.equipo_local_id = ? OR p.equipo_visitante_id = ?)
        AND p.estado = 'finalizado'
        AND f.categoria_id = ?
    ");
    $stmt->execute([
        $equipo_id, $equipo_id,
        $equipo_id, $equipo_id,
        $equipo_id, $equipo_id,
        $equipo_id, $equipo_id,
        $categoria_id
    ]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("
        SELECT COUNT(*) as total_jugadores
        FROM jugadores
        WHERE equipo_id = ? AND activo = 1
    ");
    $stmt->execute([$equipo_id]);
    $total_jugadores = (int)$stmt->fetchColumn();

    $stmt = $db->prepare("
        SELECT j.apellido_nombre, COUNT(*) as goles
        FROM eventos_partido ev
        JOIN jugadores j ON ev.jugador_id = j.id
        JOIN partidos p ON ev.partido_id = p.id
        JOIN fechas f ON p.fecha_id = f.id
        WHERE j.equipo_id = ? AND ev.tipo_evento = 'gol' AND f.categoria_id = ?
        GROUP BY j.id
        ORDER BY goles DESC
        LIMIT 3
    ");
    $stmt->execute([$equipo_id, $categoria_id]);
    $goleadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stats_por_torneo[] = [
        'instancia' => $instancia,
        'stats' => $stats,
        'jugadores' => $total_jugadores,
        'goleadores' => $goleadores
    ];

    $stats_totales['partidos_jugados'] += (int)($stats['partidos_jugados'] ?? 0);
    $stats_totales['partidos_ganados'] += (int)($stats['ganados'] ?? 0);
    $stats_totales['partidos_empatados'] += (int)($stats['empatados'] ?? 0);
    $stats_totales['partidos_perdidos'] += (int)($stats['perdidos'] ?? 0);
    $stats_totales['goles_favor'] += (int)($stats['goles_favor'] ?? 0);
    $stats_totales['goles_contra'] += (int)($stats['goles_contra'] ?? 0);
}

$stats_totales['diferencia_gol'] = $stats_totales['goles_favor'] - $stats_totales['goles_contra'];
$stats_totales['porcentaje_victorias'] = $stats_totales['partidos_jugados'] > 0
    ? round(($stats_totales['partidos_ganados'] / $stats_totales['partidos_jugados']) * 100, 1)
    : 0;

// Obtener todos los jugadores que pasaron por el equipo (incluyendo jugadores que jugaron partidos con ese equipo)
// Primero obtener todos los IDs de equipos con ese nombre
$stmt = $db->prepare("
    SELECT DISTINCT e.id as equipo_id
    FROM equipos e
    WHERE LOWER(TRIM(e.nombre)) = LOWER(TRIM(?))
");
$stmt->execute([$nombre_equipo]);
$equipos_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

$jugadores_historicos = [];
if (!empty($equipos_ids)) {
    // Crear placeholders para la consulta IN
    $placeholders = implode(',', array_fill(0, count($equipos_ids), '?'));
    
    // Obtener jugadores que están actualmente en esos equipos
    $stmt = $db->prepare("
        SELECT DISTINCT j.id, j.dni, j.apellido_nombre, j.fecha_nacimiento, j.foto,
               camp.nombre as campeonato,
               c.nombre as categoria
        FROM jugadores j
        JOIN equipos e ON j.equipo_id = e.id
        JOIN categorias c ON e.categoria_id = c.id
        JOIN campeonatos camp ON c.campeonato_id = camp.id
        WHERE e.id IN ($placeholders)
        ORDER BY j.apellido_nombre ASC
    ");
    $stmt->execute($equipos_ids);
    $jugadores_actuales = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener jugadores que jugaron partidos PARA esos equipos
    // Estrategia: usar jugadores_partido para identificar jugadores que jugaron partidos del equipo
    // Luego verificamos que el jugador jugó en múltiples partidos del equipo (para filtrar oponentes)
    // O que tiene eventos en partidos del equipo (confirmando que jugó para el equipo)
    $stmt = $db->prepare("
        SELECT DISTINCT j.id, j.dni, j.apellido_nombre, j.fecha_nacimiento, j.foto,
               camp.nombre as campeonato,
               c.nombre as categoria
        FROM jugadores j
        JOIN jugadores_partido jp ON j.id = jp.jugador_id
        JOIN partidos p ON jp.partido_id = p.id
        JOIN fechas f ON p.fecha_id = f.id
        JOIN categorias c ON f.categoria_id = c.id
        JOIN campeonatos camp ON c.campeonato_id = camp.id
        WHERE (p.equipo_local_id IN ($placeholders) OR p.equipo_visitante_id IN ($placeholders))
          AND j.id NOT IN (
              SELECT DISTINCT j2.id 
              FROM jugadores j2
              WHERE j2.equipo_id IN ($placeholders)
          )
          AND (
              -- El jugador jugó en múltiples partidos del equipo (probablemente jugó para el equipo)
              (SELECT COUNT(DISTINCT p2.id) 
               FROM partidos p2
               JOIN jugadores_partido jp2 ON jp2.jugador_id = j.id AND jp2.partido_id = p2.id
               WHERE (p2.equipo_local_id IN ($placeholders) OR p2.equipo_visitante_id IN ($placeholders))
              ) > 1
              OR
              -- O el jugador tiene eventos en partidos del equipo (confirmando que jugó para el equipo)
              EXISTS (
                  SELECT 1 FROM eventos_partido ep 
                  JOIN partidos p3 ON ep.partido_id = p3.id
                  WHERE ep.jugador_id = j.id
                    AND (p3.equipo_local_id IN ($placeholders) OR p3.equipo_visitante_id IN ($placeholders))
              )
              OR
              -- O el jugador tiene su equipo_id actual que coincide con el equipo del partido
              (p.equipo_local_id IN ($placeholders) AND j.equipo_id = p.equipo_local_id)
              OR
              (p.equipo_visitante_id IN ($placeholders) AND j.equipo_id = p.equipo_visitante_id)
          )
        ORDER BY j.apellido_nombre ASC
    ");
    // Contar cuántas veces se usa $placeholders en la consulta:
    // 1. p.equipo_local_id IN ($placeholders) - línea 178
    // 2. p.equipo_visitante_id IN ($placeholders) - línea 178
    // 3. j2.equipo_id IN ($placeholders) - línea 182
    // 4. p2.equipo_local_id IN ($placeholders) - línea 189
    // 5. p2.equipo_visitante_id IN ($placeholders) - línea 189
    // 6. p3.equipo_local_id IN ($placeholders) - línea 197
    // 7. p3.equipo_visitante_id IN ($placeholders) - línea 197
    // 8. p.equipo_local_id IN ($placeholders) - línea 201
    // 9. p.equipo_visitante_id IN ($placeholders) - línea 203
    // Total: 9 veces (cada uno necesita count($equipos_ids) parámetros)
    $params = array_merge(
        $equipos_ids,  // 1: línea 178 (p.equipo_local_id)
        $equipos_ids,  // 2: línea 178 (p.equipo_visitante_id)
        $equipos_ids,  // 3: línea 182 (j2.equipo_id NOT IN)
        $equipos_ids,  // 4: línea 189 (p2.equipo_local_id)
        $equipos_ids,  // 5: línea 189 (p2.equipo_visitante_id)
        $equipos_ids,  // 6: línea 197 (p3.equipo_local_id)
        $equipos_ids,  // 7: línea 197 (p3.equipo_visitante_id)
        $equipos_ids,  // 8: línea 201 (p.equipo_local_id)
        $equipos_ids   // 9: línea 203 (p.equipo_visitante_id)
    );
    $stmt->execute($params);
    $jugadores_partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combinar y eliminar duplicados usando DNI como clave única
    // Mantener un array de torneos para cada jugador
    $jugadores_unicos = [];
    foreach (array_merge($jugadores_actuales, $jugadores_partidos) as $jugador) {
        $dni = $jugador['dni'];
        $torneo_key = $jugador['campeonato'] . ' - ' . $jugador['categoria'];
        
        if (!isset($jugadores_unicos[$dni])) {
            $jugador['torneos_array'] = [$torneo_key];
            $jugadores_unicos[$dni] = $jugador;
        } else {
            // Si ya existe, agregar el torneo si es diferente
            if (!in_array($torneo_key, $jugadores_unicos[$dni]['torneos_array'])) {
                $jugadores_unicos[$dni]['torneos_array'][] = $torneo_key;
            }
        }
    }
    
    // Convertir de nuevo a array indexado y mantener el formato para compatibilidad
    $jugadores_historicos = [];
    foreach ($jugadores_unicos as $jugador) {
        // Mantener torneos_array para uso en la vista
        $jugador['campeonato'] = $jugador['torneos_array'][0] ?? '';
        $jugador['categoria'] = ''; // Ya está incluido en campeonato
        $jugadores_historicos[] = $jugador;
    }
}

$stmt = $db->prepare("
    SELECT j.apellido_nombre, j.foto, COUNT(*) as total_goles
    FROM eventos_partido ev
    JOIN jugadores j ON ev.jugador_id = j.id
    JOIN equipos e ON j.equipo_id = e.id
    WHERE LOWER(TRIM(e.nombre)) = LOWER(TRIM(?)) AND ev.tipo_evento = 'gol'
    GROUP BY j.id
    ORDER BY total_goles DESC
    LIMIT 1
");
$stmt->execute([$nombre_equipo]);
$max_goleador = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Completo - <?php echo htmlspecialchars($nombre_equipo); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .stat-card {
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .timeline-item {
            padding: 15px 0;
        }
        .torneo-card {
            border-left: 3px solid #28a745;
            margin-bottom: 1.5rem;
            padding-left: 1.5rem;
        }
        .torneo-card-header {
            background-color: #f8f9fa;
            padding: 0.75rem 1rem;
            border-radius: 0.375rem 0.375rem 0 0;
        }
        .torneos-index a { text-decoration: none; }
        .torneos-index .badge { font-size: 0.8rem; }
        .anchor-offset { scroll-margin-top: 90px; }
        
        /* Estilos móviles */
        @media (max-width: 768px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            h1, h5 {
                font-size: 1.5rem;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 0.5rem 0.3rem;
                font-size: 0.8rem;
            }
            
            .table th.d-none-mobile,
            .table td.d-none-mobile {
                display: none;
            }
            
            .table th.hide-xs,
            .table td.hide-xs {
                display: none;
            }
            
            .stat-card {
                padding: 0.75rem;
            }
            
            .col-6, .col-md-3 {
                margin-bottom: 0.75rem;
            }
            
            .torneos-index {
                flex-direction: column;
                align-items: flex-start !important;
            }
            
            .alert {
                padding: 1rem;
            }
            
            img {
                width: 60px !important;
                height: 60px !important;
            }
            
            .card-body {
                padding: 1rem;
            }
        }
        
        @media (max-width: 576px) {
            h1, h5 {
                font-size: 1.25rem;
            }
            
            .table th, .table td {
                padding: 0.4rem 0.2rem;
                font-size: 0.75rem;
            }
            
            .stat-card {
                padding: 0.5rem;
            }
            
            .col-6 {
                margin-bottom: 0.5rem;
            }
            
            img {
                width: 50px !important;
                height: 50px !important;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>"><i class="fas fa-futbol"></i> Fútbol Manager</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="resultados.php">Resultados</a></li>
                    <li class="nav-item"><a class="nav-link" href="tablas.php">Posiciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="goleadores.php">Goleadores</a></li>
                    <li class="nav-item"><a class="nav-link" href="fixture.php">Fixture</a></li>
                    <li class="nav-item"><a class="nav-link active" href="historial_equipos.php">Equipos</a></li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="../admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Panel Admin</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>login.php"><i class="fas fa-sign-in-alt"></i> Ingresar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-4">
                        <?php if ($instancias_equipo[0]['logo']): ?>
                            <img src="../uploads/<?php echo htmlspecialchars($instancias_equipo[0]['logo']); ?>" 
                                 alt="Logo" width="100" height="100" 
                                 class="mb-3 rounded-circle border border-3 border-success">
                        <?php else: ?>
                            <div class="bg-success rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" 
                                 style="width: 100px; height: 100px;">
                                <i class="fas fa-shield-alt text-white fa-3x"></i>
                            </div>
                        <?php endif; ?>
                        <h1 class="h3 fw-bold mb-2"><?php echo htmlspecialchars($nombre_equipo); ?></h1>
                        <p class="text-muted mb-3">Historial Completo del Equipo</p>
                        <a href="historial_equipos.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i> Volver a Equipos
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3"><i class="fas fa-chart-bar"></i> Estadísticas Generales</h5>
            </div>
            <?php
            $stats_display = [
                ['label' => 'Torneos', 'value' => $stats_totales['torneos_participados'], 'icon' => 'fa-trophy', 'color' => 'text-warning'],
                ['label' => 'Partidos', 'value' => $stats_totales['partidos_jugados'], 'icon' => 'fa-calendar-check', 'color' => 'text-info'],
                ['label' => 'Victorias', 'value' => $stats_totales['partidos_ganados'], 'icon' => 'fa-check-circle', 'color' => 'text-success'],
                ['label' => 'Goles a Favor', 'value' => $stats_totales['goles_favor'], 'icon' => 'fa-futbol', 'color' => 'text-primary'],
                ['label' => 'Goles en Contra', 'value' => $stats_totales['goles_contra'], 'icon' => 'fa-exclamation-triangle', 'color' => 'text-danger'],
                ['label' => 'Dif. Gol', 'value' => ($stats_totales['diferencia_gol'] > 0 ? '+' : '').$stats_totales['diferencia_gol'], 'icon' => 'fa-chart-line', 'color' => 'text-secondary'],
                ['label' => 'Empates', 'value' => $stats_totales['partidos_empatados'], 'icon' => 'fa-handshake', 'color' => 'text-warning'],
                ['label' => 'Derrotas', 'value' => $stats_totales['partidos_perdidos'], 'icon' => 'fa-times-circle', 'color' => 'text-muted'],
            ];
            ?>
            <?php foreach ($stats_display as $stat): ?>
            <div class="col-6 col-md-3 mb-3">
                <div class="card stat-card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-2">
                        <i class="fas <?php echo $stat['icon']; ?> <?php echo $stat['color']; ?> fa-lg mb-1"></i>
                        <h6 class="mb-0"><?php echo $stat['value']; ?></h6>
                        <small class="text-muted"><?php echo $stat['label']; ?></small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($max_goleador): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm bg-success text-white">
                    <div class="card-body text-center py-3">
                        <h6 class="mb-2"><i class="fas fa-crown"></i> Máximo Goleador Histórico</h6>
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <?php if ($max_goleador['foto']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($max_goleador['foto']); ?>" 
                                     width="50" height="50" class="rounded-circle border border-3 border-white" alt="Foto">
                            <?php else: ?>
                                <div class="bg-white rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 50px; height: 50px;">
                                    <i class="fas fa-user fa-lg text-success"></i>
                                </div>
                            <?php endif; ?>
                            <div class="text-start">
                                <div><?php echo htmlspecialchars($max_goleador['apellido_nombre']); ?></div>
                                <div><i class="fas fa-futbol"></i> <?php echo $max_goleador['total_goles']; ?> goles</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-12">
                <h5 class="mb-3"><i class="fas fa-history"></i> Historial por Torneo</h5>
                <div class="alert alert-light border d-flex align-items-center justify-content-between">
                    <div class="torneos-index d-flex flex-wrap gap-2">
                        <strong class="me-2"><i class="fas fa-list"></i> Índice:</strong>
                        <?php foreach ($stats_por_torneo as $idx => $torneo): ?>
                            <?php $anchor = 'torneo-'.($idx+1); ?>
                            <a class="badge bg-light text-dark" href="#<?php echo $anchor; ?>">
                                <?php echo htmlspecialchars($torneo['instancia']['campeonato']); ?> (<?php echo date('Y', strtotime($torneo['instancia']['fecha_inicio'])); ?>)
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <div>
                        <select class="form-select form-select-sm" onchange="if(this.value) location.hash=this.value;">
                            <option value="">Ir a torneo…</option>
                            <?php foreach ($stats_por_torneo as $idx => $torneo): ?>
                                <?php $anchor = '#torneo-'.($idx+1); ?>
                                <option value="<?php echo $anchor; ?>"><?php echo htmlspecialchars($torneo['instancia']['campeonato']); ?> (<?php echo date('Y', strtotime($torneo['instancia']['fecha_inicio'])); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 25%;">Torneo</th>
                                <th class="text-center d-none-mobile" style="width: 8%;">PJ</th>
                                <th class="text-center hide-xs" style="width: 8%;">PG</th>
                                <th class="text-center hide-xs" style="width: 8%;">PE</th>
                                <th class="text-center hide-xs" style="width: 8%;">PP</th>
                                <th class="text-center hide-xs" style="width: 8%;">GF</th>
                                <th class="text-center hide-xs" style="width: 8%;">GC</th>
                                <th class="text-center d-none-mobile" style="width: 8%;">DIF</th>
                                <th class="text-center hide-xs" style="width: 11%;">Jugadores</th>
                                <th class="text-center" style="width: 8%;">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats_por_torneo as $i => $torneo): 
                                $dif = ($torneo['stats']['goles_favor'] ?? 0) - ($torneo['stats']['goles_contra'] ?? 0);
                                $sign = $dif > 0 ? '+' : '';
                            ?>
                            <?php $anchorId = 'torneo-'.($i+1); ?>
                            <tr id="<?php echo $anchorId; ?>" class="anchor-offset">
                                <td>
                                    <strong><?php echo htmlspecialchars($torneo['instancia']['campeonato']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($torneo['instancia']['categoria']); ?> · <?php echo date('Y', strtotime($torneo['instancia']['fecha_inicio'])); ?></small>
                                </td>
                                <td class="text-center d-none-mobile"><?php echo $torneo['stats']['partidos_jugados'] ?? 0; ?></td>
                                <td class="text-center hide-xs"><strong class="text-success"><?php echo $torneo['stats']['ganados'] ?? 0; ?></strong></td>
                                <td class="text-center hide-xs"><strong class="text-warning"><?php echo $torneo['stats']['empatados'] ?? 0; ?></strong></td>
                                <td class="text-center hide-xs"><strong class="text-danger"><?php echo $torneo['stats']['perdidos'] ?? 0; ?></strong></td>
                                <td class="text-center hide-xs"><strong class="text-primary"><?php echo $torneo['stats']['goles_favor'] ?? 0; ?></strong></td>
                                <td class="text-center hide-xs"><strong class="text-danger"><?php echo $torneo['stats']['goles_contra'] ?? 0; ?></strong></td>
                                <td class="text-center d-none-mobile">
                                    <strong class="<?php echo $dif > 0 ? 'text-success' : ($dif < 0 ? 'text-danger' : ''); ?>">
                                        <?php echo $sign . $dif; ?>
                                    </strong>
                                </td>
                                <td class="text-center hide-xs">
                                    <small class="text-muted">
                                        <i class="fas fa-users"></i> <?php echo $torneo['jugadores']; ?>
                                    </small>
                                </td>
                                <td class="text-center">
                                    <a href="historial_equipos.php?categoria=<?php echo $torneo['instancia']['categoria_id']; ?>&equipo=<?php echo $torneo['instancia']['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-users"></i> Jugadores que Pasaron por el Equipo (<?php echo count($jugadores_historicos); ?>)</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($jugadores_historicos)): ?>
                            <p class="text-center text-muted py-3">No hay jugadores registrados en el historial</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Jugador</th>
                                            <th class="d-none-mobile">DNI</th>
                                            <th class="hide-xs">Edad</th>
                                            <th>Torneos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $jugadores_agrupados = [];
                                        foreach ($jugadores_historicos as $j) {
                                            $key = $j['dni'];
                                            if (!isset($jugadores_agrupados[$key])) {
                                                $jugadores_agrupados[$key] = [
                                                    'info' => $j,
                                                    'torneos' => []
                                                ];
                                            }
                                            // Si el campeonato contiene múltiples torneos separados por comas, separarlos
                                            if (isset($j['torneos_array'])) {
                                                $jugadores_agrupados[$key]['torneos'] = $j['torneos_array'];
                                            } else {
                                                // Formato antiguo: campeonato - categoria
                                                $torneo = $j['campeonato'] . ' - ' . $j['categoria'];
                                                if (!in_array($torneo, $jugadores_agrupados[$key]['torneos'])) {
                                                    $jugadores_agrupados[$key]['torneos'][] = $torneo;
                                                }
                                            }
                                        }
                                        foreach ($jugadores_agrupados as $jugador): 
                                        ?>
                                            <tr>
                                                <td>
                                                    <?php if (!empty($jugador['info']['foto'])): ?>
                                                        <img src="../uploads/<?php echo htmlspecialchars($jugador['info']['foto']); ?>" 
                                                             width="25" height="25" class="rounded-circle me-1" alt="Foto">
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($jugador['info']['apellido_nombre']); ?>
                                                </td>
                                                <td class="d-none-mobile"><?php echo htmlspecialchars($jugador['info']['dni']); ?></td>
                                                <td class="hide-xs"><?php echo calculateAge($jugador['info']['fecha_nacimiento']); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo count($jugador['torneos']); ?></span>
                                                    <button class="btn btn-sm btn-outline-secondary py-0 px-1" 
                                                            onclick="toggleTorneos('<?php echo $jugador['info']['dni']; ?>')">
                                                        <i class="fas fa-info-circle"></i>
                                                    </button>
                                                    <div id="torneos-<?php echo $jugador['info']['dni']; ?>" style="display:none;" class="mt-1 small">
                                                        <ul class="mb-0">
                                                            <?php foreach ($jugador['torneos'] as $t): ?>
                                                                <li><?php echo htmlspecialchars($t); ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
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
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-2 mt-4">
        <div class="container text-center">
            <small>© 2025 Sistema de Campeonatos - Actualizado: <?php echo date('d/m/Y H:i'); ?></small>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleTorneos(dni) {
            const div = document.getElementById('torneos-' + dni);
            div.style.display = div.style.display === 'none' ? 'block' : 'none';
        }
    </script>
</body>
</html>