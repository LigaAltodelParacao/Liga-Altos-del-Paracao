<?php
require_once 'config.php';

$db = Database::getInstance()->getConnection();

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
        if ($min >= 1 && $min <= 30) {
            $e['periodo'] = "1°T";
        } elseif ($min >= 31 && $min <= 60) {
            $e['periodo'] = "2°T";
        } elseif ($min > 60) {
            $e['periodo'] = "ET";
        } else {
            $e['periodo'] = "";
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
        if ($min >= 1 && $min <= 30) {
            $e['periodo'] = "1°T";
        } elseif ($min >= 31 && $min <= 60) {
            $e['periodo'] = "2°T";
        } elseif ($min > 60) {
            $e['periodo'] = "ET";
        } else {
            $e['periodo'] = "";
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

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color) 0%, #145a32 100%);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }

        .sponsors-section .sponsor-card {
            background: white;
            padding: 12px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            transition: transform 0.25s ease;
        }

        .sponsors-section .sponsor-card:hover {
            transform: translateY(-3px);
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
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-futbol"></i> Altos del Paracao
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="public/resultados.php">Resultados</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/tablas.php">Posiciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/goleadores.php">Goleadores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/fixture.php">Fixture</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/sanciones.php">Sanciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/historial_equipos.php">Equipos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="public/fairplay.php">Fairplay</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Panel Admin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Salir
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Ingresar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

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
                                        $minuto = $partido['minuto_periodo'] ?? 0;
                                        $periodo = ($partido['tiempo_actual'] === 'primer_tiempo') ? '1°T' : '2°T';
                                        echo $minuto . "' " . $periodo;
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
            <div class="row g-2 justify-content-center">
                <?php
                $sponsors = [
                    'Ancora.png', 'BPLAY.png', 'BPLAY01.png', 'Budweiser.png', 'Club55.png',
                    'CooperacionSeguros.png', 'FarmaciaAbril.png', 'LaMasia.png', 'NLG.png',
                    'OldSchool.png', 'Pedrin.png', 'Porgreso.png', 'Whiterabbit.png'
                ];
                foreach ($sponsors as $logo):
                ?>
                <div class="col-6 col-md-3 col-lg-2 text-center">
                    <div class="sponsor-card">
                        <img src="uploads/sponsor/<?= $logo ?>" alt="<?= pathinfo($logo, PATHINFO_FILENAME) ?>" class="img-fluid" style="max-height: 50px; object-fit: contain;">
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
    function mostrarEventosModal(partidoId) {
        console.log('Mostrando eventos del partido:', partidoId);
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

                    const minuto = e.minuto > 0 ? e.minuto : '?';
                    const periodo = e.periodo || '';
                    html += `
                        <div class="ticker-event ${clase}">
                            ${icono}
                            <span>${e.apellido_nombre} ${minuto}' ${periodo}</span>
                        </div>
                    `;
                });

                // Duplicar contenido para transición suave
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

                    if (golesLocal) golesLocal.textContent = partido.goles_local;
                    if (golesVisitante) golesVisitante.textContent = partido.goles_visitante;

                    if (timerDisplay) {
                        let texto = '';
                        if (partido.tiempo_actual === 'descanso') {
                            texto = 'Descanso';
                        } else if (partido.tiempo_actual === 'finalizado') {
                            texto = 'Finalizado';
                        } else {
                            const minuto = partido.minuto_periodo || 0;
                            const periodo = (partido.tiempo_actual === 'primer_tiempo') ? '1°T' : '2°T';
                            texto = minuto + "' " + periodo;
                        }
                        timerDisplay.textContent = texto;
                    }
                });
            })
            .catch(error => console.error('Error actualizando:', error));
    }

    // Cargar tickers INMEDIATAMENTE al cargar la página
    document.addEventListener('DOMContentLoaded', () => {
        const partidos = document.querySelectorAll('.live-match-card');
        partidos.forEach(card => {
            const id = card.dataset.partidoId;
            cargarEventosTicker(id);
        });

        actualizarPartidos();
        setInterval(actualizarPartidos, 60000); // cada 60 segundos
    });
    </script>
</body>
</html>