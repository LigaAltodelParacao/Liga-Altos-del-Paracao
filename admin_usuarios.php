<?php
require_once '../config.php';

if (!isLoggedIn() || !hasPermission('superadmin')) {
    redirect('../login.php');
}

$db = Database::getInstance()->getConnection();
$message = '';
$error = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create':
            $username = trim($_POST['username']);
            $password = $_POST['password'];
            $email = trim($_POST['email']);
            $nombre = trim($_POST['nombre']);
            $tipo = $_POST['tipo'];
            
            if (empty($username) || empty($password) || empty($nombre) || empty($tipo)) {
                $error = 'Todos los campos obligatorios deben completarse';
            } else {
                try {
                    // Verificar si el usuario ya existe
                    $stmt = $db->prepare("SELECT id FROM usuarios WHERE username = ?");
                    $stmt->execute([$username]);
                    if ($stmt->fetch()) {
                        $error = 'Ya existe un usuario con ese nombre';
                    } else {
                        // Generar código para planilleros
                        $codigo_planillero = null;
                        if ($tipo == 'planillero') {
                            do {
                                $codigo_planillero = generateCode(6);
                                $stmt = $db->prepare("SELECT id FROM usuarios WHERE codigo_planillero = ?");
                                $stmt->execute([$codigo_planillero]);
                            } while ($stmt->fetch()); // Asegurar que el código sea único
                        }
                        
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        $stmt = $db->prepare("
                            INSERT INTO usuarios (username, password, email, nombre, tipo, codigo_planillero) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([$username, $hashed_password, $email, $nombre, $tipo, $codigo_planillero]);
                        
                        $message = 'Usuario creado exitosamente' . ($codigo_planillero ? " - Código: $codigo_planillero" : '');
                    }
                } catch (Exception $e) {
                    $error = 'Error al crear usuario: ' . $e->getMessage();
                }
            }
            break;
            
        case 'update':
            $id = $_POST['id'];
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $nombre = trim($_POST['nombre']);
            $tipo = $_POST['tipo'];
            $activo = isset($_POST['activo']) ? 1 : 0;
            $nueva_password = trim($_POST['nueva_password']);
            
            try {
                if ($nueva_password) {
                    $hashed_password = password_hash($nueva_password, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("
                        UPDATE usuarios 
                        SET username = ?, email = ?, nombre = ?, tipo = ?, activo = ?, password = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $nombre, $tipo, $activo, $hashed_password, $id]);
                } else {
                    $stmt = $db->prepare("
                        UPDATE usuarios 
                        SET username = ?, email = ?, nombre = ?, tipo = ?, activo = ?
                        WHERE id = ?
                    ");
                    $stmt->execute([$username, $email, $nombre, $tipo, $activo, $id]);
                }
                
                $message = 'Usuario actualizado exitosamente';
            } catch (Exception $e) {
                $error = 'Error al actualizar usuario: ' . $e->getMessage();
            }
            break;
            
        case 'generate_code':
            $id = $_POST['id'];
            try {
                $nuevo_codigo = generateCode(6);
                $stmt = $db->prepare("UPDATE usuarios SET codigo_planillero = ? WHERE id = ? AND tipo = 'planillero'");
                $stmt->execute([$nuevo_codigo, $id]);
                
                if ($stmt->rowCount() > 0) {
                    $message = "Nuevo código generado: $nuevo_codigo";
                } else {
                    $error = 'Error al generar código';
                }
            } catch (Exception $e) {
                $error = 'Error al generar código: ' . $e->getMessage();
            }
            break;
            
        case 'delete':
            $id = $_POST['id'];
            $current_user_id = $_SESSION['user_id'];
            
            if ($id == $current_user_id) {
                $error = 'No puedes eliminar tu propio usuario';
            } else {
                try {
                    $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
                    $stmt->execute([$id]);
                    $message = 'Usuario eliminado exitosamente';
                } catch (Exception $e) {
                    $error = 'Error al eliminar usuario: ' . $e->getMessage();
                }
            }
            break;
    }
}

// Obtener usuarios
$stmt = $db->query("
    SELECT u.*,
           COUNT(DISTINCT pl.id) as planillas_asignadas,
           MAX(pl.fecha_descarga) as ultima_actividad
    FROM usuarios u
    LEFT JOIN planillas pl ON u.id = pl.planillero_id
    GROUP BY u.id
    ORDER BY 
        CASE u.tipo 
            WHEN 'superadmin' THEN 1 
            WHEN 'admin' THEN 2 
            WHEN 'planillero' THEN 3 
        END,
        u.nombre
");
$usuarios = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - Sistema de Campeonatos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
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
                <?php include 'includes/sidebar.php'; ?>
            </div>

            <div class="col-md-9 col-lg-10 p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-user-cog"></i> Gestión de Usuarios</h2>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalUsuario">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </button>
                </div>

                <!-- Mensajes -->
                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Estadísticas de usuarios -->
                <div class="row mb-4">
                    <?php
                    $stats_usuarios = [
                        'superadmin' => count(array_filter($usuarios, fn($u) => $u['tipo'] == 'superadmin')),
                        'admin' => count(array_filter($usuarios, fn($u) => $u['tipo'] == 'admin')),
                        'planillero' => count(array_filter($usuarios, fn($u) => $u['tipo'] == 'planillero')),
                        'activos' => count(array_filter($usuarios, fn($u) => $u['activo']))
                    ];
                    ?>
                    <div class="col-md-3">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user-shield fa-2x me-3"></i>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats_usuarios['superadmin']; ?></h3>
                                        <small>Superadmins</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user-cog fa-2x me-3"></i>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats_usuarios['admin']; ?></h3>
                                        <small>Administradores</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-clipboard-user fa-2x me-3"></i>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats_usuarios['planillero']; ?></h3>
                                        <small>Planilleros</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <div class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-user-check fa-2x me-3"></i>
                                    <div>
                                        <h3 class="mb-0"><?php echo $stats_usuarios['activos']; ?></h3>
                                        <small>Usuarios Activos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de usuarios -->
                <div class="card">
                    <div class="card-body">
                        <?php if (empty($usuarios)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No hay usuarios registrados</h5>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Usuario</th>
                                            <th>Nombre</th>
                                            <th>Email</th>
                                            <th>Tipo</th>
                                            <th>Código Planillero</th>
                                            <th>Actividad</th>
                                            <th>Estado</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($usuarios as $usuario): ?>
                                        <tr class="<?php echo !$usuario['activo'] ? 'table-secondary' : ''; ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <?php
                                                    $icon_color = [
                                                        'superadmin' => 'text-danger',
                                                        'admin' => 'text-primary', 
                                                        'planillero' => 'text-warning'
                                                    ];
                                                    $icon_type = [
                                                        'superadmin' => 'fa-user-shield',
                                                        'admin' => 'fa-user-cog',
                                                        'planillero' => 'fa-clipboard-user'
                                                    ];
                                                    ?>
                                                    <i class="fas <?php echo $icon_type[$usuario['tipo']]; ?> <?php echo $icon_color[$usuario['tipo']]; ?> fa-lg me-3"></i>
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($usuario['username']); ?></strong>
                                                        <?php if ($usuario['id'] == $_SESSION['user_id']): ?>
                                                            <br><small class="badge bg-info">Tú</small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($usuario['nombre']); ?></td>
                                            <td>
                                                <?php if ($usuario['email']): ?>
                                                    <a href="mailto:<?php echo htmlspecialchars($usuario['email']); ?>" class="text-decoration-none">
                                                        <?php echo htmlspecialchars($usuario['email']); ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin email</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $usuario['tipo'] == 'superadmin' ? 'danger' : ($usuario['tipo'] == 'admin' ? 'primary' : 'warning'); ?>">
                                                    <?php echo ucfirst($usuario['tipo']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($usuario['tipo'] == 'planillero'): ?>
                                                    <?php if ($usuario['codigo_planillero']): ?>
                                                        <span class="badge bg-secondary fs-6 font-monospace">
                                                            <?php echo $usuario['codigo_planillero']; ?>
                                                        </span>
                                                        <br>
                                                        <button class="btn btn-xs btn-outline-primary mt-1" 
                                                                onclick="generarCodigo(<?php echo $usuario['id']; ?>)" 
                                                                title="Generar nuevo código">
                                                            <i class="fas fa-sync-alt"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <button class="btn btn-sm btn-outline-warning" 
                                                                onclick="generarCodigo(<?php echo $usuario['id']; ?>)">
                                                            <i class="fas fa-key"></i> Generar
                                                        </button>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($usuario['tipo'] == 'planillero'): ?>
                                                    <small class="text-muted">
                                                        <?php echo $usuario['planillas_asignadas']; ?> planillas<br>
                                                        <?php if ($usuario['ultima_actividad']): ?>
                                                            <?php echo date('d/m/Y', strtotime($usuario['ultima_actividad'])); ?>
                                                        <?php else: ?>
                                                            Sin actividad
                                                        <?php endif; ?>
                                                    </small>
                                                <?php else: ?>
                                                    <small class="text-muted">
                                                        Creado: <?php echo date('d/m/Y', strtotime($usuario['created_at'])); ?>
                                                    </small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($usuario['activo']): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="editarUsuario(<?php echo $usuario['id']; ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if ($usuario['id'] != $_SESSION['user_id']): ?>
                                                        <button class="btn btn-sm btn-outline-danger" 
                                                                onclick="eliminarUsuario(<?php echo $usuario['id']; ?>, '<?php echo htmlspecialchars($usuario['username']); ?>')">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Usuario -->
    <div class="modal fade" id="modalUsuario" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalUsuarioTitle">
                        <i class="fas fa-user-plus"></i> Nuevo Usuario
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="formUsuario">
                    <input type="hidden" name="action" id="usuarioAction" value="create">
                    <input type="hidden" name="id" id="usuarioId">
                    
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="username" class="form-label">Nombre de Usuario *</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre Completo *</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email">
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Usuario *</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Seleccionar tipo...</option>
                                <option value="admin">Administrador</option>
                                <option value="planillero">Planillero</option>
                            </select>
                            <div class="form-text">
                                <strong>Administrador:</strong> Puede gestionar todo excepto crear usuarios<br>
                                <strong>Planillero:</strong> Solo puede registrar eventos de partidos asignados
                            </div>
                        </div>
                        
                        <div class="mb-3" id="passwordContainer">
                            <label for="password" class="form-label">Contraseña *</label>
                            <input type="password" class="form-control" id="password" name="password">
                            <div class="form-text">Mínimo 6 caracteres</div>
                        </div>
                        
                        <div class="mb-3" id="nuevaPasswordContainer" style="display: none;">
                            <label for="nueva_password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" id="nueva_password" name="nueva_password">
                            <div class="form-text">Dejar vacío para mantener la contraseña actual</div>
                        </div>
                        
                        <div class="mb-3" id="activoContainer" style="display: none;">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                                <label class="form-check-label" for="activo">
                                    Usuario Activo
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Forms ocultos -->
    <form method="POST" id="formGenerarCodigo" style="display: none;">
        <input type="hidden" name="action" value="generate_code">
        <input type="hidden" name="id" id="codigoUserId">
    </form>

    <form method="POST" id="formEliminar" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" id="eliminarId">
    </form>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarUsuario(id) {
            // Buscar datos del usuario
            <?php foreach ($usuarios as $usuario): ?>
            if (id == <?php echo $usuario['id']; ?>) {
                document.getElementById('modalUsuarioTitle').innerHTML = '<i class="fas fa-edit"></i> Editar Usuario';
                document.getElementById('usuarioAction').value = 'update';
                document.getElementById('usuarioId').value = <?php echo $usuario['id']; ?>;
                document.getElementById('username').value = '<?php echo htmlspecialchars($usuario['username'], ENT_QUOTES); ?>';
                document.getElementById('nombre').value = '<?php echo htmlspecialchars($usuario['nombre'], ENT_QUOTES); ?>';
                document.getElementById('email').value = '<?php echo htmlspecialchars($usuario['email'], ENT_QUOTES); ?>';
                document.getElementById('tipo').value = '<?php echo $usuario['tipo']; ?>';
                document.getElementById('activo').checked = <?php echo $usuario['activo'] ? 'true' : 'false'; ?>;
                
                // Mostrar/ocultar campos apropiados
                document.getElementById('passwordContainer').style.display = 'none';
                document.getElementById('nuevaPasswordContainer').style.display = 'block';
                document.getElementById('activoContainer').style.display = 'block';
                document.getElementById('password').required = false;
                
                var modal = new bootstrap.Modal(document.getElementById('modalUsuario'));
                modal.show();
                return;
            }
            <?php endforeach; ?>
        }

        function eliminarUsuario(id, username) {
            if (confirm('¿Eliminar el usuario "' + username + '"?\n\nEsta acción no se puede deshacer.')) {
                document.getElementById('eliminarId').value = id;
                document.getElementById('formEliminar').submit();
            }
        }

        function generarCodigo(id) {
            if (confirm('¿Generar un nuevo código de acceso?\n\nEl código anterior dejará de funcionar.')) {
                document.getElementById('codigoUserId').value = id;
                document.getElementById('formGenerarCodigo').submit();
            }
        }

        // Limpiar formulario al cerrar modal
        document.getElementById('modalUsuario').addEventListener('hidden.bs.modal', function () {
            document.getElementById('formUsuario').reset();
            document.getElementById('modalUsuarioTitle').innerHTML = '<i class="fas fa-user-plus"></i> Nuevo Usuario';
            document.getElementById('usuarioAction').value = 'create';
            document.getElementById('usuarioId').value = '';
            document.getElementById('passwordContainer').style.display = 'block';
            document.getElementById('nuevaPasswordContainer').style.display = 'none';
            document.getElementById('activoContainer').style.display = 'none';
            document.getElementById('password').required = true;
        });

        // Validación de formulario
        document.getElementById('formUsuario').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const nuevaPassword = document.getElementById('nueva_password').value;
            const isCreate = document.getElementById('usuarioAction').value === 'create';
            
            if (isCreate && password.length < 6) {
                e.preventDefault();
                alert('La contraseña debe tener al menos 6 caracteres');
                return false;
            }
            
            if (!isCreate && nuevaPassword && nuevaPassword.length < 6) {
                e.preventDefault();
                alert('La nueva contraseña debe tener al menos 6 caracteres');
                return false;
            }
        });

        // Copiar código al clipboard
        document.querySelectorAll('.font-monospace').forEach(function(element) {
            element.style.cursor = 'pointer';
            element.title = 'Clic para copiar';
            element.addEventListener('click', function() {
                navigator.clipboard.writeText(this.textContent.trim()).then(function() {
                    // Mostrar feedback temporal
                    const original = element.textContent;
                    element.textContent = '¡Copiado!';
                    setTimeout(() => {
                        element.textContent = original;
                    }, 1000);
                });
            });
        });
    </script>
</body>
</html>