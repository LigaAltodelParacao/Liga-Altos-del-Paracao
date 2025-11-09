<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config.php';

if (!function_exists('calculateAge')) {
    function calculateAge($fechaNacimiento) {
        if (!$fechaNacimiento) return '?';
        $dob = new DateTime($fechaNacimiento);
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        return $age;
    }
}

$db = Database::getInstance()->getConnection();

$stmt = $db->query("
    SELECT c.*, camp.nombre as campeonato_nombre
    FROM categorias c
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE c.activa = 1 AND camp.activo = 1
    ORDER BY camp.fecha_inicio DESC, c.nombre ASC
");
$categorias = $stmt->fetchAll();

$categoria_id = $_GET['categoria'] ?? ($categorias[0]['id'] ?? null);
$equipo_id = $_GET['equipo'] ?? null;

$equipos = [];
if ($categoria_id) {
    $stmt = $db->prepare("SELECT * FROM equipos WHERE categoria_id = ? ORDER BY nombre ASC");
    $stmt->execute([$categoria_id]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$equipo_actual = null;
if ($equipo_id) {
    $stmt = $db->prepare("SELECT * FROM equipos WHERE id = ?");
    $stmt->execute([$equipo_id]);
    $equipo_actual = $stmt->fetch(PDO::FETCH_ASSOC);
}

$partidos = [];
if ($equipo_id && $categoria_id) {
    $stmt = $db->prepare("
        SELECT p.*, 
               el.nombre AS local_nombre, el.logo AS local_logo,
               ev.nombre AS visitante_nombre, ev.logo AS visitante_logo
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        WHERE f.categoria_id = ? AND (p.equipo_local_id = ? OR p.equipo_visitante_id = ?)
        ORDER BY p.fecha_partido ASC
    ");
    $stmt->execute([$categoria_id, $equipo_id, $equipo_id]);
    $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$partidos_finalizados = array_filter($partidos, fn($p) => $p['estado'] === 'finalizado');
$partidos_restantes = array_filter($partidos, fn($p) => $p['estado'] !== 'finalizado');

$jugadores = [];
if ($equipo_id && $categoria_id) {
    $stmt = $db->prepare("
        SELECT 
            j.*,
            COALESCE(pj.partidos_jugados, 0) AS partidos_jugados,
            COALESCE(g.goles, 0) AS goles,
            COALESCE(a.amarillas, 0) AS amarillas_totales,
            COALESCE(r.rojas_directas, 0) AS rojas_directas,
            COALESCE(rd.rojas_dobles, 0) AS rojas_dobles,
            COALESCE(r.rojas_directas, 0) + COALESCE(rd.rojas_dobles, 0) AS rojas,
            COALESCE(s.fechas_pendientes, 0) AS sancion_restante
        FROM jugadores j
        LEFT JOIN (
            SELECT jp.jugador_id, COUNT(DISTINCT jp.partido_id) AS partidos_jugados
            FROM jugadores_partido jp
            JOIN partidos p ON jp.partido_id = p.id
            WHERE p.estado = 'finalizado' AND (p.equipo_local_id = ? OR p.equipo_visitante_id = ?)
            GROUP BY jp.jugador_id
        ) pj ON j.id = pj.jugador_id
        LEFT JOIN (
            SELECT ev.jugador_id, COUNT(*) AS goles
            FROM eventos_partido ev
            JOIN partidos p ON ev.partido_id = p.id
            JOIN fechas f ON p.fecha_id = f.id
            WHERE f.categoria_id = ? AND ev.tipo_evento = 'gol'
            GROUP BY ev.jugador_id
        ) g ON j.id = g.jugador_id
        LEFT JOIN (
            SELECT ev.jugador_id, COUNT(*) AS amarillas
            FROM eventos_partido ev
            JOIN partidos p ON ev.partido_id = p.id
            JOIN fechas f ON p.fecha_id = f.id
            WHERE f.categoria_id = ? AND ev.tipo_evento = 'amarilla'
            GROUP BY ev.jugador_id
        ) a ON j.id = a.jugador_id
        LEFT JOIN (
            SELECT ev.jugador_id, COUNT(*) AS rojas_directas
            FROM eventos_partido ev
            JOIN partidos p ON ev.partido_id = p.id
            JOIN fechas f ON p.fecha_id = f.id
            WHERE f.categoria_id = ? AND ev.tipo_evento = 'roja' AND (ev.observaciones IS NULL OR ev.observaciones != 'Doble amarilla')
            GROUP BY ev.jugador_id
        ) r ON j.id = r.jugador_id
        LEFT JOIN (
            SELECT ev.jugador_id, COUNT(*) AS rojas_dobles
            FROM eventos_partido ev
            JOIN partidos p ON ev.partido_id = p.id
            JOIN fechas f ON p.fecha_id = f.id
            WHERE f.categoria_id = ? AND ev.tipo_evento = 'roja' AND ev.observaciones = 'Doble amarilla'
            GROUP BY ev.jugador_id
        ) rd ON j.id = rd.jugador_id
        LEFT JOIN (
            SELECT s.jugador_id, SUM(s.partidos_suspension - s.partidos_cumplidos) AS fechas_pendientes
            FROM sanciones s
            WHERE s.activa = 1 AND s.partidos_cumplidos < s.partidos_suspension
            GROUP BY s.jugador_id
        ) s ON j.id = s.jugador_id
        WHERE j.equipo_id = ? AND j.activo = 1
        ORDER BY j.apellido_nombre ASC
    ");
    $stmt->execute([
        $equipo_id, $equipo_id,
        $categoria_id,
        $categoria_id,
        $categoria_id,
        $categoria_id,
        $equipo_id
    ]);
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (isset($_GET['ajax']) && $_GET['ajax'] === 'historial_jugador' && isset($_GET['jugador_id'])) {
    header('Content-Type: application/json');
    $jugador_id = (int)$_GET['jugador_id'];
    $categoria_id = (int)($_GET['categoria_id'] ?? 0);
    try {
        $stmt = $db->prepare("SELECT apellido_nombre, dni, fecha_nacimiento, foto FROM jugadores WHERE id = ?");
        $stmt->execute([$jugador_id]);
        $jugador = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$jugador) {
            echo json_encode(['error' => 'Jugador no encontrado']);
            exit;
        }
        $campeonato_actual = '';
        $partidos_jugados = 0;
        $goles = [];
        $amarillas = 0;
        $rojas = 0;
        $sanciones_activas = [];
        if ($categoria_id) {
            $stmt = $db->prepare("SELECT camp.nombre FROM categorias cat JOIN campeonatos camp ON cat.campeonato_id = camp.id WHERE cat.id = ?");
            $stmt->execute([$categoria_id]);
            $campeonato_actual = $stmt->fetchColumn() ?: 'Sin campeonato';
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT jp.partido_id)
                FROM jugadores_partido jp
                JOIN partidos p ON jp.partido_id = p.id
                WHERE jp.jugador_id = ? AND p.estado = 'finalizado'
            ");
            $stmt->execute([$jugador_id]);
            $partidos_jugados = (int)$stmt->fetchColumn();
            $stmt = $db->prepare("
                SELECT ev.minuto, e.nombre AS rival
                FROM eventos_partido ev
                JOIN partidos p ON ev.partido_id = p.id
                JOIN fechas f ON p.fecha_id = f.id
                JOIN jugadores j ON ev.jugador_id = j.id
                JOIN equipos e ON (
                    CASE 
                        WHEN j.equipo_id = p.equipo_local_id THEN p.equipo_visitante_id 
                        ELSE p.equipo_local_id 
                    END = e.id
                )
                WHERE f.categoria_id = ? AND ev.jugador_id = ? AND ev.tipo_evento = 'gol'
                ORDER BY p.fecha_partido ASC
            ");
            $stmt->execute([$categoria_id, $jugador_id]);
            $goles = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $db->prepare("
                SELECT COUNT(*)
                FROM eventos_partido ev
                JOIN partidos p ON ev.partido_id = p.id
                JOIN fechas f ON p.fecha_id = f.id
                WHERE f.categoria_id = ? AND ev.jugador_id = ? AND ev.tipo_evento = 'amarilla'
            ");
            $stmt->execute([$categoria_id, $jugador_id]);
            $amarillas = (int)$stmt->fetchColumn();
            $stmt = $db->prepare("
                SELECT COUNT(*)
                FROM eventos_partido ev
                JOIN partidos p ON ev.partido_id = p.id
                JOIN fechas f ON p.fecha_id = f.id
                WHERE f.categoria_id = ? AND ev.jugador_id = ? AND ev.tipo_evento = 'roja'
            ");
            $stmt->execute([$categoria_id, $jugador_id]);
            $rojas = (int)$stmt->fetchColumn();
            $stmt = $db->prepare("
                SELECT tipo, partidos_suspension, partidos_cumplidos, fecha_sancion, descripcion
                FROM sanciones
                WHERE jugador_id = ? AND activa = 1
            ");
            $stmt->execute([$jugador_id]);
            $sanciones_activas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $stmt = $db->prepare("
            SELECT 
                e.nombre AS equipo,
                cat.nombre AS categoria,
                camp.nombre AS campeonato,
                jeh.fecha_inicio AS fecha_inicio,
                jeh.fecha_fin AS fecha_fin
            FROM jugadores_equipos_historial jeh
            LEFT JOIN equipos e ON jeh.equipo_id = e.id
            LEFT JOIN categorias cat ON e.categoria_id = cat.id
            LEFT JOIN campeonatos camp ON cat.campeonato_id = camp.id
            WHERE REPLACE(REPLACE(REPLACE(TRIM(jeh.jugador_dni), '.', ''), ' ', ''), '-', '') =
                  REPLACE(REPLACE(REPLACE(TRIM((SELECT dni FROM jugadores WHERE id = ?)), '.', ''), ' ', ''), '-', '')
            ORDER BY jeh.fecha_inicio DESC, COALESCE(camp.fecha_inicio, jeh.fecha_inicio) DESC
        ");
        $stmt->execute([$jugador_id]);
        $equipos_historial = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'jugador' => $jugador,
            'campeonato_actual' => $campeonato_actual,
            'partidos_jugados' => $partidos_jugados,
            'goles' => $goles,
            'amarillas' => $amarillas,
            'rojas' => $rojas,
            'sanciones_activas' => $sanciones_activas,
            'equipos_historial' => $equipos_historial
        ]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al cargar historial']);
    }
    exit;
}

if (isset($_GET['ajax']) && $_GET['ajax'] == 'eventos' && isset($_GET['partido_id'])) {
    header('Content-Type: application/json');
    $partido_id = (int)$_GET['partido_id'];
    try {
        $stmt = $db->prepare("
            SELECT e.*, j.apellido_nombre, j.equipo_id
            FROM eventos_partido e
            JOIN jugadores j ON e.jugador_id = j.id
            WHERE e.partido_id = ?
            ORDER BY e.minuto ASC, e.id ASC
        ");
        $stmt->execute([$partido_id]);
        $eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $goles = array_filter($eventos, fn($e) => $e['tipo_evento'] === 'gol');
        $tarjetas = array_filter($eventos, fn($e) => in_array($e['tipo_evento'], ['amarilla', 'roja']));
        echo json_encode(['goles' => array_values($goles), 'tarjetas' => array_values($tarjetas)]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Error al cargar eventos']);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Equipos - Historial y Estadísticas</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
<style>
.tabla-jugadores { font-size: 0.75rem; }
.tabla-jugadores tbody tr:nth-child(odd) { background-color: #f8f9fa; }
.tabla-jugadores tbody tr:nth-child(even) { background-color: #ffffff; }
.tabla-jugadores tbody tr.sancionado { background-color: #ffe6e6 !important; }
.tabla-jugadores tbody tr.warning { background-color: #fff3cd !important; }
.tabla-jugadores td, .tabla-jugadores th {
    padding: 0.25rem 0.35rem;
    vertical-align: middle;
    line-height: 1.1;
    white-space: nowrap;
}
.badge { font-size: 0.65rem; padding: 0.2em 0.35em; }
.fas.fa-ban, .fas.fa-exclamation-triangle {
    font-size: 0.7rem;
    margin-left: 2px;
    vertical-align: middle;
}
.doble-amarilla-indicator {
    color: #ff9800;
    font-size: 0.8rem;
    margin-left: 3px;
    cursor: help;
}
.tabla-jugadores tbody tr { cursor: pointer; }
.tabla-jugadores tbody tr:hover { background-color: #e9ecef !important; }
.card-equipo {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}
.card-equipo:hover {
    transform: translateY(-5px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
    border-color: #28a745;
}
.equipo-logo-container {
    width: 80px;
    height: 80px;
    margin: 0 auto;
    display: flex;
    align-items: center;
    justify-content: center;
}
.eventos { display: none; }
</style>
</head>
<body>
<?php include '../include/header.php'; ?>
<div class="container my-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="h5 mb-3"><i class="fas fa-users"></i> Equipos - Historial y Estadísticas</h2>
        </div>
    </div>
    <div class="row mb-3">
        <div class="col-md-6">
            <label class="form-label fw-bold">Seleccionar Categoría:</label>
            <select class="form-select" onchange="cambiarCategoria(this.value)">
                <option value="">Selecciona una categoría</option>
                <?php foreach($categorias as $cat): ?>
                <option value="<?php echo $cat['id'];?>" <?php echo $cat['id']==$categoria_id?'selected':'';?>>
                    <?php echo htmlspecialchars($cat['campeonato_nombre'].' - '.$cat['nombre']);?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-bold">Seleccionar Equipo:</label>
            <select class="form-select" onchange="cambiarEquipo(this.value)">
                <option value="">Selecciona un equipo</option>
                <?php foreach($equipos as $eq): ?>
                <option value="<?php echo $eq['id'];?>" <?php echo $eq['id']==$equipo_id?'selected':'';?>>
                    <?php echo htmlspecialchars($eq['nombre']);?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php if ($equipo_actual): ?>
    <div class="row mb-3">
        <div class="col-12">
            <div class="alert alert-success alert-dismissible fade show">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="alert-heading mb-1">
                            <i class="fas fa-shield-alt"></i> <?= htmlspecialchars($equipo_actual['nombre']) ?>
                        </h6>
                        <p class="mb-0">
                            <small>Mostrando estadísticas del torneo actual</small>
                        </p>
                    </div>
                    <div>
                        <a href="historial_completo_equipo.php?equipo=<?php echo urlencode($equipo_actual['nombre']); ?>" 
                           class="btn btn-light btn-sm">
                            <i class="fas fa-history"></i> Ver Historial Completo
                        </a>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!$categoria_id): ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Selecciona una categoría para ver los equipos disponibles
            </div>
        </div>
    </div>
    <?php if (!empty($equipos)): ?>
        <div class="row">
            <div class="col-12 mb-3">
                <h6><i class="fas fa-th"></i> Equipos Disponibles</h6>
            </div>
            <?php foreach($equipos as $eq): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                <div class="card card-equipo h-100 text-center">
                    <div class="card-body">
                        <div class="equipo-logo-container mb-2">
                            <?php if ($eq['logo'] && file_exists("../uploads/".$eq['logo'])): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($eq['logo']);?>" 
                                     class="img-fluid rounded-circle border border-2 border-success" 
                                     style="max-width: 80px; max-height: 80px; object-fit: cover;"
                                     alt="Logo">
                            <?php else: ?>
                                <div class="bg-success rounded-circle d-flex align-items-center justify-content-center" 
                                     style="width: 80px; height: 80px;">
                                    <i class="fas fa-shield-alt text-white fa-2x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <h6 class="card-title mb-2"><?php echo htmlspecialchars($eq['nombre']);?></h6>
                        <div class="d-grid gap-2">
                            <button onclick="cambiarEquipo(<?php echo $eq['id']; ?>)" 
                                    class="btn btn-sm btn-primary">
                                <i class="fas fa-eye"></i> Ver Detalles
                            </button>
                            <a href="historial_completo_equipo.php?equipo=<?php echo urlencode($eq['nombre']); ?>" 
                               class="btn btn-sm btn-outline-success">
                                <i class="fas fa-history"></i> Historial
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php else: ?>
    <?php if ($equipo_id && $equipo_actual): ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-calendar-check"></i> Partidos Finalizados (<?= count($partidos_finalizados) ?>)
                </div>
                <div class="card-body p-0">
                    <?php if(empty($partidos_finalizados)): ?>
                    <p class="p-3 text-center text-muted">No hay partidos finalizados.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Rival</th>
                                    <th>Resultado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($partidos_finalizados as $p): 
                                $es_local = ($p['equipo_local_id'] == $equipo_id);
                                $rival = $es_local ? $p['visitante_nombre'] : $p['local_nombre'];
                                $rival_logo = $es_local ? $p['visitante_logo'] : $p['local_logo'];
                                $resultado = $es_local ? $p['goles_local'].' - '.$p['goles_visitante'] : $p['goles_visitante'].' - '.$p['goles_local'];
                                $resultado_clase = '';
                                if ($es_local) {
                                    if ($p['goles_local'] > $p['goles_visitante']) $resultado_clase = 'text-success fw-bold';
                                    elseif ($p['goles_local'] < $p['goles_visitante']) $resultado_clase = 'text-danger';
                                } else {
                                    if ($p['goles_visitante'] > $p['goles_local']) $resultado_clase = 'text-success fw-bold';
                                    elseif ($p['goles_visitante'] < $p['goles_local']) $resultado_clase = 'text-danger';
                                }
                                ?>
                                <tr>
                                    <td><small><?php echo date('d/m', strtotime($p['fecha_partido'])); ?></small></td>
                                    <td>
                                        <?php if($rival_logo && file_exists("../uploads/".$rival_logo)): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($rival_logo);?>" width="25" height="25" class="me-1 rounded">
                                        <?php endif; ?>
                                        <small><?php echo htmlspecialchars($rival);?></small>
                                        <br><small class="text-muted"><?= $es_local ? '(Local)' : '(Visit.)' ?></small>
                                    </td>
                                    <td>
                                        <span class="<?php echo $resultado_clase; ?>"><?php echo $resultado; ?></span>
                                        <div id="eventos-<?php echo $p['id']; ?>" class="eventos small mt-1"></div>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="toggleEventos(<?php echo $p['id']; ?>)" title="Ver eventos">
                                            <i class="fas fa-info-circle"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card mb-3">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-hourglass-half"></i> Partidos Restantes (<?= count($partidos_restantes) ?>)
                </div>
                <div class="card-body p-0">
                    <?php if(empty($partidos_restantes)): ?>
                    <p class="p-3 text-center text-muted">No hay partidos pendientes.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Rival</th>
                                    <th>Programado</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($partidos_restantes as $p): 
                                $es_local = ($p['equipo_local_id'] == $equipo_id);
                                $rival = $es_local ? $p['visitante_nombre'] : $p['local_nombre'];
                                $rival_logo = $es_local ? $p['visitante_logo'] : $p['local_logo'];
                                ?>
                                <tr>
                                    <td><small><?php echo date('d/m', strtotime($p['fecha_partido'])); ?></small></td>
                                    <td>
                                        <?php if($rival_logo && file_exists("../uploads/".$rival_logo)): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($rival_logo);?>" width="25" height="25" class="me-1 rounded">
                                        <?php endif; ?>
                                        <small><?php echo htmlspecialchars($rival);?></small>
                                        <br><small class="text-muted"><?= $es_local ? '(Local)' : '(Visit.)' ?></small>
                                    </td>
                                    <td><small><?php echo date('d/m/Y H:i', strtotime($p['fecha_partido'])); ?></small></td>
                                    <td>
                                        <span class="badge bg-<?= $p['estado']=='programado'?'primary':'warning' ?>">
                                            <?php echo ucfirst($p['estado']); ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-users"></i> Jugadores (<?= count($jugadores) ?>)</span>
                    <small>Clic en un jugador para ver su historial</small>
                </div>
                <div class="card-body p-0">
                    <?php if(empty($jugadores)): ?>
                    <p class="p-3 text-center text-muted">No hay jugadores registrados.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table tabla-jugadores table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Jugador</th>
                                    <th>Edad</th>
                                    <th title="Partidos Jugados"><i class="fas fa-running"></i></th>
                                    <th title="Goles"><i class="fas fa-futbol"></i></th>
                                    <th title="Amarillas"><i class="fas fa-square text-warning"></i></th>
                                    <th title="Rojas"><i class="fas fa-square text-danger"></i></th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($jugadores as $j): 
                                $clase = '';
                                if ($j['sancion_restante'] > 0) {
                                    $clase = 'sancionado';
                                } elseif ($j['amarillas_totales'] >= 3) {
                                    $clase = 'warning';
                                }
                                $tiene_doble_amarilla = ($j['rojas_dobles'] > 0);
                                ?>
                                <tr class="<?php echo $clase;?>" onclick="mostrarHistorialJugador(<?= $j['id'] ?>, <?= $categoria_id ?>)">
                                    <td>
                                        <?php if (!empty($j['foto']) && file_exists("../uploads/" . $j['foto'])): ?>
                                            <img src="../uploads/<?= htmlspecialchars($j['foto']) ?>" width="20" height="20" class="rounded me-1" alt="Foto">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($j['apellido_nombre']);?>
                                        <?php if($j['sancion_restante'] > 0): ?>
                                        <i class="fas fa-ban text-danger" title="Jugador sancionado"></i>
                                        <?php elseif($j['amarillas_totales'] >= 3): ?>
                                        <i class="fas fa-exclamation-triangle text-warning" title="Próximo a sanción"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo calculateAge($j['fecha_nacimiento']);?></td>
                                    <td>
                                        <?php if($j['partidos_jugados'] > 0): ?>
                                        <span class="badge bg-info"><?php echo $j['partidos_jugados'];?></span>
                                        <?php else: ?>
                                        <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($j['goles'] > 0): ?>
                                        <span class="badge bg-success"><?php echo $j['goles'];?></span>
                                        <?php else: ?>
                                        <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($j['amarillas_totales'] > 0): ?>
                                        <span class="badge bg-warning text-dark"><?php echo $j['amarillas_totales'];?></span>
                                        <?php else: ?>
                                        <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($j['rojas'] > 0): ?>
                                        <span class="badge bg-danger"><?php echo $j['rojas'];?></span>
                                        <?php if ($tiene_doble_amarilla): ?>
                                            <i class="fas fa-gem doble-amarilla-indicator" title="<?= $j['rojas_dobles'] ?> roja(s) por doble amarilla"></i>
                                        <?php endif; ?>
                                        <?php else: ?>
                                        <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if($j['sancion_restante'] > 0): ?>
                                        <span class="badge bg-danger"><?php echo $j['sancion_restante'];?> fecha(s)</span>
                                        <?php else: ?>
                                        <span class="badge bg-success">OK</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> Selecciona un equipo para ver sus estadísticas y jugadores
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-info">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-question-circle text-info"></i> ¿Cómo usar esta sección?</h6>
                    <div class="row">
                        <div class="col-md-4">
                            <small><strong>1. Seleccionar Categoría</strong></small>
                            <p class="small text-muted mb-2">Elige el torneo y categoría que deseas consultar</p>
                        </div>
                        <div class="col-md-4">
                            <small><strong>2. Seleccionar Equipo</strong></small>
                            <p class="small text-muted mb-2">Elige un equipo para ver sus estadísticas del torneo actual</p>
                        </div>
                        <div class="col-md-4">
                            <small><strong>3. Ver Historial Completo</strong></small>
                            <p class="small text-muted mb-2">Haz clic en "Ver Historial Completo" para ver todas las participaciones del equipo</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalHistorialJugador" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
            <i class="fas fa-user-circle"></i> Historial del Jugador
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-7">
            <div class="text-center mb-3" id="fotoJugadorContainer">
                <div class="bg-light border rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:80px;height:80px;">
                    <i class="fas fa-user fa-2x text-muted"></i>
                </div>
            </div>
            <h5 class="text-center mb-3" id="nombreJugadorModal"></h5>
            <div id="historialCampeonato"></div>
          </div>
          <div class="col-md-5">
            <h6 class="mb-3"><i class="fas fa-history"></i> Equipos en los que jugó</h6>
            <div id="historialEquipos"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <a id="btnPerfilJugador" href="#" class="btn btn-primary" target="_blank">
            <i class="fas fa-user"></i> Ver perfil completo
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<footer class="bg-dark text-light py-3 mt-4">
<div class="container text-center">
<small>© 2025 Sistema de Campeonatos - Actualizado: <?php echo date('d/m/Y H:i'); ?></small>
</div>
</footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script>
function cambiarCategoria(id){ 
    if(id) window.location.href='historial_equipos.php?categoria='+id; 
}
function cambiarEquipo(id){ 
    const cat = <?php echo json_encode($categoria_id); ?>;
    if(id && cat) window.location.href='historial_equipos.php?categoria='+cat+'&equipo='+id;
}
function toggleEventos(partido_id){
    const div = document.getElementById('eventos-'+partido_id);
    if(div.style.display==='block'){
        div.style.display='none';
        div.innerHTML='';
    } else {
        div.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Cargando...</span></div>';
        div.style.display='block';
        fetch('historial_equipos.php?ajax=eventos&partido_id='+partido_id)
        .then(res => res.json())
        .then(data => {
            let html = '';
            if(data.goles && data.goles.length > 0){
                html += '<strong class="text-success"><i class="fas fa-futbol"></i> Goles:</strong><ul class="mb-1">';
                data.goles.forEach(g => {
                    html += '<li class="small">'+g.apellido_nombre + (g.minuto > 0 ? " ("+g.minuto+"')" : '')+'</li>';
                });
                html += '</ul>';
            }
            if(data.tarjetas && data.tarjetas.length > 0){
                html += '<strong class="text-warning"><i class="fas fa-exclamation-triangle"></i> Tarjetas:</strong><ul class="mb-0">';
                data.tarjetas.forEach(t => {
                    const icono = t.tipo_evento === 'amarilla' ? '<i class="fas fa-square text-warning"></i>' : '<i class="fas fa-square text-danger"></i>';
                    html += '<li class="small">'+icono+' '+t.apellido_nombre + (t.observaciones ? ' ('+t.observaciones+')' : '')+'</li>';
                });
                html += '</ul>';
            }
            if(!html) html = '<small class="text-muted">Sin eventos registrados</small>';
            div.innerHTML = html;
        })
        .catch(err => {
            console.error(err);
            div.innerHTML = '<small class="text-danger">Error al cargar eventos</small>';
        });
    }
}
function mostrarHistorialJugador(jugadorId, categoriaId) {
    const modal = new bootstrap.Modal(document.getElementById('modalHistorialJugador'));
    document.getElementById('historialCampeonato').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div><p class="mt-2">Cargando historial...</p></div>';
    document.getElementById('historialEquipos').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';
    fetch(`historial_equipos.php?ajax=historial_jugador&jugador_id=${jugadorId}&categoria_id=${categoriaId}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            const fotoDiv = document.getElementById('fotoJugadorContainer');
            if (data.jugador.foto && data.jugador.foto.trim() !== '') {
                fotoDiv.innerHTML = `<img src="../uploads/${data.jugador.foto}" width="80" height="80" class="rounded-circle border border-3 border-primary mx-auto d-block">`;
            } else {
                fotoDiv.innerHTML = `<div class="bg-light border border-3 border-primary rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:80px;height:80px;"><i class="fas fa-user fa-2x text-muted"></i></div>`;
            }
            document.getElementById('nombreJugadorModal').innerText = data.jugador.apellido_nombre;
            // Link a perfil completo
            const perfilBtn = document.getElementById('btnPerfilJugador');
            if (perfilBtn) {
                perfilBtn.href = `jugador.php?id=${encodeURIComponent(jugadorId)}`;
            }
            let htmlCampeonato = `
                <div class="card mb-3">
                    <div class="card-body">
                        <p class="mb-1"><strong><i class="fas fa-id-card"></i> DNI:</strong> ${data.jugador.dni}</p>
                        <p class="mb-1"><strong><i class="fas fa-birthday-cake"></i> Edad:</strong> ${calculateAge(data.jugador.fecha_nacimiento)} años</p>
                        <p class="mb-0"><strong><i class="fas fa-trophy"></i> Campeonato:</strong> ${data.campeonato_actual}</p>
                    </div>
                </div>
                <div class="row text-center mb-3">
                    <div class="col-3">
                        <div class="card bg-info text-white">
                            <div class="card-body py-2">
                                <h4 class="mb-0">${data.partidos_jugados}</h4>
                                <small>Partidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card bg-success text-white">
                            <div class="card-body py-2">
                                <h4 class="mb-0">${data.goles.length}</h4>
                                <small>Goles</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card bg-warning text-dark">
                            <div class="card-body py-2">
                                <h4 class="mb-0">${data.amarillas}</h4>
                                <small>Amarillas</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body py-2">
                                <h4 class="mb-0">${data.rojas}</h4>
                                <small>Rojas</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            if (data.goles.length > 0) {
                htmlCampeonato += `
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <strong><i class="fas fa-futbol"></i> Detalle de Goles (${data.goles.length})</strong>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">`;
                data.goles.forEach(g => {
                    htmlCampeonato += `<li class="mb-2">⚽ ${g.minuto > 0 ? g.minuto + "'" : 'Sin min.'} vs <strong>${g.rival}</strong></li>`;
                });
                htmlCampeonato += `</ul></div></div>`;
            }
            if (data.sanciones_activas.length > 0) {
                htmlCampeonato += `<div class="alert alert-danger"><strong><i class="fas fa-ban"></i> Sanciones activas:</strong><ul class="mb-0 mt-2">`;
                data.sanciones_activas.forEach(s => {
                    let tipo = s.tipo === 'amarillas_acumuladas' ? '🟨 4 Amarillas' :
                               s.tipo === 'doble_amarilla' ? '🟨🟥 Doble Amarilla' :
                               s.tipo === 'roja_directa' ? '🟥 Roja Directa' : '📋 Administrativa';
                    let restantes = s.partidos_suspension - s.partidos_cumplidos;
                    htmlCampeonato += `<li>${tipo} - ${s.partidos_cumplidos}/${s.partidos_suspension} fechas (<strong>${restantes} restante(s)</strong>)`;
                    if(s.descripcion) htmlCampeonato += `<br><small>${s.descripcion}</small>`;
                    htmlCampeonato += `</li>`;
                });
                htmlCampeonato += `</ul></div>`;
            } else {
                htmlCampeonato += `<div class="alert alert-success"><i class="fas fa-check-circle"></i> Sin sanciones activas</div>`;
            }
            document.getElementById('historialCampeonato').innerHTML = htmlCampeonato;
            if (data.equipos_historial.length > 0) {
                let htmlEquipos = `<div class="list-group small">`;
                data.equipos_historial.forEach(eq => {
                    const equipoNombre = eq.equipo && eq.equipo.trim() !== '' ? eq.equipo : '(equipo no disponible)';
                    const categoriaNombre = eq.categoria && eq.categoria.trim() !== '' ? eq.categoria : '-';
                    const campeonatoNombre = eq.campeonato && eq.campeonato.trim() !== '' ? eq.campeonato : '-';
                    const fechaInicio = eq.fecha_inicio || '';
                    const fechaFin = eq.fecha_fin ? ` al ${eq.fecha_fin}` : '';
                    htmlEquipos += `
                        <div class="list-group-item py-2">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 fs-6"><i class="fas fa-shield-alt text-primary"></i> ${equipoNombre}</h6>
                            </div>
                            <p class="mb-1 text-muted">${campeonatoNombre} - ${categoriaNombre}</p>
                            <small class="text-muted"><i class="fas fa-calendar"></i> ${fechaInicio}${fechaFin}</small>
                        </div>`;
                });
                htmlEquipos += `</div>`;
                document.getElementById('historialEquipos').innerHTML = htmlEquipos;
            } else {
                document.getElementById('historialEquipos').innerHTML = '<p class="text-muted">Sin historial de equipos.</p>';
            }
            modal.show();
        })
        .catch(err => {
            console.error(err);
            alert('Error al cargar el historial.');
        });
}
function calculateAge(fechaNacimiento) {
    if (!fechaNacimiento) return '?';
    const dob = new Date(fechaNacimiento);
    const today = new Date();
    let age = today.getFullYear() - dob.getFullYear();
    const monthDiff = today.getMonth() - dob.getMonth();
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
        age--;
    }
    return age;
}
</script>
</body>
</html>