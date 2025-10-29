<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('planillero')) {
    redirect('../login.php');
}

// Verificar si hay un código de cancha activo en la sesión
if (!isset($_SESSION['codigo_cancha_activo'])) {
    redirect('ingreso_codigo.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Obtener partido
$partido_id = $_GET['partido_id'] ?? null;
if (!$partido_id) {
    redirect('planillero.php');
}

// Verificar que el partido pertenece a la cancha del código activo
$codigo_activo = $_SESSION['codigo_cancha_activo'];
$cancha_id = $codigo_activo['cancha_id'];

$stmt = $db->prepare("
    SELECT p.*, 
           el.nombre AS equipo_local, el.id AS equipo_local_id,
           ev.nombre AS equipo_visitante, ev.id AS equipo_visitante_id,
           c.nombre AS cancha_nombre, cat.nombre AS categoria,
           f.numero_fecha
    FROM partidos p
    JOIN equipos el ON p.equipo_local_id = el.id
    JOIN equipos ev ON p.equipo_visitante_id = ev.id
    JOIN canchas c ON p.cancha_id = c.id
    JOIN fechas f ON p.fecha_id = f.id
    JOIN categorias cat ON f.categoria_id = cat.id
    WHERE p.id = ? AND p.cancha_id = ?
");
$stmt->execute([$partido_id, $cancha_id]);
$partido = $stmt->fetch();

if (!$partido) {
    redirect('planillero.php');
}

// Obtener jugadores de ambos equipos
$stmt = $db->prepare("SELECT id, apellido_nombre, equipo_id FROM jugadores WHERE equipo_id = ? AND activo = 1 ORDER BY apellido_nombre");
$stmt->execute([$partido['equipo_local_id']]);
$jugadores_local = $stmt->fetchAll();

$stmt->execute([$partido['equipo_visitante_id']]);
$jugadores_visitante = $stmt->fetchAll();

// Obtener eventos existentes
$stmt = $db->prepare("
    SELECT e.*, j.apellido_nombre, j.equipo_id
    FROM eventos_partido e
    JOIN jugadores j ON e.jugador_id = j.id
    WHERE e.partido_id = ?
    ORDER BY e.minuto ASC, e.created_at ASC
");
$stmt->execute([$partido_id]);
$eventos_existentes = $stmt->fetchAll();

// Procesar acciones del partido
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        $db->beginTransaction();
        
        if ($action == 'iniciar_partido') {
            $stmt = $db->prepare("
                UPDATE partidos 
                SET estado = 'en_curso', iniciado_at = NOW(), 
                    minuto_actual = 0, segundos_transcurridos = 0,
                    tiempo_actual = 'primer_tiempo'
                WHERE id = ?
            ");
            $stmt->execute([$partido_id]);
            $message = 'Partido iniciado - Cronómetro activado';
        }
        
        elseif ($action == 'actualizar_cronometro') {
            $segundos = (int)$_POST['segundos_transcurridos'];
            $minutos = floor($segundos / 60);
            
            $stmt = $db->prepare("
                UPDATE partidos 
                SET segundos_transcurridos = ?, minuto_actual = ?
                WHERE id = ?
            ");
            $stmt->execute([$segundos, $minutos, $partido_id]);
        }
        
        elseif ($action == 'fin_primer_tiempo') {
            $segundos = (int)$_POST['segundos_actuales'];
            $stmt = $db->prepare("
                UPDATE partidos 
                SET tiempo_actual = 'descanso', segundos_transcurridos = ?
                WHERE id = ?
            ");
            $stmt->execute([$segundos, $partido_id]);
            $message = 'Fin del primer tiempo - Cronómetro pausado';
        }
        
        elseif ($action == 'inicio_segundo_tiempo') {
            $stmt = $db->prepare("
                UPDATE partidos 
                SET tiempo_actual = 'segundo_tiempo'
                WHERE id = ?
            ");
            $stmt->execute([$partido_id]);
            $message = 'Inicio del segundo tiempo - Cronómetro reanudado';
        }
        
        elseif ($action == 'finalizar_partido') {
            $segundos = (int)$_POST['segundos_finales'];
            $observaciones = trim($_POST['observaciones'] ?? '');
            
            // Actualizar datos del partido
            $stmt = $db->prepare("
                UPDATE partidos 
                SET estado = 'finalizado', finalizado_at = NOW(), 
                    tiempo_actual = 'finalizado', 
                    segundos_transcurridos = ?,
                    observaciones = ?
                WHERE id = ?
            ");
            $stmt->execute([$segundos, $observaciones, $partido_id]);
            
            // Eliminar eventos existentes para reemplazarlos
            $stmt = $db->prepare("DELETE FROM eventos_partido WHERE partido_id = ?");
            $stmt->execute([$partido_id]);
            
            // Insertar goles
            if (!empty($_POST['goles'])) {
                foreach ($_POST['goles'] as $gol) {
                    if (!empty($gol['jugador_id'])) {
                        // Convertir tiempo mm:ss a minutos
                        $tiempo = $gol['minuto'];
                        $partes = explode(':', $tiempo);
                        $minutos = intval($partes[0]);
                        
                        $stmt = $db->prepare("
                            INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto)
                            VALUES (?, ?, 'gol', ?)
                        ");
                        $stmt->execute([$partido_id, $gol['jugador_id'], $minutos]);
                    }
                }
            }
            
            // Insertar tarjetas
            if (!empty($_POST['tarjetas'])) {
                foreach ($_POST['tarjetas'] as $tarjeta) {
                    if (!empty($tarjeta['jugador_id']) && !empty($tarjeta['tipo'])) {
                        // Convertir tiempo mm:ss a minutos
                        $tiempo = $tarjeta['minuto'];
                        $partes = explode(':', $tiempo);
                        $minutos = intval($partes[0]);
                        
                        // Verificar si es doble amarilla
                        if ($tarjeta['tipo'] == 'amarilla') {
                            $stmt = $db->prepare("
                                SELECT COUNT(*) as total 
                                FROM eventos_partido 
                                WHERE partido_id = ? AND jugador_id = ? AND tipo_evento = 'amarilla'
                            ");
                            $stmt->execute([$partido_id, $tarjeta['jugador_id']]);
                            $amarillas_previas = $stmt->fetch()['total'];
                            
                            if ($amarillas_previas >= 1) {
                                // Es la segunda amarilla, convertirla en roja por doble amarilla
                                $tarjeta['tipo'] = 'roja_doble_amarilla';
                                
                                // Registrar sanción
                                $fecha_suspension = date('Y-m-d', strtotime('+1 day'));
                                $stmt = $db->prepare("
                                    INSERT INTO sanciones (jugador_id, partido_id, tipo_sancion, fecha_suspension, observaciones)
                                    VALUES (?, ?, 'roja', ?, 'Doble amarilla en partido')
                                ");
                                $stmt->execute([$tarjeta['jugador_id'], $partido_id, $fecha_suspension]);
                            }
                        } elseif ($tarjeta['tipo'] == 'roja') {
                            // Registrar sanción para tarjeta roja directa
                            $fecha_suspension = date('Y-m-d', strtotime('+1 day'));
                            $stmt = $db->prepare("
                                INSERT INTO sanciones (jugador_id, partido_id, tipo_sancion, fecha_suspension, observaciones)
                                VALUES (?, ?, 'roja', ?, 'Tarjeta roja directa')
                            ");
                            $stmt->execute([$tarjeta['jugador_id'], $partido_id, $fecha_suspension]);
                        }
                        
                        // Insertar evento
                        $stmt = $db->prepare("
                            INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$partido_id, $tarjeta['jugador_id'], $tarjeta['tipo'], $minutos]);
                    }
                }
            }
            
            // Calcular goles totales
            $goles_local = 0;
            $goles_visitante = 0;
            
            if (!empty($_POST['goles'])) {
                foreach ($_POST['goles'] as $gol) {
                    if (!empty($gol['jugador_id'])) {
                        // Obtener equipo del jugador
                        $stmt = $db->prepare("SELECT equipo_id FROM jugadores WHERE id = ?");
                        $stmt->execute([$gol['jugador_id']]);
                        $equipo_id = $stmt->fetchColumn();
                        
                        if ($equipo_id == $partido['equipo_local_id']) {
                            $goles_local++;
                        } else {
                            $goles_visitante++;
                        }
                    }
                }
            }
            
            // Actualizar marcador
            $stmt = $db->prepare("
                UPDATE partidos 
                SET goles_local = ?, goles_visitante = ?
                WHERE id = ?
            ");
            $stmt->execute([$goles_local, $goles_visitante, $partido_id]);
            
            $db->commit();
            $message = 'Partido finalizado correctamente';
            
            // Redirigir a planillero.php después de finalizar
            header('Location: planillero.php');
            exit;
        }
        
        elseif ($action == 'guardar_cambios') {
            // Para partidos finalizados que se están editando
            $observaciones = trim($_POST['observaciones'] ?? '');
            
            // Actualizar observaciones
            $stmt = $db->prepare("UPDATE partidos SET observaciones = ? WHERE id = ?");
            $stmt->execute([$observaciones, $partido_id]);
            
            // Eliminar eventos existentes para reemplazarlos
            $stmt = $db->prepare("DELETE FROM eventos_partido WHERE partido_id = ?");
            $stmt->execute([$partido_id]);
            
            // Insertar goles
            if (!empty($_POST['goles'])) {
                foreach ($_POST['goles'] as $gol) {
                    if (!empty($gol['jugador_id'])) {
                        // Convertir tiempo mm:ss a minutos
                        $tiempo = $gol['minuto'];
                        $partes = explode(':', $tiempo);
                        $minutos = intval($partes[0]);
                        
                        $stmt = $db->prepare("
                            INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto)
                            VALUES (?, ?, 'gol', ?)
                        ");
                        $stmt->execute([$partido_id, $gol['jugador_id'], $minutos]);
                    }
                }
            }
            
            // Insertar tarjetas
            if (!empty($_POST['tarjetas'])) {
                foreach ($_POST['tarjetas'] as $tarjeta) {
                    if (!empty($tarjeta['jugador_id']) && !empty($tarjeta['tipo'])) {
                        // Convertir tiempo mm:ss a minutos
                        $tiempo = $tarjeta['minuto'];
                        $partes = explode(':', $tiempo);
                        $minutos = intval($partes[0]);
                        
                        // Verificar si es doble amarilla
                        if ($tarjeta['tipo'] == 'amarilla') {
                            $stmt = $db->prepare("
                                SELECT COUNT(*) as total 
                                FROM eventos_partido 
                                WHERE partido_id = ? AND jugador_id = ? AND tipo_evento = 'amarilla'
                            ");
                            $stmt->execute([$partido_id, $tarjeta['jugador_id']]);
                            $amarillas_previas = $stmt->fetch()['total'];
                            
                            if ($amarillas_previas >= 1) {
                                // Es la segunda amarilla, convertirla en roja por doble amarilla
                                $tarjeta['tipo'] = 'roja_doble_amarilla';
                                
                                // Registrar sanción
                                $fecha_suspension = date('Y-m-d', strtotime('+1 day'));
                                $stmt = $db->prepare("
                                    INSERT INTO sanciones (jugador_id, partido_id, tipo_sancion, fecha_suspension, observaciones)
                                    VALUES (?, ?, 'roja', ?, 'Doble amarilla en partido')
                                ");
                                $stmt->execute([$tarjeta['jugador_id'], $partido_id, $fecha_suspension]);
                            }
                        } elseif ($tarjeta['tipo'] == 'roja') {
                            // Registrar sanción para tarjeta roja directa
                            $fecha_suspension = date('Y-m-d', strtotime('+1 day'));
                            $stmt = $db->prepare("
                                INSERT INTO sanciones (jugador_id, partido_id, tipo_sancion, fecha_suspension, observaciones)
                                VALUES (?, ?, 'roja', ?, 'Tarjeta roja directa')
                            ");
                            $stmt->execute([$tarjeta['jugador_id'], $partido_id, $fecha_suspension]);
                        }
                        
                        // Insertar evento
                        $stmt = $db->prepare("
                            INSERT INTO eventos_partido (partido_id, jugador_id, tipo_evento, minuto)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([$partido_id, $tarjeta['jugador_id'], $tarjeta['tipo'], $minutos]);
                    }
                }
            }
            
            // Calcular goles totales
            $goles_local = 0;
            $goles_visitante = 0;
            
            if (!empty($_POST['goles'])) {
                foreach ($_POST['goles'] as $gol) {
                    if (!empty($gol['jugador_id'])) {
                        // Obtener equipo del jugador
                        $stmt = $db->prepare("SELECT equipo_id FROM jugadores WHERE id = ?");
                        $stmt->execute([$gol['jugador_id']]);
                        $equipo_id = $stmt->fetchColumn();
                        
                        if ($equipo_id == $partido['equipo_local_id']) {
                            $goles_local++;
                        } else {
                            $goles_visitante++;
                        }
                    }
                }
            }
            
            // Actualizar marcador
            $stmt = $db->prepare("
                UPDATE partidos 
                SET goles_local = ?, goles_visitante = ?
                WHERE id = ?
            ");
            $stmt->execute([$goles_local, $goles_visitante, $partido_id]);
            
            $db->commit();
            $message = 'Cambios guardados correctamente';
            
            // Recargar eventos
            $stmt = $db->prepare("
                SELECT e.*, j.apellido_nombre, j.equipo_id
                FROM eventos_partido e
                JOIN jugadores j ON e.jugador_id = j.id
                WHERE e.partido_id = ?
                ORDER BY e.minuto ASC, e.created_at ASC
            ");
            $stmt->execute([$partido_id]);
            $eventos_existentes = $stmt->fetchAll();
        }
        
        $db->commit();
        
        // Recargar datos del partido
        $stmt = $db->prepare("
            SELECT p.*, 
                   el.nombre as equipo_local, ev.nombre as equipo_visitante,
                   el.id as equipo_local_id, ev.id as equipo_visitante_id,
                   c.nombre as cancha_nombre, cat.nombre as categoria,
                   f.numero_fecha
            FROM partidos p
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            JOIN canchas c ON p.cancha_id = c.id
            JOIN fechas f ON p.fecha_id = f.id
            JOIN categorias cat ON f.categoria_id = cat.id
            WHERE p.id = ?
        ");
        $stmt->execute([$partido_id]);
        $partido = $stmt->fetch();
        
    } catch (Exception $e) {
        $db->rollback();
        $error = 'Error: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Partido en Vivo</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<link href="../assets/css/style.css" rel="stylesheet">
<style>
    body { background-color: #f8f9fa; }
    .equipo-container { border-right: 1px solid #ccc; padding-right: 15px; }
    .event-container { max-height: 300px; overflow-y: auto; margin-bottom: 10px; }
    .btn-small { font-size: 0.8rem; padding: 0.25rem 0.5rem; }
    .doble-amarilla {
        position: relative;
        display: inline-block;
    }
    .doble-amarilla i:first-child {
        position: absolute;
        left: -8px;
        z-index: 1;
    }
    .doble-amarilla i:last-child {
        position: relative;
        z-index: 2;
    }
</style>
</head>
<body>
<div class="container mt-4">
    <h3 class="mb-3"><?= $partido['equipo_local'] ?> vs <?= $partido['equipo_visitante'] ?></h3>
    <div class="mb-3">
        <b>Cancha:</b> <?= $partido['cancha_nombre'] ?> 
        <b>Estado:</b> <span id="estado_partido"><?= ucfirst($partido['estado']) ?></span>
        <b>Tiempo:</b> <span id="tiempo_partido"><?= $partido['tiempo_actual'] ?></span>
        <b>Minuto:</b> <span id="reloj"><?= $partido['minuto_actual'] ?></span>'
    </div>

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

    <form method="POST">
        <input type="hidden" name="accion_estado" id="accion_estado" value="">
        <input type="hidden" name="tiempo_actual" id="tiempo_actual" value="">
        <input type="hidden" name="minuto_actual" id="minuto_actual" value="<?= $partido['minuto_actual'] ?>">
        <input type="hidden" name="goles_local" id="goles_local" value="<?= $partido['goles_local'] ?>">
        <input type="hidden" name="goles_visitante" id="goles_visitante" value="<?= $partido['goles_visitante'] ?>">
        <input type="hidden" name="reset_eventos" value="0">

        <div class="row">
            <!-- Local -->
            <div class="col-md-6 equipo-container">
                <h5><?= $partido['equipo_local'] ?></h5>
                <h6>⚽ Goles</h6>
                <div class="event-container" id="golesLocalContainer"></div>
                <button type="button" class="btn btn-success btn-small" onclick="addGol('local')">+ Agregar Gol</button>

                <h6>🟨🟥 Tarjetas</h6>
                <div class="event-container" id="tarjetasLocalContainer"></div>
                <button type="button" class="btn btn-danger btn-small" onclick="addTarjeta('local')">+ Agregar Tarjeta</button>
            </div>

            <!-- Visitante -->
            <div class="col-md-6">
                <h5><?= $partido['equipo_visitante'] ?></h5>
                <h6>⚽ Goles</h6>
                <div class="event-container" id="golesVisitanteContainer"></div>
                <button type="button" class="btn btn-success btn-small" onclick="addGol('visitante')">+ Agregar Gol</button>

                <h6>🟨🟥 Tarjetas</h6>
                <div class="event-container" id="tarjetasVisitanteContainer"></div>
                <button type="button" class="btn btn-danger btn-small" onclick="addTarjeta('visitante')">+ Agregar Tarjeta</button>
            </div>
        </div>

        <div class="mt-3">
            <?php if ($partido['estado'] != 'finalizado'): ?>
                <button type="button" class="btn btn-primary btn-small" onclick="cambiarEstado('en_curso','primer_tiempo')">Iniciar 1º Tiempo</button>
                <button type="button" class="btn btn-primary btn-small" onclick="cambiarEstado('en_curso','descanso')">Fin 1º Tiempo</button>
                <button type="button" class="btn btn-primary btn-small" onclick="cambiarEstado('en_curso','segundo_tiempo')">Iniciar 2º Tiempo</button>
                <button type="button" class="btn btn-success btn-small" onclick="finalizarPartido()">Finalizar Partido</button>
            <?php else: ?>
                <button type="submit" name="action" value="guardar_cambios" class="btn btn-primary btn-small">Guardar Cambios</button>
                <a href="planillero.php" class="btn btn-secondary btn-small">Volver</a>
            <?php endif; ?>
        </div>

        <!-- Observaciones -->
        <div class="mt-3">
            <label for="observaciones" class="form-label"><strong>Observaciones:</strong></label>
            <textarea class="form-control" id="observaciones" name="observaciones" rows="3"><?= htmlspecialchars($partido['observaciones'] ?? '') ?></textarea>
        </div>
    </form>
</div>

<script>
let relojSegundos = <?= $partido['segundos_transcurridos'] ?? 0 ?>;
let intervalo = null;
let estado_actual = '<?= $partido['estado'] ?>';
let tiempo_actual = '<?= $partido['tiempo_actual'] ?>';

function actualizarReloj(){
    if(estado_actual == 'en_curso' && (tiempo_actual == 'primer_tiempo' || tiempo_actual == 'segundo_tiempo')){
        relojSegundos++;
        const minutos = Math.floor(relojSegundos / 60);
        const segundos = relojSegundos % 60;
        document.getElementById('reloj').innerText = 
            String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
        document.getElementById('minuto_actual').value = minutos;
        
        // Guardar en base de datos cada 10 segundos
        if (relojSegundos % 10 === 0) {
            guardarTiempo();
        }
    }
}

function iniciarReloj(){
    if(intervalo) clearInterval(intervalo);
    intervalo = setInterval(actualizarReloj, 1000); // Actualizar cada segundo
}

function guardarTiempo() {
    const formData = new FormData();
    formData.append('action', 'actualizar_cronometro');
    formData.append('segundos_transcurridos', relojSegundos);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    }).catch(console.error);
}

// Iniciar reloj si partido en curso
if(estado_actual == 'en_curso' && (tiempo_actual == 'primer_tiempo' || tiempo_actual == 'segundo_tiempo')){
    iniciarReloj();
}

function cambiarEstado(estado, tiempo){
    estado_actual = estado;
    tiempo_actual = tiempo;
    document.getElementById('estado_partido').innerText = estado.charAt(0).toUpperCase()+estado.slice(1);
    document.getElementById('tiempo_partido').innerText = tiempo;
    document.getElementById('accion_estado').value = estado;
    document.getElementById('tiempo_actual').value = tiempo;

    if(tiempo=='primer_tiempo' || tiempo=='segundo_tiempo'){
        iniciarReloj();
    }else{
        clearInterval(intervalo);
    }
}

function finalizarPartido() {
    if (confirm('¿Está seguro de finalizar el partido?')) {
        // Establecer valores para el formulario
        document.getElementById('accion_estado').value = 'finalizado';
        document.getElementById('tiempo_actual').value = 'finalizado';
        
        // Crear campos ocultos para enviar los datos
        const form = document.querySelector('form');
        
        // Campo para acción
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'finalizar_partido';
        form.appendChild(actionInput);
        
        // Campo para segundos finales
        const segundosInput = document.createElement('input');
        segundosInput.type = 'hidden';
        segundosInput.name = 'segundos_finales';
        segundosInput.value = relojSegundos;
        form.appendChild(segundosInput);
        
        // Campo para observaciones finales
        const observacionesInput = document.createElement('input');
        observacionesInput.type = 'hidden';
        observacionesInput.name = 'observaciones';
        observacionesInput.value = document.getElementById('observaciones').value;
        form.appendChild(observacionesInput);
        
        // Enviar formulario
        form.submit();
    }
}

// Funciones para agregar goles y tarjetas
const jugadores_local = <?= json_encode($jugadores_local) ?>;
const jugadores_visitante = <?= json_encode($jugadores_visitante) ?>;

function addGol(lado){
    let jugadores = lado=='local'?jugadores_local:jugadores_visitante;
    let container = document.getElementById('goles'+lado.charAt(0).toUpperCase()+lado.slice(1)+'Container');
    let inputGoles = document.getElementById('goles_'+lado);

    let div = document.createElement('div');
    div.className='row mb-2';

    let options = '';
    jugadores.forEach(j => options += `<option value="${j.id}">${j.apellido_nombre}</option>`);

    const minutos = Math.floor(relojSegundos / 60);
    const segundos = relojSegundos % 60;
    const tiempoFormateado = String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');

    div.innerHTML=`
        <div class="col">
            <select class="form-select" name="goles[][jugador_id]">${options}</select>
        </div>
        <div class="col">
            <input type="text" class="form-control" name="goles[][minuto]" value="${tiempoFormateado}" readonly>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
    
    // Actualizar contador de goles
    if(lado == 'local') {
        document.getElementById('goles_local').value = parseInt(document.getElementById('goles_local').value) + 1;
    } else {
        document.getElementById('goles_visitante').value = parseInt(document.getElementById('goles_visitante').value) + 1;
    }
}

function addTarjeta(lado){
    let jugadores = lado=='local'?jugadores_local:jugadores_visitante;
    let container = document.getElementById('tarjetas'+lado.charAt(0).toUpperCase()+lado.slice(1)+'Container');

    let div = document.createElement('div');
    div.className='row mb-2';

    let options = '';
    jugadores.forEach(j => options += `<option value="${j.id}">${j.apellido_nombre}</option>`);

    const minutos = Math.floor(relojSegundos / 60);
    const segundos = relojSegundos % 60;
    const tiempoFormateado = String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');

    div.innerHTML=`
        <div class="col">
            <select class="form-select" name="tarjetas[][jugador_id]">${options}</select>
        </div>
        <div class="col">
            <select class="form-select" name="tarjetas[][tipo]">
                <option value="amarilla">Amarilla</option>
                <option value="roja">Roja</option>
            </select>
        </div>
        <div class="col">
            <input type="text" class="form-control" name="tarjetas[][minuto]" value="${tiempoFormateado}" readonly>
        </div>
        <div class="col-auto">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.parentElement.remove()">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(div);
}

// Cargar eventos existentes
function cargarEventosExistentes() {
    <?php foreach ($eventos_existentes as $evento): ?>
        <?php if ($evento['tipo_evento'] == 'gol'): ?>
            <?php 
                $lado = $evento['equipo_id'] == $partido['equipo_local_id'] ? 'local' : 'visitante';
                $minutos = $evento['minuto'];
                $segundos = 0; // No tenemos segundos en los eventos existentes, pero podríamos calcularlos
                $tiempoFormateado = String($minutos).padStart(2, '0') . ':00';
            ?>
            let container = document.getElementById('goles<?= ucfirst($lado) ?>Container');
            let div = document.createElement('div');
            div.className='row mb-2';
            div.innerHTML=`
                <div class="col">
                    <select class="form-select" name="goles[][jugador_id]">
                        <option value="<?= $evento['jugador_id'] ?>" selected><?= htmlspecialchars($evento['apellido_nombre']) ?></option>
                    </select>
                </div>
                <div class="col">
                    <input type="text" class="form-control" name="goles[][minuto]" value="<?= $tiempoFormateado ?>" readonly>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
        <?php elseif ($evento['tipo_evento'] == 'amarilla' || $evento['tipo_evento'] == 'roja' || $evento['tipo_evento'] == 'roja_doble_amarilla'): ?>
            <?php 
                $lado = $evento['equipo_id'] == $partido['equipo_local_id'] ? 'local' : 'visitante';
                $minutos = $evento['minuto'];
                $segundos = 0;
                $tiempoFormateado = String($minutos).padStart(2, '0') . ':00';
                $tipo = $evento['tipo_evento'] == 'roja_doble_amarilla' ? 'roja' : $evento['tipo_evento'];
            ?>
            let container = document.getElementById('tarjetas<?= ucfirst($lado) ?>Container');
            let div = document.createElement('div');
            div.className='row mb-2';
            div.innerHTML=`
                <div class="col">
                    <select class="form-select" name="tarjetas[][jugador_id]">
                        <option value="<?= $evento['jugador_id'] ?>" selected><?= htmlspecialchars($evento['apellido_nombre']) ?></option>
                    </select>
                </div>
                <div class="col">
                    <select class="form-select" name="tarjetas[][tipo]">
                        <option value="<?= $tipo ?>" selected><?= $tipo == 'amarilla' ? 'Amarilla' : 'Roja' ?></option>
                    </select>
                </div>
                <div class="col">
                    <input type="text" class="form-control" name="tarjetas[][minuto]" value="<?= $tiempoFormateado ?>" readonly>
                </div>
                <div class="col-auto">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            `;
            container.appendChild(div);
        <?php endif; ?>
    <?php endforeach; ?>
}

// Cargar eventos existentes al iniciar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarEventosExistentes();
    
    // Actualizar el reloj inicial
    const minutos = Math.floor(relojSegundos / 60);
    const segundos = relojSegundos % 60;
    document.getElementById('reloj').innerText = 
        String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
});
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>