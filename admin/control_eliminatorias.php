<?php
/**
 * Control de partidos eliminatorios
 * Permite cargar resultados y avanzar fases
 */

require_once '../config.php';
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
        cat.nombre as categoria_nombre
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

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'guardar_resultado') {
        try {
            $db->beginTransaction();
            
            $partido_id = (int)$_POST['partido_id'];
            $goles_local = (int)$_POST['goles_local'];
            $goles_visitante = (int)$_POST['goles_visitante'];
            $goles_local_penales = !empty($_POST['goles_local_penales']) ? (int)$_POST['goles_local_penales'] : null;
            $goles_visitante_penales = !empty($_POST['goles_visitante_penales']) ? (int)$_POST['goles_visitante_penales'] : null;
            $observaciones = trim($_POST['observaciones'] ?? '');
            
            // Actualizar resultado (usando tabla partidos)
            $stmt = $db->prepare("
                UPDATE partidos 
                SET goles_local = ?, goles_visitante = ?, 
                    goles_local_penales = ?, goles_visitante_penales = ?,
                    observaciones = ?, estado = 'finalizado', finalizado_at = NOW()
                WHERE id = ? AND tipo_torneo = 'eliminatoria'
            ");
            $stmt->execute([
                $goles_local, 
                $goles_visitante, 
                $goles_local_penales, 
                $goles_visitante_penales,
                $observaciones, 
                $partido_id
            ]);
            
            $db->commit();
            
            // Verificar si todos los partidos de esta fase están finalizados
            $stmt = $db->prepare("
                SELECT fase_eliminatoria_id, COUNT(*) as total, 
                       SUM(CASE WHEN estado = 'finalizado' THEN 1 ELSE 0 END) as finalizados
                FROM partidos
                WHERE id = ? AND tipo_torneo = 'eliminatoria'
            ");
            $stmt->execute([$partido_id]);
            $partido_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtener fase_eliminatoria_id del partido
            $stmt = $db->prepare("SELECT fase_eliminatoria_id FROM partidos WHERE id = ?");
            $stmt->execute([$partido_id]);
            $fase_id = $stmt->fetchColumn();
            
            // Verificar si todos los partidos de esta fase están finalizados
            $stmt = $db->prepare("
                SELECT COUNT(*) as total, 
                       SUM(CASE WHEN estado = 'finalizado' THEN 1 ELSE 0 END) as finalizados
                FROM partidos
                WHERE fase_eliminatoria_id = ? AND tipo_torneo = 'eliminatoria'
            ");
            $stmt->execute([$fase_id]);
            $fase_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($fase_info && $fase_info['total'] == $fase_info['finalizados']) {
                // Todos los partidos de esta fase están finalizados
                // Intentar avanzar a la siguiente fase
                try {
                    avanzarSiguienteFase($fase_id, $db);
                    $_SESSION['message'] = 'Resultado guardado. ¡Fase completada! Se generaron automáticamente los partidos de la siguiente fase.';
                } catch (Exception $e) {
                    $_SESSION['message'] = 'Resultado guardado. ' . $e->getMessage();
                }
            } else {
                $_SESSION['message'] = 'Resultado guardado correctamente';
            }
            
            header("Location: control_eliminatorias.php?formato_id={$formato_id}");
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}

// Obtener fases eliminatorias
$stmt = $db->prepare("
    SELECT * FROM fases_eliminatorias 
    WHERE formato_id = ? 
    ORDER BY orden
");
$stmt->execute([$formato_id]);
$fases = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener partidos por fase (desde tabla partidos)
$partidos_por_fase = [];
foreach ($fases as $fase) {
    $stmt = $db->prepare("
        SELECT 
            p.*,
            el.nombre as equipo_local,
            el.logo as logo_local,
            ev.nombre as equipo_visitante,
            ev.logo as logo_visitante,
            c.nombre as cancha
        FROM partidos p
        LEFT JOIN equipos el ON p.equipo_local_id = el.id
        LEFT JOIN equipos ev ON p.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        WHERE p.fase_eliminatoria_id = ? AND p.tipo_torneo = 'eliminatoria'
        ORDER BY p.numero_llave
    ");
    $stmt->execute([$fase['id']]);
    $partidos_por_fase[$fase['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Eliminatorias</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .fase-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .fase-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
        }
        .partido-card {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }
        .partido-card:hover {
            border-left-color: #0d6efd;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .partido-finalizado {
            border-left-color: #28a745;
        }
        .fase-inactiva {
            opacity: 0.6;
        }
    </style>
</head>
<body>
    <?php include 'include/sidebar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-trophy"></i> Fases Eliminatorias</h2>
                <p class="text-muted">
                    <?= htmlspecialchars($torneo['campeonato_nombre']) ?> - 
                    <?= htmlspecialchars($torneo['categoria_nombre']) ?>
                </p>
            </div>
            <div class="col-auto">
                <a href="torneos_zonas.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <a href="../public/bracket_zonas.php?formato_id=<?= $formato_id ?>" class="btn btn-primary" target="_blank">
                    <i class="fas fa-eye"></i> Ver Bracket
                </a>
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

        <?php foreach ($fases as $fase): ?>
            <div class="fase-card <?= !$fase['activa'] ? 'fase-inactiva' : '' ?>">
                <div class="fase-header">
                    <h4 class="mb-0">
                        <i class="fas fa-<?= $fase['nombre'] === 'final' ? 'trophy' : 'medal' ?>"></i> 
                        <?= ucfirst(str_replace('_', ' ', $fase['nombre'])) ?>
                        <?php if (!$fase['activa']): ?>
                            <span class="badge bg-secondary ms-2">Pendiente</span>
                        <?php endif; ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php 
                    $partidos = $partidos_por_fase[$fase['id']] ?? [];
                    if (empty($partidos)): 
                    ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            Los partidos de esta fase se generarán automáticamente cuando se completen los partidos de la fase anterior.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($partidos as $partido): ?>
                                <div class="col-md-6">
                                    <div class="card partido-card <?= $partido['estado'] === 'finalizado' ? 'partido-finalizado' : '' ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-dark">Llave <?= $partido['numero_llave'] ?></span>
                                                <?php if ($partido['origen_local']): ?>
                                                    <small class="text-muted"><?= htmlspecialchars($partido['origen_local']) ?></small>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="row align-items-center mb-2">
                                                <div class="col-5">
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($partido['logo_local']): ?>
                                                            <img src="../uploads/<?= htmlspecialchars($partido['logo_local']) ?>" 
                                                                 width="30" class="me-2">
                                                        <?php endif; ?>
                                                        <strong><?= htmlspecialchars($partido['equipo_local'] ?? 'Por definir') ?></strong>
                                                    </div>
                                                </div>
                                                <div class="col-2 text-center">
                                                    <?php if ($partido['estado'] === 'finalizado'): ?>
                                                        <h4 class="mb-0">
                                                            <span class="badge bg-success"><?= $partido['goles_local'] ?></span>
                                                            <span class="mx-1">-</span>
                                                            <span class="badge bg-danger"><?= $partido['goles_visitante'] ?></span>
                                                        </h4>
                                                        <?php if ($partido['goles_local_penales'] !== null): ?>
                                                            <small class="text-muted">
                                                                (<?= $partido['goles_local_penales'] ?> - <?= $partido['goles_visitante_penales'] ?> pen.)
                                                            </small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">vs</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-5">
                                                    <div class="d-flex align-items-center justify-content-end">
                                                        <strong><?= htmlspecialchars($partido['equipo_visitante'] ?? 'Por definir') ?></strong>
                                                        <?php if ($partido['logo_visitante']): ?>
                                                            <img src="../uploads/<?= htmlspecialchars($partido['logo_visitante']) ?>" 
                                                                 width="30" class="ms-2">
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <?php if ($partido['origen_visitante']): ?>
                                                <small class="text-muted d-block text-end"><?= htmlspecialchars($partido['origen_visitante']) ?></small>
                                            <?php endif; ?>
                                            
                                            <div class="text-end mt-2">
                                                <?php if ($partido['estado'] === 'finalizado'): ?>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editarResultado(<?= $partido['id'] ?>, <?= $partido['goles_local'] ?>, <?= $partido['goles_visitante'] ?>, <?= $partido['goles_local_penales'] ?? 'null' ?>, <?= $partido['goles_visitante_penales'] ?? 'null' ?>)">
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
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
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
                        
                        <div class="row mt-3" id="penales_section" style="display: none;">
                            <div class="col-12">
                                <label class="form-label">Penales (opcional, solo si empató)</label>
                                <div class="row">
                                    <div class="col-5">
                                        <input type="number" class="form-control" name="goles_local_penales" id="goles_local_penales" min="0" placeholder="Local">
                                    </div>
                                    <div class="col-2 text-center">
                                        <span>-</span>
                                    </div>
                                    <div class="col-5">
                                        <input type="number" class="form-control" name="goles_visitante_penales" id="goles_visitante_penales" min="0" placeholder="Visitante">
                                    </div>
                                </div>
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
        function cargarResultado(partidoId) {
            fetch(`ajax/get_partido_eliminatorio.php?partido_id=${partidoId}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('partido_id').value = partidoId;
                    document.getElementById('equipo_local_nombre').value = data.equipo_local || 'Por definir';
                    document.getElementById('equipo_visitante_nombre').value = data.equipo_visitante || 'Por definir';
                    document.getElementById('goles_local').value = '';
                    document.getElementById('goles_visitante').value = '';
                    document.getElementById('goles_local_penales').value = '';
                    document.getElementById('goles_visitante_penales').value = '';
                    document.getElementById('penales_section').style.display = 'none';
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalResultado'));
                    modal.show();
                });
        }
        
        function editarResultado(partidoId, golesLocal, golesVisitante, golesLocalPenales, golesVisitantePenales) {
            fetch(`ajax/get_partido_eliminatorio.php?partido_id=${partidoId}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('partido_id').value = partidoId;
                    document.getElementById('equipo_local_nombre').value = data.equipo_local || 'Por definir';
                    document.getElementById('equipo_visitante_nombre').value = data.equipo_visitante || 'Por definir';
                    document.getElementById('goles_local').value = golesLocal;
                    document.getElementById('goles_visitante').value = golesVisitante;
                    document.getElementById('goles_local_penales').value = golesLocalPenales || '';
                    document.getElementById('goles_visitante_penales').value = golesVisitantePenales || '';
                    
                    if (golesLocalPenales !== null) {
                        document.getElementById('penales_section').style.display = 'block';
                    }
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalResultado'));
                    modal.show();
                });
        }
        
        // Mostrar sección de penales si empatan
        document.getElementById('goles_local').addEventListener('input', function() {
            mostrarPenales();
        });
        document.getElementById('goles_visitante').addEventListener('input', function() {
            mostrarPenales();
        });
        
        function mostrarPenales() {
            const golesLocal = parseInt(document.getElementById('goles_local').value) || 0;
            const golesVisitante = parseInt(document.getElementById('goles_visitante').value) || 0;
            
            if (golesLocal === golesVisitante && golesLocal > 0) {
                document.getElementById('penales_section').style.display = 'block';
            } else {
                document.getElementById('penales_section').style.display = 'none';
            }
        }
    </script>
</body>
</html>

