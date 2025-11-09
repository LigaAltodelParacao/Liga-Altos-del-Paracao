<?php
// ================================================================
// SCRIPT DE CORRECCIÓN - PROBLEMA DE NAVEGACIÓN EN CARPETA PUBLIC
// ================================================================

echo "🔧 APLICANDO CORRECCIONES DE NAVEGACIÓN\n";
echo "========================================\n\n";

// Lista de archivos que necesitan corrección
$archivos_publicos = [
    'public_resultados.php',
    'public_tablas.php', 
    'public_fixture.php',
    'public_goleadores.php',
    'public_sanciones.php',
    'public_historial_equipos.php',
    'public_fairplay.php',
    // Añadir otros archivos de public/ según necesites
];

// Patrones de corrección
$correcciones = [
    // Link Panel Admin
    'href="admin/dashboard.php"' => 'href="../admin/dashboard.php"',
    // Link Logout  
    'href="logout.php"' => 'href="../logout.php"',
    // Link Login
    'href="login.php"' => 'href="../login.php"',
];

echo "📋 Archivos que necesitan corrección:\n";
foreach ($archivos_publicos as $archivo) {
    echo "   - $archivo\n";
}

echo "\n✅ Patrones de corrección:\n";
foreach ($correcciones as $patron => $reemplazo) {
    echo "   $patron → $reemplazo\n";
}

echo "\n🚀 Para aplicar la corrección manualmente:\n";
echo "1. Descarga cada archivo desde GitHub\n";
echo "2. Busca y reemplaza los patrones listados arriba\n";
echo "3. Sube el archivo corregido a tu servidor\n\n";

echo "📁 Ejemplo de corrección en public/resultados.php:\n";
echo "BÚSCA ESTA LÍNEA:\n";
echo '<a class="nav-link" href="admin/dashboard.php">' . "\n";
echo "CÁMBIALA POR:\n";
echo '<a class="nav-link" href="../admin/dashboard.php">' . "\n\n";

echo "📁 Ejemplo de corrección en logout:\n";
echo "BÚSCA ESTA LÍNEA:\n";
echo '<a class="nav-link" href="logout.php">' . "\n";
echo "CÁMBIALA POR:\n";
echo '<a class="nav-link" href="../logout.php">' . "\n\n";

echo "💡 NOTA: Los cambios se aplican a todos los archivos dentro de public/\n";
echo "    Esto asegura navegación correcta desde cualquier página pública.\n";
?>