<?php
require_once '../config.php';

$db = Database::getInstance()->getConnection();

// Obtener categorías activas
$stmt = $db->query("
    SELECT c.*, camp.nombre as campeonato_nombre, camp.id as campeonato_id
    FROM categorias c
    JOIN campeonatos camp ON c.campeonato_id = camp.id
    WHERE c.activa = 1 AND camp.activo = 1
    ORDER BY camp.fecha_inicio DESC, c.nombre ASC
");
$categorias = $stmt->fetchAll();

// Categoría seleccionada
$categoria_id = $_GET['categoria'] ?? ($categorias[0]['id'] ?? null);

// Verificar si tiene formato de zonas
$tiene_zonas = false;
$zonas = [];
$campeonato_id = null;
$tabla_posiciones = [];
$tablas_por_zona = [];

if ($categoria_id) {
    // Obtener campeonato_id
    foreach ($categorias as $cat) {
        if ($cat['id'] == $categoria_id) {
            $campeonato_id = $cat['campeonato_id'];
            break;
        }
    }
    
    // Verificar si tiene formato de zonas
    if ($campeonato_id) {
        $stmt = $db->prepare("SELECT id FROM campeonatos_formato WHERE campeonato_id = ? LIMIT 1");
        $stmt->execute([$campeonato_id]);
        if ($stmt->fetch()) {
            $tiene_zonas = true;
            
            // Cargar zonas
            $stmt = $db->prepare("
                SELECT z.* 
                FROM zonas z
                JOIN campeonatos_formato cf ON z.formato_id = cf.id
                WHERE cf.campeonato_id = ?
                ORDER BY z.orden
            ");
            $stmt->execute([$campeonato_id]);
            $zonas = $stmt->fetchAll();
            
            // Obtener tabla de posiciones por zona
            foreach ($zonas as $zona) {
                $stmt = $db->prepare("
                    SELECT 
                        e.id as equipo_id,
                        e.nombre as equipo,
                        e.logo,
                        ez.puntos,
                        ez.partidos_jugados,
                        ez.partidos_ganados as ganados,
                        ez.partidos_empatados as empatados,
                        ez.partidos_perdidos as perdidos,
                        ez.goles_favor,
                        ez.goles_contra,
                        ez.diferencia_gol as diferencia_goles,
                        ez.posicion,
                        ez.clasificado
                    FROM equipos_zonas ez
                    JOIN equipos e ON ez.equipo_id = e.id
                    WHERE ez.zona_id = ?
                    ORDER BY ez.posicion ASC, ez.puntos DESC, ez.diferencia_gol DESC, ez.goles_favor DESC
                ");
                $stmt->execute([$zona['id']]);
                $tablas_por_zona[$zona['id']] = [
                    'zona' => $zona,
                    'equipos' => $stmt->fetchAll()
                ];
            }
        }
    }
    
    // Si NO tiene zonas, obtener tabla normal
    if (!$tiene_zonas) {
        $stmt = $db->prepare("
            SELECT 
                e.id as equipo_id,
                e.nombre as equipo,
                e.logo,
                COUNT(p.id) as partidos_jugados,
                SUM(CASE 
                    WHEN (p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante) OR 
                         (p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local) 
                    THEN 1 ELSE 0 END) as ganados,
                SUM(CASE 
                    WHEN p.goles_local = p.goles_visitante AND p.estado = 'finalizado'
                    THEN 1 ELSE 0 END) as empatados,
                SUM(CASE 
                    WHEN (p.equipo_local_id = e.id AND p.goles_local < p.goles_visitante) OR 
                         (p.equipo_visitante_id = e.id AND p.goles_visitante < p.goles_local) 
                    THEN 1 ELSE 0 END) as perdidos,
                SUM(CASE 
                    WHEN p.equipo_local_id = e.id THEN p.goles_local 
                    WHEN p.equipo_visitante_id = e.id THEN p.goles_visitante 
                    ELSE 0 END) as goles_favor,
                SUM(CASE 
                    WHEN p.equipo_local_id = e.id THEN p.goles_visitante 
                    WHEN p.equipo_visitante_id = e.id THEN p.goles_local 
                    ELSE 0 END) as goles_contra,
                (SUM(CASE 
                    WHEN p.equipo_local_id = e.id THEN p.goles_local 
                    WHEN p.equipo_visitante_id = e.id THEN p.goles_visitante 
                    ELSE 0 END) - SUM(CASE 
                    WHEN p.equipo_local_id = e.id THEN p.goles_visitante 
                    WHEN p.equipo_visitante_id = e.id THEN p.goles_local 
                    ELSE 0 END)) as diferencia_goles,
                (SUM(CASE 
                    WHEN (p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante) OR 
                         (p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local) 
                    THEN 3
                    WHEN p.goles_local = p.goles_visitante AND p.estado = 'finalizado'
                    THEN 1 ELSE 0 END)) as puntos
            FROM equipos e
            LEFT JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id) 
                                  AND p.estado = 'finalizado'
            WHERE e.categoria_id = ? AND e.activo = 1
            GROUP BY e.id, e.nombre, e.logo
            ORDER BY puntos DESC, diferencia_goles DESC, goles_favor DESC, e.nombre ASC
        ");
        $stmt->execute([$categoria_id]);
        $tabla_posiciones = $stmt->fetchAll();
    }
}

// Obtener información de la categoría seleccionada
$categoria_actual = null;
foreach ($categorias as $cat) {
    if ($cat['id'] == $categoria_id) {
        $categoria_actual = $cat;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tabla de Posiciones</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .zona-card {
            margin-bottom: 2rem;
        }
        .zona-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem;
            border-radius: 8px 8px 0 0;
        }
        .clasificado-badge {
            background-color: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
        }
        .position-number {
            display: inline-block;
            width: 30px;
            height: 30px;
            line-height: 30px;
            text-align: center;
            border-radius: 50%;
            font-weight: bold;
        }
        .position-1 {
            background-color: #FFD700;
            color: #000;
        }
        .position-2 {
            background-color: #C0C0C0;
            color: #000;
        }
        .position-3 {
            background-color: #CD7F32;
            color: #fff;
        }
        .position-champion {
            background-color: #28a745;
            color: #fff;
        }
    </style>
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
                    <li class="nav-item"><a class="nav-link" href="resultados.php">Resultados</a></li>
                    <li class="nav-item"><a class="nav-link active" href="tablas.php">Posiciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="goleadores.php">Goleadores</a></li>
                    <li class="nav-item"><a class="nav-link" href="fixture.php">Fixture</a></li>
                    <li class="nav-item"><a class="nav-link" href="sanciones.php">Sanciones</a></li>
                    <li class="nav-item"><a class="nav-link" href="historial_equipos.php">Equipos</a></li>
                    <li class="nav-item"><a class="nav-link" href="fairplay.php">Fairplay</a></li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item"><a class="nav-link" href="../admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Panel Admin</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Salir</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="../login.php"><i class="fas fa-sign-in-alt"></i> Ingresar</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-list"></i> Tablas de Posiciones</h2>                    
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

                    <?php if ($categoria_actual): ?>
                        <?php if ($tiene_zonas && !empty($tablas_por_zona)): ?>
                            <!-- TABLAS POR ZONA -->
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle"></i> <strong>Torneo con zonas:</strong> Los equipos compiten dentro de sus respectivas zonas. Los mejores clasificados avanzan a la fase eliminatoria.
                            </div>
                            
                            <?php foreach ($tablas_por_zona as $zona_id => $zona_data): ?>
                                <div class="zona-card">
                                    <div class="zona-header">
                                        <h4 class="mb-0">
                                            <i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($zona_data['zona']['nombre']); ?>
                                        </h4>
                                    </div>
                                    <div class="card">
                                        <div class="card-body p-0">
                                            <?php if (empty($zona_data['equipos'])): ?>
                                                <div class="p-4 text-center">
                                                    <i class="fas fa-list fa-3x text-muted mb-3"></i>
                                                    <h5 class="text-muted">No hay equipos en esta zona</h5>
                                                </div>
                                            <?php else: ?>
                                                <div class="table-responsive">
                                                    <table class="table table-hover mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th class="text-center">Pos</th>
                                                                <th>Equipo</th>
                                                                <th class="text-center">PJ</th>
                                                                <th class="text-center">G</th>
                                                                <th class="text-center">E</th>
                                                                <th class="text-center">P</th>
                                                                <th class="text-center">GF</th>
                                                                <th class="text-center">GC</th>
                                                                <th class="text-center">DG</th>
                                                                <th class="text-center">Pts</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($zona_data['equipos'] as $index => $equipo): ?>
                                                                <?php
                                                                $posicion = $index + 1;
                                                                $clase_posicion = '';
                                                                if ($posicion == 1) $clase_posicion = 'position-1';
                                                                elseif ($posicion == 2) $clase_posicion = 'position-2';
                                                                elseif ($posicion <= 4) $clase_posicion = 'position-champion';
                                                                ?>
                                                                <tr class="<?php echo $equipo['clasificado'] ? 'table-success' : ''; ?>">
                                                                    <td class="text-center">
                                                                        <span class="position-number <?php echo $clase_posicion; ?>">
                                                                            <?php echo $posicion; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <?php if ($equipo['logo']): ?>
                                                                                <img src="../uploads/<?php echo htmlspecialchars($equipo['logo']); ?>" 
                                                                                     alt="Logo" class="me-2" width="30" height="30" 
                                                                                     style="object-fit: cover; border-radius: 50%;">
                                                                            <?php else: ?>
                                                                                <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                                                     style="width: 30px; height: 30px;">
                                                                                    <i class="fas fa-shield-alt text-white"></i>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <strong><?php echo htmlspecialchars($equipo['equipo']); ?></strong>
                                                                            <?php if ($equipo['clasificado']): ?>
                                                                                <span class="clasificado-badge ms-2">CLASIFICADO</span>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-center"><?php echo $equipo['partidos_jugados']; ?></td>
                                                                    <td class="text-center text-success fw-bold"><?php echo $equipo['ganados']; ?></td>
                                                                    <td class="text-center text-warning fw-bold"><?php echo $equipo['empatados']; ?></td>
                                                                    <td class="text-center text-danger fw-bold"><?php echo $equipo['perdidos']; ?></td>
                                                                    <td class="text-center"><?php echo $equipo['goles_favor']; ?></td>
                                                                    <td class="text-center"><?php echo $equipo['goles_contra']; ?></td>
                                                                    <td class="text-center">
                                                                        <span class="<?php echo $equipo['diferencia_goles'] > 0 ? 'text-success' : ($equipo['diferencia_goles'] < 0 ? 'text-danger' : ''); ?>">
                                                                            <?php echo $equipo['diferencia_goles'] > 0 ? '+' : ''; ?><?php echo $equipo['diferencia_goles']; ?>
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-center">
                                                                        <span class="badge bg-primary fs-6"><?php echo $equipo['puntos']; ?></span>
                                                                    </td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- TABLA NORMAL (SIN ZONAS) -->
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h4 class="mb-0">
                                        <i class="fas fa-trophy"></i> 
                                        <?php echo htmlspecialchars($categoria_actual['campeonato_nombre'] . ' - ' . $categoria_actual['nombre']); ?>
                                    </h4>
                                </div>
                                <div class="card-body p-0">
                                    <?php if (empty($tabla_posiciones)): ?>
                                        <div class="p-4 text-center">
                                            <i class="fas fa-list fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">No hay equipos en esta categoría</h5>
                                        </div>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center">Pos</th>
                                                        <th>Equipo</th>
                                                        <th class="text-center">PJ</th>
                                                        <th class="text-center">G</th>
                                                        <th class="text-center">E</th>
                                                        <th class="text-center">P</th>
                                                        <th class="text-center">GF</th>
                                                        <th class="text-center">GC</th>
                                                        <th class="text-center">DG</th>
                                                        <th class="text-center">Pts</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($tabla_posiciones as $index => $equipo): ?>
                                                        <?php
                                                        $posicion = $index + 1;
                                                        $clase_posicion = '';
                                                        if ($posicion == 1) $clase_posicion = 'position-1';
                                                        elseif ($posicion == 2) $clase_posicion = 'position-2';
                                                        elseif ($posicion == 3) $clase_posicion = 'position-3';
                                                        elseif ($posicion <= 4) $clase_posicion = 'position-champion';
                                                        ?>
                                                        <tr>
                                                            <td class="text-center">
                                                                <span class="position-number <?php echo $clase_posicion; ?>">
                                                                    <?php echo $posicion; ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if ($equipo['logo']): ?>
                                                                        <img src="../uploads/<?php echo htmlspecialchars($equipo['logo']); ?>" 
                                                                             alt="Logo" class="me-2" width="30" height="30" 
                                                                             style="object-fit: cover; border-radius: 50%;">
                                                                    <?php else: ?>
                                                                        <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                                             style="width: 30px; height: 30px;">
                                                                            <i class="fas fa-shield-alt text-white"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <strong><?php echo htmlspecialchars($equipo['equipo']); ?></strong>
                                                                </div>
                                                            </td>
                                                            <td class="text-center"><?php echo $equipo['partidos_jugados']; ?></td>
                                                            <td class="text-center text-success fw-bold"><?php echo $equipo['ganados']; ?></td>
                                                            <td class="text-center text-warning fw-bold"><?php echo $equipo['empatados']; ?></td>
                                                            <td class="text-center text-danger fw-bold"><?php echo $equipo['perdidos']; ?></td>
                                                            <td class="text-center"><?php echo $equipo['goles_favor']; ?></td>
                                                            <td class="text-center"><?php echo $equipo['goles_contra']; ?></td>
                                                            <td class="text-center">
                                                                <span class="<?php echo $equipo['diferencia_goles'] > 0 ? 'text-success' : ($equipo['diferencia_goles'] < 0 ? 'text-danger' : ''); ?>">
                                                                    <?php echo $equipo['diferencia_goles'] > 0 ? '+' : ''; ?><?php echo $equipo['diferencia_goles']; ?>
                                                                </span>
                                                            </td>
                                                            <td class="text-center">
                                                                <span class="badge bg-primary fs-6"><?php echo $equipo['puntos']; ?></span>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Leyenda -->
                        <div class="card mt-3">
                            <div class="card-body">
                                <h6><i class="fas fa-info-circle"></i> Leyenda:</h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small>
                                            <span class="position-number position-1 me-1">1</span> <?php echo $tiene_zonas ? '1° Puesto' : 'Campeón'; ?><br>
                                            <span class="position-number position-2 me-1">2</span> <?php echo $tiene_zonas ? '2° Puesto' : 'Subcampeón'; ?><br>
                                            <?php if (!$tiene_zonas): ?>
                                            <span class="position-number position-3 me-1">3</span> Tercer puesto
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small>
                                            <strong>PJ:</strong> Partidos Jugados &nbsp;
                                            <strong>G:</strong> Ganados &nbsp;
                                            <strong>E:</strong> Empatados &nbsp;
                                            <strong>P:</strong> Perdidos<br>
                                            <strong>GF:</strong> Goles a Favor &nbsp;
                                            <strong>GC:</strong> Goles en Contra &nbsp;
                                            <strong>DG:</strong> Diferencia de Goles &nbsp;
                                            <strong>Pts:</strong> Puntos
                                        </small>
                                    </div>
                                </div>
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
                    <p class="text-muted">Gestión completa de torneos de fútbol</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">© 2025 Todos los derechos reservados</p>
                    <small class="text-muted">Actualizado: <?php echo date('d/m/Y H:i'); ?></small>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function cambiarCategoria(categoriaId) {
            window.location.href = 'tablas.php?categoria=' + categoriaId;
        }

        // Auto actualización cada 2 minutos
        setInterval(function() {
            location.reload();
        }, 120000);
    </script>
</body>
</html>