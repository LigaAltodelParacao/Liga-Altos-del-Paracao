<?php
// Habilitar errores para debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

// Obtener campeonatos activos
$stmt = $db->query("SELECT id, nombre FROM campeonatos WHERE activo = 1 ORDER BY fecha_inicio DESC");
$campeonatos = $stmt->fetchAll();

// Variables de filtro
$campeonato_id = $_GET['campeonato'] ?? '';
$categoria_id = $_GET['categoria'] ?? '';
$fecha_id = $_GET['fecha'] ?? '';

// Obtener categorías si hay campeonato seleccionado
$categorias = [];
if ($campeonato_id) {
    $stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
    $stmt->execute([$campeonato_id]);
    $categorias = $stmt->fetchAll();
}

// Obtener fechas si hay categoría seleccionada
$fechas = [];
if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT f.id, f.numero_fecha, f.fecha_programada,
               COUNT(p.id) as total_partidos
        FROM fechas f
        LEFT JOIN partidos p ON f.id = p.fecha_id
        WHERE f.categoria_id = ?
        GROUP BY f.id
        ORDER BY f.numero_fecha
    ");
    $stmt->execute([$categoria_id]);
    $fechas = $stmt->fetchAll();
}

// Obtener partidos si hay fecha seleccionada
$partidos = [];
if ($fecha_id) {
    $stmt = $db->prepare("
        SELECT p.id, p.fecha_partido, p.hora_partido,
               el.nombre as equipo_local, el.logo as logo_local,
               ev.nombre as equipo_visitante, ev.logo as logo_visitante,
               c.nombre as cancha,
               p.estado,
               cat.nombre as categoria,
               camp.nombre as campeonato
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        JOIN categorias cat ON f.categoria_id = cat.id
        JOIN campeonatos camp ON cat.campeonato_id = camp.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        WHERE p.fecha_id = ?
        ORDER BY p.fecha_partido, p.hora_partido
    ");
    $stmt->execute([$fecha_id]);
    $partidos = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Descargar Planillas de Partidos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .partido-card {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s;
        }
        .partido-card:hover {
            border-color: #28a745;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .equipo-logo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
        }
        .vs-separator {
            font-size: 1.2rem;
            font-weight: bold;
            color: #6c757d;
            margin: 0 10px;
        }
        .info-badge {
            font-size: 0.85rem;
            padding: 4px 8px;
        }
    </style>
</head>
<body>
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
                    <h2><i class="fas fa-file-download"></i> Descargar Planillas de Partidos</h2>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Instrucciones:</strong> Seleccione el campeonato, categoría y fecha para ver los partidos disponibles y descargar sus planillas.
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" id="filtrosForm">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-trophy"></i> Campeonato *</label>
                                    <select name="campeonato" id="campeonatoSelect" class="form-select" required onchange="cargarCategorias()">
                                        <option value="">Seleccionar campeonato...</option>
                                        <?php foreach ($campeonatos as $camp): ?>
                                            <option value="<?= $camp['id'] ?>" <?= $campeonato_id == $camp['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($camp['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-list"></i> Categoría *</label>
                                    <select name="categoria" id="categoriaSelect" class="form-select" required onchange="cargarFechas()" <?= !$campeonato_id ? 'disabled' : '' ?>>
                                        <option value="">Seleccionar categoría...</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $categoria_id == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label"><i class="fas fa-calendar-day"></i> Fecha *</label>
                                    <select name="fecha" id="fechaSelect" class="form-select" required <?= !$categoria_id ? 'disabled' : '' ?>>
                                        <option value="">Seleccionar fecha...</option>
                                        <?php foreach ($fechas as $f): ?>
                                            <option value="<?= $f['id'] ?>" <?= $fecha_id == $f['id'] ? 'selected' : '' ?>>
                                                Fecha <?= $f['numero_fecha'] ?> - <?= formatDate($f['fecha_programada']) ?> (<?= $f['total_partidos'] ?> partidos)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i> Buscar Partidos
                                    </button>
                                    <a href="descargar_planillas.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Limpiar
                                    </a>
                                    <?php if ($fecha_id): ?>
                                        <a href="generar_planilla_pdf.php?fecha_id=<?= $fecha_id ?>&todas=1" class="btn btn-success float-end" target="_blank">
                                            <i class="fas fa-download"></i> Descargar Todas las Planillas (ZIP)
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Listado de Partidos -->
                <?php if ($fecha_id && !empty($partidos)): ?>
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-clipboard-list"></i> 
                                Partidos Disponibles (<?= count($partidos) ?> partidos)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php foreach ($partidos as $partido): ?>
                                <div class="partido-card">
                                    <div class="row align-items-center">
                                        <div class="col-md-5">
                                            <div class="d-flex align-items-center">
                                                <?php if ($partido['logo_local']): ?>
                                                    <img src="../uploads/<?= htmlspecialchars($partido['logo_local']) ?>" 
                                                         class="equipo-logo me-2" alt="Logo">
                                                <?php endif; ?>
                                                <strong><?= htmlspecialchars($partido['equipo_local']) ?></strong>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-2 text-center">
                                            <span class="vs-separator">VS</span>
                                        </div>
                                        
                                        <div class="col-md-5">
                                            <div class="d-flex align-items-center justify-content-end">
                                                <strong><?= htmlspecialchars($partido['equipo_visitante']) ?></strong>
                                                <?php if ($partido['logo_visitante']): ?>
                                                    <img src="../uploads/<?= htmlspecialchars($partido['logo_visitante']) ?>" 
                                                         class="equipo-logo ms-2" alt="Logo">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <hr class="my-2">
                                    
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex flex-wrap gap-2">
                                                <?php if ($partido['fecha_partido']): ?>
                                                    <span class="badge bg-info info-badge">
                                                        <i class="fas fa-calendar"></i> <?= formatDate($partido['fecha_partido']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($partido['hora_partido']): ?>
                                                    <span class="badge bg-primary info-badge">
                                                        <i class="fas fa-clock"></i> <?= date('H:i', strtotime($partido['hora_partido'])) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($partido['cancha']): ?>
                                                    <span class="badge bg-secondary info-badge">
                                                        <i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($partido['cancha']) ?>
                                                    </span>
                                                <?php endif; ?>
                                                <span class="badge bg-warning text-dark info-badge">
                                                    <?= ucfirst(str_replace('_', ' ', $partido['estado'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-md-4 text-end">
                                            <a href="generar_planilla_pdf.php?partido_id=<?= $partido['id'] ?>" 
                                               class="btn btn-success btn-sm" target="_blank">
                                                <i class="fas fa-file-pdf"></i> Descargar Planilla
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php elseif ($fecha_id && empty($partidos)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> No se encontraron partidos para la fecha seleccionada.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-search fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Seleccione los filtros para buscar partidos</h5>
                            <p class="text-muted">Use los selectores de arriba para filtrar por campeonato, categoría y fecha</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Información adicional -->
                <div class="card mt-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Información sobre las Planillas</h6>
                    </div>
                    <div class="card-body">
                        <ul class="mb-0">
                            <li>Las planillas se generan en formato PDF tamaño oficio</li>
                            <li>Cada planilla contiene 2 equipos (local arriba, visitante abajo)</li>
                            <li>Los jugadores sancionados aparecen en <strong>negrita con fondo amarillo</strong></li>
                            <li>Incluye columnas para: número, goles, tarjetas, firma</li>
                            <li>Espacios para firmas de delegado, DT y capitán</li>
                            <li>Información del árbitro al final de la planilla</li>
                            <li>Puede descargar planillas individuales o todas las de una fecha en un archivo ZIP</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Debug: Mostrar en consola
        console.log('Script cargado correctamente');
        
        function cargarCategorias() {
            const campeonatoId = document.getElementById('campeonatoSelect').value;
            const categoriaSelect = document.getElementById('categoriaSelect');
            const fechaSelect = document.getElementById('fechaSelect');
            
            console.log('Cargando categorías para campeonato:', campeonatoId);
            
            // Limpiar y deshabilitar
            categoriaSelect.innerHTML = '<option value="">Cargando...</option>';
            categoriaSelect.disabled = true;
            fechaSelect.innerHTML = '<option value="">Seleccionar fecha...</option>';
            fechaSelect.disabled = true;
            
            if (!campeonatoId) {
                categoriaSelect.innerHTML = '<option value="">Seleccionar categoría...</option>';
                return;
            }
            
            // Construir URL correcta
            const url = `ajax/get_categorias.php?campeonato_id=${campeonatoId}`;
            console.log('Fetching:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Data recibida:', data);
                    categoriaSelect.innerHTML = '<option value="">Seleccionar categoría...</option>';
                    
                    // Tu archivo devuelve un array directo, no un objeto con success
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(cat => {
                            const option = document.createElement('option');
                            option.value = cat.id;
                            option.textContent = cat.nombre;
                            categoriaSelect.appendChild(option);
                        });
                        categoriaSelect.disabled = false;
                        console.log(`${data.length} categorías cargadas`);
                    } else {
                        categoriaSelect.innerHTML = '<option value="">No hay categorías disponibles</option>';
                        console.warn('No se encontraron categorías');
                    }
                })
                .catch(error => {
                    console.error('Error al cargar categorías:', error);
                    categoriaSelect.innerHTML = '<option value="">Error al cargar categorías</option>';
                    alert('Error al cargar las categorías. Por favor, revise la consola del navegador (F12).');
                });
        }
        
        function cargarFechas() {
            const categoriaId = document.getElementById('categoriaSelect').value;
            const fechaSelect = document.getElementById('fechaSelect');
            
            console.log('Cargando fechas para categoría:', categoriaId);
            
            fechaSelect.innerHTML = '<option value="">Cargando...</option>';
            fechaSelect.disabled = true;
            
            if (!categoriaId) {
                fechaSelect.innerHTML = '<option value="">Seleccionar fecha...</option>';
                return;
            }
            
            const url = `ajax/get_fechas.php?categoria_id=${categoriaId}`;
            console.log('Fetching:', url);
            
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Data recibida:', data);
                    fechaSelect.innerHTML = '<option value="">Seleccionar fecha...</option>';
                    
                    // Verificar si es el formato con success o array directo
                    const fechas = data.success ? data.fechas : (Array.isArray(data) ? data : []);
                    
                    if (fechas && fechas.length > 0) {
                        fechas.forEach(fecha => {
                            const option = document.createElement('option');
                            option.value = fecha.id;
                            option.textContent = `Fecha ${fecha.numero_fecha} - ${fecha.fecha_programada} (${fecha.total_partidos} partidos)`;
                            fechaSelect.appendChild(option);
                        });
                        fechaSelect.disabled = false;
                        console.log(`${fechas.length} fechas cargadas`);
                    } else {
                        fechaSelect.innerHTML = '<option value="">No hay fechas disponibles</option>';
                        console.warn('No se encontraron fechas');
                    }
                })
                .catch(error => {
                    console.error('Error al cargar fechas:', error);
                    fechaSelect.innerHTML = '<option value="">Error al cargar fechas</option>';
                    alert('Error al cargar las fechas. Por favor, revise la consola del navegador (F12).');
                });
        }
        
        // Auto-cargar categorías si hay campeonato seleccionado al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            const campeonatoSelect = document.getElementById('campeonatoSelect');
            if (campeonatoSelect.value) {
                console.log('Auto-cargando categorías del campeonato preseleccionado');
                cargarCategorias();
            }
        });
    </script>
</body>
</html>
