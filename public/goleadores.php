<?php
require_once __DIR__ . '/../config.php';

$db = Database::getInstance()->getConnection();

// Obtener categorías activas
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
$campeonato_id = null;

// Obtener tabla de goleadores - LIMITADO A TOP 10
$goleadores = [];
if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT 
            j.id as jugador_id,
            j.apellido_nombre,
            j.dni,
            j.fecha_nacimiento,
            j.foto,
            e.nombre as equipo,
            e.logo as equipo_logo,
            e.color_camiseta,
            COUNT(ev.id) as goles,
            GROUP_CONCAT(
                CONCAT(
                    DATE_FORMAT(p.fecha_partido, '%d/%m'), ' vs ',
                    CASE 
                        WHEN p.equipo_local_id = e.id THEN ev_oponente.nombre 
                        ELSE el_oponente.nombre 
                    END
                ) 
                ORDER BY p.fecha_partido DESC 
                SEPARATOR '|'
            ) as partidos_gol
        FROM jugadores j
        JOIN equipos e ON j.equipo_id = e.id
        LEFT JOIN eventos_partido ev ON j.id = ev.jugador_id AND ev.tipo_evento = 'gol'
        LEFT JOIN partidos p ON ev.partido_id = p.id AND p.estado = 'finalizado'
        LEFT JOIN fechas f ON p.fecha_id = f.id
        LEFT JOIN equipos el_oponente ON p.equipo_local_id = el_oponente.id AND p.equipo_visitante_id = e.id
        LEFT JOIN equipos ev_oponente ON p.equipo_visitante_id = ev_oponente.id AND p.equipo_local_id = e.id
        WHERE e.categoria_id = ? AND j.activo = 1
          AND (f.categoria_id = ? OR f.categoria_id IS NULL)
        GROUP BY j.id, j.apellido_nombre, j.dni, j.fecha_nacimiento, j.foto, e.nombre, e.logo, e.color_camiseta
        HAVING goles > 0
        ORDER BY goles DESC, j.apellido_nombre ASC
        LIMIT 10
    ");
    $stmt->execute([$categoria_id, $categoria_id]);
    $goleadores = $stmt->fetchAll();
}

// Obtener información de la categoría seleccionada
$categoria_actual = null;
foreach ($categorias as $cat) {
    if ($cat['id'] == $categoria_id) {
        $categoria_actual = $cat;
        $campeonato_id = $cat['campeonato_id'] ?? null;
        break;
    }
}

// Estadísticas generales
$stats = [];
if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT j.id) as total_jugadores,
            COUNT(DISTINCT CASE WHEN ev.tipo_evento = 'gol' THEN j.id END) as jugadores_goleadores,
            COUNT(CASE WHEN ev.tipo_evento = 'gol' THEN 1 END) as total_goles,
            COUNT(DISTINCT p.id) as partidos_jugados,
            ROUND(COUNT(CASE WHEN ev.tipo_evento = 'gol' THEN 1 END) / COUNT(DISTINCT CASE WHEN p.estado = 'finalizado' THEN p.id END), 2) as promedio_goles_partido
        FROM equipos e
        JOIN jugadores j ON e.id = j.equipo_id
        LEFT JOIN eventos_partido ev ON j.id = ev.jugador_id
        LEFT JOIN partidos p ON ev.partido_id = p.id
        LEFT JOIN fechas f ON p.fecha_id = f.id
        WHERE e.categoria_id = ? AND j.activo = 1
          AND (f.categoria_id = ? OR f.categoria_id IS NULL)
    ");
    $stmt->execute([$categoria_id, $categoria_id]);
    $stats = $stmt->fetch();
}
// Arcos menos vencidos - Top 10
$arcos_menos_vencidos = [];
if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT 
            e.id,
            e.nombre as equipo,
            e.logo,
            e.color_camiseta,
            SUM(CASE 
                WHEN p.equipo_local_id = e.id THEN p.goles_visitante 
                WHEN p.equipo_visitante_id = e.id THEN p.goles_local 
                ELSE 0 
            END) as goles_contra,
            COUNT(DISTINCT p.id) as partidos_jugados
        FROM equipos e
        JOIN partidos p ON 
            (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
            AND p.estado = 'finalizado'
        JOIN fechas f ON p.fecha_id = f.id
        WHERE e.categoria_id = ? AND f.categoria_id = ?
        GROUP BY e.id, e.nombre, e.logo
        HAVING goles_contra IS NOT NULL
        ORDER BY goles_contra ASC, partidos_jugados DESC
        LIMIT 10
    ");
    $stmt->execute([$categoria_id, $categoria_id]);
    $arcos_menos_vencidos = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top 10 Goleadores</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        /* Estilos móviles */
        @media (max-width: 768px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 0.5rem 0.3rem;
                font-size: 0.8rem;
            }
            
            .table img {
                width: 30px !important;
                height: 30px !important;
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            .card-header {
                padding: 0.75rem;
                font-size: 0.9rem;
            }
            
            .stats-card {
                padding: 0.75rem;
            }
            
            .col-md-2, .col-md-4 {
                margin-bottom: 0.5rem;
            }
            
            .badge {
                font-size: 0.7rem;
                padding: 0.25rem 0.5rem;
            }
            
            h3, h4, h5 {
                font-size: 1rem;
            }
            
            .top-goleador-card img {
                width: 60px !important;
                height: 60px !important;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
            }
        }
        
        @media (max-width: 576px) {
            h2 {
                font-size: 1.25rem;
            }
            
            .table th, .table td {
                padding: 0.4rem 0.2rem;
                font-size: 0.75rem;
            }
            
            .table img {
                width: 24px !important;
                height: 24px !important;
            }
            
            .top-goleador-card img {
                width: 50px !important;
                height: 50px !important;
            }
            
            .card-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
     <?php include '../include/header.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-trophy"></i> Top 10 Goleadores</h2>
                </div>

                <?php if (empty($categorias)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No hay categorías disponibles en este momento.
                    </div>
                <?php else: ?>
                    <!-- Selector de Categoría -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-0">Seleccionar Categoría:</h5>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" onchange="cambiarCategoria(this.value)">
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                    <?php echo $cat['id'] == $categoria_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['campeonato_nombre'] . ' - ' . $cat['nombre']); ?>
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
                                    <div class="col-md-2">
                                        <div class="stats-card">
                                            <div class="icon text-success">
                                                <i class="fas fa-futbol"></i>
                                            </div>
                                            <div class="number text-success"><?php echo $stats['total_goles'] ?: 0; ?></div>
                                            <div class="label">Total Goles</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="stats-card">
                                            <div class="icon text-primary">
                                                <i class="fas fa-user-friends"></i>
                                            </div>
                                            <div class="number text-primary"><?php echo $stats['jugadores_goleadores'] ?: 0; ?></div>
                                            <div class="label">Goleadores</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="stats-card">
                                            <div class="icon text-info">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="number text-info"><?php echo $stats['total_jugadores'] ?: 0; ?></div>
                                            <div class="label">Jugadores</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="stats-card">
                                            <div class="icon text-warning">
                                                <i class="fas fa-calendar-check"></i>
                                            </div>
                                            <div class="number text-warning"><?php echo $stats['partidos_jugados'] ?: 0; ?></div>
                                            <div class="label">Partidos</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stats-card">
                                            <div class="icon text-secondary">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                            <div class="number text-secondary"><?php echo $stats['promedio_goles_partido'] ?: '0.00'; ?></div>
                                            <div class="label">Goles por Partido</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Podio Top 3 -->
                        <?php if (count($goleadores) >= 3): ?>
                        
						<div class="row mb-4">
						<!-- 1er Lugar -->
                            <div class="col-md-4">
                                <div class="card h-100 top-goleador-card" style="background: linear-gradient(135deg, #D4AF37, #FFD700, #B8860B);">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-crown" style="font-size: 4rem;"></i>
                                        </div>
                                        <h4 class="mb-2">👑 Pichichi</h4>
                                        <?php if ($goleadores[0]['foto']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($goleadores[0]['foto']); ?>" 
                                                 alt="Foto" class="rounded-circle mb-2" width="100" height="100" 
                                                 style="object-fit: cover; border: 4px solid #fff;">
                                        <?php endif; ?>
                                        <h3><?php echo htmlspecialchars($goleadores[0]['apellido_nombre']); ?></h3>
                                        <p class="mb-1"><?php echo htmlspecialchars($goleadores[0]['equipo']); ?></p>
                                        <h1 class="mb-0">
                                            <i class="fas fa-futbol"></i> <?php echo $goleadores[0]['goles']; ?>
                                        </h1>
                                    </div>
                                </div>
                            </div>
							
						<!-- 2do Lugar -->
                            <div class="col-md-4">
                                <div class="card h-100" style="background: linear-gradient(135deg, #C0C0C0, #A8A8A8);">
                                    <div class="card-body text-center text-dark">
                                        <div class="mb-3">
                                            <i class="fas fa-medal" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 class="mb-2">🥈 2do Lugar</h5>
                                        <?php if ($goleadores[1]['foto']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($goleadores[1]['foto']); ?>" 
                                                 alt="Foto" class="rounded-circle mb-2" width="80" height="80" 
                                                 style="object-fit: cover; border: 3px solid #fff;">
                                        <?php endif; ?>
                                        <h4><?php echo htmlspecialchars($goleadores[1]['apellido_nombre']); ?></h4>
                                        <p class="mb-1"><?php echo htmlspecialchars($goleadores[1]['equipo']); ?></p>
                                        <h2 class="mb-0">
                                            <i class="fas fa-futbol"></i> <?php echo $goleadores[1]['goles']; ?>
                                        </h2>
                                    </div>
                                </div>
                            </div>
							
                            <!-- 3er Lugar -->
                            <div class="col-md-4">
                                <div class="card h-100" style="background: linear-gradient(135deg, #CD7F32, #8B4513);">
                                    <div class="card-body text-center text-white">
                                        <div class="mb-3">
                                            <i class="fas fa-medal" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 class="mb-2">🥉 3er Lugar</h5>
                                        <?php if ($goleadores[2]['foto']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($goleadores[2]['foto']); ?>" 
                                                 alt="Foto" class="rounded-circle mb-2" width="80" height="80" 
                                                 style="object-fit: cover; border: 3px solid #fff;">
                                        <?php endif; ?>
                                        <h4><?php echo htmlspecialchars($goleadores[2]['apellido_nombre']); ?></h4>
                                        <p class="mb-1"><?php echo htmlspecialchars($goleadores[2]['equipo']); ?></p>
                                        <h2 class="mb-0">
                                            <i class="fas fa-futbol"></i> <?php echo $goleadores[2]['goles']; ?>
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Tabla Top 10 -->
                        <div class="card mt-5" style="max-width: 600px; margin: auto;">
                            <div class="card-header bg-success text-white">
                                <h4 class="mb-0">
                                    <i class="fas fa-list-ol"></i> 
                                    Top 10 - <?php echo htmlspecialchars($categoria_actual['campeonato_nombre'] . ' - ' . $categoria_actual['nombre']); ?>
                                </h4>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width: 10%;">Pos</th>
                                            <th style="width: 45%;">Jugador</th>
                                            <th style="width: 30%;">Equipo</th>
                                            <th class="text-center" style="width: 15%;">Goles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $posicion_anterior = 0;
                                        $goles_anterior = -1;
                                        foreach ($goleadores as $index => $goleador): 
                                            if ($goleador['goles'] != $goles_anterior) {
                                                $posicion_anterior = $index + 1;
                                                $goles_anterior = $goleador['goles'];
                                            }
                                            


// Records de póker (4+), hat-trick (3) y dobletes (2) por jugador en el campeonato
$records = [ 'poker' => [], 'hat_trick' => [], 'dobletes' => [] ];
if ($campeonato_id) {
    // Base: goles por partido por jugador
    $sqlBase = "\n        SELECT j.id as jugador_id, j.apellido_nombre, j.foto, sub.goles_en_partido\n        FROM (\n            SELECT ev.jugador_id, ev.partido_id, COUNT(*) as goles_en_partido\n            FROM eventos_partido ev\n            JOIN partidos p ON ev.partido_id = p.id\n            JOIN fechas f ON p.fecha_id = f.id\n            JOIN categorias c ON f.categoria_id = c.id\n            WHERE ev.tipo_evento = 'gol' AND c.campeonato_id = :camp\n            GROUP BY ev.jugador_id, ev.partido_id\n        ) sub\n        JOIN jugadores j ON j.id = sub.jugador_id\n    ";
    // Pókers (>=4)
    $stmt = $db->prepare($sqlBase . " WHERE sub.goles_en_partido >= 4");
    $stmt->execute([':camp' => $campeonato_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $agg = [];
    foreach ($rows as $r) { $id = $r['jugador_id']; if (!isset($agg[$id])) $agg[$id] = ['jugador_id'=>$id,'apellido_nombre'=>$r['apellido_nombre'],'foto'=>$r['foto'],'cantidad'=>0]; $agg[$id]['cantidad']++; }
    usort($agg, fn($a,$b) => $b['cantidad'] <=> $a['cantidad']);
    $records['poker'] = array_slice(array_values($agg), 0, 10);

    // Hat-tricks (exactamente 3)
    $stmt = $db->prepare($sqlBase . " WHERE sub.goles_en_partido = 3");
    $stmt->execute([':camp' => $campeonato_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $agg = [];
    foreach ($rows as $r) { $id = $r['jugador_id']; if (!isset($agg[$id])) $agg[$id] = ['jugador_id'=>$id,'apellido_nombre'=>$r['apellido_nombre'],'foto'=>$r['foto'],'cantidad'=>0]; $agg[$id]['cantidad']++; }
    usort($agg, fn($a,$b) => $b['cantidad'] <=> $a['cantidad']);
    $records['hat_trick'] = array_slice(array_values($agg), 0, 10);

    // Dobletes (exactamente 2)
    $stmt = $db->prepare($sqlBase . " WHERE sub.goles_en_partido = 2");
    $stmt->execute([':camp' => $campeonato_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $agg = [];
    foreach ($rows as $r) { $id = $r['jugador_id']; if (!isset($agg[$id])) $agg[$id] = ['jugador_id'=>$id,'apellido_nombre'=>$r['apellido_nombre'],'foto'=>$r['foto'],'cantidad'=>0]; $agg[$id]['cantidad']++; }
    usort($agg, fn($a,$b) => $b['cantidad'] <=> $a['cantidad']);
    $records['dobletes'] = array_slice(array_values($agg), 0, 10);
}

// Goleadores históricos de toda la liga (sin filtrar por campeonato)
$goleadores_hist_liga = [];
try {
    $stmt = $db->query("\n        SELECT \n            j.id AS jugador_id, j.apellido_nombre, j.foto,\n            COUNT(ev.id) AS goles_totales\n        FROM eventos_partido ev\n        JOIN jugadores j ON ev.jugador_id = j.id\n        JOIN partidos p ON ev.partido_id = p.id\n        WHERE ev.tipo_evento = 'gol'\n        GROUP BY j.id, j.apellido_nombre, j.foto\n        HAVING goles_totales > 0\n        ORDER BY goles_totales DESC, j.apellido_nombre ASC\n        LIMIT 20\n    ");
    $goleadores_hist_liga = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { $goleadores_hist_liga = []; }

                                            $badge_class = 'position-other';
                                            if ($posicion_anterior == 1) $badge_class = 'position-1';
                                            elseif ($posicion_anterior == 2) $badge_class = 'position-2';
                                            elseif ($posicion_anterior == 3) $badge_class = 'position-3';
                                        ?>
                                        <tr class="goleador-row">
                                            <td class="text-center align-middle">
                                                <div class="position-badge <?php echo $badge_class; ?>">
                                                    <?php echo $posicion_anterior; ?>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($goleador['foto']): ?>
                                                        <img src="../uploads/<?php echo htmlspecialchars($goleador['foto']); ?>" 
                                                             alt="Foto" class="me-2 rounded-circle" width="45" height="45" 
                                                             style="object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                             style="width: 45px; height: 45px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <strong><?php echo htmlspecialchars($goleador['apellido_nombre']); ?></strong>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($goleador['equipo_logo']): ?>
                                                        <img src="../uploads/<?php echo htmlspecialchars($goleador['equipo_logo']); ?>" 
                                                             alt="Logo" class="me-2 rounded" width="30" height="30" style="object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="rounded me-2 d-flex align-items-center justify-content-center" 
                                                             style="width: 30px; height: 30px; background-color: <?php echo $goleador['color_camiseta'] ?: '#6c757d'; ?>;">
                                                            <i class="fas fa-shield-alt text-white" style="font-size: 0.8rem;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($goleador['equipo']); ?></span>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge bg-success" style="font-size: 1rem; padding: 8px 12px;">
                                                    <i class="fas fa-futbol"></i> <?php echo $goleador['goles']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($goleadores)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">No hay goleadores registrados aún</p>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                       <!-- Records: Póker, Hat-Trick, Dobletes -->
                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-header bg-danger text-white"><strong><i class="fas fa-bolt"></i> Más Póker (4+)</strong></div>
                                    <ul class="list-group list-group-flush">
                                        <?php if (!empty($records['poker'])): ?>
                                            <?php foreach ($records['poker'] as $r): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    <?php if (!empty($r['foto'])): ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($r['foto']); ?>" width="24" height="24" class="rounded-circle me-2" style="object-fit: cover;">
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($r['apellido_nombre']); ?>
                                                </span>
                                                <span class="badge bg-danger rounded-pill"><?php echo (int)$r['cantidad']; ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="list-group-item text-muted">Sin registros</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-header bg-warning text-dark"><strong><i class="fas fa-star"></i> Más Hat-Tricks (3)</strong></div>
                                    <ul class="list-group list-group-flush">
                                        <?php if (!empty($records['hat_trick'])): ?>
                                            <?php foreach ($records['hat_trick'] as $r): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    <?php if (!empty($r['foto'])): ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($r['foto']); ?>" width="24" height="24" class="rounded-circle me-2" style="object-fit: cover;">
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($r['apellido_nombre']); ?>
                                                </span>
                                                <span class="badge bg-warning text-dark rounded-pill"><?php echo (int)$r['cantidad']; ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="list-group-item text-muted">Sin registros</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card h-100">
                                    <div class="card-header bg-info text-white"><strong><i class="fas fa-bullseye"></i> Más Dobletes (2)</strong></div>
                                    <ul class="list-group list-group-flush">
                                        <?php if (!empty($records['dobletes'])): ?>
                                            <?php foreach ($records['dobletes'] as $r): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                                <span>
                                                    <?php if (!empty($r['foto'])): ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($r['foto']); ?>" width="24" height="24" class="rounded-circle me-2" style="object-fit: cover;">
                                                    <?php endif; ?>
                                                    <?php echo htmlspecialchars($r['apellido_nombre']); ?>
                                                </span>
                                                <span class="badge bg-info text-white rounded-pill"><?php echo (int)$r['cantidad']; ?></span>
                                            </li>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <li class="list-group-item text-muted">Sin registros</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Goleadores Históricos de Toda la Liga -->
                        <?php if (!empty($goleadores_hist_liga)): ?>
                        <div class="card mt-5">
                            <div class="card-header bg-secondary text-white">
                                <h5 class="mb-0"><i class="fas fa-globe"></i> Goleadores Históricos - Toda la Liga</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width: 10%;">#</th>
                                            <th style="width: 60%;">Jugador</th>
                                            <th class="text-center" style="width: 30%;">Goles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($goleadores_hist_liga as $i => $g): ?>
                                        <tr>
                                            <td class="text-center align-middle"><strong><?php echo $i + 1; ?></strong></td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <?php if (!empty($g['foto'])): ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($g['foto']); ?>" width="32" height="32" class="rounded-circle me-2" style="object-fit: cover;">
                                                    <?php else: ?>
                                                    <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                                                        <i class="fas fa-user text-white" style="font-size: 0.8rem;"></i>
                                                    </div>
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($g['apellido_nombre']); ?></span>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle"><span class="badge bg-secondary"><?php echo (int)$g['goles_totales']; ?></span></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
						<!-- Tabla Arcos Menos Vencidos -->
<div class="card mt-5" style="max-width: 600px; margin: auto;">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-shield-alt"></i> Arcos Menos Vencidos (Top 10)
        </h5>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-striped mb-0">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="width: 10%;">#</th>
                    <th style="width: 60%;">Equipo</th>
                    <th class="text-center" style="width: 30%;">Goles Contra</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($arcos_menos_vencidos)): ?>
                    <?php foreach ($arcos_menos_vencidos as $index => $equipo): ?>
                    <tr>
                        <td class="text-center align-middle"><strong><?php echo $index + 1; ?></strong></td>
                        <td class="align-middle">
                            <div class="d-flex align-items-center">
                                <?php if ($equipo['logo']): ?>
                                    <img src="../uploads/<?php echo htmlspecialchars($equipo['logo']); ?>" 
                                         alt="Logo" class="me-2 rounded" width="30" height="30" 
                                         style="object-fit: cover;">
                                <?php else: ?>
                                    <div class="rounded me-2 d-flex align-items-center justify-content-center" 
                                         style="width: 30px; height: 30px; background-color: <?php echo $equipo['color_camiseta'] ?: '#6c757d'; ?>;">
                                        <i class="fas fa-shield-alt text-white" style="font-size: 0.8rem;"></i>
                                    </div>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($equipo['equipo']); ?></span>
                            </div>
                        </td>
                        <td class="text-center align-middle">
                            <span class="badge bg-danger" style="font-size: 0.9rem;">
                                <?php echo $equipo['goles_contra'] ?: 0; ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-3">
                            <i class="fas fa-info-circle text-muted"></i> No hay datos disponibles
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-futbol"></i> Sistema de Campeonatos</h5>
                    <p class="text-muted">Top 10 Goleadores actualizado en tiempo real</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">© 2024 Todos los derechos reservados</p>
                    <small class="text-muted">Actualizado: <?php echo date('d/m/Y H:i'); ?></small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function cambiarCategoria(categoriaId) {
            window.location.href = 'goleadores.php?categoria=' + categoriaId;
        }

        // Auto actualización cada 2 minutos
        setInterval(function() {
            location.reload();
        }, 120000);
    </script>
</body>
</html>