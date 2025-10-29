<?php
//get_categoria
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_GET['campeonato_id'])) {
    echo json_encode([]);
    exit;
}

$campeonato_id = (int)$_GET['campeonato_id'];
$db = Database::getInstance()->getConnection();

$stmt = $db->prepare("
    SELECT id, nombre 
    FROM categorias 
    WHERE campeonato_id = ? AND activa = 1 
    ORDER BY nombre
");
$stmt->execute([$campeonato_id]);
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($categorias);