<?php
//get_equipos.php
require_once '../../config.php';
header('Content-Type: application/json');

if (!isset($_GET['categoria_id'])) {
    echo json_encode(['success' => false, 'equipos' => []]);
    exit;
}

$categoria_id = (int)$_GET['categoria_id'];
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT id, nombre 
    FROM equipos 
    WHERE categoria_id = ? AND activo = 1 
    ORDER BY nombre
");
$stmt->execute([$categoria_id]);
$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'equipos' => $equipos]);