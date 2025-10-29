<?php
// gestion_codigos_cancha.php
date_default_timezone_set('America/Argentina/Buenos_Aires'); // Ajuste zona horaria

require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'generar_codigos') {
        try {
            $fecha_seleccionada = $_POST['fecha_partidos'];
            
            // Obtener canchas que tienen partidos en esa fecha
            $stmt = $db->prepare("
                SELECT DISTINCT c.id, c.nombre, 
                       COUNT(p.id) as total_partidos,
                       MIN(p.hora_partido) as primer_partido,
                       MAX(p.hora_partido) as ultimo_partido
                FROM canchas c
                JOIN partidos p ON c.id = p.cancha_id
                WHERE DATE(p.fecha_partido) = ?
                GROUP BY c.id, c.nombre
                ORDER BY c.nombre
            ");
            $stmt->execute([$fecha_seleccionada]);
            $canchas_con_partidos = $stmt->fetchAll();
            
            if (empty($canchas_con_partidos)) {
                throw new Exception("No hay partidos programados para esa fecha");
            }
            
            $db->beginTransaction();
            
            // Eliminar códigos antiguos de esa fecha
            $stmt = $db->prepare("DELETE FROM codigos_cancha WHERE fecha_partidos = ?");
            $stmt->execute([$fecha_seleccionada]);
            
            $codigos_generados = [];
            foreach ($canchas_con_partidos as $cancha) {
                // Generar código único de 6 caracteres
                do {
                    $codigo = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
                    $stmt = $db->prepare("SELECT COUNT(*) FROM codigos_cancha WHERE codigo = ?");
                    $stmt->execute([$codigo]);
                } while ($stmt->fetchColumn() > 0);
                
                // Calcular hora de expiración (1 hora después del último partido)
                $ultimo_partido = new DateTime($fecha_seleccionada . ' ' . $cancha['ultimo_partido']);
                $ultimo_partido->add(new DateInterval('PT1H'));
                
                // Insertar código usando DATETIME
                $stmt = $db->prepare("
                    INSERT INTO codigos_cancha (cancha_id, codigo, fecha_partidos, expires_at)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $cancha['id'],
                    $codigo,
                    $fecha_seleccionada,
                    $ultimo_partido->format('Y-m-d H:i:s')
                ]);
                
                $codigos_generados[] = [
                    'cancha' => $cancha['nombre'],
                    'codigo' => $codigo,
                    'partidos' => $cancha['total_partidos'],
                    'horario' => date('H:i', strtotime($cancha['primer_partido'])) . ' - ' . date('H:i', strtotime($cancha['ultimo_partido'])),
                    'expira' => $ultimo_partido->format('d/m/Y H:i')
                ];
            }
            
            $db->commit();
            $message = 'Códigos generados exitosamente para ' . count($codigos_generados) . ' canchas';
            
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Error: ' . $e->getMessage();
        }
    }
    
    if ($action == 'desactivar_codigo') {
        try {
            $codigo_id = $_POST['codigo_id'];
            $stmt = $db->prepare("UPDATE codigos_cancha SET activo = 0 WHERE id = ?");
            $stmt->execute([$codigo_id]);
            $message = 'Código desactivado correctamente';
        } catch (Exception $e) {
            $error = 'Error al desactivar código: ' . $e->getMessage();
        }
    }
}

// Obtener códigos activos
$stmt = $db->query("
    SELECT cc.*, c.nombre as cancha_nombre,
           COUNT(p.id) as total_partidos,
           SUM(CASE WHEN p.estado = 'finalizado' THEN 1 ELSE 0 END) as partidos_finalizados
    FROM codigos_cancha cc
    JOIN canchas c ON cc.cancha_id = c.id
    LEFT JOIN partidos p ON c.id = p.cancha_id AND DATE(p.fecha_partido) = cc.fecha_partidos
    WHERE cc.activo = 1 AND cc.fecha_partidos >= CURDATE()
    GROUP BY cc.id
    ORDER BY cc.fecha_partidos DESC, c.nombre
");
$codigos_activos = $stmt->fetchAll();

// Obtener fechas con partidos para generar códigos
$stmt = $db->query("
    SELECT DISTINCT DATE(p.fecha_partido) as fecha, COUNT(DISTINCT p.cancha_id) as canchas
    FROM partidos p
    WHERE DATE(p.fecha_partido) >= CURDATE()
    GROUP BY DATE(p.fecha_partido)
    ORDER BY DATE(p.fecha_partido)
");
$fechas_disponibles = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Códigos de Planilleros - Sistema de Campeonatos</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
.codigo-activo { background-color: #d4edda; }
.codigo-usado { background-color: #fff3cd; }
.codigo-expirado { background-color: #f8d7da; }
.codigo-display { 
    font-family: 'Courier New', monospace; 
    font-size: 1.2rem; 
    font-weight: bold; 
    letter-spacing: 0.1rem; 
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

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-key"></i> Códigos de Planilleros</h2>
</div>

<?php if($message): ?>
<div class="alert alert-success alert-dismissible fade show">
    <i class="fas fa-check-circle"></i> <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if($error): ?>
<div class="alert alert-danger alert-dismissible fade show">
    <i class="fas fa-exclamation-triangle"></i> <?= $error ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Generar nuevos códigos -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h5><i class="fas fa-plus-circle"></i> Generar Códigos de Acceso</h5>
    </div>
    <div class="card-body">
        <form method="POST">
            <input type="hidden" name="action" value="generar_codigos">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Fecha de los Partidos</label>
                    <select name="fecha_partidos" class="form-select" required>
                        <option value="">Seleccionar fecha</option>
                        <?php foreach($fechas_disponibles as $fecha): ?>
                        <option value="<?= $fecha['fecha'] ?>">
                            <?= date('d/m/Y', strtotime($fecha['fecha'])) ?> 
                            (<?= $fecha['canchas'] ?> cancha/s con partidos)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-key"></i> Generar Códigos
                    </button>
                </div>
            </div>
            <div class="mt-3">
                <small class="text-muted">
                    <i class="fas fa-info-circle"></i> 
                    Se generará un código único por cancha. Los códigos se desactivan automáticamente 1 hora después del último partido.
                </small>
            </div>
        </form>
    </div>
</div>

<!-- Lista de códigos activos -->
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list"></i> Códigos Activos (<?= count($codigos_activos) ?>)</h5>
    </div>
    <div class="card-body p-0">
        <?php if(empty($codigos_activos)): ?>

        <div class="text-center p-4">
            <i class="fas fa-key fa-3x text-muted mb-3"></i>
            <p class="text-muted">No hay códigos de acceso activos</p>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Cancha</th>
                        <th>Código</th>
                        <th>Fecha</th>
                        <th>Partidos</th>
                        <th>Estado</th>
                        <th>Expira</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($codigos_activos as $codigo): 
                        $ahora = new DateTime('now', new DateTimeZone('America/Argentina/Buenos_Aires'));
                        $expira = new DateTime($codigo['expires_at'], new DateTimeZone('America/Argentina/Buenos_Aires'));
                        $expirado = $ahora > $expira;
                        
                        $clase = '';
                        if ($expirado) {
                            $clase = 'codigo-expirado';
                        } elseif ($codigo['usado']) {
                            $clase = 'codigo-usado';
                        } else {
                            $clase = 'codigo-activo';
                        }
                    ?>
                    <tr class="<?= $clase ?>">
                        <td><?= htmlspecialchars($codigo['cancha_nombre']) ?></td>
                        <td>
                            <span class="codigo-display"><?= $codigo['codigo'] ?></span>
                            <button class="btn btn-sm btn-outline-secondary ms-2" 
                                    onclick="copiarCodigo('<?= $codigo['codigo'] ?>')" 
                                    title="Copiar código">
                                <i class="fas fa-copy"></i>
                            </button>
                        </td>
                        <td><?= date('d/m/Y', strtotime($codigo['fecha_partidos'])) ?></td>
                        <td>
                            <span class="badge bg-info">
                                <?= $codigo['partidos_finalizados'] ?>/<?= $codigo['total_partidos'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($expirado): ?>
                                <span class="badge bg-danger">Expirado</span>
                            <?php elseif ($codigo['usado']): ?>
                                <span class="badge bg-warning text-dark">En Uso</span>
                            <?php else: ?>
                                <span class="badge bg-success">Disponible</span>
                            <?php endif; ?>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($codigo['expires_at'])) ?></td>
                        <td>
                            <?php if (!$expirado && $codigo['activo']): ?>
                            <form method="POST" class="d-inline" onsubmit="return confirm('¿Desactivar este código?')">
                                <input type="hidden" name="action" value="desactivar_codigo">
                                <input type="hidden" name="codigo_id" value="<?= $codigo['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-ban"></i>
                                </button>
                            </form>
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

<?php if(isset($codigos_generados)): ?>
<!-- Códigos recién generados -->
<div class="modal fade" id="modalCodigosGenerados" tabindex="-1">
<div class="modal-dialog modal-lg">
<div class="modal-content">
<div class="modal-header bg-success text-white">
    <h5 class="modal-title">Códigos Generados</h5>
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>
<div class="modal-body">
    <p><strong>Códigos generados para <?= date('d/m/Y', strtotime($fecha_seleccionada)) ?>:</strong></p>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Cancha</th>
                    <th>Código</th>
                    <th>Partidos</th>
                    <th>Horario</th>
                    <th>Expira</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($codigos_generados as $cod): ?>
                <tr>
                    <td><?= $cod['cancha'] ?></td>
                    <td><span class="codigo-display"><?= $cod['codigo'] ?></span></td>
                    <td><?= $cod['partidos'] ?></td>
                    <td><?= $cod['horario'] ?></td>
                    <td><?= $cod['expira'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        <strong>Importante:</strong> Entrega estos códigos a los planilleros correspondientes. 
        Los códigos se desactivarán automáticamente después del horario indicado.
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
    <button type="button" class="btn btn-primary" onclick="imprimirCodigos()">
        <i class="fas fa-print"></i> Imprimir
    </button>
</div>
</div>
</div>
</div>
<?php endif; ?>

</div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
<script>
<?php if(isset($codigos_generados)): ?>
// Mostrar modal automáticamente
document.addEventListener('DOMContentLoaded', function() {
    new bootstrap.Modal(document.getElementById('modalCodigosGenerados')).show();
});
<?php endif; ?>

function copiarCodigo(codigo) {
    navigator.clipboard.writeText(codigo).then(function() {
        const btn = event.target.closest('button');
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-success"></i>';
        setTimeout(() => {
            btn.innerHTML = originalIcon;
        }, 2000);
    });
}

function imprimirCodigos() {
    window.print();
}

// Auto-refresh cada 5 minutos
setInterval(function() {
    location.reload();
}, 300000);
</script>
</body>
</html>
