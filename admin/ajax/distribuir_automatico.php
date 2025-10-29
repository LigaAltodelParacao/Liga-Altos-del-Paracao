<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $formato_id = $_POST['formato_id'] ?? null;
    
    if (!$formato_id) {
        throw new Exception('Formato no especificado');
    }
    
    $db->beginTransaction();
    
    // Obtener información del formato
    $stmt = $db->prepare("
        SELECT cf.*, cat.id as categoria_id
        FROM campeonatos_formato cf
        JOIN campeonatos c ON cf.campeonato_id = c.id
        JOIN categorias cat ON cat.campeonato_id = c.id
        WHERE cf.id = ?
        LIMIT 1
    ");
    $stmt->execute([$formato_id]);
    $formato = $stmt->fetch();
    
    if (!$formato) {
        throw new Exception('Formato no encontrado');
    }
    
    // Obtener zonas
    $stmt = $db->prepare("SELECT id, nombre FROM zonas WHERE formato_id = ? ORDER BY orden");
    $stmt->execute([$formato_id]);
    $zonas = $stmt->fetchAll();
    
    if (empty($zonas)) {
        throw new Exception('No hay zonas configuradas');
    }
    
    // Limpiar asignaciones anteriores
    $stmt = $db->prepare("
        DELETE ez FROM equipos_zonas ez
        JOIN zonas z ON ez.zona_id = z.id
        WHERE z.formato_id = ?
    ");
    $stmt->execute([$formato_id]);
    
    // Obtener equipos de la categoría
    $stmt = $db->prepare("
        SELECT id, nombre FROM equipos 
        WHERE categoria_id = ? AND activo = 1 
        ORDER BY nombre
    ");
    $stmt->execute([$formato['categoria_id']]);
    $equipos = $stmt->fetchAll();
    
    if (empty($equipos)) {
        throw new Exception('No hay equipos disponibles en esta categoría para distribuir');
    }
    
    $total_equipos_necesarios = $formato['cantidad_zonas'] * $formato['equipos_por_zona'];
    
    if (count($equipos) < $total_equipos_necesarios) {
        throw new Exception("Se necesitan al menos {$total_equipos_necesarios} equipos. Solo hay " . count($equipos) . " disponibles en la categoría.");
    }
    
    // Mezclar equipos aleatoriamente
    shuffle($equipos);
    
    // Distribuir equipos equitativamente
    $stmt_insert = $db->prepare("
        INSERT INTO equipos_zonas (zona_id, equipo_id, posicion)
        VALUES (?, ?, ?)
    ");
    
    $distribucion = [];
    $equipo_index = 0;
    
    // Distribuir equipos de forma circular
    for ($i = 0; $i < $formato['equipos_por_zona']; $i++) {
        foreach ($zonas as $zona) {
            if ($equipo_index >= count($equipos)) {
                break 2; // Salir de ambos loops
            }
            
            $equipo = $equipos[$equipo_index];
            $stmt_insert->execute([$zona['id'], $equipo['id'], $i + 1]);
            
            if (!isset($distribucion[$zona['nombre']])) {
                $distribucion[$zona['nombre']] = [];
            }
            $distribucion[$zona['nombre']][] = $equipo['nombre'];
            
            $equipo_index++;
        }
    }
    
    $db->commit();
    
    logActivity("Equipos distribuidos automáticamente para formato $formato_id: $equipo_index equipos asignados");
    
    echo json_encode([
        'success' => true,
        'message' => 'Equipos distribuidos correctamente',
        'total_equipos' => $equipo_index,
        'total_zonas' => count($zonas),
        'distribucion' => $distribucion
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error al distribuir equipos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>