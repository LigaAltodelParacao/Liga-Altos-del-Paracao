<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';

$db = Database::getInstance()->getConnection();

// Obtener categorías activas
$stmt = $db->query("
    SELECT c.*, camp.nombre as campeonato_nombre
    FROM categorias c
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE c.activa = 1 AND camp.activo = 1
    ORDER BY camp.fecha_inicio ASC, c.nombre ASC
");
$categorias = $stmt->fetchAll();

// Categoría seleccionada
$categoria_id = $_GET['categoria'] ?? ($categorias[0]['id'] ?? null);
$equipo_id = $_GET['equipo'] ?? null;

// Equipos de la categoría
$equipos = [];
if ($categoria_id) {
    $stmt = $db->prepare("SELECT * FROM equipos WHERE categoria_id = ? ORDER BY nombre ASC");
    $stmt->execute([$categoria_id]);
    $equipos = $stmt->fetchAll();
}

// Información del equipo seleccionado
$equipo_actual = null;
if ($equipo_id) {
    $stmt = $db->prepare("SELECT * FROM equipos WHERE id = ?");
    $stmt->execute([$equipo_id]);
    $equipo_actual = $stmt->fetch();
}

// Partidos del equipo (solo del campeonato actual)
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
    $partidos = $stmt->fetchAll();
}

// Separar partidos
$partidos_finalizados = array_filter($partidos, fn($p) => $p['estado'] === 'finalizado');
$partidos_restantes = array_filter($partidos, fn($p) => $p['estado'] !== 'finalizado');

// Jugadores con estadísticas del campeonato actual (INCLUYENDO PARTIDOS JUGADOS)
$jugadores = [];
if ($equipo_id && $categoria_id) {
    $stmt = $db->prepare("
        SELECT 
            j.*,
            COALESCE(pj.partidos_jugados, 0) AS partidos_jugados,
            COALESCE(g.goles, 0) AS goles,
            COALESCE(a.amarillas, 0) AS amarillas_totales,
            COALESCE(r.rojas_directas, 0) + COALESCE(rd.rojas_dobles, 0) AS rojas,
            COALESCE(s.fechas_pendientes, 0) AS sancion_restante
        FROM jugadores j
        LEFT JOIN (
            SELECT jp.jugador_id, COUNT(DISTINCT jp.partido_id) AS partidos_jugados
            FROM jugadores_partido jp
            JOIN partidos p ON jp.partido_id = p.id
            JOIN fechas f ON p.fecha_id = f.id
            WHERE f.categoria_id = ? AND p.estado = 'finalizado'
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
        $categoria_id,
        $categoria_id,
        $categoria_id,
        $categoria_id,
        $categoria_id,
        $equipo_id
    ]);

    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Endpoint AJAX para historial de jugador
if (isset($_GET['ajax']) && $_GET['ajax'] === 'historial_jugador' && isset($_GET['jugador_id'])) {
    header('Content-Type: application/json');
    $jugador_id = (int)$_GET['jugador_id'];
    $categoria_id = (int)($_GET['categoria_id'] ?? 0);

    try {
        // Datos básicos del jugador
        $stmt = $db->prepare("SELECT apellido_nombre, dni, fecha_nacimiento, foto FROM jugadores WHERE id = ?");
        $stmt->execute([$jugador_id]);
        $jugador = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$jugador) {
            echo json_encode(['error' => 'Jugador no encontrado']);
            exit;
        }

        // --- Historial en el campeonato actual ---
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

            // Partidos jugados (desde jugadores_partido)
            $stmt = $db->prepare("
                SELECT COUNT(DISTINCT jp.partido_id)
                FROM jugadores_partido jp
                JOIN partidos p ON jp.partido_id = p.id
                JOIN fechas f ON p.fecha_id = f.id
                WHERE f.categoria_id = ? AND jp.jugador_id = ? AND p.estado = 'finalizado'
            ");
            $stmt->execute([$categoria_id, $jugador_id]);
            $partidos_jugados = (int)$stmt->fetchColumn();

            // Goles con rival y minuto
            $stmt = $db->prepare("
                SELECT ev.minuto, e.nombre AS rival, p.equipo_local_id, p.equipo_visitante_id, j.equipo_id
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

            // Amarillas
            $stmt = $db->prepare("
                SELECT COUNT(*)
                FROM eventos_partido ev
                JOIN partidos p ON ev.partido_id = p.id
                JOIN fechas f ON p.fecha_id = f.id
                WHERE f.categoria_id = ? AND ev.jugador_id = ? AND ev.tipo_evento = 'amarilla'
            ");
            $stmt->execute([$categoria_id, $jugador_id]);
            $amarillas = (int)$stmt->fetchColumn();

            // Rojas
            $stmt = $db->prepare("
                SELECT COUNT(*)
                FROM eventos_partido ev
                JOIN partidos p ON ev.partido_id = p.id
                JOIN fechas f ON p.fecha_id = f.id
                WHERE f.categoria_id = ? AND ev.jugador_id = ? AND ev.tipo_evento = 'roja'
            ");
            $stmt->execute([$categoria_id, $jugador_id]);
            $rojas = (int)$stmt->fetchColumn();

            // Sanciones activas
            $stmt = $db->prepare("
                SELECT tipo, partidos_suspension, partidos_cumplidos, fecha_sancion, descripcion
                FROM sanciones
                WHERE jugador_id = ? AND activa = 1
            ");
            $stmt->execute([$jugador_id]);
            $sanciones_activas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // --- Historial de equipos ---
        $stmt = $db->prepare("
            SELECT DISTINCT e.nombre AS equipo, cat.nombre AS categoria, camp.nombre AS campeonato, camp.fecha_inicio
            FROM jugadores j
            JOIN equipos e ON j.equipo_id = e.id
            JOIN categorias cat ON e.categoria_id = cat.id
            JOIN campeonatos camp ON cat.campeonato_id = camp.id
            WHERE j.id = ?
            ORDER BY camp.fecha_inicio DESC
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

// Endpoint AJAX para eventos de partidos
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
<title>Equipos</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
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
.tabla-jugadores tbody tr { cursor: pointer; }
.tabla-jugadores tbody tr:hover { background-color: #e9ecef !important; }
</style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
<div class="container">
<a class="navbar-brand" href="../index.php"><i class="fas fa-futbol"></i> Fútbol Manager</a>
<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
<span class="navbar-toggler-icon"></span>
</button>
<div class="collapse navbar-collapse" id="navbarNav">
<ul class="navbar-nav me-auto">
<li class="nav-item"><a class="nav-link" href="resultados.php">Resultados</a></li>
<li class="nav-item"><a class="nav-link" href="tablas.php">Posiciones</a></li>
<li class="nav-item"><a class="nav-link" href="goleadores.php">Goleadores</a></li>
<li class="nav-item"><a class="nav-link" href="fixture.php">Fixture</a></li>
<li class="nav-item"><a class="nav-link" href="sanciones.php">Sanciones</a></li>
<li class="nav-item"><a class="nav-link active" href="historial_equipos.php">Equipos</a></li>
<li class="nav-item"><a class="nav-link" href="fairplay.php">Fairplay</a></li>
</ul>
<ul class="navbar-nav">
<?php if (isLoggedIn()): ?>
<li class="nav-item"><a class="nav-link" href="admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Panel Admin</a></li>
<li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
<?php else: ?>
<li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Ingresar</a></li>
<?php endif; ?>
</ul>
</div>
</div>
</nav>

<div class="container my-4">
<div class="row mb-3">
<div class="col-md-6">
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
<div class="alert alert-info">
<i class="fas fa-info-circle"></i> Mostrando historial de: <strong><?= htmlspecialchars($equipo_actual['nombre']) ?></strong>
</div>
<?php endif; ?>

<div class="row">
<!-- Partidos -->
<div class="col-md-6">
<!-- Finalizados -->
<div class="card mb-3">
<div class="card-header bg-success text-white">
<i class="fas fa-calendar-check"></i> Partidos Finalizados (<?= count($partidos_finalizados) ?>)
</div>
<div class="card-body p-0">
<?php if(empty($partidos_finalizados)): ?>
<p class="p-3 text-center text-muted">No hay partidos finalizados.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead>
<tr><th>Fecha</th><th>Rival</th><th>Resultado</th><th>Eventos</th></tr>
</thead>
<tbody>
<?php foreach($partidos_finalizados as $p): 
$es_local = ($p['equipo_local_id'] == $equipo_id);
$rival = $es_local ? $p['visitante_nombre'] : $p['local_nombre'];
$rival_logo = $es_local ? $p['visitante_logo'] : $p['local_logo'];
$resultado = $es_local ? $p['goles_local'].' - '.$p['goles_visitante'] : $p['goles_visitante'].' - '.$p['goles_local'];
?>
<tr>
<td><?php echo date('d/m', strtotime($p['fecha_partido'])); ?></td>
<td>
<?php if($rival_logo && file_exists("../uploads/".$rival_logo)): ?>
<img src="../uploads/<?php echo htmlspecialchars($rival_logo);?>" width="25" height="25" class="me-2 rounded">
<?php endif; ?>
<?php echo htmlspecialchars($rival);?>
<small class="text-muted"><?= $es_local ? '(Local)' : '(Visitante)' ?></small>
</td>
<td>
<span class="ver-eventos" onclick="toggleEventos(<?php echo $p['id']; ?>)">
<?php echo $resultado; ?>
</span>
<div id="eventos-<?php echo $p['id']; ?>" class="eventos"></div>
</td>
<td>
<button class="btn btn-sm btn-outline-primary" onclick="toggleEventos(<?php echo $p['id']; ?>)">
<i class="fas fa-eye"></i>
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

<!-- Restantes -->
<div class="card mb-3">
<div class="card-header bg-warning text-dark">
<i class="fas fa-hourglass-half"></i> Partidos Restantes (<?= count($partidos_restantes) ?>)
</div>
<div class="card-body p-0">
<?php if(empty($partidos_restantes)): ?>
<p class="p-3 text-center text-muted">No hay partidos pendientes.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table table-hover mb-0">
<thead>
<tr><th>Fecha</th><th>Rival</th><th>Fecha Prog.</th><th>Estado</th></tr>
</thead>
<tbody>
<?php foreach($partidos_restantes as $p): 
$es_local = ($p['equipo_local_id'] == $equipo_id);
$rival = $es_local ? $p['visitante_nombre'] : $p['local_nombre'];
$rival_logo = $es_local ? $p['visitante_logo'] : $p['local_logo'];
?>
<tr>
<td><?php echo date('d/m', strtotime($p['fecha_partido'])); ?></td>
<td>
<?php if($rival_logo && file_exists("../uploads/".$rival_logo)): ?>
<img src="../uploads/<?php echo htmlspecialchars($rival_logo);?>" width="25" height="25" class="me-2 rounded">
<?php endif; ?>
<?php echo htmlspecialchars($rival);?>
<small class="text-muted"><?= $es_local ? '(Local)' : '(Visitante)' ?></small>
</td>
<td><?php echo date('d/m/Y', strtotime($p['fecha_partido'])); ?></td>
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

<!-- Jugadores -->
<div class="col-md-6">
<div class="card mb-3">
<div class="card-header bg-primary text-white">
<i class="fas fa-users"></i> Jugadores (<?= count($jugadores) ?>)
</div>
<div class="card-body p-0">
<?php if(empty($jugadores)): ?>
<p class="p-3 text-center text-muted">No hay jugadores registrados.</p>
<?php else: ?>
<div class="table-responsive">
<table class="table tabla-jugadores table-hover mb-0">
<thead>
<tr>
<th>Jugador</th>
<th>Edad</th>
<th title="Partidos Jugados"><i class="fas fa-calendar-check"></i> PJ</th>
<th title="Goles"><i class="fas fa-futbol"></i> G</th>
<th title="Amarillas"><i class="fas fa-square text-warning"></i></th>
<th title="Rojas"><i class="fas fa-square text-danger"></i></th>
<th>Sanción</th>
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
<?php else: ?>
<span class="text-muted">0</span>
<?php endif; ?>
</td>
<td>
<?php if($j['sancion_restante'] > 0): ?>
<span class="badge bg-danger"><?php echo $j['sancion_restante'];?> fecha(s)</span>
<?php else: ?>
<span class="text-success">Habilitado</span>
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
</div>

<!-- Modal Historial Jugador -->
<div class="modal fade" id="modalHistorialJugador" tabindex="-1" aria-labelledby="modalHistorialJugadorLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="modalHistorialJugadorLabel">Historial del Jugador</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-7">
            <div class="text-center mb-3" id="fotoJugadorContainer">
                <div class="bg-light border rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:80px;height:80px;">
                    <i class="fas fa-user fa-2x text-muted"></i>
                </div>
            </div>
            <h6 class="text-center" id="nombreJugadorModal"></h6>
            <div id="historialCampeonato"></div>
          </div>
          <div class="col-md-5">
            <h6><i class="fas fa-users"></i> Equipos en los que jugó</h6>
            <div id="historialEquipos"></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
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
    if(id) window.location.href='historial_equipos.php?categoria='+cat+'&equipo='+id;
}

function toggleEventos(partido_id){
    const div = document.getElementById('eventos-'+partido_id);
    if(div.style.display==='block'){
        div.style.display='none';
        div.innerHTML='';
    } else {
        fetch('historial_equipos.php?ajax=eventos&partido_id='+partido_id)
        .then(res => res.json())
        .then(data => {
            let html = '';
            if(data.goles && data.goles.length > 0){
                html += '<strong>Goles:</strong><ul>';
                data.goles.forEach(g => html += '<li>'+g.apellido_nombre+'</li>');
                html += '</ul>';
            }
            if(data.tarjetas && data.tarjetas.length > 0){
                html += '<strong>Tarjetas:</strong><ul>';
                data.tarjetas.forEach(t => html += '<li>'+t.apellido_nombre+' ('+t.tipo_evento+')</li>');
                html += '</ul>';
            }
            div.innerHTML = html;
            div.style.display = 'block';
        });
    }
}

function mostrarHistorialJugador(jugadorId, categoriaId) {
    const modal = new bootstrap.Modal(document.getElementById('modalHistorialJugador'));
    document.getElementById('historialCampeonato').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';
    document.getElementById('historialEquipos').innerHTML = '<div class="text-center py-3"><div class="spinner-border text-primary" role="status"></div></div>';

    fetch(`historial_equipos.php?ajax=historial_jugador&jugador_id=${jugadorId}&categoria_id=${categoriaId}`)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }

            // Foto
            const fotoDiv = document.getElementById('fotoJugadorContainer');
            if (data.jugador.foto && data.jugador.foto.trim() !== '' && data.jugador.foto.startsWith('jugadores/')) {
                fotoDiv.innerHTML = `<img src="../uploads/${data.jugador.foto}" width="80" height="80" class="rounded-circle border mx-auto d-block">`;
            } else {
                fotoDiv.innerHTML = `<div class="bg-light border rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width:80px;height:80px;"><i class="fas fa-user fa-2x text-muted"></i></div>`;
            }

            document.getElementById('nombreJugadorModal').innerText = data.jugador.apellido_nombre;

            let htmlCampeonato = `
                <p><strong>DNI:</strong> ${data.jugador.dni}</p>
                <p><strong>Edad:</strong> ${calculateAge(data.jugador.fecha_nacimiento)} años</p>
                <p><strong>Campeonato:</strong> ${data.campeonato_actual}</p>
                <hr>
                <div class="row text-center mb-3">
                    <div class="col-4">
                        <div class="card bg-info text-white">
                            <div class="card-body py-2">
                                <h3 class="mb-0">${data.partidos_jugados}</h3>
                                <small>Partidos</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card bg-success text-white">
                            <div class="card-body py-2">
                                <h3 class="mb-0">${data.goles.length}</h3>
                                <small>Goles</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="card bg-warning text-dark">
                            <div class="card-body py-2">
                                <h3 class="mb-0">${data.amarillas}</h3>
                                <small>Amarillas</small>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            if (data.rojas > 0) {
                htmlCampeonato += `<p><strong>Rojas:</strong> <span class="badge bg-danger">${data.rojas}</span></p>`;
            }

            if (data.goles.length > 0) {
                htmlCampeonato += `<p><strong>Detalle de Goles (${data.goles.length}):</strong></p><ul class="list-unstyled">`;
                data.goles.forEach(g => {
                    htmlCampeonato += `<li class="mb-1">⚽ ${g.minuto > 0 ? g.minuto + "'" : ''} vs <strong>${g.rival}</strong></li>`;
                });
                htmlCampeonato += `</ul>`;
            }

            if (data.sanciones_activas.length > 0) {
                htmlCampeonato += `<div class="alert alert-danger mt-3"><strong><i class="fas fa-ban"></i> Sanciones activas:</strong><ul class="mb-0 mt-2">`;
                data.sanciones_activas.forEach(s => {
                    let tipo = s.tipo === 'amarillas_acumuladas' ? '🟨 4 Amarillas' :
                               s.tipo === 'doble_amarilla' ? '🟨🟥 Doble Amarilla' :
                               s.tipo === 'roja_directa' ? '🟥 Roja Directa' : '📋 Administrativa';
                    let restantes = s.partidos_suspension - s.partidos_cumplidos;
                    htmlCampeonato += `<li>${tipo} - ${s.partidos_cumplidos}/${s.partidos_suspension} fechas cumplidas (<strong>${restantes} restante(s)</strong>)</li>`;
                });
                htmlCampeonato += `</ul></div>`;
            } else {
                htmlCampeonato += `<div class="alert alert-success mt-3"><i class="fas fa-check-circle"></i> Sin sanciones activas</div>`;
            }

            document.getElementById('historialCampeonato').innerHTML = htmlCampeonato;

            if (data.equipos_historial.length > 0) {
                let htmlEquipos = `<ul class="list-group">`;
                data.equipos_historial.forEach(eq => {
                    htmlEquipos += `<li class="list-group-item">
                        <strong>${eq.equipo}</strong><br>
                        <small class="text-muted">${eq.campeonato} - ${eq.categoria}</small><br>
                        <small class="text-muted"><i class="fas fa-calendar"></i> ${eq.fecha_inicio}</small>
                    </li>`;
                });
                htmlEquipos += `</ul>`;
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