<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Crear tabla de turnos si no existe
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS turnos_horarios (
            id INT PRIMARY KEY AUTO_INCREMENT,
            nombre VARCHAR(100) NOT NULL,
            temporada ENUM('verano', 'invierno', 'noche') NOT NULL,
            horarios TEXT NOT NULL,
            descripcion VARCHAR(255),
            activo TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Modificar tabla horarios_canchas para agregar temporada 'noche'
    $db->exec("
        ALTER TABLE horarios_canchas 
        MODIFY COLUMN temporada ENUM('verano', 'invierno', 'noche') DEFAULT 'verano'
    ");
} catch (Exception $e) {
    // Tabla ya existe o columna ya modificada
}

// ACCIONES
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'crear_turno':
            $nombre = trim($_POST['nombre']);
            $temporada = $_POST['temporada'];
            $horarios = $_POST['horarios'] ?? [];
            $descripcion = trim($_POST['descripcion']);

            if (empty($nombre) || empty($horarios)) {
                $error = 'El nombre y al menos un horario son obligatorios';
            } else {
                try {
                    $horarios_json = json_encode($horarios);
                    $stmt = $db->prepare("INSERT INTO turnos_horarios (nombre, temporada, horarios, descripcion) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nombre, $temporada, $horarios_json, $descripcion]);
                    $message = "✓ Turno '$nombre' creado exitosamente";
                } catch (Exception $e) {
                    $error = 'Error al crear turno: ' . $e->getMessage();
                }
            }
            break;

        case 'editar_turno':
            $id = $_POST['id'];
            $nombre = trim($_POST['nombre']);
            $temporada = $_POST['temporada'];
            $horarios = $_POST['horarios'] ?? [];
            $descripcion = trim($_POST['descripcion']);
            $activo = isset($_POST['activo']) ? 1 : 0;

            if (empty($nombre) || empty($horarios)) {
                $error = 'El nombre y al menos un horario son obligatorios';
            } else {
                try {
                    $horarios_json = json_encode($horarios);
                    $stmt = $db->prepare("UPDATE turnos_horarios SET nombre = ?, temporada = ?, horarios = ?, descripcion = ?, activo = ? WHERE id = ?");
                    $stmt->execute([$nombre, $temporada, $horarios_json, $descripcion, $activo, $id]);
                    $message = "✓ Turno actualizado exitosamente";
                } catch (Exception $e) {
                    $error = 'Error al actualizar turno: ' . $e->getMessage();
                }
            }
            break;

        case 'eliminar_turno':
            $id = $_POST['id'];
            try {
                $stmt = $db->prepare("DELETE FROM turnos_horarios WHERE id = ?");
                $stmt->execute([$id]);
                $message = "✓ Turno eliminado exitosamente";
            } catch (Exception $e) {
                $error = 'Error al eliminar turno: ' . $e->getMessage();
            }
            break;

        case 'asignar_turno':
            $turno_id = $_POST['turno_id'];
            $canchas_ids = $_POST['canchas_ids'] ?? [];

            if (empty($turno_id) || empty($canchas_ids)) {
                $error = 'Debe seleccionar un turno y al menos una cancha';
            } else {
                try {
                    // Obtener turno
                    $stmt = $db->prepare("SELECT * FROM turnos_horarios WHERE id = ?");
                    $stmt->execute([$turno_id]);
                    $turno = $stmt->fetch();

                    if (!$turno) {
                        throw new Exception('Turno no encontrado');
                    }

                    $horarios = json_decode($turno['horarios'], true);
                    
                    $db->beginTransaction();
                    $stmt = $db->prepare("INSERT IGNORE INTO horarios_canchas (cancha_id, hora, temporada, activa) VALUES (?, ?, ?, 1)");
                    
                    $count = 0;
                    foreach ($canchas_ids as $cancha_id) {
                        foreach ($horarios as $hora) {
                            // Verificar si ya existe
                            $check = $db->prepare("SELECT COUNT(*) FROM horarios_canchas WHERE cancha_id = ? AND hora = ? AND temporada = ?");
                            $check->execute([$cancha_id, $hora, $turno['temporada']]);
                            
                            if ($check->fetchColumn() == 0) {
                                $stmt->execute([$cancha_id, $hora, $turno['temporada']]);
                                $count++;
                            }
                        }
                    }
                    
                    $db->commit();
                    
                    if ($count > 0) {
                        $message = "✓ $count horarios asignados exitosamente para turno '{$turno['nombre']}'";
                    } else {
                        $error = 'Los horarios ya existen para las canchas seleccionadas';
                    }
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Error al asignar horarios: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Obtener turnos
$turnos = $db->query("SELECT * FROM turnos_horarios ORDER BY temporada, nombre")->fetchAll();

// Obtener canchas activas
$canchas = $db->query("SELECT id, nombre, ubicacion FROM canchas WHERE activa = 1 ORDER BY nombre")->fetchAll();

// Resumen de horarios por cancha con detalle
$stmt = $db->query("
    SELECT c.id as cancha_id, c.nombre as cancha_nombre,
           GROUP_CONCAT(CASE WHEN h.temporada = 'verano' THEN TIME_FORMAT(h.hora, '%H:%i') END ORDER BY h.hora SEPARATOR ', ') as horarios_verano,
           GROUP_CONCAT(CASE WHEN h.temporada = 'invierno' THEN TIME_FORMAT(h.hora, '%H:%i') END ORDER BY h.hora SEPARATOR ', ') as horarios_invierno,
           GROUP_CONCAT(CASE WHEN h.temporada = 'noche' THEN TIME_FORMAT(h.hora, '%H:%i') END ORDER BY h.hora SEPARATOR ', ') as horarios_noche,
           COUNT(CASE WHEN h.temporada = 'verano' THEN 1 END) as verano_count,
           COUNT(CASE WHEN h.temporada = 'invierno' THEN 1 END) as invierno_count,
           COUNT(CASE WHEN h.temporada = 'noche' THEN 1 END) as noche_count,
           COUNT(*) as total_count
    FROM canchas c
    LEFT JOIN horarios_canchas h ON c.id = h.cancha_id
    WHERE c.activa = 1
    GROUP BY c.id, c.nombre
    ORDER BY c.nombre
");
$resumen = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener resumen de turnos creados
$turnos_resumen = [];
foreach ($turnos as $turno) {
    $horarios_arr = json_decode($turno['horarios'], true);
    $temporada = $turno['temporada'];
    
    foreach ($canchas as $cancha) {
        // Contar cuántos horarios de este turno ya están en cada cancha
        $placeholders = str_repeat('?,', count($horarios_arr) - 1) . '?';
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM horarios_canchas 
            WHERE cancha_id = ? AND temporada = ? AND hora IN ($placeholders)
        ");
        $params = array_merge([$cancha['id'], $temporada], $horarios_arr);
        $stmt->execute($params);
        $count = $stmt->fetchColumn();
        
        if (!isset($turnos_resumen[$cancha['id']])) {
            $turnos_resumen[$cancha['id']] = [];
        }
        $turnos_resumen[$cancha['id']][$turno['id']] = [
            'total' => count($horarios_arr),
            'asignados' => $count
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Turnos y Horarios</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .turno-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .turno-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        .horario-tag {
            display: inline-block;
            padding: 4px 10px;
            margin: 2px;
            border-radius: 4px;
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-futbol"></i> Fútbol Manager - Admin
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
                    <div>
                        <h2><i class="fas fa-clock"></i> Gestión de Turnos y Horarios</h2>
                        <p class="text-muted mb-0">Crea turnos personalizados y asígnalos a tus canchas</p>
                    </div>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalTurno">
                        <i class="fas fa-plus"></i> Crear Turno
                    </button>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Lista de Turnos -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Turnos Configurados</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($turnos)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay turnos creados</h5>
                                <p class="text-muted">Comienza creando tu primer turno personalizado</p>
                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalTurno">
                                    <i class="fas fa-plus"></i> Crear Primer Turno
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($turnos as $turno): ?>
                                    <?php $horarios = json_decode($turno['horarios'], true); ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card turno-card h-100 <?php echo $turno['activo'] ? 'border-primary' : 'border-secondary'; ?>">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                    <h5 class="card-title mb-0">
                                                        <i class="fas fa-<?php echo $turno['temporada'] === 'verano' ? 'sun text-warning' : 'snowflake text-info'; ?>"></i>
                                                        <?php echo htmlspecialchars($turno['nombre']); ?>
                                                    </h5>
                                                    <?php if (!$turno['activo']): ?>
                                                        <span class="badge bg-secondary">Inactivo</span>
                                                    <?php endif; ?>
                                                </div>

                                                <?php if ($turno['descripcion']): ?>
                                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($turno['descripcion']); ?></p>
                                                <?php endif; ?>

                                                <div class="mb-3">
                                                    <span class="badge bg-<?php echo $turno['temporada'] === 'verano' ? 'warning text-dark' : 'info'; ?> mb-2">
                                                        Temporada: <?php echo ucfirst($turno['temporada']); ?>
                                                    </span>
                                                    <div>
                                                        <?php foreach ($horarios as $hora): ?>
                                                            <span class="horario-tag bg-light border"><?php echo $hora; ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>

                                                <div class="d-flex gap-2">
                                                    <button class="btn btn-sm btn-primary flex-fill" 
                                                            onclick="asignarTurno(<?php echo $turno['id']; ?>)"
                                                            <?php echo !$turno['activo'] ? 'disabled' : ''; ?>>
                                                        <i class="fas fa-map-marker-alt"></i> Asignar
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-secondary" 
                                                            onclick="editarTurno(<?php echo $turno['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="eliminarTurno(<?php echo $turno['id']; ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Resumen de Canchas -->
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Horarios por Cancha</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Cancha</th>
                                        <th><i class="fas fa-sun text-warning"></i> Verano</th>
                                        <th><i class="fas fa-snowflake text-info"></i> Invierno</th>
                                        <th><i class="fas fa-moon text-dark"></i> Noche</th>
                                        <th class="text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($resumen as $r): ?>
                                    <tr>
                                        <td>
                                            <i class="fas fa-map-marker-alt text-success"></i>
                                            <?php echo htmlspecialchars($r['cancha_nombre']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark"><?php echo $r['verano_count']; ?> horarios</span>
                                            <?php if ($r['horarios_verano']): ?>
                                                <br><small class="text-muted"><?php echo $r['horarios_verano']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $r['invierno_count']; ?> horarios</span>
                                            <?php if ($r['horarios_invierno']): ?>
                                                <br><small class="text-muted"><?php echo $r['horarios_invierno']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark"><?php echo $r['noche_count']; ?> horarios</span>
                                            <?php if ($r['horarios_noche']): ?>
                                                <br><small class="text-muted"><?php echo $r['horarios_noche']; ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-secondary"><?php echo $r['total_count']; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Crear/Editar Turno -->
    <div class="modal fade" id="modalTurno" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTurnoTitle">
                        <i class="fas fa-plus"></i> Crear Turno
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formTurno">
                    <input type="hidden" name="action" id="turnoAction" value="crear_turno">
                    <input type="hidden" name="id" id="turnoId">
                    
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre del Turno *</label>
                                <input type="text" class="form-control" name="nombre" id="turnoNombre" 
                                       placeholder="Ej: Tarde Verano" required>
                                <small class="text-muted">Ej: "Tarde Verano", "Noche Invierno"</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Temporada *</label>
                                <select class="form-select" name="temporada" id="turnoTemporada" required>
                                    <option value="verano">Verano</option>
                                    <option value="invierno">Invierno</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Descripción (opcional)</label>
                            <input type="text" class="form-control" name="descripcion" id="turnoDescripcion" 
                                   placeholder="Ej: Horarios diurnos con luz natural">
                        </div>

                        <div class="mb-3" id="activoContainer" style="display: none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="turnoActivo" name="activo" checked>
                                <label class="form-check-label" for="turnoActivo">
                                    Turno activo (disponible para asignar)
                                </label>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Horarios del Turno *</label>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Agrega los horarios que incluye este turno (uno por uno)
                            </div>

                            <!-- Lista de horarios agregados -->
                            <div id="horariosAgregados" class="border rounded p-3 mb-3 bg-light" style="min-height: 60px;">
                                <div id="horariosVacios" class="text-muted text-center">
                                    <i class="fas fa-clock"></i> No hay horarios agregados
                                </div>
                            </div>

                            <!-- Agregar nuevo horario -->
                            <div class="input-group">
                                <input type="time" class="form-control" id="nuevaHora" placeholder="HH:MM">
                                <button type="button" class="btn btn-primary" onclick="agregarHorario()">
                                    <i class="fas fa-plus"></i> Agregar Horario
                                </button>
                            </div>
                            <small class="text-muted">Usa formato 24 horas (Ej: 14:00, 19:30, 22:00)</small>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar Turno
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Asignar Turno a Canchas -->
    <div class="modal fade" id="modalAsignar" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-map-marker-alt"></i> Asignar Turno a Canchas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="asignar_turno">
                    <input type="hidden" name="turno_id" id="asignarTurnoId">
                    
                    <div class="modal-body">
                        <div class="alert alert-info mb-3" id="turnoInfoAlert"></div>

                        <h6>Selecciona las canchas:</h6>
                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="seleccionarTodasCanchas()">
                                <i class="fas fa-check-double"></i> Todas
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deseleccionarTodasCanchas()">
                                <i class="fas fa-times"></i> Ninguna
                            </button>
                        </div>

                        <div class="row">
                            <?php foreach ($canchas as $cancha): ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check border rounded p-2">
                                    <input class="form-check-input cancha-asignar-check" type="checkbox" 
                                           name="canchas_ids[]" value="<?php echo $cancha['id']; ?>" 
                                           id="ca_<?php echo $cancha['id']; ?>">
                                    <label class="form-check-label w-100" for="ca_<?php echo $cancha['id']; ?>">
                                        <i class="fas fa-map-marker-alt text-success"></i>
                                        <strong><?php echo htmlspecialchars($cancha['nombre']); ?></strong>
                                        <?php if ($cancha['ubicacion']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($cancha['ubicacion']); ?></small>
                                        <?php endif; ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Asignar Horarios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Forms ocultos -->
    <form method="POST" id="formEliminarTurno" style="display: none;">
        <input type="hidden" name="action" value="eliminar_turno">
        <input type="hidden" name="id" id="eliminarTurnoId">
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        const turnosData = <?php echo json_encode($turnos); ?>;
        let horariosActuales = [];

        function agregarHorario() {
            const input = document.getElementById('nuevaHora');
            const hora = input.value;

            if (!hora) {
                alert('Por favor ingresa una hora');
                return;
            }

            // Convertir a formato HH:MM:SS si es necesario
            const horaFormateada = hora.length === 5 ? hora + ':00' : hora;

            // Verificar si ya existe
            if (horariosActuales.includes(horaFormateada)) {
                alert('Este horario ya fue agregado');
                return;
            }

            horariosActuales.push(horaFormateada);
            horariosActuales.sort();
            actualizarListaHorarios();
            input.value = '';
            input.focus();
        }

        function eliminarHorario(hora) {
            horariosActuales = horariosActuales.filter(h => h !== hora);
            actualizarListaHorarios();
        }

        function actualizarListaHorarios() {
            const container = document.getElementById('horariosAgregados');
            const vacios = document.getElementById('horariosVacios');
            const formTurno = document.getElementById('formTurno');

            // Limpiar inputs hidden anteriores
            const oldInputs = formTurno.querySelectorAll('input[name="horarios[]"]');
            oldInputs.forEach(inp => inp.remove());

            if (horariosActuales.length === 0) {
                vacios.style.display = 'block';
                container.querySelectorAll('.badge').forEach(b => b.remove());
                return;
            }

            vacios.style.display = 'none';
            
            // Limpiar badges anteriores
            container.querySelectorAll('.badge').forEach(b => b.remove());
            
            // Agregar badges e inputs
            horariosActuales.forEach(hora => {
                const horaDisplay = hora.substring(0, 5); // Mostrar solo HH:MM
                
                // Crear badge
                const badge = document.createElement('span');
                badge.className = 'badge bg-primary d-inline-flex align-items-center gap-2 m-1';
                badge.style.fontSize = '1rem';
                badge.style.padding = '8px 12px';
                badge.innerHTML = `
                    ${horaDisplay}
                    <i class="fas fa-times-circle" style="cursor: pointer;"></i>
                `;
                badge.querySelector('i').addEventListener('click', function() {
                    eliminarHorario(hora);
                });
                container.appendChild(badge);

                // Crear input hidden
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'horarios[]';
                input.value = hora;
                formTurno.appendChild(input);
            });
        }

        function editarTurno(id) {
            const turno = turnosData.find(t => t.id == id);
            if (!turno) return;

            document.getElementById('modalTurnoTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Turno';
            document.getElementById('turnoAction').value = 'editar_turno';
            document.getElementById('turnoId').value = turno.id;
            document.getElementById('turnoNombre').value = turno.nombre;
            document.getElementById('turnoTemporada').value = turno.temporada;
            document.getElementById('turnoDescripcion').value = turno.descripcion || '';
            document.getElementById('turnoActivo').checked = turno.activo == 1;
            document.getElementById('activoContainer').style.display = 'block';

            // Cargar horarios
            horariosActuales = JSON.parse(turno.horarios);
            actualizarListaHorarios();

            new bootstrap.Modal(document.getElementById('modalTurno')).show();
        }

        function eliminarTurno(id) {
            if (confirm('¿Eliminar este turno?\n\nLos horarios ya asignados NO se eliminarán.')) {
                document.getElementById('eliminarTurnoId').value = id;
                document.getElementById('formEliminarTurno').submit();
            }
        }

        function asignarTurno(id) {
            const turno = turnosData.find(t => t.id == id);
            if (!turno) return;

            const horarios = JSON.parse(turno.horarios);
            const horariosDisplay = horarios.map(h => h.substring(0, 5)).join(', ');
            document.getElementById('asignarTurnoId').value = id;
            document.getElementById('turnoInfoAlert').innerHTML = 
                `<strong>${turno.nombre}</strong> - ${horarios.length} horarios: ${horariosDisplay}`;
            
            // Deseleccionar todas
            document.querySelectorAll('.cancha-asignar-check').forEach(cb => cb.checked = false);

            new bootstrap.Modal(document.getElementById('modalAsignar')).show();
        }

        function seleccionarTodasCanchas() {
            document.querySelectorAll('.cancha-asignar-check').forEach(cb => cb.checked = true);
        }

        function deseleccionarTodasCanchas() {
            document.querySelectorAll('.cancha-asignar-check').forEach(cb => cb.checked = false);
        }

        // Enter para agregar horario
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('nuevaHora').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    agregarHorario();
                }
            });

            // Validación del formulario
            document.getElementById('formTurno').addEventListener('submit', function(e) {
                if (horariosActuales.length === 0) {
                    e.preventDefault();
                    alert('Debe agregar al menos un horario');
                }
            });
        });

        // Limpiar modal al cerrar
        document.getElementById('modalTurno').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formTurno').reset();
            document.getElementById('modalTurnoTitle').innerHTML = '<i class="fas fa-plus"></i> Crear Turno';
            document.getElementById('turnoAction').value = 'crear_turno';
            document.getElementById('turnoId').value = '';
            document.getElementById('activoContainer').style.display = 'none';
            horariosActuales = [];
            actualizarListaHorarios();
        });
    </script>
</body>
</html>