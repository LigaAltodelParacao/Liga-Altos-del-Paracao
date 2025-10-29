<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isset($_GET['categoria_id'])) {
    echo json_encode([]);
    exit;
}

$categoria_id = (int)$_GET['categoria_id'];
$db = Database::getInstance()->getConnection();

try {
    $stmt = $db->prepare("
        SELECT f.id, f.numero_fecha, f.fecha_programada,
               COUNT(p.id) as total_partidos
        FROM fechas f
        LEFT JOIN partidos p ON f.id = p.fecha_id
        WHERE f.categoria_id = ?
        GROUP BY f.id, f.numero_fecha, f.fecha_programada
        ORDER BY f.numero_fecha
    ");
    $stmt->execute([$categoria_id]);
    $fechas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear fechas
    foreach ($fechas as &$fecha) {
        $fecha['fecha_programada'] = formatDate($fecha['fecha_programada']);
    }
    
    echo json_encode($fechas);
    
} catch (Exception $e) {
    echo json_encode([]);
}
?>
