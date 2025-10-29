<?php
require_once '../config.php';

header('Content-Type: application/json');

$partido_id = $_GET['partido_id'] ?? null;

if (!$partido_id) {
    echo json_encode(['error' => 'No se indicó el partido']);
    exit;
}

$db = Database::getInstance()->getConnection();

try {
    // Obtener goles
    $stmt = $db->prepare("
        SELECT ev.id, ev.minuto, j.apellido_nombre, j.id as jugador_id
        FROM eventos_partido ev
        JOIN jugadores j ON ev.jugador_id = j.id
        WHERE ev.partido_id = ? AND ev.tipo_evento = 'gol'
        ORDER BY ev.minuto ASC
    ");
    $stmt->execute([$partido_id]);
    $goles = $stmt->fetchAll();

    // Obtener tarjetas
    $stmt = $db->prepare("
        SELECT ev.id, ev.minuto, j.apellido_nombre, j.id as jugador_id, ev.tipo_evento
        FROM eventos_partido ev
        JOIN jugadores j ON ev.jugador_id = j.id
        WHERE ev.partido_id = ? AND ev.tipo_evento IN ('amarilla','roja','doble_amarilla')
        ORDER BY ev.minuto ASC
    ");
    $stmt->execute([$partido_id]);
    $tarjetas = $stmt->fetchAll();

    echo json_encode([
        'goles' => $goles,
        'tarjetas' => $tarjetas
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
