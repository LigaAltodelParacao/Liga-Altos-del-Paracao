<?php
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
        throw new Exception('Formato no especificado');
    }
    
    // Verificar que el formato existe
    $stmt = $db->prepare("SELECT id FROM campeonatos_formato WHERE id = ?");
    $stmt->execute([$formato_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Formato no encontrado');
    }
    
    // Contar equipos a eliminar
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM equipos_zonas ez
        JOIN zonas z ON ez.zona_id = z.id
        WHERE z.formato_id = ?
    ");
    $stmt->execute([$formato_id]);
    $total = $stmt->fetch()['total'];
    
    // Limpiar todas las asignaciones de equipos
    $stmt = $db->prepare("
        DELETE ez FROM equipos_zonas ez
        JOIN zonas z ON ez.zona_id = z.id
        WHERE z.formato_id = ?
    ");
    $stmt->execute([$formato_id]);
    
    logActivity("Asignaciones de equipos limpiadas del formato $formato_id ($total equipos removidos)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Asignaciones eliminadas correctamente',
        'equipos_removidos' => $total
    ]);
    
} catch (Exception $e) {
    error_log("Error al limpiar asignaciones: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>