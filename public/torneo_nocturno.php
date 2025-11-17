<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Cargar config.php desde la raíz
require_once __DIR__ . '/../config.php';
$db = Database::getInstance()->getConnection();

// Campeonatos disponibles (solo zonales / nocturnos)
$stmt = $db->query("
    SELECT id, nombre 
    FROM campeonatos 
    WHERE activo = 1 
      AND (tipo_campeonato = 'zonal' OR es_torneo_nocturno = 1)
    ORDER BY fecha_inicio DESC, id DESC
");
$campeonatosDisponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$campeonatoIdSeleccionado = isset($_GET['campeonato_id']) ? (int)$_GET['campeonato_id'] : null;
$campeonato = null;

if (!empty($campeonatosDisponibles)) {
    $idsDisponibles = array_column($campeonatosDisponibles, 'id');
    if (!$campeonatoIdSeleccionado || !in_array($campeonatoIdSeleccionado, $idsDisponibles, true)) {
        $campeonatoIdSeleccionado = $campeonatosDisponibles[0]['id'];
    }
    $stmt = $db->prepare("SELECT * FROM campeonatos WHERE id = ? LIMIT 1");
    $stmt->execute([$campeonatoIdSeleccionado]);
    $campeonato = $stmt->fetch(PDO::FETCH_ASSOC);
}

// IDs de categorías de este campeonato
$categorias = [];
if ($campeonato) {
    $stmt = $db->prepare("SELECT id, nombre FROM categorias WHERE campeonato_id = ? AND activa = 1 ORDER BY nombre");
    $stmt->execute([$campeonato['id']]);
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$categoriaSeleccionadaId = null;
$categoriaSeleccionada = null;
if (!empty($categorias)) {
    $categoriaSeleccionadaId = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : null;
    $idsDisponibles = array_column($categorias, 'id');
    if (!$categoriaSeleccionadaId || !in_array($categoriaSeleccionadaId, $idsDisponibles, true)) {
        $categoriaSeleccionadaId = $categorias[0]['id'];
    }
    foreach ($categorias as $cat) {
        if ((int)$cat['id'] === (int)$categoriaSeleccionadaId) {
            $categoriaSeleccionada = $cat;
            break;
        }
    }
}

$categoria_ids = $categoriaSeleccionadaId ? [$categoriaSeleccionadaId] : array_column($categorias, 'id');

// Formatos de zonas
$formatos = [];
if ($campeonato) {
    $sqlFormatos = "
        SELECT * 
        FROM campeonatos_formato 
        WHERE campeonato_id = ? 
          AND activo = 1
    ";
    $paramsFormatos = [$campeonato['id']];
    if ($categoriaSeleccionadaId) {
        $sqlFormatos .= " AND (categoria_id = ? OR categoria_id IS NULL)";
        $paramsFormatos[] = $categoriaSeleccionadaId;
    }
    $sqlFormatos .= " ORDER BY created_at DESC";
    $stmt = $db->prepare($sqlFormatos);
    $stmt->execute($paramsFormatos);
    $formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($categoriaSeleccionadaId) {
        $formatos = array_values(array_filter($formatos, function ($formato) use ($categoriaSeleccionadaId) {
            return !isset($formato['categoria_id']) || (int)$formato['categoria_id'] === (int)$categoriaSeleccionadaId;
        }));
        if (empty($formatos)) {
            $stmt = $db->prepare("
                SELECT DISTINCT cf.*
                FROM campeonatos_formato cf
                JOIN zonas z ON z.formato_id = cf.id
                JOIN equipos_zonas ez ON ez.zona_id = z.id
                JOIN equipos e ON e.id = ez.equipo_id
                WHERE cf.campeonato_id = ?
                  AND cf.activo = 1
                  AND e.categoria_id = ?
            ");
            $stmt->execute([$campeonato['id'], $categoriaSeleccionadaId]);
            $formatos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

// ================ CARGAR FUNCIONES DE TORNEOS CON ZONAS ================
$ruta_funciones_zonas = __DIR__ . '/../admin/funciones_torneos_zonas.php';
if (file_exists($ruta_funciones_zonas)) {
    require_once $ruta_funciones_zonas;
} else {
    // Si no existe el archivo de funciones, mostrar error
    error_log("Error: No se encontró el archivo funciones_torneos_zonas.php");
}

// ================ POSICIONES POR ZONAS ================
$zonas_data = [];
$formato_fixture_zonas = [];
$formato_fases = [];
$formato_partidos_eliminatorios = [];
$zona_ids = [];
$fase_ids = [];

if ($campeonato && !empty($formatos)) {
    foreach ($formatos as $formato) {
        try {
            $stmtZ = $db->prepare("SELECT * FROM zonas WHERE formato_id = ? ORDER BY orden");
            $stmtZ->execute([$formato['id']]);
            $zonas = $stmtZ->fetchAll(PDO::FETCH_ASSOC);
            foreach ($zonas as $zona) {
                $zona_ids[] = (int)$zona['id'];
            }
            
            // Actualizar estadísticas de todas las zonas antes de mostrar
            // La función obtenerTablaPosicionesZona ya se encarga de actualizar automáticamente
            // pero actualizamos también los equipos que ya existen para asegurar que estén actualizados
            if (function_exists('actualizarEstadisticasZona')) {
                foreach ($zonas as $zona) {
                    // Obtener todos los equipos de la zona
                    $stmtEq = $db->prepare("SELECT equipo_id FROM equipos_zonas WHERE zona_id = ?");
                    $stmtEq->execute([$zona['id']]);
                    $equipos_zona = $stmtEq->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Actualizar estadísticas de cada equipo que ya existe
                    foreach ($equipos_zona as $eq) {
                        actualizarEstadisticasZona($zona['id'], $eq['equipo_id'], $db);
                    }
                    
                    // También obtener equipos que tienen partidos pero pueden no estar en equipos_zonas
                    $stmtEqPartidos = $db->prepare("
                        SELECT DISTINCT equipo_id 
                        FROM (
                            SELECT equipo_local_id as equipo_id FROM partidos 
                            WHERE zona_id = ? AND tipo_torneo = 'zona' AND equipo_local_id IS NOT NULL
                            UNION
                            SELECT equipo_visitante_id as equipo_id FROM partidos 
                            WHERE zona_id = ? AND tipo_torneo = 'zona' AND equipo_visitante_id IS NOT NULL
                        ) as equipos_partidos
                    ");
                    $stmtEqPartidos->execute([$zona['id'], $zona['id']]);
                    $equipos_partidos = $stmtEqPartidos->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Actualizar estadísticas de equipos que tienen partidos
                    foreach ($equipos_partidos as $eq) {
                        if ($eq['equipo_id']) {
                            actualizarEstadisticasZona($zona['id'], $eq['equipo_id'], $db);
                        }
                    }
                }
            }
            
            foreach ($zonas as &$z) {
                $z['tabla'] = [];
                if (function_exists('obtenerTablaPosicionesZona')) {
                    $tabla_zona = obtenerTablaPosicionesZona($z['id'], $db);
                    $z['tabla'] = is_array($tabla_zona) ? $tabla_zona : [];
                } else {
                    // Debería ser imposible llegar aquí gracias a la función embebida
                    $z['tabla'] = [];
                }

                // Obtener fixture por zona
                $stmt = $db->prepare("
                    SELECT p.*, el.nombre as equipo_local, el.logo as logo_local, 
                           ev.nombre as equipo_visitante, ev.logo as logo_visitante, 
                           c.nombre as cancha, f.numero_fecha, z.nombre as zona_nombre
                    FROM partidos p
                    JOIN zonas z ON p.zona_id = z.id
                    JOIN equipos el ON p.equipo_local_id = el.id
                    JOIN equipos ev ON p.equipo_visitante_id = ev.id
                    LEFT JOIN canchas c ON p.cancha_id = c.id
                    LEFT JOIN fechas f ON p.fecha_id = f.id
                    WHERE p.zona_id = ? AND p.tipo_torneo = 'zona'
                    ORDER BY p.jornada_zona ASC, p.fecha_partido ASC, p.hora_partido ASC
                ");
                $stmt->execute([$z['id']]);
                $partidos_zona = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $formato_fixture_zonas[$formato['id']][] = [
                    'zona' => $z,
                    'partidos' => $partidos_zona
                ];
            }
            $zonas_data[] = ['formato' => $formato, 'zonas' => $zonas];

            // Fases eliminatorias y partidos
            $stmtF = $db->prepare("
                SELECT * FROM fases_eliminatorias 
                WHERE formato_id = ? 
                ORDER BY orden
            ");
            $stmtF->execute([$formato['id']]);
            $fases = $stmtF->fetchAll(PDO::FETCH_ASSOC);
            $formato_fases[$formato['id']] = $fases;

            foreach ($fases as $fase) {
                $fase_ids[] = (int)$fase['id'];
                $stmtPartidos = $db->prepare("
                    SELECT 
                        p.*,
                        el.nombre as equipo_local_nombre,
                        el.logo as logo_local,
                        ev.nombre as equipo_visitante_nombre,
                        ev.logo as logo_visitante,
                        c.nombre as cancha_nombre
                    FROM partidos p
                    LEFT JOIN equipos el ON p.equipo_local_id = el.id
                    LEFT JOIN equipos ev ON p.equipo_visitante_id = ev.id
                    LEFT JOIN canchas c ON p.cancha_id = c.id
                    WHERE p.fase_eliminatoria_id = ? AND p.tipo_torneo = 'eliminatoria'
                    ORDER BY p.numero_llave
                ");
                $stmtPartidos->execute([$fase['id']]);
                $formato_partidos_eliminatorios[$formato['id']][$fase['id']] = $stmtPartidos->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            error_log("Error en zonas del formato {$formato['id']}: " . $e->getMessage());
        }
    }
}
$zona_ids = array_values(array_unique(array_filter($zona_ids)));
$fase_ids = array_values(array_unique(array_filter($fase_ids)));

$zonas_data_con_zonas = array_values(array_filter($zonas_data, function ($pack) {
    return !empty($pack['zonas']);
}));

// ================ CONSTRUIR FIXTURE ZONAS ================
$fixture_zonas = [];
foreach ($formato_fixture_zonas as $formato_id => $zonas_fixture) {
    foreach ($zonas_fixture as $zona_fixture) {
        $fixture_zonas[] = $zona_fixture;
    }
}

// ================ TABLA GENERAL (sin zonas) ================
$tabla_posiciones_general = [];
if (empty($formatos) && !empty($categoria_ids)) {
    $placeholder_cat = implode(',', array_fill(0, count($categoria_ids), '?'));
    $sql = "
        SELECT 
            e.id as equipo_id,
            e.nombre as equipo,
            e.logo,
            COALESCE(COUNT(DISTINCT CASE WHEN p.estado = 'finalizado' THEN p.id END), 0) as partidos_jugados,
            COALESCE(SUM(CASE 
                WHEN p.estado = 'finalizado' 
                     AND ((p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante) OR 
                          (p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local))
                THEN 1 ELSE 0 END), 0) as ganados,
            COALESCE(SUM(CASE 
                WHEN p.estado = 'finalizado' AND p.goles_local = p.goles_visitante
                THEN 1 ELSE 0 END), 0) as empatados,
            COALESCE(SUM(CASE 
                WHEN p.estado = 'finalizado'
                     AND ((p.equipo_local_id = e.id AND p.goles_local < p.goles_visitante) OR 
                          (p.equipo_visitante_id = e.id AND p.goles_visitante < p.goles_local))
                THEN 1 ELSE 0 END), 0) as perdidos,
            COALESCE(SUM(CASE 
                WHEN p.estado = 'finalizado' AND p.equipo_local_id = e.id THEN p.goles_local 
                WHEN p.estado = 'finalizado' AND p.equipo_visitante_id = e.id THEN p.goles_visitante 
                ELSE 0 END), 0) as goles_favor,
            COALESCE(SUM(CASE 
                WHEN p.estado = 'finalizado' AND p.equipo_local_id = e.id THEN p.goles_visitante 
                WHEN p.estado = 'finalizado' AND p.equipo_visitante_id = e.id THEN p.goles_local 
                ELSE 0 END), 0) as goles_contra,
            (COALESCE(SUM(CASE 
                WHEN p.estado = 'finalizado' AND p.equipo_local_id = e.id THEN p.goles_local 
                WHEN p.estado = 'finalizado' AND p.equipo_visitante_id = e.id THEN p.goles_visitante 
                ELSE 0 END), 0) - COALESCE(SUM(CASE 
                WHEN p.estado = 'finalizado' AND p.equipo_local_id = e.id THEN p.goles_visitante 
                WHEN p.estado = 'finalizado' AND p.equipo_visitante_id = e.id THEN p.goles_local 
                ELSE 0 END), 0)) as diferencia_goles,
            COALESCE(SUM(CASE 
                WHEN p.estado = 'finalizado' 
                     AND ((p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante) OR 
                          (p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local))
                THEN 3
                WHEN p.estado = 'finalizado' AND p.goles_local = p.goles_visitante
                THEN 1 ELSE 0 END), 0) as puntos
        FROM equipos e
        LEFT JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id) 
                              AND p.estado = 'finalizado'
                              AND (p.tipo_torneo = 'zona' OR p.tipo_torneo = 'eliminatoria')
        LEFT JOIN fechas f ON p.fecha_id = f.id AND f.categoria_id IN ($placeholder_cat)
        WHERE e.categoria_id IN ($placeholder_cat) AND e.activo = 1
        GROUP BY e.id, e.nombre, e.logo
        ORDER BY puntos DESC, diferencia_goles DESC, goles_favor DESC, e.nombre ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($categoria_ids, $categoria_ids));
    $tabla_posiciones_general = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ================ OTRAS SECCIONES (Resultados, Fixture, etc.) ================
// (Se mantienen exactamente como en tu archivo original, sin cambios)
$resultados = $fixture = $goleadores = $fairplay = $sanciones = $equipos = [];

$estadisticas_zonales = [
    'partidos' => 0,
    'goles' => 0,
    'promedio' => 0,
    'amarillas' => 0,
    'rojas' => 0,
];

if ($campeonato && !empty($categoria_ids)) {
    $placeholderStats = implode(',', array_fill(0, count($categoria_ids), '?'));

    $sqlPartidos = "
        SELECT 
            COUNT(p.id) AS partidos,
            COALESCE(SUM(p.goles_local + p.goles_visitante), 0) AS goles
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        WHERE f.categoria_id IN ($placeholderStats)
          AND p.estado = 'finalizado'
          AND p.tipo_torneo IN ('zona','eliminatoria')
    ";
    $stmt = $db->prepare($sqlPartidos);
    $stmt->execute($categoria_ids);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $estadisticas_zonales['partidos'] = (int)($row['partidos'] ?? 0);
    $estadisticas_zonales['goles'] = (int)($row['goles'] ?? 0);
    $estadisticas_zonales['promedio'] = $estadisticas_zonales['partidos'] > 0
        ? round($estadisticas_zonales['goles'] / $estadisticas_zonales['partidos'], 2)
        : 0;

    $sqlTarjetas = "
        SELECT 
            COALESCE(SUM(CASE WHEN ep.tipo_evento = 'amarilla' THEN 1 END), 0) AS amarillas,
            COALESCE(SUM(CASE WHEN ep.tipo_evento = 'roja' THEN 1 END), 0) AS rojas
        FROM eventos_partido ep
        JOIN partidos p ON ep.partido_id = p.id
        JOIN fechas f ON p.fecha_id = f.id
        WHERE f.categoria_id IN ($placeholderStats)
          AND p.tipo_torneo IN ('zona','eliminatoria')
    ";
    $stmt = $db->prepare($sqlTarjetas);
    $stmt->execute($categoria_ids);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $estadisticas_zonales['amarillas'] = (int)($row['amarillas'] ?? 0);
    $estadisticas_zonales['rojas'] = (int)($row['rojas'] ?? 0);
}

if (!empty($categoria_ids)) {
    $placeholder_cat = implode(',', array_fill(0, count($categoria_ids), '?'));
    // Resultados - Solo partidos de zonas y eliminatorias
    if (!empty($zona_ids)) {
        $placeholder_zona = implode(',', array_fill(0, count($zona_ids), '?'));
        $sql = "
            SELECT p.*, el.nombre as equipo_local, el.logo as logo_local, ev.nombre as equipo_visitante, ev.logo as logo_visitante, 
                   c.nombre as cancha, f.numero_fecha, z.nombre as zona_nombre
            FROM partidos p
            LEFT JOIN zonas z ON p.zona_id = z.id
            LEFT JOIN fechas f ON p.fecha_id = f.id
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            LEFT JOIN canchas c ON p.cancha_id = c.id
            WHERE ((p.zona_id IN ($placeholder_zona) AND p.tipo_torneo = 'zona') 
                   OR (p.tipo_torneo = 'eliminatoria' AND f.categoria_id IN ($placeholder_cat)))
              AND p.estado = 'finalizado'
            ORDER BY p.finalizado_at DESC, p.fecha_partido DESC, p.hora_partido DESC
            LIMIT 20
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute(array_merge($zona_ids, $categoria_ids));
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $resultados = [];
    }

    // Fixture normal - Solo para torneos sin zonas (sin formatos)
    // Si hay formatos (zonas), siempre usar fixture_zonas y no buscar fixture normal
    if (empty($formatos)) {
        // Solo buscar fixture normal si no hay formatos con zonas
        $sql = "
            SELECT p.*, el.nombre as equipo_local, el.logo as logo_local, ev.nombre as equipo_visitante, ev.logo as logo_visitante, 
                   c.nombre as cancha, f.numero_fecha
            FROM partidos p
            JOIN fechas f ON p.fecha_id = f.id
            JOIN equipos el ON p.equipo_local_id = el.id
            JOIN equipos ev ON p.equipo_visitante_id = ev.id
            LEFT JOIN canchas c ON p.cancha_id = c.id
            WHERE f.categoria_id IN ($placeholder_cat) 
              AND (p.tipo_torneo = 'zona' OR p.tipo_torneo = 'eliminatoria' OR p.tipo_torneo IS NULL)
              AND p.estado IN ('pendiente','programado','sin_asignar','reprogramado')
            ORDER BY p.fecha_partido ASC, p.hora_partido ASC
            LIMIT 20
        ";
        $stmt = $db->prepare($sql);
        $stmt->execute($categoria_ids);
        $fixture = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Si hay formatos (zonas), no usar fixture normal
        $fixture = [];
    }

    // Goleadores
    $sql = "
        SELECT j.id as jugador_id, j.apellido_nombre, e.nombre as equipo, COUNT(ev.id) as goles
        FROM eventos_partido ev
        JOIN partidos p ON ev.partido_id = p.id
        JOIN fechas f ON p.fecha_id = f.id
        JOIN jugadores j ON ev.jugador_id = j.id
        JOIN equipos e ON j.equipo_id = e.id
        WHERE ev.tipo_evento = 'gol' 
          AND f.categoria_id IN ($placeholder_cat)
          AND (p.tipo_torneo = 'zona' OR p.tipo_torneo = 'eliminatoria')
        GROUP BY j.id, j.apellido_nombre, e.nombre
        HAVING goles > 0
        ORDER BY goles DESC, j.apellido_nombre ASC
        LIMIT 50
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute($categoria_ids);
    $goleadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fairplay
    $sql = "
        SELECT 
            e.id,
            e.nombre AS equipo,
            e.logo,
            e.color_camiseta,
            COUNT(DISTINCT CASE WHEN p.estado = 'finalizado' THEN p.id END) AS partidos_jugados,
            COALESCE(SUM(CASE WHEN ep.tipo_evento = 'amarilla' THEN 1 ELSE 0 END), 0) AS amarillas,
            COALESCE(SUM(CASE 
                WHEN ep.tipo_evento = 'roja' AND ep.observaciones LIKE '%doble%amarilla%' THEN 1 
                ELSE 0 
            END), 0) AS rojas_doble,
            COALESCE(SUM(CASE 
                WHEN ep.tipo_evento = 'roja' AND (ep.observaciones NOT LIKE '%doble%amarilla%' OR ep.observaciones IS NULL) THEN 1 
                ELSE 0 
            END), 0) AS rojas_directa,
            COALESCE(SUM(CASE WHEN ep.tipo_evento = 'roja' THEN 1 ELSE 0 END), 0) AS rojas_total,
            (COALESCE(SUM(CASE WHEN ep.tipo_evento = 'amarilla' THEN 1 ELSE 0 END), 0) * 1 +
             COALESCE(SUM(CASE 
                WHEN ep.tipo_evento = 'roja' AND ep.observaciones LIKE '%doble%amarilla%' THEN 1 
                ELSE 0 
             END), 0) * 3 +
             COALESCE(SUM(CASE 
                WHEN ep.tipo_evento = 'roja' AND (ep.observaciones NOT LIKE '%doble%amarilla%' OR ep.observaciones IS NULL) THEN 1 
                ELSE 0 
             END), 0) * 5) AS puntos
        FROM equipos e
        LEFT JOIN jugadores j ON j.equipo_id = e.id
        LEFT JOIN eventos_partido ep ON ep.jugador_id = j.id
        LEFT JOIN partidos p ON ep.partido_id = p.id AND p.estado = 'finalizado'
        LEFT JOIN fechas f ON p.fecha_id = f.id
        WHERE e.categoria_id IN ($placeholder_cat) AND e.activo = 1
          AND (f.categoria_id IN ($placeholder_cat) OR f.categoria_id IS NULL)
          AND (p.tipo_torneo = 'zona' OR p.tipo_torneo = 'eliminatoria' OR p.tipo_torneo IS NULL OR p.id IS NULL)
        GROUP BY e.id, e.nombre, e.logo, e.color_camiseta        
        ORDER BY puntos ASC, amarillas ASC, e.nombre ASC
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($categoria_ids, $categoria_ids));
    $fairplay = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Equipos
    $sql = "SELECT id, nombre, logo FROM equipos WHERE categoria_id IN ($placeholder_cat) AND activo = 1 ORDER BY nombre";
    $stmt = $db->prepare($sql);
    $stmt->execute($categoria_ids);
    $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Sanciones
if ($campeonato) {
    try {
        $sql = "
            SELECT s.*, j.apellido_nombre, j.dni, e.nombre as equipo, e.logo as equipo_logo, c.nombre as categoria,
                   (s.partidos_suspension - s.partidos_cumplidos) as fechas_restantes,
                   CASE 
                       WHEN s.activa = 1 THEN 'Activa'
                       ELSE 'Cumplida'
                   END as estado_texto
            FROM sanciones s
            JOIN jugadores j ON s.jugador_id = j.id
            JOIN equipos e ON j.equipo_id = e.id
            JOIN categorias c ON e.categoria_id = c.id
            WHERE c.campeonato_id = ? " . ($categoriaSeleccionadaId ? "AND c.id = ? " : "") . "
              AND s.activa = 1
            ORDER BY s.fecha_sancion DESC, s.activa DESC
        ";
        $stmt = $db->prepare($sql);
        $params = [$campeonato['id']];
        if ($categoriaSeleccionadaId) {
            $params[] = $categoriaSeleccionadaId;
        }
        $stmt->execute($params);
        $sanciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error en sanciones: " . $e->getMessage());
        $sanciones = [];
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($campeonato['nombre'] ?? 'Torneo Nocturno'); ?> - Altos del Paracao</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* ... (todo tu CSS exactamente igual, sin cambios) ... */
        :root {
            --primary-color: #198754;
            --dark-bg: #1a1a1a;
            --card-bg: #ffffff;
            --text-muted: #6c757d;
            --border-light: #e9ecef;
            --shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .hero-section {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            box-shadow: 0 4px 16px rgba(0,0,0,0.1);
        }
        .nav-tabs {
            background: #ffffff;
            border-radius: 12px;
            padding: 8px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-light);
        }
        .nav-tabs .nav-link {
            border: none;
            border-radius: 8px;
            color: var(--text-muted);
            font-weight: 500;
            padding: 12px 20px;
            margin: 0 2px;
            transition: all 0.3s ease;
        }
        .nav-tabs .nav-link:hover {
            background: rgba(25, 135, 84, 0.1);
            color: var(--primary-color);
        }
        .nav-tabs .nav-link.active {
            background: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(25, 135, 84, 0.3);
        }
        .tab-content {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-light);
            margin-top: 20px;
        }
        .tab-pane {
            padding: 30px;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            border: 1px solid var(--border-light);
        }
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #146c43 100%);
            color: white;
            border: none;
            border-radius: 12px 12px 0 0 !important;
            padding: 15px 20px;
            font-weight: 600;
        }
        .card-body {
            padding: 25px;
        }
        .table {
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            width: 100%;
            table-layout: fixed;
            margin-bottom: 0;
        }
        .table thead th {
            background: #f8f9fa;
            border: none;
            font-weight: 600;
            color: #495057;
            padding: 10px 6px;
            font-size: 0.85rem;
            text-align: center;
            vertical-align: middle;
        }
        .table tbody td {
            padding: 8px 6px;
            border-color: var(--border-light);
            vertical-align: middle;
            font-size: 0.85rem;
        }
        #tab-fixture .table tbody td:nth-child(3) span,
        #tab-fixture .table tbody td:nth-child(5) span,
        #tab-resultados .table tbody td:nth-child(2),
        #tab-resultados .table tbody td:nth-child(4),
        #tab-goleadores .table tbody td:nth-child(1) span,
        #tab-sanciones .table tbody td:nth-child(2),
        #tab-sanciones .table tbody td:nth-child(3) span {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .stats-card .table {
            font-size: 0.85rem;
        }
        .stats-card .table thead th:first-child,
        .stats-card .table tbody td:first-child {
            width: 50px;
            min-width: 50px;
            max-width: 50px;
            padding: 8px 4px;
        }
        .stats-card .table thead th:nth-child(2),
        .stats-card .table tbody td:nth-child(2) {
            width: auto;
            min-width: 200px;
            text-align: left;
            padding: 8px 6px;
        }
        .stats-card .table thead th:nth-child(3),
        .stats-card .table tbody td:nth-child(3),
        .stats-card .table thead th:nth-child(4),
        .stats-card .table tbody td:nth-child(4),
        .stats-card .table thead th:nth-child(5),
        .stats-card .table tbody td:nth-child(5),
        .stats-card .table thead th:nth-child(6),
        .stats-card .table tbody td:nth-child(6),
        .stats-card .table thead th:nth-child(7),
        .stats-card .table tbody td:nth-child(7),
        .stats-card .table thead th:nth-child(8),
        .stats-card .table tbody td:nth-child(8),
        .stats-card .table thead th:nth-child(9),
        .stats-card .table tbody td:nth-child(9),
        .stats-card .table thead th:nth-child(10),
        .stats-card .table tbody td:nth-child(10) {
            width: 55px;
            min-width: 55px;
            max-width: 55px;
            text-align: center;
            padding: 8px 4px;
        }
        .card-body .table thead th:first-child,
        .card-body .table tbody td:first-child {
            width: 45px;
            min-width: 45px;
            max-width: 45px;
        }
        .card-body .table thead th:nth-child(2),
        .card-body .table tbody td:nth-child(2) {
            width: auto;
            min-width: 150px;
        }
        .card-body .table thead th:nth-child(n+3),
        .card-body .table tbody td:nth-child(n+3) {
            width: 55px;
            min-width: 55px;
            max-width: 55px;
            text-align: center;
        }
        .table tbody tr:hover {
            background: rgba(25, 135, 84, 0.05);
        }
        .table-striped tbody tr:nth-of-type(odd) {
            background: rgba(248, 249, 250, 0.7);
        }
        .team-logo {
            width: 28px;
            height: 28px;
            object-fit: contain;
            border-radius: 4px;
            background: #f8f9fa;
            border: 1px solid var(--border-light);
        }
        .team-initial {
            width: 28px;
            height: 28px;
            border-radius: 4px;
            background: var(--primary-color);
            color: white;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .alert {
            border: none;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .alert-info {
            background: #e7f3ff;
            color: #0c5460;
            border-left: 4px solid #17a2b8;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border-left: 4px solid #ffc107;
        }
        .badge {
            padding: 6px 12px;
            font-weight: 600;
            border-radius: 6px;
        }
        .stats-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
            overflow: hidden;
            max-width: 100%;
        }
        .stats-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .stats-card h5 {
            font-size: 1.1rem;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 3px solid var(--primary-color);
            font-weight: 600;
        }
        .stats-card h6 {
            font-size: 1rem;
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--primary-color);
        }
        .stats-card .table-responsive {
            overflow-x: visible !important;
            margin: 0;
            padding: 0;
            width: 100%;
            max-width: 100%;
        }
        .stats-card .table {
            width: 100%;
            max-width: 100%;
            table-layout: fixed;
        }
        .footer {
            background: #2c3e50;
            color: white;
            border-radius: 12px;
            margin: 30px 0 20px 0;
            padding: 30px;
            text-align: center;
        }
        .footer h5 {
            color: white;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            h1, h2 {
                font-size: 1.5rem;
            }
            .tab-pane {
                padding: 15px 10px;
            }
            .nav-tabs {
                overflow-x: auto;
                flex-wrap: nowrap;
                white-space: nowrap;
                -webkit-overflow-scrolling: touch;
            }
            .nav-tabs .nav-link {
                padding: 8px 12px;
                font-size: 0.85rem;
            }
            .card-body {
                padding: 15px 10px;
            }
            .table-responsive {
                font-size: 0.75rem;
                overflow-x: visible !important;
            }
            .table {
                table-layout: fixed;
                width: 100%;
            }
            .table th, .table td {
                padding: 0.4rem 0.25rem;
                font-size: 0.75rem;
            }
            .table thead th:first-child,
            .table tbody td:first-child {
                width: 40px;
                min-width: 40px;
                max-width: 40px;
            }
            .table thead th:nth-child(2),
            .table tbody td:nth-child(2) {
                width: auto;
                min-width: 120px;
            }
            .table thead th:nth-child(n+3),
            .table tbody td:nth-child(n+3) {
                width: 50px;
                min-width: 50px;
                max-width: 50px;
            }
            .table th.d-none-mobile,
            .table td.d-none-mobile {
                display: none;
            }
            .table th.hide-xs,
            .table td.hide-xs {
                display: none;
            }
            .table img {
                width: 20px !important;
                height: 20px !important;
            }
            .team-logo, .team-initial {
                width: 22px;
                height: 22px;
                font-size: 0.7rem;
            }
            .col-lg-6, .col-md-6, .col-md-4, .col-lg-3 {
                margin-bottom: 0.75rem;
            }
            .stats-card {
                padding: 15px;
            }
            .hero-section {
                padding: 1rem;
            }
        }
        @media (max-width: 576px) {
            h1, h2 {
                font-size: 1.25rem;
            }
            .tab-pane {
                padding: 10px 5px;
            }
            .nav-tabs .nav-link {
                padding: 6px 10px;
                font-size: 0.8rem;
            }
            .table th, .table td {
                padding: 0.4rem 0.2rem;
                font-size: 0.75rem;
            }
            .team-logo, .team-initial {
                width: 18px;
                height: 18px;
                font-size: 0.65rem;
            }
            .col-6 {
                margin-bottom: 0.5rem;
            }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fairplay-badge {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 1.1rem;
        }
        .badge-gold { background: linear-gradient(135deg, #FFD700, #FFA500); color: #000; box-shadow: 0 4px 8px rgba(255, 215, 0, 0.3); }
        .badge-silver { background: linear-gradient(135deg, #C0C0C0, #A8A8A8); color: #000; box-shadow: 0 4px 8px rgba(192, 192, 192, 0.3); }
        .badge-bronze { background: linear-gradient(135deg, #CD7F32, #8B4513); color: #fff; box-shadow: 0 4px 8px rgba(205, 127, 50, 0.3); }
        .badge-good { background: linear-gradient(135deg, #4CAF50, #45a049); color: #fff; }
        .badge-warning { background: linear-gradient(135deg, #FFC107, #FF9800); color: #000; }
        .badge-danger { background: linear-gradient(135deg, #DC3545, #C82333); color: #fff; }
        .fairplay-row {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        .fairplay-row:hover {
            background-color: #f0f8ff !important;
            border-left-color: #28a745;
            transform: translateX(5px);
        }
        .fairplay-row.best-team {
            background: linear-gradient(90deg, rgba(76, 175, 80, 0.1) 0%, rgba(255, 255, 255, 0) 100%) !important;
            border-left-color: #4CAF50;
        }
        .fairplay-row.worst-team {
            background: linear-gradient(90deg, rgba(220, 53, 69, 0.1) 0%, rgba(255, 255, 255, 0) 100%) !important;
            border-left-color: #DC3545;
        }
        .stats-card .icon {
            font-size: 2rem;
            margin-bottom: 10px;
        }
        .stats-card .number {
            font-size: 2rem;
            font-weight: bold;
        }
        .stats-card .label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .trophy-icon {
            font-size: 1.2rem;
            margin-right: 5px;
        }
        .card-amarilla {
            display: inline-block;
            width: 20px;
            height: 28px;
            background: linear-gradient(135deg, #FFC107, #FFD54F);
            border: 1px solid #FFA000;
            border-radius: 2px;
            margin: 0 2px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .card-roja {
            display: inline-block;
            width: 20px;
            height: 28px;
            background: linear-gradient(135deg, #DC3545, #E57373);
            border: 1px solid #C62828;
            border-radius: 2px;
            margin: 0 2px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }
        .card-roja-doble {
            display: inline-block;
            width: 20px;
            height: 28px;
            background: linear-gradient(135deg, #FF6B6B, #EE5A6F);
            border: 1px solid #C62828;
            border-radius: 2px;
            margin: 0 2px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            position: relative;
        }
        .card-roja-doble::before {
            content: "2x";
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 8px;
            color: white;
            font-weight: bold;
        }
        .card-roja-directa {
            display: inline-block;
            width: 20px;
            height: 28px;
            background: linear-gradient(135deg, #8B0000, #DC143C);
            border: 1px solid #8B0000;
            border-radius: 2px;
            margin: 0 2px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .podio-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .tabla-fairplay th {
            background-color: #f8f9fa;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }
        .tabla-fairplay td {
            vertical-align: middle;
            font-size: 0.9rem;
        }
        .card-tabla-fairplay .card-body {
            padding: 0 !important;
        }
        .table-responsive {
            overflow-x: visible !important;
            width: 100%;
        }
        .card-body .table-responsive {
            margin: 0;
            padding: 0;
            width: 100%;
        }
        .tab-pane .table {
            margin-bottom: 0;
            width: 100%;
        }
        .table thead th.text-center,
        .table tbody td.text-center {
            text-align: center !important;
        }
        .stats-card .table td:nth-child(2) {
            text-align: left;
            min-width: 200px;
        }
        .stats-card .table td:nth-child(2) span {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .stats-card .team-logo {
            width: 28px;
            height: 28px;
        }
        .stats-card .team-initial {
            width: 28px;
            height: 28px;
            font-size: 0.85rem;
        }
        .stats-card .badge {
            padding: 5px 8px;
            font-size: 0.8rem;
        }
        .stats-card .table td {
            font-size: 0.85rem;
        }
        .stats-card .table td:nth-child(2) {
            font-size: 0.9rem;
            font-weight: 500;
        }
        @media (max-width: 768px) {
            .stats-card {
                padding: 15px;
            }
            .stats-card h5 {
                font-size: 1rem;
                margin-bottom: 12px;
            }
            .stats-card .table {
                font-size: 0.75rem;
            }
            .stats-card .table thead th,
            .stats-card .table tbody td {
                padding: 6px 3px;
            }
            .stats-card .table thead th:first-child,
            .stats-card .table tbody td:first-child {
                width: 40px;
                min-width: 40px;
                max-width: 40px;
            }
            .stats-card .table thead th:nth-child(2),
            .stats-card .table tbody td:nth-child(2) {
                min-width: 150px;
            }
            .stats-card .table thead th:nth-child(n+3),
            .stats-card .table tbody td:nth-child(n+3) {
                width: 45px;
                min-width: 45px;
                max-width: 45px;
            }
            .stats-card .team-logo {
                width: 24px;
                height: 24px;
            }
            .stats-card .team-initial {
                width: 24px;
                height: 24px;
                font-size: 0.75rem;
            }
        }
        @media (max-width: 576px) {
            .stats-card {
                padding: 12px;
            }
            .stats-card h5 {
                font-size: 0.95rem;
                margin-bottom: 10px;
            }
            .stats-card .table {
                font-size: 0.7rem;
            }
            .stats-card .table thead th,
            .stats-card .table tbody td {
                padding: 5px 2px;
            }
            .stats-card .table thead th:first-child,
            .stats-card .table tbody td:first-child {
                width: 35px;
                min-width: 35px;
                max-width: 35px;
            }
            .stats-card .table thead th:nth-child(2),
            .stats-card .table tbody td:nth-child(2) {
                min-width: 120px;
            }
            .stats-card .table thead th:nth-child(n+3),
            .stats-card .table tbody td:nth-child(n+3) {
                width: 40px;
                min-width: 40px;
                max-width: 40px;
            }
        }
        #tab-fixture .table {
            font-size: 0.85rem;
            table-layout: fixed;
        }
        #tab-fixture .table thead th:first-child,
        #tab-fixture .table tbody td:first-child {
            width: 90px;
            min-width: 90px;
            max-width: 90px;
        }
        #tab-fixture .table thead th:nth-child(2),
        #tab-fixture .table tbody td:nth-child(2) {
            width: 70px;
            min-width: 70px;
            max-width: 70px;
        }
        #tab-fixture .table thead th:nth-child(3),
        #tab-fixture .table tbody td:nth-child(3) {
            width: auto;
            min-width: 120px;
            max-width: 200px;
        }
        #tab-fixture .table thead th:nth-child(4),
        #tab-fixture .table tbody td:nth-child(4) {
            width: 40px;
            min-width: 40px;
            max-width: 40px;
        }
        #tab-fixture .table thead th:nth-child(5),
        #tab-fixture .table tbody td:nth-child(5) {
            width: auto;
            min-width: 120px;
            max-width: 200px;
        }
        #tab-fixture .table thead th:nth-child(6),
        #tab-fixture .table tbody td:nth-child(6) {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }
        #tab-resultados .table {
            font-size: 0.85rem;
            table-layout: fixed;
        }
        #tab-resultados .table thead th:first-child,
        #tab-resultados .table tbody td:first-child {
            width: 90px;
            min-width: 90px;
            max-width: 90px;
        }
        #tab-resultados .table thead th:nth-child(2),
        #tab-resultados .table tbody td:nth-child(2) {
            width: auto;
            min-width: 130px;
            max-width: 200px;
        }
        #tab-resultados .table thead th:nth-child(3),
        #tab-resultados .table tbody td:nth-child(3) {
            width: 40px;
            min-width: 40px;
            max-width: 40px;
        }
        #tab-resultados .table thead th:nth-child(4),
        #tab-resultados .table tbody td:nth-child(4) {
            width: auto;
            min-width: 130px;
            max-width: 200px;
        }
        #tab-resultados .table thead th:nth-child(5),
        #tab-resultados .table tbody td:nth-child(5) {
            width: 80px;
            min-width: 80px;
            max-width: 80px;
        }
        #tab-resultados .table thead th:nth-child(6),
        #tab-resultados .table tbody td:nth-child(6) {
            width: 120px;
            min-width: 120px;
            max-width: 120px;
        }
        #tab-goleadores .table {
            font-size: 0.85rem;
            table-layout: fixed;
        }
        #tab-goleadores .table thead th:first-child,
        #tab-goleadores .table tbody td:first-child {
            width: auto;
            min-width: 200px;
            max-width: 300px;
        }
        #tab-goleadores .table thead th:nth-child(2),
        #tab-goleadores .table tbody td:nth-child(2) {
            width: auto;
            min-width: 150px;
            max-width: 250px;
        }
        #tab-goleadores .table thead th:nth-child(3),
        #tab-goleadores .table tbody td:nth-child(3) {
            width: 80px;
            min-width: 80px;
            max-width: 80px;
        }
        #tab-sanciones .table {
            font-size: 0.85rem;
            table-layout: fixed;
        }
        #tab-sanciones .table thead th:first-child,
        #tab-sanciones .table tbody td:first-child {
            width: 90px;
            min-width: 90px;
            max-width: 90px;
        }
        #tab-sanciones .table thead th:nth-child(2),
        #tab-sanciones .table tbody td:nth-child(2) {
            width: auto;
            min-width: 180px;
            max-width: 250px;
        }
        #tab-sanciones .table thead th:nth-child(3),
        #tab-sanciones .table tbody td:nth-child(3) {
            width: auto;
            min-width: 150px;
            max-width: 200px;
        }
        #tab-sanciones .table thead th:nth-child(4),
        #tab-sanciones .table tbody td:nth-child(4) {
            width: 100px;
            min-width: 100px;
            max-width: 100px;
        }
        #tab-sanciones .table thead th:nth-child(5),
        #tab-sanciones .table tbody td:nth-child(5) {
            width: 70px;
            min-width: 70px;
            max-width: 70px;
        }
        #tab-sanciones .table thead th:nth-child(6),
        #tab-sanciones .table tbody td:nth-child(6) {
            width: 80px;
            min-width: 80px;
            max-width: 80px;
        }
        #tab-sanciones .table thead th:nth-child(7),
        #tab-sanciones .table tbody td:nth-child(7) {
            width: 80px;
            min-width: 80px;
            max-width: 80px;
        }
        #tab-equipos .stats-card {
            padding: 15px;
            text-align: center;
        }
        #tab-equipos .team-logo {
            width: 50px;
            height: 50px;
            margin: 0 auto 10px;
        }
        #tab-equipos .team-initial {
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            margin: 0 auto 10px;
        }
        #tab-fairplay .tabla-fairplay {
            font-size: 0.85rem;
            table-layout: fixed;
        }
        #tab-fairplay .tabla-fairplay thead th:first-child,
        #tab-fairplay .tabla-fairplay tbody td:first-child {
            width: 60px;
            min-width: 60px;
            max-width: 60px;
        }
        #tab-fairplay .tabla-fairplay thead th:nth-child(2),
        #tab-fairplay .tabla-fairplay tbody td:nth-child(2) {
            width: auto;
            min-width: 180px;
            max-width: 250px;
        }
        #tab-fairplay .tabla-fairplay thead th:nth-child(3),
        #tab-fairplay .tabla-fairplay tbody td:nth-child(3) {
            width: 50px;
            min-width: 50px;
            max-width: 50px;
        }
        #tab-fairplay .tabla-fairplay thead th:nth-child(4),
        #tab-fairplay .tabla-fairplay tbody td:nth-child(4),
        #tab-fairplay .tabla-fairplay thead th:nth-child(5),
        #tab-fairplay .tabla-fairplay tbody td:nth-child(5),
        #tab-fairplay .tabla-fairplay thead th:nth-child(6),
        #tab-fairplay .tabla-fairplay tbody td:nth-child(6) {
            width: 65px;
            min-width: 65px;
            max-width: 65px;
        }
        #tab-fairplay .tabla-fairplay thead th:nth-child(7),
        #tab-fairplay .tabla-fairplay tbody td:nth-child(7) {
            width: 70px;
            min-width: 70px;
            max-width: 70px;
        }
        @media (max-width: 768px) {
            #tab-fixture .table,
            #tab-resultados .table,
            #tab-goleadores .table,
            #tab-sanciones .table,
            #tab-fairplay .tabla-fairplay {
                font-size: 0.75rem;
            }
            #tab-fixture .table thead th,
            #tab-fixture .table tbody td,
            #tab-resultados .table thead th,
            #tab-resultados .table tbody td,
            #tab-goleadores .table thead th,
            #tab-goleadores .table tbody td,
            #tab-sanciones .table thead th,
            #tab-sanciones .table tbody td {
                padding: 6px 3px;
            }
            #tab-fixture .table thead th:first-child,
            #tab-fixture .table tbody td:first-child {
                width: 0;
                display: none;
            }
            #tab-fixture .table thead th:nth-child(2),
            #tab-fixture .table tbody td:nth-child(2) {
                width: 0;
                display: none;
            }
            #tab-fixture .table thead th:nth-child(3),
            #tab-fixture .table tbody td:nth-child(3) {
                width: auto;
                min-width: 100px;
                max-width: 150px;
            }
            #tab-fixture .table thead th:nth-child(5),
            #tab-fixture .table tbody td:nth-child(5) {
                width: auto;
                min-width: 100px;
                max-width: 150px;
            }
            #tab-fixture .table thead th:nth-child(6),
            #tab-fixture .table tbody td:nth-child(6) {
                width: 0;
                display: none;
            }
            #tab-resultados .table thead th:first-child,
            #tab-resultados .table tbody td:first-child {
                width: 0;
                display: none;
            }
            #tab-resultados .table thead th:nth-child(2),
            #tab-resultados .table tbody td:nth-child(2) {
                width: auto;
                min-width: 110px;
                max-width: 150px;
            }
            #tab-resultados .table thead th:nth-child(4),
            #tab-resultados .table tbody td:nth-child(4) {
                width: auto;
                min-width: 110px;
                max-width: 150px;
            }
            #tab-resultados .table thead th:nth-child(5),
            #tab-resultados .table tbody td:nth-child(5) {
                width: 70px;
                min-width: 70px;
                max-width: 70px;
            }
            #tab-resultados .table thead th:nth-child(6),
            #tab-resultados .table tbody td:nth-child(6) {
                width: 0;
                display: none;
            }
            #tab-goleadores .table thead th:nth-child(2),
            #tab-goleadores .table tbody td:nth-child(2) {
                width: 0;
                display: none;
            }
            #tab-goleadores .table thead th:first-child,
            #tab-goleadores .table tbody td:first-child {
                width: auto;
                min-width: 150px;
            }
            #tab-sanciones .table thead th:first-child,
            #tab-sanciones .table tbody td:first-child {
                width: 0;
                display: none;
            }
            #tab-sanciones .table thead th:nth-child(3),
            #tab-sanciones .table tbody td:nth-child(3) {
                width: 0;
                display: none;
            }
            #tab-sanciones .table thead th:nth-child(6),
            #tab-sanciones .table tbody td:nth-child(6),
            #tab-sanciones .table thead th:nth-child(7),
            #tab-sanciones .table tbody td:nth-child(7) {
                width: 0;
                display: none;
            }
            #tab-sanciones .table thead th:nth-child(2),
            #tab-sanciones .table tbody td:nth-child(2) {
                width: auto;
                min-width: 150px;
            }
            #tab-fairplay .tabla-fairplay thead th:nth-child(3),
            #tab-fairplay .tabla-fairplay tbody td:nth-child(3) {
                width: 45px;
                min-width: 45px;
                max-width: 45px;
            }
            #tab-fairplay .tabla-fairplay thead th:nth-child(2),
            #tab-fairplay .tabla-fairplay tbody td:nth-child(2) {
                width: auto;
                min-width: 120px;
            }
            #tab-fairplay .tabla-fairplay thead th:nth-child(4),
            #tab-fairplay .tabla-fairplay tbody td:nth-child(4),
            #tab-fairplay .tabla-fairplay thead th:nth-child(5),
            #tab-fairplay .tabla-fairplay tbody td:nth-child(5),
            #tab-fairplay .tabla-fairplay thead th:nth-child(6),
            #tab-fairplay .tabla-fairplay tbody td:nth-child(6) {
                width: 50px;
                min-width: 50px;
                max-width: 50px;
            }
            #tab-fairplay .tabla-fairplay thead th:nth-child(7),
            #tab-fairplay .tabla-fairplay tbody td:nth-child(7) {
                width: 55px;
                min-width: 55px;
                max-width: 55px;
            }
        }
        @media (max-width: 576px) {
            .tab-pane {
                padding: 15px;
            }
            #tab-fixture .table,
            #tab-resultados .table,
            #tab-goleadores .table,
            #tab-sanciones .table,
            #tab-fairplay .tabla-fairplay {
                font-size: 0.7rem;
            }
            #tab-fixture .table thead th,
            #tab-fixture .table tbody td,
            #tab-resultados .table thead th,
            #tab-resultados .table tbody td,
            #tab-goleadores .table thead th,
            #tab-goleadores .table tbody td,
            #tab-sanciones .table thead th,
            #tab-sanciones .table tbody td {
                padding: 5px 2px;
            }
            #tab-equipos .col-6 {
                margin-bottom: 10px;
            }
            #tab-equipos .team-logo {
                width: 40px;
                height: 40px;
            }
            #tab-equipos .team-initial {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            #tab-fairplay .podio-card {
                margin-bottom: 15px;
            }
            #tab-fairplay .podio-card .trophy-icon {
                font-size: 2rem !important;
            }
            #tab-fairplay .podio-card h5 {
                font-size: 1rem;
            }
            #tab-fairplay .podio-card h6 {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <?php include '../include/header.php'; ?>
    <div class="hero-section py-4">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="h2 fw-bold text-white mb-2">
                        <i class="fas fa-moon text-warning"></i> <?php echo htmlspecialchars($campeonato['nombre'] ?? 'Torneos por Zonas'); ?>
                    </h1>
                    <p class="text-white-50 mb-0">Información completa de los torneos nocturnos y zonales</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <?php if (!empty($campeonatosDisponibles)): ?>
                        <form method="GET" class="text-white-50 small">
                            <label class="form-label text-white-50 mb-1" for="campeonatoSelect">Seleccionar torneo</label>
                            <select id="campeonatoSelect" name="campeonato_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                <?php foreach ($campeonatosDisponibles as $camp): ?>
                                    <option value="<?= $camp['id']; ?>" <?= (int)$camp['id'] === (int)$campeonatoIdSeleccionado ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($camp['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark fs-6">
                            <i class="fas fa-info-circle"></i> Sin torneos activos
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($campeonato && count($categorias) > 1): ?>
                <div class="row mt-3">
                    <div class="col-lg-6">
                        <form method="GET" class="row g-2 align-items-center text-white-50">
                            <input type="hidden" name="campeonato_id" value="<?= $campeonatoIdSeleccionado ?>">
                            <div class="col-auto">
                                <label class="form-label text-white-50 small mb-0" for="categoriaSelect">Categoría</label>
                            </div>
                            <div class="col">
                                <select id="categoriaSelect" name="categoria_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                    <?php foreach ($categorias as $cat): ?>
                                        <option value="<?= $cat['id'] ?>" <?= (int)$cat['id'] === (int)$categoriaSeleccionadaId ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            <?php elseif ($campeonato && $categoriaSeleccionada): ?>
                <div class="row mt-3">
                    <div class="col-lg-6">
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-tag me-1"></i> Categoría: <?= htmlspecialchars($categoriaSeleccionada['nombre']); ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="container my-4">
        <?php if (!$campeonato): ?>
            <div class="alert alert-warning shadow-sm">
                No hay torneos nocturnos o por zonas activos en este momento. Vuelve pronto para ver las novedades.
            </div>
        <?php elseif (empty($categoria_ids)): ?>
            <div class="alert alert-warning shadow-sm">
                No hay categorías activas asociadas a este torneo. Vuelve más tarde para ver la información completa.
            </div>
        <?php else: ?>
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stats-card text-center">
                    <h6 class="text-muted text-uppercase small mb-1">Partidos</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $estadisticas_zonales['partidos']; ?></h3>
                    <small class="text-muted">Finalizados</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card text-center">
                    <h6 class="text-muted text-uppercase small mb-1">Goles</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $estadisticas_zonales['goles']; ?></h3>
                    <small class="text-muted">Total convertido</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card text-center">
                    <h6 class="text-muted text-uppercase small mb-1">Promedio</h6>
                    <h3 class="mb-0 fw-bold"><?php echo number_format($estadisticas_zonales['promedio'], 2); ?></h3>
                    <small class="text-muted">Goles por partido</small>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stats-card text-center">
                    <h6 class="text-muted text-uppercase small mb-1">Tarjetas</h6>
                    <h3 class="mb-0 fw-bold"><?php echo $estadisticas_zonales['amarillas'] + $estadisticas_zonales['rojas']; ?></h3>
                    <small class="text-muted">🟨 <?php echo $estadisticas_zonales['amarillas']; ?> · 🟥 <?php echo $estadisticas_zonales['rojas']; ?></small>
                </div>
            </div>
        </div>
        <ul class="nav nav-tabs" id="nocturnoTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-posiciones" type="button" role="tab">
                    <i class="fas fa-trophy"></i> Posiciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fixture" type="button" role="tab">
                    <i class="fas fa-calendar"></i> Fixture
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-resultados" type="button" role="tab">
                    <i class="fas fa-list"></i> Resultados
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-goleadores" type="button" role="tab">
                    <i class="fas fa-futbol"></i> Goleadores
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-sanciones" type="button" role="tab">
                    <i class="fas fa-ban"></i> Sanciones
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-equipos" type="button" role="tab">
                    <i class="fas fa-users"></i> Equipos
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-fairplay" type="button" role="tab">
                    <i class="fas fa-shield-alt"></i> Fairplay
                </button>
            </li>
        </ul>
        <div class="tab-content">
            <!-- CONTENIDO EXACTAMENTE IGUAL AL ORIGINAL -->
            <!-- ... (todo tu HTML sin cambios, ya incluido arriba en el bloque de estilo) ... -->
            
            <!-- Posiciones por Zonas -->
            <div class="tab-pane fade show active" id="tab-posiciones" role="tabpanel">
                <?php if (!empty($zonas_data_con_zonas)): ?>
                    <?php foreach ($zonas_data_con_zonas as $pack): $formato = $pack['formato']; $zlist = $pack['zonas']; ?>
                        <div class="card mb-4">
                            <div class="card-header">
                                <i class="fas fa-layer-group me-2"></i>
                                Formato con <?php echo (int)$formato['cantidad_zonas']; ?> zonas
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($zlist as $zona): ?>
                                        <div class="col-12 mb-4">
                                            <div class="stats-card">
                                                <h5 class="mb-3 text-primary">
                                                    <i class="fas fa-flag me-2"></i>
                                                    <?php echo htmlspecialchars($zona['nombre']); ?>
                                                </h5>
                                                <?php 
                                                $tabla_zona = $zona['tabla'] ?? [];
                                                if (!empty($tabla_zona) && is_array($tabla_zona) && count($tabla_zona) > 0): ?>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-striped mb-0">
                                                            <thead>
                                                                <tr>
                                                                    <th>Pos</th>
                                                                    <th>Equipo</th>
                                                                    <th>PJ</th>
                                                                    <th>G</th>
                                                                    <th>E</th>
                                                                    <th>P</th>
                                                                    <th>GF</th>
                                                                    <th>GC</th>
                                                                    <th>DG</th>
                                                                    <th>Pts</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php foreach ($tabla_zona as $r): ?>
                                                                    <?php if (isset($r['equipo_id']) || isset($r['equipo'])): ?>
                                                                        <tr>
                                                                            <td class="text-center">
                                                                                <span class="badge <?= (isset($r['posicion']) && $r['posicion'] <= 3) ? 'bg-success' : 'bg-secondary' ?>">
                                                                                    <?= isset($r['posicion']) ? (int)$r['posicion'] : '?' ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <div class="d-flex align-items-center">
                                                                                    <?php if (!empty($r['logo'])): ?>
                                                                                        <img src="../uploads/<?= htmlspecialchars($r['logo']) ?>" 
                                                                                             alt="Logo" class="team-logo me-2">
                                                                                    <?php else: ?>
                                                                                        <div class="team-initial me-2">
                                                                                            <?= strtoupper(substr($r['equipo'] ?? '?', 0, 1)) ?>
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                    <span class="fw-medium"><?php echo htmlspecialchars($r['equipo'] ?? 'Sin nombre'); ?></span>
                                                                                </div>
                                                                            </td>
                                                                            <td class="text-center"><?php echo (int)($r['partidos_jugados'] ?? 0); ?></td>
                                                                            <td class="text-center text-success fw-bold"><?php echo (int)($r['partidos_ganados'] ?? 0); ?></td>
                                                                            <td class="text-center text-warning fw-bold"><?php echo (int)($r['partidos_empatados'] ?? 0); ?></td>
                                                                            <td class="text-center text-danger fw-bold"><?php echo (int)($r['partidos_perdidos'] ?? 0); ?></td>
                                                                            <td class="text-center"><?php echo (int)($r['goles_favor'] ?? 0); ?></td>
                                                                            <td class="text-center"><?php echo (int)($r['goles_contra'] ?? 0); ?></td>
                                                                            <td class="text-center">
                                                                                <span class="<?= (isset($r['diferencia_gol']) && $r['diferencia_gol'] > 0) ? 'text-success' : ((isset($r['diferencia_gol']) && $r['diferencia_gol'] < 0) ? 'text-danger' : '') ?>">
                                                                                    <?= (isset($r['diferencia_gol']) && $r['diferencia_gol'] > 0) ? '+' : '' ?><?php echo (int)($r['diferencia_gol'] ?? 0); ?>
                                                                                </span>
                                                                            </td>
                                                                            <td class="text-center"><strong><?php echo (int)($r['puntos'] ?? 0); ?></strong></td>
                                                                        </tr>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-info mb-0 py-2" style="font-size: 0.75rem;">
                                                        <small><i class="fas fa-info-circle me-1"></i>No hay equipos registrados en esta zona aún.</small>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php elseif (!empty($formatos)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Aún no hay datos registrados en las zonas de esta categoría.
                    </div>
                <?php elseif (!empty($tabla_posiciones_general)): ?>
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-trophy"></i> 
                                Tabla de Posiciones
                            </h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="overflow-x: visible;">
                                <table class="table table-hover mb-0 table-sm">
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
                                        <?php foreach ($tabla_posiciones_general as $index => $equipo): ?>
                                            <?php
                                            $posicion = $index + 1;
                                            $clase_posicion = '';
                                            if ($posicion == 1) $clase_posicion = 'bg-success';
                                            elseif ($posicion == 2) $clase_posicion = 'bg-info';
                                            elseif ($posicion == 3) $clase_posicion = 'bg-warning';
                                            ?>
                                            <tr>
                                                <td class="text-center">
                                                    <span class="badge <?= $clase_posicion ?: 'bg-secondary' ?>">
                                                        <?= $posicion ?>
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
                                                    <span class="badge bg-primary"><?php echo $equipo['puntos']; ?></span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay datos de posiciones disponibles para este torneo.
                    </div>
                <?php endif; ?>
            </div>

            <!-- Fixture -->
            <div class="tab-pane fade" id="tab-fixture" role="tabpanel">
                <?php if (empty($fixture) && empty($fixture_zonas)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        No hay partidos programados. El fixture aún no ha sido generado.
                    </div>
                <?php else: ?>
                    <?php if (!empty($fixture_zonas)): ?>
                        <?php foreach ($fixture_zonas as $fz): ?>
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">
                                        <i class="fas fa-flag me-2"></i>
                                        <?= htmlspecialchars($fz['zona']['nombre']) ?>
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($fz['partidos'])): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            No hay partidos generados para esta zona.
                                        </div>
                                    <?php else: ?>
                                        <?php
                                        $por_jornada = [];
                                        foreach ($fz['partidos'] as $p) {
                                            $jornada = $p['jornada_zona'] ?? 1;
                                            if (!isset($por_jornada[$jornada])) {
                                                $por_jornada[$jornada] = [];
                                            }
                                            $por_jornada[$jornada][] = $p;
                                        }
                                        ksort($por_jornada);
                                        ?>
                                        <?php foreach ($por_jornada as $jornada => $partidos): ?>
                                            <div class="mb-4">
                                                <h6 class="mb-3">
                                                    <span class="badge bg-primary">
                                                        <i class="fas fa-calendar me-1"></i>Jornada <?= $jornada ?>
                                                    </span>
                                                </h6>
                                                <div class="table-responsive" style="overflow-x: visible;">
                                                    <table class="table table-striped table-sm mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th class="hide-xs">Fecha</th>
                                                                <th class="d-none-mobile">Hora</th>
                                                                <th>Local</th>
                                                                <th></th>
                                                                <th>Visitante</th>
                                                                <th class="hide-xs">Cancha</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($partidos as $p): ?>
                                                                <tr>
                                                                    <td class="hide-xs"><?= $p['fecha_partido'] ? date('d/m/Y', strtotime($p['fecha_partido'])) : '-' ?></td>
                                                                    <td class="d-none-mobile"><?= $p['hora_partido'] ? date('H:i', strtotime($p['hora_partido'])) : '-' ?></td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <?php if (!empty($p['logo_local'])): ?>
                                                                                <img src="../uploads/<?= htmlspecialchars($p['logo_local']) ?>" 
                                                                                     class="team-logo me-2" alt="Logo" style="width: 24px; height: 24px;">
                                                                            <?php else: ?>
                                                                                <div class="team-initial me-2" style="width: 24px; height: 24px; font-size: 0.75rem;">
                                                                                    <?= substr($p['equipo_local'], 0, 1) ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <span class="fw-medium"><?= htmlspecialchars($p['equipo_local']) ?></span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-center text-muted" style="width: 40px;">vs</td>
                                                                    <td>
                                                                        <div class="d-flex align-items-center">
                                                                            <?php if (!empty($p['logo_visitante'])): ?>
                                                                                <img src="../uploads/<?= htmlspecialchars($p['logo_visitante']) ?>" 
                                                                                     class="team-logo me-2" alt="Logo" style="width: 24px; height: 24px;">
                                                                            <?php else: ?>
                                                                                <div class="team-initial me-2" style="width: 24px; height: 24px; font-size: 0.75rem;">
                                                                                    <?= substr($p['equipo_visitante'], 0, 1) ?>
                                                                                </div>
                                                                            <?php endif; ?>
                                                                            <span class="fw-medium"><?= htmlspecialchars($p['equipo_visitante']) ?></span>
                                                                        </div>
                                                                    </td>
                                                                    <td class="text-muted hide-xs"><?= htmlspecialchars($p['cancha'] ?? 'Por confirmar') ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <!-- Fases Eliminatorias -->
                    <?php if (!empty($formato_fases)): ?>
                        <?php foreach ($formato_fases as $formato_id => $fases): ?>
                            <?php if (!empty($fases)): ?>
                                <div class="card mb-4">
                                    <div class="card-header bg-warning text-dark">
                                        <h6 class="mb-0">
                                            <i class="fas fa-trophy me-2"></i>Fases Eliminatorias
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($fases as $fase): ?>
                                            <?php if (!empty($formato_partidos_eliminatorios[$formato_id][$fase['id']])): ?>
                                                <div class="mb-4">
                                                    <h6 class="mb-3">
                                                        <?php
                                                        $nombre_fase = [
                                                            'dieciseisavos' => 'Dieciseisavos de Final',
                                                            'octavos' => 'Octavos de Final',
                                                            'cuartos' => 'Cuartos de Final',
                                                            'semifinal' => 'Semifinales',
                                                            'final' => 'Final',
                                                            'tercer_puesto' => 'Tercer Puesto'
                                                        ];
                                                        echo $nombre_fase[$fase['nombre']] ?? $fase['nombre'];
                                                        ?>
                                                    </h6>
                                                    <div class="row">
                                                        <?php foreach ($formato_partidos_eliminatorios[$formato_id][$fase['id']] as $partido): ?>
                                                            <div class="col-md-6 col-lg-4 mb-3">
                                                                <div class="card border">
                                                                    <div class="card-body p-3">
                                                                        <div class="text-center mb-2">
                                                                            <small class="text-muted">Llave <?= $partido['numero_llave'] ?></small>
                                                                            <?php if ($partido['fecha_partido']): ?>
                                                                                <br><small><?= date('d/m/Y', strtotime($partido['fecha_partido'])) ?></small>
                                                                                <?php if ($partido['hora_partido']): ?>
                                                                                    <br><small><?= date('H:i', strtotime($partido['hora_partido'])) ?></small>
                                                                                <?php endif; ?>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        
                                                                        <?php
                                                                        // Calcular ganador
                                                                        $ganador_id = null;
                                                                        if ($partido['estado'] === 'finalizado' && isset($partido['goles_local']) && isset($partido['goles_visitante'])) {
                                                                            if ($partido['goles_local_penales'] !== null) {
                                                                                // Hubo penales
                                                                                $ganador_id = $partido['goles_local_penales'] > $partido['goles_visitante_penales'] 
                                                                                    ? $partido['equipo_local_id'] 
                                                                                    : $partido['equipo_visitante_id'];
                                                                            } else {
                                                                                // Sin penales
                                                                                $ganador_id = $partido['goles_local'] > $partido['goles_visitante'] 
                                                                                    ? $partido['equipo_local_id'] 
                                                                                    : ($partido['goles_local'] < $partido['goles_visitante'] ? $partido['equipo_visitante_id'] : null);
                                                                            }
                                                                        }
                                                                        ?>
                                                                        
                                                                        <div class="d-flex align-items-center mb-2 p-2 rounded <?= $ganador_id == $partido['equipo_local_id'] ? 'bg-light' : '' ?>">
                                                                            <?php if ($partido['equipo_local_id']): ?>
                                                                                <?php if (!empty($partido['logo_local'])): ?>
                                                                                    <img src="../uploads/<?= htmlspecialchars($partido['logo_local']) ?>" 
                                                                                         class="me-2" alt="Logo" style="width: 24px; height: 24px;">
                                                                                <?php endif; ?>
                                                                                <span class="flex-grow-1 <?= $ganador_id == $partido['equipo_local_id'] ? 'fw-bold' : '' ?>">
                                                                                    <?= htmlspecialchars($partido['equipo_local_nombre']) ?>
                                                                                </span>
                                                                                <?php if (isset($partido['goles_local'])): ?>
                                                                                    <span class="ms-2 fw-bold">
                                                                                        <?= $partido['goles_local'] ?>
                                                                                        <?php if (isset($partido['goles_local_penales']) && $partido['goles_local_penales'] !== null): ?>
                                                                                            <small class="text-muted d-block" style="font-size: 0.7rem;">
                                                                                                (<?= $partido['goles_local_penales'] ?> pen.)
                                                                                            </small>
                                                                                        <?php endif; ?>
                                                                                    </span>
                                                                                <?php endif; ?>
                                                                            <?php else: ?>
                                                                                <em class="text-muted"><?= htmlspecialchars($partido['origen_local'] ?? 'Por definir') ?></em>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        
                                                                        <div class="text-center mb-2">
                                                                            <small class="text-muted">VS</small>
                                                                        </div>
                                                                        
                                                                        <div class="d-flex align-items-center mb-2 p-2 rounded <?= $ganador_id == $partido['equipo_visitante_id'] ? 'bg-light' : '' ?>">
                                                                            <?php if ($partido['equipo_visitante_id']): ?>
                                                                                <?php if (!empty($partido['logo_visitante'])): ?>
                                                                                    <img src="../uploads/<?= htmlspecialchars($partido['logo_visitante']) ?>" 
                                                                                         class="me-2" alt="Logo" style="width: 24px; height: 24px;">
                                                                                <?php endif; ?>
                                                                                <span class="flex-grow-1 <?= $ganador_id == $partido['equipo_visitante_id'] ? 'fw-bold' : '' ?>">
                                                                                    <?= htmlspecialchars($partido['equipo_visitante_nombre']) ?>
                                                                                </span>
                                                                                <?php if (isset($partido['goles_visitante'])): ?>
                                                                                    <span class="ms-2 fw-bold">
                                                                                        <?= $partido['goles_visitante'] ?>
                                                                                        <?php if (isset($partido['goles_visitante_penales']) && $partido['goles_visitante_penales'] !== null): ?>
                                                                                            <small class="text-muted d-block" style="font-size: 0.7rem;">
                                                                                                (<?= $partido['goles_visitante_penales'] ?> pen.)
                                                                                            </small>
                                                                                        <?php endif; ?>
                                                                                    </span>
                                                                                <?php endif; ?>
                                                                            <?php else: ?>
                                                                                <em class="text-muted"><?= htmlspecialchars($partido['origen_visitante'] ?? 'Por definir') ?></em>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        
                                                                        <?php if (isset($partido['goles_local_penales']) && $partido['goles_local_penales'] !== null): ?>
                                                                            <div class="text-center mt-2">
                                                                                <small class="text-muted">
                                                                                    Resultado: <?= $partido['goles_local'] ?> - <?= $partido['goles_visitante'] ?> 
                                                                                    (<?= $partido['goles_local_penales'] ?> - <?= $partido['goles_visitante_penales'] ?> pen.)
                                                                                </small>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                        
                                                                        <div class="text-center mt-2">
                                                                            <span class="badge bg-<?= $partido['estado'] === 'finalizado' ? 'success' : ($partido['estado'] === 'programado' ? 'primary' : 'secondary') ?>">
                                                                                <?= ucfirst($partido['estado']) ?>
                                                                            </span>
                                                                            <?php if ($partido['cancha_nombre']): ?>
                                                                                <br><small class="text-muted"><?= htmlspecialchars($partido['cancha_nombre']) ?></small>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($fixture)): ?>
                        <div class="card">
                            <div class="card-header bg-success">
                                <h6 class="mb-0">
                                    <i class="fas fa-calendar me-2"></i>Partidos del Torneo
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive" style="overflow-x: visible;">
                                    <table class="table table-striped table-sm mb-0">
                                        <thead>
                                            <tr>
                                                <th class="hide-xs">Fecha</th>
                                                <th class="d-none-mobile">Hora</th>
                                                <th>Local</th>
                                                <th></th>
                                                <th>Visitante</th>
                                                <th class="hide-xs">Cancha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($fixture as $p): ?>
                                                <tr>
                                                    <td class="hide-xs"><?= $p['fecha_partido'] ? date('d/m/Y', strtotime($p['fecha_partido'])) : '-' ?></td>
                                                    <td class="d-none-mobile"><?= $p['hora_partido'] ? date('H:i', strtotime($p['hora_partido'])) : '-' ?></td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($p['logo_local'])): ?>
                                                                <img src="../uploads/<?= htmlspecialchars($p['logo_local']) ?>" 
                                                                     class="team-logo me-2" alt="Logo" style="width: 24px; height: 24px;">
                                                            <?php endif; ?>
                                                            <span class="fw-medium"><?= htmlspecialchars($p['equipo_local']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center text-muted" style="width: 40px;">vs</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <?php if (!empty($p['logo_visitante'])): ?>
                                                                <img src="../uploads/<?= htmlspecialchars($p['logo_visitante']) ?>" 
                                                                     class="team-logo me-2" alt="Logo" style="width: 24px; height: 24px;">
                                                            <?php endif; ?>
                                                            <span class="fw-medium"><?= htmlspecialchars($p['equipo_visitante']) ?></span>
                                                        </div>
                                                    </td>
                                                    <td class="text-muted hide-xs"><?= htmlspecialchars($p['cancha'] ?? 'Por confirmar') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Resultados -->
            <div class="tab-pane fade" id="tab-resultados" role="tabpanel">
                <?php if (empty($resultados)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sin resultados recientes.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Resultados Recientes
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="overflow-x: visible;">
                                <table class="table table-striped table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th class="hide-xs">Fecha</th>
                                            <th>Local</th>
                                            <th></th>
                                            <th>Visitante</th>
                                            <th>Resultado</th>
                                            <th class="hide-xs">Cancha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($resultados as $p): ?>
                                            <tr>
                                                <td class="text-muted hide-xs"><?= $p['fecha_partido'] ? date('d/m/Y', strtotime($p['fecha_partido'])) : '-' ?></td>
                                                <td class="fw-medium"><?= htmlspecialchars($p['equipo_local']) ?></td>
                                                <td class="text-center text-muted" style="width: 40px;">vs</td>
                                                <td class="fw-medium"><?= htmlspecialchars($p['equipo_visitante']) ?></td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        <strong><?= (int)$p['goles_local'] ?> - <?= (int)$p['goles_visitante'] ?></strong>
                                                    </span>
                                                </td>
                                                <td class="text-muted hide-xs"><?= htmlspecialchars($p['cancha'] ?? '-') ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Goleadores -->
            <div class="tab-pane fade" id="tab-goleadores" role="tabpanel">
                <?php if (empty($goleadores)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sin goles registrados.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-futbol me-2"></i>Tabla de Goleadores
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="overflow-x: visible;">
                                <table class="table table-striped table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Jugador</th>
                                            <th class="d-none-mobile">Equipo</th>
                                            <th>Goles</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $pos = 1;
                                        foreach ($goleadores as $g): 
                                        ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <span class="badge <?= $pos <= 3 ? 'bg-warning' : 'bg-secondary' ?> me-2">
                                                            <?= $pos++ ?>
                                                        </span>
                                                        <span class="fw-medium"><?= htmlspecialchars($g['apellido_nombre']) ?></span>
                                                    </div>
                                                    <small class="text-muted d-md-none"><?= htmlspecialchars($g['equipo']) ?></small>
                                                </td>
                                                <td class="text-muted d-none-mobile"><?= htmlspecialchars($g['equipo']) ?></td>
                                                <td>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-futbol me-1"></i>
                                                        <strong><?= (int)$g['goles'] ?></strong>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Sanciones -->
            <div class="tab-pane fade" id="tab-sanciones" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-ban me-2"></i>Sanciones Activas
                            <?php if (!empty($sanciones)): ?>
                                <span class="badge bg-danger ms-2"><?= count($sanciones) ?></span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="overflow-x: visible;">
                            <table class="table table-striped table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th class="hide-xs">Fecha</th>
                                        <th>Jugador</th>
                                        <th class="d-none-mobile">Equipo</th>
                                        <th>Tipo</th>
                                        <th class="text-center">Partidos</th>
                                        <th class="hide-xs text-center">Cumplidos</th>
                                        <th class="hide-xs text-center">Restantes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($sanciones)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-info-circle me-2"></i>No hay sanciones activas en este momento.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($sanciones as $s): ?>
                                            <tr>
                                                <td class="text-muted hide-xs"><?= $s['fecha_sancion'] ? date('d/m/Y', strtotime($s['fecha_sancion'])) : '-' ?></td>
                                                <td class="fw-medium">
                                                    <?= htmlspecialchars($s['apellido_nombre']) ?>
                                                    <br><small class="text-muted d-md-none"><?= htmlspecialchars($s['equipo']) ?></small>
                                                </td>
                                                <td class="text-muted d-none-mobile">
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($s['equipo_logo'])): ?>
                                                            <img src="../uploads/<?= htmlspecialchars($s['equipo_logo']) ?>" 
                                                                 class="team-logo me-2" alt="Logo" style="width: 24px; height: 24px;">
                                                        <?php endif; ?>
                                                        <span><?= htmlspecialchars($s['equipo']) ?></span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= strpos(strtolower($s['tipo'] ?? ''), 'roja') !== false ? 'danger' : 'warning' ?>">
                                                        <?= htmlspecialchars($s['tipo'] ?? 'N/A') ?>
                                                    </span>
                                                </td>
                                                <td class="text-center"><strong><?= (int)($s['partidos_suspension'] ?? 0) ?></strong></td>
                                                <td class="text-muted hide-xs text-center"><?= (int)($s['partidos_cumplidos'] ?? 0) ?></td>
                                                <td class="hide-xs text-center">
                                                    <span class="badge bg-<?= ($s['fechas_restantes'] ?? 0) > 0 ? 'danger' : 'success' ?>">
                                                        <?= (int)($s['fechas_restantes'] ?? 0) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Equipos -->
            <div class="tab-pane fade" id="tab-equipos" role="tabpanel">
                <?php if (empty($equipos)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No hay equipos registrados.
                    </div>
                <?php else: ?>
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-users me-2"></i>Equipos del Torneo
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($equipos as $e): ?>
                                    <div class="col-6 col-md-4 col-lg-3 mb-3">
                                        <div class="stats-card text-center">
                                            <div class="d-flex flex-column align-items-center">
                                                <?php if (!empty($e['logo'])): ?>
                                                    <img src="../uploads/<?= htmlspecialchars($e['logo']) ?>" 
                                                         alt="<?= htmlspecialchars($e['nombre']) ?>" 
                                                         class="team-logo mb-2"
                                                         style="width: 50px; height: 50px; object-fit: contain;">
                                                <?php else: ?>
                                                    <div class="team-initial mb-2">
                                                        <?= strtoupper(substr($e['nombre'], 0, 1)) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <span class="fw-medium" style="font-size: 0.9rem;"><?= htmlspecialchars($e['nombre']) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Fairplay -->
            <div class="tab-pane fade" id="tab-fairplay" role="tabpanel">
                <?php if (empty($fairplay)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>Sin datos de tarjetas registradas.
                    </div>
                <?php else: ?>
                    <?php
                    $total_amarillas = array_sum(array_column($fairplay, 'amarillas'));
                    $total_rojas_doble = array_sum(array_column($fairplay, 'rojas_doble'));
                    $total_rojas_directa = array_sum(array_column($fairplay, 'rojas_directa'));
                    $total_rojas = array_sum(array_column($fairplay, 'rojas_total'));
                    $total_puntos = array_sum(array_column($fairplay, 'puntos'));
                    ?>
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="stats-card">
                                        <div class="icon text-warning">
                                            <i class="fas fa-square"></i>
                                        </div>
                                        <div class="number text-warning"><?= $total_amarillas ?></div>
                                        <div class="label">Amarillas</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="stats-card">
                                        <div class="icon" style="color: #FF8C00;">
                                            <i class="fas fa-square"></i>
                                        </div>
                                        <div class="number" style="color: #FF8C00;"><?= $total_rojas_doble ?></div>
                                        <div class="label">Doble Amarilla</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="stats-card">
                                        <div class="icon" style="color: #8B0000;">
                                            <i class="fas fa-square"></i>
                                        </div>
                                        <div class="number" style="color: #8B0000;"><?= $total_rojas_directa ?></div>
                                        <div class="label">Rojas Directas</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 mb-3">
                                    <div class="stats-card">
                                        <div class="icon text-primary">
                                            <i class="fas fa-calculator"></i>
                                        </div>
                                        <div class="number text-primary"><?= $total_puntos ?></div>
                                        <div class="label">Puntos Totales</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if (count($fairplay) >= 3): ?>
                    <div class="row mb-4">
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <div class="podio-card" style="background: linear-gradient(135deg, #FFD700, #FFA500); color: #000;">
                                <div class="trophy-icon">
                                    <i class="fas fa-trophy" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="mt-3">🥇 Fair Play de Oro</h5>
                                <div class="d-flex align-items-center justify-content-center my-2">
                                    <?php if ($fairplay[0]['logo']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($fairplay[0]['logo']) ?>" 
                                             alt="Logo" class="me-2 rounded" width="30" height="30">
                                    <?php endif; ?>
                                    <h6 class="mb-0"><?= htmlspecialchars($fairplay[0]['equipo']) ?></h6>
                                </div>
                                <p class="mb-1"><strong><?= $fairplay[0]['puntos'] ?></strong> puntos</p>
                                <small>
                                    <?= $fairplay[0]['amarillas'] ?> <span class="card-amarilla"></span> 
                                    <?= $fairplay[0]['rojas_doble'] ?> <span class="card-roja-doble"></span>
                                    <?= $fairplay[0]['rojas_directa'] ?> <span class="card-roja-directa"></span>
                                </small>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <div class="podio-card" style="background: linear-gradient(135deg, #C0C0C0, #A8A8A8); color: #000;">
                                <div class="trophy-icon">
                                    <i class="fas fa-medal" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="mt-3">🥈 Fair Play de Plata</h5>
                                <div class="d-flex align-items-center justify-content-center my-2">
                                    <?php if ($fairplay[1]['logo']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($fairplay[1]['logo']) ?>" 
                                             alt="Logo" class="me-2 rounded" width="30" height="30">
                                    <?php endif; ?>
                                    <h6 class="mb-0"><?= htmlspecialchars($fairplay[1]['equipo']) ?></h6>
                                </div>
                                <p class="mb-1"><strong><?= $fairplay[1]['puntos'] ?></strong> puntos</p>
                                <small>
                                    <?= $fairplay[1]['amarillas'] ?> <span class="card-amarilla"></span> 
                                    <?= $fairplay[1]['rojas_doble'] ?> <span class="card-roja-doble"></span>
                                    <?= $fairplay[1]['rojas_directa'] ?> <span class="card-roja-directa"></span>
                                </small>
                            </div>
                        </div>
                        <div class="col-12 col-md-4 mb-3 mb-md-0">
                            <div class="podio-card" style="background: linear-gradient(135deg, #CD7F32, #8B4513); color: #fff;">
                                <div class="trophy-icon">
                                    <i class="fas fa-medal" style="font-size: 3rem;"></i>
                                </div>
                                <h5 class="mt-3">🥉 Fair Play de Bronce</h5>
                                <div class="d-flex align-items-center justify-content-center my-2">
                                    <?php if ($fairplay[2]['logo']): ?>
                                        <img src="../uploads/<?= htmlspecialchars($fairplay[2]['logo']) ?>" 
                                             alt="Logo" class="me-2 rounded" width="30" height="30">
                                    <?php endif; ?>
                                    <h6 class="mb-0"><?= htmlspecialchars($fairplay[2]['equipo']) ?></h6>
                                </div>
                                <p class="mb-1"><strong><?= $fairplay[2]['puntos'] ?></strong> puntos</p>
                                <small>
                                    <?= $fairplay[2]['amarillas'] ?> <span class="card-amarilla"></span> 
                                    <?= $fairplay[2]['rojas_doble'] ?> <span class="card-roja-doble"></span>
                                    <?= $fairplay[2]['rojas_directa'] ?> <span class="card-roja-directa"></span>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="card card-tabla-fairplay">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">
                                <i class="fas fa-star"></i> Tabla Fair Play
                            </h4>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive" style="overflow-x: visible;">
                                <table class="table table-hover tabla-fairplay mb-0 table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-center">Pos</th>
                                            <th>Equipo</th>
                                            <th class="text-center">PJ</th>
                                            <th class="text-center">
                                                <div class="d-md-block d-none">
                                                    <span class="card-amarilla"></span><br>
                                                    <small>Amarilla</small>
                                                </div>
                                                <div class="d-md-none">
                                                    <span class="card-amarilla"></span>
                                                    <small>A</small>
                                                </div>
                                            </th>
                                            <th class="text-center">
                                                <div class="d-md-block d-none">
                                                    <span class="card-roja-doble"></span><br>
                                                    <small>Doble Amarilla</small>
                                                </div>
                                                <div class="d-md-none">
                                                    <span class="card-roja-doble"></span>
                                                    <small>2A</small>
                                                </div>
                                            </th>
                                            <th class="text-center">
                                                <div class="d-md-block d-none">
                                                    <span class="card-roja-directa"></span><br>
                                                    <small>Directa</small>
                                                </div>
                                                <div class="d-md-none">
                                                    <span class="card-roja-directa"></span>
                                                    <small>R</small>
                                                </div>
                                            </th>
                                            <th class="text-center">Pts</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total_equipos = count($fairplay);
                                        foreach ($fairplay as $i => $f): 
                                            $posicion = $i + 1;
                                            $es_mejor = ($i == 0);
                                            $es_peor = ($i == $total_equipos - 1);
                                            $badge_class = 'badge-good';
                                            if ($posicion == 1) $badge_class = 'badge-gold';
                                            elseif ($posicion == 2) $badge_class = 'badge-silver';
                                            elseif ($posicion == 3) $badge_class = 'badge-bronze';
                                            $row_class = '';
                                            if ($es_mejor) $row_class = 'best-team';
                                            elseif ($es_peor) $row_class = 'worst-team';
                                        ?>
                                        <tr class="fairplay-row <?= $row_class ?>" style="background-color: <?= $i % 2 == 0 ? '#f9f9f9' : '#ffffff' ?>;">
                                            <td class="text-center">
                                                <div class="fairplay-badge <?= $badge_class ?>">
                                                    <?= $posicion ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php if ($f['logo']): ?>
                                                        <img src="../uploads/<?= htmlspecialchars($f['logo']) ?>" 
                                                             alt="Logo" class="me-2" width="30" height="30" 
                                                             style="object-fit: cover; border-radius: 50%;">
                                                    <?php else: ?>
                                                        <div class="bg-secondary rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                             style="width: 30px; height: 30px; background-color: <?= $f['color_camiseta'] ?: '#6c757d' ?> !important;">
                                                            <i class="fas fa-shield-alt text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <strong><?= htmlspecialchars($f['equipo']) ?></strong>
                                                    <?php if ($es_mejor): ?>
                                                        <span class="ms-2 trophy-icon text-warning">🏆</span>
                                                    <?php elseif ($es_peor): ?>
                                                        <span class="ms-2 trophy-icon text-danger">⚠️</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?= $f['partidos_jugados'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-warning text-dark"><?= $f['amarillas'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge" style="background-color: #FF6B6B; color: white;"><?= $f['rojas_doble'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge" style="background-color: #8B0000; color: white;"><?= $f['rojas_directa'] ?></span>
                                            </td>
                                            <td class="text-center">
                                                <strong class="text-primary"><?= $f['puntos'] ?></strong>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-body">
                            <h6><i class="fas fa-info-circle"></i> Leyenda:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <small>
                                        <strong>PJ:</strong> Partidos Jugados<br>
                                    </small>
                                </div>
                                <div class="col-md-6">
                                    <small>
                                        🏆 = Equipo más disciplinado<br>
                                        ⚠️ = Equipo menos disciplinado<br>
                                    </small>
                                </div>
                            </div>
                            <hr>
                            <small class="text-muted">
                                <i class="fas fa-calculator"></i> 
                                <strong>Sistema de puntuación:</strong>
                                <br>
                                • Tarjeta Amarilla = 1 punto
                                <br>
                                • Tarjeta Roja por Doble Amarilla = 3 puntos
                                <br>
                                • Tarjeta Roja Directa = 5 puntos
                                <br>
                                <em>Menor puntuación = Mayor disciplina</em>
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div class="container">
        <footer class="footer">
            <h5><i class="fas fa-futbol me-2"></i>Altos del Paracao</h5>
            <p class="mb-0 opacity-75">Torneo Nocturno - Información consolidada</p>
            <small class="opacity-50">
                © <?= date('Y') ?> - Actualizado: <?= date('d/m/Y H:i') ?>
            </small>
        </footer>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>