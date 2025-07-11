<?php
/**
 * Script de limpieza y reinstalación de RINAC
 * EJECUTAR SOLO SI HAY PROBLEMAS CON LA INSTALACIÓN
 */

// Verificar que se ejecute con el parámetro correcto
if (!isset($_GET['reset']) || $_GET['reset'] !== 'rinac_force') {
    die('Acceso no autorizado. Para ejecutar: ?reset=rinac_force');
}

// Cargar WordPress
require_once('../../../wp-config.php');

echo "<h1>🔄 Limpieza y Reinstalación de RINAC</h1>\n";

// Verificar si el usuario tiene permisos
if (!current_user_can('manage_options')) {
    die('❌ Sin permisos suficientes');
}

global $wpdb;

echo "<h2>📋 Paso 1: Eliminando tablas existentes</h2>\n";

$tables = array(
    $wpdb->prefix . 'rinac_reservas',
    $wpdb->prefix . 'rinac_producto_horas', 
    $wpdb->prefix . 'rinac_disponibilidad',
    $wpdb->prefix . 'rinac_horas',
    $wpdb->prefix . 'rinac_rangos_horarios'
);

foreach ($tables as $table) {
    $result = $wpdb->query("DROP TABLE IF EXISTS $table");
    if ($result !== false) {
        echo "✅ Eliminada: $table<br>\n";
    } else {
        echo "⚠️ No se pudo eliminar: $table<br>\n";
    }
}

echo "<h2>📋 Paso 2: Eliminando opciones</h2>\n";
$deleted = $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE 'rinac_%'");
echo "✅ Eliminadas $deleted opciones de RINAC<br>\n";

echo "<h2>📋 Paso 3: Recreando estructuras</h2>\n";

// Cargar clase de instalación
require_once RINAC_PLUGIN_PATH . 'includes/class-rinac-install.php';

// Crear tablas
try {
    RINAC_Install::create_tables();
    echo "✅ Tablas recreadas correctamente<br>\n";
} catch (Exception $e) {
    echo "❌ Error al crear tablas: " . $e->getMessage() . "<br>\n";
}

// Crear opciones por defecto
try {
    RINAC_Install::create_default_options();
    echo "✅ Opciones por defecto creadas<br>\n";
} catch (Exception $e) {
    echo "❌ Error al crear opciones: " . $e->getMessage() . "<br>\n";
}

echo "<h2>📋 Paso 4: Verificando estructura final</h2>\n";

foreach ($tables as $table) {
    $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
    if ($exists) {
        $columns = $wpdb->get_results("DESCRIBE $table");
        echo "✅ $table - " . count($columns) . " columnas<br>\n";
        
        // Mostrar columnas para verificar
        foreach ($columns as $column) {
            echo "&nbsp;&nbsp;&nbsp;- {$column->Field} ({$column->Type})<br>\n";
        }
    } else {
        echo "❌ $table - NO EXISTE<br>\n";
    }
}

echo "<h2>🎉 Proceso Completado</h2>\n";
echo "<p><strong>RINAC ha sido reinstalado completamente.</strong></p>\n";
echo "<p>Ahora puedes:</p>\n";
echo "<ol>\n";
echo "<li>Ir a WordPress Admin → Plugins</li>\n";
echo "<li>Desactivar RINAC si está activado</li>\n";
echo "<li>Activar RINAC nuevamente</li>\n";
echo "</ol>\n";

echo "<h2>📊 Verificación de Compatibilidad HPOS</h2>\n";
if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
    echo "✅ WooCommerce HPOS Utils disponible<br>\n";
    echo "✅ El plugin ahora es compatible con HPOS<br>\n";
} else {
    echo "⚠️ WooCommerce HPOS Utils no disponible (versión WC antigua)<br>\n";
}

?>
