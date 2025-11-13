<?php
require_once __DIR__ . '/config.php';

// Destruir la sesión
session_destroy();

// Limpiar las cookies de sesión
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirigir al inicio
redirect('index.php');
?>