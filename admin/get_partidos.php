<?php
require_once '../config.php';
header('Content-Type: application/json');

$equipo_id = $_GET['equipo_id'] ?? null;
if (!$equipo_id) { echo json_encode([]); exit; }

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT id, apellido_nombre FROM jugadores WHERE equipo_id=? AND activo=1 ORDER BY apellido_nombre");
$stmt->execute([$equipo_id]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
