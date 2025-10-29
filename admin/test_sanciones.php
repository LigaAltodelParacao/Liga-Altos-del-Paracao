<?php
/**
 * ARCHIVO DE PRUEBA - Verificar sistema de sanciones
 * Ubicación: admin/test_sanciones.php
 * 
 * Este archivo te permitirá ver exactamente qué está pasando
 */

require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    die('Acceso denegado');
}

$db = Database::getInstance()->getConnection();

echo '<pre style="background: #f5f5f5; padding: 20px; border-radius: 5px;">';
echo "<h2>🔍 TEST DEL SISTEMA DE SANCIONES</h2>\n\n";

// TEST 1: Verificar función existe
echo "═══════════════════════════════════════\n";
echo "TEST 1: Función cumplirSancionesAutomaticas()\n";
echo "═══════════════════════════════════════\n";

if (function_exists('cumplirSancionesAutomaticas')) {
    echo "✅ La función EXISTE\n\n";
} else {
    echo "❌ La función NO EXISTE\n";
    echo "⚠️  Verifica que admin/include/sanciones_functions.php esté cargado en config.php\n\n";
}

// TEST 2: Buscar sanciones de HOY
echo "═══════════════════════════════════════\n";
echo "TEST 2: Sanciones creadas HOY\n";
echo "═══════════════════════════════════════\n";

$stmt = $db->query("
    SELECT 
        s.id,
        s.fecha_sancion,
        s.tipo,
        s.partidos_cumplidos,
        s.partidos_suspension,
        s.activa,
        j.apellido_nombre,
        e.nombre as equipo
    FROM sanciones s
    JOIN jugadores j ON s.jugador_id = j.id
    JOIN equipos e ON j.equipo_id = e.id
    WHERE DATE(s.fecha_sancion) = CURDATE()
    ORDER BY s.id DESC
");

$sanciones_hoy = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($sanciones_hoy)) {
    echo "ℹ️  No hay sanciones creadas hoy\n\n";
} else {
    echo "Total: " . count($sanciones_hoy) . " sanción(es)\n\n";
    foreach ($sanciones_hoy as $s) {
        $estado = $s['activa'] ? '🔴 ACTIVA' : '✅ CUMPLIDA';
        echo "• {$s['apellido_nombre']} ({$s['equipo']})\n";
        echo "  Tipo: {$s['tipo']}\n";
        echo "  Cumplidas: {$s['partidos_cumplidos']}/{$s['partidos_suspension']}\n";
        echo "  Estado: $estado\n";
        echo "  Fecha: {$s['fecha_sancion']}\n\n";
    }
}

// TEST 3: Sanciones activas (no de hoy)
echo "═══════════════════════════════════════\n";
echo "TEST 3: Sanciones ACTIVAS (anteriores a hoy)\n";
echo "═══════════════════════════════════════\n";

$stmt = $db->query("
    SELECT 
        s.id,
        s.fecha_sancion,
        s.tipo,
        s.partidos_cumplidos,
        s.partidos_suspension,
        j.apellido_nombre,
        e.nombre as equipo
    FROM sanciones s
    JOIN jugadores j ON s.jugador_id = j.id
    JOIN equipos e ON j.equipo_id = e.id
    WHERE s.activa = 1
    AND DATE(s.fecha_sancion) < CURDATE()
    ORDER BY s.fecha_sancion DESC
");

$sanciones_antiguas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($sanciones_antiguas)) {
    echo "ℹ️  No hay sanciones activas de fechas anteriores\n\n";
} else {
    echo "Total: " . count($sanciones_antiguas) . " sanción(es)\n\n";
    foreach ($sanciones_antiguas as $s) {
        echo "• {$s['apellido_nombre']} ({$s['equipo']})\n";
        echo "  Tipo: {$s['tipo']}\n";
        echo "  Cumplidas: {$s['partidos_cumplidos']}/{$s['partidos_suspension']}\n";
        echo "  Fecha sanción: {$s['fecha_sancion']}\n\n";
    }
}

// TEST 4: Último partido finalizado HOY
echo "═══════════════════════════════════════\n";
echo "TEST 4: Partidos finalizados HOY\n";
echo "═══════════════════════════════════════\n";

$stmt = $db->query("
    SELECT 
        p.id,
        p.fecha_partido,
        p.finalizado_at,
        el.nombre as local,
        ev.nombre as visitante,
        CONCAT(p.goles_local, '-', p.goles_visitante) as resultado
    FROM partidos p
    JOIN equipos el ON p.equipo_local_id = el.id
    JOIN equipos ev ON p.equipo_visitante_id = ev.id
    WHERE p.estado = 'finalizado'
    AND DATE(p.finalizado_at) = CURDATE()
    ORDER BY p.finalizado_at DESC
");

$partidos_hoy = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($partidos_hoy)) {
    echo "ℹ️  No hay partidos finalizados hoy\n\n";
} else {
    echo "Total: " . count($partidos_hoy) . " partido(s)\n\n";
    foreach ($partidos_hoy as $p) {
        echo "• Partido #{$p['id']}: {$p['local']} {$p['resultado']} {$p['visitante']}\n";
        echo "  Finalizado: {$p['finalizado_at']}\n\n";
    }
}

// TEST 5: Log de sanciones cumplidas
echo "═══════════════════════════════════════\n";
echo "TEST 5: Log de cumplimientos (últimos 10)\n";
echo "═══════════════════════════════════════\n";

$stmt = $db->query("
    SELECT 
        ls.id,
        ls.fechas_cumplidas,
        ls.fecha_registro,
        j.apellido_nombre,
        e.nombre as equipo,
        p.id as partido_id
    FROM log_sanciones ls
    JOIN sanciones s ON ls.sancion_id = s.id
    JOIN jugadores j ON s.jugador_id = j.id
    JOIN equipos e ON j.equipo_id = e.id
    JOIN partidos p ON ls.partido_id = p.id
    ORDER BY ls.fecha_registro DESC
    LIMIT 10
");

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($logs)) {
    echo "ℹ️  No hay registros de cumplimiento\n\n";
} else {
    foreach ($logs as $log) {
        echo "• {$log['apellido_nombre']} ({$log['equipo']})\n";
        echo "  Fechas cumplidas: {$log['fechas_cumplidas']}\n";
        echo "  Partido: #{$log['partido_id']}\n";
        echo "  Fecha: {$log['fecha_registro']}\n\n";
    }
}

// TEST 6: Verificar eventos_partido con doble amarilla
echo "═══════════════════════════════════════\n";
echo "TEST 6: Eventos de tarjetas HOY\n";
echo "═══════════════════════════════════════\n";

$stmt = $db->query("
    SELECT 
        ep.partido_id,
        ep.tipo_evento,
        ep.descripcion,
        j.apellido_nombre,
        COUNT(*) as cantidad
    FROM eventos_partido ep
    JOIN partidos p ON ep.partido_id = p.id
    JOIN jugadores j ON ep.jugador_id = j.id
    WHERE DATE(p.finalizado_at) = CURDATE()
    AND ep.tipo_evento IN ('amarilla', 'roja')
    GROUP BY ep.partido_id, ep.jugador_id, ep.tipo_evento, j.apellido_nombre
    HAVING cantidad > 1 OR ep.tipo_evento = 'roja'
");

$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($eventos)) {
    echo "ℹ️  No hay eventos de tarjetas hoy\n\n";
} else {
    foreach ($eventos as $ev) {
        $icono = $ev['tipo_evento'] == 'amarilla' ? '🟨' : '🟥';
        echo "$icono {$ev['apellido_nombre']}: {$ev['tipo_evento']} x{$ev['cantidad']} (Partido #{$ev['partido_id']})\n";
        if ($ev['descripcion']) {
            echo "  Nota: {$ev['descripcion']}\n";
        }
    }
    echo "\n";
}

echo "═══════════════════════════════════════\n";
echo "FIN DEL TEST\n";
echo "═══════════════════════════════════════\n";
echo "</pre>";

echo '<div style="margin: 20px;">';
echo '<a href="control_fechas.php" class="btn btn-primary">Volver a Control de Fechas</a> ';
echo '<a href="?" class="btn btn-secondary">Refrescar Test</a>';
echo '</div>';
?>