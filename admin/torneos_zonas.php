<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

$message = '';
$error = '';

// Obtener campeonatos activos
$campeonatos = $db->query("SELECT * FROM campeonatos WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Obtener torneos con zonas existentes
$stmt = $db->query("
    SELECT 
        cf.*,
        c.nombre as campeonato_nombre,
        cat.nombre as categoria_nombre,
        (SELECT COUNT(*) FROM zonas WHERE formato_id = cf.id) as total_zonas,
        (SELECT COUNT(DISTINCT equipo_id) FROM equipos_zonas ez 
         JOIN zonas z ON ez.zona_id = z.id 
         WHERE z.formato_id = cf.id) as total_equipos
    FROM campeonatos_formato cf
    JOIN campeonatos c ON cf.campeonato_id = c.id
    JOIN categorias cat ON cat.campeonato_id = c.id
    WHERE cf.activo = 1
    ORDER BY cf.created_at DESC
");
$torneos_zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Eliminar torneo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_torneo'])) {
    $formato_id = $_POST['formato_id'];
    
    try {
        $db->beginTransaction();
        
        // Eliminar en cascada (las FK ya lo hacen, pero por seguridad)
        // Eliminar partidos eliminatorios
        $db->prepare("DELETE FROM partidos WHERE fase_eliminatoria_id IN (SELECT id FROM fases_eliminatorias WHERE formato_id = ?)")->execute([$formato_id]);
        // Eliminar partidos de zonas
        $db->prepare("DELETE FROM partidos WHERE zona_id IN (SELECT id FROM zonas WHERE formato_id = ?) AND tipo_torneo = 'zona'")->execute([$formato_id]);
        // Eliminar fechas de zonas
        $db->prepare("DELETE FROM fechas WHERE zona_id IN (SELECT id FROM zonas WHERE formato_id = ?) AND tipo_fecha = 'zona'")->execute([$formato_id]);
        // Eliminar fases eliminatorias
        $db->prepare("DELETE FROM fases_eliminatorias WHERE formato_id = ?")->execute([$formato_id]);
        // Eliminar equipos de zonas
        $db->prepare("DELETE FROM equipos_zonas WHERE zona_id IN (SELECT id FROM zonas WHERE formato_id = ?)")->execute([$formato_id]);
        // Eliminar zonas
        $db->prepare("DELETE FROM zonas WHERE formato_id = ?")->execute([$formato_id]);
        // Eliminar formato
        $db->prepare("DELETE FROM campeonatos_formato WHERE id = ?")->execute([$formato_id]);
        
        $db->commit();
        $message = 'Torneo eliminado exitosamente';
        header("Location: torneos_zonas.php?msg=deleted");
        exit;
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error al eliminar: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Torneos con Zonas y Eliminatorias</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-futbol"></i> Fútbol Manager
            </a>
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
                <h2><i class="fas fa-layer-group"></i> Torneos con Zonas y Eliminatorias</h2>

                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Torneo creado exitosamente
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

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

                <!-- Botón Crear Nuevo Torneo -->
                <div class="card mb-4">
                    <div class="card-body">
                        <a href="crear_torneo_zonas.php" class="btn btn-success btn-lg">
                            <i class="fas fa-plus-circle"></i> Crear Nuevo Torneo con Zonas
                        </a>
                        <p class="text-muted mt-2 mb-0">
                            <i class="fas fa-info-circle"></i> Crea torneos con fase de grupos (zonas) y fases eliminatorias (octavos, cuartos, semis, final)
                        </p>
                    </div>
                </div>

                <!-- Lista de Torneos -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Torneos Activos</h5>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($torneos_zonas)): ?>
                            <div class="p-4 text-center">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay torneos con zonas creados</h5>
                                <p class="text-muted">Crea tu primer torneo usando el botón de arriba</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>Torneo</th>
                                            <th class="text-center">Zonas</th>
                                            <th class="text-center">Equipos</th>
                                            <th class="text-center">Clasifican</th>
                                            <th class="text-center">Fases</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($torneos_zonas as $torneo): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($torneo['campeonato_nombre']) ?></strong><br>
                                                    <small class="text-muted"><?= htmlspecialchars($torneo['categoria_nombre']) ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info"><?= $torneo['cantidad_zonas'] ?> zonas</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-secondary"><?= $torneo['total_equipos'] ?> equipos</span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success"><?= $torneo['equipos_clasifican'] ?></span>
                                                </td>
                                                <td class="text-center">
                                                    <small>
                                                        <?php
                                                        $fases = [];
                                                        if ($torneo['tiene_octavos']) $fases[] = '1/8';
                                                        if ($torneo['tiene_cuartos']) $fases[] = '1/4';
                                                        if ($torneo['tiene_semifinal']) $fases[] = 'Semis';
                                                        echo implode(' → ', $fases) . ' → Final';
                                                        ?>
                                                    </small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-<?= $torneo['activo'] ? 'success' : 'secondary' ?>">
                                                        <?= $torneo['activo'] ? 'Activo' : 'Inactivo' ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <a href="torneos_zonas_ver.php?id=<?= $torneo['id'] ?>" 
                                                       class="btn btn-sm btn-primary" title="Ver Detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="campeonatos_zonas_fixture.php?formato_id=<?= $torneo['id'] ?>" 
                                                       class="btn btn-sm btn-success" title="Generar Fixture">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </a>
                                                    <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar este torneo? Se perderán todos los datos asociados.');">
                                                        <input type="hidden" name="formato_id" value="<?= $torneo['id'] ?>">
                                                        <button type="submit" name="delete_torneo" class="btn btn-sm btn-danger" title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
