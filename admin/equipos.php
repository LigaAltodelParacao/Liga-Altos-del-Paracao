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
            $categoria_id = $_POST['categoria_id'];
            $nombre = trim($_POST['nombre']);
            $color_camiseta = trim($_POST['color_camiseta']);
            $director_tecnico = trim($_POST['director_tecnico']);
            
            if (empty($categoria_id) || empty($nombre)) {
                $error = 'La categoría y nombre son obligatorios';
            } else {
                try {
                    $logo = null;
                    if (isset($_FILES['logo']) && $_FILES['logo']['tmp_name']) {
                        $logo = uploadFile($_FILES['logo'], 'equipos');
                        if (!$logo) {
                            $error = 'Error al subir el logo';
                        }
                    }
                    
                    if (!$error) {
                        $stmt = $db->prepare("
                            INSERT INTO equipos (categoria_id, nombre, logo, color_camiseta, director_tecnico) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$categoria_id, $nombre, $logo, $color_camiseta, $director_tecnico]);
                        $message = 'Equipo creado exitosamente';
                    }
                } catch (Exception $e) {
                    $error = 'Error al crear equipo: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update':
            $id = $_POST['id'];
            $categoria_id = $_POST['categoria_id'];
            $nombre = trim($_POST['nombre']);
            $color_camiseta = trim($_POST['color_camiseta']);
            $director_tecnico = trim($_POST['director_tecnico']);
            $activo = isset($_POST['activo']) ? 1 : 0;
            
            try {
                $logo = null;
                if (isset($_FILES['logo']) && $_FILES['logo']['tmp_name']) {
                    $logo = uploadFile($_FILES['logo'], 'equipos');
                }
                
                if ($logo) {
                    $stmt = $db->prepare("
                        UPDATE equipos 
                        SET categoria_id = ?, nombre = ?, logo = ?, color_camiseta = ?, director_tecnico = ?, activo = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$categoria_id, $nombre, $logo, $color_camiseta, $director_tecnico, $activo, $id]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE equipos 
                        SET categoria_id = ?, nombre = ?, color_camiseta = ?, director_tecnico = ?, activo = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$categoria_id, $nombre, $color_camiseta, $director_tecnico, $activo, $id]);
                }
                
                $message = 'Equipo actualizado exitosamente';
            } catch (Exception $e) {
                $error = 'Error al actualizar equipo: ' . $e->getMessage();
            }
            break;
            
        case 'delete':
            $id = $_POST['id'];
            try {
                $stmt = $db->prepare("DELETE FROM equipos WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Equipo eliminado exitosamente';
            } catch (Exception $e) {
                $error = 'Error al eliminar equipo: ' . $e->getMessage();
            }
            break;
    }
}

// Obtener categorías para el selector
$stmt = $db->query("
    SELECT c.*, camp.nombre as campeonato_nombre
    FROM categorias c
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE c.activa = 1
    ORDER BY camp.nombre, c.nombre
");
$categorias = $stmt->fetchAll();

// Filtros
$categoria_filtro = $_GET['categoria'] ?? '';

// Obtener equipos
$sql = "
    SELECT e.*, c.nombre as categoria, camp.nombre as campeonato,
           COUNT(DISTINCT j.id) as jugadores_count
    FROM equipos e
    JOIN categorias c ON e.categoria_id = c.id
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    LEFT JOIN jugadores j ON e.id = j.equipo_id AND j.activo = 1
    WHERE 1=1
";

$params = [];
if ($categoria_filtro) {
    $sql .= " AND e.categoria_id = ?";
    $params[] = $categoria_filtro;
}

$sql .= " GROUP BY e.id ORDER BY camp.nombre, c.nombre, e.nombre";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$equipos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Equipos - Sistema de Campeonatos</title>
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
                    <h2><i class="fas fa-users"></i> Gestión de Equipos</h2>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalEquipo">
                        <i class="fas fa-plus"></i> Nuevo Equipo
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

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Categoría:</label>
                                <select name="categoria" class="form-select">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?php echo $categoria['id']; ?>" 
                                                <?php echo $categoria_filtro == $categoria['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($categoria['campeonato_nombre'] . ' - ' . $categoria['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">&nbsp;</label>
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Filtrar
                                    </button>
                                    <a href="equipos.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Equipos -->
                <div class="row">
                    <?php if (empty($equipos)): ?>
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body text-center py-5">
                                    <i class="fas fa-users fa-4x text-muted mb-3"></i>
                                    <h4 class="text-muted">No hay equipos registrados</h4>
                                    <p class="text-muted">Crea tu primer equipo para comenzar</p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($equipos as $equipo): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header text-center" style="background-color: <?php echo $equipo['color_camiseta'] ?: '#f8f9fa'; ?>;">
                                    <?php if ($equipo['logo']): ?>
                                        <img src="../uploads/<?php echo htmlspecialchars($equipo['logo']); ?>" 
                                             alt="Logo" class="mb-2" width="80" height="80" 
                                             style="object-fit: cover; border-radius: 50%; border: 3px solid white;">
                                    <?php else: ?>
                                        <div class="bg-secondary rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center" 
                                             style="width: 80px; height: 80px;">
                                            <i class="fas fa-shield-alt text-white fa-2x"></i>
                                        </div>
                                    <?php endif; ?>
                                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($equipo['nombre']); ?></h5>
                                </div>
                                
                                <div class="card-body">
                                    <div class="mb-2">
                                        <small class="text-muted"><?php echo htmlspecialchars($equipo['campeonato']); ?></small><br>
                                        <strong class="text-primary"><?php echo htmlspecialchars($equipo['categoria']); ?></strong>
                                    </div>
                                    
                                    <?php if ($equipo['director_tecnico']): ?>
                                        <div class="mb-2">
                                            <i class="fas fa-user-tie text-muted"></i>
                                            <small><?php echo htmlspecialchars($equipo['director_tecnico']); ?></small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="mb-2">
                                        <i class="fas fa-user-friends text-muted"></i>
                                        <span class="badge bg-info"><?php echo $equipo['jugadores_count']; ?> jugadores</span>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <?php if ($equipo['activo']): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="card-footer">
                                    <div class="btn-group w-100" role="group">
                                        <button class="btn btn-outline-primary btn-sm" 
                                                onclick="editarEquipo(<?php echo $equipo['id']; ?>)">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                        <a href="jugadores.php?equipo=<?php echo $equipo['id']; ?>" 
                                           class="btn btn-outline-info btn-sm">
                                            <i class="fas fa-user-friends"></i> Jugadores
                                        </a>
                                        <button class="btn btn-outline-danger btn-sm" 
                                                onclick="eliminarEquipo(<?php echo $equipo['id']; ?>, '<?php echo htmlspecialchars($equipo['nombre']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Equipo -->
    <div class="modal fade" id="modalEquipo" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalEquipoTitle">
                        <i class="fas fa-users"></i> Nuevo Equipo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEquipo" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="equipoAction" value="create">
                    <input type="hidden" name="id" id="equipoId">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="categoria_id" class="form-label">Categoría *</label>
                                    <select class="form-select" id="categoria_id" name="categoria_id" required>
                                        <option value="">Seleccionar categoría...</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?php echo $categoria['id']; ?>">
                                                <?php echo htmlspecialchars($categoria['campeonato_nombre'] . ' - ' . $categoria['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="nombre" class="form-label">Nombre del Equipo *</label>
                                    <input type="text" class="form-control" id="nombre" name="nombre" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="color_camiseta" class="form-label">Color de Camiseta</label>
                                            <input type="color" class="form-control form-control-color" 
                                                   id="color_camiseta" name="color_camiseta" value="#007bff">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="director_tecnico" class="form-label">Director Técnico</label>
                                            <input type="text" class="form-control" id="director_tecnico" name="director_tecnico">
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="activoContainer" style="display: none;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                                        <label class="form-check-label" for="activo">
                                            Equipo Activo
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="logo" class="form-label">Logo del Equipo</label>
                                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                                    <div class="form-text">Tamaño recomendado: 200x200px</div>
                                    <div class="mt-3 text-center">
                                        <img id="logoPreview" src="" alt="Preview" class="img-thumbnail" 
                                             style="display: none; max-width: 120px; max-height: 120px;">
                                    </div>
                                </div>
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
        // Preview de logo
        document.getElementById('logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('logoPreview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.style.display = 'none';
            }
        });

        function editarEquipo(id) {
            // Buscar datos del equipo
            <?php foreach ($equipos as $equipo): ?>
            if (id == <?php echo $equipo['id']; ?>) {
                document.getElementById('modalEquipoTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Equipo';
                document.getElementById('equipoAction').value = 'update';
                document.getElementById('equipoId').value = <?php echo $equipo['id']; ?>;
                document.getElementById('categoria_id').value = <?php echo $equipo['categoria_id']; ?>;
                document.getElementById('nombre').value = '<?php echo htmlspecialchars($equipo['nombre'], ENT_QUOTES); ?>';
                document.getElementById('color_camiseta').value = '<?php echo $equipo['color_camiseta'] ?: '#007bff'; ?>';
                document.getElementById('director_tecnico').value = '<?php echo htmlspecialchars($equipo['director_tecnico'], ENT_QUOTES); ?>';
                document.getElementById('activo').checked = <?php echo $equipo['activo'] ? 'true' : 'false'; ?>;
                document.getElementById('activoContainer').style.display = 'block';
                
                <?php if ($equipo['logo']): ?>
                document.getElementById('logoPreview').src = '../uploads/<?php echo htmlspecialchars($equipo['logo']); ?>';
                document.getElementById('logoPreview').style.display = 'block';
                <?php endif; ?>
                
                var modal = new bootstrap.Modal(document.getElementById('modalEquipo'));
                modal.show();
                return;
            }
            <?php endforeach; ?>
        }

        function eliminarEquipo(id, nombre) {
            if (confirm('¿Eliminar el equipo "' + nombre + '"?\n\nEsto eliminará también todos los jugadores y partidos asociados.')) {
                document.getElementById('eliminarId').value = id;
                document.getElementById('formEliminar').submit();
            }
        }

        // Limpiar formulario al cerrar modal
        document.getElementById('modalEquipo').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formEquipo').reset();
            document.getElementById('modalEquipoTitle').innerHTML = '<i class="fas fa-users"></i> Nuevo Equipo';
            document.getElementById('equipoAction').value = 'create';
            document.getElementById('equipoId').value = '';
            document.getElementById('activoContainer').style.display = 'none';
            document.getElementById('logoPreview').style.display = 'none';
            document.getElementById('color_camiseta').value = '#007bff';
        });
    </script>
</body>
</html>