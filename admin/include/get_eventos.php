<?php
/**
 * API para obtener eventos de un partido
 * Archivo: admin/ajax/get_eventos.php
 */

require_once '../../config.php';
require_once '../include/sanciones_functions.php';

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
    
    // Obtener jugadores que participaron en el partido
    $stmt = $db->prepare("
        SELECT jp.jugador_id, jp.numero_camiseta, j.equipo_id, j.apellido_nombre
        FROM jugadores_partido jp
        JOIN jugadores j ON jp.jugador_id = j.id
        WHERE jp.partido_id = ?
    ");
    $stmt->execute([$partido_id]);
    $jugadores_partido = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener goles
    $stmt = $db->prepare("
        SELECT ep.jugador_id, ep.tipo_evento, j.equipo_id
        FROM eventos_partido ep
        JOIN jugadores j ON ep.jugador_id = j.id
        WHERE ep.partido_id = ? AND ep.tipo_evento = 'gol'
        ORDER BY ep.id
    ");
    $stmt->execute([$partido_id]);
    $goles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener tarjetas
    $stmt = $db->prepare("
        SELECT ep.jugador_id, ep.tipo_evento, ep.observaciones, j.equipo_id
        FROM eventos_partido ep
        JOIN jugadores j ON ep.jugador_id = j.id
        WHERE ep.partido_id = ? AND ep.tipo_evento IN ('amarilla', 'roja')
        ORDER BY ep.id
    ");
    $stmt->execute([$partido_id]);
    $tarjetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retornar datos
    echo json_encode([
        'jugadores_partido' => $jugadores_partido,
        'goles' => $goles,
        'tarjetas' => $tarjetas
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>