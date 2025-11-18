<?php
require_once __DIR__ . '/../config.php';

$db = Database::getInstance()->getConnection();

// Buscar campeonato activo llamado "Torneo Nocturno"
$stmt = $db->prepare("SELECT id, nombre FROM campeonatos WHERE activo = 1 AND nombre LIKE ? LIMIT 1");
$stmt->execute(['%Torneo Nocturno%']);
$campeonato = $stmt->fetch(PDO::FETCH_ASSOC);

// Si no hay campeonato activo, buscar el más reciente
if (!$campeonato) {
    $stmt = $db->prepare("SELECT id, nombre FROM campeonatos WHERE nombre LIKE ? ORDER BY created_at DESC LIMIT 1");
    $stmt->execute(['%Torneo Nocturno%']);
    $campeonato = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Si aún no existe ningún campeonato nocturno, mostrar mensaje
$sin_campeonato = false;
if (!$campeonato) {
    $sin_campeonato = true;
    $campeonato = ['id' => 0, 'nombre' => 'Torneo Nocturno'];
}

// IDs de categorías de este campeonato (para filtrar todo)
$categorias = [];
$categoria_ids = [];
if (!$sin_campeonato) {
    $stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
    $stmt->execute([$campeonato['id']]);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $categoria_ids = array_column($categorias, 'id');
}

$placeholder_cat = '';
if (!empty($categoria_ids)) {
    $placeholder_cat = implode(',', array_fill(0, count($categoria_ids), '?'));
}

// Formatos de zonas (si existen)
$formatos = [];
if (!$sin_campeonato) {
    $stmt = $db->prepare("SELECT * FROM campeonatos_formato WHERE campeonato_id = ? AND activo = 1 ORDER BY created_at DESC");
    $stmt->execute([$campeonato['id']]);
    $formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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
        SELECT p.*, el.nombre as equipo_local, el.logo as logo_local, 
               ev.nombre as equipo_visitante, ev.logo as logo_visitante, 
               c.nombre as cancha, f.numero_fecha
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
        SELECT p.*, el.nombre as equipo_local, el.logo as logo_local,
               ev.nombre as equipo_visitante, ev.logo as logo_visitante,
               c.nombre as cancha, f.numero_fecha
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        WHERE f.categoria_id IN ($placeholder_cat) AND p.estado IN ('programado','sin_asignar') 
        AND (p.tipo_torneo = 'normal' OR p.tipo_torneo IS NULL)
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

// Sanciones del torneo nocturno - CORREGIDO para incluir campeonato_id
$sanciones = [];
if (!empty($categoria_ids)) {
    $sql = "
        SELECT s.*, j.apellido_nombre, j.dni, e.nombre as equipo, e.logo as equipo_logo, c.nombre as categoria,
               (s.partidos_suspension - s.partidos_cumplidos) as fechas_restantes
        FROM sanciones s
        JOIN jugadores j ON s.jugador_id = j.id
        JOIN equipos e ON j.equipo_id = e.id
        JOIN categorias c ON e.categoria_id = c.id
        WHERE s.activa = 1 
        AND (
            s.campeonato_id = ? 
            OR (s.campeonato_id IS NULL AND c.campeonato_id = ?)
            OR e.categoria_id IN ($placeholder_cat)
        )
        ORDER BY s.fecha_sancion DESC
    ";
    $params = array_merge([$campeonato['id'], $campeonato['id']], $categoria_ids);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
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
    <title><?php echo htmlspecialchars($campeonato['nombre']); ?> - Liga Altos del Paracao</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #198754;
            --primary-dark: #146c43;
            --secondary-color: #6c757d;
            --accent-color: #ffc107;
            --bg-gradient-start: #f5f7fa;
            --bg-gradient-end: #c3cfe2;
            --card-bg: #ffffff;
            --text-dark: #212529;
            --text-muted: #6c757d;
            --border-light: #dee2e6;
            --shadow-sm: 0 2px 4px rgba(0,0,0,0.08);
            --shadow-md: 0 4px 12px rgba(0,0,0,0.12);
            --shadow-lg: 0 8px 24px rgba(0,0,0,0.15);
        }

        body {
            background: linear-gradient(135deg, var(--bg-gradient-start) 0%, var(--bg-gradient-end) 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .hero-section {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            padding: 2rem 0;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            border-bottom: 3px solid var(--accent-color);
        }

        .hero-title {
            color: #ffffff;
            font-weight: 700;
            font-size: 2rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-subtitle {
            color: rgba(255,255,255,0.8);
            font-size: 1rem;
        }

        .nav-tabs {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 10px;
            box-shadow: var(--shadow-md);
            border: none;
            overflow-x: auto;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .nav-tabs::-webkit-scrollbar {
            height: 6px;
        }

        .nav-tabs::-webkit-scrollbar-thumb {
            background: var(--primary-color);
            border-radius: 3px;
        }

        .nav-tabs .nav-link {
            border: none;
            border-radius: 8px;
            color: var(--text-muted);
            font-weight: 600;
            padding: 12px 24px;
            margin: 0 4px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .nav-tabs .nav-link:hover {
            background: rgba(25, 135, 84, 0.1);
            color: var(--primary-color);
            transform: translateY(-2px);
        }

        .nav-tabs .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(25, 135, 84, 0.4);
        }

        .tab-content {
            background: transparent;
            padding: 0;
        }

        .tab-pane {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            margin-bottom: 24px;
            overflow: hidden;
            background: var(--card-bg);
            transition: all 0.3s ease;
        }

        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-4px);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 18px 24px;
            font-weight: 700;
            font-size: 1.1rem;
            border-bottom: 3px solid var(--accent-color);
        }

        .card-body {
            padding: 24px;
        }

        .stats-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            box-shadow: var(--shadow-sm);
            border: 2px solid var(--border-light);
            transition: all 0.3s ease;
        }

        .stats-card:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-4px);
            border-color: var(--primary-color);
        }

        .table {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 0;
        }

        .table thead th {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: none;
            font-weight: 700;
            color: var(--text-dark);
            padding: 16px 12px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 14px 12px;
            border-color: var(--border-light);
            vertical-align: middle;
        }

        .table tbody tr {
            transition: all 0.2s ease;
        }

        .table tbody tr:hover {
            background: rgba(25, 135, 84, 0.05);
            transform: scale(1.01);
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(248, 249, 250, 0.5);
        }

        .team-logo {
            width: 32px;
            height: 32px;
            object-fit: contain;
            border-radius: 6px;
            background: #f8f9fa;
            border: 2px solid var(--border-light);
            padding: 2px;
        }

        .team-initial {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }

        .badge {
            padding: 6px 14px;
            font-weight: 600;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .badge-position {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 700;
        }

        .alert {
            border: none;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 24px;
            box-shadow: var(--shadow-sm);
            border-left: 4px solid;
        }

        .alert-info {
            background: linear-gradient(135deg, #e7f3ff 0%, #d0ebff 100%);
            color: #0c5460;
            border-left-color: #17a2b8;
        }

        .alert-warning {
            background: linear-gradient(135deg, #fff3cd 0%, #ffebad 100%);
            color: #856404;
            border-left-color: #ffc107;
        }

        .footer {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            color: white;
            border-radius: 16px;
            margin: 40px 0 24px 0;
            padding: 40px;
            text-align: center;
            box-shadow: var(--shadow-lg);
        }

        .footer h5 {
            color: var(--accent-color);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 1.5rem;
            }

            .nav-tabs .nav-link {
                padding: 10px 16px;
                font-size: 0.9rem;
            }

            .card-body {
                padding: 16px;
            }

            .table th, .table td {
                padding: 10px 8px;
                font-size: 0.85rem;
            }

            .team-logo, .team-initial {
                width: 24px;
                height: 24px;
                font-size: 0.75rem;
            }

            .d-none-mobile {
                display: none !important;
            }
        }

        @media (max-width: 576px) {
            .hero-title {
                font-size: 1.25rem;
            }

            .nav-tabs .nav-link {
                padding: 8px 12px;
                font-size: 0.8rem;
            }

            .table th, .table td {
                padding: 8px 6px;
                font-size: 0.8rem;
            }

            .hide-xs {
                display: none !important;
            }
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
                    <h1 class="hero-title mb-2">
                        <i class="fas fa-moon text-warning me-2"></i><?php echo htmlspecialchars($campeonato['nombre']); ?>
                    </h1>
                    <p class="hero-subtitle mb-0">
                        <i class="fas fa-futbol me-2"></i>Información completa del torneo nocturno
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 10px 20px;">
                        <i class="fas fa-star me-2"></i>Edición Anual
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Contenido Principal -->
    <div class="container my-4">
        <!-- Tabs de Navegación -->
        <ul class="nav nav-tabs mb-4" id="nocturnoTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-posiciones" type="button" role="tab">
                    <i class="fas fa-trophy me-2"></i>Posiciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fixture" type="button" role="tab">
                    <i class="fas fa-calendar me-2"></i>Fixture
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-resultados" type="button" role="tab">
                    <i class="fas fa-list me-2"></i>Resultados
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-goleadores" type="button" role="tab">
                    <i class="fas fa-futbol me-2"></i>Goleadores
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sanciones" type="button" role="tab">
                    <i class="fas fa-ban me-2"></i>Sanciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-equipos" type="button" role="tab">
                    <i class="fas fa-users me-2"></i>Equipos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fairplay" type="button" role="tab">
                    <i class="fas fa-shield-alt me-2"></i>Fairplay
                </button>
            </li>
        </ul>

        <!-- Contenido de las Tabs -->
        <div class="tab-content">
            <!-- Verificar si hay campeonato -->
            <?php if ($sin_campeonato): ?>
                <div class="tab-pane fade show active">
                    <div class="alert alert-warning">
                        <h5 class="mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay Torneo Nocturno configurado
                        </h5>
                        <p class="mb-3">
                            Actualmente no existe un campeonato "Torneo Nocturno" en el sistema.
                        </p>
                        <p class="mb-0">
                            <strong>Para administradores:</strong> Crea un nuevo campeonato con el nombre "Torneo Nocturno" desde el panel de administración.
                        </p>
                    </div>
                </div>
            <?php elseif (empty($categoria_ids)): ?>
                <div class="tab-pane fade show active">
                    <div class="alert alert-warning">
                        <h5 class="mb-3">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Sin categorías activas
                        </h5>
                        <p class="mb-3">
                            El campeonato <strong><?= htmlspecialchars($campeonato['nombre']) ?></strong> no tiene categorías activas.
                        </p>
                        <p class="mb-0">
                            <strong>Para administradores:</strong> Asigna categorías al campeonato desde el panel de administración.
                        </p>
                    </div>
                </div>
            <?php else: ?>
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
                                Formato con <?php echo (int)$formato['cantidad_zonas']; ?> zonas - <?php echo (int)$formato['equipos_clasifican']; ?> equipos clasifican
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($zlist as $zona): ?>
                                        <div class="col-lg-6 mb-4">
                                            <div class="stats-card">
                                                <h6 class="mb-3 fw-bold text-primary">
                                                    <i class="fas fa-flag me-2"></i>
                                                    <?php echo htmlspecialchars($zona['nombre']); ?>
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 50px;">Pos</th>
                                                                <th>Equipo</th>
                                                                <th class="text-center">Pts</th>
                                                                <th class="text-center d-none-mobile">PJ</th>
                                                                <th class="text-center hide-xs">DG</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php $pos=1; foreach ($zona['tabla'] as $r): ?>
                                                                <tr>
                                                                    <td class="text-center">
                                                                        <span class="badge-position <?= $pos <= $formato['equipos_clasifican']/2 ? 'bg-success' : 'bg-secondary' ?>">
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
                                                                            <span class="fw-semibold"><?php echo htmlspecialchars($r['equipo']); ?></span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-center"><strong class="text-primary"><?php echo (int)$r['puntos']; ?></strong></td>
                                                                    <td class="text-center d-none-mobile"><?php echo (int)$r['partidos_jugados']; ?></td>
                                                                    <td class="text-center hide-xs">
                                                                        <span class="badge <?= $r['diferencia_gol'] >= 0 ? 'bg-success' : 'bg-danger' ?>">
                                                                            <?= $r['diferencia_gol'] >= 0 ? '+' : '' ?><?php echo (int)$r['diferencia_gol']; ?>
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
                            <?php if (!empty($fz['partidos'])): ?>
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <i class="fas fa-flag me-2"></i>
                                        <?= htmlspecialchars($fz['zona']['nombre']) ?>
                                    </div>
                                    <div class="card-body">
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
                                                    <span class="badge bg-primary" style="font-size: 0.95rem;">
                                                        <i class="fas fa-calendar me-1"></i>Jornada <?= $jornada ?>
                                                    </span>
                                                </h6>
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th class="hide-xs">Fecha</th>
                                                                <th class="d-none-mobile">Hora</th>
                                                                <th>Local</th>
                                                                <th class="text-center" style="width: 50px;"></th>
                                                                <th>Visitante</th>
                                                                <th class="hide-xs">Cancha</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($partidos as $p): ?>
                                                                <tr>
                                                                    <td class="hide-xs text-muted">
                                                                        <?= $p['fecha_partido'] ? date('d/m/Y', strtotime($p['fecha_partido'])) : '-' ?>
                                                                    </td>
                                                                    <td class="d-none-mobile text-muted">
                                                                        <?= $p['hora_partido'] ? date('H:i', strtotime($p['hora_partido'])) : '-' ?>
                                                                    </td>
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
                                                                            <span class="fw-semibold"><?= htmlspecialchars($p['equipo_local']) ?></span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-center text-muted fw-bold">vs</td>
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
                                                                            <span class="fw-semibold"><?= htmlspecialchars($p['equipo_visitante']) ?></span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-muted hide-xs">
                                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                                        <?= htmlspecialchars($p['cancha'] ?? 'Por confirmar') ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Partidos Normales -->
                    <?php if (!empty($fixture)): ?>
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-calendar me-2"></i>Próximos Partidos
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th class="hide-xs">Fecha</th>
                                                <th class="d-none-mobile">Hora</th>
                                                <th>Local</th>
                                                <th class="text-center" style="width: 50px;"></th>
                                                <th>Visitante</th>
                                                <th class="hide-xs">Cancha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fixture as $p): ?>
                                                <tr>
                                                    <td class="hide-xs text-muted">
                                                        <?= $p['fecha_partido'] ? date('d/m/Y', strtotime($p['fecha_partido'])) : '-' ?>
                                                    </td>
                                                    <td class="d-none-mobile text-muted">
                                                        <?= $p['hora_partido'] ? date('H:i', strtotime($p['hora_partido'])) : '-' ?>
                                                    </td>
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
                                                            <span class="fw-semibold"><?= htmlspecialchars($p['equipo_local']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center text-muted fw-bold">vs</td>
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
                                                            <span class="fw-semibold"><?= htmlspecialchars($p['equipo_visitante']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-muted hide-xs">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?= htmlspecialchars($p['cancha'] ?? 'Por confirmar') ?>
                                                    </td>
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
                        <i class="fas fa-info-circle me-2"></i>
                        Sin resultados recientes.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-history me-2"></i>Últimos Resultados
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th class="hide-xs">Fecha</th>
                                            <th>Local</th>
                                            <th class="text-center">Resultado</th>
                                            <th>Visitante</th>
                                            <th class="hide-xs">Cancha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resultados as $p): ?>
                                            <tr>
                                                <td class="text-muted hide-xs">
                                                    <?= $p['fecha_partido'] ? date('d/m/Y', strtotime($p['fecha_partido'])) : '-' ?>
                                                </td>
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
                                                        <span class="fw-semibold"><?= htmlspecialchars($p['equipo_local']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-dark" style="font-size: 1rem; padding: 8px 16px;">
                                                        <strong><?= (int)$p['goles_local'] ?></strong>
                                                        <span class="mx-2">-</span>
                                                        <strong><?= (int)$p['goles_visitante'] ?></strong>
                                                    </span>
                                                </td>
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
                                                        <span class="fw-semibold"><?= htmlspecialchars($p['equipo_visitante']) ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-muted hide-xs">
                                                    <i class="fas fa-map-marker-alt me-1"></i>
                                                    <?= htmlspecialchars($p['cancha'] ?? '-') ?>
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

            <!-- Goleadores -->
            <div class="tab-pane fade" id="tab-goleadores" role="tabpanel">
                <?php if (empty($goleadores)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Sin goles registrados aún.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-trophy me-2"></i>Tabla de Goleadores
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th style="width: 60px;">Pos</th>
                                            <th>Jugador</th>
                                            <th class="d-none-mobile">Equipo</th>
                                            <th class="text-center">Goles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $pos = 1;
                                        foreach ($goleadores as $g): 
                                            $medalla = '';
                                            if ($pos == 1) $medalla = '🥇';
                                            elseif ($pos == 2) $medalla = '🥈';
                                            elseif ($pos == 3) $medalla = '🥉';
                                        ?>
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge-position <?= $pos <= 3 ? 'bg-warning text-dark' : 'bg-secondary' ?>">
                                                        <?= $pos++ ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div>
                                                        <span class="fw-bold"><?= $medalla ?> <?= htmlspecialchars($g['apellido_nombre']) ?></span>
                                                        <br>
                                                        <small class="text-muted d-md-none"><?= htmlspecialchars($g['equipo']) ?></small>
                                                    </div>
                                                </td>
                                                <td class="text-muted d-none-mobile">
                                                    <i class="fas fa-shield-alt me-1"></i>
                                                    <?= htmlspecialchars($g['equipo']) ?>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success" style="font-size: 1rem; padding: 8px 16px;">
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
                        <i class="fas fa-info-circle me-2"></i>
                        Sin sanciones activas en este momento.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-ban me-2"></i>Sanciones Activas (<?= count($sanciones) ?>)
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th class="hide-xs">Fecha</th>
                                            <th>Jugador</th>
                                            <th class="d-none-mobile">Equipo</th>
                                            <th>Tipo</th>
                                            <th class="text-center">Fechas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sanciones as $s): ?>
                                            <tr>
                                                <td class="text-muted hide-xs">
                                                    <?= date('d/m/Y', strtotime($s['fecha_sancion'])) ?>
                                                </td>
                                                <td>
                                                    <div>
                                                        <span class="fw-bold"><?= htmlspecialchars($s['apellido_nombre']) ?></span>
                                                        <br>
                                                        <small class="text-muted">DNI: <?= htmlspecialchars($s['dni']) ?></small>
                                                        <br class="d-md-none">
                                                        <small class="text-muted d-md-none">
                                                            <i class="fas fa-shield-alt me-1"></i><?= htmlspecialchars($s['equipo']) ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td class="d-none-mobile">
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($s['equipo_logo'])): ?>
                                                            <img src="../uploads/<?= htmlspecialchars($s['equipo_logo']) ?>" 
                                                                 class="team-logo me-2" alt="Logo">
                                                        <?php else: ?>
                                                            <div class="team-initial me-2">
                                                                <?= substr($s['equipo'], 0, 1) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <span class="fw-semibold"><?= htmlspecialchars($s['equipo']) ?></span>
                                                            <br>
                                                            <small class="text-muted"><?= htmlspecialchars($s['categoria']) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php
                                                    $badges = [
                                                        'amarillas_acumuladas' => '<span class="badge bg-warning text-dark">🟨 4 Amarillas</span>',
                                                        'doble_amarilla' => '<span class="badge" style="background: linear-gradient(90deg, #ffc107 50%, #dc3545 50%);">🟨🟥 Doble Amarilla</span>',
                                                        'roja_directa' => '<span class="badge bg-danger">🟥 Roja Directa</span>',
                                                        'administrativa' => '<span class="badge bg-info">📋 Administrativa</span>'
                                                    ];
                                                    echo $badges[$s['tipo']] ?? '<span class="badge bg-secondary">' . htmlspecialchars($s['tipo']) . '</span>';
                                                    ?>
                                                </td>
                                                <td class="text-center">
                                                    <div>
                                                        <strong class="text-danger" style="font-size: 1.1rem;">
                                                            <?= min($s['partidos_cumplidos'], $s['partidos_suspension']) ?>/<?= $s['partidos_suspension'] ?>
                                                        </strong>
                                                        <br>
                                                        <?php if ($s['fechas_restantes'] > 0): ?>
                                                            <small class="badge bg-danger">Faltan <?= $s['fechas_restantes'] ?></small>
                                                        <?php else: ?>
                                                            <small class="badge bg-success">✓ Cumplida</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Información adicional -->
                            <div class="alert alert-info mt-4 mb-0">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-info-circle me-2"></i>Información del Sistema de Sanciones
                                </h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <strong>Tipos de Sanciones:</strong>
                                        <ul class="mt-2 mb-0">
                                            <li><strong>4 Amarillas:</strong> 1 fecha de suspensión</li>
                                            <li><strong>Doble Amarilla:</strong> 1-2 fechas</li>
                                            <li><strong>Roja Directa:</strong> 2+ fechas</li>
                                            <li><strong>Administrativa:</strong> Según autoridades</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6 mb-0">
                                        <strong>Sistema Automático:</strong>
                                        <ul class="mt-2 mb-0">
                                            <li>Las sanciones se cumplen automáticamente</li>
                                            <li>Se actualizan al finalizar cada partido</li>
                                            <li>Los sancionados no pueden jugar</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Equipos -->
            <div class="tab-pane fade" id="tab-equipos" role="tabpanel">
                <?php if (empty($equipos)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay equipos registrados.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-users me-2"></i>Equipos Participantes (<?= count($equipos) ?>)
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($equipos as $e): ?>
                                    <div class="col-6 col-md-4 col-lg-3">
                                        <div class="stats-card text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <?php if (!empty($e['logo'])): ?>
                                                    <img src="../uploads/<?= htmlspecialchars($e['logo']) ?>" 
                                                         alt="<?= htmlspecialchars($e['nombre']) ?>" 
                                                         style="width: 60px; height: 60px; object-fit: contain; margin-bottom: 12px;">
                                                <?php else: ?>
                                                    <div style="width: 60px; height: 60px; border-radius: 12px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 700; margin-bottom: 12px;">
                                                        <?= substr($e['nombre'], 0, 1) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="fw-bold text-center" style="font-size: 0.9rem;">
                                                    <?= htmlspecialchars($e['nombre']) ?>
                                                </span>
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
                        <i class="fas fa-info-circle me-2"></i>
                        Sin datos de tarjetas registradas.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-shield-alt me-2"></i>Ranking Fair Play
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Equipo</th>
                                            <th class="text-center hide-xs">
                                                <i class="fas fa-square text-danger me-1"></i>Rojas
                                            </th>
                                            <th class="text-center hide-xs">
                                                <i class="fas fa-square text-warning me-1"></i>Amarillas
                                            </th>
                                            <th class="text-center">Puntuación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $pos = 1;
                                        foreach ($fairplay as $f): 
                                            $puntuacion = ((int)$f['rojas'] * 5) + ((int)$f['amarillas']);
                                            $color = $puntuacion <= 5 ? 'success' : ($puntuacion <= 15 ? 'warning' : 'danger');
                                        ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <span class="badge-position bg-<?= $color ?> me-2">
                                                            <?= $pos++ ?>
                                                        </span>
                                                        <span class="fw-bold"><?= htmlspecialchars($f['equipo']) ?></span>
                                                        <br class="d-md-none">
                                                        <small class="d-md-none text-muted">
                                                            🟥 <?= (int)$f['rojas'] ?> | 🟨 <?= (int)$f['amarillas'] ?>
                                                        </small>
                                                    </div>
                                                </td>
                                                <td class="text-center hide-xs">
                                                    <span class="badge bg-danger" style="font-size: 0.95rem;">
                                                        <?= (int)$f['rojas'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-center hide-xs">
                                                    <span class="badge bg-warning text-dark" style="font-size: 0.95rem;">
                                                        <?= (int)$f['amarillas'] ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?= $color ?>" style="font-size: 1rem; padding: 8px 16px;">
                                                        <i class="fas fa-<?= $color == 'success' ? 'check' : ($color == 'warning' ? 'exclamation' : 'times') ?> me-1"></i>
                                                        <strong><?= $puntuacion ?></strong>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="alert alert-info mt-4 mb-0">
                                <h6 class="fw-bold">
                                    <i class="fas fa-calculator me-2"></i>Sistema de Puntuación
                                </h6>
                                <p class="mb-0">
                                    <strong>Tarjeta Roja:</strong> 5 puntos | 
                                    <strong>Tarjeta Amarilla:</strong> 1 punto
                                    <br>
                                    <small class="text-muted">Menor puntuación = Mejor Fair Play</small>
                                </p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
            <?php endif; // Fin del check de campeonato ?>
    </div>

    <!-- Footer -->
    <div class="container">
        <footer class="footer">
            <h5>
                <i class="fas fa-futbol me-2"></i>Liga Altos del Paracao
            </h5>
            <p class="mb-3 opacity-75">
                <?= htmlspecialchars($campeonato['nombre']) ?> - Información completa y actualizada
            </p>
            <small class="opacity-50">
                <i class="fas fa-calendar-alt me-2"></i>
                Actualizado: <?= date('d/m/Y H:i') ?>
            </small>
        </footer>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>