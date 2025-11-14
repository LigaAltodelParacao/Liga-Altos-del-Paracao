<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

$message = '';
$error = '';

// Obtener campeonatos y categorías
$campeonatos = $db->query("SELECT * FROM campeonatos WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// AJAX: Obtener categorías por campeonato
if (isset($_GET['ajax']) && $_GET['ajax'] === 'categorias' && isset($_GET['campeonato_id'])) {
    header('Content-Type: application/json');
    $stmt = $db->prepare("SELECT * FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
    $stmt->execute([$_GET['campeonato_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// AJAX: Obtener equipos por categoría
if (isset($_GET['ajax']) && $_GET['ajax'] === 'equipos' && isset($_GET['categoria_id'])) {
    header('Content-Type: application/json');
    $stmt = $db->prepare("SELECT id, nombre, logo FROM equipos WHERE categoria_id = ? AND activo = 1 ORDER BY nombre");
    $stmt->execute([$_GET['categoria_id']]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

// Función para distribuir equipos en zonas automáticamente
function distribuirEquiposEnZonas($total_equipos, $cantidad_zonas) {
    $equipos_base = floor($total_equipos / $cantidad_zonas);
    $equipos_extra = $total_equipos % $cantidad_zonas;
    
    $distribucion = [];
    for ($i = 0; $i < $cantidad_zonas; $i++) {
        $equipos_en_zona = $equipos_base + ($i < $equipos_extra ? 1 : 0);
        $distribucion[] = $equipos_en_zona;
    }
    
    return $distribucion;
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
        
        if (empty($equipos_seleccionados)) {
            throw new Exception('Debes seleccionar al menos un equipo para crear el torneo.');
        }
        
        $total_equipos = count($equipos_seleccionados);
        
        // Calcular distribución automática
        $distribucion = distribuirEquiposEnZonas($total_equipos, $cantidad_zonas);
        
        // Clasificación
        $primeros = (int)($_POST['primeros'] ?? 1);
        $segundos = (int)($_POST['segundos'] ?? 0);
        $terceros = (int)($_POST['terceros'] ?? 0);
        $cuartos = (int)($_POST['cuartos'] ?? 0);
        
        $equipos_clasifican = ($primeros * $cantidad_zonas) + $segundos + $terceros + $cuartos;
        
        // Construir tipo de clasificación
        $tipo_clasificacion_partes = [];
        if ($primeros > 0) {
            $tipo_clasificacion_partes[] = "{$primeros}° de cada zona";
        }
        if ($segundos > 0) {
            $tipo_clasificacion_partes[] = "{$segundos} mejores 2°";
        }
        if ($terceros > 0) {
            $tipo_clasificacion_partes[] = "{$terceros} mejores 3°";
        }
        if ($cuartos > 0) {
            $tipo_clasificacion_partes[] = "{$cuartos} mejores 4°";
        }
        $tipo_clasificacion = implode(' + ', $tipo_clasificacion_partes);
        
        // Calcular equipos_por_zona (promedio para referencia)
        $equipos_por_zona = ceil($total_equipos / $cantidad_zonas);
        
        // Crear formato
        $stmt = $db->prepare("
            INSERT INTO campeonatos_formato 
            (campeonato_id, categoria_id, tipo_formato, cantidad_zonas, equipos_por_zona, equipos_clasifican, 
             tipo_clasificacion, primeros_clasifican, segundos_clasifican, terceros_clasifican, cuartos_clasifican,
             tiene_octavos, tiene_cuartos, tiene_semifinal, tiene_tercer_puesto, activo)
            VALUES (?, ?, 'mixto', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $campeonato_id,
            $categoria_id,
            $cantidad_zonas,
            $equipos_por_zona,
            $equipos_clasifican,
            $tipo_clasificacion,
            $primeros,
            $segundos,
            $terceros,
            $cuartos,
            isset($_POST['tiene_octavos']) ? 1 : 0,
            isset($_POST['tiene_cuartos']) ? 1 : 0,
            isset($_POST['tiene_semifinal']) ? 1 : 0,
            isset($_POST['tiene_tercer_puesto']) ? 1 : 0
        ]);
        
        $formato_id = $db->lastInsertId();
        
        // Crear zonas con la cantidad real de equipos
        $letras_zonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        $zonas_ids = [];
        
        for ($i = 0; $i < $cantidad_zonas; $i++) {
            $stmt = $db->prepare("INSERT INTO zonas (formato_id, nombre, orden) VALUES (?, ?, ?)");
            $stmt->execute([$formato_id, "Zona {$letras_zonas[$i]}", $i + 1]);
            $zonas_ids[] = $db->lastInsertId();
        }
        
        // Distribuir equipos en zonas según la distribución calculada
        shuffle($equipos_seleccionados); // Sorteo aleatorio
        
        $equipo_index = 0;
        foreach ($zonas_ids as $zona_idx => $zona_id) {
            $equipos_en_esta_zona = $distribucion[$zona_idx];
            
            for ($pos = 1; $pos <= $equipos_en_esta_zona; $pos++) {
                if ($equipo_index >= count($equipos_seleccionados)) break;
                
                $equipo_id = $equipos_seleccionados[$equipo_index];
                
                $stmt = $db->prepare("
                    INSERT INTO equipos_zonas 
                    (zona_id, equipo_id, posicion, puntos, partidos_jugados) 
                    VALUES (?, ?, ?, 0, 0)
                ");
                $stmt->execute([$zona_id, $equipo_id, $pos]);
                
                $equipo_index++;
            }
        }
        
        // Crear fases eliminatorias (solo estructura, no los partidos)
        $orden = 1;
        
        if (isset($_POST['tiene_octavos'])) {
            $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden) VALUES (?, 'octavos', ?)");
            $stmt->execute([$formato_id, $orden++]);
        }
        
        if (isset($_POST['tiene_cuartos'])) {
            $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden) VALUES (?, 'cuartos', ?)");
            $stmt->execute([$formato_id, $orden++]);
        }
        
        if (isset($_POST['tiene_semifinal'])) {
            $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden) VALUES (?, 'semifinal', ?)");
            $stmt->execute([$formato_id, $orden++]);
        }
        
        if (isset($_POST['tiene_tercer_puesto'])) {
            $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden) VALUES (?, 'tercer_puesto', ?)");
            $stmt->execute([$formato_id, $orden++]);
        }
        
        // Final siempre existe
        $stmt = $db->prepare("INSERT INTO fases_eliminatorias (formato_id, nombre, orden) VALUES (?, 'final', ?)");
        $stmt->execute([$formato_id, $orden]);
        
        $db->commit();
        
        header("Location: torneos_zonas.php?msg=created");
        exit;
        
    } catch (Exception $e) {
        $db->rollBack();
        $error = 'Error al crear torneo: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Torneo con Zonas</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-futbol"></i> Fútbol Manager
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
                <?php include __DIR__ . '/../include/sidebar.php'; ?>
            </div>

            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-plus-circle"></i> Crear Torneo con Zonas</h2>
                    <a href="torneos_zonas.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

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

                    <!-- Paso 2: Asignación de Equipos -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-users"></i> 2. Seleccionar Equipos Participantes *</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Selecciona los equipos que participarán en el torneo. La cantidad de equipos determinará la distribución en zonas.</p>
                            <div class="mb-3">
                                <button type="button" class="btn btn-sm btn-success" id="seleccionar_todos">
                                    <i class="fas fa-check-double"></i> Seleccionar Todos
                                </button>
                                <button type="button" class="btn btn-sm btn-warning" id="deseleccionar_todos">
                                    <i class="fas fa-times"></i> Deseleccionar Todos
                                </button>
                                <span class="ms-3 badge bg-primary" id="contador_equipos">0 equipos seleccionados</span>
                            </div>
                            <div id="equipos_container" class="row">
                                <div class="col-12 text-center text-muted">
                                    <i class="fas fa-info-circle"></i> Selecciona primero una categoría
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Configuración de Zonas -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-layer-group"></i> 3. Configuración de Zonas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Cantidad de Zonas *</label>
                                    <select name="cantidad_zonas" id="cantidad_zonas" class="form-select" required>
                                        <option value="2">2 Zonas</option>
                                        <option value="3">3 Zonas</option>
                                        <option value="4" selected>4 Zonas</option>
                                        <option value="5">5 Zonas</option>
                                        <option value="6">6 Zonas</option>
                                        <option value="7">7 Zonas</option>
                                        <option value="8">8 Zonas</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Distribución Automática</label>
                                    <div class="alert alert-info mb-0" id="distribucion_info">
                                        <i class="fas fa-info-circle"></i> Selecciona equipos y zonas para ver la distribución
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 4: Tipo de Clasificación -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-medal"></i> 4. Clasificación a Fase Eliminatoria</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Define qué equipos clasifican a la fase eliminatoria:</p>
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Primeros de cada zona *</label>
                                    <select name="primeros" id="primeros" class="form-select" required>
                                        <option value="1" selected>1° de cada zona</option>
                                        <option value="2">1° y 2° de cada zona</option>
                                    </select>
                                    <small class="text-muted">Siempre clasifican</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Mejores Segundos</label>
                                    <input type="number" name="segundos" id="segundos" class="form-control" min="0" max="8" value="0">
                                    <small class="text-muted">Ej: 2 mejores 2°</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Mejores Terceros</label>
                                    <input type="number" name="terceros" id="terceros" class="form-control" min="0" max="8" value="0">
                                    <small class="text-muted">Ej: 2 mejores 3°</small>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Mejores Cuartos</label>
                                    <input type="number" name="cuartos" id="cuartos" class="form-control" min="0" max="8" value="0">
                                    <small class="text-muted">Ej: 1 mejor 4°</small>
                                </div>
                            </div>
                            <div class="alert alert-success" id="total_clasifican_info">
                                <i class="fas fa-calculator"></i> <strong>Total equipos que clasifican:</strong> <span id="total_clasifican">0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 5: Fases Eliminatorias -->
                    <div class="card mb-4">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0"><i class="fas fa-trophy"></i> 5. Fases Eliminatorias</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Selecciona las fases que tendrá el torneo (sistema bracket de eliminación directa con siembra):</p>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> <strong>Importante:</strong> La fase eliminatoria se ejecutará automáticamente cuando se terminen TODOS los partidos de la fase clasificatoria (zonas).
                            </div>
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
                            <div class="alert alert-info mt-3">
                                <i class="fas fa-info-circle"></i> La <strong>Final</strong> se creará automáticamente
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="card">
                        <div class="card-body text-end">
                            <a href="torneos_zonas.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" name="crear_torneo" class="btn btn-success btn-lg" id="btn_crear">
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
        let equiposData = [];
        
        // Cargar categorías al seleccionar campeonato
        document.getElementById('campeonato_id').addEventListener('change', function() {
            const campeonatoId = this.value;
            const categoriaSelect = document.getElementById('categoria_id');
            
            if (!campeonatoId) {
                categoriaSelect.disabled = true;
                categoriaSelect.innerHTML = '<option value="">Seleccione un campeonato primero</option>';
                return;
            }
            
            fetch(`?ajax=categorias&campeonato_id=${campeonatoId}`)
                .then(r => r.json())
                .then(data => {
                    categoriaSelect.innerHTML = '<option value="">Seleccionar...</option>';
                    data.forEach(cat => {
                        categoriaSelect.innerHTML += `<option value="${cat.id}">${cat.nombre}</option>`;
                    });
                    categoriaSelect.disabled = false;
                });
        });

        // Cargar equipos al seleccionar categoría
        document.getElementById('categoria_id').addEventListener('change', function() {
            const categoriaId = this.value;
            const container = document.getElementById('equipos_container');
            
            if (!categoriaId) {
                container.innerHTML = '<div class="col-12 text-center text-muted"><i class="fas fa-info-circle"></i> Selecciona una categoría</div>';
                equiposData = [];
                actualizarContador();
                return;
            }
            
            fetch(`?ajax=equipos&categoria_id=${categoriaId}`)
                .then(r => r.json())
                .then(equipos => {
                    equiposData = equipos;
                    
                    if (equipos.length === 0) {
                        container.innerHTML = '<div class="col-12 text-center text-muted">No hay equipos en esta categoría</div>';
                        return;
                    }
                    
                    container.innerHTML = '';
                    equipos.forEach(eq => {
                        const logo = eq.logo ? `<img src="../uploads/${eq.logo}" width="20" class="me-2">` : '';
                        container.innerHTML += `
                            <div class="col-md-4 col-lg-3 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input equipo-checkbox" type="checkbox" name="equipos[]" value="${eq.id}" id="eq_${eq.id}">
                                    <label class="form-check-label" for="eq_${eq.id}">
                                        ${logo}${eq.nombre}
                                    </label>
                                </div>
                            </div>
                        `;
                    });
                    
                    // Agregar event listeners a los checkboxes
                    document.querySelectorAll('.equipo-checkbox').forEach(checkbox => {
                        checkbox.addEventListener('change', function() {
                            actualizarContador();
                            calcularDistribucion();
                        });
                    });
                });
        });

        // Seleccionar/Deseleccionar todos
        document.getElementById('seleccionar_todos').addEventListener('click', function() {
            document.querySelectorAll('.equipo-checkbox').forEach(cb => {
                cb.checked = true;
            });
            actualizarContador();
            calcularDistribucion();
        });

        document.getElementById('deseleccionar_todos').addEventListener('click', function() {
            document.querySelectorAll('.equipo-checkbox').forEach(cb => {
                cb.checked = false;
            });
            actualizarContador();
            calcularDistribucion();
        });

        // Actualizar contador de equipos
        function actualizarContador() {
            const total = document.querySelectorAll('.equipo-checkbox:checked').length;
            document.getElementById('contador_equipos').textContent = `${total} equipos seleccionados`;
        }

        // Calcular distribución automática
        function calcularDistribucion() {
            const totalEquipos = document.querySelectorAll('.equipo-checkbox:checked').length;
            const zonas = parseInt(document.getElementById('cantidad_zonas').value) || 0;
            const infoDiv = document.getElementById('distribucion_info');
            
            if (totalEquipos === 0 || zonas === 0) {
                infoDiv.innerHTML = '<i class="fas fa-info-circle"></i> Selecciona equipos y zonas para ver la distribución';
                return;
            }
            
            const equiposBase = Math.floor(totalEquipos / zonas);
            const equiposExtra = totalEquipos % zonas;
            
            let distribucionTexto = '<strong>Distribución automática:</strong><br>';
            const letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
            
            for (let i = 0; i < zonas; i++) {
                const equiposEnZona = equiposBase + (i < equiposExtra ? 1 : 0);
                distribucionTexto += `Zona ${letras[i]}: ${equiposEnZona} equipos<br>`;
            }
            
            infoDiv.innerHTML = distribucionTexto;
            calcularTotalClasifican();
        }

        // Calcular total de equipos que clasifican
        function calcularTotalClasifican() {
            const zonas = parseInt(document.getElementById('cantidad_zonas').value) || 0;
            const primeros = parseInt(document.getElementById('primeros').value) || 0;
            const segundos = parseInt(document.getElementById('segundos').value) || 0;
            const terceros = parseInt(document.getElementById('terceros').value) || 0;
            const cuartos = parseInt(document.getElementById('cuartos').value) || 0;
            
            const total = (primeros * zonas) + segundos + terceros + cuartos;
            document.getElementById('total_clasifican').textContent = total;
        }

        // Event listeners para calcular clasificados
        ['cantidad_zonas', 'primeros', 'segundos', 'terceros', 'cuartos'].forEach(id => {
            document.getElementById(id).addEventListener('change', function() {
                calcularDistribucion();
                calcularTotalClasifican();
            });
        });

        // Validación antes de enviar
        document.getElementById('form_crear_torneo').addEventListener('submit', function(e) {
            const equiposSeleccionados = document.querySelectorAll('.equipo-checkbox:checked').length;
            
            if (equiposSeleccionados === 0) {
                e.preventDefault();
                alert('Debes seleccionar al menos un equipo para crear el torneo.');
                return false;
            }
            
            const zonas = parseInt(document.getElementById('cantidad_zonas').value);
            if (equiposSeleccionados < zonas) {
                e.preventDefault();
                alert(`Necesitas al menos ${zonas} equipos para crear ${zonas} zonas.`);
                return false;
            }
        });

        // Inicializar cálculos
        calcularDistribucion();
        calcularTotalClasifican();
    </script>
</body>
</html>