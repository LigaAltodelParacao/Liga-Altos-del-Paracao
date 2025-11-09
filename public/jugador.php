<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

$db = Database::getInstance()->getConnection();
$jugador_id = (int)($_GET['id'] ?? 0);
if (!$jugador_id) {
    redirect('historial_equipos.php');
}

$stmt = $db->prepare("\n    SELECT j.*, e.nombre as equipo_actual, e.id as equipo_actual_id, c.nombre as categoria_actual, camp.nombre as campeonato_actual, camp.id as campeonato_actual_id\n    FROM jugadores j\n    LEFT JOIN equipos e ON j.equipo_id = e.id\n    LEFT JOIN categorias c ON e.categoria_id = c.id\n    LEFT JOIN campeonatos camp ON c.campeonato_id = camp.id\n    WHERE j.id = ?\n");
$stmt->execute([$jugador_id]);
$jugador = $stmt->fetch();
if (!$jugador) redirect('historial_equipos.php');

$stmt = $db->prepare("\n    SELECT \n        jeh.*,\n        jeh.equipo_id,\n        e.nombre as equipo_nombre,\n        e.logo as equipo_logo,\n        c.nombre as categoria_nombre,\n        camp.id as campeonato_id,\n        camp.nombre as campeonato_nombre,\n        camp.fecha_inicio as campeonato_fecha_inicio,\n        camp.fecha_fin as campeonato_fecha_fin\n    FROM jugadores_equipos_historial jeh\n    JOIN equipos e ON jeh.equipo_id = e.id\n    JOIN categorias c ON e.categoria_id = c.id\n    JOIN campeonatos camp ON c.campeonato_id = camp.id\n    WHERE jeh.jugador_dni = ?\n    ORDER BY jeh.fecha_inicio DESC, camp.fecha_inicio DESC\n");
$stmt->execute([$jugador['dni']]);
$historial = $stmt->fetchAll();

// Completar estadísticas por registro del timeline (equipo + campeonato)
foreach ($historial as $idx => $reg) {
    $equipoId = (int)($reg['equipo_id'] ?? 0);
    $campId = (int)($reg['campeonato_id'] ?? 0);
    if ($equipoId && $campId) {
        try {
            // Partidos jugados por el jugador con ese equipo en ese campeonato
            $q = $db->prepare("\n                SELECT COUNT(DISTINCT jp.partido_id)\n                FROM jugadores_partido jp\n                JOIN partidos p ON jp.partido_id = p.id\n                JOIN fechas f ON p.fecha_id = f.id\n                WHERE jp.jugador_id = ? AND p.estado = 'finalizado'\n                  AND (p.equipo_local_id = ? OR p.equipo_visitante_id = ?)\n                  AND f.categoria_id IN (SELECT id FROM categorias WHERE campeonato_id = ?)\n            ");
            $q->execute([$jugador_id, $equipoId, $equipoId, $campId]);
            $historial[$idx]['partidos_jugados'] = (int)$q->fetchColumn();

            // Goles, Amarillas, Rojas
            $tipos = [ 'gol' => 'goles', 'amarilla' => 'amarillas', 'roja' => 'rojas' ];
            foreach ($tipos as $tipo => $key) {
                $q2 = $db->prepare("\n                    SELECT COUNT(*)\n                    FROM eventos_partido ev\n                    JOIN partidos p ON ev.partido_id = p.id\n                    JOIN fechas f ON p.fecha_id = f.id\n                    WHERE ev.jugador_id = ? AND ev.tipo_evento = ?\n                      AND (p.equipo_local_id = ? OR p.equipo_visitante_id = ?)\n                      AND f.categoria_id IN (SELECT id FROM categorias WHERE campeonato_id = ?)\n                ");
                $q2->execute([$jugador_id, $tipo, $equipoId, $equipoId, $campId]);
                $historial[$idx][$key] = (int)$q2->fetchColumn();
            }
        } catch (Exception $e) {
            // Continuar sin romper
        }
    }
}

if (!function_exists('calculateAge')) {
    function calculateAge($birthDate) { return date_diff(date_create($birthDate), date_create('today'))->y; }
}

$campFiltro = isset($_GET['camp_id']) ? (int)$_GET['camp_id'] : null;
$teamFiltro = isset($_GET['team_id']) ? (int)$_GET['team_id'] : null;
$campActualId = $jugador['campeonato_actual_id'] ?? null;
if (!$campFiltro && !$teamFiltro) {
    if ($campActualId) $campFiltro = (int)$campActualId; else if (!empty($historial)) $campFiltro = (int)$historial[0]['campeonato_id'];
}
$subset = $historial; $scopeLabel = 'Historial completo';
if ($campFiltro) { $subset = array_values(array_filter($historial, fn($r) => (int)$r['campeonato_id'] === (int)$campFiltro)); foreach ($historial as $r) { if ((int)$r['campeonato_id']===(int)$campFiltro) { $scopeLabel = 'Campeonato: '.$r['campeonato_nombre']; break; } } }
if ($teamFiltro) { $subset = array_values(array_filter($historial, fn($r) => (int)$r['equipo_id'] === (int)$teamFiltro)); foreach ($historial as $r) { if ((int)$r['equipo_id']===(int)$teamFiltro) { $scopeLabel = 'Equipo: '.$r['equipo_nombre']; break; } } }
$dest = [
    'partidos' => array_sum(array_column($subset, 'partidos_jugados')),
    'goles' => array_sum(array_column($subset, 'goles')),
    'amarillas' => array_sum(array_column($subset, 'amarillas')),
    'rojas' => array_sum(array_column($subset, 'rojas'))
];
$torneos = []; $equipos = [];
foreach ($historial as $r) { $torneos[$r['campeonato_id']] = $r['campeonato_nombre']; $equipos[$r['equipo_id']] = $r['equipo_nombre']; }

// Fallback PRO: si las sumas del historial están en 0, calcular por SQL según alcance (campeonato)
if (($dest['partidos'] + $dest['goles'] + $dest['amarillas'] + $dest['rojas']) === 0) {
    try {
        // Partidos jugados
        if ($campFiltro) {
            $stmt = $db->prepare("\n                SELECT COUNT(DISTINCT jp.partido_id)\n                FROM jugadores_partido jp\n                JOIN partidos p ON jp.partido_id = p.id\n                JOIN fechas f ON p.fecha_id = f.id\n                WHERE jp.jugador_id = ? AND p.estado = 'finalizado'\n                  AND f.categoria_id IN (SELECT id FROM categorias WHERE campeonato_id = ?)\n            ");
            $stmt->execute([$jugador_id, $campFiltro]);
        } else {
            $stmt = $db->prepare("\n                SELECT COUNT(DISTINCT jp.partido_id)\n                FROM jugadores_partido jp\n                JOIN partidos p ON jp.partido_id = p.id\n                WHERE jp.jugador_id = ? AND p.estado = 'finalizado'\n            ");
            $stmt->execute([$jugador_id]);
        }
        $dest['partidos'] = (int)$stmt->fetchColumn();

        // Contadores de eventos
        $tipos = ['gol' => 'goles', 'amarilla' => 'amarillas', 'roja' => 'rojas'];
        foreach ($tipos as $tipo => $key) {
            if ($campFiltro) {
                $stmt = $db->prepare("\n                    SELECT COUNT(*)\n                    FROM eventos_partido ev\n                    JOIN partidos p ON ev.partido_id = p.id\n                    JOIN fechas f ON p.fecha_id = f.id\n                    WHERE ev.jugador_id = ? AND ev.tipo_evento = ?\n                      AND f.categoria_id IN (SELECT id FROM categorias WHERE campeonato_id = ?)\n                ");
                $stmt->execute([$jugador_id, $tipo, $campFiltro]);
            } else {
                $stmt = $db->prepare("\n                    SELECT COUNT(*)\n                    FROM eventos_partido ev\n                    JOIN partidos p ON ev.partido_id = p.id\n                    WHERE ev.jugador_id = ? AND ev.tipo_evento = ?\n                ");
                $stmt->execute([$jugador_id, $tipo]);
            }
            $dest[$key] = (int)$stmt->fetchColumn();
        }
    } catch (Exception $e) {
        // En caso de error, mantener valores en 0 silenciosamente
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Perfil - <?php echo htmlspecialchars($jugador['apellido_nombre']); ?></title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
.stat-card{background:#fff;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.08);padding:1rem;text-align:center}
.stat-number{font-size:2rem;font-weight:700}
.chips a{text-decoration:none}
.timeline-item{position:relative;padding-left:1.25rem;border-left:2px solid #e9ecef;margin-bottom:1rem}
.timeline-marker{position:absolute;left:-7px;top:0;width:14px;height:14px;border-radius:50%;background:#198754;border:3px solid #fff;box-shadow:0 0 0 3px #e9ecef}
@media (max-width: 768px) {
.container-fluid{padding:0.5rem}
.container{padding-left:10px;padding-right:10px}
h1,h5{font-size:1.5rem}
.stat-card{padding:1rem}
.stat-number{font-size:2rem}
.col-md-2{margin-bottom:0.75rem}
.timeline-item{padding-left:1.5rem}
.col-md-1,.col-md-5,.col-md-2,.col-md-4{margin-bottom:0.5rem}
.card-body{padding:1rem}
img,.rounded-circle{width:60px!important;height:60px!important}
.col-3 img{width:50px!important;height:50px!important}
}
@media (max-width: 576px) {
h1,h5{font-size:1.25rem}
.stat-number{font-size:1.5rem}
.timeline-item{padding-left:1.25rem;padding-bottom:1rem}
img,.rounded-circle{width:50px!important;height:50px!important}
.col-3 img{width:40px!important;height:40px!important}
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?php echo SITE_URL; ?>"><i class="fas fa-futbol"></i> Fútbol Manager</a>
    <div class="navbar-nav ms-auto">
      <a class="nav-link" href="#" onclick="history.back(); return false;"><i class="fas fa-arrow-left"></i> Volver</a>
    </div>
  </div>
</nav>
<div class="container py-3">
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="row align-items-center">
        <div class="col-3 col-md-1 text-center">
          <?php if ($jugador['foto']): ?>
            <img src="../uploads/<?php echo htmlspecialchars($jugador['foto']); ?>" class="rounded-circle border" style="width:60px; height:60px; object-fit:cover;">
          <?php else: ?>
            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width:60px;height:60px;"><i class="fas fa-user text-muted"></i></div>
          <?php endif; ?>
        </div>
        <div class="col-9 col-md-5">
          <h5 class="mb-1"><?php echo htmlspecialchars($jugador['apellido_nombre']); ?></h5>
          <small class="text-muted">DNI: <?php echo htmlspecialchars($jugador['dni']); ?> · Edad: <?php echo calculateAge($jugador['fecha_nacimiento']); ?></small>
          <div class="small mt-1">
            <i class="fas fa-shield-alt"></i> Equipo actual: <?php echo htmlspecialchars($jugador['equipo_actual'] ?: 'Sin equipo'); ?>
          </div>
        </div>
        <div class="col-md-6 mt-2 mt-md-0">
          <div class="chips d-flex flex-wrap gap-2 justify-content-md-end">
            <strong class="me-1"><i class="fas fa-trophy"></i> Torneos:</strong>
            <?php foreach($torneos as $cid=>$cname): ?>
              <a class="badge bg-light text-dark" href="?id=<?php echo $jugador_id; ?>&camp_id=<?php echo (int)$cid; ?>"><?php echo htmlspecialchars($cname); ?></a>
            <?php endforeach; ?>
            <strong class="ms-3 me-1"><i class="fas fa-users"></i> Equipos:</strong>
            <?php foreach($equipos as $eid=>$ename): ?>
              <a class="badge bg-light text-dark" href="?id=<?php echo $jugador_id; ?>&team_id=<?php echo (int)$eid; ?>"><?php echo htmlspecialchars($ename); ?></a>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
      <h6 class="mb-0"><i class="fas fa-chart-line"></i> Estadísticas destacadas</h6>
      <small class="text-muted"><?php echo htmlspecialchars($scopeLabel); ?></small>
    </div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-number"><?php echo $dest['partidos']; ?></div><div class="text-muted">Partidos</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-number text-success"><?php echo $dest['goles']; ?></div><div class="text-muted">Goles</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-number text-warning"><?php echo $dest['amarillas']; ?></div><div class="text-muted">Amarillas</div></div></div>
        <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-number text-danger"><?php echo $dest['rojas']; ?></div><div class="text-muted">Rojas</div></div></div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white"><h6 class="mb-0"><i class="fas fa-stream"></i> Timeline</h6></div>
    <div class="card-body">
      <?php if (empty($historial)): ?>
        <p class="text-muted mb-0">Sin historial disponible.</p>
      <?php else: ?>
        <?php foreach($historial as $reg): ?>
          <div class="timeline-item">
            <div class="timeline-marker"></div>
            <div class="row align-items-center">
              <div class="col-md-4">
                <strong><?php echo htmlspecialchars($reg['equipo_nombre']); ?></strong>
                <div class="small text-muted"><i class="fas fa-trophy"></i> <?php echo htmlspecialchars($reg['campeonato_nombre']); ?> · <?php echo htmlspecialchars($reg['categoria_nombre']); ?></div>
              </div>
              <div class="col-md-3 small text-muted">
                <i class="fas fa-calendar-alt"></i>
                <?php echo date('d/m/Y', strtotime($reg['fecha_inicio'])); ?>
                <?php if ($reg['fecha_fin']): ?> - <?php echo date('d/m/Y', strtotime($reg['fecha_fin'])); ?><?php else: ?> - <span class="badge bg-success">Actual</span><?php endif; ?>
              </div>
              <div class="col-md-5">
                <div class="row g-2 text-center">
                  <div class="col-3"><span class="badge bg-primary w-100"><?php echo (int)$reg['partidos_jugados']; ?></span><div class="small">PJ</div></div>
                  <div class="col-3"><span class="badge bg-success w-100"><?php echo (int)$reg['goles']; ?></span><div class="small">Goles</div></div>
                  <div class="col-3"><span class="badge bg-warning text-dark w-100"><?php echo (int)$reg['amarillas']; ?></span><div class="small">TA</div></div>
                  <div class="col-3"><span class="badge bg-danger w-100"><?php echo (int)$reg['rojas']; ?></span><div class="small">TR</div></div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
