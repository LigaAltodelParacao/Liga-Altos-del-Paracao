<?php
require_once '../../config.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('admin')) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Validar datos requeridos
    $campeonato_id = $_POST['campeonato_id'] ?? null;
    $cantidad_zonas = (int)($_POST['cantidad_zonas'] ?? 2);
    $equipos_por_zona = (int)($_POST['equipos_por_zona'] ?? 4);
    $equipos_clasifican = (int)($_POST['equipos_clasifican'] ?? 8);
    $tipo_clasificacion = $_POST['tipo_clasificacion'] ?? '2_primeros';
    
    if (!$campeonato_id) {
        throw new Exception('Debe seleccionar un campeonato');
    }
    
    if ($cantidad_zonas < 2 || $cantidad_zonas > 8) {
        throw new Exception('La cantidad de zonas debe estar entre 2 y 8');
    }
    
    if ($equipos_por_zona < 2) {
        throw new Exception('Debe haber al menos 2 equipos por zona');
    }
    
    // Checkboxes de fases eliminatorias
    $tiene_octavos = isset($_POST['tiene_octavos']) ? 1 : 0;
    $tiene_cuartos = isset($_POST['tiene_cuartos']) ? 1 : 0;
    $tiene_semifinal = 1; // Siempre incluida
    $tiene_tercer_puesto = isset($_POST['tiene_tercer_puesto']) ? 1 : 0;
    
    $db->beginTransaction();
    
    // Insertar formato con el tipo de clasificación
    $stmt = $db->prepare("
        INSERT INTO campeonatos_formato 
        (campeonato_id, tipo_formato, cantidad_zonas, equipos_por_zona, equipos_clasifican,
         tiene_octavos, tiene_cuartos, tiene_semifinal, tiene_tercer_puesto, tipo_clasificacion)
        VALUES (?, 'mixto', ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $campeonato_id,
        $cantidad_zonas,
        $equipos_por_zona,
        $equipos_clasifican,
        $tiene_octavos,
        $tiene_cuartos,
        $tiene_semifinal,
        $tiene_tercer_puesto,
        $tipo_clasificacion
    ]);
    
    $formato_id = $db->lastInsertId();
    
    // Crear las zonas (A, B, C, etc.)
    $stmt_zona = $db->prepare("
        INSERT INTO zonas (formato_id, nombre, orden)
        VALUES (?, ?, ?)
    ");
    
    for ($i = 1; $i <= $cantidad_zonas; $i++) {
        $nombre_zona = 'Zona ' . chr(64 + $i); // A=65, B=66, etc.
        $stmt_zona->execute([$formato_id, $nombre_zona, $i]);
    }
    
    // Crear las fases eliminatorias
    // IMPORTANTE: Las fases se crean con activa=1 y generada=0
    // Los partidos se generarán después cuando se completen los partidos de grupos
    $stmt_fase = $db->prepare("
        INSERT INTO fases_eliminatorias (formato_id, nombre, orden, activa, generada)
        VALUES (?, ?, ?, 1, 0)
    ");
    
    $orden = 1;
    
    if ($tiene_octavos) {
        $stmt_fase->execute([$formato_id, 'octavos', $orden++]);
    }
    if ($tiene_cuartos) {
        $stmt_fase->execute([$formato_id, 'cuartos', $orden++]);
    }
    // Semifinal siempre
    $stmt_fase->execute([$formato_id, 'semifinal', $orden++]);
    
    // Final siempre
    $stmt_fase->execute([$formato_id, 'final', $orden++]);
    
    if ($tiene_tercer_puesto) {
        $stmt_fase->execute([$formato_id, 'tercer_puesto', $orden]);
    }
    
    $db->commit();
    
    logActivity("Formato de campeonato creado: ID $formato_id - Zonas: $cantidad_zonas - Clasifican: $equipos_clasifican ($tipo_clasificacion)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Formato creado exitosamente',
        'formato_id' => $formato_id
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Error al guardar formato: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>