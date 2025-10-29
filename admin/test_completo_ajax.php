<?php
// admin/test_ajax_completo.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    die("No autorizado");
}

$formato_id = $_GET['id'] ?? 1;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Test AJAX Completo</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .log-entry { 
            font-family: monospace; 
            font-size: 12px; 
            padding: 5px;
            border-bottom: 1px solid #eee;
        }
        .log-success { background: #d4edda; }
        .log-error { background: #f8d7da; }
        .log-info { background: #d1ecf1; }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1>🧪 Test AJAX Completo</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5>Tests Disponibles</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label>Formato ID:</label>
                        <input type="number" id="formato_id" class="form-control" value="<?php echo $formato_id; ?>">
                    </div>
                    
                    <hr>
                    
                    <h6>Test 1: Conexión Básica</h6>
                    <button class="btn btn-info w-100 mb-2" onclick="testConexion()">
                        🔌 Test Conexión
                    </button>
                    
                    <hr>
                    
                    <h6>Test 2: Asignar Equipo Individual</h6>
                    <div class="input-group mb-2">
                        <input type="number" id="equipo_id" class="form-control" placeholder="Equipo ID">
                        <input type="number" id="zona_id" class="form-control" placeholder="Zona ID">
                        <button class="btn btn-primary" onclick="testAsignarEquipo()">
                            ➕ Asignar
                        </button>
                    </div>
                    
                    <hr>
                    
                    <h6>Test 3: Distribuir Automáticamente</h6>
                    <button class="btn btn-success w-100 mb-2" onclick="testDistribuir()">
                        🎲 Distribuir Auto
                    </button>
                    
                    <hr>
                    
                    <h6>Test 4: Limpiar Asignaciones</h6>
                    <button class="btn btn-warning w-100 mb-2" onclick="testLimpiar()">
                        🧹 Limpiar
                    </button>
                    
                    <hr>
                    
                    <button class="btn btn-danger w-100" onclick="limpiarLogs()">
                        🗑️ Limpiar Logs
                    </button>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-dark text-white">
                    <h5>📊 Logs en Tiempo Real</h5>
                </div>
                <div class="card-body" style="max-height: 600px; overflow-y: auto;">
                    <div id="logs"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5>✅ Resultado del Test</h5>
                </div>
                <div class="card-body">
                    <div id="resultado"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script>
function log(message, type = 'info') {
    const timestamp = new Date().toLocaleTimeString();
    const colors = {
        success: 'log-success',
        error: 'log-error',
        info: 'log-info'
    };
    
    const entry = `<div class="log-entry ${colors[type]}">[${timestamp}] ${message}</div>`;
    $('#logs').prepend(entry);
    console.log(`[${type.toUpperCase()}] ${message}`);
}

function limpiarLogs() {
    $('#logs').empty();
    $('#resultado').empty();
    log('Logs limpiados', 'info');
}

function mostrarResultado(data, success = true) {
    const color = success ? 'success' : 'danger';
    const icon = success ? '✓' : '✗';
    $('#resultado').html(`
        <div class="alert alert-${color}">
            <h5>${icon} ${success ? 'Éxito' : 'Error'}</h5>
            <pre>${JSON.stringify(data, null, 2)}</pre>
        </div>
    `);
}

function testConexion() {
    log('Iniciando test de conexión...', 'info');
    
    $.ajax({
        url: 'ajax/test_asignar.php',
        method: 'POST',
        data: { test: 'conexion' },
        beforeSend: function() {
            log('→ Enviando request a ajax/test_asignar.php', 'info');
        },
        success: function(response) {
            log('✓ Respuesta recibida', 'success');
            log('Datos: ' + JSON.stringify(response), 'info');
            mostrarResultado(response, true);
        },
        error: function(xhr, status, error) {
            log('✗ Error en la petición', 'error');
            log('Status: ' + status, 'error');
            log('Error: ' + error, 'error');
            log('Response: ' + xhr.responseText, 'error');
            mostrarResultado({
                status: xhr.status,
                statusText: xhr.statusText,
                error: error,
                response: xhr.responseText
            }, false);
        }
    });
}

function testAsignarEquipo() {
    const formatoId = $('#formato_id').val();
    const equipoId = $('#equipo_id').val();
    const zonaId = $('#zona_id').val();
    
    if (!equipoId || !zonaId) {
        alert('Debes ingresar Equipo ID y Zona ID');
        return;
    }
    
    log(`Asignando equipo ${equipoId} a zona ${zonaId}...`, 'info');
    
    $.ajax({
        url: 'ajax/asignar_equipo_zona.php',
        method: 'POST',
        data: {
            formato_id: formatoId,
            equipo_id: equipoId,
            zona_id: zonaId
        },
        dataType: 'json',
        beforeSend: function() {
            log('→ POST a ajax/asignar_equipo_zona.php', 'info');
            log(`  formato_id: ${formatoId}`, 'info');
            log(`  equipo_id: ${equipoId}`, 'info');
            log(`  zona_id: ${zonaId}`, 'info');
        },
        success: function(response) {
            if (response.success) {
                log('✓ Equipo asignado correctamente', 'success');
                mostrarResultado(response, true);
            } else {
                log('✗ Error: ' + response.message, 'error');
                mostrarResultado(response, false);
            }
        },
        error: function(xhr, status, error) {
            log('✗ Error AJAX: ' + error, 'error');
            log('Status: ' + xhr.status, 'error');
            log('Response: ' + xhr.responseText.substring(0, 500), 'error');
            mostrarResultado({
                error: error,
                status: xhr.status,
                response: xhr.responseText
            }, false);
        }
    });
}

function testDistribuir() {
    const formatoId = $('#formato_id').val();
    
    if (!confirm('¿Distribuir equipos automáticamente?')) {
        return;
    }
    
    log('Iniciando distribución automática...', 'info');
    
    $.ajax({
        url: 'ajax/distribuir_equipos_automatico.php',
        method: 'POST',
        data: {
            formato_id: formatoId
        },
        dataType: 'json',
        beforeSend: function() {
            log('→ POST a ajax/distribuir_equipos_automatico.php', 'info');
            log(`  formato_id: ${formatoId}`, 'info');
        },
        success: function(response) {
            if (response.success) {
                log('✓ Distribución exitosa', 'success');
                log(`Equipos distribuidos: ${response.total_equipos}`, 'success');
                log(`Zonas: ${response.total_zonas}`, 'success');
                mostrarResultado(response, true);
            } else {
                log('✗ Error: ' + response.message, 'error');
                mostrarResultado(response, false);
            }
        },
        error: function(xhr, status, error) {
            log('✗ Error AJAX: ' + error, 'error');
            log('Status: ' + xhr.status, 'error');
            log('Response: ' + xhr.responseText.substring(0, 500), 'error');
            mostrarResultado({
                error: error,
                status: xhr.status,
                response: xhr.responseText
            }, false);
        }
    });
}

function testLimpiar() {
    const formatoId = $('#formato_id').val();
    
    if (!confirm('¿Limpiar todas las asignaciones?')) {
        return;
    }
    
    log('Limpiando asignaciones...', 'info');
    
    $.ajax({
        url: 'ajax/limpiar_equipos_zonas.php',
        method: 'POST',
        data: {
            formato_id: formatoId
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                log('✓ Asignaciones limpiadas', 'success');
                mostrarResultado(response, true);
            } else {
                log('✗ Error: ' + response.message, 'error');
                mostrarResultado(response, false);
            }
        },
        error: function(xhr, status, error) {
            log('✗ Error AJAX: ' + error, 'error');
            mostrarResultado({
                error: error,
                status: xhr.status,
                response: xhr.responseText
            }, false);
        }
    });
}

// Al cargar la página
$(document).ready(function() {
    log('🚀 Sistema de test cargado', 'success');
    log('jQuery versión: ' + $.fn.jquery, 'info');
    log('Formato ID: ' + $('#formato_id').val(), 'info');
});
</script>
</body>
</html>