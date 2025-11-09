<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission(['superadmin', 'admin', 'planillero'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado']);
    exit;
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Función auxiliar para verificar si un planillero puede editar un partido
function puedeEditarPartido($partido_id, $db) {
    if (hasPermission(['superadmin', 'admin'])) {
        return true;
    }
    
    // Si es planillero, verificar que el partido sea de su cancha activa
    if (!isset($_SESSION['codigo_cancha_activo'])) {
        return false;
    }
    
    $cancha_id = $_SESSION['codigo_cancha_activo']['cancha_id'];
    $stmt = $db->prepare("SELECT id FROM partidos WHERE id = ? AND cancha_id = ?");
    $stmt->execute([$partido_id, $cancha_id]);
    return (bool) $stmt->fetch();
}

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        switch ($action) {
            case 'update_estado':
                $id = (int)($_POST['id'] ?? 0);
                $estado = $_POST['estado'] ?? '';
                $minuto_actual = (int)($_POST['minuto_actual'] ?? 0);
                $tiempo_actual = $_POST['tiempo_actual'] ?? 'primer_tiempo';

                // Validar estado
                $estados_validos = ['programado', 'en_curso', 'finalizado', 'suspendido'];
                $tiempos_validos = ['primer_tiempo', 'descanso', 'segundo_tiempo', 'finalizado'];
                
                if (!in_array($estado, $estados_validos) || !in_array($tiempo_actual, $tiempos_validos)) {
                    throw new Exception('Estado o tiempo inválido');
                }

                if (!puedeEditarPartido($id, $db)) {
                    throw new Exception('No tiene permiso para editar este partido');
                }

                $stmt = $db->prepare("
                    UPDATE partidos 
                    SET estado = ?, minuto_actual = ?, tiempo_actual = ?
                    WHERE id = ?
                ");
                $stmt->execute([$estado, $minuto_actual, $tiempo_actual, $id]);
                $message = 'Estado del partido actualizado';
                break;

            case 'agregar_evento':
                $partido_id = (int)($_POST['partido_id'] ?? 0);
                $jugador_id = (int)($_POST['jugador_id'] ?? 0);
                $tipo_evento = $_POST['tipo_evento'] ?? '';
                $minuto = (int)($_POST['minuto'] ?? 0);
                $descripcion = trim($_POST['descripcion'] ?? '');

                if (!puedeEditarPartido($partido_id, $db)) {
                    throw new Exception('No tiene permiso para editar este partido');
                }

                $tipos_validos = ['gol', 'amarilla', 'roja', 'cambio'];
                if (!in_array($tipo_evento, $tipos_validos)) {
                    throw new Exception('Tipo de evento inválido');
                }

                $stmt = $db->prepare("
                    INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto, descripcion)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$partido_id, $jugador_id, $tipo_evento, $minuto, $descripcion]);

                // Actualizar goles si es gol
                if ($tipo_evento === 'gol') {
                    $stmt2 = $db->prepare("SELECT equipo_local_id, equipo_visitante_id FROM partidos WHERE id = ?");
                    $stmt2->execute([$partido_id]);
                    $partido = $stmt2->fetch();

                    $stmt3 = $db->prepare("SELECT equipo_id FROM jugadores WHERE id = ?");
                    $stmt3->execute([$jugador_id]);
                    $jugador = $stmt3->fetch();

                    if ($jugador && $partido) {
                        if ($jugador['equipo_id'] == $partido['equipo_local_id']) {
                            $stmt4 = $db->prepare("UPDATE partidos SET goles_local = goles_local + 1 WHERE id = ?");
                        } else {
                            $stmt4 = $db->prepare("UPDATE partidos SET goles_visitante = goles_visitante + 1 WHERE id = ?");
                        }
                        $stmt4->execute([$partido_id]);
                    }
                }

                $message = 'Evento agregado correctamente';
                break;

            case 'eliminar_evento':
                $evento_id = (int)($_POST['evento_id'] ?? 0);

                // Primero obtener el partido_id para verificar permisos
                $stmt = $db->prepare("SELECT partido_id FROM eventos_partido WHERE id = ?");
                $stmt->execute([$evento_id]);
                $evento = $stmt->fetch();

                if (!$evento) {
                    throw new Exception('Evento no encontrado');
                }

                if (!puedeEditarPartido($evento['partido_id'], $db)) {
                    throw new Exception('No tiene permiso para editar este partido');
                }

                $stmt = $db->prepare("SELECT partido_id, jugador_id, tipo_evento FROM eventos_partido WHERE id = ?");
                $stmt->execute([$evento_id]);
                $evento = $stmt->fetch();

                if ($evento['tipo_evento'] === 'gol') {
                    $stmt2 = $db->prepare("SELECT equipo_local_id, equipo_visitante_id FROM partidos WHERE id = ?");
                    $stmt2->execute([$evento['partido_id']]);
                    $partido = $stmt2->fetch();

                    $stmt3 = $db->prepare("SELECT equipo_id FROM jugadores WHERE id = ?");
                    $stmt3->execute([$evento['jugador_id']]);
                    $jugador = $stmt3->fetch();

                    if ($jugador && $partido) {
                        if ($jugador['equipo_id'] == $partido['equipo_local_id']) {
                            $stmt4 = $db->prepare("UPDATE partidos SET goles_local = GREATEST(goles_local - 1, 0) WHERE id = ?");
                        } else {
                            $stmt4 = $db->prepare("UPDATE partidos SET goles_visitante = GREATEST(goles_visitante - 1, 0) WHERE id = ?");
                        }
                        $stmt4->execute([$evento['partido_id']]);
                    }
                }

                $stmt = $db->prepare("DELETE FROM eventos_partido WHERE id = ?");
                $stmt->execute([$evento_id]);

                $message = 'Evento eliminado correctamente';
                break;

            default:
                throw new Exception('Acción no válida');
        }

        // Responder en JSON si es AJAX
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true, 'message' => $message]);
            exit;
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $error]);
            exit;
        }
    }
}

// Si no es POST, redirigir o mostrar error (según uso)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Si se accede directamente, redirigir al panel
    if (hasPermission(['superadmin', 'admin'])) {
        redirect('dashboard.php');
    } else {
        redirect('../index.php');
    }
}