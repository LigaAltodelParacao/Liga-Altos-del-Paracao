<?php
require_once '../../config.php';
header('Content-Type: application/json');

$partido_id = $_GET['id'] ?? 0;
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT p.*, f.categoria_id, f.numero_fecha
    FROM partidos p
    JOIN fechas f ON p.fecha_id = f.id
    WHERE p.id = ?
");
$stmt->execute([$partido_id]);
$partido = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($partido);
