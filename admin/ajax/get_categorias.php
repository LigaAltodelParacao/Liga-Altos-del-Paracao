<?php
// Este archivo debe estar en: admin/ajax/get_categorias.php
require_once '../../config.php';
header('Content-Type: application/json');

// Verificar si hay sesión iniciada
if (!isset($_GET['campeonato_id'])) {
    echo json_encode(['success' => false, 'categorias' => []]);
    exit;
}

$campeonato_id = (int)$_GET['campeonato_id'];
$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("
        SELECT id, nombre 
        FROM categorias 
        WHERE campeonato_id = ? AND activa = 1 
        ORDER BY nombre
    ");
    $stmt->execute([$campeonato_id]);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'categorias' => $categorias
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'categorias' => [],
        'error' => $e->getMessage()
    ]);
}