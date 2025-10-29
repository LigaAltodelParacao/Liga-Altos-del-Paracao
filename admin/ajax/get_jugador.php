<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    echo json_encode(['success' => false, 'error' => 'No autorizado']);
    exit;
}

$jugador_id = $_GET['id'] ?? '';

if (empty($jugador_id)) {
    echo json_encode(['success' => false, 'error' => 'ID de jugador requerido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("
        SELECT j.*, e.nombre as equipo_nombre, e.categoria_id
        FROM jugadores j
        JOIN equipos e ON j.equipo_id = e.id
        WHERE j.id = ?
    ");
    $stmt->execute([$jugador_id]);
    $jugador = $stmt->fetch();
    
    if (!$jugador) {
        echo json_encode(['success' => false, 'error' => 'Jugador no encontrado']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'jugador' => $jugador
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}