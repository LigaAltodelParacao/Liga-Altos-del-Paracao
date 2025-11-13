<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user = getCurrentUser();
?>

<div class="admin-sidebar">
    <nav class="nav flex-column">
        <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
            <i class="fas fa-tachometer-alt"></i> Dashboard
        </a>
        <a class="nav-link <?php echo $current_page == 'campeonatos.php' ? 'active' : ''; ?>" href="campeonatos.php">
            <i class="fas fa-trophy"></i> Campeonatos
        </a>
        <a class="nav-link <?php echo $current_page == 'categorias.php' ? 'active' : ''; ?>" href="categorias.php">
            <i class="fas fa-list"></i> Categorías
        </a>
        <a class="nav-link <?php echo $current_page == 'equipos.php' ? 'active' : ''; ?>" href="equipos.php">
            <i class="fas fa-users"></i> Equipos
        </a>
        <a class="nav-link <?php echo $current_page == 'jugadores.php' ? 'active' : ''; ?>" href="jugadores.php">
            <i class="fas fa-user-friends"></i> Jugadores
        </a>
        <a class="nav-link <?php echo $current_page == 'canchas.php' ? 'active' : ''; ?>" href="canchas.php">
            <i class="fas fa-map"></i> Canchas
        </a>
		<a class="nav-link <?php echo $current_page == 'canchas.php' ? 'active' : ''; ?>" href="horarios.php">
            <i class="fas fa-map"></i> Horarios
        </a>
        <a class="nav-link <?php echo $current_page == 'partidos.php' ? 'active' : ''; ?>" href="partidos.php">
            <i class="fas fa-calendar"></i> Partidos
        </a>
        <a class="nav-link <?php echo $current_page == 'sanciones.php' ? 'active' : ''; ?>" href="sanciones.php">
            <i class="fas fa-ban"></i> Sanciones
        </a>
        <a class="nav-link <?php echo $current_page == 'gestion_codigos_cancha.php' ? 'active' : ''; ?>" href="gestion_codigos_cancha.php">
            <i class="fas fa-file-alt"></i> Planillero
        </a>
        <?php if ($user['tipo'] == 'superadmin'): ?>
        <a class="nav-link <?php echo $current_page == 'usuarios.php' ? 'active' : ''; ?>" href="usuarios.php">
            <i class="fas fa-user-cog"></i> Usuarios
        </a>
        <?php endif; ?>
    </nav>
</div>