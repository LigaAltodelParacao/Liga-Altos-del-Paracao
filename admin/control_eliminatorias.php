<?php
/**
 * Control de partidos eliminatorios
 * Permite cargar resultados y avanzar fases
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/funciones_torneos_zonas.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

$formato_id = $_GET['formato_id'] ?? null;
$partido_id_redirect = $_GET['partido_id'] ?? null;
$empate_redirect = isset($_GET['empate']) && $_GET['empate'] == '1';

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
    
    if ($action === 'editar_fase') {
        try {
            $db->beginTransaction();
            
            $fase_id = (int)$_POST['fase_id'];
            $partidos_data = json_decode($_POST['partidos_data'], true);
            
            if (!$partidos_data || !is_array($partidos_data)) {
                throw new Exception("Datos de partidos inválidos");
            }
            
            // Obtener información de la fase
            $stmt = $db->prepare("SELECT * FROM fases_eliminatorias WHERE id = ?");
            $stmt->execute([$fase_id]);
            $fase = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fase) {
                throw new Exception("Fase no encontrada");
            }
            
            // Obtener fecha para esta fase
            $stmt = $db->prepare("SELECT id FROM fechas WHERE fase_eliminatoria_id = ? LIMIT 1");
            $stmt->execute([$fase_id]);
            $fecha = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$fecha) {
                // Crear fecha si no existe
                $stmt = $db->prepare("SELECT categoria_id FROM campeonatos_formato WHERE id = ?");
                $stmt->execute([$formato_id]);
                $formato = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($formato && $formato['categoria_id']) {
                    $stmt = $db->prepare("
                        INSERT INTO fechas (categoria_id, numero_fecha, tipo_fecha, fase_eliminatoria_id, fecha_programada)
                        VALUES (?, 1, 'eliminatoria', ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY))
                    ");
                    $stmt->execute([$formato['categoria_id'], $fase_id]);
                    $fecha_id = $db->lastInsertId();
                } else {
                    throw new Exception("No se pudo obtener categoría para crear fecha");
                }
            } else {
                $fecha_id = $fecha['id'];
            }
            
            // Obtener partidos existentes de esta fase
            $stmt = $db->prepare("SELECT id, numero_llave FROM partidos WHERE fase_eliminatoria_id = ? AND tipo_torneo = 'eliminatoria'");
            $stmt->execute([$fase_id]);
            $partidos_existentes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $partidos_existentes_ids = array_column($partidos_existentes, 'id');
            $partidos_existentes_llaves = array_column($partidos_existentes, 'numero_llave', 'id');
            
            // IDs de partidos que se mantienen
            $partidos_mantener = [];
            
            // Actualizar o crear partidos
            foreach ($partidos_data as $partido_data) {
                $equipo_local_id = !empty($partido_data['equipo_local_id']) ? (int)$partido_data['equipo_local_id'] : null;
                $equipo_visitante_id = !empty($partido_data['equipo_visitante_id']) ? (int)$partido_data['equipo_visitante_id'] : null;
                $numero_llave = (int)$partido_data['numero_llave'];
                $origen_local = $partido_data['origen_local'] ?? null;
                $origen_visitante = $partido_data['origen_visitante'] ?? null;
                $partido_id_existente = !empty($partido_data['id']) ? (int)$partido_data['id'] : null;
                
                if (!$equipo_local_id || !$equipo_visitante_id) {
                    continue; // Saltar si no tiene ambos equipos
                }
                
                // Buscar si existe un partido con esta llave
                $partido_existente = null;
                if ($partido_id_existente && in_array($partido_id_existente, $partidos_existentes_ids)) {
                    $partido_existente = $partido_id_existente;
                } else {
                    // Buscar por número de llave
                    foreach ($partidos_existentes as $pe) {
                        if ($pe['numero_llave'] == $numero_llave) {
                            $partido_existente = $pe['id'];
                            break;
                        }
                    }
                }
                
                if ($partido_existente) {
                    // Actualizar partido existente (solo si no está finalizado)
                    $stmt = $db->prepare("
                        SELECT estado FROM partidos WHERE id = ?
                    ");
                    $stmt->execute([$partido_existente]);
                    $estado = $stmt->fetchColumn();
                    
                    // Solo actualizar si no está finalizado o si el usuario quiere forzar
                    if ($estado !== 'finalizado') {
                        $stmt = $db->prepare("
                            UPDATE partidos 
                            SET equipo_local_id = ?, equipo_visitante_id = ?, 
                                numero_llave = ?, origen_local = ?, origen_visitante = ?
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $equipo_local_id, $equipo_visitante_id,
                            $numero_llave, $origen_local, $origen_visitante,
                            $partido_existente
                        ]);
                    }
                    $partidos_mantener[] = $partido_existente;
                } else {
                    // Crear nuevo partido
                    $stmt = $db->prepare("
                        INSERT INTO partidos (
                            fecha_id, equipo_local_id, equipo_visitante_id, 
                            fase_eliminatoria_id, numero_llave, origen_local, origen_visitante,
                            fecha_partido, estado, tipo_torneo
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), 'programado', 'eliminatoria')
                    ");
                    $stmt->execute([
                        $fecha_id, $equipo_local_id, $equipo_visitante_id,
                        $fase_id, $numero_llave, $origen_local, $origen_visitante
                    ]);
                    $partidos_mantener[] = $db->lastInsertId();
                }
            }
            
            // Eliminar partidos que no están en la lista (solo si no están finalizados)
            if (!empty($partidos_existentes_ids)) {
                $partidos_eliminar = array_diff($partidos_existentes_ids, $partidos_mantener);
                if (!empty($partidos_eliminar)) {
                    $placeholders = implode(',', array_fill(0, count($partidos_eliminar), '?'));
                    $stmt = $db->prepare("
                        DELETE FROM partidos 
                        WHERE id IN ($placeholders) 
                        AND estado != 'finalizado'
                        AND tipo_torneo = 'eliminatoria'
                    ");
                    $stmt->execute($partidos_eliminar);
                }
            }
            
            $db->commit();
            $_SESSION['message'] = 'Fase editada correctamente';
            header("Location: control_eliminatorias.php?formato_id={$formato_id}");
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Error: ' . $e->getMessage();
        }
    }
    
    if ($action === 'guardar_resultado') {
        try {
            $db->beginTransaction();
            
            $partido_id = (int)$_POST['partido_id'];
            $goles_local = (int)$_POST['goles_local'];
            $goles_visitante = (int)$_POST['goles_visitante'];
            $goles_local_penales = !empty($_POST['goles_local_penales']) ? (int)$_POST['goles_local_penales'] : null;
            $goles_visitante_penales = !empty($_POST['goles_visitante_penales']) ? (int)$_POST['goles_visitante_penales'] : null;
            $observaciones = trim($_POST['observaciones'] ?? '');
            
            // Validar que si hay empate, debe haber penales
            if ($goles_local == $goles_visitante && ($goles_local_penales === null || $goles_visitante_penales === null)) {
                throw new Exception("En caso de empate en fase eliminatoria, es obligatorio cargar el resultado de los penales");
            }
            
            // Validar que los penales no sean iguales (debe haber un ganador)
            if ($goles_local_penales !== null && $goles_visitante_penales !== null) {
                if ($goles_local_penales == $goles_visitante_penales) {
                    throw new Exception("Los penales no pueden terminar en empate. Debe haber un ganador.");
                }
            }
            
            // Obtener IDs de equipos del partido
            $stmt = $db->prepare("SELECT equipo_local_id, equipo_visitante_id FROM partidos WHERE id = ?");
            $stmt->execute([$partido_id]);
            $partido_info = $stmt->fetch(PDO::FETCH_ASSOC);
            $equipo_local_id = $partido_info['equipo_local_id'];
            $equipo_visitante_id = $partido_info['equipo_visitante_id'];
            
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
            
            // Guardar penales por jugador si se enviaron
            if (!empty($_POST['penales_data'])) {
                $penales_data = json_decode($_POST['penales_data'], true);
                
                // Eliminar penales anteriores de este partido
                $stmt = $db->prepare("DELETE FROM penales_partido WHERE partido_id = ?");
                $stmt->execute([$partido_id]);
                
                // Guardar penales del equipo local
                if (!empty($penales_data['local']) && is_array($penales_data['local'])) {
                    $stmt = $db->prepare("
                        INSERT INTO penales_partido (partido_id, jugador_id, equipo_id, numero_penal, convertido)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    foreach ($penales_data['local'] as $penal) {
                        if (!empty($penal['jugador_id']) && isset($penal['convertido'])) {
                            $stmt->execute([
                                $partido_id,
                                (int)$penal['jugador_id'],
                                $equipo_local_id,
                                (int)$penal['numero_penal'],
                                $penal['convertido'] ? 1 : 0
                            ]);
                        }
                    }
                }
                
                // Guardar penales del equipo visitante
                if (!empty($penales_data['visitante']) && is_array($penales_data['visitante'])) {
                    $stmt = $db->prepare("
                        INSERT INTO penales_partido (partido_id, jugador_id, equipo_id, numero_penal, convertido)
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    foreach ($penales_data['visitante'] as $penal) {
                        if (!empty($penal['jugador_id']) && isset($penal['convertido'])) {
                            $stmt->execute([
                                $partido_id,
                                (int)$penal['jugador_id'],
                                $equipo_visitante_id,
                                (int)$penal['numero_penal'],
                                $penal['convertido'] ? 1 : 0
                            ]);
                        }
                    }
                }
            }
            
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

// Obtener todos los equipos disponibles para el selector
$stmt = $db->prepare("
    SELECT DISTINCT e.id, e.nombre, e.logo
    FROM equipos e
    INNER JOIN equipos_zonas ez ON e.id = ez.equipo_id
    INNER JOIN zonas z ON ez.zona_id = z.id
    INNER JOIN campeonatos_formato cf ON z.formato_id = cf.id
    WHERE cf.id = ?
    ORDER BY e.nombre
");
$stmt->execute([$formato_id]);
$equipos_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
    <?php include __DIR__ . '/include/sidebar.php'; ?>
    
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
                    <div class="d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-<?= $fase['nombre'] === 'final' ? 'trophy' : 'medal' ?>"></i> 
                        <?= ucfirst(str_replace('_', ' ', $fase['nombre'])) ?>
                        <?php if (!$fase['activa']): ?>
                            <span class="badge bg-secondary ms-2">Pendiente</span>
                        <?php endif; ?>
                    </h4>
                        <button class="btn btn-sm btn-light" onclick="editarFase(<?= $fase['id'] ?>, '<?= htmlspecialchars($fase['nombre']) ?>')" title="Editar fase">
                            <i class="fas fa-edit"></i> Editar Fase
                        </button>
                    </div>
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

    <!-- Modal para editar fase -->
    <div class="modal fade" id="modalEditarFase" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Fase Eliminatoria</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formEditarFase">
                    <input type="hidden" name="action" value="editar_fase">
                    <input type="hidden" name="fase_id" id="fase_id_editar">
                    <input type="hidden" name="partidos_data" id="partidos_data_json">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Puede agregar, quitar o modificar equipos y partidos de esta fase.
                        </div>
                        <div id="partidos_editar_container">
                            <!-- Se generarán dinámicamente -->
                        </div>
                        <div class="text-end mt-3">
                            <button type="button" class="btn btn-sm btn-primary" onclick="agregarPartido()">
                                <i class="fas fa-plus"></i> Agregar Partido
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    </div>
                </form>
            </div>
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
                        
                        <div class="row mt-3" id="penales_section" style="display: none;">
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label mb-0"><strong>Penales (obligatorio si empató)</strong></label>
                                    <div class="btn-group btn-group-sm" role="group">
                                        <button type="button" class="btn btn-outline-primary" onclick="cargarPenalesJugadores()">
                                            <i class="fas fa-users"></i> Por jugadores
                                        </button>
                                        <button type="button" class="btn btn-outline-success" onclick="cargarPenalesManual()">
                                            <i class="fas fa-edit"></i> Resultado manual
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Modo manual (resultado directo) -->
                                <div id="penales_manual" style="display: none;">
                                    <div class="row mb-2">
                                        <div class="col-5">
                                            <label class="form-label small">Penales Local</label>
                                            <input type="number" class="form-control" name="goles_local_penales" id="goles_local_penales" min="0" placeholder="0">
                                        </div>
                                        <div class="col-2 text-center">
                                            <label class="form-label small">&nbsp;</label>
                                            <span class="d-block">-</span>
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label small">Penales Visitante</label>
                                            <input type="number" class="form-control" name="goles_visitante_penales" id="goles_visitante_penales" min="0" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Modo por jugadores -->
                                <div id="penales_jugadores">
                                    <div class="alert alert-info small mb-3">
                                        <i class="fas fa-info-circle"></i> Seleccione el jugador y el número de penal, luego marque si convirtió (●) o erró (✗).
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-header bg-primary text-white">
                                                    <strong id="equipo_local_header">Equipo Local</strong>
                                                    <span class="badge bg-light text-dark ms-2">Total: <span id="total_penales_local">0</span></span>
                                                </div>
                                                <div class="card-body">
                                                    <div id="penales_local_jugadores">
                                                        <!-- Se generarán dinámicamente -->
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="agregarPenalJugador('local')">
                                                        <i class="fas fa-plus"></i> Agregar penal
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card">
                                                <div class="card-header bg-danger text-white">
                                                    <strong id="equipo_visitante_header">Equipo Visitante</strong>
                                                    <span class="badge bg-light text-dark ms-2">Total: <span id="total_penales_visitante">0</span></span>
                                                </div>
                                                <div class="card-body">
                                                    <div id="penales_visitante_jugadores">
                                                        <!-- Se generarán dinámicamente -->
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" onclick="agregarPenalJugador('visitante')">
                                                        <i class="fas fa-plus"></i> Agregar penal
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Campos ocultos para enviar los datos -->
                                    <input type="hidden" name="goles_local_penales" id="goles_local_penales_hidden" value="0">
                                    <input type="hidden" name="goles_visitante_penales" id="goles_visitante_penales_hidden" value="0">
                                    <input type="hidden" name="penales_data" id="penales_data_json" value="">
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
        // Verificar si se debe abrir el modal de penales automáticamente
        <?php if ($partido_id_redirect && $empate_redirect): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener información del partido
            fetch(`ajax/get_partido_eliminatorio.php?partido_id=<?= $partido_id_redirect ?>`)
                .then(r => r.json())
                .then(data => {
                    if (data && data.goles_local !== null && data.goles_visitante !== null) {
                        // Cargar el resultado con los goles ya ingresados
                        document.getElementById('partido_id').value = <?= $partido_id_redirect ?>;
                        document.getElementById('equipo_local_nombre').value = data.equipo_local || 'Por definir';
                        document.getElementById('equipo_visitante_nombre').value = data.equipo_visitante || 'Por definir';
                        document.getElementById('goles_local').value = data.goles_local;
                        document.getElementById('goles_visitante').value = data.goles_visitante;
                        
                        equipoLocalId = data.equipo_local_id;
                        equipoVisitanteId = data.equipo_visitante_id;
                        
                        // Mostrar sección de penales automáticamente
                        document.getElementById('penales_section').style.display = 'block';
                        cargarPenalesJugadores();
                        
                        // Abrir el modal
                        const modal = new bootstrap.Modal(document.getElementById('modalResultado'));
                        modal.show();
                        
                        // Mostrar mensaje informativo
                        if (document.querySelector('.alert')) {
                            const alertDiv = document.createElement('div');
                            alertDiv.className = 'alert alert-warning alert-dismissible fade show';
                            alertDiv.innerHTML = `
                                <i class="fas fa-exclamation-triangle"></i> 
                                El partido terminó en empate. Debe cargar el resultado de los penales para finalizar el partido.
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            `;
                            document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.container-fluid').firstChild);
                        }
                    }
                })
                .catch(err => {
                    console.error('Error al cargar partido:', err);
                });
        });
        <?php endif; ?>
        
        // Datos de equipos disponibles (desde PHP)
        const equiposDisponibles = <?= json_encode($equipos_disponibles) ?>;
        let partidosEditar = [];
        let contadorPartidos = 0;
        
        function editarFase(faseId, nombreFase) {
            document.getElementById('fase_id_editar').value = faseId;
            document.querySelector('#modalEditarFase .modal-title').textContent = `Editar ${nombreFase}`;
            
            // Cargar partidos existentes de esta fase
            fetch(`ajax/get_partidos_fase.php?fase_id=${faseId}`)
                .then(r => r.json())
                .then(data => {
                    partidosEditar = data.partidos || [];
                    contadorPartidos = partidosEditar.length;
                    renderizarPartidosEditar();
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalEditarFase'));
                    modal.show();
                })
                .catch(err => {
                    console.error('Error:', err);
                    partidosEditar = [];
                    contadorPartidos = 0;
                    renderizarPartidosEditar();
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalEditarFase'));
                    modal.show();
                });
        }
        
        function renderizarPartidosEditar() {
            const container = document.getElementById('partidos_editar_container');
            container.innerHTML = '';
            
            if (partidosEditar.length === 0) {
                container.innerHTML = '<div class="alert alert-warning">No hay partidos en esta fase. Agregue partidos usando el botón de abajo.</div>';
                return;
            }
            
            partidosEditar.forEach((partido, index) => {
                const partidoDiv = document.createElement('div');
                partidoDiv.className = 'card mb-3';
                partidoDiv.innerHTML = `
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Partido ${partido.numero_llave || index + 1}</h6>
                            <button type="button" class="btn btn-sm btn-danger" onclick="eliminarPartido(${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-md-5">
                                <label class="form-label small">Equipo Local</label>
                                <select class="form-select form-select-sm" onchange="actualizarPartido(${index}, 'equipo_local_id', this.value)">
                                    <option value="">Seleccionar...</option>
                                    ${equiposDisponibles.map(eq => 
                                        `<option value="${eq.id}" ${partido.equipo_local_id == eq.id ? 'selected' : ''}>${eq.nombre}</option>`
                                    ).join('')}
                                </select>
                            </div>
                            <div class="col-md-2 text-center">
                                <label class="form-label small">&nbsp;</label>
                                <span class="d-block">vs</span>
                            </div>
                            <div class="col-md-5">
                                <label class="form-label small">Equipo Visitante</label>
                                <select class="form-select form-select-sm" onchange="actualizarPartido(${index}, 'equipo_visitante_id', this.value)">
                                    <option value="">Seleccionar...</option>
                                    ${equiposDisponibles.map(eq => 
                                        `<option value="${eq.id}" ${partido.equipo_visitante_id == eq.id ? 'selected' : ''}>${eq.nombre}</option>`
                                    ).join('')}
                                </select>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <label class="form-label small">Origen Local (opcional)</label>
                                <input type="text" class="form-control form-control-sm" 
                                       value="${partido.origen_local || ''}" 
                                       onchange="actualizarPartido(${index}, 'origen_local', this.value)"
                                       placeholder="Ej: Ganador Llave 1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small">Origen Visitante (opcional)</label>
                                <input type="text" class="form-control form-control-sm" 
                                       value="${partido.origen_visitante || ''}" 
                                       onchange="actualizarPartido(${index}, 'origen_visitante', this.value)"
                                       placeholder="Ej: Ganador Llave 2">
                            </div>
                        </div>
                        <input type="hidden" class="numero_llave" value="${partido.numero_llave || index + 1}">
                        <input type="hidden" class="partido_id_existente" value="${partido.id || ''}">
                    </div>
                `;
                container.appendChild(partidoDiv);
            });
        }
        
        function agregarPartido() {
            const nuevoPartido = {
                numero_llave: contadorPartidos + 1,
                equipo_local_id: null,
                equipo_visitante_id: null,
                origen_local: '',
                origen_visitante: ''
            };
            partidosEditar.push(nuevoPartido);
            contadorPartidos++;
            renderizarPartidosEditar();
        }
        
        function eliminarPartido(index) {
            if (confirm('¿Está seguro de eliminar este partido?')) {
                partidosEditar.splice(index, 1);
                renderizarPartidosEditar();
            }
        }
        
        function actualizarPartido(index, campo, valor) {
            if (partidosEditar[index]) {
                partidosEditar[index][campo] = valor;
            }
        }
        
        // Guardar cambios al enviar el formulario
        document.getElementById('formEditarFase').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Actualizar números de llave e IDs
            const llaves = document.querySelectorAll('.numero_llave');
            const ids = document.querySelectorAll('.partido_id_existente');
            partidosEditar.forEach((partido, index) => {
                if (llaves[index]) {
                    partido.numero_llave = parseInt(llaves[index].value) || index + 1;
                }
                if (ids[index] && ids[index].value) {
                    partido.id = parseInt(ids[index].value);
                }
            });
            
            // Validar que todos los partidos tengan equipos
            const partidosValidos = partidosEditar.filter(p => p.equipo_local_id && p.equipo_visitante_id);
            
            if (partidosValidos.length === 0) {
                alert('Debe agregar al menos un partido con ambos equipos definidos.');
                return;
            }
            
            // Convertir a JSON y enviar
            document.getElementById('partidos_data_json').value = JSON.stringify(partidosValidos);
            this.submit();
        });
        
        let modoPenales = 'jugadores'; // 'jugadores' o 'manual'
        let penalesData = { local: [], visitante: [] };
        let jugadoresLocal = [];
        let jugadoresVisitante = [];
        let equipoLocalId = null;
        let equipoVisitanteId = null;
        
        function cargarResultado(partidoId) {
            fetch(`ajax/get_partido_eliminatorio.php?partido_id=${partidoId}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('partido_id').value = partidoId;
                    document.getElementById('equipo_local_nombre').value = data.equipo_local || 'Por definir';
                    document.getElementById('equipo_visitante_nombre').value = data.equipo_visitante || 'Por definir';
                    document.getElementById('goles_local').value = '';
                    document.getElementById('goles_visitante').value = '';
                    
                    equipoLocalId = data.equipo_local_id;
                    equipoVisitanteId = data.equipo_visitante_id;
                    
                    resetearPenales();
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
                    
                    equipoLocalId = data.equipo_local_id;
                    equipoVisitanteId = data.equipo_visitante_id;
                    
                    if (golesLocalPenales !== null && golesLocalPenales !== undefined) {
                        document.getElementById('penales_section').style.display = 'block';
                        
                        // Si hay datos de penales por jugador, cargarlos
                        if (data.penales_data && (data.penales_data.local.length > 0 || data.penales_data.visitante.length > 0)) {
                            penalesData = data.penales_data;
                            cargarPenalesJugadores();
                        } else {
                            // Si no hay datos por jugador, usar modo manual
                            cargarPenalesManual();
                            document.getElementById('goles_local_penales').value = golesLocalPenales;
                            document.getElementById('goles_visitante_penales').value = golesVisitantePenales || 0;
                        }
                    } else {
                        resetearPenales();
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
                // Cargar jugadores si aún no se cargaron
                if (modoPenales === 'jugadores' && equipoLocalId && equipoVisitanteId) {
                    cargarJugadoresEquipos();
                }
            } else {
                document.getElementById('penales_section').style.display = 'none';
            }
        }
        
        function cargarJugadoresEquipos() {
            // Cargar jugadores del equipo local
            if (equipoLocalId) {
                fetch(`ajax/get_jugadores.php?equipo_id=${equipoLocalId}`)
                    .then(r => r.json())
                    .then(data => {
                        jugadoresLocal = data;
                        document.getElementById('equipo_local_header').textContent = document.getElementById('equipo_local_nombre').value;
                    });
            }
            
            // Cargar jugadores del equipo visitante
            if (equipoVisitanteId) {
                fetch(`ajax/get_jugadores.php?equipo_id=${equipoVisitanteId}`)
                    .then(r => r.json())
                    .then(data => {
                        jugadoresVisitante = data;
                        document.getElementById('equipo_visitante_header').textContent = document.getElementById('equipo_visitante_nombre').value;
                    });
            }
        }
        
        function cargarPenalesJugadores() {
            modoPenales = 'jugadores';
            document.getElementById('penales_manual').style.display = 'none';
            document.getElementById('penales_jugadores').style.display = 'block';
            
            if (equipoLocalId && equipoVisitanteId) {
                cargarJugadoresEquipos();
            }
            
            renderizarPenalesJugadores();
        }
        
        function cargarPenalesManual() {
            modoPenales = 'manual';
            document.getElementById('penales_jugadores').style.display = 'none';
            document.getElementById('penales_manual').style.display = 'block';
        }
        
        function agregarPenalJugador(equipo) {
            const index = penalesData[equipo].length;
            penalesData[equipo].push({
                jugador_id: null,
                numero_penal: index + 1,
                convertido: null
            });
            renderizarPenalesJugadores();
        }
        
        function eliminarPenalJugador(equipo, index) {
            penalesData[equipo].splice(index, 1);
            // Renumerar penales
            penalesData[equipo].forEach((p, i) => {
                p.numero_penal = i + 1;
            });
            renderizarPenalesJugadores();
        }
        
        function renderizarPenalesJugadores() {
            const containerLocal = document.getElementById('penales_local_jugadores');
            const containerVisitante = document.getElementById('penales_visitante_jugadores');
            
            containerLocal.innerHTML = '';
            containerVisitante.innerHTML = '';
            
            // Renderizar penales local
            penalesData.local.forEach((penal, index) => {
                const div = document.createElement('div');
                div.className = 'mb-2 p-2 border rounded';
                div.innerHTML = `
                    <div class="row align-items-center">
                        <div class="col-4">
                            <select class="form-select form-select-sm" onchange="actualizarPenalJugador('local', ${index}, 'jugador_id', this.value)">
                                <option value="">Jugador...</option>
                                ${jugadoresLocal.map(j => `<option value="${j.id}" ${penal.jugador_id == j.id ? 'selected' : ''}>${j.apellido_nombre}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-2">
                            <input type="number" class="form-control form-control-sm" value="${penal.numero_penal}" min="1" onchange="actualizarPenalJugador('local', ${index}, 'numero_penal', this.value)">
                        </div>
                        <div class="col-4">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn ${penal.convertido === true ? 'btn-success' : 'btn-outline-success'}" onclick="marcarPenal('local', ${index}, true)" title="Gol">
                                    <i class="fas fa-circle"></i>
                                </button>
                                <button type="button" class="btn ${penal.convertido === false ? 'btn-danger' : 'btn-outline-danger'}" onclick="marcarPenal('local', ${index}, false)" title="Errado">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarPenalJugador('local', ${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                containerLocal.appendChild(div);
            });
            
            // Renderizar penales visitante
            penalesData.visitante.forEach((penal, index) => {
                const div = document.createElement('div');
                div.className = 'mb-2 p-2 border rounded';
                div.innerHTML = `
                    <div class="row align-items-center">
                        <div class="col-4">
                            <select class="form-select form-select-sm" onchange="actualizarPenalJugador('visitante', ${index}, 'jugador_id', this.value)">
                                <option value="">Jugador...</option>
                                ${jugadoresVisitante.map(j => `<option value="${j.id}" ${penal.jugador_id == j.id ? 'selected' : ''}>${j.apellido_nombre}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-2">
                            <input type="number" class="form-control form-control-sm" value="${penal.numero_penal}" min="1" onchange="actualizarPenalJugador('visitante', ${index}, 'numero_penal', this.value)">
                        </div>
                        <div class="col-4">
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn ${penal.convertido === true ? 'btn-success' : 'btn-outline-success'}" onclick="marcarPenal('visitante', ${index}, true)" title="Gol">
                                    <i class="fas fa-circle"></i>
                                </button>
                                <button type="button" class="btn ${penal.convertido === false ? 'btn-danger' : 'btn-outline-danger'}" onclick="marcarPenal('visitante', ${index}, false)" title="Errado">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-2">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarPenalJugador('visitante', ${index})">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
                containerVisitante.appendChild(div);
            });
            
            actualizarTotalesPenalesJugadores();
        }
        
        function actualizarPenalJugador(equipo, index, campo, valor) {
            if (penalesData[equipo][index]) {
                penalesData[equipo][index][campo] = campo === 'jugador_id' || campo === 'numero_penal' ? parseInt(valor) : valor;
                renderizarPenalesJugadores();
            }
        }
        
        function marcarPenal(equipo, index, convertido) {
            if (penalesData[equipo][index]) {
                penalesData[equipo][index].convertido = convertido;
                renderizarPenalesJugadores();
            }
        }
        
        function actualizarTotalesPenalesJugadores() {
            const totalLocal = penalesData.local.filter(p => p.convertido === true).length;
            const totalVisitante = penalesData.visitante.filter(p => p.convertido === true).length;
            
            document.getElementById('total_penales_local').textContent = totalLocal;
            document.getElementById('total_penales_visitante').textContent = totalVisitante;
            
            document.getElementById('goles_local_penales_hidden').value = totalLocal;
            document.getElementById('goles_visitante_penales_hidden').value = totalVisitante;
            
            // Guardar datos de penales en JSON
            document.getElementById('penales_data_json').value = JSON.stringify(penalesData);
        }
        
        function resetearPenales() {
            penalesData = { local: [], visitante: [] };
            modoPenales = 'jugadores';
            document.getElementById('penales_manual').style.display = 'none';
            document.getElementById('penales_jugadores').style.display = 'block';
            renderizarPenalesJugadores();
        }
        
        // Ajustar el formulario antes de enviar
        document.getElementById('formResultado').addEventListener('submit', function(e) {
            const golesLocal = parseInt(document.getElementById('goles_local').value) || 0;
            const golesVisitante = parseInt(document.getElementById('goles_visitante').value) || 0;
            
            // Si hay empate, validar que se hayan cargado los penales
            if (golesLocal === golesVisitante && golesLocal > 0) {
                if (modoPenales === 'jugadores') {
                    const totalLocal = parseInt(document.getElementById('goles_local_penales_hidden').value) || 0;
                    const totalVisitante = parseInt(document.getElementById('goles_visitante_penales_hidden').value) || 0;
                    
                    // Validar que haya al menos un penal cargado
                    if (penalesData.local.length === 0 && penalesData.visitante.length === 0) {
                        e.preventDefault();
                        alert('Debe cargar al menos un penal para cada equipo.');
                        return false;
                    }
                    
                    // Validar que todos los penales tengan jugador y resultado
                    const penalesIncompletos = penalesData.local.some(p => !p.jugador_id || p.convertido === null) ||
                                             penalesData.visitante.some(p => !p.jugador_id || p.convertido === null);
                    
                    if (penalesIncompletos) {
                        e.preventDefault();
                        alert('Todos los penales deben tener un jugador seleccionado y un resultado (gol o errado).');
                        return false;
                    }
                    
                    if (totalLocal === totalVisitante) {
                        e.preventDefault();
                        alert('Los penales no pueden terminar en empate. Debe haber un ganador.');
                        return false;
                    }
                    
                    // Crear campos ocultos con los nombres correctos
                    const inputLocal = document.createElement('input');
                    inputLocal.type = 'hidden';
                    inputLocal.name = 'goles_local_penales';
                    inputLocal.value = totalLocal;
                    this.appendChild(inputLocal);
                    
                    const inputVisitante = document.createElement('input');
                    inputVisitante.type = 'hidden';
                    inputVisitante.name = 'goles_visitante_penales';
                    inputVisitante.value = totalVisitante;
                    this.appendChild(inputVisitante);
                } else {
                    // Modo manual - validar que los campos estén llenos
                    const penalesLocal = parseInt(document.getElementById('goles_local_penales').value) || 0;
                    const penalesVisitante = parseInt(document.getElementById('goles_visitante_penales').value) || 0;
                    
                    if (penalesLocal === 0 && penalesVisitante === 0) {
                        e.preventDefault();
                        alert('Debe cargar el resultado de los penales.');
                        return false;
                    }
                    
                    if (penalesLocal === penalesVisitante) {
                        e.preventDefault();
                        alert('Los penales no pueden terminar en empate. Debe haber un ganador.');
                        return false;
                    }
                }
            }
        });
    </script>
</body>
</html>


