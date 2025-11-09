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

// Obtener fases del torneo
$stmt = $pdo->prepare("
    SELECT * FROM fases_torneo 
    WHERE categoria_id = ? 
    ORDER BY orden
");
$stmt->execute([$categoria_id]);
$fases = $stmt->fetchAll();

// Obtener partidos por fase
$partidos_por_fase = [];
foreach ($fases as $fase) {
    $stmt = $pdo->prepare("
        SELECT 
            p.*,
            pe.numero_llave,
            pe.es_ida,
            pe.equipo_ganador_id,
            el.nombre as equipo_local,
            el.logo as logo_local,
            ev.nombre as equipo_visitante,
            ev.logo as logo_visitante,
            f.numero_fecha,
            f.fecha_programada
        FROM partidos p
        INNER JOIN partidos_eliminatorias pe ON p.id = pe.partido_id
        INNER JOIN equipos el ON p.equipo_local_id = el.id
        INNER JOIN equipos ev ON p.equipo_visitante_id = ev.id
        INNER JOIN fechas f ON p.fecha_id = f.id
        WHERE pe.fase_id = ?
        ORDER BY pe.numero_llave, pe.es_ida DESC
    ");
    $stmt->execute([$fase['id']]);
    $partidos_por_fase[$fase['id']] = $stmt->fetchAll();
}

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'definir_ganador') {
        $partido_id = $_POST['partido_id'];
        $ganador_id = $_POST['ganador_id'];
        $llave = $_POST['llave'];
        $fase_id = $_POST['fase_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Actualizar ganador en la llave
            $stmt = $pdo->prepare("
                UPDATE partidos_eliminatorias 
                SET equipo_ganador_id = ?
                WHERE partido_id = ?
            ");
            $stmt->execute([$ganador_id, $partido_id]);
            
            // Buscar si hay siguiente fase
            $stmt = $pdo->prepare("
                SELECT * FROM fases_torneo 
                WHERE categoria_id = ? AND orden > (
                    SELECT orden FROM fases_torneo WHERE id = ?
                )
                ORDER BY orden
                LIMIT 1
            ");
            $stmt->execute([$categoria_id, $fase_id]);
            $siguiente_fase = $stmt->fetch();
            
            if ($siguiente_fase) {
                // Verificar si todos los ganadores de esta llave están definidos
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) as total,
                           SUM(CASE WHEN equipo_ganador_id IS NOT NULL THEN 1 ELSE 0 END) as definidos
                    FROM partidos_eliminatorias
                    WHERE fase_id = ? AND numero_llave = ?
                ");
                $stmt->execute([$fase_id, $llave]);
                $estado_llave = $stmt->fetch();
                
                if ($estado_llave['total'] == $estado_llave['definidos']) {
                    // Todos los partidos de esta llave tienen ganador
                    // Crear partido en siguiente fase si no existe
                    
                    $nueva_llave = ceil($llave / 2);
                    
                    // Buscar fecha de la siguiente fase
                    $stmt = $pdo->prepare("
                        SELECT * FROM fechas 
                        WHERE categoria_id = ? AND fase_id = ?
                        LIMIT 1
                    ");
                    $stmt->execute([$categoria_id, $siguiente_fase['id']]);
                    $fecha_siguiente = $stmt->fetch();
                    
                    if (!$fecha_siguiente) {
                        // Crear fecha para la siguiente fase
                        $stmt = $pdo->prepare("
                            SELECT MAX(numero_fecha) as max_fecha FROM fechas WHERE categoria_id = ?
                        ");
                        $stmt->execute([$categoria_id]);
                        $max_fecha = $stmt->fetchColumn() ?: 0;
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO fechas (categoria_id, numero_fecha, fecha_programada, fase_id)
                            VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY), ?)
                        ");
                        $stmt->execute([$categoria_id, $max_fecha + 1, $siguiente_fase['id']]);
                        $fecha_id_siguiente = $pdo->lastInsertId();
                    } else {
                        $fecha_id_siguiente = $fecha_siguiente['id'];
                    }
                    
                    // Determinar equipos de la nueva llave
                    $es_llave_impar = ($llave % 2) == 1;
                    
                    if ($es_llave_impar) {
                        // Este ganador va como local en la nueva llave
                        $equipo_local_id = $ganador_id;
                        $equipo_visitante_id = null; // Se define con el ganador de la siguiente llave
                    } else {
                        // Este ganador va como visitante en la nueva llave
                        $equipo_visitante_id = $ganador_id;
                        
                        // Buscar el ganador de la llave anterior (impar)
                        $stmt = $pdo->prepare("
                            SELECT equipo_ganador_id FROM partidos_eliminatorias
                            WHERE fase_id = ? AND numero_llave = ?
                            LIMIT 1
                        ");
                        $stmt->execute([$fase_id, $llave - 1]);
                        $ganador_anterior = $stmt->fetchColumn();
                        
                        if ($ganador_anterior) {
                            $equipo_local_id = $ganador_anterior;
                            
                            // Verificar si el partido ya existe
                            $stmt = $pdo->prepare("
                                SELECT p.id FROM partidos p
                                INNER JOIN partidos_eliminatorias pe ON p.id = pe.partido_id
                                WHERE pe.fase_id = ? AND pe.numero_llave = ?
                            ");
                            $stmt->execute([$siguiente_fase['id'], $nueva_llave]);
                            $partido_existente = $stmt->fetchColumn();
                            
                            if (!$partido_existente) {
                                // Crear partido en siguiente fase
                                $stmt = $pdo->prepare("
                                    INSERT INTO partidos 
                                    (fecha_id, equipo_local_id, equipo_visitante_id, fecha_partido, estado)
                                    VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'programado')
                                ");
                                $stmt->execute([$fecha_id_siguiente, $equipo_local_id, $equipo_visitante_id]);
                                $nuevo_partido_id = $pdo->lastInsertId();
                                
                                // Registrar en eliminatorias
                                $stmt = $pdo->prepare("
                                    INSERT INTO partidos_eliminatorias (partido_id, fase_id, numero_llave, es_ida)
                                    VALUES (?, ?, ?, 1)
                                ");
                                $stmt->execute([$nuevo_partido_id, $siguiente_fase['id'], $nueva_llave]);
                                
                                // Si la fase es ida y vuelta, crear vuelta
                                if ($siguiente_fase['ida_vuelta']) {
                                    $stmt = $pdo->prepare("
                                        INSERT INTO partidos 
                                        (fecha_id, equipo_local_id, equipo_visitante_id, fecha_partido, estado)
                                        VALUES (?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'programado')
                                    ");
                                    $stmt->execute([$fecha_id_siguiente, $equipo_visitante_id, $equipo_local_id]);
                                    $partido_vuelta_id = $pdo->lastInsertId();
                                    
                                    $stmt = $pdo->prepare("
                                        INSERT INTO partidos_eliminatorias (partido_id, fase_id, numero_llave, es_ida)
                                        VALUES (?, ?, ?, 0)
                                    ");
                                    $stmt->execute([$partido_vuelta_id, $siguiente_fase['id'], $nueva_llave]);
                                }
                            }
                        }
                    }
                }
            }
            
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Ganador definido correctamente']);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración Eliminatorias - <?= htmlspecialchars($categoria['nombre']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .bracket {
            display: flex;
            justify-content: space-around;
            padding: 20px;
            overflow-x: auto;
        }
        .bracket-round {
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            min-width: 250px;
            padding: 0 15px;
        }
        .bracket-match {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 10px;
            margin: 10px 0;
            overflow: hidden;
            transition: all 0.3s;
        }
        .bracket-match:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.2);
        }
        .bracket-match.finalizado {
            border-color: #28a745;
            background: #f8fff9;
        }
        .match-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 8px 12px;
            font-size: 0.85rem;
            font-weight: bold;
        }
        .match-team {
            display: flex;
            align-items: center;
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s;
        }
        .match-team:last-child {
            border-bottom: none;
        }
        .match-team:hover {
            background: #f8f9fa;
        }
        .match-team.ganador {
            background: #d4edda;
            font-weight: bold;
        }
        .match-team img {
            width: 35px;
            height: 35px;
            object-fit: contain;
            margin-right: 10px;
        }
        .match-score {
            margin-left: auto;
            font-size: 1.2rem;
            font-weight: bold;
            min-width: 40px;
            text-align: center;
        }
        .btn-define-ganador {
            padding: 2px 8px;
            font-size: 0.75rem;
        }
        .fase-title {
            text-align: center;
            font-weight: bold;
            color: #495057;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .ida-vuelta-badge {
            font-size: 0.7rem;
            padding: 2px 6px;
        }
        .aggregate-score {
            font-size: 0.8rem;
            color: #6c757d;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <?php include '../include/sidebar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="bi bi-trophy-fill"></i> Eliminatorias en Vivo</h2>
                <p class="text-muted">
                    <?= htmlspecialchars($categoria['campeonato']) ?> - 
                    <?= htmlspecialchars($categoria['nombre']) ?>
                </p>
            </div>
            <div class="col-auto">
                <a href="gestion_zonas.php?categoria_id=<?= $categoria_id ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i> Volver
                </a>
                <button class="btn btn-primary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise"></i> Actualizar
                </button>
            </div>
        </div>

        <!-- Bracket de Eliminatorias -->
        <div class="card">
            <div class="card-body">
                <div class="bracket">
                    <?php foreach ($fases as $fase): ?>
                        <?php if ($fase['tipo'] !== 'grupos' && isset($partidos_por_fase[$fase['id']])): ?>
                            <div class="bracket-round">
                                <div class="fase-title">
                                    <?= htmlspecialchars($fase['nombre']) ?>
                                    <?php if ($fase['ida_vuelta']): ?>
                                        <span class="badge bg-info ida-vuelta-badge">Ida/Vuelta</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php
                                // Agrupar por llave
                                $llaves = [];
                                foreach ($partidos_por_fase[$fase['id']] as $partido) {
                                    $llaves[$partido['numero_llave']][] = $partido;
                                }
                                ?>
                                
                                <?php foreach ($llaves as $num_llave => $partidos_llave): ?>
                                    <div class="bracket-match <?= $partidos_llave[0]['equipo_ganador_id'] ? 'finalizado' : '' ?>">
                                        <div class="match-header">
                                            Llave <?= $num_llave ?>
                                            <?php if (count($partidos_llave) > 1): ?>
                                                - Global
                                            <?php endif; ?>
                                        </div>
                                        
                                        <?php
                                        // Calcular resultado global si es ida y vuelta
                                        $goles_local_total = 0;
                                        $goles_visitante_total = 0;
                                        $equipo_local_id = null;
                                        $equipo_visitante_id = null;
                                        $ganador_id = $partidos_llave[0]['equipo_ganador_id'] ?? null;
                                        
                                        foreach ($partidos_llave as $p) {
                                            if ($p['estado'] === 'finalizado') {
                                                if ($p['es_ida']) {
                                                    $goles_local_total += $p['goles_local'];
                                                    $goles_visitante_total += $p['goles_visitante'];
                                                    $equipo_local_id = $p['equipo_local_id'];
                                                    $equipo_visitante_id = $p['equipo_visitante_id'];
                                                } else {
                                                    // En la vuelta los equipos están invertidos
                                                    $goles_local_total += $p['goles_visitante'];
                                                    $goles_visitante_total += $p['goles_local'];
                                                }
                                            }
                                        }
                                        
                                        // Mostrar equipos
                                        $primer_partido = $partidos_llave[0];
                                        $equipo_local_id = $primer_partido['equipo_local_id'];
                                        $equipo_visitante_id = $primer_partido['equipo_visitante_id'];
                                        ?>
                                        
                                        <!-- Equipo Local -->
                                        <div class="match-team <?= $ganador_id == $equipo_local_id ? 'ganador' : '' ?>">
                                            <?php if ($primer_partido['logo_local']): ?>
                                                <img src="../uploads/<?= htmlspecialchars($primer_partido['logo_local']) ?>" alt="Logo">
                                            <?php endif; ?>
                                            <span class="flex-grow-1"><?= htmlspecialchars($primer_partido['equipo_local']) ?></span>
                                            
                                            <?php if (count($partidos_llave) > 1): ?>
                                                <span class="aggregate-score">(<?= $goles_local_total ?>)</span>
                                            <?php endif; ?>
                                            
                                            <span class="match-score">
                                                <?php
                                                $goles_local_mostrar = '';
                                                foreach ($partidos_llave as $p) {
                                                    if ($p['estado'] === 'finalizado') {
                                                        $goles_local_mostrar .= ($p['es_ida'] ? $p['goles_local'] : $p['goles_visitante']) . ' ';
                                                    }
                                                }
                                                echo trim($goles_local_mostrar);
                                                ?>
                                            </span>
                                            
                                            <?php if (!$ganador_id && $primer_partido['estado'] === 'finalizado'): ?>
                                                <button class="btn btn-sm btn-success btn-define-ganador ms-2"
                                                        onclick="definirGanador(<?= $primer_partido['id'] ?>, <?= $equipo_local_id ?>, <?= $num_llave ?>, <?= $fase['id'] ?>)">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Equipo Visitante -->
                                        <div class="match-team <?= $ganador_id == $equipo_visitante_id ? 'ganador' : '' ?>">
                                            <?php if ($primer_partido['logo_visitante']): ?>
                                                <img src="../uploads/<?= htmlspecialchars($primer_partido['logo_visitante']) ?>" alt="Logo">
                                            <?php endif; ?>
                                            <span class="flex-grow-1"><?= htmlspecialchars($primer_partido['equipo_visitante']) ?></span>
                                            
                                            <?php if (count($partidos_llave) > 1): ?>
                                                <span class="aggregate-score">(<?= $goles_visitante_total ?>)</span>
                                            <?php endif; ?>
                                            
                                            <span class="match-score">
                                                <?php
                                                $goles_visitante_mostrar = '';
                                                foreach ($partidos_llave as $p) {
                                                    if ($p['estado'] === 'finalizado') {
                                                        $goles_visitante_mostrar .= ($p['es_ida'] ? $p['goles_visitante'] : $p['goles_local']) . ' ';
                                                    }
                                                }
                                                echo trim($goles_visitante_mostrar);
                                                ?>
                                            </span>
                                            
                                            <?php if (!$ganador_id && $primer_partido['estado'] === 'finalizado'): ?>
                                                <button class="btn btn-sm btn-success btn-define-ganador ms-2"
                                                        onclick="definirGanador(<?= $primer_partido['id'] ?>, <?= $equipo_visitante_id ?>, <?= $num_llave ?>, <?= $fase['id'] ?>)">
                                                    <i class="bi bi-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Detalles de los partidos -->
                                        <div class="p-2 bg-light border-top">
                                            <?php foreach ($partidos_llave as $p): ?>
                                                <small class="d-block text-muted">
                                                    <?= $p['es_ida'] ? 'Ida' : 'Vuelta' ?>: 
                                                    <?= date('d/m/Y', strtotime($p['fecha_partido'])) ?>
                                                    <?php if ($p['hora_partido']): ?>
                                                        - <?= date('H:i', strtotime($p['hora_partido'])) ?>
                                                    <?php endif; ?>
                                                    <span class="badge bg-<?= $p['estado'] === 'finalizado' ? 'success' : ($p['estado'] === 'en_curso' ? 'warning' : 'secondary') ?> ms-2">
                                                        <?= ucfirst($p['estado']) ?>
                                                    </span>
                                                    <a href="partido_live.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary ms-2" target="_blank">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </small>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function definirGanador(partidoId, ganadorId, llave, faseId) {
            if (!confirm('¿Está seguro de definir este equipo como ganador de la llave?')) {
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=definir_ganador&partido_id=${partidoId}&ganador_id=${ganadorId}&llave=${llave}&fase_id=${faseId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Ganador definido correctamente. Se ha creado el cruce para la siguiente fase.');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error al definir ganador: ' + error);
            });
        }
        
        // Auto-refresh cada 30 segundos
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>