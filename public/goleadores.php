<?php
require_once '../config.php';

$db = Database::getInstance()->getConnection();

// Obtener categorías activas
$stmt = $db->query("
    SELECT c.*, camp.nombre as campeonato_nombre
    FROM categorias c
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE c.activa = 1 AND camp.activo = 1
    ORDER BY camp.fecha_inicio DESC, c.nombre ASC
");
$categorias = $stmt->fetchAll();

// Categoría seleccionada
$categoria_id = $_GET['categoria'] ?? ($categorias[0]['id'] ?? null);

// Obtener tabla de goleadores - LIMITADO A TOP 10
$goleadores = [];
if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT 
            j.id as jugador_id,
            j.apellido_nombre,
            j.dni,
            j.fecha_nacimiento,
            j.foto,
            e.nombre as equipo,
            e.logo as equipo_logo,
            e.color_camiseta,
            COUNT(ev.id) as goles,
            GROUP_CONCAT(
                CONCAT(
                    DATE_FORMAT(p.fecha_partido, '%d/%m'), ' vs ',
                    CASE 
                        WHEN p.equipo_local_id = e.id THEN ev_oponente.nombre 
                        ELSE el_oponente.nombre 
                    END
                ) 
                ORDER BY p.fecha_partido DESC 
                SEPARATOR '|'
            ) as partidos_gol
        FROM jugadores j
        JOIN equipos e ON j.equipo_id = e.id
        LEFT JOIN eventos_partido ev ON j.id = ev.jugador_id AND ev.tipo_evento = 'gol'
        LEFT JOIN partidos p ON ev.partido_id = p.id AND p.estado = 'finalizado'
        LEFT JOIN equipos el_oponente ON p.equipo_local_id = el_oponente.id AND p.equipo_visitante_id = e.id
        LEFT JOIN equipos ev_oponente ON p.equipo_visitante_id = ev_oponente.id AND p.equipo_local_id = e.id
        WHERE e.categoria_id = ? AND j.activo = 1
        GROUP BY j.id, j.apellido_nombre, j.dni, j.fecha_nacimiento, j.foto, e.nombre, e.logo, e.color_camiseta
        HAVING goles > 0
        ORDER BY goles DESC, j.apellido_nombre ASC
        LIMIT 10
    ");
    $stmt->execute([$categoria_id]);
    $goleadores = $stmt->fetchAll();
}

// Obtener información de la categoría seleccionada
$categoria_actual = null;
foreach ($categorias as $cat) {
    if ($cat['id'] == $categoria_id) {
        $categoria_actual = $cat;
        break;
    }
}

// Estadísticas generales
$stats = [];
if ($categoria_id) {
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT j.id) as total_jugadores,
            COUNT(DISTINCT CASE WHEN ev.tipo_evento = 'gol' THEN j.id END) as jugadores_goleadores,
            COUNT(CASE WHEN ev.tipo_evento = 'gol' THEN 1 END) as total_goles,
            COUNT(DISTINCT p.id) as partidos_jugados,
            ROUND(COUNT(CASE WHEN ev.tipo_evento = 'gol' THEN 1 END) / COUNT(DISTINCT CASE WHEN p.estado = 'finalizado' THEN p.id END), 2) as promedio_goles_partido
        FROM equipos e
        JOIN jugadores j ON e.id = j.equipo_id
        LEFT JOIN eventos_partido ev ON j.id = ev.jugador_id
        LEFT JOIN partidos p ON ev.partido_id = p.id
        WHERE e.categoria_id = ? AND j.activo = 1
    ");
    $stmt->execute([$categoria_id]);
    $stats = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top 10 Goleadores</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-futbol"></i> Fútbol Manager
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="resultados.php">Resultados</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="tablas.php">Posiciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="goleadores.php">Goleadores</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="fixture.php">Fixture</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="sanciones.php">Sanciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="historial_equipos.php">Equipos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="fairplay.php">Fairplay</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Panel Admin
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Salir
                            </a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt"></i> Ingresar
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-trophy"></i> Top 10 Goleadores</h2>
                </div>

                <?php if (empty($categorias)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No hay categorías disponibles en este momento.
                    </div>
                <?php else: ?>
                    <!-- Selector de Categoría -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-6">
                                    <h5 class="mb-0">Seleccionar Categoría:</h5>
                                </div>
                                <div class="col-md-6">
                                    <select class="form-select" onchange="cambiarCategoria(this.value)">
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>" 
                                                    <?php echo $cat['id'] == $categoria_id ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['campeonato_nombre'] . ' - ' . $cat['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($categoria_actual && !empty($stats)): ?>
                        <!-- Estadísticas -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-2">
                                        <div class="stats-card">
                                            <div class="icon text-success">
                                                <i class="fas fa-futbol"></i>
                                            </div>
                                            <div class="number text-success"><?php echo $stats['total_goles'] ?: 0; ?></div>
                                            <div class="label">Total Goles</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="stats-card">
                                            <div class="icon text-primary">
                                                <i class="fas fa-user-friends"></i>
                                            </div>
                                            <div class="number text-primary"><?php echo $stats['jugadores_goleadores'] ?: 0; ?></div>
                                            <div class="label">Goleadores</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="stats-card">
                                            <div class="icon text-info">
                                                <i class="fas fa-users"></i>
                                            </div>
                                            <div class="number text-info"><?php echo $stats['total_jugadores'] ?: 0; ?></div>
                                            <div class="label">Jugadores</div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="stats-card">
                                            <div class="icon text-warning">
                                                <i class="fas fa-calendar-check"></i>
                                            </div>
                                            <div class="number text-warning"><?php echo $stats['partidos_jugados'] ?: 0; ?></div>
                                            <div class="label">Partidos</div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="stats-card">
                                            <div class="icon text-secondary">
                                                <i class="fas fa-chart-line"></i>
                                            </div>
                                            <div class="number text-secondary"><?php echo $stats['promedio_goles_partido'] ?: '0.00'; ?></div>
                                            <div class="label">Goles por Partido</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Podio Top 3 -->
                        <?php if (count($goleadores) >= 3): ?>
                        <div class="row mb-4">
                            <!-- 2do Lugar -->
                            <div class="col-md-4">
                                <div class="card h-100" style="background: linear-gradient(135deg, #C0C0C0, #A8A8A8);">
                                    <div class="card-body text-center text-dark">
                                        <div class="mb-3">
                                            <i class="fas fa-medal" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 class="mb-2">🥈 2do Lugar</h5>
                                        <?php if ($goleadores[1]['foto']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($goleadores[1]['foto']); ?>" 
                                                 alt="Foto" class="rounded-circle mb-2" width="80" height="80" 
                                                 style="object-fit: cover; border: 3px solid #fff;">
                                        <?php endif; ?>
                                        <h4><?php echo htmlspecialchars($goleadores[1]['apellido_nombre']); ?></h4>
                                        <p class="mb-1"><?php echo htmlspecialchars($goleadores[1]['equipo']); ?></p>
                                        <h2 class="mb-0">
                                            <i class="fas fa-futbol"></i> <?php echo $goleadores[1]['goles']; ?>
                                        </h2>
                                    </div>
                                </div>
                            </div>

                            <!-- 1er Lugar -->
                            <div class="col-md-4">
                                <div class="card h-100 top-goleador-card" style="margin-top: -20px;">
                                    <div class="card-body text-center">
                                        <div class="mb-3">
                                            <i class="fas fa-crown" style="font-size: 4rem;"></i>
                                        </div>
                                        <h4 class="mb-2">👑 Pichichi</h4>
                                        <?php if ($goleadores[0]['foto']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($goleadores[0]['foto']); ?>" 
                                                 alt="Foto" class="rounded-circle mb-2" width="100" height="100" 
                                                 style="object-fit: cover; border: 4px solid #fff;">
                                        <?php endif; ?>
                                        <h3><?php echo htmlspecialchars($goleadores[0]['apellido_nombre']); ?></h3>
                                        <p class="mb-1"><?php echo htmlspecialchars($goleadores[0]['equipo']); ?></p>
                                        <h1 class="mb-0">
                                            <i class="fas fa-futbol"></i> <?php echo $goleadores[0]['goles']; ?>
                                        </h1>
                                    </div>
                                </div>
                            </div>

                            <!-- 3er Lugar -->
                            <div class="col-md-4">
                                <div class="card h-100" style="background: linear-gradient(135deg, #CD7F32, #8B4513);">
                                    <div class="card-body text-center text-white">
                                        <div class="mb-3">
                                            <i class="fas fa-medal" style="font-size: 3rem;"></i>
                                        </div>
                                        <h5 class="mb-2">🥉 3er Lugar</h5>
                                        <?php if ($goleadores[2]['foto']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($goleadores[2]['foto']); ?>" 
                                                 alt="Foto" class="rounded-circle mb-2" width="80" height="80" 
                                                 style="object-fit: cover; border: 3px solid #fff;">
                                        <?php endif; ?>
                                        <h4><?php echo htmlspecialchars($goleadores[2]['apellido_nombre']); ?></h4>
                                        <p class="mb-1"><?php echo htmlspecialchars($goleadores[2]['equipo']); ?></p>
                                        <h2 class="mb-0">
                                            <i class="fas fa-futbol"></i> <?php echo $goleadores[2]['goles']; ?>
                                        </h2>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Tabla Top 10 -->
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h4 class="mb-0">
                                    <i class="fas fa-list-ol"></i> 
                                    Top 10 - <?php echo htmlspecialchars($categoria_actual['campeonato_nombre'] . ' - ' . $categoria_actual['nombre']); ?>
                                </h4>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th class="text-center" style="width: 10%;">Pos</th>
                                            <th style="width: 45%;">Jugador</th>
                                            <th style="width: 30%;">Equipo</th>
                                            <th class="text-center" style="width: 15%;">Goles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $posicion_anterior = 0;
                                        $goles_anterior = -1;
                                        foreach ($goleadores as $index => $goleador): 
                                            if ($goleador['goles'] != $goles_anterior) {
                                                $posicion_anterior = $index + 1;
                                                $goles_anterior = $goleador['goles'];
                                            }
                                            
                                            $badge_class = 'position-other';
                                            if ($posicion_anterior == 1) $badge_class = 'position-1';
                                            elseif ($posicion_anterior == 2) $badge_class = 'position-2';
                                            elseif ($posicion_anterior == 3) $badge_class = 'position-3';
                                        ?>
                                        <tr class="goleador-row" style="background-color: <?php echo $index % 2 == 0 ? '#f9f9f9' : '#ffffff'; ?>;">
                                            <td class="text-center align-middle">
                                                <div class="position-badge <?php echo $badge_class; ?>">
                                                    <?php echo $posicion_anterior; ?>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($goleador['foto']): ?>
                                                        <img src="../uploads/<?php echo htmlspecialchars($goleador['foto']); ?>" 
                                                             alt="Foto" class="me-2 rounded-circle" width="45" height="45" 
                                                             style="object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                             style="width: 45px; height: 45px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <strong><?php echo htmlspecialchars($goleador['apellido_nombre']); ?></strong>
                                                </div>
                                            </td>
                                            <td class="align-middle">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($goleador['equipo_logo']): ?>
                                                        <img src="../uploads/<?php echo htmlspecialchars($goleador['equipo_logo']); ?>" 
                                                             alt="Logo" class="me-2 rounded" width="30" height="30" style="object-fit: cover;">
                                                    <?php else: ?>
                                                        <div class="rounded me-2 d-flex align-items-center justify-content-center" 
                                                             style="width: 30px; height: 30px; background-color: <?php echo $goleador['color_camiseta'] ?: '#6c757d'; ?>;">
                                                            <i class="fas fa-shield-alt text-white" style="font-size: 0.8rem;"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <span><?php echo htmlspecialchars($goleador['equipo']); ?></span>
                                                </div>
                                            </td>
                                            <td class="text-center align-middle">
                                                <span class="badge bg-success" style="font-size: 1rem; padding: 8px 12px;">
                                                    <i class="fas fa-futbol"></i> <?php echo $goleador['goles']; ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($goleadores)): ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4">
                                                <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                                                <p class="text-muted mb-0">No hay goleadores registrados aún</p>
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-futbol"></i> Sistema de Campeonatos</h5>
                    <p class="text-muted">Top 10 Goleadores actualizado en tiempo real</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">© 2024 Todos los derechos reservados</p>
                    <small class="text-muted">Actualizado: <?php echo date('d/m/Y H:i'); ?></small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function cambiarCategoria(categoriaId) {
            window.location.href = 'goleadores.php?categoria=' + categoriaId;
        }

        // Auto actualización cada 2 minutos
        setInterval(function() {
            location.reload();
        }, 120000);
    </script>
</body>
</html>