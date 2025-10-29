<?php
// get_jugadores.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$equipo_id = $_GET['equipo_id'] ?? null;

if (!$equipo_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de equipo requerido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener jugadores ACTIVOS y SIN sanción activa
    $stmt = $db->prepare("
        SELECT j.id, j.apellido_nombre 
        FROM jugadores j
        LEFT JOIN sanciones s ON j.id = s.jugador_id AND s.activa = 1
        WHERE j.equipo_id = ? 
          AND j.activo = 1 
          AND s.id IS NULL
        ORDER BY j.apellido_nombre
    ");
    $stmt->execute([(int)$equipo_id]);
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($jugadores);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>