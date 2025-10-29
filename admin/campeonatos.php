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
            $descripcion = trim($_POST['descripcion']);
            $fecha_inicio = $_POST['fecha_inicio'];
                        
            if (empty($nombre) || empty($fecha_inicio)) {
                $error = 'El nombre y fecha de inicio son obligatorios';
            } else {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO campeonatos (nombre, descripcion, fecha_inicio) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$nombre, $descripcion, $fecha_inicio]);
                    $message = 'Campeonato creado exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al crear campeonato: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update':
            $id = $_POST['id'];
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion']);
            $fecha_inicio = $_POST['fecha_inicio'];
            $activo = isset($_POST['activo']) ? 1 : 0;
            
            try {
                $stmt = $db->prepare("
                    UPDATE campeonatos 
                    SET nombre = ?, descripcion = ?, fecha_inicio = ?, activo = ?
                    WHERE id = ?
                ");
                $stmt->execute([$nombre, $descripcion, $fecha_inicio, $activo, $id]);
                $message = 'Campeonato actualizado exitosamente';
            } catch (Exception $e) {
                $error = 'Error al actualizar campeonato: ' . $e->getMessage();
            }
            break;
            
        case 'delete':
            $id = $_POST['id'];
            try {
                $stmt = $db->prepare("DELETE FROM campeonatos WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Campeonato eliminado exitosamente';
            } catch (Exception $e) {
                $error = 'Error al eliminar campeonato: ' . $e->getMessage();
            }
            break;
    }
}

// Obtener campeonatos
$stmt = $db->query("
    SELECT c.*, 
           COUNT(DISTINCT cat.id) as categorias,
           COUNT(DISTINCT e.id) as equipos
    FROM campeonatos c
    LEFT JOIN categorias cat ON c.id = cat.campeonato_id
    LEFT JOIN equipos e ON cat.id = e.categoria_id
    GROUP BY c.id
    ORDER BY c.fecha_inicio ASC
");
$campeonatos = $stmt->fetchAll();

// Para edición
$editando = null;
if (isset($_GET['edit'])) {
    $stmt = $db->prepare("SELECT * FROM campeonatos WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editando = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Campeonatos - Sistema de Campeonatos</title>
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
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 p-0">
                <?php include 'include/sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-trophy"></i> Gestión de Campeonatos</h2>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCampeonato">
                        <i class="fas fa-plus"></i> Nuevo Campeonato
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

                <!-- Lista de Campeonatos -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($campeonatos)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-trophy fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay campeonatos registrados</h5>
                                <p class="text-muted">Crea tu primer campeonato para comenzar</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nombre</th>
                                            <th>Fecha Inicio</th>
                                            <th>Categorías</th>
                                            <th>Equipos</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($campeonatos as $campeonato): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($campeonato['nombre']); ?></strong>
                                                <?php if ($campeonato['descripcion']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($campeonato['descripcion']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo formatDate($campeonato['fecha_inicio']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $campeonato['categorias']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $campeonato['equipos']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($campeonato['activo']): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editarCampeonato(<?php echo $campeonato['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="categorias.php?campeonato=<?php echo $campeonato['id']; ?>" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-list"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="eliminarCampeonato(<?php echo $campeonato['id']; ?>, '<?php echo htmlspecialchars($campeonato['nombre']); ?>')">
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

    <!-- Modal Campeonato -->
    <div class="modal fade" id="modalCampeonato" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-trophy"></i> Nuevo Campeonato
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formCampeonato">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="campeonatoId">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Campeonato *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="fecha_inicio" class="form-label">Fecha de Inicio *</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" required>
                                </div>
                            </div>
                            <div class="col-md-6">                                
                            </div>
                        </div>
                        
                        <div class="mb-3" id="activoContainer" style="display: none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                                <label class="form-check-label" for="activo">
                                    Campeonato Activo
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
        function editarCampeonato(id) {
            // Buscar los datos del campeonato
            <?php foreach ($campeonatos as $campeonato): ?>
            if (id == <?php echo $campeonato['id']; ?>) {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Campeonato';
                document.getElementById('formAction').value = 'update';
                document.getElementById('campeonatoId').value = <?php echo $campeonato['id']; ?>;
                document.getElementById('nombre').value = '<?php echo htmlspecialchars($campeonato['nombre'], ENT_QUOTES); ?>';
                document.getElementById('descripcion').value = '<?php echo htmlspecialchars($campeonato['descripcion'], ENT_QUOTES); ?>';
                document.getElementById('fecha_inicio').value = '<?php echo $campeonato['fecha_inicio']; ?>';
                document.getElementById('activo').checked = <?php echo $campeonato['activo'] ? 'true' : 'false'; ?>;
                document.getElementById('activoContainer').style.display = 'block';
                
                var modal = new bootstrap.Modal(document.getElementById('modalCampeonato'));
                modal.show();
                return;
            }
            <?php endforeach; ?>
        }

        function eliminarCampeonato(id, nombre) {
            if (confirm('¿Estás seguro de que deseas eliminar el campeonato "' + nombre + '"?\n\nEsta acción eliminará también todas las categorías, equipos y partidos asociados.')) {
                document.getElementById('eliminarId').value = id;
                document.getElementById('formEliminar').submit();
            }
        }

        // Limpiar formulario al cerrar modal
        document.getElementById('modalCampeonato').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formCampeonato').reset();
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-trophy"></i> Nuevo Campeonato';
            document.getElementById('formAction').value = 'create';
            document.getElementById('campeonatoId').value = '';
            document.getElementById('activoContainer').style.display = 'none';
        });
    </script>
</body>
</html>