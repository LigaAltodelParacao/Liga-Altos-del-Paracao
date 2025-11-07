<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('admin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$user = getCurrentUser();

// Obtener todos los campeonatos activos
$stmt = $db->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM categorias WHERE campeonato_id = c.id) as total_categorias
    FROM campeonatos c 
    WHERE c.activo = 1
    ORDER BY c.created_at DESC
");
$campeonatos = $stmt->fetchAll();

// Obtener formatos existentes
$stmt = $db->query("
    SELECT cf.*, c.nombre as campeonato_nombre,
           (SELECT COUNT(*) FROM zonas WHERE formato_id = cf.id) as total_zonas
    FROM campeonatos_formato cf
    JOIN campeonatos c ON cf.campeonato_id = c.id
    ORDER BY cf.created_at DESC
");
$formatos = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campeonatos con Zonas - Sistema de Campeonatos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .formato-card {
            border: 2px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .formato-card:hover {
            border-color: #198754;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .zona-badge {
            display: inline-block;
            padding: 5px 15px;
            margin: 5px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px;
            font-weight: bold;
        }
        .fase-badge {
            display: inline-block;
            padding: 5px 12px;
            margin: 3px;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 15px;
            font-size: 0.85em;
        }
        .fase-badge.active {
            background: #d4edda;
            border-color: #c3e6cb;
            color: #155724;
        }
        .calculadora-info {
            background: #e7f3ff;
            border: 2px solid #0066cc;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-success">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">
                <i class="fas fa-futbol"></i> Fútbol Manager - Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link" href="../logout.php">Salir</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 p-0">
                <?php include 'include/sidebar.php'; ?>
            </div>

            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-trophy"></i> Campeonatos con Zonas y Eliminatorias</h2>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalNuevoFormato">
                        <i class="fas fa-plus"></i> Nuevo Formato
                    </button>
                </div>

                <!-- Lista de formatos existentes -->
                <?php if (empty($formatos)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No hay formatos de campeonato creados. 
                    Haz clic en "Nuevo Formato" para comenzar.
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($formatos as $formato): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="formato-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($formato['campeonato_nombre']); ?></h5>
                                    <span class="badge bg-primary">Zonas + Eliminatorias</span>
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="campeonatos_zonas_detalle.php?id=<?php echo $formato['id']; ?>">
                                            <i class="fas fa-eye"></i> Ver Detalle
                                        </a></li>
                                        <li><a class="dropdown-item" href="campeonatos_zonas_equipos.php?id=<?php echo $formato['id']; ?>">
                                            <i class="fas fa-users"></i> Asignar Equipos
                                        </a></li>
                                        <li><a class="dropdown-item" href="campeonatos_zonas_fixture.php?id=<?php echo $formato['id']; ?>">
                                            <i class="fas fa-calendar"></i> Generar Fixture
                                        </a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item text-danger" href="#" onclick="eliminarFormato(<?php echo $formato['id']; ?>)">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </a></li>
                                    </ul>
                                </div>
                            </div>

                            <div class="mb-3">
                                <strong>Fase de Zonas:</strong><br>
                                <div class="mt-2">
                                    <?php for ($i = 1; $i <= $formato['cantidad_zonas']; $i++): ?>
                                        <span class="zona-badge">Zona <?php echo chr(64 + $i); ?></span>
                                    <?php endfor; ?>
                                </div>
                                <small class="text-muted">
                                    <?php echo $formato['equipos_por_zona']; ?> equipos por zona • 
                                    Clasifican <?php echo $formato['equipos_clasifican']; ?> equipos
                                </small>
                            </div>

                            <div>
                                <strong>Fases Eliminatorias:</strong><br>
                                <div class="mt-2">
                                    <?php if ($formato['tiene_octavos']): ?>
                                        <span class="fase-badge active"><i class="fas fa-check"></i> Octavos</span>
                                    <?php endif; ?>
                                    <?php if ($formato['tiene_cuartos']): ?>
                                        <span class="fase-badge active"><i class="fas fa-check"></i> Cuartos</span>
                                    <?php endif; ?>
                                    <span class="fase-badge active"><i class="fas fa-check"></i> Semifinal</span>
                                    <span class="fase-badge active"><i class="fas fa-check"></i> Final</span>
                                    <?php if ($formato['tiene_tercer_puesto']): ?>
                                        <span class="fase-badge active"><i class="fas fa-check"></i> 3er Puesto</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="mt-3 pt-3 border-top">
                                <div class="d-flex justify-content-between">
                                    <a href="campeonatos_zonas_detalle.php?id=<?php echo $formato['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> Ver Detalle
                                    </a>
                                    <a href="campeonatos_zonas_equipos.php?id=<?php echo $formato['id']; ?>" 
                                       class="btn btn-sm btn-success">
                                        <i class="fas fa-users"></i> Equipos
                                    </a>
                                    <a href="campeonatos_zonas_fixture.php?id=<?php echo $formato['id']; ?>" 
                                       class="btn btn-sm btn-warning">
                                        <i class="fas fa-calendar"></i> Fixture
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal Nuevo Formato -->
    <div class="modal fade" id="modalNuevoFormato" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Crear Nuevo Formato de Campeonato</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="formNuevoFormato" method="POST" action="ajax/guardar_formato_zonas.php">
                    <div class="modal-body">
                        <!-- Selección de Campeonato -->
                        <div class="mb-3">
                            <label class="form-label">Campeonato *</label>
                            <select name="campeonato_id" id="campeonato_id" class="form-select" required>
                                <option value="">Seleccione un campeonato</option>
                                <?php foreach ($campeonatos as $camp): ?>
                                <option value="<?php echo $camp['id']; ?>">
                                    <?php echo htmlspecialchars($camp['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <hr>
                        <h6><i class="fas fa-calculator"></i> Configuración del Torneo</h6>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Cantidad Total de Equipos *</label>
                                    <input type="number" id="total_equipos" class="form-control" 
                                           min="4" max="32" value="12" required>
                                    <small class="text-muted">Entre 4 y 32 equipos</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Cantidad de Zonas *</label>
                                    <select id="cantidad_zonas" name="cantidad_zonas" class="form-select" required>
                                        <option value="2">2 Zonas</option>
                                        <option value="3">3 Zonas</option>
                                        <option value="4" selected>4 Zonas</option>
                                        <option value="5">5 Zonas</option>
                                        <option value="6">6 Zonas</option>
                                        <option value="8">8 Zonas</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Información calculada automáticamente -->
                        <div class="calculadora-info" id="calculadoraInfo">
                            <h6 class="text-primary"><i class="fas fa-info-circle"></i> Configuración Calculada:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Equipos por zona:</strong> <span id="equipos_por_zona_calc">-</span></p>
                                    <p class="mb-1"><strong>Total clasificados:</strong> <span id="total_clasificados">-</span></p>
                                </div>
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Clasifican:</strong> <span id="descripcion_clasificacion">-</span></p>
                                    <p class="mb-1"><strong>Fase eliminatoria:</strong> <span id="fase_eliminatoria">-</span></p>
                                </div>
                            </div>
                        </div>

                        <!-- Campos ocultos calculados -->
                        <input type="hidden" name="equipos_por_zona" id="equipos_por_zona_hidden">
                        <input type="hidden" name="equipos_clasifican" id="equipos_clasifican_hidden">
                        <input type="hidden" name="tipo_clasificacion" id="tipo_clasificacion_hidden">

                        <hr>
                        <h6><i class="fas fa-trophy"></i> Fases Eliminatorias</h6>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Las fases se configurarán automáticamente según la cantidad de clasificados
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="tiene_octavos" value="1" id="checkOctavos">
                                    <label class="form-check-label" for="checkOctavos">
                                        Incluir Octavos de Final
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="tiene_cuartos" value="1" id="checkCuartos">
                                    <label class="form-check-label" for="checkCuartos">
                                        Incluir Cuartos de Final
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="alert alert-warning mb-2" style="font-size: 0.9em; padding: 8px;">
                                    <strong>Semifinal y Final</strong> siempre incluidas
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="tiene_tercer_puesto" value="1" id="checkTercero" checked>
                                    <label class="form-check-label" for="checkTercero">
                                        Incluir Partido por 3er Puesto
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-warning mt-3">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Importante:</strong> Asegúrate de tener al menos la cantidad de equipos indicada en la categoría seleccionada.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="btnCrearFormato">
                            <i class="fas fa-save"></i> Crear Formato
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Calcular configuración automáticamente
        function calcularConfiguracion() {
            const totalEquipos = parseInt($('#total_equipos').val()) || 0;
            const cantidadZonas = parseInt($('#cantidad_zonas').val()) || 0;

            if (totalEquipos < 4 || cantidadZonas < 2) {
                $('#calculadoraInfo').hide();
                return;
            }

            // Calcular equipos por zona
            const equiposPorZona = Math.floor(totalEquipos / cantidadZonas);
            const equiposSobrantes = totalEquipos % cantidadZonas;

            if (equiposPorZona < 2) {
                alert('Error: Demasiadas zonas para tan pocos equipos. Cada zona debe tener al menos 2 equipos.');
                return;
            }

            // Determinar clasificación según equipos totales
            let totalClasificados = 0;
            let descripcionClasificacion = '';
            let tipoClasificacion = '';
            let faseEliminatoria = '';
            let necesitaOctavos = false;
            let necesitaCuartos = false;

            // Lógica de clasificación basada en potencias de 2
            if (totalEquipos >= 16) {
                totalClasificados = 16;
                descripcionClasificacion = 'Los 2 primeros de cada zona';
                tipoClasificacion = '2_primeros';
                faseEliminatoria = 'Octavos → Cuartos → Semifinal → Final';
                necesitaOctavos = true;
                necesitaCuartos = true;
            } else if (totalEquipos >= 8) {
                totalClasificados = 8;
                if (cantidadZonas === 4) {
                    descripcionClasificacion = 'Los 2 primeros de cada zona';
                    tipoClasificacion = '2_primeros';
                } else if (cantidadZonas === 3) {
                    descripcionClasificacion = 'Los 2 primeros + los 2 mejores 3ros';
                    tipoClasificacion = '2_primeros_2_mejores_terceros';
                } else if (cantidadZonas === 2) {
                    descripcionClasificacion = 'Los 4 primeros de cada zona';
                    tipoClasificacion = '4_primeros';
                }
                faseEliminatoria = 'Cuartos → Semifinal → Final';
                necesitaCuartos = true;
            } else if (totalEquipos >= 4) {
                totalClasificados = 4;
                if (cantidadZonas === 2) {
                    descripcionClasificacion = 'Los 2 primeros de cada zona';
                    tipoClasificacion = '2_primeros';
                } else if (cantidadZonas === 4) {
                    descripcionClasificacion = 'El 1ro de cada zona';
                    tipoClasificacion = '1_primero';
                }
                faseEliminatoria = 'Semifinal → Final';
            }

            // Actualizar interfaz
            $('#equipos_por_zona_calc').text(equiposPorZona + (equiposSobrantes > 0 ? ' (algunas zonas tendrán ' + (equiposPorZona + 1) + ')' : ''));
            $('#total_clasificados').text(totalClasificados);
            $('#descripcion_clasificacion').text(descripcionClasificacion);
            $('#fase_eliminatoria').text(faseEliminatoria);

            // Actualizar campos ocultos
            $('#equipos_por_zona_hidden').val(equiposPorZona);
            $('#equipos_clasifican_hidden').val(totalClasificados);
            $('#tipo_clasificacion_hidden').val(tipoClasificacion);

            // Marcar/desmarcar checkboxes automáticamente
            $('#checkOctavos').prop('checked', necesitaOctavos);
            $('#checkCuartos').prop('checked', necesitaCuartos);

            // Deshabilitar opciones incorrectas
            if (!necesitaOctavos) {
                $('#checkOctavos').prop('disabled', true);
            } else {
                $('#checkOctavos').prop('disabled', false);
            }

            if (!necesitaCuartos) {
                $('#checkCuartos').prop('disabled', true);
            } else {
                $('#checkCuartos').prop('disabled', false);
            }

            $('#calculadoraInfo').show();
        }

        // Ejecutar al cambiar valores
        $('#total_equipos, #cantidad_zonas').on('input change', calcularConfiguracion);

        // Calcular al cargar
        $(document).ready(function() {
            calcularConfiguracion();
        });

        $('#formNuevoFormato').on('submit', function(e) {
            e.preventDefault();
            
            const totalEquipos = parseInt($('#total_equipos').val());
            const equiposClasificados = parseInt($('#equipos_clasifican_hidden').val());

            if (!equiposClasificados || equiposClasificados === 0) {
                alert('Error: No se pudo calcular la configuración. Verifica los datos ingresados.');
                return;
            }

            const btn = $('#btnCrearFormato');
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...');
            
            $.ajax({
                url: 'ajax/guardar_formato_zonas.php',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert('Formato creado exitosamente');
                        window.location.href = 'campeonatos_zonas_equipos.php?id=' + response.formato_id;
                    } else {
                        alert('Error: ' + response.message);
                        btn.prop('disabled', false).html('<i class="fas fa-save"></i> Crear Formato');
                    }
                },
                error: function() {
                    alert('Error al guardar el formato');
                    btn.prop('disabled', false).html('<i class="fas fa-save"></i> Crear Formato');
                }
            });
        });

        function eliminarFormato(id) {
            if (confirm('¿Estás seguro de eliminar este formato? Se eliminarán todas las zonas y partidos asociados.')) {
                $.post('ajax/eliminar_formato_zonas.php', {id: id}, function(response) {
                    if (response.success) {
                        alert('Formato eliminado');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                }, 'json');
            }
        }
    </script>
</body>
</html>