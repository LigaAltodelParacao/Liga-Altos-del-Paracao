<?php
/**
 * Control de partidos de fase de grupos (zonas)
 * Permite cargar resultados y gestionar partidos
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/funciones_torneos_zonas.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

$formato_id = $_GET['formato_id'] ?? null;

if (!$formato_id) {
    redirect('torneos_zonas.php');
}

// Obtener información del torneo
$stmt = $db->prepare("
    SELECT 
        cf.*,
        c.nombre as campeonato_nombre,
        cat.nombre as categoria_nombre,
        cat.id as categoria_id
    FROM campeonatos_formato cf
    JOIN campeonatos c ON cf.campeonato_id = c.id
    LEFT JOIN categorias cat ON cf.categoria_id = cat.id
    WHERE cf.id = ?
");
$stmt->execute([$formato_id]);
$torneo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$torneo) {
    redirect('torneos_zonas.php');
}

// Obtener zonas
$stmt = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
$stmt->execute([$formato_id]);
$zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'guardar_resultado') {
        try {
            $db->beginTransaction();
            
            $partido_id = (int)$_POST['partido_id'];
            $goles_local = (int)$_POST['goles_local'];
            $goles_visitante = (int)$_POST['goles_visitante'];
            $observaciones = trim($_POST['observaciones'] ?? '');
            
            // Obtener información del partido
            $stmt = $db->prepare("
                SELECT zona_id, equipo_local_id, equipo_visitante_id 
                FROM partidos 
                WHERE id = ? AND tipo_torneo = 'zona'
            ");
            $stmt->execute([$partido_id]);
            $partido = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$partido) {
                throw new Exception("Partido no encontrado");
            }
            
            // Actualizar resultado (usando tabla partidos)
            $stmt = $db->prepare("
                UPDATE partidos 
                SET goles_local = ?, goles_visitante = ?, observaciones = ?, 
                    estado = 'finalizado', finalizado_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$goles_local, $goles_visitante, $observaciones, $partido_id]);
            
            // Actualizar estadísticas de ambos equipos si es partido de zona
            if ($partido['zona_id']) {
                actualizarEstadisticasZona($partido['zona_id'], $partido['equipo_local_id'], $db);
                actualizarEstadisticasZona($partido['zona_id'], $partido['equipo_visitante_id'], $db);
            }
            
            // Procesar eventos y sanciones automáticas (si existen las funciones)
            $sanciones_file = __DIR__ . '/../include/sanciones_functions.php';
            if (file_exists($sanciones_file)) {
                require_once $sanciones_file;
                if (function_exists('procesarEventosYCrearSanciones')) {
                    procesarEventosYCrearSanciones($partido_id, $db);
                }
                if (function_exists('cumplirSancionesAutomaticas')) {
                    cumplirSancionesAutomaticas($partido_id, $db);
                }
            }
            
            $db->commit();
            
            // Verificar si todos los partidos están finalizados para generar eliminatorias
            if (todosPartidosGruposFinalizados($formato_id, $db)) {
                // Intentar generar eliminatorias automáticamente
                try {
                    generarFixtureEliminatorias($formato_id, $db);
                    $_SESSION['message'] = 'Resultado guardado. ¡Fase de grupos completada! Se generaron automáticamente los partidos eliminatorios.';
                } catch (Exception $e) {
                    $_SESSION['message'] = 'Resultado guardado. ' . $e->getMessage();
                }
            } else {
                $_SESSION['message'] = 'Resultado guardado correctamente';
            }
            
            header("Location: control_partidos_zonas.php?formato_id={$formato_id}");
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
    
    if ($action === 'editar_resultado') {
        // Similar a guardar, pero permite editar
        // (implementación similar)
    }
}

// Obtener partidos por zona (desde tabla partidos)
$partidos_por_zona = [];
foreach ($zonas as $zona) {
    $stmt = $db->prepare("
        SELECT 
            p.*,
            el.nombre as equipo_local,
            el.logo as logo_local,
            ev.nombre as equipo_visitante,
            ev.logo as logo_visitante,
            c.nombre as cancha,
            f.numero_fecha
        FROM partidos p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        LEFT JOIN fechas f ON p.fecha_id = f.id
        WHERE p.zona_id = ? AND p.tipo_torneo = 'zona'
        ORDER BY p.jornada_zona, p.fecha_partido, p.hora_partido
    ");
    $stmt->execute([$zona['id']]);
    $partidos_por_zona[$zona['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener tablas de posiciones
$tablas_posiciones = [];
foreach ($zonas as $zona) {
    $tablas_posiciones[$zona['id']] = obtenerTablaPosicionesZona($zona['id'], $db);
}

// Verificar si se pueden generar eliminatorias
$puede_generar_eliminatorias = todosPartidosGruposFinalizados($formato_id, $db);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Partidos - Zonas</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .tabla-posiciones {
            font-size: 0.9rem;
        }
        .tabla-posiciones th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .posicion-1 { background: #d4edda !important; }
        .posicion-2 { background: #fff3cd !important; }
        .posicion-3 { background: #f8d7da !important; }
        .partido-card {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s;
        }
        .partido-card:hover {
            border-left-color: #0d6efd;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .partido-finalizado {
            border-left-color: #28a745;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../include/sidebar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-calendar-alt"></i> Control de Partidos - Fase de Grupos</h2>
                <p class="text-muted">
                    <?= htmlspecialchars($torneo['campeonato_nombre']) ?> - 
                    <?= htmlspecialchars($torneo['categoria_nombre']) ?>
                </p>
            </div>
            <div class="col-auto">
                <a href="torneos_zonas.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <?php if ($puede_generar_eliminatorias): ?>
                    <a href="generar_eliminatorias.php?formato_id=<?= $formato_id ?>" class="btn btn-success">
                        <i class="fas fa-trophy"></i> Generar Eliminatorias
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Tabs para cada zona -->
        <ul class="nav nav-tabs mb-4" id="zonasTab" role="tablist">
            <?php foreach ($zonas as $index => $zona): ?>
                <li class="nav-item" role="presentation">
                    <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" 
                            id="zona-<?= $zona['id'] ?>-tab" 
                            data-bs-toggle="tab" 
                            data-bs-target="#zona-<?= $zona['id'] ?>" 
                            type="button">
                        <?= htmlspecialchars($zona['nombre']) ?>
                    </button>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="tab-content" id="zonasTabContent">
            <?php foreach ($zonas as $index => $zona): ?>
                <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" 
                     id="zona-<?= $zona['id'] ?>" 
                     role="tabpanel">
                    
                    <div class="row">
                        <!-- Tabla de Posiciones -->
                        <div class="col-md-4 mb-4">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-table"></i> Tabla de Posiciones</h5>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-sm tabla-posiciones mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Pos</th>
                                                    <th>Equipo</th>
                                                    <th class="text-center">Pts</th>
                                                    <th class="text-center">PJ</th>
                                                    <th class="text-center">PG</th>
                                                    <th class="text-center">PE</th>
                                                    <th class="text-center">PP</th>
                                                    <th class="text-center">GF</th>
                                                    <th class="text-center">GC</th>
                                                    <th class="text-center">Dif</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($tablas_posiciones[$zona['id']] as $equipo): ?>
                                                    <tr class="posicion-<?= $equipo['posicion'] <= 3 ? $equipo['posicion'] : '' ?>">
                                                        <td><strong><?= $equipo['posicion'] ?></strong></td>
                                                        <td>
                                                            <?php if ($equipo['logo']): ?>
                                                                <img src="../uploads/<?= htmlspecialchars($equipo['logo']) ?>" 
                                                                     width="20" class="me-1">
                                                            <?php endif; ?>
                                                            <?= htmlspecialchars($equipo['equipo']) ?>
                                                        </td>
                                                        <td class="text-center"><strong><?= $equipo['puntos'] ?></strong></td>
                                                        <td class="text-center"><?= $equipo['partidos_jugados'] ?></td>
                                                        <td class="text-center"><?= $equipo['partidos_ganados'] ?></td>
                                                        <td class="text-center"><?= $equipo['partidos_empatados'] ?></td>
                                                        <td class="text-center"><?= $equipo['partidos_perdidos'] ?></td>
                                                        <td class="text-center"><?= $equipo['goles_favor'] ?></td>
                                                        <td class="text-center"><?= $equipo['goles_contra'] ?></td>
                                                        <td class="text-center"><?= $equipo['diferencia_gol'] ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Partidos -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-futbol"></i> Partidos</h5>
                                </div>
                                <div class="card-body">
                                    <?php 
                                    $partidos = $partidos_por_zona[$zona['id']];
                                    $partidos_por_fecha = [];
                                    foreach ($partidos as $partido) {
                                        $fecha = $partido['jornada_zona'] ?? $partido['numero_fecha'] ?? 1;
                                        if (!isset($partidos_por_fecha[$fecha])) {
                                            $partidos_por_fecha[$fecha] = [];
                                        }
                                        $partidos_por_fecha[$fecha][] = $partido;
                                    }
                                    ?>
                                    
                                    <?php foreach ($partidos_por_fecha as $fecha_num => $partidos_fecha): ?>
                                        <h6 class="mt-3 mb-2">Jornada <?= $fecha_num ?></h6>
                                        
                                        <?php foreach ($partidos_fecha as $partido): ?>
                                            <div class="card mb-2 partido-card <?= $partido['estado'] === 'finalizado' ? 'partido-finalizado' : '' ?>">
                                                <div class="card-body">
                                                    <div class="row align-items-center">
                                                        <div class="col-md-5">
                                                            <div class="d-flex align-items-center">
                                                                <?php if ($partido['logo_local']): ?>
                                                                    <img src="../uploads/<?= htmlspecialchars($partido['logo_local']) ?>" 
                                                                         width="30" class="me-2">
                                                                <?php endif; ?>
                                                                <strong><?= htmlspecialchars($partido['equipo_local']) ?></strong>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-2 text-center">
                                                            <?php if ($partido['estado'] === 'finalizado'): ?>
                                                                <h4 class="mb-0">
                                                                    <span class="badge bg-success"><?= $partido['goles_local'] ?></span>
                                                                    <span class="mx-1">-</span>
                                                                    <span class="badge bg-danger"><?= $partido['goles_visitante'] ?></span>
                                                                </h4>
                                                            <?php else: ?>
                                                                <span class="text-muted">vs</span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <div class="d-flex align-items-center justify-content-end">
                                                                <strong><?= htmlspecialchars($partido['equipo_visitante']) ?></strong>
                                                                <?php if ($partido['logo_visitante']): ?>
                                                                    <img src="../uploads/<?= htmlspecialchars($partido['logo_visitante']) ?>" 
                                                                         width="30" class="ms-2">
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="row mt-2">
                                                        <div class="col-12 text-end">
                                                            <?php if ($partido['estado'] === 'finalizado'): ?>
                                                                <button class="btn btn-sm btn-outline-primary" 
                                                                        onclick="editarResultado(<?= $partido['id'] ?>, <?= $partido['goles_local'] ?>, <?= $partido['goles_visitante'] ?>)">
                                                                    <i class="fas fa-edit"></i> Editar
                                                                </button>
                                                            <?php else: ?>
                                                                <button class="btn btn-sm btn-success" 
                                                                        onclick="cargarResultado(<?= $partido['id'] ?>)">
                                                                    <i class="fas fa-check"></i> Cargar Resultado
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal para cargar/editar resultado -->
    <div class="modal fade" id="modalResultado" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cargar Resultado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formResultado">
                    <input type="hidden" name="action" value="guardar_resultado">
                    <input type="hidden" name="partido_id" id="partido_id">
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-5">
                                <label class="form-label">Equipo Local</label>
                                <input type="text" class="form-control" id="equipo_local_nombre" readonly>
                            </div>
                            <div class="col-2 text-center">
                                <label class="form-label">Goles</label>
                                <input type="number" class="form-control text-center" name="goles_local" id="goles_local" min="0" required>
                            </div>
                            <div class="col-5">
                                <label class="form-label">Equipo Visitante</label>
                                <input type="text" class="form-control" id="equipo_visitante_nombre" readonly>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-2 text-center">
                                <label class="form-label">&nbsp;</label>
                                <span class="d-block">-</span>
                            </div>
                            <div class="col-2 text-center">
                                <label class="form-label">&nbsp;</label>
                                <input type="number" class="form-control text-center" name="goles_visitante" id="goles_visitante" min="0" required>
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Observaciones</label>
                            <textarea class="form-control" name="observaciones" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar Resultado</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function cargarPartido(partidoId, golesLocal = '', golesVisitante = '') {
            // Obtener información del partido via AJAX
            fetch(`../ajax/get_partido_zona.php?partido_id=${partidoId}`)
                .then(r => {
                    if (!r.ok) {
                        throw new Error('Error al cargar partido');
                    }
                    return r.json();
                })
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    document.getElementById('partido_id').value = partidoId;
                    document.getElementById('equipo_local_nombre').value = data.equipo_local || 'Equipo Local';
                    document.getElementById('equipo_visitante_nombre').value = data.equipo_visitante || 'Equipo Visitante';
                    document.getElementById('goles_local').value = golesLocal;
                    document.getElementById('goles_visitante').value = golesVisitante;
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalResultado'));
                    modal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cargar el partido: ' + error.message);
                });
        }
        
        function cargarResultado(partidoId) {
            cargarPartido(partidoId);
        }
        
        function editarResultado(partidoId, golesLocal, golesVisitante) {
            cargarPartido(partidoId, golesLocal, golesVisitante);
        }
    </script>
</body>
</html>

