<?php
// get_jugadores.php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$equipo_id = $_GET['equipo_id'] ?? null;
$editar_partido = isset($_GET['editar_partido']) && $_GET['editar_partido'] == '1';

if (!$equipo_id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de equipo requerido']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Si se está editando un partido finalizado, mostrar TODOS los jugadores (incluso sancionados)
    // para permitir corrección de errores. Si es un partido nuevo, excluir sancionados.
    if ($editar_partido) {
        // Mostrar todos los jugadores activos (incluyendo sancionados)
        $stmt = $db->prepare("
            SELECT j.id, j.apellido_nombre 
            FROM jugadores j
            WHERE j.equipo_id = ? 
              AND j.activo = 1
            ORDER BY j.apellido_nombre
        ");
        $stmt->execute([(int)$equipo_id]);
    } else {
        // Obtener jugadores ACTIVOS y SIN sanción activa (para partidos nuevos)
        $stmt = $db->prepare("
            SELECT j.id, j.apellido_nombre 
            FROM jugadores j
            LEFT JOIN sanciones s ON j.id = s.jugador_id AND s.activa = 1
            WHERE j.equipo_id = ? 
              AND j.activo = 1 
              AND s.id IS NULL
            ORDER BY j.apellido_nombre
        ");
        $stmt->execute([(int)$equipo_id]);
    }
    
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($jugadores);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>