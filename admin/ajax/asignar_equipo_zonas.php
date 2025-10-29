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
    $equipo_id = $_POST['equipo_id'] ?? null;
    $zona_id = $_POST['zona_id'] ?? null;
    
    if (!$formato_id || !$equipo_id || !$zona_id) {
        throw new Exception('Datos incompletos. Se requiere formato_id, equipo_id y zona_id');
    }
    
    // Verificar que la zona pertenece al formato
    $stmt = $db->prepare("SELECT id FROM zonas WHERE id = ? AND formato_id = ?");
    $stmt->execute([$zona_id, $formato_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Zona inválida o no pertenece a este formato');
    }
    
    // Obtener límite de equipos por zona
    $stmt = $db->prepare("SELECT equipos_por_zona FROM campeonatos_formato WHERE id = ?");
    $stmt->execute([$formato_id]);
    $formato = $stmt->fetch();
    
    if (!$formato) {
        throw new Exception('Formato no encontrado');
    }
    
    // Verificar si la zona ya tiene el máximo de equipos
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM equipos_zonas WHERE zona_id = ?");
    $stmt->execute([$zona_id]);
    $count = $stmt->fetch()['total'];
    
    if ($count >= $formato['equipos_por_zona']) {
        throw new Exception('La zona ya tiene el máximo de equipos permitidos (' . $formato['equipos_por_zona'] . ')');
    }
    
    // Verificar si el equipo ya está en otra zona del mismo formato
    $stmt = $db->prepare("
        SELECT z.nombre 
        FROM equipos_zonas ez
        JOIN zonas z ON ez.zona_id = z.id
        WHERE ez.equipo_id = ? AND z.formato_id = ?
    ");
    $stmt->execute([$equipo_id, $formato_id]);
    if ($zona_actual = $stmt->fetch()) {
        throw new Exception('El equipo ya está asignado a ' . $zona_actual['nombre']);
    }
    
    // Verificar que el equipo existe
    $stmt = $db->prepare("SELECT nombre FROM equipos WHERE id = ?");
    $stmt->execute([$equipo_id]);
    $equipo = $stmt->fetch();
    
    if (!$equipo) {
        throw new Exception('Equipo no encontrado');
    }
    
    // Asignar equipo a zona
    $stmt = $db->prepare("
        INSERT INTO equipos_zonas (zona_id, equipo_id, posicion)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$zona_id, $equipo_id, $count + 1]);
    
    logActivity("Equipo '{$equipo['nombre']}' (ID: $equipo_id) asignado a zona $zona_id");
    
    echo json_encode([
        'success' => true,
        'message' => 'Equipo asignado correctamente'
    ]);
    
} catch (Exception $e) {
    error_log("Error al asignar equipo: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>