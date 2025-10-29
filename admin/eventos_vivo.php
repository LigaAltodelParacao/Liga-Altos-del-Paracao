<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Procesar eventos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'iniciar_partido':
            $partido_id = $_POST['partido_id'];
            try {
                $stmt = $db->prepare("
                    UPDATE partidos 
                    SET estado = 'en_curso', iniciado_at = NOW(), minuto_actual = 0, tiempo_actual = 'primer_tiempo'
                    WHERE id = ? AND estado = 'programado'
                ");
                $stmt->execute([$partido_id]);
                $message = 'Partido iniciado correctamente';
            } catch (Exception $e) {
                $error = 'Error al iniciar partido: ' . $e->getMessage();
            }
            break;
            
        case 'finalizar_partido':
            $partido_id = $_POST['partido_id'];
            try {
                $stmt = $db->prepare("
                    UPDATE partidos 
                    SET estado = 'finalizado', finalizado_at = NOW(), tiempo_actual = 'finalizado'
                    WHERE id = ? AND estado = 'en_curso'
                ");
                $stmt->execute([$partido_id]);
                $message = 'Partido finalizado correctamente';
            } catch (Exception $e) {
                $error = 'Error al finalizar partido: ' . $e->getMessage();
            }
            break;
            
        case 'cambiar_tiempo':
            $partido_id = $_POST['partido_id'];
            $tiempo = $_POST['tiempo'];
            try {
                $stmt = $db->prepare("
                    UPDATE partidos 
                    SET tiempo_actual = ?
                    WHERE id = ? AND estado = 'en_curso'
                ");
                $stmt->execute([$tiempo, $partido_id]);
                $message = 'Tiempo cambiado correctamente';
            } catch (Exception $e) {
                $error = 'Error al cambiar tiempo: ' . $e->getMessage();
            }
            break;
            
        case 'add_event':
            $partido_id = $_POST['partido_id'];
            $jugador_id = $_POST['jugador_id'];
            $tipo_evento = $_POST['tipo_evento'];
            $minuto = $_POST['minuto'];
            $descripcion = $_POST['descripcion'] ?? '';
            
            try {
                $db->beginTransaction();
                
                // Insertar evento
                $stmt = $db->prepare("
                    INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto, descripcion) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$partido_id, $jugador_id, $tipo_evento, $minuto, $descripcion]);
                
                // Actualizar marcador si es gol
                if ($tipo_evento == 'gol') {
                    // Determinar si es local o visitante
                    $stmt = $db->prepare("
                        SELECT p.*, j.equipo_id 
                        FROM partidos p
                        JOIN jugadores j ON j.id = ?
                        WHERE p.id = ?
                    ");
                    $stmt->execute([$jugador_id, $partido_id]);
                    $info = $stmt->fetch();
                    
                    if ($info['equipo_id'] == $info['equipo_local_id']) {
                        $stmt = $db->prepare("UPDATE partidos SET goles_local = goles_local + 1 WHERE id = ?");
                    } else {
                        $stmt = $db->prepare("UPDATE partidos SET goles_visitante = goles_visitante + 1 WHERE id = ?");
                    }
                    $stmt->execute([$partido_id]);
                }
                
                // Verificar tarjetas rojas por doble amarilla
                if ($tipo_evento == 'amarilla') {
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as amarillas 
                        FROM eventos_partido 
                        WHERE partido_id = ? AND jugador_id = ? AND tipo_evento = 'amarilla'
                    ");
                    $stmt->execute([$partido_id, $jugador_id]);
                    $amarillas = $stmt->fetch()['amarillas'];
                    
                    if ($amarillas >= 2) {
                        // Agregar roja automática
                        $stmt = $db->prepare("
                            INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto, descripcion) 
                            VALUES (?, ?, 'roja', ?, 'Doble amarilla')
                        ");
                        $stmt->execute([$partido_id, $jugador_id, $minuto]);
                        
                        // Crear sanción automática
                        $stmt = $db->prepare("
                            INSERT INTO sanciones (jugador_id, tipo, partidos_suspension, descripcion, fecha_sancion)
                            VALUES (?, 'doble_amarilla', 1, 'Sanción automática por doble amarilla', CURDATE())
                        ");
                        $stmt->execute([$jugador_id]);
                    }
                }
                
                // Actualizar minuto del partido
                $stmt = $db->prepare("UPDATE partidos SET minuto_actual = ? WHERE id = ?");
                $stmt->execute([$minuto, $partido_id]);
                
                $db->commit();
                $message = 'Evento registrado correctamente';
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Error al registrar evento: ' . $e->getMessage();
            }
            break;
    }
}

// Obtener partidos en curso o del día
$stmt = $db->query("
    SELECT p.*, 
           el.nombre as equipo_local, el.logo as logo_local,
           ev.nombre as equipo_visitante, ev.logo as logo_visitante,
           c.nombre as cancha,
           cat.nombre as categoria,
           camp.nombre as campeonato
    FROM partidos p
    JOIN equipos el ON p.equipo_local_id = el.id
    JOIN equipos ev ON p.equipo_visitante_id = ev.id
    JOIN canchas c ON p.cancha_id = c.id
    JOIN fechas f ON p.fecha_id = f.id
    JOIN categorias cat ON f.categoria_id = cat.id
    JOIN campeonatos camp ON cat.campeonato_id = camp.id
    WHERE p.estado IN ('programado', 'en_curso') AND p.fecha_partido = CURDATE()
    ORDER BY p.estado DESC, p.hora_partido ASC
");
$partidos = $stmt->fetchAll();

// Obtener partido seleccionado
$partido_seleccionado = null;
$eventos_partido = [];
$jugadores_local = [];
$jugadores_visitante = [];

if (isset($_GET['partido'])) {
    $partido_id = $_GET['partido'];
    
    // Obtener partido con toda la información necesaria
    $stmt = $db->prepare("
        SELECT p.*, 
               el.nombre as equipo_local, el.logo as logo_local,
               ev.nombre as equipo_visitante, ev.logo as logo_visitante,
               c.nombre as cancha,
               cat.nombre as categoria, camp.nombre as campeonato,
               f.numero_fecha
        FROM partidos p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        JOIN canchas c ON p.cancha_id = c.id
        JOIN fechas f ON p.fecha_id = f.id
        JOIN categorias cat ON f.categoria_id = cat.id
        JOIN campeonatos camp ON cat.campeonato_id = camp.id
        WHERE p.id = ?
    ");
    $stmt->execute([$partido_id]);
    $partido_seleccionado = $stmt->fetch();
    
    if ($partido_seleccionado) {
        // Obtener eventos del partido
        $stmt = $db->prepare("
            SELECT e.*, j.apellido_nombre, j.dni, eq.nombre as equipo_nombre
            FROM eventos_partido e
            JOIN jugadores j ON e.jugador_id = j.id
            JOIN equipos eq ON j.equipo_id = eq.id
            WHERE e.partido_id = ?
            ORDER BY e.minuto ASC, e.created_at ASC
        ");
        $stmt->execute([$partido_id]);
        $eventos_partido = $stmt->fetchAll();
        
        // Obtener jugadores habilitados
        $stmt = $db->prepare("
            SELECT j.*, 
                   (SELECT COUNT(*) FROM sanciones s WHERE s.jugador_id = j.id AND s.activa = 1 AND s.partidos_cumplidos < s.partidos_suspension) as sancionado
            FROM jugadores j
            WHERE j.equipo_id = ? AND j.activo = 1
            ORDER BY j.apellido_nombre ASC
        ");
        $stmt->execute([$partido_seleccionado['equipo_local_id']]);
        $jugadores_local = $stmt->fetchAll();
        
        $stmt->execute([$partido_seleccionado['equipo_visitante_id']]);
        $jugadores_visitante = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventos en Vivo - Sistema de Campeonatos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-futbol"></i> Fútbol Manager - Eventos en Vivo
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0">
                <?php include 'include/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-play"></i> Eventos en Vivo
                        <span class="live-indicator"></span>
                    </h2>
                    <div class="text-muted">
                        <i class="fas fa-clock"></i> <span id="reloj"><?php echo date('H:i:s'); ?></span>
                    </div>
                </div>

                <!-- Mensajes -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <!-- Lista de Partidos -->
                    <div class="col-lg-4 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-calendar-day"></i> Partidos de Hoy</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($partidos)): ?>
                                    <p class="text-muted">No hay partidos programados para hoy.</p>
                                <?php else: ?>
                                    <?php foreach ($partidos as $partido): ?>
                                        <div class="match-card <?php echo $partido['estado'] == 'en_curso' ? 'live' : ''; ?> mb-3" 
                                             onclick="seleccionarPartido(<?php echo $partido['id']; ?>)"
                                             style="cursor: pointer;">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($partido['equipo_local']); ?></strong>
                                                    <br>
                                                    <strong><?php echo htmlspecialchars($partido['equipo_visitante']); ?></strong>
                                                </div>
                                                <div class="text-center">
                                                    <?php if ($partido['estado'] == 'en_curso'): ?>
                                                        <div class="score"><?php echo $partido['goles_local']; ?></div>
                                                        <div class="score"><?php echo $partido['goles_visitante']; ?></div>
                                                        <small class="text-danger">
                                                            <i class="fas fa-circle"></i> <?php echo $partido['minuto_actual']; ?>'
                                                        </small>
                                                    <?php else: ?>
                                                        <small><?php echo date('H:i', strtotime($partido['hora_partido'])); ?></small>
                                                        <br>
                                                        <span class="badge bg-secondary">Programado</span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($partido['categoria']); ?> - <?php echo htmlspecialchars($partido['cancha']); ?>
                                            </small>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Panel de Control del Partido -->
                    <div class="col-lg-8">
                        <?php if (!$partido_seleccionado): ?>
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-futbol fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">Selecciona un partido para comenzar</h4>
                                    <p class="text-muted">Elige un partido de la lista para gestionar eventos en vivo</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- Marcador -->
                            <div class="card mb-4">
                                <div class="card-header bg-dark text-white text-center">
                                    <h3><?php echo htmlspecialchars($partido_seleccionado['campeonato'] . ' - ' . $partido_seleccionado['categoria']); ?></h3>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <?php if ($partido_seleccionado['logo_local']): ?>
                                                <img src="../uploads/<?php echo $partido_seleccionado['logo_local']; ?>" 
                                                     alt="Logo" width="60" height="60" class="mb-2">
                                            <?php endif; ?>
                                            <h4><?php echo htmlspecialchars($partido_seleccionado['equipo_local']); ?></h4>
                                        </div>
                                        <div class="col-4">
                                            <div class="match-timer <?php echo $partido_seleccionado['estado'] == 'en_curso' ? 'running' : ''; ?>">
                                                <?php if ($partido_seleccionado['estado'] == 'en_curso'): ?>
                                                    <?php echo $partido_seleccionado['minuto_actual']; ?>' - <?php echo ucfirst($partido_seleccionado['tiempo_actual']); ?>
                                                <?php else: ?>
                                                    <?php echo date('H:i', strtotime($partido_seleccionado['hora_partido'])); ?>
                                                <?php endif; ?>
                                            </div>
                                            <h2 class="mb-0">
                                                <?php echo $partido_seleccionado['goles_local']; ?> - <?php echo $partido_seleccionado['goles_visitante']; ?>
                                            </h2>
                                            <small class="text-muted"><?php echo htmlspecialchars($partido_seleccionado['cancha']); ?></small>
                                        </div>
                                        <div class="col-4">
                                            <?php if ($partido_seleccionado['logo_visitante']): ?>
                                                <img src="../uploads/<?php echo $partido_seleccionado['logo_visitante']; ?>" 
                                                     alt="Logo" width="60" height="60" class="mb-2">
                                            <?php endif; ?>
                                            <h4><?php echo htmlspecialchars($partido_seleccionado['equipo_visitante']); ?></h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Controles del Partido -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5><i class="fas fa-cogs"></i> Control del Partido</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <?php if ($partido_seleccionado['estado'] == 'programado'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="iniciar_partido">
                                                    <input type="hidden" name="partido_id" value="<?php echo $partido_seleccionado['id']; ?>">
                                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                                        <i class="fas fa-play"></i> Iniciar Partido
                                                    </button>
                                                </form>
                                            <?php elseif ($partido_seleccionado['estado'] == 'en_curso'): ?>
                                                <div class="btn-group w-100">
                                                    <button class="btn btn-warning" onclick="cambiarTiempo('primer_tiempo')">
                                                        1er Tiempo
                                                    </button>
                                                    <button class="btn btn-info" onclick="cambiarTiempo('descanso')">
                                                        Descanso
                                                    </button>
                                                    <button class="btn btn-warning" onclick="cambiarTiempo('segundo_tiempo')">
                                                        2do Tiempo
                                                    </button>
                                                </div>
                                                <form method="POST" class="mt-2" style="display: inline;">
                                                    <input type="hidden" name="action" value="finalizar_partido">
                                                    <input type="hidden" name="partido_id" value="<?php echo $partido_seleccionado['id']; ?>">
                                                    <button type="submit" class="btn btn-danger w-100" 
                                                            onclick="return confirm('¿Finalizar el partido?')">
                                                        <i class="fas fa-stop"></i> Finalizar Partido
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if ($partido_seleccionado['estado'] == 'en_curso'): ?>
                                                <button class="btn btn-primary btn-lg w-100" data-bs-toggle="modal" data-bs-target="#modalEvento">
                                                    <i class="fas fa-plus"></i> Agregar Evento
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Eventos del Partido -->
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-list"></i> Eventos del Partido</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($eventos_partido)): ?>
                                        <p class="text-muted">No hay eventos registrados aún.</p>
                                    <?php else: ?>
                                        <div class="timeline">
                                            <?php foreach (array_reverse($eventos_partido) as $evento): ?>
                                                <div class="event-card event-<?php echo $evento['tipo_evento']; ?>">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="flex-grow-1">
                                                            <div class="d-flex align-items-center">
                                                                <span class="badge bg-dark me-2"><?php echo $evento['minuto']; ?>'</span>
                                                                <?php
                                                                $icon = 'fas fa-circle';
                                                                $color = 'text-muted';
                                                                switch ($evento['tipo_evento']) {
                                                                    case 'gol':
                                                                        $icon = 'fas fa-futbol';
                                                                        $color = 'text-success';
                                                                        break;
                                                                    case 'amarilla':
                                                                        $icon = 'fas fa-square';
                                                                        $color = 'text-warning';
                                                                        break;
                                                                    case 'roja':
                                                                        $icon = 'fas fa-square';
                                                                        $color = 'text-danger';
                                                                        break;
                                                                }
                                                                ?>
                                                                <i class="<?php echo $icon; ?> <?php echo $color; ?> me-2"></i>
                                                                <strong><?php echo htmlspecialchars($evento['apellido_nombre']); ?></strong>
                                                                <small class="text-muted ms-2">(<?php echo htmlspecialchars($evento['equipo_nombre']); ?>)</small>
                                                            </div>
                                                            <?php if ($evento['descripcion']): ?>
                                                                <small class="text-muted"><?php echo htmlspecialchars($evento['descripcion']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-uppercase fw-bold">
                                                            <?php echo $evento['tipo_evento']; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Agregar Evento -->
    <?php if ($partido_seleccionado && $partido_seleccionado['estado'] == 'en_curso'): ?>
    <div class="modal fade" id="modalEvento" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus"></i> Agregar Evento
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_event">
                    <input type="hidden" name="partido_id" value="<?php echo $partido_seleccionado['id']; ?>">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tipo_evento" class="form-label">Tipo de Evento *</label>
                                    <select class="form-select" id="tipo_evento" name="tipo_evento" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="gol">⚽ Gol</option>
                                        <option value="amarilla">🟨 Tarjeta Amarilla</option>
                                        <option value="roja">🟥 Tarjeta Roja</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="minuto" class="form-label">Minuto *</label>
                                    <input type="number" class="form-control" id="minuto" name="minuto" 
                                           min="0" max="120" value="<?php echo $partido_seleccionado['minuto_actual']; ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Jugador *</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><?php echo htmlspecialchars($partido_seleccionado['equipo_local']); ?></h6>
                                    <?php foreach ($jugadores_local as $jugador): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="jugador_id" 
                                                   value="<?php echo $jugador['id']; ?>" id="local_<?php echo $jugador['id']; ?>"
                                                   <?php echo $jugador['sancionado'] ? 'disabled' : ''; ?>>
                                            <label class="form-check-label" for="local_<?php echo $jugador['id']; ?>">
                                                <?php echo htmlspecialchars($jugador['apellido_nombre']); ?>
                                                <?php if ($jugador['sancionado']): ?>
                                                    <span class="badge bg-danger">Sancionado</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="col-md-6">
                                    <h6><?php echo htmlspecialchars($partido_seleccionado['equipo_visitante']); ?></h6>
                                    <?php foreach ($jugadores_visitante as $jugador): ?>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="jugador_id" 
                                                   value="<?php echo $jugador['id']; ?>" id="visitante_<?php echo $jugador['id']; ?>"
                                                   <?php echo $jugador['sancionado'] ? 'disabled' : ''; ?>>
                                            <label class="form-check-label" for="visitante_<?php echo $jugador['id']; ?>">
                                                <?php echo htmlspecialchars($jugador['apellido_nombre']); ?>
                                                <?php if ($jugador['sancionado']): ?>
                                                    <span class="badge bg-danger">Sancionado</span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Agregar Evento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Forms ocultos para acciones rápidas -->
    <form method="POST" id="formCambiarTiempo" style="display: none;">
        <input type="hidden" name="action" value="cambiar_tiempo">
        <input type="hidden" name="partido_id" value="<?php echo $partido_seleccionado['id'] ?? ''; ?>">
        <input type="hidden" name="tiempo" id="tiempoInput">
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function seleccionarPartido(partidoId) {
            window.location.href = 'eventos.php?partido=' + partidoId;
        }

        function cambiarTiempo(tiempo) {
            document.getElementById('tiempoInput').value = tiempo;
            document.getElementById('formCambiarTiempo').submit();
        }

        // Actualizar reloj
        function actualizarReloj() {
            const now = new Date();
            const tiempo = now.toLocaleTimeString('es-AR');
            document.getElementById('reloj').textContent = tiempo;
        }

        setInterval(actualizarReloj, 1000);

        // Auto refresh cada 30 segundos para eventos en vivo
        <?php if ($partido_seleccionado && $partido_seleccionado['estado'] == 'en_curso'): ?>
        setInterval(function() {
            location.reload();
        }, 30000);
        <?php endif; ?>

        // Limpiar formulario al cerrar modal
        document.getElementById('modalEvento')?.addEventListener('hidden.bs.modal', function () {
            document.querySelector('#modalEvento form').reset();
        });

        // Notificación sonora para eventos importantes
        function playNotification() {
            // Crear sonido simple con Web Audio API
            const audioContext = new (window.AudioContext || window.webkitAudioContext)();
            const oscillator = audioContext.createOscillator();
            const gainNode = audioContext.createGain();
            
            oscillator.connect(gainNode);
            gainNode.connect(audioContext.destination);
            
            oscillator.frequency.value = 800;
            oscillator.type = 'sine';
            
            gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
            gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 1);
            
            oscillator.start();
            oscillator.stop(audioContext.currentTime + 1);
        }

        // Avisar cuando hay nuevos eventos
        <?php if ($message && strpos($message, 'Evento') !== false): ?>
            playNotification();
        <?php endif; ?>
    </script>
</body>
</html>
                