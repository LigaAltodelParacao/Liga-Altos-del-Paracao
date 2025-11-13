<?php
// admin/ajax/quitar_equipo_zona.php
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
    
    if (!$equipo_id || !$zona_id) {
        throw new Exception('Datos incompletos');
    }
    
    $stmt = $db->prepare("DELETE FROM equipos_zonas WHERE equipo_id = ? AND zona_id = ?");
    $stmt->execute([$equipo_id, $zona_id]);
    
    logActivity("Equipo ID $equipo_id quitado de zona ID $zona_id");
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>