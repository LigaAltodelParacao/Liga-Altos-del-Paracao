<?php
// Habilitar errores para debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config.php';
require_once '../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

if (!isLoggedIn() || !hasPermission('admin')) {
    die('Acceso denegado');
}

$db = Database::getInstance()->getConnection();

// Modo: partido individual o todas las planillas de una fecha
$partido_id = $_GET['partido_id'] ?? null;
$fecha_id = $_GET['fecha_id'] ?? null;
$todas = $_GET['todas'] ?? 0;

if (!$partido_id && !$fecha_id) {
    die('Debe especificar partido_id o fecha_id');
}

// Si es "todas", generar ZIP
if ($todas && $fecha_id) {
    generarZipPlanillas($fecha_id, $db);
    exit;
}

// Si es un partido individual
if ($partido_id) {
    $html = generarHtmlPlanilla($partido_id, $db);
    
    // Configurar Dompdf
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');
    
    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('legal', 'portrait'); // Tamaño oficio
    $dompdf->render();
    
    // Nombre del archivo
    $stmt = $db->prepare("
        SELECT el.nombre as local, ev.nombre as visitante, f.numero_fecha
        FROM partidos p
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        JOIN fechas f ON p.fecha_id = f.id
        WHERE p.id = ?
    ");
    $stmt->execute([$partido_id]);
    $info = $stmt->fetch();
    
    $filename = "Planilla_Fecha{$info['numero_fecha']}_{$info['local']}_vs_{$info['visitante']}.pdf";
    $filename = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $filename);
    
    $dompdf->stream($filename, ['Attachment' => true]);
}

// ============================================
// FUNCIÓN: GENERAR HTML DE PLANILLA
// ============================================
function generarHtmlPlanilla($partido_id, $db) {
    // Obtener datos del partido
    $stmt = $db->prepare("
        SELECT p.*, 
               el.nombre as equipo_local, el.id as local_id,
               ev.nombre as equipo_visitante, ev.id as visitante_id,
               c.nombre as cancha,
               cat.nombre as categoria,
               camp.nombre as campeonato,
               f.numero_fecha
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        JOIN categorias cat ON f.categoria_id = cat.id
        JOIN campeonatos camp ON cat.campeonato_id = camp.id
        LEFT JOIN canchas c ON p.cancha_id = c.id
        WHERE p.id = ?
    ");
    $stmt->execute([$partido_id]);
    $partido = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$partido) {
        die('Partido no encontrado');
    }
    
    // Obtener jugadores del equipo local
    $jugadores_local = obtenerJugadoresEquipo($partido['local_id'], $db);
    
    // Obtener jugadores del equipo visitante
    $jugadores_visitante = obtenerJugadoresEquipo($partido['visitante_id'], $db);
    
    // Generar HTML
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            @page { 
                margin: 10mm; 
                size: legal portrait;
            }
            body {
                font-family: "DejaVu Sans", Arial, sans-serif;
                font-size: 9pt;
                margin: 0;
                padding: 0;
            }
            .header {
                text-align: center;
                margin-bottom: 15px;
                border-bottom: 3px solid #000;
                padding-bottom: 10px;
            }
            .header h1 {
                margin: 0;
                font-size: 18pt;
                font-weight: bold;
            }
            .header p {
                margin: 3px 0;
                font-size: 10pt;
            }
            .info-partido {
                display: table;
                width: 100%;
                margin-bottom: 10px;
                border: 2px solid #000;
                padding: 8px;
                background-color: #f0f0f0;
            }
            .info-partido td {
                padding: 3px 8px;
            }
            .equipo-seccion {
                border: 2px solid #000;
                margin-bottom: 10px;
                page-break-inside: avoid;
            }
            .equipo-header {
                background-color: #333;
                color: white;
                padding: 8px;
                font-size: 12pt;
                font-weight: bold;
                text-align: center;
            }
            table.jugadores {
                width: 100%;
                border-collapse: collapse;
                margin: 0;
            }
            table.jugadores th {
                background-color: #666;
                color: white;
                padding: 6px 4px;
                text-align: center;
                font-size: 8pt;
                border: 1px solid #000;
            }
            table.jugadores td {
                padding: 8px 4px;
                border: 1px solid #000;
                text-align: center;
                font-size: 8pt;
            }
            .sancionado {
                background-color: #ffeb3b !important;
                font-weight: bold;
            }
            .observaciones {
                padding: 8px;
                background-color: #fff3cd;
                border-top: 2px solid #000;
                font-size: 7pt;
            }
            .firmas {
                padding: 10px;
                background-color: #f8f9fa;
                border-top: 2px solid #000;
            }
            .firma-box {
                display: inline-block;
                width: 30%;
                text-align: center;
                margin: 5px 1%;
                vertical-align: top;
            }
            .firma-linea {
                border-bottom: 1px solid #000;
                height: 40px;
                margin-bottom: 3px;
            }
            .arbitro-seccion {
                border: 2px solid #000;
                padding: 10px;
                margin-top: 10px;
                background-color: #e9ecef;
            }
            .col-num { width: 6%; }
            .col-apellido { width: 30%; }
            .col-dni { width: 12%; }
            .col-fecha { width: 10%; }
            .col-edad { width: 6%; }
            .col-goles { width: 8%; }
            .col-amarilla { width: 8%; }
            .col-roja { width: 8%; }
            .col-firma { width: 12%; }
        </style>
    </head>
    <body>';
    
    // HEADER
    $html .= '
        <div class="header">
            <h1>PLANILLA OFICIAL DE PARTIDO</h1>
            <p><strong>' . htmlspecialchars($partido['campeonato']) . '</strong></p>
            <p>' . htmlspecialchars($partido['categoria']) . '</p>
        </div>';
    
    // INFO DEL PARTIDO
    $html .= '
        <table class="info-partido">
            <tr>
                <td><strong>Fecha:</strong> ' . formatDate($partido['fecha_partido']) . '</td>
                <td><strong>Hora:</strong> ' . ($partido['hora_partido'] ? date('H:i', strtotime($partido['hora_partido'])) : '______') . '</td>
                <td><strong>Cancha:</strong> ' . ($partido['cancha'] ? htmlspecialchars($partido['cancha']) : '_______________') . '</td>
                <td><strong>Fecha N°:</strong> ' . $partido['numero_fecha'] . '</td>
            </tr>
        </table>';
    
    // EQUIPO LOCAL
    $html .= generarSeccionEquipo($partido['equipo_local'], $jugadores_local, 'LOCAL');
    
    // ESPACIO ENTRE EQUIPOS
    $html .= '<div style="height: 15px;"></div>';
    
    // EQUIPO VISITANTE
    $html .= generarSeccionEquipo($partido['equipo_visitante'], $jugadores_visitante, 'VISITANTE');
    
    // SECCIÓN ÁRBITRO
    $html .= '
        <div class="arbitro-seccion">
            <table style="width: 100%;">
                <tr>
                    <td style="width: 60%;"><strong>ÁRBITRO:</strong> _______________________________________</td>
                    <td><strong>DNI:</strong> ___________________</td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top: 10px;">
                        <div style="border-bottom: 1px solid #000; height: 40px; margin-bottom: 5px;"></div>
                        <div style="text-align: center; font-size: 8pt;">FIRMA DEL ÁRBITRO</div>
                    </td>
                </tr>
            </table>
        </div>';
    
    $html .= '</body></html>';
    
    return $html;
}

// ============================================
// FUNCIÓN: GENERAR SECCIÓN DE EQUIPO
// ============================================
function generarSeccionEquipo($nombre_equipo, $jugadores, $tipo) {
    $html = '<div class="equipo-seccion">';
    $html .= '<div class="equipo-header">EQUIPO ' . $tipo . ': ' . htmlspecialchars($nombre_equipo) . '</div>';
    
    $html .= '
        <table class="jugadores">
            <thead>
                <tr>
                    <th class="col-num">N°</th>
                    <th class="col-apellido">APELLIDO Y NOMBRE</th>
                    <th class="col-dni">DNI</th>
                    <th class="col-fecha">FECHA NAC.</th>
                    <th class="col-edad">EDAD</th>
                    <th class="col-goles">GOLES</th>
                    <th class="col-amarilla">AMAR.</th>
                    <th class="col-roja">ROJA</th>
                    <th class="col-firma">FIRMA</th>
                </tr>
            </thead>
            <tbody>';
    
    $observaciones = [];
    
    foreach ($jugadores as $jugador) {
        $clase_sancion = $jugador['sancionado'] ? 'sancionado' : '';
        
        $html .= '<tr class="' . $clase_sancion . '">';
        $html .= '<td></td>'; // Número de camiseta (vacío para llenar a mano)
        $html .= '<td style="text-align: left; padding-left: 5px;">' . htmlspecialchars($jugador['apellido_nombre']) . '</td>';
        $html .= '<td>' . htmlspecialchars($jugador['dni']) . '</td>';
        $html .= '<td>' . formatDate($jugador['fecha_nacimiento']) . '</td>';
        $html .= '<td>' . $jugador['edad'] . '</td>';
        $html .= '<td></td>'; // Goles (vacío)
        $html .= '<td></td>'; // Amarilla (vacío)
        $html .= '<td></td>'; // Roja (vacío)
        $html .= '<td></td>'; // Firma (vacío)
        $html .= '</tr>';
        
        // Agregar observaciones si está sancionado
        if ($jugador['sancionado']) {
            $obs = htmlspecialchars($jugador['apellido_nombre']) . ' - ' . $jugador['sanciones_detalle'];
            $observaciones[] = $obs;
        }
    }
    
    $html .= '</tbody></table>';
    
    // Observaciones
    if (!empty($observaciones)) {
        $html .= '<div class="observaciones">';
        $html .= '<strong>OBSERVACIONES:</strong><br>';
        foreach ($observaciones as $obs) {
            $html .= '• ' . $obs . '<br>';
        }
        $html .= '</div>';
    }
    
    // Firmas
    $html .= '
        <div class="firmas">
            <div class="firma-box">
                <div class="firma-linea"></div>
                <strong>DELEGADO</strong><br>
                <small>Aclaración y Firma</small>
            </div>
            <div class="firma-box">
                <div class="firma-linea"></div>
                <strong>DIRECTOR TÉCNICO</strong><br>
                <small>Aclaración y Firma</small>
            </div>
            <div class="firma-box">
                <div class="firma-linea"></div>
                <strong>CAPITÁN</strong><br>
                <small>Aclaración y Firma</small>
            </div>
        </div>';
    
    $html .= '</div>';
    
    return $html;
}

// ============================================
// FUNCIÓN: OBTENER JUGADORES DEL EQUIPO
// ============================================
function obtenerJugadoresEquipo($equipo_id, $db) {
    $stmt = $db->prepare("
        SELECT 
            j.id,
            j.apellido_nombre,
            j.dni,
            j.fecha_nacimiento,
            TIMESTAMPDIFF(YEAR, j.fecha_nacimiento, CURDATE()) as edad,
            CASE WHEN EXISTS (
                SELECT 1 FROM sanciones s 
                WHERE s.jugador_id = j.id 
                AND s.activa = 1 
                AND s.partidos_cumplidos < s.partidos_suspension
            ) THEN 1 ELSE 0 END as sancionado
        FROM jugadores j
        WHERE j.equipo_id = ?
        AND j.activo = 1
        ORDER BY j.apellido_nombre
    ");
    $stmt->execute([$equipo_id]);
    $jugadores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener detalles de sanciones para jugadores sancionados
    foreach ($jugadores as &$jugador) {
        if ($jugador['sancionado']) {
            $stmt = $db->prepare("
                SELECT 
                    s.tipo,
                    s.partidos_suspension,
                    s.partidos_cumplidos,
                    (s.partidos_suspension - s.partidos_cumplidos) as fechas_restantes,
                    CASE s.tipo
                        WHEN 'amarillas_acumuladas' THEN '4 Amarillas'
                        WHEN 'doble_amarilla' THEN 'Doble Amarilla'
                        WHEN 'roja_directa' THEN 'Roja Directa'
                        WHEN 'administrativa' THEN 'Sanción Admin'
                    END as tipo_desc
                FROM sanciones s
                WHERE s.jugador_id = ?
                AND s.activa = 1
                AND s.partidos_cumplidos < s.partidos_suspension
                ORDER BY s.fecha_sancion DESC
                LIMIT 1
            ");
            $stmt->execute([$jugador['id']]);
            $sancion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sancion) {
                $jugador['sanciones_detalle'] = $sancion['tipo_desc'] . ' (' . $sancion['fechas_restantes'] . ' fecha' . ($sancion['fechas_restantes'] > 1 ? 's' : '') . ' restante' . ($sancion['fechas_restantes'] > 1 ? 's' : '') . ')';
            } else {
                $jugador['sanciones_detalle'] = 'Sancionado';
            }
        }
    }
    
    return $jugadores;
}

// ============================================
// FUNCIÓN: GENERAR ZIP CON TODAS LAS PLANILLAS
// ============================================
function generarZipPlanillas($fecha_id, $db) {
    // Obtener todos los partidos de la fecha
    $stmt = $db->prepare("
        SELECT p.id, el.nombre as local, ev.nombre as visitante, f.numero_fecha
        FROM partidos p
        JOIN fechas f ON p.fecha_id = f.id
        JOIN equipos el ON p.equipo_local_id = el.id
        JOIN equipos ev ON p.equipo_visitante_id = ev.id
        WHERE p.fecha_id = ?
        ORDER BY p.hora_partido
    ");
    $stmt->execute([$fecha_id]);
    $partidos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($partidos)) {
        die('No hay partidos para esta fecha');
    }
    
    // Crear directorio temporal
    $temp_dir = sys_get_temp_dir() . '/planillas_' . time();
    mkdir($temp_dir);
    
    $numero_fecha = $partidos[0]['numero_fecha'];
    
    // Generar cada PDF
    foreach ($partidos as $index => $partido) {
        $html = generarHtmlPlanilla($partido['id'], $db);
        
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('legal', 'portrait');
        $dompdf->render();
        
        $filename = sprintf('%02d_Planilla_%s_vs_%s.pdf', 
            $index + 1,
            preg_replace('/[^a-zA-Z0-9]/', '_', $partido['local']),
            preg_replace('/[^a-zA-Z0-9]/', '_', $partido['visitante'])
        );
        
        file_put_contents($temp_dir . '/' . $filename, $dompdf->output());
    }
    
    // Crear ZIP
    $zip_filename = 'Planillas_Fecha_' . $numero_fecha . '.zip';
    $zip_path = sys_get_temp_dir() . '/' . $zip_filename;
    
    $zip = new ZipArchive();
    if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
        $files = scandir($temp_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $zip->addFile($temp_dir . '/' . $file, $file);
            }
        }
        $zip->close();
    }
    
    // Enviar ZIP
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zip_filename . '"');
    header('Content-Length: ' . filesize($zip_path));
    readfile($zip_path);
    
    // Limpiar archivos temporales
    array_map('unlink', glob($temp_dir . '/*'));
    rmdir($temp_dir);
    unlink($zip_path);
}
?>