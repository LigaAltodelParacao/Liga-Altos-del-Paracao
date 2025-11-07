<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once '../config.php';
if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}
$db = Database::getInstance()->getConnection();
$message = '';
$error = '';
$unassigned = [];

// Filtros GET
$campeonato_id = $_GET['campeonato_id'] ?? null;
$categoria_id = $_GET['categoria_id'] ?? null;
$formato_id = $_GET['formato_id'] ?? null;
$zonas_ids = $_GET['zonas_ids'] ?? [];
$fecha_id = $_GET['fecha_id'] ?? null;
$fecha_numero = $_GET['fecha_numero'] ?? null;
$temporada = $_GET['temporada'] ?? null;

// Obtener campeonatos activos
$campeonatos = $db->query("SELECT * FROM campeonatos WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
$categorias = [];
$formatos = [];
$zonas = [];
$fechas = [];
$partidos = [];
$tiene_zonas = false;
$tipo_campeonato = 'comun';

// Cargar categorías si hay campeonato
if ($campeonato_id) {
    $stmt = $db->prepare("SELECT * FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
    $stmt->execute([$campeonato_id]);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Verificar si tiene formato de zonas
    $stmt = $db->prepare("SELECT COUNT(*) FROM campeonatos_formato WHERE campeonato_id = ?");
    $stmt->execute([$campeonato_id]);
    $tiene_zonas = $stmt->fetchColumn() > 0;
    if ($tiene_zonas) {
        $tipo_campeonato = 'zonas';
        $stmt = $db->prepare("SELECT * FROM campeonatos_formato WHERE campeonato_id = ?");
        $stmt->execute([$campeonato_id]);
        $formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Si es campeonato de zonas
if ($tipo_campeonato === 'zonas' && $formato_id) {
    // Cargar zonas
    $stmt = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
    $stmt->execute([$formato_id]);
    $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Obtener jornadas disponibles (números únicos) de las zonas seleccionadas
    if (!empty($zonas_ids)) {
        $placeholders = str_repeat('?,', count($zonas_ids) - 1) . '?';
        $stmt = $db->prepare("SELECT DISTINCT jornada_zona FROM partidos WHERE zona_id IN ($placeholders) AND tipo_torneo = 'zona' ORDER BY jornada_zona");
        $stmt->execute($zonas_ids);
        $fechas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        // Cargar partidos si hay jornada seleccionada
        if ($fecha_numero !== null) {
            $stmt = $db->prepare("
                SELECT p.id, el.nombre AS local, ev.nombre AS visitante, 
                       p.cancha_id, p.hora_partido, p.fecha_partido, p.estado, z.nombre AS zona_nombre
                FROM partidos p
                JOIN equipos el ON p.equipo_local_id = el.id
                JOIN equipos ev ON p.equipo_visitante_id = ev.id
                JOIN zonas z ON p.zona_id = z.id
                WHERE p.zona_id IN ($placeholders) AND p.jornada_zona = ? AND p.tipo_torneo = 'zona'
                ORDER BY z.nombre, p.id
            ");
            $params = array_merge($zonas_ids, [$fecha_numero]);
            $stmt->execute($params);
            $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} else if ($tipo_campeonato === 'comun' && $categoria_id) {
    // Campeonato común
    $stmt = $db->prepare("SELECT * FROM fechas WHERE categoria_id = ? AND activa = 1 ORDER BY numero_fecha");
    $stmt->execute([$categoria_id]);
    $fechas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($fecha_id) {
        $stmt = $db->prepare("
            SELECT p.id, el.nombre AS local, ev.nombre AS visitante,
                   p.cancha_id, p.hora_partido, p.fecha_partido, p.estado
            FROM partidos p
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            WHERE p.fecha_id = ?
            ORDER BY p.id
        ");
        $stmt->execute([$fecha_id]);
        $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Obtener canchas activas
$canchas_all = $db->query("SELECT * FROM canchas WHERE activa = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// AJAX: Recargar solo la sección de partidos y calendario
if (isset($_GET['ajax_reload_partidos']) && $_GET['ajax_reload_partidos'] == '1') {
    // Reutilizamos toda la lógica anterior para cargar $partidos, $fecha_programada_sel, etc.

    $fecha_programada_sel = null;
    if ($tipo_campeonato === 'zonas' && !empty($zonas_ids) && $fecha_numero !== null) {
        $fs = $db->prepare("SELECT fecha_partido FROM partidos WHERE zona_id = ? AND jornada_zona = ? AND tipo_torneo = 'zona' LIMIT 1");
        $fs->execute([$zonas_ids[0], $fecha_numero]);
        $fecha_programada_sel = $fs->fetchColumn();
    } elseif ($tipo_campeonato === 'comun' && $fecha_id) {
        $fs = $db->prepare("SELECT fecha_programada FROM fechas WHERE id = ?");
        $fs->execute([$fecha_id]);
        $fecha_programada_sel = $fs->fetchColumn();
    }

    ob_start();
    ?>
    <!-- Partidos Section -->
    <div id="partidosSection">
        <?php if (!empty($partidos)): ?>
            <!-- ASIGNACIÓN AUTOMÁTICA -->
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-magic"></i> Asignación Automática</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="autoAssignForm">
                        <input type="hidden" name="tipo_campeonato" value="<?= $tipo_campeonato ?>">
                        <input type="hidden" name="temporada" value="<?= htmlspecialchars($temporada) ?>">
                        <?php 
                        if ($tipo_campeonato === 'zonas' && !empty($zonas_ids) && $fecha_numero !== null) {
                            foreach ($zonas_ids as $zid) {
                                echo '<input type="hidden" name="zonas_ids[]" value="' . $zid . '">';
                            }
                            echo '<input type="hidden" name="fecha_numero" value="' . $fecha_numero . '">';
                        } elseif ($tipo_campeonato === 'comun' && $fecha_id) {
                            echo '<input type="hidden" name="fecha_id" value="' . $fecha_id . '">';
                        }
                        ?>
                        <input type="hidden" name="fecha_programada" value="<?= htmlspecialchars($fecha_programada_sel) ?>">
                        <div class="row">
                            <div class="col-md-12">
                                <label class="form-label">Seleccionar Canchas (sólo con horarios libres)</label>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php
                                    if ($fecha_programada_sel && $temporada) {
                                        foreach ($canchas_all as $cancha) {
                                            if (canchaTieneHorarioLibre($db, $cancha['id'], $fecha_programada_sel, $temporada)) {
                                                echo '<div class="form-check me-2">';
                                                echo '<input class="form-check-input" type="checkbox" name="canchas[]" value="' . $cancha['id'] . '" id="cancha' . $cancha['id'] . '">';
                                                echo '<label class="form-check-label" for="cancha' . $cancha['id'] . '">' . htmlspecialchars($cancha['nombre']) . '</label>';
                                                echo '</div>';
                                            }
                                        }
                                    } else {
                                        echo '<div class="alert alert-info">Selecciona fecha y temporada.</div>';
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <button type="button" id="btnAutoAssign" class="btn btn-success mt-3">
                            <i class="fas fa-magic"></i> Asignación Automática
                        </button>
                    </form>
                </div>
            </div>

            <!-- TABLA DE PARTIDOS -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Partidos - Asignación Manual</h5>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <?php if ($tipo_campeonato === 'zonas'): ?>
                                <th>Zona</th>
                                <?php endif; ?>
                                <th>Local</th>
                                <th>Visitante</th>
                                <th>Cancha</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($partidos as $p): ?>
                            <tr id="row-<?= $p['id'] ?>">
                                <td><?= $p['id'] ?></td>
                                <?php if ($tipo_campeonato === 'zonas'): ?>
                                <td><span class="badge bg-primary"><?= htmlspecialchars($p['zona_nombre']) ?></span></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($p['local']) ?></td>
                                <td><?= htmlspecialchars($p['visitante']) ?></td>
                                <td>
                                    <select class="form-select manual-cancha" data-partido="<?= $p['id'] ?>">
                                        <option value="">-- Seleccionar --</option>
                                        <?php foreach ($canchas_all as $cancha): ?>
                                            <option value="<?= $cancha['id'] ?>" <?= ($p['cancha_id'] == $cancha['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cancha['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <input type="date" class="form-control manual-fecha" value="<?= $p['fecha_partido'] ?? $fecha_programada_sel ?>" data-partido="<?= $p['id'] ?>">
                                </td>
                                <td>
                                    <input list="horarios<?= $p['id'] ?>" class="form-control manual-hora" value="<?= $p['hora_partido'] ?>" data-partido="<?= $p['id'] ?>">
                                    <datalist id="horarios<?= $p['id'] ?>">
                                        <?php
                                        if (!empty($p['cancha_id']) && $temporada) {
                                            $hh = $db->prepare("SELECT hora FROM horarios_canchas WHERE cancha_id = ? AND temporada = ? AND activa = 1 ORDER BY hora");
                                            $hh->execute([$p['cancha_id'], $temporada]);
                                            foreach ($hh->fetchAll(PDO::FETCH_COLUMN) as $opt_h) {
                                                echo "<option value=\"{$opt_h}\">";
                                            }
                                        }
                                        ?>
                                    </datalist>
                                </td>
                                <td>
                                    <button class="btn btn-primary btn-sm guardar-manual" data-partido="<?= $p['id'] ?>" data-tipo="<?= $tipo_campeonato ?>">
                                        <i class="fas fa-save"></i> Guardar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- CALENDARIO VISUAL -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="fas fa-calendar"></i> Calendario Visual (Temporada: <?= htmlspecialchars($temporada ?: '—') ?>)</h5>
                </div>
                <div class="card-body">
                    <div id="calendarContainer">
                        <?php
                        if ($fecha_programada_sel && $temporada) {
                            echo '<div class="row">';
                            foreach ($canchas_all as $cancha) {
                                echo '<div class="col-md-3">';
                                echo '<div class="card mb-3">';
                                echo '<div class="card-header">' . htmlspecialchars($cancha['nombre']) . '</div>';
                                echo '<div class="card-body p-2" style="min-height:100px;">';
                                $hstmt = $db->prepare("SELECT hora FROM horarios_canchas WHERE cancha_id = ? AND temporada = ? AND activa = 1 ORDER BY hora");
                                $hstmt->execute([$cancha['id'], $temporada]);
                                $horarios_cancha = $hstmt->fetchAll(PDO::FETCH_COLUMN);
                                $oc_stmt = $db->prepare("SELECT hora_partido FROM partidos WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido IS NOT NULL");
                                $oc_stmt->execute([$cancha['id'], $fecha_programada_sel]);
                                $ocupados_cancha = $oc_stmt->fetchAll(PDO::FETCH_COLUMN);
                                $oc_stmt_zona = $db->prepare("SELECT hora_partido FROM partidos_zona WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido IS NOT NULL");
                                $oc_stmt_zona->execute([$cancha['id'], $fecha_programada_sel]);
                                $ocupados_zona = $oc_stmt_zona->fetchAll(PDO::FETCH_COLUMN);
                                $todos_ocupados = array_merge($ocupados_cancha, $ocupados_zona);
                                foreach ($horarios_cancha as $hora) {
                                    if (in_array($hora, $todos_ocupados)) {
                                        echo "<div class='bloque ocupado'>{$hora}</div>";
                                    } else {
                                        echo "<div class='bloque disponible'>{$hora}</div>";
                                    }
                                }
                                echo '</div></div></div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="alert alert-info">Selecciona fecha y temporada para mostrar el calendario.</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> No hay partidos para mostrar.
            </div>
        <?php endif; ?>
    </div>
    <?php
    $output = ob_get_clean();
    echo $output;
    exit;
}

// AJAX: Calendario visual
if (isset($_GET['ajax_calendar']) && $_GET['ajax_calendar'] == '1') {
    $fecha_programada_ajax = $_GET['fecha_programada'] ?? null;
    $temporada_ajax = $_GET['temporada'] ?? null;
    if (!$fecha_programada_ajax || !$temporada_ajax) {
        echo '<div class="alert alert-info">Selecciona fecha y temporada para ver el calendario.</div>';
        exit;
    }
    echo '<div class="row">';
    foreach ($canchas_all as $cancha) {
        echo '<div class="col-md-3">';
        echo '<div class="card mb-3">';
        echo '<div class="card-header">' . htmlspecialchars($cancha['nombre']) . '</div>';
        echo '<div class="card-body p-2" style="min-height:100px;">';
        $hstmt = $db->prepare("SELECT hora FROM horarios_canchas WHERE cancha_id = ? AND temporada = ? AND activa = 1 ORDER BY hora");
        $hstmt->execute([$cancha['id'], $temporada_ajax]);
        $horarios_cancha = $hstmt->fetchAll(PDO::FETCH_COLUMN);
        $oc_stmt = $db->prepare("SELECT hora_partido FROM partidos WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido IS NOT NULL");
        $oc_stmt->execute([$cancha['id'], $fecha_programada_ajax]);
        $ocupados_cancha = $oc_stmt->fetchAll(PDO::FETCH_COLUMN);
        $oc_stmt_zona = $db->prepare("SELECT hora_partido FROM partidos_zona WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido IS NOT NULL");
        $oc_stmt_zona->execute([$cancha['id'], $fecha_programada_ajax]);
        $ocupados_zona = $oc_stmt_zona->fetchAll(PDO::FETCH_COLUMN);
        $ocupados_cancha = array_merge($ocupados_cancha, $ocupados_zona);
        foreach ($horarios_cancha as $hora) {
            if (in_array($hora, $ocupados_cancha)) {
                echo "<div class='bloque ocupado'>{$hora}</div>";
            } else {
                echo "<div class='bloque disponible'>{$hora}</div>";
            }
        }
        echo '</div></div></div>';
    }
    echo '</div>';
    exit;
}

// AJAX: Obtener horarios de cancha
if (isset($_GET['ajax_horarios']) && $_GET['ajax_horarios'] == '1') {
    header('Content-Type: application/json; charset=utf-8');
    $cancha_id_ajax = $_GET['cancha_id'] ?? null;
    $temporada_ajax = $_GET['temporada'] ?? null;
    if (!$cancha_id_ajax || !$temporada_ajax) {
        echo json_encode(['success' => false]);
        exit;
    }
    $stmt = $db->prepare("SELECT hora FROM horarios_canchas WHERE cancha_id = ? AND temporada = ? AND activa = 1 ORDER BY hora");
    $stmt->execute([$cancha_id_ajax, $temporada_ajax]);
    $horarios_arr = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['success' => true, 'horarios' => $horarios_arr]);
    exit;
}

// POST: Asignación manual (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_manual']) && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    $partido_id = intval($_POST['partido_id']);
    $cancha_id = intval($_POST['cancha_id']) ?: null;
    $hora = trim($_POST['hora']) ?: null;
    $fecha_part = $_POST['fecha_partido'] ?? null;
    $tipo = $_POST['tipo_partido'] ?? 'comun';
    if (!$partido_id || !$cancha_id || !$hora || !$fecha_part) {
        echo json_encode(['success' => false, 'error' => 'Datos incompletos.']);
        exit;
    }
    $chk = $db->prepare("SELECT COUNT(*) FROM partidos WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido = ? AND id <> ?");
    $chk->execute([$cancha_id, $fecha_part, $hora, $partido_id]);
    $ocupado_comun = $chk->fetchColumn() > 0;
    $chk_zona = $db->prepare("SELECT COUNT(*) FROM partidos WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido = ? AND id <> ? AND tipo_torneo = 'zona'");
    $chk_zona->execute([$cancha_id, $fecha_part, $hora, $partido_id]);
    $ocupado_zona = $chk_zona->fetchColumn() > 0;
    if ($ocupado_comun || $ocupado_zona) {
        echo json_encode(['success' => false, 'error' => 'La cancha ya está ocupada en ese día y horario.']);
        exit;
    }
    // Usar tabla unificada partidos para ambos tipos
    $up = $db->prepare("UPDATE partidos SET cancha_id = ?, hora_partido = ?, fecha_partido = ? WHERE id = ?");
    $up->execute([$cancha_id, $hora, $fecha_part, $partido_id]);
    $cn = $db->prepare("SELECT nombre FROM canchas WHERE id = ?");
    $cn->execute([$cancha_id]);
    $cancha_nombre = $cn->fetchColumn();
    echo json_encode(['success' => true, 'cancha_nombre' => $cancha_nombre, 'hora' => $hora]);
    exit;
}

// POST: Asignación automática (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asignar_auto_ajax'])) {
    header('Content-Type: application/json');
    $post_tipo = $_POST['tipo_campeonato'] ?? 'comun';
    $post_temporada = $_POST['temporada'] ?? null;
    $post_canchas = $_POST['canchas'] ?? [];
    $post_fecha_programada = $_POST['fecha_programada'] ?? null;

    if (!$post_temporada || empty($post_canchas) || !$post_fecha_programada) {
        echo json_encode(['success' => false, 'error' => 'Selecciona temporada, fecha y al menos una cancha.']);
        exit;
    }

    try {
        $db->beginTransaction();

        if ($post_tipo === 'zonas') {
            $post_zonas_ids = $_POST['zonas_ids'] ?? [];
            $post_fecha_numero = $_POST['fecha_numero'] ?? null;
            if (empty($post_zonas_ids) || !$post_fecha_numero) {
                throw new Exception('Zonas y fecha requeridas.');
            }
            $placeholders = str_repeat('?,', count($post_zonas_ids) - 1) . '?';
            $pstmt = $db->prepare("
                SELECT p.id, el.nombre as local, ev.nombre as visitante, z.nombre as zona_nombre
                FROM partidos p
                JOIN equipos el ON el.id = p.equipo_local_id
                JOIN equipos ev ON ev.id = p.equipo_visitante_id
                JOIN zonas z ON z.id = p.zona_id
                WHERE p.zona_id IN ($placeholders) AND p.jornada_zona = ? AND p.tipo_torneo = 'zona'
                AND (p.cancha_id IS NULL OR p.hora_partido IS NULL)
                ORDER BY z.nombre, p.id
            ");
            $params = array_merge($post_zonas_ids, [$post_fecha_numero]);
            $pstmt->execute($params);
        } else {
            $post_fecha_id = $_POST['fecha_id'] ?? null;
            if (!$post_fecha_id) {
                throw new Exception('Fecha requerida.');
            }
            $pstmt = $db->prepare("
                SELECT p.id, el.nombre as local, ev.nombre as visitante 
                FROM partidos p
                JOIN equipos el ON el.id = p.equipo_local_id
                JOIN equipos ev ON ev.id = p.equipo_visitante_id
                WHERE p.fecha_id = ? AND (p.cancha_id IS NULL OR p.hora_partido IS NULL)
                ORDER BY p.id
            ");
            $pstmt->execute([$post_fecha_id]);
        }

        $partidos_asignar = $pstmt->fetchAll(PDO::FETCH_ASSOC);

        $available_slots = [];
        foreach ($post_canchas as $cid) {
            $hstmt = $db->prepare("SELECT hora FROM horarios_canchas WHERE cancha_id = ? AND temporada = ? AND activa = 1 ORDER BY hora");
            $hstmt->execute([$cid, $post_temporada]);
            $horarios = $hstmt->fetchAll(PDO::FETCH_COLUMN);
            if (empty($horarios)) continue;

            $oc_stmt = $db->prepare("SELECT hora_partido FROM partidos WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido IS NOT NULL");
            $oc_stmt->execute([$cid, $post_fecha_programada]);
            $ocupados = $oc_stmt->fetchAll(PDO::FETCH_COLUMN);

            $oc_stmt_zona = $db->prepare("SELECT hora_partido FROM partidos WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido IS NOT NULL AND tipo_torneo = 'zona'");
            $oc_stmt_zona->execute([$cid, $post_fecha_programada]);
            $ocupados_zona = $oc_stmt_zona->fetchAll(PDO::FETCH_COLUMN);

            $todos_ocupados = array_merge($ocupados, $ocupados_zona);
            $ocupados_map = array_flip($todos_ocupados);
            foreach ($horarios as $hora) {
                if (!isset($ocupados_map[$hora])) {
                    $available_slots[] = ['cancha_id' => $cid, 'hora' => $hora];
                }
            }
        }

        $assigned = [];
        $unassigned = [];
        $slotIndex = 0;
        $totalSlots = count($available_slots);
        foreach ($partidos_asignar as $partido) {
            if ($slotIndex >= $totalSlots) {
                $unassigned[] = $partido;
                continue;
            }
            $slot = $available_slots[$slotIndex];
            // Usar tabla unificada partidos para ambos tipos
            $upd = $db->prepare("UPDATE partidos SET cancha_id = ?, hora_partido = ?, fecha_partido = ? WHERE id = ?");
            $upd->execute([$slot['cancha_id'], $slot['hora'], $post_fecha_programada, $partido['id']]);
            $assigned[] = $partido;
            $slotIndex++;
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Asignación automática completada. Partidos asignados: ' . count($assigned) . '.',
            'unassigned_count' => count($unassigned)
        ]);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'error' => 'Error al asignar automáticamente: ' . $e->getMessage()]);
    }
    exit;
}

// Función auxiliar
function canchaTieneHorarioLibre(PDO $db, $cancha_id, $fecha_programada, $temporada) {
    $hstmt = $db->prepare("SELECT hora FROM horarios_canchas WHERE cancha_id = ? AND temporada = ? AND activa = 1 ORDER BY hora");
    $hstmt->execute([$cancha_id, $temporada]);
    $horarios = $hstmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($horarios)) return false;
    $oc_stmt = $db->prepare("SELECT hora_partido FROM partidos WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido IS NOT NULL");
    $oc_stmt->execute([$cancha_id, $fecha_programada]);
    $ocupados = $oc_stmt->fetchAll(PDO::FETCH_COLUMN);
    $oc_stmt_zona = $db->prepare("SELECT hora_partido FROM partidos WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido IS NOT NULL AND tipo_torneo = 'zona'");
    $oc_stmt_zona->execute([$cancha_id, $fecha_programada]);
    $ocupados_zona = $oc_stmt_zona->fetchAll(PDO::FETCH_COLUMN);
    $todos_ocupados = array_merge($ocupados, $ocupados_zona);
    $ocupados_map = array_flip($todos_ocupados);
    foreach ($horarios as $h) {
        if (!isset($ocupados_map[$h])) return true;
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Asignar Canchas y Horarios</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<style>
.bloque { padding:3px; color:#fff; border-radius:4px; margin:1px 0; font-size:12px; text-align:center; }
.disponible { background-color:#28a745; }
.ocupado { background-color:#dc3545; }
.zona-checkbox {
    padding: 8px 12px;
    border: 2px solid #dee2e6;
    border-radius: 6px;
    transition: all 0.3s;
}
.zona-checkbox:has(input:checked) {
    background-color: #e7f5ff;
    border-color: #0d6efd;
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container-fluid">
        <a class="navbar-brand" href="../index.php"><i class="fas fa-futbol"></i> Fútbol Manager - Admin</a>
        <div class="navbar-nav ms-auto">
            <a class="nav-link" href="dashboard.php">Dashboard</a>
            <a class="nav-link" href="../logout.php">Salir</a>
        </div>
    </div>
</nav>
<div class="container-fluid">
<div class="row">
    <div class="col-md-3 col-lg-2 p-0">
        <?php include 'include/sidebar.php'; ?>
    </div>
    <div class="col-md-9 col-lg-10 p-4">
        <h2><i class="fas fa-map-marker-alt"></i> Asignación de Canchas y Horarios</h2>
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- FILTROS -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros</h5>
            </div>
            <div class="card-body">
                <form method="GET" id="filterForm">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Campeonato</label>
                            <select name="campeonato_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Seleccionar</option>
                                <?php foreach ($campeonatos as $camp): ?>
                                    <option value="<?= $camp['id'] ?>" <?= ($campeonato_id == $camp['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($camp['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($campeonato_id && $tipo_campeonato === 'zonas'): ?>
                            <div class="col-md-3">
                                <label class="form-label">Formato</label>
                                <select name="formato_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($formatos as $fmt): ?>
                                        <option value="<?= $fmt['id'] ?>" <?= ($formato_id == $fmt['id']) ? 'selected' : '' ?>>
                                            <?= $fmt['cantidad_zonas'] ?> Zonas
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($formato_id): ?>
                            <div class="col-md-12 mt-3">
                                <label class="form-label fw-bold">
                                    <i class="fas fa-layer-group"></i> Seleccionar Zonas (una o varias)
                                </label>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php foreach ($zonas as $zona): ?>
                                        <div class="zona-checkbox form-check">
                                            <input class="form-check-input zona-select" type="checkbox" 
                                                   name="zonas_ids[]" value="<?= $zona['id'] ?>" 
                                                   id="zona<?= $zona['id'] ?>"
                                                   <?= in_array($zona['id'], $zonas_ids) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="zona<?= $zona['id'] ?>">
                                                <?= htmlspecialchars($zona['nombre']) ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="aplicarZonas">
                                    <i class="fas fa-check"></i> Aplicar Selección
                                </button>
                            </div>
                            <?php if (!empty($zonas_ids)): ?>
                            <div class="col-md-3 mt-3">
                                <label class="form-label">Jornada</label>
                                <select name="fecha_numero" class="form-select" onchange="this.form.submit()">
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($fechas as $fn): ?>
                                        <option value="<?= $fn ?>" <?= ($fecha_numero == $fn) ? 'selected' : '' ?>>
                                            Jornada <?= $fn ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            <?php endif; ?>
                        <?php elseif ($campeonato_id && $tipo_campeonato === 'comun'): ?>
                            <div class="col-md-3">
                                <label class="form-label">Categoría</label>
                                <select name="categoria_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= ($categoria_id == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php if ($categoria_id): ?>
                            <div class="col-md-3">
                                <label class="form-label">Fecha</label>
                                <select name="fecha_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($fechas as $f): ?>
                                        <option value="<?= $f['id'] ?>" <?= ($fecha_id == $f['id']) ? 'selected' : '' ?>>
                                            Fecha <?= $f['numero_fecha'] ?> (<?= formatDate($f['fecha_programada']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        <div class="col-md-3">
                            <label class="form-label">Temporada</label>
                            <select name="temporada" class="form-select" onchange="this.form.submit()">
                                <option value="">Seleccionar</option>
                                <option value="verano" <?= ($temporada === 'verano') ? 'selected' : '' ?>>Verano</option>
                                <option value="invierno" <?= ($temporada === 'invierno') ? 'selected' : '' ?>>Invierno</option>
                            </select>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- SECCIÓN DINÁMICA DE PARTIDOS -->
        <div id="partidosSection">
            <?php if (!empty($partidos)): ?>
                <!-- ASIGNACIÓN AUTOMÁTICA -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-magic"></i> Asignación Automática</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="autoAssignForm">
                            <input type="hidden" name="tipo_campeonato" value="<?= $tipo_campeonato ?>">
                            <input type="hidden" name="temporada" value="<?= htmlspecialchars($temporada) ?>">
                            <?php 
                            $fecha_programada_sel = null;
                            if ($tipo_campeonato === 'zonas' && !empty($zonas_ids) && $fecha_numero !== null) {
                                $fs = $db->prepare("SELECT fecha_partido FROM partidos WHERE zona_id = ? AND jornada_zona = ? AND tipo_torneo = 'zona' LIMIT 1");
                                $fs->execute([$zonas_ids[0], $fecha_numero]);
                                $fecha_programada_sel = $fs->fetchColumn();
                                foreach ($zonas_ids as $zid) {
                                    echo '<input type="hidden" name="zonas_ids[]" value="' . $zid . '">';
                                }
                                echo '<input type="hidden" name="fecha_numero" value="' . $fecha_numero . '">';
                            } elseif ($tipo_campeonato === 'comun' && $fecha_id) {
                                $fs = $db->prepare("SELECT fecha_programada FROM fechas WHERE id = ?");
                                $fs->execute([$fecha_id]);
                                $fecha_programada_sel = $fs->fetchColumn();
                                echo '<input type="hidden" name="fecha_id" value="' . $fecha_id . '">';
                            }
                            ?>
                            <input type="hidden" name="fecha_programada" value="<?= htmlspecialchars($fecha_programada_sel) ?>">
                            <div class="row">
                                <div class="col-md-12">
                                    <label class="form-label">Seleccionar Canchas (sólo con horarios libres)</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php
                                        if ($fecha_programada_sel && $temporada) {
                                            foreach ($canchas_all as $cancha) {
                                                if (canchaTieneHorarioLibre($db, $cancha['id'], $fecha_programada_sel, $temporada)) {
                                                    echo '<div class="form-check me-2">';
                                                    echo '<input class="form-check-input" type="checkbox" name="canchas[]" value="' . $cancha['id'] . '" id="cancha' . $cancha['id'] . '">';
                                                    echo '<label class="form-check-label" for="cancha' . $cancha['id'] . '">' . htmlspecialchars($cancha['nombre']) . '</label>';
                                                    echo '</div>';
                                                }
                                            }
                                        } else {
                                            echo '<div class="alert alert-info">Selecciona fecha y temporada.</div>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <button type="button" id="btnAutoAssign" class="btn btn-success mt-3">
                                <i class="fas fa-magic"></i> Asignación Automática
                            </button>
                        </form>
                    </div>
                </div>

                <!-- TABLA DE PARTIDOS -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Partidos - Asignación Manual</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <?php if ($tipo_campeonato === 'zonas'): ?>
                                    <th>Zona</th>
                                    <?php endif; ?>
                                    <th>Local</th>
                                    <th>Visitante</th>
                                    <th>Cancha</th>
                                    <th>Fecha</th>
                                    <th>Hora</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($partidos as $p): ?>
                                <tr id="row-<?= $p['id'] ?>">
                                    <td><?= $p['id'] ?></td>
                                    <?php if ($tipo_campeonato === 'zonas'): ?>
                                    <td><span class="badge bg-primary"><?= htmlspecialchars($p['zona_nombre']) ?></span></td>
                                    <?php endif; ?>
                                    <td><?= htmlspecialchars($p['local']) ?></td>
                                    <td><?= htmlspecialchars($p['visitante']) ?></td>
                                    <td>
                                        <select class="form-select manual-cancha" data-partido="<?= $p['id'] ?>">
                                            <option value="">-- Seleccionar --</option>
                                            <?php foreach ($canchas_all as $cancha): ?>
                                                <option value="<?= $cancha['id'] ?>" <?= ($p['cancha_id'] == $cancha['id']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($cancha['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="date" class="form-control manual-fecha" value="<?= $p['fecha_partido'] ?? $fecha_programada_sel ?>" data-partido="<?= $p['id'] ?>">
                                    </td>
                                    <td>
                                        <input list="horarios<?= $p['id'] ?>" class="form-control manual-hora" value="<?= $p['hora_partido'] ?>" data-partido="<?= $p['id'] ?>">
                                        <datalist id="horarios<?= $p['id'] ?>">
                                            <?php
                                            if (!empty($p['cancha_id']) && $temporada) {
                                                $hh = $db->prepare("SELECT hora FROM horarios_canchas WHERE cancha_id = ? AND temporada = ? AND activa = 1 ORDER BY hora");
                                                $hh->execute([$p['cancha_id'], $temporada]);
                                                foreach ($hh->fetchAll(PDO::FETCH_COLUMN) as $opt_h) {
                                                    echo "<option value=\"{$opt_h}\">";
                                                }
                                            }
                                            ?>
                                        </datalist>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm guardar-manual" data-partido="<?= $p['id'] ?>" data-tipo="<?= $tipo_campeonato ?>">
                                            <i class="fas fa-save"></i> Guardar
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- CALENDARIO VISUAL -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar"></i> Calendario Visual (Temporada: <?= htmlspecialchars($temporada ?: '—') ?>)</h5>
                    </div>
                    <div class="card-body">
                        <div id="calendarContainer">
                            <?php
                            if ($fecha_programada_sel && $temporada) {
                                echo '<div class="row">';
                                foreach ($canchas_all as $cancha) {
                                    echo '<div class="col-md-3">';
                                    echo '<div class="card mb-3">';
                                    echo '<div class="card-header">' . htmlspecialchars($cancha['nombre']) . '</div>';
                                    echo '<div class="card-body p-2" style="min-height:100px;">';
                                    $hstmt = $db->prepare("SELECT hora FROM horarios_canchas WHERE cancha_id = ? AND temporada = ? AND activa = 1 ORDER BY hora");
                                    $hstmt->execute([$cancha['id'], $temporada]);
                                    $horarios_cancha = $hstmt->fetchAll(PDO::FETCH_COLUMN);
                                    $oc_stmt = $db->prepare("SELECT hora_partido FROM partidos WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido IS NOT NULL");
                                    $oc_stmt->execute([$cancha['id'], $fecha_programada_sel]);
                                    $ocupados_cancha = $oc_stmt->fetchAll(PDO::FETCH_COLUMN);
                                    $oc_stmt_zona = $db->prepare("SELECT hora_partido FROM partidos WHERE cancha_id = ? AND fecha_partido = ? AND hora_partido IS NOT NULL AND tipo_torneo = 'zona'");
                                    $oc_stmt_zona->execute([$cancha['id'], $fecha_programada_sel]);
                                    $ocupados_zona = $oc_stmt_zona->fetchAll(PDO::FETCH_COLUMN);
                                    $todos_ocupados = array_merge($ocupados_cancha, $ocupados_zona);
                                    foreach ($horarios_cancha as $hora) {
                                        if (in_array($hora, $todos_ocupados)) {
                                            echo "<div class='bloque ocupado'>{$hora}</div>";
                                        } else {
                                            echo "<div class='bloque disponible'>{$hora}</div>";
                                        }
                                    }
                                    echo '</div></div></div>';
                                }
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-info">Selecciona fecha y temporada para mostrar el calendario.</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No hay partidos para mostrar.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<!-- Modal: partidos no asignados -->
<div class="modal fade" id="modalUnassigned" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Partidos no asignados</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p>Los siguientes partidos no pudieron programarse por falta de horarios disponibles:</p>
        <ul id="unassignedList"></ul>
        <p>Puedes asignar manualmente o elegir otras canchas/temporadas.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
function loadCalendar(fecha_programada, temporada) {
    if (!fecha_programada || !temporada) return;
    $.get('', { ajax_calendar: 1, fecha_programada: fecha_programada, temporada: temporada }, function(html) {
        $('#calendarContainer').html(html);
    });
}

function reloadPartidosSection() {
    const urlParams = new URLSearchParams(window.location.search);
    const temporada = '<?= addslashes($temporada) ?>';
    const data = {
        campeonato_id: urlParams.get('campeonato_id'),
        categoria_id: urlParams.get('categoria_id'),
        formato_id: urlParams.get('formato_id'),
        fecha_id: urlParams.get('fecha_id'),
        fecha_numero: urlParams.get('fecha_numero'),
        temporada: temporada,
        ajax_reload_partidos: 1
    };
    // Agregar zonas_ids si existen
    const zonas = urlParams.getAll('zonas_ids[]');
    if (zonas.length > 0) {
        data['zonas_ids[]'] = zonas;
    }
    $.get('', data, function(html) {
        $('#partidosSection').replaceWith(html);
        // Re-vincular eventos
        bindEventListeners();
    });
}

function bindEventListeners() {
    $('.manual-cancha').off('change').on('change', function() {
        var partidoId = $(this).data('partido');
        var canchaId = $(this).val();
        var temporada = '<?= addslashes($temporada) ?>';
        var datalistId = '#horarios' + partidoId;
        $(datalistId).empty();
        if (!canchaId || !temporada) return;
        $.getJSON('', { ajax_horarios: 1, cancha_id: canchaId, temporada: temporada }, function(data) {
            if (data.success) {
                data.horarios.forEach(function(h) {
                    $(datalistId).append('<option value="'+h+'">');
                });
            }
        });
    });

    $('.guardar-manual').off('click').on('click', function(e) {
        e.preventDefault();
        var id = $(this).data('partido');
        var tipo = $(this).data('tipo');
        var cancha = $('.manual-cancha[data-partido="'+id+'"]').val();
        var hora = $('.manual-hora[data-partido="'+id+'"]').val();
        var fecha = $('.manual-fecha[data-partido="'+id+'"]').val();
        if (!cancha || !hora || !fecha) {
            alert('Completa cancha, fecha y hora antes de guardar.');
            return;
        }
        $.post('', {
            asignar_manual: 1,
            ajax: 1,
            partido_id: id,
            cancha_id: cancha,
            hora: hora,
            fecha_partido: fecha,
            tipo_partido: tipo
        }, function(res) {
            if (res.success) {
                var fechaProg = fecha;
                var temp = '<?= addslashes($temporada) ?>';
                loadCalendar(fechaProg, temp);
                var alert = $('<div class="alert alert-success alert-dismissible fade show position-fixed" style="top:20px;right:20px;z-index:9999;">'+
                    '<i class="fas fa-check-circle"></i> Partido actualizado correctamente.'+
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'+
                    '</div>');
                $('body').append(alert);
                setTimeout(function() { alert.fadeOut(); }, 3000);
            } else {
                alert(res.error || 'Error al guardar');
            }
        }, 'json').fail(function() {
            alert('Error de conexión al guardar el partido.');
        });
    });
}

$(document).ready(function() {
    bindEventListeners();

    $('#aplicarZonas').on('click', function() {
        $('#filterForm').submit();
    });

    $('#btnAutoAssign').on('click', function() {
        const formData = $('#autoAssignForm').serialize();
        $.post('', formData + '&asignar_auto_ajax=1', function(res) {
            if (res.success) {
                // Recargar sección
                reloadPartidosSection();
                // Mostrar mensaje
                var alert = $('<div class="alert alert-success alert-dismissible fade show position-fixed" style="top:20px;right:20px;z-index:9999;">'+
                    '<i class="fas fa-check-circle"></i> ' + res.message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>'+
                    '</div>');
                $('body').append(alert);
                setTimeout(function() { alert.fadeOut(); }, 4000);

                // Si hay no asignados, mostrar modal
                if (res.unassigned_count > 0) {
                    $.get('', { ajax_reload_partidos: 1 }, function(html) {
                        // Extraer lista de no asignados del HTML (opcional)
                        // Por simplicidad, mostramos mensaje genérico
                        $('#unassignedList').html('<li>Hay ' + res.unassigned_count + ' partidos sin asignar.</li>');
                        var myModal = new bootstrap.Modal(document.getElementById('modalUnassigned'));
                        myModal.show();
                    });
                }
            } else {
                alert(res.error || 'Error al asignar automáticamente.');
            }
        }, 'json').fail(function() {
            alert('Error de conexión al asignar automáticamente.');
        });
    });
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>