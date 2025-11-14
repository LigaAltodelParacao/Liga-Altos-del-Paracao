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
        el.id as equipo_local_id,
        ev.nombre as equipo_visitante,
        ev.id as equipo_visitante_id
    FROM partidos p
    LEFT JOIN equipos el ON p.equipo_local_id = el.id
    LEFT JOIN equipos ev ON p.equipo_visitante_id = ev.id
    WHERE p.id = ? AND p.tipo_torneo = 'eliminatoria'
");
$stmt->execute([$partido_id]);
$partido = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$partido) {
    http_response_code(404);
    echo json_encode(['error' => 'Partido no encontrado']);
    exit;
}

// Obtener penales por jugador si existen
$stmt = $db->prepare("
    SELECT 
        pp.*,
        j.apellido_nombre as jugador_nombre
    FROM penales_partido pp
    LEFT JOIN jugadores j ON pp.jugador_id = j.id
    WHERE pp.partido_id = ?
    ORDER BY pp.equipo_id, pp.numero_penal
");
$stmt->execute([$partido_id]);
$penales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Organizar penales por equipo
$penales_organizados = ['local' => [], 'visitante' => []];
foreach ($penales as $penal) {
    if ($penal['equipo_id'] == $partido['equipo_local_id']) {
        $penales_organizados['local'][] = [
            'jugador_id' => (int)$penal['jugador_id'],
            'numero_penal' => (int)$penal['numero_penal'],
            'convertido' => (bool)$penal['convertido']
        ];
    } else {
        $penales_organizados['visitante'][] = [
            'jugador_id' => (int)$penal['jugador_id'],
            'numero_penal' => (int)$penal['numero_penal'],
            'convertido' => (bool)$penal['convertido']
        ];
    }
}

$partido['penales_data'] = $penales_organizados;

header('Content-Type: application/json');
echo json_encode($partido);

