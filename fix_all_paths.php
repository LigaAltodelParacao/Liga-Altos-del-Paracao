<?php
/**
 * Script para corregir todas las rutas relativas a absolutas
 * Ejecutar una sola vez desde la línea de comandos: php fix_all_paths.php
 */

$base_dir = __DIR__;
$files_to_fix = [];

// Buscar todos los archivos PHP
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($base_dir),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        // Excluir este script y archivos en .git
        if ($path !== __FILE__ && strpos($path, '/.git/') === false) {
            $files_to_fix[] = $path;
        }
    }
}

$total_fixed = 0;
$fixes_log = [];

foreach ($files_to_fix as $file_path) {
    $content = file_get_contents($file_path);
    $original_content = $content;
    $file_fixes = [];
    
    // Determinar el nivel de directorio
    $relative_path = str_replace($base_dir . '/', '', $file_path);
    $dir_level = substr_count($relative_path, '/');
    
    // Patrones a corregir
    $patterns = [
        // require_once y require
        "/require_once\s+['\"]\.\.\/config\.php['\"]/i" => "require_once __DIR__ . '/../config.php'",
        "/require_once\s+['\"]config\.php['\"]/i" => "require_once __DIR__ . '/config.php'",
        "/require\s+['\"]\.\.\/config\.php['\"]/i" => "require __DIR__ . '/../config.php'",
        "/require\s+['\"]config\.php['\"]/i" => "require __DIR__ . '/config.php'",
        
        // include y include_once
        "/include_once\s+['\"]include\/sidebar\.php['\"]/i" => "include_once __DIR__ . '/include/sidebar.php'",
        "/include\s+['\"]include\/sidebar\.php['\"]/i" => "include __DIR__ . '/include/sidebar.php'",
        
        // Rutas de assets - mantener SITE_URL
        "/href=['\"]\.\.\/assets\//i" => "href=\"<?php echo SITE_URL; ?>assets/",
        "/src=['\"]\.\.\/assets\//i" => "src=\"<?php echo SITE_URL; ?>assets/",
        
        // Rutas de navegación
        "/href=['\"]\.\.\/index\.php['\"]/i" => "href=\"<?php echo SITE_URL; ?>\"",
        "/href=['\"]\.\.\/login\.php['\"]/i" => "href=\"<?php echo SITE_URL; ?>login.php\"",
        "/href=['\"]\.\.\/logout\.php['\"]/i" => "href=\"<?php echo SITE_URL; ?>logout.php\"",
    ];
    
    // Aplicar correcciones
    foreach ($patterns as $pattern => $replacement) {
        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $replacement, $content);
            $file_fixes[] = "Aplicado: $pattern => $replacement";
        }
    }
    
    // Correcciones específicas para archivos en admin/ajax/
    if (strpos($file_path, '/admin/ajax/') !== false) {
        $content = preg_replace(
            "/require_once\s+['\"]\.\.\/config\.php['\"]/i",
            "require_once __DIR__ . '/../../config.php'",
            $content
        );
        $file_fixes[] = "Corregido ruta config.php para admin/ajax/";
    }
    
    // Si hubo cambios, guardar el archivo
    if ($content !== $original_content) {
        file_put_contents($file_path, $content);
        $total_fixed++;
        $fixes_log[$relative_path] = $file_fixes;
        echo "✓ Corregido: $relative_path\n";
        foreach ($file_fixes as $fix) {
            echo "  - $fix\n";
        }
    }
}

echo "\n========================================\n";
echo "RESUMEN:\n";
echo "Archivos analizados: " . count($files_to_fix) . "\n";
echo "Archivos corregidos: $total_fixed\n";
echo "========================================\n";

// Guardar log
file_put_contents(
    $base_dir . '/path_fixes_log.txt',
    "Correcciones realizadas el " . date('Y-m-d H:i:s') . "\n\n" .
    print_r($fixes_log, true)
);

echo "\nLog guardado en: path_fixes_log.txt\n";
?>