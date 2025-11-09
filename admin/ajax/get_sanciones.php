<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$campeonato_id = $_GET['campeonato_id'] ?? null;
$categoria_id = $_GET['categoria_id'] ?? null;

try {
    $db = Database::getInstance()->getConnection();
    
    $sql = "
        SELECT j.id, j.apellido_nombre, j.dni, e.nombre as equipo, c.nombre as categoria,
               COUNT(s.id) as sanciones_activas
        FROM jugadores j
        JOIN equipos e ON j.equipo_id = e.id
        JOIN categorias c ON e.categoria_id = c.id
        LEFT JOIN sanciones s ON j.id = s.jugador_id AND s.activa = 1
        WHERE j.activo = 1
    ";
    
    $params = [];
    
    if ($campeonato_id) {
        $sql .= " AND c.campeonato_id = ?";
        $params[] = $campeonato_id;
    }
    
    if ($categoria_id) {
        $sql .= " AND e.categoria_id = ?";
        $params[] = $categoria_id;
    }
    
    $sql .= " GROUP BY j.id ORDER BY j.apellido_nombre";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($jugadores);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>