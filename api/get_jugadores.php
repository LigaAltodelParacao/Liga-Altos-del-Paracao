<?php
// api/get_jugadores.php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
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
    
    $stmt = $db->prepare("
        SELECT id, apellido_nombre 
        FROM jugadores 
        WHERE equipo_id = ? AND activo = 1 
        ORDER BY apellido_nombre
    ");
    $stmt->execute([$equipo_id]);
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($jugadores);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>