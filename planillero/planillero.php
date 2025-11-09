<?php
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('planillero')) {
    redirect('../login.php');
}

// Verificar código de cancha activo
if (!isset($_SESSION['codigo_cancha_activo'])) {
    redirect('index.php');
}

$db = Database::getInstance()->getConnection();
$user = getCurrentUser();
$codigo_activo = $_SESSION['codigo_cancha_activo'];

// Verificar validez del código
$stmt = $db->prepare("
    SELECT cc.*, c.nombre as cancha_nombre
    FROM codigos_cancha cc
    JOIN canchas c ON cc.cancha_id = c.id
    WHERE cc.id = ? AND cc.activo = 1 AND (cc.expires_at IS NULL OR NOW() <= cc.expires_at)
");
$stmt->execute([$codigo_activo['id']]);
$verificacion = $stmt->fetch();

if (!$verificacion) {
    unset($_SESSION['codigo_cancha_activo']);
    redirect('index.php');
}

// Obtener partidos de la cancha
$stmt = $db->prepare("
    SELECT p.*, 
           el.nombre as equipo_local, ev.nombre as equipo_visitante,
           c.nombre as cancha_nombre, cat.nombre as categoria,
           f.numero_fecha
    FROM partidos p
    JOIN equipos el ON p.equipo_local_id = el.id
    JOIN equipos ev ON p.equipo_visitante_id = ev.id
    JOIN canchas c ON p.cancha_id = c.id
    JOIN fechas f ON p.fecha_id = f.id
    JOIN categorias cat ON f.categoria_id = cat.id
    WHERE p.cancha_id = ? AND DATE(p.fecha_partido) = ?
    ORDER BY p.hora_partido ASC
");
$stmt->execute([$codigo_activo['cancha_id'], $codigo_activo['fecha_partidos']]);
$partidos = $stmt->fetchAll();

// Verificar si debe cerrar sesión automáticamente
$debe_redirigir_a_codigo = false;
if (!empty($partidos)) {
    $todos_finalizados = true;
    $ultimo_finalizado = null;
    
    foreach ($partidos as $partido) {
        if ($partido['estado'] != 'finalizado') {
            $todos_finalizados = false;
            break;
        } else {
            if (!$ultimo_finalizado || $partido['finalizado_at'] > $ultimo_finalizado) {
                $ultimo_finalizado = $partido['finalizado_at'];
            }
        }
    }
    
    if ($todos_finalizados && $ultimo_finalizado) {
        $tiempo_limite = new DateTime($ultimo_finalizado);
        $tiempo_limite->add(new DateInterval('PT10M'));
        $ahora = new DateTime();
        
        if ($ahora > $tiempo_limite) {
            unset($_SESSION['codigo_cancha_activo']);
            $debe_redirigir_a_codigo = true;
        }
    }
}

// Redirección segura si se cumplen condiciones de cierre
if ($debe_redirigir_a_codigo) {
    redirect('index.php?mensaje=sesion_finalizada');
}

// Mensaje de éxito
$mensaje_exito = '';
if (isset($_GET['mensaje'])) {
    if ($_GET['mensaje'] == 'partido_finalizado') {
        $mensaje_exito = 'Partido finalizado correctamente';
        if (isset($_GET['sanciones_cumplidas'])) {
            $mensaje_exito .= '. Se actualizaron las sanciones automáticamente.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planillero - <?= htmlspecialchars($codigo_activo['cancha_nombre']) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .header-cancha {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.2);
        }
        .partido-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border-left: 5px solid #007bff;
        }
        .partido-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        .partido-programado { 
            border-left-color: #6c757d; 
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }
        .partido-en_curso { 
            border-left-color: #dc3545; 
            background: linear-gradient(135deg, #fff5f5 0%, #ffebee 100%);
            animation: pulse-border 2s infinite;
        }
        .partido-finalizado { 
            border-left-color: #28a745;
            background: linear-gradient(135deg, #f0fff4 0%, #e8f5e8 100%);
        }
        @keyframes pulse-border {
            0% { box-shadow: 0 5px 15px rgba(220, 53, 69, 0.1); }
            50% { box-shadow: 0 10px 30px rgba(220, 53, 69, 0.3); }
            100% { box-shadow: 0 5px 15px rgba(220, 53, 69, 0.1); }
        }
        .btn-iniciar {
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: bold;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }
        .btn-iniciar:hover {
            transform: translateY(-2px);
        }
        .marcador {
            font-size: 2rem;
            font-weight: bold;
        }
        .estado-badge {
            font-size: 0.9rem;
            padding: 8px 15px;
            border-radius: 20px;
        }
        .tiempo-restante {
            font-size: 1.1rem;
            font-weight: bold;
        }
        .cronometro-live {
            font-family: 'Courier New', monospace;
            font-size: 1.2rem;
            font-weight: bold;
            color: #dc3545;
        }

        /* --- Ajustes responsive móvil --- */
        @media (max-width: 576px) {
            .header-cancha { padding: 16px; border-radius: 12px; }
            .header-cancha h2 { font-size: 1.1rem; margin-bottom: 6px; }
            .header-cancha .row > div h6 { font-size: 0.8rem; }
            .header-cancha .row > div p { font-size: 0.9rem; }
            .partido-card { margin-bottom: 14px; border-radius: 12px; }
            .marcador { font-size: 1.6rem; }
            .estado-badge { font-size: 0.8rem; padding: 6px 10px; }
            .btn-iniciar { width: 100%; padding: 10px 14px; font-size: 0.95rem; }
            .cronometro-live { font-size: 1rem; }
            .card-body .row > [class^="col-"] { margin-top: 8px; }
        }
    </style>
</head>
<body>
    <div class="container my-4">
        <!-- Header con info de la cancha -->
        <div class="header-cancha text-center">
            <h2><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($codigo_activo['cancha_nombre']) ?></h2>
            <div class="row mt-3">
                <div class="col-4">
                    <h6>Fecha</h6>
                    <p class="mb-0"><?= date('d/m/Y', strtotime($codigo_activo['fecha_partidos'])) ?></p>
                </div>
                <div class="col-4">
                    <h6>Código</h6>
                    <p class="mb-0 fw-bold"><?= $codigo_activo['codigo'] ?></p>
                </div>
                <div class="col-4">
                    <h6>Válido hasta</h6>
                    <p class="mb-0 tiempo-restante" id="countdown"><?= date('H:i', strtotime($codigo_activo['expires_at'])) ?></p>
                </div>
            </div>
            <div class="mt-3">
                <a href="index.php" class="btn btn-light">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>

        <?php if ($mensaje_exito): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($mensaje_exito) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (empty($partidos)): ?>
            <div class="text-center py-5">
                <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                <h4>No hay partidos programados</h4>
                <p class="text-muted">No se encontraron partidos para esta cancha en la fecha seleccionada.</p>
                <a href="index.php" class="btn btn-outline-primary mt-3">
                    <i class="fas fa-key"></i> Ingresar Nuevo Código
                </a>
            </div>
        <?php else: ?>
            <!-- Contador de partidos -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-0">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-3">
                                    <h4 class="text-primary"><?= count($partidos) ?></h4>
                                    <small>Total Partidos</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-success">
                                        <?= count(array_filter($partidos, fn($p) => $p['estado'] == 'finalizado')) ?>
                                    </h4>
                                    <small>Finalizados</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-danger">
                                        <?= count(array_filter($partidos, fn($p) => $p['estado'] == 'en_curso')) ?>
                                    </h4>
                                    <small>En Curso</small>
                                </div>
                                <div class="col-3">
                                    <h4 class="text-secondary">
                                        <?= count(array_filter($partidos, fn($p) => $p['estado'] == 'programado')) ?>
                                    </h4>
                                    <small>Pendientes</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de partidos -->
            <?php foreach ($partidos as $index => $partido): ?>
                <div class="partido-card partido-<?= $partido['estado'] ?>">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-1 text-center">
                                <h5 class="text-muted mb-0">#<?= $index + 1 ?></h5>
                            </div>
                            <div class="col-md-4">
                                <h5 class="mb-1">
                                    <?= htmlspecialchars($partido['equipo_local']) ?>
                                    <span class="text-muted mx-2">VS</span>
                                    <?= htmlspecialchars($partido['equipo_visitante']) ?>
                                </h5>
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> <?= date('H:i', strtotime($partido['hora_partido'])) ?>
                                    | <i class="fas fa-tag"></i> <?= htmlspecialchars($partido['categoria']) ?>
                                    | <i class="fas fa-list-ol"></i> Fecha <?= $partido['numero_fecha'] ?>
                                </small>
                            </div>
                            <div class="col-md-2 text-center">
                                <?php if ($partido['estado'] == 'finalizado'): ?>
                                    <div class="marcador text-success">
                                        <?= $partido['goles_local'] ?> - <?= $partido['goles_visitante'] ?>
                                    </div>
                                    <small class="text-success">
                                        <i class="fas fa-flag-checkered"></i> Finalizado
                                    </small>
                                <?php elseif ($partido['estado'] == 'en_curso'): ?>
                                    <div class="marcador text-danger">
                                        <?= $partido['goles_local'] ?> - <?= $partido['goles_visitante'] ?>
                                    </div>
                                    <div class="cronometro-live" id="cronometro-<?= $partido['id'] ?>" 
                                         data-segundos="<?= $partido['segundos_transcurridos'] ?? 0 ?>"
                                         data-estado="<?= $partido['tiempo_actual'] ?>">
                                        00:00
                                    </div>
                                    <small class="text-danger">
                                        <i class="fas fa-broadcast-tower"></i> EN VIVO
                                        (<?= ucfirst(str_replace('_', ' ', $partido['tiempo_actual'])) ?>)
                                    </small>
                                <?php else: ?>
                                    <div class="marcador text-muted">- : -</div>
                                    <small class="text-muted">Programado</small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="badge estado-badge bg-<?= $partido['estado'] == 'finalizado' ? 'success' : ($partido['estado'] == 'en_curso' ? 'danger' : 'secondary') ?>">
                                    <?php if ($partido['estado'] == 'en_curso'): ?>
                                        <i class="fas fa-broadcast-tower"></i> EN VIVO
                                    <?php elseif ($partido['estado'] == 'finalizado'): ?>
                                        <i class="fas fa-check-circle"></i> FINALIZADO
                                    <?php else: ?>
                                        <i class="fas fa-hourglass-start"></i> PROGRAMADO
                                    <?php endif; ?>
                                </span>
                            </div>
                            <div class="col-md-3 text-end">
                                <a href="partido_live.php?partido_id=<?= $partido['id'] ?>" 
                                   class="btn btn-<?= $partido['estado'] == 'finalizado' ? 'outline-success' : ($partido['estado'] == 'en_curso' ? 'danger' : 'primary') ?> btn-iniciar">
                                    <i class="fas fa-<?= $partido['estado'] == 'finalizado' ? 'eye' : ($partido['estado'] == 'en_curso' ? 'broadcast-tower' : 'clipboard-list') ?>"></i>
                                    <?php 
                                    if ($partido['estado'] == 'finalizado') {
                                        echo 'Ver Resumen';
                                    } elseif ($partido['estado'] == 'en_curso') {
                                        echo 'Continuar Partido';
                                    } else {
                                        echo 'Ver Partido';
                                    }
                                    ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Cronómetros para partidos en curso
        const cronometros = {};
        
        // Inicializar cronómetros
        document.addEventListener('DOMContentLoaded', function() {
            const elementos = document.querySelectorAll('.cronometro-live');
            
            elementos.forEach(elemento => {
                const partidoId = elemento.id.replace('cronometro-', '');
                const segundosIniciales = parseInt(elemento.dataset.segundos);
                const estado = elemento.dataset.estado;
                
                cronometros[partidoId] = {
                    segundos: segundosIniciales,
                    estado: estado,
                    elemento: elemento
                };
                
                actualizarDisplay(partidoId);
            });
            
            // Actualizar cronómetros cada segundo
            setInterval(actualizarCronometros, 1000);
        });
        
        function actualizarCronometros() {
            Object.keys(cronometros).forEach(partidoId => {
                const cronometro = cronometros[partidoId];
                
                // Solo avanzar si no está en descanso
                if (cronometro.estado !== 'descanso') {
                    cronometro.segundos++;
                }
                
                actualizarDisplay(partidoId);
            });
        }
        
        function actualizarDisplay(partidoId) {
            const cronometro = cronometros[partidoId];
            const minutos = Math.floor(cronometro.segundos / 60);
            const segundos = cronometro.segundos % 60;
            
            cronometro.elemento.textContent = 
                String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
        }
        
        // Countdown timer para expiración del código
        const expiresAt = new Date('<?= $codigo_activo['expires_at'] ?>').getTime();
        const countdownElement = document.getElementById('countdown');
        
        function updateCountdown() {
            const now = new Date().getTime();
            const distance = expiresAt - now;
            
            if (distance < 0) {
                countdownElement.innerHTML = '<span class="text-danger">EXPIRADO</span>';
                setTimeout(() => {
                    alert('Su código ha expirado. Será redirigido para ingresar un nuevo código.');
                    window.location.href = 'index.php';
                }, 1000);
                return;
            }
            
            const hours = Math.floor(distance / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            
            if (hours > 0) {
                countdownElement.innerHTML = `${hours}h ${minutes}m`;
            } else if (minutes > 0) {
                countdownElement.innerHTML = `${minutes}m`;
                if (minutes <= 10) {
                    countdownElement.className = 'tiempo-restante text-danger';
                }
            } else {
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                countdownElement.innerHTML = `${seconds}s`;
                countdownElement.className = 'tiempo-restante text-danger';
            }
        }
        
        updateCountdown();
        setInterval(updateCountdown, 1000);
        
        // Auto-refresh cada 30 segundos para mantener datos actualizados
        setInterval(function() {
            // Solo recargar si el código aún es válido
            const now = new Date().getTime();
            if (now < expiresAt) {
                location.reload();
            }
        }, 30000);
        
        // Verificar cierre de sesión automático
        <?php if (!empty($partidos) && isset($ultimo_finalizado)): ?>
        const todosFinalizados = <?= json_encode(count(array_filter($partidos, fn($p) => $p['estado'] != 'finalizado')) == 0) ?>;
        const ultimoFinalizado = '<?= $ultimo_finalizado ?? '' ?>';
        
        if (todosFinalizados && ultimoFinalizado) {
            const tiempoLimite = new Date(ultimoFinalizado).getTime() + (10 * 60 * 1000); // +10 minutos
            
            setInterval(function() {
                if (new Date().getTime() > tiempoLimite) {
                    alert('Todos los partidos han finalizado. Será redirigido para ingresar un nuevo código.');
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 3000);
                }
            }, 60000); // Verificar cada minuto
        }
        <?php endif; ?>
    </script>
</body>
</html>