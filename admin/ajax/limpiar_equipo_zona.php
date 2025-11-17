<?php
// admin/ajax/limpiar_equipos_zonas.php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    $formato_id = $_POST['formato_id'] ?? null;
    
    if (!$formato_id) {
        throw new Exception('ID de formato no proporcionado');
    }
    
    $stmt = $db->prepare("
        DELETE ez FROM equipos_zonas ez
        JOIN zonas z ON ez.zona_id = z.id
        WHERE z.formato_id = ?
    ");
    $stmt->execute([$formato_id]);
    
    logActivity("Asignaciones limpiadas para formato ID $formato_id");
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>