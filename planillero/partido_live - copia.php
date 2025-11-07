<?php
// (El código PHP inicial hasta la línea 220 es el mismo que tenías y es correcto)
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('planillero')) {
    redirect('../login.php');
}
if (!isset($_SESSION['codigo_cancha_activo'])) {
    redirect('ingreso_codigo.php');
}
$db = Database::getInstance()->getConnection();
$message = '';
$error = '';
$partido_id = $_GET['partido_id'] ?? null;
if (!$partido_id) {
    redirect('planillero.php');
}
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
$stmt = $db->prepare("SELECT id, apellido_nombre, equipo_id FROM jugadores WHERE equipo_id = ? AND activo = 1 ORDER BY apellido_nombre");
$stmt->execute([$partido['equipo_local_id']]);
$jugadores_local = $stmt->fetchAll();
$stmt->execute([$partido['equipo_visitante_id']]);
$jugadores_visitante = $stmt->fetchAll();
$stmt = $db->prepare("
    SELECT e.*, j.apellido_nombre, j.equipo_id
    FROM eventos_partido e
    JOIN jugadores j ON e.jugador_id = j.id
    WHERE e.partido_id = ?
    ORDER BY e.minuto ASC, e.created_at ASC
");
$stmt->execute([$partido_id]);
$eventos_existentes = $stmt->fetchAll();

// CORRECCIÓN: Variable para almacenar los segundos del primer tiempo
$segundos_primer_tiempo = 0;
if ($partido['tiempo_actual'] === 'descanso' || $partido['tiempo_actual'] === 'segundo_tiempo' || $partido['tiempo_actual'] === 'finalizado') {
    // Si ya pasó el primer tiempo, necesitamos saber cuánto duró.
    // Esto asume que al finalizar el 1T, 'segundos_transcurridos' contiene esa duración.
    // Para ser más robusto, se podría añadir una columna 'segundos_primer_tiempo' a la tabla partidos.
    // Por ahora, lo manejamos así:
    $stmt_pt = $db->prepare("SELECT segundos_transcurridos FROM partidos WHERE id = ?");
    $stmt_pt->execute([$partido_id]);
    $segundos_primer_tiempo = $stmt_pt->fetchColumn();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        $db->beginTransaction();
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($action == 'iniciar_partido') {
            $stmt = $db->prepare("UPDATE partidos SET estado = 'en_curso', iniciado_at = NOW(), minuto_actual = 0, segundos_transcurridos = 0, tiempo_actual = 'primer_tiempo' WHERE id = ?");
            $stmt->execute([$partido_id]);
            $db->commit();
            if ($is_ajax) { echo json_encode(['success' => true]); exit; }
        }
        
        elseif ($action == 'actualizar_cronometro') {
            $segundos_totales = (int)($_POST['segundos_transcurridos'] ?? 0);
            $minutos = floor($segundos_totales / 60);
            $stmt = $db->prepare("UPDATE partidos SET segundos_transcurridos = ?, minuto_actual = ? WHERE id = ?");
            $stmt->execute([$segundos_totales, $minutos, $partido_id]);
            $db->commit();
            if ($is_ajax) { echo json_encode(['success' => true]); exit; }
        }
        
        elseif ($action == 'fin_primer_tiempo') {
            $segundos_primer_tiempo = (int)($_POST['segundos_actuales'] ?? 0);
            $stmt = $db->prepare("UPDATE partidos SET tiempo_actual = 'descanso', segundos_transcurridos = ? WHERE id = ?");
            $stmt->execute([$segundos_primer_tiempo, $partido_id]);
            $db->commit();
            if ($is_ajax) { echo json_encode(['success' => true, 'segundos_primer_tiempo' => $segundos_primer_tiempo]); exit; }
        }
        
        elseif ($action == 'inicio_segundo_tiempo') {
            $stmt = $db->prepare("UPDATE partidos SET tiempo_actual = 'segundo_tiempo' WHERE id = ?");
            $stmt->execute([$partido_id]);
            $db->commit();
            if ($is_ajax) { echo json_encode(['success' => true]); exit; }
        }
        
        elseif ($action == 'finalizar_partido') {
            // El resto del código PHP de finalización de partido es correcto
            // ... (código sin cambios)
        }
        
    } catch (Exception $e) {
        $db->rollback();
        // ... (código de manejo de errores sin cambios)
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<!-- ... (El HTML es el mismo, no necesita cambios) ... -->
<body>
<!-- ... (El HTML es el mismo, no necesita cambios) ... -->
<script>
// ========== VARIABLES GLOBALES ==========
// CORRECCIÓN: 'relojSegundos' ahora cuenta el tiempo del periodo actual.
let relojSegundos = 0;
// CORRECCIÓN: Nueva variable para guardar la duración del 1er tiempo.
let segundosPrimerTiempo = <?= (float)$segundos_primer_tiempo ?>;

let intervalo = null;
let estado_actual = '<?= $partido['estado'] ?>';
let tiempo_actual = '<?= $partido['tiempo_actual'] ?>';
const equipo_local_id = <?= $partido['equipo_local_id'] ?>;
const jugadores_local = <?= json_encode($jugadores_local) ?>;
const jugadores_visitante = <?= json_encode($jugadores_visitante) ?>;
const eventos_existentes = <?= json_encode($eventos_existentes) ?>;

// ========== RELOJ ==========
function formatearTiempo(seg) {
    const minutos = Math.floor(seg / 60);
    const segundos = seg % 60;
    return String(minutos).padStart(2, '0') + ':' + String(segundos).padStart(2, '0');
}

function actualizarReloj() {
    relojSegundos++;
    document.getElementById('reloj').innerText = formatearTiempo(relojSegundos);
    if (relojSegundos % 15 === 0) guardarTiempo();
}

function iniciarReloj() {
    if (intervalo) clearInterval(intervalo);
    intervalo = setInterval(actualizarReloj, 1000);
}

function detenerReloj() {
    if (intervalo) clearInterval(intervalo);
    intervalo = null;
    guardarTiempo();
}

// CORRECCIÓN: 'guardarTiempo' ahora envía el tiempo total acumulado.
function guardarTiempo() {
    let segundosTotales = segundosPrimerTiempo + relojSegundos;
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: `action=actualizar_cronometro&segundos_transcurridos=${segundosTotales}`
    }).catch(console.error);
}

// ========== LÓGICA DEL BOTÓN DE ACCIÓN ==========
// (Esta función es correcta, no necesita cambios)
function actualizarBotonAccion() { /* ... */ }

function ejecutarAccion() { /* ... */ }

// ========== ACCIONES AJAX (SIN RECARGA) ==========
function enviarAccion(action, bodyParams = {}) { /* ... */ }

// CORRECCIÓN: iniciarPrimerTiempo resetea los contadores
function iniciarPrimerTiempo() {
    relojSegundos = 0;
    segundosPrimerTiempo = 0;
    enviarAccion('iniciar_partido').then(data => {
        if (data.success) {
            estado_actual = 'en_curso';
            tiempo_actual = 'primer_tiempo';
            iniciarReloj();
            actualizarBotonAccion();
        }
    }).catch(err => alert('Error al iniciar el partido.'));
}

// CORRECCIÓN: finPrimerTiempo actualiza la variable 'segundosPrimerTiempo'
function finPrimerTiempo() {
    enviarAccion('fin_primer_tiempo', { segundos_actuales: relojSegundos }).then(data => {
        if (data.success) {
            tiempo_actual = 'descanso';
            segundosPrimerTiempo = data.segundos_primer_tiempo; // Guardamos la duración
            detenerReloj();
            actualizarBotonAccion();
            // Reseteamos el reloj visualmente
            document.getElementById('reloj').innerText = '00:00';
        }
    }).catch(err => alert('Error al finalizar el primer tiempo.'));
}

// CORRECCIÓN: inicioSegundoTiempo resetea el reloj del periodo
function inicioSegundoTiempo() {
    relojSegundos = 0; // Reiniciamos el contador para el 2T
    enviarAccion('inicio_segundo_tiempo').then(data => {
        if (data.success) {
            tiempo_actual = 'segundo_tiempo';
            iniciarReloj();
            actualizarBotonAccion();
        }
    }).catch(err => alert('Error al iniciar el segundo tiempo.'));
}

// CORRECCIÓN: finalizarPartido envía el tiempo total correcto
function finalizarPartido() {
    if (!confirm('¿Está seguro de finalizar el partido?')) return;
    detenerReloj();
    
    const formData = new FormData();
    // Sumamos el tiempo del 1T con lo que se haya jugado del 2T
    const segundosFinales = segundosPrimerTiempo + relojSegundos;
    formData.append('action', 'finalizar_partido');
    formData.append('segundos_finales', segundosFinales);
    // ... (el resto de la función para recopilar eventos es correcta)
}


// ========== GESTIÓN DINÁMICA DE EVENTOS ==========
// (Sin cambios en addGol, addTarjeta, etc.)

// ========== INICIALIZACIÓN DE LA PÁGINA ==========
document.addEventListener('DOMContentLoaded', () => {
    // CORRECCIÓN: Lógica de inicialización del reloj
    if (tiempo_actual === 'primer_tiempo' || tiempo_actual === 'segundo_tiempo') {
        // Si la página se recarga a mitad de un tiempo, calculamos cuánto ha pasado
        // en el periodo actual.
        const segundosTotalesDB = <?= (int)($partido['segundos_transcurridos'] ?? 0) ?>;
        relojSegundos = segundosTotalesDB - segundosPrimerTiempo;
    }
    document.getElementById('reloj').innerText = formatearTiempo(relojSegundos);
    
    actualizarBotonAccion();
    
    // (Renderizar eventos existentes no cambia)

    if (estado_actual === 'en_curso' && (tiempo_actual === 'primer_tiempo' || tiempo_actual === 'segundo_tiempo')) {
        iniciarReloj();
    }
});
</script>
</body>
</html>