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
            $categoria_id = $_POST['categoria_id'];
            $nombre = trim($_POST['nombre']);
            $color_camiseta = trim($_POST['color_camiseta']);
            $director_tecnico = trim($_POST['director_tecnico']);
            $importar_datos = isset($_POST['importar_datos']) ? 1 : 0;
            
            if (empty($categoria_id) || empty($nombre)) {
                $error = 'La categoría y nombre son obligatorios';
            } else {
                try {
                    $db->beginTransaction();
                    
                    $logo = null;
                    if (isset($_FILES['logo']) && $_FILES['logo']['tmp_name']) {
                        $logo = uploadFile($_FILES['logo'], 'equipos');
                        if (!$logo) {
                            throw new Exception('Error al subir el logo');
                        }
                    }
                    
                    // Buscar si existe un equipo con el mismo nombre en otro campeonato
                    $stmt = $db->prepare("
                        SELECT e.*, c.campeonato_id
                        FROM equipos e
                        JOIN categorias c ON e.categoria_id = c.id
                        WHERE LOWER(TRIM(e.nombre)) = LOWER(TRIM(?))
                        AND c.campeonato_id != (SELECT campeonato_id FROM categorias WHERE id = ?)
                        ORDER BY e.created_at DESC
                        LIMIT 1
                    ");
                    $stmt->execute([$nombre, $categoria_id]);
                    $equipo_existente = $stmt->fetch();
                    
                    // Si existe y se marcó importar datos, usar su logo y DT
                    if ($equipo_existente && $importar_datos) {
                        if (!$logo && $equipo_existente['logo']) {
                            $logo = $equipo_existente['logo'];
                        }
                        if (empty($color_camiseta) && $equipo_existente['color_camiseta']) {
                            $color_camiseta = $equipo_existente['color_camiseta'];
                        }
                        if (empty($director_tecnico) && $equipo_existente['director_tecnico']) {
                            $director_tecnico = $equipo_existente['director_tecnico'];
                        }
                    }
                    
                    // Crear el nuevo equipo
                    $stmt = $db->prepare("
                        INSERT INTO equipos (categoria_id, nombre, logo, color_camiseta, director_tecnico) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$categoria_id, $nombre, $logo, $color_camiseta, $director_tecnico]);
                    $nuevo_equipo_id = $db->lastInsertId();
                    
                    // Si existe equipo anterior y se marcó importar
                    if ($equipo_existente && $importar_datos) {
                        // Obtener campeonato del nuevo equipo una sola vez
                        $stmtCampNuevo = $db->prepare("SELECT camp.id AS campeonato_id FROM categorias c JOIN campeonatos camp ON c.campeonato_id = camp.id WHERE c.id = ?");
                        $stmtCampNuevo->execute([$categoria_id]);
                        $rowCampNuevo = $stmtCampNuevo->fetch();
                        if (!$rowCampNuevo) {
                            throw new Exception('No se pudo determinar el campeonato del nuevo equipo.');
                        }

                        // Obtener jugadores del equipo anterior
                        $stmt = $db->prepare("
                            SELECT * FROM jugadores 
                            WHERE equipo_id = ? AND activo = 1
                        ");
                        $stmt->execute([$equipo_existente['id']]);
                        $jugadores_anteriores = $stmt->fetchAll();
                        
                        $jugadores_importados = 0;
                        $jugadores_actualizados = 0;
                        
                        foreach ($jugadores_anteriores as $jugador) {
                            $jugador_existente = jugadorExisteEnCampeonato($jugador['dni'], $rowCampNuevo['campeonato_id'], null, $db);
                            
                            if ($jugador_existente) {
                                if ((int)$jugador_existente['equipo_id'] === (int)$nuevo_equipo_id) {
                                    continue;
                                }

                                // El jugador ya existe en este campeonato, actualizar su equipo_id al nuevo equipo
                                $stmt_update = $db->prepare("
                                    UPDATE jugadores 
                                    SET equipo_id = ?, activo = 1
                                    WHERE id = ?
                                ");
                                $stmt_update->execute([$nuevo_equipo_id, $jugador_existente['id']]);
                                $jugadores_actualizados++;
                                $jugador_id_actual = $jugador_existente['id'];

                                // Cerrar historial abierto únicamente en el equipo anterior e insertar nuevo historial
                                $stmtClose = $db->prepare("
                                    UPDATE jugadores_equipos_historial 
                                    SET fecha_fin = CURDATE() 
                                    WHERE jugador_dni = ? AND equipo_id = ? AND fecha_fin IS NULL
                                ");
                                $stmtClose->execute([$jugador['dni'], $jugador_existente['equipo_id']]);

                                $stmtHist = $db->prepare("INSERT INTO jugadores_equipos_historial (jugador_dni, jugador_nombre, equipo_id, campeonato_id, fecha_inicio) VALUES (?, ?, ?, ?, CURDATE())");
                                $stmtHist->execute([$jugador['dni'], $jugador['apellido_nombre'], $nuevo_equipo_id, $rowCampNuevo['campeonato_id']]);
                            } else {
                                // El jugador no existe, crear uno nuevo
                                $stmt_insert = $db->prepare("
                                    INSERT INTO jugadores 
                                    (equipo_id, dni, apellido_nombre, fecha_nacimiento, foto, activo)
                                    VALUES (?, ?, ?, ?, ?, 1)
                                ");
                                $stmt_insert->execute([
                                    $nuevo_equipo_id,
                                    $jugador['dni'],
                                    $jugador['apellido_nombre'],
                                    $jugador['fecha_nacimiento'],
                                    $jugador['foto']
                                ]);
                                $jugadores_importados++;
                                $jugador_id_actual = $db->lastInsertId();

                                // Registrar historial inicial del jugador creado
                                if ($rowCampNuevo) {
                                    $stmtHist = $db->prepare("INSERT INTO jugadores_equipos_historial (jugador_dni, jugador_nombre, equipo_id, campeonato_id, fecha_inicio) VALUES (?, ?, ?, ?, CURDATE())");
                                    $stmtHist->execute([$jugador['dni'], $jugador['apellido_nombre'], $nuevo_equipo_id, $rowCampNuevo['campeonato_id']]);
                                }
                            }
                            
                            // Copiar sanciones activas (excepto amarillas acumuladas que se reinician)
                            $stmt_sanciones = $db->prepare("
                                SELECT * FROM sanciones 
                                WHERE jugador_id = ? AND activa = 1 AND tipo != 'amarillas_acumuladas'
                            ");
                            $stmt_sanciones->execute([$jugador['id']]);
                            $sanciones = $stmt_sanciones->fetchAll();
                            
                            foreach ($sanciones as $sancion) {
                                $stmt_insert_sancion = $db->prepare("
                                    INSERT INTO sanciones 
                                    (jugador_id, tipo, partidos_suspension, partidos_cumplidos, fecha_sancion, descripcion, activa)
                                    VALUES (?, ?, ?, ?, ?, ?, ?)
                                ");
                                $stmt_insert_sancion->execute([
                                    $jugador_id_actual,
                                    $sancion['tipo'],
                                    $sancion['partidos_suspension'],
                                    $sancion['partidos_cumplidos'],
                                    $sancion['fecha_sancion'],
                                    $sancion['descripcion'],
                                    $sancion['activa']
                                ]);
                            }
                        }
                        
                        $db->commit();
                        
                        $total_jugadores = $jugadores_importados + $jugadores_actualizados;
                        $mensaje_detalle = [];
                        if ($jugadores_importados > 0) {
                            $mensaje_detalle[] = "{$jugadores_importados} jugadores nuevos";
                        }
                        if ($jugadores_actualizados > 0) {
                            $mensaje_detalle[] = "{$jugadores_actualizados} jugadores transferidos";
                        }
                        
                        $message = "✅ Equipo creado exitosamente. ";
                        $message .= "Se importaron {$total_jugadores} jugadores del historial (" . implode(", ", $mensaje_detalle) . "). ";
                        $message .= "Las sanciones activas (excepto amarillas) fueron transferidas automáticamente.";
                    } else {
                        $db->commit();
                        $message = 'Equipo creado exitosamente';
                    }
                } catch (Exception $e) {
                    $db->rollBack();
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
            
        case 'check_equipo':
            // AJAX: Verificar si existe equipo con ese nombre
            $nombre = trim($_POST['nombre']);
            $categoria_id = $_POST['categoria_id'];
            
            $stmt = $db->prepare("
                SELECT e.*, c.campeonato_id, camp.nombre as campeonato_nombre,
                       cat.nombre as categoria_nombre,
                       COUNT(DISTINCT j.id) as jugadores_count,
                       COUNT(DISTINCT s.id) as sanciones_count
                FROM equipos e
                JOIN categorias c ON e.categoria_id = c.id
                JOIN categorias cat ON e.categoria_id = cat.id
                JOIN campeonatos camp ON c.campeonato_id = camp.id
                LEFT JOIN jugadores j ON e.id = j.equipo_id AND j.activo = 1
                LEFT JOIN sanciones s ON j.id = s.jugador_id AND s.activa = 1 AND s.tipo != 'amarillas_acumuladas'
                WHERE LOWER(TRIM(e.nombre)) = LOWER(TRIM(?))
                AND c.campeonato_id != (SELECT campeonato_id FROM categorias WHERE id = ?)
                GROUP BY e.id
                ORDER BY e.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$nombre, $categoria_id]);
            $equipo = $stmt->fetch(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode($equipo ?: null);
            exit;
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
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .alerta-importacion {
            display: none;
            animation: slideDown 0.3s ease-out;
        }
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
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
                        <!-- Alerta de equipo existente -->
                        <div class="alert alert-info alerta-importacion" id="alertaEquipoExistente">
                            <h6 class="pulse"><i class="fas fa-info-circle"></i> ¡Equipo encontrado en historial!</h6>
                            <p class="mb-2">Este equipo ya participó en otro torneo:</p>
                            <div id="infoEquipoExistente"></div>
                            <hr>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="importar_datos" name="importar_datos" checked>
                                <label class="form-check-label" for="importar_datos">
                                    <strong>✅ Importar jugadores, fotos, sanciones y datos del equipo automáticamente</strong>
                                    <br>
                                    <small class="text-muted">
                                        • Se transferirán todos los jugadores al nuevo equipo<br>
                                        • Se copiarán las sanciones activas (excepto amarillas que se reinician)<br>
                                        • Se mantendrán las fotos y datos de los jugadores<br>
                                        • El logo y DT se copiarán automáticamente
                                    </small>
                                </label>
                            </div>
                        </div>
                        
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
                                    <div class="form-text">
                                        <i class="fas fa-lightbulb"></i> El sistema buscará si este equipo ya participó en otros torneos
                                    </div>
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
        let debounceTimer;
        
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

        // Verificar equipo existente al escribir nombre
        document.getElementById('nombre').addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(verificarEquipoExistente, 500);
        });
        
        document.getElementById('categoria_id').addEventListener('change', verificarEquipoExistente);
        
        function verificarEquipoExistente() {
            const nombre = document.getElementById('nombre').value.trim();
            const categoria_id = document.getElementById('categoria_id').value;
            const alerta = document.getElementById('alertaEquipoExistente');
            
            if (nombre.length < 3 || !categoria_id) {
                alerta.style.display = 'none';
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'check_equipo');
            formData.append('nombre', nombre);
            formData.append('categoria_id', categoria_id);
            
            fetch('equipos.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data) {
                    document.getElementById('infoEquipoExistente').innerHTML = `
                        <ul class="mb-0">
                            <li><strong>Campeonato:</strong> ${data.campeonato_nombre} - ${data.categoria_nombre}</li>
                            <li><strong>Jugadores registrados:</strong> ${data.jugadores_count}</li>
                            ${data.sanciones_count > 0 ? `<li><strong>Sanciones activas:</strong> ${data.sanciones_count}</li>` : ''}
                            ${data.director_tecnico ? `<li><strong>DT:</strong> ${data.director_tecnico}</li>` : ''}
                        </ul>
                    `;
                    alerta.style.display = 'block';
                    
                    // Auto-rellenar campos si están vacíos
                    if (!document.getElementById('logo').files.length && data.logo) {
                        document.getElementById('logoPreview').src = '../uploads/' + data.logo;
                        document.getElementById('logoPreview').style.display = 'block';
                    }
                    if (!document.getElementById('director_tecnico').value && data.director_tecnico) {
                        document.getElementById('director_tecnico').value = data.director_tecnico;
                    }
                    if (data.color_camiseta) {
                        document.getElementById('color_camiseta').value = data.color_camiseta;
                    }
                } else {
                    alerta.style.display = 'none';
                }
            })
            .catch(err => console.error('Error al verificar equipo:', err));
        }

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
                document.getElementById('alertaEquipoExistente').style.display = 'none';
                
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
            document.getElementById('alertaEquipoExistente').style.display = 'none';
            document.getElementById('logoPreview').style.display = 'none';
            document.getElementById('color_camiseta').value = '#007bff';
        });
    </script>
</body>
</html>