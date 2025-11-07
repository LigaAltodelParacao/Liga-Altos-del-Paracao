<?php
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
    
    switch ($action) {
        case 'create':
            $nombre = trim($_POST['nombre']);
            $ubicacion = trim($_POST['ubicacion']);
            
            if (empty($nombre)) {
                $error = 'El nombre de la cancha es obligatorio';
            } else {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO canchas (nombre, ubicacion) 
                        VALUES (?, ?)
                    ");
                    $stmt->execute([$nombre, $ubicacion]);
                    $message = 'Cancha creada exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al crear cancha: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update':
            $id = $_POST['id'];
            $nombre = trim($_POST['nombre']);
            $ubicacion = trim($_POST['ubicacion']);
            $activa = isset($_POST['activa']) ? 1 : 0;
            
            try {
                $stmt = $db->prepare("
                    UPDATE canchas 
                    SET nombre = ?, ubicacion = ?, activa = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nombre, $ubicacion, $activa, $id]);
                $message = 'Cancha actualizada exitosamente';
            } catch (Exception $e) {
                $error = 'Error al actualizar cancha: ' . $e->getMessage();
            }
            break;
            
        case 'delete':
            $id = $_POST['id'];
            try {
                // Verificar si hay partidos asignados
                $stmt = $db->prepare("SELECT COUNT(*) FROM partidos WHERE cancha_id = ?");
                $stmt->execute([$id]);
                $partidos_count = $stmt->fetchColumn();
                
                if ($partidos_count > 0) {
                    $error = 'No se puede eliminar la cancha porque tiene partidos asignados';
                } else {
                    $stmt = $db->prepare("DELETE FROM canchas WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Cancha eliminada exitosamente';
                }
            } catch (Exception $e) {
                $error = 'Error al eliminar cancha: ' . $e->getMessage();
            }
            break;
    }
}

// Obtener canchas con estadísticas
$stmt = $db->query("
    SELECT c.*,
           COUNT(DISTINCT CASE WHEN p.fecha_partido >= CURDATE() THEN p.id END) as partidos_programados,
           COUNT(DISTINCT CASE WHEN p.fecha_partido = CURDATE() THEN p.id END) as partidos_hoy,
           COUNT(DISTINCT p.id) as total_partidos
    FROM canchas c
    LEFT JOIN partidos p ON c.id = p.cancha_id
    GROUP BY c.id
    ORDER BY c.nombre
");
$canchas = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Canchas - Sistema de Campeonatos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-futbol"></i> Fútbol Manager - Admin
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-map-marker-alt"></i> Gestión de Canchas</h2>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCancha">
                        <i class="fas fa-plus"></i> Nueva Cancha
                    </button>
                </div>

                <!-- Mensajes -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas Rápidas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-map-marker-alt fa-2x me-3"></i>
                                    <div>
                                        <h3 class="mb-0"><?php echo count($canchas); ?></h3>
                                        <small>Total Canchas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-check-circle fa-2x me-3"></i>
                                    <div>
                                        <h3 class="mb-0"><?php echo count(array_filter($canchas, fn($c) => $c['activa'])); ?></h3>
                                        <small>Activas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-calendar-day fa-2x me-3"></i>
                                    <div>
                                        <h3 class="mb-0"><?php echo array_sum(array_column($canchas, 'partidos_hoy')); ?></h3>
                                        <small>Partidos Hoy</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-calendar-alt fa-2x me-3"></i>
                                    <div>
                                        <h3 class="mb-0"><?php echo array_sum(array_column($canchas, 'partidos_programados')); ?></h3>
                                        <small>Programados</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Canchas -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($canchas)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay canchas registradas</h5>
                                <p class="text-muted">Crea tu primera cancha para comenzar a programar partidos</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Ubicación</th>
                                            <th class="text-center">Partidos Hoy</th>
                                            <th class="text-center">Programados</th>
                                            <th class="text-center">Total</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($canchas as $cancha): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-success rounded-circle me-3 d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px;">
                                                        <i class="fas fa-futbol text-white"></i>
                                                    </div>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($cancha['nombre']); ?></strong>
                                                        <?php if ($cancha['partidos_hoy'] > 0): ?>
                                                            <br><small class="text-danger"><i class="fas fa-circle"></i> En uso hoy</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($cancha['ubicacion']): ?>
                                                    <i class="fas fa-map-marker-alt text-muted"></i>
                                                    <?php echo htmlspecialchars($cancha['ubicacion']); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin ubicación</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($cancha['partidos_hoy'] > 0): ?>
                                                    <span class="badge bg-danger"><?php echo $cancha['partidos_hoy']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($cancha['partidos_programados'] > 0): ?>
                                                    <span class="badge bg-warning"><?php echo $cancha['partidos_programados']; ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-info"><?php echo $cancha['total_partidos']; ?></span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($cancha['activa']): ?>
                                                    <span class="badge bg-success">Activa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactiva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editarCancha(<?php echo $cancha['id']; ?>)"
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="partidos.php?cancha=<?php echo $cancha['id']; ?>" 
                                                       class="btn btn-sm btn-outline-info" title="Ver partidos">
                                                        <i class="fas fa-calendar-alt"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="eliminarCancha(<?php echo $cancha['id']; ?>, '<?php echo htmlspecialchars($cancha['nombre']); ?>')"
                                                            title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
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

    <!-- Modal Cancha -->
    <div class="modal fade" id="modalCancha" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCanchaTitle">
                        <i class="fas fa-map-marker-alt"></i> Nueva Cancha
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formCancha">
                    <input type="hidden" name="action" id="canchaAction" value="create">
                    <input type="hidden" name="id" id="canchaId">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Cancha *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   placeholder="Ej: Cancha Principal, Cancha Norte" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="ubicacion" class="form-label">Ubicación</label>
                            <textarea class="form-control" id="ubicacion" name="ubicacion" rows="3"
                                      placeholder="Dirección, referencias, descripción del lugar..."></textarea>
                            <div class="form-text">
                                Incluye dirección completa, referencias o cualquier información útil para ubicar la cancha
                            </div>
                        </div>
                        
                        <div class="mb-3" id="activaContainer" style="display: none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activa" name="activa" checked>
                                <label class="form-check-label" for="activa">
                                    Cancha Activa
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Form para eliminar -->
    <form method="POST" id="formEliminar" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="eliminarId">
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarCancha(id) {
            // Buscar datos de la cancha
            <?php foreach ($canchas as $cancha): ?>
            if (id == <?php echo $cancha['id']; ?>) {
                document.getElementById('modalCanchaTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Cancha';
                document.getElementById('canchaAction').value = 'update';
                document.getElementById('canchaId').value = <?php echo $cancha['id']; ?>;
                document.getElementById('nombre').value = '<?php echo htmlspecialchars($cancha['nombre'], ENT_QUOTES); ?>';
                document.getElementById('ubicacion').value = '<?php echo htmlspecialchars($cancha['ubicacion'], ENT_QUOTES); ?>';
                document.getElementById('activa').checked = <?php echo $cancha['activa'] ? 'true' : 'false'; ?>;
                document.getElementById('activaContainer').style.display = 'block';
                
                var modal = new bootstrap.Modal(document.getElementById('modalCancha'));
                modal.show();
                return;
            }
            <?php endforeach; ?>
        }

        function eliminarCancha(id, nombre) {
            if (confirm('¿Estás seguro de que deseas eliminar la cancha "' + nombre + '"?\n\nEsta acción solo será posible si no tiene partidos asignados.')) {
                document.getElementById('eliminarId').value = id;
                document.getElementById('formEliminar').submit();
            }
        }

        // Limpiar formulario al cerrar modal
        document.getElementById('modalCancha').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formCancha').reset();
            document.getElementById('modalCanchaTitle').innerHTML = '<i class="fas fa-map-marker-alt"></i> Nueva Cancha';
            document.getElementById('canchaAction').value = 'create';
            document.getElementById('canchaId').value = '';
            document.getElementById('activaContainer').style.display = 'none';
        });

        // Auto-refresh cada 5 minutos para actualizar estadísticas
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>