<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Obtener campeonatos activos
$stmt = $db->query("SELECT * FROM campeonatos WHERE activo = 1 ORDER BY nombre");
$campeonatos = $stmt->fetchAll();

// Procesar selección de campeonato y categoría
$campeonato_id = $_GET['campeonato'] ?? null;
$categoria_id = $_GET['categoria'] ?? null;

// Guardar cambios de cancha/hora
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reprogramar'])) {
    $partido_id = $_POST['partido_id'];
    $cancha_id = $_POST['cancha_id'] ?: null;
    $hora = $_POST['hora'] ?: null;

    try {
        // Verificar disponibilidad de horario para esa cancha y fecha
        $stmtCheck = $db->prepare("
            SELECT COUNT(*) FROM partidos
            WHERE cancha_id = ? AND fecha_partido = (SELECT fecha_programada FROM partidos p
                                                     JOIN fechas f ON p.fecha_id = f.id
                                                     WHERE p.id = ?)
            AND hora_partido = ? AND id <> ?
        ");
        $stmtCheck->execute([$cancha_id, $partido_id, $hora, $partido_id]);
        $ocupado = $stmtCheck->fetchColumn();

        if ($ocupado) {
            $error = "El horario seleccionado ya está ocupado para esa cancha.";
        } else {
            $stmt = $db->prepare("
                UPDATE partidos
                SET cancha_id = ?, hora_partido = ?
                WHERE id = ?
            ");
            $stmt->execute([$cancha_id, $hora, $partido_id]);
            $message = "Reprogramación guardada correctamente.";
        }

    } catch (Exception $e) {
        $error = "Error al reprogramar: " . $e->getMessage();
    }
}

// Obtener categorías según campeonato
$categorias = [];
if ($campeonato_id) {
    $stmt = $db->prepare("SELECT * FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
    $stmt->execute([$campeonato_id]);
    $categorias = $stmt->fetchAll();
}

// Obtener fechas y partidos de la categoría seleccionada
$fechas = [];
if ($categoria_id) {
    $stmt = $db->prepare("SELECT * FROM fechas WHERE categoria_id = ? ORDER BY numero_fecha");
    $stmt->execute([$categoria_id]);
    $fechas = $stmt->fetchAll();

    foreach ($fechas as &$fecha) {
        $stmtP = $db->prepare("SELECT p.*, e1.nombre as local, e2.nombre as visitante, c.nombre as cancha
            FROM partidos p
            JOIN equipos e1 ON p.equipo_local_id = e1.id
            JOIN equipos e2 ON p.equipo_visitante_id = e2.id
            LEFT JOIN canchas c ON p.cancha_id = c.id
            WHERE p.fecha_id = ?
            ORDER BY p.id
        ");
        $stmtP->execute([$fecha['id']]);
        $fecha['partidos'] = $stmtP->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reprogramar Fechas</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-warning">
<div class="container-fluid">
<a class="navbar-brand" href="<?php echo SITE_URL; ?>"><i class="fas fa-futbol"></i> Fútbol Manager - Admin</a>
<div class="navbar-nav ms-auto">
<a class="nav-link" href="dashboard.php">Dashboard</a>
<a class="nav-link" href="<?php echo SITE_URL; ?>logout.php">Salir</a>
</div>
</div>
</nav>

<div class="container-fluid mt-4">
    <?php if($message): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $message ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if($error): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white"><i class="fas fa-trophy"></i> Seleccionar Campeonato y Categoría</div>
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label>Campeonato</label>
                    <select name="campeonato" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Seleccione --</option>
                        <?php foreach($campeonatos as $camp): ?>
                            <option value="<?= $camp['id'] ?>" <?= ($camp['id']==$campeonato_id)?'selected':'' ?>><?= htmlspecialchars($camp['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Categoría</label>
                    <select name="categoria" class="form-select" onchange="this.form.submit()">
                        <option value="">-- Seleccione --</option>
                        <?php foreach($categorias as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($cat['id']==$categoria_id)?'selected':'' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if($categoria_id && !empty($fechas)): ?>
        <?php foreach($fechas as $fecha): ?>
            <div class="card mb-3">
                <div class="card-header bg-info text-white">
                    Fecha <?= $fecha['numero_fecha'] ?> - <?= date('d/m/Y', strtotime($fecha['fecha_programada'])) ?>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Partido</th>
                                <th>Cancha</th>
                                <th>Hora</th>
                                <th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($fecha['partidos'] as $partido): ?>
                                <tr>
                                    <td><?= htmlspecialchars($partido['local']) ?> vs <?= htmlspecialchars($partido['visitante']) ?></td>
                                    <form method="POST">
                                        <input type="hidden" name="partido_id" value="<?= $partido['id'] ?>">
                                        <td>
                                            <select name="cancha_id" class="form-select">
                                                <option value="">-- Seleccione --</option>
                                                <?php
                                                $stmtC = $db->query("SELECT * FROM canchas WHERE activa = 1 ORDER BY nombre");
                                                $canchasList = $stmtC->fetchAll();
                                                foreach($canchasList as $c):
                                                ?>
                                                    <option value="<?= $c['id'] ?>" <?= ($c['id']==$partido['cancha_id'])?'selected':'' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="time" name="hora" class="form-control" value="<?= $partido['hora_partido'] ?>">
                                        </td>
                                        <td>
                                            <button type="submit" name="reprogramar" class="btn btn-primary btn-sm">
                                                <i class="fas fa-sync-alt"></i> Reprogramar
                                            </button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
