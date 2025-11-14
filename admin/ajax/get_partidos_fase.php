<?php
require_once '../../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$fase_id = $_GET['fase_id'] ?? null;

if (!$fase_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Fase ID requerido']);
    exit;
}

$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT 
        p.id,
        p.numero_llave,
        p.equipo_local_id,
        p.equipo_visitante_id,
        p.origen_local,
        p.origen_visitante,
        p.goles_local,
        p.goles_visitante,
        p.goles_local_penales,
        p.goles_visitante_penales,
        p.estado,
        el.nombre as equipo_local,
        ev.nombre as equipo_visitante
    FROM partidos p
    LEFT JOIN equipos el ON p.equipo_local_id = el.id
    LEFT JOIN equipos ev ON p.equipo_visitante_id = ev.id
    WHERE p.fase_eliminatoria_id = ? AND p.tipo_torneo = 'eliminatoria'
    ORDER BY p.numero_llave
");
$stmt->execute([$fase_id]);
$partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode(['partidos' => $partidos]);

