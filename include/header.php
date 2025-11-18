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

// Detectar página activa
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    .navbar {
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        padding: 0.75rem 0;
    }
    
    .navbar-brand {
        font-weight: 700;
        font-size: 1.3rem;
        transition: all 0.3s ease;
    }
    
    .navbar-brand:hover {
        transform: scale(1.05);
    }
    
    .nav-link {
        font-weight: 500;
        padding: 0.6rem 1rem !important;
        transition: all 0.3s ease;
        border-radius: 8px;
        margin: 0 2px;
    }
    
    .nav-link:hover {
        background: rgba(255,255,255,0.15);
        transform: translateY(-2px);
    }
    
    .nav-link.active {
        background: rgba(255,255,255,0.25);
        font-weight: 700;
    }
    
    .nav-link.torneo-nocturno {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        color: #000 !important;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(255, 193, 7, 0.4);
    }
    
    .nav-link.torneo-nocturno:hover {
        background: linear-gradient(135deg, #ffca28 0%, #ffa726 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255, 193, 7, 0.6);
    }
    
    .nav-link.torneo-nocturno.active {
        background: linear-gradient(135deg, #ffb300 0%, #f57c00 100%);
    }
    
    @media (max-width: 991px) {
        .navbar-nav {
            padding: 1rem 0;
        }
        
        .nav-link {
            margin: 4px 0;
        }
    }
</style>

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
                    <a class="nav-link <?php echo $current_page == 'resultados.php' ? 'active' : ''; ?>" 
                       href="<?php echo $public_path; ?>resultados.php">
                        <i class="fas fa-list-ul me-1"></i>Resultados
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'tablas.php' ? 'active' : ''; ?>" 
                       href="<?php echo $public_path; ?>tablas.php">
                        <i class="fas fa-trophy me-1"></i>Posiciones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'goleadores.php' ? 'active' : ''; ?>" 
                       href="<?php echo $public_path; ?>goleadores.php">
                        <i class="fas fa-futbol me-1"></i>Goleadores
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'fixture.php' ? 'active' : ''; ?>" 
                       href="<?php echo $public_path; ?>fixture.php">
                        <i class="fas fa-calendar-alt me-1"></i>Fixture
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'sanciones.php' ? 'active' : ''; ?>" 
                       href="<?php echo $public_path; ?>sanciones.php">
                        <i class="fas fa-ban me-1"></i>Sanciones
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'historial_equipos.php' ? 'active' : ''; ?>" 
                       href="<?php echo $public_path; ?>historial_equipos.php">
                        <i class="fas fa-users me-1"></i>Equipos
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'fairplay.php' ? 'active' : ''; ?>" 
                       href="<?php echo $public_path; ?>fairplay.php">
                        <i class="fas fa-shield-alt me-1"></i>Fairplay
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'estadisticas_historicas.php' ? 'active' : ''; ?>" 
                       href="<?php echo $public_path; ?>estadisticas_historicas.php">
                        <i class="fas fa-chart-bar me-1"></i>Estadísticas
                    </a>
                </li>
                <!-- Separador visual -->
                <li class="nav-item d-none d-lg-block" style="border-left: 2px solid rgba(255,255,255,0.2); height: 40px; margin: 0 8px;"></li>
                
                <!-- Torneo Nocturno destacado -->
                <li class="nav-item">
                    <a class="nav-link torneo-nocturno <?php echo $current_page == 'torneo_nocturno.php' ? 'active' : ''; ?>" 
                       href="<?php echo $public_path; ?>torneo_nocturno.php">
                        <i class="fas fa-moon me-1"></i>Torneo Nocturno
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav">
                <?php if ($user_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $admin_path; ?>dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Panel Admin
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_path; ?>logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Salir
                        </a>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo $base_path; ?>login.php">
                            <i class="fas fa-sign-in-alt me-1"></i>Ingresar
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>