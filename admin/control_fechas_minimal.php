<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/include/sanciones_functions.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Cargar datos básicos
try {
    $stmt = $db->query("SELECT id, nombre FROM campeonatos WHERE activo = 1 ORDER BY nombre");
    $campeonatos = $stmt->fetchAll();
} catch (PDOException $e) {
    $campeonatos = [];
    $error = 'Error: ' . $e->getMessage();
}

$campeonato_id = $_GET['campeonato'] ?? null;
$categoria_id = $_GET['categoria'] ?? null;
$fecha_id = $_GET['fecha'] ?? null;
$categorias = [];
$fechas_categoria = [];
$partidos = [];

if ($campeonato_id) {
    try {
        $stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1");
        $stmt->execute([$campeonato_id]);
        $categorias = $stmt->fetchAll();
    } catch (PDOException $e) {
        $categorias = [];
    }
}

if ($categoria_id) {
    try {
        $stmt = $db->prepare("SELECT id, numero_fecha, fecha_programada FROM fechas WHERE categoria_id = ? ORDER BY numero_fecha");
        $stmt->execute([$categoria_id]);
        $fechas_categoria = $stmt->fetchAll();
    } catch (PDOException $e) {
        $fechas_categoria = [];
    }
}

if ($fecha_id) {
    try {
        $stmt = $db->prepare("
            SELECT p.*, el.nombre as equipo_local, ev.nombre as equipo_visitante,
                   can.nombre as cancha, el.color_camiseta as color_local, ev.color_camiseta as color_visitante,
                   el.id as equipo_local_id, ev.id as equipo_visitante_id
            FROM partidos p
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            LEFT JOIN canchas can ON p.cancha_id = can.id
            WHERE p.fecha_id = ?
            ORDER BY p.hora_partido ASC
        ");
        $stmt->execute([$fecha_id]);
        $partidos = $stmt->fetchAll();
    } catch (PDOException $e) {
        $partidos = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Fechas - Versión Minimal</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Control de Fechas - Versión Minimal</h1>
        
        <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-3">
                <label>Campeonato</label>
                <select name="campeonato" class="form-select" onchange="this.form.submit()">
                    <option value="">Seleccionar</option>
                    <?php foreach($campeonatos as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $campeonato_id==$c['id']?'selected':'' ?>>
                            <?= htmlspecialchars($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <?php if($campeonato_id): ?>
            <div class="col-md-3">
                <label>Categoría</label>
                <select name="categoria" class="form-select" onchange="this.form.submit()">
                    <option value="">Seleccionar</option>
                    <?php foreach($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $categoria_id==$cat['id']?'selected':'' ?>>
                            <?= htmlspecialchars($cat['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="campeonato" value="<?= $campeonato_id ?>">
            </div>
            <?php endif; ?>
            
            <?php if($categoria_id): ?>
            <div class="col-md-3">
                <label>Fecha</label>
                <select name="fecha" class="form-select" onchange="this.form.submit()">
                    <option value="">Seleccionar</option>
                    <?php foreach($fechas_categoria as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= $fecha_id==$f['id']?'selected':'' ?>>
                            Fecha <?= $f['numero_fecha'] ?> (<?= date('d/m/Y', strtotime($f['fecha_programada'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="hidden" name="campeonato" value="<?= $campeonato_id ?>">
                <input type="hidden" name="categoria" value="<?= $categoria_id ?>">
            </div>
            <?php endif; ?>
        </form>
        
        <?php if($partidos): ?>
        <h3>Partidos (<?= count($partidos) ?>)</h3>
        <div class="row">
            <?php foreach($partidos as $p): ?>
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-body">
                        <h5><?= htmlspecialchars($p['equipo_local']) ?> vs <?= htmlspecialchars($p['equipo_visitante']) ?></h5>
                        <p>Cancha: <?= htmlspecialchars($p['cancha'] ?? 'Sin asignar') ?></p>
                        <p>Hora: <?= $p['hora_partido'] ? date('H:i', strtotime($p['hora_partido'])) : 'Sin horario' ?></p>
                        <?php if($p['estado'] == 'finalizado'): ?>
                            <p><strong>Resultado: <?= $p['goles_local'] ?> - <?= $p['goles_visitante'] ?></strong></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php elseif($fecha_id): ?>
        <div class="alert alert-warning">No hay partidos para esta fecha</div>
        <?php else: ?>
        <div class="alert alert-info">Selecciona campeonato, categoría y fecha para ver los partidos</div>
        <?php endif; ?>
        
        <hr>
        <p><a href="control_fechas.php">Ir a versión completa</a></p>
    </div>
</body>
</html>

