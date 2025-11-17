<?php
require_once '../config.php';

$db = Database::getInstance()->getConnection();

// Obtener el campeonato seleccionado (por defecto todos los campeonatos)
$campeonato_id = isset($_GET['campeonato_id']) ? (int)$_GET['campeonato_id'] : 0;

// Filtro de campeonato para las consultas
$filtro_campeonato = $campeonato_id > 0 ? "AND c.id = $campeonato_id" : "";
$filtro_campeonato_directo = $campeonato_id > 0 ? "WHERE campeonato_id = $campeonato_id" : "";

// ====================================
// ESTADÍSTICAS GENERALES POR EQUIPO
// ====================================

// Equipo con más partidos jugados
$query_mas_partidos = "
SELECT e.nombre, e.logo,
       COUNT(DISTINCT p.id) as total_partidos
FROM equipos e
JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE p.estado = 'finalizado' $filtro_campeonato
GROUP BY e.id, e.nombre, e.logo
ORDER BY total_partidos DESC
LIMIT 1
";
$equipo_mas_partidos = $db->query($query_mas_partidos)->fetch();

// Equipo con más victorias
$query_mas_victorias = "
SELECT e.nombre, e.logo,
       SUM(CASE 
           WHEN p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante THEN 1
           WHEN p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local THEN 1
           ELSE 0
       END) as total_victorias
FROM equipos e
JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE p.estado = 'finalizado' $filtro_campeonato
GROUP BY e.id, e.nombre, e.logo
ORDER BY total_victorias DESC
LIMIT 1
";
$equipo_mas_victorias = $db->query($query_mas_victorias)->fetch();

// Equipo con más goles a favor
$query_mas_goles_favor = "
SELECT e.nombre, e.logo,
       SUM(CASE 
           WHEN p.equipo_local_id = e.id THEN COALESCE(p.goles_local, 0)
           WHEN p.equipo_visitante_id = e.id THEN COALESCE(p.goles_visitante, 0)
           ELSE 0
       END) as total_goles
FROM equipos e
JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE p.estado = 'finalizado' $filtro_campeonato
GROUP BY e.id, e.nombre, e.logo
ORDER BY total_goles DESC
LIMIT 1
";
$equipo_mas_goles_favor = $db->query($query_mas_goles_favor)->fetch();

// Equipo con más goles en contra
$query_mas_goles_contra = "
SELECT e.nombre, e.logo,
       SUM(CASE 
           WHEN p.equipo_local_id = e.id THEN COALESCE(p.goles_visitante, 0)
           WHEN p.equipo_visitante_id = e.id THEN COALESCE(p.goles_local, 0)
           ELSE 0
       END) as total_goles_contra
FROM equipos e
JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE p.estado = 'finalizado' $filtro_campeonato
GROUP BY e.id, e.nombre, e.logo
ORDER BY total_goles_contra DESC
LIMIT 1
";
$equipo_mas_goles_contra = $db->query($query_mas_goles_contra)->fetch();

// Mayor diferencia de gol histórica
$query_mejor_diferencia = "
SELECT e.nombre, e.logo,
       SUM(CASE 
           WHEN p.equipo_local_id = e.id THEN (COALESCE(p.goles_local, 0) - COALESCE(p.goles_visitante, 0))
           WHEN p.equipo_visitante_id = e.id THEN (COALESCE(p.goles_visitante, 0) - COALESCE(p.goles_local, 0))
           ELSE 0
       END) as diferencia_gol
FROM equipos e
JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE p.estado = 'finalizado' $filtro_campeonato
GROUP BY e.id, e.nombre, e.logo
ORDER BY diferencia_gol DESC
LIMIT 1
";
$equipo_mejor_diferencia = $db->query($query_mejor_diferencia)->fetch();

// ====================================
// ESTADÍSTICAS INDIVIDUALES
// ====================================

// Máximo goleador histórico
$query_goleador_historico = "
SELECT j.apellido_nombre, j.foto, e.nombre as equipo, e.logo,
       COUNT(*) as total_goles
FROM eventos_partido ep
JOIN jugadores j ON ep.jugador_id = j.id
JOIN equipos e ON j.equipo_id = e.id
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE ep.tipo_evento = 'gol' $filtro_campeonato
GROUP BY j.id, j.apellido_nombre, j.foto, e.nombre, e.logo
ORDER BY total_goles DESC
LIMIT 1
";
$goleador_historico = $db->query($query_goleador_historico)->fetch();

// Jugador con más goles en un partido
$query_mas_goles_partido = "
SELECT j.apellido_nombre, e.nombre as equipo, e.logo,
       COUNT(*) as goles_partido, p.fecha_partido,
       el.nombre as rival
FROM eventos_partido ep
JOIN jugadores j ON ep.jugador_id = j.id
JOIN equipos e ON j.equipo_id = e.id
JOIN partidos p ON ep.partido_id = p.id
JOIN equipos el ON (p.equipo_local_id = el.id OR p.equipo_visitante_id = el.id) AND el.id != e.id
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE ep.tipo_evento = 'gol' $filtro_campeonato
GROUP BY j.id, ep.partido_id, j.apellido_nombre, e.nombre, e.logo, p.fecha_partido, el.nombre
ORDER BY goles_partido DESC
LIMIT 1
";
$mas_goles_partido = $db->query($query_mas_goles_partido)->fetch();

// Jugador con más tarjetas amarillas
$query_mas_amarillas = "
SELECT j.apellido_nombre, j.foto, e.nombre as equipo, e.logo,
       COUNT(*) as total_amarillas
FROM eventos_partido ep
JOIN jugadores j ON ep.jugador_id = j.id
JOIN equipos e ON j.equipo_id = e.id
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE ep.tipo_evento = 'amarilla' $filtro_campeonato
GROUP BY j.id, j.apellido_nombre, j.foto, e.nombre, e.logo
ORDER BY total_amarillas DESC
LIMIT 1
";
$mas_amarillas = $db->query($query_mas_amarillas)->fetch();

// Jugador con más tarjetas rojas
$query_mas_rojas = "
SELECT j.apellido_nombre, j.foto, e.nombre as equipo, e.logo,
       COUNT(*) as total_rojas
FROM eventos_partido ep
JOIN jugadores j ON ep.jugador_id = j.id
JOIN equipos e ON j.equipo_id = e.id
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE ep.tipo_evento = 'roja' $filtro_campeonato
GROUP BY j.id, j.apellido_nombre, j.foto, e.nombre, e.logo
ORDER BY total_rojas DESC
LIMIT 1
";
$mas_rojas = $db->query($query_mas_rojas)->fetch();

// ====================================
// RÉCORDS DE PARTIDOS
// ====================================

// Mayor goleada en la historia (solo victorias, no empates)
$query_mayor_goleada = "
SELECT 
    CASE 
        WHEN p.goles_local > p.goles_visitante THEN el.nombre
        ELSE ev.nombre
    END as equipo_ganador,
    CASE 
        WHEN p.goles_local > p.goles_visitante THEN el.logo
        ELSE ev.logo
    END as logo_ganador,
    CASE 
        WHEN p.goles_local < p.goles_visitante THEN el.nombre
        ELSE ev.nombre
    END as equipo_perdedor,
    CASE 
        WHEN p.goles_local < p.goles_visitante THEN el.logo
        ELSE ev.logo
    END as logo_perdedor,
    p.goles_local, p.goles_visitante,
    ABS(p.goles_local - p.goles_visitante) as diferencia,
    p.fecha_partido,
    c.nombre as cancha
FROM partidos p
JOIN equipos el ON p.equipo_local_id = el.id
JOIN equipos ev ON p.equipo_visitante_id = ev.id
JOIN categorias cat ON el.categoria_id = cat.id
JOIN campeonatos camp ON cat.campeonato_id = camp.id
LEFT JOIN canchas c ON p.cancha_id = c.id
WHERE p.estado = 'finalizado' 
AND p.goles_local IS NOT NULL 
AND p.goles_visitante IS NOT NULL
AND p.goles_local != p.goles_visitante
$filtro_campeonato
ORDER BY diferencia DESC, (p.goles_local + p.goles_visitante) DESC
LIMIT 1
";
$mayor_goleada = $db->query($query_mayor_goleada)->fetch();

// Partido con más goles
$query_mas_goles_partido_total = "
SELECT 
    el.nombre as equipo_local, el.logo as logo_local,
    ev.nombre as equipo_visitante, ev.logo as logo_visitante,
    p.goles_local, p.goles_visitante,
    (p.goles_local + p.goles_visitante) as total_goles,
    p.fecha_partido,
    c.nombre as cancha
FROM partidos p
JOIN equipos el ON p.equipo_local_id = el.id
JOIN equipos ev ON p.equipo_visitante_id = ev.id
JOIN categorias cat ON el.categoria_id = cat.id
JOIN campeonatos camp ON cat.campeonato_id = camp.id
LEFT JOIN canchas c ON p.cancha_id = c.id
WHERE p.estado = 'finalizado' 
AND p.goles_local IS NOT NULL 
AND p.goles_visitante IS NOT NULL
$filtro_campeonato
ORDER BY total_goles DESC
LIMIT 1
";
$mas_goles_partido_total = $db->query($query_mas_goles_partido_total)->fetch();

// Partido con más expulsados
$query_mas_expulsados = "
SELECT 
    el.nombre as equipo_local, el.logo as logo_local,
    ev.nombre as equipo_visitante, ev.logo as logo_visitante,
    COUNT(ep.id) as total_expulsiones,
    p.fecha_partido,
    p.goles_local, p.goles_visitante
FROM partidos p
JOIN equipos el ON p.equipo_local_id = el.id
JOIN equipos ev ON p.equipo_visitante_id = ev.id
JOIN categorias cat ON el.categoria_id = cat.id
JOIN campeonatos camp ON cat.campeonato_id = camp.id
JOIN eventos_partido ep ON p.id = ep.partido_id
WHERE ep.tipo_evento = 'roja'
AND p.estado = 'finalizado'
$filtro_campeonato
GROUP BY p.id
ORDER BY total_expulsiones DESC
LIMIT 1
";
$mas_expulsados = $db->query($query_mas_expulsados)->fetch();

// ====================================
// ESTADÍSTICAS ADICIONALES
// ====================================

// Top 5 Goleadores Históricos
$query_top_goleadores = "
SELECT j.apellido_nombre, j.foto, e.nombre as equipo, e.logo,
       COUNT(*) as total_goles
FROM eventos_partido ep
JOIN jugadores j ON ep.jugador_id = j.id
JOIN equipos e ON j.equipo_id = e.id
JOIN categorias cat ON e.categoria_id = cat.id
JOIN campeonatos c ON cat.campeonato_id = c.id
WHERE ep.tipo_evento = 'gol' $filtro_campeonato
GROUP BY j.id, j.apellido_nombre, j.foto, e.nombre, e.logo
ORDER BY total_goles DESC
LIMIT 5
";
$top_goleadores = $db->query($query_top_goleadores)->fetchAll();

// Obtener lista de campeonatos para el filtro
$query_campeonatos = "SELECT id, nombre FROM campeonatos ORDER BY fecha_inicio DESC";
$campeonatos = $db->query($query_campeonatos)->fetchAll();

$campeones_por_categoria = [];
$campeones_por_anio = [];

try {
    $query_campeones_final = "
        SELECT 
            cf.categoria_id,
            camp.id AS campeonato_id,
            pe.ganador_id AS equipo_id,
            e.nombre AS equipo_nombre,
            e.logo,
            COALESCE(pe.finalizado_at, pe.fecha_partido, camp.fecha_fin, camp.fecha_inicio) AS orden_fecha
        FROM partidos_eliminatorios pe
        JOIN fases_eliminatorias fe ON pe.fase_id = fe.id
        JOIN campeonatos_formato cf ON fe.formato_id = cf.id
        JOIN campeonatos camp ON cf.campeonato_id = camp.id
        JOIN equipos e ON pe.ganador_id = e.id
        WHERE fe.nombre = 'final'
          AND pe.estado = 'finalizado'
          AND pe.ganador_id IS NOT NULL
        ORDER BY orden_fecha DESC
    ";
    $stmtCampeonesFinal = $db->query($query_campeones_final);
    foreach ($stmtCampeonesFinal as $row) {
        $key = $row['categoria_id'] . '_' . $row['campeonato_id'];
        if (!isset($campeones_por_categoria[$key])) {
            $campeones_por_categoria[$key] = [
                'equipo_id' => $row['equipo_id'],
                'equipo_nombre' => $row['equipo_nombre'],
                'logo' => $row['logo'],
                'origen' => 'eliminatoria'
            ];
        }
    }
} catch (Exception $e) {
    error_log('Error obteniendo campeones de finales: ' . $e->getMessage());
}

try {
    $query_tabla_general = "
        SELECT 
            c.id AS categoria_id,
            c.campeonato_id,
            e.id AS equipo_id,
            e.nombre AS equipo_nombre,
            e.logo,
            COALESCE(SUM(
                CASE 
                    WHEN (p.equipo_local_id = e.id AND p.goles_local > p.goles_visitante) OR 
                         (p.equipo_visitante_id = e.id AND p.goles_visitante > p.goles_local) THEN 3
                    WHEN p.goles_local = p.goles_visitante AND p.estado = 'finalizado' THEN 1
                    ELSE 0
                END
            ),0) AS puntos,
            COALESCE(SUM(CASE WHEN p.equipo_local_id = e.id THEN p.goles_local WHEN p.equipo_visitante_id = e.id THEN p.goles_visitante ELSE 0 END),0) AS goles_favor,
            COALESCE(SUM(CASE WHEN p.equipo_local_id = e.id THEN p.goles_visitante WHEN p.equipo_visitante_id = e.id THEN p.goles_local ELSE 0 END),0) AS goles_contra,
            COUNT(p.id) AS partidos_jugados
        FROM equipos e
        JOIN categorias c ON e.categoria_id = c.id
        LEFT JOIN partidos p ON (p.equipo_local_id = e.id OR p.equipo_visitante_id = e.id)
            AND p.estado = 'finalizado'
            AND (p.tipo_torneo = 'normal' OR p.tipo_torneo IS NULL)
        GROUP BY e.id, c.id, c.campeonato_id
    ";
    $stmtTablaGeneral = $db->query($query_tabla_general);
    $rankings_por_categoria = [];
    foreach ($stmtTablaGeneral as $row) {
        $key = $row['categoria_id'] . '_' . $row['campeonato_id'];
        $row['puntos'] = (int)$row['puntos'];
        $row['goles_favor'] = (int)$row['goles_favor'];
        $row['goles_contra'] = (int)$row['goles_contra'];
        $row['diferencia_gol'] = $row['goles_favor'] - $row['goles_contra'];
        $row['partidos_jugados'] = (int)$row['partidos_jugados'];
        $rankings_por_categoria[$key][] = $row;
    }

    foreach ($rankings_por_categoria as $key => $equiposCategoria) {
        if (isset($campeones_por_categoria[$key]) || empty($equiposCategoria)) {
            continue;
        }
        usort($equiposCategoria, function ($a, $b) {
            if ($a['puntos'] !== $b['puntos']) {
                return $b['puntos'] <=> $a['puntos'];
            }
            if ($a['diferencia_gol'] !== $b['diferencia_gol']) {
                return $b['diferencia_gol'] <=> $a['diferencia_gol'];
            }
            if ($a['goles_favor'] !== $b['goles_favor']) {
                return $b['goles_favor'] <=> $a['goles_favor'];
            }
            return strcasecmp($a['equipo_nombre'], $b['equipo_nombre']);
        });

        $ganador = $equiposCategoria[0];
        $tiene_datos = ($ganador['partidos_jugados'] > 0) || ($ganador['puntos'] > 0) || ($ganador['goles_favor'] > 0) || ($ganador['goles_contra'] > 0);
        if ($tiene_datos) {
            $campeones_por_categoria[$key] = [
                'equipo_id' => $ganador['equipo_id'],
                'equipo_nombre' => $ganador['equipo_nombre'],
                'logo' => $ganador['logo'],
                'origen' => 'tabla',
                'puntos' => $ganador['puntos']
            ];
        }
    }
} catch (Exception $e) {
    error_log('Error calculando campeones por tabla: ' . $e->getMessage());
}

try {
    $query_categorias_historial = "
        SELECT 
            c.id AS categoria_id,
            c.nombre AS categoria_nombre,
            camp.id AS campeonato_id,
            camp.nombre AS campeonato_nombre,
            YEAR(COALESCE(camp.fecha_fin, camp.fecha_inicio)) AS anio,
            COALESCE(camp.fecha_fin, camp.fecha_inicio) AS fecha_referencia
        FROM categorias c
        JOIN campeonatos camp ON c.campeonato_id = camp.id
        ORDER BY camp.fecha_inicio DESC, c.nombre ASC
    ";
    $stmtCategoriasHistorial = $db->query($query_categorias_historial);
    $categorias_historial = $stmtCategoriasHistorial->fetchAll();

    foreach ($categorias_historial as $cat) {
        $key = $cat['categoria_id'] . '_' . $cat['campeonato_id'];
        if ($campeonato_id !== 0 && $campeonato_id != $cat['campeonato_id']) {
            continue;
        }
        $anio_key = ($cat['anio'] ?? 'Sin año') ?: 'Sin año';
        if (!isset($campeones_por_anio[$anio_key][$cat['campeonato_id']])) {
            $campeones_por_anio[$anio_key][$cat['campeonato_id']] = [
                'campeonato_nombre' => $cat['campeonato_nombre'],
                'categorias' => []
            ];
        }
        $campeones_por_anio[$anio_key][$cat['campeonato_id']]['categorias'][] = [
            'categoria_nombre' => $cat['categoria_nombre'],
            'campeon' => $campeones_por_categoria[$key] ?? null
        ];
    }
    krsort($campeones_por_anio);
} catch (Exception $e) {
    error_log('Error armando historial de campeones: ' . $e->getMessage());
    $campeones_por_anio = [];
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Estadísticas Históricas - Liga Altos del Paracao</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #198754;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #0dcaf0;
            --dark-bg: #1a1a1a;
            --card-bg: #ffffff;
            --text-muted: #6c757d;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .stats-container {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .stat-card {
            background: white;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
            height: 100%;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), #20c997);
        }
        
        .stat-card:hover {
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
            transform: translateY(-4px);
        }
        
        .stat-card.gold::before {
            background: linear-gradient(90deg, #ffc107, #ff9800);
        }
        
        .stat-card.silver::before {
            background: linear-gradient(90deg, #6c757d, #495057);
        }
        
        .stat-card.bronze::before {
            background: linear-gradient(90deg, #fd7e14, #e65100);
        }

        .stat-card.danger::before {
            background: linear-gradient(90deg, #dc3545, #c82333);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 12px;
            color: var(--primary-color);
            transition: transform 0.3s ease;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1);
        }
        
        .stat-value {
            font-size: 2.2rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 12px 0;
            font-family: 'Courier New', monospace;
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .team-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
            margin: 12px auto;
            display: block;
            border-radius: 8px;
            background: #f8f9fa;
            padding: 4px;
            border: 1px solid #e9ecef;
        }
        
        .team-name {
            font-size: 0.95rem;
            font-weight: 600;
            margin-top: 8px;
            color: #2c3e50;
            text-align: center;
        }
        
        .section-title {
            font-size: 1.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 3px solid var(--primary-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: var(--primary-color);
        }
        
        .record-item {
            background: #fff;
            border: 1px solid #e9ecef;
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .record-item:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateX(4px);
        }
        
        .badge-custom {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .vs-separator {
            color: var(--text-muted);
            font-weight: bold;
            margin: 0 12px;
            font-size: 1.2rem;
        }
        
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            border: 1px solid rgba(0,0,0,0.05);
        }
        
        .page-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #145a32 100%);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 6px 16px rgba(0,0,0,0.1);
            text-align: center;
            color: white;
        }

        .page-header h1 {
            color: white;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .page-header p {
            color: rgba(255,255,255,0.9);
            margin: 0;
        }

        .top-goleador-item {
            display: flex;
            align-items: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .top-goleador-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }

        .medal-icon {
            font-size: 2rem;
            margin-right: 15px;
            min-width: 40px;
        }

        .medal-gold { color: #FFD700; }
        .medal-silver { color: #C0C0C0; }
        .medal-bronze { color: #CD7F32; }

        .record-match {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .record-team {
            flex: 1;
            min-width: 150px;
            text-align: center;
        }

        .record-score {
            flex: 0 0 auto;
            text-align: center;
            padding: 0 20px;
        }

        @media (max-width: 768px) {
            .container {
                padding-left: 10px;
                padding-right: 10px;
            }
            
            h1, h2 {
                font-size: 1.5rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            .stat-icon {
                font-size: 1.5rem;
            }
            .record-match {
                flex-direction: column;
            }
            .record-score {
                padding: 15px 0;
            }
            
            .stats-container {
                padding: 15px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .team-logo {
                width: 35px;
                height: 35px;
            }
            
            .top-goleador-item {
                padding: 10px;
                flex-direction: column;
                align-items: flex-start !important;
            }
            
            .medal-icon {
                font-size: 1.5rem;
                margin-right: 10px;
            }
            
            .col-md-12 {
                margin-bottom: 0.75rem;
            }
            
            .filter-section {
                padding: 15px;
            }
            
            .page-header {
                padding: 20px;
            }
        }
        
        @media (max-width: 576px) {
            h1, h2 {
                font-size: 1.25rem;
            }
            
            .stat-value {
                font-size: 1.25rem;
            }
            
            .stat-icon {
                font-size: 1.25rem;
            }
            
            .team-logo {
                width: 30px;
                height: 30px;
            }
            
            .top-goleador-item {
                padding: 8px;
            }
            
            .stats-container {
                padding: 10px;
            }
            
            .stat-card {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include '../include/header.php'; ?>

    <div class="container my-4">
        <!-- Filtro de Campeonato -->
        <div class="filter-section">
            <form method="GET" class="row align-items-center g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold mb-0">
                        <i class="fas fa-filter text-primary"></i> Filtrar por Campeonato:
                    </label>
                </div>
                <div class="col-md-6">
                    <select name="campeonato_id" class="form-select" onchange="this.form.submit()">
                        <option value="0" <?php echo $campeonato_id == 0 ? 'selected' : ''; ?>>
                            Todos los Campeonatos (Histórico)
                        </option>
                        <?php foreach ($campeonatos as $camp): ?>
                            <option value="<?php echo $camp['id']; ?>" 
                                    <?php echo $campeonato_id == $camp['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($camp['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                </div>
            </form>
        </div>

        <!-- Título Principal -->
        <div class="page-header">
            <h1 class="mb-2">
                <i class="fas fa-chart-bar"></i> 
                Estadísticas Históricas
            </h1>
            <p class="mb-0">
                Récords y estadísticas de todos los tiempos
            </p>
        </div>

        <!-- ESTADÍSTICAS GENERALES POR EQUIPO -->
        <div class="stats-container">
            <h2 class="section-title">
                <i class="fas fa-users"></i> Rendimiento Global por Equipo
            </h2>
            
            <div class="row">
                <?php 
                $estadisticas_equipos = [];
                if ($equipo_mas_partidos) {
                    $estadisticas_equipos[] = [
                        'icono' => 'fa-calendar-alt',
                        'color' => 'primary',
                        'titulo' => 'Partidos Jugados',
                        'valor' => $equipo_mas_partidos['total_partidos'],
                        'nombre' => $equipo_mas_partidos['nombre'],
                        'logo' => $equipo_mas_partidos['logo'],
                        'sufijo' => 'partidos'
                    ];
                }
                if ($equipo_mas_victorias) {
                    $estadisticas_equipos[] = [
                        'icono' => 'fa-trophy',
                        'color' => 'warning',
                        'titulo' => 'Victorias',
                        'valor' => $equipo_mas_victorias['total_victorias'],
                        'nombre' => $equipo_mas_victorias['nombre'],
                        'logo' => $equipo_mas_victorias['logo'],
                        'sufijo' => 'victorias'
                    ];
                }
                if ($equipo_mas_goles_favor) {
                    $estadisticas_equipos[] = [
                        'icono' => 'fa-futbol',
                        'color' => 'success',
                        'titulo' => 'Goles a Favor',
                        'valor' => $equipo_mas_goles_favor['total_goles'],
                        'nombre' => $equipo_mas_goles_favor['nombre'],
                        'logo' => $equipo_mas_goles_favor['logo'],
                        'sufijo' => 'goles'
                    ];
                }
                if ($equipo_mejor_diferencia) {
                    $estadisticas_equipos[] = [
                        'icono' => 'fa-chart-line',
                        'color' => 'info',
                        'titulo' => 'Diferencia de Gol',
                        'valor' => '+' . $equipo_mejor_diferencia['diferencia_gol'],
                        'nombre' => $equipo_mejor_diferencia['nombre'],
                        'logo' => $equipo_mejor_diferencia['logo'],
                        'sufijo' => 'diferencia'
                    ];
                }
                if ($equipo_mas_goles_contra) {
                    $estadisticas_equipos[] = [
                        'icono' => 'fa-exclamation-triangle',
                        'color' => 'danger',
                        'titulo' => 'Goles en Contra',
                        'valor' => $equipo_mas_goles_contra['total_goles_contra'],
                        'nombre' => $equipo_mas_goles_contra['nombre'],
                        'logo' => $equipo_mas_goles_contra['logo'],
                        'sufijo' => 'goles'
                    ];
                }
                foreach ($estadisticas_equipos as $index => $stat): ?>
                <div class="col-md-12">
                    <div class="top-goleador-item">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="medal-icon">
                                <i class="fas <?php echo $stat['icono']; ?> text-<?php echo $stat['color']; ?>" style="font-size: 1.8rem;"></i>
                            </div>
                            <?php if ($stat['logo']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($stat['logo']); ?>" 
                                     style="width: 45px; height: 45px; object-fit: contain; margin-right: 15px; border-radius: 8px; background: #f8f9fa; padding: 4px;" alt="Logo">
                            <?php endif; ?>
                            <div>
                                <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($stat['nombre']); ?></h5>
                                <small class="text-muted"><?php echo htmlspecialchars($stat['titulo']); ?></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-0 text-<?php echo $stat['color']; ?> fw-bold">
                                <i class="fas <?php echo $stat['icono']; ?>"></i> <?php echo $stat['valor']; ?>
                            </h3>
                            <small class="text-muted"><?php echo $stat['sufijo']; ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ESTADÍSTICAS INDIVIDUALES -->
        <div class="stats-container">
            <h2 class="section-title">
                <i class="fas fa-user"></i> Récords Individuales
            </h2>
            
            <div class="row">
                <?php 
                $estadisticas_individuales = [];
                if ($goleador_historico) {
                    $estadisticas_individuales[] = [
                        'icono' => 'fa-crown',
                        'color' => 'warning',
                        'titulo' => 'Goleador Histórico',
                        'valor' => $goleador_historico['total_goles'],
                        'nombre' => $goleador_historico['apellido_nombre'],
                        'equipo' => $goleador_historico['equipo'],
                        'logo' => $goleador_historico['logo'],
                        'sufijo' => 'goles'
                    ];
                }
                if ($mas_goles_partido) {
                    $estadisticas_individuales[] = [
                        'icono' => 'fa-fire',
                        'color' => 'danger',
                        'titulo' => 'Goles en Partido',
                        'valor' => $mas_goles_partido['goles_partido'],
                        'nombre' => $mas_goles_partido['apellido_nombre'],
                        'equipo' => $mas_goles_partido['equipo'] . ' vs ' . $mas_goles_partido['rival'],
                        'logo' => $mas_goles_partido['logo'],
                        'sufijo' => 'goles'
                    ];
                }
                if ($mas_amarillas) {
                    $estadisticas_individuales[] = [
                        'icono' => 'fa-square',
                        'color' => 'warning',
                        'titulo' => 'Tarjetas Amarillas',
                        'valor' => $mas_amarillas['total_amarillas'],
                        'nombre' => $mas_amarillas['apellido_nombre'],
                        'equipo' => $mas_amarillas['equipo'],
                        'logo' => $mas_amarillas['logo'],
                        'sufijo' => 'tarjetas'
                    ];
                }
                if ($mas_rojas) {
                    $estadisticas_individuales[] = [
                        'icono' => 'fa-square',
                        'color' => 'danger',
                        'titulo' => 'Tarjetas Rojas',
                        'valor' => $mas_rojas['total_rojas'],
                        'nombre' => $mas_rojas['apellido_nombre'],
                        'equipo' => $mas_rojas['equipo'],
                        'logo' => $mas_rojas['logo'],
                        'sufijo' => 'tarjetas'
                    ];
                }
                foreach ($estadisticas_individuales as $index => $stat): ?>
                <div class="col-md-12">
                    <div class="top-goleador-item">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="medal-icon">
                                <i class="fas <?php echo $stat['icono']; ?> text-<?php echo $stat['color']; ?>" style="font-size: 1.8rem;"></i>
                            </div>
                            <?php if ($stat['logo']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($stat['logo']); ?>" 
                                     style="width: 45px; height: 45px; object-fit: contain; margin-right: 15px; border-radius: 8px; background: #f8f9fa; padding: 4px;" alt="Logo">
                            <?php endif; ?>
                            <div>
                                <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($stat['nombre']); ?></h5>
                                <small class="text-muted"><?php echo htmlspecialchars($stat['equipo']); ?> - <?php echo htmlspecialchars($stat['titulo']); ?></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-0 text-<?php echo $stat['color']; ?> fw-bold">
                                <i class="fas <?php echo $stat['icono']; ?>"></i> <?php echo $stat['valor']; ?>
                            </h3>
                            <small class="text-muted"><?php echo $stat['sufijo']; ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- TOP 5 GOLEADORES -->
        <?php if (!empty($top_goleadores)): ?>
        <div class="stats-container">
            <h2 class="section-title">
                <i class="fas fa-medal"></i> Top 5 Goleadores Históricos
            </h2>
            
            <div class="row">
                <?php foreach ($top_goleadores as $index => $goleador): ?>
                <div class="col-md-12">
                    <div class="top-goleador-item">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="medal-icon">
                                <?php if ($index == 0): ?>
                                    <i class="fas fa-medal medal-gold"></i>
                                <?php elseif ($index == 1): ?>
                                    <i class="fas fa-medal medal-silver"></i>
                                <?php elseif ($index == 2): ?>
                                    <i class="fas fa-medal medal-bronze"></i>
                                <?php else: ?>
                                    <span class="badge bg-secondary" style="font-size: 1.2em; padding: 8px 12px;"><?php echo $index + 1; ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($goleador['logo']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($goleador['logo']); ?>" 
                                     style="width: 45px; height: 45px; object-fit: contain; margin-right: 15px; border-radius: 8px; background: #f8f9fa; padding: 4px;" alt="Logo">
                            <?php endif; ?>
                            <div>
                                <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($goleador['apellido_nombre']); ?></h5>
                                <small class="text-muted"><?php echo htmlspecialchars($goleador['equipo']); ?></small>
                            </div>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-0 text-success fw-bold">
                                <i class="fas fa-futbol"></i> <?php echo $goleador['total_goles']; ?>
                            </h3>
                            <small class="text-muted">goles</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="stats-container">
            <h2 class="section-title">
                <i class="fas fa-flag-checkered"></i> Historial de Campeones
            </h2>
            <?php if (empty($campeones_por_anio)): ?>
                <p class="text-center text-muted py-3 mb-0">
                    No hay registros de campeones para el filtro seleccionado.
                </p>
            <?php else: ?>
                <?php foreach ($campeones_por_anio as $anio => $campeonatos_list): ?>
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-calendar-alt"></i> Temporada <?php echo htmlspecialchars($anio); ?></span>
                            <span class="badge bg-light text-dark">
                                <?php echo count($campeonatos_list); ?> campeonato<?php echo count($campeonatos_list) > 1 ? 's' : ''; ?>
                            </span>
                        </div>
                        <div class="card-body p-0">
                            <?php foreach ($campeonatos_list as $camp): ?>
                                <div class="p-3 border-bottom">
                                    <h5 class="fw-bold mb-3">
                                        <i class="fas fa-trophy text-success"></i> <?php echo htmlspecialchars($camp['campeonato_nombre']); ?>
                                    </h5>
                                    <div class="table-responsive">
                                        <table class="table table-sm align-middle mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="width: 35%;">Categoría</th>
                                                    <th>Campeón</th>
                                                    <th class="text-end" style="width: 15%;">Fuente</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($camp['categorias'] as $cat): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($cat['categoria_nombre']); ?></td>
                                                        <td>
                                                            <?php if (!empty($cat['campeon'])): ?>
                                                                <div class="d-flex align-items-center">
                                                                    <?php if (!empty($cat['campeon']['logo'])): ?>
                                                                        <img src="../uploads/<?php echo htmlspecialchars($cat['campeon']['logo']); ?>" 
                                                                             alt="Logo" width="32" height="32" class="rounded-circle me-2" style="object-fit: cover;">
                                                                    <?php else: ?>
                                                                        <div class="bg-light rounded-circle me-2 d-flex align-items-center justify-content-center" 
                                                                             style="width: 32px; height: 32px;">
                                                                            <i class="fas fa-shield-alt text-muted"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <span class="fw-semibold"><?php echo htmlspecialchars($cat['campeon']['equipo_nombre']); ?></span>
                                                                </div>
                                                            <?php else: ?>
                                                                <span class="text-muted"><i class="fas fa-clock"></i> Sin registro</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td class="text-end">
                                                            <?php if (!empty($cat['campeon'])): ?>
                                                                <span class="badge <?php echo $cat['campeon']['origen'] === 'eliminatoria' ? 'bg-danger' : 'bg-primary'; ?>">
                                                                    <?php echo $cat['campeon']['origen'] === 'eliminatoria' ? 'Final' : 'Tabla'; ?>
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">Pendiente</span>
                                                            <?php endif; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- RÉCORDS DE PARTIDOS -->
        <div class="stats-container">
            <h2 class="section-title">
                <i class="fas fa-fire"></i> Récords de Partidos
            </h2>
            
            <div class="row">
                <?php 
                $estadisticas_partidos = [];
                if ($mayor_goleada) {
                    $estadisticas_partidos[] = [
                        'icono' => 'fa-bolt',
                        'color' => 'warning',
                        'titulo' => 'Mayor Goleada',
                        'valor' => $mayor_goleada['goles_local'] . ' - ' . $mayor_goleada['goles_visitante'],
                        'nombre' => $mayor_goleada['equipo_ganador'] . ' vs ' . $mayor_goleada['equipo_perdedor'],
                        'detalle' => 'Diferencia: ' . $mayor_goleada['diferencia'] . ' goles',
                        'fecha' => date('d/m/Y', strtotime($mayor_goleada['fecha_partido'])),
                        'cancha' => $mayor_goleada['cancha'],
                        'logo' => $mayor_goleada['logo_ganador']
                    ];
                }
                if ($mas_goles_partido_total) {
                    $estadisticas_partidos[] = [
                        'icono' => 'fa-trophy',
                        'color' => 'success',
                        'titulo' => 'Partido con Más Goles',
                        'valor' => $mas_goles_partido_total['goles_local'] . ' - ' . $mas_goles_partido_total['goles_visitante'],
                        'nombre' => $mas_goles_partido_total['equipo_local'] . ' vs ' . $mas_goles_partido_total['equipo_visitante'],
                        'detalle' => 'Total: ' . $mas_goles_partido_total['total_goles'] . ' goles',
                        'fecha' => date('d/m/Y', strtotime($mas_goles_partido_total['fecha_partido'])),
                        'cancha' => $mas_goles_partido_total['cancha'],
                        'logo' => $mas_goles_partido_total['logo_local']
                    ];
                }
                if ($mas_expulsados) {
                    $estadisticas_partidos[] = [
                        'icono' => 'fa-exclamation-triangle',
                        'color' => 'danger',
                        'titulo' => 'Partido con Más Expulsiones',
                        'valor' => $mas_expulsados['goles_local'] . ' - ' . $mas_expulsados['goles_visitante'],
                        'nombre' => $mas_expulsados['equipo_local'] . ' vs ' . $mas_expulsados['equipo_visitante'],
                        'detalle' => $mas_expulsados['total_expulsiones'] . ' expulsiones',
                        'fecha' => date('d/m/Y', strtotime($mas_expulsados['fecha_partido'])),
                        'cancha' => null,
                        'logo' => $mas_expulsados['logo_local']
                    ];
                }
                foreach ($estadisticas_partidos as $index => $stat): ?>
                <div class="col-md-12">
                    <div class="top-goleador-item">
                        <div class="d-flex align-items-center flex-grow-1">
                            <div class="medal-icon">
                                <i class="fas <?php echo $stat['icono']; ?> text-<?php echo $stat['color']; ?>" style="font-size: 1.8rem;"></i>
                            </div>
                            <?php if ($stat['logo']): ?>
                                <img src="../uploads/<?php echo htmlspecialchars($stat['logo']); ?>" 
                                     style="width: 45px; height: 45px; object-fit: contain; margin-right: 15px; border-radius: 8px; background: #f8f9fa; padding: 4px;" alt="Logo">
                            <?php endif; ?>
                            <div>
                                <h5 class="mb-1 fw-bold"><?php echo htmlspecialchars($stat['nombre']); ?></h5>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($stat['titulo']); ?>
                                    <?php if ($stat['fecha']): ?>
                                        | <i class="fas fa-calendar"></i> <?php echo $stat['fecha']; ?>
                                    <?php endif; ?>
                                    <?php if ($stat['cancha']): ?>
                                        | <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($stat['cancha']); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-0 text-<?php echo $stat['color']; ?> fw-bold">
                                <?php echo $stat['valor']; ?>
                            </h3>
                            <small class="text-muted"><?php echo $stat['detalle']; ?></small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-futbol"></i> Liga Altos del Paracao</h5>
                    <p class="text-muted mb-0">Estadísticas históricas y récords</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-0">© <?php echo date('Y'); ?> Todos los derechos reservados</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
</body>
</html>
