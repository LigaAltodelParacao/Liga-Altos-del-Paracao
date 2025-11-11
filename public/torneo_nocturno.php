<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config.php';

// Verificar primero que el archivo exista
$ruta_desempate = __DIR__ . '/../admin/include/desempate_functions.php';
if (!file_exists($ruta_desempate)) {
    // Intentar ruta alternativa si la estructura es diferente
    $ruta_desempate = dirname(dirname(__FILE__)) . '/admin/include/desempate_functions.php';
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
    // Incluir partidos de zona Y normales - USAR COLUMNAS EXPLÍCITAS
    $sql = "
        (
            SELECT 
                pz.id,
                pz.equipo_local_id,
                pz.equipo_visitante_id,
                pz.goles_local,
                pz.goles_visitante,
                pz.fecha_partido,
                pz.hora_partido,
                pz.estado,
                pz.cancha_id,
                el.nombre as equipo_local,
                ev.nombre as equipo_visitante,
                c.nombre as cancha,
                NULL as numero_fecha,
                'zona' as tipo
            FROM partidos_zona pz
            JOIN equipos el ON pz.equipo_local_id = el.id
            JOIN equipos ev ON pz.equipo_visitante_id = ev.id
            LEFT JOIN canchas c ON pz.cancha_id = c.id
            JOIN zonas z ON pz.zona_id = z.id
            JOIN campeonatos_formato cf ON z.formato_id = cf.id
            WHERE cf.campeonato_id = ? AND pz.estado = 'finalizado'
        )
        UNION ALL
        (
            SELECT 
                p.id,
                p.equipo_local_id,
                p.equipo_visitante_id,
                p.goles_local,
                p.goles_visitante,
                p.fecha_partido,
                p.hora_partido,
                p.estado,
                p.cancha_id,
                el.nombre as equipo_local,
                ev.nombre as equipo_visitante,
                c.nombre as cancha,
                f.numero_fecha,
                'normal' as tipo
            FROM partidos p
            JOIN fechas f ON p.fecha_id = f.id
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            LEFT JOIN canchas c ON p.cancha_id = c.id
            WHERE f.categoria_id IN (" . implode(',', array_fill(0, count($categoria_ids), '?')) . ") AND p.estado = 'finalizado'
        )
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
        WHERE f.categoria_id IN (" . implode(',', array_fill(0, count($categoria_ids), '?')) . ") 
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
                    SELECT id FROM fechas WHERE categoria_id IN (" . implode(',', array_fill(0, count($categoria_ids), '?')) . ")
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
                    SELECT id FROM fechas WHERE categoria_id IN (" . implode(',', array_fill(0, count($categoria_ids), '?')) . ")
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
        WHERE e.categoria_id IN (" . implode(',', array_fill(0, count($categoria_ids), '?')) . ") 
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
        WHERE categoria_id IN (" . implode(',', array_fill(0, count($categoria_ids), '?')) . ") AND activo = 1 
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
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        :root {
            --primary-color: #198754;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --light-bg: #f8f9fa;
            --dark-bg: #212529;
            --card-shadow: 0 2px 10px rgba(0,0,0,0.1);
            --hover-shadow: 0 5px 20px rgba(0,0,0,0.15);
            --border-radius: 12px;
            --transition: all 0.3s ease;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 0.95rem;
        }

        /* Hero Section Mejorado */
        .hero-section {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 2rem 0;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="2"/><path d="M50 10 L50 90 M10 50 L90 50" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></svg>');
            background-size: 100px 100px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .torneo-badge {
            background: linear-gradient(45deg, #ffc107, #ff9800);
            color: #212529;
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Navegación Mejorada */
        .nav-tabs-custom {
            background: white;
            border-radius: var(--border-radius);
            padding: 0.5rem;
            box-shadow: var(--card-shadow);
            border: 1px solid #e9ecef;
            overflow-x: auto;
            flex-wrap: nowrap;
            -webkit-overflow-scrolling: touch;
            margin-bottom: 1.5rem;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            border-radius: 8px;
            color: var(--secondary-color);
            font-weight: 500;
            padding: 0.75rem 1rem;
            margin: 0 0.25rem;
            transition: var(--transition);
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-tabs-custom .nav-link:hover {
            background: rgba(25, 135, 84, 0.1);
            color: var(--primary-color);
        }

        .nav-tabs-custom .nav-link.active {
            background: linear-gradient(45deg, var(--primary-color), #146c43);
            color: white;
            box-shadow: 0 2px 8px rgba(25, 135, 84, 0.3);
        }

        /* Cards Mejorados */
        .card-custom {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            transition: var(--transition);
            background: white;
            overflow: hidden;
        }

        .card-custom:hover {
            box-shadow: var(--hover-shadow);
            transform: translateY(-2px);
        }

        .card-header-custom {
            background: linear-gradient(45deg, var(--primary-color), #146c43);
            color: white;
            border: none;
            padding: 1rem 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Tablas Mejoradas */
        .table-custom {
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 0;
            font-size: 0.875rem;
        }

        .table-custom thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
            padding: 0.75rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-custom tbody td {
            padding: 0.75rem;
            border-color: #e9ecef;
            vertical-align: middle;
        }

        .table-custom tbody tr {
            transition: var(--transition);
        }

        .table-custom tbody tr:hover {
            background: rgba(25, 135, 84, 0.05);
        }

        /* Elementos de Equipo */
        .team-logo {
            width: 28px;
            height: 28px;
            object-fit: contain;
            border-radius: 6px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            transition: var(--transition);
        }

        .team-logo:hover {
            transform: scale(1.1);
        }

        .team-initial {
            width: 28px;
            height: 28px;
            border-radius: 6px;
            background: linear-gradient(45deg, var(--primary-color), #146c43);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
        }

        .position-badge {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.75rem;
            color: white;
        }

        .position-1 { background: linear-gradient(45deg, #FFD700, #FFA000); color: #333; }
        .position-2 { background: linear-gradient(45deg, #C0C0C0, #9E9E9E); color: #333; }
        .position-3 { background: linear-gradient(45deg, #CD7F32, #A0522D); }
        .position-other { background: linear-gradient(45deg, #6c757d, #495057); }

        /* Stats Cards */
        .stats-card-custom {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid #e9ecef;
            transition: var(--transition);
            text-align: center;
        }

        .stats-card-custom:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        /* Badges Personalizados */
        .badge-custom {
            padding: 0.25rem 0.5rem;
            font-weight: 600;
            border-radius: 6px;
            font-size: 0.75rem;
        }

        .badge-success-custom {
            background: linear-gradient(45deg, var(--success-color), #34ce57);
            color: white;
        }

        .badge-danger-custom {
            background: linear-gradient(45deg, var(--danger-color), #c82333);
            color: white;
        }

        .badge-warning-custom {
            background: linear-gradient(45deg, var(--warning-color), #e0a800);
            color: #212529;
        }

        /* Partidos en Vivo */
        .live-indicator {
            display: inline-block;
            width: 8px;
            height: 8px;
            background: #ff0000;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
            margin-right: 0.5rem;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Responsive Optimizado */
        @media (max-width: 768px) {
            .hero-section {
                padding: 1.5rem 0;
            }
            
            .hero-section h1 {
                font-size: 1.5rem;
            }
            
            .nav-tabs-custom {
                padding: 0.25rem;
            }
            
            .nav-tabs-custom .nav-link {
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
            }
            
            .nav-tabs-custom .nav-link i {
                font-size: 0.9rem;
            }
            
            .table-custom {
                font-size: 0.8rem;
            }
            
            .table-custom thead th,
            .table-custom tbody td {
                padding: 0.5rem 0.25rem;
            }
            
            .hide-mobile {
                display: none !important;
            }
            
            .team-logo, .team-initial {
                width: 24px;
                height: 24px;
            }
            
            .team-name {
                max-width: 100px;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
        }

        @media (max-width: 576px) {
            .hide-xs {
                display: none !important;
            }
            
            .nav-tabs-custom .nav-link {
                padding: 0.4rem 0.6rem;
                font-size: 0.75rem;
            }
            
            .card-header-custom {
                padding: 0.75rem 1rem;
                font-size: 0.9rem;
            }
            
            .stats-card-custom {
                padding: 0.75rem;
            }
        }

        /* Animaciones */
        .fade-in {
            animation: fadeIn 0.6s ease-in;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Footer Mejorado */
        .footer-custom {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            border-radius: var(--border-radius);
            margin: 2rem 0;
            padding: 2rem;
            text-align: center;
        }

        .footer-custom h5 {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .footer-custom .text-muted {
            color: rgba(255,255,255,0.7) !important;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../include/header.php'; ?>

    <!-- Hero Section Mejorado -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center hero-content">
                <div class="col-lg-8">
                    <h1 class="fw-bold text-white mb-2">
                        <i class="fas fa-moon text-warning me-3"></i>
                        <?php echo htmlspecialchars($campeonato['nombre']); ?>
                    </h1>
                    <p class="text-white-50 mb-0">
                        <i class="fas fa-trophy me-2"></i>
                        Información completa del torneo • Actualizado: <?php echo date('d/m/Y H:i'); ?>
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <span class="torneo-badge">
                        <i class="fas fa-star"></i>
                        Edición <?php echo date('Y'); ?>
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Contenido Principal -->
    <div class="container my-4">
        
        <!-- Navegación Mejorada -->
        <ul class="nav nav-tabs-custom" id="nocturnoTabs" role="tablist">
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
        <div class="tab-content fade-in">
            
            <!-- POSICIONES -->
            <div class="tab-pane fade show active" id="tab-posiciones">
                <?php if (empty($zonas_data)): ?>
                    <div class="card-custom">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-info-circle text-info mb-3" style="font-size: 3rem;"></i>
                            <h5>No hay formatos de zonas configurados</h5>
                            <p class="text-muted mb-0">Las tablas de posiciones aparecerán cuando se configuren las zonas del torneo.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($zonas_data as $pack): ?>
                        <div class="row g-3">
                            <?php foreach ($pack['zonas'] as $zona): ?>
                                <div class="col-lg-6">
                                    <div class="card-custom">
                                        <div class="card-header-custom">
                                            <i class="fas fa-flag me-2"></i>
                                            <?php echo htmlspecialchars($zona['nombre']); ?>
                                        </div>
                                        <div class="card-body">
                                            <?php if (empty($zona['tabla'])): ?>
                                                <div class="text-center py-4">
                                                    <i class="fas fa-users text-muted mb-2" style="font-size: 2rem;"></i>
                                                    <p class="text-muted mb-0">Sin datos en esta zona</p>
                                                </div>
                                            <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-custom table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 40px;"></th>
                                                                <th>Equipo</th>
                                                                <th class="text-center">Pts</th>
                                                                <th class="text-center hide-mobile">PJ</th>
                                                                <th class="text-center hide-xs">GF</th>
                                                                <th class="text-center hide-xs">GC</th>
                                                                <th class="text-center hide-mobile">Dif</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($zona['tabla'] as $pos => $r): ?>
                                                                <tr>
                                                                    <td>
                                                                        <span class="position-badge <?php echo $pos < 3 ? 'position-' . ($pos + 1) : 'position-other'; ?>">
                                                                            <?php echo $pos + 1; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <?php if (!empty($r['logo'])): ?>
                                                                                <img src="../uploads/<?php echo htmlspecialchars($r['logo']); ?>" 
                                                                                     class="team-logo me-2" alt="Logo">
                                                                            <?php else: ?>
                                                                                <div class="team-initial me-2">
                                                                                    <?php echo substr($r['nombre'], 0, 1); ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <span class="team-name fw-medium"><?php echo htmlspecialchars($r['nombre']); ?></span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-center fw-bold"><?php echo (int)$r['puntos']; ?></td>
                                                                    <td class="text-center hide-mobile"><?php echo (int)$r['partidos_jugados']; ?></td>
                                                                    <td class="text-center hide-xs"><?php echo (int)$r['goles_favor']; ?></td>
                                                                    <td class="text-center hide-xs"><?php echo (int)$r['goles_contra']; ?></td>
                                                                    <td class="text-center hide-mobile">
                                                                        <small class="<?php echo $r['diferencia_gol'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                                                                            <?php echo ($r['diferencia_gol'] >= 0 ? '+' : '') . (int)$r['diferencia_gol']; ?>
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
                    <div class="card-custom">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-calendar-times text-info mb-3" style="font-size: 3rem;"></i>
                            <h5>No hay partidos programados</h5>
                            <p class="text-muted mb-0">El fixture se actualizará cuando se programen nuevos partidos.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if (!empty($fixture_zonas)): ?>
                        <?php foreach ($fixture_zonas as $fz): ?>
                            <div class="card-custom mb-3">
                                <div class="card-header-custom">
                                    <i class="fas fa-flag me-2"></i>
                                    <?php echo htmlspecialchars($fz['zona']['nombre']); ?>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($fz['partidos'])): ?>
                                        <div class="text-center py-3">
                                            <i class="fas fa-futbol text-muted"></i>
                                            <p class="text-muted mb-0">Sin partidos programados</p>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-custom table-sm">
                                                <thead>
                                                    <tr>
                                                        <th class="hide-xs">Fecha</th>
                                                        <th>Local</th>
                                                        <th></th>
                                                        <th>Visitante</th>
                                                        <th class="hide-xs">Cancha</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($fz['partidos'] as $p): ?>
                                                        <tr>
                                                            <td class="hide-xs text-muted small">
                                                                <?php echo $p['fecha_partido'] ? date('d/m', strtotime($p['fecha_partido'])) : '-'; ?>
                                                                <?php echo $p['hora_partido'] ? ' ' . date('H:i', strtotime($p['hora_partido'])) : ''; ?>
                                                            </td>
                                                            <td class="team-name">
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($p['logo_local'])): ?>
                                                                        <img src="../uploads/<?php echo htmlspecialchars($p['logo_local']); ?>" 
                                                                             class="team-logo me-2" alt="Logo">
                                                                    <?php endif; ?>
                                                                    <?php echo htmlspecialchars($p['equipo_local']); ?>
                                                                </div>
                                                            </td>
                                                            <td class="text-center text-muted small">vs</td>
                                                            <td class="team-name">
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($p['logo_visitante'])): ?>
                                                                        <img src="../uploads/<?php echo htmlspecialchars($p['logo_visitante']); ?>" 
                                                                             class="team-logo me-2" alt="Logo">
                                                                    <?php endif; ?>
                                                                    <?php echo htmlspecialchars($p['equipo_visitante']); ?>
                                                                </div>
                                                            </td>
                                                            <td class="text-muted small hide-xs"><?php echo htmlspecialchars($p['cancha'] ?? '-'); ?></td>
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
                        <div class="card-custom">
                            <div class="card-header-custom">
                                <i class="fas fa-calendar me-2"></i>Partidos Programados
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-custom table-sm">
                                        <thead>
                                            <tr>
                                                <th class="hide-xs">Fecha</th>
                                                <th>Local</th>
                                                <th></th>
                                                <th>Visitante</th>
                                                <th class="hide-xs">Cancha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fixture as $p): ?>
                                                <tr>
                                                    <td class="hide-xs text-muted small">
                                                        <?php echo $p['fecha_partido'] ? date('d/m', strtotime($p['fecha_partido'])) : '-'; ?>
                                                        <?php echo $p['hora_partido'] ? ' ' . date('H:i', strtotime($p['hora_partido'])) : ''; ?>
                                                    </td>
                                                    <td class="team-name"><?php echo htmlspecialchars($p['equipo_local']); ?></td>
                                                    <td class="text-center text-muted small">vs</td>
                                                    <td class="team-name"><?php echo htmlspecialchars($p['equipo_visitante']); ?></td>
                                                    <td class="text-muted small hide-xs"><?php echo htmlspecialchars($p['cancha'] ?? '-'); ?></td>
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
                    <div class="card-custom">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-list text-info mb-3" style="font-size: 3rem;"></i>
                            <h5>Sin resultados recientes</h5>
                            <p class="text-muted mb-0">Los resultados aparecerán cuando se jueguen los partidos.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card-custom">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-custom table-sm">
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
                                                <td class="text-muted small hide-xs">
                                                    <?php echo $p['fecha_partido'] ? date('d/m', strtotime($p['fecha_partido'])) : '-'; ?>
                                                </td>
                                                <td class="team-name">
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($p['logo_local'])): ?>
                                                            <img src="../uploads/<?php echo htmlspecialchars($p['logo_local']); ?>" 
                                                                 class="team-logo me-2" alt="Logo">
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($p['equipo_local']); ?>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge-custom badge-success-custom">
                                                        <?php echo (int)$p['goles_local']; ?> - <?php echo (int)$p['goles_visitante']; ?>
                                                    </span>
                                                </td>
                                                <td class="team-name">
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($p['logo_visitante'])): ?>
                                                            <img src="../uploads/<?php echo htmlspecialchars($p['logo_visitante']); ?>" 
                                                                 class="team-logo me-2" alt="Logo">
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($p['equipo_visitante']); ?>
                                                    </div>
                                                </td>
                                                <td class="text-muted small hide-xs"><?php echo htmlspecialchars($p['cancha'] ?? '-'); ?></td>
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
                    <div class="card-custom">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-futbol text-info mb-3" style="font-size: 3rem;"></i>
                            <h5>Sin goles registrados</h5>
                            <p class="text-muted mb-0">Los goleadores aparecerán cuando se registren los primeros goles.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card-custom">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-custom table-sm">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Jugador</th>
                                            <th class="hide-mobile">Equipo</th>
                                            <th class="text-center">Goles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($goleadores as $pos => $g): ?>
                                            <tr>
                                                <td>
                                                    <span class="position-badge <?php echo $pos < 3 ? 'position-' . ($pos + 1) : 'position-other'; ?>">
                                                        <?php echo $pos + 1; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($g['apellido_nombre']); ?></strong>
                                                    <br class="d-md-none">
                                                    <small class="text-muted d-md-none"><?php echo htmlspecialchars($g['equipo']); ?></small>
                                                </td>
                                                <td class="text-muted small hide-mobile"><?php echo htmlspecialchars($g['equipo']); ?></td>
                                                <td class="text-center">
                                                    <span class="badge-custom badge-success-custom">
                                                        <i class="fas fa-futbol me-1"></i><?php echo (int)$g['goles']; ?>
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
                    <div class="card-custom">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-check-circle text-success mb-3" style="font-size: 3rem;"></i>
                            <h5>No hay sanciones activas</h5>
                            <p class="text-muted mb-0">Todos los jugadores están habilitados para jugar.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card-custom">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-custom table-sm">
                                    <thead>
                                        <tr>
                                            <th class="hide-xs">Fecha</th>
                                            <th>Jugador</th>
                                            <th class="hide-mobile">Equipo</th>
                                            <th>Tipo</th>
                                            <th class="text-center">Pendientes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($sanciones as $s): ?>
                                            <tr>
                                                <td class="text-muted small hide-xs">
                                                    <?php echo date('d/m', strtotime($s['fecha_sancion'])); ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($s['apellido_nombre']); ?></strong>
                                                    <br class="d-md-none">
                                                    <small class="text-muted d-md-none"><?php echo htmlspecialchars($s['equipo']); ?></small>
                                                </td>
                                                <td class="text-muted small hide-mobile"><?php echo htmlspecialchars($s['equipo']); ?></td>
                                                <td>
                                                    <?php
                                                    $badge_class = 'badge-warning-custom';
                                                    if (strpos($s['tipo'], 'roja') !== false) {
                                                        $badge_class = 'badge-danger-custom';
                                                    } elseif (strpos($s['tipo'], 'administrativa') !== false) {
                                                        $badge_class = 'badge-custom';
                                                        $badge_class .= ' bg-dark text-white';
                                                    }
                                                    ?>
                                                    <span class="badge-custom <?php echo $badge_class; ?>">
                                                        <?php echo htmlspecialchars($s['tipo']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge-custom badge-dark">
                                                        <?php echo (int)$s['pendientes']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Las sanciones se cumplen automáticamente cuando el equipo juega partidos oficiales
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- EQUIPOS -->
            <div class="tab-pane fade" id="tab-equipos">
                <?php if (empty($equipos)): ?>
                    <div class="card-custom">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-users text-info mb-3" style="font-size: 3rem;"></i>
                            <h5>No hay equipos registrados</h5>
                            <p class="text-muted mb-0">Los equipos aparecerán cuando se registren en el torneo.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($equipos as $e): ?>
                            <div class="col-6 col-md-4 col-lg-3">
                                <div class="stats-card-custom">
                                    <?php if (!empty($e['logo'])): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($e['logo']); ?>" 
                                             class="team-logo mb-2" 
                                             style="width: 40px; height: 40px;" alt="Logo">
                                    <?php else: ?>
                                        <div class="team-initial mb-2 mx-auto" style="width: 40px; height: 40px; font-size: 1rem;">
                                            <?php echo substr($e['nombre'], 0, 1); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="small fw-medium"><?php echo htmlspecialchars($e['nombre']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- FAIRPLAY -->
            <div class="tab-pane fade" id="tab-fairplay">
                <?php if (empty($fairplay)): ?>
                    <div class="card-custom">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-shield-alt text-info mb-3" style="font-size: 3rem;"></i>
                            <h5>Sin datos de disciplina</h5>
                            <p class="text-muted mb-0">La tabla fairplay aparecerá cuando se registren tarjetas.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="card-custom">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-custom table-sm">
                                    <thead>
                                        <tr>
                                            <th style="width: 50px;">#</th>
                                            <th>Equipo</th>
                                            <th class="text-center hide-xs">
                                                <i class="fas fa-square text-warning"></i> Amarillas
                                            </th>
                                            <th class="text-center hide-xs">
                                                <i class="fas fa-square text-danger"></i> Rojas
                                            </th>
                                            <th class="text-center">Puntos</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($fairplay as $pos => $f): ?>
                                            <tr>
                                                <td>
                                                    <span class="position-badge <?php echo $pos < 3 ? 'position-' . ($pos + 1) : 'position-other'; ?>">
                                                        <?php echo $pos + 1; ?>
                                                    </span>
                                                </td>
                                                <td class="team-name fw-medium"><?php echo htmlspecialchars($f['equipo']); ?></td>
                                                <td class="text-center hide-xs">
                                                    <span class="badge-custom badge-warning-custom">
                                                        <?php echo (int)$f['amarillas']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center hide-xs">
                                                    <span class="badge-custom badge-danger-custom">
                                                        <?php echo (int)$f['rojas']; ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?php
                                                    $pts = (int)$f['puntos_disciplina'];
                                                    $color = $pts < 5 ? 'success' : ($pts < 10 ? 'warning' : 'danger');
                                                    ?>
                                                    <span class="badge-custom badge-<?php echo $color; ?>-custom">
                                                        <?php echo $pts; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <small class="text-muted d-block mt-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Puntos disciplina: Amarilla = 1pt, Roja = 3pts. Menos es mejor.
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer Mejorado -->
    <div class="container">
        <footer class="footer-custom">
            <h5>
                <i class="fas fa-futbol me-2"></i>
                Altos del Paracao
            </h5>
            <p class="mb-2 text-muted">
                <?php echo htmlspecialchars($campeonato['nombre']); ?>
            </p>
            <small class="text-muted">
                © <?php echo date('Y'); ?> • Actualizado: <?php echo date('d/m/Y H:i'); ?>
            </small>
        </footer>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
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

            // Animación de entrada para las tabs
            var tabPanes = document.querySelectorAll('.tab-pane');
            tabPanes.forEach(function(pane) {
                pane.classList.add('fade-in');
            });
        });

        // Agregar animación a las cards al hacer hover
        document.querySelectorAll('.card-custom').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>