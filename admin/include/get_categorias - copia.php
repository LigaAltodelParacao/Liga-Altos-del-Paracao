<?php
require_once '../../config.php';
header('Content-Type: application/json');

if (isset($_GET['campeonato_id'])) {
    $campeonato_id = (int)$_GET['campeonato_id'];
    $db = Database::getInstance()->getConnection();
    
    $stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
    $stmt->execute([$campeonato_id]);
    $categorias = $stmt->fetchAll();
    
    echo json_encode(['success' => true, 'categorias' => $categorias]);
} else {
    echo json_encode(['success' => false]);
}
?>