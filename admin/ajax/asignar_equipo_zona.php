<?php
declare(strict_types=1);
require_once '../../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

$formato_id = filter_input(INPUT_POST, 'formato_id', FILTER_VALIDATE_INT);
$equipo_id = filter_input(INPUT_POST, 'equipo_id', FILTER_VALIDATE_INT);
$zona_id = filter_input(INPUT_POST, 'zona_id', FILTER_VALIDATE_INT);

if (!$formato_id || !$equipo_id || !$zona_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();

    // 1. Validar que la zona pertenece al formato (ESTA ES LA CLAVE DEL ERROR)
    $stmt = $db->prepare("SELECT COUNT(*) FROM zonas WHERE id = ? AND formato_id = ?");
    $stmt->execute([$zona_id, $formato_id]);
    if ($stmt->fetchColumn() == 0) {
        http_response_code(400);
        // Este es el mensaje de error que estabas recibiendo.
        echo json_encode(['success' => false, 'message' => 'Zona no válida para este formato.']);
        exit;
    }

    // 2. Validar que el equipo no esté ya asignado a otra zona de este formato
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM equipos_zonas 
        WHERE equipo_id = ? AND zona_id IN (SELECT id FROM zonas WHERE formato_id = ?)
    ");
    $stmt->execute([$equipo_id, $formato_id]);
    if ($stmt->fetchColumn() > 0) {
        http_response_code(409); // 409 Conflict
        echo json_encode(['success' => false, 'message' => 'Este equipo ya está asignado a una zona.']);
        exit;
    }

    // 3. Validar que la zona no esté llena
    $stmt = $db->prepare("SELECT equipos_por_zona FROM campeonatos_formato WHERE id = ?");
    $stmt->execute([$formato_id]);
    $equipos_por_zona = $stmt->fetchColumn();

    $stmt = $db->prepare("SELECT COUNT(*) FROM equipos_zonas WHERE zona_id = ?");
    $stmt->execute([$zona_id]);
    $equipos_actuales = $stmt->fetchColumn();

    if ($equipos_actuales >= $equipos_por_zona) {
        http_response_code(409); // 409 Conflict
        echo json_encode(['success' => false, 'message' => 'La zona ya está llena.']);
        exit;
    }
    
    // 4. Si todo es correcto, insertar
    $stmt = $db->prepare("INSERT INTO equipos_zonas (equipo_id, zona_id) VALUES (?, ?)");
    $stmt->execute([$equipo_id, $zona_id]);
    
    echo json_encode(['success' => true, 'message' => 'Equipo asignado correctamente.']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>