<?php
require_once '../config/database.php';
session_start();

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Procesar creación de torneo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_torneo'])) {
    $nombre = $_POST['nombre'];
    $cantidad_zonas = intval($_POST['cantidad_zonas']);
    $equipos_seleccionados = $_POST['equipos'] ?? [];
    $cantidad_equipos = count($equipos_seleccionados);
    
    // Validar clasificación
    $primeros = $cantidad_zonas; // Siempre clasifican todos los primeros
    $segundos = intval($_POST['segundos'] ?? 0);
    $terceros = intval($_POST['terceros'] ?? 0);
    $cuartos = intval($_POST['cuartos'] ?? 0);
    
    if ($cantidad_equipos < 4) {
        $error = "Debe seleccionar al menos 4 equipos";
    } elseif ($cantidad_zonas < 2) {
        $error = "Debe haber al menos 2 zonas";
    } elseif ($cantidad_zonas > $cantidad_equipos) {
        $error = "No puede haber más zonas que equipos";
    } else {
        // Calcular distribución de equipos por zona
        $distribucion = calcularDistribucionZonas($cantidad_equipos, $cantidad_zonas);
        
        // Iniciar transacción
        $conn->begin_transaction();
        
        try {
            // Crear torneo
            $sql = "INSERT INTO torneos (nombre, tipo_torneo, estado, primeros, segundos, terceros, cuartos) 
                    VALUES (?, 'zonas', 'activo', ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siiii", $nombre, $primeros, $segundos, $terceros, $cuartos);
            $stmt->execute();
            $torneo_id = $conn->insert_id;
            
            // Mezclar equipos aleatoriamente para distribución equitativa
            shuffle($equipos_seleccionados);
            
            // Crear zonas y asignar equipos
            $idx_equipo = 0;
            $letras_zonas = range('A', 'Z');
            
            foreach ($distribucion as $idx_zona => $cant_equipos_zona) {
                $nombre_zona = "Zona " . $letras_zonas[$idx_zona];
                
                // Crear zona
                $sql = "INSERT INTO zonas (torneo_id, nombre) VALUES (?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("is", $torneo_id, $nombre_zona);
                $stmt->execute();
                $zona_id = $conn->insert_id;
                
                // Asignar equipos a esta zona
                for ($i = 0; $i < $cant_equipos_zona; $i++) {
                    if ($idx_equipo < $cantidad_equipos) {
                        $equipo_id = $equipos_seleccionados[$idx_equipo];
                        
                        $sql = "INSERT INTO zona_equipos (zona_id, equipo_id) VALUES (?, ?)";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ii", $zona_id, $equipo_id);
                        $stmt->execute();
                        
                        $idx_equipo++;
                    }
                }
                
                // Generar fixture para esta zona (todos contra todos)
                generarFixtureZona($zona_id, $torneo_id, $conn);
            }
            
            $conn->commit();
            $mensaje = "Torneo creado exitosamente con " . count($distribucion) . " zonas";
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error al crear torneo: " . $e->getMessage();
        }
    }
}

// Función para calcular distribución de equipos en zonas
function calcularDistribucionZonas($total_equipos, $num_zonas) {
    $equipos_por_zona = floor($total_equipos / $num_zonas);
    $equipos_sobrantes = $total_equipos % $num_zonas;
    
    $distribucion = [];
    for ($i = 0; $i < $num_zonas; $i++) {
        // Las primeras zonas llevan un equipo extra si hay sobrantes
        $distribucion[] = $equipos_por_zona + ($i < $equipos_sobrantes ? 1 : 0);
    }
    
    return $distribucion;
}

// Función para generar fixture de zona (todos contra todos)
function generarFixtureZona($zona_id, $torneo_id, $conn) {
    // Obtener equipos de la zona
    $sql = "SELECT equipo_id FROM zona_equipos WHERE zona_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $zona_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $equipos = [];
    while ($row = $result->fetch_assoc()) {
        $equipos[] = $row['equipo_id'];
    }
    
    $num_equipos = count($equipos);
    
    // Si hay número impar, agregar "BYE"
    if ($num_equipos % 2 != 0) {
        $equipos[] = null;
        $num_equipos++;
    }
    
    // Algoritmo Round Robin
    $jornadas = $num_equipos - 1;
    $partidos_por_jornada = $num_equipos / 2;
    
    $orden = 1;
    
    for ($jornada = 0; $jornada < $jornadas; $jornada++) {
        for ($i = 0; $i < $partidos_por_jornada; $i++) {
            $local = $equipos[$i];
            $visitante = $equipos[$num_equipos - 1 - $i];
            
            // No crear partido si alguno es BYE
            if ($local !== null && $visitante !== null) {
                $sql = "INSERT INTO partidos (torneo_id, zona_id, fase, equipo_local_id, equipo_visitante_id, orden)
                        VALUES (?, ?, 'clasificatoria', ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iiiii", $torneo_id, $zona_id, $local, $visitante, $orden);
                $stmt->execute();
                $orden++;
            }
        }
        
        // Rotar equipos (el primero queda fijo)
        $ultimo = array_pop($equipos);
        array_splice($equipos, 1, 0, [$ultimo]);
    }
}

// Obtener equipos disponibles
$sql_equipos = "SELECT * FROM equipos WHERE estado = 'activo' ORDER BY nombre";
$equipos = $conn->query($sql_equipos);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Torneo por Zonas - Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            text-align: center;
        }
        .subtitulo {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        .mensaje {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }
        .mensaje.exito {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .mensaje.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .form-group {
            margin-bottom: 25px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }
        .equipos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: #f8f9fa;
        }
        .equipo-checkbox {
            display: flex;
            align-items: center;
            padding: 10px;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .equipo-checkbox:hover {
            background: #e9ecef;
        }
        .equipo-checkbox input {
            margin-right: 10px;
            width: auto;
        }
        .preview-distribucion {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
            display: none;
        }
        .preview-distribucion h3 {
            color: #0066cc;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .zona-preview {
            display: inline-block;
            background: white;
            padding: 8px 15px;
            margin: 5px;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        .clasificacion-section {
            background: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .clasificacion-section h3 {
            color: #856404;
            margin-bottom: 15px;
        }
        .clasificacion-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }
        .clasificacion-item {
            display: flex;
            flex-direction: column;
        }
        .clasificacion-item label {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .clasificacion-item input {
            padding: 8px;
        }
        .btn-crear {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn-crear:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-volver {
            display: inline-block;
            margin-bottom: 20px;
            padding: 10px 20px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .info-box {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            color: #0c5460;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="index.php" class="btn-volver">← Volver al Panel</a>
        <h1>🏆 Crear Torneo por Zonas</h1>
        <p class="subtitulo">Sistema automático de distribución de equipos</p>
        
        <?php if (isset($mensaje)): ?>
            <div class="mensaje exito"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="mensaje error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="info-box">
            <strong>ℹ️ Instrucciones:</strong><br>
            1. Ingrese el nombre del torneo<br>
            2. Defina la cantidad de zonas (2-26)<br>
            3. Seleccione los equipos participantes<br>
            4. Configure la clasificación (primeros siempre clasifican)<br>
            5. El sistema distribuirá automáticamente los equipos de forma equitativa
        </div>
        
        <form method="POST" id="form-torneo">
            <div class="form-group">
                <label>Nombre del Torneo *</label>
                <input type="text" name="nombre" required placeholder="Ej: Apertura 2024">
            </div>
            
            <div class="form-group">
                <label>Cantidad de Zonas * (2-26)</label>
                <input type="number" name="cantidad_zonas" id="cantidad_zonas" 
                       min="2" max="26" required value="4" 
                       onchange="actualizarPreview()">
            </div>
            
            <div class="form-group">
                <label>Seleccionar Equipos Participantes *</label>
                <div style="margin-bottom: 10px;">
                    <button type="button" onclick="seleccionarTodos()" 
                            style="padding: 5px 15px; margin-right: 10px;">Seleccionar Todos</button>
                    <button type="button" onclick="deseleccionarTodos()"
                            style="padding: 5px 15px;">Deseleccionar Todos</button>
                    <span id="contador-equipos" style="margin-left: 15px; font-weight: bold;"></span>
                </div>
                <div class="equipos-grid" id="equipos-grid">
                    <?php while ($equipo = $equipos->fetch_assoc()): ?>
                        <label class="equipo-checkbox">
                            <input type="checkbox" name="equipos[]" 
                                   value="<?php echo $equipo['id']; ?>"
                                   onchange="actualizarPreview()">
                            <?php echo htmlspecialchars($equipo['nombre']); ?>
                        </label>
                    <?php endwhile; ?>
                </div>
            </div>
            
            <div id="preview-distribucion" class="preview-distribucion">
                <h3>📊 Distribución Automática de Equipos</h3>
                <div id="preview-content"></div>
            </div>
            
            <div class="clasificacion-section">
                <h3>⭐ Configuración de Clasificación</h3>
                <p style="margin-bottom: 15px; font-size: 14px; color: #666;">
                    Los primeros de cada zona SIEMPRE clasifican. Configure adicionales:
                </p>
                <div class="clasificacion-grid">
                    <div class="clasificacion-item">
                        <label>Segundos que clasifican</label>
                        <input type="number" name="segundos" min="0" value="0" id="segundos">
                    </div>
                    <div class="clasificacion-item">
                        <label>Terceros que clasifican</label>
                        <input type="number" name="terceros" min="0" value="0" id="terceros">
                    </div>
                    <div class="clasificacion-item">
                        <label>Cuartos que clasifican</label>
                        <input type="number" name="cuartos" min="0" value="0" id="cuartos">
                    </div>
                </div>
                <div id="total-clasificados" style="margin-top: 15px; padding: 10px; background: white; border-radius: 5px;"></div>
            </div>
            
            <button type="submit" name="crear_torneo" class="btn-crear">
                Crear Torneo
            </button>
        </form>
    </div>
    
    <script>
        function actualizarPreview() {
            const cantidadZonas = parseInt(document.getElementById('cantidad_zonas').value) || 0;
            const checkboxes = document.querySelectorAll('input[name="equipos[]"]:checked');
            const cantidadEquipos = checkboxes.length;
            
            document.getElementById('contador-equipos').textContent = 
                `${cantidadEquipos} equipos seleccionados`;
            
            if (cantidadZonas > 0 && cantidadEquipos >= 4) {
                const distribucion = calcularDistribucion(cantidadEquipos, cantidadZonas);
                mostrarPreview(distribucion, cantidadZonas);
                document.getElementById('preview-distribucion').style.display = 'block';
            } else {
                document.getElementById('preview-distribucion').style.display = 'none';
            }
            
            actualizarTotalClasificados();
        }
        
        function calcularDistribucion(totalEquipos, numZonas) {
            const equiposPorZona = Math.floor(totalEquipos / numZonas);
            const sobrantes = totalEquipos % numZonas;
            
            const distribucion = [];
            for (let i = 0; i < numZonas; i++) {
                distribucion.push(equiposPorZona + (i < sobrantes ? 1 : 0));
            }
            return distribucion;
        }
        
        function mostrarPreview(distribucion, numZonas) {
            const letras = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            let html = '';
            
            for (let i = 0; i < numZonas; i++) {
                html += `<div class="zona-preview">
                    <strong>Zona ${letras[i]}:</strong> ${distribucion[i]} equipos
                </div>`;
            }
            
            document.getElementById('preview-content').innerHTML = html;
        }
        
        function actualizarTotalClasificados() {
            const cantidadZonas = parseInt(document.getElementById('cantidad_zonas').value) || 0;
            const segundos = parseInt(document.getElementById('segundos').value) || 0;
            const terceros = parseInt(document.getElementById('terceros').value) || 0;
            const cuartos = parseInt(document.getElementById('cuartos').value) || 0;
            
            const total = cantidadZonas + segundos + terceros + cuartos;
            
            document.getElementById('total-clasificados').innerHTML = 
                `<strong>Total de equipos que clasificarán:</strong> ${total} equipos<br>` +
                `<small>(${cantidadZonas} primeros + ${segundos} segundos + ${terceros} terceros + ${cuartos} cuartos)</small>`;
        }
        
        function seleccionarTodos() {
            document.querySelectorAll('input[name="equipos[]"]').forEach(cb => cb.checked = true);
            actualizarPreview();
        }
        
        function deseleccionarTodos() {
            document.querySelectorAll('input[name="equipos[]"]').forEach(cb => cb.checked = false);
            actualizarPreview();
        }
        
        // Validación del formulario
        document.getElementById('form-torneo').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input[name="equipos[]"]:checked');
            if (checkboxes.length < 4) {
                e.preventDefault();
                alert('Debe seleccionar al menos 4 equipos para crear el torneo');
                return false;
            }
            
            const cantidadZonas = parseInt(document.getElementById('cantidad_zonas').value);
            if (cantidadZonas > checkboxes.length) {
                e.preventDefault();
                alert('No puede haber más zonas que equipos seleccionados');
                return false;
            }
        });
        
        // Actualizar preview inicial
        actualizarPreview();
    </script>
</body>
</html>