<?php
/**
 * Verificador de estado del plugin RINAC
 * Ejecutar este archivo para verificar que todo esté en orden antes de activar
 */

// Solo permitir acceso si se ejecuta desde línea de comandos o con el parámetro correcto
if (!defined('WP_CLI') && (!isset($_GET['check']) || $_GET['check'] !== 'rinac')) {
    die('Acceso no autorizado');
}

require_once('../../../wp-config.php');

echo "<h1>Verificación del Plugin RINAC</h1>\n";

// Verificar archivos principales
$files_to_check = [
    'rinac.php',
    'includes/class-rinac-install.php',
    'includes/class-rinac-admin.php',
    'includes/class-rinac-frontend.php',
    'includes/class-rinac-product-type.php',
    'includes/class-rinac-calendar.php',
    'includes/class-rinac-validation.php',
    'includes/class-rinac-database.php',
    'includes/class-rinac-template-helper.php',
    'includes/functions.php',
    'templates/forms/booking-form.php',
    'templates/modals/base-modal.php',
    'templates/admin/dashboard.php',
    'assets/css/admin.css',
    'assets/css/frontend.css',
    'assets/js/admin.js',
    'assets/js/frontend.js'
];

echo "<h2>✅ Verificando Archivos</h2>\n";
$missing_files = [];
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "✅ $file\n<br>";
    } else {
        echo "❌ $file - FALTANTE\n<br>";
        $missing_files[] = $file;
    }
}

// Verificar WooCommerce
echo "<h2>✅ Verificando Dependencias</h2>\n";
if (class_exists('WooCommerce')) {
    echo "✅ WooCommerce está disponible\n<br>";
} else {
    echo "❌ WooCommerce NO está disponible - REQUERIDO\n<br>";
}

// Verificar PHP
echo "✅ PHP Version: " . PHP_VERSION . "\n<br>";
if (version_compare(PHP_VERSION, '7.4', '>=')) {
    echo "✅ Versión PHP compatible\n<br>";
} else {
    echo "❌ PHP 7.4+ requerido\n<br>";
}

// Verificar MySQL
global $wpdb;
$mysql_version = $wpdb->db_version();
echo "✅ MySQL Version: $mysql_version\n<br>";

// Verificar permisos de escritura
echo "<h2>✅ Verificando Permisos</h2>\n";
if (is_writable(WP_CONTENT_DIR . '/plugins/rinac/')) {
    echo "✅ Directorio del plugin es escribible\n<br>";
} else {
    echo "❌ El directorio del plugin no es escribible\n<br>";
}

// Resumen
echo "<h2>📋 Resumen</h2>\n";
if (empty($missing_files) && class_exists('WooCommerce')) {
    echo "🎉 <strong>EL PLUGIN ESTÁ LISTO PARA ACTIVAR</strong>\n<br>";
    echo "Puedes proceder a activarlo desde el panel de administración de WordPress.\n<br>";
} else {
    echo "⚠️ <strong>FALTAN ALGUNOS REQUISITOS:</strong>\n<br>";
    if (!empty($missing_files)) {
        echo "- Archivos faltantes: " . implode(', ', $missing_files) . "\n<br>";
    }
    if (!class_exists('WooCommerce')) {
        echo "- WooCommerce debe estar instalado y activado\n<br>";
    }
}

echo "<h2>📖 Próximos Pasos</h2>\n";
echo "1. Activar el plugin desde Plugins → Plugins instalados\n<br>";
echo "2. Ir a RINAC → Configuración para configurar opciones básicas\n<br>";
echo "3. Crear un producto de tipo 'Visitas' para probar\n<br>";
echo "4. Configurar horarios y fechas disponibles\n<br>";
echo "5. Probar el formulario de reserva en el frontend\n<br>";
?>
