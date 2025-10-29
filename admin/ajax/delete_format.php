<?php
// Este archivo es un alias para mantener compatibilidad
require_once '../../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $id = $_POST['id'] ?? null;
    
    if (!$id) {
        throw new Exception('ID no especificado');
    }
    
    // Verificar que el formato existe
    $stmt = $db->prepare("SELECT id FROM campeonatos_formato WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) {
        throw new Exception('Formato no encontrado');
    }
    
    // Eliminar formato (las foreign keys en CASCADE eliminarán todo lo relacionado)
    $stmt = $db->prepare("DELETE FROM campeonatos_formato WHERE id = ?");
    $stmt->execute([$id]);
    
    logActivity("Formato de campeonato eliminado: ID $id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Formato eliminado correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error al eliminar formato: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>