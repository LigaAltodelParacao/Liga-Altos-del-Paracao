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

// Verificar que todos los partidos estén finalizados
if (!todosPartidosGruposFinalizados($formato_id, $db)) {
    $_SESSION['error'] = 'Aún hay partidos de grupos pendientes. Debe finalizar todos los partidos antes de generar las eliminatorias.';
    header("Location: control_partidos_zonas.php?formato_id={$formato_id}");
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
    header("Location: control_partidos_zonas.php?formato_id={$formato_id}");
    exit;
}

