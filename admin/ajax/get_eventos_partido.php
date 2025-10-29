<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(403);
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
    
    $stmt = $db->prepare("
        SELECT e.*, j.apellido_nombre, j.equipo_id
        FROM eventos_partido e
        JOIN jugadores j ON e.jugador_id = j.id
        WHERE e.partido_id = ?
        ORDER BY e.tipo_evento, e.created_at
    ");
    $stmt->execute([$partido_id]);
    $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($eventos);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>