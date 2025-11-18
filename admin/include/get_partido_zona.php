<?php
require_once '../../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$partido_id = $_GET['partido_id'] ?? null;

if (!$partido_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Partido ID requerido']);
    exit;
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT 
        p.*,
        el.nombre as equipo_local,
        ev.nombre as equipo_visitante
    FROM partidos p
    JOIN equipos el ON p.equipo_local_id = el.id
    JOIN equipos ev ON p.equipo_visitante_id = ev.id
    WHERE p.id = ? AND (p.tipo_torneo = 'zona' OR p.tipo_torneo = 'eliminatoria')
");
$stmt->execute([$partido_id]);
$partido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partido) {
    http_response_code(404);
    echo json_encode(['error' => 'Partido no encontrado']);
    exit;
}

header('Content-Type: application/json');
echo json_encode($partido);

