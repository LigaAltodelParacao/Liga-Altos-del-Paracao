<?php
// admin/get_eventos_zona.php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

$db = Database::getInstance()->getConnection();
$partido_id = $_GET['partido_id'] ?? null;

if (!$partido_id) {
    echo json_encode(['success' => false, 'message' => 'Partido no especificado']);
    exit;
}

try {
    // Obtener jugadores que jugaron con sus números
    $stmt = $db->prepare("
        SELECT jp.*, j.equipo_id
        FROM jugadores_partido jp
        JOIN jugadores j ON jp.jugador_id = j.id
        WHERE jp.partido_id = ? AND jp.tipo_partido = 'zona'
    ");
    $stmt->execute([$partido_id]);
    $jugadores_partido = $stmt->fetchAll();
    
    // Obtener goles
    $stmt = $db->prepare("
        SELECT ep.*, j.equipo_id
        FROM eventos_partido ep
        JOIN jugadores j ON ep.jugador_id = j.id
        WHERE ep.partido_id = ? AND ep.tipo_evento = 'gol' AND ep.tipo_partido = 'zona'
        ORDER BY ep.created_at
    ");
    $stmt->execute([$partido_id]);
    $goles = $stmt->fetchAll();
    
    // Obtener tarjetas
    $stmt = $db->prepare("
        SELECT ep.*, j.equipo_id
        FROM eventos_partido ep
        JOIN jugadores j ON ep.jugador_id = j.id
        WHERE ep.partido_id = ? AND ep.tipo_evento IN ('amarilla', 'roja') AND ep.tipo_partido = 'zona'
        ORDER BY ep.created_at
    ");
    $stmt->execute([$partido_id]);
    $tarjetas = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'jugadores_partido' => $jugadores_partido,
        'goles' => $goles,
        'tarjetas' => $tarjetas
    ]);
    
} catch (Exception $e) {
    error_log("Error en get_eventos_zona.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>