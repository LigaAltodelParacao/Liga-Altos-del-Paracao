<?php
// Inicializar variables de entorno
$current_path = '';
$is_in_public = false;
$is_in_admin = false;

// Requerir configuración
require_once 'config.php';

// Obtener base de datos
$db = Database::getInstance()->getConnection();

// Configurar detección de contexto ANTES del header
$current_path = $_SERVER['REQUEST_URI'] ?? '';
$is_in_public = (strpos($current_path, '/public/') !== false);
$is_in_admin = (strpos($current_path, '/admin/') !== false);

// Partidos en vivo con logos
$stmt = $db->query("
    SELECT p.*, 
           el.nombre as equipo_local, el.logo as logo_local,
           ev.nombre as equipo_visitante, ev.logo as logo_visitante,
           c.nombre as cancha,
           f.numero_fecha,
           cat.nombre as categoria
    FROM partidos p
    JOIN equipos el ON p.equipo_local_id = el.id
    JOIN equipos ev ON p.equipo_visitante_id = ev.id
    JOIN fechas f ON p.fecha_id = f.id
    JOIN categorias cat ON f.categoria_id = cat.id
    LEFT JOIN canchas c ON p.cancha_id = c.id
    WHERE p.estado = 'en_curso'
    ORDER BY p.fecha_partido DESC, p.hora_partido DESC
");
$partidos_vivo = $stmt->fetchAll();

// Endpoint AJAX para eventos
if (!empty($_GET['ajax']) && $_GET['ajax'] === 'eventos' && !empty($_GET['partido_id'])) {
    header('Content-Type: application/json');
    $partido_id = (int)$_GET['partido_id'];
    $stmt = $db->prepare("
        SELECT e.*, j.apellido_nombre, j.equipo_id, eq.nombre as nombre_equipo
        FROM eventos_partido e
        JOIN jugadores j ON e.jugador_id = j.id
        JOIN equipos eq ON j.equipo_id = eq.id
        WHERE e.partido_id = ?
        ORDER BY e.minuto ASC, e.created_at ASC
    ");
    $stmt->execute([$partido_id]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($eventos as &$e) {
        $min = (int)$e['minuto'];
        // Los eventos se guardan con offset:
        // Primer tiempo: minuto = minuto_periodo (1-30)
        // Segundo tiempo: minuto = minuto_periodo + 30 (31-60)
        if ($min >= 1 && $min <= 30) {
            $e['periodo'] = "1°T";
            $e['minuto_display'] = $min; // Minuto del período
        } elseif ($min >= 31 && $min <= 60) {
            $e['periodo'] = "2°T";
            $e['minuto_display'] = $min - 30; // Minuto del período (sin offset)
        } elseif ($min > 60) {
            $e['periodo'] = "ET";
            $e['minuto_display'] = $min;
        } else {
            $e['periodo'] = "";
            $e['minuto_display'] = $min;
        }
    }
    echo json_encode($eventos);
    exit;
}

// Endpoint para actualizar minutos en tiempo real
if (!empty($_GET['ajax']) && $_GET['ajax'] === 'update_minutes') {
    header('Content-Type: application/json');
    $stmt = $db->query("
        SELECT id, minuto_periodo, segundos_transcurridos, tiempo_actual, iniciado_at, 
               goles_local, goles_visitante
        FROM partidos
        WHERE estado = 'en_curso'
    ");
    $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calcular segundos del período para cada partido
    // IMPORTANTE: El cálculo de segundos_periodo debe ser consistente con partido_live.php
    // En partido_live.php, el reloj muestra: transcurrido = segundosCronometro - segundosInicioPeriodo
    // Los segundos del período = transcurrido % 60
    foreach ($partidos as &$p) {
        $tiempo_actual = $p['tiempo_actual'] ?? '';
        $segundos_totales = (int)($p['segundos_transcurridos'] ?? 0);
        $minuto_periodo = (int)($p['minuto_periodo'] ?? 0);
        
        if ($tiempo_actual === 'primer_tiempo') {
            // Primer tiempo: segundosInicioPeriodo = 0, entonces segundos_periodo = segundos_totales % 60
            $p['segundos_periodo'] = $segundos_totales % 60;
        } elseif ($tiempo_actual === 'segundo_tiempo') {
            // Segundo tiempo: segundosInicioPeriodo = tiempo del primer tiempo
            // transcurrido = segundos_totales - tiempoPrimerTiempo
            // segundos_periodo = transcurrido % 60
            // Como no tenemos tiempoPrimerTiempo exacto guardado, estimamos:
            // Si minuto_periodo = X, entonces han pasado X minutos del segundo tiempo
            // Estimar: tiempoPrimerTiempo ≈ segundos_totales - (minuto_periodo * 60 + segundos_estimados)
            // Simplificación: usar una estimación basada en minuto_periodo
            // Si minuto_periodo = 0, entonces segundos_totales es el tiempo del primer tiempo
            // Si minuto_periodo > 0, estimar que tiempoPrimerTiempo ≈ 1800 (30 minutos)
            // Entonces: transcurrido ≈ segundos_totales - 1800
            // segundos_periodo = transcurrido % 60
            // Pero esto no es exacto si el primer tiempo duró más o menos de 30 minutos
            // Solución más simple: usar segundos_totales % 60 como aproximación
            // Esto funcionará bien si el primer tiempo duró aproximadamente un múltiplo de 60 segundos
            $p['segundos_periodo'] = $segundos_totales % 60;
        } else {
            $p['segundos_periodo'] = 0;
        }
    }
    unset($p);
    
    echo json_encode($partidos);
    exit;
}

// Endpoint para eventos en tiempo real (ticker)
if (!empty($_GET['ajax']) && $_GET['ajax'] === 'eventos_ticker' && !empty($_GET['partido_id'])) {
    header('Content-Type: application/json');
    $partido_id = (int)$_GET['partido_id'];
    $stmt = $db->prepare("
        SELECT e.*, j.apellido_nombre, j.equipo_id, eq.nombre as nombre_equipo
        FROM eventos_partido e
        JOIN jugadores j ON e.jugador_id = j.id
        JOIN equipos eq ON j.equipo_id = eq.id
        WHERE e.partido_id = ?
        ORDER BY e.minuto DESC, e.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$partido_id]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($eventos as &$e) {
        $min = (int)$e['minuto'];
        // Los eventos se guardan con offset:
        // Primer tiempo: minuto = minuto_periodo (0-30)
        // Segundo tiempo: minuto = minuto_periodo + 30 (31-60)
        if ($min >= 1 && $min <= 30) {
            $e['periodo'] = "1°T";
            $e['minuto_display'] = $min; // Minuto del período
        } elseif ($min >= 31 && $min <= 60) {
            $e['periodo'] = "2°T";
            $e['minuto_display'] = $min - 30; // Minuto del período (sin offset)
        } elseif ($min > 60) {
            $e['periodo'] = "ET";
            $e['minuto_display'] = $min;
        } else {
            $e['periodo'] = "";
            $e['minuto_display'] = $min;
        }
    }
    
    echo json_encode($eventos);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liga Altos del Paracao</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #198754;
            --danger-color: #dc3545;
            --dark-bg: #1a1a1a;
            --card-bg: #ffffff;
            --text-muted: #6c757d;
            --score-bg: #28a745;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .live-match-card {
            background: var(--card-bg);
            border-radius: 12px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
            padding: 12px;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-bottom: 1rem;
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
            min-height: 190px;
        }

        .live-match-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, var(--danger-color), #ff6b6b);
        }

        .match-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 0.75rem;
        }

        .match-badge {
            background: var(--danger-color);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.6rem;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .live-dot {
            width: 4px;
            height: 4px;
            background: white;
            border-radius: 50%;
            animation: blink 1.2s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .match-info {
            color: var(--text-muted);
        }

        .teams-container {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin: 8px 0;
            flex: 1;
        }

        .team-row {
            display: grid;
            grid-template-columns: 1fr auto auto;
            align-items: center;
            gap: 8px;
            padding: 6px 0;
        }

        .team-name {
            font-weight: 600;
            font-size: 0.82rem;
            color: #2c3e50;
            line-height: 1.3;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: normal;
            text-align: left;
        }

        .team-logo {
            width: 30px;
            height: 30px;
            object-fit: contain;
            border-radius: 6px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            flex-shrink: 0;
        }

        .logo-placeholder {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 6px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: bold;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .team-score {
            font-size: 1.3rem;
            font-weight: bold;
            color: var(--score-bg);
            min-width: 25px;
            text-align: center;
        }

        .match-timer {
            background: rgba(0, 0, 0, 0.08);
            padding: 4px 10px;
            border-radius: 8px;
            display: inline-block;
            margin: 6px 0;
            text-align: center;
        }

        .timer-display {
            font-size: 0.85rem;
            color: #2c3e50;
            font-weight: 600;
            font-family: 'Courier New', monospace;
        }

        .match-footer {
            margin-top: auto;
            padding-top: 8px;
            border-top: 1px solid #eee;
            overflow: hidden;
            position: relative;
            height: 50px;
        }

        .footer-static {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 3px 0;
        }

        .match-location {
            display: flex;
            align-items: center;
            gap: 3px;
            color: var(--text-muted);
            font-size: 0.68rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 60%;
        }

        .match-category {
            background: #f8f9fa;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 0.68rem;
            font-weight: 600;
            color: var(--primary-color);
            white-space: nowrap;
        }

        .events-ticker {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.98);
            display: none;
            align-items: center;
            overflow: hidden;
            padding: 0 8px;
        }

        .events-ticker.active {
            display: flex;
        }

        .ticker-content {
            display: flex;
            align-items: center;
            white-space: nowrap;
            gap: 25px;
            animation: scroll-left 35s linear infinite;
        }

        @keyframes scroll-left {
            0% {
                transform: translateX(100%);
            }
            100% {
                transform: translateX(-100%);
            }
        }

        .ticker-event {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.72rem;
            padding: 3px 8px;
            border-radius: 5px;
            background: #f8f9fa;
        }

        .ticker-event.gol {
            background: #d4edda;
            color: #155724;
        }

        .ticker-event.amarilla {
            background: #fff3cd;
            color: #856404;
        }

        .ticker-event.roja {
            background: #f8d7da;
            color: #721c24;
        }

        .ticker-event i {
            font-size: 0.85rem;
        }

        .ticker-event .event-time {
            font-weight: bold;
            font-size: 0.7rem;
        }

        .ticker-legend {
            background: #eef2f7;
            color: #4a5568;
            font-weight: 600;
        }

        /* Destacado cuando hay gol */
        .goal-glow {
            position: relative;
            z-index: 1;
            animation: goalPulse 1s ease-in-out infinite;
            box-shadow: 0 0 0 rgba(40, 167, 69, 0.0);
        }

        @keyframes goalPulse {
            0% {
                box-shadow: 0 0 0 rgba(40, 167, 69, 0.0);
                transform: scale(1.0);
            }
            50% {
                box-shadow: 0 0 20px rgba(40, 167, 69, 0.65);
                transform: scale(1.02);
            }
            100% {
                box-shadow: 0 0 0 rgba(40, 167, 69, 0.0);
                transform: scale(1.0);
            }
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #145a32 100%);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .sponsors-section .sponsor-card {
            position: relative;
            background: linear-gradient(180deg, #ffffff 0%, #f8f9fb 100%);
            padding: 18px 18px 16px 18px;
            border-radius: 14px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            border: 1px solid rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .sponsors-section .sponsor-card:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: 0 12px 28px rgba(0,0,0,0.12);
        }

        /* Barra de color superior */
        .sponsors-section .sponsor-card .color-bar {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(90deg, #e91e63, #ff9800, #ffc107, #4caf50, #03a9f4, #3f51b5);
            background-size: 300% 100%;
            animation: barFlow 8s linear infinite;
        }
        @keyframes barFlow {
            0% { background-position: 0% 50%; }
            100% { background-position: 100% 50%; }
        }

        /* Efecto brillo sutil */
        .sponsors-section .sponsor-card::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(ellipse at center, rgba(255,255,255,0.25), rgba(255,255,255,0) 50%);
            transform: translate(-20%, -20%);
            transition: transform 0.6s ease;
            pointer-events: none;
        }
        .sponsors-section .sponsor-card:hover::before {
            transform: translate(0, 0);
        }

        /* (Ribbon eliminado a pedido) */

        /* Logo efecto: de escala/grayscale a color */
        .sponsors-section .sponsor-logo {
            max-height: 88px;
            object-fit: contain;
            filter: grayscale(20%);
            opacity: .95;
            transition: filter .25s ease, opacity .25s ease, transform .25s ease;
            background: #ffffff;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.06);
        }
        .sponsors-section .sponsor-card:hover .sponsor-logo {
            filter: grayscale(0%);
            opacity: 1;
            transform: scale(1.03);
        }

        @media (max-width: 576px) {
            .sponsors-section .sponsor-logo { max-height: 72px; }
        }

        .no-matches {
            text-align: center;
            padding: 60px 20px;
        }

        .no-matches i {
            font-size: 3.5rem;
            color: #cbd5e0;
            margin-bottom: 16px;
        }

        @media (max-width: 768px) {
            .team-name {
                font-size: 0.78rem;
            }
            .team-logo, .logo-placeholder {
                width: 28px;
                height: 28px;
            }
            .team-score {
                font-size: 1.15rem;
            }
        }

        @media (max-width: 576px) {
            .live-match-card {
                min-height: 180px;
            }
        }
    </style>
</head>
<body>
    <!-- Header inteligente -->
    <?php include 'include/header.php'; ?>

    <!-- Hero Section -->
    <div class="hero-section py-3">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h1 class="display-6 fw-bold text-white">
                        <i class="fas fa-trophy text-warning"></i> Liga Altos del Paracao
                    </h1>
                    <p class="text-white-50 mb-0">
                        Seguí todos los campeonatos en vivo.
                    </p>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="uploads/logo/altos.png" alt="Liga Altos del Paracao" class="img-fluid" style="max-height: 150px;">
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container my-3">
        <?php if (!empty($partidos_vivo)): ?>
        <div class="col-12 mb-3">
            <div class="text-center mb-3">
                <h3 class="fw-bold">
                    <i class="fas fa-broadcast-tower text-danger"></i> PARTIDOS EN VIVO
                </h3>
            </div>
            
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-2" id="live-matches-container">
                <?php foreach ($partidos_vivo as $partido): ?>
                <div class="col" onclick="mostrarEventosModal(<?= $partido['id'] ?>)">
                    <div class="live-match-card" data-partido-id="<?= $partido['id'] ?>">
                        <!-- Header -->
                        <div class="match-header">
                            <span class="match-badge">
                                <span class="live-dot"></span> EN VIVO
                            </span>
                            <span class="match-info">
                                <i class="fas fa-calendar-alt"></i> Fecha <?= $partido['numero_fecha'] ?>
                            </span>
                        </div>

                        <!-- Equipos y goles -->
                        <div class="teams-container">
                            <div class="team-row">
                                <span class="team-name" title="<?= htmlspecialchars($partido['equipo_local']) ?>">
                                    <?= htmlspecialchars($partido['equipo_local']) ?>
                                </span>
                                <?php if ($partido['logo_local']): ?>
                                    <img src="uploads/<?= $partido['logo_local'] ?>" class="team-logo" alt="<?= $partido['equipo_local'] ?>">
                                <?php else: ?>
                                    <span class="logo-placeholder"><?= substr($partido['equipo_local'], 0, 1) ?></span>
                                <?php endif; ?>
                                <span class="team-score goles-local" data-partido-id="<?= $partido['id'] ?>">
                                    <?= $partido['goles_local'] ?>
                                </span>
                            </div>

                            <div class="team-row">
                                <span class="team-name" title="<?= htmlspecialchars($partido['equipo_visitante']) ?>">
                                    <?= htmlspecialchars($partido['equipo_visitante']) ?>
                                </span>
                                <?php if ($partido['logo_visitante']): ?>
                                    <img src="uploads/<?= $partido['logo_visitante'] ?>" class="team-logo" alt="<?= $partido['equipo_visitante'] ?>">
                                <?php else: ?>
                                    <span class="logo-placeholder"><?= substr($partido['equipo_visitante'], 0, 1) ?></span>
                                <?php endif; ?>
                                <span class="team-score goles-visitante" data-partido-id="<?= $partido['id'] ?>">
                                    <?= $partido['goles_visitante'] ?>
                                </span>
                            </div>
                        </div>

                        <!-- Minuto de juego (usando minuto_periodo) -->
                        <div class="match-timer">
                            <div class="timer-display" 
                                 data-partido-id="<?= $partido['id'] ?>" 
                                 data-minuto="<?= $partido['minuto_periodo'] ?>" 
                                 data-segundos="<?= $partido['segundos_transcurridos'] ?>"
                                 data-tiempo="<?= htmlspecialchars($partido['tiempo_actual']) ?>"
                                 data-iniciado="<?= $partido['iniciado_at'] ?>">
                                <?php 
                                    if ($partido['tiempo_actual'] === 'descanso') {
                                        echo 'Descanso';
                                    } elseif ($partido['tiempo_actual'] === 'finalizado') {
                                        echo 'Finalizado';
                                    } else {
                                        // Usar minuto_periodo del servidor (calculado correctamente en partido_live.php)
                                        $minutoPeriodo = (int)($partido['minuto_periodo'] ?? 0);
                                        $tiempoActual = $partido['tiempo_actual'] ?? '';
                                        $segundosTotales = (int)($partido['segundos_transcurridos'] ?? 0);
                                        $secs = $segundosTotales % 60;
                                        
                                        // Formato igual que partido_live.php: mostrar MM:SS
                                        if ($minutoPeriodo <= 30) {
                                            $texto = str_pad($minutoPeriodo, 2, '0', STR_PAD_LEFT) . ':' . str_pad($secs, 2, '0', STR_PAD_LEFT);
                                        } else {
                                            $minutosExtra = $minutoPeriodo - 30;
                                            $texto = "30'" . ($minutosExtra > 0 ? '+' . $minutosExtra : '') . "'";
                                        }
                                        
                                        // Agregar período
                                        $periodo = ($tiempoActual === 'primer_tiempo') ? ' 1°T' : ' 2°T';
                                        echo $texto . $periodo;
                                    }
                                ?>
                            </div>
                        </div>

                        <!-- Footer con ticker -->
                        <div class="match-footer">
                            <div class="footer-static">
                                <div class="match-location" title="<?= htmlspecialchars($partido['cancha'] ?? 'Por confirmar') ?>">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?= htmlspecialchars($partido['cancha'] ?? 'Por confirmar') ?>
                                </div>
                                <span class="match-category">
                                    <?= htmlspecialchars($partido['categoria']) ?>
                                </span>
                            </div>
                            <div class="events-ticker" data-partido-id="<?= $partido['id'] ?>">
                                <div class="ticker-content" id="ticker-<?= $partido['id'] ?>">
                                    <!-- Eventos se cargarán aquí -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="no-matches">
            <i class="fas fa-calendar-times"></i>
            <h4 class="text-muted">No hay partidos en vivo ahora</h4>
            <p class="text-muted">Los partidos activos aparecerán automáticamente</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sponsors Section -->
    <div class="sponsors-section mt-4">
        <div class="container">
            <h3 class="text-center mb-2"><i class="fas fa-handshake"></i> Nuestros Sponsors</h3>
            <p class="text-center text-muted mb-3">Gracias por hacer posible este campeonato</p>
            <div class="row g-3 justify-content-center">
                <?php
                $sponsors = [
                    'Ancora.png', 'BPLAY.png', 'Budweiser.png', 'Club55.png',
                    'CooperacionSeguros.png', 'FarmaciaAbril.png', 'LaMasia.png', 'NLG.png',
                    'OldSchool.png', 'Pedrin.png', 'Porgreso.png', 'Whiterabbit.png'
                ];
                foreach ($sponsors as $logo):
                ?>
                <div class="col-6 col-md-3 col-lg-2 text-center">
                    <div class="sponsor-card" title="<?= pathinfo($logo, PATHINFO_FILENAME) ?>">
                        <span class="color-bar"></span>
                        <img src="uploads/sponsor/<?= $logo ?>" alt="<?= pathinfo($logo, PATHINFO_FILENAME) ?>" class="img-fluid sponsor-logo">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-4 mt-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-futbol"></i> Altos del Paracao</h5>
                    <p class="text-muted mb-0">Gracias a nuestros sponsors por apoyar la liga.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">© <?= date('Y') ?> Todos los derechos reservados</p>
                    <small class="text-muted">Actualizado: <?= date('d/m/Y H:i') ?></small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
    const lastScores = {}; // { [partidoId]: { local: number, visitante: number } }

    function mostrarEventosModal(partidoId) {
        console.log('Mostrando eventos del partido:', partidoId);
    }

    // Función para abreviar nombre del equipo
    function abreviarEquipo(nombre) {
        if (!nombre) return '';
        
        // Palabras comunes a mantener (artículos y preposiciones)
        const palabrasComunes = ['La', 'El', 'Los', 'Las', 'De', 'Del', 'Un', 'Una'];
        const palabras = nombre.trim().split(/\s+/);
        
        // Si tiene 2 palabras
        if (palabras.length === 2) {
            const primera = palabras[0];
            const segunda = palabras[1];
            
            // Si la primera es un artículo común
            if (palabrasComunes.includes(primera)) {
                // Ejemplo: "La Pinguina" -> "La Ping."
                // Si la segunda palabra tiene 7 o más caracteres, tomar 4 letras
                if (segunda.length >= 7) {
                    return primera + ' ' + segunda.substring(0, 4) + '.';
                }
                // Si tiene entre 5-6 caracteres, tomar 5 letras
                else if (segunda.length >= 5) {
                    return primera + ' ' + segunda.substring(0, 5) + '.';
                }
                return nombre;
            } else {
                // Ejemplo: "River Plate" -> "River P."
                if (segunda.length > 4) {
                    return primera + ' ' + segunda.substring(0, 4) + '.';
                }
                return nombre;
            }
        }
        
        // Si tiene más de 2 palabras
        if (palabras.length > 2) {
            const primera = palabras[0];
            const segunda = palabras[1];
            
            // Si la primera es un artículo común, tomar artículo + segunda palabra abreviada
            if (palabrasComunes.includes(primera)) {
                // Ejemplo: "La Pinguina F.C." -> "La Ping."
                // Si la segunda palabra tiene 7 o más caracteres, tomar 4 letras
                if (segunda.length >= 7) {
                    return primera + ' ' + segunda.substring(0, 4) + '.';
                }
                // Si tiene entre 5-6 caracteres, tomar 5 letras
                else if (segunda.length >= 5) {
                    return primera + ' ' + segunda.substring(0, 5) + '.';
                }
                return primera + ' ' + segunda;
            } else {
                // Ejemplo: "Club Atlético River" -> "Club At."
                if (segunda && segunda.length > 3) {
                    return primera + ' ' + segunda.substring(0, 3) + '.';
                }
                return primera + (segunda ? ' ' + segunda : '');
            }
        }
        
        // Si tiene una sola palabra
        if (palabras.length === 1) {
            // Abreviar si es muy larga (más de 10 caracteres)
            if (nombre.length > 10) {
                return nombre.substring(0, 8) + '.';
            }
            return nombre;
        }
        
        return nombre;
    }

    function cargarEventosTicker(partidoId) {
        fetch(`?ajax=eventos_ticker&partido_id=${partidoId}`)
            .then(response => response.json())
            .then(eventos => {
                const tickerContent = document.getElementById(`ticker-${partidoId}`);
                const ticker = document.querySelector(`.events-ticker[data-partido-id="${partidoId}"]`);
                
                if (!tickerContent || !ticker) return;

                if (eventos.length === 0) {
                    ticker.classList.remove('active');
                    return;
                }
                
                let html = '';
                eventos.forEach(e => {
                    let icono = '', clase = '';
                    if (e.tipo_evento === 'gol') { 
                        icono = '<i class="fas fa-futbol"></i>'; 
                        clase = 'gol';
                    }
                    else if (e.tipo_evento === 'amarilla') {
                        icono = '<i class="fas fa-circle" style="color:#ffc107;"></i>';
                        clase = 'amarilla';
                    }
                    else if (e.tipo_evento === 'roja') {
                        icono = '<i class="fas fa-circle" style="color:#dc3545;"></i>';
                        clase = 'roja';
                    }
                    else {
                        icono = '<i class="fas fa-info-circle"></i>';
                        clase = '';
                    }

                    // Usar minuto_display si está disponible (ya calculado con offset), sino usar minuto
                    const minuto = e.minuto_display !== undefined ? e.minuto_display : (e.minuto > 0 ? e.minuto : '?');
                    const periodo = e.periodo || '';
                    
                    // Abreviar nombre del equipo y agregarlo entre paréntesis
                    const equipoAbrev = e.nombre_equipo ? abreviarEquipo(e.nombre_equipo) : '';
                    const equipoDisplay = equipoAbrev ? ` (${equipoAbrev})` : '';
                    
                    html += `
                        <div class="ticker-event ${clase}">
                            ${icono}
                            <span>${e.apellido_nombre} ${minuto}' ${periodo}${equipoDisplay}</span>
                        </div>
                    `;
                });

                // Agregar leyenda al final
                html += `
                    <div class="ticker-event ticker-legend">
                        <i class="fas fa-shield-alt"></i>
                        <span>Liga Altos del Paracao</span>
                    </div>
                `;

                // Duplicar contenido para transición suave (incluye la leyenda al final)
                tickerContent.innerHTML = html + html;
                ticker.classList.add('active');
            })
            .catch(error => console.error('Error en ticker:', error));
    }

    function actualizarPartidos() {
        fetch('?ajax=update_minutes')
            .then(response => response.json())
            .then(partidos => {
                partidos.forEach(partido => {
                    const id = partido.id;
                    const golesLocal = document.querySelector(`.goles-local[data-partido-id="${id}"]`);
                    const golesVisitante = document.querySelector(`.goles-visitante[data-partido-id="${id}"]`);
                    const timerDisplay = document.querySelector(`.timer-display[data-partido-id="${id}"]`);
                    const card = document.querySelector(`.live-match-card[data-partido-id="${id}"]`);

                    // Detectar goles nuevos y actualizar marcador
                    const prev = lastScores[id] || { local: parseInt(golesLocal?.textContent || '0', 10) || 0, visitante: parseInt(golesVisitante?.textContent || '0', 10) || 0 };
                    const nuevoLocal = parseInt(partido.goles_local, 10) || 0;
                    const nuevoVisit = parseInt(partido.goles_visitante, 10) || 0;

                    if (golesLocal) golesLocal.textContent = nuevoLocal;
                    if (golesVisitante) golesVisitante.textContent = nuevoVisit;

                    if (card) {
                        if (nuevoLocal > prev.local) {
                            triggerGoalGlow(card, 0);
                        }
                        if (nuevoVisit > prev.visitante) {
                            triggerGoalGlow(card, 1);
                        }
                    }
                    lastScores[id] = { local: nuevoLocal, visitante: nuevoVisit };

                    if (timerDisplay) {
                        let texto = '';
                        if (partido.tiempo_actual === 'descanso') {
                            texto = 'Descanso';
                        } else if (partido.tiempo_actual === 'finalizado') {
                            texto = 'Finalizado';
                        } else {
                            // Usar minuto_periodo del servidor (calculado correctamente en partido_live.php)
                            const minutoPeriodo = parseInt(partido.minuto_periodo) || 0;
                            const tiempoActual = partido.tiempo_actual || '';
                            const segundosPeriodo = parseInt(partido.segundos_periodo) || 0;
                            
                            // Formato igual que partido_live.php: mostrar MM:SS
                            if (minutoPeriodo <= 30) {
                                texto = String(minutoPeriodo).padStart(2, '0') + ':' + String(segundosPeriodo).padStart(2, '0');
                            } else {
                                const minutosExtra = minutoPeriodo - 30;
                                texto = `30'${minutosExtra > 0 ? '+' + minutosExtra : ''}'`;
                            }
                            
                            // Agregar período
                            const periodo = (tiempoActual === 'primer_tiempo') ? ' 1°T' : ' 2°T';
                            texto += periodo;
                        }
                        timerDisplay.textContent = texto;
                    }
                });
            })
            .catch(error => console.error('Error actualizando:', error));
    }

    function triggerGoalGlow(card, teamIndex /* 0 = local, 1 = visitante */) {
        try {
            const rows = card.querySelectorAll('.team-row');
            const row = rows[teamIndex];
            if (!row) return;
            row.classList.add('goal-glow');
            const logo = row.querySelector('.team-logo, .logo-placeholder');
            if (logo) logo.classList.add('goal-glow');
            setTimeout(() => {
                row.classList.remove('goal-glow');
                if (logo) logo.classList.remove('goal-glow');
            }, 7000);
        } catch (e) {
            console.error('Error aplicando glow de gol:', e);
        }
    }

    // Cargar tickers INMEDIATAMENTE y refrescarlos periódicamente mientras haya eventos
    document.addEventListener('DOMContentLoaded', () => {
        const partidos = document.querySelectorAll('.live-match-card');
        partidos.forEach(card => {
            const id = card.dataset.partidoId;
            cargarEventosTicker(id);
            // Refrescar ticker cada 8 segundos
            setInterval(() => cargarEventosTicker(id), 8000);

            // Inicializar valores previos de goles
            const gl = card.querySelector(`.goles-local[data-partido-id="${id}"]`);
            const gv = card.querySelector(`.goles-visitante[data-partido-id="${id}"]`);
            lastScores[id] = {
                local: parseInt(gl?.textContent || '0', 10) || 0,
                visitante: parseInt(gv?.textContent || '0', 10) || 0
            };
        });

        actualizarPartidos();
        // Actualización más frecuente para reflejar goles casi en tiempo real
        setInterval(actualizarPartidos, 3000);
    });
    </script>
</body>
</html>