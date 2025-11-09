<?php
// header.php - Header reutilizable con navegación inteligente
// Detecta automáticamente el contexto y ajusta los links correctamente

// Detectar el contexto de la página actual
$current_path = $_SERVER['REQUEST_URI'] ?? $_SERVER['SCRIPT_NAME'] ?? '';
$is_in_public = (strpos($current_path, '/public/') !== false);
$is_in_admin = (strpos($current_path, '/admin/') !== false);

// Generar paths base según el contexto
if ($is_in_public) {
    // Estamos en una página dentro de public/
    $base_path = '../';
    $public_path = './';
    $admin_path = '../admin/';
} elseif ($is_in_admin) {
    // Estamos en una página dentro de admin/
    $base_path = '../';
    $public_path = '../public/';
    $admin_path = './';
} else {
    // Estamos en la raíz
    $base_path = './';
    $public_path = 'public/';
    $admin_path = 'admin/';
}

// Obtener información del usuario logueado con verificaciones adicionales
$user_logged_in = false;
try {
    if (function_exists('isLoggedIn') && isLoggedIn()) {
        $user_logged_in = true;
        $current_user = function_exists('getCurrentUser') ? getCurrentUser() : null;
    }
} catch (Exception $e) {
    $user_logged_in = false;
    $current_user = null;
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="<?php echo $base_path; ?>index.php">
            <i class="fas fa-futbol"></i> Altos del Paracao
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $public_path; ?>resultados.php">Resultados</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $public_path; ?>tablas.php">Posiciones</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $public_path; ?>goleadores.php">Goleadores</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $public_path; ?>fixture.php">Fixture</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $public_path; ?>sanciones.php">Sanciones</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $public_path; ?>historial_equipos.php">Equipos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo $public_path; ?>fairplay.php">Fairplay</a>
                </li>
                <?php
                // Mostrar Torneo Nocturno solo si existe y está activo
                try {
                    if (class_exists('Database')) {
                        $db = Database::getInstance()->getConnection();
                        $stmtTN = $db->prepare("SELECT id FROM campeonatos WHERE activo = 1 AND nombre LIKE ? LIMIT 1");
                        $stmtTN->execute(['%Torneo Nocturno%']);
                        $torneoNocturno = $stmtTN->fetchColumn();
                        if ($torneoNocturno): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $public_path; ?>torneo_nocturno.php">Torneo Nocturno</a>
                            </li>
                        <?php endif;
                    }
                } catch (Exception $e) { 
                    // Silent fail for torneo nocturno
                } catch (PDOException $e) {
                    // Silent fail for torneo nocturno
                }
                ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($user_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $admin_path; ?>dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Panel Admin
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_path; ?>logout.php">
                            <i class="fas fa-sign-out-alt"></i> Salir
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_path; ?>login.php">
                            <i class="fas fa-sign-in-alt"></i> Ingresar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>