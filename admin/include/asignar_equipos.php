<?php
require_once '../../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

if (!isLoggedIn() || !hasPermission('admin')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    $formato_id = $_POST['formato_id'] ?? null;
    
    if (!$formato_id || !is_numeric($formato_id)) {
        throw new Exception('ID de formato inválido');
    }
    
    $db->beginTransaction();
    
    try {
        $stmt = $db->prepare("
            SELECT cf.*, cat.id as categoria_id
            FROM campeonatos_formato cf
            JOIN campeonatos c ON cf.campeonato_id = c.id
            JOIN categorias cat ON cat.campeonato_id = c.id
            WHERE cf.id = ?
        ");
        $stmt->execute([$formato_id]);
        $formato = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$formato) {
            throw new Exception('Formato no encontrado');
        }
        
        $stmt = $db->prepare("SELECT id, nombre FROM zonas WHERE formato_id = ? ORDER BY orden");
        $stmt->execute([$formato_id]);
        $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($zonas)) {
            throw new Exception('No hay zonas configuradas');
        }
        
        $zona_ids = array_column($zonas, 'id');
        $placeholders = str_repeat('?,', count($zona_ids) - 1) . '?';
        
        $stmt = $db->prepare("DELETE FROM equipos_zonas WHERE zona_id IN ($placeholders)");
        $stmt->execute($zona_ids);
        
        $stmt = $db->prepare("
            SELECT id, nombre 
            FROM equipos 
            WHERE categoria_id = ? AND activo = 1
            ORDER BY nombre
        ");
        $stmt->execute([$formato['categoria_id']]);
        $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($equipos)) {
            throw new Exception('No hay equipos disponibles');
        }
        
        shuffle($equipos);
        
        $distribucion = [];
        $equipo_index = 0;
        
        foreach ($zonas as $index => $zona) {
            $distribucion[$zona['nombre']] = [];
            
            for ($i = 0; $i < $formato['equipos_por_zona'] && $equipo_index < count($equipos); $i++) {
                $equipo = $equipos[$equipo_index];
                
                $stmt = $db->prepare("
                    INSERT INTO equipos_zonas (equipo_id, zona_id, puntos, posicion)
                    VALUES (?, ?, 0, ?)
                ");
                $stmt->execute([$equipo['id'], $zona['id'], $i + 1]);
                
                $distribucion[$zona['nombre']][] = $equipo['nombre'];
                $equipo_index++;
            }
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Equipos distribuidos correctamente',
            'total_equipos' => $equipo_index,
            'total_zonas' => count($zonas),
            'distribucion' => $distribucion
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    error_log("Error al distribuir equipos: " . $e->getMessage());
}