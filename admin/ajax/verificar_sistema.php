<?php
require_once 'config.php';

$archivos_necesarios = [
    'admin/campeonatos_zonas.php' => 'Lista de formatos creados',
    'admin/campeonatos_zonas_crear.php' => 'Crear formato y zonas',
    'admin/campeonatos_zonas_asignar.php' => 'Asignar equipos a zonas',
    'admin/campeonatos_zonas_fixture.php' => 'Generar fixture de partidos',
    'admin/campeonatos_zonas_partidos.php' => 'Cargar resultados',
    'admin/campeonatos_zonas_tabla.php' => 'Ver tabla de posiciones',
    'admin/ajax/generar_fixture.php' => 'AJAX: Generar fixture',
    'admin/ajax/guardar_resultado.php' => 'AJAX: Guardar resultado de partido',
];

echo "<h2>🔍 Verificación del Sistema de Campeonatos</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #007bff; color: white;'>
        <th>Estado</th>
        <th>Archivo</th>
        <th>Descripción</th>
        <th>Tamaño</th>
      </tr>";

$faltantes = [];

foreach ($archivos_necesarios as $archivo => $descripcion) {
    $existe = file_exists($archivo);
    $tamano = $existe ? filesize($archivo) . ' bytes' : 'N/A';
    $estado = $existe ? '✅' : '❌';
    $color = $existe ? '#d4edda' : '#f8d7da';
    
    if (!$existe) {
        $faltantes[] = $archivo;
    }
    
    echo "<tr style='background: $color;'>
            <td style='text-align: center; font-size: 20px;'>$estado</td>
            <td><strong>$archivo</strong></td>
            <td>$descripcion</td>
            <td>$tamano</td>
          </tr>";
}

echo "</table>";

if (empty($faltantes)) {
    echo "<div style='background: #d4edda; padding: 20px; margin: 20px 0; border-radius: 5px;'>
            <h3>✅ ¡SISTEMA COMPLETO!</h3>
            <p>Todos los archivos necesarios están presentes.</p>
            <a href='admin/campeonatos_zonas.php' style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>
                🚀 Ir al Sistema de Campeonatos
            </a>
          </div>";
} else {
    echo "<div style='background: #fff3cd; padding: 20px; margin: 20px 0; border-radius: 5px;'>
            <h3>⚠️ Faltan " . count($faltantes) . " archivos</h3>
            <p>Se necesitan crear los siguientes archivos:</p>
            <ul>";
    
    foreach ($faltantes as $faltante) {
        echo "<li><code>$faltante</code></li>";
    }
    
    echo "</ul>
            <a href='crear_sistema_completo.php' style='display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 10px;'>
                🔧 Crear Archivos Faltantes
            </a>
          </div>";
}

echo "<hr>";
echo "<h3>📚 Estructura de la Base de Datos</h3>";

try {
    $db = Database::getInstance()->getConnection();
    
    $tablas = [
        'campeonatos' => 'Campeonatos principales',
        'categorias' => 'Categorías del campeonato',
        'campeonatos_formato' => 'Formatos de campeonato (zonas/grupos)',
        'zonas' => 'Zonas/Grupos del formato',
        'equipos' => 'Equipos participantes',
        'equipos_zonas' => 'Asignación equipos-zonas',
        'partidos' => 'Fixture de partidos',
    ];
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #6c757d; color: white;'>
            <th>Tabla</th>
            <th>Descripción</th>
            <th>Registros</th>
          </tr>";
    
    foreach ($tablas as $tabla => $descripcion) {
        $stmt = $db->query("SELECT COUNT(*) as total FROM $tabla");
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        $total = $resultado['total'];
        
        echo "<tr>
                <td><strong>$tabla</strong></td>
                <td>$descripcion</td>
                <td style='text-align: center;'>$total</td>
              </tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 10px; border-radius: 5px;'>
            ❌ Error al verificar base de datos: " . $e->getMessage() . "
          </div>";
}
?>