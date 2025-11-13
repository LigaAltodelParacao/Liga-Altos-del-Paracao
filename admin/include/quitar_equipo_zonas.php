<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $equipo_id = $_POST['equipo_id'] ?? null;
    $zona_id = $_POST['zona_id'] ?? null;
    $formato_id = $_POST['formato_id'] ?? null;
    
    if (!$equipo_id || !$zona_id) {
        throw new Exception('Datos incompletos. Se requiere equipo_id y zona_id');
    }
    
    // Verificar que el equipo está asignado a esa zona
    $stmt = $db->prepare("
        SELECT ez.id, e.nombre as equipo_nombre, z.nombre as zona_nombre
        FROM equipos_zonas ez
        JOIN equipos e ON ez.equipo_id = e.id
        JOIN zonas z ON ez.zona_id = z.id
        WHERE ez.equipo_id = ? AND ez.zona_id = ?
    ");
    $stmt->execute([$equipo_id, $zona_id]);
    $asignacion = $stmt->fetch();
    
    if (!$asignacion) {
        throw new Exception('El equipo no está asignado a esa zona');
    }
    
    // Eliminar asignación
    $stmt = $db->prepare("DELETE FROM equipos_zonas WHERE equipo_id = ? AND zona_id = ?");
    $stmt->execute([$equipo_id, $zona_id]);
    
    // Reordenar posiciones de los equipos restantes
    $stmt = $db->prepare("
        SELECT id FROM equipos_zonas 
        WHERE zona_id = ? 
        ORDER BY posicion
    ");
    $stmt->execute([$zona_id]);
    $equipos_restantes = $stmt->fetchAll();
    
    $stmt_update = $db->prepare("UPDATE equipos_zonas SET posicion = ? WHERE id = ?");
    $posicion = 1;
    foreach ($equipos_restantes as $eq) {
        $stmt_update->execute([$posicion, $eq['id']]);
        $posicion++;
    }
    
    logActivity("Equipo '{$asignacion['equipo_nombre']}' removido de {$asignacion['zona_nombre']}");
    
    echo json_encode([
        'success' => true,
        'message' => 'Equipo removido correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error al quitar equipo: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>