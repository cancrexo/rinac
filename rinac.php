<?php
/**
 * Plugin Name: RINAC
 * Plugin URI: https://example.com
 * Description: Rinac Is Not Another Calendar.
 * Version: 0.1.0
 * Author: RINAC
 * Text Domain: rinac
 * Domain Path: /languages
 * Requires at least: 6.6
 * Requires PHP: 7.4
 */

defined( 'ABSPATH' ) || exit;

define( 'RINAC_VERSION', '0.1.0' );
define( 'RINAC_PLUGIN_FILE', __FILE__ );
define( 'RINAC_PATH', plugin_dir_path( __FILE__ ) );
define( 'RINAC_URL', plugin_dir_url( __FILE__ ) );

$rinac_autoload = RINAC_PATH . 'vendor/autoload.php';
if ( file_exists( $rinac_autoload ) ) {
    require_once $rinac_autoload;
}

if ( class_exists( 'RINAC\\Core\\Plugin' ) ) {
    ( new RINAC\Core\Plugin() )->register();
}

/**
 * Activa el plugin.
 *
 * @return void
 */
function rinac_activate(): void {
    if ( class_exists( 'RINAC\\Core\\PostTypesRegistrar' ) ) {
        ( new RINAC\Core\PostTypesRegistrar() )->registerPostTypes();
    }

    flush_rewrite_rules();
}

/**
 * Desactiva el plugin.
 *
 * @return void
 */
function rinac_deactivate(): void {
    flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'rinac_activate' );
register_deactivation_hook( __FILE__, 'rinac_deactivate' );
