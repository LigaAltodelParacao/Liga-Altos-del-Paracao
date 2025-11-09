<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/funciones_torneos_zonas.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

$message = '';
$error = '';

// Obtener campeonatos activos
$campeonatos = $db->query("SELECT * FROM campeonatos WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Variables para filtros
$campeonato_id = $_GET['campeonato_id'] ?? null;
$categoria_id = $_GET['categoria_id'] ?? null;
$formato_id = $_GET['formato_id'] ?? null;

$categorias = [];
$formatos = [];
$tiene_zonas = false;
$zonas = [];
$equipos_por_zona = [];

// Cargar categorías si hay campeonato
if ($campeonato_id) {
    $stmt = $db->prepare("SELECT * FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
    $stmt->execute([$campeonato_id]);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Cargar formatos si hay categoría
if ($categoria_id) {
    // Verificar si hay formatos de zona
    $stmt = $db->prepare("SELECT * FROM campeonatos_formato WHERE campeonato_id = ? LIMIT 1");
    $stmt->execute([$campeonato_id]);
    $formato_zona = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($formato_zona) {
        $tiene_zonas = true;
        $stmt = $db->prepare("SELECT * FROM campeonatos_formato WHERE campeonato_id = ?");
        $stmt->execute([$campeonato_id]);
        $formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Si hay formato seleccionado, cargar zonas
if ($formato_id && $tiene_zonas) {
    $stmt = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
    $stmt->execute([$formato_id]);
    $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cargar equipos por zona
    foreach ($zonas as $zona) {
        $stmt = $db->prepare("
            SELECT e.*, ez.posicion
            FROM equipos_zonas ez
            JOIN equipos e ON ez.equipo_id = e.id
            WHERE ez.zona_id = ?
            ORDER BY ez.posicion
        ");
        $stmt->execute([$zona['id']]);
        $equipos_por_zona[$zona['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Obtener canchas
$canchas = $db->query("SELECT * FROM canchas WHERE activa = 1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Procesar generación de fixture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_fixture'])) {
    $tipo_campeonato = $_POST['tipo_campeonato'] ?? 'comun';
    
    try {
        $db->beginTransaction();
        
        if ($tipo_campeonato === 'zonas') {
            // Generar fixture para campeonato con zonas
            $formato_id = $_POST['formato_id'] ?? null;
            if (!$formato_id) {
                throw new Exception('Formato no especificado');
            }
            
            // Obtener zonas del formato
            $stmt = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
            $stmt->execute([$formato_id]);
            $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($zonas)) {
                throw new Exception('No hay zonas configuradas para este formato');
            }
            
            // Generar fixture para cada zona
            foreach ($zonas as $zona) {
                $resultado = generarFixtureZona($zona['id'], $db);
                if (!$resultado) {
                    throw new Exception("Error al generar fixture para la zona {$zona['nombre']}");
                }
            }
            
            $message = 'Fixture de zonas generado exitosamente.';
        } else {
            // Generar fixture común
            generarFixtureComun($db, $_POST);
            $message = 'Fixture común generado exitosamente.';
        }
        
        $db->commit();
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = 'Error al generar fixture: ' . $e->getMessage();
    }
}

/**
 * Genera fixture común (todos contra todos)
 */
function generarFixtureComun($db, $data) {
    $categoria_id = $data['categoria_id'] ?? null;
    $fecha_inicio = $data['fecha_inicio'] ?? date('Y-m-d');
    $dias_entre_fechas = (int)($data['dias_entre_fechas'] ?? 7);
    $tipo_fixture = $data['tipo_fixture'] ?? 'todos_contra_todos';
    
    if (!$categoria_id) {
        throw new Exception('Categoría no especificada');
    }
    
    // Limpiar fechas y partidos existentes
    $stmt = $db->prepare("DELETE FROM partidos WHERE fecha_id IN (SELECT id FROM fechas WHERE categoria_id = ?)");
    $stmt->execute([$categoria_id]);
    
    $stmt = $db->prepare("DELETE FROM fechas WHERE categoria_id = ?");
    $stmt->execute([$categoria_id]);
    
    // Obtener equipos de la categoría
    $stmt = $db->prepare("
        SELECT id, nombre
        FROM equipos
        WHERE categoria_id = ? AND activo = 1
        ORDER BY nombre
    ");
    $stmt->execute([$categoria_id]);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($equipos) < 2) {
        throw new Exception('La categoría debe tener al menos 2 equipos');
    }
    
    // Generar fixture
    $partidos = generarTodosContraTodos($equipos);
    
    // Si es ida y vuelta
    if ($tipo_fixture === 'ida_vuelta') {
        $partidos_vuelta = [];
        foreach ($partidos as $fecha => $partidos_fecha) {
            foreach ($partidos_fecha as $partido) {
                $partidos_vuelta[$fecha][] = [
                    'local' => $partido['visitante'],
                    'visitante' => $partido['local']
                ];
            }
        }
        $ultima_fecha = max(array_keys($partidos));
        foreach ($partidos_vuelta as $fecha => $partidos_fecha) {
            $partidos[$ultima_fecha + $fecha] = $partidos_fecha;
        }
    }
    
    // Insertar fechas y partidos
    $fecha_actual = new DateTime($fecha_inicio);
    
    foreach ($partidos as $numero_fecha => $partidos_fecha) {
        // Crear fecha
        $stmt = $db->prepare("
            INSERT INTO fechas (categoria_id, numero_fecha, fecha_programada, activa)
            VALUES (?, ?, ?, 1)
        ");
        $stmt->execute([$categoria_id, $numero_fecha, $fecha_actual->format('Y-m-d')]);
        $fecha_id = $db->lastInsertId();
        
        // Insertar partidos
        $stmt_partido = $db->prepare("
            INSERT INTO partidos 
            (fecha_id, equipo_local_id, equipo_visitante_id, fecha_partido, estado)
            VALUES (?, ?, ?, ?, 'programado')
        ");
        
        foreach ($partidos_fecha as $partido) {
            $stmt_partido->execute([
                $fecha_id,
                $partido['local']['id'],
                $partido['visitante']['id'],
                $fecha_actual->format('Y-m-d')
            ]);
        }
        
        $fecha_actual->modify("+{$dias_entre_fechas} days");
    }
    
    logActivity("Fixture común generado para categoría $categoria_id");
}

/**
 * Genera fixture para campeonato con zonas
 */
function generarFixtureZonas($db, $data) {
    $formato_id = $data['formato_id'] ?? null;
    $fecha_inicio = $data['fecha_inicio'] ?? date('Y-m-d');
    $dias_entre_fechas = (int)($data['dias_entre_fechas'] ?? 7);
    $tipo_fixture = $data['tipo_fixture'] ?? 'todos_contra_todos';
    
    if (!$formato_id) {
        throw new Exception('Formato no especificado');
    }
    
    // Obtener formato
    $stmt = $db->prepare("SELECT * FROM campeonatos_formato WHERE id = ?");
    $stmt->execute([$formato_id]);
    $formato = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$formato) {
        throw new Exception('Formato no encontrado');
    }
    
    // Limpiar partidos existentes de zonas
    $stmt = $db->prepare("
        DELETE FROM partidos 
        WHERE zona_id IN (SELECT id FROM zonas WHERE formato_id = ?) AND tipo_torneo = 'zona'
    ");
    $stmt->execute([$formato_id]);
    
    // Limpiar fechas de zonas
    $stmt = $db->prepare("
        DELETE FROM fechas 
        WHERE zona_id IN (SELECT id FROM zonas WHERE formato_id = ?) AND tipo_fecha = 'zona'
    ");
    $stmt->execute([$formato_id]);
    
    // Limpiar partidos eliminatorios
    $stmt = $db->prepare("
        DELETE FROM partidos 
        WHERE fase_eliminatoria_id IN (SELECT id FROM fases_eliminatorias WHERE formato_id = ?) AND tipo_torneo = 'eliminatoria'
    ");
    $stmt->execute([$formato_id]);
    
    // Obtener zonas
    $stmt = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
    $stmt->execute([$formato_id]);
    $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generar fixture para cada zona usando la función
    foreach ($zonas as $zona) {
        $resultado = generarFixtureZona($zona['id'], $db);
        if (!$resultado) {
            throw new Exception("Error al generar fixture para la zona {$zona['nombre']}");
        }
    }
    
    // Los partidos eliminatorios se generan automáticamente cuando se completa la fase de grupos
    // usando la función generarFixtureEliminatorias() en funciones_torneos_zonas.php
    
    logActivity("Fixture de zonas generado para formato $formato_id");
}

/**
 * Genera fixture todos contra todos usando algoritmo Round-Robin
 */
function generarTodosContraTodos($equipos) {
    $n = count($equipos);
    
    $tiene_dummy = ($n % 2 != 0);
    if ($tiene_dummy) {
        $equipos[] = ['id' => null, 'nombre' => 'DESCANSO'];
        $n++;
    }
    
    $total_fechas = $n - 1;
    $partidos_por_fecha = $n / 2;
    
    $fixture = [];
    
    for ($fecha = 1; $fecha <= $total_fechas; $fecha++) {
        $fixture[$fecha] = [];
        
        for ($i = 0; $i < $partidos_por_fecha; $i++) {
            $local_idx = ($fecha + $i - 1) % ($n - 1);
            $visitante_idx = ($n - 1 - $i + $fecha - 1) % ($n - 1);
            
            if ($i == 0) {
                $visitante_idx = $n - 1;
            }
            
            $local = $equipos[$local_idx];
            $visitante = $equipos[$visitante_idx];
            
            if ($fecha % 2 == 0) {
                $temp = $local;
                $local = $visitante;
                $visitante = $temp;
            }
            
            if ($local['id'] !== null && $visitante['id'] !== null) {
                $fixture[$fecha][] = [
                    'local' => $local,
                    'visitante' => $visitante
                ];
            }
        }
    }
    
    return $fixture;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Fixture</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
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
                <h2><i class="fas fa-calendar-alt"></i> Generar Fixture</h2>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?= htmlspecialchars($message) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-filter"></i> Seleccionar Campeonato</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" id="filterForm">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="form-label">Campeonato *</label>
                                    <select name="campeonato_id" class="form-select" required onchange="this.form.submit()">
                                        <option value="">Seleccionar...</option>
                                        <?php foreach ($campeonatos as $camp): ?>
                                            <option value="<?= $camp['id'] ?>" <?= ($campeonato_id == $camp['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($camp['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <?php if ($campeonato_id && !empty($categorias)): ?>
                                <div class="col-md-4">
                                    <label class="form-label">Categoría *</label>
                                    <select name="categoria_id" class="form-select" required onchange="this.form.submit()">
                                        <option value="">Seleccionar...</option>
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= ($categoria_id == $cat['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>

                                <?php if ($tiene_zonas && !empty($formatos)): ?>
                                <div class="col-md-4">
                                    <label class="form-label">Formato de Zonas</label>
                                    <select name="formato_id" class="form-select" onchange="this.form.submit()">
                                        <option value="">Seleccionar...</option>
                                        <?php foreach ($formatos as $fmt): ?>
                                            <option value="<?= $fmt['id'] ?>" <?= ($formato_id == $fmt['id']) ? 'selected' : '' ?>>
                                                <?= $fmt['cantidad_zonas'] ?> Zonas - <?= $fmt['equipos_por_zona'] ?> equipos c/u
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if (($categoria_id && !$tiene_zonas) || ($formato_id && $tiene_zonas)): ?>
                <!-- Formulario de generación -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Configuración del Fixture</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="tipo_campeonato" value="<?= $tiene_zonas ? 'zonas' : 'comun' ?>">
                            <input type="hidden" name="campeonato_id" value="<?= htmlspecialchars($campeonato_id) ?>">
                            
                            <?php if ($tiene_zonas): ?>
                                <input type="hidden" name="formato_id" value="<?= htmlspecialchars($formato_id) ?>">
                            <?php else: ?>
                                <input type="hidden" name="categoria_id" value="<?= htmlspecialchars($categoria_id) ?>">
                            <?php endif; ?>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Fecha de Inicio *</label>
                                        <input type="date" name="fecha_inicio" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Días entre Fechas</label>
                                        <input type="number" name="dias_entre_fechas" class="form-control" value="7" min="1" max="30">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Tipo de Fixture</label>
                                        <select name="tipo_fixture" class="form-select">
                                            <option value="todos_contra_todos">Todos contra Todos (Ida)</option>
                                            <option value="ida_vuelta">Ida y Vuelta</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <?php if ($tiene_zonas && !empty($zonas)): ?>
                            <div class="alert alert-info">
                                <strong><i class="fas fa-info-circle"></i> Zonas del Campeonato:</strong>
                                <div class="row mt-2">
                                    <?php foreach ($zonas as $zona): ?>
                                        <div class="col-md-6">
                                            <strong><?= htmlspecialchars($zona['nombre']) ?>:</strong>
                                            <?= count($equipos_por_zona[$zona['id']] ?? []) ?> equipos
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="text-end">
                                <button type="submit" name="generar_fixture" class="btn btn-success btn-lg">
                                    <i class="fas fa-magic"></i> Generar Fixture
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>