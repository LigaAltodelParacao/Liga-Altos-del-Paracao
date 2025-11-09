<?php
/**
 * Vista pública del bracket de eliminación directa
 */

require_once __DIR__ . '/../config.php';

$db = Database::getInstance()->getConnection();

$formato_id = $_GET['formato_id'] ?? null;

if (!$formato_id) {
    header('Location: tablas_zonas.php');
    exit;
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
    header('Location: tablas_zonas.php');
    exit;
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
            ev.logo as logo_visitante
        FROM partidos p
        LEFT JOIN equipos el ON p.equipo_local_id = el.id
        LEFT JOIN equipos ev ON p.equipo_visitante_id = ev.id
        WHERE p.fase_eliminatoria_id = ? AND p.tipo_torneo = 'eliminatoria'
        ORDER BY p.numero_llave
    ");
    $stmt->execute([$fase['id']]);
    $partidos_por_fase[$fase['id']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Mapear nombres de fases
$nombres_fases = [
    'octavos' => 'Octavos de Final',
    'cuartos' => 'Cuartos de Final',
    'semifinal' => 'Semifinales',
    'tercer_puesto' => 'Tercer Puesto',
    'final' => 'Final'
];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bracket - Eliminatorias</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .bracket-container {
            display: flex;
            justify-content: space-around;
            flex-wrap: wrap;
            padding: 2rem 0;
        }
        .fase-column {
            min-width: 200px;
            margin: 0 1rem;
        }
        .fase-title {
            text-align: center;
            font-weight: bold;
            margin-bottom: 1rem;
            padding: 0.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 5px;
        }
        .partido-item {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            min-height: 100px;
            transition: all 0.3s;
        }
        .partido-item:hover {
            border-color: #0d6efd;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .partido-finalizado {
            border-color: #28a745;
            background: #f8fff9;
        }
        .equipo-line {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0;
        }
        .equipo-nombre {
            font-weight: 500;
            flex: 1;
        }
        .equipo-goles {
            font-weight: bold;
            font-size: 1.2rem;
            margin: 0 0.5rem;
        }
        .vs-line {
            text-align: center;
            padding: 0.5rem 0;
            color: #6c757d;
            font-weight: bold;
        }
        .conector {
            position: absolute;
            width: 50px;
            height: 2px;
            background: #dee2e6;
            right: -50px;
            top: 50%;
        }
        .fase-inactiva {
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <i class="fas fa-futbol"></i> Fútbol Manager
            </a>
        </div>
    </nav>

    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-sitemap"></i> Bracket - Eliminación Directa</h2>
                <p class="text-muted">
                    <?= htmlspecialchars($torneo['campeonato_nombre']) ?> - 
                    <?= htmlspecialchars($torneo['categoria_nombre']) ?>
                </p>
            </div>
        </div>

        <?php if (empty($fases) || empty($partidos_por_fase)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Aún no se han generado los partidos eliminatorios. Se generarán automáticamente cuando se completen todos los partidos de la fase de grupos.
            </div>
        <?php else: ?>
            <div class="bracket-container">
                <?php foreach ($fases as $fase): ?>
                    <div class="fase-column <?= !$fase['activa'] ? 'fase-inactiva' : '' ?>">
                        <div class="fase-title">
                            <?= $nombres_fases[$fase['nombre']] ?? ucfirst($fase['nombre']) ?>
                        </div>
                        
                        <?php 
                        $partidos = $partidos_por_fase[$fase['id']] ?? [];
                        foreach ($partidos as $partido): 
                        ?>
                            <div class="partido-item <?= $partido['estado'] === 'finalizado' ? 'partido-finalizado' : '' ?>" 
                                 style="position: relative;">
                                
                                <div class="equipo-line">
                                    <div class="d-flex align-items-center">
                                        <?php if ($partido['logo_local']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($partido['logo_local']) ?>" 
                                                 width="25" height="25" class="me-2" style="object-fit: contain;">
                                        <?php endif; ?>
                                        <span class="equipo-nombre">
                                            <?= htmlspecialchars($partido['equipo_local'] ?? 'Por definir') ?>
                                        </span>
                                    </div>
                                    <?php if ($partido['estado'] === 'finalizado'): ?>
                                        <span class="equipo-goles <?= $partido['goles_local'] > $partido['goles_visitante'] ? 'text-success' : '' ?>">
                                            <?= $partido['goles_local'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="vs-line">VS</div>
                                
                                <div class="equipo-line">
                                    <div class="d-flex align-items-center">
                                        <?php if ($partido['logo_visitante']): ?>
                                            <img src="../uploads/<?= htmlspecialchars($partido['logo_visitante']) ?>" 
                                                 width="25" height="25" class="me-2" style="object-fit: contain;">
                                        <?php endif; ?>
                                        <span class="equipo-nombre">
                                            <?= htmlspecialchars($partido['equipo_visitante'] ?? 'Por definir') ?>
                                        </span>
                                    </div>
                                    <?php if ($partido['estado'] === 'finalizado'): ?>
                                        <span class="equipo-goles <?= $partido['goles_visitante'] > $partido['goles_local'] ? 'text-success' : '' ?>">
                                            <?= $partido['goles_visitante'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($partido['estado'] === 'finalizado' && $partido['goles_local_penales'] !== null): ?>
                                    <div class="text-center mt-2">
                                        <small class="text-muted">
                                            Penales: <?= $partido['goles_local_penales'] ?> - <?= $partido['goles_visitante_penales'] ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($partido['origen_local'] || $partido['origen_visitante']): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <?php if ($partido['origen_local']): ?>
                                                <?= htmlspecialchars($partido['origen_local']) ?>
                                            <?php endif; ?>
                                            <?php if ($partido['origen_local'] && $partido['origen_visitante']): ?> vs <?php endif; ?>
                                            <?php if ($partido['origen_visitante']): ?>
                                                <?= htmlspecialchars($partido['origen_visitante']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>

