<?php
require_once '../config.php';
if (!isLoggedIn() || !hasPermission('planillero')) {
    http_response_code(403);
    exit('Acceso denegado');
}

$db = Database::getInstance()->getConnection();
$data = json_decode(file_get_contents('php://input'), true);

$partido_id = $data['partido_id'] ?? 0;

// Guardar evento de gol o tarjeta
if(isset($data['tipo'])){
    $tipo = $data['tipo']; // gol, amarilla, roja
    $jugador = $data['jugador'];
    $minuto = $data['minuto'];
    $lado = $data['lado'] ?? null;

    // Buscar jugador por nombre o id simplificado
    $stmt = $db->prepare("SELECT id, apellido_nombre FROM jugadores WHERE id=? OR apellido_nombre=? LIMIT 1");
    $stmt->execute([$jugador,$jugador]);
    $jug = $stmt->fetch();
    if(!$jug) exit(json_encode(['error'=>'Jugador no encontrado']));

    $stmt = $db->prepare("INSERT INTO eventos_partido (partido_id,jugador_id,tipo_evento,minuto) VALUES (?,?,?,?)");
    $stmt->execute([$partido_id,$jug['id'],$tipo,$minuto]);

    // Actualizar goles si es gol
    if($tipo==='gol'){
        if($lado==='local'){
            $db->prepare("UPDATE partidos SET goles_local = goles_local + 1, minuto_actual = ? WHERE id = ?")
               ->execute([$minuto,$partido_id]);
        } else {
            $db->prepare("UPDATE partidos SET goles_visitante = goles_visitante + 1, minuto_actual = ? WHERE id = ?")
               ->execute([$minuto,$partido_id]);
        }
    }
}

// Guardar observaciones si vienen
if(isset($data['observaciones'])){
    $obs = $data['observaciones'];
    $stmt = $db->prepare("UPDATE partidos SET observaciones = ? WHERE id = ?");
    $stmt->execute([$obs,$partido_id]);
}

// Actualizar estado del partido
if(isset($data['estado']) && isset($data['tiempo'])){
    $estado = $data['estado'];
    $tiempo = $data['tiempo'];
    $minuto = $data['minuto'] ?? 0;

    $stmt = $db->prepare("UPDATE partidos SET estado=?, tiempo_actual=?, minuto_actual=? WHERE id=?");
    $stmt->execute([$estado,$tiempo,$minuto,$partido_id]);
}

echo json_encode(['success'=>true]);
