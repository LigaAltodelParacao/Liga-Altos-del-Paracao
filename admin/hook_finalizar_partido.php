<?php
/**
 * Este código debe agregarse en el archivo donde se finalizan los partidos
 * Probablemente en admin/resultados.php o admin/cargar_resultado.php
 * 
 * Agregar después de que se actualiza el estado del partido a 'finalizado'
 */

// Incluir las funciones de sanciones
require_once '../include/sanciones_functions.php';
require_once __DIR__ . '/funciones_torneos_zonas.php';

// Ejemplo de uso cuando se finaliza un partido:
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['finalizar_partido'])) {
    $partido_id = (int)$_POST['partido_id'];
    
    // Tu código existente para finalizar el partido...
    $stmt = $db->prepare("
        UPDATE partidos 
        SET estado = 'finalizado', 
            finalizado_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$partido_id]);
    
    // NUEVO: Cumplir automáticamente las sanciones
    if (cumplirSancionesAutomaticas($partido_id, $db)) {
        $message .= ' Las sanciones se actualizaron automáticamente.';
    }

    $resultado_auto = procesarPostFinalizacionPartido($partido_id, $db);
    if (!empty($resultado_auto['messages'])) {
        $message .= ' ' . implode(' ', $resultado_auto['messages']);
    }
}

/**
 * ALTERNATIVA: Si quieres ejecutar esto para todos los partidos finalizados
 * que no han procesado sanciones (por si hay partidos viejos)
 */
function procesarSancionesPendientes($db) {
    // Obtener todos los partidos finalizados
    $stmt = $db->query("
        SELECT id FROM partidos 
        WHERE estado = 'finalizado' 
        ORDER BY fecha_partido ASC, hora_partido ASC
    ");
    $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $procesados = 0;
    foreach ($partidos as $partido) {
        if (cumplirSancionesAutomaticas($partido['id'], $db)) {
            $procesados++;
        }
    }
    
    return $procesados;
}

// Si quieres ejecutar esto una sola vez para procesar todo el historial:
// $procesados = procesarSancionesPendientes($db);
// echo "Se procesaron $procesados partidos";

?>