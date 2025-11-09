<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/funciones_torneos_zonas.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

$message = '';
$error = '';

// Obtener campeonatos
$campeonatos = $db->query("SELECT * FROM campeonatos WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// AJAX: Obtener categorías por campeonato
if (isset($_GET['ajax']) && $_GET['ajax'] === 'categorias' && isset($_GET['campeonato_id'])) {
    header('Content-Type: application/json');
    try {
        $campeonato_id = (int)$_GET['campeonato_id'];
        // La tabla categorias no tiene columna 'nivel', solo id, nombre, campeonato_id, descripcion, activa
        $stmt = $db->prepare("SELECT id, nombre, campeonato_id FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
        $stmt->execute([$campeonato_id]);
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $categorias]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// AJAX: Obtener equipos por categoría
if (isset($_GET['ajax']) && $_GET['ajax'] === 'equipos' && isset($_GET['categoria_id'])) {
    header('Content-Type: application/json');
    try {
        $categoria_id = (int)$_GET['categoria_id'];
        $stmt = $db->prepare("SELECT id, nombre, logo FROM equipos WHERE categoria_id = ? AND activo = 1 ORDER BY nombre");
        $stmt->execute([$categoria_id]);
        $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $equipos]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// Procesar creación de torneo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_torneo'])) {
    try {
        $db->beginTransaction();
        
        // Validaciones
        $campeonato_id = $_POST['campeonato_id'];
        $categoria_id = $_POST['categoria_id'];
        $cantidad_zonas = (int)$_POST['cantidad_zonas'];
        $equipos_seleccionados = $_POST['equipos'] ?? [];
        $cantidad_equipos = count($equipos_seleccionados);
        
        // Configuración de clasificación
        $segundos_clasifican = (int)($_POST['segundos_clasifican'] ?? 0);
        $terceros_clasifican = (int)($_POST['terceros_clasifican'] ?? 0);
        $cuartos_clasifican = (int)($_POST['cuartos_clasifican'] ?? 0);
        
        // Los primeros siempre clasifican
        $primeros_clasifican = $cantidad_zonas;
        
        // Validaciones
        if ($cantidad_equipos < 4) {
            throw new Exception("Debe seleccionar al menos 4 equipos");
        }
        
        if ($cantidad_zonas < 2) {
            throw new Exception("Debe haber al menos 2 zonas");
        }
        
        if ($cantidad_zonas > $cantidad_equipos) {
            throw new Exception("No puede haber más zonas que equipos");
        }
        
        // Calcular distribución automática de equipos
        $distribucion = calcularDistribucionZonas($cantidad_equipos, $cantidad_zonas);
        
        // Crear formato
        $stmt = $db->prepare("
            INSERT INTO campeonatos_formato 
            (campeonato_id, categoria_id, tipo_formato, cantidad_zonas, equipos_clasifican,
             primeros_clasifican, segundos_clasifican, terceros_clasifican, cuartos_clasifican,
             tiene_octavos, tiene_cuartos, tiene_semifinal, tiene_tercer_puesto, activo)
            VALUES (?, ?, 'mixto', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $total_clasifican = $primeros_clasifican + $segundos_clasifican + $terceros_clasifican + $cuartos_clasifican;
        
        $stmt->execute([
            $campeonato_id,
            $categoria_id,
            $cantidad_zonas,
            $total_clasifican,
            $primeros_clasifican,
            $segundos_clasifican,
            $terceros_clasifican,
            $cuartos_clasifican,
            isset($_POST['tiene_octavos']) ? 1 : 0,
            isset($_POST['tiene_cuartos']) ? 1 : 0,
            isset($_POST['tiene_semifinal']) ? 1 : 0,
            isset($_POST['tiene_tercer_puesto']) ? 1 : 0
        ]);
        
        $formato_id = $db->lastInsertId();
        
        // Crear zonas con distribución automática
        $letras_zonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $zonas_ids = [];
        
        for ($i = 0; $i < $cantidad_zonas; $i++) {
            $stmt = $db->prepare("INSERT INTO zonas (formato_id, nombre, orden) VALUES (?, ?, ?)");
            $stmt->execute([$formato_id, "Zona {$letras_zonas[$i]}", $i + 1]);
            $zonas_ids[] = $db->lastInsertId();
        }
        
        // Mezclar equipos aleatoriamente para distribución equitativa
        shuffle($equipos_seleccionados);
        
        // Asignar equipos a zonas según la distribución calculada
        $idx_equipo = 0;
        foreach ($distribucion as $idx_zona => $cant_equipos_zona) {
            $zona_id = $zonas_ids[$idx_zona];
            $posicion = 1;
            
            for ($i = 0; $i < $cant_equipos_zona && $idx_equipo < $cantidad_equipos; $i++) {
                $equipo_id = $equipos_seleccionados[$idx_equipo];
                
                $stmt = $db->prepare("
                    INSERT INTO equipos_zonas 
                    (zona_id, equipo_id, posicion, puntos, partidos_jugados) 
                    VALUES (?, ?, ?, 0, 0)
                ");
                $stmt->execute([$zona_id, $equipo_id, $posicion++]);
                
                $idx_equipo++;
            }
            
            // Generar fixture para esta zona (todos contra todos)
            $resultado = generarFixtureZona($zona_id, $db);
            if (!$resultado) {
                throw new Exception("Error al generar fixture para la zona {$zona['nombre']}");
            }
        }
        
        // Crear fases eliminatorias (solo estructura, los partidos se crean después)
        $orden = 1;
        
        if (isset($_POST['tiene_octavos'])) {
            $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden, activa) VALUES (?, 'octavos', ?, 0)");
            $stmt->execute([$formato_id, $orden++]);
        }
        
        if (isset($_POST['tiene_cuartos'])) {
            $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden, activa) VALUES (?, 'cuartos', ?, 0)");
            $stmt->execute([$formato_id, $orden++]);
        }
        
        if (isset($_POST['tiene_semifinal'])) {
            $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden, activa) VALUES (?, 'semifinal', ?, 0)");
            $stmt->execute([$formato_id, $orden++]);
        }
        
        if (isset($_POST['tiene_tercer_puesto'])) {
            $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden, activa) VALUES (?, 'tercer_puesto', ?, 0)");
            $stmt->execute([$formato_id, $orden++]);
        }
        
        // Final siempre existe
        $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden, activa) VALUES (?, 'final', ?, 0)");
        $stmt->execute([$formato_id, $orden]);
        
        $db->commit();
        
        $message = 'Torneo creado exitosamente con ' . $cantidad_zonas . ' zonas y ' . $cantidad_equipos . ' equipos';
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error al crear torneo: ' . $e->getMessage();
    }
}

// Las funciones calcularDistribucionZonas y generarFixtureZona están en funciones_torneos_zonas.php
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Torneo con Zonas</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .preview-zona {
            background: #e7f3ff;
            border-left: 4px solid #0d6efd;
            padding: 10px 15px;
            margin: 5px;
            border-radius: 5px;
            display: inline-block;
        }
        .preview-container {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        .clasificacion-preview {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 8px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-futbol"></i> Fútbol Manager
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
                    <h2><i class="fas fa-plus-circle"></i> Crear Torneo con Zonas</h2>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Sistema Automático:</strong> 
                    Solo ingresa la cantidad de zonas y selecciona los equipos. El sistema distribuirá automáticamente los equipos de forma equitativa.
                    <br>Ejemplo: 17 equipos en 4 zonas = 3 zonas de 4 equipos + 1 zona de 5 equipos.
                </div>

                <form method="POST" id="form_crear_torneo">
                    <!-- Paso 1: Información Básica -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> 1. Información Básica</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Campeonato *</label>
                                    <select name="campeonato_id" id="campeonato_id" class="form-select" required>
                                        <option value="">Seleccionar...</option>
                                        <?php foreach ($campeonatos as $camp): ?>
                                            <option value="<?= $camp['id'] ?>"><?= htmlspecialchars($camp['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Categoría *</label>
                                    <select name="categoria_id" id="categoria_id" class="form-select" required disabled>
                                        <option value="">Seleccione un campeonato primero</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 2: Configuración de Zonas -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-layer-group"></i> 2. Configuración de Zonas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Cantidad de Zonas *</label>
                                    <select name="cantidad_zonas" id="cantidad_zonas" class="form-select" required>
                                        <option value="2">2 Zonas (A, B)</option>
                                        <option value="3">3 Zonas (A, B, C)</option>
                                        <option value="4" selected>4 Zonas (A, B, C, D)</option>
                                        <option value="5">5 Zonas (A-E)</option>
                                        <option value="6">6 Zonas (A-F)</option>
                                        <option value="8">8 Zonas (A-H)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Equipos Seleccionados</label>
                                    <input type="text" class="form-control" id="contador_equipos" value="0 equipos" readonly>
                                    <small class="text-muted">Se distribuirán automáticamente en las zonas</small>
                                </div>
                            </div>
                            
                            <!-- Vista previa de distribución -->
                            <div id="preview_distribucion" class="preview-container">
                                <h6><i class="fas fa-eye"></i> Vista Previa de Distribución:</h6>
                                <div id="preview_content"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Clasificación a Eliminatorias -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-trophy"></i> 3. Configuración de Clasificación</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-primary">
                                <i class="fas fa-star"></i> Los <strong>primeros de cada zona SIEMPRE clasifican</strong> automáticamente
                            </div>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">¿Cuántos segundos clasifican?</label>
                                    <input type="number" name="segundos_clasifican" id="segundos_clasifican" 
                                           class="form-control" min="0" max="8" value="0">
                                    <small class="text-muted">Segundos puestos que avanzan</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">¿Cuántos terceros clasifican?</label>
                                    <input type="number" name="terceros_clasifican" id="terceros_clasifican" 
                                           class="form-control" min="0" max="8" value="0">
                                    <small class="text-muted">Terceros puestos que avanzan</small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">¿Cuántos cuartos clasifican?</label>
                                    <input type="number" name="cuartos_clasifican" id="cuartos_clasifican" 
                                           class="form-control" min="0" max="8" value="0">
                                    <small class="text-muted">Cuartos puestos que avanzan</small>
                                </div>
                            </div>
                            
                            <div id="total_clasificados" class="clasificacion-preview">
                                <h6><i class="fas fa-users"></i> Total de Clasificados:</h6>
                                <p id="resumen_clasificacion" class="mb-0"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 4: Fases Eliminatorias -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-medal"></i> 4. Fases Eliminatorias</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Selecciona las fases que tendrá el torneo después de la clasificatoria:</p>
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="tiene_octavos" id="tiene_octavos">
                                        <label class="form-check-label" for="tiene_octavos">
                                            Octavos de Final (16 equipos)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="tiene_cuartos" id="tiene_cuartos" checked>
                                        <label class="form-check-label" for="tiene_cuartos">
                                            Cuartos de Final (8 equipos)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="tiene_semifinal" id="tiene_semifinal" checked>
                                        <label class="form-check-label" for="tiene_semifinal">
                                            Semifinales (4 equipos)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="tiene_tercer_puesto" id="tiene_tercer_puesto" checked>
                                        <label class="form-check-label" for="tiene_tercer_puesto">
                                            Tercer Puesto
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="alert alert-warning mt-3">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> Los partidos de fase eliminatoria se generarán automáticamente 
                                cuando TODOS los partidos de la fase clasificatoria estén completados.
                            </div>
                        </div>
                    </div>

                    <!-- Paso 5: Asignación de Equipos -->
                    <div class="card mb-4">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="fas fa-users"></i> 5. Seleccionar Equipos Participantes</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="seleccionarTodos()">
                                    <i class="fas fa-check-square"></i> Seleccionar Todos
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deseleccionarTodos()">
                                    <i class="fas fa-square"></i> Deseleccionar Todos
                                </button>
                            </div>
                            <div id="equipos_container" class="row">
                                <div class="col-12 text-center text-muted py-4">
                                    <i class="fas fa-info-circle fa-2x mb-2"></i>
                                    <p>Selecciona primero una categoría para ver los equipos disponibles</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="card">
                        <div class="card-body text-end">
                            <a href="dashboard.php" class="btn btn-secondary btn-lg">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" name="crear_torneo" class="btn btn-success btn-lg">
                                <i class="fas fa-check"></i> Crear Torneo
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cargar categorías al seleccionar campeonato
        document.getElementById('campeonato_id').addEventListener('change', function() {
            const campeonatoId = this.value;
            const categoriaSelect = document.getElementById('categoria_id');
            
            if (!campeonatoId) {
                categoriaSelect.disabled = true;
                categoriaSelect.innerHTML = '<option value="">Seleccione un campeonato primero</option>';
                document.getElementById('equipos_container').innerHTML = '<div class="col-12 text-center text-muted py-4"><i class="fas fa-info-circle fa-2x mb-2"></i><p>Selecciona una categoría</p></div>';
                return;
            }
            
            fetch(`?ajax=categorias&campeonato_id=${campeonatoId}`)
                .then(r => {
                    if (!r.ok) {
                        throw new Error(`HTTP error! status: ${r.status}`);
                    }
                    return r.json();
                })
                .then(response => {
                    if (response.success === false) {
                        throw new Error(response.error || 'Error desconocido');
                    }
                    
                    const data = response.data || response; // Compatibilidad con ambas respuestas
                    categoriaSelect.innerHTML = '<option value="">Seleccionar...</option>';
                    
                    if (Array.isArray(data) && data.length > 0) {
                        data.forEach(cat => {
                            categoriaSelect.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
                        });
                        categoriaSelect.disabled = false;
                    } else {
                        categoriaSelect.innerHTML = '<option value="">No hay categorías disponibles</option>';
                        categoriaSelect.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error al cargar categorías:', error);
                    categoriaSelect.innerHTML = '<option value="">Error al cargar categorías</option>';
                    categoriaSelect.disabled = true;
                    alert('Error al cargar las categorías: ' + error.message);
                });
        });

        // Cargar equipos al seleccionar categoría
        document.getElementById('categoria_id').addEventListener('change', function() {
            const categoriaId = this.value;
            const container = document.getElementById('equipos_container');
            
            if (!categoriaId) {
                container.innerHTML = '<div class="col-12 text-center text-muted py-4"><i class="fas fa-info-circle fa-2x mb-2"></i><p>Selecciona una categoría</p></div>';
                return;
            }
            
            container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border text-primary" role="status"></div></div>';
            
            fetch(`?ajax=equipos&categoria_id=${categoriaId}`)
                .then(r => {
                    if (!r.ok) {
                        throw new Error(`HTTP error! status: ${r.status}`);
                    }
                    return r.json();
                })
                .then(response => {
                    if (response.success === false) {
                        throw new Error(response.error || 'Error desconocido');
                    }
                    
                    const equipos = response.data || response; // Compatibilidad con ambas respuestas
                    
                    if (!Array.isArray(equipos) || equipos.length === 0) {
                        container.innerHTML = '<div class="col-12 text-center text-muted py-4"><i class="fas fa-exclamation-triangle fa-2x mb-2"></i><p>No hay equipos en esta categoría</p></div>';
                        return;
                    }
                    
                    container.innerHTML = '';
                    equipos.forEach(eq => {
                        const logo = eq.logo ? `<img src="../uploads/${eq.logo}" width="20" class="me-2" style="object-fit: contain;">` : '';
                        container.innerHTML += `
                            <div class="col-md-4 col-lg-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input equipo-checkbox" type="checkbox" 
                                           name="equipos[]" value="${eq.id}" id="eq_${eq.id}"
                                           onchange="actualizarPreview()">
                                    <label class="form-check-label" for="eq_${eq.id}">
                                        ${logo}${eq.nombre}
                                    </label>
                                </div>
                            </div>
                        `;
                    });
                })
                .catch(error => {
                    console.error('Error al cargar equipos:', error);
                    container.innerHTML = '<div class="col-12 text-center text-danger py-4"><i class="fas fa-exclamation-triangle"></i> Error al cargar equipos: ' + error.message + '</div>';
                });
        });

        // Actualizar vista previa de distribución
        function actualizarPreview() {
            const zonas = parseInt(document.getElementById('cantidad_zonas').value) || 0;
            const equiposSeleccionados = document.querySelectorAll('.equipo-checkbox:checked').length;
            
            document.getElementById('contador_equipos').value = `${equiposSeleccionados} equipos seleccionados`;
            
            if (zonas > 0 && equiposSeleccionados >= 4) {
                const distribucion = calcularDistribucion(equiposSeleccionados, zonas);
                mostrarPreviewDistribucion(distribucion, zonas);
                document.getElementById('preview_distribucion').style.display = 'block';
            } else {
                document.getElementById('preview_distribucion').style.display = 'none';
            }
            
            actualizarTotalClasificados();
        }

        // Calcular distribución automática
        function calcularDistribucion(totalEquipos, numZonas) {
            const equiposPorZona = Math.floor(totalEquipos / numZonas);
            const sobrantes = totalEquipos % numZonas;
            
            const distribucion = [];
            for (let i = 0; i < numZonas; i++) {
                distribucion.push(equiposPorZona + (i < sobrantes ? 1 : 0));
            }
            return distribucion;
        }

        // Mostrar vista previa
        function mostrarPreviewDistribucion(distribucion, numZonas) {
            const letras = 'ABCDEFGH';
            let html = '';
            
            for (let i = 0; i < numZonas; i++) {
                html += `<div class="preview-zona">
                    <strong>Zona ${letras[i]}:</strong> ${distribucion[i]} equipos
                </div>`;
            }
            
            document.getElementById('preview_content').innerHTML = html;
        }

        // Actualizar total de clasificados
        function actualizarTotalClasificados() {
            const zonas = parseInt(document.getElementById('cantidad_zonas').value) || 0;
            const segundos = parseInt(document.getElementById('segundos_clasifican').value) || 0;
            const terceros = parseInt(document.getElementById('terceros_clasifican').value) || 0;
            const cuartos = parseInt(document.getElementById('cuartos_clasifican').value) || 0;
            
            const total = zonas + segundos + terceros + cuartos;
            
            let resumen = `<strong>${total} equipos clasificarán:</strong><br>`;
            resumen += `- ${zonas} primeros de zona (automático)<br>`;
            if (segundos > 0) resumen += `- ${segundos} segundos lugares<br>`;
            if (terceros > 0) resumen += `- ${terceros} terceros lugares<br>`;
            if (cuartos > 0) resumen += `- ${cuartos} cuartos lugares`;
            
            document.getElementById('resumen_clasificacion').innerHTML = resumen;
        }

        // Seleccionar todos los equipos
        function seleccionarTodos() {
            document.querySelectorAll('.equipo-checkbox').forEach(cb => cb.checked = true);
            actualizarPreview();
        }

        // Deseleccionar todos
        function deseleccionarTodos() {
            document.querySelectorAll('.equipo-checkbox').forEach(cb => cb.checked = false);
            actualizarPreview();
        }

        // Event listeners para actualizar preview
        document.getElementById('cantidad_zonas').addEventListener('change', actualizarPreview);
        document.getElementById('segundos_clasifican').addEventListener('input', actualizarTotalClasificados);
        document.getElementById('terceros_clasifican').addEventListener('input', actualizarTotalClasificados);
        document.getElementById('cuartos_clasifican').addEventListener('input', actualizarTotalClasificados);

        // Validación del formulario
        document.getElementById('form_crear_torneo').addEventListener('submit', function(e) {
            const equiposSeleccionados = document.querySelectorAll('.equipo-checkbox:checked').length;
            const zonas = parseInt(document.getElementById('cantidad_zonas').value);
            
            if (equiposSeleccionados < 4) {
                e.preventDefault();
                alert('Debe seleccionar al menos 4 equipos para crear el torneo');
                return false;
            }
            
            if (zonas > equiposSeleccionados) {
                e.preventDefault();
                alert('No puede haber más zonas que equipos seleccionados');
                return false;
            }
            
            return true;
        });

        // Inicializar
        actualizarTotalClasificados();
    </script>
</body>
</html>