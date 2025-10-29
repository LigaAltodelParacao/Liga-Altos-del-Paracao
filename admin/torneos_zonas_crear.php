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

// Procesar creación de torneo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_torneo'])) {
    try {
        $db->beginTransaction();
        
        // Validaciones
        $campeonato_id = $_POST['campeonato_id'];
        $categoria_id = $_POST['categoria_id'];
        $cantidad_zonas = (int)$_POST['cantidad_zonas'];
        $equipos_por_zona = (int)$_POST['equipos_por_zona'];
        $equipos_clasifican = (int)$_POST['equipos_clasifican'];
        $tipo_clasificacion = $_POST['tipo_clasificacion'];
        
        // Crear formato
        $stmt = $db->prepare("
            INSERT INTO campeonatos_formato 
            (campeonato_id, tipo_formato, cantidad_zonas, equipos_por_zona, equipos_clasifican, 
             tipo_clasificacion, tiene_octavos, tiene_cuartos, tiene_semifinal, tiene_tercer_puesto, activo)
            VALUES (?, 'mixto', ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([
            $campeonato_id,
            $cantidad_zonas,
            $equipos_por_zona,
            $equipos_clasifican,
            $tipo_clasificacion,
            isset($_POST['tiene_octavos']) ? 1 : 0,
            isset($_POST['tiene_cuartos']) ? 1 : 0,
            isset($_POST['tiene_semifinal']) ? 1 : 0,
            isset($_POST['tiene_tercer_puesto']) ? 1 : 0
        ]);
        
        $formato_id = $db->lastInsertId();
        
        // Crear zonas
        $letras_zonas = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        for ($i = 0; $i < $cantidad_zonas; $i++) {
            $stmt = $db->prepare("INSERT INTO zonas (formato_id, nombre, orden) VALUES (?, ?, ?)");
            $stmt->execute([$formato_id, "Zona {$letras_zonas[$i]}", $i + 1]);
        }
        
        // Asignar equipos a zonas (si se seleccionaron)
        if (!empty($_POST['equipos'])) {
            $equipos_seleccionados = $_POST['equipos'];
            shuffle($equipos_seleccionados); // Sorteo aleatorio
            
            $stmt_zonas = $db->prepare("SELECT id FROM zonas WHERE formato_id = ? ORDER BY orden");
            $stmt_zonas->execute([$formato_id]);
            $zonas = $stmt_zonas->fetchAll(PDO::FETCH_COLUMN);
            
            $posicion_en_zona = array_fill(0, count($zonas), 1);
            
            foreach ($equipos_seleccionados as $index => $equipo_id) {
                $zona_idx = $index % count($zonas);
                $zona_id = $zonas[$zona_idx];
                
                $stmt = $db->prepare("
                    INSERT INTO equipos_zonas 
                    (zona_id, equipo_id, posicion, puntos, partidos_jugados) 
                    VALUES (?, ?, ?, 0, 0)
                ");
                $stmt->execute([$zona_id, $equipo_id, $posicion_en_zona[$zona_idx]++]);
            }
        }
        
        // Crear fases eliminatorias
        $fases = [];
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
                <?php include 'include/sidebar.php'; ?>
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

                    <!-- Paso 2: Configuración de Zonas -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="fas fa-layer-group"></i> 2. Configuración de Zonas</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Cantidad de Zonas *</label>
                                    <select name="cantidad_zonas" id="cantidad_zonas" class="form-select" required>
                                        <option value="2">2 Zonas (A, B)</option>
                                        <option value="4">4 Zonas (A, B, C, D)</option>
                                        <option value="6">6 Zonas (A-F)</option>
                                        <option value="8">8 Zonas (A-H)</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Equipos por Zona *</label>
                                    <select name="equipos_por_zona" id="equipos_por_zona" class="form-select" required>
                                        <option value="3">3 equipos</option>
                                        <option value="4">4 equipos</option>
                                        <option value="5">5 equipos</option>
                                        <option value="6">6 equipos</option>
                                    </select>
                                    <small class="text-muted">Total equipos: <span id="total_equipos">0</span></small>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Equipos que Clasifican *</label>
                                    <input type="number" name="equipos_clasifican" id="equipos_clasifican" class="form-control" 
                                           min="4" max="16" value="8" required>
                                    <small class="text-muted">Para fase eliminatoria</small>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Tipo de Clasificación *</label>
                                    <select name="tipo_clasificacion" class="form-select" required>
                                        <option value="1_primero">1° de cada zona</option>
                                        <option value="2_primeros">2 primeros de cada zona</option>
                                        <option value="2_primeros_2_mejores_terceros">2 primeros + 2 mejores terceros</option>
                                        <option value="personalizado">Clasificación personalizada</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paso 3: Fases Eliminatorias -->
                    <div class="card mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-trophy"></i> 3. Fases Eliminatorias</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Selecciona las fases que tendrá el torneo:</p>
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
                                <i class="fas fa-info-circle"></i> La Final se creará automáticamente
                            </div>
                        </div>
                    </div>

                    <!-- Paso 4: Asignación de Equipos (Opcional) -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-users"></i> 4. Asignar Equipos (Opcional)</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">Selecciona los equipos que participarán. Se distribuirán automáticamente en las zonas.</p>
                            <div id="equipos_container" class="row">
                                <div class="col-12 text-center text-muted">
                                    <i class="fas fa-info-circle"></i> Selecciona primero una categoría
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Botones -->
                    <div class="card">
                        <div class="card-body text-end">
                            <a href="torneos_zonas.php" class="btn btn-secondary">
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
                return;
            }
            
            fetch(`?ajax=equipos&categoria_id=${categoriaId}`)
                .then(r => r.json())
                .then(equipos => {
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
                                    <input class="form-check-input" type="checkbox" name="equipos[]" value="${eq.id}" id="eq_${eq.id}">
                                    <label class="form-check-label" for="eq_${eq.id}">
                                        ${logo}${eq.nombre}
                                    </label>
                                </div>
                            </div>
                        `;
                    });
                });
        });

        // Calcular total de equipos
        function calcularTotalEquipos() {
            const zonas = parseInt(document.getElementById('cantidad_zonas').value) || 0;
            const equiposPorZona = parseInt(document.getElementById('equipos_por_zona').value) || 0;
            document.getElementById('total_equipos').textContent = zonas * equiposPorZona;
        }

        document.getElementById('cantidad_zonas').addEventListener('change', calcularTotalEquipos);
        document.getElementById('equipos_por_zona').addEventListener('change', calcularTotalEquipos);
        calcularTotalEquipos();
    </script>
</body>
</html>
