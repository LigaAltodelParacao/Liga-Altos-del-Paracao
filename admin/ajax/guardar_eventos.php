<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('planillero')) {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit;
}

$db = Database::getInstance()->getConnection();
$user = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $partido_id   = $_POST['partido_id'] ?? null;
    $jugador_id   = $_POST['jugador_id'] ?? null;
    $tipo_evento  = $_POST['tipo_evento'] ?? null;
    $minuto       = $_POST['minuto'] ?? null;
    $descripcion  = $_POST['descripcion'] ?? null;

    // Validaciones básicas
    if (!$partido_id || !$jugador_id || !$tipo_evento || !$minuto) {
        echo json_encode(['success' => false, 'mensaje' => 'Faltan datos obligatorios']);
        exit;
    }

    try {
        // Insertar evento
        $stmt = $db->prepare("
            INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto, descripcion)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$partido_id, $jugador_id, $tipo_evento, $minuto, $descripcion]);

        // Actualizar marcador automáticamente si es gol
        if ($tipo_evento === 'gol') {
            // Detectar si el jugador es del equipo local o visitante
            $stmt = $db->prepare("
                SELECT p.equipo_local_id, p.equipo_visitante_id, j.equipo_id
                FROM partidos p
                JOIN jugadores j ON j.id = ?
                WHERE p.id = ?
            ");
            $stmt->execute([$jugador_id, $partido_id]);
            $data = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($data) {
                if ($data['equipo_id'] == $data['equipo_local_id']) {
                    $db->prepare("UPDATE partidos SET goles_local = goles_local + 1 WHERE id = ?")
                       ->execute([$partido_id]);
                } else {
                    $db->prepare("UPDATE partidos SET goles_visitante = goles_visitante + 1 WHERE id = ?")
                       ->execute([$partido_id]);
                }
            }
        }

        echo json_encode(['success' => true, 'mensaje' => 'Evento registrado']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
}
