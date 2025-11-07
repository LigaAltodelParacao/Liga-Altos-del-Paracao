<?php
require_once '../config.php';

$db = Database::getInstance()->getConnection();

// Buscar campeonato activo llamado "Torneo Nocturno"
$stmt = $db->prepare("SELECT id, nombre FROM campeonatos WHERE activo = 1 AND nombre LIKE ? LIMIT 1");
$stmt->execute(['%Torneo Nocturno%']);
$campeonato = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$campeonato) {
    header('Location: ../index.php');
    exit;
}

// IDs de categorías de este campeonato (para filtrar todo)
$stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
$stmt->execute([$campeonato['id']]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
$categoria_ids = array_column($categorias, 'id');
$placeholder_cat = implode(',', array_fill(0, count($categoria_ids), '?'));

// Formatos de zonas (si existen)
$stmt = $db->prepare("SELECT * FROM campeonatos_formato WHERE campeonato_id = ? AND activo = 1 ORDER BY created_at DESC");
$stmt->execute([$campeonato['id']]);
$formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Posiciones por zonas (si hay formatos)
$zonas_data = [];
if (!empty($formatos)) {
    foreach ($formatos as $formato) {
        $stmtZ = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
        $stmtZ->execute([$formato['id']]);
        $zonas = $stmtZ->fetchAll(PDO::FETCH_ASSOC);
        foreach ($zonas as &$z) {
            $stmtT = $db->prepare("SELECT ez.*, e.nombre as equipo, e.logo FROM equipos_zonas ez JOIN equipos e ON ez.equipo_id = e.id WHERE ez.zona_id = ? ORDER BY ez.puntos DESC, ez.diferencia_gol DESC, ez.goles_favor DESC");
            $stmtT->execute([$z['id']]);
            $z['tabla'] = $stmtT->fetchAll(PDO::FETCH_ASSOC);
        }
        $zonas_data[] = ['formato' => $formato, 'zonas' => $zonas];
    }
}

// Resultados recientes (últimos 20) de este campeonato
$resultados = [];
if (!empty($categoria_ids)) {
    $sql = "
        SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante, c.nombre as cancha, f.numero_fecha
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        WHERE f.categoria_id IN ($placeholder_cat) AND p.estado = 'finalizado'
        ORDER BY p.finalizado_at DESC, p.fecha_partido DESC, p.hora_partido DESC
        LIMIT 20
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($categoria_ids);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fixture próximo - incluir partidos de zonas y normales
$fixture = [];
$fixture_zonas = [];

// Partidos normales
if (!empty($categoria_ids)) {
    $sql = "
        SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante, c.nombre as cancha, f.numero_fecha
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        WHERE f.categoria_id IN ($placeholder_cat) AND p.estado IN ('programado','sin_asignar') AND (p.tipo_torneo = 'normal' OR p.tipo_torneo IS NULL)
        ORDER BY p.fecha_partido ASC, p.hora_partido ASC
        LIMIT 20
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($categoria_ids);
    $fixture = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Partidos de zonas (agrupados por zona y jornada)
if (!empty($formatos)) {
    foreach ($formatos as $formato) {
        $stmtZ = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
        $stmtZ->execute([$formato['id']]);
        $zonas_fixture = $stmtZ->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($zonas_fixture as $zona) {
            $sql = "
                SELECT p.*, el.nombre as equipo_local, el.logo as logo_local, 
                       ev.nombre as equipo_visitante, ev.logo as logo_visitante, 
                       c.nombre as cancha, f.numero_fecha, z.nombre as zona_nombre
                FROM partidos p
                JOIN zonas z ON p.zona_id = z.id
                JOIN equipos el ON p.equipo_local_id = el.id
                JOIN equipos ev ON p.equipo_visitante_id = ev.id
                LEFT JOIN canchas c ON p.cancha_id = c.id
                LEFT JOIN fechas f ON p.fecha_id = f.id
                WHERE p.zona_id = ? AND p.tipo_torneo = 'zona'
                ORDER BY p.jornada_zona ASC, p.fecha_partido ASC, p.hora_partido ASC
            ";
            $stmt = $db->prepare($sql);
            $stmt->execute([$zona['id']]);
            $partidos_zona = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Agregar siempre, incluso si está vacío, para mostrar la estructura
            $fixture_zonas[] = [
                'zona' => $zona,
                'partidos' => $partidos_zona
            ];
        }
    }
}

// Goleadores del torneo nocturno
$goleadores = [];
if (!empty($categoria_ids)) {
    $sql = "
        SELECT j.id as jugador_id, j.apellido_nombre, e.nombre as equipo, COUNT(ev.id) as goles
        FROM eventos_partido ev
        JOIN partidos p ON ev.partido_id = p.id
        JOIN fechas f ON p.fecha_id = f.id
        JOIN jugadores j ON ev.jugador_id = j.id
        JOIN equipos e ON j.equipo_id = e.id
        WHERE ev.tipo_evento = 'gol' AND f.categoria_id IN ($placeholder_cat)
        GROUP BY j.id, j.apellido_nombre, e.nombre
        HAVING goles > 0
        ORDER BY goles DESC, j.apellido_nombre ASC
        LIMIT 50
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($categoria_ids);
    $goleadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fairplay del torneo nocturno (tarjetas por equipo)
$fairplay = [];
if (!empty($categoria_ids)) {
    $sql = "
        SELECT e.id as equipo_id, e.nombre as equipo,
               SUM(CASE WHEN ev.tipo_evento = 'amarilla' THEN 1 ELSE 0 END) as amarillas,
               SUM(CASE WHEN ev.tipo_evento = 'roja' THEN 1 ELSE 0 END) as rojas
        FROM equipos e
        JOIN jugadores j ON j.equipo_id = e.id
        JOIN eventos_partido ev ON ev.jugador_id = j.id
        JOIN partidos p ON ev.partido_id = p.id
        JOIN fechas f ON p.fecha_id = f.id
        WHERE f.categoria_id IN ($placeholder_cat)
        GROUP BY e.id, e.nombre
        ORDER BY rojas ASC, amarillas ASC, equipo ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($categoria_ids);
    $fairplay = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Sanciones del torneo nocturno (jugadores en equipos de estas categorías)
$sanciones = [];
if (!empty($categoria_ids)) {
    $sql = "
        SELECT s.*, j.apellido_nombre, e.nombre as equipo
        FROM sanciones s
        JOIN jugadores j ON s.jugador_id = j.id
        JOIN equipos e ON j.equipo_id = e.id
        WHERE e.categoria_id IN ($placeholder_cat) AND s.activa = 1
        ORDER BY s.fecha_sancion DESC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($categoria_ids);
    $sanciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Equipos del torneo nocturno
$equipos = [];
if (!empty($categoria_ids)) {
    $sql = "
        SELECT id, nombre, logo FROM equipos WHERE categoria_id IN ($placeholder_cat) AND activo = 1 ORDER BY nombre
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
        }

        .hero-section {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .nav-tabs {
            background: #ffffff;
            border-radius: 12px;
            padding: 8px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-light);
        }

        .nav-tabs .nav-link {
            border: none;
            border-radius: 8px;
            color: var(--text-muted);
            font-weight: 500;
            padding: 12px 20px;
            margin: 0 2px;
            transition: all 0.3s ease;
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
            padding: 30px;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            border: 1px solid var(--border-light);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #146c43 100%);
            color: white;
            border: none;
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }

        .card-body {
            padding: 25px;
        }

        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .table thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
            padding: 15px 12px;
            font-size: 0.9rem;
        }

        .table tbody td {
            padding: 12px;
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
            width: 28px;
            height: 28px;
            object-fit: contain;
            border-radius: 4px;
            background: #f8f9fa;
            border: 1px solid var(--border-light);
        }

        .team-initial {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            background: var(--primary-color);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .alert {
            border: none;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .alert-info {
            background: #e7f3ff;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }

        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }

        .badge {
            padding: 6px 12px;
            font-weight: 600;
            border-radius: 6px;
        }

        .stats-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
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
            padding: 30px;
            text-align: center;
        }

        .footer h5 {
            color: white;
            font-weight: 600;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .tab-pane {
                padding: 20px 15px;
            }

            .nav-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                white-space: nowrap;
            }

            .nav-tabs .nav-link {
                padding: 10px 15px;
                font-size: 0.9rem;
            }

            .card-body {
                padding: 20px 15px;
            }

            .table-responsive {
                font-size: 0.9rem;
            }
        }

        /* Animaciones sutiles */
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-futbol"></i> Altos del Paracao
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="../public/resultados.php">Resultados</a></li>
                    <li class="nav-item"><a class="nav-link" href="../public/tablas.php">Posiciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="../public/goleadores.php">Goleadores</a></li>
                    <li class="nav-item"><a class="nav-link" href="../public/fixture.php">Fixture</a></li>
                    <li class="nav-item"><a class="nav-link" href="../public/sanciones.php">Sanciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="../public/historial_equipos.php">Equipos</a></li>
                    <li class="nav-item"><a class="nav-link" href="../public/fairplay.php">Fairplay</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="h2 fw-bold text-white mb-2">
                        <i class="fas fa-moon text-warning"></i> <?php echo htmlspecialchars($campeonato['nombre']); ?>
                    </h1>
                    <p class="text-white-50 mb-0">Información completa del torneo nocturno</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <span class="badge bg-warning text-dark fs-6">
                        <i class="fas fa-star"></i> Edición Anual
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="container my-4">
        <!-- Tabs de Navegación -->
        <ul class="nav nav-tabs" id="nocturnoTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-posiciones" type="button" role="tab">
                    <i class="fas fa-trophy"></i> Posiciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fixture" type="button" role="tab">
                    <i class="fas fa-calendar"></i> Fixture
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-resultados" type="button" role="tab">
                    <i class="fas fa-list"></i> Resultados
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-goleadores" type="button" role="tab">
                    <i class="fas fa-futbol"></i> Goleadores
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sanciones" type="button" role="tab">
                    <i class="fas fa-ban"></i> Sanciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-equipos" type="button" role="tab">
                    <i class="fas fa-users"></i> Equipos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fairplay" type="button" role="tab">
                    <i class="fas fa-shield-alt"></i> Fairplay
                </button>
            </li>
        </ul>

        <!-- Contenido de las Tabs -->
        <div class="tab-content">
            <!-- Posiciones por Zonas -->
            <div class="tab-pane fade show active" id="tab-posiciones" role="tabpanel">
                <?php if (empty($zonas_data)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay formatos de zonas configurados para este torneo.
                    </div>
                <?php else: ?>
                    <?php foreach ($zonas_data as $pack): $formato = $pack['formato']; $zlist = $pack['zonas']; ?>
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-layer-group me-2"></i>
                                Formato con <?php echo (int)$formato['cantidad_zonas']; ?> zonas
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($zlist as $zona): ?>
                                        <div class="col-lg-6 mb-4">
                                            <div class="stats-card">
                                                <h6 class="mb-3 text-primary">
                                                    <i class="fas fa-flag me-2"></i>
                                                    <?php echo htmlspecialchars($zona['nombre']); ?>
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>#</th>
                                                                <th>Equipo</th>
                                                                <th>Pts</th>
                                                                <th>PJ</th>
                                                                <th>GF</th>
                                                                <th>GC</th>
                                                                <th>Dif</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php $pos=1; foreach ($zona['tabla'] as $r): ?>
                                                                <tr>
                                                                    <td>
                                                                        <span class="badge <?= $pos <= 3 ? 'bg-success' : 'bg-secondary' ?>">
                                                                            <?= $pos++ ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <?php if (!empty($r['logo'])): ?>
                                                                                <img src="../uploads/<?= htmlspecialchars($r['logo']) ?>" 
                                                                                     class="team-logo me-2" alt="Logo">
                                                                            <?php else: ?>
                                                                                <div class="team-initial me-2">
                                                                                    <?= substr($r['equipo'], 0, 1) ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <span class="fw-medium"><?php echo htmlspecialchars($r['equipo']); ?></span>
                                                                        </div>
                                                                    </td>
                                                                    <td><strong><?php echo (int)$r['puntos']; ?></strong></td>
                                                                    <td><?php echo (int)$r['partidos_jugados']; ?></td>
                                                                    <td><?php echo (int)$r['goles_favor']; ?></td>
                                                                    <td><?php echo (int)$r['goles_contra']; ?></td>
                                                                    <td>
                                                                        <span class="badge <?= $r['diferencia_gol'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                                                            <?php echo (int)$r['diferencia_gol']; ?>
                                                                        </span>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Fixture -->
            <div class="tab-pane fade" id="tab-fixture" role="tabpanel">
                <?php if (empty($fixture) && empty($fixture_zonas)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay partidos programados. El fixture aún no ha sido generado.
                    </div>
                <?php else: ?>
                    <!-- Partidos de Zonas -->
                    <?php if (!empty($fixture_zonas)): ?>
                        <?php foreach ($fixture_zonas as $fz): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-flag me-2"></i>
                                        <?= htmlspecialchars($fz['zona']['nombre']) ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($fz['partidos'])): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            No hay partidos generados para esta zona.
                                        </div>
                                    <?php else: ?>
                                        <?php
                                        // Agrupar por jornada
                                        $por_jornada = [];
                                        foreach ($fz['partidos'] as $p) {
                                            $jornada = $p['jornada_zona'] ?? 1;
                                            if (!isset($por_jornada[$jornada])) {
                                                $por_jornada[$jornada] = [];
                                            }
                                            $por_jornada[$jornada][] = $p;
                                        }
                                        ksort($por_jornada);
                                        ?>
                                        <?php foreach ($por_jornada as $jornada => $partidos): ?>
                                            <div class="mb-4">
                                                <h6 class="mb-3">
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-calendar me-1"></i>Jornada <?= $jornada ?>
                                                    </span>
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Fecha</th>
                                                                <th>Hora</th>
                                                                <th>Local</th>
                                                                <th></th>
                                                                <th>Visitante</th>
                                                                <th>Cancha</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($partidos as $p): ?>
                                                                <tr>
                                                                    <td><?= $p['fecha_partido'] ? date('d/m/Y', strtotime($p['fecha_partido'])) : '-' ?></td>
                                                                    <td><?= $p['hora_partido'] ? date('H:i', strtotime($p['hora_partido'])) : '-' ?></td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <?php if (!empty($p['logo_local'])): ?>
                                                                                <img src="../uploads/<?= htmlspecialchars($p['logo_local']) ?>" 
                                                                                     class="team-logo me-2" alt="Logo">
                                                                            <?php else: ?>
                                                                                <div class="team-initial me-2">
                                                                                    <?= substr($p['equipo_local'], 0, 1) ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <span class="fw-medium"><?= htmlspecialchars($p['equipo_local']) ?></span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-center text-muted">vs</td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <?php if (!empty($p['logo_visitante'])): ?>
                                                                                <img src="../uploads/<?= htmlspecialchars($p['logo_visitante']) ?>" 
                                                                                     class="team-logo me-2" alt="Logo">
                                                                            <?php else: ?>
                                                                                <div class="team-initial me-2">
                                                                                    <?= substr($p['equipo_visitante'], 0, 1) ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <span class="fw-medium"><?= htmlspecialchars($p['equipo_visitante']) ?></span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-muted"><?= htmlspecialchars($p['cancha'] ?? 'Por confirmar') ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Partidos Normales -->
                    <?php if (!empty($fixture)): ?>
                        <div class="card">
                            <div class="card-header bg-success">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar me-2"></i>Partidos del Torneo
                                </h6>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Hora</th>
                                                <th>Local</th>
                                                <th></th>
                                                <th>Visitante</th>
                                                <th>Cancha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fixture as $p): ?>
                                                <tr>
                                                    <td><?= $p['fecha_partido'] ? date('d/m/Y', strtotime($p['fecha_partido'])) : '-' ?></td>
                                                    <td><?= $p['hora_partido'] ? date('H:i', strtotime($p['hora_partido'])) : '-' ?></td>
                                                    <td class="fw-medium"><?= htmlspecialchars($p['equipo_local']) ?></td>
                                                    <td class="text-center text-muted">vs</td>
                                                    <td class="fw-medium"><?= htmlspecialchars($p['equipo_visitante']) ?></td>
                                                    <td class="text-muted"><?= htmlspecialchars($p['cancha'] ?? 'Por confirmar') ?></td>
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

            <!-- Resultados -->
            <div class="tab-pane fade" id="tab-resultados" role="tabpanel">
                <?php if (empty($resultados)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sin resultados recientes.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Local</th>
                                            <th></th>
                                            <th>Visitante</th>
                                            <th>Resultado</th>
                                            <th>Cancha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resultados as $p): ?>
                                            <tr>
                                                <td class="text-muted"><?= htmlspecialchars($p['fecha_partido'] ?? '-') ?></td>
                                                <td class="fw-medium"><?= htmlspecialchars($p['equipo_local']) ?></td>
                                                <td class="text-center text-muted">vs</td>
                                                <td class="fw-medium"><?= htmlspecialchars($p['equipo_visitante']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary fs-6">
                                                        <strong><?= (int)$p['goles_local'] ?> - <?= (int)$p['goles_visitante'] ?></strong>
                                                    </span>
                                                </td>
                                                <td class="text-muted"><?= htmlspecialchars($p['cancha'] ?? '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Goleadores -->
            <div class="tab-pane fade" id="tab-goleadores" role="tabpanel">
                <?php if (empty($goleadores)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sin goles registrados.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Jugador</th>
                                            <th>Equipo</th>
                                            <th>Goles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $pos = 1;
                                        foreach ($goleadores as $g): 
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge <?= $pos <= 3 ? 'bg-warning' : 'bg-secondary' ?> me-2">
                                                            <?= $pos++ ?>
                                                        </span>
                                                        <span class="fw-medium"><?= htmlspecialchars($g['apellido_nombre']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-muted"><?= htmlspecialchars($g['equipo']) ?></td>
                                                <td>
                                                    <span class="badge bg-success fs-6">
                                                        <i class="fas fa-futbol me-1"></i>
                                                        <strong><?= (int)$g['goles'] ?></strong>
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

            <!-- Sanciones -->
            <div class="tab-pane fade" id="tab-sanciones" role="tabpanel">
                <?php if (empty($sanciones)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sin sanciones activas.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Jugador</th>
                                            <th>Equipo</th>
                                            <th>Tipo</th>
                                            <th>Partidos</th>
                                            <th>Cumplidos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sanciones as $s): ?>
                                            <tr>
                                                <td class="text-muted"><?= htmlspecialchars($s['fecha_sancion']) ?></td>
                                                <td class="fw-medium"><?= htmlspecialchars($s['apellido_nombre']) ?></td>
                                                <td class="text-muted"><?= htmlspecialchars($s['equipo']) ?></td>
                                                <td>
                                                    <span class="badge bg-<?= strpos($s['tipo'], 'roja') !== false ? 'danger' : 'warning' ?>">
                                                        <?= htmlspecialchars($s['tipo']) ?>
                                                    </span>
                                                </td>
                                                <td><strong><?= (int)$s['partidos_suspension'] ?></strong></td>
                                                <td class="text-muted"><?= (int)$s['partidos_cumplidos'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Equipos -->
            <div class="tab-pane fade" id="tab-equipos" role="tabpanel">
                <?php if (empty($equipos)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No hay equipos registrados.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($equipos as $e): ?>
                                    <div class="col-6 col-md-4 col-lg-3 mb-3">
                                        <div class="stats-card text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <?php if (!empty($e['logo'])): ?>
                                                    <img src="../uploads/<?= $e['logo'] ?>" 
                                                         alt="<?= htmlspecialchars($e['nombre']) ?>" 
                                                         class="team-logo mb-2">
                                                <?php else: ?>
                                                    <div class="team-initial mb-2">
                                                        <?= substr($e['nombre'], 0, 1) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="fw-medium small"><?= htmlspecialchars($e['nombre']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Fairplay -->
            <div class="tab-pane fade" id="tab-fairplay" role="tabpanel">
                <?php if (empty($fairplay)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sin datos de tarjetas registradas.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Equipo</th>
                                            <th>Rojas</th>
                                            <th>Amarillas</th>
                                            <th>Fairplay</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fairplay as $f): ?>
                                            <tr>
                                                <td class="fw-medium"><?= htmlspecialchars($f['equipo']) ?></td>
                                                <td>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-square me-1"></i><?= (int)$f['rojas'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-square me-1"></i><?= (int)$f['amarillas'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $puntuacion = (int)$f['rojas'] * -2 + (int)$f['amarillas'] * -0.5;
                                                    $color = $puntuacion > -5 ? 'success' : ($puntuacion > -10 ? 'warning' : 'danger');
                                                    ?>
                                                    <span class="badge bg-<?= $color ?>">
                                                        <i class="fas fa-shield-alt me-1"></i>
                                                        <?= number_format(abs($puntuacion), 1) ?>
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
        </div>
    </div>

    <!-- Footer -->
    <div class="container">
        <footer class="footer">
            <h5><i class="fas fa-futbol me-2"></i>Altos del Paracao</h5>
            <p class="mb-0 opacity-75">Torneo Nocturno - Información consolidada</p>
            <small class="opacity-50">
                © <?= date('Y') ?> - Actualizado: <?= date('d/m/Y H:i') ?>
            </small>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>