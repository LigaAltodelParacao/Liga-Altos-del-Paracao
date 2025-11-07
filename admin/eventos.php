<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Procesar eventos POST
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
                    $stmt = $db->prepare("
                        SELECT j.equipo_id, p.equipo_local_id 
                        FROM jugadores j
                        JOIN partidos p ON p.id = ?
                        WHERE j.id = ?
                    ");
                    $stmt->execute([$partido_id, $jugador_id]);
                    $info = $stmt->fetch();

                    if ($info['equipo_id'] == $info['equipo_local_id']) {
                        $stmt = $db->prepare("UPDATE partidos SET goles_local = goles_local + 1 WHERE id = ?");
                    } else {
                        $stmt = $db->prepare("UPDATE partidos SET goles_visitante = goles_visitante + 1 WHERE id = ?");
                    }
                    $stmt->execute([$partido_id]);
                }

                // Tarjeta doble amarilla
                if ($tipo_evento == 'amarilla') {
                    $stmt = $db->prepare("
                        SELECT COUNT(*) as amarillas 
                        FROM eventos_partido 
                        WHERE partido_id = ? AND jugador_id = ? AND tipo_evento = 'amarilla'
                    ");
                    $stmt->execute([$partido_id, $jugador_id]);
                    $amarillas = $stmt->fetch()['amarillas'];

                    if ($amarillas >= 2) {
                        $stmt = $db->prepare("
                            INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto, descripcion) 
                            VALUES (?, ?, 'roja', ?, 'Doble amarilla')
                        ");
                        $stmt->execute([$partido_id, $jugador_id, $minuto]);

                        $stmt = $db->prepare("
                            INSERT INTO sanciones (jugador_id, tipo, partidos_suspension, descripcion, fecha_sancion)
                            VALUES (?, 'doble_amarilla', 1, 'Sanción automática por doble amarilla', CURDATE())
                        ");
                        $stmt->execute([$jugador_id]);
                    }
                }

                // Actualizar minuto
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

// Obtener partido seleccionado (histórico o en curso)
$partido_id = $_GET['partido'] ?? $_GET['id'] ?? null;
$partido_seleccionado = null;
$eventos_partido = [];
$jugadores_local = [];
$jugadores_visitante = [];

if ($partido_id) {
    $stmt = $db->prepare("
        SELECT p.*, 
               el.nombre as equipo_local, el.logo as logo_local,
               ev.nombre as equipo_visitante, ev.logo as logo_visitante,
               c.nombre as cancha,
               cat.nombre as categoria,
               camp.nombre as campeonato
        FROM partidos p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        JOIN fechas f ON p.fecha_id = f.id
        JOIN categorias cat ON f.categoria_id = cat.id
        JOIN campeonatos camp ON cat.campeonato_id = camp.id
        WHERE p.id = ?
    ");
    $stmt->execute([$partido_id]);
    $partido_seleccionado = $stmt->fetch();

    if ($partido_seleccionado) {
        // Eventos del partido
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

        // Jugadores habilitados (para agregar eventos)
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
<title>Eventos - Sistema de Campeonatos</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>

<div class="container mt-4">
    <?php if($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>

    <?php if($partido_seleccionado): ?>
        <h3><?= $partido_seleccionado['equipo_local'] ?> vs <?= $partido_seleccionado['equipo_visitante'] ?></h3>
        <p><b>Cancha:</b> <?= $partido_seleccionado['cancha'] ?> | <b>Estado:</b> <?= ucfirst($partido_seleccionado['estado']) ?></p>

        <h4>Eventos</h4>
        <?php if($eventos_partido): ?>
            <ul class="list-group">
                <?php foreach($eventos_partido as $ev): ?>
                    <li class="list-group-item">
                        <?= $ev['minuto'] ?>' - <?= $ev['tipo_evento'] ?> - <?= $ev['apellido_nombre'] ?> (<?= $ev['equipo_nombre'] ?>)
                        <?php if($ev['descripcion']): ?>
                            - <?= $ev['descripcion'] ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No hay eventos registrados para este partido.</p>
        <?php endif; ?>

        <?php if($partido_seleccionado['estado'] == 'en_curso'): ?>
            <!-- Modal y botones para agregar evento -->
            <?php include 'include/eventos_html.php'; ?>
        <?php endif; ?>
    <?php else: ?>
        <p>Partido no encontrado o no seleccionado.</p>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
