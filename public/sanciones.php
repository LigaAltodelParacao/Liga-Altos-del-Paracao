<?php
require_once __DIR__ . '/../config.php';

 $error = '';
 $message = '';

 $db = Database::getInstance()->getConnection();

// Filtros
 $campeonato_id = $_GET['campeonato'] ?? null;
 $categoria_id = $_GET['categoria'] ?? null;
 $filtro_tipo = $_GET['tipo'] ?? '';
 $filtro_estado = $_GET['estado'] ?? '';

// Campeonatos
 $stmt = $db->query("SELECT id, nombre FROM campeonatos WHERE activo = 1 ORDER BY nombre");
 $campeonatos = $stmt->fetchAll();

 $categorias = [];
if ($campeonato_id) {
    $stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1");
    $stmt->execute([$campeonato_id]);
    $categorias = $stmt->fetchAll();
}

// Consulta principal de sanciones
 $where_conditions = [];
 $params = [];

 $sql = "
    SELECT s.*, j.apellido_nombre, j.dni, e.nombre as equipo, e.logo as equipo_logo, c.nombre as categoria,
           (s.partidos_suspension - s.partidos_cumplidos) as fechas_restantes,
           CASE 
               WHEN s.activa = 1 THEN 'Activa'
               ELSE 'Cumplida'
           END as estado_texto
    FROM sanciones s
    JOIN jugadores j ON s.jugador_id = j.id
    JOIN equipos e ON j.equipo_id = e.id
    JOIN categorias c ON e.categoria_id = c.id
";

if ($campeonato_id) {
    // Filtrar por campeonato_id si está disponible en sanciones, sino por categoría
    $where_conditions[] = "(s.campeonato_id = ? OR (s.campeonato_id IS NULL AND c.campeonato_id = ?))";
    $params[] = $campeonato_id;
    $params[] = $campeonato_id;
}

if ($categoria_id) {
    $where_conditions[] = "e.categoria_id = ?";
    $params[] = $categoria_id;
}

if ($filtro_tipo) {
    $where_conditions[] = "s.tipo = ?";
    $params[] = $filtro_tipo;
}

if ($filtro_estado === 'activa') {
    $where_conditions[] = "s.activa = 1";
} elseif ($filtro_estado === 'cumplida') {
    $where_conditions[] = "s.activa = 0";
} else {
    // Por defecto: solo mostrar sanciones activas
    $where_conditions[] = "s.activa = 1";
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

 $sql .= " ORDER BY s.fecha_sancion DESC, s.activa DESC";

 $stmt = $db->prepare($sql);
 $stmt->execute($params);
 $sanciones = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sanciones</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .live-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            background: #e74c3c;
            border-radius: 50%;
            margin-left: 8px;
            animation: blink 1.5s infinite;
        }
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .match-card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            background: white;
            overflow: hidden;
            height: 100%;
        }
        .match-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(0,0,0,0.12);
        }
        .team-logo {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 50%;
            background: #f1f1f1;
        }
        .match-header {
            background: #f8f9fa;
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }
        .match-body {
            padding: 15px;
        }
        .score-display {
            font-size: 1.8rem;
            font-weight: bold;
            margin: 0 10px;
        }
        .match-timer {
            background: #e74c3c;
            color: white;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .event-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 20px;
            margin: 2px;
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .event-gol { background: #2ecc71; color: white; }
        .event-amarilla { background: #f1c40f; color: black; }
        .event-roja { background: #e74c3c; color: white; }
        .eventos-container {
            min-height: 30px;
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 4px;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #eee;
        }
        .resultado-clickeable {
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .resultado-clickeable:hover {
            background-color: #f8f9fa;
        }
        .logo-placeholder {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #ddd;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            color: #666;
        }
        .stats-card {
            text-align: center;
            padding: 15px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .stats-icon {
            font-size: 1.8rem;
            margin-bottom: 10px;
        }
        
        /* Estilos móviles */
        @media (max-width: 768px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            h2 {
                font-size: 1.5rem;
            }
            
            .table-responsive {
                font-size: 0.8rem;
            }
            
            .table th, .table td {
                padding: 0.5rem 0.3rem;
                font-size: 0.8rem;
            }
            
            .table th.d-none-mobile,
            .table td.d-none-mobile {
                display: none;
            }
            
            .table img {
                width: 24px !important;
                height: 24px !important;
            }
            
            .col-md-2 {
                margin-bottom: 0.5rem;
            }
            
            .card {
                margin-bottom: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            
            .stats-card {
                padding: 0.75rem;
            }
            
            h3 {
                font-size: 1.5rem;
            }
            
            .d-flex.justify-content-between {
                flex-direction: column;
                align-items: flex-start !important;
            }
        }
        
        @media (max-width: 576px) {
            h2 {
                font-size: 1.25rem;
            }
            
            .table th, .table td {
                padding: 0.4rem 0.2rem;
                font-size: 0.75rem;
            }
            
            .col-md-2 {
                margin-bottom: 0.75rem;
            }
            
            h3 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
     <?php include '../include/header.php'; ?>

    <!-- Contenido Principal alineado -->
    <main class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-ban"></i> Sanciones </h2>                    
        </div>
        <!-- Estadísticas rápidas -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card estadisticas">
                    <div class="card-body text-center">
                        <h3><?= count(array_filter($sanciones, fn($s) => $s['activa'] == 1)) ?></h3>
                        <p class="mb-0">Sanciones Activas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <h3><?= count(array_filter($sanciones, fn($s) => $s['tipo'] == 'amarillas_acumuladas' && $s['activa'] == 1)) ?></h3>
                        <p class="mb-0">4 Amarillas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card" style="background: linear-gradient(135deg, #ffc107 50%, #dc3545 50%);">
                    <div class="card-body text-center text-white">
                        <h3><?= count(array_filter($sanciones, fn($s) => $s['tipo'] == 'doble_amarilla' && $s['activa'] == 1)) ?></h3>
                        <p class="mb-0">Doble Amarilla</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <h3><?= count(array_filter($sanciones, fn($s) => $s['tipo'] == 'roja_directa' && $s['activa'] == 1)) ?></h3>
                        <p class="mb-0">Roja Directa</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <h3><?= count(array_filter($sanciones, fn($s) => $s['tipo'] == 'administrativa' && $s['activa'] == 1)) ?></h3>
                        <p class="mb-0">Administrativas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <h3><?= count(array_filter($sanciones, fn($s) => $s['activa'] == 0)) ?></h3>
                        <p class="mb-0">Cumplidas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-2">
                        <select name="campeonato" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos los campeonatos</option>
                            <?php foreach($campeonatos as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= $campeonato_id == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if($campeonato_id): ?>
                        <div class="col-md-2">
                            <select name="categoria" class="form-select" onchange="this.form.submit()">
                                <option value="">Todas las categorías</option>
                                <?php foreach($categorias as $cat): ?>
                                    <option value="<?= $cat['id'] ?>" <?= $categoria_id == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="campeonato" value="<?= $campeonato_id ?>">
                        </div>
                    <?php endif; ?>
                    <div class="col-md-2">
                        <select name="tipo" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos los tipos</option>
                            <option value="amarillas_acumuladas" <?= $filtro_tipo == 'amarillas_acumuladas' ? 'selected' : '' ?>>4 Amarillas</option>
                            <option value="doble_amarilla" <?= $filtro_tipo == 'doble_amarilla' ? 'selected' : '' ?>>Doble Amarilla</option>
                            <option value="roja_directa" <?= $filtro_tipo == 'roja_directa' ? 'selected' : '' ?>>Roja Directa</option>
                            <option value="administrativa" <?= $filtro_tipo == 'administrativa' ? 'selected' : '' ?>>Administrativa</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="estado" class="form-select" onchange="this.form.submit()">
                            <option value="">Activas</option>
                            <option value="cumplida" <?= $filtro_estado == 'cumplida' ? 'selected' : '' ?>>Cumplidas</option>
                        </select>
                    </div>
                    <?php if($campeonato_id || $categoria_id || $filtro_tipo || $filtro_estado): ?>
                        <div class="col-md-2">
                            <a href="sanciones.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times"></i> Limpiar
                            </a>
                        </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- Lista de sanciones -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list"></i> Lista de Sanciones (<?= count($sanciones) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <?php if(empty($sanciones)): ?>
                    <div class="text-center p-4">
                        <i class="fas fa-ban fa-3x text-muted mb-3"></i>
                        <p class="text-muted">
                            <?php if ($filtro_estado === 'cumplida'): ?>
                                No hay sanciones cumplidas.
                            <?php else: ?>
                                No hay sanciones activas.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Jugador</th>
                                    <th class="d-none-mobile">Equipo</th>
                                    <th>Tipo</th>
                                    <th>Fechas</th>
                                    <th class="hide-xs">Estado</th>
                                    <th class="d-none-mobile">Fecha Sanción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($sanciones as $s): ?>
                                    <tr class="<?= $s['activa'] ? 'table-danger' : 'table-success' ?>">
                                        <td>
                                            <strong><?= htmlspecialchars($s['apellido_nombre']) ?></strong><br>
                                            <small class="text-muted d-md-none"><?= htmlspecialchars($s['equipo']) ?></small>
                                        </td>
                                        <td class="d-none-mobile">
                                            <div class="d-flex align-items-center">
                                                <?php if(!empty($s['equipo_logo'])): ?>
                                                    <img src="../uploads/<?= htmlspecialchars($s['equipo_logo']) ?>" 
                                                         alt="<?= htmlspecialchars($s['equipo']) ?>" 
                                                         class="me-2"
                                                         style="width: 30px; height: 30px; object-fit: contain;">
                                                <?php else: ?>
                                                    <div class="me-2 bg-secondary rounded d-flex align-items-center justify-content-center" 
                                                         style="width: 30px; height: 30px;">
                                                        <i class="fas fa-shield-alt text-white" style="font-size: 16px;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <?= htmlspecialchars($s['equipo']) ?><br>
                                                    <small class="text-muted"><?= htmlspecialchars($s['categoria']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $badges = [
                                                'amarillas_acumuladas' => '<span class="badge bg-warning text-dark">🟨 4 Amarillas</span>',
                                                'doble_amarilla' => '<span class="badge bg-danger">🟨🟥 Doble Amarilla</span>',
                                                'roja_directa' => '<span class="badge bg-danger">🟥 Roja Directa</span>',
                                                'administrativa' => '<span class="badge bg-info">📋 Administrativa</span>'
                                            ];
                                            echo $badges[$s['tipo']] ?? htmlspecialchars($s['tipo']);
                                            ?>
                                        </td>
                                        <td>
                                            <strong><?= min($s['partidos_cumplidos'], $s['partidos_suspension']) ?>/<?= $s['partidos_suspension'] ?></strong><br>
                                            <?php if($s['fechas_restantes'] > 0): ?>
                                                <small class="text-danger">Faltan <?= $s['fechas_restantes'] ?></small>
                                            <?php else: ?>
                                                <small class="text-success">✓ Cumplida</small>
                                            <?php endif; ?>
                                            <?php if($s['activa']): ?>
                                                <br><span class="badge bg-danger d-md-none">🔴 Activa</span>
                                            <?php else: ?>
                                                <br><span class="badge bg-success d-md-none">✓ Cumplida</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="hide-xs">
                                            <?php if($s['activa']): ?>
                                                <span class="badge bg-danger">🔴 Activa</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">✓ Cumplida</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="d-none-mobile"><?= date('d/m/Y', strtotime($s['fecha_sancion'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Información adicional -->
        <div class="card mt-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle"></i> Información del Sistema de Sanciones</h5>
            </div>
            <div class="card-body">
                <h6><strong>Tipos de Sanciones:</strong></h6>
                <ul class="mb-3">
                    <li><strong>4 Amarillas Acumuladas:</strong> 1 fecha de suspensión automática</li>
                    <li><strong>Doble Amarilla:</strong> 1-2 fechas según reglamento</li>
                    <li><strong>Roja Directa:</strong> 2+ fechas según gravedad de la falta</li>
                    <li><strong>Administrativa:</strong> Cantidad determinada por autoridades del torneo</li>
                </ul>
                
                <h6><strong>Sistema Automático:</strong></h6>
                <ul class="mb-3">
                    <li>Las sanciones se cumplen automáticamente cuando el equipo del jugador juega un partido oficial</li>
                    <li>El sistema actualiza el contador de fechas cumplidas al cargar resultados</li>
                    <li>Una vez cumplidas todas las fechas, la sanción se marca como completada automáticamente</li>
                    <li>Los jugadores sancionados no pueden ser incluidos en planillas de juego</li>
                </ul>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white text-center py-3 mt-5">
        <div class="container">
            <p class="mb-0">&copy; <?= date('Y') ?> Fútbol Manager. Sistema de Gestión de Torneos.</p>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>