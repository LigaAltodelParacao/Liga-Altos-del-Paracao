<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');
require_once __DIR__ . '/../config.php';

if (!isLoggedIn() || !hasPermission('planillero')) {
    redirect('../login.php');
}


$db = Database::getInstance()->getConnection();
$user = getCurrentUser();
$message = '';
$error = '';

// Procesar código de acceso
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['codigo_acceso'])) {
    $codigo = strtoupper(trim($_POST['codigo_acceso']));
    
    try {
        // Verificar código válido
        $stmt = $db->prepare("
            SELECT cc.*, c.nombre as cancha_nombre
            FROM codigos_cancha cc
            JOIN canchas c ON cc.cancha_id = c.id
            WHERE cc.codigo = ? AND cc.activo = 1 AND (cc.expires_at IS NULL OR cc.expires_at >= NOW())
        ");
        $stmt->execute([$codigo]);
        $codigo_data = $stmt->fetch();
        
        if (!$codigo_data) {
            throw new Exception('Código inválido, expirado o desactivado');
        }
        
        // Marcar como usado
        $stmt = $db->prepare("UPDATE codigos_cancha SET usado = 1 WHERE id = ?");
        $stmt->execute([$codigo_data['id']]);
        
        // Guardar en sesión
        $_SESSION['codigo_cancha_activo'] = $codigo_data;
        
        // Redirigir a planillero.php
        redirect('planillero.php');
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Planillero - Sistema de Campeonatos</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .codigo-input {
            font-size: 2.5rem;
            letter-spacing: 0.5rem;
            text-align: center;
            text-transform: uppercase;
            font-weight: bold;
            border: 3px solid #007bff;
            border-radius: 15px;
        }
        .btn-acceso {
            font-size: 1.2rem;
            padding: 15px;
            border-radius: 15px;
            font-weight: bold;
        }
        .header-section {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }

        /* --- Ajustes responsive móvil --- */
        @media (max-width: 576px) {
            .login-container {
                border-radius: 14px;
            }
            .header-section {
                padding: 24px;
            }
            .codigo-input {
                font-size: 2rem;
                letter-spacing: 0.35rem;
                border-width: 2px;
            }
            .btn-acceso {
                font-size: 1.05rem;
                padding: 14px;
            }
            .p-5 {
                padding: 1.25rem !important;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <div class="login-container">
                    <div class="header-section">
                        <i class="fas fa-futbol fa-4x mb-3"></i>
                        <h2>Sistema de Planilleros</h2>
                        <p class="mb-0">Ingresa el código de tu cancha</p>
                    </div>
                    
                    <div class="p-5">
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label text-center d-block">
                                    <strong>Código de Acceso</strong>
                                </label>
                                <input type="text" 
                                       class="form-control codigo-input" 
                                       name="codigo_acceso" 
                                       placeholder="ABC123" 
                                       maxlength="6" 
                                       required
                                       autocomplete="off"
                                       autofocus>
                                <small class="form-text text-muted text-center d-block mt-3">
                                    Código proporcionado por el administrador
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-acceso w-100">
                                <i class="fas fa-sign-in-alt"></i> Acceder a Mi Cancha
                            </button>
                        </form>
                        
                        <div class="text-center mt-4">
                            <small class="text-muted">
                                ¿Problemas con el código? Contacta al administrador
                            </small>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="<?php echo SITE_URL; ?>logout.php" class="text-white">
                        <i class="fas fa-sign-out-alt"></i> Salir del sistema
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto mayúsculas y solo alfanuméricos
        document.querySelector('input[name="codigo_acceso"]').addEventListener('input', function(e) {
            e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
    </script>
</body>
</html>