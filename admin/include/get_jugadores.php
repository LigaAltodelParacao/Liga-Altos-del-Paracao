<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_GET['equipo_id'])) {
    echo json_encode([]);
    exit;
}

$equipo_id = (int)$_GET['equipo_id'];
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT j.id, j.apellido_nombre, j.dni
    FROM jugadores j
    WHERE j.equipo_id = ? AND j.activo = 1
    ORDER BY j.apellido_nombre
");
$stmt->execute([$equipo_id]);
$jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($jugadores);