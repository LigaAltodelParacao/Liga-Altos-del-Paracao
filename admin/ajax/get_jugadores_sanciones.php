<?php
require_once '../config.php';

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
    
    // Obtener jugadores activos
    $sql = "
        SELECT j.id, j.apellido_nombre, j.dni, e.nombre as equipo, c.nombre as categoria
        FROM jugadores j
        JOIN equipos e ON j.equipo_id = e.id
        JOIN categorias c ON e.categoria_id = c.id
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

    $sql .= " ORDER BY j.apellido_nombre";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Para cada jugador, calcular sanciones y amarillas/rojas
    foreach ($jugadores as &$j) {
        $jugador_id = $j['id'];

        // Traer eventos de tarjetas
        $stmtEv = $db->prepare("
            SELECT tipo_evento, partido_id
            FROM eventos_partido
            WHERE jugador_id = ? AND tipo_evento IN ('amarilla','roja')
            ORDER BY partido_id ASC, id ASC
        ");
        $stmtEv->execute([$jugador_id]);
        $eventos = $stmtEv->fetchAll(PDO::FETCH_ASSOC);

        $amarillas_acumuladas = 0;
        $sancion_automatica = 0;
        $partidos_tarjeta = [];

        foreach ($eventos as $ev) {
            if ($ev['tipo_evento'] === 'amarilla') {
                // Contamos amarillas por partido
                if (!isset($partidos_tarjeta[$ev['partido_id']])) {
                    $partidos_tarjeta[$ev['partido_id']] = ['amarillas' => 0, 'rojas' => 0];
                }
                $partidos_tarjeta[$ev['partido_id']]['amarillas']++;
            } elseif ($ev['tipo_evento'] === 'roja') {
                // Tarjeta roja directa: ignorar para sanción automática
            }
        }

        // Calcular sanciones automáticas
        foreach ($partidos_tarjeta as $p) {
            if ($p['amarillas'] >= 2) {
                // Doble amarilla en el mismo partido -> contar como 1 roja y 1 fecha
                $sancion_automatica++;
                $amarillas_acumuladas += 2; // sumar a acumuladas
            } else {
                $amarillas_acumuladas += $p['amarillas'];
            }
        }

        // Sanción por acumulación de 4 amarillas
        $sancion_automatica += intdiv($amarillas_acumuladas, 4);

        // Obtener sanciones manuales activas aplicadas por admin
        $stmtS = $db->prepare("
            SELECT SUM(partidos_suspension - partidos_cumplidos) as sancion_manual
            FROM sanciones
            WHERE jugador_id = ? AND activa = 1
        ");
        $stmtS->execute([$jugador_id]);
        $sancion_manual = (int)($stmtS->fetchColumn() ?? 0);

        $j['amarillas_acumuladas'] = $amarillas_acumuladas;
        $j['sancion_restante'] = $sancion_automatica; // automáticas
        $j['sancion_manual'] = $sancion_manual;
    }

    echo json_encode($jugadores);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error del servidor: ' . $e->getMessage()]);
}
?>
