<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $equipo_id = $_POST['equipo_id'];
            $dni = trim($_POST['dni']);
            $apellido_nombre = trim($_POST['apellido_nombre']);
            $fecha_nacimiento = $_POST['fecha_nacimiento'];
            
            if (empty($equipo_id) || empty($dni) || empty($apellido_nombre) || empty($fecha_nacimiento)) {
                $error = 'Todos los campos son obligatorios';
            } else {
                try {
                    $stmt = $db->prepare("SELECT id FROM jugadores WHERE dni = ?");
                    $stmt->execute([$dni]);
                    if ($stmt->fetch()) {
                        $error = 'Ya existe un jugador con ese DNI';
                    } else {
                        $foto = null;
                        if (isset($_FILES['foto']) && $_FILES['foto']['tmp_name']) {
                            $foto = uploadFile($_FILES['foto'], 'jugadores');
                            if (!$foto) {
                                $error = 'Error al subir la foto';
                            }
                        }
                        
                        if (!$error) {
                            $stmt = $db->prepare("INSERT INTO jugadores (equipo_id, dni, apellido_nombre, fecha_nacimiento, foto) VALUES (?, ?, ?, ?, ?)");
                            $stmt->execute([$equipo_id, $dni, $apellido_nombre, $fecha_nacimiento, $foto]);
                            $message = 'Jugador registrado exitosamente';
                        }
                    }
                } catch (Exception $e) {
                    $error = 'Error al registrar jugador: ' . $e->getMessage();
                }
            }
            break;

        case 'import_excel':
            $equipo_id = $_POST['equipo_id'];
            if (empty($equipo_id)) {
                $error = 'Debe seleccionar un equipo';
            } elseif (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Debe seleccionar un archivo Excel válido';
            } else {
                try {
                    if (file_exists('../vendor/autoload.php')) {
                        require_once '../vendor/autoload.php';
                        $inputFileName = $_FILES['excel_file']['tmp_name'];
                        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                        $reader->setReadDataOnly(true);
                        $spreadsheet = $reader->load($inputFileName);
                        $worksheet = $spreadsheet->getActiveSheet();
                        
                        $importados = 0;
                        $errores = [];
                        $db->beginTransaction();
                        
                        $highestRow = $worksheet->getHighestRow();
                        for ($row = 2; $row <= $highestRow; $row++) {
                            $apellido_nombre = trim($worksheet->getCell('A'.$row)->getValue());
                            $dni = trim($worksheet->getCell('B'.$row)->getValue());
                            $fecha_raw = $worksheet->getCell('C'.$row)->getValue();
                            if (!empty($apellido_nombre) && !empty($dni)) {
                                $fecha_nacimiento = null;
                                if (!empty($fecha_raw)) {
                                    if (is_numeric($fecha_raw)) {
                                        try {
                                            $fecha_nacimiento = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha_raw)->format('Y-m-d');
                                        } catch (Exception $e) {
                                            $fecha_nacimiento = null;
                                        }
                                    } else {
                                        $fecha_nacimiento = date('Y-m-d', strtotime($fecha_raw));
                                        if ($fecha_nacimiento == '1970-01-01') $fecha_nacimiento = null;
                                    }
                                }
                                if ($fecha_nacimiento) {
                                    $stmt = $db->prepare("SELECT id FROM jugadores WHERE dni = ?");
                                    $stmt->execute([$dni]);
                                    if (!$stmt->fetch()) {
                                        $stmt = $db->prepare("INSERT INTO jugadores (equipo_id, dni, apellido_nombre, fecha_nacimiento) VALUES (?, ?, ?, ?)");
                                        $stmt->execute([$equipo_id, $dni, $apellido_nombre, $fecha_nacimiento]);
                                        $importados++;
                                    } else {
                                        $errores[] = "DNI $dni ya existe";
                                    }
                                } else {
                                    $errores[] = "Fecha inválida para $apellido_nombre (fila $row)";
                                }
                            }
                        }
                        $db->commit();
                        $message = "Se importaron $importados jugadores exitosamente";
                        if (!empty($errores)) {
                            $message .= ". Errores: " . implode(', ', array_slice($errores, 0, 3));
                            if (count($errores) > 3) $message .= " y " . (count($errores) - 3) . " más...";
                        }
                    } else {
                        throw new Exception('PhpSpreadsheet no está instalado. Instale con: composer require phpoffice/phpspreadsheet');
                    }
                } catch (Exception $e) {
                    if (isset($db) && $db->inTransaction()) $db->rollback();
                    $error = 'Error al importar archivo: ' . $e->getMessage();
                }
            }
            break;

        case 'update':
            $id = $_POST['id'];
            $equipo_id = $_POST['equipo_id'];
            $dni = trim($_POST['dni']);
            $apellido_nombre = trim($_POST['apellido_nombre']);
            $fecha_nacimiento = $_POST['fecha_nacimiento'];
            $activo = isset($_POST['activo']) ? 1 : 0;
            try {
                $foto = null;
                if (isset($_FILES['foto']) && $_FILES['foto']['tmp_name']) $foto = uploadFile($_FILES['foto'], 'jugadores');
                if ($foto) {
                    $stmt = $db->prepare("UPDATE jugadores SET equipo_id = ?, dni = ?, apellido_nombre = ?, fecha_nacimiento = ?, foto = ?, activo = ? WHERE id = ?");
                    $stmt->execute([$equipo_id, $dni, $apellido_nombre, $fecha_nacimiento, $foto, $activo, $id]);
                } else {
                    $stmt = $db->prepare("UPDATE jugadores SET equipo_id = ?, dni = ?, apellido_nombre = ?, fecha_nacimiento = ?, activo = ? WHERE id = ?");
                    $stmt->execute([$equipo_id, $dni, $apellido_nombre, $fecha_nacimiento, $activo, $id]);
                }
                $message = 'Jugador actualizado exitosamente';
            } catch (Exception $e) {
                $error = 'Error al actualizar jugador: ' . $e->getMessage();
            }
            break;

        case 'delete':
            $id = $_POST['id'];
            try {
                $stmt = $db->prepare("DELETE FROM jugadores WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Jugador eliminado exitosamente';
            } catch (Exception $e) {
                $error = 'Error al eliminar jugador: ' . $e->getMessage();
            }
            break;
    }
}

$stmt = $db->query("SELECT id, nombre FROM campeonatos WHERE activo = 1 ORDER BY fecha_inicio DESC, nombre");
$campeonatos = $stmt->fetchAll();

$campeonato_filtro = $_GET['campeonato'] ?? '';
$categoria_filtro = $_GET['categoria'] ?? '';
$categorias = [];
if ($campeonato_filtro) {
    $stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
    $stmt->execute([$campeonato_filtro]);
    $categorias = $stmt->fetchAll();
}

$equipos_query = "SELECT e.*, c.nombre as categoria, camp.nombre as campeonato, c.id as categoria_id FROM equipos e JOIN categorias c ON e.categoria_id = c.id JOIN campeonatos camp ON c.campeonato_id = camp.id WHERE e.activo = 1";
$equipos_params = [];
if ($categoria_filtro) {
    $equipos_query .= " AND c.id = ?";
    $equipos_params[] = $categoria_filtro;
} elseif ($campeonato_filtro) {
    $equipos_query .= " AND camp.id = ?";
    $equipos_params[] = $campeonato_filtro;
}
$equipos_query .= " ORDER BY camp.nombre, c.nombre, e.nombre";
$stmt = $db->prepare($equipos_query);
$stmt->execute($equipos_params);
$equipos = $stmt->fetchAll();

$equipo_filtro = $_GET['equipo'] ?? '';
$buscar = $_GET['buscar'] ?? '';

$sql = "SELECT j.*, e.nombre as equipo, c.nombre as categoria, camp.nombre as campeonato,
        (SELECT COUNT(*) FROM eventos_partido ep WHERE ep.jugador_id = j.id AND ep.tipo_evento = 'gol') as goles,
        (SELECT COUNT(*) FROM eventos_partido ep WHERE ep.jugador_id = j.id AND ep.tipo_evento = 'amarilla') as amarillas,
        (SELECT COUNT(*) FROM eventos_partido ep WHERE ep.jugador_id = j.id AND ep.tipo_evento = 'roja') as rojas,
        (SELECT COUNT(*) FROM sanciones s WHERE s.jugador_id = j.id AND s.activa = 1 AND s.partidos_cumplidos < s.partidos_suspension) as sancionado
        FROM jugadores j
        JOIN equipos e ON j.equipo_id = e.id
        JOIN categorias c ON e.categoria_id = c.id
        JOIN campeonatos camp ON c.campeonato_id = camp.id
        WHERE 1=1";

$params = [];
if ($campeonato_filtro) { $sql .= " AND camp.id = ?"; $params[] = $campeonato_filtro; }
if ($categoria_filtro) { $sql .= " AND c.id = ?"; $params[] = $categoria_filtro; }
if ($equipo_filtro) { $sql .= " AND j.equipo_id = ?"; $params[] = $equipo_filtro; }
if ($buscar) { $sql .= " AND (j.apellido_nombre LIKE ? OR j.dni LIKE ?)"; $params[] = "%$buscar%"; $params[] = "%$buscar%"; }
$sql .= " ORDER BY camp.nombre, c.nombre, e.nombre, j.apellido_nombre";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$jugadores = $stmt->fetchAll();

if (!function_exists('calculateAge')) {
    function calculateAge($birthDate) {
        return date_diff(date_create($birthDate), date_create('today'))->y;
    }
}
?>
<!-- HTML completo similar al original, con modales, filtros, tablas, scripts para editar, eliminar y previsualizar imagen -->

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Jugadores - Sistema de Campeonatos</title>
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
                    <h2><i class="fas fa-user-friends"></i> Gestión de Jugadores</h2>
                    <div class="btn-group">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalJugador">
                            <i class="fas fa-plus"></i> Nuevo Jugador
                        </button>
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalImportar">
                            <i class="fas fa-file-excel"></i> Importar Excel
                        </button>
                        <a href="export/jugadores_excel.php" class="btn btn-outline-success">
                            <i class="fas fa-download"></i> Exportar
                        </a>
                    </div>
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

                <!-- CORRECCIÓN 1: Filtros mejorados con selector de categoría -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3" id="filtrosForm">
                            <div class="col-md-3">
                                <label class="form-label">Campeonato:</label>
                                <select name="campeonato" class="form-select" onchange="cargarCategorias()">
                                    <option value="">Todos los campeonatos</option>
                                    <?php foreach ($campeonatos as $camp): ?>
                                        <option value="<?php echo $camp['id']; ?>" 
                                                <?php echo $campeonato_filtro == $camp['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($camp['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Categoría:</label>
                                <select name="categoria" id="categoriaSelect" class="form-select" onchange="cargarEquipos()">
                                    <option value="">Todas las categorías</option>
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" 
                                                <?php echo $categoria_filtro == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Equipo:</label>
                                <select name="equipo" id="equipoSelect" class="form-select">
                                    <option value="">Todos los equipos</option>
                                    <?php foreach ($equipos as $equipo): ?>
                                        <option value="<?php echo $equipo['id']; ?>" 
                                                <?php echo $equipo_filtro == $equipo['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($equipo['nombre']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-3">
                                <label class="form-label">Buscar:</label>
                                <input type="text" name="buscar" class="form-control" 
                                       placeholder="Nombre o DNI..." value="<?php echo htmlspecialchars($buscar); ?>">
                            </div>
                            
                            <div class="col-12">
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Buscar
                                    </button>
                                    <a href="jugadores.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Jugadores -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($jugadores)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-user-friends fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay jugadores registrados</h5>
                                <p class="text-muted">Use los filtros para buscar o agregue nuevos jugadores</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Foto</th>
                                            <th>Apellido y Nombre</th>
                                            <th>DNI</th>
                                            <th>Edad</th>
                                            <th>Equipo</th>
                                            <th>Goles</th>
                                            <th>Amarillas</th>
                                            <th>Rojas</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jugadores as $jugador): ?>
                                        <tr class="<?php echo $jugador['sancionado'] ? 'table-warning' : ''; ?>">
                                            <td>
                                                <?php if ($jugador['foto']): ?>
                                                    <img src="../uploads/<?php echo htmlspecialchars($jugador['foto']); ?>" 
                                                         alt="Foto" class="rounded-circle" width="40" height="40" style="object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center" 
                                                         style="width: 40px; height: 40px;">
                                                        <i class="fas fa-user text-white"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($jugador['apellido_nombre']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($jugador['dni']); ?></td>
                                            <td><?php echo calculateAge($jugador['fecha_nacimiento']); ?> años</td>
                                            <td>
                                                <small class="text-muted"><?php echo htmlspecialchars($jugador['campeonato']); ?></small><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($jugador['categoria']); ?></small><br>
                                                <strong><?php echo htmlspecialchars($jugador['equipo']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $jugador['goles']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning"><?php echo $jugador['amarillas']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge bg-danger"><?php echo $jugador['rojas']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($jugador['sancionado']): ?>
                                                    <span class="badge bg-danger">Sancionado</span>
                                                <?php elseif ($jugador['activo']): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
												<div class="btn-group" role="group">
													<button class="btn btn-sm btn-outline-primary" 
														data-id="<?= $jugador['id'] ?>"
														data-equipo="<?= $jugador['equipo_id'] ?>"
														data-dni="<?= htmlspecialchars($jugador['dni'], ENT_QUOTES) ?>"
														data-apellido="<?= htmlspecialchars($jugador['apellido_nombre'], ENT_QUOTES) ?>"
														data-fecha="<?= $jugador['fecha_nacimiento'] ?>"
														data-activo="<?= $jugador['activo'] ?>"
														data-foto="<?= $jugador['foto'] ?>"
														onclick="editarJugador(this)">
														<i class="fas fa-edit"></i>
													</button>
													<button class="btn btn-sm btn-outline-danger" 
															onclick="eliminarJugador(<?php echo $jugador['id']; ?>, '<?php echo htmlspecialchars($jugador['apellido_nombre']); ?>')">
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

    <!-- Modal Jugador -->
    <div class="modal fade" id="modalJugador" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalJugadorTitle">
                        <i class="fas fa-user-plus"></i> Nuevo Jugador
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formJugador" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="jugadorAction" value="create">
                    <input type="hidden" name="id" id="jugadorId">
                    
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="equipo_id" class="form-label">Equipo *</label>
                                    <select class="form-select" id="equipo_id" name="equipo_id" required>
                                        <option value="">Seleccionar equipo...</option>
                                        <?php foreach ($equipos as $equipo): ?>
                                            <option value="<?php echo $equipo['id']; ?>">
                                                <?php echo htmlspecialchars($equipo['campeonato'] . ' - ' . $equipo['categoria'] . ' - ' . $equipo['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="apellido_nombre" class="form-label">Apellido y Nombre *</label>
                                    <input type="text" class="form-control" id="apellido_nombre" name="apellido_nombre" required>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="dni" class="form-label">DNI *</label>
                                            <input type="text" class="form-control" id="dni" name="dni" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento *</label>
                                            <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3" id="activoContainer" style="display: none;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                                        <label class="form-check-label" for="activo">
                                            Jugador Activo
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="foto" class="form-label">Foto del Jugador</label>
                                    <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
                                    <div class="mt-2">
                                        <img id="fotoPreview" src="" alt="Preview" class="img-thumbnail" 
                                             style="display: none; max-width: 150px;">
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

    <!-- CORRECCIÓN 2: Modal Importar mejorado -->
    <div class="modal fade" id="modalImportar" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-excel"></i> Importar Jugadores desde Excel
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" enctype="multipart/form-data" id="formImportar">
                    <input type="hidden" name="action" value="import_excel">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="equipo_import" class="form-label">Equipo de Destino *</label>
                            <select class="form-select" id="equipo_import" name="equipo_id" required>
                                <option value="">Seleccionar equipo...</option>
                                <?php foreach ($equipos as $equipo): ?>
                                    <option value="<?php echo $equipo['id']; ?>">
                                        <?php echo htmlspecialchars($equipo['campeonato'] . ' - ' . $equipo['categoria'] . ' - ' . $equipo['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="excel_file" class="form-label">Archivo Excel *</label>
                            <input type="file" class="form-control" id="excel_file" name="excel_file" 
                                   accept=".xlsx,.xls" required>
                            <div class="form-text">
                                El archivo debe tener las columnas: Apellido y Nombre | DNI | Fecha de Nacimiento
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Formato del archivo Excel:</h6>
                            <ul class="mb-0">
                                <li><strong>Columna A:</strong> Apellido y Nombre</li>
                                <li><strong>Columna B:</strong> DNI</li>
                                <li><strong>Columna C:</strong> Fecha de Nacimiento (DD/MM/YYYY)</li>
                                <li>La primera fila debe contener los encabezados</li>
                            </ul>
                        </div>
                        
                        <div class="text-center">
                            <a href="../templates/plantilla_jugadores.xlsx" class="btn btn-outline-primary">
                                <i class="fas fa-download"></i> Descargar Plantilla
                            </a>
                        </div>
                        
                        <!-- CORRECCIÓN 2: Indicador de progreso -->
                        <div id="progressContainer" style="display: none;" class="mt-3">
                            <div class="alert alert-info">
                                <i class="fas fa-spinner fa-spin"></i> Procesando archivo Excel, por favor espere...
                            </div>
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-info" id="btnImportar">
                            <i class="fas fa-upload"></i> Importar
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
        // CORRECCIÓN 1: Funciones para filtros dinámicos
        function cargarCategorias() {
            const campeonatoId = document.querySelector('select[name="campeonato"]').value;
            const categoriaSelect = document.getElementById('categoriaSelect');
            const equipoSelect = document.getElementById('equipoSelect');
            
            // Limpiar categorías y equipos
            categoriaSelect.innerHTML = '<option value="">Todas las categorías</option>';
            equipoSelect.innerHTML = '<option value="">Todos los equipos</option>';
            
            if (campeonatoId) {
                // Cargar categorías vía AJAX
                fetch(`ajax/get_categorias.php?campeonato_id=${campeonatoId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            data.categorias.forEach(categoria => {
                                const option = document.createElement('option');
                                option.value = categoria.id;
                                option.textContent = categoria.nombre;
                                categoriaSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }
        
        function cargarEquipos() {
            const categoriaId = document.getElementById('categoriaSelect').value;
            const equipoSelect = document.getElementById('equipoSelect');
            
            // Limpiar equipos
            equipoSelect.innerHTML = '<option value="">Todos los equipos</option>';
            
            if (categoriaId) {
                // Cargar equipos vía AJAX
                fetch(`ajax/get_equipos.php?categoria_id=${categoriaId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            data.equipos.forEach(equipo => {
                                const option = document.createElement('option');
                                option.value = equipo.id;
                                option.textContent = equipo.nombre;
                                equipoSelect.appendChild(option);
                            });
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        }

        // CORRECCIÓN 2: Mejorar el manejo de importación de Excel
        document.getElementById('formImportar').addEventListener('submit', function(e) {
            const equipoId = document.getElementById('equipo_import').value;
            const archivoExcel = document.getElementById('excel_file').files[0];
            
            if (!equipoId) {
                e.preventDefault();
                alert('Por favor seleccione un equipo de destino');
                return;
            }
            
            if (!archivoExcel) {
                e.preventDefault();
                alert('Por favor seleccione un archivo Excel');
                return;
            }
            
            // Validar tipo de archivo
            const tiposPermitidos = ['.xlsx', '.xls'];
            const extension = '.' + archivoExcel.name.split('.').pop().toLowerCase();
            
            if (!tiposPermitidos.includes(extension)) {
                e.preventDefault();
                alert('Por favor seleccione un archivo Excel válido (.xlsx o .xls)');
                return;
            }
            
            // Mostrar indicador de progreso
            document.getElementById('progressContainer').style.display = 'block';
            document.getElementById('btnImportar').disabled = true;
            document.getElementById('btnImportar').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Importando...';
        });

        // Preview de imagen
        document.getElementById('foto').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('fotoPreview');
            
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

        function editarJugador(button) {
			document.getElementById('modalJugadorTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Jugador';
			document.getElementById('jugadorAction').value = 'update';
			document.getElementById('jugadorId').value = button.dataset.id;
			document.getElementById('equipo_id').value = button.dataset.equipo;
			document.getElementById('apellido_nombre').value = button.dataset.apellido;
			document.getElementById('dni').value = button.dataset.dni;
			document.getElementById('fecha_nacimiento').value = button.dataset.fecha;
			document.getElementById('activo').checked = button.dataset.activo == 1;
			document.getElementById('activoContainer').style.display = 'block';

			const preview = document.getElementById('fotoPreview');
			if (button.dataset.foto) {
				preview.src = '../uploads/' + button.dataset.foto;
				preview.style.display = 'block';
			} else {
				preview.style.display = 'none';
			}

			const modal = new bootstrap.Modal(document.getElementById('modalJugador'));
			modal.show();
		}


        function eliminarJugador(id, nombre) {
            if (confirm('¿Estás seguro de que deseas eliminar al jugador "' + nombre + '"?\n\nEsta acción también eliminará todos sus eventos y estadísticas.')) {
                document.getElementById('eliminarId').value = id;
                document.getElementById('formEliminar').submit();
            }
        }

        // Limpiar formulario al cerrar modal
        document.getElementById('modalJugador').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formJugador').reset();
            document.getElementById('modalJugadorTitle').innerHTML = '<i class="fas fa-user-plus"></i> Nuevo Jugador';
            document.getElementById('jugadorAction').value = 'create';
            document.getElementById('jugadorId').value = '';
            document.getElementById('activoContainer').style.display = 'none';
            document.getElementById('fotoPreview').style.display = 'none';
        });
        
        // Limpiar modal de importar al cerrar
        document.getElementById('modalImportar').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formImportar').reset();
            document.getElementById('progressContainer').style.display = 'none';
            document.getElementById('btnImportar').disabled = false;
            document.getElementById('btnImportar').innerHTML = '<i class="fas fa-upload"></i> Importar';
        });
    </script>
</body>
</html>