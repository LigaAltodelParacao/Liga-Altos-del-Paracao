<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Obtener partidos en vivo
    $stmt = $db->query("
        SELECT p.id, p.goles_local, p.goles_visitante, p.minuto_actual, p.estado,
               el.nombre as equipo_local, ev.nombre as equipo_visitante
        FROM partidos p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        WHERE p.estado = 'en_curso'
    ");
    
    $matches = $stmt->fetchAll();
    
    // Para cada partido, verificar si hay nuevos goles (simplificado)
    foreach ($matches as &$match) {
        // En una implementación real, compararías con el estado anterior
        $match['new_goal'] = false;
    }
    
    echo json_encode([
        'success' => true,
        'matches' => $matches,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}