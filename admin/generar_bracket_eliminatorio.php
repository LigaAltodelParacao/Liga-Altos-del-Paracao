<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/includes/desempate_functions.php'; // Incluir las funciones de desempate

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();

// Verificar si se solicitó generar bracket
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generar_bracket'])) {
    $formato_id = $_POST['formato_id'];
    
    try {
        // 1. Verificar que TODOS los partidos de zona estén completos
        $stmt = $db->prepare("
            SELECT COUNT(*) as pendientes
            FROM partidos_zona pz
            INNER JOIN zonas z ON pz.zona_id = z.id
            WHERE z.formato_id = ?
            AND pz.estado != 'finalizado'
        ");
        $stmt->execute([$formato_id]);
        $pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['pendientes'];
        
        if ($pendientes > 0) {
            throw new Exception("Aún hay {$pendientes} partidos pendientes en la fase de zonas. Todos los partidos deben estar finalizados.");
        }
        
        // 2. Verificar que no se haya generado ya el bracket
        $stmt = $db->prepare("
            SELECT COUNT(*) as ya_generado
            FROM partidos_eliminatorios pe
            INNER JOIN fases_eliminatorias fe ON pe.fase_id = fe.id
            WHERE fe.formato_id = ?
        ");
        $stmt->execute([$formato_id]);
        $ya_generado = $stmt->fetch(PDO::FETCH_ASSOC)['ya_generado'];
        
        if ($ya_generado > 0) {
            throw new Exception("El bracket de eliminación directa ya fue generado para este torneo.");
        }
        
        $db->beginTransaction();
        
        // 3. Recalcular todas las tablas aplicando criterios de desempate
        $stmt = $db->prepare("SELECT id FROM zonas WHERE formato_id = ?");
        $stmt->execute([$formato_id]);
        $zonas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($zonas as $zona_id) {
            calcularTablaPosicionesConDesempate($zona_id, $db);
        }
        
        // 4. Generar emparejamientos con el nuevo sistema
        $emparejamientos = generarBracketEliminatorio($formato_id, $db);
        
        if (empty($emparejamientos)) {
            throw new Exception("No se pudieron generar los emparejamientos. Verifica que haya equipos clasificados.");
        }
        
        // 5. Insertar partidos eliminatorios
        $stmt_insert = $db->prepare("
            INSERT INTO partidos_eliminatorios 
            (fase_id, numero_llave, equipo_local_id, equipo_visitante_id, origen_local, origen_visitante, estado, fecha_partido, hora_partido, cancha_id)
            VALUES (?, ?, ?, ?, ?, ?, 'pendiente', NULL, NULL, NULL)
        ");
        
        foreach ($emparejamientos as $emp) {
            $stmt_insert->execute([
                $emp['fase_id'],
                $emp['numero_llave'],
                $emp['equipo_local_id'],
                $emp['equipo_visitante_id'],
                $emp['origen_local'],
                $emp['origen_visitante']
            ]);
        }
        
        $db->commit();
        
        logActivity("Bracket eliminatorio generado para formato $formato_id con " . count($emparejamientos) . " emparejamientos");
        
        header("Location: campeonatos_zonas_detalle.php?id=$formato_id&msg=bracket_generado");
        exit;
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $error = $e->getMessage();
        error_log("Error al generar bracket: " . $e->getMessage());
    }
}

// Obtener formatos disponibles para generar bracket
$stmt = $db->query("
    SELECT 
        cf.id,
        c.nombre as campeonato,
        cf.cantidad_zonas,
        cf.equipos_clasifican,
        COUNT(DISTINCT z.id) as zonas_creadas,
        COUNT(pz.id) as total_partidos,
        SUM(CASE WHEN pz.estado = 'finalizado' THEN 1 ELSE 0 END) as partidos_completados,
        (SELECT COUNT(*) FROM partidos_eliminatorios pe 
         INNER JOIN fases_eliminatorias fe ON pe.fase_id = fe.id 
         WHERE fe.formato_id = cf.id) as bracket_generado
    FROM campeonatos_formato cf
    INNER JOIN campeonatos c ON cf.campeonato_id = c.id
    LEFT JOIN zonas z ON cf.id = z.formato_id
    LEFT JOIN partidos_zona pz ON z.id = pz.zona_id
    WHERE cf.tipo_formato = 'mixto'
    AND cf.activo = 1
    GROUP BY cf.id
    HAVING total_partidos > 0
    ORDER BY c.created_at DESC
");
$formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Bracket Eliminatorio</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .formato-card {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .formato-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .criterios-desempate {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 15px;
            margin: 20px 0;
        }
        .criterios-desempate ol {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .criterios-desempate li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
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
                    <h2><i class="fas fa-sitemap"></i> Generar Bracket de Eliminación Directa</h2>
                    <a href="campeonatos_zonas.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_GET['msg']) && $_GET['msg'] === 'bracket_generado'): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Bracket generado exitosamente
                    </div>
                <?php endif; ?>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <strong>Importante:</strong> 
                    El bracket solo se puede generar cuando TODOS los partidos de la fase de zonas estén finalizados.
                    El sistema aplicará automáticamente los criterios de desempate y ordenará a los clasificados.
                </div>

                <div class="criterios-desempate">
                    <h5><i class="fas fa-balance-scale"></i> Criterios de Desempate</h5>
                    <p class="mb-2">Cuando dos o más equipos empatan en puntos, se aplican los siguientes criterios en orden:</p>
                    <ol>
                        <li><strong>Diferencia de goles</strong> (GF - GC) general</li>
                        <li><strong>Mayor cantidad de goles a favor</strong> general</li>
                        <li><strong>Mayor cantidad de puntos</strong> obtenidos en los enfrentamientos entre los equipos empatados</li>
                        <li><strong>Mayor diferencia de goles</strong> entre esos equipos</li>
                        <li><strong>Mayor cantidad de goles a favor</strong> entre esos equipos</li>
                        <li><strong>Fairplay</strong> (menos tarjetas amarillas y rojas)</li>
                    </ol>
                </div>
                
                <?php if (empty($formatos)): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-circle"></i> No hay torneos con fase de zonas disponibles
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($formatos as $formato): 
                            $completado = $formato['total_partidos'] > 0 && 
                                         $formato['total_partidos'] == $formato['partidos_completados'];
                            $porcentaje = $formato['total_partidos'] > 0 ? 
                                         ($formato['partidos_completados'] / $formato['total_partidos'] * 100) : 0;
                            $bracket_ya_generado = $formato['bracket_generado'] > 0;
                        ?>
                        <div class="col-lg-6 mb-4">
                            <div class="formato-card">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h5 class="mb-1"><?= htmlspecialchars($formato['campeonato']) ?></h5>
                                        <span class="badge bg-info">
                                            <?= $formato['cantidad_zonas'] ?> Zonas | 
                                            <?= $formato['equipos_clasifican'] ?> Clasifican
                                        </span>
                                    </div>
                                    <?php if ($bracket_ya_generado): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check"></i> Bracket Generado
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <small><strong>Progreso de partidos:</strong></small>
                                        <small><?= $formato['partidos_completados'] ?> / <?= $formato['total_partidos'] ?></small>
                                    </div>
                                    <div class="progress" style="height: 8px;">
                                        <div class="progress-bar <?= $completado ? 'bg-success' : 'bg-warning' ?>" 
                                             style="width: <?= $porcentaje ?>%"></div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center">
                                    <?php if ($completado): ?>
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle"></i> Zona completa
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-clock"></i> Pendiente
                                        </span>
                                    <?php endif; ?>

                                    <?php if ($bracket_ya_generado): ?>
                                        <a href="campeonatos_zonas_detalle.php?id=<?= $formato['id'] ?>" 
                                           class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> Ver Bracket
                                        </a>
                                    <?php elseif ($completado): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="formato_id" value="<?= $formato['id'] ?>">
                                            <button type="submit" name="generar_bracket" 
                                                    class="btn btn-primary btn-sm"
                                                    onclick="return confirm('¿Está seguro de generar el bracket eliminatorio?\n\nSe aplicarán todos los criterios de desempate y se crearán los emparejamientos.\n\nEsta acción no se puede deshacer.')">
                                                <i class="fas fa-sitemap"></i> Generar Bracket
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled>
                                            <i class="fas fa-lock"></i> No disponible
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
