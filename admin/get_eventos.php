<?php
/**
 * API: Obtener eventos de un partido
 * Ubicación: admin/get_eventos.php
 */

require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

$partido_id = $_GET['partido_id'] ?? null;

if (!$partido_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Falta partido_id']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener goles
    $stmt = $db->prepare("
        SELECT 
            ev.id,
            ev.jugador_id,
            ev.tipo_evento,
            ev.minuto,
            j.apellido_nombre,
            j.equipo_id
        FROM eventos_partido ev
        JOIN jugadores j ON ev.jugador_id = j.id
        WHERE ev.partido_id = ? 
        AND ev.tipo_evento = 'gol'
        ORDER BY ev.created_at ASC
    ");
    $stmt->execute([$partido_id]);
    $goles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener tarjetas
    $stmt = $db->prepare("
        SELECT 
            ev.id,
            ev.jugador_id,
            ev.tipo_evento,
            ev.minuto,
            j.apellido_nombre,
            j.equipo_id
        FROM eventos_partido ev
        JOIN jugadores j ON ev.jugador_id = j.id
        WHERE ev.partido_id = ? 
        AND ev.tipo_evento IN ('amarilla', 'roja')
        ORDER BY ev.created_at ASC
    ");
    $stmt->execute([$partido_id]);
    $tarjetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'goles' => $goles,
        'tarjetas' => $tarjetas
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error al obtener eventos: ' . $e->getMessage()
    ]);
}
?>