<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Procesar asignación de horarios y canchas
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action == 'asignar_horarios') {
        $fecha_seleccionada = $_POST['fecha_seleccionada'] ?? '';
        $categorias_seleccionadas = $_POST['categorias'] ?? [];
        $canchas_seleccionadas = $_POST['canchas'] ?? [];
        $hora_inicio = $_POST['hora_inicio'] ?? '14:00';
        $intervalo_minutos = (int)($_POST['intervalo_minutos'] ?? 90);
        
        if (empty($fecha_seleccionada)) {
            $error = 'Debe seleccionar una fecha';
        } elseif (empty($categorias_seleccionadas)) {
            $error = 'Debe seleccionar al menos una categoría';
        } elseif (empty($canchas_seleccionadas)) {
            $error = 'Debe seleccionar al menos una cancha';
        } else {
            try {
                $db->beginTransaction();
                
                // Obtener todos los partidos sin asignar de las categorías seleccionadas para esa fecha
                $categorias_placeholders = str_repeat('?,', count($categorias_seleccionadas) - 1) . '?';
                $params = array_merge([$fecha_seleccionada], $categorias_seleccionadas);
                
                $stmt = $db->prepare("
                    SELECT p.*, c.nombre as categoria_nombre, 
                           el.nombre as equipo_local, ev.nombre as equipo_visitante
                    FROM partidos p
                    JOIN fechas f ON p.fecha_id = f.id
                    JOIN categorias c ON f.categoria_id = c.id
                    JOIN equipos el ON p.equipo_local_id = el.id
                    JOIN equipos ev ON p.equipo_visitante_id = ev.id
                    WHERE p.fecha_partido = ? 
                    AND f.categoria_id IN ($categorias_placeholders)
                    AND p.estado = 'sin_asignar'
                    ORDER BY c.nombre, f.numero_fecha, p.id
                ");
                $stmt->execute($params);
                $partidos_sin_asignar = $stmt->fetchAll();
                
                if (empty($partidos_sin_asignar)) {
                    throw new Exception('No hay partidos sin asignar para las categorías y fecha seleccionadas');
                }
                
                // Verificar conflictos existentes en las canchas seleccionadas para esa fecha
                $canchas_placeholders = str_repeat('?,', count($canchas_seleccionadas) - 1) . '?';
                $params_conflicto = array_merge([$fecha_seleccionada], $canchas_seleccionadas);
                
                $stmt = $db->prepare("
                    SELECT cancha_id, hora_partido
                    FROM partidos 
                    WHERE fecha_partido = ? 
                    AND cancha_id IN ($canchas_placeholders)
                    AND estado != 'sin_asignar'
                    ORDER BY cancha_id, hora_partido
                ");
                $stmt->execute($params_conflicto);
                $horarios_ocupados = $stmt->fetchAll();
                
                // Crear matriz de disponibilidad
                $disponibilidad = [];
                foreach ($canchas_seleccionadas as $cancha_id) {
                    $disponibilidad[$cancha_id] = [];
                }
                
                // Marcar horarios ocupados
                foreach ($horarios_ocupados as $ocupado) {
                    $disponibilidad[$ocupado['cancha_id']][] = $ocupado['hora_partido'];
                }
                
                // Asignar partidos
                $hora_actual = new DateTime($hora_inicio);
                $cancha_index = 0;
                $partidos_asignados = 0;
                
                foreach ($partidos_sin_asignar as $partido) {
                    $asignado = false;
                    $intentos = 0;
                    
                    while (!$asignado && $intentos < 50) { // Máximo 50 intentos para evitar loop infinito
                        $cancha_id = $canchas_seleccionadas[$cancha_index % count($canchas_seleccionadas)];
                        $hora_str = $hora_actual->format('H:i:s');
                        
                        // Verificar si la cancha está disponible en este horario
                        if (!in_array($hora_str, $disponibilidad[$cancha_id])) {
                            // Asignar el partido
                            $stmt = $db->prepare("
                                UPDATE partidos 
                                SET cancha_id = ?, hora_partido = ?, estado = 'programado'
                                WHERE id = ?
                            ");
                            $stmt->execute([$cancha_id, $hora_str, $partido['id']]);
                            
                            // Marcar horario como ocupado
                            $disponibilidad[$cancha_id][] = $hora_str;
                            
                            $partidos_asignados++;
                            $asignado = true;
                        }
                        
                        // Siguiente cancha
                        $cancha_index++;
                        
                        // Si probamos todas las canchas en este horario, avanzar al siguiente horario
                        if ($cancha_index % count($canchas_seleccionadas) == 0) {
                            $hora_actual->add(new DateInterval("PT{$intervalo_minutos}M"));
                        }
                        
                        $intentos++;
                    }
                    
                    if (!$asignado) {
                        throw new Exception("No se pudo asignar el partido {$partido['equipo_local']} vs {$partido['equipo_visitante']}. Revise la disponibilidad de canchas.");
                    }
                }
                
                $db->commit();
                $message = "Horarios y canchas asignados exitosamente:<br>• $partidos_asignados partidos programados";
                
            } catch (Exception $e) {
                $db->rollback();
                $error = 'Error al asignar horarios: ' . $e->getMessage();
            }
        }
    }
}

// Obtener fechas con partidos sin asignar
$stmt = $db->query("
    SELECT DISTINCT p.fecha_partido, 
           COUNT(p.id) as total_partidos,
           COUNT(CASE WHEN p.estado = 'sin_asignar' THEN 1 END) as sin_asignar
    FROM partidos p
    WHERE p.fecha_partido >= CURDATE()
    GROUP BY p.fecha_partido
    HAVING sin_asignar > 0
    ORDER BY p.fecha_partido
");
$fechas_disponibles = $stmt->fetchAll();

// Obtener canchas activas
$stmt = $db->query("SELECT * FROM canchas WHERE activa = 1 ORDER BY nombre");
$canchas = $stmt->fetchAll();

// Variables para el formulario
$fecha_seleccionada = $_GET['fecha'] ?? '';
$categorias_por_fecha = [];

if ($fecha_seleccionada) {
    // Obtener categorías con partidos sin asignar para la fecha seleccionada
    $stmt = $db->prepare("
        SELECT DISTINCT c.id, c.nombre, camp.nombre as campeonato_nombre,
               COUNT(p.id) as total_partidos,
               COUNT(CASE WHEN p.estado = 'sin_asignar' THEN 1 END) as sin_asignar
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        JOIN categorias c ON f.categoria_id = c.id
        JOIN campeonatos camp ON c.campeonato_id = camp.id
        WHERE p.fecha_partido = ?
        GROUP BY c.id, c.nombre, camp.nombre
        HAVING sin_asignar > 0
        ORDER BY camp.nombre, c.nombre
    ");
    $stmt->execute([$fecha_seleccionada]);
    $categorias_por_fecha = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Horarios y Canchas - Sistema de Campeonatos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
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
                    <h2><i class="fas fa-clock"></i> Asignar Horarios y Canchas</h2>
                    <a href="generar_canchas.php" class="btn btn-info">
                        <i class="fas fa-magic"></i> Generar Fixtures
                    </a>
                </div>

                <!-- Mensajes -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Selector de fecha -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-calendar-alt"></i> Seleccionar Fecha</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($fechas_disponibles)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay partidos sin asignar</h5>
                                <p class="text-muted">Primero genera fixtures usando el "Generador de Fixtures Masivo"</p>
                                <a href="generar_canchas.php" class="btn btn-primary">
                                    <i class="fas fa-magic"></i> Ir al Generador
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($fechas_disponibles as $fecha): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card <?php echo $fecha_seleccionada == $fecha['fecha_partido'] ? 'border-primary' : 'border-light'; ?>">
                                            <div class="card-body text-center">
                                                <h6 class="card-title">
                                                    <?php echo date('l, d/m/Y', strtotime($fecha['fecha_partido'])); ?>
                                                </h6>
                                                <div class="mb-2">
                                                    <span class="badge bg-info"><?php echo $fecha['total_partidos']; ?> partidos</span>
                                                    <span class="badge bg-warning"><?php echo $fecha['sin_asignar']; ?> sin asignar</span>
                                                </div>
                                                <a href="?fecha=<?php echo $fecha['fecha_partido']; ?>" 
                                                   class="btn btn-<?php echo $fecha_seleccionada == $fecha['fecha_partido'] ? 'primary' : 'outline-primary'; ?> btn-sm">
                                                    <i class="fas fa-<?php echo $fecha_seleccionada == $fecha['fecha_partido'] ? 'check' : 'arrow-right'; ?>"></i>
                                                    <?php echo $fecha_seleccionada == $fecha['fecha_partido'] ? 'Seleccionada' : 'Seleccionar'; ?>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($fecha_seleccionada && !empty($categorias_por_fecha)): ?>
                    <!-- Formulario de asignación -->
                    <form method="POST" id="formAsignar">
                        <input type="hidden" name="action" value="asignar_horarios">
                        <input type="hidden" name="fecha_seleccionada" value="<?php echo $fecha_seleccionada; ?>">
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-cogs"></i> Configuración de Asignación - <?php echo date('d/m/Y', strtotime($fecha_seleccionada)); ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- Selección de categorías -->
                                    <div class="col-md-6">
                                        <h6>Categorías a asignar:</h6>
                                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="select_all_categorias" onchange="toggleAllCategorias()">
                                                <label class="form-check-label fw-bold" for="select_all_categorias">
                                                    Seleccionar todas
                                                </label>
                                            </div>
                                            <hr>
                                            <?php foreach ($categorias_por_fecha as $categoria): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input categoria-checkbox" type="checkbox" 
                                                           name="categorias[]" value="<?php echo $categoria['id']; ?>" 
                                                           id="categoria_<?php echo $categoria['id']; ?>">
                                                    <label class="form-check-label" for="categoria_<?php echo $categoria['id']; ?>">
                                                        <strong><?php echo htmlspecialchars($categoria['nombre']); ?></strong>
                                                        <br><small class="text-muted"><?php echo htmlspecialchars($categoria['campeonato_nombre']); ?></small>
                                                        <br><span class="badge bg-warning"><?php echo $categoria['sin_asignar']; ?> partidos sin asignar</span>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <!-- Selección de canchas -->
                                    <div class="col-md-6">
                                        <h6>Canchas disponibles:</h6>
                                        <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
                                            <div class="form-check mb-2">
                                                <input class="form-check-input" type="checkbox" id="select_all_canchas" onchange="toggleAllCanchas()">
                                                <label class="form-check-label fw-bold" for="select_all_canchas">
                                                    Seleccionar todas
                                                </label>
                                            </div>
                                            <hr>
                                            <?php foreach ($canchas as $cancha): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input cancha-checkbox" type="checkbox" 
                                                           name="canchas[]" value="<?php echo $cancha['id']; ?>" 
                                                           id="cancha_<?php echo $cancha['id']; ?>">
                                                    <label class="form-check-label" for="cancha_<?php echo $cancha['id']; ?>">
                                                        <i class="fas fa-map-marker-alt"></i>
                                                        <?php echo htmlspecialchars($cancha['nombre']); ?>
                                                        <?php if ($cancha['ubicacion']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($cancha['ubicacion']); ?></small>
                                                        <?php endif; ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <!-- Configuración de horarios -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="form-label">Hora de inicio:</label>
                                        <input type="time" class="form-control" name="hora_inicio" value="14:00" required>
                                        <small class="text-muted">Primer partido del día</small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Intervalo entre partidos (minutos):</label>
                                        <select class="form-select" name="intervalo_minutos" required>
                                            <option value="90" selected>90 minutos (recomendado)</option>
                                            <option value="60">60 minutos</option>
                                            <option value="105">105 minutos</option>
                                            <option value="120">120 minutos</option>
                                        </select>
                                        <small class="text-muted">Tiempo entre inicio de partidos</small>
                                    </div>
                                    <div class="col-md-4 d-flex align-items-end">
                                        <button type="submit" class="btn btn-success w-100" onclick="return confirmarAsignacion()">
                                            <i class="fas fa-save"></i> Asignar Horarios y Canchas
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Vista previa de partidos -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-eye"></i> Vista previa de partidos sin asignar</h5>
                        </div>
                        <div class="card-body">
                            <div id="vista-previa">
                                <p class="text-muted">Selecciona categorías para ver los partidos que se asignarán</p>
                            </div>
                        </div>
                    </div>

                <?php elseif ($fecha_seleccionada): ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                            <h4 class="text-success">¡Todos los partidos ya están asignados!</h4>
                            <p class="text-muted">No hay partidos sin asignar para la fecha seleccionada</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Ayuda -->
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-question-circle"></i> Ayuda y recomendaciones</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6>Algoritmo de asignación:</h6>
                                <ul class="small">
                                    <li>Los partidos se asignan secuencialmente</li>
                                    <li>Se rotan las canchas disponibles</li>
                                    <li>Se evitan conflictos de horario automáticamente</li>
                                    <li>Si una cancha está ocupada, se prueba la siguiente</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Recomendaciones:</h6>
                                <ul class="small">
                                    <li>Usa al menos 2-3 canchas para mayor flexibilidad</li>
                                    <li>Intervalo de 90 min incluye partido + limpieza</li>
                                    <li>Comienza temprano si hay muchos partidos</li>
                                    <li>Verifica conflictos antes de asignar</li>
                                </ul>
                            </div>
                            <div class="col-md-4">
                                <h6>Estados de partidos:</h6>
                                <ul class="small">
                                    <li><span class="badge bg-warning">sin_asignar</span> - Creado sin cancha/hora</li>
                                    <li><span class="badge bg-success">programado</span> - Con cancha y hora asignada</li>
                                    <li><span class="badge bg-danger">en_curso</span> - Partido iniciado</li>
                                    <li><span class="badge bg-primary">finalizado</span> - Partido terminado</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAllCategorias() {
            const selectAll = document.getElementById('select_all_categorias');
            const checkboxes = document.querySelectorAll('.categoria-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
            
            updateVistaPrevia();
        }

        function toggleAllCanchas() {
            const selectAll = document.getElementById('select_all_canchas');
            const checkboxes = document.querySelectorAll('.cancha-checkbox');
            
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        function updateVistaPrevia() {
            const categoriasSeleccionadas = [];
            document.querySelectorAll('.categoria-checkbox:checked').forEach(checkbox => {
                const label = document.querySelector(`label[for="${checkbox.id}"]`);
                const nombre = label.querySelector('strong').textContent;
                const badge = label.querySelector('.badge').textContent;
                categoriasSeleccionadas.push({
                    id: checkbox.value,
                    nombre: nombre,
                    partidos: badge
                });
            });

            const vistaPrevia = document.getElementById('vista-previa');
            
            if (categoriasSeleccionadas.length === 0) {
                vistaPrevia.innerHTML = '<p class="text-muted">Selecciona categorías para ver los partidos que se asignarán</p>';
                return;
            }

            let html = '<div class="row">';
            let totalPartidos = 0;
            
            categoriasSeleccionadas.forEach(categoria => {
                const numPartidos = parseInt(categoria.partidos.split(' ')[0]);
                totalPartidos += numPartidos;
                
                html += `
                    <div class="col-md-6 col-lg-4 mb-2">
                        <div class="card border-primary">
                            <div class="card-body p-2">
                                <h6 class="card-title mb-1">${categoria.nombre}</h6>
                                <span class="badge bg-warning">${categoria.partidos}</span>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            html += `<div class="alert alert-info mt-3">
                <strong><i class="fas fa-info-circle"></i> Total: ${totalPartidos} partidos para asignar</strong>
            </div>`;
            
            vistaPrevia.innerHTML = html;
        }

        function confirmarAsignacion() {
            const categoriasSeleccionadas = document.querySelectorAll('.categoria-checkbox:checked').length;
            const canchasSeleccionadas = document.querySelectorAll('.cancha-checkbox:checked').length;
            
            if (categoriasSeleccionadas === 0) {
                alert('Debe seleccionar al menos una categoría');
                return false;
            }
            
            if (canchasSeleccionadas === 0) {
                alert('Debe seleccionar al menos una cancha');
                return false;
            }
            
            const fecha = '<?php echo date("d/m/Y", strtotime($fecha_seleccionada)); ?>';
            return confirm(`¿Asignar horarios y canchas para ${categoriasSeleccionadas} categorías el ${fecha}?\n\nSe utilizarán ${canchasSeleccionadas} canchas para distribuir los partidos.\n\nEsta acción cambiará el estado de los partidos a "programado".`);
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Actualizar vista previa cuando cambian las categorías
            document.querySelectorAll('.categoria-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateVistaPrevia);
            });
        });
    </script>
</body>
</html>