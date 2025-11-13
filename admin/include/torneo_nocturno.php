<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

// La estructura es:
// /raiz_sistema/
//   ├── config.php
//   ├── admin/includes/desempate_functions.php
//   └── public/torneo_nocturno.php
// Verificar primero que el archivo exista
$ruta_desempate = __DIR__ . '/admin/includes/desempate_functions.php';

if (!file_exists($ruta_desempate)) {
    // Intentar ruta alternativa si la estructura es diferente
    $ruta_desempate = dirname(dirname(__FILE__)) . '/admin/includes/desempate_functions.php';
    
    if (!file_exists($ruta_desempate)) {
        die("ERROR: No se encuentra el archivo desempate_functions.php. Ruta buscada: " . $ruta_desempate);
    }
}

require_once $ruta_desempate;

$db = Database::getInstance()->getConnection();

// Buscar campeonato activo llamado "Torneo Nocturno"
$stmt = $db->prepare("SELECT id, nombre FROM campeonatos WHERE activo = 1 AND nombre LIKE ? LIMIT 1");
$stmt->execute(['%Torneo Nocturno%']);
$campeonato = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campeonato) {
    header('Location: ../index.php');
    exit;
}

// IDs de categorías de este campeonato
$stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
$stmt->execute([$campeonato['id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categoria_ids = array_column($categorias, 'id');
$placeholder_cat = !empty($categoria_ids) ? implode(',', array_fill(0, count($categoria_ids), '?')) : '';

// Formatos de zonas (si existen)
$stmt = $db->prepare("SELECT * FROM campeonatos_formato WHERE campeonato_id = ? AND activo = 1 ORDER BY created_at DESC");
$stmt->execute([$campeonato['id']]);
$formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Posiciones por zonas CON SISTEMA DE DESEMPATE
$zonas_data = [];
if (!empty($formatos)) {
    foreach ($formatos as $formato) {
        $stmtZ = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
        $stmtZ->execute([$formato['id']]);
        $zonas = $stmtZ->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($zonas as &$z) {
            // USAR SISTEMA DE DESEMPATE COMPLETO
            try {
                $z['tabla'] = calcularTablaPosicionesConDesempate($z['id'], $db);
            } catch (Exception $e) {
                error_log("Error calculando tabla zona {$z['id']}: " . $e->getMessage());
                $z['tabla'] = [];
            }
        }
        unset($z); // Romper referencia
        
        $zonas_data[] = ['formato' => $formato, 'zonas' => $zonas];
    }
}

// Resultados recientes (últimos 30)
$resultados = [];
if (!empty($categoria_ids)) {
    // Incluir partidos de zona Y normales
    $sql = "
        (SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante, 
                c.nombre as cancha, NULL as numero_fecha, 'zona' as tipo
        FROM partidos_zona p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        JOIN zonas z ON p.zona_id = z.id
        JOIN campeonatos_formato cf ON z.formato_id = cf.id
        WHERE cf.campeonato_id = ? AND p.estado = 'finalizado')
        UNION ALL
        (SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante, 
                c.nombre as cancha, f.numero_fecha, 'normal' as tipo
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        WHERE f.categoria_id IN ($placeholder_cat) AND p.estado = 'finalizado')
        ORDER BY fecha_partido DESC, hora_partido DESC
        LIMIT 30
    ";
    $params = array_merge([$campeonato['id']], $categoria_ids);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fixture próximo - partidos de zonas y normales
$fixture = [];
$fixture_zonas = [];

// Partidos normales
if (!empty($categoria_ids)) {
    $sql = "
        SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante, 
               c.nombre as cancha, f.numero_fecha
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        WHERE f.categoria_id IN ($placeholder_cat) 
        AND p.estado IN ('programado','sin_asignar') 
        AND (p.tipo_torneo = 'normal' OR p.tipo_torneo IS NULL)
        ORDER BY p.fecha_partido ASC, p.hora_partido ASC
        LIMIT 30
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($categoria_ids);
    $fixture = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Partidos de zonas
if (!empty($formatos)) {
    foreach ($formatos as $formato) {
        $stmtZ = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
        $stmtZ->execute([$formato['id']]);
        $zonas_fixture = $stmtZ->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($zonas_fixture as $zona) {
            $sql = "
                SELECT pz.*, el.nombre as equipo_local, el.logo as logo_local, 
                       ev.nombre as equipo_visitante, ev.logo as logo_visitante, 
                       c.nombre as cancha, z.nombre as zona_nombre
                FROM partidos_zona pz
                JOIN equipos el ON pz.equipo_local_id = el.id
                JOIN equipos ev ON pz.equipo_visitante_id = ev.id
                LEFT JOIN canchas c ON pz.cancha_id = c.id
                JOIN zonas z ON pz.zona_id = z.id
                WHERE pz.zona_id = ?
                ORDER BY pz.fecha_numero ASC, pz.fecha_partido ASC, pz.hora_partido ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute([$zona['id']]);
            $partidos_zona = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $fixture_zonas[] = [
                'zona' => $zona,
                'partidos' => $partidos_zona
            ];
        }
    }
}

// Goleadores - incluir partidos de zona
$goleadores = [];
if (!empty($categoria_ids)) {
    $sql = "
        SELECT j.id as jugador_id, j.apellido_nombre, e.nombre as equipo, COUNT(ev.id) as goles
        FROM eventos_partido ev
        JOIN jugadores j ON ev.jugador_id = j.id
        JOIN equipos e ON j.equipo_id = e.id
        WHERE ev.tipo_evento = 'gol'
        AND (
            ev.partido_id IN (
                SELECT id FROM partidos WHERE fecha_id IN (
                    SELECT id FROM fechas WHERE categoria_id IN ($placeholder_cat)
                )
            )
            OR ev.partido_id IN (
                SELECT pz.id FROM partidos_zona pz
                JOIN zonas z ON pz.zona_id = z.id
                JOIN campeonatos_formato cf ON z.formato_id = cf.id
                WHERE cf.campeonato_id = ?
            )
        )
        GROUP BY j.id, j.apellido_nombre, e.nombre
        HAVING goles > 0
        ORDER BY goles DESC, j.apellido_nombre ASC
        LIMIT 50
    ";
    $params = array_merge($categoria_ids, [$campeonato['id']]);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $goleadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fairplay - incluir partidos de zona
$fairplay = [];
if (!empty($categoria_ids)) {
    $sql = "
        SELECT e.id as equipo_id, e.nombre as equipo,
               SUM(CASE WHEN ev.tipo_evento = 'amarilla' THEN 1 ELSE 0 END) as amarillas,
               SUM(CASE WHEN ev.tipo_evento = 'roja' THEN 1 ELSE 0 END) as rojas,
               (SUM(CASE WHEN ev.tipo_evento = 'amarilla' THEN 1 ELSE 0 END) + 
                SUM(CASE WHEN ev.tipo_evento = 'roja' THEN 1 ELSE 0 END) * 3) as puntos_disciplina
        FROM equipos e
        JOIN jugadores j ON j.equipo_id = e.id
        JOIN eventos_partido ev ON ev.jugador_id = j.id
        WHERE (
            ev.partido_id IN (
                SELECT id FROM partidos WHERE fecha_id IN (
                    SELECT id FROM fechas WHERE categoria_id IN ($placeholder_cat)
                )
            )
            OR ev.partido_id IN (
                SELECT pz.id FROM partidos_zona pz
                JOIN zonas z ON pz.zona_id = z.id
                JOIN campeonatos_formato cf ON z.formato_id = cf.id
                WHERE cf.campeonato_id = ?
            )
        )
        GROUP BY e.id, e.nombre
        ORDER BY puntos_disciplina ASC, rojas ASC, amarillas ASC, equipo ASC
    ";
    $params = array_merge($categoria_ids, [$campeonato['id']]);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $fairplay = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Sanciones - incluir de partidos de zona
$sanciones = [];
if (!empty($categoria_ids)) {
    $sql = "
        SELECT s.*, j.apellido_nombre, e.nombre as equipo,
               (s.partidos_suspension - s.partidos_cumplidos) as pendientes
        FROM sanciones s
        JOIN jugadores j ON s.jugador_id = j.id
        JOIN equipos e ON j.equipo_id = e.id
        WHERE e.categoria_id IN ($placeholder_cat) 
        AND s.activa = 1
        AND s.partidos_cumplidos < s.partidos_suspension
        ORDER BY s.fecha_sancion DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($categoria_ids);
    $sanciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Equipos del torneo
$equipos = [];
if (!empty($categoria_ids)) {
    $sql = "
        SELECT id, nombre, logo FROM equipos 
        WHERE categoria_id IN ($placeholder_cat) AND activo = 1 
        ORDER BY nombre
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($categoria_ids);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($campeonato['nombre']); ?> - Altos del Paracao</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #198754;
            --dark-bg: #1a1a1a;
            --card-bg: #ffffff;
            --text-muted: #6c757d;
            --border-light: #e9ecef;
            --shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-size: 0.95rem;
        }
        .hero-section {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
            padding: 1.5rem 0;
        }
        .nav-tabs {
            background: #ffffff;
            border-radius: 12px;
            padding: 6px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-light);
            overflow-x: auto;
            flex-wrap: nowrap;
            -webkit-overflow-scrolling: touch;
        }
        .nav-tabs .nav-link {
            border: none;
            border-radius: 8px;
            color: var(--text-muted);
            font-weight: 500;
            padding: 10px 16px;
            margin: 0 2px;
            transition: all 0.3s ease;
            white-space: nowrap;
            font-size: 0.9rem;
        }
        .nav-tabs .nav-link:hover {
            background: rgba(25, 135, 84, 0.1);
            color: var(--primary-color);
        }
        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(25, 135, 84, 0.3);
        }
        .tab-content {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-light);
            margin-top: 20px;
        }
        .tab-pane {
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 15px;
            border: 1px solid var(--border-light);
        }
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #146c43 100%);
            color: white;
            border: none;
            border-radius: 10px 10px 0 0 !important;
            padding: 12px 16px;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .card-body {
            padding: 15px;
        }
        .table {
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 0;
            font-size: 0.875rem;
        }
        .table thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
            padding: 10px 8px;
            font-size: 0.825rem;
        }
        .table tbody td {
            padding: 8px;
            border-color: var(--border-light);
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background: rgba(25, 135, 84, 0.05);
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(248, 249, 250, 0.7);
        }
        .team-logo {
            width: 24px;
            height: 24px;
            object-fit: contain;
            border-radius: 4px;
            background: #f8f9fa;
            border: 1px solid var(--border-light);
        }
        .team-initial {
            width: 24px;
            height: 24px;
            border-radius: 4px;
            background: var(--primary-color);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .alert {
            border: none;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .badge {
            padding: 4px 8px;
            font-weight: 600;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        .stats-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .footer {
            background: #2c3e50;
            color: white;
            border-radius: 12px;
            margin: 30px 0 20px 0;
            padding: 25px;
            text-align: center;
        }
        /* Responsive optimizado */
        @media (max-width: 768px) {
            body {
                font-size: 0.875rem;
            }
            .hero-section {
                padding: 1rem 0;
            }
            .hero-section h1 {
                font-size: 1.25rem;
            }
            .tab-pane {
                padding: 12px;
            }
            .nav-tabs .nav-link {
                padding: 8px 12px;
                font-size: 0.825rem;
            }
            .card-body {
                padding: 10px;
            }
            .table {
                font-size: 0.8rem;
            }
            .table thead th,
            .table tbody td {
                padding: 6px 4px;
                font-size: 0.75rem;
            }
            .hide-mobile {
                display: none !important;
            }
            .team-logo, .team-initial {
                width: 20px;
                height: 20px;
                font-size: 0.7rem;
            }
            .badge {
                font-size: 0.7rem;
                padding: 3px 6px;
            }
            .stats-card {
                padding: 10px;
            }
            .col-6 {
                padding-left: 5px;
                padding-right: 5px;
            }
        }
        @media (max-width: 576px) {
            .hide-xs {
                display: none !important;
            }
            .table thead th,
            .table tbody td {
                padding: 5px 3px;
            }
            .team-name {
                max-width: 80px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        }
        .clasificado-badge {
            width: 8px;
            height: 8px;
            background: #198754;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../include/header.php'; ?>
    
    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="fw-bold text-white mb-1">
                        <i class="fas fa-moon text-warning"></i> <?php echo htmlspecialchars($campeonato['nombre']); ?>
                    </h1>
                    <p class="text-white-50 mb-0 small">Información completa del torneo</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-2 mt-lg-0">
                    <span class="badge bg-warning text-dark">
                        <i class="fas fa-star"></i> Edición <?= date('Y') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="container my-3">
        <!-- Tabs de Navegación -->
        <ul class="nav nav-tabs" id="nocturnoTabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-posiciones">
                    <i class="fas fa-trophy"></i> Posiciones
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fixture">
                    <i class="fas fa-calendar"></i> Fixture
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-resultados">
                    <i class="fas fa-list"></i> Resultados
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-goleadores">
                    <i class="fas fa-futbol"></i> Goleadores
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sanciones">
                    <i class="fas fa-ban"></i> Sanciones
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-equipos">
                    <i class="fas fa-users"></i> Equipos
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fairplay">
                    <i class="fas fa-shield-alt"></i> Fairplay
                </button>
            </li>
        </ul>

        <!-- Contenido de las Tabs -->
        <div class="tab-content">
            <!-- POSICIONES -->
            <div class="tab-pane fade show active" id="tab-posiciones">
                <?php if (empty($zonas_data)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No hay formatos de zonas configurados.
                    </div>
                <?php else: ?>
                    <?php foreach ($zonas_data as $pack): ?>
                        <div class="row">
                            <?php foreach ($pack['zonas'] as $zona): ?>
                                <div class="col-lg-6 mb-3">
                                    <div class="card">
                                        <div class="card-header">
                                            <i class="fas fa-flag me-2"></i><?= htmlspecialchars($zona['nombre']) ?>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($zona['tabla'])): ?>
                                                <small class="text-muted">Sin datos en esta zona</small>
                                            <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th style="width:30px"></th>
                                                                <th>Equipo</th>
                                                                <th>Pts</th>
                                                                <th class="hide-mobile">PJ</th>
                                                                <th class="hide-xs">GF</th>
                                                                <th class="hide-xs">GC</th>
                                                                <th class="hide-mobile">Dif</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($zona['tabla'] as $pos => $r): ?>
                                                                <tr>
                                                                    <td>
                                                                        <?php if ($pos < 2): ?>
                                                                            <span class="clasificado-badge" title="Clasificado"></span>
                                                                        <?php endif; ?>
                                                                        <small class="fw-bold"><?= $pos + 1 ?></small>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <?php if (!empty($r['logo'])): ?>
                                                                                <img src="../uploads/<?= htmlspecialchars($r['logo']) ?>" 
                                                                                     class="team-logo me-1" alt="Logo">
                                                                            <?php else: ?>
                                                                                <div class="team-initial me-1">
                                                                                    <?= substr($r['nombre'], 0, 1) ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <span class="team-name"><?= htmlspecialchars($r['nombre']) ?></span>
                                                                        </div>
                                                                    </td>
                                                                    <td><strong><?= (int)$r['puntos'] ?></strong></td>
                                                                    <td class="hide-mobile"><?= (int)$r['partidos_jugados'] ?></td>
                                                                    <td class="hide-xs"><?= (int)$r['goles_favor'] ?></td>
                                                                    <td class="hide-xs"><?= (int)$r['goles_contra'] ?></td>
                                                                    <td class="hide-mobile">
                                                                        <small class="<?= $r['diferencia_gol'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                                                            <?= $r['diferencia_gol'] >= 0 ? '+' : '' ?><?= (int)$r['diferencia_gol'] ?>
                                                                        </small>
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
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- FIXTURE -->
            <div class="tab-pane fade" id="tab-fixture">
                <?php if (empty($fixture) && empty($fixture_zonas)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No hay partidos programados.
                    </div>
                <?php else: ?>
                    <?php if (!empty($fixture_zonas)): ?>
                        <?php foreach ($fixture_zonas as $fz): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <i class="fas fa-flag me-2"></i><?= htmlspecialchars($fz['zona']['nombre']) ?>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($fz['partidos'])): ?>
                                        <small class="text-muted">Sin partidos</small>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th class="hide-xs">Fecha</th>
                                                        <th>Local</th>
                                                        <th>Visitante</th>
                                                        <th class="hide-xs">Cancha</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($fz['partidos'] as $p): ?>
                                                        <tr>
                                                            <td class="hide-xs text-muted small">
                                                                <?= $p['fecha_partido'] ? date('d/m', strtotime($p['fecha_partido'])) : '-' ?>
                                                                <?= $p['hora_partido'] ? date('H:i', strtotime($p['hora_partido'])) : '' ?>
                                                            </td>
                                                            <td class="team-name"><?= htmlspecialchars($p['equipo_local']) ?></td>
                                                            <td class="team-name"><?= htmlspecialchars($p['equipo_visitante']) ?></td>
                                                            <td class="text-muted small hide-xs"><?= htmlspecialchars($p['cancha'] ?? '-') ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($fixture)): ?>
                        <div class="card">
                            <div class="card-header">Partidos Programados</div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <tbody>
                                            <?php foreach ($fixture as $p): ?>
                                                <tr>
                                                    <td class="hide-xs text-muted small">
                                                        <?= $p['fecha_partido'] ? date('d/m', strtotime($p['fecha_partido'])) : '-' ?>
                                                        <?= $p['hora_partido'] ? ' ' . date('H:i', strtotime($p['hora_partido'])) : '' ?>
                                                    </td>
                                                    <td class="team-name"><?= htmlspecialchars($p['equipo_local']) ?></td>
                                                    <td class="team-name"><?= htmlspecialchars($p['equipo_visitante']) ?></td>
                                                    <td class="text-muted small hide-xs"><?= htmlspecialchars($p['cancha'] ?? '-') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- RESULTADOS -->
            <div class="tab-pane fade" id="tab-resultados">
                <?php if (empty($resultados)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sin resultados recientes.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th class="hide-xs">Fecha</th>
                                            <th>Local</th>
                                            <th></th>
                                            <th>Visitante</th>
                                            <th>Resultado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resultados as $p): ?>
                                            <tr>
                                                <td class="text-muted small hide-xs">
                                                    <?= $p['fecha_partido'] ? date('d/m', strtotime($p['fecha_partido'])) : '-' ?>
                                                </td>
                                                <td class="team-name"><?= htmlspecialchars($p['equipo_local']) ?></td>
                                                <td class="text-center text-muted small">vs</td>
                                                <td class="team-name"><?= htmlspecialchars($p['equipo_visitante']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <?= (int)$p['goles_local'] ?> - <?= (int)$p['goles_visitante'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- GOLEADORES -->
            <div class="tab-pane fade" id="tab-goleadores">
                <?php if (empty($goleadores)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sin goles registrados.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width:40px">#</th>
                                            <th>Jugador</th>
                                            <th class="hide-mobile">Equipo</th>
                                            <th>Goles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($goleadores as $pos => $g): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge <?= $pos < 3 ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                                        <?= $pos + 1 ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($g['apellido_nombre']) ?></strong>
                                                    <br class="d-md-none">
                                                    <small class="text-muted d-md-none"><?= htmlspecialchars($g['equipo']) ?></small>
                                                </td>
                                                <td class="text-muted small hide-mobile"><?= htmlspecialchars($g['equipo']) ?></td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-futbol"></i> <?= (int)$g['goles'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SANCIONES -->
            <div class="tab-pane fade" id="tab-sanciones">
                <?php if (empty($sanciones)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sin sanciones activas.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th class="hide-xs">Fecha</th>
                                            <th>Jugador</th>
                                            <th class="hide-mobile">Equipo</th>
                                            <th>Tipo</th>
                                            <th>Pend.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sanciones as $s): ?>
                                            <tr>
                                                <td class="text-muted small hide-xs">
                                                    <?= date('d/m', strtotime($s['fecha_sancion'])) ?>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($s['apellido_nombre']) ?></strong>
                                                    <br class="d-md-none">
                                                    <small class="text-muted d-md-none"><?= htmlspecialchars($s['equipo']) ?></small>
                                                </td>
                                                <td class="text-muted small hide-mobile"><?= htmlspecialchars($s['equipo']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= strpos($s['tipo'], 'roja') !== false ? 'danger' : 'warning' ?>">
                                                        <?= htmlspecialchars($s['tipo']) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-dark">
                                                        <?= (int)$s['pendientes'] ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- EQUIPOS -->
            <div class="tab-pane fade" id="tab-equipos">
                <?php if (empty($equipos)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No hay equipos registrados.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="row g-2">
                                <?php foreach ($equipos as $e): ?>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="stats-card text-center">
                                            <?php if (!empty($e['logo'])): ?>
                                                <img src="../uploads/<?= htmlspecialchars($e['logo']) ?>" 
                                                     class="team-logo mb-2" 
                                                     style="width: 40px; height: 40px;" alt="Logo">
                                            <?php else: ?>
                                                <div class="team-initial mb-2 mx-auto" style="width: 40px; height: 40px; font-size: 1rem;">
                                                    <?= substr($e['nombre'], 0, 1) ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="small fw-medium"><?= htmlspecialchars($e['nombre']) ?></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- FAIRPLAY -->
            <div class="tab-pane fade" id="tab-fairplay">
                <?php if (empty($fairplay)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sin datos de disciplina.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th style="width:40px">#</th>
                                            <th>Equipo</th>
                                            <th class="hide-xs">
                                                <i class="fas fa-square text-danger"></i>
                                            </th>
                                            <th class="hide-xs">
                                                <i class="fas fa-square text-warning"></i>
                                            </th>
                                            <th>Pts</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fairplay as $pos => $f): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge <?= $pos < 3 ? 'bg-success' : 'bg-secondary' ?>">
                                                        <?= $pos + 1 ?>
                                                    </span>
                                                </td>
                                                <td class="team-name fw-medium"><?= htmlspecialchars($f['equipo']) ?></td>
                                                <td class="hide-xs">
                                                    <span class="badge bg-danger"><?= (int)$f['rojas'] ?></span>
                                                </td>
                                                <td class="hide-xs">
                                                    <span class="badge bg-warning text-dark"><?= (int)$f['amarillas'] ?></span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $pts = (int)$f['puntos_disciplina'];
                                                    $color = $pts < 5 ? 'success' : ($pts < 10 ? 'warning' : 'danger');
                                                    ?>
                                                    <span class="badge bg-<?= $color ?>">
                                                        <?= $pts ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-info-circle"></i> Puntos disciplina: Amarilla = 1pt, Roja = 3pts. Menos es mejor.
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="container">
        <footer class="footer">
            <h5><i class="fas fa-futbol me-2"></i>Altos del Paracao</h5>
            <p class="mb-2 opacity-75"><?= htmlspecialchars($campeonato['nombre']) ?></p>
            <small class="opacity-50">
                © <?= date('Y') ?> | Actualizado: <?= date('d/m/Y H:i') ?>
            </small>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mantener tab activo en localStorage
        document.addEventListener('DOMContentLoaded', function() {
            var activeTab = localStorage.getItem('activeTab');
            if (activeTab) {
                var tab = document.querySelector('[data-bs-target="' + activeTab + '"]');
                if (tab) {
                    var bsTab = new bootstrap.Tab(tab);
                    bsTab.show();
                }
            }
            
            var tabs = document.querySelectorAll('[data-bs-toggle="tab"]');
            tabs.forEach(function(tab) {
                tab.addEventListener('shown.bs.tab', function(e) {
                    var target = e.target.getAttribute('data-bs-target');
                    localStorage.setItem('activeTab', target);
                });
            });
        });
    </script>
</body>
</html>