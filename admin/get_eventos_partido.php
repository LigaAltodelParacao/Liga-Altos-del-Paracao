<?php
// api/get_eventos_partido.php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

 $partido_id = $_GET['partido_id'] ?? null;

if (!$partido_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de partido requerido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener goles
    $stmt = $db->prepare("
        SELECT e.*, j.apellido_nombre, j.equipo_id
        FROM eventos_partido e
        JOIN jugadores j ON e.jugador_id = j.id
        WHERE e.partido_id = ? AND e.tipo_evento = 'gol'
        ORDER BY e.id
    ");
    $stmt->execute([$partido_id]);
    $goles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener tarjetas (incluyendo doble amarilla)
    $stmt = $db->prepare("
        SELECT e.*, j.apellido_nombre, j.equipo_id
        FROM eventos_partido e
        JOIN jugadores j ON e.jugador_id = j.id
        WHERE e.partido_id = ? AND e.tipo_evento IN ('amarilla', 'roja', 'doble_amarilla')
        ORDER BY e.id
    ");
    $stmt->execute([$partido_id]);
    $tarjetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'goles' => $goles,
        'tarjetas' => $tarjetas
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}