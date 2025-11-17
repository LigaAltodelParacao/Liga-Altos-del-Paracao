<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/funciones_torneos_zonas.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

$formato_id = $_GET['formato_id'] ?? null;

if (!$formato_id) {
    redirect('torneos_zonas.php');
}

// Incluir funciones de desempate
require_once __DIR__ . '/include/desempate_functions.php';

// Verificar que todos los partidos estén finalizados
if (!todosPartidosGruposFinalizados($formato_id, $db)) {
    $_SESSION['error'] = 'Aún hay partidos de grupos pendientes. Debe finalizar todos los partidos antes de generar las eliminatorias.';
    header("Location: control_partidos_zonas.php?formato_id={$formato_id}");
    exit;
}

// Recalcular tablas para detectar empates pendientes
$stmt = $db->prepare("SELECT id FROM zonas WHERE formato_id = ?");
$stmt->execute([$formato_id]);
$zonas = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($zonas as $zona_id) {
    calcularTablaPosicionesConDesempate($zona_id, $db);
}

// Verificar si hay empates pendientes
if (hayEmpatesPendientes($formato_id, $db)) {
    $empates = obtenerEmpatesPendientes($formato_id, $db);
    $_SESSION['error'] = 'Hay ' . count($empates) . ' empate(s) pendiente(s) de resolución por sorteo. ';
    $_SESSION['error'] .= 'Debes resolver todos los empates antes de generar las fases eliminatorias. ';
    $_SESSION['error'] .= '<a href="resolver_empates.php?formato_id=' . $formato_id . '">Ir a Resolver Empates</a>';
    header("Location: resolver_empates.php?formato_id={$formato_id}");
    exit;
}

// Intentar generar eliminatorias
try {
    generarFixtureEliminatorias($formato_id, $db);
    $_SESSION['message'] = '¡Fixture eliminatorias generado exitosamente!';
    header("Location: control_eliminatorias.php?formato_id={$formato_id}");
    exit;
} catch (Exception $e) {
    $_SESSION['error'] = 'Error al generar eliminatorias: ' . $e->getMessage();
    if (strpos($e->getMessage(), 'empate') !== false) {
        header("Location: resolver_empates.php?formato_id={$formato_id}");
    } else {
        header("Location: control_partidos_zonas.php?formato_id={$formato_id}");
    }
    exit;
}

