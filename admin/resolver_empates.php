<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/include/desempate_functions.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$formato_id = $_GET['formato_id'] ?? null;

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
$formato = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$formato) {
    redirect('campeonatos_zonas.php');
}

// Procesar resolución de empate
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolver_empate'])) {
    $empate_id = $_POST['empate_id'] ?? null;
    $equipo_ganador_id = $_POST['equipo_ganador_id'] ?? null;
    
    if (!$empate_id || !$equipo_ganador_id) {
        $error = 'Debe seleccionar un equipo ganador';
    } else {
        try {
            resolverEmpatePendiente($empate_id, $equipo_ganador_id, $db);
            $message = 'Empate resuelto exitosamente. El equipo seleccionado ha sido designado como ganador.';
            
            // Recalcular tablas después de resolver el empate
            $stmt = $db->prepare("SELECT id FROM zonas WHERE formato_id = ?");
            $stmt->execute([$formato_id]);
            $zonas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($zonas as $zona_id) {
                calcularTablaPosicionesConDesempate($zona_id, $db);
            }
        } catch (Exception $e) {
            $error = 'Error al resolver empate: ' . $e->getMessage();
        }
    }
}

// Recalcular todas las tablas para detectar empates pendientes
$stmt = $db->prepare("SELECT id FROM zonas WHERE formato_id = ?");
$stmt->execute([$formato_id]);
$zonas = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($zonas as $zona_id) {
    calcularTablaPosicionesConDesempate($zona_id, $db);
}

// Obtener empates pendientes
$empates_pendientes = obtenerEmpatesPendientes($formato_id, $db);

// Verificar si todos los partidos están finalizados
$todos_finalizados = todosPartidosGruposFinalizados($formato_id, $db);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolver Empates - Sistema de Campeonatos</title>
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
                <a class="nav-link" href="campeonatos_zonas.php">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
                <a class="nav-link" href="<?php echo SITE_URL; ?>logout.php">
                    <i class="fas fa-sign-out-alt"></i> Salir
                </a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-random"></i> Resolver Empates por Sorteo</h2>
                <p class="text-muted">Campeonato: <strong><?php echo htmlspecialchars($formato['campeonato_nombre']); ?></strong></p>
                
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

                <?php if (!$todos_finalizados): ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>Atención:</strong> Aún hay partidos de grupos pendientes. Los empates se detectarán automáticamente cuando todos los partidos estén finalizados.
                    </div>
                <?php endif; ?>

                <?php if (empty($empates_pendientes)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        <strong>¡Excelente!</strong> No hay empates pendientes de resolución. Todos los criterios de desempate fueron suficientes para determinar las posiciones.
                        <?php if ($todos_finalizados): ?>
                            <br><br>
                            <a href="generar_eliminatorias.php?formato_id=<?php echo $formato_id; ?>" class="btn btn-success">
                                <i class="fas fa-trophy"></i> Generar Fases Eliminatorias
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Hay <?php echo count($empates_pendientes); ?> empate(s) pendiente(s) de resolución.</strong>
                        <br>Después de aplicar todos los criterios de desempate (diferencia de goles, goles a favor, enfrentamientos directos, fairplay), 
                        estos equipos siguen empatados. Debes seleccionar manualmente qué equipo pasa de fase mediante sorteo.
                    </div>

                    <?php foreach ($empates_pendientes as $empate): ?>
                        <div class="card mb-4">
                            <div class="card-header bg-warning">
                                <h5 class="mb-0">
                                    <i class="fas fa-random"></i> Empate en <?php echo htmlspecialchars($empate['zona_nombre']); ?> - Posición <?php echo $empate['posicion']; ?>°
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">
                                    <strong>Criterios aplicados:</strong> Diferencia de goles, Goles a favor, Enfrentamientos directos, Fairplay
                                </p>
                                
                                <div class="row mb-3">
                                    <?php 
                                    $equipos_ids = is_array($empate['equipos_ids']) ? $empate['equipos_ids'] : json_decode($empate['equipos_ids'], true);
                                    $equipos_nombres = is_array($empate['equipos_nombres']) ? $empate['equipos_nombres'] : json_decode($empate['equipos_nombres'], true);
                                    ?>
                                    <?php foreach ($equipos_ids as $idx => $equipo_id): ?>
                                        <?php
                                        // Obtener información del equipo
                                        $stmt_eq = $db->prepare("
                                            SELECT e.*, ez.puntos, ez.diferencia_gol, ez.goles_favor, ez.goles_contra
                                            FROM equipos e
                                            INNER JOIN equipos_zonas ez ON e.id = ez.equipo_id
                                            WHERE e.id = ? AND ez.zona_id = ?
                                        ");
                                        $stmt_eq->execute([$equipo_id, $empate['zona_id']]);
                                        $equipo_info = $stmt_eq->fetch(PDO::FETCH_ASSOC);
                                        ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100 equipo-card" data-equipo-id="<?php echo $equipo_id; ?>">
                                                <div class="card-body text-center">
                                                    <?php if ($equipo_info && $equipo_info['logo']): ?>
                                                        <img src="<?php echo htmlspecialchars($equipo_info['logo']); ?>" 
                                                             alt="<?php echo htmlspecialchars($equipos_nombres[$idx]); ?>" 
                                                             class="img-fluid mb-2" style="max-height: 80px;">
                                                    <?php endif; ?>
                                                    <h5><?php echo htmlspecialchars($equipos_nombres[$idx]); ?></h5>
                                                    <div class="text-muted small">
                                                        <div>Puntos: <strong><?php echo $equipo_info['puntos'] ?? 0; ?></strong></div>
                                                        <div>Dif: <strong><?php echo $equipo_info['diferencia_gol'] ?? 0; ?></strong></div>
                                                        <div>GF: <strong><?php echo $equipo_info['goles_favor'] ?? 0; ?></strong></div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <form method="POST" class="resolver-empate-form">
                                    <input type="hidden" name="empate_id" value="<?php echo $empate['id']; ?>">
                                    <input type="hidden" name="equipo_ganador_id" id="equipo_ganador_<?php echo $empate['id']; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">
                                            <strong>Selecciona el equipo ganador por sorteo:</strong>
                                        </label>
                                        <select class="form-select" name="equipo_ganador_select" required 
                                                onchange="document.getElementById('equipo_ganador_<?php echo $empate['id']; ?>').value = this.value;">
                                            <option value="">-- Selecciona un equipo --</option>
                                            <?php foreach ($equipos_ids as $idx => $equipo_id): ?>
                                                <option value="<?php echo $equipo_id; ?>">
                                                    <?php echo htmlspecialchars($equipos_nombres[$idx]); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" name="resolver_empate" class="btn btn-success">
                                        <i class="fas fa-check"></i> Resolver Empate
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Resaltar equipo seleccionado
        document.querySelectorAll('.equipo-card').forEach(card => {
            card.addEventListener('click', function() {
                const form = this.closest('.resolver-empate-form');
                const equipoId = this.dataset.equipoId;
                const hiddenInput = form.querySelector('input[name="equipo_ganador_id"]');
                const select = form.querySelector('select[name="equipo_ganador_select"]');
                
                // Desmarcar otros equipos
                form.querySelectorAll('.equipo-card').forEach(c => {
                    c.classList.remove('border-success', 'border-3');
                });
                
                // Marcar equipo seleccionado
                this.classList.add('border-success', 'border-3');
                
                // Actualizar select e input
                select.value = equipoId;
                hiddenInput.value = equipoId;
            });
        });
    </script>
    <style>
        .equipo-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        .equipo-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .equipo-card.border-success {
            background-color: #d4edda;
        }
    </style>
</body>
</html>

