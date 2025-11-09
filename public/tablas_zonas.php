<?php
/**
 * Vista pública de tablas de posiciones por zona
 */

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../admin/funciones_torneos_zonas.php';

$db = Database::getInstance()->getConnection();

// Obtener categorías activas con torneos por zonas
$stmt = $db->query("
    SELECT DISTINCT
        cat.*,
        camp.nombre as campeonato_nombre
    FROM categorias cat
    JOIN campeonatos camp ON cat.campeonato_id = camp.id
    JOIN campeonatos_formato cf ON cf.campeonato_id = camp.id
    WHERE cat.activa = 1 AND camp.activo = 1 AND cf.activo = 1
    ORDER BY camp.fecha_inicio DESC, cat.nombre ASC
");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Categoría seleccionada
$categoria_id = $_GET['categoria'] ?? ($categorias[0]['id'] ?? null);

// Obtener formato del torneo
$formato_id = null;
$torneo = null;
$zonas = [];
$tablas_posiciones = [];

if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT cf.*, camp.nombre as campeonato_nombre
        FROM campeonatos_formato cf
        JOIN categorias cat ON cf.categoria_id = cat.id
        JOIN campeonatos camp ON cf.campeonato_id = camp.id
        WHERE cat.id = ? AND cf.activo = 1
        LIMIT 1
    ");
    $stmt->execute([$categoria_id]);
    $torneo = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($torneo) {
        $formato_id = $torneo['id'];
        
        // Obtener zonas
        $stmt = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
        $stmt->execute([$formato_id]);
        $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener tablas de posiciones
        foreach ($zonas as $zona) {
            $tablas_posiciones[$zona['id']] = obtenerTablaPosicionesZona($zona['id'], $db);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tablas de Posiciones - Zonas</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo SITE_URL; ?>assets/css/style.css" rel="stylesheet">
    <style>
        .tabla-posiciones {
            font-size: 0.9rem;
        }
        .posicion-1 { 
            background: linear-gradient(90deg, #d4edda 0%, #c3e6cb 100%) !important;
            font-weight: bold;
        }
        .posicion-2 { 
            background: linear-gradient(90deg, #fff3cd 0%, #ffeaa7 100%) !important;
        }
        .posicion-3 { 
            background: linear-gradient(90deg, #f8d7da 0%, #f5c6cb 100%) !important;
        }
        .zona-card {
            border: 2px solid #dee2e6;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .zona-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 10px 10px 0 0;
        }
    </style>
</head>
<body>
    <?php include '../include/sidebar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="row mb-4">
            <div class="col">
                <h2><i class="fas fa-table"></i> Tablas de Posiciones por Zona</h2>
            </div>
            <div class="col-auto">
                <select class="form-select" id="selectCategoria" onchange="window.location.href='?categoria=' + this.value">
                    <option value="">Seleccionar categoría...</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $categoria_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['campeonato_nombre']) ?> - <?= htmlspecialchars($cat['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($torneo && !empty($zonas)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                <strong><?= htmlspecialchars($torneo['campeonato_nombre']) ?></strong> - 
                <?= htmlspecialchars($torneo['categoria_nombre'] ?? '') ?>
                <br>
                <small>Clasifican: <?= $torneo['equipos_clasifican'] ?> equipos</small>
            </div>

            <div class="row">
                <?php foreach ($zonas as $zona): ?>
                    <div class="col-md-6 mb-4">
                        <div class="zona-card">
                            <div class="zona-header">
                                <h4 class="mb-0">
                                    <i class="fas fa-layer-group"></i> <?= htmlspecialchars($zona['nombre']) ?>
                                </h4>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-sm tabla-posiciones mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th style="width: 5%;">Pos</th>
                                                <th>Equipo</th>
                                                <th class="text-center" style="width: 6%;">Pts</th>
                                                <th class="text-center" style="width: 5%;">PJ</th>
                                                <th class="text-center" style="width: 5%;">PG</th>
                                                <th class="text-center" style="width: 5%;">PE</th>
                                                <th class="text-center" style="width: 5%;">PP</th>
                                                <th class="text-center" style="width: 6%;">GF</th>
                                                <th class="text-center" style="width: 6%;">GC</th>
                                                <th class="text-center" style="width: 6%;">Dif</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $equipos = $tablas_posiciones[$zona['id']] ?? [];
                                            foreach ($equipos as $equipo): 
                                            ?>
                                                <tr class="posicion-<?= $equipo['posicion'] <= 3 ? $equipo['posicion'] : '' ?>">
                                                    <td><strong><?= $equipo['posicion'] ?></strong></td>
                                                    <td>
                                                        <?php if ($equipo['logo']): ?>
                                                            <img src="../uploads/<?= htmlspecialchars($equipo['logo']) ?>" 
                                                                 width="25" height="25" class="me-2" style="object-fit: contain;">
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
                                                    <td class="text-center">
                                                        <span class="<?= $equipo['diferencia_gol'] > 0 ? 'text-success' : ($equipo['diferencia_gol'] < 0 ? 'text-danger' : '') ?>">
                                                            <?= $equipo['diferencia_gol'] > 0 ? '+' : '' ?><?= $equipo['diferencia_gol'] ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                No hay torneos por zonas activos en esta categoría.
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>

