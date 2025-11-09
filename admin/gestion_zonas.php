<?php
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$categoria_id = $_GET['categoria_id'] ?? null;

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

// Obtener equipos de la categoría
$stmt = $pdo->prepare("SELECT * FROM equipos WHERE categoria_id = ? AND activo = 1 ORDER BY nombre");
$stmt->execute([$categoria_id]);
$equipos = $stmt->fetchAll();

// Obtener zonas existentes
$stmt = $pdo->prepare("SELECT * FROM zonas WHERE categoria_id = ? ORDER BY orden, nombre");
$stmt->execute([$categoria_id]);
$zonas = $stmt->fetchAll();

// Obtener equipos por zona
$equipos_por_zona = [];
foreach ($zonas as $zona) {
    $stmt = $pdo->prepare("
        SELECT e.* FROM equipos e
        INNER JOIN equipos_zonas ez ON e.id = ez.equipo_id
        WHERE ez.zona_id = ?
        ORDER BY e.nombre
    ");
    $stmt->execute([$zona['id']]);
    $equipos_por_zona[$zona['id']] = $stmt->fetchAll();
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'crear_zonas_auto') {
        $cantidad_equipos = count($equipos);
        $equipos_por_zona_config = (int)($_POST['equipos_por_zona'] ?? 4);
        
        // Calcular cantidad de zonas necesarias
        $cantidad_zonas = ceil($cantidad_equipos / $equipos_por_zona_config);
        
        // Eliminar zonas anteriores
        $pdo->prepare("DELETE FROM zonas WHERE categoria_id = ?")->execute([$categoria_id]);
        
        // Crear zonas
        $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        for ($i = 0; $i < $cantidad_zonas; $i++) {
            $stmt = $pdo->prepare("INSERT INTO zonas (categoria_id, nombre, orden) VALUES (?, ?, ?)");
            $stmt->execute([$categoria_id, "Zona {$letras[$i]}", $i + 1]);
        }
        
        // Obtener zonas creadas
        $stmt = $pdo->prepare("SELECT * FROM zonas WHERE categoria_id = ? ORDER BY orden");
        $stmt->execute([$categoria_id]);
        $zonas_nuevas = $stmt->fetchAll();
        
        // Distribuir equipos aleatoriamente
        $equipos_shuffle = $equipos;
        shuffle($equipos_shuffle);
        
        $zona_index = 0;
        foreach ($equipos_shuffle as $equipo) {
            $zona_actual = $zonas_nuevas[$zona_index];
            $stmt = $pdo->prepare("INSERT INTO equipos_zonas (equipo_id, zona_id) VALUES (?, ?)");
            $stmt->execute([$equipo['id'], $zona_actual['id']]);
            
            $zona_index = ($zona_index + 1) % count($zonas_nuevas);
        }
        
        $_SESSION['mensaje'] = "Zonas creadas y equipos distribuidos correctamente";
        header("Location: gestion_zonas.php?categoria_id={$categoria_id}");
        exit;
    }
    
    if ($action === 'asignar_equipo') {
        $equipo_id = $_POST['equipo_id'];
        $zona_id = $_POST['zona_id'];
        
        // Eliminar asignación anterior
        $pdo->prepare("DELETE FROM equipos_zonas WHERE equipo_id = ?")->execute([$equipo_id]);
        
        // Asignar a nueva zona
        if ($zona_id) {
            $stmt = $pdo->prepare("INSERT INTO equipos_zonas (equipo_id, zona_id) VALUES (?, ?)");
            $stmt->execute([$equipo_id, $zona_id]);
        }
        
        echo json_encode(['success' => true]);
        exit;
    }
    
    if ($action === 'calcular_clasificados') {
        $cantidad_zonas = count($zonas);
        $clasifican_primeros = $_POST['clasifican_primeros'] ?? 1;
        $clasifican_segundos = $_POST['clasifican_segundos'] ?? 0;
        
        $clasificados = [];
        
        // Obtener primeros de cada zona
        foreach ($zonas as $zona) {
            $stmt = $pdo->prepare("
                SELECT * FROM v_tabla_posiciones_zona 
                WHERE zona_id = ?
                ORDER BY pts DESC, dif DESC, gf DESC
                LIMIT ?
            ");
            $stmt->execute([$zona['id'], $clasifican_primeros]);
            $primeros = $stmt->fetchAll();
            
            foreach ($primeros as $equipo) {
                $clasificados[] = [
                    'equipo_id' => $equipo['equipo_id'],
                    'equipo' => $equipo['equipo'],
                    'zona' => $equipo['zona'],
                    'posicion' => 'Primero',
                    'pts' => $equipo['pts']
                ];
            }
        }
        
        // Obtener mejores segundos
        if ($clasifican_segundos > 0) {
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
            $stmt->execute([$categoria_id, $clasifican_segundos]);
            $segundos = $stmt->fetchAll();
            
            foreach ($segundos as $equipo) {
                $clasificados[] = [
                    'equipo_id' => $equipo['equipo_id'],
                    'equipo' => $equipo['equipo'],
                    'zona' => $equipo['zona'],
                    'posicion' => 'Mejor Segundo',
                    'pts' => $equipo['pts']
                ];
            }
        }
        
        echo json_encode(['success' => true, 'clasificados' => $clasificados]);
        exit;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Zonas - <?= htmlspecialchars($categoria['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .zona-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .zona-card:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
        }
        .zona-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 8px 8px 0 0;
            font-weight: bold;
        }
        .equipo-item {
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .drag-over {
            background: #e7f3ff;
            border: 2px dashed #0d6efd;
        }
        .clasificado {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        .mejor-segundo {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <?php include '../include/sidebar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="bi bi-diagram-3"></i> Gestión de Zonas</h2>
                <p class="text-muted">
                    <?= htmlspecialchars($categoria['campeonato']) ?> - 
                    <?= htmlspecialchars($categoria['nombre']) ?>
                </p>
            </div>
            <div class="col-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrearZonas">
                    <i class="bi bi-plus-circle"></i> Crear Zonas Automáticamente
                </button>
                <button class="btn btn-success" onclick="calcularClasificados()">
                    <i class="bi bi-trophy"></i> Ver Clasificados
                </button>
                <a href="fixture_eliminatorias.php?categoria_id=<?= $categoria_id ?>" class="btn btn-warning">
                    <i class="bi bi-lightning"></i> Generar Eliminatorias
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

        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5>Total Equipos</h5>
                        <h2><?= count($equipos) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5>Zonas Creadas</h5>
                        <h2><?= count($zonas) ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5>Equipos por Zona</h5>
                        <h2><?= count($zonas) > 0 ? round(count($equipos) / count($zonas), 1) : 0 ?></h2>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5>Sin Asignar</h5>
                        <h2 id="equipos-sin-asignar">0</h2>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zonas -->
        <div class="row">
            <?php foreach ($zonas as $zona): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="zona-card">
                        <div class="zona-header">
                            <?= htmlspecialchars($zona['nombre']) ?>
                            <span class="badge bg-light text-dark float-end">
                                <?= count($equipos_por_zona[$zona['id']] ?? []) ?> equipos
                            </span>
                        </div>
                        <div class="zona-body p-3" 
                             data-zona-id="<?= $zona['id'] ?>"
                             ondrop="drop(event)" 
                             ondragover="allowDrop(event)"
                             ondragleave="dragLeave(event)">
                            <?php if (isset($equipos_por_zona[$zona['id']])): ?>
                                <?php foreach ($equipos_por_zona[$zona['id']] as $equipo): ?>
                                    <div class="equipo-item" 
                                         draggable="true" 
                                         ondragstart="drag(event)"
                                         data-equipo-id="<?= $equipo['id'] ?>">
                                        <?php if ($equipo['logo']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($equipo['logo']) ?>" 
                                                 style="width: 30px; height: 30px; object-fit: contain;"
                                                 class="me-2">
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars($equipo['nombre']) ?></span>
                                        <i class="bi bi-grip-vertical text-muted"></i>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Equipos sin asignar -->
        <?php 
        $equipos_asignados_ids = [];
        foreach ($equipos_por_zona as $eq_zona) {
            foreach ($eq_zona as $eq) {
                $equipos_asignados_ids[] = $eq['id'];
            }
        }
        $equipos_sin_asignar = array_filter($equipos, function($e) use ($equipos_asignados_ids) {
            return !in_array($e['id'], $equipos_asignados_ids);
        });
        ?>
        
        <?php if (count($equipos_sin_asignar) > 0): ?>
            <div class="card border-warning mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5><i class="bi bi-exclamation-triangle"></i> Equipos Sin Asignar</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($equipos_sin_asignar as $equipo): ?>
                            <div class="col-md-3 mb-2">
                                <div class="equipo-item" 
                                     draggable="true" 
                                     ondragstart="drag(event)"
                                     data-equipo-id="<?= $equipo['id'] ?>">
                                    <?php if ($equipo['logo']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($equipo['logo']) ?>" 
                                             style="width: 30px; height: 30px; object-fit: contain;"
                                             class="me-2">
                                    <?php endif; ?>
                                    <span><?= htmlspecialchars($equipo['nombre']) ?></span>
                                    <i class="bi bi-grip-vertical text-muted"></i>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Crear Zonas -->
    <div class="modal fade" id="modalCrearZonas" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Crear Zonas Automáticamente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="crear_zonas_auto">
                        
                        <div class="alert alert-info">
                            <strong>Total de equipos:</strong> <?= count($equipos) ?>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Equipos por zona</label>
                            <select name="equipos_por_zona" class="form-select" id="equipos_por_zona">
                                <option value="3">3 equipos (<?= ceil(count($equipos) / 3) ?> zonas)</option>
                                <option value="4" selected>4 equipos (<?= ceil(count($equipos) / 4) ?> zonas)</option>
                                <option value="5">5 equipos (<?= ceil(count($equipos) / 5) ?> zonas)</option>
                                <option value="6">6 equipos (<?= ceil(count($equipos) / 6) ?> zonas)</option>
                            </select>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i>
                            Esto eliminará las zonas existentes y redistribuirá los equipos aleatoriamente.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Zonas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Clasificados -->
    <div class="modal fade" id="modalClasificados" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="bi bi-trophy"></i> Equipos Clasificados</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">Clasifican primeros de cada zona</label>
                                <input type="number" id="clasifican_primeros" class="form-control" value="1" min="1">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Mejores segundos</label>
                                <input type="number" id="clasifican_segundos" class="form-control" value="0" min="0">
                            </div>
                        </div>
                        <button class="btn btn-primary mt-3" onclick="calcularClasificados()">
                            <i class="bi bi-calculator"></i> Calcular
                        </button>
                    </div>
                    <div id="listaClasificados"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-success" onclick="generarEliminatorias()">
                        Generar Eliminatorias
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Drag and Drop
        function allowDrop(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.add('drag-over');
        }

        function dragLeave(ev) {
            ev.currentTarget.classList.remove('drag-over');
        }

        function drag(ev) {
            ev.dataTransfer.setData("equipoId", ev.currentTarget.dataset.equipoId);
        }

        function drop(ev) {
            ev.preventDefault();
            ev.currentTarget.classList.remove('drag-over');
            
            const equipoId = ev.dataTransfer.getData("equipoId");
            const zonaId = ev.currentTarget.dataset.zonaId;
            
            // Enviar asignación al servidor
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=asignar_equipo&equipo_id=${equipoId}&zona_id=${zonaId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        // Calcular clasificados
        function calcularClasificados() {
            const primeros = document.getElementById('clasifican_primeros')?.value || 1;
            const segundos = document.getElementById('clasifican_segundos')?.value || 0;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=calcular_clasificados&clasifican_primeros=${primeros}&clasifican_segundos=${segundos}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarClasificados(data.clasificados);
                    const modal = new bootstrap.Modal(document.getElementById('modalClasificados'));
                    modal.show();
                }
            });
        }

        function mostrarClasificados(clasificados) {
            const lista = document.getElementById('listaClasificados');
            let html = '<div class="table-responsive"><table class="table table-hover">';
            html += '<thead><tr><th>Equipo</th><th>Zona</th><th>Posición</th><th>Pts</th></tr></thead><tbody>';
            
            clasificados.forEach(eq => {
                const clase = eq.posicion === 'Primero' ? 'clasificado' : 'mejor-segundo';
                html += `<tr class="${clase}">
                    <td><strong>${eq.equipo}</strong></td>
                    <td>${eq.zona}</td>
                    <td>${eq.posicion}</td>
                    <td><span class="badge bg-primary">${eq.pts}</span></td>
                </tr>`;
            });
            
            html += '</tbody></table></div>';
            html += `<div class="alert alert-success mt-3">
                <strong>Total clasificados:</strong> ${clasificados.length} equipos
            </div>`;
            
            lista.innerHTML = html;
        }

        function generarEliminatorias() {
            const primeros = document.getElementById('clasifican_primeros').value;
            const segundos = document.getElementById('clasifican_segundos').value;
            window.location.href = `fixture_eliminatorias.php?categoria_id=<?= $categoria_id ?>&primeros=${primeros}&segundos=${segundos}`;
        }

        // Actualizar contador de equipos sin asignar
        document.addEventListener('DOMContentLoaded', function() {
            const sinAsignar = document.querySelectorAll('.border-warning .equipo-item').length;
            document.getElementById('equipos-sin-asignar').textContent = sinAsignar;
        });
    </script>
</body>
</html>