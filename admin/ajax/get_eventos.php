<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$db = Database::getInstance()->getConnection();

$partido_id = $_GET['partido_id'] ?? null;
$tipo_partido = $_GET['tipo_partido'] ?? 'normal';

if (!$partido_id) {
    echo json_encode(['error' => 'ID de partido requerido']);
    exit;
}

try {
    // Obtener jugadores que participaron del partido
    $stmt = $db->prepare("
        SELECT jp.*, j.equipo_id
        FROM jugadores_partido jp
        JOIN jugadores j ON jp.jugador_id = j.id
        WHERE jp.partido_id = ? AND jp.tipo_partido = ?
    ");
    $stmt->execute([$partido_id, $tipo_partido]);
    $jugadores_partido = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener goles
    $stmt = $db->prepare("
        SELECT e.*, j.equipo_id
        FROM eventos_partido e
        JOIN jugadores j ON e.jugador_id = j.id
        WHERE e.partido_id = ? AND e.tipo_evento = 'gol' AND e.tipo_partido = ?
        ORDER BY e.id
    ");
    $stmt->execute([$partido_id, $tipo_partido]);
    $goles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener tarjetas
    $stmt = $db->prepare("
        SELECT e.*, j.equipo_id
        FROM eventos_partido e
        JOIN jugadores j ON e.jugador_id = j.id
        WHERE e.partido_id = ? AND e.tipo_evento IN ('amarilla', 'roja') AND e.tipo_partido = ?
        ORDER BY e.id
    ");
    $stmt->execute([$partido_id, $tipo_partido]);
    $tarjetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'jugadores_partido' => $jugadores_partido,
        'goles' => $goles,
        'tarjetas' => $tarjetas
    ]);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}