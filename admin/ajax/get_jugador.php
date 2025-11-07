<?php
/**
 * API para obtener jugadores de un equipo
 * Archivo: admin/ajax/get_jugadores.php
 */

require_once '../../config.php';
require_once '../include/sanciones_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$equipo_id = $_GET['equipo_id'] ?? null;
$editar_partido = isset($_GET['editar_partido']);

if (!$equipo_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de equipo requerido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Si es para editar un partido, mostrar TODOS los jugadores
    if ($editar_partido) {
        $sql = "
            SELECT j.id, j.apellido_nombre, j.activo
            FROM jugadores j
            WHERE j.equipo_id = ?
            ORDER BY j.apellido_nombre
        ";
    } else {
        // Si es para cargar resultado nuevo, solo mostrar jugadores activos y sin sanciones activas
        $sql = "
            SELECT j.id, j.apellido_nombre, j.activo
            FROM jugadores j
            LEFT JOIN sanciones s ON j.id = s.jugador_id AND s.activa = 1 AND s.partidos_cumplidos < s.partidos_suspension
            WHERE j.equipo_id = ? 
            AND j.activo = 1
            AND s.id IS NULL
            ORDER BY j.apellido_nombre
        ";
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$equipo_id]);
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($jugadores);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>