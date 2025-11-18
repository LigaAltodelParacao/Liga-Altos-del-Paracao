<?php
/**
 * API para obtener eventos de un partido
 * Archivo: admin/ajax/get_eventos.php
 * 
 * CORRECCIÓN: Ahora carga correctamente todas las tarjetas, incluyendo las que están
 * grabadas en la base de datos desde el primer partido
 */

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
    
    // Obtener jugadores que participaron en el partido con sus números
    $stmt = $db->prepare("
        SELECT jp.jugador_id, jp.numero_camiseta, j.equipo_id, j.apellido_nombre
        FROM jugadores_partido jp
        JOIN jugadores j ON jp.jugador_id = j.id
        WHERE jp.partido_id = ?
        ORDER BY j.apellido_nombre
    ");
    $stmt->execute([$partido_id]);
    $jugadores_partido = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener goles con información completa
    $stmt = $db->prepare("
        SELECT ep.id, ep.jugador_id, ep.tipo_evento, ep.minuto, j.equipo_id, j.apellido_nombre
        FROM eventos_partido ep
        JOIN jugadores j ON ep.jugador_id = j.id
        WHERE ep.partido_id = ? AND ep.tipo_evento = 'gol'
        ORDER BY ep.id ASC
    ");
    $stmt->execute([$partido_id]);
    $goles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener tarjetas con información completa (amarillas y rojas)
    // IMPORTANTE: Las rojas pueden tener observaciones indicando si son por doble amarilla
    $stmt = $db->prepare("
        SELECT ep.id, ep.jugador_id, ep.tipo_evento, ep.minuto, ep.observaciones, j.equipo_id, j.apellido_nombre
        FROM eventos_partido ep
        JOIN jugadores j ON ep.jugador_id = j.id
        WHERE ep.partido_id = ? AND ep.tipo_evento IN ('amarilla', 'roja')
        ORDER BY ep.id ASC
    ");
    $stmt->execute([$partido_id]);
    $tarjetas_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Procesar tarjetas: las rojas por doble amarilla ya están convertidas en la BD
    // Solo devolvemos lo que está en la base de datos tal cual
    $tarjetas = [];
    foreach ($tarjetas_raw as $tarjeta) {
        $tarjetas[] = [
            'id' => $tarjeta['id'],
            'jugador_id' => $tarjeta['jugador_id'],
            'tipo_evento' => $tarjeta['tipo_evento'], // 'amarilla' o 'roja'
            'minuto' => $tarjeta['minuto'],
            'observaciones' => $tarjeta['observaciones'],
            'equipo_id' => $tarjeta['equipo_id'],
            'apellido_nombre' => $tarjeta['apellido_nombre']
        ];
    }
    
    // Retornar datos en formato JSON
    $response = [
        'success' => true,
        'jugadores_partido' => $jugadores_partido,
        'goles' => $goles,
        'tarjetas' => $tarjetas
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
?>