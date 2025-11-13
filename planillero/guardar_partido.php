<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('planillero')) {
    http_response_code(403);
    exit(json_encode(['error' => 'Acceso denegado']));
}

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents('php://input'), true);

$partido_id = (int)($data['partido_id'] ?? 0);
if (!$partido_id) exit(json_encode(['error' => 'Partido no válido']));

try {
    $db->beginTransaction();

    // Borrar eventos existentes
    $db->prepare("DELETE FROM eventos_partido WHERE partido_id = ?")->execute([$partido_id]);

    $goles_local = 0;
    $goles_visitante = 0;

    // Guardar goles
    foreach ($data['goles'] ?? [] as $gol) {
        $equipo_id = $gol['lado'] === 'local' ? 
            (int)$_GET['equipo_local_id'] : (int)$_GET['equipo_visitante_id'];
        $db->prepare("INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto) VALUES (?, ?, 'gol', 0)")
           ->execute([$partido_id, $gol['jugador_id']]);
        if ($gol['lado'] === 'local') $goles_local++; else $goles_visitante++;
    }

    // Guardar tarjetas
    foreach ($data['tarjetas'] ?? [] as $tarjeta) {
        $db->prepare("INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto) VALUES (?, ?, ?, 0)")
           ->execute([$partido_id, $tarjeta['jugador_id'], $tarjeta['tipo']]);
    }

    // Actualizar marcador
    $db->prepare("UPDATE partidos SET goles_local = ?, goles_visitante = ? WHERE id = ?")
       ->execute([$goles_local, $goles_visitante, $partido_id]);

    // Actualizar observaciones
    $db->prepare("UPDATE partidos SET observaciones = ? WHERE id = ?")
       ->execute([$data['observaciones'] ?? '', $partido_id]);

    // Si se finaliza, actualizar estado
    if (!empty($data['finalizar'])) {
        $db->prepare("UPDATE partidos SET estado = 'finalizado', tiempo_actual = 'finalizado' WHERE id = ?")
           ->execute([$partido_id]);
    }

    $db->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $db->rollback();
    echo json_encode(['error' => $e->getMessage()]);
}
?>