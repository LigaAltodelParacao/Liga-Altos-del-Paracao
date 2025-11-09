<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$formato_id = $_GET['id'] ?? null;

if (!$formato_id) {
    redirect('campeonatos_zonas.php');
}

// Obtener información del formato
$stmt = $db->prepare("
    SELECT cf.*, c.nombre as campeonato_nombre
    FROM campeonatos_formato cf
    JOIN campeonatos c ON cf.campeonato_id = c.id
    WHERE cf.id = ?
");
$stmt->execute([$formato_id]);
$formato = $stmt->fetch();

// Obtener zonas con partidos
$stmt = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
$stmt->execute([$formato_id]);
$zonas = $stmt->fetchAll();

// Obtener partidos por zona
$partidos_por_zona = [];
foreach ($zonas as $zona) {
    $stmt = $db->prepare("
        SELECT pz.*,
               el.nombre as local_nombre, el.logo as local_logo,
               ev.nombre as visitante_nombre, ev.logo as visitante_logo,
               c.nombre as cancha_nombre
        FROM partidos_zona pz
        JOIN equipos el ON pz.equipo_local_id = el.id
        JOIN equipos ev ON pz.equipo_visitante_id = ev.id
        LEFT JOIN canchas c ON pz.cancha_id = c.id
        WHERE pz.zona_id = ?
        ORDER BY pz.fecha_numero, pz.fecha_partido, pz.hora_partido
    ");
    $stmt->execute([$zona['id']]);
    $partidos_por_zona[$zona['id']] = $stmt->fetchAll();
}

// Obtener tabla de posiciones por zona
$tablas_por_zona = [];
foreach ($zonas as $zona) {
    $stmt = $db->prepare("
        SELECT ez.*, e.nombre, e.logo
        FROM equipos_zonas ez
        JOIN equipos e ON ez.equipo_id = e.id
        WHERE ez.zona_id = ?
        ORDER BY ez.puntos DESC, ez.diferencia_gol DESC, ez.goles_favor DESC
    ");
    $stmt->execute([$zona['id']]);
    $tablas_por_zona[$zona['id']] = $stmt->fetchAll();
}

// Obtener fases eliminatorias
$stmt = $db->prepare("SELECT * FROM fases_eliminatorias WHERE formato_id = ? ORDER BY orden");
$stmt->execute([$formato_id]);
$fases = $stmt->fetchAll();

// Obtener partidos eliminatorios por fase
$partidos_eliminatorios = [];
foreach ($fases as $fase) {
    $stmt = $db->prepare("
        SELECT pe.*,
               el.nombre as local_nombre, el.logo as local_logo,
               ev.nombre as visitante_nombre, ev.logo as visitante_logo,
               eg.nombre as ganador_nombre,
               c.nombre as cancha_nombre
        FROM partidos_eliminatorios pe
        LEFT JOIN equipos el ON pe.equipo_local_id = el.id
        LEFT JOIN equipos ev ON pe.equipo_visitante_id = ev.id
        LEFT JOIN equipos eg ON pe.ganador_id = eg.id
        LEFT JOIN canchas c ON pe.cancha_id = c.id
        WHERE pe.fase_id = ?
        ORDER BY pe.numero_llave
    ");
    $stmt->execute([$fase['id']]);
    $partidos_eliminatorios[$fase['id']] = $stmt->fetchAll();
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixture Completo - <?php echo htmlspecialchars($formato['campeonato_nombre']); ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .nav-pills .nav-link {
            border-radius: 20px;
            margin: 0 5px;
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .zona-section {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .zona-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .partido-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        .partido-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .partido-card.finalizado {
            background: #f8f9fa;
        }
        .equipo-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .equipo-logo {
            width: 40px;
            height: 40px;
            object-fit: contain;
            margin-right: 10px;
        }
        .resultado {
            font-size: 1.5em;
            font-weight: bold;
            color: #198754;
            margin: 0 15px;
        }
        .tabla-posiciones {
            width: 100%;
        }
        .tabla-posiciones th {
            background: #f8f9fa;
            font-size: 0.85em;
            text-transform: uppercase;
        }
        .tabla-posiciones td {
            vertical-align: middle;
        }
        .posicion-clasificado {
            background: #d4edda;
        }
        .bracket-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            gap: 20px;
        }
        .bracket-match {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            min-width: 300px;
            background: white;
        }
        .bracket-match.finalizado {
            border-color: #198754;
        }
        .llave-numero {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 15px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 10px;
        }
        .fase-badge {
            font-size: 1.2em;
            padding: 10px 20px;
            border-radius: 20px;
        }
    </style>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-trophy"></i> Fixture Completo</h2>
                        <p class="text-muted mb-0"><?php echo htmlspecialchars($formato['campeonato_nombre']); ?></p>
                    </div>
                    <div>
                        <a href="campeonatos_zonas.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                        <button class="btn btn-primary" onclick="window.print()">
                            <i class="fas fa-print"></i> Imprimir
                        </button>
                    </div>
                </div>

                <!-- Pestañas de navegación -->
                <ul class="nav nav-pills mb-4" id="fixtureTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="zonas-tab" data-bs-toggle="pill" 
                                data-bs-target="#zonas" type="button">
                            <i class="fas fa-layer-group"></i> Fase de Zonas
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="tablas-tab" data-bs-toggle="pill" 
                                data-bs-target="#tablas" type="button">
                            <i class="fas fa-table"></i> Tablas de Posiciones
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="eliminatorias-tab" data-bs-toggle="pill" 
                                data-bs-target="#eliminatorias" type="button">
                            <i class="fas fa-crown"></i> Fase Eliminatoria
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="fixtureTabContent">
                    <!-- TAB: FASE DE ZONAS -->
                    <div class="tab-pane fade show active" id="zonas">
                        <?php foreach ($zonas as $zona): ?>
                        <div class="zona-section">
                            <div class="zona-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($zona['nombre']); ?>
                                </h4>
                            </div>

                            <?php if (empty($partidos_por_zona[$zona['id']])): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> No hay partidos generados para esta zona.
                            </div>
                            <?php else: ?>
                            
                            <?php 
                            // Agrupar partidos por fecha
                            $partidos_agrupados = [];
                            foreach ($partidos_por_zona[$zona['id']] as $partido) {
                                $partidos_agrupados[$partido['fecha_numero']][] = $partido;
                            }
                            ?>

                            <?php foreach ($partidos_agrupados as $numero_fecha => $partidos): ?>
                            <div class="mb-4">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-calendar-day"></i> Fecha <?php echo $numero_fecha; ?>
                                    <small class="text-muted">
                                        (<?php echo formatDate($partidos[0]['fecha_partido']); ?>)
                                    </small>
                                </h5>

                                <?php foreach ($partidos as $partido): ?>
                                <div class="partido-card <?php echo $partido['estado'] === 'finalizado' ? 'finalizado' : ''; ?>">
                                    <div class="row align-items-center">
                                        <div class="col-5">
                                            <div class="d-flex align-items-center justify-content-end">
                                                <strong><?php echo htmlspecialchars($partido['local_nombre']); ?></strong>
                                                <?php if ($partido['local_logo']): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($partido['local_logo']); ?>" 
                                                     class="equipo-logo ms-2" alt="Logo">
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="col-2 text-center">
                                            <?php if ($partido['estado'] === 'finalizado'): ?>
                                            <div class="resultado">
                                                <?php echo $partido['goles_local']; ?> - <?php echo $partido['goles_visitante']; ?>
                                            </div>
                                            <?php else: ?>
                                            <div class="text-muted">
                                                <i class="fas fa-clock"></i>
                                                <?php echo substr($partido['hora_partido'], 0, 5); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-5">
                                            <div class="d-flex align-items-center">
                                                <?php if ($partido['visitante_logo']): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($partido['visitante_logo']); ?>" 
                                                     class="equipo-logo me-2" alt="Logo">
                                                <?php endif; ?>
                                                <strong><?php echo htmlspecialchars($partido['visitante_nombre']); ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row mt-2">
                                        <div class="col-12 text-center">
                                            <small class="text-muted">
                                                <?php if ($partido['cancha_nombre']): ?>
                                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($partido['cancha_nombre']); ?>
                                                <?php endif; ?>
                                                <span class="badge bg-<?php 
                                                    echo $partido['estado'] === 'finalizado' ? 'success' : 
                                                        ($partido['estado'] === 'en_curso' ? 'danger' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst($partido['estado']); ?>
                                                </span>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- TAB: TABLAS DE POSICIONES -->
                    <div class="tab-pane fade" id="tablas">
                        <div class="row">
                            <?php foreach ($zonas as $zona): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="zona-section">
                                    <div class="zona-header">
                                        <h5 class="mb-0"><?php echo htmlspecialchars($zona['nombre']); ?></h5>
                                    </div>

                                    <?php if (empty($tablas_por_zona[$zona['id']])): ?>
                                    <div class="alert alert-info">
                                        No hay equipos asignados a esta zona.
                                    </div>
                                    <?php else: ?>
                                    <table class="table tabla-posiciones">
                                        <thead>
                                            <tr>
                                                <th>Pos</th>
                                                <th>Equipo</th>
                                                <th class="text-center">PJ</th>
                                                <th class="text-center">G</th>
                                                <th class="text-center">E</th>
                                                <th class="text-center">P</th>
                                                <th class="text-center">GF</th>
                                                <th class="text-center">GC</th>
                                                <th class="text-center">DIF</th>
                                                <th class="text-center">Pts</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $posicion = 1;
                                            foreach ($tablas_por_zona[$zona['id']] as $equipo): 
                                                $clasificado = $posicion <= $formato['equipos_clasifican'];
                                            ?>
                                            <tr class="<?php echo $clasificado ? 'posicion-clasificado' : ''; ?>">
                                                <td>
                                                    <strong><?php echo $posicion; ?></strong>
                                                    <?php if ($clasificado): ?>
                                                    <i class="fas fa-arrow-up text-success"></i>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($equipo['logo']): ?>
                                                        <img src="../uploads/<?php echo htmlspecialchars($equipo['logo']); ?>" 
                                                             style="width: 25px; height: 25px; object-fit: contain; margin-right: 8px;">
                                                        <?php endif; ?>
                                                        <span><?php echo htmlspecialchars($equipo['nombre']); ?></span>
                                                    </div>
                                                </td>
                                                <td class="text-center"><?php echo $equipo['partidos_jugados']; ?></td>
                                                <td class="text-center"><?php echo $equipo['partidos_ganados']; ?></td>
                                                <td class="text-center"><?php echo $equipo['partidos_empatados']; ?></td>
                                                <td class="text-center"><?php echo $equipo['partidos_perdidos']; ?></td>
                                                <td class="text-center"><?php echo $equipo['goles_favor']; ?></td>
                                                <td class="text-center"><?php echo $equipo['goles_contra']; ?></td>
                                                <td class="text-center">
                                                    <strong><?php echo $equipo['diferencia_gol'] >= 0 ? '+' : ''; ?><?php echo $equipo['diferencia_gol']; ?></strong>
                                                </td>
                                                <td class="text-center">
                                                    <strong class="text-primary"><?php echo $equipo['puntos']; ?></strong>
                                                </td>
                                            </tr>
                                            <?php 
                                            $posicion++;
                                            endforeach; 
                                            ?>
                                        </tbody>
                                    </table>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="alert alert-success">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Clasificación:</strong> Los equipos marcados en verde clasifican a la siguiente fase.
                            Clasifican los primeros <?php echo $formato['equipos_clasifican']; ?> de cada zona.
                        </div>
                    </div>

                    <!-- TAB: FASE ELIMINATORIA -->
                    <div class="tab-pane fade" id="eliminatorias">
                        <?php if (empty($fases)): ?>
                        <div class="alert alert-info">
                            No hay fases eliminatorias configuradas.
                        </div>
                        <?php else: ?>
                        <?php foreach ($fases as $fase): ?>
                        <div class="zona-section">
                            <div class="text-center mb-4">
                                <span class="fase-badge badge bg-primary">
                                    <i class="fas fa-trophy"></i> <?php echo ucfirst(str_replace('_', ' ', $fase['nombre'])); ?>
                                </span>
                            </div>

                            <?php if (empty($partidos_eliminatorios[$fase['id']])): ?>
                            <div class="alert alert-info">
                                No hay partidos generados para esta fase.
                            </div>
                            <?php else: ?>
                            <div class="bracket-container">
                                <?php foreach ($partidos_eliminatorios[$fase['id']] as $partido): ?>
                                <div class="bracket-match <?php echo $partido['estado'] === 'finalizado' ? 'finalizado' : ''; ?>">
                                    <div class="text-center mb-3">
                                        <span class="llave-numero">Llave <?php echo $partido['numero_llave']; ?></span>
                                    </div>

                                    <!-- Equipo Local -->
                                    <div class="mb-2 p-2 border rounded <?php echo $partido['ganador_id'] == $partido['equipo_local_id'] ? 'bg-success text-white' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <?php if ($partido['local_logo']): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($partido['local_logo']); ?>" 
                                                     style="width: 30px; height: 30px; object-fit: contain; margin-right: 10px;">
                                                <?php endif; ?>
                                                <strong>
                                                    <?php echo $partido['equipo_local_id'] ? htmlspecialchars($partido['local_nombre']) : $partido['origen_local']; ?>
                                                </strong>
                                            </div>
                                            <?php if ($partido['estado'] === 'finalizado'): ?>
                                            <span class="badge bg-dark"><?php echo $partido['goles_local']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Equipo Visitante -->
                                    <div class="mb-2 p-2 border rounded <?php echo $partido['ganador_id'] == $partido['equipo_visitante_id'] ? 'bg-success text-white' : ''; ?>">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center">
                                                <?php if ($partido['visitante_logo']): ?>
                                                <img src="../uploads/<?php echo htmlspecialchars($partido['visitante_logo']); ?>" 
                                                     style="width: 30px; height: 30px; object-fit: contain; margin-right: 10px;">
                                                <?php endif; ?>
                                                <strong>
                                                    <?php echo $partido['equipo_visitante_id'] ? htmlspecialchars($partido['visitante_nombre']) : $partido['origen_visitante']; ?>
                                                </strong>
                                            </div>
                                            <?php if ($partido['estado'] === 'finalizado'): ?>
                                            <span class="badge bg-dark"><?php echo $partido['goles_visitante']; ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <!-- Info del partido -->
                                    <div class="text-center mt-3">
                                        <?php if ($partido['fecha_partido']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar"></i> <?php echo formatDate($partido['fecha_partido']); ?>
                                            <?php if ($partido['hora_partido']): ?>
                                            - <?php echo substr($partido['hora_partido'], 0, 5); ?>
                                            <?php endif; ?>
                                        </small>
                                        <br>
                                        <?php endif; ?>
                                        <?php if ($partido['cancha_nombre']): ?>
                                        <small class="text-muted">
                                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($partido['cancha_nombre']); ?>
                                        </small>
                                        <br>
                                        <?php endif; ?>
                                        <span class="badge bg-<?php 
                                            echo $partido['estado'] === 'finalizado' ? 'success' : 
                                                ($partido['estado'] === 'programado' ? 'primary' : 'secondary'); 
                                        ?>">
                                            <?php echo ucfirst($partido['estado']); ?>
                                        </span>
                                        <?php if ($partido['penales_local'] !== null): ?>
                                        <br><small class="text-danger">
                                            <i class="fas fa-futbol"></i> Penales: 
                                            <?php echo $partido['penales_local']; ?> - <?php echo $partido['penales_visitante']; ?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>