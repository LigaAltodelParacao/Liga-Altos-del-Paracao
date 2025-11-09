<?php
require_once __DIR__ . '/../config.php';

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
            $campeonato_id = $_POST['campeonato_id'];
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion']);
            
            if (empty($campeonato_id) || empty($nombre)) {
                $error = 'El campeonato y nombre son obligatorios';
            } else {
                try {
                    $stmt = $db->prepare("
                        INSERT INTO categorias (campeonato_id, nombre, descripcion) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$campeonato_id, $nombre, $descripcion]);
                    $message = 'Categoría creada exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al crear categoría: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update':
            $id = $_POST['id'];
            $campeonato_id = $_POST['campeonato_id'];
            $nombre = trim($_POST['nombre']);
            $descripcion = trim($_POST['descripcion']);
            $activa = isset($_POST['activa']) ? 1 : 0;
            
            try {
                $stmt = $db->prepare("
                    UPDATE categorias 
                    SET campeonato_id = ?, nombre = ?, descripcion = ?, activa = ?
                    WHERE id = ?
                ");
                $stmt->execute([$campeonato_id, $nombre, $descripcion, $activa, $id]);
                $message = 'Categoría actualizada exitosamente';
            } catch (Exception $e) {
                $error = 'Error al actualizar categoría: ' . $e->getMessage();
            }
            break;
            
        case 'delete':
            $id = $_POST['id'];
            try {
                $stmt = $db->prepare("DELETE FROM categorias WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Categoría eliminada exitosamente';
            } catch (Exception $e) {
                $error = 'Error al eliminar categoría: ' . $e->getMessage();
            }
            break;
    }
}

// Obtener campeonatos para el selector
$stmt = $db->query("SELECT * FROM campeonatos ORDER BY nombre");
$campeonatos = $stmt->fetchAll();

// Obtener categorías
$stmt = $db->query("
    SELECT c.*, camp.nombre as campeonato_nombre,
           COUNT(DISTINCT e.id) as equipos_count
    FROM categorias c
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    LEFT JOIN equipos e ON c.id = e.categoria_id
    GROUP BY c.id
    ORDER BY camp.nombre, c.nombre
");
$categorias = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Categorías - Sistema de Campeonatos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-futbol"></i> Fútbol Manager - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="<?php echo SITE_URL; ?>logout.php">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 p-0">
                <?php include __DIR__ . '/include/sidebar.php'; ?>
            </div>

            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-list"></i> Gestión de Categorías</h2>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalCategoria">
                        <i class="fas fa-plus"></i> Nueva Categoría
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

                <!-- Lista de Categorías -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($categorias)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-list fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay categorías registradas</h5>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Campeonato</th>
                                            <th>Nombre</th>
                                            <th>Descripción</th>
                                            <th>Equipos</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categorias as $categoria): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($categoria['campeonato_nombre']); ?></td>
                                            <td><strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($categoria['descripcion']); ?></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo $categoria['equipos_count']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($categoria['activa']): ?>
                                                    <span class="badge bg-success">Activa</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactiva</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editarCategoria(<?php echo $categoria['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="equipos.php?categoria=<?php echo $categoria['id']; ?>" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-users"></i>
                                                    </a>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="eliminarCategoria(<?php echo $categoria['id']; ?>, '<?php echo htmlspecialchars($categoria['nombre']); ?>')">
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

    <!-- Modal Categoría -->
    <div class="modal fade" id="modalCategoria" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">
                        <i class="fas fa-list"></i> Nueva Categoría
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formCategoria">
                    <input type="hidden" name="action" id="formAction" value="create">
                    <input type="hidden" name="id" id="categoriaId">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="campeonato_id" class="form-label">Campeonato *</label>
                            <select class="form-select" id="campeonato_id" name="campeonato_id" required>
                                <option value="">Seleccionar campeonato...</option>
                                <?php foreach ($campeonatos as $campeonato): ?>
                                    <option value="<?php echo $campeonato['id']; ?>">
                                        <?php echo htmlspecialchars($campeonato['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la Categoría *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" 
                                   placeholder="Ej: Libre, M30A, M40, Femenino" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion" rows="3"
                                      placeholder="Descripción opcional de la categoría"></textarea>
                        </div>
                        
                        <div class="mb-3" id="activaContainer" style="display: none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activa" name="activa" checked>
                                <label class="form-check-label" for="activa">
                                    Categoría Activa
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
        function editarCategoria(id) {
            // Buscar datos y llenar formulario
            <?php foreach ($categorias as $categoria): ?>
            if (id == <?php echo $categoria['id']; ?>) {
                document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Categoría';
                document.getElementById('formAction').value = 'update';
                document.getElementById('categoriaId').value = <?php echo $categoria['id']; ?>;
                document.getElementById('campeonato_id').value = <?php echo $categoria['campeonato_id']; ?>;
                document.getElementById('nombre').value = '<?php echo htmlspecialchars($categoria['nombre'], ENT_QUOTES); ?>';
                document.getElementById('descripcion').value = '<?php echo htmlspecialchars($categoria['descripcion'], ENT_QUOTES); ?>';
                document.getElementById('activa').checked = <?php echo $categoria['activa'] ? 'true' : 'false'; ?>;
                document.getElementById('activaContainer').style.display = 'block';
                
                var modal = new bootstrap.Modal(document.getElementById('modalCategoria'));
                modal.show();
                return;
            }
            <?php endforeach; ?>
        }

        function eliminarCategoria(id, nombre) {
            if (confirm('¿Eliminar la categoría "' + nombre + '"?\n\nEsto eliminará también todos los equipos y partidos asociados.')) {
                document.getElementById('eliminarId').value = id;
                document.getElementById('formEliminar').submit();
            }
        }

        // Limpiar formulario al cerrar modal
        document.getElementById('modalCategoria').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formCategoria').reset();
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-list"></i> Nueva Categoría';
            document.getElementById('formAction').value = 'create';
            document.getElementById('categoriaId').value = '';
            document.getElementById('activaContainer').style.display = 'none';
        });
    </script>
</body>
</html>