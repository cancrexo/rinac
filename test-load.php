<?php
/**
 * Prueba rápida para verificar que RINAC se puede cargar
 */

// Definir constantes simuladas
define('ABSPATH', '/var/vhost/woo.lan/httpdocs/');
define('RINAC_PLUGIN_PATH', '/var/vhost/woo.lan/httpdocs/wp-content/plugins/rinac/');
define('RINAC_PLUGIN_URL', 'http://woo.lan/wp-content/plugins/rinac/');
define('RINAC_VERSION', '1.0.0');
define('RINAC_PLUGIN_BASENAME', 'rinac/rinac.php');

// Simular funciones de WordPress necesarias
if (!function_exists('wp_die')) {
    function wp_die($message) {
        die($message);
    }
}

if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

if (!function_exists('deactivate_plugins')) {
    function deactivate_plugins($plugins) {
        // Función simulada
    }
}

echo "Probando carga de la clase RINAC_Install...\n";

// Intentar cargar la clase Install (vía Composer)
try {
    $autoload = RINAC_PLUGIN_PATH . 'vendor/autoload.php';
    if (!file_exists($autoload)) {
        throw new Exception('No existe vendor/autoload.php. Ejecuta composer install --no-dev.');
    }

    require_once $autoload;
    echo "✅ Autoload de Composer cargado correctamente\n";
    
    // Verificar que los métodos existen
    if (method_exists('\Rinac\Install\Install', 'activate')) {
        echo "✅ Método activate() existe\n";
    } else {
        echo "❌ Método activate() NO existe\n";
    }
    
    if (method_exists('\Rinac\Install\Install', 'deactivate')) {
        echo "✅ Método deactivate() existe\n";
    } else {
        echo "❌ Método deactivate() NO existe\n";
    }
    
    if (method_exists('\Rinac\Install\Install', 'create_tables')) {
        echo "✅ Método create_tables() existe\n";
    } else {
        echo "❌ Método create_tables() NO existe\n";
    }
    
    if (method_exists('\Rinac\Install\Install', 'create_default_options')) {
        echo "✅ Método create_default_options() existe\n";
    } else {
        echo "❌ Método create_default_options() NO existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error al cargar RINAC_Install: " . $e->getMessage() . "\n";
}

echo "\nProbando carga del archivo principal...\n";

try {
    require_once RINAC_PLUGIN_PATH . 'rinac.php';
    echo "✅ Archivo principal cargado correctamente\n";
    
    if (class_exists('RINAC')) {
        echo "✅ Clase RINAC existe\n";
    } else {
        echo "❌ Clase RINAC NO existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error al cargar archivo principal: " . $e->getMessage() . "\n";
}

echo "\n🎯 Prueba completada.\n";
?>
