<?php
require_once __DIR__ . '/../config.php';
if (!isLoggedIn()) {
    http_response_code(403);
    exit('Acceso denegado');
}

$db = Database::getInstance()->getConnection();
$partido_id = $_GET['partido'] ?? 0;

$stmt = $db->prepare("
    SELECT e.*, j.apellido_nombre as jugador,
        CASE 
            WHEN j.equipo_id = p.equipo_local_id THEN 'local'
            ELSE 'visitante'
        END as lado
    FROM eventos_partido e
    JOIN jugadores j ON e.jugador_id = j.id
    JOIN partidos p ON e.partido_id = p.id
    WHERE e.partido_id = ?
    ORDER BY e.minuto ASC
");
$stmt->execute([$partido_id]);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Devolver JSON
header('Content-Type: application/json');
echo json_encode($eventos);
