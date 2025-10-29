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
            $cancha_id = $_POST['cancha_id'];
            $hora = $_POST['hora'];
            $temporada = $_POST['temporada'];

            if (empty($cancha_id) || empty($hora)) {
                $error = 'Todos los campos son obligatorios';
            } else {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO horarios_canchas (cancha_id, hora, temporada, activa) 
                        VALUES (?, ?, ?, 1)
                    ");
                    $stmt->execute([$cancha_id, $hora, $temporada]);
                    $message = 'Horario creado exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al crear horario: ' . $e->getMessage();
                }
            }
            break;

        case 'update':
            $id = $_POST['id'];
            $cancha_id = $_POST['cancha_id'];
            $hora = $_POST['hora'];
            $temporada = $_POST['temporada'];
            $activa = isset($_POST['activa']) ? 1 : 0;

            try {
                $stmt = $db->prepare("
                    UPDATE horarios_canchas 
                    SET cancha_id = ?, hora = ?, temporada = ?, activa = ?
                    WHERE id = ?
                ");
                $stmt->execute([$cancha_id, $hora, $temporada, $activa, $id]);
                $message = 'Horario actualizado exitosamente';
            } catch (Exception $e) {
                $error = 'Error al actualizar horario: ' . $e->getMessage();
            }
            break;

        case 'delete':
            $id = $_POST['id'];
            try {
                $stmt = $db->prepare("DELETE FROM horarios_canchas WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Horario eliminado exitosamente';
            } catch (Exception $e) {
                $error = 'Error al eliminar horario: ' . $e->getMessage();
            }
            break;
    }
}

// Obtener horarios
$stmt = $db->query("
    SELECT h.*, c.nombre as cancha_nombre
    FROM horarios_canchas h
    JOIN canchas c ON h.cancha_id = c.id
    ORDER BY c.nombre, h.hora
");
$horarios = $stmt->fetchAll();

// Obtener canchas activas
$stmtCanchas = $db->query("SELECT id, nombre FROM canchas WHERE activa = 1 ORDER BY nombre");
$canchas = $stmtCanchas->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Horarios - Sistema de Campeonatos</title>
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
                    <h2><i class="fas fa-clock"></i> Gestión de Horarios</h2>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalHorario">
                        <i class="fas fa-plus"></i> Nuevo Horario
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

                <!-- Lista de Horarios -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($horarios)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-clock fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay horarios cargados</h5>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Cancha</th>
                                            <th>Hora</th>
                                            <th>Temporada</th>
                                            <th class="text-center">Estado</th>
                                            <th class="text-center">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($horarios as $horario): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($horario['cancha_nombre']); ?></td>
                                            <td><?php echo date('H:i', strtotime($horario['hora'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo $horario['temporada'] === 'verano' ? 'bg-warning text-dark' : 'bg-info'; ?>">
                                                    <?php echo ucfirst($horario['temporada']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($horario['activa']): ?>
                                                    <span class="badge bg-success">Activa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactiva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editarHorario(<?php echo $horario['id']; ?>)"
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="eliminarHorario(<?php echo $horario['id']; ?>)"
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

    <!-- Modal Horario -->
    <div class="modal fade" id="modalHorario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalHorarioTitle">
                        <i class="fas fa-clock"></i> Nuevo Horario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formHorario">
                    <input type="hidden" name="action" id="horarioAction" value="create">
                    <input type="hidden" name="id" id="horarioId">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="cancha_id" class="form-label">Cancha *</label>
                            <select class="form-control" id="cancha_id" name="cancha_id" required>
                                <option value="">Seleccione una cancha</option>
                                <?php foreach ($canchas as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="hora" class="form-label">Hora *</label>
                            <input type="time" class="form-control" id="hora" name="hora" required>
                        </div>

                        <div class="mb-3">
                            <label for="temporada" class="form-label">Temporada *</label>
                            <select class="form-control" id="temporada" name="temporada" required>
                                <option value="verano">Verano</option>
                                <option value="invierno">Invierno</option>
                            </select>
                        </div>

                        <div class="mb-3" id="activaContainer" style="display: none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activa" name="activa" checked>
                                <label class="form-check-label" for="activa">Horario Activo</label>
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
        function editarHorario(id) {
            <?php foreach ($horarios as $horario): ?>
            if (id == <?php echo $horario['id']; ?>) {
                document.getElementById('modalHorarioTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Horario';
                document.getElementById('horarioAction').value = 'update';
                document.getElementById('horarioId').value = <?php echo $horario['id']; ?>;
                document.getElementById('cancha_id').value = '<?php echo $horario['cancha_id']; ?>';
                document.getElementById('hora').value = '<?php echo $horario['hora']; ?>';
                document.getElementById('temporada').value = '<?php echo $horario['temporada']; ?>';
                document.getElementById('activa').checked = <?php echo $horario['activa'] ? 'true' : 'false'; ?>;
                document.getElementById('activaContainer').style.display = 'block';

                var modal = new bootstrap.Modal(document.getElementById('modalHorario'));
                modal.show();
                return;
            }
            <?php endforeach; ?>
        }

        function eliminarHorario(id) {
            if (confirm('¿Estás seguro de que deseas eliminar este horario?')) {
                document.getElementById('eliminarId').value = id;
                document.getElementById('formEliminar').submit();
            }
        }

        // Limpiar formulario al cerrar modal
        document.getElementById('modalHorario').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formHorario').reset();
            document.getElementById('modalHorarioTitle').innerHTML = '<i class="fas fa-clock"></i> Nuevo Horario';
            document.getElementById('horarioAction').value = 'create';
            document.getElementById('horarioId').value = '';
            document.getElementById('activaContainer').style.display = 'none';
        });
    </script>
</body>
</html>
