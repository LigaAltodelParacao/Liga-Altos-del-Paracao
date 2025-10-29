<?php
/**
 * ARCHIVO DE DIAGNÓSTICO DEL SISTEMA DE SANCIONES
 * 
 * Ubicación: admin/diagnostico_sanciones.php
 * 
 * Este archivo te mostrará exactamente qué está fallando
 * en el sistema de descuento automático de sanciones.
 */

require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    die('Acceso denegado');
}

$db = Database::getInstance()->getConnection();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico de Sanciones</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .test-ok { color: #28a745; font-weight: bold; }
        .test-error { color: #dc3545; font-weight: bold; }
        .test-warning { color: #ffc107; font-weight: bold; }
        .code-block {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4"><i class="fas fa-stethoscope"></i> Diagnóstico del Sistema de Sanciones</h1>
    
    <?php
    $errores = [];
    $advertencias = [];
    $exitos = [];
    
    // ===== TEST 1: Verificar que el archivo de funciones existe =====
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-primary text-white"><strong>TEST 1:</strong> Archivo de funciones</div>';
    echo '<div class="card-body">';
    
    $archivo_funciones = __DIR__ . '/include/sanciones_functions.php';
    if (file_exists($archivo_funciones)) {
        echo '<p class="test-ok"><i class="fas fa-check-circle"></i> El archivo include/sanciones_functions.php EXISTE</p>';
        $exitos[] = 'Archivo de funciones existe';
        
        // Verificar que la función principal está definida
        if (function_exists('cumplirSancionesAutomaticas')) {
            echo '<p class="test-ok"><i class="fas fa-check-circle"></i> La función cumplirSancionesAutomaticas() está DISPONIBLE</p>';
            $exitos[] = 'Función principal disponible';
        } else {
            echo '<p class="test-error"><i class="fas fa-times-circle"></i> La función cumplirSancionesAutomaticas() NO está disponible</p>';
            echo '<div class="alert alert-danger">El archivo existe pero la función no se cargó. Verifica que config.php incluya el archivo correctamente.</div>';
            $errores[] = 'Función no disponible';
        }
    } else {
        echo '<p class="test-error"><i class="fas fa-times-circle"></i> El archivo include/sanciones_functions.php NO EXISTE</p>';
        echo '<div class="alert alert-danger">Debes crear el archivo <code>admin/include/sanciones_functions.php</code> con el código proporcionado.</div>';
        $errores[] = 'Archivo de funciones no existe';
    }
    
    echo '</div></div>';
    
    // ===== TEST 2: Verificar tabla log_sanciones =====
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-primary text-white"><strong>TEST 2:</strong> Tabla log_sanciones</div>';
    echo '<div class="card-body">';
    
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'log_sanciones'");
        if ($stmt->rowCount() > 0) {
            echo '<p class="test-ok"><i class="fas fa-check-circle"></i> La tabla log_sanciones EXISTE</p>';
            $exitos[] = 'Tabla log_sanciones existe';
            
            // Verificar estructura
            $stmt = $db->query("DESCRIBE log_sanciones");
            $columnas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            echo '<p>Columnas: ' . implode(', ', $columnas) . '</p>';
            
            // Ver registros
            $stmt = $db->query("SELECT COUNT(*) as total FROM log_sanciones");
            $total = $stmt->fetch()['total'];
            echo "<p>Total de registros en log_sanciones: <strong>$total</strong></p>";
            
        } else {
            echo '<p class="test-error"><i class="fas fa-times-circle"></i> La tabla log_sanciones NO EXISTE</p>';
            echo '<div class="alert alert-danger">Debes ejecutar el SQL para crear la tabla. Ve al artefacto "SQL - Tabla log_sanciones y correcciones".</div>';
            $errores[] = 'Tabla log_sanciones no existe';
        }
    } catch (Exception $e) {
        echo '<p class="test-error"><i class="fas fa-times-circle"></i> Error: ' . $e->getMessage() . '</p>';
        $errores[] = 'Error al verificar tabla log_sanciones';
    }
    
    echo '</div></div>';
    
    // ===== TEST 3: Verificar columnas en tabla sanciones =====
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-primary text-white"><strong>TEST 3:</strong> Estructura tabla sanciones</div>';
    echo '<div class="card-body">';
    
    try {
        $stmt = $db->query("DESCRIBE sanciones");
        $columnas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $columnas_necesarias = ['id', 'jugador_id', 'tipo', 'partidos_suspension', 'partidos_cumplidos', 'activa', 'fecha_sancion'];
        $columnas_existentes = array_column($columnas, 'Field');
        
        $faltan = array_diff($columnas_necesarias, $columnas_existentes);
        
        if (empty($faltan)) {
            echo '<p class="test-ok"><i class="fas fa-check-circle"></i> La tabla sanciones tiene todas las columnas necesarias</p>';
            echo '<p>Columnas: ' . implode(', ', $columnas_existentes) . '</p>';
            $exitos[] = 'Tabla sanciones correcta';
        } else {
            echo '<p class="test-error"><i class="fas fa-times-circle"></i> Faltan columnas en la tabla sanciones</p>';
            echo '<div class="alert alert-danger">Columnas faltantes: <strong>' . implode(', ', $faltan) . '</strong></div>';
            $errores[] = 'Columnas faltantes en tabla sanciones';
        }
    } catch (Exception $e) {
        echo '<p class="test-error"><i class="fas fa-times-circle"></i> Error: ' . $e->getMessage() . '</p>';
        $errores[] = 'Error al verificar tabla sanciones';
    }
    
    echo '</div></div>';
    
    // ===== TEST 4: Verificar columna finalizado_at en partidos =====
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-primary text-white"><strong>TEST 4:</strong> Columna finalizado_at en partidos</div>';
    echo '<div class="card-body">';
    
    try {
        $stmt = $db->query("DESCRIBE partidos");
        $columnas = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Field');
        
        if (in_array('finalizado_at', $columnas)) {
            echo '<p class="test-ok"><i class="fas fa-check-circle"></i> La columna finalizado_at EXISTE en tabla partidos</p>';
            $exitos[] = 'Columna finalizado_at existe';
        } else {
            echo '<p class="test-warning"><i class="fas fa-exclamation-triangle"></i> La columna finalizado_at NO EXISTE (no es crítico pero recomendado)</p>';
            echo '<div class="alert alert-warning">Ejecuta: <code>ALTER TABLE partidos ADD COLUMN finalizado_at DATETIME NULL DEFAULT NULL AFTER estado;</code></div>';
            $advertencias[] = 'Columna finalizado_at no existe';
        }
    } catch (Exception $e) {
        echo '<p class="test-error"><i class="fas fa-times-circle"></i> Error: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div></div>';
    
    // ===== TEST 5: Verificar sanciones activas =====
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-primary text-white"><strong>TEST 5:</strong> Sanciones en el sistema</div>';
    echo '<div class="card-body">';
    
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM sanciones WHERE activa = 1");
        $total_activas = $stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM sanciones WHERE activa = 0");
        $total_cumplidas = $stmt->fetch()['total'];
        
        echo "<p><strong>Sanciones activas:</strong> $total_activas</p>";
        echo "<p><strong>Sanciones cumplidas:</strong> $total_cumplidas</p>";
        
        if ($total_activas > 0) {
            echo '<table class="table table-sm mt-3">';
            echo '<thead><tr><th>Jugador</th><th>Equipo</th><th>Tipo</th><th>Cumplidas/Total</th></tr></thead>';
            echo '<tbody>';
            
            $stmt = $db->query("
                SELECT j.apellido_nombre, e.nombre as equipo, s.tipo, s.partidos_cumplidos, s.partidos_suspension
                FROM sanciones s
                JOIN jugadores j ON s.jugador_id = j.id
                JOIN equipos e ON j.equipo_id = e.id
                WHERE s.activa = 1
                LIMIT 10
            ");
            
            while ($row = $stmt->fetch()) {
                echo "<tr>";
                echo "<td>{$row['apellido_nombre']}</td>";
                echo "<td>{$row['equipo']}</td>";
                echo "<td>{$row['tipo']}</td>";
                echo "<td>{$row['partidos_cumplidos']}/{$row['partidos_suspension']}</td>";
                echo "</tr>";
            }
            
            echo '</tbody></table>';
        }
        
    } catch (Exception $e) {
        echo '<p class="test-error"><i class="fas fa-times-circle"></i> Error: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div></div>';
    
    // ===== TEST 6: Simular descuento de sanciones =====
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-warning"><strong>TEST 6:</strong> Probar función de descuento</div>';
    echo '<div class="card-body">';
    
    if (function_exists('cumplirSancionesAutomaticas')) {
        // Buscar un partido finalizado reciente
        $stmt = $db->query("
            SELECT id, DATE_FORMAT(fecha_partido, '%d/%m/%Y') as fecha, 
                   el.nombre as local, ev.nombre as visitante
            FROM partidos p
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            WHERE p.estado = 'finalizado'
            ORDER BY p.fecha_partido DESC
            LIMIT 1
        ");
        
        if ($partido = $stmt->fetch()) {
            echo "<p>Último partido finalizado: <strong>{$partido['local']} vs {$partido['visitante']}</strong> ({$partido['fecha']})</p>";
            
            try {
                // NO ejecutar, solo mostrar que la función existe
                echo '<p class="test-ok"><i class="fas fa-check-circle"></i> La función está lista para usar</p>';
                echo '<div class="alert alert-info">Para probar manualmente, finaliza un partido desde control_fechas.php o partido_live.php</div>';
            } catch (Exception $e) {
                echo '<p class="test-error"><i class="fas fa-times-circle"></i> Error al probar: ' . $e->getMessage() . '</p>';
            }
        } else {
            echo '<p class="test-warning"><i class="fas fa-exclamation-triangle"></i> No hay partidos finalizados en el sistema</p>';
        }
    } else {
        echo '<p class="test-error"><i class="fas fa-times-circle"></i> No se puede probar: la función no está disponible</p>';
    }
    
    echo '</div></div>';
    
    // ===== TEST 7: Verificar archivos actualizados =====
    echo '<div class="card mb-3">';
    echo '<div class="card-header bg-primary text-white"><strong>TEST 7:</strong> Archivos actualizados</div>';
    echo '<div class="card-body">';
    
    // Verificar control_fechas.php
    $control_fechas = file_get_contents(__DIR__ . '/control_fechas.php');
    if (strpos($control_fechas, 'cumplirSancionesAutomaticas') !== false) {
        echo '<p class="test-ok"><i class="fas fa-check-circle"></i> control_fechas.php TIENE la integración</p>';
        $exitos[] = 'control_fechas.php actualizado';
    } else {
        echo '<p class="test-error"><i class="fas fa-times-circle"></i> control_fechas.php NO TIENE la integración</p>';
        echo '<div class="alert alert-danger">Debes actualizar el archivo control_fechas.php con el código del artefacto</div>';
        $errores[] = 'control_fechas.php sin actualizar';
    }
    
    // Verificar partido_live.php
    $partido_live_path = __DIR__ . '/../planillero/partido_live.php';
    if (file_exists($partido_live_path)) {
        $partido_live = file_get_contents($partido_live_path);
        if (strpos($partido_live, 'cumplirSancionesAutomaticas') !== false) {
            echo '<p class="test-ok"><i class="fas fa-check-circle"></i> partido_live.php TIENE la integración</p>';
            $exitos[] = 'partido_live.php actualizado';
        } else {
            echo '<p class="test-error"><i class="fas fa-times-circle"></i> partido_live.php NO TIENE la integración</p>';
            echo '<div class="alert alert-danger">Debes actualizar el archivo planillero/partido_live.php con el código del artefacto</div>';
            $errores[] = 'partido_live.php sin actualizar';
        }
    } else {
        echo '<p class="test-warning"><i class="fas fa-exclamation-triangle"></i> No se encontró planillero/partido_live.php</p>';
    }
    
    echo '</div></div>';
    
    // ===== RESUMEN FINAL =====
    echo '<div class="card">';
    echo '<div class="card-header bg-dark text-white"><strong>RESUMEN DEL DIAGNÓSTICO</strong></div>';
    echo '<div class="card-body">';
    
    echo '<h5 class="test-ok"><i class="fas fa-check-circle"></i> Exitosos: ' . count($exitos) . '</h5>';
    if (!empty($exitos)) {
        echo '<ul>';
        foreach ($exitos as $exito) {
            echo "<li>$exito</li>";
        }
        echo '</ul>';
    }
    
    if (!empty($advertencias)) {
        echo '<h5 class="test-warning"><i class="fas fa-exclamation-triangle"></i> Advertencias: ' . count($advertencias) . '</h5>';
        echo '<ul>';
        foreach ($advertencias as $advertencia) {
            echo "<li>$advertencia</li>";
        }
        echo '</ul>';
    }
    
    if (!empty($errores)) {
        echo '<h5 class="test-error"><i class="fas fa-times-circle"></i> Errores: ' . count($errores) . '</h5>';
        echo '<ul>';
        foreach ($errores as $error) {
            echo "<li>$error</li>";
        }
        echo '</ul>';
        
        echo '<div class="alert alert-danger mt-3">';
        echo '<h6>⚠️ ACCIÓN REQUERIDA:</h6>';
        echo '<ol>';
        if (in_array('Tabla log_sanciones no existe', $errores)) {
            echo '<li>Ejecuta el SQL para crear la tabla log_sanciones</li>';
        }
        if (in_array('Archivo de funciones no existe', $errores)) {
            echo '<li>Crea el archivo admin/include/sanciones_functions.php</li>';
        }
        if (in_array('control_fechas.php sin actualizar', $errores)) {
            echo '<li>Actualiza admin/control_fechas.php</li>';
        }
        if (in_array('partido_live.php sin actualizar', $errores)) {
            echo '<li>Actualiza planillero/partido_live.php</li>';
        }
        echo '</ol>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-success mt-3">';
        echo '<h5>✅ TODO ESTÁ LISTO</h5>';
        echo '<p>El sistema de descuento automático está correctamente configurado.</p>';
        echo '<p><strong>Próximo paso:</strong> Finaliza un partido y verifica que las sanciones se descuenten automáticamente.</p>';
        echo '</div>';
    }
    
    echo '</div></div>';
    ?>
    
    <div class="mt-4 mb-5">
        <a href="dashboard.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Volver al Dashboard</a>
        <a href="?" class="btn btn-primary"><i class="fas fa-sync"></i> Ejecutar Diagnóstico Nuevamente</a>
    </div>
</div>
</body>
</html>