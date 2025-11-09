<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$categoria_id = $_GET['categoria_id'] ?? null;
$primeros = $_GET['primeros'] ?? 1;
$segundos = $_GET['segundos'] ?? 0;

if (!$categoria_id) {
    header('Location: categorias.php');
    exit;
}

// Obtener información de la categoría
$stmt = $pdo->prepare("
    SELECT c.*, camp.nombre as campeonato 
    FROM categorias c
    INNER JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE c.id = ?
");
$stmt->execute([$categoria_id]);
$categoria = $stmt->fetch();

// Obtener clasificados
$clasificados = [];

// Primeros de cada zona
$stmt = $pdo->prepare("
    SELECT * FROM (
        SELECT *, 
            ROW_NUMBER() OVER (PARTITION BY zona_id ORDER BY pts DESC, dif DESC, gf DESC) as posicion_zona
        FROM v_tabla_posiciones_zona 
        WHERE categoria_id = ?
    ) t
    WHERE posicion_zona <= ?
    ORDER BY zona, posicion_zona
");
$stmt->execute([$categoria_id, $primeros]);
$primeros_zonas = $stmt->fetchAll();

foreach ($primeros_zonas as $eq) {
    $clasificados[] = [
        'equipo_id' => $eq['equipo_id'],
        'equipo' => $eq['equipo'],
        'logo' => $eq['logo'],
        'zona' => $eq['zona'],
        'tipo' => 'Primero',
        'pts' => $eq['pts'],
        'dif' => $eq['dif'],
        'gf' => $eq['gf']
    ];
}

// Mejores segundos
if ($segundos > 0) {
    $stmt = $pdo->prepare("
        SELECT * FROM (
            SELECT *, 
                ROW_NUMBER() OVER (PARTITION BY zona_id ORDER BY pts DESC, dif DESC, gf DESC) as posicion_zona
            FROM v_tabla_posiciones_zona 
            WHERE categoria_id = ?
        ) t
        WHERE posicion_zona = 2
        ORDER BY pts DESC, dif DESC, gf DESC
        LIMIT ?
    ");
    $stmt->execute([$categoria_id, $segundos]);
    $mejores_segundos = $stmt->fetchAll();
    
    foreach ($mejores_segundos as $eq) {
        $clasificados[] = [
            'equipo_id' => $eq['equipo_id'],
            'equipo' => $eq['equipo'],
            'logo' => $eq['logo'],
            'zona' => $eq['zona'],
            'tipo' => 'Mejor Segundo',
            'pts' => $eq['pts'],
            'dif' => $eq['dif'],
            'gf' => $eq['gf']
        ];
    }
}

$total_clasificados = count($clasificados);

// Determinar fases necesarias
$fases_necesarias = [];

// Validar si necesita octavos (16 equipos)
$necesita_octavos = $total_clasificados > 8;

// Validar si necesita cuartos (8 equipos)
$necesita_cuartos = $total_clasificados > 4 || $necesita_octavos;

// Siempre hay semifinal y final si hay al menos 4 equipos
$necesita_semifinal = $total_clasificados >= 4;
$necesita_final = $total_clasificados >= 2;

if ($necesita_octavos) {
    $fases_necesarias[] = [
        'tipo' => 'octavos',
        'nombre' => 'Octavos de Final',
        'cantidad' => 16,
        'partidos' => 8
    ];
}

if ($necesita_cuartos) {
    $fases_necesarias[] = [
        'tipo' => 'cuartos',
        'nombre' => 'Cuartos de Final',
        'cantidad' => 8,
        'partidos' => 4
    ];
}

if ($necesita_semifinal) {
    $fases_necesarias[] = [
        'tipo' => 'semifinal',
        'nombre' => 'Semifinales',
        'cantidad' => 4,
        'partidos' => 2
    ];
}

if ($necesita_final) {
    $fases_necesarias[] = [
        'tipo' => 'tercer_puesto',
        'nombre' => 'Tercer Puesto',
        'cantidad' => 2,
        'partidos' => 1
    ];
    $fases_necesarias[] = [
        'tipo' => 'final',
        'nombre' => 'Final',
        'cantidad' => 2,
        'partidos' => 1
    ];
}

// Procesar generación de fixture
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'generar_fixture') {
    $incluir_octavos = isset($_POST['incluir_octavos']);
    $incluir_cuartos = isset($_POST['incluir_cuartos']);
    $ida_vuelta = isset($_POST['ida_vuelta']);
    
    try {
        $pdo->beginTransaction();
        
        // Eliminar fases anteriores
        $pdo->prepare("DELETE FROM fases_torneo WHERE categoria_id = ?")->execute([$categoria_id]);
        
        $orden = 1;
        $fases_creadas = [];
        
        // Crear fase de grupos
        $stmt = $pdo->prepare("
            INSERT INTO fases_torneo (categoria_id, nombre, tipo, cantidad_equipos, orden, ida_vuelta)
            VALUES (?, 'Fase de Grupos', 'grupos', ?, ?, 0)
        ");
        $stmt->execute([$categoria_id, $total_clasificados, $orden++]);
        
        // Crear octavos si aplica
        if ($incluir_octavos && $necesita_octavos) {
            $stmt = $pdo->prepare("
                INSERT INTO fases_torneo (categoria_id, nombre, tipo, cantidad_equipos, orden, ida_vuelta)
                VALUES (?, 'Octavos de Final', 'octavos', 16, ?, ?)
            ");
            $stmt->execute([$categoria_id, $orden++, $ida_vuelta ? 1 : 0]);
            $fases_creadas['octavos'] = $pdo->lastInsertId();
        }
        
        // Crear cuartos si aplica
        if ($incluir_cuartos && $necesita_cuartos) {
            $stmt = $pdo->prepare("
                INSERT INTO fases_torneo (categoria_id, nombre, tipo, cantidad_equipos, orden, ida_vuelta)
                VALUES (?, 'Cuartos de Final', 'cuartos', 8, ?, ?)
            ");
            $stmt->execute([$categoria_id, $orden++, $ida_vuelta ? 1 : 0]);
            $fases_creadas['cuartos'] = $pdo->lastInsertId();
        }
        
        // Crear semifinales
        if ($necesita_semifinal) {
            $stmt = $pdo->prepare("
                INSERT INTO fases_torneo (categoria_id, nombre, tipo, cantidad_equipos, orden, ida_vuelta)
                VALUES (?, 'Semifinales', 'semifinal', 4, ?, ?)
            ");
            $stmt->execute([$categoria_id, $orden++, $ida_vuelta ? 1 : 0]);
            $fases_creadas['semifinal'] = $pdo->lastInsertId();
        }
        
        // Crear tercer puesto
        $stmt = $pdo->prepare("
            INSERT INTO fases_torneo (categoria_id, nombre, tipo, cantidad_equipos, orden, ida_vuelta)
            VALUES (?, 'Tercer Puesto', 'tercer_puesto', 2, ?, 0)
        ");
        $stmt->execute([$categoria_id, $orden++]);
        $fases_creadas['tercer_puesto'] = $pdo->lastInsertId();
        
        // Crear final
        $stmt = $pdo->prepare("
            INSERT INTO fases_torneo (categoria_id, nombre, tipo, cantidad_equipos, orden, ida_vuelta)
            VALUES (?, 'Final', 'final', 2, ?, 0)
        ");
        $stmt->execute([$categoria_id, $orden++]);
        $fases_creadas['final'] = $pdo->lastInsertId();
        
        // Generar cruces de primera fase eliminatoria
        $primera_fase = null;
        if (isset($fases_creadas['octavos'])) {
            $primera_fase = 'octavos';
        } elseif (isset($fases_creadas['cuartos'])) {
            $primera_fase = 'cuartos';
        } elseif (isset($fases_creadas['semifinal'])) {
            $primera_fase = 'semifinal';
        }
        
        if ($primera_fase && isset($fases_creadas[$primera_fase])) {
            $fase_id = $fases_creadas[$primera_fase];
            
            // Obtener fecha más alta de grupos
            $stmt = $pdo->prepare("
                SELECT MAX(numero_fecha) as max_fecha 
                FROM fechas 
                WHERE categoria_id = ? AND fase_id IS NULL
            ");
            $stmt->execute([$categoria_id]);
            $max_fecha = $stmt->fetchColumn() ?: 0;
            $numero_fecha = $max_fecha + 1;
            
            // Crear fecha para eliminatoria
            $stmt = $pdo->prepare("
                INSERT INTO fechas (categoria_id, numero_fecha, fecha_programada, fase_id)
                VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY), ?)
            ");
            $stmt->execute([$categoria_id, $numero_fecha, $fase_id]);
            $fecha_id = $pdo->lastInsertId();
            
            // Generar cruces
            $cantidad_cruces = count($clasificados) / 2;
            
            // Emparejar: 1° vs último, 2° vs penúltimo, etc.
            for ($i = 0; $i < $cantidad_cruces; $i++) {
                $local = $clasificados[$i];
                $visitante = $clasificados[count($clasificados) - 1 - $i];
                
                // Crear partido de ida
                $stmt = $pdo->prepare("
                    INSERT INTO partidos (fecha_id, equipo_local_id, equipo_visitante_id, fecha_partido, estado)
                    VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'programado')
                ");
                $stmt->execute([$fecha_id, $local['equipo_id'], $visitante['equipo_id']]);
                $partido_id = $pdo->lastInsertId();
                
                // Registrar en eliminatorias
                $stmt = $pdo->prepare("
                    INSERT INTO partidos_eliminatorias (partido_id, fase_id, numero_llave, es_ida)
                    VALUES (?, ?, ?, 1)
                ");
                $stmt->execute([$partido_id, $fase_id, $i + 1]);
                
                // Si es ida y vuelta, crear partido de vuelta
                if ($ida_vuelta) {
                    $stmt = $pdo->prepare("
                        INSERT INTO partidos (fecha_id, equipo_local_id, equipo_visitante_id, fecha_partido, estado)
                        VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'programado')
                    ");
                    $stmt->execute([$fecha_id, $visitante['equipo_id'], $local['equipo_id']]);
                    $partido_vuelta_id = $pdo->lastInsertId();
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO partidos_eliminatorias (partido_id, fase_id, numero_llave, es_ida)
                        VALUES (?, ?, ?, 0)
                    ");
                    $stmt->execute([$partido_vuelta_id, $fase_id, $i + 1]);
                }
            }
        }
        
        $pdo->commit();
        $_SESSION['mensaje'] = "Fixture de eliminatorias generado correctamente";
        header("Location: fixture_eliminatorias.php?categoria_id={$categoria_id}&success=1");
        exit;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Error al generar fixture: " . $e->getMessage();
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixture Eliminatorias - <?= htmlspecialchars($categoria['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .llave-container {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        .fase-column {
            flex: 1;
            padding: 0 10px;
        }
        .cruce {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            transition: all 0.3s;
        }
        .cruce:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
        }
        .equipo-cruce {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .equipo-cruce img {
            width: 30px;
            height: 30px;
            object-fit: contain;
            margin-right: 10px;
        }
        .vs-badge {
            text-align: center;
            font-weight: bold;
            color: #6c757d;
            margin: 5px 0;
        }
        .clasificado-badge {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 5px;
            font-size: 0.75rem;
            margin-left: auto;
        }
        .mejor-segundo-badge {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
            color: white;
            padding: 2px 8px;
            border-radius: 5px;
            font-size: 0.75rem;
            margin-left: auto;
        }
    </style>
</head>
<body>
    <?php include '../include/sidebar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="bi bi-lightning-charge"></i> Fixture Eliminatorias</h2>
                <p class="text-muted">
                    <?= htmlspecialchars($categoria['campeonato']) ?> - 
                    <?= htmlspecialchars($categoria['nombre']) ?>
                </p>
            </div>
            <div class="col-auto">
                <a href="gestion_zonas.php?categoria_id=<?= $categoria_id ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver a Zonas
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['mensaje'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['mensaje']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Resumen de clasificados -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5><i class="bi bi-trophy"></i> Equipos Clasificados (<?= $total_clasificados ?>)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($clasificados as $index => $eq): ?>
                        <div class="col-md-4 col-lg-3 mb-3">
                            <div class="equipo-cruce">
                                <span class="badge bg-dark me-2"><?= $index + 1 ?></span>
                                <?php if ($eq['logo']): ?>
                                    <img src="../uploads/<?= htmlspecialchars($eq['logo']) ?>" alt="Logo">
                                <?php endif; ?>
                                <div class="flex-grow-1">
                                    <strong><?= htmlspecialchars($eq['equipo']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($eq['zona']) ?></small>
                                </div>
                                <span class="<?= $eq['tipo'] === 'Primero' ? 'clasificado-badge' : 'mejor-segundo-badge' ?>">
                                    <?= $eq['tipo'] === 'Primero' ? '1°' : '2°' ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Configuración de fases -->
        <div class="card mb-4">
            <div class="card-header bg-warning">
                <h5><i class="bi bi-gear"></i> Configurar Fases Eliminatorias</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <strong>Equipos clasificados:</strong> <?= $total_clasificados ?><br>
                    <strong>Fases disponibles según cantidad de equipos:</strong>
                    <ul class="mb-0 mt-2">
                        <?php foreach ($fases_necesarias as $fase): ?>
                            <li>
                                <strong><?= $fase['nombre'] ?></strong>: 
                                <?= $fase['partidos'] ?> partido(s)
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <form method="POST" id="formGenerarFixture">
                    <input type="hidden" name="action" value="generar_fixture">
                    
                    <div class="row">
                        <?php if ($necesita_octavos): ?>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="incluir_octavos" 
                                           name="incluir_octavos" <?= $necesita_octavos ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="incluir_octavos">
                                        <strong>Incluir Octavos de Final</strong>
                                        <br><small class="text-muted">Para 16 equipos</small>
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($necesita_cuartos): ?>
                            <div class="col-md-6 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="incluir_cuartos" 
                                           name="incluir_cuartos" <?= $necesita_cuartos ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="incluir_cuartos">
                                        <strong>Incluir Cuartos de Final</strong>
                                        <br><small class="text-muted">Para 8 equipos</small>
                                    </label>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-6 mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="ida_vuelta" name="ida_vuelta">
                                <label class="form-check-label" for="ida_vuelta">
                                    <strong>Partidos Ida y Vuelta</strong>
                                    <br><small class="text-muted">Excepto Final y Tercer Puesto</small>
                                </label>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-lightning-charge-fill"></i> Generar Fixture Eliminatorias
                    </button>
                </form>
            </div>
        </div>

        <!-- Preview de cruces -->
        <div class="card">
            <div class="card-header bg-success text-white">
                <h5><i class="bi bi-diagram-3"></i> Vista Previa de Cruces</h5>
            </div>
            <div class="card-body">
                <?php
                $cantidad_cruces = floor($total_clasificados / 2);
                if ($cantidad_cruces > 0):
                ?>
                    <div class="row">
                        <?php for ($i = 0; $i < $cantidad_cruces; $i++): ?>
                            <?php
                            $local = $clasificados[$i];
                            $visitante = $clasificados[$total_clasificados - 1 - $i];
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="cruce">
                                    <div class="text-center mb-2">
                                        <span class="badge bg-dark">Llave <?= $i + 1 ?></span>
                                    </div>
                                    
                                    <div class="equipo-cruce">
                                        <?php if ($local['logo']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($local['logo']) ?>" alt="Logo">
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <strong><?= htmlspecialchars($local['equipo']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($local['zona']) ?></small>
                                        </div>
                                        <span class="badge bg-primary"><?= $local['pts'] ?> pts</span>
                                    </div>
                                    
                                    <div class="vs-badge">VS</div>
                                    
                                    <div class="equipo-cruce">
                                        <?php if ($visitante['logo']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($visitante['logo']) ?>" alt="Logo">
                                        <?php endif; ?>
                                        <div class="flex-grow-1">
                                            <strong><?= htmlspecialchars($visitante['equipo']) ?></strong>
                                            <br>
                                            <small class="text-muted"><?= htmlspecialchars($visitante['zona']) ?></small>
                                        </div>
                                        <span class="badge bg-primary"><?= $visitante['pts'] ?> pts</span>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        No hay suficientes equipos para generar cruces eliminatorios.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validar formulario
        document.getElementById('formGenerarFixture')?.addEventListener('submit', function(e) {
            if (!confirm('¿Está seguro de generar el fixture de eliminatorias? Esto creará todos los partidos de las fases seleccionadas.')) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>